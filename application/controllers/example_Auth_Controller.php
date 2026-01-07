<?php
/**
 * Example: Secure Login Controller with Rate Limiting
 * Place in application/controllers/Auth.php
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->library('rate_limiter');
        $this->load->library('session');
    }
    
    /**
     * Login with Rate Limiting and Security
     */
    public function login() {
        // GET request: Show login form
        if ($this->input->server('REQUEST_METHOD') === 'GET') {
            $this->load->view('auth/login');
            return;
        }
        
        // POST request: Process login
        $email = $this->input->post('email', TRUE); // XSS filter
        $password = $this->input->post('password'); // Don't XSS filter passwords
        
        // Validate CSRF token (automatic if enabled in config)
        
        // Rate limiting check
        $identifier = $this->input->ip_address();
        $rate_check = $this->rate_limiter->check_and_record($identifier, 'login', [
            'max_attempts' => 5,
            'window' => 300, // 5 minutes
            'lockout_time' => 900 // 15 minutes
        ]);
        
        if (!$rate_check['allowed']) {
            if ($rate_check['reason'] === 'locked_out') {
                $this->session->set_flashdata('error', 'Too many failed login attempts. Account locked for 15 minutes.');
                
                // Log the lockout
                $this->log_security_event('login_locked', $email, [
                    'ip' => $identifier,
                    'unlock_at' => $rate_check['unlock_at']
                ]);
            } else {
                $this->session->set_flashdata('error', 'Too many login attempts. Please try again in ' . ceil($rate_check['retry_after'] / 60) . ' minutes.');
            }
            
            redirect('auth/login');
            return;
        }
        
        // Validate input
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email');
        $this->form_validation->set_rules('password', 'Password', 'required|min_length[6]');
        
        if ($this->form_validation->run() === FALSE) {
            $this->load->view('auth/login');
            return;
        }
        
        // Attempt login
        $user = $this->User_model->login($email, $password);
        
        if ($user) {
            // Success: Reset rate limit
            $this->rate_limiter->reset($identifier, 'login');
            
            // Set session
            $this->session->set_userdata([
                'user_id' => $user->id,
                'email' => $user->email,
                'name' => $user->full_name,
                'role' => $user->role,
                'logged_in' => TRUE,
                'login_time' => time()
            ]);
            
            // Regenerate session ID (prevent fixation)
            $this->session->sess_regenerate(TRUE);
            
            // Log successful login
            $this->log_security_event('login_success', $email, [
                'ip' => $identifier,
                'user_id' => $user->id
            ]);
            
            // Update last login
            $this->User_model->update_last_login($user->id);
            
            redirect('dashboard');
        } else {
            // Failed login
            $attempts_left = $rate_check['remaining'];
            
            $this->session->set_flashdata('error', 'Invalid email or password. ' . $attempts_left . ' attempts remaining.');
            
            // Log failed login
            $this->log_security_event('login_failed', $email, [
                'ip' => $identifier,
                'attempts_left' => $attempts_left
            ]);
            
            redirect('auth/login');
        }
    }
    
    /**
     * Logout
     */
    public function logout() {
        $user_id = $this->session->userdata('user_id');
        $email = $this->session->userdata('email');
        
        // Log logout
        $this->log_security_event('logout', $email, [
            'user_id' => $user_id,
            'ip' => $this->input->ip_address()
        ]);
        
        // Destroy session
        $this->session->sess_destroy();
        
        redirect('auth/login');
    }
    
    /**
     * Check if user is logged in (use in other controllers)
     */
    public function require_login() {
        if (!$this->session->userdata('logged_in')) {
            $this->session->set_flashdata('error', 'Please login to continue');
            redirect('auth/login');
        }
        
        // Check session timeout (2 hours)
        $login_time = $this->session->userdata('login_time');
        if (time() - $login_time > 7200) {
            $this->logout();
        }
    }
    
    /**
     * Check if user has required role
     */
    public function require_role($required_role) {
        $this->require_login();
        
        $user_role = $this->session->userdata('role');
        
        if ($user_role !== $required_role) {
            $this->session->set_flashdata('error', 'Access denied. Insufficient permissions.');
            redirect('dashboard');
        }
    }
    
    /**
     * Log security events to database
     */
    private function log_security_event($event_type, $email, $details = []) {
        $data = [
            'event_type' => $event_type,
            'email' => $email,
            'ip_address' => $this->input->ip_address(),
            'user_agent' => $this->input->user_agent(),
            'details' => json_encode($details),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('security_logs', $data);
    }
}
