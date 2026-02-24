<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Members Controller - Member Management
 */
class Members extends Admin_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('Member_model');
    }
    
    /**
     * List Members
     */
    public function index() {
        $data['title'] = 'Members';
        $data['page_title'] = 'Member Management';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Members', 'url' => '']
        ];
        
        // Get filters
        $filters = [
            'status' => $this->input->get('status'),
            'search' => $this->input->get('search')
        ];
        
        // Pagination
        $page = (int) ($this->input->get('page') ?: 1);
        $per_page = 20;
        
        $paginated_result = $this->Member_model->get_paginated($filters, $page, $per_page);
        $data['members'] = $paginated_result['data'];
        $data['pagination'] = [
            'current_page' => $paginated_result['current_page'],
            'per_page' => $paginated_result['per_page'],
            'total' => $paginated_result['total'],
            'total_pages' => $paginated_result['total_pages']
        ];
        $data['filters'] = $filters;
        //echo"<pre>";
        //print_r($data);die;
        $this->load_view('admin/members/index', $data);
    }
    
    /**
     * View Member
     */
    public function view($id = null) {
        if (!$id) {
            redirect('admin/members');
        }

        $member = $this->Member_model->get_member_details($id);
        
        if (!$member) {
            $this->session->set_flashdata('error', 'Member not found.');
            redirect('admin/members');
        }
        
        $data['title'] = 'View Member';
        $data['page_title'] = 'Member Details';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Members', 'url' => 'admin/members'],
            ['title' => $member->member_code, 'url' => '']
        ];
        
        $data['member'] = $member;
        
        // Get savings accounts
        $this->load->model('Savings_model');
        $data['savings_accounts'] = $this->Savings_model->get_member_accounts($id);
        
        // Get loans
        $this->load->model('Loan_model');
        $data['loans'] = $this->Loan_model->get_member_loans($id);
        
        // Get fines
        $this->load->model('Fine_model');
        $data['fines'] = $this->Fine_model->get_member_fines($id);
        
        // Get ledger
        $this->load->model('Ledger_model');
        $data['ledger'] = $this->Ledger_model->get_member_ledger($id);
        
        $this->load_view('admin/members/view', $data);
    }
    
    /**
     * Create Member Form
     */
    public function create() {
        $data['title'] = 'Add Member';
        $data['page_title'] = 'Add New Member';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Members', 'url' => 'admin/members'],
            ['title' => 'Add New', 'url' => '']
        ];
        
        // Generate member code for display
        $data['member_code'] = $this->Member_model->generate_member_code();
        
        // Load existing members for referral dropdown
        $data['existing_members'] = $this->Member_model->get_active_members_dropdown();
        
        $this->load_view('admin/members/create', $data);
    }
    
    /**
     * Store Member
     */
    public function store() {
        if ($this->input->method() !== 'post') {
            redirect('admin/members/create');
        }
        
        // Normalize phone before validation to ensure consistent uniqueness checks
        $this->load->helper('format_helper');
        $raw_phone = $this->input->post('phone', TRUE);
        $normalized_phone = normalize_phone($raw_phone);
        // Override POST so form_validation rules use normalized value
        if ($normalized_phone !== null) {
            $_POST['phone'] = $normalized_phone;
        }

        // Validation
        $this->load->library('form_validation');
        $this->form_validation->set_rules('first_name', 'First Name', 'required|max_length[100]');
        $this->form_validation->set_rules('last_name', 'Last Name', 'required|max_length[100]');
        $this->form_validation->set_rules('phone', 'Phone', 'required|numeric|min_length[10]|max_length[15]');
        $this->form_validation->set_rules('email', 'Email', 'valid_email');
        $this->form_validation->set_rules('date_of_birth', 'Date of Birth', 'callback_validate_age');
        $this->form_validation->set_rules('gender', 'Gender', 'in_list[male,female,other]');
        
        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('admin/members/create');
        }
        
        // Prepare data (use normalized phone)
        $member_data = [
            'first_name' => $this->input->post('first_name', TRUE),
            'middle_name' => $this->input->post('middle_name', TRUE),
            'last_name' => $this->input->post('last_name', TRUE),
            'phone' => normalize_phone($this->input->post('phone', TRUE)),
            'alternate_phone' => normalize_phone($this->input->post('alternate_phone', TRUE)),
            'email' => $this->input->post('email', TRUE),
            'date_of_birth' => $this->input->post('date_of_birth'),
            'gender' => $this->input->post('gender'),
            'marital_status' => $this->input->post('marital_status'),
            'occupation' => $this->input->post('occupation', TRUE),
            'monthly_income' => $this->input->post('monthly_income'),
            'address_line1' => $this->input->post('address_line1', TRUE),
            'address_line2' => $this->input->post('address_line2', TRUE),
            'city' => $this->input->post('city', TRUE),
            'state' => $this->input->post('state', TRUE),
            'pincode' => $this->input->post('pincode', TRUE),
            'id_proof_type' => $this->input->post('id_proof_type'),
            'id_proof_number' => $this->input->post('id_proof_number', TRUE),
            'pan_number' => strtoupper($this->input->post('pan_number', TRUE)),
            'bank_name' => $this->input->post('bank_name', TRUE),
            'bank_account_number' => $this->input->post('bank_account_number', TRUE),
            'bank_ifsc' => strtoupper($this->input->post('bank_ifsc', TRUE)),
            'nominee_name' => $this->input->post('nominee_name', TRUE),
            'nominee_relationship' => $this->input->post('nominee_relationship', TRUE),
            'nominee_phone' => normalize_phone($this->input->post('nominee_phone', TRUE)),
            'join_date' => $this->input->post('join_date') ?: date('Y-m-d'),
            'member_level' => $this->input->post('member_level') ?: NULL,
            'created_by' => $this->session->userdata('admin_id')
        ];
        
        // Handle profile image upload
        if (!empty($_FILES['profile_image']['name'])) {
            $upload_path = './uploads/profile_images/';
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0755, TRUE);
            }
            
            $config['upload_path'] = $upload_path;
            $config['allowed_types'] = 'jpg|jpeg|png';
            $config['max_size'] = 2048;
            $config['file_name'] = 'member_' . time();
            
            $this->load->library('upload', $config);
            
            if ($this->upload->do_upload('profile_image')) {
                $member_data['profile_image'] = $this->upload->data('file_name');
            }
        }
        
        // Create member
        $member_id = $this->Member_model->create_member($member_data);
        
        if ($member_id) {
            // Get the created member details
            $created_member = $this->Member_model->get_by_id($member_id);
            
            // Send welcome email if email is provided and settings are configured
            if (!empty($member_data['email']) && $created_member) {
                $this->load->helper('email');
                
                $member_name = $member_data['first_name'];
                if (!empty($member_data['middle_name'])) {
                    $member_name .= ' ' . $member_data['middle_name'];
                }
                $member_name .= ' ' . $member_data['last_name'];
                
                // Default password is the member code
                $default_password = $created_member->member_code;
                
                // Send welcome email (async, don't wait for response)
                $email_result = send_welcome_email(
                    $created_member->member_code,
                    $member_name,
                    $member_data['email'],
                    $default_password
                );
                
                if ($email_result['success']) {
                    log_message('info', 'Welcome email sent to member: ' . $created_member->member_code);
                } else {
                    log_message('error', 'Failed to send welcome email: ' . $email_result['message']);
                }
            }
            
            $this->log_audit('create', 'members', 'members', $member_id, null, $member_data);

            // Auto-enroll in default savings scheme
            $this->load->model('Savings_model');
            $this->Savings_model->enroll_in_default_scheme($member_id, $this->session->userdata('admin_id'));

            $this->session->set_flashdata('success', 'Member created successfully.');
            redirect('admin/members/view/' . $member_id);
        } else {
            $this->session->set_flashdata('error', 'Member registration failed. Please check for duplicate member codes or missing required fields and try again.');
            redirect('admin/members/create');
        }
    }
    
    /**
     * Edit Member Form
     */
    public function edit($id = null) {
        if (!$id) { redirect('admin/members'); }
        $member = $this->Member_model->get_by_id($id);
        
        if (!$member) {
            $this->session->set_flashdata('error', 'Member not found.');
            redirect('admin/members');
        }
        
        $data['title'] = 'Edit Member';
        $data['page_title'] = 'Edit Member';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Members', 'url' => 'admin/members'],
            ['title' => $member->member_code, 'url' => 'admin/members/view/' . $id],
            ['title' => 'Edit', 'url' => '']
        ];
        
        $data['member'] = $member;
        
        $this->load_view('admin/members/edit', $data);
    }
    
    /**
     * Update Member
     */
    public function update($id = null) {
        if (!$id) { redirect('admin/members'); }
        if ($this->input->method() !== 'post') {
            redirect('admin/members/edit/' . $id);
        }
        
        $member = $this->Member_model->get_by_id($id);
        
        if (!$member) {
            $this->session->set_flashdata('error', 'Member not found.');
            redirect('admin/members');
        }
        
        // Normalize phone before validation
        $this->load->helper('format_helper');
        $raw_phone = $this->input->post('phone', TRUE);
        $normalized_phone = normalize_phone($raw_phone);
        if ($normalized_phone !== null) {
            $_POST['phone'] = $normalized_phone;
        }

        // Validation
        $this->load->library('form_validation');
        $this->form_validation->set_rules('first_name', 'First Name', 'required|max_length[100]');
        $this->form_validation->set_rules('last_name', 'Last Name', 'required|max_length[100]');
        $this->form_validation->set_rules('phone', 'Phone', 'required|numeric|min_length[10]|max_length[15]');
        $this->form_validation->set_rules('date_of_birth', 'Date of Birth', 'required|callback_validate_age');
        

        
        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('admin/members/edit/' . $id);
        }
        
        // Prepare data
        $old_data = (array) $member;
        
        $aadhaar_raw = $this->input->post('aadhaar_number');
        $pan_raw = $this->input->post('pan_number');
        $this->load->helper('format_helper');
        $aadhaar = sanitize_aadhaar($aadhaar_raw);
        $pan = sanitize_pan($pan_raw);

        if ($aadhaar && !validate_aadhaar($aadhaar)) {
            $this->session->set_flashdata('error', 'Aadhaar number is invalid. It must be 12 digits.');
            redirect('admin/members/edit/' . $id);
            return;
        }
        if ($pan && !validate_pan($pan)) {
            $this->session->set_flashdata('error', 'PAN number is invalid. Format: AAAAA9999A');
            redirect('admin/members/edit/' . $id);
            return;
        }

        $member_data = [
            'first_name' => $this->input->post('first_name', TRUE),
            'middle_name' => $this->input->post('middle_name', TRUE),
            'last_name' => $this->input->post('last_name', TRUE),
            'father_name' => $this->input->post('father_name', TRUE) ?: NULL,
            'phone' => $normalized_phone,
            'alternate_phone' => $this->input->post('alternate_phone', TRUE) ?: NULL,
            'email' => $this->input->post('email', TRUE),
            'date_of_birth' => $this->input->post('date_of_birth'),
            'gender' => $this->input->post('gender'),
            'marital_status' => $this->input->post('marital_status'),
            'join_date' => $this->input->post('join_date') ?: NULL,
            'membership_type' => $this->input->post('membership_type') ?: 'regular',
            'occupation' => $this->input->post('occupation', TRUE),
            'monthly_income' => $this->input->post('monthly_income'),
            'address_line1' => $this->input->post('address_line1', TRUE),
            'address_line2' => $this->input->post('address_line2', TRUE),
            'city' => $this->input->post('city', TRUE),
            'state' => $this->input->post('state', TRUE),
            'pincode' => $this->input->post('pincode', TRUE),
            'id_proof_type' => $this->input->post('id_proof_type'),
            'id_proof_number' => $this->input->post('id_proof_number', TRUE),
            'pan_number' => $pan,
            'aadhaar_number' => $aadhaar,
            'bank_name' => $this->input->post('bank_name', TRUE),
            'bank_account_number' => $this->input->post('bank_account_number', TRUE),
            'bank_ifsc' => strtoupper($this->input->post('bank_ifsc', TRUE)),
            'nominee_name' => $this->input->post('nominee_name', TRUE),
            'nominee_relationship' => $this->input->post('nominee_relationship', TRUE),
            'nominee_phone' => $this->input->post('nominee_phone', TRUE),
            'member_level' => $this->input->post('member_level') ?: NULL
        ];
        
        // Handle profile image upload
        if (!empty($_FILES['profile_image']['name'])) {
            $upload_path = './uploads/profile_images/';
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0755, TRUE);
            }
            
            $config['upload_path'] = $upload_path;
            $config['allowed_types'] = 'jpg|jpeg|png';
            $config['max_size'] = 2048;
            $config['file_name'] = 'member_' . $id . '_' . time();
            
            $this->load->library('upload', $config);
            
            if ($this->upload->do_upload('profile_image')) {
                // Delete old image
                if (!empty($member->profile_image) && file_exists($upload_path . $member->profile_image)) {
                    unlink($upload_path . $member->profile_image);
                }
                $member_data['profile_image'] = $this->upload->data('file_name');
            }
        }

        // Handle document uploads (aadhaar_doc, pan_doc, address_proof_doc)
        $upload_base = './uploads/members_docs/' . $id . '/';
        if (!is_dir($upload_base)) mkdir($upload_base, 0755, TRUE);
        $docs = ['aadhaar_doc' => 'aadhaar_doc', 'pan_doc' => 'pan_doc', 'address_proof_doc' => 'address_proof'];
        foreach ($docs as $field => $input_name) {
            if (!empty($_FILES[$input_name]['name'])) {
                $config = [];
                $config['upload_path'] = $upload_base;
                $config['allowed_types'] = 'jpg|jpeg|png|pdf';
                $config['max_size'] = 4096;
                $config['file_name'] = $field . '_' . $id . '_' . time();

                $this->load->library('upload');
                $this->upload->initialize($config);
                if ($this->upload->do_upload($input_name)) {
                    // delete old
                    if (!empty($member->{$field}) && file_exists($upload_base . $member->{$field})) {
                        @unlink($upload_base . $member->{$field});
                    }
                    $member_data[$field] = $this->upload->data('file_name');
                } else {
                    $this->session->set_flashdata('error', 'File upload failed: ' . $this->upload->display_errors('', ''));
                    redirect('admin/members/edit/' . $id);
                    return;
                }
            }
        }
        
        // Update member
        $result = $this->Member_model->update($id, $member_data);
        
        if ($result) {
            $this->log_audit('update', 'members', 'members', $id, $old_data, $member_data);
            $this->session->set_flashdata('success', 'Member updated successfully.');
        } else {
            $this->session->set_flashdata('error', 'Member details could not be saved. Please verify the information and try again.');
        }
        
        redirect('admin/members/view/' . $id);
    }
    
    /**
     * Validate age - must be at least 18 years
     */
    public function validate_age($dob) {
        if (empty($dob)) {
            return TRUE; // DOB is optional
        }

        // Use DateTime to compute precise age
        try {
            $dob_dt = new DateTime($dob);
        } catch (Exception $e) {
            $ts = strtotime($dob);
            if ($ts === FALSE) {
                $this->form_validation->set_message('validate_age', 'the age of the member should be greater than 18 years.');
                return FALSE;
            }
            $dob_dt = (new DateTime())->setTimestamp($ts);
        }

        $today = new DateTime();
        $age = $today->diff($dob_dt)->y;

        if ($age < 18) {
            $this->form_validation->set_message('validate_age', 'the age of the member should be greater than 18 years.');
            return FALSE;
        }

        return TRUE;
    }
    
    /**
     * Update Status
     */
    public function update_status($id = null) {
        if (!$id) { $this->json_response(['success' => false, 'message' => 'Invalid member.']); return; }
        $member = $this->Member_model->get_by_id($id);
        
        if (!$member) {
            $this->json_response(['success' => false, 'message' => 'Member not found.']);
            return;
        }
        
        $status = $this->input->post('status');
        $reason = $this->input->post('reason');
        
        $result = $this->Member_model->update_status($id, $status, $reason, $this->session->userdata('admin_id'));
        
        if ($result) {
            $this->log_audit('status_change', 'members', 'members', $id, ['status' => $member->status], ['status' => $status, 'reason' => $reason]);
            $this->json_response(['success' => true, 'message' => 'Status updated successfully.']);
        } else {
            $this->json_response(['success' => false, 'message' => 'Failed to update status.']);
        }
    }
    
    /**
     * Verify KYC
     */
    public function verify_kyc($id = null) {
        if (!$id) { redirect('admin/members'); }
        $member = $this->Member_model->get_by_id($id);
        
        if (!$member) {
            $this->session->set_flashdata('error', 'Member not found.');
            redirect('admin/members');
        }
        
        $result = $this->Member_model->verify_kyc($id, $this->session->userdata('admin_id'));
        
        if ($result) {
            $this->log_audit('kyc_verified', 'members', 'members', $id);
            $this->session->set_flashdata('success', 'KYC verified successfully.');
        } else {
            $this->session->set_flashdata('error', 'KYC verification could not be completed. The member record may have been modified. Please refresh and try again.');
        }
        
        redirect('admin/members/view/' . $id);
    }
    
    /**
     * Search Members (AJAX)
     */
    public function search() {
        $query = $this->input->get('q');
        $members = $this->Member_model->search_members($query, 20);
        
        $results = [];
        foreach ($members as $m) {
            $results[] = [
                'id' => $m->id,
                'text' => $m->member_code . ' - ' . $m->first_name . ' ' . $m->last_name . ' (' . $m->phone . ')',
                'member_code' => $m->member_code,
                'name' => $m->first_name . ' ' . $m->last_name,
                'phone' => $m->phone
            ];
        }
        
        $this->json_response(['results' => $results]);
    }
    
    /**
     * Export Members
     */
    public function export() {
        $format = $this->input->get('format') ?: 'csv';
        $filters = [
            'status' => $this->input->get('status')
        ];
        
        $members = $this->Member_model->get_all_filtered($filters);
        
        if ($format === 'csv') {
            $this->export_csv($members);
        } else {
            $this->export_excel($members);
        }
    }
    
    /**
     * Export CSV
     */
    private function export_csv($members) {
        $filename = 'members_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Header
        fputcsv($output, [
            'Member Code', 'First Name', 'Last Name', 'Phone', 'Email',
            'Date of Birth', 'Gender', 'Address', 'City', 'State',
            'Status', 'Join Date'
        ]);
        
        // Data
        foreach ($members as $m) {
            fputcsv($output, [
                $m->member_code,
                $m->first_name,
                $m->last_name,
                $m->phone,
                $m->email,
                $m->date_of_birth,
                $m->gender,
                $m->address_line1 . ' ' . $m->address_line2,
                $m->city,
                $m->state,
                $m->status,
                format_date($m->created_at, 'Y-m-d', '')
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Print Member Card
     */
    public function print_card($id = null) {
        if (!$id) { redirect('admin/members'); }
        $member = $this->Member_model->get_member_details($id);
        
        if (!$member) {
            $this->session->set_flashdata('error', 'Member not found.');
            redirect('admin/members');
        }
        
        $data['member'] = $member;
        $this->load->view('admin/members/print_card', $data);
    }

    /**
     * Send Email to Member
     */
    public function send_email($id = null) {
        if ($id) {
            // Send to specific member
            $member = $this->Member_model->get_member_details($id);
            if (!$member) {
                $this->session->set_flashdata('error', 'Member not found.');
                redirect('admin/members');
            }
            $data['member'] = $member;
            $data['bulk'] = false;
        } else {
            // Bulk email interface
            $data['bulk'] = true;
            $data['member'] = null;
        }

        $data['title'] = 'Send Email';
        $data['page_title'] = $id ? 'Send Email to Member' : 'Send Bulk Email';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Members', 'url' => 'admin/members'],
            ['title' => 'Send Email', 'url' => '']
        ];

        $this->load_view('admin/members/send_email', $data);
    }

    /**
     * Process Email Sending (AJAX)
     */
    public function process_send_email() {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $member_ids = $this->input->post('member_ids');
        $subject = $this->input->post('subject');
        $message = $this->input->post('message');

        if (empty($subject) || empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Subject and message are required']);
            return;
        }

        $sent_count = 0;
        $failed_count = 0;
        $errors = [];

        if (!empty($member_ids)) {
            // Send to specific members
            foreach ($member_ids as $member_id) {
                $member = $this->Member_model->get_member_details($member_id);
                if ($member && !empty($member->email)) {
                    $result = send_manual_member_email($member->email, $subject, $message);
                    if ($result['success']) {
                        $sent_count++;
                    } else {
                        $failed_count++;
                        $errors[] = "Failed to send to {$member->email}: {$result['message']}";
                    }
                } else {
                    $failed_count++;
                    $errors[] = "Member {$member_id} has no email address";
                }
            }
        } else {
            // Send to all members with email addresses
            $members = $this->Member_model->get_members_with_email();
            foreach ($members as $member) {
                $result = send_manual_member_email($member->email, $subject, $message);
                if ($result['success']) {
                    $sent_count++;
                } else {
                    $failed_count++;
                    $errors[] = "Failed to send to {$member->email}: {$result['message']}";
                }
                // Small delay for bulk emails
                usleep(100000); // 0.1 seconds
            }
        }

        echo json_encode([
            'success' => true,
            'sent' => $sent_count,
            'failed' => $failed_count,
            'errors' => $errors
        ]);
    }
}
