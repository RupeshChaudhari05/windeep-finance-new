<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * NonMembers Controller - Non-Member Fund Provider Management
 */
class NonMembers extends Admin_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('NonMember_model');
    }

    /**
     * List Non-Members
     */
    public function index() {
        $data['title'] = 'Fund Providers';
        $data['page_title'] = 'Non-Member Fund Providers';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Fund Providers', 'url' => '']
        ];

        $filters = [
            'status' => $this->input->get('status'),
            'search' => $this->input->get('search')
        ];

        $page = (int) ($this->input->get('page') ?: 1);
        $per_page = 20;

        $paginated = $this->NonMember_model->get_paginated($filters, $page, $per_page);
        $data['non_members'] = $paginated['data'];
        $data['pagination'] = [
            'current_page' => $paginated['current_page'],
            'per_page' => $paginated['per_page'],
            'total' => $paginated['total'],
            'total_pages' => $paginated['total_pages']
        ];
        $data['filters'] = $filters;
        $data['summary'] = $this->NonMember_model->get_dashboard_summary();

        $this->load_view('admin/non_members/index', $data);
    }

    /**
     * Create Non-Member
     */
    public function create() {
        $data['title'] = 'Add Fund Provider';
        $data['page_title'] = 'Add New Fund Provider';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Fund Providers', 'url' => 'admin/non_members'],
            ['title' => 'Add New', 'url' => '']
        ];

        if ($this->input->method() === 'post') {
            $insert = [
                'name'    => $this->input->post('name', true),
                'email'   => $this->input->post('email', true),
                'phone'   => $this->input->post('phone', true),
                'address' => $this->input->post('address', true),
                'notes'   => $this->input->post('notes', true),
                'status'  => $this->input->post('status') ?: 'active',
                'created_by' => $this->session->userdata('admin_id')
            ];

            if (empty($insert['name'])) {
                $this->session->set_flashdata('error', 'Name is required.');
                redirect('admin/non_members/create');
                return;
            }

            $id = $this->NonMember_model->create($insert);
            if ($id) {
                $this->log_activity('Created non-member fund provider', "ID: $id, Name: {$insert['name']}");
                $this->session->set_flashdata('success', 'Fund provider added successfully.');
                redirect('admin/non_members/view/' . $id);
            } else {
                $this->session->set_flashdata('error', 'Failed to add fund provider.');
                redirect('admin/non_members/create');
            }
            return;
        }

        $this->load_view('admin/non_members/create', $data);
    }

    /**
     * View Non-Member with Fund History
     */
    public function view($id = null) {
        if (!$id) redirect('admin/non_members');

        $nm = $this->NonMember_model->get_by_id($id);
        if (!$nm) {
            $this->session->set_flashdata('error', 'Fund provider not found.');
            redirect('admin/non_members');
        }

        $data['title'] = 'View Fund Provider';
        $data['page_title'] = $nm->name;
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Fund Providers', 'url' => 'admin/non_members'],
            ['title' => $nm->name, 'url' => '']
        ];
        $data['non_member'] = $nm;
        $data['funds'] = $this->NonMember_model->get_funds($id);

        $this->load_view('admin/non_members/view', $data);
    }

    /**
     * Edit Non-Member
     */
    public function edit($id = null) {
        if (!$id) redirect('admin/non_members');

        $nm = $this->NonMember_model->get_by_id($id);
        if (!$nm) {
            $this->session->set_flashdata('error', 'Fund provider not found.');
            redirect('admin/non_members');
        }

        $data['title'] = 'Edit Fund Provider';
        $data['page_title'] = 'Edit: ' . $nm->name;
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Fund Providers', 'url' => 'admin/non_members'],
            ['title' => 'Edit', 'url' => '']
        ];
        $data['non_member'] = $nm;

        if ($this->input->method() === 'post') {
            $update = [
                'name'    => $this->input->post('name', true),
                'email'   => $this->input->post('email', true),
                'phone'   => $this->input->post('phone', true),
                'address' => $this->input->post('address', true),
                'notes'   => $this->input->post('notes', true),
                'status'  => $this->input->post('status') ?: 'active'
            ];

            if (empty($update['name'])) {
                $this->session->set_flashdata('error', 'Name is required.');
                redirect('admin/non_members/edit/' . $id);
                return;
            }

            $this->NonMember_model->update($id, $update);
            $this->log_activity('Updated non-member fund provider', "ID: $id, Name: {$update['name']}");
            $this->session->set_flashdata('success', 'Fund provider updated successfully.');
            redirect('admin/non_members/view/' . $id);
            return;
        }

        $this->load_view('admin/non_members/edit', $data);
    }

    /**
     * Delete Non-Member
     */
    public function delete($id = null) {
        if (!$id) redirect('admin/non_members');

        $result = $this->NonMember_model->delete($id);
        if ($result['success']) {
            $this->log_activity('Deleted non-member fund provider', "ID: $id");
            $this->session->set_flashdata('success', 'Fund provider deleted successfully.');
        } else {
            $this->session->set_flashdata('error', $result['message']);
        }
        redirect('admin/non_members');
    }

    /**
     * Add Fund Transaction (AJAX)
     */
    public function add_fund() {
        if (!$this->input->is_ajax_request()) {
            redirect('admin/non_members');
        }

        $non_member_id = $this->input->post('non_member_id');
        $nm = $this->NonMember_model->get_by_id($non_member_id);
        if (!$nm) {
            $this->error_response('Fund provider not found.');
            return;
        }

        $amount = floatval($this->input->post('amount'));
        $type = $this->input->post('transaction_type');
        if ($amount <= 0) {
            $this->error_response('Amount must be greater than zero.');
            return;
        }

        // Validate: can't return more than received
        if ($type === 'returned' && $amount > $nm->balance) {
            $this->error_response('Return amount (' . number_format($amount, 2) . ') exceeds outstanding balance (' . number_format($nm->balance, 2) . ').');
            return;
        }

        $fund_data = [
            'non_member_id'    => $non_member_id,
            'amount'           => $amount,
            'transaction_type' => $type,
            'transaction_date' => $this->input->post('transaction_date') ?: date('Y-m-d'),
            'payment_mode'     => $this->input->post('payment_mode') ?: 'cash',
            'reference_number' => $this->input->post('reference_number'),
            'description'      => $this->input->post('description'),
            'created_by'       => $this->session->userdata('admin_id')
        ];

        $fund_id = $this->NonMember_model->add_fund($fund_data);
        if ($fund_id) {
            $this->log_activity('Added non-member fund transaction',
                "Provider: {$nm->name}, Type: $type, Amount: " . number_format($amount, 2));
            $this->success_response('Fund transaction recorded successfully.', ['fund_id' => $fund_id]);
        } else {
            $this->error_response('Failed to record fund transaction.');
        }
    }

    /**
     * Delete Fund Transaction (AJAX)
     */
    public function delete_fund() {
        if (!$this->input->is_ajax_request()) {
            redirect('admin/non_members');
        }

        $fund_id = $this->input->post('fund_id');
        $fund = $this->NonMember_model->get_fund_by_id($fund_id);
        if (!$fund) {
            $this->error_response('Fund transaction not found.');
            return;
        }

        $this->NonMember_model->delete_fund($fund_id);
        $this->log_activity('Deleted non-member fund transaction', "Fund ID: $fund_id");
        $this->success_response('Fund transaction deleted successfully.');
    }
}
