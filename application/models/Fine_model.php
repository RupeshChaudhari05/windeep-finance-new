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
     * Bug #7 Fix: Check date to prevent duplicate fines on same day
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
        
        // Check if already fined for this installment on this date (Bug #7 Fix)
        $existing = $this->db->where('related_type', 'loan_installment')
                             ->where('related_id', $installment_id)
                             ->where('fine_date', date('Y-m-d'))
                             ->count_all_results($this->table);
        
        if ($existing > 0) return false;
        
        // Days late
        $days_late = floor((safe_timestamp(date('Y-m-d')) - safe_timestamp($installment->due_date)) / 86400);

        // Helper to add days range conditionally depending on schema
        $add_days_conditions = function($db, $days) {
            if ($this->db->field_exists('min_days', 'fine_rules')) {
                $db->where('min_days <=', $days)
                   ->where('max_days >=', $days);
            } elseif ($this->db->field_exists('grace_period_days', 'fine_rules')) {
                // If only grace_period is present, pick rules where grace_period_days <= days_late
                $db->where('grace_period_days <=', $days);
            } else {
                // No days columns present; don't add day constraints
            }
        };
        
        // Get product-specific rule first
        $query = $this->db->where('fine_type', 'loan_late')
                          ->where('loan_product_id', $installment->loan_product_id)
                          ->where('is_active', 1);
        $add_days_conditions($query, $days_late);
        $rule = $query->get('fine_rules')->row();
        
        if (!$rule) {
            // Get default rule (no product-specific)
            $query = $this->db->where('fine_type', 'loan_late')
                              ->where('loan_product_id IS NULL')
                              ->where('is_active', 1);
            $add_days_conditions($query, $days_late);
            $rule = $query->get('fine_rules')->row();
        }
        
        if (!$rule) return false;
        
        // Calculate fine
        $fine_amount = 0;
        $calc_type = isset($rule->calculation_type) ? $rule->calculation_type : 
                    (isset($rule->fine_type) && in_array($rule->fine_type, ['fixed', 'percentage', 'per_day']) ? $rule->fine_type : 'fixed');
        
        if ($calc_type === 'fixed') {
            $fine_amount = $rule->fine_value;
        } elseif ($calc_type === 'percentage') {
            $base = $rule->calculation_base === 'principal' ? $installment->principal_amount : $installment->emi_amount;
            $fine_amount = $base * ($rule->fine_value / 100);
        } elseif ($calc_type === 'per_day') {
            // For per_day: initial fixed amount + per day rate * (days_late - 1)
            // Example: fine_value = 100, per_day_amount = 10
            // 1 day late: 100
            // 2 days late: 100 + 10 = 110
            // 3 days late: 100 + 10 + 10 = 120
            if ($days_late >= 1) {
                $fine_amount = $rule->fine_value + ($rule->per_day_amount * ($days_late - 1));
            }
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
     * Bug #7 Fix: Check date to prevent duplicate fines on same day
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
        
        // Check if already fined on this date (Bug #7 Fix)
        $existing = $this->db->where('related_type', 'savings_schedule')
                             ->where('related_id', $schedule_id)
                             ->where('fine_date', date('Y-m-d'))
                             ->count_all_results($this->table);
        
        if ($existing > 0) return false;
        
        $days_late = floor((safe_timestamp(date('Y-m-d')) - safe_timestamp($schedule->due_date)) / 86400);
        
        // Build query with conditional day constraints
        $query = $this->db->where('fine_type', 'savings_late')
                          ->where('is_active', 1);
        if ($this->db->field_exists('min_days', 'fine_rules')) {
            $query->where('min_days <=', $days_late)
                  ->where('max_days >=', $days_late);
        } elseif ($this->db->field_exists('grace_period_days', 'fine_rules')) {
            $query->where('grace_period_days <=', $days_late);
        }
        
        $rule = $query->get('fine_rules')->row();
        
        if (!$rule) return false;
        
        // Calculate fine
        $fine_amount = 0;
        $calc_type = isset($rule->calculation_type) ? $rule->calculation_type : 
                    (isset($rule->fine_type) && in_array($rule->fine_type, ['fixed', 'percentage', 'per_day']) ? $rule->fine_type : 'fixed');
        
        if ($calc_type === 'fixed') {
            $fine_amount = $rule->fine_value ?? ($rule->fine_amount ?? 0);
        } elseif ($calc_type === 'percentage') {
            $rate = $rule->fine_value ?? ($rule->fine_rate ?? 0);
            $fine_amount = $schedule->due_amount * ($rate / 100);
        } elseif ($calc_type === 'per_day') {
            // For per_day: initial fixed amount + per day rate * (days_late - 1)
            // Example: fine_value = 100, per_day_amount = 10
            // 1 day late: 100
            // 2 days late: 100 + 10 = 110
            // 3 days late: 100 + 10 + 10 = 120
            if ($days_late >= 1) {
                $fine_amount = $rule->fine_value + ($rule->per_day_amount * ($days_late - 1));
            }
        } else {
            // Fallback: try to use flexible calculate_fine_amount helper
            $fine_amount = $this->calculate_fine_amount($rule, $days_late, $schedule->due_amount);
        }
        
        if (!empty($rule->max_fine_amount) && $rule->max_fine_amount > 0 && $fine_amount > $rule->max_fine_amount) {
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
        
        // Choose ordering column based on schema compatibility
        $order_col = 'id';
        if ($this->db->field_exists('min_days', 'fine_rules')) {
            $order_col = 'min_days';
        } elseif ($this->db->field_exists('grace_period_days', 'fine_rules')) {
            $order_col = 'grace_period_days';
        }
        
        $rules = $this->db->order_by('fine_type', 'ASC')
                          ->order_by($order_col, 'ASC')
                          ->get('fine_rules')
                          ->result();
        
        // Normalize fields for backward compatibility with views
        foreach ($rules as $r) {
            // fine_amount alias
            if (!isset($r->fine_amount)) {
                if (isset($r->fine_value)) $r->fine_amount = $r->fine_value;
                elseif (isset($r->fixed_amount)) $r->fine_amount = $r->fixed_amount;
                else $r->fine_amount = 0;
            }
            // min_days / grace_period_days
            if (!isset($r->min_days)) {
                if (isset($r->grace_period_days)) $r->min_days = $r->grace_period_days;
                elseif (isset($r->grace_period)) $r->min_days = $r->grace_period;
                else $r->min_days = 0;
            }
            // max_days fallback
            if (!isset($r->max_days)) {
                $r->max_days = $r->max_days ?? 99999;
            }
            // max_fine alias
            if (!isset($r->max_fine)) {
                if (isset($r->max_fine_amount)) $r->max_fine = $r->max_fine_amount;
                else $r->max_fine = null;
            }

            // Provide view-friendly properties for backward compatibility
            // amount_type: 'percentage' or 'fixed'
            if (!isset($r->amount_type)) {
                if (isset($r->calculation_type) && $r->calculation_type === 'percentage') {
                    $r->amount_type = 'percentage';
                } elseif (isset($r->percentage_value)) {
                    $r->amount_type = 'percentage';
                } else {
                    $r->amount_type = 'fixed';
                }
            }

            // amount_value: percentage rate or fixed amount
            if (!isset($r->amount_value)) {
                if (isset($r->percentage_value)) {
                    $r->amount_value = $r->percentage_value;
                } elseif (isset($r->per_day_amount)) {
                    // Prefer per_day_amount when present
                    $r->amount_value = $r->per_day_amount;
                } elseif (isset($r->fine_value)) {
                    $r->amount_value = $r->fine_value;
                } elseif (isset($r->fine_amount)) {
                    $r->amount_value = $r->fine_amount;
                } elseif (isset($r->fixed_amount)) {
                    $r->amount_value = $r->fixed_amount;
                } else {
                    $r->amount_value = 0;
                }
            }

            // grace_days (used by views)
            if (!isset($r->grace_days)) {
                if (isset($r->grace_period_days)) $r->grace_days = $r->grace_period_days;
                elseif (isset($r->min_days)) $r->grace_days = $r->min_days;
                elseif (isset($r->grace_period)) $r->grace_days = $r->grace_period;
                else $r->grace_days = 0;
            }

            // frequency for display / form defaults
            if (!isset($r->frequency)) {
                if (isset($r->per_day_amount) && $r->per_day_amount > 0) $r->frequency = 'daily';
                else $r->frequency = $r->frequency ?? 'one_time';
            }
        }
        
        return $rules;
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
     * Request Waiver for a fine (stores reason and requested_by/at).
     * Returns true on success.
     */
    public function request_waiver($fine_id, $reason, $requested_by, $amount = null) {
        if (empty($fine_id) || empty($reason)) return false;
        $data = [
            'waiver_reason' => $reason,
            'waiver_requested_by' => $requested_by,
            'waiver_requested_at' => date('Y-m-d H:i:s'),
            'waiver_requested_amount' => $amount !== null ? (float) $amount : null,
            'waiver_approved_by' => null,
            'waiver_approved_at' => null,
            'waiver_denied_by' => null,
            'waiver_denied_at' => null,
            'waiver_denied_reason' => null
        ];
        return $this->db->where('id', $fine_id)->update($this->table, $data);
    }

    /**
     * Get pending waiver requests
     */
    public function get_waiver_requests() {
        return $this->db->select('f.*, m.member_code, m.first_name, m.last_name')
                        ->from($this->table . ' f')
                        ->join('members m', 'm.id = f.member_id')
                        ->where('f.waiver_reason IS NOT NULL')
                        ->where('f.waiver_approved_by IS NULL')
                        ->where('f.waived_by IS NULL')
                        ->where('f.waiver_denied_by IS NULL')
                        ->order_by('f.waiver_requested_at', 'DESC')
                        ->get()
                        ->result();
    }

    /**
     * Get waiver request status for a specific fine and member
     */
    public function get_member_waiver_request($fine_id, $member_id) {
        // Build select list conditionally so code works even if `admin_comments` column isn't present in older schemas
        $select = 'f.id, f.waiver_reason, f.waiver_requested_at, f.waiver_requested_amount, f.waiver_approved_at, f.waiver_denied_at, f.waiver_denied_reason, f.status as fine_status';
        if ($this->db->field_exists('admin_comments', 'fines')) {
            $select .= ', f.admin_comments';
        } else {
            // Provide a NULL alias so views can safely reference ->admin_comments without error
            $select .= ', NULL as admin_comments';
        }

        return $this->db->select($select, false)
                        ->select('au.full_name as reviewer_name, au2.full_name as denier_name')
                        ->from('fines f')
                        ->join('admin_users au', 'au.id = f.waiver_approved_by', 'left')
                        ->join('admin_users au2', 'au2.id = f.waiver_denied_by', 'left')
                        ->where('f.id', $fine_id)
                        ->where('f.member_id', $member_id)
                        ->where('f.waiver_requested_at IS NOT NULL')
                        ->get()
                        ->row();
    }

    /**
     * Approve a waiver request and apply waiver amount
     */
    public function approve_waiver($fine_id, $amount, $approved_by) {
        $fine = $this->get_by_id($fine_id);
        if (!$fine) return false;
        $amount = (float) $amount;
        if ($amount <= 0) return false;

        $new_waived = (float) ($fine->waived_amount ?? 0) + $amount;
        $new_balance = (float) ($fine->balance_amount ?? 0) - $amount;
        if ($new_balance < 0) $new_balance = 0;

        $update = [
            'waived_amount' => $new_waived,
            'balance_amount' => $new_balance,
            'waived_by' => $approved_by,
            'waived_at' => date('Y-m-d H:i:s'),
            'waiver_approved_by' => $approved_by,
            'waiver_approved_at' => date('Y-m-d H:i:s')
        ];

        // Update status if fully waived
        if ($new_balance == 0) {
            $update['status'] = 'waived';
        }

        return $this->db->where('id', $fine_id)->update($this->table, $update);
    }

    /**
     * Deny a waiver request (store denial info)
     */
    public function deny_waiver($fine_id, $denied_by, $reason = null) {
        $update = [
            'waiver_denied_by' => $denied_by,
            'waiver_denied_at' => date('Y-m-d H:i:s'),
            'waiver_denied_reason' => $reason,
            'waiver_reason' => null
        ];
        return $this->db->where('id', $fine_id)->update($this->table, $update);
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
     * Calculate fine for a specific fine type and overdue details
     * Used by cron job to automatically apply fines
     * 
     * @param string $fine_type 'loan_late' or 'savings_late'
     * @param float $outstanding_amount Outstanding amount
     * @param int $days_overdue Days overdue
     * @return float Fine amount
     */
    public function calculate_fine($fine_type, $outstanding_amount, $days_overdue) {
        // Get applicable rule
        $rule = $this->db->where('fine_type', $fine_type)
                        ->where('is_active', 1)
                        ->order_by('grace_period_days', 'DESC')
                        ->limit(1)
                        ->get('fine_rules')
                        ->row();
        
        if (!$rule) {
            return 0; // No rule configured
        }
        
        // Normalize rule object for calculate_fine_amount
        $normalized_rule = (object)[
            'grace_period' => $rule->grace_period_days ?? 0,
            'fine_type' => $rule->calculation_type ?? 'fixed',
            'fine_amount' => $rule->fine_value ?? 0,
            'fine_rate' => $rule->fine_rate ?? 0,
            'per_day_amount' => $rule->per_day_amount ?? 0,
            'max_fine' => $rule->max_fine_amount ?? 0
        ];
        
        return $this->calculate_fine_amount($normalized_rule, $days_overdue, $outstanding_amount);
    }
    
    /**
     * Update Daily Fines
     * Called daily to update fine amounts for per_day and fixed_plus_daily rules
     */
    public function update_daily_fines($created_by = 1) {
        $updated = 0;
        
        // Get all pending fines that use daily calculation
        $pending_fines = $this->db->select('f.*, fr.fine_type, fr.fine_value as fine_amount, fr.per_day_amount, fr.grace_period_days as grace_period, fr.max_fine_amount as max_fine')
                                  ->from('fines f')
                                  ->join('fine_rules fr', 'fr.id = f.fine_rule_id')
                                  ->where_in('f.status', ['pending', 'partial'])
                                  ->where_in('fr.fine_type', ['per_day', 'fixed_plus_daily'])
                                  ->get()
                                  ->result();
        
        foreach ($pending_fines as $fine) {
            $days_late = floor((safe_timestamp(date('Y-m-d')) - safe_timestamp($fine->due_date)) / 86400);
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
