<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Email Helper Functions
 * Uses PHPMailer for sending emails
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer classes
require_once APPPATH . '../vendor/autoload.php';

/**
 * Send email using PHPMailer
 */
function send_email($to, $subject, $message, $from_email = null, $from_name = null, $attachments = [])
{
    $CI =& get_instance();

    // Get email config
    $mail_driver = env('MAIL_DRIVER', 'smtp');
    $mail_host = env('MAIL_HOST', 'smtp.gmail.com');
    $mail_port = env('MAIL_PORT', 587);
    $mail_username = env('MAIL_USERNAME', '');
    $mail_password = env('MAIL_PASSWORD', '');
    $mail_from_address = env('MAIL_FROM_ADDRESS', 'noreply@windeep.com');
    $mail_from_name = env('MAIL_FROM_NAME', 'Windeep Finance');

    // Override defaults if provided
    $from_email = $from_email ?: $mail_from_address;
    $from_name = $from_name ?: $mail_from_name;

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $mail_host;
        $mail->SMTPAuth = true;
        $mail->Username = $mail_username;
        $mail->Password = $mail_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $mail_port;

        // Recipients
        $mail->setFrom($from_email, $from_name);

        // Handle multiple recipients
        if (is_array($to)) {
            foreach ($to as $email) {
                $mail->addAddress($email);
            }
        } else {
            $mail->addAddress($to);
        }

        // Attachments
        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                if (is_array($attachment)) {
                    $mail->addAttachment($attachment['path'], $attachment['name'] ?? '');
                } else {
                    $mail->addAttachment($attachment);
                }
            }
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];

    } catch (Exception $e) {
        log_message('error', 'Email sending failed: ' . $mail->ErrorInfo);
        return ['success' => false, 'message' => $mail->ErrorInfo];
    }
}

/**
 * Send report via email
 */
function send_report_email($report_type, $report_data, $recipient_emails, $subject = null, $additional_message = '')
{
    $CI =& get_instance();

    if (empty($recipient_emails)) {
        return ['success' => false, 'message' => 'No recipients specified'];
    }

    // Generate subject if not provided
    if (!$subject) {
        $subject = ucfirst(str_replace('-', ' ', $report_type)) . ' Report - ' . date('Y-m-d');
    }

    // Create HTML email content
    $html_content = generate_report_email_html($report_type, $report_data, $additional_message);

    // Send email
    return send_email($recipient_emails, $subject, $html_content);
}

/**
 * Generate HTML content for report emails
 */
function generate_report_email_html($report_type, $report_data, $additional_message = '')
{
    $CI =& get_instance();

    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>' . ucfirst(str_replace('-', ' ', $report_type)) . ' Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #007bff; padding-bottom: 20px; }
        .header h1 { color: #007bff; margin: 0; }
        .header p { color: #666; margin: 5px 0 0 0; }
        .content { margin-bottom: 30px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .table th { background-color: #f8f9fa; font-weight: bold; }
        .table tr:nth-child(even) { background-color: #f8f9fa; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 12px; }
        .additional-message { background: #e3f2fd; padding: 15px; border-left: 4px solid #007bff; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>' . ucfirst(str_replace('-', ' ', $report_type)) . ' Report</h1>
            <p>Generated on ' . date('F j, Y \a\t g:i A') . '</p>
        </div>';

    if ($additional_message) {
        $html .= '<div class="additional-message">' . nl2br(htmlspecialchars($additional_message)) . '</div>';
    }

    $html .= '<div class="content">';

    // Generate table based on report type
    if (!empty($report_data)) {
        $html .= generate_report_table_html($report_type, $report_data);
    } else {
        $html .= '<p>No data available for this report.</p>';
    }

    $html .= '</div>
        <div class="footer">
            <p>This is an automated report from Windeep Finance System.</p>
            <p>Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>';

    return $html;
}

/**
 * Generate table HTML for different report types
 */
function generate_report_table_html($report_type, $data)
{
    if (empty($data)) {
        return '<p>No data available.</p>';
    }

    $html = '<table class="table">';

    // Generate headers based on first row
    $first_row = is_array($data) ? reset($data) : $data;
    if (is_object($first_row)) {
        $first_row = (array) $first_row;
    }

    if (!empty($first_row)) {
        $html .= '<thead><tr>';
        foreach (array_keys($first_row) as $key) {
            $label = ucwords(str_replace(['_', '-'], ' ', $key));
            $html .= '<th>' . htmlspecialchars($label) . '</th>';
        }
        $html .= '</tr></thead>';
    }

    $html .= '<tbody>';

    foreach ($data as $row) {
        if (is_object($row)) {
            $row = (array) $row;
        }

        $html .= '<tr>';
        foreach ($row as $value) {
            $display_value = $value;

            // Format dates
            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
                $display_value = date('M j, Y', strtotime($value));
            }
            // Format currency
            elseif (is_numeric($value) && strpos($value, '.') !== false) {
                $display_value = number_format((float)$value, 2);
            }

            $html .= '<td>' . htmlspecialchars($display_value) . '</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';
    return $html;
}

/**
 * Send due date reminder email to member
 */
function send_due_date_reminder($member, $loan_details, $due_amount, $due_date)
{
    $subject = 'Payment Due Reminder - Windeep Finance';

    $message = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <h2 style='color: #007bff;'>Payment Due Reminder</h2>
        <p>Dear {$member->first_name} {$member->last_name},</p>

        <p>This is a reminder that your loan payment is due.</p>

        <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
            <strong>Loan Details:</strong><br>
            Member Code: {$member->member_code}<br>
            Due Amount: ₹" . number_format($due_amount, 2) . "<br>
            Due Date: " . date('F j, Y', strtotime($due_date)) . "
        </div>

        <p>Please ensure timely payment to avoid late fees.</p>

        <p>If you have already made the payment, please disregard this reminder.</p>

        <p>Thank you for banking with us!</p>

        <p>Best regards,<br>Windeep Finance Team</p>
    </div>";

    return send_email($member->email, $subject, $message);
}

/**
 * Send installment reminder email
 */
function send_installment_reminder($member, $installment_details)
{
    $subject = 'Installment Payment Reminder - Windeep Finance';

    $message = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <h2 style='color: #007bff;'>Installment Payment Reminder</h2>
        <p>Dear {$member->first_name} {$member->last_name},</p>

        <p>Your next installment payment is approaching.</p>

        <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
            <strong>Installment Details:</strong><br>
            Member Code: {$member->member_code}<br>
            Amount: ₹" . number_format($installment_details->amount, 2) . "<br>
            Due Date: " . date('F j, Y', strtotime($installment_details->due_date)) . "<br>
            Installment: {$installment_details->installment_number}
        </div>

        <p>Please make your payment on time to maintain good standing.</p>

        <p>Thank you!</p>

        <p>Best regards,<br>Windeep Finance Team</p>
    </div>";

    return send_email($member->email, $subject, $message);
}

/**
 * Send manual email to member
 */
function send_manual_member_email($member_email, $subject, $message, $attachments = [])
{
    return send_email($member_email, $subject, $message, null, null, $attachments);
}