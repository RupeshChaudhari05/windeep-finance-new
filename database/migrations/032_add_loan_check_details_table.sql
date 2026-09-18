-- Migration 032: Create loan_check_details table
-- Date: 2026-09-18
-- Purpose: Support admin-side loan check collection and verification workflow.

CREATE TABLE IF NOT EXISTS `loan_check_details` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `loan_application_id` int(10) unsigned NOT NULL,
  `loan_id` int(10) unsigned DEFAULT NULL,
  `member_id` int(10) unsigned NOT NULL,
  `check_type` enum('applicant','guarantor') NOT NULL DEFAULT 'applicant',
  `guarantor_id` int(10) unsigned DEFAULT NULL,
  `check_number` varchar(30) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `ifsc_code` varchar(15) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `check_date` date NOT NULL,
  `status` enum('pending','verified','encashed','bounced','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `verified_by` int(10) unsigned DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_loan_application` (`loan_application_id`),
  KEY `idx_loan_id` (`loan_id`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_guarantor_id` (`guarantor_id`),
  KEY `idx_check_type` (`check_type`),
  KEY `idx_status` (`status`),
  KEY `idx_check_date` (`check_date`),
  KEY `idx_bank_ifsc` (`bank_name`, `ifsc_code`),
  KEY `idx_check_number` (`check_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores check details for loan applications';
