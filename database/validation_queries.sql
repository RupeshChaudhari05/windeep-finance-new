-- =====================================================
-- WINDEEP FINANCE - VALIDATION & RECONCILIATION QUERIES
-- Purpose: Verify financial accuracy and data integrity
-- Usage: Run these queries regularly (daily/weekly)
-- =====================================================

-- =====================================================
-- 1. EMI CALCULATION VERIFICATION
-- =====================================================

-- Check if EMI schedule matches loan totals
SELECT
    l.loan_number,
    l.principal_amount,
    l.total_interest,
    l.total_payable,
    (
        SELECT SUM(principal_amount)
        FROM loan_installments
        WHERE
            loan_id = l.id
    ) as calculated_principal,
    (
        SELECT SUM(interest_amount)
        FROM loan_installments
        WHERE
            loan_id = l.id
    ) as calculated_interest,
    (
        SELECT SUM(emi_amount)
        FROM loan_installments
        WHERE
            loan_id = l.id
    ) as calculated_total,
    ABS(
        l.principal_amount - (
            SELECT SUM(principal_amount)
            FROM loan_installments
            WHERE
                loan_id = l.id
        )
    ) as principal_diff,
    ABS(
        l.total_interest - (
            SELECT SUM(interest_amount)
            FROM loan_installments
            WHERE
                loan_id = l.id
        )
    ) as interest_diff,
    CASE
        WHEN ABS(
            l.principal_amount - (
                SELECT SUM(principal_amount)
                FROM loan_installments
                WHERE
                    loan_id = l.id
            )
        ) > 0.10 THEN '❌ MISMATCH'
        ELSE '✅ OK'
    END as status
FROM loans l
WHERE
    l.status = 'active'
ORDER BY principal_diff DESC;

-- =====================================================
-- 2. LOAN OUTSTANDING BALANCE VERIFICATION
-- =====================================================

-- Compare loans table outstanding vs calculated from installments
SELECT
    l.loan_number,
    m.member_code,
    l.outstanding_principal as stored_outstanding,
    (
        SELECT COALESCE(
                SUM(
                    principal_amount - principal_paid
                ), 0
            )
        FROM loan_installments
        WHERE
            loan_id = l.id
            AND status != 'paid'
    ) as actual_outstanding,
    l.outstanding_interest as stored_interest,
    (
        SELECT COALESCE(
                SUM(
                    interest_amount - interest_paid
                ), 0
            )
        FROM loan_installments
        WHERE
            loan_id = l.id
            AND status != 'paid'
    ) as actual_interest,
    ABS(
        l.outstanding_principal - (
            SELECT COALESCE(
                    SUM(
                        principal_amount - principal_paid
                    ), 0
                )
            FROM loan_installments
            WHERE
                loan_id = l.id
                AND status != 'paid'
        )
    ) as principal_mismatch,
    CASE
        WHEN ABS(
            l.outstanding_principal - (
                SELECT COALESCE(
                        SUM(
                            principal_amount - principal_paid
                        ), 0
                    )
                FROM loan_installments
                WHERE
                    loan_id = l.id
                    AND status != 'paid'
            )
        ) > 1.00 THEN '❌ CRITICAL'
        WHEN ABS(
            l.outstanding_principal - (
                SELECT COALESCE(
                        SUM(
                            principal_amount - principal_paid
                        ), 0
                    )
                FROM loan_installments
                WHERE
                    loan_id = l.id
                    AND status != 'paid'
            )
        ) > 0.10 THEN '⚠️ WARNING'
        ELSE '✅ OK'
    END as status
FROM loans l
    JOIN members m ON m.id = l.member_id
WHERE
    l.status = 'active'
HAVING
    principal_mismatch > 0.01
ORDER BY principal_mismatch DESC;

-- =====================================================
-- 3. PAYMENT ALLOCATION VERIFICATION
-- =====================================================

