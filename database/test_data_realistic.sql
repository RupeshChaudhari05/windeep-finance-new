-- =====================================================
-- WINDEEP FINANCE - REALISTIC TEST DATA
-- Purpose: Create comprehensive test scenarios
-- Coverage: All edge cases for EMI, fines, payments
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- CLEANUP EXISTING TEST DATA
-- =====================================================

DELETE FROM transaction_mappings
WHERE
    bank_transaction_id IN (
        SELECT id
        FROM bank_transactions
        WHERE
            description LIKE '%TEST%'
    );

DELETE FROM bank_transactions WHERE description LIKE '%TEST%';

DELETE FROM loan_payments
WHERE
    loan_id IN (
        SELECT id
        FROM loans
        WHERE
            loan_number LIKE 'TEST%'
    );

DELETE FROM loan_installments
WHERE
    loan_id IN (
        SELECT id
        FROM loans
        WHERE
            loan_number LIKE 'TEST%'
    );

DELETE FROM loan_guarantors
WHERE
    loan_id IN (
        SELECT id
        FROM loans
        WHERE
            loan_number LIKE 'TEST%'
    );

DELETE FROM loans WHERE loan_number LIKE 'TEST%';

DELETE FROM loan_guarantors
WHERE
    loan_application_id IN (
        SELECT id
        FROM loan_applications
        WHERE
            application_number LIKE 'TEST%'
    );

DELETE FROM loan_applications WHERE application_number LIKE 'TEST%';

DELETE FROM savings_transactions
WHERE
    savings_account_id IN (
        SELECT id
        FROM savings_accounts
        WHERE
            account_number LIKE 'TEST%'
    );

DELETE FROM savings_schedule
WHERE
    savings_account_id IN (
        SELECT id
        FROM savings_accounts
        WHERE
            account_number LIKE 'TEST%'
    );

DELETE FROM savings_accounts WHERE account_number LIKE 'TEST%';

DELETE FROM fines
WHERE
    member_id IN (
        SELECT id
        FROM members
        WHERE
            member_code LIKE 'TEST%'
    );

DELETE FROM member_ledger
WHERE
    member_id IN (
        SELECT id
        FROM members
        WHERE
            member_code LIKE 'TEST%'
    );

DELETE FROM members WHERE member_code LIKE 'TEST%';

-- =====================================================
-- TEST MEMBERS (10 MEMBERS)
-- =====================================================

INSERT INTO
    members (
        member_code,
        first_name,
        last_name,
        father_name,
        date_of_birth,
        gender,
        email,
        phone,
        alternate_phone,
        address_line1,
        city,
        state,
        pincode,
        aadhaar_number,
        pan_number,
        bank_name,
        bank_branch,
        account_number,
        ifsc_code,
        account_holder_name,
        join_date,
        membership_type,
        opening_balance,
        opening_balance_type,
        status,
        password,
        created_at
    )
VALUES
    -- Member 1: Perfect credit, always on time
    (
        'TEST-001',
        'Rajesh',
        'Kumar',
        'Ram Kumar',
        '1985-03-15',
        'male',
        'rajesh.test@example.com',
        '9876543001',
        '9876543101',
        '123 MG Road',
        'Mumbai',
        'Maharashtra',
        '400001',
        '123456789001',
        'ABCDE1234A',
        'HDFC Bank',
        'Andheri Branch',
        '1234567890001',
        'HDFC0001234',
        'Rajesh Kumar',
        '2024-01-15',
        'premium',
        50000.00,
        'credit',
        'active',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        NOW()
    ),

-- Member 2: Good savings, needs larger loan
(
    'TEST-002',
    'Priya',
    'Sharma',
    'Suresh Sharma',
    '1990-06-20',
    'female',
    'priya.test@example.com',
    '9876543002',
    '9876543102',
    '456 Park Street',
    'Delhi',
    'Delhi',
    '110001',
    '123456789002',
    'ABCDE1234B',
    'ICICI Bank',
    'Connaught Place',
    '1234567890002',
    'ICIC0001234',
    'Priya Sharma',
    '2024-02-01',
    'regular',
    35000.00,
    'credit',
    'active',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    NOW()
),

