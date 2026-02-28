-- Migration 023: Fix Savings Module Issues (SAV-1 through SAV-8)
-- Date: 2026-03-01
-- Description: Documents savings module fixes. No new columns needed since
--   total_interest_earned, interest_rate already exist in schema.
--   Adds performance index for interest accrual duplicate detection.

-- Index for fast duplicate interest credit check per account+month
-- Used by accrue_monthly_interest() to skip already-credited months
ALTER TABLE savings_transactions
ADD INDEX idx_interest_accrual (
    savings_account_id,
    transaction_type,
    for_month
);

-- Summary of code-level fixes applied:
-- SAV-1: Added calculate_monthly_interest(), accrue_monthly_interest(), post_interest_credit()
--         methods to Savings_model.php for monthly interest calculation and posting.
-- SAV-2: record_payment() now uses SELECT FOR UPDATE + atomic SQL UPDATE
--         (current_balance = current_balance + ?) instead of PHP-side arithmetic.
-- SAV-3: apply_late_fine() now wrapped in trans_begin/trans_commit with
--         SELECT FOR UPDATE on schedule row.
-- SAV-4: All Savings controller methods now call check_permission() with
--         appropriate permission keys (savings_view, savings_create, savings_collect,
--         savings_edit, savings_manage_schemes).
-- SAV-5: generate_account_number() now accepts $insert_id parameter.
--         create_account() inserts with temp number, then updates with real
--         account number derived from guaranteed-unique insert_id.
-- SAV-6: record_payment() now validates deposit amount <= 10x monthly_amount.
-- SAV-7: record_payment() now checks account.status === 'active' before proceeding.
-- SAV-8: update_schedule_payment() now carries forward overpayment to the next
--         pending schedule entry recursively.