-- ============================================
-- SIMPLE TEST DATA (Without Stored Procedures)
-- Quick test data for Windeep Finance
-- ============================================

USE windeep_finance;

START TRANSACTION;

-- Clean up existing test data
SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM loan_payments WHERE 1 = 1;

DELETE FROM loan_installments WHERE 1 = 1;

DELETE FROM loans WHERE 1 = 1;

DELETE FROM loan_applications WHERE 1 = 1;

DELETE FROM fines WHERE 1 = 1;

DELETE FROM bank_transactions WHERE 1 = 1;

DELETE FROM savings_transactions WHERE 1 = 1;

DELETE FROM savings_accounts WHERE member_id > 0;

DELETE FROM members WHERE member_code LIKE 'TEST%';

SET FOREIGN_KEY_CHECKS = 1;

-- Insert test members
INSERT INTO
    members (
        member_code,
        first_name,
        last_name,
        date_of_birth,
        gender,
        phone,
        email,
        address,
        city,
        state,
        pincode,
        joining_date,
        status,
        created_at
    )
VALUES (
        'TEST001',
        'Rajesh',
        'Kumar',
        '1985-03-15',
        'male',
        '9876543210',
        'rajesh@test.com',
        'Mumbai Address',
        'Mumbai',
        'Maharashtra',
        '400001',
        '2023-01-15',
        'active',
        NOW()
    ),
    (
        'TEST002',
        'Priya',
        'Sharma',
        '1990-07-22',
        'female',
        '9876543211',
        'priya@test.com',
        'Delhi Address',
        'Delhi',
        'Delhi',
        '110001',
        '2023-02-01',
        'active',
        NOW()
    ),
    (
        'TEST003',
        'Amit',
        'Patel',
        '1988-11-30',
        'male',
        '9876543212',
        'amit@test.com',
        'Ahmedabad Address',
        'Ahmedabad',
        'Gujarat',
        '380001',
        '2023-03-10',
        'active',
        NOW()
    ),
    (
        'TEST004',
        'Sneha',
        'Reddy',
        '1992-05-18',
        'female',
        '9876543213',
        'sneha@test.com',
        'Hyderabad Address',
        'Hyderabad',
        'Telangana',
        '500001',
        '2023-04-05',
        'active',
        NOW()
    ),
    (
        'TEST005',
        'Vikram',
        'Singh',
        '1987-09-25',
        'male',
        '9876543214',
        'vikram@test.com',
        'Lucknow Address',
        'Lucknow',
        'Uttar Pradesh',
        '226001',
        '2023-05-12',
        'active',
        NOW()
    );

SET @member1 = LAST_INSERT_ID();

SET @member2 = @member1 + 1;

SET @member3 = @member1 + 2;

SET @member4 = @member1 + 3;

SET @member5 = @member1 + 4;

-- Insert savings accounts
INSERT INTO
    savings_accounts (
        member_id,
        account_number,
        account_type,
        current_balance,
        minimum_balance,
        interest_rate,
        opening_date,
        is_active,
        created_at
    )
VALUES (
        @member1,
        'SAV-TEST-001',
        'regular',
        50000.00,
        1000.00,
        4.00,
        '2023-01-15',
        1,
        NOW()
    ),
    (
        @member2,
        'SAV-TEST-002',
        'regular',
        75000.00,
        1000.00,
        4.00,
        '2023-02-01',
        1,
        NOW()
    ),
    (
        @member3,
        'SAV-TEST-003',
        'regular',
        35000.00,
        1000.00,
        4.00,
        '2023-03-10',
        1,
        NOW()
    ),
    (
        @member4,
        'SAV-TEST-004',
        'regular',
        120000.00,
        1000.00,
        4.00,
        '2023-04-05',
        1,
        NOW()
    ),
    (
        @member5,
        'SAV-TEST-005',
        'regular',
        42000.00,
        1000.00,
        4.00,
        '2023-05-12',
        1,
        NOW()
    );

-- Get/Create loan products
INSERT INTO
    loan_products (
        product_name,
        product_code,
        interest_type,
        interest_rate_min,
        interest_rate_max,
        min_amount,
        max_amount,
        min_tenure_months,
        max_tenure_months,
        processing_fee_percentage,
        is_active,
        created_at
    )
SELECT *
FROM (
        SELECT 'Personal Loan', 'PL001', 'reducing_balance', 10.00, 15.00, 10000.00, 500000.00, 6, 60, 2.00, 1, NOW()
    ) AS tmp
WHERE
    NOT EXISTS (
        SELECT 1
        FROM loan_products
        WHERE
            product_code = 'PL001'
    );

