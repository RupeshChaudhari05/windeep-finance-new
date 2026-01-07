<?php
$db = mysqli_connect('localhost', 'root', '', 'windeep_finance_new');
$result = mysqli_query($db, "SELECT email, password FROM admin_users WHERE email = 'admin@windeep.com'");
$row = mysqli_fetch_assoc($result);

echo 'Email: ' . $row['email'] . PHP_EOL;
echo 'Password hash: ' . $row['password'] . PHP_EOL;
echo 'Verify admin123: ' . (password_verify('admin123', $row['password']) ? 'YES' : 'NO') . PHP_EOL;
