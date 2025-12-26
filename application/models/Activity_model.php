<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Activity_model - Activity Log Management
 */
class Activity_model extends MY_Model {
    
    protected $table = 'activity_logs';
    protected $primary_key = 'id';
    
    /**
     * Create Activity Log
     */
    public function create($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }
    
    /**
     * Get Recent Activities
     */
    public function get_recent($limit = 50) {
        return $this->db->order_by('created_at', 'DESC')
                        ->limit($limit)
                        ->get($this->table)
                        ->result();
    }
    
    /**
     * Get User Activities
     */
    public function get_user_activities($user_type, $user_id, $limit = 50) {
        return $this->db->where('user_type', $user_type)
                        ->where('user_id', $user_id)
                        ->order_by('created_at', 'DESC')
                        ->limit($limit)
                        ->get($this->table)
                        ->result();
    }
}
