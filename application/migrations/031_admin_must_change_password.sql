-- ============================================================
-- Migration: Force password change after an admin reset
-- Date: 2026-08-28
-- Purpose: When an administrator resets someone's password, the
--          temporary password must be single-use — the account
--          has to set its own password at next login. admin_users
--          had no flag for this (members already have one).
-- ============================================================

ALTER TABLE `admin_users`
    ADD COLUMN IF NOT EXISTS `must_change_password` TINYINT(1) NOT NULL DEFAULT 0
        COMMENT 'Set when a temporary password was issued; forces a change at next login'
        AFTER `password`;

ALTER TABLE `admin_users`
    ADD COLUMN IF NOT EXISTS `password_reset_by` INT UNSIGNED NULL DEFAULT NULL
        COMMENT 'Admin who last reset this password'
        AFTER `password_changed_at`;

INSERT INTO `schema_migrations` (`filename`, `applied_at`)
VALUES ('031_admin_must_change_password.sql', NOW())
ON DUPLICATE KEY UPDATE `applied_at` = NOW();

-- ============================================================
-- Verification
-- ============================================================
-- SHOW COLUMNS FROM `admin_users` LIKE 'must_change_password';