SET
    @product_id = (
        SELECT id
        FROM loan_products
        WHERE
            product_code = 'PL001'
        LIMIT 1
    );

-- Insert loan applications
INSERT INTO
    loan_applications (
        application_number,
        member_id,
        loan_product_id,
        requested_amount,
        requested_tenure_months,
        purpose,
        application_date,
        status,
        approved_amount,
        approved_tenure_months,
        approved_interest_rate,
        created_at
    )
VALUES (
        'TEST-APP-001',
        @member1,
        @product_id,
        100000.00,
        12,
        'Home renovation',
        '2024-01-15',
        'approved',
        100000.00,
        12,
        12.00,
        '2024-01-15 10:00:00'
    ),
    (
        'TEST-APP-002',
        @member2,
        @product_id,
        150000.00,
        24,
        'Wedding',
        '2024-02-01',
        'approved',
        150000.00,
        24,
        11.50,
        '2024-02-01 10:00:00'
    ),
    (
        'TEST-APP-003',
        @member3,
        @product_id,
        80000.00,
        12,
        'Education',
        '2024-03-10',
        'approved',
        80000.00,
        12,
        10.00,
        '2024-03-10 10:00:00'
    );

SET @app1 = LAST_INSERT_ID();

SET @app2 = @app1 + 1;

SET @app3 = @app1 + 2;

-- Insert loans
INSERT INTO
    loans (
        loan_number,
        member_id,
        loan_product_id,
        loan_application_id,
        principal_amount,
        interest_rate,
        interest_type,
        tenure_months,
        emi_amount,
        disbursement_date,
        first_emi_date,
        maturity_date,
        outstanding_principal,
        outstanding_interest,
        outstanding_fine,
        status,
        disbursed_by,
        created_at
    )
VALUES (
        'TESTLN-001',
        @member1,
        @product_id,
        @app1,
        100000.00,
        12.00,
        'reducing_balance',
        12,
        8884.88,
        '2024-01-20',
        '2024-02-20',
        '2025-01-20',
        50000.00,
        2500.00,
        0.00,
        'active',
        1,
        '2024-01-20 10:00:00'
    ),
    (
        'TESTLN-002',
        @member2,
        @product_id,
        @app2,
        150000.00,
        11.50,
        'reducing_balance',
        24,
        7065.09,
        '2024-02-05',
        '2024-03-05',
        '2026-02-05',
        100000.00,
        3500.00,
        250.00,
        'active',
        1,
        '2024-02-05 11:00:00'
    ),
    (
        'TESTLN-003',
        @member3,
        @product_id,
        @app3,
        80000.00,
        10.00,
        'reducing_balance',
        12,
        7032.40,
        '2024-03-15',
        '2024-04-15',
        '2025-03-15',
        40000.00,
        1500.00,
        0.00,
        'active',
        1,
        '2024-03-15 09:00:00'
    );

SET @loan1 = LAST_INSERT_ID();

SET @loan2 = @loan1 + 1;

SET @loan3 = @loan1 + 2;

-- Manually insert installments for Loan 1 (12 months)
INSERT INTO
    loan_installments (
        loan_id,
        installment_number,
        due_date,
        principal_amount,
        interest_amount,
        total_amount,
        principal_paid,
        interest_paid,
        paid_date,
        status,
        created_at
    )
VALUES (
        @loan1,
        1,
        '2024-02-20',
        7884.88,
        1000.00,
        8884.88,
        7884.88,
        1000.00,
        '2024-02-20',
        'paid',
        NOW()
    ),
    (
        @loan1,
        2,
        '2024-03-20',
        7963.81,
        921.07,
        8884.88,
        7963.81,
        921.07,
        '2024-03-20',
        'paid',
        NOW()
    ),
    (
        @loan1,
        3,
        '2024-04-20',
        8043.53,
        841.35,
        8884.88,
        8043.53,
        841.35,
        '2024-04-20',
        'paid',
        NOW()
    ),
    (
        @loan1,
        4,
        '2024-05-20',
        8124.03,
        760.85,
        8884.88,
        8124.03,
        760.85,
        '2024-05-20',
        'paid',
        NOW()
    ),
    (
        @loan1,
        5,
        '2024-06-20',
        8205.33,
        679.55,
        8884.88,
        8205.33,
        679.55,
        '2024-06-20',
        'paid',
        NOW()
    ),
    (
        @loan1,
        6,
        '2024-07-20',
        8287.43,
        597.45,
        8884.88,
        8287.43,
        597.45,
        '2024-07-20',
        'paid',
        NOW()
    ),
    (
        @loan1,
        7,
        '2024-08-20',
        8370.34,
        514.54,
        8884.88,
        0.00,
        0.00,
        NULL,
        'pending',
        NOW()
    ),
    (
        @loan1,
        8,
        '2024-09-20',
        8454.08,
        430.80,
        8884.88,
        0.00,
        0.00,
        NULL,
        'pending',
        NOW()
    ),
    (
        @loan1,
        9,
        '2024-10-20',
        8538.64,
        346.24,
        8884.88,
        0.00,
        0.00,
        NULL,
        'pending',
        NOW()
    ),
    (
        @loan1,
        10,
        '2024-11-20',
        8624.04,
        260.84,
        8884.88,
        0.00,
        0.00,
        NULL,
        'pending',
        NOW()
    ),
    (
        @loan1,
        11,
        '2024-12-20',
        8710.29,
        174.59,
        8884.88,
        0.00,
        0.00,
        NULL,
        'pending',
        NOW()
    ),
    (
        @loan1,
        12,
        '2025-01-20',
        8797.42,
        87.46,
        8884.88,
        0.00,
        0.00,
        NULL,
        'pending',
        NOW()
    );

