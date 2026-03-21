<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Import_model — Handles validation and execution of bulk imports
 * Supports: Members, Loans, Savings Transactions
 */
class Import_model extends MY_Model {

    protected $table = 'import_logs';

    /**
     * Validate rows based on import type
     */
    public function validate_rows($type, $rows) {
        $errors = [];
        $valid_count = 0;
        $error_count = 0;

        foreach ($rows as $idx => $row) {
            $row_num = $idx + 2; // +2 because row 1 is header
            $row_errors = [];

            switch ($type) {
                case 'members':
                    $row_errors = $this->_validate_member_row($row, $row_num);
                    break;
                case 'loans':
                    $row_errors = $this->_validate_loan_row($row, $row_num);
                    break;
                case 'savings_transactions':
                    $row_errors = $this->_validate_savings_tx_row($row, $row_num);
                    break;
            }

            if (empty($row_errors)) {
                $valid_count++;
            } else {
                $error_count++;
                $errors = array_merge($errors, $row_errors);
            }
        }

        return [
            'valid_count' => $valid_count,
            'error_count' => $error_count,
            'errors' => $errors
        ];
    }

    /**
     * Execute import
     */
    public function execute_import($type, $rows, $admin_id) {
        $inserted = 0;
        $skipped = 0;
        $error_count = 0;
        $error_details = [];

        foreach ($rows as $idx => $row) {
            $row_num = $idx + 2;

            try {
                switch ($type) {
                    case 'members':
                        $result = $this->_import_member($row, $admin_id);
                        break;
                    case 'loans':
                        $result = $this->_import_loan($row, $admin_id);
                        break;
                    case 'savings_transactions':
                        $result = $this->_import_savings_transaction($row, $admin_id);
                        break;
                    default:
                        $result = ['status' => 'error', 'message' => 'Unknown type'];
                }

                if ($result['status'] === 'inserted') {
                    $inserted++;
                } elseif ($result['status'] === 'skipped') {
                    $skipped++;
                } else {
                    $error_count++;
                    $error_details[] = "Row {$row_num}: " . $result['message'];
                }
            } catch (Exception $e) {
                $error_count++;
                $error_details[] = "Row {$row_num}: " . $e->getMessage();
            }
        }

        return [
            'inserted' => $inserted,
            'skipped' => $skipped,
            'errors' => $error_count,
            'error_details' => $error_details
        ];
    }

    // ===== VALIDATION METHODS =====

    private function _validate_member_row($row, $row_num) {
        $errors = [];

        if (empty($row['first_name'] ?? '')) {
            $errors[] = "Row {$row_num}: first_name is required";
        }
        if (empty($row['last_name'] ?? '')) {
            $errors[] = "Row {$row_num}: last_name is required";
        }
        if (empty($row['phone'] ?? '')) {
            $errors[] = "Row {$row_num}: phone is required";
        } elseif (!preg_match('/^\d{10,15}$/', preg_replace('/[^0-9]/', '', $row['phone']))) {
            $errors[] = "Row {$row_num}: phone must be 10-15 digits";
        }
        if (!empty($row['email'] ?? '') && !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Row {$row_num}: invalid email format";
        }
        if (!empty($row['date_of_birth'] ?? '') && !$this->_is_valid_date($row['date_of_birth'])) {
            $errors[] = "Row {$row_num}: date_of_birth must be YYYY-MM-DD format";
        }
        if (!empty($row['join_date'] ?? '') && !$this->_is_valid_date($row['join_date'])) {
            $errors[] = "Row {$row_num}: join_date must be YYYY-MM-DD format";
        }
        if (!empty($row['gender'] ?? '') && !in_array(strtolower($row['gender']), ['male', 'female', 'other'])) {
            $errors[] = "Row {$row_num}: gender must be male, female, or other";
        }
        if (!empty($row['aadhaar_number'] ?? '') && !preg_match('/^\d{12}$/', $row['aadhaar_number'])) {
            $errors[] = "Row {$row_num}: aadhaar_number must be 12 digits";
        }

        return $errors;
    }