-- Member 3: Late payer (for fine testing)
(
    'TEST-003',
    'Amit',
    'Patel',
    'Ramesh Patel',
    '1988-09-10',
    'male',
    'amit.test@example.com',
    '9876543003',
    NULL,
    '789 Ring Road',
    'Ahmedabad',
    'Gujarat',
    '380001',
    '123456789003',
    'ABCDE1234C',
    'SBI',
    'Ashram Road',
    '1234567890003',
    'SBIN0001234',
    'Amit Patel',
    '2024-03-01',
    'regular',
    25000.00,
    'credit',
    'active',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    NOW()
),

-- Member 4: Partial payment scenario
(
    'TEST-004',
    'Sunita',
    'Reddy',
    'Krishna Reddy',
    '1992-12-05',
    'female',
    'sunita.test@example.com',
    '9876543004',
    '9876543104',
    '321 Banjara Hills',
    'Hyderabad',
    'Telangana',
    '500034',
    '123456789004',
    'ABCDE1234D',
    'Axis Bank',
    'Jubilee Hills',
    '1234567890004',
    'UTIB0001234',
    'Sunita Reddy',
    '2024-01-20',
    'regular',
    20000.00,
    'credit',
    'active',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    NOW()
),

-- Member 5: Interest-only payment test
(
    'TEST-005',
    'Vikram',
    'Singh',
    'Balwant Singh',
    '1987-04-25',
    'male',
    'vikram.test@example.com',
    '9876543005',
    NULL,
    '654 Mall Road',
    'Shimla',
    'Himachal Pradesh',
    '171001',
    '123456789005',
    'ABCDE1234E',
    'PNB',
    'Mall Road',
    '1234567890005',
    'PUNB0001234',
    'Vikram Singh',
    '2024-02-10',
    'regular',
    15000.00,
    'credit',
    'active',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    NOW()
),

-- Member 6: Skip EMI scenario
(
    'TEST-006',
    'Meera',
    'Nair',
    'Gopalan Nair',
    '1991-07-30',
    'female',
    'meera.test@example.com',
    '9876543006',
    '9876543106',
    '987 Marine Drive',
    'Kochi',
    'Kerala',
    '682001',
    '123456789006',
    'ABCDE1234F',
    'Canara Bank',
    'MG Road',
    '1234567890006',
    'CNRB0001234',
    'Meera Nair',
    '2024-03-05',
    'regular',
    18000.00,
    'credit',
    'active',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    NOW()
),

-- Member 7: Advance payment scenario
(
    'TEST-007',
    'Ravi',
    'Desai',
    'Jayant Desai',
    '1989-11-12',
    'male',
    'ravi.test@example.com',
    '9876543007',
    NULL,
    '111 FC Road',
    'Pune',
    'Maharashtra',
    '411004',
    '123456789007',
    'ABCDE1234G',
    'BOB',
    'Shivaji Nagar',
    '1234567890007',
    'BARB0001234',
    'Ravi Desai',
    '2024-01-25',
    'regular',
    30000.00,
    'credit',
    'active',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    NOW()
),

-- Member 8: Overpayment scenario
(
    'TEST-008',
    'Kavita',
    'Joshi',
    'Prakash Joshi',
    '1993-02-18',
    'female',
    'kavita.test@example.com',
    '9876543008',
    '9876543108',
    '222 Civil Lines',
    'Jaipur',
    'Rajasthan',
    '302001',
    '123456789008',
    'ABCDE1234H',
    'Union Bank',
    'MI Road',
    '1234567890008',
    'UBIN0001234',
    'Kavita Joshi',
    '2024-02-15',
    'regular',
    22000.00,
    'credit',
    'active',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    NOW()
),

-- Member 9: Guarantor (no loan)
(
    'TEST-009',
    'Suresh',
    'Iyer',
    'Venkat Iyer',
    '1986-08-22',
    'male',
    'suresh.test@example.com',
    '9876543009',
    NULL,
    '333 Anna Salai',
    'Chennai',
    'Tamil Nadu',
    '600002',
    '123456789009',
    'ABCDE1234I',
    'IOB',
    'T Nagar',
    '1234567890009',
    'IOBA0001234',
    'Suresh Iyer',
    '2024-01-10',
    'regular',
    40000.00,
    'credit',
    'active',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    NOW()
),

