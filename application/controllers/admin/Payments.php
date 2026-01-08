<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Payments Controller - Payment Collection & History
 */
class Payments extends Admin_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model(['Loan_model', 'Member_model', 'Savings_model', 'Fine_model']);
    }
    
    /**
     * Receive Payment - Universal Payment Collection
     */
    public function receive() {
        $data['title'] = 'Receive Payment';
        $data['page_title'] = 'Receive Payment';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Payments', 'url' => ''],
            ['title' => 'Receive', 'url' => '']
        ];
        
        // Handle form submission
        if ($this->input->method() === 'post') {
            $this->_process_payment();
            return;
        }
        
        // Get member if provided
        $member_id = $this->input->get('member_id');
        $data['member'] = null;
        $data['member_dues'] = [];
        
        if ($member_id) {
            $data['member'] = $this->Member_model->get_by_id($member_id);
            if ($data['member']) {
                // Get all pending dues for this member
                $data['member_dues'] = $this->_get_member_dues($member_id);
            }
        }
        
        // Get recent members for quick selection
        $data['recent_members'] = $this->db->select('m.id, m.member_code, m.first_name, m.last_name, m.phone')
                                           ->from('members m')
                                           ->where('m.status', 'active')
                                           ->order_by('m.created_at', 'DESC')
                                           ->limit(10)
                                           ->get()
                                           ->result();
        
        $this->load_view('admin/payments/receive', $data);
    }
    
    /**
     * Payment History - All Payments
     */
    public function history() {
        $data['title'] = 'Payment History';
        $data['page_title'] = 'Payment History';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Payments', 'url' => ''],
            ['title' => 'History', 'url' => '']
        ];
        
        // Filters
        $filters = [
            'type' => $this->input->get('type'),
            'member_id' => $this->input->get('member_id'),
            'payment_mode' => $this->input->get('payment_mode'),
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to') ?: date('Y-m-d')
        ];
        
        $data['filters'] = $filters;
        $data['payments'] = [];
        $data['total_amount'] = 0;
        
        // Fetch payments based on type
        if ($filters['type'] === 'loan' || !$filters['type']) {
            $loan_payments = $this->_get_loan_payments($filters);
            $data['payments'] = array_merge($data['payments'], $loan_payments);
        }
        
        if ($filters['type'] === 'savings' || !$filters['type']) {
            $savings_payments = $this->_get_savings_payments($filters);
            $data['payments'] = array_merge($data['payments'], $savings_payments);
        }
        
        if ($filters['type'] === 'fine' || !$filters['type']) {
            $fine_payments = $this->_get_fine_payments($filters);
            $data['payments'] = array_merge($data['payments'], $fine_payments);
        }
        
        // Sort by date descending
        usort($data['payments'], function($a, $b) {
            return strtotime($b->payment_date) - strtotime($a->payment_date);
        });
        
        // Calculate totals
        $data['total_amount'] = array_sum(array_column($data['payments'], 'amount'));
        
        // Get members for filter
        $data['all_members'] = $this->db->select('id, member_code, first_name, last_name')
                                        ->from('members')
                                        ->where('status', 'active')
                                        ->order_by('member_code', 'ASC')
                                        ->get()
                                        ->result();
        
        $this->load_view('admin/payments/history', $data);
    }
    
    /**
     * View Payment Receipt
     */
    public function receipt($payment_id) {
        $type = $this->input->get('type') ?: 'loan';
        
        $payment = null;
        $member = null;
        
        switch ($type) {
            case 'loan':
                $payment = $this->db->select('lp.*, l.loan_number, m.member_code, m.first_name, m.last_name, m.phone, m.address')
                                    ->from('loan_payments lp')
                                    ->join('loans l', 'l.id = lp.loan_id')
                                    ->join('members m', 'm.id = l.member_id')
                                    ->where('lp.id', $payment_id)
                                    ->get()
                                    ->row();
                break;
            case 'savings':
                $payment = $this->db->select('st.*, sa.account_number, m.member_code, m.first_name, m.last_name, m.phone, m.address')
                                    ->from('savings_transactions st')
                                    ->join('savings_accounts sa', 'sa.id = st.savings_account_id')
                                    ->join('members m', 'm.id = sa.member_id')
                                    ->where('st.id', $payment_id)
                                    ->get()
                                    ->row();
                break;
            case 'fine':
                $payment = $this->db->select('fp.*, f.fine_code, m.member_code, m.first_name, m.last_name, m.phone, m.address')
                                    ->from('fine_payments fp')
                                    ->join('fines f', 'f.id = fp.fine_id')
                                    ->join('members m', 'm.id = f.member_id')
                                    ->where('fp.id', $payment_id)
                                    ->get()
                                    ->row();
                break;
        }
        
        if (!$payment) {
            $this->session->set_flashdata('error', 'Payment not found.');
            redirect('admin/payments/history');
        }
        
        $data['payment'] = $payment;
        $data['type'] = $type;
        $data['title'] = 'Payment Receipt';
        
        $this->load->view('admin/payments/receipt', $data);
    }
    
    /**
     * Search Members (AJAX)
     */
    public function search_members() {
        $term = $this->input->get('term');
        
        if (strlen($term) < 2) {
            $this->json_response([]);
            return;
        }
        
        $members = $this->db->select('id, member_code, first_name, last_name, phone')
                            ->from('members')
                            ->where('status', 'active')
                            ->group_start()
                                ->like('member_code', $term)
                                ->or_like('first_name', $term)
                                ->or_like('last_name', $term)
                                ->or_like('phone', $term)
                            ->group_end()
                            ->limit(10)
                            ->get()
                            ->result();
        
        $results = array_map(function($m) {
            return [
                'id' => $m->id,
                'text' => $m->member_code . ' - ' . $m->first_name . ' ' . $m->last_name,
                'phone' => $m->phone
            ];
        }, $members);
        
        $this->json_response($results);
    }
    
    /**
     * Get Member Dues (AJAX)
     */
    public function get_member_dues($member_id = null) {
        $member_id = $member_id ?: $this->input->get('member_id');
        
        if (!$member_id) {
            $this->json_response(['error' => 'Member ID required']);
            return;
        }
        
        $dues = $this->_get_member_dues($member_id);
        $this->json_response($dues);
    }
    
    // ===== PRIVATE METHODS =====
    
    /**
     * Process Payment Form Submission
     */
    private function _process_payment() {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('payment_type', 'Payment Type', 'required|in_list[loan,savings,fine]');
        $this->form_validation->set_rules('member_id', 'Member', 'required|numeric');
        $this->form_validation->set_rules('amount', 'Amount', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('payment_mode', 'Payment Mode', 'required');
        
        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('admin/payments/receive');
            return;
        }
        
        $payment_type = $this->input->post('payment_type');
        $member_id = $this->input->post('member_id');
        $amount = $this->input->post('amount');
        
        try {
            $this->db->trans_start();
            
            $payment_data = [
                'payment_mode' => $this->input->post('payment_mode'),
                'payment_date' => $this->input->post('payment_date') ?: date('Y-m-d'),
                'reference_number' => $this->input->post('reference_number'),
                'remarks' => $this->input->post('remarks'),
                'created_by' => $this->session->userdata('admin_id')
            ];
            
            $result = false;
            
            switch ($payment_type) {
                case 'loan':
                    $loan_id = $this->input->post('related_id');
                    if ($loan_id) {
                        $payment_data['loan_id'] = $loan_id;
                        $payment_data['total_amount'] = $amount;
                        $payment_data['payment_type'] = 'regular';
                        $result = $this->Loan_model->record_payment($payment_data);
                    }
                    break;
                    
                case 'savings':
                    $account_id = $this->input->post('related_id');
                    if ($account_id) {
                        $payment_data['savings_account_id'] = $account_id;
                        $payment_data['amount'] = $amount;
                        $payment_data['transaction_type'] = 'deposit';
                        $result = $this->Savings_model->record_payment($payment_data);
                    }
                    break;
                    
                case 'fine':
                    $fine_id = $this->input->post('related_id');
                    if ($fine_id) {
                        $payment_data['fine_id'] = $fine_id;
                        $payment_data['amount'] = $amount;
                        $result = $this->Fine_model->record_payment($payment_data);
                    }
                    break;
            }
            
            $this->db->trans_complete();
            
            if ($result && $this->db->trans_status()) {
                $this->session->set_flashdata('success', 'Payment recorded successfully.');
                redirect('admin/payments/history');
            } else {
                throw new Exception('Failed to record payment');
            }
            
        } catch (Exception $e) {
            $this->session->set_flashdata('error', 'Error: ' . $e->getMessage());
            redirect('admin/payments/receive');
        }
    }
    
    /**
     * Get All Pending Dues for a Member
     */
    private function _get_member_dues($member_id) {
        $dues = [];
        
        // Get pending loan EMIs
        $loans = $this->db->select('l.id, l.loan_number, SUM(li.emi_amount - li.total_paid) as total_due, COUNT(*) as installment_count')
                          ->from('loans l')
                          ->join('loan_installments li', 'li.loan_id = l.id')
                          ->where('l.member_id', $member_id)
                          ->where_in('l.status', ['active', 'overdue'])
                          ->where('li.status', 'pending')
                          ->group_by('l.id')
                          ->get()
                          ->result();
        
        foreach ($loans as $loan) {
            $dues[] = [
                'type' => 'loan',
                'id' => $loan->id,
                'description' => 'Loan ' . $loan->loan_number . ' (' . $loan->installment_count . ' EMIs pending)',
                'amount' => $loan->total_due
            ];
        }
        
        // Get savings accounts with pending monthly
        $savings = $this->db->select('id, account_number, monthly_amount')
                            ->from('savings_accounts')
                            ->where('member_id', $member_id)
                            ->where('status', 'active')
                            ->get()
                            ->result();
        
        foreach ($savings as $account) {
            $dues[] = [
                'type' => 'savings',
                'id' => $account->id,
                'description' => 'Savings Account ' . $account->account_number . ' (Monthly Contribution)',
                'amount' => $account->monthly_amount
            ];
        }
        
        // Get pending fines
        $fines = $this->db->select('id, fine_code, fine_type, (fine_amount - COALESCE(paid_amount, 0) - COALESCE(waived_amount, 0)) as balance')
                          ->from('fines')
                          ->where('member_id', $member_id)
                          ->where_in('status', ['pending', 'partial'])
                          ->having('balance >', 0)
                          ->get()
                          ->result();
        
        foreach ($fines as $fine) {
            $dues[] = [
                'type' => 'fine',
                'id' => $fine->id,
                'description' => 'Fine ' . $fine->fine_code . ' (' . ucfirst(str_replace('_', ' ', $fine->fine_type)) . ')',
                'amount' => $fine->balance
            ];
        }
        
        return $dues;
    }
    
    /**
     * Get Loan Payments
     */
    private function _get_loan_payments($filters) {
        $this->db->select('lp.id, lp.payment_date, lp.total_amount as amount, lp.payment_mode, lp.payment_code, lp.reference_number, 
                          m.member_code, m.first_name, m.last_name, l.loan_number, "loan" as type')
                 ->from('loan_payments lp')
                 ->join('loans l', 'l.id = lp.loan_id')
                 ->join('members m', 'm.id = l.member_id')
                 ->where('lp.is_reversed', 0);
        
        if ($filters['member_id']) {
            $this->db->where('m.id', $filters['member_id']);
        }
        if ($filters['payment_mode']) {
            $this->db->where('lp.payment_mode', $filters['payment_mode']);
        }
        if ($filters['date_from']) {
            $this->db->where('lp.payment_date >=', $filters['date_from']);
        }
        if ($filters['date_to']) {
            $this->db->where('lp.payment_date <=', $filters['date_to']);
        }
        
        return $this->db->get()->result();
    }
    
    /**
     * Get Savings Payments
     */
    private function _get_savings_payments($filters) {
        $this->db->select('st.id, st.transaction_date as payment_date, st.amount, st.payment_mode, st.reference_number, st.reference_number as reference_number,
                          m.member_code, m.first_name, m.last_name, sa.account_number, "savings" as type')
                 ->from('savings_transactions st')
                 ->join('savings_accounts sa', 'sa.id = st.savings_account_id')
                 ->join('members m', 'm.id = sa.member_id')
                 ->where('st.transaction_type', 'deposit');
        
        if ($filters['member_id']) {
            $this->db->where('m.id', $filters['member_id']);
        }
        if ($filters['payment_mode']) {
            $this->db->where('st.payment_mode', $filters['payment_mode']);
        }
        if ($filters['date_from']) {
            $this->db->where('st.transaction_date >=', $filters['date_from']);
        }
        if ($filters['date_to']) {
            $this->db->where('st.transaction_date <=', $filters['date_to']);
        }
        
        return $this->db->get()->result();
    }
    
    /**
     * Get Fine Payments
     */
    private function _get_fine_payments($filters) {
        // Since fines don't have a separate payments table, query paid fines from fines table
        $this->db->select('f.id, f.fine_date as payment_date, f.paid_amount as amount, "cash" as payment_mode, f.fine_code as reference_number,
                          m.member_code, m.first_name, m.last_name, f.fine_code, "fine" as type')
                 ->from('fines f')
                 ->join('members m', 'm.id = f.member_id')
                 ->where('f.status', 'paid')
                 ->where('f.paid_amount >', 0);

        if ($filters['member_id']) {
            $this->db->where('m.id', $filters['member_id']);
        }
        if ($filters['date_from']) {
            $this->db->where('f.fine_date >=', $filters['date_from']);
        }
        if ($filters['date_to']) {
            $this->db->where('f.fine_date <=', $filters['date_to']);
        }

        return $this->db->get()->result();
    }
}
