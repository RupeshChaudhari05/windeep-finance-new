-- ============================================================
-- WINDEEP FINANCE - COMPLETE DATABASE INSTALL FILE
-- Version : 1.0.0
-- Compatible : MySQL 5.7+ | MySQL 8.0+ | MariaDB 10.3+
-- Import via : phpMyAdmin → Import → Select this file
-- ============================================================
-- DEFAULT LOGIN CREDENTIALS
--   Admin URL : /admin
--   Username  : admin
--   Password  : admin123
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

SET SQL_MODE = '';

SET NAMES utf8mb4;

SET CHARACTER SET utf8mb4;

-- ============================================================
-- DROP ALL TABLES (safe, any order)
-- ============================================================
DROP TABLE IF EXISTS `view_requests`;

DROP TABLE IF EXISTS `shares`;

DROP TABLE IF EXISTS `send_form`;

DROP TABLE IF EXISTS `accounting_settings`;

DROP TABLE IF EXISTS `guarantor_settings`;

DROP TABLE IF EXISTS `loan_foreclosure_requests`;

DROP TABLE IF EXISTS `transaction_mappings`;

DROP TABLE IF EXISTS `bank_imports`;

DROP TABLE IF EXISTS `loan_payments`;

DROP TABLE IF EXISTS `loan_installments`;

DROP TABLE IF EXISTS `loans`;

DROP TABLE IF EXISTS `loan_guarantors`;

DROP TABLE IF EXISTS `loan_applications`;

DROP TABLE IF EXISTS `loan_transaction_details`;

DROP TABLE IF EXISTS `loan_transactions`;

DROP TABLE IF EXISTS `savings_transactions`;

DROP TABLE IF EXISTS `savings_schedule`;

DROP TABLE IF EXISTS `savings_accounts`;

DROP TABLE IF EXISTS `fines`;

DROP TABLE IF EXISTS `fine_rules`;

DROP TABLE IF EXISTS `member_ledger`;

DROP TABLE IF EXISTS `members`;

DROP TABLE IF EXISTS `member_code_sequence`;

DROP TABLE IF EXISTS `general_ledger`;

DROP TABLE IF EXISTS `chart_of_accounts`;

DROP TABLE IF EXISTS `bank_statement_imports`;

DROP TABLE IF EXISTS `bank_transactions`;

DROP TABLE IF EXISTS `bank_balance_history`;

DROP TABLE IF EXISTS `bank_accounts`;

DROP TABLE IF EXISTS `notifications`;

DROP TABLE IF EXISTS `chat_box`;

DROP TABLE IF EXISTS `expenditure`;

DROP TABLE IF EXISTS `two_factor_auth`;

DROP TABLE IF EXISTS `password_history`;

DROP TABLE IF EXISTS `api_tokens`;

DROP TABLE IF EXISTS `active_sessions`;

DROP TABLE IF EXISTS `admin_sessions`;

DROP TABLE IF EXISTS `failed_login_attempts`;

DROP TABLE IF EXISTS `security_logs`;

DROP TABLE IF EXISTS `audit_logs`;

DROP TABLE IF EXISTS `activity_logs`;

DROP TABLE IF EXISTS `ci_sessions`;

DROP TABLE IF EXISTS `admin_details`;

DROP TABLE IF EXISTS `schema_migrations`;

DROP TABLE IF EXISTS `rule_code_sequence`;

DROP TABLE IF EXISTS `other_member_details`;

DROP TABLE IF EXISTS `savings_schemes`;

DROP TABLE IF EXISTS `loan_products`;

DROP TABLE IF EXISTS `financial_years`;

DROP TABLE IF EXISTS `system_settings`;

DROP TABLE IF EXISTS `requests_status`;

DROP TABLE IF EXISTS `member_details`;

DROP TABLE IF EXISTS `admin_users`;

