<?php
$db = mysqli_connect('localhost', 'root', '', 'windeep_finance_new');
if (!$db) {
    die('Connection failed: ' . mysqli_connect_error());
}

echo "Recent loan applications:\n";
echo str_repeat("=", 80) . "\n";
$result = mysqli_query($db, 'SELECT id, application_number, member_id, requested_amount, purpose, status, created_at FROM loan_applications ORDER BY id DESC LIMIT 5');
while ($row = mysqli_fetch_assoc($result)) {
    echo "ID: {$row['id']} | App#: {$row['application_number']} | Member: {$row['member_id']} | Amount: {$row['requested_amount']} | Purpose: {$row['purpose']} | Status: {$row['status']} | Date: {$row['created_at']}\n";
}

echo "\n\nMember login details (for testing):\n";
echo str_repeat("=", 80) . "\n";
$result = mysqli_query($db, 'SELECT id, member_code, first_name, last_name, phone FROM members WHERE status="active" LIMIT 3');
while ($row = mysqli_fetch_assoc($result)) {
    echo "ID: {$row['id']} | Code: {$row['member_code']} | Name: {$row['first_name']} {$row['last_name']} | Phone: {$row['phone']}\n";
}
