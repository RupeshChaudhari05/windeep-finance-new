<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CLI Flush Members
 * Usage examples:
 *   php index.php cli flush_members ids 1,2,3 --confirm
 *   php index.php cli flush_members all --confirm --really=yes
 *   php index.php cli flush_members pattern MEMB --confirm
 * Options:
 *   --dry    : do a dry run (no changes)
 *   --confirm: required to perform destructive actions
 */
class Flush_members extends CI_Controller {

    public function __construct() {
        parent::__construct();
        if (!is_cli()) {
            echo "This controller is intended to be run from CLI only.\n";
            exit;
        }

        $this->load->database();
        $this->load->helper('file');
    }

    /**
     * Entrypoint.
     * param $mode: 'ids', 'all', or 'pattern'
     * param $value: comma list of ids OR pattern value
     */
    public function run($mode = 'ids', $value = null) {
        $argv = $_SERVER['argv'] ?? [];
        $isDry = in_array('--dry', $argv) || in_array('dry', $argv);
        $confirm = in_array('--confirm', $argv) || in_array('--yes', $argv);
        $really = false;
        foreach ($argv as $a) {
            if (strpos($a, '--really=') === 0) {
                $parts = explode('=', $a, 2);
                $really = isset($parts[1]) && strtolower($parts[1]) === 'yes';
            }
        }

        echo "Flush Members: mode={$mode} value=" . ($value ?? '[none]') . ($isDry ? " (DRY RUN)" : "") . "\n";

        if ($mode === 'all' && !$confirm) {
            echo "Safety: to flush ALL members pass --confirm and --really=yes.\n";
            echo "Usage: php index.php cli flush_members all --confirm --really=yes\n";
            return;
        }
        if ($mode === 'all' && !$really) {
            echo "Safety: require --really=yes when using 'all' to avoid accidental wipes.\n";
            return;
        }

        if (!$confirm && !$isDry) {
            echo "Safety: destructive action requires --confirm (or use --dry for testing).\n";
            return;
        }

        // Resolve member IDs
        $member_ids = [];
        if ($mode === 'ids') {
            if (empty($value)) {
                echo "Provide comma separated ids: php index.php cli flush_members ids 1,2,3 --confirm\n";
                return;
            }
            $parts = array_filter(array_map('trim', explode(',', $value)));
            foreach ($parts as $p) {
                if (is_numeric($p)) $member_ids[] = (int)$p;
            }
        } elseif ($mode === 'pattern') {
            if (empty($value)) {
                echo "Provide a LIKE pattern fragment: php index.php cli flush_members pattern MEMB --confirm\n";
                return;
            }
            $q = $this->db->select('id')->from('members')->like('member_code', $value, 'after')->get()->result();
            $member_ids = array_map(function($m){ return $m->id; }, $q);
        } elseif ($mode === 'all') {
            $q = $this->db->select('id')->from('members')->get()->result();
            $member_ids = array_map(function($m){ return $m->id; }, $q);
        } else {
            echo "Unknown mode. Use 'ids', 'pattern' or 'all'.\n";
            return;
        }

        if (empty($member_ids)) {
            echo "No members found for the selected criteria.\n";
            return;
        }

        echo "Found " . count($member_ids) . " members to flush.\n";

        // Safety: show list preview in dry-run
        if ($isDry) {
            echo "Member IDs: " . implode(',', $member_ids) . "\n";
        }

        $this->db->trans_begin();
        try {
            // Helper: delete from table if exists
            $delIfExists = function($table, $whereField = null, $values = null) use ($isDry) {
                if (!$this->db->table_exists($table)) return;
                if ($isDry) {
                    echo "[DRY] Would delete from {$table}" . ( $whereField ? " where {$whereField} in (" . implode(',', $values) . ")" : "" ) . "\n";
                    return;
                }
                if ($whereField && $values) {
                    $this->db->where_in($whereField, $values)->delete($table);
                } else {
                    $this->db->empty_table($table);
                }
                echo "Deleted rows from {$table}\n";
            };

            // 1) Loans -> installments, payments, loans
            if ($this->db->table_exists('loans')) {
                $loans = $this->db->select('id')->from('loans')->where_in('member_id', $member_ids)->get()->result();
                $loan_ids = array_map(function($l){ return $l->id; }, $loans);
                if (!empty($loan_ids)) {
                    $delIfExists('loan_installments', 'loan_id', $loan_ids);
                    $delIfExists('loan_payments', 'loan_id', $loan_ids);
                    $delIfExists('loans', 'id', $loan_ids);
                }
            }

            // 2) Loan applications
            $delIfExists('loan_applications', 'member_id', $member_ids);

            // 3) Savings accounts & transactions
            if ($this->db->table_exists('savings_accounts')) {
                $savings = $this->db->select('id')->from('savings_accounts')->where_in('member_id', $member_ids)->get()->result();
                $savings_ids = array_map(function($s){ return $s->id; }, $savings);
                if (!empty($savings_ids)) {
                    $delIfExists('savings_transactions', 'savings_account_id', $savings_ids);
                    $delIfExists('savings_accounts', 'id', $savings_ids);
                }
            }

            // 4) Fines
            $delIfExists('fines', 'member_id', $member_ids);

            // 5) Member other transactions and bonus transactions
            $delIfExists('member_other_transactions', 'member_id', $member_ids);
            $delIfExists('bonus_transactions', 'member_id', $member_ids);

            // 6) Bank transactions referencing member
            if ($this->db->table_exists('bank_transactions')) {
                // try common member-related fields
                $fields = ['paid_by_member_id', 'paid_for_member_id', 'member_id'];
                foreach ($fields as $f) {
                    if ($this->db->field_exists($f, 'bank_transactions')) {
                        $delIfExists('bank_transactions', $f, $member_ids);
                    }
                }
            }

            // 7) Notifications (recipient/target)
            if ($this->db->table_exists('notifications')) {
                if ($this->db->field_exists('recipient_id', 'notifications')) $delIfExists('notifications', 'recipient_id', $member_ids);
                if ($this->db->field_exists('target_id', 'notifications')) $delIfExists('notifications', 'target_id', $member_ids);
            }

            // 8) Attempt to remove uploaded files for members (members/uploads/{id} and uploads/profile_images)
            foreach ($member_ids as $mid) {
                $member_path = FCPATH . 'members/uploads/' . $mid;
                if (is_dir($member_path)) {
                    if ($isDry) {
                        echo "[DRY] Would remove directory: {$member_path}\n";
                    } else {
                        delete_files($member_path, true);
                        @rmdir($member_path);
                        echo "Removed files in {$member_path}\n";
                    }
                }
                // profile images stored under uploads/profile_images may be shared; do not delete globally
            }

            // 9) Finally delete members
            $delIfExists('members', 'id', $member_ids);

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                throw new Exception('DB transaction failed during flush');
            }

            if ($isDry) {
                $this->db->trans_rollback();
                echo "Dry run complete: no changes were made.\n";
            } else {
                $this->db->trans_commit();
                echo "Flush complete: deleted members and related data.\n";
            }

        } catch (Exception $e) {
            $this->db->trans_rollback();
            echo "Flush failed: " . $e->getMessage() . "\n";
        }
    }
}
