<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin_model - Admin User Management
 */
class Admin_model extends MY_Model {
    
    protected $table = 'admin_users';
    protected $primary_key = 'id';
    protected $fillable = [
        'username', 'email', 'password', 'full_name', 'phone',
        'role', 'permissions', 'is_active'
    ];
    
    /**
     * Authenticate Admin
     */
    public function authenticate($username, $password) {
        $admin = $this->db->where('username', $username)
                          ->or_where('email', $username)
                          ->get($this->table)
                          ->row();
        
        if ($admin && password_verify($password, $admin->password)) {
            if ($admin->is_active != 1) {
                return ['error' => 'Account is inactive'];
            }
            
            // Update last login
            $this->db->where('id', $admin->id)
                     ->update($this->table, [
                         'last_login' => date('Y-m-d H:i:s'),
                         'last_login_ip' => $this->input->ip_address()
                     ]);
            
            return ['success' => true, 'admin' => $admin];
        }
        
        return ['error' => 'Invalid username or password'];
    }
    
    /**
     * Create Admin User
     */
    public function create_admin($data) {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['created_at'] = date('Y-m-d H:i:s');
        
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }
    
    /**
     * Update Password
     */
    public function update_password($id, $new_password) {
        return $this->db->where('id', $id)
                        ->update($this->table, [
                            'password' => password_hash($new_password, PASSWORD_DEFAULT),
                            'password_changed_at' => date('Y-m-d H:i:s')
                        ]);
    }
    
    /**
     * Get All Admins
     */
    public function get_all_admins() {
        return $this->db->select('id, username, email, full_name, phone, role, is_active, last_login, created_at')
                        ->order_by('created_at', 'DESC')
                        ->get($this->table)
                        ->result();
    }
    
    /**
     * Check Username Exists
     */
    public function username_exists($username, $exclude_id = null) {
        $this->db->where('username', $username);
        
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        
        return $this->db->count_all_results($this->table) > 0;
    }
    
    /**
     * Check Email Exists
     */
    public function email_exists($email, $exclude_id = null) {
        $this->db->where('email', $email);
        
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        
        return $this->db->count_all_results($this->table) > 0;
    }
}
