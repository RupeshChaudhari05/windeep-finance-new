-- ============================================
-- WINDEEP FINANCE - COMPLETE TEST DATA
-- Purpose: Realistic test data for all screens and features
-- Date: January 6, 2026
-- ============================================

USE windeep_finance;

-- Start transaction for safety
START TRANSACTION;

-- ============================================
-- STEP 1: CLEAN UP EXISTING TEST DATA
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;

-- Delete in correct order (respecting foreign keys)
DELETE FROM loan_payments WHERE loan_id IN ( SELECT id FROM loans );

DELETE FROM loan_installments
WHERE
    loan_id IN (
        SELECT id
        FROM loans
    );

DELETE FROM loan_guarantors;

DELETE FROM loans;

DELETE FROM loan_applications;

DELETE FROM fines;

DELETE FROM bank_transactions;

DELETE FROM bank_statement_imports;

DELETE FROM transaction_mappings;

DELETE FROM savings_transactions
WHERE
    savings_account_id IN (
        SELECT id
        FROM savings_accounts
    );

DELETE FROM savings_schedule
WHERE
    savings_account_id IN (
        SELECT id
        FROM savings_accounts
    );

DELETE FROM savings_accounts
WHERE
    member_id IN (
        SELECT id
        FROM members
        WHERE
            member_code LIKE 'MEMB%'
    );

DELETE FROM member_ledger
WHERE
    member_id IN (
        SELECT id
        FROM members
        WHERE
            member_code LIKE 'MEMB%'
    );

DELETE FROM members WHERE member_code LIKE 'MEMB%';

-- Reset auto-increment
ALTER TABLE members AUTO_INCREMENT = 1;

ALTER TABLE loans AUTO_INCREMENT = 1;

ALTER TABLE loan_applications AUTO_INCREMENT = 1;

ALTER TABLE loan_installments AUTO_INCREMENT = 1;

ALTER TABLE savings_accounts AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- STEP 2: INSERT MEMBERS (15 members with variety)
-- ============================================

INSERT INTO
    members (
        member_code,
        first_name,
        last_name,
        date_of_birth,
        gender,
        phone,
        email,
        aadhaar_number,
        pan_number,
        address,
        city,
        state,
        pincode,
        occupation,
        monthly_income,
        joining_date,
        status,
        created_at
    )
VALUES
    -- Active members with good credit
    (
        'MEMB000001',
        'Rajesh',
        'Kumar',
        '1985-03-15',
        'male',
        '9876543210',
        'rajesh.kumar@email.com',
        '123456789012',
        'ABCDE1234F',
        'House No 123, MG Road',
        'Mumbai',
        'Maharashtra',
        '400001',
        'Business Owner',
        75000.00,
        '2023-01-15',
        'active',
        NOW()
    ),
    (
        'MEMB000002',
        'Priya',
        'Sharma',
        '1990-07-22',
        'female',
        '9876543211',
        'priya.sharma@email.com',
        '123456789013',
        'ABCDE1235G',
        'Flat 456, Park Street',
        'Delhi',
        'Delhi',
        '110001',
        'Software Engineer',
        85000.00,
        '2023-02-01',
        'active',
        NOW()
    ),
    (
        'MEMB000003',
        'Amit',
        'Patel',
        '1988-11-30',
        'male',
        '9876543212',
        'amit.patel@email.com',
        '123456789014',
        'ABCDE1236H',
        'Plot 789, Gandhi Nagar',
        'Ahmedabad',
        'Gujarat',
        '380001',
        'Teacher',
        45000.00,
        '2023-03-10',
        'active',
        NOW()
    ),
    (
        'MEMB000004',
        'Sneha',
        'Reddy',
        '1992-05-18',
        'female',
        '9876543213',
        'sneha.reddy@email.com',
        '123456789015',
        'ABCDE1237I',
        'Villa 12, Jubilee Hills',
        'Hyderabad',
        'Telangana',
        '500001',
        'Doctor',
        120000.00,
        '2023-04-05',
        'active',
        NOW()
    ),
    (
        'MEMB000005',
        'Vikram',
        'Singh',
        '1987-09-25',
        'male',
        '9876543214',
        'vikram.singh@email.com',
        '123456789016',
        'ABCDE1238J',
        'House 567, Civil Lines',
        'Lucknow',
        'Uttar Pradesh',
        '226001',
        'Government Employee',
        55000.00,
        '2023-05-12',
        'active',
        NOW()
    ),

-- Members with some payment delays
(
    'MEMB000006',
    'Anjali',
    'Mehta',
    '1991-12-08',
    'female',
    '9876543215',
    'anjali.mehta@email.com',
    '123456789017',
    'ABCDE1239K',
    'Apartment 901, Linking Road',
    'Mumbai',
    'Maharashtra',
    '400002',
    'Marketing Manager',
    68000.00,
    '2023-06-20',
    'active',
    NOW()
),
(
    'MEMB000007',
    'Rahul',
    'Verma',
    '1989-04-14',
    'male',
    '9876543216',
    'rahul.verma@email.com',
    '123456789018',
    'ABCDE1240L',
    'House 234, Sector 15',
    'Noida',
    'Uttar Pradesh',
    '201301',
    'Sales Executive',
    52000.00,
    '2023-07-08',
    'active',
    NOW()
),
(
    'MEMB000008',
    'Kavita',
    'Desai',
    '1993-08-21',
    'female',
    '9876543217',
    'kavita.desai@email.com',
    '123456789019',
    'ABCDE1241M',
    'Flat 345, SG Highway',
    'Ahmedabad',
    'Gujarat',
    '380015',
    'HR Manager',
    62000.00,
    '2023-08-15',
    'active',
    NOW()
),

