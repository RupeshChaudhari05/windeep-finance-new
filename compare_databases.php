<?php
// Compare two databases
define('BASEPATH', __DIR__ . '/system/');
define('ENVIRONMENT', 'development');

function env($key, $default = null) {
    return getenv($key) ?: $default;
}

// Connect to both databases
$db1_name = 'windeep_finance_new';
$db2_name = 'windeep_finance';

$conn1 = mysqli_connect('localhost', 'root', '', $db1_name);
$conn2 = mysqli_connect('localhost', 'root', '', $db2_name);

if (!$conn1) {
    die("Cannot connect to $db1_name: " . mysqli_connect_error());
}

if (!$conn2) {
    die("Cannot connect to $db2_name: " . mysqli_connect_error());
}

echo "=== DATABASE COMPARISON ===\n\n";

// Get tables from DB1
$result1 = mysqli_query($conn1, "SHOW TABLES");
$tables1 = [];
while ($row = mysqli_fetch_array($result1)) {
    $tables1[] = $row[0];
}

// Get tables from DB2
$result2 = mysqli_query($conn2, "SHOW TABLES");
$tables2 = [];
while ($row = mysqli_fetch_array($result2)) {
    $tables2[] = $row[0];
}

echo "DATABASE 1: $db1_name\n";
echo "Total tables: " . count($tables1) . "\n";
echo "Tables: " . implode(", ", $tables1) . "\n\n";

echo "DATABASE 2: $db2_name\n";
echo "Total tables: " . count($tables2) . "\n";
echo "Tables: " . implode(", ", $tables2) . "\n\n";

// Tables only in DB1
$only_in_db1 = array_diff($tables1, $tables2);
if (!empty($only_in_db1)) {
    echo "TABLES ONLY IN $db1_name:\n";
    foreach ($only_in_db1 as $table) {
        // Get row count
        $result = mysqli_query($conn1, "SELECT COUNT(*) as cnt FROM `$table`");
        $row = mysqli_fetch_assoc($result);
        echo "  - $table (Rows: {$row['cnt']})\n";
    }
    echo "\n";
}

// Tables only in DB2
$only_in_db2 = array_diff($tables2, $tables1);
if (!empty($only_in_db2)) {
    echo "TABLES ONLY IN $db2_name:\n";
    foreach ($only_in_db2 as $table) {
        // Get row count
        $result = mysqli_query($conn2, "SELECT COUNT(*) as cnt FROM `$table`");
        $row = mysqli_fetch_assoc($result);
        echo "  - $table (Rows: {$row['cnt']})\n";
    }
    echo "\n";
}

// Common tables
$common = array_intersect($tables1, $tables2);
echo "COMMON TABLES: " . count($common) . "\n";
foreach ($common as $table) {
    $result1 = mysqli_query($conn1, "SELECT COUNT(*) as cnt FROM `$table`");
    $row1 = mysqli_fetch_assoc($result1);
    
    $result2 = mysqli_query($conn2, "SELECT COUNT(*) as cnt FROM `$table`");
    $row2 = mysqli_fetch_assoc($result2);
    
    $diff = '';
    if ($row1['cnt'] != $row2['cnt']) {
        $diff = " [DIFF: {$row1['cnt']} vs {$row2['cnt']}]";
    }
    echo "  - $table: $db1_name({$row1['cnt']}) vs $db2_name({$row2['cnt']})$diff\n";
}

echo "\n=== RECOMMENDATION ===\n";
if (count($tables1) > count($tables2)) {
    echo "✓ Use $db1_name as primary database (has " . count($only_in_db1) . " more tables)\n";
} elseif (count($tables2) > count($tables1)) {
    echo "✓ Use $db2_name as primary database (has " . count($only_in_db2) . " more tables)\n";
} else {
    echo "Both databases have the same number of tables\n";
}

mysqli_close($conn1);
mysqli_close($conn2);
