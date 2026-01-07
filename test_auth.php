<?php
// Test admin authentication
require_once 'system/core/CodeIgniter.php';

// Simulate CI environment
class CI_Controller {
    public function __construct() {}
}

class TestAuth {
    private $db;

    public function __construct() {
        $this->db = mysqli_connect('localhost', 'root', '', 'windeep_finance_new');
        if (!$this->db) {
            die('Connection failed: ' . mysqli_connect_error());
        }
    }

    public function test_auth($email, $password) {
        // Get admin by email
        $result = mysqli_query($this->db, "SELECT * FROM admin_users WHERE email = '$email'");
        $admin = mysqli_fetch_assoc($result);

        if (!$admin) {
            return ['error' => 'User not found'];
        }

        // Check password
        if (password_verify($password, $admin['password'])) {
            return ['success' => true, 'admin' => $admin];
        } else {
            return ['error' => 'Invalid password'];
        }
    }
}

$test = new TestAuth();
$result = $test->test_auth('admin@windeep.com', 'admin123');

echo "Testing authentication for admin@windeep.com / admin123:\n";
echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
