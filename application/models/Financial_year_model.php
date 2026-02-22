<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Financial_year_model - Financial Year Management
 */
class Financial_year_model extends MY_Model {
    
    protected $table = 'financial_years';
    protected $primary_key = 'id';
    
    /**
     * Get Active Financial Year
     */
    public function get_active() {
        return $this->db->where('is_active', 1)
                        ->get($this->table)
                        ->row();
    }
    
    /**
     * Set Active Financial Year (with transaction to prevent data inconsistency)
     */
    public function set_active($id) {
        $this->db->trans_begin();
        
        // Deactivate all
        $this->db->update($this->table, ['is_active' => 0]);
        
        // Activate selected
        $this->db->where('id', $id)
                 ->update($this->table, ['is_active' => 1]);
        
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        }
        
        $this->db->trans_commit();
        return true;
    }
    
    /**
     * Close Financial Year
     */
    public function close_year($id, $closed_by) {
        return $this->db->where('id', $id)
                        ->update($this->table, [
                            'is_closed' => 1,
                            'closed_at' => date('Y-m-d H:i:s'),
                            'closed_by' => $closed_by
                        ]);
    }
    
    /**
     * Create New Financial Year
     */
    public function create_year($year_code, $start_date, $end_date) {
        return $this->db->insert($this->table, [
            'year_code' => $year_code,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'is_active' => 0,
            'is_closed' => 0
        ]);
    }
    
    /**
     * Get All Financial Years
     */
    public function get_all_years() {
        return $this->db->order_by('start_date', 'DESC')
                        ->get($this->table)
                        ->result();
    }
}
