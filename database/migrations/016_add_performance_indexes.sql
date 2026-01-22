-- ============================================
-- Migration 016: Add Performance Indexes
-- Windeep Finance - Production Optimization
-- ============================================

-- ============================================
-- MEMBERS TABLE INDEXES
-- ============================================

-- Index for member search by name
CREATE INDEX IF NOT EXISTS idx_members_name ON members (first_name, last_name);

-- Index for member code lookup
CREATE INDEX IF NOT EXISTS idx_members_code ON members (member_code);

-- Index for phone search
CREATE INDEX IF NOT EXISTS idx_members_phone ON members (phone);

-- Index for email search
CREATE INDEX IF NOT EXISTS idx_members_email ON members (email);

-- Index for status filter
CREATE INDEX IF NOT EXISTS idx_members_status ON members (status);

-- Composite index for active member search
CREATE INDEX IF NOT EXISTS idx_members_active_search ON members (status, first_name, last_name);

-- ============================================
-- LOANS TABLE INDEXES
-- ============================================

-- Index for member loans
CREATE INDEX IF NOT EXISTS idx_loans_member ON loans (member_id);

-- Index for loan status
CREATE INDEX IF NOT EXISTS idx_loans_status ON loans (status);

-- Index for loan product
CREATE INDEX IF NOT EXISTS idx_loans_product ON loans (product_id);

-- Composite index for member active loans
CREATE INDEX IF NOT EXISTS idx_loans_member_status ON loans (member_id, status);

-- Index for disbursement date (reports)
CREATE INDEX IF NOT EXISTS idx_loans_disbursement_date ON loans (disbursement_date);

-- Index for maturity date
CREATE INDEX IF NOT EXISTS idx_loans_maturity_date ON loans (maturity_date);

-- ============================================
-- LOAN INSTALLMENTS TABLE INDEXES
-- ============================================

-- Index for loan installments
CREATE INDEX IF NOT EXISTS idx_installments_loan ON loan_installments (loan_id);

-- Index for due date (reminders/reports)
CREATE INDEX IF NOT EXISTS idx_installments_due_date ON loan_installments (due_date);

-- Index for status
CREATE INDEX IF NOT EXISTS idx_installments_status ON loan_installments (status);

-- Composite for overdue check
CREATE INDEX IF NOT EXISTS idx_installments_overdue ON loan_installments (status, due_date);

-- Composite for member installments
CREATE INDEX IF NOT EXISTS idx_installments_member ON loan_installments (member_id, status);

-- ============================================
-- LOAN PAYMENTS TABLE INDEXES
-- ============================================

-- Index for loan payments
CREATE INDEX IF NOT EXISTS idx_payments_loan ON loan_payments (loan_id);

-- Index for member payments
CREATE INDEX IF NOT EXISTS idx_payments_member ON loan_payments (member_id);

-- Index for payment date (reports)
CREATE INDEX IF NOT EXISTS idx_payments_date ON loan_payments (payment_date);

-- Index for UTR reference
CREATE INDEX IF NOT EXISTS idx_payments_utr ON loan_payments (utr_reference);

-- ============================================
-- SAVINGS ACCOUNTS TABLE INDEXES
-- ============================================

-- Index for member savings
CREATE INDEX IF NOT EXISTS idx_savings_member ON savings_accounts (member_id);

-- Index for scheme
CREATE INDEX IF NOT EXISTS idx_savings_scheme ON savings_accounts (scheme_id);

-- Index for status
CREATE INDEX IF NOT EXISTS idx_savings_status ON savings_accounts (status);

-- Index for account number
CREATE INDEX IF NOT EXISTS idx_savings_account_number ON savings_accounts (account_number);

-- Composite for member active savings
CREATE INDEX IF NOT EXISTS idx_savings_member_status ON savings_accounts (member_id, status);

-- ============================================
-- SAVINGS SCHEDULE TABLE INDEXES
-- ============================================

-- Index for account schedule
CREATE INDEX IF NOT EXISTS idx_schedule_account ON savings_schedule (savings_account_id);

-- Index for due date
CREATE INDEX IF NOT EXISTS idx_schedule_due_date ON savings_schedule (due_date);

-- Index for status
CREATE INDEX IF NOT EXISTS idx_schedule_status ON savings_schedule (status);

-- Composite for pending dues
CREATE INDEX IF NOT EXISTS idx_schedule_pending ON savings_schedule (status, due_date);

-- ============================================
-- SAVINGS TRANSACTIONS TABLE INDEXES
-- ============================================

-- Index for account transactions
CREATE INDEX IF NOT EXISTS idx_savings_trans_account ON savings_transactions (savings_account_id);

-- Index for transaction date
CREATE INDEX IF NOT EXISTS idx_savings_trans_date ON savings_transactions (transaction_date);

-- Index for transaction type
CREATE INDEX IF NOT EXISTS idx_savings_trans_type ON savings_transactions (transaction_type);

