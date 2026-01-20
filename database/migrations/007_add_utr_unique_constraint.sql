-- Migration 007: Add UTR Uniqueness Constraint
-- Bug #10 Fix: Prevent duplicate UTR numbers
-- Date: January 6, 2026

-- Step 1: Check for existing duplicate UTR numbers
SELECT
    utr_number,
    COUNT(*) as count,
    GROUP_CONCAT(id) as transaction_ids
FROM bank_transactions
WHERE
    utr_number IS NOT NULL
    AND utr_number != ''
GROUP BY
    utr_number
HAVING
    count > 1;

-- Step 2: If duplicates exist, update them to make unique
-- (Manual intervention required - review and decide which transactions are valid)

-- Step 3: Add unique constraint
ALTER TABLE bank_transactions
ADD UNIQUE INDEX idx_utr_unique (utr_number);

-- Step 4: Verify constraint was added
SHOW INDEX FROM bank_transactions WHERE Key_name = 'idx_utr_unique';

-- Step 5: Add validation at application level as well
-- (This is done in Bank_model.php - see import_statement method)

-- Rollback script (if needed)
-- ALTER TABLE bank_transactions DROP INDEX idx_utr_unique;