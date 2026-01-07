<?php
$db = mysqli_connect('localhost', 'root', '', 'windeep_finance_new');
$result = mysqli_query($db, 'DESCRIBE loan_products');
echo "loan_products table columns:\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . "\n";
}
