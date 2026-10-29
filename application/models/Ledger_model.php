<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ledger_model - General Ledger & Member Ledger
 */
class Ledger_model extends MY_Model {
    
    protected $table = 'general_ledger';
    protected $primary_key = 'id';

    // ── Chart of account codes (leaf/postable accounts only) ──────────────
    const AC_CASH_IN_HAND       = '1101';
    const AC_BANK               = '1102';
    const AC_MEMBER_LOANS       = '1201';   // loans receivable
    const AC_INTEREST_RECV      = '1301';
    const AC_FINE_RECV          = '1302';
    const AC_SAVINGS_DEP        = '2101';   // liability to members
    const AC_INTEREST_INCOME    = '3100';
    const AC_PROCESSING_INCOME  = '3200';
    const AC_FINE_INCOME        = '3300';
    const AC_MEMBERSHIP_INCOME  = '3400';
    const AC_INTEREST_EXP       = '4100';
    const AC_OPERATING_EXP      = '4200';

    /** Amounts below this are rounding noise, not money. */
    const LEDGER_EPSILON = 0.005;
    
    /**
     * Generate Voucher Number
     */
    public function generate_voucher_number($voucher_type) {
        $prefix = strtoupper(substr($voucher_type, 0, 2));
        $year = date('Y');
        $month = date('m');
        
        $max = $this->db->select_max('id')
                        ->where('voucher_type', $voucher_type)
                        ->get($this->table)
                        ->row();
        
        $next = ($max->id ?? 0) + 1;
        
        return $prefix . $year . $month . str_pad($next, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Create Journal Entry
     * Inserts two rows (debit leg + credit leg) into general_ledger.
     */
    public function create_entry($data) {
        $this->db->trans_begin();
        
        try {
            if (empty($data['voucher_number'])) {
                $data['voucher_number'] = $this->generate_voucher_number($data['voucher_type']);
            }
            
            $amount        = $data['debit_amount'] ?? ($data['credit_amount'] ?? 0);
            $voucher_date  = $data['voucher_date'] ?? ($data['transaction_date'] ?? date('Y-m-d'));
            $debit_acct    = $data['debit_account_id']  ?? null;
            $credit_acct   = $data['credit_account_id'] ?? null;
            $common = [
                'voucher_number'    => $data['voucher_number'],
                'voucher_date'      => $voucher_date,
                'voucher_type'      => $data['voucher_type'],
                'narration'         => $data['narration'] ?? null,
                'reference_type'    => $data['reference_type'] ?? null,
                'reference_id'      => $data['reference_id'] ?? null,
                'member_id'         => $data['member_id'] ?? null,
                'financial_year_id' => $data['financial_year_id'] ?? null,
                'is_posted'         => 1,
                'created_by'        => $data['created_by'] ?? null,
            ];
            
            // Debit leg
            $debit_row = $common;
            $debit_row['account_id']    = $debit_acct;
            $debit_row['debit_amount']  = $amount;
            $debit_row['credit_amount'] = 0;
            $debit_row['balance_after'] = 0; // will be updated by update_account_balance
            $this->db->insert($this->table, $debit_row);
            $entry_id = $this->db->insert_id();
            
            // Credit leg
            $credit_row = $common;
            $credit_row['account_id']    = $credit_acct;
            $credit_row['debit_amount']  = 0;
            $credit_row['credit_amount'] = $amount;
            $credit_row['balance_after'] = 0;
            $this->db->insert($this->table, $credit_row);
            
            // Update chart of accounts balances
            if (!$this->bulk) {
                $this->update_account_balance($debit_acct,  'debit',  $amount);
                $this->update_account_balance($credit_acct, 'credit', $amount);
            }
            
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return false;
            }
            
            $this->db->trans_commit();
            return $entry_id;
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            throw $e;
        }
    }
    
    /**
     * Post a COMPOUND journal entry (one debit/credit per leg, N legs).
     *
     * Used where a single receipt settles several accounts at once — an EMI is
     * part principal, part interest, part fine, and each must land in its own
     * account. Refuses to post unless total debits equal total credits.
     *
     * @param string $voucher_type receipt|payment|journal|contra
     * @param array  $legs  [['code'=>'1101','debit'=>500,'credit'=>0], ...]
     * @param array  $meta  narration, reference_type, reference_id, member_id,
     *                      voucher_date, created_by
     * @return int|false first row id, or false if it could not be posted
     */
    public function post_compound_entry($voucher_type, array $legs, array $meta = []) {
        // Resolve account codes to ids and drop zero-value legs
        $resolved     = [];
        $total_debit  = 0.0;
        $total_credit = 0.0;

        foreach ($legs as $leg) {
            $debit  = round((float) (isset($leg['debit']) ? $leg['debit'] : 0), 2);
            $credit = round((float) (isset($leg['credit']) ? $leg['credit'] : 0), 2);

            if ($debit < self::LEDGER_EPSILON && $credit < self::LEDGER_EPSILON) {
                continue;   // nothing to post for this account
            }

            $account = $this->get_account_by_code($leg['code']);
            if (!$account) {
                log_message('error', 'Ledger: unknown account code ' . $leg['code'] . ' - compound entry NOT posted.');
                return false;
            }
            if (!empty($account->is_group)) {
                log_message('error', 'Ledger: account ' . $leg['code'] . ' is a group heading and cannot be posted to.');
                return false;
            }

            $resolved[]    = ['id' => $account->id, 'debit' => $debit, 'credit' => $credit];
            $total_debit  += $debit;
            $total_credit += $credit;
        }

        if (empty($resolved)) {
            return false;
        }

        if (abs($total_debit - $total_credit) >= 0.01) {
            log_message('error', sprintf(
                'Ledger: unbalanced entry rejected (debits %.2f != credits %.2f) ref %s#%s',
                $total_debit, $total_credit,
                isset($meta['reference_type']) ? $meta['reference_type'] : '?',
                isset($meta['reference_id']) ? $meta['reference_id'] : '?'
            ));
            return false;
        }

        $voucher_date = !empty($meta['voucher_date']) ? substr($meta['voucher_date'], 0, 10) : date('Y-m-d');
        $voucher_number = $this->generate_voucher_number($voucher_type);
        $common = [
            'voucher_number'    => $voucher_number,
            'voucher_date'      => $voucher_date,
            'voucher_type'      => $voucher_type,
            'narration'         => isset($meta['narration']) ? $meta['narration'] : null,
            'reference_type'    => isset($meta['reference_type']) ? $meta['reference_type'] : null,
            'reference_id'      => isset($meta['reference_id']) ? $meta['reference_id'] : null,
            'member_id'         => isset($meta['member_id']) ? $meta['member_id'] : null,
            'financial_year_id' => $this->fy_id_for($voucher_date),
            'is_posted'         => 1,
            'created_by'        => isset($meta['created_by']) ? $meta['created_by'] : null,
        ];

        $this->db->trans_begin();
        try {
            $first_id = null;
            foreach ($resolved as $leg) {
                $row = $common;
                $row['account_id']    = $leg['id'];
                $row['debit_amount']  = $leg['debit'];
                $row['credit_amount'] = $leg['credit'];
                $row['balance_after'] = 0;
                $this->db->insert($this->table, $row);
                if ($first_id === null) {
                    $first_id = $this->db->insert_id();
                }

                if ($this->bulk) continue;   // sync() recomputes all balances at the end
                if ($leg['debit'] >= self::LEDGER_EPSILON) {
                    $this->update_account_balance($leg['id'], 'debit', $leg['debit']);
                }
                if ($leg['credit'] >= self::LEDGER_EPSILON) {
                    $this->update_account_balance($leg['id'], 'credit', $leg['credit']);
                }
            }

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return false;
            }
            $this->db->trans_commit();
            if (!$this->bulk && !empty($meta['member_id'])) $this->rebuild_member_ledger($meta['member_id']);
            return $first_id;

        } catch (Exception $e) {
            $this->db->trans_rollback();
            throw $e;
        }
    }