-- Member 10: Guarantor (no loan)
(
    'TEST-010',
    'Anjali',
    'Verma',
    'Rajendra Verma',
    '1994-05-28',
    'female',
    'anjali.test@example.com',
    '9876543010',
    '9876543110',
    '444 Station Road',
    'Lucknow',
    'Uttar Pradesh',
    '226001',
    '123456789010',
    'ABCDE1234J',
    'Indian Bank',
    'Hazratganj',
    '1234567890010',
    'IDIB0001234',
    'Anjali Verma',
    '2024-01-12',
    'regular',
    28000.00,
    'credit',
    'active',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    NOW()
);

-- =====================================================
-- SAVINGS ACCOUNTS
-- =====================================================

INSERT INTO
    savings_accounts (
        account_number,
        member_id,
        scheme_id,
        monthly_amount,
        start_date,
        total_deposited,
        current_balance,
        status,
        created_at
    )
SELECT CONCAT('TEST-SAV-', LPAD(id, 3, '0')), id, 1, -- Default savings scheme
    1000.00, DATE_SUB(NOW(), INTERVAL 6 MONTH), 6000.00, 6000.00, 'active', NOW()
FROM members
WHERE
    member_code LIKE 'TEST%';

-- =====================================================
-- LOAN SCENARIOS
-- =====================================================

-- SCENARIO 1: Short-term loan, on-time payments (Member 1)
-- Loan: ₹50,000 @ 12% reducing for 6 months
INSERT INTO
    loans (
        loan_number,
        loan_application_id,
        member_id,
        loan_product_id,
        principal_amount,
        interest_rate,
        interest_type,
        tenure_months,
        emi_amount,
        total_interest,
        total_payable,
        processing_fee,
        net_disbursement,
        outstanding_principal,
        outstanding_interest,
        disbursement_date,
        first_emi_date,
        last_emi_date,
        status,
        disbursement_mode,
        created_at
    )
VALUES (
        'TEST-LOAN-001',
        NULL, -- Simulating direct disbursement
        (
            SELECT id
            FROM members
            WHERE
                member_code = 'TEST-001'
        ),
        1, -- Personal Loan product
        50000.00,
        12.00,
        'reducing',
        6,
        8606.64, -- Calculated EMI
        1639.84,
        51639.84,
        500.00,
        49500.00,
        50000.00,
        1639.84,
        DATE_SUB(NOW(), INTERVAL 5 MONTH),
        DATE_SUB(NOW(), INTERVAL 4 MONTH),
        DATE_ADD(NOW(), INTERVAL 2 MONTH),
        'active',
        'bank_transfer',
        DATE_SUB(NOW(), INTERVAL 5 MONTH)
    );

-- Generate installment schedule for LOAN-001
SET @loan1_id = LAST_INSERT_ID();

SET @balance = 50000.00;

SET @monthly_rate = 0.01;
-- 1% per month
SET @emi = 8606.64;

INSERT INTO
    loan_installments (
        loan_id,
        installment_number,
        due_date,
        principal_amount,
        interest_amount,
        emi_amount,
        outstanding_principal_before,
        outstanding_principal_after,
        status,
        paid_date,
        principal_paid,
        interest_paid,
        total_paid,
        created_at
    )
VALUES
    -- Month 1: PAID
    (
        @loan1_id,
        1,
        DATE_SUB(NOW(), INTERVAL 4 MONTH),
        8106.64,
        500.00,
        8606.64,
        50000.00,
        41893.36,
        'paid',
        DATE_SUB(NOW(), INTERVAL 4 MONTH),
        8106.64,
        500.00,
        8606.64,
        NOW()
    ),
    -- Month 2: PAID
    (
        @loan1_id,
        2,
        DATE_SUB(NOW(), INTERVAL 3 MONTH),
        8187.70,
        418.94,
        8606.64,
        41893.36,
        33705.66,
        'paid',
        DATE_SUB(NOW(), INTERVAL 3 MONTH),
        8187.70,
        418.94,
        8606.64,
        NOW()
    ),
    -- Month 3: PAID
    (
        @loan1_id,
        3,
        DATE_SUB(NOW(), INTERVAL 2 MONTH),
        8269.58,
        337.06,
        8606.64,
        33705.66,
        25436.08,
        'paid',
        DATE_SUB(NOW(), INTERVAL 2 MONTH),
        8269.58,
        337.06,
        8606.64,
        NOW()
    ),
    -- Month 4: PAID
    (
        @loan1_id,
        4,
        DATE_SUB(NOW(), INTERVAL 1 MONTH),
        8352.28,
        254.36,
        8606.64,
        25436.08,
        17083.80,
        'paid',
        DATE_SUB(NOW(), INTERVAL 1 MONTH),
        8352.28,
        254.36,
        8606.64,
        NOW()
    ),
    -- Month 5: PENDING (current month)
    (
        @loan1_id,
        5,
        NOW(),
        8435.80,
        170.84,
        8606.64,
        17083.80,
        8648.00,
        'pending',
        NULL,
        0.00,
        0.00,
        0.00,
        NOW()
    ),
    -- Month 6: UPCOMING
    (
        @loan1_id,
        6,
        DATE_ADD(NOW(), INTERVAL 1 MONTH),
        8648.00,
        86.48,
        8734.48,
        8648.00,
        0.00,
        'upcoming',
        NULL,
        0.00,
        0.00,
        0.00,
        NOW()
    );

