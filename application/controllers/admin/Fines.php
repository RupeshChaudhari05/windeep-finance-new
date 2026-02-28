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
        
        // Check if status is specified in URI (e.g., /admin/fines/pending)
        $uri_status = $this->uri->segment(3); // Gets 'pending', 'paid', etc.
        $valid_statuses = ['pending', 'paid', 'waived', 'cancelled', 'partial', 'all'];
        
        if ($uri_status && in_array($uri_status, $valid_statuses)) {
            $status = $uri_status;
        } else {
            $status = $this->input->get('status') ?: 'pending';
        }
        
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
        
        $fines = $this->db->get()->result();
        // Normalize fines for view compatibility
        foreach ($fines as $idx => $f) {
            // Construct member name
            $f->member_name = trim(($f->first_name ?? '') . ' ' . ($f->last_name ?? '')) ?: ($f->member_name ?? '');
            $f->member_code = $f->member_code ?? '';
            $f->paid_amount = isset($f->paid_amount) ? (float) $f->paid_amount : 0.0;
            $f->balance_amount = isset($f->balance_amount) ? (float) $f->balance_amount : (isset($f->fine_amount) ? (float) $f->fine_amount : 0.0);
            $f->fine_amount = isset($f->fine_amount) ? (float) $f->fine_amount : 0.0;
            $fines[$idx] = $f;
        }
        $data['fines'] = $fines;
        $data['status'] = $status;
        
        // Filters for the view (used by the search form and links)
        $data['filters'] = [
            'search' => $this->input->get('search') ?: '',
            'type' => $this->input->get('type') ?: '',
            'status' => $this->input->get('status') ?: $status,
            'page' => (int) ($this->input->get('page') ?: 1)
        ];
        
        // Normalize summary (Fine_model returns an object with different keys)
        $raw_summary = $this->Fine_model->get_summary();
        $data['summary'] = [
            'pending_amount' => (float) ($raw_summary->total_balance ?? 0),
            'collected_amount' => (float) ($raw_summary->total_paid ?? 0),
            'waived_amount' => (float) ($raw_summary->total_waived ?? 0),
            'pending_count' => (int) ($raw_summary->total_fines ?? 0)
        ];
        
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
     * AJAX: Get fine calculation breakdown for modal
     */
    public function get_fine_detail($id) {
        $fine = $this->db->select('f.*, m.member_code, m.first_name, m.last_name, m.phone')
                         ->select('fr.rule_name, fr.calculation_type, fr.fine_value as rule_fine_value, fr.per_day_amount as rule_per_day_amount, fr.grace_period_days, fr.max_fine_amount as rule_max_fine, fr.applies_to, fr.fine_type as rule_fine_type')
                         ->from('fines f')
                         ->join('members m', 'm.id = f.member_id')
                         ->join('fine_rules fr', 'fr.id = f.fine_rule_id', 'left')
                         ->where('f.id', $id)
                         ->get()
                         ->row();
        
        if (!$fine) {
            $this->json_response(['success' => false, 'message' => 'Fine not found']);
            return;
        }
        
        // Build calculation breakdown
        $grace = (int)($fine->grace_period_days ?? 0);
        $days_late = (int)$fine->days_late;
        $effective_days = max(0, $days_late - $grace);
        $calc_type = $fine->calculation_type ?? 'fixed';
        $rule_value = (float)($fine->rule_fine_value ?? 0);
        $per_day = (float)($fine->rule_per_day_amount ?? 0);
        $max_cap = (float)($fine->rule_max_fine ?? 0);
        
        // FINE-4 FIX: Look up the base due amount from the related entity for percentage calculation
        $base_due_amount = 0;
        if (isset($fine->related_type) && isset($fine->related_id)) {
            if ($fine->related_type === 'loan_installment') {
                $related = $this->db->select('emi_amount')->where('id', $fine->related_id)->get('loan_installments')->row();
                $base_due_amount = $related ? (float) $related->emi_amount : 0;
            } elseif ($fine->related_type === 'savings_schedule') {
                $related = $this->db->select('due_amount')->where('id', $fine->related_id)->get('savings_schedule')->row();
                $base_due_amount = $related ? (float) $related->due_amount : 0;
            }
        }

        // Recalculate step by step
        $steps = [];
        $steps[] = ['label' => 'Due Date', 'value' => format_date($fine->due_date)];
        $steps[] = ['label' => 'Fine Date', 'value' => format_date($fine->fine_date)];
        $steps[] = ['label' => 'Total Days Late', 'value' => $days_late . ' days'];
        $steps[] = ['label' => 'Grace Period', 'value' => $grace . ' days'];
        $steps[] = ['label' => 'Effective Overdue Days', 'value' => $effective_days . ' days (= ' . $days_late . ' - ' . $grace . ')'];
        
        $correct_amount = 0;
        if ($calc_type === 'fixed') {
            $correct_amount = $rule_value;
            $steps[] = ['label' => 'Calculation Type', 'value' => 'Fixed Amount'];
            $steps[] = ['label' => 'Fixed Fine', 'value' => format_amount($rule_value)];
        } elseif ($calc_type === 'percentage') {
            $correct_amount = $base_due_amount * ($rule_value / 100);
            $steps[] = ['label' => 'Calculation Type', 'value' => 'Percentage'];
            $steps[] = ['label' => 'Rate', 'value' => $rule_value . '%'];
            $steps[] = ['label' => 'Base Due Amount', 'value' => format_amount($base_due_amount)];
            $steps[] = ['label' => 'Calculation', 'value' => format_amount($base_due_amount) . ' x ' . $rule_value . '% = ' . format_amount($correct_amount)];
        } elseif ($calc_type === 'per_day') {
            $steps[] = ['label' => 'Calculation Type', 'value' => 'Per Day (Fixed + Daily)'];
            $steps[] = ['label' => 'Initial Fixed Amount', 'value' => format_amount($rule_value)];
            $steps[] = ['label' => 'Per Day Rate', 'value' => format_amount($per_day) . '/day'];
            
            if ($effective_days > 0) {
                $daily_portion = $per_day * max(0, $effective_days - 1);
                $correct_amount = $rule_value + $daily_portion;
                $steps[] = ['label' => 'Calculation', 'value' => format_amount($rule_value) . ' + (' . format_amount($per_day) . ' x ' . max(0, $effective_days - 1) . ' days) = ' . format_amount($correct_amount)];
            }
        }
        
        if ($max_cap > 0) {
            $steps[] = ['label' => 'Max Cap', 'value' => format_amount($max_cap)];
            if ($correct_amount > $max_cap) {
                $correct_amount = $max_cap;
                $steps[] = ['label' => 'Capped Amount', 'value' => format_amount($max_cap) . ' (cap applied)'];
            }
        }
        
        $steps[] = ['label' => 'Correct Fine Amount', 'value' => format_amount($correct_amount), 'highlight' => true];
        $steps[] = ['label' => 'Current Fine (in DB)', 'value' => format_amount($fine->fine_amount), 'highlight' => true];
        
        $is_correct = abs($correct_amount - (float)$fine->fine_amount) < 0.02;
        
        $this->json_response([
            'success' => true,
            'fine' => [
                'id' => $fine->id,
                'fine_code' => $fine->fine_code,
                'member_name' => trim($fine->first_name . ' ' . $fine->last_name),
                'member_code' => $fine->member_code,
                'fine_type' => ucfirst(str_replace('_', ' ', $fine->fine_type)),
                'rule_name' => $fine->rule_name ?: 'N/A',
                'fine_date' => format_date($fine->fine_date),
                'due_date' => format_date($fine->due_date),
                'days_late' => $days_late,
                'fine_amount' => (float)$fine->fine_amount,
                'paid_amount' => (float)$fine->paid_amount,
                'balance_amount' => (float)$fine->balance_amount,
                'status' => $fine->status,
                'remarks' => $fine->remarks,
                'is_correct' => $is_correct,
                'correct_amount' => $correct_amount
            ],
            'steps' => $steps
        ]);
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
            $this->log_audit('create', 'fines', 'fines', $fine_id, null, $fine_data);
            $this->session->set_flashdata('success', 'Fine created successfully.');
            redirect('admin/fines/view/' . $fine_id);
        } else {
            $this->session->set_flashdata('error', 'The fine could not be recorded. Please verify the member and amount, then try again.');
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
            $amount = (float) $this->input->post('amount');
            $payment_mode = $this->input->post('payment_mode');
            $reference = $this->input->post('reference_number');

            // FINE-1 FIX: Validate payment amount
            if ($amount <= 0) {
                $this->session->set_flashdata('error', 'Payment amount must be greater than zero.');
                redirect('admin/fines/collect/' . $id);
            }
            if ($amount > $fine->balance_amount) {
                $this->session->set_flashdata('error', 'Payment amount (' . number_format($amount, 2) . ') exceeds outstanding balance (' . number_format($fine->balance_amount, 2) . ').');
                redirect('admin/fines/collect/' . $id);
            }

            // FINE-3 FIX: Wrap payment + GL posting in a single transaction
            $this->db->trans_begin();

            $result = $this->Fine_model->record_payment(
                $id, 
                $amount, 
                $payment_mode, 
                $reference, 
                $this->session->userdata('admin_id')
            );
            
            if ($result) {
                // Post to ledger — now inside the same transaction
                $this->load->model('Ledger_model');
                $this->Ledger_model->post_transaction(
                    'fine_income',
                    $id,
                    $amount,
                    $fine->member_id,
                    'Fine payment: ' . $fine->fine_code,
                    $this->session->userdata('admin_id')
                );
                
                if ($this->db->trans_status() === FALSE) {
                    $this->db->trans_rollback();
                    $this->session->set_flashdata('error', 'Fine payment or ledger posting failed. Transaction rolled back.');
                } else {
                    $this->db->trans_commit();
                    $this->log_audit('payment', 'fines', 'fines', $id, null, ['amount' => $amount]);
                    $this->session->set_flashdata('success', 'Payment recorded successfully.');
                }
            } else {
                $this->db->trans_rollback();
                $this->session->set_flashdata('error', 'Fine payment could not be recorded. Please check the payment amount and try again.');
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
            $this->log_audit('waived', 'fines', 'fines', $id, null, ['amount' => $waive_amount, 'reason' => $reason]);
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
            $this->log_audit('cancelled', 'fines', 'fines', $id, null, ['reason' => $reason]);
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
     * List waiver requests
     */
    public function waiver_requests() {
        $data['title'] = 'Waiver Requests';
        $data['page_title'] = 'Waiver Requests';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Fines', 'url' => 'admin/fines'],
            ['title' => 'Waiver Requests', 'url' => '']
        ];

        $this->load->model('Fine_model');
        $data['requests'] = $this->Fine_model->get_waiver_requests();

        $this->load_view('admin/fines/waiver_requests', $data);
    }

    /**
     * Request a waiver for a fine (AJAX)
     */
    public function request_waiver($id) {
        if ($this->input->method() !== 'post') {
            $this->json_response(['success' => false, 'message' => 'Invalid request']);
            return;
        }

        $reason = $this->input->post('reason');
        $requested_by = $this->session->userdata('admin_id');
        $amount = $this->input->post('amount');

        if (!$reason) {
            $this->json_response(['success' => false, 'message' => 'Reason is required']);
            return;
        }

        $result = $this->Fine_model->request_waiver($id, $reason, $requested_by, $amount);
        if ($result) {
            $this->log_audit('waiver_requested', 'fines', 'fines', $id, null, ['reason' => $reason]);
            $this->json_response(['success' => true, 'message' => 'Waiver request submitted']);
        } else {
            $this->json_response(['success' => false, 'message' => 'Failed to submit waiver request']);
        }
    }

    /**
     * Approve waiver (AJAX)
     */
    public function approve_waiver($id) {
        if ($this->input->method() !== 'post') {
            $this->json_response(['success' => false, 'message' => 'Invalid request']);
            return;
        }

        $amount = $this->input->post('amount');
        $approved_by = $this->session->userdata('admin_id');

        if (!$amount || $amount <= 0) {
            $this->json_response(['success' => false, 'message' => 'Invalid amount']);
            return;
        }

        $result = $this->Fine_model->approve_waiver($id, $amount, $approved_by);

        if ($result) {
            $this->log_audit('waiver_approved', 'fines', 'fines', $id, null, ['amount' => $amount]);
            $this->json_response(['success' => true, 'message' => 'Waiver approved successfully.']);
        } else {
            $this->json_response(['success' => false, 'message' => 'Failed to approve waiver']);
        }
    }

    /**
     * Deny waiver (AJAX)
     */
    public function deny_waiver($id) {
        if ($this->input->method() !== 'post') {
            $this->json_response(['success' => false, 'message' => 'Invalid request']);
            return;
        }

        $reason = $this->input->post('reason');
        $denied_by = $this->session->userdata('admin_id');

        if (!$reason) {
            $this->json_response(['success' => false, 'message' => 'Denial reason is required']);
            return;
        }

        $result = $this->Fine_model->deny_waiver($id, $denied_by, $reason);

        if ($result) {
            $this->log_audit('waiver_denied', 'fines', 'fines', $id, null, ['reason' => $reason]);
            $this->json_response(['success' => true, 'message' => 'Waiver denied successfully.']);
        } else {
            $this->json_response(['success' => false, 'message' => 'Failed to deny waiver']);
        }
    }
    
    /**
     * Run Auto Fine Job
     */
    public function run_auto_fines() {
        $applied = $this->Fine_model->run_late_fine_job($this->session->userdata('admin_id'));
        
        $this->log_activity('Ran auto-fine job', 'Applied ' . $applied . ' fines', 'fines');
        
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
        $admin_id = $this->session->userdata('admin_id');

        // Map incoming form fields to schema-compatible columns
        $fine_type = $this->input->post('fine_type') ?: 'fixed';
        $rule_data = [
            'rule_name' => $this->input->post('rule_name'),
            'applies_to' => $this->input->post('applies_to') ?: 'both',
            'fine_type' => $fine_type,
            'calculation_type' => $fine_type, // Always keep in sync with fine_type
            'fine_value' => $this->input->post('amount_value') ?: ($this->input->post('fine_amount') ?: 0),
            'per_day_amount' => $this->input->post('per_day_amount') ?: 0,
            'grace_period_days' => $this->input->post('grace_days') ?: ($this->input->post('grace_period') ?: 0),
            'max_fine_amount' => $this->input->post('max_fine_amount') ?: null,
            'description' => $this->input->post('description'),
            'is_active' => $this->input->post('is_active') ? 1 : 0,
        ];

        if (empty($rule_data['rule_name'])) {
            $this->json_response(['success' => false, 'message' => 'Rule name is required']);
            return;
        }

        if ($id) {
            // Editing existing rule: changes apply from 1st of next month
            $effective_from = date('Y-m-01', strtotime('first day of next month'));
            $rule_data['effective_from'] = $effective_from;
            $rule_data['updated_by'] = $admin_id;
            $rule_data['updated_at'] = date('Y-m-d H:i:s');
            $old = $this->db->where('id', $id)->get('fine_rules')->row();
            $this->db->where('id', $id)->update('fine_rules', $rule_data);
            $this->log_audit('update', 'fine_rules', 'fine_rules', $id, (array)$old, $rule_data);
            $this->json_response([
                'success' => true, 
                'message' => 'Rule updated. Changes effective from ' . format_date($effective_from)
            ]);
        } else {
            // New rule: effective immediately
            $rule_data['effective_from'] = date('Y-m-d');
            $rule_data['rule_code'] = $this->generate_rule_code();
            $rule_data['created_by'] = $admin_id;
            $this->db->insert('fine_rules', $rule_data);
            $id = $this->db->insert_id();
            $this->log_audit('create', 'fine_rules', 'fine_rules', $id, null, $rule_data);
            $this->json_response(['success' => true, 'message' => 'Rule created and active immediately']);
        }
    }
    
    /**     * Generate unique rule code
     */
    private function generate_rule_code() {
        $year = date('Y');
        
        // Get or create sequence
        $seq = $this->db->where('year', $year)->get('rule_code_sequence')->row();
        
        if (!$seq) {
            $this->db->insert('rule_code_sequence', [
                'prefix' => 'FR',
                'current_number' => 0,
                'year' => $year
            ]);
            $seq = $this->db->where('year', $year)->get('rule_code_sequence')->row();
        }
        
        // Increment
        $next_number = $seq->current_number + 1;
        $this->db->where('id', $seq->id)->update('rule_code_sequence', ['current_number' => $next_number]);
        
        return $seq->prefix . '-' . $year . '-' . str_pad($next_number, 4, '0', STR_PAD_LEFT);
    }
    
    /**     * Toggle Rule Status (AJAX)
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
        $this->log_audit('delete', 'fine_rules', 'fine_rules', $id, null, null);
        
        $this->json_response(['success' => true, 'message' => 'Rule deleted successfully']);
    }
    
    /**
     * Recalculate all pending/partial fines using correct formula
     */
    public function recalculate_all() {
        $admin_id = $this->session->userdata('admin_id');
        
        // Get all pending/partial fines with their rules
        $fines = $this->db->select('f.id, f.due_date, f.fine_amount, f.paid_amount, f.waived_amount')
                          ->select('fr.calculation_type, fr.fine_value, fr.per_day_amount, fr.grace_period_days, fr.max_fine_amount')
                          ->from('fines f')
                          ->join('fine_rules fr', 'fr.id = f.fine_rule_id')
                          ->where_in('f.status', ['pending', 'partial'])
                          ->get()
                          ->result();
        
        $fixed = 0;
        $total = count($fines);
        
        foreach ($fines as $f) {
            $days_late = floor((strtotime(date('Y-m-d')) - strtotime($f->due_date)) / 86400);
            
            $rule_obj = (object)[
                'grace_period_days' => $f->grace_period_days ?? 0,
                'calculation_type'  => $f->calculation_type ?? 'fixed',
                'fine_value'        => $f->fine_value ?? 0,
                'per_day_amount'    => $f->per_day_amount ?? 0,
                'max_fine_amount'   => $f->max_fine_amount ?? 0,
            ];
            
            $correct_amount = $this->Fine_model->calculate_fine_amount($rule_obj, $days_late);
            
            if ($correct_amount > 0 && abs($correct_amount - (float)$f->fine_amount) > 0.01) {
                $new_balance = $correct_amount - (float)$f->paid_amount - (float)$f->waived_amount;
                
                $this->db->where('id', $f->id)->update('fines', [
                    'fine_amount' => $correct_amount,
                    'balance_amount' => max(0, $new_balance),
                    'days_late' => $days_late,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'updated_by' => $admin_id
                ]);
                $fixed++;
            }
        }
        
        $this->log_audit('recalculate', 'fines', 'fines', null, null, ['total' => $total, 'fixed' => $fixed]);
        $this->session->set_flashdata('success', "Recalculated $fixed of $total pending fines.");
        redirect('admin/fines');
    }
}
