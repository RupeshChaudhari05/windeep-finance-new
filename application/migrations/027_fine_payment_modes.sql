-- ============================================================
-- Migration: Fine payment modes
-- Date: 2026-08-17
-- Purpose: The "Collect Fine Payment" form offers UPI and
--          Security Deposit Deduction, but fines.payment_mode only
--          allowed cash/cheque/bank_transfer/online. Because MySQL
--          is not in STRICT mode, those two choices were silently
--          written as an empty string — the payment mode was lost.
--          Widen the enum so every option the form offers is stored.
-- ============================================================

ALTER TABLE `fines`
    MODIFY COLUMN `payment_mode` ENUM(
        'cash',
        'cheque',
        'bank_transfer',
        'online',
        'upi',
        'savings_deduction'
    ) NULL DEFAULT NULL;

-- Track this migration
INSERT INTO `schema_migrations` (`filename`, `applied_at`)
VALUES ('027_fine_payment_modes.sql', NOW())
ON DUPLICATE KEY UPDATE `applied_at` = NOW();

-- ============================================================
-- Verification
-- ============================================================
-- SHOW COLUMNS FROM `fines` LIKE 'payment_mode';
