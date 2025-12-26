-- Add waiver request and approval/denial timestamps/fields
ALTER TABLE `fines`
ADD COLUMN `waiver_requested_by` INT UNSIGNED NULL AFTER `waiver_reason`,
ADD COLUMN `waiver_requested_at` TIMESTAMP NULL AFTER `waiver_requested_by`,
ADD COLUMN `waiver_approved_at` TIMESTAMP NULL AFTER `waiver_approved_by`,
ADD COLUMN `waiver_denied_by` INT UNSIGNED NULL AFTER `waiver_approved_at`,
ADD COLUMN `waiver_denied_at` TIMESTAMP NULL AFTER `waiver_denied_by`,
ADD COLUMN `waiver_denied_reason` VARCHAR(255) NULL AFTER `waiver_denied_at`;