    /**
     * Post a loan repayment, split across the accounts it actually settles.
     *
     * A single EMI recovers principal (an asset) and earns interest and fine
     * (income). Booking the whole receipt against the loan asset - as the old
     * single-line posting did - understates income and overstates recovery.
     *
     *   Dr  Cash / Bank            total received
     *       Cr  Member Loans       principal component
     *       Cr  Interest Income    interest component
     *       Cr  Fine Income        fine component
     *
     * @param int|object $payment loan_payments row (or its id)
     */
    public function post_loan_payment($payment, $member_id = null, $narration = null, $created_by = null) {
        if (!is_object($payment)) {
            $payment = $this->db->where('id', $payment)->get('loan_payments')->row();
        }
        if (!$payment || !empty($payment->is_reversed)) {
            return false;
        }
        $loan = $this->db->select('id, loan_number, member_id')->where('id', $payment->loan_id)->get('loans')->row();
        if (!$loan) return false;
        $member_id = $member_id ?: $loan->member_id;
        $date = !empty($payment->payment_date) ? $payment->payment_date : date('Y-m-d');

        list($legs, $write_off, $note) = $this->loan_payment_legs($payment, $loan);

        $id = true;
        if (!$this->is_posted('loan_payment', $payment->id)) {
            $id = $this->_post_source('receipt', $date, 'loan_payment', $payment->id, $member_id,
                $narration ? $narration : ('Loan repayment ' . $loan->loan_number . ($note ? ' (' . $note . ')' : '')), $legs, $created_by);
            if (!$id) return false;
        }
        // Principal waived on a force close leaves the loan book through a write-off, not through cash
        if ($write_off >= 0.01 && !$this->is_posted('loan_write_off', $payment->id)) {
            $this->_post_source('journal', $date, 'loan_write_off', $payment->id, $member_id,
                'Principal waived on force close: ' . $loan->loan_number,
                [[self::AC_LOAN_WRITE_OFF, $write_off, 0], [self::AC_MEMBER_LOANS, 0, $write_off]], $created_by);
        }
        return $id;
    }
    /**
     * Create Double Entry from Transaction
     *
     * @param string      $transaction_type
     * @param int         $transaction_id
     * @param float       $amount
     * @param int|null    $member_id
     * @param string|null $narration
     * @param int|null    $created_by
     * @param string|null $voucher_date Actual event date (Y-m-d). Pass the loan's
     *                    disbursement / payment date for back-dated entries so the
     *                    GL reflects when it happened, not when it was keyed in.
     *                    Defaults to today when omitted.
     * @param string|null $payment_mode Routes the cash leg to Cash in Hand (cash)
     *                    or Bank Accounts (everything else).
     */
    public function post_transaction($transaction_type, $transaction_id, $amount, $member_id = null, $narration = null, $created_by = null, $voucher_date = null, $payment_mode = null) {
        // Get account mappings based on transaction type
        $accounts = $this->get_transaction_accounts($transaction_type, $payment_mode);

        if (!$accounts) {
            return false;
        }

        $amount = round((float) $amount, 2);
        if ($amount < self::LEDGER_EPSILON) {
            return false;   // never post a zero-value voucher
        }

        $voucher_date = !empty($voucher_date) ? substr($voucher_date, 0, 10) : date('Y-m-d');

        $data = [
            'voucher_type'      => $this->get_voucher_type($transaction_type),
            'voucher_date'      => $voucher_date,
            'financial_year_id' => $this->fy_id_for($voucher_date),
            'debit_account_id'  => $accounts['debit'],
            'credit_account_id' => $accounts['credit'],
            'debit_amount'      => $amount,
            'credit_amount'     => $amount,
            'member_id'         => $member_id,
            'reference_type'    => $transaction_type,
            'reference_id'      => $transaction_id,
            'narration'         => $narration ?? $this->generate_narration($transaction_type, $transaction_id),
            'created_by'        => $created_by
        ];
        
        $entry_id = $this->create_entry($data);
        
        // Member ledger is derived from the general ledger
        if ($member_id && $entry_id && !$this->bulk) {
            $this->rebuild_member_ledger($member_id);
        }
        
        return $entry_id;
    }
    
    /**
     * Record an expense transaction in the General Ledger.
     * Debits the Operating Expenses account (4200) and credits Cash & Bank (1102).
     */
    public function record_expense($expense_category, $amount, $description = '', $reference_id = null, $date = null, $payment_mode = 'bank_transfer') {
        // Map specific expense categories to account codes if needed
        // Default: Debit Operating Expenses (4200), Credit Bank Accounts (1102)
        $debit_account = $this->get_account_by_code('4200');    // Operating Expenses
        $credit_account = $this->get_account_by_code($this->cash_account_code($payment_mode));   // Bank (or Cash in Hand)
        
        if (!$debit_account || !$credit_account) {
            log_message('error', 'record_expense: Could not find expense/bank accounts in chart_of_accounts');
            return false;
        }
        
        $date = $date ? substr($date, 0, 10) : date('Y-m-d');
        $data = [
            'voucher_type'      => 'payment',
            'voucher_date'      => $date,
            'financial_year_id' => $this->fy_id_for($date),
            'debit_account_id'  => $debit_account->id,
            'credit_account_id' => $credit_account->id,
            'debit_amount'      => $amount,
            'credit_amount'     => $amount,
            'member_id'         => null,
            'reference_type'    => 'expense_' . $expense_category,
            'reference_id'      => $reference_id,
            'narration'         => $description ?: ucwords(str_replace('_', ' ', $expense_category)) . ' expense',
            'created_by'        => null
        ];
        
        return $this->create_entry($data);
    }
    
