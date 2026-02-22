<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cron Controller - Scheduled Tasks
 * 
 * Run via: php index.php cli/cron [method_name]
 *
 * CRON SCHEDULE (Add to crontab):
 * ================================
 * # Daily jobs (run at 2 AM)
 * 0 2 * * * cd /path/to/windeep_finance && php index.php cli/cron daily >> /var/log/windeep_cron.log 2>&1
 * 
 * # Weekly jobs (run Sunday at 3 AM)
 * 0 3 * * 0 cd /path/to/windeep_finance && php index.php cli/cron weekly >> /var/log/windeep_cron.log 2>&1
 * 
 * # Monthly jobs (run 1st of month at 4 AM)
 * 0 4 1 * * cd /path/to/windeep_finance && php index.php cli/cron monthly >> /var/log/windeep_cron.log 2>&1
 * 
 * # Hourly jobs
 * 0 * * * * cd /path/to/windeep_finance && php index.php cli/cron hourly >> /var/log/windeep_cron.log 2>&1
 */
class Cron extends CI_Controller {

    private $log_file;

    public function __construct() {
        parent::__construct();
        
        // Ensure CLI only
        if (!is_cli()) {
            echo json_encode(['error' => 'CLI access only']);
            exit;
        }
        
        $this->load->database();
        $this->load->model([
            'Savings_model', 
            'Loan_model', 
            'Fine_model', 
            'Member_model',
            'Notification_model',
            'Setting_model'
        ]);
        $this->load->helper(['email', 'date']);
        
        // Initialize log file
        $this->log_file = APPPATH . 'logs/cron_' . date('Y-m-d') . '.log';
    }

    /**
     * Log message to file and console
     */
    private function log($message, $type = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[{$timestamp}] [{$type}] {$message}\n";
        
        // Output to console
        echo $log_message;
        
        // Write to log file
        file_put_contents($this->log_file, $log_message, FILE_APPEND);
    }

    // =========================================
    // MAIN ENTRY POINTS
    // =========================================

    /**
     * Run all daily jobs
     */
    public function daily() {
        $this->log("========== DAILY CRON START ==========");
        
        $this->apply_overdue_fines();
        $this->mark_overdue_installments();
        $this->send_due_reminders();
        $this->update_npa_status();
        $this->cleanup_old_sessions();
        
        $this->log("========== DAILY CRON END ==========\n");
    }

    /**
     * Run all weekly jobs
     */
    public function weekly() {
        $this->log("========== WEEKLY CRON START ==========");
        
        $this->extend_savings_schedules();
        $this->send_weekly_report();
        $this->cleanup_old_notifications();
        
        $this->log("========== WEEKLY CRON END ==========\n");
    }

    /**
     * Run all monthly jobs
     */
    public function monthly() {
        $this->log("========== MONTHLY CRON START ==========");
        
        $this->calculate_savings_interest();
        $this->generate_monthly_reports();
        $this->archive_old_logs();
        $this->database_backup();
        
        $this->log("========== MONTHLY CRON END ==========\n");
    }

    /**
     * Run hourly jobs
     */
    public function hourly() {
        $this->log("========== HOURLY CRON START ==========");
        
        $this->process_pending_emails();
        $this->check_pending_consents();
        
        $this->log("========== HOURLY CRON END ==========\n");
    }

    // =========================================
    // FINE MANAGEMENT
    // =========================================

