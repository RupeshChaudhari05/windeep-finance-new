-- Migration 005: Enhanced Bank Statement Reconciliation
-- Adds disbursement tracking, internal transactions, and improved mapping support

-- ============================================
-- 1. Add missing columns to bank_transactions
-- ============================================

-- Add mapped_by and mapped_at if not exist
SET
    @col_exists = (
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE
            TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'bank_transactions'
            AND COLUMN_NAME = 'mapped_by'
    );

SET
    @sql = IF(
        @col_exists = 0,
        'ALTER TABLE `bank_transactions` ADD COLUMN `mapped_by` INT UNSIGNED NULL AFTER `updated_by`',
        'SELECT 1'
    );

PREPARE stmt FROM @sql;

EXECUTE stmt;

DEALLOCATE PREPARE stmt;

SET
    @col_exists = (
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE
            TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'bank_transactions'
            AND COLUMN_NAME = 'mapped_at'
    );

SET
    @sql = IF(
        @col_exists = 0,
        'ALTER TABLE `bank_transactions` ADD COLUMN `mapped_at` TIMESTAMP NULL AFTER `mapped_by`',
        'SELECT 1'
    );

PREPARE stmt FROM @sql;

EXECUTE stmt;

DEALLOCATE PREPARE stmt;

SET
    @col_exists = (
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE
            TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'bank_transactions'
            AND COLUMN_NAME = 'mapping_remarks'
    );

SET
    @sql = IF(
        @col_exists = 0,
        'ALTER TABLE `bank_transactions` ADD COLUMN `mapping_remarks` TEXT NULL AFTER `remarks`',
        'SELECT 1'
    );

PREPARE stmt FROM @sql;

EXECUTE stmt;

DEALLOCATE PREPARE stmt;

SET
    @col_exists = (
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE
            TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'bank_transactions'
            AND COLUMN_NAME = 'transaction_category'
    );

SET
    @sql = IF(
        @col_exists = 0,
        'ALTER TABLE `bank_transactions` ADD COLUMN `transaction_category` VARCHAR(50) NULL AFTER `mapping_remarks`',
        'SELECT 1'
    );

PREPARE stmt FROM @sql;

EXECUTE stmt;

DEALLOCATE PREPARE stmt;

SET
    @col_exists = (
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE
            TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'bank_transactions'
            AND COLUMN_NAME = 'related_type'
    );

SET
    @sql = IF(
        @col_exists = 0,
        'ALTER TABLE `bank_transactions` ADD COLUMN `related_type` VARCHAR(50) NULL AFTER `transaction_category`',
        'SELECT 1'
    );

PREPARE stmt FROM @sql;

EXECUTE stmt;

DEALLOCATE PREPARE stmt;

SET
    @col_exists = (
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE
            TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'bank_transactions'
            AND COLUMN_NAME = 'related_id'
    );

SET
    @sql = IF(
        @col_exists = 0,
        'ALTER TABLE `bank_transactions` ADD COLUMN `related_id` INT UNSIGNED NULL AFTER `related_type`',
        'SELECT 1'
    );

PREPARE stmt FROM @sql;

EXECUTE stmt;

DEALLOCATE PREPARE stmt;

-- ============================================
-- 2. Extend transaction_mappings enum & add related_type
-- ============================================

-- Extend mapping_type to include disbursement and internal types
ALTER TABLE `transaction_mappings`
MODIFY COLUMN `mapping_type` VARCHAR(50) NOT NULL DEFAULT 'other';

-- Add related_type column if not exists
SET
    @col_exists = (
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE
            TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'transaction_mappings'
            AND COLUMN_NAME = 'related_type'
    );

SET
    @sql = IF(
        @col_exists = 0,
        'ALTER TABLE `transaction_mappings` ADD COLUMN `related_type` VARCHAR(50) NULL AFTER `mapping_type`',
        'SELECT 1'
    );

PREPARE stmt FROM @sql;

EXECUTE stmt;

DEALLOCATE PREPARE stmt;

-- Allow NULL member_id for internal/expense mappings
ALTER TABLE `transaction_mappings`
MODIFY COLUMN `member_id` INT UNSIGNED NULL;

-- ============================================
-- 3. Create internal_transactions table
-- ============================================

