-- ============================================
-- QUICK REFERENCE - DATA CLEARANCE COMMANDS
-- Copy-paste ready commands for quick execution
-- ============================================

-- ============================================
-- STEP 1: BACKUP YOUR DATABASE (ESSENTIAL!)
-- ============================================

-- For command line (bash/terminal):
-- mysqldump -u root -p windeep_finance > backup_$(date +%Y%m%d_%H%M%S).sql

-- Or export from GUI tools
-- - phpMyAdmin: Export > SQL
-- - HeidiSQL: File > Export Database
-- - MySQL Workbench: Server > Data Export


-- ============================================
-- STEP 2: VERIFY DATA BEFORE CLEARING
-- ============================================

-- Check record counts to see what will be cleared:
SELECT TABLE_NAME, TABLE_ROWS 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_ROWS DESC;

-- Check members will be preserved:
SELECT COUNT(*) as total_members, 
       COUNT(DISTINCT IF(password IS NOT NULL, 1, NULL)) as members_with_login
FROM members;

-- Check loan records that will be cleared:
SELECT 
  (SELECT COUNT(*) FROM loans) as total_loans,
  (SELECT COUNT(*) FROM loan_installments) as total_installments,
  (SELECT COUNT(*) FROM loan_payments) as total_payments;

-- Check savings records that will be cleared:
SELECT
  (SELECT COUNT(*) FROM savings_accounts) as total_savings_accounts,
  (SELECT COUNT(*) FROM savings_transactions) as total_transactions;


-- ============================================
-- STEP 3A: CREATE PROCEDURE (TRUNCATE VERSION)
-- Faster, resets IDs to 1
-- ============================================

DELIMITER //

DROP PROCEDURE IF EXISTS sp_clear_all_data//

CREATE PROCEDURE sp_clear_all_data()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET FOREIGN_KEY_CHECKS = 1;
        RESIGNAL;
    END;

    START TRANSACTION;
    SET FOREIGN_KEY_CHECKS = 0;

    -- Clear all transactional data (not members)
    TRUNCATE TABLE loan_payments;
    TRUNCATE TABLE loan_installments;
    TRUNCATE TABLE loan_guarantors;
    TRUNCATE TABLE loans;
    TRUNCATE TABLE loan_applications;
    TRUNCATE TABLE loan_products;
    
    TRUNCATE TABLE savings_transactions;
    TRUNCATE TABLE savings_schedule;
    TRUNCATE TABLE savings_accounts;
    TRUNCATE TABLE savings_schemes;
    
    TRUNCATE TABLE fines;
    TRUNCATE TABLE fine_rules;
    TRUNCATE TABLE rule_code_sequence;
    
    TRUNCATE TABLE bank_transactions;
    TRUNCATE TABLE bank_statement_imports;
    TRUNCATE TABLE transaction_mappings;
    TRUNCATE TABLE bank_accounts;
    TRUNCATE TABLE general_ledger;
    TRUNCATE TABLE member_ledger;
    TRUNCATE TABLE chart_of_accounts;
    
    TRUNCATE TABLE admin_sessions;
    TRUNCATE TABLE admin_users;
    TRUNCATE TABLE financial_years;
    TRUNCATE TABLE system_settings;
    
    TRUNCATE TABLE audit_logs;
    TRUNCATE TABLE activity_logs;
    TRUNCATE TABLE notifications;

    SET FOREIGN_KEY_CHECKS = 1;
    COMMIT;

    SELECT 'SUCCESS' AS status, 
           'All data cleared except members and login information' AS message,
           NOW() AS cleared_at;
END //

DELIMITER ;


-- ============================================
-- STEP 3B: OR CREATE PROCEDURE (DELETE VERSION)
-- Safer, preserves ID sequences
-- ============================================

DELIMITER //

DROP PROCEDURE IF EXISTS sp_clear_all_data_with_delete//

CREATE PROCEDURE sp_clear_all_data_with_delete()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET FOREIGN_KEY_CHECKS = 1;
        RESIGNAL;
    END;

    START TRANSACTION;
    SET FOREIGN_KEY_CHECKS = 0;

    DELETE FROM loan_payments;
    DELETE FROM loan_installments;
    DELETE FROM loan_guarantors;
    DELETE FROM loans;
    DELETE FROM loan_applications;
    DELETE FROM loan_products;
    
    DELETE FROM savings_transactions;
    DELETE FROM savings_schedule;
    DELETE FROM savings_accounts;
    DELETE FROM savings_schemes;
    
    DELETE FROM fines;
    DELETE FROM fine_rules;
    DELETE FROM rule_code_sequence;
    
    DELETE FROM bank_transactions;
    DELETE FROM bank_statement_imports;
    DELETE FROM transaction_mappings;
    DELETE FROM bank_accounts;
    DELETE FROM general_ledger;
    DELETE FROM member_ledger;
    DELETE FROM chart_of_accounts;
    
    DELETE FROM admin_sessions;
    DELETE FROM admin_users;
    DELETE FROM financial_years;
    DELETE FROM system_settings;
    
    DELETE FROM audit_logs;
    DELETE FROM activity_logs;
    DELETE FROM notifications;

    SET FOREIGN_KEY_CHECKS = 1;
    COMMIT;

    SELECT 'SUCCESS' AS status, 
           'Data cleared (auto-increment sequences preserved)' AS message,
           NOW() AS cleared_at;
