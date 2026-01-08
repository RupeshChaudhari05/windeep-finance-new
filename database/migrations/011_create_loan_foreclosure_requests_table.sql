-- Create loan foreclosure requests table
CREATE TABLE IF NOT EXISTS `loan_foreclosure_requests` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `loan_id` INT UNSIGNED NOT NULL,
    `member_id` INT UNSIGNED NOT NULL,
    `foreclosure_amount` DECIMAL(15, 2) NOT NULL,
    `reason` TEXT NOT NULL,
    `settlement_date` DATE NOT NULL,
    `status` ENUM(
        'pending',
        'approved',
        'rejected'
    ) DEFAULT 'pending',
    `requested_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `processed_by` INT UNSIGNED NULL,
    `processed_at` TIMESTAMP NULL,
    `admin_comments` TEXT,
    PRIMARY KEY (`id`),
    KEY `idx_loan_id` (`loan_id`),
    KEY `idx_member_id` (`member_id`),
    KEY `idx_status` (`status`),
    FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`processed_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;