<?php
if (!defined('BASEPATH')) define('BASEPATH', true);
if (!defined('ENVIRONMENT')) define('ENVIRONMENT', 'development');
if (!function_exists('env')) { function env($k,$d=null){$v=getenv($k);return $v===false?$d:$v;} }
require __DIR__ . '/../application/config/database.php';
$c=$db['default'];
$mysqli=new mysqli($c['hostname'],$c['username'],$c['password'],$c['database']);
if($mysqli->connect_errno){echo "DB connect failed: {$mysqli->connect_error}\n"; exit(1);} 

// Find or create application
$aid = null;
$res = $mysqli->query('SELECT id, member_id FROM loan_applications LIMIT 1');
if ($res && $row = $res->fetch_assoc()) {
    $aid = (int)$row['id'];
    $app_member = (int)$row['member_id'];
    echo "Using existing application id: $aid (member $app_member)\n";
} else {
    // Need a member and product
    $m = $mysqli->query('SELECT id FROM members LIMIT 2');
    if (!$m || $m->num_rows < 1) { echo "No members available to create application.\n"; exit(1); }
    $members = [];
    while($r=$m->fetch_assoc()) $members[] = $r['id'];
    $prod = $mysqli->query('SELECT id FROM loan_products LIMIT 1');
    if (!$prod || $prod->num_rows < 1) { echo "No loan products available to create application.\n"; exit(1); }
    $prodid = $prod->fetch_assoc()['id'];
    $memberid = $members[0];
    $appnum = 'TESTAPP'.time();
    $stmt = $mysqli->prepare('INSERT INTO loan_applications (application_number, member_id, loan_product_id, requested_amount, requested_tenure_months, purpose, application_date, created_at, status) VALUES (?,?,?,?,?,?,?,?,?)');
    $status='pending';
    $amt=100000; $ten=12; $purpose='Test app'; $date=date('Y-m-d'); $created=date('Y-m-d H:i:s');
    $stmt->bind_param('siiidssss', $appnum, $memberid, $prodid, $amt, $ten, $purpose, $date, $created, $status);
    if (!$stmt->execute()) { echo "Failed to create test application: " . $mysqli->error . "\n"; exit(1);} 
    $aid = $stmt->insert_id;
    $app_member = $memberid;
    echo "Created test application id: $aid (member $app_member)\n";
    $stmt->close();
}

// Choose guarantor member different from applicant and not already a guarantor for this application
$g = $mysqli->query('SELECT id FROM members WHERE id != '.intval($app_member));
$guarantor_id = null;
while ($row = $g->fetch_assoc()) {
    $mid = $row['id'];
    $check = $mysqli->query('SELECT COUNT(*) as c FROM loan_guarantors WHERE loan_application_id = '.intval($aid).' AND guarantor_member_id = '.intval($mid));
    $cnt = $check->fetch_assoc()['c'];
    if ($cnt == 0) { $guarantor_id = $mid; break; }
}
if (!$guarantor_id) {
    // Create a new small member record to act as guarantor if none free
    $stmtm = $mysqli->prepare('INSERT INTO members (member_code, first_name, last_name, email, phone, status, created_at) VALUES (?,?,?,?,?,?,?)');
    $code = 'TST' . time(); $fn='Test'; $ln='Guar'; $em=$code . '@example.com'; $ph = '9' . substr((string)time() . (string)rand(1000,9999), -10); $st='active'; $created=date('Y-m-d H:i:s');
    $stmtm->bind_param('sssssss', $code, $fn, $ln, $em, $ph, $st, $created);
    if (!$stmtm->execute()) { echo "Failed to create test member: " . $mysqli->error . "\n"; exit(1);} $guarantor_id = $stmtm->insert_id; $stmtm->close();
    echo "Created test guarantor member id: $guarantor_id\n";
}

// Insert into loan_guarantors
$token = bin2hex(random_bytes(16));
$guarantee_amount = 50000;
$created_at = date('Y-m-d H:i:s');
$stmt = $mysqli->prepare('INSERT INTO loan_guarantors (loan_application_id, guarantor_member_id, guarantee_amount, relationship, consent_status, consent_token, created_at) VALUES (?,?,?,?,?,?,?)');
$rel = NULL; $status='pending';
if (!$stmt) { echo "Prepare failed: " . $mysqli->error . "\n"; exit(1); }
$stmt->bind_param('iiissss', $aid, $guarantor_id, $guarantee_amount, $rel, $status, $token, $created_at);
if (!$stmt->execute()) { echo "Failed to insert guarantor: " . $mysqli->error . "\n"; exit(1);} 
echo "Inserted guarantor id: " . $stmt->insert_id . " with token $token\n";
$stmt->close();
$mysqli->close();
