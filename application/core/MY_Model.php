<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MY_Model - Base Model
 * 
 * Provides common CRUD operations and utility methods
 */
class MY_Model extends CI_Model {
    
    protected $table = '';
    protected $primary_key = 'id';
    protected $fillable = [];
    protected $hidden = ['password'];
    protected $timestamps = true;
    protected $soft_delete = false;
    protected $soft_delete_field = 'deleted_at';
    
    public function __construct() {
        // parent::__construct(); // Commented out to avoid PHP 8 constructor issue
    }
    
    /**
     * Get All Records
     */
    public function get_all($conditions = [], $order_by = null, $limit = null, $offset = null) {
        if ($this->soft_delete) {
            $this->db->where($this->soft_delete_field, null);
        }
        
        if (!empty($conditions)) {
            $this->db->where($conditions);
        }
        
        if ($order_by) {
            if (is_array($order_by)) {
                foreach ($order_by as $field => $direction) {
                    $this->db->order_by($field, $direction);
                }
            } else {
                $this->db->order_by($order_by);
            }
        }
        
        if ($limit !== null) {
            $this->db->limit($limit, $offset);
        }
        
        return $this->db->get($this->table)->result();
    }
    
    /**
     * Get Single Record by ID
     */
    public function get_by_id($id) {
        if ($this->soft_delete) {
            $this->db->where($this->soft_delete_field, null);
        }
        
        return $this->db->where($this->primary_key, $id)
                        ->get($this->table)
                        ->row();
    }
    
    /**
     * Get Single Record by Conditions
     */
    public function get_by($conditions) {
        if ($this->soft_delete) {
            $this->db->where($this->soft_delete_field, null);
        }
        
        return $this->db->where($conditions)
                        ->get($this->table)
                        ->row();
    }
    
    /**
     * Get Multiple Records by Conditions
     */
    public function get_where($conditions, $order_by = null) {
        if ($this->soft_delete) {
            $this->db->where($this->soft_delete_field, null);
        }
        
        $this->db->where($conditions);
        
        if ($order_by) {
            $this->db->order_by($order_by);
        }
        
        return $this->db->get($this->table)->result();
    }
    
    /**
     * Create New Record
     */
    public function create($data) {
        // Filter to fillable fields
        if (!empty($this->fillable)) {
            $data = array_intersect_key($data, array_flip($this->fillable));
        }
        
        // Add timestamps
        if ($this->timestamps) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        
        $this->db->insert($this->table, $data);
        
        return $this->db->insert_id();
    }
    
    /**
     * Update Record
     */
    public function update($id, $data) {
        // Filter to fillable fields
        if (!empty($this->fillable)) {
            $data = array_intersect_key($data, array_flip($this->fillable));
        }
        
        // Add timestamp
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->db->where($this->primary_key, $id)
                        ->update($this->table, $data);
    }
    
    /**
     * Update by Conditions
     */
    public function update_where($conditions, $data) {
        if (!empty($this->fillable)) {
            $data = array_intersect_key($data, array_flip($this->fillable));
        }
        
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->db->where($conditions)
                        ->update($this->table, $data);
    }
    
    /**
     * Delete Record (Soft or Hard)
     */
    public function delete($id) {
        if ($this->soft_delete) {
            return $this->db->where($this->primary_key, $id)
                            ->update($this->table, [
                                $this->soft_delete_field => date('Y-m-d H:i:s')
                            ]);
        }
        
        return $this->db->where($this->primary_key, $id)
                        ->delete($this->table);
    }
    
    /**
     * Hard Delete (Force)
     */
    public function force_delete($id) {
        return $this->db->where($this->primary_key, $id)
                        ->delete($this->table);
    }
    
    /**
     * Restore Soft Deleted Record
     */
    public function restore($id) {
        if (!$this->soft_delete) {
            return false;
        }
        
        return $this->db->where($this->primary_key, $id)
                        ->update($this->table, [
                            $this->soft_delete_field => null
                        ]);
    }
    
