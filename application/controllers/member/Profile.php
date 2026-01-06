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
        $this->load->library('form_validation');
        $this->form_validation->set_rules('phone', 'Phone', 'required|numeric|exact_length[10]');
        $this->form_validation->set_rules('email', 'Email', 'valid_email');
        $this->form_validation->set_rules('address_line1', 'Address', 'required');
        $this->form_validation->set_rules('pincode', 'Pincode', 'numeric');

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('member/profile/edit');
            return;
        }

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
            'phone' => $this->input->post('phone'),
            'email' => $this->input->post('email'),
            'father_name' => $this->input->post('father_name'),
            'date_of_birth' => $this->input->post('date_of_birth') ?: null,
            'gender' => $this->input->post('gender'),
            'address_line1' => $this->input->post('address_line1'),
            'address_line2' => $this->input->post('address_line2'),
            'city' => $this->input->post('city'),
            'state' => $this->input->post('state'),
            'pincode' => $this->input->post('pincode'),
            'aadhaar_number' => $aadhaar,
            'pan_number' => $pan,
            'bank_name' => $this->input->post('bank_name'),
            'bank_branch' => $this->input->post('bank_branch'),
            'account_number' => $this->input->post('account_number'),
            'ifsc_code' => $this->input->post('ifsc_code'),
            'account_holder_name' => $this->input->post('account_holder_name'),
            'nominee_name' => $this->input->post('nominee_name'),
            'nominee_relation' => $this->input->post('nominee_relation'),
            'nominee_phone' => $this->input->post('nominee_phone'),
            'notes' => $this->input->post('notes')
        ];

        // Handle document uploads
        $upload_base = './uploads/members_docs/' . $this->member->id . '/';
        if (!is_dir($upload_base)) mkdir($upload_base, 0755, TRUE);

        $doc_fields = [
            'aadhaar_doc' => 'aadhaar_doc',
            'pan_doc' => 'pan_doc',
            'address_proof_doc' => 'address_proof'
        ];

        foreach ($doc_fields as $field => $input_name) {
            if (!empty($_FILES[$input_name]['name'])) {
                $config['upload_path'] = $upload_base;
                $config['allowed_types'] = 'jpg|jpeg|png|pdf';
                $config['max_size'] = 4096;
                $config['file_name'] = $field . '_' . $this->member->id . '_' . time();

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
            $this->session->set_flashdata('success', 'Profile updated successfully.');
            redirect('member/profile');
        } else {
            $this->session->set_flashdata('error', 'Failed to update profile.');
            redirect('member/profile/edit');
        }
    }
}
