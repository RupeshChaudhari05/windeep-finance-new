<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin Migration Controller
 * 
 * Manages database migrations from admin panel
 * - Upload migration files
 * - Execute migrations
 * - View migration history
 * - Track execution status
 */
class Migrations extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Migration_model');
    }

    /**
     * Main migration manager dashboard
     */
    public function index()
    {
        $this->admin_access_check(); // Check if user is admin

        $data['page_title'] = 'Database Migrations';
        $data['available_migrations'] = $this->get_available_migrations();
        $data['migration_history'] = $this->Migration_model->get_migration_history(20);
        $data['pending_migrations'] = $this->Migration_model->get_pending_migrations();

        $this->load->view('admin/migrations/index', $data);
    }

    /**
     * Get list of migration files in database/migrations folder
     */
    private function get_available_migrations()
    {
        $migrations_path = APPPATH . '../database/migrations/';
        $available = [];

        if (is_dir($migrations_path)) {
            $files = scandir($migrations_path);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                    $full_path = $migrations_path . $file;
                    $executed = $this->Migration_model->is_migration_executed($file);
                    $available[] = [
                        'name' => $file,
                        'path' => $full_path,
                        'size' => filesize($full_path),
                        'size_formatted' => $this->format_bytes(filesize($full_path)),
                        'executed' => $executed,
                        'modified' => filemtime($full_path),
                        'modified_date' => date('Y-m-d H:i:s', filemtime($full_path))
                    ];
                }
            }
        }

        return $available;
    }

    /**
     * Format bytes to human readable
     */
    private function format_bytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Execute a specific migration
     */
    public function execute($migration_name = null)
    {
        $this->admin_access_check();

        if ($migration_name === null) {
            json_response(['success' => false, 'message' => 'Migration name not provided']);
        }

        // Security: only allow .sql files
        if (pathinfo($migration_name, PATHINFO_EXTENSION) !== 'sql') {
            json_response(['success' => false, 'message' => 'Invalid file type']);
        }

        $migration_path = APPPATH . '../database/migrations/' . $migration_name;

        // Check if file exists
        if (!file_exists($migration_path)) {
            json_response(['success' => false, 'message' => 'Migration file not found']);
        }

        // Check if already executed
        if ($this->Migration_model->is_migration_executed($migration_name)) {
            json_response(['success' => false, 'message' => 'This migration has already been executed']);
        }

        // Read migration content
        $migration_content = file_get_contents($migration_path);

        // Execute migration
        $result = $this->execute_migration_sql($migration_name, $migration_content);

        json_response($result);
    }

    /**
     * Execute migration SQL safely
     */
    private function execute_migration_sql($migration_name, $sql_content)
    {
        $start_time = microtime(true);
        $admin_id = $this->session->userdata('admin_id') ?? $this->session->userdata('id');

        try {
            // Record migration as running
            $this->Migration_model->update_migration_status($migration_name, 'running', $admin_id);

            // Split SQL by semicolon and execute each statement
            $statements = array_filter(
                array_map('trim', explode(';', $sql_content)),
                fn($stmt) => !empty($stmt) && !str_starts_with($stmt, '--')
            );

            $output = [];
            foreach ($statements as $statement) {
                $result = $this->db->query($statement);
                $output[] = "✓ Statement executed";
            }

            $duration = round((microtime(true) - $start_time) * 1000); // milliseconds

            // Record migration as completed
            $this->Migration_model->update_migration_status(
                $migration_name,
                'completed',
                $admin_id,
                implode("\n", $output),
                null,
                $duration
            );

            return [
                'success' => true,
                'message' => "Migration '{$migration_name}' executed successfully",
                'duration' => $duration . 'ms',
                'output' => $output
            ];

        } catch (Exception $e) {
            $error_msg = $e->getMessage();

            // Record migration as failed
            $this->Migration_model->update_migration_status(
                $migration_name,
                'failed',
                $admin_id,
                null,
                $error_msg
            );

            return [
                'success' => false,
                'message' => "Migration failed: " . $error_msg,
                'error' => $error_msg
            ];
        }
    }

    /**
     * View migration history/details
     */
    public function history()
    {
        $this->admin_access_check();

        $data['page_title'] = 'Migration History';
        $data['migrations'] = $this->Migration_model->get_migration_history(100);

        $this->load->view('admin/migrations/history', $data);
    }

    /**
     * Get migration details (AJAX)
     */
    public function get_details($migration_id)
    {
        $this->admin_access_check();

        $details = $this->Migration_model->get_migration_by_id($migration_id);
        json_response(['data' => $details]);
    }

    /**
     * Admin access check
     */
    private function admin_access_check()
    {
        // Check if user is logged in and is admin
        if (!$this->session->userdata('admin_id') && !$this->session->userdata('user_role') === 'admin') {
            redirect('admin/login');
        }
    }
}

function json_response($data)
{
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