    private function _validate_loan_row($row, $row_num) {
        $errors = [];

        if (empty($row['member_code'] ?? '')) {
            $errors[] = "Row {$row_num}: member_code is required";
        } else {
            $member = $this->db->where('member_code', trim($row['member_code']))
                               ->where('deleted_at IS NULL', null, false)
                               ->get('members')->row();
            if (!$member) {
                $errors[] = "Row {$row_num}: member_code '{$row['member_code']}' not found";
            }
        }
        if (empty($row['loan_product_id'] ?? '')) {
            $errors[] = "Row {$row_num}: loan_product_id is required";
        }
        if (empty($row['principal_amount'] ?? '') || !is_numeric($row['principal_amount']) || $row['principal_amount'] <= 0) {
            $errors[] = "Row {$row_num}: principal_amount must be a positive number";
        }
        if (empty($row['interest_rate'] ?? '') || !is_numeric($row['interest_rate'])) {
            $errors[] = "Row {$row_num}: interest_rate is required and must be numeric";
        }
        if (empty($row['interest_type'] ?? '') || !in_array(strtolower($row['interest_type']), ['flat', 'reducing', 'reducing_monthly'])) {
            $errors[] = "Row {$row_num}: interest_type must be flat, reducing, or reducing_monthly";
        }
        if (empty($row['tenure_months'] ?? '') || !is_numeric($row['tenure_months']) || $row['tenure_months'] < 1) {
            $errors[] = "Row {$row_num}: tenure_months must be a positive number";
        }
        if (empty($row['disbursement_date'] ?? '') || !$this->_is_valid_date($row['disbursement_date'])) {
            $errors[] = "Row {$row_num}: disbursement_date is required (YYYY-MM-DD)";
        }

        return $errors;
    }

    private function _validate_savings_tx_row($row, $row_num) {
        $errors = [];

        if (empty($row['member_code'] ?? '')) {
            $errors[] = "Row {$row_num}: member_code is required";
        } else {
            $member = $this->db->where('member_code', trim($row['member_code']))
                               ->where('deleted_at IS NULL', null, false)
                               ->get('members')->row();
            if (!$member) {
                $errors[] = "Row {$row_num}: member_code '{$row['member_code']}' not found";
            }
        }
        if (empty($row['transaction_type'] ?? '') || !in_array(strtolower($row['transaction_type']), ['deposit', 'withdrawal'])) {
            $errors[] = "Row {$row_num}: transaction_type must be deposit or withdrawal";
        }
        if (empty($row['amount'] ?? '') || !is_numeric($row['amount']) || $row['amount'] <= 0) {
            $errors[] = "Row {$row_num}: amount must be a positive number";
        }
        if (empty($row['transaction_date'] ?? '') || !$this->_is_valid_date($row['transaction_date'])) {
            $errors[] = "Row {$row_num}: transaction_date is required (YYYY-MM-DD)";
        }

        return $errors;
    }

    // ===== IMPORT METHODS =====

