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
