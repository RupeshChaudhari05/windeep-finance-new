<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * WhatsApp Library
 * 
 * Integration with WhatsApp Business API
 * Supports: Meta Cloud API, Twilio, and custom providers
 */
class Whatsapp {
    
    private $CI;
    private $provider;
    private $api_url;
    private $api_key;
    private $phone_number_id;
    private $business_account_id;
    private $enabled;
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model('Setting_model');
        
        // Load configuration from settings
        $this->provider = $this->CI->Setting_model->get('whatsapp_provider', 'meta'); // meta, twilio, custom
        $this->api_url = $this->CI->Setting_model->get('whatsapp_api_url', 'https://graph.facebook.com/v18.0');
        $this->api_key = $this->CI->Setting_model->get('whatsapp_api_key', '');
        $this->phone_number_id = $this->CI->Setting_model->get('whatsapp_phone_number_id', '');
        $this->business_account_id = $this->CI->Setting_model->get('whatsapp_business_account_id', '');
        $this->enabled = $this->CI->Setting_model->get('whatsapp_enabled', false);
    }
    
    /**
     * Check if WhatsApp is enabled
     */
    public function is_enabled() {
        return $this->enabled && !empty($this->api_key);
    }
    
    /**
     * Send text message
     */
    public function send_message($phone, $message) {
        if (!$this->is_enabled()) {
            return ['success' => false, 'message' => 'WhatsApp is not enabled'];
        }
        
        // Format phone number (add country code if missing)
        $phone = $this->format_phone_number($phone);
        
        switch ($this->provider) {
            case 'meta':
                return $this->send_via_meta($phone, $message);
            case 'twilio':
                return $this->send_via_twilio($phone, $message);
            default:
                return $this->send_via_custom($phone, $message);
        }
    }
    
    /**
     * Send template message (for transactional messages)
     */
    public function send_template($phone, $template_name, $parameters = []) {
        if (!$this->is_enabled()) {
            return ['success' => false, 'message' => 'WhatsApp is not enabled'];
        }
        
        $phone = $this->format_phone_number($phone);
        
        switch ($this->provider) {
            case 'meta':
                return $this->send_template_via_meta($phone, $template_name, $parameters);
            default:
                // For other providers, convert to simple message
                $message = $this->parse_template($template_name, $parameters);
                return $this->send_message($phone, $message);
        }
    }
    
    /**
     * Send payment reminder
     */
    public function send_payment_reminder($phone, $member_name, $amount, $due_date) {
        return $this->send_template($phone, 'payment_reminder', [
            ['type' => 'text', 'text' => $member_name],
            ['type' => 'currency', 'currency' => ['fallback_value' => "₹{$amount}", 'code' => 'INR', 'amount_1000' => $amount * 1000]],
            ['type' => 'date_time', 'date_time' => ['fallback_value' => date('d M Y', strtotime($due_date))]]
        ]);
    }
    
    /**
     * Send loan approval notification
     */
    public function send_loan_approval($phone, $member_name, $loan_amount, $loan_id) {
        return $this->send_template($phone, 'loan_approved', [
            ['type' => 'text', 'text' => $member_name],
            ['type' => 'currency', 'currency' => ['fallback_value' => "₹{$loan_amount}", 'code' => 'INR', 'amount_1000' => $loan_amount * 1000]],
            ['type' => 'text', 'text' => $loan_id]
        ]);
    }
    
    /**
     * Send OTP for verification
     */
    public function send_otp($phone, $otp) {
        return $this->send_template($phone, 'otp_verification', [
            ['type' => 'text', 'text' => $otp]
        ]);
    }
    
    /**
     * Send via Meta Cloud API
     */
    private function send_via_meta($phone, $message) {
        $url = "{$this->api_url}/{$this->phone_number_id}/messages";
        
        $data = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $phone,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $message
            ]
        ];
        
        return $this->make_request($url, $data);
    }
    
    /**
     * Send template via Meta
     */
    private function send_template_via_meta($phone, $template_name, $parameters) {
        $url = "{$this->api_url}/{$this->phone_number_id}/messages";
        
        $data = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $phone,
            'type' => 'template',
            'template' => [
                'name' => $template_name,
                'language' => ['code' => 'en'],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => $parameters
                    ]
                ]
            ]
        ];
        
        return $this->make_request($url, $data);
    }
    
    /**
     * Send via Twilio
     */
    private function send_via_twilio($phone, $message) {
        $account_sid = $this->CI->Setting_model->get('twilio_account_sid', '');
        $auth_token = $this->CI->Setting_model->get('twilio_auth_token', '');
        $from_number = $this->CI->Setting_model->get('twilio_whatsapp_number', '');
        
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$account_sid}/Messages.json";
        
        $data = [
            'From' => "whatsapp:{$from_number}",
            'To' => "whatsapp:{$phone}",
            'Body' => $message
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_USERPWD, "{$account_sid}:{$auth_token}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code >= 200 && $http_code < 300) {
            $result = json_decode($response);
            return [
                'success' => true,
                'message_id' => $result->sid ?? null,
                'message' => 'Message sent successfully'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to send message: ' . $response
        ];
    }
    
    /**
     * Send via custom API
     */
    private function send_via_custom($phone, $message) {
        $custom_url = $this->CI->Setting_model->get('whatsapp_custom_url', '');
        $custom_key = $this->CI->Setting_model->get('whatsapp_custom_key', '');
        
        if (empty($custom_url)) {
            return ['success' => false, 'message' => 'Custom API URL not configured'];
        }
        
        $data = [
            'phone' => $phone,
            'message' => $message,
            'api_key' => $custom_key
        ];
        
        return $this->make_request($custom_url, $data);
    }
    
    /**
     * Make API request
     */
    private function make_request($url, $data, $method = 'POST') {
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json'
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'message' => 'cURL Error: ' . $error
            ];
        }
        
        $result = json_decode($response, true);
        
        if ($http_code >= 200 && $http_code < 300) {
            // Log success
            $this->log_message($data['to'] ?? $data['phone'], 'sent', $result['messages'][0]['id'] ?? null);
            
            return [
                'success' => true,
                'message_id' => $result['messages'][0]['id'] ?? null,
                'message' => 'Message sent successfully'
            ];
        }
        
        // Log failure
        $this->log_message($data['to'] ?? $data['phone'], 'failed', null, $response);
        
        return [
            'success' => false,
            'message' => $result['error']['message'] ?? 'Unknown error',
            'error_code' => $result['error']['code'] ?? null
        ];
    }
    
    /**
     * Format phone number with country code
     */
    private function format_phone_number($phone) {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Get country code from settings, default to India (+91)
        $CI =& get_instance();
        $country_code = isset($CI->settings['country_code']) ? $CI->settings['country_code'] : '91';
        
        // If number starts with 0, replace leading 0 with country code
        if (substr($phone, 0, 1) === '0') {
            $phone = $country_code . substr($phone, 1);
        }
        
        // If number is 10 digits, add country code
        if (strlen($phone) === 10) {
            $phone = $country_code . $phone;
        }
        
        return $phone;
    }
    
    /**
     * Parse template with parameters
     */
    private function parse_template($template_name, $parameters) {
        $templates = [
            'payment_reminder' => "Dear {{1}}, this is a reminder that your payment of {{2}} is due on {{3}}. Please make the payment to avoid late fees. - Windeep Finance",
            'loan_approved' => "Congratulations {{1}}! Your loan of {{2}} (Ref: {{3}}) has been approved. Please visit our office to complete the disbursement. - Windeep Finance",
            'otp_verification' => "Your OTP for Windeep Finance is {{1}}. Valid for 10 minutes. Do not share this code. - Windeep Finance",
            'payment_received' => "Dear {{1}}, we have received your payment of {{2}} on {{3}}. Thank you! - Windeep Finance",
            'welcome' => "Welcome to Windeep Finance, {{1}}! Your member code is {{2}}. Thank you for joining us. - Windeep Finance"
        ];
        
        $message = $templates[$template_name] ?? "Message from Windeep Finance.";
        
        foreach ($parameters as $index => $param) {
            $value = is_array($param) ? ($param['text'] ?? $param['currency']['fallback_value'] ?? $param['date_time']['fallback_value'] ?? '') : $param;
            $message = str_replace('{{' . ($index + 1) . '}}', $value, $message);
        }
        
        return $message;
    }
    
    /**
     * Log message to database
     */
    private function log_message($phone, $status, $message_id = null, $error = null) {
        try {
            $this->CI->db->insert('whatsapp_logs', [
                'phone' => $phone,
                'status' => $status,
                'message_id' => $message_id,
                'error' => $error,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            // Silently fail if table doesn't exist
            log_message('error', 'WhatsApp log failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get message status (for Meta API)
     */
    public function get_message_status($message_id) {
        if ($this->provider !== 'meta') {
            return ['success' => false, 'message' => 'Status check only available for Meta API'];
        }
        
        $url = "{$this->api_url}/{$message_id}";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->api_key
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    /**
     * Test WhatsApp configuration
     */
    public function test($test_phone = null) {
        if (!$this->is_enabled()) {
            return [
                'success' => false,
                'message' => 'WhatsApp is not enabled or not configured properly',
                'config' => [
                    'provider' => $this->provider,
                    'api_key_set' => !empty($this->api_key),
                    'phone_number_id_set' => !empty($this->phone_number_id)
                ]
            ];
        }
        
        if ($test_phone) {
            return $this->send_message($test_phone, 'This is a test message from Windeep Finance. If you received this, WhatsApp integration is working correctly!');
        }
        
        return [
            'success' => true,
            'message' => 'WhatsApp configuration appears valid',
            'config' => [
                'provider' => $this->provider,
                'api_key_set' => true,
                'phone_number_id_set' => !empty($this->phone_number_id)
            ]
        ];
    }
}
