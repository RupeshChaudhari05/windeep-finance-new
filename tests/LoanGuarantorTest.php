<?php
use PHPUnit\Framework\TestCase;

if (!defined('BASEPATH')) define('BASEPATH', true);
if (!class_exists('CI_Model')) { class CI_Model {} }
require_once __DIR__ . '/../application/core/MY_Model.php';
require_once __DIR__ . '/../application/models/Loan_model.php';

class LoanGuarantorTest extends TestCase {

    public function test_get_accepted_guarantor_count_returns_integer() {
        $model = new Loan_model();
        $model->db = new class {
            public function where($a, $b = null) { return $this; }
            public function count_all_results($table) { return 3; }
        };

        $this->assertEquals(3, $model->get_accepted_guarantor_count(42));
    }

    public function test_update_guarantor_consent_sets_fields_and_returns_true() {
        $model = new Loan_model();
        $model->input = new class {
            public function ip_address() { return '127.0.0.1'; }
        };

        $model->db = new class {
            public $captured = [];
            public function where($col, $val) { $this->captured['where'] = [$col => $val]; return $this; }
            public function update($table, $data) { $this->captured['update'] = [$table => $data]; return true; }
        };

        $res = $model->update_guarantor_consent(7, 'accepted', 'OK');
        $this->assertTrue($res);
        $this->assertEquals(['id' => 7], $model->db->captured['where']);
        $this->assertArrayHasKey('loan_guarantors', $model->db->captured['update']);
        $data = $model->db->captured['update']['loan_guarantors'];
        $this->assertEquals('accepted', $data['consent_status']);
        $this->assertArrayHasKey('consent_date', $data);
        $this->assertEquals('127.0.0.1', $data['consent_ip']);
        $this->assertEquals('OK', $data['consent_remarks']);
    }
}