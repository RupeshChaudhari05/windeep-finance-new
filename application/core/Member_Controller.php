<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!class_exists('Member_Controller')) {
/**
 * Member_Controller - Base Controller for Member Portal
 * Handles member authentication and session management
 */
class Member_Controller extends MY_Controller {
    
    protected $member = null;
    
    public function __construct() {
        parent::__construct();
        
        // Check if member is logged in
        if (!$this->session->userdata('member_logged_in')) {
            redirect('member/auth/login');
        }
        
        // Enforce session timeout (default 30 minutes)
        $timeout = (int) ($this->settings['session_timeout'] ?? 1800);
        $last_activity = $this->session->userdata('member_last_activity');
        if ($last_activity && (time() - $last_activity) > $timeout) {
            $this->session->sess_destroy();
            $this->session->set_flashdata('error', 'Your session has expired due to inactivity. Please login again.');
            redirect('member/auth/login');
        }
        $this->session->set_userdata('member_last_activity', time());
        
        // Load member data
        $member_id = $this->session->userdata('member_id');
        $this->load->model('Member_model');
        $this->member = $this->Member_model->get_by_id($member_id);
        
        if (!$this->member || $this->member->status !== 'active') {
            $this->session->set_flashdata('error', 'Your account is not active. Please contact administrator.');
            $this->session->sess_destroy();
            redirect('member/auth/login');
        }
    }
    
    /**
     * Public wrapper for audit logging so member controllers can call it directly
     * This forwards to the protected implementation in MY_Controller
     */
    public function log_audit($action, $module, $table_name, $record_id, $old_values = null, $new_values = null, $remarks = null) {
        return parent::log_audit($action, $module, $table_name, $record_id, $old_values, $new_values, $remarks);
    }

    /**
     * Load View with Member Layout
     */
    protected function load_member_view($view, $data = []) {
        $data['member'] = $this->member;
        $data['settings'] = $this->settings;
        $data['page_title'] = $data['page_title'] ?? $data['title'] ?? 'Member Portal';
        
        $this->load->view('member/layouts/header', $data);
        $this->load->view('member/layouts/sidebar', $data);
        $this->load->view($view, $data);
        $this->load->view('member/layouts/footer', $data);
    }
}
}