-- New members (no loan history)
(
    'MEMB000009',
    'Suresh',
    'Nair',
    '1986-02-10',
    'male',
    '9876543218',
    'suresh.nair@email.com',
    '123456789020',
    'ABCDE1242N',
    'House 678, MG Road',
    'Kochi',
    'Kerala',
    '682001',
    'Accountant',
    48000.00,
    '2024-10-01',
    'active',
    NOW()
),
(
    'MEMB000010',
    'Deepa',
    'Iyer',
    '1994-06-28',
    'female',
    '9876543219',
    'deepa.iyer@email.com',
    '123456789021',
    'ABCDE1243O',
    'Villa 89, Anna Nagar',
    'Chennai',
    'Tamil Nadu',
    '600040',
    'Architect',
    72000.00,
    '2024-11-15',
    'active',
    NOW()
),

-- Members for guarantor testing
(
    'MEMB000011',
    'Manoj',
    'Joshi',
    '1984-10-05',
    'male',
    '9876543220',
    'manoj.joshi@email.com',
    '123456789022',
    'ABCDE1244P',
    'Plot 111, Banjara Hills',
    'Hyderabad',
    'Telangana',
    '500034',
    'Chartered Accountant',
    95000.00,
    '2023-09-10',
    'active',
    NOW()
),
(
    'MEMB000012',
    'Pooja',
    'Agarwal',
    '1995-01-17',
    'female',
    '9876543221',
    'pooja.agarwal@email.com',
    '123456789023',
    'ABCDE1245Q',
    'House 222, Ballygunge',
    'Kolkata',
    'West Bengal',
    '700019',
    'Fashion Designer',
    58000.00,
    '2024-01-20',
    'active',
    NOW()
),

-- Members with high savings
(
    'MEMB000013',
    'Sanjay',
    'Kapoor',
    '1983-07-12',
    'male',
    '9876543222',
    'sanjay.kapoor@email.com',
    '123456789024',
    'ABCDE1246R',
    'Bungalow 5, Juhu',
    'Mumbai',
    'Maharashtra',
    '400049',
    'Film Producer',
    250000.00,
    '2022-06-01',
    'active',
    NOW()
),
(
    'MEMB000014',
    'Neha',
    'Gupta',
    '1990-03-29',
    'female',
    '9876543223',
    'neha.gupta@email.com',
    '123456789025',
    'ABCDE1247S',
    'Flat 567, Connaught Place',
    'Delhi',
    'Delhi',
    '110001',
    'Lawyer',
    110000.00,
    '2023-11-05',
    'active',
    NOW()
),
(
    'MEMB000015',
    'Arun',
    'Rao',
    '1988-11-14',
    'male',
    '9876543224',
    'arun.rao@email.com',
    '123456789026',
    'ABCDE1248T',
    'House 890, Koramangala',
    'Bangalore',
    'Karnataka',
    '560034',
    'IT Consultant',
    135000.00,
    '2024-02-10',
    'active',
    NOW()
);

-- ============================================
-- STEP 3: INSERT SAVINGS ACCOUNTS
-- ============================================

INSERT INTO
    savings_accounts (
        member_id,
        account_number,
        account_type,
        current_balance,
        minimum_balance,
        interest_rate,
        opening_date,
        maturity_date,
        is_active,
        created_at
    )
VALUES (
        1,
        'SAV00001',
        'regular',
        50000.00,
        1000.00,
        4.00,
        '2023-01-15',
        NULL,
        1,
        NOW()
    ),
    (
        2,
        'SAV00002',
        'regular',
        75000.00,
        1000.00,
        4.00,
        '2023-02-01',
        NULL,
        1,
        NOW()
    ),
    (
        3,
        'SAV00003',
        'regular',
        35000.00,
        1000.00,
        4.00,
        '2023-03-10',
        NULL,
        1,
        NOW()
    ),
    (
        4,
        'SAV00004',
        'regular',
        120000.00,
        1000.00,
        4.00,
        '2023-04-05',
        NULL,
        1,
        NOW()
    ),
    (
        5,
        'SAV00005',
        'regular',
        42000.00,
        1000.00,
        4.00,
        '2023-05-12',
        NULL,
        1,
        NOW()
    ),
    (
        6,
        'SAV00006',
        'regular',
        38000.00,
        1000.00,
        4.00,
        '2023-06-20',
        NULL,
        1,
        NOW()
    ),
    (
        7,
        'SAV00007',
        'regular',
        28000.00,
        1000.00,
        4.00,
        '2023-07-08',
        NULL,
        1,
        NOW()
    ),
    (
        8,
        'SAV00008',
        'regular',
        45000.00,
        1000.00,
        4.00,
        '2023-08-15',
        NULL,
        1,
        NOW()
    ),
    (
        9,
        'SAV00009',
        'regular',
        15000.00,
        1000.00,
        4.00,
        '2024-10-01',
        NULL,
        1,
        NOW()
    ),
    (
        10,
        'SAV00010',
        'regular',
        25000.00,
        1000.00,
        4.00,
        '2024-11-15',
        NULL,
        1,
        NOW()
    ),
    (
        11,
        'SAV00011',
        'regular',
        85000.00,
        1000.00,
        4.00,
        '2023-09-10',
        NULL,
        1,
        NOW()
    ),
    (
        12,
        'SAV00012',
        'regular',
        32000.00,
        1000.00,
        4.00,
        '2024-01-20',
        NULL,
        1,
        NOW()
    ),
    (
        13,
        'SAV00013',
        'fixed_deposit',
        500000.00,
        10000.00,
        6.50,
        '2022-06-01',
        '2025-06-01',
        1,
        NOW()
    ),
    (
        14,
        'SAV00014',
        'regular',
        95000.00,
        1000.00,
        4.00,
        '2023-11-05',
        NULL,
        1,
        NOW()
    ),
    (
        15,
        'SAV00015',
        'regular',
        125000.00,
        1000.00,
        4.00,
        '2024-02-10',
        NULL,
        1,
        NOW()
    );