-- SCENARIO 2: Long-term loan with late payment (Member 2)
-- Loan: ₹100,000 @ 12% reducing for 12 months
INSERT INTO
    loans (
        loan_number,
        member_id,
        loan_product_id,
        principal_amount,
        interest_rate,
        interest_type,
        tenure_months,
        emi_amount,
        total_interest,
        total_payable,
        processing_fee,
        net_disbursement,
        outstanding_principal,
        outstanding_interest,
        disbursement_date,
        first_emi_date,
        last_emi_date,
        status,
        disbursement_mode,
        created_at
    )
VALUES (
        'TEST-LOAN-002',
        (
            SELECT id
            FROM members
            WHERE
                member_code = 'TEST-002'
        ),
        1,
        100000.00,
        12.00,
        'reducing',
        12,
        8884.88,
        6618.56,
        106618.56,
        1000.00,
        99000.00,
        100000.00,
        6618.56,
        DATE_SUB(NOW(), INTERVAL 3 MONTH),
        DATE_SUB(NOW(), INTERVAL 2 MONTH),
        DATE_ADD(NOW(), INTERVAL 10 MONTH),
        'active',
        'bank_transfer',
        DATE_SUB(NOW(), INTERVAL 3 MONTH)
    );

SET @loan2_id = LAST_INSERT_ID();

INSERT INTO
    loan_installments (
        loan_id,
        installment_number,
        due_date,
        principal_amount,
        interest_amount,
        emi_amount,
        outstanding_principal_before,
        outstanding_principal_after,
        status,
        paid_date,
        principal_paid,
        interest_paid,
        fine_amount,
        fine_paid,
        is_late,
        days_late,
        created_at
    )
VALUES
    -- Month 1: PAID (on time)
    (
        @loan2_id,
        1,
        DATE_SUB(NOW(), INTERVAL 2 MONTH),
        7884.88,
        1000.00,
        8884.88,
        100000.00,
        92115.12,
        'paid',
        DATE_SUB(NOW(), INTERVAL 2 MONTH),
        7884.88,
        1000.00,
        0.00,
        0.00,
        0,
        0,
        NOW()
    ),
    -- Month 2: PAID (15 days late - should have fine)
    (
        @loan2_id,
        2,
        DATE_SUB(NOW(), INTERVAL 1 MONTH),
        7963.73,
        921.15,
        8884.88,
        92115.12,
        84151.39,
        'paid',
        DATE_SUB(NOW(), INTERVAL 1 MONTH) + INTERVAL 15 DAY,
        7963.73,
        921.15,
        150.00,
        150.00,
        1,
        15,
        NOW()
    ),
    -- Month 3: PENDING (overdue by 5 days)
    (
        @loan2_id,
        3,
        DATE_SUB(NOW(), INTERVAL 5 DAY),
        8043.36,
        841.52,
        8884.88,
        84151.39,
        76108.03,
        'overdue',
        NULL,
        0.00,
        0.00,
        50.00,
        0.00,
        1,
        5,
        NOW()
    );

-- SCENARIO 3: Flat interest loan (Member 3)
-- Loan: ₹30,000 @ 12% flat for 6 months
INSERT INTO
    loans (
        loan_number,
        member_id,
        loan_product_id,
        principal_amount,
        interest_rate,
        interest_type,
        tenure_months,
        emi_amount,
        total_interest,
        total_payable,
        processing_fee,
        net_disbursement,
        outstanding_principal,
        outstanding_interest,
        disbursement_date,
        first_emi_date,
        last_emi_date,
        status,
        disbursement_mode,
        created_at
    )
