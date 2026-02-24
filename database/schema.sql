-- ============================================
-- WINDEEP FINANCE - BANKING & LOAN MANAGEMENT SYSTEM
-- Complete Database Schema
-- Version: 1.0.0
-- Created: 2025-12-26
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

START TRANSACTION;

SET time_zone = "+05:30";

-- ============================================
-- SYSTEM CONFIGURATION TABLES
-- ============================================

-- System Settings
CREATE TABLE IF NOT EXISTS `system_settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT,
    `setting_type` ENUM(
        'string',
        'number',
        'boolean',
        'json'
    ) DEFAULT 'string',
    `description` VARCHAR(255),
    `is_editable` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_setting_key` (`setting_key`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Financial Year
CREATE TABLE IF NOT EXISTS `financial_years` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `year_code` VARCHAR(20) NOT NULL COMMENT 'e.g., 2025-26',
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `is_active` TINYINT(1) DEFAULT 0,
    `is_closed` TINYINT(1) DEFAULT 0,
    `closed_at` TIMESTAMP NULL,
    `closed_by` INT UNSIGNED NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_year_code` (`year_code`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================
-- USER & ADMIN TABLES
-- ============================================

-- Admin Users
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20),
    `role` ENUM(
        'super_admin',
        'admin',
        'manager',
        'accountant',
        'viewer'
    ) DEFAULT 'viewer',
    `permissions` JSON COMMENT 'Granular permissions JSON',
    `is_active` TINYINT(1) DEFAULT 1,
    `last_login` TIMESTAMP NULL,
    `last_login_ip` VARCHAR(45),
    `password_changed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_username` (`username`),
    UNIQUE KEY `idx_email` (`email`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Admin Sessions
CREATE TABLE IF NOT EXISTS `admin_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `admin_id` INT UNSIGNED NOT NULL,
    `session_token` VARCHAR(255) NOT NULL,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `expires_at` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_admin_id` (`admin_id`),
    KEY `idx_session_token` (`session_token`),
    FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================
-- MEMBER MANAGEMENT TABLES
-- ============================================

-- Members Master
CREATE TABLE IF NOT EXISTS `members` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `member_code` VARCHAR(20) NOT NULL COMMENT 'Unique Member ID like MEM-0001',
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `father_name` VARCHAR(100),
    `date_of_birth` DATE,
    `gender` ENUM('male', 'female', 'other'),
    `email` VARCHAR(100),
    `phone` VARCHAR(20) NOT NULL,
    `alternate_phone` VARCHAR(20),
    `address_line1` VARCHAR(255),
    `address_line2` VARCHAR(255),
    `city` VARCHAR(100),
    `state` VARCHAR(100),
    `pincode` VARCHAR(10),
    `photo` VARCHAR(255) COMMENT 'Profile photo path',

-- KYC Fields
`aadhaar_number` VARCHAR(12),
`pan_number` VARCHAR(10),
`voter_id` VARCHAR(20),
`aadhaar_doc` VARCHAR(255),
`pan_doc` VARCHAR(255),
`address_proof_doc` VARCHAR(255),
`kyc_verified` TINYINT(1) DEFAULT 0,
`kyc_verified_at` TIMESTAMP NULL,
`kyc_verified_by` INT UNSIGNED NULL,

-- Bank Details
`bank_name` VARCHAR(100),
`bank_branch` VARCHAR(100),
`account_number` VARCHAR(30),
`ifsc_code` VARCHAR(15),
`account_holder_name` VARCHAR(100),

-- Membership Details
`join_date` DATE NOT NULL,
`membership_type` ENUM(
    'regular',
    'premium',
    'founder'
) DEFAULT 'regular',
`opening_balance` DECIMAL(15, 2) DEFAULT 0.00 COMMENT 'Initial ledger balance',
`opening_balance_type` ENUM('credit', 'debit') DEFAULT 'credit',

-- Status
`status` ENUM(
    'active',
    'inactive',
    'blocked',
    'suspended'
) DEFAULT 'active',
`status_reason` VARCHAR(255),
`status_changed_at` TIMESTAMP NULL,
`status_changed_by` INT UNSIGNED NULL,

-- Nominee Details
`nominee_name` VARCHAR(100),
`nominee_relation` VARCHAR(50),
`nominee_phone` VARCHAR(20),
`nominee_aadhaar` VARCHAR(12),

-- Guarantor Limits
`max_guarantee_amount` DECIMAL(15, 2) DEFAULT 100000.00,
`max_guarantee_count` INT DEFAULT 3,

-- Password for member portal
`password` VARCHAR(255), `last_login` TIMESTAMP NULL,

-- Metadata


`notes` TEXT,
    `created_by` INT UNSIGNED,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL COMMENT 'Soft delete',
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_member_code` (`member_code`),
    KEY `idx_phone` (`phone`),
    UNIQUE KEY `idx_aadhaar` (`aadhaar_number`),
    UNIQUE KEY `idx_pan` (`pan_number`),
    KEY `idx_status` (`status`),
    KEY `idx_join_date` (`join_date`),
    KEY `idx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Member Code Sequence
CREATE TABLE IF NOT EXISTS `member_code_sequence` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `prefix` VARCHAR(10) DEFAULT 'MEM',
    `current_number` INT UNSIGNED DEFAULT 0,
    `year` YEAR,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================
-- SAVINGS MODULE TABLES
-- ============================================

-- Savings Schemes
CREATE TABLE IF NOT EXISTS `savings_schemes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `scheme_code` VARCHAR(20) NOT NULL,
    `scheme_name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `min_deposit` DECIMAL(15, 2) DEFAULT 0.00,
    `deposit_frequency` ENUM(
        'daily',
        'weekly',
        'monthly',
        'quarterly',
        'yearly',
        'onetime'
    ) DEFAULT 'monthly',
    `lock_in_period` INT UNSIGNED DEFAULT 0,
    `penalty_rate` DECIMAL(5, 2) DEFAULT 0.00,
    `maturity_bonus` DECIMAL(5, 2) DEFAULT 0.00,
    `monthly_amount` DECIMAL(15, 2) NOT NULL,
    `duration_months` INT UNSIGNED COMMENT 'NULL for indefinite',
    `interest_rate` DECIMAL(5, 2) DEFAULT 0.00 COMMENT 'Annual interest rate',
    `late_fine_type` ENUM(
        'fixed',
        'percentage',
        'per_day'
    ) DEFAULT 'fixed',
    `late_fine_value` DECIMAL(10, 2) DEFAULT 0.00,
    `grace_period_days` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_by` INT UNSIGNED,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_scheme_code` (`scheme_code`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Member Savings Accounts
CREATE TABLE IF NOT EXISTS `savings_accounts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `account_number` VARCHAR(20) NOT NULL,
    `member_id` INT UNSIGNED NOT NULL,
    `scheme_id` INT UNSIGNED NOT NULL,
    `monthly_amount` DECIMAL(15, 2) NOT NULL COMMENT 'Can override scheme default',
    `start_date` DATE NOT NULL,
    `maturity_date` DATE,
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
    `closed_at` TIMESTAMP NULL,
    `closed_reason` VARCHAR(255),
    `created_by` INT UNSIGNED,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_account_number` (`account_number`),
    KEY `idx_member_id` (`member_id`),
    KEY `idx_scheme_id` (`scheme_id`),
    KEY `idx_status` (`status`),
    FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
    FOREIGN KEY (`scheme_id`) REFERENCES `savings_schemes` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Savings Schedule (Monthly dues)
CREATE TABLE IF NOT EXISTS `savings_schedule` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `savings_account_id` INT UNSIGNED NOT NULL,
    `due_month` DATE NOT NULL COMMENT 'First day of month',
    `due_amount` DECIMAL(15, 2) NOT NULL,
    `paid_amount` DECIMAL(15, 2) DEFAULT 0.00,
    `fine_amount` DECIMAL(15, 2) DEFAULT 0.00,
    `fine_paid` DECIMAL(15, 2) DEFAULT 0.00,
    `due_date` DATE NOT NULL,
    `paid_date` DATE,
    `status` ENUM(
        'pending',
        'partial',
        'paid',
        'overdue',
        'waived'
    ) DEFAULT 'pending',
    `is_late` TINYINT(1) DEFAULT 0,
    `days_late` INT DEFAULT 0,
    `remarks` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_account_month` (
        `savings_account_id`,
        `due_month`
    ),
    KEY `idx_due_date` (`due_date`),
    KEY `idx_status` (`status`),
    FOREIGN KEY (`savings_account_id`) REFERENCES `savings_accounts` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Savings Transactions
CREATE TABLE IF NOT EXISTS `savings_transactions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `transaction_code` VARCHAR(30) NOT NULL,
    `savings_account_id` INT UNSIGNED NOT NULL,
    `schedule_id` INT UNSIGNED COMMENT 'NULL for interest credits, withdrawals',
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
    `reference_number` VARCHAR(50),
    `transaction_date` DATE NOT NULL,
    `for_month` DATE COMMENT 'Which month this payment is for',
    `narration` VARCHAR(255),
    `receipt_number` VARCHAR(30),
    `bank_transaction_id` INT UNSIGNED COMMENT 'Link to imported bank transaction',
    `is_reversed` TINYINT(1) DEFAULT 0,
    `reversed_at` TIMESTAMP NULL,
    `reversed_by` INT UNSIGNED,
    `reversal_reason` VARCHAR(255),
    `created_by` INT UNSIGNED,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_transaction_code` (`transaction_code`),
    KEY `idx_savings_account_id` (`savings_account_id`),
    KEY `idx_transaction_date` (`transaction_date`),
    KEY `idx_transaction_type` (`transaction_type`),
    FOREIGN KEY (`savings_account_id`) REFERENCES `savings_accounts` (`id`),
    FOREIGN KEY (`schedule_id`) REFERENCES `savings_schedule` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================
-- LOAN MODULE TABLES
-- ============================================

-- Loan Products/Schemes
CREATE TABLE IF NOT EXISTS `loan_products` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_code` VARCHAR(20) NOT NULL,
    `product_name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `min_amount` DECIMAL(15, 2) NOT NULL,
    `max_amount` DECIMAL(15, 2) NOT NULL,
    `min_tenure_months` INT NOT NULL,
    `max_tenure_months` INT NOT NULL,
    `interest_rate` DECIMAL(5, 2) NOT NULL COMMENT 'Annual interest rate',
    `interest_type` ENUM(
        'flat',
        'reducing',
        'reducing_monthly'
    ) DEFAULT 'reducing',
    `processing_fee_type` ENUM('fixed', 'percentage') DEFAULT 'percentage',
    `processing_fee_value` DECIMAL(10, 2) DEFAULT 1.00,
    `prepayment_allowed` TINYINT(1) DEFAULT 1,
    `prepayment_penalty_percent` DECIMAL(5, 2) DEFAULT 0.00,
    `min_guarantors` INT DEFAULT 2,
    `max_guarantors` INT DEFAULT 3,
    `required_savings_months` INT DEFAULT 6 COMMENT 'Min months of savings required',
    `max_loan_to_savings_ratio` DECIMAL(5, 2) DEFAULT 3.00 COMMENT 'Max loan = savings * ratio',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_by` INT UNSIGNED,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_product_code` (`product_code`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Loan Applications
CREATE TABLE IF NOT EXISTS `loan_applications` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `application_number` VARCHAR(30) NOT NULL,
    `member_id` INT UNSIGNED NOT NULL,
    `loan_product_id` INT UNSIGNED NOT NULL,

-- Requested Details
`requested_amount` DECIMAL(15, 2) NOT NULL,
`requested_tenure_months` INT NOT NULL,
`requested_interest_rate` DECIMAL(5, 2),
`purpose` VARCHAR(255) NOT NULL,
`purpose_details` TEXT,

-- Admin Revised Details
`approved_amount` DECIMAL(15, 2),
`approved_tenure_months` INT,
`approved_interest_rate` DECIMAL(5, 2),
`revision_remarks` TEXT,
`revised_at` TIMESTAMP NULL,
`revised_by` INT UNSIGNED,

-- Status Flow
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
    'expired',
    'needs_revision'
) DEFAULT 'draft',
`status_remarks` VARCHAR(255),

-- Approval Tracking
`admin_approved_at` TIMESTAMP NULL,
`admin_approved_by` INT UNSIGNED,
`member_approved_at` TIMESTAMP NULL,
`rejection_reason` TEXT,
`rejected_at` TIMESTAMP NULL,
`rejected_by` INT UNSIGNED,

-- Eligibility Snapshot
`member_savings_balance` DECIMAL(15, 2) COMMENT 'At time of application',
`member_existing_loans` INT DEFAULT 0,
`member_existing_loan_balance` DECIMAL(15, 2) DEFAULT 0.00,
`eligibility_score` INT,

-- Metadata


`application_date` DATE NOT NULL,
    `expiry_date` DATE COMMENT 'Application expires if not processed',
    `documents` JSON COMMENT 'Uploaded documents',
    `created_by` INT UNSIGNED,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_application_number` (`application_number`),
    KEY `idx_member_id` (`member_id`),
    KEY `idx_status` (`status`),
    KEY `idx_application_date` (`application_date`),
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`),
    FOREIGN KEY (`loan_product_id`) REFERENCES `loan_products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Loan Guarantors
CREATE TABLE IF NOT EXISTS `loan_guarantors` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `loan_application_id` INT UNSIGNED NOT NULL,
    `loan_id` INT UNSIGNED COMMENT 'Set after loan is disbursed',
    `guarantor_member_id` INT UNSIGNED NOT NULL,
    `guarantee_amount` DECIMAL(15,2) NOT NULL COMMENT 'Liability amount',
    `relationship` VARCHAR(50),

-- Consent Status
`consent_status` ENUM(
    'pending',
    'accepted',
    'rejected',
    'withdrawn'
) DEFAULT 'pending',
`consent_date` TIMESTAMP NULL,
`consent_ip` VARCHAR(45),
`consent_remarks` VARCHAR(255),
`rejection_reason` VARCHAR(255),

-- Release (after loan closure)


`is_released` TINYINT(1) DEFAULT 0,
    `released_at` TIMESTAMP NULL,
    `released_by` INT UNSIGNED,
    
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_application_guarantor` (`loan_application_id`, `guarantor_member_id`),
    KEY `idx_guarantor_member` (`guarantor_member_id`),
    KEY `idx_loan_id` (`loan_id`),
    KEY `idx_consent_status` (`consent_status`),
    FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications`(`id`),
    FOREIGN KEY (`guarantor_member_id`) REFERENCES `members`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Loans (Disbursed)
CREATE TABLE IF NOT EXISTS `loans` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `loan_number` VARCHAR(30) NOT NULL,
    `loan_application_id` INT UNSIGNED NOT NULL,
    `member_id` INT UNSIGNED NOT NULL,
    `loan_product_id` INT UNSIGNED NOT NULL,

-- Loan Terms
`principal_amount` DECIMAL(15, 2) NOT NULL,
`interest_rate` DECIMAL(5, 2) NOT NULL,
`interest_type` ENUM(
    'flat',
    'reducing',
    'reducing_monthly'
) NOT NULL,
`tenure_months` INT NOT NULL,
`emi_amount` DECIMAL(15, 2) NOT NULL,

-- Calculated Totals
`total_interest` DECIMAL(15, 2) NOT NULL,
`total_payable` DECIMAL(15, 2) NOT NULL,
`processing_fee` DECIMAL(15, 2) DEFAULT 0.00,
`net_disbursement` DECIMAL(15, 2) NOT NULL COMMENT 'principal - processing_fee',

-- Current Status
`outstanding_principal` DECIMAL(15, 2) NOT NULL,
`outstanding_interest` DECIMAL(15, 2) NOT NULL,
`outstanding_fine` DECIMAL(15, 2) DEFAULT 0.00,
`total_amount_paid` DECIMAL(15, 2) DEFAULT 0.00,
`total_principal_paid` DECIMAL(15, 2) DEFAULT 0.00,
`total_interest_paid` DECIMAL(15, 2) DEFAULT 0.00,
`total_fine_paid` DECIMAL(15, 2) DEFAULT 0.00,

-- Dates
`disbursement_date` DATE NOT NULL,
`first_emi_date` DATE NOT NULL,
`last_emi_date` DATE NOT NULL,
`closure_date` DATE,

-- Status
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
),
`closure_remarks` VARCHAR(255),
`closed_by` INT UNSIGNED,

-- NPA Tracking
`is_npa` TINYINT(1) DEFAULT 0,
`npa_date` DATE,
`npa_category` ENUM(
    'substandard',
    'doubtful',
    'loss'
),
`days_overdue` INT DEFAULT 0,

-- Disbursement Details


`disbursement_mode` ENUM('cash', 'bank_transfer', 'cheque') DEFAULT 'bank_transfer',
    `disbursement_reference` VARCHAR(50),
    `disbursement_bank_account` VARCHAR(50),
    `disbursed_by` INT UNSIGNED,
    
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_loan_number` (`loan_number`),
    KEY `idx_member_id` (`member_id`),
    KEY `idx_status` (`status`),
    KEY `idx_disbursement_date` (`disbursement_date`),
    KEY `idx_is_npa` (`is_npa`),
    FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications`(`id`),
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`),
    FOREIGN KEY (`loan_product_id`) REFERENCES `loan_products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Loan Installments (EMI Schedule)
CREATE TABLE IF NOT EXISTS `loan_installments` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `loan_id` INT UNSIGNED NOT NULL,
    `installment_number` INT NOT NULL,
    `due_date` DATE NOT NULL,
    `principal_amount` DECIMAL(15,2) NOT NULL,
    `interest_amount` DECIMAL(15,2) NOT NULL,
    `emi_amount` DECIMAL(15,2) NOT NULL,
    `outstanding_principal_before` DECIMAL(15,2) NOT NULL,
    `outstanding_principal_after` DECIMAL(15,2) NOT NULL,

-- Payment Tracking
`principal_paid` DECIMAL(15, 2) DEFAULT 0.00,
`interest_paid` DECIMAL(15, 2) DEFAULT 0.00,
`fine_amount` DECIMAL(15, 2) DEFAULT 0.00,
`fine_paid` DECIMAL(15, 2) DEFAULT 0.00,
`total_paid` DECIMAL(15, 2) DEFAULT 0.00,

-- Status
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
`paid_date` DATE,
`is_late` TINYINT(1) DEFAULT 0,
`days_late` INT DEFAULT 0,

-- Skip/Adjustment


`is_skipped` TINYINT(1) DEFAULT 0,
    `skip_reason` VARCHAR(255),
    `skipped_by` INT UNSIGNED,
    `is_adjusted` TINYINT(1) DEFAULT 0,
    `adjustment_remarks` VARCHAR(255),
    
    `remarks` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_loan_installment` (`loan_id`, `installment_number`),
    KEY `idx_due_date` (`due_date`),
    KEY `idx_status` (`status`),
    FOREIGN KEY (`loan_id`) REFERENCES `loans`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Loan Payments/Transactions


