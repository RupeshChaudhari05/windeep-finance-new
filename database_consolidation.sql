-- =====================================================
-- DATABASE CONSOLIDATION - AUTOMATED SCRIPT
-- =====================================================
-- This script safely consolidates two databases:
--   windeep_finance (41 tables) -> OLD
--   windeep_finance_new (50 tables) -> PRIMARY
-- =====================================================

-- STEP 1: Drop old database (after backup)
-- Uncomment to execute:
-- DROP DATABASE IF EXISTS `windeep_finance`;

-- STEP 2: Rename windeep_finance_new to windeep_finance (optional)
-- This is safer than DROP/CREATE
-- Uncomment to execute:
-- RENAME DATABASE `windeep_finance_new` TO `windeep_finance`;

-- Note: MySQL doesn't support RENAME DATABASE directly
-- Use this method instead:

-- Method: Create new DB and move all tables
/*
CREATE DATABASE IF NOT EXISTS `windeep_finance`;

-- Move all tables from windeep_finance_new to windeep_finance
RENAME TABLE `windeep_finance_new`.`active_sessions` TO `windeep_finance`.`active_sessions`;
RENAME TABLE `windeep_finance_new`.`activity_logs` TO `windeep_finance`.`activity_logs`;
RENAME TABLE `windeep_finance_new`.`admin_details` TO `windeep_finance`.`admin_details`;
RENAME TABLE `windeep_finance_new`.`admin_sessions` TO `windeep_finance`.`admin_sessions`;
RENAME TABLE `windeep_finance_new`.`admin_users` TO `windeep_finance`.`admin_users`;
RENAME TABLE `windeep_finance_new`.`api_tokens` TO `windeep_finance`.`api_tokens`;
RENAME TABLE `windeep_finance_new`.`audit_logs` TO `windeep_finance`.`audit_logs`;
RENAME TABLE `windeep_finance_new`.`bank_accounts` TO `windeep_finance`.`bank_accounts`;
RENAME TABLE `windeep_finance_new`.`bank_balance_history` TO `windeep_finance`.`bank_balance_history`;
RENAME TABLE `windeep_finance_new`.`bank_statement_imports` TO `windeep_finance`.`bank_statement_imports`;
RENAME TABLE `windeep_finance_new`.`bank_transactions` TO `windeep_finance`.`bank_transactions`;
RENAME TABLE `windeep_finance_new`.`chart_of_accounts` TO `windeep_finance`.`chart_of_accounts`;
RENAME TABLE `windeep_finance_new`.`chat_box` TO `windeep_finance`.`chat_box`;
RENAME TABLE `windeep_finance_new`.`ci_sessions` TO `windeep_finance`.`ci_sessions`;
RENAME TABLE `windeep_finance_new`.`expenditure` TO `windeep_finance`.`expenditure`;
RENAME TABLE `windeep_finance_new`.`failed_login_attempts` TO `windeep_finance`.`failed_login_attempts`;
RENAME TABLE `windeep_finance_new`.`financial_years` TO `windeep_finance`.`financial_years`;
RENAME TABLE `windeep_finance_new`.`fine_rules` TO `windeep_finance`.`fine_rules`;
RENAME TABLE `windeep_finance_new`.`fines` TO `windeep_finance`.`fines`;
RENAME TABLE `windeep_finance_new`.`general_ledger` TO `windeep_finance`.`general_ledger`;
RENAME TABLE `windeep_finance_new`.`loan_applications` TO `windeep_finance`.`loan_applications`;
RENAME TABLE `windeep_finance_new`.`loan_foreclosure_requests` TO `windeep_finance`.`loan_foreclosure_requests`;
RENAME TABLE `windeep_finance_new`.`loan_guarantors` TO `windeep_finance`.`loan_guarantors`;
RENAME TABLE `windeep_finance_new`.`loan_installments` TO `windeep_finance`.`loan_installments`;
RENAME TABLE `windeep_finance_new`.`loan_payments` TO `windeep_finance`.`loan_payments`;
RENAME TABLE `windeep_finance_new`.`loan_products` TO `windeep_finance`.`loan_products`;
RENAME TABLE `windeep_finance_new`.`loan_transaction_details` TO `windeep_finance`.`loan_transaction_details`;
RENAME TABLE `windeep_finance_new`.`loan_transactions` TO `windeep_finance`.`loan_transactions`;
RENAME TABLE `windeep_finance_new`.`loans` TO `windeep_finance`.`loans`;
RENAME TABLE `windeep_finance_new`.`member_code_sequence` TO `windeep_finance`.`member_code_sequence`;
RENAME TABLE `windeep_finance_new`.`member_details` TO `windeep_finance`.`member_details`;
RENAME TABLE `windeep_finance_new`.`member_ledger` TO `windeep_finance`.`member_ledger`;
RENAME TABLE `windeep_finance_new`.`members` TO `windeep_finance`.`members`;
RENAME TABLE `windeep_finance_new`.`notifications` TO `windeep_finance`.`notifications`;
RENAME TABLE `windeep_finance_new`.`other_member_details` TO `windeep_finance`.`other_member_details`;
RENAME TABLE `windeep_finance_new`.`password_history` TO `windeep_finance`.`password_history`;
RENAME TABLE `windeep_finance_new`.`requests_status` TO `windeep_finance`.`requests_status`;
RENAME TABLE `windeep_finance_new`.`rule_code_sequence` TO `windeep_finance`.`rule_code_sequence`;
RENAME TABLE `windeep_finance_new`.`savings_accounts` TO `windeep_finance`.`savings_accounts`;
RENAME TABLE `windeep_finance_new`.`savings_schedule` TO `windeep_finance`.`savings_schedule`;
RENAME TABLE `windeep_finance_new`.`savings_schemes` TO `windeep_finance`.`savings_schemes`;
RENAME TABLE `windeep_finance_new`.`savings_transactions` TO `windeep_finance`.`savings_transactions`;
RENAME TABLE `windeep_finance_new`.`schema_migrations` TO `windeep_finance`.`schema_migrations`;
RENAME TABLE `windeep_finance_new`.`security_logs` TO `windeep_finance`.`security_logs`;
RENAME TABLE `windeep_finance_new`.`send_form` TO `windeep_finance`.`send_form`;
RENAME TABLE `windeep_finance_new`.`shares` TO `windeep_finance`.`shares`;
RENAME TABLE `windeep_finance_new`.`system_settings` TO `windeep_finance`.`system_settings`;
RENAME TABLE `windeep_finance_new`.`transaction_mappings` TO `windeep_finance`.`transaction_mappings`;
RENAME TABLE `windeep_finance_new`.`two_factor_auth` TO `windeep_finance`.`two_factor_auth`;
RENAME TABLE `windeep_finance_new`.`view_requests` TO `windeep_finance`.`view_requests`;

-- Drop empty old database
DROP DATABASE IF EXISTS `windeep_finance_new`;
*/

-- After running this script:
-- 1. Update .env: DB_NAME=windeep_finance
-- 2. Test application thoroughly
-- 3. Delete backup files if everything works