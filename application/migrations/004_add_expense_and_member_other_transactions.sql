-- Migration: 004_add_expense_and_member_other_transactions
-- Date: 2026-02-24
-- Description: Adds expense_transactions for bank statement expense tracking
--              and member_other_transactions for membership fees, other member charges, etc.

-- ============================================================
-- TABLE: expense_transactions
-- Tracks expenses mapped from bank statements (Stationery, Travelling, Electricity, Rent, etc.)
-- ============================================================
CREATE TABLE IF NOT EXISTS `expense_transactions` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `bank_transaction_id` INT(10) UNSIGNED DEFAULT NULL COMMENT 'Linked bank transaction if mapped from statement',
    `expense_category` VARCHAR(50) NOT NULL COMMENT 'e.g. stationery, travelling, electricity, rent, salary, etc.',
    `amount` DECIMAL(15, 2) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `expense_date` DATE NOT NULL,
    `receipt_number` VARCHAR(50) DEFAULT NULL,
    `vendor_name` VARCHAR(100) DEFAULT NULL,
    `gl_entry_id` INT(10) UNSIGNED DEFAULT NULL COMMENT 'General Ledger entry ID',
    `created_by` INT(10) UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_expense_category` (`expense_category`),
    KEY `idx_expense_date` (`expense_date`),
    KEY `idx_bank_transaction_id` (`bank_transaction_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: member_other_transactions
-- Tracks member fees and other charges: membership fee, processing fee, penalties, rewards, etc.
-- ============================================================
CREATE TABLE IF NOT EXISTS `member_other_transactions` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `member_id` INT(10) UNSIGNED NOT NULL,
    `transaction_type` VARCHAR(50) NOT NULL COMMENT 'membership_fee, processing_fee, bonus, reward, penalty, other',
    `amount` DECIMAL(15, 2) NOT NULL,
    `transaction_date` DATE NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `reference_type` VARCHAR(50) DEFAULT NULL COMMENT 'loan, savings, bank_transaction, manual',
    `reference_id` INT(10) UNSIGNED DEFAULT NULL,
    `payment_mode` VARCHAR(30) DEFAULT NULL COMMENT 'cash, bank_transfer, upi, cheque',
    `receipt_number` VARCHAR(50) DEFAULT NULL,
    `bank_transaction_id` INT(10) UNSIGNED DEFAULT NULL,
    `gl_entry_id` INT(10) UNSIGNED DEFAULT NULL,
    `status` ENUM(
        'completed',
        'pending',
        'reversed'
    ) DEFAULT 'completed',
    `reversed_at` TIMESTAMP NULL DEFAULT NULL,
    `reversed_by` INT(10) UNSIGNED DEFAULT NULL,
    `reversal_reason` VARCHAR(255) DEFAULT NULL,
    `created_by` INT(10) UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_member_id` (`member_id`),
    KEY `idx_transaction_type` (`transaction_type`),
    KEY `idx_transaction_date` (`transaction_date`),
    KEY `idx_status` (`status`),
    CONSTRAINT `member_other_txn_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- Add default expense sub-accounts to chart_of_accounts
-- ============================================================
INSERT IGNORE INTO
    `chart_of_accounts` (
        `account_code`,
        `account_name`,
        `account_type`,
        `parent_id`,
        `is_group`,
        `is_system`
    )
SELECT '4201', 'Stationery Expenses', 'expense', id, 0, 0
FROM `chart_of_accounts`
WHERE
    account_code = '4000'
ON DUPLICATE KEY UPDATE
    `account_name` = VALUES(`account_name`);

INSERT IGNORE INTO
    `chart_of_accounts` (
        `account_code`,
        `account_name`,
        `account_type`,
        `parent_id`,
        `is_group`,
        `is_system`
    )
SELECT '4202', 'Travelling Expenses', 'expense', id, 0, 0
FROM `chart_of_accounts`
WHERE
    account_code = '4000'
ON DUPLICATE KEY UPDATE
    `account_name` = VALUES(`account_name`);