    /**
     * Count Records
     */
    public function count($conditions = []) {
        if ($this->soft_delete) {
            $this->db->where($this->soft_delete_field, null);
        }
        
        if (!empty($conditions)) {
            $this->db->where($conditions);
        }
        
        return $this->db->count_all_results($this->table);
    }
    
    /**
     * Check if Record Exists
     */
    public function exists($conditions) {
        if ($this->soft_delete) {
            $this->db->where($this->soft_delete_field, null);
        }
        
        return $this->db->where($conditions)
                        ->count_all_results($this->table) > 0;
    }
    
    /**
     * Get Last Inserted ID
     */
    public function last_id() {
        return $this->db->insert_id();
    }
    
    /**
     * Begin Transaction
     */
    public function begin_transaction() {
        $this->db->trans_begin();
    }
    
    /**
     * Commit Transaction
     */
    public function commit_transaction() {
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        }
        
        $this->db->trans_commit();
        return true;
    }
    
    /**
     * Rollback Transaction
     */
    public function rollback_transaction() {
        $this->db->trans_rollback();
    }
    
    /**
     * Raw Query
     */
    public function query($sql, $bindings = []) {
        return $this->db->query($sql, $bindings);
    }
    
    /**
     * Get Sum
     */
    public function sum($field, $conditions = []) {
        if ($this->soft_delete) {
            $this->db->where($this->soft_delete_field, null);
        }
        
        if (!empty($conditions)) {
            $this->db->where($conditions);
        }
        
        $this->db->select_sum($field);
        $result = $this->db->get($this->table)->row();
        
        return $result->$field ?: 0;
    }
    
    /**
     * Increment Field
     */
    public function increment($id, $field, $value = 1) {
        $this->db->set($field, "$field + $value", FALSE);
        return $this->db->where($this->primary_key, $id)
                        ->update($this->table);
    }
    
    /**
     * Decrement Field
     */
    public function decrement($id, $field, $value = 1) {
        $this->db->set($field, "$field - $value", FALSE);
        return $this->db->where($this->primary_key, $id)
                        ->update($this->table);
    }
    
    /**
     * Generate Unique Code
     */
    protected function generate_code($prefix, $field = null, $length = 6) {
        $field = $field ?: $this->primary_key;
        
        $max = $this->db->select_max($field)
                        ->get($this->table)
                        ->row();
        
        $next_number = ($max->$field ?: 0) + 1;
        
        return $prefix . str_pad($next_number, $length, '0', STR_PAD_LEFT);
    }
    
    /**
     * Paginate Results
     */
    public function paginate($page = 1, $per_page = 25, $conditions = [], $order_by = null) {
        $offset = ($page - 1) * $per_page;
        
        // Get total count
        $total = $this->count($conditions);
        
        // Get records
        $records = $this->get_all($conditions, $order_by, $per_page, $offset);
        
        return [
            'data' => $records,
            'total' => $total,
            'per_page' => $per_page,
            'current_page' => $page,
            'total_pages' => ceil($total / $per_page)
        ];
    }
    
    /**
     * Search Records
     */
    public function search($keyword, $fields = [], $conditions = [], $order_by = null, $limit = 50) {
        if ($this->soft_delete) {
            $this->db->where($this->soft_delete_field, null);
        }
        
        if (!empty($conditions)) {
            $this->db->where($conditions);
        }
        
        if (!empty($fields) && !empty($keyword)) {
            $this->db->group_start();
            foreach ($fields as $index => $field) {
                if ($index === 0) {
                    $this->db->like($field, $keyword);
                } else {
                    $this->db->or_like($field, $keyword);
                }
            }
            $this->db->group_end();
        }
        
        if ($order_by) {
            $this->db->order_by($order_by);
        }
        
        if ($limit) {
            $this->db->limit($limit);
        }
        
        return $this->db->get($this->table)->result();
    }
}
