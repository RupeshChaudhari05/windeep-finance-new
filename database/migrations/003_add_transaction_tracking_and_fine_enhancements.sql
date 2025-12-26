-- Migration: Add transaction tracking and fine system enhancements
-- Date: 2025-12-26
-- Description: Adds tracking columns for who made transactions and enhanced fine rules

-- =====================================================
-- Add transaction tracking columns to various tables
-- =====================================================

-- Bank Transactions - Add mapping and tracking columns
ALTER TABLE `bank_transactions`
ADD COLUMN IF NOT EXISTS `paid_by_member_id` INT NULL AFTER `detected_member_id`,
ADD COLUMN IF NOT EXISTS `paid_for_member_id` INT NULL AFTER `paid_by_member_id`,
ADD COLUMN IF NOT EXISTS `transaction_category` VARCHAR(50) NULL AFTER `paid_for_member_id`,
ADD COLUMN IF NOT EXISTS `mapping_remarks` TEXT NULL AFTER `transaction_category`,
ADD COLUMN IF NOT EXISTS `mapped_by` INT NULL AFTER `mapping_remarks`,
ADD COLUMN IF NOT EXISTS `mapped_at` DATETIME NULL AFTER `mapped_by`,
ADD COLUMN IF NOT EXISTS `related_type` VARCHAR(50) NULL AFTER `mapped_at`,
ADD COLUMN IF NOT EXISTS `related_id` INT NULL AFTER `related_type`,
ADD COLUMN IF NOT EXISTS `credit_amount` DECIMAL(15, 2) DEFAULT 0 AFTER `amount`,
ADD COLUMN IF NOT EXISTS `debit_amount` DECIMAL(15, 2) DEFAULT 0 AFTER `credit_amount`,
ADD COLUMN IF NOT EXISTS `running_balance` DECIMAL(15, 2) DEFAULT 0 AFTER `debit_amount`,
ADD COLUMN IF NOT EXISTS `description2` VARCHAR(255) NULL AFTER `description`,
ADD COLUMN IF NOT EXISTS `updated_by` INT NULL AFTER `updated_at`;

-- Add foreign keys for bank_transactions
ALTER TABLE `bank_transactions`
ADD INDEX IF NOT EXISTS `idx_paid_by_member` (`paid_by_member_id`),
ADD INDEX IF NOT EXISTS `idx_paid_for_member` (`paid_for_member_id`),
ADD INDEX IF NOT EXISTS `idx_mapped_by` (`mapped_by`);

-- =====================================================
-- Enhanced Fine Rules table
-- =====================================================
ALTER TABLE `fine_rules`
ADD COLUMN IF NOT EXISTS `per_day_amount` DECIMAL(10, 2) DEFAULT 0 AFTER `fine_rate`,
ADD COLUMN IF NOT EXISTS `created_by` INT NULL AFTER `created_at`,
ADD COLUMN IF NOT EXISTS `updated_by` INT NULL AFTER `updated_at`;

-- Update fine_type enum to include fixed_plus_daily
-- Note: Run this if you need to alter the enum
-- ALTER TABLE `fine_rules` MODIFY COLUMN `fine_type` ENUM('fixed', 'percentage', 'per_day', 'fixed_plus_daily') DEFAULT 'fixed';

-- =====================================================
-- Add tracking columns to fines table
-- =====================================================
ALTER TABLE `fines`
ADD COLUMN IF NOT EXISTS `updated_by` INT NULL AFTER `updated_at`;

