<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bank extends Admin_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('Bank_model');
        $this->load->model('Member_model');
        $this->data['active_menu'] = 'bank';
    }
    
    public function import() {
        $this->data['page_title'] = 'Bank Statement Import';
        $this->data['breadcrumb'] = [['label' => 'Bank Import']];
        
        // Get bank accounts
        $this->data['bank_accounts'] = $this->Bank_model->get_accounts();
        
        // Get recent imports
        $this->data['recent_imports'] = $this->Bank_model->get_imports(10);
        
        $this->load->view('admin/layouts/header', $this->data);
        $this->load->view('admin/layouts/sidebar', $this->data);
        $this->load->view('admin/bank/import', $this->data);
        $this->load->view('admin/layouts/footer', $this->data);
    }
    
    public function upload() {
        if (!$this->input->post()) {
            redirect('admin/bank/import');
        }
        
        $bank_account_id = $this->input->post('bank_account_id');
        $statement_date = $this->input->post('statement_date');
        
        // Handle file upload
        $config['upload_path'] = './uploads/bank_statements/';
        $config['allowed_types'] = 'csv|xlsx|xls';
        $config['max_size'] = 10240; // 10MB
        $config['file_name'] = 'statement_' . time();
        
        $this->load->library('upload', $config);
        
        if (!$this->upload->do_upload('statement_file')) {
            $this->session->set_flashdata('error', $this->upload->display_errors());
            redirect('admin/bank/import');
        }
        
        $file_data = $this->upload->data();
        
        // Import statement
        $result = $this->Bank_model->import_statement(
            $file_data['full_path'],
            $bank_account_id,
            $this->session->userdata('admin_id')
        );
        
        if ($result['success']) {
            $this->session->set_flashdata('success', "Bank statement imported successfully. {$result['imported']} transactions imported, {$result['auto_matched']} auto-matched.");
            redirect('admin/bank/view_import/' . $result['import_id']);
        } else {
            $this->session->set_flashdata('error', $result['message']);
            redirect('admin/bank/import');
        }
    }
    
    public function view_import($import_id) {
        $import = $this->Bank_model->get_import($import_id);
        if (!$import) {
            show_404();
        }
        
        $this->data['page_title'] = 'Bank Import - ' . $import->import_code;
        $this->data['breadcrumb'] = [
            ['label' => 'Bank Import', 'link' => site_url('admin/bank/import')],
            ['label' => $import->import_code]
        ];
        
        $this->data['import'] = $import;
        $this->data['transactions'] = $this->Bank_model->get_import_transactions($import_id);
        
        $this->load->view('admin/layouts/header', $this->data);
        $this->load->view('admin/layouts/sidebar', $this->data);
        $this->load->view('admin/bank/view_import', $this->data);
        $this->load->view('admin/layouts/footer', $this->data);
    }
    
    public function match_transaction() {
        if (!$this->input->post()) {
            $this->ajax_response(false, 'Invalid request');
        }
        
        $transaction_id = $this->input->post('transaction_id');
        $match_type = $this->input->post('match_type'); // savings_payment, loan_payment, etc.
        $match_id = $this->input->post('match_id');
        
        $result = $this->Bank_model->match_transaction($transaction_id, $match_type, $match_id);
        
        if ($result) {
            $this->log_activity('bank_transaction_matched', "Matched bank transaction #{$transaction_id}");
            $this->ajax_response(true, 'Transaction matched successfully');
        } else {
            $this->ajax_response(false, 'Failed to match transaction');
        }
    }
    
    public function confirm_match() {
        if (!$this->input->post()) {
            $this->ajax_response(false, 'Invalid request');
        }
        
        $transaction_id = $this->input->post('transaction_id');
        
        $result = $this->Bank_model->confirm_transaction($transaction_id);
        
        if ($result) {
            $this->log_activity('bank_transaction_confirmed', "Confirmed bank transaction #{$transaction_id}");
            $this->ajax_response(true, 'Transaction confirmed');
        } else {
            $this->ajax_response(false, 'Failed to confirm transaction');
        }
    }
    
    public function unmatched() {
        $this->data['page_title'] = 'Unmatched Transactions';
        $this->data['breadcrumb'] = [
            ['label' => 'Bank Import', 'link' => site_url('admin/bank/import')],
            ['label' => 'Unmatched']
        ];
        
        // Get unmatched transactions
        $this->data['transactions'] = $this->Bank_model->get_unmatched_transactions();
        
        // Get potential matches
        $this->data['savings_payments'] = $this->Bank_model->get_potential_savings_matches();
        $this->data['loan_payments'] = $this->Bank_model->get_potential_loan_matches();
        
        $this->load->view('admin/layouts/header', $this->data);
        $this->load->view('admin/layouts/sidebar', $this->data);
        $this->load->view('admin/bank/unmatched', $this->data);
        $this->load->view('admin/layouts/footer', $this->data);
    }
    
    /**
     * Bank Transactions with Mapping Interface
     */
    public function transactions() {
        $this->data['page_title'] = 'Bank Transactions';
        $this->data['breadcrumb'] = [
            ['label' => 'Bank', 'link' => site_url('admin/bank/import')],
            ['label' => 'Transactions']
        ];
        
        // Get bank accounts for filter
        $this->data['bank_accounts'] = $this->Bank_model->get_accounts();
        
        // Get filters
        $filters = [
            'bank_id' => $this->input->get('bank_id'),
            'from_date' => $this->input->get('from_date') ?: date('Y-m-01'),
            'to_date' => $this->input->get('to_date') ?: date('Y-m-d'),
            'mapping_status' => $this->input->get('mapping_status')
        ];
        
        $this->data['filters'] = $filters;
        
        // Get transactions
        $this->data['transactions'] = $this->Bank_model->get_transactions_for_mapping($filters);
        
        $this->load->view('admin/layouts/header', $this->data);
        $this->load->view('admin/layouts/sidebar', $this->data);
        $this->load->view('admin/bank/transactions', $this->data);
        $this->load->view('admin/layouts/footer', $this->data);
    }
    
    /**
     * Search Members (AJAX)
     */
    public function search_members() {
        if (!$this->input->is_ajax_request()) {
            $this->ajax_response(false, 'Invalid request');
            return;
        }
        
        $search = $this->input->post('search');
        
        $members = $this->db->select('id, member_code, first_name, last_name, phone, status')
                            ->from('members')
                            ->group_start()
                                ->like('member_code', $search)
                                ->or_like('first_name', $search)
                                ->or_like('last_name', $search)
                                ->or_like('phone', $search)
                            ->group_end()
                            ->limit(20)
                            ->get()
                            ->result();
        
        $this->ajax_response(true, 'Members found', ['members' => $members]);
    }
    
    /**
     * Get Member Accounts (AJAX)
     */
    public function get_member_accounts() {
        if (!$this->input->is_ajax_request()) {
            $this->ajax_response(false, 'Invalid request');
            return;
        }
        
        $member_id = $this->input->post('member_id');
        
        // Get savings accounts
        $savings_accounts = $this->db->select('id, account_number, current_balance as balance')
                                     ->from('savings_accounts')
                                     ->where('member_id', $member_id)
                                     ->where('status', 'active')
                                     ->get()
                                     ->result();
        
        // Get active loans
        $loans = $this->db->select('id, loan_number, outstanding_principal as outstanding')
                          ->from('loans')
                          ->where('member_id', $member_id)
                          ->where('status', 'active')
                          ->get()
                          ->result();
        
        $this->ajax_response(true, 'Accounts found', [
            'savings_accounts' => $savings_accounts,
            'loans' => $loans
        ]);
    }
    
    /**
     * Save Transaction Mapping (AJAX)
     */
    public function save_transaction_mapping() {
        if (!$this->input->is_ajax_request()) {
            $this->ajax_response(false, 'Invalid request');
            return;
        }
        
        $transaction_id = $this->input->post('transaction_id');
        $paying_member_id = $this->input->post('paying_member_id');
        $paid_for_member_id = $this->input->post('paid_for_member_id');
        $transaction_type = $this->input->post('transaction_type');
        $related_account = $this->input->post('related_account');
        $remarks = $this->input->post('remarks');
        
        $admin_id = $this->session->userdata('admin_id');
        
        $update_data = [
            'paid_by_member_id' => $paying_member_id,
            'paid_for_member_id' => $paid_for_member_id ?: $paying_member_id,
            'transaction_category' => $transaction_type,
            'mapping_status' => 'mapped',
            'mapping_remarks' => $remarks,
            'mapped_by' => $admin_id,
            'mapped_at' => date('Y-m-d H:i:s'),
            'updated_by' => $admin_id,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Handle related account
        if ($related_account) {
            $parts = explode('_', $related_account);
            if (count($parts) == 2) {
                $update_data['related_type'] = $parts[0];
                $update_data['related_id'] = $parts[1];
            }
        }
        
        $result = $this->db->where('id', $transaction_id)
                           ->update('bank_transactions', $update_data);
        
        if ($result) {
            // Process the transaction based on type
            $this->process_mapped_transaction($transaction_id, $transaction_type, $update_data);
            
            $this->log_activity('bank_transaction_mapped', "Mapped bank transaction #{$transaction_id} - Type: {$transaction_type}");
            $this->ajax_response(true, 'Transaction mapped successfully');
        } else {
            $this->ajax_response(false, 'Failed to save mapping');
        }
    }
    
    /**
     * Process Mapped Transaction
     */
    private function process_mapped_transaction($transaction_id, $type, $data) {
        $txn = $this->db->where('id', $transaction_id)->get('bank_transactions')->row();
        if (!$txn) return;
        
        $admin_id = $this->session->userdata('admin_id');
        
        switch ($type) {
            case 'emi':
                // Create loan payment record
                if (isset($data['related_id']) && $data['related_type'] == 'loan') {
                    $this->load->model('Loan_model');
                    // Process EMI payment with tracking
                    $amount = $txn->credit_amount ?: abs($txn->debit_amount);
                    $this->Loan_model->record_payment(
                        $data['related_id'],
                        $amount,
                        'bank_transfer',
                        $transaction_id,
                        $admin_id
                    );
                }
                break;
                
            case 'savings':
                // Create savings deposit record
                if (isset($data['related_id']) && $data['related_type'] == 'savings') {
                    $this->load->model('Savings_model');
                    $amount = $txn->credit_amount ?: abs($txn->debit_amount);
                    $this->Savings_model->record_deposit(
                        $data['related_id'],
                        $amount,
                        'bank_transfer',
                        $transaction_id,
                        $admin_id
                    );
                }
                break;
                
            case 'fine':
                // Record fine payment
                $this->load->model('Fine_model');
                // Find pending fine for member
                $fine = $this->db->where('member_id', $data['paid_for_member_id'])
                                 ->where('status', 'pending')
                                 ->order_by('fine_date', 'ASC')
                                 ->get('fines')
                                 ->row();
                if ($fine) {
                    $amount = $txn->credit_amount ?: abs($txn->debit_amount);
                    $this->Fine_model->record_payment($fine->id, $amount, 'bank_transfer', $transaction_id, $admin_id);
                }
                break;
        }
    }
    
    /**
     * AJAX Response Helper
     */
    protected function ajax_response($success, $message, $data = []) {
        $response = array_merge(['success' => $success, 'message' => $message], $data);
        echo json_encode($response);
        exit;
    }
}
