-- Migration: Add calculation_type column to fine_rules table
-- Version: 008
-- Date: 2025-01-27

-- Add calculation_type column to fine_rules table
ALTER TABLE `fine_rules` ADD COLUMN `calculation_type` ENUM(
    'fixed',
    'percentage',
    'per_day',
    'slab'
) NOT NULL DEFAULT 'fixed' AFTER `fine_type`;

-- Update existing rules to have proper calculation_type based on current logic
-- For now, set all existing rules to 'fixed' as default
UPDATE `fine_rules` SET `calculation_type` = 'fixed' WHERE `calculation_type` = '' OR `calculation_type` IS NULL;