<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/Member_Controller.php';

/**
 * Member Profile Controller
 */
class Profile extends Member_Controller {
    
    /**
     * View Profile
     */
    public function index() {
        $data['title'] = 'My Profile';
        $data['page_title'] = 'Profile Information';
        
        $this->load_member_view('member/profile/index', $data);
    }
    
    /**
     * Edit Profile
     */
    public function edit() {
        $data['title'] = 'Edit Profile';
        $data['page_title'] = 'Update Profile';
        
        if ($this->input->method() === 'post') {
            $this->_process_edit();
            return;
        }
        
        $this->load_member_view('member/profile/edit', $data);
    }
    
    /**
     * Change Password
     */
    public function change_password() {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('current_password', 'Current Password', 'required');
        $this->form_validation->set_rules('new_password', 'New Password', 'required|min_length[6]');
        $this->form_validation->set_rules('confirm_password', 'Confirm Password', 'required|matches[new_password]');
        
        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('member/profile');
            return;
        }
        
        $current_password = $this->input->post('current_password');
        $new_password = $this->input->post('new_password');
        
        // Verify current password
        if (!password_verify($current_password, $this->member->password ?? '') && $current_password !== $this->member->member_code) {
            $this->session->set_flashdata('error', 'Current password is incorrect.');
            redirect('member/profile');
            return;
        }
        
        // Update password
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        if ($this->db->where('id', $this->member->id)->update('members', ['password' => $hashed])) {
            $this->session->set_flashdata('success', 'Password updated successfully.');
        } else {
            $this->session->set_flashdata('error', 'Failed to update password.');
        }
        
