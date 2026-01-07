<?php
// Simulate admin login process
echo "Testing admin login process...\n\n";

// 1. Check database connection
$db = mysqli_connect('localhost', 'root', '', 'windeep_finance_new');
if (!$db) {
    die('Database connection failed: ' . mysqli_connect_error());
}
echo "✓ Database connection successful\n";

// 2. Check admin user exists
$result = mysqli_query($db, "SELECT id, username, email, password, is_active FROM admin_users WHERE email = 'admin@windeep.com'");
$admin = mysqli_fetch_assoc($result);
if (!$admin) {
    die('✗ Admin user not found');
}
echo "✓ Admin user found: {$admin['username']} ({$admin['email']})\n";

// 3. Check if active
if ($admin['is_active'] != 1) {
    die('✗ Admin user is not active');
}
echo "✓ Admin user is active\n";

// 4. Test password
$password = 'admin123';
if (!password_verify($password, $admin['password'])) {
    die('✗ Password verification failed');
}
echo "✓ Password verification successful\n";

// 5. Check session directory exists
$session_path = session_save_path();
if (!$session_path) {
    $session_path = sys_get_temp_dir();
}
echo "Session save path: $session_path\n";

if (!is_writable($session_path)) {
    echo "⚠ Session path is not writable\n";
} else {
    echo "✓ Session path is writable\n";
}

echo "\nAll checks passed! Login should work.\n";
echo "Try accessing: http://localhost/windeep_finance/admin\n";
echo "Use email: admin@windeep.com\n";
echo "Use password: admin123\n";