-- ============================================
-- STEP 4: INSERT LOAN APPLICATIONS
-- ============================================

-- Get loan product IDs (assuming they exist)
SET
    @personal_loan_product_id = (
        SELECT id
        FROM loan_products
        WHERE
            product_name LIKE '%Personal%'
        LIMIT 1
    );

SET
    @business_loan_product_id = (
        SELECT id
        FROM loan_products
        WHERE
            product_name LIKE '%Business%'
        LIMIT 1
    );

SET
    @education_loan_product_id = (
        SELECT id
        FROM loan_products
        WHERE
            product_name LIKE '%Education%'
        LIMIT 1
    );

-- If no products exist, create default ones
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
        SELECT 'Business Loan', 'BL001', 'reducing_balance', 12.00, 18.00, 50000.00, 2000000.00, 12, 84, 2.50, 1, NOW()
    ) AS tmp
WHERE
    NOT EXISTS (
        SELECT 1
        FROM loan_products
        WHERE
            product_code = 'BL001'
    );

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
        SELECT 'Education Loan', 'EL001', 'reducing_balance', 8.00, 12.00, 25000.00, 1000000.00, 12, 120, 1.50, 1, NOW()
    ) AS tmp
WHERE
    NOT EXISTS (
        SELECT 1
        FROM loan_products
        WHERE
            product_code = 'EL001'
    );

-- Set product IDs
SET
    @personal_loan_product_id = (
        SELECT id
        FROM loan_products
        WHERE
            product_code = 'PL001'
    );

SET
    @business_loan_product_id = (
        SELECT id
        FROM loan_products
        WHERE
            product_code = 'BL001'
    );

SET
    @education_loan_product_id = (
        SELECT id
        FROM loan_products
        WHERE
            product_code = 'EL001'
    );

-- Loan applications (mix of approved, pending, and processing)
INSERT INTO
    loan_applications (
        application_number,
        member_id,
        loan_product_id,
        requested_amount,
        requested_tenure_months,
        purpose,
        monthly_income,
        existing_loans_emi,
        approved_amount,
        approved_tenure_months,
        approved_interest_rate,
        application_date,
        status,
        member_savings_balance,
        member_existing_loans,
        member_existing_loan_balance,
        created_at
    )
VALUES (
        'APP202401001',
        1,
        @personal_loan_product_id,
        100000.00,
        12,
        'Home renovation',
        75000.00,
        0.00,
        100000.00,
        12,
        12.00,
        '2024-01-15',
        'member_approved',
        50000.00,
        0,
        0.00,
        '2024-01-15 10:00:00'
    ),
    (
        'APP202401002',
        2,
        @personal_loan_product_id,
        150000.00,
        24,
        'Wedding expenses',
        85000.00,
        0.00,
        150000.00,
        24,
        11.50,
        '2024-02-01',
        'member_approved',
        75000.00,
        0,
        0.00,
        '2024-02-01 11:30:00'
    ),
    (
        'APP202401003',
        3,
        @education_loan_product_id,
        80000.00,
        24,
        'Child education',
        45000.00,
        0.00,
        80000.00,
        24,
        10.00,
        '2024-03-10',
        'member_approved',
        35000.00,
        0,
        0.00,
        '2024-03-10 09:15:00'
    ),
    (
        'APP202401004',
        4,
        @personal_loan_product_id,
        200000.00,
        36,
        'Medical emergency',
        120000.00,
        0.00,
        200000.00,
        36,
        11.00,
        '2024-04-05',
        'member_approved',
        120000.00,
        0,
        0.00,
        '2024-04-05 14:20:00'
    ),
    (
        'APP202401005',
        5,
        @personal_loan_product_id,
        120000.00,
        18,
        'Vehicle purchase',
        55000.00,
        0.00,
        120000.00,
        18,
        12.50,
        '2024-05-12',
        'member_approved',
        42000.00,
        0,
        0.00,
        '2024-05-12 16:45:00'
    ),
    (
        'APP202401006',
        6,
        @personal_loan_product_id,
        90000.00,
        12,
        'Debt consolidation',
        68000.00,
        5000.00,
        90000.00,
        12,
        13.00,
        '2024-06-20',
        'member_approved',
        38000.00,
        0,
        0.00,
        '2024-06-20 10:30:00'
    ),
    (
        'APP202401007',
        7,
        @business_loan_product_id,
        250000.00,
        36,
        'Business expansion',
        52000.00,
        0.00,
        250000.00,
        36,
        14.00,
        '2024-07-08',
        'member_approved',
        28000.00,
        0,
        0.00,
        '2024-07-08 13:00:00'
    ),
    -- Pending applications (not yet approved)
    (
        'APP202412001',
        9,
        @personal_loan_product_id,
        75000.00,
        12,
        'Personal needs',
        48000.00,
        0.00,
        NULL,
        NULL,
        NULL,
        '2024-12-15',
        'pending',
        15000.00,
        0,
        0.00,
        '2024-12-15 09:00:00'
    ),
    (
        'APP202412002',
        10,
        @education_loan_product_id,
        150000.00,
        48,
        'Masters degree',
        72000.00,
        0.00,
        NULL,
        NULL,
        NULL,
        '2024-12-20',
        'pending',
        25000.00,
        0,
        0.00,
        '2024-12-20 11:00:00'
    ),
    (
        'APP202412003',
        12,
        @personal_loan_product_id,
        50000.00,
        12,
        'Business startup',
        58000.00,
        0.00,
        50000.00,
        12,
        12.00,
        '2024-12-22',
        'member_review',
        32000.00,
        0,
        0.00,
        '2024-12-22 15:00:00'
    );

