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
        
        $this->load_view('admin/members/index', $data);
    }
    
    /**
     * View Member
     */
    public function view($id) {
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
        
        $this->load_view('admin/members/create', $data);
    }
    
    /**
     * Store Member
     */
    public function store() {
        if ($this->input->method() !== 'post') {
            redirect('admin/members/create');
        }
        
        // Validation
        $this->load->library('form_validation');
        $this->form_validation->set_rules('first_name', 'First Name', 'required|max_length[100]');
        $this->form_validation->set_rules('last_name', 'Last Name', 'required|max_length[100]');
        $this->form_validation->set_rules('phone', 'Phone', 'required|numeric|min_length[10]|max_length[15]|is_unique[members.phone]');
        $this->form_validation->set_rules('email', 'Email', 'valid_email|is_unique[members.email]');
        $this->form_validation->set_rules('date_of_birth', 'Date of Birth', 'required');
        $this->form_validation->set_rules('gender', 'Gender', 'required|in_list[male,female,other]');
        
        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('admin/members/create');
        }
        
        // Prepare data
        $member_data = [
            'first_name' => $this->input->post('first_name', TRUE),
            'last_name' => $this->input->post('last_name', TRUE),
            'phone' => $this->input->post('phone', TRUE),
            'alternate_phone' => $this->input->post('alternate_phone', TRUE),
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
            'nominee_phone' => $this->input->post('nominee_phone', TRUE),
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
            $this->log_audit('members', $member_id, 'create', null, $member_data);
            $this->session->set_flashdata('success', 'Member created successfully.');
            redirect('admin/members/view/' . $member_id);
        } else {
            $this->session->set_flashdata('error', 'Failed to create member.');
            redirect('admin/members/create');
        }
    }
    
    /**
     * Edit Member Form
     */
    public function edit($id) {
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
    public function update($id) {
        if ($this->input->method() !== 'post') {
            redirect('admin/members/edit/' . $id);
        }
        
        $member = $this->Member_model->get_by_id($id);
        
        if (!$member) {
            $this->session->set_flashdata('error', 'Member not found.');
            redirect('admin/members');
        }
        
        // Validation
        $this->load->library('form_validation');
        $this->form_validation->set_rules('first_name', 'First Name', 'required|max_length[100]');
        $this->form_validation->set_rules('last_name', 'Last Name', 'required|max_length[100]');
        $this->form_validation->set_rules('phone', 'Phone', 'required|numeric|min_length[10]|max_length[15]');
        
        // Check phone uniqueness (excluding current member)
        $phone = $this->input->post('phone', TRUE);
        $existing = $this->db->where('phone', $phone)
                             ->where('id !=', $id)
                             ->count_all_results('members');
        if ($existing > 0) {
            $this->session->set_flashdata('error', 'Phone number already exists.');
            redirect('admin/members/edit/' . $id);
        }
        
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
            'last_name' => $this->input->post('last_name', TRUE),
            'phone' => $phone,
            'alternate_phone' => $this->input->post('alternate_phone', TRUE),
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
            'pan_number' => $pan,
            'aadhaar_number' => $aadhaar,
            'bank_name' => $this->input->post('bank_name', TRUE),
            'bank_account_number' => $this->input->post('bank_account_number', TRUE),
            'bank_ifsc' => strtoupper($this->input->post('bank_ifsc', TRUE)),
            'nominee_name' => $this->input->post('nominee_name', TRUE),
            'nominee_relationship' => $this->input->post('nominee_relationship', TRUE),
            'nominee_phone' => $this->input->post('nominee_phone', TRUE)
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

                $this->load->library('upload', $config);
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
            $this->log_audit('members', $id, 'update', $old_data, $member_data);
            $this->session->set_flashdata('success', 'Member updated successfully.');
        } else {
            $this->session->set_flashdata('error', 'Failed to update member.');
        }
        
        redirect('admin/members/view/' . $id);
    }
    
    /**
     * Update Status
     */
    public function update_status($id) {
        $member = $this->Member_model->get_by_id($id);
        
        if (!$member) {
            $this->json_response(['success' => false, 'message' => 'Member not found.']);
            return;
        }
        
        $status = $this->input->post('status');
        $reason = $this->input->post('reason');
        
        $result = $this->Member_model->update_status($id, $status, $reason, $this->session->userdata('admin_id'));
        
        if ($result) {
            $this->log_audit('members', $id, 'status_change', ['status' => $member->status], ['status' => $status, 'reason' => $reason]);
            $this->json_response(['success' => true, 'message' => 'Status updated successfully.']);
        } else {
            $this->json_response(['success' => false, 'message' => 'Failed to update status.']);
        }
    }
    
    /**
     * Verify KYC
     */
    public function verify_kyc($id) {
        $member = $this->Member_model->get_by_id($id);
        
        if (!$member) {
            $this->session->set_flashdata('error', 'Member not found.');
            redirect('admin/members');
        }
        
        $result = $this->Member_model->verify_kyc($id, $this->session->userdata('admin_id'));
        
        if ($result) {
            $this->log_audit('members', $id, 'kyc_verified');
            $this->session->set_flashdata('success', 'KYC verified successfully.');
        } else {
            $this->session->set_flashdata('error', 'Failed to verify KYC.');
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
    public function print_card($id) {
        $member = $this->Member_model->get_member_details($id);
        
        if (!$member) {
            $this->session->set_flashdata('error', 'Member not found.');
            redirect('admin/members');
        }
        
        $data['member'] = $member;
        $this->load->view('admin/members/print_card', $data);
    }
}
