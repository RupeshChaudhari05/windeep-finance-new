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




    public function login($email, $password) {
        $this->db->where('email', $email);
        $this->db->where('password',$password); // Using md5 for simplicity, consider using a stronger hash function
        $query = $this->db->get('admin');
        //print_r($query);die;
        if ($query->num_rows() == 1) {
            return $query->row();
        } else {
            return false;
        }
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
