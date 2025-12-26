<?php
// Parse DB config defaults from application/config/database.php (safe without bootstrapping)
$cfg = file_get_contents(__DIR__ . '/../application/config/database.php');
function pick($cfg, $key) {
    // Try env('KEY', 'value') pattern
    if (preg_match("/'$key'\s*=>\s*env\([^,]+,\s*'([^']+)'\)/", $cfg, $m)) return $m[1];
    // Try simple 'key' => 'value'
    if (preg_match("/'$key'\s*=>\s*'([^']+)'/", $cfg, $m)) return $m[1];
    return null;
}
$host = pick($cfg, 'hostname') ?: 'localhost';
$user = pick($cfg, 'username') ?: 'root';
$pass = pick($cfg, 'password') ?: '';
$db   = pick($cfg, 'database') ?: '';

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_errno) {
    echo "DB connect failed: " . $mysqli->connect_error . "\n";
    exit(1);
}
$cols = ['waiver_reason','waiver_approved_by','waiver_requested_by','waiver_requested_at','waiver_approved_at','waiver_denied_by','waiver_denied_at','waiver_denied_reason','waiver_requested_amount'];
$found = [];
foreach ($cols as $col) {
    $sql = sprintf("SELECT COUNT(*) as c FROM information_schema.columns WHERE table_schema = '%s' AND table_name = 'fines' AND column_name = '%s'", $mysqli->real_escape_string($db), $mysqli->real_escape_string($col));
    $res = $mysqli->query($sql);
    $row = $res->fetch_assoc();
    $found[$col] = ($row['c'] > 0) ? 'YES' : 'NO';
}
foreach ($found as $c => $v) {
    echo "$c: $v\n";
}
$mysqli->close();
