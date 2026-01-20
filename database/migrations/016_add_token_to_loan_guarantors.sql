-- Migration: Add token to loan_guarantors for secure consent links
ALTER TABLE `loan_guarantors`
ADD COLUMN IF NOT EXISTS `consent_token` VARCHAR(64) NULL AFTER `rejection_reason`;