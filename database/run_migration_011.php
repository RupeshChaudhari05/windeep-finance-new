<?php
require __DIR__ . '/../index.php';

$path = __DIR__ . '/migrations/011_create_loan_foreclosure_requests_table.sql';
$sql = file_get_contents($path);
$CI =& get_instance();
$CI->load->database();
try {
    $CI->db->query($sql);
    echo "Migration applied: 011_create_loan_foreclosure_requests_table executed\n";
} catch (Exception $e) {
    echo "Migration failed or already applied: " . $e->getMessage() . "\n";
}