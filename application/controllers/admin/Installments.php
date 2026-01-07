<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Installments Controller - EMI & Payment Management
 */
class Installments extends Admin_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model(['Loan_model', 'Member_model']);
    }
    
    /**
     * EMI Schedule - All Installments
     */
    public function index() {
        $data['title'] = 'EMI Schedule';
        $data['page_title'] = 'Installment Management';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Installments', 'url' => '']
        ];
        
        // Filters
        $status = $this->input->get('status') ?: 'pending';
        $month = $this->input->get('month') ?: date('Y-m');
        $loan_id = $this->input->get('loan_id');
        
        // Build query
        $this->db->select('li.*, l.loan_number, l.member_id, m.member_code, m.first_name, m.last_name, m.phone, lp.product_name');
        $this->db->from('loan_installments li');
        $this->db->join('loans l', 'l.id = li.loan_id');
        $this->db->join('members m', 'm.id = l.member_id');
        $this->db->join('loan_products lp', 'lp.id = l.loan_product_id');
        
        if ($status !== 'all') {
            if ($status == 'overdue') {
                $this->db->where('li.status', 'pending');
                $this->db->where('li.due_date <', date('Y-m-d'));
            } else {
                $this->db->where('li.status', $status);
            }
        }
        
        if ($month) {
            list($year, $month_num) = explode('-', $month);
            $this->db->where('YEAR(li.due_date)', $year);
            $this->db->where('MONTH(li.due_date)', $month_num);
        }
        
        if ($loan_id) {
            $this->db->where('li.loan_id', $loan_id);
        }
        
        $this->db->where_in('l.status', ['active', 'overdue', 'npa']);
        $this->db->order_by('li.due_date', 'ASC');
        $this->db->order_by('m.member_code', 'ASC');
        
        $data['installments'] = $this->db->get()->result();
        $data['status'] = $status;
        $data['month'] = $month;
        $data['loan_id'] = $loan_id;
        
        // Calculate totals
        $data['total_emi'] = array_sum(array_column($data['installments'], 'emi_amount'));
        $data['total_paid'] = array_sum(array_column($data['installments'], 'total_paid'));
        $data['total_pending'] = $data['total_emi'] - $data['total_paid'];
        
        // Get active loans for filter
        $data['active_loans'] = $this->db->select('l.id, l.loan_number, m.member_code, m.first_name, m.last_name')
                                         ->from('loans l')
                                         ->join('members m', 'm.id = l.member_id')
                                         ->where_in('l.status', ['active', 'overdue', 'npa'])
                                         ->order_by('l.loan_number', 'DESC')
                                         ->get()
                                         ->result();
        
        $this->load_view('admin/installments/index', $data);
    }
    
    /**
     * Due Today
     */
    public function due_today() {
        $data['title'] = 'EMI Due Today';
        $data['page_title'] = 'Installments Due Today';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Installments', 'url' => 'admin/installments'],
            ['title' => 'Due Today', 'url' => '']
        ];
        
        $today = date('Y-m-d');
        
        $this->db->select('li.*, l.loan_number, l.member_id, l.loan_product_id, m.member_code, m.first_name, m.last_name, m.phone, lp.product_name');
        $this->db->from('loan_installments li');
        $this->db->join('loans l', 'l.id = li.loan_id');
        $this->db->join('members m', 'm.id = l.member_id');
        $this->db->join('loan_products lp', 'lp.id = l.loan_product_id');
        $this->db->where('li.due_date', $today);
        $this->db->where('li.status', 'pending');
        $this->db->where_in('l.status', ['active', 'overdue']);
        $this->db->order_by('m.member_code', 'ASC');
        
        $data['installments'] = $this->db->get()->result();
        
        // Calculate totals
        $data['total_count'] = count($data['installments']);
        $data['total_amount'] = array_sum(array_column($data['installments'], 'emi_amount'));
        $data['total_collected'] = array_sum(array_column($data['installments'], 'total_paid'));
        $data['total_pending'] = $data['total_amount'] - $data['total_collected'];
        
        $this->load_view('admin/installments/due_today', $data);
    }
    
    /**
     * Upcoming Installments
     */
    public function upcoming() {
        $data['title'] = 'Upcoming EMIs';
        $data['page_title'] = 'Upcoming Installments';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Installments', 'url' => 'admin/installments'],
            ['title' => 'Upcoming', 'url' => '']
        ];
        
        $days = $this->input->get('days') ?: 7;
        $end_date = date('Y-m-d', strtotime("+$days days"));
        
        $this->db->select('li.*, l.loan_number, l.member_id, m.member_code, m.first_name, m.last_name, m.phone, lp.product_name');
        $this->db->from('loan_installments li');
        $this->db->join('loans l', 'l.id = li.loan_id');
        $this->db->join('members m', 'm.id = l.member_id');
        $this->db->join('loan_products lp', 'lp.id = l.loan_product_id');
        $this->db->where('li.due_date >', date('Y-m-d'));
        $this->db->where('li.due_date <=', $end_date);
        $this->db->where_in('li.status', ['pending', 'upcoming']);
        $this->db->where_in('l.status', ['active', 'overdue']);
        $this->db->order_by('li.due_date', 'ASC');
        
        $data['installments'] = $this->db->get()->result();
        $data['days'] = $days;
        $data['end_date'] = $end_date;
        
        // Calculate totals
        $data['total_count'] = count($data['installments']);
        $data['total_amount'] = array_sum(array_column($data['installments'], 'emi_amount'));
        
        $this->load_view('admin/installments/upcoming', $data);
    }
    
    /**
     * Overdue Installments
     */
    public function overdue() {
        $data['title'] = 'Overdue EMIs';
        $data['page_title'] = 'Overdue Installments';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Installments', 'url' => 'admin/installments'],
            ['title' => 'Overdue', 'url' => '']
        ];
        
        $this->db->select('li.*, l.loan_number, l.member_id, m.member_code, m.first_name, m.last_name, m.phone, lp.product_name, DATEDIFF(CURDATE(), li.due_date) as days_overdue');
        $this->db->from('loan_installments li');
        $this->db->join('loans l', 'l.id = li.loan_id');
        $this->db->join('members m', 'm.id = l.member_id');
        $this->db->join('loan_products lp', 'lp.id = l.loan_product_id');
        $this->db->where('li.status', 'pending');
        $this->db->where('li.due_date <', date('Y-m-d'));
        $this->db->where_in('l.status', ['active', 'overdue', 'npa']);
        $this->db->order_by('li.due_date', 'ASC');
        
        $data['installments'] = $this->db->get()->result();
        
        // Calculate totals
        $data['total_count'] = count($data['installments']);
        $data['total_amount'] = array_sum(array_column($data['installments'], 'emi_amount'));
        $data['total_collected'] = array_sum(array_column($data['installments'], 'total_paid'));
        $data['total_overdue'] = $data['total_amount'] - $data['total_collected'];
        
        // Group by overdue period
        $data['critical'] = array_filter($data['installments'], function($inst) {
            return ($inst->days_overdue ?? 0) > 90;
        });
        $data['serious'] = array_filter($data['installments'], function($inst) {
            $days = $inst->days_overdue ?? 0;
            return $days > 30 && $days <= 90;
        });
        $data['recent'] = array_filter($data['installments'], function($inst) {
            return ($inst->days_overdue ?? 0) <= 30;
        });
        
        $this->load_view('admin/installments/overdue', $data);
    }
    
    /**
     * View Single Installment
     */
    public function view($id) {
        $installment = $this->db->select('li.*, l.*, m.member_code, m.first_name, m.last_name, m.phone, lp.product_name')
                                ->from('loan_installments li')
                                ->join('loans l', 'l.id = li.loan_id')
                                ->join('members m', 'm.id = l.member_id')
                                ->join('loan_products lp', 'lp.id = l.loan_product_id')
                                ->where('li.id', $id)
                                ->get()
                                ->row();
        
        if (!$installment) {
            $this->session->set_flashdata('error', 'Installment not found.');
            redirect('admin/installments');
        }
        
        $data['installment'] = $installment;
        $data['title'] = 'Installment Details';
        $data['page_title'] = 'EMI #' . $installment->installment_number . ' - ' . $installment->loan_number;
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Installments', 'url' => 'admin/installments'],
            ['title' => 'View', 'url' => '']
        ];
        
        // Get payment history for this installment
        $data['payments'] = $this->db->where('installment_id', $id)
                                    ->where('is_reversed', 0)
                                    ->order_by('payment_date', 'DESC')
                                    ->get('loan_payments')
                                    ->result();
        
        // Get fines
        $data['fines'] = $this->db->where('related_type', 'loan_installment')
                                  ->where('related_id', $id)
                                  ->get('fines')
                                  ->result();
        
        $this->load_view('admin/installments/view', $data);
    }
    
    /**
     * Bulk Collection Sheet
     */
    public function collection_sheet() {
        $data['title'] = 'Collection Sheet';
        $data['page_title'] = 'EMI Collection Sheet';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Installments', 'url' => 'admin/installments'],
            ['title' => 'Collection Sheet', 'url' => '']
        ];
        
        $date = $this->input->get('date') ?: date('Y-m-d');
        
        $this->db->select('li.*, l.loan_number, l.member_id, m.member_code, m.first_name, m.last_name, m.phone');
        $this->db->from('loan_installments li');
        $this->db->join('loans l', 'l.id = li.loan_id');
        $this->db->join('members m', 'm.id = l.member_id');
        $this->db->where('li.due_date', $date);
        $this->db->where_in('li.status', ['pending', 'partial']);
        $this->db->where_in('l.status', ['active', 'overdue']);
        $this->db->order_by('m.member_code', 'ASC');
        
        $data['installments'] = $this->db->get()->result();
        $data['date'] = $date;
        $data['total_expected'] = array_sum(array_column($data['installments'], 'emi_amount'));
        
        $this->load_view('admin/installments/collection_sheet', $data);
    }
    
    /**
     * Send Reminder (AJAX)
     */
    public function send_reminder() {
        $installment_id = $this->input->post('installment_id');
        
        if (!$installment_id) {
            $this->json_response(['success' => false, 'message' => 'Invalid installment']);
            return;
        }
        
        $installment = $this->db->select('li.*, l.loan_number, m.phone, m.first_name')
                                ->from('loan_installments li')
                                ->join('loans l', 'l.id = li.loan_id')
                                ->join('members m', 'm.id = l.member_id')
                                ->where('li.id', $installment_id)
                                ->get()
                                ->row();
        
        if (!$installment) {
            $this->json_response(['success' => false, 'message' => 'Installment not found']);
            return;
        }
        
        // TODO: Implement SMS/WhatsApp reminder
        $this->log_audit('reminder_sent', 'loan_installments', 'loan_installments', $installment_id, null, [
            'loan_number' => $installment->loan_number,
            'phone' => $installment->phone
        ]);
        
        $this->json_response(['success' => true, 'message' => 'Reminder sent successfully']);
    }
    
    /**
     * Mark as Paid (Quick)
     */
    public function quick_payment() {
        $installment_id = $this->input->post('installment_id');
        $amount = $this->input->post('amount');
        $payment_mode = $this->input->post('payment_mode') ?: 'cash';
        
        if (!$installment_id || !$amount) {
            $this->json_response(['success' => false, 'message' => 'Invalid data']);
            return;
        }
        
        try {
            $payment_data = [
                'loan_id' => $this->input->post('loan_id'),
                'installment_id' => $installment_id,
                'payment_type' => 'emi',
                'total_amount' => $amount,
                'payment_mode' => $payment_mode,
                'payment_date' => date('Y-m-d'),
                'created_by' => $this->session->userdata('admin_id')
            ];
            
            $payment_id = $this->Loan_model->record_payment($payment_data);
            
            $this->json_response(['success' => true, 'message' => 'Payment recorded successfully', 'payment_id' => $payment_id]);
        } catch (Exception $e) {
            $this->json_response(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
