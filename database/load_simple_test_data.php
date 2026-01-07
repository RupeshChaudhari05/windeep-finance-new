<?php
/**
 * Simple Test Data Loader
 * Loads basic test data without complex dependencies
 */

$host = 'localhost';
$db = 'windeep_finance';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<!DOCTYPE html><html><head><style>
    body { font-family: Arial; margin: 40px; background: #f5f5f5; }
    .container { background: white; padding: 30px; border-radius: 8px; max-width: 1200px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h2 { color: #2c3e50; }
    pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
    .success { color: #27ae60; font-weight: bold; }
    .error { color: #e74c3c; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background: #3498db; color: white; }
    tr:hover { background: #f5f5f5; }
    .badge { display: inline-block; padding: 4px 8px; border-radius: 3px; font-size: 12px; }
    .badge-success { background: #27ae60; color: white; }
    .badge-warning { background: #f39c12; color: white; }
    .badge-danger { background: #e74c3c; color: white; }
    .badge-info { background: #3498db; color: white; }
    </style></head><body><div class='container'>";
    
    echo "<h2>🧪 Loading Test Data...</h2>";
    
    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/simple_test_data.sql');
    
    // Execute SQL
    $pdo->exec($sql);
    
    echo "<p class='success'>✓ Test data loaded successfully!</p>";
    
    // Show summary
    echo "<h3>📊 Data Summary</h3>";
    echo "<table>";
    echo "<tr><th>Item</th><th>Count</th></tr>";
    
    $queries = [
        'Members' => "SELECT COUNT(*) as count FROM members WHERE member_code LIKE 'TEST%'",
        'Savings Accounts' => "SELECT COUNT(*) as count FROM savings_accounts WHERE account_number LIKE 'SAV-TEST%'",
        'Loan Applications' => "SELECT COUNT(*) as count FROM loan_applications WHERE application_number LIKE 'TEST%'",
        'Active Loans' => "SELECT COUNT(*) as count FROM loans WHERE loan_number LIKE 'TESTLN%'",
        'Loan Installments' => "SELECT COUNT(*) as count FROM loan_installments WHERE loan_id IN (SELECT id FROM loans WHERE loan_number LIKE 'TESTLN%')",
        'Fines' => "SELECT COUNT(*) as count FROM fines WHERE fine_code LIKE 'TEST%'",
        'Unmapped Transactions' => "SELECT COUNT(*) as count FROM bank_transactions WHERE utr_number LIKE 'TEST-UTR%'"
    ];
    
    foreach ($queries as $label => $query) {
        $stmt = $pdo->query($query);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<tr><td>$label</td><td><strong>{$result['count']}</strong></td></tr>";
    }
    echo "</table>";
    
    // Show loans
    echo "<h3>💳 Active Loans</h3>";
    $stmt = $pdo->query("
        SELECT 
            l.loan_number,
            CONCAT(m.first_name, ' ', m.last_name) AS member,
            m.phone,
            l.principal_amount,
            l.emi_amount,
            l.status,
            (SELECT COUNT(*) FROM loan_installments WHERE loan_id = l.id AND status = 'paid') AS paid_emis,
            (SELECT COUNT(*) FROM loan_installments WHERE loan_id = l.id AND status = 'pending') AS pending_emis,
            (SELECT COUNT(*) FROM loan_installments WHERE loan_id = l.id AND status = 'overdue') AS overdue_emis
        FROM loans l
        JOIN members m ON m.id = l.member_id
        WHERE l.loan_number LIKE 'TESTLN%'
        ORDER BY l.id
    ");
    
    $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Loan Number</th><th>Member</th><th>Phone</th><th>Principal</th><th>EMI</th><th>Status</th><th>Paid</th><th>Pending</th><th>Overdue</th></tr>";
    
    foreach ($loans as $loan) {
        $statusClass = $loan['status'] == 'active' ? 'badge-success' : 'badge-warning';
        echo "<tr>";
        echo "<td><strong>{$loan['loan_number']}</strong></td>";
        echo "<td>{$loan['member']}</td>";
        echo "<td>{$loan['phone']}</td>";
        echo "<td>₹" . number_format($loan['principal_amount'], 2) . "</td>";
        echo "<td>₹" . number_format($loan['emi_amount'], 2) . "</td>";
        echo "<td><span class='badge $statusClass'>" . strtoupper($loan['status']) . "</span></td>";
        echo "<td>{$loan['paid_emis']}</td>";
        echo "<td>{$loan['pending_emis']}</td>";
        echo "<td>" . ($loan['overdue_emis'] > 0 ? "<span class='badge badge-danger'>{$loan['overdue_emis']}</span>" : "0") . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show unmapped transactions
    echo "<h3>🏦 Unmapped Bank Transactions (For Testing)</h3>";
    $stmt = $pdo->query("
        SELECT 
            bt.id,
            DATE_FORMAT(bt.transaction_date, '%d-%m-%Y') AS date,
            bt.amount,
            bt.utr_number,
            bt.description
        FROM bank_transactions bt
        WHERE bt.utr_number LIKE 'TEST-UTR%'
        ORDER BY bt.id
        LIMIT 10
    ");
    
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Date</th><th>Amount</th><th>UTR Number</th><th>Description</th></tr>";
    
    foreach ($transactions as $txn) {
        echo "<tr>";
        echo "<td>{$txn['id']}</td>";
        echo "<td>{$txn['date']}</td>";
        echo "<td>₹" . number_format($txn['amount'], 2) . "</td>";
        echo "<td><code>{$txn['utr_number']}</code></td>";
        echo "<td>{$txn['description']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show next steps
    echo "<h3>📋 Next Steps</h3>";
    echo "<ol>";
    echo "<li>Login to admin panel</li>";
    echo "<li>Go to <strong>Members</strong> → View members with code starting with 'TEST'</li>";
    echo "<li>Go to <strong>Loans</strong> → View active loans (TESTLN-001, TESTLN-002, TESTLN-003)</li>";
    echo "<li>Go to <strong>Bank → Transactions</strong> → Map the unmapped transactions</li>";
    echo "<li>Test payment recording for loans</li>";
    echo "<li>Test EMI schedule screens</li>";
    echo "<li>Test split payment mapping with TEST-UTR-006 (₹15,000)</li>";
    echo "<li>Test reports and exports</li>";
    echo "</ol>";
    
    echo "<div style='background: #e8f5e9; padding: 15px; border-radius: 4px; margin-top: 20px;'>";
    echo "<h4 style='margin-top: 0; color: #27ae60;'>✅ All Test Data Ready!</h4>";
    echo "<p><strong>Test Members Phone Numbers:</strong> 9876543210 to 9876543214</p>";
    echo "<p><strong>Excel File:</strong> database/bank_statement_jan2025.xlsx (20 transactions)</p>";
    echo "<p><strong>CSV File:</strong> database/bank_statement_for_import.csv</p>";
    echo "</div>";
    
    echo "</div></body></html>";
    
} catch (PDOException $e) {
    echo "<div class='container'>";
    echo "<h2 class='error'>❌ Error Loading Test Data</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<h3>Troubleshooting:</h3>";
    echo "<ol>";
    echo "<li>Ensure MySQL/MariaDB server is running</li>";
    echo "<li>Check database credentials in script</li>";
    echo "<li>Verify 'windeep_finance' database exists</li>";
    echo "<li>Run schema.sql first if tables don't exist</li>";
    echo "</ol>";
    echo "</div></body></html>";
}
