<?php
if (!defined('BASEPATH')) define('BASEPATH', true);
if (!defined('ENVIRONMENT')) define('ENVIRONMENT', 'development');
if (!function_exists('env')) {
    function env($k, $d=null){$v=getenv($k);return $v===false?$d:$v;}
}
require __DIR__ . '/../application/config/database.php';
$c=$db['default'];
$mysqli=new mysqli($c['hostname'],$c['username'],$c['password'],$c['database']);
if($mysqli->connect_errno){echo "DB connect failed: {$mysqli->connect_error}\n"; exit(1);} 
$filename='016_add_token_to_loan_guarantors.sql';
$stmt=$mysqli->prepare('SELECT COUNT(*) FROM schema_migrations WHERE filename=?');
$stmt->bind_param('s',$filename); $stmt->execute(); $stmt->bind_result($count); $stmt->fetch(); $stmt->close();
if($count>0){echo "Migration $filename already recorded.\n"; exit(0);} 
$ins=$mysqli->prepare('INSERT INTO schema_migrations (filename) VALUES (?)');
$ins->bind_param('s',$filename); if($ins->execute()){ echo "Recorded migration: $filename\n"; } else { echo "Failed to record migration: " . $mysqli->error . "\n"; exit(1);} $ins->close();
$mysqli->close();
