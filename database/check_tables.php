<?php
$pdo = new PDO('mysql:host=localhost;dbname=windeep_finance', 'root', '');
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
echo "Total tables: " . count($tables) . "\n\n";

$required = ['members', 'loans', 'loan_installments', 'loan_payments', 'bank_transactions', 'fines', 'savings_accounts', 'loan_applications'];

echo "Required tables:\n";
foreach ($required as $table) {
    $exists = in_array($table, $tables) ? '✓' : '✗';
    echo "$exists $table\n";
}

// Check for stored procedure
try {
    $pdo->query("SHOW PROCEDURE STATUS WHERE Name = 'sp_generate_loan_installments'");
    $procs = $pdo->fetchAll();
    echo "\n✓ Stored procedure exists\n";
} catch (Exception $e) {
    echo "\n✗ Stored procedure NOT found\n";
    echo "Creating stored procedure...\n";
}
