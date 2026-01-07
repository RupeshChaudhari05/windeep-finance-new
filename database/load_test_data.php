<?php
/**
 * Test Data Loader
 * Run this script to load comprehensive test data
 * URL: http://localhost/windeep_finance/database/load_test_data.php
 */

// Database connection
$host = 'localhost';
$db = 'windeep_finance';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Loading Test Data...</h2>";
    echo "<pre>";
    
    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/full_test_data_with_transactions.sql');
    
    // Remove comments and split by semicolon
    $sql = preg_replace('/--[^\n]*\n/', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    
    // Execute SQL
    $pdo->exec($sql);
    
    echo "✓ Test data loaded successfully!\n\n";
    
    // Show summary
    echo "=== DATA SUMMARY ===\n";
    
    $queries = [
        'Members' => "SELECT COUNT(*) as count FROM members WHERE member_code LIKE 'MEMB%'",
        'Savings Accounts' => "SELECT COUNT(*) as count FROM savings_accounts",
        'Loan Applications' => "SELECT COUNT(*) as count FROM loan_applications",
        'Active Loans' => "SELECT COUNT(*) as count FROM loans",
        'Loan Installments' => "SELECT COUNT(*) as count FROM loan_installments",
        'Loan Payments' => "SELECT COUNT(*) as count FROM loan_payments",
        'Fines' => "SELECT COUNT(*) as count FROM fines",
        'Bank Transactions' => "SELECT COUNT(*) as count FROM bank_transactions",
        'Unmapped Transactions' => "SELECT COUNT(*) as count FROM bank_transactions WHERE mapping_status = 'unmapped'"
    ];
    
    foreach ($queries as $label => $query) {
        $stmt = $pdo->query($query);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo str_pad($label . ':', 25) . $result['count'] . "\n";
    }
    
    echo "\n=== ACTIVE LOANS ===\n";
    $stmt = $pdo->query("
        SELECT 
            l.loan_number,
            CONCAT(m.first_name, ' ', m.last_name) AS member_name,
            CONCAT('₹', FORMAT(l.principal_amount, 2)) AS principal,
            l.tenure_months AS tenure,
            CONCAT('₹', FORMAT(l.emi_amount, 2)) AS emi,
            l.status,
            (SELECT COUNT(*) FROM loan_installments WHERE loan_id = l.id AND status = 'paid') AS paid,
            (SELECT COUNT(*) FROM loan_installments WHERE loan_id = l.id AND status = 'pending') AS pending,
            (SELECT COUNT(*) FROM loan_installments WHERE loan_id = l.id AND status = 'overdue') AS overdue
        FROM loans l
        JOIN members m ON m.id = l.member_id
        ORDER BY l.id
    ");
    
    $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo str_pad('Loan Number', 18) . str_pad('Member', 20) . str_pad('Principal', 15) . 
         str_pad('EMI', 15) . str_pad('Status', 12) . str_pad('Paid', 6) . 
         str_pad('Pending', 8) . str_pad('Overdue', 8) . "\n";
    echo str_repeat('-', 110) . "\n";
    
    foreach ($loans as $loan) {
        echo str_pad($loan['loan_number'], 18) . 
             str_pad($loan['member_name'], 20) . 
             str_pad($loan['principal'], 15) . 
             str_pad($loan['emi'], 15) . 
             str_pad($loan['status'], 12) . 
             str_pad($loan['paid'], 6) . 
             str_pad($loan['pending'], 8) . 
             str_pad($loan['overdue'], 8) . "\n";
    }
    
    echo "\n=== UNMAPPED TRANSACTIONS (For Testing) ===\n";
    $stmt = $pdo->query("
        SELECT 
            bt.id,
            DATE_FORMAT(bt.transaction_date, '%d-%m-%Y') AS date,
            CONCAT('₹', FORMAT(bt.amount, 2)) AS amount,
            bt.utr_number,
            bt.description,
            CONCAT(IFNULL(m.first_name, ''), ' ', IFNULL(m.last_name, '')) AS detected_member
        FROM bank_transactions bt
        LEFT JOIN members m ON m.id = bt.detected_member_id
        WHERE bt.mapping_status = 'unmapped'
        ORDER BY bt.transaction_date DESC, bt.id
        LIMIT 15
    ");
    
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo str_pad('ID', 6) . str_pad('Date', 14) . str_pad('Amount', 15) . 
         str_pad('UTR Number', 20) . str_pad('Description', 45) . "Detected Member\n";
    echo str_repeat('-', 120) . "\n";
    
    foreach ($transactions as $txn) {
        echo str_pad($txn['id'], 6) . 
             str_pad($txn['date'], 14) . 
             str_pad($txn['amount'], 15) . 
             str_pad($txn['utr_number'], 20) . 
             str_pad(substr($txn['description'], 0, 43), 45) . 
             $txn['detected_member'] . "\n";
    }
    
    echo "\n=== FILES READY ===\n";
    echo "✓ Excel File: database/bank_statement_jan2025.xlsx\n";
    echo "✓ CSV File: database/bank_statement_for_import.csv\n";
    
    echo "\n=== NEXT STEPS ===\n";
    echo "1. Login to admin panel\n";
    echo "2. Go to Bank > Bank Statements\n";
    echo "3. Upload bank_statement_jan2025.xlsx\n";
    echo "4. Map the unmapped transactions\n";
    echo "5. Test loan payments, EMI screens, reports\n";
    echo "6. Test split payment mapping feature\n";
    
    echo "\n✅ All test data loaded successfully!\n";
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<pre style='color: red;'>";
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nPlease check:\n";
    echo "1. Database credentials are correct\n";
    echo "2. MySQL server is running\n";
    echo "3. Database 'windeep_finance' exists\n";
    echo "4. Required tables exist (run schema.sql first)\n";
    echo "</pre>";
}
