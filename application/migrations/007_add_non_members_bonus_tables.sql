-- Migration 007: Add Non-Members (Fund Providers), Bonus Transactions tables
-- Date: 2026-03-01

-- ═══════════════════════════════════════════════════════════
-- 1. Non-Members / Fund Providers Table
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `non_members` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_by` INT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_non_members_status` (`status`),
    INDEX `idx_non_members_phone` (`phone`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ═══════════════════════════════════════════════════════════
-- 2. Non-Member Fund Transactions
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `non_member_funds` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `non_member_id` INT NOT NULL,
    `amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    `transaction_type` ENUM('received', 'returned') NOT NULL DEFAULT 'received',
    `transaction_date` DATE NOT NULL,
    `payment_mode` VARCHAR(50) DEFAULT 'cash',
    `reference_number` VARCHAR(100) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `created_by` INT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_nmf_non_member` (`non_member_id`),
    INDEX `idx_nmf_date` (`transaction_date`),
    FOREIGN KEY (`non_member_id`) REFERENCES `non_members` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ═══════════════════════════════════════════════════════════
-- 3. Bonus Transactions (for savings/security deposit)
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `bonus_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `member_id` INT NOT NULL,
    `savings_account_id` INT DEFAULT NULL,
    `bonus_year` YEAR NOT NULL,
    `amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    `description` VARCHAR(500) DEFAULT NULL,
    `status` ENUM(
        'pending',
        'credited',
        'reversed'
    ) NOT NULL DEFAULT 'credited',
    `savings_transaction_id` INT DEFAULT NULL COMMENT 'FK to savings_transactions when bonus credited',
    `created_by` INT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_bonus_member` (`member_id`),
    INDEX `idx_bonus_year` (`bonus_year`),
    INDEX `idx_bonus_status` (`status`),
    FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ═══════════════════════════════════════════════════════════
-- 4. Ensure loan_foreclosure_requests table exists
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `loan_foreclosure_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `loan_id` INT NOT NULL,
    `member_id` INT NOT NULL,
    `foreclosure_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    `reason` TEXT DEFAULT NULL,
    `settlement_date` DATE DEFAULT NULL,
    `status` ENUM(
        'pending',
        'approved',
        'rejected',
        'cancelled'
    ) NOT NULL DEFAULT 'pending',
    `requested_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `processed_by` INT DEFAULT NULL,
    `processed_at` DATETIME DEFAULT NULL,
    `admin_comments` TEXT DEFAULT NULL,
    INDEX `idx_lfr_loan` (`loan_id`),
    INDEX `idx_lfr_member` (`member_id`),
    INDEX `idx_lfr_status` (`status`),
    FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ═══════════════════════════════════════════════════════════
-- 5. Ensure expense_transactions table exists (for office expense tracking)
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `expense_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `bank_transaction_id` INT DEFAULT NULL,
    `expense_category` VARCHAR(100) NOT NULL DEFAULT 'office',
    `amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    `description` TEXT DEFAULT NULL,
    `expense_date` DATE NOT NULL,
    `created_by` INT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_expense_category` (`expense_category`),
    INDEX `idx_expense_date` (`expense_date`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;