<?php
/**
 * Setup Fresh Database for Testing
 * Creates windeep_finance_new with complete schema and test data
 */

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║      WINDEEP FINANCE - FRESH DATABASE SETUP                  ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

try {
    $pdo = new PDO('mysql:host=localhost', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Step 1: Create new database
    echo "Step 1: Creating database 'windeep_finance_new'...\n";
    $pdo->exec("DROP DATABASE IF EXISTS windeep_finance_new");
    $pdo->exec("CREATE DATABASE windeep_finance_new CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database created\n\n";
    
    // Connect to new database
    $pdo = new PDO('mysql:host=localhost;dbname=windeep_finance_new', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Step 2: Import schema
    echo "Step 2: Importing database schema...\n";
    $schemaFile = __DIR__ . '/database/schema.sql';
    
    if (file_exists($schemaFile)) {
        $schema = file_get_contents($schemaFile);
        // Remove USE statement if exists
        $schema = preg_replace('/^USE\s+.*;/m', '', $schema);
        $pdo->exec($schema);
        
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "✓ Schema imported: " . count($tables) . " tables created\n\n";
    } else {
        throw new Exception("schema.sql not found!");
    }
    
    // Step 3: Load test data
    echo "Step 3: Loading test data...\n";
    $testDataFile = __DIR__ . '/database/simple_test_data.sql';
    
    if (file_exists($testDataFile)) {
        $testData = file_get_contents($testDataFile);
        // Update USE statement
        $testData = preg_replace('/USE\s+windeep_finance;/', 'USE windeep_finance_new;', $testData);
        $pdo->exec($testData);
        echo "✓ Test data loaded\n\n";
    } else {
        echo "⚠ simple_test_data.sql not found, skipping test data\n\n";
    }
    
    // Step 4: Show summary
    echo "═══════════════════════════════════════════════════════════\n";
    echo "                    SETUP COMPLETE!                         \n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    
    $queries = [
        'Members' => "SELECT COUNT(*) as c FROM members",
        'Test Members (TEST*)' => "SELECT COUNT(*) as c FROM members WHERE member_code LIKE 'TEST%'",
        'Loans' => "SELECT COUNT(*) as c FROM loans",
        'Test Loans (TESTLN*)' => "SELECT COUNT(*) as c FROM loans WHERE loan_number LIKE 'TESTLN%'",
        'Bank Transactions' => "SELECT COUNT(*) as c FROM bank_transactions",
        'Unmapped Transactions' => "SELECT COUNT(*) as c FROM bank_transactions WHERE utr_number LIKE 'TEST-UTR%'"
    ];
    
    foreach ($queries as $label => $query) {
        try {
            $stmt = $pdo->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo str_pad($label . ':', 30) . $result['c'] . "\n";
        } catch (Exception $e) {
            echo str_pad($label . ':', 30) . "N/A\n";
        }
    }
    
    echo "\n═══════════════════════════════════════════════════════════\n";
    echo "                   NEXT STEPS                               \n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    
    echo "1. UPDATE CONFIGURATION:\n";
    echo "   Edit: config/database.php\n";
    echo "   Change: \$db['default']['database'] = 'windeep_finance_new';\n\n";
    
    echo "2. CREATE ADMIN USER (if needed):\n";
    echo "   Run: php create_admin.php\n\n";
    
    echo "3. ACCESS SYSTEM:\n";
    echo "   URL: http://localhost/windeep_finance/\n";
    echo "   Login with your admin credentials\n\n";
    
    echo "4. TEST DATA:\n";
    echo "   - 5 test members (TEST001 to TEST005)\n";
    echo "   - 3 test loans (TESTLN-001, 002, 003)\n";
    echo "   - 10 unmapped transactions for mapping tests\n\n";
    
    echo "5. UPLOAD BANK STATEMENT:\n";
    echo "   File: database/bank_statement_jan2025.xlsx\n";
    echo "   Go to: Bank → Bank Statements → Import\n\n";
    
    echo "✅ READY TO TEST ALL FEATURES!\n\n";
    
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n\n";
    echo "Troubleshooting:\n";
    echo "1. Is MySQL running in XAMPP?\n";
    echo "2. Does database/schema.sql exist?\n";
    echo "3. Check MySQL error log\n";
}
