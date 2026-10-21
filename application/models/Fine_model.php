<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Fine_model - Fine & Penalty Management
 */
class Fine_model extends MY_Model {
    
    protected $table = 'fines';
    protected $primary_key = 'id';
    
    // Cache schema checks to avoid repeated DESCRIBE queries
    private $_field_cache = [];
    
    /**
     * Check if a column exists in a table (cached per request)
     */
    private function _has_field($field, $table) {
        $key = $table . '.' . $field;
        if (!isset($this->_field_cache[$key])) {
            $this->_field_cache[$key] = $this->db->field_exists($field, $table);
        }
        return $this->_field_cache[$key];
    }
    
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
        // Manual fines aren't tied to an installment/schedule, but these columns
        // are NOT NULL with no default — fill them so the insert can't fail.
        $valid_types = ['savings_late', 'loan_late', 'bounced_cheque', 'other'];
        if (empty($data['fine_type']) || !in_array($data['fine_type'], $valid_types, true)) {
            $data['fine_type'] = 'other';
        }
        if (empty($data['related_type'])) { $data['related_type'] = 'other'; }
        if (!isset($data['related_id']) || $data['related_id'] === '' || $data['related_id'] === null) {
            $data['related_id'] = 0;
        }
        if (empty($data['due_date']))  { $data['due_date']  = $data['fine_date'] ?? date('Y-m-d'); }
        if (!isset($data['days_late'])) { $data['days_late'] = 0; }

