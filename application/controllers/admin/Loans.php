<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Loans Controller - Loan Management
 */
class Loans extends Admin_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model(['Loan_model', 'Member_model']);
    }
    
    /**
     * List Active Loans
     */
    public function index() {
        $data['title'] = 'Loans';
        $data['page_title'] = 'Loan Management';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Loans', 'url' => '']
        ];
        
        $data['stats'] = $this->Loan_model->get_dashboard_stats();
        // Provide summary alias expected by view
        $data['summary'] = $data['stats'];
        
        // Get loans with filters
        $status = $this->input->get('status') ?: 'active';
        
        $this->db->select('l.*, lp.product_name, m.member_code, m.first_name, m.last_name, m.phone');
        $this->db->from('loans l');
        $this->db->join('loan_products lp', 'lp.id = l.loan_product_id');
        $this->db->join('members m', 'm.id = l.member_id');
        
        if ($status !== 'all') {
            $this->db->where('l.status', $status);
        }
        
        $this->db->order_by('l.disbursement_date', 'DESC');
        
        $data['loans'] = $this->db->get()->result();
        $data['status'] = $status;
        $data['products'] = $this->Loan_model->get_products();

        // Normalize loans: compute overdue_count and member display name
        foreach ($data['loans'] as &$loan) {
            $loan->overdue_count = (int) $this->db->where('loan_id', $loan->id)
                                                  ->where('status', 'pending')
                                                  ->where('due_date <', date('Y-m-d'))
                                                  ->count_all_results('loan_installments');
            if (!isset($loan->member_name)) {
                $loan->member_name = trim(($loan->first_name ?? '') . ' ' . ($loan->last_name ?? '')) ?: ($loan->member_code ?? '');
            }
        }
        
        $this->load_view('admin/loans/index', $data);
    }
    
    /**
     * View Loan
     */
    public function view($id) {
        $loan = $this->Loan_model->get_loan_details($id);
        
        if (!$loan) {
            $this->session->set_flashdata('error', 'Loan not found.');
            redirect('admin/loans');
        }
        
        $data['title'] = 'View Loan';
        $data['page_title'] = 'Loan Details';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Loans', 'url' => 'admin/loans'],
            ['title' => $loan->loan_number, 'url' => '']
        ];
        
        $data['loan'] = $loan;
        // Compatibility mappings
        // total_paid used in view; map from schema totals if missing
        $data['loan']->total_paid = $data['loan']->total_paid ?? ($data['loan']->total_amount_paid ?? ((($data['loan']->total_principal_paid ?? 0) + ($data['loan']->total_interest_paid ?? 0)))) ;
        // Ensure numeric defaults to avoid warnings
        $data['loan']->total_paid = $data['loan']->total_paid ?? 0;

        // Compute day of month for EMI display (compatibility with view expecting emi_date)
        if (!isset($data['loan']->emi_date) || empty($data['loan']->emi_date)) {
            $day = $data['loan']->first_emi_date ? (int) date('j', safe_timestamp($data['loan']->first_emi_date)) : 1;
            $data['loan']->emi_date = $day;
        } else {
            $day = (int) $data['loan']->emi_date;
        }
        // Load formatting helper and provide an ordinal formatted day for nicer UI
        $this->load->helper('format');
        $data['loan']->emi_date_formatted = ordinal_suffix($day);

        // Ensure member and product objects for view
        $data['member'] = $this->Member_model->get_member_details($loan->member_id);
        $data['product'] = $this->db->where('id', $loan->loan_product_id)->get('loan_products')->row();

        // Get guarantor details
        $data['guarantors'] = $this->db->select('lg.*, m.member_code, m.first_name, m.last_name, m.phone')
                                       ->from('loan_guarantors lg')
                                       ->join('members m', 'm.id = lg.guarantor_member_id')
                                       ->where('lg.loan_id', $id)
                                       ->get()
                                       ->result();

        // Provide installments, payments and fines for the view (compatibility)
        $data['installments'] = $loan->installments ?? $this->Loan_model->get_loan_installments($id);
        // Normalize installment fields for view compatibility
        foreach ($data['installments'] as &$inst) {
            // Map schema names to view-friendly properties
            if (!isset($inst->principal_component) && isset($inst->principal_amount)) {
                $inst->principal_component = $inst->principal_amount;
            }
            if (!isset($inst->interest_component) && isset($inst->interest_amount)) {
                $inst->interest_component = $inst->interest_amount;
            }
            if (!isset($inst->outstanding_after) && isset($inst->outstanding_principal_after)) {
                $inst->outstanding_after = $inst->outstanding_principal_after;
            }
            // Ensure numeric defaults to avoid warnings
            $inst->principal_component = $inst->principal_component ?? 0;
            $inst->interest_component = $inst->interest_component ?? 0;
            $inst->outstanding_after = $inst->outstanding_after ?? 0;
            $inst->emi_amount = $inst->emi_amount ?? ($inst->principal_component + $inst->interest_component);
        }
        unset($inst);

        $data['payments'] = $loan->payments ?? $this->Loan_model->get_loan_payments($id);
        // Fetch fines related to this loan via its installments (fines table uses related_type/related_id)
        $installments = $this->Loan_model->get_loan_installments($id);
        $installment_ids = array_column($installments, 'id');
        if (!empty($installment_ids)) {
            $data['fines'] = $this->db->where('related_type', 'loan_installment')
                                       ->where_in('related_id', $installment_ids)
                                       ->get('fines')
                                       ->result();
        } else {
            $data['fines'] = [];
        }

        // Overdue stats
        $data['overdue_count'] = (int) $this->db->where('loan_id', $id)
                                               ->where('status', 'pending')
                                               ->where('due_date <', date('Y-m-d'))
                                               ->count_all_results('loan_installments');
        $row = $this->db->select('SUM(emi_amount - total_paid) as overdue')->where('loan_id', $id)
                        ->where('status', 'pending')->where('due_date <', date('Y-m-d'))->get('loan_installments')->row();
        $data['overdue_amount'] = $row->overdue ?? 0;

        $this->load_view('admin/loans/view', $data);
    }
    
    /**
     * Loan Applications
     */
    public function applications() {
        $data['title'] = 'Loan Applications';
        $data['page_title'] = 'Pending Applications';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Loans', 'url' => 'admin/loans'],
            ['title' => 'Applications', 'url' => '']
        ];
        
        $status = $this->input->get('status') ?: 'pending';
        
        $this->db->select('la.*, lp.product_name, m.member_code, m.first_name, m.last_name, m.phone');
        $this->db->from('loan_applications la');
        $this->db->join('loan_products lp', 'lp.id = la.loan_product_id', 'left');
        $this->db->join('members m', 'm.id = la.member_id');
        
        if ($status !== 'all') {
            $this->db->where('la.status', $status);
        }
        
        $this->db->order_by('la.application_date', 'DESC');
        
        $data['applications'] = $this->db->get()->result();
        $data['status'] = $status;

        // Normalize applications: compute member display name
        foreach ($data['applications'] as &$app) {
            if (!isset($app->member_name)) {
                $app->member_name = trim(($app->first_name ?? '') . ' ' . ($app->last_name ?? '')) ?: ($app->member_code ?? '');
            }
        }
        
        $this->load_view('admin/loans/applications', $data);
    }
    
    /**
     * View Application
     */
    public function view_application($id) {
        $application = $this->Loan_model->get_application($id);
        
        if (!$application) {
            $this->session->set_flashdata('error', 'Application not found.');
            redirect('admin/loans/applications');
        }
        
        $data['title'] = 'Loan Application';
        $data['page_title'] = 'Application Details';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Loans', 'url' => 'admin/loans'],
            ['title' => 'Applications', 'url' => 'admin/loans/applications'],
            ['title' => $application->application_number, 'url' => '']
        ];
        
        $data['application'] = $application;
        $data['guarantors'] = $this->Loan_model->get_application_guarantors($id);
        // Guarantor counts and min required
        $data['guarantor_counts'] = [
            'total' => (int) $this->db->where('loan_application_id', $id)->count_all_results('loan_guarantors'),
            'accepted' => (int) $this->db->where('loan_application_id', $id)->where('consent_status', 'accepted')->count_all_results('loan_guarantors'),
            'pending' => (int) $this->db->where('loan_application_id', $id)->where('consent_status', 'pending')->count_all_results('loan_guarantors'),
            'rejected' => (int) $this->db->where('loan_application_id', $id)->where('consent_status', 'rejected')->count_all_results('loan_guarantors')
        ];
        $data['min_guarantors_required'] = (int) $this->get_setting('min_guarantors', 1);

        // Get member details
        $data['member'] = $this->Member_model->get_member_details($application->member_id);
        
        // Flag for page-specific scripts
        $data['load_reject_script'] = true;
        
        $this->load_view('admin/loans/view_application', $data);
    }
    
    /**
     * New Loan Application Form
     */
    public function apply() {
        $data['title'] = 'New Loan Application';
        $data['page_title'] = 'New Loan Application';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Loans', 'url' => 'admin/loans'],
            ['title' => 'New Application', 'url' => '']
        ];
        
        $data['products'] = $this->Loan_model->get_products();        $data['members'] = $this->Member_model->get_active_members_dropdown();        
        // Pre-fill member if passed
        if ($member_id = $this->input->get('member_id')) {
            $data['selected_member'] = $this->Member_model->get_member_details($member_id);
        }
        
        $this->load_view('admin/loans/apply', $data);
    }
    
    /**
     * Submit Application
     */
    public function submit_application() {
        if ($this->input->method() !== 'post') {
            redirect('admin/loans/apply');
        }
        
        $this->load->library('form_validation');
        $this->form_validation->set_rules('member_id', 'Member', 'required|numeric');
        $this->form_validation->set_rules('loan_product_id', 'Loan Product', 'required|numeric');
        $this->form_validation->set_rules('requested_amount', 'Loan Amount', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('requested_tenure_months', 'Tenure', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('purpose', 'Purpose', 'required');
        
        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('admin/loans/apply');
        }
        
        $application_data = [
            'member_id' => $this->input->post('member_id'),
            'loan_product_id' => $this->input->post('loan_product_id'),
            'requested_amount' => $this->input->post('requested_amount'),
            'requested_tenure_months' => $this->input->post('requested_tenure_months'),
            'purpose' => $this->input->post('purpose'),
            'purpose_details' => $this->input->post('purpose_details'),
            'status_remarks' => $this->input->post('remarks'),
            'created_by' => $this->session->userdata('admin_id')
        ];
        
        $application_id = $this->Loan_model->create_application($application_data);
        
        if ($application_id) {
            // Add guarantors
            $guarantor_ids = $this->input->post('guarantor_ids');
            $guarantee_amounts = $this->input->post('guarantee_amounts');
            
            if ($guarantor_ids && is_array($guarantor_ids)) {
                $this->load->model('Notification_model');
                $this->load->model('Member_model');
                foreach ($guarantor_ids as $key => $guarantor_id) {
                    if ($guarantor_id) {
                        $res = $this->Loan_model->add_guarantor(
                            $application_id, 
                            $guarantor_id, 
                            $guarantee_amounts[$key] ?? 0
                        );

                        if ($res && isset($res['id'])) {
                            $app = $this->Loan_model->get_application($application_id);
                            $url = site_url('public/guarantor_consent/' . $res['id'] . '/' . $res['token']);
                            $title = 'Guarantor Request: ' . $app->application_number;
                            $message = "You have been requested to act as a guarantor for loan application " . $app->application_number . ". Please review and accept or reject: " . $url;

                            $this->Notification_model->create('member', $guarantor_id, 'guarantor_request', $title, $message, ['application_id' => $application_id, 'guarantor_id' => $res['id'], 'url' => $url]);

                            $gmember = $this->Member_model->get_by_id($guarantor_id);
                            if ($gmember && !empty($gmember->email)) {
                                $subject = 'You have been requested as guarantor';
                                $html = '<p>Dear ' . htmlspecialchars($gmember->first_name . ' ' . $gmember->last_name) . ',</p>';
                                $html .= '<p>You have been requested to act as a guarantor for loan application <strong>' . $app->application_number . '</strong>.</p>';
                                $html .= '<p>Please <a href="' . $url . '">click here to review and respond</a>.</p>';
                                send_email($gmember->email, $subject, $html);
                            }
                        }
                    }
                }
            }
            
            $this->log_audit('create', 'loan_applications', 'loan_applications', $application_id, null, $application_data);
            $this->session->set_flashdata('success', 'Loan application submitted successfully.');
            redirect('admin/loans/view_application/' . $application_id);
        } else {
            $this->session->set_flashdata('error', 'The loan application could not be created. Please verify all fields are correct and try again.');
            redirect('admin/loans/apply');
        }
    }
    
    /**
     * Approve Application
     */
    public function approve($id) {
        $application = $this->Loan_model->get_application($id);
        
        if (!$application) {
            $this->session->set_flashdata('error', 'Application not found.');
            redirect('admin/loans/applications');
        }
        
        if ($this->input->method() === 'post') {
            $approval_data = [
                'approved_amount' => $this->input->post('approved_amount'),
                'approved_tenure_months' => $this->input->post('approved_tenure_months'),
                'approved_interest_rate' => $this->input->post('approved_interest_rate'),
                'remarks' => $this->input->post('remarks'),
                'loan_product_id' => $this->input->post('loan_product_id') ?: null
            ];

            // Loan product is required at approval time
            if (empty($approval_data['loan_product_id'])) {
                $this->session->set_flashdata('error', 'Please select a Loan Scheme/Product before approving.');
                redirect('admin/loans/approve/' . $id);
                return;
            }

            // Guarantor acceptance requirement
            $guarantor_count = $this->db->where('loan_application_id', $id)->count_all_results('loan_guarantors');
            $min_required = (int) $this->get_setting('min_guarantors', 1);
            $accepted = $this->Loan_model->get_accepted_guarantor_count($id);

            // Force approve option (admin override)
            $force = (bool) $this->input->post('force_approve');
            if ($guarantor_count > 0 && !$force && $accepted < $min_required) {
                $this->session->set_flashdata('error', 'At least ' . $min_required . ' guarantor(s) must accept before approval, or use Force Approve to override.');
                redirect('admin/loans/approve/' . $id);
                return;
            }

            // If force approve requested, mark pending guarantors as accepted by admin
            if ($force) {
                $this->db->where('loan_application_id', $id)
                         ->where('consent_status', 'pending')
                         ->update('loan_guarantors', [
                             'consent_status' => 'accepted',
                             'consent_date' => date('Y-m-d H:i:s'),
                             'consent_remarks' => 'Accepted by admin via Force Approve',
                             'updated_at' => date('Y-m-d H:i:s')
                         ]);

                // Audit log for admin override
                $this->log_audit('force_approve', 'loan_applications', 'loan_applications', $id, null, ['admin_id' => $this->session->userdata('admin_id')]);
            }

            // Force-savings override (admin explicitly bypasses savings checks)
            $force_savings = ($this->input->post('force_savings') == '1');
            log_message('debug', '[Loans::approve] force_savings POST value: ' . var_export($this->input->post('force_savings'), true) . ' | cast bool: ' . var_export($force_savings, true));
            if ($force_savings) {
                $this->log_audit('force_savings_override', 'loan_applications', 'loan_applications', $id, null, ['admin_id' => $this->session->userdata('admin_id'), 'approved_amount' => $approval_data['approved_amount']]);
            }

            // Proceed with admin approval
            try {
                $res = $this->Loan_model->admin_approve($id, $approval_data, $this->session->userdata('admin_id'), $force_savings);

                if ($res) {
                    $this->log_audit('admin_approved', 'loan_applications', 'loan_applications', $id, null, $approval_data);

                    // Notify the member so they can review and accept terms
                    $this->load->model('Notification_model');
                    $this->load->model('Member_model');
                    $approved_app = $this->Loan_model->get_application($id);
                    $applicant    = $this->Member_model->get_by_id($approved_app->member_id);
                    $review_url   = site_url('member/loans/application/' . $id);
                    $n_title      = 'Loan Approved – Action Required: ' . $approved_app->application_number;
                    $n_message    = 'Your loan application ' . $approved_app->application_number
                        . ' has been approved by the admin.'
                        . ' Approved Amount: ' . format_amount($approval_data['approved_amount'])
                        . ', Tenure: ' . $approval_data['approved_tenure_months'] . ' months'
                        . ', Rate: ' . $approval_data['approved_interest_rate'] . '% p.a.'
                        . ' Please log in and review/accept the terms to proceed to disbursement.';
                    $this->Notification_model->create('member', $approved_app->member_id, 'loan_admin_approved', $n_title, $n_message, [
                        'application_id' => $id,
                        'review_url'     => $review_url,
                    ]);
                    if ($applicant && !empty($applicant->email)) {
                        $html = '<p>Dear ' . htmlspecialchars($applicant->first_name . ' ' . $applicant->last_name) . ',</p>'
                            . '<p>Your loan application <strong>' . $approved_app->application_number . '</strong> has been approved.</p>'
                            . '<ul>'
                            . '<li><strong>Approved Amount:</strong> ' . format_amount($approval_data['approved_amount']) . '</li>'
                            . '<li><strong>Tenure:</strong> ' . $approval_data['approved_tenure_months'] . ' months</li>'
                            . '<li><strong>Interest Rate:</strong> ' . $approval_data['approved_interest_rate'] . '% p.a.</li>'
                            . '</ul>'
                            . '<p><a href="' . $review_url . '">Click here to review and accept the loan terms</a> to proceed to disbursement.</p>';
                        send_email($applicant->email, $n_title, $html);
                    }

                    $this->session->set_flashdata('success', 'Application approved. Member has been notified to review and accept the terms.' . ($force_savings ? ' (Savings check overridden by admin.)' : ''));
                    redirect('admin/loans/view_application/' . $id);
                    return;
                } else {
                    $this->session->set_flashdata('error', 'Loan approval failed. The application may have been modified. Please refresh and retry.');
                    redirect('admin/loans/approve/' . $id);
                    return;
                }
            } catch (Exception $e) {
                $this->session->set_flashdata('error', 'Approval failed: ' . $e->getMessage());
                redirect('admin/loans/approve/' . $id);
                return;
            }
        }
        
        $data['title'] = 'Approve Application';
        $data['page_title'] = 'Approve Loan Application';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Applications', 'url' => 'admin/loans/applications'],
            ['title' => 'Approve', 'url' => '']
        ];
        
        $data['application'] = $application;
        $data['member'] = $this->Member_model->get_member_details($application->member_id);
        
        // Get product details (may be null if member applied without scheme)
        $data['product'] = null;
        if (!empty($application->loan_product_id)) {
            $data['product'] = $this->db->where('id', $application->loan_product_id)
                                        ->get('loan_products')
                                        ->row();
        }

        // All active loan products for admin to select/change
        $data['loan_products'] = $this->db->where('is_active', 1)
                                          ->order_by('product_name', 'ASC')
                                          ->get('loan_products')
                                          ->result();

        // Savings constraint data for the approval view
        $savings_balance = $data['member']->savings_summary->current_balance ?? 0;
        $data['savings_balance'] = $savings_balance;
        $data['min_savings_required'] = !empty($data['product']) ? ($data['product']->min_savings_balance ?? 0) : 0;
        $data['savings_ratio']        = !empty($data['product']) ? ($data['product']->max_loan_to_savings_ratio ?? 0) : 0;
        $data['max_loan_by_savings']  = $data['savings_ratio'] > 0
            ? $savings_balance * $data['savings_ratio']
            : null; // null means no ratio restriction

        // Guarantor summary for view
        $data['guarantor_counts'] = [
            'total' => (int) $this->db->where('loan_application_id', $id)->count_all_results('loan_guarantors'),
            'accepted' => (int) $this->db->where('loan_application_id', $id)->where('consent_status', 'accepted')->count_all_results('loan_guarantors'),
            'pending' => (int) $this->db->where('loan_application_id', $id)->where('consent_status', 'pending')->count_all_results('loan_guarantors'),
            'rejected' => (int) $this->db->where('loan_application_id', $id)->where('consent_status', 'rejected')->count_all_results('loan_guarantors')
        ];
        $data['min_guarantors_required'] = (int) $this->get_setting('min_guarantors', 1);
        
        $this->load_view('admin/loans/approve', $data);
    }
    
    /**
     * Request Modification
     */
    public function request_modification($id) {
        $remarks = $this->input->post('remarks');
        if (!$remarks) {
            $this->json_response(['success' => false, 'message' => 'Remarks are required.']);
            return;
        }

        $proposed = [];
        if ($this->input->post('approved_amount')) $proposed['approved_amount'] = $this->input->post('approved_amount');
        if ($this->input->post('approved_tenure_months')) $proposed['approved_tenure_months'] = $this->input->post('approved_tenure_months');
        if ($this->input->post('approved_interest_rate')) $proposed['approved_interest_rate'] = $this->input->post('approved_interest_rate');

        $result = $this->Loan_model->request_modification($id, $remarks, $this->session->userdata('admin_id'), $proposed);

        if ($result) {
            $this->log_audit('modification_requested', 'loan_applications', 'loan_applications', $id, null, ['remarks' => $remarks, 'proposed' => $proposed]);
            $this->json_response(['success' => true, 'message' => 'Member notified to modify application.']);
        } else {
            $this->json_response(['success' => false, 'message' => 'Failed to request modification.']);
        }
    }

    /**
     * Reject Application
     */
    public function reject($id) {
        $reason = $this->input->post('reason');

        if (!$reason) {
            $this->json_response(['success' => false, 'message' => 'Rejection reason is required.']);
            return;
        }

        $result = $this->Loan_model->reject_application($id, $reason, $this->session->userdata('admin_id'));

        if ($result) {
            $this->log_audit('rejected', 'loan_applications', 'loan_applications', $id, null, ['reason' => $reason]);
            $this->json_response(['success' => true, 'message' => 'Application rejected.']);
        } else {
            $this->json_response(['success' => false, 'message' => 'Failed to reject application.']);
        }
    }
    
    /**
     * Disburse Loan
     */
    public function disburse($id) {
        $application = $this->Loan_model->get_application($id);
        
        if (!$application || $application->status !== 'member_approved') {
            $this->session->set_flashdata('error', 'Application not ready for disbursement.');
            redirect('admin/loans/applications');
        }
        
        if ($this->input->method() === 'post') {
            try {
                $disbursement_data = [
                    'disbursement_date' => $this->input->post('disbursement_date'),
                    'first_emi_date' => $this->input->post('first_emi_date'),
                    'disbursement_mode' => $this->input->post('disbursement_mode'),
                    'reference_number' => $this->input->post('reference_number')
                ];
                
                $loan_id = $this->Loan_model->disburse_loan($id, $disbursement_data, $this->session->userdata('admin_id'));
                
                if ($loan_id) {
                    // Post to ledger
                    $loan = $this->Loan_model->get_by_id($loan_id);
                    $this->load->model('Ledger_model');
                    $this->Ledger_model->post_transaction(
                        'loan_disbursement',
                        $loan_id,
                        $loan->principal_amount,
                        $loan->member_id,
                        'Loan disbursement: ' . $loan->loan_number,
                        $this->session->userdata('admin_id')
                    );
                    
                    // Processing fee entry
                    if ($loan->processing_fee > 0) {
                        $this->Ledger_model->post_transaction(
                            'processing_fee',
                            $loan_id,
                            $loan->processing_fee,
                            $loan->member_id,
                            'Processing fee for loan: ' . $loan->loan_number,
                            $this->session->userdata('admin_id')
                        );

                        // Auto-record in member other transactions
                        $this->load->model('Member_transaction_model');
                        $this->Member_transaction_model->record_processing_fee(
                            $loan->member_id,
                            $loan->processing_fee,
                            $loan_id,
                            $this->session->userdata('admin_id')
                        );
                    }
                    
                    $this->log_audit('disbursed', 'loans', 'loans', $loan_id, null, $disbursement_data);
                    $this->session->set_flashdata('success', 'Loan disbursed successfully.');
                    redirect('admin/loans/view/' . $loan_id);
                }
                
            } catch (Exception $e) {
                $this->session->set_flashdata('error', $e->getMessage());
                redirect('admin/loans/disburse/' . $id);
            }
        }
        
        $data['title'] = 'Disburse Loan';
        $data['page_title'] = 'Loan Disbursement';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Applications', 'url' => 'admin/loans/applications'],
            ['title' => 'Disburse', 'url' => '']
        ];
        
        $data['application'] = $application;
        $data['member'] = $this->Member_model->get_by_id($application->member_id);
        
        // Get product for processing fee calculation
        $data['product'] = $this->db->where('id', $application->loan_product_id)
                                    ->get('loan_products')
                                    ->row();
        
        // Calculate EMI preview
        $data['emi_calc'] = $this->Loan_model->calculate_emi(
            $application->approved_amount,
            $application->approved_interest_rate,
            $application->approved_tenure_months,
            $data['product']->interest_type
        );
        
        $this->load_view('admin/loans/disburse', $data);
    }
    
    /**
     * Collect EMI Payment
     */
    public function collect($id = null) {
        $data['title'] = 'Collect EMI';
        $data['page_title'] = 'Collect Loan Payment';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Loans', 'url' => 'admin/loans'],
            ['title' => 'Collect', 'url' => '']
        ];
        
        // Initialize defaults
        $data['loan'] = null;
        $data['pending_installments'] = [];
        $data['overdue_emis'] = [];
        $data['member'] = null;
        $data['product'] = null;
        
        if ($id) {
            $data['loan'] = $this->Loan_model->get_loan_details($id);
            $data['pending_installments'] = $this->db->where('loan_id', $id)
                                                      ->where('status', 'pending')
                                                      ->order_by('installment_number', 'ASC')
                                                      ->get('loan_installments')
                                                      ->result();
            $data['overdue_emis'] = $this->db->where('loan_id', $id)
                                              ->where('status', 'pending')
                                              ->where('due_date <', date('Y-m-d'))
                                              ->order_by('installment_number', 'ASC')
                                              ->get('loan_installments')
                                              ->result();
            
            // Load member and product
            if ($data['loan']) {
                $this->load->model('Member_model');
                $data['member'] = $this->Member_model->get_by_id($data['loan']->member_id);
                $data['product'] = $this->db->where('id', $data['loan']->loan_product_id)->get('loan_products')->row();
            }
        }
        
        $this->load_view('admin/loans/collect', $data);
    }
    
    /**
     * Record EMI Payment
     */
    public function record_payment() {
        if ($this->input->method() !== 'post') {
            redirect('admin/loans/collect');
        }
        
        $this->load->library('form_validation');
        $this->form_validation->set_rules('loan_id', 'Loan', 'required|numeric');
        $this->form_validation->set_rules('total_amount', 'Amount', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('payment_mode', 'Payment Mode', 'required');
        
        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('admin/loans/collect/' . $this->input->post('loan_id'));
        }
        
        try {
            $payment_data = [
                'loan_id' => $this->input->post('loan_id'),
                'installment_id' => $this->input->post('installment_id'),
                'total_amount' => $this->input->post('total_amount'),
                'payment_mode' => $this->input->post('payment_mode'),
                'payment_type' => $this->input->post('payment_type') ?: 'regular',
                'reference_number' => $this->input->post('reference_number'),
                'narration' => $this->input->post('remarks'),
                'created_by' => $this->session->userdata('admin_id')
            ];
            
            $payment_id = $this->Loan_model->record_payment($payment_data);
            
            if ($payment_id) {
                // Post to ledger
                $loan = $this->Loan_model->get_by_id($payment_data['loan_id']);
                $this->load->model('Ledger_model');
                $this->Ledger_model->post_transaction(
                    'loan_payment',
                    $payment_id,
                    $payment_data['total_amount'],
                    $loan->member_id,
                    'Loan payment for ' . $loan->loan_number,
                    $this->session->userdata('admin_id')
                );
                
                $this->log_audit('create', 'loan_payments', 'loan_payments', $payment_id, null, $payment_data);
                $this->session->set_flashdata('success', 'Payment recorded successfully.');
                redirect('admin/loans/view/' . $payment_data['loan_id']);
            }
            
        } catch (Exception $e) {
            $this->session->set_flashdata('error', $e->getMessage());
            redirect('admin/loans/collect/' . $this->input->post('loan_id'));
        }
    }
    
    /**
     * AJAX: Check Interest-Only Payment Eligibility
     * Returns eligibility status, interest amount, and extension info for a loan.
     */
    public function check_interest_only($loan_id = null) {
        if (!$loan_id) {
            return $this->output->set_content_type('application/json')
                               ->set_output(json_encode(['error' => 'Loan ID required']));
        }
        
        $result = $this->Loan_model->check_interest_only_eligibility($loan_id);
        
        return $this->output->set_content_type('application/json')
                           ->set_output(json_encode($result));
    }
    
    /**
     * Process Interest-Only Payment
     * When member pays only the interest, principal is deferred and tenure extends.
     */
    public function interest_only_payment() {
        if ($this->input->method() !== 'post') {
            redirect('admin/loans/collect');
        }
        
        $this->load->library('form_validation');
        $this->form_validation->set_rules('loan_id', 'Loan', 'required|numeric');
        $this->form_validation->set_rules('installment_id', 'Installment', 'required|numeric');
        $this->form_validation->set_rules('total_amount', 'Amount', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('payment_mode', 'Payment Mode', 'required');
        
        $loan_id = $this->input->post('loan_id');
        
        if ($this->form_validation->run() === FALSE) {
            if ($this->input->is_ajax_request()) {
                return $this->output->set_content_type('application/json')
                                   ->set_output(json_encode(['success' => false, 'message' => validation_errors()]));
            }
            $this->session->set_flashdata('error', validation_errors());
            redirect('admin/loans/collect/' . $loan_id);
        }
        
        try {
            // Check eligibility first
            $eligibility = $this->Loan_model->check_interest_only_eligibility($loan_id);
            if (!$eligibility['allowed']) {
                throw new Exception($eligibility['reason']);
            }
            
            $payment_data = [
                'loan_id' => $loan_id,
                'installment_id' => $this->input->post('installment_id'),
                'total_amount' => $this->input->post('total_amount'),
                'payment_mode' => $this->input->post('payment_mode'),
                'payment_type' => 'interest_only',
                'payment_date' => $this->input->post('payment_date') ?: date('Y-m-d'),
                'reference_number' => $this->input->post('reference_number'),
                'narration' => $this->input->post('remarks') ?: 'Interest-only payment - principal deferred',
                'created_by' => $this->session->userdata('admin_id')
            ];
            
            $payment_id = $this->Loan_model->record_payment($payment_data);
            
            if ($payment_id) {
                // Post to ledger
                $loan = $this->Loan_model->get_by_id($loan_id);
                $this->load->model('Ledger_model');
                $this->Ledger_model->post_transaction(
                    'loan_payment',
                    $payment_id,
                    $payment_data['total_amount'],
                    $loan->member_id,
                    'Interest-only payment for ' . $loan->loan_number . ' (tenure extended)',
                    $this->session->userdata('admin_id')
                );
                
                $this->log_audit('create', 'loan_payments', 'loan_payments', $payment_id, null, $payment_data);
                
                $success_msg = 'Interest-only payment recorded. Principal ₹' . 
                               number_format($eligibility['principal_deferred'], 2) . 
                               ' deferred. Loan tenure extended to ' . 
                               ($loan->tenure_months) . ' months.';
                
                if ($this->input->is_ajax_request()) {
                    return $this->output->set_content_type('application/json')
                                       ->set_output(json_encode([
                                           'success' => true, 
                                           'message' => $success_msg,
                                           'payment_id' => $payment_id,
                                           'new_tenure' => $loan->tenure_months,
                                           'extensions_used' => $loan->tenure_extensions
                                       ]));
                }
                
                $this->session->set_flashdata('success', $success_msg);
                redirect('admin/loans/view/' . $loan_id);
            }
            
        } catch (Exception $e) {
            if ($this->input->is_ajax_request()) {
                return $this->output->set_content_type('application/json')
                                   ->set_output(json_encode(['success' => false, 'message' => $e->getMessage()]));
            }
            $this->session->set_flashdata('error', $e->getMessage());
            redirect('admin/loans/collect/' . $loan_id);
        }
    }
    
    /**
     * AJAX: Get Interest-Only Payment History for a Loan
     */
    public function interest_only_history($loan_id = null) {
        if (!$loan_id) {
            return $this->output->set_content_type('application/json')
                               ->set_output(json_encode(['error' => 'Loan ID required']));
        }
        
        $history = $this->Loan_model->get_interest_only_history($loan_id);
        
        return $this->output->set_content_type('application/json')
                           ->set_output(json_encode(['success' => true, 'history' => $history]));
    }
    
    /**
     * Pending Approval - Applications awaiting admin approval
     */
    public function pending_approval() {
        $data['title'] = 'Pending Approval';
        $data['page_title'] = 'Applications Pending Approval';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Loans', 'url' => 'admin/loans'],
            ['title' => 'Pending Approval', 'url' => '']
        ];
        
        $data['applications'] = $this->Loan_model->get_pending_applications();
        
        // Get status counts for the cards
        $data['stats'] = [];
        $status_counts = $this->db->select('status, COUNT(*) as count')
                                  ->where_in('status', ['pending', 'under_review', 'guarantor_pending', 'admin_approved', 'member_approved'])
                                  ->group_by('status')
                                  ->get('loan_applications')
                                  ->result();
        
        foreach ($status_counts as $stat) {
            $data['stats'][$stat->status] = $stat->count;
        }
        
        $this->load_view('admin/loans/pending_approval', $data);
    }
    
    /**
     * Disbursement - Applications ready for disbursement
     */
    public function disbursement() {
        $data['title'] = 'Loan Disbursement';
        $data['page_title'] = 'Ready for Disbursement';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Loans', 'url' => 'admin/loans'],
            ['title' => 'Disbursement', 'url' => '']
        ];
        
        // Get approved applications ready for disbursement
        $data['applications'] = $this->Loan_model->get_ready_for_disbursement();
        
        // Calculate totals
        $data['total_amount'] = 0;
        $data['total_monthly_emi'] = 0;
        if (!empty($data['applications'])) {
            foreach ($data['applications'] as $app) {
                $data['total_amount'] += $app->approved_amount;
                $data['total_monthly_emi'] += $app->monthly_emi ?? 0;
            }
        }
        
        // Get recent disbursements
        $data['recent_disbursements'] = $this->db->select('l.*, m.first_name, m.last_name, m.member_code')
                                                  ->from('loans l')
                                                  ->join('members m', 'm.id = l.member_id')
                                                  ->order_by('l.disbursement_date', 'DESC')
                                                  ->limit(10)
                                                  ->get()
                                                  ->result();
        
        $this->load_view('admin/loans/disbursement', $data);
    }
    
    /**
     * Overdue Loans
     */
    public function overdue() {
        $data['title'] = 'Overdue Loans';
        $data['page_title'] = 'Overdue EMIs';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Loans', 'url' => 'admin/loans'],
            ['title' => 'Overdue', 'url' => '']
        ];
        
        $data['overdue'] = $this->Loan_model->get_overdue_loans();
        $data['stats'] = $this->Loan_model->get_dashboard_stats();
        
        // Aging analysis
        $aging = ['1_30' => 0, '31_60' => 0, '61_90' => 0, '90_plus' => 0];
        if (!empty($data['overdue'])) {
            foreach ($data['overdue'] as $loan) {
                $days = floor((time() - safe_timestamp($loan->due_date)) / 86400);
                if ($days <= 30) $aging['1_30']++;
                elseif ($days <= 60) $aging['31_60']++;
                elseif ($days <= 90) $aging['61_90']++;
                else $aging['90_plus']++;
            }
        }
        $data['aging'] = $aging;
        
        $this->load_view('admin/loans/overdue', $data);
    }
    
    /**
     * EMI Calculator
     */
    public function calculator() {
        $data['title'] = 'EMI Calculator';
        $data['page_title'] = 'Loan EMI Calculator';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Loans', 'url' => 'admin/loans'],
            ['title' => 'Calculator', 'url' => '']
        ];
        
        $data['products'] = $this->Loan_model->get_products();
        
        $this->load_view('admin/loans/calculator', $data);
    }
    
    /**
     * Calculate EMI (AJAX)
     */
    public function calculate_emi() {
        $principal = $this->input->post('principal');
        $rate = $this->input->post('rate');
        $tenure = $this->input->post('tenure');
        $type = $this->input->post('type') ?: 'reducing';
        
        $result = $this->Loan_model->calculate_emi($principal, $rate, $tenure, $type);
        $this->json_response($result);
    }
    
    /**
     * Manage Loan Products
     */
    public function products() {
        $data['title'] = 'Loan Products';
        $data['page_title'] = 'Manage Loan Products';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Loans', 'url' => 'admin/loans'],
            ['title' => 'Products', 'url' => '']
        ];
        
        // Get products with loan counts
        // Ensure text helper is loaded (for character_limiter in views) and normalize fields for backward compatibility
        $this->load->helper('text');
        $products = $this->Loan_model->get_products(false);
        foreach ($products as &$product) {
            // Normalize name field if older schema uses product_name
            if (!isset($product->name) && isset($product->product_name)) {
                $product->name = $product->product_name;
            }
            // Product code may be named product_code in DB
            if (!isset($product->code) && isset($product->product_code)) {
                $product->code = $product->product_code;
            }
            // Normalize tenure fields (months)
            if (!isset($product->min_tenure) && isset($product->min_tenure_months)) {
                $product->min_tenure = $product->min_tenure_months;
            }
            if (!isset($product->max_tenure) && isset($product->max_tenure_months)) {
                $product->max_tenure = $product->max_tenure_months;
            }
            // Processing fee/value compatibility
            if (!isset($product->processing_fee) && isset($product->processing_fee_value)) {
                $product->processing_fee = $product->processing_fee_value;
            }
            if (!isset($product->processing_fee_type) && isset($product->processing_fee_type)) {
                // keep existing
            }
            // Ensure description exists
            if (!isset($product->description)) {
                $product->description = '';
            }
            // Ensure other commonly expected fields exist
            $product->interest_rate = $product->interest_rate ?? 0;
            $product->interest_type = $product->interest_type ?? 'reducing';
            $product->is_active = isset($product->is_active) ? (int)$product->is_active : 0;

            $product->active_loans = $this->db->where('loan_product_id', $product->id)
                                              ->where('status', 'active')
                                              ->count_all_results('loans') ?: 0;
        }
        $data['products'] = $products;
        
        $this->load_view('admin/loans/products', $data);
    }
    
    /**
     * Save Loan Product (AJAX)
     */
    public function save_product() {
        if ($this->input->method() !== 'post') {
            $this->json_response(['success' => false, 'message' => 'Invalid request']);
            return;
        }
        
        $id = $this->input->post('id');
        
        $data = [
            'product_name' => $this->input->post('name'),
            'product_code' => strtoupper($this->input->post('code')),
            'description' => $this->input->post('description'),
            'min_amount' => $this->input->post('min_amount'),
            'max_amount' => $this->input->post('max_amount'),
            'interest_rate' => $this->input->post('interest_rate'),
            'interest_type' => $this->input->post('interest_type'),
            'min_tenure_months' => $this->input->post('min_tenure'),
            'max_tenure_months' => $this->input->post('max_tenure'),
            'processing_fee_value' => $this->input->post('processing_fee') ?: 0,
            'processing_fee_type' => $this->input->post('processing_fee_type'),
            'late_fee_value' => $this->input->post('late_fine_rate') ?: 0,
            'grace_period_days' => $this->input->post('grace_period') ?: 0,
            'is_active' => $this->input->post('is_active') ? 1 : 0
        ];
        
        // Validation
        if (empty($data['product_name']) || empty($data['product_code'])) {
            $this->json_response(['success' => false, 'message' => 'Name and code are required']);
            return;
        }
        
        if ($data['min_amount'] > $data['max_amount']) {
            $this->json_response(['success' => false, 'message' => 'Min amount cannot exceed max amount']);
            return;
        }
        
        if ($data['min_tenure_months'] > $data['max_tenure_months']) {
            $this->json_response(['success' => false, 'message' => 'Min tenure cannot exceed max tenure']);
            return;
        }
        
        // Check for duplicate code
        $existing = $this->db->where('product_code', $data['product_code']);
        if ($id) {
            $existing->where('id !=', $id);
        }
        if ($existing->get('loan_products')->num_rows() > 0) {
            $this->json_response(['success' => false, 'message' => 'Product code already exists']);
            return;
        }
        
        if ($id) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', $id)->update('loan_products', $data);
            $this->log_audit('update', 'loan_products', 'loan_products', $id, null, $data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('loan_products', $data);
            $id = $this->db->insert_id();
            $this->log_audit('create', 'loan_products', 'loan_products', $id, null, $data);
        }
        
        $this->json_response(['success' => true, 'message' => 'Product saved successfully']);
    }
    
    /**
     * Toggle Product Status (AJAX)
     */
    public function toggle_product_status() {
        $id = $this->input->post('id');
        $is_active = $this->input->post('is_active');
        
        if (!$id) {
            $this->json_response(['success' => false, 'message' => 'Invalid product']);
            return;
        }
        
        $this->db->where('id', $id)->update('loan_products', [
            'is_active' => $is_active,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        $this->log_audit('status_change', 'loan_products', 'loan_products', $id, null, ['is_active' => $is_active]);
        $this->json_response(['success' => true]);
    }
    
    /**
     * Delete Product (AJAX)
     */
    public function delete_product() {
        $id = $this->input->post('id');
        
        if (!$id) {
            $this->json_response(['success' => false, 'message' => 'Invalid product']);
            return;
        }
        
        // Check if product is in use
        $active_loans = $this->db->where('loan_product_id', $id)
                                 ->count_all_results('loans');
        
        if ($active_loans > 0) {
            $this->json_response(['success' => false, 'message' => 'Cannot delete product with active loans']);
            return;
        }
        
        $this->db->where('id', $id)->delete('loan_products');
        $this->log_audit('delete', 'loan_products', 'loan_products', $id, null, null);
        
        $this->json_response(['success' => true, 'message' => 'Product deleted successfully']);
    }
    
    /**
     * Send Payment Reminder (AJAX)
     */
    public function send_reminder() {
        $loan_id = $this->input->post('loan_id');
        
        if (!$loan_id) {
            $this->json_response(['success' => false, 'message' => 'Please select a valid loan.']);
            return;
        }
        
        // Get loan details with member info
        $loan = $this->Loan_model->get_loan_details($loan_id);
        if (!$loan) {
            $this->json_response(['success' => false, 'message' => 'Loan not found.']);
            return;
        }
        
        $member = $this->Member_model->get_by_id($loan->member_id);
        if (!$member) {
            $this->json_response(['success' => false, 'message' => 'Member not found for this loan.']);
            return;
        }
        
        $reminder_sent = false;
        $channels = [];
        
        // Send Email reminder if member has email
        if (!empty($member->email)) {
            try {
                $this->load->library('email');
                $from_email = $this->get_setting('smtp_user', 'noreply@windeepfinance.com');
                $org_name = $this->get_setting('organization_name', 'Windeep Finance');
                
                $this->email->from($from_email, $org_name);
                $this->email->to($member->email);
                $this->email->subject('Payment Reminder - Loan ' . $loan->loan_number);
                
                $message = "Dear " . $member->first_name . ",\n\n";
                $message .= "This is a friendly reminder regarding your loan " . $loan->loan_number . ".\n\n";
                $message .= "Outstanding Amount: " . format_amount($loan->outstanding_principal ?? 0) . "\n";
                $message .= "Please make your payment at the earliest.\n\n";
                $message .= "Thank you,\n" . $org_name;
                
                $this->email->message($message);
                
                if ($this->email->send()) {
                    $reminder_sent = true;
                    $channels[] = 'email';
                }
            } catch (Exception $e) {
                log_message('error', 'Loan reminder email failed: ' . $e->getMessage());
            }
        }
        
        // Send in-app notification
        $this->load->model('Notification_model');
        $this->Notification_model->create([
            'target_type' => 'member',
            'target_id' => $member->id,
            'title' => 'Payment Reminder',
            'message' => 'Reminder: Your loan ' . $loan->loan_number . ' has a pending payment. Please clear your dues at the earliest.',
            'type' => 'reminder',
            'reference_type' => 'loan',
            'reference_id' => $loan_id,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        $channels[] = 'notification';
        $reminder_sent = true;
        
        // Log the audit
        $this->log_audit('reminder_sent', 'loans', 'loans', $loan_id, null, [
            'member_id' => $loan->member_id,
            'phone' => $member->phone ?? '',
            'channels' => implode(', ', $channels)
        ]);
        
        if ($reminder_sent) {
            $this->json_response(['success' => true, 'message' => 'Reminder sent successfully via: ' . implode(', ', $channels)]);
        } else {
            $this->json_response(['success' => false, 'message' => 'Failed to send reminder. Member has no email or phone on file.']);
        }
    }
    
    /**
     * Print Loan Statement
     */
    public function statement($id) {
        $loan = $this->Loan_model->get_loan_details($id);
        
        if (!$loan) {
            $this->session->set_flashdata('error', 'Loan not found.');
            redirect('admin/loans');
        }
        
        $data['loan'] = $loan;
        $data['member'] = $this->Member_model->get_by_id($loan->member_id);
        
        $this->load->view('admin/loans/print_statement', $data);
    }
    
    /**
     * Repayment History
     */
    public function repayment_history($id = null) {
        $data['title'] = 'Repayment History';
        $data['page_title'] = 'Loan Repayment History';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Loans', 'url' => 'admin/loans'],
            ['title' => 'Repayment History', 'url' => '']
        ];
        
        // Filters
        $filters = [];
        $loan_id = $id ?? $this->input->get('loan_id');
        $member_id = $this->input->get('member_id');
        $date_from = $this->input->get('date_from');
        $date_to = $this->input->get('date_to');
        $payment_mode = $this->input->get('payment_mode');
        $payment_type = $this->input->get('payment_type');
        
        // Build query - reset query builder first
        $this->db->reset_query();
        $this->db->select('lp.id, lp.loan_id, lp.payment_date, lp.total_amount, lp.principal_component, lp.interest_component, lp.fine_component, lp.payment_mode, lp.payment_code, lp.reference_number, lp.payment_type, lp.created_at as payment_created_at, l.loan_number, l.member_id, m.member_code, m.first_name, m.last_name, m.phone');
        $this->db->from('loan_payments lp');
        $this->db->join('loans l', 'l.id = lp.loan_id', 'left');
        $this->db->join('members m', 'm.id = l.member_id', 'left');
        $this->db->where('lp.is_reversed', 0);
        
        if ($loan_id) {
            $this->db->where('lp.loan_id', $loan_id);
            $filters['loan_id'] = $loan_id;
        }
        
        if ($member_id) {
            $this->db->where('l.member_id', $member_id);
            $filters['member_id'] = $member_id;
        }
        
        if ($date_from) {
            $this->db->where('lp.payment_date >=', $date_from);
            $filters['date_from'] = $date_from;
        }
        
        if ($date_to) {
            $this->db->where('lp.payment_date <=', $date_to);
            $filters['date_to'] = $date_to;
        }
        
        if ($payment_mode) {
            $this->db->where('lp.payment_mode', $payment_mode);
            $filters['payment_mode'] = $payment_mode;
        }
        
        if ($payment_type) {
            $this->db->where('lp.payment_type', $payment_type);
            $filters['payment_type'] = $payment_type;
        }
        
        $this->db->order_by('lp.payment_date', 'DESC');
        $this->db->order_by('lp.created_at', 'DESC');
        
        $data['payments'] = $this->db->get()->result();
        
        // Get loan details for context (after query execution)
        if ($loan_id) {
            $data['loan'] = $this->Loan_model->get_loan_details($loan_id);
        }
        
        $data['filters'] = $filters;
        
        // Calculate totals
        $data['total_amount'] = array_sum(array_column($data['payments'], 'total_amount'));
        $data['total_principal'] = array_sum(array_column($data['payments'], 'principal_component'));
        $data['total_interest'] = array_sum(array_column($data['payments'], 'interest_component'));
        $data['total_fine'] = array_sum(array_column($data['payments'], 'fine_component'));
        
        // Get all loans for filter dropdown
        $data['all_loans'] = $this->db->select('l.id, l.loan_number, m.member_code, m.first_name, m.last_name')
                                      ->from('loans l')
                                      ->join('members m', 'm.id = l.member_id')
                                      ->where_in('l.status', ['active', 'overdue', 'npa'])
                                      ->order_by('l.loan_number', 'DESC')
                                      ->get()
                                      ->result();
        
        $this->load_view('admin/loans/repayment_history', $data);
    }
    
    /**
     * View Single Payment Receipt
     */
    public function payment_receipt($payment_id) {
        $addressExpr = "CONCAT(IFNULL(m.address_line1,''), ' ', IFNULL(m.address_line2,''), ', ', IFNULL(m.city,'')) as address";
        $payment = $this->db->select('lp.*, l.loan_number, l.member_id, l.principal_amount, l.interest_rate, m.member_code, m.first_name, m.last_name, m.phone')
                           ->select($addressExpr, false)
                           ->from('loan_payments lp')
                           ->join('loans l', 'l.id = lp.loan_id')
                           ->join('members m', 'm.id = l.member_id')
                           ->where('lp.id', $payment_id)
                           ->get()
                           ->row();
        
        if (!$payment) {
            $this->session->set_flashdata('error', 'Payment record not found.');
            redirect('admin/loans/repayment_history');
        }
        
        $data['payment'] = $payment;
        $data['title'] = 'Payment Receipt - ' . $payment->payment_code;
        
        $this->load->view('admin/loans/payment_receipt', $data);
    }
}