VALUES (
        'TEST-LOAN-003',
        (
            SELECT id
            FROM members
            WHERE
                member_code = 'TEST-003'
        ),
        2, -- Emergency loan (flat interest)
        30000.00,
        15.00,
        'flat',
        6,
        5375.00, -- (30000 + 2250) / 6
        2250.00, -- 30000 * 15% * (6/12)
        32250.00,
        500.00,
        29500.00,
        30000.00,
        2250.00,
        DATE_SUB(NOW(), INTERVAL 2 MONTH),
        DATE_SUB(NOW(), INTERVAL 1 MONTH),
        DATE_ADD(NOW(), INTERVAL 5 MONTH),
        'active',
        'cash',
        DATE_SUB(NOW(), INTERVAL 2 MONTH)
    );

SET @loan3_id = LAST_INSERT_ID();

INSERT INTO
    loan_installments (
        loan_id,
        installment_number,
        due_date,
        principal_amount,
        interest_amount,
        emi_amount,
        outstanding_principal_before,
        outstanding_principal_after,
        status,
        created_at
    )
VALUES
    -- All installments are equal for flat interest
    (
        @loan3_id,
        1,
        DATE_SUB(NOW(), INTERVAL 1 MONTH),
        5000.00,
        375.00,
        5375.00,
        30000.00,
        25000.00,
        'paid',
        NOW()
    ),
    (
        @loan3_id,
        2,
        NOW(),
        5000.00,
        375.00,
        5375.00,
        25000.00,
        20000.00,
        'pending',
        NOW()
    ),
    (
        @loan3_id,
        3,
        DATE_ADD(NOW(), INTERVAL 1 MONTH),
        5000.00,
        375.00,
        5375.00,
        20000.00,
        15000.00,
        'upcoming',
        NOW()
    ),
    (
        @loan3_id,
        4,
        DATE_ADD(NOW(), INTERVAL 2 MONTH),
        5000.00,
        375.00,
        5375.00,
        15000.00,
        10000.00,
        'upcoming',
        NOW()
    ),
    (
        @loan3_id,
        5,
        DATE_ADD(NOW(), INTERVAL 3 MONTH),
        5000.00,
        375.00,
        5375.00,
        10000.00,
        5000.00,
        'upcoming',
        NOW()
    ),
    (
        @loan3_id,
        6,
        DATE_ADD(NOW(), INTERVAL 4 MONTH),
        5000.00,
        375.00,
        5375.00,
        5000.00,
        0.00,
        'upcoming',
        NOW()
    );

-- SCENARIO 4: Partial payment scenario (Member 4)
INSERT INTO
    loans (
        loan_number,
        member_id,
        loan_product_id,
        principal_amount,
        interest_rate,
        interest_type,
        tenure_months,
        emi_amount,
        total_interest,
        total_payable,
        processing_fee,
        net_disbursement,
        outstanding_principal,
        outstanding_interest,
        disbursement_date,
        first_emi_date,
        last_emi_date,
        status,
        disbursement_mode,
        created_at
    )
VALUES (
        'TEST-LOAN-004',
        (
            SELECT id
            FROM members
            WHERE
                member_code = 'TEST-004'
        ),
        1,
        75000.00,
        12.00,
        'reducing',
        12,
        6663.66,
        4963.92,
        79963.92,
        750.00,
        74250.00,
        75000.00,
        4963.92,
        DATE_SUB(NOW(), INTERVAL 1 MONTH),
        NOW(),
        DATE_ADD(NOW(), INTERVAL 11 MONTH),
        'active',
        'bank_transfer',
        DATE_SUB(NOW(), INTERVAL 1 MONTH)
    );

SET @loan4_id = LAST_INSERT_ID();

INSERT INTO
    loan_installments (
        loan_id,
        installment_number,
        due_date,
        principal_amount,
        interest_amount,
        emi_amount,
        outstanding_principal_before,
        outstanding_principal_after,
        status,
        principal_paid,
        interest_paid,
        total_paid,
        created_at
    )
