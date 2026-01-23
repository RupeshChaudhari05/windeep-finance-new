<?php
/**
 * Quick Test Data Setup
 * Run: php database/setup_test_data.php
 */

$db = new mysqli('localhost', 'root', '', 'windeep_finance');

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "=== SETTING UP TEST DATA ===\n\n";

// Disable foreign key checks
$db->query("SET FOREIGN_KEY_CHECKS = 0");

// 1. Add Fine Rules
echo "1. Creating Fine Rules...\n";
$db->query("DELETE FROM fine_rules");
$db->query("INSERT INTO fine_rules (rule_code, rule_name, applies_to, fine_type, calculation_type, fine_value, per_day_amount, max_fine_amount, grace_period_days, is_active, effective_from) VALUES
    ('FR-001', 'Loan EMI Late Fine', 'loan', 'loan_late', 'fixed', 100.00, 10.00, 1000.00, 5, 1, '2024-01-01'),
    ('FR-002', 'Savings Late Fine', 'savings', 'savings_late', 'fixed', 50.00, 5.00, 500.00, 3, 1, '2024-01-01')
");
echo "   ✅ Created 2 fine rules\n";

// 2. Add Test Members (use INSERT IGNORE to skip if exists)
echo "2. Creating Test Members...\n";
$db->query("INSERT IGNORE INTO members (member_code, first_name, last_name, phone, email, address_line1, city, state, pincode, status, join_date, created_at) VALUES
    ('MEM-TEST-001', 'Rajesh', 'Kumar', '9876543210', 'rajesh@test.com', '123 Main St', 'Mumbai', 'Maharashtra', '400001', 'active', '2024-01-01', NOW()),
    ('MEM-TEST-002', 'Priya', 'Sharma', '9876543211', 'priya@test.com', '456 Park Ave', 'Delhi', 'Delhi', '110001', 'active', '2024-01-01', NOW())
");
$member1_id = $db->insert_id - 1;
$member2_id = $db->insert_id;
echo "   ✅ Created 2 test members\n";

// Get actual member IDs
$result = $db->query("SELECT id FROM members WHERE member_code = 'MEM-TEST-001'");
$row = $result->fetch_assoc();
$member1_id = $row['id'];

$result = $db->query("SELECT id FROM members WHERE member_code = 'MEM-TEST-002'");
$row = $result->fetch_assoc();
$member2_id = $row['id'];

// 3. Add Loan Product
echo "3. Creating Loan Product...\n";
$db->query("DELETE FROM loan_products WHERE product_code = 'LP-TEST'");
$db->query("INSERT INTO loan_products (product_code, product_name, interest_rate, interest_type, min_amount, max_amount, min_tenure_months, max_tenure_months, processing_fee_value, is_active, created_at) VALUES
    ('LP-TEST', 'Personal Loan', 12.00, 'reducing', 10000, 500000, 6, 60, 1.00, 1, NOW())
");
$product_id = $db->insert_id;
echo "   ✅ Created loan product\n";

// 4. Add Savings Scheme
echo "4. Creating Savings Scheme...\n";
$db->query("DELETE FROM savings_schemes WHERE scheme_code = 'SS-TEST'");
$db->query("INSERT INTO savings_schemes (scheme_code, scheme_name, deposit_frequency, interest_rate, min_deposit, monthly_amount, duration_months, lock_in_period, is_active, created_at) VALUES
    ('SS-TEST', 'Monthly Savings', 'monthly', 6.00, 500, 1000, 24, 12, 1, NOW())
");
$scheme_id = $db->insert_id;
echo "   ✅ Created savings scheme\n";

// 5. Add Loan with Overdue Installments
echo "5. Creating Loan with Overdue Installments...\n";
$db->query("DELETE FROM loan_installments WHERE loan_id IN (SELECT id FROM loans WHERE loan_number LIKE 'LN-TEST%')");
$db->query("DELETE FROM loans WHERE loan_number LIKE 'LN-TEST%'");
$db->query("DELETE FROM loan_applications WHERE application_number LIKE 'LA-TEST%'");

// First create a loan application
$db->query("INSERT INTO loan_applications (application_number, member_id, loan_product_id, requested_amount, requested_tenure_months, purpose, status, application_date, created_at) VALUES
    ('LA-TEST-001', $member1_id, $product_id, 100000, 12, 'Personal needs', 'disbursed', '2024-01-01', NOW())
");
$app_id = $db->insert_id;

$db->query("INSERT INTO loans (loan_number, loan_application_id, member_id, loan_product_id, principal_amount, interest_rate, interest_type, tenure_months, emi_amount, total_interest, total_payable, net_disbursement, outstanding_principal, outstanding_interest, disbursement_date, first_emi_date, last_emi_date, status, created_at) VALUES
    ('LN-TEST-001', $app_id, $member1_id, $product_id, 100000, 12.00, 'reducing', 12, 8885, 6620, 106620, 99000, 100000, 6620, '2024-01-05', '2024-02-05', '2025-01-05', 'active', NOW())
");
$loan_id = $db->insert_id;

// Add installments - some overdue (due date in past with pending status)
$db->query("DELETE FROM loan_installments WHERE loan_id = $loan_id");
$outstanding = 100000;
for ($i = 1; $i <= 12; $i++) {
    $due_date = date('Y-m-d', strtotime("2024-01-05 +{$i} months"));
    $status = 'pending';
    $total_paid = 0;
    $principal_paid = 0;
    $interest_paid = 0;
    if ($i <= 3) {
        $status = 'paid';
        $total_paid = 8885;
        $principal_paid = 8333;
        $interest_paid = 552;
    }
    
    $outstanding_before = $outstanding;
    if ($status == 'paid') $outstanding -= 8333;
    $outstanding_after = $outstanding;
    
    $db->query("INSERT INTO loan_installments (loan_id, installment_number, due_date, principal_amount, interest_amount, emi_amount, outstanding_principal_before, outstanding_principal_after, principal_paid, interest_paid, total_paid, status, created_at) VALUES
        ($loan_id, $i, '$due_date', 8333, 552, 8885, $outstanding_before, $outstanding_after, $principal_paid, $interest_paid, $total_paid, '$status', NOW())
    ");
}
echo "   ✅ Created loan with 12 installments (3 paid, 9 pending/overdue)\n";

// 6. Add Savings Account with Overdue Schedule
echo "6. Creating Savings Account with Overdue Schedule...\n";
$db->query("DELETE FROM savings_accounts WHERE account_number LIKE 'SA-TEST%'");
$db->query("INSERT INTO savings_accounts (account_number, member_id, scheme_id, monthly_amount, start_date, total_deposited, current_balance, status, created_at) VALUES
    ('SA-TEST-001', $member2_id, $scheme_id, 1000, '2024-01-01', 3000, 3000, 'active', NOW())
");
$savings_id = $db->insert_id;

// Add schedule - some overdue
$db->query("DELETE FROM savings_schedule WHERE savings_account_id = $savings_id");
for ($i = 1; $i <= 24; $i++) {
    $due_month = date('Y-m-01', strtotime("2024-01-01 +{$i} months"));
    $due_date = date('Y-m-10', strtotime("2024-01-10 +{$i} months")); // Due on 10th
    $status = 'pending';
    if ($i <= 3) $status = 'paid'; // First 3 paid
    
    $db->query("INSERT INTO savings_schedule (savings_account_id, due_month, due_date, due_amount, paid_amount, status, created_at) VALUES
        ($savings_id, '$due_month', '$due_date', 1000, " . ($status == 'paid' ? '1000' : '0') . ", '$status', NOW())
    ");
}
echo "   ✅ Created savings account with 24 schedules (3 paid, 21 pending/overdue)\n";

// Summary
echo "\n=== SUMMARY ===\n";
$result = $db->query("SELECT COUNT(*) as cnt FROM loan_installments WHERE status = 'pending' AND due_date < CURDATE()");
$row = $result->fetch_assoc();
echo "Overdue loan installments: {$row['cnt']}\n";

$result = $db->query("SELECT COUNT(*) as cnt FROM savings_schedule WHERE status = 'pending' AND due_date < CURDATE()");
$row = $result->fetch_assoc();
echo "Overdue savings schedules: {$row['cnt']}\n";

$result = $db->query("SELECT COUNT(*) as cnt FROM fine_rules WHERE is_active = 1");
$row = $result->fetch_assoc();
echo "Active fine rules: {$row['cnt']}\n";

// Re-enable foreign key checks
$db->query("SET FOREIGN_KEY_CHECKS = 1");

echo "\n✅ Test data setup complete!\n";
echo "Now run: php index.php cli/cron apply_overdue_fines\n";

$db->close();
