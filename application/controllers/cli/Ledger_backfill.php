<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ledger Backfill — replays every historical transaction into the General Ledger.
 *
 *   php index.php cli/ledger_backfill dryrun    report what would be posted, write nothing
 *   php index.php cli/ledger_backfill run       post the entries
 *   php index.php cli/ledger_backfill verify    trial balance + control account reconciliation
 *   php index.php cli/ledger_backfill reconcile  per-loan control account reconciliation
 *   php index.php cli/ledger_backfill reset     remove backfilled entries and start over
 *
 * ── Accounting decisions (industry standard) ─────────────────────────────
 *  1. FULL CHRONOLOGICAL REPLAY. Every voucher carries the real transaction
 *     date, so the ledger is complete and each entry traces to a source
 *     document. No opening-balance shortcut — the detail exists, so it is used.
 *  2. CASH vs BANK is routed from the recorded payment mode. Blank or unknown
 *     modes fall to Cash in Hand, matching the application's own default.
 *  3. APPROPRIATION ORDER on a repayment is fine, then interest, then
 *     principal — each capped at the cash actually received, so income is
 *     never recognised beyond what came in. Whatever remains is principal
 *     recovery. Rows whose stored components disagree with the cash by Rs 1 or
 *     more are listed as exceptions for review.
 *  4. PROCESSING FEE is booked once, inside the disbursement voucher (the fee
 *     is deducted at source). The duplicate rows in member_other_transactions
 *     are skipped so the income is not counted twice.
 *  5. REVERSED transactions are skipped (their net effect is nil) and counted.
 *  6. IDEMPOTENT — a source row already in the ledger is never posted twice,
 *     so the command is safe to re-run.
 */
class Ledger_backfill extends CI_Controller {

    /** Batch size for inserts */
    const CHUNK = 500;

    private $acc = [];        // account_code => id
    private $fy  = [];        // financial years
    private $rows = [];       // pending general_ledger rows
    private $stats = [];
    private $exceptions = [];
    private $voucher_seq = 0;

    public function __construct() {
        parent::__construct();
        if (!is_cli()) { show_404(); }
        $this->load->model('Ledger_model');
        @set_time_limit(0);
    }

    // ──────────────────────────────────────────────────────────── commands

    public function dryrun() { $this->execute(false); }

    public function run()    { $this->execute(true); }

    public function reset() {
        $n = $this->db->where('narration LIKE', 'BF:%')->count_all_results('general_ledger');
        $m = $this->db->where('narration LIKE', 'BF:%')->count_all_results('member_ledger');
        echo "Removing {$n} general_ledger and {$m} member_ledger backfilled rows...\n";
        $this->db->where('narration LIKE', 'BF:%')->delete('general_ledger');
        $this->db->where('narration LIKE', 'BF:%')->delete('member_ledger');
        $this->recompute_account_balances();
        echo "Done. general_ledger now holds " . $this->db->count_all('general_ledger') . " rows.\n";
    }

    // ──────────────────────────────────────────────────────────── engine

    private function execute($commit) {
        $t0 = microtime(true);
        echo $commit ? "=== LEDGER BACKFILL (writing) ===\n\n" : "=== LEDGER BACKFILL (dry run — nothing will be written) ===\n\n";

        $this->load_accounts();
        $this->load_financial_years();
        $this->stats = ['vouchers' => 0, 'legs' => 0, 'skipped_existing' => 0,
                        'skipped_reversed' => 0, 'skipped_no_fy' => 0, 'unbalanced' => 0];

        $this->collect_disbursements();
        $this->collect_loan_payments();
        $this->collect_savings();
        $this->collect_fines();
        $this->collect_other_transactions();

        // Chronological order keeps the ledger readable and member balances sane
        usort($this->rows, function ($a, $b) {
            return [$a['voucher_date'], $a['_seq']] <=> [$b['voucher_date'], $b['_seq']];
        });

        $this->report();

        if (!$commit) {
            printf("\nDry run complete in %.1fs. Nothing was written.\n", microtime(true) - $t0);
            echo "Run 'php index.php cli/ledger_backfill run' to post these entries.\n";
            return;
        }

        $this->write_rows();
        $this->recompute_account_balances();
        $this->backfill_member_ledger();

        printf("\nPosted in %.1fs.\n", microtime(true) - $t0);
        $this->verify();
    }

    // ──────────────────────────────────────────────────────────── sources

