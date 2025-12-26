<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Fine_model - Fine & Penalty Management
 */
class Fine_model extends MY_Model {
    
    protected $table = 'fines';
    protected $primary_key = 'id';
    
    /**
     * Generate Fine Code
     */
    public function generate_fine_code() {
        return 'FIN-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
    
    /**
     * Create Fine
     */
    public function create_fine($data) {
        $data['fine_code'] = $this->generate_fine_code();
        $data['balance_amount'] = $data['fine_amount'];
        $data['status'] = 'pending';
        $data['created_at'] = date('Y-m-d H:i:s');
        
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }
    
    /**
     * Apply Loan Late Fine
     */
    public function apply_loan_late_fine($installment_id, $created_by) {
        // Get installment details
        $installment = $this->db->select('li.*, l.member_id, l.loan_number, l.loan_product_id')
                                ->from('loan_installments li')
                                ->join('loans l', 'l.id = li.loan_id')
                                ->where('li.id', $installment_id)
                                ->get()
                                ->row();
        
        if (!$installment) return false;
        
        // Check if already fined for this period
        $existing = $this->db->where('related_type', 'loan_installment')
                             ->where('related_id', $installment_id)
                             ->count_all_results($this->table);
        
        if ($existing > 0) return false;
        
        // Get fine rule
        $days_late = floor((strtotime(date('Y-m-d')) - strtotime($installment->due_date)) / 86400);
        
        $rule = $this->db->where('fine_type', 'loan_late')
                         ->where('loan_product_id', $installment->loan_product_id)
                         ->where('min_days <=', $days_late)
                         ->where('max_days >=', $days_late)
                         ->where('is_active', 1)
                         ->get('fine_rules')
                         ->row();
        
        if (!$rule) {
            // Get default rule
            $rule = $this->db->where('fine_type', 'loan_late')
                             ->where('loan_product_id IS NULL')
                             ->where('min_days <=', $days_late)
                             ->where('max_days >=', $days_late)
                             ->where('is_active', 1)
                             ->get('fine_rules')
                             ->row();
        }
        
        if (!$rule) return false;
        
        // Calculate fine
        $fine_amount = 0;
        if ($rule->calculation_type === 'fixed') {
            $fine_amount = $rule->fixed_amount;
        } elseif ($rule->calculation_type === 'percentage') {
            $base = $rule->calculation_base === 'principal' ? $installment->principal_amount : $installment->emi_amount;
            $fine_amount = $base * ($rule->percentage_value / 100);
        } elseif ($rule->calculation_type === 'per_day') {
            $fine_amount = $rule->per_day_amount * $days_late;
        }
        
        // Apply max cap if set
        if ($rule->max_fine_amount > 0 && $fine_amount > $rule->max_fine_amount) {
            $fine_amount = $rule->max_fine_amount;
        }
        
        if ($fine_amount <= 0) return false;
        
        // Create fine
        return $this->create_fine([
            'member_id' => $installment->member_id,
            'fine_type' => 'loan_late',
            'related_type' => 'loan_installment',
            'related_id' => $installment_id,
            'fine_rule_id' => $rule->id,
            'fine_date' => date('Y-m-d'),
            'due_date' => $installment->due_date,
            'days_late' => $days_late,
            'fine_amount' => $fine_amount,
            'remarks' => 'Late payment fine for ' . $installment->loan_number . ' EMI #' . $installment->installment_number,
            'created_by' => $created_by
        ]);
    }
    
    /**
     * Apply Savings Late Fine
     */
    public function apply_savings_late_fine($schedule_id, $created_by) {
        // Get schedule details
        $schedule = $this->db->select('sch.*, sa.account_number, sa.member_id')
                             ->from('savings_schedule sch')
                             ->join('savings_accounts sa', 'sa.id = sch.savings_account_id')
                             ->where('sch.id', $schedule_id)
                             ->get()
                             ->row();
        
        if (!$schedule) return false;
        
        // Check if already fined
        $existing = $this->db->where('related_type', 'savings_schedule')
                             ->where('related_id', $schedule_id)
                             ->count_all_results($this->table);
        
        if ($existing > 0) return false;
        
        $days_late = floor((strtotime(date('Y-m-d')) - strtotime($schedule->due_date)) / 86400);
        
        // Get fine rule
        $rule = $this->db->where('fine_type', 'savings_late')
                         ->where('min_days <=', $days_late)
                         ->where('max_days >=', $days_late)
                         ->where('is_active', 1)
                         ->get('fine_rules')
                         ->row();
        
        if (!$rule) return false;
        
        // Calculate fine
        $fine_amount = 0;
        if ($rule->calculation_type === 'fixed') {
            $fine_amount = $rule->fixed_amount;
        } elseif ($rule->calculation_type === 'percentage') {
            $fine_amount = $schedule->due_amount * ($rule->percentage_value / 100);
        } elseif ($rule->calculation_type === 'per_day') {
            $fine_amount = $rule->per_day_amount * $days_late;
        }
        
        if ($rule->max_fine_amount > 0 && $fine_amount > $rule->max_fine_amount) {
            $fine_amount = $rule->max_fine_amount;
        }
        
        if ($fine_amount <= 0) return false;
        
        return $this->create_fine([
            'member_id' => $schedule->member_id,
            'fine_type' => 'savings_late',
            'related_type' => 'savings_schedule',
            'related_id' => $schedule_id,
            'fine_rule_id' => $rule->id,
            'fine_date' => date('Y-m-d'),
            'due_date' => $schedule->due_date,
            'days_late' => $days_late,
            'fine_amount' => $fine_amount,
            'remarks' => 'Late savings payment fine for ' . $schedule->account_number,
            'created_by' => $created_by
        ]);
    }
    
    /**
     * Record Fine Payment
     */
    public function record_payment($fine_id, $amount, $payment_mode, $reference = null, $received_by = null) {
        $fine = $this->get_by_id($fine_id);
        
        if (!$fine || $fine->status === 'paid') {
            return false;
        }
        
        $new_paid = $fine->paid_amount + $amount;
        $new_balance = $fine->balance_amount - $amount;
        
        $status = 'partial';
        if ($new_balance <= 0) {
            $status = 'paid';
            $new_balance = 0;
        }
        
        return $this->db->where('id', $fine_id)
                        ->update($this->table, [
                            'paid_amount' => $new_paid,
                            'balance_amount' => $new_balance,
                            'status' => $status,
                            'payment_mode' => $payment_mode,
                            'payment_reference' => $reference,
                            'payment_date' => date('Y-m-d'),
                            'received_by' => $received_by,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
    }
    
    /**
     * Waive Fine
     */
    public function waive_fine($fine_id, $waive_amount, $reason, $waived_by) {
        $fine = $this->get_by_id($fine_id);
        
        if (!$fine) return false;
        
        $new_waived = $fine->waived_amount + $waive_amount;
        $new_balance = $fine->balance_amount - $waive_amount;
        
        $status = $fine->status;
        if ($new_balance <= 0) {
            $status = 'waived';
            $new_balance = 0;
        }
        
        return $this->db->where('id', $fine_id)
                        ->update($this->table, [
                            'waived_amount' => $new_waived,
                            'balance_amount' => $new_balance,
                            'waive_reason' => $reason,
                            'waived_by' => $waived_by,
                            'waived_at' => date('Y-m-d H:i:s'),
                            'status' => $status,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
    }
    
    /**
     * Cancel Fine
     */
    public function cancel_fine($fine_id, $reason, $cancelled_by) {
        return $this->db->where('id', $fine_id)
                        ->update($this->table, [
                            'status' => 'cancelled',
                            'cancellation_reason' => $reason,
                            'cancelled_by' => $cancelled_by,
                            'cancelled_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
    }
    
    /**
     * Get Member Fines
     */
    public function get_member_fines($member_id, $pending_only = false) {
        $this->db->where('member_id', $member_id);
        
        if ($pending_only) {
            $this->db->where_in('status', ['pending', 'partial']);
        }
        
        return $this->db->order_by('fine_date', 'DESC')
                        ->get($this->table)
                        ->result();
    }
    
    /**
     * Get Pending Fines
     */
    public function get_pending() {
        return $this->db->select('f.*, m.member_code, m.first_name, m.last_name, m.phone')
                        ->from('fines f')
                        ->join('members m', 'm.id = f.member_id')
                        ->where_in('f.status', ['pending', 'partial'])
                        ->order_by('f.fine_date', 'ASC')
                        ->get()
                        ->result();
    }
    
    /**
     * Get Fine Rules
     */
    public function get_rules($fine_type = null) {
        if ($fine_type) {
            $this->db->where('fine_type', $fine_type);
        }
        
        return $this->db->order_by('fine_type', 'ASC')
                        ->order_by('min_days', 'ASC')
                        ->get('fine_rules')
                        ->result();
    }
    
    /**
     * Get Fine Summary
     */
    public function get_summary() {
        return $this->db->select('
            COUNT(*) as total_fines,
            SUM(fine_amount) as total_amount,
            SUM(paid_amount) as total_paid,
            SUM(waived_amount) as total_waived,
            SUM(balance_amount) as total_balance
        ')
        ->where_in('status', ['pending', 'partial', 'paid', 'waived'])
        ->get($this->table)
        ->row();
    }
    
    /**
     * Calculate Fine Amount (Indian Banking Style)
     * Supports: Fixed, Percentage, Per Day, Fixed + Daily
     */
    public function calculate_fine_amount($rule, $days_late, $due_amount = 0) {
        $grace_period = $rule->grace_period ?? 0;
        $effective_days = max(0, $days_late - $grace_period);
        
        if ($effective_days <= 0) {
            return 0;
        }
        
        $fine_amount = 0;
        $fine_type = $rule->fine_type ?? 'fixed';
        
        switch ($fine_type) {
            case 'fixed':
                // One-time fixed amount
                $fine_amount = $rule->fine_amount ?? 0;
                break;
                
            case 'percentage':
                // Percentage of due amount
                $rate = $rule->fine_rate ?? 0;
                $fine_amount = $due_amount * ($rate / 100);
                break;
                
            case 'per_day':
                // Per day calculation only
                $per_day = $rule->per_day_amount ?? 0;
                $fine_amount = $effective_days * $per_day;
                break;
                
            case 'fixed_plus_daily':
                // Indian banking style: Initial fixed + daily amount
                // Example: ₹100 initial + ₹10 per day after grace period
                $initial = $rule->fine_amount ?? 100;
                $per_day = $rule->per_day_amount ?? 10;
                $fine_amount = $initial + ($effective_days * $per_day);
                break;
        }
        
        // Apply maximum cap if set
        $max_fine = $rule->max_fine ?? 0;
        if ($max_fine > 0 && $fine_amount > $max_fine) {
            $fine_amount = $max_fine;
        }
        
        return round($fine_amount, 2);
    }
    
    /**
     * Update Daily Fines
     * Called daily to update fine amounts for per_day and fixed_plus_daily rules
     */
    public function update_daily_fines($created_by = 1) {
        $updated = 0;
        
        // Get all pending fines that use daily calculation
        $pending_fines = $this->db->select('f.*, fr.fine_type, fr.fine_amount, fr.per_day_amount, fr.grace_period, fr.max_fine')
                                  ->from('fines f')
                                  ->join('fine_rules fr', 'fr.id = f.fine_rule_id')
                                  ->where_in('f.status', ['pending', 'partial'])
                                  ->where_in('fr.fine_type', ['per_day', 'fixed_plus_daily'])
                                  ->get()
                                  ->result();
        
        foreach ($pending_fines as $fine) {
            $days_late = floor((strtotime(date('Y-m-d')) - strtotime($fine->due_date)) / 86400);
            $new_amount = $this->calculate_fine_amount($fine, $days_late);
            
            // Only update if amount increased
            if ($new_amount > $fine->fine_amount) {
                $new_balance = $new_amount - $fine->paid_amount - $fine->waived_amount;
                
                $this->db->where('id', $fine->id)
                         ->update($this->table, [
                             'fine_amount' => $new_amount,
                             'balance_amount' => max(0, $new_balance),
                             'days_late' => $days_late,
                             'updated_at' => date('Y-m-d H:i:s'),
                             'updated_by' => $created_by
                         ]);
                
                $updated++;
            }
        }
        
        return $updated;
    }
    
    /**
     * Run Late Fine Job
     * Called by cron to auto-apply fines
     */
    public function run_late_fine_job($created_by = 1) {
        $applied = 0;
        
        // First update existing daily fines
        $this->update_daily_fines($created_by);
        
        // Loan late fines
        $overdue_installments = $this->db->select('li.id')
            ->from('loan_installments li')
            ->join('loans l', 'l.id = li.loan_id')
            ->where('l.status', 'active')
            ->where('li.status', 'pending')
            ->where('li.due_date <', date('Y-m-d'))
            ->where('li.fine_amount', 0)
            ->get()
            ->result();
        
        foreach ($overdue_installments as $inst) {
            if ($this->apply_loan_late_fine($inst->id, $created_by)) {
                $applied++;
            }
        }
        
        // Savings late fines
        $overdue_schedules = $this->db->select('sch.id')
            ->from('savings_schedule sch')
            ->join('savings_accounts sa', 'sa.id = sch.savings_account_id')
            ->where('sa.status', 'active')
            ->where_in('sch.status', ['pending', 'partial'])
            ->where('sch.due_date <', date('Y-m-d'))
            ->where('sch.fine_amount', 0)
            ->get()
            ->result();
        
        foreach ($overdue_schedules as $sch) {
            if ($this->apply_savings_late_fine($sch->id, $created_by)) {
                $applied++;
            }
        }
        
        return $applied;
    }
}
