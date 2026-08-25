-- ============================================================
-- Migration: Close out the EMI schedule when a loan is closed
-- Date: 2026-08-24
-- Purpose:
--   Foreclosing / settling a loan left its remaining installments
--   sitting as 'upcoming' or 'overdue', so a closed loan still showed
--   future EMIs on its schedule.
--
--   Industry standard on pre-closure is to CANCEL the unrealised
--   future EMIs — the records are retained for audit, but they are no
--   longer receivable. This adds the 'cancelled' status and corrects
--   the loans that were already closed with EMIs left open.
-- ============================================================

-- 1. Add the 'cancelled' status ------------------------------
ALTER TABLE `loan_installments`
    MODIFY COLUMN `status` ENUM(
        'upcoming',
        'pending',
        'partial',
        'paid',
        'overdue',
        'skipped',
        'interest_only',
        'waived',
        'cancelled'
    ) NULL DEFAULT 'upcoming';

-- 2. Backfill: close out schedules of loans already settled ---
--    Only touches installments with NOTHING paid, on loans that are
--    already closed/foreclosed. Partly-paid rows are left untouched
--    so no payment history is ever masked.
UPDATE `loan_installments` li
JOIN `loans` l ON l.id = li.loan_id
SET li.`status`      = 'cancelled',
    li.`is_skipped`  = 1,
    li.`skip_reason` = 'Foreclosure settlement',
    li.`remarks`     = CONCAT('Cancelled on loan closure (backfill ', CURDATE(), ')'),
    li.`updated_at`  = NOW()
WHERE l.`status` IN ('closed', 'foreclosed')
  AND li.`status` IN ('upcoming', 'pending', 'overdue')
  AND COALESCE(li.`total_paid`, 0) = 0;

-- 3. Track this migration ------------------------------------
INSERT INTO `schema_migrations` (`filename`, `applied_at`)
VALUES ('029_cancel_installments_on_closure.sql', NOW())
ON DUPLICATE KEY UPDATE `applied_at` = NOW();

-- ============================================================
-- Verification
-- ============================================================
-- SELECT l.loan_number, l.status,
--        SUM(li.status IN ('upcoming','pending','overdue','partial')) AS still_open
--   FROM loans l JOIN loan_installments li ON li.loan_id = l.id
--  WHERE l.status IN ('closed','foreclosed')
--  GROUP BY l.id HAVING still_open > 0;