-- ============================================
-- STEP 5: INSERT ACTIVE LOANS WITH INSTALLMENTS
-- ============================================

-- Loan 1: Rajesh - 100K, 12 months (paid 6 EMIs on time)
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
        'LN2024010001',
        1,
        @personal_loan_product_id,
        1,
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
    );

SET @loan1_id = LAST_INSERT_ID();

-- Generate installments for Loan 1
CALL sp_generate_loan_installments (
    @loan1_id,
    100000.00,
    12.00,
    12,
    'reducing_balance',
    8884.88,
    '2024-02-20'
);

-- Mark first 6 installments as paid
UPDATE loan_installments
SET
    status = 'paid',
    principal_paid = principal_amount,
    interest_paid = interest_amount,
    paid_date = due_date
WHERE
    loan_id = @loan1_id
    AND installment_number <= 6;

-- Loan 2: Priya - 150K, 24 months (paid 10 EMIs, 1 overdue)
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
        'LN2024020001',
        2,
        @personal_loan_product_id,
        2,
        150000.00,
        11.50,
        'reducing_balance',
        24,
        7065.09,
        '2024-02-05',
        '2024-03-05',
        '2026-02-05',
        75000.00,
        3500.00,
        250.00,
        'overdue',
        1,
        '2024-02-05 11:00:00'
    );

SET @loan2_id = LAST_INSERT_ID();

CALL sp_generate_loan_installments (
    @loan2_id,
    150000.00,
    11.50,
    24,
    'reducing_balance',
    7065.09,
    '2024-03-05'
);

UPDATE loan_installments
SET
    status = 'paid',
    principal_paid = principal_amount,
    interest_paid = interest_amount,
    paid_date = due_date
WHERE
    loan_id = @loan2_id
    AND installment_number <= 10;

-- Mark installment 11 as overdue
UPDATE loan_installments
SET
    status = 'overdue',
    due_date = DATE_SUB(CURDATE(), INTERVAL 15 DAY)
WHERE
    loan_id = @loan2_id
    AND installment_number = 11;

-- Loan 3: Amit - 80K, 24 months (paid 8 EMIs regularly)
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
        'LN2024030001',
        3,
        @education_loan_product_id,
        3,
        80000.00,
        10.00,
        'reducing_balance',
        24,
        3695.78,
        '2024-03-15',
        '2024-04-15',
        '2026-03-15',
        50000.00,
        2000.00,
        0.00,
        'active',
        1,
        '2024-03-15 09:00:00'
    );

SET @loan3_id = LAST_INSERT_ID();

CALL sp_generate_loan_installments (
    @loan3_id,
    80000.00,
    10.00,
    24,
    'reducing_balance',
    3695.78,
    '2024-04-15'
);

UPDATE loan_installments
SET
    status = 'paid',
    principal_paid = principal_amount,
    interest_paid = interest_amount,
    paid_date = due_date
WHERE
    loan_id = @loan3_id
    AND installment_number <= 8;

-- Loan 4: Sneha - 200K, 36 months (just started, paid 2 EMIs)
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
        'LN2024040001',
        4,
        @personal_loan_product_id,
        4,
        200000.00,
        11.00,
        'reducing_balance',
        36,
        6545.35,
        '2024-11-01',
        '2024-12-01',
        '2027-11-01',
        190000.00,
        5000.00,
        0.00,
        'active',
        1,
        '2024-11-01 10:00:00'
    );

SET @loan4_id = LAST_INSERT_ID();

CALL sp_generate_loan_installments (
    @loan4_id,
    200000.00,
    11.00,
    36,
    'reducing_balance',
    6545.35,
    '2024-12-01'
);

UPDATE loan_installments
SET
    status = 'paid',
    principal_paid = principal_amount,
    interest_paid = interest_amount,
    paid_date = due_date
WHERE
    loan_id = @loan4_id
    AND installment_number <= 2;

-- Loan 5: Vikram - 120K, 18 months (paid 5 EMIs, 2 partial payments)
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
        'LN2024050001',
        5,
        @personal_loan_product_id,
        5,
        120000.00,
        12.50,
        'reducing_balance',
        18,
        7378.98,
        '2024-05-20',
        '2024-06-20',
        '2025-11-20',
        70000.00,
        3000.00,
        150.00,
        'active',
        1,
        '2024-05-20 10:00:00'
    );

SET @loan5_id = LAST_INSERT_ID();

CALL sp_generate_loan_installments (
    @loan5_id,
    120000.00,
    12.50,
    18,
    'reducing_balance',
    7378.98,
    '2024-06-20'
);

UPDATE loan_installments
SET
    status = 'paid',
    principal_paid = principal_amount,
    interest_paid = interest_amount,
    paid_date = due_date
WHERE
    loan_id = @loan5_id
    AND installment_number <= 5;

-- Partial payment on installment 6 and 7
UPDATE loan_installments
SET
    status = 'pending',
    principal_paid = principal_amount * 0.5,
    interest_paid = interest_amount * 0.5
WHERE
    loan_id = @loan5_id
    AND installment_number IN (6, 7);

