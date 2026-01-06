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
}
