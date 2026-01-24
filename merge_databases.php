<?php
/**
 * Smart Database Merger
 * Merges windeep_finance tables into windeep_finance_new
 * Keeps windeep_finance_new as primary with better data
 */

define('BASEPATH', __DIR__ . '/system/');
define('ENVIRONMENT', 'development');

$source_db = 'windeep_finance'; // Old database
$target_db = 'windeep_finance_new'; // New database (will be primary)

$conn_source = mysqli_connect('localhost', 'root', '', $source_db);
$conn_target = mysqli_connect('localhost', 'root', '', $target_db);

if (!$conn_source || !$conn_target) {
    die("Connection failed\n");
}

echo "=== SMART DATABASE MERGER ===\n\n";

// Tables to copy from windeep_finance to windeep_finance_new
$tables_to_migrate = [
    'admin_details',
    'bank_balance_history',
    'chat_box',
    'expenditure',
    'loan_transaction_details',
    'loan_transactions',
    'member_details',
    'other_member_details',
    'requests_status',
    'send_form',
    'shares',
    'view_requests'
];

echo "STEP 1: Migrating missing tables from $source_db to $target_db\n";
echo "Tables to migrate: " . count($tables_to_migrate) . "\n\n";

// Disable foreign key checks
mysqli_query($conn_target, "SET FOREIGN_KEY_CHECKS=0");

foreach ($tables_to_migrate as $table) {
    echo "Copying table: $table... ";
    
    // Get CREATE TABLE statement
    $result = mysqli_query($conn_source, "SHOW CREATE TABLE `$table`");
    if (!$result) {
        echo "FAILED (cannot get structure)\n";
        continue;
    }
    
    $row = mysqli_fetch_array($result);
    $create_sql = $row[1];
    
    // Create table in target
    mysqli_query($conn_target, "DROP TABLE IF EXISTS `$table`");
    if (mysqli_query($conn_target, $create_sql)) {
        // Copy data
        $result = mysqli_query($conn_source, "SELECT * FROM `$table`");
        $count = 0;
        
        if ($result && mysqli_num_rows($result) > 0) {
            while ($data_row = mysqli_fetch_assoc($result)) {
                $columns = array_keys($data_row);
                $values = array_values($data_row);
                
                // Escape values
                $escaped_values = array_map(function($v) use ($conn_target) {
                    return $v === null ? 'NULL' : "'" . mysqli_real_escape_string($conn_target, $v) . "'";
                }, $values);
                
                $insert_sql = "INSERT INTO `$table` (`" . implode("`, `", $columns) . "`) VALUES (" . implode(", ", $escaped_values) . ")";
                mysqli_query($conn_target, $insert_sql);
                $count++;
            }
        }
        
        echo "✓ Created + $count rows copied\n";
    } else {
        echo "FAILED: " . mysqli_error($conn_target) . "\n";
    }
}

// Re-enable foreign key checks
mysqli_query($conn_target, "SET FOREIGN_KEY_CHECKS=1");

echo "\n=== MIGRATION COMPLETE ===\n\n";

// Final comparison
$result = mysqli_query($conn_target, "SHOW TABLES");
$final_tables = [];
while ($row = mysqli_fetch_array($result)) {
    $final_tables[] = $row[0];
}

echo "$target_db now has " . count($final_tables) . " tables\n";
echo "\nRECOMMENDATION:\n";
echo "1. Update .env to use: DB_NAME=windeep_finance_new\n";
echo "2. Test application thoroughly\n";
echo "3. After testing, you can drop database: windeep_finance\n";
echo "4. Optionally rename windeep_finance_new to windeep_finance\n";

mysqli_close($conn_source);
mysqli_close($conn_target);