-- Loan 6: Anjali - 90K, 12 months (paid irregularly, has fines)
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
        'LN2024060001',
        6,
        @personal_loan_product_id,
        6,
        90000.00,
        13.00,
        'reducing_balance',
        12,
        8019.12,
        '2024-07-01',
        '2024-08-01',
        '2025-07-01',
        45000.00,
        2500.00,
        500.00,
        'overdue',
        1,
        '2024-07-01 10:00:00'
    );

SET @loan6_id = LAST_INSERT_ID();

CALL sp_generate_loan_installments (
    @loan6_id,
    90000.00,
    13.00,
    12,
    'reducing_balance',
    8019.12,
    '2024-08-01'
);

UPDATE loan_installments
SET
    status = 'paid',
    principal_paid = principal_amount,
    interest_paid = interest_amount,
    paid_date = DATE_ADD(due_date, INTERVAL 10 DAY)
WHERE
    loan_id = @loan6_id
    AND installment_number <= 5;

UPDATE loan_installments
SET
    status = 'overdue',
    due_date = DATE_SUB(CURDATE(), INTERVAL 20 DAY)
WHERE
    loan_id = @loan6_id
    AND installment_number = 6;

-- Loan 7: Rahul - 250K, 36 months (business loan, just disbursed)
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
        'LN2024070001',
        7,
        @business_loan_product_id,
        7,
        250000.00,
        14.00,
        'reducing_balance',
        36,
        8538.95,
        '2024-12-01',
        '2025-01-01',
        '2027-12-01',
        250000.00,
        0.00,
        0.00,
        'active',
        1,
        '2024-12-01 10:00:00'
    );

SET @loan7_id = LAST_INSERT_ID();

CALL sp_generate_loan_installments (
    @loan7_id,
    250000.00,
    14.00,
    36,
    'reducing_balance',
    8538.95,
    '2025-01-01'
);

-- ============================================
-- STEP 6: INSERT LOAN PAYMENTS (Transaction History)
-- ============================================

INSERT INTO
    loan_payments (
        loan_id,
        payment_code,
        payment_date,
        total_amount,
        principal_component,
        interest_component,
        fine_component,
        excess_amount,
        payment_mode,
        payment_type,
        bank_transaction_id,
        outstanding_principal_after,
        outstanding_interest_after,
        remarks,
        created_by,
        created_at
    )
VALUES
    -- Loan 1 payments
    (
        @loan1_id,
        'PAY-20240220-ABC123',
        '2024-02-20',
        8884.88,
        7884.88,
        1000.00,
        0.00,
        0.00,
        'cash',
        'regular',
        NULL,
        92115.12,
        0.00,
        'EMI 1',
        1,
        '2024-02-20 10:00:00'
    ),
    (
        @loan1_id,
        'PAY-20240320-ABC124',
        '2024-03-20',
        8884.88,
        7963.95,
        920.93,
        0.00,
        0.00,
        'bank_transfer',
        'regular',
        NULL,
        84151.17,
        0.00,
        'EMI 2',
        1,
        '2024-03-20 10:00:00'
    ),
    (
        @loan1_id,
        'PAY-20240420-ABC125',
        '2024-04-20',
        8884.88,
        8043.80,
        841.08,
        0.00,
        0.00,
        'bank_transfer',
        'regular',
        NULL,
        76107.37,
        0.00,
        'EMI 3',
        1,
        '2024-04-20 10:00:00'
    ),
    (
        @loan1_id,
        'PAY-20240520-ABC126',
        '2024-05-20',
        8884.88,
        8124.44,
        760.44,
        0.00,
        0.00,
        'bank_transfer',
        'regular',
        NULL,
        67982.93,
        0.00,
        'EMI 4',
        1,
        '2024-05-20 10:00:00'
    ),
    (
        @loan1_id,
        'PAY-20240620-ABC127',
        '2024-06-20',
        8884.88,
        8205.89,
        678.99,
        0.00,
        0.00,
        'bank_transfer',
        'regular',
        NULL,
        59777.04,
        0.00,
        'EMI 5',
        1,
        '2024-06-20 10:00:00'
    ),
    (
        @loan1_id,
        'PAY-20240720-ABC128',
        '2024-07-20',
        8884.88,
        8288.15,
        596.73,
        0.00,
        0.00,
        'bank_transfer',
        'regular',
        NULL,
        51488.89,
        0.00,
        'EMI 6',
        1,
        '2024-07-20 10:00:00'
    ),

-- Loan 2 payments (with one late payment)
(
    @loan2_id,
    'PAY-20240305-DEF123',
    '2024-03-05',
    7065.09,
    5627.59,
    1437.50,
    0.00,
    0.00,
    'bank_transfer',
    'regular',
    NULL,
    144372.41,
    0.00,
    'EMI 1',
    1,
    '2024-03-05 10:00:00'
),
(
    @loan2_id,
    'PAY-20240415-DEF124',
    '2024-04-15',
    7065.09,
    5681.57,
    1383.52,
    0.00,
    0.00,
    'bank_transfer',
    'regular',
    NULL,
    138690.84,
    0.00,
    'EMI 2 (10 days late)',
    1,
    '2024-04-15 10:00:00'
),

-- Loan 5 partial payments
(
    @loan5_id,
    'PAY-20240620-GHI123',
    '2024-06-20',
    7378.98,
    6128.98,
    1250.00,
    0.00,
    0.00,
    'bank_transfer',
    'regular',
    NULL,
    113871.02,
    0.00,
    'EMI 1',
    1,
    '2024-06-20 10:00:00'
),
(
    @loan5_id,
    'PAY-20240920-GHI124',
    '2024-09-20',
    3689.49,
    3200.00,
    489.49,
    0.00,
    0.00,
    'bank_transfer',
    'partial',
    NULL,
    110671.02,
    0.00,
    'Partial payment EMI 4',
    1,
    '2024-09-20 10:00:00'
);

