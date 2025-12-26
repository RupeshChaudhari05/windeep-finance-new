<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Audit_model - Audit Trail Management
 */
class Audit_model extends MY_Model {
    
    protected $table = 'audit_logs';
    protected $primary_key = 'id';
    
    /**
     * Create Audit Log
     */
    public function create($data) {
        $data['audit_code'] = $this->generate_audit_code();
        $data['created_at'] = date('Y-m-d H:i:s');
        
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }
    
    /**
     * Generate Unique Audit Code
     */
    private function generate_audit_code() {
        return 'AUD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -8));
    }
    
    /**
     * Get Audit Logs for Record
     */
    public function get_for_record($table_name, $record_id) {
        return $this->db->select('audit_logs.*, admin_users.full_name as user_name')
                        ->join('admin_users', 'admin_users.id = audit_logs.user_id AND audit_logs.user_type = "admin"', 'left')
                        ->where('audit_logs.table_name', $table_name)
                        ->where('audit_logs.record_id', $record_id)
                        ->order_by('audit_logs.created_at', 'DESC')
                        ->get($this->table)
                        ->result();
    }
    
    /**
     * Get Audit Logs by Module
     */
    public function get_by_module($module, $limit = 100) {
        return $this->db->select('audit_logs.*, admin_users.full_name as user_name')
                        ->join('admin_users', 'admin_users.id = audit_logs.user_id AND audit_logs.user_type = "admin"', 'left')
                        ->where('audit_logs.module', $module)
                        ->order_by('audit_logs.created_at', 'DESC')
                        ->limit($limit)
                        ->get($this->table)
                        ->result();
    }
    
    /**
     * Get Audit Logs by User
     */
    public function get_by_user($user_type, $user_id, $limit = 100) {
        return $this->db->where('user_type', $user_type)
                        ->where('user_id', $user_id)
                        ->order_by('created_at', 'DESC')
                        ->limit($limit)
                        ->get($this->table)
                        ->result();
    }
    
    /**
     * Get Recent Audit Logs
     */
    public function get_recent($limit = 50) {
        return $this->db->select('audit_logs.*, admin_users.full_name as user_name')
                        ->join('admin_users', 'admin_users.id = audit_logs.user_id AND audit_logs.user_type = "admin"', 'left')
                        ->order_by('audit_logs.created_at', 'DESC')
                        ->limit($limit)
                        ->get($this->table)
                        ->result();
    }
    
    /**
     * Search Audit Logs
     */
    public function search_audit_logs($filters = [], $page = 1, $per_page = 50) {
        // Build base query
        $this->db->select('audit_logs.*, admin_users.full_name as user_name');
        $this->db->from($this->table);
        $this->db->join('admin_users', 'admin_users.id = audit_logs.user_id AND audit_logs.user_type = "admin"', 'left');
        
        if (!empty($filters['module'])) {
            $this->db->where('audit_logs.module', $filters['module']);
        }
        
        if (!empty($filters['action'])) {
            $this->db->where('audit_logs.action', $filters['action']);
        }
        
        if (!empty($filters['user_id'])) {
            $this->db->where('audit_logs.user_id', $filters['user_id']);
        }
        
        if (!empty($filters['from_date'])) {
            $this->db->where('audit_logs.created_at >=', $filters['from_date'] . ' 00:00:00');
        }
        
        if (!empty($filters['to_date'])) {
            $this->db->where('audit_logs.created_at <=', $filters['to_date'] . ' 23:59:59');
        }
        
        // Get total count
        $total = $this->db->count_all_results('', false);
        
        // Apply ordering and pagination for records
        $offset = ($page - 1) * $per_page;
        $this->db->order_by('audit_logs.created_at', 'DESC');
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
}
