-- ============================================================
-- Migration 010: Loan Top-up Support
-- ============================================================
-- Industry-standard loan top-up: foreclose existing loan internally,
-- disburse new loan with combined (outstanding + additional) amount.
-- Only the NET additional amount goes to the borrower.
-- ============================================================

-- -------------------------------------------------------
-- 1. Add top-up tracking columns to loans table
-- -------------------------------------------------------
ALTER TABLE loans
ADD COLUMN is_topup TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Whether this loan is a top-up of a previous loan' AFTER status,
ADD COLUMN parent_loan_id INT(10) UNSIGNED NULL DEFAULT NULL COMMENT 'The original loan that was topped up (foreclosed)' AFTER is_topup,
ADD COLUMN topup_amount DECIMAL(15, 2) NULL DEFAULT NULL COMMENT 'The additional top-up amount (new money to borrower)' AFTER parent_loan_id,
ADD COLUMN foreclosed_outstanding DECIMAL(15, 2) NULL DEFAULT NULL COMMENT 'Outstanding principal of parent loan at foreclosure' AFTER topup_amount,
ADD COLUMN foreclosed_interest DECIMAL(15, 2) NULL DEFAULT NULL COMMENT 'Outstanding interest of parent loan at foreclosure' AFTER foreclosed_outstanding;

-- Add 'topup' to existing closure_type ENUM
ALTER TABLE loans
MODIFY COLUMN closure_type ENUM(
    'regular',
    'foreclosure',
    'write_off',
    'settlement',
    'topup'
) NULL DEFAULT NULL;

-- Add index for parent loan lookups
ALTER TABLE loans ADD INDEX idx_parent_loan (parent_loan_id);

-- -------------------------------------------------------
-- 2. Add top-up flag to loan_applications
-- -------------------------------------------------------
ALTER TABLE loan_applications
ADD COLUMN is_topup TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Whether this application is for a top-up loan' AFTER status_remarks,
ADD COLUMN parent_loan_id INT(10) UNSIGNED NULL DEFAULT NULL COMMENT 'The existing active loan being topped up' AFTER is_topup,
ADD COLUMN topup_amount DECIMAL(15, 2) NULL DEFAULT NULL COMMENT 'Additional amount requested on top of outstanding' AFTER parent_loan_id,
ADD COLUMN parent_outstanding_principal DECIMAL(15, 2) NULL DEFAULT NULL COMMENT 'Outstanding principal of parent loan at application time' AFTER topup_amount,
ADD COLUMN parent_outstanding_interest DECIMAL(15, 2) NULL DEFAULT NULL COMMENT 'Outstanding interest of parent loan at application time' AFTER parent_outstanding_principal;

-- -------------------------------------------------------
-- 3. Add top-up configuration to loan_products
-- -------------------------------------------------------
ALTER TABLE loan_products
ADD COLUMN topup_allowed TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Whether top-up is allowed on this product' AFTER prepayment_penalty_percent,
ADD COLUMN topup_min_emis_paid INT(11) NOT NULL DEFAULT 6 COMMENT 'Minimum EMIs that must be paid before top-up eligibility' AFTER topup_allowed,
ADD COLUMN topup_max_overdue INT(11) NOT NULL DEFAULT 0 COMMENT 'Maximum overdue EMIs allowed for top-up eligibility (0=none)' AFTER topup_min_emis_paid,
ADD COLUMN topup_fee_type ENUM('fixed', 'percentage') NULL DEFAULT 'percentage' COMMENT 'Type of top-up processing fee' AFTER topup_max_overdue,
ADD COLUMN topup_fee_value DECIMAL(10, 2) NULL DEFAULT 0.50 COMMENT 'Top-up processing fee value' AFTER topup_fee_type;