VALUES
    -- Month 1: PARTIAL PAYMENT (paid only ₹4,000 out of ₹6,663.66)
    (
        @loan4_id,
        1,
        NOW(),
        5913.66,
        750.00,
        6663.66,
        75000.00,
        69086.34,
        'partial',
        3250.00,
        750.00,
        4000.00,
        NOW()
    );

-- =====================================================
-- LOAN PAYMENTS (TRANSACTION RECORDS)
-- =====================================================

-- Payments for LOAN-001 (all on-time)
INSERT INTO
    loan_payments (
        payment_code,
        loan_id,
        installment_id,
        payment_type,
        payment_date,
        total_amount,
        principal_component,
        interest_component,
        fine_component,
        outstanding_principal_after,
        outstanding_interest_after,
        payment_mode,
        receipt_number,
        created_at
    )
SELECT
    CONCAT(
        'PAY-',
        DATE_FORMAT(due_date, '%Y%m%d'),
        '-',
        LPAD(installment_number, 3, '0')
    ),
    loan_id,
    id,
    'emi',
    paid_date,
    total_paid,
    principal_paid,
    interest_paid,
    fine_paid,
    outstanding_principal_after,
    (
        SELECT SUM(
                interest_amount - interest_paid
            )
        FROM loan_installments
        WHERE
            loan_id = @loan1_id
            AND installment_number > loan_installments.installment_number
    ),
    'bank_transfer',
    CONCAT(
        'RCP-',
        DATE_FORMAT(paid_date, '%Y%m%d'),
        '-',
        LPAD(installment_number, 3, '0')
    ),
    NOW()
FROM loan_installments
WHERE
    loan_id = @loan1_id
    AND status = 'paid';

-- Payment for LOAN-002 Month 2 (late payment with fine)
INSERT INTO
    loan_payments (
        payment_code,
        loan_id,
        installment_id,
        payment_type,
        payment_date,
        total_amount,
        principal_component,
        interest_component,
        fine_component,
        outstanding_principal_after,
        outstanding_interest_after,
        payment_mode,
        created_at
    )
VALUES (
        'PAY-TEST-002',
        @loan2_id,
        (
            SELECT id
            FROM loan_installments
            WHERE
                loan_id = @loan2_id
                AND installment_number = 2
        ),
        'emi',
        DATE_SUB(NOW(), INTERVAL 1 MONTH) + INTERVAL 15 DAY,
        9034.88, -- EMI + fine
        7963.73,
        921.15,
        150.00,
        84151.39,
        841.52,
        'bank_transfer',
        NOW()
    );

-- Partial payment for LOAN-004
INSERT INTO
    loan_payments (
        payment_code,
        loan_id,
        installment_id,
        payment_type,
        payment_date,
        total_amount,
        principal_component,
        interest_component,
        fine_component,
        outstanding_principal_after,
        outstanding_interest_after,
        payment_mode,
        created_at
    )
VALUES (
        'PAY-TEST-004',
        @loan4_id,
        (
            SELECT id
            FROM loan_installments
            WHERE
                loan_id = @loan4_id
                AND installment_number = 1
        ),
        'emi',
        NOW(),
        4000.00,
        3250.00, -- Interest paid first, then partial principal
        750.00,
        0.00,
        71750.00,
        4213.92,
        'upi',
        NOW()
    );

-- =====================================================
-- FINES
-- =====================================================

-- Fine for LOAN-002 Month 2 (late payment)
INSERT INTO
    fines (
        fine_code,
        member_id,
        fine_type,
        related_type,
        related_id,
        fine_rule_id,
        fine_date,
        due_date,
        days_late,
        fine_amount,
        paid_amount,
        waived_amount,
        balance_amount,
        status,
        created_at
    )
VALUES (
        'FIN-TEST-001',
        (
            SELECT member_id
            FROM loans
            WHERE
                loan_number = 'TEST-LOAN-002'
        ),
        'loan_late',
        'loan_installment',
        (
            SELECT id
            FROM loan_installments
            WHERE
                loan_id = @loan2_id
                AND installment_number = 2
        ),
        2, -- Per day fine rule
        DATE_SUB(NOW(), INTERVAL 1 MONTH) + INTERVAL 8 DAY,
        DATE_SUB(NOW(), INTERVAL 1 MONTH),
        15,
        150.00, -- 10 per day * 15 days
        150.00, -- Paid
        0.00,
        0.00,
        'paid',
        NOW()
    );

