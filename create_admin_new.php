<?php
// Create admin user for windeep_finance_new

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'windeep_finance_new';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if admin exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM admin_users WHERE username = 'admin'");
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        echo "✓ Admin user already exists\n";
    } else {
        // Create admin user
        // Password: admin123 (hashed with bcrypt)
        $hashedPassword = password_hash('admin123', PASSWORD_BCRYPT);
        
        $sql = "INSERT INTO admin_users (username, password, email, full_name, role, is_active, created_at) 
                VALUES ('admin', :password, 'admin@windeep.com', 'System Administrator', 'admin', 1, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['password' => $hashedPassword]);
        
        echo "✓ Admin user created successfully!\n";
        echo "  Username: admin\n";
        echo "  Password: admin123\n";
    }
    
    // Show all users
    $stmt = $pdo->query("SELECT id, username, role, is_active FROM admin_users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nCurrent users:\n";
    foreach ($users as $user) {
        echo "  - {$user['username']} ({$user['role']}) - " . ($user['is_active'] ? 'Active' : 'Inactive') . "\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
