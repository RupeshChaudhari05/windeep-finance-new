<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bank extends Admin_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('Bank_model');
        $this->load->model('Member_model');
        $this->load->library('form_validation');
        $this->load->helper('form');
        $this->data['active_menu'] = 'bank';
    }
    
    public function import() {
        $this->data['page_title'] = 'Bank Statement Import';
        $this->data['breadcrumb'] = [['label' => 'Bank Import']];
        
        // Get bank accounts
        $this->data['bank_accounts'] = $this->Bank_model->get_accounts();
        
        // Get recent imports
        $this->data['recent_imports'] = $this->Bank_model->get_imports(10);
        
        $this->load->view('admin/layouts/header', $this->data);
        $this->load->view('admin/layouts/sidebar', $this->data);
        $this->load->view('admin/bank/import', $this->data);
        $this->load->view('admin/layouts/footer', $this->data);
    }

    /**
     * Unified Bank Statement - All transactions for a financial year as a single continuous statement
     */
    public function statement() {
        $this->data['page_title'] = 'Bank Statement';
        $this->data['breadcrumb'] = [
            ['label' => 'Bank', 'link' => site_url('admin/bank/import')],
            ['label' => 'Statement']
        ];

        // Load financial years
        $this->load->model('Financial_year_model');
        $financial_years = $this->Financial_year_model->get_all_years();
        $this->data['financial_years'] = $financial_years;

        // Get active financial year as default
        $active_fy = $this->Financial_year_model->get_active();

        // Determine selected financial year
        $fy_id = $this->input->get('fy_id');
        $selected_fy = null;
        if ($fy_id) {
            foreach ($financial_years as $fy) {
                if ($fy->id == $fy_id) { $selected_fy = $fy; break; }
            }
        }
        if (!$selected_fy && $active_fy) {
            $selected_fy = $active_fy;
        }
        if (!$selected_fy && !empty($financial_years)) {
            $selected_fy = $financial_years[0];
        }
        $this->data['selected_fy'] = $selected_fy;

        // Get bank accounts for filter
        $this->data['bank_accounts'] = $this->Bank_model->get_accounts();

        // Build filters
        $filters = [
            'bank_id' => $this->input->get('bank_id'),
            'mapping_status' => $this->input->get('mapping_status'),
            'member_id' => $this->input->get('member_id'),
            'transaction_type' => $this->input->get('transaction_type'),
        ];

        // Use financial year date range
        if ($selected_fy) {
            $filters['from_date'] = $selected_fy->start_date;
            $filters['to_date'] = $selected_fy->end_date;
        }

        $this->data['filters'] = $filters;

        // Get all transactions for the financial year (no limit - single continuous statement)
        $this->data['transactions'] = $this->Bank_model->get_transactions_for_mapping($filters);

        // Provide JS config for mapping modal
        $config = "<script>\n" .
                  "window.BANK_MAPPING_CONFIG = {\n" .
                  "  search_members_url: '" . site_url('admin/bank/search_members') . "',\n" .
                  "  get_member_details_url: '" . site_url('admin/bank/get_member_details') . "',\n" .
                  "  get_member_accounts_url: '" . site_url('admin/bank/get_member_accounts') . "',\n" .
                  "  save_mapping_url: '" . site_url('admin/bank/save_transaction_mapping') . "',\n" .
                  "  calculate_fine_url: '" . site_url('admin/bank/calculate_fine_due') . "',\n" .
                  "  get_mapping_details_url: '" . site_url('admin/bank/get_mapping_details') . "',\n" .
                  "  reverse_mapping_url: '" . site_url('admin/bank/reverse_mapping') . "',\n" .
                  "  map_disbursement_url: '" . site_url('admin/bank/map_disbursement') . "',\n" .
                  "  map_internal_url: '" . site_url('admin/bank/map_internal') . "',\n" .
                  "  get_disbursable_loans_url: '" . site_url('admin/bank/get_disbursable_loans') . "',\n" .
                  "  ignore_transaction_url: '" . site_url('admin/bank/ignore_transaction') . "',\n" .
                  "  restore_transaction_url: '" . site_url('admin/bank/restore_transaction') . "'\n" .
                  "};\n" .
                  "</script>";

        $this->data['extra_js'] = $config;

        $this->load->view('admin/layouts/header', $this->data);
        $this->load->view('admin/layouts/sidebar', $this->data);
        $this->load->view('admin/bank/statement', $this->data);
        $this->load->view('admin/layouts/footer', $this->data);
    }

    /**
     * Download Sample Excel Format
     */
    public function download_sample() {
        // Load PHPSpreadsheet
        require_once FCPATH . 'vendor/autoload.php';
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator('WinDeep Finance')
            ->setTitle('Bank Statement Import Template')
            ->setSubject('Sample format for bank statement import')
            ->setDescription('Use this template to import bank transactions');
        
        // Set column headers with styling
        $headers = ['TransaDate', 'Description', 'Credit', 'Debit', 'Reference'];
        $sheet->fromArray($headers, NULL, 'A1');
        
        // Style header row
        $headerStyle = [
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
        ];
        $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);
        
        // Add sample data rows
        $sampleData = [
            ['05/01/2026', 'MEMB000001 Monthly Savings Deposit', '5000', '', 'UPI/123456789'],
            ['06/01/2026', 'MEMB000025 Loan EMI Payment LNC440DA', '3500', '', 'NEFT/987654321'],
            ['08/01/2026', 'UPI Payment from Member 9707502695', '2000', '', 'UPI/456789123'],
            ['10/01/2026', 'MEMB000042 Fine Payment for Late EMI', '500', '', 'UPI/741852963'],
            ['12/01/2026', 'SAV2025000120 Savings Account Deposit', '10000', '', 'RTGS/159357246'],
            ['15/01/2026', 'Bank Charges - SMS Service', '', '250', 'CHG/202601'],
            ['18/01/2026', 'Cash Withdrawal from Branch', '', '50000', 'WDL/202601001'],
        ];
        
        $rowNum = 2;
        foreach ($sampleData as $data) {
            $sheet->fromArray($data, NULL, 'A' . $rowNum);
            $rowNum++;
        }
        
        // Format amount columns
        $sheet->getStyle('C2:D' . ($rowNum - 1))->getNumberFormat()
              ->setFormatCode('#,##0.00');
        
        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(45);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(20);
        
        // Add instructions sheet
        $instructionsSheet = $spreadsheet->createSheet();
        $instructionsSheet->setTitle('Instructions');
        
        $instructions = [
            ['Bank Statement Import Instructions'],
            [''],
            ['Required Columns:'],
            ['1. TransaDate', 'Transaction date (DD/MM/YYYY format)'],
            ['2. Description', 'Transaction description/narration'],
            ['3. Credit', 'Amount received (money IN) - enter positive numbers'],
            ['4. Debit', 'Amount spent (money OUT) - enter positive numbers'],
            ['5. Reference', 'Transaction reference/UTR number (optional)'],
            [''],
            ['Important Notes:'],
            ['• Each transaction should have EITHER Credit OR Debit amount (not both)'],
            ['• Leave the other amount column blank'],
            ['• Dates should be in DD/MM/YYYY, DD-MM-YYYY, or YYYY-MM-DD format'],
            ['• For auto-matching, include member codes (MEMB000001), phone numbers,'],
            ['  savings account numbers (SAV2025000120), or loan numbers (LNC440DA)'],
            ['  in the description field'],
            [''],
            ['Example Patterns for Auto-Matching:'],
            ['• Member Code: "MEMB000001 Deposit" or "Payment from MEMB000025"'],
            ['• Phone: "UPI Payment 9707502695" (10-digit phone number)'],
            ['• Savings Account: "SAV2025000120 Deposit"'],
            ['• Loan Number: "LNC440DA EMI Payment"'],
            [''],
            ['File Format:'],
            ['• Supported formats: Excel (.xlsx, .xls) or CSV'],
            ['• Maximum file size: 10MB'],
            ['• First row must contain column headers'],
            [''],
            ['After Import:'],
            ['• System will auto-match transactions where possible'],
            ['• You can manually map remaining transactions'],
            ['• Split payments across multiple members/accounts are supported'],
        ];
        
        $instructionsSheet->fromArray($instructions, NULL, 'A1');
        $instructionsSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $instructionsSheet->getStyle('A3')->getFont()->setBold(true);
        $instructionsSheet->getStyle('A10')->getFont()->setBold(true);
        $instructionsSheet->getStyle('A18')->getFont()->setBold(true);
        $instructionsSheet->getStyle('A25')->getFont()->setBold(true);
        $instructionsSheet->getStyle('A30')->getFont()->setBold(true);
        $instructionsSheet->getColumnDimension('A')->setWidth(60);
        $instructionsSheet->getColumnDimension('B')->setWidth(50);
        
        // Set active sheet back to data sheet
        $spreadsheet->setActiveSheetIndex(0);
        
        // Generate filename
        $filename = 'Bank_Statement_Import_Template_' . date('Y-m-d') . '.xlsx';
        
        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
    
    public function upload() {
        if (!$this->input->post()) {
            redirect('admin/bank/import');
        }
        
        $bank_account_id = $this->input->post('bank_account_id');
        $statement_date = $this->input->post('statement_date');
        
        // Handle file upload
        $upload_path = FCPATH . 'uploads/bank_statements/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0755, true);
        }
        
        $config['upload_path'] = $upload_path;
        $config['allowed_types'] = 'csv|xlsx|xls';
        $config['max_size'] = 10240; // 10MB
        $config['file_name'] = 'statement_' . time();
        
        $this->load->library('upload', $config);
        
        if (!$this->upload->do_upload('statement_file')) {
            $this->session->set_flashdata('error', $this->upload->display_errors());
            redirect('admin/bank/import');
        }
        
        $file_data = $this->upload->data();

        // Optional mapping column (1-based index)
        $mapping_column = $this->input->post('mapping_column');

        // Import statement
        try {
            $result = $this->Bank_model->import_statement(
                $file_data['full_path'],
                $bank_account_id,
                $this->session->userdata('admin_id'),
                $mapping_column
            );
            
            if ($result['success']) {
                $this->session->set_flashdata('success', "Bank statement imported successfully. {$result['total']} transactions imported, {$result['matched']} auto-matched.");
                redirect('admin/bank/view_import/' . $result['import_id']);
            } else {
                $this->session->set_flashdata('error', $result['message']);
                redirect('admin/bank/import');
            }
        } catch (Exception $e) {
            $this->session->set_flashdata('error', 'Import failed: ' . $e->getMessage());
            redirect('admin/bank/import');
        }
    }
    
    public function view_import($import_id) {
        $import = $this->Bank_model->get_import($import_id);
        if (!$import) {
            show_404();
        }
        
        $this->data['page_title'] = 'Bank Import - ' . $import->import_code;
        $this->data['breadcrumb'] = [
            ['label' => 'Bank Import', 'link' => site_url('admin/bank/import')],
            ['label' => $import->import_code]
        ];
        
        $this->data['import'] = $import;
        $this->data['transactions'] = $this->Bank_model->get_import_transactions($import_id);
        
        $this->load->view('admin/layouts/header', $this->data);
        $this->load->view('admin/layouts/sidebar', $this->data);
        $this->load->view('admin/bank/view_import', $this->data);
        $this->load->view('admin/layouts/footer', $this->data);
    }
    
    public function match_transaction() {
        if (!$this->input->post()) {
            $this->ajax_response(false, 'Invalid request');
        }
        
        $transaction_id = $this->input->post('transaction_id');
        $match_type = $this->input->post('match_type'); // savings_payment, loan_payment, etc.
        $match_id = $this->input->post('match_id');
        
        $result = $this->Bank_model->match_transaction($transaction_id, $match_type, $match_id);
        
        if ($result) {
            $this->log_activity('bank_transaction_matched', "Matched bank transaction #{$transaction_id}");
            $this->ajax_response(true, 'Transaction matched successfully');
        } else {
            $this->ajax_response(false, 'Failed to match transaction');
        }
    }
    
    public function confirm_match() {
        if (!$this->input->post()) {
            $this->ajax_response(false, 'Invalid request');
        }
        
        $transaction_id = $this->input->post('transaction_id');
        
        $result = $this->Bank_model->confirm_transaction($transaction_id);
        
        if ($result) {
            $this->log_activity('bank_transaction_confirmed', "Confirmed bank transaction #{$transaction_id}");
            $this->ajax_response(true, 'Transaction confirmed');
        } else {
            $this->ajax_response(false, 'Failed to confirm transaction');
        }
    }
    
    public function unmatched() {
        $this->data['page_title'] = 'Unmatched Transactions';
        $this->data['breadcrumb'] = [
            ['label' => 'Bank Import', 'link' => site_url('admin/bank/import')],
            ['label' => 'Unmatched']
        ];
        
        // Get unmatched transactions
        $this->data['transactions'] = $this->Bank_model->get_unmatched_transactions();
        
        // Get potential matches
        $this->data['savings_payments'] = $this->Bank_model->get_potential_savings_matches();
        $this->data['loan_payments'] = $this->Bank_model->get_potential_loan_matches();
        
        $this->load->view('admin/layouts/header', $this->data);
        $this->load->view('admin/layouts/sidebar', $this->data);
        $this->load->view('admin/bank/unmatched', $this->data);
        $this->load->view('admin/layouts/footer', $this->data);
    }
    
    /**
     * Bank Transaction Mapping Interface
     */
    public function mapping() {
        $this->data['page_title'] = 'Bank Transaction Mapping';
        $this->data['breadcrumb'] = [
            ['label' => 'Bank', 'link' => site_url('admin/bank/import')],
            ['label' => 'Mapping']
        ];
        
        // Get bank accounts for filter
        $this->data['bank_accounts'] = $this->Bank_model->get_accounts();
        
        // Get filters
        $filters = [
            'bank_id' => $this->input->get('bank_id'),
            'from_date' => $this->input->get('from_date') ?: date('Y-m-01'),
            'to_date' => $this->input->get('to_date') ?: date('Y-m-d'),
            'mapping_status' => $this->input->get('mapping_status') ?: 'unmapped'
        ];
        
        $this->data['filters'] = $filters;
        
        // Get transactions
        $this->data['transactions'] = $this->Bank_model->get_transactions_for_mapping($filters);
        
        // Get transaction categories
        $this->data['transaction_categories'] = [
            'emi' => 'Loan EMI Payment',
            'savings' => 'Savings Deposit',
            'share' => 'Share Capital',
            'fine' => 'Fine Payment',
            'withdrawal' => 'Withdrawal',
            'other' => 'Other'
        ];

        // Provide JS config and include mapping asset so it runs after footer scripts (jQuery)
        $config = "<script>\n" .
                  "window.BANK_MAPPING_CONFIG = {\n" .
                  "  search_members_url: '" . site_url('admin/bank/search_members') . "',\n" .
                  "  get_member_details_url: '" . site_url('admin/bank/get_member_details') . "',\n" .
                  "  get_member_accounts_url: '" . site_url('admin/bank/get_member_accounts') . "',\n" .
                  "  save_mapping_url: '" . site_url('admin/bank/save_transaction_mapping') . "',\n" .
                  "  calculate_fine_url: '" . site_url('admin/bank/calculate_fine_due') . "',\n" .
                  "  get_mapping_details_url: '" . site_url('admin/bank/get_mapping_details') . "',\n" .
                  "  reverse_mapping_url: '" . site_url('admin/bank/reverse_mapping') . "',\n" .
                  "  map_disbursement_url: '" . site_url('admin/bank/map_disbursement') . "',\n" .
                  "  map_internal_url: '" . site_url('admin/bank/map_internal') . "',\n" .
                  "  get_disbursable_loans_url: '" . site_url('admin/bank/get_disbursable_loans') . "',\n" .
                  "  ignore_transaction_url: '" . site_url('admin/bank/ignore_transaction') . "',\n" .
                  "  restore_transaction_url: '" . site_url('admin/bank/restore_transaction') . "'\n" .
                  "};\n" .
                  "</script>\n" .
                  "<script src='" . base_url('assets/js/admin/bank-mapping.js') . "'></script>";

        $this->data['extra_js'] = $config;

        $this->load->view('admin/layouts/header', $this->data);
        $this->load->view('admin/layouts/sidebar', $this->data);
        $this->load->view('admin/bank/mapping', $this->data);
        $this->load->view('admin/layouts/footer', $this->data);
    }
    
    /**
     * Bank Transactions with Mapping Interface
     */
    public function transactions() {
        $this->data['page_title'] = 'Bank Transactions';
        $this->data['breadcrumb'] = [
            ['label' => 'Bank', 'link' => site_url('admin/bank/import')],
            ['label' => 'Transactions']
        ];
        
        // Get bank accounts for filter
        $this->data['bank_accounts'] = $this->Bank_model->get_accounts();
        
        // Get filters
        $filters = [
            'bank_id' => $this->input->get('bank_id'),
            'from_date' => $this->input->get('from_date') ?: date('Y-m-01'),
            'to_date' => $this->input->get('to_date') ?: date('Y-m-d'),
            'mapping_status' => $this->input->get('mapping_status')
        ];
        
        $this->data['filters'] = $filters;
        
        // Get transactions
        $this->data['transactions'] = $this->Bank_model->get_transactions_for_mapping($filters);
        
        $this->load->view('admin/layouts/header', $this->data);
        $this->load->view('admin/layouts/sidebar', $this->data);
        $this->load->view('admin/bank/transactions', $this->data);
        $this->load->view('admin/layouts/footer', $this->data);
    }
    
    /**
     * Save Transaction Mapping (AJAX)
     */
    public function save_transaction_mapping() {
        if (!$this->input->is_ajax_request() && empty(trim(file_get_contents('php://input')))) {
            $this->ajax_response(false, 'Invalid request');
            return;
        }

        // Support JSON payloads for multi-row mappings
        $payload = $this->input->post();
        $raw = trim(file_get_contents('php://input'));
        if (empty($payload) && !empty($raw)) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $payload = $decoded;
            }
        }

        $transaction_id = $payload['transaction_id'] ?? $this->input->post('transaction_id');
        $remarks = $payload['remarks'] ?? $this->input->post('remarks');
        $admin_id = $this->session->userdata('admin_id');

        // If mappings array present, handle multi-mapping
        $mappings = $payload['mappings'] ?? null;
        if ($mappings && is_array($mappings)) {
            // Start transaction FIRST then lock the row to prevent double-mapping (BANK-1)
            $this->db->trans_begin();

            // SELECT ... FOR UPDATE to prevent concurrent mapping of the same transaction
            $txn = $this->db->query(
                'SELECT * FROM bank_transactions WHERE id = ? FOR UPDATE',
                [$transaction_id]
            )->row();
            if (!$txn) { 
                $this->db->trans_rollback();
                log_message('error', 'Transaction not found: ID=' . $transaction_id);
                $this->ajax_response(false, 'Transaction not found'); 
                return; 
            }

            // BANK-2: Reject if transaction is already fully mapped
            if ($txn->mapping_status === 'mapped') {
                $this->db->trans_rollback();
                $this->ajax_response(false, 'This transaction is already fully mapped. Unmap it first to re-map.');
                return;
            }

            // Determine transaction amount - try multiple fields
            $txn_amount = 0;
            if (isset($txn->credit_amount) && $txn->credit_amount > 0) {
                $txn_amount = floatval($txn->credit_amount);
            } elseif (isset($txn->debit_amount) && $txn->debit_amount > 0) {
                $txn_amount = floatval(abs($txn->debit_amount));
            } elseif (isset($txn->amount) && $txn->amount > 0) {
                $txn_amount = floatval($txn->amount);
            }

            // Account for already-mapped amount (partial mappings)
            $already_mapped = floatval($txn->mapped_amount ?? 0);
            $available_amount = $txn_amount - $already_mapped;

            // Calculate total mapped amount
            $total = 0;
            $mapping_details = [];
            foreach ($mappings as $index => $m) {
                $amt = floatval($m['amount'] ?? 0);
                $total += $amt;
                $mapping_details[] = [
                    'index' => $index,
                    'amount' => $amt,
                    'type' => $m['transaction_type'] ?? 'unknown',
                    'member_id' => $m['paid_for_member_id'] ?? 'none'
                ];
            }

            // Log detailed information
            log_message('debug', 'Transaction Mapping Details:');
            log_message('debug', '  Transaction ID: ' . $transaction_id);
            log_message('debug', '  Transaction Amount: ' . $txn_amount);
            log_message('debug', '  Already Mapped: ' . $already_mapped);
            log_message('debug', '  Available: ' . $available_amount);
            log_message('debug', '  Total New Mapped: ' . $total);
            log_message('debug', '  Mappings: ' . json_encode($mapping_details));
            log_message('debug', '  Transaction Object: ' . json_encode($txn));

            // Use a small tolerance for floating point comparison (0.01 = 1 paisa)
            if ($total > ($available_amount + 0.01)) {
                $this->db->trans_rollback();
                $cs = get_currency_symbol();
                $error_msg = sprintf(
                    'Mapped amounts (%s%.2f) exceed available amount (%s%.2f). Transaction total: %s%.2f, already mapped: %s%.2f.',
                    $cs, $total,
                    $cs, $available_amount,
                    $cs, $txn_amount,
                    $cs, $already_mapped
                );
                log_message('error', 'Mapping validation failed: ' . $error_msg);
                $this->ajax_response(false, $error_msg);
                return;
            }

            if ($txn_amount == 0) {
                $this->db->trans_rollback();
                log_message('error', 'Transaction amount is zero. Cannot map. Transaction: ' . json_encode($txn));
                $this->ajax_response(false, 'Transaction amount is zero. Cannot proceed with mapping.');
                return;
            }

            try {
                foreach ($mappings as $m) {
                    $paying = $m['paying_member_id'] ?? null;
                    $paid_for = $m['paid_for_member_id'] ?? null;
                    $type = $m['transaction_type'] ?? null;
                    $related = $m['related_account'] ?? null;
                    $amount = (float) ($m['amount'] ?? 0);
                    $m_remarks = $m['remarks'] ?? null;

                    if ($amount <= 0) continue;

                    // Handle expense types - these don't need member mapping
                    if (strpos($type, 'expense_') === 0) {
                        $expense_category = substr($type, 8); // Remove 'expense_' prefix
                        $expense_label = ucwords(str_replace('_', ' ', $expense_category));
                        
                        // Record in expense_transactions table if it exists
                        if ($this->db->table_exists('expense_transactions')) {
                            $this->db->insert('expense_transactions', [
                                'bank_transaction_id' => $transaction_id,
                                'expense_category' => $expense_category,
                                'amount' => $amount,
                                'description' => $m_remarks ?: $expense_label,
                                'expense_date' => $txn->transaction_date ?? date('Y-m-d'),
                                'created_by' => $admin_id,
                                'created_at' => date('Y-m-d H:i:s')
                            ]);
                        }
                        
                        // Record GL entry for expenses
                        $this->load->model('Ledger_model');
                        $this->Ledger_model->record_expense($expense_category, $amount, $m_remarks ?: $expense_label, $transaction_id);
                        
                        // Mark bank transaction as mapped
                        $updateTxn = [
                            'mapping_status' => 'mapped',
                            'transaction_category' => $type,
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                        if ($this->db->field_exists('mapped_by', 'bank_transactions')) {
                            $updateTxn['mapped_by'] = $admin_id;
                        }
                        if ($this->db->field_exists('mapped_at', 'bank_transactions')) {
                            $updateTxn['mapped_at'] = date('Y-m-d H:i:s');
                        }
                        if ($this->db->field_exists('mapping_remarks', 'bank_transactions')) {
                            $updateTxn['mapping_remarks'] = $expense_label . ': ' . ($m_remarks ?? '');
                        }
                        $this->db->where('id', $transaction_id)->update('bank_transactions', $updateTxn);
                        continue;
                    }

                    // Handle other fees tagged to a member (membership_fee, joining_fee, etc.)
                    if (strpos($type, 'other_fee_') === 0) {
                        $fee_type = substr($type, 10); // Remove 'other_fee_' prefix
                        $fee_label = ucwords(str_replace('_', ' ', $fee_type));

                        if (!empty($paid_for) && is_numeric($paid_for)) {
                            // Record in member_other_transactions
                            $this->load->model('Member_transaction_model');
                            $this->Member_transaction_model->record([
                                'member_id'          => $paid_for,
                                'transaction_type'   => $fee_type,
                                'amount'             => $amount,
                                'transaction_date'   => $txn->transaction_date ?? date('Y-m-d'),
                                'description'        => $fee_label . ($m_remarks ? ' - ' . $m_remarks : ''),
                                'reference_type'     => 'bank_transaction',
                                'reference_id'       => $transaction_id,
                                'payment_mode'       => 'bank_transfer',
                                'bank_transaction_id'=> $transaction_id,
                                'created_by'         => $admin_id
                            ]);

                            // Record GL entry (income)
                            $this->load->model('Ledger_model');
                            $this->Ledger_model->post_transaction(
                                'processing_fee',
                                $transaction_id,
                                $amount,
                                $paid_for,
                                $fee_label . ' from Member #' . $paid_for,
                                $admin_id
                            );

                            // Insert transaction_mapping row
                            $this->db->insert('transaction_mappings', [
                                'bank_transaction_id' => $transaction_id,
                                'member_id'           => $paid_for,
                                'mapping_type'        => 'other',
                                'amount'              => $amount,
                                'narration'           => $fee_label . ($m_remarks ? ' - ' . $m_remarks : ''),
                                'mapped_by'           => $admin_id,
                                'mapped_at'           => date('Y-m-d H:i:s')
                            ]);
                        }
                        continue;
                    }

                    // Convert 'emi' and 'loan' to 'loan_payment' for database enum compatibility
                    // Database enum: ('savings','loan_payment','fine','other')
                    $db_mapping_type = $type;
                    if ($type === 'emi' || $type === 'loan') {
                        $db_mapping_type = 'loan_payment';
                    } elseif (!in_array($type, ['savings', 'loan_payment', 'fine', 'other'])) {
                        // Default to 'other' for unrecognized types
                        $db_mapping_type = 'other';
                    }
                    
                    $insert = [
                        'bank_transaction_id' => $transaction_id,
                        'member_id' => $paid_for,
                        'mapping_type' => $db_mapping_type,
                        'related_id' => null,
                        'amount' => $amount,
                        'narration' => $m_remarks,
                        'mapped_by' => $admin_id,
                        'mapped_at' => date('Y-m-d H:i:s')
                    ];

                    // If there is no member specified or member doesn't exist (internal bank expense etc.),
                    // avoid inserting into `transaction_mappings` which requires a valid member_id.
                    $member_exists = false;
                    if (!empty($paid_for) && is_numeric($paid_for)) {
                        $member_exists = $this->db->where('id', $paid_for)->count_all_results('members') > 0;
                    }
                    if (empty($paid_for) || !$member_exists) {
                        // Mark the bank transaction with mapping remarks and skip creating a mapping row.
                        $updateTxn = [
                            'mapping_status' => 'mapped',
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                        if ($this->db->field_exists('mapped_by', 'bank_transactions')) {
                            $updateTxn['mapped_by'] = $admin_id;
                        }
                        if ($this->db->field_exists('mapped_at', 'bank_transactions')) {
                            $updateTxn['mapped_at'] = date('Y-m-d H:i:s');
                        }
                        if ($this->db->field_exists('mapping_remarks', 'bank_transactions')) {
                            $updateTxn['mapping_remarks'] = $m_remarks ?? 'Internal bank transaction';
                        }
                        $this->db->where('id', $transaction_id)->update('bank_transactions', $updateTxn);
                        // No further processing for memberless mapping types (e.g., expense)
                        continue;
                    }

                    if ($related) {
                        $parts = explode('_', $related);
                        if (count($parts) == 2) {
                            if ($this->db->field_exists('related_type', 'transaction_mappings')) {
                                $insert['related_type'] = $parts[0];
                            }
                            $insert['related_id'] = $parts[1];
                        }
                    }

                    $this->db->insert('transaction_mappings', $insert);

                    // Process the mapped amount immediately
                    switch ($type) {
                        case 'emi':
                            if (!empty($insert['related_id'])) {
                                $this->load->model('Loan_model');
                                // related_id is the installment ID, look up the actual loan_id
                                $installment = $this->db->select('loan_id')
                                    ->where('id', $insert['related_id'])
                                    ->get('loan_installments')
                                    ->row();
                                $actual_loan_id = $installment ? $installment->loan_id : $insert['related_id'];
                                $payment_data = [
                                    'loan_id' => $actual_loan_id,
                                    'installment_id' => $insert['related_id'],
                                    'total_amount' => $amount,
                                    'payment_mode' => 'bank_transfer',
                                    'bank_transaction_id' => $transaction_id,
                                    'payment_type' => 'regular',
                                    'created_by' => $admin_id
                                ];
                                $this->Loan_model->record_payment($payment_data);
                            }
                            break;
                        case 'savings':
                            if (!empty($insert['related_id'])) {
                                $this->load->model('Savings_model');
                                $payment_data = [
                                    'savings_account_id' => $insert['related_id'],
                                    'amount' => $amount,
                                    'transaction_type' => 'deposit',
                                    'reference_number' => $transaction_id,
                                    'created_by' => $admin_id
                                ];
                                $this->Savings_model->record_payment($payment_data);
                            }
                            break;
                        case 'fine':
                            // Apply to earliest pending fine for the member as of now
                            $this->load->model('Fine_model');
                            $fine = $this->db->where('member_id', $paid_for)
                                             ->where('status', 'pending')
                                             ->order_by('fine_date', 'ASC')
                                             ->get('fines')
                                             ->row();
                            if ($fine) {
                                $this->Fine_model->record_payment($fine->id, $amount, 'bank_transfer', $transaction_id, $admin_id);
                            }
                            break;
                        default:
                            // do nothing (other types may be handled manually later)
                            break;
                    }
                }

                // Update bank transaction mapping status
                $new_total_mapped = $already_mapped + $total;
                $new_status = ($new_total_mapped >= ($txn_amount - 0.01)) ? 'mapped' : 'partial';
                $update = [
                    'mapping_status' => $new_status,
                    'mapped_amount' => $new_total_mapped,
                    'unmapped_amount' => max(0, $txn_amount - $new_total_mapped),
                    'updated_by' => $admin_id,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                if ($this->db->field_exists('mapped_by', 'bank_transactions')) {
                    $update['mapped_by'] = $admin_id;
                }
                if ($this->db->field_exists('mapped_at', 'bank_transactions')) {
                    $update['mapped_at'] = date('Y-m-d H:i:s');
                }
                $this->db->where('id', $transaction_id)->update('bank_transactions', $update);

                if ($this->db->trans_status() === FALSE) {
                    $this->db->trans_rollback();
                    $this->ajax_response(false, 'Failed to save mappings');
                    return;
                }

                $this->db->trans_commit();
                $this->log_activity('bank_transaction_mapped', "Mapped bank transaction #{$transaction_id} (split into " . count($mappings) . " rows)");
                $this->ajax_response(true, 'Transaction mapped successfully');
                return;

            } catch (Exception $e) {
                $this->db->trans_rollback();
                $this->ajax_response(false, 'Error: ' . $e->getMessage());
                return;
            }
        }

        // Fallback: single mapping (legacy behaviour)
        $transaction_id = $this->input->post('transaction_id');
        $paying_member_id = $this->input->post('paying_member_id');
        $paid_for_member_id = $this->input->post('paid_for_member_id');
        $transaction_type = $this->input->post('transaction_type');
        $related_account = $this->input->post('related_account');
        $remarks = $this->input->post('remarks');

        $update_data = [
            'paid_by_member_id' => $paying_member_id,
            'paid_for_member_id' => $paid_for_member_id ?: $paying_member_id,
            'transaction_category' => $transaction_type,
            'mapping_status' => 'mapped',
            'updated_by' => $admin_id,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        if ($this->db->field_exists('mapped_by', 'bank_transactions')) {
            $update_data['mapped_by'] = $admin_id;
        }
        if ($this->db->field_exists('mapped_at', 'bank_transactions')) {
            $update_data['mapped_at'] = date('Y-m-d H:i:s');
        }
        if ($this->db->field_exists('mapping_remarks', 'bank_transactions')) {
            $update_data['mapping_remarks'] = $remarks;
        }

        // Handle related account
        if ($related_account) {
            $parts = explode('_', $related_account);
            if (count($parts) == 2) {
                $update_data['related_type'] = $parts[0];
                $update_data['related_id'] = $parts[1];
            }
        }

        $result = $this->db->where('id', $transaction_id)
                           ->update('bank_transactions', $update_data);

        if ($result) {
            // Process the transaction based on type via model (so it can be unit-tested)
            $this->load->model('Bank_model');
            $this->Bank_model->map_and_process_transaction($transaction_id, $update_data, $admin_id);

            $this->log_activity('bank_transaction_mapped', "Mapped bank transaction #{$transaction_id} - Type: {$transaction_type}");
            $this->ajax_response(true, 'Transaction mapped successfully');
        } else {
            $this->ajax_response(false, 'Failed to save mapping');
        }
    }
    
    /**
     * Process Mapped Transaction
     */
    private function process_mapped_transaction($transaction_id, $type, $data) {
        $txn = $this->db->where('id', $transaction_id)->get('bank_transactions')->row();
        if (!$txn) return;
        
        $admin_id = $this->session->userdata('admin_id');
        
        switch ($type) {
            case 'emi':
                // Create loan payment record
                if (isset($data['related_id']) && $data['related_type'] == 'loan') {
                    $this->load->model('Loan_model');
                    // Process EMI payment with tracking
                    $amount = $txn->credit_amount ?: abs($txn->debit_amount);
                    $this->Loan_model->record_payment(
                        $data['related_id'],
                        $amount,
                        'bank_transfer',
                        $transaction_id,
                        $admin_id
                    );
                }
                break;
                
            case 'savings':
                // Create savings deposit record
                if (isset($data['related_id']) && $data['related_type'] == 'savings') {
                    $this->load->model('Savings_model');
                    $amount = $txn->credit_amount ?: abs($txn->debit_amount);
                    $this->Savings_model->record_deposit(
                        $data['related_id'],
                        $amount,
                        'bank_transfer',
                        $transaction_id,
                        $admin_id
                    );
                }
                break;
                
            case 'fine':
                // Record fine payment
                $this->load->model('Fine_model');
                // Find pending fine for member
                $fine = $this->db->where('member_id', $data['paid_for_member_id'])
                                 ->where('status', 'pending')
                                 ->order_by('fine_date', 'ASC')
                                 ->get('fines')
                                 ->row();
                if ($fine) {
                    $amount = $txn->credit_amount ?: abs($txn->debit_amount);
                    $this->Fine_model->record_payment($fine->id, $amount, 'bank_transfer', $transaction_id, $admin_id);
                }
                break;
        }
    }
    
    /**
     * Bank Accounts Management
     */
    public function accounts() {
        $this->data['page_title'] = 'Bank Accounts';
        $this->data['breadcrumb'] = [['label' => 'Bank Accounts']];
        
        // Get all bank accounts
        $this->data['bank_accounts'] = $this->Bank_model->get_accounts(false);
        
        $this->load->view('admin/layouts/header', $this->data);
        $this->load->view('admin/layouts/sidebar', $this->data);
        $this->load->view('admin/bank/accounts', $this->data);
        $this->load->view('admin/layouts/footer', $this->data);
    }
    
    /**
     * Create Bank Account
     */
    public function create() {
        $this->data['page_title'] = 'Create Bank Account';
        $this->data['breadcrumb'] = [
            ['label' => 'Bank Accounts', 'url' => 'admin/bank/accounts'],
            ['label' => 'Create']
        ];
        
        if ($this->input->post()) {
            $this->form_validation->set_rules('account_name', 'Account Name', 'required|trim');
            $this->form_validation->set_rules('bank_name', 'Bank Name', 'required|trim');
            $this->form_validation->set_rules('account_number', 'Account Number', 'required|trim|is_unique[bank_accounts.account_number]');
            $this->form_validation->set_rules('account_type', 'Account Type', 'required|in_list[current,savings,cash]');
            $this->form_validation->set_rules('opening_balance', 'Opening Balance', 'numeric');
            
            if ($this->form_validation->run() === TRUE) {
                $data = [
                    'account_name' => $this->input->post('account_name'),
                    'bank_name' => $this->input->post('bank_name'),
                    'branch_name' => $this->input->post('branch_name'),
                    'account_number' => $this->input->post('account_number'),
                    'ifsc_code' => $this->input->post('ifsc_code'),
                    'account_type' => $this->input->post('account_type'),
                    'opening_balance' => $this->input->post('opening_balance') ?: 0,
                    'current_balance' => $this->input->post('opening_balance') ?: 0,
                    'is_active' => $this->input->post('is_active') ? 1 : 0,
                    'is_primary' => $this->input->post('is_primary') ? 1 : 0
                ];
                
                $account_id = $this->Bank_model->create_account($data);
                
                if ($account_id) {
                    $this->session->set_flashdata('success', 'Bank account created successfully!');
                    redirect('admin/bank/accounts');
                } else {
                    $this->session->set_flashdata('error', 'Bank account could not be created. Please verify the account details and check for duplicates.');
                }
            }
        }
        
        $this->load->view('admin/layouts/header', $this->data);
        $this->load->view('admin/layouts/sidebar', $this->data);
        $this->load->view('admin/bank/create_account', $this->data);
        $this->load->view('admin/layouts/footer', $this->data);
    }
    
    /**
     * Edit Bank Account
     */
    public function edit($id) {
        $account = $this->Bank_model->get_by_id($id);
        
        if (!$account) {
            show_404();
        }
        
        $this->data['page_title'] = 'Edit Bank Account';
        $this->data['breadcrumb'] = [
            ['label' => 'Bank Accounts', 'url' => 'admin/bank/accounts'],
            ['label' => 'Edit']
        ];
        $this->data['account'] = $account;
        
        if ($this->input->post()) {
            $this->form_validation->set_rules('account_name', 'Account Name', 'required|trim');
            $this->form_validation->set_rules('bank_name', 'Bank Name', 'required|trim');
            $this->form_validation->set_rules('account_number', 'Account Number', 'required|trim');
            $this->form_validation->set_rules('account_type', 'Account Type', 'required|in_list[current,savings,cash]');
            $this->form_validation->set_rules('opening_balance', 'Opening Balance', 'numeric');
            
            // Check if account number is unique (excluding current account)
            if ($this->input->post('account_number') != $account->account_number) {
                $this->form_validation->set_rules('account_number', 'Account Number', 'is_unique[bank_accounts.account_number]');
            }
            
            if ($this->form_validation->run() === TRUE) {
                $data = [
                    'account_name' => $this->input->post('account_name'),
                    'bank_name' => $this->input->post('bank_name'),
                    'branch_name' => $this->input->post('branch_name'),
                    'account_number' => $this->input->post('account_number'),
                    'ifsc_code' => $this->input->post('ifsc_code'),
                    'account_type' => $this->input->post('account_type'),
                    'opening_balance' => $this->input->post('opening_balance') ?: 0,
                    'is_active' => $this->input->post('is_active') ? 1 : 0,
                    'is_primary' => $this->input->post('is_primary') ? 1 : 0
                ];
                
                if ($this->Bank_model->update($id, $data)) {
                    $this->session->set_flashdata('success', 'Bank account updated successfully!');
                    redirect('admin/bank/accounts');
                } else {
                    $this->session->set_flashdata('error', 'Bank account details could not be saved. Please verify your changes and try again.');
                }
            }
        }
        
        $this->load->view('admin/layouts/header', $this->data);
        $this->load->view('admin/layouts/sidebar', $this->data);
        $this->load->view('admin/bank/edit_account', $this->data);
        $this->load->view('admin/layouts/footer', $this->data);
    }
    
    /**
     * Toggle Account Status
     */
    public function toggle($id) {
        $account = $this->Bank_model->get_by_id($id);
        
        if (!$account) {
            $this->ajax_response(false, 'Account not found.');
        }
        
        $new_status = $account->is_active ? 0 : 1;
        
        $result = $this->Bank_model->update($id, ['is_active' => $new_status]);
        
        if ($result) {
            $message = $new_status ? 'Account activated successfully!' : 'Account deactivated successfully!';
            $this->ajax_response(true, $message);
        } else {
            $this->ajax_response(false, 'Failed to update account status.');
        }
    }
    
    /**
     * AJAX: Search Members
     */
    public function search_members() {
        // Allow both AJAX and regular GET requests (for Select2)
        header('Content-Type: application/json');
        
        $query = $this->input->get('q');
        $limit = $this->input->get('limit') ?: 10;
        
        if (empty($query)) {
            $this->ajax_response(false, 'Search query required');
            return;
        }
        
        try {
            $this->load->model('Member_model');
            $members = $this->Member_model->search_members($query, null, $limit);
            
            $results = [];
            foreach ($members as $member) {
                $full_name = trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? ''));
                $phone = (property_exists($member, 'phone') && !empty($member->phone)) ? $member->phone : '';
                $display = $member->member_code;
                if ($full_name !== '') $display .= ' - ' . $full_name;
                if ($phone !== '') $display .= ' (' . $phone . ')';

                $results[] = [
                    'id' => $member->id,
                    'text' => $display,
                    'member_code' => $member->member_code,
                    'full_name' => $full_name,
                    'phone' => $phone
                ];
            }
            
            $this->ajax_response(true, 'Members found', $results);
        } catch (Exception $e) {
            log_message('error', 'Member search failed: ' . $e->getMessage());
            $this->ajax_response(false, 'Search failed: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX: Get Member Accounts
     */
    public function get_member_accounts() {
        // Allow both AJAX and regular GET requests
        header('Content-Type: application/json');
        
        $member_id = $this->input->get('member_id');
        
        if (empty($member_id)) {
            $this->ajax_response(false, 'Member ID required');
            return;
        }
        
        $this->load->model('Savings_model');
        $this->load->model('Loan_model');
        
        $savings_accounts = $this->Savings_model->get_member_accounts($member_id);
        $loans = $this->Loan_model->get_member_loans($member_id);
        
        $accounts = [];
        
        // Add savings accounts
        foreach ($savings_accounts as $acc) {
            $accounts[] = [
                'id' => 'savings_' . $acc->id,
                'text' => 'Savings: ' . $acc->account_number,
                'type' => 'savings',
                'account_id' => $acc->id,
                'account_number' => $acc->account_number,
                'balance' => $acc->current_balance
            ];
        }
        
        // Add loans
        foreach ($loans as $loan) {
            $accounts[] = [
                'id' => 'loan_' . $loan->id,
                'text' => 'Loan: ' . $loan->loan_number . ' (' . format_amount($loan->pending_amount) . ')',
                'type' => 'loan',
                'account_id' => $loan->id,
                'account_number' => $loan->loan_number,
                'balance' => $loan->pending_amount
            ];
        }
        
        $this->ajax_response(true, 'Accounts found', $accounts);
    }

    /**
     * AJAX: Get Member Details (Loans with installments, Savings, Fines)
     */
    public function get_member_details() {
        // Allow both AJAX and regular GET requests
        header('Content-Type: application/json');
        
        $member_id = $this->input->get('member_id');
        
        if (empty($member_id)) {
            $this->ajax_response(false, 'Member ID required');
            return;
        }
        
        $this->load->model('Savings_model');
        $this->load->model('Loan_model');
        $this->load->model('Fine_model');
        
        // Get savings accounts
        $savings_accounts = $this->Savings_model->get_member_accounts($member_id);
        $savings = [];
        foreach ($savings_accounts as $acc) {
            $savings[] = [
                'id' => $acc->id,
                'account_number' => $acc->account_number,
                'account_type' => $acc->account_type ?? 'savings',
                'current_balance' => $acc->current_balance ?? 0
            ];
        }
        
        // Get active loans with pending installments
        $member_loans = $this->Loan_model->get_member_loans($member_id);
        $loans = [];
        foreach ($member_loans as $loan) {
            // Get pending installments for this loan
            $installments = $this->db->select('id, installment_number, due_date, emi_amount, principal_amount, interest_amount, 
                                               (emi_amount - COALESCE(total_paid, 0)) as pending_amount, status')
                                     ->where('loan_id', $loan->id)
                                     ->where('status !=', 'paid')
                                     ->order_by('installment_number', 'ASC')
                                     ->limit(6) // Show next 6 pending EMIs
                                     ->get('loan_installments')
                                     ->result();
            
            $installment_list = [];
            foreach ($installments as $inst) {
                $installment_list[] = [
                    'id' => $inst->id,
                    'installment_number' => $inst->installment_number,
                    'due_date' => format_date($inst->due_date),
                    'emi_amount' => $inst->emi_amount,
                    'pending_amount' => $inst->pending_amount
                ];
            }
            
            $loans[] = [
                'id' => $loan->id,
                'loan_number' => $loan->loan_number,
                'loan_type' => $loan->loan_type ?? 'loan',
                'principal_amount' => $loan->principal_amount ?? 0,
                'pending_amount' => $loan->pending_amount ?? 0,
                'installments' => $installment_list
            ];
        }
        
        // Get pending fines
        $member_fines = $this->Fine_model->get_member_fines($member_id, true); // true = pending only
        $fines = [];
        foreach ($member_fines as $fine) {
            $fines[] = [
                'id' => $fine->id,
                'fine_type' => $fine->fine_type ?? 'Fine',
                'fine_amount' => $fine->fine_amount ?? 0,
                'paid_amount' => $fine->paid_amount ?? 0,
                'pending_amount' => ($fine->fine_amount ?? 0) - ($fine->paid_amount ?? 0),
                'fine_date' => isset($fine->fine_date) ? format_date($fine->fine_date) : ''
            ];
        }
        
        $this->ajax_response(true, 'Member details loaded', [
            'savings' => $savings,
            'loans' => $loans,
            'fines' => $fines
        ]);
    }
    
    /**
     * AJAX: Calculate Fine Due
     */
    public function calculate_fine_due() {
        // Allow both AJAX and regular POST requests
        header('Content-Type: application/json');
        
        $member_id = $this->input->post('member_id');
        $amount = $this->input->post('amount');
        
        if (empty($member_id) || empty($amount)) {
            $this->ajax_response(false, 'Member ID and amount required');
            return;
        }
        
        $this->load->model('Fine_model');
        $fines = $this->Fine_model->get_member_fines($member_id, true);
        
        $total_fine = 0;
        foreach ($fines as $fine) {
            $total_fine += $fine->pending_amount;
        }
        
        $this->ajax_response(true, 'Fine calculated', [
            'total_fine' => $total_fine,
            'can_pay' => $amount >= $total_fine
        ]);
    }
    
    /**
     * AJAX Response Helper
     */
    private function ajax_response($success, $message, $data = null) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }

    // ============================================================
    // ENHANCED RECONCILIATION & MAPPING ENDPOINTS
    // ============================================================

    /**
     * AJAX: Get Mapping Details for a Transaction
     * Returns all mappings with member/account info for the detail panel
     */
    public function get_mapping_details() {
        header('Content-Type: application/json');
        $transaction_id = $this->input->get('transaction_id');

        if (empty($transaction_id)) {
            $this->ajax_response(false, 'Transaction ID required');
            return;
        }

        $txn = $this->db->where('id', $transaction_id)->get('bank_transactions')->row();
        if (!$txn) {
            $this->ajax_response(false, 'Transaction not found');
            return;
        }

        $mappings = $this->Bank_model->get_transaction_mappings($transaction_id);

        // Enrich mapping details
        $enriched = [];
        foreach ($mappings as $m) {
            $detail = [
                'id' => $m->id,
                'member_code' => $m->member_code,
                'member_name' => $m->member_name ?: ($m->first_name . ' ' . $m->last_name),
                'mapping_type' => $m->mapping_type,
                'related_type' => $m->related_type ?? null,
                'related_id' => $m->related_id,
                'amount' => $m->amount,
                'narration' => $m->narration,
                'mapped_at' => $m->mapped_at,
                'for_month' => $m->for_month,
                'is_reversed' => $m->is_reversed,
                'account_info' => null
            ];

            // Get related account info
            if ($m->mapping_type === 'loan_payment' || $m->mapping_type === 'emi') {
                $related_type = $m->related_type ?? '';
                if ($related_type === 'loan' && $m->related_id) {
                    $loan = $this->db->select('loan_number, principal_amount, outstanding_principal')
                                     ->where('id', $m->related_id)->get('loans')->row();
                    if ($loan) {
                        $detail['account_info'] = 'Loan: ' . $loan->loan_number . ' (Outstanding: ' . format_amount($loan->outstanding_principal) . ')';
                    }
                }
                // Check if related_id points to a loan_installment
                $inst = $this->db->select('li.installment_number, li.emi_amount, li.due_date, l.loan_number')
                                 ->from('loan_installments li')
                                 ->join('loans l', 'l.id = li.loan_id')
                                 ->where('li.id', $m->related_id)
                                 ->get()->row();
                if ($inst) {
                    $detail['account_info'] = 'Loan: ' . $inst->loan_number . ' EMI #' . $inst->installment_number . ' (Due: ' . format_date($inst->due_date) . ')';
                }
            } elseif ($m->mapping_type === 'savings') {
                if ($m->related_id) {
                    $related_type = $m->related_type ?? '';
                    $acc = null;
                    if ($related_type === 'savings') {
                        $acc = $this->db->select('account_number, current_balance')
                                        ->where('id', $m->related_id)->get('savings_accounts')->row();
                    }
                    if (!$acc) {
                        $acc = $this->db->select('sa.account_number, sa.current_balance')
                                        ->from('savings_transactions st')
                                        ->join('savings_accounts sa', 'sa.id = st.savings_account_id')
                                        ->where('st.id', $m->related_id)->get()->row();
                    }
                    if ($acc) {
                        $detail['account_info'] = 'Savings: ' . $acc->account_number . ' (Balance: ' . format_amount($acc->current_balance) . ')';
                    }
                }
            } elseif ($m->mapping_type === 'fine') {
                if ($m->related_id) {
                    $fine = $this->db->select('fine_type, fine_amount, paid_amount')
                                     ->where('id', $m->related_id)->get('fines')->row();
                    if ($fine) {
                        $detail['account_info'] = 'Fine: ' . $fine->fine_type . ' (' . format_amount($fine->fine_amount) . ')';
                    }
                }
            } elseif ($m->mapping_type === 'disbursement') {
                if ($m->related_id && $this->db->table_exists('disbursement_tracking')) {
                    $dt = $this->db->select('dt.*, l.loan_number')
                                   ->from('disbursement_tracking dt')
                                   ->join('loans l', 'l.id = dt.loan_id')
                                   ->where('dt.id', $m->related_id)->get()->row();
                    if ($dt) {
                        $detail['account_info'] = 'Disbursement: ' . $dt->loan_number . ' (' . format_amount($dt->net_amount) . ')';
                    }
                }
            } else {
                // Internal/other types
                if ($m->related_id && $this->db->table_exists('internal_transactions')) {
                    $it = $this->db->where('id', $m->related_id)->get('internal_transactions')->row();
                    if ($it) {
                        $detail['account_info'] = ucwords(str_replace('_', ' ', $it->transaction_type)) . ': ' . $it->transaction_code;
                    }
                }
            }

            $enriched[] = $detail;
        }

        $this->ajax_response(true, 'Mapping details loaded', [
            'transaction' => [
                'id' => $txn->id,
                'date' => $txn->transaction_date,
                'amount' => $txn->amount,
                'type' => $txn->transaction_type,
                'description' => $txn->description,
                'mapping_status' => $txn->mapping_status,
                'mapped_amount' => $txn->mapped_amount ?? 0,
                'unmapped_amount' => $txn->unmapped_amount ?? ($txn->amount - ($txn->mapped_amount ?? 0)),
                'mapping_remarks' => $txn->mapping_remarks ?? ''
            ],
            'mappings' => $enriched
        ]);
    }

    /**
     * AJAX: Reverse/Unmap a Transaction Mapping
     */
    public function reverse_mapping() {
        if (!$this->input->post()) {
            $this->ajax_response(false, 'Invalid request');
            return;
        }

        $mapping_id = $this->input->post('mapping_id');
        $reason = $this->input->post('reason') ?: 'Manual reversal';
        $admin_id = $this->session->userdata('admin_id');

        if (empty($mapping_id)) {
            $this->ajax_response(false, 'Mapping ID required');
            return;
        }

        try {
            $result = $this->Bank_model->reverse_mapping($mapping_id, $reason, $admin_id);
            if ($result) {
                $this->log_activity('bank_mapping_reversed', "Reversed mapping #{$mapping_id}: {$reason}");
                $this->ajax_response(true, 'Mapping reversed successfully. Financial effects have been rolled back.');
            } else {
                $this->ajax_response(false, 'Failed to reverse mapping');
            }
        } catch (Exception $e) {
            $this->ajax_response(false, 'Error: ' . $e->getMessage());
        }
    }

    /**
     * AJAX: Map Disbursement (Debit transaction -> Loan disbursement)
     */
    public function map_disbursement() {
        if (!$this->input->post()) {
            $this->ajax_response(false, 'Invalid request');
            return;
        }

        $transaction_id = $this->input->post('transaction_id');
        $loan_id = $this->input->post('loan_id');
        $amount = floatval($this->input->post('amount'));
        $remarks = $this->input->post('remarks') ?: '';
        $admin_id = $this->session->userdata('admin_id');

        if (empty($transaction_id) || empty($loan_id) || $amount <= 0) {
            $this->ajax_response(false, 'Transaction ID, Loan ID and amount are required');
            return;
        }

        try {
            $result = $this->Bank_model->map_disbursement($transaction_id, $loan_id, $amount, $admin_id, $remarks);
            if ($result) {
                $this->log_activity('bank_disbursement_mapped', "Mapped disbursement: Txn #{$transaction_id} -> Loan #{$loan_id} ({$amount})");
                $this->ajax_response(true, 'Disbursement mapped successfully');
            } else {
                $this->ajax_response(false, 'Failed to map disbursement');
            }
        } catch (Exception $e) {
            $this->ajax_response(false, 'Error: ' . $e->getMessage());
        }
    }

    /**
     * AJAX: Map Internal Transaction
     */
    public function map_internal() {
        if (!$this->input->post()) {
            $this->ajax_response(false, 'Invalid request');
            return;
        }

        $transaction_id = $this->input->post('transaction_id');
        $type = $this->input->post('type');
        $amount = floatval($this->input->post('amount'));
        $description = $this->input->post('description') ?: '';
        $admin_id = $this->session->userdata('admin_id');

        $valid_types = ['internal_transfer', 'bank_charge', 'interest_earned', 'dividend_paid', 
                        'cash_deposit', 'cash_withdrawal', 'contra_entry', 'adjustment', 'other'];

        if (empty($transaction_id) || empty($type) || $amount <= 0) {
            $this->ajax_response(false, 'Transaction ID, type and amount are required');
            return;
        }
        if (!in_array($type, $valid_types)) {
            $this->ajax_response(false, 'Invalid internal transaction type');
            return;
        }

        try {
            $result = $this->Bank_model->map_internal_transaction($transaction_id, $type, $amount, $admin_id, [
                'description' => $description
            ]);
            if ($result !== false) {
                $this->log_activity('bank_internal_mapped', "Mapped internal txn: #{$transaction_id} as {$type} ({$amount})");
                $this->ajax_response(true, 'Internal transaction mapped successfully');
            } else {
                $this->ajax_response(false, 'Failed to map internal transaction');
            }
        } catch (Exception $e) {
            $this->ajax_response(false, 'Error: ' . $e->getMessage());
        }
    }

    /**
     * AJAX: Get Disbursable Loans for mapping dropdown
     */
    public function get_disbursable_loans() {
        header('Content-Type: application/json');
        $member_id = $this->input->get('member_id');
        $loans = $this->Bank_model->get_disbursable_loans($member_id ?: null);
        $this->ajax_response(true, 'Loans loaded', $loans);
    }

    /**
     * AJAX: Ignore/Skip a bank transaction
     */
    public function ignore_transaction() {
        if (!$this->input->post()) {
            $this->ajax_response(false, 'Invalid request');
            return;
        }

        $transaction_id = $this->input->post('transaction_id');
        $reason = $this->input->post('reason') ?: 'Manually ignored';
        $admin_id = $this->session->userdata('admin_id');

        if (empty($transaction_id)) {
            $this->ajax_response(false, 'Transaction ID required');
            return;
        }

        $result = $this->Bank_model->skip_transaction($transaction_id, $reason, $admin_id);
        if ($result) {
            // Also update mapped_by/mapped_at fields
            $update = ['updated_at' => date('Y-m-d H:i:s')];
            if ($this->db->field_exists('mapped_by', 'bank_transactions')) {
                $update['mapped_by'] = $admin_id;
            }
            if ($this->db->field_exists('mapped_at', 'bank_transactions')) {
                $update['mapped_at'] = date('Y-m-d H:i:s');
            }
            if ($this->db->field_exists('mapping_remarks', 'bank_transactions')) {
                $update['mapping_remarks'] = 'Ignored: ' . $reason;
            }
            $this->db->where('id', $transaction_id)->update('bank_transactions', $update);

            $this->log_activity('bank_transaction_ignored', "Ignored bank transaction #{$transaction_id}: {$reason}");
            $this->ajax_response(true, 'Transaction ignored successfully');
        } else {
            $this->ajax_response(false, 'Failed to ignore transaction');
        }
    }

    /**
     * AJAX: Restore ignored transaction back to unmapped
     */
    public function restore_transaction() {
        if (!$this->input->post()) {
            $this->ajax_response(false, 'Invalid request');
            return;
        }

        $transaction_id = $this->input->post('transaction_id');

        if (empty($transaction_id)) {
            $this->ajax_response(false, 'Transaction ID required');
            return;
        }

        $result = $this->db->where('id', $transaction_id)
                           ->update('bank_transactions', [
                               'mapping_status' => 'unmapped',
                               'remarks' => null,
                               'mapping_remarks' => null,
                               'updated_at' => date('Y-m-d H:i:s')
                           ]);

        if ($result) {
            $this->log_activity('bank_transaction_restored', "Restored bank transaction #{$transaction_id} to unmapped");
            $this->ajax_response(true, 'Transaction restored to unmapped');
        } else {
            $this->ajax_response(false, 'Failed to restore transaction');
        }
    }

    /**
     * Reconciliation Report Page
     */
    public function reconciliation() {
        $this->data['page_title'] = 'Bank Reconciliation Report';
        $this->data['breadcrumb'] = [
            ['label' => 'Bank', 'link' => site_url('admin/bank/import')],
            ['label' => 'Reconciliation']
        ];

        // Load financial years
        $this->load->model('Financial_year_model');
        $financial_years = $this->Financial_year_model->get_all_years();
        $this->data['financial_years'] = $financial_years;

        $active_fy = $this->Financial_year_model->get_active();

        $fy_id = $this->input->get('fy_id');
        $selected_fy = null;
        if ($fy_id) {
            foreach ($financial_years as $fy) {
                if ($fy->id == $fy_id) { $selected_fy = $fy; break; }
            }
        }
        if (!$selected_fy && $active_fy) $selected_fy = $active_fy;
        if (!$selected_fy && !empty($financial_years)) $selected_fy = $financial_years[0];
        $this->data['selected_fy'] = $selected_fy;

        $this->data['bank_accounts'] = $this->Bank_model->get_accounts();

        $filters = [
            'bank_id' => $this->input->get('bank_id'),
            'from_date' => $selected_fy ? $selected_fy->start_date : date('Y-04-01'),
            'to_date' => $selected_fy ? $selected_fy->end_date : date('Y-03-31', strtotime('+1 year'))
        ];

        $this->data['filters'] = $filters;
        $this->data['stats'] = $this->Bank_model->get_reconciliation_stats($filters);

        // Get disbursement records for the period
        if ($this->db->table_exists('disbursement_tracking')) {
            $this->data['disbursements'] = $this->Bank_model->get_disbursement_records($filters);
        } else {
            $this->data['disbursements'] = [];
        }

        // Get internal transactions
        $this->data['internal_transactions'] = $this->Bank_model->get_internal_transactions($filters);

        $this->load->view('admin/layouts/header', $this->data);
        $this->load->view('admin/layouts/sidebar', $this->data);
        $this->load->view('admin/bank/reconciliation', $this->data);
        $this->load->view('admin/layouts/footer', $this->data);
    }

    // ============================================================
    // CA BANK STATEMENT REPORT (Passbook vs System Mapping)
    // ============================================================

    /**
     * CA Report - Bank Statement with Mapping Details
     * Industry-standard report showing all bank transactions with their
     * system-level mapping (loan, savings, fine, disbursement, internal)
     */
    public function ca_report() {
        $this->data['page_title'] = 'CA Bank Statement Report';
        $this->data['breadcrumb'] = [
            ['label' => 'Bank', 'link' => site_url('admin/bank/import')],
            ['label' => 'CA Report']
        ];

        // Load financial years
        $this->load->model('Financial_year_model');
        $financial_years = $this->Financial_year_model->get_all_years();
        $this->data['financial_years'] = $financial_years;

        $active_fy = $this->Financial_year_model->get_active();
        $fy_id = $this->input->get('fy_id');
        $selected_fy = null;
        if ($fy_id) {
            foreach ($financial_years as $fy) {
                if ($fy->id == $fy_id) { $selected_fy = $fy; break; }
            }
        }
        if (!$selected_fy && $active_fy) $selected_fy = $active_fy;
        if (!$selected_fy && !empty($financial_years)) $selected_fy = $financial_years[0];
        $this->data['selected_fy'] = $selected_fy;

        $this->data['bank_accounts'] = $this->Bank_model->get_accounts();

        $filters = [
            'bank_id' => $this->input->get('bank_id'),
            'from_date' => $this->input->get('from_date') ?: ($selected_fy ? $selected_fy->start_date : date('Y-04-01')),
            'to_date' => $this->input->get('to_date') ?: ($selected_fy ? $selected_fy->end_date : date('Y-03-31', strtotime('+1 year'))),
            'mapping_status' => $this->input->get('mapping_status')
        ];
        $this->data['filters'] = $filters;

        // Fetch all bank transactions for the period
        $transactions = $this->_get_ca_report_data($filters);
        $this->data['transactions'] = $transactions;

        // Summary stats
        $summary = $this->_compute_ca_summary($transactions);
        $this->data['summary'] = $summary;

        // Organization info
        $this->data['org_name'] = get_setting('organization_name', 'WinDeep Finance');

        $this->load->view('admin/layouts/header', $this->data);
        $this->load->view('admin/layouts/sidebar', $this->data);
        $this->load->view('admin/bank/ca_report', $this->data);
        $this->load->view('admin/layouts/footer', $this->data);
    }

    /**
     * Export CA Report to Excel
     */
    public function ca_report_export() {
        require_once FCPATH . 'vendor/autoload.php';

        $filters = [
            'bank_id' => $this->input->get('bank_id'),
            'from_date' => $this->input->get('from_date') ?: date('Y-04-01'),
            'to_date' => $this->input->get('to_date') ?: date('Y-03-31', strtotime('+1 year')),
            'mapping_status' => $this->input->get('mapping_status')
        ];

        $transactions = $this->_get_ca_report_data($filters);
        $summary = $this->_compute_ca_summary($transactions);
        $org_name = get_setting('organization_name', 'WinDeep Finance');

        // Get selected bank name
        $bank_label = 'All Bank Accounts';
        if (!empty($filters['bank_id'])) {
            $bank = $this->db->where('id', $filters['bank_id'])->get('bank_accounts')->row();
            if ($bank) $bank_label = $bank->bank_name . ' - ' . $bank->account_number;
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator($org_name)
            ->setTitle('CA Bank Statement Report')
            ->setSubject('Bank Statement Mapping Report for CA')
            ->setDescription('System-generated bank reconciliation report');

        // ── Sheet 1: Passbook Statement with Mapping ──
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bank Statement');

        // Title block
        $sheet->setCellValue('A1', $org_name);
        $sheet->mergeCells('A1:L1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

        $sheet->setCellValue('A2', 'Bank Statement Mapping Report (For CA Review)');
        $sheet->mergeCells('A2:L2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');

        $sheet->setCellValue('A3', 'Bank: ' . $bank_label . '  |  Period: ' . $filters['from_date'] . ' to ' . $filters['to_date']);
        $sheet->mergeCells('A3:L3');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A3')->getFont()->setItalic(true);

        $sheet->setCellValue('A4', 'Generated: ' . date('d-M-Y H:i'));
        $sheet->mergeCells('A4:L4');
        $sheet->getStyle('A4')->getAlignment()->setHorizontal('center');

        // Headers (row 6)
        $headers = [
            'Sr.No', 'Date', 'Value Date', 'Description', 'Reference/UTR',
            'Debit (₹)', 'Credit (₹)', 'Balance (₹)',
            'Mapping Status', 'Category', 'Mapped To', 'Member/Narration'
        ];
        $headerRow = 6;
        foreach ($headers as $col => $h) {
            $cell = $sheet->getCellByColumnAndRow($col + 1, $headerRow);
            $cell->setValue($h);
        }

        // Style header row
        $headerRange = 'A' . $headerRow . ':L' . $headerRow;
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet->getStyle($headerRange)->getFont()->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($headerRange)->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Data rows
        $row = $headerRow + 1;
        $sr = 1;
        foreach ($transactions as $txn) {
            $sheet->setCellValueByColumnAndRow(1, $row, $sr);
            $sheet->setCellValueByColumnAndRow(2, $row, $txn->transaction_date);
            $sheet->setCellValueByColumnAndRow(3, $row, $txn->value_date ?? $txn->transaction_date);
            $sheet->setCellValueByColumnAndRow(4, $row, $txn->description);
            $sheet->setCellValueByColumnAndRow(5, $row, $txn->reference_number ?? $txn->utr_number ?? $txn->cheque_number ?? '');
            $sheet->setCellValueByColumnAndRow(6, $row, $txn->debit_amount > 0 ? floatval($txn->debit_amount) : '');
            $sheet->setCellValueByColumnAndRow(7, $row, $txn->credit_amount > 0 ? floatval($txn->credit_amount) : '');
            $sheet->setCellValueByColumnAndRow(8, $row, $txn->running_balance ?? $txn->balance_after ?? '');
            $sheet->setCellValueByColumnAndRow(9, $row, ucfirst($txn->mapping_status));
            $sheet->setCellValueByColumnAndRow(10, $row, ucfirst(str_replace('_', ' ', $txn->transaction_category ?? '')));
            $sheet->setCellValueByColumnAndRow(11, $row, $txn->mapped_to_display ?? '');
            $sheet->setCellValueByColumnAndRow(12, $row, $txn->mapping_narration ?? '');

            // Number format for amounts
            foreach ([6, 7, 8] as $c) {
                $sheet->getStyleByColumnAndRow($c, $row)->getNumberFormat()
                    ->setFormatCode('#,##0.00');
            }

            // Color-code mapping status
            $statusColors = ['mapped' => 'C6EFCE', 'unmapped' => 'FFC7CE', 'partial' => 'FFEB9C', 'ignored' => 'D9D9D9'];
            $statusColor = $statusColors[$txn->mapping_status] ?? 'FFFFFF';
            $sheet->getStyleByColumnAndRow(9, $row)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB($statusColor);

            // Borders
            $sheet->getStyle('A' . $row . ':L' . $row)->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            $row++;
            $sr++;
        }

        // Totals row
        $totalRow = $row;
        $sheet->setCellValueByColumnAndRow(4, $totalRow, 'TOTAL');
        $sheet->setCellValueByColumnAndRow(6, $totalRow, $summary['total_debit']);
        $sheet->setCellValueByColumnAndRow(7, $totalRow, $summary['total_credit']);
        $sheet->getStyle('A' . $totalRow . ':L' . $totalRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $totalRow . ':L' . $totalRow)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('D9E2F3');
        foreach ([6, 7] as $c) {
            $sheet->getStyleByColumnAndRow($c, $totalRow)->getNumberFormat()
                ->setFormatCode('#,##0.00');
        }

        // Auto-size columns
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ── Sheet 2: Mapping Summary (Category-wise) ──
        $sheet2 = $spreadsheet->createSheet(1);
        $sheet2->setTitle('Category Summary');

        $sheet2->setCellValue('A1', $org_name . ' - Mapping Category Summary');
        $sheet2->mergeCells('A1:D1');
        $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $sheet2->setCellValue('A2', 'Period: ' . $filters['from_date'] . ' to ' . $filters['to_date']);
        $sheet2->mergeCells('A2:D2');

        $catHeaders = ['Category', 'Count', 'Total Debit (₹)', 'Total Credit (₹)'];
        foreach ($catHeaders as $c => $h) {
            $sheet2->setCellValueByColumnAndRow($c + 1, 4, $h);
        }
        $sheet2->getStyle('A4:D4')->getFont()->setBold(true);
        $sheet2->getStyle('A4:D4')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet2->getStyle('A4:D4')->getFont()->getColor()->setRGB('FFFFFF');

        $catRow = 5;
        foreach ($summary['by_category'] as $cat => $data) {
            $sheet2->setCellValueByColumnAndRow(1, $catRow, ucfirst(str_replace('_', ' ', $cat ?: 'Uncategorized')));
            $sheet2->setCellValueByColumnAndRow(2, $catRow, $data['count']);
            $sheet2->setCellValueByColumnAndRow(3, $catRow, $data['debit']);
            $sheet2->setCellValueByColumnAndRow(4, $catRow, $data['credit']);
            foreach ([3, 4] as $c) {
                $sheet2->getStyleByColumnAndRow($c, $catRow)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $sheet2->getStyle('A' . $catRow . ':D' . $catRow)->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $catRow++;
        }

        foreach (range('A', 'D') as $col) {
            $sheet2->getColumnDimension($col)->setAutoSize(true);
        }

        // ── Sheet 3: Status Summary ──
        $sheet3 = $spreadsheet->createSheet(2);
        $sheet3->setTitle('Status Summary');

        $sheet3->setCellValue('A1', $org_name . ' - Mapping Status Summary');
        $sheet3->mergeCells('A1:D1');
        $sheet3->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $stHeaders = ['Status', 'Count', 'Debit (₹)', 'Credit (₹)'];
        foreach ($stHeaders as $c => $h) {
            $sheet3->setCellValueByColumnAndRow($c + 1, 3, $h);
        }
        $sheet3->getStyle('A3:D3')->getFont()->setBold(true);
        $sheet3->getStyle('A3:D3')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet3->getStyle('A3:D3')->getFont()->getColor()->setRGB('FFFFFF');

        $stRow = 4;
        foreach ($summary['by_status'] as $status => $data) {
            $sheet3->setCellValueByColumnAndRow(1, $stRow, ucfirst($status));
            $sheet3->setCellValueByColumnAndRow(2, $stRow, $data['count']);
            $sheet3->setCellValueByColumnAndRow(3, $stRow, $data['debit']);
            $sheet3->setCellValueByColumnAndRow(4, $stRow, $data['credit']);
            foreach ([3, 4] as $c) {
                $sheet3->getStyleByColumnAndRow($c, $stRow)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $stRow++;
        }

        foreach (range('A', 'D') as $col) {
            $sheet3->getColumnDimension($col)->setAutoSize(true);
        }

        // ── Sheet 4: Detailed Mapping Ledger ──
        $sheet4 = $spreadsheet->createSheet(3);
        $sheet4->setTitle('Mapping Ledger');

        $sheet4->setCellValue('A1', $org_name . ' - Detailed Mapping Ledger');
        $sheet4->mergeCells('A1:J1');
        $sheet4->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $mlHeaders = ['Txn Date', 'Bank Description', 'Txn Amount (₹)', 'Mapping Type',
                      'Member Code', 'Member Name', 'Account/Ref', 'Mapped Amount (₹)',
                      'Mapped By', 'Mapped At'];
        foreach ($mlHeaders as $c => $h) {
            $sheet4->setCellValueByColumnAndRow($c + 1, 3, $h);
        }
        $sheet4->getStyle('A3:J3')->getFont()->setBold(true);
        $sheet4->getStyle('A3:J3')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet4->getStyle('A3:J3')->getFont()->getColor()->setRGB('FFFFFF');

        // Fetch all mapping rows in date range
        $mappings = $this->_get_mapping_ledger($filters);
        $mlRow = 4;
        foreach ($mappings as $m) {
            $sheet4->setCellValueByColumnAndRow(1, $mlRow, $m->transaction_date);
            $sheet4->setCellValueByColumnAndRow(2, $mlRow, $m->description);
            $sheet4->setCellValueByColumnAndRow(3, $mlRow, floatval($m->txn_amount));
            $sheet4->setCellValueByColumnAndRow(4, $mlRow, ucfirst(str_replace('_', ' ', $m->mapping_type)));
            $sheet4->setCellValueByColumnAndRow(5, $mlRow, $m->member_code ?? '');
            $sheet4->setCellValueByColumnAndRow(6, $mlRow, trim(($m->first_name ?? '') . ' ' . ($m->last_name ?? '')));
            $sheet4->setCellValueByColumnAndRow(7, $mlRow, $m->narration ?? '');
            $sheet4->setCellValueByColumnAndRow(8, $mlRow, floatval($m->amount));
            $sheet4->setCellValueByColumnAndRow(9, $mlRow, $m->admin_name ?? '');
            $sheet4->setCellValueByColumnAndRow(10, $mlRow, $m->mapped_at);
            foreach ([3, 8] as $c) {
                $sheet4->getStyleByColumnAndRow($c, $mlRow)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $sheet4->getStyle('A' . $mlRow . ':J' . $mlRow)->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $mlRow++;
        }

        foreach (range('A', 'J') as $col) {
            $sheet4->getColumnDimension($col)->setAutoSize(true);
        }

        // Set active sheet back to first
        $spreadsheet->setActiveSheetIndex(0);

        // Output
        $filename = 'CA_Bank_Report_' . $filters['from_date'] . '_to_' . $filters['to_date'] . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Fetch bank transactions with mapping details for CA report
     */
    private function _get_ca_report_data($filters) {
        $this->db->select('
            bt.id, bt.transaction_date, bt.value_date, bt.description, bt.description2,
            bt.reference_number, bt.utr_number, bt.cheque_number,
            bt.transaction_type, bt.amount,
            IF(bt.transaction_type = "debit", bt.amount, 0) as debit_amount,
            IF(bt.transaction_type = "credit", bt.amount, 0) as credit_amount,
            bt.running_balance, bt.balance_after,
            bt.mapping_status, bt.mapped_amount, bt.unmapped_amount,
            bt.transaction_category, bt.mapping_remarks,
            ba.bank_name, ba.account_number as bank_account_number,
            ba.branch_name,
            COALESCE(m_by.first_name, "") as paid_by_name,
            COALESCE(m_for.first_name, "") as paid_for_name
        ', false);
        $this->db->from('bank_transactions bt');
        $this->db->join('bank_accounts ba', 'ba.id = bt.bank_account_id', 'left');
        $this->db->join('members m_by', 'm_by.id = bt.paid_by_member_id', 'left');
        $this->db->join('members m_for', 'm_for.id = bt.paid_for_member_id', 'left');

        if (!empty($filters['bank_id'])) {
            $this->db->where('bt.bank_account_id', $filters['bank_id']);
        }
        if (!empty($filters['from_date'])) {
            $this->db->where('bt.transaction_date >=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $this->db->where('bt.transaction_date <=', $filters['to_date']);
        }
        if (!empty($filters['mapping_status'])) {
            $this->db->where('bt.mapping_status', $filters['mapping_status']);
        }

        $this->db->order_by('bt.transaction_date', 'ASC');
        $this->db->order_by('bt.id', 'ASC');
        $transactions = $this->db->get()->result();

        // Enrich each transaction with mapping summary
        foreach ($transactions as &$txn) {
            $mappings = $this->db->select('tm.mapping_type, tm.amount, tm.narration,
                                           m.member_code, m.first_name, m.last_name')
                                 ->from('transaction_mappings tm')
                                 ->join('members m', 'm.id = tm.member_id', 'left')
                                 ->where('tm.bank_transaction_id', $txn->id)
                                 ->where('tm.is_reversed', 0)
                                 ->get()->result();

            $mapped_labels = [];
            $mapped_narrations = [];
            foreach ($mappings as $mp) {
                $type_label = ucfirst(str_replace('_', ' ', $mp->mapping_type));
                $member_label = $mp->member_code ? ($mp->member_code . ' - ' . trim($mp->first_name . ' ' . $mp->last_name)) : '';
                $mapped_labels[] = $type_label . ($member_label ? ' (' . $member_label . ')' : '');
                if ($mp->narration) $mapped_narrations[] = $mp->narration;
            }
            $txn->mapped_to_display = implode('; ', $mapped_labels);
            $txn->mapping_narration = implode('; ', $mapped_narrations);

            // Fill category if not set
            if (empty($txn->transaction_category) && !empty($mappings)) {
                $txn->transaction_category = $mappings[0]->mapping_type;
            }
        }

        return $transactions;
    }

    /**
     * Compute summary stats for CA report
     */
    private function _compute_ca_summary($transactions) {
        $summary = [
            'total_debit' => 0,
            'total_credit' => 0,
            'total_transactions' => count($transactions),
            'mapped_count' => 0,
            'unmapped_count' => 0,
            'partial_count' => 0,
            'ignored_count' => 0,
            'by_category' => [],
            'by_status' => []
        ];

        foreach ($transactions as $txn) {
            $debit = floatval($txn->debit_amount);
            $credit = floatval($txn->credit_amount);
            $summary['total_debit'] += $debit;
            $summary['total_credit'] += $credit;

            // By status
            $st = $txn->mapping_status ?: 'unmapped';
            if (!isset($summary['by_status'][$st])) {
                $summary['by_status'][$st] = ['count' => 0, 'debit' => 0, 'credit' => 0];
            }
            $summary['by_status'][$st]['count']++;
            $summary['by_status'][$st]['debit'] += $debit;
            $summary['by_status'][$st]['credit'] += $credit;

            // Status counters
            if ($st === 'mapped') $summary['mapped_count']++;
            elseif ($st === 'unmapped') $summary['unmapped_count']++;
            elseif ($st === 'partial') $summary['partial_count']++;
            elseif ($st === 'ignored') $summary['ignored_count']++;

            // By category
            $cat = $txn->transaction_category ?: 'uncategorized';
            if (!isset($summary['by_category'][$cat])) {
                $summary['by_category'][$cat] = ['count' => 0, 'debit' => 0, 'credit' => 0];
            }
            $summary['by_category'][$cat]['count']++;
            $summary['by_category'][$cat]['debit'] += $debit;
            $summary['by_category'][$cat]['credit'] += $credit;
        }

        return $summary;
    }

    /**
     * Fetch detailed mapping ledger for Excel Sheet 4
     */
    private function _get_mapping_ledger($filters) {
        $this->db->select('
            bt.transaction_date, bt.description,
            bt.amount as txn_amount, bt.transaction_type,
            tm.mapping_type, tm.amount, tm.narration, tm.mapped_at,
            m.member_code, m.first_name, m.last_name,
            COALESCE(a.full_name, a.username, "") as admin_name
        ', false);
        $this->db->from('transaction_mappings tm');
        $this->db->join('bank_transactions bt', 'bt.id = tm.bank_transaction_id');
        $this->db->join('members m', 'm.id = tm.member_id', 'left');
        $this->db->join('admin_users a', 'a.id = tm.mapped_by', 'left');
        $this->db->where('tm.is_reversed', 0);

        if (!empty($filters['bank_id'])) {
            $this->db->where('bt.bank_account_id', $filters['bank_id']);
        }
        if (!empty($filters['from_date'])) {
            $this->db->where('bt.transaction_date >=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $this->db->where('bt.transaction_date <=', $filters['to_date']);
        }

        $this->db->order_by('bt.transaction_date', 'ASC');
        $this->db->order_by('tm.mapped_at', 'ASC');
        return $this->db->get()->result();
    }
}
