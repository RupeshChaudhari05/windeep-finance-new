<?php
require __DIR__ . '/../index.php';
$path = __DIR__ . '/006_add_waiver_requested_amount.sql';
$sql = file_get_contents($path);
$CI =& get_instance();
$CI->load->database();
try {
    $CI->db->query($sql);
    echo "Migration applied: 006_add_waiver_requested_amount executed\n";
} catch (Exception $e) {
    echo "Migration failed or already applied: " . $e->getMessage() . "\n";
}
