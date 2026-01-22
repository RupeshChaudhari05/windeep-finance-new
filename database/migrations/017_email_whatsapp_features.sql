-- ============================================
-- Migration 017: Email Verification & WhatsApp
-- Windeep Finance - Production Features
-- ============================================

-- ============================================
-- VERIFICATION TOKENS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `verification_tokens` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `user_type` ENUM('member', 'admin') NOT NULL DEFAULT 'member',
    `type` VARCHAR(50) NOT NULL COMMENT 'email_verification, password_reset, phone_verification',
    `token` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255),
    `phone` VARCHAR(20),
    `expires_at` DATETIME NOT NULL,
    `used_at` DATETIME,
    `verified_ip` VARCHAR(45),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_token` (`token`),
    KEY `idx_user` (`user_id`, `user_type`),
    KEY `idx_expires` (`expires_at`),
    KEY `idx_type` (`type`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ============================================
-- EMAIL QUEUE TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `email_queue` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `to_email` VARCHAR(255) NOT NULL,
    `to_name` VARCHAR(200),
    `cc` VARCHAR(500),
    `bcc` VARCHAR(500),
    `subject` VARCHAR(500) NOT NULL,
    `body` LONGTEXT NOT NULL,
    `attachments` TEXT COMMENT 'JSON array of file paths',
    `priority` TINYINT DEFAULT 5 COMMENT '1=highest, 10=lowest',
    `status` ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    `attempts` TINYINT DEFAULT 0,
    `last_error` TEXT,
    `scheduled_at` DATETIME,
    `sent_at` DATETIME,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `created_by` INT,
    KEY `idx_status` (`status`),
    KEY `idx_priority` (`priority`),
    KEY `idx_scheduled` (`scheduled_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ============================================
-- WHATSAPP LOGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `whatsapp_logs` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `phone` VARCHAR(20) NOT NULL,
    `message_id` VARCHAR(100),
    `template_name` VARCHAR(100),
    `message_type` ENUM('text', 'template', 'media') DEFAULT 'text',
    `status` ENUM(
        'queued',
        'sent',
        'delivered',
        'read',
        'failed'
    ) DEFAULT 'queued',
    `error` TEXT,
    `cost` DECIMAL(8, 4) DEFAULT 0,
    `member_id` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_phone` (`phone`),
    KEY `idx_message_id` (`message_id`),
    KEY `idx_status` (`status`),
    KEY `idx_member` (`member_id`),
    KEY `idx_created` (`created_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ============================================
