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
                // Quote / comment / DELIMITER aware (a ; inside COMMENT '…' must not split the statement)
                $statements = sql_split_statements($content);

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

if (!function_exists('sql_split_statements')) {
    /**
     * Split an SQL script into single statements.
     * Unlike explode(';'): a ; inside quotes ('…', "…", `…`) or comments does not split, comments (--, #, block comments)
     * are dropped (MySQL versioned "/*!" hints are kept) and DELIMITER $$ blocks (procedures / triggers) stay whole.
     * A statement that starts after a comment line is therefore no longer skipped.
     *
     * @return string[]
     */
    function sql_split_statements($sql)
    {
        $out = [];
        $buf = '';
        $delim = ';';
        $quote = null;
        $len = strlen($sql);
        $flush = function () use (&$buf, &$out) {
            if (trim($buf) !== '') $out[] = trim($buf);
            $buf = '';
        };
        for ($i = 0; $i < $len;) {
            $c = $sql[$i];
            if ($quote !== null) {
                $buf .= $c;
                if ($c === '\\' && $quote !== '`' && $i + 1 < $len) { $buf .= $sql[$i + 1]; $i += 2; continue; }
                if ($c === $quote) {
                    if ($i + 1 < $len && $sql[$i + 1] === $quote) { $buf .= $quote; $i += 2; continue; }   // '' inside a string
                    $quote = null;
                }
                $i++;
                continue;
            }
            // DELIMITER $$ / DELIMITER ; / DELIMITER; at the start of a line (mysql client command, not SQL)
            if (($i === 0 || $sql[$i - 1] === "\n") && preg_match('/\G[ \t]*DELIMITER(?![A-Za-z0-9_])[ \t]*(\S+)[^\n]*(\n|$)/i', $sql, $m, 0, $i)) {
                $flush();
                $delim = $m[1];
                $i += strlen($m[0]);
                continue;
            }
            if (($c === '-' && substr($sql, $i, 2) === '--' && ($i + 2 >= $len || ctype_space($sql[$i + 2]))) || $c === '#') {
                $e = strpos($sql, "\n", $i);
                $i = $e === false ? $len : $e;
                continue;
            }
            if ($c === '/' && substr($sql, $i, 2) === '/*') {
                $e = strpos($sql, '*/', $i + 2);
                $end = $e === false ? $len : $e + 2;
                if (substr($sql, $i, 3) === '/*!') $buf .= substr($sql, $i, $end - $i);   // executable version hint
                else $buf .= ' ';
                $i = $end;
                continue;
            }
            if ($c === "'" || $c === '"' || $c === '`') { $quote = $c; $buf .= $c; $i++; continue; }
            if (substr_compare($sql, $delim, $i, strlen($delim)) === 0) { $flush(); $i += strlen($delim); continue; }
            $buf .= $c;
            $i++;
        }
        $flush();
        return $out;
    }
}

if (!function_exists('sql_split_top_level')) {
    /** Split on commas that are outside quotes and parentheses (the clauses of one ALTER TABLE). */
    function sql_split_top_level($s)
    {
        $parts = [];
        $buf = '';
        $depth = 0;
        $quote = null;
        $len = strlen($s);
        for ($i = 0; $i < $len; $i++) {
            $c = $s[$i];
            if ($quote !== null) {
                $buf .= $c;
                if ($c === '\\' && $quote !== '`' && $i + 1 < $len) { $buf .= $s[++$i]; continue; }
                if ($c === $quote) $quote = null;
                continue;
            }
            if ($c === "'" || $c === '"' || $c === '`') $quote = $c;
            elseif ($c === '(') $depth++;
            elseif ($c === ')') $depth--;
            elseif ($c === ',' && $depth === 0) { $parts[] = trim($buf); $buf = ''; continue; }
            $buf .= $c;
        }
        if (trim($buf) !== '') $parts[] = trim($buf);
        return $parts;
    }
}