CREATE TABLE IF NOT EXISTS `internal_transactions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `transaction_code` VARCHAR(30) NOT NULL,
    `transaction_type` ENUM(
        'internal_transfer',
        'bank_charge',
        'interest_earned',
        'dividend_paid',
        'cash_deposit',
        'cash_withdrawal',
        'contra_entry',
        'adjustment',
        'other'
    ) NOT NULL,
    `from_account_type` VARCHAR(50) NULL COMMENT 'bank_account, cash, member_savings, etc.',
    `from_account_id` INT UNSIGNED NULL,
    `to_account_type` VARCHAR(50) NULL,
    `to_account_id` INT UNSIGNED NULL,
    `amount` DECIMAL(15, 2) NOT NULL,
    `transaction_date` DATE NOT NULL,
    `description` TEXT,
    `reference_number` VARCHAR(100),
    `bank_transaction_id` INT UNSIGNED NULL COMMENT 'Linked bank statement row',
    `status` ENUM(
        'pending',
        'completed',
        'reversed'
    ) DEFAULT 'completed',
    `created_by` INT UNSIGNED,
    `approved_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_transaction_code` (`transaction_code`),
    KEY `idx_transaction_type` (`transaction_type`),
    KEY `idx_transaction_date` (`transaction_date`),
    KEY `idx_bank_transaction_id` (`bank_transaction_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================
-- 4. Create disbursement_tracking table
-- ============================================

CREATE TABLE IF NOT EXISTS `disbursement_tracking` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `loan_id` INT UNSIGNED NOT NULL,
    `member_id` INT UNSIGNED NOT NULL,
    `bank_transaction_id` INT UNSIGNED NULL COMMENT 'Linked bank statement debit entry',
    `disbursement_amount` DECIMAL(15, 2) NOT NULL,
    `processing_fee` DECIMAL(15, 2) DEFAULT 0.00,
    `net_amount` DECIMAL(15, 2) NOT NULL,
    `disbursement_date` DATE NOT NULL,
    `disbursement_mode` ENUM(
        'bank_transfer',
        'cheque',
        'cash',
        'upi'
    ) DEFAULT 'bank_transfer',
    `reference_number` VARCHAR(100),
    `bank_account_id` INT UNSIGNED NULL,
    `status` ENUM(
        'pending',
        'completed',
        'failed',
        'reversed'
    ) DEFAULT 'completed',
    `remarks` TEXT,
    `created_by` INT UNSIGNED,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_loan_id` (`loan_id`),
    KEY `idx_member_id` (`member_id`),
    KEY `idx_bank_transaction_id` (`bank_transaction_id`),
    KEY `idx_disbursement_date` (`disbursement_date`),
    FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`),
    FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================
-- 5. Create expense_transactions table if not exists
-- ============================================

CREATE TABLE IF NOT EXISTS `expense_transactions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `bank_transaction_id` INT UNSIGNED NULL,
    `expense_category` VARCHAR(50) NOT NULL,
    `amount` DECIMAL(15, 2) NOT NULL,
    `description` TEXT,
    `expense_date` DATE NOT NULL,
    `vendor_name` VARCHAR(100) NULL,
    `receipt_number` VARCHAR(50) NULL,
    `gl_entry_id` INT UNSIGNED NULL,
    `status` ENUM(
        'pending',
        'approved',
        'rejected'
    ) DEFAULT 'approved',
    `created_by` INT UNSIGNED,
    `approved_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_expense_category` (`expense_category`),
    KEY `idx_expense_date` (`expense_date`),
    KEY `idx_bank_transaction_id` (`bank_transaction_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================
-- 6. Create member_other_transactions if not exists
-- ============================================

CREATE TABLE IF NOT EXISTS `member_other_transactions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `member_id` INT UNSIGNED NOT NULL,
    `transaction_type` VARCHAR(50) NOT NULL,
    `amount` DECIMAL(15, 2) NOT NULL,
    `transaction_date` DATE NOT NULL,
    `description` TEXT,
    `reference_type` VARCHAR(50) NULL,
    `reference_id` INT UNSIGNED NULL,
    `payment_mode` VARCHAR(20) DEFAULT 'cash',
    `receipt_number` VARCHAR(50) NULL,
    `bank_transaction_id` INT UNSIGNED NULL,
    `gl_entry_id` INT UNSIGNED NULL,
    `status` ENUM(
        'pending',
        'completed',
        'reversed'
    ) DEFAULT 'completed',
    `created_by` INT UNSIGNED,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_member_id` (`member_id`),
    KEY `idx_transaction_type` (`transaction_type`),
    KEY `idx_transaction_date` (`transaction_date`),
    FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;