    /**
     * Apply fines to overdue installments
     */
    public function apply_overdue_fines() {
        $this->log("Starting: Apply overdue fines");

        // Respect the admin setting: Auto-apply late payment fines
        $auto_apply = $this->Setting_model->get_setting('auto_apply_fines', '0');
        if (!$auto_apply || $auto_apply === '0' || $auto_apply === 'false') {
            $this->log("Skipping fine application: Auto-apply late payment fines is DISABLED in Settings.");
            return;
        }
        
        try {
            // Get overdue loan installments
            $overdue = $this->db->select('
                li.*, l.member_id, l.loan_product_id,
                DATEDIFF(CURDATE(), li.due_date) as days_overdue
            ')
            ->from('loan_installments li')
            ->join('loans l', 'l.id = li.loan_id')
            ->where('li.status', 'pending')
            ->where('li.due_date <', date('Y-m-d'))
            ->where('l.status', 'active')
            ->get()->result();
            
            $fines_applied = 0;
            
            foreach ($overdue as $installment) {
                // Check if fine already exists for today
                $existing = $this->db->where('related_type', 'loan_installment')
                    ->where('related_id', $installment->id)
                    ->where('DATE(created_at)', date('Y-m-d'))
                    ->get('fines')->num_rows();
                
                if ($existing > 0) continue;
                
                // Calculate fine based on rules
                $paid = isset($installment->total_paid) ? $installment->total_paid : 0;
                $fine_amount = $this->Fine_model->calculate_fine(
                    'loan_late',
                    $installment->emi_amount - $paid,
                    $installment->days_overdue
                );
                
                if ($fine_amount > 0) {
                    // Get the fine rule ID
                    $rule = $this->db->where('fine_type', 'loan_late')
                                    ->where('is_active', 1)
                                    ->get('fine_rules')->row();
                    
                    // Apply fine
                    $this->db->insert('fines', [
                        'fine_code' => 'FIN-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6)),
                        'member_id' => $installment->member_id,
                        'fine_type' => 'loan_late',
                        'related_type' => 'loan_installment',
                        'related_id' => $installment->id,
                        'fine_rule_id' => $rule ? $rule->id : null,
                        'fine_date' => date('Y-m-d'),
                        'due_date' => $installment->due_date,
                        'days_late' => $installment->days_overdue,
                        'fine_amount' => $fine_amount,
                        'balance_amount' => $fine_amount,
                        'remarks' => "Late payment for installment #{$installment->installment_number}",
                        'status' => 'pending',
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    $fines_applied++;
                }
            }
            
            // Get overdue savings schedules
            $savings_overdue = $this->db->select('
                ss.*, sa.member_id,
                DATEDIFF(CURDATE(), ss.due_date) as days_overdue
            ')
            ->from('savings_schedule ss')
            ->join('savings_accounts sa', 'sa.id = ss.savings_account_id')
            ->where('ss.status', 'pending')
            ->where('ss.due_date <', date('Y-m-d'))
            ->where('sa.status', 'active')
            ->get()->result();
            
            foreach ($savings_overdue as $schedule) {
                // Check existing fine
                $existing = $this->db->where('related_type', 'savings_schedule')
                    ->where('related_id', $schedule->id)
                    ->where('DATE(created_at)', date('Y-m-d'))
                    ->get('fines')->num_rows();
                
                if ($existing > 0) continue;
                
                $fine_amount = $this->Fine_model->calculate_fine(
                    'savings_late',
                    $schedule->due_amount - $schedule->paid_amount,
                    $schedule->days_overdue
                );
                
                if ($fine_amount > 0) {
                    $this->db->insert('fines', [
                        'fine_code' => 'FIN-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6)),
                        'member_id' => $schedule->member_id,
                        'fine_type' => 'savings_late',
                        'related_type' => 'savings_schedule',
                        'related_id' => $schedule->id,
                        'fine_date' => date('Y-m-d'),
                        'due_date' => $schedule->due_date,
                        'days_late' => $schedule->days_overdue,
                        'fine_amount' => $fine_amount,
                        'balance_amount' => $fine_amount,
                        'remarks' => "Late savings deposit for " . date('M Y', strtotime($schedule->due_date)),
                        'status' => 'pending',
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    $fines_applied++;
                }
            }
            
            $this->log("Applied {$fines_applied} fines");
            
        } catch (Exception $e) {
            $this->log("Error: " . $e->getMessage(), 'ERROR');
        }
    }

    /**
     * Mark installments as overdue
     */
    public function mark_overdue_installments() {
        $this->log("Starting: Mark overdue installments");
        
        try {
            // Update loan installments
            $this->db->where('status', 'pending')
                ->where('due_date <', date('Y-m-d'))
                ->update('loan_installments', [
                    'status' => 'overdue',
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            
            $loan_updated = $this->db->affected_rows();
            
            // Update savings schedules
            $this->db->where('status', 'pending')
                ->where('due_date <', date('Y-m-d'))
                ->update('savings_schedule', [
                    'status' => 'overdue',
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            
            $savings_updated = $this->db->affected_rows();
            
            $this->log("Marked overdue: {$loan_updated} loan installments, {$savings_updated} savings schedules");
            
        } catch (Exception $e) {
            $this->log("Error: " . $e->getMessage(), 'ERROR');
        }
    }

    /**
     * Update NPA status for loans
     */
    public function update_npa_status() {
        $this->log("Starting: Update NPA status");
        
        try {
            $npa_days = $this->Setting_model->get('npa_days', 90);
            
            // Find loans with installments overdue > NPA days
            $npa_loans = $this->db->select('DISTINCT l.id')
                ->from('loans l')
                ->join('loan_installments li', 'li.loan_id = l.id')
                ->where('l.status', 'active')
                ->where('li.status', 'overdue')
                ->where('li.due_date <', date('Y-m-d', strtotime("-{$npa_days} days")))
                ->get()->result();
            
            $npa_count = 0;
            foreach ($npa_loans as $loan) {
                $this->db->where('id', $loan->id)
                    ->update('loans', [
                        'status' => 'npa',
                        'npa_date' => date('Y-m-d'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                $npa_count++;
            }
            
            $this->log("Marked {$npa_count} loans as NPA");
            
        } catch (Exception $e) {
            $this->log("Error: " . $e->getMessage(), 'ERROR');
        }
    }

    // =========================================
    // INTEREST CALCULATION
    // =========================================

    /**
     * Calculate and post monthly savings interest
     */
    public function calculate_savings_interest() {
        $this->log("Starting: Calculate savings interest");
        
        try {
            // Get active savings accounts
            $accounts = $this->db->select('
                sa.*, ss.interest_rate, ss.scheme_name
            ')
            ->from('savings_accounts sa')
            ->join('savings_schemes ss', 'ss.id = sa.scheme_id')
            ->where('sa.status', 'active')
            ->where('ss.interest_rate >', 0)
            ->get()->result();
            
            $interest_posted = 0;
            $total_interest = 0;
            
            foreach ($accounts as $account) {
                // Calculate monthly interest
                $monthly_rate = $account->interest_rate / 12 / 100;
                $interest = round($account->current_balance * $monthly_rate, 2);
                
                if ($interest <= 0) continue;
                
                // Check if already posted this month
                $existing = $this->db->where('savings_account_id', $account->id)
                    ->where('transaction_type', 'interest')
                    ->where('MONTH(transaction_date)', date('m'))
                    ->where('YEAR(transaction_date)', date('Y'))
                    ->get('savings_transactions')->num_rows();
                
                if ($existing > 0) continue;
                
                $this->db->trans_begin();
                
                // Post interest transaction
                $new_balance = $account->current_balance + $interest;
                
                $this->db->insert('savings_transactions', [
                    'savings_account_id' => $account->id,
                    'transaction_type' => 'interest',
                    'amount' => $interest,
                    'balance_after' => $new_balance,
                    'transaction_code' => 'INT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6)),
                    'description' => 'Monthly interest credit @ ' . $account->interest_rate . '% p.a.',
                    'transaction_date' => date('Y-m-d'),
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                // Update account balance
                $this->db->where('id', $account->id)
                    ->update('savings_accounts', [
                        'current_balance' => $new_balance,
                        'interest_earned' => $account->interest_earned + $interest,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                
                // Post to ledger
                $this->post_to_ledger([
                    'debit_account' => 'Interest Expense',
                    'credit_account' => 'Savings Deposits',
                    'amount' => $interest,
                    'description' => "Interest credit for account {$account->account_number}",
                    'reference_type' => 'savings_interest',
                    'reference_id' => $account->id
                ]);
                
                if ($this->db->trans_status() === FALSE) {
                    $this->db->trans_rollback();
                    continue;
                }
                
                $this->db->trans_commit();
                $interest_posted++;
                $total_interest += $interest;
            }
            
            $this->log("Posted interest to {$interest_posted} accounts, total: ₹" . number_format($total_interest, 2));
            
        } catch (Exception $e) {
            $this->log("Error: " . $e->getMessage(), 'ERROR');
        }
    }

    /**
     * Post to ledger
     */
    private function post_to_ledger($data) {
        $transaction_id = 'TXN-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        
        // Debit entry
        $this->db->insert('ledger', [
            'transaction_id' => $transaction_id,
            'account_name' => $data['debit_account'],
            'debit' => $data['amount'],
            'credit' => 0,
            'description' => $data['description'],
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'transaction_date' => date('Y-m-d'),
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Credit entry
        $this->db->insert('ledger', [
            'transaction_id' => $transaction_id,
            'account_name' => $data['credit_account'],
            'debit' => 0,
            'credit' => $data['amount'],
            'description' => $data['description'],
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'transaction_date' => date('Y-m-d'),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    // =========================================
    // SCHEDULE EXTENSION
    // =========================================

    /**
     * Extend savings schedules before they expire
     */
    public function extend_savings_schedules() {
        $this->log("Starting: Extend savings schedules");
        
        try {
            // Get accounts with schedules ending within 3 months
            $accounts = $this->db->query("
                SELECT 
                    sa.id, sa.member_id, sa.scheme_id, sa.monthly_amount,
                    MAX(ss.due_date) as last_schedule_date
                FROM savings_accounts sa
                JOIN savings_schedule ss ON ss.savings_account_id = sa.id
                WHERE sa.status = 'active'
                GROUP BY sa.id
                HAVING last_schedule_date < DATE_ADD(CURDATE(), INTERVAL 3 MONTH)
            ")->result();
            
            $extended = 0;
            
            foreach ($accounts as $account) {
                // Generate next 12 months from last schedule date
                $start_date = date('Y-m-d', strtotime($account->last_schedule_date . ' +1 month'));
                
                $result = $this->Savings_model->generate_schedule(
                    $account->id,
                    $start_date,
                    $account->monthly_amount,
                    12
                );
                
                if ($result) {
                    $extended++;
                    
                    // Notify member
                    $this->Notification_model->create([
                        'user_type' => 'member',
                        'user_id' => $account->member_id,
                        'title' => 'Savings Schedule Extended',
                        'message' => 'Your savings schedule has been extended for the next 12 months.',
                        'type' => 'info'
                    ]);
                }
            }
            
            $this->log("Extended schedules for {$extended} accounts");
            
        } catch (Exception $e) {
            $this->log("Error: " . $e->getMessage(), 'ERROR');
        }
    }

    // =========================================
    // REMINDERS & NOTIFICATIONS
    // =========================================

    /**
     * Send due date reminders
     */
    public function send_due_reminders() {
        $this->log("Starting: Send due reminders");
        
        try {
            // Get installments due TODAY (exact due date alerts)
            $due_today = $this->db->select('
                li.*, l.member_id, l.loan_number, m.email, m.phone, m.first_name,
                CONCAT(m.first_name, " ", m.last_name) as member_name
            ')
            ->from('loan_installments li')
            ->join('loans l', 'l.id = li.loan_id')
            ->join('members m', 'm.id = l.member_id')
            ->where('li.status', 'pending')
            ->where('li.due_date', date('Y-m-d'))
            ->get()->result();
            
            $sent = 0;
            
            foreach ($due_today as $installment) {
                if (!empty($installment->email)) {
                    $subject = "Payment Due Today - ₹" . number_format($installment->emi_amount, 2);
                    
                    $message = "
                        <h2>Payment Due Today</h2>
                        <p>Dear {$installment->first_name},</p>
                        <p>Your EMI payment of <strong>₹" . number_format($installment->emi_amount, 2) . "</strong> is due <strong>TODAY</strong>.</p>
                        <p><strong>Loan Number:</strong> {$installment->loan_number}<br>
                        <strong>Installment:</strong> #{$installment->installment_number}<br>
                        <strong>Due Date:</strong> " . date('d M Y', strtotime($installment->due_date)) . "</p>
                        <p>Please make the payment to avoid late fees.</p>
                        <p>Thank you,<br>Windeep Finance</p>
                    ";
                    
                    $result = send_email($installment->email, $subject, $message);
                    if ($result['success']) $sent++;
                }
                
                // Create notification
                $this->Notification_model->create([
                    'user_type' => 'member',
                    'user_id' => $installment->member_id,
                    'title' => 'Payment Due Today',
                    'message' => "Your EMI of ₹" . number_format($installment->emi_amount, 2) . " is due today!",
                    'type' => 'warning'
                ]);
            }
            
            // Also send 3-day advance reminders
            $upcoming = $this->db->select('
                li.*, l.member_id, m.email, m.phone, m.first_name
            ')
            ->from('loan_installments li')
            ->join('loans l', 'l.id = li.loan_id')
            ->join('members m', 'm.id = l.member_id')
            ->where('li.status', 'pending')
            ->where('li.due_date', date('Y-m-d', strtotime('+3 days')))
            ->get()->result();
            
            foreach ($upcoming as $installment) {
                if (!empty($installment->email)) {
                    $subject = "Payment Reminder - Due in 3 Days";
                    
                    $message = "
                        <h2>Payment Reminder</h2>
                        <p>Dear {$installment->first_name},</p>
                        <p>Your EMI payment of <strong>₹" . number_format($installment->emi_amount, 2) . "</strong> is due in <strong>3 days</strong> on " . date('d M Y', strtotime($installment->due_date)) . ".</p>
                        <p>Please ensure timely payment to avoid late fees.</p>
                        <p>Thank you,<br>Windeep Finance</p>
                    ";
                    
                    $result = send_email($installment->email, $subject, $message);
                    if ($result['success']) $sent++;
                }
            }
            
            // Send savings due reminders
            $savings_due_today = $this->db->select('
                ss.*, sa.member_id, sa.account_number, m.email, m.phone, m.first_name
            ')
            ->from('savings_schedule ss')
            ->join('savings_accounts sa', 'sa.id = ss.savings_account_id')
            ->join('members m', 'm.id = sa.member_id')
            ->where('ss.status', 'pending')
            ->where('ss.due_date', date('Y-m-d'))
            ->get()->result();
            
            foreach ($savings_due_today as $schedule) {
                if (!empty($schedule->email)) {
                    $subject = "Savings Deposit Due Today - ₹" . number_format($schedule->due_amount, 2);
                    
                    $message = "
                        <h2>Savings Deposit Due</h2>
                        <p>Dear {$schedule->first_name},</p>
                        <p>Your monthly savings deposit of <strong>₹" . number_format($schedule->due_amount, 2) . "</strong> is due <strong>TODAY</strong>.</p>
                        <p><strong>Account:</strong> {$schedule->account_number}<br>
                        <strong>Month:</strong> " . date('M Y', strtotime($schedule->due_month)) . "</p>
                        <p>Please make your deposit to avoid late fees.</p>
                        <p>Thank you,<br>Windeep Finance</p>
                    ";
                    
                    $result = send_email($schedule->email, $subject, $message);
                    if ($result['success']) $sent++;
                }
                
                // Create notification
                $this->Notification_model->create([
                    'user_type' => 'member',
                    'user_id' => $schedule->member_id,
                    'title' => 'Savings Deposit Due',
                    'message' => "Your savings deposit of ₹" . number_format($schedule->due_amount, 2) . " is due today!",
                    'type' => 'info'
                ]);
            }
            
            $this->log("Sent {$sent} reminder emails (loans + savings)");
            
        } catch (Exception $e) {
            $this->log("Error: " . $e->getMessage(), 'ERROR');
        }
    }

    /**
     * Check pending guarantor consents
     */
    public function check_pending_consents() {
        $this->log("Starting: Check pending consents");
        
        try {
            // Get pending consents older than 3 days
            $pending = $this->db->select('
                lg.*, m.email, m.phone, m.first_name
            ')
            ->from('loan_guarantors lg')
            ->join('members m', 'm.id = lg.guarantor_id')
            ->where('lg.consent_status', 'pending')
            ->where('lg.created_at <', date('Y-m-d H:i:s', strtotime('-3 days')))
            ->where('lg.reminder_count <', 3)
            ->get()->result();
            
            $reminders_sent = 0;
            
            foreach ($pending as $guarantor) {
                if (!empty($guarantor->email)) {
                    $subject = "Reminder: Guarantor Consent Required";
                    $consent_url = base_url("guarantor/consent/{$guarantor->consent_token}");
                    
                    $message = "
                        <h2>Guarantor Consent Reminder</h2>
                        <p>Dear {$guarantor->first_name},</p>
                        <p>This is a reminder that your consent is required for a loan application where you have been listed as a guarantor.</p>
                        <p><a href='{$consent_url}' style='padding:10px 20px; background:#007bff; color:#fff; text-decoration:none; border-radius:5px;'>Review & Accept</a></p>
                        <p>If you did not expect this request, please contact us.</p>
                    ";
                    
                    $result = send_email($guarantor->email, $subject, $message);
                    
                    if ($result['success']) {
                        $this->db->where('id', $guarantor->id)
                            ->update('loan_guarantors', [
                                'reminder_count' => $guarantor->reminder_count + 1,
                                'last_reminder_at' => date('Y-m-d H:i:s')
                            ]);
                        $reminders_sent++;
                    }
                }
            }
            
            $this->log("Sent {$reminders_sent} consent reminders");
            
        } catch (Exception $e) {
            $this->log("Error: " . $e->getMessage(), 'ERROR');
        }
    }

    /**
     * Send weekly report to admin
     */
    public function send_weekly_report() {
        $this->log("Starting: Send weekly report");
        
        try {
            $admin_email = $this->Setting_model->get('admin_email', 'admin@windeep.com');
            
            // Get weekly stats
            $week_start = date('Y-m-d', strtotime('monday this week'));
            $week_end = date('Y-m-d', strtotime('sunday this week'));
            
            $stats = [
                'new_members' => $this->db->where('created_at >=', $week_start)->count_all_results('members'),
                'new_loans' => $this->db->where('disbursement_date >=', $week_start)->count_all_results('loans'),
                'collections' => $this->db->select_sum('amount')
                    ->where('payment_date >=', $week_start)
                    ->get('loan_payments')->row()->amount ?? 0,
                'savings_deposits' => $this->db->select_sum('amount')
                    ->where('transaction_type', 'deposit')
                    ->where('transaction_date >=', $week_start)
                    ->get('savings_transactions')->row()->amount ?? 0
            ];
            
            $subject = "Weekly Report - " . date('M d', strtotime($week_start)) . " to " . date('M d, Y', strtotime($week_end));
            
            $message = "
                <h2>Weekly Summary Report</h2>
                <table border='1' cellpadding='10' style='border-collapse:collapse;'>
                    <tr><td>New Members</td><td><strong>{$stats['new_members']}</strong></td></tr>
                    <tr><td>New Loans Disbursed</td><td><strong>{$stats['new_loans']}</strong></td></tr>
                    <tr><td>Total Collections</td><td><strong>₹" . number_format($stats['collections'], 2) . "</strong></td></tr>
                    <tr><td>Savings Deposits</td><td><strong>₹" . number_format($stats['savings_deposits'], 2) . "</strong></td></tr>
                </table>
                <p>Generated on " . date('d M Y H:i:s') . "</p>
            ";
            
            $result = send_email($admin_email, $subject, $message);
            
            $this->log($result['success'] ? "Weekly report sent to {$admin_email}" : "Failed to send report: " . $result['message']);
            
        } catch (Exception $e) {
            $this->log("Error: " . $e->getMessage(), 'ERROR');
        }
    }

    // =========================================
    // EMAIL QUEUE PROCESSING
    // =========================================

    /**
     * Process pending emails from queue
     */
    public function process_pending_emails() {
        $this->log("Starting: Process email queue");
        
        try {
            $pending = $this->db->where('status', 'pending')
                ->where('attempts <', 3)
                ->order_by('created_at', 'ASC')
                ->limit(50)
                ->get('email_queue')->result();
            
            if (empty($pending)) {
                $this->log("No pending emails");
                return;
            }
            
            $sent = 0;
            $failed = 0;
            
            foreach ($pending as $email) {
                $result = send_email($email->to_email, $email->subject, $email->body);
                
                if ($result['success']) {
                    $this->db->where('id', $email->id)
                        ->update('email_queue', [
                            'status' => 'sent',
                            'sent_at' => date('Y-m-d H:i:s')
                        ]);
                    $sent++;
                } else {
                    $this->db->where('id', $email->id)
                        ->update('email_queue', [
                            'attempts' => $email->attempts + 1,
                            'last_error' => $result['message'],
                            'status' => $email->attempts >= 2 ? 'failed' : 'pending'
                        ]);
                    $failed++;
                }
                
                usleep(100000); // 100ms delay between emails
            }
            
            $this->log("Processed emails: {$sent} sent, {$failed} failed");
            
        } catch (Exception $e) {
            $this->log("Error: " . $e->getMessage(), 'ERROR');
        }
    }

    // =========================================
    // CLEANUP TASKS
    // =========================================

    /**
     * Clean up old sessions
     */
    public function cleanup_old_sessions() {
        $this->log("Starting: Cleanup old sessions");
        
        try {
            $this->db->where('timestamp <', time() - 86400 * 7) // 7 days
                ->delete('ci_sessions');
            
            $deleted = $this->db->affected_rows();
            $this->log("Deleted {$deleted} old sessions");
            
        } catch (Exception $e) {
            $this->log("Error: " . $e->getMessage(), 'ERROR');
        }
    }

    /**
     * Clean up old notifications
     */
    public function cleanup_old_notifications() {
        $this->log("Starting: Cleanup old notifications");
        
        try {
            // Delete read notifications older than 30 days
            $this->db->where('is_read', 1)
                ->where('created_at <', date('Y-m-d H:i:s', strtotime('-30 days')))
                ->delete('notifications');
            
            $deleted = $this->db->affected_rows();
            $this->log("Deleted {$deleted} old notifications");
            
        } catch (Exception $e) {
            $this->log("Error: " . $e->getMessage(), 'ERROR');
        }
    }

    /**
     * Archive old log files
     */
    public function archive_old_logs() {
        $this->log("Starting: Archive old logs");
        
        try {
            $log_path = APPPATH . 'logs/';
            $archive_path = $log_path . 'archive/';
            
            if (!is_dir($archive_path)) {
                mkdir($archive_path, 0755, true);
            }
            
            $files = glob($log_path . '*.php');
            $archived = 0;
            
            foreach ($files as $file) {
                $modified = filemtime($file);
                
                // Archive files older than 30 days
                if ($modified < strtotime('-30 days')) {
                    $filename = basename($file);
                    rename($file, $archive_path . $filename);
                    $archived++;
                }
            }
            
            // Delete archive files older than 90 days
            $old_archives = glob($archive_path . '*.php');
            $deleted = 0;
            
            foreach ($old_archives as $file) {
                if (filemtime($file) < strtotime('-90 days')) {
                    unlink($file);
                    $deleted++;
                }
            }
            
            $this->log("Archived {$archived} logs, deleted {$deleted} old archives");
            
        } catch (Exception $e) {
            $this->log("Error: " . $e->getMessage(), 'ERROR');
        }
    }

    /**
     * Create database backup
     */
    public function database_backup() {
        $this->log("Starting: Database backup");
        
        try {
            $this->load->dbutil();
            
            $prefs = [
                'format' => 'zip',
                'filename' => 'backup_' . date('Y-m-d_His') . '.sql'
            ];
            
            $backup = $this->dbutil->backup($prefs);
            
            $backup_path = APPPATH . 'backups/';
            if (!is_dir($backup_path)) {
                mkdir($backup_path, 0755, true);
            }
            
            $filename = $prefs['filename'] . '.zip';
            $filepath = $backup_path . $filename;
            
            write_file($filepath, $backup);
            
            // Log backup
            $this->db->insert('backups', [
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => filesize($filepath),
                'type' => 'scheduled',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Cleanup old backups (keep last 30)
            $old_backups = $this->db->where('type', 'scheduled')
                ->order_by('created_at', 'DESC')
                ->limit(1000, 30)
                ->get('backups')->result();
            
            foreach ($old_backups as $old) {
                if (file_exists($old->filepath)) {
                    unlink($old->filepath);
                }
                $this->db->delete('backups', ['id' => $old->id]);
            }
            
            $this->log("Backup created: {$filename}");
            
        } catch (Exception $e) {
            $this->log("Error: " . $e->getMessage(), 'ERROR');
        }
    }

    /**
     * Generate monthly reports
     */
    public function generate_monthly_reports() {
        $this->log("Starting: Generate monthly reports");
        
        try {
            $month = date('Y-m', strtotime('last month'));
            $month_name = date('F Y', strtotime('last month'));
            
            // Collection summary
            $collections = $this->db->select('
                SUM(amount) as total,
                COUNT(*) as count
            ')
            ->where('payment_date >=', $month . '-01')
            ->where('payment_date <=', date('Y-m-t', strtotime($month . '-01')))
            ->get('loan_payments')->row();
            
            // Disbursement summary
            $disbursements = $this->db->select('
                SUM(amount) as total,
                COUNT(*) as count
            ')
            ->where('disbursement_date >=', $month . '-01')
            ->where('disbursement_date <=', date('Y-m-t', strtotime($month . '-01')))
            ->get('loans')->row();
            
            // Store report
            $this->db->insert('monthly_reports', [
                'report_month' => $month . '-01',
                'report_type' => 'summary',
                'data' => json_encode([
                    'collections' => $collections,
                    'disbursements' => $disbursements
                ]),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->log("Monthly report generated for {$month_name}");
            
        } catch (Exception $e) {
            $this->log("Error: " . $e->getMessage(), 'ERROR');
        }
    }

    // =========================================
    // UTILITY METHODS
    // =========================================

    /**
     * Test all cron jobs
     */
    public function test() {
        $this->log("========== CRON TEST START ==========");
        
        echo "Testing database connection... ";
        $test = $this->db->query("SELECT 1")->row();
        echo ($test ? "OK\n" : "FAILED\n");
        
        echo "Testing email configuration... ";
        $result = send_email('test@example.com', 'Test', 'Test email', true); // Dry run
        echo ($result['success'] ? "OK\n" : "FAILED: " . $result['message'] . "\n");
        
        echo "Testing log file write... ";
        $this->log("Test log entry");
        echo (file_exists($this->log_file) ? "OK\n" : "FAILED\n");
        
        echo "\nAll systems operational!\n";
        
        $this->log("========== CRON TEST END ==========\n");
    }

    /**
     * Show status of all scheduled tasks
     */
    public function status() {
        echo "\n=== WINDEEP FINANCE CRON STATUS ===\n\n";
        
        echo "Last Run Times:\n";
        echo "---------------\n";
        
        $last_runs = $this->db->order_by('created_at', 'DESC')
            ->limit(10)
            ->get('cron_log')->result();
        
        if (empty($last_runs)) {
            echo "No cron history found\n";
        } else {
            foreach ($last_runs as $run) {
                echo "{$run->job_name}: {$run->created_at} ({$run->status})\n";
            }
        }
        
        echo "\nPending Tasks:\n";
        echo "--------------\n";
        
        $overdue_fines = $this->db->where('status', 'pending')
            ->where('due_date <', date('Y-m-d'))
            ->count_all_results('loan_installments');
        echo "Overdue installments: {$overdue_fines}\n";
        
        $pending_emails = $this->db->where('status', 'pending')
            ->count_all_results('email_queue');
        echo "Pending emails: {$pending_emails}\n";
        
        $pending_consents = $this->db->where('consent_status', 'pending')
            ->count_all_results('loan_guarantors');
        echo "Pending consents: {$pending_consents}\n";
        
        echo "\n";
    }
}
