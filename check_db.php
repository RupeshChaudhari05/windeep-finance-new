<?php
$pdo = new PDO('mysql:host=localhost;dbname=windeep_finance', 'root', '');
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Tables in windeep_finance database:\n";
echo "Total: " . count($tables) . "\n\n";

foreach ($tables as $table) {
    echo "- $table\n";
}
