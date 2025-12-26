<?php
/**
 * Create Admin User Script
 * 
 * Usage: php create_admin.php
 */

// Define BASEPATH to allow includes
define('BASEPATH', __DIR__ . '/system/');

// Load environment
require_once __DIR__ . '/application/helpers/env_helper.php';
load_env(__DIR__ . '/.env');

// Database connection
$mysqli = new mysqli(
    env('DB_HOST', 'localhost'),
    env('DB_USERNAME', 'root'),
    env('DB_PASSWORD', ''),
    env('DB_NAME', 'windeep_finance_new')
);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "Connected to database successfully!\n\n";

// Admin credentials
$username = 'admin';
$email = 'admin@windeep.com';
$password = 'admin123';  // Change this to your desired password
$full_name = 'System Administrator';

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Check if admin exists
$check = $mysqli->query("SELECT id FROM admin_users WHERE username = '$username' OR email = '$email'");

if ($check->num_rows > 0) {
    // Update existing admin
    $sql = "UPDATE admin_users SET 
            password = '$hashed_password',
            full_name = '$full_name',
            role = 'super_admin',
            is_active = 1,
            updated_at = NOW()
            WHERE username = '$username' OR email = '$email'";
    
    if ($mysqli->query($sql)) {
        echo "✓ Admin user updated successfully!\n";
    } else {
        echo "✗ Error updating admin: " . $mysqli->error . "\n";
    }
} else {
    // Create new admin
    $sql = "INSERT INTO admin_users (username, email, password, full_name, role, is_active, created_at, updated_at)
            VALUES ('$username', '$email', '$hashed_password', '$full_name', 'super_admin', 1, NOW(), NOW())";
    
    if ($mysqli->query($sql)) {
        echo "✓ Admin user created successfully!\n";
    } else {
        echo "✗ Error creating admin: " . $mysqli->error . "\n";
    }
}

echo "\n";
echo "=================================\n";
echo "Admin Credentials:\n";
echo "=================================\n";
echo "Username/Email: $email\n";
echo "Password: $password\n";
echo "=================================\n";
echo "\nYou can now login at: " . env('APP_URL') . "/admin/login\n";

$mysqli->close();
