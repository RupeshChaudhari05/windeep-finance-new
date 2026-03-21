-- Migration: Add missing columns to bank_imports
-- Adds: status (ENUM), matched_id (INT), created_at (TIMESTAMP)
-- Uses IF NOT EXISTS via stored procedure for MySQL 5.7 compatibility

DROP PROCEDURE IF EXISTS _add_bank_imports_cols;

DELIMITER / /

CREATE PROCEDURE _add_bank_imports_cols()
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='bank_imports' AND COLUMN_NAME='status') THEN
    ALTER TABLE `bank_imports` ADD COLUMN `status` ENUM('uploaded','parsing','parsed','mapping','completed','failed') DEFAULT 'uploaded';
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='bank_imports' AND COLUMN_NAME='matched_id') THEN
    ALTER TABLE `bank_imports` ADD COLUMN `matched_id` INT(11) DEFAULT NULL;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='bank_imports' AND COLUMN_NAME='created_at') THEN
    ALTER TABLE `bank_imports` ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
  END IF;
END//

DELIMITER;

CALL _add_bank_imports_cols ();

DROP PROCEDURE IF EXISTS _add_bank_imports_cols;