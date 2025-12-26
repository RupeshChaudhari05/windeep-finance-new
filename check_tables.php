<?php
// Quick check for database tables
$db = new mysqli('localhost', 'root', '', 'windeep_finance_new');

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "Database connected successfully\n\n";

$tables = ['system_settings', 'financial_years'];

foreach ($tables as $table) {
    $result = $db->query("SHOW TABLES LIKE '$table'");
    
    if ($result && $result->num_rows > 0) {
        $count_result = $db->query("SELECT COUNT(*) as cnt FROM $table");
        $count = $count_result->fetch_assoc()['cnt'];
        echo "✓ Table '$table' exists (rows: $count)\n";
        
        // Show sample data
        if ($count > 0) {
            $sample = $db->query("SELECT * FROM $table LIMIT 3");
            if ($sample) {
                echo "  Sample data:\n";
                while ($row = $sample->fetch_assoc()) {
                    echo "  - " . json_encode($row) . "\n";
                }
            }
        }
    } else {
        echo "✗ Table '$table' does NOT exist\n";
    }
    echo "\n";
}

$db->close();
