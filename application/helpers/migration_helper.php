<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration Helper
 * 
 * Utility functions for migration management
 */

/**
 * Get all migration files from database/migrations folder
 */
function get_migration_files()
{
    $migrations_path = APPPATH . '../database/migrations/';
    $migrations = [];

    if (is_dir($migrations_path)) {
        $files = scandir($migrations_path);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                $migrations[] = [
                    'name' => $file,
                    'path' => $migrations_path . $file,
                    'size' => filesize($migrations_path . $file),
                    'modified' => filemtime($migrations_path . $file)
                ];
            }
        }
    }

    return $migrations;
}

/**
 * Parse SQL migration file and extract info
 */
function parse_migration_info($file_path)
{
    if (!file_exists($file_path)) {
        return null;
    }

    $content = file_get_contents($file_path);
    $info = [
        'name' => basename($file_path),
        'size' => filesize($file_path),
        'lines' => substr_count($content, "\n") + 1,
        'statements' => substr_count($content, ';'),
        'has_transaction' => strpos($content, 'START TRANSACTION') !== false || strpos($content, 'BEGIN') !== false
    ];

    // Extract description from comments
    if (preg_match('/--\s*Purpose:\s*(.+)/i', $content, $matches)) {
        $info['purpose'] = trim($matches[1]);
    }

    return $info;
}

/**
 * Auto-run pending migrations
 */
function auto_run_migrations()
{
    $CI = &get_instance();
    $CI->load->model('Migration_model');

    $pending = $CI->Migration_model->get_pending_migrations();
    
    foreach ($pending as $migration) {
        $file_path = APPPATH . '../database/migrations/' . $migration->migration_name;
        
        if (file_exists($file_path)) {
            $content = file_get_contents($file_path);
            
            try {
                $statements = array_filter(
                    array_map('trim', explode(';', $content)),
                    fn($stmt) => !empty($stmt) && !str_starts_with($stmt, '--')
                );

                foreach ($statements as $statement) {
                    $CI->db->query($statement);
                }

                $CI->Migration_model->update_migration_status(
                    $migration->migration_name,
                    'completed',
                    null,
                    'Auto-executed on deployment'
                );

            } catch (Exception $e) {
                $CI->Migration_model->update_migration_status(
                    $migration->migration_name,
                    'failed',
                    null,
                    null,
                    $e->getMessage()
                );
            }
        }
    }
}

/**
 * Generate migration report
 */
function get_migration_report()
{
    $CI = &get_instance();
    $CI->load->model('Migration_model');

    $stats = $CI->Migration_model->get_statistics();
    $migrations = $CI->Migration_model->get_migration_history(100);
    $pending = $CI->Migration_model->get_pending_migrations();

    return [
        'statistics' => $stats,
        'recent_migrations' => $migrations,
        'pending_migrations' => $pending,
        'generated_at' => date('Y-m-d H:i:s')
    ];
}

/**
 * Validate migration file syntax
 */
function validate_migration_syntax($file_path)
{
    if (!file_exists($file_path)) {
        return ['valid' => false, 'error' => 'File not found'];
    }

    $content = file_get_contents($file_path);

    // Basic validation
    $errors = [];

    // Check for balanced parentheses
    if (substr_count($content, '(') !== substr_count($content, ')')) {
        $errors[] = 'Unbalanced parentheses';
    }

    // Check for balanced quotes
    if ((substr_count($content, "'") % 2) !== 0) {
        $errors[] = 'Unbalanced single quotes';
    }

    // Check for balanced backticks
    if ((substr_count($content, '`') % 2) !== 0) {
        $errors[] = 'Unbalanced backticks';
    }

    if (!empty($errors)) {
        return ['valid' => false, 'errors' => $errors];
    }

    return ['valid' => true];
}

/**
 * Backup database before migration
 */
function backup_database()
{
    $CI = &get_instance();
    $backup_file = APPPATH . '../database/backups/backup_' . date('Y-m-d-H-i-s') . '.sql';

    // Ensure backup directory exists
    if (!is_dir(dirname($backup_file))) {
        mkdir(dirname($backup_file), 0755, true);
    }

    $cmd = "mysqldump -u {$CI->db->username} -p{$CI->db->password} {$CI->db->database} > {$backup_file}";
    exec($cmd, $output, $return_var);

    return $return_var === 0 ? $backup_file : false;
}
