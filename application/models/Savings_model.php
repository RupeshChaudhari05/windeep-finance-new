<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Savings_model - Savings Management
 */
class Savings_model extends MY_Model {
    
    protected $table = 'savings_accounts';
    protected $primary_key = 'id';
    
    /**
     * Generate Account Number
     */
    public function generate_account_number() {
        $prefix = 'SAV';
        $year = date('Y');
        
        $max = $this->db->select_max('id')
                        ->get($this->table)
                        ->row();
        
        $next = ($max->id ?? 0) + 1;
        
        return $prefix . $year . str_pad($next, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Create Savings Account
     */
    public function create_account($data) {
        $this->db->trans_begin();
        
        try {
            // Generate account number
            if (empty($data['account_number'])) {
                $data['account_number'] = $this->generate_account_number();
            }
            
            $data['created_at'] = date('Y-m-d H:i:s');
            
            $this->db->insert($this->table, $data);
            $account_id = $this->db->insert_id();
            
            // Generate schedule for upcoming months
            if ($account_id) {
                $this->generate_schedule($account_id, $data['start_date'], $data['monthly_amount']);
            }
            
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return false;
            }
            
            $this->db->trans_commit();
            return $account_id;
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            return false;
        }
    }
    
    /**
     * Generate Monthly Schedule
     */
    public function generate_schedule($account_id, $start_date, $monthly_amount, $months = 12) {
        $start = new DateTime($start_date);
        $start->modify('first day of this month');

        // Determine due_day from scheme if available
        $account = $this->db->where('id', $account_id)->get($this->table)->row();
        $due_day = 1;
        if ($account && !empty($account->scheme_id)) {
            $scheme = $this->db->where('id', $account->scheme_id)->get('savings_schemes')->row();
            if ($scheme && isset($scheme->due_day) && is_numeric($scheme->due_day)) {
                $due_day = (int) $scheme->due_day;
            }
        }

        for ($i = 0; $i < $months; $i++) {
            // move to first day of the current month in loop
            $cur = clone $start;
            $cur->modify("+$i month");
            $cur->modify('first day of this month');

            // clamp due day to last day of month
            $last_day = (int) $cur->format('t');
            $day = min(max(1, $due_day), $last_day);

            $due_month = $cur->format('Y-m-01');
            $due_date = $cur->format('Y-m-') . str_pad($day, 2, '0', STR_PAD_LEFT);

            // Check if schedule already exists
            $exists = $this->db->where('savings_account_id', $account_id)
                               ->where('due_month', $due_month)
                               ->count_all_results('savings_schedule');

            if (!$exists) {
                $this->db->insert('savings_schedule', [
                    'savings_account_id' => $account_id,
                    'due_month' => $due_month,
                    'due_amount' => $monthly_amount,
                    'due_date' => $due_date,
                    'status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        }

        return true;
    }
    
    /**
     * Record Payment
     */
    public function record_payment($data) {
        $this->db->trans_begin();
        
        try {
            $account = $this->get_by_id($data['savings_account_id']);
            
            if (!$account) {
                throw new Exception('Savings account not found');
            }
            
            // Generate transaction code
            $data['transaction_code'] = 'STX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            
            // Calculate new balance
            $new_balance = $account->current_balance;
            
            if ($data['transaction_type'] === 'deposit') {
                $new_balance += $data['amount'];
            } elseif ($data['transaction_type'] === 'withdrawal') {
                if ($data['amount'] > $account->current_balance) {
                    throw new Exception('Insufficient balance');
                }
                $new_balance -= $data['amount'];
            }
            
            $data['balance_after'] = $new_balance;
            $data['created_at'] = date('Y-m-d H:i:s');
            
            // Insert transaction
            $this->db->insert('savings_transactions', $data);
            $transaction_id = $this->db->insert_id();
            
            // Update account balance
            $update_data = ['current_balance' => $new_balance, 'updated_at' => date('Y-m-d H:i:s')];
            
            if ($data['transaction_type'] === 'deposit') {
                $update_data['total_deposited'] = $account->total_deposited + $data['amount'];
            }
            
            $this->db->where('id', $data['savings_account_id'])
                     ->update($this->table, $update_data);
            
            // Update schedule if applicable
            if (!empty($data['schedule_id'])) {
                $this->update_schedule_payment($data['schedule_id'], $data['amount']);
            }
            
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return false;
            }
            
            $this->db->trans_commit();
            return $transaction_id;
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            throw $e;
        }
    }
    
    /**
     * Update Schedule Payment
     */
    private function update_schedule_payment($schedule_id, $amount) {
        $schedule = $this->db->where('id', $schedule_id)
                             ->get('savings_schedule')
                             ->row();
        
        if (!$schedule) return false;
        
        $new_paid = $schedule->paid_amount + $amount;
        $status = 'partial';
        
        if ($new_paid >= $schedule->due_amount) {
            $status = 'paid';
        }
        
        // Check if late
        $is_late = (safe_timestamp(date('Y-m-d')) > safe_timestamp($schedule->due_date));
        $days_late = 0;
        
        if ($is_late && $status === 'paid') {
            $days_late = floor((safe_timestamp(date('Y-m-d')) - safe_timestamp($schedule->due_date)) / 86400);
        }
        
        return $this->db->where('id', $schedule_id)
                        ->update('savings_schedule', [
                            'paid_amount' => $new_paid,
                            'status' => $status,
                            'paid_date' => date('Y-m-d'),
                            'is_late' => $is_late ? 1 : 0,
                            'days_late' => $days_late,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
    }
    
    /**
     * Get Member Savings Accounts
     */
    public function get_member_accounts($member_id) {
        return $this->db->select('sa.*, ss.scheme_name')
                        ->from('savings_accounts sa')
                        ->join('savings_schemes ss', 'ss.id = sa.scheme_id')
                        ->where('sa.member_id', $member_id)
                        ->order_by('sa.created_at', 'DESC')
                        ->get()
                        ->result();
    }
    
    /**
     * Get Pending Dues
     */
    public function get_pending_dues($month = null) {
        $this->db->select('
            sch.*, 
            sa.account_number, sa.member_id,
            m.member_code, m.first_name, m.last_name, m.phone,
            ss.scheme_name
        ');
        $this->db->from('savings_schedule sch');
        $this->db->join('savings_accounts sa', 'sa.id = sch.savings_account_id');
        $this->db->join('members m', 'm.id = sa.member_id');
        $this->db->join('savings_schemes ss', 'ss.id = sa.scheme_id');
        $this->db->where_in('sch.status', ['pending', 'partial', 'overdue']);
        
        if ($month) {
            $this->db->where('sch.due_month', $month);
        }
        
        $this->db->order_by('sch.due_date', 'ASC');
        
        $result = $this->db->get()->result();
        
        // Add computed member_name
        foreach ($result as &$item) {
            $item->member_name = trim(($item->first_name ?? '') . ' ' . ($item->last_name ?? '')) ?: ($item->member_code ?? '');
        }
        
        return $result;
    }
    
    /**
     * Get Overdue Payments
     */
    public function get_overdue() {
        return $this->db->select('
            sch.*, 
            sa.account_number, sa.member_id,
            m.member_code, m.first_name, m.last_name, m.phone
        ')
        ->from('savings_schedule sch')
        ->join('savings_accounts sa', 'sa.id = sch.savings_account_id')
        ->join('members m', 'm.id = sa.member_id')
        ->where_in('sch.status', ['pending', 'partial'])
        ->where('sch.due_date <', date('Y-m-d'))
        ->order_by('sch.due_date', 'ASC')
        ->get()
        ->result();
    }
    
    /**
     * Get Account Schedule
     */
    public function get_schedule($account_id, $year = null) {
        $this->db->where('savings_account_id', $account_id);
        
        if ($year) {
            $this->db->where('YEAR(due_month)', $year);
        }
        
        return $this->db->order_by('due_month', 'ASC')
                        ->get('savings_schedule')
                        ->result();
    }
    
    /**
     * Get Account Transactions
     */
    public function get_transactions($account_id, $limit = 50) {
        return $this->db->where('savings_account_id', $account_id)
                        ->order_by('created_at', 'DESC')
                        ->limit($limit)
                        ->get('savings_transactions')
                        ->result();
    }
    
    /**
     * Get Monthly Collection Summary
     */
    public function get_monthly_collection($month) {
        return $this->db->select('
            COUNT(DISTINCT sa.member_id) as total_members,
            SUM(sch.due_amount) as total_due,
            SUM(sch.paid_amount) as total_collected,
            SUM(sch.fine_amount) as total_fine,
            SUM(sch.fine_paid) as fine_collected
        ')
        ->from('savings_schedule sch')
        ->join('savings_accounts sa', 'sa.id = sch.savings_account_id')
        ->where('sch.due_month', $month)
        ->get()
        ->row();
    }
    
    /**
     * Apply Late Fine
     */
    public function apply_late_fine($schedule_id, $fine_amount, $created_by) {
        $schedule = $this->db->where('id', $schedule_id)
                             ->get('savings_schedule')
                             ->row();
        
        if (!$schedule) return false;
        
        // Update schedule
        $this->db->where('id', $schedule_id)
                 ->update('savings_schedule', [
                     'fine_amount' => $schedule->fine_amount + $fine_amount,
                     'status' => 'overdue',
                     'updated_at' => date('Y-m-d H:i:s')
                 ]);
        
        // Get account and member
        $account = $this->get_by_id($schedule->savings_account_id);
        
        // Create fine record
        $this->db->insert('fines', [
            'fine_code' => 'FIN-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6)),
            'member_id' => $account->member_id,
            'fine_type' => 'savings_late',
            'related_type' => 'savings_schedule',
            'related_id' => $schedule_id,
            'fine_date' => date('Y-m-d'),
            'due_date' => $schedule->due_date,
            'days_late' => floor((safe_timestamp(date('Y-m-d')) - safe_timestamp($schedule->due_date)) / 86400),
            'fine_amount' => $fine_amount,
            'balance_amount' => $fine_amount,
            'status' => 'pending',
            'created_by' => $created_by,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return $this->db->insert_id();
    }
    
    /**
     * Get All Schemes
     */
    public function get_schemes($active_only = true) {
        if ($active_only) {
            $this->db->where('is_active', 1);
        }
        
        return $this->db->get('savings_schemes')->result();
    }
    
    /**
     * Get Dashboard Stats
     */
    public function get_dashboard_stats() {
        $stats = [];
        
        // Total Savings
        $stats['total_savings'] = $this->db->select_sum('current_balance')
                                           ->where('status', 'active')
                                           ->get($this->table)
                                           ->row()
                                           ->current_balance ?? 0;
        
        // Active Accounts
        $stats['active_accounts'] = $this->db->where('status', 'active')
                                             ->count_all_results($this->table);
        
        // This Month Collection
        $month = date('Y-m-01');
        $stats['month_collection'] = $this->db->select_sum('paid_amount')
                                               ->where('due_month', $month)
                                               ->get('savings_schedule')
                                               ->row()
                                               ->paid_amount ?? 0;
        
        // Pending Dues
        $stats['pending_dues'] = $this->db->select('SUM(due_amount - paid_amount) as pending')
                                          ->where_in('status', ['pending', 'partial', 'overdue'])
                                          ->get('savings_schedule')
                                          ->row()
                                          ->pending ?? 0;
        
        return $stats;
    }
}
