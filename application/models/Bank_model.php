<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bank_model - Bank Statement Import & Transaction Mapping
 */
class Bank_model extends MY_Model {
    
    protected $table = 'bank_accounts';
    protected $primary_key = 'id';
    
    /**
     * Get All Bank Accounts
     */
    public function get_accounts($active_only = true) {
        if ($active_only) {
            $this->db->where('is_active', 1);
        }
        
        return $this->db->order_by('bank_name', 'ASC')
                        ->get($this->table)
                        ->result();
    }
    
    /**
     * Create Bank Account
     */
    public function create_account($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }
    
    /**
     * Import Bank Statement
     */
    public function import_statement($file_path, $bank_account_id, $imported_by) {
        $this->db->trans_begin();
        
        try {
            // Create import record
            $import_data = [
                'bank_account_id' => $bank_account_id,
                'file_name' => basename($file_path),
                'file_path' => $file_path,
                'status' => 'processing',
                'imported_by' => $imported_by
            ];
            
            $this->db->insert('bank_statement_imports', $import_data);
            $import_id = $this->db->insert_id();
            
            // Parse the file
            $transactions = $this->parse_statement($file_path, $bank_account_id);
            
            $total = count($transactions);
            $matched = 0;
            $unmatched = 0;
            $duplicates = 0;
            $total_amount = 0;
            
            foreach ($transactions as $txn) {
                // Check for duplicate
                $exists = $this->db->where('bank_account_id', $bank_account_id)
                                   ->where('transaction_date', $txn['transaction_date'])
                                   ->where('amount', $txn['amount'])
                                   ->where('reference_number', $txn['reference_number'] ?? '')
                                   ->count_all_results('bank_transactions');
                
                if ($exists > 0) {
                    $duplicates++;
                    continue;
                }
                
                $txn['import_id'] = $import_id;
                $txn['bank_account_id'] = $bank_account_id;
                $txn['mapping_status'] = 'unmapped';
                
                // Try auto-match
                $match = $this->auto_match($txn);
                if ($match) {
                    $txn['mapping_status'] = 'mapped';
                    $txn['detected_member_id'] = $match['member_id'] ?? null;
                    $matched++;
                } else {
                    $unmatched++;
                }
                
                $total_amount += $txn['amount'];
                
                $this->db->insert('bank_transactions', $txn);
            }
            
            // Update import record
            $this->db->where('id', $import_id)
                     ->update('bank_statement_imports', [
                         'status' => 'completed',
                         'total_transactions' => $total,
                         'matched_transactions' => $matched,
                         'unmatched_transactions' => $unmatched,
                         'duplicate_transactions' => $duplicates,
                         'total_amount' => $total_amount,
                         'processed_at' => date('Y-m-d H:i:s')
                     ]);
            
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return false;
            }
            
            $this->db->trans_commit();
            
            return [
                'import_id' => $import_id,
                'total' => $total,
                'matched' => $matched,
                'unmatched' => $unmatched,
                'duplicates' => $duplicates,
                'total_amount' => $total_amount
            ];
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            throw $e;
        }
    }
    
    /**
     * Parse Statement File
     */
    private function parse_statement($file_path, $bank_account_id) {
        $transactions = [];
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        if ($extension === 'csv') {
            $transactions = $this->parse_csv($file_path, $bank_account_id);
        } elseif (in_array($extension, ['xls', 'xlsx'])) {
            $transactions = $this->parse_excel($file_path, $bank_account_id);
        }
        
        return $transactions;
    }
    
    /**
     * Parse CSV File
     */
    private function parse_csv($file_path, $bank_account_id) {
        $transactions = [];
        
        // Get bank account for parsing rules
        $account = $this->get_by_id($bank_account_id);
        
        if (($handle = fopen($file_path, 'r')) !== FALSE) {
            $header = fgetcsv($handle);
            
            while (($row = fgetcsv($handle)) !== FALSE) {
                if (count($row) < 4) continue;
                
                $txn = [
                    'transaction_date' => $this->parse_date($row[0]),
                    'description' => trim($row[1]),
                    'bank_reference' => trim($row[2] ?? ''),
                    'amount' => $this->parse_amount($row[3]),
                    'transaction_type' => $this->determine_type($row)
                ];
                
                if ($txn['amount'] != 0) {
                    $transactions[] = $txn;
                }
            }
            
            fclose($handle);
        }
        
        return $transactions;
    }
    
    /**
     * Parse Excel File
     */
    private function parse_excel($file_path, $bank_account_id) {
        $transactions = [];
        
        // Requires PHPSpreadsheet
        if (!class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
            return $transactions;
        }
        
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            array_shift($rows); // Remove header
            
            foreach ($rows as $row) {
                if (count($row) < 4 || empty($row[0])) continue;
                
                $txn = [
                    'transaction_date' => $this->parse_date($row[0]),
                    'description' => trim($row[1]),
                    'bank_reference' => trim($row[2] ?? ''),
                    'amount' => $this->parse_amount($row[3]),
                    'transaction_type' => $this->determine_type($row)
                ];
                
                if ($txn['amount'] != 0) {
                    $transactions[] = $txn;
                }
            }
            
        } catch (Exception $e) {
            log_message('error', 'Excel parse error: ' . $e->getMessage());
        }
        
        return $transactions;
    }
    
    /**
     * Parse Date
     */
    private function parse_date($date_str) {
        $formats = ['Y-m-d', 'd-m-Y', 'd/m/Y', 'm/d/Y', 'Y/m/d'];
        
        foreach ($formats as $format) {
            $d = DateTime::createFromFormat($format, trim($date_str));
            if ($d && $d->format($format) == trim($date_str)) {
                return $d->format('Y-m-d');
            }
        }
        
        // Try strtotime
        $timestamp = safe_timestamp($date_str);
        if ($timestamp > 0) {
            return date('Y-m-d', $timestamp);
        }
        
        return date('Y-m-d');
    }
    
    /**
     * Parse Amount
     */
    private function parse_amount($amount_str) {
        $amount = preg_replace('/[^0-9.\-]/', '', $amount_str);
        return floatval($amount);
    }
    
    /**
     * Determine Transaction Type
     */
    private function determine_type($row) {
        $amount = $this->parse_amount($row[3]);
        return $amount >= 0 ? 'credit' : 'debit';
    }
    
    /**
     * Auto Match Transaction
     */
    private function auto_match($txn) {
        // Try to match by member code in description
        if (preg_match('/MEM\d{4}[A-Z0-9]+/', $txn['description'], $matches)) {
            $member = $this->db->where('member_code', $matches[0])
                               ->get('members')
                               ->row();
            
            if ($member) {
                return [
                    'type' => 'member',
                    'id' => $member->id,
                    'member_id' => $member->id
                ];
            }
        }
        
        // Try to match by phone number
        if (preg_match('/\d{10}/', $txn['description'], $matches)) {
            $member = $this->db->where('phone', $matches[0])
                               ->get('members')
                               ->row();
            
            if ($member) {
                return [
                    'type' => 'member',
                    'id' => $member->id,
                    'member_id' => $member->id
                ];
            }
        }
        
        // Try to match by account number
        if (preg_match('/SAV\d{4}\d+/', $txn['description'], $matches)) {
            $savings = $this->db->where('account_number', $matches[0])
                                ->get('savings_accounts')
                                ->row();
            
            if ($savings) {
                return [
                    'type' => 'savings_account',
                    'id' => $savings->id,
                    'member_id' => $savings->member_id
                ];
            }
        }
        
        // Try to match by loan number
        if (preg_match('/LN\d{4}\d+/', $txn['description'], $matches)) {
            $loan = $this->db->where('loan_number', $matches[0])
                             ->get('loans')
                             ->row();
            
            if ($loan) {
                return [
                    'type' => 'loan',
                    'id' => $loan->id,
                    'member_id' => $loan->member_id
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Manual Match Transaction
     */
    public function match_transaction($transaction_id, $match_type, $match_id, $member_id, $matched_by) {
        // Update transaction status
        $this->db->where('id', $transaction_id)
                 ->update('bank_transactions', [
                     'mapping_status' => 'mapped',
                     'detected_member_id' => $member_id,
                     'updated_at' => date('Y-m-d H:i:s')
                 ]);
        
        // Create transaction mapping record
        return $this->db->insert('transaction_mappings', [
            'bank_transaction_id' => $transaction_id,
            'member_id' => $member_id,
            'mapping_type' => $match_type,
            'related_id' => $match_id,
            'amount' => $this->db->where('id', $transaction_id)->get('bank_transactions')->row()->amount,
            'mapped_by' => $matched_by,
            'mapped_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Confirm Transaction and Create Payment
     */
    public function confirm_transaction($transaction_id, $payment_type, $confirmed_by) {
        $this->db->trans_begin();
        
        try {
            $txn = $this->db->where('id', $transaction_id)
                            ->get('bank_transactions')
                            ->row();
            
            if (!$txn || $txn->mapping_status !== 'mapped') {
                throw new Exception('Transaction not matched');
            }
            
            // Get mapping information
            $mapping = $this->db->where('bank_transaction_id', $transaction_id)
                               ->get('transaction_mappings')
                               ->row();
            
            if (!$mapping) {
                throw new Exception('Transaction mapping not found');
            }
            
            $payment_id = null;
            
            // Create appropriate payment record
            if ($payment_type === 'savings' && $mapping->mapping_type === 'savings') {
                $this->load->model('Savings_model');
                $payment_id = $this->Savings_model->record_payment([
                    'savings_account_id' => $mapping->related_id,
                    'transaction_type' => 'deposit',
                    'amount' => abs($txn->amount),
                    'payment_mode' => 'bank_transfer',
                    'reference_number' => $txn->reference_number,
                    'remarks' => 'Bank import: ' . $txn->description,
                    'received_by' => $confirmed_by
                ]);
            } elseif ($payment_type === 'loan' && $mapping->mapping_type === 'loan_payment') {
                $this->load->model('Loan_model');
                $payment_id = $this->Loan_model->record_payment([
                    'loan_id' => $mapping->related_id,
                    'total_amount' => abs($txn->amount),
                    'payment_mode' => 'bank_transfer',
                    'reference_number' => $txn->reference_number,
                    'remarks' => 'Bank import: ' . $txn->description,
                    'received_by' => $confirmed_by
                ]);
            }
            
            // Update mapping record as confirmed
            $this->db->where('id', $mapping->id)
                     ->update('transaction_mappings', [
                         'is_reversed' => 0,
                         'mapped_by' => $confirmed_by
                     ]);
            
            // Update bank transaction status
            $this->db->where('id', $transaction_id)
                     ->update('bank_transactions', [
                         'mapping_status' => 'mapped',
                         'updated_at' => date('Y-m-d H:i:s')
                     ]);
            
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return false;
            }
            
            $this->db->trans_commit();
            return $payment_id;
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            throw $e;
        }
    }
    
    /**
     * Skip Transaction
     */
    public function skip_transaction($transaction_id, $reason, $skipped_by) {
        return $this->db->where('id', $transaction_id)
                        ->update('bank_transactions', [
                            'mapping_status' => 'ignored',
                            'remarks' => $reason,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
    }
    
    /**
     * Get Import History (detailed)
     * Returns recent imports with counts and importer info
     */
    public function get_imports($limit = 50) {
        return $this->db->select('bsi.*, ba.bank_name, ba.account_number as account_name, au.full_name as imported_by_name,
                                  (SELECT COUNT(*) FROM bank_transactions WHERE import_id = bsi.id) as total_transactions,
                                  (SELECT COUNT(*) FROM bank_transactions WHERE import_id = bsi.id AND mapping_status = "mapped") as matched_count,
                                  (SELECT COUNT(*) FROM bank_transactions WHERE import_id = bsi.id AND mapping_status = "unmapped") as unmatched_count')
                        ->from('bank_statement_imports bsi')
                        ->join('bank_accounts ba', 'ba.id = bsi.bank_account_id', 'left')
                        ->join('admin_users au', 'au.id = bsi.imported_by', 'left')
                        ->order_by('bsi.imported_at', 'DESC')
                        ->limit($limit)
                        ->get()
                        ->result();
    }
    
    /**
     * Get Single Import
     */
    public function get_import($import_id) {
        return $this->db->select('bsi.*, ba.bank_name, ba.account_number')
                        ->from('bank_statement_imports bsi')
                        ->join('bank_accounts ba', 'ba.id = bsi.bank_account_id')
                        ->where('bsi.id', $import_id)
                        ->get()
                        ->row();
    }
    
    /**
     * Get Import Transactions
     */
    public function get_import_transactions($import_id, $status = null) {
        $this->db->where('import_id', $import_id);
        
        if ($status) {
            $this->db->where('mapping_status', $status);
        }
        
        return $this->db->order_by('transaction_date', 'DESC')
                        ->get('bank_transactions')
                        ->result();
    }

    /**
     * Map and Process a Transaction (used by controller and integration tests)
     * @param int $transaction_id
     * @param array $data - mapping data (paid_by_member_id, paid_for_member_id, transaction_category, related_type, related_id, remarks)
     * @param int $admin_id
     * @return bool
     */
    public function map_and_process_transaction($transaction_id, $data, $admin_id) {
        // Update mapping fields
        $update = [
            'paid_by_member_id' => $data['paid_by_member_id'] ?? null,
            'paid_for_member_id' => $data['paid_for_member_id'] ?? ($data['paid_by_member_id'] ?? null),
            'transaction_category' => $data['transaction_category'] ?? $data['transaction_type'] ?? null,
            'mapping_status' => 'mapped',
            'remarks' => $data['mapping_remarks'] ?? $data['remarks'] ?? null,
            'mapped_by' => $admin_id,
            'mapped_at' => date('Y-m-d H:i:s'),
            'updated_by' => $admin_id,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Conditionally include related_type/related_id if columns exist
        if ($this->db->field_exists('related_type', 'bank_transactions')) {
            $update['related_type'] = $data['related_type'] ?? null;
        }
        if ($this->db->field_exists('related_id', 'bank_transactions')) {
            $update['related_id'] = $data['related_id'] ?? null;
        }

        $this->db->where('id', $transaction_id)->update('bank_transactions', $update);

        // Fetch transaction
        $txn = $this->db->where('id', $transaction_id)->get('bank_transactions')->row();
        if (!$txn) return false;

        // Handle different schema possibilities (credit_amount/debit_amount) or single amount
        $amount = 0;
        if (isset($txn->credit_amount) && $txn->credit_amount > 0) {
            $amount = $txn->credit_amount;
        } elseif (isset($txn->debit_amount) && $txn->debit_amount > 0) {
            $amount = abs($txn->debit_amount);
        } else {
            $amount = $txn->amount ?? 0;
        }
        $type = $update['transaction_category'];

        try {
            // Determine related id/type fallback to passed data if columns not present
        $related_id = $update['related_id'] ?? ($data['related_id'] ?? null);
        $related_type = $update['related_type'] ?? ($data['related_type'] ?? null);

        switch ($type) {
                case 'emi':
                    $target_loan_id = $related_id ?: ($data['related_id'] ?? null);
                    if (!empty($target_loan_id)) {
                        $this->load->model('Loan_model');
                        $payment_data = [
                            'loan_id' => $target_loan_id,
                            'total_amount' => $amount,
                            'payment_mode' => 'bank_transfer',
                            'bank_transaction_id' => $transaction_id,
                            'payment_type' => 'regular',
                            'created_by' => $admin_id
                        ];
                        $payment_id = $this->Loan_model->record_payment($payment_data);
                        if (!$payment_id) {
                            throw new Exception('Loan_model::record_payment failed');
                        }
                        return $payment_id;
                    }
                    break;

                case 'savings':
                    if (!empty($update['related_type']) && $update['related_type'] == 'savings' && !empty($update['related_id'])) {
                        $this->load->model('Savings_model');
                        $payment_data = [
                            'savings_account_id' => $update['related_id'],
                            'amount' => $amount,
                            'transaction_type' => 'deposit',
                            'reference' => $transaction_id,
                            'created_by' => $admin_id
                        ];
                        $transaction_id = $this->Savings_model->record_payment($payment_data);
                        if (!$transaction_id) {
                            throw new Exception('Savings_model::record_payment failed');
                        }
                        return $transaction_id;
                    }
                    break;

                case 'fine':
                    $this->load->model('Fine_model');
                    $fine = $this->db->where('member_id', $update['paid_for_member_id'])
                                     ->where('status', 'pending')
                                     ->order_by('fine_date', 'ASC')
                                     ->get('fines')
                                     ->row();
                    if ($fine) {
                        $res = $this->Fine_model->record_payment($fine->id, $amount, 'bank_transfer', $transaction_id, $admin_id);
                        if (!$res) {
                            throw new Exception('Fine_model::record_payment failed');
                        }
                        return $res;
                    }
                    break;

                default:
                    // unknown type, just mark mapped
                    return true;
            }
        } catch (Exception $e) {
            // rethrow for caller to handle
            throw $e;
        }

        throw new Exception('No action taken for transaction mapping');
    }
    
    /**
     * Get Pending Transactions
     */
    public function get_pending_transactions() {
        return $this->db->select('bt.*, ba.bank_name')
                        ->from('bank_transactions bt')
                        ->join('bank_accounts ba', 'ba.id = bt.bank_account_id')
                        ->where_in('bt.mapping_status', ['partial', 'mapped'])
                        ->order_by('bt.transaction_date', 'ASC')
                        ->get()
                        ->result();
    }
    
    /**
     * Get Account Balance
     */
    public function get_account_balance($account_id) {
        $credits = $this->db->select_sum('amount')
                            ->where('bank_account_id', $account_id)
                            ->where('transaction_type', 'credit')
                            ->get('bank_transactions')
                            ->row()
                            ->amount ?? 0;
        
        $debits = $this->db->select_sum('amount')
                           ->where('bank_account_id', $account_id)
                           ->where('transaction_type', 'debit')
                           ->get('bank_transactions')
                           ->row()
                           ->amount ?? 0;
        
        return $credits - $debits;
    }
    
    /**
     * Get Unmatched Transactions
     */
    public function get_unmatched_transactions() {
        return $this->db->select('bt.*, ba.bank_name, ba.account_number')
                        ->from('bank_transactions bt')
                        ->join('bank_accounts ba', 'ba.id = bt.bank_account_id')
                        ->where('bt.mapping_status', 'unmapped')
                        ->order_by('bt.transaction_date', 'DESC')
                        ->get()
                        ->result();
    }
    
    /**
     * Get Potential Savings Payment Matches
     */
    public function get_potential_savings_matches() {
        return $this->db->select('sp.*, m.member_code, m.first_name, m.last_name, sa.account_number as savings_account')
                        ->from('savings_payments sp')
                        ->join('members m', 'm.id = sp.member_id')
                        ->join('savings_accounts sa', 'sa.id = sp.savings_account_id')
                        ->where('sp.status', 'pending')
                        ->where('sp.payment_date >=', date('Y-m-d', safe_timestamp('-30 days')))
                        ->order_by('sp.payment_date', 'DESC')
                        ->get()
                        ->result();
    }
    
    /**
     * Get Potential Loan Payment Matches
     */
    public function get_potential_loan_matches() {
        return $this->db->select('lp.*, m.member_code, m.first_name, m.last_name, l.loan_number')
                        ->from('loan_payments lp')
                        ->join('members m', 'm.id = lp.member_id')
                        ->join('loans l', 'l.id = lp.loan_id')
                        ->where('lp.status', 'pending')
                        ->where('lp.payment_date >=', date('Y-m-d', safe_timestamp('-30 days')))
                        ->order_by('lp.payment_date', 'DESC')
                        ->get()
                        ->result();
    }
    
    /**
     * Get Transactions for Mapping Interface
     */
    public function get_transactions_for_mapping($filters = []) {
        $this->db->select('bt.*, ba.bank_name, ba.account_number as bank_account_number,
                          payer.first_name as paid_by_first, payer.last_name as paid_by_last, payer.member_code as paid_by_code,
                          payee.first_name as paid_for_first, payee.last_name as paid_for_last, payee.member_code as paid_for_code,
                          updater.full_name as updated_by_name');
        $this->db->from('bank_transactions bt');
        $this->db->join('bank_accounts ba', 'ba.id = bt.bank_account_id', 'left');
        $this->db->join('members payer', 'payer.id = bt.paid_by_member_id', 'left');
        $this->db->join('members payee', 'payee.id = bt.paid_for_member_id', 'left');
        $this->db->join('admin_users updater', 'updater.id = bt.updated_by', 'left');
        
        if (!empty($filters['bank_id'])) {
            $this->db->where('bt.bank_account_id', $filters['bank_id']);
        }
        
        if (!empty($filters['from_date'])) {
            $this->db->where('bt.transaction_date >=', $filters['from_date']);
        }
        
        if (!empty($filters['to_date'])) {
            $this->db->where('bt.transaction_date <=', $filters['to_date']);
        }
        
        if (!empty($filters['mapping_status'])) {
            $this->db->where('bt.mapping_status', $filters['mapping_status']);
        }
        
        $this->db->order_by('bt.transaction_date', 'DESC');
        $this->db->limit(100);
        
        $transactions = $this->db->get()->result();
        
        // Format the results
        foreach ($transactions as &$txn) {
            $txn->paid_by_name = $txn->paid_by_first ? "{$txn->paid_by_first} {$txn->paid_by_last} ({$txn->paid_by_code})" : null;
            $txn->paid_for_name = $txn->paid_for_first ? "{$txn->paid_for_first} {$txn->paid_for_last} ({$txn->paid_for_code})" : null;

            // Normalize amount fields to avoid undefined property warnings in views
            $amount = isset($txn->amount) ? (float) $txn->amount : 0.0;
            if (isset($txn->transaction_type) && $txn->transaction_type === 'credit') {
                $txn->credit_amount = $amount;
                $txn->debit_amount = 0.0;
            } else {
                $txn->debit_amount = $amount;
                $txn->credit_amount = 0.0;
            }

            // Ensure mapping_status is set
            $txn->mapping_status = $txn->mapping_status ?? 'unmapped';

            // Format updated_by name fallback
            $txn->updated_by_name = $txn->updated_by_name ?? null;
        }
        
        return $transactions;
    }
    
    /**
     * Generate Import Code
     */
    public function generate_import_code() {
        return 'IMP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
    

}