-- Pending fine for LOAN-002 Month 3 (overdue)
INSERT INTO
    fines (
        fine_code,
        member_id,
        fine_type,
        related_type,
        related_id,
        fine_rule_id,
        fine_date,
        due_date,
        days_late,
        fine_amount,
        paid_amount,
        waived_amount,
        balance_amount,
        status,
        created_at
    )
VALUES (
        'FIN-TEST-002',
        (
            SELECT member_id
            FROM loans
            WHERE
                loan_number = 'TEST-LOAN-002'
        ),
        'loan_late',
        'loan_installment',
        (
            SELECT id
            FROM loan_installments
            WHERE
                loan_id = @loan2_id
                AND installment_number = 3
        ),
        2,
        NOW(),
        DATE_SUB(NOW(), INTERVAL 5 DAY),
        5,
        50.00, -- 10 per day * 5 days
        0.00,
        0.00,
        50.00,
        'pending',
        NOW()
    );

-- =====================================================
-- BANK TRANSACTIONS (SIMULATED IMPORTS)
-- =====================================================

-- Insert a test bank account
INSERT INTO
    bank_accounts (
        account_name,
        bank_name,
        account_number,
        account_type,
        is_primary,
        created_at
    )
VALUES (
        'TEST Cash Account',
        'Test Bank',
        'TEST-001',
        'current',
        1,
        NOW()
    )
ON DUPLICATE KEY UPDATE
    account_name = 'TEST Cash Account';

SET @test_bank_id = LAST_INSERT_ID();

-- Create a test import record
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
        completed_at
    )
VALUES (
        'IMP-TEST-001',
        @test_bank_id,
        'test_statement.csv',
        '/uploads/bank_imports/test_statement.csv',
        'csv',
        'completed',
        10,
        8,
        2,
        1,
        NOW(),
        NOW()
    );

SET @test_import_id = LAST_INSERT_ID();

-- Bank transactions matching loan payments
INSERT INTO
    bank_transactions (
        import_id,
        bank_account_id,
        transaction_date,
        transaction_type,
        amount,
        description,
        reference_number,
        utr_number,
        mapping_status,
        detected_member_id,
        created_at
    )
VALUES
    -- Matching LOAN-001 Month 1 payment
    (
        @test_import_id,
        @test_bank_id,
        DATE_SUB(NOW(), INTERVAL 4 MONTH),
        'credit',
        8606.64,
        'EMI Payment TEST-001 TEST-LOAN-001',
        'REF001',
        'UTR001',
        'mapped',
        (
            SELECT id
            FROM members
            WHERE
                member_code = 'TEST-001'
        ),
        NOW()
    ),
    -- Matching LOAN-002 Month 2 payment (with fine)
    (
        @test_import_id,
        @test_bank_id,
        DATE_SUB(NOW(), INTERVAL 1 MONTH) + INTERVAL 15 DAY,
        'credit',
        9034.88,
        'Late EMI TEST-002 TEST-LOAN-002',
        'REF002',
        'UTR002',
        'mapped',
        (
            SELECT id
            FROM members
            WHERE
                member_code = 'TEST-002'
        ),
        NOW()
    ),
    -- Unmatched transaction (ambiguous description)
    (
        @test_import_id,
        @test_bank_id,
        NOW(),
        'credit',
        5000.00,
        'Payment from customer',
        'REF003',
        'UTR003',
        'unmapped',
        NULL,
        NOW()
    ),
    -- Overpayment scenario
    (
        @test_import_id,
        @test_bank_id,
        NOW(),
        'credit',
        15000.00,
        'Lump sum TEST-001',
        'REF004',
        'UTR004',
        'unmapped',
        (
            SELECT id
            FROM members
            WHERE
                member_code = 'TEST-001'
        ),
        NOW()
    ),
    -- Split payment scenario (should map to multiple EMIs)
    (
        @test_import_id,
        @test_bank_id,
        NOW(),
        'credit',
        20000.00,
        'Combined payment TEST-002 Savings+Loan',
        'REF005',
        'UTR005',
        'unmapped',
        (
            SELECT id
            FROM members
            WHERE
                member_code = 'TEST-002'
        ),
        NOW()
    );

