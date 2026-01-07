-- ===================================================================
-- COMPLETE TEST DATA SETUP FOR WINDEEP_FINANCE_NEW
-- Compatible with actual database schema
-- ===================================================================

USE windeep_finance_new;

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

DELETE FROM savings_schedule WHERE 1 = 1;

DELETE FROM savings_accounts WHERE 1 = 1;

DELETE FROM members WHERE member_code LIKE 'TEST%';

DELETE FROM savings_schemes WHERE scheme_code = 'TEST-SCHEME';

DELETE FROM loan_products WHERE product_code = 'TEST-PL001';

SET FOREIGN_KEY_CHECKS = 1;

-- ===================================================================
-- STEP 1: CREATE REQUIRED SCHEMES AND PRODUCTS
-- ===================================================================

-- Create test savings scheme
INSERT INTO
    savings_schemes (
        scheme_name,
        scheme_code,
        description,
        monthly_amount,
        interest_rate,
        duration_months,
        late_fine_type,
        late_fine_value,
        grace_period_days,
        is_active,
        created_at
    )
VALUES (
        'Test Savings Scheme',
        'TEST-SCHEME',
        'Regular savings for testing',
        1000.00,
        4.00,
        12,
        'per_day',
        10.00,
        5,
        1,
        NOW()
    );

SET @scheme_id = LAST_INSERT_ID();

-- Create test loan product
INSERT INTO
    loan_products (
        product_code,
        product_name,
        description,
        min_amount,
        max_amount,
        min_tenure_months,
        max_tenure_months,
        interest_rate,
        interest_type,
        processing_fee_type,
        processing_fee_value,
        min_guarantors,
        max_guarantors,
        is_active,
        created_at
    )
VALUES (
        'TEST-PL001',
        'Test Personal Loan',
        'Personal loan for testing',
        10000.00,
        500000.00,
        6,
        60,
        12.00,
        'reducing',
        'percentage',
        2.00,
        0,
        2,
        1,
        NOW()
    );

SET @product_id = LAST_INSERT_ID();

-- ===================================================================
-- STEP 2: CREATE TEST MEMBERS
-- ===================================================================

INSERT INTO
    members (
        member_code,
        first_name,
        last_name,
        father_name,
        date_of_birth,
        gender,
        phone,
        email,
        address_line1,
        city,
        state,
        pincode,
        join_date,
        membership_type,
        status,
        created_at
    )
VALUES (
        'TEST001',
        'Rajesh',
        'Kumar',
        'Ram Kumar',
        '1985-03-15',
        'male',
        '9876543210',
        'rajesh@test.com',
        'House No 123, MG Road',
        'Mumbai',
        'Maharashtra',
        '400001',
        '2023-01-15',
        'regular',
        'active',
        NOW()
    ),
    (
        'TEST002',
        'Priya',
        'Sharma',
        'Vijay Sharma',
        '1990-07-22',
        'female',
        '9876543211',
        'priya@test.com',
        'Flat 456, Park Street',
        'Delhi',
        'Delhi',
        '110001',
        '2023-02-01',
        'regular',
        'active',
        NOW()
    ),
    (
        'TEST003',
        'Amit',
        'Patel',
        'Suresh Patel',
        '1988-11-30',
        'male',
        '9876543212',
        'amit@test.com',
        'Plot 789, Gandhi Nagar',
        'Ahmedabad',
        'Gujarat',
        '380001',
        '2023-03-10',
        'regular',
        'active',
        NOW()
    ),
    (
        'TEST004',
        'Sneha',
        'Reddy',
        'Krishna Reddy',
        '1992-05-18',
        'female',
        '9876543213',
        'sneha@test.com',
        'Villa 12, Jubilee Hills',
        'Hyderabad',
        'Telangana',
        '500001',
        '2023-04-05',
        'regular',
        'active',
        NOW()
    ),
    (
        'TEST005',
        'Vikram',
        'Singh',
        'Amarjeet Singh',
        '1987-09-25',
        'male',
        '9876543214',
        'vikram@test.com',
        'House 567, Civil Lines',
        'Lucknow',
        'Uttar Pradesh',
        '226001',
        '2023-05-12',
        'regular',
        'active',
        NOW()
    );

SET @member1 = LAST_INSERT_ID();

SET @member2 = @member1 + 1;

SET @member3 = @member1 + 2;

SET @member4 = @member1 + 3;

SET @member5 = @member1 + 4;

