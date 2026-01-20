#!/usr/bin/env php
<?php
// Simple migrations runner - safe to run from CLI
if (php_sapi_name() !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}

// Load DB config
if (!defined('BASEPATH')) define('BASEPATH', true);
// Provide basic constants expected by CI config & helpers
if (!defined('ENVIRONMENT')) define('ENVIRONMENT', 'development');
if (!defined('FCPATH')) define('FCPATH', realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR);

// Load env helper if present for env() function
$envHelper = __DIR__ . '/../application/helpers/env_helper.php';
if (file_exists($envHelper)) require_once $envHelper;
// Ensure environment variables from .env are loaded if .env exists
if (function_exists('load_env')) load_env();
require __DIR__ . '/../application/config/database.php';
$active = $db['default'];
$host = $active['hostname'];
$user = $active['username'];
$pass = $active['password'];
$database = $active['database'];

$mysqli = new mysqli($host, $user, $pass, $database);
if ($mysqli->connect_errno) {
    echo "Failed to connect to DB: ({$mysqli->connect_errno}) {$mysqli->connect_error}\n";
    exit(1);
}

// Ensure schema_migrations table exists
$create = "CREATE TABLE IF NOT EXISTS schema_migrations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    filename VARCHAR(255) NOT NULL,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY (filename)
) ENGINE=InnoDB";
$mysqli->query($create);

$dir = __DIR__ . '/../database/migrations';
$files = array_values(array_filter(scandir($dir), function($f){ return preg_match('/^\d+.*\.sql$/', $f); }));

if (empty($files)) {
    echo "No migration files found in database/migrations.\n";
    exit(0);
}

// Helper to apply a single migration file and record it
function apply_migration($mysqli, $dir, $filename) {
    echo "Applying: $filename\n";
    $sql = file_get_contents($dir . '/' . $filename);
    if ($sql === false) {
        throw new Exception("Failed to read $filename");
    }

    if ($mysqli->multi_query($sql)) {
        do {
            if ($res = $mysqli->store_result()) {
                $res->free();
            }
        } while ($mysqli->more_results() && $mysqli->next_result());

        if ($mysqli->errno) {
            throw new Exception($mysqli->error, $mysqli->errno);
        }

        // Record applied migration
        $ins = $mysqli->prepare('INSERT INTO schema_migrations (filename) VALUES (?)');
        $ins->bind_param('s', $filename);
        $ins->execute();
        $ins->close();

        echo "Applied: $filename\n";
        return true;
    } else {
        throw new Exception($mysqli->error, $mysqli->errno);
    }
}

foreach ($files as $file) {
    $filename = $file;
    // Check if applied
    $stmt = $mysqli->prepare('SELECT COUNT(*) FROM schema_migrations WHERE filename = ?');
    $stmt->bind_param('s', $filename);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        echo "Skipping applied: $filename\n";
        continue;
    }

    try {
        apply_migration($mysqli, $dir, $filename);
    } catch (Exception $e) {
        $msg = $e->getMessage();
        // If the migration failed due to 'already exists' type errors, assume it's a previously-applied change and mark as applied
        if (stripos($msg, 'duplicate column') !== false || stripos($msg, 'already exists') !== false || stripos($msg, 'duplicate key') !== false || stripos($msg, 'Duplicate CHECK constraint') !== false || $e->getCode() === 1826) {
            echo "Non-fatal issue detected in $filename: {$msg} — marking migration as applied.\n";
            $ins = $mysqli->prepare('INSERT INTO schema_migrations (filename) VALUES (?)');
            $ins->bind_param('s', $filename);
            $ins->execute();
            $ins->close();
            continue;
        }

        // Attempt to detect missing column error and apply migrations that might add the missing column
        if (preg_match("/Unknown column '([^']+)' in '([^']+)'/i", $msg, $m)) {
            $missing_col = $m[1];
            $missing_table = $m[2];
            echo "Detected missing column {$missing_table}.{$missing_col} while applying {$filename}.\n";

            // Search other migration files for ADD COLUMN that mentions the missing column
            $candidates = [];
            foreach ($files as $f) {
                if ($f === $filename) continue;
                $content = file_get_contents($dir . '/' . $f);
                if (stripos($content, $missing_col) !== false && stripos($content, 'ADD COLUMN') !== false) {
                    // Check if candidate is already applied
                    $stmt = $mysqli->prepare('SELECT COUNT(*) FROM schema_migrations WHERE filename = ?');
                    $stmt->bind_param('s', $f);
                    $stmt->execute();
                    $stmt->bind_result($already);
                    $stmt->fetch();
                    $stmt->close();
                    if ($already == 0) $candidates[] = $f;
                }
            }

            if (!empty($candidates)) {
                echo "Found candidate migrations that may add the missing column: " . implode(', ', $candidates) . "\n";
                foreach ($candidates as $cand) {
                    try {
                        apply_migration($mysqli, $dir, $cand);
                    } catch (Exception $ee) {
                        echo "Failed to apply dependency $cand: " . $ee->getMessage() . "\n";
                    }
                }

                // Retry original migration once
                try {
                    apply_migration($mysqli, $dir, $filename);
                    continue;
                } catch (Exception $ee) {
                    echo "Retry failed for $filename: " . $ee->getMessage() . "\n";
                    exit(1);
                }
            }
        }

        echo "Error executing $filename: ({$e->getCode()}) {$msg}\n";
        exit(1);
    }
}

echo "All pending migrations applied.\n";
$mysqli->close();
