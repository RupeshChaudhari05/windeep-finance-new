-- Migration 009: Add Database Constraints and Validations
-- Purpose: Add CHECK constraints, foreign key cascades, and data integrity rules
-- Date: January 6, 2026

USE windeep_finance;

-- ============================================
-- PART 1: Add CHECK constraints (MySQL 8.0.16+)
-- ============================================

-- Loans table: Prevent negative balances
ALTER TABLE loans
ADD CONSTRAINT chk_loans_positive_amounts CHECK (
    principal_amount >= 0
    AND outstanding_principal >= 0
    AND outstanding_interest >= 0
    AND outstanding_fine >= 0
    AND emi_amount >= 0
);

-- Loan installments: Prevent negative amounts
ALTER TABLE loan_installments
ADD CONSTRAINT chk_installments_positive CHECK (
    principal_amount >= 0
    AND interest_amount >= 0
    AND emi_amount >= 0
    AND principal_paid >= 0
    AND interest_paid >= 0
    AND fine_paid >= 0
    AND principal_paid <= principal_amount
    AND interest_paid <= interest_amount
);

-- Loan payments: Ensure positive amounts
ALTER TABLE loan_payments
ADD CONSTRAINT chk_payments_positive CHECK (
    total_amount > 0
    AND principal_component >= 0
    AND interest_component >= 0
    AND fine_component >= 0
    AND excess_amount >= 0
);

-- Fines: Prevent negative amounts
ALTER TABLE fines
ADD CONSTRAINT chk_fines_positive CHECK (
    fine_amount > 0
    AND paid_amount >= 0
    AND balance_amount >= 0
    AND balance_amount <= fine_amount
);

-- Savings accounts: Balance constraints
ALTER TABLE savings_accounts
ADD CONSTRAINT chk_savings_balance CHECK (
    current_balance >= 0
    AND minimum_balance >= 0
    AND current_balance >= minimum_balance - 100
);
-- Allow ₹100 temporary breach

-- Bank transactions: Amount must be positive
ALTER TABLE bank_transactions
ADD CONSTRAINT chk_bank_transaction_amount CHECK (
    amount > 0
    OR (
        amount = 0
        AND transaction_type = 'reversal'
    )
);

-- ============================================
-- PART 2: Add missing indexes for performance
-- ============================================

-- Member searches
ALTER TABLE members
ADD INDEX idx_member_search (
    member_code,
    phone,
    first_name,
    last_name
);

-- Loan status and date queries
ALTER TABLE loans
ADD INDEX idx_loan_status_date (status, disbursement_date);

ALTER TABLE loans
ADD INDEX idx_loan_member_status (member_id, status);

-- Installment due date queries (for overdue checks)
ALTER TABLE loan_installments
ADD INDEX idx_installment_due (status, due_date);

ALTER TABLE loan_installments
ADD INDEX idx_installment_loan_status (loan_id, status);

-- Payment date queries
ALTER TABLE loan_payments ADD INDEX idx_payment_date (payment_date);

ALTER TABLE loan_payments
ADD INDEX idx_payment_loan (loan_id, payment_date);

-- Fine queries
ALTER TABLE fines ADD INDEX idx_fine_status_date (status, fine_date);

ALTER TABLE fines
ADD INDEX idx_fine_member_status (member_id, status);

-- Bank transaction searches
ALTER TABLE bank_transactions
ADD INDEX idx_bank_txn_status (
    mapping_status,
    transaction_date
);

ALTER TABLE bank_transactions
ADD INDEX idx_bank_txn_date (
    transaction_date,
    bank_account_id
);

-- Ledger queries
ALTER TABLE general_ledger
ADD INDEX idx_gl_date_type (
    transaction_date,
    voucher_type
);

ALTER TABLE member_ledger
ADD INDEX idx_member_ledger_date (member_id, transaction_date);

-- ============================================
-- PART 3: Add foreign key ON DELETE/UPDATE cascades
-- ============================================

-- Note: First drop existing FKs, then recreate with cascades

-- Loan guarantors: Remove guarantor when loan is deleted
ALTER TABLE loan_guarantors
DROP FOREIGN KEY IF EXISTS fk_loan_guarantors_loan;

ALTER TABLE loan_guarantors
ADD CONSTRAINT fk_loan_guarantors_loan FOREIGN KEY (loan_id) REFERENCES loans (id) ON DELETE CASCADE ON UPDATE CASCADE;

-- Loan installments: Delete when loan is deleted
ALTER TABLE loan_installments
DROP FOREIGN KEY IF EXISTS fk_loan_installments_loan;

ALTER TABLE loan_installments
ADD CONSTRAINT fk_loan_installments_loan FOREIGN KEY (loan_id) REFERENCES loans (id) ON DELETE CASCADE ON UPDATE CASCADE;

