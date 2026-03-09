<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Installments Controller - EMI & Payment Management
 */
class Installments extends Admin_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model(['Loan_model', 'Member_model']);
    }
    
    /**
     * EMI Schedule - All Installments
     */
    public function index() {
        $data['title'] = 'EMI Schedule';
        $data['page_title'] = 'Installment Management';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Installments', 'url' => '']
        ];
        
        // Filters
        $status = $this->input->get('status') ?: 'pending';
        $month = $this->input->get('month') ?: date('Y-m');
        $loan_id = $this->input->get('loan_id');
        
        // Build query
        $this->db->select('li.*, l.loan_number, l.member_id, m.member_code, m.first_name, m.last_name, m.phone, lp.product_name');
        $this->db->from('loan_installments li');
        $this->db->join('loans l', 'l.id = li.loan_id');
        $this->db->join('members m', 'm.id = l.member_id');
        $this->db->join('loan_products lp', 'lp.id = l.loan_product_id');
        
        if ($status !== 'all') {
            if ($status == 'overdue') {
                $this->db->where('li.status', 'pending');
                $this->db->where('li.due_date <', date('Y-m-d'));
            } else {
                $this->db->where('li.status', $status);
            }
        }
        
        if ($month) {
            list($year, $month_num) = explode('-', $month);
            $this->db->where('YEAR(li.due_date)', $year);
            $this->db->where('MONTH(li.due_date)', $month_num);
        }
        
        if ($loan_id) {
            $this->db->where('li.loan_id', $loan_id);
        }
        
        $this->db->where_in('l.status', ['active', 'overdue', 'npa']);
        $this->db->order_by('li.due_date', 'ASC');
        $this->db->order_by('m.member_code', 'ASC');
        
        $data['installments'] = $this->db->get()->result();
        $data['status'] = $status;
        $data['month'] = $month;
        $data['loan_id'] = $loan_id;
        
        // Calculate totals
        $data['total_emi'] = array_sum(array_column($data['installments'], 'emi_amount'));
        $data['total_paid'] = array_sum(array_column($data['installments'], 'total_paid'));
        $data['total_pending'] = $data['total_emi'] - $data['total_paid'];
        
        // Get active loans for filter
        $data['active_loans'] = $this->db->select('l.id, l.loan_number, m.member_code, m.first_name, m.last_name')
                                         ->from('loans l')
                                         ->join('members m', 'm.id = l.member_id')
                                         ->where_in('l.status', ['active', 'overdue', 'npa'])
                                         ->order_by('l.loan_number', 'DESC')
                                         ->get()
                                         ->result();
        
        $this->load_view('admin/installments/index', $data);
    }
    
    /**
     * Due Today
     */
    public function due_today() {
        $data['title'] = 'EMI Due Today';
        $data['page_title'] = 'Installments Due Today';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Installments', 'url' => 'admin/installments'],
            ['title' => 'Due Today', 'url' => '']
        ];
        
        $today = date('Y-m-d');

        // Sync: promote 'upcoming' installments due today → 'pending' BEFORE building the SELECT.
        // Must be a standalone query (separate from the query below) so CI3's query builder
        // is clean when we start building the SELECT query.
        $this->db->where('status', 'upcoming')
                 ->where('due_date <=', $today)
                 ->update('loan_installments', ['status' => 'pending', 'updated_at' => date('Y-m-d H:i:s')]);

        $this->db->select('li.*, l.loan_number, l.member_id, l.loan_product_id, m.member_code, m.first_name, m.last_name, m.phone, lp.product_name');
        $this->db->from('loan_installments li');
        $this->db->join('loans l', 'l.id = li.loan_id');
        $this->db->join('members m', 'm.id = l.member_id');
        $this->db->join('loan_products lp', 'lp.id = l.loan_product_id');
        $this->db->where('li.due_date', $today);
        $this->db->where_in('li.status', ['pending', 'partial']);
        $this->db->where_in('l.status', ['active', 'overdue']);
        $this->db->order_by('m.member_code', 'ASC');
        
        $data['installments'] = $this->db->get()->result();
        
        // Calculate totals
        $data['total_count'] = count($data['installments']);
        $data['total_amount'] = array_sum(array_column($data['installments'], 'emi_amount'));
        $data['total_collected'] = array_sum(array_column($data['installments'], 'total_paid'));
        $data['total_pending'] = $data['total_amount'] - $data['total_collected'];
        
        $this->load_view('admin/installments/due_today', $data);
    }
    
    /**
     * Upcoming Installments
     */
    public function upcoming() {
        $data['title'] = 'Upcoming EMIs';
        $data['page_title'] = 'Upcoming Installments';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Installments', 'url' => 'admin/installments'],
            ['title' => 'Upcoming', 'url' => '']
        ];
        
        // Default to 30 days — monthly EMI schedules need at least a full month window.
        $days = (int) ($this->input->get('days') ?: 30);
        $today = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime("+$days days"));

        // Before querying, run the status-sync inline so the page is always accurate
        // even when the daily cron has not yet executed today.
        $this->db->where('status', 'upcoming')
                 ->where('due_date <=', $today)
                 ->update('loan_installments', ['status' => 'pending', 'updated_at' => date('Y-m-d H:i:s')]);
        
        $this->db->select('li.*, l.loan_number, l.member_id, m.member_code, m.first_name, m.last_name, m.phone, lp.product_name');
        $this->db->from('loan_installments li');
        $this->db->join('loans l', 'l.id = li.loan_id');
        $this->db->join('members m', 'm.id = l.member_id');
        $this->db->join('loan_products lp', 'lp.id = l.loan_product_id');
        $this->db->where('li.due_date >', $today);
        $this->db->where('li.due_date <=', $end_date);
        $this->db->where_in('li.status', ['pending', 'upcoming']);
        $this->db->where_in('l.status', ['active', 'overdue']);
        $this->db->order_by('li.due_date', 'ASC');
        
        $data['installments'] = $this->db->get()->result();
        $data['days'] = $days;
        $data['end_date'] = $end_date;
        
        // Calculate totals
        $data['total_count'] = count($data['installments']);
        $data['total_amount'] = array_sum(array_column($data['installments'], 'emi_amount'));
        
        $this->load_view('admin/installments/upcoming', $data);
    }
    
    /**
     * Overdue Installments
     */
    public function overdue() {
        $data['title'] = 'Overdue EMIs';
        $data['page_title'] = 'Overdue Installments';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Installments', 'url' => 'admin/installments'],
            ['title' => 'Overdue', 'url' => '']
        ];
        
        $today = date('Y-m-d');

        // Sync: promote any 'upcoming' installments that have now become due/overdue
        $this->db->where_in('status', ['upcoming'])
                 ->where('due_date <', $today)
                 ->update('loan_installments', ['status' => 'overdue', 'updated_at' => date('Y-m-d H:i:s')]);

        $this->db->select('li.*, l.loan_number, l.member_id, m.member_code, m.first_name, m.last_name, m.phone, lp.product_name, DATEDIFF(CURDATE(), li.due_date) as days_overdue');
        $this->db->from('loan_installments li');
        $this->db->join('loans l', 'l.id = li.loan_id');
        $this->db->join('members m', 'm.id = l.member_id');
        $this->db->join('loan_products lp', 'lp.id = l.loan_product_id');
        // Include 'partial' — installments where a partial payment was made but full EMI is still outstanding past due date
        $this->db->where_in('li.status', ['pending', 'overdue', 'partial']);
        $this->db->where('li.due_date <', $today);
        $this->db->where_in('l.status', ['active', 'overdue', 'npa']);
        $this->db->order_by('li.due_date', 'ASC');
        
        $data['installments'] = $this->db->get()->result();
        
        // Calculate totals
        $data['total_count'] = count($data['installments']);
        $data['total_amount'] = array_sum(array_column($data['installments'], 'emi_amount'));
        $data['total_collected'] = array_sum(array_column($data['installments'], 'total_paid'));
        $data['total_overdue'] = $data['total_amount'] - $data['total_collected'];
        
        // Group by overdue period
        $data['critical'] = array_filter($data['installments'], function($inst) {
            return ($inst->days_overdue ?? 0) > 90;
        });
        $data['serious'] = array_filter($data['installments'], function($inst) {
            $days = $inst->days_overdue ?? 0;
            return $days > 30 && $days <= 90;
        });
        $data['recent'] = array_filter($data['installments'], function($inst) {
            return ($inst->days_overdue ?? 0) <= 30;
        });
        
        $this->load_view('admin/installments/overdue', $data);
    }
    
    /**
     * View Single Installment
     */
    public function view($id) {
        $installment = $this->db->select('li.*, l.*, m.member_code, m.first_name, m.last_name, m.phone, lp.product_name')
                                ->from('loan_installments li')
                                ->join('loans l', 'l.id = li.loan_id')
                                ->join('members m', 'm.id = l.member_id')
                                ->join('loan_products lp', 'lp.id = l.loan_product_id')
                                ->where('li.id', $id)
                                ->get()
                                ->row();
        
        if (!$installment) {
            $this->session->set_flashdata('error', 'Installment not found.');
            redirect('admin/installments');
        }
        
        $data['installment'] = $installment;
        $data['title'] = 'Installment Details';
        $data['page_title'] = 'EMI #' . $installment->installment_number . ' - ' . $installment->loan_number;
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Installments', 'url' => 'admin/installments'],
            ['title' => 'View', 'url' => '']
        ];
        
        // Get payment history for this installment
        $data['payments'] = $this->db->where('installment_id', $id)
                                    ->where('is_reversed', 0)
                                    ->order_by('payment_date', 'DESC')
                                    ->get('loan_payments')
                                    ->result();
        
        // Get fines
        $data['fines'] = $this->db->where('related_type', 'loan_installment')
                                  ->where('related_id', $id)
                                  ->get('fines')
                                  ->result();
        
        $this->load_view('admin/installments/view', $data);
    }
    
    /**
     * Bulk Collection Sheet
     */
    public function collection_sheet() {
        $data['title'] = 'Collection Sheet';
        $data['page_title'] = 'EMI Collection Sheet';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Installments', 'url' => 'admin/installments'],
            ['title' => 'Collection Sheet', 'url' => '']
        ];
        
        $date = $this->input->get('date') ?: date('Y-m-d');
        
        $this->db->select('li.*, l.loan_number, l.member_id, m.member_code, m.first_name, m.last_name, m.phone');
        $this->db->from('loan_installments li');
        $this->db->join('loans l', 'l.id = li.loan_id');
        $this->db->join('members m', 'm.id = l.member_id');
        $this->db->where('li.due_date', $date);
        $this->db->where_in('li.status', ['pending', 'partial']);
        $this->db->where_in('l.status', ['active', 'overdue']);
        $this->db->order_by('m.member_code', 'ASC');
        
        $data['installments'] = $this->db->get()->result();
        $data['date'] = $date;
        $data['total_expected'] = array_sum(array_column($data['installments'], 'emi_amount'));
        
        $this->load_view('admin/installments/collection_sheet', $data);
    }
    
    /**
     * Send Reminder (AJAX)
     */
    public function send_reminder() {
        $installment_id = $this->input->post('installment_id');
        
        if (!$installment_id) {
            $this->json_response(['success' => false, 'message' => 'Please select a valid installment.']);
            return;
        }
        
        $installment = $this->db->select('li.*, l.loan_number, l.member_id, m.phone, m.first_name, m.last_name, m.email')
                                ->from('loan_installments li')
                                ->join('loans l', 'l.id = li.loan_id')
                                ->join('members m', 'm.id = l.member_id')
                                ->where('li.id', $installment_id)
                                ->get()
                                ->row();
        
        if (!$installment) {
            $this->json_response(['success' => false, 'message' => 'Installment not found.']);
            return;
        }
        
        $channels = [];
        
        // Send email if available
        if (!empty($installment->email)) {
            try {
                $this->load->library('email');
                $from_email = $this->get_setting('smtp_user', 'noreply@windeepfinance.com');
                $org_name = $this->get_setting('organization_name', 'Windeep Finance');
                $cs = $this->get_setting('currency_symbol', '₹');
                
                $this->email->from($from_email, $org_name);
                $this->email->to($installment->email);
                $this->email->subject('Installment Due Reminder - ' . $installment->loan_number);
                
                $message = "Dear " . $installment->first_name . ",\n\n";
                $message .= "This is a reminder for your upcoming/overdue installment.\n\n";
                $message .= "Loan: " . $installment->loan_number . "\n";
                $message .= "Due Date: " . format_date($installment->due_date) . "\n";
                $message .= "EMI Amount: " . $cs . number_format($installment->emi_amount, 2) . "\n\n";
                $message .= "Please make your payment at the earliest to avoid penalties.\n\n";
                $message .= "Thank you,\n" . $org_name;
                
                $this->email->message($message);
                if ($this->email->send()) {
                    $channels[] = 'email';
                }
            } catch (Exception $e) {
                log_message('error', 'Installment reminder email failed: ' . $e->getMessage());
            }
        }
        
        // In-app notification
        $this->load->model('Notification_model');
        $this->Notification_model->create([
            'target_type' => 'member',
            'target_id' => $installment->member_id,
            'title' => 'Installment Due Reminder',
            'message' => 'Reminder: EMI of ' . $this->get_setting('currency_symbol', '₹') . number_format($installment->emi_amount, 2) . ' for loan ' . $installment->loan_number . ' is due on ' . format_date($installment->due_date) . '.',
            'type' => 'reminder',
            'reference_type' => 'loan_installment',
            'reference_id' => $installment_id,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        $channels[] = 'notification';
        
        $this->log_audit('reminder_sent', 'loan_installments', 'loan_installments', $installment_id, null, [
            'loan_number' => $installment->loan_number,
            'phone' => $installment->phone,
            'channels' => implode(', ', $channels)
        ]);
        
        $this->json_response(['success' => true, 'message' => 'Reminder sent via: ' . implode(', ', $channels)]);
    }
    
    /**
     * Mark as Paid (Quick)
     */
    public function quick_payment() {
        $installment_id = $this->input->post('installment_id');
        $amount = $this->input->post('amount');
        $payment_mode = $this->input->post('payment_mode') ?: 'cash';
        
        if (!$installment_id || !$amount) {
            $this->json_response(['success' => false, 'message' => 'Invalid data']);
            return;
        }
        
        try {
            $payment_data = [
                'loan_id' => $this->input->post('loan_id'),
                'installment_id' => $installment_id,
                'payment_type' => 'emi',
                'total_amount' => $amount,
                'payment_mode' => $payment_mode,
                'payment_date' => date('Y-m-d'),
                'created_by' => $this->session->userdata('admin_id')
            ];
            
            $payment_id = $this->Loan_model->record_payment($payment_data);
            
            $this->json_response(['success' => true, 'message' => 'Payment recorded successfully', 'payment_id' => $payment_id]);
        } catch (Exception $e) {
            $this->json_response(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Reschedule Installments to Fixed Due Day (Admin Tool — POST/AJAX)
     *
     * When admin sets/changes the global "Fixed Due Day" setting,
     * existing installments that were generated with a different day-of-month
     * are NOT automatically updated. This method retroactively corrects
     * all UNPAID loan installments (status: upcoming / pending / overdue)
     * to use the configured fixed_due_day.
     *
     * Rules:
     *  - Only unpaid installments are touched (paid / partial are left as-is)
     *  - The MONTH and YEAR stay the same; only the DAY changes
     *  - Capped at the last day of the month (e.g., Feb never exceeds 28/29)
     *  - After date changes, status is re-evaluated:
     *      new due_date = today  → pending
     *      new due_date < today  → overdue
     *      new due_date > today  → upcoming
     *  - optional ?loan_id=X  → restrict to a single loan
     */
    public function reschedule_to_fixed_day() {
        $this->load->model('Setting_model');
        $fixed_day = (int) $this->Setting_model->get_setting('fixed_due_day', 0);

        if ($fixed_day < 1 || $fixed_day > 28) {
            $this->json_response([
                'success' => false,
                'message' => 'Fixed Due Day is not configured (must be 1–28). Please set it in Settings first.'
            ]);
            return;
        }

        $loan_id   = (int) $this->input->post('loan_id'); // 0 = all loans
        $today     = date('Y-m-d');
        $updated   = 0;
        $skipped   = 0;

        // Fetch unpaid installments that need their day adjusted
        $this->db->select('li.id, li.due_date, li.status')
                 ->from('loan_installments li')
                 ->join('loans l', 'l.id = li.loan_id')
                 ->where_in('li.status', ['upcoming', 'pending', 'overdue'])
                 ->where_in('l.status', ['active', 'overdue', 'npa']);

        if ($loan_id > 0) {
            $this->db->where('li.loan_id', $loan_id);
        }

        $installments = $this->db->get()->result();

        foreach ($installments as $inst) {
            list($year, $month, $current_day) = explode('-', $inst->due_date);
            $current_day = (int) $current_day;

            // Skip if already on the correct day
            if ($current_day === $fixed_day) {
                $skipped++;
                continue;
            }

            // Cap day to last valid day of that month
            $last_day_of_month = (int) date('t', mktime(0, 0, 0, (int)$month, 1, (int)$year));
            $new_day = min($fixed_day, $last_day_of_month);
            $new_due_date = sprintf('%s-%s-%02d', $year, $month, $new_day);

            // Re-derive status from the new due date
            if ($new_due_date < $today) {
                $new_status = 'overdue';
            } elseif ($new_due_date === $today) {
                $new_status = 'pending';
            } else {
                $new_status = 'upcoming';
            }

            $this->db->where('id', $inst->id)
                     ->update('loan_installments', [
                         'due_date'   => $new_due_date,
                         'status'     => $new_status,
                         'updated_at' => date('Y-m-d H:i:s')
                     ]);
            $updated++;
        }

        // Also update loans.last_emi_date for affected loans
        if ($updated > 0) {
            $loan_filter = $loan_id > 0 ? "AND l.id = {$loan_id}" : '';
            $this->db->query("
                UPDATE loans l
                SET last_emi_date = (
                    SELECT MAX(li.due_date) FROM loan_installments li WHERE li.loan_id = l.id
                )
                WHERE l.status IN ('active','overdue','npa') {$loan_filter}
            ");
        }

        log_message('info', "reschedule_to_fixed_day: fixed_day={$fixed_day}, updated={$updated}, skipped={$skipped}");

        $this->json_response([
            'success' => true,
            'message' => "Rescheduled {$updated} installments to day {$fixed_day} of each month. {$skipped} were already correct.",
            'details' => [
                'fixed_day' => $fixed_day,
                'updated'   => $updated,
                'skipped'   => $skipped,
            ]
        ]);
    }

    /**
     * Sync Installment Statuses (Admin Tool — POST/AJAX)
     *
     * Fixes installments that are stuck in 'upcoming' status because the daily
     * cron has not yet run or was never configured.  Safe to call repeatedly.
     *
     * Transitions applied:
     *   upcoming  → pending  (due_date = today)
     *   upcoming  → overdue  (due_date < today, unpaid)
     *   pending   → overdue  (due_date < today, still pending)
     */
    public function sync_statuses() {
        $today = date('Y-m-d');

        // 0. Correctness guard: future-dated 'pending' → 'upcoming' (data repair)
        $this->db->where('status', 'pending')
                 ->where('due_date >', $today)
                 ->update('loan_installments', ['status' => 'upcoming', 'updated_at' => date('Y-m-d H:i:s')]);
        $future_pending_fixed = $this->db->affected_rows();

        // 1. upcoming → pending (due today)
        $this->db->where('status', 'upcoming')
                 ->where('due_date', $today)
                 ->update('loan_installments', ['status' => 'pending', 'updated_at' => date('Y-m-d H:i:s')]);
        $activated_today = $this->db->affected_rows();

        // 2. upcoming → overdue (past due, never activated)
        $this->db->where('status', 'upcoming')
                 ->where('due_date <', $today)
                 ->update('loan_installments', ['status' => 'overdue', 'updated_at' => date('Y-m-d H:i:s')]);
        $past_upcoming = $this->db->affected_rows();

        // 3. pending → overdue (due date already passed)
        $this->db->where('status', 'pending')
                 ->where('due_date <', $today)
                 ->update('loan_installments', ['status' => 'overdue', 'updated_at' => date('Y-m-d H:i:s')]);
        $past_pending = $this->db->affected_rows();

        $total = $activated_today + $past_upcoming + $past_pending + $future_pending_fixed;

        log_message('info', "sync_statuses: activated_today={$activated_today}, past_upcoming={$past_upcoming}, past_pending={$past_pending}");

        $this->json_response([
            'success' => true,
            'message' => "Sync complete. {$total} installments updated.",
            'details' => [
                'future_pending_corrected'   => $future_pending_fixed,
                'activated_today'            => $activated_today,
                'past_upcoming_to_overdue'   => $past_upcoming,
                'past_pending_to_overdue'    => $past_pending,
            ]
        ]);
    }
}
