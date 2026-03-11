-- =============================================================================
-- WINDEEP FINANCE — DATABASE RESET SCRIPT
-- =============================================================================
-- PURPOSE : Wipe all transactional / member data and return the database to a
--           clean "fresh install" state while keeping all configuration data.
--
-- TABLES PRESERVED (not touched):
--   admin_users, admin_details, system_settings, chart_of_accounts,
--   savings_schemes, loan_products, fine_rules, guarantor_settings,
--   accounting_settings, bank_accounts, requests_status,
--   schema_migrations, financial_years
--
-- SEQUENCE COUNTERS RESET (row kept, counter zeroed):
--   member_code_sequence, rule_code_sequence
--
-- WARNING : This is IRREVERSIBLE. Take a full database backup before running.
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- 1. MEMBER / IDENTITY DATA
-- ---------------------------------------------------------------------------
TRUNCATE TABLE members;

TRUNCATE TABLE member_details;

TRUNCATE TABLE other_member_details;

TRUNCATE TABLE non_members;

TRUNCATE TABLE non_member_funds;

-- ---------------------------------------------------------------------------
-- 2. LOAN DATA
-- ---------------------------------------------------------------------------
TRUNCATE TABLE loan_applications;

TRUNCATE TABLE loans;

TRUNCATE TABLE loan_installments;

TRUNCATE TABLE loan_payments;

TRUNCATE TABLE loan_part_payments;

TRUNCATE TABLE loan_guarantors;

TRUNCATE TABLE loan_foreclosure_requests;

TRUNCATE TABLE loan_transactions;

TRUNCATE TABLE loan_transaction_details;

TRUNCATE TABLE disbursement_tracking;

-- ---------------------------------------------------------------------------
-- 3. SAVINGS DATA
-- ---------------------------------------------------------------------------
TRUNCATE TABLE savings_accounts;

TRUNCATE TABLE savings_transactions;

TRUNCATE TABLE savings_schedule;

TRUNCATE TABLE bonus_transactions;

TRUNCATE TABLE shares;

-- ---------------------------------------------------------------------------
-- 4. BANK / TRANSACTION DATA
-- ---------------------------------------------------------------------------
TRUNCATE TABLE bank_transactions;

TRUNCATE TABLE bank_statement_imports;

TRUNCATE TABLE bank_imports;

TRUNCATE TABLE bank_balance_history;

TRUNCATE TABLE transaction_mappings;

TRUNCATE TABLE internal_transactions;

-- ---------------------------------------------------------------------------
-- 5. FINES & FEES
-- ---------------------------------------------------------------------------
TRUNCATE TABLE fines;

-- ---------------------------------------------------------------------------
-- 6. ACCOUNTING / LEDGER
-- ---------------------------------------------------------------------------
TRUNCATE TABLE member_ledger;

TRUNCATE TABLE member_other_transactions;

TRUNCATE TABLE general_ledger;

TRUNCATE TABLE expense_transactions;

TRUNCATE TABLE expenditure;

-- ---------------------------------------------------------------------------
-- 7. NOTIFICATIONS & COMMUNICATION
-- ---------------------------------------------------------------------------
TRUNCATE TABLE notifications;

TRUNCATE TABLE email_queue;

TRUNCATE TABLE send_form;

TRUNCATE TABLE chat_box;

TRUNCATE TABLE view_requests;

-- ---------------------------------------------------------------------------
-- 8. SECURITY / SESSION DATA
-- ---------------------------------------------------------------------------
TRUNCATE TABLE ci_sessions;

TRUNCATE TABLE active_sessions;

TRUNCATE TABLE admin_sessions;

TRUNCATE TABLE security_logs;

TRUNCATE TABLE audit_logs;

TRUNCATE TABLE activity_logs;

TRUNCATE TABLE failed_login_attempts;

TRUNCATE TABLE password_history;

TRUNCATE TABLE two_factor_auth;

TRUNCATE TABLE verification_tokens;

TRUNCATE TABLE api_tokens;

-- ---------------------------------------------------------------------------
-- 9. RESET SEQUENCE COUNTERS (keeps the configuration row)
-- ---------------------------------------------------------------------------
UPDATE member_code_sequence SET current_number = 0;

UPDATE rule_code_sequence SET current_number = 0;

-- ---------------------------------------------------------------------------
-- 10. RESTORE FOREIGN KEY CHECKS
-- ---------------------------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- TABLES INTENTIONALLY NOT TOUCHED (configuration / seed data):
--
--   admin_users          — admin login accounts
--   admin_details        — admin profile details
--   system_settings      — all site configuration values
--   chart_of_accounts    — accounting COA structure
--   savings_schemes      — savings product definitions
--   loan_products        — loan product definitions
--   fine_rules           — fine calculation rules
--   guarantor_settings   — guarantor policy config
--   accounting_settings  — accounting method config
--   bank_accounts        — configured bank accounts
--   requests_status      — status enum / lookup table
--   schema_migrations    — applied migration history
--   financial_years      — defined financial year periods
-- =============================================================================

SELECT 'Database reset complete. Configuration data has been preserved.' AS status;