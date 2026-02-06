-- Migration 018: Add created_by to financial_years
-- Adds created_by (INT UNSIGNED) so controller can set creator id

ALTER TABLE `financial_years`
ADD COLUMN `created_by` INT(10) UNSIGNED DEFAULT NULL AFTER `closed_by`;

-- Optionally add an index if you filter by created_by often
-- ALTER TABLE `financial_years` ADD INDEX `idx_created_by` (`created_by`);