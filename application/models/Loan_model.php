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
        return $this->db->insert_id();
    }
    
    /**
     * Add Guarantor to Application
     */
    public function add_guarantor($application_id, $guarantor_member_id, $guarantee_amount, $relationship = null) {
        return $this->db->insert('loan_guarantors', [
            'loan_application_id' => $application_id,
            'guarantor_member_id' => $guarantor_member_id,
            'guarantee_amount' => $guarantee_amount,
            'relationship' => $relationship,
            'consent_status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]);
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
        
        return $this->db->where('id', $guarantor_id)
                        ->update('loan_guarantors', $data);
    }
    
    /**
     * Admin Approve/Revise Application
     */
    public function admin_approve($application_id, $data, $admin_id) {
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
    public function disburse_loan($application_id, $disbursement_data, $admin_id) {
        $this->db->trans_begin();
        
        try {
            $application = $this->db->where('id', $application_id)
                                    ->get('loan_applications')
                                    ->row();
            
            if (!$application || $application->status !== 'member_approved') {
                throw new Exception('Application not ready for disbursement');
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
     */
    public function calculate_emi($principal, $rate, $tenure, $type = 'reducing') {
        $monthly_rate = ($rate / 12) / 100;
        
        if ($type === 'flat') {
            $total_interest = $principal * ($rate / 100) * ($tenure / 12);
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
     */
    public function generate_installment_schedule($loan_id, $principal, $rate, $tenure, $type, $emi, $first_emi_date) {
        $monthly_rate = ($rate / 12) / 100;
        $balance = $principal;
        $due_date = new DateTime($first_emi_date);
        
        for ($i = 1; $i <= $tenure; $i++) {
            if ($type === 'flat') {
                $interest = ($principal * ($rate / 100) * ($tenure / 12)) / $tenure;
                $principal_part = $principal / $tenure;
            } else {
                $interest = $balance * $monthly_rate;
                $principal_part = $emi - $interest;
            }
            
            $outstanding_before = $balance;
            $balance -= $principal_part;
            
            // Last installment adjustment
            if ($i === $tenure) {
                $principal_part += $balance;
                $balance = 0;
            }
            
            $this->db->insert('loan_installments', [
                'loan_id' => $loan_id,
                'installment_number' => $i,
                'due_date' => $due_date->format('Y-m-d'),
                'principal_amount' => round($principal_part, 2),
                'interest_amount' => round($interest, 2),
                'emi_amount' => round($principal_part + $interest, 2),
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
            
            // Determine payment allocation
            $amount = $data['total_amount'];
            $principal_paid = 0;
            $interest_paid = 0;
            $fine_paid = 0;
            
            // Pay fine first
            if ($loan->outstanding_fine > 0 && $amount > 0) {
                $fine_paid = min($loan->outstanding_fine, $amount);
                $amount -= $fine_paid;
            }
            
            // Pay interest next
            if ($loan->outstanding_interest > 0 && $amount > 0) {
                $interest_paid = min($loan->outstanding_interest, $amount);
                $amount -= $interest_paid;
            }
            
            // Pay principal
            if ($loan->outstanding_principal > 0 && $amount > 0) {
                $principal_paid = min($loan->outstanding_principal, $amount);
                $amount -= $principal_paid;
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
     */
    private function adjust_schedule_after_skip($loan_id, $skipped_installment_number) {
        // Get skipped installment
        $skipped = $this->db->where('loan_id', $loan_id)
                            ->where('installment_number', $skipped_installment_number)
                            ->get('loan_installments')
                            ->row();
        
        // Distribute skipped amount to remaining installments
        $remaining = $this->db->where('loan_id', $loan_id)
                              ->where('installment_number >', $skipped_installment_number)
                              ->where('status !=', 'paid')
                              ->count_all_results('loan_installments');
        
        if ($remaining > 0) {
            $additional_per_emi = $skipped->principal_amount / $remaining;
            
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
        return $this->db->select('la.*, lp.product_name, lp.interest_type, m.member_code, m.first_name, m.last_name, m.phone')
                        ->from('loan_applications la')
                        ->join('loan_products lp', 'lp.id = la.loan_product_id')
                        ->join('members m', 'm.id = la.member_id')
                        ->where('la.id', $id)
                        ->get()
                        ->row();
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
        return $this->db->select('
            l.*, 
            m.member_code, m.first_name, m.last_name, m.phone,
            li.due_date, li.emi_amount, li.installment_number
        ')
        ->from('loans l')
        ->join('members m', 'm.id = l.member_id')
        ->join('loan_installments li', 'li.loan_id = l.id')
        ->where('l.status', 'active')
        ->where('li.status', 'pending')
        ->where('li.due_date <', date('Y-m-d'))
        ->group_by('l.id')
        ->order_by('li.due_date', 'ASC')
        ->get()
        ->result();
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
}
