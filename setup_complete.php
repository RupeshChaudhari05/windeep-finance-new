<?php
// Complete Database Setup Script
echo "=== WINDEEP FINANCE DATABASE SETUP ===\n\n";

try {
    // Connect without database first
    $pdo = new PDO('mysql:host=localhost', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE 'windeep_finance'");
    $dbExists = $stmt->rowCount() > 0;
    
    if (!$dbExists) {
        echo "Step 1: Creating database 'windeep_finance'...\n";
        $pdo->exec("CREATE DATABASE IF NOT EXISTS windeep_finance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✓ Database created\n\n";
    } else {
        echo "✓ Database 'windeep_finance' exists\n\n";
    }
    
    // Now connect to the database
    $pdo = new PDO('mysql:host=localhost;dbname=windeep_finance', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if tables exist
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $tableCount = count($tables);
    
    if ($tableCount == 0) {
        echo "Step 2: Creating database schema...\n";
        echo "Reading schema.sql...\n";
        
        if (file_exists(__DIR__ . '/database/schema.sql')) {
            $schema = file_get_contents(__DIR__ . '/database/schema.sql');
            
            // Remove comments
            $schema = preg_replace('/--[^\n]*\n/', '', $schema);
            $schema = preg_replace('/\/\*.*?\*\//s', '', $schema);
            
            // Execute
            $pdo->exec($schema);
            
            // Count tables again
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "✓ Schema created: " . count($tables) . " tables\n\n";
        } else {
            echo "✗ schema.sql not found!\n";
            exit(1);
        }
    } else {
        echo "✓ Database schema exists: $tableCount tables\n\n";
    }
    
    // Check for test data
    $stmt = $pdo->query("SELECT COUNT(*) as c FROM members WHERE member_code LIKE 'TEST%'");
    $testCount = $stmt->fetch(PDO::FETCH_ASSOC)['c'];
    
    if ($testCount == 0) {
        echo "Step 3: Loading test data...\n";
        
        if (file_exists(__DIR__ . '/database/simple_test_data.sql')) {
            $testData = file_get_contents(__DIR__ . '/database/simple_test_data.sql');
            $pdo->exec($testData);
            echo "✓ Test data loaded\n\n";
        } else {
            echo "✗ simple_test_data.sql not found!\n";
        }
    } else {
        echo "✓ Test data exists: $testCount test members\n\n";
    }
    
    // Show summary
    echo "=== DATABASE STATUS ===\n\n";
    
    $queries = [
        'Total Members' => "SELECT COUNT(*) as c FROM members",
        'Test Members' => "SELECT COUNT(*) as c FROM members WHERE member_code LIKE 'TEST%'",
        'Total Loans' => "SELECT COUNT(*) as c FROM loans",
        'Test Loans' => "SELECT COUNT(*) as c FROM loans WHERE loan_number LIKE 'TESTLN%'",
        'Bank Transactions' => "SELECT COUNT(*) as c FROM bank_transactions",
        'Unmapped Transactions' => "SELECT COUNT(*) as c FROM bank_transactions WHERE utr_number LIKE 'TEST-UTR%'"
    ];
    
    foreach ($queries as $label => $query) {
        $stmt = $pdo->query($query);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo str_pad($label . ':', 30) . $result['c'] . "\n";
    }
    
    echo "\n=== NEXT STEPS ===\n\n";
    echo "1. Open browser: http://localhost/windeep_finance/\n";
    echo "2. Login as admin (use your existing admin credentials)\n";
    echo "3. Go to Members → Search for 'TEST' to see test members\n";
    echo "4. Go to Loans → View TESTLN-001, TESTLN-002, TESTLN-003\n";
    echo "5. Go to Bank → Bank Statements → Import Statement\n";
    echo "6. Upload: database/bank_statement_jan2025.xlsx\n";
    echo "7. Start mapping transactions!\n";
    echo "\n✓ System ready for testing!\n";
    
} catch (PDOException $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n\n";
    echo "Troubleshooting:\n";
    echo "1. Is MySQL/MariaDB running in XAMPP?\n";
    echo "2. Start XAMPP → Click 'Start' for MySQL\n";
    echo "3. Check if port 3306 is available\n";
    echo "4. Verify root user has no password (or update this script)\n";
}
