<?php
/**
 * Test Fine Calculation
 */
// Direct MySQL connection - no CI framework
$db = new mysqli('localhost', 'root', '', 'windeep_finance');

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "=== FINE CALCULATION DEBUG ===\n\n";

// 1. Check fine rules
echo "1. Fine Rules:\n";
$result = $db->query("SELECT * FROM fine_rules WHERE is_active = 1");
while ($row = $result->fetch_assoc()) {
    echo "   ID: {$row['id']}, Type: {$row['fine_type']}, Calc: {$row['calculation_type']}, Value: {$row['fine_value']}, Grace: {$row['grace_period_days']}\n";
}

// 2. Check overdue loan installments
echo "\n2. Overdue Loan Installments:\n";
$result = $db->query("
    SELECT li.*, l.member_id, l.status as loan_status, 
           DATEDIFF(CURDATE(), li.due_date) as days_overdue
    FROM loan_installments li
    JOIN loans l ON l.id = li.loan_id
    WHERE li.status = 'pending' AND li.due_date < CURDATE() AND l.status = 'active'
    LIMIT 5
");
$count = 0;
while ($row = $result->fetch_assoc()) {
    $count++;
    echo "   Inst #{$row['installment_number']}: Due: {$row['due_date']}, Days: {$row['days_overdue']}, EMI: {$row['emi_amount']}\n";
}
echo "   Total overdue: " . $count . "\n";

// 3. Check overdue savings schedules
echo "\n3. Overdue Savings Schedules:\n";
$result = $db->query("
    SELECT ss.*, sa.member_id, sa.status as account_status,
           DATEDIFF(CURDATE(), ss.due_date) as days_overdue
    FROM savings_schedule ss
    JOIN savings_accounts sa ON sa.id = ss.savings_account_id
    WHERE ss.status = 'pending' AND ss.due_date < CURDATE() AND sa.status = 'active'
    LIMIT 5
");
$count = 0;
while ($row = $result->fetch_assoc()) {
    $count++;
    echo "   Month: {$row['due_month']}, Due: {$row['due_date']}, Days: {$row['days_overdue']}, Amount: {$row['due_amount']}\n";
}
echo "   Total overdue: " . $count . "\n";

// 4. Manually test fine calculation
echo "\n4. Manual Fine Calculation Test:\n";
$rule = $db->query("SELECT * FROM fine_rules WHERE fine_type = 'loan_late' AND is_active = 1")->fetch_assoc();
if ($rule) {
    $days_late = 100; // example
    $grace_period = $rule['grace_period_days'];
    $effective_days = max(0, $days_late - $grace_period);
    
    echo "   Rule: {$rule['rule_name']}\n";
    echo "   Calculation Type: {$rule['calculation_type']}\n";
    echo "   Fine Value: {$rule['fine_value']}\n";
    echo "   Grace Period: {$grace_period} days\n";
    echo "   Test Days Late: {$days_late}\n";
    echo "   Effective Days: {$effective_days}\n";
    
    if ($effective_days > 0) {
        if ($rule['calculation_type'] == 'fixed') {
            echo "   Fine Amount: {$rule['fine_value']} (fixed)\n";
        }
    } else {
        echo "   Fine Amount: 0 (within grace period)\n";
    }
} else {
    echo "   No loan_late rule found!\n";
}

// 5. Check existing fines
echo "\n5. Existing Fines:\n";
$result = $db->query("SELECT * FROM fines LIMIT 5");
$count = $result->num_rows;
echo "   Total fines in database: " . $count . "\n";

echo "\n=== DEBUG COMPLETE ===\n";
$db->close();
