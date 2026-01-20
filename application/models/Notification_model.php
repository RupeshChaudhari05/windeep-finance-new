<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notification_model extends MY_Model {
    protected $table = 'notifications';

    /**
     * Create a notification in a DB-schema-agnostic way.
     * Usage:
     *  - create([ 'recipient_type' => 'member', 'recipient_id' => 12, 'notification_type' => 'foo', 'title' => 'T', 'message' => 'M', 'data' => [...] ])
     *  - create('member', 12, 'foo', 'T', 'M', ['extra' => 1]) // legacy varargs supported
     */
    public function create($data) {
        // Support legacy varargs as well as associative array
        if (!is_array($data)) {
            $args = func_get_args();
            if (count($args) >= 5) {
                $data = [
                    'recipient_type' => $args[0],
                    'recipient_id' => $args[1],
                    'notification_type' => $args[2],
                    'title' => $args[3],
                    'message' => $args[4],
                    'data' => $args[5] ?? []
                ];
            } else {
                log_message('error', 'Notification_model::create invalid arguments');
                return false;
            }
        }

        $notification_type = $data['notification_type'] ?? null;
        $title = $data['title'] ?? '';
        $message = $data['message'] ?? '';
        $payload = $data['data'] ?? null;
        $recipient_type = $data['recipient_type'] ?? ($data['user_type'] ?? null);
        $recipient_id = $data['recipient_id'] ?? ($data['user_id'] ?? null);

        $insert = [
            'notification_type' => $notification_type,
            'title' => $title,
            'message' => $message,
            'data' => !empty($payload) ? json_encode($payload) : null,
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Prefer new schema columns if present
        if ($recipient_type !== null && $recipient_id !== null && $this->db->field_exists('recipient_type', $this->table) && $this->db->field_exists('recipient_id', $this->table)) {
            $insert['recipient_type'] = $recipient_type;
            $insert['recipient_id'] = $recipient_id;
        }

        // Backwards compatibility: older code used user_type/user_id
        if ($recipient_type !== null && $recipient_id !== null && $this->db->field_exists('user_type', $this->table) && $this->db->field_exists('user_id', $this->table)) {
            $insert['user_type'] = $recipient_type;
            $insert['user_id'] = $recipient_id;
        }

        if ($this->db->insert($this->table, $insert)) {
            return $this->db->insert_id();
        }

        log_message('error', 'Notification_model::create failed: ' . json_encode($insert) . ' | DB error: ' . json_encode($this->db->error()));
        return false;
    }

    public function mark_read($id) {
        return $this->db->where('id', $id)->update($this->table, ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Get notifications for a recipient in a schema-agnostic way.
     * Prefer `recipient_type`/`recipient_id` columns if they exist, otherwise fallback to `user_type`/`user_id`.
     */
    public function get_for($recipient_type, $recipient_id, $limit = 50) {
        if ($this->db->field_exists('recipient_type', $this->table) && $this->db->field_exists('recipient_id', $this->table)) {
            $results = $this->db->where('recipient_type', $recipient_type)
                            ->where('recipient_id', $recipient_id)
                            ->order_by('created_at', 'DESC')
                            ->limit($limit)
                            ->get($this->table)
                            ->result();
            // Normalize is_read to integer and decode data for reliable JSON consumption
            foreach ($results as $r) {
                $r->is_read = isset($r->is_read) ? (int) $r->is_read : 0;
                $r->data = !empty($r->data) ? json_decode($r->data, true) : null;
            }
            return $results;
        }

        // Fallback for legacy schema
        $results = $this->db->where('user_type', $recipient_type)
                        ->where('user_id', $recipient_id)
                        ->order_by('created_at', 'DESC')
                        ->limit($limit)
                        ->get($this->table)
                        ->result();
        foreach ($results as $r) {
            $r->is_read = isset($r->is_read) ? (int) $r->is_read : 0;
        }
        return $results;
    }
}
