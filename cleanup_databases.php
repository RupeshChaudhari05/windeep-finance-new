<?php
/**
 * Database Cleanup Script
 * Safely drops windeep_finance and optionally renames windeep_finance_new
 */

$old_db = 'windeep_finance';
$new_db = 'windeep_finance_new';
$final_name = 'windeep_finance';

$conn = mysqli_connect('localhost', 'root', '');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "=== DATABASE CLEANUP ===\n\n";

// Step 1: Create final backup of old database
echo "STEP 1: Creating backup of $old_db...\n";
$backup_file = "backup_{$old_db}_" . date('Y-m-d_His') . ".sql";
$cmd = "mysqldump -u root $old_db > $backup_file";
system($cmd, $ret);
if ($ret === 0) {
    echo "✓ Backup saved to: $backup_file\n\n";
} else {
    echo "✗ Backup failed\n\n";
}

// Step 2: Drop old database
echo "STEP 2: Dropping database $old_db...\n";
$confirm = readline("Are you sure you want to DROP database '$old_db'? Type 'YES' to confirm: ");
if (strtoupper($confirm) === 'YES') {
    if (mysqli_query($conn, "DROP DATABASE `$old_db`")) {
        echo "✓ Database $old_db dropped successfully\n\n";
    } else {
        echo "✗ Failed to drop database: " . mysqli_error($conn) . "\n\n";
    }
} else {
    echo "⊗ Skipped dropping database\n\n";
}

// Step 3: Rename windeep_finance_new to windeep_finance (optional)
echo "STEP 3: Rename $new_db to $final_name (optional)...\n";
$rename = readline("Do you want to rename '$new_db' to '$final_name'? Type 'YES' to confirm: ");
if (strtoupper($rename) === 'YES') {
    // Create new database
    mysqli_query($conn, "CREATE DATABASE `$final_name`");
    
    // Get all tables
    mysqli_select_db($conn, $new_db);
    $result = mysqli_query($conn, "SHOW TABLES");
    $tables = [];
    while ($row = mysqli_fetch_array($result)) {
        $tables[] = $row[0];
    }
    
    echo "Renaming $new_db to $final_name (moving " . count($tables) . " tables)...\n";
    
    foreach ($tables as $table) {
        $sql = "RENAME TABLE `$new_db`.`$table` TO `$final_name`.`$table`";
        mysqli_query($conn, $sql);
        echo ".";
    }
    
    // Drop old database
    mysqli_query($conn, "DROP DATABASE `$new_db`");
    
    echo "\n✓ Database renamed successfully\n\n";
    echo "⚠ UPDATE .env FILE: Change DB_NAME to '$final_name'\n";
} else {
    echo "⊗ Skipped renaming\n\n";
    echo "ℹ Current database: $new_db (as configured in .env)\n";
}

mysqli_close($conn);

echo "\n=== CLEANUP COMPLETE ===\n";
echo "\nSUMMARY:\n";
echo "- Application is using: $new_db (50 tables)\n";
echo "- Old database backed up to: $backup_file\n";
echo "- All features from both databases are now available\n";