-- ============================================
-- STEP 7: INSERT FINES
-- ============================================

-- Fine for Loan 2 (overdue installment)
INSERT INTO
    fines (
        member_id,
        fine_code,
        fine_type,
        related_type,
        related_id,
        fine_rule_id,
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
        2,
        'FIN-20241220-001',
        'loan_late',
        'loan_installment',
        (
            SELECT id
            FROM loan_installments
            WHERE
                loan_id = @loan2_id
                AND installment_number = 11
        ),
        1,
        '2024-12-20',
        (
            SELECT due_date
            FROM loan_installments
            WHERE
                loan_id = @loan2_id
                AND installment_number = 11
        ),
        15,
        250.00,
        0.00,
        250.00,
        'pending',
        'Late payment fine for LN2024020001 EMI #11',
        1,
        '2024-12-20 10:00:00'
    );

-- Fines for Loan 6 (multiple late payments)
INSERT INTO
    fines (
        member_id,
        fine_code,
        fine_type,
        related_type,
        related_id,
        fine_rule_id,
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
        6,
        'FIN-20241015-002',
        'loan_late',
        'loan_installment',
        (
            SELECT id
            FROM loan_installments
            WHERE
                loan_id = @loan6_id
                AND installment_number = 3
        ),
        1,
        '2024-10-15',
        '2024-10-01',
        14,
        200.00,
        200.00,
        0.00,
        'paid',
        'Late payment fine paid',
        1,
        '2024-10-15 10:00:00'
    ),
    (
        6,
        'FIN-20241225-003',
        'loan_late',
        'loan_installment',
        (
            SELECT id
            FROM loan_installments
            WHERE
                loan_id = @loan6_id
                AND installment_number = 6
        ),
        1,
        CURDATE(),
        DATE_SUB(CURDATE(), INTERVAL 20 DAY),
        20,
        300.00,
        0.00,
        300.00,
        'pending',
        'Late payment fine for LN2024060001 EMI #6',
        1,
        NOW()
    );

-- ============================================
-- STEP 8: INSERT GUARANTORS
-- ============================================

INSERT INTO
    loan_guarantors (
        loan_application_id,
        loan_id,
        guarantor_member_id,
        guarantee_amount,
        relationship,
        consent_status,
        consent_date,
        created_at
    )
VALUES (
        1,
        @loan1_id,
        11,
        100000.00,
        'Friend',
        'approved',
        '2024-01-18 10:00:00',
        '2024-01-15 10:00:00'
    ),
    (
        2,
        @loan2_id,
        13,
        150000.00,
        'Colleague',
        'approved',
        '2024-01-30 10:00:00',
        '2024-02-01 10:00:00'
    ),
    (
        4,
        @loan4_id,
        14,
        200000.00,
        'Family',
        'approved',
        '2024-10-30 10:00:00',
        '2024-11-01 10:00:00'
    ),
    (
        7,
        @loan7_id,
        11,
        125000.00,
        'Business Partner',
        'approved',
        '2024-11-28 10:00:00',
        '2024-12-01 10:00:00'
    ),
    (
        7,
        @loan7_id,
        15,
        125000.00,
        'Business Partner',
        'approved',
        '2024-11-28 10:00:00',
        '2024-12-01 10:00:00'
    );

-- ============================================
-- STEP 9: CREATE BANK ACCOUNT
-- ============================================

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
        SELECT 'HDFC Bank', 'Mumbai Main Branch', '50100012345678', 'current', 'HDFC0000123', 1000000.00, 1250000.00, 1, NOW()
    ) AS tmp
WHERE
    NOT EXISTS (
        SELECT 1
        FROM bank_accounts
        WHERE
            account_number = '50100012345678'
    );

SET
    @bank_account_id = (
        SELECT id
        FROM bank_accounts
        WHERE
            account_number = '50100012345678'
    );

-- ============================================
-- STEP 10: INSERT BANK STATEMENT IMPORT
-- ============================================

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
        'IMP-20250106-001',
        @bank_account_id,
        'bank_statement_jan2025.xlsx',
        '/uploads/bank_statements/bank_statement_jan2025.xlsx',
        'xlsx',
        'completed',
        20,
        12,
        8,
        1,
        NOW(),
        NOW(),
        NOW()
    );

SET @import_id = LAST_INSERT_ID();

-- ============================================
-- STEP 11: INSERT BANK TRANSACTIONS (For Mapping Testing)
-- ============================================

INSERT INTO
    bank_transactions (
        import_id,
        bank_account_id,
        transaction_date,
        transaction_type,
        amount,
        utr_number,
        description,
        paid_by_member_id,
        paid_for_member_id,
        transaction_category,
        related_type,
        related_id,
        mapping_status,
        detected_member_id,
        remarks,
        created_at
    )
