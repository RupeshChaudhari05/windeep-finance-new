-- =====================================================================
-- Migration 022: Fix Loan Module Schema Issues (LOAN-3, LOAN-7 support)
-- Date: 2026-02-28
-- Description:
--   LOAN-3 FIX: Add missing columns for interest-only payment feature
--     - loans: tenure_extensions, original_tenure_months, max_tenure_extensions
--     - loan_installments: is_extension, extended_from_installment, deferred_principal
--   Performance: Add indexes for overdue detection queries
-- =====================================================================

-- ─── LOANS TABLE: Interest-only / tenure extension columns ───

ALTER TABLE `loans`
ADD COLUMN `tenure_extensions` INT NOT NULL DEFAULT 0 COMMENT 'Number of tenure extensions used (interest-only payments)' AFTER `tenure_months`,
ADD COLUMN `original_tenure_months` INT DEFAULT NULL COMMENT 'Original tenure before any extensions' AFTER `tenure_extensions`,
ADD COLUMN `max_tenure_extensions` INT NOT NULL DEFAULT 0 COMMENT 'Loan-level max extensions override (0 = use system setting)' AFTER `original_tenure_months`;

-- Backfill original_tenure_months for existing loans
UPDATE `loans`
SET
    `original_tenure_months` = `tenure_months`
WHERE
    `original_tenure_months` IS NULL;

-- ─── LOAN_INSTALLMENTS TABLE: Extension tracking columns ───

ALTER TABLE `loan_installments`
ADD COLUMN `is_extension` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Whether this installment was created by interest-only deferral' AFTER `is_adjusted`,
ADD COLUMN `extended_from_installment` INT UNSIGNED DEFAULT NULL COMMENT 'ID of original installment whose principal was deferred' AFTER `is_extension`,
ADD COLUMN `deferred_principal` DECIMAL(15, 2) DEFAULT NULL COMMENT 'Principal amount deferred from this installment (for interest_only status)' AFTER `extended_from_installment`;

-- Add FK for extended_from_installment (self-referencing)
-- Wrapped in a procedure to prevent errors if FK already exists
DELIMITER / /

CREATE PROCEDURE _add_extension_fk()
BEGIN
  DECLARE CONTINUE HANDLER FOR 1061 BEGIN END; -- duplicate key name
  DECLARE CONTINUE HANDLER FOR 1826 BEGIN END; -- duplicate FK
  ALTER TABLE `loan_installments`
    ADD CONSTRAINT `fk_extended_from_installment`
    FOREIGN KEY (`extended_from_installment`) REFERENCES `loan_installments`(`id`)
    ON DELETE SET NULL;
END //

DELIMITER;

CALL _add_extension_fk ();

DROP PROCEDURE IF EXISTS _add_extension_fk;

-- ─── PERFORMANCE INDEXES for overdue detection (LOAN-7 support) ───

-- Index for overdue installment queries: WHERE status IN (...) AND due_date < ?
CREATE INDEX `idx_installments_status_due_date` ON `loan_installments` (`status`, `due_date`);

-- Index for bank transaction date queries (CA report)
-- Wrapped to ignore if already exists
DELIMITER / /

CREATE PROCEDURE _add_bt_index()
BEGIN
  DECLARE CONTINUE HANDLER FOR 1061 BEGIN END;
  CREATE INDEX `idx_bank_txn_date` ON `bank_transactions` (`transaction_date`);
END //

DELIMITER;

CALL _add_bt_index ();

DROP PROCEDURE IF EXISTS _add_bt_index;

-- ─── FIX CURRENCY SYMBOL (CFG-4) ───
UPDATE `system_settings`
SET
    `setting_value` = '₹'
WHERE
    `setting_key` = 'currency_symbol'
    AND `setting_value` = '?';

-- ─── VERIFICATION ───
-- Run these queries to verify the migration:
-- SHOW COLUMNS FROM loans LIKE 'tenure_extensions';
-- SHOW COLUMNS FROM loans LIKE 'original_tenure_months';
-- SHOW COLUMNS FROM loans LIKE 'max_tenure_extensions';
-- SHOW COLUMNS FROM loan_installments LIKE 'is_extension';
-- SHOW COLUMNS FROM loan_installments LIKE 'extended_from_installment';
-- SHOW COLUMNS FROM loan_installments LIKE 'deferred_principal';
-- SHOW INDEX FROM loan_installments WHERE Key_name = 'idx_installments_status_due_date';
-- SELECT setting_value FROM system_settings WHERE setting_key = 'currency_symbol';