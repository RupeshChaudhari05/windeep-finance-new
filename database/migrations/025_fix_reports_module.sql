-- Migration 025: Reports module fixes (RPT-2)
-- Date: 2026-02-28
-- Issue: RPT-2 — Fiscal year start month now configurable via system_settings

-- ============================================================
-- RPT-2: Add fiscal_year_start_month setting (default 4 = April)
-- Used by profit_loss() and email reports for date defaults.
-- ============================================================
INSERT INTO
    `system_settings` (
        `setting_key`,
        `setting_value`,
        `setting_type`
    )
SELECT 'fiscal_year_start_month', '4', 'number'
FROM DUAL
WHERE
    NOT EXISTS (
        SELECT 1
        FROM `system_settings`
        WHERE
            `setting_key` = 'fiscal_year_start_month'
    );