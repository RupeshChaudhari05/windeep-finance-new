<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin Adjustments Controller
 *
 * Super-admin-only tool for viewing and adjusting member financial data.
 * Provides Excel-like editable tables for loan installments, savings schedules, and fines.
 * Every edit is audit-logged with before/after values and requires a reason.
 *
 * Industry Standard: "Maker-Checker" pattern with full audit trail.
 */
class Adjustments extends Admin_Controller {

    public function __construct() {
        parent::__construct();

        // Super admin only
        if ($this->admin_data->role !== 'super_admin') {
            if ($this->is_ajax()) {
                $this->error_response('Access denied. Super Admin only.', [], 403);
                exit;
            }
            $this->session->set_flashdata('error', 'Access denied. This page is for Super Admin only.');
            redirect('admin/dashboard');
        }

        $this->load->model('Member_model');
        $this->load->model('Loan_model');
        $this->load->model('Savings_model');
        $this->load->model('Fine_model');
        $this->load->model('Ledger_model');
    }

    /**
     * Main Page - Admin Adjustments Console
     */
    public function index() {
        $data['title'] = 'Admin Adjustments';
        $data['page_title'] = 'Admin Adjustments Console';
        $data['breadcrumbs'] = [
            ['title' => 'Dashboard', 'url' => base_url('admin/dashboard')],
            ['title' => 'Admin Adjustments']
        ];

        $this->load_view('admin/adjustments/index', $data);
    }

    /**
     * AJAX: Search Members
     */
    public function search_members() {
        header('Content-Type: application/json');

        $keyword = $this->input->get('q');

        if (empty($keyword) || strlen($keyword) < 1) {
            echo json_encode(['results' => []]);
            exit;
        }

        try {
            $members = $this->Member_model->search_members($keyword, null, 20);

            $results = [];
            foreach ($members as $m) {
                $full_name = trim($m->first_name . ' ' . $m->last_name);
                $display = $m->member_code;
                if ($full_name !== '') $display .= ' - ' . $full_name;
                if (!empty($m->phone)) $display .= ' (' . $m->phone . ')';

                $results[] = [
                    'id' => $m->id,
                    'text' => $display,
                    'member_code' => $m->member_code,
                    'name' => $full_name,
                    'phone' => $m->phone,
                    'status' => $m->status
                ];
            }

            echo json_encode(['results' => $results]);
        } catch (Exception $e) {
            log_message('error', 'Adjustment member search failed: ' . $e->getMessage());
            echo json_encode(['results' => []]);
        }
        exit;
    }

