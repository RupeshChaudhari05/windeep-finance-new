-- ============================================================
-- Migration: Foreclosure — 30% interest rule + admin override
-- Date: 2026-08-12
-- Purpose:
--   1. Store the amount/percentage the admin actually approved
--      (admin can edit both on the approval screen).
--   2. Default the foreclosure interest charge to 30% of the
--      remaining (current + future) scheduled interest.
--   3. Backfill closure_type, which some installs never received
--      from migration 025.
-- ============================================================

-- 1. Columns that record what the admin approved -------------
ALTER TABLE `loan_foreclosure_requests`
    ADD COLUMN IF NOT EXISTS `closure_type` ENUM('regular', 'force_close')
        NOT NULL DEFAULT 'regular'
        COMMENT 'Type of foreclosure: regular (full amount) or force_close'
        AFTER `foreclosure_amount`;

ALTER TABLE `loan_foreclosure_requests`
    ADD COLUMN IF NOT EXISTS `approved_amount` DECIMAL(15, 2) NULL DEFAULT NULL
        COMMENT 'Final settlement amount approved/collected by admin (may differ from foreclosure_amount)'
        AFTER `closure_type`;

ALTER TABLE `loan_foreclosure_requests`
    ADD COLUMN IF NOT EXISTS `approved_interest_pct` DECIMAL(5, 2) NULL DEFAULT NULL
        COMMENT 'Interest charge %% applied by admin at approval time'
        AFTER `approved_amount`;

-- 2. Foreclosure interest charge = 30% of remaining interest --
INSERT INTO `system_settings` (`setting_key`, `setting_value`)
VALUES ('foreclosure_interest_charge_pct', '30')
ON DUPLICATE KEY UPDATE `setting_value` = '30';

-- Legacy/unused key kept in sync to avoid confusion in the UI
UPDATE `system_settings`
SET `setting_value` = '30'
WHERE `setting_key` = 'foreclosure_pending_interest_charge_pct';

-- 3. Track this migration ------------------------------------
INSERT INTO `schema_migrations` (`filename`, `applied_at`)
VALUES ('026_foreclosure_admin_override.sql', NOW())
ON DUPLICATE KEY UPDATE `applied_at` = NOW();

-- ============================================================
-- Verification
-- ============================================================
-- SELECT COLUMN_NAME, COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS
--  WHERE TABLE_NAME = 'loan_foreclosure_requests'
--    AND COLUMN_NAME IN ('closure_type','approved_amount','approved_interest_pct');
-- SELECT setting_key, setting_value FROM system_settings
--  WHERE setting_key LIKE '%foreclos%';
