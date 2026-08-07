-- ============================================
-- WINDEEP FINANCE - CLEAR DATA (ALTERNATIVE WITH DELETE)
-- Uses DELETE instead of TRUNCATE for better control
-- Preserves auto-increment sequences
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

    -- Clear in dependency order using DELETE (preserves auto-increment)
    
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
           'Data cleared using DELETE (auto-increment sequences preserved)' AS message,
           NOW() AS cleared_at;

END //

DELIMITER ;

-- ============================================
-- DIFFERENCES BETWEEN TRUNCATE vs DELETE
-- ============================================
-- TRUNCATE (CLEAR_DATA_SP.sql):
--   - Much faster for large datasets
--   - Resets auto-increment counters to 1
--   - Cannot be rolled back in some MySQL versions
--   - Better for complete data purge
--
-- DELETE (this file):
--   - Slower but has transaction support
--   - Preserves auto-increment sequences
--   - Better control and rollback capability
--   - Good for testing/safe deletions
-- ============================================
