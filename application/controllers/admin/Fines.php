<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Fines Controller - Fine & Penalty Management
 */
class Fines extends Admin_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model(['Fine_model', 'Member_model']);
    }
    
    /**
     * List Fines
     */
    public function index() {
        $data['title'] = 'Fines & Penalties';
        $data['page_title'] = 'Fine Management';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Fines', 'url' => '']
        ];
        
        $status = $this->input->get('status') ?: 'pending';
        
        $this->db->select('f.*, m.member_code, m.first_name, m.last_name, m.phone');
        $this->db->from('fines f');
        $this->db->join('members m', 'm.id = f.member_id');
        
        if ($status !== 'all') {
            if ($status === 'pending') {
                $this->db->where_in('f.status', ['pending', 'partial']);
            } else {
                $this->db->where('f.status', $status);
            }
        }
        
        $this->db->order_by('f.fine_date', 'DESC');
        
        $data['fines'] = $this->db->get()->result();
        $data['status'] = $status;
        $data['summary'] = $this->Fine_model->get_summary();
        
        $this->load_view('admin/fines/index', $data);
    }
    
    /**
     * View Fine Details
     */
    public function view($id) {
        $fine = $this->db->select('f.*, m.member_code, m.first_name, m.last_name, m.phone, fr.rule_name')
                         ->from('fines f')
                         ->join('members m', 'm.id = f.member_id')
                         ->join('fine_rules fr', 'fr.id = f.fine_rule_id', 'left')
                         ->where('f.id', $id)
                         ->get()
                         ->row();
        
        if (!$fine) {
            $this->session->set_flashdata('error', 'Fine not found.');
            redirect('admin/fines');
        }
        
        $data['title'] = 'Fine Details';
        $data['page_title'] = 'Fine Details';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Fines', 'url' => 'admin/fines'],
            ['title' => $fine->fine_code, 'url' => '']
        ];
        
        $data['fine'] = $fine;
        
        $this->load_view('admin/fines/view', $data);
    }
    
    /**
     * Create Manual Fine
     */
    public function create() {
        $data['title'] = 'Create Fine';
        $data['page_title'] = 'Create Manual Fine';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Fines', 'url' => 'admin/fines'],
            ['title' => 'Create', 'url' => '']
        ];
                $data['members'] = $this->Member_model->get_active_members_dropdown();
                if ($member_id = $this->input->get('member_id')) {
            $data['selected_member'] = $this->Member_model->get_by_id($member_id);
        }
        
        $this->load_view('admin/fines/create', $data);
    }
    
    /**
     * Store Fine
     */
    public function store() {
        if ($this->input->method() !== 'post') {
            redirect('admin/fines/create');
        }
        
        $this->load->library('form_validation');
        $this->form_validation->set_rules('member_id', 'Member', 'required|numeric');
        $this->form_validation->set_rules('fine_type', 'Fine Type', 'required');
        $this->form_validation->set_rules('fine_amount', 'Fine Amount', 'required|numeric|greater_than[0]');
        
        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('admin/fines/create');
        }
        
        $fine_data = [
            'member_id' => $this->input->post('member_id'),
            'fine_type' => $this->input->post('fine_type'),
            'fine_date' => $this->input->post('fine_date') ?: date('Y-m-d'),
            'fine_amount' => $this->input->post('fine_amount'),
            'remarks' => $this->input->post('remarks'),
            'created_by' => $this->session->userdata('admin_id')
        ];
        
        $fine_id = $this->Fine_model->create_fine($fine_data);
        
        if ($fine_id) {
            $this->log_audit('fines', $fine_id, 'create', null, $fine_data);
            $this->session->set_flashdata('success', 'Fine created successfully.');
            redirect('admin/fines/view/' . $fine_id);
        } else {
            $this->session->set_flashdata('error', 'Failed to create fine.');
            redirect('admin/fines/create');
        }
    }
    
    /**
     * Collect Fine Payment
     */
    public function collect($id) {
        $fine = $this->Fine_model->get_by_id($id);
        
        if (!$fine) {
            $this->session->set_flashdata('error', 'Fine not found.');
            redirect('admin/fines');
        }
        
        if ($this->input->method() === 'post') {
            $amount = $this->input->post('amount');
            $payment_mode = $this->input->post('payment_mode');
            $reference = $this->input->post('reference_number');
            
            $result = $this->Fine_model->record_payment(
                $id, 
                $amount, 
                $payment_mode, 
                $reference, 
                $this->session->userdata('admin_id')
            );
            
            if ($result) {
                // Post to ledger
                $this->load->model('Ledger_model');
                $this->Ledger_model->post_transaction(
                    'fine_income',
                    $id,
                    $amount,
                    $fine->member_id,
                    'Fine payment: ' . $fine->fine_code,
                    $this->session->userdata('admin_id')
                );
                
                $this->log_audit('fines', $id, 'payment', null, ['amount' => $amount]);
                $this->session->set_flashdata('success', 'Payment recorded successfully.');
            } else {
                $this->session->set_flashdata('error', 'Failed to record payment.');
            }
            
            redirect('admin/fines/view/' . $id);
        }
        
        $data['title'] = 'Collect Fine';
        $data['page_title'] = 'Collect Fine Payment';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Fines', 'url' => 'admin/fines'],
            ['title' => 'Collect', 'url' => '']
        ];
        
        $data['fine'] = $fine;
        $data['member'] = $this->Member_model->get_by_id($fine->member_id);
        
        $this->load_view('admin/fines/collect', $data);
    }
    
    /**
     * Waive Fine
     */
    public function waive($id) {
        $fine = $this->Fine_model->get_by_id($id);
        
        if (!$fine) {
            $this->json_response(['success' => false, 'message' => 'Fine not found.']);
            return;
        }
        
        $waive_amount = $this->input->post('amount');
        $reason = $this->input->post('reason');
        
        if (!$reason) {
            $this->json_response(['success' => false, 'message' => 'Waiver reason is required.']);
            return;
        }
        
        $result = $this->Fine_model->waive_fine($id, $waive_amount, $reason, $this->session->userdata('admin_id'));
        
        if ($result) {
            $this->log_audit('fines', $id, 'waived', null, ['amount' => $waive_amount, 'reason' => $reason]);
            $this->json_response(['success' => true, 'message' => 'Fine waived successfully.']);
        } else {
            $this->json_response(['success' => false, 'message' => 'Failed to waive fine.']);
        }
    }
    
    /**
     * Cancel Fine
     */
    public function cancel($id) {
        $reason = $this->input->post('reason');
        
        if (!$reason) {
            $this->json_response(['success' => false, 'message' => 'Cancellation reason is required.']);
            return;
        }
        
        $result = $this->Fine_model->cancel_fine($id, $reason, $this->session->userdata('admin_id'));
        
        if ($result) {
            $this->log_audit('fines', $id, 'cancelled', null, ['reason' => $reason]);
            $this->json_response(['success' => true, 'message' => 'Fine cancelled successfully.']);
        } else {
            $this->json_response(['success' => false, 'message' => 'Failed to cancel fine.']);
        }
    }
    
    /**
     * Fine Rules
     */
    public function rules() {
        $data['title'] = 'Fine Rules';
        $data['page_title'] = 'Manage Fine Rules';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Fines', 'url' => 'admin/fines'],
            ['title' => 'Rules', 'url' => '']
        ];
        
        $data['rules'] = $this->Fine_model->get_rules();
        
        $this->load_view('admin/fines/rules', $data);
    }
    
    /**
     * Run Auto Fine Job
     */
    public function run_auto_fines() {
        $applied = $this->Fine_model->run_late_fine_job($this->session->userdata('admin_id'));
        
        $this->log_activity('admin', $this->session->userdata('admin_id'), 'Ran auto-fine job', 'Applied ' . $applied . ' fines');
        
        $this->session->set_flashdata('success', $applied . ' fines applied automatically.');
        redirect('admin/fines');
    }
    
    /**
     * Save Fine Rule (AJAX)
     */
    public function save_rule() {
        if ($this->input->method() !== 'post') {
            $this->json_response(['success' => false, 'message' => 'Invalid request']);
            return;
        }
        
        $id = $this->input->post('id');
        
        $data = [
            'rule_name' => $this->input->post('rule_name'),
            'fine_type' => $this->input->post('fine_type'),
            'description' => $this->input->post('description'),
            'amount_type' => $this->input->post('amount_type'),
            'amount_value' => $this->input->post('amount_value'),
            'frequency' => $this->input->post('frequency'),
            'grace_days' => $this->input->post('grace_days') ?: 0,
            'max_fine_amount' => $this->input->post('max_fine_amount') ?: null,
            'applies_to' => $this->input->post('applies_to'),
            'is_active' => $this->input->post('is_active') ? 1 : 0
        ];
        
        if (empty($data['rule_name'])) {
            $this->json_response(['success' => false, 'message' => 'Rule name is required']);
            return;
        }
        
        if ($id) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', $id)->update('fine_rules', $data);
            $this->log_audit('fine_rules', $id, 'update', null, $data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['created_by'] = $this->session->userdata('admin_id');
            $this->db->insert('fine_rules', $data);
            $id = $this->db->insert_id();
            $this->log_audit('fine_rules', $id, 'create', null, $data);
        }
        
        $this->json_response(['success' => true, 'message' => 'Rule saved successfully']);
    }
    
    /**
     * Toggle Rule Status (AJAX)
     */
    public function toggle_rule_status() {
        $id = $this->input->post('id');
        $is_active = $this->input->post('is_active');
        
        if (!$id) {
            $this->json_response(['success' => false, 'message' => 'Invalid rule']);
            return;
        }
        
        $this->db->where('id', $id)->update('fine_rules', [
            'is_active' => $is_active,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        $this->log_audit('fine_rules', $id, 'status_change', null, ['is_active' => $is_active]);
        $this->json_response(['success' => true]);
    }
    
    /**
     * Delete Rule (AJAX)
     */
    public function delete_rule() {
        $id = $this->input->post('id');
        
        if (!$id) {
            $this->json_response(['success' => false, 'message' => 'Invalid rule']);
            return;
        }
        
        // Check if rule is in use
        $in_use = $this->db->where('fine_rule_id', $id)->count_all_results('fines');
        
        if ($in_use > 0) {
            $this->json_response(['success' => false, 'message' => 'Cannot delete rule that is in use']);
            return;
        }
        
        $this->db->where('id', $id)->delete('fine_rules');
        $this->log_audit('fine_rules', $id, 'delete', null, null);
        
        $this->json_response(['success' => true, 'message' => 'Rule deleted successfully']);
    }
}
