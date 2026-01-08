-- Migration: Add rule_code_sequence table for auto-generating fine rule codes
-- Version: 007
-- Date: 2025-01-27

-- Add rule_code_sequence table
CREATE TABLE IF NOT EXISTS `rule_code_sequence` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `prefix` VARCHAR(10) NOT NULL DEFAULT 'FR',
    `current_number` INT UNSIGNED NOT NULL DEFAULT 0,
    `year` YEAR NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_prefix_year` (`prefix`, `year`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Insert initial sequence for current year
INSERT IGNORE INTO `rule_code_sequence` (`prefix`, `current_number`, `year`) VALUES ('FR', 0, YEAR(CURDATE()));