-- ===================================================================
-- STEP 3: CREATE SAVINGS ACCOUNTS
-- ===================================================================

INSERT INTO
    savings_accounts (
        account_number,
        member_id,
        scheme_id,
        monthly_amount,
        start_date,
        current_balance,
        status,
        created_at
    )
VALUES (
        'SAV-TEST-001',
        @member1,
        @scheme_id,
        5000.00,
        '2023-01-15',
        50000.00,
        'active',
        NOW()
    ),
    (
        'SAV-TEST-002',
        @member2,
        @scheme_id,
        5000.00,
        '2023-02-01',
        75000.00,
        'active',
        NOW()
    ),
    (
        'SAV-TEST-003',
        @member3,
        @scheme_id,
        3000.00,
        '2023-03-10',
        35000.00,
        'active',
        NOW()
    ),
    (
        'SAV-TEST-004',
        @member4,
        @scheme_id,
        10000.00,
        '2023-04-05',
        120000.00,
        'active',
        NOW()
    ),
    (
        'SAV-TEST-005',
        @member5,
        @scheme_id,
        4000.00,
        '2023-05-12',
        42000.00,
        'active',
        NOW()
    );

-- ===================================================================
-- STEP 4: CREATE LOAN APPLICATIONS
-- ===================================================================

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

-- ===================================================================
-- STEP 5: CREATE ACTIVE LOANS
-- ===================================================================

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
        138690.84,
        3500.00,
        250.00,
        'active',
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
        66754.27,
        1500.00,
        0.00,
        'active',
        '2024-03-15 09:00:00'
    );

SET @loan1 = LAST_INSERT_ID();

SET @loan2 = @loan1 + 1;

SET @loan3 = @loan1 + 2;

-- ===================================================================
-- STEP 6: CREATE LOAN INSTALLMENTS
-- ===================================================================

-- Loan 1 installments (6 paid, 6 pending)
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

-- Loan 2 installments (2 paid, 1 overdue, rest pending)
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
    ),
    (
        @loan2,
        4,
        '2024-06-05',
        5790.79,
        1274.30,
        7065.09,
        0.00,
        0.00,
        NULL,
        'pending',
        NOW()
    );

-- Loan 3 installments (2 paid, 1 pending)
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

-- ===================================================================
-- STEP 7: CREATE BANK ACCOUNT AND TRANSACTIONS
-- ===================================================================

-- Create bank account if not exists
INSERT INTO
    bank_accounts (
        bank_name,
        branch_name,
        account_number,
        ifsc_code,
        opening_balance,
        current_balance,
        is_active,
        created_at
    )
SELECT *
FROM (
        SELECT 'HDFC Bank', 'Main Branch', 'TEST-50100012345678', 'HDFC0000123', 1000000.00, 1250000.00, 1, NOW()
    ) AS tmp
WHERE
    NOT EXISTS (
        SELECT 1
        FROM bank_accounts
        WHERE
            account_number = 'TEST-50100012345678'
    );

SET
    @bank_id = (
        SELECT id
        FROM bank_accounts
        WHERE
            account_number = 'TEST-50100012345678'
        LIMIT 1
    );

-- Create bank statement import
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
        10,
        0,
        10,
        1,
        NOW(),
        NOW(),
        NOW()
    );

SET @import_id = LAST_INSERT_ID();

-- Create unmapped transactions for testing
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

-- ===================================================================
-- STEP 8: CREATE FINES
-- ===================================================================

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
        'Late payment fine for testing',
        NOW()
    );

COMMIT;

-- ===================================================================
-- VERIFY DATA LOADED
-- ===================================================================

SELECT '=== TEST DATA LOADED SUCCESSFULLY ===' AS status;

SELECT 'Members' AS item, COUNT(*) AS count
FROM members
WHERE
    member_code LIKE 'TEST%'
UNION ALL
SELECT 'Savings Accounts', COUNT(*)
FROM savings_accounts
WHERE
    account_number LIKE 'SAV-TEST%'
UNION ALL
SELECT 'Loans', COUNT(*)
FROM loans
WHERE
    loan_number LIKE 'TESTLN%'
UNION ALL
SELECT 'Loan Installments', COUNT(*)
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
    utr_number LIKE 'TEST-UTR%'
UNION ALL
SELECT 'Fines', COUNT(*)
FROM fines
WHERE
    fine_code LIKE 'TEST-%';

SELECT '=== SYSTEM READY FOR TESTING ===' AS message;