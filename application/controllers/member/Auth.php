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
            $this->session->set_flashdata('error', 'No account found with that member code. Please check your member code and try again.');
            redirect('member/login');
            return;
        }
        
        // Check password (bcrypt verified)
        if (!password_verify($password, $member->password ?? '')) {
            $this->session->set_flashdata('error', 'Invalid credentials. Please check your member code and password.');
            redirect('member/login');
            return;
        }
        
        // Check if account is active
        if ($member->status !== 'active') {
            $this->session->set_flashdata('error', 'Your account is not active. Please contact administrator.');
            redirect('member/login');
            return;
        }

        // Age check: member must be at least 18
        $this->load->helper('age_helper');
        if (!is_age_at_least($member->date_of_birth, 18)) {
            $this->session->set_flashdata('error', 'the age of the member should be greater than 18 years.');
            redirect('member/login');
            return;
        }
        
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        
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
        
        // Check if password needs to be changed (first login or admin-reset)
        if (!empty($member->must_change_password) && $member->must_change_password == 1) {
            $this->session->set_flashdata('info', 'Please change your password for security.');
            redirect('member/profile/change_password');
            return;
        }
        
        redirect('member/dashboard');
    }
}