-- Loan payments: Prevent deletion if payments exist (protect data)
ALTER TABLE loan_payments
DROP FOREIGN KEY IF EXISTS fk_loan_payments_loan;

ALTER TABLE loan_payments
ADD CONSTRAINT fk_loan_payments_loan FOREIGN KEY (loan_id) REFERENCES loans (id) ON DELETE RESTRICT ON UPDATE CASCADE;

-- Fines: Restrict deletion if fines exist
ALTER TABLE fines DROP FOREIGN KEY IF EXISTS fk_fines_member;

ALTER TABLE fines
ADD CONSTRAINT fk_fines_member FOREIGN KEY (member_id) REFERENCES members (id) ON DELETE RESTRICT ON UPDATE CASCADE;

-- ============================================
-- PART 4: Add unique constraints
-- ============================================

-- Ensure loan numbers are unique
ALTER TABLE loans
ADD UNIQUE INDEX idx_loan_number_unique (loan_number);

-- Ensure member codes are unique
ALTER TABLE members
ADD UNIQUE INDEX idx_member_code_unique (member_code);

-- Ensure Aadhaar uniqueness (if not already)
ALTER TABLE members
ADD UNIQUE INDEX idx_aadhaar_unique (aadhaar_number);

-- Savings account number uniqueness
ALTER TABLE savings_accounts
ADD UNIQUE INDEX idx_account_number_unique (account_number);

-- ============================================
-- PART 5: Add default values
-- ============================================

-- Loans: Set default status
ALTER TABLE loans
MODIFY COLUMN status ENUM(
    'pending',
    'active',
    'overdue',
    'closed',
    'foreclosed',
    'written_off'
) DEFAULT 'pending';

-- Loan installments: Set default status
ALTER TABLE loan_installments
MODIFY COLUMN status ENUM(
    'upcoming',
    'pending',
    'paid',
    'overdue',
    'skipped',
    'waived'
) DEFAULT 'upcoming';

-- Fines: Set default status
ALTER TABLE fines
MODIFY COLUMN status ENUM(
    'pending',
    'paid',
    'waived',
    'cancelled'
) DEFAULT 'pending';

-- Bank transactions: Set default mapping status
ALTER TABLE bank_transactions
MODIFY COLUMN mapping_status ENUM(
    'unmapped',
    'mapped',
    'split',
    'ignored',
    'partial'
) DEFAULT 'unmapped';

-- ============================================
-- PART 6: Add data validation triggers
-- ============================================

DELIMITER $$

-- Trigger: Validate loan disbursement date
CREATE TRIGGER trg_loan_validate_dates
BEFORE INSERT ON loans
FOR EACH ROW
BEGIN
    IF NEW.first_emi_date <= NEW.disbursement_date THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'First EMI date must be after disbursement date';
    END IF;
    
    IF NEW.disbursement_date > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Disbursement date cannot be in future';
    END IF;
    
    IF DATEDIFF(NEW.first_emi_date, NEW.disbursement_date) < 7 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'First EMI date must be at least 7 days after disbursement';
    END IF;
END$$

-- Trigger: Validate installment due date sequence
CREATE TRIGGER trg_installment_validate_due_date
BEFORE INSERT ON loan_installments
FOR EACH ROW
BEGIN
    DECLARE prev_due_date DATE;
    
    -- Get previous installment due date
    SELECT due_date INTO prev_due_date
    FROM loan_installments
    WHERE loan_id = NEW.loan_id
      AND installment_number = NEW.installment_number - 1;
    
    -- Validate sequence
    IF prev_due_date IS NOT NULL AND NEW.due_date <= prev_due_date THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Installment due date must be after previous installment';
    END IF;
END$$

-- Trigger: Prevent payment exceeding outstanding
CREATE TRIGGER trg_payment_validate_amount
BEFORE INSERT ON loan_payments
FOR EACH ROW
BEGIN
    DECLARE v_outstanding DECIMAL(15,2);
    DECLARE v_interest_outstanding DECIMAL(15,2);
    DECLARE v_fine_outstanding DECIMAL(15,2);
    
    SELECT outstanding_principal, outstanding_interest, outstanding_fine
    INTO v_outstanding, v_interest_outstanding, v_fine_outstanding
    FROM loans
    WHERE id = NEW.loan_id;
    
    IF NEW.total_amount > (v_outstanding + v_interest_outstanding + v_fine_outstanding + 1000) THEN
        -- Allow ₹1000 buffer for processing fees/adjustments
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Payment amount exceeds total outstanding (principal + interest + fine)';
    END IF;
END$$

DELIMITER;

-- ============================================
-- PART 7: Create views for common queries
-- ============================================

-- View: Active loans with member details
CREATE OR REPLACE VIEW vw_active_loans AS
SELECT
    l.*,
    m.member_code,
    m.first_name,
    m.last_name,
    m.phone,
    lp.product_name,
    DATEDIFF(
        CURDATE(),
        l.disbursement_date
    ) AS loan_age_days,
    (
        SELECT COUNT(*)
        FROM loan_installments
        WHERE
            loan_id = l.id
            AND status = 'overdue'
    ) AS overdue_count
