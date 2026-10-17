-- ============================================================
-- Migration: Foreclosure / force close schema fixes
-- Date: 2026-10-17
-- Purpose:
--  1. Approving a force-close request writes loans.closure_type = 'force_close',
--     which the loans ENUM did not allow ("Data truncated for column 'closure_type'").
--  2. Closing a loan cancels its future EMIs with loan_installments.status = 'cancelled',
--     which that ENUM did not allow either. Outside strict mode MySQL stored '' instead,
--     so foreclosed loans have installments with a blank status — repaired below.
--  Existing values are kept unchanged.
-- ============================================================

ALTER TABLE `loans`
MODIFY COLUMN `closure_type` ENUM('regular', 'foreclosure', 'force_close', 'write_off', 'settlement', 'topup') NULL DEFAULT NULL;

ALTER TABLE `loan_installments`
MODIFY COLUMN `status` ENUM('upcoming', 'pending', 'partial', 'paid', 'overdue', 'skipped', 'interest_only', 'waived', 'cancelled') NULL DEFAULT 'upcoming';

-- Repair EMIs that were meant to be cancelled when their loan was closed
UPDATE `loan_installments` li
JOIN `loans` l ON l.id = li.loan_id
SET li.status = 'cancelled'
WHERE li.status = ''
  AND l.status IN ('foreclosed', 'closed')
  AND (li.remarks LIKE 'Cancelled on loan%' OR li.skip_reason IN ('Foreclosure settlement', 'Loan fully settled'));

INSERT INTO `schema_migrations` (`filename`, `applied_at`)
VALUES ('034_loans_closure_type_force_close.sql', NOW())
ON DUPLICATE KEY UPDATE `applied_at` = NOW();

-- Verification:
-- SHOW COLUMNS FROM `loans` LIKE 'closure_type';
-- SHOW COLUMNS FROM `loan_installments` LIKE 'status';
-- SELECT status, COUNT(*) FROM loan_installments GROUP BY status;   -- no '' rows expected
