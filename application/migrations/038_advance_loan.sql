-- ============================================================
-- Migration: Advance loan (loan product type)
-- Date: 2026-10-29
-- Purpose:
--   A loan product can be marked "Advance loan". For such loans:
--   * EMI / schedule are on the full sanctioned amount and start from the first EMI date,
--     even before any money is released (EMIs paid before release are normal loan repayments);
--   * the money is released later, in parts, as per a plan (date + amount per part) fixed at sanction;
--   * each planned part waits in disbursement_tracking with status 'pending' and is released
--     (status 'completed', ledger posted) on or after its planned date, for exactly the planned amount;
--   * the full processing fee is withheld from part 1 when it is released.
--
--   * loan_products.is_advance_loan    – product works as an advance loan
--   * loans.is_advance                 – copied from the product when the loan is created
--   * disbursement_tracking.planned_date – planned release date (disbursement_date = actual date once released)
-- Requires migration 035 (part disbursement).
-- ============================================================

ALTER TABLE `loan_products`
    ADD COLUMN IF NOT EXISTS `is_advance_loan` TINYINT(1) NOT NULL DEFAULT 0
        COMMENT 'Advance loan: EMI from the first date on the full amount, money released later as per a fixed plan' AFTER `interest_type`;

ALTER TABLE `loans`
    ADD COLUMN IF NOT EXISTS `is_advance` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_tranche`;

ALTER TABLE `disbursement_tracking`
    ADD COLUMN IF NOT EXISTS `planned_date` DATE NULL DEFAULT NULL AFTER `disbursement_date`;

INSERT INTO `schema_migrations` (`filename`, `applied_at`)
VALUES ('038_advance_loan.sql', NOW())
ON DUPLICATE KEY UPDATE `applied_at` = NOW();

-- Verification:
-- SHOW COLUMNS FROM loan_products LIKE 'is_advance_loan';
-- SELECT COUNT(*) FROM disbursement_tracking WHERE status = 'pending';
