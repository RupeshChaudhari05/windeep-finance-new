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
    
    // ─── Dashboard Card Detail AJAX Endpoints ───
    
    /**
     * Active Members Detail (for modal)
     */
    public function card_members() {
        $members = $this->db->select('id, member_code, first_name, last_name, phone, email, status, created_at')
                            ->where('status', 'active')
                            ->order_by('created_at', 'DESC')
                            ->limit(50)
                            ->get('members')
                            ->result();
        $total = $this->db->where('status', 'active')->count_all_results('members');
        $this->json_response(['data' => $members, 'total' => $total]);
    }
    
    /**
     * Total Savings Detail (for modal)
     */
    public function card_savings() {
        $accounts = $this->db->select('sa.id, sa.account_number, sa.current_balance, sa.total_deposited, sa.status, m.member_code, m.first_name, m.last_name')
                             ->from('savings_accounts sa')
                             ->join('members m', 'm.id = sa.member_id')
                             ->where('sa.status', 'active')
                             ->order_by('sa.current_balance', 'DESC')
                             ->limit(50)
                             ->get()
                             ->result();
        $totals = $this->db->select('SUM(current_balance) as total_balance, COUNT(*) as total_accounts, SUM(total_deposited) as total_deposited')
                           ->where('status', 'active')
                           ->get('savings_accounts')
                           ->row();
        $this->json_response(['data' => $accounts, 'totals' => $totals]);
    }
    
    /**
     * Loan Outstanding Detail (for modal)
     */
    public function card_loans() {
        $loans = $this->db->select('l.id, l.loan_number, l.principal_amount, l.outstanding_principal, l.outstanding_interest, l.emi_amount, l.status, m.member_code, m.first_name, m.last_name, lp.product_name')
                          ->from('loans l')
                          ->join('members m', 'm.id = l.member_id')
                          ->join('loan_products lp', 'lp.id = l.loan_product_id', 'left')
                          ->where('l.status', 'active')
                          ->order_by('l.outstanding_principal', 'DESC')
                          ->limit(50)
                          ->get()
                          ->result();
        $totals = $this->db->select('SUM(outstanding_principal) as total_principal, SUM(outstanding_interest) as total_interest, COUNT(*) as total_loans, SUM(principal_amount) as total_disbursed')
                           ->where('status', 'active')
                           ->get('loans')
                           ->row();
        $this->json_response(['data' => $loans, 'totals' => $totals]);
    }
    
    /**
     * Overdue Amount Detail (for modal)
     */
    public function card_overdue() {
        $overdue = $this->db->select('li.id, li.installment_number, li.due_date, li.emi_amount, li.total_paid, (li.emi_amount - li.total_paid) as overdue_amount, l.loan_number, l.id as loan_id, m.member_code, m.first_name, m.last_name, m.phone')
                            ->from('loan_installments li')
                            ->join('loans l', 'l.id = li.loan_id')
                            ->join('members m', 'm.id = l.member_id')
                            ->where('l.status', 'active')
                            ->where('li.status', 'pending')
                            ->where('li.due_date <', date('Y-m-d'))
                            ->order_by('li.due_date', 'ASC')
                            ->limit(100)
                            ->get()
                            ->result();
        $this->json_response(['data' => $overdue]);
    }
    
    /**
     * Pending Applications Detail (for modal)
     */
    public function card_applications() {
        $apps = $this->db->select('la.id, la.application_number, la.requested_amount, la.purpose AS loan_purpose, la.status, la.created_at, m.member_code, m.first_name, m.last_name')
                         ->from('loan_applications la')
                         ->join('members m', 'm.id = la.member_id')
                         ->where_in('la.status', ['pending', 'under_review', 'guarantor_pending'])
                         ->order_by('la.created_at', 'DESC')
                         ->get()
                         ->result();
        $this->json_response(['data' => $apps]);
    }
    
    /**
     * Monthly Collection Detail (for modal)
     */
    public function card_collection() {
        $month = date('m');
        $year = date('Y');
        
        $loan_collections = $this->db->select('lp.id, lp.payment_date, lp.total_amount, lp.principal_component, lp.interest_component, l.loan_number, m.member_code, m.first_name, m.last_name')
                                     ->from('loan_payments lp')
                                     ->join('loans l', 'l.id = lp.loan_id')
                                     ->join('members m', 'm.id = l.member_id')
                                     ->where('MONTH(lp.payment_date)', $month)
                                     ->where('YEAR(lp.payment_date)', $year)
                                     ->where('lp.is_reversed', 0)
                                     ->order_by('lp.payment_date', 'DESC')
                                     ->limit(50)
                                     ->get()
                                     ->result();
        
        $savings_collections = $this->db->select('st.id, st.transaction_date, st.amount, sa.account_number, m.member_code, m.first_name, m.last_name')
                                        ->from('savings_transactions st')
                                        ->join('savings_accounts sa', 'sa.id = st.savings_account_id')
                                        ->join('members m', 'm.id = sa.member_id')
                                        ->where('st.transaction_type', 'deposit')
                                        ->where('MONTH(st.created_at)', $month)
                                        ->where('YEAR(st.created_at)', $year)
                                        ->order_by('st.transaction_date', 'DESC')
                                        ->limit(50)
                                        ->get()
                                        ->result();
        
        $this->json_response(['loan_collections' => $loan_collections, 'savings_collections' => $savings_collections]);
    }
    
    /**
     * Monthly Disbursement Detail (for modal)
     */
    public function card_disbursed() {
        $month = date('m');
        $year = date('Y');
        
        $disbursed = $this->db->select('l.id, l.loan_number, l.principal_amount, l.disbursement_date, l.emi_amount, l.tenure_months, m.member_code, m.first_name, m.last_name, lp.product_name')
                              ->from('loans l')
                              ->join('members m', 'm.id = l.member_id')
                              ->join('loan_products lp', 'lp.id = l.loan_product_id', 'left')
                              ->where('MONTH(l.disbursement_date)', $month)
                              ->where('YEAR(l.disbursement_date)', $year)
                              ->order_by('l.disbursement_date', 'DESC')
                              ->get()
                              ->result();
        $this->json_response(['data' => $disbursed]);
    }
    
    /**
     * Pending Fines Detail (for modal)
     */
    public function card_fines() {
        $fines = $this->db->select('f.id, f.fine_code, f.fine_amount, f.balance_amount, f.status, f.fine_date, f.remarks AS reason, l.loan_number, m.member_code, m.first_name, m.last_name')
                          ->from('fines f')
                          ->join('members m', 'm.id = f.member_id')
                          ->join('loans l', 'l.id = f.loan_id', 'left')
                          ->where_in('f.status', ['pending', 'partial'])
                          ->order_by('f.fine_date', 'DESC')
                          ->limit(50)
                          ->get()
                          ->result();
        $total = $this->db->select_sum('balance_amount')
                          ->where_in('status', ['pending', 'partial'])
                          ->get('fines')
                          ->row()
                          ->balance_amount ?? 0;
        $this->json_response(['data' => $fines, 'total' => $total]);
    }
    
    /**
     * Fee Detail (for modal)
     */
    public function card_fees() {
        $membership_fees = $this->db->select('bt.id, bt.transaction_date, bt.amount, bt.description, bt.reference_number, m.member_code, m.first_name, m.last_name')
                                     ->from('bank_transactions bt')
                                     ->join('members m', 'm.id = bt.paid_by_member_id', 'left')
                                     ->where('bt.transaction_category', 'membership_fee')
                                     ->where('bt.mapping_status', 'mapped')
                                     ->order_by('bt.transaction_date', 'DESC')
                                     ->limit(50)
                                     ->get()
                                     ->result();
        $this->json_response(['data' => $membership_fees]);
    }
}
