<?php
$db = mysqli_connect('localhost', 'root', '', 'windeep_finance_new');
$result = mysqli_query($db, 'DESCRIBE loan_guarantors');
echo "loan_guarantors table columns:\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
