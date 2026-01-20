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
        $this->db->where('id', $id)->update('notifications', ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]);
        $this->session->set_flashdata('success', 'Notification marked read.');
        redirect('member/notifications');
    }

    /**
     * Mark notification read (AJAX)
     */
    public function mark_read_ajax($id) {
        $this->db->where('id', $id)->update('notifications', ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]);
        $this->json_response(['success' => true]);
    }
}