-- Installments for Loan 2 (some overdue)
INSERT INTO
    loan_installments (
        loan_id,
        installment_number,
        due_date,
        principal_amount,
        interest_amount,
        total_amount,
        principal_paid,
        interest_paid,
        paid_date,
        status,
        created_at
    )
VALUES (
        @loan2,
        1,
        '2024-03-05',
        5627.59,
        1437.50,
        7065.09,
        5627.59,
        1437.50,
        '2024-03-05',
        'paid',
        NOW()
    ),
    (
        @loan2,
        2,
        '2024-04-05',
        5681.46,
        1383.63,
        7065.09,
        5681.46,
        1383.63,
        '2024-04-05',
        'paid',
        NOW()
    ),
    (
        @loan2,
        3,
        '2024-05-05',
        5735.85,
        1329.24,
        7065.09,
        0.00,
        0.00,
        NULL,
        'overdue',
        NOW()
    );

-- Installments for Loan 3
INSERT INTO
    loan_installments (
        loan_id,
        installment_number,
        due_date,
        principal_amount,
        interest_amount,
        total_amount,
        principal_paid,
        interest_paid,
        paid_date,
        status,
        created_at
    )
VALUES (
        @loan3,
        1,
        '2024-04-15',
        6365.73,
        666.67,
        7032.40,
        6365.73,
        666.67,
        '2024-04-15',
        'paid',
        NOW()
    ),
    (
        @loan3,
        2,
        '2024-05-15',
        6418.78,
        613.62,
        7032.40,
        6418.78,
        613.62,
        '2024-05-15',
        'paid',
        NOW()
    ),
    (
        @loan3,
        3,
        '2024-06-15',
        6472.38,
        560.02,
        7032.40,
        0.00,
        0.00,
        NULL,
        'pending',
        NOW()
    );

-- Get bank account
INSERT INTO
    bank_accounts (
        bank_name,
        branch_name,
        account_number,
        account_type,
        ifsc_code,
        opening_balance,
        current_balance,
        is_active,
        created_at
    )
SELECT *
FROM (
        SELECT 'HDFC Bank', 'Main Branch', '50100012345678', 'current', 'HDFC0000123', 1000000.00, 1250000.00, 1, NOW()
    ) AS tmp
WHERE
    NOT EXISTS (
        SELECT 1
        FROM bank_accounts
        WHERE
            account_number = '50100012345678'
    );

SET
    @bank_id = (
        SELECT id
        FROM bank_accounts
        WHERE
            account_number = '50100012345678'
        LIMIT 1
    );

-- Insert bank statement import record
INSERT INTO
    bank_statement_imports (
        import_code,
        bank_account_id,
        file_name,
        file_path,
        file_type,
        status,
        total_transactions,
        mapped_count,
        unmapped_count,
        imported_by,
        imported_at,
        completed_at,
        created_at
    )
VALUES (
        'TESTIMP-001',
        @bank_id,
        'test_statement.xlsx',
        '/uploads/test_statement.xlsx',
        'xlsx',
        'completed',
        15,
        5,
        10,
        1,
        NOW(),
        NOW(),
        NOW()
    );

SET @import_id = LAST_INSERT_ID();

-- Insert unmapped bank transactions for testing
INSERT INTO
    bank_transactions (
        import_id,
        bank_account_id,
        transaction_date,
        transaction_type,
        amount,
        utr_number,
        description,
        mapping_status,
        created_at
    )