    /**
     * AJAX: Get Full Member Overview
     * Returns all loans, savings, fines summary for the selected member
     */
    public function member_overview($member_id = null) {
        header('Content-Type: application/json');

        if (!$member_id) {
            echo json_encode(['success' => false, 'message' => 'Member ID required']);
            exit;
        }

        try {
            $member = $this->Member_model->get_by_id($member_id);
            if (!$member) {
                echo json_encode(['success' => false, 'message' => 'Member not found']);
                exit;
            }

            // Get all loans (active + closed) - use LEFT JOIN in case product is deleted
            $loans = $this->db->select('l.*, COALESCE(lp.product_name, "Unknown") as product_name')
                              ->from('loans l')
                              ->join('loan_products lp', 'lp.id = l.loan_product_id', 'left')
                              ->where('l.member_id', $member_id)
                              ->order_by('l.disbursement_date', 'DESC')
                              ->get()
                              ->result();

            // Get all savings accounts - use LEFT JOIN in case scheme is deleted
            $savings = $this->db->select('sa.*, COALESCE(ss.scheme_name, "Unknown") as scheme_name')
                                ->from('savings_accounts sa')
                                ->join('savings_schemes ss', 'ss.id = sa.scheme_id', 'left')
                                ->where('sa.member_id', $member_id)
                                ->order_by('sa.created_at', 'DESC')
                                ->get()
                                ->result();

            // Get all fines
            $fines = $this->db->where('member_id', $member_id)
                              ->order_by('fine_date', 'DESC')
                              ->get('fines')
                              ->result();
            if (!$fines) $fines = [];

            // Summary stats
            $summary = [
                'total_loans' => count($loans),
                'active_loans' => count(array_filter($loans, function($l) { return $l->status === 'active'; })),
                'total_outstanding' => array_sum(array_map(function($l) { return (float)$l->outstanding_principal; }, $loans)),
                'total_savings_accounts' => count($savings),
                'total_savings_balance' => array_sum(array_map(function($s) { return (float)$s->current_balance; }, $savings)),
                'total_fines' => count($fines),
                'pending_fines' => count(array_filter($fines, function($f) { return in_array($f->status, ['pending', 'partial']); })),
                'pending_fine_amount' => array_sum(array_map(function($f) {
                    return in_array($f->status, ['pending', 'partial']) ? (float)$f->balance_amount : 0;
                }, $fines))
            ];

            echo json_encode([
                'success' => true,
                'member' => [
                    'id' => $member->id,
                    'member_code' => $member->member_code,
                    'name' => trim($member->first_name . ' ' . $member->last_name),
                    'phone' => $member->phone,
                    'email' => $member->email ?? '',
                    'status' => $member->status
                ],
                'loans' => $loans,
                'savings' => $savings,
                'fines' => $fines,
                'summary' => $summary
            ]);
        } catch (Exception $e) {
            log_message('error', 'Adjustments member_overview error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error loading member data: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * AJAX: Get Loan Installments (Editable Grid)
     */
    public function loan_installments($loan_id = null) {
        header('Content-Type: application/json');

        if (!$loan_id) {
            echo json_encode(['success' => false, 'message' => 'Loan ID required']);
            exit;
        }

        try {
            $loan = $this->db->select('l.*, COALESCE(lp.product_name,"Unknown") as product_name, lp.interest_type, m.member_code, m.first_name, m.last_name')
                             ->from('loans l')
                             ->join('loan_products lp', 'lp.id = l.loan_product_id', 'left')
                             ->join('members m', 'm.id = l.member_id')
                             ->where('l.id', $loan_id)
                             ->get()
                             ->row();

            if (!$loan) {
                echo json_encode(['success' => false, 'message' => 'Loan not found']);
                exit;
            }

            // Viewing never writes. (It used to recalculate and SAVE the loan totals on every
            // open, which put the waived principal back on foreclosed / force-closed loans.)
            // The admin uses "Recalculate" explicitly when the totals need rebuilding.

            $installments = $this->db->where('loan_id', $loan_id)
                                     ->order_by('installment_number', 'ASC')
                                     ->get('loan_installments')
                                     ->result();

            // Get payments for this loan (include all, flag reversed)
            $payments = $this->db->where('loan_id', $loan_id)
                                 ->order_by('payment_date', 'DESC')
                                 ->get('loan_payments')
                                 ->result();

            echo json_encode([
                'success' => true,
                'loan' => $loan,
                'installments' => $installments ?: [],
                'payments' => $payments ?: []
            ]);
        } catch (Exception $e) {
            log_message('error', 'Adjustments loan_installments error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * AJAX: Get Savings Schedule (Editable Grid)
     */
    public function savings_schedule($account_id = null) {
        header('Content-Type: application/json');

        if (!$account_id) {
            echo json_encode(['success' => false, 'message' => 'Account ID required']);
            exit;
        }

        try {
            $account = $this->db->select('sa.*, COALESCE(ss.scheme_name,"Unknown") as scheme_name, ss.monthly_amount, m.member_code, m.first_name, m.last_name')
                                ->from('savings_accounts sa')
                                ->join('savings_schemes ss', 'ss.id = sa.scheme_id', 'left')
                                ->join('members m', 'm.id = sa.member_id')
                                ->where('sa.id', $account_id)
                                ->get()
                                ->row();

            if (!$account) {
                echo json_encode(['success' => false, 'message' => 'Savings account not found']);
                exit;
            }

            // Viewing never writes; "Recalculate" rebuilds the balance on request.

            $schedule = $this->db->where('savings_account_id', $account_id)
                                 ->order_by('due_month', 'ASC')
                                 ->get('savings_schedule')
                                 ->result();

            // Get all transactions (include reversed flag for admin view)
            $transactions = $this->db->where('savings_account_id', $account_id)
                                     ->order_by('transaction_date', 'DESC')
                                     ->order_by('id', 'DESC')
                                     ->get('savings_transactions')
                                     ->result();

            echo json_encode([
                'success' => true,
                'account' => $account,
                'schedule' => $schedule ?: [],
                'transactions' => $transactions ?: []
            ]);
        } catch (Exception $e) {
            log_message('error', 'Adjustments savings_schedule error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * AJAX: Adjust Loan Installment
     * Allows admin to modify installment values with full audit trail
     */
    public function adjust_loan_installment() {
        if ($this->input->method() !== 'post') {
            return $this->error_response('POST required');
        }

        $installment_id = $this->input->post('installment_id');
        $field = $this->input->post('field');
        $new_value = $this->input->post('new_value');
        $reason = $this->input->post('reason');

        if (empty($installment_id) || empty($field) || !isset($new_value) || empty($reason)) {
            return $this->error_response('All fields required: installment_id, field, new_value, reason');
        }

        // Whitelist of adjustable fields
        $allowed_fields = [
            'principal_amount', 'interest_amount', 'emi_amount',
            'principal_paid', 'interest_paid', 'fine_amount', 'fine_paid', 'total_paid',
            'status', 'due_date', 'paid_date', 'remarks'
        ];

        if (!in_array($field, $allowed_fields)) {
            return $this->error_response('Field "' . $field . '" is not adjustable');
        }

        list($new_value, $verr) = $this->_clean_value('loan_installments', $field, $new_value);
        if ($verr) return $this->error_response($verr);

        $this->db->trans_begin();

        try {
            // Get current value
            $installment = $this->db->where('id', $installment_id)
                                    ->get('loan_installments')
                                    ->row();

            if (!$installment) {
                throw new Exception('Installment not found');
            }

            $old_value = $installment->$field;

            // Apply adjustment
            $update_data = [
                $field => $new_value,
                'is_adjusted' => 1,
                'adjustment_remarks' => ($installment->adjustment_remarks ? $installment->adjustment_remarks . ' | ' : '') .
                    date('Y-m-d H:i') . ': ' . $field . ' changed from ' . $old_value . ' to ' . $new_value . ' - ' . $reason,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // If changing payment fields, recalculate total_paid and the status (both ways)
            if (in_array($field, ['principal_paid', 'interest_paid', 'fine_paid', 'emi_amount', 'total_paid'])) {
                $p_paid = $field === 'principal_paid' ? $new_value : $installment->principal_paid;
                $i_paid = $field === 'interest_paid' ? $new_value : $installment->interest_paid;
                $f_paid = $field === 'fine_paid' ? $new_value : $installment->fine_paid;
                $emi    = $field === 'emi_amount' ? (float) $new_value : (float) $installment->emi_amount;
                // A direct total_paid edit keeps the typed total; otherwise total = sum of the parts
                $update_data['total_paid'] = $field === 'total_paid' ? (float) $new_value : round((float)$p_paid + (float)$i_paid + (float)$f_paid, 2);

                $total = $update_data['total_paid'];
                if (!in_array($installment->status, ['skipped', 'waived', 'cancelled', 'interest_only'], true)) {
                    if ($total >= $emi - 0.01) {
                        $update_data['status'] = 'paid';
                        if (empty($installment->paid_date)) $update_data['paid_date'] = date('Y-m-d');
                    } elseif ($total > 0) {
                        $update_data['status'] = 'partial';
                    } else {
                        $update_data['status'] = $installment->due_date < date('Y-m-d') ? 'overdue' : 'pending';
                        $update_data['paid_date'] = null;
                    }
                }
            }

            $this->db->where('id', $installment_id)
                     ->update('loan_installments', $update_data);

            // If we changed payment amounts, also update the loan totals
            if (in_array($field, ['principal_paid', 'interest_paid', 'fine_paid', 'principal_amount', 'interest_amount', 'emi_amount'])) {
                $this->recalculate_loan_totals($installment->loan_id);   // active loans only
            }

            // Log audit trail
            $this->log_audit(
                'adjustment',
                'admin_adjustments',
                'loan_installments',
                $installment_id,
                [$field => $old_value],
                [$field => $new_value],
                'Admin Adjustment: ' . $reason
            );

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return $this->error_response('Database error');
            }

            $this->db->trans_commit();

            $this->json_response([
                'success' => true,
                'message' => ucfirst(str_replace('_', ' ', $field)) . ' updated successfully',
                'old_value' => $old_value,
                'new_value' => $new_value,
                'field' => $field
            ]);

        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->error_response($e->getMessage());
        }
    }

    /**
     * AJAX: Adjust Savings Schedule Entry
     */
    public function adjust_savings_schedule() {
        if ($this->input->method() !== 'post') {
            return $this->error_response('POST required');
        }

        $schedule_id = $this->input->post('schedule_id');
        $field = $this->input->post('field');
        $new_value = $this->input->post('new_value');
        $reason = $this->input->post('reason');

        if (empty($schedule_id) || empty($field) || !isset($new_value) || empty($reason)) {
            return $this->error_response('All fields required');
        }

        $allowed_fields = [
            'due_amount', 'paid_amount', 'fine_amount', 'fine_paid',
            'status', 'due_date', 'paid_date', 'remarks'
        ];

        if (!in_array($field, $allowed_fields)) {
            return $this->error_response('Field "' . $field . '" is not adjustable');
        }
        list($new_value, $verr) = $this->_clean_value('savings_schedule', $field, $new_value);
        if ($verr) return $this->error_response($verr);

        $this->db->trans_begin();

        try {
            $schedule = $this->db->where('id', $schedule_id)
                                ->get('savings_schedule')
                                ->row();

            if (!$schedule) {
                throw new Exception('Schedule entry not found');
            }

            $old_value = $schedule->$field;

            $update_data = [
                $field => $new_value,
                'remarks' => ($schedule->remarks ? $schedule->remarks . ' | ' : '') .
                    date('Y-m-d H:i') . ': ' . $field . ' changed from ' . $old_value . ' to ' . $new_value . ' [ADJ: ' . $reason . ']',
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Auto-status for payment fields
            if (in_array($field, ['paid_amount', 'due_amount'])) {
                $paid = $field === 'paid_amount' ? (float)$new_value : (float)$schedule->paid_amount;
                $due = $field === 'due_amount' ? (float)$new_value : (float)$schedule->due_amount;

                if ($paid >= $due - 0.01 && $due > 0) {
                    $update_data['status'] = 'paid';
                    if (empty($schedule->paid_date)) $update_data['paid_date'] = date('Y-m-d');
                } elseif ($paid > 0) {
                    $update_data['status'] = 'partial';
                } elseif ($schedule->status !== 'waived') {
                    $update_data['status'] = $schedule->due_date < date('Y-m-d') ? 'overdue' : 'pending';
                    $update_data['paid_date'] = null;
                }
            }

            $this->db->where('id', $schedule_id)
                     ->update('savings_schedule', $update_data);

            // Recalculate savings account balance if payment amounts changed
            if (in_array($field, ['paid_amount'])) {
                $this->recalculate_savings_balance($schedule->savings_account_id);
            }

            // Audit trail
            $this->log_audit(
                'adjustment',
                'admin_adjustments',
                'savings_schedule',
                $schedule_id,
                [$field => $old_value],
                [$field => $new_value],
                'Admin Adjustment: ' . $reason
            );

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return $this->error_response('Database error');
            }

            $this->db->trans_commit();

            $this->json_response([
                'success' => true,
                'message' => ucfirst(str_replace('_', ' ', $field)) . ' updated successfully',
                'old_value' => $old_value,
                'new_value' => $new_value
            ]);

        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->error_response($e->getMessage());
        }
    }

    /**
     * AJAX: Adjust Fine
     */
    public function adjust_fine() {
        if ($this->input->method() !== 'post') {
            return $this->error_response('POST required');
        }

        $fine_id = $this->input->post('fine_id');
        $field = $this->input->post('field');
        $new_value = $this->input->post('new_value');
        $reason = $this->input->post('reason');

        if (empty($fine_id) || empty($field) || !isset($new_value) || empty($reason)) {
            return $this->error_response('All fields required');
        }

        $allowed_fields = [
            'fine_amount', 'paid_amount', 'waived_amount', 'balance_amount',
            'status', 'remarks'
        ];

        if (!in_array($field, $allowed_fields)) {
            return $this->error_response('Field "' . $field . '" is not adjustable');
        }
        list($new_value, $verr) = $this->_clean_value('fines', $field, $new_value);
        if ($verr) return $this->error_response($verr);

        $this->db->trans_begin();

        try {
            $fine = $this->db->where('id', $fine_id)
                             ->get('fines')
                             ->row();

            if (!$fine) {
                throw new Exception('Fine not found');
            }

            $old_value = $fine->$field;

            $update_data = [
                $field => $new_value,
                'remarks' => ($fine->remarks ? $fine->remarks . ' | ' : '') .
                    date('Y-m-d H:i') . ': ' . $field . ' ' . $old_value . '→' . $new_value . ' [ADJ: ' . $reason . ']',
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Auto-recalculate balance when amount fields change
            if (in_array($field, ['fine_amount', 'paid_amount', 'waived_amount'])) {
                $fine_amt = $field === 'fine_amount' ? (float)$new_value : (float)$fine->fine_amount;
                $paid_amt = $field === 'paid_amount' ? (float)$new_value : (float)$fine->paid_amount;
                $waived_amt = $field === 'waived_amount' ? (float)$new_value : (float)$fine->waived_amount;
                $balance = $fine_amt - $paid_amt - $waived_amt;

                $update_data['balance_amount'] = max(0, $balance);

                if ($balance <= 0) {
                    $update_data['status'] = $waived_amt > 0 && $paid_amt <= 0 ? 'waived' : 'paid';
                } elseif ($fine->status !== 'cancelled') {
                    $update_data['status'] = $paid_amt > 0 ? 'partial' : 'pending';
                }
            }

            $this->db->where('id', $fine_id)
                     ->update('fines', $update_data);

            // Audit trail
            $this->log_audit(
                'adjustment',
                'admin_adjustments',
                'fines',
                $fine_id,
                [$field => $old_value],
                [$field => $new_value],
                'Admin Adjustment: ' . $reason
            );

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return $this->error_response('Database error');
            }

            $this->db->trans_commit();

            $this->json_response([
                'success' => true,
                'message' => ucfirst(str_replace('_', ' ', $field)) . ' updated successfully',
                'old_value' => $old_value,
                'new_value' => $new_value
            ]);

        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->error_response($e->getMessage());
        }
    }

    /**
     * AJAX: Adjust Loan Header (loan table fields)
     */
    public function adjust_loan() {
        if ($this->input->method() !== 'post') {
            return $this->error_response('POST required');
        }

        $loan_id = $this->input->post('loan_id');
        $field = $this->input->post('field');
        $new_value = $this->input->post('new_value');
        $reason = $this->input->post('reason');

        if (empty($loan_id) || empty($field) || !isset($new_value) || empty($reason)) {
            return $this->error_response('All fields required');
        }

        $allowed_fields = [
            'outstanding_principal', 'outstanding_interest', 'outstanding_fine',
            'total_amount_paid', 'total_principal_paid', 'total_interest_paid', 'total_fine_paid',
            'tenure_months', 'emi_amount', 'status', 'closure_date', 'closure_type'
        ];

        if (!in_array($field, $allowed_fields)) {
            return $this->error_response('Field "' . $field . '" is not adjustable');
        }
        list($new_value, $verr) = $this->_clean_value('loans', $field, $new_value);
        if ($verr) return $this->error_response($verr);

        $this->db->trans_begin();

        try {
            $loan = $this->db->where('id', $loan_id)->get('loans')->row();
            if (!$loan) {
                throw new Exception('Loan not found');
            }

            $old_value = $loan->$field;

            $this->db->where('id', $loan_id)
                     ->update('loans', [
                         $field => $new_value,
                         'updated_at' => date('Y-m-d H:i:s')
                     ]);

            $this->log_audit(
                'adjustment',
                'admin_adjustments',
                'loans',
                $loan_id,
                [$field => $old_value],
                [$field => $new_value],
                'Admin Adjustment: ' . $reason
            );

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return $this->error_response('Database error');
            }

            $this->db->trans_commit();

            $this->json_response([
                'success' => true,
                'message' => ucfirst(str_replace('_', ' ', $field)) . ' updated successfully',
                'old_value' => $old_value,
                'new_value' => $new_value
            ]);

        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->error_response($e->getMessage());
        }
    }

    /**
     * AJAX: Adjust Savings Account (header fields)
     */
    public function adjust_savings_account() {
        if ($this->input->method() !== 'post') {
            return $this->error_response('POST required');
        }

        $account_id = $this->input->post('account_id');
        $field = $this->input->post('field');
        $new_value = $this->input->post('new_value');
        $reason = $this->input->post('reason');

        if (empty($account_id) || empty($field) || !isset($new_value) || empty($reason)) {
            return $this->error_response('All fields required');
        }

        $allowed_fields = ['current_balance', 'total_deposited', 'total_interest_earned', 'total_fines_paid', 'status'];

        if (!in_array($field, $allowed_fields)) {
            return $this->error_response('Field "' . $field . '" is not adjustable');
        }
        list($new_value, $verr) = $this->_clean_value('savings_accounts', $field, $new_value);
        if ($verr) return $this->error_response($verr);

        $this->db->trans_begin();

        try {
            $account = $this->db->where('id', $account_id)->get('savings_accounts')->row();
            if (!$account) {
                throw new Exception('Savings account not found');
            }

            $old_value = $account->$field;

            $this->db->where('id', $account_id)
                     ->update('savings_accounts', [
                         $field => $new_value,
                         'updated_at' => date('Y-m-d H:i:s')
                     ]);

            $this->log_audit(
                'adjustment',
                'admin_adjustments',
                'savings_accounts',
                $account_id,
                [$field => $old_value],
                [$field => $new_value],
                'Admin Adjustment: ' . $reason
            );

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return $this->error_response('Database error');
            }

            $this->db->trans_commit();

            $this->json_response([
                'success' => true,
                'message' => ucfirst(str_replace('_', ' ', $field)) . ' updated successfully',
                'old_value' => $old_value,
                'new_value' => $new_value
            ]);

        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->error_response($e->getMessage());
        }
    }

    /**
     * AJAX: Bulk Recalculate Loan Totals from Installments
     * Recalculates loan outstanding amounts from installment data
     */
    public function recalc_loan($loan_id = null) {
        if (!$loan_id) {
            return $this->error_response('Loan ID required');
        }

        $this->db->trans_begin();

        try {
            $before = $this->db->where('id', $loan_id)->get('loans')->row();
            if (!$before) {
                throw new Exception('Loan not found');
            }

            $skip = $this->recalculate_loan_totals($loan_id);
            if ($skip !== true) {
                $this->db->trans_rollback();
                return $this->error_response($skip);
            }

            $after = $this->db->where('id', $loan_id)->get('loans')->row();

            $this->log_audit(
                'recalculation',
                'admin_adjustments',
                'loans',
                $loan_id,
                [
                    'outstanding_principal' => $before->outstanding_principal,
                    'outstanding_interest' => $before->outstanding_interest,
                    'total_principal_paid' => $before->total_principal_paid,
                    'total_interest_paid' => $before->total_interest_paid
                ],
                [
                    'outstanding_principal' => $after->outstanding_principal,
                    'outstanding_interest' => $after->outstanding_interest,
                    'total_principal_paid' => $after->total_principal_paid,
                    'total_interest_paid' => $after->total_interest_paid
                ],
                'Admin triggered loan recalculation'
            );

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return $this->error_response('Database error');
            }

            $this->db->trans_commit();

            $this->json_response([
                'success' => true,
                'message' => 'Loan totals recalculated successfully',
                'before' => [
                    'outstanding_principal' => $before->outstanding_principal,
                    'outstanding_interest' => $before->outstanding_interest
                ],
                'after' => [
                    'outstanding_principal' => $after->outstanding_principal,
                    'outstanding_interest' => $after->outstanding_interest
                ]
            ]);

        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->error_response($e->getMessage());
        }
    }

    /**
     * AJAX: Recalculate Savings Balance from Schedule
     */
    public function recalc_savings($account_id = null) {
        if (!$account_id) {
            return $this->error_response('Account ID required');
        }

        $this->db->trans_begin();

        try {
            $before = $this->db->where('id', $account_id)->get('savings_accounts')->row();
            if (!$before) {
                throw new Exception('Savings account not found');
            }

            $this->recalculate_savings_balance($account_id);

            $after = $this->db->where('id', $account_id)->get('savings_accounts')->row();

            $this->log_audit(
                'recalculation',
                'admin_adjustments',
                'savings_accounts',
                $account_id,
                ['current_balance' => $before->current_balance, 'total_deposited' => $before->total_deposited],
                ['current_balance' => $after->current_balance, 'total_deposited' => $after->total_deposited],
                'Admin triggered savings recalculation'
            );

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return $this->error_response('Database error');
            }

            $this->db->trans_commit();

            $this->json_response([
                'success' => true,
                'message' => 'Savings balance recalculated',
                'before' => ['current_balance' => $before->current_balance],
                'after' => ['current_balance' => $after->current_balance]
            ]);

        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->error_response($e->getMessage());
        }
    }

    /**
     * AJAX: Get adjustment audit history for a record
     */
    public function audit_history() {
        $table = $this->input->get('table');
        $record_id = $this->input->get('record_id');

        if (empty($table) || empty($record_id)) {
            return $this->error_response('table and record_id required');
        }

        $logs = $this->db->select('a.*, au.full_name as admin_name')
                         ->from('audit_logs a')
                         ->join('admin_users au', 'au.id = a.user_id', 'left')
                         ->where('a.table_name', $table)
                         ->where('a.record_id', $record_id)
                         ->where('a.module', 'admin_adjustments')
                         ->order_by('a.created_at', 'DESC')
                         ->limit(50)
                         ->get()
                         ->result();

        $this->json_response(['success' => true, 'logs' => $logs]);
    }

    /**
     * AJAX: Delete Loan Installment (duplicate/erroneous entry removal)
     * Recalculates loan totals after deletion.
     */
    /**
     * AJAX: Delete Loan Installment — only an installment nothing was paid against
     * (duplicate / wrongly generated row). Paid installments must be reversed through
     * their payment instead, otherwise the money disappears from the schedule.
     */
    public function delete_loan_installment() {
        if ($this->input->method() !== 'post') {
            return $this->error_response('POST required');
        }

        $installment_id = (int)$this->input->post('installment_id');
        $reason         = trim((string)$this->input->post('reason'));

        if (!$installment_id || strlen($reason) < 5) {
            return $this->error_response('installment_id and reason (min 5 chars) are required');
        }

        $installment = $this->db->where('id', $installment_id)->get('loan_installments')->row();
        if (!$installment) {
            return $this->error_response('Installment not found');
        }
        $payments = $this->db->where('installment_id', $installment_id)->where('is_reversed', 0)->count_all_results('loan_payments');
        if ((float) $installment->total_paid > 0 || $payments > 0) {
            return $this->error_response('Installment #' . $installment->installment_number . ' has payments (₹' . number_format((float) $installment->total_paid, 2)
                . ', ' . $payments . ' payment record(s)). Reverse those payments first, or edit the paid amounts.');
        }

        $this->db->trans_begin();

        try {
            $loan_id = $installment->loan_id;

            $this->db->where('id', $installment_id)->delete('loan_installments');
            // Late fines raised on this installment go with it (only unpaid ones can exist here)
            $this->db->where('related_type', 'loan_installment')->where('related_id', $installment_id)
                     ->where('paid_amount', 0)->where_in('status', ['pending', 'partial'])
                     ->update('fines', ['status' => 'cancelled', 'balance_amount' => 0, 'updated_at' => date('Y-m-d H:i:s'),
                                        'admin_comments' => 'Cancelled: installment deleted in Admin Adjustments — ' . $reason]);

            $this->recalculate_loan_totals($loan_id);

            $this->log_audit(
                'delete',
                'admin_adjustments',
                'loan_installments',
                $installment_id,
                (array)$installment,
                [],
                'Admin Deleted Installment #' . $installment->installment_number . ': ' . $reason
            );

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return $this->error_response('Database error');
            }

            $this->db->trans_commit();

            $this->json_response([
                'success'  => true,
                'message'  => 'Installment #' . $installment->installment_number . ' deleted. Loan totals recalculated.',
                'loan_id'  => $loan_id
            ]);

        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->error_response($e->getMessage());
        }
    }

    /**
     * AJAX: Delete Savings Schedule Entry — only an entry with nothing paid against it.
     */
    public function delete_savings_schedule_entry() {
        if ($this->input->method() !== 'post') {
            return $this->error_response('POST required');
        }

        $schedule_id = (int)$this->input->post('schedule_id');
        $reason      = trim((string)$this->input->post('reason'));

        if (!$schedule_id || strlen($reason) < 5) {
            return $this->error_response('schedule_id and reason (min 5 chars) are required');
        }

        $entry = $this->db->where('id', $schedule_id)->get('savings_schedule')->row();
        if (!$entry) {
            return $this->error_response('Schedule entry not found');
        }
        $txns = $this->db->where('schedule_id', $schedule_id)->where('is_reversed', 0)->count_all_results('savings_transactions');
        if ((float) $entry->paid_amount > 0 || $txns > 0) {
            return $this->error_response('This month has deposits (₹' . number_format((float) $entry->paid_amount, 2) . ', ' . $txns
                . ' transaction(s)). Reverse the deposit first, or edit the paid amount.');
        }

        $this->db->trans_begin();

        try {
            $account_id = $entry->savings_account_id;

            $this->db->where('id', $schedule_id)->delete('savings_schedule');
            $this->db->where('related_type', 'savings_schedule')->where('related_id', $schedule_id)
                     ->where('paid_amount', 0)->where_in('status', ['pending', 'partial'])
                     ->update('fines', ['status' => 'cancelled', 'balance_amount' => 0, 'updated_at' => date('Y-m-d H:i:s'),
                                        'admin_comments' => 'Cancelled: savings due deleted in Admin Adjustments — ' . $reason]);

            $this->log_audit(
                'delete',
                'admin_adjustments',
                'savings_schedule',
                $schedule_id,
                (array)$entry,
                [],
                'Admin Deleted Schedule Entry ' . $entry->due_month . ': ' . $reason
            );

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return $this->error_response('Database error');
            }

            $this->db->trans_commit();

            $this->json_response([
                'success'    => true,
                'message'    => 'Schedule entry for ' . $entry->due_month . ' deleted.',
                'account_id' => $account_id
            ]);

        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->error_response($e->getMessage());
        }
    }

    /**
     * AJAX: "Delete" Loan Payment = full reversal (kept as a reversed record for audit).
     *  - installment paid amounts and status restored, loan totals restored, loan reopened if it closed it
     *  - savings adjustments made with the payment (shortfall from savings / excess to savings) reversed
     *  - ledger mirrored with a contra voucher
     *  - if the payment came from a bank mapping, that mapping is reversed so the bank line can be mapped again
     * The old version hard-deleted the row: installments stayed "paid", the ledger kept the receipt.
     */
    public function delete_loan_payment() {
        if ($this->input->method() !== 'post') {
            return $this->error_response('POST required');
        }

        $payment_id = (int)$this->input->post('payment_id');
        $reason     = trim((string)$this->input->post('reason'));

        if (!$payment_id || strlen($reason) < 5) {
            return $this->error_response('payment_id and reason (min 5 chars) are required');
        }

        $payment = $this->db->where('id', $payment_id)->get('loan_payments')->row();
        if (!$payment) {
            return $this->error_response('Payment not found');
        }
        if (!empty($payment->is_reversed)) {
            return $this->error_response('This payment is already reversed');
        }

        $this->load->model('Bank_model');
        $admin_id = $this->session->userdata('admin_id');
        $reason_full = 'Admin Adjustments: ' . $reason;

        $mapping = null;
        if ($this->db->table_exists('transaction_mappings')) {
            $mapping = $this->db->where_in('mapping_type', ['emi', 'loan_payment'])->where('related_id', $payment_id)
                                ->where('is_reversed', 0)->order_by('id', 'DESC')->get('transaction_mappings')->row();
        }

        $this->db->trans_begin();

        try {
            if ($mapping) {
                // Bank-mapped payment: the mapping reversal undoes payment, savings adjustments,
                // ledger and frees the bank line in one go
                $ok = $this->Bank_model->reverse_mapping($mapping->id, $reason_full, $admin_id);
                if (!$ok) throw new Exception('Could not reverse the bank mapping of this payment');
                $how = 'Bank mapping reversed — the bank line can be mapped again.';
            } else {
                if (!$this->Bank_model->reverse_loan_payment_effects($payment_id, $reason_full, $admin_id)) {
                    throw new Exception('Could not reverse the payment');
                }
                $this->Ledger_model->reverse_source('loan_payment', $payment_id, $reason_full, date('Y-m-d'), $admin_id);
                $this->Ledger_model->reverse_source('loan_write_off', $payment_id, $reason_full, date('Y-m-d'), $admin_id);
                $how = 'Installments, loan totals, savings adjustments and ledger restored.';
            }

            $this->log_audit(
                'reverse',
                'admin_adjustments',
                'loan_payments',
                $payment_id,
                (array)$payment,
                ['is_reversed' => 1],
                'Admin Reversed Payment ' . $payment->payment_code . ': ' . $reason
            );

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return $this->error_response('Database error');
            }

            $this->db->trans_commit();

            $this->json_response([
                'success' => true,
                'message' => 'Payment ' . $payment->payment_code . ' reversed. ' . $how,
                'loan_id' => $payment->loan_id
            ]);

        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->error_response($e->getMessage());
        }
    }

    /**
     * AJAX: "Delete" Savings Transaction = reversal (kept, marked reversed).
     * Balance recalculated, the month in the schedule reopened, ledger mirrored;
     * a bank-mapped deposit is unmapped so the bank line can be mapped again.
     */
    public function delete_savings_transaction() {
        if ($this->input->method() !== 'post') {
            return $this->error_response('POST required');
        }

        $txn_id = (int)$this->input->post('txn_id');
        $reason = trim((string)$this->input->post('reason'));

        if (!$txn_id || strlen($reason) < 5) {
            return $this->error_response('txn_id and reason (min 5 chars) are required');
        }

        $txn = $this->db->where('id', $txn_id)->get('savings_transactions')->row();
        if (!$txn) {
            return $this->error_response('Transaction not found');
        }
        if (!empty($txn->is_reversed)) {
            return $this->error_response('This transaction is already reversed');
        }
        if ($txn->payment_mode === 'adjustment' && preg_match('/Loan:\s*\S+/', (string) $txn->narration)) {
            return $this->error_response('This is an automatic loan adjustment (EMI shortfall / excess). Reverse the loan payment it belongs to instead — that reverses this entry too.');
        }

        $admin_id = $this->session->userdata('admin_id');
        $reason_full = 'Admin Adjustments: ' . $reason;
        $mapping = null;
        if ($this->db->table_exists('transaction_mappings')) {
            $mapping = $this->db->where('mapping_type', 'savings')->where('related_id', $txn_id)
                                ->where('is_reversed', 0)->order_by('id', 'DESC')->get('transaction_mappings')->row();
        }

        $this->db->trans_begin();

        try {
            if ($mapping) {
                $this->load->model('Bank_model');
                if (!$this->Bank_model->reverse_mapping($mapping->id, $reason_full, $admin_id)) {
                    throw new Exception('Could not reverse the bank mapping of this deposit');
                }
                $how = 'Bank mapping reversed — the bank line can be mapped again.';
            } else {
                $res = $this->Savings_model->reverse_transaction($txn_id, $reason_full, $admin_id);
                if (empty($res['success'])) throw new Exception($res['error'] ?? 'Could not reverse the transaction');
                foreach (['savings_transaction', 'savings_deposit', 'savings_withdrawal'] as $rt) {
                    $this->Ledger_model->reverse_source($rt, $txn_id, $reason_full, date('Y-m-d'), $admin_id);
                }
                $how = 'Balance and ledger restored.';
            }

            // Reopen the schedule month this deposit paid
            if ($txn->transaction_type === 'deposit' && !empty($txn->schedule_id)) {
                $sch = $this->db->where('id', $txn->schedule_id)->get('savings_schedule')->row();
                if ($sch) {
                    $paid = max(0, round((float) $sch->paid_amount - (float) $txn->amount, 2));
                    $status = $paid >= (float) $sch->due_amount - 0.01 && $paid > 0 ? 'paid' : ($paid > 0 ? 'partial' : ($sch->due_date < date('Y-m-d') ? 'overdue' : 'pending'));
                    $this->db->where('id', $sch->id)->update('savings_schedule', [
                        'paid_amount' => $paid, 'status' => $status, 'paid_date' => $paid > 0 ? $sch->paid_date : null, 'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
            $this->recalculate_savings_balance($txn->savings_account_id);

            $this->log_audit(
                'reverse',
                'admin_adjustments',
                'savings_transactions',
                $txn_id,
                (array)$txn,
                ['is_reversed' => 1],
                'Admin Reversed Savings Transaction: ' . $reason
            );

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return $this->error_response('Database error');
            }

            $this->db->trans_commit();

            $this->json_response([
                'success'    => true,
                'message'    => 'Transaction reversed. ' . $how,
                'account_id' => $txn->savings_account_id
            ]);

        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->error_response($e->getMessage());
        }
    }

    /**
     * AJAX: Delete Fine entry — only a fine with nothing collected. A fine that has
     * receipts would leave fine income in the ledger with no fine behind it.
     */
    public function delete_fine_entry() {
        if ($this->input->method() !== 'post') {
            return $this->error_response('POST required');
        }

        $fine_id = (int)$this->input->post('fine_id');
        $reason  = trim((string)$this->input->post('reason'));

        if (!$fine_id || strlen($reason) < 5) {
            return $this->error_response('fine_id and reason (min 5 chars) are required');
        }

        $fine = $this->db->where('id', $fine_id)->get('fines')->row();
        if (!$fine) {
            return $this->error_response('Fine not found');
        }
        if ((float) $fine->paid_amount > 0) {
            return $this->error_response('Fine ' . $fine->fine_code . ' has ₹' . number_format((float) $fine->paid_amount, 2)
                . ' collected. Reverse that receipt (Bank mapping / Fines) first, or set its amount with Adjust.');
        }

        $this->db->trans_begin();

        try {
            $this->db->where('id', $fine_id)->delete('fines');

            $this->log_audit(
                'delete',
                'admin_adjustments',
                'fines',
                $fine_id,
                (array)$fine,
                [],
                'Admin Deleted Fine ' . $fine->fine_code . ': ' . $reason
            );

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return $this->error_response('Database error');
            }

            $this->db->trans_commit();

            $this->json_response([
                'success' => true,
                'message' => 'Fine ' . $fine->fine_code . ' deleted successfully'
            ]);

        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->error_response($e->getMessage());
        }
    }

    // ─── Private Helper Methods ───

    /**
     * Recalculate loan totals from installments — active / NPA loans only.
     * Closed, foreclosed and written-off loans are final: their principal was settled
     * or waived outside the schedule, so rebuilding from installments would put the
     * waived principal back. Returns true, or a message why nothing was changed.
     */
    private function recalculate_loan_totals($loan_id) {
        $loan = $this->db->where('id', $loan_id)->get('loans')->row();
        if (!$loan) return 'Loan not found';
        if (!in_array($loan->status, ['active', 'npa'], true)) {
            return 'Loan ' . $loan->loan_number . ' is ' . $loan->status . ' — its totals are final and were not recalculated.';
        }

        $totals = $this->db->select('
            SUM(interest_amount) as total_interest_scheduled,
            SUM(principal_paid) as total_principal_paid,
            SUM(interest_paid) as total_interest_paid,
            SUM(fine_paid) as total_fine_paid,
            SUM(total_paid) as total_paid
        ')
        ->where('loan_id', $loan_id)
        ->where_not_in('status', ['cancelled'])
        ->get('loan_installments')
        ->row();

        $outstanding_principal = (float) $loan->principal_amount - (float)$totals->total_principal_paid;
        $outstanding_interest  = (float)$totals->total_interest_scheduled - (float)$totals->total_interest_paid;

        $this->db->where('id', $loan_id)
                 ->update('loans', [
                     'outstanding_principal' => max(0, round($outstanding_principal, 2)),
                     'outstanding_interest' => max(0, round($outstanding_interest, 2)),
                     'total_principal_paid' => round((float)$totals->total_principal_paid, 2),
                     'total_interest_paid' => round((float)$totals->total_interest_paid, 2),
                     'total_fine_paid' => round((float)$totals->total_fine_paid, 2),
                     // installment total_paid already includes fine_paid (it was added twice before)
                     'total_amount_paid' => round((float)$totals->total_paid, 2),
                     'updated_at' => date('Y-m-d H:i:s')
                 ]);
        return true;
    }

    /**
     * Recalculate savings balance from transactions — the same rule as the Savings screen
     * (Savings_model), so both always agree.
     */
    private function recalculate_savings_balance($account_id) {
        return $this->Savings_model->recalculate_account_balance($account_id);
    }

    /**
     * Validate / normalise a value typed into an adjust cell.
     * Returns [value, error|null].
     */
    private function _clean_value($table, $field, $value) {
        $value = is_string($value) ? trim($value) : $value;
        $money = ['principal_amount', 'interest_amount', 'emi_amount', 'principal_paid', 'interest_paid', 'fine_amount', 'fine_paid',
                  'total_paid', 'due_amount', 'paid_amount', 'waived_amount', 'balance_amount', 'outstanding_principal', 'outstanding_interest',
                  'outstanding_fine', 'total_amount_paid', 'total_principal_paid', 'total_interest_paid', 'total_fine_paid',
                  'current_balance', 'total_deposited', 'total_interest_earned', 'total_fines_paid'];
        $dates = ['due_date', 'paid_date', 'closure_date'];
        $statuses = [
            'loan_installments' => ['upcoming', 'pending', 'partial', 'paid', 'overdue', 'skipped', 'interest_only', 'waived', 'cancelled'],
            'savings_schedule'  => ['pending', 'partial', 'paid', 'overdue', 'waived'],
            'fines'             => ['pending', 'partial', 'paid', 'waived', 'cancelled'],
            'loans'             => ['active', 'closed', 'foreclosed', 'written_off', 'npa'],
            'savings_accounts'  => ['active', 'matured', 'closed', 'suspended'],
        ];

        if (in_array($field, $money, true)) {
            if ($value === '' || !is_numeric($value)) return [null, 'Enter a number for ' . str_replace('_', ' ', $field)];
            if ((float) $value < 0) return [null, str_replace('_', ' ', ucfirst($field)) . ' cannot be negative'];
            return [round((float) $value, 2), null];
        }
        if ($field === 'tenure_months') {
            if (!ctype_digit((string) $value) || (int) $value < 1 || (int) $value > 600) return [null, 'Tenure must be a whole number of months (1–600)'];
            return [(int) $value, null];
        }
        if (in_array($field, $dates, true)) {
            if ($value === '' || $value === null) {
                return $field === 'due_date' ? [null, 'Due date cannot be empty'] : [null, null];
            }
            $d = DateTime::createFromFormat('!Y-m-d', (string) $value);
            if (!$d || $d->format('Y-m-d') !== (string) $value) return [null, 'Use a date in YYYY-MM-DD format'];
            return [$value, null];
        }
        if ($field === 'status') {
            if (!in_array($value, $statuses[$table] ?? [], true)) return [null, 'Invalid status. Allowed: ' . implode(', ', $statuses[$table] ?? [])];
            return [$value, null];
        }
        if ($field === 'closure_type') {
            if ($value === '') return [null, null];
            if (!in_array($value, ['regular', 'foreclosure', 'force_close', 'write_off', 'settlement', 'topup'], true)) return [null, 'Invalid closure type'];
            return [$value, null];
        }
        if ($field === 'remarks') return [mb_substr((string) $value, 0, 255), null];
        return [$value, null];
    }
}