    /**
     * Disbursement. The processing fee is withheld from the cheque, so it is
     * recognised here rather than as a separate receipt:
     *   Dr Member Loans (gross)  Cr Cash/Bank (net)  Cr Processing Fee Income
     */
    private function collect_disbursements() {
        $q = $this->db->select('id, loan_number, member_id, principal_amount, processing_fee,
                                disbursement_date, disbursement_mode')
                      ->where('status !=', 'rejected')
                      ->where('disbursement_date IS NOT NULL', null, false)
                      ->order_by('disbursement_date', 'ASC')
                      ->get('loans')->result();

        foreach ($q as $l) {
            $gross = round((float) $l->principal_amount, 2);
            if ($gross <= 0) { continue; }
            $fee   = round((float) ($l->processing_fee ?? 0), 2);
            $net   = round($gross - $fee, 2);
            $cash  = $this->cash_code($l->disbursement_mode);

            $this->add_voucher('payment', $l->disbursement_date, 'loan_disbursement', $l->id, $l->member_id,
                'BF: Disbursement ' . $l->loan_number, [
                    [$this->acc['1201'], $gross, 0],
                    [$this->acc[$cash],  0,      $net],
                    [$this->acc['3200'], 0,      $fee],
                ]);
        }
    }

    /**
     * Repayment, appropriated fine → interest → principal, capped at cash.
     */
    private function collect_loan_payments() {
        $q = $this->db->select('lp.id, lp.loan_id, lp.payment_date, lp.payment_mode, lp.total_amount,
                                lp.principal_component, lp.interest_component, lp.fine_component,
                                lp.is_reversed, l.member_id, l.loan_number')
                      ->from('loan_payments lp')
                      ->join('loans l', 'l.id = lp.loan_id')
                      ->order_by('lp.payment_date', 'ASC')
                      ->get()->result();

        foreach ($q as $p) {
            if (!empty($p->is_reversed)) { $this->stats['skipped_reversed']++; continue; }

            $total = round((float) $p->total_amount, 2);
            if ($total <= 0) { continue; }

            $fine     = max(0, round((float) ($p->fine_component ?? 0), 2));
            $interest = max(0, round((float) ($p->interest_component ?? 0), 2));

            $fine     = min($fine, $total);
            $interest = min($interest, round($total - $fine, 2));
            $principal = round($total - $fine - $interest, 2);

            $stored = round((float)($p->principal_component ?? 0) + (float)($p->interest_component ?? 0)
                          + (float)($p->fine_component ?? 0), 2);
            if (abs($stored - $total) >= 1.00) {
                $this->exceptions[] = sprintf(
                    'payment #%d (%s, %s): stored components %.2f vs cash %.2f — appropriated P %.2f / I %.2f / F %.2f',
                    $p->id, $p->loan_number, $p->payment_date, $stored, $total, $principal, $interest, $fine);
            }

            $cash = $this->cash_code($p->payment_mode);
            $this->add_voucher('receipt', $p->payment_date, 'loan_payment', $p->id, $p->member_id,
                'BF: Repayment ' . $p->loan_number, [
                    [$this->acc[$cash],  $total, 0],
                    [$this->acc['1201'], 0, $principal],
                    [$this->acc['3100'], 0, $interest],
                    [$this->acc['3300'], 0, $fine],
                ]);
        }
    }

    /** Member deposits are a liability; withdrawals discharge it. */
    private function collect_savings() {
        $q = $this->db->select('st.id, st.savings_account_id, st.transaction_type, st.amount,
                                st.transaction_date, st.payment_mode, st.is_reversed, sa.member_id')
                      ->from('savings_transactions st')
                      ->join('savings_accounts sa', 'sa.id = st.savings_account_id')
                      ->order_by('st.transaction_date', 'ASC')
                      ->get()->result();

        foreach ($q as $s) {
            if (!empty($s->is_reversed)) { $this->stats['skipped_reversed']++; continue; }
            $amt = round((float) $s->amount, 2);
            if ($amt < 0.01) { continue; }          // the 0.00 rows fixed earlier

            $cash = $this->cash_code($s->payment_mode);

            if ($s->transaction_type === 'deposit' || $s->transaction_type === 'opening_balance') {
                $legs = [[$this->acc[$cash], $amt, 0], [$this->acc['2101'], 0, $amt]];
                $type = 'receipt';
            } elseif ($s->transaction_type === 'withdrawal') {
                $legs = [[$this->acc['2101'], $amt, 0], [$this->acc[$cash], 0, $amt]];
                $type = 'payment';
            } elseif ($s->transaction_type === 'interest_credit') {
                // Interest owed to members is an expense of the society
                $legs = [[$this->acc['4100'], $amt, 0], [$this->acc['2101'], 0, $amt]];
                $type = 'journal';
            } else {
                continue;   // fine / fine_waiver / adjustment handled elsewhere
            }

            $this->add_voucher($type, $s->transaction_date, 'savings_transaction', $s->id, $s->member_id,
                'BF: Savings ' . $s->transaction_type, $legs);
        }
    }

    /** Fine receipts. */
    private function collect_fines() {
        $q = $this->db->select('id, member_id, paid_amount, payment_date, payment_mode')
                      ->where('status', 'paid')
                      ->where('paid_amount >', 0)
                      ->where('payment_date IS NOT NULL', null, false)
                      ->order_by('payment_date', 'ASC')
                      ->get('fines')->result();

        foreach ($q as $f) {
            $amt  = round((float) $f->paid_amount, 2);
            $cash = $this->cash_code($f->payment_mode);
            $this->add_voucher('receipt', $f->payment_date, 'fine_payment', $f->id, $f->member_id,
                'BF: Fine received', [
                    [$this->acc[$cash],  $amt, 0],
                    [$this->acc['3300'], 0, $amt],
                ]);
        }
    }

    /**
     * Member fees. Processing-fee rows duplicate the disbursement voucher and
     * are skipped so the income is not counted twice.
     */
    private function collect_other_transactions() {
        $q = $this->db->where('status', 'completed')
                      ->where('amount >', 0)
                      ->order_by('transaction_date', 'ASC')
                      ->get('member_other_transactions')->result();

        $income = ['membership_fee' => '3400', 'admission_fee' => '3400', 'late_fee' => '3300', 'bonus' => null];

        foreach ($q as $t) {
            if ($t->transaction_type === 'processing_fee') {
                $this->stats['skipped_dup_fee'] = ($this->stats['skipped_dup_fee'] ?? 0) + 1;
                continue;
            }
            if (!isset($income[$t->transaction_type]) || $income[$t->transaction_type] === null) { continue; }

            $amt  = round((float) $t->amount, 2);
            $cash = $this->cash_code($t->payment_mode);
            $this->add_voucher('receipt', $t->transaction_date, 'member_transaction', $t->id, $t->member_id,
                'BF: ' . ucwords(str_replace('_', ' ', $t->transaction_type)), [
                    [$this->acc[$cash], $amt, 0],
                    [$this->acc[$income[$t->transaction_type]], 0, $amt],
                ]);
        }
    }

    // ──────────────────────────────────────────────────────────── helpers

    private function add_voucher($vtype, $date, $ref_type, $ref_id, $member_id, $narration, $legs) {
        $date = substr((string) $date, 0, 10);

        if (isset($this->seen[$ref_type . ':' . $ref_id])) { return; }

        if ($this->already_posted($ref_type, $ref_id)) {
            $this->stats['skipped_existing']++;
            return;
        }

        $fy = $this->fy_for($date);
        if ($fy === null) {
            $this->stats['skipped_no_fy']++;
            $this->exceptions[] = sprintf('%s #%s dated %s falls outside every defined financial year — not posted.',
                $ref_type, $ref_id, $date);
            return;
        }

        $dr = 0; $cr = 0;
        $keep = [];
        foreach ($legs as $leg) {
            list($account_id, $debit, $credit) = $leg;
            $debit = round((float) $debit, 2); $credit = round((float) $credit, 2);
            if ($debit < 0.005 && $credit < 0.005) { continue; }
            if (!$account_id) { continue; }
            $keep[] = [$account_id, $debit, $credit];
            $dr += $debit; $cr += $credit;
        }
        if (empty($keep)) { return; }

        if (abs($dr - $cr) >= 0.01) {
            $this->stats['unbalanced']++;
            $this->exceptions[] = sprintf('%s #%s dated %s is unbalanced (Dr %.2f / Cr %.2f) — not posted.',
                $ref_type, $ref_id, $date, $dr, $cr);
            return;
        }

        $this->voucher_seq++;
        $vno = strtoupper(substr($vtype, 0, 2)) . 'BF' . str_pad($this->voucher_seq, 7, '0', STR_PAD_LEFT);

        foreach ($keep as $leg) {
            $this->rows[] = [
                'voucher_number'    => $vno,
                'voucher_date'      => $date,
                'voucher_type'      => $vtype,
                'account_id'        => $leg[0],
                'debit_amount'      => $leg[1],
                'credit_amount'     => $leg[2],
                'balance_after'     => 0,
                'narration'         => substr($narration, 0, 255),
                'reference_type'    => $ref_type,
                'reference_id'      => $ref_id,
                'member_id'         => $member_id ?: null,
                'financial_year_id' => $fy,
                'is_posted'         => 1,
                'created_by'        => null,
                '_seq'              => $this->voucher_seq,
            ];
            $this->stats['legs']++;
        }
        $this->stats['vouchers']++;
        $this->seen[$ref_type . ':' . $ref_id] = true;
    }

    private $seen = [];
    private $posted_cache = null;

    private function already_posted($ref_type, $ref_id) {
        if ($this->posted_cache === null) {
            $this->posted_cache = [];
            $rows = $this->db->select('reference_type, reference_id')->distinct()
                             ->where('reference_id IS NOT NULL', null, false)
                             ->get('general_ledger')->result();
            foreach ($rows as $r) { $this->posted_cache[$r->reference_type . ':' . $r->reference_id] = true; }
        }
        return isset($this->posted_cache[$ref_type . ':' . $ref_id]);
    }

    private function cash_code($mode) {
        $m = strtolower(trim((string) $mode));
        return ($m === '' || $m === 'cash') ? '1101' : '1102';
    }

    private function load_accounts() {
        foreach ($this->db->get('chart_of_accounts')->result() as $a) {
            $this->acc[$a->account_code] = $a->id;
        }
        foreach (['1101','1102','1201','2101','3100','3200','3300','3400','4100'] as $code) {
            if (empty($this->acc[$code])) {
                echo "FATAL: chart_of_accounts is missing account {$code}. Aborting.\n";
                exit(1);
            }
        }
    }

    private function load_financial_years() {
        $this->fy = $this->db->order_by('start_date', 'ASC')->get('financial_years')->result();
    }

    private function fy_for($date) {
        foreach ($this->fy as $f) {
            if ($date >= $f->start_date && $date <= $f->end_date) { return $f->id; }
        }
        return null;
    }

    // ──────────────────────────────────────────────────────────── output

    private function report() {
        $by_type = [];
        $dr = 0; $cr = 0;
        foreach ($this->rows as $r) {
            $by_type[$r['reference_type']] = ($by_type[$r['reference_type']] ?? 0) + 1;
            $dr += $r['debit_amount']; $cr += $r['credit_amount'];
        }

        echo "Vouchers to post by source:\n";
        foreach ($by_type as $t => $n) { printf("  %-22s %6d legs\n", $t, $n); }
        printf("\n  %-22s %6d vouchers, %d legs\n", 'TOTAL', $this->stats['vouchers'], $this->stats['legs']);
        printf("  %-22s Dr %15s   Cr %15s   %s\n", 'control total',
            number_format($dr, 2), number_format($cr, 2),
            abs($dr - $cr) < 0.01 ? 'BALANCED' : 'UNBALANCED');

        echo "\nSkipped:\n";
        printf("  already in ledger      %6d\n", $this->stats['skipped_existing']);
        printf("  reversed transactions  %6d\n", $this->stats['skipped_reversed']);
        printf("  duplicate fee rows     %6d\n", $this->stats['skipped_dup_fee'] ?? 0);
        printf("  outside financial year %6d\n", $this->stats['skipped_no_fy']);
        printf("  unbalanced (bad data)  %6d\n", $this->stats['unbalanced']);

        if ($this->exceptions) {
            $n = count($this->exceptions);
            printf("\nExceptions for review (%d):\n", $n);
            foreach (array_slice($this->exceptions, 0, 20) as $e) { echo "  - {$e}\n"; }
            if ($n > 20) { printf("  ... and %d more\n", $n - 20); }
        }
    }

    private function write_rows() {
        $total = count($this->rows);
        echo "\nWriting {$total} ledger rows...\n";
        $chunk = [];
        $done = 0;
        foreach ($this->rows as $r) {
            unset($r['_seq']);
            $chunk[] = $r;
            if (count($chunk) >= self::CHUNK) {
                $this->db->insert_batch('general_ledger', $chunk);
                $done += count($chunk); $chunk = [];
                printf("  %d / %d\r", $done, $total);
            }
        }
        if ($chunk) { $this->db->insert_batch('general_ledger', $chunk); $done += count($chunk); }
        printf("  %d / %d rows written\n", $done, $total);
    }

    /** Recompute every account balance from the ledger (normal-balance aware). */
    private function recompute_account_balances() {
        echo "Recomputing account balances...\n";
        $this->db->query("
            UPDATE chart_of_accounts coa
            LEFT JOIN (
                SELECT account_id,
                       SUM(debit_amount)  AS dr,
                       SUM(credit_amount) AS cr
                  FROM general_ledger GROUP BY account_id
            ) g ON g.account_id = coa.id
            SET coa.current_balance = CASE
                WHEN coa.account_type IN ('asset','expense') THEN COALESCE(g.dr,0) - COALESCE(g.cr,0)
                ELSE COALESCE(g.cr,0) - COALESCE(g.dr,0) END");
    }

    /** Member-wise subsidiary ledger with running balances. */
    private function backfill_member_ledger() {
        echo "Building member ledger...\n";

        $rows = $this->db->query("
            SELECT member_id, voucher_date, reference_type, reference_id,
                   SUM(debit_amount) dr, SUM(credit_amount) cr, MIN(narration) narration
              FROM general_ledger
             WHERE member_id IS NOT NULL AND narration LIKE 'BF:%'
               AND account_id = (SELECT id FROM chart_of_accounts WHERE account_code='1201')
             GROUP BY member_id, voucher_date, reference_type, reference_id, voucher_number
             ORDER BY member_id, voucher_date, reference_id")->result();

        $balances = [];
        $batch = [];
        foreach ($rows as $r) {
            $mid = (int) $r->member_id;
            if (!isset($balances[$mid])) { $balances[$mid] = 0.0; }
            $balances[$mid] += (float) $r->dr - (float) $r->cr;

            $batch[] = [
                'member_id'        => $mid,
                'transaction_date' => $r->voucher_date,
                'transaction_type' => $r->reference_type,
                'reference_type'   => $r->reference_type,
                'reference_id'     => $r->reference_id,
                'debit_amount'     => round((float) $r->dr, 2),
                'credit_amount'    => round((float) $r->cr, 2),
                'balance_after'    => round($balances[$mid], 2),
                'narration'        => $r->narration,
            ];
            if (count($batch) >= self::CHUNK) {
                $this->db->insert_batch('member_ledger', $batch); $batch = [];
            }
        }
        if ($batch) { $this->db->insert_batch('member_ledger', $batch); }
        printf("  %d member ledger rows\n", count($rows));
    }

    // ──────────────────────────────────────────────────────────── verify

    public function verify() {
        echo "\n=== VERIFICATION ===\n";

        $tb = $this->db->query("
            SELECT coa.account_code, coa.account_name, coa.account_type,
                   COALESCE(SUM(gl.debit_amount),0) dr, COALESCE(SUM(gl.credit_amount),0) cr
              FROM chart_of_accounts coa
              LEFT JOIN general_ledger gl ON gl.account_id = coa.id
             GROUP BY coa.id HAVING dr <> 0 OR cr <> 0
             ORDER BY coa.account_code")->result();

        printf("\n%-6s %-28s %16s %16s\n", 'CODE', 'ACCOUNT', 'DEBIT', 'CREDIT');
        $td = 0; $tc = 0;
        foreach ($tb as $a) {
            printf("%-6s %-28s %16s %16s\n", $a->account_code, $a->account_name,
                number_format($a->dr, 2), number_format($a->cr, 2));
            $td += $a->dr; $tc += $a->cr;
        }
        printf("%-35s %16s %16s\n", 'TOTAL', number_format($td, 2), number_format($tc, 2));
        echo (abs($td - $tc) < 0.01 ? "OK   trial balance balances\n" : "FAIL trial balance does NOT balance\n");

        // Control accounts vs the operational tables they summarise
        echo "\nControl account reconciliation:\n";
        $gl_loans = (float) $this->db->query("
            SELECT COALESCE(SUM(gl.debit_amount - gl.credit_amount),0) v FROM general_ledger gl
             JOIN chart_of_accounts c ON c.id=gl.account_id WHERE c.account_code='1201'")->row()->v;
        $op_loans = (float) $this->db->query("
            SELECT COALESCE(SUM(outstanding_principal),0) v FROM loans WHERE status IN ('active','overdue','npa')")->row()->v;
        $this->recon_line('Member Loans (1201)', $gl_loans, $op_loans);

        $gl_sav = (float) $this->db->query("
            SELECT COALESCE(SUM(gl.credit_amount - gl.debit_amount),0) v FROM general_ledger gl
             JOIN chart_of_accounts c ON c.id=gl.account_id WHERE c.account_code='2101'")->row()->v;
        $op_sav = (float) $this->db->query("
            SELECT COALESCE(SUM(current_balance),0) v FROM savings_accounts")->row()->v;
        $this->recon_line('Savings Deposits (2101)', $gl_sav, $op_sav);

        $bs = $this->Ledger_model->get_balance_sheet();
        printf("\nBalance sheet: assets %s | liabilities %s | equity %s | difference %s -> %s\n",
            number_format($bs['total_assets'], 2), number_format($bs['total_liabilities'], 2),
            number_format($bs['total_equity'], 2), number_format($bs['difference'], 2),
            $bs['balanced'] ? 'OK  Assets = Liabilities + Equity' : 'FAIL');
    }

    /**
     * Per-loan reconciliation of the Member Loans control account.
     *
     * A variance means the loan record and its payment records disagree — the
     * ledger is only reporting it. Each line needs an accounting decision
     * (write-off, waiver, or a correction to the source data); nothing is
     * auto-posted, because inventing an entry would misstate the books.
     *
     *   php index.php cli/ledger_backfill reconcile
     */
    public function reconcile() {
        echo "=== MEMBER LOANS (1201) CONTROL ACCOUNT RECONCILIATION ===\n\n";

        $rows = $this->db->query("
            SELECT l.id, l.loan_number, l.status, l.closure_type,
                   ROUND(l.principal_amount, 2) AS disbursed,
                   ROUND(COALESCE(g.repaid, 0), 2) AS repaid,
                   ROUND(l.principal_amount - COALESCE(g.repaid, 0), 2) AS ledger_balance,
                   ROUND(IF(l.status IN ('active','overdue','npa'), l.outstanding_principal, 0), 2) AS book_balance,
                   ROUND((l.principal_amount - COALESCE(g.repaid, 0))
                         - IF(l.status IN ('active','overdue','npa'), l.outstanding_principal, 0), 2) AS variance
              FROM loans l
              LEFT JOIN (
                    SELECT lp.loan_id, SUM(gl.credit_amount) AS repaid
                      FROM general_ledger gl
                      JOIN chart_of_accounts c ON c.id = gl.account_id AND c.account_code = '1201'
                      JOIN loan_payments lp ON lp.id = gl.reference_id
                     WHERE gl.reference_type = 'loan_payment'
                     GROUP BY lp.loan_id
              ) g ON g.loan_id = l.id
             WHERE l.disbursement_date IS NOT NULL
            HAVING ABS(variance) >= 1
             ORDER BY ABS(variance) DESC")->result();

        if (empty($rows)) {
            echo "No variances. The control account agrees with the loan book.\n";
            return;
        }

        printf("%-16s %-11s %14s %14s %14s %14s\n",
            'LOAN', 'STATUS', 'DISBURSED', 'REPAID (GL)', 'BOOK BAL', 'VARIANCE');
        echo str_repeat('-', 92) . "\n";

        $total = 0;
        foreach ($rows as $r) {
            printf("%-16s %-11s %14s %14s %14s %14s\n",
                $r->loan_number, $r->status,
                number_format($r->disbursed, 2), number_format($r->repaid, 2),
                number_format($r->book_balance, 2), number_format($r->variance, 2));
            $total += $r->variance;
        }
        echo str_repeat('-', 92) . "\n";
        printf("%-73s %14s\n", 'TOTAL VARIANCE', number_format($total, 2));

        echo "\nWhat each variance means:\n";
        echo "  positive — the ledger still carries principal the loan book has already cleared.\n";
        echo "             Usually a closure or foreclosure that wrote the balance off without a\n";
        echo "             matching receipt. Needs a write-off entry, or a correction to the loan.\n";
        echo "  negative — payments exceed what was disbursed. Usually legacy component data\n";
        echo "             copied from another loan during bulk import.\n";
        echo "\nNothing above has been auto-adjusted.\n";
    }

    private function recon_line($label, $ledger, $operational) {
        $diff = round($ledger - $operational, 2);
        printf("  %-26s ledger %16s   operational %16s   diff %14s %s\n",
            $label, number_format($ledger, 2), number_format($operational, 2), number_format($diff, 2),
            abs($diff) < 1 ? 'OK' : 'REVIEW');
    }
}
