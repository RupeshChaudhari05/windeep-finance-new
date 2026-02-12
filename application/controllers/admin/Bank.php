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
                  "  calculate_fine_url: '" . site_url('admin/bank/calculate_fine_due') . "'\n" .
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
                  "  calculate_fine_url: '" . site_url('admin/bank/calculate_fine_due') . "'\n" .
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
            $txn = $this->db->where('id', $transaction_id)->get('bank_transactions')->row();
            if (!$txn) { 
                log_message('error', 'Transaction not found: ID=' . $transaction_id);
                $this->ajax_response(false, 'Transaction not found'); 
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
            log_message('debug', '  Total Mapped: ' . $total);
            log_message('debug', '  Mappings: ' . json_encode($mapping_details));
            log_message('debug', '  Transaction Object: ' . json_encode($txn));

            // Use a small tolerance for floating point comparison (0.01 = 1 paisa)
            if ($total > ($txn_amount + 0.01)) {
                $error_msg = sprintf(
                    'Mapped amounts (₹%.2f) exceed transaction amount (₹%.2f) by ₹%.2f. Txn fields: credit=%.2f, debit=%.2f, amount=%.2f',
                    $total,
                    $txn_amount,
                    $total - $txn_amount,
                    floatval($txn->credit_amount ?? 0),
                    floatval($txn->debit_amount ?? 0),
                    floatval($txn->amount ?? 0)
                );
                log_message('error', 'Mapping validation failed: ' . $error_msg);
                $this->ajax_response(false, $error_msg);
                return;
            }

            if ($txn_amount == 0) {
                log_message('error', 'Transaction amount is zero. Cannot map. Transaction: ' . json_encode($txn));
                $this->ajax_response(false, 'Transaction amount is zero. Cannot proceed with mapping.');
                return;
            }

            $this->db->trans_begin();

            try {
                foreach ($mappings as $m) {
                    $paying = $m['paying_member_id'] ?? null;
                    $paid_for = $m['paid_for_member_id'] ?? null;
                    $type = $m['transaction_type'] ?? null;
                    $related = $m['related_account'] ?? null;
                    $amount = (float) ($m['amount'] ?? 0);
                    $m_remarks = $m['remarks'] ?? null;

                    if ($amount <= 0) continue;

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
                                $payment_data = [
                                    'loan_id' => $insert['related_id'],
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
                $new_status = ($total == $txn_amount) ? 'mapped' : 'partial';
                $update = [
                    'mapping_status' => $new_status,
                    'mapped_amount' => $total,
                    'unmapped_amount' => $txn_amount - $total,
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
                    $this->session->set_flashdata('error', 'Failed to create bank account.');
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
                    $this->session->set_flashdata('error', 'Failed to update bank account.');
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
                $full_name = (property_exists($member, 'full_name') && !empty($member->full_name)) ? $member->full_name : '';
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
                'text' => 'Loan: ' . $loan->loan_number . ' (₹' . number_format($loan->pending_amount, 2) . ')',
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
                    'due_date' => date('d M Y', strtotime($inst->due_date)),
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
                'fine_date' => isset($fine->fine_date) ? date('d M Y', strtotime($fine->fine_date)) : ''
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
}