-- Check if payment components match installment payments
SELECT
    lp.payment_code,
    lp.loan_id,
    l.loan_number,
    lp.total_amount,
    lp.principal_component,
    lp.interest_component,
    lp.fine_component,
    lp.excess_amount,
    (
        lp.principal_component + lp.interest_component + lp.fine_component + lp.excess_amount
    ) as calculated_total,
    ABS(
        lp.total_amount - (
            lp.principal_component + lp.interest_component + lp.fine_component + lp.excess_amount
        )
    ) as allocation_diff,
    CASE
        WHEN ABS(
            lp.total_amount - (
                lp.principal_component + lp.interest_component + lp.fine_component + lp.excess_amount
            )
        ) > 0.01 THEN '❌ MISMATCH'
        ELSE '✅ OK'
    END as status
FROM loan_payments lp
    JOIN loans l ON l.id = lp.loan_id
WHERE
    lp.is_reversed = 0
HAVING
    allocation_diff > 0.01
ORDER BY allocation_diff DESC;

-- =====================================================
-- 4. FINE CALCULATION VERIFICATION
-- =====================================================

-- Verify fine amounts are within rule limits
SELECT
    f.fine_code,
    m.member_code,
    f.fine_type,
    f.days_late,
    f.fine_amount,
    fr.max_fine_amount as max_allowed,
    fr.per_day_amount,
    CASE fr.calculation_type
        WHEN 'per_day' THEN f.days_late * fr.per_day_amount
        WHEN 'fixed' THEN fr.fixed_amount
        ELSE f.fine_amount
    END as expected_amount,
    ABS(
        f.fine_amount - CASE fr.calculation_type
            WHEN 'per_day' THEN LEAST(
                f.days_late * fr.per_day_amount,
                fr.max_fine_amount
            )
            WHEN 'fixed' THEN fr.fixed_amount
            ELSE f.fine_amount
        END
    ) as amount_diff,
    CASE
        WHEN fr.max_fine_amount > 0
        AND f.fine_amount > fr.max_fine_amount THEN '❌ EXCEEDS MAX'
        WHEN ABS(
            f.fine_amount - CASE fr.calculation_type
                WHEN 'per_day' THEN LEAST(
                    f.days_late * fr.per_day_amount,
                    fr.max_fine_amount
                )
                WHEN 'fixed' THEN fr.fixed_amount
                ELSE f.fine_amount
            END
        ) > 0.01 THEN '⚠️ CALCULATION ERROR'
        ELSE '✅ OK'
    END as status
FROM
    fines f
    JOIN members m ON m.id = f.member_id
    LEFT JOIN fine_rules fr ON fr.id = f.fine_rule_id
WHERE
    f.status IN ('pending', 'partial')
HAVING
    status != '✅ OK'
ORDER BY f.fine_date DESC;

-- Check for duplicate fines (same installment, same date)
SELECT
    f1.related_type,
    f1.related_id,
    f1.fine_date,
    COUNT(*) as duplicate_count,
    GROUP_CONCAT(f1.fine_code) as fine_codes,
    SUM(f1.fine_amount) as total_fined
FROM fines f1
WHERE
    f1.fine_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY
    f1.related_type,
    f1.related_id,
    f1.fine_date
HAVING
    duplicate_count > 1
ORDER BY duplicate_count DESC;

-- =====================================================
-- 5. FINE BALANCE VERIFICATION
-- =====================================================

SELECT
    f.fine_code,
    m.member_code,
    f.fine_amount,
    f.paid_amount,
    f.waived_amount,
    f.balance_amount as stored_balance,
    (
        f.fine_amount - f.paid_amount - f.waived_amount
    ) as calculated_balance,
    ABS(
        f.balance_amount - (
            f.fine_amount - f.paid_amount - f.waived_amount
        )
    ) as balance_diff,
    CASE
        WHEN ABS(
            f.balance_amount - (
                f.fine_amount - f.paid_amount - f.waived_amount
            )
        ) > 0.01 THEN '❌ MISMATCH'
        WHEN f.balance_amount < 0 THEN '❌ NEGATIVE'
        ELSE '✅ OK'
    END as status
FROM fines f
    JOIN members m ON m.id = f.member_id
HAVING
    status != '✅ OK'
