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
     */
    public function generate_loan_number() {
        $prefix = 'LN';
        $year = date('Y');
        
        $max = $this->db->select_max('id')
                        ->get($this->table)
                        ->row();
        
        $next = ($max->id ?? 0) + 1;
        
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
     */
    public function admin_approve($application_id, $data, $admin_id) {
        // Bug #2 Fix: Get member savings balance and enforce ratio
        $application = $this->db->where('id', $application_id)
                                ->get('loan_applications')
                                ->row();
        
        if (!$application) {
            throw new Exception('Application not found');
        }
        
        // Get product requirements
        $product = $this->db->where('id', $application->loan_product_id)
                            ->get('loan_products')
                            ->row();
        
        if ($product && !empty($product->min_savings_balance)) {
            // Get member's current savings balance
            $this->load->model('Member_model');
            $member = $this->Member_model->get_member_details($application->member_id);
            $savings_balance = $member->savings_summary->current_balance ?? 0;
            
            if ($savings_balance < $product->min_savings_balance) {
                throw new Exception('Member savings balance (₹' . number_format($savings_balance, 2) . ') is below minimum requirement (₹' . number_format($product->min_savings_balance, 2) . ')');
            }
        }
        
        // Check loan-to-savings ratio (if configured)
        if ($product && !empty($product->max_loan_to_savings_ratio)) {
            $member = $this->Member_model->get_member_details($application->member_id);
            $savings_balance = $member->savings_summary->current_balance ?? 0;
            
            $max_loan = $savings_balance * $product->max_loan_to_savings_ratio;
            
            if ($data['approved_amount'] > $max_loan) {
                throw new Exception('Approved amount (₹' . number_format($data['approved_amount'], 2) . ') exceeds maximum based on savings ratio (₹' . number_format($max_loan, 2) . ')');
            }
        }
        
        $update = [
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
            
            // Create loan record
            $loan_data = [
                'loan_number' => $this->generate_loan_number(),
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
     */
    public function record_payment($data) {
        $this->db->trans_begin();
        
        try {
            $loan = $this->get_by_id($data['loan_id']);
            
            if (!$loan || $loan->status !== 'active') {
                throw new Exception('Loan not found or not active');
            }
            
            // Generate payment code
            $data['payment_code'] = 'PAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            $data['payment_date'] = $data['payment_date'] ?? date('Y-m-d');
            
            // Determine payment allocation (Bug #16 Fix: RBI-compliant order)
            // RBI Guidelines: Interest → Principal → Fine
            $amount = $data['total_amount'];
            $principal_paid = 0;
            $interest_paid = 0;
            $fine_paid = 0;
            
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
     */
    private function update_installment_payment($installment_id, $principal, $interest, $fine) {
        $installment = $this->db->where('id', $installment_id)
                                ->get('loan_installments')
                                ->row();
        
        if (!$installment) return false;
        
        $new_principal_paid = $installment->principal_paid + $principal;
        $new_interest_paid = $installment->interest_paid + $interest;
        $new_fine_paid = $installment->fine_paid + $fine;
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
        
        if ($loan->interest_type === 'reducing_balance' || $loan->interest_type === 'reducing_monthly') {
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
                               ->join('loan_products lp', 'lp.id = la.loan_product_id')
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
                        ->join('loan_products lp', 'lp.id = la.loan_product_id')
                        ->where_in('la.status', ['pending', 'under_review', 'guarantor_pending'])
                        ->order_by('la.application_date', 'ASC')
                        ->get()
                        ->result();
    }
    
    /**
     * Get Overdue Loans
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
        ->where('li.status', 'pending')
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
        ->where('li.status', 'pending')
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
        $stats['overdue_amount'] = $this->db->select('SUM(li.emi_amount - li.total_paid) as overdue')
                                            ->from('loan_installments li')
                                            ->join('loans l', 'l.id = li.loan_id')
                                            ->where('l.status', 'active')
                                            ->where('li.status', 'pending')
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

        // Get prepayment charge percentage from settings
        $prepayment_percentage = $this->db->where('setting_key', 'prepayment_charge_percentage')
                                         ->get('system_settings')
                                         ->row()
                                         ->setting_value ?? 2; // Default 2%

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

        $total_amount = $outstanding_principal + $prepayment_charge + $pending_fines;

        return [
            'outstanding_principal' => $outstanding_principal,
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

            // Update loan status to closed
            $this->db->where('id', $request->loan_id)
                    ->update('loans', [
                        'status' => 'closed',
                        'closed_at' => date('Y-m-d H:i:s'),
                        'closure_reason' => 'foreclosure'
                    ]);

            // Mark associated fines as paid (since they're included in foreclosure)
            $this->db->where('loan_id', $request->loan_id)
                    ->where('status', 'pending')
                    ->update('fines', [
                        'status' => 'paid',
                        'paid_at' => date('Y-m-d H:i:s')
                    ]);

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
