<?php
if (!defined('BASEPATH')) define('BASEPATH', true);
if (!defined('ENVIRONMENT')) define('ENVIRONMENT', 'development');
if (!function_exists('env')) { function env($k,$d=null){$v=getenv($k);return $v===false?$d:$v;} }
require __DIR__ . '/../application/config/database.php';
$c=$db['default'];
$mysqli=new mysqli($c['hostname'],$c['username'],$c['password'],$c['database']);
if($mysqli->connect_errno){echo "DB connect failed: {$mysqli->connect_error}\n"; exit(1);} 
$res=$mysqli->query("SHOW TABLES LIKE 'bank_transactions'");
if($res) echo "Tables matching: " . $res->num_rows . "\n";
while($r=$res->fetch_row()){ echo $r[0] . "\n"; }
$mysqli->close();