ORDER BY balance_diff DESC;

-- =====================================================
-- 6. MEMBER LEDGER RECONCILIATION
-- =====================================================

-- Verify member ledger running balance
SELECT
    m.member_code,
    m.first_name,
    m.last_name,
    (
        SELECT COALESCE(SUM(debit_amount), 0) - COALESCE(SUM(credit_amount), 0)
        FROM member_ledger ml
        WHERE
            ml.member_id = m.id
    ) as calculated_balance,
    (
        SELECT running_balance
        FROM member_ledger ml
        WHERE
            ml.member_id = m.id
        ORDER BY id DESC
        LIMIT 1
    ) as stored_balance,
    ABS(
        (
            SELECT COALESCE(SUM(debit_amount), 0) - COALESCE(SUM(credit_amount), 0)
            FROM member_ledger ml
            WHERE
                ml.member_id = m.id
        ) - COALESCE(
            (
                SELECT running_balance
                FROM member_ledger ml
                WHERE
                    ml.member_id = m.id
                ORDER BY id DESC
                LIMIT 1
            ),
            0
        )
    ) as balance_diff,
    CASE
        WHEN ABS(
            (
                SELECT COALESCE(SUM(debit_amount), 0) - COALESCE(SUM(credit_amount), 0)
                FROM member_ledger ml
                WHERE
                    ml.member_id = m.id
            ) - COALESCE(
                (
                    SELECT running_balance
                    FROM member_ledger ml
                    WHERE
                        ml.member_id = m.id
                    ORDER BY id DESC
                    LIMIT 1
                ),
                0
            )
        ) > 1.00 THEN '❌ CRITICAL'
        ELSE '✅ OK'
    END as status
FROM members m
WHERE
    m.deleted_at IS NULL
HAVING
    balance_diff > 0.01
ORDER BY balance_diff DESC;

-- =====================================================
-- 7. BANK TRANSACTION RECONCILIATION
-- =====================================================

-- Check for unmapped bank transactions (aging report)
SELECT
    DATE(bt.transaction_date) as transaction_date,
    COUNT(*) as unmapped_count,
    SUM(
        CASE
            WHEN bt.transaction_type = 'credit' THEN bt.amount
            ELSE 0
        END
    ) as total_credits,
    SUM(
        CASE
            WHEN bt.transaction_type = 'debit' THEN bt.amount
            ELSE 0
        END
    ) as total_debits,
    GROUP_CONCAT(
        CONCAT(bt.id, ':', bt.description) SEPARATOR ' | '
    ) as sample_transactions
FROM bank_transactions bt
WHERE
    bt.mapping_status = 'unmapped'
    AND bt.transaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY
    DATE(bt.transaction_date)
ORDER BY transaction_date DESC;

-- Check for duplicate UTR numbers
SELECT
    bt.utr_number,
    COUNT(*) as occurrence_count,
    SUM(bt.amount) as total_amount,
    GROUP_CONCAT(
        CONCAT(bt.id, ':', bt.description) SEPARATOR ' | '
    ) as transactions
FROM bank_transactions bt
WHERE
    bt.utr_number IS NOT NULL
    AND bt.utr_number != ''
GROUP BY
    bt.utr_number
HAVING
    occurrence_count > 1
ORDER BY occurrence_count DESC;

-- =====================================================
-- 8. GENERAL LEDGER BALANCE VERIFICATION
-- =====================================================

-- Verify debit = credit for all entries
SELECT
    gl.voucher_number,
    gl.voucher_date,
    gl.debit_amount,
    gl.credit_amount,
    ABS(
        gl.debit_amount - gl.credit_amount
    ) as imbalance,
    CASE
        WHEN ABS(
            gl.debit_amount - gl.credit_amount
        ) > 0.01 THEN '❌ IMBALANCED'
        ELSE '✅ OK'
    END as status
FROM general_ledger gl
WHERE
    ABS(
        gl.debit_amount - gl.credit_amount
    ) > 0.01
ORDER BY imbalance DESC, gl.voucher_date DESC;

