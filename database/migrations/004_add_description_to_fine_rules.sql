-- Migration: Add description column to fine_rules
-- Date: 2025-12-26

ALTER TABLE `fine_rules`
ADD COLUMN IF NOT EXISTS `description` VARCHAR(255) NULL AFTER `rule_name`;