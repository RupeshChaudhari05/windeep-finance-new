<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/Member_Controller.php';

/**
 * Member Fines Controller - Handle member fine viewing and waiver requests
 */
class Fines extends Member_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(['Fine_model']);
    }

    /**
     * My Fines - List all fines for the logged-in member
     */
    public function index() {
        $data['title'] = 'My Fines';
        $data['page_title'] = 'My Fines & Penalties';

        // Get member's fines with related information
        $this->db->select('f.*, fr.rule_name, fr.calculation_type, fr.fine_value, fr.per_day_amount');
        $this->db->from('fines f');
        $this->db->join('fine_rules fr', 'fr.id = f.fine_rule_id', 'left');
        $this->db->where('f.member_id', $this->member->id);
        $this->db->order_by('f.created_at', 'DESC');

        $data['fines'] = $this->db->get()->result();

        // Calculate totals
        $data['total_pending'] = 0;
        $data['total_paid'] = 0;
        $data['total_waived'] = 0;

        foreach ($data['fines'] as $fine) {
            $balance = $fine->fine_amount - ($fine->paid_amount ?? 0) - ($fine->waived_amount ?? 0);

            switch ($fine->status) {
                case 'pending':
                case 'partial':
                    $data['total_pending'] += $balance;
                    break;
                case 'paid':
                    $data['total_paid'] += $fine->paid_amount ?? 0;
                    break;
                case 'waived':
                    $data['total_waived'] += $fine->waived_amount ?? 0;
                    break;
            }
        }

        $this->load_member_view('member/fines/index', $data);
    }

    /**
     * View Fine Details
     */
    public function view($fine_id) {
        $data['title'] = 'Fine Details';
        $data['page_title'] = 'Fine Details';

        // Get fine details with validation
        $this->db->select('f.*, fr.rule_name, fr.calculation_type, fr.fine_value, fr.per_day_amount, fr.description as rule_description');
        $this->db->from('fines f');
        $this->db->join('fine_rules fr', 'fr.id = f.fine_rule_id', 'left');
        $this->db->where('f.id', $fine_id);
        $this->db->where('f.member_id', $this->member->id);

        $fine = $this->db->get()->row();

        if (!$fine) {
            $this->session->set_flashdata('error', 'Fine not found or access denied.');
            redirect('member/fines');
            return;
        }

        $data['fine'] = $fine;

        // Get waiver request status if exists
        $data['waiver_request'] = $this->Fine_model->get_member_waiver_request($fine_id, $this->member->id);

        // Calculate balance
        $data['balance'] = $fine->fine_amount - ($fine->paid_amount ?? 0) - ($fine->waived_amount ?? 0);

        $this->load_member_view('member/fines/view', $data);
    }

    /**
     * Request Waiver for a Fine
     */
    public function request_waiver($fine_id) {
        // Validate fine ownership
        $fine = $this->db->where('id', $fine_id)
                        ->where('member_id', $this->member->id)
                        ->get('fines')
                        ->row();

        if (!$fine) {
            $this->json_response(['success' => false, 'message' => 'Fine not found or access denied.']);
            return;
        }

        // Check if waiver already requested
        if (!empty($fine->waiver_requested_at)) {
            $this->json_response(['success' => false, 'message' => 'Waiver already requested for this fine.']);
            return;
        }

        // Check if fine is already paid or waived
        if (in_array($fine->status, ['paid', 'waived'])) {
            $this->json_response(['success' => false, 'message' => 'Cannot request waiver for ' . $fine->status . ' fine.']);
            return;
        }

        $reason = $this->input->post('reason');
        $amount = $this->input->post('amount');

        if (empty($reason)) {
            $this->json_response(['success' => false, 'message' => 'Please provide a reason for the waiver request.']);
            return;
        }

        // Request waiver
        $result = $this->Fine_model->request_waiver($fine_id, $reason, $this->member->id, $amount);

        if ($result) {
            // Log activity
            $this->log_activity('Member requested fine waiver', "Fine ID: $fine_id, Reason: $reason");

            $this->json_response([
                'success' => true,
                'message' => 'Waiver request submitted successfully. You will be notified once it is reviewed.'
            ]);
        } else {
            $this->json_response(['success' => false, 'message' => 'Failed to submit waiver request. Please try again.']);
        }
    }

    /**
     * Get Waiver Status for a Fine (AJAX)
     */
    public function waiver_status($fine_id) {
        $fine = $this->db->select('waiver_requested_at, waiver_approved_at, waiver_denied_at, waiver_denied_reason, waiver_reason')
                        ->where('id', $fine_id)
                        ->where('member_id', $this->member->id)
                        ->get('fines')
                        ->row();

        if (!$fine) {
            $this->json_response(['success' => false, 'message' => 'Fine not found.']);
            return;
        }

        $status = 'none';
        $message = '';

        if (!empty($fine->waiver_denied_at)) {
            $status = 'denied';
            $message = 'Waiver request denied: ' . ($fine->waiver_denied_reason ?? 'No reason provided');
        } elseif (!empty($fine->waiver_approved_at)) {
            $status = 'approved';
            $message = 'Waiver request approved';
        } elseif (!empty($fine->waiver_requested_at)) {
            $status = 'pending';
            $message = 'Waiver request submitted and pending approval';
        }

        $this->json_response([
            'success' => true,
            'status' => $status,
            'message' => $message,
            'requested_at' => $fine->waiver_requested_at,
            'reason' => $fine->waiver_reason
        ]);
    }
}