<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CLI Integration Tests
 * Run via: php index.php cli integration bank_mapping_test
 */
class Integration extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Ensure CLI only
        if (!is_cli()) {
            echo "This controller is intended to be run from CLI only.\n";
            exit;
        }
        $this->load->database();
        $this->load->model('Bank_model');
        $this->load->model('Loan_model');
    }

    public function bank_mapping_test() {
        echo "Starting Bank Import -> Mapping -> Payment integration test...\n";

        $this->db->trans_begin();

        try {
            // Create test member
            $member_code = 'TESTM' . strtoupper(substr(uniqid(), -6));
            $this->db->insert('members', [
                'member_code' => $member_code,
                'first_name' => 'Test',
                'last_name' => 'Member',
                'phone' => '999' . rand(1000000, 9999999),
                'join_date' => date('Y-m-d'),
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $member_id = $this->db->insert_id();

            // Create loan product
            $this->db->insert('loan_products', [
                'product_code' => 'TP' . strtoupper(substr(uniqid(), -4)),
                'product_name' => 'Test Product',
                'min_amount' => 100,
                'max_amount' => 100000,
                'interest_rate' => 12,
                'min_tenure_months' => 6,
                'max_tenure_months' => 60,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $product_id = $this->db->insert_id();

            // Create a minimal loan_application and then a loan
            $this->db->insert('loan_applications', [
                'application_number' => 'APP' . strtoupper(substr(uniqid(), -6)),
                'member_id' => $member_id,
                'loan_product_id' => $product_id,
                'requested_amount' => 1000.00,
                'requested_tenure_months' => 12,
                'purpose' => 'Integration test loan',
                'application_date' => date('Y-m-d'),
                'status' => 'admin_approved',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $loan_application_id = $this->db->insert_id();

            // Create loan
            $loan_number = 'LN' . strtoupper(substr(uniqid(), -6));
            $principal = 1000.00;
            $this->db->insert('loans', [
                'loan_number' => $loan_number,
                'loan_application_id' => $loan_application_id,
                'member_id' => $member_id,
                'loan_product_id' => $product_id,
                'principal_amount' => $principal,
                'interest_rate' => 12.00,
                'interest_type' => 'reducing',
                'tenure_months' => 12,
                'emi_amount' => 90.00,
                'total_interest' => 80.00,
                'total_payable' => $principal + 80.00,
                'outstanding_principal' => $principal,
                'outstanding_interest' => 80.00,
                'outstanding_fine' => 0.00,
                'status' => 'active',
                'disbursement_date' => date('Y-m-d'),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $loan_id = $this->db->insert_id();

            // Ensure a bank account exists (use first or create a test account)
            $bank_account = $this->db->get('bank_accounts')->row();
            if (!$bank_account) {
                $this->db->insert('bank_accounts', [
                    'bank_name' => 'Test Bank',
                    'account_number' => 'TB-' . strtoupper(substr(uniqid(), -6)),
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                $bank_account_id = $this->db->insert_id();
            } else {
                $bank_account_id = $bank_account->id;
            }

            // Create a bank_statement_imports record and attach transaction to it
            $admin_id = 1; // use admin user ID 1 for created_by/imported_by

            $this->db->insert('bank_statement_imports', [
                'import_code' => 'TESTIMP' . strtoupper(substr(uniqid(), -6)),
                'bank_account_id' => $bank_account_id,
                'file_name' => 'integration_test.csv',
                'total_transactions' => 1,
                'imported_by' => $admin_id,
                'imported_at' => date('Y-m-d H:i:s')
            ]);
            $import_id = $this->db->insert_id();

            // Create bank transaction (credit)
            $this->db->insert('bank_transactions', [
                'import_id' => $import_id,
                'bank_account_id' => $bank_account_id,
                'transaction_date' => date('Y-m-d'),
                'amount' => $principal,
                'transaction_type' => 'credit',
                'description' => 'Test EMI Payment',
                'mapping_status' => 'unmapped',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $transaction_id = $this->db->insert_id();
            // Map and process via Bank_model
            $mapping_data = [
                'paid_by_member_id' => $member_id,
                'paid_for_member_id' => $member_id,
                'transaction_category' => 'emi',
                'related_type' => 'loan',
                'related_id' => $loan_id,
                'mapping_remarks' => 'Integration test mapping'
            ];

            $admin_id = 1; // assume admin 1 exists
            $payment_id = $this->Bank_model->map_and_process_transaction($transaction_id, $mapping_data, $admin_id);

            if (!$payment_id) {
                throw new Exception('Payment processing failed or returned falsy value');
            }

            // Verify loan payment created
            $payment = $this->db->where('id', $payment_id)->get('loan_payments')->row();
            if (!$payment) {
                throw new Exception('Loan payment record not created');
            }

            // Verify bank transaction mapping_status
            $txn = $this->db->where('id', $transaction_id)->get('bank_transactions')->row();
            if ($txn->mapping_status !== 'mapped') {
                throw new Exception('Bank transaction not marked as mapped');
            }

            // Verify loan outstanding updated
            $loan = $this->db->where('id', $loan_id)->get('loans')->row();
            if ($loan->outstanding_principal != ($principal - $payment->principal_component)) {
                throw new Exception('Loan outstanding principal not updated correctly');
            }

            // Success
            echo "Integration test PASSED: payment ID {$payment_id}, transaction {$transaction_id} processed.\n";

            // Rollback so test doesn't persist data
            $this->db->trans_rollback();

        } catch (Exception $e) {
            $this->db->trans_rollback();
            echo "Integration test FAILED: " . $e->getMessage() . "\n";
            return;
        }
    }
}