        $data['fine_code'] = $this->generate_fine_code();
        $data['balance_amount'] = $data['fine_amount'];
        $data['status'] = 'pending';
        $data['created_at'] = date('Y-m-d H:i:s');
        
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }
    
    /* ==================================================================
     * LATE-FINE ENGINE
     *
     * - One fine row per overdue loan installment / savings schedule.
     * - The rule used is the one in force on the item's DUE DATE
     *   (effective_from <= due_date and effective_to empty or >= due_date),
     *   and it stays locked to the fine through fine_rule_id. Old arrears that
     *   fell due before any rule existed are never fined retroactively.
     * - Days late run from the due date to the payment date (or today while
     *   unpaid), so a per-day fine stops growing the day the EMI is paid.
     * - Paid / waived amounts are never undone by a recalculation.
     * ================================================================== */

    /** Stats of the last run_late_fine_job() call (also used for the preview page) */
    public $last_run = [];

    private $_rules = null;
    private $_rule_by_id = [];

    /**
     * Canonical calculation type of a rule: fixed | percentage | per_day | slab.
     * 'fixed_plus_daily' (older settings screen) is the same formula as per_day.
     */
    public function rule_type($rule) {
        $t = (string) ($rule->calculation_type ?? '');
        if ($t === '') $t = (string) ($rule->fine_type ?? '');
        if ($t === 'fixed_plus_daily') $t = 'per_day';
        if (!in_array($t, ['fixed', 'percentage', 'per_day', 'slab'], true)) {
            $t = ((float) ($rule->per_day_amount ?? 0) > 0) ? 'per_day' : 'fixed';
        }
        return $t;
    }

    /**
     * Rule in force for an item ($applies = 'loan' | 'savings') that fell due on $due_date.
     */
    public function find_rule($applies, $due_date, $loan_product_id = null) {
        if ($this->_rules === null) {
            $this->_rules = $this->db->where('is_active', 1)
                                     ->order_by('effective_from', 'DESC')->order_by('id', 'DESC')
                                     ->get('fine_rules')->result();
        }
        $due_date = substr((string) $due_date, 0, 10);
        $best = null;
        foreach ($this->_rules as $r) {
            if (!in_array($r->applies_to, [$applies, 'both'], true)) continue;
            if ($r->effective_from > $due_date) continue;
            if (!empty($r->effective_to) && $r->effective_to < $due_date) continue;
            $pid = $r->loan_product_id ?? null;
            if ($pid) {
                if ((int) $pid === (int) $loan_product_id) return $r;   // product-specific rule wins
                continue;
            }
            if (!$best) $best = $r;
        }
        return $best;
    }

    private function _rule($id) {
        if (!$id) return null;
        if (!array_key_exists($id, $this->_rule_by_id)) {
            $this->_rule_by_id[$id] = $this->db->where('id', $id)->get('fine_rules')->row();
        }
        return $this->_rule_by_id[$id];
    }

    /** Earliest date any active rule starts — nothing due before it can be fined */
    private function _rules_start() {
        $row = $this->db->select_min('effective_from', 'd')->where('is_active', 1)->get('fine_rules')->row();
        return $row && $row->d ? $row->d : null;
    }

    private static function _days_between($from, $to) {
        $a = DateTime::createFromFormat('Y-m-d', substr((string) $from, 0, 10));
        $b = DateTime::createFromFormat('Y-m-d', substr((string) $to, 0, 10));
        if (!$a || !$b || $b <= $a) return 0;
        return (int) $a->diff($b)->days;
    }

    private static function _valid_date($d) {
        return $d && strpos((string) $d, '0000-00-00') !== 0 && strtotime((string) $d) !== false;
    }

    /**
     * Load one overdue item in a common shape.
     */
    private function _item($kind, $id) {
        if ($kind === 'loan') {
            return $this->db->select('li.id, li.loan_id, li.due_date, li.emi_amount AS base, li.status, li.paid_date,
                                      li.installment_number, l.member_id, l.loan_number, l.loan_product_id AS product_id, l.status AS account_status')
                            ->from('loan_installments li')->join('loans l', 'l.id = li.loan_id')
                            ->where('li.id', $id)->get()->row();
        }
        return $this->db->select('sch.id, NULL AS loan_id, sch.due_date, sch.due_amount AS base, sch.status, sch.paid_date,
                                  sch.due_month, sa.member_id, sa.account_number, NULL AS product_id, sa.status AS account_status', false)
                        ->from('savings_schedule sch')->join('savings_accounts sa', 'sa.id = sch.savings_account_id')
                        ->where('sch.id', $id)->get()->row();
    }

    /**
     * Create or recalculate the fine of one item. Returns created|updated|same|none|no_rule|skip.
     */
    private function _accrue($kind, $item, $created_by, $existing = false) {
        if (!$item) return 'skip';
        $related = $kind === 'loan' ? 'loan_installment' : 'savings_schedule';
        if ($existing === false) {
            $existing = $this->db->where('related_type', $related)->where('related_id', $item->id)
                                 ->where('status !=', 'cancelled')
                                 ->order_by('id', 'DESC')->limit(1)->get($this->table)->row();
        }
        if ($existing && $existing->status === 'waived') return 'skip';
        // Installments that were skipped, waived, rescheduled as interest-only or
        // cancelled by a force close are not overdue in the business sense.
        if (in_array($item->status, ['waived', 'skipped', 'cancelled', 'interest_only'], true)) return 'skip';

        $rule = ($existing && !empty($existing->fine_rule_id)) ? $this->_rule($existing->fine_rule_id) : null;
        if (!$rule) $rule = $this->find_rule($kind, $item->due_date, $item->product_id ?? null);
        if (!$rule) return 'no_rule';
        // A rule switched off stops charging: open fines keep their amount
        if (empty($rule->is_active)) return 'skip';

        $today = date('Y-m-d');
        $end   = ($item->status === 'paid' && self::_valid_date($item->paid_date)) ? substr($item->paid_date, 0, 10) : $today;
        if ($end > $today) $end = $today;
        $days   = self::_days_between($item->due_date, $end);
        $amount = $this->calculate_fine_amount($rule, $days, (float) $item->base);

        $this->_last_item = ['kind' => $kind, 'item' => $item, 'days' => $days, 'amount' => $amount,
                             'old' => $existing ? (float) $existing->fine_amount : 0.0, 'rule' => $rule->rule_name ?? ''];

        if (!$existing) {
            if ($amount <= 0) return 'none';
            $grace = (int) ($rule->grace_period_days ?? 0);
            $start = date('Y-m-d', strtotime(substr($item->due_date, 0, 10) . ' +' . ($grace + 1) . ' days'));
            $label = $kind === 'loan'
                ? 'Late payment fine for ' . $item->loan_number . ' EMI #' . $item->installment_number
                : 'Late savings payment fine for ' . $item->account_number . (!empty($item->due_month) ? ' (' . date('M Y', strtotime($item->due_month)) . ')' : '');
            $this->create_fine([
                'member_id'    => $item->member_id,
                'loan_id'      => $item->loan_id ?: null,
                'fine_type'    => $kind === 'loan' ? 'loan_late' : 'savings_late',
                'related_type' => $related,
                'related_id'   => $item->id,
                'fine_rule_id' => $rule->id,
                'fine_date'    => min($start, $today),
                'due_date'     => substr($item->due_date, 0, 10),
                'days_late'    => $days,
                'fine_amount'  => $amount,
                'remarks'      => $label,
                'created_by'   => $created_by,
            ]);
            return 'created';
        }

        // Never recalculate below what has already been collected or waived
        $settled = (float) $existing->paid_amount + (float) $existing->waived_amount;
        $amount  = max($amount, round($settled, 2));
        if (abs($amount - (float) $existing->fine_amount) < 0.01 && (int) $existing->days_late === $days) return 'same';

        $balance = round($amount - $settled, 2);
        if ($balance <= 0) {
            $status = (float) $existing->paid_amount > 0 ? 'paid' : 'waived';
            $balance = 0;
        } else {
            $status = (float) $existing->paid_amount > 0 ? 'partial' : 'pending';
        }
        $this->db->where('id', $existing->id)->update($this->table, [
            'fine_amount'    => $amount,
            'balance_amount' => $balance,
            'days_late'      => $days,
            'status'         => $status,
            'fine_rule_id'   => $existing->fine_rule_id ?: $rule->id,
            'updated_by'     => $created_by,
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
        return abs($amount - (float) $existing->fine_amount) < 0.01 ? 'same' : 'updated';
    }

    private $_last_item = null;

    /**
     * Apply / refresh the late fine of one loan installment.
     * Returns the fine id, or false when no fine applies.
     */
    public function apply_loan_late_fine($installment_id, $created_by) {
        $res = $this->_accrue('loan', $this->_item('loan', $installment_id), $created_by);
        if (!in_array($res, ['created', 'updated', 'same'], true)) return false;
        $row = $this->db->select('id')->where('related_type', 'loan_installment')->where('related_id', $installment_id)
                        ->where('status !=', 'cancelled')->order_by('id', 'DESC')->limit(1)->get($this->table)->row();
        return $row ? $row->id : false;
    }

    /**
     * Apply / refresh the late fine of one savings schedule.
     */
    public function apply_savings_late_fine($schedule_id, $created_by) {
        $res = $this->_accrue('savings', $this->_item('savings', $schedule_id), $created_by);
        if (!in_array($res, ['created', 'updated', 'same'], true)) return false;
        $row = $this->db->select('id')->where('related_type', 'savings_schedule')->where('related_id', $schedule_id)
                        ->where('status !=', 'cancelled')->order_by('id', 'DESC')->limit(1)->get($this->table)->row();
        return $row ? $row->id : false;
    }

    /**
     * Record Fine Payment
     */
    public function record_payment($fine_id, $amount, $payment_mode, $reference = null, $received_by = null, $payment_date = null) {
        $this->db->trans_begin();
        
        // FINE-2 FIX: Lock the fine row to prevent concurrent payment corruption
        $fine = $this->db->query(
            "SELECT * FROM {$this->table} WHERE id = ? FOR UPDATE",
            [$fine_id]
        )->row();
        
        if (!$fine || $fine->status === 'paid') {
            $this->db->trans_rollback();
            return false;
        }

        // FINE-1 FIX (model-level guard): Validate amount
        if ($amount <= 0 || $amount > $fine->balance_amount) {
            $this->db->trans_rollback();
            return false;
        }
        
        // fines.payment_mode only allows cash/cheque/bank_transfer/online
        $mode_map = ['upi' => 'online', 'auto_debit' => 'online', 'auto' => 'online', 'neft' => 'bank_transfer',
                     'rtgs' => 'bank_transfer', 'imps' => 'bank_transfer', 'bank' => 'bank_transfer'];
        $payment_mode = strtolower((string) $payment_mode);
        $payment_mode = $mode_map[$payment_mode] ?? $payment_mode;
        if (!in_array($payment_mode, ['cash', 'cheque', 'bank_transfer', 'online'], true)) {
            $payment_mode = 'cash';
        }

        $new_paid = $fine->paid_amount + $amount;
        $new_balance = $fine->balance_amount - $amount;
        
        $status = 'partial';
        if ($new_balance <= 0) {
            $status = 'paid';
            $new_balance = 0;
        }
        
        $this->db->where('id', $fine_id)
                 ->update($this->table, [
                     'paid_amount' => $new_paid,
                     'balance_amount' => $new_balance,
                     'status' => $status,
                     'payment_mode' => $payment_mode,
                     'payment_reference' => $reference,
                     'payment_date' => $payment_date ?: date('Y-m-d'),
                     'received_by' => $received_by,
                     'updated_at' => date('Y-m-d H:i:s')
                 ]);
        
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        }
        
        $this->db->trans_commit();
        return true;
    }
    
    /**
     * Waive Fine
     * FINE-5 FIX: Wrapped in transaction for atomicity
     */
    public function waive_fine($fine_id, $waive_amount, $reason, $waived_by) {
        $this->db->trans_begin();

        // Lock the fine row
        $fine = $this->db->query(
            "SELECT * FROM {$this->table} WHERE id = ? FOR UPDATE",
            [$fine_id]
        )->row();
        
        if (!$fine) {
            $this->db->trans_rollback();
            return false;
        }

        if ($waive_amount <= 0 || $waive_amount > $fine->balance_amount) {
            $this->db->trans_rollback();
            return false;
        }
        
        $new_waived = $fine->waived_amount + $waive_amount;
        $new_balance = $fine->balance_amount - $waive_amount;
        
        $status = $fine->status;
        if ($new_balance <= 0) {
            $status = 'waived';
            $new_balance = 0;
        }
        
        $this->db->where('id', $fine_id)
                 ->update($this->table, [
                     'waived_amount' => $new_waived,
                     'balance_amount' => $new_balance,
                     'waiver_reason' => $reason,
                     'waived_by' => $waived_by,
                     'waived_at' => date('Y-m-d H:i:s'),
                     'status' => $status,
                     'updated_at' => date('Y-m-d H:i:s')
                 ]);

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        }

        $this->db->trans_commit();
        return true;
    }
    
    /**
     * Cancel Fine
     */
    public function cancel_fine($fine_id, $reason, $cancelled_by) {
        // fines has no cancellation_* columns — the old update failed on every cancel.
        // Record who/why in admin_comments + updated_by and clear what is owed.
        $fine = $this->db->select('admin_comments')->where('id', $fine_id)->get($this->table)->row();
        if (!$fine) return false;
        $note = '[Cancelled ' . date('Y-m-d H:i') . ' by admin #' . (int) $cancelled_by . '] ' . $reason;
        return $this->db->where('id', $fine_id)
                        ->update($this->table, [
                            'status'         => 'cancelled',
                            'balance_amount' => 0,
                            'admin_comments' => trim(($fine->admin_comments ? $fine->admin_comments . "\n" : '') . $note),
                            'updated_by'     => $cancelled_by,
                            'updated_at'     => date('Y-m-d H:i:s')
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
        if ($this->_has_field('min_days', 'fine_rules')) {
            $order_col = 'min_days';
        } elseif ($this->_has_field('grace_period_days', 'fine_rules')) {
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
                        ->where('f.waiver_requested_at IS NOT NULL')
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
        if ($this->_has_field('admin_comments', 'fines')) {
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
            'waiver_denied_reason' => $reason
        ];
        return $this->db->where('id', $fine_id)->update($this->table, $update);
    }
    
    /**
     * Calculate Fine Amount — the single formula used everywhere.
     *
     *   effective days = days late − grace period (no fine while ≤ 0)
     *   fixed       : fine_value (once)
     *   percentage  : fine_value % of the due amount (once)
     *   per_day     : fine_value on the first late day + per_day_amount for every further day
     *                 (when fine_value is 0: per_day_amount × effective days)
     *   slab        : amount of the slab_config band the effective days fall in
     *   max_fine_amount caps the result.
     *
     * Example (active rule): ₹100 + ₹10/day, grace 10 → 11 days late = ₹100, 15 days = ₹140.
     */
    public function calculate_fine_amount($rule, $days_late, $due_amount = 0) {
        $grace = (int) ($rule->grace_period_days ?? ($rule->grace_period ?? 0));
        $days  = max(0, (int) $days_late - $grace);
        if ($days <= 0) return 0;

        $value   = (float) ($rule->fine_value ?? ($rule->fine_amount ?? 0));
        $per_day = (float) ($rule->per_day_amount ?? 0);

        switch ($this->rule_type($rule)) {
            case 'percentage':
                $rate = (float) ($rule->fine_value ?? ($rule->fine_rate ?? 0));
                $fine = (float) $due_amount * $rate / 100;
                break;
            case 'per_day':
                $fine = $value > 0 ? $value + $per_day * ($days - 1) : $per_day * $days;
                break;
            case 'slab':
                $fine = $this->_slab_amount($rule, $days, $value);
                break;
            default:
                $fine = $value;
        }

        $cap = (float) ($rule->max_fine_amount ?? ($rule->max_fine ?? 0));
        if ($cap > 0 && $fine > $cap) $fine = $cap;
        return round(max(0, $fine), 2);
    }

    /**
     * slab_config JSON: [{"from":1,"to":30,"amount":100},{"from":31,"to":9999,"amount":250}]
     * ("days" are effective days after grace). Falls back to fine_value.
     */
    private function _slab_amount($rule, $days, $fallback) {
        $slabs = json_decode((string) ($rule->slab_config ?? ''), true);
        if (!is_array($slabs) || !$slabs) return $fallback;
        $last = null;
        foreach ($slabs as $s) {
            $from = (int) ($s['from'] ?? 0);
            $to   = (int) ($s['to'] ?? PHP_INT_MAX);
            if ($days >= $from && $days <= $to) return (float) ($s['amount'] ?? 0);
            if ($days > $to) $last = (float) ($s['amount'] ?? 0);
        }
        return $last ?? $fallback;
    }

    /**
     * Fine for a hypothetical overdue item (used by calculators / AI assistant).
     *
     * @param string $fine_type 'loan_late' | 'savings_late' | 'loan' | 'savings'
     */
    public function calculate_fine($fine_type, $outstanding_amount, $days_overdue, $due_date = null) {
        $applies = strpos($fine_type, 'saving') !== false ? 'savings' : 'loan';
        $rule = $this->find_rule($applies, $due_date ?: date('Y-m-d', strtotime('-' . (int) $days_overdue . ' days')));
        return $rule ? $this->calculate_fine_amount($rule, $days_overdue, $outstanding_amount) : 0;
    }

    /**
     * Recalculate every open automatic fine (pending/partial) against its locked
     * rule and its item's payment date. Returns how many changed.
     */
    public function recalculate_open_fines($created_by = 1) {
        $this->_rules = null;
        $this->_rule_by_id = [];
        $open = $this->db->where_in('related_type', ['loan_installment', 'savings_schedule'])
                         ->where('related_id >', 0)
                         ->where_in('status', ['pending', 'partial'])
                         ->get($this->table)->result();
        $changed = 0;
        foreach ($open as $f) {
            $kind = $f->related_type === 'loan_installment' ? 'loan' : 'savings';
            $res  = $this->_accrue($kind, $this->_item($kind, $f->related_id), $created_by, $f);
            $this->_tally($res);
            if ($res === 'updated') $changed++;
        }
        return $changed;
    }

    /**
     * Kept for older callers: per-day fines are refreshed by recalculate_open_fines().
     */
    public function update_daily_fines($created_by = 1) {
        return $this->recalculate_open_fines($created_by);
    }

    private function _tally($res) {
        if (!$this->last_run) return;
        $key = ['created' => 'created', 'updated' => 'updated', 'same' => 'unchanged',
                'no_rule' => 'no_rule', 'none' => 'within_grace', 'skip' => 'skipped'][$res] ?? 'skipped';
        $this->last_run[$key]++;
        if (in_array($res, ['created', 'updated'], true) && $this->_last_item) {
            $li = $this->_last_item;
            $this->last_run['amount_change'] += $li['amount'] - $li['old'];
            if (count($this->last_run['items']) < 5000) {
                $it = $li['item'];
                $this->last_run['items'][] = [
                    'action'    => $res,
                    'kind'      => $li['kind'],
                    'member_id' => $it->member_id,
                    'reference' => $li['kind'] === 'loan' ? $it->loan_number . ' EMI #' . $it->installment_number : $it->account_number,
                    'due_date'  => substr($it->due_date, 0, 10),
                    'status'    => $it->status,
                    'days_late' => $li['days'],
                    'rule'      => $li['rule'],
                    'old'       => $li['old'],
                    'amount'    => $li['amount'],
                ];
            }
        }
        $this->_last_item = null;
    }

    /**
     * Run Late Fine Job (cron "daily" and the Fines → Rules "Run" button).
     *
     * 1. Every unpaid installment/schedule of an active account that fell due on or
     *    after the first rule's start date gets its fine created or refreshed.
     * 2. Open fines whose EMI/deposit has since been paid are recalculated once more
     *    so they freeze at the payment date.
     *
     * $dry_run = true does all the work inside a transaction that is rolled back,
     * and leaves the would-be result in $this->last_run (used by the Preview page).
     * Returns number of fines created + changed.
     */
    public function run_late_fine_job($created_by = 1, $dry_run = false) {
        $this->_rules = null;
        $this->_rule_by_id = [];
        $this->last_run = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'no_rule' => 0,
                           'within_grace' => 0, 'skipped' => 0, 'amount_change' => 0.0, 'items' => [],
                           'rules_start' => null, 'dry_run' => (bool) $dry_run];

        $start = $this->_rules_start();
        $this->last_run['rules_start'] = $start;
        if (!$start) return 0;      // no active rule → nothing to do

        $today = date('Y-m-d');
        if ($dry_run) $this->db->trans_begin();

        $done = ['loan' => [], 'savings' => []];

        $loan_items = $this->db->select('li.id')
            ->from('loan_installments li')->join('loans l', 'l.id = li.loan_id')
            ->where('l.status', 'active')
            ->where_in('li.status', ['pending', 'overdue', 'partial'])
            ->where('li.due_date <', $today)
            ->where('li.due_date >=', $start)
            ->get()->result();
        foreach ($loan_items as $r) {
            $this->_tally($this->_accrue('loan', $this->_item('loan', $r->id), $created_by));
            $done['loan'][$r->id] = true;
        }

        $sav_items = $this->db->select('sch.id')
            ->from('savings_schedule sch')->join('savings_accounts sa', 'sa.id = sch.savings_account_id')
            ->where('sa.status', 'active')
            ->where_in('sch.status', ['pending', 'partial', 'overdue'])
            ->where('sch.due_date <', $today)
            ->where('sch.due_date >=', $start)
            ->get()->result();
        foreach ($sav_items as $r) {
            $this->_tally($this->_accrue('savings', $this->_item('savings', $r->id), $created_by));
            $done['savings'][$r->id] = true;
        }

        // Open fines not covered above (EMI paid since, account closed…): freeze them
        $open = $this->db->where_in('related_type', ['loan_installment', 'savings_schedule'])
                         ->where('related_id >', 0)
                         ->where_in('status', ['pending', 'partial'])
                         ->get($this->table)->result();
        foreach ($open as $f) {
            $kind = $f->related_type === 'loan_installment' ? 'loan' : 'savings';
            if (isset($done[$kind][$f->related_id])) continue;
            $this->_tally($this->_accrue($kind, $this->_item($kind, $f->related_id), $created_by, $f));
        }

        if ($dry_run) {
            $this->db->trans_rollback();
        }

        $this->last_run['amount_change'] = round($this->last_run['amount_change'], 2);
        return $this->last_run['created'] + $this->last_run['updated'];
    }

    /**
     * Save a fine rule (used by Fines → Rules and Settings).
     *
     * Editing a rule with an effective date later than its current start does not
     * overwrite it: the current version is closed the day before (effective_to)
     * and a new version starts, so EMIs that fell due earlier keep the old terms.
     * Returns ['id' => int, 'versioned' => bool, 'effective_from' => Y-m-d] or false.
     */
    public function save_rule($id, array $data, $admin_id) {
        $t = $data['calculation_type'] ?? ($data['fine_type'] ?? 'fixed');
        if ($t === 'fixed_plus_daily') $t = 'per_day';
        if (!in_array($t, ['fixed', 'percentage', 'per_day', 'slab'], true)) $t = 'fixed';
        $data['calculation_type'] = $t;
        $data['fine_type'] = $t;
        $data['effective_from'] = !empty($data['effective_from']) ? date('Y-m-d', strtotime($data['effective_from'])) : date('Y-m-d');
        foreach (['fine_value', 'per_day_amount'] as $k) {
            if (isset($data[$k])) $data[$k] = max(0, (float) $data[$k]);
        }
        if (isset($data['grace_period_days'])) $data['grace_period_days'] = max(0, (int) $data['grace_period_days']);
        if (isset($data['max_fine_amount']) && (float) $data['max_fine_amount'] <= 0) $data['max_fine_amount'] = null;
        $now = date('Y-m-d H:i:s');

        if (!$id) {
            $data['rule_code']  = $data['rule_code'] ?? $this->_unique_rule_code('FR-' . date('Y'));
            $data['created_by'] = $admin_id;
            $data['created_at'] = $now;
            $this->db->insert('fine_rules', $data);
            return ['id' => (int) $this->db->insert_id(), 'versioned' => false, 'effective_from' => $data['effective_from']];
        }

        $old = $this->db->where('id', $id)->get('fine_rules')->row();
        if (!$old) return false;
        unset($data['rule_code'], $data['created_by'], $data['created_at']);

        if ($data['effective_from'] > $old->effective_from) {
            $this->db->trans_start();
            $this->db->where('id', $id)->update('fine_rules', [
                'effective_to' => date('Y-m-d', strtotime($data['effective_from'] . ' -1 day')),
                'updated_by'   => $admin_id,
                'updated_at'   => $now,
            ]);
            $new = array_merge((array) $old, $data);
            unset($new['id'], $new['updated_by'], $new['updated_at']);
            $new['effective_to'] = null;
            $new['rule_code']    = $this->_unique_rule_code(preg_replace('/-V\d+$/', '', $old->rule_code));
            $new['created_by']   = $admin_id;
            $new['created_at']   = $now;
            $this->db->insert('fine_rules', $new);
            $new_id = (int) $this->db->insert_id();
            $this->db->trans_complete();
            if (!$this->db->trans_status()) return false;
            return ['id' => $new_id, 'versioned' => true, 'effective_from' => $data['effective_from']];
        }

        $data['updated_by'] = $admin_id;
        $data['updated_at'] = $now;
        $this->db->where('id', $id)->update('fine_rules', $data);
        return ['id' => (int) $id, 'versioned' => false, 'effective_from' => $data['effective_from']];
    }

    private function _unique_rule_code($base) {
        $base = substr($base, 0, 15);
        $exists = function ($c) { return $this->db->where('rule_code', $c)->count_all_results('fine_rules') > 0; };
        if (!$exists($base)) return $base;
        for ($n = 2; $n < 1000; $n++) {
            if (!$exists($base . '-V' . $n)) return $base . '-V' . $n;
        }
        return substr($base, 0, 10) . '-' . strtoupper(substr(uniqid(), -6));
    }

    /**
     * Open fine balance of a member (pending + partial).
     */
    public function member_open_balance($member_id) {
        $r = $this->db->select_sum('balance_amount', 's')->where('member_id', $member_id)
                      ->where_in('status', ['pending', 'partial'])->get($this->table)->row();
        return round((float) ($r->s ?? 0), 2);
    }

    /**
     * Collect money against a member's open fines — oldest first, or the given
     * fine first — recording each fine payment and posting each receipt to the
     * ledger. Used by bank mapping / passbook import, so an amount that covers
     * several small fines no longer fails and the bank date is kept.
     *
     * Returns ['applied' => float, 'rest' => float, 'fines' => [fine_id => amount]].
     */
    public function collect_for_member($member_id, $amount, $mode, $reference, $received_by, $date = null, $first_fine_id = null, $post_ledger = true) {
        $amount = round((float) $amount, 2);
        $date   = $date ?: date('Y-m-d');
        $out    = ['applied' => 0.0, 'rest' => $amount, 'fines' => []];
        if ($amount < 0.01 || !$member_id) return $out;

        $fines = $this->db->where('member_id', $member_id)->where_in('status', ['pending', 'partial'])
                          ->where('balance_amount >', 0)
                          ->order_by('fine_date', 'ASC')->order_by('id', 'ASC')->get($this->table)->result();
        if ($first_fine_id) {
            usort($fines, function ($a, $b) use ($first_fine_id) {
                return ((int) $b->id === (int) $first_fine_id) <=> ((int) $a->id === (int) $first_fine_id);
            });
        }
        if ($post_ledger) $this->load->model('Ledger_model');

        $left = $amount;
        foreach ($fines as $f) {
            if ($left < 0.01) break;
            $pay = round(min($left, (float) $f->balance_amount), 2);
            if ($pay < 0.01) continue;
            if (!$this->record_payment($f->id, $pay, $mode, $reference, $received_by, $date)) continue;
            if ($post_ledger) $this->Ledger_model->post_fine_receipt($f->id, $pay, $date, $mode, $received_by);
            $out['fines'][(int) $f->id] = $pay;
            $left = round($left - $pay, 2);
        }
        $out['applied'] = round($amount - $left, 2);
        $out['rest']    = $left;
        return $out;
    }
}
