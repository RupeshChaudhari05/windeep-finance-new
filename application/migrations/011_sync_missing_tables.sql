-- ============================================================
-- Migration 011: Sync Missing Tables
-- ============================================================
-- 1. view_requests  — existed in live DB but was never in any
--    SQL schema file, so DB Health Check could not track it.
--    Adding the canonical definition here so the health check
--    accepts it as a known/required table.
--
-- 2. guarantor_settings  — defined in migration 003 but never
--    applied to the live DB. Idempotent IF NOT EXISTS guard.
--
-- 3. accounting_settings — same situation as guarantor_settings.
--
-- 4. bank_imports  — legacy import staging table from migration
--    003; added here with IF NOT EXISTS for health-check parity.
-- ============================================================

-- -------------------------------------------------------
-- 1. view_requests
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `view_requests` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `member_from` VARCHAR(50) DEFAULT NULL,
    `member_to` VARCHAR(50) DEFAULT NULL,
    `admin_reason` TEXT DEFAULT NULL,
    `guarantor_reason` TEXT DEFAULT NULL,
    `status` INT(11) DEFAULT 1,
    `created_by` VARCHAR(50) DEFAULT NULL,
    `amount` DECIMAL(10, 2) DEFAULT NULL,
    `period` INT(11) DEFAULT NULL,
    `unit` VARCHAR(20) DEFAULT NULL,
    `emi` DECIMAL(10, 2) DEFAULT NULL,
    `processing_fee` DECIMAL(10, 2) DEFAULT NULL,
    `is_deleted` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `member_from` (`member_from`),
    KEY `member_to` (`member_to`),
    KEY `status` (`status`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- -------------------------------------------------------
-- 2. guarantor_settings (from migration 003, never applied)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `guarantor_settings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

INSERT IGNORE INTO
    `guarantor_settings` (
        `setting_key`,
        `setting_value`,
        `description`
    )
VALUES (
        'min_guarantors',
        '2',
        'Minimum number of guarantors required'
    ),
    (
        'max_guarantors',
        '4',
        'Maximum number of guarantors allowed'
    ),
    (
        'min_coverage_percentage',
        '100',
        'Minimum coverage percentage required'
    ),
    (
        'guarantor_must_be_member',
        '1',
        'Guarantor must be a registered member'
    ),
    (
        'guarantor_max_active_guarantees',
        '3',
        'Maximum active guarantees a member can give'
    ),
    (
        'guarantor_min_membership_days',
        '30',
        'Minimum membership days before can be guarantor'
    ),
    (
        'allow_self_guarantee',
        '0',
        'Allow member to guarantee their own loan'
    ),
    (
        'guarantor_liability_type',
        'joint',
        'Liability type: joint or several'
    ),
    (
        'auto_debit_from_guarantor',
        '1',
        'Auto debit from guarantor if borrower defaults'
    ),
    (
        'notify_guarantor_on_default',
        '1',
        'Send notification to guarantor on default'
    );

-- -------------------------------------------------------
-- 3. accounting_settings (from migration 003, never applied)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `accounting_settings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

INSERT IGNORE INTO
    `accounting_settings` (
        `setting_key`,
        `setting_value`,
        `description`
    )
VALUES (
        'default_cash_account',
        '1',
        'Default cash account ID'
    ),
    (
        'default_bank_account',
        '1',
        'Default bank account ID'
    ),
    (
        'fiscal_year_start',
        '04-01',
        'Fiscal year start month-day'
    ),
    (
        'auto_close_fiscal_year',
        '0',
        'Auto close fiscal year'
    ),
    (
        'depreciation_method',
        'straight_line',
        'Default depreciation method'
    ),
    (
        'rounding_precision',
        '2',
        'Decimal places for rounding'
    ),
    (
        'enable_double_entry',
        '1',
        'Enable double-entry bookkeeping'
    ),
    (
        'default_currency',
        'INR',
        'Default currency code'
    ),
    (
        'currency_symbol',
        '₹',
        'Currency symbol'
    );

-- -------------------------------------------------------
-- 4. bank_imports (staging table from migration 003)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bank_imports` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `import_id` INT(11) DEFAULT NULL,
    `row_number` INT(11) DEFAULT NULL,
    `transaction_date` DATE DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `debit` DECIMAL(15, 2) DEFAULT 0.00,
    `credit` DECIMAL(15, 2) DEFAULT 0.00,
    `balance` DECIMAL(15, 2) DEFAULT NULL,
    `status` ENUM(
        'pending',
        'matched',
        'ignored',
        'error'
    ) DEFAULT 'pending',
    `matched_id` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `import_id` (`import_id`),
    KEY `status` (`status`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;