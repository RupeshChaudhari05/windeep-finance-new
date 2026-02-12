<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bank_model - Bank Statement Import & Transaction Mapping
 */
class Bank_model extends MY_Model {
    
    protected $table = 'bank_accounts';
    protected $primary_key = 'id';
    protected $timestamps = true;
    
    /**
     * Override update method for debugging
     */
    public function update($id, $data) {
        // Add timestamp
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $result = $this->db->where($this->primary_key, $id)
                          ->update($this->table, $data);
        
        // Debug: log the query
        log_message('debug', 'Bank_model update query: ' . $this->db->last_query());
        log_message('debug', 'Bank_model update result: ' . ($result ? 'true' : 'false'));
        
        return $result;
    }
    
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
    public function import_statement($file_path, $bank_account_id, $imported_by, $mapping_column = null) {
        $this->db->trans_begin();
        
        try {
            // Create import record
            $import_code = 'IMP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            $import_data = [
                'import_code' => $import_code,
                'bank_account_id' => $bank_account_id,
                'file_name' => basename($file_path),
                'file_path' => $file_path,
                'file_type' => strtolower(pathinfo($file_path, PATHINFO_EXTENSION)),
                'status' => 'processing',
                'imported_by' => $imported_by
            ];
            
            $this->db->insert('bank_statement_imports', $import_data);
            $import_id = $this->db->insert_id();
            
            // Parse the file (pass mapping column if provided)
            $transactions = $this->parse_statement($file_path, $bank_account_id, $mapping_column);
            log_message('debug', 'Parsed ' . count($transactions) . ' transactions from file: ' . $file_path);
            
            $total = count($transactions);
            $matched = 0;
            $unmatched = 0;
            $duplicates = 0;
            $total_amount = 0;
            
            foreach ($transactions as $txn) {
                // Bug #10 Fix: Check for duplicate UTR number (if provided)
                if (!empty($txn['utr_number'])) {
                    $utr_exists = $this->db->where('utr_number', $txn['utr_number'])
                                           ->count_all_results('bank_transactions');
                    
                    if ($utr_exists > 0) {
                        $duplicates++;
                        log_message('debug', 'Duplicate UTR skipped: ' . $txn['utr_number']);
                        continue;
                    }
                }
                
                // Check for duplicate transaction (date + amount + description)
                $exists = $this->db->where('bank_account_id', $bank_account_id)
                                   ->where('transaction_date', $txn['transaction_date'])
                                   ->where('amount', abs($txn['amount']))
                                   ->where('description', $txn['description'])
                                   ->count_all_results('bank_transactions');
                
                if ($exists > 0) {
                    $duplicates++;
                    log_message('debug', 'Duplicate transaction skipped: ' . json_encode($txn));
                    continue;
                }
                
                $txn['import_id'] = $import_id;
                $txn['bank_account_id'] = $bank_account_id;
                $txn['mapping_status'] = 'unmapped';
                
                // Ensure amount is positive and transaction_type is set correctly
                $txn['amount'] = abs($txn['amount']);
                
                // Try auto-match
                $match = $this->auto_match($txn);
                if ($match) {
                    $txn['mapping_status'] = 'mapped';
                    $txn['detected_member_id'] = $match['member_id'] ?? null;
                    $matched++;
                    log_message('debug', 'Auto-matched transaction: ' . json_encode($txn));
                } else {
                    $unmatched++;
                    log_message('debug', 'Unmatched transaction: ' . json_encode($txn));
                }
                
                $total_amount += $txn['amount'];
                
                $result = $this->db->insert('bank_transactions', $txn);
                if (!$result) {
                    log_message('error', 'Failed to insert transaction: ' . json_encode($txn) . ' - Error: ' . $this->db->error());
                }
            }
            
            // Update import record
            $this->db->where('id', $import_id)
                     ->update('bank_statement_imports', [
                         'status' => 'completed',
                         'total_transactions' => $total,
                         'mapped_count' => $matched,
                         'unmapped_count' => $unmatched,
                         'completed_at' => date('Y-m-d H:i:s')
                     ]);
            
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return false;
            }
            