-- BACKUPS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `backups` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `filename` VARCHAR(255) NOT NULL,
    `filepath` VARCHAR(500) NOT NULL,
    `size` BIGINT,
    `type` ENUM(
        'manual',
        'scheduled',
        'pre-restore'
    ) DEFAULT 'manual',
    `status` ENUM(
        'completed',
        'failed',
        'in_progress'
    ) DEFAULT 'completed',
    `notes` TEXT,
    `created_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_type` (`type`),
    KEY `idx_created` (`created_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ============================================
-- MONTHLY REPORTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `monthly_reports` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `report_month` DATE NOT NULL,
    `report_type` VARCHAR(50) NOT NULL,
    `data` JSON,
    `file_path` VARCHAR(500),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_report` (`report_month`, `report_type`),
    KEY `idx_month` (`report_month`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ============================================
-- CRON LOG TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `cron_log` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `job_name` VARCHAR(100) NOT NULL,
    `status` ENUM(
        'started',
        'completed',
        'failed'
    ) NOT NULL,
    `message` TEXT,
    `duration_seconds` INT,
    `records_processed` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_job` (`job_name`),
    KEY `idx_status` (`status`),
    KEY `idx_created` (`created_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ============================================
-- ADD EMAIL VERIFICATION COLUMNS TO MEMBERS
-- ============================================
ALTER TABLE `members`
ADD COLUMN IF NOT EXISTS `email_verified` TINYINT(1) DEFAULT 0 AFTER `email`,
ADD COLUMN IF NOT EXISTS `email_verified_at` DATETIME AFTER `email_verified`,
ADD COLUMN IF NOT EXISTS `phone_verified` TINYINT(1) DEFAULT 0 AFTER `phone`,
ADD COLUMN IF NOT EXISTS `phone_verified_at` DATETIME AFTER `phone_verified`;

-- ============================================
-- ADD EMAIL VERIFICATION COLUMNS TO ADMIN_USERS
-- ============================================
ALTER TABLE `admin_users`
ADD COLUMN IF NOT EXISTS `email_verified` TINYINT(1) DEFAULT 1 AFTER `email`,
ADD COLUMN IF NOT EXISTS `email_verified_at` DATETIME AFTER `email_verified`;

-- ============================================
-- ADD WHATSAPP SETTINGS
-- ============================================
INSERT IGNORE INTO
    `settings` (
        `key`,
        `value`,
        `category`,
        `label`,
        `type`
    )
VALUES (
        'whatsapp_enabled',
        '0',
        'whatsapp',
        'Enable WhatsApp',
        'boolean'
    ),
    (
        'whatsapp_provider',
        'meta',
        'whatsapp',
        'WhatsApp Provider',
        'select'
    ),
    (
        'whatsapp_api_url',
        'https://graph.facebook.com/v18.0',
        'whatsapp',
        'API URL',
        'text'
    ),
    (
        'whatsapp_api_key',
        '',
        'whatsapp',
        'API Key / Access Token',
        'password'
    ),
    (
        'whatsapp_phone_number_id',
        '',
        'whatsapp',
        'Phone Number ID',
        'text'
    ),
    (
        'whatsapp_business_account_id',
        '',
        'whatsapp',
        'Business Account ID',
        'text'
    ),
    (
        'twilio_account_sid',
        '',
        'whatsapp',
        'Twilio Account SID',
        'text'
    ),
    (
        'twilio_auth_token',
        '',
        'whatsapp',
        'Twilio Auth Token',
        'password'
    ),
    (
        'twilio_whatsapp_number',
        '',
        'whatsapp',
        'Twilio WhatsApp Number',
        'text'
    ),
    (
        'whatsapp_custom_url',
        '',
        'whatsapp',
        'Custom API URL',
        'text'
    ),
    (
        'whatsapp_custom_key',
        '',
        'whatsapp',
        'Custom API Key',
        'password'
    );

-- ============================================
-- ADD NOTIFICATION PREFERENCES
-- ============================================
INSERT IGNORE INTO
    `settings` (
        `key`,
        `value`,
        `category`,
        `label`,
        `type`
    )
VALUES (
        'notify_email_payment_reminder',
        '1',
        'notifications',
        'Email Payment Reminders',
        'boolean'
    ),
    (
        'notify_email_loan_approval',
        '1',
        'notifications',
        'Email Loan Approvals',
        'boolean'
    ),
    (
        'notify_email_payment_receipt',
        '1',
        'notifications',
        'Email Payment Receipts',
        'boolean'
    ),
    (
        'notify_whatsapp_payment_reminder',
        '0',
        'notifications',
        'WhatsApp Payment Reminders',
        'boolean'
    ),
    (
        'notify_whatsapp_loan_approval',
        '0',
        'notifications',
        'WhatsApp Loan Approvals',
        'boolean'
    ),
    (
        'notify_whatsapp_payment_receipt',
        '0',
        'notifications',
        'WhatsApp Payment Receipts',
        'boolean'
    ),
    (
        'payment_reminder_days',
        '3',
        'notifications',
        'Payment Reminder Days Before Due',
        'number'
    );

-- ============================================
-- ADD GUARANTOR REMINDER COLUMNS
-- ============================================
ALTER TABLE `loan_guarantors`
ADD COLUMN IF NOT EXISTS `reminder_count` TINYINT DEFAULT 0 AFTER `consent_status`,
ADD COLUMN IF NOT EXISTS `last_reminder_at` DATETIME AFTER `reminder_count`;

-- ============================================
-- ACTIVITY LOG TABLE (if not exists)
-- ============================================
CREATE TABLE IF NOT EXISTS `activity_log` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_type` VARCHAR(20),
    `user_id` INT,
    `action` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `entity_type` VARCHAR(50),
    `entity_id` INT,
    `old_values` JSON,
    `new_values` JSON,
    `ip_address` VARCHAR(45),
    `user_agent` VARCHAR(500),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_user` (`user_type`, `user_id`),
    KEY `idx_action` (`action`),
    KEY `idx_entity` (`entity_type`, `entity_id`),
    KEY `idx_created` (`created_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ============================================
-- UPDATE SETTINGS TABLE STRUCTURE
-- ============================================
ALTER TABLE `settings`
ADD COLUMN IF NOT EXISTS `category` VARCHAR(50) DEFAULT 'general' AFTER `value`,
ADD COLUMN IF NOT EXISTS `label` VARCHAR(200) AFTER `category`,
ADD COLUMN IF NOT EXISTS `type` VARCHAR(20) DEFAULT 'text' AFTER `label`,
ADD COLUMN IF NOT EXISTS `options` TEXT AFTER `type`,
ADD COLUMN IF NOT EXISTS `help_text` TEXT AFTER `options`,
ADD COLUMN IF NOT EXISTS `sort_order` INT DEFAULT 0 AFTER `help_text`;

-- ============================================
-- ADD INTEREST EARNED COLUMN TO SAVINGS
-- ============================================
ALTER TABLE `savings_accounts`
ADD COLUMN IF NOT EXISTS `interest_earned` DECIMAL(12, 2) DEFAULT 0 AFTER `current_balance`;

-- ============================================
-- ADD NPA DATE TO LOANS
-- ============================================
ALTER TABLE `loans`
ADD COLUMN IF NOT EXISTS `npa_date` DATE AFTER `status`,
ADD COLUMN IF NOT EXISTS `is_restructured` TINYINT(1) DEFAULT 0 AFTER `npa_date`;