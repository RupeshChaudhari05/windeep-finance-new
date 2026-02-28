<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Savings Controller - Savings Management
 */
class Savings extends Admin_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model(['Savings_model', 'Member_model']);
    }
    
    /**
     * List Savings Accounts
     */
    public function index() {
        $this->check_permission('savings_view');
        $data['title'] = 'Savings Accounts';
        $data['page_title'] = 'Savings Management';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Savings', 'url' => '']
        ];
        
        $data['stats'] = $this->Savings_model->get_dashboard_stats();
        
        // Get accounts with pagination
        $page = (int) ($this->input->get('page') ?: 1);
        $per_page = 20;
        // Filters for the view
        $data['filters'] = [
            'search' => $this->input->get('search') ?: '',
            'scheme' => $this->input->get('scheme') ?: '',
            'status' => $this->input->get('status') ?: ''
        ];
        
        // Build base query for counting
        $this->db->from('savings_accounts sa');
        $this->db->join('savings_schemes ss', 'ss.id = sa.scheme_id');
        $this->db->join('members m', 'm.id = sa.member_id');
        if ($data['filters']['status']) {
            $this->db->where('sa.status', $data['filters']['status']);
        }
        if (!empty($data['filters']['scheme'])) {
            $this->db->where('sa.scheme_id', $data['filters']['scheme']);
        }
        if (!empty($data['filters']['search'])) {
            $search = $data['filters']['search'];
            $this->db->group_start();
            $this->db->like('sa.account_number', $search);
            $this->db->or_like('m.member_code', $search);
            $this->db->or_like('m.first_name', $search);
            $this->db->or_like('m.last_name', $search);
            $this->db->or_like('ss.scheme_name', $search);
            $this->db->group_end();
        }
        $total = (int) $this->db->count_all_results();

        // Pagination metadata for view
        $data['pagination'] = [
            'current_page' => $page,
            'per_page' => $per_page,
            'total' => $total
        ];

        // Fetch paged results
        $this->db->select('sa.*, ss.scheme_name, m.member_code, m.first_name, m.last_name, m.phone');
        $this->db->from('savings_accounts sa');
        $this->db->join('savings_schemes ss', 'ss.id = sa.scheme_id');
        $this->db->join('members m', 'm.id = sa.member_id');
        if ($data['filters']['status']) {
            $this->db->where('sa.status', $data['filters']['status']);
        }
        if (!empty($data['filters']['scheme'])) {
            $this->db->where('sa.scheme_id', $data['filters']['scheme']);
        }
        if (!empty($data['filters']['search'])) {
            $search = $data['filters']['search'];
            $this->db->group_start();
            $this->db->like('sa.account_number', $search);
            $this->db->or_like('m.member_code', $search);
            $this->db->or_like('m.first_name', $search);
            $this->db->or_like('m.last_name', $search);
            $this->db->or_like('ss.scheme_name', $search);
            $this->db->group_end();
        }
        $this->db->order_by('sa.created_at', 'DESC');
        $this->db->limit($per_page, ($page - 1) * $per_page);

        $data['accounts'] = $this->db->get()->result();
        // Normalize accounts: compute pending_dues count and display name
        foreach ($data['accounts'] as &$acc) {
            // Compute count of pending/partial/overdue schedule entries for the account
            $acc->pending_dues = (int) $this->db->where('savings_account_id', $acc->id)
                                                ->where_in('status', ['pending', 'partial', 'overdue'])
                                                ->count_all_results('savings_schedule');
            // Provide a member display name
            if (!isset($acc->member_name)) {
                $acc->member_name = trim(($acc->first_name ?? '') . ' ' . ($acc->last_name ?? '')) ?: ($acc->member_code ?? '');
            }
        }
        $data['schemes'] = $this->Savings_model->get_schemes();
        
        $this->load_view('admin/savings/index', $data);
    }
    
    /**
     * View Savings Account
     */
    public function view($id) {
        $this->check_permission('savings_view');
        $account = $this->db->select('sa.*, ss.scheme_name, ss.interest_rate, m.member_code, m.first_name, m.last_name, m.phone')
                            ->from('savings_accounts sa')
                            ->join('savings_schemes ss', 'ss.id = sa.scheme_id')
                            ->join('members m', 'm.id = sa.member_id')
                            ->where('sa.id', $id)
                            ->get()
                            ->row();
        
        if (!$account) {
            $this->session->set_flashdata('error', 'Savings account not found.');
            redirect('admin/savings');
        }
        
        $data['title'] = 'View Savings Account';
        $data['page_title'] = 'Savings Account Details';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Savings', 'url' => 'admin/savings'],
            ['title' => $account->account_number, 'url' => '']
        ];
        
        $data['account'] = $account;
        // Ensure related objects are available to the view
        $data['member'] = $this->Member_model->get_by_id($account->member_id);
        $data['scheme'] = $this->db->where('id', $account->scheme_id)->get('savings_schemes')->row();
        // Count pending dues for the account
        $data['pending_dues'] = (int) $this->db->where('savings_account_id', $id)
                                               ->where_in('status', ['pending', 'partial', 'overdue'])
                                               ->count_all_results('savings_schedule');
        // Ensure account due_date/day is available (prefer scheme due_day, else next pending schedule or fallback to start day)
        if (!isset($account->due_date) || empty($account->due_date)) {
            // Prefer scheme configured due_day
            if (isset($data['scheme']) && isset($data['scheme']->due_day) && $data['scheme']->due_day) {
                $account->due_date = (int) $data['scheme']->due_day;
            } else {
                $next_due = $this->db->where('savings_account_id', $id)
                                     ->where_in('status', ['pending', 'partial', 'overdue'])
                                     ->order_by('due_date', 'ASC')
                                     ->get('savings_schedule')
                                     ->row();
                if ($next_due && isset($next_due->due_date)) {
                    $account->due_date = (int) date('j', safe_timestamp($next_due->due_date));
                } else {
                    // Fallback: use account start_date day or 1
                    $account->due_date = $account->start_date ? (int) date('j', safe_timestamp($account->start_date)) : 1;
                }
            }
        }
        // Compatibility mappings for view fields
        // Some schema/legacy names differ - ensure view properties exist
        $account->interest_earned = $account->interest_earned ?? $account->total_interest_earned ?? 0;
        $account->total_deposited = $account->total_deposited ?? $account->deposited_total ?? 0;
        $account->current_balance = $account->current_balance ?? $account->balance ?? 0;

        $data['schedule'] = $this->Savings_model->get_schedule($id);
        $data['transactions'] = $this->Savings_model->get_transactions($id);
        
        $this->load_view('admin/savings/view', $data);
    }
    
    /**
     * Create Savings Account Form
     */
    public function create() {
        $this->check_permission('savings_create');
        $data['title'] = 'Create Savings Account';
        $data['page_title'] = 'Open Savings Account';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Savings', 'url' => 'admin/savings'],
            ['title' => 'Open Account', 'url' => '']
        ];
        
        $data['schemes'] = $this->Savings_model->get_schemes();        $data['members'] = $this->Member_model->get_active_members_dropdown();        
        // Pre-fill member if passed
        if ($member_id = $this->input->get('member_id')) {
            $data['selected_member'] = $this->Member_model->get_by_id($member_id);
        }
        
        $this->load_view('admin/savings/create', $data);
    }
    
    /**
     * Store Savings Account
     */
    public function store() {
        $this->check_permission('savings_create');
        if ($this->input->method() !== 'post') {
            redirect('admin/savings/create');
        }
        
        $this->load->library('form_validation');
        $this->form_validation->set_rules('member_id', 'Member', 'required|numeric');
        $this->form_validation->set_rules('scheme_id', 'Savings Scheme', 'required|numeric');
        $this->form_validation->set_rules('monthly_amount', 'Monthly Amount', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('start_date', 'Start Date', 'required');
        
        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('admin/savings/create');
        }
        
        $account_data = [
            'member_id' => $this->input->post('member_id'),
            'scheme_id' => $this->input->post('scheme_id'),
            'monthly_amount' => $this->input->post('monthly_amount'),
            'start_date' => $this->input->post('start_date'),
            'maturity_date' => $this->input->post('maturity_date'),
            'status' => 'active'
        ];
        
        $account_id = $this->Savings_model->create_account($account_data);
        
        if ($account_id) {
            $this->log_audit('create', 'savings_accounts', 'savings_accounts', $account_id, null, $account_data);
            $this->session->set_flashdata('success', 'Savings account created successfully.');
            redirect('admin/savings/view/' . $account_id);
        } else {
            $this->session->set_flashdata('error', 'Savings account could not be created. Please check for duplicate accounts or missing required fields and try again.');
            redirect('admin/savings/create');
        }
    }
    
    /**
     * Collect Savings Payment
     */
    public function collect($id = null) {
        $this->check_permission('savings_collect');
        $data['title'] = 'Collect Savings';
        $data['page_title'] = 'Collect Savings Payment';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Savings', 'url' => 'admin/savings'],
            ['title' => 'Collect', 'url' => '']
        ];
        
        // Initialize defaults to avoid undefined variable warnings in view
        $data['account'] = null;
        $data['member'] = null;
        $data['scheme'] = null;
        $data['pending_dues'] = [];

        // Load all active savings accounts for the search selector
        $data['active_accounts'] = $this->db
            ->select('sa.id, sa.account_number, sa.monthly_amount, sa.current_balance, sa.status, m.first_name, m.last_name, m.phone, ss.scheme_name')
            ->from('savings_accounts sa')
            ->join('members m', 'm.id = sa.member_id')
            ->join('savings_schemes ss', 'ss.id = sa.scheme_id', 'left')
            ->where('sa.status', 'active')
            ->where('m.status', 'active')
            ->where('m.deleted_at', null)
            ->order_by('m.first_name', 'ASC')
            ->get()
            ->result();

        // If account ID provided, pre-fill
        if ($id) {
            $data['account'] = $this->db->select('sa.*, m.member_code, m.first_name, m.last_name')
                                        ->from('savings_accounts sa')
                                        ->join('members m', 'm.id = sa.member_id')
                                        ->where('sa.id', $id)
                                        ->get()
                                        ->row();
            
            $data['pending_dues'] = $this->db->where('savings_account_id', $id)
                                              ->where_in('status', ['pending', 'partial'])
                                              ->order_by('due_month', 'ASC')
                                              ->get('savings_schedule')
                                              ->result();

            // Load related member and scheme objects for the view
            if ($data['account']) {
                $this->load->model('Member_model');
                $data['member'] = $this->Member_model->get_by_id($data['account']->member_id);
                $data['scheme'] = $this->db->where('id', $data['account']->scheme_id)->get('savings_schemes')->row();
            }
        }
        
        $this->load_view('admin/savings/collect', $data);
    }
    
    /**
     * Record Savings Payment
     */
    public function record_payment() {
        $this->check_permission('savings_collect');
        if ($this->input->method() !== 'post') {
            redirect('admin/savings/collect');
        }
        
        $this->load->library('form_validation');
        $this->form_validation->set_rules('savings_account_id', 'Savings Account', 'required|numeric');
        $this->form_validation->set_rules('amount', 'Amount', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('payment_mode', 'Payment Mode', 'required');
        
        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('admin/savings/collect/' . $this->input->post('savings_account_id'));
        }
        
        try {
            $payment_data = [
                'savings_account_id' => $this->input->post('savings_account_id'),
                'transaction_type' => 'deposit',
                'amount' => $this->input->post('amount'),
                'payment_mode' => $this->input->post('payment_mode'),
                'reference_number' => $this->input->post('reference_number'),
                'schedule_id' => $this->input->post('schedule_id'),
                'transaction_date' => $this->input->post('transaction_date') ?: date('Y-m-d'),
                'remarks' => $this->input->post('remarks'),
                'received_by' => $this->session->userdata('admin_id')
            ];
            
            $transaction_id = $this->Savings_model->record_payment($payment_data);
            
            if ($transaction_id) {
                // Post to ledger
                $account = $this->Savings_model->get_by_id($payment_data['savings_account_id']);
                $this->load->model('Ledger_model');
                $this->Ledger_model->post_transaction(
                    'savings_deposit',
                    $transaction_id,
                    $payment_data['amount'],
                    $account->member_id,
                    'Savings deposit for account ' . $account->account_number,
                    $this->session->userdata('admin_id')
                );
                
                $this->log_audit('create', 'savings_transactions', 'savings_transactions', $transaction_id, null, $payment_data);
                $this->session->set_flashdata('success', 'Payment recorded successfully.');
                redirect('admin/savings/view/' . $payment_data['savings_account_id']);
            } else {
                throw new Exception('Failed to record payment');
            }
            
        } catch (Exception $e) {
            $this->session->set_flashdata('error', $e->getMessage());
            redirect('admin/savings/collect/' . $this->input->post('savings_account_id'));
        }
    }
    
    /**
     * Edit Savings Account
     */
    public function edit($id = null) {
        $this->check_permission('savings_edit');
        $data['title'] = 'Edit Savings Account';
        $data['page_title'] = 'Edit Savings Account';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Savings', 'url' => 'admin/savings'],
            ['title' => 'Edit', 'url' => '']
        ];
        
        if (!$id) {
            $this->session->set_flashdata('error', 'No savings account ID was provided. Please select a valid account to edit.');
            redirect('admin/savings');
        }
        
        $data['account'] = $this->db->select('sa.*, m.member_code, m.first_name, m.last_name')
                                    ->from('savings_accounts sa')
                                    ->join('members m', 'm.id = sa.member_id')
                                    ->where('sa.id', $id)
                                    ->get()
                                    ->row();
        
        if (!$data['account']) {
            $this->session->set_flashdata('error', 'Savings account not found');
            redirect('admin/savings');
        }
        
        $data['schemes'] = $this->Savings_model->get_schemes();
        $this->load->model('Member_model');
        $data['members'] = $this->Member_model->get_active_members_dropdown();
        
        $this->load_view('admin/savings/edit', $data);
    }

    /**
     * Update Savings Account
     */
    public function update($id = null) {
        $this->check_permission('savings_edit');
        if ($this->input->method() !== 'post') {
            redirect('admin/savings/edit/' . $id);
        }

        $this->load->library('form_validation');
        $this->form_validation->set_rules('monthly_amount', 'Monthly Amount', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('start_date', 'Start Date', 'required');

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('admin/savings/edit/' . $id);
        }

        $update_data = [
            'scheme_id' => $this->input->post('scheme_id'),
            'monthly_amount' => $this->input->post('monthly_amount'),
            'start_date' => $this->input->post('start_date'),
            'maturity_date' => $this->input->post('maturity_date'),
            'status' => $this->input->post('status') ?: 'active',
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->db->where('id', $id)->update('savings_accounts', $update_data);
        $this->log_audit('update', 'savings_accounts', 'savings_accounts', $id, null, $update_data);
        $this->session->set_flashdata('success', 'Savings account updated successfully.');
        redirect('admin/savings/view/' . $id);
    }

    /**
     * Pending Dues
     */
    public function pending() {
        $this->check_permission('savings_view');
        $data['title'] = 'Pending Savings Dues';
        $data['page_title'] = 'Pending Dues';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Savings', 'url' => 'admin/savings'],
            ['title' => 'Pending Dues', 'url' => '']
        ];
        
        $month = $this->input->get('month') ?: date('Y-m-01');
        // Normalize YYYY-MM (from <input type="month">) to YYYY-MM-01
        if (preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = $month . '-01';
        }
        
        $data['pending_dues'] = $this->Savings_model->get_pending_dues($month);
        $data['month'] = $month;
        $data['collection_summary'] = $this->Savings_model->get_monthly_collection($month);
        $data['schemes'] = $this->Savings_model->get_schemes();
        
        $this->load_view('admin/savings/pending', $data);
    }
    
    /**
     * Overdue List
     */
    public function overdue() {
        $this->check_permission('savings_view');
        $data['title'] = 'Overdue Savings';
        $data['page_title'] = 'Overdue Payments';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Savings', 'url' => 'admin/savings'],
            ['title' => 'Overdue', 'url' => '']
        ];
        
        $data['overdue'] = $this->Savings_model->get_overdue();
        
        $this->load_view('admin/savings/overdue', $data);
    }
    
    /**
     * Set a scheme as the default for new member auto-enrollment
     */
    public function set_default_scheme($id) {
        $this->check_permission('savings_manage_schemes');
        $scheme = $this->db->where('id', $id)->get('savings_schemes')->row();
        if (!$scheme) {
            $this->session->set_flashdata('error', 'Scheme not found.');
            redirect('admin/savings/schemes');
        }
        // Clear existing default then set new one
        $this->db->update('savings_schemes', ['is_default' => 0]);
        $this->db->where('id', $id)->update('savings_schemes', ['is_default' => 1]);
        $this->session->set_flashdata('success', '"' . $scheme->scheme_name . '" is now the default scheme for new members.');
        redirect('admin/savings/schemes');
    }

    /**
     * Manage Schemes
     */
    public function schemes() {
        $this->check_permission('savings_manage_schemes');
        $data['title'] = 'Savings Schemes';
        $data['page_title'] = 'Manage Savings Schemes';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Savings', 'url' => 'admin/savings'],
            ['title' => 'Schemes', 'url' => '']
        ];
        
        $data['schemes'] = $this->Savings_model->get_schemes(false);
        // Enrich schemes with member counts and total deposits for the dashboard/charts
        foreach ($data['schemes'] as &$scheme) {
            $scheme->member_count = (int) $this->db->where('scheme_id', $scheme->id)
                                                   ->count_all_results('savings_accounts');
            // Use CI select_sum(field, alias) to avoid invalid SQL
            $row = $this->db->select_sum('current_balance', 'total')->where('scheme_id', $scheme->id)->get('savings_accounts')->row();
            $scheme->total_deposits = (float) ($row->total ?? 0);
        }
        unset($scheme);

        $this->load->model('Member_model');
        $data['members'] = $this->Member_model->get_active_members_dropdown();

        $this->load_view('admin/savings/schemes', $data);
    }
    
    /**
     * Enroll Members to Scheme (Bulk)
     */
    public function enroll_members() {
        $this->check_permission('savings_manage_schemes');
        if ($this->input->method() !== 'post') {
            redirect('admin/savings/schemes');
        }

        $this->load->library('form_validation');
        $this->form_validation->set_rules('scheme_id', 'Scheme', 'required|numeric');
        $this->form_validation->set_rules('member_ids[]', 'Members', 'required');
        $this->form_validation->set_rules('monthly_amount', 'Monthly Amount', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('start_date', 'Start Date', 'required');

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('admin/savings/schemes');
        }

        $scheme_id = (int) $this->input->post('scheme_id');
        $raw_member_ids = $this->input->post('member_ids');
        $monthly_amount = $this->input->post('monthly_amount');
        $start_date = $this->input->post('start_date');

        // Normalize member ids: accept array or comma-separated string
        if (!is_array($raw_member_ids)) {
            if (is_string($raw_member_ids)) {
                $member_ids = array_filter(array_map('trim', explode(',', $raw_member_ids)));
            } else {
                $member_ids = (array) $raw_member_ids;
            }
        } else {
            $member_ids = $raw_member_ids;
        }

        // Ensure we have numeric ids
        $member_ids = array_values(array_filter(array_map(function($v){ return is_numeric($v) ? (int)$v : null; }, $member_ids)));

        if (empty($member_ids)) {
            $this->session->set_flashdata('error', 'No valid members selected');
            redirect('admin/savings/schemes');
        }

        $success = [];
        $failed = [];

        foreach ($member_ids as $member_id) {
            // Check existing active-ish account for same scheme
            $existing = $this->db->where('member_id', $member_id)
                                 ->where('scheme_id', $scheme_id)
                                 ->get('savings_accounts')
                                 ->row();

            if ($existing && in_array($existing->status, ['active', 'suspended', 'matured'])) {
                $failed[] = ['member_id' => $member_id, 'reason' => 'Already enrolled (account ' . ($existing->account_number ?? $existing->id) . ')'];
                continue;
            }

            // If closed or no existing account, proceed to create
            $account_data = [
                'member_id' => $member_id,
                'scheme_id' => $scheme_id,
                'monthly_amount' => $monthly_amount,
                'start_date' => $start_date,
                'status' => 'active'
            ];

            $account_id = $this->Savings_model->create_account($account_data);
            if ($account_id) {
                $success[] = $member_id;
                $this->log_audit('create', 'savings_accounts', 'savings_accounts', $account_id, null, $account_data);
            } else {
                $failed[] = ['member_id' => $member_id, 'reason' => 'Failed to create'];
            }
        }

        // Prepare flash messages
        if (!empty($success)) {
            $this->session->set_flashdata('success', count($success) . ' members enrolled successfully.');
        }

        if (!empty($failed)) {
            $this->session->set_flashdata('error', implode('; ', array_map(function($f){ return 'Member ' . $f['member_id'] . ': ' . $f['reason']; }, $failed)));
        }

        redirect('admin/savings/schemes');
    }
    
    /**
     * Enroll All Members to Scheme (Bulk)
     */
    public function enroll_all_members() {
        $this->check_permission('savings_manage_schemes');
        if ($this->input->method() !== 'post') {
            redirect('admin/savings/schemes');
        }

        $this->load->library('form_validation');
        $this->form_validation->set_rules('scheme_id', 'Scheme', 'required|numeric');
        $this->form_validation->set_rules('monthly_amount', 'Monthly Amount', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('start_date', 'Start Date', 'required');

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('admin/savings/schemes');
        }

        $scheme_id = (int) $this->input->post('scheme_id');
        $monthly_amount = $this->input->post('monthly_amount');
        $start_date = $this->input->post('start_date');
        $force = $this->input->post('force') ? 1 : 0;

        $scheme = $this->db->where('id', $scheme_id)->get('savings_schemes')->row();
        if (!$scheme) {
            $this->session->set_flashdata('error', 'Savings scheme not found.');
            redirect('admin/savings/schemes');
        }

        $this->load->model('Member_model');
        $members = $this->Member_model->get_active_members_dropdown();

        $success = [];
        $failed = [];

        foreach ($members as $m) {
            $member_id = (int) $m->id;
            $existing = $this->db->where('member_id', $member_id)->where('scheme_id', $scheme_id)->get('savings_accounts')->row();

            if ($existing && $force == 0 && in_array($existing->status, ['active', 'suspended', 'matured'])) {
                $failed[] = ['member_id' => $member_id, 'reason' => 'Already enrolled'];
                continue;
            }

            $account_data = [
                'member_id' => $member_id,
                'scheme_id' => $scheme_id,
                'monthly_amount' => $monthly_amount ?: ($scheme->monthly_amount ?? $scheme->min_deposit ?? 0),
                'start_date' => $start_date,
                'status' => 'active'
            ];

            $account_id = $this->Savings_model->create_account($account_data);
            if ($account_id) {
                $success[] = $member_id;
                $this->log_audit('create', 'savings_accounts', 'savings_accounts', $account_id, null, $account_data);
            } else {
                $failed[] = ['member_id' => $member_id, 'reason' => 'Failed to create'];
            }
        }

        if (!empty($success)) {
            $this->session->set_flashdata('success', count($success) . ' members enrolled successfully.');
        }

        if (!empty($failed)) {
            $this->session->set_flashdata('error', implode('; ', array_map(function($f){ return 'Member ' . $f['member_id'] . ': ' . $f['reason']; }, $failed)));
        }

        redirect('admin/savings/schemes');
    }
    
    /**
     * Print Account Statement
     */
    public function statement($id) {
        $this->check_permission('savings_view');
        $account = $this->db->select('sa.*, ss.scheme_name, m.member_code, m.first_name, m.last_name, m.phone, m.address_line1, m.city')
                            ->from('savings_accounts sa')
                            ->join('savings_schemes ss', 'ss.id = sa.scheme_id')
                            ->join('members m', 'm.id = sa.member_id')
                            ->where('sa.id', $id)
                            ->get()
                            ->row();
        
        if (!$account) {
            $this->session->set_flashdata('error', 'Account not found.');
            redirect('admin/savings');
        }
        
        $data['account'] = $account;
        $data['transactions'] = $this->Savings_model->get_transactions($id, 100);
        $data['schedule'] = $this->Savings_model->get_schedule($id);
        
        $this->load->view('admin/savings/print_statement', $data);
    }
    
    /**
     * Send Payment Reminder (Email + Notification)
     */
    public function send_reminder() {
        $this->check_permission('savings_collect');
        $this->load->model('Notification_model');
        $this->load->helper('email');
        
        $account_id = $this->input->post('account_id');
        
        // Debug log
        log_message('debug', 'send_reminder called with POST data: ' . json_encode($this->input->post()));
        
        if (!$account_id) {
            $response = [
                'success' => false, 
                'message' => 'The specified savings account could not be found. Please verify the account ID.',
                'debug' => [
                    'post_data' => $this->input->post(),
                    'account_id' => $account_id
                ]
            ];
            echo json_encode($response);
            return;
        }
        
        // Get account details with member info
        $account = $this->db->select('
            sa.*, 
            m.id as member_id, 
            m.first_name, 
            m.last_name, 
            m.email, 
            m.phone,
            ss.scheme_name
        ')
        ->from('savings_accounts sa')
        ->join('members m', 'm.id = sa.member_id')
        ->join('savings_schemes ss', 'ss.id = sa.scheme_id')
        ->where('sa.id', $account_id)
        ->get()->row();
        
        if (!$account) {
            echo json_encode(['success' => false, 'message' => 'Account not found']);
            return;
        }
        
        // Get pending dues
        $pending_dues = $this->db->where('savings_account_id', $account_id)
                                 ->where('status', 'pending')
                                 ->where('due_date <', date('Y-m-d'))
                                 ->get('savings_schedule')->result();
        
        $total_due = 0;
        $oldest_due_date = null;
        foreach ($pending_dues as $due) {
            $total_due += ($due->due_amount - $due->paid_amount);
            if (!$oldest_due_date || $due->due_date < $oldest_due_date) {
                $oldest_due_date = $due->due_date;
            }
        }
        
        $email_sent = false;
        $notification_sent = false;
        
        // Send email if email exists
        if (!empty($account->email)) {
            $subject = "Payment Reminder - Savings Account #{$account->account_number}";
            
            $message = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; text-align: center;'>
                        <h2 style='color: white; margin: 0;'>Payment Reminder</h2>
                    </div>
                    <div style='padding: 20px; background: #f9f9f9;'>
                        <p>Dear {$account->first_name} {$account->last_name},</p>
                        
                        <p>This is a friendly reminder regarding your savings account with us.</p>
                        
                        <div style='background: white; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                            <table style='width: 100%; border-collapse: collapse;'>
                                <tr>
                                    <td style='padding: 8px 0; color: #666;'>Account Number:</td>
                                    <td style='padding: 8px 0; font-weight: bold;'>{$account->account_number}</td>
                                </tr>
                                <tr>
                                    <td style='padding: 8px 0; color: #666;'>Scheme:</td>
                                    <td style='padding: 8px 0;'>{$account->scheme_name}</td>
                                </tr>
                                <tr>
                                    <td style='padding: 8px 0; color: #666;'>Monthly Amount:</td>
                                    <td style='padding: 8px 0; font-weight: bold;'>" . format_amount($account->monthly_amount) . "</td>
                                </tr>";
            
            if (count($pending_dues) > 0) {
                $message .= "
                                <tr>
                                    <td colspan='2' style='padding-top: 15px; border-top: 1px solid #eee;'></td>
                                </tr>
                                <tr>
                                    <td style='padding: 8px 0; color: #dc3545;'>Pending Installments:</td>
                                    <td style='padding: 8px 0; font-weight: bold; color: #dc3545;'>" . count($pending_dues) . "</td>
                                </tr>
                                <tr>
                                    <td style='padding: 8px 0; color: #dc3545;'>Total Due Amount:</td>
                                    <td style='padding: 8px 0; font-weight: bold; color: #dc3545; font-size: 18px;'>" . format_amount($total_due) . "</td>
                                </tr>
                                <tr>
                                    <td style='padding: 8px 0; color: #dc3545;'>Oldest Due Date:</td>
                                    <td style='padding: 8px 0; color: #dc3545;'>" . format_date($oldest_due_date) . "</td>
                                </tr>";
            }
            
            $message .= "
                            </table>
                        </div>
                        
                        <p style='color: #dc3545; font-weight: bold;'>Please make your payment at the earliest to avoid late fees.</p>
                        
                        <p>If you have already made the payment, please ignore this reminder.</p>
                        
                        <p>For any queries, please contact our office.</p>
                        
                        <p style='margin-top: 30px;'>
                            Best regards,<br>
                            <strong>Windeep Finance</strong>
                        </p>
                    </div>
                    <div style='background: #333; color: white; padding: 15px; text-align: center; font-size: 12px;'>
                        <p style='margin: 0;'>This is an automated reminder. Please do not reply to this email.</p>
                    </div>
                </div>
            ";
            
            $result = send_email($account->email, $subject, $message);
            $email_sent = $result['success'] ?? false;
        }
        
        // Create in-app notification
        $notification_data = [
            'recipient_type' => 'member',
            'recipient_id' => $account->member_id,
            'notification_type' => 'savings_reminder',
            'title' => 'Savings Payment Reminder',
            'message' => count($pending_dues) > 0 
                ? "You have " . count($pending_dues) . " pending installment(s) totaling " . format_amount($total_due) . ". Please make your payment soon."
                : "This is a reminder about your savings account #{$account->account_number}. Monthly amount: " . format_amount($account->monthly_amount),
            'data' => [
                'account_id' => $account->id,
                'account_number' => $account->account_number,
                'pending_dues' => count($pending_dues),
                'total_due' => $total_due
            ]
        ];
        
        $notification_id = $this->Notification_model->create($notification_data);
        $notification_sent = $notification_id ? true : false;
        
        // Prepare response message
        $message_parts = [];
        if ($email_sent) {
            $message_parts[] = "Email sent to {$account->email}";
        } elseif (!empty($account->email)) {
            $message_parts[] = "Failed to send email";
        } else {
            $message_parts[] = "No email address on file";
        }
        
        if ($notification_sent) {
            $message_parts[] = "In-app notification created";
        } else {
            $message_parts[] = "Failed to create notification";
        }
        
        $success = $email_sent || $notification_sent;
        
        echo json_encode([
            'success' => $success,
            'message' => implode('. ', $message_parts),
            'email_sent' => $email_sent,
            'notification_sent' => $notification_sent
        ]);
    }
}
