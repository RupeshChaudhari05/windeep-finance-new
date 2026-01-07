-- ===================================================================
-- MINIMAL TEST DATA - GUARANTEED TO WORK
-- ===================================================================

USE windeep_finance_new;

-- Clean up
SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM loans WHERE loan_number LIKE 'TEST%';

DELETE FROM savings_accounts WHERE account_number LIKE 'SAV-TEST%';

DELETE FROM members WHERE member_code LIKE 'TEST%';

DELETE FROM savings_schemes WHERE scheme_code = 'TEST-SCHEME';

DELETE FROM loan_products WHERE product_code = 'TEST-PL001';

DELETE FROM bank_accounts WHERE account_number LIKE 'TEST-%';

SET FOREIGN_KEY_CHECKS = 1;

-- Create savings scheme
INSERT INTO
    savings_schemes (
        scheme_code,
        scheme_name,
        monthly_amount,
        interest_rate,
        duration_months,
        is_active
    )
VALUES (
        'TEST-SCHEME',
        'Test Scheme',
        1000.00,
        4.00,
        12,
        1
    );

SET @scheme_id = LAST_INSERT_ID();

-- Create loan product
INSERT INTO
    loan_products (
        product_code,
        product_name,
        min_amount,
        max_amount,
        min_tenure_months,
        max_tenure_months,
        interest_rate,
        interest_type,
        is_active
    )
VALUES (
        'TEST-PL001',
        'Test Personal Loan',
        10000.00,
        500000.00,
        6,
        60,
        12.00,
        'reducing',
        1
    );

SET @product_id = LAST_INSERT_ID();

-- Create members
INSERT INTO
    members (
        member_code,
        first_name,
        last_name,
        gender,
        phone,
        email,
        address_line1,
        city,
        state,
        pincode,
        join_date,
        status
    )
VALUES (
        'TEST001',
        'Rajesh',
        'Kumar',
        'male',
        '9876543210',
        'rajesh@test.com',
        'Mumbai Address',
        'Mumbai',
        'Maharashtra',
        '400001',
        '2023-01-15',
        'active'
    ),
    (
        'TEST002',
        'Priya',
        'Sharma',
        'female',
        '9876543211',
        'priya@test.com',
        'Delhi Address',
        'Delhi',
        'Delhi',
        '110001',
        '2023-02-01',
        'active'
    ),
    (
        'TEST003',
        'Amit',
        'Patel',
        'male',
        '9876543212',
        'amit@test.com',
        'Ahmedabad Address',
        'Ahmedabad',
        'Gujarat',
        '380001',
        '2023-03-10',
        'active'
    );

SET @member1 = LAST_INSERT_ID();

SET @member2 = @member1 + 1;

SET @member3 = @member1 + 2;

-- Create savings accounts
INSERT INTO
    savings_accounts (
        account_number,
        member_id,
        scheme_id,
        monthly_amount,
        start_date,
        current_balance,
        status
    )
VALUES (
        'SAV-TEST-001',
        @member1,
        @scheme_id,
        5000.00,
        '2023-01-15',
        50000.00,
        'active'
    ),
    (
        'SAV-TEST-002',
        @member2,
        @scheme_id,
        5000.00,
        '2023-02-01',
        75000.00,
        'active'
    ),
    (
        'SAV-TEST-003',
        @member3,
        @scheme_id,
        3000.00,
        '2023-03-10',
        35000.00,
        'active'
    );

-- Create bank account
INSERT INTO
    bank_accounts (
        bank_name,
        branch_name,
        account_number,
        ifsc_code,
        opening_balance,
        current_balance,
        is_active
    )
VALUES (
        'HDFC Bank',
        'Main Branch',
        'TEST-ACC-001',
        'HDFC0000123',
        1000000.00,
        1250000.00,
        1
    );

SET @bank_id = LAST_INSERT_ID();

-- Show results
SELECT '✓ Test data loaded!' AS status;

SELECT COUNT(*) AS members
FROM members
WHERE
    member_code LIKE 'TEST%';

SELECT COUNT(*) AS savings
FROM savings_accounts
WHERE
    account_number LIKE 'SAV-TEST%';