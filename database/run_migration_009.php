-- Migration: Add manual transaction columns to bank_transactions table
-- Version: 009
-- Date: 2025-01-27

-- Add columns for manual transaction recording
ALTER TABLE `bank_transactions`
ADD COLUMN `paid_by_member_id` INT UNSIGNED COMMENT 'Member who made the payment' AFTER `detection_confidence`,
ADD COLUMN `paid_for_member_id` INT UNSIGNED COMMENT 'Member who received the payment' AFTER `paid_by_member_id`,
ADD COLUMN `updated_by` INT UNSIGNED COMMENT 'Admin who recorded this transaction' AFTER `paid_for_member_id`;

-- Add foreign key constraints
ALTER TABLE `bank_transactions`
ADD CONSTRAINT `fk_bank_transactions_paid_by_member` FOREIGN KEY (`paid_by_member_id`) REFERENCES `members`(`id`),
ADD CONSTRAINT `fk_bank_transactions_paid_for_member` FOREIGN KEY (`paid_for_member_id`) REFERENCES `members`(`id`),
ADD CONSTRAINT `fk_bank_transactions_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `admin_users`(`id`);