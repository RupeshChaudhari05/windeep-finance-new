<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Dashboard Controller - Admin Dashboard
 */
class Dashboard extends Admin_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model(['Report_model', 'Loan_model', 'Savings_model', 'Member_model']);
    }
    
    /**
     * Dashboard Home
     */
    public function index() {
        $data['title'] = 'Dashboard';
        $data['page_title'] = 'Dashboard';
        $data['breadcrumb'] = [
            ['title' => 'Home', 'url' => '']
        ];
        
        // Get dashboard stats
        $data['stats'] = $this->Report_model->get_dashboard_stats();
        
        // Get recent activities
        $this->load->model('Activity_model');
        $data['recent_activities'] = $this->Activity_model->get_recent(10);
        
        // Get pending applications
        $data['pending_applications'] = $this->Loan_model->get_pending_applications();
        
        // Get overdue loans
        $data['overdue_loans'] = $this->Loan_model->get_overdue_loans();
        
        // Get due today
        $data['due_today'] = $this->Loan_model->get_due_today();
        
        // Get chart data
        $data['monthly_trend'] = $this->Report_model->get_monthly_trend(date('Y'));
        
        // Get this month's summary
        $data['monthly_summary'] = $this->Report_model->get_monthly_summary(date('Y'), date('m'));
        
        // Notification count for admin dashboard
        $this->load->model('Notification_model');
        $admin_id = $this->session->userdata('admin_id');
        $data['unread_notifications_count'] = $this->Notification_model->count_unread('admin', $admin_id);
        
        // Fee summaries (membership fee, other member fees)
        $data['fee_summary'] = $this->Report_model->get_fee_summary();
        
        $this->load_view('admin/dashboard/index', $data);
    }
    
    /**
     * Quick Stats API
     */
    public function quick_stats() {
        $stats = $this->Report_model->get_dashboard_stats();
        $this->json_response($stats);
    }
    
    /**
     * Today's Collections
     */
    public function today_collections() {
        $data['title'] = 'Today\'s Collections';
        $data['page_title'] = 'Today\'s Collections';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Today\'s Collections', 'url' => '']
        ];
        
        $today = date('Y-m-d');
        
        // Loan collections
        $data['loan_collections'] = $this->db->select('
            lp.*, l.loan_number, m.member_code, m.first_name, m.last_name
        ')
        ->from('loan_payments lp')
        ->join('loans l', 'l.id = lp.loan_id')
        ->join('members m', 'm.id = l.member_id')
        ->where('lp.payment_date', $today)
        ->where('lp.is_reversed', 0)
        ->get()
        ->result();
        
        // Savings collections
        $data['savings_collections'] = $this->db->select('
            st.*, sa.account_number, m.member_code, m.first_name, m.last_name
        ')
        ->from('savings_transactions st')
        ->join('savings_accounts sa', 'sa.id = st.savings_account_id')
        ->join('members m', 'm.id = sa.member_id')
        ->where('st.transaction_type', 'deposit')
        ->where('DATE(st.created_at)', $today)
        ->get()
        ->result();
        
        $this->load_view('admin/dashboard/today_collections', $data);
    }
    
    /**
     * Notifications
     */
    public function notifications() {
        $admin_id = $this->session->userdata('admin_id');
        
        $this->load->model('Notification_model');
        $notifications = $this->Notification_model->get_for('admin', $admin_id, 50);
        
        $this->json_response($notifications);
    }
    
    /**
     * Mark Notification Read
     */
    public function mark_notification_read($id) {
        $this->db->where('id', $id)
                 ->update('notifications', [
                     'is_read' => 1,
                     'read_at' => date('Y-m-d H:i:s')
                 ]);
        
        $this->json_response(['success' => true]);
    }
    
    /**
     * Search
     */
    public function search() {
        $query = $this->input->get('q');
        
        if (strlen($query) < 2) {
            $this->json_response(['results' => []]);
            return;
        }
        
        $results = [];
        
        // Search members
        $members = $this->db->select('id, member_code, first_name, last_name, phone')
                            ->like('member_code', $query)
                            ->or_like('first_name', $query)
                            ->or_like('last_name', $query)
                            ->or_like('phone', $query)
                            ->limit(5)
                            ->get('members')
                            ->result();
        
        foreach ($members as $m) {
            $results[] = [
                'type' => 'member',
                'id' => $m->id,
                'text' => $m->member_code . ' - ' . $m->first_name . ' ' . $m->last_name,
                'url' => site_url('admin/members/view/' . $m->id)
            ];
        }
        
        // Search loans
        $loans = $this->db->select('id, loan_number')
                          ->like('loan_number', $query)
                          ->limit(5)
                          ->get('loans')
                          ->result();
        
        foreach ($loans as $l) {
            $results[] = [
                'type' => 'loan',
                'id' => $l->id,
                'text' => 'Loan: ' . $l->loan_number,
                'url' => site_url('admin/loans/view/' . $l->id)
            ];
        }
        
        // Search savings accounts
        $savings = $this->db->select('id, account_number')
                            ->like('account_number', $query)
                            ->limit(5)
                            ->get('savings_accounts')
                            ->result();
        
        foreach ($savings as $s) {
            $results[] = [
                'type' => 'savings',
                'id' => $s->id,
                'text' => 'Savings: ' . $s->account_number,
                'url' => site_url('admin/savings/view/' . $s->id)
            ];
        }
        
        $this->json_response(['results' => $results]);
    }
}
