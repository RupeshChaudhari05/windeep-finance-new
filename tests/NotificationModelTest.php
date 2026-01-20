<?php
use PHPUnit\Framework\TestCase;

if (!defined('BASEPATH')) define('BASEPATH', true);
if (!class_exists('CI_Model')) { class CI_Model {} }
require_once __DIR__ . '/../application/core/MY_Model.php';
require_once __DIR__ . '/../application/models/Notification_model.php';

class NotificationModelTest extends TestCase {
    public function test_get_for_normalizes_is_read_to_integer() {
        $model = new Notification_model();
        $model->db = new class {
            public function field_exists($field, $table) { return true; }
            public function where() { return $this; }
            public function order_by() { return $this; }
            public function limit() { return $this; }
            public function get($table) { return $this; }
            public function result() { return [(object)['id' => 1, 'title' => 'T', 'message' => 'M', 'is_read' => '0']]; }
        };

        $res = $model->get_for('member', 12, 50);
        $this->assertIsArray($res);
        $this->assertEquals(1, count($res));
        $this->assertEquals(0, $res[0]->is_read);
        $this->assertIsInt($res[0]->is_read);
    }
}
