<?php
$db = new mysqli('localhost', 'root', '', 'windeep_finance_new');
if ($db->connect_error) die('Connection failed: ' . $db->connect_error);
$sql = "ALTER TABLE `fine_rules` ADD COLUMN IF NOT EXISTS `description` VARCHAR(255) NULL AFTER `rule_name`";
if ($db->query($sql) === TRUE) {
    echo "Migration applied: description column added or already exists\n";
} else {
    echo "Migration failed: " . $db->error . "\n";
}
$db->close();