CREATE TABLE IF NOT EXISTS `loan_payments` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `payment_code` VARCHAR(30) NOT NULL,
    `loan_id` INT UNSIGNED NOT NULL,
    `installment_id` INT UNSIGNED COMMENT 'Can be NULL for prepayments',
    
    `payment_type` ENUM('emi', 'part_payment', 'advance_payment', 'interest_only', 'fine_payment', 'foreclosure', 'adjustment', 'reversal') NOT NULL,
    `payment_date` DATE NOT NULL,

-- Amount Breakdown
`total_amount` DECIMAL(15, 2) NOT NULL,
`principal_component` DECIMAL(15, 2) DEFAULT 0.00,
`interest_component` DECIMAL(15, 2) DEFAULT 0.00,
`fine_component` DECIMAL(15, 2) DEFAULT 0.00,
`excess_amount` DECIMAL(15, 2) DEFAULT 0.00 COMMENT 'Advance payment',

-- Balances After
`outstanding_principal_after` DECIMAL(15, 2) NOT NULL,
`outstanding_interest_after` DECIMAL(15, 2) NOT NULL,

-- Payment Details
`payment_mode` ENUM(
    'cash',
    'bank_transfer',
    'cheque',
    'upi',
    'auto_debit',
    'adjustment'
) DEFAULT 'cash',
`reference_number` VARCHAR(50),
`receipt_number` VARCHAR(30),
`cheque_number` VARCHAR(20),
`cheque_date` DATE,
`bank_name` VARCHAR(100),

