<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Public_Controller extends MY_Controller {
    public function __construct() {
        parent::__construct();
    }
}

class PublicController extends Public_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('Loan_model');
        $this->load->model('Member_model');
        $this->load->model('Notification_model');
    }

    /**
     * Public guarantor consent (token based)
     */
    public function guarantor_consent($guarantor_id, $token = null) {
        $guarantor = $this->db->where('id', $guarantor_id)->get('loan_guarantors')->row();
        if (!$guarantor) {
            echo '<p>Request not found.</p>'; return;
        }

        if (!$token || !isset($guarantor->consent_token) || $token !== $guarantor->consent_token) {
            echo '<p>Invalid or expired link.</p>'; return;
        }

        $application = $this->Loan_model->get_application($guarantor->loan_application_id);

        $view_data = [
            'guarantor'        => $guarantor,
            'application'      => $application,
            'success_message'  => '',
            'error_message'    => '',
            'already_responded'=> false,
        ];

        if ($this->input->method() === 'post') {
            $action = $this->input->post('action');
            $remarks = $this->input->post('remarks');

            if ($action === 'accept') {
                $this->Loan_model->update_guarantor_consent($guarantor_id, 'accepted', $remarks);
                $title = 'Guarantor Accepted: ' . $application->application_number;
                $message = 'Guarantor has accepted for application ' . $application->application_number . '.';
                // notify admins
                $admins = $this->db->where('is_active', 1)->get('admin_users')->result();
                foreach ($admins as $a) {
                    $this->Notification_model->create('admin', $a->id, 'guarantor_accepted', $title, $message, ['application_id' => $application->id]);
                    if (!empty($a->email)) {
                        send_email($a->email, $title, '<p>' . htmlspecialchars($message) . '</p>');
                    }
                }
                // notify applicant
                $applicant = $this->Member_model->get_by_id($application->member_id);
                if ($applicant && !empty($applicant->email)) {
                    $this->Notification_model->create('member', $applicant->id, 'guarantor_accepted', $title, $message, ['application_id' => $application->id]);
                    send_email($applicant->email, $title, '<p>' . htmlspecialchars($message) . '</p>');
                }
                $view_data['success_message'] = 'Thank you! You have accepted the guarantor request.';

            } elseif ($action === 'reject') {
                $this->Loan_model->update_guarantor_consent($guarantor_id, 'rejected', $remarks);
                $title = 'Guarantor Rejected: ' . $application->application_number;
                $message = 'Guarantor has rejected the request for application ' . $application->application_number . '.';
                $guarantor_member = $this->Member_model->get_by_id($guarantor->guarantor_member_id);
                $rev_note = 'Rejected by guarantor: ' . ($guarantor_member ? ($guarantor_member->first_name . ' ' . $guarantor_member->last_name) : 'Guarantor');
                $this->Loan_model->request_modification($application->id, $rev_note, null, []);
                // notify admins
                $admins = $this->db->where('is_active', 1)->get('admin_users')->result();
                foreach ($admins as $a) {
                    $this->Notification_model->create('admin', $a->id, 'guarantor_rejected', $title, $message, ['application_id' => $application->id]);
                    if (!empty($a->email)) {
                        send_email($a->email, $title, '<p>' . htmlspecialchars($message) . '</p>');
                    }
                }
                // notify applicant
                $applicant = $this->Member_model->get_by_id($application->member_id);
                if ($applicant && !empty($applicant->email)) {
                    $member_msg = '<p>Your loan application <strong>' . htmlspecialchars($application->application_number) . '</strong> requires modification: ' . htmlspecialchars($rev_note) . '</p>';
                    $this->Notification_model->create('member', $applicant->id, 'guarantor_rejected', 'Application requires modification', $member_msg, ['application_id' => $application->id]);
                    send_email($applicant->email, 'Application requires modification', $member_msg);
                }
                $view_data['success_message'] = 'You have rejected the guarantor request. The applicant has been notified.';
            }

            $this->load->view('public/guarantor_consent', $view_data);
            return;
        }

        // Show consent form or already-responded status
        if ($guarantor->consent_status !== 'pending') {
            $view_data['already_responded'] = true;
        }

        $this->load->view('public/guarantor_consent', $view_data);
    }
}
