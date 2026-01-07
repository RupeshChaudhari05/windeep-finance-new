<?php
echo "Loading complete test data...\n\n";

$pdo = new PDO('mysql:host=localhost;dbname=windeep_finance_new', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $sql = file_get_contents(__DIR__ . '/database/complete_test_data.sql');
    $pdo->exec($sql);
    
    echo "✓ Test data loaded successfully!\n\n";
    
    echo "=== SUMMARY ===\n\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as c FROM members WHERE member_code LIKE 'TEST%'");
    echo "Test Members: " . $stmt->fetch(PDO::FETCH_ASSOC)['c'] . "\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as c FROM loans WHERE loan_number LIKE 'TESTLN%'");
    echo "Test Loans: " . $stmt->fetch(PDO::FETCH_ASSOC)['c'] . "\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as c FROM bank_transactions WHERE utr_number LIKE 'TEST-UTR%'");
    echo "Unmapped Transactions: " . $stmt->fetch(PDO::FETCH_ASSOC)['c'] . "\n\n";
    
    echo "=== READY TO TEST! ===\n\n";
    echo "Next step: Update config/database.php\n";
    echo "Change: \$db['default']['database'] = 'windeep_finance_new';\n\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nLine: " . $e->getLine() . "\n";
}
