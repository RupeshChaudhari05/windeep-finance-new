-- ============================================================
-- Migration: Fine data repair
-- Date: 2026-10-20
-- Purpose:
--   * Manual fines saved before Fine_model::create_fine() validated its input
--     have an empty fine_type, related_type 'savings_schedule' with related_id 0
--     and due_date 0000-00-00. They are manual "penalty interest" entries:
--     mark them 'other' so reports group them correctly and the late-fine
--     engine never treats them as savings-schedule fines.
--   * Fine rules saved with an empty calculation type (the old settings modal
--     posted values outside the enum) get a real type back.
-- Safe to run more than once.
-- ============================================================

UPDATE `fines`
SET `fine_type` = 'other'
WHERE `fine_type` = '' OR `fine_type` IS NULL;

UPDATE `fines`
SET `related_type` = 'other'
WHERE `related_id` = 0 AND `related_type` <> 'other';

UPDATE `fines`
SET `due_date` = `fine_date`
WHERE `due_date` < '1900-01-01';

UPDATE `fine_rules`
SET `calculation_type` = IF(`fine_type` <> '', `fine_type`, IF(`per_day_amount` > 0, 'per_day', 'fixed'))
WHERE `calculation_type` = '' OR `calculation_type` IS NULL;

UPDATE `fine_rules`
SET `fine_type` = `calculation_type`
WHERE `fine_type` = '' OR `fine_type` IS NULL;

INSERT INTO `schema_migrations` (`filename`, `applied_at`)
VALUES ('037_fine_data_repair.sql', NOW())
ON DUPLICATE KEY UPDATE `applied_at` = NOW();
