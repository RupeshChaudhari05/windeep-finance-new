<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Settings Controller - System Settings
 */
class Settings extends Admin_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model(['Setting_model', 'Financial_year_model']);
    }
    
    /**
     * Settings Overview
     */
    public function index() {
        $data['title'] = 'Settings';
        $data['page_title'] = 'System Settings';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Settings', 'url' => '']
        ];
        
        $data['settings'] = $this->Setting_model->get_all_settings();
        
        $this->load_view('admin/settings/index', $data);
    }
    
    /**
     * Update Settings
     */
    public function update() {
        if ($this->input->method() !== 'post') {
            redirect('admin/settings');
        }
        
        $settings = $this->input->post('settings');
        
        if ($settings && is_array($settings)) {
            $this->Setting_model->update_settings($settings);
            $this->log_audit('settings', 0, 'update', null, $settings);
            $this->session->set_flashdata('success', 'Settings updated successfully.');
        }
        
        redirect('admin/settings');
    }
    
    /**
     * Financial Years
     */
    public function financial_years() {
        $data['title'] = 'Financial Years';
        $data['page_title'] = 'Manage Financial Years';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Settings', 'url' => 'admin/settings'],
            ['title' => 'Financial Years', 'url' => '']
        ];
        
        $data['years'] = $this->Financial_year_model->get_all();
        $data['active'] = $this->Financial_year_model->get_active();
        
        $this->load_view('admin/settings/financial_years', $data);
    }
    
    /**
     * Create Financial Year
     */
    public function create_financial_year() {
        if ($this->input->method() !== 'post') {
            redirect('admin/settings/financial_years');
        }
        
        $year_data = [
            'year_name' => $this->input->post('year_name'),
            'start_date' => $this->input->post('start_date'),
            'end_date' => $this->input->post('end_date'),
            'is_active' => $this->input->post('is_active') ? 1 : 0,
            'created_by' => $this->session->userdata('admin_id')
        ];
        
        $result = $this->Financial_year_model->create($year_data);
        
        if ($result) {
            $this->log_audit('financial_years', $result, 'create', null, $year_data);
            $this->session->set_flashdata('success', 'Financial year created successfully.');
        } else {
            $this->session->set_flashdata('error', 'Failed to create financial year.');
        }
        
        redirect('admin/settings/financial_years');
    }
    
    /**
     * Chart of Accounts
     */
    public function chart_of_accounts() {
        $data['title'] = 'Chart of Accounts';
        $data['page_title'] = 'Chart of Accounts';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Settings', 'url' => 'admin/settings'],
            ['title' => 'Chart of Accounts', 'url' => '']
        ];
        
        $data['accounts'] = $this->db->order_by('account_code', 'ASC')
                                     ->get('chart_of_accounts')
                                     ->result();
        
        $this->load_view('admin/settings/chart_of_accounts', $data);
    }
    
    /**
     * Loan Products
     */
    public function loan_products() {
        $data['title'] = 'Loan Products';
        $data['page_title'] = 'Manage Loan Products';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Settings', 'url' => 'admin/settings'],
            ['title' => 'Loan Products', 'url' => '']
        ];
        
        $data['products'] = $this->db->get('loan_products')->result();
        
        $this->load_view('admin/settings/loan_products', $data);
    }
    
    /**
     * Savings Schemes
     */
    public function savings_schemes() {
        $data['title'] = 'Savings Schemes';
        $data['page_title'] = 'Manage Savings Schemes';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Settings', 'url' => 'admin/settings'],
            ['title' => 'Savings Schemes', 'url' => '']
        ];
        
        $data['schemes'] = $this->db->get('savings_schemes')->result();
        
        $this->load_view('admin/settings/savings_schemes', $data);
    }
    
    /**
     * Fine Rules
     */
    public function fine_rules() {
        $data['title'] = 'Fine Rules';
        $data['page_title'] = 'Manage Fine Rules';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Settings', 'url' => 'admin/settings'],
            ['title' => 'Fine Rules', 'url' => '']
        ];
        
        $data['rules'] = $this->db->order_by('fine_type', 'ASC')
                                  ->order_by('min_days', 'ASC')
                                  ->get('fine_rules')
                                  ->result();
        
        $this->load_view('admin/settings/fine_rules', $data);
    }
    
    /**
     * Admin Users
     */
    public function admin_users() {
        $data['title'] = 'Admin Users';
        $data['page_title'] = 'Manage Admin Users';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Settings', 'url' => 'admin/settings'],
            ['title' => 'Admin Users', 'url' => '']
        ];
        
        $data['users'] = $this->db->get('admin_users')->result();
        
        $this->load_view('admin/settings/admin_users', $data);
    }
    
    /**
     * Create Admin User
     */
    public function create_admin() {
        if ($this->input->method() !== 'post') {
            redirect('admin/settings/admin_users');
        }
        
        $this->load->model('Admin_model');
        
        $user_data = [
            'full_name' => $this->input->post('full_name'),
            'email' => $this->input->post('email'),
            'phone' => $this->input->post('phone'),
            'role' => $this->input->post('role'),
            'password' => $this->input->post('password')
        ];
        
        $result = $this->Admin_model->create_admin($user_data);
        
        if ($result) {
            $this->log_audit('admin_users', $result, 'create', null, ['email' => $user_data['email']]);
            $this->session->set_flashdata('success', 'Admin user created successfully.');
        } else {
            $this->session->set_flashdata('error', 'Failed to create admin user.');
        }
        
        redirect('admin/settings/admin_users');
    }
    
    /**
     * Save Fine Rule
     */
    public function save_fine_rule() {
        if ($this->input->method() !== 'post') {
            redirect('admin/settings/fine_rules');
        }
        
        $id = $this->input->post('id');
        $admin_id = $this->session->userdata('admin_id');
        
        $rule_data = [
            'rule_name' => $this->input->post('rule_name'),
            'applies_to' => $this->input->post('applies_to'),
            'fine_type' => $this->input->post('fine_type'),
            'fine_amount' => $this->input->post('fine_amount') ?: 0,
            'fine_rate' => $this->input->post('fine_rate') ?: 0,
            'per_day_amount' => $this->input->post('per_day_amount') ?: 0,
            'grace_period' => $this->input->post('grace_period') ?: 0,
            'max_fine' => $this->input->post('max_fine') ?: null,
            'min_days' => $this->input->post('min_days') ?: 0,
            'max_days' => $this->input->post('max_days') ?: 9999,
            'description' => $this->input->post('description'),
            'updated_by' => $admin_id,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($id) {
            // Update existing rule
            $result = $this->db->where('id', $id)->update('fine_rules', $rule_data);
            $this->log_audit('fine_rules', $id, 'update', null, $rule_data);
            $message = 'Fine rule updated successfully.';
        } else {
            // Create new rule
            $rule_data['created_by'] = $admin_id;
            $rule_data['created_at'] = date('Y-m-d H:i:s');
            $rule_data['is_active'] = 1;
            $result = $this->db->insert('fine_rules', $rule_data);
            $id = $this->db->insert_id();
            $this->log_audit('fine_rules', $id, 'create', null, $rule_data);
            $message = 'Fine rule created successfully.';
        }
        
        if ($result) {
            $this->session->set_flashdata('success', $message);
        } else {
            $this->session->set_flashdata('error', 'Failed to save fine rule.');
        }
        
        redirect('admin/settings/fine_rules');
    }
    
    /**
     * Toggle Fine Rule Status (AJAX)
     */
    public function toggle_fine_rule() {
        if (!$this->input->is_ajax_request()) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }
        
        $id = $this->input->post('id');
        $is_active = $this->input->post('is_active');
        
        $result = $this->db->where('id', $id)
                           ->update('fine_rules', [
                               'is_active' => $is_active,
                               'updated_at' => date('Y-m-d H:i:s'),
                               'updated_by' => $this->session->userdata('admin_id')
                           ]);
        
        echo json_encode(['success' => $result]);
    }
    
    /**
     * Save Loan Product
     */
    public function save_loan_product() {
        if ($this->input->method() !== 'post') {
            redirect('admin/settings/loan_products');
        }
        
        $id = $this->input->post('id');
        $admin_id = $this->session->userdata('admin_id');
        
        $product_data = [
            'product_code' => $this->input->post('product_code'),
            'product_name' => $this->input->post('product_name'),
            'min_amount' => $this->input->post('min_amount'),
            'max_amount' => $this->input->post('max_amount'),
            'min_interest_rate' => $this->input->post('min_interest_rate'),
            'max_interest_rate' => $this->input->post('max_interest_rate'),
            'default_interest_rate' => $this->input->post('default_interest_rate'),
            'min_tenure_months' => $this->input->post('min_tenure_months'),
            'max_tenure_months' => $this->input->post('max_tenure_months'),
            'processing_fee_type' => $this->input->post('processing_fee_type'),
            'processing_fee_value' => $this->input->post('processing_fee_value'),
            'late_fee_type' => $this->input->post('late_fee_type'),
            'late_fee_value' => $this->input->post('late_fee_value'),
            'late_fee_per_day' => $this->input->post('late_fee_per_day') ?: 0,
            'grace_period_days' => $this->input->post('grace_period_days') ?: 0,
            'description' => $this->input->post('description'),
            'updated_by' => $admin_id,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($id) {
            $result = $this->db->where('id', $id)->update('loan_products', $product_data);
            $this->log_audit('loan_products', $id, 'update', null, $product_data);
            $message = 'Loan product updated successfully.';
        } else {
            $product_data['created_by'] = $admin_id;
            $product_data['created_at'] = date('Y-m-d H:i:s');
            $product_data['is_active'] = 1;
            $result = $this->db->insert('loan_products', $product_data);
            $id = $this->db->insert_id();
            $this->log_audit('loan_products', $id, 'create', null, $product_data);
            $message = 'Loan product created successfully.';
        }
        
        if ($result) {
            $this->session->set_flashdata('success', $message);
        } else {
            $this->session->set_flashdata('error', 'Failed to save loan product.');
        }
        
        redirect('admin/settings/loan_products');
    }
    
    /**
     * Audit Logs
     */
    public function audit_logs() {
        $data['title'] = 'Audit Logs';
        $data['page_title'] = 'Audit Trail';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Settings', 'url' => 'admin/settings'],
            ['title' => 'Audit Logs', 'url' => '']
        ];
        
        $this->load->model('Audit_model');
        
        $filters = [
            'table_name' => $this->input->get('table'),
            'action' => $this->input->get('action'),
            'from_date' => $this->input->get('from_date'),
            'to_date' => $this->input->get('to_date')
        ];
        
        $data['logs'] = $this->Audit_model->search_audit_logs($filters, 100);
        $data['filters'] = $filters;
        
        $this->load_view('admin/settings/audit_logs', $data);
    }
    
    /**
     * Activity Logs
     */
    public function activity_logs() {
        $data['title'] = 'Activity Logs';
        $data['page_title'] = 'User Activity';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Settings', 'url' => 'admin/settings'],
            ['title' => 'Activity Logs', 'url' => '']
        ];
        
        $this->load->model('Activity_model');
        
        $data['activities'] = $this->Activity_model->get_recent(200);
        
        $this->load_view('admin/settings/activity_logs', $data);
    }
    
    /**
     * Bank Accounts
     */
    public function bank_accounts() {
        $data['title'] = 'Bank Accounts';
        $data['page_title'] = 'Manage Bank Accounts';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Settings', 'url' => 'admin/settings'],
            ['title' => 'Bank Accounts', 'url' => '']
        ];
        
        $data['accounts'] = $this->db->get('bank_accounts')->result();
        
        $this->load_view('admin/settings/bank_accounts', $data);
    }
    
    /**
     * Guarantor Settings
     */
    public function guarantor_settings() {
        $data['title'] = 'Guarantor Settings';
        $data['page_title'] = 'Guarantor Configuration';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Settings', 'url' => 'admin/settings'],
            ['title' => 'Guarantor Settings', 'url' => '']
        ];
        
        $data['settings'] = $this->Setting_model->get_all_settings();
        
        $this->load_view('admin/settings/guarantor_settings', $data);
    }
    
    /**
     * Accounting Settings
     */
    public function accounting_settings() {
        $data['title'] = 'Accounting Settings';
        $data['page_title'] = 'Accounting Configuration';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Settings', 'url' => 'admin/settings'],
            ['title' => 'Accounting Settings', 'url' => '']
        ];
        
        $data['settings'] = $this->Setting_model->get_all_settings();
        $data['accounts'] = $this->db->get('chart_of_accounts')->result();
        
        $this->load_view('admin/settings/accounting_settings', $data);
    }
    
    /**
     * Toggle Loan Product Status (AJAX)
     */
    public function toggle_loan_product() {
        if (!$this->input->is_ajax_request()) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }
        
        $id = $this->input->post('id');
        $is_active = $this->input->post('is_active');
        
        $result = $this->db->where('id', $id)
                           ->update('loan_products', [
                               'is_active' => $is_active,
                               'updated_at' => date('Y-m-d H:i:s'),
                               'updated_by' => $this->session->userdata('admin_id')
                           ]);
        
        echo json_encode(['success' => $result]);
    }
    
    /**
     * Backup Database
     */
    public function backup() {
        $this->load->dbutil();
        
        $backup = $this->dbutil->backup();
        
        $filename = 'backup_' . date('Y-m-d_His') . '.sql';
        
        $this->load->helper('download');
        force_download($filename, $backup);
    }
}
