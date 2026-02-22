-- Migration 004: Allow loan_product_id to be NULL in loan_applications
-- Member applies without selecting a scheme; admin assigns it during approval.

-- 1. Make loan_product_id nullable in loan_applications
ALTER TABLE `loan_applications`
MODIFY COLUMN `loan_product_id` INT UNSIGNED NULL DEFAULT NULL;

-- Done.