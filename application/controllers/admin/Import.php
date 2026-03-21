<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Import Controller — Bulk Data Import (Members, Loans, Savings Transactions)
 * Supports Excel (.xlsx) and CSV (.csv) uploads
 */
class Import extends Admin_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(['Member_model', 'Loan_model', 'Savings_model', 'Import_model']);
    }

    /**
     * Main Import Dashboard
     */
    public function index() {
        $data['title'] = 'Bulk Data Import';
        $data['page_title'] = 'Bulk Data Import';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Import', 'url' => '']
        ];

        // Get stats for the page
        $data['member_count'] = (int) $this->db->where('deleted_at IS NULL', null, false)->count_all_results('members');
        $data['loan_count']   = (int) $this->db->count_all_results('loans');
        $data['savings_tx_count'] = (int) $this->db->count_all_results('savings_transactions');

        // Get recent imports
        if ($this->db->table_exists('import_logs')) {
            $data['recent_imports'] = $this->db->order_by('created_at', 'DESC')->limit(20)->get('import_logs')->result();
        } else {
            $data['recent_imports'] = [];
        }

        // Get loan products and savings schemes for dropdowns
        $data['loan_products'] = $this->db->where('is_active', 1)->get('loan_products')->result();
        $data['savings_schemes'] = $this->db->where('is_active', 1)->get('savings_schemes')->result();

        $this->load_view('admin/import/index', $data);
    }

    /**
     * Download Sample Excel Template
     */
    public function download_template($type = 'members') {
        require_once FCPATH . 'vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        switch ($type) {
            case 'members':
                $headers = [
                    'first_name*', 'last_name*', 'phone*', 'email', 'date_of_birth',
                    'gender (male/female/other)', 'father_name', 'occupation', 'monthly_income',
                    'address_line1', 'address_line2', 'city', 'state', 'pincode',
                    'aadhaar_number', 'pan_number', 'bank_name', 'bank_branch',
                    'account_number', 'ifsc_code', 'account_holder_name',
                    'join_date (YYYY-MM-DD)', 'membership_type (regular/premium/founder)',
                    'nominee_name', 'nominee_relation', 'nominee_phone', 'notes'
                ];
                // Add sample data
                $sample = [
                    'Rajesh', 'Kumar', '9876543210', 'rajesh@email.com', '1990-05-15',
                    'male', 'Ramesh Kumar', 'Business', '50000',
                    '123 Main Street', 'Near Temple', 'Mumbai', 'Maharashtra', '400001',
                    '123456789012', 'ABCDE1234F', 'SBI', 'Andheri Branch',
                    '1234567890', 'SBIN0001234', 'Rajesh Kumar',
                    '2024-01-01', 'regular',
                    'Priya Kumar', 'wife', '9876543211', 'First member'
                ];
                $sheet->setTitle('Members Import');
                break;

            case 'loans':
                $headers = [
                    'member_code* (e.g. MEMB000001)', 'loan_product_id*', 'principal_amount*',
                    'interest_rate*', 'interest_type* (flat/reducing)', 'tenure_months*',
                    'disbursement_date* (YYYY-MM-DD)', 'first_emi_date (YYYY-MM-DD)',
                    'disbursement_mode (cash/bank_transfer/cheque)', 'disbursement_reference',
                    'processing_fee', 'status (active/closed)', 'remarks'
                ];
                $sample = [
                    'MEMB000001', '1', '100000',
                    '12', 'reducing', '12',
                    '2024-06-01', '2024-07-01',
                    'bank_transfer', 'REF-001',
                    '1000', 'active', 'Import loan'
                ];
                $sheet->setTitle('Loans Import');
                break;

            case 'savings_transactions':
                $headers = [
                    'member_code* (e.g. MEMB000001)', 'account_number (e.g. SAV2024000001)',
                    'scheme_id (if no account_number)', 'transaction_type* (deposit/withdrawal)',
                    'amount*', 'transaction_date* (YYYY-MM-DD)',
                    'payment_mode (cash/bank_transfer/cheque/upi)', 'reference_number',
                    'remarks'
                ];
                $sample = [
                    'MEMB000001', 'SAV2024000001',
                    '', 'deposit',
                    '5000', '2024-06-15',
                    'cash', 'REC-001',
                    'Monthly deposit'
                ];
                $sheet->setTitle('Savings Transactions');
                break;

            default:
                show_404();
                return;
        }

        // Write headers (bold, colored)
        $col = 1;
        foreach ($headers as $header) {
            $cell = $sheet->getCellByColumnAndRow($col, 1);
            $cell->setValue($header);
            $cell->getStyle()->getFont()->setBold(true);
            $cell->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $cell->getStyle()->getFill()->getStartColor()->setARGB('FF4472C4');
            $cell->getStyle()->getFont()->getColor()->setARGB('FFFFFFFF');
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
            $col++;
        }

        // Write sample row
        $col = 1;
        foreach ($sample as $val) {
            $sheet->getCellByColumnAndRow($col, 2)->setValue($val);
            $col++;
        }

        // Add instructions sheet
        $instructions = $spreadsheet->createSheet();
        $instructions->setTitle('Instructions');
        $instructions->setCellValue('A1', 'Import Instructions');
        $instructions->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $instructions->setCellValue('A3', '1. Fields marked with * are required');
        $instructions->setCellValue('A4', '2. Dates must be in format YYYY-MM-DD (e.g. 2024-06-15)');
        $instructions->setCellValue('A5', '3. Phone numbers should be 10 digits (no country code)');
        $instructions->setCellValue('A6', '4. The first row is headers - do NOT delete it');
        $instructions->setCellValue('A7', '5. Sample data in row 2 should be replaced with your data');
        $instructions->setCellValue('A8', '6. For loans: member_code must match an existing member');
        $instructions->setCellValue('A9', '7. For savings: provide either account_number OR member_code + scheme_id');
        $instructions->setCellValue('A10', '8. Maximum 500 rows per import');
        $instructions->getColumnDimension('A')->setWidth(70);

        $spreadsheet->setActiveSheetIndex(0);

        // Output
        $filename = "import_template_{$type}_" . date('Y-m-d') . ".xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Preview uploaded file (AJAX)
     */
    public function preview() {
        if ($this->input->method() !== 'post') {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }

        $type = $this->input->post('import_type');
        if (!in_array($type, ['members', 'loans', 'savings_transactions'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid import type']);
            return;
        }

        // Handle file upload
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== 0) {
            echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
            return;
        }

        $file = $_FILES['import_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Please upload .xlsx, .xls or .csv']);
            return;
        }

        try {
            $rows = $this->_parse_file($file['tmp_name'], $ext);

            if (empty($rows)) {
                echo json_encode(['success' => false, 'message' => 'File is empty or has no data rows']);
                return;
            }

            if (count($rows) > 500) {
                echo json_encode(['success' => false, 'message' => 'Maximum 500 rows allowed. Your file has ' . count($rows) . ' rows.']);
                return;
            }

            // Validate rows
            $validated = $this->Import_model->validate_rows($type, $rows);

            // Store in session for import
            $this->session->set_userdata('import_data', [
                'type' => $type,
                'rows' => $rows,
                'validated' => $validated
            ]);

            echo json_encode([
                'success' => true,
                'total_rows' => count($rows),
                'valid_rows' => $validated['valid_count'],
                'error_rows' => $validated['error_count'],
                'errors' => $validated['errors'],
                'preview' => array_slice($rows, 0, 10), // First 10 rows
                'headers' => array_keys($rows[0] ?? [])
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error reading file: ' . $e->getMessage()]);
        }
    }

    /**
     * Execute Import (AJAX)
     */
    public function execute() {
        if ($this->input->method() !== 'post') {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }

        $import_data = $this->session->userdata('import_data');
        if (!$import_data) {
            echo json_encode(['success' => false, 'message' => 'No import data found. Please upload the file again.']);
            return;
        }

        $type = $import_data['type'];
        $rows = $import_data['rows'];
        $admin_id = $this->session->userdata('admin_id');

        try {
            $result = $this->Import_model->execute_import($type, $rows, $admin_id);

            // Clear session data
            $this->session->unset_userdata('import_data');

            // Log the import
            $this->_log_import($type, $result);

            echo json_encode([
                'success' => true,
                'message' => "Import completed! {$result['inserted']} records imported, {$result['skipped']} skipped, {$result['errors']} errors.",
                'inserted' => $result['inserted'],
                'skipped' => $result['skipped'],
                'errors' => $result['errors'],
                'error_details' => $result['error_details']
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Import failed: ' . $e->getMessage()]);
        }
    }

    // ===== PRIVATE METHODS =====

    /**
     * Parse uploaded file into array of associative rows
     */
    private function _parse_file($filepath, $ext) {
        require_once FCPATH . 'vendor/autoload.php';

        if ($ext === 'csv') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
        } elseif ($ext === 'xls') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        } else {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        }

        $spreadsheet = $reader->load($filepath);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, true, false);

        if (count($data) < 2) {
            return []; // Only header, no data
        }

        // First row = headers
        $headers = array_map(function($h) {
            // Clean header: remove *, strip (explanations), trim
            $h = preg_replace('/\*/', '', $h);
            $h = preg_replace('/\s*\(.*?\)\s*/', '', $h);
            return trim(strtolower(str_replace(' ', '_', $h)));
        }, $data[0]);

        $rows = [];
        for ($i = 1; $i < count($data); $i++) {
            $row = [];
            $is_empty = true;
            foreach ($headers as $col_idx => $header) {
                if (empty($header)) continue;
                $val = isset($data[$i][$col_idx]) ? trim($data[$i][$col_idx]) : '';
                $row[$header] = $val;
                if ($val !== '') $is_empty = false;
            }
            if (!$is_empty) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * Log import to database
     */
    private function _log_import($type, $result) {
        // Create table if not exists
        if (!$this->db->table_exists('import_logs')) {
            $this->db->query("CREATE TABLE IF NOT EXISTS `import_logs` (
                `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `import_type` VARCHAR(50) NOT NULL,
                `total_rows` INT(11) DEFAULT 0,
                `inserted` INT(11) DEFAULT 0,
                `skipped` INT(11) DEFAULT 0,
                `errors` INT(11) DEFAULT 0,
                `error_details` TEXT DEFAULT NULL,
                `created_by` INT(10) UNSIGNED DEFAULT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        }

        $this->db->insert('import_logs', [
            'import_type' => $type,
            'total_rows' => $result['inserted'] + $result['skipped'] + $result['errors'],
            'inserted' => $result['inserted'],
            'skipped' => $result['skipped'],
            'errors' => $result['errors'],
            'error_details' => json_encode($result['error_details']),
            'created_by' => $this->session->userdata('admin_id'),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
