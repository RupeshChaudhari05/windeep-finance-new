<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {

    public function get_all_users() {
        $query = $this->db->get('users');
        return $query->result_array();
    }

    public function update_status($user_id, $status) {
        $this->db->where('user_id', $user_id);
        $this->db->update('users', array('status' => $status));
    }




    /**
     * User Login
     * Security Fix: Use password_verify() instead of MD5
     */
    public function login($email, $password) {
        $this->db->where('email', $email);
        $query = $this->db->get('admin');
        
        if ($query->num_rows() == 1) {
            $user = $query->row();
            
            // Check if password is still MD5 (migration compatibility)
            if (strlen($user->password) == 32 && ctype_xdigit($user->password)) {
                // Legacy MD5 password - verify and upgrade
                if (md5($password) === $user->password) {
                    // Rehash with bcrypt
                    $new_hash = password_hash($password, PASSWORD_BCRYPT);
                    $this->db->where('id', $user->id)
                             ->update('admin', ['password' => $new_hash]);
                    
                    return $user;
                }
            } else {
                // Modern bcrypt password
                if (password_verify($password, $user->password)) {
                    // Check if rehash needed (algorithm upgraded)
                    if (password_needs_rehash($user->password, PASSWORD_BCRYPT)) {
                        $new_hash = password_hash($password, PASSWORD_BCRYPT);
                        $this->db->where('id', $user->id)
                                 ->update('admin', ['password' => $new_hash]);
                    }
                    
                    return $user;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Create User with Secure Password
     */
    public function create_user($data) {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        
        $data['created_at'] = date('Y-m-d H:i:s');
        
        return $this->db->insert('admin', $data);
    }
    
    /**
     * Update User Password
     */
    public function update_password($user_id, $new_password) {
        $hashed = password_hash($new_password, PASSWORD_BCRYPT);
        
        return $this->db->where('id', $user_id)
                        ->update('admin', ['password' => $hashed]);
    }

    public function get_user_by_email($email) {
        $this->db->where('email', $email);
        $query = $this->db->get('users');
        
        if ($query->num_rows() == 1) {
            return $query->row();
        } else {
            return false;
        }
    }

     public function insert_user($email) {
        $data = array(
            'email' => $email,
            // Set default values for other fields or remove them from the insert
            'created_at' => date('Y-m-d H:i:s'),
            'status' => 'No' // or any default value you prefer
        );
        
        return $this->db->insert('users', $data);
    }

}
