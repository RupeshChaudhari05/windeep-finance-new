-- =====================================================
-- WINDEEP FINANCE - DATA CLEANUP SCRIPT
-- Purpose: Remove dummy/test data safely
-- WARNING: Review before executing in production
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- STEP 1: BACKUP CURRENT DATA (RECOMMENDED)
-- =====================================================
-- Run these commands in terminal before cleanup:
-- mysqldump -u root -p windeep_finance > backup_before_cleanup_$(date +%Y%m%d).sql

-- =====================================================
-- STEP 2: IDENTIFY TEST/DUMMY DATA
-- =====================================================

-- Show members created recently (likely test data)
SELECT
    id,
    member_code,
    first_name,
    last_name,
    phone,
    created_at
FROM members
WHERE
    created_at >= DATE_SUB(NOW(), INTERVAL 7 DAYS)
ORDER BY created_at DESC;

-- Show loans without proper documentation
SELECT l.id, l.loan_number, m.member_code, l.principal_amount, l.created_at
FROM loans l
    JOIN members m ON m.id = l.member_id
WHERE
    l.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAYS);

-- =====================================================
-- STEP 3: DELETE TEST FINANCIAL DATA (CAREFUL!)
-- =====================================================

-- DELETE recent test loans (adjust date as needed)
-- DELETE FROM loan_payments WHERE loan_id IN (
--     SELECT id FROM loans WHERE created_at >= '2026-01-01'
-- );
-- DELETE FROM loan_installments WHERE loan_id IN (
--     SELECT id FROM loans WHERE created_at >= '2026-01-01'
-- );
-- DELETE FROM loan_guarantors WHERE loan_id IN (
--     SELECT id FROM loans WHERE created_at >= '2026-01-01'
-- );
-- DELETE FROM loans WHERE created_at >= '2026-01-01';

-- DELETE test loan applications
-- DELETE FROM loan_guarantors WHERE loan_application_id IN (
--     SELECT id FROM loan_applications WHERE created_at >= '2026-01-01'
-- );
-- DELETE FROM loan_applications WHERE created_at >= '2026-01-01';

-- DELETE test savings
-- DELETE FROM savings_transactions WHERE savings_account_id IN (
--     SELECT id FROM savings_accounts WHERE created_at >= '2026-01-01'
-- );
-- DELETE FROM savings_schedule WHERE savings_account_id IN (
--     SELECT id FROM savings_accounts WHERE created_at >= '2026-01-01'
-- );
-- DELETE FROM savings_accounts WHERE created_at >= '2026-01-01';

-- DELETE test fines
-- DELETE FROM fines WHERE created_at >= '2026-01-01';

-- DELETE test bank imports
-- DELETE FROM transaction_mappings WHERE bank_transaction_id IN (
--     SELECT id FROM bank_transactions WHERE import_id IN (
--         SELECT id FROM bank_statement_imports WHERE imported_at >= '2026-01-01'
--     )
-- );
-- DELETE FROM bank_transactions WHERE import_id IN (
--     SELECT id FROM bank_statement_imports WHERE imported_at >= '2026-01-01'
-- );
-- DELETE FROM bank_statement_imports WHERE imported_at >= '2026-01-01';

-- =====================================================
-- STEP 4: DELETE TEST MEMBERS (LAST)
-- =====================================================

-- Option A: Delete ALL members created after specific date
-- DELETE FROM members WHERE created_at >= '2026-01-01';

-- Option B: Delete specific test members by pattern
-- DELETE FROM members WHERE member_code LIKE 'TEST%';
-- DELETE FROM members WHERE first_name LIKE 'Test%';
-- DELETE FROM members WHERE phone LIKE '9999%';  -- Test phone numbers

-- =====================================================
-- STEP 5: CLEAN AUDIT/ACTIVITY LOGS (OPTIONAL)
-- =====================================================

-- Keep only last 3 months of logs
-- DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 3 MONTH);
-- DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);

-- =====================================================
-- STEP 6: RESET AUTO-INCREMENT SEQUENCES
-- =====================================================

-- Get current max IDs
SELECT MAX(id) as max_member_id FROM members;

SELECT MAX(id) as max_loan_id FROM loans;

SELECT MAX(id) as max_savings_id FROM savings_accounts;

-- Reset sequences (uncomment after verifying)
-- ALTER TABLE members AUTO_INCREMENT = 1;
-- ALTER TABLE loans AUTO_INCREMENT = 1;
-- ALTER TABLE loan_applications AUTO_INCREMENT = 1;
-- ALTER TABLE savings_accounts AUTO_INCREMENT = 1;
-- ALTER TABLE fines AUTO_INCREMENT = 1;

