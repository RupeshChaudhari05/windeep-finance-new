<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MY_Controller - Base Controller
 * 
 * All controllers should extend this base controller
 * Provides common functionality, authentication, and audit logging
 */
class MY_Controller extends CI_Controller {
    
    protected $admin_data = null;
    protected $member_data = null;
    protected $current_financial_year = null;
    protected $settings = [];
    
    public function __construct() {
        parent::__construct();
        
        // Load essential libraries and helpers
        $this->load->library('session');
        $this->load->helper(['url', 'form', 'security', 'date']);
        $this->load->model('Setting_model');
        
        // Load system settings
        $this->settings = $this->Setting_model->get_all_settings();
        
        // Get current financial year
        $this->load->model('Financial_year_model');
        $this->current_financial_year = $this->Financial_year_model->get_active();
    }
    
    /**
     * JSON Response Helper
     */
    protected function json_response($data, $status_code = 200) {
        $this->output
            ->set_status_header($status_code)
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }
    
    /**
     * Success Response
     */
    protected function success_response($message, $data = [], $redirect = null) {
        $response = [
            'status' => 'success',
            'success' => true,
            'message' => $message,
            'data' => $data
        ];
        
        if ($redirect) {
            $response['redirect'] = $redirect;
        }
        
        $this->json_response($response);
    }
    
    /**
     * Error Response
     */
    protected function error_response($message, $errors = [], $status_code = 400) {
        $response = [
            'status' => 'error',
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ];
        
        $this->json_response($response, $status_code);
    }
    
    /**
     * Check if AJAX request
     */
    protected function is_ajax() {
        return $this->input->is_ajax_request();
    }
    
    /**
     * Get setting value
     */
    protected function get_setting($key, $default = null) {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }
    
    /**
     * Format currency
     */
    protected function format_currency($amount) {
        $symbol = $this->get_setting('currency_symbol', '₹');
        return $symbol . number_format($amount, 2);
    }
    
    /**
     * Format date
     */
    protected function format_date($date, $format = null) {
        if (empty($date)) return '';
        $format = $format ?: $this->get_setting('date_format', 'd-m-Y');
        return format_date($date, $format, '');
    }
    
    /**
     * Log Activity
     */
    protected function log_activity($activity, $description = null, $module = null) {
        $user_type = 'system';
        $user_id = null;
        
        if (isset($this->admin_data) && $this->admin_data) {
            $user_type = 'admin';
            $user_id = $this->admin_data->id;
        } elseif (isset($this->member) && $this->member) {
            $user_type = 'member';
            $user_id = $this->member->id;
        }
        
        $this->load->model('Activity_model');
        
        $this->Activity_model->create([
            'user_type' => $user_type,
            'user_id' => $user_id,
            'activity' => $activity,
            'description' => $description,
            'module' => $module,
            'ip_address' => $this->input->ip_address(),
            'user_agent' => $this->input->user_agent()
        ]);
    }
    
    /**
     * Log Audit Trail
     * Available to both Admin and Member controllers
     */
    protected function log_audit($action, $module, $table_name, $record_id, $old_values = null, $new_values = null, $remarks = null) {
        $this->load->model('Audit_model');
        
        // Determine user type and ID
        $user_type = 'system';
        $user_id = null;
        
        if (isset($this->admin_data) && $this->admin_data) {
            $user_type = 'admin';
            $user_id = $this->admin_data->id;
        } elseif (isset($this->member) && $this->member) {
            $user_type = 'member';
            $user_id = $this->member->id;
        }
        
        if (!$user_id) {
            return;
        }
        
        // Ensure record_id is not null
        if ($record_id === null || $record_id === '') {
            $record_id = 0;
        }
        
        $data = [
            'user_type' => $user_type,
            'user_id' => $user_id,
            'action' => $action,
            'module' => $module,
            'table_name' => $table_name,
            'record_id' => (int) $record_id,
            'old_values' => $old_values ? json_encode($old_values) : null,
            'new_values' => $new_values ? json_encode($new_values) : null,
            'ip_address' => $this->input->ip_address(),
            'user_agent' => $this->input->user_agent(),
            'session_id' => session_id(),
            'remarks' => $remarks
        ];

        // Determine changed fields
        if ($old_values && $new_values) {
            $changed_fields = [];
            $old_arr = is_array($old_values) ? $old_values : (array) $old_values;
            $new_arr = is_array($new_values) ? $new_values : (array) $new_values;
            foreach ($new_arr as $key => $value) {
                if (!isset($old_arr[$key]) || $old_arr[$key] != $value) {
                    $changed_fields[] = $key;
                }
            }
            $data['changed_fields'] = json_encode($changed_fields);
        }
        
        $this->Audit_model->create($data);
    }
}

