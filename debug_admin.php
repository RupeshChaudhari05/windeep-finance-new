<?php
$db = mysqli_connect('localhost', 'root', '', 'windeep_finance_new');
if (!$db) {
    die('Connection failed: ' . mysqli_connect_error());
}

echo "Admin users in database:\n";
echo str_repeat("=", 60) . "\n";
$result = mysqli_query($db, 'SELECT id, username, email, full_name, role, is_active, created_at FROM admin_users');
while ($row = mysqli_fetch_assoc($result)) {
    echo "ID: {$row['id']} | Username: {$row['username']} | Email: {$row['email']} | Role: {$row['role']} | Active: {$row['is_active']} | Created: {$row['created_at']}\n";
}

echo "\nTesting password verification:\n";
echo str_repeat("=", 60) . "\n";

// Get the admin user
$result = mysqli_query($db, "SELECT password FROM admin_users WHERE username = 'admin'");
$row = mysqli_fetch_assoc($result);
$hashedPassword = $row['password'];

echo "Stored hash: " . $hashedPassword . "\n";

// Test password verification
$testPassword = 'admin123';
$verify = password_verify($testPassword, $hashedPassword);
echo "Password 'admin123' verification: " . ($verify ? 'SUCCESS' : 'FAILED') . "\n";

// Test with wrong password
$wrongPassword = 'wrong';
$verifyWrong = password_verify($wrongPassword, $hashedPassword);
echo "Password 'wrong' verification: " . ($verifyWrong ? 'SUCCESS' : 'FAILED') . "\n";
