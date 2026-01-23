<?php
// Quick check for fine rules and overdue records

$db = new mysqli('localhost', 'root', '', 'windeep_finance');

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "=== ALL TABLES IN DATABASE ===\n";
$result = $db->query("SHOW TABLES");
$tables = [];
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
    echo "  - {$row[0]}\n";
}

echo "\n=== CHECKING REQUIRED TABLES ===\n";
$required = ['fine_rules', 'fines', 'loan_installments', 'loans', 'savings_schedule', 'savings_accounts', 'members'];
foreach ($required as $table) {
    if (in_array($table, $tables)) {
        echo "✅ {$table}\n";
    } else {
        echo "❌ {$table} - MISSING!\n";
    }
}

echo "\n=== RECOMMENDATION ===\n";
echo "Run this command to import the full schema:\n";
echo "C:\\xampp_new\\mysql\\bin\\mysql -u root windeep_finance < database/schema.sql\n";

$db->close();
