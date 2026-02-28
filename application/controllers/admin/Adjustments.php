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
            
            $installments = $this->db->where('loan_id', $loan_id)
                                     ->order_by('installment_number', 'ASC')
                                     ->get('loan_installments')
                                     ->result();
            
            // Get payments for this loan
            $payments = $this->db->where('loan_id', $loan_id)
                                 ->where('is_reversed', 0)
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
            
            $schedule = $this->db->where('savings_account_id', $account_id)
                                 ->order_by('due_month', 'ASC')
                                 ->get('savings_schedule')
                                 ->result();
            
            // Get transactions
            $transactions = $this->db->where('savings_account_id', $account_id)
                                     ->where('is_reversed', 0)
                                     ->order_by('transaction_date', 'DESC')
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
        
        // Allowed status values
        $allowed_statuses = ['upcoming', 'pending', 'partial', 'paid', 'overdue', 'skipped', 'interest_only', 'waived'];
        if ($field === 'status' && !in_array($new_value, $allowed_statuses)) {
            return $this->error_response('Invalid status value');
        }
        
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
            
            // If changing payment fields, recalculate total_paid
            if (in_array($field, ['principal_paid', 'interest_paid', 'fine_paid'])) {
                $p_paid = $field === 'principal_paid' ? $new_value : $installment->principal_paid;
                $i_paid = $field === 'interest_paid' ? $new_value : $installment->interest_paid;
                $f_paid = $field === 'fine_paid' ? $new_value : $installment->fine_paid;
                $update_data['total_paid'] = (float)$p_paid + (float)$i_paid + (float)$f_paid;
                
                // Auto-update status based on payment
                $total = $update_data['total_paid'];
                if ($total >= $installment->emi_amount) {
                    $update_data['status'] = 'paid';
                } elseif ($total > 0) {
                    $update_data['status'] = 'partial';
                }
            }
            
            $this->db->where('id', $installment_id)
                     ->update('loan_installments', $update_data);
            
            // If we changed payment amounts, also update the loan totals
            if (in_array($field, ['principal_paid', 'interest_paid', 'fine_paid', 'principal_amount', 'interest_amount'])) {
                $this->recalculate_loan_totals($installment->loan_id);
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
                
                if ($paid >= $due) {
                    $update_data['status'] = 'paid';
                } elseif ($paid > 0) {
                    $update_data['status'] = 'partial';
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
                    $update_data['status'] = $waived_amt > 0 ? 'waived' : 'paid';
                } elseif ($paid_amt > 0) {
                    $update_data['status'] = 'partial';
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
            
            $this->recalculate_loan_totals($loan_id);
            
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

    // ─── Private Helper Methods ───

    /**
     * Recalculate loan totals from installments
     */
    private function recalculate_loan_totals($loan_id) {
        $loan = $this->db->where('id', $loan_id)->get('loans')->row();
        if (!$loan) return;
        
        // Sum up from installments
        $totals = $this->db->select('
            SUM(principal_amount) as total_principal_scheduled,
            SUM(interest_amount) as total_interest_scheduled,
            SUM(principal_paid) as total_principal_paid,
            SUM(interest_paid) as total_interest_paid,
            SUM(fine_paid) as total_fine_paid,
            SUM(total_paid) as total_paid
        ')
        ->where('loan_id', $loan_id)
        ->get('loan_installments')
        ->row();
        
        $outstanding_principal = $loan->principal_amount - (float)$totals->total_principal_paid;
        $outstanding_interest = (float)$totals->total_interest_scheduled - (float)$totals->total_interest_paid;
        
        $this->db->where('id', $loan_id)
                 ->update('loans', [
                     'outstanding_principal' => max(0, round($outstanding_principal, 2)),
                     'outstanding_interest' => max(0, round($outstanding_interest, 2)),
                     'total_principal_paid' => round((float)$totals->total_principal_paid, 2),
                     'total_interest_paid' => round((float)$totals->total_interest_paid, 2),
                     'total_fine_paid' => round((float)$totals->total_fine_paid, 2),
                     'total_amount_paid' => round((float)$totals->total_paid + (float)$totals->total_fine_paid, 2),
                     'updated_at' => date('Y-m-d H:i:s')
                 ]);
    }

    /**
     * Recalculate savings balance from transactions
     */
    private function recalculate_savings_balance($account_id) {
        // Sum deposits
        $deposits = $this->db->select_sum('amount')
                             ->where('savings_account_id', $account_id)
                             ->where('is_reversed', 0)
                             ->where_in('transaction_type', ['deposit', 'interest_credit', 'opening_balance'])
                             ->get('savings_transactions')
                             ->row()
                             ->amount ?? 0;
        
        // Sum withdrawals
        $withdrawals = $this->db->select_sum('amount')
                                ->where('savings_account_id', $account_id)
                                ->where('is_reversed', 0)
                                ->where_in('transaction_type', ['withdrawal'])
                                ->get('savings_transactions')
                                ->row()
                                ->amount ?? 0;
        
        $balance = (float)$deposits - (float)$withdrawals;
        
        $this->db->where('id', $account_id)
                 ->update('savings_accounts', [
                     'current_balance' => round($balance, 2),
                     'total_deposited' => round((float)$deposits, 2),
                     'updated_at' => date('Y-m-d H:i:s')
                 ]);
    }
}
