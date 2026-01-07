<?php
// Quick verification and data load
$pdo = new PDO('mysql:host=localhost;dbname=windeep_finance', 'root', '');

// Check if test data exists
$stmt = $pdo->query("SELECT COUNT(*) as c FROM members WHERE member_code LIKE 'TEST%'");
$count = $stmt->fetch(PDO::FETCH_ASSOC)['c'];

if ($count > 0) {
    echo "✓ Test data already exists: $count test members\n\n";
} else {
    echo "Loading test data...\n";
    $sql = file_get_contents(__DIR__ . '/database/simple_test_data.sql');
    $pdo->exec($sql);
    echo "✓ Test data loaded successfully!\n\n";
}

// Show summary
echo "=== CURRENT TEST DATA ===\n\n";

$queries = [
    'Members' => "SELECT COUNT(*) as c FROM members WHERE member_code LIKE 'TEST%'",
    'Loans' => "SELECT COUNT(*) as c FROM loans WHERE loan_number LIKE 'TESTLN%'",
    'Unmapped Transactions' => "SELECT COUNT(*) as c FROM bank_transactions WHERE utr_number LIKE 'TEST-UTR%'"
];

foreach ($queries as $label => $query) {
    $stmt = $pdo->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo str_pad($label . ':', 25) . $result['c'] . "\n";
}

echo "\n=== READY TO TEST ===\n";
echo "1. Open browser: http://localhost/windeep_finance/\n";
echo "2. Login as admin\n";
echo "3. Check Members → Search for 'TEST'\n";
echo "4. Check Loans → View TESTLN-001, 002, 003\n";
echo "5. Upload Bank Statement: database/bank_statement_jan2025.xlsx\n";
echo "6. Map transactions!\n";
