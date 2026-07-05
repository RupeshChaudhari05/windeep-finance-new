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

    /**
     * Download / Print EMI Payment Receipt
     */
    public function receipt($installment_id) {
        // Fetch installment and verify it belongs to this member
        $installment = $this->db
            ->select('li.*, l.loan_number, l.tenure_months, l.interest_rate, lp.product_name, l.id as loan_id')
            ->from('loan_installments li')
            ->join('loans l', 'l.id = li.loan_id')
            ->join('loan_products lp', 'lp.id = l.loan_product_id')
            ->where('li.id', $installment_id)
            ->where('l.member_id', $this->member->id)
            ->get()
            ->row();

        if (!$installment) {
            show_404();
            return;
        }

        // Only paid installments can have a receipt
        if (!in_array($installment->status, ['paid', 'partial', 'interest_only', 'waived'])) {
            $this->session->set_flashdata('error', 'Receipt is only available for paid installments.');
            redirect('member/installments');
            return;
        }

        // Fetch most recent payment record for this installment
        $payment = $this->db
            ->where('installment_id', $installment_id)
            ->where('is_reversed', 0)
            ->order_by('payment_date', 'DESC')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('loan_payments')
            ->row();

        // If no payment linked by installment_id, try loan_id + date match
        if (!$payment) {
            $payment = $this->db
                ->where('loan_id', $installment->loan_id)
                ->where('payment_date', $installment->paid_date)
                ->where('is_reversed', 0)
                ->where_in('payment_type', ['emi', 'advance_payment', 'interest_only'])
                ->order_by('id', 'DESC')
                ->limit(1)
                ->get('loan_payments')
                ->row();
        }

        $data['installment'] = $installment;
        $data['loan']        = (object)[
            'loan_number'  => $installment->loan_number,
            'product_name' => $installment->product_name,
            'tenure_months'=> $installment->tenure_months,
            'interest_rate'=> $installment->interest_rate,
        ];
        $data['payment']     = $payment;
        $data['member']      = $this->member;

        // Output clean HTML receipt (no header/footer wrapper)
        $this->load->view('member/installments/receipt', $data);
    }
}
