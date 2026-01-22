<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Verification Controller
 * 
 * Handles email and phone verification for members and admins
 * Public controller - no authentication required
 */
class Verify extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('email_verification');
        $this->load->model('Member_model');
    }

    /**
     * Verify email from token link
     * URL: /verify/email/{token}
     */
    public function email($token = null)
    {
        if (empty($token)) {
            $this->_show_message('error', 'Invalid Verification Link', 'The verification link is invalid or malformed.');
            return;
        }

        $result = $this->email_verification->verify_token($token);

        if ($result['success']) {
            $this->_show_message(
                'success',
                'Email Verified Successfully!',
                'Your email address has been verified. You can now access all features of your account.',
                $result['user_type'] === 'member' ? 'members/login' : 'admin/login'
            );
        } else {
            $this->_show_message('error', 'Verification Failed', $result['message']);
        }
    }

    /**
     * Request resend verification email
     */
    public function resend($user_type = 'member', $user_id = null)
    {
        if (empty($user_id)) {
            $this->_show_message('error', 'Invalid Request', 'User ID is required.');
            return;
        }

        $result = $this->email_verification->resend_verification($user_id, $user_type);

        if ($result['success']) {
            $this->_show_message(
                'success',
                'Verification Email Sent',
                'A new verification email has been sent to your registered email address. Please check your inbox.'
            );
        } else {
            $this->_show_message('error', 'Could Not Send Email', $result['message']);
        }
    }

    /**
     * Password reset request form
     */
    public function forgot_password()
    {
        $data = [
            'title' => 'Forgot Password',
            'error' => $this->session->flashdata('error'),
            'success' => $this->session->flashdata('success')
        ];

        $this->load->view('public/forgot_password', $data);
    }

    /**
     * Process password reset request
     */
    public function process_forgot_password()
    {
        $email = $this->input->post('email');
        $user_type = $this->input->post('user_type', 'member');

        if (empty($email)) {
            $this->session->set_flashdata('error', 'Please enter your email address.');
            redirect('verify/forgot_password');
            return;
        }

        // Find user by email
        if ($user_type === 'admin') {
            $user = $this->db->where('email', $email)->get('admin_users')->row();
        } else {
            $user = $this->Member_model->get_by_email($email);
        }

        // Always show success message to prevent email enumeration
        $this->session->set_flashdata('success', 'If an account exists with this email, you will receive a password reset link shortly.');

        if ($user) {
            // Generate token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $this->db->insert('verification_tokens', [
                'user_id' => $user->id,
                'user_type' => $user_type,
                'type' => 'password_reset',
                'token' => $token,
                'email' => $email,
                'expires_at' => $expires
            ]);

            // Send email
            $reset_url = base_url('verify/reset_password/' . $token);
            $this->_send_reset_email($email, $user->name ?? $user->first_name, $reset_url);
        }

        redirect('verify/forgot_password');
    }

    /**
     * Password reset form
     */
    public function reset_password($token = null)
    {
        if (empty($token)) {
            $this->_show_message('error', 'Invalid Reset Link', 'The password reset link is invalid.');
            return;
        }

        // Validate token
        $token_data = $this->db
            ->where('token', $token)
            ->where('type', 'password_reset')
            ->where('used_at IS NULL', null, false)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->get('verification_tokens')
            ->row();

        if (!$token_data) {
            $this->_show_message('error', 'Invalid or Expired Link', 'This password reset link is invalid or has expired. Please request a new one.');
            return;
        }

        $data = [
            'title' => 'Reset Password',
            'token' => $token,
            'error' => $this->session->flashdata('error')
        ];

        $this->load->view('public/reset_password', $data);
    }

    /**
     * Process password reset
     */
    public function process_reset_password()
    {
        $token = $this->input->post('token');
        $password = $this->input->post('password');
        $confirm = $this->input->post('confirm_password');

        // Validate
        if (empty($password) || strlen($password) < 8) {
            $this->session->set_flashdata('error', 'Password must be at least 8 characters.');
            redirect('verify/reset_password/' . $token);
            return;
        }

        if ($password !== $confirm) {
            $this->session->set_flashdata('error', 'Passwords do not match.');
            redirect('verify/reset_password/' . $token);
            return;
        }

        // Validate token
        $token_data = $this->db
            ->where('token', $token)
            ->where('type', 'password_reset')
            ->where('used_at IS NULL', null, false)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->get('verification_tokens')
            ->row();

        if (!$token_data) {
            $this->_show_message('error', 'Invalid or Expired Link', 'This password reset link is invalid or has expired.');
            return;
        }

        // Update password
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        
        if ($token_data->user_type === 'admin') {
            $this->db->where('id', $token_data->user_id)
                ->update('admin_users', ['password' => $hashed]);
        } else {
            $this->db->where('id', $token_data->user_id)
                ->update('members', ['password' => $hashed]);
        }

        // Mark token as used
        $this->db->where('id', $token_data->id)
            ->update('verification_tokens', [
                'used_at' => date('Y-m-d H:i:s'),
                'verified_ip' => $this->input->ip_address()
            ]);

        // Log activity
        $this->db->insert('audit_log', [
            'user_type' => $token_data->user_type,
            'user_id' => $token_data->user_id,
            'action' => 'password_reset',
            'description' => 'Password reset via email link',
            'ip_address' => $this->input->ip_address()
        ]);

        $this->_show_message(
            'success',
            'Password Reset Successfully!',
            'Your password has been reset. You can now login with your new password.',
            $token_data->user_type === 'member' ? 'members/login' : 'admin/login'
        );
    }

    /**
     * Display a message page
     */
    private function _show_message($type, $title, $message, $redirect_url = null)
    {
        $data = [
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'redirect_url' => $redirect_url,
            'company_name' => $this->db->select('value')->where('key', 'company_name')->get('settings')->row()->value ?? 'Windeep Finance'
        ];

        $this->load->view('public/message', $data);
    }

    /**
     * Send password reset email
     */
    private function _send_reset_email($email, $name, $reset_url)
    {
        $this->load->library('email');
        
        $company_name = $this->db->select('value')->where('key', 'company_name')->get('settings')->row()->value ?? 'Windeep Finance';
        
        $subject = "Password Reset Request - {$company_name}";
        $body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #333;'>Password Reset Request</h2>
            <p>Hello {$name},</p>
            <p>We received a request to reset your password for your {$company_name} account.</p>
            <p>Click the button below to reset your password:</p>
            <p style='text-align: center; margin: 30px 0;'>
                <a href='{$reset_url}' style='background-color: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>Reset Password</a>
            </p>
            <p style='color: #666; font-size: 14px;'>This link will expire in 1 hour.</p>
            <p style='color: #666; font-size: 14px;'>If you didn't request this, please ignore this email.</p>
            <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>
            <p style='color: #999; font-size: 12px;'>This is an automated message from {$company_name}</p>
        </div>";

        $this->email->from($this->config->item('from_email') ?: 'noreply@example.com', $company_name);
        $this->email->to($email);
        $this->email->subject($subject);
        $this->email->message($body);
        $this->email->set_mailtype('html');
        
        return $this->email->send();
    }
}