        redirect('member/profile');
    }
    
    /**
     * Process Profile Edit
     */
    private function _process_edit() {
        // Debug: Check if method is called
        log_message('debug', 'Profile _process_edit called for member ID: ' . ($this->member->id ?? 'NULL') . ' Method: ' . $this->input->method());
        log_message('debug', 'POST data: ' . print_r($this->input->post(), true));
        
        if (!$this->member || !$this->member->id) {
            $this->session->set_flashdata('error', 'Member data not found. Please login again.');
            redirect('member/auth/login');
            return;
        }
        
        $this->load->library('form_validation');
        $this->form_validation->set_rules('first_name', 'First Name', 'required|trim');
        $this->form_validation->set_rules('last_name', 'Last Name', 'required|trim');
        $this->form_validation->set_rules('phone', 'Phone', 'required|numeric|min_length[10]|max_length[15]');
        $this->form_validation->set_rules('email', 'Email', 'valid_email|trim');
        $this->form_validation->set_rules('address_line1', 'Address', 'required|trim');
        $this->form_validation->set_rules('pincode', 'Pincode', 'trim|numeric');
        // Date of birth must be provided and member should be at least 18 years
        $this->form_validation->set_rules('date_of_birth', 'Date of Birth', 'required|callback_validate_age');

        // Normalize phone before validation and check uniqueness
        $this->load->helper('format_helper');
        $raw_phone = $this->input->post('phone');
        $normalized_phone = normalize_phone($raw_phone);
        if ($normalized_phone !== null) {
            $_POST['phone'] = $normalized_phone;
        } else {
            // Let validation rule catch missing/invalid phone
            $_POST['phone'] = '';
        }



        if ($this->form_validation->run() === FALSE) {
            log_message('debug', 'Validation failed: ' . validation_errors());
            $this->session->set_flashdata('error', validation_errors());
            redirect('member/profile/edit');
            return;
        }
        
        log_message('debug', 'Validation passed, processing update');

        // Map form fields to DB columns (include commonly admin-updated fields)
        // Sanitize and validate Aadhaar/PAN
        $aadhaar_raw = $this->input->post('aadhaar_number');
        $pan_raw = $this->input->post('pan_number');

        $this->load->helper('format_helper');
        $aadhaar = sanitize_aadhaar($aadhaar_raw);
        $pan = sanitize_pan($pan_raw);

        if ($aadhaar && !validate_aadhaar($aadhaar)) {
            $this->session->set_flashdata('error', 'Aadhaar number is invalid. It must be 12 digits.');
            redirect('member/profile/edit');
            return;
        }

        if ($pan && !validate_pan($pan)) {
            $this->session->set_flashdata('error', 'PAN number is invalid. Format: AAAAA9999A');
            redirect('member/profile/edit');
            return;
        }

        $update_data = [
            'first_name' => $this->input->post('first_name'),
            'last_name' => $this->input->post('last_name'),
            'father_name' => $this->input->post('father_name'),
            'date_of_birth' => $this->input->post('date_of_birth') ?: null,
            'gender' => $this->input->post('gender'),
            'email' => $this->input->post('email'),
            'phone' => normalize_phone($this->input->post('phone')),
            'alternate_phone' => normalize_phone($this->input->post('alternate_phone')),
            'address_line1' => $this->input->post('address_line1'),
            'address_line2' => $this->input->post('address_line2'),
            'city' => $this->input->post('city'),
            'state' => $this->input->post('state'),
            'pincode' => $this->input->post('pincode'),
            'aadhaar_number' => $aadhaar,
            'pan_number' => $pan,
            'voter_id' => $this->input->post('voter_id'),
            'bank_name' => $this->input->post('bank_name'),
            'bank_branch' => $this->input->post('bank_branch'),
            'account_number' => $this->input->post('account_number'),
            'ifsc_code' => $this->input->post('ifsc_code'),
            'account_holder_name' => $this->input->post('account_holder_name'),
            'nominee_name' => $this->input->post('nominee_name'),
            'nominee_relation' => $this->input->post('nominee_relation'),
            'nominee_phone' => normalize_phone($this->input->post('nominee_phone')),
            'nominee_aadhaar' => $this->input->post('nominee_aadhaar'),
            'notes' => $this->input->post('notes')
        ];

        // Handle document uploads
        $upload_base = './members/uploads/' . $this->member->id . '/';
        if (!is_dir($upload_base)) mkdir($upload_base, 0755, TRUE);

        $doc_fields = [
            'photo' => 'profile_photo',
            'aadhaar_doc' => 'aadhaar_doc',
            'pan_doc' => 'pan_doc',
            'address_proof_doc' => 'address_proof'
        ];

        foreach ($doc_fields as $field => $input_name) {
            if (!empty($_FILES[$input_name]['name'])) {
                $config['upload_path'] = $upload_base;
                $config['allowed_types'] = 'jpg|jpeg|png';
                $config['max_size'] = 2048; // 2MB for photos
                $config['file_name'] = $field . '_' . $this->member->id . '_' . time();

                // For documents, allow PDF too
                if ($field !== 'photo') {
                    $config['allowed_types'] = 'jpg|jpeg|png|pdf';
                    $config['max_size'] = 4096; // 4MB for documents
                }

                $this->load->library('upload', $config);
                if ($this->upload->do_upload($input_name)) {
                    $upload_data = $this->upload->data();
                    // Delete old file if exists
                    if (!empty($this->member->{$field}) && file_exists($upload_base . $this->member->{$field})) {
                        @unlink($upload_base . $this->member->{$field});
                    }
                    $update_data[$field] = $upload_data['file_name'];
                } else {
                    $this->session->set_flashdata('error', 'File upload failed for ' . $input_name . ': ' . $this->upload->display_errors('', ''));
                    redirect('member/profile/edit');
                    return;
                }
            }
        }

        if ($this->db->where('id', $this->member->id)->update('members', $update_data)) {
            // Debug: Update successful
            log_message('debug', 'Profile update successful for member ID: ' . $this->member->id);
            
            // Update the session member data
            $this->member = $this->Member_model->get_by_id($this->member->id);
            $this->session->set_userdata('member_data', $this->member);
            
            $this->session->set_flashdata('success', 'Profile updated successfully.');
            redirect('member/profile');
        } else {
            $error = $this->db->error();
            log_message('error', 'Profile update failed for member ID: ' . $this->member->id . ' - Error: ' . $error['message']);
            $this->session->set_flashdata('error', 'Failed to update profile: ' . $error['message']);
            redirect('member/profile/edit');
        }
    }

    /**
     * Validate age - must be at least 18 years for members
     */
    public function validate_age($dob) {
        $this->load->helper('age_helper');

        if (empty($dob) || !is_age_at_least($dob, 18)) {
            $this->form_validation->set_message('validate_age', 'the age of the member should be greater than 18 years.');
            return FALSE;
        }

        return TRUE;
    }
}

