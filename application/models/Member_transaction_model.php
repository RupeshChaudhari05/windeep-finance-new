<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Member_transaction_model - Tracks member other transactions 
 * (membership fees, processing fees, bonuses, rewards, penalties, etc.)
 */
class Member_transaction_model extends MY_Model {
    
    protected $table = 'member_other_transactions';
    protected $primary_key = 'id';
    
    /**
     * Record a member transaction
     */
    public function record($data) {
        $insert = [
            'member_id'          => $data['member_id'],
            'transaction_type'   => $data['transaction_type'],
            'amount'             => $data['amount'],
            'transaction_date'   => $data['transaction_date'] ?? date('Y-m-d'),
            'description'        => $data['description'] ?? null,
            'reference_type'     => $data['reference_type'] ?? null,
            'reference_id'       => $data['reference_id'] ?? null,
            'payment_mode'       => $data['payment_mode'] ?? null,
            'receipt_number'     => $data['receipt_number'] ?? null,
            'bank_transaction_id'=> $data['bank_transaction_id'] ?? null,
            'gl_entry_id'        => $data['gl_entry_id'] ?? null,
            'status'             => $data['status'] ?? 'completed',
            'created_by'         => $data['created_by'] ?? null,
            'created_at'         => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert($this->table, $insert);
        return $this->db->insert_id();
    }
    
    /**
     * Get all transactions for a member
     */
    public function get_member_transactions($member_id, $filters = []) {
        $this->db->where('member_id', $member_id);
        $this->db->where('status !=', 'reversed');
        
        if (!empty($filters['type'])) {
            $this->db->where('transaction_type', $filters['type']);
        }
        if (!empty($filters['from_date'])) {
            $this->db->where('transaction_date >=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $this->db->where('transaction_date <=', $filters['to_date']);
        }
        
        return $this->db->order_by('transaction_date', 'DESC')
                        ->order_by('id', 'DESC')
                        ->get($this->table)
                        ->result();
    }
    
    /**
     * Get summary by transaction type for a member
     */
    public function get_member_summary($member_id) {
        return $this->db->select('transaction_type, COUNT(*) as count, SUM(amount) as total')
                        ->where('member_id', $member_id)
                        ->where('status', 'completed')
                        ->group_by('transaction_type')
                        ->get($this->table)
                        ->result();
    }
    
    /**
     * Get total by type across all members
     */
    public function get_totals_by_type() {
        return $this->db->select('transaction_type, COUNT(*) as count, SUM(amount) as total')
                        ->where('status', 'completed')
                        ->group_by('transaction_type')
                        ->get($this->table)
                        ->result();
    }
    
    /**
     * Record membership fee
     */
    public function record_membership_fee($member_id, $amount, $payment_mode = 'cash', $created_by = null, $extra = []) {
        return $this->record(array_merge([
            'member_id'        => $member_id,
            'transaction_type' => 'membership_fee',
            'amount'           => $amount,
            'description'      => 'Membership fee',
            'payment_mode'     => $payment_mode,
            'created_by'       => $created_by
        ], $extra));
    }
    
    /**
     * Record processing fee
     */
    public function record_processing_fee($member_id, $amount, $loan_id = null, $created_by = null) {
        return $this->record([
            'member_id'        => $member_id,
            'transaction_type' => 'processing_fee',
            'amount'           => $amount,
            'description'      => 'Loan processing fee' . ($loan_id ? ' for Loan #' . $loan_id : ''),
            'reference_type'   => 'loan',
            'reference_id'     => $loan_id,
            'created_by'       => $created_by
        ]);
    }
    
    /**
     * Record bonus/reward credit (for annual bonus distribution)
     */
    public function record_bonus($member_id, $amount, $description = 'Annual bonus', $created_by = null) {
        return $this->record([
            'member_id'        => $member_id,
            'transaction_type' => 'bonus',
            'amount'           => $amount,
            'description'      => $description,
            'created_by'       => $created_by
        ]);
    }
    
    /**
     * Get all transactions across all members (for reports)
     */
    public function get_all_transactions($filters = []) {
        $this->db->select('mot.*, m.member_code, m.first_name, m.last_name, m.phone')
                 ->from($this->table . ' as mot')
                 ->join('members as m', 'm.id = mot.member_id', 'left')
                 ->where('mot.status !=', 'reversed');

        if (!empty($filters['member_id'])) {
            $this->db->where('mot.member_id', $filters['member_id']);
        }
        if (!empty($filters['type'])) {
            $this->db->where('mot.transaction_type', $filters['type']);
        }
        if (!empty($filters['from_date'])) {
            $this->db->where('mot.transaction_date >=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $this->db->where('mot.transaction_date <=', $filters['to_date']);
        }
        if (!empty($filters['payment_mode'])) {
            $this->db->where('mot.payment_mode', $filters['payment_mode']);
        }

        return $this->db->order_by('mot.transaction_date', 'DESC')
                        ->order_by('mot.id', 'DESC')
                        ->get()
                        ->result();
    }

    /**
     * Reverse a transaction
     */
    public function reverse($id, $reason, $reversed_by = null) {
        return $this->db->where('id', $id)
                        ->update($this->table, [
                            'status'          => 'reversed',
                            'reversed_at'     => date('Y-m-d H:i:s'),
                            'reversed_by'     => $reversed_by,
                            'reversal_reason' => $reason
                        ]);
    }
}