INSERT IGNORE INTO
    `chart_of_accounts` (
        `account_code`,
        `account_name`,
        `account_type`,
        `parent_id`,
        `is_group`,
        `is_system`
    )
SELECT '4203', 'Electricity Expenses', 'expense', id, 0, 0
FROM `chart_of_accounts`
WHERE
    account_code = '4000'
ON DUPLICATE KEY UPDATE
    `account_name` = VALUES(`account_name`);

INSERT IGNORE INTO
    `chart_of_accounts` (
        `account_code`,
        `account_name`,
        `account_type`,
        `parent_id`,
        `is_group`,
        `is_system`
    )
SELECT '4204', 'Rent Expenses', 'expense', id, 0, 0
FROM `chart_of_accounts`
WHERE
    account_code = '4000'
ON DUPLICATE KEY UPDATE
    `account_name` = VALUES(`account_name`);

INSERT IGNORE INTO
    `chart_of_accounts` (
        `account_code`,
        `account_name`,
        `account_type`,
        `parent_id`,
        `is_group`,
        `is_system`
    )
SELECT '4205', 'Salary Expenses', 'expense', id, 0, 0
FROM `chart_of_accounts`
WHERE
    account_code = '4000'
ON DUPLICATE KEY UPDATE
    `account_name` = VALUES(`account_name`);

INSERT IGNORE INTO
    `chart_of_accounts` (
        `account_code`,
        `account_name`,
        `account_type`,
        `parent_id`,
        `is_group`,
        `is_system`
    )
SELECT '4206', 'Printing & Postage', 'expense', id, 0, 0
FROM `chart_of_accounts`
WHERE
    account_code = '4000'
ON DUPLICATE KEY UPDATE
    `account_name` = VALUES(`account_name`);

INSERT IGNORE INTO
    `chart_of_accounts` (
        `account_code`,
        `account_name`,
        `account_type`,
        `parent_id`,
        `is_group`,
        `is_system`
    )
SELECT '4207', 'Telephone & Internet', 'expense', id, 0, 0
FROM `chart_of_accounts`
WHERE
    account_code = '4000'
ON DUPLICATE KEY UPDATE
    `account_name` = VALUES(`account_name`);

INSERT IGNORE INTO
    `chart_of_accounts` (
        `account_code`,
        `account_name`,
        `account_type`,
        `parent_id`,
        `is_group`,
        `is_system`
    )
SELECT '4208', 'Maintenance Expenses', 'expense', id, 0, 0
FROM `chart_of_accounts`
WHERE
    account_code = '4000'
ON DUPLICATE KEY UPDATE
    `account_name` = VALUES(`account_name`);

INSERT IGNORE INTO
    `chart_of_accounts` (
        `account_code`,
        `account_name`,
        `account_type`,
        `parent_id`,
        `is_group`,
        `is_system`
    )
SELECT '4209', 'Legal & Professional', 'expense', id, 0, 0
FROM `chart_of_accounts`
WHERE
    account_code = '4000'
ON DUPLICATE KEY UPDATE
    `account_name` = VALUES(`account_name`);

INSERT IGNORE INTO
    `chart_of_accounts` (
        `account_code`,
        `account_name`,
        `account_type`,
        `parent_id`,
        `is_group`,
        `is_system`
    )
SELECT '4210', 'Miscellaneous Expenses', 'expense', id, 0, 0
FROM `chart_of_accounts`
WHERE
    account_code = '4000'
ON DUPLICATE KEY UPDATE
    `account_name` = VALUES(`account_name`);

-- Add Membership Fee Income account
INSERT IGNORE INTO
    `chart_of_accounts` (
        `account_code`,
        `account_name`,
        `account_type`,
        `parent_id`,
        `is_group`,
        `is_system`
    )
SELECT '3400', 'Membership Fee Income', 'income', id, 0, 0
FROM `chart_of_accounts`
WHERE
    account_code = '3000'
ON DUPLICATE KEY UPDATE
    `account_name` = VALUES(`account_name`);