-- ============================================================
-- Migration: Ledger completeness
-- Date: 2026-10-20
-- Purpose:
--   * 4300 Loan Principal Waived (Write-off) — a force close waives the remaining
--     principal; it leaves Member Loans through this expense, not through cash.
--   After running it, post the history with:
--       php index.php cli/ledger_backfill dryrun   (report only)
--       php index.php cli/ledger_backfill run      (post + verify)
-- ============================================================

INSERT INTO `chart_of_accounts` (`account_code`, `account_name`, `account_type`, `parent_id`, `is_group`, `is_system`,
                                 `opening_balance`, `current_balance`, `is_active`, `description`)
SELECT '4300', 'Loan Principal Waived (Write-off)', 'expense', p.id, 0, 1, 0, 0, 1,
       'Principal waived on force close / settlement'
FROM `chart_of_accounts` p
WHERE p.account_code = '4000'
  AND NOT EXISTS (SELECT 1 FROM `chart_of_accounts` x WHERE x.account_code = '4300');

INSERT INTO `schema_migrations` (`filename`, `applied_at`)
VALUES ('036_ledger_write_off_account.sql', NOW())
ON DUPLICATE KEY UPDATE `applied_at` = NOW();