-- ============================================
-- FINES TABLE INDEXES
-- ============================================

-- Index for member fines
CREATE INDEX IF NOT EXISTS idx_fines_member ON fines (member_id);

-- Index for loan fines
CREATE INDEX IF NOT EXISTS idx_fines_loan ON fines (loan_id);

-- Index for status
CREATE INDEX IF NOT EXISTS idx_fines_status ON fines (status);

-- Composite for pending fines
CREATE INDEX IF NOT EXISTS idx_fines_pending ON fines (status, member_id);

-- ============================================
-- BANK TRANSACTIONS TABLE INDEXES
-- ============================================

-- Index for import batch
CREATE INDEX IF NOT EXISTS idx_bank_trans_import ON bank_transactions (import_id);

-- Index for matching status
CREATE INDEX IF NOT EXISTS idx_bank_trans_status ON bank_transactions (mapping_status);

-- Index for UTR reference
CREATE INDEX IF NOT EXISTS idx_bank_trans_utr ON bank_transactions (utr_reference);

-- Index for transaction date
CREATE INDEX IF NOT EXISTS idx_bank_trans_date ON bank_transactions (transaction_date);

-- Composite for unmatched transactions
CREATE INDEX IF NOT EXISTS idx_bank_trans_unmatched ON bank_transactions (mapping_status, import_id);

-- ============================================
-- LEDGER TABLE INDEXES
-- ============================================

-- Index for ledger date
CREATE INDEX IF NOT EXISTS idx_ledger_date ON ledger (transaction_date);

-- Index for account
CREATE INDEX IF NOT EXISTS idx_ledger_account ON ledger (account_id);

-- Index for reference
CREATE INDEX IF NOT EXISTS idx_ledger_reference ON ledger (reference_type, reference_id);

-- ============================================
-- NOTIFICATIONS TABLE INDEXES
-- ============================================

-- Index for user notifications
CREATE INDEX IF NOT EXISTS idx_notifications_user ON notifications (user_type, user_id);

-- Index for read status
CREATE INDEX IF NOT EXISTS idx_notifications_read ON notifications (is_read);

-- Composite for unread notifications
CREATE INDEX IF NOT EXISTS idx_notifications_unread ON notifications (user_type, user_id, is_read);

-- ============================================
-- AUDIT LOG TABLE INDEXES
-- ============================================

-- Index for entity lookup
CREATE INDEX IF NOT EXISTS idx_audit_entity ON audit_log (entity_type, entity_id);

-- Index for user lookup
CREATE INDEX IF NOT EXISTS idx_audit_user ON audit_log (user_type, user_id);

-- Index for date range
CREATE INDEX IF NOT EXISTS idx_audit_date ON audit_log (created_at);

-- ============================================
-- LOAN APPLICATIONS TABLE INDEXES
-- ============================================

-- Index for member applications
CREATE INDEX IF NOT EXISTS idx_applications_member ON loan_applications (member_id);

-- Index for status
CREATE INDEX IF NOT EXISTS idx_applications_status ON loan_applications (status);

-- Composite for pending applications
CREATE INDEX IF NOT EXISTS idx_applications_pending ON loan_applications (status, created_at);

-- ============================================
-- LOAN GUARANTORS TABLE INDEXES
-- ============================================

-- Index for loan guarantors
CREATE INDEX IF NOT EXISTS idx_guarantors_loan ON loan_guarantors (loan_id);

-- Index for guarantor member
CREATE INDEX IF NOT EXISTS idx_guarantors_member ON loan_guarantors (guarantor_id);

-- Index for consent token
CREATE INDEX IF NOT EXISTS idx_guarantors_token ON loan_guarantors (consent_token);

-- Index for consent status
CREATE INDEX IF NOT EXISTS idx_guarantors_consent ON loan_guarantors (consent_status);

-- ============================================
-- FULL-TEXT SEARCH INDEXES (MySQL 5.6+)
-- ============================================

-- Full-text search on members (for advanced search)
-- ALTER TABLE members ADD FULLTEXT INDEX ft_members_search (first_name, last_name, phone, email);

-- Full-text search on loan notes/remarks
-- ALTER TABLE loans ADD FULLTEXT INDEX ft_loans_remarks (remarks);

-- ============================================
-- VERIFY INDEXES CREATED
-- ============================================
-- Run: SHOW INDEX FROM members;
-- Run: SHOW INDEX FROM loans;
-- Run: SHOW INDEX FROM loan_installments;
-- etc.

-- ============================================
-- OPTIMIZATION NOTES
-- ============================================
-- 1. Run ANALYZE TABLE after adding indexes
-- 2. Monitor slow query log after deployment
-- 3. Consider partitioning for tables > 1M rows
-- 4. Use EXPLAIN to verify index usage
-- 5. Regularly update statistics: ANALYZE TABLE table_name;