            $this->db->trans_commit();
            
            return [
                'success' => true,
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
    private function parse_statement($file_path, $bank_account_id, $mapping_column = null) {
        $transactions = [];
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        if ($extension === 'csv') {
            $transactions = $this->parse_csv($file_path, $bank_account_id, $mapping_column);
        } elseif (in_array($extension, ['xls', 'xlsx'])) {
            $transactions = $this->parse_excel($file_path, $bank_account_id, $mapping_column);
        }
        
        return $transactions;
    }
    
    /**
     * Parse CSV File
     */
    private function parse_csv($file_path, $bank_account_id, $mapping_column = null) {
        $transactions = [];
        
        // Get bank account for parsing rules
        $account = $this->get_by_id($bank_account_id);
        
        if (($handle = fopen($file_path, 'r')) !== FALSE) {
            $header = fgetcsv($handle);

            // Normalize mapping column to zero-based index if provided (1-based input from UI)
            $map_idx = null;
            if (!empty($mapping_column) && is_numeric($mapping_column) && intval($mapping_column) > 0) {
                $map_idx = intval($mapping_column) - 1;
            }

            // Build header map (normalize header names)
            $header_map = [];
            if (!empty($header) && is_array($header)) {
                foreach ($header as $i => $h) {
                    $key = strtolower(trim(preg_replace('/[^a-z0-9_ ]/', '', $h)));
                    $key = preg_replace('/\s+/', ' ', $key);
                    $header_map[$key] = $i;
                }
            }

            while (($row = fgetcsv($handle)) !== FALSE) {
                if (count($row) < 1) continue;

                // Helper to get value by header name variants
                $get_by_names = function($names, $default = null) use ($row, $header_map) {
                    foreach ($names as $n) {
                        $k = strtolower(trim(preg_replace('/[^a-z0-9_ ]/', '', $n)));
                        $k = preg_replace('/\s+/', ' ', $k);
                        if (isset($header_map[$k]) && isset($row[$header_map[$k]])) {
                            return $row[$header_map[$k]];
                        }
                    }
                    return $default;
                };

                // Determine date column (common header names)
                $date_raw = $get_by_names(['transadate', 'transactiondate', 'date', 'transa date', 'trans date'], $row[0] ?? '');

                // Determine description (try Description1 + Description2 or fallback to second column)
                $desc1 = $get_by_names(['description1', 'description 1', 'description'], null);
                $desc2 = $get_by_names(['description2', 'description 2'], null);
                if ($desc1 !== null && $desc2 !== null) {
                    $description = trim($desc1 . ' ' . $desc2);
                } elseif ($desc1 !== null) {
                    $description = trim($desc1);
                } else {
                    $description = trim($row[1] ?? '');
                }

                // Determine credit/debit columns by header names or fallback to index 2/3
                $credit_raw = $get_by_names(['credit', 'cr', 'credit amount'], $row[6] ?? ($row[2] ?? 0));
                $debit_raw = $get_by_names(['debit', 'dr', 'debit amount'], $row[5] ?? ($row[3] ?? 0));

                // If mapping column provided and present in row, use it for reference
                $reference = '';
                if ($map_idx !== null && isset($row[$map_idx])) {
                    $reference = trim($row[$map_idx]);
                } else {
                    $reference = trim($get_by_names(['transid', 'reference', 'utr', 'reference number', 'reference_number'], $row[4] ?? ''));
                }

                // Parse amounts
                $credit = $this->parse_amount($credit_raw);
                $debit = $this->parse_amount($debit_raw);

                // Determine amount and type
                if ($credit > 0 && $debit == 0) {
                    $amount = $credit;
                    $type = 'credit';
                } elseif ($debit > 0 && $credit == 0) {
                    $amount = -$debit; // Negative for debit
                    $type = 'debit';
                } elseif ($credit > 0 && $debit > 0) {
                    // Prefer whichever is larger
                    if ($credit >= $debit) {
                        $amount = $credit; $type = 'credit';
                    } else {
                        $amount = -$debit; $type = 'debit';
                    }
                } else {
                    continue; // Skip rows with no amount
                }

                $txn = [
                    'transaction_date' => $this->parse_date($date_raw),
                    'description' => $description,
                    'reference_number' => $reference,
                    'amount' => $amount,
                    'transaction_type' => $type
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
    private function parse_excel($file_path, $bank_account_id, $mapping_column = null) {
        $transactions = [];
        
        // Requires PHPSpreadsheet
        if (!class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
            log_message('error', 'PHPSpreadsheet not available for Excel parsing');
            return $transactions;
        }
        
        try {
            log_message('debug', 'Attempting to parse Excel file: ' . $file_path);
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            log_message('debug', 'Excel file loaded, ' . count($rows) . ' rows found');
            $header = array_shift($rows); // Remove header

            // normalize mapping column
            $map_idx = null;
            if (!empty($mapping_column) && is_numeric($mapping_column) && intval($mapping_column) > 0) {
                $map_idx = intval($mapping_column) - 1;
            }

            // Build header map from $header
            $header_map = [];
            if (!empty($header) && is_array($header)) {
                foreach ($header as $i => $h) {
                    $key = strtolower(trim(preg_replace('/[^a-z0-9_ ]/', '', $h)));
                    $key = preg_replace('/\s+/', ' ', $key);
                    $header_map[$key] = $i;
                }
            }

            foreach ($rows as $row) {
                if (count($row) < 1 || empty($row[0])) continue;

                // Helper to get value by header name variants
                $get_by_names = function($names, $default = null) use ($row, $header_map) {
                    foreach ($names as $n) {
                        $k = strtolower(trim(preg_replace('/[^a-z0-9_ ]/', '', $n)));
                        $k = preg_replace('/\s+/', ' ', $k);
                        if (isset($header_map[$k]) && isset($row[$header_map[$k]])) {
                            return $row[$header_map[$k]];
                        }
                    }
                    return $default;
                };

                // Determine date, description, credit/debit similarly to CSV parser
                $date_raw = $get_by_names(['transadate', 'transactiondate', 'date', 'transa date', 'trans date'], $row[0] ?? '');

                $desc1 = $get_by_names(['description1', 'description 1', 'description'], null);
                $desc2 = $get_by_names(['description2', 'description 2'], null);
                if ($desc1 !== null && $desc2 !== null) {
                    $description = trim($desc1 . ' ' . $desc2);
                } elseif ($desc1 !== null) {
                    $description = trim($desc1);
                } else {
                    $description = trim($row[1] ?? '');
                }

                $credit_raw = $get_by_names(['credit', 'cr', 'credit amount'], $row[6] ?? ($row[2] ?? 0));
                $debit_raw = $get_by_names(['debit', 'dr', 'debit amount'], $row[5] ?? ($row[3] ?? 0));

                $reference = '';
                if ($map_idx !== null && isset($row[$map_idx])) {
                    $reference = trim($row[$map_idx]);
                } else {
                    $reference = trim($get_by_names(['transid', 'reference', 'utr', 'reference number', 'reference_number'], $row[4] ?? ''));
                }

                $credit = $this->parse_amount($credit_raw);
                $debit = $this->parse_amount($debit_raw);

                if ($credit > 0 && $debit == 0) {
                    $amount = $credit;
                    $type = 'credit';
                } elseif ($debit > 0 && $credit == 0) {
                    $amount = -$debit; $type = 'debit';
                } elseif ($credit > 0 && $debit > 0) {
                    if ($credit >= $debit) { $amount = $credit; $type = 'credit'; }
                    else { $amount = -$debit; $type = 'debit'; }
                } else {
                    continue;
                }

                $txn = [
                    'transaction_date' => $this->parse_date($date_raw),
                    'description' => $description,
                    'reference_number' => $reference,
                    'amount' => $amount,
                    'transaction_type' => $type
                ];

                if ($txn['amount'] != 0) {
                    $transactions[] = $txn;
                }
            }
            
            log_message('debug', 'Parsed ' . count($transactions) . ' transactions from Excel');
            
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
     * Auto Match Transaction
     */
    private function auto_match($txn) {
        log_message('debug', 'Auto-matching transaction: ' . $txn['description']);
        
        // Try to match by member code in description (format: MEMB000001)
        if (preg_match('/MEMB\d{6}/', $txn['description'], $matches)) {
            $member = $this->db->where('member_code', $matches[0])
                               ->get('members')
                               ->row();
            
            if ($member) {
                log_message('debug', 'Matched by member code: ' . $matches[0] . ' -> Member ID: ' . $member->id);
                return [
                    'type' => 'member',
                    'id' => $member->id,
                    'member_id' => $member->id
                ];
            }
        }
        
        // Try to match by phone number (10 digits)
        if (preg_match('/\b(\d{10})\b/', $txn['description'], $matches)) {
            $member = $this->db->where('phone', $matches[1])
                               ->get('members')
                               ->row();
            
            if ($member) {
                log_message('debug', 'Matched by phone: ' . $matches[1] . ' -> Member ID: ' . $member->id);
                return [
                    'type' => 'member',
                    'id' => $member->id,
                    'member_id' => $member->id
                ];
            }
        }
        
        // Try to match by savings account number (format: SAV2025000120)
        if (preg_match('/SAV\d{10}/', $txn['description'], $matches)) {
            $savings = $this->db->where('account_number', $matches[0])
                                ->get('savings_accounts')
                                ->row();
            
            if ($savings) {
                log_message('debug', 'Matched by savings account: ' . $matches[0] . ' -> Member ID: ' . $savings->member_id);
                return [
                    'type' => 'savings_account',
                    'id' => $savings->id,
                    'member_id' => $savings->member_id
                ];
            }
        }
        
        // Try to match by loan number (format: LNC440DA)
        if (preg_match('/LN[A-Z0-9]{5,}/', $txn['description'], $matches)) {
            $loan = $this->db->where('loan_number', $matches[0])
                             ->get('loans')
                             ->row();
            
            if ($loan) {
                log_message('debug', 'Matched by loan number: ' . $matches[0] . ' -> Member ID: ' . $loan->member_id);
                return [
                    'type' => 'loan',
                    'id' => $loan->id,
                    'member_id' => $loan->member_id
                ];
            }
        }
        
        log_message('debug', 'No match found for transaction');
        return null;
    }
    
    /**
     * Manual Match Transaction
     */
    public function match_transaction($transaction_id, $match_type, $match_id, $member_id = null, $matched_by = null) {
        // If member_id not provided, attempt to derive it from match_type and match_id
        if (empty($member_id)) {
            if (in_array($match_type, ['savings', 'savings_account', 'savings_payment'])) {
                $savings = $this->db->where('id', $match_id)->get('savings_accounts')->row();
                $member_id = $savings->member_id ?? null;
            } elseif (in_array($match_type, ['loan', 'loan_payment', 'emi'])) {
                $loan = $this->db->where('id', $match_id)->get('loans')->row();
                $member_id = $loan->member_id ?? null;
            } elseif ($match_type === 'member') {
                // match_id is actually member id in this case
                $member_id = $match_id;
            }
        }

        // Default matched_by to current admin user if available
        if (empty($matched_by) && isset($this->session)) {
            $matched_by = $this->session->userdata('admin_id') ?? $this->session->userdata('user_id') ?? null;
        }

        // Update transaction status (always mark as mapped for UI)
        $this->db->where('id', $transaction_id)
                 ->update('bank_transactions', [
                     'mapping_status' => 'mapped',
                     'detected_member_id' => $member_id,
                     'updated_at' => date('Y-m-d H:i:s')
                 ]);

        // If we don't have a member_id (internal expense or bank-level mapping),
        // avoid inserting into `transaction_mappings` which requires a non-null member_id.
        if (empty($member_id)) {
            // Add mapping remarks to bank_transactions for audit and return success
            $updateTxn = [];
            if ($this->db->field_exists('mapped_by', 'bank_transactions')) {
                $updateTxn['mapped_by'] = $matched_by;
            }
            if ($this->db->field_exists('mapped_at', 'bank_transactions')) {
                $updateTxn['mapped_at'] = date('Y-m-d H:i:s');
            }
            if ($this->db->field_exists('mapping_remarks', 'bank_transactions')) {
                $updateTxn['mapping_remarks'] = 'Mapped as internal/bank expense: ' . $match_type . ' ' . ($match_id ?? '');
            }
            if (!empty($updateTxn)) {
                $updateTxn['updated_at'] = date('Y-m-d H:i:s');
                $this->db->where('id', $transaction_id)->update('bank_transactions', $updateTxn);
            }
            return true;
        }

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
        return $this->db->select('bsi.*, ba.bank_name, ba.account_number, au.full_name as imported_by_name')
                        ->from('bank_statement_imports bsi')
                        ->join('bank_accounts ba', 'ba.id = bsi.bank_account_id')
                        ->join('admin_users au', 'au.id = bsi.imported_by', 'left')
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
                            'reference_number' => $transaction_id,
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
     * Map and Process Split Payment
     * Bug #11 Fix: Support splitting one bank transaction across multiple EMIs/accounts
     * 
     * @param int $transaction_id
     * @param array $splits - Array of split mappings: [['type' => 'emi', 'related_id' => loan_id, 'amount' => 1000], ...]
     * @param int $admin_id
     * @return array - Array of processing results
     */
    public function map_split_payment($transaction_id, $splits, $admin_id) {
        $this->db->trans_begin();
        
        try {
            // Get transaction
            $txn = $this->db->where('id', $transaction_id)->get('bank_transactions')->row();
            
            if (!$txn) {
                throw new Exception('Transaction not found');
            }
            
            // Calculate available amount
            $txn_amount = 0;
            if (isset($txn->credit_amount) && $txn->credit_amount > 0) {
                $txn_amount = $txn->credit_amount;
            } elseif (isset($txn->debit_amount) && $txn->debit_amount > 0) {
                $txn_amount = abs($txn->debit_amount);
            } else {
                $txn_amount = $txn->amount ?? 0;
            }
            
            // Validate split amounts
            $total_split_amount = array_sum(array_column($splits, 'amount'));
            
            if ($total_split_amount > $txn_amount) {
                throw new Exception('Split amounts (' . $total_split_amount . ') exceed transaction amount (' . $txn_amount . ')');
            }
            
            $results = [];
            
            // Process each split
            foreach ($splits as $index => $split) {
                // Convert 'emi' to 'loan_payment' for database enum compatibility
                $mapping_type = ($split['type'] ?? 'loan_payment');
                if ($mapping_type === 'emi' || $mapping_type === 'loan') {
                    $mapping_type = 'loan_payment';
                }
                
                // Create transaction mapping record
                $mapping_data = [
                    'bank_transaction_id' => $transaction_id,
                    'mapping_type' => $mapping_type,
                    'related_id' => $split['related_id'] ?? null,
                    'amount' => $split['amount'],
                    'member_id' => $split['member_id'] ?? $txn->paid_by_member_id ?? null,
                    'narration' => $split['remarks'] ?? null,
                    'mapped_by' => $admin_id,
                    'mapped_at' => date('Y-m-d H:i:s')
                ];
                
                $this->db->insert('transaction_mappings', $mapping_data);
                $mapping_id = $this->db->insert_id();
                
                // Process based on type
                switch ($split['type']) {
                    case 'emi':
                    case 'loan':
                        $this->load->model('Loan_model');
                        $payment_data = [
                            'loan_id' => $split['related_id'],
                            'total_amount' => $split['amount'],
                            'payment_mode' => 'bank_transfer',
                            'bank_transaction_id' => $transaction_id,
                            'payment_type' => 'regular',
                            'created_by' => $admin_id
                        ];
                        $payment_id = $this->Loan_model->record_payment($payment_data);
                        
                        $results[] = [
                            'mapping_id' => $mapping_id,
                            'type' => 'emi',
                            'related_id' => $split['related_id'],
                            'amount' => $split['amount'],
                            'payment_id' => $payment_id,
                            'status' => $payment_id ? 'success' : 'failed'
                        ];
                        break;
                        
                    case 'savings':
                        $this->load->model('Savings_model');
                        $savings_data = [
                            'savings_account_id' => $split['related_id'],
                            'amount' => $split['amount'],
                            'transaction_type' => 'deposit',
                            'reference_number' => $transaction_id,
                            'created_by' => $admin_id
                        ];
                        $savings_id = $this->Savings_model->record_payment($savings_data);
                        
                        $results[] = [
                            'mapping_id' => $mapping_id,
                            'type' => 'savings',
                            'related_id' => $split['related_id'],
                            'amount' => $split['amount'],
                            'savings_id' => $savings_id,
                            'status' => $savings_id ? 'success' : 'failed'
                        ];
                        break;
                        
                    case 'fine':
                        $this->load->model('Fine_model');
                        $fine_id = $split['related_id'];
                        $result = $this->Fine_model->record_payment($fine_id, $split['amount'], 'bank_transfer', $transaction_id, $admin_id);
                        
                        $results[] = [
                            'mapping_id' => $mapping_id,
                            'type' => 'fine',
                            'related_id' => $fine_id,
                            'amount' => $split['amount'],
                            'fine_payment_id' => $result,
                            'status' => $result ? 'success' : 'failed'
                        ];
                        break;
                }
            }
            
            // Update bank transaction status
            $status = ($total_split_amount >= $txn_amount) ? 'mapped' : 'split';
            $update = [
                'mapping_status' => $status,
                'mapped_amount' => $total_split_amount,
                'unmapped_amount' => $txn_amount - $total_split_amount,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            if ($this->db->field_exists('mapped_by', 'bank_transactions')) {
                $update['mapped_by'] = $admin_id;
            }
            if ($this->db->field_exists('mapped_at', 'bank_transactions')) {
                $update['mapped_at'] = date('Y-m-d H:i:s');
            }
            $this->db->where('id', $transaction_id)->update('bank_transactions', $update);
            
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return ['success' => false, 'error' => 'Transaction failed'];
            }
            
            $this->db->trans_commit();
            
            return [
                'success' => true,
                'transaction_id' => $transaction_id,
                'total_amount' => $txn_amount,
                'allocated_amount' => $total_split_amount,
                'remaining_amount' => $txn_amount - $total_split_amount,
                'splits' => $results
            ];
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
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

        if (!empty($filters['member_id'])) {
            $this->db->group_start();
            $this->db->where('bt.paid_by_member_id', $filters['member_id']);
            $this->db->or_where('bt.paid_for_member_id', $filters['member_id']);
            $this->db->or_where('bt.detected_member_id', $filters['member_id']);
            $this->db->group_end();
        }

        if (!empty($filters['transaction_type'])) {
            $this->db->where('bt.transaction_type', $filters['transaction_type']);
        }
        
        $this->db->order_by('bt.transaction_date', 'DESC');
        
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
