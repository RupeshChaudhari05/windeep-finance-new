<?php
if (!defined('BASEPATH')) define('BASEPATH', true);
if (!defined('ENVIRONMENT')) define('ENVIRONMENT', 'development');
if (!function_exists('env')) { function env($k,$d=null){$v=getenv($k);return $v===false?$d:$v;} }
require __DIR__ . '/../application/config/database.php';
$c=$db['default'];
$mysqli=new mysqli($c['hostname'],$c['username'],$c['password'],$c['database']);
if($mysqli->connect_errno){echo "DB connect failed: {$mysqli->connect_error}\n"; exit(1);} 
$fname = $argv[1] ?? null;
if(!$fname){ echo "Usage: php run_single_migration.php <filename>\n"; exit(1);} 
$dir = __DIR__ . '/../database/migrations';
$path = $dir . '/' . $fname;
if(!file_exists($path)){ echo "File not found: $path\n"; exit(1);} 
$sql = file_get_contents($path);
echo "Running migration $fname against DB {$c['database']}\n";
if ($mysqli->multi_query($sql)){
    do {
        if ($res = $mysqli->store_result()) {
            echo "Result rows: " . $res->num_rows . "\n";
            $res->free();
        }
    } while ($mysqli->more_results() && $mysqli->next_result());
    if ($mysqli->errno) { echo "ERROR: ({$mysqli->errno}) {$mysqli->error}\n"; exit(1);} else { echo "Done OK\n"; }
} else {
    echo "Multi-query failed: ({$mysqli->errno}) {$mysqli->error}\n"; exit(1);
}
$mysqli->close();