-- =====================================================
-- UPDATE LOAN OUTSTANDING BALANCES
-- =====================================================

-- Update LOAN-001 outstanding
UPDATE loans
SET
    outstanding_principal = (
        SELECT SUM(
                principal_amount - principal_paid
            )
        FROM loan_installments
        WHERE
            loan_id = @loan1_id
    ),
    outstanding_interest = (
        SELECT SUM(
                interest_amount - interest_paid
            )
        FROM loan_installments
        WHERE
            loan_id = @loan1_id
    ),
    outstanding_fine = (
        SELECT SUM(fine_amount - fine_paid)
        FROM loan_installments
        WHERE
            loan_id = @loan1_id
    ),
    total_principal_paid = (
        SELECT SUM(principal_paid)
        FROM loan_installments
        WHERE
            loan_id = @loan1_id
    ),
    total_interest_paid = (
        SELECT SUM(interest_paid)
        FROM loan_installments
        WHERE
            loan_id = @loan1_id
    ),
    total_fine_paid = (
        SELECT SUM(fine_paid)
        FROM loan_installments
        WHERE
            loan_id = @loan1_id
    ),
    total_amount_paid = (
        SELECT SUM(total_paid)
        FROM loan_installments
        WHERE
            loan_id = @loan1_id
    )
WHERE
    id = @loan1_id;

-- Update LOAN-002 outstanding
UPDATE loans
SET
    outstanding_principal = (
        SELECT SUM(
                principal_amount - principal_paid
            )
        FROM loan_installments
        WHERE
            loan_id = @loan2_id
    ),
    outstanding_interest = (
        SELECT SUM(
                interest_amount - interest_paid
            )
        FROM loan_installments
        WHERE
            loan_id = @loan2_id
    ),
    outstanding_fine = (
        SELECT SUM(fine_amount - fine_paid)
        FROM loan_installments
        WHERE
            loan_id = @loan2_id
    ),
    total_principal_paid = (
        SELECT SUM(principal_paid)
        FROM loan_installments
        WHERE
            loan_id = @loan2_id
    ),
    total_interest_paid = (
        SELECT SUM(interest_paid)
        FROM loan_installments
        WHERE
            loan_id = @loan2_id
    ),
    total_fine_paid = (
        SELECT SUM(fine_paid)
        FROM loan_installments
        WHERE
            loan_id = @loan2_id
    ),
    total_amount_paid = (
        SELECT SUM(total_paid)
        FROM loan_installments
        WHERE
            loan_id = @loan2_id
    )
WHERE
    id = @loan2_id;

-- =====================================================
-- TEST SCENARIOS SUMMARY
-- =====================================================

SELECT 'Test data created successfully' as status;

SELECT 'LOAN SCENARIOS' as category, 'TEST-LOAN-001: Perfect on-time payments (4/6 paid)' as scenario
UNION ALL
SELECT 'LOAN SCENARIOS', 'TEST-LOAN-002: Late payment with fine (1 paid, 1 overdue)'
UNION ALL
SELECT 'LOAN SCENARIOS', 'TEST-LOAN-003: Flat interest loan (1/6 paid)'
UNION ALL
SELECT 'LOAN SCENARIOS', 'TEST-LOAN-004: Partial payment (₹4k paid out of ₹6.6k due)'
UNION ALL
SELECT 'FINE SCENARIOS', 'FIN-TEST-001: Late payment fine (paid)'
UNION ALL
SELECT 'FINE SCENARIOS', 'FIN-TEST-002: Overdue fine (pending)'
UNION ALL
SELECT 'BANK SCENARIOS', '2 matched transactions (auto-mapped)'
UNION ALL
SELECT 'BANK SCENARIOS', '3 unmatched transactions (manual mapping required)'
UNION ALL
SELECT 'MEMBERS', '10 test members created with savings accounts';

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Check loan data integrity
SELECT
    l.loan_number,
    l.principal_amount,
    l.outstanding_principal,
    (
        SELECT SUM(principal_amount)
        FROM loan_installments
        WHERE
            loan_id = l.id
    ) as total_installment_principal,
    (
        SELECT SUM(principal_paid)
        FROM loan_installments
        WHERE
            loan_id = l.id
    ) as total_paid
FROM loans l
WHERE
    l.loan_number LIKE 'TEST%';

SET FOREIGN_KEY_CHECKS = 1;