-- =====================================================
-- 9. TRIAL BALANCE (OVERALL SYSTEM INTEGRITY)
-- =====================================================

SELECT
    coa.account_code,
    coa.account_name,
    coa.account_type,
    COALESCE(
        SUM(
            CASE
                WHEN gl.debit_account_id = coa.id THEN gl.debit_amount
                ELSE 0
            END
        ),
        0
    ) as total_debit,
    COALESCE(
        SUM(
            CASE
                WHEN gl.credit_account_id = coa.id THEN gl.credit_amount
                ELSE 0
            END
        ),
        0
    ) as total_credit,
    COALESCE(
        SUM(
            CASE
                WHEN gl.debit_account_id = coa.id THEN gl.debit_amount
                ELSE 0
            END
        ),
        0
    ) - COALESCE(
        SUM(
            CASE
                WHEN gl.credit_account_id = coa.id THEN gl.credit_amount
                ELSE 0
            END
        ),
        0
    ) as net_balance
FROM
    chart_of_accounts coa
    LEFT JOIN general_ledger gl ON gl.debit_account_id = coa.id
    OR gl.credit_account_id = coa.id
WHERE
    coa.is_active = 1
GROUP BY
    coa.id,
    coa.account_code,
    coa.account_name,
    coa.account_type
ORDER BY coa.account_code;

-- Overall debit/credit balance check
SELECT
    SUM(debit_amount) as total_debits,
    SUM(credit_amount) as total_credits,
    ABS(
        SUM(debit_amount) - SUM(credit_amount)
    ) as imbalance,
    CASE
        WHEN ABS(
            SUM(debit_amount) - SUM(credit_amount)
        ) > 1.00 THEN '❌ CRITICAL IMBALANCE'
        WHEN ABS(
            SUM(debit_amount) - SUM(credit_amount)
        ) > 0.10 THEN '⚠️ MINOR IMBALANCE'
        ELSE '✅ BALANCED'
    END as status
FROM general_ledger;

-- =====================================================
-- 10. DATA INTEGRITY CHECKS
-- =====================================================

-- Check for negative balances (should not exist)
SELECT 'Negative Loan Outstanding' as issue, COUNT(*) as count
FROM loans
WHERE
    outstanding_principal < 0
    OR outstanding_interest < 0
UNION ALL
SELECT 'Negative Fine Balance' as issue, COUNT(*) as count
FROM fines
WHERE
    balance_amount < 0
UNION ALL
SELECT 'Negative Savings Balance' as issue, COUNT(*) as count
FROM savings_accounts
WHERE
    current_balance < 0
UNION ALL
SELECT 'Negative Payment Components' as issue, COUNT(*) as count
FROM loan_payments
WHERE
    principal_component < 0
    OR interest_component < 0
    OR fine_component < 0
UNION ALL
SELECT 'Negative Installment Payments' as issue, COUNT(*) as count
FROM loan_installments
WHERE
    principal_paid < 0
    OR interest_paid < 0
    OR fine_paid < 0;

-- Check for orphan records
SELECT 'Orphan Loan Installments' as issue, COUNT(*) as count
FROM loan_installments li
    LEFT JOIN loans l ON l.id = li.loan_id
WHERE
    l.id IS NULL
UNION ALL
SELECT 'Orphan Loan Payments' as issue, COUNT(*) as count
FROM loan_payments lp
    LEFT JOIN loans l ON l.id = lp.loan_id
WHERE
    l.id IS NULL
UNION ALL
SELECT 'Orphan Fines' as issue, COUNT(*) as count
FROM fines f
    LEFT JOIN members m ON m.id = f.member_id
WHERE
    m.id IS NULL
UNION ALL
SELECT 'Orphan Guarantors' as issue, COUNT(*) as count
FROM
    loan_guarantors lg
    LEFT JOIN loan_applications la ON la.id = lg.loan_application_id
WHERE
    la.id IS NULL;

-- =====================================================
-- 11. SKIP EMI VERIFICATION
-- =====================================================

