<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/Member_Controller.php';

/**
 * Member Savings Controller
 */
class Savings extends Member_Controller {
    
    /**
     * My Savings Accounts
     */
    public function index() {
        $data['title'] = 'My Savings';
        $data['page_title'] = 'Savings & Contributions';
        
        $data['accounts'] = $this->db->select('sa.*, ss.scheme_name, ss.interest_rate')
                                    ->from('savings_accounts sa')
                                    ->join('savings_schemes ss', 'ss.id = sa.scheme_id')
                                    ->where('sa.member_id', $this->member->id)
                                    ->get()
                                    ->result();
        
        // Get recent transactions for all accounts
        foreach ($data['accounts'] as $account) {
            $account->recent_transactions = $this->db->where('savings_account_id', $account->id)
                                                    ->order_by('transaction_date', 'DESC')
                                                    ->limit(5)
                                                    ->get('savings_transactions')
                                                    ->result();
        }
        
        $this->load_member_view('member/savings/index', $data);
    }

    /**
     * View Savings Account Detail with All Transactions
     */
    public function view($account_id) {
        $account = $this->db->select('sa.*, ss.scheme_name, ss.interest_rate, ss.monthly_amount as scheme_monthly_amount')
                            ->from('savings_accounts sa')
                            ->join('savings_schemes ss', 'ss.id = sa.scheme_id')
                            ->where('sa.id', $account_id)
                            ->where('sa.member_id', $this->member->id)
                            ->get()
                            ->row();
        
        if (!$account) {
            $this->session->set_flashdata('error', 'Savings account not found.');
            redirect('member/savings');
        }
        
        $data['title'] = 'Savings Details';
        $data['page_title'] = 'Savings Account - ' . $account->account_number;
        $data['account'] = $account;
        
        // Date filters
        $date_from = $this->input->get('date_from') ?: date('Y-m-01', strtotime('-6 months'));
        $date_to = $this->input->get('date_to') ?: date('Y-m-d');
        $data['date_from'] = $date_from;
        $data['date_to'] = $date_to;
        
        // All transactions date-wise
        $this->db->where('savings_account_id', $account_id);
        $this->db->where('transaction_date >=', $date_from);
        $this->db->where('transaction_date <=', $date_to);
        $this->db->order_by('transaction_date', 'DESC');
        $this->db->order_by('created_at', 'DESC');
        $data['transactions'] = $this->db->get('savings_transactions')->result();
        
        // Summary stats
        $data['total_deposits'] = $this->db->select_sum('amount')
                                          ->where('savings_account_id', $account_id)
                                          ->where('transaction_type', 'deposit')
                                          ->get('savings_transactions')
                                          ->row()->amount ?? 0;
        
        $data['total_withdrawals'] = $this->db->select_sum('amount')
                                             ->where('savings_account_id', $account_id)
                                             ->where('transaction_type', 'withdrawal')
                                             ->get('savings_transactions')
                                             ->row()->amount ?? 0;
        
        // Payment schedule
        $data['schedule'] = $this->db->where('savings_account_id', $account_id)
                                    ->order_by('due_date', 'ASC')
                                    ->get('savings_schedule')
                                    ->result();
        
        $this->load_member_view('member/savings/view', $data);
    }
}
