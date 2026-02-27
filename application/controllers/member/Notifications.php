<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/Member_Controller.php';

class Notifications extends Member_Controller {
    public function index() {
        $member_id = $this->member->id;
        $this->load->model('Notification_model');
        $notifications = $this->Notification_model->get_for('member', $member_id, 100);

        // For guarantor_request notifications, attach live consent_status so the
        // view knows whether the member already responded (avoids stale buttons).
        foreach ($notifications as $n) {
            if (isset($n->notification_type) && $n->notification_type === 'guarantor_request'
                && is_array($n->data) && !empty($n->data['guarantor_id'])) {
                $row = $this->db->select('consent_status')
                                ->where('id', (int) $n->data['guarantor_id'])
                                ->get('loan_guarantors')
                                ->row();
                $n->guarantor_consent_status = $row ? $row->consent_status : 'pending';
            }
        }

        $data['notifications'] = $notifications;
        $data['title'] = 'Notifications';
        $data['page_title'] = 'Your Notifications';
        $this->load_member_view('member/notifications/index', $data);
    }

    public function mark_read($id) {
        $member_id = $this->member->id;
        $this->db->where('id', $id)
                 ->where('recipient_type', 'member')
                 ->where('recipient_id', $member_id)
                 ->update('notifications', ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]);
        
        if ($this->db->affected_rows() > 0) {
            $this->session->set_flashdata('success', 'Notification marked as read.');
        } else {
            $this->session->set_flashdata('error', 'Notification not found.');
        }
        redirect('member/notifications');
    }

    /**
     * Mark notification read (AJAX - with ownership check)
     */
    public function mark_read_ajax($id) {
        $member_id = $this->member->id;
        $this->db->where('id', $id)
                 ->where('recipient_id', $member_id)
                 ->update('notifications', ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]);
        $this->json_response(['success' => $this->db->affected_rows() > 0]);
    }

    /**
     * Return loan application details as JSON for the notification modal.
     */
    public function get_loan_detail($application_id) {
        $this->load->model('Loan_model');
        $app = $this->Loan_model->get_application($application_id);
        if (!$app) {
            $this->json_response(['success' => false, 'message' => 'Application not found.']);
            return;
        }
        // Only expose to the applicant or a guarantor of this application
        $member_id = $this->member->id;
        $is_guarantor = (bool)$this->db
            ->where('loan_application_id', $application_id)
            ->where('guarantor_member_id', $member_id)
            ->count_all_results('loan_guarantors');
        if ($app->member_id != $member_id && !$is_guarantor) {
            $this->json_response(['success' => false, 'message' => 'Unauthorized.']);
            return;
        }
        $this->json_response([
            'success'            => true,
            'application_number' => $app->application_number,
            'applicant'          => $app->first_name . ' ' . $app->last_name,
            'member_code'        => $app->member_code,
            'phone'              => $app->phone,
            'product_name'       => $app->product_name ?? '-',
            'requested_amount'   => number_format($app->requested_amount, 2),
            'tenure'             => $app->requested_tenure_months . ' months',
            'interest_type'      => $app->interest_type ?? '-',
            'purpose'            => ucfirst(str_replace('_', ' ', $app->purpose)),
            'purpose_details'    => $app->purpose_details ?? '',
            'status'             => ucfirst(str_replace('_', ' ', $app->status)),
            'application_date'   => format_date($app->application_date),
            'expiry_date'        => $app->expiry_date ? format_date($app->expiry_date) : '-',
        ]);
    }
}