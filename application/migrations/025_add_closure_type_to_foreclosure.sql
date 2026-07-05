-- ============================================================
-- Migration: Add Force Close Support to Foreclosure
-- Date: 2026-07-02
-- Purpose: Add closure_type column to support force close 
--          with only next month interest charging
-- ============================================================

-- Add closure_type column if not exists
ALTER TABLE `loan_foreclosure_requests` 
ADD COLUMN `closure_type` ENUM('regular', 'force_close') 
NOT NULL DEFAULT 'regular' 
COMMENT 'Type of foreclosure: regular (full amount) or force_close (next month interest only)' 
AFTER `foreclosure_amount`;

-- Create index for faster filtering
ALTER TABLE `loan_foreclosure_requests`
ADD INDEX `idx_closure_type` (`closure_type`);

-- Update schema_migrations table
INSERT INTO `schema_migrations` (`filename`, `applied_at`) 
VALUES ('025_add_closure_type_to_foreclosure.sql', NOW())
ON DUPLICATE KEY UPDATE `applied_at` = NOW();

-- ============================================================
-- Verification Query
-- ============================================================
-- SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_COMMENT 
-- FROM INFORMATION_SCHEMA.COLUMNS 
-- WHERE TABLE_NAME = 'loan_foreclosure_requests' 
-- AND COLUMN_NAME = 'closure_type';
