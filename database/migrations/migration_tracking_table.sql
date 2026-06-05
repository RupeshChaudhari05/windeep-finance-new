-- ============================================================================
-- Migration Tracking Table
-- Date: 2026-06-05
-- Purpose: Track all migrations executed on the database
-- ============================================================================

CREATE TABLE IF NOT EXISTS `migrations` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `migration_name` VARCHAR(255) NOT NULL UNIQUE COMMENT 'File name of migration',
    `migration_file` LONGTEXT NOT NULL COMMENT 'Full migration SQL content',
    `status` ENUM(
        'pending',
        'running',
        'completed',
        'failed',
        'rolled_back'
    ) DEFAULT 'pending' COMMENT 'Current status',
    `executed_by` INT UNSIGNED COMMENT 'admin_id who ran migration',
    `execution_timestamp` TIMESTAMP NULL COMMENT 'When migration was executed',
    `completion_timestamp` TIMESTAMP NULL COMMENT 'When migration finished',
    `duration_seconds` INT UNSIGNED COMMENT 'How long migration took',
    `error_message` TEXT COMMENT 'If failed, error details',
    `output_log` LONGTEXT COMMENT 'Migration execution output',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`),
    KEY `idx_executed_by` (`executed_by`),
    KEY `idx_created_at` (`created_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Insert the loan schedule integrity migration into tracking (if not already run)
INSERT INTO
    `migrations` (
        `migration_name`,
        `migration_file`,
        `status`
    )
SELECT 'loan_schedule_integrity_constraints.sql', '', 'pending'
WHERE
    NOT EXISTS (
        SELECT 1
        FROM migrations
        WHERE
            migration_name = 'loan_schedule_integrity_constraints.sql'
    );