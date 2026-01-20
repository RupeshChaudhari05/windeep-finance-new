<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/Member_Controller.php';

/**
 * Member Dashboard Controller
 */
class Dashboard extends Member_Controller {
    
    public function index() {
        $data['title'] = 'Dashboard';
        $data['page_title'] = 'Member Dashboard';
        
        // Get member statistics
        $member_id = $this->member->id;
        
        // Loans summary
        $data['loans_summary'] = $this->db->select('COUNT(*) as total_loans, SUM(principal_amount) as total_borrowed, 
                                                    SUM(outstanding_principal) as total_outstanding')
                                         ->from('loans')
                                         ->where('member_id', $member_id)
                                         ->where_in('status', ['active', 'overdue', 'npa'])
                                         ->get()
                                         ->row();
        
        // Active loans
        $data['active_loans'] = $this->db->select('l.*, lp.product_name')
                                        ->from('loans l')
                                        ->join('loan_products lp', 'lp.id = l.loan_product_id')
                                        ->where('l.member_id', $member_id)
                                        ->where_in('l.status', ['active', 'overdue'])
                                        ->order_by('l.disbursement_date', 'DESC')
                                        ->get()
                                        ->result();
        
        // Pending installments (next 5)
        $data['pending_installments'] = $this->db->select('li.*, l.loan_number')
                                                ->from('loan_installments li')
                                                ->join('loans l', 'l.id = li.loan_id')
                                                ->where('l.member_id', $member_id)
                                                ->where('li.status', 'pending')
                                                ->order_by('li.due_date', 'ASC')
                                                ->limit(5)
                                                ->get()
                                                ->result();
        
        // Savings accounts
        $data['savings_accounts'] = $this->db->select('sa.*, ss.scheme_name')
                                            ->from('savings_accounts sa')
                                            ->join('savings_schemes ss', 'ss.id = sa.scheme_id')
                                            ->where('sa.member_id', $member_id)
                                            ->where('sa.status', 'active')
                                            ->get()
                                            ->result();
        
        // Pending fines
        $data['pending_fines'] = $this->db->select('SUM(fine_amount - COALESCE(paid_amount, 0) - COALESCE(waived_amount, 0)) as total_fines')
                                         ->from('fines')
                                         ->where('member_id', $member_id)
                                         ->where_in('status', ['pending', 'partial'])
                                         ->get()
                                         ->row();
        
        // Recent transactions (payments made)
        $data['recent_transactions'] = $this->db->select('lp.payment_date, lp.total_amount, lp.payment_code, l.loan_number')
                                                ->from('loan_payments lp')
                                                ->join('loans l', 'l.id = lp.loan_id')
                                                ->where('l.member_id', $member_id)
                                                ->where('lp.is_reversed', 0)
                                                ->order_by('lp.payment_date', 'DESC')
                                                ->limit(5)
                                                ->get()
                                                ->result();
        
        $this->load_member_view('member/dashboard/index', $data);
    }

    /**
     * Member Notifications (AJAX)
     */
    public function notifications() {
        $member_id = $this->member->id;
        $this->load->model('Notification_model');
        $notifications = $this->Notification_model->get_for('member', $member_id, 50);
        $this->json_response($notifications);
    }

    /**
     * Mark notification read
     */
    public function mark_notification_read($id) {
        $this->db->where('id', $id)
                 ->update('notifications', ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]);
        $this->json_response(['success' => true]);
    }
}
