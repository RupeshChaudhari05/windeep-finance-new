<?php
$pdo = new PDO('mysql:host=localhost;dbname=windeep_finance_new', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Loading test data...\n\n";

$sql = file_get_contents(__DIR__ . '/database/test_data_corrected.sql');

if (empty($sql)) {
    die("Error: SQL file is empty or not found!\n");
}

try {
    $pdo->exec($sql);
    echo "✓ Test data loaded successfully!\n\n";
    
    // Show summary
    $stmt = $pdo->query("SELECT COUNT(*) as c FROM members WHERE member_code LIKE 'TEST%'");
    $members = $stmt->fetch(PDO::FETCH_ASSOC)['c'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as c FROM loans WHERE loan_number LIKE 'TESTLN%'");
    $loans = $stmt->fetch(PDO::FETCH_ASSOC)['c'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as c FROM bank_transactions WHERE utr_number LIKE 'TEST-UTR%'");
    $transactions = $stmt->fetch(PDO::FETCH_ASSOC)['c'];
    
    echo "Members: $members\n";
    echo "Loans: $loans\n";
    echo "Unmapped Transactions: $transactions\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
