<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/Member_Controller.php';

/**
 * Member Loans Controller
 */
class Loans extends Member_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('Loan_model');
    }
    
    /**
     * My Loans List
     */
    public function index() {
        $data['title'] = 'My Loans';
        $data['page_title'] = 'My Loan Accounts';
        
        $data['loans'] = $this->db->select('l.*, lp.product_name, lp.interest_rate')
                                 ->from('loans l')
                                 ->join('loan_products lp', 'lp.id = l.loan_product_id')
                                 ->where('l.member_id', $this->member->id)
                                 ->order_by('l.created_at', 'DESC')
                                 ->get()
                                 ->result();
        
        $this->load_member_view('member/loans/index', $data);
    }
    
    /**
     * Apply for New Loan
     */
    public function apply() {
        $data['title'] = 'Apply for Loan';
        $data['page_title'] = 'New Loan Application';
        
        if ($this->input->method() === 'post') {
            $this->_process_application();
            return;
        }
        
        // Get loan products
        $data['loan_products'] = $this->db->where('is_active', 1)->order_by('product_name', 'ASC')->get('loan_products')->result();
        
        $this->load_member_view('member/loans/apply', $data);
    }

    /**
     * List current member's applications
     */
    public function applications() {
        $data['title'] = 'My Applications';
        $data['page_title'] = 'Loan Applications';

        // Join with loan_products to include product name and ensure consistent fields
        $this->db->select('la.*, lp.product_name')
                 ->from('loan_applications la')
                 ->join('loan_products lp', 'lp.id = la.loan_product_id', 'left')
                 ->where('la.member_id', $this->member->id)
                 ->order_by('la.created_at', 'DESC');

        $data['applications'] = $this->db->get()->result();

        $this->load_member_view('member/loans/applications', $data);
    }
    
    /**
     * View Loan Details
     */
    public function view($loan_id) {
        $loan = $this->db->select('l.*, lp.product_name, lp.interest_rate')
                        ->from('loans l')
                        ->join('loan_products lp', 'lp.id = l.loan_product_id')
                        ->where('l.id', $loan_id)
                        ->where('l.member_id', $this->member->id)
                        ->get()
                        ->row();
        
        if (!$loan) {
            $this->session->set_flashdata('error', 'Loan not found.');
            redirect('member/loans');
        }
        
        $data['loan'] = $loan;
        $data['title'] = 'Loan Details';
        $data['page_title'] = 'Loan: ' . $loan->loan_number;
        
        // Get installments
        $data['installments'] = $this->Loan_model->get_loan_installments($loan_id);
        
        // Get payments
        $data['payments'] = $this->db->where('loan_id', $loan_id)
                                    ->where('is_reversed', 0)
                                    ->order_by('payment_date', 'DESC')
                                    ->get('loan_payments')
                                    ->result();
        
        $this->load_member_view('member/loans/view', $data);
    }
    
    /**
     * Process Loan Application
     */
    private function _process_application() {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('loan_product_id', 'Loan Product', 'required|numeric');
        $this->form_validation->set_rules('amount_requested', 'Amount', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('requested_tenure_months', 'Tenure', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('purpose', 'Purpose', 'required');
        
        if ($this->form_validation->run() === FALSE) {
            // Log validation errors and input for debugging
            log_message('error', 'Member Loans::apply validation failed: ' . validation_errors());
            log_message('debug', 'Member Loans::apply POST data: ' . json_encode($this->input->post()));

            $this->session->set_flashdata('error', validation_errors());
            redirect('member/loans/apply');
            return;
        }

        // Validate tenure against selected product's min/max
        $product_id = (int) $this->input->post('loan_product_id');
        $product = $this->db->where('id', $product_id)->get('loan_products')->row();
        $requested_tenure = (int) $this->input->post('requested_tenure_months');

        if (!$product) {
            $this->session->set_flashdata('error', 'Selected loan product not found.');
            redirect('member/loans/apply');
            return;
        }
        $min = isset($product->min_tenure_months) ? (int) $product->min_tenure_months : 1;
        $max = isset($product->max_tenure_months) ? (int) $product->max_tenure_months : 240;
        if ($requested_tenure < $min || $requested_tenure > $max) {
            $this->session->set_flashdata('error', 'Tenure must be between ' . $min . ' and ' . $max . ' months for the selected product.');
            redirect('member/loans/apply');
            return;
        }
        
        $application_data = [
            'member_id' => $this->member->id,
            'loan_product_id' => $product_id,
            'requested_amount' => $this->input->post('amount_requested'),
            'requested_tenure_months' => $requested_tenure,
            'purpose' => $this->input->post('purpose'),
            'application_date' => date('Y-m-d'),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Create application via model (captures member snapshot)
        $this->load->model('Loan_model');
        $application_id = $this->Loan_model->create_application($application_data);

        if ($application_id) {
            // Save guarantors if provided
            $g_ids = $this->input->post('guarantor_member_id') ?: [];
            $g_amounts = $this->input->post('guarantee_amount') ?: [];
            foreach ($g_ids as $idx => $gmember_id) {
                $gmember_id = (int) $gmember_id;
                $gamount = isset($g_amounts[$idx]) ? (float) $g_amounts[$idx] : 0;
                if ($gmember_id > 0) {
                    $this->Loan_model->add_guarantor($application_id, $gmember_id, $gamount);
                }
            }

            $this->session->set_flashdata('success', 'Loan application submitted successfully. Application Number: ' . $this->Loan_model->get_application($application_id)->application_number);
            redirect('member/loans/applications');
        } else {
            $this->session->set_flashdata('error', 'Failed to submit application.');
            redirect('member/loans/apply');
        }
    }

    /**
     * View an application (member)
     */
    public function application($application_id) {
        $this->load->model('Loan_model');
        $app = $this->Loan_model->get_application($application_id);
        if (!$app || $app->member_id != $this->member->id) {
            $this->session->set_flashdata('error', 'Application not found.');
            redirect('member/loans');
            return;
        }

        $data['application'] = $app;
        $data['guarantors'] = $this->Loan_model->get_application_guarantors($application_id);
        $data['title'] = 'Application ' . $app->application_number;
        $data['page_title'] = 'Loan Application';
        $this->load_member_view('member/loans/application', $data);
    }

    /**
     * Edit application that was requested for modification or is pending
     */
    public function edit_application($application_id) {
        $this->load->model('Loan_model');
        $app = $this->Loan_model->get_application($application_id);
        if (!$app || $app->member_id != $this->member->id) {
            $this->session->set_flashdata('error', 'Application not found.');
            redirect('member/loans');
            return;
        }

        if (!in_array($app->status, ['pending','needs_revision','rejected'])) {
            $this->session->set_flashdata('error', 'Application cannot be edited in its current status.');
            redirect('member/loans');
            return;
        }

        $data['loan_products'] = $this->db->where('is_active',1)->order_by('product_name','ASC')->get('loan_products')->result();
        $data['application'] = $app;
        $data['guarantors'] = $this->Loan_model->get_application_guarantors($application_id);
        $data['title'] = 'Edit Application';
        $data['page_title'] = 'Edit Loan Application';

        $this->load_member_view('member/loans/edit_application', $data);
    }

    /**
     * Update application (resubmit)
     */
    public function update_application($application_id) {
        $this->load->model('Loan_model');
        $app = $this->Loan_model->get_application($application_id);
        if (!$app || $app->member_id != $this->member->id) {
            $this->session->set_flashdata('error', 'Application not found.');
            redirect('member/loans');
            return;
        }

        if ($this->input->method() !== 'post') {
            redirect('member/loans/edit_application/' . $application_id);
            return;
        }

        $this->load->library('form_validation');
        $this->form_validation->set_rules('loan_product_id', 'Loan Product', 'required|numeric');
        $this->form_validation->set_rules('amount_requested', 'Amount', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('requested_tenure_months', 'Tenure', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('purpose', 'Purpose', 'required');

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('member/loans/edit_application/' . $application_id);
            return;
        }

        $product_id = (int) $this->input->post('loan_product_id');
        $product = $this->db->where('id', $product_id)->get('loan_products')->row();
        $requested_tenure = (int) $this->input->post('requested_tenure_months');
        $min = isset($product->min_tenure_months) ? (int) $product->min_tenure_months : 1;
        $max = isset($product->max_tenure_months) ? (int) $product->max_tenure_months : 240;
        if ($requested_tenure < $min || $requested_tenure > $max) {
            $this->session->set_flashdata('error', 'Tenure must be between ' . $min . ' and ' . $max . ' months for the selected product.');
            redirect('member/loans/edit_application/' . $application_id);
            return;
        }

        $update = [
            'loan_product_id' => $product_id,
            'requested_amount' => $this->input->post('amount_requested'),
            'requested_tenure_months' => $requested_tenure,
            'purpose' => $this->input->post('purpose'),
            'status' => 'pending',
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // update application
        $this->db->where('id', $application_id)->update('loan_applications', $update);

        // replace guarantors
        $this->db->where('loan_application_id', $application_id)->delete('loan_guarantors');
        $g_ids = $this->input->post('guarantor_member_id') ?: [];
        $g_amounts = $this->input->post('guarantee_amount') ?: [];
        foreach ($g_ids as $idx => $gmember_id) {
            $gmember_id = (int) $gmember_id;
            $gamount = isset($g_amounts[$idx]) ? (float) $g_amounts[$idx] : 0;
            if ($gmember_id > 0) {
                $this->Loan_model->add_guarantor($application_id, $gmember_id, $gamount);
            }
        }

        $this->session->set_flashdata('success', 'Application updated and resubmitted.');
        redirect('member/loans/application/' . $application_id);
    }

    /**
     * Member approve admin-approved application
     */
    public function approve_application($application_id) {
        $this->load->model('Loan_model');
        $app = $this->Loan_model->get_application($application_id);

        if (!$app || $app->member_id != $this->member->id) {
            $this->session->set_flashdata('error', 'Application not found.');
            redirect('member/loans/applications');
            return;
        }

        if ($app->status !== 'member_review') {
            $this->session->set_flashdata('error', 'Application is not available for review.');
            redirect('member/loans/application/' . $application_id);
            return;
        }

        $result = $this->Loan_model->member_approve($application_id);

        if ($result) {
            $this->log_audit('member_approved', 'loan_applications', 'loan_applications', $application_id, null, ['status' => 'member_approved']);
            $this->session->set_flashdata('success', 'Application approved successfully. Awaiting disbursement.');
        } else {
            $this->session->set_flashdata('error', 'Failed to approve application.');
        }

        redirect('member/loans/application/' . $application_id);
    }

    /**
     * Member reject admin-approved application
     */
    public function reject_application($application_id) {
        $this->load->model('Loan_model');
        $app = $this->Loan_model->get_application($application_id);

        if (!$app || $app->member_id != $this->member->id) {
            $this->session->set_flashdata('error', 'Application not found.');
            redirect('member/loans/applications');
            return;
        }

        if ($app->status !== 'member_review') {
            $this->session->set_flashdata('error', 'Application is not available for review.');
            redirect('member/loans/application/' . $application_id);
            return;
        }

        $reason = $this->input->post('reason');
        if (empty($reason)) {
            $this->session->set_flashdata('error', 'Please provide a reason for rejection.');
            redirect('member/loans/application/' . $application_id);
            return;
        }

        $result = $this->Loan_model->reject_application($application_id, $reason, $this->member->id);

        if ($result) {
            $this->log_audit('rejected', 'loan_applications', 'loan_applications', $application_id, null, ['reason' => $reason]);
            $this->session->set_flashdata('success', 'Application rejected successfully.');
        } else {
            $this->session->set_flashdata('error', 'Failed to reject application.');
        }

        redirect('member/loans/application/' . $application_id);
    }
}
