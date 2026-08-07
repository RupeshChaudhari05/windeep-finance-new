-- ============================================
-- WINDEEP FINANCE - DATA CLEAR VERIFICATION SCRIPT
-- Verify what will be kept vs cleared before running SP
-- ============================================

-- Show what WILL BE PRESERVED
SELECT '=== TABLES THAT WILL BE PRESERVED ===' AS section;
SELECT TABLE_NAME, TABLE_ROWS 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME IN ('members', 'member_code_sequence')
ORDER BY TABLE_NAME;

-- Show record counts for tables THAT WILL BE CLEARED
SELECT '' AS section;
SELECT '=== LOAN DATA (WILL BE CLEARED) ===' AS section;
SELECT TABLE_NAME, TABLE_ROWS 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME IN (
    'loan_products', 'loan_applications', 'loan_guarantors', 
    'loans', 'loan_installments', 'loan_payments'
  )
ORDER BY TABLE_NAME;

SELECT '' AS section;
SELECT '=== SAVINGS DATA (WILL BE CLEARED) ===' AS section;
SELECT TABLE_NAME, TABLE_ROWS 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME IN (
    'savings_schemes', 'savings_accounts', 
    'savings_schedule', 'savings_transactions'
  )
ORDER BY TABLE_NAME;

SELECT '' AS section;
SELECT '=== FINES & PENALTIES (WILL BE CLEARED) ===' AS section;
SELECT TABLE_NAME, TABLE_ROWS 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME IN (
    'fine_rules', 'rule_code_sequence', 'fines'
  )
ORDER BY TABLE_NAME;

SELECT '' AS section;
SELECT '=== BANK & LEDGER DATA (WILL BE CLEARED) ===' AS section;
SELECT TABLE_NAME, TABLE_ROWS 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME IN (
    'bank_accounts', 'bank_statement_imports', 'bank_transactions',
    'transaction_mappings', 'chart_of_accounts', 
    'general_ledger', 'member_ledger'
  )
ORDER BY TABLE_NAME;

SELECT '' AS section;
SELECT '=== ADMIN & SYSTEM (WILL BE CLEARED) ===' AS section;
SELECT TABLE_NAME, TABLE_ROWS 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME IN (
    'admin_users', 'admin_sessions', 
    'financial_years', 'system_settings'
  )
ORDER BY TABLE_NAME;

SELECT '' AS section;
SELECT '=== LOGS & NOTIFICATIONS (WILL BE CLEARED) ===' AS section;
SELECT TABLE_NAME, TABLE_ROWS 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME IN (
    'audit_logs', 'activity_logs', 'notifications'
  )
ORDER BY TABLE_NAME;

-- Summary
SELECT '' AS section;
SELECT '=== SUMMARY ===' AS section;
SELECT 'MEMBERS' AS table_name, COUNT(*) AS total_records, 'PRESERVED' AS action FROM members
UNION ALL
SELECT 'TOTAL LOAN RECORDS', 
  (SELECT COALESCE(SUM(TABLE_ROWS), 0) FROM information_schema.TABLES 
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('loans', 'loan_applications', 'loan_installments', 'loan_payments', 'loan_guarantors')),
  'WILL BE CLEARED' FROM DUAL
UNION ALL
SELECT 'TOTAL SAVINGS RECORDS',
  (SELECT COALESCE(SUM(TABLE_ROWS), 0) FROM information_schema.TABLES 
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('savings_accounts', 'savings_transactions', 'savings_schedule')),
  'WILL BE CLEARED' FROM DUAL;
