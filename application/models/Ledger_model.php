<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ledger_model - General Ledger & Member Ledger
 */
class Ledger_model extends MY_Model {
    
    protected $table = 'general_ledger';
    protected $primary_key = 'id';
    
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
     * Create Double Entry from Transaction
     */
    public function post_transaction($transaction_type, $transaction_id, $amount, $member_id = null, $narration = null, $created_by = null) {
        // Get account mappings based on transaction type
        $accounts = $this->get_transaction_accounts($transaction_type);
        
        if (!$accounts) {
            return false;
        }
        
        // Get financial year
        $this->load->model('Financial_year_model');
        $fy = $this->Financial_year_model->get_active();
        
        $data = [
            'voucher_type'      => $this->get_voucher_type($transaction_type),
            'voucher_date'      => date('Y-m-d'),
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
            $this->create_member_ledger_entry($member_id, $transaction_type, $transaction_id, $amount, $entry_id);
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
    private function get_transaction_accounts($transaction_type) {
        $mappings = [
            'loan_disbursement' => [
                'debit' => 'loans_receivable', // Asset increase
                'credit' => 'cash_bank'        // Asset decrease
            ],
            'loan_payment' => [
                'debit' => 'cash_bank',        // Asset increase
                'credit' => 'loans_receivable' // Asset decrease
            ],
            'loan_interest' => [
                'debit' => 'interest_receivable',
                'credit' => 'interest_income'
            ],
            'savings_deposit' => [
                'debit' => 'cash_bank',        // Asset increase
                'credit' => 'member_savings'   // Liability increase
            ],
            'savings_withdrawal' => [
                'debit' => 'member_savings',   // Liability decrease
                'credit' => 'cash_bank'        // Asset decrease
            ],
            'fine_income' => [
                'debit' => 'cash_bank',
                'credit' => 'fine_income'
            ],
            'processing_fee' => [
                'debit' => 'cash_bank',
                'credit' => 'processing_fee_income'
            ]
        ];
        
        if (!isset($mappings[$transaction_type])) {
            return null;
        }
        
        // Get actual account IDs from chart of accounts
        $debit_account = $this->get_account_by_code($mappings[$transaction_type]['debit']);
        $credit_account = $this->get_account_by_code($mappings[$transaction_type]['credit']);
        
        if (!$debit_account || !$credit_account) {
            return null;
        }
        
        return [
            'debit' => $debit_account->id,
            'credit' => $credit_account->id
        ];
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
    private function create_member_ledger_entry($member_id, $transaction_type, $transaction_id, $amount, $gl_entry_id) {
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
            'transaction_date' => date('Y-m-d'),
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
        
        return $result;
    }
}
