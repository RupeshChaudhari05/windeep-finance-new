<?php
// Allow CLI execution and bypass 'No direct script access allowed' guard
if (!defined('BASEPATH')) define('BASEPATH', true);
if (!defined('ENVIRONMENT')) define('ENVIRONMENT', 'development');
// Provide env() fallback so config can load under CLI
if (!function_exists('env')) {
    function env($key, $default = null) {
        $v = getenv($key);
        return $v === false ? $default : $v;
    }
}
require __DIR__ . '/../application/config/database.php';
$c = $db['default'];
$mysqli = new mysqli($c['hostname'], $c['username'], $c['password'], $c['database']);
if ($mysqli->connect_errno) {
    echo "DB connect failed: ({$mysqli->connect_errno}) {$mysqli->connect_error}\n";
    exit(1);
}
$sql = "ALTER TABLE `loan_guarantors` ADD COLUMN IF NOT EXISTS `consent_token` VARCHAR(64) NULL AFTER `rejection_reason`;";
if ($mysqli->query($sql) === TRUE) {
    echo "ALTER OK\n";
} else {
    echo "ALTER ERROR: " . $mysqli->error . "\n";
    exit(1);
}
$res = $mysqli->query("SHOW COLUMNS FROM `loan_guarantors` LIKE 'consent_token'");
if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    echo "FOUND: " . json_encode($row) . "\n";
    exit(0);
} else {
    echo "COLUMN NOT FOUND\n";
    exit(1);
}
