-- ============================================================
-- Migration: Savings / Security Deposit account closure (with closing fee)
-- Date: 2026-11-02
-- Purpose:
--   A member requests to close a savings (security deposit) account; an admin approves or rejects it.
--   On approval the closing fee (setting savings_closing_fee, default 150, editable / waivable by the
--   admin with a reason) is deducted, the rest of the balance is paid out to the member, and the
--   account is closed with a zero balance.
--   * savings_closure_requests          – one row per request
--   * chart_of_accounts 3500            – Account Closing Fee Income (Dr 2101 / Cr 3500 for the fee)
--   * system_settings savings_closing_fee
-- ============================================================

CREATE TABLE IF NOT EXISTS `savings_closure_requests` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `savings_account_id` INT UNSIGNED NOT NULL,
    `member_id` INT UNSIGNED NOT NULL,
    `reason` TEXT NOT NULL,
    `status` ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
    `balance_at_request` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `requested_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `balance_at_closure` DECIMAL(15,2) NULL DEFAULT NULL,
    `setting_fee` DECIMAL(15,2) NULL DEFAULT NULL COMMENT 'Fee in settings when approved',
    `fee_amount` DECIMAL(15,2) NULL DEFAULT NULL COMMENT 'Fee actually deducted',
    `fee_change_reason` VARCHAR(255) NULL DEFAULT NULL,
    `refund_amount` DECIMAL(15,2) NULL DEFAULT NULL,
    `payout_mode` ENUM('cash','bank_transfer','cheque','upi') NULL DEFAULT NULL,
    `payout_date` DATE NULL DEFAULT NULL,
    `payout_reference` VARCHAR(100) NULL DEFAULT NULL,
    `fee_transaction_id` INT UNSIGNED NULL DEFAULT NULL,
    `refund_transaction_id` INT UNSIGNED NULL DEFAULT NULL,
    `admin_remarks` TEXT NULL,
    `processed_by` INT UNSIGNED NULL DEFAULT NULL,
    `processed_at` DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_scr_account` (`savings_account_id`),
    KEY `idx_scr_member` (`member_id`),
    KEY `idx_scr_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `chart_of_accounts` (`account_code`, `account_name`, `account_type`, `parent_id`, `is_group`, `is_system`,
                                 `opening_balance`, `current_balance`, `is_active`, `description`)
SELECT '3500', 'Account Closing Fee Income', 'income', p.id, 0, 1, 0, 0, 1,
       'Fee deducted when a savings / security deposit account is closed'
FROM `chart_of_accounts` p
WHERE p.account_code = '3000'
  AND NOT EXISTS (SELECT 1 FROM `chart_of_accounts` x WHERE x.account_code = '3500');

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_group`, `setting_type`, `description`, `is_editable`)
SELECT 'savings_closing_fee', '150', 'savings', 'number', 'Fee deducted when a savings / security deposit account is closed', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `system_settings` WHERE `setting_key` = 'savings_closing_fee');

INSERT INTO `schema_migrations` (`filename`, `applied_at`)
VALUES ('039_savings_closure.sql', NOW())
ON DUPLICATE KEY UPDATE `applied_at` = NOW();

-- Verification:
-- SELECT * FROM chart_of_accounts WHERE account_code = '3500';
-- SELECT setting_value FROM system_settings WHERE setting_key = 'savings_closing_fee';
