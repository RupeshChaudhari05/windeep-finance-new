<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CLI Jobs Controller
 * Run via: php index.php cli jobs [method_name]
 *
 * Examples:
 * php index.php cli jobs send_due_reminders
 * php index.php cli jobs send_installment_reminders
 */
class Jobs extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Ensure CLI only
        if (!is_cli()) {
            echo "This controller is intended to be run from CLI only.\n";
            exit;
        }
        $this->load->database();
        $this->load->model(['Member_model', 'Loan_model', 'Report_model']);
        $this->load->helper('email');
    }

    /**
     * Send due date reminders to members
     * Run daily: php index.php cli jobs send_due_reminders
     */
    public function send_due_reminders() {
        echo "Starting due date reminder job...\n";

        // Get loans with payments due in next 3 days
        $upcoming_dues = $this->Loan_model->get_upcoming_due_payments(3);

        if (empty($upcoming_dues)) {
            echo "No upcoming due payments found.\n";
            return;
        }

        $sent_count = 0;
        $failed_count = 0;

        foreach ($upcoming_dues as $due) {
            // Get member details
            $member = $this->Member_model->get_member($due->member_id);

            if (!$member || empty($member->email)) {
                echo "Skipping member {$due->member_id} - no email address\n";
                continue;
            }

            // Send reminder email
            $result = send_due_date_reminder($member, $due, $due->due_amount, $due->due_date);

            if ($result['success']) {
                echo "✓ Sent reminder to {$member->email} for ₹{$due->due_amount}\n";
                $sent_count++;
            } else {
                echo "✗ Failed to send to {$member->email}: {$result['message']}\n";
                $failed_count++;
            }

            // Small delay to avoid overwhelming mail server
            sleep(1);
        }

        echo "\nJob completed: {$sent_count} sent, {$failed_count} failed\n";
    }

    /**
     * Send installment reminders
     * Run weekly: php index.php cli jobs send_installment_reminders
     */
    public function send_installment_reminders() {
        echo "Starting installment reminder job...\n";

        // Get upcoming installments for next week
        $upcoming_installments = $this->Loan_model->get_upcoming_installments(7);

        if (empty($upcoming_installments)) {
            echo "No upcoming installments found.\n";
            return;
        }

        $sent_count = 0;
        $failed_count = 0;

        foreach ($upcoming_installments as $installment) {
            // Get member details
            $member = $this->Member_model->get_member($installment->member_id);

            if (!$member || empty($member->email)) {
                echo "Skipping member {$installment->member_id} - no email address\n";
                continue;
            }

            // Send reminder email
            $result = send_installment_reminder($member, $installment);

            if ($result['success']) {
                echo "✓ Sent installment reminder to {$member->email} for ₹{$installment->amount}\n";
                $sent_count++;
            } else {
                echo "✗ Failed to send to {$member->email}: {$result['message']}\n";
                $failed_count++;
            }

            // Small delay
            sleep(1);
        }

        echo "\nJob completed: {$sent_count} sent, {$failed_count} failed\n";
    }

    /**
     * Send weekly report to admin
     * Run weekly: php index.php cli jobs send_weekly_report
     */
    public function send_weekly_report() {
        echo "Starting weekly report job...\n";

        // Get admin email from settings or use default
        $admin_email = $this->db->get_where('settings', ['key' => 'admin_email'])->row();
        $admin_email = $admin_email ? $admin_email->value : 'admin@windeep.com';

        // Get weekly summary data
        $weekly_data = $this->Report_model->get_weekly_summary();

        if (empty($weekly_data)) {
            echo "No weekly data available.\n";
            return;
        }

        $subject = 'Weekly Summary Report - ' . date('M j, Y', strtotime('last week'));

        $result = send_report_email('weekly-summary', $weekly_data, [$admin_email], $subject,
            'This is your automated weekly summary report.');

        if ($result['success']) {
            echo "✓ Weekly report sent to {$admin_email}\n";
        } else {
            echo "✗ Failed to send weekly report: {$result['message']}\n";
        }
    }

    /**
     * Test email configuration
     */
    public function test_email() {
        echo "Testing email configuration...\n";

        $test_email = $this->db->get_where('settings', ['key' => 'admin_email'])->row();
        $test_email = $test_email ? $test_email->value : 'admin@windeep.com';

        $result = send_email($test_email, 'Email Test', '<h1>Email Test</h1><p>This is a test email from Windeep Finance.</p>');

        if ($result['success']) {
            echo "✓ Test email sent successfully to {$test_email}\n";
        } else {
            echo "✗ Test email failed: {$result['message']}\n";
        }
    }
}