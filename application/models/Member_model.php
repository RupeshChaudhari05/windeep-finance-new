<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Member_model - Member Management
 */
class Member_model extends MY_Model {
    
    protected $table = 'members';
    protected $primary_key = 'id';
    protected $soft_delete = true;
    protected $fillable = [
        'member_code', 'first_name', 'last_name', 'father_name', 'date_of_birth',
        'gender', 'email', 'phone', 'alternate_phone', 'address_line1', 'address_line2',
        'city', 'state', 'pincode', 'photo', 'aadhaar_number', 'pan_number', 'voter_id',
        'aadhaar_doc', 'pan_doc', 'address_proof_doc', 'kyc_verified', 'kyc_verified_at',
        'kyc_verified_by', 'bank_name', 'bank_branch', 'account_number', 'ifsc_code',
        'account_holder_name', 'join_date', 'membership_type', 'opening_balance',
        'opening_balance_type', 'status', 'status_reason', 'status_changed_at',
        'status_changed_by', 'nominee_name', 'nominee_relation', 'nominee_phone',
        'nominee_aadhaar', 'max_guarantee_amount', 'max_guarantee_count', 'password',
        'notes', 'created_by'
    ];
    
    /**
     * Generate New Member Code
     */
    public function generate_member_code() {
        $year = date('Y');
        
        // Get or create sequence
        $seq = $this->db->where('year', $year)
                        ->get('member_code_sequence')
                        ->row();
        
        if (!$seq) {
            $this->db->insert('member_code_sequence', [
                'prefix' => 'MEM',
                'current_number' => 0,
                'year' => $year
            ]);
            $seq = $this->db->where('year', $year)
                            ->get('member_code_sequence')
                            ->row();
        }
        
        // Increment
        $next_number = $seq->current_number + 1;
        $this->db->where('id', $seq->id)
                 ->update('member_code_sequence', ['current_number' => $next_number]);
        
        return $seq->prefix . '-' . $year . '-' . str_pad($next_number, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Create Member
     */
    public function create_member($data) {
        if (empty($data['member_code'])) {
            $data['member_code'] = $this->generate_member_code();
        }
        
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        $data['created_at'] = date('Y-m-d H:i:s');
        
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }
    
    /**
     * Get Member with Full Details
     */
    public function get_member_details($id) {
        $member = $this->get_by_id($id);
        
        if (!$member) {
            return null;
        }
        
        // Get savings summary
        $member->savings_summary = $this->get_savings_summary($id);
        
        // Get loan summary
        $member->loan_summary = $this->get_loan_summary($id);
        
        // Get guarantor exposure
        $member->guarantor_exposure = $this->get_guarantor_exposure($id);
        
        // Get fine summary
        $member->fine_summary = $this->get_fine_summary($id);
        
        return $member;
    }
    
    /**
     * Get Savings Summary
     */
    public function get_savings_summary($member_id) {
        return $this->db->select('
            COUNT(id) as total_accounts,
            SUM(total_deposited) as total_deposited,
            SUM(current_balance) as current_balance,
            SUM(total_fines_paid) as total_fines
        ')
        ->where('member_id', $member_id)
        ->where('status', 'active')
        ->get('savings_accounts')
        ->row();
    }
    
    /**
     * Get Loan Summary
     */
    public function get_loan_summary($member_id) {
        return $this->db->select('
            COUNT(id) as total_loans,
            SUM(principal_amount) as total_principal,
            SUM(outstanding_principal) as outstanding_principal,
            SUM(outstanding_interest) as outstanding_interest,
            SUM(outstanding_fine) as outstanding_fine,
            SUM(total_amount_paid) as total_paid
        ')
        ->where('member_id', $member_id)
        ->where('status', 'active')
        ->get('loans')
        ->row();
    }
    
    /**
     * Get Guarantor Exposure
     */
    public function get_guarantor_exposure($member_id) {
        return $this->db->select('
            COUNT(lg.id) as guarantee_count,
            SUM(lg.guarantee_amount) as total_exposure,
            SUM(l.outstanding_principal + l.outstanding_interest) as current_liability
        ')
        ->from('loan_guarantors lg')
        ->join('loans l', 'l.id = lg.loan_id', 'left')
        ->where('lg.guarantor_member_id', $member_id)
        ->where('lg.is_released', 0)
        ->where('lg.consent_status', 'accepted')
        ->get()
        ->row();
    }
    
    /**
     * Get Fine Summary
     */
    public function get_fine_summary($member_id) {
        return $this->db->select('
            COUNT(id) as total_fines,
            SUM(fine_amount) as total_fine_amount,
            SUM(paid_amount) as total_paid,
            SUM(balance_amount) as pending_amount
        ')
        ->where('member_id', $member_id)
        ->get('fines')
        ->row();
    }
    
    /**
     * Search Members
     */
    public function search_members($keyword, $status = null, $limit = 50) {
        $this->db->select('id, member_code, first_name, last_name, phone, email, status');
        
        if ($this->soft_delete) {
            $this->db->where('deleted_at', null);
        }
        
        if ($status) {
            $this->db->where('status', $status);
        }
        
        if ($keyword) {
            $this->db->group_start();
            $this->db->like('member_code', $keyword);
            $this->db->or_like('first_name', $keyword);
            $this->db->or_like('last_name', $keyword);
            $this->db->or_like('phone', $keyword);
            $this->db->or_like('email', $keyword);
            $this->db->or_like('aadhaar_number', $keyword);
            $this->db->group_end();
        }
        
        return $this->db->limit($limit)
                        ->order_by('first_name', 'ASC')
                        ->get($this->table)
                        ->result();
    }
    
    /**
     * Get Active Members for Dropdown
     */
    public function get_active_members_dropdown() {
        $members = $this->db->select('id, member_code, first_name, last_name, phone, kyc_verified')
                        ->where('status', 'active')
                        ->where('deleted_at', null)
                        ->order_by('first_name', 'ASC')
                        ->get($this->table)
                        ->result();

        // Add savings balance and active loans count for each member
        foreach ($members as $member) {
            // Get savings balance
            $savings = $this->db->select('SUM(current_balance) as balance')
                               ->where('member_id', $member->id)
                               ->where('status', 'active')
                               ->get('savings_accounts')
                               ->row();
            $member->savings_balance = $savings ? $savings->balance : 0;

            // Get active loans count
            $member->active_loans = $this->db->where('member_id', $member->id)
                                            ->where_in('status', ['active', 'overdue'])
                                            ->count_all_results('loans');
        }

        return $members;
    }
    
    /**
     * Get Members with Pagination
     */
    public function get_paginated($filters = [], $page = 1, $per_page = 25) {
        // Build base query
        $this->db->from($this->table);
        
        if ($this->soft_delete) {
            $this->db->where('deleted_at', null);
        }
        
        // Apply filters
        if (!empty($filters['status'])) {
            $this->db->where('status', $filters['status']);
        }
        
        if (!empty($filters['membership_type'])) {
            $this->db->where('membership_type', $filters['membership_type']);
        }
        
        if (!empty($filters['kyc_verified'])) {
            $this->db->where('kyc_verified', $filters['kyc_verified']);
        }
        
        if (!empty($filters['search'])) {
            $this->db->group_start();
            $this->db->like('member_code', $filters['search']);
            $this->db->or_like('first_name', $filters['search']);
            $this->db->or_like('last_name', $filters['search']);
            $this->db->or_like('phone', $filters['search']);
            $this->db->group_end();
        }
        
        // Get total count
        $total = $this->db->count_all_results('', false);
        
        // Apply ordering and pagination for records
        $offset = ($page - 1) * $per_page;
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($per_page, $offset);
        $records = $this->db->get()->result();
        
        return [
            'data' => $records,
            'total' => $total,
            'per_page' => $per_page,
            'current_page' => $page,
            'total_pages' => ceil($total / $per_page)
        ];
    }
    
    /**
     * Update Member Status
     */
    public function update_status($id, $status, $reason = null, $changed_by = null) {
        return $this->db->where('id', $id)
                        ->update($this->table, [
                            'status' => $status,
                            'status_reason' => $reason,
                            'status_changed_at' => date('Y-m-d H:i:s'),
                            'status_changed_by' => $changed_by,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
    }
    
    /**
     * Verify KYC
     */
    public function verify_kyc($id, $verified_by) {
        return $this->db->where('id', $id)
                        ->update($this->table, [
                            'kyc_verified' => 1,
                            'kyc_verified_at' => date('Y-m-d H:i:s'),
                            'kyc_verified_by' => $verified_by,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
    }
    
    /**
     * Check Phone Exists
     */
    public function phone_exists($phone, $exclude_id = null) {
        $this->db->where('phone', $phone);
        
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        
        if ($this->soft_delete) {
            $this->db->where('deleted_at', null);
        }
        
        return $this->db->count_all_results($this->table) > 0;
    }
    
    /**
     * Check Aadhaar Exists
     */
    public function aadhaar_exists($aadhaar, $exclude_id = null) {
        if (empty($aadhaar)) return false;
        
        $this->db->where('aadhaar_number', $aadhaar);
        
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        
        if ($this->soft_delete) {
            $this->db->where('deleted_at', null);
        }
        
        return $this->db->count_all_results($this->table) > 0;
    }
    
    /**
     * Get Member Count by Status
     */
    public function get_count_by_status() {
        return $this->db->select('status, COUNT(*) as count')
                        ->where('deleted_at', null)
                        ->group_by('status')
                        ->get($this->table)
                        ->result();
    }
    
    /**
     * Can Be Guarantor Check
     */
    public function can_be_guarantor($member_id, $loan_amount) {
        $member = $this->get_by_id($member_id);
        
        if (!$member || $member->status !== 'active') {
            return ['can_guarantee' => false, 'reason' => 'Member is not active'];
        }
        
        // Get current exposure
        $exposure = $this->get_guarantor_exposure($member_id);
        $current_exposure = $exposure->current_liability ?? 0;
        
        // Get savings balance
        $savings = $this->get_savings_summary($member_id);
        $savings_balance = $savings->current_balance ?? 0;
        
        // Check guarantee count
        if (($exposure->guarantee_count ?? 0) >= $member->max_guarantee_count) {
            return ['can_guarantee' => false, 'reason' => 'Maximum guarantee count reached'];
        }
        
        // Check guarantee amount limit
        $max_allowed = min($member->max_guarantee_amount, $savings_balance * 0.5); // 50% of savings
        $available = $max_allowed - $current_exposure;
        
        if ($loan_amount > $available) {
            return [
                'can_guarantee' => false, 
                'reason' => 'Guarantee limit exceeded. Available: ₹' . number_format($available, 2)
            ];
        }
        
        return [
            'can_guarantee' => true,
            'available_amount' => $available,
            'current_exposure' => $current_exposure
        ];
    }
    
    /**
     * Get KYC Pending Members
     */
    public function get_kyc_pending() {
        return $this->db->where('kyc_verified', 0)
                        ->where('deleted_at', null)
                        ->order_by('created_at', 'DESC')
                        ->get($this->table)
                        ->result();
    }
    
    /**
     * Get Member Ledger Balance
     */
    public function get_ledger_balance($member_id) {
        $result = $this->db->select('
            SUM(credit_amount) as total_credit,
            SUM(debit_amount) as total_debit
        ')
        ->where('member_id', $member_id)
        ->get('member_ledger')
        ->row();
        
        $credit = $result->total_credit ?? 0;
        $debit = $result->total_debit ?? 0;
        
        return $credit - $debit;
    }

    /**
     * Get Members with Email Addresses
     */
    public function get_members_with_email() {
        return $this->db->select('id, member_code, first_name, last_name, email')
                        ->where('status', 'active')
                        ->where('email IS NOT NULL')
                        ->where('email !=', '')
                        ->order_by('first_name', 'ASC')
                        ->get('members')
                        ->result();
    }
}
