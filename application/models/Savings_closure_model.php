<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Savings / Security Deposit account closure.
 *
 * Member requests → admin approves (or rejects). On approval, in one transaction:
 *   1. closing fee (setting savings_closing_fee, editable / waivable with a reason) — savings debit
 *      'Account closing fee …' (payment_mode adjustment): ledger Dr 2101 Members' Savings / Cr 3500 Closing Fee Income
 *   2. the rest of the balance paid out — normal withdrawal: ledger Dr 2101 / Cr Cash or Bank
 *   3. remaining (future) monthly dues waived, account closed with a zero balance.
 * A request cannot be made or approved while the member has a running loan (as borrower or guarantor),
 * open fines, or overdue deposits on the account.
 */
class Savings_closure_model extends MY_Model {

    protected $table = 'savings_closure_requests';

    const FEE_NARRATION    = 'Account closing fee';
    const REFUND_NARRATION = 'Account closure payout';

    public function is_installed() {
        return $this->db->table_exists($this->table);
    }

    /** Closing fee from settings (default 150). */
    public function setting_fee() {
        $v = $this->db->select('setting_value')->where('setting_key', 'savings_closing_fee')->get('system_settings')->row();
        return $v ? max(0, round((float) $v->setting_value, 2)) : 150.0;
    }

    public function get_account($account_id) {
        return $this->db->select('sa.*, m.member_code, m.first_name, m.last_name, m.phone, m.email, ss.scheme_name')
                        ->from('savings_accounts sa')
                        ->join('members m', 'm.id = sa.member_id')
                        ->join('savings_schemes ss', 'ss.id = sa.scheme_id', 'left')
                        ->where('sa.id', (int) $account_id)->get()->row();
    }