END //

DELIMITER ;


-- ============================================
-- STEP 4: RUN THE CLEARANCE (CHOOSE ONE)
-- ============================================

-- OPTION A: Using TRUNCATE (faster, fresh IDs)
CALL sp_clear_all_data();

-- OPTION B: Using DELETE (safer, preserve IDs)
CALL sp_clear_all_data_with_delete();


-- ============================================
-- STEP 5: VERIFY CLEARANCE WAS SUCCESSFUL
-- ============================================

-- Check all data was cleared:
SELECT TABLE_NAME, TABLE_ROWS 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME;

-- Specifically verify:
SELECT COUNT(*) as remaining_loans FROM loans;           -- Should be 0
SELECT COUNT(*) as remaining_savings FROM savings_accounts;  -- Should be 0
SELECT COUNT(*) as remaining_members FROM members;       -- Should be > 0
SELECT COUNT(*) as members_with_password FROM members WHERE password IS NOT NULL;


-- ============================================
-- STEP 6: OPTIONAL - RESET ADMIN USER
-- (if admin_users was cleared and you need access)
-- ============================================

-- Reset with hashed password (use proper hashing in production)
INSERT INTO admin_users 
(username, email, password, full_name, role, is_active, created_at)
VALUES 
('admin', 'admin@windeep.local', SHA2('NewPassword123', 256), 'Administrator', 'super_admin', 1, NOW());


-- ============================================
-- STEP 7: OPTIONAL - RECREATE FINANCIAL YEAR
-- (to start fresh accounting period)
-- ============================================

INSERT INTO financial_years 
(year_code, start_date, end_date, is_active, created_at)
VALUES 
('2026-27', '2026-04-01', '2027-03-31', 1, NOW());


-- ============================================
-- VERIFICATION QUERIES
-- ============================================

-- Quick Status Check:
SELECT 
  'LOANS' as data_type, COUNT(*) as record_count FROM loans
UNION ALL
SELECT 'LOAN PAYMENTS', COUNT(*) FROM loan_payments
UNION ALL
SELECT 'SAVINGS ACCOUNTS', COUNT(*) FROM savings_accounts
UNION ALL
SELECT 'SAVINGS TRANSACTIONS', COUNT(*) FROM savings_transactions
UNION ALL
SELECT 'FINES', COUNT(*) FROM fines
UNION ALL
SELECT 'MEMBERS (PRESERVED)', COUNT(*) FROM members
UNION ALL
SELECT 'MEMBERS WITH PASSWORD', COUNT(*) FROM members WHERE password IS NOT NULL;

-- Detailed Member Count:
SELECT 
  COUNT(*) as total_members,
  COUNT(CASE WHEN status = 'active' THEN 1 END) as active_members,
  COUNT(CASE WHEN password IS NOT NULL THEN 1 END) as can_login,
  COUNT(CASE WHEN photo IS NOT NULL THEN 1 END) as with_photo
FROM members;


-- ============================================
-- COMMAND LINE EXECUTION
-- ============================================

-- One-liner to execute procedure:
-- mysql -u root -p database_name -e "CALL sp_clear_all_data();"

-- Or with all steps:
-- mysql -u root -p database_name << EOF
-- CALL sp_clear_all_data();
-- SELECT COUNT(*) as members FROM members;
-- SELECT COUNT(*) as loans FROM loans;
-- EOF


-- ============================================
-- TROUBLESHOOTING
-- ============================================

-- If you get foreign key error:
SET FOREIGN_KEY_CHECKS = 0;
-- Then run your TRUNCATE or DELETE statements
SET FOREIGN_KEY_CHECKS = 1;

-- If procedure won't drop:
DROP PROCEDURE IF EXISTS sp_clear_all_data;

-- Check if procedure exists:
SHOW PROCEDURE STATUS WHERE db = DATABASE();

-- View procedure code:
SHOW CREATE PROCEDURE sp_clear_all_data;
