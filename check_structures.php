<?php
$pdo = new PDO('mysql:host=localhost;dbname=windeep_finance_new', 'root', '');

echo "=== TABLE STRUCTURES ===\n\n";

$tables = ['members', 'savings_accounts', 'bank_accounts', 'loan_products', 'loans', 'loan_applications'];

foreach ($tables as $table) {
    echo "$table:\n";
    $stmt = $pdo->query("DESCRIBE $table");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  " . $row['Field'] . "\n";
    }
    echo "\n";
}
