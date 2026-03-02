<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * NonMember_model - Non-Member Fund Provider Management
 */
class NonMember_model extends CI_Model {

    protected $table = 'non_members';
    protected $funds_table = 'non_member_funds';

    /**
     * Get all non-members with optional filters and pagination
     */
    public function get_paginated($filters = [], $page = 1, $per_page = 20) {
        $this->db->from($this->table);

        if (!empty($filters['status'])) {
            $this->db->where('status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $this->db->group_start()
                     ->like('name', $filters['search'])
                     ->or_like('phone', $filters['search'])
                     ->or_like('email', $filters['search'])
                     ->group_end();
        }

        $total = $this->db->count_all_results('', false);
        $total_pages = ceil($total / $per_page);
        $page = max(1, min($page, $total_pages ?: 1));
        $offset = ($page - 1) * $per_page;

        $data = $this->db->order_by('created_at', 'DESC')
                         ->limit($per_page, $offset)
                         ->get()
                         ->result();

        // Attach fund totals
        foreach ($data as &$row) {
            $row->total_received = $this->get_fund_total($row->id, 'received');
            $row->total_returned = $this->get_fund_total($row->id, 'returned');
            $row->balance = $row->total_received - $row->total_returned;
        }

        return [
            'data' => $data,
            'total' => $total,
            'current_page' => $page,
            'per_page' => $per_page,
            'total_pages' => $total_pages
        ];
    }

    /**
     * Get all non-members (no pagination)
     */
    public function get_all($status = null) {
        if ($status) {
            $this->db->where('status', $status);
        }
        return $this->db->order_by('name', 'ASC')->get($this->table)->result();
    }

    /**
     * Get single non-member by ID
     */
    public function get_by_id($id) {
        $nm = $this->db->where('id', $id)->get($this->table)->row();
        if ($nm) {
            $nm->total_received = $this->get_fund_total($id, 'received');
            $nm->total_returned = $this->get_fund_total($id, 'returned');
            $nm->balance = $nm->total_received - $nm->total_returned;
        }
        return $nm;
    }

    /**
     * Create non-member
     */
    public function create($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    /**
     * Update non-member
     */
    public function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    /**
     * Delete non-member (only if no funds exist)
     */
    public function delete($id) {
        $fund_count = $this->db->where('non_member_id', $id)->count_all_results($this->funds_table);
        if ($fund_count > 0) {
            return ['success' => false, 'message' => 'Cannot delete: this fund provider has transaction records. You can deactivate instead.'];
        }
        $this->db->where('id', $id)->delete($this->table);
        return ['success' => true];
    }

    // ═══════════════════════════════════════════════════════════
    // Fund Transactions
    // ═══════════════════════════════════════════════════════════

    /**
     * Get fund total by type
     */
    public function get_fund_total($non_member_id, $type = 'received') {
        $result = $this->db->select('COALESCE(SUM(amount), 0) as total')
                           ->where('non_member_id', $non_member_id)
                           ->where('transaction_type', $type)
                           ->get($this->funds_table)
                           ->row();
        return $result->total ?? 0;
    }

    /**
     * Get fund transactions for a non-member
     */
    public function get_funds($non_member_id, $limit = 0) {
        $this->db->where('non_member_id', $non_member_id)
                 ->order_by('transaction_date', 'DESC')
                 ->order_by('created_at', 'DESC');
        if ($limit > 0) {
            $this->db->limit($limit);
        }
        return $this->db->get($this->funds_table)->result();
    }

    /**
     * Add fund transaction
     */
    public function add_fund($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->funds_table, $data);
        return $this->db->insert_id();
    }

    /**
     * Delete fund transaction
     */
    public function delete_fund($fund_id) {
        return $this->db->where('id', $fund_id)->delete($this->funds_table);
    }

    /**
     * Get a single fund record
     */
    public function get_fund_by_id($fund_id) {
        return $this->db->where('id', $fund_id)->get($this->funds_table)->row();
    }

    // ═══════════════════════════════════════════════════════════
    // Dashboard / Reporting
    // ═══════════════════════════════════════════════════════════

    /**
     * Get dashboard summary
     */
    public function get_dashboard_summary() {
        $total_received = $this->db->select('COALESCE(SUM(amount), 0) as total')
                                   ->where('transaction_type', 'received')
                                   ->get($this->funds_table)->row()->total ?? 0;

        $total_returned = $this->db->select('COALESCE(SUM(amount), 0) as total')
                                   ->where('transaction_type', 'returned')
                                   ->get($this->funds_table)->row()->total ?? 0;

        $active_providers = $this->db->where('status', 'active')
                                     ->count_all_results($this->table);

        return [
            'total_received' => $total_received,
            'total_returned' => $total_returned,
            'outstanding' => $total_received - $total_returned,
            'active_providers' => $active_providers
        ];
    }
}