    /**
     * Import a single member row
     */
    private function _import_member($row, $admin_id) {
        $phone = preg_replace('/[^0-9]/', '', $row['phone'] ?? '');

        // Check duplicate by phone
        $existing = $this->db->where('phone', $phone)
                             ->where('deleted_at IS NULL', null, false)
                             ->get('members')->row();
        if ($existing) {
            return ['status' => 'skipped', 'message' => "Phone {$phone} already exists (Member: {$existing->member_code})"];
        }

        // Check duplicate by email
        if (!empty($row['email'])) {
            $existing_email = $this->db->where('email', $row['email'])
                                       ->where('deleted_at IS NULL', null, false)
                                       ->get('members')->row();
            if ($existing_email) {
                return ['status' => 'skipped', 'message' => "Email {$row['email']} already exists"];
            }
        }

        $member_data = [
            'first_name'     => trim($row['first_name'] ?? ''),
            'last_name'      => trim($row['last_name'] ?? ''),
            'phone'          => $phone,
            'email'          => !empty($row['email']) ? trim($row['email']) : null,
            'date_of_birth'  => !empty($row['date_of_birth']) ? $this->_parse_date($row['date_of_birth']) : null,
            'gender'         => !empty($row['gender']) ? strtolower(trim($row['gender'])) : null,
            'father_name'    => !empty($row['father_name']) ? trim($row['father_name']) : null,
            'occupation'     => !empty($row['occupation']) ? trim($row['occupation']) : null,
            'monthly_income' => !empty($row['monthly_income']) ? (float) $row['monthly_income'] : null,
            'address_line1'  => !empty($row['address_line1']) ? trim($row['address_line1']) : null,
            'address_line2'  => !empty($row['address_line2']) ? trim($row['address_line2']) : null,
            'city'           => !empty($row['city']) ? trim($row['city']) : null,
            'state'          => !empty($row['state']) ? trim($row['state']) : null,
            'pincode'        => !empty($row['pincode']) ? trim($row['pincode']) : null,
            'aadhaar_number' => !empty($row['aadhaar_number']) ? trim($row['aadhaar_number']) : null,
            'pan_number'     => !empty($row['pan_number']) ? strtoupper(trim($row['pan_number'])) : null,
            'bank_name'      => !empty($row['bank_name']) ? trim($row['bank_name']) : null,
            'bank_branch'    => !empty($row['bank_branch']) ? trim($row['bank_branch']) : null,
            'account_number' => !empty($row['account_number']) ? trim($row['account_number']) : null,
            'ifsc_code'      => !empty($row['ifsc_code']) ? strtoupper(trim($row['ifsc_code'])) : null,
            'account_holder_name' => !empty($row['account_holder_name']) ? trim($row['account_holder_name']) : null,
            'join_date'      => !empty($row['join_date']) ? $this->_parse_date($row['join_date']) : date('Y-m-d'),
            'membership_type' => !empty($row['membership_type']) ? strtolower(trim($row['membership_type'])) : 'regular',
            'nominee_name'   => !empty($row['nominee_name']) ? trim($row['nominee_name']) : null,
            'nominee_relation' => !empty($row['nominee_relation']) ? trim($row['nominee_relation']) : null,
            'nominee_phone'  => !empty($row['nominee_phone']) ? preg_replace('/[^0-9]/', '', $row['nominee_phone']) : null,
            'notes'          => !empty($row['notes']) ? trim($row['notes']) : null,
            'status'         => 'active',
            'created_by'     => $admin_id,
        ];

        $this->load->model('Member_model');
        $member_id = $this->Member_model->create_member($member_data);

        if ($member_id) {
            return ['status' => 'inserted', 'message' => 'OK', 'id' => $member_id];
        } else {
            // Capture DB error for debugging (check model's stored error first, then CI db)
            $err_msg = 'Failed to create member';
            if (!empty($this->Member_model->_last_db_error)) {
                $err_msg .= ': ' . $this->Member_model->_last_db_error;
            } else {
                $db_error = $this->db->error();
                if (!empty($db_error['message']) && $db_error['code'] != 0) {
                    $err_msg .= ': ' . $db_error['message'];
                }
            }
            return ['status' => 'error', 'message' => $err_msg];
        }
    }