    /**
     * Get Transaction Account Mapping
     */
    private function get_transaction_accounts($transaction_type, $payment_mode = null) {
        $cash = $this->cash_account_code($payment_mode);

        // Account CODES from chart_of_accounts. Only non-group (leaf) accounts
        // may be posted to — never a group heading such as 1100 or 1200.
        $mappings = [
            // Money out to the member, loan asset created
            'loan_disbursement'   => ['debit' => self::AC_MEMBER_LOANS,   'credit' => $cash],
            // Money in against a loan. NOTE: prefer post_loan_payment(), which
            // splits principal / interest / fine instead of dumping the whole
            // EMI against the loan asset.
            'loan_payment'        => ['debit' => $cash,                   'credit' => self::AC_MEMBER_LOANS],
            'loan_interest'       => ['debit' => self::AC_INTEREST_RECV,  'credit' => self::AC_INTEREST_INCOME],
            'loan_write_off'      => ['debit' => self::AC_OPERATING_EXP,  'credit' => self::AC_MEMBER_LOANS],

            // Member deposits are a liability of the society
            'savings_deposit'     => ['debit' => $cash,                   'credit' => self::AC_SAVINGS_DEP],
            'savings_withdrawal'  => ['debit' => self::AC_SAVINGS_DEP,    'credit' => $cash],
            'savings_interest'    => ['debit' => self::AC_INTEREST_EXP,   'credit' => self::AC_SAVINGS_DEP],

            // Income
            'fine_income'         => ['debit' => $cash,                   'credit' => self::AC_FINE_INCOME],
            'processing_fee'      => ['debit' => $cash,                   'credit' => self::AC_PROCESSING_INCOME],
            'membership_fee'      => ['debit' => $cash,                   'credit' => self::AC_MEMBERSHIP_INCOME],
            'interest_income'     => ['debit' => $cash,                   'credit' => self::AC_INTEREST_INCOME],

            // Expenses
            'expense'             => ['debit' => self::AC_OPERATING_EXP,  'credit' => $cash],
        ];

        if (!isset($mappings[$transaction_type])) {
            log_message('error', 'Ledger: no account mapping for transaction type "' . $transaction_type . '" — entry NOT posted.');
            return null;
        }

        $debit_account  = $this->get_account_by_code($mappings[$transaction_type]['debit']);
        $credit_account = $this->get_account_by_code($mappings[$transaction_type]['credit']);

        if (!$debit_account || !$credit_account) {
            log_message('error', sprintf(
                'Ledger: chart_of_accounts is missing %s (debit %s / credit %s) for "%s" — entry NOT posted.',
                (!$debit_account ? 'the debit account' : 'the credit account'),
                $mappings[$transaction_type]['debit'],
                $mappings[$transaction_type]['credit'],
                $transaction_type
            ));
            return null;
        }

        return [
            'debit'  => $debit_account->id,
            'credit' => $credit_account->id,
        ];
    }

    /**
     * Resolve which asset account the money moved through.
     * Anything that is not plain cash is treated as passing through the bank.
     */
    private function cash_account_code($payment_mode = null) {
        $mode = strtolower(trim((string) $payment_mode));
        if ($mode === '' || $mode === 'cash') {
            return self::AC_CASH_IN_HAND;
        }
        return self::AC_BANK;
    }

    /**
     * Get Account by Code
     */
    public function get_account_by_code($code) {
        if (!array_key_exists($code, $this->acct_cache)) {
            $this->acct_cache[$code] = $this->db->where('account_code', $code)->get('chart_of_accounts')->row();
        }
        return $this->acct_cache[$code];
    }
    
    /**
     * Get Voucher Type
     */
    private function get_voucher_type($transaction_type) {
        $types = [
            'loan_disbursement' => 'payment',
            'loan_payment' => 'receipt',
            'savings_deposit' => 'receipt',
            'savings_withdrawal' => 'payment',
            'fine_income' => 'receipt',
            'processing_fee' => 'receipt'
        ];
        
        return $types[$transaction_type] ?? 'journal';
    }
    
    /**
     * Generate Narration
     */
    private function generate_narration($transaction_type, $transaction_id) {
        $descriptions = [
            'loan_disbursement' => 'Loan disbursement',
            'loan_payment' => 'Loan payment received',
            'savings_deposit' => 'Savings deposit received',
            'savings_withdrawal' => 'Savings withdrawal',
            'fine_income' => 'Fine/penalty received',
            'processing_fee' => 'Processing fee received'
        ];
        
        return ($descriptions[$transaction_type] ?? 'Transaction') . ' #' . $transaction_id;
    }
    
    /**
     * Update Account Balance
     */
    private function update_account_balance($account_id, $type, $amount) {
        $account = $this->db->where('id', $account_id)
                            ->get('chart_of_accounts')
                            ->row();
        
        if (!$account) return false;
        
        $balance_change = 0;
        
        // Assets and Expenses increase with debit
        if (in_array($account->account_type, ['asset', 'expense'])) {
            $balance_change = ($type === 'debit') ? $amount : -$amount;
        }
        // Liabilities, Equity, and Income increase with credit
        else {
            $balance_change = ($type === 'credit') ? $amount : -$amount;
        }
        
        return $this->db->set('current_balance', 'current_balance + ' . $balance_change, FALSE)
                        ->where('id', $account_id)
                        ->update('chart_of_accounts');
    }
    
    /**
     * Create Member Ledger Entry
     * Bug #13 Fix: Use database locking to prevent race conditions
     */
    private function create_member_ledger_entry($member_id, $transaction_type, $transaction_id, $amount, $gl_entry_id, $transaction_date = null) {
        // Get current balance
        $last_entry = $this->db->where('member_id', $member_id)
                               ->order_by('id', 'DESC')
                               ->limit(1)
                               ->get('member_ledger')
                               ->row();
        
        $current_balance = $last_entry ? $last_entry->balance_after : 0;
        
        // Determine debit/credit based on transaction type
        $debit  = 0;
        $credit = 0;
        
        switch ($transaction_type) {
            case 'loan_disbursement':   $debit  = $amount; break;
            case 'loan_payment':        $credit = $amount; break;
            case 'savings_deposit':     $credit = $amount; break;
            case 'savings_withdrawal':  $debit  = $amount; break;
            case 'fine_income':         $credit = $amount; break;
        }
        
        $balance_after = $current_balance + $debit - $credit;
        
        return $this->db->insert('member_ledger', [
            'member_id'        => $member_id,
            'transaction_date' => !empty($transaction_date) ? $transaction_date : date('Y-m-d'),
            'transaction_type' => $transaction_type,
            'reference_type'   => $transaction_type,
            'reference_id'     => $transaction_id,
            'debit_amount'     => $debit,
            'credit_amount'    => $credit,
            'balance_after'    => $balance_after,
            'narration'        => $this->generate_narration($transaction_type, $transaction_id),
        ]);
    }
    
    /**
     * Get Member Ledger
     */
    public function get_member_ledger($member_id, $from_date = null, $to_date = null) {
        $this->db->where('member_id', $member_id);
        
        if ($from_date) {
            $this->db->where('transaction_date >=', $from_date);
        }
        
        if ($to_date) {
            $this->db->where('transaction_date <=', $to_date);
        }
        
        return $this->db->order_by('id', 'ASC')
                        ->get('member_ledger')
                        ->result();
    }
    
    /**
     * Get Member Balance
     */
    public function get_member_balance($member_id) {
        $last = $this->db->where('member_id', $member_id)
                         ->order_by('id', 'DESC')
                         ->limit(1)
                         ->get('member_ledger')
                         ->row();
        
        return $last ? $last->balance_after : 0;
    }
    
    /**
     * Get Chart of Accounts
     */
    public function get_chart_of_accounts($parent_id = null, $type = null) {
        if ($parent_id !== null) {
            $this->db->where('parent_id', $parent_id);
        }
        
        if ($type) {
            $this->db->where('account_type', $type);
        }
        
        return $this->db->where('is_active', 1)
                        ->order_by('account_code', 'ASC')
                        ->get('chart_of_accounts')
                        ->result();
    }
    
