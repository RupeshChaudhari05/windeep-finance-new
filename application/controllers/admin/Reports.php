<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Reports Controller - Reports & Analytics
 */
class Reports extends Admin_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model(['Report_model', 'Ledger_model', 'Member_model']);
    }
    
    /**
     * Reports Dashboard
     */
    public function index() {
        $data['title'] = 'Reports';
        $data['page_title'] = 'Reports & Analytics';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Reports', 'url' => '']
        ];
        
        $this->load_view('admin/reports/index', $data);
    }
    
    /**
     * Collection Report
     */
    public function collection() {
        $data['title'] = 'Collection Report';
        $data['page_title'] = 'Collection Report';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Reports', 'url' => 'admin/reports'],
            ['title' => 'Collection', 'url' => '']
        ];
        
        $from_date = $this->input->get('from_date') ?: date('Y-m-01');
        $to_date = $this->input->get('to_date') ?: date('Y-m-d');
        $type = $this->input->get('type') ?: 'all';
        
        $data['report'] = $this->Report_model->get_collection_report($from_date, $to_date, $type);
        $data['from_date'] = $from_date;
        $data['to_date'] = $to_date;
        $data['type'] = $type;
        
        $this->load_view('admin/reports/collection', $data);
    }
    
    /**
     * Disbursement Report
     */
    public function disbursement() {
        $data['title'] = 'Disbursement Report';
        $data['page_title'] = 'Disbursement Report';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Reports', 'url' => 'admin/reports'],
            ['title' => 'Disbursement', 'url' => '']
        ];
        
        $from_date = $this->input->get('from_date') ?: date('Y-m-01');
        $to_date = $this->input->get('to_date') ?: date('Y-m-d');
        
        $data['report'] = $this->Report_model->get_disbursement_report($from_date, $to_date);
        $data['from_date'] = $from_date;
        $data['to_date'] = $to_date;
        
        $this->load_view('admin/reports/disbursement', $data);
    }
    
    /**
     * Outstanding Report
     */
    public function outstanding() {
        $data['title'] = 'Outstanding Report';
        $data['page_title'] = 'Outstanding Loans Report';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Reports', 'url' => 'admin/reports'],
            ['title' => 'Outstanding', 'url' => '']
        ];
        
        $data['report'] = $this->Report_model->get_outstanding_report();
        
        // Calculate totals
        $data['totals'] = [
            'principal' => array_sum(array_column($data['report'], 'outstanding_principal')),
            'interest' => array_sum(array_column($data['report'], 'outstanding_interest')),
            'overdue' => array_sum(array_column($data['report'], 'overdue_amount'))
        ];
        
        $this->load_view('admin/reports/outstanding', $data);
    }
    
    /**
     * NPA Report
     */
    public function npa() {
        $data['title'] = 'NPA Report';
        $data['page_title'] = 'Non-Performing Assets';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Reports', 'url' => 'admin/reports'],
            ['title' => 'NPA', 'url' => '']
        ];
        
        $days = $this->input->get('days') ?: 90;
        
        $data['report'] = $this->Report_model->get_npa_report($days);
        $data['days'] = $days;
        
        $this->load_view('admin/reports/npa', $data);
    }
    
    /**
     * Member Statement
     */
    public function member_statement() {
        $data['title'] = 'Member Statement';
        $data['page_title'] = 'Member Statement';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Reports', 'url' => 'admin/reports'],
            ['title' => 'Member Statement', 'url' => '']
        ];
        
        $member_id = $this->input->get('member_id');
        $from_date = $this->input->get('from_date');
        $to_date = $this->input->get('to_date');
        
        $data['members_list'] = $this->Member_model->get_active_members_dropdown();
        
        if ($member_id) {
            $data['statement'] = $this->Report_model->get_member_statement($member_id, $from_date, $to_date);
        }
        
        $data['member_id'] = $member_id;
        $data['from_date'] = $from_date;
        $data['to_date'] = $to_date;
        $data['filters'] = [
            'member_id' => $member_id,
            'from_date' => $from_date,
            'to_date' => $to_date
        ];
        
        $this->load_view('admin/reports/member_statement', $data);
    }
    
    /**
     * Demand Report
     */
    public function demand() {
        $data['title'] = 'Demand Report';
        $data['page_title'] = 'Monthly Demand Report';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Reports', 'url' => 'admin/reports'],
            ['title' => 'Demand', 'url' => '']
        ];
        
        $month = $this->input->get('month') ?: date('Y-m-01');
        
        $data['report'] = $this->Report_model->get_demand_report($month);
        $data['month'] = $month;
        
        $this->load_view('admin/reports/demand', $data);
    }
    
    /**
     * Guarantor Exposure Report
     */
    public function guarantor() {
        $data['title'] = 'Guarantor Report';
        $data['page_title'] = 'Guarantor Exposure Report';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Reports', 'url' => 'admin/reports'],
            ['title' => 'Guarantor', 'url' => '']
        ];
        
        $data['report'] = $this->Report_model->get_guarantor_report();
        
        $this->load_view('admin/reports/guarantor', $data);
    }
    
    /**
     * Trial Balance
     */
    public function trial_balance() {
        $data['title'] = 'Trial Balance';
        $data['page_title'] = 'Trial Balance';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Reports', 'url' => 'admin/reports'],
            ['title' => 'Trial Balance', 'url' => '']
        ];
        
        $as_on = $this->input->get('as_on') ?: date('Y-m-d');
        
        $data['trial_balance'] = $this->Ledger_model->get_trial_balance($as_on);
        $data['as_on'] = $as_on;
        
        // Calculate totals
        $data['totals'] = ['debit' => 0, 'credit' => 0];
        foreach ($data['trial_balance'] as $account) {
            $balance = $account->total_debit - $account->total_credit;
            if ($balance > 0) {
                $data['totals']['debit'] += $balance;
            } else {
                $data['totals']['credit'] += abs($balance);
            }
        }
        
        $this->load_view('admin/reports/trial_balance', $data);
    }
    
    /**
     * Profit & Loss
     */
    public function profit_loss() {
        $data['title'] = 'Profit & Loss';
        $data['page_title'] = 'Profit & Loss Statement';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Reports', 'url' => 'admin/reports'],
            ['title' => 'Profit & Loss', 'url' => '']
        ];
        
        $from_date = $this->input->get('from_date') ?: date('Y-04-01');
        $to_date = $this->input->get('to_date') ?: date('Y-m-d');
        
        $data['report'] = $this->Ledger_model->get_profit_loss($from_date, $to_date);
        $data['from_date'] = $from_date;
        $data['to_date'] = $to_date;
        
        $this->load_view('admin/reports/profit_loss', $data);
    }
    
    /**
     * Balance Sheet
     */
    public function balance_sheet() {
        $data['title'] = 'Balance Sheet';
        $data['page_title'] = 'Balance Sheet';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Reports', 'url' => 'admin/reports'],
            ['title' => 'Balance Sheet', 'url' => '']
        ];
        
        $as_on = $this->input->get('as_on') ?: date('Y-m-d');
        
        $data['report'] = $this->Ledger_model->get_balance_sheet($as_on);
        $data['as_on'] = $as_on;
        
        $this->load_view('admin/reports/balance_sheet', $data);
    }
    
    /**
     * General Ledger
     */
    public function general_ledger() {
        $data['title'] = 'General Ledger';
        $data['page_title'] = 'General Ledger';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Reports', 'url' => 'admin/reports'],
            ['title' => 'General Ledger', 'url' => '']
        ];
        
        $filters = [
            'from_date' => $this->input->get('from_date') ?: date('Y-m-01'),
            'to_date' => $this->input->get('to_date') ?: date('Y-m-d'),
            'account_id' => $this->input->get('account_id'),
            'voucher_type' => $this->input->get('voucher_type')
        ];
        
        $data['entries'] = $this->Ledger_model->get_ledger_entries($filters);
        $data['filters'] = $filters;
        $data['accounts'] = $this->Ledger_model->get_chart_of_accounts();
        
        $this->load_view('admin/reports/general_ledger', $data);
    }
    
    /**
     * Account Statement
     */
    public function account_statement() {
        $data['title'] = 'Account Statement';
        $data['page_title'] = 'Account Statement';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Reports', 'url' => 'admin/reports'],
            ['title' => 'Account Statement', 'url' => '']
        ];
        
        $account_id = $this->input->get('account_id');
        $from_date = $this->input->get('from_date') ?: date('Y-m-01');
        $to_date = $this->input->get('to_date') ?: date('Y-m-d');
        
        if ($account_id) {
            $data['statement'] = $this->Ledger_model->get_account_statement($account_id, $from_date, $to_date);
            $data['account'] = $this->db->where('id', $account_id)->get('chart_of_accounts')->row();
        }
        
        $data['accounts'] = $this->Ledger_model->get_chart_of_accounts();
        $data['account_id'] = $account_id;
        $data['from_date'] = $from_date;
        $data['to_date'] = $to_date;
        
        $this->load_view('admin/reports/account_statement', $data);
    }
    
    /**
     * Monthly Summary
     */
    public function monthly_summary() {
        $data['title'] = 'Monthly Summary';
        $data['page_title'] = 'Monthly Summary';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Reports', 'url' => 'admin/reports'],
            ['title' => 'Monthly Summary', 'url' => '']
        ];
        
        $year = $this->input->get('year') ?: date('Y');
        $month = $this->input->get('month') ?: date('m');
        
        $data['summary'] = $this->Report_model->get_monthly_summary($year, $month);
        $data['year'] = $year;
        $data['month'] = $month;
        
        $this->load_view('admin/reports/monthly_summary', $data);
    }
    
    /**
     * Export Report
     */
    public function export($report_type) {
        $format = $this->input->get('format') ?: 'csv';
        
        // Get report data based on type
        switch ($report_type) {
            case 'collection':
                $from_date = $this->input->get('from_date') ?: date('Y-m-01');
                $to_date = $this->input->get('to_date') ?: date('Y-m-d');
                $data = $this->Report_model->get_collection_report($from_date, $to_date);
                $filename = 'collection_report_' . date('Y-m-d');
                break;
                
            case 'outstanding':
                $data = $this->Report_model->get_outstanding_report();
                $filename = 'outstanding_report_' . date('Y-m-d');
                break;
                
            case 'npa':
                $data = $this->Report_model->get_npa_report();
                $filename = 'npa_report_' . date('Y-m-d');
                break;
                
            default:
                redirect('admin/reports');
        }
        
        if ($format === 'csv') {
            $this->export_csv($data, $filename, $report_type);
        } else {
            $this->export_excel($data, $filename, $report_type);
        }
    }
    
    /**
     * Export CSV
     */
    private function export_csv($data, $filename, $type) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        
        $output = fopen('php://output', 'w');
        // BOM for Excel UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        switch ($type) {
            case 'outstanding':
                fputcsv($output, ['Loan No', 'Member Code', 'Member Name', 'Product', 'Principal', 'Outstanding Principal', 'Outstanding Interest', 'Overdue Amount']);
                if (is_array($data) || is_object($data)) {
                    foreach ($data as $row) {
                        fputcsv($output, [
                            $row->loan_number ?? '',
                            $row->member_code ?? '',
                            ($row->first_name ?? '') . ' ' . ($row->last_name ?? ''),
                            $row->product_name ?? '',
                            $row->principal_amount ?? 0,
                            $row->outstanding_principal ?? 0,
                            $row->outstanding_interest ?? 0,
                            $row->overdue_amount ?? 0
                        ]);
                    }
                }
                break;
                
            case 'collection':
                fputcsv($output, ['Date', 'Member Code', 'Member Name', 'Type', 'Amount', 'Payment Mode', 'Reference']);
                if (is_array($data) || is_object($data)) {
                    foreach ($data as $row) {
                        fputcsv($output, [
                            $row->payment_date ?? $row->date ?? '',
                            $row->member_code ?? '',
                            ($row->first_name ?? '') . ' ' . ($row->last_name ?? ''),
                            $row->payment_type ?? $row->type ?? '',
                            $row->total_amount ?? $row->amount ?? 0,
                            $row->payment_mode ?? '',
                            $row->reference_number ?? ''
                        ]);
                    }
                }
                break;
                
            case 'demand':
                fputcsv($output, ['Loan No', 'Member Code', 'Member Name', 'Due Date', 'EMI Amount', 'Days Overdue', 'Phone']);
                if (is_array($data) || is_object($data)) {
                    foreach ($data as $row) {
                        fputcsv($output, [
                            $row->loan_number ?? '',
                            $row->member_code ?? '',
                            ($row->first_name ?? '') . ' ' . ($row->last_name ?? ''),
                            $row->due_date ?? '',
                            $row->emi_amount ?? 0,
                            $row->days_overdue ?? 0,
                            $row->phone ?? ''
                        ]);
                    }
                }
                break;
                
            case 'npa':
                fputcsv($output, ['Loan No', 'Member Code', 'Member Name', 'Principal', 'Outstanding', 'Days Overdue', 'NPA Category']);
                if (is_array($data) || is_object($data)) {
                    foreach ($data as $row) {
                        fputcsv($output, [
                            $row->loan_number ?? '',
                            $row->member_code ?? '',
                            ($row->first_name ?? '') . ' ' . ($row->last_name ?? ''),
                            $row->principal_amount ?? 0,
                            $row->outstanding_amount ?? 0,
                            $row->days_overdue ?? 0,
                            $row->npa_category ?? ''
                        ]);
                    }
                }
                break;
                
            default:
                // Generic CSV export for any data
                if (is_array($data) && !empty($data)) {
                    $first = reset($data);
                    if (is_object($first)) {
                        fputcsv($output, array_keys((array) $first));
                        foreach ($data as $row) {
                            fputcsv($output, array_values((array) $row));
                        }
                    }
                }
                break;
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export Excel (XLSX) using PHPSpreadsheet
     */
    private function export_excel($data, $filename, $type) {
        // Check if PhpSpreadsheet is available
        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            // Fallback to CSV if PhpSpreadsheet not available
            $this->export_csv($data, $filename, $type);
            return;
        }
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(ucfirst(str_replace('_', ' ', $type)));
        
        $row = 1;
        $headers = [];
        
        switch ($type) {
            case 'outstanding':
                $headers = ['Loan No', 'Member Code', 'Member Name', 'Product', 'Principal', 'Outstanding Principal', 'Outstanding Interest', 'Overdue Amount'];
                break;
            case 'collection':
                $headers = ['Date', 'Member Code', 'Member Name', 'Type', 'Amount', 'Payment Mode', 'Reference'];
                break;
            case 'demand':
                $headers = ['Loan No', 'Member Code', 'Member Name', 'Due Date', 'EMI Amount', 'Days Overdue', 'Phone'];
                break;
            case 'npa':
                $headers = ['Loan No', 'Member Code', 'Member Name', 'Principal', 'Outstanding', 'Days Overdue', 'NPA Category'];
                break;
            default:
                if (is_array($data) && !empty($data)) {
                    $first = reset($data);
                    $headers = array_keys((array) $first);
                }
                break;
        }
        
        // Write headers with styling
        foreach ($headers as $col => $header) {
            $cell = $sheet->getCellByColumnAndRow($col + 1, $row);
            $cell->setValue($header);
            $cell->getStyle()->getFont()->setBold(true);
            $sheet->getColumnDimensionByColumn($col + 1)->setAutoSize(true);
        }
        
        // Write data rows
        $row = 2;
        if (is_array($data) || is_object($data)) {
            foreach ($data as $item) {
                $arr = (array) $item;
                $values = array_values($arr);
                foreach ($values as $col => $value) {
                    $sheet->setCellValueByColumnAndRow($col + 1, $row, $value);
                }
                $row++;
            }
        }
        
        // Output
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
    
    /**
     * Overdue Report (alias for demand)
     */
    public function overdue() {
        $this->demand();
    }
    
    /**
     * Member Summary Report
     */
    public function member_summary() {
        $data['title'] = 'Member Summary Report';
        $data['page_title'] = 'Member Summary Report';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Reports', 'url' => 'admin/reports'],
            ['title' => 'Member Summary', 'url' => '']
        ];
        
        $data['report'] = $this->Report_model->get_member_summary_report();
        
        $this->load_view('admin/reports/member_summary', $data);
    }
    
    /**
     * KYC Pending Report
     */
    public function kyc_pending() {
        $data['title'] = 'KYC Pending Report';
        $data['page_title'] = 'KYC Pending Members';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Reports', 'url' => 'admin/reports'],
            ['title' => 'KYC Pending', 'url' => '']
        ];
        
        $data['report'] = $this->Report_model->get_kyc_pending_report();
        
        $this->load_view('admin/reports/kyc_pending', $data);
    }
    
    /**
     * Ageing Analysis Report
     */
    public function ageing() {
        $data['title'] = 'Ageing Analysis';
        $data['page_title'] = 'Loan Ageing Analysis';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Reports', 'url' => 'admin/reports'],
            ['title' => 'Ageing Analysis', 'url' => '']
        ];
        
        $data['report'] = $this->Report_model->get_ageing_report();
        
        $this->load_view('admin/reports/ageing', $data);
    }
    
    /**
     * Audit Log Report
     */
    public function audit_log() {
        $data['title'] = 'Audit Trail';
        $data['page_title'] = 'Audit Trail Report';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Reports', 'url' => 'admin/reports'],
            ['title' => 'Audit Trail', 'url' => '']
        ];
        
        $data['logs'] = $this->Audit_model->search_audit_logs();
        
        $this->load_view('admin/reports/audit_log', $data);
    }
    
    /**
     * Cash Book Report
     */
    public function cash_book() {
        $data['title'] = 'Cash Book';
        $data['page_title'] = 'Cash Book Report';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Reports', 'url' => 'admin/reports'],
            ['title' => 'Cash Book', 'url' => '']
        ];
        
        $from_date = $this->input->get('from_date') ?: date('Y-m-01');
        $to_date = $this->input->get('to_date') ?: date('Y-m-d');
        
        $data['report'] = $this->Report_model->get_cash_book($from_date, $to_date);
        $data['from_date'] = $from_date;
        $data['to_date'] = $to_date;
        
        $this->load_view('admin/reports/cash_book', $data);
    }
    
    /**
     * Bank Reconciliation Report
     */
    public function bank_reconciliation() {
        $data['title'] = 'Bank Reconciliation';
        $data['page_title'] = 'Bank Reconciliation Report';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Reports', 'url' => 'admin/reports'],
            ['title' => 'Bank Reconciliation', 'url' => '']
        ];
        
        $data['report'] = $this->Report_model->get_bank_reconciliation();
        
        $this->load_view('admin/reports/bank_reconciliation', $data);
    }
    
    /**
     * Custom Report Builder
     */
    public function custom() {
        $data['title'] = 'Custom Report';
        $data['page_title'] = 'Custom Report Builder';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Reports', 'url' => 'admin/reports'],
            ['title' => 'Custom Report', 'url' => '']
        ];
        
        $this->load_view('admin/reports/custom', $data);
    }

    /**
     * Send Report via Email (AJAX)
     */
    public function send_email() {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $report_type = $this->input->post('report_type');
        $recipients = $this->input->post('recipients');
        $additional_message = $this->input->post('additional_message');

        if (empty($report_type) || empty($recipients)) {
            echo json_encode(['success' => false, 'message' => 'Report type and recipients are required']);
            return;
        }

        // Get report data based on type
        $report_data = $this->get_report_data_for_email($report_type);

        if ($report_data === false) {
            echo json_encode(['success' => false, 'message' => 'Invalid report type']);
            return;
        }

        // Split recipients by comma and trim
        $recipient_emails = array_map('trim', explode(',', $recipients));

        // Send email
        $result = send_report_email($report_type, $report_data, $recipient_emails, null, $additional_message);

        echo json_encode($result);
    }

    /**
     * Get report data for email sending
     */
    private function get_report_data_for_email($report_type) {
        switch ($report_type) {
            case 'member-summary':
                return $this->Report_model->get_member_summary_report();
            case 'kyc-pending':
                return $this->Report_model->get_kyc_pending_report();
            case 'ageing':
                return $this->Report_model->get_ageing_report();
            case 'cash-book':
                return $this->Report_model->get_cash_book();
            case 'bank-reconciliation':
                return $this->Report_model->get_bank_reconciliation();
            case 'trial-balance':
                return $this->Ledger_model->get_trial_balance();
            case 'profit-loss':
                return $this->Ledger_model->get_profit_loss();
            case 'balance-sheet':
                return $this->Ledger_model->get_balance_sheet();
            case 'general-ledger':
                return $this->Ledger_model->get_general_ledger();
            case 'account-statement':
                return $this->Ledger_model->get_account_statement();
            case 'monthly-summary':
                return $this->Ledger_model->get_monthly_summary();
            case 'disbursement':
                return $this->Report_model->get_disbursement_report();
            case 'npa':
                return $this->Report_model->get_npa_report();
            case 'demand':
                return $this->Report_model->get_demand_report();
            case 'guarantor':
                return $this->Report_model->get_guarantor_report();
            case 'overdue':
                return $this->Report_model->get_overdue_report();
            case 'audit-log':
                return $this->Report_model->get_audit_log_report();
            default:
                return false;
        }
    }

    // -------------------------------------------------------------------------
    // ONE-CLICK EXPORTS
    // -------------------------------------------------------------------------

    /**
     * Export All Members → CSV (one click)
     * URL: admin/reports/export_members
     */
    public function export_members() {
        $members = $this->db
            ->select('m.member_code, m.first_name, m.middle_name, m.last_name,
                      m.gender, m.date_of_birth, m.phone, m.alternate_phone, m.email,
                      m.occupation, m.monthly_income,
                      m.address_line1, m.address_line2, m.city, m.state, m.pincode,
                      m.id_proof_type, m.id_proof_number,
                      m.aadhaar_number, m.pan_number,
                      m.bank_name, m.bank_ifsc, m.bank_account_number,
                      m.nominee_name, m.nominee_relation, m.nominee_phone,
                      m.membership_type, m.member_level,
                      m.join_date, m.status, m.kyc_verified, m.created_at')
            ->from('members m')
            ->where('m.deleted_at IS NULL', null, false)
            ->order_by('m.member_code', 'ASC')
            ->get()->result();

        $filename = 'members_export_' . date('Y-m-d');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
        fputcsv($out, [
            'Member Code','First Name','Middle Name','Last Name',
            'Gender','Date of Birth','Phone','Alt Phone','Email',
            'Occupation','Monthly Income',
            'Address','Address 2','City','State','Pincode',
            'ID Type','ID Number','Aadhaar','PAN',
            'Bank Name','IFSC','Bank Account No',
            'Nominee Name','Nominee Relation','Nominee Phone',
            'Membership Type','Member Level',
            'Join Date','Status','KYC Verified','Created At'
        ]);
        foreach ($members as $m) {
            fputcsv($out, [
                $m->member_code, $m->first_name, $m->middle_name, $m->last_name,
                $m->gender, $m->date_of_birth, $m->phone, $m->alternate_phone, $m->email,
                $m->occupation, $m->monthly_income,
                $m->address_line1, $m->address_line2, $m->city, $m->state, $m->pincode,
                $m->id_proof_type, $m->id_proof_number, $m->aadhaar_number, $m->pan_number,
                $m->bank_name, $m->bank_ifsc, $m->bank_account_number,
                $m->nominee_name, $m->nominee_relation, $m->nominee_phone,
                $m->membership_type, $m->member_level,
                $m->join_date, $m->status, $m->kyc_verified ? 'Yes' : 'No', $m->created_at
            ]);
        }
        fclose($out);
        exit;
    }

    /**
     * Export All Loans + Installments + Fines → CSV (one click)
     * URL: admin/reports/export_loans_full
     */
    public function export_loans_full() {
        // Sheet 1 – Loans
        $loans = $this->db
            ->select('l.loan_number, m.member_code,
                      CONCAT(m.first_name," ",m.last_name) as member_name, m.phone,
                      lp.product_name,
                      l.principal_amount, l.interest_rate, l.interest_type,
                      l.tenure_months, l.emi_amount, l.total_interest, l.total_payable,
                      l.net_disbursement, l.outstanding_principal,
                      l.total_amount_paid, l.outstanding_fine,
                      l.disbursement_date, l.first_emi_date, l.last_emi_date,
                      l.status, l.is_npa, l.npa_category, l.days_overdue')
            ->from('loans l')
            ->join('members m', 'm.id = l.member_id')
            ->join('loan_products lp', 'lp.id = l.loan_product_id', 'left')
            ->order_by('l.disbursement_date', 'DESC')
            ->get()->result();

        // Sheet 2 – Installments
        $installments = $this->db
            ->select('l.loan_number, m.member_code,
                      li.installment_number, li.due_date,
                      li.principal_amount, li.interest_amount, li.emi_amount,
                      li.principal_paid, li.interest_paid, li.fine_amount, li.fine_paid,
                      li.total_paid, li.status, li.paid_date, li.days_late')
            ->from('loan_installments li')
            ->join('loans l', 'l.id = li.loan_id')
            ->join('members m', 'm.id = l.member_id')
            ->order_by('l.loan_number', 'ASC')
            ->order_by('li.installment_number', 'ASC')
            ->get()->result();

        // Sheet 3 – Fines
        $fines = $this->db
            ->select('f.fine_code, m.member_code,
                      CONCAT(m.first_name," ",m.last_name) as member_name,
                      f.fine_type, f.related_type, f.fine_date, f.due_date,
                      f.days_late, f.fine_amount, f.paid_amount, f.waived_amount,
                      f.balance_amount, f.status, f.payment_date, f.payment_mode,
                      f.remarks')
            ->from('fines f')
            ->join('members m', 'm.id = f.member_id')
            ->order_by('f.fine_date', 'DESC')
            ->get()->result();

        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            // Fallback: single CSV with all loans
            $filename = 'loans_full_export_' . date('Y-m-d');
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            header('Pragma: public');
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['--- LOANS ---']);
            if (!empty($loans)) {
                fputcsv($out, array_keys((array)$loans[0]));
                foreach ($loans as $r) fputcsv($out, array_values((array)$r));
            }
            fputcsv($out, []);
            fputcsv($out, ['--- INSTALLMENTS ---']);
            if (!empty($installments)) {
                fputcsv($out, array_keys((array)$installments[0]));
                foreach ($installments as $r) fputcsv($out, array_values((array)$r));
            }
            fputcsv($out, []);
            fputcsv($out, ['--- FINES ---']);
            if (!empty($fines)) {
                fputcsv($out, array_keys((array)$fines[0]));
                foreach ($fines as $r) fputcsv($out, array_values((array)$r));
            }
            fclose($out);
            exit;
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $this->_write_sheet($spreadsheet, 0, 'Loans', $loans, [
            'Loan No','Member Code','Member Name','Phone','Product',
            'Principal','Rate','Interest Type','Tenure','EMI',
            'Total Interest','Total Payable','Net Disbursed',
            'Outstanding Principal','Amt Paid','Outstanding Fine',
            'Disbursement Date','First EMI','Last EMI',
            'Status','NPA','NPA Category','Days Overdue'
        ]);
        $this->_write_sheet($spreadsheet, 1, 'Installments', $installments, [
            'Loan No','Member Code','EMI #','Due Date',
            'Principal','Interest','EMI Amt',
            'Principal Paid','Interest Paid','Fine Amt','Fine Paid',
            'Total Paid','Status','Paid Date','Days Late'
        ]);
        $this->_write_sheet($spreadsheet, 2, 'Fines', $fines, [
            'Fine Code','Member Code','Member Name',
            'Fine Type','Related Type','Fine Date','Due Date',
            'Days Late','Fine Amt','Paid','Waived','Balance','Status',
            'Payment Date','Payment Mode','Remarks'
        ]);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="loans_full_export_' . date('Y-m-d') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Export All Savings Accounts + Transactions → CSV / Excel (one click)
     * URL: admin/reports/export_savings_full
     */
    public function export_savings_full() {
        // Savings Accounts
        $accounts = $this->db
            ->select('sa.account_number, m.member_code,
                      CONCAT(m.first_name," ",m.last_name) as member_name, m.phone,
                      sc.scheme_name,
                      sa.monthly_amount, sa.start_date, sa.maturity_date,
                      sa.total_deposited, sa.total_interest_earned,
                      sa.total_fines_paid, sa.current_balance,
                      sa.status, sa.closed_at, sa.created_at')
            ->from('savings_accounts sa')
            ->join('members m', 'm.id = sa.member_id')
            ->join('savings_schemes sc', 'sc.id = sa.scheme_id', 'left')
            ->order_by('sa.account_number', 'ASC')
            ->get()->result();

        // Savings Transactions
        $txns = $this->db
            ->select('sa.account_number, m.member_code,
                      CONCAT(m.first_name," ",m.last_name) as member_name,
                      st.transaction_code, st.transaction_type, st.amount,
                      st.balance_after, st.payment_mode, st.transaction_date,
                      st.for_month, st.narration, st.receipt_number')
            ->from('savings_transactions st')
            ->join('savings_accounts sa', 'sa.id = st.savings_account_id')
            ->join('members m', 'm.id = sa.member_id')
            ->order_by('st.transaction_date', 'DESC')
            ->order_by('st.id', 'DESC')
            ->get()->result();

        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            $filename = 'savings_full_export_' . date('Y-m-d');
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            header('Pragma: public');
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['--- SAVINGS ACCOUNTS ---']);
            if (!empty($accounts)) {
                fputcsv($out, array_keys((array)$accounts[0]));
                foreach ($accounts as $r) fputcsv($out, array_values((array)$r));
            }
            fputcsv($out, []);
            fputcsv($out, ['--- TRANSACTIONS ---']);
            if (!empty($txns)) {
                fputcsv($out, array_keys((array)$txns[0]));
                foreach ($txns as $r) fputcsv($out, array_values((array)$r));
            }
            fclose($out);
            exit;
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $this->_write_sheet($spreadsheet, 0, 'Accounts', $accounts, [
            'Account No','Member Code','Member Name','Phone','Scheme',
            'Monthly Amt','Start Date','Maturity Date',
            'Total Deposited','Interest Earned','Fines Paid','Balance',
            'Status','Closed At','Created At'
        ]);
        $this->_write_sheet($spreadsheet, 1, 'Transactions', $txns, [
            'Account No','Member Code','Member Name',
            'Txn Code','Type','Amount','Balance After',
            'Payment Mode','Date','For Month','Narration','Receipt No'
        ]);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="savings_full_export_' . date('Y-m-d') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Helper: write a sheet into a Spreadsheet object
     */
    private function _write_sheet($spreadsheet, $index, $title, $data, $headers) {
        if ($index === 0) {
            $sheet = $spreadsheet->getActiveSheet();
        } else {
            $sheet = $spreadsheet->createSheet($index);
        }
        $sheet->setTitle($title);

        // Header row
        foreach ($headers as $col => $h) {
            $cell = $sheet->getCellByColumnAndRow($col + 1, 1);
            $cell->setValue($h);
            $cell->getStyle()->getFont()->setBold(true);
            $sheet->getColumnDimensionByColumn($col + 1)->setAutoSize(true);
        }

        // Data rows
        $row = 2;
        foreach ($data as $item) {
            $values = array_values((array) $item);
            foreach ($values as $col => $val) {
                $sheet->setCellValueByColumnAndRow($col + 1, $row, $val);
            }
            $row++;
        }
    }
}
