-- ============================================================
-- Migration: Add employer_name to members
-- Date: 2026-08-22
-- Purpose: The member create/edit screens collect
--          "Employer / Business Name", but the members table had no
--          column for it, so the value was silently discarded on
--          every save. Add the column so the field persists.
-- ============================================================

ALTER TABLE `members`
    ADD COLUMN IF NOT EXISTS `employer_name` VARCHAR(150) NULL DEFAULT NULL
        COMMENT 'Employer or business name'
        AFTER `monthly_income`;

-- Track this migration
INSERT INTO `schema_migrations` (`filename`, `applied_at`)
VALUES ('028_add_member_employer_name.sql', NOW())
ON DUPLICATE KEY UPDATE `applied_at` = NOW();

-- ============================================================
-- Verification
-- ============================================================
-- SHOW COLUMNS FROM `members` LIKE 'employer_name';
