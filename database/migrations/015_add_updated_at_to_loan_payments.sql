-- Migration: Ensure loan_payments has updated_at column
ALTER TABLE `loan_payments`
ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;