VALUES
    -- Mapped transactions (already linked to payments)
    (
        @import_id,
        @bank_account_id,
        '2024-02-20',
        'credit',
        8884.88,
        'UTR1234567890',
        'IMPS-MEMB000001-Rajesh Kumar-Loan Payment',
        1,
        1,
        'emi',
        'loan',
        @loan1_id,
        'mapped',
        1,
        'Auto-matched',
        '2024-02-20 10:00:00'
    ),
    (
        @import_id,
        @bank_account_id,
        '2024-03-20',
        'credit',
        8884.88,
        'UTR1234567891',
        'NEFT-919876543210-EMI Payment',
        1,
        1,
        'emi',
        'loan',
        @loan1_id,
        'mapped',
        1,
        'Auto-matched by phone',
        '2024-03-20 10:00:00'
    ),
    (
        @import_id,
        @bank_account_id,
        '2024-04-20',
        'credit',
        8884.88,
        'UTR1234567892',
        'RTGS-LN2024010001-Monthly EMI',
        1,
        1,
        'emi',
        'loan',
        @loan1_id,
        'mapped',
        1,
        'Auto-matched by loan number',
        '2024-04-20 10:00:00'
    ),
    (
        @import_id,
        @bank_account_id,
        '2024-03-05',
        'credit',
        7065.09,
        'UTR2234567890',
        'IMPS-919876543211-Priya Sharma',
        2,
        2,
        'emi',
        'loan',
        @loan2_id,
        'mapped',
        2,
        'Auto-matched by phone',
        '2024-03-05 10:00:00'
    ),
    (
        @import_id,
        @bank_account_id,
        '2024-06-20',
        'credit',
        7378.98,
        'UTR3234567890',
        'Transfer from MEMB000005 Vikram Singh',
        5,
        5,
        'emi',
        'loan',
        @loan5_id,
        'mapped',
        5,
        'Auto-matched',
        '2024-06-20 10:00:00'
    ),

-- UNMAPPED transactions (Need manual mapping) - THESE ARE FOR YOU TO TEST
(
    @import_id,
    @bank_account_id,
    CURDATE(),
    'credit',
    8884.88,
    'UTR1234567899',
    'NEFT-Rajesh K-Payment',
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    'unmapped',
    1,
    NULL,
    NOW()
),
(
    @import_id,
    @bank_account_id,
    CURDATE(),
    'credit',
    7065.09,
    'UTR2234567899',
    'IMPS-9876543211-EMI',
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    'unmapped',
    2,
    NULL,
    NOW()
),
(
    @import_id,
    @bank_account_id,
    CURDATE(),
    'credit',
    3695.78,
    'UTR3234567899',
    'UPI-Amit@upi-Loan EMI',
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    'unmapped',
    3,
    NULL,
    NOW()
),
(
    @import_id,
    @bank_account_id,
    CURDATE(),
    'credit',
    6545.35,
    'UTR4234567899',
    'Transfer-Sneha Reddy-LN2024040001',
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    'unmapped',
    4,
    NULL,
    NOW()
),
(
    @import_id,
    @bank_account_id,
    CURDATE(),
    'credit',
    5000.00,
    'UTR5234567899',
    'IMPS-Unknown Sender',
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    'unmapped',
    NULL,
    'No match found',
    NOW()
),

-- Savings deposits (unmapped)
(
    @import_id,
    @bank_account_id,
    CURDATE(),
    'credit',
    5000.00,
    'UTR6234567899',
    'Savings-MEMB000009-Suresh Nair',
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    'unmapped',
    9,
    NULL,
    NOW()
),
(
    @import_id,
    @bank_account_id,
    CURDATE(),
    'credit',
    10000.00,
    'UTR7234567899',
    'FD-919876543219-Deepa Iyer',
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    'unmapped',
    10,
    NULL,
    NOW()
),

-- Fine payments (unmapped)
(
    @import_id,
    @bank_account_id,
    CURDATE(),
    'credit',
    250.00,
    'UTR8234567899',
    'Fine-MEMB000002-Late Payment',
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    'unmapped',
    2,
    NULL,
    NOW()
),
(
    @import_id,
    @bank_account_id,
    CURDATE(),
    'credit',
    300.00,
    'UTR9234567899',
    'Penalty-Anjali Mehta-919876543215',
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    'unmapped',
    6,
    NULL,
    NOW()
),

-- Overpayment (unmapped - test excess handling)
(
    @import_id,
    @bank_account_id,
    CURDATE(),
    'credit',
    10000.00,
    'UTR0234567899',
    'NEFT-LN2024010001-Advance Payment',
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    'unmapped',
    1,
    'Overpayment',
    NOW()
),

-- Mixed payment (for split testing)
(
    @import_id,
    @bank_account_id,
    CURDATE(),
    'credit',
    15000.00,
    'UTR1134567899',
    'Combined-MEMB000005-Multiple EMIs',
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    'unmapped',
    5,
    'Split required',
    NOW()
),

-- Duplicate UTR (should be rejected)
-- (@import_id, @bank_account_id, CURDATE(), 'credit', 8884.88, 'UTR1234567890', 'DUPLICATE-Should Fail', NULL, NULL, NULL, NULL, NULL, 'unmapped', NULL, NULL, NOW()),

-- Withdrawals/debits
(
    @import_id,
    @bank_account_id,
    CURDATE(),
    'debit',
    50000.00,
    'UTR9999567890',
    'Loan Disbursement-LN2024070001',
    NULL,
    NULL,
    'disbursement',
    NULL,
    NULL,
    'mapped',
    NULL,
    'Auto-processed',
    NOW()
),
(
    @import_id,
    @bank_account_id,
    CURDATE(),
    'debit',
    15000.00,
    'UTR9999567891',
    'Withdrawal-Staff Salary',
    NULL,
    NULL,
    'expense',
    NULL,
    NULL,
    'mapped',
    NULL,
    NULL,
    NOW()
),
(
    @import_id,
    @bank_account_id,
    CURDATE(),
    'debit',
    25000.00,
    'UTR9999567892',
    'Transfer-Operating Expenses',
    NULL,
    NULL,
    'expense',
    NULL,
    NULL,
    'mapped',
    NULL,
    NULL,
    NOW()
);

-- ============================================
-- STEP 12: INSERT GENERAL LEDGER ENTRIES
-- ============================================