    public function get_request($id) {
        return $this->db->select('r.*, sa.account_number, sa.current_balance, sa.status AS account_status, sa.monthly_amount,
                                  m.member_code, m.first_name, m.last_name, m.phone, m.email, a.full_name AS processed_by_name')
                        ->from($this->table . ' r')
                        ->join('savings_accounts sa', 'sa.id = r.savings_account_id')
                        ->join('members m', 'm.id = r.member_id')
                        ->join('admin_users a', 'a.id = r.processed_by', 'left')
                        ->where('r.id', (int) $id)->get()->row();
    }

    public function get_requests($status = 'pending') {
        $this->db->select('r.*, sa.account_number, sa.current_balance, m.member_code, m.first_name, m.last_name, m.phone, a.full_name AS processed_by_name')
                 ->from($this->table . ' r')
                 ->join('savings_accounts sa', 'sa.id = r.savings_account_id')
                 ->join('members m', 'm.id = r.member_id')
                 ->join('admin_users a', 'a.id = r.processed_by', 'left');
        if ($status !== '' && $status !== 'all') $this->db->where('r.status', $status);
        return $this->db->order_by('r.status = "pending"', 'DESC', false)->order_by('r.requested_at', 'DESC')->get()->result();
    }

    public function count_pending() {
        return $this->is_installed() ? $this->db->where('status', 'pending')->count_all_results($this->table) : 0;
    }

    /** The open (pending) request of an account, if any. */
    public function pending_for_account($account_id) {
        if (!$this->is_installed()) return null;
        return $this->db->where('savings_account_id', (int) $account_id)->where('status', 'pending')
                        ->order_by('id', 'DESC')->limit(1)->get($this->table)->row();
    }

    /** Latest request per account for a member: [account_id => request]. */
    public function latest_for_member($member_id) {
        if (!$this->is_installed()) return [];
        $out = [];
        foreach ($this->db->where('member_id', (int) $member_id)->order_by('id', 'ASC')->get($this->table)->result() as $r) $out[$r->savings_account_id] = $r;
        return $out;
    }

    /**
     * Reasons the account cannot be closed now (empty = can be closed).
     * @return array list of ['type' => ..., 'text' => ...]
     */
    public function blockers($account) {
        $b = [];
        if (!$account) return [['type' => 'account', 'text' => 'Account not found']];
        if (!in_array($account->status, ['active', 'matured'], true)) $b[] = ['type' => 'account', 'text' => 'Account is ' . $account->status];
        if ((float) $account->current_balance < 0) $b[] = ['type' => 'balance', 'text' => 'Balance is negative (' . format_amount($account->current_balance) . ')'];

        $mid = (int) $account->member_id;
        foreach ($this->db->select('loan_number, status, outstanding_principal')->where('member_id', $mid)
                          ->where_not_in('status', ['closed', 'foreclosed', 'written_off'])->get('loans')->result() as $l) {
            $b[] = ['type' => 'loan', 'text' => 'Running loan ' . $l->loan_number . ' (' . $l->status . ', outstanding ' . format_amount($l->outstanding_principal) . ')'];
        }
        // Guarantees on a running loan (older rows only carry the application id)
        $g = $this->db->query("
            SELECT DISTINCT l.loan_number FROM loan_guarantors lg
              JOIN loans l ON l.id = COALESCE(lg.loan_id, (SELECT x.id FROM loans x WHERE x.loan_application_id = lg.loan_application_id LIMIT 1))
             WHERE lg.guarantor_member_id = ? AND lg.consent_status IN ('accepted','pending') AND COALESCE(lg.is_released, 0) = 0
               AND l.status NOT IN ('closed','foreclosed','written_off')", [$mid])->result();
        foreach ($g as $x) $b[] = ['type' => 'guarantor', 'text' => 'Guarantor for running loan ' . $x->loan_number];

        $f = $this->db->select('COUNT(*) n, COALESCE(SUM(balance_amount), 0) amt', false)->where('member_id', $mid)
                      ->where_in('status', ['pending', 'partial'])->where('balance_amount >', 0)->get('fines')->row();
        if ((int) $f->n > 0) $b[] = ['type' => 'fine', 'text' => (int) $f->n . ' open fine(s) of ' . format_amount($f->amt)];

        $d = $this->db->select('COUNT(*) n, COALESCE(SUM(GREATEST(due_amount - paid_amount, 0)), 0) amt', false)
                      ->where('savings_account_id', (int) $account->id)->where_in('status', ['pending', 'partial', 'overdue'])
                      ->where('due_date <', date('Y-m-d'))->get('savings_schedule')->row();
        if ((int) $d->n > 0) $b[] = ['type' => 'dues', 'text' => (int) $d->n . ' unpaid monthly deposit(s) of ' . format_amount($d->amt) . ' on this account'];
        return $b;
    }

    /** Member (or admin on the member's behalf) asks to close an account. @return int request id */
    public function create_request($account_id, $member_id, $reason, $by_admin_id = null) {
        if (!$this->is_installed()) throw new Exception('Account closure is not set up yet (run migration 039)');
        $account = $this->get_account($account_id);
        if (!$account || (int) $account->member_id !== (int) $member_id) throw new Exception('Account not found');
        $reason = trim((string) $reason);
        if (mb_strlen($reason) < 5) throw new Exception('Please give a reason for closing the account');
        if ($this->pending_for_account($account_id)) throw new Exception('A closure request for this account is already pending');
        if ($bl = $this->blockers($account)) {
            throw new Exception('The account cannot be closed yet: ' . implode('; ', array_column($bl, 'text')));
        }
        $this->db->insert($this->table, [
            'savings_account_id' => (int) $account_id, 'member_id' => (int) $member_id, 'reason' => mb_substr($reason, 0, 2000),
            'status' => 'pending', 'balance_at_request' => round((float) $account->current_balance, 2),
            'requested_at' => date('Y-m-d H:i:s'),
            'admin_remarks' => $by_admin_id ? 'Requested at the office (entered by admin #' . (int) $by_admin_id . ')' : null,
        ]);
        $id = (int) $this->db->insert_id();

        $this->load->model('Audit_model');
        $this->Audit_model->create([
            'user_type' => $by_admin_id ? 'admin' : 'member', 'user_id' => $by_admin_id ?: $member_id,
            'action' => 'savings_closure_requested', 'module' => 'savings', 'table_name' => $this->table, 'record_id' => $id,
            'new_values' => json_encode(['account' => $account->account_number, 'balance' => (float) $account->current_balance, 'reason' => $reason]),
        ]);

        // Tell the admins
        $this->load->model('Notification_model');
        $title = 'Account closure request';
        $msg = trim($account->first_name . ' ' . $account->last_name) . ' (' . $account->member_code . ') asked to close ' . $account->account_number
             . ' — balance ' . format_amount($account->current_balance) . '.';
        foreach ($this->db->select('id')->where('is_active', 1)->get('admin_users')->result() as $a) {
            $this->Notification_model->create('admin', $a->id, 'savings_closure_request', $title, $msg, ['request_id' => $id, 'url' => site_url('admin/savings/closure/' . $id)]);
        }
        return $id;
    }

    /** Member withdraws their own pending request. */
    public function cancel_request($id, $member_id) {
        $r = $this->db->where('id', (int) $id)->where('member_id', (int) $member_id)->get($this->table)->row();
        if (!$r) throw new Exception('Request not found');
        if ($r->status !== 'pending') throw new Exception('Only a pending request can be cancelled');
        $this->db->where('id', $r->id)->update($this->table, ['status' => 'cancelled', 'processed_at' => date('Y-m-d H:i:s'), 'admin_remarks' => 'Cancelled by member']);
        return true;
    }

    public function reject($id, $remarks, $admin_id) {
        $r = $this->get_request($id);
        if (!$r) throw new Exception('Request not found');
        if ($r->status !== 'pending') throw new Exception('This request is already ' . $r->status);
        $remarks = trim((string) $remarks);
        if ($remarks === '') throw new Exception('Give a reason for rejecting');
        $this->db->where('id', $r->id)->update($this->table, ['status' => 'rejected', 'admin_remarks' => mb_substr($remarks, 0, 2000),
                                                              'processed_by' => $admin_id, 'processed_at' => date('Y-m-d H:i:s')]);
        $this->load->model(['Audit_model', 'Notification_model']);
        $this->Audit_model->create(['user_type' => 'admin', 'user_id' => $admin_id, 'action' => 'savings_closure_rejected', 'module' => 'savings',
                                    'table_name' => $this->table, 'record_id' => $r->id, 'remarks' => $remarks]);
        $this->Notification_model->create('member', $r->member_id, 'savings_closure_rejected', 'Account closure request rejected',
            'Your request to close ' . $r->account_number . ' was not approved: ' . $remarks, ['request_id' => $r->id]);
        return true;
    }

    /**
     * Approve: deduct the fee, pay out the rest, close the account.
     * @param array $d fee, fee_reason, payout_mode, payout_date, payout_reference, remarks
     * @return array ['fee' => , 'refund' => , 'fee_txn' => , 'refund_txn' => ]
     */
    public function approve($id, array $d, $admin_id) {
        $this->load->model(['Savings_model', 'Ledger_model', 'Audit_model', 'Notification_model']);
        $this->db->trans_begin();
        try {
            $r = $this->db->query('SELECT * FROM ' . $this->table . ' WHERE id = ? FOR UPDATE', [(int) $id])->row();
            if (!$r) throw new Exception('Request not found');
            if ($r->status !== 'pending') throw new Exception('This request is already ' . $r->status);

            // Balance from the transactions themselves, then re-check what blocks a closure
            $this->Savings_model->recalculate_account_balance($r->savings_account_id);
            $account = $this->get_account($r->savings_account_id);
            if ($bl = $this->blockers($account)) throw new Exception('Cannot close yet: ' . implode('; ', array_column($bl, 'text')));
            $balance = round((float) $account->current_balance, 2);

            $setting_fee = $this->setting_fee();
            $fee = isset($d['fee']) && $d['fee'] !== '' ? round((float) $d['fee'], 2) : min($setting_fee, $balance);
            $fee_reason = trim((string) ($d['fee_reason'] ?? ''));
            if ($fee < 0) throw new Exception('Closing fee cannot be negative');
            if ($fee > $balance + 0.001) throw new Exception('Closing fee ' . format_amount($fee) . ' is more than the balance ' . format_amount($balance));
            if (abs($fee - $setting_fee) > 0.001 && $fee_reason === '') {
                throw new Exception('The fee differs from the settings fee (' . format_amount($setting_fee) . ') — give a reason for the change / waiver');
            }
            $refund = round($balance - $fee, 2);

            $date = $d['payout_date'] ?? date('Y-m-d');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date)) throw new Exception('Invalid payout date');
            if ($date > date('Y-m-d')) throw new Exception('Payout date cannot be in the future');
            if ($date < substr($r->requested_at, 0, 10)) throw new Exception('Payout date cannot be before the request date');
            $mode = in_array($d['payout_mode'] ?? '', ['cash', 'bank_transfer', 'cheque', 'upi'], true) ? $d['payout_mode'] : null;
            if ($refund > 0 && !$mode) throw new Exception('Choose how the balance is paid out');
            $ref = mb_substr(trim((string) ($d['payout_reference'] ?? '')), 0, 100);

            // Savings_model::record_payment only takes active accounts; a matured one is closed the same way
            // (inside this transaction, so it never stays 'active' if anything fails)
            if ($account->status === 'matured') $this->db->where('id', $account->id)->update('savings_accounts', ['status' => 'active']);

            $fee_txn = $refund_txn = null;
            if ($fee > 0) {
                $fee_txn = $this->Savings_model->record_payment([
                    'savings_account_id' => $account->id, 'transaction_type' => 'withdrawal', 'amount' => $fee, 'payment_mode' => 'adjustment',
                    'transaction_date' => $date, 'narration' => self::FEE_NARRATION . ' — ' . $account->account_number
                        . ($fee_reason !== '' ? ' (' . $fee_reason . ')' : ''), 'created_by' => $admin_id,
                ]);
                if (!$fee_txn) throw new Exception('Could not record the closing fee');
                $this->Ledger_model->post_savings_txn((int) $fee_txn, $admin_id);
            }
            if ($refund > 0) {
                $refund_txn = $this->Savings_model->record_payment([
                    'savings_account_id' => $account->id, 'transaction_type' => 'withdrawal', 'amount' => $refund, 'payment_mode' => $mode,
                    'reference_number' => $ref ?: null, 'transaction_date' => $date,
                    'narration' => self::REFUND_NARRATION . ' — ' . $account->account_number, 'created_by' => $admin_id,
                ]);
                if (!$refund_txn) throw new Exception('Could not record the payout');
                $this->Ledger_model->post_savings_txn((int) $refund_txn, $admin_id);
            }

            $this->Savings_model->recalculate_account_balance($account->id);
            $left = (float) $this->db->select('current_balance')->where('id', $account->id)->get('savings_accounts')->row()->current_balance;
            if (abs($left) > 0.01) throw new Exception('Balance after closure is ' . format_amount($left) . ', expected 0');

            // Monthly dues still ahead are no longer owed
            $this->db->where('savings_account_id', $account->id)->where_in('status', ['pending', 'partial', 'overdue'])
                     ->update('savings_schedule', ['status' => 'waived', 'remarks' => 'Account closed on ' . $date, 'updated_at' => date('Y-m-d H:i:s')]);

            $this->db->where('id', $account->id)->update('savings_accounts', [
                'status' => 'closed', 'closed_at' => $date . ' ' . date('H:i:s'),
                'closed_reason' => mb_substr('Closure request #' . $r->id . ': ' . $r->reason, 0, 255), 'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $this->db->where('id', $r->id)->update($this->table, [
                'status' => 'approved', 'balance_at_closure' => $balance, 'setting_fee' => $setting_fee, 'fee_amount' => $fee,
                'fee_change_reason' => $fee_reason !== '' ? mb_substr($fee_reason, 0, 255) : null, 'refund_amount' => $refund,
                'payout_mode' => $refund > 0 ? $mode : null, 'payout_date' => $date, 'payout_reference' => $ref ?: null,
                'fee_transaction_id' => $fee_txn ?: null, 'refund_transaction_id' => $refund_txn ?: null,
                'admin_remarks' => trim(trim((string) $r->admin_remarks) . ' ' . trim((string) ($d['remarks'] ?? ''))) ?: null,
                'processed_by' => $admin_id, 'processed_at' => date('Y-m-d H:i:s'),
            ]);

            $this->Audit_model->create([
                'user_type' => 'admin', 'user_id' => $admin_id, 'action' => 'savings_account_closed', 'module' => 'savings',
                'table_name' => 'savings_accounts', 'record_id' => $account->id,
                'new_values' => json_encode(['request' => (int) $r->id, 'account' => $account->account_number, 'balance' => $balance, 'setting_fee' => $setting_fee,
                                             'fee' => $fee, 'fee_reason' => $fee_reason, 'refund' => $refund, 'mode' => $mode, 'date' => $date, 'reference' => $ref]),
                'remarks' => 'Closed ' . $account->account_number . ': fee ' . number_format($fee, 2) . ', paid out ' . number_format($refund, 2),
            ]);

            if ($this->db->trans_status() === false) throw new Exception('Database error while closing the account');
            $this->db->trans_commit();
        } catch (Exception $e) {
            $this->db->trans_rollback();
            throw $e;
        }

        $this->Notification_model->create('member', $r->member_id, 'savings_closure_approved', 'Account closed',
            'Your account ' . $account->account_number . ' is closed. Balance ' . format_amount($balance) . ', closing fee ' . format_amount($fee)
            . ', paid to you ' . format_amount($refund) . ($mode ? ' by ' . str_replace('_', ' ', $mode) : '') . ' on ' . format_date($date) . '.',
            ['request_id' => (int) $r->id]);
        return ['fee' => $fee, 'refund' => $refund, 'fee_txn' => $fee_txn, 'refund_txn' => $refund_txn];
    }
}
