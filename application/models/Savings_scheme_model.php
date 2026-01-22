<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Savings_scheme_model - CRUD for savings_schemes
 */
class Savings_scheme_model extends MY_Model {
    protected $table = 'savings_schemes';

    /**
     * Save a scheme (insert or update)
     * Returns inserted id or updated id on success, false on failure
     */
    public function save_scheme(array $data) {
        $id = isset($data['id']) ? (int) $data['id'] : null;

        // Normalize fields
        // Build payload conditionally based on existing DB columns so updates don't fail on databases
        $payload = ['scheme_name' => $data['scheme_name'] ?? null];

        if ($this->db->field_exists('description', $this->table)) {
            $payload['description'] = $data['description'] ?? null;
        }
        if ($this->db->field_exists('min_deposit', $this->table)) {
            $payload['min_deposit'] = isset($data['min_deposit']) ? (float) $data['min_deposit'] : 0.0;
        }
        if ($this->db->field_exists('monthly_amount', $this->table)) {
            $payload['monthly_amount'] = isset($data['monthly_amount']) ? (float) $data['monthly_amount'] : (isset($data['min_deposit']) ? (float) $data['min_deposit'] : 0.0);
        }
        if ($this->db->field_exists('interest_rate', $this->table)) {
            $payload['interest_rate'] = isset($data['interest_rate']) ? (float) $data['interest_rate'] : 0.0;
        }
        if ($this->db->field_exists('deposit_frequency', $this->table)) {
            $payload['deposit_frequency'] = $data['deposit_frequency'] ?? 'monthly';
        }
        if ($this->db->field_exists('lock_in_period', $this->table)) {
            $payload['lock_in_period'] = isset($data['lock_in_period']) ? (int) $data['lock_in_period'] : 0;
        }
        if ($this->db->field_exists('penalty_rate', $this->table)) {
            $payload['penalty_rate'] = isset($data['penalty_rate']) ? (float) $data['penalty_rate'] : 0.0;
        }
        if ($this->db->field_exists('maturity_bonus', $this->table)) {
            $payload['maturity_bonus'] = isset($data['maturity_bonus']) ? (float) $data['maturity_bonus'] : 0.0;
        }
        if ($this->db->field_exists('due_day', $this->table)) {
            $payload['due_day'] = isset($data['due_day']) ? (int) $data['due_day'] : 1;
        }

        // always update timestamp if column exists, else ignore
        if ($this->db->field_exists('updated_at', $this->table)) {
            $payload['updated_at'] = date('Y-m-d H:i:s');
        }

        if ($id) {
            $this->db->where('id', $id);
            $ok = $this->db->update($this->table, $payload);
            return $ok ? $id : false;
        }

        // Insert
        $payload['scheme_code'] = $data['scheme_code'] ?? ('SS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6)));
        $payload['created_at'] = date('Y-m-d H:i:s');
        $payload['created_by'] = $data['created_by'] ?? null;

        $ok = $this->db->insert($this->table, $payload);
        return $ok ? $this->db->insert_id() : false;
    }

    /**
     * Toggle scheme active flag
     */
    public function toggle_scheme($id, $is_active) {
        $ok = $this->db->where('id', $id)->update($this->table, ['is_active' => (int) $is_active, 'updated_at' => date('Y-m-d H:i:s')]);
        return (bool) $ok;
    }
}