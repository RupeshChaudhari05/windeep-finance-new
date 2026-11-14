<?php
defined('BASEPATH') OR exit('No direct script access allowed');
// TEMPORARY read-only query helper — runs only SELECT / SHOW. Delete after use.
class Zz_ro extends CI_Controller {
    public function q() {
        if (!is_cli()) exit;
        $sql = (string) getenv('RO_SQL');
        if (!preg_match('/^\s*(SELECT|SHOW)\b/i', $sql) || preg_match('/\b(INSERT|UPDATE|DELETE|REPLACE|ALTER|DROP|CREATE|TRUNCATE|GRANT|CALL)\b/i', $sql)) exit("read-only only\n");
        foreach ($this->db->query($sql)->result_array() as $r) echo json_encode($r), "\n";
    }
}
