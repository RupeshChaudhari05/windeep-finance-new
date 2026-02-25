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
        
        $this->db->select('li.*, l.loan_number, l.interest_rate, l.tenure_months, lp.product_name');
        $this->db->from('loan_installments li');
        $this->db->join('loans l', 'l.id = li.loan_id');
        $this->db->join('loan_products lp', 'lp.id = l.loan_product_id');
        $this->db->where('l.member_id', $this->member->id);
        
        if ($loan_id) {
            $this->db->where('l.id', $loan_id);
        }
        
        $this->db->order_by('li.due_date', 'ASC');
        
        $installments = $this->db->get()->result();
        $data['installments'] = $installments;
        
        // Calculate summary totals
        $data['summary'] = [
            'total_emi' => 0,
            'total_principal' => 0,
            'total_interest' => 0,
            'total_paid' => 0,
            'total_fine' => 0,
            'total_outstanding' => 0,
            'paid_count' => 0,
            'pending_count' => 0,
            'overdue_count' => 0,
        ];
        
        foreach ($installments as $inst) {
            $data['summary']['total_emi'] += (float) $inst->emi_amount;
            $data['summary']['total_principal'] += (float) $inst->principal_amount;
            $data['summary']['total_interest'] += (float) $inst->interest_amount;
            $data['summary']['total_paid'] += (float) ($inst->total_paid ?? 0);
            $data['summary']['total_fine'] += (float) ($inst->fine_amount ?? 0);
            
            $balance = (float) $inst->emi_amount - (float) ($inst->total_paid ?? 0);
            if ($balance > 0 && in_array($inst->status, ['pending', 'partial', 'overdue'])) {
                $data['summary']['total_outstanding'] += $balance;
            }
            
            if ($inst->status === 'paid') $data['summary']['paid_count']++;
            elseif ($inst->status === 'overdue') $data['summary']['overdue_count']++;
            elseif (in_array($inst->status, ['pending', 'partial'])) $data['summary']['pending_count']++;
        }
        
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
