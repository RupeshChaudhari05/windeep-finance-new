-- Migration 010: Create Security Logs and Session Tables
-- Purpose: Track security events and secure session storage
-- Date: January 6, 2026

USE windeep_finance;

-- ============================================
-- Security Logs Table
-- ============================================

CREATE TABLE IF NOT EXISTS `security_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `event_type` VARCHAR(50) NOT NULL COMMENT 'login_success, login_failed, login_locked, logout, password_change, etc.',
    `user_id` INT UNSIGNED NULL COMMENT 'User ID if known',
    `email` VARCHAR(255) NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT NULL,
    `details` JSON NULL COMMENT 'Additional event details',
    `severity` ENUM('info', 'warning', 'critical') DEFAULT 'info',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type, created_at),
    INDEX idx_user_id (user_id, created_at),
    INDEX idx_ip_address (ip_address, created_at),
    INDEX idx_severity (severity, created_at)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Security event audit trail - Retain for 7 years (RBI requirement)';

-- ============================================
-- Session Storage Table (for database sessions)
-- ============================================

CREATE TABLE IF NOT EXISTS `ci_sessions` (
    `id` VARCHAR(128) NOT NULL PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL,
    `timestamp` INT UNSIGNED NOT NULL DEFAULT 0,
    `data` BLOB NOT NULL,
    INDEX idx_timestamp (timestamp),
    INDEX idx_ip_address (ip_address)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'CodeIgniter session storage';

-- ============================================
-- Failed Login Attempts Table (Alternative to cache)
-- ============================================

CREATE TABLE IF NOT EXISTS `failed_login_attempts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `identifier` VARCHAR(255) NOT NULL COMMENT 'IP address or email',
    `identifier_type` ENUM('ip', 'email', 'username') DEFAULT 'ip',
    `attempts` INT UNSIGNED NOT NULL DEFAULT 1,
    `first_attempt_at` DATETIME NOT NULL,
    `last_attempt_at` DATETIME NOT NULL,
    `locked_until` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE INDEX idx_identifier (identifier, identifier_type),
    INDEX idx_locked_until (locked_until)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Track failed login attempts for rate limiting';

-- ============================================
-- Password History Table (Prevent reuse)
-- ============================================

CREATE TABLE IF NOT EXISTS `password_history` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id, created_at),
    FOREIGN KEY (user_id) REFERENCES admin (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Track password history to prevent reuse';

-- ============================================
-- Active Sessions Table (Track concurrent logins)
-- ============================================

CREATE TABLE IF NOT EXISTS `active_sessions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `session_id` VARCHAR(128) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT NULL,
    `last_activity` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_last_activity (last_activity),
    FOREIGN KEY (user_id) REFERENCES admin (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Track active user sessions for security monitoring';

-- ============================================
-- 2FA (Two-Factor Authentication) Table
-- ============================================

CREATE TABLE IF NOT EXISTS `two_factor_auth` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL UNIQUE,
    `secret_key` VARCHAR(255) NOT NULL COMMENT 'Encrypted TOTP secret',
    `backup_codes` JSON NULL COMMENT 'Encrypted backup codes',
    `is_enabled` TINYINT(1) NOT NULL DEFAULT 0,
    `enabled_at` DATETIME NULL,
    `last_used_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES admin (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Two-factor authentication configuration';

-- ============================================
-- API Tokens Table (for API authentication)
-- ============================================

CREATE TABLE IF NOT EXISTS `api_tokens` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `token` VARCHAR(255) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL COMMENT 'Token description/name',
    `scopes` JSON NULL COMMENT 'Allowed API scopes',
    `last_used_at` DATETIME NULL,
    `expires_at` DATETIME NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at),
    FOREIGN KEY (user_id) REFERENCES admin (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'API authentication tokens';

-- ============================================
-- Stored Procedures for Security Operations
-- ============================================

DELIMITER $$

-- Cleanup old security logs (older than 7 years)
CREATE PROCEDURE sp_cleanup_old_security_logs()
BEGIN
    DELETE FROM security_logs
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 YEAR);
    
    SELECT ROW_COUNT() AS deleted_records;
END$$

-- Cleanup expired sessions
CREATE PROCEDURE sp_cleanup_expired_sessions()
BEGIN
    -- Delete sessions older than 2 hours
    DELETE FROM ci_sessions
    WHERE timestamp < UNIX_TIMESTAMP() - 7200;
    
    -- Delete inactive active_sessions
    DELETE FROM active_sessions
    WHERE last_activity < DATE_SUB(NOW(), INTERVAL 2 HOUR);
    
    SELECT ROW_COUNT() AS deleted_sessions;
END$$

-- Get security summary for user
CREATE PROCEDURE sp_get_user_security_summary(IN p_user_id INT)
BEGIN
    SELECT 
        'Active Sessions' AS metric,
        COUNT(*) AS value
    FROM active_sessions
    WHERE user_id = p_user_id
    
    UNION ALL
    
    SELECT 
        'Failed Login Attempts (Last 24h)',
        COUNT(*)
    FROM security_logs
    WHERE user_id = p_user_id
      AND event_type = 'login_failed'
      AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    
    UNION ALL
    
    SELECT 
        'Password Changes (Last 90 days)',
        COUNT(*)
    FROM password_history
    WHERE user_id = p_user_id
      AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
    
    UNION ALL
    
    SELECT 
        '2FA Enabled',
        CASE WHEN is_enabled = 1 THEN 'Yes' ELSE 'No' END
    FROM two_factor_auth
    WHERE user_id = p_user_id;
END$$

DELIMITER;

-- ============================================
-- Insert Sample Security Events
-- ============================================

-- Insert a successful login event
INSERT INTO
    security_logs (
        event_type,
        email,
        ip_address,
        user_agent,
        details,
        severity
    )
VALUES (
        'login_success',
        'admin@windeep.com',
        '127.0.0.1',
        'Mozilla/5.0',
        '{"user_id": 1, "session_duration": 7200}',
        'info'
    );

-- ============================================
-- Create Indexes for Performance
-- ============================================

-- Optimize security_logs queries
CREATE INDEX idx_security_logs_user_event ON security_logs (
    user_id,
    event_type,
    created_at
);

-- Optimize active_sessions queries
CREATE INDEX idx_active_sessions_user_activity ON active_sessions (user_id, last_activity DESC);

-- ============================================
-- Scheduled Job (Create Event)
-- ============================================

-- Auto-cleanup old logs daily
CREATE EVENT IF NOT EXISTS evt_cleanup_security_logs
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO CALL sp_cleanup_old_security_logs();

-- Auto-cleanup expired sessions every hour
CREATE EVENT IF NOT EXISTS evt_cleanup_expired_sessions
ON SCHEDULE EVERY 1 HOUR
STARTS CURRENT_TIMESTAMP
DO CALL sp_cleanup_expired_sessions();

-- ============================================
-- Verify Tables Created
-- ============================================

SELECT
    table_name,
    table_rows,
    ROUND(
        (
            (data_length + index_length) / 1024 / 1024
        ),
        2
    ) AS size_mb
FROM information_schema.TABLES
WHERE
    table_schema = 'windeep_finance'
    AND table_name IN (
        'security_logs',
        'ci_sessions',
        'failed_login_attempts',
        'password_history',
        'active_sessions',
        'two_factor_auth',
        'api_tokens'
    )
ORDER BY table_name;