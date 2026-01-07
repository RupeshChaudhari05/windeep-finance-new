<?php
$db = mysqli_connect('localhost', 'root', '', 'windeep_finance_new');
if (!$db) {
    die('Connection failed: ' . mysqli_connect_error());
}

$result = mysqli_query($db, 'DESCRIBE loan_applications');
echo "loan_applications table columns:\n";
echo str_repeat("=", 50) . "\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
