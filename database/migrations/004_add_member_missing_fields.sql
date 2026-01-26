-- Migration: Add missing member fields
-- Date: 2026-01-26
-- Description: Adds marital_status, occupation, monthly_income and fixes field name mismatches

ALTER TABLE `members`
ADD COLUMN `marital_status` ENUM(
    'single',
    'married',
    'divorced',
    'widowed'
) NULL AFTER `gender`,
ADD COLUMN `occupation` VARCHAR(100) NULL AFTER `marital_status`,
ADD COLUMN `monthly_income` DECIMAL(15, 2) NULL AFTER `occupation`;

-- Add alternative field names for compatibility
ALTER TABLE `members`
ADD COLUMN `id_proof_type` VARCHAR(50) NULL AFTER `address_proof_doc`,
ADD COLUMN `id_proof_number` VARCHAR(50) NULL AFTER `id_proof_type`,
ADD COLUMN `bank_account_number` VARCHAR(30) NULL AFTER `account_number`,
ADD COLUMN `bank_ifsc` VARCHAR(15) NULL AFTER `bank_account_number`,
ADD COLUMN `nominee_relationship` VARCHAR(50) NULL AFTER `nominee_relation`,
ADD COLUMN `profile_image` VARCHAR(255) NULL AFTER `photo`;

-- Note: You can optionally copy data from old columns to new columns if needed:
-- UPDATE `members` SET `bank_account_number` = `account_number` WHERE `bank_account_number` IS NULL;
-- UPDATE `members` SET `bank_ifsc` = `ifsc_code` WHERE `bank_ifsc` IS NULL;
-- UPDATE `members` SET `nominee_relationship` = `nominee_relation` WHERE `nominee_relationship` IS NULL;
-- UPDATE `members` SET `profile_image` = `photo` WHERE `profile_image` IS NULL;