-- Loan disbursement entries
INSERT INTO
    general_ledger (
        voucher_number,
        voucher_type,
        transaction_date,
        financial_year_id,
        debit_account_id,
        credit_account_id,
        debit_amount,
        credit_amount,
        member_id,
        reference_type,
        reference_id,
        narration,
        created_by,
        created_at
    )
VALUES (
        'JV202401001',
        'payment',
        '2024-01-20',
        1,
        1,
        2,
        100000.00,
        100000.00,
        1,
        'loan_disbursement',
        @loan1_id,
        'Loan disbursement - LN2024010001',
        1,
        '2024-01-20 10:00:00'
    ),
    (
        'JV202402001',
        'payment',
        '2024-02-05',
        1,
        1,
        2,
        150000.00,
        150000.00,
        2,
        'loan_disbursement',
        @loan2_id,
        'Loan disbursement - LN2024020001',
        1,
        '2024-02-05 10:00:00'
    ),
    (
        'JV202403001',
        'payment',
        '2024-03-15',
        1,
        1,
        2,
        80000.00,
        80000.00,
        3,
        'loan_disbursement',
        @loan3_id,
        'Loan disbursement - LN2024030001',
        1,
        '2024-03-15 10:00:00'
    ),
    (
        'JV202411001',
        'payment',
        '2024-11-01',
        1,
        1,
        2,
        200000.00,
        200000.00,
        4,
        'loan_disbursement',
        @loan4_id,
        'Loan disbursement - LN2024040001',
        1,
        '2024-11-01 10:00:00'
    ),
    (
        'JV202405001',
        'payment',
        '2024-05-20',
        1,
        1,
        2,
        120000.00,
        120000.00,
        5,
        'loan_disbursement',
        @loan5_id,
        'Loan disbursement - LN2024050001',
        1,
        '2024-05-20 10:00:00'
    ),
    (
        'JV202407001',
        'payment',
        '2024-07-01',
        1,
        1,
        2,
        90000.00,
        90000.00,
        6,
        'loan_disbursement',
        @loan6_id,
        'Loan disbursement - LN2024060001',
        1,
        '2024-07-01 10:00:00'
    ),
    (
        'JV202412001',
        'payment',
        '2024-12-01',
        1,
        1,
        2,
        250000.00,
        250000.00,
        7,
        'loan_disbursement',
        @loan7_id,
        'Loan disbursement - LN2024070001',
        1,
        '2024-12-01 10:00:00'
    );

-- Loan payment entries (sample)
INSERT INTO
    general_ledger (
        voucher_number,
        voucher_type,
        transaction_date,
        financial_year_id,
        debit_account_id,
        credit_account_id,
        debit_amount,
        credit_amount,
        member_id,
        reference_type,
        reference_id,
        narration,
        created_by,
        created_at
    )
VALUES (
        'RC202402001',
        'receipt',
        '2024-02-20',
        1,
        2,
        1,
        8884.88,
        8884.88,
        1,
        'loan_payment',
        1,
        'Loan payment received - LN2024010001 EMI #1',
        1,
        '2024-02-20 10:00:00'
    ),
    (
        'RC202403001',
        'receipt',
        '2024-03-05',
        1,
        2,
        1,
        7065.09,
        7065.09,
        2,
        'loan_payment',
        2,
        'Loan payment received - LN2024020001 EMI #1',
        1,
        '2024-03-05 10:00:00'
    );

-- ============================================
-- COMMIT TRANSACTION
-- ============================================

COMMIT;

-- ============================================
-- VERIFICATION QUERIES
-- ============================================

SELECT '=== DATA INSERTION SUMMARY ===' AS status;

SELECT 'Members' AS table_name, COUNT(*) AS count
FROM members
WHERE
    member_code LIKE 'MEMB%'
UNION ALL
SELECT 'Savings Accounts', COUNT(*)
FROM savings_accounts
UNION ALL
SELECT 'Loan Applications', COUNT(*)
FROM loan_applications
UNION ALL
SELECT 'Active Loans', COUNT(*)
FROM loans
UNION ALL
SELECT 'Loan Installments', COUNT(*)
FROM loan_installments
UNION ALL
SELECT 'Loan Payments', COUNT(*)
FROM loan_payments
UNION ALL
SELECT 'Fines', COUNT(*)
FROM fines
UNION ALL
SELECT 'Guarantors', COUNT(*)
FROM loan_guarantors
UNION ALL
SELECT 'Bank Transactions', COUNT(*)
FROM bank_transactions
WHERE
    import_id = @import_id
UNION ALL
SELECT 'Unmapped Transactions', COUNT(*)
FROM bank_transactions
WHERE
    mapping_status = 'unmapped'
    AND import_id = @import_id
UNION ALL
SELECT 'General Ledger', COUNT(*)
FROM general_ledger;

-- Show loans summary
SELECT
    l.loan_number,
    CONCAT(
        m.first_name,
        ' ',
        m.last_name
    ) AS member_name,
    l.principal_amount,
    l.tenure_months,
    l.emi_amount,
    l.outstanding_principal,
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
ORDER BY l.id;

-- Show unmapped transactions for testing
SELECT bt.id, bt.transaction_date, bt.amount, bt.utr_number, bt.description, CONCAT(
        m.first_name, ' ', m.last_name
    ) AS detected_member, bt.mapping_status
FROM
    bank_transactions bt
    LEFT JOIN members m ON m.id = bt.detected_member_id
WHERE
    bt.mapping_status = 'unmapped'
ORDER BY bt.transaction_date DESC;

SELECT '=== TEST DATA READY ===' AS status;

SELECT 'You can now test all screens and features!' AS message;

SELECT 'Excel file for bank transactions will be created separately.' AS note;