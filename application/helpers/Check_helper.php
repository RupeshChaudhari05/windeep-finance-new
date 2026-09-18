<?php
/**
 * Check Details Helper
 * Manages check collection and verification for loan applications
 */

class Check_helper {
    private $CI;
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }

    private function table_exists() {
        return $this->CI->db->table_exists('loan_check_details');
    }
    
    /**
     * Save check details for a loan application
     */
    public function save_check_details($application_id, $check_data, $created_by = null) {
        if (!$this->table_exists()) {
            return false;
        }
        $insert_data = array(
            'loan_application_id' => $application_id,
            'member_id' => $check_data['member_id'],
            'check_type' => $check_data['check_type'] ?? 'applicant',
            'check_number' => $check_data['check_number'],
            'bank_name' => $check_data['bank_name'],
            'account_number' => $check_data['account_number'] ?? null,
            'ifsc_code' => $check_data['ifsc_code'],
            'amount' => $check_data['amount'],
            'check_date' => $check_data['check_date'],
            'status' => 'pending',
            'notes' => $check_data['notes'] ?? null,
            'created_by' => $created_by
        );
        
        if (isset($check_data['guarantor_id'])) {
            $insert_data['guarantor_id'] = $check_data['guarantor_id'];
        }
        
        return $this->CI->db->insert('loan_check_details', $insert_data);
    }
    
    /**
     * Get all checks for a loan application
     */
    public function get_application_checks($application_id) {
        if (!$this->table_exists()) {
            return [];
        }

        $this->CI->db->select('lcd.*, m.first_name, m.last_name, m.member_code, 
                              g.guarantor_member_id, gm.first_name as guar_first_name, 
                              gm.last_name as guar_last_name, gm.member_code as guar_member_code,
                              au.full_name as verified_by_name')
            ->from('loan_check_details lcd')
            ->join('members m', 'lcd.member_id = m.id', 'left')
            ->join('loan_guarantors g', 'lcd.guarantor_id = g.id', 'left')
            ->join('members gm', 'g.guarantor_member_id = gm.id', 'left')
            ->join('admin_users au', 'lcd.verified_by = au.id', 'left')
            ->where('lcd.loan_application_id', $application_id)
            ->order_by('lcd.check_type', 'ASC')
            ->order_by('lcd.created_at', 'ASC');
        
        $checks = $this->CI->db->get()->result();
        
        // Add member_name property based on check type
        foreach ($checks as &$check) {
            if ($check->check_type === 'applicant') {
                $check->member_name = trim(($check->first_name ?? '') . ' ' . ($check->last_name ?? '')) . ' (' . ($check->member_code ?? '') . ')';
            } else {
                $check->member_name = trim(($check->guar_first_name ?? '') . ' ' . ($check->guar_last_name ?? '')) . ' (' . ($check->guar_member_code ?? '') . ')';
            }
        }
        
        return $checks;
    }
    
    /**
     * Get all checks for a loan
     */
    public function get_loan_checks($loan_id) {
        if (!$this->table_exists()) {
            return [];
        }

        $this->CI->db->select('lcd.*, m.first_name, m.last_name, m.member_code, 
                              g.guarantor_member_id, gm.first_name as guar_first_name, 
                              gm.last_name as guar_last_name')
            ->from('loan_check_details lcd')
            ->join('members m', 'lcd.member_id = m.id', 'left')
            ->join('loan_guarantors g', 'lcd.guarantor_id = g.id', 'left')
            ->join('members gm', 'g.guarantor_member_id = gm.id', 'left')
            ->where('lcd.loan_id', $loan_id)
            ->order_by('lcd.check_type', 'ASC')
            ->order_by('lcd.created_at', 'ASC');
        
        return $this->CI->db->get()->result();
    }
    
    /**
     * Get a single check by ID
     */
    public function get_check($check_id) {
        if (!$this->table_exists()) {
            return null;
        }

        return $this->CI->db->select('lcd.*, m.first_name, m.last_name, m.member_code, 
                                      g.guarantor_member_id, gm.first_name as guar_first_name, 
                                      gm.last_name as guar_last_name,
                                      au.full_name as verified_by_name')
            ->from('loan_check_details lcd')
            ->join('members m', 'lcd.member_id = m.id', 'left')
            ->join('loan_guarantors g', 'lcd.guarantor_id = g.id', 'left')
            ->join('members gm', 'g.guarantor_member_id = gm.id', 'left')
            ->join('admin_users au', 'lcd.verified_by = au.id', 'left')
            ->where('lcd.id', $check_id)
            ->get()
            ->row();
    }
    
    /**
     * Update check status
     */
    public function update_check_status($check_id, $status, $notes = null, $verified_by = null) {
        if (!$this->table_exists()) {
            return false;
        }

        $update_data = array(
            'status' => $status
        );
        
        if (!empty($notes)) {
            $update_data['notes'] = $notes;
        }
        
        if ($status !== 'pending' && !empty($verified_by)) {
            $update_data['verified_by'] = $verified_by;
            $update_data['verified_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->CI->db->where('id', $check_id)->update('loan_check_details', $update_data);
    }
    
    /**
     * Link checks to a loan (when disbursed)
     */
    public function link_checks_to_loan($application_id, $loan_id) {
        if (!$this->table_exists()) {
            return false;
        }

        return $this->CI->db->where('loan_application_id', $application_id)
            ->where('loan_id', null)
            ->update('loan_check_details', array('loan_id' => $loan_id));
    }
    
    /**
     * Delete checks for an application
     */
    public function delete_application_checks($application_id) {
        if (!$this->table_exists()) {
            return false;
        }

        return $this->CI->db->where('loan_application_id', $application_id)->delete('loan_check_details');
    }
    
    /**
     * Get check summary for dashboard
     */
    public function get_check_summary() {
        $summary = new stdClass();

        if (!$this->table_exists()) {
            return $summary;
        }
        
        $this->CI->db->select('status, COUNT(*) as count')
            ->from('loan_check_details')
            ->group_by('status');
        
        $results = $this->CI->db->get()->result();
        
        foreach ($results as $row) {
            $summary->{$row->status} = $row->count;
        }
        
        return $summary;
    }
    
    /**
     * Get checks by bank for reconciliation
     */
    public function get_checks_by_bank($bank_name = null) {
        if (!$this->table_exists()) {
            return [];
        }

        $query = $this->CI->db->select('bank_name, COUNT(*) as count, SUM(amount) as total')
            ->from('loan_check_details')
            ->where('status !=', 'cancelled');
        
        if (!empty($bank_name)) {
            $query->where('bank_name', $bank_name);
        }
        
        $query->group_by('bank_name');
        
        return $query->get()->result();
    }
    
    /**
     * Validate check details
     */
    public function validate_check($check_data) {
        $errors = array();
        
        if (empty($check_data['check_number'])) {
            $errors[] = 'Check number is required';
        }
        
        if (empty($check_data['bank_name'])) {
            $errors[] = 'Bank name is required';
        }
        
        if (empty($check_data['ifsc_code'])) {
            $errors[] = 'IFSC code is required';
        } elseif (strlen($check_data['ifsc_code']) !== 11 && !preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $check_data['ifsc_code'])) {
            $errors[] = 'IFSC code format is invalid (must be 11 characters: XXXXXX0XXXXX)';
        }
        
        if (empty($check_data['amount']) || $check_data['amount'] <= 0) {
            $errors[] = 'Check amount must be greater than 0';
        }
        
        if (empty($check_data['check_date'])) {
            $errors[] = 'Check date is required';
        } else {
            $check_date = strtotime($check_data['check_date']);
            $today = strtotime(date('Y-m-d'));
            if ($check_date < $today) {
                $errors[] = 'Check date cannot be in the past';
            }
        }
        
        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }
}