VALUES (
        @import_id,
        @bank_id,
        CURDATE(),
        'credit',
        8884.88,
        'TEST-UTR-001',
        'NEFT-Rajesh K-EMI Payment',
        'unmapped',
        NOW()
    ),
    (
        @import_id,
        @bank_id,
        CURDATE(),
        'credit',
        7065.09,
        'TEST-UTR-002',
        'IMPS-9876543211-Priya Payment',
        'unmapped',
        NOW()
    ),
    (
        @import_id,
        @bank_id,
        CURDATE(),
        'credit',
        7032.40,
        'TEST-UTR-003',
        'UPI-Amit@paytm-Loan EMI',
        'unmapped',
        NOW()
    ),
    (
        @import_id,
        @bank_id,
        CURDATE(),
        'credit',
        5000.00,
        'TEST-UTR-004',
        'RTGS-Unknown Sender',
        'unmapped',
        NOW()
    ),
    (
        @import_id,
        @bank_id,
        CURDATE(),
        'credit',
        10000.00,
        'TEST-UTR-005',
        'NEFT-TESTLN-001-Advance',
        'unmapped',
        NOW()
    ),
    (
        @import_id,
        @bank_id,
        CURDATE(),
        'credit',
        15000.00,
        'TEST-UTR-006',
        'Combined-TEST001-Multiple',
        'unmapped',
        NOW()
    ),
    (
        @import_id,
        @bank_id,
        CURDATE(),
        'credit',
        250.00,
        'TEST-UTR-007',
        'Fine-Priya-Late Payment',
        'unmapped',
        NOW()
    ),
    (
        @import_id,
        @bank_id,
        CURDATE(),
        'credit',
        5000.00,
        'TEST-UTR-008',
        'Savings-TEST004-Deposit',
        'unmapped',
        NOW()
    ),
    (
        @import_id,
        @bank_id,
        CURDATE(),
        'credit',
        3500.00,
        'TEST-UTR-009',
        'Partial-9876543214-Payment',
        'unmapped',
        NOW()
    ),
    (
        @import_id,
        @bank_id,
        CURDATE(),
        'credit',
        12000.00,
        'TEST-UTR-010',
        'RTGS-Multiple Loans Split',
        'unmapped',
        NOW()
    );

-- Insert fine
INSERT INTO
    fines (
        member_id,
        fine_code,
        fine_type,
        related_type,
        related_id,
        fine_date,
        due_date,
        days_late,
        fine_amount,
        paid_amount,
        balance_amount,
        status,
        remarks,
        created_by,
        created_at
    )
VALUES (
        @member2,
        'TEST-FIN-001',
        'loan_late',
        'loan_installment',
        (
            SELECT id
            FROM loan_installments
            WHERE
                loan_id = @loan2
                AND installment_number = 3
        ),
        CURDATE(),
        '2024-05-05',
        15,
        250.00,
        0.00,
        250.00,
        'pending',
        'Late payment fine',
        1,
        NOW()
    );

COMMIT;

-- Show results
SELECT '=== TEST DATA LOADED ===' AS status;

SELECT 'Members' AS item, COUNT(*) AS count
FROM members
WHERE
    member_code LIKE 'TEST%'
UNION ALL
SELECT 'Loans', COUNT(*)
FROM loans
WHERE
    loan_number LIKE 'TESTLN%'
UNION ALL
SELECT 'Installments', COUNT(*)
FROM loan_installments
WHERE
    loan_id IN (
        SELECT id
        FROM loans
        WHERE
            loan_number LIKE 'TESTLN%'
    )
UNION ALL
SELECT 'Unmapped Transactions', COUNT(*)
FROM bank_transactions
WHERE
    mapping_status = 'unmapped'
    AND utr_number LIKE 'TEST%';

SELECT
    l.loan_number,
    CONCAT(
        m.first_name,
        ' ',
        m.last_name
    ) AS member,
    l.principal_amount,
    l.emi_amount,
    l.status,
    (
        SELECT COUNT(*)
        FROM loan_installments
        WHERE
            loan_id = l.id
            AND status = 'paid'
    ) AS paid_emis,
    (
        SELECT COUNT(*)
        FROM loan_installments
        WHERE
            loan_id = l.id
            AND status = 'pending'
    ) AS pending_emis,
    (
        SELECT COUNT(*)
        FROM loan_installments
        WHERE
            loan_id = l.id
            AND status = 'overdue'
    ) AS overdue_emis
FROM loans l
    JOIN members m ON m.id = l.member_id
WHERE
    l.loan_number LIKE 'TESTLN%';

SELECT '=== Ready for testing! ===' AS message;