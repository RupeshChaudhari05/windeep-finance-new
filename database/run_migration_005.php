<?php
require __DIR__ . '/../index.php';

$path = __DIR__ . '/005_add_waiver_request_fields.sql';
$sql = file_get_contents($path);
$CI =& get_instance();
$CI->load->database();
try {
    $CI->db->query($sql);
    echo "Migration applied: 005_add_waiver_request_fields executed\n";
} catch (Exception $e) {
    echo "Migration failed or already applied: " . $e->getMessage() . "\n";
}
