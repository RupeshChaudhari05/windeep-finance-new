<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Passbook_import - bulk passbook / collection entries from an Excel sheet, for the AI Assistant.
 *
 *   template()  → the sample .xlsx admins fill in
 *   read()      → rows from an uploaded .xlsx / .xls / .csv
 *   resolve()   → finds member, loan / savings account and the allocation of every row with the same
 *                 identifiers the Bank Import uses (member code, phone, SAV…, LN…), checks duplicates.
 *                 Nothing is written.
 *   execute()   → each entry becomes a credit in bank_transactions (Cash Account for cash, the bank
 *                 account otherwise) and is mapped with Bank_model::apply_mappings() — the exact code of
 *                 the Bank Mapping screen — so it shows up in Mapping / Reconciliation like any statement line.
 *
 * Rows that share Date + Mode + Reference are one receipt split across allocations (e.g. EMI + savings).
 */
class Passbook_import {

    const HEADERS = ['Date', 'Member (Code / Name / Phone)', 'Amount', 'Type', 'Loan / Savings A/c No', 'Mode', 'Reference / UTR', 'Remarks'];
    const TYPES   = ['auto', 'emi', 'savings', 'fine', 'membership_fee', 'joining_fee', 'admission_fee', 'share_capital', 'processing_fee', 'penalty', 'other_fee'];
    const MODES   = ['cash', 'upi', 'bank_transfer', 'cheque'];
    const MAX_ROWS = 500;

