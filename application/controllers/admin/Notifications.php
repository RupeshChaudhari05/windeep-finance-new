<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin Notifications Controller
 * Professional notification management for admin panel
 */
class Notifications extends Admin_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('Notification_model');
    }
    
    /**
     * Notifications Index Page
     */
    public function index() {
        $admin_id = $this->session->userdata('admin_id');
        
        // Get filter
        $filter = $this->input->get('filter') ?? 'all';
        
        $notifications = $this->Notification_model->get_for('admin', $admin_id, 200);
        
        // Apply filter
        if ($filter === 'unread') {
            $notifications = array_filter($notifications, function($n) { return !$n->is_read; });
        } elseif ($filter === 'read') {
            $notifications = array_filter($notifications, function($n) { return $n->is_read; });
        }
        
        // Count stats
        $all_notifications = $this->Notification_model->get_for('admin', $admin_id, 200);
        $unread_count = 0;
        $read_count = 0;
        foreach ($all_notifications as $n) {
            if ($n->is_read) $read_count++;
            else $unread_count++;
        }
        
        $data['notifications'] = $notifications;
        $data['filter'] = $filter;
        $data['unread_count'] = $unread_count;
        $data['read_count'] = $read_count;
        $data['total_count'] = count($all_notifications);
        $data['title'] = 'Notifications';
        $data['page_title'] = 'Notifications';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Notifications', 'url' => '']
        ];
        
        $this->load_view('admin/notifications/index', $data);
    }
    
    /**
     * Mark single notification as read (AJAX)
     */
    public function mark_read($id) {
        $admin_id = $this->session->userdata('admin_id');
        
        $this->db->where('id', $id);
        // Check ownership - use whichever column exists
        if ($this->db->field_exists('recipient_type', 'notifications')) {
            $this->db->where('recipient_type', 'admin')
                     ->where('recipient_id', $admin_id);
        } else {
            $this->db->where('user_type', 'admin')
                     ->where('user_id', $admin_id);
        }
        $this->db->update('notifications', ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]);
        
        if ($this->input->is_ajax_request()) {
            $this->json_response(['success' => $this->db->affected_rows() > 0]);
        } else {
            $this->session->set_flashdata('success', 'Notification marked as read.');
            redirect('admin/notifications');
        }
    }
    
    /**
     * Mark all notifications as read (AJAX)
     */
    public function mark_all_read() {
        $admin_id = $this->session->userdata('admin_id');
        
        if ($this->db->field_exists('recipient_type', 'notifications')) {
            $this->db->where('recipient_type', 'admin')
                     ->where('recipient_id', $admin_id);
        } else {
            $this->db->where('user_type', 'admin')
                     ->where('user_id', $admin_id);
        }
        $this->db->where('is_read', 0)
                 ->update('notifications', ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]);
        
        if ($this->input->is_ajax_request()) {
            $this->json_response(['success' => true, 'affected' => $this->db->affected_rows()]);
        } else {
            $this->session->set_flashdata('success', 'All notifications marked as read.');
            redirect('admin/notifications');
        }
    }
    
    /**
     * Delete a notification (AJAX)
     */
    public function delete($id) {
        $admin_id = $this->session->userdata('admin_id');
        
        if ($this->db->field_exists('recipient_type', 'notifications')) {
            $this->db->where('recipient_type', 'admin')
                     ->where('recipient_id', $admin_id);
        } else {
            $this->db->where('user_type', 'admin')
                     ->where('user_id', $admin_id);
        }
        $this->db->where('id', $id)->delete('notifications');
        
        if ($this->input->is_ajax_request()) {
            $this->json_response(['success' => $this->db->affected_rows() > 0]);
        } else {
            $this->session->set_flashdata('success', 'Notification deleted.');
            redirect('admin/notifications');
        }
    }
}
