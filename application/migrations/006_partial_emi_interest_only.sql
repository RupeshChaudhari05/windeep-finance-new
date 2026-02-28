-- ============================================================
-- Migration 006: Partial EMI / Interest-Only Payment System
-- ============================================================
-- Allows members to pay only interest when they can't afford full EMI.
-- Principal is deferred and loan tenure extends by 1 month per skip.
-- ============================================================

-- 1. Track tenure extensions on loans table
ALTER TABLE `loans`
ADD COLUMN `original_tenure_months` INT AFTER `tenure_months`,
ADD COLUMN `tenure_extensions` INT DEFAULT 0 AFTER `original_tenure_months`,
ADD COLUMN `max_tenure_extensions` INT DEFAULT 6 COMMENT 'Max allowed interest-only months' AFTER `tenure_extensions`;

-- Backfill original tenure for existing loans
UPDATE `loans`
SET
    `original_tenure_months` = `tenure_months`
WHERE
    `original_tenure_months` IS NULL;

-- 2. Track extension details on loan_installments
ALTER TABLE `loan_installments`
ADD COLUMN `is_extension` TINYINT(1) DEFAULT 0 COMMENT 'Was this installment added due to interest-only payment' AFTER `is_adjusted`,
ADD COLUMN `extended_from_installment` INT UNSIGNED COMMENT 'Original installment whose principal was deferred' AFTER `is_extension`,
ADD COLUMN `deferred_principal` DECIMAL(15, 2) DEFAULT 0.00 COMMENT 'Principal amount deferred from this installment' AFTER `extended_from_installment`;

-- 3. System setting for max allowed tenure extensions per loan
INSERT INTO
    `system_settings` (
        `setting_key`,
        `setting_value`,
        `setting_group`,
        `description`
    )
VALUES (
        'max_tenure_extensions',
        '6',
        'loan',
        'Maximum number of interest-only payments allowed per loan'
    )
ON DUPLICATE KEY UPDATE
    `setting_key` = `setting_key`;

-- 4. Loan product level control (optional per-product override)
ALTER TABLE `loan_products`
ADD COLUMN `allow_interest_only` TINYINT(1) DEFAULT 1 COMMENT 'Allow interest-only partial payments' AFTER `is_active`,
ADD COLUMN `max_interest_only_months` INT DEFAULT 6 COMMENT 'Max interest-only months for this product' AFTER `allow_interest_only`;