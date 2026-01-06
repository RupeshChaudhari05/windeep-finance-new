<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Member Auth Controller - Member Login & Authentication
 */
class Auth extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url', 'form']);
        $this->load->model('Member_model');
    }
    
    /**
     * Login Page
     */
    public function login() {
        // If already logged in, redirect to dashboard
        if ($this->session->userdata('member_logged_in')) {
            redirect('member/dashboard');
        }
        
        // Handle form submission
        if ($this->input->method() === 'post') {
            $this->_process_login();
            return;
        }
        
        $data['title'] = 'Member Login';
        $this->load->view('member/auth/login', $data);
    }
    
    /**
     * Logout
     */
    public function logout() {
        // Redirect with a flag so login page can display a message after session is destroyed
        $redirect = site_url('member/login?logged_out=1');

        // Destroy entire session to ensure logout
        $this->session->sess_destroy();

        // Redirect to login with a query flag
        redirect($redirect);
    }
    
    /**
     * Process Login
     */
    private function _process_login() {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('identifier', 'Member Code', 'required');
        $this->form_validation->set_rules('password', 'Password', 'required');
        
        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('member/login');
            return;
        }
        
        $identifier = $this->input->post('identifier');
        $password = $this->input->post('password');
        
        // Find member by member_code only
        $member = $this->db->where('member_code', $identifier)
                           ->get('members')
                           ->row();
        
        if (!$member) {
            $this->session->set_flashdata('error', 'Invalid credentials.');
            redirect('member/login');
            return;
        }
        
        // Check password (assuming password_hash is used)
        if (!password_verify($password, $member->password ?? '')) {
            // Fallback: Check if password matches member_code (default password)
            if ($password !== $member->member_code) {
                $this->session->set_flashdata('error', 'Invalid credentials.');
                redirect('member/login');
                return;
            }
        }
        
        // Check if account is active
        if ($member->status !== 'active') {
            $this->session->set_flashdata('error', 'Your account is not active. Please contact administrator.');
            redirect('member/login');
            return;
        }
        
        // Set session data
        $this->session->set_userdata([
            'member_id' => $member->id,
            'member_logged_in' => true,
            'member_code' => $member->member_code,
            'member_name' => $member->first_name . ' ' . $member->last_name
        ]);
        
        // Update last login
        $this->db->where('id', $member->id)
                 ->update('members', ['last_login' => date('Y-m-d H:i:s')]);
        
        redirect('member/dashboard');
    }
}
