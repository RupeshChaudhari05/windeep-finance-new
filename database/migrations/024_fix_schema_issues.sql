-- Migration 024: Fix remaining schema issues (SCH-5, SCH-7) and add performance indexes
-- Date: 2026-02-28
-- Issues: SCH-5 (fines.loan_id), SCH-7 (currency_symbol), missing composite indexes

-- ============================================================
-- SCH-5: Add loan_id column to fines table for direct lookups
-- The code already works around this via installment-based joins,
-- but this column enables efficient direct queries.
-- ============================================================
ALTER TABLE `fines`
ADD COLUMN `loan_id` INT(10) UNSIGNED NULL DEFAULT NULL AFTER `member_id`,
ADD INDEX `idx_fines_loan_id` (`loan_id`);

-- Backfill loan_id from related loan_installment records
UPDATE `fines` f
JOIN `loan_installments` li ON f.related_id = li.id
SET
    f.loan_id = li.loan_id
WHERE
    f.related_type = 'loan_installment'
    AND f.loan_id IS NULL;

-- ============================================================
-- SCH-7: Fix currency symbol (UTF-8 encoding issue)
-- ============================================================
UPDATE `system_settings`
SET
    `setting_value` = '₹'
WHERE
    `setting_key` = 'currency_symbol';

-- ============================================================
-- Performance indexes: composite indexes for common query patterns
-- ============================================================

-- fines: member_id + status composite (for member fines lookup)
-- Single-column indexes exist but composite is needed for WHERE member_id=? AND status=?
ALTER TABLE `fines`
ADD INDEX `idx_fines_member_status` (`member_id`, `status`);

-- fines: related_type + related_id composite (for foreclosure fine waiver lookups)
ALTER TABLE `fines`
ADD INDEX `idx_fines_related` (`related_type`, `related_id`);

-- savings_schedule: due_date + status composite (for overdue detection)
-- Individual indexes exist but composite avoids index merge
ALTER TABLE `savings_schedule`
ADD INDEX `idx_savings_schedule_due_status` (`due_date`, `status`);

-- loan_installments: already has idx_installments_status_due_date (status, due_date) — OK

-- bank_transactions: already has idx_transaction_date — OK