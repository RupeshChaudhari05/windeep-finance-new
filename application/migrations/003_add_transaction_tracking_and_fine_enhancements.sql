-- Migration: Add Transaction Tracking and Fine Enhancements
-- Date: 2025-12-26
-- Description: Adds tracking columns for who made transactions, fine calculation enhancements

-- =========================================
-- 1. ADD TRANSACTION TRACKING COLUMNS
-- =========================================

-- Add tracking columns to bank_transactions table
ALTER TABLE `bank_transactions`
ADD COLUMN IF NOT EXISTS `paid_by_member_id` INT(11) NULL DEFAULT NULL COMMENT 'Member who made the payment',
ADD COLUMN IF NOT EXISTS `paid_for_member_id` INT(11) NULL DEFAULT NULL COMMENT 'Member for whom payment was made',
ADD COLUMN IF NOT EXISTS `transaction_category` VARCHAR(50) NULL DEFAULT NULL COMMENT 'Category: loan_repayment, deposit, withdrawal, etc.',
ADD COLUMN IF NOT EXISTS `mapping_status` ENUM(
    'unmapped',
    'mapped',
    'processed'
) DEFAULT 'unmapped',
ADD COLUMN IF NOT EXISTS `mapped_by` INT(11) NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `mapped_at` DATETIME NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `updated_by` INT(11) NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `updated_at` DATETIME NULL DEFAULT NULL;

-- =========================================
-- 2. ENHANCE FINE RULES TABLE
-- =========================================

-- Add columns for Indian banking style fine calculation
ALTER TABLE `fine_rules`
ADD COLUMN IF NOT EXISTS `per_day_amount` DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Per day fine amount for fixed_plus_daily type',
ADD COLUMN IF NOT EXISTS `grace_period_days` INT(11) DEFAULT 0,
ADD COLUMN IF NOT EXISTS `max_fine_amount` DECIMAL(10, 2) DEFAULT NULL COMMENT 'Maximum fine cap',
ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) DEFAULT 1,
ADD COLUMN IF NOT EXISTS `created_by` INT(11) NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `updated_by` INT(11) NULL DEFAULT NULL;

-- Modify fine_type ENUM to include fixed_plus_daily
ALTER TABLE `fine_rules`
MODIFY COLUMN `fine_type` ENUM(
    'fixed',
    'percentage',
    'per_day',
    'fixed_plus_daily'
) DEFAULT 'fixed';

-- =========================================
-- 3. ENHANCE LOAN PRODUCTS TABLE
-- =========================================

-- Add columns for dynamic interest rates
ALTER TABLE `loan_products`
ADD COLUMN IF NOT EXISTS `min_interest_rate` DECIMAL(5, 2) DEFAULT NULL COMMENT 'Minimum interest rate admin can assign',
ADD COLUMN IF NOT EXISTS `max_interest_rate` DECIMAL(5, 2) DEFAULT NULL COMMENT 'Maximum interest rate admin can assign',
ADD COLUMN IF NOT EXISTS `default_interest_rate` DECIMAL(5, 2) DEFAULT NULL COMMENT 'Default interest rate',
ADD COLUMN IF NOT EXISTS `late_fee_type` ENUM(
    'fixed',
    'percentage',
    'per_day',
    'fixed_plus_daily'
) DEFAULT 'fixed',
ADD COLUMN IF NOT EXISTS `late_fee_value` DECIMAL(10, 2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS `late_fee_per_day` DECIMAL(10, 2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS `grace_period_days` INT(11) DEFAULT 0,
ADD COLUMN IF NOT EXISTS `product_code` VARCHAR(20) NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) DEFAULT 1,
ADD COLUMN IF NOT EXISTS `created_by` INT(11) NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `updated_by` INT(11) NULL DEFAULT NULL;

-- =========================================
-- 4. ENHANCE LOAN APPLICATIONS TABLE
-- =========================================

-- Add column for custom interest rate assigned during approval
ALTER TABLE `loan_applications`
ADD COLUMN IF NOT EXISTS `assigned_interest_rate` DECIMAL(5, 2) DEFAULT NULL COMMENT 'Interest rate assigned by admin during approval',
ADD COLUMN IF NOT EXISTS `approved_by` INT(11) NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `disbursed_by` INT(11) NULL DEFAULT NULL;

-- =========================================
-- 5. ADD GUARANTOR SETTINGS TABLE
-- =========================================

CREATE TABLE IF NOT EXISTS `guarantor_settings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT,
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Insert default guarantor settings
INSERT IGNORE INTO
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

-- =========================================
-- 6. ADD ACCOUNTING SETTINGS TABLE
-- =========================================

CREATE TABLE IF NOT EXISTS `accounting_settings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT,
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Insert default accounting settings
INSERT IGNORE INTO
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

-- =========================================
-- 7. INSERT DEFAULT FINE RULES (Indian Banking Style)
-- =========================================

INSERT IGNORE INTO
    `fine_rules` (
        `rule_name`,
        `fine_type`,
        `fine_amount`,
        `per_day_amount`,
        `grace_period_days`,
        `max_fine_amount`,
        `is_active`,
        `description`
    )
VALUES (
        'EMI Late Payment - Standard',
        'fixed_plus_daily',
        100.00,
        10.00,
        0,
        500.00,
        1,
        'Rs.100 initial fine + Rs.10 per day (Indian Banking Style)'
    ),
    (
        'EMI Late Payment - Premium',
        'fixed_plus_daily',
        200.00,
        20.00,
        3,
        1000.00,
        1,
        'Rs.200 initial fine + Rs.20 per day with 3 day grace period'
    ),
    (
        'Savings Default',
        'fixed',
        50.00,
        0.00,
        7,
        NULL,
        1,
        'Fixed Rs.50 fine for missing savings deposit'
    );

-- =========================================
-- 8. ADD BANK IMPORT TRACKING TABLE
-- =========================================

CREATE TABLE IF NOT EXISTS `bank_imports` (
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
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Add import_id to bank_transactions
ALTER TABLE `bank_transactions`
ADD COLUMN IF NOT EXISTS `import_id` INT(11) NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `import_code` VARCHAR(50) NULL DEFAULT NULL;

-- =========================================
-- 9. ADD INDEXES FOR BETTER PERFORMANCE
-- =========================================

-- Add indexes if they don't exist (wrapped in procedure for safety)
DELIMITER / /

CREATE PROCEDURE add_indexes_if_not_exists()
BEGIN
    -- Index for bank_transactions mapping
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'bank_transactions' AND index_name = 'idx_mapping_status') THEN
        CREATE INDEX idx_mapping_status ON bank_transactions(mapping_status);
    END IF;
    
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'bank_transactions' AND index_name = 'idx_paid_by_member') THEN
        CREATE INDEX idx_paid_by_member ON bank_transactions(paid_by_member_id);
    END IF;
    
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'bank_transactions' AND index_name = 'idx_paid_for_member') THEN
        CREATE INDEX idx_paid_for_member ON bank_transactions(paid_for_member_id);
    END IF;
END //

DELIMITER;

CALL add_indexes_if_not_exists ();

DROP PROCEDURE IF EXISTS add_indexes_if_not_exists;

-- =========================================
-- MIGRATION COMPLETE
-- =========================================