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
        $data['title'] = 'My Security ';
        $data['page_title'] = 'Security & Contributions';
        
        $data['accounts'] = $this->db->select('sa.*, ss.scheme_name, ss.interest_rate')
                                    ->from('savings_accounts sa')
                                    ->join('savings_schemes ss', 'ss.id = sa.scheme_id')
                                    ->where('sa.member_id', $this->member->id)
                                    ->get()
                                    ->result();
        
        // Get recent transactions for all accounts (exclude reversed — they are ghost entries)
        foreach ($data['accounts'] as $account) {
            $account->recent_transactions = $this->db
                ->where('savings_account_id', $account->id)
                ->where('is_reversed', 0)
                ->order_by('transaction_date', 'DESC')
                ->order_by('id', 'DESC')
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
            $this->session->set_flashdata('error', 'Security account not found.');
            redirect('member/savings');
        }
        
        $data['title'] = 'Security Details';
        $data['page_title'] = 'Security Account - ' . $account->account_number;
        $data['account'] = $account;
        
        // Date filters
        $date_from = $this->input->get('date_from') ?: date('Y-m-01', strtotime('-6 months'));
        $date_to = $this->input->get('date_to') ?: date('Y-m-d');
        $data['date_from'] = $date_from;
        $data['date_to'] = $date_to;
        
        // ── Industry-standard passbook: recompute running balance dynamically ──
        // Reason: savings_transactions.balance_after becomes STALE when a
        // mid-history transaction is reversed (bank model correctly updates
        // savings_accounts.current_balance, but old rows keep their snapshot).
        // Fix: load ALL non-reversed transactions newest-first, walk backwards
        // from the always-accurate current_balance to rebuild correct balance_after.

        $all_txns = $this->db
            ->where('savings_account_id', $account_id)
            ->where('is_reversed', 0)
            ->order_by('transaction_date', 'DESC')
            ->order_by('id', 'DESC')
            ->get('savings_transactions')
            ->result();

        // Start from the true current balance and walk backwards through history
        $running_balance = (float) ($account->current_balance ?? 0);
        foreach ($all_txns as $txn) {
            // The running_balance at this point IS the balance AFTER this transaction
            $txn->balance_after_computed = $running_balance;
            // Undo the effect of this transaction to get the balance BEFORE it
            if (in_array($txn->transaction_type, ['deposit', 'interest'])) {
                $running_balance -= (float) $txn->amount;
            } else {
                // withdrawal, loan_adjustment, etc. reduce balance
                $running_balance += (float) $txn->amount;
            }
        }

        // Filter to requested date range and pass to view
        $from_ts = strtotime($date_from);
        $to_ts   = strtotime($date_to . ' 23:59:59');
        $data['transactions'] = array_values(array_filter($all_txns, function ($txn) use ($from_ts, $to_ts) {
            $ts = strtotime($txn->transaction_date);
            return $ts >= $from_ts && $ts <= $to_ts;
        }));

        // ── Summary stats: always exclude reversed transactions ──
        $data['total_deposits'] = (float) ($this->db->select_sum('amount')
            ->where('savings_account_id', $account_id)
            ->where('transaction_type', 'deposit')
            ->where('is_reversed', 0)
            ->get('savings_transactions')
            ->row()->amount ?? 0);

        $data['total_withdrawals'] = (float) ($this->db->select_sum('amount')
            ->where('savings_account_id', $account_id)
            ->where('transaction_type', 'withdrawal')
            ->where('is_reversed', 0)
            ->get('savings_transactions')
            ->row()->amount ?? 0);
        
        // Payment schedule
        $data['schedule'] = $this->db->where('savings_account_id', $account_id)
                                    ->order_by('due_date', 'ASC')
                                    ->get('savings_schedule')
                                    ->result();
        
        $this->load_member_view('member/savings/view', $data);
    }
}
