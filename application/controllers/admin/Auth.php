<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Auth Controller - Admin Authentication
 */
class Auth extends MY_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('Admin_model');
    }
    
    /**
     * Login Page
     */
    public function index() {
        $this->login_form();
    }
    
    /**
     * Login Form
     */
    public function login_form() {
        // Redirect if already logged in
        if ($this->session->userdata('admin_logged_in')) {
            redirect('admin/dashboard');
        }
        
        $this->load->view('admin/auth/login');
    }
    
    /**
     * Login Process
     */
    public function login() {
        if ($this->input->method() !== 'post') {
            redirect('admin/auth');
        }
        
        $email = $this->input->post('email', TRUE);
        $password = $this->input->post('password');
        $remember = $this->input->post('remember');
        
        // Validate
        $this->load->library('form_validation');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email');
        $this->form_validation->set_rules('password', 'Password', 'required');
        
        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('admin/auth');
        }
        
        // Authenticate
        $auth = $this->Admin_model->authenticate($email, $password);
        
        if (isset($auth['error'])) {
            $this->log_activity('Failed login attempt', 'email: ' . $email);
            $this->session->set_flashdata('error', $auth['error']);
            redirect('admin/auth');
        }
        
        $admin = $auth['admin'];

        // Age check for admin (only if DOB exists)
        $this->load->helper('age_helper');
        if (isset($admin->date_of_birth) && !empty($admin->date_of_birth)) {
            if (!is_age_at_least($admin->date_of_birth, 18)) {
                $this->log_activity('Failed login attempt', 'email: ' . $email . ' (underage)');
                $this->session->set_flashdata('error', 'the age of the member should be greater than 18 years.');
                redirect('admin/auth');
                return;
            }
        }
        
        // Create session
        $session_data = [
            'admin_id' => $admin->id,
            'admin_name' => $admin->full_name,
            'admin_email' => $admin->email,
            'admin_role' => $admin->role,
            'admin_logged_in' => TRUE
        ];
        
        $this->session->set_userdata($session_data);
        
        // Set admin data for logging
        $this->admin_data = $admin;
        
        // Log activity
        $this->log_activity('Admin login', 'IP: ' . $this->input->ip_address());
        
        redirect('admin/dashboard');
    }
    
    /**
     * Logout
     */
    public function logout() {
        $admin_id = $this->session->userdata('admin_id');
        
        if ($admin_id) {
            $this->log_activity('admin', $admin_id, 'Admin logout');
        }
        
        // Destroy session
        $this->session->unset_userdata(['admin_id', 'admin_name', 'admin_email', 'admin_role', 'admin_logged_in']);
        $this->session->set_flashdata('success', 'You have been logged out successfully.');
        
        redirect('admin/auth');
    }
    
    /**
     * Forgot Password
     */
    public function forgot_password() {
        $this->load->view('admin/auth/forgot_password');
    }
    
    /**
     * Send Password Reset
     */
    public function send_reset() {
        if ($this->input->method() !== 'post') {
            redirect('admin/auth/forgot_password');
        }
        
        $email = $this->input->post('email', TRUE);
        
        $admin = $this->db->where('email', $email)
                          ->where('is_active', 1)
                          ->get('admin_users')
                          ->row();
        
        if (!$admin) {
            $this->session->set_flashdata('error', 'No account found with that email address.');
            redirect('admin/auth/forgot_password');
        }
        
        // Generate token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', safe_timestamp('+1 hour'));
        
        $this->db->where('id', $admin->id)
                 ->update('admin_users', [
                     'reset_token' => $token,
                     'reset_token_expiry' => $expiry
                 ]);
        
        // TODO: Send email with reset link
        // For now, show success message
        $this->session->set_flashdata('success', 'Password reset instructions have been sent to your email.');
        redirect('admin/auth');
    }
    
    /**
     * Reset Password Form
     */
    public function reset_password($token) {
        $admin = $this->db->where('reset_token', $token)
                          ->where('reset_token_expiry >', date('Y-m-d H:i:s'))
                          ->where('is_active', 1)
                          ->get('admin_users')
                          ->row();
        
        if (!$admin) {
            $this->session->set_flashdata('error', 'Invalid or expired reset link.');
            redirect('admin/auth');
        }
        
        $data['token'] = $token;
        $this->load->view('admin/auth/reset_password', $data);
    }
    
    /**
     * Process Password Reset
     */
    public function process_reset() {
        if ($this->input->method() !== 'post') {
            redirect('admin/auth');
        }
        
        $token = $this->input->post('token');
        $password = $this->input->post('password');
        $confirm = $this->input->post('confirm_password');
        
        if ($password !== $confirm) {
            $this->session->set_flashdata('error', 'Passwords do not match.');
            redirect('admin/auth/reset_password/' . $token);
        }
        
        if (strlen($password) < 6) {
            $this->session->set_flashdata('error', 'Password must be at least 6 characters.');
            redirect('admin/auth/reset_password/' . $token);
        }
        
        $admin = $this->db->where('reset_token', $token)
                          ->where('reset_token_expiry >', date('Y-m-d H:i:s'))
                          ->where('is_active', 1)
                          ->get('admin_users')
                          ->row();
        
        if (!$admin) {
            $this->session->set_flashdata('error', 'Invalid or expired reset link.');
            redirect('admin/auth');
        }
        
        // Update password
        $this->db->where('id', $admin->id)
                 ->update('admin_users', [
                     'password' => password_hash($password, PASSWORD_DEFAULT),
                     'reset_token' => NULL,
                     'reset_token_expiry' => NULL,
                     'updated_at' => date('Y-m-d H:i:s')
                 ]);
        
        $this->log_activity('admin', $admin->id, 'Password reset');
        
        $this->session->set_flashdata('success', 'Password has been reset. Please login with your new password.');
        redirect('admin/auth');
    }
    
    /**
     * Change Password
     */
    public function change_password() {
        if (!$this->session->userdata('admin_logged_in')) {
            redirect('admin/auth');
        }
        
        if ($this->input->method() !== 'post') {
            $this->load->view('admin/auth/change_password');
            return;
        }
        
        $admin_id = $this->session->userdata('admin_id');
        $current = $this->input->post('current_password');
        $new = $this->input->post('new_password');
        $confirm = $this->input->post('confirm_password');
        
        // Validate current password
        $admin = $this->db->where('id', $admin_id)->get('admin_users')->row();
        
        if (!password_verify($current, $admin->password)) {
            $this->session->set_flashdata('error', 'Current password is incorrect.');
            redirect('admin/auth/change_password');
        }
        
        if ($new !== $confirm) {
            $this->session->set_flashdata('error', 'New passwords do not match.');
            redirect('admin/auth/change_password');
        }
        
        if (strlen($new) < 6) {
            $this->session->set_flashdata('error', 'Password must be at least 6 characters.');
            redirect('admin/auth/change_password');
        }
        
        // Update
        $this->Admin_model->update_password($admin_id, $new);
        $this->log_activity('admin', $admin_id, 'Password changed');
        
        $this->session->set_flashdata('success', 'Password changed successfully.');
        redirect('admin/dashboard');
    }
}
