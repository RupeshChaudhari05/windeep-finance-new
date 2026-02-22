<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/Member_Controller.php';

class Notifications extends Member_Controller {
    public function index() {
        $member_id = $this->member->id;
        $this->load->model('Notification_model');
        $data['notifications'] = $this->Notification_model->get_for('member', $member_id, 100);
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
                 ->where('recipient_type', 'member')
                 ->where('recipient_id', $member_id)
                 ->update('notifications', ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]);
        
        if ($this->db->affected_rows() > 0) {
            $this->json_response(['success' => true, 'message' => 'Notification marked as read.']);
        } else {
            $this->json_response(['success' => false, 'message' => 'Notification not found.']);
        }
    }
}