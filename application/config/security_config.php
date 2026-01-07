<?php
/**
 * Security Configuration Updates
 * Apply these changes to application/config/config.php
 * Date: January 6, 2026
 */

// ============================================
// CSRF Protection (Enable)
// ============================================
$config['csrf_protection'] = TRUE;
$config['csrf_token_name'] = 'windeep_csrf_token';
$config['csrf_cookie_name'] = 'windeep_csrf_cookie';
$config['csrf_expire'] = 7200; // 2 hours
$config['csrf_regenerate'] = TRUE;
$config['csrf_exclude_uris'] = array('api/*'); // Exclude API endpoints if needed

// ============================================
// Session Security
// ============================================
$config['sess_driver'] = 'database'; // Store sessions in database (more secure than files)
$config['sess_cookie_name'] = 'windeep_session';
$config['sess_expiration'] = 7200; // 2 hours
$config['sess_save_path'] = 'ci_sessions'; // Database table name
$config['sess_match_ip'] = TRUE; // Validate IP
$config['sess_time_to_update'] = 300; // Regenerate session ID every 5 minutes
$config['sess_regenerate_destroy'] = TRUE; // Destroy old session

// Cookie Security
$config['cookie_prefix'] = 'wd_';
$config['cookie_domain'] = ''; // Set your domain
$config['cookie_path'] = '/';
$config['cookie_secure'] = TRUE; // HTTPS only (set FALSE for development)
$config['cookie_httponly'] = TRUE; // Prevent JavaScript access
$config['cookie_samesite'] = 'Lax'; // CSRF protection

// ============================================
// XSS Filtering
// ============================================
$config['global_xss_filtering'] = FALSE; // Don't use global (performance)
// Use $this->input->post('field', TRUE) for XSS filtering

// ============================================
// Error Handling (Production)
// ============================================
// In index.php, set:
// define('ENVIRONMENT', 'production');

// In application/config/config.php:
$config['log_threshold'] = 1; // Only log errors, not info
$config['log_path'] = APPPATH . '../logs/';
$config['log_file_permissions'] = 0644;

// ============================================
// Database Security
// ============================================
// In application/config/database.php:
// $db['default']['char_set'] = 'utf8mb4';
// $db['default']['dbcollat'] = 'utf8mb4_unicode_ci';
// $db['default']['encrypt'] = TRUE; // Use SSL for DB connection

// ============================================
// File Upload Security
// ============================================
$config['allowed_types'] = 'jpg|jpeg|png|pdf|doc|docx|xls|xlsx';
$config['max_size'] = 5120; // 5MB
$config['max_width'] = 2000;
$config['max_height'] = 2000;
$config['remove_spaces'] = TRUE;
$config['encrypt_name'] = TRUE; // Random filename

// ============================================
// Rate Limiting Configuration
// ============================================
$config['rate_limit'] = array(
    'login' => array(
        'max_attempts' => 5,
        'lockout_time' => 900, // 15 minutes
        'window' => 300 // 5 minutes
    ),
    'api' => array(
        'max_requests' => 100,
        'window' => 60 // 1 minute
    )
);

// ============================================
// Password Policy
// ============================================
$config['password_policy'] = array(
    'min_length' => 8,
    'require_uppercase' => TRUE,
    'require_lowercase' => TRUE,
    'require_number' => TRUE,
    'require_special' => TRUE,
    'max_age_days' => 90, // Force password change every 90 days
    'history_count' => 5 // Don't allow reusing last 5 passwords
);

// ============================================
// Allowed IPs for Admin Access (Optional)
// ============================================
$config['admin_allowed_ips'] = array(
    // Whitelist specific IPs for admin access
    // '192.168.1.100',
    // '10.0.0.0/8'
);

// ============================================
// Security Headers
// ============================================
$config['security_headers'] = array(
    'X-Frame-Options' => 'DENY',
    'X-Content-Type-Options' => 'nosniff',
    'X-XSS-Protection' => '1; mode=block',
    'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
    'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'",
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
    'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()'
);

// ============================================
// Audit Log Configuration
// ============================================
$config['audit_log'] = array(
    'enabled' => TRUE,
    'log_login' => TRUE,
    'log_logout' => TRUE,
    'log_failed_login' => TRUE,
    'log_crud_operations' => TRUE,
    'log_financial_transactions' => TRUE,
    'retention_days' => 2555 // 7 years (RBI requirement)
);
