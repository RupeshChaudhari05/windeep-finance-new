<?php
use PHPUnit\Framework\TestCase;

// Minimal CI stubs
if (!defined('BASEPATH')) define('BASEPATH', true);
if (!class_exists('CI_Model')) { class CI_Model {} }

require_once __DIR__ . '/../application/core/MY_Model.php';
require_once __DIR__ . '/../application/models/Savings_scheme_model.php';

class SavingsSchemeTest extends TestCase {
    public function test_save_new_scheme_inserts_and_returns_id() {
        $model = new Savings_scheme_model();
        // fake db that emulates insert
        $model->db = new class {
            public $last_id = 123;
            public $last_insert = [];
            public function insert($table, $data) { $this->last_insert = $data; return true; }
            public function insert_id() { return $this->last_id; }
            public function where() { return $this; }
            public function update() { return true; }
        };

        $data = ['scheme_name' => 'Test Scheme', 'min_deposit' => 500, 'interest_rate' => 5.0];
        $id = $model->save_scheme($data);
        $this->assertEquals(123, $id);
    }

    public function test_toggle_scheme_calls_update_and_returns_true() {
        $model = new Savings_scheme_model();
        $called = false;
        $model->db = new class(&$called) {
            private $called;
            public function __construct(&$called) { $this->called = &$called; }
            public function where() { return $this; }
            public function update($table, $data) { $this->called = true; return true; }
        };

        $this->assertTrue($model->toggle_scheme(5, 0));
        $this->assertTrue($called, 'update should be called');
    }
}