    /**
     * Import a single loan row
     */
    private function _import_loan($row, $admin_id) {
        // Look up member
        $member = $this->db->where('member_code', trim($row['member_code']))
                           ->where('deleted_at IS NULL', null, false)
                           ->get('members')->row();
        if (!$member) {
            return ['status' => 'error', 'message' => "Member {$row['member_code']} not found"];
        }

        // Look up loan product
        $product = $this->db->where('id', (int) $row['loan_product_id'])->get('loan_products')->row();
        if (!$product) {
            return ['status' => 'error', 'message' => "Loan product ID {$row['loan_product_id']} not found"];
        }

        $principal = (float) $row['principal_amount'];
        $rate = (float) $row['interest_rate'];
        $tenure = (int) $row['tenure_months'];
        $interest_type = strtolower(trim($row['interest_type']));
        $disbursement_date = $this->_parse_date($row['disbursement_date']);
        $processing_fee = !empty($row['processing_fee']) ? (float) $row['processing_fee'] : 0;

        // Calculate EMI and totals
        if ($interest_type === 'flat') {
            $total_interest = $principal * ($rate / 100) * ($tenure / 12);
            $total_payable = $principal + $total_interest;
            $emi = $total_payable / $tenure;
        } else {
            // Reducing balance EMI calculation
            $monthly_rate = ($rate / 100) / 12;
            if ($monthly_rate > 0) {
                $emi = $principal * $monthly_rate * pow(1 + $monthly_rate, $tenure) / (pow(1 + $monthly_rate, $tenure) - 1);
            } else {
                $emi = $principal / $tenure;
            }
            $total_payable = $emi * $tenure;
            $total_interest = $total_payable - $principal;
        }

        $emi = round($emi, 2);
        $total_interest = round($total_interest, 2);
        $total_payable = round($total_payable, 2);

        // First EMI date
        if (!empty($row['first_emi_date'])) {
            $first_emi_date = $this->_parse_date($row['first_emi_date']);
        } else {
            $first_emi_date = date('Y-m-d', strtotime($disbursement_date . ' +1 month'));
        }

        // Last EMI date
        $last_emi_date = date('Y-m-d', strtotime($first_emi_date . ' +' . ($tenure - 1) . ' months'));

        $status = !empty($row['status']) ? strtolower(trim($row['status'])) : 'active';

        $this->db->trans_begin();

        try {
            // Helper: filter array to only keys that exist as columns in a table
            $filter_cols = function($table, $data) {
                $fields = $this->db->list_fields($table);
                return array_intersect_key($data, array_flip($fields));
            };

            // 1. Create loan application — use actual DB column names
            $app_data = $filter_cols('loan_applications', [
                'application_number'       => 'APP-IMP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5)),
                'member_id'                => $member->id,
                'loan_product_id'          => (int) $row['loan_product_id'],
                'requested_amount'         => $principal,
                'requested_tenure_months'  => $tenure,
                'requested_interest_rate'  => $rate,
                'approved_amount'          => $principal,
                'approved_tenure_months'   => $tenure,
                'approved_interest_rate'   => $rate,
                'purpose'                  => !empty($row['remarks']) ? trim($row['remarks']) : 'Imported via bulk import',
                'status'                   => 'admin_approved',
                'admin_approved_by'        => $admin_id,
                'admin_approved_at'        => date('Y-m-d H:i:s'),
                'application_date'         => $disbursement_date,
                'created_by'               => $admin_id,
                'created_at'               => date('Y-m-d H:i:s'),
            ]);
            $this->db->insert('loan_applications', $app_data);
            $app_id = $this->db->insert_id();

            if (!$app_id) {
                $err = $this->db->error();
                $this->db->trans_rollback();
                return ['status' => 'error', 'message' => 'Failed to create loan application: ' . ($err['message'] ?? 'Unknown error')];
            }

            // 2. Create the loan — filter to actual DB columns
            $loan_data = $filter_cols('loans', [
                'loan_number'            => 'LN-IMP-' . substr(uniqid(), -8),
                'loan_application_id'    => $app_id,
                'member_id'              => $member->id,
                'loan_product_id'        => (int) $row['loan_product_id'],
                'principal_amount'       => $principal,
                'interest_rate'          => $rate,
                'interest_type'          => $interest_type,
                'tenure_months'          => $tenure,
                'original_tenure_months' => $tenure,
                'emi_amount'             => $emi,
                'total_interest'         => $total_interest,
                'total_payable'          => $total_payable,
                'processing_fee'         => $processing_fee,
                'net_disbursement'       => $principal - $processing_fee,
                'outstanding_principal'  => $principal,
                'outstanding_interest'   => $total_interest,
                'outstanding_fine'       => 0,
                'total_amount_paid'      => 0,
                'total_principal_paid'   => 0,
                'total_interest_paid'    => 0,
                'total_fine_paid'        => 0,
                'disbursement_date'      => $disbursement_date,
                'first_emi_date'         => $first_emi_date,
                'last_emi_date'          => $last_emi_date,
                'status'                 => $status,
                'disbursement_mode'      => !empty($row['disbursement_mode']) ? $row['disbursement_mode'] : 'bank_transfer',
                'disbursement_reference' => !empty($row['disbursement_reference']) ? $row['disbursement_reference'] : null,
                'created_by'             => $admin_id,
                'created_at'             => date('Y-m-d H:i:s'),
            ]);
            $this->db->insert('loans', $loan_data);
            $loan_id = $this->db->insert_id();

            if (!$loan_id) {
                $err = $this->db->error();
                $this->db->trans_rollback();
                return ['status' => 'error', 'message' => 'Failed to create loan record: ' . ($err['message'] ?? 'Unknown error')];
            }

            // Generate proper loan number from the actual auto-increment ID
            $loan_number = 'LN' . date('Y') . str_pad($loan_id, 6, '0', STR_PAD_LEFT);
            $this->db->where('id', $loan_id)->update('loans', ['loan_number' => $loan_number]);

            // 3. Generate installment schedule
            $this->_generate_loan_installments($loan_id, $principal, $rate, $interest_type, $tenure, $emi, $first_emi_date);

            if ($this->db->trans_status() === FALSE) {
                $err = $this->db->error();
                $this->db->trans_rollback();
                return ['status' => 'error', 'message' => 'Transaction failed: ' . ($err['message'] ?? 'Unknown DB error')];
            }

            $this->db->trans_commit();
            return ['status' => 'inserted', 'message' => 'OK', 'id' => $loan_id];

        } catch (Exception $e) {
            $this->db->trans_rollback();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Import a single savings transaction row
     */
    private function _import_savings_transaction($row, $admin_id) {
        // Look up member
        $member = $this->db->where('member_code', trim($row['member_code']))
                           ->where('deleted_at IS NULL', null, false)
                           ->get('members')->row();
        if (!$member) {
            return ['status' => 'error', 'message' => "Member {$row['member_code']} not found"];
        }

        // Find the savings account
        $account = null;
        if (!empty($row['account_number'])) {
            $account = $this->db->where('account_number', trim($row['account_number']))
                                ->where('member_id', $member->id)
                                ->get('savings_accounts')->row();
        }

        if (!$account) {
            // Try to find by member_id + scheme_id
            $scheme_id = !empty($row['scheme_id']) ? (int) $row['scheme_id'] : null;
            if ($scheme_id) {
                $account = $this->db->where('member_id', $member->id)
                                    ->where('scheme_id', $scheme_id)
                                    ->where('status', 'active')
                                    ->get('savings_accounts')->row();
            }
        }

        if (!$account) {
            // Try to find any active savings account for this member
            $account = $this->db->where('member_id', $member->id)
                                ->where('status', 'active')
                                ->order_by('created_at', 'ASC')
                                ->get('savings_accounts')->row();
        }

        if (!$account) {
            return ['status' => 'error', 'message' => "No active savings account found for member {$row['member_code']}"];
        }

        $amount = (float) $row['amount'];
        $tx_type = strtolower(trim($row['transaction_type']));
        $tx_date = $this->_parse_date($row['transaction_date']);
        $payment_mode = !empty($row['payment_mode']) ? strtolower(trim($row['payment_mode'])) : 'cash';

        // Validate payment_mode
        $valid_modes = ['cash', 'bank_transfer', 'cheque', 'upi', 'auto', 'adjustment'];
        if (!in_array($payment_mode, $valid_modes)) {
            $payment_mode = 'cash';
        }

        // Use existing Savings_model record_payment
        $this->load->model('Savings_model');

        $payment_data = [
            'savings_account_id' => $account->id,
            'transaction_type' => $tx_type,
            'amount' => $amount,
            'payment_mode' => $payment_mode,
            'reference_number' => !empty($row['reference_number']) ? trim($row['reference_number']) : null,
            'transaction_date' => $tx_date,
            'remarks' => !empty($row['remarks']) ? trim($row['remarks']) : 'Imported via bulk import',
            'received_by' => $admin_id
        ];

        try {
            $tx_id = $this->Savings_model->record_payment($payment_data);
            if ($tx_id) {
                return ['status' => 'inserted', 'message' => 'OK', 'id' => $tx_id];
            } else {
                return ['status' => 'error', 'message' => 'Failed to record savings transaction'];
            }
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    // ===== HELPER METHODS =====

    /**
     * Generate loan installments
     */
    private function _generate_loan_installments($loan_id, $principal, $rate, $interest_type, $tenure, $emi, $first_emi_date) {
        $outstanding = $principal;
        $monthly_rate = ($rate / 100) / 12;

        for ($i = 1; $i <= $tenure; $i++) {
            $due_date = date('Y-m-d', strtotime($first_emi_date . ' +' . ($i - 1) . ' months'));

            if ($interest_type === 'flat') {
                $interest_amount = round(($principal * ($rate / 100)) / $tenure, 2);
                $principal_amount = round($emi - $interest_amount, 2);
            } else {
                $interest_amount = round($outstanding * $monthly_rate, 2);
                $principal_amount = round($emi - $interest_amount, 2);
            }

            // Last installment adjustment
            if ($i == $tenure) {
                $principal_amount = round($outstanding, 2);
                $emi_amount = $principal_amount + $interest_amount;
            } else {
                $emi_amount = $emi;
            }

            $outstanding_before = $outstanding;
            $outstanding -= $principal_amount;
            if ($outstanding < 0) $outstanding = 0;

            $this->db->insert('loan_installments', [
                'loan_id' => $loan_id,
                'installment_number' => $i,
                'due_date' => $due_date,
                'principal_amount' => $principal_amount,
                'interest_amount' => $interest_amount,
                'emi_amount' => $emi_amount,
                'outstanding_principal_before' => round($outstanding_before, 2),
                'outstanding_principal_after' => round($outstanding, 2),
                'status' => (strtotime($due_date) < time()) ? 'pending' : 'upcoming',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    }

    /**
     * Validate date format
     */
    private function _is_valid_date($date) {
        if (empty($date)) return false;
        // Try common formats
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if ($d && $d->format('Y-m-d') === $date) return true;

        $d = DateTime::createFromFormat('d/m/Y', $date);
        if ($d) return true;

        $d = DateTime::createFromFormat('d-m-Y', $date);
        if ($d) return true;

        $d = DateTime::createFromFormat('m/d/Y', $date);
        if ($d) return true;

        return false;
    }

    /**
     * Parse date to Y-m-d format
     */
    private function _parse_date($date) {
        if (empty($date)) return date('Y-m-d');

        // Already in correct format?
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if ($d && $d->format('Y-m-d') === $date) return $date;

        // Try DD/MM/YYYY
        $d = DateTime::createFromFormat('d/m/Y', $date);
        if ($d) return $d->format('Y-m-d');

        // Try DD-MM-YYYY
        $d = DateTime::createFromFormat('d-m-Y', $date);
        if ($d) return $d->format('Y-m-d');

        // Try MM/DD/YYYY
        $d = DateTime::createFromFormat('m/d/Y', $date);
        if ($d) return $d->format('Y-m-d');

        // Try Excel numeric date
        if (is_numeric($date)) {
            $unix = ($date - 25569) * 86400;
            return date('Y-m-d', $unix);
        }

        // Fallback: try strtotime
        $ts = strtotime($date);
        if ($ts) return date('Y-m-d', $ts);

        return date('Y-m-d');
    }
}
