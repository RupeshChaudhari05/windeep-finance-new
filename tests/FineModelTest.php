<?php
use PHPUnit\Framework\TestCase;

// Define BASEPATH to allow model includes
if (!defined('BASEPATH')) define('BASEPATH', true);

// Provide a minimal CI_Model if not present
if (!class_exists('CI_Model')) {
    class CI_Model {}
}

// Include MY_Model and Fine_model
require_once __DIR__ . '/../application/core/MY_Model.php';
require_once __DIR__ . '/../application/models/Fine_model.php';

class FineModelTest extends TestCase {

    private function makeFakeDb(array $rulesData) {
        $self = $this;
        return new class($rulesData) {
            private $rules;
            public function __construct($rules) { $this->rules = $rules; }
            public function field_exists($field, $table) {
                // For tests, assume no min_days and no grace_period_days columns
                return false;
            }
            public function order_by() { return $this; }
            public function get($table) {
                $rules = $this->rules;
                return new class($rules) {
                    private $rules;
                    public function __construct($rules) { $this->rules = $rules; }
                    public function result() { return $this->rules; }
                };
            }
        };
    }

    public function test_percentage_rule_normalization() {
        $raw = [(object) [
            'id' => 1,
            'rule_name' => 'Percent Test',
            'calculation_type' => 'percentage',
            'percentage_value' => 2.5,
            'fine_type' => 'late_emi',
            'is_active' => 1
        ]];

        $model = new Fine_model();
        $model->db = $this->makeFakeDb($raw);

        $rules = $model->get_rules();
        $this->assertCount(1, $rules);
        $r = $rules[0];

        $this->assertEquals('percentage', $r->amount_type, 'amount_type should be percentage');
        $this->assertEquals(2.5, $r->amount_value, 'amount_value should match percentage_value');
        $this->assertEquals(0, $r->grace_days, 'grace_days default to 0');
    }

    public function test_fixed_and_per_day_normalization() {
        $raw = [(object) [
            'id' => 2,
            'rule_name' => 'Per Day Test',
            'per_day_amount' => 10,
            'fixed_amount' => 50,
            'fine_type' => 'late_savings',
            'is_active' => 1
        ]];

        $model = new Fine_model();
        $model->db = $this->makeFakeDb($raw);

        $rules = $model->get_rules();
        $this->assertCount(1, $rules);
        $r = $rules[0];

        $this->assertEquals('fixed', $r->amount_type, 'amount_type should default to fixed');
        // For per_day presence we expect frequency daily and amount_value equals per_day_amount
        $this->assertEquals(10, $r->amount_value, 'amount_value should use per_day_amount when present');
        $this->assertEquals('daily', $r->frequency, 'frequency should be daily when per_day_amount present');
    }

    public function test_grace_and_max_fine_mapping() {
        $raw = [(object) [
            'id' => 3,
            'rule_name' => 'Grace Test',
            'grace_period_days' => 5,
            'max_fine_amount' => 1000,
            'fine_value' => 25,
            'fine_type' => 'other',
            'is_active' => 1
        ]];

        $model = new Fine_model();
        $model->db = $this->makeFakeDb($raw);

        $rules = $model->get_rules();
        $this->assertCount(1, $rules);
        $r = $rules[0];

        $this->assertEquals(5, $r->grace_days);
        $this->assertEquals(1000, $r->max_fine);
        $this->assertEquals(25, $r->amount_value);
    }

    public function test_waiver_request_and_approval() {
        // Create a fake fine record
        $fine = (object) [
            'id' => 10,
            'fine_amount' => 500,
            'waived_amount' => 0,
            'balance_amount' => 500,
            'member_id' => 1
        ];

        $model = new Fine_model();
        // fake db that returns the fine in get_by_id and allows update
        $model->db = new class($fine) {
            private $fine;
            public function __construct($fine) { $this->fine = $fine; }
            public function where() { return $this; }
            public function update($table, $data) { 
                // emulate update by merging
                foreach ($data as $k=>$v) { $this->fine->$k = $v; }
                return true;
            }
            public function get($table) { return $this; }
            public function row() { return $this->fine; }
            public function select() { return $this; }
            public function from() { return $this; }
            public function join() { return $this; }
            public function order_by() { return $this; }
            public function result() { return [$this->fine]; }
        };

        // Request a waiver
        $res = $model->request_waiver(10, 'Hardship', 2, 200);
        $this->assertTrue($res, 'request_waiver should return true');

        // Approve waiver
        $res2 = $model->approve_waiver(10, 200, 3);
        $this->assertTrue($res2, 'approve_waiver should return true');

        // Deny waiver (should be able to call and return true)
        $res3 = $model->deny_waiver(10, 4, 'Not eligible');
        $this->assertTrue($res3, 'deny_waiver should return true');
    }
}
