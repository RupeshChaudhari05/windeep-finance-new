<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * System Management Controller
 * 
 * Handles system administration tasks:
 * - Log viewing and management
 * - Database backup and restore
 * - System health monitoring
 * - Cache management
 */
class System extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->check_permission('manage_settings'); // Require admin access
        $this->load->helper('file');
    }

    /**
     * System Dashboard
     */
    public function index()
    {
        $data = [
            'title' => 'System Management',
            'page' => 'system/index',
            'disk_free' => disk_free_space('/'),
            'disk_total' => disk_total_space('/'),
            'php_version' => phpversion(),
            'ci_version' => CI_VERSION,
            'db_version' => $this->db->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'recent_errors' => $this->_get_recent_errors(5),
            'cron_status' => $this->_get_cron_status(),
            'db_stats' => $this->_get_db_stats()
        ];

        $this->load->view('layouts/main', $data);
    }

    // =========================================
    // LOG MANAGEMENT
    // =========================================

    /**
     * View Application Logs
     */
    public function logs()
    {
        $log_path = APPPATH . 'logs/';
        $log_files = [];

        if (is_dir($log_path)) {
            $files = glob($log_path . 'log-*.php');
            foreach ($files as $file) {
                $log_files[] = [
                    'name' => basename($file),
                    'date' => date('Y-m-d', filemtime($file)),
                    'size' => filesize($file),
                    'modified' => filemtime($file)
                ];
            }
            // Sort by date descending
            usort($log_files, function($a, $b) {
                return $b['modified'] - $a['modified'];
            });
        }

        $data = [
            'title' => 'Application Logs',
            'page' => 'system/logs',
            'log_files' => array_slice($log_files, 0, 30) // Last 30 log files
        ];

        $this->load->view('layouts/main', $data);
    }

    /**
     * View single log file
     */
    public function view_log($filename)
    {
        $log_path = APPPATH . 'logs/' . $filename;

        // Security check
        if (strpos($filename, '..') !== false || !preg_match('/^log-\d{4}-\d{2}-\d{2}\.php$/', $filename)) {
            $this->session->set_flashdata('error', 'Invalid log file.');
            redirect('admin/system/logs');
            return;
        }

        if (!file_exists($log_path)) {
            $this->session->set_flashdata('error', 'Log file not found.');
            redirect('admin/system/logs');
            return;
        }

        $content = file_get_contents($log_path);
        // Skip the PHP die() line
        $content = preg_replace('/^<\?php.*?\?>\s*/s', '', $content);

        // Parse log entries
        $entries = $this->_parse_log_content($content);

        $data = [
            'title' => 'Log: ' . $filename,
            'page' => 'system/view_log',
            'filename' => $filename,
            'entries' => $entries,
            'raw_content' => $content
        ];

        $this->load->view('layouts/main', $data);
    }

    /**
     * Delete a log file
     */
    public function delete_log($filename)
    {
        $log_path = APPPATH . 'logs/' . $filename;

        if (strpos($filename, '..') !== false || !preg_match('/^log-\d{4}-\d{2}-\d{2}\.php$/', $filename)) {
            $this->session->set_flashdata('error', 'Invalid log file.');
            redirect('admin/system/logs');
            return;
        }

        if (file_exists($log_path) && unlink($log_path)) {
            $this->session->set_flashdata('success', 'Log file deleted.');
        } else {
            $this->session->set_flashdata('error', 'Failed to delete log file.');
        }

        redirect('admin/system/logs');
    }

    /**
     * Clear all log files
     */
    public function clear_logs()
    {
        $log_path = APPPATH . 'logs/';
        $files = glob($log_path . 'log-*.php');
        $deleted = 0;

        foreach ($files as $file) {
            if (unlink($file)) {
                $deleted++;
            }
        }

        $this->session->set_flashdata('success', "Cleared {$deleted} log files.");
        redirect('admin/system/logs');
    }

    /**
     * View Audit Logs
     */
    public function audit_logs()
    {
        $page = max(1, $this->input->get('page') ?: 1);
        $per_page = 50;
        $offset = ($page - 1) * $per_page;

        // Filters
        $user_type = $this->input->get('user_type');
        $action = $this->input->get('action');
        $date_from = $this->input->get('date_from');
        $date_to = $this->input->get('date_to');

        $this->db->from('audit_log');
        
        if ($user_type) {
            $this->db->where('user_type', $user_type);
        }
        if ($action) {
            $this->db->like('action', $action);
        }
        if ($date_from) {
            $this->db->where('created_at >=', $date_from . ' 00:00:00');
        }
        if ($date_to) {
            $this->db->where('created_at <=', $date_to . ' 23:59:59');
        }

        $total = $this->db->count_all_results('', false);
        $logs = $this->db->order_by('created_at', 'DESC')
            ->limit($per_page, $offset)
            ->get()
            ->result();

        // Get unique actions for filter
        $actions = $this->db->select('DISTINCT(action) as action')
            ->order_by('action')
            ->get('audit_log')
            ->result();

        $data = [
            'title' => 'Audit Logs',
            'page' => 'system/audit_logs',
            'logs' => $logs,
            'actions' => $actions,
            'total' => $total,
            'per_page' => $per_page,
            'current_page' => $page,
            'total_pages' => ceil($total / $per_page),
            'filters' => [
                'user_type' => $user_type,
                'action' => $action,
                'date_from' => $date_from,
                'date_to' => $date_to
            ]
        ];

        $this->load->view('layouts/main', $data);
    }

    /**
     * View Cron Logs
     */
    public function cron_logs()
    {
        $page = max(1, $this->input->get('page') ?: 1);
        $per_page = 50;
        $offset = ($page - 1) * $per_page;

        $job_name = $this->input->get('job');
        $status = $this->input->get('status');

        $this->db->from('cron_log');
        
        if ($job_name) {
            $this->db->where('job_name', $job_name);
        }
        if ($status) {
            $this->db->where('status', $status);
        }

        $total = $this->db->count_all_results('', false);
        $logs = $this->db->order_by('created_at', 'DESC')
            ->limit($per_page, $offset)
            ->get()
            ->result();

        // Get unique job names
        $jobs = $this->db->select('DISTINCT(job_name) as job_name')
            ->order_by('job_name')
            ->get('cron_log')
            ->result();

        $data = [
            'title' => 'Cron Job Logs',
            'page' => 'system/cron_logs',
            'logs' => $logs,
            'jobs' => $jobs,
            'total' => $total,
            'per_page' => $per_page,
            'current_page' => $page,
            'total_pages' => ceil($total / $per_page)
        ];

        $this->load->view('layouts/main', $data);
    }

    // =========================================
    // BACKUP MANAGEMENT
    // =========================================

    /**
     * Backup Management Page
     */
    public function backups()
    {
        $backups = $this->db->order_by('created_at', 'DESC')
            ->get('backups')
            ->result();

        $data = [
            'title' => 'Database Backups',
            'page' => 'system/backups',
            'backups' => $backups,
            'backup_path' => FCPATH . 'uploads/backups/'
        ];

        $this->load->view('layouts/main', $data);
    }

    /**
     * Create a new backup
     */
    public function create_backup()
    {
        $this->load->dbutil();

        // Backup preferences
        $prefs = [
            'format' => 'zip',
            'filename' => 'backup_' . date('Y-m-d_His'),
            'add_drop' => true,
            'add_insert' => true,
            'newline' => "\n",
            'foreign_key_checks' => false
        ];

        $backup = $this->dbutil->backup($prefs);

        if (!$backup) {
            $this->session->set_flashdata('error', 'Failed to create backup.');
            redirect('admin/system/backups');
            return;
        }

        // Ensure backup directory exists
        $backup_path = FCPATH . 'uploads/backups/';
        if (!is_dir($backup_path)) {
            mkdir($backup_path, 0755, true);
        }

        // Save backup file
        $filename = $prefs['filename'] . '.zip';
        $filepath = $backup_path . $filename;

        if (write_file($filepath, $backup)) {
            // Record in database
            $this->db->insert('backups', [
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => filesize($filepath),
                'type' => 'manual',
                'status' => 'completed',
                'notes' => 'Manual backup created by ' . $this->session->userdata('name'),
                'created_by' => $this->session->userdata('user_id')
            ]);

            $this->session->set_flashdata('success', 'Backup created successfully.');
        } else {
            $this->session->set_flashdata('error', 'Failed to save backup file.');
        }

        redirect('admin/system/backups');
    }

    /**
     * Download a backup file
     */
    public function download_backup($id)
    {
        $backup = $this->db->where('id', $id)->get('backups')->row();

        if (!$backup || !file_exists($backup->filepath)) {
            $this->session->set_flashdata('error', 'Backup file not found.');
            redirect('admin/system/backups');
            return;
        }

        $this->load->helper('download');
        force_download($backup->filename, file_get_contents($backup->filepath));
    }

    /**
     * Delete a backup
     */
    public function delete_backup($id)
    {
        $backup = $this->db->where('id', $id)->get('backups')->row();

        if ($backup) {
            if (file_exists($backup->filepath)) {
                unlink($backup->filepath);
            }
            $this->db->where('id', $id)->delete('backups');
            $this->session->set_flashdata('success', 'Backup deleted.');
        } else {
            $this->session->set_flashdata('error', 'Backup not found.');
        }

        redirect('admin/system/backups');
    }

    /**
     * Restore from backup (confirmation page)
     */
    public function restore_backup($id)
    {
        $backup = $this->db->where('id', $id)->get('backups')->row();

        if (!$backup) {
            $this->session->set_flashdata('error', 'Backup not found.');
            redirect('admin/system/backups');
            return;
        }

        $data = [
            'title' => 'Restore Backup',
            'page' => 'system/restore_confirm',
            'backup' => $backup
        ];

        $this->load->view('layouts/main', $data);
    }

    /**
     * Process backup restore
     */
    public function process_restore($id)
    {
        $backup = $this->db->where('id', $id)->get('backups')->row();

        if (!$backup || !file_exists($backup->filepath)) {
            $this->session->set_flashdata('error', 'Backup file not found.');
            redirect('admin/system/backups');
            return;
        }

        // Create pre-restore backup
        $this->_create_pre_restore_backup();

        // Extract and run SQL
        $zip = new ZipArchive();
        if ($zip->open($backup->filepath) === true) {
            $sql = $zip->getFromIndex(0);
            $zip->close();

            // Split into statements and execute
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            $executed = 0;
            $errors = [];

            $this->db->trans_start();
            foreach ($statements as $stmt) {
                if (!empty($stmt) && stripos($stmt, 'CREATE') !== 0) {
                    try {
                        $this->db->query($stmt);
                        $executed++;
                    } catch (Exception $e) {
                        $errors[] = $e->getMessage();
                    }
                }
            }
            $this->db->trans_complete();

            if ($this->db->trans_status()) {
                $this->session->set_flashdata('success', "Database restored from backup. {$executed} statements executed.");
            } else {
                $this->session->set_flashdata('error', 'Restore failed: ' . implode(', ', $errors));
            }
        } else {
            $this->session->set_flashdata('error', 'Failed to open backup file.');
        }

        redirect('admin/system/backups');
    }

    // =========================================
    // CACHE MANAGEMENT
    // =========================================

    /**
     * Clear application cache
     */
    public function clear_cache()
    {
        $cache_path = APPPATH . 'cache/';
        $deleted = 0;

        if (is_dir($cache_path)) {
            $files = glob($cache_path . '*');
            foreach ($files as $file) {
                if (is_file($file) && basename($file) !== 'index.html') {
                    unlink($file);
                    $deleted++;
                }
            }
        }

        // Also clear session files if file-based
        $session_path = ini_get('session.save_path');
        if ($session_path && is_dir($session_path)) {
            // Only clear expired sessions (older than session.gc_maxlifetime)
            $maxlifetime = ini_get('session.gc_maxlifetime') ?: 1440;
            $files = glob($session_path . '/sess_*');
            foreach ($files as $file) {
                if (filemtime($file) < time() - $maxlifetime) {
                    @unlink($file);
                }
            }
        }

        $this->session->set_flashdata('success', "Cache cleared. {$deleted} files removed.");
        redirect('admin/system');
    }

    // =========================================
    // SYSTEM HEALTH CHECK
    // =========================================

    /**
     * System health check endpoint
     */
    public function health_check()
    {
        $checks = [
            'database' => $this->_check_database(),
            'disk_space' => $this->_check_disk_space(),
            'writable_dirs' => $this->_check_writable_dirs(),
            'php_extensions' => $this->_check_php_extensions(),
            'cron_jobs' => $this->_check_cron_jobs()
        ];

        $overall = !in_array(false, array_column($checks, 'status'));

        header('Content-Type: application/json');
        echo json_encode([
            'status' => $overall ? 'healthy' : 'degraded',
            'timestamp' => date('c'),
            'checks' => $checks
        ]);
    }

    // =========================================
    // PRIVATE HELPER METHODS
    // =========================================

    private function _parse_log_content($content)
    {
        $entries = [];
        $lines = explode("\n", $content);
        $current_entry = null;

        foreach ($lines as $line) {
            if (preg_match('/^(ERROR|DEBUG|INFO|WARNING|NOTICE)\s+-\s+(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\s+-->\s+(.*)$/', $line, $matches)) {
                if ($current_entry) {
                    $entries[] = $current_entry;
                }
                $current_entry = [
                    'level' => $matches[1],
                    'datetime' => $matches[2],
                    'message' => $matches[3]
                ];
            } elseif ($current_entry && !empty(trim($line))) {
                $current_entry['message'] .= "\n" . $line;
            }
        }

        if ($current_entry) {
            $entries[] = $current_entry;
        }

        return array_reverse($entries);
    }

    private function _get_recent_errors($limit = 5)
    {
        $log_path = APPPATH . 'logs/log-' . date('Y-m-d') . '.php';
        
        if (!file_exists($log_path)) {
            return [];
        }

        $content = file_get_contents($log_path);
        $entries = $this->_parse_log_content($content);
        
        // Filter errors only
        $errors = array_filter($entries, function($e) {
            return $e['level'] === 'ERROR';
        });

        return array_slice($errors, 0, $limit);
    }

    private function _get_cron_status()
    {
        // Check last cron run
        $last_run = $this->db
            ->where('status', 'completed')
            ->order_by('created_at', 'DESC')
            ->limit(1)
            ->get('cron_log')
            ->row();

        if (!$last_run) {
            return ['status' => 'unknown', 'message' => 'No cron runs recorded'];
        }

        $last_time = strtotime($last_run->created_at);
        $hours_ago = (time() - $last_time) / 3600;

        if ($hours_ago < 25) {
            return ['status' => 'ok', 'message' => 'Last run: ' . $last_run->job_name . ' ' . round($hours_ago, 1) . ' hours ago'];
        } else {
            return ['status' => 'warning', 'message' => 'No cron activity in ' . round($hours_ago, 0) . ' hours'];
        }
    }

    private function _get_db_stats()
    {
        $stats = [];
        
        $tables = ['members', 'loans', 'savings_accounts', 'installments', 'transactions'];
        foreach ($tables as $table) {
            $stats[$table] = $this->db->count_all($table);
        }

        return $stats;
    }

    private function _create_pre_restore_backup()
    {
        $this->load->dbutil();

        $backup = $this->dbutil->backup(['format' => 'zip', 'add_drop' => true]);
        
        if ($backup) {
            $backup_path = FCPATH . 'uploads/backups/';
            $filename = 'pre_restore_' . date('Y-m-d_His') . '.zip';
            $filepath = $backup_path . $filename;

            if (write_file($filepath, $backup)) {
                $this->db->insert('backups', [
                    'filename' => $filename,
                    'filepath' => $filepath,
                    'size' => filesize($filepath),
                    'type' => 'pre-restore',
                    'status' => 'completed',
                    'notes' => 'Automatic backup before restore',
                    'created_by' => $this->session->userdata('user_id')
                ]);
            }
        }
    }

    private function _check_database()
    {
        try {
            $this->db->query('SELECT 1');
            return ['status' => true, 'message' => 'Database connected'];
        } catch (Exception $e) {
            return ['status' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    private function _check_disk_space()
    {
        $free = disk_free_space('/');
        $total = disk_total_space('/');
        $percent_free = ($free / $total) * 100;

        if ($percent_free > 20) {
            return ['status' => true, 'message' => round($percent_free, 1) . '% free'];
        } else {
            return ['status' => false, 'message' => 'Low disk space: ' . round($percent_free, 1) . '% free'];
        }
    }

    private function _check_writable_dirs()
    {
        $dirs = [
            APPPATH . 'logs/',
            APPPATH . 'cache/',
            FCPATH . 'uploads/'
        ];

        $unwritable = [];
        foreach ($dirs as $dir) {
            if (!is_writable($dir)) {
                $unwritable[] = $dir;
            }
        }

        if (empty($unwritable)) {
            return ['status' => true, 'message' => 'All directories writable'];
        } else {
            return ['status' => false, 'message' => 'Not writable: ' . implode(', ', $unwritable)];
        }
    }

    private function _check_php_extensions()
    {
        $required = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'curl', 'zip'];
        $missing = [];

        foreach ($required as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }

        if (empty($missing)) {
            return ['status' => true, 'message' => 'All required extensions loaded'];
        } else {
            return ['status' => false, 'message' => 'Missing: ' . implode(', ', $missing)];
        }
    }

    private function _check_cron_jobs()
    {
        $last_day = date('Y-m-d H:i:s', strtotime('-24 hours'));
        
        $count = $this->db
            ->where('created_at >', $last_day)
            ->count_all_results('cron_log');

        if ($count > 0) {
            return ['status' => true, 'message' => "{$count} cron runs in last 24 hours"];
        } else {
            return ['status' => false, 'message' => 'No cron activity in last 24 hours'];
        }
    }
}
