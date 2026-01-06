<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/Member_Controller.php';

/**
 * Member Installments Controller
 */
class Installments extends Member_Controller {
    
    /**
     * My Installments
     */
    public function index() {
        $data['title'] = 'My Installments';
        $data['page_title'] = 'EMI Schedule';
        
        $loan_id = $this->input->get('loan_id');
        
        $this->db->select('li.*, l.loan_number, lp.product_name');
        $this->db->from('loan_installments li');
        $this->db->join('loans l', 'l.id = li.loan_id');
        $this->db->join('loan_products lp', 'lp.id = l.loan_product_id');
        $this->db->where('l.member_id', $this->member->id);
        
        if ($loan_id) {
            $this->db->where('l.id', $loan_id);
        }
        
        $this->db->order_by('li.due_date', 'ASC');
        
        $data['installments'] = $this->db->get()->result();
        
        // Get loans for filter
        $data['member_loans'] = $this->db->select('id, loan_number')
                                        ->from('loans')
                                        ->where('member_id', $this->member->id)
                                        ->where_in('status', ['active', 'overdue'])
                                        ->get()
                                        ->result();
        
        $this->load_member_view('member/installments/index', $data);
    }
}
