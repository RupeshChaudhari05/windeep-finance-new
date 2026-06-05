<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration_Model
 * 
 * Handles database operations for migration tracking
 */
class Migration_model extends MY_Model
{
    protected $table = 'migrations';

    /**
     * Check if migration has been executed
     */
    public function is_migration_executed($migration_name)
    {
        $this->db->where('migration_name', $migration_name);
        $this->db->where_in('status', ['completed']);
        return $this->db->count_all_results($this->table) > 0;
    }

    /**
     * Get migration by ID
     */
    public function get_migration_by_id($id)
    {
        return $this->db->where('id', $id)->get($this->table)->row();
    }

    /**
     * Get migration history with limit
     */
    public function get_migration_history($limit = 50)
    {
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit);
        return $this->db->get($this->table)->result();
    }

    /**
     * Get pending migrations
     */
    public function get_pending_migrations()
    {
        $this->db->where('status', 'pending');
        $this->db->order_by('created_at', 'ASC');
        return $this->db->get($this->table)->result();
    }

    /**
     * Get failed migrations
     */
    public function get_failed_migrations()
    {
        $this->db->where('status', 'failed');
        $this->db->order_by('execution_timestamp', 'DESC');
        return $this->db->get($this->table)->result();
    }

    /**
     * Update migration status
     */
    public function update_migration_status(
        $migration_name,
        $status,
        $executed_by = null,
        $output_log = null,
        $error_message = null,
        $duration_seconds = null
    ) {
        $update_data = [
            'status' => $status,
            'executed_by' => $executed_by,
            'execution_timestamp' => ($status === 'running' || $status === 'completed') ? date('Y-m-d H:i:s') : null,
            'completion_timestamp' => ($status === 'completed') ? date('Y-m-d H:i:s') : null,
            'duration_seconds' => $duration_seconds,
            'output_log' => $output_log,
            'error_message' => $error_message
        ];

        // Remove null values to not overwrite existing data
        $update_data = array_filter($update_data, fn($v) => $v !== null);

        $this->db->where('migration_name', $migration_name);
        return $this->db->update($this->table, $update_data);
    }

    /**
     * Record migration execution
     */
    public function record_migration(
        $migration_name,
        $migration_content,
        $executed_by,
        $success = true,
        $output = null,
        $error = null,
        $duration = null
    ) {
        $data = [
            'migration_name' => $migration_name,
            'migration_file' => $migration_content,
            'status' => $success ? 'completed' : 'failed',
            'executed_by' => $executed_by,
            'execution_timestamp' => date('Y-m-d H:i:s'),
            'completion_timestamp' => date('Y-m-d H:i:s'),
            'duration_seconds' => $duration,
            'output_log' => $output,
            'error_message' => $error,
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->db->insert($this->table, $data);
    }

    /**
     * Get migration statistics
     */
    public function get_statistics()
    {
        $total = $this->db->count_all($this->table);
        $completed = $this->db->where('status', 'completed')->count_all_results($this->table);
        $failed = $this->db->where('status', 'failed')->count_all_results($this->table);
        $pending = $this->db->where('status', 'pending')->count_all_results($this->table);

        return [
            'total' => $total,
            'completed' => $completed,
            'failed' => $failed,
            'pending' => $pending,
            'success_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0
        ];
    }

    /**
     * Clear migration history (admin only)
     */
    public function clear_history($status = null)
    {
        if ($status) {
            $this->db->where('status', $status);
        }
        return $this->db->delete($this->table);
    }
}
