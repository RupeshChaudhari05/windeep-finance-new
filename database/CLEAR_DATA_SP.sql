-- ============================================
-- WINDEEP FINANCE - CLEAR ALL DATA SP
-- Clears all data except MEMBERS and MEMBERS LOGIN
-- Preserves: members table, member_code_sequence
-- Clears: Loans, Savings, Transactions, Admin, Logs, etc.
-- ============================================

DELIMITER //

DROP PROCEDURE IF EXISTS sp_clear_all_data//

CREATE PROCEDURE sp_clear_all_data()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        -- In case of error, re-enable foreign key checks
        SET FOREIGN_KEY_CHECKS = 1;
        RESIGNAL;
    END;

    -- Start transaction
    START TRANSACTION;

    -- Disable foreign key checks for bulk operations
    SET FOREIGN_KEY_CHECKS = 0;

    -- ============================================
    -- CLEAR LOAN PAYMENT & TRANSACTION DATA
    -- (Delete in reverse order of dependencies)
    -- ============================================
    
    -- 1. Clear Loan Payments (child of loans)
    TRUNCATE TABLE loan_payments;
    
    -- 2. Clear Loan Installments (child of loans)
    TRUNCATE TABLE loan_installments;
    
    -- 3. Clear Loan Guarantors (child of loans & loan_applications)
    TRUNCATE TABLE loan_guarantors;
    
    -- 4. Clear Loans (main loan table)
    TRUNCATE TABLE loans;
    
    -- 5. Clear Loan Applications (application history)
    TRUNCATE TABLE loan_applications;
    
    -- 6. Clear Loan Products (scheme definitions)
    TRUNCATE TABLE loan_products;

    -- ============================================
    -- CLEAR SAVINGS DATA
    -- ============================================
    
    -- 7. Clear Savings Transactions
    TRUNCATE TABLE savings_transactions;
    
    -- 8. Clear Savings Schedule (monthly dues)
    TRUNCATE TABLE savings_schedule;
    
    -- 9. Clear Savings Accounts
    TRUNCATE TABLE savings_accounts;
    
    -- 10. Clear Savings Schemes
    TRUNCATE TABLE savings_schemes;

    -- ============================================
    -- CLEAR FINE & PENALTY DATA
    -- ============================================
    
    -- 11. Clear Fines
    TRUNCATE TABLE fines;
    
    -- 12. Clear Fine Rules
    TRUNCATE TABLE fine_rules;
    
    -- 13. Clear Fine Rule Code Sequence
    TRUNCATE TABLE rule_code_sequence;

    -- ============================================
    -- CLEAR BANK & LEDGER DATA
    -- ============================================
    
    -- 14. Clear Bank Transactions (imported)
    TRUNCATE TABLE bank_transactions;
    
    -- 15. Clear Bank Statement Imports
    TRUNCATE TABLE bank_statement_imports;
    
    -- 16. Clear Transaction Mappings
    TRUNCATE TABLE transaction_mappings;
    
    -- 17. Clear Bank Accounts
    TRUNCATE TABLE bank_accounts;
    
    -- 18. Clear General Ledger
    TRUNCATE TABLE general_ledger;
    
    -- 19. Clear Member Ledger
    TRUNCATE TABLE member_ledger;
    
    -- 20. Clear Chart of Accounts
    TRUNCATE TABLE chart_of_accounts;

    -- ============================================
    -- CLEAR ADMIN & SYSTEM DATA
    -- ============================================
    
    -- 21. Clear Admin Sessions
    TRUNCATE TABLE admin_sessions;
    
    -- 22. Clear Admin Users
    TRUNCATE TABLE admin_users;
    
    -- 23. Clear Financial Years
    TRUNCATE TABLE financial_years;
    
    -- 24. Clear System Settings
    TRUNCATE TABLE system_settings;

    -- ============================================
    -- CLEAR LOGS & NOTIFICATIONS
    -- ============================================
    
    -- 25. Clear Audit Logs
    TRUNCATE TABLE audit_logs;
    
    -- 26. Clear Activity Logs
    TRUNCATE TABLE activity_logs;
    
    -- 27. Clear Notifications
    TRUNCATE TABLE notifications;

    -- ============================================
    -- PRESERVE MEMBERS DATA
    -- (DO NOT TRUNCATE - Keeps all member records & login info)
    -- ============================================
    -- Members table: PRESERVED (all member records with passwords)
    -- Member Code Sequence: PRESERVED (continues member code generation)

    -- Re-enable foreign key checks
    SET FOREIGN_KEY_CHECKS = 1;

    -- Commit transaction
    COMMIT;

    -- Return success message
    SELECT 'SUCCESS' AS status, 
           'All data cleared except members and login information' AS message,
           NOW() AS cleared_at;

END //

DELIMITER ;

-- ============================================
-- EXECUTION INSTRUCTIONS
-- ============================================
-- Run the procedure:
-- CALL sp_clear_all_data();
--
-- Before running in production:
-- 1. Take a database backup
-- 2. Test in development environment first
-- 3. Notify all users that data will be cleared
-- 4. Schedule during maintenance window
-- ============================================
