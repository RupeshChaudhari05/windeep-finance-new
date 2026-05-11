-- ============================================
-- BULK IMPORT DATA CLEANUP - SQL QUERIES
-- ============================================
-- Copy and paste these queries into phpMyAdmin SQL tab to run them
-- Execute them in this exact order

-- Step 1: Reset Savings Account Balances
UPDATE savings_accounts
SET
    current_balance = 0,
    total_deposited = 0,
    total_interest_earned = 0,
    total_fines_paid = 0,
    updated_at = NOW();

-- Step 2: Reset Savings Schedule to Pending Status
UPDATE savings_schedule
SET
    status = 'pending',
    paid_amount = 0,
    fine_paid = 0,
    paid_date = NULL,
    is_late = 0,
    days_late = 0,
    updated_at = NOW();

-- Step 3: Delete All Savings Transactions
DELETE FROM savings_transactions;

-- Step 4: Reset Loan Installments to Pending Status
UPDATE loan_installments
SET
    principal_paid = 0,
    interest_paid = 0,
    fine_paid = 0,
    total_paid = 0,
    status = 'pending',
    paid_date = NULL,
    is_late = 0,
    days_late = 0,
    updated_at = NOW()
WHERE
    status != 'upcoming';

-- Step 5: Reset Loan Outstanding Amounts
UPDATE loans
SET
    outstanding_principal = principal_amount,
    outstanding_interest = total_interest,
    outstanding_fine = 0,
    total_amount_paid = 0,
    total_principal_paid = 0,
    total_interest_paid = 0,
    total_fine_paid = 0,
    updated_at = NOW();

-- Step 6: Delete All Loan Payments
DELETE FROM loan_payments;

-- Step 7: Delete All Fines
DELETE FROM fines;

-- ============================================
-- VERIFICATION QUERY (Run after cleanup)
-- ============================================
-- This query shows the counts - should show:
-- savings_txn_count = 0
-- loan_payments_count = 0
-- fines_count = 0
-- members_count > 0
-- loan_apps_count > 0
-- loans_count > 0
-- savings_accounts_count > 0

SELECT (
        SELECT COUNT(*)
        FROM savings_transactions
    ) as savings_txn_count,
    (
        SELECT COUNT(*)
        FROM loan_payments
    ) as loan_payments_count,
    (
        SELECT COUNT(*)
        FROM fines
    ) as fines_count,
    (
        SELECT COUNT(*)
        FROM members
    ) as members_count,
    (
        SELECT COUNT(*)
        FROM loan_applications
    ) as loan_apps_count,
    (
        SELECT COUNT(*)
        FROM loans
    ) as loans_count,
    (
        SELECT COUNT(*)
        FROM savings_accounts
    ) as savings_accounts_count;