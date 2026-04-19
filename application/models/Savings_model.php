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
     * SAV-5 FIX: Accept actual insert_id to avoid MAX(id)+1 race condition.
     * If $insert_id is null, falls back to MAX(id)+1 (pre-insert estimate only).
     */
    public function generate_account_number($insert_id = null) {
        $prefix = 'SAV';
        $year = date('Y');
        
        if ($insert_id) {
            $next = $insert_id;
        } else {
            $max = $this->db->select_max('id')
                            ->get($this->table)
                            ->row();
            $next = ($max->id ?? 0) + 1;
        }
        
        return $prefix . $year . str_pad($next, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Auto-enroll a new member in the default savings scheme
     */
    /**
     * Ensure a default scheme exists; if not, create "Regular Scheme" (₹2000/mo, 0% interest).
     * Returns the default scheme object, or false on failure.
     */
    public function get_or_create_default_scheme($created_by = null) {
        $scheme = $this->db->where('is_default', 1)
                           ->where('is_active', 1)
                           ->get('savings_schemes')
                           ->row();
        if ($scheme) return $scheme;

        // No default scheme — create one automatically
        $this->load->model('Savings_scheme_model');
        $scheme_id = $this->Savings_scheme_model->save_scheme([
            'scheme_name'       => 'Regular Scheme',
            'description'       => 'Default savings scheme — auto-created',
            'monthly_amount'    => 2000.00,
            'min_deposit'       => 2000.00,
            'interest_rate'     => 0.00,
            'deposit_frequency' => 'monthly',
            'lock_in_period'    => 0,
            'penalty_rate'      => 0.00,
            'maturity_bonus'    => 0.00,
            'due_day'           => 1,
            'is_default'        => 1,
            'is_active'         => 1,
            'created_by'        => $created_by,
        ]);

        if (!$scheme_id) return false;

        // Mark it as default (save_scheme doesn't set is_default/is_active — do it directly)
        $this->db->where('id', $scheme_id)->update('savings_schemes', [
            'is_default' => 1,
            'is_active'  => 1,
        ]);

        return $this->db->where('id', $scheme_id)->get('savings_schemes')->row();
    }

    public function enroll_in_default_scheme($member_id, $created_by = null, $start_date = null) {
        $scheme = $this->get_or_create_default_scheme($created_by);
        if (!$scheme) return false;

        // Check if member already has an account in this scheme
        $existing = $this->db->where('member_id', $member_id)
                             ->where('scheme_id', $scheme->id)
                             ->where_in('status', ['active', 'matured'])
                             ->get($this->table)
                             ->row();

        if ($existing) {
            return $existing->id; // Already enrolled
        }

        if (empty($start_date)) {
            $start_date = date('Y-m-d');
        }
        $maturity_date = null;
        if (!empty($scheme->duration_months)) {
            $maturity_date = date('Y-m-d', strtotime($start_date . " +{$scheme->duration_months} months"));
        }

        return $this->create_account([
            'member_id'      => $member_id,
            'scheme_id'      => $scheme->id,
            'monthly_amount' => $scheme->monthly_amount,
            'start_date'     => $start_date,
            'maturity_date'  => $maturity_date,
            'status'         => 'active',
            'created_by'     => $created_by,
        ]);
    }

    /**
     * Create Savings Account
     */
    public function create_account($data) {
        $this->db->trans_begin();
        
        try {
            // SAV-5 FIX: Insert with a temporary account number, then update with real insert_id
            if (empty($data['account_number'])) {
                // Must fit VARCHAR(20): 'T'+13-char uniqid = 14 chars
                $data['account_number'] = 'T' . uniqid();
            }
            
            $data['created_at'] = date('Y-m-d H:i:s');
            
            $this->db->insert($this->table, $data);
            $account_id = $this->db->insert_id();
            
            if ($account_id) {
                // Now generate the real account number from the guaranteed-unique insert_id
                $real_account_number = $this->generate_account_number($account_id);
                $this->db->where('id', $account_id)
                         ->update($this->table, ['account_number' => $real_account_number]);

                // For one-time schemes create a single schedule entry; otherwise generate monthly schedule
                $scheme_frequency = 'monthly';
                if (!empty($data['scheme_id'])) {
                    $scheme_row = $this->db->where('id', $data['scheme_id'])->get('savings_schemes')->row();
                    if ($scheme_row) $scheme_frequency = $scheme_row->deposit_frequency ?? 'monthly';
                }

                if ($scheme_frequency === 'onetime') {
                    $this->generate_onetime_schedule($account_id, $data['start_date'], $data['monthly_amount']);
                } else {
                    $this->generate_schedule($account_id, $data['start_date'], $data['monthly_amount']);
                }
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
     * Generate One-Time Schedule
     * Creates a single savings_schedule entry — member pays once and the account is done.
     */
    public function generate_onetime_schedule($account_id, $start_date, $amount) {
        $due_date = date('Y-m-d', strtotime($start_date));
        $due_month = date('Y-m-01', strtotime($start_date));

        $exists = $this->db->where('savings_account_id', $account_id)
                           ->count_all_results('savings_schedule');
        if (!$exists) {
            $this->db->insert('savings_schedule', [
                'savings_account_id' => $account_id,
                'due_month'          => $due_month,
                'due_amount'         => $amount,
                'due_date'           => $due_date,
                'status'             => 'pending',
                'created_at'         => date('Y-m-d H:i:s'),
            ]);
        }
        return true;
    }

    /**
     * Generate Monthly Schedule
     */
    public function generate_schedule($account_id, $start_date, $monthly_amount, $months = 12) {
        $start = new DateTime($start_date);
        $start->modify('first day of this month');

        // Determine due_day from global settings or scheme
        $this->load->model('Setting_model');
        $force_fixed = $this->Setting_model->get_setting('force_fixed_due_day', false);
        $fixed_day = (int) $this->Setting_model->get_setting('fixed_due_day', 0);
        
        $due_day = 1;
        
        // Priority: Global fixed due day > Scheme due day > Default (1)
        if ($force_fixed && $fixed_day >= 1 && $fixed_day <= 28) {
            $due_day = $fixed_day;
        } else {
            $account = $this->db->where('id', $account_id)->get($this->table)->row();
            if ($account && !empty($account->scheme_id)) {
                $scheme = $this->db->where('id', $account->scheme_id)->get('savings_schemes')->row();
                if ($scheme && isset($scheme->due_day) && is_numeric($scheme->due_day)) {
                    $due_day = (int) $scheme->due_day;
                }
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
            // SAV-2 FIX: Lock the account row to prevent concurrent balance corruption
            $account = $this->db->query(
                "SELECT * FROM {$this->table} WHERE id = ? FOR UPDATE",
                [$data['savings_account_id']]
            )->row();
            
            if (!$account) {
                throw new Exception('Savings account not found');
            }

            // SAV-7 FIX: Verify account is active before accepting payment
            if ($account->status !== 'active') {
                throw new Exception('Cannot record payment: account status is "' . $account->status . '". Only active accounts accept deposits/withdrawals.');
            }

            // SAV-10: Validate amount > 0
            if (!isset($data['amount']) || $data['amount'] <= 0) {
                throw new Exception('Payment amount must be greater than zero.');
            }
            
            // Generate transaction code
            $data['transaction_code'] = 'STX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            
            // Calculate new balance using the locked row's current value
            $new_balance = $account->current_balance;
            
            if ($data['transaction_type'] === 'deposit') {
                // SAV-6 FIX (updated): Cap deposits at 24x monthly amount to allow up to 2-year advance payment
                // Skip this cap for bank_transfer (admin-mapped bank statement entries are already verified)
                $payment_mode = $data['payment_mode'] ?? 'cash';
                if ($payment_mode !== 'bank_transfer') {
                    $max_deposit = $account->monthly_amount * 24;
                    if ($max_deposit > 0 && $data['amount'] > $max_deposit) {
                        $months = floor($data['amount'] / $account->monthly_amount);
                        throw new Exception('Deposit amount (' . number_format($data['amount'], 2) . ') would cover ' . $months . ' months, which exceeds the maximum 24-month advance limit. Use bank_transfer mode for exceptional cases.');
                    }
                }
                $new_balance += $data['amount'];
            } elseif ($data['transaction_type'] === 'withdrawal') {
                if ($data['amount'] > $account->current_balance) {
                    throw new Exception('Insufficient balance');
                }
                $new_balance -= $data['amount'];
            }
            
            $data['balance_after'] = $new_balance;
            $data['created_at'] = date('Y-m-d H:i:s');
            
            // Map controller field names to actual DB column names
            if (isset($data['remarks'])) {
                $data['narration'] = $data['remarks'];
                unset($data['remarks']);
            }
            if (isset($data['received_by'])) {
                $data['created_by'] = $data['received_by'];
                unset($data['received_by']);
            }

            // Default transaction_date to today if not provided
            if (empty($data['transaction_date'])) {
                $data['transaction_date'] = date('Y-m-d');
            }

            // Whitelist only columns that exist in savings_transactions
            $allowed = ['transaction_code', 'savings_account_id', 'schedule_id', 'transaction_type',
                        'amount', 'balance_after', 'payment_mode', 'reference_number',
                        'transaction_date', 'for_month', 'narration', 'receipt_number',
                        'bank_transaction_id', 'created_by', 'created_at'];
            $insert = array_intersect_key($data, array_flip($allowed));

            // Insert transaction
            $this->db->insert('savings_transactions', $insert);
            $transaction_id = $this->db->insert_id();
            
            // SAV-2 FIX: Use atomic SQL update instead of PHP-side arithmetic
            if ($data['transaction_type'] === 'deposit') {
                $this->db->query(
                    "UPDATE {$this->table}
                        SET current_balance = current_balance + ?,
                            total_deposited = total_deposited + ?,
                            updated_at = NOW()
                      WHERE id = ?",
                    [$data['amount'], $data['amount'], $data['savings_account_id']]
                );
            } elseif ($data['transaction_type'] === 'withdrawal') {
                $this->db->query(
                    "UPDATE {$this->table}
                        SET current_balance = current_balance - ?,
                            updated_at = NOW()
                      WHERE id = ?",
                    [$data['amount'], $data['savings_account_id']]
                );
            }
            
            // ── Schedule update (FIFO – Indian banking standard) ──────────────────
            // If a specific schedule entry was targeted, apply to it (with overflow carry-forward).
            // Otherwise auto-apply against the oldest pending/overdue entries first.
            if ($data['transaction_type'] === 'deposit') {
                $payment_date = $data['transaction_date'] ?? date('Y-m-d');
                if (!empty($data['schedule_id'])) {
                    $this->update_schedule_payment($data['schedule_id'], $data['amount'], $payment_date);
                } else {
                    $this->auto_apply_deposit_fifo($data['savings_account_id'], $data['amount'], $payment_date);
                }
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
     * Update Schedule Payment for a specific entry, then carry forward any overpayment.
     * Indian banking standard: oldest dues settled first (FIFO).
     *
     * @param int    $schedule_id  savings_schedule.id to apply this payment to
     * @param float  $amount       amount being applied
     * @param string $payment_date actual payment date (YYYY-MM-DD); defaults to today
     */
    private function update_schedule_payment($schedule_id, $amount, $payment_date = null) {
        if ($payment_date === null) $payment_date = date('Y-m-d');

        $schedule = $this->db->query(
            "SELECT * FROM savings_schedule WHERE id = ? FOR UPDATE",
            [$schedule_id]
        )->row();

        if (!$schedule) return false;

        $remaining_due = (float)$schedule->due_amount - (float)$schedule->paid_amount;
        $applied       = min((float)$amount, max(0.0, $remaining_due));
        $overpayment   = (float)$amount - $applied;

        $new_paid = (float)$schedule->paid_amount + $applied;
        $status   = ($new_paid >= (float)$schedule->due_amount) ? 'paid' : 'partial';

        $is_late   = (safe_timestamp($payment_date) > safe_timestamp($schedule->due_date));
        $days_late = 0;
        if ($is_late && $status === 'paid') {
            $days_late = (int)floor((safe_timestamp($payment_date) - safe_timestamp($schedule->due_date)) / 86400);
        }

        $this->db->where('id', $schedule_id)
                 ->update('savings_schedule', [
                     'paid_amount' => round($new_paid, 2),
                     'status'      => $status,
                     'paid_date'   => $payment_date,
                     'is_late'     => $is_late ? 1 : 0,
                     'days_late'   => $days_late,
                     'updated_at'  => date('Y-m-d H:i:s')
                 ]);

        // Carry forward overpayment to the next pending/partial/overdue entry (FIFO)
        if ($overpayment > 0.009) {
            $next = $this->db->where('savings_account_id', $schedule->savings_account_id)
                             ->where('id !=', $schedule_id)
                             ->where_in('status', ['pending', 'partial', 'overdue'])
                             ->order_by('due_date', 'ASC')
                             ->get('savings_schedule')
                             ->row();
            if ($next) {
                $this->update_schedule_payment($next->id, $overpayment, $payment_date);
            }
            // Remaining overpayment beyond last due entry stays as advance credit balance
        }

        return true;
    }

    /**
     * Auto-apply a deposit against ALL pending/overdue schedule entries in chronological
     * order (oldest first — FIFO, Indian banking standard).
     * Called when no specific schedule_id is passed (e.g., member pays 3 months at once).
     *
     * @param int    $account_id
     * @param float  $amount       total deposit amount
     * @param string $payment_date YYYY-MM-DD
     */
    private function auto_apply_deposit_fifo($account_id, $amount, $payment_date) {
        // Fetch ALL pending/partial/overdue entries oldest-first (lock for concurrent safety)
        $pending = $this->db->query(
            "SELECT * FROM savings_schedule
              WHERE savings_account_id = ?
                AND status IN ('pending','partial','overdue')
              ORDER BY due_date ASC
              FOR UPDATE",
            [$account_id]
        )->result();

        $remaining = (float)$amount;

        foreach ($pending as $entry) {
            if ($remaining < 0.01) break;

            $remaining_due = (float)$entry->due_amount - (float)$entry->paid_amount;
            if ($remaining_due <= 0) continue;

            $applied      = min($remaining, $remaining_due);
            $new_paid     = (float)$entry->paid_amount + $applied;
            $remaining   -= $applied;

            $status    = ($new_paid >= (float)$entry->due_amount) ? 'paid' : 'partial';
            $is_late   = (safe_timestamp($payment_date) > safe_timestamp($entry->due_date));
            $days_late = 0;
            if ($is_late && $status === 'paid') {
                $days_late = (int)floor((safe_timestamp($payment_date) - safe_timestamp($entry->due_date)) / 86400);
            }

            $this->db->where('id', $entry->id)
                     ->update('savings_schedule', [
                         'paid_amount' => round($new_paid, 2),
                         'status'      => $status,
                         'paid_date'   => $payment_date,
                         'is_late'     => $is_late ? 1 : 0,
                         'days_late'   => $days_late,
                         'updated_at'  => date('Y-m-d H:i:s')
                     ]);
        }
        // Any $remaining > 0 stays as advance credit in the account balance (already deposited)
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
            // Ensure full DATE format YYYY-MM-DD (month input gives YYYY-MM)
            if (preg_match('/^\d{4}-\d{2}$/', $month)) {
                $month = $month . '-01';
            }
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
    public function get_transactions($account_id, $limit = 50, $include_reversed = FALSE) {
        // Order by transaction_date (passbook/statement date) not created_at (record-insertion timestamp).
        // This ensures mapped bank transactions appear on the date the money actually moved,
        // not the date the admin performed the mapping.
        $query = $this->db->where('savings_account_id', $account_id);
        if (!$include_reversed) {
            // By default exclude reversed transactions from passbook view.
            // Pass $include_reversed = TRUE to get full history (e.g. audit/admin).
            $query->where('is_reversed', 0);
        }
        return $query->order_by('transaction_date', 'DESC')
                     ->order_by('id', 'DESC') // tiebreaker for same-day entries
                     ->limit($limit)
                     ->get('savings_transactions')
                     ->result();
    }
    
    /**
     * Get Monthly Collection Summary
     */
    public function get_monthly_collection($month) {
        // Ensure full DATE format YYYY-MM-DD (month input gives YYYY-MM)
        if (preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = $month . '-01';
        }
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
        // SAV-3 FIX: Wrap in transaction for atomicity
        $this->db->trans_begin();

        try {
            $schedule = $this->db->query(
                "SELECT * FROM savings_schedule WHERE id = ? FOR UPDATE",
                [$schedule_id]
            )->row();

            if (!$schedule) {
                $this->db->trans_rollback();
                return false;
            }

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

            $fine_id = $this->db->insert_id();

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return false;
            }

            $this->db->trans_commit();
            return $fine_id;

        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'apply_late_fine failed for schedule ' . $schedule_id . ': ' . $e->getMessage());
            return false;
        }
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
     * Calculate monthly interest for a single savings account.
     * Uses simple interest: (balance * annual_rate / 100) / 12
     *
     * @param int $account_id
     * @return float Interest amount for the month
     */
    public function calculate_monthly_interest($account_id) {
        $account = $this->db->select('sa.current_balance, ss.interest_rate')
                            ->from('savings_accounts sa')
                            ->join('savings_schemes ss', 'ss.id = sa.scheme_id')
                            ->where('sa.id', $account_id)
                            ->where('sa.status', 'active')
                            ->get()
                            ->row();

        if (!$account || $account->current_balance <= 0 || $account->interest_rate <= 0) {
            return 0.00;
        }

        return round(($account->current_balance * $account->interest_rate / 100) / 12, 2);
    }

    /**
     * Accrue (calculate + post) monthly interest for ALL active savings accounts.
     * Intended to be called by a monthly cron job or CLI command.
     *
     * @param string $for_month  YYYY-MM-01 date string (defaults to current month)
     * @param int    $created_by Admin user ID performing the accrual
     * @return array  Summary with 'processed', 'credited', 'total_interest', 'errors'
     */
    public function accrue_monthly_interest($for_month = null, $created_by = null) {
        if (!$for_month) {
            $for_month = date('Y-m-01');
        }

        $accounts = $this->db->select('sa.id, sa.current_balance, sa.total_interest_earned, sa.member_id, sa.account_number, ss.interest_rate')
                             ->from('savings_accounts sa')
                             ->join('savings_schemes ss', 'ss.id = sa.scheme_id')
                             ->where('sa.status', 'active')
                             ->where('ss.interest_rate >', 0)
                             ->get()
                             ->result();

        $summary = ['processed' => 0, 'credited' => 0, 'total_interest' => 0, 'errors' => []];

        foreach ($accounts as $acc) {
            $summary['processed']++;

            // Skip if interest already credited for this month
            $already = $this->db->where('savings_account_id', $acc->id)
                                ->where('transaction_type', 'interest_credit')
                                ->where('for_month', $for_month)
                                ->count_all_results('savings_transactions');
            if ($already > 0) {
                continue;
            }

            $interest = round(($acc->current_balance * $acc->interest_rate / 100) / 12, 2);
            if ($interest <= 0) {
                continue;
            }

            $result = $this->post_interest_credit($acc->id, $interest, $for_month, $created_by);
            if ($result) {
                $summary['credited']++;
                $summary['total_interest'] += $interest;
            } else {
                $summary['errors'][] = 'Account ' . $acc->account_number . ': failed to credit interest';
            }
        }

        return $summary;
    }

    /**
     * Post an interest credit transaction for a single savings account.
     *
     * @param int    $account_id
     * @param float  $interest_amount
     * @param string $for_month  YYYY-MM-01
     * @param int    $created_by
     * @return int|false  Transaction ID on success
     */
    public function post_interest_credit($account_id, $interest_amount, $for_month, $created_by = null) {
        $this->db->trans_begin();

        try {
            // Lock the account row
            $account = $this->db->query(
                "SELECT * FROM {$this->table} WHERE id = ? FOR UPDATE",
                [$account_id]
            )->row();

            if (!$account || $account->status !== 'active') {
                $this->db->trans_rollback();
                return false;
            }

            $new_balance = $account->current_balance + $interest_amount;
            $new_interest_earned = ($account->total_interest_earned ?? 0) + $interest_amount;

            // Insert interest credit transaction
            $tx_code = 'INT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            $this->db->insert('savings_transactions', [
                'transaction_code'   => $tx_code,
                'savings_account_id' => $account_id,
                'transaction_type'   => 'interest_credit',
                'amount'             => $interest_amount,
                'balance_after'      => $new_balance,
                'payment_mode'       => 'auto',
                'transaction_date'   => date('Y-m-d'),
                'for_month'          => $for_month,
                'narration'          => 'Monthly interest credit for ' . date('M Y', strtotime($for_month)),
                'created_by'         => $created_by,
                'created_at'         => date('Y-m-d H:i:s')
            ]);
            $tx_id = $this->db->insert_id();

            // Update account balance and interest earned atomically
            $this->db->query(
                "UPDATE {$this->table}
                    SET current_balance = current_balance + ?,
                        total_interest_earned = total_interest_earned + ?,
                        updated_at = NOW()
                  WHERE id = ?",
                [$interest_amount, $interest_amount, $account_id]
            );

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return false;
            }

            $this->db->trans_commit();
            return $tx_id;

        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'Interest credit failed for account ' . $account_id . ': ' . $e->getMessage());
            return false;
        }
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
