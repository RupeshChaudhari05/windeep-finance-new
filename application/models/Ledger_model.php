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
            $this->update_account_balance($debit_acct,  'debit',  $amount);
            $this->update_account_balance($credit_acct, 'credit', $amount);
            
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

        $this->load->model('Financial_year_model');
        $fy = $this->Financial_year_model->get_active();

        $voucher_number = $this->generate_voucher_number($voucher_type);
        $common = [
            'voucher_number'    => $voucher_number,
            'voucher_date'      => !empty($meta['voucher_date']) ? $meta['voucher_date'] : date('Y-m-d'),
            'voucher_type'      => $voucher_type,
            'narration'         => isset($meta['narration']) ? $meta['narration'] : null,
            'reference_type'    => isset($meta['reference_type']) ? $meta['reference_type'] : null,
            'reference_id'      => isset($meta['reference_id']) ? $meta['reference_id'] : null,
            'member_id'         => isset($meta['member_id']) ? $meta['member_id'] : null,
            'financial_year_id' => $fy ? $fy->id : null,
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
        if (!$payment) {
            return false;
        }

        $total     = round((float) $payment->total_amount, 2);
        $principal = round((float) (isset($payment->principal_component) ? $payment->principal_component : 0), 2);
        $interest  = round((float) (isset($payment->interest_component) ? $payment->interest_component : 0), 2);
        $fine      = round((float) (isset($payment->fine_component) ? $payment->fine_component : 0), 2);

        // Components must account for the whole receipt; anything unexplained is
        // treated as principal recovery so the entry still balances.
        $unallocated = round($total - ($principal + $interest + $fine), 2);
        if (abs($unallocated) >= 0.01) {
            $principal = round($principal + $unallocated, 2);
        }

        $cash = $this->cash_account_code(isset($payment->payment_mode) ? $payment->payment_mode : null);

        return $this->post_compound_entry('receipt', [
            ['code' => $cash,                    'debit'  => $total],
            ['code' => self::AC_MEMBER_LOANS,    'credit' => $principal],
            ['code' => self::AC_INTEREST_INCOME, 'credit' => $interest],
            ['code' => self::AC_FINE_INCOME,     'credit' => $fine],
        ], [
            'narration'      => $narration ? $narration : ('Loan repayment #' . $payment->id),
            'reference_type' => 'loan_payment',
            'reference_id'   => $payment->id,
            'member_id'      => $member_id,
            'voucher_date'   => !empty($payment->payment_date) ? $payment->payment_date : date('Y-m-d'),
            'created_by'     => $created_by,
        ]);
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

        $voucher_date = !empty($voucher_date) ? $voucher_date : date('Y-m-d');

        // Get financial year
        $this->load->model('Financial_year_model');
        $fy = $this->Financial_year_model->get_active();

        $data = [
            'voucher_type'      => $this->get_voucher_type($transaction_type),
            'voucher_date'      => $voucher_date,
            'financial_year_id' => $fy ? $fy->id : null,
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
        
        // Create member ledger entry if applicable
        if ($member_id && $entry_id) {
            $this->create_member_ledger_entry($member_id, $transaction_type, $transaction_id, $amount, $entry_id, $voucher_date);
        }
        
        return $entry_id;
    }
    
    /**
     * Record an expense transaction in the General Ledger.
     * Debits the Operating Expenses account (4200) and credits Cash & Bank (1102).
     */
    public function record_expense($expense_category, $amount, $description = '', $reference_id = null) {
        // Map specific expense categories to account codes if needed
        // Default: Debit Operating Expenses (4200), Credit Bank Accounts (1102)
        $debit_account = $this->get_account_by_code('4200');    // Operating Expenses
        $credit_account = $this->get_account_by_code('1102');   // Bank Accounts
        
        if (!$debit_account || !$credit_account) {
            log_message('error', 'record_expense: Could not find expense/bank accounts in chart_of_accounts');
            return false;
        }
        
        $this->load->model('Financial_year_model');
        $fy = $this->Financial_year_model->get_active();
        
        $data = [
            'voucher_type'      => 'payment',
            'voucher_date'      => date('Y-m-d'),
            'financial_year_id' => $fy ? $fy->id : null,
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
        return $this->db->where('account_code', $code)
                        ->get('chart_of_accounts')
                        ->row();
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
        $this->db->select('gl.*, coa.account_name, coa.account_code, m.member_code, m.first_name, m.last_name');
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
        
        $txn_sql = "SELECT gl.*, debit_amount as debit, credit_amount as credit
            FROM {$this->table} gl
            WHERE account_id = ? AND voucher_date >= ? AND voucher_date <= ?
            ORDER BY voucher_date ASC, id ASC";
        
        $transactions = $this->db->query($txn_sql, [$account_id, $from_date, $to_date])->result();
        
        return [
            'opening_balance' => $opening_balance,
            'transactions'    => $transactions
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
}
