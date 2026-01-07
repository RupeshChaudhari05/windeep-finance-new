<?php
$db = mysqli_connect('localhost', 'root', '', 'windeep_finance_new');
if (!$db) {
    die('Connection failed: ' . mysqli_connect_error());
}

// Hash the correct password
$hashedPassword = password_hash('admin123', PASSWORD_BCRYPT);
echo "New hashed password for 'admin123': $hashedPassword\n";

// Update the admin user password
$sql = "UPDATE admin_users SET password = ? WHERE username = 'admin'";
$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, 's', $hashedPassword);

if (mysqli_stmt_execute($stmt)) {
    echo "✓ Admin password updated successfully!\n";

    // Verify the update
    $result = mysqli_query($db, "SELECT password FROM admin_users WHERE username = 'admin'");
    $row = mysqli_fetch_assoc($result);
    $storedHash = $row['password'];

    $verify = password_verify('admin123', $storedHash);
    echo "Password verification test: " . ($verify ? 'SUCCESS' : 'FAILED') . "\n";
} else {
    echo "✗ Failed to update password: " . mysqli_error($db) . "\n";
}

mysqli_close($db);
