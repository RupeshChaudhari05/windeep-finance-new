<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Loan_model - Loan Management
 */
class Loan_model extends MY_Model {
    
    protected $table = 'loans';
    protected $primary_key = 'id';
    
    /**
     * Generate Loan Number
     * LOAN-8 FIX: Generate from actual loan_id (post-insert) to prevent race condition
     */
    public function generate_loan_number($loan_id = null) {
        $prefix = 'LN';
        $year = date('Y');
        
        if ($loan_id) {
            // Race-safe: use actual auto-increment ID
            return $prefix . $year . str_pad($loan_id, 6, '0', STR_PAD_LEFT);
        }
        
        // Fallback (should not be used in concurrent scenarios)
        $max = $this->db->query('SELECT MAX(id) as max_id FROM ' . $this->table . ' FOR UPDATE')
                        ->row();
        
        $next = ($max->max_id ?? 0) + 1;
        
        return $prefix . $year . str_pad($next, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Generate Application Number
     */
    public function generate_application_number() {
        $prefix = 'APP';
        $year = date('Y');
        $month = date('m');
        
        $max = $this->db->select_max('id')
                        ->get('loan_applications')
                        ->row();
        
        $next = ($max->id ?? 0) + 1;
        
        return $prefix . $year . $month . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
    
    /**
     * Create Loan Application
     */
    public function create_application($data) {
        $data['application_number'] = $this->generate_application_number();
        $data['application_date'] = date('Y-m-d');
        $data['status'] = 'pending';
        $data['created_at'] = date('Y-m-d H:i:s');
        
        // Set expiry date (30 days from application)
        $data['expiry_date'] = date('Y-m-d', safe_timestamp('+30 days'));
        
        // Capture member's current financial position
        $this->load->model('Member_model');
        $member = $this->Member_model->get_member_details($data['member_id']);
        
        if ($member) {
            $data['member_savings_balance'] = $member->savings_summary->current_balance ?? 0;
            $data['member_existing_loans'] = $this->get_active_loan_count($data['member_id']);
            $data['member_existing_loan_balance'] = $member->loan_summary->outstanding_principal ?? 0;
        }
        
        $this->db->insert('loan_applications', $data);

        // Check insert result and provide helpful logging on failure
        if ($this->db->affected_rows() > 0) {
            return $this->db->insert_id();
        } else {
            $err = $this->db->error();
            log_message('error', 'Loan_model::create_application DB error: ' . ($err['message'] ?? json_encode($err)) . ' | Data: ' . json_encode($data));
            return false;
        }
    }
    
    /**
     * Add Guarantor to Application
     */
    public function add_guarantor($application_id, $guarantor_member_id, $guarantee_amount, $relationship = null) {
        // Generate a consent token for secure email links
        $token = bin2hex(random_bytes(16));

        $insert = [
            'loan_application_id' => $application_id,
            'guarantor_member_id' => $guarantor_member_id,
            'guarantee_amount' => $guarantee_amount,
            'relationship' => $relationship,
            'consent_status' => 'pending',
            'consent_token' => $token,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->db->insert('loan_guarantors', $insert);
        if ($this->db->affected_rows() > 0) {
            return ['id' => $this->db->insert_id(), 'token' => $token];
        }

        return false;
    }
    
    /**
     * Update Guarantor Consent
     */
    public function update_guarantor_consent($guarantor_id, $status, $remarks = null) {
        $data = [
            'consent_status' => $status,
            'consent_date' => date('Y-m-d H:i:s'),
            'consent_ip' => $this->input->ip_address(),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($status === 'rejected') {
            $data['rejection_reason'] = $remarks;
        } else {
            $data['consent_remarks'] = $remarks;
        }
        
        $res = $this->db->where('id', $guarantor_id)
                        ->update('loan_guarantors', $data);

        return $res;
    }

    /**
     * Count accepted guarantors for an application
     */
    public function get_accepted_guarantor_count($application_id) {
        return (int) $this->db->where('loan_application_id', $application_id)
                              ->where('consent_status', 'accepted')
                              ->count_all_results('loan_guarantors');
    }
    
    /**
     * Admin Approve/Revise Application
     * Bug #2 Fix: Validate loan-to-savings ratio
     * @param bool $force_savings  When true, admin-overrides all savings checks
     */
    public function admin_approve($application_id, $data, $admin_id, $force_savings = false) {
        // Bug #2 Fix: Get member savings balance and enforce ratio
        $application = $this->db->where('id', $application_id)
                                ->get('loan_applications')
                                ->row();
        
        if (!$application) {
            throw new Exception('Application not found');
        }

        // Use the product selected by admin (may differ from what was on the application)
        $product_id = !empty($data['loan_product_id']) ? $data['loan_product_id'] : $application->loan_product_id;
        
        // Get product requirements
        $product = null;
        if ($product_id) {
            $product = $this->db->where('id', $product_id)
                                ->get('loan_products')
                                ->row();
        }
        
        // Savings checks — skipped when admin explicitly overrides
        if (!$force_savings) {
            if ($product && !empty($product->min_savings_balance)) {
                // Get member's current savings balance
                $this->load->model('Member_model');
                $member = $this->Member_model->get_member_details($application->member_id);
                $savings_balance = $member->savings_summary->current_balance ?? 0;
                
                if ($savings_balance < $product->min_savings_balance) {
                    throw new Exception('Member savings balance (' . format_amount($savings_balance) . ') is below minimum requirement (' . format_amount($product->min_savings_balance) . ')');
                }
            }
            
            // Check loan-to-savings ratio (if configured)
            if ($product && !empty($product->max_loan_to_savings_ratio)) {
                $this->load->model('Member_model');
                $member = $this->Member_model->get_member_details($application->member_id);
                $savings_balance = $member->savings_summary->current_balance ?? 0;
                
                $max_loan = $savings_balance * $product->max_loan_to_savings_ratio;
                
                if ($data['approved_amount'] > $max_loan) {
                    throw new Exception('Approved amount (' . format_amount($data['approved_amount']) . ') exceeds maximum based on savings ratio (' . format_amount($max_loan) . ')');
                }
            }
        }
        
        $update = [
            'loan_product_id' => $product_id,
            'approved_amount' => $data['approved_amount'],
            'approved_tenure_months' => $data['approved_tenure_months'],
            'approved_interest_rate' => $data['approved_interest_rate'],
            'revision_remarks' => $data['remarks'] ?? null,
            'revised_at' => date('Y-m-d H:i:s'),
            'revised_by' => $admin_id,
            'admin_approved_at' => date('Y-m-d H:i:s'),
            'admin_approved_by' => $admin_id,
            'status' => 'member_review',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->where('id', $application_id)
                        ->update('loan_applications', $update);
    }

    /**
     * Request modification from member (admin)
     */
    public function request_modification($application_id, $remarks, $admin_id, $proposed = []) {
        $update = [
            'revision_remarks' => $remarks,
            'revised_at' => date('Y-m-d H:i:s'),
            'revised_by' => $admin_id,
            'status' => 'needs_revision',
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if (!empty($proposed['approved_amount'])) {
            $update['approved_amount'] = $proposed['approved_amount'];
        }
        if (!empty($proposed['approved_tenure_months'])) {
            $update['approved_tenure_months'] = $proposed['approved_tenure_months'];
        }
        if (!empty($proposed['approved_interest_rate'])) {
            $update['approved_interest_rate'] = $proposed['approved_interest_rate'];
        }

        return $this->db->where('id', $application_id)
                        ->update('loan_applications', $update);
    }
    
    /**
     * Member Approve Revised Terms
     */
    public function member_approve($application_id) {
        return $this->db->where('id', $application_id)
                        ->update('loan_applications', [
                            'status' => 'member_approved',
                            'member_approved_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
    }
    
    /**
     * Reject Application
     */
    public function reject_application($application_id, $reason, $rejected_by) {
        return $this->db->where('id', $application_id)
                        ->update('loan_applications', [
                            'status' => 'rejected',
                            'rejection_reason' => $reason,
                            'rejected_at' => date('Y-m-d H:i:s'),
                            'rejected_by' => $rejected_by,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
    }
    
    /**
     * Disburse Loan
     */
    /**
     * Disburse Loan
     * Bug #1 Fix: Validate disbursement and first EMI dates
     */
    public function disburse_loan($application_id, $disbursement_data, $admin_id) {
        $this->db->trans_begin();
        
        try {
            $application = $this->db->where('id', $application_id)
                                    ->get('loan_applications')
                                    ->row();
            
            if (!$application || $application->status !== 'member_approved') {
                throw new Exception('Application not ready for disbursement');
            }
            
            // Bug #1 Fix: Validate dates
            $disbursement_date = strtotime($disbursement_data['disbursement_date']);
            $first_emi_date = strtotime($disbursement_data['first_emi_date']);
            $today = strtotime(date('Y-m-d'));
            
            if ($disbursement_date > $today) {
                throw new Exception('Disbursement date cannot be in the future');
            }
            
            if ($first_emi_date <= $disbursement_date) {
                throw new Exception('First EMI date must be after disbursement date');
            }
            
            $days_diff = ($first_emi_date - $disbursement_date) / 86400;
            if ($days_diff < 7) {
                throw new Exception('First EMI date must be at least 7 days after disbursement');
            }
            
            if ($days_diff > 60) {
                throw new Exception('First EMI date cannot be more than 60 days after disbursement');
            }
            
            // Get loan product
            $product = $this->db->where('id', $application->loan_product_id)
                                ->get('loan_products')
                                ->row();
            
            // Calculate loan details
            $principal = $application->approved_amount;
            $rate = $application->approved_interest_rate;
            $tenure = $application->approved_tenure_months;
            $interest_type = $product->interest_type;
            
            // Calculate EMI and totals
            $calc = $this->calculate_emi($principal, $rate, $tenure, $interest_type);
            
            // Processing fee
            $processing_fee = 0;
            if ($product->processing_fee_type === 'percentage') {
                $processing_fee = $principal * ($product->processing_fee_value / 100);
            } else {
                $processing_fee = $product->processing_fee_value;
            }
            
            $net_disbursement = $principal - $processing_fee;
            
            // Create loan record — loan_number generated post-insert (LOAN-8 fix)
            $loan_data = [
                'loan_number' => 'TEMP-' . uniqid(), // Temporary, replaced after insert
                'loan_application_id' => $application_id,
                'member_id' => $application->member_id,
                'loan_product_id' => $application->loan_product_id,
                'principal_amount' => $principal,
                'interest_rate' => $rate,
                'interest_type' => $interest_type,
                'tenure_months' => $tenure,
                'emi_amount' => $calc['emi'],
                'total_interest' => $calc['total_interest'],
                'total_payable' => $calc['total_payable'],
                'processing_fee' => $processing_fee,
                'net_disbursement' => $net_disbursement,
                'outstanding_principal' => $principal,
                'outstanding_interest' => $calc['total_interest'],
                'disbursement_date' => $disbursement_data['disbursement_date'],
                'first_emi_date' => $disbursement_data['first_emi_date'],
                'last_emi_date' => date('Y-m-d', safe_timestamp($disbursement_data['first_emi_date'] . ' +' . ($tenure - 1) . ' months')),
                'status' => 'active',
                'disbursement_mode' => $disbursement_data['disbursement_mode'],
                'disbursement_reference' => $disbursement_data['reference_number'] ?? null,
                'disbursed_by' => $admin_id,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert($this->table, $loan_data);
            $loan_id = $this->db->insert_id();
            
            // LOAN-8 FIX: Generate race-safe loan number from actual ID
            $loan_number = $this->generate_loan_number($loan_id);
            $this->db->where('id', $loan_id)
                     ->update($this->table, ['loan_number' => $loan_number]);
            
            // Generate installment schedule
            $this->generate_installment_schedule($loan_id, $principal, $rate, $tenure, $interest_type, $calc['emi'], $disbursement_data['first_emi_date']);
            
            // Update application status
            $this->db->where('id', $application_id)
                     ->update('loan_applications', [
                         'status' => 'disbursed',
                         'updated_at' => date('Y-m-d H:i:s')
                     ]);
            
            // Update guarantors with loan_id
            $this->db->where('loan_application_id', $application_id)
                     ->update('loan_guarantors', ['loan_id' => $loan_id]);
            
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return false;
            }
            
            $this->db->trans_commit();
            return $loan_id;
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            throw $e;
        }
    }
    
    /**
     * Calculate EMI
     * Bug #5 Fix: Consistent flat interest calculation
     */
    public function calculate_emi($principal, $rate, $tenure, $type = 'reducing') {
        $monthly_rate = ($rate / 12) / 100;
        
        if ($type === 'flat') {
            // Flat interest: Total interest = P × R × T (where T is in years)
            $years = $tenure / 12;
            $total_interest = $principal * ($rate / 100) * $years;
            $total_payable = $principal + $total_interest;
            $emi = $total_payable / $tenure;
        } else {
            // Reducing balance
            if ($monthly_rate == 0) {
                $emi = $principal / $tenure;
            } else {
                $emi = $principal * $monthly_rate * pow(1 + $monthly_rate, $tenure) / (pow(1 + $monthly_rate, $tenure) - 1);
            }
            
            $total_payable = $emi * $tenure;
            $total_interest = $total_payable - $principal;
        }
        
        return [
            'emi' => round($emi, 2),
            'total_interest' => round($total_interest, 2),
            'total_payable' => round($total_payable, 2)
        ];
    }
    
    /**
     * Generate Installment Schedule
     * Bug #4 Fix: Proper rounding adjustment in last installment
     * Bug #5 Fix: Consistent flat interest calculation
     */
    public function generate_installment_schedule($loan_id, $principal, $rate, $tenure, $type, $emi, $first_emi_date) {
        $monthly_rate = ($rate / 12) / 100;
        $balance = $principal;

        // Respect global "fixed due day" setting if enabled: compute first due date as the next occurrence
        // of the configured day-of-month. For example, if fixed_due_day=10 and first_emi_date is 20-Jan,
        // the first installment will be 10-Feb.
        $this->load->model('Setting_model');
        $force_fixed = $this->Setting_model->get_setting('force_fixed_due_day', false);
        $fixed_day = (int) $this->Setting_model->get_setting('fixed_due_day', 0);

        if ($force_fixed && $fixed_day >= 1 && $fixed_day <= 31) {
            $d = new DateTime($first_emi_date);
            // Candidate in same month
            $year = (int) $d->format('Y');
            $month = (int) $d->format('n');
            $last_day = (int) $d->format('t');
            $day = min($fixed_day, $last_day);
            $candidate = DateTime::createFromFormat('Y-n-j', "$year-$month-$day");

            // If candidate is on or before the supplied date, move to next month
            if ($candidate <= $d) {
                $candidate->modify('+1 month');
                $year = (int) $candidate->format('Y');
                $month = (int) $candidate->format('n');
                $last_day = (int) date('t', strtotime("$year-$month-01"));
                $day = min($fixed_day, $last_day);
                $candidate = DateTime::createFromFormat('Y-n-j', "$year-$month-$day");
            }

            $due_date = $candidate;
        } else {
            $due_date = new DateTime($first_emi_date);
        }

        $total_principal_allocated = 0;
        
        for ($i = 1; $i <= $tenure; $i++) {
            if ($type === 'flat') {
                // Use same formula as calculate_emi for consistency
                $years = $tenure / 12;
                $total_interest = $principal * ($rate / 100) * $years;
                $interest = $total_interest / $tenure;
                $principal_part = $principal / $tenure;
            } else {
                $interest = $balance * $monthly_rate;
                $principal_part = $emi - $interest;
            }
            
            $outstanding_before = $balance;
            
            // Last installment adjustment - allocate remaining principal exactly
            if ($i === $tenure) {
                $principal_part = $principal - $total_principal_allocated;
                $balance = 0;
            } else {
                $principal_part = round($principal_part, 2);
                $balance -= $principal_part;
                $total_principal_allocated += $principal_part;
            }
            
            $interest = round($interest, 2);
            $emi_amount = $principal_part + $interest;
            
            $this->db->insert('loan_installments', [
                'loan_id' => $loan_id,
                'installment_number' => $i,
                'due_date' => $due_date->format('Y-m-d'),
                'principal_amount' => $principal_part,
                'interest_amount' => $interest,
                'emi_amount' => round($emi_amount, 2),
                'outstanding_principal_before' => round($outstanding_before, 2),
                'outstanding_principal_after' => round(max(0, $balance), 2),
                'status' => $i === 1 ? 'pending' : 'upcoming',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $due_date->modify('+1 month');
        }
        
        return true;
    }
    
    /**
     * Record Loan Payment
     * Supports regular EMI, interest-only, and other payment types.
     * For interest_only: pays only interest portion, skips principal, extends tenure.
     * 
     * FIXES APPLIED:
     * - LOAN-1:  SELECT FOR UPDATE to prevent concurrent payment corruption
     * - LOAN-2:  Fixed status check to use valid ENUM values only
     * - LOAN-10: Validate payment amount is positive
     * - LOAN-11: Duplicate payment detection (same loan, amount, date within 60s)
     */
    public function record_payment($data) {
        // LOAN-10 FIX: Validate payment amount
        if (empty($data['total_amount']) || $data['total_amount'] <= 0) {
            throw new Exception('Payment amount must be greater than zero');
        }
        
        $this->db->trans_begin();
        
        try {
            // LOAN-1 FIX: Lock loan row to prevent concurrent payment corruption
            $loan = $this->db->query(
                'SELECT * FROM ' . $this->table . ' WHERE id = ? FOR UPDATE',
                [$data['loan_id']]
            )->row();
            
            // LOAN-2 FIX: Only check valid DB ENUM values ('active','npa' accept payments)
            if (!$loan || !in_array($loan->status, ['active', 'npa'])) {
                throw new Exception('Loan not found or not active. Status: ' . ($loan ? $loan->status : 'not found'));
            }
            
            // LOAN-11 FIX: Duplicate payment detection (same loan, same amount, within 60 seconds)
            $recent_dup = $this->db->where('loan_id', $data['loan_id'])
                                   ->where('total_amount', $data['total_amount'])
                                   ->where('is_reversed', 0)
                                   ->where('created_at >', date('Y-m-d H:i:s', time() - 60))
                                   ->count_all_results('loan_payments');
            if ($recent_dup > 0) {
                throw new Exception('Duplicate payment detected. A payment of the same amount was recorded within the last 60 seconds.');
            }
            
            // Generate payment code
            $data['payment_code'] = 'PAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            $data['payment_date'] = $data['payment_date'] ?? date('Y-m-d');
            
            // ─── Interest-Only Payment Mode ───
            // When member can't afford full EMI, pay only interest and defer principal
            if (isset($data['payment_type']) && $data['payment_type'] === 'interest_only') {
                return $this->process_interest_only_payment($data, $loan);
            }
            
            // ─── Regular Payment Allocation (RBI-compliant order) ───
            // RBI Guidelines: Interest → Principal → Fine
            $amount = $data['total_amount'];
            $principal_paid = 0;
            $interest_paid = 0;
            $fine_paid = 0;
            
            // If a specific installment is targeted, allocate based on installment amounts
            // This prevents constraint violations (interest_paid <= interest_amount, etc.)
            $use_loan_level = true; // LOAN-14 FIX: Replace goto with flag
            
            if (!empty($data['installment_id'])) {
                $target_inst = $this->db->where('id', $data['installment_id'])
                                        ->get('loan_installments')
                                        ->row();
                
                // LOAN-12 FIX: Verify installment belongs to this loan
                if ($target_inst && (int)$target_inst->loan_id !== (int)$data['loan_id']) {
                    throw new Exception('Installment #' . $data['installment_id'] . ' does not belong to loan #' . $data['loan_id']);
                }
                
                if ($target_inst) {
                    $use_loan_level = false;
                    $inst_interest_pending = max(0, $target_inst->interest_amount - ($target_inst->interest_paid ?? 0));
                    $inst_principal_pending = max(0, $target_inst->principal_amount - ($target_inst->principal_paid ?? 0));
                    
                    // Pay installment interest first
                    if ($inst_interest_pending > 0 && $amount > 0) {
                        $interest_paid = min($inst_interest_pending, $amount);
                        $amount -= $interest_paid;
                    }
                    // Pay installment principal next
                    if ($inst_principal_pending > 0 && $amount > 0) {
                        $principal_paid = min($inst_principal_pending, $amount);
                        $amount -= $principal_paid;
                    }
                    // Pay fine last (from loan-level outstanding)
                    if ($loan->outstanding_fine > 0 && $amount > 0) {
                        $fine_paid = min($loan->outstanding_fine, $amount);
                        $amount -= $fine_paid;
                    }
                }
                // If installment not found, fall through to loan-level allocation
            }
            
            if ($use_loan_level) {
                // Loan-level allocation when no specific installment
                // Pay interest first (RBI compliance)
                if ($loan->outstanding_interest > 0 && $amount > 0) {
                    $interest_paid = min($loan->outstanding_interest, $amount);
                    $amount -= $interest_paid;
                }
                
                // Pay principal next
                if ($loan->outstanding_principal > 0 && $amount > 0) {
                    $principal_paid = min($loan->outstanding_principal, $amount);
                    $amount -= $principal_paid;
                }
                
                // Pay fine last
                if ($loan->outstanding_fine > 0 && $amount > 0) {
                    $fine_paid = min($loan->outstanding_fine, $amount);
                    $amount -= $fine_paid;
                }
            }
            
            $data['principal_component'] = $principal_paid;
            $data['interest_component'] = $interest_paid;
            $data['fine_component'] = $fine_paid;
            $data['excess_amount'] = $amount;
            
            // New outstanding amounts
            $new_outstanding_principal = $loan->outstanding_principal - $principal_paid;
            $new_outstanding_interest = $loan->outstanding_interest - $interest_paid;
            $new_outstanding_fine = $loan->outstanding_fine - $fine_paid;
            
            $data['outstanding_principal_after'] = $new_outstanding_principal;
            $data['outstanding_interest_after'] = $new_outstanding_interest;
            $data['created_at'] = date('Y-m-d H:i:s');
            
            // Insert payment
            $this->db->insert('loan_payments', $data);
            $payment_id = $this->db->insert_id();
            
            // Update loan
            $loan_update = [
                'outstanding_principal' => $new_outstanding_principal,
                'outstanding_interest' => $new_outstanding_interest,
                'outstanding_fine' => $new_outstanding_fine,
                'total_amount_paid' => $loan->total_amount_paid + $data['total_amount'],
                'total_principal_paid' => $loan->total_principal_paid + $principal_paid,
                'total_interest_paid' => $loan->total_interest_paid + $interest_paid,
                'total_fine_paid' => $loan->total_fine_paid + $fine_paid,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Check if loan is closed
            if ($new_outstanding_principal <= 0) {
                $loan_update['status'] = 'closed';
                $loan_update['closure_date'] = date('Y-m-d');
                $loan_update['closure_type'] = $data['payment_type'] === 'foreclosure' ? 'foreclosure' : 'regular';
                
                // Release guarantors
                $this->release_guarantors($loan->id);
            }
            
            $this->db->where('id', $loan->id)
                     ->update($this->table, $loan_update);
            
            // Update installment if provided
            if (!empty($data['installment_id'])) {
                $this->update_installment_payment($data['installment_id'], $principal_paid, $interest_paid, $fine_paid);
            }
            
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
     * Update Installment Payment
     * LOAN-9 FIX: Added SELECT FOR UPDATE to prevent concurrent partial payment corruption
     */
    private function update_installment_payment($installment_id, $principal, $interest, $fine) {
        // LOAN-9 FIX: Lock installment row within the active transaction
        $installment = $this->db->query(
            'SELECT * FROM loan_installments WHERE id = ? FOR UPDATE',
            [$installment_id]
        )->row();
        
        if (!$installment) return false;
        
        // Cap paid amounts to installment limits to satisfy database constraints
        // (interest_paid <= interest_amount, principal_paid <= principal_amount)
        $max_interest = max(0, $installment->interest_amount - ($installment->interest_paid ?? 0));
        $max_principal = max(0, $installment->principal_amount - ($installment->principal_paid ?? 0));
        $actual_interest = min($interest, $max_interest);
        $actual_principal = min($principal, $max_principal);
        
        $new_principal_paid = ($installment->principal_paid ?? 0) + $actual_principal;
        $new_interest_paid = ($installment->interest_paid ?? 0) + $actual_interest;
        $new_fine_paid = ($installment->fine_paid ?? 0) + $fine;
        $new_total_paid = $new_principal_paid + $new_interest_paid;
        
        $status = 'partial';
        if ($new_total_paid >= $installment->emi_amount) {
            $status = 'paid';
        }
        
        // Check if late
        $is_late = (safe_timestamp(date('Y-m-d')) > safe_timestamp($installment->due_date));
        $days_late = 0;
        if ($is_late) {
            $days_late = floor((safe_timestamp(date('Y-m-d')) - safe_timestamp($installment->due_date)) / 86400);
        }
        
        return $this->db->where('id', $installment_id)
                        ->update('loan_installments', [
                            'principal_paid' => $new_principal_paid,
                            'interest_paid' => $new_interest_paid,
                            'fine_paid' => $new_fine_paid,
                            'total_paid' => $new_total_paid + $new_fine_paid,
                            'status' => $status,
                            'paid_date' => date('Y-m-d'),
                            'is_late' => $is_late ? 1 : 0,
                            'days_late' => $days_late,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
    }
    
    /**
     * Process Interest-Only Payment
     * 
     * When a member cannot afford the full EMI, this method:
     * 1. Pays only the interest portion of the current installment
     * 2. Marks the installment as 'interest_only' 
     * 3. Defers the principal to a NEW installment at the end of the schedule
     * 4. Extends loan tenure by 1 month
     * 
     * Example: EMI = ₹100 (Interest ₹30 + Principal ₹70)
     *          Member pays ₹30 → Interest paid, principal deferred, tenure +1
     * 
     * @param array $data Payment data with loan_id, installment_id, total_amount, etc.
     * @param object $loan The loan object
     * @return int|false Payment ID on success
     */
    private function process_interest_only_payment($data, $loan) {
        // Installment is required for interest-only payment
        if (empty($data['installment_id'])) {
            throw new Exception('Installment must be specified for interest-only payment');
        }
        
        $installment = $this->db->where('id', $data['installment_id'])
                                ->get('loan_installments')
                                ->row();
        
        if (!$installment) {
            throw new Exception('Installment not found');
        }
        
        if (!in_array($installment->status, ['pending', 'overdue', 'partial'])) {
            throw new Exception('Installment is not eligible for interest-only payment (status: ' . $installment->status . ')');
        }
        
        // Check tenure extension limits
        $extensions_used = $loan->tenure_extensions ?? 0;
        $max_extensions = $this->get_max_tenure_extensions($loan);
        
        if ($extensions_used >= $max_extensions) {
            throw new Exception("Maximum tenure extensions ({$max_extensions}) reached. Interest-only payment not allowed.");
        }
        
        // Calculate interest portion for this installment
        $interest_due = $installment->interest_amount - $installment->interest_paid;
        $amount = $data['total_amount'];
        
        if ($amount < $interest_due) {
            throw new Exception('Payment amount (₹' . number_format($amount, 2) . ') is less than interest due (₹' . number_format($interest_due, 2) . '). Minimum payment must cover the interest.');
        }
        
        // Allocate: Interest only from this installment
        $interest_paid = $interest_due;
        $remaining = $amount - $interest_paid;
        
        // Any excess goes towards fine if applicable
        $fine_paid = 0;
        if ($loan->outstanding_fine > 0 && $remaining > 0) {
            $fine_paid = min($loan->outstanding_fine, $remaining);
            $remaining -= $fine_paid;
        }
        
        // Principal is NOT paid - it's deferred
        $principal_paid = 0;
        $deferred_principal = $installment->principal_amount - $installment->principal_paid;
        
        // Set payment components
        $data['principal_component'] = $principal_paid;
        $data['interest_component'] = $interest_paid;
        $data['fine_component'] = $fine_paid;
        $data['excess_amount'] = $remaining;
        
        // Outstanding amounts - principal stays same, only interest decreases
        $new_outstanding_principal = $loan->outstanding_principal; // Principal unchanged
        $new_outstanding_interest = $loan->outstanding_interest - $interest_paid;
        $new_outstanding_fine = $loan->outstanding_fine - $fine_paid;
        
        $data['outstanding_principal_after'] = $new_outstanding_principal;
        $data['outstanding_interest_after'] = $new_outstanding_interest;
        $data['narration'] = ($data['narration'] ?? '') . ' [Interest-only: Principal ₹' . number_format($deferred_principal, 2) . ' deferred]';
        $data['created_at'] = date('Y-m-d H:i:s');
        
        // Insert payment record
        $this->db->insert('loan_payments', $data);
        $payment_id = $this->db->insert_id();
        
        // Mark installment as interest_only
        $is_late = (safe_timestamp(date('Y-m-d')) > safe_timestamp($installment->due_date));
        $days_late = 0;
        if ($is_late) {
            $days_late = floor((safe_timestamp(date('Y-m-d')) - safe_timestamp($installment->due_date)) / 86400);
        }
        
        $this->db->where('id', $installment->id)
                 ->update('loan_installments', [
                     'interest_paid' => $installment->interest_paid + $interest_paid,
                     'fine_paid' => $installment->fine_paid + $fine_paid,
                     'total_paid' => $installment->total_paid + $interest_paid + $fine_paid,
                     'status' => 'interest_only',
                     'paid_date' => date('Y-m-d'),
                     'is_late' => $is_late ? 1 : 0,
                     'days_late' => $days_late,
                     'deferred_principal' => $deferred_principal,
                     'remarks' => 'Interest-only payment. Principal ₹' . number_format($deferred_principal, 2) . ' deferred to extended installment.',
                     'updated_at' => date('Y-m-d H:i:s')
                 ]);
        
        // Extend tenure: add new installment at end of schedule
        $this->extend_tenure_for_deferred_principal($loan, $installment, $deferred_principal);
        
        // Update loan totals
        $new_tenure = $loan->tenure_months + 1;
        $loan_update = [
            'outstanding_interest' => $new_outstanding_interest,
            'outstanding_fine' => $new_outstanding_fine,
            'total_amount_paid' => $loan->total_amount_paid + $data['total_amount'],
            'total_interest_paid' => $loan->total_interest_paid + $interest_paid,
            'total_fine_paid' => $loan->total_fine_paid + $fine_paid,
            'tenure_months' => $new_tenure,
            'tenure_extensions' => $extensions_used + 1,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Update last EMI date
        $last_installment = $this->db->where('loan_id', $loan->id)
                                     ->order_by('installment_number', 'DESC')
                                     ->limit(1)
                                     ->get('loan_installments')
                                     ->row();
        if ($last_installment) {
            $loan_update['last_emi_date'] = $last_installment->due_date;
        }
        
        // Also update outstanding_interest to add interest for the new installment
        $new_installment_interest = $this->get_last_added_installment_interest($loan->id);
        if ($new_installment_interest > 0) {
            $loan_update['outstanding_interest'] = $new_outstanding_interest + $new_installment_interest;
            $loan_update['total_interest'] = $loan->total_interest + $new_installment_interest;
            $loan_update['total_payable'] = $loan->total_payable + $new_installment_interest;
        }
        
        $this->db->where('id', $loan->id)
                 ->update($this->table, $loan_update);
        
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        }
        
        $this->db->trans_commit();
        return $payment_id;
    }
    
    /**
     * Extend Tenure for Deferred Principal
     * 
     * Adds a new installment at the end of the loan schedule for the deferred principal.
     * The new installment includes interest calculated on the deferred principal.
     * 
     * @param object $loan The loan object
     * @param object $original_installment The installment whose principal was deferred
     * @param float $deferred_principal The principal amount being deferred
     */
    private function extend_tenure_for_deferred_principal($loan, $original_installment, $deferred_principal) {
        // Get the last installment to determine next installment number and due date
        $last_installment = $this->db->where('loan_id', $loan->id)
                                     ->order_by('installment_number', 'DESC')
                                     ->limit(1)
                                     ->get('loan_installments')
                                     ->row();
        
        $new_installment_number = $last_installment->installment_number + 1;
        
        // Next due date = last installment due date + 1 month
        $next_due_date = new DateTime($last_installment->due_date);
        $next_due_date->modify('+1 month');
        
        // Calculate interest for the new installment
        $monthly_rate = ($loan->interest_rate / 12) / 100;
        
        if ($loan->interest_type === 'flat') {
            // Flat: same interest per month as original schedule
            $interest_for_new = $original_installment->interest_amount;
        } else {
            // Reducing balance: interest on the outstanding principal at that point
            // The deferred principal will still be in outstanding, so calculate on it
            $interest_for_new = round($deferred_principal * $monthly_rate, 2);
        }
        
        $emi_for_new = round($deferred_principal + $interest_for_new, 2);
        
        // Outstanding principal context for the new installment
        $outstanding_before = $last_installment->outstanding_principal_after + $deferred_principal;
        $outstanding_after = $last_installment->outstanding_principal_after; // After paying the deferred principal
        
        $this->db->insert('loan_installments', [
            'loan_id' => $loan->id,
            'installment_number' => $new_installment_number,
            'due_date' => $next_due_date->format('Y-m-d'),
            'principal_amount' => $deferred_principal,
            'interest_amount' => $interest_for_new,
            'emi_amount' => $emi_for_new,
            'outstanding_principal_before' => round($outstanding_before, 2),
            'outstanding_principal_after' => round(max(0, $outstanding_after), 2),
            'status' => 'upcoming',
            'is_extension' => 1,
            'extended_from_installment' => $original_installment->id,
            'remarks' => 'Extended installment for deferred principal from installment #' . $original_installment->installment_number,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return $this->db->insert_id();
    }
    
    /**
     * Get interest amount of the last added (extension) installment for a loan
     */
    private function get_last_added_installment_interest($loan_id) {
        $last = $this->db->where('loan_id', $loan_id)
                         ->where('is_extension', 1)
                         ->order_by('installment_number', 'DESC')
                         ->limit(1)
                         ->get('loan_installments')
                         ->row();
        return $last ? (float)$last->interest_amount : 0;
    }
    
    /**
     * Get Maximum Tenure Extensions Allowed for a Loan
     * 
     * Checks loan product setting first, then falls back to system setting.
     * 
     * @param object $loan The loan object
     * @return int Maximum extensions allowed
     */
    private function get_max_tenure_extensions($loan) {
        // Check loan product level override
        $product = $this->db->where('id', $loan->loan_product_id)
                            ->get('loan_products')
                            ->row();
        
        if ($product && isset($product->max_interest_only_months) && $product->max_interest_only_months > 0) {
            return (int)$product->max_interest_only_months;
        }
        
        // Fall back to loan level setting
        if (isset($loan->max_tenure_extensions) && $loan->max_tenure_extensions > 0) {
            return (int)$loan->max_tenure_extensions;
        }
        
        // Fall back to system setting
        $this->load->model('Setting_model');
        $max = $this->Setting_model->get_setting('max_tenure_extensions', 6);
        return (int)$max;
    }
    
    /**
     * Check if Interest-Only Payment is Allowed for a Loan
     * 
     * Validates:
     * 1. Loan product allows it
     * 2. Extension limit not reached
     * 3. Loan is active
     * 
     * @param int $loan_id Loan ID
     * @return array ['allowed' => bool, 'reason' => string, 'extensions_used' => int, 'max_extensions' => int, 'interest_amount' => float]
     */
    public function check_interest_only_eligibility($loan_id) {
        $loan = $this->get_by_id($loan_id);
        
        if (!$loan || $loan->status !== 'active') {
            return ['allowed' => false, 'reason' => 'Loan not found or not active'];
        }
        
        // Check product allows it
        $product = $this->db->where('id', $loan->loan_product_id)
                            ->get('loan_products')
                            ->row();
        
        if ($product && isset($product->allow_interest_only) && !$product->allow_interest_only) {
            return ['allowed' => false, 'reason' => 'Interest-only payments not allowed for this loan product'];
        }
        
        // Check extension limits
        $extensions_used = $loan->tenure_extensions ?? 0;
        $max_extensions = $this->get_max_tenure_extensions($loan);
        
        if ($extensions_used >= $max_extensions) {
            return [
                'allowed' => false, 
                'reason' => "Maximum tenure extensions ({$max_extensions}) already used",
                'extensions_used' => $extensions_used,
                'max_extensions' => $max_extensions
            ];
        }
        
        // Get next pending installment for interest amount info
        $next_installment = $this->db->where('loan_id', $loan_id)
                                     ->where_in('status', ['pending', 'overdue'])
                                     ->order_by('installment_number', 'ASC')
                                     ->limit(1)
                                     ->get('loan_installments')
                                     ->row();
        
        $interest_due = 0;
        $installment_id = null;
        $principal_deferred = 0;
        $emi_amount = 0;
        
        if ($next_installment) {
            $interest_due = $next_installment->interest_amount - $next_installment->interest_paid;
            $principal_deferred = $next_installment->principal_amount - $next_installment->principal_paid;
            $installment_id = $next_installment->id;
            $emi_amount = $next_installment->emi_amount;
        }
        
        return [
            'allowed' => true,
            'reason' => 'Eligible for interest-only payment',
            'extensions_used' => $extensions_used,
            'max_extensions' => $max_extensions,
            'remaining_extensions' => $max_extensions - $extensions_used,
            'interest_amount' => round($interest_due, 2),
            'principal_deferred' => round($principal_deferred, 2),
            'emi_amount' => round($emi_amount, 2),
            'installment_id' => $installment_id,
            'new_tenure' => $loan->tenure_months + 1,
            'original_tenure' => $loan->original_tenure_months ?? $loan->tenure_months
        ];
    }
    
    /**
     * Get Interest-Only Payment History for a Loan
     * 
     * @param int $loan_id Loan ID
     * @return array List of interest-only payments with details
     */
    public function get_interest_only_history($loan_id) {
        return $this->db->select('lp.*, li.installment_number, li.deferred_principal,
                                  ext.installment_number as extended_to_installment, ext.due_date as extended_due_date')
                        ->from('loan_payments lp')
                        ->join('loan_installments li', 'li.id = lp.installment_id', 'left')
                        ->join('loan_installments ext', 'ext.extended_from_installment = li.id AND ext.loan_id = lp.loan_id', 'left')
                        ->where('lp.loan_id', $loan_id)
                        ->where('lp.payment_type', 'interest_only')
                        ->where('lp.is_reversed', 0)
                        ->order_by('lp.payment_date', 'ASC')
                        ->get()
                        ->result();
    }
    
    /**
     * Release Guarantors
     */
    private function release_guarantors($loan_id) {
        return $this->db->where('loan_id', $loan_id)
                        ->update('loan_guarantors', [
                            'is_released' => 1,
                            'released_at' => date('Y-m-d H:i:s')
                        ]);
    }
    
    /**
     * Skip Installment
     */
    public function skip_installment($installment_id, $reason, $skipped_by) {
        $this->db->trans_begin();
        
        try {
            $installment = $this->db->where('id', $installment_id)
                                    ->get('loan_installments')
                                    ->row();
            
            if (!$installment) {
                throw new Exception('Installment not found');
            }
            
            // Mark as skipped
            $this->db->where('id', $installment_id)
                     ->update('loan_installments', [
                         'is_skipped' => 1,
                         'skip_reason' => $reason,
                         'skipped_by' => $skipped_by,
                         'status' => 'skipped',
                         'updated_at' => date('Y-m-d H:i:s')
                     ]);
            
            // Adjust remaining installments
            $this->adjust_schedule_after_skip($installment->loan_id, $installment->installment_number);
            
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return false;
            }
            
            $this->db->trans_commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            throw $e;
        }
    }
    
    /**
     * Adjust Schedule After Skip
     * Bug #17 Fix: Properly recalculate remaining schedule with correct interest
     */
    private function adjust_schedule_after_skip($loan_id, $skipped_installment_number) {
        // Get loan details
        $loan = $this->get_by_id($loan_id);
        
        // Get skipped installment
        $skipped = $this->db->where('loan_id', $loan_id)
                            ->where('installment_number', $skipped_installment_number)
                            ->get('loan_installments')
                            ->row();
        
        // Get remaining installments
        $remaining_installments = $this->db->where('loan_id', $loan_id)
                                          ->where('installment_number >', $skipped_installment_number)
                                          ->where('status !=', 'paid')
                                          ->order_by('installment_number', 'ASC')
                                          ->get('loan_installments')
                                          ->result();
        
        $remaining_count = count($remaining_installments);
        
        if ($remaining_count === 0) {
            return true; // No remaining installments to adjust
        }
        
        // Calculate outstanding principal (including skipped amount)
        $outstanding_principal = $skipped->outstanding_principal_before;
        
        // Recalculate EMI for remaining tenure
        $monthly_rate = ($loan->interest_rate / 12) / 100;
        
        // LOAN-6 FIX: Use correct ENUM values ('reducing', 'reducing_monthly')
        if ($loan->interest_type === 'reducing' || $loan->interest_type === 'reducing_monthly') {
            // Recalculate EMI using reducing balance formula
            if ($monthly_rate > 0) {
                $new_emi = $outstanding_principal * $monthly_rate * pow(1 + $monthly_rate, $remaining_count) 
                          / (pow(1 + $monthly_rate, $remaining_count) - 1);
            } else {
                $new_emi = $outstanding_principal / $remaining_count;
            }
            
            // Regenerate schedule for remaining installments
            $balance = $outstanding_principal;
            $total_principal_allocated = 0;
            
            foreach ($remaining_installments as $index => $inst) {
                $interest = $balance * $monthly_rate;
                $principal_part = $new_emi - $interest;
                
                // Last installment adjustment
                if ($index === $remaining_count - 1) {
                    $principal_part = $outstanding_principal - $total_principal_allocated;
                } else {
                    $principal_part = round($principal_part, 2);
                    $total_principal_allocated += $principal_part;
                }
                
                $interest = round($interest, 2);
                $emi_amount = $principal_part + $interest;
                $balance -= $principal_part;
                
                // Update installment
                $this->db->where('id', $inst->id)
                         ->update('loan_installments', [
                             'principal_amount' => $principal_part,
                             'interest_amount' => $interest,
                             'emi_amount' => round($emi_amount, 2),
                             'outstanding_principal_before' => round($outstanding_principal - $total_principal_allocated + $principal_part, 2),
                             'outstanding_principal_after' => round(max(0, $balance), 2),
                             'updated_at' => date('Y-m-d H:i:s')
                         ]);
            }
        } else {
            // Flat interest - just distribute principal evenly
            $additional_per_emi = $skipped->principal_amount / $remaining_count;
            
            $this->db->set('principal_amount', 'principal_amount + ' . $additional_per_emi, FALSE)
                     ->set('emi_amount', 'emi_amount + ' . $additional_per_emi, FALSE)
                     ->where('loan_id', $loan_id)
                     ->where('installment_number >', $skipped_installment_number)
                     ->where('status !=', 'paid')
                     ->update('loan_installments');
        }
        
        return true;
    }
    
    /**
     * Get Application Details
     */
    public function get_application($id) {
        $application = $this->db->select('la.*, lp.product_name, lp.interest_type, m.member_code, m.first_name, m.last_name, m.phone, 
                                         au.full_name as approver_name')
                               ->from('loan_applications la')
                               ->join('loan_products lp', 'lp.id = la.loan_product_id', 'left')
                               ->join('members m', 'm.id = la.member_id')
                               ->join('admin_users au', 'au.id = la.admin_approved_by', 'left')
                               ->where('la.id', $id)
                               ->get()
                               ->row();
        
        // If application is disbursed, get the loan_id
        if ($application && $application->status === 'disbursed') {
            $loan = $this->db->select('id')
                            ->where('loan_application_id', $id)
                            ->get('loans')
                            ->row();
            $application->loan_id = $loan ? $loan->id : null;
        }
        
        return $application;
    }
    
    /**
     * Get Application Guarantors
     */
    public function get_application_guarantors($application_id) {
        return $this->db->select('lg.*, m.member_code, m.first_name, m.last_name, m.phone')
                        ->from('loan_guarantors lg')
                        ->join('members m', 'm.id = lg.guarantor_member_id')
                        ->where('lg.loan_application_id', $application_id)
                        ->get()
                        ->result();
    }
    
    /**
     * Get Loan Details with Installments
     */
    public function get_loan_details($id) {
        $loan = $this->db->select('l.*, lp.product_name, m.member_code, m.first_name, m.last_name, m.phone')
                         ->from('loans l')
                         ->join('loan_products lp', 'lp.id = l.loan_product_id')
                         ->join('members m', 'm.id = l.member_id')
                         ->where('l.id', $id)
                         ->get()
                         ->row();
        
        if ($loan) {
            $loan->installments = $this->get_loan_installments($id);
            $loan->guarantors = $this->db->where('loan_id', $id)
                                          ->get('loan_guarantors')
                                          ->result();
            $loan->payments = $this->get_loan_payments($id);
        }
        
        return $loan;
    }
    
    /**
     * Get Loan Installments
     */
    public function get_loan_installments($loan_id) {
        return $this->db->where('loan_id', $loan_id)
                        ->order_by('installment_number', 'ASC')
                        ->get('loan_installments')
                        ->result();
    }
    
    /**
     * Get Loan Payments
     */
    public function get_loan_payments($loan_id) {
        return $this->db->where('loan_id', $loan_id)
                        ->where('is_reversed', 0)
                        ->order_by('payment_date', 'DESC')
                        ->get('loan_payments')
                        ->result();
    }
    
    /**
     * Get Payment History with Stats
     */
    public function get_payment_history_stats($filters = []) {
        $this->db->select('COUNT(*) as total_count, SUM(total_amount) as total_amount, SUM(principal_component) as total_principal, SUM(interest_component) as total_interest, SUM(fine_component) as total_fine');
        $this->db->from('loan_payments');
        $this->db->where('is_reversed', 0);
        
        if (!empty($filters['loan_id'])) {
            $this->db->where('loan_id', $filters['loan_id']);
        }
        
        if (!empty($filters['date_from'])) {
            $this->db->where('payment_date >=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $this->db->where('payment_date <=', $filters['date_to']);
        }
        
        return $this->db->get()->row();
    }
    
    /**
     * Get Recent Payments
     */
    public function get_recent_payments($limit = 10) {
        return $this->db->select('lp.*, l.loan_number, m.member_code, m.first_name, m.last_name')
                        ->from('loan_payments lp')
                        ->join('loans l', 'l.id = lp.loan_id')
                        ->join('members m', 'm.id = l.member_id')
                        ->where('lp.is_reversed', 0)
                        ->order_by('lp.payment_date', 'DESC')
                        ->limit($limit)
                        ->get()
                        ->result();
    }
    
    /**
     * Get Member Loans
     */
    public function get_member_loans($member_id) {
        return $this->db->select('l.*, lp.product_name')
                        ->from('loans l')
                        ->join('loan_products lp', 'lp.id = l.loan_product_id')
                        ->where('l.member_id', $member_id)
                        ->order_by('l.disbursement_date', 'DESC')
                        ->get()
                        ->result();
    }
    
    /**
     * Get Active Loan Count
     */
    public function get_active_loan_count($member_id) {
        return $this->db->where('member_id', $member_id)
                        ->where('status', 'active')
                        ->count_all_results($this->table);
    }
    
    /**
     * Get Pending Applications
     */
    public function get_pending_applications() {
        return $this->db->select('la.*, m.member_code, m.first_name, m.last_name, m.phone, lp.product_name')
                        ->from('loan_applications la')
                        ->join('members m', 'm.id = la.member_id')
                        ->join('loan_products lp', 'lp.id = la.loan_product_id', 'left')
                        ->where_in('la.status', ['pending', 'under_review', 'guarantor_pending'])
                        ->order_by('la.application_date', 'ASC')
                        ->get()
                        ->result();
    }
    
    /**
     * Get Overdue Loans
     * LOAN-7 FIX: Include 'upcoming' installments past due date (not just 'pending')
     */
    public function get_overdue_loans() {
        $overdue = $this->db->select('
            l.*, 
            m.member_code, m.first_name, m.last_name, m.phone,
            li.id as installment_id, li.due_date, li.emi_amount, li.installment_number,
            IFNULL(f_sum.total_fine, 0) as fine_amount,
            IFNULL(f_sum.total_paid, 0) as fine_paid
        ')
        ->from('loans l')
        ->join('members m', 'm.id = l.member_id')
        ->join('loan_installments li', 'li.loan_id = l.id')
        ->join('(SELECT related_id, 
                    SUM(fine_amount) as total_fine, 
                    SUM(IFNULL(paid_amount,0)) as total_paid 
                 FROM fines 
                 WHERE related_type = "loan_installment" 
                   AND status NOT IN ("cancelled","waived")
                 GROUP BY related_id) f_sum', 'f_sum.related_id = li.id', 'left')
        ->where('l.status', 'active')
        ->where_in('li.status', ['pending', 'upcoming', 'partial'])
        ->where('li.due_date <', date('Y-m-d'))
        ->group_by('l.id')
        ->order_by('li.due_date', 'ASC')
        ->get()
        ->result();

        // If no fine record exists yet, calculate estimated fine from active rules
        if (!empty($overdue)) {
            $rule = $this->db->where_in('applies_to', ['loan', 'both'])
                             ->where('is_active', 1)
                             ->where('effective_from <=', date('Y-m-d'))
                             ->get('fine_rules')
                             ->row();

            if ($rule) {
                foreach ($overdue as &$loan) {
                    if ($loan->fine_amount <= 0) {
                        $days_late = floor((time() - safe_timestamp($loan->due_date)) / 86400);
                        $grace = $rule->grace_period_days ?? 0;
                        $effective_days = max(0, $days_late - $grace);

                        if ($effective_days > 0) {
                            $calc_type = $rule->calculation_type ?? ($rule->fine_type ?? 'fixed');
                            $estimated = 0;

                            if ($calc_type === 'fixed') {
                                $estimated = $rule->fine_value;
                            } elseif ($calc_type === 'percentage') {
                                $estimated = $loan->emi_amount * ($rule->fine_value / 100);
                            } elseif ($calc_type === 'per_day') {
                                $estimated = $rule->fine_value + ($rule->per_day_amount * max(0, $effective_days - 1));
                            }

                            if ($rule->max_fine_amount > 0 && $estimated > $rule->max_fine_amount) {
                                $estimated = $rule->max_fine_amount;
                            }

                            $loan->fine_amount = round($estimated, 2);
                            $loan->fine_estimated = true; // flag for view
                        }
                    }
                }
            }
        }

        return $overdue;
    }
    
    /**
     * Get Due Today
     * LOAN-7 FIX: Include 'upcoming' installments (not just 'pending')
     */
    public function get_due_today() {
        return $this->db->select('
            li.*, 
            l.loan_number,
            m.member_code, m.first_name, m.last_name, m.phone
        ')
        ->from('loan_installments li')
        ->join('loans l', 'l.id = li.loan_id')
        ->join('members m', 'm.id = l.member_id')
        ->where('li.due_date', date('Y-m-d'))
        ->where_in('li.status', ['pending', 'upcoming'])
        ->get()
        ->result();
    }
    
    /**
     * Get Loan Products
     */
    public function get_products($active_only = true) {
        if ($active_only) {
            $this->db->where('is_active', 1);
        }
        
        return $this->db->get('loan_products')->result();
    }
    
    /**
     * Get Ready for Disbursement
     * Applications that are approved by admin and confirmed by member
     */
    public function get_ready_for_disbursement() {
        return $this->db->select('la.*, m.member_code, m.first_name, m.last_name, m.phone, lp.product_name, lp.interest_rate as default_rate')
                        ->from('loan_applications la')
                        ->join('members m', 'm.id = la.member_id')
                        ->join('loan_products lp', 'lp.id = la.loan_product_id')
                        ->where('la.status', 'member_approved')
                        ->order_by('la.member_approved_at', 'ASC')
                        ->get()
                        ->result();
    }
    
    /**
     * Get Dashboard Stats
     */
    public function get_dashboard_stats() {
        $stats = [];
        
        // Total Outstanding
        $stats['total_outstanding'] = $this->db->select_sum('outstanding_principal')
                                               ->where('status', 'active')
                                               ->get($this->table)
                                               ->row()
                                               ->outstanding_principal ?? 0;
        
        // Active Loans
        $stats['active_loans'] = $this->db->where('status', 'active')
                                          ->count_all_results($this->table);
        
        // Pending Applications
        $stats['pending_applications'] = $this->db->where_in('status', ['pending', 'under_review', 'guarantor_pending'])
                                                   ->count_all_results('loan_applications');
        
        // This Month Disbursement
        $stats['month_disbursement'] = $this->db->select_sum('principal_amount')
                                                 ->where('MONTH(disbursement_date)', date('m'))
                                                 ->where('YEAR(disbursement_date)', date('Y'))
                                                 ->get($this->table)
                                                 ->row()
                                                 ->principal_amount ?? 0;
        
        // This Month Collection
        $stats['month_collection'] = $this->db->select_sum('total_amount')
                                               ->where('MONTH(payment_date)', date('m'))
                                               ->where('YEAR(payment_date)', date('Y'))
                                               ->where('is_reversed', 0)
                                               ->get('loan_payments')
                                               ->row()
                                               ->total_amount ?? 0;
        
        // Overdue Amount
        // LOAN-7 FIX: Include 'upcoming' installments past due date
        $stats['overdue_amount'] = $this->db->select('SUM(li.emi_amount - li.total_paid) as overdue')
                                            ->from('loan_installments li')
                                            ->join('loans l', 'l.id = li.loan_id')
                                            ->where('l.status', 'active')
                                            ->where_in('li.status', ['pending', 'upcoming', 'partial'])
                                            ->where('li.due_date <', date('Y-m-d'))
                                            ->get()
                                            ->row()
                                            ->overdue ?? 0;
        
        return $stats;
    }

    /**
     * Calculate Foreclosure Amount
     */
    public function calculate_foreclosure_amount($loan_id) {
        $loan = $this->db->where('id', $loan_id)->get('loans')->row();
        if (!$loan) {
            return false;
        }

        $outstanding_principal = $loan->outstanding_principal ?? 0;
        // LOAN-4 FIX: Include outstanding interest in foreclosure calculation
        $outstanding_interest = $loan->outstanding_interest ?? 0;

        // LOAN-13 FIX: Prepayment charge defaults to 0% (not 2%)
        $prepayment_row = $this->db->where('setting_key', 'prepayment_charge_percentage')
                                   ->get('system_settings')
                                   ->row();
        $prepayment_percentage = $prepayment_row ? (float)$prepayment_row->setting_value : 0;

        $prepayment_charge = ($outstanding_principal * $prepayment_percentage) / 100;

        // Get pending fines for this loan (join through installments)
        $pending_fines = $this->db->select_sum('f.fine_amount')
                                  ->from('fines f')
                                  ->join('loan_installments li', 'li.id = f.related_id AND f.related_type = "loan_installment"', 'inner')
                                  ->where('li.loan_id', $loan_id)
                                  ->where('f.status', 'pending')
                                  ->get()
                                  ->row()
                                  ->fine_amount ?? 0;

        // LOAN-4 FIX: Total includes outstanding_interest
        $total_amount = $outstanding_principal + $outstanding_interest + $prepayment_charge + $pending_fines;

        return [
            'outstanding_principal' => $outstanding_principal,
            'outstanding_interest' => $outstanding_interest,
            'prepayment_charge' => $prepayment_charge,
            'prepayment_percentage' => $prepayment_percentage,
            'pending_fines' => $pending_fines,
            'total_amount' => $total_amount,
            'pending_fines_list' => $this->db->select('f.*')
                                           ->from('fines f')
                                           ->join('loan_installments li', 'li.id = f.related_id AND f.related_type = "loan_installment"', 'inner')
                                           ->where('li.loan_id', $loan_id)
                                           ->where('f.status', 'pending')
                                           ->get()
                                           ->result()
        ];
    }

    /**
     * Request Loan Foreclosure
     */
    public function request_foreclosure($loan_id, $member_id, $reason, $settlement_date) {
        $loan = $this->db->where('id', $loan_id)
                        ->where('member_id', $member_id)
                        ->where('status', 'active')
                        ->get('loans')
                        ->row();

        if (!$loan) {
            return ['success' => false, 'message' => 'Loan not found or not eligible for foreclosure'];
        }

        // Check if foreclosure already requested
        if ($this->db->where('loan_id', $loan_id)->where('status', 'pending')->get('loan_foreclosure_requests')->num_rows() > 0) {
            return ['success' => false, 'message' => 'Foreclosure already requested for this loan'];
        }

        $calculation = $this->calculate_foreclosure_amount($loan_id);

        $data = [
            'loan_id' => $loan_id,
            'member_id' => $member_id,
            'foreclosure_amount' => $calculation['total_amount'],
            'reason' => $reason,
            'settlement_date' => $settlement_date,
            'status' => 'pending',
            'requested_at' => date('Y-m-d H:i:s')
        ];

        $result = $this->db->insert('loan_foreclosure_requests', $data);

        if ($result) {
            // Log the foreclosure request
            $this->load->model('Audit_model');
            $this->Audit_model->log_activity(
                'loan_foreclosure_requested',
                'loans',
                $loan_id,
                "Member requested foreclosure for loan #{$loan->loan_number}: {$reason}",
                $member_id
            );

            return ['success' => true, 'message' => 'Foreclosure request submitted successfully'];
        }

        return ['success' => false, 'message' => 'Failed to submit foreclosure request'];
    }

    /**
     * Get Foreclosure Request Status
     */
    public function get_foreclosure_request($loan_id, $member_id) {
        return $this->db->where('loan_id', $loan_id)
                       ->where('member_id', $member_id)
                       ->order_by('requested_at', 'DESC')
                       ->get('loan_foreclosure_requests')
                       ->row();
    }

    /**
     * Process Foreclosure Request (Admin only)
     */
    public function process_foreclosure_request($request_id, $admin_id, $action, $comments = null) {
        $request = $this->db->where('id', $request_id)->get('loan_foreclosure_requests')->row();
        if (!$request) {
            return ['success' => false, 'message' => 'Foreclosure request not found'];
        }

        $update_data = [
            'processed_by' => $admin_id,
            'processed_at' => date('Y-m-d H:i:s'),
            'admin_comments' => $comments
        ];

        if ($action === 'approve') {
            $update_data['status'] = 'approved';

            // LOAN-5 FIX: Use correct column names (closure_date, closure_type)
            $this->db->where('id', $request->loan_id)
                    ->update('loans', [
                        'status' => 'foreclosed',
                        'closure_date' => date('Y-m-d'),
                        'closure_type' => 'foreclosure',
                        'closure_remarks' => $comments,
                        'closed_by' => $admin_id,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

            // Release guarantors on foreclosure
            $this->release_guarantors($request->loan_id);

            // Mark associated fines as paid (join through installments since fines has no loan_id)
            $installment_ids = $this->db->select('id')
                                       ->where('loan_id', $request->loan_id)
                                       ->get('loan_installments')
                                       ->result_array();
            if (!empty($installment_ids)) {
                $inst_ids = array_column($installment_ids, 'id');
                $this->db->where('related_type', 'loan_installment')
                        ->where_in('related_id', $inst_ids)
                        ->where('status', 'pending')
                        ->update('fines', [
                            'status' => 'paid',
                            'payment_date' => date('Y-m-d')
                        ]);
            }

        } elseif ($action === 'reject') {
            $update_data['status'] = 'rejected';
        }

        $result = $this->db->where('id', $request_id)->update('loan_foreclosure_requests', $update_data);

        if ($result) {
            // Log the processing
            $this->load->model('Audit_model');
            $this->Audit_model->log_activity(
                'loan_foreclosure_' . $action . 'd',
                'loan_foreclosure_requests',
                $request_id,
                "Foreclosure request {$action}d for loan #{$request->loan_id}",
                $admin_id
            );

            return ['success' => true, 'message' => "Foreclosure request {$action}d successfully"];
        }

        return ['success' => false, 'message' => 'Failed to process foreclosure request'];
    }
}
