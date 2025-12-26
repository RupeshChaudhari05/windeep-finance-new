<?php
// Ensure waiver-related columns exist in `fines` table, adding them if missing.
$cfg = file_get_contents(__DIR__ . '/../application/config/database.php');
function pick($cfg, $key) {
    if (preg_match("/'$key'\s*=>\s*env\([^,]+,\s*'([^']+)'\)/", $cfg, $m)) return $m[1];
    if (preg_match("/'$key'\s*=>\s*'([^']+)'/", $cfg, $m)) return $m[1];
    return null;
}
$host = pick($cfg, 'hostname') ?: 'localhost';
$user = pick($cfg, 'username') ?: 'root';
$pass = pick($cfg, 'password') ?: '';
$db   = pick($cfg, 'database') ?: '';
$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_errno) { echo "DB connect failed: " . $mysqli->connect_error . "\n"; exit(1); }

$columns = [
    'waiver_requested_by' => 'INT UNSIGNED NULL',
    'waiver_requested_at' => 'TIMESTAMP NULL',
    'waiver_requested_amount' => 'DECIMAL(15,2) NULL',
    'waiver_approved_at' => 'TIMESTAMP NULL',
    'waiver_denied_by' => 'INT UNSIGNED NULL',
    'waiver_denied_at' => 'TIMESTAMP NULL',
    'waiver_denied_reason' => "VARCHAR(255) NULL"
];

foreach ($columns as $col => $definition) {
    $sql = sprintf("SELECT COUNT(*) as c FROM information_schema.columns WHERE table_schema = '%s' AND table_name = 'fines' AND column_name = '%s'", $mysqli->real_escape_string($db), $mysqli->real_escape_string($col));
    $res = $mysqli->query($sql);
    $row = $res->fetch_assoc();
    if ($row['c'] == 0) {
        $alter = sprintf("ALTER TABLE `fines` ADD COLUMN `%s` %s", $col, $definition);
        if ($mysqli->query($alter)) {
            echo "Added column: $col\n";
        } else {
            echo "Failed to add $col: " . $mysqli->error . "\n";
        }
    } else {
        echo "Exists: $col\n";
    }
}

$mysqli->close();
