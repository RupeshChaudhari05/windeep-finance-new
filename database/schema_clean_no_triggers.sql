-- ============================================
-- WINDEEP FINANCE - DATABASE SCHEMA (CLEAN)
-- Exported: 2026-01-24 21:20:45
-- Database: windeep_finance_new
-- NO TRIGGERS - Perfect for Shared Hosting
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Table: active_sessions
DROP TABLE IF EXISTS `active_sessions`;
CREATE TABLE `active_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `session_id` varchar(128) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `last_activity` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_last_activity` (`last_activity`),
  KEY `idx_active_sessions_user_activity` (`user_id`,`last_activity`),
  CONSTRAINT `active_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Track active user sessions for security monitoring';

-- Table: activity_logs
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_type` enum('admin','member') NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `activity` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `module` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_type`,`user_id`),
  KEY `idx_activity` (`activity`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: admin_details
DROP TABLE IF EXISTS `admin_details`;
CREATE TABLE `admin_details` (
  `admin_id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `member_id` varchar(50) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `email` (`email`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `admin_details_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `member_details` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: admin_sessions
DROP TABLE IF EXISTS `admin_sessions`;
CREATE TABLE `admin_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(10) unsigned NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_session_token` (`session_token`),
  CONSTRAINT `admin_sessions_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: admin_users
DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE `admin_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('super_admin','admin','manager','accountant','viewer') DEFAULT 'viewer',
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Granular permissions JSON' CHECK (json_valid(`permissions`)),
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `password_changed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_username` (`username`),
  UNIQUE KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: api_tokens
DROP TABLE IF EXISTS `api_tokens`;
CREATE TABLE `api_tokens` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'Token description/name',
  `scopes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Allowed API scopes' CHECK (json_valid(`scopes`)),
  `last_used_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_token` (`token`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `api_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='API authentication tokens';

-- Table: audit_logs
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `audit_code` varchar(50) NOT NULL,
  `user_type` enum('admin','member','system') NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(50) NOT NULL COMMENT 'create, update, delete, approve, reject, etc.',
  `module` varchar(50) NOT NULL COMMENT 'members, loans, savings, etc.',
  `table_name` varchar(100) NOT NULL,
  `record_id` int(10) unsigned NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `changed_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'List of changed field names' CHECK (json_valid(`changed_fields`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_code` (`audit_code`),
  KEY `idx_user` (`user_type`,`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_module` (`module`),
  KEY `idx_table_record` (`table_name`,`record_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: bank_accounts
DROP TABLE IF EXISTS `bank_accounts`;
CREATE TABLE `bank_accounts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `account_name` varchar(100) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `branch_name` varchar(100) DEFAULT NULL,
  `account_number` varchar(30) NOT NULL,
  `ifsc_code` varchar(15) DEFAULT NULL,
  `account_type` enum('current','savings','cash') DEFAULT 'current',
  `opening_balance` decimal(15,2) DEFAULT 0.00,
  `current_balance` decimal(15,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_account_number` (`account_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: bank_balance_history
DROP TABLE IF EXISTS `bank_balance_history`;
CREATE TABLE `bank_balance_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `balance` decimal(15,2) NOT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: bank_statement_imports
DROP TABLE IF EXISTS `bank_statement_imports`;
CREATE TABLE `bank_statement_imports` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `import_code` varchar(30) NOT NULL,
  `bank_account_id` int(10) unsigned NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `statement_date` date DEFAULT NULL,
  `file_type` enum('csv','excel','pdf') NOT NULL,
  `statement_from_date` date DEFAULT NULL,
  `statement_to_date` date DEFAULT NULL,
  `total_transactions` int(11) DEFAULT 0,
  `total_credits` decimal(15,2) DEFAULT 0.00,
  `total_debits` decimal(15,2) DEFAULT 0.00,
  `mapped_count` int(11) DEFAULT 0,
  `unmapped_count` int(11) DEFAULT 0,
  `status` enum('uploaded','parsing','parsed','mapping','completed','failed') DEFAULT 'uploaded',
  `error_message` text DEFAULT NULL,
  `imported_by` int(10) unsigned DEFAULT NULL,
  `imported_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_import_code` (`import_code`),
  KEY `idx_bank_account_id` (`bank_account_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `bank_statement_imports_ibfk_1` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: bank_transactions
DROP TABLE IF EXISTS `bank_transactions`;
CREATE TABLE `bank_transactions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `import_id` int(10) unsigned NOT NULL,
  `bank_account_id` int(10) unsigned NOT NULL,
  `transaction_date` date NOT NULL,
  `value_date` date DEFAULT NULL,
  `transaction_type` enum('credit','debit') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `credit_amount` decimal(15,2) DEFAULT 0.00,
  `debit_amount` decimal(15,2) DEFAULT 0.00,
  `running_balance` decimal(15,2) DEFAULT 0.00,
  `balance_after` decimal(15,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `description2` varchar(255) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `utr_number` varchar(50) DEFAULT NULL,
  `cheque_number` varchar(20) DEFAULT NULL,
  `mapping_status` enum('unmapped','partial','mapped','ignored','split') DEFAULT 'unmapped',
  `mapped_amount` decimal(15,2) DEFAULT 0.00,
  `unmapped_amount` decimal(15,2) DEFAULT NULL,
  `detected_member_id` int(10) unsigned DEFAULT NULL COMMENT 'Auto-detected member',
  `detection_confidence` decimal(5,2) DEFAULT NULL COMMENT 'Confidence score 0-100',
  `paid_by_member_id` int(10) unsigned DEFAULT NULL COMMENT 'Member who made the payment',
  `paid_for_member_id` int(10) unsigned DEFAULT NULL COMMENT 'Member who received the payment',
  `transaction_category` varchar(50) DEFAULT NULL,
  `mapping_remarks` text DEFAULT NULL,
  `mapped_by` int(11) DEFAULT NULL,
  `mapped_at` datetime DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL COMMENT 'Admin who recorded this transaction',
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_utr_unique` (`utr_number`),
  KEY `idx_import_id` (`import_id`),
  KEY `idx_transaction_date` (`transaction_date`),
  KEY `idx_mapping_status` (`mapping_status`),
  KEY `idx_utr_number` (`utr_number`),
  KEY `bank_account_id` (`bank_account_id`),
  KEY `idx_paid_by_member` (`paid_by_member_id`),
  KEY `idx_paid_for_member` (`paid_for_member_id`),
  KEY `idx_mapped_by` (`mapped_by`),
  CONSTRAINT `bank_transactions_ibfk_1` FOREIGN KEY (`import_id`) REFERENCES `bank_statement_imports` (`id`),
  CONSTRAINT `bank_transactions_ibfk_2` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: chart_of_accounts
DROP TABLE IF EXISTS `chart_of_accounts`;
CREATE TABLE `chart_of_accounts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `account_code` varchar(20) NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `account_type` enum('asset','liability','income','expense','equity') NOT NULL,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `is_group` tinyint(1) DEFAULT 0,
  `is_system` tinyint(1) DEFAULT 0 COMMENT 'System accounts cannot be deleted',
  `opening_balance` decimal(15,2) DEFAULT 0.00,
  `current_balance` decimal(15,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_account_code` (`account_code`),
  KEY `idx_account_type` (`account_type`),
  KEY `idx_parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: chat_box
DROP TABLE IF EXISTS `chat_box`;
CREATE TABLE `chat_box` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assign_by` varchar(50) DEFAULT NULL,
  `assign_to` varchar(50) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `task` text DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `color` varchar(20) DEFAULT 'red',
  `admin_id` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`admin_id`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: ci_sessions
DROP TABLE IF EXISTS `ci_sessions`;
CREATE TABLE `ci_sessions` (
  `id` varchar(128) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `timestamp` int(10) unsigned NOT NULL DEFAULT 0,
  `data` blob NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_timestamp` (`timestamp`),
  KEY `idx_ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='CodeIgniter session storage';

-- Table: expenditure
DROP TABLE IF EXISTS `expenditure`;
CREATE TABLE `expenditure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `updated_by` varchar(255) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: failed_login_attempts
DROP TABLE IF EXISTS `failed_login_attempts`;
CREATE TABLE `failed_login_attempts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(255) NOT NULL COMMENT 'IP address or email',
  `identifier_type` enum('ip','email','username') DEFAULT 'ip',
  `attempts` int(10) unsigned NOT NULL DEFAULT 1,
  `first_attempt_at` datetime NOT NULL,
  `last_attempt_at` datetime NOT NULL,
  `locked_until` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_identifier` (`identifier`,`identifier_type`),
  KEY `idx_locked_until` (`locked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Track failed login attempts for rate limiting';

-- Table: financial_years
DROP TABLE IF EXISTS `financial_years`;
CREATE TABLE `financial_years` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `year_code` varchar(20) NOT NULL COMMENT 'e.g., 2025-26',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `is_closed` tinyint(1) DEFAULT 0,
  `closed_at` timestamp NULL DEFAULT NULL,
  `closed_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_year_code` (`year_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: fine_rules
DROP TABLE IF EXISTS `fine_rules`;
CREATE TABLE `fine_rules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rule_code` varchar(20) NOT NULL,
  `rule_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `applies_to` enum('savings','loan','both') DEFAULT 'both',
  `fine_type` enum('fixed','percentage','per_day','slab') NOT NULL,
  `calculation_type` enum('fixed','percentage','per_day','slab') NOT NULL DEFAULT 'fixed',
  `fine_value` decimal(10,2) NOT NULL COMMENT 'Fixed amount or percentage',
  `per_day_amount` decimal(10,2) DEFAULT 0.00,
  `max_fine_amount` decimal(15,2) DEFAULT NULL COMMENT 'Cap on fine',
  `grace_period_days` int(11) DEFAULT 0,
  `slab_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'For slab-based fines' CHECK (json_valid(`slab_config`)),
  `is_active` tinyint(1) DEFAULT 1,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_rule_code` (`rule_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: fines
DROP TABLE IF EXISTS `fines`;
CREATE TABLE `fines` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fine_code` varchar(30) NOT NULL,
  `member_id` int(10) unsigned NOT NULL,
  `fine_type` enum('savings_late','loan_late','bounced_cheque','other') NOT NULL,
  `related_type` enum('savings_schedule','loan_installment','other') NOT NULL,
  `related_id` int(10) unsigned NOT NULL,
  `fine_rule_id` int(10) unsigned DEFAULT NULL,
  `fine_date` date NOT NULL,
  `due_date` date NOT NULL COMMENT 'Original due date',
  `days_late` int(11) NOT NULL,
  `fine_amount` decimal(15,2) NOT NULL,
  `paid_amount` decimal(15,2) DEFAULT 0.00,
  `waived_amount` decimal(15,2) DEFAULT 0.00,
  `balance_amount` decimal(15,2) NOT NULL,
  `status` enum('pending','partial','paid','waived','cancelled') DEFAULT 'pending',
  `payment_date` date DEFAULT NULL,
  `payment_mode` enum('cash','cheque','bank_transfer','online') DEFAULT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `received_by` int(10) unsigned DEFAULT NULL,
  `waived_by` int(10) unsigned DEFAULT NULL,
  `waived_at` timestamp NULL DEFAULT NULL,
  `waiver_reason` varchar(255) DEFAULT NULL,
  `waiver_approved_by` int(10) unsigned DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `waiver_requested_by` int(10) unsigned DEFAULT NULL,
  `waiver_requested_at` timestamp NULL DEFAULT NULL,
  `waiver_requested_amount` decimal(15,2) DEFAULT NULL,
  `waiver_approved_at` timestamp NULL DEFAULT NULL,
  `waiver_denied_by` int(10) unsigned DEFAULT NULL,
  `waiver_denied_at` timestamp NULL DEFAULT NULL,
  `waiver_denied_reason` varchar(255) DEFAULT NULL,
  `admin_comments` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_fine_code` (`fine_code`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_status` (`status`),
  KEY `idx_fine_date` (`fine_date`),
  KEY `fine_rule_id` (`fine_rule_id`),
  CONSTRAINT `fines_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `fines_ibfk_2` FOREIGN KEY (`fine_rule_id`) REFERENCES `fine_rules` (`id`),
  CONSTRAINT `chk_fines_positive` CHECK (`fine_amount` > 0 and `paid_amount` >= 0 and `balance_amount` >= 0 and `balance_amount` <= `fine_amount`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: general_ledger
DROP TABLE IF EXISTS `general_ledger`;
CREATE TABLE `general_ledger` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `voucher_number` varchar(30) NOT NULL,
  `voucher_date` date NOT NULL,
  `voucher_type` enum('receipt','payment','journal','contra') NOT NULL,
  `account_id` int(10) unsigned NOT NULL,
  `debit_amount` decimal(15,2) DEFAULT 0.00,
  `credit_amount` decimal(15,2) DEFAULT 0.00,
  `balance_after` decimal(15,2) NOT NULL,
  `narration` varchar(255) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL COMMENT 'savings_transaction, loan_payment, etc.',
  `reference_id` int(10) unsigned DEFAULT NULL,
  `member_id` int(10) unsigned DEFAULT NULL,
  `financial_year_id` int(10) unsigned DEFAULT NULL,
  `is_posted` tinyint(1) DEFAULT 1,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_voucher_number` (`voucher_number`),
  KEY `idx_voucher_date` (`voucher_date`),
  KEY `idx_account_id` (`account_id`),
  KEY `idx_reference` (`reference_type`,`reference_id`),
  CONSTRAINT `general_ledger_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: loan_applications
DROP TABLE IF EXISTS `loan_applications`;
CREATE TABLE `loan_applications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `application_number` varchar(30) NOT NULL,
  `member_id` int(10) unsigned NOT NULL,
  `loan_product_id` int(10) unsigned NOT NULL,
  `requested_amount` decimal(15,2) NOT NULL,
  `requested_tenure_months` int(11) NOT NULL,
  `requested_interest_rate` decimal(5,2) DEFAULT NULL,
  `purpose` varchar(255) NOT NULL,
  `purpose_details` text DEFAULT NULL,
  `approved_amount` decimal(15,2) DEFAULT NULL,
  `approved_tenure_months` int(11) DEFAULT NULL,
  `approved_interest_rate` decimal(5,2) DEFAULT NULL,
  `revision_remarks` text DEFAULT NULL,
  `revised_at` timestamp NULL DEFAULT NULL,
  `revised_by` int(10) unsigned DEFAULT NULL,
  `status` enum('draft','pending','under_review','guarantor_pending','admin_approved','member_review','member_approved','disbursed','rejected','cancelled','expired') DEFAULT 'draft',
  `status_remarks` varchar(255) DEFAULT NULL,
  `admin_approved_at` timestamp NULL DEFAULT NULL,
  `admin_approved_by` int(10) unsigned DEFAULT NULL,
  `member_approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `rejected_by` int(10) unsigned DEFAULT NULL,
  `member_savings_balance` decimal(15,2) DEFAULT NULL COMMENT 'At time of application',
  `member_existing_loans` int(11) DEFAULT 0,
  `member_existing_loan_balance` decimal(15,2) DEFAULT 0.00,
  `eligibility_score` int(11) DEFAULT NULL,
  `application_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL COMMENT 'Application expires if not processed',
  `documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Uploaded documents' CHECK (json_valid(`documents`)),
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_application_number` (`application_number`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_status` (`status`),
  KEY `idx_application_date` (`application_date`),
  KEY `loan_product_id` (`loan_product_id`),
  CONSTRAINT `loan_applications_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `loan_applications_ibfk_2` FOREIGN KEY (`loan_product_id`) REFERENCES `loan_products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: loan_foreclosure_requests
DROP TABLE IF EXISTS `loan_foreclosure_requests`;
CREATE TABLE `loan_foreclosure_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `loan_id` int(10) unsigned NOT NULL,
  `member_id` int(10) unsigned NOT NULL,
  `foreclosure_amount` decimal(15,2) NOT NULL,
  `reason` text NOT NULL,
  `settlement_date` date NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_by` int(10) unsigned DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `admin_comments` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_loan_id` (`loan_id`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_status` (`status`),
  KEY `processed_by` (`processed_by`),
  CONSTRAINT `loan_foreclosure_requests_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loan_foreclosure_requests_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loan_foreclosure_requests_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: loan_guarantors
DROP TABLE IF EXISTS `loan_guarantors`;
CREATE TABLE `loan_guarantors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `loan_application_id` int(10) unsigned NOT NULL,
  `loan_id` int(10) unsigned DEFAULT NULL COMMENT 'Set after loan is disbursed',
  `guarantor_member_id` int(10) unsigned NOT NULL,
  `guarantee_amount` decimal(15,2) NOT NULL COMMENT 'Liability amount',
  `relationship` varchar(50) DEFAULT NULL,
  `consent_status` enum('pending','accepted','rejected','withdrawn') DEFAULT 'pending',
  `consent_date` timestamp NULL DEFAULT NULL,
  `consent_ip` varchar(45) DEFAULT NULL,
  `consent_remarks` varchar(255) DEFAULT NULL,
  `rejection_reason` varchar(255) DEFAULT NULL,
  `consent_token` varchar(64) DEFAULT NULL,
  `is_released` tinyint(1) DEFAULT 0,
  `released_at` timestamp NULL DEFAULT NULL,
  `released_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_application_guarantor` (`loan_application_id`,`guarantor_member_id`),
  KEY `idx_guarantor_member` (`guarantor_member_id`),
  KEY `idx_loan_id` (`loan_id`),
  KEY `idx_consent_status` (`consent_status`),
  CONSTRAINT `loan_guarantors_ibfk_1` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`),
  CONSTRAINT `loan_guarantors_ibfk_2` FOREIGN KEY (`guarantor_member_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: loan_installments
DROP TABLE IF EXISTS `loan_installments`;
CREATE TABLE `loan_installments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `loan_id` int(10) unsigned NOT NULL,
  `installment_number` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `principal_amount` decimal(15,2) NOT NULL,
  `interest_amount` decimal(15,2) NOT NULL,
  `emi_amount` decimal(15,2) NOT NULL,
  `outstanding_principal_before` decimal(15,2) NOT NULL,
  `outstanding_principal_after` decimal(15,2) NOT NULL,
  `principal_paid` decimal(15,2) DEFAULT 0.00,
  `interest_paid` decimal(15,2) DEFAULT 0.00,
  `fine_amount` decimal(15,2) DEFAULT 0.00,
  `fine_paid` decimal(15,2) DEFAULT 0.00,
  `total_paid` decimal(15,2) DEFAULT 0.00,
  `status` enum('upcoming','pending','partial','paid','overdue','skipped','interest_only','waived') DEFAULT 'upcoming',
  `paid_date` date DEFAULT NULL,
  `is_late` tinyint(1) DEFAULT 0,
  `days_late` int(11) DEFAULT 0,
  `is_skipped` tinyint(1) DEFAULT 0,
  `skip_reason` varchar(255) DEFAULT NULL,
  `skipped_by` int(10) unsigned DEFAULT NULL,
  `is_adjusted` tinyint(1) DEFAULT 0,
  `adjustment_remarks` varchar(255) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_loan_installment` (`loan_id`,`installment_number`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_status` (`status`),
  CONSTRAINT `loan_installments_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`),
  CONSTRAINT `chk_installments_positive` CHECK (`principal_amount` >= 0 and `interest_amount` >= 0 and `emi_amount` >= 0 and `principal_paid` >= 0 and `interest_paid` >= 0 and `fine_paid` >= 0 and `principal_paid` <= `principal_amount` and `interest_paid` <= `interest_amount`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: loan_payments
DROP TABLE IF EXISTS `loan_payments`;
CREATE TABLE `loan_payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `payment_code` varchar(30) NOT NULL,
  `loan_id` int(10) unsigned NOT NULL,
  `installment_id` int(10) unsigned DEFAULT NULL COMMENT 'Can be NULL for prepayments',
  `payment_type` enum('emi','part_payment','advance_payment','interest_only','fine_payment','foreclosure','adjustment','reversal') NOT NULL,
  `payment_date` date NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `principal_component` decimal(15,2) DEFAULT 0.00,
  `interest_component` decimal(15,2) DEFAULT 0.00,
  `fine_component` decimal(15,2) DEFAULT 0.00,
  `excess_amount` decimal(15,2) DEFAULT 0.00 COMMENT 'Advance payment',
  `outstanding_principal_after` decimal(15,2) NOT NULL,
  `outstanding_interest_after` decimal(15,2) NOT NULL,
  `payment_mode` enum('cash','bank_transfer','cheque','upi','auto_debit','adjustment') DEFAULT 'cash',
  `reference_number` varchar(50) DEFAULT NULL,
  `receipt_number` varchar(30) DEFAULT NULL,
  `cheque_number` varchar(20) DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_transaction_id` int(10) unsigned DEFAULT NULL,
  `is_reversed` tinyint(1) DEFAULT 0,
  `reversed_at` timestamp NULL DEFAULT NULL,
  `reversed_by` int(10) unsigned DEFAULT NULL,
  `reversal_reason` varchar(255) DEFAULT NULL,
  `reversal_payment_id` int(10) unsigned DEFAULT NULL COMMENT 'Link to reversal entry',
  `narration` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_payment_code` (`payment_code`),
  KEY `idx_loan_id` (`loan_id`),
  KEY `idx_payment_date` (`payment_date`),
  KEY `idx_payment_type` (`payment_type`),
  KEY `installment_id` (`installment_id`),
  CONSTRAINT `loan_payments_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`),
  CONSTRAINT `loan_payments_ibfk_2` FOREIGN KEY (`installment_id`) REFERENCES `loan_installments` (`id`),
  CONSTRAINT `chk_payments_positive` CHECK (`total_amount` > 0 and `principal_component` >= 0 and `interest_component` >= 0 and `fine_component` >= 0 and `excess_amount` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: loan_products
DROP TABLE IF EXISTS `loan_products`;
CREATE TABLE `loan_products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_code` varchar(20) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `min_amount` decimal(15,2) NOT NULL,
  `max_amount` decimal(15,2) NOT NULL,
  `min_tenure_months` int(11) NOT NULL,
  `max_tenure_months` int(11) NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL COMMENT 'Annual interest rate',
  `min_interest_rate` decimal(5,2) DEFAULT 0.00,
  `max_interest_rate` decimal(5,2) DEFAULT 50.00,
  `default_interest_rate` decimal(5,2) DEFAULT NULL,
  `interest_type` enum('flat','reducing','reducing_monthly') DEFAULT 'reducing',
  `processing_fee_type` enum('fixed','percentage') DEFAULT 'percentage',
  `processing_fee_value` decimal(10,2) DEFAULT 1.00,
  `late_fee_type` enum('fixed','percentage','per_day','fixed_plus_daily') DEFAULT 'fixed',
  `late_fee_value` decimal(10,2) DEFAULT 0.00,
  `late_fee_per_day` decimal(10,2) DEFAULT 0.00,
  `grace_period_days` int(11) DEFAULT 5,
  `prepayment_allowed` tinyint(1) DEFAULT 1,
  `prepayment_penalty_percent` decimal(5,2) DEFAULT 0.00,
  `min_guarantors` int(11) DEFAULT 2,
  `max_guarantors` int(11) DEFAULT 3,
  `required_savings_months` int(11) DEFAULT 6 COMMENT 'Min months of savings required',
  `max_loan_to_savings_ratio` decimal(5,2) DEFAULT 3.00 COMMENT 'Max loan = savings * ratio',
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_product_code` (`product_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: loan_transaction_details
DROP TABLE IF EXISTS `loan_transaction_details`;
CREATE TABLE `loan_transaction_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loan_id` int(11) DEFAULT NULL,
  `period` varchar(50) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `emi` decimal(10,2) DEFAULT NULL,
  `principal` decimal(10,2) DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT NULL,
  `interest` decimal(10,2) DEFAULT NULL,
  `fee_fine` decimal(10,2) DEFAULT NULL,
  `excess_fee` decimal(10,2) DEFAULT NULL,
  `only_interest` decimal(10,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `admin_remarks` text DEFAULT NULL,
  `date` date DEFAULT NULL,
  `loan_flag` int(11) DEFAULT 0,
  `is_deleted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `loan_id` (`loan_id`),
  CONSTRAINT `loan_transaction_details_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loan_transactions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: loan_transactions
DROP TABLE IF EXISTS `loan_transactions`;
CREATE TABLE `loan_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reason` text DEFAULT NULL,
  `total_loan` decimal(10,2) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `monthly_emi` decimal(10,2) DEFAULT NULL,
  `created_by` varchar(255) DEFAULT NULL,
  `updated_by` varchar(255) DEFAULT NULL,
  `interest` decimal(5,2) DEFAULT NULL,
  `period` int(11) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `processing_fee` decimal(10,2) DEFAULT NULL,
  `total_repayment` decimal(10,2) DEFAULT NULL,
  `emi_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`emi_details`)),
  `is_deleted` tinyint(1) DEFAULT 0,
  `member_id` varchar(50) DEFAULT NULL,
  `loan_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `loan_transactions_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `member_details` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: loans
DROP TABLE IF EXISTS `loans`;
CREATE TABLE `loans` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `loan_number` varchar(30) NOT NULL,
  `loan_application_id` int(10) unsigned NOT NULL,
  `member_id` int(10) unsigned NOT NULL,
  `loan_product_id` int(10) unsigned NOT NULL,
  `principal_amount` decimal(15,2) NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL,
  `interest_type` enum('flat','reducing','reducing_monthly') NOT NULL,
  `tenure_months` int(11) NOT NULL,
  `emi_amount` decimal(15,2) NOT NULL,
  `total_interest` decimal(15,2) NOT NULL,
  `total_payable` decimal(15,2) NOT NULL,
  `processing_fee` decimal(15,2) DEFAULT 0.00,
  `net_disbursement` decimal(15,2) NOT NULL COMMENT 'principal - processing_fee',
  `outstanding_principal` decimal(15,2) NOT NULL,
  `outstanding_interest` decimal(15,2) NOT NULL,
  `outstanding_fine` decimal(15,2) DEFAULT 0.00,
  `total_amount_paid` decimal(15,2) DEFAULT 0.00,
  `total_principal_paid` decimal(15,2) DEFAULT 0.00,
  `total_interest_paid` decimal(15,2) DEFAULT 0.00,
  `total_fine_paid` decimal(15,2) DEFAULT 0.00,
  `disbursement_date` date NOT NULL,
  `first_emi_date` date NOT NULL,
  `last_emi_date` date NOT NULL,
  `closure_date` date DEFAULT NULL,
  `status` enum('active','closed','foreclosed','written_off','npa') DEFAULT 'active',
  `closure_type` enum('regular','foreclosure','write_off','settlement') DEFAULT NULL,
  `closure_remarks` varchar(255) DEFAULT NULL,
  `closed_by` int(10) unsigned DEFAULT NULL,
  `is_npa` tinyint(1) DEFAULT 0,
  `npa_date` date DEFAULT NULL,
  `npa_category` enum('substandard','doubtful','loss') DEFAULT NULL,
  `days_overdue` int(11) DEFAULT 0,
  `disbursement_mode` enum('cash','bank_transfer','cheque') DEFAULT 'bank_transfer',
  `disbursement_reference` varchar(50) DEFAULT NULL,
  `disbursement_bank_account` varchar(50) DEFAULT NULL,
  `disbursed_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_loan_number` (`loan_number`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_status` (`status`),
  KEY `idx_disbursement_date` (`disbursement_date`),
  KEY `idx_is_npa` (`is_npa`),
  KEY `loan_application_id` (`loan_application_id`),
  KEY `loan_product_id` (`loan_product_id`),
  CONSTRAINT `loans_ibfk_1` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`),
  CONSTRAINT `loans_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `loans_ibfk_3` FOREIGN KEY (`loan_product_id`) REFERENCES `loan_products` (`id`),
  CONSTRAINT `chk_loans_positive_amounts` CHECK (`principal_amount` >= 0 and `outstanding_principal` >= 0 and `outstanding_interest` >= 0 and `outstanding_fine` >= 0 and `emi_amount` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: member_code_sequence
DROP TABLE IF EXISTS `member_code_sequence`;
CREATE TABLE `member_code_sequence` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `prefix` varchar(10) DEFAULT 'MEM',
  `current_number` int(10) unsigned DEFAULT 0,
  `year` year(4) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: member_details
DROP TABLE IF EXISTS `member_details`;
CREATE TABLE `member_details` (
  `member_id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `aadhar_card` varchar(20) DEFAULT NULL,
  `pan_card` varchar(20) DEFAULT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `bank_address` text DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `shares_flag` int(11) DEFAULT 0,
  `is_deleted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`member_id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `aadhar_card` (`aadhar_card`),
  UNIQUE KEY `pan_card` (`pan_card`),
  UNIQUE KEY `account_number` (`account_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: member_ledger
DROP TABLE IF EXISTS `member_ledger`;
CREATE TABLE `member_ledger` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL,
  `transaction_date` date NOT NULL,
  `transaction_type` varchar(50) NOT NULL COMMENT 'savings_deposit, loan_disbursement, etc.',
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(10) unsigned DEFAULT NULL,
  `debit_amount` decimal(15,2) DEFAULT 0.00,
  `credit_amount` decimal(15,2) DEFAULT 0.00,
  `balance_after` decimal(15,2) NOT NULL,
  `narration` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_transaction_date` (`transaction_date`),
  KEY `idx_reference` (`reference_type`,`reference_id`),
  CONSTRAINT `member_ledger_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: members
DROP TABLE IF EXISTS `members`;
CREATE TABLE `members` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_code` varchar(20) NOT NULL COMMENT 'Unique Member ID like MEM-0001',
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `alternate_phone` varchar(20) DEFAULT NULL,
  `address_line1` varchar(255) DEFAULT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL COMMENT 'Profile photo path',
  `aadhaar_number` varchar(12) DEFAULT NULL,
  `pan_number` varchar(10) DEFAULT NULL,
  `voter_id` varchar(20) DEFAULT NULL,
  `aadhaar_doc` varchar(255) DEFAULT NULL,
  `pan_doc` varchar(255) DEFAULT NULL,
  `address_proof_doc` varchar(255) DEFAULT NULL,
  `kyc_verified` tinyint(1) DEFAULT 0,
  `kyc_verified_at` timestamp NULL DEFAULT NULL,
  `kyc_verified_by` int(10) unsigned DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_branch` varchar(100) DEFAULT NULL,
  `account_number` varchar(30) DEFAULT NULL,
  `ifsc_code` varchar(15) DEFAULT NULL,
  `account_holder_name` varchar(100) DEFAULT NULL,
  `join_date` date NOT NULL,
  `membership_type` enum('regular','premium','founder') DEFAULT 'regular',
  `opening_balance` decimal(15,2) DEFAULT 0.00 COMMENT 'Initial ledger balance',
  `opening_balance_type` enum('credit','debit') DEFAULT 'credit',
  `status` enum('active','inactive','blocked','suspended') DEFAULT 'active',
  `status_reason` varchar(255) DEFAULT NULL,
  `status_changed_at` timestamp NULL DEFAULT NULL,
  `status_changed_by` int(10) unsigned DEFAULT NULL,
  `nominee_name` varchar(100) DEFAULT NULL,
  `nominee_relation` varchar(50) DEFAULT NULL,
  `nominee_phone` varchar(20) DEFAULT NULL,
  `nominee_aadhaar` varchar(12) DEFAULT NULL,
  `max_guarantee_amount` decimal(15,2) DEFAULT 100000.00,
  `max_guarantee_count` int(11) DEFAULT 3,
  `password` varchar(255) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT 'Soft delete',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_member_code` (`member_code`),
  UNIQUE KEY `idx_phone` (`phone`),
  UNIQUE KEY `idx_aadhaar` (`aadhaar_number`),
  UNIQUE KEY `idx_pan` (`pan_number`),
  KEY `idx_status` (`status`),
  KEY `idx_join_date` (`join_date`),
  KEY `idx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: notifications
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `recipient_type` enum('admin','member') NOT NULL,
  `recipient_id` int(10) unsigned NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `is_sent` tinyint(1) DEFAULT 0 COMMENT 'SMS/Email sent',
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_recipient` (`recipient_type`,`recipient_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: other_member_details
DROP TABLE IF EXISTS `other_member_details`;
CREATE TABLE `other_member_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `member_fee` decimal(10,2) DEFAULT NULL,
  `other_fee` decimal(10,2) DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: password_history
DROP TABLE IF EXISTS `password_history`;
CREATE TABLE `password_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`,`created_at`),
  CONSTRAINT `password_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Track password history to prevent reuse';

-- Table: requests_status
DROP TABLE IF EXISTS `requests_status`;
CREATE TABLE `requests_status` (
  `id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: rule_code_sequence
DROP TABLE IF EXISTS `rule_code_sequence`;
CREATE TABLE `rule_code_sequence` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `prefix` varchar(10) NOT NULL DEFAULT 'FR',
  `current_number` int(10) unsigned NOT NULL DEFAULT 0,
  `year` year(4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_prefix_year` (`prefix`,`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: savings_accounts
DROP TABLE IF EXISTS `savings_accounts`;
CREATE TABLE `savings_accounts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `account_number` varchar(20) NOT NULL,
  `member_id` int(10) unsigned NOT NULL,
  `scheme_id` int(10) unsigned NOT NULL,
  `monthly_amount` decimal(15,2) NOT NULL COMMENT 'Can override scheme default',
  `start_date` date NOT NULL,
  `maturity_date` date DEFAULT NULL,
  `total_deposited` decimal(15,2) DEFAULT 0.00,
  `total_interest_earned` decimal(15,2) DEFAULT 0.00,
  `total_fines_paid` decimal(15,2) DEFAULT 0.00,
  `current_balance` decimal(15,2) DEFAULT 0.00,
  `status` enum('active','matured','closed','suspended') DEFAULT 'active',
  `closed_at` timestamp NULL DEFAULT NULL,
  `closed_reason` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_account_number` (`account_number`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_scheme_id` (`scheme_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `savings_accounts_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `savings_accounts_ibfk_2` FOREIGN KEY (`scheme_id`) REFERENCES `savings_schemes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: savings_schedule
DROP TABLE IF EXISTS `savings_schedule`;
CREATE TABLE `savings_schedule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `savings_account_id` int(10) unsigned NOT NULL,
  `due_month` date NOT NULL COMMENT 'First day of month',
  `due_amount` decimal(15,2) NOT NULL,
  `paid_amount` decimal(15,2) DEFAULT 0.00,
  `fine_amount` decimal(15,2) DEFAULT 0.00,
  `fine_paid` decimal(15,2) DEFAULT 0.00,
  `due_date` date NOT NULL,
  `paid_date` date DEFAULT NULL,
  `status` enum('pending','partial','paid','overdue','waived') DEFAULT 'pending',
  `is_late` tinyint(1) DEFAULT 0,
  `days_late` int(11) DEFAULT 0,
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_account_month` (`savings_account_id`,`due_month`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_status` (`status`),
  CONSTRAINT `savings_schedule_ibfk_1` FOREIGN KEY (`savings_account_id`) REFERENCES `savings_accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: savings_schemes
DROP TABLE IF EXISTS `savings_schemes`;
CREATE TABLE `savings_schemes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `scheme_code` varchar(20) NOT NULL,
  `scheme_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `min_deposit` decimal(15,2) DEFAULT 0.00,
  `deposit_frequency` enum('daily','weekly','monthly','quarterly','yearly','onetime') DEFAULT 'monthly',
  `lock_in_period` int(10) unsigned DEFAULT 0,
  `penalty_rate` decimal(5,2) DEFAULT 0.00,
  `maturity_bonus` decimal(5,2) DEFAULT 0.00,
  `monthly_amount` decimal(15,2) NOT NULL,
  `duration_months` int(10) unsigned DEFAULT NULL COMMENT 'NULL for indefinite',
  `interest_rate` decimal(5,2) DEFAULT 0.00 COMMENT 'Annual interest rate',
  `late_fine_type` enum('fixed','percentage','per_day') DEFAULT 'fixed',
  `late_fine_value` decimal(10,2) DEFAULT 0.00,
  `grace_period_days` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_scheme_code` (`scheme_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: savings_transactions
DROP TABLE IF EXISTS `savings_transactions`;
CREATE TABLE `savings_transactions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `transaction_code` varchar(30) NOT NULL,
  `savings_account_id` int(10) unsigned NOT NULL,
  `schedule_id` int(10) unsigned DEFAULT NULL COMMENT 'NULL for interest credits, withdrawals',
  `transaction_type` enum('deposit','withdrawal','interest_credit','fine','fine_waiver','adjustment','opening_balance') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `balance_after` decimal(15,2) NOT NULL,
  `payment_mode` enum('cash','bank_transfer','cheque','upi','auto','adjustment') DEFAULT 'cash',
  `reference_number` varchar(50) DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `for_month` date DEFAULT NULL COMMENT 'Which month this payment is for',
  `narration` varchar(255) DEFAULT NULL,
  `receipt_number` varchar(30) DEFAULT NULL,
  `bank_transaction_id` int(10) unsigned DEFAULT NULL COMMENT 'Link to imported bank transaction',
  `is_reversed` tinyint(1) DEFAULT 0,
  `reversed_at` timestamp NULL DEFAULT NULL,
  `reversed_by` int(10) unsigned DEFAULT NULL,
  `reversal_reason` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_transaction_code` (`transaction_code`),
  KEY `idx_savings_account_id` (`savings_account_id`),
  KEY `idx_transaction_date` (`transaction_date`),
  KEY `idx_transaction_type` (`transaction_type`),
  KEY `schedule_id` (`schedule_id`),
  CONSTRAINT `savings_transactions_ibfk_1` FOREIGN KEY (`savings_account_id`) REFERENCES `savings_accounts` (`id`),
  CONSTRAINT `savings_transactions_ibfk_2` FOREIGN KEY (`schedule_id`) REFERENCES `savings_schedule` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: schema_migrations
DROP TABLE IF EXISTS `schema_migrations`;
CREATE TABLE `schema_migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `filename` (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: security_logs
DROP TABLE IF EXISTS `security_logs`;
CREATE TABLE `security_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL COMMENT 'login_success, login_failed, login_locked, logout, password_change, etc.',
  `user_id` int(10) unsigned DEFAULT NULL COMMENT 'User ID if known',
  `email` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional event details' CHECK (json_valid(`details`)),
  `severity` enum('info','warning','critical') DEFAULT 'info',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_event_type` (`event_type`,`created_at`),
  KEY `idx_user_id` (`user_id`,`created_at`),
  KEY `idx_ip_address` (`ip_address`,`created_at`),
  KEY `idx_severity` (`severity`,`created_at`),
  KEY `idx_security_logs_user_event` (`user_id`,`event_type`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Security event audit trail - Retain for 7 years (RBI requirement)';

-- Table: send_form
DROP TABLE IF EXISTS `send_form`;
CREATE TABLE `send_form` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `send_form_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `member_details` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: shares
DROP TABLE IF EXISTS `shares`;
CREATE TABLE `shares` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `monthly_contribution` decimal(10,2) DEFAULT NULL,
  `total_contribution` decimal(10,2) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `bonus` decimal(10,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` varchar(255) DEFAULT NULL,
  `updated_by` varchar(255) DEFAULT NULL,
  `admin_remarks` text DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `member_id` varchar(50) DEFAULT NULL,
  `flag` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `shares_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `member_details` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: system_settings
DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
  `description` varchar(255) DEFAULT NULL,
  `is_editable` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: transaction_mappings
DROP TABLE IF EXISTS `transaction_mappings`;
CREATE TABLE `transaction_mappings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bank_transaction_id` int(10) unsigned NOT NULL,
  `member_id` int(10) unsigned NOT NULL,
  `mapping_type` enum('savings','loan_payment','fine','other') NOT NULL,
  `related_id` int(10) unsigned DEFAULT NULL COMMENT 'savings_transaction_id or loan_payment_id',
  `amount` decimal(15,2) NOT NULL,
  `for_month` date DEFAULT NULL COMMENT 'Applicable month',
  `narration` varchar(255) DEFAULT NULL,
  `mapped_by` int(10) unsigned DEFAULT NULL,
  `mapped_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_reversed` tinyint(1) DEFAULT 0,
  `reversed_at` timestamp NULL DEFAULT NULL,
  `reversed_by` int(10) unsigned DEFAULT NULL,
  `reversal_reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bank_transaction_id` (`bank_transaction_id`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_mapping_type` (`mapping_type`),
  CONSTRAINT `transaction_mappings_ibfk_1` FOREIGN KEY (`bank_transaction_id`) REFERENCES `bank_transactions` (`id`),
  CONSTRAINT `transaction_mappings_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: two_factor_auth
DROP TABLE IF EXISTS `two_factor_auth`;
CREATE TABLE `two_factor_auth` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `secret_key` varchar(255) NOT NULL COMMENT 'Encrypted TOTP secret',
  `backup_codes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Encrypted backup codes' CHECK (json_valid(`backup_codes`)),
  `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `enabled_at` datetime DEFAULT NULL,
  `last_used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `two_factor_auth_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Two-factor authentication configuration';

-- Table: view_requests
DROP TABLE IF EXISTS `view_requests`;
CREATE TABLE `view_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_from` varchar(50) DEFAULT NULL,
  `member_to` varchar(50) DEFAULT NULL,
  `admin_reason` text DEFAULT NULL,
  `guarantor_reason` text DEFAULT NULL,
  `status` int(11) DEFAULT 1,
  `created_by` varchar(50) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `period` int(11) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `emi` decimal(10,2) DEFAULT NULL,
  `processing_fee` decimal(10,2) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `member_from` (`member_from`),
  KEY `member_to` (`member_to`),
  KEY `status` (`status`),
  CONSTRAINT `view_requests_ibfk_1` FOREIGN KEY (`member_from`) REFERENCES `member_details` (`member_id`),
  CONSTRAINT `view_requests_ibfk_2` FOREIGN KEY (`member_to`) REFERENCES `member_details` (`member_id`),
  CONSTRAINT `view_requests_ibfk_3` FOREIGN KEY (`status`) REFERENCES `requests_status` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

-- ============================================
-- Export completed successfully!
-- Total tables: 50
-- File ready for shared hosting import
-- ============================================