    private $CI;
    private $planned = [];   // installment id => amount already allocated by earlier rows of this file

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model(['Member_model', 'Bank_model']);
        require_once FCPATH . 'vendor/autoload.php';
    }

    // ───────────────────────────── Template ─────────────────────────────

    public function template() {
        $book = new Spreadsheet();
        $book->getProperties()->setCreator('Windeep Finance')->setTitle('Passbook entries for AI Assistant');
        $s = $book->getActiveSheet()->setTitle('Passbook');
        $s->fromArray(self::HEADERS, null, 'A1');
        $s->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A3C6E']],
        ]);

        // Sample rows built from real members / loans / accounts so the file can be tried as it is
        $db = $this->CI->db;
        $pick = function ($skip = []) use ($db) {
            $db->select('m.id, m.member_code, m.first_name, m.last_name, l.loan_number, l.emi_amount, sa.account_number')->from('members m')
               ->join('loans l', "l.member_id = m.id AND l.status = 'active'")->join('savings_accounts sa', "sa.member_id = m.id AND sa.status = 'active'")
               ->where('m.deleted_at', null)->where('m.status', 'active')
               ->where("(SELECT COUNT(*) FROM loans x WHERE x.member_id = m.id AND x.status IN ('active','npa')) = 1", null, false)
               ->where("(SELECT COUNT(*) FROM savings_accounts y WHERE y.member_id = m.id AND y.status = 'active') = 1", null, false);
            if ($skip) $db->where_not_in('m.id', $skip);
            return $db->order_by('m.id', 'ASC')->limit(1)->get()->row();
        };
        $a = $pick() ?: (object) ['id' => 0, 'member_code' => 'MEMB000001', 'first_name' => 'Ramesh', 'last_name' => 'Patil', 'loan_number' => 'LN2026000001', 'emi_amount' => 3500, 'account_number' => 'SAV2026000001'];
        $b = $pick([$a->id]) ?: $a;
        $saver = $db->select('m.member_code, m.phone, sa.account_number')->from('members m')->join('savings_accounts sa', "sa.member_id = m.id AND sa.status = 'active'")
            ->where('m.deleted_at', null)->where("m.id NOT IN (SELECT member_id FROM loans WHERE status IN ('active','npa'))", null, false)
            ->where("(SELECT COUNT(*) FROM members p WHERE p.phone = m.phone) = 1", null, false)->limit(1)->get()->row();
        $fined = $db->select('m.member_code, f.balance_amount')->from('fines f')->join('members m', 'm.id = f.member_id')
            ->where_in('f.status', ['pending', 'partial'])->where('m.deleted_at', null)->order_by('f.id', 'ASC')->limit(1)->get()->row();
        $emi_a = round((float) $a->emi_amount);
        $emi_b = round((float) $b->emi_amount);
        $d = function ($days) { return date('d/m/Y', strtotime("-{$days} days")); };
        $ref = function ($p, $n) { return $p . date('ymd') . $n; };

        $rows = [
            [$d(6), $a->member_code, $emi_a, 'emi', $a->loan_number, 'cash', '', 'Monthly EMI (cash)'],
            [$d(6), $a->member_code, 1000, 'savings', $a->account_number, 'cash', '', 'Monthly savings (cash)'],
            [$d(5), trim($b->first_name . ' ' . $b->last_name), $emi_b + 1000, 'auto', '', 'upi', $ref('UPI', '4521'), 'auto: EMI due first, balance to savings'],
            [$d(4), $saver ? $saver->phone : '9876543210', 2000, 'savings', '', 'bank_transfer', $ref('NEFT', '77'), 'Member found by mobile number'],
            [$d(3), $b->member_code, $emi_b, 'emi', $b->loan_number, 'upi', $ref('UPI', '9001'), 'One UPI receipt split in 2 rows: EMI part'],
            [$d(3), $b->member_code, 500, 'savings', $b->account_number, 'upi', $ref('UPI', '9001'), '…and savings part (same Date + Mode + Reference)'],
            [$d(2), $fined ? $fined->member_code : $a->member_code, $fined ? round((float) $fined->balance_amount) : 100, 'fine', '', 'cash', '', 'Pending fine paid'],
            [$d(1), $saver ? $saver->member_code : $a->member_code, 500, 'membership_fee', '', 'cash', '', 'Membership fee'],
        ];
        $s->fromArray($rows, null, 'A2');
        $last = 2 + count($rows) - 1;
        $s->getStyle('C2:C200')->getNumberFormat()->setFormatCode('#,##0.00');
        $s->getStyle('A2:A200')->getNumberFormat()->setFormatCode('@');
        foreach (['A' => 12, 'B' => 30, 'C' => 12, 'D' => 16, 'E' => 22, 'F' => 15, 'G' => 22, 'H' => 48] as $col => $w) {
            $s->getColumnDimension($col)->setWidth($w);
        }
        $s->freezePane('A2');
        $s->getStyle('A2:H' . $last)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3F6FB');

        // Dropdowns for Type and Mode
        $lists = $book->createSheet()->setTitle('Lists');
        foreach (self::TYPES as $i => $t) $lists->setCellValue('A' . ($i + 1), $t);
        foreach (self::MODES as $i => $t) $lists->setCellValue('B' . ($i + 1), $t);
        foreach (['D' => '=Lists!$A$1:$A$' . count(self::TYPES), 'F' => '=Lists!$B$1:$B$' . count(self::MODES)] as $col => $formula) {
            $v = $s->getCell($col . '2')->getDataValidation();
            $v->setType(DataValidation::TYPE_LIST)->setAllowBlank(true)->setShowDropDown(true)->setShowErrorMessage(true)
              ->setErrorTitle('Not in the list')->setError('Choose a value from the list.')->setFormula1($formula);
            $s->setDataValidation($col . '2:' . $col . '500', clone $v);
        }

        $help = $book->createSheet()->setTitle('Instructions');
        $help->fromArray([
            ['Passbook entries → AI Assistant'],
            [''],
            ['How to use'],
            ['1. Fill one row per entry on the "Passbook" sheet (delete the sample rows).'],
            ['2. In AI Assistant click 📎 and choose this file.'],
            ['3. The assistant shows a preview: member, loan / account, allocation and any problem per row.'],
            ['4. Press "Save entries". Nothing is saved before that. Rows already in the system are skipped as duplicates, so re-uploading is safe.'],
            [''],
            ['Columns'],
            ['Date', 'DD/MM/YYYY (also DD-MM-YYYY or YYYY-MM-DD). Not in the future.'],
            ['Member', 'Member code (MEMB000001), mobile number, or full name in English letters. Can be blank when Loan / A/c No is filled.'],
            ['Amount', 'Amount received in ₹ (numbers only).'],
            ['Type', 'auto, emi, savings, fine, membership_fee, joining_fee, admission_fee, share_capital, processing_fee, penalty, other_fee.'],
            ['Loan / Savings A/c No', 'Optional. Loan number (LN…) for emi, savings account (SAV…) for savings — needed when the member has more than one.'],
            ['Mode', 'cash, upi, bank_transfer, cheque. Cash rows go to the "Cash Account", others to the bank account.'],
            ['Reference / UTR', 'UTR / cheque no. Rows with the same Date + Mode + Reference are ONE receipt split across allocations.'],
            ['Remarks', 'Free text, saved with the entry.'],
            [''],
            ['Type "auto" (same rules as the application)'],
            ['• Member with one active loan: the due EMI is paid first, the balance goes to savings.'],
            ['• Amount smaller than the due EMI → marked for review (the app would take the shortfall from savings), choose emi or savings.'],
            ['• Member without a loan: whole amount to savings.'],
            ['• Several loans / several savings accounts → fill Loan / Savings A/c No.'],
            [''],
            ['What happens on save'],
            ['Each receipt is created as a credit line in Bank Transactions and mapped exactly like the Bank Mapping screen:'],
            ['EMI → loan payment on the next unpaid installment (overpayment to savings, underpayment from savings, as per loan rules);'],
            ['savings → deposit applied to the oldest dues; fine → oldest pending fine; fees → member other transactions.'],
            ['Every entry can be seen and reversed from Bank → Mapping.'],
        ], null, 'A1');
        $help->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        foreach (['A3', 'A9', 'A19', 'A25'] as $c) $help->getStyle($c)->getFont()->setBold(true);
        $help->getColumnDimension('A')->setWidth(28);
        $help->getColumnDimension('B')->setWidth(110);

        $book->setActiveSheetIndex(0);
        return $book;
    }

    // ───────────────────────────── Read ─────────────────────────────

    /** @return array rows: [row, date, member, amount, type, account, mode, reference, remarks] */
    public function read($path) {
        $book = IOFactory::load($path);
        $sheet = $book->getSheetByName('Passbook') ?: $book->getActiveSheet();
        $data = $sheet->toArray(null, true, false, false);
        if (!$data) return [];

        $head = array_map(function ($h) { return strtolower(trim(preg_replace('/[^a-z]+/i', ' ', (string) $h))); }, array_shift($data));
        $find = function (array $words, $default) use ($head) {
            foreach ($head as $i => $h) foreach ($words as $w) if ($h !== '' && strpos($h, $w) !== false) return $i;
            return $default;
        };
        $col = [
            'date'      => $find(['date'], 0),
            'member'    => $find(['member', 'name', 'code'], 1),
            'amount'    => $find(['amount', 'credit', 'deposit'], 2),
            'type'      => $find(['type', 'head', 'for'], 3),
            'account'   => $find(['loan', 'savings a', 'a c', 'account'], 4),
            'mode'      => $find(['mode'], 5),
            'reference' => $find(['reference', 'utr', 'ref', 'cheque'], 6),
            'remarks'   => $find(['remark', 'narration', 'description', 'note'], 7),
        ];

        $rows = [];
        foreach ($data as $i => $r) {
            $get = function ($k) use ($r, $col) { return isset($r[$col[$k]]) ? $r[$col[$k]] : null; };
            if (trim(implode('', array_map('strval', array_filter($r, function ($v) { return $v !== null; })))) === '') continue;
            $rows[] = [
                'row'       => $i + 2,
                'date_raw'  => $get('date'),
                'member'    => trim((string) $get('member')),
                'amount'    => $get('amount'),
                'type'      => strtolower(trim((string) $get('type'))),
                'account'   => strtoupper(trim((string) $get('account'))),
                'mode'      => strtolower(trim((string) $get('mode'))),
                'reference' => trim((string) $get('reference')),
                'remarks'   => trim((string) $get('remarks')),
            ];
            if (count($rows) >= self::MAX_ROWS) break;
        }
        return $rows;
    }

    // ───────────────────────────── Resolve (no writes) ─────────────────────────────

    /**
     * @param callable|null $transliterate fn(array $texts): array text => Latin text (AI help for Devanagari names)
     */
    public function resolve(array $rows, $transliterate = null) {
        $db = $this->CI->db;
        $cash_acc = $db->where('is_active', 1)->like('account_name', 'cash')->order_by('is_primary', 'DESC')->limit(1)->get('bank_accounts')->row();
        $bank_acc = $db->where('is_active', 1)->not_like('account_name', 'cash')->order_by('is_primary', 'DESC')->order_by('id', 'ASC')->limit(1)->get('bank_accounts')->row();

        // Devanagari / other-script member names → ask the AI for the English spelling once
        $latin = [];
        if ($transliterate) {
            $need = array_values(array_unique(array_filter(array_column($rows, 'member'), function ($t) { return $t !== '' && preg_match('/[^\x00-\x7F]/u', $t); })));
            if ($need) { try { $latin = (array) call_user_func($transliterate, $need); } catch (Exception $e) { $latin = []; } }
        }

        $out = [];
        $seen = [];
        $this->planned = [];
        foreach ($rows as $r) {
            $r['issues'] = [];
            $r['allocations'] = [];
            $r['notes'] = [];
            $r['status'] = 'ready';

            // Date / amount / mode / type
            $r['date'] = $this->_date($r['date_raw']);
            if (!$r['date']) $r['issues'][] = 'Date "' . $r['date_raw'] . '" not understood';
            elseif ($r['date'] > date('Y-m-d')) $r['issues'][] = 'Date is in the future';
            $r['amount'] = round((float) preg_replace('/[^0-9.\-]/', '', strtr((string) $r['amount'], ['०' => 0, '१' => 1, '२' => 2, '३' => 3, '४' => 4, '५' => 5, '६' => 6, '७' => 7, '८' => 8, '९' => 9])), 2);
            if ($r['amount'] <= 0) $r['issues'][] = 'Amount missing';
            $r['mode'] = $this->_mode($r['mode'], $r['reference']);
            $r['type'] = $this->_type($r['type'], $r['remarks']);
            if ($r['type'] === null) $r['issues'][] = 'Unknown Type';
            $r['bank_account'] = $r['mode'] === 'cash' ? $cash_acc : $bank_acc;
            if (!$r['bank_account']) $r['issues'][] = 'No active ' . ($r['mode'] === 'cash' ? '"Cash Account"' : 'bank account') . ' in Bank → Accounts';

            // Loan / savings account named on the row
            $loan = $acc = null;
            if ($r['account'] !== '') {
                $loan = $db->where('loan_number', $r['account'])->get('loans')->row();
                if (!$loan) $acc = $db->where('account_number', $r['account'])->get('savings_accounts')->row();
                if (!$loan && !$acc) $r['issues'][] = 'Loan / account "' . $r['account'] . '" not found';
            }

            // Member: column → loan / account → identifiers in remarks / reference (Bank Import rules)
            $member = null;
            $text = $r['member'];
            if ($text !== '' && isset($latin[$text]) && is_string($latin[$text])) $text = $latin[$text];
            if ($text !== '') {
                $found = $this->_members($text);
                if (count($found) === 1) $member = $found[0];
                elseif ($found) $r['issues'][] = count($found) . ' members match "' . $r['member'] . '" — use the member code';
                elseif (!$loan && !$acc) $r['issues'][] = 'Member "' . $r['member'] . '" not found';
            }
            $owner = $loan ? (int) $loan->member_id : ($acc ? (int) $acc->member_id : null);
            if (!$member && $owner && ($text === '' || count($this->_members($text)) === 0)) {
                $member = $db->where('id', $owner)->get('members')->row();
            }
            if (!$member && $text === '' && !$owner) {
                $member = $this->_detect($r['remarks'] . ' ' . $r['reference']);
                if (!$member) $r['issues'][] = 'Member missing';
            }
            if ($member && $owner && (int) $member->id !== $owner) $r['issues'][] = $r['account'] . ' belongs to another member';
            $r['member_id'] = $member ? (int) $member->id : null;
            $r['member_label'] = $member ? $member->member_code . ' — ' . trim($member->first_name . ' ' . $member->last_name) : ($r['member'] ?: '—');

            if ($member && !$r['issues']) $this->_allocate($r, $member, $loan, $acc);

            // Duplicates: same reference already imported / same entry already recorded / repeated in this file
            if (!$r['issues'] && $r['allocations']) {
                if ($r['reference'] !== '' && $r['mode'] !== 'cash'
                    && $db->group_start()->where('utr_number', $r['reference'])->or_where('reference_number', $r['reference'])->group_end()
                          ->where('transaction_type', 'credit')->where('amount >=', $r['amount'])->count_all_results('bank_transactions') > 0
                    && !isset($seen['ref:' . $r['date'] . $r['mode'] . $r['reference']])) {
                    $r['status'] = 'duplicate';
                    $r['issues'][] = 'Reference ' . $r['reference'] . ' is already in Bank Transactions';
                } elseif ($this->_already_recorded($r)) {
                    $r['status'] = 'duplicate';
                    $r['issues'][] = 'Same amount for this member on this date is already recorded';
                }
                $key = $r['date'] . '|' . $r['member_id'] . '|' . $r['amount'] . '|' . $r['type'] . '|' . $r['reference'];
                if (isset($seen[$key])) { $r['status'] = 'duplicate'; $r['issues'][] = 'Same as row ' . $seen[$key]; }
                $seen[$key] = $r['row'];
                if ($r['reference'] !== '') $seen['ref:' . $r['date'] . $r['mode'] . $r['reference']] = true;
            }
            if ($r['status'] === 'ready' && ($r['issues'] || !$r['allocations'])) $r['status'] = 'review';
            unset($r['date_raw']);
            $r['bank_account_id'] = $r['bank_account'] ? (int) $r['bank_account']->id : null;
            $r['bank_account_name'] = $r['bank_account'] ? $r['bank_account']->account_name : '';
            unset($r['bank_account']);
            $out[] = $r;
        }

        $sum = ['rows' => count($out), 'ready' => 0, 'review' => 0, 'duplicate' => 0, 'amount_ready' => 0.0];
        foreach ($out as $r) {
            $sum[$r['status']]++;
            if ($r['status'] === 'ready') $sum['amount_ready'] += $r['amount'];
        }
        return ['rows' => $out, 'summary' => $sum];
    }

    /** Allocation of one row (same targets the Bank Mapping screen sends). */
    private function _allocate(array &$r, $member, $loan, $acc) {
        $db = $this->CI->db;
        $mid = (int) $member->id;
        $loans = $loan ? [$loan] : $db->where('member_id', $mid)->where_in('status', ['active', 'npa'])->get('loans')->result();
        $accs  = $acc ? [$acc] : $db->where('member_id', $mid)->where('status', 'active')->get('savings_accounts')->result();
        $next = function ($l) use ($db) {
            // Skip EMIs already covered by earlier rows of this file (two months paid in one sheet)
            foreach ($db->where('loan_id', $l->id)->where_in('status', ['overdue', 'pending', 'partial', 'upcoming'])
                        ->order_by('installment_number', 'ASC')->limit(24)->get('loan_installments')->result() as $inst) {
                $due = round((float) $inst->emi_amount - (float) $inst->total_paid, 2);
                $planned = $this->planned[$inst->id] ?? 0;
                if ($planned + 0.01 < $due) { $inst->total_paid = (float) $inst->total_paid + $planned; return $inst; }
            }
            return null;
        };
        $emi_alloc = function ($l, $amount) use ($next, &$r) {
            if (!in_array($l->status, ['active', 'npa'], true)) { $r['issues'][] = 'Loan ' . $l->loan_number . ' is ' . $l->status; return false; }
            $inst = $next($l);
            if (!$inst) { $r['issues'][] = 'Loan ' . $l->loan_number . ' has no unpaid EMI'; return false; }
            $this->planned[$inst->id] = ($this->planned[$inst->id] ?? 0) + round($amount, 2);
            $r['allocations'][] = ['type' => 'emi', 'related' => 'loan_' . $inst->id, 'amount' => round($amount, 2),
                                   'label' => 'EMI #' . $inst->installment_number . ' ' . $l->loan_number];
            return round((float) $inst->emi_amount - (float) $inst->total_paid, 2);
        };
        $sav_alloc = function ($a, $amount) use (&$r) {
            $r['allocations'][] = ['type' => 'savings', 'related' => 'savings_' . $a->id, 'amount' => round($amount, 2), 'label' => 'Savings ' . $a->account_number];
        };

        switch ($r['type']) {
            case 'emi':
                if ($acc && !$loan) { $r['issues'][] = $r['account'] . ' is a savings account, not a loan'; return; }
                if (count($loans) !== 1) { $r['issues'][] = $loans ? 'Member has ' . count($loans) . ' active loans — fill Loan No' : 'Member has no active loan'; return; }
                $due = $emi_alloc($loans[0], $r['amount']);
                if ($due !== false && $r['amount'] + 0.01 < $due) $r['notes'][] = 'Less than EMI due ₹' . number_format($due, 2) . ' — shortfall is taken from savings (loan rule)';
                if ($due !== false && $r['amount'] > $due + 1) $r['notes'][] = 'More than EMI due ₹' . number_format($due, 2) . ' — excess goes to savings (loan rule)';
                return;
            case 'savings':
                if ($loan && !$acc) { $r['issues'][] = $r['account'] . ' is a loan, not a savings account'; return; }
                if (count($accs) !== 1) { $r['issues'][] = $accs ? 'Member has ' . count($accs) . ' savings accounts — fill A/c No' : 'Member has no active savings account'; return; }
                $sav_alloc($accs[0], $r['amount']);
                return;
            case 'fine':
                $fine = $db->where('member_id', $mid)->where_in('status', ['pending', 'partial'])->order_by('fine_date', 'ASC')->get('fines')->row();
                if (!$fine) { $r['issues'][] = 'Member has no pending fine'; return; }
                $r['allocations'][] = ['type' => 'fine', 'related' => 'fine_' . $fine->id, 'amount' => $r['amount'], 'label' => 'Fine ' . ($fine->fine_code ?? '#' . $fine->id)];
                if ($r['amount'] > (float) $fine->balance_amount + 0.01) $r['notes'][] = 'More than the pending fine (₹' . number_format((float) $fine->balance_amount, 2) . ')';
                return;
            case 'auto':
                if (count($loans) > 1) { $r['issues'][] = 'Member has ' . count($loans) . ' active loans — set Type and Loan No'; return; }
                if ($loans) {
                    $inst = $next($loans[0]);
                    $due = $inst ? round((float) $inst->emi_amount - (float) $inst->total_paid, 2) : 0;
                    if ($inst && $r['amount'] + 0.01 < $due) {
                        $r['issues'][] = '₹' . number_format($r['amount'], 2) . ' is less than the EMI due ₹' . number_format($due, 2) . ' — set Type emi (shortfall from savings) or savings';
                        return;
                    }
                    if ($inst) {
                        $rest = round($r['amount'] - $due, 2);
                        if ($rest > 0.5 && count($accs) === 1) {
                            $emi_alloc($loans[0], $due);
                            $sav_alloc($accs[0], $rest);
                        } else {
                            $emi_alloc($loans[0], $r['amount']);
                            if ($rest > 0.5) $r['notes'][] = 'Excess ₹' . number_format($rest, 2) . ' goes to savings by the loan rule';
                        }
                        return;
                    }
                }
                if (count($accs) !== 1) { $r['issues'][] = $accs ? 'Member has ' . count($accs) . ' savings accounts — fill A/c No' : 'No active loan or savings account'; return; }
                $sav_alloc($accs[0], $r['amount']);
                return;
            default:   // fees
                $fee = $r['type'] === 'other_fee' ? 'other' : $r['type'];
                $r['allocations'][] = ['type' => 'other_fee_' . $fee, 'related' => '', 'amount' => $r['amount'], 'label' => ucwords(str_replace('_', ' ', $fee))];
        }
    }

    private function _already_recorded(array $r) {
        $db = $this->CI->db;
        foreach ($r['allocations'] as $a) {
            if ($a['type'] === 'emi') {
                $n = $db->from('loan_payments lp')->join('loans l', 'l.id = lp.loan_id')->where('l.member_id', $r['member_id'])
                        ->where('lp.payment_date', $r['date'])->where('lp.total_amount', $a['amount'])->where('lp.is_reversed', 0)->count_all_results();
            } elseif ($a['type'] === 'savings') {
                $n = $db->from('savings_transactions st')->join('savings_accounts sa', 'sa.id = st.savings_account_id')->where('sa.member_id', $r['member_id'])
                        ->where('st.transaction_date', $r['date'])->where('st.amount', $a['amount'])->where('st.transaction_type', 'deposit')
                        ->where('st.is_reversed', 0)->count_all_results();
            } elseif (strpos($a['type'], 'other_fee_') === 0) {
                $n = $db->where('member_id', $r['member_id'])->where('transaction_type', substr($a['type'], 10))
                        ->where('transaction_date', $r['date'])->where('amount', $a['amount'])->where('reversed_at', null)->count_all_results('member_other_transactions');
            } else {
                continue;
            }
            if ($n > 0) return true;
        }
        return false;
    }

    // ───────────────────────────── Execute ─────────────────────────────

    /**
     * Save the ready rows. Each receipt (rows grouped by Date + Mode + Reference) is saved in its own DB
     * transaction, so one bad row never blocks the others.
     * @return array ['saved' => n, 'failed' => n, 'amount' => float, 'results' => [row => [status, message]]]
     */
    public function execute(array $rows, $admin_id, $file_name = '') {
        $db = $this->CI->db;
        $groups = [];
        foreach ($rows as $r) {
            if ($r['status'] !== 'ready') continue;
            $key = $r['reference'] !== '' ? $r['date'] . '|' . $r['mode'] . '|' . $r['reference'] . '|' . $r['bank_account_id'] : 'row' . $r['row'];
            $groups[$key][] = $r;
        }

        $imports = [];
        $res = ['saved' => 0, 'failed' => 0, 'amount' => 0.0, 'results' => [], 'import_codes' => []];
        $debug = $db->db_debug;
        $db->db_debug = false;
        foreach ($groups as $g) {
            $first = $g[0];
            $total = round(array_sum(array_map(function ($r) { return array_sum(array_column($r['allocations'], 'amount')); }, $g)), 2);
            $db->trans_begin();
            try {
                $acc_id = $first['bank_account_id'];
                if (!isset($imports[$acc_id])) {
                    $code = 'AIPB-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
                    $db->insert('bank_statement_imports', [
                        'import_code' => $code, 'bank_account_id' => $acc_id, 'file_name' => mb_substr($file_name ?: 'AI passbook import', 0, 255),
                        'file_path' => '', 'file_type' => 'excel', 'statement_date' => date('Y-m-d'), 'status' => 'completed',
                        'imported_by' => $admin_id, 'imported_at' => date('Y-m-d H:i:s'),
                    ]);
                    $imports[$acc_id] = (int) $db->insert_id();
                    $db->trans_commit();   // the import header is kept even if a receipt fails
                    $db->trans_begin();
                    $res['import_codes'][] = $code;
                }
                $desc = 'AI passbook: ' . implode(' + ', array_map(function ($r) {
                    return $r['member_label'] . ' ' . implode('/', array_column($r['allocations'], 'label')) . ($r['remarks'] !== '' ? ' (' . $r['remarks'] . ')' : '');
                }, $g));
                $txn = [
                    'import_id' => $imports[$acc_id], 'bank_account_id' => $acc_id, 'transaction_date' => $first['date'], 'value_date' => $first['date'],
                    'transaction_type' => 'credit', 'amount' => $total, 'credit_amount' => $total, 'debit_amount' => 0,
                    'description' => mb_substr($desc, 0, 500), 'reference_number' => $first['reference'] ?: null,
                    'utr_number' => ($first['reference'] !== '' && $first['mode'] !== 'cash') ? mb_substr($first['reference'], 0, 50) : null,
                    'cheque_number' => $first['mode'] === 'cheque' ? mb_substr($first['reference'], 0, 30) : null,
                    'mapping_status' => 'unmapped', 'detected_member_id' => $first['member_id'], 'paid_by_member_id' => $first['member_id'],
                    'remarks' => 'Imported from passbook via AI Assistant', 'created_at' => date('Y-m-d H:i:s'),
                ];
                if (!$db->insert('bank_transactions', $txn)) throw new Exception($db->error()['message'] ?? 'could not create the bank transaction');
                $txn_id = (int) $db->insert_id();
                $txn_row = $db->where('id', $txn_id)->get('bank_transactions')->row();

                $mappings = [];
                foreach ($g as $r) {
                    foreach ($r['allocations'] as $a) {
                        $mappings[] = ['paying_member_id' => $first['member_id'], 'paid_for_member_id' => $r['member_id'], 'transaction_type' => $a['type'],
                                       'related_account' => $a['related'], 'amount' => $a['amount'],
                                       'remarks' => mb_substr(($r['remarks'] !== '' ? $r['remarks'] : 'Passbook entry') . ' (AI import, row ' . $r['row'] . ')', 0, 255)];
                    }
                }
                $this->CI->Bank_model->apply_mappings($txn_row, $mappings, $admin_id, 0, $total, $total);

                // The mapping code does not check record_payment() — make sure every allocation really became a record
                $need = ['emi' => 0, 'savings' => 0];
                foreach ($mappings as $mp) if (isset($need[$mp['transaction_type']])) $need[$mp['transaction_type']]++;
                $got_lp = $db->where('bank_transaction_id', $txn_id)->where('is_reversed', 0)->count_all_results('loan_payments');
                $got_sv = $db->where('bank_transaction_id', $txn_id)->where('transaction_type', 'deposit')->count_all_results('savings_transactions');
                if ($got_lp < $need['emi']) throw new Exception('The loan payment could not be recorded (check the loan / EMI).');
                if ($got_sv < $need['savings']) throw new Exception('The savings deposit could not be recorded (check the savings account).');

                // The mapping code records "bank_transfer" — keep the passbook's real mode and reference
                $this->_fix_modes($txn_id, $first['mode'], $first['reference']);

                if ($db->trans_status() === false) {
                    $e = $db->error();
                    throw new Exception($e['message'] ?: 'database error');
                }
                $db->trans_commit();
                foreach ($g as $r) {
                    $res['results'][$r['row']] = ['status' => 'saved', 'message' => implode(' + ', array_map(function ($a) {
                        return $a['label'] . ' ₹' . number_format($a['amount'], 2); }, $r['allocations']))];
                    $res['saved']++;
                }
                $res['amount'] += $total;
            } catch (Exception $e) {
                $db->trans_rollback();
                foreach ($g as $r) {
                    $res['results'][$r['row']] = ['status' => 'failed', 'message' => $e->getMessage()];
                    $res['failed']++;
                }
            }
        }
        $db->db_debug = $debug;

        foreach ($imports as $acc_id => $import_id) {
            $n = $db->where('import_id', $import_id)->count_all_results('bank_transactions');
            $db->where('id', $import_id)->update('bank_statement_imports', [
                'total_transactions' => $n, 'mapped_count' => $n, 'unmapped_count' => 0, 'completed_at' => date('Y-m-d H:i:s'),
                'total_credits' => $db->select_sum('amount')->where('import_id', $import_id)->get('bank_transactions')->row()->amount ?? 0,
            ]);
            if ($n === 0) $db->where('id', $import_id)->delete('bank_statement_imports');
        }
        return $res;
    }

    private function _fix_modes($txn_id, $mode, $reference) {
        if ($mode === 'bank_transfer') return;
        $db = $this->CI->db;
        foreach ($db->where('bank_transaction_id', $txn_id)->get('transaction_mappings')->result() as $m) {
            if (!$m->related_id) continue;
            if ($m->mapping_type === 'loan_payment') {
                $db->where('id', $m->related_id)->update('loan_payments', ['payment_mode' => $mode, 'reference_number' => $reference ?: null]);
            } elseif ($m->mapping_type === 'savings') {
                $db->where('id', $m->related_id)->update('savings_transactions', ['payment_mode' => $mode, 'reference_number' => $reference ?: null]);
            } elseif ($m->mapping_type === 'fine') {
                $db->where('id', $m->related_id)->where('payment_mode', 'bank_transfer')->update('fines', ['payment_mode' => $mode === 'upi' ? 'online' : $mode]);
            }
        }
        $db->where('bank_transaction_id', $txn_id)->where('payment_mode', 'bank_transfer')
           ->update('member_other_transactions', ['payment_mode' => $mode]);
        $db->where('bank_transaction_id', $txn_id)->where('payment_mode', 'bank_transfer')
           ->update('savings_transactions', ['payment_mode' => $mode]);
    }

    // ───────────────────────────── Helpers ─────────────────────────────

    private function _date($v) {
        if ($v === null || $v === '') return null;
        if (is_numeric($v) && $v > 20000 && $v < 80000) {   // Excel serial date
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($v)->format('Y-m-d');
        }
        $v = trim(strtr((string) $v, ['०' => 0, '१' => 1, '२' => 2, '३' => 3, '४' => 4, '५' => 5, '६' => 6, '७' => 7, '८' => 8, '९' => 9]));
        foreach (['d/m/Y', 'd-m-Y', 'd.m.Y', 'Y-m-d', 'd/m/y', 'd-m-y', 'Y/m/d', 'd M Y', 'd-M-Y'] as $f) {
            $d = DateTime::createFromFormat('!' . $f, $v);
            if ($d && $d->format($f) === $v) return $d->format('Y-m-d');
        }
        return null;
    }

    private function _mode($m, $ref) {
        $map = ['online' => 'upi', 'gpay' => 'upi', 'phonepe' => 'upi', 'paytm' => 'upi', 'bank' => 'bank_transfer', 'neft' => 'bank_transfer',
                'rtgs' => 'bank_transfer', 'imps' => 'bank_transfer', 'transfer' => 'bank_transfer', 'bank transfer' => 'bank_transfer',
                'chq' => 'cheque', 'check' => 'cheque', 'रोख' => 'cash', 'चेक' => 'cheque'];
        $m = $map[$m] ?? $m;
        if (in_array($m, self::MODES, true)) return $m;
        if (preg_match('/^upi/i', $ref)) return 'upi';
        if (preg_match('/^(neft|rtgs|imps)/i', $ref)) return 'bank_transfer';
        return 'cash';
    }

    private function _type($t, $remarks) {
        $map = ['loan' => 'emi', 'installment' => 'emi', 'हप्ता' => 'emi', 'कर्ज' => 'emi', 'saving' => 'savings', 'deposit' => 'savings',
                'बचत' => 'savings', 'दंड' => 'fine', 'penalty fine' => 'fine', 'membership' => 'membership_fee', 'joining' => 'joining_fee',
                'admission' => 'admission_fee', 'share' => 'share_capital', 'shares' => 'share_capital', 'fee' => 'other_fee', 'other' => 'other_fee'];
        $t = str_replace([' ', '-'], '_', trim($t));
        $t = $map[str_replace('_', ' ', $t)] ?? ($map[$t] ?? $t);
        if ($t === '') {
            $x = mb_strtolower($remarks);
            if (preg_match('/\b(emi|installment)\b|हप्ता/u', $x)) return 'emi';
            if (preg_match('/\bsaving|बचत/u', $x)) return 'savings';
            if (preg_match('/\bfine\b|दंड/u', $x)) return 'fine';
            return 'auto';
        }
        return in_array($t, self::TYPES, true) ? $t : null;
    }

    /** Member by code, numeric id, mobile or every word of the name. */
    private function _members($text) {
        $db = $this->CI->db;
        $text = trim(preg_replace('/^@/', '', $text));
        if (preg_match('/^MEMB\d+$/i', $text)) return $db->where('member_code', strtoupper($text))->where('deleted_at', null)->get('members')->result();
        $digits = preg_replace('/\D/', '', $text);
        if (strlen($digits) >= 10 && strlen($digits) === strlen(preg_replace('/[\s+\-]/', '', $text))) {
            return $db->where('deleted_at', null)->group_start()->where('phone', substr($digits, -10))->or_where('alternate_phone', substr($digits, -10))->group_end()
                      ->limit(3)->get('members')->result();
        }
        if (preg_match('/^\d{1,6}$/', $text)) return $db->where('id', (int) $text)->where('deleted_at', null)->get('members')->result();
        $words = array_slice(array_filter(preg_split('/\s+/', $text)), 0, 4);
        if (!$words) return [];
        $db->where('deleted_at', null);
        foreach ($words as $w) {
            $db->group_start()->like('first_name', $w)->or_like('middle_name', $w)->or_like('last_name', $w)->group_end();
        }
        return $db->order_by("status = 'active'", 'DESC', false)->limit(3)->get('members')->result();
    }

    /** Same identifiers as Bank_model::auto_match (member code, phone, savings account, loan number). */
    private function _detect($text) {
        $db = $this->CI->db;
        if (preg_match('/MEMB\d{6}/i', $text, $m)) { $r = $db->where('member_code', strtoupper($m[0]))->get('members')->row(); if ($r) return $r; }
        if (preg_match('/\b(\d{10})\b/', $text, $m)) { $r = $db->where('phone', $m[1])->get('members')->row(); if ($r) return $r; }
        if (preg_match('/SAV\d{10}/i', $text, $m)) {
            $a = $db->where('account_number', strtoupper($m[0]))->get('savings_accounts')->row();
            if ($a) return $db->where('id', $a->member_id)->get('members')->row();
        }
        if (preg_match('/LN[A-Z0-9]{5,}/i', $text, $m)) {
            $l = $db->where('loan_number', strtoupper($m[0]))->get('loans')->row();
            if ($l) return $db->where('id', $l->member_id)->get('members')->row();
        }
        return null;
    }
}