-- Reset member code sequence
-- UPDATE member_code_sequence SET current_number = 0 WHERE year = YEAR(NOW());

-- =====================================================
-- STEP 7: VERIFY DATA INTEGRITY AFTER CLEANUP
-- =====================================================

-- Check for orphan records
SELECT 'Orphan loan_installments' as issue, COUNT(*) as count
FROM loan_installments li
    LEFT JOIN loans l ON l.id = li.loan_id
WHERE
    l.id IS NULL
UNION ALL
SELECT 'Orphan loan_payments' as issue, COUNT(*) as count
FROM loan_payments lp
    LEFT JOIN loans l ON l.id = lp.loan_id
WHERE
    l.id IS NULL
UNION ALL
SELECT 'Orphan loan_guarantors' as issue, COUNT(*) as count
FROM
    loan_guarantors lg
    LEFT JOIN loan_applications la ON la.id = lg.loan_application_id
WHERE
    la.id IS NULL
UNION ALL
SELECT 'Orphan savings_transactions' as issue, COUNT(*) as count
FROM
    savings_transactions st
    LEFT JOIN savings_accounts sa ON sa.id = st.savings_account_id
WHERE
    sa.id IS NULL
UNION ALL
SELECT 'Orphan fines' as issue, COUNT(*) as count
FROM fines f
    LEFT JOIN members m ON m.id = f.member_id
WHERE
    m.id IS NULL;

-- Check ledger balance consistency
SELECT 'Member ledger imbalance' as issue, COUNT(*) as count
FROM (
        SELECT
            member_id, SUM(debit_amount) - SUM(credit_amount) as calculated_balance, (
                SELECT running_balance
                FROM member_ledger ml2
                WHERE
                    ml2.member_id = ml.member_id
                ORDER BY id DESC
                LIMIT 1
            ) as stored_balance
        FROM member_ledger ml
        GROUP BY
            member_id
        HAVING
            ABS(
                calculated_balance - stored_balance
            ) > 0.01
    ) as imbalanced;

-- Verify no negative balances
SELECT 'Negative loan outstanding' as issue, COUNT(*) as count
FROM loans
WHERE
    outstanding_principal < 0
    OR outstanding_interest < 0
UNION ALL
SELECT 'Negative fine balance' as issue, COUNT(*) as count
FROM fines
WHERE
    balance_amount < 0
UNION ALL
SELECT 'Negative savings balance' as issue, COUNT(*) as count
FROM savings_accounts
WHERE
    current_balance < 0;

-- =====================================================
-- STEP 8: CLEAN SYSTEM LOGS
-- =====================================================

-- Remove old error logs (keep only 1 month)
-- DELETE FROM system_logs WHERE log_level = 'error' AND created_at < DATE_SUB(NOW(), INTERVAL 1 MONTH);

-- =====================================================
-- STEP 9: OPTIMIZE TABLES
-- =====================================================

OPTIMIZE TABLE members;

OPTIMIZE TABLE loans;

OPTIMIZE TABLE loan_installments;

OPTIMIZE TABLE loan_payments;

OPTIMIZE TABLE savings_accounts;

OPTIMIZE TABLE savings_transactions;

OPTIMIZE TABLE fines;

OPTIMIZE TABLE bank_transactions;

OPTIMIZE TABLE general_ledger;

OPTIMIZE TABLE member_ledger;

-- =====================================================
-- STEP 10: FINAL VERIFICATION
-- =====================================================

-- Count remaining records
SELECT 'members' as table_name, COUNT(*) as record_count
FROM members
UNION ALL
SELECT 'loans' as table_name, COUNT(*) as record_count
FROM loans
UNION ALL
SELECT 'loan_applications' as table_name, COUNT(*) as record_count
FROM loan_applications
UNION ALL
SELECT 'savings_accounts' as table_name, COUNT(*) as record_count
FROM savings_accounts
UNION ALL
SELECT 'fines' as table_name, COUNT(*) as record_count
FROM fines
UNION ALL
SELECT 'bank_transactions' as table_name, COUNT(*) as record_count
FROM bank_transactions;

-- =====================================================
-- CLEANUP COMPLETE
-- =====================================================

SET FOREIGN_KEY_CHECKS = 1;

-- Log cleanup action
INSERT INTO
    activity_logs (
        user_type,
        user_id,
        activity,
        description,
        created_at
    )
VALUES (
        'admin',
        1,
        'data_cleanup',
        'Database cleanup script executed',
        NOW()
    );

SELECT 'Data cleanup completed successfully' as status;