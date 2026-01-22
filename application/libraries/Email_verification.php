<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Email Verification Library
 * 
 * Handles email verification for members and admins
 */
class Email_verification {
    
    private $CI;
    private $token_expiry = 24; // Hours
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->load->helper('email');
    }
    
    /**
     * Generate verification token
     */
    public function generate_token($user_id, $user_type = 'member', $email = null) {
        // Delete any existing tokens
        $this->CI->db->where('user_id', $user_id)
            ->where('user_type', $user_type)
            ->where('type', 'email_verification')
            ->delete('verification_tokens');
        
        // Generate new token
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime("+{$this->token_expiry} hours"));
        
        $this->CI->db->insert('verification_tokens', [
            'user_id' => $user_id,
            'user_type' => $user_type,
            'type' => 'email_verification',
            'token' => $token,
            'email' => $email,
            'expires_at' => $expires_at,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return $token;
    }
    
    /**
     * Send verification email
     */
    public function send_verification_email($user_id, $user_type = 'member') {
        // Get user details
        if ($user_type === 'member') {
            $user = $this->CI->db->get_where('members', ['id' => $user_id])->row();
            $name = $user->first_name ?? 'Member';
        } else {
            $user = $this->CI->db->get_where('admin_users', ['id' => $user_id])->row();
            $name = $user->full_name ?? 'Admin';
        }
        
        if (!$user || empty($user->email)) {
            return ['success' => false, 'message' => 'User or email not found'];
        }
        
        // Generate token
        $token = $this->generate_token($user_id, $user_type, $user->email);
        
        // Build verification URL
        $verify_url = base_url("verify/email/{$token}");
        
        // Send email
        $subject = "Verify Your Email - Windeep Finance";
        
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #007bff; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; background: #f9f9f9; }
                .button { display: inline-block; padding: 12px 30px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .warning { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Email Verification</h1>
                </div>
                <div class='content'>
                    <p>Dear <strong>{$name}</strong>,</p>
                    <p>Thank you for registering with Windeep Finance. Please verify your email address by clicking the button below:</p>
                    
                    <p style='text-align: center;'>
                        <a href='{$verify_url}' class='button'>Verify Email Address</a>
                    </p>
                    
                    <div class='warning'>
                        <strong>⏰ This link expires in {$this->token_expiry} hours.</strong>
                    </div>
                    
                    <p>If you didn't create an account with us, please ignore this email.</p>
                    
                    <p>If the button doesn't work, copy and paste this link into your browser:</p>
                    <p style='word-break: break-all; font-size: 12px;'>{$verify_url}</p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " Windeep Finance. All rights reserved.</p>
                    <p>This is an automated email, please do not reply.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $result = send_email($user->email, $subject, $message);
        
        if ($result['success']) {
            // Log the verification email sent
            $this->CI->db->insert('activity_log', [
                'user_type' => $user_type,
                'user_id' => $user_id,
                'action' => 'verification_email_sent',
                'description' => 'Verification email sent to ' . $user->email,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        return $result;
    }
    
    /**
     * Verify email token
     */
    public function verify_token($token) {
        // Find token
        $record = $this->CI->db->where('token', $token)
            ->where('type', 'email_verification')
            ->where('used_at IS NULL')
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->get('verification_tokens')
            ->row();
        
        if (!$record) {
            return [
                'success' => false,
                'message' => 'Invalid or expired verification link. Please request a new one.',
                'expired' => true
            ];
        }
        
        // Mark token as used
        $this->CI->db->where('id', $record->id)
            ->update('verification_tokens', [
                'used_at' => date('Y-m-d H:i:s'),
                'verified_ip' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);
        
        // Update user as verified
        if ($record->user_type === 'member') {
            $this->CI->db->where('id', $record->user_id)
                ->update('members', [
                    'email_verified' => 1,
                    'email_verified_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
        } else {
            $this->CI->db->where('id', $record->user_id)
                ->update('admin_users', [
                    'email_verified' => 1,
                    'email_verified_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
        }
        
        // Log verification
        $this->CI->db->insert('activity_log', [
            'user_type' => $record->user_type,
            'user_id' => $record->user_id,
            'action' => 'email_verified',
            'description' => 'Email verified: ' . $record->email,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return [
            'success' => true,
            'message' => 'Email verified successfully!',
            'user_id' => $record->user_id,
            'user_type' => $record->user_type,
            'email' => $record->email
        ];
    }
    
    /**
     * Check if user's email is verified
     */
    public function is_verified($user_id, $user_type = 'member') {
        if ($user_type === 'member') {
            $user = $this->CI->db->select('email_verified')
                ->get_where('members', ['id' => $user_id])
                ->row();
        } else {
            $user = $this->CI->db->select('email_verified')
                ->get_where('admin_users', ['id' => $user_id])
                ->row();
        }
        
        return $user && $user->email_verified == 1;
    }
    
    /**
     * Resend verification email
     */
    public function resend_verification($user_id, $user_type = 'member') {
        // Check rate limit (max 3 per hour)
        $recent_count = $this->CI->db->where('user_id', $user_id)
            ->where('user_type', $user_type)
            ->where('type', 'email_verification')
            ->where('created_at >', date('Y-m-d H:i:s', strtotime('-1 hour')))
            ->count_all_results('verification_tokens');
        
        if ($recent_count >= 3) {
            return [
                'success' => false,
                'message' => 'Too many verification emails requested. Please wait an hour before trying again.'
            ];
        }
        
        return $this->send_verification_email($user_id, $user_type);
    }
    
    /**
     * Clean up expired tokens
     */
    public function cleanup_expired_tokens() {
        $this->CI->db->where('expires_at <', date('Y-m-d H:i:s'))
            ->delete('verification_tokens');
        
        return $this->CI->db->affected_rows();
    }
}