/**
 * Admin_Controller - For Admin Panel
 */
class Admin_Controller extends MY_Controller {
    
    public function __construct() {
        parent::__construct();
        
        $this->load->model('Admin_model');
        $this->load->model('Audit_model');
        
        // Check admin authentication
        $this->_check_auth();
        
        // Load admin data for views
        $this->load->vars([
            'admin' => $this->admin_data,
            'settings' => $this->settings,
            'financial_year' => $this->current_financial_year
        ]);
        // Load member helper for admin views
        $this->load->helper('member');
    }
    
    /**
     * Check Admin Authentication
     */
    private function _check_auth() {
        $admin_id = $this->session->userdata('admin_id');
        
        if (!$admin_id) {
            if ($this->is_ajax()) {
                $this->error_response('Session expired. Please login again.', [], 401);
                exit;
            }
            redirect('auth/login');
        }
        
        // Enforce session timeout (default 30 minutes)
        $timeout = (int) ($this->settings['session_timeout'] ?? 1800);
        $last_activity = $this->session->userdata('last_activity');
        if ($last_activity && (time() - $last_activity) > $timeout) {
            $this->session->sess_destroy();
            if ($this->is_ajax()) {
                $this->error_response('Your session has expired due to inactivity. Please login again.', [], 401);
                exit;
            }
            $this->session->set_flashdata('error', 'Your session has expired due to inactivity. Please login again.');
            redirect('auth/login');
        }
        
        $this->admin_data = $this->Admin_model->get_by_id($admin_id);
        
        if (!$this->admin_data || $this->admin_data->is_active != 1) {
            $this->session->sess_destroy();
            if ($this->is_ajax()) {
                $this->error_response('Account is inactive.', [], 401);
                exit;
            }
            redirect('admin/auth');
        }
        
        // Update last activity
        $this->session->set_userdata('last_activity', time());
    }
    
    /**
     * Check Permission
     */
    protected function check_permission($permission) {
        if ($this->admin_data->role === 'super_admin') {
            return true;
        }
        
        $permissions = json_decode($this->admin_data->permissions, true) ?: [];
        
        if (!in_array($permission, $permissions)) {
            if ($this->is_ajax()) {
                $this->error_response('You do not have permission to perform this action.', [], 403);
                exit;
            }
            $this->session->set_flashdata('error', 'You do not have permission to access this page.');
            redirect('admin/dashboard');
        }
        
        return true;
    }
    
    /**
     * Load Admin View
     */
    protected function load_view($view, $data = []) {
        $data['page_title'] = isset($data['page_title']) ? $data['page_title'] : 'Dashboard';
        $data['breadcrumbs'] = isset($data['breadcrumbs']) ? $data['breadcrumbs'] : [];
        
        $this->load->view('admin/layouts/header', $data);
        $this->load->view('admin/layouts/sidebar', $data);
        $this->load->view($view, $data);
        $this->load->view('admin/layouts/footer', $data);
    }
}

// Member_Controller is implemented in application/core/Member_Controller.php and should be autoloaded separately.

/**
 * API_Controller - For API Endpoints
 */
class API_Controller extends MY_Controller {
    
    protected $request_data = [];
    
    public function __construct() {
        parent::__construct();
        
        // Set JSON content type
        $this->output->set_content_type('application/json');
        
        // Parse request data
        $this->_parse_request();
    }
    
    /**
     * Parse Request Data
     */
    private function _parse_request() {
        $raw_input = file_get_contents('php://input');
        
        if (!empty($raw_input)) {
            $this->request_data = json_decode($raw_input, true) ?: [];
        }
        
        // Merge with POST/GET data
        $this->request_data = array_merge(
            $this->input->get() ?: [],
            $this->input->post() ?: [],
            $this->request_data
        );
    }
    
    /**
     * Get Request Parameter
     */
    protected function get_param($key, $default = null) {
        return isset($this->request_data[$key]) ? $this->request_data[$key] : $default;
    }
}