-- ============================================================
-- TABLE: system_settings
-- ============================================================
CREATE TABLE `system_settings` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    `setting_type` ENUM(
        'string',
        'number',
        'boolean',
        'json'
    ) DEFAULT 'string',
    `description` VARCHAR(255) DEFAULT NULL,
    `is_editable` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_setting_key` (`setting_key`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: financial_years
-- ============================================================
CREATE TABLE `financial_years` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `year_code` VARCHAR(20) NOT NULL COMMENT 'e.g., 2025-26',
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `is_active` TINYINT(1) DEFAULT 0,
    `is_closed` TINYINT(1) DEFAULT 0,
    `closed_at` TIMESTAMP NULL DEFAULT NULL,
    `closed_by` INT(10) UNSIGNED DEFAULT NULL,
    `created_by` INT(10) UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_year_code` (`year_code`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: admin_users
-- ============================================================
CREATE TABLE `admin_users` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `role` ENUM(
        'super_admin',
        'admin',
        'manager',
        'accountant',
        'viewer'
    ) DEFAULT 'viewer',
    `permissions` LONGTEXT DEFAULT NULL COMMENT 'Granular permissions JSON',
    `is_active` TINYINT(1) DEFAULT 1,
    `last_login` TIMESTAMP NULL DEFAULT NULL,
    `last_login_ip` VARCHAR(45) DEFAULT NULL,
    `password_changed_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_username` (`username`),
    UNIQUE KEY `idx_email` (`email`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: requests_status
-- ============================================================
CREATE TABLE `requests_status` (
    `id` INT(11) NOT NULL,
    `status` VARCHAR(50) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ============================================================
-- TABLE: member_details  (legacy member table)
-- ============================================================
CREATE TABLE `member_details` (
    `member_id` VARCHAR(50) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `contact_no` VARCHAR(20) DEFAULT NULL,
    `dob` DATE DEFAULT NULL,
    `aadhar_card` VARCHAR(20) DEFAULT NULL,
    `pan_card` VARCHAR(20) DEFAULT NULL,
    `ifsc_code` VARCHAR(20) DEFAULT NULL,
    `account_number` VARCHAR(50) DEFAULT NULL,
    `bank_address` TEXT DEFAULT NULL,
    `profile_pic` VARCHAR(255) DEFAULT NULL,
    `shares_flag` INT(11) DEFAULT 0,
    `is_deleted` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`member_id`),
    UNIQUE KEY `email` (`email`),
    UNIQUE KEY `aadhar_card` (`aadhar_card`),
    UNIQUE KEY `pan_card` (`pan_card`),
    UNIQUE KEY `account_number` (`account_number`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ============================================================
-- TABLE: admin_details
-- ============================================================
CREATE TABLE `admin_details` (
    `admin_id` INT(11) NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `member_id` VARCHAR(50) DEFAULT NULL,
    `is_deleted` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`admin_id`),
    UNIQUE KEY `email` (`email`),
    KEY `member_id` (`member_id`),
    CONSTRAINT `admin_details_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `member_details` (`member_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ============================================================
-- TABLE: admin_sessions
-- ============================================================
CREATE TABLE `admin_sessions` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `admin_id` INT(10) UNSIGNED NOT NULL,
    `session_token` VARCHAR(255) NOT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `expires_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_admin_id` (`admin_id`),
    KEY `idx_session_token` (`session_token`),
    CONSTRAINT `admin_sessions_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: active_sessions
-- ============================================================
CREATE TABLE `active_sessions` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(10) UNSIGNED NOT NULL,
    `session_id` VARCHAR(128) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `last_activity` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_last_activity` (`last_activity`),
    KEY `idx_active_sessions_user_activity` (`user_id`, `last_activity`),
    CONSTRAINT `active_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Track active user sessions for security monitoring';

-- ============================================================
-- TABLE: api_tokens
-- ============================================================
CREATE TABLE `api_tokens` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(10) UNSIGNED NOT NULL,
    `token` VARCHAR(255) NOT NULL,
    `name` VARCHAR(100) NOT NULL COMMENT 'Token description/name',
    `scopes` LONGTEXT DEFAULT NULL COMMENT 'Allowed API scopes JSON',
    `last_used_at` DATETIME DEFAULT NULL,
    `expires_at` DATETIME DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `token` (`token`),
    KEY `idx_token` (`token`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_expires_at` (`expires_at`),
    CONSTRAINT `api_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'API authentication tokens';

-- ============================================================
-- TABLE: password_history
-- ============================================================
CREATE TABLE `password_history` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(10) UNSIGNED NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`, `created_at`),
    CONSTRAINT `password_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Track password history to prevent reuse';

-- ============================================================
-- TABLE: two_factor_auth
-- ============================================================
CREATE TABLE `two_factor_auth` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(10) UNSIGNED NOT NULL,
    `secret_key` VARCHAR(255) NOT NULL COMMENT 'Encrypted TOTP secret',
    `backup_codes` LONGTEXT DEFAULT NULL COMMENT 'Encrypted backup codes JSON',
    `is_enabled` TINYINT(1) NOT NULL DEFAULT 0,
    `enabled_at` DATETIME DEFAULT NULL,
    `last_used_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_id` (`user_id`),
    CONSTRAINT `two_factor_auth_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Two-factor authentication configuration';

-- ============================================================
-- TABLE: failed_login_attempts
-- ============================================================
CREATE TABLE `failed_login_attempts` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `identifier` VARCHAR(255) NOT NULL COMMENT 'IP address or email',
    `identifier_type` ENUM('ip', 'email', 'username') DEFAULT 'ip',
    `attempts` INT(10) UNSIGNED NOT NULL DEFAULT 1,
    `first_attempt_at` DATETIME NOT NULL,
    `last_attempt_at` DATETIME NOT NULL,
    `locked_until` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_identifier` (
        `identifier`,
        `identifier_type`
    ),
    KEY `idx_locked_until` (`locked_until`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Track failed login attempts for rate limiting';

-- ============================================================
-- TABLE: security_logs
-- ============================================================
CREATE TABLE `security_logs` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `event_type` VARCHAR(50) NOT NULL COMMENT 'login_success, login_failed, logout, password_change, etc.',
    `user_id` INT(10) UNSIGNED DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `details` LONGTEXT DEFAULT NULL COMMENT 'Additional event details JSON',
    `severity` ENUM('info', 'warning', 'critical') DEFAULT 'info',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_event_type` (`event_type`, `created_at`),
    KEY `idx_user_id` (`user_id`, `created_at`),
    KEY `idx_ip_address` (`ip_address`, `created_at`),
    KEY `idx_severity` (`severity`, `created_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Security event audit trail';

-- ============================================================
-- TABLE: audit_logs
-- ============================================================
CREATE TABLE `audit_logs` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `audit_code` VARCHAR(50) NOT NULL,
    `user_type` ENUM('admin', 'member', 'system') NOT NULL,
    `user_id` INT(10) UNSIGNED DEFAULT NULL,
    `action` VARCHAR(50) NOT NULL,
    `module` VARCHAR(50) NOT NULL,
    `table_name` VARCHAR(100) NOT NULL,
    `record_id` INT(10) UNSIGNED NOT NULL,
    `old_values` LONGTEXT DEFAULT NULL,
    `new_values` LONGTEXT DEFAULT NULL,
    `changed_fields` LONGTEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `session_id` VARCHAR(255) DEFAULT NULL,
    `remarks` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_code` (`audit_code`),
    KEY `idx_user` (`user_type`, `user_id`),
    KEY `idx_action` (`action`),
    KEY `idx_module` (`module`),
    KEY `idx_table_record` (`table_name`, `record_id`),
    KEY `idx_created_at` (`created_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: activity_logs
-- ============================================================
CREATE TABLE `activity_logs` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_type` ENUM('admin', 'member') NOT NULL,
    `user_id` INT(10) UNSIGNED DEFAULT NULL,
    `activity` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `module` VARCHAR(50) DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user` (`user_type`, `user_id`),
    KEY `idx_activity` (`activity`),
    KEY `idx_created_at` (`created_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: ci_sessions  (CodeIgniter sessions)
-- ============================================================
CREATE TABLE `ci_sessions` (
    `id` VARCHAR(128) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `timestamp` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `data` BLOB NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_timestamp` (`timestamp`),
    KEY `idx_ip_address` (`ip_address`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'CodeIgniter session storage';

-- ============================================================
-- TABLE: bank_accounts  (Organisation bank accounts)
-- ============================================================
CREATE TABLE `bank_accounts` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `account_name` VARCHAR(100) NOT NULL,
    `bank_name` VARCHAR(100) NOT NULL,
    `branch_name` VARCHAR(100) DEFAULT NULL,
    `account_number` VARCHAR(30) NOT NULL,
    `ifsc_code` VARCHAR(15) DEFAULT NULL,
    `account_type` ENUM('current', 'savings', 'cash') DEFAULT 'current',
    `opening_balance` DECIMAL(15, 2) DEFAULT 0.00,
    `current_balance` DECIMAL(15, 2) DEFAULT 0.00,
    `is_active` TINYINT(1) DEFAULT 1,
    `is_primary` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_account_number` (`account_number`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: bank_statement_imports
-- ============================================================
CREATE TABLE `bank_statement_imports` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `import_code` VARCHAR(30) NOT NULL,
    `bank_account_id` INT(10) UNSIGNED NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `statement_date` DATE DEFAULT NULL,
    `file_type` ENUM('csv', 'excel', 'pdf') NOT NULL,
    `statement_from_date` DATE DEFAULT NULL,
    `statement_to_date` DATE DEFAULT NULL,
    `total_transactions` INT(11) DEFAULT 0,
    `total_credits` DECIMAL(15, 2) DEFAULT 0.00,
    `total_debits` DECIMAL(15, 2) DEFAULT 0.00,
    `mapped_count` INT(11) DEFAULT 0,
    `unmapped_count` INT(11) DEFAULT 0,
    `status` ENUM(
        'uploaded',
        'parsing',
        'parsed',
        'mapping',
        'completed',
        'failed'
    ) DEFAULT 'uploaded',
    `error_message` TEXT DEFAULT NULL,
    `imported_by` INT(10) UNSIGNED DEFAULT NULL,
    `imported_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_import_code` (`import_code`),
    KEY `idx_bank_account_id` (`bank_account_id`),
    KEY `idx_status` (`status`),
    CONSTRAINT `bank_statement_imports_ibfk_1` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: bank_balance_history
-- ============================================================
CREATE TABLE `bank_balance_history` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `date` DATE NOT NULL,
    `balance` DECIMAL(15, 2) NOT NULL,
    `is_deleted` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ============================================================
-- TABLE: bank_transactions
-- ============================================================
CREATE TABLE `bank_transactions` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `import_id` INT(10) UNSIGNED NOT NULL,
    `bank_account_id` INT(10) UNSIGNED NOT NULL,
    `transaction_date` DATE NOT NULL,
    `value_date` DATE DEFAULT NULL,
    `transaction_type` ENUM('credit', 'debit') NOT NULL,
    `amount` DECIMAL(15, 2) NOT NULL,
    `credit_amount` DECIMAL(15, 2) DEFAULT 0.00,
    `debit_amount` DECIMAL(15, 2) DEFAULT 0.00,
    `running_balance` DECIMAL(15, 2) DEFAULT 0.00,
    `balance_after` DECIMAL(15, 2) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `description2` VARCHAR(255) DEFAULT NULL,
    `reference_number` VARCHAR(100) DEFAULT NULL,
    `utr_number` VARCHAR(50) DEFAULT NULL,
    `cheque_number` VARCHAR(20) DEFAULT NULL,
    `mapping_status` ENUM(
        'unmapped',
        'partial',
        'mapped',
        'ignored',
        'split'
    ) DEFAULT 'unmapped',
    `mapped_amount` DECIMAL(15, 2) DEFAULT 0.00,
    `unmapped_amount` DECIMAL(15, 2) DEFAULT NULL,
    `detected_member_id` INT(10) UNSIGNED DEFAULT NULL COMMENT 'Auto-detected member',
    `detection_confidence` DECIMAL(5, 2) DEFAULT NULL COMMENT 'Confidence score 0-100',
    `paid_by_member_id` INT(10) UNSIGNED DEFAULT NULL COMMENT 'Member who made the payment',
    `paid_for_member_id` INT(10) UNSIGNED DEFAULT NULL COMMENT 'Member who received the payment',
    `transaction_category` VARCHAR(50) DEFAULT NULL,
    `mapping_remarks` TEXT DEFAULT NULL,
    `mapped_by` INT(11) DEFAULT NULL,
    `mapped_at` DATETIME DEFAULT NULL,
    `import_code` VARCHAR(50) DEFAULT NULL,
    `related_type` VARCHAR(50) DEFAULT NULL,
    `related_id` INT(11) DEFAULT NULL,
    `updated_by` INT(10) UNSIGNED DEFAULT NULL,
    `remarks` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_utr_unique` (`utr_number`),
    KEY `idx_import_id` (`import_id`),
    KEY `idx_bank_account_id` (`bank_account_id`),
    KEY `idx_transaction_date` (`transaction_date`),
    KEY `idx_mapping_status` (`mapping_status`),
    KEY `idx_utr_number` (`utr_number`),
    KEY `idx_paid_by_member` (`paid_by_member_id`),
    KEY `idx_paid_for_member` (`paid_for_member_id`),
    CONSTRAINT `bank_transactions_ibfk_1` FOREIGN KEY (`import_id`) REFERENCES `bank_statement_imports` (`id`),
    CONSTRAINT `bank_transactions_ibfk_2` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: bank_imports  (alternative import tracking)
-- ============================================================
CREATE TABLE `bank_imports` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `import_code` VARCHAR(50) NOT NULL,
    `bank_name` VARCHAR(100) DEFAULT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `total_records` INT(11) DEFAULT 0,
    `mapped_records` INT(11) DEFAULT 0,
    `processed_records` INT(11) DEFAULT 0,
    `import_status` ENUM(
        'pending',
        'partial',
        'completed'
    ) DEFAULT 'pending',
    `imported_by` INT(11) NOT NULL,
    `imported_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `completed_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `import_code` (`import_code`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: chart_of_accounts
-- ============================================================
CREATE TABLE `chart_of_accounts` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `account_code` VARCHAR(20) NOT NULL,
    `account_name` VARCHAR(100) NOT NULL,
    `account_type` ENUM(
        'asset',
        'liability',
        'income',
        'expense',
        'equity'
    ) NOT NULL,
    `parent_id` INT(10) UNSIGNED DEFAULT NULL,
    `is_group` TINYINT(1) DEFAULT 0,
    `is_system` TINYINT(1) DEFAULT 0 COMMENT 'System accounts cannot be deleted',
    `opening_balance` DECIMAL(15, 2) DEFAULT 0.00,
    `current_balance` DECIMAL(15, 2) DEFAULT 0.00,
    `is_active` TINYINT(1) DEFAULT 1,
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_account_code` (`account_code`),
    KEY `idx_account_type` (`account_type`),
    KEY `idx_parent_id` (`parent_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: savings_schemes
-- ============================================================
CREATE TABLE `savings_schemes` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `scheme_code` VARCHAR(20) NOT NULL,
    `scheme_name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `min_deposit` DECIMAL(15, 2) DEFAULT 0.00,
    `deposit_frequency` ENUM(
        'daily',
        'weekly',
        'monthly',
        'quarterly',
        'yearly',
        'onetime'
    ) DEFAULT 'monthly',
    `lock_in_period` INT(10) UNSIGNED DEFAULT 0,
    `penalty_rate` DECIMAL(5, 2) DEFAULT 0.00,
    `maturity_bonus` DECIMAL(5, 2) DEFAULT 0.00,
    `monthly_amount` DECIMAL(15, 2) NOT NULL,
    `duration_months` INT(10) UNSIGNED DEFAULT NULL COMMENT 'NULL for indefinite',
    `interest_rate` DECIMAL(5, 2) DEFAULT 0.00 COMMENT 'Annual interest rate',
    `late_fine_type` ENUM(
        'fixed',
        'percentage',
        'per_day'
    ) DEFAULT 'fixed',
    `late_fine_value` DECIMAL(10, 2) DEFAULT 0.00,
    `grace_period_days` INT(11) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `is_default` TINYINT(1) DEFAULT 0 COMMENT 'Default scheme for new member auto-enrollment',
    `created_by` INT(10) UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_scheme_code` (`scheme_code`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: fine_rules
-- ============================================================
CREATE TABLE `fine_rules` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `rule_code` VARCHAR(20) NOT NULL,
    `rule_name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `applies_to` ENUM('savings', 'loan', 'both') DEFAULT 'both',
    `fine_type` ENUM(
        'fixed',
        'percentage',
        'per_day',
        'slab'
    ) NOT NULL,
    `calculation_type` ENUM(
        'fixed',
        'percentage',
        'per_day',
        'slab'
    ) NOT NULL DEFAULT 'fixed',
    `fine_value` DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Fixed amount or percentage',
    `per_day_amount` DECIMAL(10, 2) DEFAULT 0.00,
    `max_fine_amount` DECIMAL(15, 2) DEFAULT NULL COMMENT 'Cap on fine',
    `grace_period_days` INT(11) DEFAULT 0,
    `slab_config` LONGTEXT DEFAULT NULL COMMENT 'For slab-based fines JSON',
    `is_active` TINYINT(1) DEFAULT 1,
    `effective_from` DATE NOT NULL,
    `effective_to` DATE DEFAULT NULL,
    `created_by` INT(10) UNSIGNED DEFAULT NULL,
    `updated_by` INT(10) UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_rule_code` (`rule_code`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: loan_products
-- ============================================================
CREATE TABLE `loan_products` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_code` VARCHAR(20) NOT NULL,
    `product_name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `min_amount` DECIMAL(15, 2) NOT NULL,
    `max_amount` DECIMAL(15, 2) NOT NULL,
    `min_tenure_months` INT(11) NOT NULL,
    `max_tenure_months` INT(11) NOT NULL,
    `interest_rate` DECIMAL(5, 2) NOT NULL COMMENT 'Default annual interest rate',
    `min_interest_rate` DECIMAL(5, 2) DEFAULT 0.00,
    `max_interest_rate` DECIMAL(5, 2) DEFAULT 50.00,
    `default_interest_rate` DECIMAL(5, 2) DEFAULT NULL,
    `interest_type` ENUM(
        'flat',
        'reducing',
        'reducing_monthly'
    ) DEFAULT 'reducing',
    `processing_fee_type` ENUM('fixed', 'percentage') DEFAULT 'percentage',
    `processing_fee_value` DECIMAL(10, 2) DEFAULT 1.00,
    `late_fee_type` ENUM(
        'fixed',
        'percentage',
        'per_day',
        'fixed_plus_daily'
    ) DEFAULT 'fixed',
    `late_fee_value` DECIMAL(10, 2) DEFAULT 0.00,
    `late_fee_per_day` DECIMAL(10, 2) DEFAULT 0.00,
    `grace_period_days` INT(11) DEFAULT 5,
    `prepayment_allowed` TINYINT(1) DEFAULT 1,
    `prepayment_penalty_percent` DECIMAL(5, 2) DEFAULT 0.00,
    `min_guarantors` INT(11) DEFAULT 2,
    `max_guarantors` INT(11) DEFAULT 3,
    `required_savings_months` INT(11) DEFAULT 6 COMMENT 'Min months of savings required',
    `max_loan_to_savings_ratio` DECIMAL(5, 2) DEFAULT 3.00 COMMENT 'Max loan = savings * ratio',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_by` INT(10) UNSIGNED DEFAULT NULL,
    `updated_by` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_product_code` (`product_code`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: members  (main member master)
-- ============================================================
CREATE TABLE `members` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `member_code` VARCHAR(20) NOT NULL COMMENT 'Unique Member ID like MEM-0001',
    `first_name` VARCHAR(50) NOT NULL,
    `middle_name` VARCHAR(50) DEFAULT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `father_name` VARCHAR(100) DEFAULT NULL,
    `date_of_birth` DATE DEFAULT NULL,
    `gender` ENUM('male', 'female', 'other') DEFAULT NULL,
    `marital_status` ENUM(
        'single',
        'married',
        'divorced',
        'widowed'
    ) DEFAULT NULL,
    `occupation` VARCHAR(100) DEFAULT NULL,
    `monthly_income` DECIMAL(12, 2) DEFAULT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `alternate_phone` VARCHAR(20) DEFAULT NULL,
    `address_line1` VARCHAR(255) DEFAULT NULL,
    `address_line2` VARCHAR(255) DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `state` VARCHAR(100) DEFAULT NULL,
    `pincode` VARCHAR(10) DEFAULT NULL,
    `photo` VARCHAR(255) DEFAULT NULL,
    `profile_image` VARCHAR(255) DEFAULT NULL,
    `aadhaar_number` VARCHAR(12) DEFAULT NULL,
    `pan_number` VARCHAR(10) DEFAULT NULL,
    `voter_id` VARCHAR(20) DEFAULT NULL,
    `id_proof_type` VARCHAR(50) DEFAULT NULL,
    `id_proof_number` VARCHAR(50) DEFAULT NULL,
    `aadhaar_doc` VARCHAR(255) DEFAULT NULL,
    `pan_doc` VARCHAR(255) DEFAULT NULL,
    `address_proof_doc` VARCHAR(255) DEFAULT NULL,
    `kyc_verified` TINYINT(1) DEFAULT 0,
    `kyc_verified_at` TIMESTAMP NULL DEFAULT NULL,
    `kyc_verified_by` INT(10) UNSIGNED DEFAULT NULL,
    `bank_name` VARCHAR(100) DEFAULT NULL,
    `bank_branch` VARCHAR(100) DEFAULT NULL,
    `account_number` VARCHAR(30) DEFAULT NULL,
    `bank_account_number` VARCHAR(30) DEFAULT NULL,
    `ifsc_code` VARCHAR(15) DEFAULT NULL,
    `bank_ifsc` VARCHAR(15) DEFAULT NULL,
    `account_holder_name` VARCHAR(100) DEFAULT NULL,
    `join_date` DATE NOT NULL,
    `membership_type` ENUM(
        'regular',
        'premium',
        'founder'
    ) DEFAULT 'regular',
    `member_level` VARCHAR(50) DEFAULT NULL COMMENT 'Extended membership level label',
    `opening_balance` DECIMAL(15, 2) DEFAULT 0.00 COMMENT 'Initial ledger balance',
    `opening_balance_type` ENUM('credit', 'debit') DEFAULT 'credit',
    `status` ENUM(
        'active',
        'inactive',
        'blocked',
        'suspended'
    ) DEFAULT 'active',
    `status_reason` VARCHAR(255) DEFAULT NULL,
    `status_changed_at` TIMESTAMP NULL DEFAULT NULL,
    `status_changed_by` INT(10) UNSIGNED DEFAULT NULL,
    `nominee_name` VARCHAR(100) DEFAULT NULL,
    `nominee_relation` VARCHAR(50) DEFAULT NULL,
    `nominee_relationship` VARCHAR(50) DEFAULT NULL,
    `nominee_phone` VARCHAR(20) DEFAULT NULL,
    `nominee_aadhaar` VARCHAR(12) DEFAULT NULL,
    `max_guarantee_amount` DECIMAL(15, 2) DEFAULT 100000.00,
    `max_guarantee_count` INT(11) DEFAULT 3,
    `password` VARCHAR(255) DEFAULT NULL,
    `last_login` TIMESTAMP NULL DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_by` INT(10) UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Soft delete',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_member_code` (`member_code`),
    KEY `idx_phone` (`phone`),
    UNIQUE KEY `idx_aadhaar` (`aadhaar_number`),
    UNIQUE KEY `idx_pan` (`pan_number`),
    KEY `idx_status` (`status`),
    KEY `idx_join_date` (`join_date`),
    KEY `idx_deleted_at` (`deleted_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: member_code_sequence
-- ============================================================
CREATE TABLE `member_code_sequence` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `prefix` VARCHAR(10) DEFAULT 'MEM',
    `current_number` INT(10) UNSIGNED DEFAULT 0,
    `year` YEAR DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: member_ledger
-- ============================================================
CREATE TABLE `member_ledger` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `member_id` INT(10) UNSIGNED NOT NULL,
    `transaction_date` DATE NOT NULL,
    `transaction_type` VARCHAR(50) NOT NULL COMMENT 'savings_deposit, loan_disbursement, etc.',
    `reference_type` VARCHAR(50) DEFAULT NULL,
    `reference_id` INT(10) UNSIGNED DEFAULT NULL,
    `debit_amount` DECIMAL(15, 2) DEFAULT 0.00,
    `credit_amount` DECIMAL(15, 2) DEFAULT 0.00,
    `balance_after` DECIMAL(15, 2) NOT NULL,
    `narration` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_member_id` (`member_id`),
    KEY `idx_transaction_date` (`transaction_date`),
    KEY `idx_reference` (
        `reference_type`,
        `reference_id`
    ),
    CONSTRAINT `member_ledger_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: general_ledger  (double-entry accounting)
-- ============================================================
CREATE TABLE `general_ledger` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `voucher_number` VARCHAR(30) NOT NULL,
    `voucher_date` DATE NOT NULL,
    `voucher_type` ENUM(
        'receipt',
        'payment',
        'journal',
        'contra'
    ) NOT NULL,
    `account_id` INT(10) UNSIGNED NOT NULL,
    `debit_amount` DECIMAL(15, 2) DEFAULT 0.00,
    `credit_amount` DECIMAL(15, 2) DEFAULT 0.00,
    `balance_after` DECIMAL(15, 2) NOT NULL,
    `narration` VARCHAR(255) DEFAULT NULL,
    `reference_type` VARCHAR(50) DEFAULT NULL,
    `reference_id` INT(10) UNSIGNED DEFAULT NULL,
    `member_id` INT(10) UNSIGNED DEFAULT NULL,
    `financial_year_id` INT(10) UNSIGNED DEFAULT NULL,
    `is_posted` TINYINT(1) DEFAULT 1,
    `created_by` INT(10) UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_voucher_number` (`voucher_number`),
    KEY `idx_voucher_date` (`voucher_date`),
    KEY `idx_account_id` (`account_id`),
    KEY `idx_reference` (
        `reference_type`,
        `reference_id`
    ),
    CONSTRAINT `general_ledger_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: savings_accounts
-- ============================================================
CREATE TABLE `savings_accounts` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `account_number` VARCHAR(20) NOT NULL,
    `member_id` INT(10) UNSIGNED NOT NULL,
    `scheme_id` INT(10) UNSIGNED NOT NULL,
    `monthly_amount` DECIMAL(15, 2) NOT NULL COMMENT 'Can override scheme default',
    `start_date` DATE NOT NULL,
    `maturity_date` DATE DEFAULT NULL,
    `total_deposited` DECIMAL(15, 2) DEFAULT 0.00,
    `total_interest_earned` DECIMAL(15, 2) DEFAULT 0.00,
    `total_fines_paid` DECIMAL(15, 2) DEFAULT 0.00,
    `current_balance` DECIMAL(15, 2) DEFAULT 0.00,
    `status` ENUM(
        'active',
        'matured',
        'closed',
        'suspended'
    ) DEFAULT 'active',
    `closed_at` TIMESTAMP NULL DEFAULT NULL,
    `closed_reason` VARCHAR(255) DEFAULT NULL,
    `created_by` INT(10) UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_account_number` (`account_number`),
    KEY `idx_member_id` (`member_id`),
    KEY `idx_scheme_id` (`scheme_id`),
    KEY `idx_status` (`status`),
    CONSTRAINT `savings_accounts_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
    CONSTRAINT `savings_accounts_ibfk_2` FOREIGN KEY (`scheme_id`) REFERENCES `savings_schemes` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: savings_schedule
-- ============================================================
CREATE TABLE `savings_schedule` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `savings_account_id` INT(10) UNSIGNED NOT NULL,
    `due_month` DATE NOT NULL COMMENT 'First day of month',
    `due_amount` DECIMAL(15, 2) NOT NULL,
    `paid_amount` DECIMAL(15, 2) DEFAULT 0.00,
    `fine_amount` DECIMAL(15, 2) DEFAULT 0.00,
    `fine_paid` DECIMAL(15, 2) DEFAULT 0.00,
    `due_date` DATE NOT NULL,
    `paid_date` DATE DEFAULT NULL,
    `status` ENUM(
        'pending',
        'partial',
        'paid',
        'overdue',
        'waived'
    ) DEFAULT 'pending',
    `is_late` TINYINT(1) DEFAULT 0,
    `days_late` INT(11) DEFAULT 0,
    `remarks` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_account_month` (
        `savings_account_id`,
        `due_month`
    ),
    KEY `idx_due_date` (`due_date`),
    KEY `idx_status` (`status`),
    CONSTRAINT `savings_schedule_ibfk_1` FOREIGN KEY (`savings_account_id`) REFERENCES `savings_accounts` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: savings_transactions
-- ============================================================
CREATE TABLE `savings_transactions` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `transaction_code` VARCHAR(30) NOT NULL,
    `savings_account_id` INT(10) UNSIGNED NOT NULL,
    `schedule_id` INT(10) UNSIGNED DEFAULT NULL,
    `transaction_type` ENUM(
        'deposit',
        'withdrawal',
        'interest_credit',
        'fine',
        'fine_waiver',
        'adjustment',
        'opening_balance'
    ) NOT NULL,
    `amount` DECIMAL(15, 2) NOT NULL,
    `balance_after` DECIMAL(15, 2) NOT NULL,
    `payment_mode` ENUM(
        'cash',
        'bank_transfer',
        'cheque',
        'upi',
        'auto',
        'adjustment'
    ) DEFAULT 'cash',
    `reference_number` VARCHAR(50) DEFAULT NULL,
    `transaction_date` DATE NOT NULL,
    `for_month` DATE DEFAULT NULL COMMENT 'Which month this payment is for',
    `narration` VARCHAR(255) DEFAULT NULL,
    `receipt_number` VARCHAR(30) DEFAULT NULL,
    `bank_transaction_id` INT(10) UNSIGNED DEFAULT NULL COMMENT 'Link to imported bank transaction',
    `is_reversed` TINYINT(1) DEFAULT 0,
    `reversed_at` TIMESTAMP NULL DEFAULT NULL,
    `reversed_by` INT(10) UNSIGNED DEFAULT NULL,
    `reversal_reason` VARCHAR(255) DEFAULT NULL,
    `created_by` INT(10) UNSIGNED DEFAULT NULL,
    `updated_by` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_transaction_code` (`transaction_code`),
    KEY `idx_savings_account_id` (`savings_account_id`),
    KEY `idx_transaction_date` (`transaction_date`),
    KEY `idx_transaction_type` (`transaction_type`),
    KEY `schedule_id` (`schedule_id`),
    CONSTRAINT `savings_transactions_ibfk_1` FOREIGN KEY (`savings_account_id`) REFERENCES `savings_accounts` (`id`),
    CONSTRAINT `savings_transactions_ibfk_2` FOREIGN KEY (`schedule_id`) REFERENCES `savings_schedule` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: loan_applications
-- ============================================================
CREATE TABLE `loan_applications` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `application_number` VARCHAR(30) NOT NULL,
    `member_id` INT(10) UNSIGNED NOT NULL,
    `loan_product_id` INT(10) UNSIGNED NOT NULL,
    `requested_amount` DECIMAL(15, 2) NOT NULL,
    `requested_tenure_months` INT(11) NOT NULL,
    `requested_interest_rate` DECIMAL(5, 2) DEFAULT NULL,
    `purpose` VARCHAR(255) NOT NULL,
    `purpose_details` TEXT DEFAULT NULL,
    `approved_amount` DECIMAL(15, 2) DEFAULT NULL,
    `approved_tenure_months` INT(11) DEFAULT NULL,
    `approved_interest_rate` DECIMAL(5, 2) DEFAULT NULL,
    `assigned_interest_rate` DECIMAL(5, 2) DEFAULT NULL COMMENT 'Rate assigned by admin during approval',
    `revision_remarks` TEXT DEFAULT NULL,
    `revised_at` TIMESTAMP NULL DEFAULT NULL,
    `revised_by` INT(10) UNSIGNED DEFAULT NULL,
    `status` ENUM(
        'draft',
        'pending',
        'under_review',
        'guarantor_pending',
        'admin_approved',
        'member_review',
        'member_approved',
        'disbursed',
        'rejected',
        'cancelled',
        'expired'
    ) DEFAULT 'draft',
    `status_remarks` VARCHAR(255) DEFAULT NULL,
    `admin_approved_at` TIMESTAMP NULL DEFAULT NULL,
    `admin_approved_by` INT(10) UNSIGNED DEFAULT NULL,
    `approved_by` INT(11) DEFAULT NULL,
    `member_approved_at` TIMESTAMP NULL DEFAULT NULL,
    `rejection_reason` TEXT DEFAULT NULL,
    `rejected_at` TIMESTAMP NULL DEFAULT NULL,
    `rejected_by` INT(10) UNSIGNED DEFAULT NULL,
    `member_savings_balance` DECIMAL(15, 2) DEFAULT NULL,
    `member_existing_loans` INT(11) DEFAULT 0,
    `member_existing_loan_balance` DECIMAL(15, 2) DEFAULT 0.00,
    `eligibility_score` INT(11) DEFAULT NULL,
    `application_date` DATE NOT NULL,
    `expiry_date` DATE DEFAULT NULL,
    `documents` LONGTEXT DEFAULT NULL COMMENT 'Uploaded documents JSON',
    `disbursed_by` INT(11) DEFAULT NULL,
    `created_by` INT(10) UNSIGNED DEFAULT NULL,
    `updated_by` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_application_number` (`application_number`),
    KEY `idx_member_id` (`member_id`),
    KEY `idx_loan_product_id` (`loan_product_id`),
    KEY `idx_status` (`status`),
    KEY `idx_application_date` (`application_date`),
    CONSTRAINT `loan_applications_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
    CONSTRAINT `loan_applications_ibfk_2` FOREIGN KEY (`loan_product_id`) REFERENCES `loan_products` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: loan_guarantors
-- ============================================================
CREATE TABLE `loan_guarantors` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `loan_application_id` INT(10) UNSIGNED NOT NULL,
    `loan_id` INT(10) UNSIGNED DEFAULT NULL COMMENT 'Set after loan is disbursed',
    `guarantor_member_id` INT(10) UNSIGNED NOT NULL,
    `guarantee_amount` DECIMAL(15, 2) NOT NULL,
    `relationship` VARCHAR(50) DEFAULT NULL,
    `consent_status` ENUM(
        'pending',
        'accepted',
        'rejected',
        'withdrawn'
    ) DEFAULT 'pending',
    `consent_date` TIMESTAMP NULL DEFAULT NULL,
    `consent_ip` VARCHAR(45) DEFAULT NULL,
    `consent_remarks` VARCHAR(255) DEFAULT NULL,
    `rejection_reason` VARCHAR(255) DEFAULT NULL,
    `consent_token` VARCHAR(64) DEFAULT NULL,
    `is_released` TINYINT(1) DEFAULT 0,
    `released_at` TIMESTAMP NULL DEFAULT NULL,
    `released_by` INT(10) UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_application_guarantor` (
        `loan_application_id`,
        `guarantor_member_id`
    ),
    KEY `idx_guarantor_member` (`guarantor_member_id`),
    KEY `idx_loan_id` (`loan_id`),
    KEY `idx_consent_status` (`consent_status`),
    CONSTRAINT `loan_guarantors_ibfk_1` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`),
    CONSTRAINT `loan_guarantors_ibfk_2` FOREIGN KEY (`guarantor_member_id`) REFERENCES `members` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: loans  (disbursed loans)
-- ============================================================
CREATE TABLE `loans` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `loan_number` VARCHAR(30) NOT NULL,
    `loan_application_id` INT(10) UNSIGNED NOT NULL,
    `member_id` INT(10) UNSIGNED NOT NULL,
    `loan_product_id` INT(10) UNSIGNED NOT NULL,
    `principal_amount` DECIMAL(15, 2) NOT NULL,
    `interest_rate` DECIMAL(5, 2) NOT NULL,
    `interest_type` ENUM(
        'flat',
        'reducing',
        'reducing_monthly'
    ) NOT NULL,
    `tenure_months` INT(11) NOT NULL,
    `emi_amount` DECIMAL(15, 2) NOT NULL,
    `total_interest` DECIMAL(15, 2) NOT NULL,
    `total_payable` DECIMAL(15, 2) NOT NULL,
    `processing_fee` DECIMAL(15, 2) DEFAULT 0.00,
    `net_disbursement` DECIMAL(15, 2) NOT NULL COMMENT 'principal minus processing_fee',
    `outstanding_principal` DECIMAL(15, 2) NOT NULL,
    `outstanding_interest` DECIMAL(15, 2) NOT NULL,
    `outstanding_fine` DECIMAL(15, 2) DEFAULT 0.00,
    `total_amount_paid` DECIMAL(15, 2) DEFAULT 0.00,
    `total_principal_paid` DECIMAL(15, 2) DEFAULT 0.00,
    `total_interest_paid` DECIMAL(15, 2) DEFAULT 0.00,
    `total_fine_paid` DECIMAL(15, 2) DEFAULT 0.00,
    `disbursement_date` DATE NOT NULL,
    `first_emi_date` DATE NOT NULL,
    `last_emi_date` DATE NOT NULL,
    `closure_date` DATE DEFAULT NULL,
    `status` ENUM(
        'active',
        'closed',
        'foreclosed',
        'written_off',
        'npa'
    ) DEFAULT 'active',
    `closure_type` ENUM(
        'regular',
        'foreclosure',
        'write_off',
        'settlement'
    ) DEFAULT NULL,
    `closure_remarks` VARCHAR(255) DEFAULT NULL,
    `closed_by` INT(10) UNSIGNED DEFAULT NULL,
    `is_npa` TINYINT(1) DEFAULT 0,
    `npa_date` DATE DEFAULT NULL,
    `npa_category` ENUM(
        'substandard',
        'doubtful',
        'loss'
    ) DEFAULT NULL,
    `days_overdue` INT(11) DEFAULT 0,
    `disbursement_mode` ENUM(
        'cash',
        'bank_transfer',
        'cheque'
    ) DEFAULT 'bank_transfer',
    `disbursement_reference` VARCHAR(50) DEFAULT NULL,
    `disbursement_bank_account` VARCHAR(50) DEFAULT NULL,
    `disbursed_by` INT(10) UNSIGNED DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `updated_by` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_loan_number` (`loan_number`),
    KEY `idx_member_id` (`member_id`),
    KEY `idx_loan_application_id` (`loan_application_id`),
    KEY `idx_loan_product_id` (`loan_product_id`),
    KEY `idx_status` (`status`),
    KEY `idx_disbursement_date` (`disbursement_date`),
    KEY `idx_is_npa` (`is_npa`),
    CONSTRAINT `loans_ibfk_1` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`),
    CONSTRAINT `loans_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
    CONSTRAINT `loans_ibfk_3` FOREIGN KEY (`loan_product_id`) REFERENCES `loan_products` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: loan_installments  (EMI schedule)
-- ============================================================
CREATE TABLE `loan_installments` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `loan_id` INT(10) UNSIGNED NOT NULL,
    `installment_number` INT(11) NOT NULL,
    `due_date` DATE NOT NULL,
    `principal_amount` DECIMAL(15, 2) NOT NULL,
    `interest_amount` DECIMAL(15, 2) NOT NULL,
    `emi_amount` DECIMAL(15, 2) NOT NULL,
    `outstanding_principal_before` DECIMAL(15, 2) NOT NULL,
    `outstanding_principal_after` DECIMAL(15, 2) NOT NULL,
    `principal_paid` DECIMAL(15, 2) DEFAULT 0.00,
    `interest_paid` DECIMAL(15, 2) DEFAULT 0.00,
    `fine_amount` DECIMAL(15, 2) DEFAULT 0.00,
    `fine_paid` DECIMAL(15, 2) DEFAULT 0.00,
    `total_paid` DECIMAL(15, 2) DEFAULT 0.00,
    `status` ENUM(
        'upcoming',
        'pending',
        'partial',
        'paid',
        'overdue',
        'skipped',
        'interest_only',
        'waived'
    ) DEFAULT 'upcoming',
    `paid_date` DATE DEFAULT NULL,
    `is_late` TINYINT(1) DEFAULT 0,
    `days_late` INT(11) DEFAULT 0,
    `is_skipped` TINYINT(1) DEFAULT 0,
    `skip_reason` VARCHAR(255) DEFAULT NULL,
    `skipped_by` INT(10) UNSIGNED DEFAULT NULL,
    `is_adjusted` TINYINT(1) DEFAULT 0,
    `adjustment_remarks` VARCHAR(255) DEFAULT NULL,
    `remarks` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_loan_installment` (
        `loan_id`,
        `installment_number`
    ),
    KEY `idx_due_date` (`due_date`),
    KEY `idx_status` (`status`),
    CONSTRAINT `loan_installments_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: loan_payments
-- ============================================================
CREATE TABLE `loan_payments` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `payment_code` VARCHAR(30) NOT NULL,
    `loan_id` INT(10) UNSIGNED NOT NULL,
    `installment_id` INT(10) UNSIGNED DEFAULT NULL,
    `payment_type` ENUM(
        'emi',
        'part_payment',
        'advance_payment',
        'interest_only',
        'fine_payment',
        'foreclosure',
        'adjustment',
        'reversal'
    ) NOT NULL,
    `payment_date` DATE NOT NULL,
    `total_amount` DECIMAL(15, 2) NOT NULL,
    `principal_component` DECIMAL(15, 2) DEFAULT 0.00,
    `interest_component` DECIMAL(15, 2) DEFAULT 0.00,
    `fine_component` DECIMAL(15, 2) DEFAULT 0.00,
    `excess_amount` DECIMAL(15, 2) DEFAULT 0.00,
    `outstanding_principal_after` DECIMAL(15, 2) NOT NULL,
    `outstanding_interest_after` DECIMAL(15, 2) NOT NULL,
    `payment_mode` ENUM(
        'cash',
        'bank_transfer',
        'cheque',
        'upi',
        'auto_debit',
        'adjustment'
    ) DEFAULT 'cash',
    `reference_number` VARCHAR(50) DEFAULT NULL,
    `receipt_number` VARCHAR(30) DEFAULT NULL,
    `cheque_number` VARCHAR(20) DEFAULT NULL,
    `cheque_date` DATE DEFAULT NULL,
    `bank_name` VARCHAR(100) DEFAULT NULL,
    `bank_transaction_id` INT(10) UNSIGNED DEFAULT NULL,
    `is_reversed` TINYINT(1) DEFAULT 0,
    `reversed_at` TIMESTAMP NULL DEFAULT NULL,
    `reversed_by` INT(10) UNSIGNED DEFAULT NULL,
    `reversal_reason` VARCHAR(255) DEFAULT NULL,
    `reversal_payment_id` INT(10) UNSIGNED DEFAULT NULL,
    `narration` VARCHAR(255) DEFAULT NULL,
    `created_by` INT(10) UNSIGNED DEFAULT NULL,
    `updated_by` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_payment_code` (`payment_code`),
    KEY `idx_loan_id` (`loan_id`),
    KEY `idx_installment_id` (`installment_id`),
    KEY `idx_payment_date` (`payment_date`),
    KEY `idx_payment_type` (`payment_type`),
    CONSTRAINT `loan_payments_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`),
    CONSTRAINT `loan_payments_ibfk_2` FOREIGN KEY (`installment_id`) REFERENCES `loan_installments` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: loan_foreclosure_requests
-- ============================================================
CREATE TABLE `loan_foreclosure_requests` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `loan_id` INT(10) UNSIGNED NOT NULL,
    `member_id` INT(10) UNSIGNED NOT NULL,
    `foreclosure_amount` DECIMAL(15, 2) NOT NULL,
    `reason` TEXT NOT NULL,
    `settlement_date` DATE NOT NULL,
    `status` ENUM(
        'pending',
        'approved',
        'rejected'
    ) DEFAULT 'pending',
    `requested_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `processed_by` INT(10) UNSIGNED DEFAULT NULL,
    `processed_at` TIMESTAMP NULL DEFAULT NULL,
    `admin_comments` TEXT DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_loan_id` (`loan_id`),
    KEY `idx_member_id` (`member_id`),
    KEY `idx_status` (`status`),
    CONSTRAINT `loan_foreclosure_requests_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
    CONSTRAINT `loan_foreclosure_requests_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
    CONSTRAINT `loan_foreclosure_requests_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: loan_transactions  (legacy)
-- ============================================================
CREATE TABLE `loan_transactions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `reason` TEXT DEFAULT NULL,
    `total_loan` DECIMAL(10, 2) NOT NULL,
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `monthly_emi` DECIMAL(10, 2) DEFAULT NULL,
    `created_by` VARCHAR(255) DEFAULT NULL,
    `updated_by` VARCHAR(255) DEFAULT NULL,
    `interest` DECIMAL(5, 2) DEFAULT NULL,
    `period` INT(11) DEFAULT NULL,
    `unit` VARCHAR(20) DEFAULT NULL,
    `processing_fee` DECIMAL(10, 2) DEFAULT NULL,
    `total_repayment` DECIMAL(10, 2) DEFAULT NULL,
    `emi_details` LONGTEXT DEFAULT NULL COMMENT 'EMI details JSON',
    `is_deleted` TINYINT(1) DEFAULT 0,
    `member_id` VARCHAR(50) DEFAULT NULL,
    `loan_type` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `member_id` (`member_id`),
    CONSTRAINT `loan_transactions_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `member_details` (`member_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ============================================================
-- TABLE: loan_transaction_details  (legacy)
-- ============================================================
CREATE TABLE `loan_transaction_details` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `loan_id` INT(11) DEFAULT NULL,
    `period` VARCHAR(50) DEFAULT NULL,
    `unit` VARCHAR(20) DEFAULT NULL,
    `emi` DECIMAL(10, 2) DEFAULT NULL,
    `principal` DECIMAL(10, 2) DEFAULT NULL,
    `balance` DECIMAL(10, 2) DEFAULT NULL,
    `interest` DECIMAL(10, 2) DEFAULT NULL,
    `fee_fine` DECIMAL(10, 2) DEFAULT NULL,
    `excess_fee` DECIMAL(10, 2) DEFAULT NULL,
    `only_interest` DECIMAL(10, 2) DEFAULT NULL,
    `remarks` TEXT DEFAULT NULL,
    `admin_remarks` TEXT DEFAULT NULL,
    `date` DATE DEFAULT NULL,
    `loan_flag` INT(11) DEFAULT 0,
    `is_deleted` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `loan_id` (`loan_id`),
    CONSTRAINT `loan_transaction_details_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loan_transactions` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ============================================================
-- TABLE: fines
-- ============================================================
CREATE TABLE `fines` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `fine_code` VARCHAR(30) NOT NULL,
    `member_id` INT(10) UNSIGNED NOT NULL,
    `fine_type` ENUM(
        'savings_late',
        'loan_late',
        'bounced_cheque',
        'other'
    ) NOT NULL,
    `related_type` ENUM(
        'savings_schedule',
        'loan_installment',
        'other'
    ) NOT NULL,
    `related_id` INT(10) UNSIGNED NOT NULL,
    `fine_rule_id` INT(10) UNSIGNED DEFAULT NULL,
    `fine_date` DATE NOT NULL,
    `due_date` DATE NOT NULL,
    `days_late` INT(11) NOT NULL,
    `fine_amount` DECIMAL(15, 2) NOT NULL,
    `paid_amount` DECIMAL(15, 2) DEFAULT 0.00,
    `waived_amount` DECIMAL(15, 2) DEFAULT 0.00,
    `balance_amount` DECIMAL(15, 2) NOT NULL,
    `status` ENUM(
        'pending',
        'partial',
        'paid',
        'waived',
        'cancelled'
    ) DEFAULT 'pending',
    `payment_date` DATE DEFAULT NULL,
    `payment_mode` ENUM(
        'cash',
        'cheque',
        'bank_transfer',
        'online'
    ) DEFAULT NULL,
    `payment_reference` VARCHAR(100) DEFAULT NULL,
    `received_by` INT(10) UNSIGNED DEFAULT NULL,
    `waiver_requested_by` INT(10) UNSIGNED DEFAULT NULL,
    `waiver_requested_at` TIMESTAMP NULL DEFAULT NULL,
    `waiver_requested_amount` DECIMAL(15, 2) DEFAULT NULL,
    `waived_by` INT(10) UNSIGNED DEFAULT NULL,
    `waived_at` TIMESTAMP NULL DEFAULT NULL,
    `waiver_reason` VARCHAR(255) DEFAULT NULL,
    `waiver_approved_by` INT(10) UNSIGNED DEFAULT NULL,
    `waiver_approved_at` TIMESTAMP NULL DEFAULT NULL,
    `waiver_denied_by` INT(10) UNSIGNED DEFAULT NULL,
    `waiver_denied_at` TIMESTAMP NULL DEFAULT NULL,
    `waiver_denied_reason` VARCHAR(255) DEFAULT NULL,
    `admin_comments` TEXT DEFAULT NULL,
    `updated_by` INT(11) DEFAULT NULL,
    `remarks` VARCHAR(255) DEFAULT NULL,
    `created_by` INT(10) UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_fine_code` (`fine_code`),
    KEY `idx_member_id` (`member_id`),
    KEY `idx_status` (`status`),
    KEY `idx_fine_date` (`fine_date`),
    KEY `fine_rule_id` (`fine_rule_id`),
    CONSTRAINT `fines_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
    CONSTRAINT `fines_ibfk_2` FOREIGN KEY (`fine_rule_id`) REFERENCES `fine_rules` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: transaction_mappings
-- ============================================================
CREATE TABLE `transaction_mappings` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `bank_transaction_id` INT(10) UNSIGNED NOT NULL,
    `member_id` INT(10) UNSIGNED NOT NULL,
    `mapping_type` ENUM(
        'savings',
        'loan_payment',
        'fine',
        'other'
    ) NOT NULL,
    `related_id` INT(10) UNSIGNED DEFAULT NULL,
    `amount` DECIMAL(15, 2) NOT NULL,
    `for_month` DATE DEFAULT NULL,
    `narration` VARCHAR(255) DEFAULT NULL,
    `mapped_by` INT(10) UNSIGNED DEFAULT NULL,
    `mapped_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `is_reversed` TINYINT(1) DEFAULT 0,
    `reversed_at` TIMESTAMP NULL DEFAULT NULL,
    `reversed_by` INT(10) UNSIGNED DEFAULT NULL,
    `reversal_reason` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_bank_transaction_id` (`bank_transaction_id`),
    KEY `idx_member_id` (`member_id`),
    KEY `idx_mapping_type` (`mapping_type`),
    CONSTRAINT `transaction_mappings_ibfk_1` FOREIGN KEY (`bank_transaction_id`) REFERENCES `bank_transactions` (`id`),
    CONSTRAINT `transaction_mappings_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: notifications
-- ============================================================
CREATE TABLE `notifications` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `recipient_type` ENUM('admin', 'member') NOT NULL,
    `recipient_id` INT(10) UNSIGNED NOT NULL,
    `notification_type` VARCHAR(50) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `data` LONGTEXT DEFAULT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `read_at` TIMESTAMP NULL DEFAULT NULL,
    `is_sent` TINYINT(1) DEFAULT 0 COMMENT 'SMS/Email sent',
    `sent_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_recipient` (
        `recipient_type`,
        `recipient_id`
    ),
    KEY `idx_is_read` (`is_read`),
    KEY `idx_created_at` (`created_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: chat_box
-- ============================================================
CREATE TABLE `chat_box` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `assign_by` VARCHAR(50) DEFAULT NULL,
    `assign_to` VARCHAR(50) DEFAULT NULL,
    `start_date` DATE DEFAULT NULL,
    `task` TEXT DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `is_deleted` TINYINT(1) DEFAULT 0,
    `color` VARCHAR(20) DEFAULT 'red',
    `admin_id` LONGTEXT DEFAULT NULL COMMENT 'Admin IDs JSON',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ============================================================
-- TABLE: expenditure
-- ============================================================
CREATE TABLE `expenditure` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) DEFAULT NULL,
    `contact_no` VARCHAR(20) DEFAULT NULL,
    `date` DATE DEFAULT NULL,
    `amount` DECIMAL(10, 2) DEFAULT NULL,
    `remarks` TEXT DEFAULT NULL,
    `updated_by` VARCHAR(255) DEFAULT NULL,
    `is_deleted` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ============================================================
-- TABLE: other_member_details
-- ============================================================
CREATE TABLE `other_member_details` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) DEFAULT NULL,
    `member_fee` DECIMAL(10, 2) DEFAULT NULL,
    `other_fee` DECIMAL(10, 2) DEFAULT NULL,
    `contact_no` VARCHAR(20) DEFAULT NULL,
    `dob` DATE DEFAULT NULL,
    `is_deleted` TINYINT(1) DEFAULT 0,
    `remarks` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ============================================================
-- TABLE: schema_migrations
-- ============================================================
CREATE TABLE `schema_migrations` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `filename` VARCHAR(255) NOT NULL,
    `applied_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `filename` (`filename`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: rule_code_sequence
-- ============================================================
CREATE TABLE `rule_code_sequence` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `prefix` VARCHAR(10) NOT NULL DEFAULT 'FR',
    `current_number` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `year` YEAR NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_prefix_year` (`prefix`, `year`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: send_form  (legacy)
-- ============================================================
CREATE TABLE `send_form` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `member_id` VARCHAR(50) DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `type` VARCHAR(50) DEFAULT NULL,
    `file_name` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `member_id` (`member_id`),
    CONSTRAINT `send_form_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `member_details` (`member_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ============================================================
-- TABLE: shares  (legacy)
-- ============================================================
CREATE TABLE `shares` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `monthly_contribution` DECIMAL(10, 2) DEFAULT NULL,
    `total_contribution` DECIMAL(10, 2) DEFAULT NULL,
    `start_date` DATE DEFAULT NULL,
    `bonus` DECIMAL(10, 2) DEFAULT NULL,
    `remarks` TEXT DEFAULT NULL,
    `created_by` VARCHAR(255) DEFAULT NULL,
    `updated_by` VARCHAR(255) DEFAULT NULL,
    `admin_remarks` TEXT DEFAULT NULL,
    `is_deleted` TINYINT(1) DEFAULT 0,
    `member_id` VARCHAR(50) DEFAULT NULL,
    `flag` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `member_id` (`member_id`),
    CONSTRAINT `shares_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `member_details` (`member_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ============================================================
-- TABLE: view_requests  (legacy loan requests)
-- ============================================================
CREATE TABLE `view_requests` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `member_from` VARCHAR(50) DEFAULT NULL,
    `member_to` VARCHAR(50) DEFAULT NULL,
    `admin_reason` TEXT DEFAULT NULL,
    `guarantor_reason` TEXT DEFAULT NULL,
    `status` INT(11) DEFAULT 1,
    `created_by` VARCHAR(50) DEFAULT NULL,
    `amount` DECIMAL(10, 2) DEFAULT NULL,
    `period` INT(11) DEFAULT NULL,
    `unit` VARCHAR(20) DEFAULT NULL,
    `emi` DECIMAL(10, 2) DEFAULT NULL,
    `processing_fee` DECIMAL(10, 2) DEFAULT NULL,
    `is_deleted` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `member_from` (`member_from`),
    KEY `member_to` (`member_to`),
    KEY `status` (`status`),
    CONSTRAINT `view_requests_ibfk_1` FOREIGN KEY (`member_from`) REFERENCES `member_details` (`member_id`),
    CONSTRAINT `view_requests_ibfk_2` FOREIGN KEY (`member_to`) REFERENCES `member_details` (`member_id`),
    CONSTRAINT `view_requests_ibfk_3` FOREIGN KEY (`status`) REFERENCES `requests_status` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ============================================================
-- TABLE: guarantor_settings
-- ============================================================
CREATE TABLE `guarantor_settings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: accounting_settings
-- ============================================================
CREATE TABLE `accounting_settings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- ============================================================
-- DEFAULT DATA INSERTS
-- ============================================================
-- ============================================================

-- ============================================================
-- Default Admin User
-- Username : admin
-- Password : admin123  (bcrypt hash)
-- ============================================================
INSERT INTO
    `admin_users` (
        `username`,
        `email`,
        `password`,
        `full_name`,
        `role`,
        `is_active`
    )
VALUES (
        'admin',
        'admin@windeep.com',
        '$2y$10$tcf8sMpJjl2zlSVaVb3fbeFb02DBn/t7yXmZrs5SDH6dvFgREItHC',
        'System Administrator',
        'super_admin',
        1
    );

-- ============================================================
-- requests_status  (required for view_requests FK)
-- ============================================================
INSERT INTO
    `requests_status` (`id`, `status`)
VALUES (1, 'Pending'),
    (2, 'Approved'),
    (3, 'Rejected'),
    (4, 'Cancelled');

-- ============================================================
-- Member Code Sequence
-- ============================================================
INSERT INTO
    `member_code_sequence` (
        `prefix`,
        `current_number`,
        `year`
    )
VALUES ('MEM', 0, 2026);

-- ============================================================
-- Rule Code Sequence
-- ============================================================
INSERT INTO
    `rule_code_sequence` (
        `prefix`,
        `current_number`,
        `year`
    )
VALUES ('FR', 0, 2026);

-- ============================================================
-- Financial Year (current: 2025-26)
-- ============================================================
INSERT INTO
    `financial_years` (
        `year_code`,
        `start_date`,
        `end_date`,
        `is_active`
    )
VALUES (
        '2025-26',
        '2025-04-01',
        '2026-03-31',
        1
    ),
    (
        '2026-27',
        '2026-04-01',
        '2027-03-31',
        0
    );

-- ============================================================
-- System Settings
-- ============================================================
INSERT INTO
    `system_settings` (
        `setting_key`,
        `setting_value`,
        `setting_type`,
        `description`
    )
VALUES (
        'company_name',
        'Windeep Finance',
        'string',
        'Organisation name'
    ),
    (
        'company_address',
        '',
        'string',
        'Organisation address'
    ),
    (
        'company_phone',
        '',
        'string',
        'Contact phone number'
    ),
    (
        'company_email',
        '',
        'string',
        'Contact email address'
    ),
    (
        'company_logo',
        '',
        'string',
        'Logo file path'
    ),
    (
        'currency_symbol',
        '₹',
        'string',
        'Currency symbol'
    ),
    (
        'currency_code',
        'INR',
        'string',
        'ISO currency code'
    ),
    (
        'date_format',
        'd-m-Y',
        'string',
        'Date display format'
    ),
    (
        'financial_year_start_month',
        '4',
        'number',
        'Financial year start month (April = 4)'
    ),
    (
        'late_fine_grace_days',
        '7',
        'number',
        'Default grace period days for late payments'
    ),
    (
        'max_guarantor_exposure_percent',
        '50',
        'number',
        'Max % of savings a member can guarantee'
    ),
    (
        'loan_approval_required',
        'true',
        'boolean',
        'Require admin approval for loans'
    ),
    (
        'member_approval_after_revision',
        'true',
        'boolean',
        'Require member re-approval after admin revision'
    ),
    (
        'auto_generate_receipts',
        'true',
        'boolean',
        'Auto-generate receipt numbers'
    ),
    (
        'sms_notifications',
        'false',
        'boolean',
        'Enable SMS notifications'
    ),
    (
        'email_notifications',
        'true',
        'boolean',
        'Enable email notifications'
    ),
    (
        'whatsapp_notifications',
        'false',
        'boolean',
        'Enable WhatsApp notifications'
    ),
    (
        'onesignal_notifications',
        'false',
        'boolean',
        'Enable push notifications via OneSignal'
    ),
    (
        'max_login_attempts',
        '5',
        'number',
        'Max failed login attempts before lockout'
    ),
    (
        'session_timeout_minutes',
        '60',
        'number',
        'Admin session timeout in minutes'
    ),
    (
        'receipt_prefix',
        'REC',
        'string',
        'Receipt number prefix'
    ),
    (
        'voucher_prefix_receipt',
        'RV',
        'string',
        'Receipt voucher prefix'
    ),
    (
        'voucher_prefix_payment',
        'PV',
        'string',
        'Payment voucher prefix'
    ),
    (
        'voucher_prefix_journal',
        'JV',
        'string',
        'Journal voucher prefix'
    ),
    (
        'auto_apply_fines',
        'true',
        'boolean',
        'Automatically apply late payment fines via daily cron job'
    ),
    (
        'kyc_required',
        'false',
        'boolean',
        'Require KYC verification before loan approval'
    );

-- ============================================================
-- Chart of Accounts  (default COA)
-- ============================================================
INSERT INTO
    `chart_of_accounts` (
        `account_code`,
        `account_name`,
        `account_type`,
        `is_group`,
        `is_system`
    )
VALUES (
        '1000',
        'Assets',
        'asset',
        1,
        1
    ),
    (
        '1100',
        'Cash & Bank',
        'asset',
        1,
        1
    ),
    (
        '1101',
        'Cash in Hand',
        'asset',
        0,
        1
    ),
    (
        '1102',
        'Bank Accounts',
        'asset',
        0,
        1
    ),
    (
        '1200',
        'Loans & Advances',
        'asset',
        1,
        1
    ),
    (
        '1201',
        'Member Loans',
        'asset',
        0,
        1
    ),
    (
        '1300',
        'Receivables',
        'asset',
        1,
        1
    ),
    (
        '1301',
        'Interest Receivable',
        'asset',
        0,
        1
    ),
    (
        '1302',
        'Fine Receivable',
        'asset',
        0,
        1
    ),
    (
        '2000',
        'Liabilities',
        'liability',
        1,
        1
    ),
    (
        '2100',
        'Member Deposits',
        'liability',
        1,
        1
    ),
    (
        '2101',
        'Savings Deposits',
        'liability',
        0,
        1
    ),
    (
        '2200',
        'Other Liabilities',
        'liability',
        1,
        1
    ),
    (
        '3000',
        'Income',
        'income',
        1,
        1
    ),
    (
        '3100',
        'Interest Income',
        'income',
        0,
        1
    ),
    (
        '3200',
        'Processing Fee Income',
        'income',
        0,
        1
    ),
    (
        '3300',
        'Fine Income',
        'income',
        0,
        1
    ),
    (
        '4000',
        'Expenses',
        'expense',
        1,
        1
    ),
    (
        '4100',
        'Interest Expense',
        'expense',
        0,
        1
    ),
    (
        '4200',
        'Operating Expenses',
        'expense',
        0,
        1
    ),
    (
        '5000',
        'Equity',
        'equity',
        1,
        1
    ),
    (
        '5100',
        'Capital',
        'equity',
        0,
        1
    ),
    (
        '5200',
        'Retained Earnings',
        'equity',
        0,
        1
    );

-- ============================================================
-- Default Savings Schemes
-- ============================================================
INSERT INTO
    `savings_schemes` (
        `scheme_code`,
        `scheme_name`,
        `description`,
        `monthly_amount`,
        `interest_rate`,
        `late_fine_type`,
        `late_fine_value`,
        `grace_period_days`,
        `is_active`
    )
VALUES (
        'SAV-001',
        'Regular Monthly Savings',
        'Standard monthly savings scheme with fixed contribution',
        1000.00,
        0.00,
        'fixed',
        50.00,
        7,
        1
    ),
    (
        'SAV-002',
        'Premium Monthly Savings',
        'Premium savings scheme with higher monthly contribution',
        2000.00,
        2.00,
        'fixed',
        100.00,
        7,
        1
    ),
    (
        'SAV-003',
        'Daily Savings',
        'Daily micro-savings scheme',
        100.00,
        0.00,
        'fixed',
        20.00,
        3,
        1
    );

-- ============================================================
-- Default Fine Rules
-- ============================================================
INSERT INTO
    `fine_rules` (
        `rule_code`,
        `rule_name`,
        `description`,
        `applies_to`,
        `fine_type`,
        `calculation_type`,
        `fine_value`,
        `per_day_amount`,
        `max_fine_amount`,
        `grace_period_days`,
        `is_active`,
        `effective_from`
    )
VALUES (
        'FINE-SAV-01',
        'Savings Late Payment Fine',
        'Fixed fine for missing monthly savings deposit',
        'savings',
        'fixed',
        'fixed',
        50.00,
        0.00,
        500.00,
        7,
        1,
        '2025-01-01'
    ),
    (
        'FINE-LOAN-01',
        'Loan EMI Late Payment Fine',
        'Per-day fine after grace period for EMI default',
        'loan',
        'per_day',
        'per_day',
        0.00,
        10.00,
        1000.00,
        7,
        1,
        '2025-01-01'
    ),
    (
        'FINE-LOAN-02',
        'Loan EMI Standard Fine',
        'Fixed Rs.100 + Rs.10 per day fine (Indian banking style)',
        'loan',
        'slab',
        'fixed',
        100.00,
        10.00,
        500.00,
        0,
        1,
        '2025-01-01'
    );

-- ============================================================
-- Default Loan Products
-- ============================================================
INSERT INTO
    `loan_products` (
        `product_code`,
        `product_name`,
        `description`,
        `min_amount`,
        `max_amount`,
        `min_tenure_months`,
        `max_tenure_months`,
        `interest_rate`,
        `min_interest_rate`,
        `max_interest_rate`,
        `default_interest_rate`,
        `interest_type`,
        `processing_fee_type`,
        `processing_fee_value`,
        `late_fee_type`,
        `late_fee_value`,
        `late_fee_per_day`,
        `grace_period_days`,
        `min_guarantors`,
        `required_savings_months`,
        `max_loan_to_savings_ratio`,
        `is_active`
    )
VALUES (
        'LOAN-001',
        'Personal Loan',
        'General purpose personal loan for members',
        10000.00,
        500000.00,
        6,
        36,
        12.00,
        10.00,
        18.00,
        12.00,
        'reducing',
        'percentage',
        1.00,
        'fixed',
        100.00,
        10.00,
        5,
        2,
        6,
        3.00,
        1
    ),
    (
        'LOAN-002',
        'Emergency Loan',
        'Quick emergency loan with faster processing',
        5000.00,
        100000.00,
        3,
        12,
        15.00,
        12.00,
        20.00,
        15.00,
        'flat',
        'fixed',
        500.00,
        'fixed',
        50.00,
        5.00,
        3,
        1,
        3,
        2.00,
        1
    ),
    (
        'LOAN-003',
        'Gold Loan',
        'Loan against gold collateral',
        10000.00,
        1000000.00,
        1,
        24,
        10.00,
        8.00,
        14.00,
        10.00,
        'reducing',
        'percentage',
        0.50,
        'fixed',
        50.00,
        5.00,
        7,
        0,
        0,
        10.00,
        1
    );

-- ============================================================
-- Default Bank Account (Cash)
-- ============================================================
INSERT INTO
    `bank_accounts` (
        `account_name`,
        `bank_name`,
        `account_number`,
        `account_type`,
        `opening_balance`,
        `current_balance`,
        `is_primary`,
        `is_active`
    )
VALUES (
        'Cash Account',
        'Cash',
        'CASH-001',
        'cash',
        0.00,
        0.00,
        1,
        1
    );

-- ============================================================
-- Guarantor Settings
-- ============================================================
INSERT INTO
    `guarantor_settings` (
        `setting_key`,
        `setting_value`,
        `description`
    )
VALUES (
        'min_guarantors',
        '2',
        'Minimum number of guarantors required'
    ),
    (
        'max_guarantors',
        '4',
        'Maximum number of guarantors allowed'
    ),
    (
        'min_coverage_percentage',
        '100',
        'Minimum coverage percentage required'
    ),
    (
        'guarantor_must_be_member',
        '1',
        'Guarantor must be a registered member'
    ),
    (
        'guarantor_max_active_guarantees',
        '3',
        'Maximum active guarantees a member can give'
    ),
    (
        'guarantor_min_membership_days',
        '30',
        'Minimum membership days before can be guarantor'
    ),
    (
        'allow_self_guarantee',
        '0',
        'Allow member to guarantee their own loan'
    ),
    (
        'guarantor_liability_type',
        'joint',
        'Liability type: joint or several'
    ),
    (
        'auto_debit_from_guarantor',
        '1',
        'Auto debit from guarantor if borrower defaults'
    ),
    (
        'notify_guarantor_on_default',
        '1',
        'Send notification to guarantor on default'
    );

-- ============================================================
-- Accounting Settings
-- ============================================================
INSERT INTO
    `accounting_settings` (
        `setting_key`,
        `setting_value`,
        `description`
    )
VALUES (
        'default_cash_account',
        '1',
        'Default cash account ID'
    ),
    (
        'default_bank_account',
        '2',
        'Default bank account ID'
    ),
    (
        'loan_receivable_account',
        '3',
        'Loan receivable account ID'
    ),
    (
        'interest_income_account',
        '4',
        'Interest income account ID'
    ),
    (
        'fine_income_account',
        '5',
        'Fine/penalty income account ID'
    ),
    (
        'processing_fee_account',
        '6',
        'Processing fee account ID'
    ),
    (
        'member_savings_account',
        '7',
        'Member savings liability account ID'
    ),
    (
        'voucher_prefix_receipt',
        'RV',
        'Receipt voucher prefix'
    ),
    (
        'voucher_prefix_payment',
        'PV',
        'Payment voucher prefix'
    ),
    (
        'voucher_prefix_journal',
        'JV',
        'Journal voucher prefix'
    ),
    (
        'financial_year_start_month',
        '4',
        'Financial year start month (April = 4)'
    ),
    (
        'auto_generate_voucher_number',
        '1',
        'Auto generate voucher numbers'
    );

-- ============================================================
-- Migration tracking
-- ============================================================
INSERT INTO
    `schema_migrations` (`filename`)
VALUES ('001_initial_schema.sql'),
    ('002_clean_no_triggers.sql'),
    (
        '003_add_transaction_tracking_and_fine_enhancements.sql'
    );

-- ============================================================
SET FOREIGN_KEY_CHECKS = 1;
-- ============================================================
-- INSTALL COMPLETE
-- ============================================================
-- Total Tables : 50
-- Default Data : Admin user, financial year, system settings,
--                chart of accounts, savings schemes, loan products,
--                fine rules, bank account, guarantor & accounting settings
-- ============================================================
-- NEXT STEPS:
--   1. Open phpMyAdmin → Create database 'windeep_finance'
--   2. Select that database → Import → Choose this file → Go
--   3. Login at /admin  →  Username: admin  Password: admin123
--   4. Go to Settings → Change company name, address, logo
--   5. Go to Settings → Change admin password immediately
-- ============================================================