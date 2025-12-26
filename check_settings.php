<?php
// Check system_settings keys
$db = new mysqli('localhost', 'root', '', 'windeep_finance_new');

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$result = $db->query("SELECT setting_key, setting_value, setting_type FROM system_settings ORDER BY setting_key");

echo "System Settings Keys:\n";
echo str_repeat("=", 80) . "\n";

while ($row = $result->fetch_assoc()) {
    echo sprintf("%-30s | %-30s | %s\n", $row['setting_key'], $row['setting_value'], $row['setting_type']);
}

$db->close();