FROM
    loans l
    JOIN members m ON m.id = l.member_id
    JOIN loan_products lp ON lp.id = l.loan_product_id
WHERE
    l.status IN ('active', 'overdue');

-- View: Pending EMIs
CREATE OR REPLACE VIEW vw_pending_emis AS
SELECT li.*, l.loan_number, l.member_id, m.member_code, m.first_name, m.last_name, m.phone, DATEDIFF(CURDATE(), li.due_date) AS days_overdue
FROM
    loan_installments li
    JOIN loans l ON l.id = li.loan_id
    JOIN members m ON m.id = l.member_id
WHERE
    li.status IN ('pending', 'overdue')
ORDER BY li.due_date ASC;

-- View: Member financial summary
CREATE OR REPLACE VIEW vw_member_financial_summary AS
SELECT
    m.id AS member_id,
    m.member_code,
    m.first_name,
    m.last_name,
    COALESCE(
        SUM(
            CASE
                WHEN l.status IN ('active', 'overdue') THEN l.outstanding_principal
                ELSE 0
            END
        ),
        0
    ) AS total_loan_outstanding,
    COALESCE(
        SUM(
            CASE
                WHEN l.status IN ('active', 'overdue') THEN l.outstanding_interest
                ELSE 0
            END
        ),
        0
    ) AS total_interest_outstanding,
    COALESCE(
        SUM(
            CASE
                WHEN f.status = 'pending' THEN f.balance_amount
                ELSE 0
            END
        ),
        0
    ) AS total_fine_outstanding,
    COALESCE(
        (
            SELECT current_balance
            FROM savings_accounts
            WHERE
                member_id = m.id
                AND is_active = 1
            LIMIT 1
        ),
        0
    ) AS savings_balance,
    COUNT(DISTINCT l.id) AS active_loan_count
FROM members m
    LEFT JOIN loans l ON l.member_id = m.id
    LEFT JOIN fines f ON f.member_id = m.id
WHERE
    m.status = 'active'
GROUP BY
    m.id;

-- ============================================
-- PART 8: Verification queries
-- ============================================

-- Check for constraint violations in existing data
SELECT 'Loans with negative balances' AS check_name, COUNT(*) AS violation_count
FROM loans
WHERE
    outstanding_principal < 0
    OR outstanding_interest < 0
    OR outstanding_fine < 0
UNION ALL
SELECT 'Installments with overpayment', COUNT(*)
FROM loan_installments
WHERE
    principal_paid > principal_amount
    OR interest_paid > interest_amount
UNION ALL
SELECT 'Fines with negative balance', COUNT(*)
FROM fines
WHERE
    balance_amount < 0
UNION ALL
SELECT 'Duplicate member codes', COUNT(*) - COUNT(DISTINCT member_code)
FROM members
UNION ALL
SELECT 'Duplicate loan numbers', COUNT(*) - COUNT(DISTINCT loan_number)
FROM loans;

-- ============================================
-- USAGE NOTES
-- ============================================

/*
1. This migration adds:
- CHECK constraints to prevent negative balances
- Indexes for better query performance
- Foreign key cascades for referential integrity
- Unique constraints for business keys
- Validation triggers for data integrity
- Views for common queries

2. To verify constraints:
- Try inserting invalid data (should fail)
- Check existing data for violations (run verification queries)

3. Performance impact:
- CHECK constraints: Minimal (validated on insert/update only)
- Indexes: Slight write overhead, major read improvement
- Triggers: Minimal (only on insert/update)

4. Rollback: Not recommended after production use (data may depend on constraints)
*/

-- ============================================
-- ROLLBACK SCRIPT (USE WITH CAUTION)
-- ============================================

/*
-- Drop CHECK constraints
ALTER TABLE loans DROP CHECK chk_loans_positive_amounts;
ALTER TABLE loan_installments DROP CHECK chk_installments_positive;
ALTER TABLE loan_payments DROP CHECK chk_payments_positive;
ALTER TABLE fines DROP CHECK chk_fines_positive;
ALTER TABLE savings_accounts DROP CHECK chk_savings_balance;
ALTER TABLE bank_transactions DROP CHECK chk_bank_transaction_amount;

-- Drop triggers
DROP TRIGGER IF EXISTS trg_loan_validate_dates;
DROP TRIGGER IF EXISTS trg_installment_validate_due_date;
DROP TRIGGER IF EXISTS trg_payment_validate_amount;

-- Drop views
DROP VIEW IF EXISTS vw_active_loans;
DROP VIEW IF EXISTS vw_pending_emis;
DROP VIEW IF EXISTS vw_member_financial_summary;
*/