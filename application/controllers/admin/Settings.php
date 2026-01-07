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
        
        // Get raw settings from database
        $raw_settings = $this->Setting_model->get_all_settings();
        
        // Normalize settings with mappings and defaults
        $data['settings'] = [
            // Organization details (company_* -> org_*)
            'org_name' => $raw_settings['company_name'] ?? 'Windeep Finance',
            'org_short_name' => $raw_settings['company_short_name'] ?? '',
            'org_phone' => $raw_settings['company_phone'] ?? '',
            'org_email' => $raw_settings['company_email'] ?? '',
            'org_address' => $raw_settings['company_address'] ?? '',
            
            // System preferences
            'currency_symbol' => $raw_settings['currency_symbol'] ?? '₹',
            'date_format' => $raw_settings['date_format'] ?? 'd/m/Y',
            
            // Code prefixes
            'member_code_prefix' => $raw_settings['member_code_prefix'] ?? 'MEM',
            'loan_prefix' => $raw_settings['loan_prefix'] ?? 'LN',
            'savings_prefix' => $raw_settings['savings_prefix'] ?? 'SV',
            'receipt_prefix' => $raw_settings['receipt_prefix'] ?? 'RCP',
            
            // Business rules
            'max_active_loans' => $raw_settings['max_active_loans'] ?? 3,
            'max_guarantor' => $raw_settings['max_guarantor'] ?? 3,
            'npa_days' => $raw_settings['npa_days'] ?? 90,
            'auto_apply_fines' => $raw_settings['auto_apply_fines'] ?? false,
            'kyc_required' => $raw_settings['kyc_required'] ?? true,
            
            // Include all other raw settings
        ] + $raw_settings;
        
        // Financial years - normalize fields expected by the view
        $data['financial_years'] = $this->Financial_year_model->get_all() ?: [];
        foreach ($data['financial_years'] as $fy_idx => $fy) {
            // Ensure all view fields exist with safe defaults
            $fy->year_name = $fy->year_name ?? $fy->year_code ?? ('FY-' . ($fy->id ?? $fy_idx));
            $fy->start_date = $fy->start_date ?? null;
            $fy->end_date = $fy->end_date ?? null;
            $fy->is_current = isset($fy->is_active) ? (bool) $fy->is_active : false;
            $fy->is_closed = isset($fy->is_closed) ? (bool) $fy->is_closed : false;
            $data['financial_years'][$fy_idx] = $fy;
        }

        // Loan products for Loan Products tab (normalize tenure fields)
        $data['loan_products'] = $this->db->order_by('product_name', 'ASC')->get('loan_products')->result();
        foreach ($data['loan_products'] as $pidx => $prod) {
            // Tenure normalization
            $prod->min_tenure = isset($prod->min_tenure) ? (int) $prod->min_tenure : (isset($prod->min_tenure_months) ? (int) $prod->min_tenure_months : 1);
            $prod->max_tenure = isset($prod->max_tenure) ? (int) $prod->max_tenure : (isset($prod->max_tenure_months) ? (int) $prod->max_tenure_months : 60);
            $prod->interest_rate = $prod->interest_rate ?? ($prod->default_interest_rate ?? 0);
            $prod->interest_type = $prod->interest_type ?? 'fixed';
            $data['loan_products'][$pidx] = $prod;
        }
        
        // Savings schemes for Savings Schemes tab
        $data['savings_schemes'] = $this->db->order_by('scheme_name', 'ASC')->get('savings_schemes')->result();
        foreach ($data['savings_schemes'] as $sidx => $scheme) {
            // Normalize minimum / monthly contribution and ensure numeric
            $raw_min = null;
            if (isset($scheme->minimum_amount) && $scheme->minimum_amount !== null) {
                $raw_min = $scheme->minimum_amount;
            } elseif (isset($scheme->minimum_contribution)) {
                $raw_min = $scheme->minimum_contribution;
            } elseif (isset($scheme->monthly_amount)) {
                $raw_min = $scheme->monthly_amount;
            } elseif (isset($scheme->min_amount)) {
                $raw_min = $scheme->min_amount;
            } else {
                $raw_min = 0;
            }
            $scheme->minimum_amount = is_numeric($raw_min) ? (float) $raw_min : 0.0;

            // Ensure duration exists
            $scheme->duration_months = isset($scheme->duration_months) ? (int) $scheme->duration_months : (isset($scheme->duration) ? (int) $scheme->duration : null);
            $data['savings_schemes'][$sidx] = $scheme;
        }
        
        // Fine rules - use Fine_model to get normalized rules and map to view-friendly fields
        $this->load->model('Fine_model');
        $raw_rules = $this->Fine_model->get_rules();
        $mapped_rules = [];
        foreach ($raw_rules as $r) {
            $rule = $r;
            $rule->calculation_type = $rule->fine_type ?? ($rule->calculation_type ?? 'fixed');
            // amount: percentage or fixed
            if ($rule->calculation_type === 'percentage') {
                $rule->amount = $rule->fine_value ?? ($rule->fine_rate ?? 0);
            } else {
                $rule->amount = $rule->fine_amount ?? ($rule->fine_value ?? 0);
            }
            $rule->grace_days = $rule->grace_period_days ?? $rule->min_days ?? ($rule->grace_period ?? 0);
            $mapped_rules[] = $rule;
        }
        $data['fine_rules'] = $mapped_rules;
        
        // Admin users for Admin Users tab
        $data['admin_users'] = $this->db->select('id, full_name as name, email, role, last_login, is_active')
                                       ->order_by('full_name', 'ASC')
                                       ->get('admin_users')
                                       ->result();
        
        $this->load_view('admin/settings/index', $data);
    }
    
    /**
     * General Settings (Alias to index)
     */
    public function general() {
        $this->index();
    }
    
    /**
     * Update Settings
     */
    public function update() {
        if ($this->input->method() !== 'post') {
            redirect('admin/settings');
        }
        
        $post_data = $this->input->post();
        
        if ($post_data && is_array($post_data)) {
            // Map org_* fields back to company_* for database storage
            $settings = [];
            
            // Organization mappings
            if (isset($post_data['org_name'])) {
                $settings['company_name'] = $post_data['org_name'];
            }
            if (isset($post_data['org_short_name'])) {
                $settings['company_short_name'] = $post_data['org_short_name'];
            }
            if (isset($post_data['org_phone'])) {
                $settings['company_phone'] = $post_data['org_phone'];
            }
            if (isset($post_data['org_email'])) {
                $settings['company_email'] = $post_data['org_email'];
            }
            if (isset($post_data['org_address'])) {
                $settings['company_address'] = $post_data['org_address'];
            }
            
            // Direct mappings (no conversion needed)
            $direct_fields = [
                'currency_symbol', 'date_format',
                'member_code_prefix', 'loan_prefix', 'savings_prefix', 'receipt_prefix',
                'max_active_loans', 'max_guarantor', 'npa_days'
            ];
            
            foreach ($direct_fields as $field) {
                if (isset($post_data[$field])) {
                    $settings[$field] = $post_data[$field];
                }
            }
            
            // Handle checkboxes (set to 0 if not present)
            $checkbox_fields = ['auto_apply_fines', 'kyc_required'];
            foreach ($checkbox_fields as $field) {
                $settings[$field] = isset($post_data[$field]) ? 1 : 0;
            }
            
            // Capture previous settings for audit
            $old_settings = $this->Setting_model->get_all_settings();

            // Update settings
            $this->Setting_model->update_settings($settings);

            // Log audit correctly: action, module, table_name, record_id, old_values, new_values
            // Use record_id=0 to indicate bulk/system settings update
            $this->log_audit('update', 'settings', 'system_settings', 0, $old_settings, $settings);
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
            // Schema uses year_code (e.g., 2025-26)
            'year_code' => $this->input->post('year_name') ?: $this->input->post('year_code'),
            'start_date' => $this->input->post('start_date'),
            'end_date' => $this->input->post('end_date'),
            'is_active' => $this->input->post('is_active') ? 1 : 0,
            'created_by' => $this->session->userdata('admin_id')
        ];
        
        $result = $this->Financial_year_model->create($year_data);
        
        if ($result) {
            $this->log_audit('create', 'financial_years', 'financial_years', $result, null, $year_data);
            $this->session->set_flashdata('success', 'Financial year created successfully.');
        } else {
            $this->session->set_flashdata('error', 'Failed to create financial year.');
        }
        
        redirect('admin/settings/financial_years');
    }

    /**
     * Set Current Financial Year
     */
    public function set_current_fy($id) {
        if (!$id) {
            $this->session->set_flashdata('error', 'Invalid financial year');
            redirect('admin/settings/financial_years');
        }

        $this->Financial_year_model->set_active($id);
        $this->log_audit('set_current', 'financial_years', 'financial_years', $id, null, ['id' => $id]);
        $this->session->set_flashdata('success', 'Financial year set as current.');
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
        
        // Use Fine_model to fetch rules (returns normalized fields for views)
        $this->load->model('Fine_model');
        $data['rules'] = $this->Fine_model->get_rules();

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
            $this->log_audit('create', 'admin_users', 'admin_users', $result, null, ['email' => $user_data['email']]);
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
        
        // Map incoming form fields to DB schema-compatible columns
        $rule_data = [
            'rule_name' => $this->input->post('rule_name'),
            'applies_to' => $this->input->post('applies_to'),
            'fine_type' => $this->input->post('fine_type'),
            // 'fine_value' stores either fixed amount or percentage (depending on fine_type)
            'fine_value' => $this->input->post('fine_amount') !== null ? $this->input->post('fine_amount') : ($this->input->post('fine_rate') ?: 0),
            'per_day_amount' => $this->input->post('per_day_amount') ?: 0,
            // Map grace/min days
            'grace_period_days' => $this->input->post('min_days') ?: $this->input->post('grace_period') ?: 0,
            'max_fine_amount' => $this->input->post('max_fine') ?: null,
            'description' => $this->input->post('description'),
            'updated_by' => $admin_id,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Preserve compatibility: if min_days/max_days columns exist store them as well
        if ($this->db->field_exists('min_days', 'fine_rules')) {
            $rule_data['min_days'] = $this->input->post('min_days') ?: 0;
        }
        if ($this->db->field_exists('max_days', 'fine_rules')) {
            $rule_data['max_days'] = $this->input->post('max_days') ?: 9999;
        }
        
        if ($id) {
            // Update existing rule
            $old = $this->db->where('id', $id)->get('fine_rules')->row();
            $rule_data['updated_at'] = date('Y-m-d H:i:s');
            $rule_data['updated_by'] = $admin_id;
            $result = $this->db->where('id', $id)->update('fine_rules', $rule_data);
            $this->log_audit('update', 'fine_rules', 'fine_rules', $id, $old, $rule_data);
            $message = 'Fine rule updated successfully.';
        } else {
            // Create new rule
            $rule_data['created_by'] = $admin_id;
            $rule_data['created_at'] = date('Y-m-d H:i:s');
            $rule_data['is_active'] = 1;
            $result = $this->db->insert('fine_rules', $rule_data);
            $id = $this->db->insert_id();
            $this->log_audit('create', 'fine_rules', 'fine_rules', $id, null, $rule_data);
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
            $this->log_audit('update', 'loan_products', 'loan_products', $id, null, $product_data);
            $message = 'Loan product updated successfully.';
        } else {
            $product_data['created_by'] = $admin_id;
            $product_data['created_at'] = date('Y-m-d H:i:s');
            $product_data['is_active'] = 1;
            $result = $this->db->insert('loan_products', $product_data);
            $id = $this->db->insert_id();
            $this->log_audit('create', 'loan_products', 'loan_products', $id, null, $product_data);
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