-- =====================================================
-- Loan Products - Add dynamic interest rate settings
-- =====================================================
ALTER TABLE `loan_products`
ADD COLUMN IF NOT EXISTS `min_interest_rate` DECIMAL(5, 2) DEFAULT 0 AFTER `interest_rate`,
ADD COLUMN IF NOT EXISTS `max_interest_rate` DECIMAL(5, 2) DEFAULT 50 AFTER `min_interest_rate`,
ADD COLUMN IF NOT EXISTS `default_interest_rate` DECIMAL(5, 2) NULL AFTER `max_interest_rate`,
ADD COLUMN IF NOT EXISTS `late_fee_type` ENUM(
    'fixed',
    'percentage',
    'per_day',
    'fixed_plus_daily'
) DEFAULT 'fixed' AFTER `processing_fee_value`,
ADD COLUMN IF NOT EXISTS `late_fee_value` DECIMAL(10, 2) DEFAULT 0 AFTER `late_fee_type`,
ADD COLUMN IF NOT EXISTS `late_fee_per_day` DECIMAL(10, 2) DEFAULT 0 AFTER `late_fee_value`,
ADD COLUMN IF NOT EXISTS `grace_period_days` INT DEFAULT 5 AFTER `late_fee_per_day`,
ADD COLUMN IF NOT EXISTS `created_by` INT NULL AFTER `created_at`,
ADD COLUMN IF NOT EXISTS `updated_by` INT NULL AFTER `updated_at`;

-- =====================================================
-- Loan Applications - Add tracking
-- =====================================================
ALTER TABLE `loan_applications`
ADD COLUMN IF NOT EXISTS `created_by` INT NULL AFTER `created_at`,
ADD COLUMN IF NOT EXISTS `updated_by` INT NULL AFTER `updated_at`;

-- =====================================================
-- Loans - Add tracking
-- =====================================================
ALTER TABLE `loans`
ADD COLUMN IF NOT EXISTS `disbursed_by` INT NULL AFTER `disbursed_at`,
ADD COLUMN IF NOT EXISTS `closed_by` INT NULL AFTER `closed_at`,
ADD COLUMN IF NOT EXISTS `created_by` INT NULL AFTER `created_at`,
ADD COLUMN IF NOT EXISTS `updated_by` INT NULL AFTER `updated_at`;

-- =====================================================
-- Loan Payments - Add tracking
-- =====================================================
ALTER TABLE `loan_payments`
ADD COLUMN IF NOT EXISTS `bank_transaction_id` INT NULL AFTER `reference_number`,
ADD COLUMN IF NOT EXISTS `created_by` INT NULL AFTER `created_at`,
ADD COLUMN IF NOT EXISTS `updated_by` INT NULL AFTER `updated_at`;

-- =====================================================
-- Savings Payments - Add tracking
-- =====================================================
ALTER TABLE `savings_payments`
ADD COLUMN IF NOT EXISTS `bank_transaction_id` INT NULL AFTER `reference_number`,
ADD COLUMN IF NOT EXISTS `created_by` INT NULL AFTER `created_at`,
ADD COLUMN IF NOT EXISTS `updated_by` INT NULL AFTER `updated_at`;

-- =====================================================
-- Bank Statement Imports - Add import code
-- =====================================================
ALTER TABLE `bank_statement_imports`
ADD COLUMN IF NOT EXISTS `import_code` VARCHAR(50) NULL AFTER `id`,
ADD COLUMN IF NOT EXISTS `statement_date` DATE NULL AFTER `file_path`;

-- Update existing imports with codes
UPDATE `bank_statement_imports`
SET
    `import_code` = CONCAT(
        'IMP-',
        DATE_FORMAT(created_at, '%Y%m%d'),
        '-',
        UPPER(SUBSTRING(MD5(id), 1, 6))
    )
WHERE
    `import_code` IS NULL;

-- =====================================================
-- Insert Default Fine Rules (Indian Banking Style)
-- =====================================================
INSERT INTO
    `fine_rules` (
        `rule_name`,
        `applies_to`,
        `fine_type`,
        `fine_amount`,
        `per_day_amount`,
        `grace_period`,
        `max_fine`,
        `min_days`,
        `max_days`,
        `description`,
        `is_active`,
        `created_at`
    )
SELECT 'Loan EMI Late Fine', 'loan', 'fixed_plus_daily', 100.00, 10.00, 5, 1000.00, 1, 9999, '₹100 initial fine after 5 days grace, then ₹10 per day (max ₹1000)', 1, NOW()
WHERE
    NOT EXISTS (
        SELECT 1
        FROM `fine_rules`
        WHERE
            `rule_name` = 'Loan EMI Late Fine'
    );

