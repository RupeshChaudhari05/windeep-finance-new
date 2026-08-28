-- ============================================================
-- Migration: Define forward financial years
-- Date: 2026-08-26
-- Purpose: The ledger assigns every voucher to a financial year.
--          Transactions exist dated up to Apr-2029 (bulk-imported
--          savings deposits), but financial_years stopped at
--          2026-27, so those entries could not be posted and the
--          savings control account would never reconcile.
--          Define the years forward so the ledger stays complete.
-- ============================================================

INSERT INTO `financial_years` (`year_code`, `start_date`, `end_date`, `is_active`, `is_closed`)
SELECT * FROM (
    SELECT '2027-28' AS a, '2027-04-01' AS b, '2028-03-31' AS c, 0 AS d, 0 AS e
    UNION ALL SELECT '2028-29', '2028-04-01', '2029-03-31', 0, 0
    UNION ALL SELECT '2029-30', '2029-04-01', '2030-03-31', 0, 0
) t
WHERE NOT EXISTS (
    SELECT 1 FROM `financial_years` f WHERE f.`year_code` = t.a
);

INSERT INTO `schema_migrations` (`filename`, `applied_at`)
VALUES ('030_financial_years_forward.sql', NOW())
ON DUPLICATE KEY UPDATE `applied_at` = NOW();

-- ============================================================
-- Verification
-- ============================================================
-- SELECT year_code, start_date, end_date FROM financial_years ORDER BY start_date;