    /**
     * Get General Ledger Entries
     */
    public function get_ledger_entries($filters = []) {
        // Aliases are the names the General Ledger report view uses
        $this->db->select('gl.*, gl.debit_amount AS debit, gl.credit_amount AS credit, gl.voucher_date AS transaction_date, gl.id AS voucher_id,
                           coa.account_name, coa.account_code, m.member_code, m.first_name, m.last_name');
        $this->db->from('general_ledger gl');
        $this->db->join('chart_of_accounts coa', 'coa.id = gl.account_id', 'left');
        $this->db->join('members m', 'm.id = gl.member_id', 'left');
        
        if (!empty($filters['from_date'])) {
            $this->db->where('gl.voucher_date >=', $filters['from_date']);
        }
        
        if (!empty($filters['to_date'])) {
            $this->db->where('gl.voucher_date <=', $filters['to_date']);
        }
        
        if (!empty($filters['account_id'])) {
            $this->db->where('gl.account_id', $filters['account_id']);
        }
        
        if (!empty($filters['voucher_type'])) {
            $this->db->where('gl.voucher_type', $filters['voucher_type']);
        }
        
        return $this->db->order_by('gl.voucher_date', 'DESC')
                        ->order_by('gl.id', 'DESC')
                        ->get()
                        ->result();
    }
    
    /**
     * Get Account Statement
     */
    public function get_account_statement($account_id, $from_date, $to_date) {
        $account_id = (int) $account_id;
        
        $opening_sql = "SELECT 
            COALESCE(SUM(debit_amount), 0) as total_debit,
            COALESCE(SUM(credit_amount), 0) as total_credit
            FROM {$this->table}
            WHERE account_id = ? AND voucher_date < ?";
        
        $opening = $this->db->query($opening_sql, [$account_id, $from_date])->row();
        $opening_balance = ($opening->total_debit ?? 0) - ($opening->total_credit ?? 0);
        
        $txn_sql = "SELECT gl.*, debit_amount as debit, credit_amount as credit, voucher_date as transaction_date
            FROM {$this->table} gl
            WHERE account_id = ? AND voucher_date >= ? AND voucher_date <= ?
            ORDER BY voucher_date ASC, id ASC";
        
        $transactions = $this->db->query($txn_sql, [$account_id, $from_date, $to_date])->result();
        $total_debit  = round(array_sum(array_column($transactions, 'debit')), 2);
        $total_credit = round(array_sum(array_column($transactions, 'credit')), 2);

        // 'entries' and the totals are what the account statement page reads
        return [
            'opening_balance' => $opening_balance,
            'transactions'    => $transactions,
            'entries'         => $transactions,
            'total_debit'     => $total_debit,
            'total_credit'    => $total_credit,
            'closing_balance' => round($opening_balance + $total_debit - $total_credit, 2),
        ];
    }
    
    /**
     * Get Trial Balance
     */
    public function get_trial_balance($as_on_date = null) {
        if (!$as_on_date) {
            $as_on_date = date('Y-m-d');
        }
        
        $sql = "SELECT coa.*,
            (SELECT COALESCE(SUM(debit_amount), 0)  FROM general_ledger WHERE account_id = coa.id AND voucher_date <= ?) as total_debit,
            (SELECT COALESCE(SUM(credit_amount), 0) FROM general_ledger WHERE account_id = coa.id AND voucher_date <= ?) as total_credit
            FROM chart_of_accounts coa
            WHERE coa.is_active = 1
            ORDER BY coa.account_code ASC";
        
        return $this->db->query($sql, [$as_on_date, $as_on_date])->result();
    }
    
    /**
     * Get Profit & Loss
     */
    public function get_profit_loss($from_date, $to_date) {
        $result = [
            'income' => [],
            'expenses' => [],
            'total_income' => 0,
            'total_expenses' => 0,
            'net_profit' => 0
        ];
        
        $income_sql = "SELECT coa.account_name,
            (SELECT COALESCE(SUM(credit_amount), 0) FROM general_ledger WHERE account_id = coa.id AND voucher_date BETWEEN ? AND ?) -
            (SELECT COALESCE(SUM(debit_amount), 0)  FROM general_ledger WHERE account_id = coa.id AND voucher_date BETWEEN ? AND ?) as amount
            FROM chart_of_accounts coa
            WHERE coa.account_type = 'income' AND coa.is_active = 1";
        
        $result['income'] = $this->db->query($income_sql, [$from_date, $to_date, $from_date, $to_date])->result();
        
        foreach ($result['income'] as $income) {
            $result['total_income'] += $income->amount;
        }
        
        $expense_sql = "SELECT coa.account_name,
            (SELECT COALESCE(SUM(debit_amount), 0)  FROM general_ledger WHERE account_id = coa.id AND voucher_date BETWEEN ? AND ?) -
            (SELECT COALESCE(SUM(credit_amount), 0) FROM general_ledger WHERE account_id = coa.id AND voucher_date BETWEEN ? AND ?) as amount
            FROM chart_of_accounts coa
            WHERE coa.account_type = 'expense' AND coa.is_active = 1";
        
        $result['expenses'] = $this->db->query($expense_sql, [$from_date, $to_date, $from_date, $to_date])->result();
        
        foreach ($result['expenses'] as $expense) {
            $result['total_expenses'] += $expense->amount;
        }
        
        $result['net_profit'] = $result['total_income'] - $result['total_expenses'];
        
        return $result;
    }
    
    /**
     * Get Balance Sheet
     */
    public function get_balance_sheet($as_on_date = null) {
        if (!$as_on_date) {
            $as_on_date = date('Y-m-d');
        }
        
        $result = [
            'assets' => [],
            'liabilities' => [],
            'equity' => [],
            'total_assets' => 0,
            'total_liabilities' => 0,
            'total_equity' => 0
        ];
        
        // Assets (debit-normal)
        $asset_sql = "SELECT coa.account_name,
            (SELECT COALESCE(SUM(debit_amount), 0)  FROM general_ledger WHERE account_id = coa.id AND voucher_date <= ?) -
            (SELECT COALESCE(SUM(credit_amount), 0) FROM general_ledger WHERE account_id = coa.id AND voucher_date <= ?) as amount
            FROM chart_of_accounts coa
            WHERE coa.account_type = 'asset' AND coa.is_active = 1";
        
        $result['assets'] = $this->db->query($asset_sql, [$as_on_date, $as_on_date])->result();
        
        foreach ($result['assets'] as $asset) {
            $asset->balance = $asset->amount;   // the balance sheet view reads ->balance
            $result['total_assets'] += $asset->amount;
        }
        
        // Liabilities (credit-normal)
        $liability_sql = "SELECT coa.account_name,
            (SELECT COALESCE(SUM(credit_amount), 0) FROM general_ledger WHERE account_id = coa.id AND voucher_date <= ?) -
            (SELECT COALESCE(SUM(debit_amount), 0)  FROM general_ledger WHERE account_id = coa.id AND voucher_date <= ?) as amount
            FROM chart_of_accounts coa
            WHERE coa.account_type = 'liability' AND coa.is_active = 1";
        
        $result['liabilities'] = $this->db->query($liability_sql, [$as_on_date, $as_on_date])->result();
        
        foreach ($result['liabilities'] as $liability) {
            $liability->balance = $liability->amount;
            $result['total_liabilities'] += $liability->amount;
        }
        
        // Equity (credit-normal)
        $equity_sql = "SELECT coa.account_name,
            (SELECT COALESCE(SUM(credit_amount), 0) FROM general_ledger WHERE account_id = coa.id AND voucher_date <= ?) -
            (SELECT COALESCE(SUM(debit_amount), 0)  FROM general_ledger WHERE account_id = coa.id AND voucher_date <= ?) as amount
            FROM chart_of_accounts coa
            WHERE coa.account_type = 'equity' AND coa.is_active = 1";
        
        $result['equity'] = $this->db->query($equity_sql, [$as_on_date, $as_on_date])->result();
        
        foreach ($result['equity'] as $eq) {
            $eq->balance = $eq->amount;
            $result['total_equity'] += $eq->amount;
        }

        // Un-appropriated surplus. Income and expense accounts are not closed to
        // equity until year end, so without this the sheet cannot balance.
        $surplus_sql = "SELECT
            (SELECT COALESCE(SUM(gl.credit_amount - gl.debit_amount), 0)
               FROM general_ledger gl JOIN chart_of_accounts c ON c.id = gl.account_id
              WHERE c.account_type = 'income'  AND gl.voucher_date <= ?) -
            (SELECT COALESCE(SUM(gl.debit_amount - gl.credit_amount), 0)
               FROM general_ledger gl JOIN chart_of_accounts c ON c.id = gl.account_id
              WHERE c.account_type = 'expense' AND gl.voucher_date <= ?) AS surplus";
        $surplus_row = $this->db->query($surplus_sql, [$as_on_date, $as_on_date])->row();
        $surplus = (float) ($surplus_row->surplus ?? 0);

        if (abs($surplus) >= self::LEDGER_EPSILON) {
            $result['equity'][] = (object) [
                'account_name' => 'Surplus / (Deficit) for the period',
                'amount'       => $surplus,
                'balance'      => $surplus,
            ];
            $result['total_equity'] += $surplus;
        }
        $result['current_surplus'] = $surplus;

        // Accounting identity: Assets = Liabilities + Equity
        $result['difference'] = round($result['total_assets']
                              - ($result['total_liabilities'] + $result['total_equity']), 2);
        $result['balanced']   = (abs($result['difference']) < 0.01);

        return $result;
    }

    // ================================================================
    // POSTING SERVICE — one rule per source document, used by every screen
    // (Loans, Savings, Fines, Members, Bank Mapping, AI Assistant, passbook
    // import) and by sync(), so live posting and catch-up can never disagree.
    //
    //   source                     reference_type / reference_id
    //   loan disbursement part 1   loan_disbursement      / loans.id   (+ fee inside)
    //   later disbursement part    loan_disbursement_part / disbursement_tracking.id
    //   loan repayment             loan_payment           / loan_payments.id
    //   principal waived (closure) loan_write_off         / loan_payments.id
    //   savings transaction        savings_transaction    / savings_transactions.id
    //   fine received              fine_payment           / fines.id   (one per receipt)
    //   member fee                 member_transaction     / member_other_transactions.id
    //   reversal of any of these   rev_<type>             / same id
    //
    // Older entries posted under savings_deposit / savings_withdrawal /
    // fine_income / membership_fee are recognised, so nothing is posted twice.
    // ================================================================

    const AC_LOAN_WRITE_OFF = '4300';
    const AC_CAPITAL        = '5100';

    /** sync(): skip per-leg balance updates and member-ledger rows; both are rebuilt at the end. */
    private $bulk = false;
    private $posted_cache = null;
    private $acct_cache = [];
    private $fy_list = null;

    /** Financial year containing $date (null when no year covers it). */
    public function fy_id_for($date) {
        if ($this->fy_list === null) {
            $this->fy_list = $this->db->select('id, start_date, end_date')->get('financial_years')->result();
        }
        foreach ($this->fy_list as $f) {
            if ($date >= $f->start_date && $date <= $f->end_date) return (int) $f->id;
        }
        return null;
    }

    /** Has this source been posted under any of these reference types? */
    public function is_posted($ref_types, $ref_id) {
        $ref_types = (array) $ref_types;
        if ($this->posted_cache !== null) {
            foreach ($ref_types as $t) if (isset($this->posted_cache[$t . ':' . $ref_id])) return true;
            return false;
        }
        return $this->db->where_in('reference_type', $ref_types)->where('reference_id', $ref_id)->count_all_results($this->table) > 0;
    }

    /** Post a balanced voucher for a source document. Legs: [account_code, debit, credit]. */
    private function _post_source($voucher_type, $date, $ref_type, $ref_id, $member_id, $narration, array $legs, $created_by = null) {
        $compound = [];
        foreach ($legs as $l) $compound[] = ['code' => $l[0], 'debit' => $l[1], 'credit' => $l[2]];
        $id = $this->post_compound_entry($voucher_type, $compound, [
            'narration' => mb_substr((string) $narration, 0, 255), 'reference_type' => $ref_type, 'reference_id' => $ref_id,
            'member_id' => $member_id ?: null, 'voucher_date' => $date, 'created_by' => $created_by,
        ]);
        if ($id && $this->posted_cache !== null) $this->posted_cache[$ref_type . ':' . $ref_id] = true;
        return $id;
    }

    // ── disbursement ──

    /** Post one disbursement part (part 1 carries the processing fee withheld at source). */
    public function post_disbursement_part($part, $created_by = null) {
        if (!is_object($part)) $part = $this->db->where('id', $part)->get('disbursement_tracking')->row();
        if (!$part || $part->status !== 'completed') return false;
        $loan = $this->db->select('id, loan_number, member_id, disbursement_mode')->where('id', $part->loan_id)->get('loans')->row();
        if (!$loan) return false;
        $gross = round((float) $part->disbursement_amount, 2);
        $fee   = round((float) $part->processing_fee, 2);
        $cash  = $this->cash_account_code($part->disbursement_mode ?: $loan->disbursement_mode);

        if ((int) $part->tranche_no === 1) {
            if ($this->is_posted('loan_disbursement', $loan->id)) return true;
            return $this->_post_source('payment', $part->disbursement_date, 'loan_disbursement', $loan->id, $loan->member_id,
                'Loan disbursement' . ($fee > 0 ? ' (fee withheld)' : '') . ': ' . $loan->loan_number, [
                    [self::AC_MEMBER_LOANS, $gross, 0], [$cash, 0, round($gross - $fee, 2)], [self::AC_PROCESSING_INCOME, 0, $fee],
                ], $created_by);
        }
        if ($this->is_posted('loan_disbursement_part', $part->id)) return true;
        return $this->_post_source('payment', $part->disbursement_date, 'loan_disbursement_part', $part->id, $loan->member_id,
            'Loan disbursement part ' . $part->tranche_no . ': ' . $loan->loan_number, [
                [self::AC_MEMBER_LOANS, $gross, 0], [$cash, 0, $gross],
            ], $created_by);
    }

    // ── loan repayment ──

    /**
     * How a repayment settles the books. The stored components are what the loan module applied; the
     * difference to the cash received was moved to / from the member's savings by the loan rule
     * (an 'adjustment' savings row names the loan), or — on a force close — waived.
     * @return array [legs, write_off, note]
     */
    public function loan_payment_legs($p, $loan) {
        $cash_amt = round((float) $p->total_amount, 2);
        $P = max(0, round((float) $p->principal_component, 2));
        $I = max(0, round((float) $p->interest_component, 2));
        $F = max(0, round((float) $p->fine_component, 2));
        $cash = $this->cash_account_code($p->payment_mode);

        // Force close: only next month's interest is collected, the principal is waived
        if ($p->payment_type === 'foreclosure' && stripos((string) $p->narration, 'Force Close') === 0) {
            return [[[$cash, $cash_amt, 0], [self::AC_INTEREST_INCOME, 0, $cash_amt]], $P, 'force close'];
        }

        $d = round($cash_amt - ($P + $I + $F), 2);
        if (abs($d) >= 0.5) {
            $moved = $this->_savings_transfer($p, $loan, $d > 0 ? 'deposit' : 'withdrawal');
            if (abs($moved - abs($d)) <= 1) {
                $legs = [[$cash, $cash_amt, 0], [self::AC_MEMBER_LOANS, 0, $P], [self::AC_INTEREST_INCOME, 0, $I], [self::AC_FINE_INCOME, 0, $F]];
                if ($d > 0) $legs[] = [self::AC_SAVINGS_DEP, 0, $d];                       // excess credited to savings
                else        $legs[] = [self::AC_SAVINGS_DEP, abs($d), 0];                  // shortfall taken from savings
                return [$legs, 0, $d > 0 ? 'excess to savings' : 'shortfall from savings'];
            }
        }
        // Components disagree with the cash and nothing explains it: fine → interest → principal, capped at the cash
        $F = min($F, $cash_amt);
        $I = min($I, round($cash_amt - $F, 2));
        $P = round($cash_amt - $F - $I, 2);
        return [[[$cash, $cash_amt, 0], [self::AC_MEMBER_LOANS, 0, $P], [self::AC_INTEREST_INCOME, 0, $I], [self::AC_FINE_INCOME, 0, $F]], 0,
                abs($d) >= 0.5 ? 'components differ from cash' : ''];
    }

    /** Savings moved by the loan rule for this payment (adjustment rows naming the loan, same date). */
    private function _savings_transfer($p, $loan, $type) {
        $r = $this->db->select('COALESCE(SUM(st.amount), 0) s', false)->from('savings_transactions st')
            ->join('savings_accounts sa', 'sa.id = st.savings_account_id')
            ->where('sa.member_id', $loan->member_id)->where('st.transaction_date', $p->payment_date)
            ->where('st.payment_mode', 'adjustment')->where('st.transaction_type', $type)->where('st.is_reversed', 0)
            ->like('st.narration', 'Loan: ' . $loan->loan_number)->get()->row();
        return round((float) $r->s, 2);
    }

    /** True for the savings rows the loan rule creates (posted inside the repayment voucher). */
    public function is_loan_rule_transfer($s) {
        return $s->payment_mode === 'adjustment'
            && preg_match('/^(Auto-deducted for loan EMI shortfall|Overpayment from loan EMI|Negative balance: Loan EMI shortfall)/i', (string) $s->narration);
    }

    // ── savings ──

    public function post_savings_txn($s, $created_by = null) {
        if (!is_object($s)) {
            $s = $this->db->select('st.*, sa.member_id, sa.account_number')->from('savings_transactions st')
                          ->join('savings_accounts sa', 'sa.id = st.savings_account_id')->where('st.id', $s)->get()->row();
        }
        if (!$s || !empty($s->is_reversed) || $this->is_loan_rule_transfer($s)) return false;
        if ($this->is_posted(['savings_transaction', 'savings_deposit', 'savings_withdrawal'], $s->id)) return true;
        $amt = round((float) $s->amount, 2);
        if ($amt < 0.01) return false;
        $cash = $this->cash_account_code($s->payment_mode === 'adjustment' ? 'bank_transfer' : $s->payment_mode);
        switch ($s->transaction_type) {
            case 'deposit':
            case 'opening_balance': $legs = [[$cash, $amt, 0], [self::AC_SAVINGS_DEP, 0, $amt]]; $vt = 'receipt'; break;
            case 'withdrawal':      $legs = [[self::AC_SAVINGS_DEP, $amt, 0], [$cash, 0, $amt]]; $vt = 'payment'; break;
            case 'interest_credit': $legs = [[self::AC_INTEREST_EXP, $amt, 0], [self::AC_SAVINGS_DEP, 0, $amt]]; $vt = 'journal'; break;
            default: return false;   // fine / fine_waiver / adjustment: not money in or out
        }
        return $this->_post_source($vt, $s->transaction_date, 'savings_transaction', $s->id, $s->member_id ?? null,
            'Savings ' . str_replace('_', ' ', $s->transaction_type) . (isset($s->account_number) ? ' ' . $s->account_number : ''), $legs, $created_by);
    }

    // ── fines ──

    /** One fine receipt (a fine can be paid in several parts, each is its own voucher). */
    public function post_fine_receipt($fine_id, $amount, $date, $mode, $created_by = null) {
        $fine = $this->db->select('id, fine_code, member_id')->where('id', $fine_id)->get('fines')->row();
        $amount = round((float) $amount, 2);
        if (!$fine || $amount < 0.01) return false;
        return $this->_post_source('receipt', $date ?: date('Y-m-d'), 'fine_payment', $fine->id, $fine->member_id,
            'Fine received: ' . ($fine->fine_code ?: '#' . $fine->id), [[$this->cash_account_code($mode), $amount, 0], [self::AC_FINE_INCOME, 0, $amount]], $created_by);
    }

    /** Fine income already in the ledger for a fine (new + older 'fine_income' postings of the Fines screen). */
    private function _fine_posted_total($fine_id) {
        $r = $this->db->query("SELECT COALESCE(SUM(gl.credit_amount - gl.debit_amount), 0) s FROM general_ledger gl
              JOIN chart_of_accounts c ON c.id = gl.account_id AND c.account_code = ?
             WHERE gl.reference_id = ? AND (gl.reference_type IN ('fine_payment', 'rev_fine_payment')
                   OR (gl.reference_type = 'fine_income' AND gl.narration LIKE 'Fine payment:%'))", [self::AC_FINE_INCOME, $fine_id])->row();
        return round((float) $r->s, 2);
    }

    // ── member fees ──

    public function post_member_txn($t, $created_by = null) {
        if (!is_object($t)) $t = $this->db->where('id', $t)->get('member_other_transactions')->row();
        if (!$t || $t->status !== 'completed') return false;
        $map = ['membership_fee' => self::AC_MEMBERSHIP_INCOME, 'admission_fee' => self::AC_MEMBERSHIP_INCOME, 'joining_fee' => self::AC_MEMBERSHIP_INCOME,
                'other' => self::AC_MEMBERSHIP_INCOME, 'late_fee' => self::AC_FINE_INCOME, 'penalty' => self::AC_FINE_INCOME, 'share_capital' => self::AC_CAPITAL];
        if (!isset($map[$t->transaction_type])) return false;   // processing_fee is inside the disbursement voucher; bonus via savings
        if ($this->is_posted('member_transaction', $t->id)) return true;
        $legacy = $this->db->where('reference_id', $t->id)
            ->group_start()->where('reference_type', 'membership_fee')
                ->or_group_start()->where_in('reference_type', ['processing_fee', 'fine_income'])->like('narration', ' from ', 'both')->group_end()
            ->group_end()->count_all_results($this->table);
        if ($legacy) return true;
        $amt = round((float) $t->amount, 2);
        if ($amt < 0.01) return false;
        return $this->_post_source('receipt', $t->transaction_date, 'member_transaction', $t->id, $t->member_id,
            ucwords(str_replace('_', ' ', $t->transaction_type)) . ($t->description ? ' — ' . $t->description : ''),
            [[$this->cash_account_code($t->payment_mode), $amt, 0], [$map[$t->transaction_type], 0, $amt]], $created_by);
    }

    // ── reversal ──

    /** Contra of one fine receipt (bank mapping undone). */
    public function reverse_fine_receipt($fine_id, $amount, $date = null, $created_by = null) {
        $amount = round((float) $amount, 2);
        if ($amount < 0.01 || $this->_fine_posted_total($fine_id) < $amount - 0.01) return false;
        $fine = $this->db->select('member_id, fine_code')->where('id', $fine_id)->get('fines')->row();
        return $this->_post_source('journal', $date ?: date('Y-m-d'), 'rev_fine_payment', $fine_id, $fine ? $fine->member_id : null,
            'Reversal of fine receipt ' . ($fine ? $fine->fine_code : '#' . $fine_id), [[self::AC_FINE_INCOME, $amount, 0], [self::AC_BANK, 0, $amount]], $created_by);
    }

    /** Mirror every leg posted for a source (contra voucher). Safe to call twice. */
    public function reverse_source($ref_type, $ref_id, $reason = '', $date = null, $created_by = null) {
        if ($this->is_posted('rev_' . $ref_type, $ref_id)) return true;
        $legs = $this->db->select('c.account_code, gl.debit_amount, gl.credit_amount, gl.member_id')->from('general_ledger gl')
            ->join('chart_of_accounts c', 'c.id = gl.account_id')->where('gl.reference_type', $ref_type)->where('gl.reference_id', $ref_id)->get()->result();
        if (!$legs) return false;
        $mirror = [];
        foreach ($legs as $l) $mirror[] = [$l->account_code, (float) $l->credit_amount, (float) $l->debit_amount];
        return $this->_post_source('journal', $date ?: date('Y-m-d'), 'rev_' . $ref_type, $ref_id, $legs[0]->member_id,
            'Reversal of ' . str_replace('_', ' ', $ref_type) . ' #' . $ref_id . ($reason ? ' — ' . $reason : ''), $mirror, $created_by);
    }

    // ================================================================
    // SYNC — post every source document that is not in the ledger yet
    // ================================================================

    /**
     * @param bool $commit false = dry run (everything is rolled back)
     * @return array ['posted' => [type => n], 'reversed' => n, 'exceptions' => [], 'trial' => [dr, cr]]
     */
    public function sync($commit = true) {
        @set_time_limit(0);
        $out = ['posted' => [], 'reversed' => 0, 'skipped' => [], 'exceptions' => []];
        $count = function ($k) use (&$out) { $out['posted'][$k] = ($out['posted'][$k] ?? 0) + 1; };
        $today = date('Y-m-d');
        $bad_date = function ($d) use ($today) { return !$d || $d < '2000-01-01' || substr($d, 0, 10) > $today; };

        $this->bulk = true;
        $this->posted_cache = [];
        foreach ($this->db->select('reference_type, reference_id')->distinct()->where('reference_id IS NOT NULL', null, false)->get($this->table)->result() as $r) {
            $this->posted_cache[$r->reference_type . ':' . $r->reference_id] = true;
        }
        $debug = $this->db->db_debug;
        $this->db->db_debug = false;
        $this->db->trans_begin();
        try {
            // 1. Disbursements (every loan has its parts since migration 035)
            foreach ($this->db->where_in('status', ['completed', 'reversed'])->order_by('disbursement_date')->get('disbursement_tracking')->result() as $p) {
                if ($p->status === 'reversed') {
                    if ($p->tranche_no > 1 && $this->is_posted('loan_disbursement_part', $p->id) && $this->reverse_source('loan_disbursement_part', $p->id, 'part reversed')) $out['reversed']++;
                    continue;
                }
                if ($bad_date($p->disbursement_date)) { $out['exceptions'][] = "Disbursement part #{$p->id} has an invalid date {$p->disbursement_date}"; continue; }
                $was = $this->is_posted($p->tranche_no > 1 ? 'loan_disbursement_part' : 'loan_disbursement', $p->tranche_no > 1 ? $p->id : $p->loan_id);
                if (!$was && $this->post_disbursement_part($p)) $count('disbursement');
            }

            // 2. Repayments (+ principal waived on force close)
            $loans = [];
            foreach ($this->db->select('id, loan_number, member_id')->get('loans')->result() as $l) $loans[$l->id] = $l;
            foreach ($this->db->order_by('payment_date')->get('loan_payments')->result() as $p) {
                $loan = $loans[$p->loan_id] ?? null;
                if (!$loan) continue;
                if (!empty($p->is_reversed)) {
                    if ($this->is_posted('loan_payment', $p->id) && $this->reverse_source('loan_payment', $p->id, 'payment reversed', $p->reversed_at ? substr($p->reversed_at, 0, 10) : null)) $out['reversed']++;
                    if ($this->is_posted('loan_write_off', $p->id)) $this->reverse_source('loan_write_off', $p->id, 'payment reversed');
                    continue;
                }
                if ($bad_date($p->payment_date)) { $out['exceptions'][] = "Payment #{$p->id} ({$loan->loan_number}) has an invalid date {$p->payment_date} — fix the date, then sync again"; continue; }
                $had_pay = $this->is_posted('loan_payment', $p->id);
                $had_wo = $this->is_posted('loan_write_off', $p->id);
                list(, , $note) = $this->loan_payment_legs($p, $loan);
                $this->post_loan_payment($p, $loan->member_id);
                if (!$had_pay && $this->is_posted('loan_payment', $p->id)) {
                    $count('loan_payment');
                    if ($note === 'components differ from cash') {
                        $says = preg_match('/credited to savings|covered from savings/i', (string) $p->narration);
                        $out['exceptions'][] = "Payment #{$p->id} ({$loan->loan_number}, {$p->payment_date}): cash " . number_format($p->total_amount, 2)
                            . ' vs EMI components ' . number_format($p->principal_component + $p->interest_component + $p->fine_component, 2)
                            . ($says ? ' — narration says savings were adjusted, but no savings entry exists' : '') . '; booked fine → interest → principal';
                    }
                }
                if (!$had_wo && $this->is_posted('loan_write_off', $p->id)) $count('loan_write_off');
            }

            // 3. Savings
            foreach ($this->db->select('st.*, sa.member_id, sa.account_number')->from('savings_transactions st')
                         ->join('savings_accounts sa', 'sa.id = st.savings_account_id')->order_by('st.transaction_date')->get()->result() as $s) {
                if (!empty($s->is_reversed)) {
                    foreach (['savings_transaction', 'savings_deposit'] as $t) {
                        if ($this->is_posted($t, $s->id) && !($t === 'savings_deposit' && $this->is_posted('savings_withdrawal', $s->id))) {
                            if ($this->reverse_source($t, $s->id, 'savings entry reversed')) $out['reversed']++;
                        }
                    }
                    continue;
                }
                if ($this->is_loan_rule_transfer($s) || $this->is_posted(['savings_transaction', 'savings_deposit', 'savings_withdrawal'], $s->id)) continue;
                if ($bad_date($s->transaction_date)) { $out['exceptions'][] = "Savings entry #{$s->id} has an invalid date {$s->transaction_date}"; continue; }
                if ($this->post_savings_txn($s)) $count('savings_' . $s->transaction_type);
                if (!$this->fy_id_for($s->transaction_date)) $out['skipped']['savings before the first financial year (posted, no year)'] = ($out['skipped']['savings before the first financial year (posted, no year)'] ?? 0) + 1;
            }

            // 4. Fines — post whatever was received but is not in the ledger yet
            foreach ($this->db->where('paid_amount >', 0)->get('fines')->result() as $f) {
                $missing = round((float) $f->paid_amount - $this->_fine_posted_total($f->id), 2);
                if ($missing < 0.5) continue;
                $date = $f->payment_date ?: substr((string) $f->updated_at, 0, 10);
                if ($bad_date($date)) { $out['exceptions'][] = "Fine #{$f->id} paid {$f->paid_amount} has no valid payment date"; continue; }
                if ($this->post_fine_receipt($f->id, $missing, $date, $f->payment_mode)) $count('fine_payment');
            }

            // 5. Member fees
            foreach ($this->db->order_by('transaction_date')->get('member_other_transactions')->result() as $t) {
                if ($t->status === 'reversed') {
                    if ($this->is_posted('member_transaction', $t->id) && $this->reverse_source('member_transaction', $t->id, 'fee reversed')) $out['reversed']++;
                    continue;
                }
                if ($t->status !== 'completed') continue;
                if ($t->transaction_type === 'processing_fee') { $out['skipped']['processing-fee rows (inside the disbursement voucher)'] = ($out['skipped']['processing-fee rows (inside the disbursement voucher)'] ?? 0) + 1; continue; }
                if ($bad_date($t->transaction_date)) { $out['exceptions'][] = "Member fee #{$t->id} has an invalid date"; continue; }
                $had = $this->is_posted('member_transaction', $t->id);
                if ($this->post_member_txn($t) && !$had && $this->is_posted('member_transaction', $t->id)) $count('member_fee');
                elseif (!isset(['membership_fee' => 1, 'admission_fee' => 1, 'joining_fee' => 1, 'other' => 1, 'late_fee' => 1, 'penalty' => 1, 'share_capital' => 1][$t->transaction_type])) {
                    $out['skipped'][$t->transaction_type . ' rows (no ledger rule)'] = ($out['skipped'][$t->transaction_type . ' rows (no ledger rule)'] ?? 0) + 1;
                }
            }

            $this->recompute_balances();
            $this->rebuild_member_ledger();
            $tb = $this->db->query('SELECT COALESCE(SUM(debit_amount), 0) dr, COALESCE(SUM(credit_amount), 0) cr FROM general_ledger')->row();
            $out['trial'] = [round((float) $tb->dr, 2), round((float) $tb->cr, 2)];

            if ($commit && $this->db->trans_status() !== false) $this->db->trans_commit();
            else {
                if ($commit) $out['exceptions'][] = 'Database error — nothing was saved: ' . ($this->db->error()['message'] ?? '');
                $this->db->trans_rollback();
            }
        } catch (Exception $e) {
            $this->db->trans_rollback();
            $out['exceptions'][] = 'Stopped: ' . $e->getMessage() . ' — nothing was saved';
        } finally {
            $this->db->db_debug = $debug;
            $this->bulk = false;
            $this->posted_cache = null;
        }
        $out['committed'] = $commit;
        return $out;
    }

    /** Chart of accounts balances from the ledger (normal-balance aware). */
    public function recompute_balances() {
        $this->db->query("UPDATE chart_of_accounts coa
            LEFT JOIN (SELECT account_id, SUM(debit_amount) dr, SUM(credit_amount) cr FROM general_ledger GROUP BY account_id) g ON g.account_id = coa.id
            SET coa.current_balance = CASE WHEN coa.account_type IN ('asset', 'expense') THEN COALESCE(g.dr, 0) - COALESCE(g.cr, 0)
                                           ELSE COALESCE(g.cr, 0) - COALESCE(g.dr, 0) END");
    }

    /**
     * Member ledger = the member's money in and out of the society, from the general ledger:
     * cash / bank legs of every voucher tagged with the member (credit = paid in, debit = paid out).
     * Running balance = total paid in − total paid out (positive = Cr, as the ledger screens show it).
     */
    public function rebuild_member_ledger($member_id = null) {
        $where = $member_id ? 'AND gl.member_id = ' . (int) $member_id : '';
        if ($member_id) $this->db->where('member_id', (int) $member_id)->delete('member_ledger');
        else $this->db->query('DELETE FROM member_ledger');
        $rows = $this->db->query("SELECT gl.member_id, gl.voucher_date, gl.voucher_number, gl.reference_type, gl.reference_id, MIN(gl.narration) narration,
                   SUM(gl.debit_amount) dr, SUM(gl.credit_amount) cr
              FROM general_ledger gl JOIN chart_of_accounts c ON c.id = gl.account_id AND c.account_code IN (?, ?)
             WHERE gl.member_id IS NOT NULL {$where}
             GROUP BY gl.member_id, gl.voucher_number, gl.voucher_date, gl.reference_type, gl.reference_id
             ORDER BY gl.member_id, gl.voucher_date, MIN(gl.id)", [self::AC_CASH_IN_HAND, self::AC_BANK])->result();
        $bal = [];
        $batch = [];
        foreach ($rows as $r) {
            $mid = (int) $r->member_id;
            $in = round((float) $r->dr, 2);    // cash debited = money received from the member
            $out = round((float) $r->cr, 2);   // cash credited = money paid to the member
            if ($in < 0.005 && $out < 0.005) continue;
            $bal[$mid] = round(($bal[$mid] ?? 0) + $in - $out, 2);   // + = member has paid in more (Cr), − = received more (Dr)
            $batch[] = ['member_id' => $mid, 'transaction_date' => $r->voucher_date, 'transaction_type' => $r->reference_type,
                        'reference_type' => $r->reference_type, 'reference_id' => $r->reference_id,
                        'debit_amount' => $out, 'credit_amount' => $in, 'balance_after' => $bal[$mid], 'narration' => $r->narration];
            if (count($batch) >= 500) { $this->db->insert_batch('member_ledger', $batch); $batch = []; }
        }
        if ($batch) $this->db->insert_batch('member_ledger', $batch);
        return count($rows);
    }
}