-- Check if skip EMI adjustments are correct
SELECT
    l.loan_number,
    li.installment_number,
    li.is_skipped,
    li.status,
    li.principal_amount,
    li.interest_amount,
    li.emi_amount,
    CASE
        WHEN li.is_skipped = 1
        AND li.status != 'skipped' THEN '⚠️ Status mismatch'
        WHEN li.is_skipped = 1
        AND (
            li.principal_paid > 0
            OR li.interest_paid > 0
        ) THEN '❌ Payment on skipped EMI'
        ELSE '✅ OK'
    END as status
FROM loan_installments li
    JOIN loans l ON l.id = li.loan_id
WHERE
    li.is_skipped = 1
HAVING
    status != '✅ OK';

-- =====================================================
-- 12. OVERDUE ANALYSIS
-- =====================================================

-- Loans with overdue installments (NPA risk)
SELECT
    l.loan_number,
    m.member_code,
    m.first_name,
    m.last_name,
    m.phone,
    COUNT(
        CASE
            WHEN li.status IN ('pending', 'overdue')
            AND li.due_date < NOW() THEN 1
        END
    ) as overdue_installments,
    SUM(
        CASE
            WHEN li.status IN ('pending', 'overdue')
            AND li.due_date < NOW() THEN li.emi_amount - li.total_paid
            ELSE 0
        END
    ) as overdue_amount,
    MIN(
        CASE
            WHEN li.status IN ('pending', 'overdue')
            AND li.due_date < NOW() THEN li.due_date
        END
    ) as oldest_overdue_date,
    DATEDIFF(
        NOW(),
        MIN(
            CASE
                WHEN li.status IN ('pending', 'overdue')
                AND li.due_date < NOW() THEN li.due_date
            END
        )
    ) as days_overdue,
    CASE
        WHEN DATEDIFF(
            NOW(),
            MIN(
                CASE
                    WHEN li.status IN ('pending', 'overdue')
                    AND li.due_date < NOW() THEN li.due_date
                END
            )
        ) > 90 THEN '🚨 NPA (>90 days)'
        WHEN DATEDIFF(
            NOW(),
            MIN(
                CASE
                    WHEN li.status IN ('pending', 'overdue')
                    AND li.due_date < NOW() THEN li.due_date
                END
            )
        ) > 30 THEN '⚠️ HIGH RISK (30-90 days)'
        WHEN DATEDIFF(
            NOW(),
            MIN(
                CASE
                    WHEN li.status IN ('pending', 'overdue')
                    AND li.due_date < NOW() THEN li.due_date
                END
            )
        ) > 7 THEN '⚠️ MODERATE RISK (7-30 days)'
        ELSE '⚠️ EARLY OVERDUE (<7 days)'
    END as risk_category
FROM
    loans l
    JOIN members m ON m.id = l.member_id
    JOIN loan_installments li ON li.loan_id = l.id
WHERE
    l.status = 'active'
GROUP BY
    l.id
HAVING
    overdue_installments > 0
ORDER BY days_overdue DESC, overdue_amount DESC;

-- =====================================================
-- 13. COLLECTION EFFICIENCY
-- =====================================================

-- Monthly collection vs due report
SELECT
    DATE_FORMAT(li.due_date, '%Y-%m') as month,
    COUNT(*) as total_installments,
    SUM(li.emi_amount) as total_due,
    SUM(li.total_paid) as total_collected,
    SUM(li.emi_amount) - SUM(li.total_paid) as outstanding,
    ROUND(
        (
            SUM(li.total_paid) / SUM(li.emi_amount)
        ) * 100,
        2
    ) as collection_rate
FROM loan_installments li
WHERE
    li.due_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    AND li.due_date <= NOW()
GROUP BY
    DATE_FORMAT(li.due_date, '%Y-%m')
ORDER BY month DESC;

-- =====================================================
-- 14. MEMBER CREDIT SCORE (SIMPLIFIED)
-- =====================================================

