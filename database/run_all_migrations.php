<?php
/**
 * Run All Pending Migrations
 * 
 * Execute this script from command line or browser to run all pending migrations
 * 
 * Usage: php run_all_migrations.php
 * Or: http://yourdomain.com/database/run_all_migrations.php
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>\n";
echo "===========================================\n";
echo "  WINDEEP FINANCE - MIGRATION RUNNER\n";
echo "===========================================\n\n";

// Database configuration
$config = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '',
    'name' => 'windeep_finance'
];

// Check for environment variables
if (getenv('DB_HOST')) $config['host'] = getenv('DB_HOST');
if (getenv('DB_USER')) $config['user'] = getenv('DB_USER');
if (getenv('DB_PASS')) $config['pass'] = getenv('DB_PASS');
if (getenv('DB_NAME')) $config['name'] = getenv('DB_NAME');

// Connect to database
$conn = new mysqli($config['host'], $config['user'], $config['pass'], $config['name']);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}

echo "✓ Connected to database: {$config['name']}\n\n";

// List of migration files in order
$migrations = [
    '016_add_performance_indexes.sql',
    '017_email_whatsapp_features.sql'
];

$success_count = 0;
$error_count = 0;

foreach ($migrations as $migration_file) {
    $file_path = __DIR__ . '/migrations/' . $migration_file;
    
    echo "Processing: {$migration_file}\n";
    echo str_repeat('-', 50) . "\n";
    
    if (!file_exists($file_path)) {
        echo "  ✗ File not found: {$file_path}\n\n";
        $error_count++;
        continue;
    }
    
    $sql_content = file_get_contents($file_path);
    
    // Remove comments
    $sql_content = preg_replace('/--.*$/m', '', $sql_content);
    $sql_content = preg_replace('/\/\*.*?\*\//s', '', $sql_content);
    
    // Split by semicolons
    $statements = array_filter(array_map('trim', explode(';', $sql_content)));
    
    $stmt_success = 0;
    $stmt_skip = 0;
    $stmt_error = 0;
    
    foreach ($statements as $stmt) {
        if (empty($stmt)) continue;
        
        // Skip if it's just whitespace or comments
        if (preg_match('/^(--|#|\/\*)/', trim($stmt))) continue;
        
        // Execute the statement
        if ($conn->query($stmt)) {
            $stmt_success++;
        } else {
            // Check if it's a "duplicate" or "already exists" error - these are OK
            if (strpos($conn->error, 'Duplicate') !== false || 
                strpos($conn->error, 'already exists') !== false ||
                strpos($conn->error, 'duplicate column') !== false ||
                strpos($conn->error, 'Duplicate key name') !== false) {
                $stmt_skip++;
            } else {
                $stmt_error++;
                echo "  ✗ Error: " . $conn->error . "\n";
                echo "    Statement: " . substr($stmt, 0, 100) . "...\n";
            }
        }
    }
    
    echo "  ✓ Executed: {$stmt_success} statements\n";
    if ($stmt_skip > 0) echo "  ⊘ Skipped (already exists): {$stmt_skip}\n";
    if ($stmt_error > 0) echo "  ✗ Errors: {$stmt_error}\n";
    echo "\n";
    
    if ($stmt_error == 0) {
        $success_count++;
    } else {
        $error_count++;
    }
}

echo "===========================================\n";
echo "SUMMARY\n";
echo "===========================================\n";
echo "Migrations completed: {$success_count}\n";
echo "Migrations with errors: {$error_count}\n\n";

if ($error_count == 0) {
    echo "✓ ALL MIGRATIONS COMPLETED SUCCESSFULLY!\n\n";
} else {
    echo "⚠ Some migrations had errors. Please review above.\n\n";
}

// Verify key tables exist
echo "Verifying new tables...\n";
$tables_to_check = [
    'verification_tokens',
    'email_queue',
    'whatsapp_logs',
    'backups',
    'cron_log',
    'monthly_reports'
];

foreach ($tables_to_check as $table) {
    $result = $conn->query("SHOW TABLES LIKE '{$table}'");
    if ($result->num_rows > 0) {
        echo "  ✓ Table '{$table}' exists\n";
    } else {
        echo "  ✗ Table '{$table}' NOT FOUND\n";
    }
}

echo "\n";
echo "===========================================\n";
echo "NEXT STEPS\n";
echo "===========================================\n";
echo "1. Configure cron jobs (see PRODUCTION_READY_GUIDE.md)\n";
echo "2. Set up email settings in Admin > Settings\n";
echo "3. Configure WhatsApp integration if needed\n";
echo "4. Create a database backup\n";
echo "\n";

$conn->close();
echo "</pre>\n";
