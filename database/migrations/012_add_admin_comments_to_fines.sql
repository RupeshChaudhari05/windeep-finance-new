-- Migration: Add admin_comments to fines
ALTER TABLE `fines`
ADD COLUMN `admin_comments` TEXT NULL AFTER `waiver_denied_reason`;