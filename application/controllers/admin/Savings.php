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
        
        $this->db->select('sa.*, ss.scheme_name, m.member_code, m.first_name, m.last_name, m.phone');
        $this->db->from('savings_accounts sa');
        $this->db->join('savings_schemes ss', 'ss.id = sa.scheme_id');
        $this->db->join('members m', 'm.id = sa.member_id');
        
        if ($status = $this->input->get('status')) {
            $this->db->where('sa.status', $status);
        }
        
        $this->db->order_by('sa.created_at', 'DESC');
        $this->db->limit($per_page, ($page - 1) * $per_page);
        
        $data['accounts'] = $this->db->get()->result();
        $data['schemes'] = $this->Savings_model->get_schemes();
        
        $this->load_view('admin/savings/index', $data);
    }
    
    /**
     * View Savings Account
     */
    public function view($id) {
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
        $data['schedule'] = $this->Savings_model->get_schedule($id);
        $data['transactions'] = $this->Savings_model->get_transactions($id);
        
        $this->load_view('admin/savings/view', $data);
    }
    
    /**
     * Create Savings Account Form
     */
    public function create() {
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
            $this->log_audit('savings_accounts', $account_id, 'create', null, $account_data);
            $this->session->set_flashdata('success', 'Savings account created successfully.');
            redirect('admin/savings/view/' . $account_id);
        } else {
            $this->session->set_flashdata('error', 'Failed to create savings account.');
            redirect('admin/savings/create');
        }
    }
    
    /**
     * Collect Savings Payment
     */
    public function collect($id = null) {
        $data['title'] = 'Collect Savings';
        $data['page_title'] = 'Collect Savings Payment';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Savings', 'url' => 'admin/savings'],
            ['title' => 'Collect', 'url' => '']
        ];
        
        // If account ID provided, pre-fill
        if ($id) {
            $data['account'] = $this->db->select('sa.*, m.member_code, m.first_name, m.last_name')
                                        ->from('savings_accounts sa')
                                        ->join('members m', 'm.id = sa.member_id')
                                        ->where('sa.id', $id)
                                        ->get()
                                        ->row();
            
            $data['pending_schedule'] = $this->db->where('savings_account_id', $id)
                                                  ->where_in('status', ['pending', 'partial'])
                                                  ->order_by('due_month', 'ASC')
                                                  ->get('savings_schedule')
                                                  ->result();
        }
        
        $this->load_view('admin/savings/collect', $data);
    }
    
    /**
     * Record Savings Payment
     */
    public function record_payment() {
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
                
                $this->log_audit('savings_transactions', $transaction_id, 'create', null, $payment_data);
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
     * Pending Dues
     */
    public function pending() {
        $data['title'] = 'Pending Savings Dues';
        $data['page_title'] = 'Pending Dues';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Savings', 'url' => 'admin/savings'],
            ['title' => 'Pending Dues', 'url' => '']
        ];
        
        $month = $this->input->get('month') ?: date('Y-m-01');
        
        $data['pending_dues'] = $this->Savings_model->get_pending_dues($month);
        $data['month'] = $month;
        $data['collection_summary'] = $this->Savings_model->get_monthly_collection($month);
        
        $this->load_view('admin/savings/pending', $data);
    }
    
    /**
     * Overdue List
     */
    public function overdue() {
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
     * Manage Schemes
     */
    public function schemes() {
        $data['title'] = 'Savings Schemes';
        $data['page_title'] = 'Manage Savings Schemes';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Savings', 'url' => 'admin/savings'],
            ['title' => 'Schemes', 'url' => '']
        ];
        
        $data['schemes'] = $this->Savings_model->get_schemes(false);
        
        $this->load_view('admin/savings/schemes', $data);
    }
    
    /**
     * Print Account Statement
     */
    public function statement($id) {
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
}