-- Bank Import Link
`bank_transaction_id` INT UNSIGNED,

-- Reversal


`is_reversed` TINYINT(1) DEFAULT 0,
    `reversed_at` TIMESTAMP NULL,
    `reversed_by` INT UNSIGNED,
    `reversal_reason` VARCHAR(255),
    `reversal_payment_id` INT UNSIGNED COMMENT 'Link to reversal entry',
    
    `narration` VARCHAR(255),
    `created_by` INT UNSIGNED,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_payment_code` (`payment_code`),
    KEY `idx_loan_id` (`loan_id`),
    KEY `idx_payment_date` (`payment_date`),
    KEY `idx_payment_type` (`payment_type`),
    FOREIGN KEY (`loan_id`) REFERENCES `loans`(`id`),
    FOREIGN KEY (`installment_id`) REFERENCES `loan_installments`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- FINE & PENALTY TABLES
-- ============================================

-- Fine Rules Configuration
CREATE TABLE IF NOT EXISTS `fine_rules` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `rule_code` VARCHAR(20) NOT NULL,
    `rule_name` VARCHAR(100) NOT NULL,
    `applies_to` ENUM('savings', 'loan', 'both') DEFAULT 'both',
    `fine_type` ENUM(
        'loan_late',
        'savings_late',
        'meeting_absence',
        'document_missing',
        'other'
    ) NOT NULL,
    `calculation_type` ENUM(
        'fixed',
        'percentage',
        'per_day',
        'slab'
    ) NOT NULL DEFAULT 'fixed',
    `fine_value` DECIMAL(10, 2) NOT NULL COMMENT 'Fixed amount or percentage',
    `per_day_amount` DECIMAL(10, 2) DEFAULT 0.00,
    `max_fine_amount` DECIMAL(15, 2) COMMENT 'Cap on fine',
    `grace_period_days` INT DEFAULT 0,
    `slab_config` JSON COMMENT 'For slab-based fines',
    `is_active` TINYINT(1) DEFAULT 1,
    `effective_from` DATE NOT NULL,
    `effective_to` DATE,
    `created_by` INT UNSIGNED,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_rule_code` (`rule_code`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Rule Code Sequence (for auto-generating rule codes)
CREATE TABLE IF NOT EXISTS `rule_code_sequence` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `prefix` VARCHAR(10) NOT NULL DEFAULT 'FR',
    `current_number` INT UNSIGNED NOT NULL DEFAULT 0,
    `year` YEAR NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_prefix_year` (`prefix`, `year`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Fines Ledger


CREATE TABLE IF NOT EXISTS `fines` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `fine_code` VARCHAR(30) NOT NULL,
    `member_id` INT UNSIGNED NOT NULL,
    `fine_type` ENUM('savings_late', 'loan_late', 'bounced_cheque', 'other') NOT NULL,
    `related_type` ENUM('savings_schedule', 'loan_installment', 'other') NOT NULL,
    `related_id` INT UNSIGNED NOT NULL,
    `fine_rule_id` INT UNSIGNED,
    
    `fine_date` DATE NOT NULL,
    `due_date` DATE NOT NULL COMMENT 'Original due date',
    `days_late` INT NOT NULL,
    
    `fine_amount` DECIMAL(15,2) NOT NULL,
    `paid_amount` DECIMAL(15,2) DEFAULT 0.00,
    `waived_amount` DECIMAL(15,2) DEFAULT 0.00,
    `balance_amount` DECIMAL(15,2) NOT NULL,
    
    `status` ENUM('pending', 'partial', 'paid', 'waived', 'cancelled') DEFAULT 'pending',

-- Waiver Details


`waiver_requested_by` INT UNSIGNED NULL,
    `waiver_requested_at` TIMESTAMP NULL,
    `waiver_requested_amount` DECIMAL(15,2) NULL,
    `waiver_approved_by` INT UNSIGNED,
    `waiver_approved_at` TIMESTAMP NULL,
    `waiver_denied_by` INT UNSIGNED NULL,
    `waiver_denied_at` TIMESTAMP NULL,
    `waiver_denied_reason` VARCHAR(255) NULL,
    `admin_comments` TEXT,
    `waived_by` INT UNSIGNED,
    `waived_at` TIMESTAMP NULL,
    `waiver_reason` VARCHAR(255),
    
    `remarks` VARCHAR(255),
    `created_by` INT UNSIGNED,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_fine_code` (`fine_code`),
    KEY `idx_member_id` (`member_id`),
    KEY `idx_status` (`status`),
    KEY `idx_fine_date` (`fine_date`),
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`),
    FOREIGN KEY (`fine_rule_id`) REFERENCES `fine_rules`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- BANK STATEMENT IMPORT TABLES
-- ============================================

-- Bank Accounts (Organization's accounts)
CREATE TABLE IF NOT EXISTS `bank_accounts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `account_name` VARCHAR(100) NOT NULL,
    `bank_name` VARCHAR(100) NOT NULL,
    `branch_name` VARCHAR(100),
    `account_number` VARCHAR(30) NOT NULL,
    `ifsc_code` VARCHAR(15),
    `account_type` ENUM('current', 'savings', 'cash') DEFAULT 'current',
    `opening_balance` DECIMAL(15, 2) DEFAULT 0.00,
    `current_balance` DECIMAL(15, 2) DEFAULT 0.00,
    `is_active` TINYINT(1) DEFAULT 1,
    `is_primary` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_account_number` (`account_number`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Bank Statement Imports
CREATE TABLE IF NOT EXISTS `bank_statement_imports` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `import_code` VARCHAR(30) NOT NULL,
    `bank_account_id` INT UNSIGNED NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_type` ENUM('csv', 'excel', 'pdf') NOT NULL,
    `statement_from_date` DATE,
    `statement_to_date` DATE,
    `total_transactions` INT DEFAULT 0,
    `total_credits` DECIMAL(15, 2) DEFAULT 0.00,
    `total_debits` DECIMAL(15, 2) DEFAULT 0.00,
    `mapped_count` INT DEFAULT 0,
    `unmapped_count` INT DEFAULT 0,
    `status` ENUM(
        'uploaded',
        'parsing',
        'parsed',
        'mapping',
        'completed',
        'failed'
    ) DEFAULT 'uploaded',
    `error_message` TEXT,
    `imported_by` INT UNSIGNED,
    `imported_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `completed_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_import_code` (`import_code`),
    KEY `idx_bank_account_id` (`bank_account_id`),
    KEY `idx_status` (`status`),
    FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Bank Transactions (Parsed from statements)
CREATE TABLE IF NOT EXISTS `bank_transactions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `import_id` INT UNSIGNED NOT NULL,
    `bank_account_id` INT UNSIGNED NOT NULL,
    `transaction_date` DATE NOT NULL,
    `value_date` DATE,
    `transaction_type` ENUM('credit', 'debit') NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `balance_after` DECIMAL(15,2),
    `description` TEXT,
    `reference_number` VARCHAR(100),
    `utr_number` VARCHAR(50),
    `cheque_number` VARCHAR(20),

-- Mapping Status
`mapping_status` ENUM(
    'unmapped',
    'partial',
    'mapped',
    'ignored',
    'split'
) DEFAULT 'unmapped',
`mapped_amount` DECIMAL(15, 2) DEFAULT 0.00,
`unmapped_amount` DECIMAL(15, 2),

-- Auto-Detection

`detected_member_id` INT UNSIGNED COMMENT 'Auto-detected member',
`detection_confidence` DECIMAL(5, 2) COMMENT 'Confidence score 0-100',

-- Manual Transaction Recording


`paid_by_member_id` INT UNSIGNED COMMENT 'Member who made the payment',
    `paid_for_member_id` INT UNSIGNED COMMENT 'Member who received the payment',
    `updated_by` INT UNSIGNED COMMENT 'Admin who recorded this transaction',
    
    `remarks` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    KEY `idx_import_id` (`import_id`),
    KEY `idx_transaction_date` (`transaction_date`),
    KEY `idx_mapping_status` (`mapping_status`),
    KEY `idx_utr_number` (`utr_number`),
    FOREIGN KEY (`import_id`) REFERENCES `bank_statement_imports`(`id`),
    FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts`(`id`),
    FOREIGN KEY (`paid_by_member_id`) REFERENCES `members`(`id`),
    FOREIGN KEY (`paid_for_member_id`) REFERENCES `members`(`id`),
    FOREIGN KEY (`updated_by`) REFERENCES `admin_users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Transaction Mappings
CREATE TABLE IF NOT EXISTS `transaction_mappings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `bank_transaction_id` INT UNSIGNED NOT NULL,
    `member_id` INT UNSIGNED NOT NULL,
    `mapping_type` ENUM('savings', 'loan_payment', 'fine', 'other') NOT NULL,
    `related_id` INT UNSIGNED COMMENT 'savings_transaction_id or loan_payment_id',
    `amount` DECIMAL(15,2) NOT NULL,
    `for_month` DATE COMMENT 'Applicable month',
    `narration` VARCHAR(255),
    `mapped_by` INT UNSIGNED,
    `mapped_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

-- Reversal


`is_reversed` TINYINT(1) DEFAULT 0,
    `reversed_at` TIMESTAMP NULL,
    `reversed_by` INT UNSIGNED,
    `reversal_reason` VARCHAR(255),
    
    PRIMARY KEY (`id`),
    KEY `idx_bank_transaction_id` (`bank_transaction_id`),
    KEY `idx_member_id` (`member_id`),
    KEY `idx_mapping_type` (`mapping_type`),
    FOREIGN KEY (`bank_transaction_id`) REFERENCES `bank_transactions`(`id`),
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- LEDGER & ACCOUNTING TABLES
-- ============================================

-- Chart of Accounts
CREATE TABLE IF NOT EXISTS `chart_of_accounts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `account_code` VARCHAR(20) NOT NULL,
    `account_name` VARCHAR(100) NOT NULL,
    `account_type` ENUM(
        'asset',
        'liability',
        'income',
        'expense',
        'equity'
    ) NOT NULL,
    `parent_id` INT UNSIGNED,
    `is_group` TINYINT(1) DEFAULT 0,
    `is_system` TINYINT(1) DEFAULT 0 COMMENT 'System accounts cannot be deleted',
    `opening_balance` DECIMAL(15, 2) DEFAULT 0.00,
    `current_balance` DECIMAL(15, 2) DEFAULT 0.00,
    `is_active` TINYINT(1) DEFAULT 1,
    `description` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_account_code` (`account_code`),
    KEY `idx_account_type` (`account_type`),
    KEY `idx_parent_id` (`parent_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- General Ledger (Double-Entry)
CREATE TABLE IF NOT EXISTS `general_ledger` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `voucher_number` VARCHAR(30) NOT NULL,
    `voucher_date` DATE NOT NULL,
    `voucher_type` ENUM('receipt', 'payment', 'journal', 'contra') NOT NULL,
    `account_id` INT UNSIGNED NOT NULL,
    `debit_amount` DECIMAL(15,2) DEFAULT 0.00,
    `credit_amount` DECIMAL(15,2) DEFAULT 0.00,
    `balance_after` DECIMAL(15,2) NOT NULL,
    `narration` VARCHAR(255),

-- Reference


`reference_type` VARCHAR(50) COMMENT 'savings_transaction, loan_payment, etc.',
    `reference_id` INT UNSIGNED,
    `member_id` INT UNSIGNED,
    
    `financial_year_id` INT UNSIGNED,
    `is_posted` TINYINT(1) DEFAULT 1,
    `created_by` INT UNSIGNED,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    KEY `idx_voucher_number` (`voucher_number`),
    KEY `idx_voucher_date` (`voucher_date`),
    KEY `idx_account_id` (`account_id`),
    KEY `idx_reference` (`reference_type`, `reference_id`),
    FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Member Ledger (Individual member transactions)
CREATE TABLE IF NOT EXISTS `member_ledger` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `member_id` INT UNSIGNED NOT NULL,
    `transaction_date` DATE NOT NULL,
    `transaction_type` VARCHAR(50) NOT NULL COMMENT 'savings_deposit, loan_disbursement, etc.',
    `reference_type` VARCHAR(50),
    `reference_id` INT UNSIGNED,
    `debit_amount` DECIMAL(15, 2) DEFAULT 0.00,
    `credit_amount` DECIMAL(15, 2) DEFAULT 0.00,
    `balance_after` DECIMAL(15, 2) NOT NULL,
    `narration` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_member_id` (`member_id`),
    KEY `idx_transaction_date` (`transaction_date`),
    KEY `idx_reference` (
        `reference_type`,
        `reference_id`
    ),
    FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================
-- AUDIT & LOGGING TABLES
-- ============================================

-- Audit Logs (Immutable)
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `audit_code` VARCHAR(50) NOT NULL,
    `user_type` ENUM('admin', 'member', 'system') NOT NULL,
    `user_id` INT UNSIGNED,
    `action` VARCHAR(50) NOT NULL COMMENT 'create, update, delete, approve, reject, etc.',
    `module` VARCHAR(50) NOT NULL COMMENT 'members, loans, savings, etc.',
    `table_name` VARCHAR(100) NOT NULL,
    `record_id` INT UNSIGNED NOT NULL,
    `old_values` JSON,
    `new_values` JSON,
    `changed_fields` JSON COMMENT 'List of changed field names',
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `session_id` VARCHAR(255),
    `remarks` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_code` (`audit_code`),
    KEY `idx_user` (`user_type`, `user_id`),
    KEY `idx_action` (`action`),
    KEY `idx_module` (`module`),
    KEY `idx_table_record` (`table_name`, `record_id`),
    KEY `idx_created_at` (`created_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Activity Logs (Non-financial actions)
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_type` ENUM('admin', 'member') NOT NULL,
    `user_id` INT UNSIGNED,
    `activity` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `module` VARCHAR(50),
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user` (`user_type`, `user_id`),
    KEY `idx_activity` (`activity`),
    KEY `idx_created_at` (`created_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================
-- NOTIFICATIONS & ALERTS
-- ============================================

CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `recipient_type` ENUM('admin', 'member') NOT NULL,
    `recipient_id` INT UNSIGNED NOT NULL,
    `notification_type` VARCHAR(50) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `data` JSON,
    `is_read` TINYINT(1) DEFAULT 0,
    `read_at` TIMESTAMP NULL,
    `is_sent` TINYINT(1) DEFAULT 0 COMMENT 'SMS/Email sent',
    `sent_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_recipient` (
        `recipient_type`,
        `recipient_id`
    ),
    KEY `idx_is_read` (`is_read`),
    KEY `idx_created_at` (`created_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================
-- INSERT DEFAULT DATA
-- ============================================

-- Default Admin User (password: admin123)
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
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'System Administrator',
        'super_admin',
        1
    );

-- Member Code Sequence
INSERT INTO
    `member_code_sequence` (
        `prefix`,
        `current_number`,
        `year`
    )
VALUES ('MEM', 0, 2025);

-- Default Financial Year
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
    );

-- Default System Settings
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
        'Organization name'
    ),
    (
        'company_address',
        '',
        'string',
        'Organization address'
    ),
    (
        'company_phone',
        '',
        'string',
        'Contact phone'
    ),
    (
        'company_email',
        '',
        'string',
        'Contact email'
    ),
    (
        'currency_symbol',
        '₹',
        'string',
        'Currency symbol'
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
        'Financial year start month (1-12)'
    ),
    (
        'late_fine_grace_days',
        '7',
        'number',
        'Default grace period for late payments'
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
    );

-- Default Chart of Accounts
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

-- Default Savings Scheme
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
    );

-- Default Loan Product
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
        `interest_type`,
        `processing_fee_type`,
        `processing_fee_value`,
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
        'reducing',
        'percentage',
        1.00,
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
        'flat',
        'fixed',
        500.00,
        1,
        3,
        2.00,
        1
    );

-- Default Fine Rules
INSERT INTO
    `fine_rules` (
        `rule_code`,
        `rule_name`,
        `applies_to`,
        `fine_type`,
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
        'savings',
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
        'loan',
        'per_day',
        0.00,
        10.00,
        1000.00,
        7,
        1,
        '2025-01-01'
    );

-- Default Bank Account (Cash)
INSERT INTO
    `bank_accounts` (
        `account_name`,
        `bank_name`,
        `account_number`,
        `account_type`,
        `is_primary`
    )
VALUES (
        'Cash Account',
        'Cash',
        'CASH-001',
        'cash',
        1
    );

SET FOREIGN_KEY_CHECKS = 1;

COMMIT;