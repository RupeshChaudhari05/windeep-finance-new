<?php
/**
 * System Status Check
 * 
 * Verifies all system requirements and configuration
 */

// Define BASEPATH to allow includes
define('BASEPATH', __DIR__ . '/system/');

echo "\n";
echo "=========================================\n";
echo "   Windeep Finance - System Check\n";
echo "=========================================\n\n";

// Load environment
require_once __DIR__ . '/application/helpers/env_helper.php';
load_env(__DIR__ . '/.env');

$errors = [];
$warnings = [];
$success = [];

// Check PHP Version
echo "✓ Checking PHP Version...\n";
$phpVersion = phpversion();
if (version_compare($phpVersion, '8.0.0', '>=')) {
    $success[] = "PHP Version: $phpVersion";
} else {
    $errors[] = "PHP Version $phpVersion is not supported. Requires PHP 8.0+";
}

// Check Extensions
echo "✓ Checking PHP Extensions...\n";
$required = ['mysqli', 'mbstring', 'json', 'curl'];
foreach ($required as $ext) {
    if (extension_loaded($ext)) {
        $success[] = "Extension '$ext' is loaded";
    } else {
        $errors[] = "Required extension '$ext' is not loaded";
    }
}

// Check .env file
echo "✓ Checking Configuration...\n";
if (file_exists(__DIR__ . '/.env')) {
    $success[] = ".env file exists";
    
    // Check required env variables
    $requiredEnv = ['APP_URL', 'DB_HOST', 'DB_NAME', 'DB_USERNAME'];
    foreach ($requiredEnv as $var) {
        if (env($var)) {
            $success[] = "$var is set";
        } else {
            $warnings[] = "$var is not set in .env";
        }
    }
} else {
    $errors[] = ".env file does not exist. Copy from .env.example";
}

// Check Database Connection
echo "✓ Checking Database Connection...\n";
try {
    $mysqli = new mysqli(
        env('DB_HOST', 'localhost'),
        env('DB_USERNAME', 'root'),
        env('DB_PASSWORD', ''),
        env('DB_NAME', 'windeep_finance_new')
    );
    
    if ($mysqli->connect_error) {
        $errors[] = "Database connection failed: " . $mysqli->connect_error;
    } else {
        $success[] = "Database connection successful";
        
        // Check admin users
        $result = $mysqli->query("SELECT COUNT(*) as count FROM admin_users WHERE is_active = 1");
        if ($result) {
            $row = $result->fetch_assoc();
            if ($row['count'] > 0) {
                $success[] = "Active admin users found: " . $row['count'];
            } else {
                $warnings[] = "No active admin users found. Run: php create_admin.php";
            }
        }
        
        $mysqli->close();
    }
} catch (Exception $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

// Check Writable Directories
echo "✓ Checking Directory Permissions...\n";
$writableDirs = [
    'application/cache',
    'application/logs',
    'uploads'
];

foreach ($writableDirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        if (is_writable($path)) {
            $success[] = "$dir is writable";
        } else {
            $warnings[] = "$dir is not writable. Set permissions: chmod 755 $dir";
        }
    } else {
        $warnings[] = "$dir directory does not exist";
    }
}

// Check .htaccess
echo "✓ Checking URL Rewrite...\n";
if (file_exists(__DIR__ . '/.htaccess')) {
    $success[] = ".htaccess file exists";
} else {
    $errors[] = ".htaccess file is missing";
}

// Print Results
echo "\n";
echo "=========================================\n";
echo "   RESULTS\n";
echo "=========================================\n\n";

if (!empty($success)) {
    echo "✓ SUCCESS (" . count($success) . "):\n";
    echo "-------------------\n";
    foreach ($success as $msg) {
        echo "  ✓ $msg\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "⚠ WARNINGS (" . count($warnings) . "):\n";
    echo "-------------------\n";
    foreach ($warnings as $msg) {
        echo "  ⚠ $msg\n";
    }
    echo "\n";
}

if (!empty($errors)) {
    echo "✗ ERRORS (" . count($errors) . "):\n";
    echo "-------------------\n";
    foreach ($errors as $msg) {
        echo "  ✗ $msg\n";
    }
    echo "\n";
}

// Final Status
echo "=========================================\n";
if (empty($errors)) {
    echo "✓ System is ready to use!\n";
    echo "\nAccess your application at:\n";
    echo env('APP_URL', 'http://localhost/windeep_finance') . "\n";
    echo "\nDefault Login:\n";
    echo "Email: admin@windeep.com\n";
    echo "Password: admin123\n";
} else {
    echo "✗ Please fix the errors above before using the system.\n";
    exit(1);
}
echo "=========================================\n\n";