INSERT INTO
    `fine_rules` (
        `rule_name`,
        `applies_to`,
        `fine_type`,
        `fine_amount`,
        `per_day_amount`,
        `grace_period`,
        `max_fine`,
        `min_days`,
        `max_days`,
        `description`,
        `is_active`,
        `created_at`
    )
SELECT 'Savings Late Fine', 'savings', 'fixed_plus_daily', 50.00, 5.00, 3, 500.00, 1, 9999, '₹50 initial fine after 3 days grace, then ₹5 per day (max ₹500)', 1, NOW()
WHERE
    NOT EXISTS (
        SELECT 1
        FROM `fine_rules`
        WHERE
            `rule_name` = 'Savings Late Fine'
    );

-- =====================================================
-- Guarantor related settings
-- =====================================================
INSERT INTO
    `settings` (
        `setting_key`,
        `setting_value`,
        `setting_group`,
        `created_at`
    )
SELECT 'min_guarantors', '1', 'guarantor', NOW()
WHERE
    NOT EXISTS (
        SELECT 1
        FROM `settings`
        WHERE
            `setting_key` = 'min_guarantors'
    );

INSERT INTO
    `settings` (
        `setting_key`,
        `setting_value`,
        `setting_group`,
        `created_at`
    )
SELECT 'max_guarantors', '5', 'guarantor', NOW()
WHERE
    NOT EXISTS (
        SELECT 1
        FROM `settings`
        WHERE
            `setting_key` = 'max_guarantors'
    );

INSERT INTO
    `settings` (
        `setting_key`,
        `setting_value`,
        `setting_group`,
        `created_at`
    )
SELECT 'guarantor_coverage_percent', '100', 'guarantor', NOW()
WHERE
    NOT EXISTS (
        SELECT 1
        FROM `settings`
        WHERE
            `setting_key` = 'guarantor_coverage_percent'
    );

INSERT INTO
    `settings` (
        `setting_key`,
        `setting_value`,
        `setting_group`,
        `created_at`
    )
SELECT 'max_loans_as_guarantor', '3', 'guarantor', NOW()
WHERE
    NOT EXISTS (
        SELECT 1
        FROM `settings`
        WHERE
            `setting_key` = 'max_loans_as_guarantor'
    );

-- =====================================================
-- Accounting settings
-- =====================================================
INSERT INTO
    `settings` (
        `setting_key`,
        `setting_value`,
        `setting_group`,
        `created_at`
    )
SELECT 'fy_start_month', '4', 'accounting', NOW()
WHERE
    NOT EXISTS (
        SELECT 1
        FROM `settings`
        WHERE
            `setting_key` = 'fy_start_month'
    );

INSERT INTO
    `settings` (
        `setting_key`,
        `setting_value`,
        `setting_group`,
        `created_at`
    )
SELECT 'accounting_method', 'accrual', 'accounting', NOW()
WHERE
    NOT EXISTS (
        SELECT 1
        FROM `settings`
        WHERE
            `setting_key` = 'accounting_method'
    );

INSERT INTO
    `settings` (
        `setting_key`,
        `setting_value`,
        `setting_group`,
        `created_at`
    )
SELECT 'receipt_prefix', 'RCT', 'accounting', NOW()
WHERE
    NOT EXISTS (
        SELECT 1
        FROM `settings`
        WHERE
            `setting_key` = 'receipt_prefix'
    );

INSERT INTO
    `settings` (
        `setting_key`,
        `setting_value`,
        `setting_group`,
        `created_at`
    )
SELECT 'payment_prefix', 'PAY', 'accounting', NOW()
WHERE
    NOT EXISTS (
        SELECT 1
        FROM `settings`
        WHERE
            `setting_key` = 'payment_prefix'
    );

-- Success message
SELECT 'Migration completed successfully!' as status;