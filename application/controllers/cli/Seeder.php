<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CLI Seeder
 * Usage:
 *   php index.php cli seeder generate [members=20] [loans_per_member=1] [savings_per_member=1]
 * Example:
 *   php index.php cli seeder generate 50 1 1
 */
class Seeder extends CI_Controller {

    public function __construct() {
        parent::__construct();
        if (!is_cli()) {
            echo "This controller is intended to be run from CLI only.\n";
            exit;
        }

        $this->load->database();
        $this->load->model('Savings_model');
        $this->load->model('Loan_model');
    }

    public function generate($members = 20, $loans_per_member = 1, $savings_per_member = 1) {
        $members = (int)$members ?: 20;
        $loans_per_member = (int)$loans_per_member ?: 1;
        $savings_per_member = (int)$savings_per_member ?: 1;

        // Detect CLI flags
        $argv = $_SERVER['argv'] ?? [];
        $isDry = in_array('--dry', $argv) || in_array('dry', $argv);

        echo "Seeding data: {$members} members, {$loans_per_member} loans/member, {$savings_per_member} savings/member" . ($isDry ? " (DRY RUN)" : "") . "\n";

        $this->db->trans_begin();
        try {
            // Ensure there is at least one bank account
            $bank_account = $this->db->get('bank_accounts')->row();
            if (!$bank_account) {
                $this->db->insert('bank_accounts', [
                    'bank_name' => 'Demo Bank',
                    'account_number' => 'DEMO' . strtoupper(substr(uniqid(), -6)),
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                $bank_account_id = $this->db->insert_id();
                echo "Created demo bank account id {$bank_account_id}\n";
            } else {
                $bank_account_id = $bank_account->id;
            }

            // Ensure at least one loan product exists
            $loan_product = $this->db->get('loan_products')->row();
            if (!$loan_product) {
                $this->db->insert('loan_products', [
                    'product_code' => 'TP' . strtoupper(substr(uniqid(), -4)),
                    'product_name' => 'Demo Product',
                    'min_amount' => 1000,
                    'max_amount' => 100000,
                    'interest_rate' => 12.00,
                    'min_tenure_months' => 6,
                    'max_tenure_months' => 60,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                $loan_product_id = $this->db->insert_id();
                echo "Created demo loan product id {$loan_product_id}\n";
            } else {
                $loan_product_id = $loan_product->id;
            }

            // Determine starting member code
            $row = $this->db->select('member_code')->like('member_code', 'MEMB', 'after')->order_by('id', 'DESC')->limit(1)->get('members')->row();
            $start = 1;
            if ($row && preg_match('/MEMB(\d+)/', $row->member_code, $m)) {
                $start = intval($m[1]) + 1;
            }

            $created_members = [];
            for ($i = 0; $i < $members; $i++) {
                $num = $start + $i;
                $member_code = 'MEMB' . str_pad($num, 6, '0', STR_PAD_LEFT);

                $first = 'Member' . ($num);
                $last = 'Test';
                $phone = '970' . str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT);

                $this->db->insert('members', [
                    'member_code' => $member_code,
                    'first_name' => $first,
                    'last_name' => $last,
                    'phone' => $phone,
                    'join_date' => date('Y-m-d'),
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                $member_id = $this->db->insert_id();
                $created_members[] = $member_id;
                echo "Created member {$member_code} (id: {$member_id})\n";

                // Create savings accounts
                for ($s = 0; $s < $savings_per_member; $s++) {
                    $acc_data = [
                        'account_number' => 'SAV' . date('Y') . str_pad($member_id . $s, 6, '0', STR_PAD_LEFT),
                        'member_id' => $member_id,
                        'scheme_id' => 1,
                        'current_balance' => 0.00,
                        'status' => 'active',
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    // Try using create_account if scheme exists
                    try {
                        $this->Savings_model->create_account([
                            'account_number' => $acc_data['account_number'],
                            'member_id' => $member_id,
                            'scheme_id' => 1,
                            'start_date' => date('Y-m-d'),
                            'monthly_amount' => 100.00
                        ]);
                        echo "  Created savings account for member {$member_code}\n";
                    } catch (Exception $e) {
                        // fallback to direct insert
                        $this->db->insert('savings_accounts', $acc_data);
                        echo "  Inserted savings account fallback for member {$member_code}\n";
                    }
                }

                // Create loans
                for ($l = 0; $l < $loans_per_member; $l++) {
                    // create loan_application
                    $this->db->insert('loan_applications', [
                        'application_number' => 'APP' . strtoupper(substr(uniqid(), -6)),
                        'member_id' => $member_id,
                        'loan_product_id' => $loan_product_id,
                        'requested_amount' => 5000.00,
                        'requested_tenure_months' => 12,
                        'purpose' => 'Demo loan',
                        'application_date' => date('Y-m-d'),
                        'status' => 'admin_approved',
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    $app_id = $this->db->insert_id();

                    $loan_number = 'LN' . strtoupper(substr(uniqid(), -6));
                    $principal = 5000.00;
                    $this->db->insert('loans', [
                        'loan_number' => $loan_number,
                        'loan_application_id' => $app_id,
                        'member_id' => $member_id,
                        'loan_product_id' => $loan_product_id,
                        'principal_amount' => $principal,
                        'interest_rate' => 12.00,
                        'interest_type' => 'reducing',
                        'tenure_months' => 12,
                        'emi_amount' => round(($principal / 12) + 50, 2),
                        'total_interest' => 1000.00,
                        'total_payable' => $principal + 1000.00,
                        'outstanding_principal' => $principal,
                        'outstanding_interest' => 1000.00,
                        'outstanding_fine' => 0.00,
                        'status' => 'active',
                        'disbursement_date' => date('Y-m-d'),
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    $loan_id = $this->db->insert_id();
                    echo "  Created loan {$loan_number} for member {$member_code} (loan id: {$loan_id})\n";

                    // create initial loan installment record to simulate due schedule
                    $this->db->insert('loan_installments', [
                        'loan_id' => $loan_id,
                        'installment_number' => 1,
                        'due_date' => date('Y-m-d', safe_timestamp('+1 month')),
                        'principal_amount' => round($principal / 12, 2),
                        'interest_amount' => round(1000.00 / 12, 2),
                        'emi_amount' => round(($principal / 12) + (1000.00 / 12), 2),
                        'status' => 'pending',
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                }

                // Create sample bank transactions for this member (attach to a single test import to satisfy FK)
                if (!isset($import_id)) {
                    $this->db->insert('bank_statement_imports', [
                        'import_code' => 'TESTIMP' . strtoupper(substr(uniqid(), -6)),
                        'bank_account_id' => $bank_account_id,
                        'file_name' => 'seed_import.csv',
                        'total_transactions' => 0,
                        'imported_by' => 1,
                        'imported_at' => date('Y-m-d H:i:s')
                    ]);
                    $import_id = $this->db->insert_id();
                }

                $this->db->insert('bank_transactions', [
                    'import_id' => $import_id,
                    'bank_account_id' => $bank_account_id,
                    'transaction_date' => date('Y-m-d'),
                    'amount' => 1000.00,
                    'transaction_type' => 'credit',
                    'description' => 'Seed deposit for ' . $member_code,
                    'mapping_status' => 'unmapped',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                // increment counter on import
                $this->db->set('total_transactions', 'total_transactions + 1', FALSE)->where('id', $import_id)->update('bank_statement_imports');
            }

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                throw new Exception('DB transaction failed during seeding');
            }

            // Commit or rollback depending on dry-run flag
            if ($isDry) {
                $this->db->trans_rollback();
                echo "Dry run complete: all changes rolled back.\n";
            } else {
                $this->db->trans_commit();
                echo "Seeding completed: Created " . count($created_members) . " members.\n";
            }

        } catch (Exception $e) {
            $this->db->trans_rollback();
            echo "Seeding failed: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Cleanup seeded data created by this seeder
     * Usage: php index.php cli Seeder cleanup [created_after=YYYY-MM-DD] --confirm
     */
    public function cleanup($created_after = null) {
        $argv = $_SERVER['argv'] ?? [];
        $confirm = in_array('--confirm', $argv) || in_array('--yes', $argv);

        if (!$confirm) {
            echo "Safety: Please pass --confirm to actually delete seeded data.\n";
            echo "Usage: php index.php cli Seeder cleanup [created_after=YYYY-MM-DD] --confirm\n";
            return;
        }

        if ($created_after && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $created_after)) {
            echo "Invalid date format for created_after. Use YYYY-MM-DD.\n";
            return;
        }

        // Find members created by seeder (MEMB prefix)
        $this->db->select('id')->from('members')->like('member_code', 'MEMB', 'after');
        if ($created_after) {
            $this->db->where('created_at >=', $created_after . ' 00:00:00');
        }
        $members = $this->db->get()->result();
        if (empty($members)) {
            echo "No seeded members found to delete.\n";
            return;
        }

        $member_ids = array_map(function($m){ return $m->id; }, $members);
        echo "Found " . count($member_ids) . " seeded members. Proceeding to delete related data...\n";

        $this->db->trans_begin();
        try {
            // Delete loan installments/payments and loans
            $loans = $this->db->select('id')->from('loans')->where_in('member_id', $member_ids)->get()->result();
            $loan_ids = array_map(function($l){ return $l->id; }, $loans);
            if (!empty($loan_ids)) {
                $this->db->where_in('loan_id', $loan_ids)->delete('loan_installments');
                $this->db->where_in('loan_id', $loan_ids)->delete('loan_payments');
                $this->db->where_in('id', $loan_ids)->delete('loans');
            }

            // Delete loan applications
            $this->db->where_in('member_id', $member_ids)->delete('loan_applications');

            // Delete savings accounts and transactions
            $savings = $this->db->select('id')->from('savings_accounts')->where_in('member_id', $member_ids)->get()->result();
            $saving_ids = array_map(function($s){ return $s->id; }, $savings);
            if (!empty($saving_ids)) {
                $this->db->where_in('savings_account_id', $saving_ids)->delete('savings_transactions');
                $this->db->where_in('id', $saving_ids)->delete('savings_accounts');
            }

            // Delete fines for these members
            $this->db->where_in('member_id', $member_ids)->delete('fines');

            // Delete bank transactions associated to test imports (file_name seed_import.csv or import_code TESTIMP%)
            $imports = $this->db->select('id')->from('bank_statement_imports')->group_start()->like('file_name', 'seed_import')->or_like('import_code', 'TESTIMP', 'after')->group_end()->get()->result();
            $import_ids = array_map(function($i){ return $i->id; }, $imports);
            if (!empty($import_ids)) {
                $this->db->where_in('import_id', $import_ids)->delete('bank_transactions');
                $this->db->where_in('id', $import_ids)->delete('bank_statement_imports');
            }

            // Finally delete members
            $this->db->where_in('id', $member_ids)->delete('members');

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                throw new Exception('Cleanup transaction failed');
            }

            $this->db->trans_commit();
            echo "Cleanup successful: deleted seeded members and related data.\n";

        } catch (Exception $e) {
            $this->db->trans_rollback();
            echo "Cleanup failed: " . $e->getMessage() . "\n";
        }
    }
}