SELECT
    m.member_code,
    m.first_name,
    m.last_name,
    COUNT(DISTINCT l.id) as total_loans,
    COUNT(
        DISTINCT CASE
            WHEN l.status = 'closed' THEN l.id
        END
    ) as closed_loans,
    COUNT(
        DISTINCT CASE
            WHEN li.is_late = 1 THEN li.id
        END
    ) as late_payments,
    COUNT(
        DISTINCT CASE
            WHEN li.status = 'paid'
            AND li.is_late = 0 THEN li.id
        END
    ) as on_time_payments,
    COALESCE(
        SUM(
            CASE
                WHEN f.status IN ('pending', 'partial') THEN f.balance_amount
                ELSE 0
            END
        ),
        0
    ) as pending_fines,
    CASE
        WHEN COUNT(
            DISTINCT CASE
                WHEN li.is_late = 1 THEN li.id
            END
        ) = 0 THEN '⭐⭐⭐⭐⭐ EXCELLENT'
        WHEN COUNT(
            DISTINCT CASE
                WHEN li.is_late = 1 THEN li.id
            END
        ) <= 2 THEN '⭐⭐⭐⭐ GOOD'
        WHEN COUNT(
            DISTINCT CASE
                WHEN li.is_late = 1 THEN li.id
            END
        ) <= 5 THEN '⭐⭐⭐ AVERAGE'
        WHEN COUNT(
            DISTINCT CASE
                WHEN li.is_late = 1 THEN li.id
            END
        ) <= 10 THEN '⭐⭐ POOR'
        ELSE '⭐ BAD'
    END as credit_rating
FROM
    members m
    LEFT JOIN loans l ON l.member_id = m.id
    LEFT JOIN loan_installments li ON li.loan_id = l.id
    LEFT JOIN fines f ON f.member_id = m.id
WHERE
    m.deleted_at IS NULL
GROUP BY
    m.id
ORDER BY credit_rating, late_payments DESC;

-- =====================================================
-- 15. AUDIT TRAIL VERIFICATION
-- =====================================================

-- Check for missing audit entries for critical operations
SELECT 'Loan Disbursements Without Audit' as issue, COUNT(*) as count
FROM
    loans l
    LEFT JOIN audit_logs al ON al.table_name = 'loans'
    AND al.record_id = l.id
    AND al.action = 'create'
WHERE
    l.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    AND al.id IS NULL
UNION ALL
SELECT 'Loan Payments Without Audit' as issue, COUNT(*) as count
FROM
    loan_payments lp
    LEFT JOIN audit_logs al ON al.table_name = 'loan_payments'
    AND al.record_id = lp.id
    AND al.action = 'create'
WHERE
    lp.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    AND al.id IS NULL;

-- =====================================================
-- SUMMARY DASHBOARD
-- =====================================================

SELECT 'Total Active Loans' as metric, COUNT(*) as value, CONCAT(
        '₹', FORMAT(SUM(outstanding_principal), 2)
    ) as amount
FROM loans
WHERE
    status = 'active'
UNION ALL
SELECT 'Total Overdue Loans' as metric, COUNT(DISTINCT li.loan_id) as value, CONCAT(
        '₹', FORMAT(
            SUM(li.emi_amount - li.total_paid), 2
        )
    ) as amount
FROM loan_installments li
WHERE
    li.status IN ('pending', 'overdue')
    AND li.due_date < NOW()
UNION ALL
SELECT 'Total Pending Fines' as metric, COUNT(*) as value, CONCAT(
        '₹', FORMAT(SUM(balance_amount), 2)
    ) as amount
FROM fines
WHERE
    status IN ('pending', 'partial')
UNION ALL
SELECT 'Unmapped Bank Transactions' as metric, COUNT(*) as value, CONCAT('₹', FORMAT(SUM(amount), 2)) as amount
FROM bank_transactions
WHERE
    mapping_status = 'unmapped'
UNION ALL
SELECT 'Total Active Members' as metric, COUNT(*) as value, NULL as amount
FROM members
WHERE
    status = 'active'
    AND deleted_at IS NULL;

-- =====================================================
-- END OF VALIDATION QUERIES
-- =====================================================

SELECT NOW() as validation_run_time, 'All validation queries executed' as status;