-- ============================================================
-- Migration 009: Fix transaction_mappings related_id values
-- ============================================================
-- The old mapping code stored wrong related_id values:
--   - For savings: stored savings_account_id instead of savings_transaction.id
--   - For loan_payment/emi: stored loan_installment.id instead of loan_payment.id
--   - For other: stored NULL instead of member_other_transactions.id
--
-- This migration fixes related_id by cross-referencing bank_transaction_id
-- between the mapping's bank_transaction and the downstream payment records.
--
-- NOTE: This is a DATA-FIX migration, not a schema migration.
--       Run ONCE after deploying the code fix (commit e115122+).
-- ============================================================

-- -------------------------------------------------------
-- FIX 1: Savings mappings — related_id should point to savings_transactions.id
-- The old code stored savings_account_id; the new code stores savings_transaction.id.
-- Match by bank_transaction_id.
-- -------------------------------------------------------
UPDATE transaction_mappings tm
INNER JOIN savings_transactions st ON st.bank_transaction_id = tm.bank_transaction_id
AND st.is_reversed = 0
SET
    tm.related_id = st.id
WHERE
    tm.mapping_type = 'savings'
    AND tm.is_reversed = 0
    AND tm.related_id IS NOT NULL
    AND tm.related_id != st.id;

-- Also fix savings_transactions that are missing bank_transaction_id
-- by matching through mapping amount and savings_account_id
-- (This handles cases where bank_transaction_id was not set on savings_transactions)
-- Run this as a manual check — log candidates but don't auto-update

-- -------------------------------------------------------
-- FIX 2: Loan payment mappings — related_id should point to loan_payments.id
-- The old code stored installment_id; the new code stores loan_payment.id.
-- Match by bank_transaction_id.
-- -------------------------------------------------------
UPDATE transaction_mappings tm
INNER JOIN loan_payments lp ON lp.bank_transaction_id = tm.bank_transaction_id
AND lp.is_reversed = 0
SET
    tm.related_id = lp.id
WHERE
    tm.mapping_type IN ('loan_payment', 'emi')
    AND tm.is_reversed = 0
    AND tm.related_id IS NOT NULL
    AND tm.related_id != lp.id;

-- -------------------------------------------------------
-- FIX 3: Other mappings — related_id should point to member_other_transactions.id
-- The old code stored NULL; the new code stores the transaction id.
-- Match by bank_transaction_id.
-- -------------------------------------------------------
UPDATE transaction_mappings tm
INNER JOIN member_other_transactions mot ON mot.bank_transaction_id = tm.bank_transaction_id
AND mot.status != 'reversed'
SET
    tm.related_id = mot.id
WHERE
    tm.mapping_type = 'other'
    AND tm.is_reversed = 0
    AND tm.related_id IS NULL;

-- -------------------------------------------------------
-- FIX 4: Backfill bank_transaction_id on savings_transactions
-- that were created from bank mappings but didn't have it set
-- -------------------------------------------------------
UPDATE savings_transactions st
INNER JOIN transaction_mappings tm ON tm.mapping_type = 'savings'
AND tm.is_reversed = 0
AND tm.related_id = st.id
SET
    st.bank_transaction_id = tm.bank_transaction_id
WHERE
    st.bank_transaction_id IS NULL;

-- -------------------------------------------------------
-- FIX 5: Backfill bank_transaction_id on loan_payments
-- that were created from bank mappings but didn't have it set
-- -------------------------------------------------------
UPDATE loan_payments lp
INNER JOIN transaction_mappings tm ON tm.mapping_type IN ('loan_payment', 'emi')
AND tm.is_reversed = 0
AND tm.related_id = lp.id
SET
    lp.bank_transaction_id = tm.bank_transaction_id
WHERE
    lp.bank_transaction_id IS NULL;

-- -------------------------------------------------------
-- VERIFICATION: Show all mappings with their linked records
-- -------------------------------------------------------
SELECT
    tm.id as mapping_id,
    tm.mapping_type,
    tm.related_id,
    tm.amount as mapping_amount,
    tm.bank_transaction_id,
    tm.is_reversed,
    CASE
        WHEN tm.mapping_type = 'savings' THEN (
            SELECT st.amount
            FROM savings_transactions st
            WHERE
                st.id = tm.related_id
        )
        WHEN tm.mapping_type IN ('loan_payment', 'emi') THEN (
            SELECT lp.total_amount
            FROM loan_payments lp
            WHERE
                lp.id = tm.related_id
        )
        WHEN tm.mapping_type = 'other' THEN (
            SELECT mot.amount
            FROM member_other_transactions mot
            WHERE
                mot.id = tm.related_id
        )
        ELSE NULL
    END as linked_amount,
    CASE
        WHEN tm.mapping_type = 'savings'
        AND tm.related_id IS NOT NULL
        AND (
            SELECT st.id
            FROM savings_transactions st
            WHERE
                st.id = tm.related_id
        ) IS NULL THEN 'BROKEN'
        WHEN tm.mapping_type IN ('loan_payment', 'emi')
        AND tm.related_id IS NOT NULL
        AND (
            SELECT lp.id
            FROM loan_payments lp
            WHERE
                lp.id = tm.related_id
        ) IS NULL THEN 'BROKEN'
        WHEN tm.mapping_type = 'other'
        AND tm.related_id IS NOT NULL
        AND (
            SELECT mot.id
            FROM member_other_transactions mot
            WHERE
                mot.id = tm.related_id
        ) IS NULL THEN 'BROKEN'
        ELSE 'OK'
    END as link_status
FROM transaction_mappings tm
ORDER BY tm.id;