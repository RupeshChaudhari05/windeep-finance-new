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
        
        $data['today_summary'] = $this->Report_model->quick_summary(date('Y-m-d'), date('Y-m-d'));
        $data['mtd_summary']   = $this->Report_model->quick_summary(date('Y-m-01'), date('Y-m-d'));
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
        
        // The old collection view expected a different data shape and showed empty tables
        $spec = $this->_xls_collection($data);
        $spec['icon'] = 'fa-receipt';
        $spec['filters'] = [
            ['name' => 'from_date', 'label' => 'From', 'type' => 'date', 'value' => $from_date],
            ['name' => 'to_date', 'label' => 'To', 'type' => 'date', 'value' => $to_date],
            ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'value' => $type, 'options' => ['all' => 'Loan + savings', 'loan' => 'Loan repayments', 'savings' => 'Savings deposits']],
        ];
        $spec['table_title'] = 'Loan repayments';
        $this->_render_spec($spec);
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
        
        $spec = $this->_xls_disbursement($data);
        $spec['icon'] = 'fa-money-bill-wave';
        $spec['filters'] = [
            ['name' => 'from_date', 'label' => 'From', 'type' => 'date', 'value' => $from_date],
            ['name' => 'to_date', 'label' => 'To', 'type' => 'date', 'value' => $to_date],
        ];
        $spec['table_title'] = 'Loans disbursed';
        $spec['note'] = '"Money disbursed in period" counts each disbursement part on its own date; "Disbursed so far" is what has been released on the loan to date.';
        $this->_render_spec($spec);
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
        
        $this->_render_spec($this->_xls_outstanding($data));
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
        
        $spec = $this->_xls_npa($data);
        $spec['icon'] = 'fa-exclamation-triangle';
        $spec['filters'] = [['name' => 'days', 'label' => 'Overdue more than', 'type' => 'select', 'value' => (int) $days,
                             'options' => [30 => '30 days', 60 => '60 days', 90 => '90 days (NPA)', 180 => '180 days', 365 => '365 days']]];
        $spec['note'] = 'Overdue amount counts EMIs older than the threshold. SMA < 90 days, Sub-standard 90–365 days, Doubtful / Loss > 365 days.';
        $this->_render_spec($spec);
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
        
        $opts = ['' => '— choose member —'];
        foreach ($data['members_list'] as $m) $opts[$m->id] = $m->member_code . ' — ' . trim($m->first_name . ' ' . $m->last_name);
        $filters = [
            ['name' => 'member_id', 'label' => 'Member', 'type' => 'select', 'value' => $member_id, 'options' => $opts],
            ['name' => 'from_date', 'label' => 'From', 'type' => 'date', 'value' => $from_date],
            ['name' => 'to_date', 'label' => 'To', 'type' => 'date', 'value' => $to_date],
        ];
        if (!$member_id || empty($data['statement']['member'])) {
            $this->_render_spec(['title' => 'Member Statement', 'icon' => 'fa-file-alt', 'filters' => $filters,
                                 'columns' => [['key' => 'x', 'label' => 'Choose a member above']], 'rows' => [],
                                 'note' => 'Pick a member to see the ledger, savings, loans and fines.']);
            return;
        }
        $spec = $this->_xls_member_statement($data);
        $s = $data['statement'];
        $spec['icon'] = 'fa-file-alt';
        $spec['filters'] = $filters;
        $spec['table_title'] = 'Member ledger';
        $spec['kpis'] = [
            ['label' => 'Savings balance', 'value' => array_sum(array_map(function ($a) { return (float) ($a->current_balance ?? 0); },
                         $this->db->where('member_id', $member_id)->get('savings_accounts')->result())), 'type' => 'money', 'color' => 'success'],
            ['label' => 'Loan outstanding', 'value' => self::_sum($s['loans'] ?? [], 'outstanding_principal'), 'type' => 'money', 'color' => 'danger'],
            ['label' => 'Fines open', 'value' => self::_sum(array_filter($s['fines'] ?? [], function ($f) { return in_array($f->status, ['pending', 'partial']); }), 'balance_amount'), 'type' => 'money', 'color' => 'warning'],
            ['label' => 'Ledger entries', 'value' => count($s['ledger'] ?? []), 'type' => 'int'],
        ];
        $this->_render_spec($spec);
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
        
        $m = (string) $this->input->get('month');   // the month picker sends YYYY-MM; due_month is a date
        $ts = $m !== '' ? strtotime(strlen($m) === 7 ? $m . '-01' : $m) : false;
        $month = $ts ? date('Y-m-01', $ts) : date('Y-m-01');
        
        $data['report'] = $this->Report_model->get_demand_report($month);
        $data['month'] = $month;
        
        $spec = $this->_xls_demand($data);
        $spec['filters'] = [['name' => 'month', 'label' => 'Month', 'type' => 'month', 'value' => date('Y-m', strtotime($month))]];
        $this->_render_spec($spec);
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
        
        $this->_render_spec($this->_xls_guarantor($data));
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
        
        $this->_excel_if_requested('_xls_trial_balance', $data);
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
        
        $from_date = $this->input->get('from_date') ?: $this->_get_fiscal_year_start();
        $to_date = $this->input->get('to_date') ?: date('Y-m-d');
        
        $data['report'] = $this->Ledger_model->get_profit_loss($from_date, $to_date);
        $data['from_date'] = $from_date;
        $data['to_date'] = $to_date;
        
        $this->_excel_if_requested('_xls_profit_loss', $data);
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
        
        $this->_excel_if_requested('_xls_balance_sheet', $data);
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
        
        $this->_excel_if_requested('_xls_general_ledger', $data);
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
        // The view reads the selection from $filters
        $data['filters'] = ['account_id' => $account_id, 'from_date' => $from_date, 'to_date' => $to_date];
        
        if ($account_id) $this->_excel_if_requested('_xls_account_statement', $data);
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
        
        $period = (string) $this->input->get('period');   // YYYY-MM from the month picker; year/month still accepted
        if (preg_match('/^(\d{4})-(\d{2})$/', $period, $pm)) { $year = (int) $pm[1]; $month = (int) $pm[2]; }
        else { $year = (int) ($this->input->get('year') ?: date('Y')); $month = (int) ($this->input->get('month') ?: date('m')); }
        if ($month < 1 || $month > 12) $month = (int) date('m');

        $data['summary'] = $this->Report_model->get_monthly_summary($year, $month);
        $data['year'] = $year;
        $data['month'] = $month;
        
        // the old view read keys the model never returned (most figures showed 0)
        $spec = $this->_xls_monthly_summary($data);
        $spec['filters'] = [['name' => 'period', 'label' => 'Month', 'type' => 'month', 'value' => sprintf('%04d-%02d', $year, $month)]];
        $daily = $this->Report_model->get_monthly_daily($year, $month);
        $spec['chart'] = ['type' => 'bar', 'labels' => $daily['labels'], 'datasets' => [
            ['label' => 'Collection (loan + savings)', 'data' => $daily['collection']],
            ['label' => 'Disbursement', 'data' => $daily['disbursement']],
        ]];
        $this->_render_spec($spec);
    }
    
    /**
     * Other Transactions Report (Membership Fee, Joining Fee, Processing Fee, etc.)
     */
    public function other_transactions() {
        $data['title'] = 'Other Transactions Report';
        $data['page_title'] = 'Member Other Transactions';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Reports', 'url' => 'admin/reports'],
            ['title' => 'Other Transactions', 'url' => '']
        ];

        $from_date = $this->input->get('from_date') ?: date('Y-m-01');
        $to_date   = $this->input->get('to_date') ?: date('Y-m-d');
        $type      = $this->input->get('type') ?: '';
        $member_id = $this->input->get('member_id') ?: '';

        $filters = [
            'from_date' => $from_date,
            'to_date'   => $to_date,
            'type'      => $type,
            'member_id' => $member_id
        ];

        $this->load->model('Member_transaction_model');
        $data['transactions'] = $this->Member_transaction_model->get_all_transactions($filters);
        $data['from_date'] = $from_date;
        $data['to_date']   = $to_date;
        $data['type']      = $type;
        $data['member_id'] = $member_id;

        // Summary totals
        $data['summary'] = $this->Member_transaction_model->get_totals_by_type();

        $this->_excel_if_requested('_xls_other_transactions', $data);
        $this->load_view('admin/reports/other_transactions', $data);
    }

    /**
     * Export Other Transactions as CSV/Excel
     */
    public function export_other_transactions() {
        $format    = $this->input->get('format') ?: 'csv';
        $from_date = $this->input->get('from_date') ?: date('Y-m-01');
        $to_date   = $this->input->get('to_date') ?: date('Y-m-d');
        $type      = $this->input->get('type') ?: '';
        $member_id = $this->input->get('member_id') ?: '';

        $filters = [
            'from_date' => $from_date,
            'to_date'   => $to_date,
            'type'      => $type,
            'member_id' => $member_id
        ];

        $this->load->model('Member_transaction_model');
        $rows = $this->Member_transaction_model->get_all_transactions($filters);
        $filename = 'other_transactions_' . date('Y-m-d');

        if ($format === 'excel') {
            $this->export_excel($rows, $filename, 'other_transactions');
        } else {
            $this->export_csv($rows, $filename, 'other_transactions');
        }
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
                
            case 'other_transactions':
                fputcsv($output, ['Date', 'Member Code', 'Member Name', 'Phone', 'Transaction Type', 'Amount', 'Payment Mode', 'Receipt No', 'Description', 'Status']);
                if (is_array($data) || is_object($data)) {
                    foreach ($data as $row) {
                        fputcsv($output, [
                            $row->transaction_date ?? '',
                            $row->member_code ?? '',
                            ($row->first_name ?? '') . ' ' . ($row->last_name ?? ''),
                            $row->phone ?? '',
                            ucwords(str_replace('_', ' ', $row->transaction_type ?? '')),
                            $row->amount ?? 0,
                            $row->payment_mode ?? '',
                            $row->receipt_number ?? '',
                            $row->description ?? '',
                            $row->status ?? ''
                        ]);
                    }
                }
                break;

            default:
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
        
        $this->load->library('Excel_report');
        $rows = [];
        foreach ((is_array($data) || is_object($data)) ? $data : [] as $item) $rows[] = $this->_get_row_values($type, $item);
        $this->excel_report->download(Excel_report::from_headers(ucwords(str_replace('_', ' ', $type)) . ' Report', $this->_export_headers($type, $data), $rows), $filename);
        return;
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
            case 'other_transactions':
                $headers = ['Date', 'Member Code', 'Member Name', 'Phone', 'Transaction Type', 'Amount', 'Payment Mode', 'Receipt No', 'Description', 'Status'];
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
        
        // Write data rows — extract only columns matching headers (RPT-3 FIX)
        $row = 2;
        if (is_array($data) || is_object($data)) {
            foreach ($data as $item) {
                $values = $this->_get_row_values($type, $item);
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
        $spec = $this->_xls_demand(['report' => $this->Report_model->get_overdue_report(), 'month' => date('Y-m-d'), 'overdue' => true]);
        $this->_render_spec($spec);
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
        
        $this->_excel_if_requested('_xls_member_summary', $data);
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
        
        $rows = array_map(function ($m) { return (array) $m; }, $this->Report_model->kyc_pending_list());
        $this->_render_spec([
            'title' => 'KYC Pending', 'icon' => 'fa-id-card', 'subtitle' => 'Active members whose KYC is not verified or incomplete',
            'kpis' => [
                ['label' => 'Members', 'value' => count($rows), 'type' => 'int', 'color' => 'warning'],
                ['label' => 'No Aadhaar no.', 'value' => count(array_filter($rows, function ($r) { return empty($r['aadhaar_number']); })), 'type' => 'int'],
                ['label' => 'No PAN', 'value' => count(array_filter($rows, function ($r) { return empty($r['pan_number']); })), 'type' => 'int'],
                ['label' => 'Docs complete, not verified', 'value' => count(array_filter($rows, function ($r) { return $r['pending_documents'] == 0; })), 'type' => 'int', 'color' => 'info'],
            ],
            'columns' => [
                ['key' => 'member_code', 'label' => 'Member', 'type' => 'code', 'link' => function ($r) { return site_url('admin/members/view/' . $r['id']); }],
                ['key' => 'member_name', 'label' => 'Name'],
                ['key' => 'phone', 'label' => 'Phone', 'type' => 'code'],
                ['key' => 'join_date', 'label' => 'Joined', 'type' => 'date'],
                ['key' => 'aadhaar_number', 'label' => 'Aadhaar', 'type' => 'code'],
                ['key' => 'pan_number', 'label' => 'PAN', 'type' => 'code'],
                ['key' => 'pending_documents', 'label' => 'Items missing', 'type' => 'int', 'total' => true],
                ['key' => 'missing', 'label' => 'Missing'],
            ],
            'rows' => $rows,
        ]);
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
        
        $this->_excel_if_requested('_xls_ageing', $data);
        $this->load_view('admin/reports/ageing', $data);
    }
    
    /**
     * Audit Log Report (RPT-4 FIX: server-side pagination + filters)
     */
    public function audit_log() {
        $data['title'] = 'Audit Trail';
        $data['page_title'] = 'Audit Trail Report';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Reports', 'url' => 'admin/reports'],
            ['title' => 'Audit Trail', 'url' => '']
        ];

        $this->load->model('Audit_model');

        $page = max(1, (int) ($this->input->get('page') ?: 1));
        $per_page = 50;

        $filters = [
            'module'    => $this->input->get('table'),
            'action'    => $this->input->get('action'),
            'from_date' => $this->input->get('from_date'),
            'to_date'   => $this->input->get('to_date')
        ];

        $result = $this->Audit_model->search_audit_logs($filters, $page, $per_page);

        $data['logs'] = $result;

        // Distinct tables/modules for filter dropdown
        $data['tables'] = $this->db->distinct()
            ->select('module')
            ->where('module IS NOT NULL', null, false)
            ->where('module !=', '')
            ->order_by('module', 'ASC')
            ->get('audit_logs')
            ->result_array();
        $data['tables'] = array_column($data['tables'], 'module');

        // Build Bootstrap 4 pagination
        $total_pages = $result['total_pages'] ?? 1;
        $current_page = $result['current_page'] ?? 1;
        $pagination = '';
        if ($total_pages > 1) {
            // Preserve existing query params
            $query_params = $this->input->get();
            $pagination = '<ul class="pagination pagination-sm m-0 justify-content-center">';
            // Previous
            if ($current_page > 1) {
                $query_params['page'] = $current_page - 1;
                $pagination .= '<li class="page-item"><a class="page-link" href="' . site_url('admin/reports/audit_log') . '?' . http_build_query($query_params) . '">&laquo;</a></li>';
            } else {
                $pagination .= '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>';
            }
            // Page numbers (show max 7 pages around current)
            $start = max(1, $current_page - 3);
            $end = min($total_pages, $current_page + 3);
            for ($p = $start; $p <= $end; $p++) {
                $query_params['page'] = $p;
                if ($p == $current_page) {
                    $pagination .= '<li class="page-item active"><span class="page-link">' . $p . '</span></li>';
                } else {
                    $pagination .= '<li class="page-item"><a class="page-link" href="' . site_url('admin/reports/audit_log') . '?' . http_build_query($query_params) . '">' . $p . '</a></li>';
                }
            }
            // Next
            if ($current_page < $total_pages) {
                $query_params['page'] = $current_page + 1;
                $pagination .= '<li class="page-item"><a class="page-link" href="' . site_url('admin/reports/audit_log') . '?' . http_build_query($query_params) . '">&raquo;</a></li>';
            } else {
                $pagination .= '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>';
            }
            $pagination .= '</ul>';
        }
        $data['pagination'] = $pagination;
        $data['total_records'] = $result['total'] ?? 0;

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
        
        $book = $this->input->get('book') === 'bank' ? 'bank' : 'cash';
        $b = $this->Report_model->cash_bank_book($book === 'bank' ? '1102' : '1101', $from_date, $to_date);
        $rows = $b['rows'];
        foreach ($rows as &$r) $r['type_label'] = ucwords(str_replace('_', ' ', $r['reference_type'] ?: $r['voucher_type']));
        unset($r);
        array_unshift($rows, ['voucher_date' => $from_date, 'narration' => 'Opening balance', 'balance' => $b['opening'], '_style' => 'group']);
        $rows[] = ['voucher_date' => $to_date, 'narration' => 'Closing balance', 'balance' => $b['closing'], '_style' => 'total'];
        $this->_render_spec([
            'title' => $book === 'bank' ? 'Bank Book' : 'Cash Book', 'icon' => 'fa-book', 'subtitle' => self::_period($from_date, $to_date),
            'filters' => [
                ['name' => 'book', 'label' => 'Book', 'type' => 'select', 'value' => $book, 'options' => ['cash' => 'Cash in hand', 'bank' => 'Bank']],
                ['name' => 'from_date', 'label' => 'From', 'type' => 'date', 'value' => $from_date],
                ['name' => 'to_date', 'label' => 'To', 'type' => 'date', 'value' => $to_date],
            ],
            'kpis' => [
                ['label' => 'Opening', 'value' => $b['opening'], 'type' => 'money'],
                ['label' => 'Receipts', 'value' => self::_sum($b['rows'], 'debit_amount'), 'type' => 'money', 'color' => 'success'],
                ['label' => 'Payments', 'value' => self::_sum($b['rows'], 'credit_amount'), 'type' => 'money', 'color' => 'danger'],
                ['label' => 'Closing', 'value' => $b['closing'], 'type' => 'money', 'color' => 'info'],
            ],
            'columns' => [
                ['key' => 'voucher_date', 'label' => 'Date', 'type' => 'date'],
                ['key' => 'voucher_number', 'label' => 'Voucher', 'type' => 'code'],
                ['key' => 'type_label', 'label' => 'Type'],
                ['key' => 'member_code', 'label' => 'Member', 'type' => 'code'],
                ['key' => 'member_name', 'label' => 'Name'],
                ['key' => 'narration', 'label' => 'Narration'],
                ['key' => 'debit_amount', 'label' => 'Receipt', 'type' => 'money', 'total' => true],
                ['key' => 'credit_amount', 'label' => 'Payment', 'type' => 'money', 'total' => true],
                ['key' => 'balance', 'label' => 'Balance', 'type' => 'money'],
            ],
            'rows' => $rows,
            'note' => 'From the general ledger. A negative balance means the opening cash / bank balance has not been entered in the books yet.',
        ]);
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
        
        $from = $this->_date_param('from_date', date('Y-04-01', strtotime(date('n') < 4 ? '-1 year' : 'now')));
        $to   = $this->_date_param('to_date', date('Y-m-d'));
        $r = $this->Report_model->bank_reconciliation_report($from, $to);
        $un = $r['unmapped'];
        foreach ($un as &$u) $u['type_label'] = $u['transaction_type'] === 'credit' ? 'Credit (in)' : 'Debit (out)';
        unset($u);
        $this->_render_spec([
            'title' => 'Bank Reconciliation', 'icon' => 'fa-balance-scale', 'subtitle' => self::_period($from, $to),
            'filters' => [
                ['name' => 'from_date', 'label' => 'From', 'type' => 'date', 'value' => $from],
                ['name' => 'to_date', 'label' => 'To', 'type' => 'date', 'value' => $to],
            ],
            'kpis' => [
                ['label' => 'Statement lines', 'value' => self::_sum($r['summary'], 'line_count'), 'type' => 'int'],
                ['label' => 'Mapped amount', 'value' => self::_sum($r['summary'], 'mapped_amount'), 'type' => 'money', 'color' => 'success'],
                ['label' => 'Not yet mapped', 'value' => self::_sum($r['summary'], 'unmapped_amount'), 'type' => 'money', 'color' => 'danger'],
                ['label' => 'Lines to map', 'value' => self::_sum($r['summary'], 'unmapped_lines'), 'type' => 'int', 'color' => 'warning'],
                ['label' => 'Bank balance in books', 'value' => $r['gl_bank'], 'type' => 'money', 'color' => 'info'],
                ['label' => 'Statement balance', 'value' => $r['statement_balance'] === null ? 'not in statement' : format_amount($r['statement_balance'])],
            ],
            'table_title' => 'Lines not yet mapped in the books',
            'columns' => [
                ['key' => 'transaction_date', 'label' => 'Date', 'type' => 'date'],
                ['key' => 'type_label', 'label' => 'Direction'],
                ['key' => 'amount', 'label' => 'Amount', 'type' => 'money', 'total' => true],
                ['key' => 'pending_amount', 'label' => 'To map', 'type' => 'money', 'total' => true],
                ['key' => 'description', 'label' => 'Description'],
                ['key' => 'reference_number', 'label' => 'Reference', 'type' => 'code'],
                ['key' => 'utr_number', 'label' => 'UTR', 'type' => 'code'],
                ['key' => 'member_code', 'label' => 'Detected member', 'type' => 'code'],
                ['key' => 'member_name', 'label' => 'Name'],
            ],
            'rows' => $un,
            'sheets' => [[
                'title' => 'By bank account',
                'columns' => [
                    ['key' => 'account_name', 'label' => 'Account'], ['key' => 'account_number', 'label' => 'Number', 'type' => 'code'],
                    ['key' => 'line_count', 'label' => 'Lines', 'type' => 'int', 'total' => true],
                    ['key' => 'credits', 'label' => 'Credits', 'type' => 'money', 'total' => true],
                    ['key' => 'debits', 'label' => 'Debits', 'type' => 'money', 'total' => true],
                    ['key' => 'mapped_amount', 'label' => 'Mapped', 'type' => 'money', 'total' => true],
                    ['key' => 'ignored_amount', 'label' => 'Ignored', 'type' => 'money', 'total' => true],
                    ['key' => 'unmapped_amount', 'label' => 'Not mapped', 'type' => 'money', 'total' => true],
                ],
                'rows' => $r['summary'],
            ]],
            'note' => 'Every statement line must be mapped (or ignored) before the bank balance in the books can agree with the statement. Map lines in Bank → Statement.',
        ]);
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
     * Send Report via Email (AJAX) — RPT-5 FIX: forwards user date filters
     */
    public function send_email() {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $report_type = $this->input->post('report_type');
        $recipients = $this->input->post('recipients');
        $additional_message = $this->input->post('additional_message');
        $from_date = $this->input->post('from_date');
        $to_date = $this->input->post('to_date');
        $month = $this->input->post('month');

        if (empty($report_type) || empty($recipients)) {
            echo json_encode(['success' => false, 'message' => 'Report type and recipients are required']);
            return;
        }

        // Get report data based on type with date filters
        $report_data = $this->_get_report_data_for_email($report_type, $from_date, $to_date, $month);

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
     * Get report data for email sending — RPT-5 FIX: accepts and forwards date filters
     *
     * @param string      $report_type Report identifier
     * @param string|null $from_date   Start date (Y-m-d)
     * @param string|null $to_date     End date (Y-m-d)
     * @param string|null $month       Month (Y-m-01) for demand/monthly-summary
     * @return mixed Report data or false
     */
    private function _get_report_data_for_email($report_type, $from_date = null, $to_date = null, $month = null) {
        // Sensible defaults for date-range reports
        $from_date = $from_date ?: $this->_get_fiscal_year_start();
        $to_date   = $to_date ?: date('Y-m-d');
        $month     = $month ?: date('Y-m-01');

        switch ($report_type) {
            // --- Date-range reports ---
            case 'collection':
                return $this->Report_model->get_collection_report($from_date, $to_date);
            case 'disbursement':
                return $this->Report_model->get_disbursement_report($from_date, $to_date);
            case 'cash-book':
                return $this->Report_model->get_cash_book($from_date, $to_date);
            case 'profit-loss':
                return $this->Ledger_model->get_profit_loss($from_date, $to_date);

            // --- Point-in-time reports (use to_date as as_on) ---
            case 'trial-balance':
                return $this->Ledger_model->get_trial_balance($to_date);
            case 'balance-sheet':
                return $this->Ledger_model->get_balance_sheet($to_date);

            // --- Month-based reports ---
            case 'demand':
                return $this->Report_model->get_demand_report($month);
            case 'monthly-summary':
                return $this->Report_model->get_monthly_summary(
                    date('Y', strtotime($month)),
                    date('m', strtotime($month))
                );

            // --- No date filters needed ---
            case 'member-summary':
                return $this->Report_model->get_member_summary_report();
            case 'kyc-pending':
                return $this->Report_model->get_kyc_pending_report();
            case 'ageing':
                return $this->Report_model->get_ageing_report();
            case 'bank-reconciliation':
                return $this->Report_model->get_bank_reconciliation();
            case 'npa':
                return $this->Report_model->get_npa_report();
            case 'guarantor':
                return $this->Report_model->get_guarantor_report();
            case 'outstanding':
                return $this->Report_model->get_outstanding_report();

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
        // Same styled layout as every other report
        $this->load->library('Excel_report');
        $ws = $index === 0 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet($index);
        $this->excel_report->write_sheet($ws, Excel_report::from_headers($title, $headers, $data), $index === 0);
        return;
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

    // =========================================================================
    //  PRIVATE HELPERS
    // =========================================================================

    /**
     * RPT-2 FIX: Get fiscal year start date from system_settings.
     * Defaults to April 1 (Indian Financial Year) if not configured.
     *
     * @return string  Y-m-d formatted date
     */
    private function _get_fiscal_year_start() {
        $row = $this->db
            ->where_in('setting_key', ['financial_year_start_month', 'fiscal_year_start_month'])
            ->get('system_settings')
            ->row();

        $month = $row ? (int) $row->setting_value : 4; // April default
        $year  = (int) date('Y');

        // If we haven't reached the fiscal start month yet, FY started last year
        if ((int) date('n') < $month) {
            $year--;
        }

        return $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01';
    }

    /**
     * RPT-3 FIX: Extract row values matching header columns for a given report type.
     * Prevents column misalignment in Excel exports.
     *
     * @param string $type  Report type identifier
     * @param object $row   Data row object
     * @return array  Ordered values matching the header columns
     */
    private function _get_row_values($type, $row) {
        switch ($type) {
            case 'outstanding':
                return [
                    $row->loan_number ?? '',
                    $row->member_code ?? '',
                    ($row->first_name ?? '') . ' ' . ($row->last_name ?? ''),
                    $row->product_name ?? '',
                    $row->principal_amount ?? 0,
                    $row->outstanding_principal ?? 0,
                    $row->outstanding_interest ?? 0,
                    $row->overdue_amount ?? 0
                ];

            case 'collection':
                return [
                    $row->payment_date ?? $row->date ?? '',
                    $row->member_code ?? '',
                    ($row->first_name ?? '') . ' ' . ($row->last_name ?? ''),
                    $row->payment_type ?? $row->type ?? '',
                    $row->total_amount ?? $row->amount ?? 0,
                    $row->payment_mode ?? '',
                    $row->reference_number ?? ''
                ];

            case 'demand':
                return [
                    $row->loan_number ?? '',
                    $row->member_code ?? '',
                    ($row->first_name ?? '') . ' ' . ($row->last_name ?? ''),
                    $row->due_date ?? '',
                    $row->emi_amount ?? 0,
                    $row->days_overdue ?? 0,
                    $row->phone ?? ''
                ];

            case 'npa':
                return [
                    $row->loan_number ?? '',
                    $row->member_code ?? '',
                    ($row->first_name ?? '') . ' ' . ($row->last_name ?? ''),
                    $row->principal_amount ?? 0,
                    $row->outstanding_amount ?? 0,
                    $row->days_overdue ?? 0,
                    $row->npa_category ?? ''
                ];

            case 'other_transactions':
                return [
                    $row->transaction_date ?? '',
                    $row->member_code ?? '',
                    ($row->first_name ?? '') . ' ' . ($row->last_name ?? ''),
                    $row->phone ?? '',
                    ucwords(str_replace('_', ' ', $row->transaction_type ?? '')),
                    $row->amount ?? 0,
                    $row->payment_mode ?? '',
                    $row->receipt_number ?? '',
                    $row->description ?? '',
                    $row->status ?? ''
                ];

            default:
                return array_values((array) $row);
        }
    }

    /** Column headers of the legacy export() types (same order as _get_row_values). */
    private function _export_headers($type, $data) {
        switch ($type) {
            case 'outstanding': return ['Loan No', 'Member Code', 'Member Name', 'Product', 'Principal', 'Outstanding Principal', 'Outstanding Interest', 'Overdue Amount'];
            case 'collection': return ['Date', 'Member Code', 'Member Name', 'Type', 'Amount', 'Payment Mode', 'Reference'];
            case 'demand': return ['Loan No', 'Member Code', 'Member Name', 'Due Date', 'EMI Amount', 'Days Overdue', 'Phone'];
            case 'npa': return ['Loan No', 'Member Code', 'Member Name', 'Principal', 'Outstanding', 'Days Overdue', 'NPA Category'];
            case 'other_transactions': return ['Date', 'Member Code', 'Member Name', 'Phone', 'Transaction Type', 'Amount', 'Payment Mode', 'Receipt No', 'Description', 'Status'];
        }
        $first = is_array($data) && $data ? reset($data) : null;
        return $first ? array_map(function ($k) { return ucwords(str_replace('_', ' ', $k)); }, array_keys((array) $first)) : ['No data'];
    }

    // =========================================================================
    //  REPORT CENTER — spec-driven reports (screen + styled Excel, same rows)
    // =========================================================================

    /** Show a report spec on the shared page, or download it when ?export=excel. */
    private function _render_spec(array $spec) {
        if ($this->input->get('export') === 'excel') {
            $this->load->library('Excel_report');
            $this->excel_report->download($spec);
        }
        $this->load_view('admin/reports/report_view', [
            'title'      => $spec['title'],
            'page_title' => $spec['title'],
            'spec'       => $spec,
            'breadcrumb' => [['title' => 'Dashboard', 'url' => 'admin/dashboard'],
                             ['title' => 'Reports', 'url' => 'admin/reports'],
                             ['title' => $spec['title'], 'url' => '']],
        ]);
    }

    /** Excel download for reports that keep their own page layout. */
    private function _excel_if_requested($builder, $data) {
        if ($this->input->get('export') !== 'excel') return;
        $this->load->library('Excel_report');
        $this->excel_report->download($this->$builder($data));
    }

    private function _date_param($key, $default) {
        $v = $this->input->get($key);
        return ($v && strtotime($v)) ? date('Y-m-d', strtotime($v)) : $default;
    }

    private static function _period($from, $to) {
        return format_date($from) . ' to ' . format_date($to);
    }

    private static function _full_name($r) {
        $r = (array) $r;
        return trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
    }

    private static function _sum($rows, $key) {
        $t = 0;
        foreach ($rows as $r) { $r = (array) $r; $t += (float) ($r[$key] ?? 0); }
        return round($t, 2);
    }

    private static function _label($v) {
        return ucwords(str_replace('_', ' ', (string) $v));
    }

    // ---------------------------------------------------------------- new reports

    /** Fine Register — every fine with status, source and settlement. */
    public function fines() {
        $from   = $this->_date_param('from_date', date('Y-01-01', strtotime('-1 year')));
        $to     = $this->_date_param('to_date', date('Y-m-d'));
        $status = (string) $this->input->get('status');
        $type   = (string) $this->input->get('type');
        $rows   = $this->Report_model->fine_register($from, $to, $status, $type);

        foreach ($rows as &$r) {
            $r['type_label']   = self::_label($r['fine_type']);
            $r['status_label'] = ucfirst($r['status']);
        }
        unset($r);
        $by = [];
        foreach ($rows as $r) {
            $k = $r['type_label'] . '|' . $r['status_label'];
            if (!isset($by[$k])) $by[$k] = ['type' => $r['type_label'], 'status' => $r['status_label'], 'count' => 0, 'amount' => 0, 'paid' => 0, 'waived' => 0, 'balance' => 0];
            $by[$k]['count']++;
            $by[$k]['amount']  += $r['fine_amount'];
            $by[$k]['paid']    += $r['paid_amount'];
            $by[$k]['waived']  += $r['waived_amount'];
            $by[$k]['balance'] += $r['balance_amount'];
        }

        $this->_render_spec([
            'title' => 'Fine Register', 'icon' => 'fa-gavel', 'subtitle' => self::_period($from, $to),
            'filters' => [
                ['name' => 'from_date', 'label' => 'From', 'type' => 'date', 'value' => $from],
                ['name' => 'to_date', 'label' => 'To', 'type' => 'date', 'value' => $to],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'value' => $status,
                 'options' => ['' => 'All', 'open' => 'Open (pending + partial)', 'pending' => 'Pending', 'partial' => 'Partial', 'paid' => 'Paid', 'waived' => 'Waived', 'cancelled' => 'Cancelled']],
                ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'value' => $type,
                 'options' => ['' => 'All', 'loan_late' => 'Loan late', 'savings_late' => 'Savings late', 'bounced_cheque' => 'Bounced cheque', 'other' => 'Other / manual']],
            ],
            'kpis' => [
                ['label' => 'Fines', 'value' => count($rows), 'type' => 'int'],
                ['label' => 'Levied', 'value' => self::_sum($rows, 'fine_amount'), 'type' => 'money'],
                ['label' => 'Collected', 'value' => self::_sum($rows, 'paid_amount'), 'type' => 'money', 'color' => 'success'],
                ['label' => 'Waived', 'value' => self::_sum($rows, 'waived_amount'), 'type' => 'money', 'color' => 'info'],
                ['label' => 'Outstanding', 'value' => self::_sum(array_filter($rows, function ($r) { return in_array($r['status'], ['pending', 'partial']); }), 'balance_amount'), 'type' => 'money', 'color' => 'danger'],
            ],
            'columns' => [
                ['key' => 'fine_code', 'label' => 'Fine #', 'type' => 'code', 'link' => function ($r) { return site_url('admin/fines/view/' . $r['id']); }],
                ['key' => 'fine_date', 'label' => 'Fine date', 'type' => 'date'],
                ['key' => 'member_code', 'label' => 'Member', 'type' => 'code'],
                ['key' => 'member_name', 'label' => 'Name'],
                ['key' => 'phone', 'label' => 'Phone', 'type' => 'code'],
                ['key' => 'type_label', 'label' => 'Type'],
                ['key' => 'reference_no', 'label' => 'Loan / Account', 'type' => 'code'],
                ['key' => 'due_date', 'label' => 'Due date', 'type' => 'date'],
                ['key' => 'days_late', 'label' => 'Days late', 'type' => 'int'],
                ['key' => 'rule_name', 'label' => 'Rule'],
                ['key' => 'fine_amount', 'label' => 'Fine', 'type' => 'money', 'total' => true],
                ['key' => 'paid_amount', 'label' => 'Paid', 'type' => 'money', 'total' => true],
                ['key' => 'waived_amount', 'label' => 'Waived', 'type' => 'money', 'total' => true],
                ['key' => 'balance_amount', 'label' => 'Balance', 'type' => 'money', 'total' => true],
                ['key' => 'status_label', 'label' => 'Status'],
                ['key' => 'payment_date', 'label' => 'Paid on', 'type' => 'date'],
                ['key' => 'remarks', 'label' => 'Remarks'],
            ],
            'rows' => $rows,
            'sheets' => [[
                'title' => 'Summary by type', 'no_total_row' => false,
                'columns' => [
                    ['key' => 'type', 'label' => 'Type'], ['key' => 'status', 'label' => 'Status'],
                    ['key' => 'count', 'label' => 'Fines', 'type' => 'int', 'total' => true],
                    ['key' => 'amount', 'label' => 'Levied', 'type' => 'money', 'total' => true],
                    ['key' => 'paid', 'label' => 'Collected', 'type' => 'money', 'total' => true],
                    ['key' => 'waived', 'label' => 'Waived', 'type' => 'money', 'total' => true],
                    ['key' => 'balance', 'label' => 'Balance', 'type' => 'money', 'total' => true],
                ],
                'rows' => array_values($by),
            ]],
        ]);
    }

    /** Member-wise dues: overdue EMIs + savings dues + open fines. */
    public function member_dues() {
        $as_on = $this->_date_param('as_on', date('Y-m-d'));
        $min   = (float) $this->input->get('min_amount');
        $rows  = array_values(array_filter($this->Report_model->member_dues($as_on), function ($r) use ($min) { return $r['total_due'] >= $min; }));
        $flagged = count(array_filter($rows, function ($r) { return $r['flag'] !== ''; }));

        $this->_render_spec([
            'title' => 'Member Dues', 'icon' => 'fa-user-clock', 'subtitle' => 'As on ' . format_date($as_on),
            'filters' => [
                ['name' => 'as_on', 'label' => 'As on', 'type' => 'date', 'value' => $as_on],
                ['name' => 'min_amount', 'label' => 'Min. total due', 'type' => 'number', 'value' => $min ?: '', 'placeholder' => '0'],
            ],
            'kpis' => [
                ['label' => 'Members with dues', 'value' => count($rows), 'type' => 'int'],
                ['label' => 'EMI overdue', 'value' => self::_sum($rows, 'emi_due'), 'type' => 'money', 'color' => 'danger'],
                ['label' => 'Savings due', 'value' => self::_sum($rows, 'savings_due'), 'type' => 'money', 'color' => 'warning'],
                ['label' => 'Fines open', 'value' => self::_sum($rows, 'fine_due'), 'type' => 'money', 'color' => 'info'],
                ['label' => 'Total due', 'value' => self::_sum($rows, 'total_due'), 'type' => 'money', 'color' => 'danger'],
                ['label' => 'Check data (no payments)', 'value' => $flagged, 'type' => 'int', 'hint' => 'Members with an active loan that has no payment recorded at all'],
            ],
            'columns' => [
                ['key' => 'member_code', 'label' => 'Member', 'type' => 'code', 'link' => function ($r) { return site_url('admin/members/view/' . $r['id']); }],
                ['key' => 'member_name', 'label' => 'Name'],
                ['key' => 'phone', 'label' => 'Phone', 'type' => 'code'],
                ['key' => 'emi_count', 'label' => 'EMIs due', 'type' => 'int', 'total' => true],
                ['key' => 'emi_due', 'label' => 'EMI amount', 'type' => 'money', 'total' => true],
                ['key' => 'sav_count', 'label' => 'Savings months', 'type' => 'int', 'total' => true],
                ['key' => 'savings_due', 'label' => 'Savings amount', 'type' => 'money', 'total' => true],
                ['key' => 'fine_due', 'label' => 'Fines', 'type' => 'money', 'total' => true],
                ['key' => 'total_due', 'label' => 'Total due', 'type' => 'money', 'total' => true],
                ['key' => 'oldest_due', 'label' => 'Oldest due', 'type' => 'date'],
                ['key' => 'days_overdue', 'label' => 'Days', 'type' => 'int'],
                ['key' => 'flag', 'label' => 'Check'],
            ],
            'rows' => $rows,
            'note' => 'EMI amount = EMI − amount already paid, for unpaid installments of active loans due on or before the date. '
                    . '"Check" marks members whose active loan has no payment recorded at all — usually a loan whose old payments were never imported.',
        ]);
    }

    /** Loan portfolio by product with portfolio-at-risk. */
    public function portfolio() {
        $rows   = $this->Report_model->loan_portfolio();
        $status = $this->Report_model->loan_status_summary();
        foreach ($status as &$s) $s['status_label'] = self::_label($s['status']);
        unset($s);
        $op = self::_sum($rows, 'outstanding_principal');

        $this->_render_spec([
            'title' => 'Loan Portfolio', 'icon' => 'fa-briefcase', 'subtitle' => 'Active loans as on ' . format_date(date('Y-m-d')),
            'kpis' => [
                ['label' => 'Active loans', 'value' => self::_sum($rows, 'loans'), 'type' => 'int'],
                ['label' => 'Disbursed', 'value' => self::_sum($rows, 'disbursed'), 'type' => 'money'],
                ['label' => 'Outstanding principal', 'value' => $op, 'type' => 'money', 'color' => 'info'],
                ['label' => 'Overdue EMI amount', 'value' => self::_sum($rows, 'overdue_amount'), 'type' => 'money', 'color' => 'danger'],
                ['label' => 'PAR 30', 'value' => $op > 0 ? self::_sum($rows, 'par_30') / $op * 100 : 0, 'type' => 'pct', 'color' => 'warning', 'hint' => 'Outstanding principal of loans more than 30 days overdue'],
                ['label' => 'PAR 90', 'value' => $op > 0 ? self::_sum($rows, 'par_90') / $op * 100 : 0, 'type' => 'pct', 'color' => 'danger'],
            ],
            'chart' => ['type' => 'bar', 'labels' => array_column($rows, 'product_name'), 'datasets' => [
                ['label' => 'Outstanding principal', 'data' => array_map('floatval', array_column($rows, 'outstanding_principal'))],
                ['label' => 'PAR 30', 'data' => array_map('floatval', array_column($rows, 'par_30'))],
                ['label' => 'Overdue EMI', 'data' => array_map('floatval', array_column($rows, 'overdue_amount'))],
            ]],
            'table_title' => 'By product',
            'columns' => [
                ['key' => 'product_name', 'label' => 'Product'],
                ['key' => 'loans', 'label' => 'Loans', 'type' => 'int', 'total' => true],
                ['key' => 'sanctioned', 'label' => 'Sanctioned', 'type' => 'money', 'total' => true],
                ['key' => 'disbursed', 'label' => 'Disbursed', 'type' => 'money', 'total' => true],
                ['key' => 'outstanding_principal', 'label' => 'Outstanding principal', 'type' => 'money', 'total' => true],
                ['key' => 'outstanding_interest', 'label' => 'Interest due (schedule)', 'type' => 'money', 'total' => true],
                ['key' => 'loans_overdue', 'label' => 'Loans overdue', 'type' => 'int', 'total' => true],
                ['key' => 'overdue_amount', 'label' => 'Overdue EMI', 'type' => 'money', 'total' => true],
                ['key' => 'par_30', 'label' => 'PAR 30 amount', 'type' => 'money', 'total' => true],
                ['key' => 'par_30_pct', 'label' => 'PAR 30 %', 'type' => 'pct'],
                ['key' => 'par_90', 'label' => 'PAR 90 amount', 'type' => 'money', 'total' => true],
                ['key' => 'par_90_pct', 'label' => 'PAR 90 %', 'type' => 'pct'],
            ],
            'rows' => $rows,
            'sheets' => [[
                'title' => 'By loan status',
                'columns' => [
                    ['key' => 'status_label', 'label' => 'Status'],
                    ['key' => 'loans', 'label' => 'Loans', 'type' => 'int', 'total' => true],
                    ['key' => 'disbursed', 'label' => 'Disbursed', 'type' => 'money', 'total' => true],
                    ['key' => 'principal_paid', 'label' => 'Principal repaid', 'type' => 'money', 'total' => true],
                    ['key' => 'interest_paid', 'label' => 'Interest received', 'type' => 'money', 'total' => true],
                    ['key' => 'outstanding_principal', 'label' => 'Outstanding principal', 'type' => 'money', 'total' => true],
                ],
                'rows' => $status,
            ]],
            'note' => 'PAR (portfolio at risk) = outstanding principal of loans whose oldest unpaid EMI is more than 30 / 90 days late, as % of all outstanding principal.',
        ]);
    }

    /** Month-wise income and expenses from the general ledger. */
    public function income() {
        $from = $this->_date_param('from_date', $this->_get_fiscal_year_start());
        $to   = $this->_date_param('to_date', date('Y-m-d'));
        $rows = $this->Report_model->income_by_month($from, $to);

        $this->_render_spec([
            'title' => 'Income Analysis', 'icon' => 'fa-chart-line', 'subtitle' => self::_period($from, $to),
            'filters' => [
                ['name' => 'from_date', 'label' => 'From', 'type' => 'date', 'value' => $from],
                ['name' => 'to_date', 'label' => 'To', 'type' => 'date', 'value' => $to],
            ],
            'kpis' => [
                ['label' => 'Interest', 'value' => self::_sum($rows, 'interest'), 'type' => 'money', 'color' => 'success'],
                ['label' => 'Processing fees', 'value' => self::_sum($rows, 'processing'), 'type' => 'money'],
                ['label' => 'Fines', 'value' => self::_sum($rows, 'fines'), 'type' => 'money'],
                ['label' => 'Membership fees', 'value' => self::_sum($rows, 'membership'), 'type' => 'money'],
                ['label' => 'Expenses + write-off', 'value' => self::_sum($rows, 'expenses') + self::_sum($rows, 'write_off'), 'type' => 'money', 'color' => 'danger'],
                ['label' => 'Net surplus', 'value' => self::_sum($rows, 'net'), 'type' => 'money', 'color' => 'info'],
            ],
            'chart' => ['type' => 'bar', 'labels' => array_column($rows, 'month'), 'datasets' => [
                ['label' => 'Interest', 'data' => array_map('floatval', array_column($rows, 'interest'))],
                ['label' => 'Fees + fines', 'data' => array_map(function ($r) { return $r['processing'] + $r['fines'] + $r['membership'] + $r['other_income']; }, $rows)],
                ['label' => 'Expenses', 'data' => array_map(function ($r) { return $r['expenses'] + $r['write_off']; }, $rows)],
            ]],
            'columns' => [
                ['key' => 'month', 'label' => 'Month'],
                ['key' => 'interest', 'label' => 'Interest', 'type' => 'money', 'total' => true],
                ['key' => 'processing', 'label' => 'Processing fee', 'type' => 'money', 'total' => true],
                ['key' => 'fines', 'label' => 'Fines', 'type' => 'money', 'total' => true],
                ['key' => 'membership', 'label' => 'Membership', 'type' => 'money', 'total' => true],
                ['key' => 'other_income', 'label' => 'Other income', 'type' => 'money', 'total' => true],
                ['key' => 'total_income', 'label' => 'Total income', 'type' => 'money', 'total' => true],
                ['key' => 'expenses', 'label' => 'Expenses', 'type' => 'money', 'total' => true],
                ['key' => 'write_off', 'label' => 'Principal written off', 'type' => 'money', 'total' => true],
                ['key' => 'net', 'label' => 'Net surplus', 'type' => 'money', 'total' => true],
            ],
            'rows' => $rows,
            'note' => 'From the general ledger (income and expense accounts) by voucher date.',
        ]);
    }

    /** Savings accounts register with dues. */
    public function savings_register() {
        $status = $this->input->get('status');
        $status = $status === null ? 'active' : (string) $status;
        $rows = $this->Report_model->savings_register($status);
        foreach ($rows as &$r) $r['status_label'] = ucfirst($r['status']);
        unset($r);

        $this->_render_spec([
            'title' => 'Savings Register', 'icon' => 'fa-piggy-bank', 'subtitle' => 'As on ' . format_date(date('Y-m-d')),
            'filters' => [
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'value' => $status,
                 'options' => ['active' => 'Active', 'matured' => 'Matured', 'closed' => 'Closed', 'suspended' => 'Suspended', '' => 'All']],
            ],
            'kpis' => [
                ['label' => 'Accounts', 'value' => count($rows), 'type' => 'int'],
                ['label' => 'Balance held', 'value' => self::_sum($rows, 'current_balance'), 'type' => 'money', 'color' => 'success'],
                ['label' => 'Total deposited', 'value' => self::_sum($rows, 'total_deposited'), 'type' => 'money'],
                ['label' => 'Withdrawn / adjusted', 'value' => self::_sum($rows, 'withdrawn'), 'type' => 'money', 'color' => 'warning'],
                ['label' => 'Monthly demand', 'value' => self::_sum($rows, 'monthly_amount'), 'type' => 'money', 'color' => 'info'],
                ['label' => 'Overdue amount', 'value' => self::_sum($rows, 'amount_due'), 'type' => 'money', 'color' => 'danger'],
            ],
            'columns' => [
                ['key' => 'account_number', 'label' => 'Account', 'type' => 'code'],
                ['key' => 'member_code', 'label' => 'Member', 'type' => 'code'],
                ['key' => 'member_name', 'label' => 'Name'],
                ['key' => 'phone', 'label' => 'Phone', 'type' => 'code'],
                ['key' => 'scheme_name', 'label' => 'Scheme'],
                ['key' => 'start_date', 'label' => 'Opened', 'type' => 'date'],
                ['key' => 'monthly_amount', 'label' => 'Monthly', 'type' => 'money', 'total' => true],
                ['key' => 'total_deposited', 'label' => 'Deposited', 'type' => 'money', 'total' => true],
                ['key' => 'withdrawn', 'label' => 'Withdrawn', 'type' => 'money', 'total' => true],
                ['key' => 'current_balance', 'label' => 'Balance', 'type' => 'money', 'total' => true],
                ['key' => 'last_deposit', 'label' => 'Last deposit', 'type' => 'date'],
                ['key' => 'months_due', 'label' => 'Months due', 'type' => 'int', 'total' => true],
                ['key' => 'amount_due', 'label' => 'Amount due', 'type' => 'money', 'total' => true],
                ['key' => 'status_label', 'label' => 'Status'],
            ],
            'rows' => $rows,
            'note' => 'Withdrawn includes automatic EMI-shortfall adjustments taken from savings under the loan rules.',
        ]);
    }

    /** Day book: real money in and out per day (cash + bank). */
    public function day_book() {
        $from = $this->_date_param('from_date', date('Y-m-01'));
        $to   = $this->_date_param('to_date', date('Y-m-d'));
        $d    = $this->Report_model->day_book($from, $to);
        $rows = $d['rows'];

        $this->_render_spec([
            'title' => 'Day Book', 'icon' => 'fa-calendar-day', 'subtitle' => self::_period($from, $to),
            'filters' => [
                ['name' => 'from_date', 'label' => 'From', 'type' => 'date', 'value' => $from],
                ['name' => 'to_date', 'label' => 'To', 'type' => 'date', 'value' => $to],
            ],
            'kpis' => [
                ['label' => 'Opening (cash + bank)', 'value' => $d['opening'], 'type' => 'money'],
                ['label' => 'Money in', 'value' => self::_sum($rows, 'total_in'), 'type' => 'money', 'color' => 'success'],
                ['label' => 'Money out', 'value' => self::_sum($rows, 'total_out'), 'type' => 'money', 'color' => 'danger'],
                ['label' => 'Net', 'value' => self::_sum($rows, 'net'), 'type' => 'money', 'color' => 'info'],
                ['label' => 'Closing (cash + bank)', 'value' => $d['closing'], 'type' => 'money'],
            ],
            'columns' => [
                ['key' => 'date', 'label' => 'Date', 'type' => 'date'],
                ['key' => 'emi', 'label' => 'EMI received', 'type' => 'money', 'total' => true],
                ['key' => 'savings', 'label' => 'Savings received', 'type' => 'money', 'total' => true],
                ['key' => 'fines', 'label' => 'Fines received', 'type' => 'money', 'total' => true],
                ['key' => 'fees', 'label' => 'Fees received', 'type' => 'money', 'total' => true],
                ['key' => 'other_in', 'label' => 'Other in', 'type' => 'money', 'total' => true],
                ['key' => 'total_in', 'label' => 'Total in', 'type' => 'money', 'total' => true],
                ['key' => 'disbursed', 'label' => 'Loans disbursed', 'type' => 'money', 'total' => true],
                ['key' => 'withdrawal', 'label' => 'Savings paid out', 'type' => 'money', 'total' => true],
                ['key' => 'expense', 'label' => 'Expenses', 'type' => 'money', 'total' => true],
                ['key' => 'other_out', 'label' => 'Other out', 'type' => 'money', 'total' => true],
                ['key' => 'total_out', 'label' => 'Total out', 'type' => 'money', 'total' => true],
                ['key' => 'cash_net', 'label' => 'Cash net', 'type' => 'money', 'total' => true],
                ['key' => 'bank_net', 'label' => 'Bank net', 'type' => 'money', 'total' => true],
                ['key' => 'closing', 'label' => 'Closing balance', 'type' => 'money'],
            ],
            'rows' => $rows,
            'note' => 'Built from the cash and bank accounts of the general ledger, so internal transfers (EMI shortfall taken from savings, excess credited to savings) are not counted as money in or out. '
                    . 'A negative balance means opening balances / capital have not been entered in the books yet.',
        ]);
    }

    /** Collection efficiency: EMI demand of each month vs what has been paid against it. */
    public function collection_efficiency() {
        $months = max(3, min(36, (int) ($this->input->get('months') ?: 12)));
        $rows = $this->Report_model->collection_efficiency($months);
        $demand = self::_sum($rows, 'demand');
        $coll   = self::_sum($rows, 'collected');

        $this->_render_spec([
            'title' => 'Collection Efficiency', 'icon' => 'fa-bullseye', 'subtitle' => 'Last ' . $months . ' months (by EMI due month)',
            'filters' => [['name' => 'months', 'label' => 'Months', 'type' => 'select', 'value' => $months,
                           'options' => [6 => '6 months', 12 => '12 months', 24 => '24 months', 36 => '36 months']]],
            'kpis' => [
                ['label' => 'EMI demand', 'value' => $demand, 'type' => 'money'],
                ['label' => 'Collected', 'value' => $coll, 'type' => 'money', 'color' => 'success'],
                ['label' => 'Pending', 'value' => $demand - $coll, 'type' => 'money', 'color' => 'danger'],
                ['label' => 'Efficiency', 'value' => $demand > 0 ? $coll / $demand * 100 : 0, 'type' => 'pct', 'color' => 'info'],
            ],
            'chart' => ['type' => 'bar', 'labels' => array_column($rows, 'month'), 'datasets' => [
                ['label' => 'Demand', 'data' => array_map('floatval', array_column($rows, 'demand'))],
                ['label' => 'Collected', 'data' => array_map('floatval', array_column($rows, 'collected'))],
            ]],
            'columns' => [
                ['key' => 'month', 'label' => 'Due month'],
                ['key' => 'emis', 'label' => 'EMIs due', 'type' => 'int', 'total' => true],
                ['key' => 'demand', 'label' => 'Demand', 'type' => 'money', 'total' => true],
                ['key' => 'collected', 'label' => 'Collected', 'type' => 'money', 'total' => true],
                ['key' => 'pending', 'label' => 'Pending', 'type' => 'money', 'total' => true],
                ['key' => 'efficiency', 'label' => 'Efficiency %', 'type' => 'pct'],
                ['key' => 'paid_emis', 'label' => 'EMIs fully paid', 'type' => 'int', 'total' => true],
                ['key' => 'on_time', 'label' => 'Paid by due date', 'type' => 'int', 'total' => true],
                ['key' => 'on_time_pct', 'label' => 'On-time %', 'type' => 'pct'],
            ],
            'rows' => $rows,
        ]);
    }

    // ---------------------------------------------------------- Excel for existing pages

    private function _xls_collection($data) {
        $r = $data['report'];
        $loan = array_map(function ($p) {
            $p = (array) $p;
            return $p + ['member_name' => self::_full_name($p), 'type_label' => self::_label($p['payment_type'] ?: 'emi'),
                         'mode_label' => self::_label($p['payment_mode']), 'receipt' => $p['receipt_number'] ?: $p['payment_code']];
        }, $r['details']['loan'] ?? []);
        $sav = array_map(function ($p) {
            $p = (array) $p;
            return $p + ['member_name' => self::_full_name($p), 'mode_label' => self::_label($p['payment_mode']), 'receipt' => $p['receipt_number'] ?: $p['transaction_code']];
        }, $r['details']['savings'] ?? []);
        $spec = [
            'title' => 'Collection Report', 'subtitle' => self::_period($data['from_date'], $data['to_date']),
            'kpis' => [
                ['label' => 'Loan collection', 'value' => self::_sum($loan, 'total_amount'), 'type' => 'money'],
                ['label' => 'Principal', 'value' => self::_sum($loan, 'principal_component'), 'type' => 'money'],
                ['label' => 'Interest', 'value' => self::_sum($loan, 'interest_component'), 'type' => 'money'],
                ['label' => 'Savings collection', 'value' => self::_sum($sav, 'amount'), 'type' => 'money'],
                ['label' => 'Grand total', 'value' => self::_sum($loan, 'total_amount') + self::_sum($sav, 'amount'), 'type' => 'money'],
            ],
            'sheet_title' => 'Loan collections',
            'columns' => [
                ['key' => 'payment_date', 'label' => 'Date', 'type' => 'date'], ['key' => 'receipt', 'label' => 'Receipt', 'type' => 'code'],
                ['key' => 'member_code', 'label' => 'Member', 'type' => 'code'], ['key' => 'member_name', 'label' => 'Name'],
                ['key' => 'loan_number', 'label' => 'Loan', 'type' => 'code'], ['key' => 'type_label', 'label' => 'Type'],
                ['key' => 'principal_component', 'label' => 'Principal', 'type' => 'money', 'total' => true],
                ['key' => 'interest_component', 'label' => 'Interest', 'type' => 'money', 'total' => true],
                ['key' => 'fine_component', 'label' => 'Fine', 'type' => 'money', 'total' => true],
                ['key' => 'total_amount', 'label' => 'Total', 'type' => 'money', 'total' => true],
                ['key' => 'mode_label', 'label' => 'Mode'], ['key' => 'reference_number', 'label' => 'Reference', 'type' => 'code'],
            ],
            'rows' => $loan,
            'sheets' => [[
                'title' => 'Savings collections',
                'columns' => [
                    ['key' => 'transaction_date', 'label' => 'Date', 'type' => 'date'], ['key' => 'receipt', 'label' => 'Receipt', 'type' => 'code'],
                    ['key' => 'member_code', 'label' => 'Member', 'type' => 'code'], ['key' => 'member_name', 'label' => 'Name'],
                    ['key' => 'account_number', 'label' => 'Account', 'type' => 'code'],
                    ['key' => 'amount', 'label' => 'Amount', 'type' => 'money', 'total' => true],
                    ['key' => 'mode_label', 'label' => 'Mode'], ['key' => 'narration', 'label' => 'Narration'],
                ],
                'rows' => $sav,
            ]],
        ];
        if (!$loan && $sav) { $first = $spec['sheets'][0]; unset($spec['sheets']); $spec = array_merge($spec, $first, ['title' => 'Collection Report', 'sheet_title' => 'Savings collections']); }
        return $spec;
    }

    private function _loan_rows($rows) {
        return array_map(function ($l) {
            $l = (array) $l;
            return $l + ['member_name' => self::_full_name($l), 'status_label' => self::_label($l['status'] ?? ''),
                         'disbursed' => $l['disbursed_amount'] ?? $l['principal_amount'] ?? 0];
        }, $rows);
    }

    private function _xls_disbursement($data) {
        $rows = $this->_loan_rows($data['report']['details'] ?? []);
        return [
            'title' => 'Disbursement Report', 'subtitle' => self::_period($data['from_date'], $data['to_date']),
            'kpis' => [
                ['label' => 'Loans', 'value' => count($rows), 'type' => 'int'],
                ['label' => 'Money disbursed in period', 'value' => $data['report']['summary']->total_disbursed ?? 0, 'type' => 'money'],
                ['label' => 'Processing fees', 'value' => $data['report']['summary']->total_fees ?? 0, 'type' => 'money'],
            ],
            'columns' => [
                ['key' => 'disbursement_date', 'label' => 'Date', 'type' => 'date'], ['key' => 'loan_number', 'label' => 'Loan', 'type' => 'code'],
                ['key' => 'member_code', 'label' => 'Member', 'type' => 'code'], ['key' => 'member_name', 'label' => 'Name'],
                ['key' => 'product_name', 'label' => 'Product'],
                ['key' => 'principal_amount', 'label' => 'Sanctioned', 'type' => 'money', 'total' => true],
                ['key' => 'disbursed', 'label' => 'Disbursed so far', 'type' => 'money', 'total' => true],
                ['key' => 'processing_fee', 'label' => 'Processing fee', 'type' => 'money', 'total' => true],
                ['key' => 'net_disbursement', 'label' => 'Net paid', 'type' => 'money', 'total' => true],
                ['key' => 'interest_rate', 'label' => 'Rate %', 'type' => 'number'], ['key' => 'tenure_months', 'label' => 'Tenure', 'type' => 'int'],
                ['key' => 'emi_amount', 'label' => 'EMI', 'type' => 'money'], ['key' => 'status_label', 'label' => 'Status'],
            ],
            'rows' => $rows,
            'sheets' => [[
                'title' => 'By product',
                'columns' => [['key' => 'product_name', 'label' => 'Product'], ['key' => 'count', 'label' => 'Loans', 'type' => 'int', 'total' => true],
                              ['key' => 'total_disbursed', 'label' => 'Disbursed', 'type' => 'money', 'total' => true]],
                'rows' => array_map(function ($p) { return (array) $p; }, $data['report']['by_product'] ?? []),
            ]],
        ];
    }

    private function _xls_outstanding($data) {
        $rows = $this->_loan_rows($data['report']);
        return [
            'title' => 'Outstanding Loans', 'subtitle' => 'As on ' . format_date(date('Y-m-d')), 'icon' => 'fa-hand-holding-usd',
            'kpis' => [
                ['label' => 'Loans', 'value' => count($rows), 'type' => 'int'],
                ['label' => 'Outstanding principal', 'value' => self::_sum($rows, 'outstanding_principal'), 'type' => 'money'],
                ['label' => 'Outstanding interest', 'value' => self::_sum($rows, 'outstanding_interest'), 'type' => 'money'],
                ['label' => 'Overdue EMI', 'value' => self::_sum($rows, 'overdue_amount'), 'type' => 'money'],
            ],
            'columns' => [
                ['key' => 'loan_number', 'label' => 'Loan', 'type' => 'code'], ['key' => 'member_code', 'label' => 'Member', 'type' => 'code'],
                ['key' => 'member_name', 'label' => 'Name'], ['key' => 'phone', 'label' => 'Phone', 'type' => 'code'], ['key' => 'product_name', 'label' => 'Product'],
                ['key' => 'disbursement_date', 'label' => 'Disbursed on', 'type' => 'date'],
                ['key' => 'disbursed', 'label' => 'Disbursed', 'type' => 'money', 'total' => true],
                ['key' => 'emi_amount', 'label' => 'EMI', 'type' => 'money'],
                ['key' => 'outstanding_principal', 'label' => 'Outstanding principal', 'type' => 'money', 'total' => true],
                ['key' => 'outstanding_interest', 'label' => 'Outstanding interest', 'type' => 'money', 'total' => true],
                ['key' => 'overdue_count', 'label' => 'EMIs overdue', 'type' => 'int', 'total' => true],
                ['key' => 'overdue_amount', 'label' => 'Overdue amount', 'type' => 'money', 'total' => true],
                ['key' => 'last_emi_date', 'label' => 'Last EMI', 'type' => 'date'],
            ],
            'rows' => $rows,
        ];
    }

    private function _xls_npa($data) {
        $rows = $this->_loan_rows($data['report']);
        foreach ($rows as &$r) {
            $d = (int) ($r['days_overdue'] ?? 0);
            $r['category'] = $d > 365 ? 'Doubtful / Loss' : ($d > 90 ? 'Sub-standard' : 'SMA');
        }
        unset($r);
        return [
            'title' => 'NPA Report', 'subtitle' => 'Overdue more than ' . (int) $data['days'] . ' days, as on ' . format_date(date('Y-m-d')),
            'kpis' => [
                ['label' => 'NPA loans', 'value' => count($rows), 'type' => 'int'],
                ['label' => 'Outstanding principal', 'value' => self::_sum($rows, 'outstanding_principal'), 'type' => 'money'],
                ['label' => 'Overdue amount', 'value' => self::_sum($rows, 'total_overdue'), 'type' => 'money'],
            ],
            'columns' => [
                ['key' => 'loan_number', 'label' => 'Loan', 'type' => 'code'], ['key' => 'member_code', 'label' => 'Member', 'type' => 'code'],
                ['key' => 'member_name', 'label' => 'Name'], ['key' => 'phone', 'label' => 'Phone', 'type' => 'code'], ['key' => 'product_name', 'label' => 'Product'],
                ['key' => 'disbursed', 'label' => 'Disbursed', 'type' => 'money', 'total' => true],
                ['key' => 'outstanding_principal', 'label' => 'Outstanding principal', 'type' => 'money', 'total' => true],
                ['key' => 'first_overdue_date', 'label' => 'Overdue since', 'type' => 'date'],
                ['key' => 'days_overdue', 'label' => 'Days overdue', 'type' => 'int'],
                ['key' => 'total_overdue', 'label' => 'Overdue amount', 'type' => 'money', 'total' => true],
                ['key' => 'category', 'label' => 'Category'],
            ],
            'rows' => $rows,
        ];
    }

    private function _xls_demand($data) {
        $r = $data['report'];
        $loans = array_map(function ($i) { $i = (array) $i; return $i + ['member_name' => self::_full_name($i), 'due' => (float) $i['emi_amount'] - (float) $i['total_paid'], 'status_label' => self::_label($i['status'])]; }, $r['loans'] ?? []);
        $sav   = array_map(function ($i) { $i = (array) $i; return $i + ['member_name' => self::_full_name($i), 'due' => (float) $i['due_amount'] - (float) $i['paid_amount'], 'status_label' => self::_label($i['status'])]; }, $r['savings'] ?? []);
        $od = !empty($data['overdue']);
        $spec = [
            'title' => $od ? 'Overdue Report' : 'Demand Report',
            'subtitle' => $od ? 'Unpaid and past due as on ' . format_date(date('Y-m-d')) : date('F Y', strtotime($data['month'])),
            'icon' => $od ? 'fa-exclamation-circle' : 'fa-calendar-check',
            'kpis' => [
                ['label' => $od ? 'EMI overdue' : 'EMI demand', 'value' => $r['summary']['loan_due'] ?? 0, 'type' => 'money', 'color' => $od ? 'danger' : ''],
                ['label' => 'Loan members', 'value' => $r['summary']['loan_members'] ?? 0, 'type' => 'int'],
                ['label' => $od ? 'Savings overdue' : 'Savings demand', 'value' => $r['summary']['savings_due'] ?? 0, 'type' => 'money', 'color' => $od ? 'warning' : ''],
                ['label' => 'Savings members', 'value' => $r['summary']['savings_members'] ?? 0, 'type' => 'int'],
            ],
            'sheet_title' => 'Loan EMIs',
            'columns' => [
                ['key' => 'due_date', 'label' => 'Due date', 'type' => 'date'], ['key' => 'loan_number', 'label' => 'Loan', 'type' => 'code'],
                ['key' => 'installment_number', 'label' => 'EMI #', 'type' => 'int'], ['key' => 'member_code', 'label' => 'Member', 'type' => 'code'],
                ['key' => 'member_name', 'label' => 'Name'], ['key' => 'phone', 'label' => 'Phone', 'type' => 'code'],
                ['key' => 'emi_amount', 'label' => 'EMI', 'type' => 'money', 'total' => true], ['key' => 'total_paid', 'label' => 'Paid', 'type' => 'money', 'total' => true],
                ['key' => 'due', 'label' => 'Due', 'type' => 'money', 'total' => true], ['key' => 'status_label', 'label' => 'Status'],
            ],
            'rows' => $loans,
            'sheets' => [[
                'title' => 'Savings dues',
                'columns' => [
                    ['key' => 'due_date', 'label' => 'Due date', 'type' => 'date'], ['key' => 'account_number', 'label' => 'Account', 'type' => 'code'],
                    ['key' => 'member_code', 'label' => 'Member', 'type' => 'code'], ['key' => 'member_name', 'label' => 'Name'], ['key' => 'phone', 'label' => 'Phone', 'type' => 'code'],
                    ['key' => 'due_amount', 'label' => 'Demand', 'type' => 'money', 'total' => true], ['key' => 'paid_amount', 'label' => 'Paid', 'type' => 'money', 'total' => true],
                    ['key' => 'due', 'label' => 'Due', 'type' => 'money', 'total' => true], ['key' => 'status_label', 'label' => 'Status'],
                ],
                'rows' => $sav,
            ]],
        ];
        if ($od) {   // days late after the due date column
            $days = ['key' => 'days_late', 'label' => 'Days late', 'type' => 'int'];
            array_splice($spec['columns'], 1, 0, [$days]);
            array_splice($spec['sheets'][0]['columns'], 1, 0, [$days]);
        }
        return $spec;
    }

    private function _xls_guarantor($data) {
        $rows = array_map(function ($g) {
            $g = (array) $g;
            $risk = (int) $g['overdue_loans'] > 0 ? 'High' : ((float) $g['active_exposure'] > 500000 ? 'Medium' : 'Low');
            return $g + ['member_name' => self::_full_name($g), 'risk' => $risk];
        }, $data['report'] ?? []);
        return [
            'title' => 'Guarantor Exposure', 'subtitle' => 'As on ' . format_date(date('Y-m-d')), 'icon' => 'fa-user-shield',
            'kpis' => [
                ['label' => 'Guarantors', 'value' => count($rows), 'type' => 'int'],
                ['label' => 'Guarantees', 'value' => self::_sum($rows, 'guarantee_count'), 'type' => 'int'],
                ['label' => 'Guaranteed amount', 'value' => self::_sum($rows, 'total_exposure'), 'type' => 'money'],
                ['label' => 'Outstanding on active loans', 'value' => self::_sum($rows, 'active_exposure'), 'type' => 'money', 'color' => 'info'],
                ['label' => 'High risk (loan overdue)', 'value' => count(array_filter($rows, function ($r) { return $r['risk'] === 'High'; })), 'type' => 'int', 'color' => 'danger'],
            ],
            'columns' => [
                ['key' => 'member_code', 'label' => 'Member', 'type' => 'code', 'link' => function ($r) { return site_url('admin/members/view/' . $r['guarantor_id']); }],
                ['key' => 'member_name', 'label' => 'Guarantor'], ['key' => 'phone', 'label' => 'Phone', 'type' => 'code'],
                ['key' => 'guarantee_count', 'label' => 'Guarantees', 'type' => 'int', 'total' => true],
                ['key' => 'consent_pending', 'label' => 'Consent pending', 'type' => 'int', 'total' => true],
                ['key' => 'total_exposure', 'label' => 'Guaranteed amount', 'type' => 'money', 'total' => true],
                ['key' => 'active_loans', 'label' => 'Active loans', 'type' => 'int', 'total' => true],
                ['key' => 'active_exposure', 'label' => 'Outstanding (active loans)', 'type' => 'money', 'total' => true],
                ['key' => 'overdue_loans', 'label' => 'Loans overdue', 'type' => 'int', 'total' => true],
                ['key' => 'risk', 'label' => 'Risk'], ['key' => 'loans', 'label' => 'Loans / applications'],
            ],
            'rows' => $rows,
            'note' => 'Accepted or pending guarantees that are not released, on active loans or on applications still in progress. Risk is High when a guaranteed loan has an overdue EMI, Medium above ₹5,00,000 outstanding.',
        ];
    }

    private function _xls_trial_balance($data) {
        $rows = [];
        foreach ($data['trial_balance'] as $a) {
            $bal = (float) $a->total_debit - (float) $a->total_credit;
            if (abs($bal) < 0.005 && (float) $a->total_debit == 0) continue;
            $rows[] = ['code' => $a->account_code, 'name' => $a->account_name, 'type' => ucfirst($a->account_type),
                       'total_debit' => $a->total_debit, 'total_credit' => $a->total_credit,
                       'dr' => $bal > 0 ? $bal : 0, 'cr' => $bal < 0 ? -$bal : 0];
        }
        return [
            'title' => 'Trial Balance', 'subtitle' => 'As on ' . format_date($data['as_on']),
            'kpis' => [['label' => 'Debit balances', 'value' => $data['totals']['debit'], 'type' => 'money'],
                       ['label' => 'Credit balances', 'value' => $data['totals']['credit'], 'type' => 'money'],
                       ['label' => 'Difference', 'value' => $data['totals']['debit'] - $data['totals']['credit'], 'type' => 'money']],
            'columns' => [
                ['key' => 'code', 'label' => 'Code', 'type' => 'code'], ['key' => 'name', 'label' => 'Account'], ['key' => 'type', 'label' => 'Type'],
                ['key' => 'total_debit', 'label' => 'Total debit', 'type' => 'money', 'total' => true],
                ['key' => 'total_credit', 'label' => 'Total credit', 'type' => 'money', 'total' => true],
                ['key' => 'dr', 'label' => 'Debit balance', 'type' => 'money', 'total' => true],
                ['key' => 'cr', 'label' => 'Credit balance', 'type' => 'money', 'total' => true],
            ],
            'rows' => $rows,
        ];
    }

    private function _xls_profit_loss($data) {
        $r = $data['report'];
        $rows = [['name' => 'INCOME', 'amount' => '', '_style' => 'group']];
        foreach ($r['income'] ?? [] as $i) $rows[] = ['name' => $i->account_name, 'amount' => $i->amount];
        $rows[] = ['name' => 'Total income', 'amount' => $r['total_income'] ?? 0, '_style' => 'subtotal'];
        $rows[] = ['name' => 'EXPENSES', 'amount' => '', '_style' => 'group'];
        foreach ($r['expenses'] ?? [] as $e) $rows[] = ['name' => $e->account_name, 'amount' => $e->amount];
        $rows[] = ['name' => 'Total expenses', 'amount' => $r['total_expenses'] ?? 0, '_style' => 'subtotal'];
        $rows[] = ['name' => ($r['net_profit'] ?? 0) >= 0 ? 'NET SURPLUS' : 'NET DEFICIT', 'amount' => $r['net_profit'] ?? 0, '_style' => 'total'];
        return ['title' => 'Profit & Loss', 'subtitle' => self::_period($data['from_date'], $data['to_date']),
                'columns' => [['key' => 'name', 'label' => 'Particulars', 'width' => 45], ['key' => 'amount', 'label' => 'Amount', 'type' => 'money', 'width' => 20]],
                'rows' => $rows];
    }

    private function _xls_balance_sheet($data) {
        $r = $data['report'];
        $rows = [];
        foreach (['liabilities' => 'LIABILITIES', 'equity' => 'CAPITAL & RESERVES', 'assets' => 'ASSETS'] as $k => $label) {
            $rows[] = ['name' => $label, 'amount' => '', '_style' => 'group'];
            foreach ($r[$k] ?? [] as $a) $rows[] = ['name' => $a->account_name, 'amount' => $a->amount ?? $a->balance ?? 0];
            $rows[] = ['name' => 'Total ' . strtolower($label), 'amount' => $r['total_' . $k] ?? 0, '_style' => 'subtotal'];
        }
        $rows[] = ['name' => 'Difference (assets − liabilities − capital)', 'amount' => $r['difference'] ?? 0, '_style' => 'total'];
        return ['title' => 'Balance Sheet', 'subtitle' => 'As on ' . format_date($data['as_on']),
                'columns' => [['key' => 'name', 'label' => 'Particulars', 'width' => 50], ['key' => 'amount', 'label' => 'Amount', 'type' => 'money', 'width' => 20]],
                'rows' => $rows];
    }

    private function _xls_general_ledger($data) {
        $rows = array_map(function ($e) {
            $e = (array) $e;
            return $e + ['member_name' => self::_full_name($e), 'type_label' => self::_label($e['voucher_type'] ?? '')];
        }, $data['entries'] ?? []);
        $f = $data['filters'];
        return [
            'title' => 'General Ledger', 'subtitle' => self::_period($f['from_date'], $f['to_date']),
            'columns' => [
                ['key' => 'voucher_date', 'label' => 'Date', 'type' => 'date'], ['key' => 'voucher_number', 'label' => 'Voucher', 'type' => 'code'],
                ['key' => 'type_label', 'label' => 'Type'], ['key' => 'account_code', 'label' => 'Code', 'type' => 'code'], ['key' => 'account_name', 'label' => 'Account'],
                ['key' => 'member_code', 'label' => 'Member', 'type' => 'code'], ['key' => 'member_name', 'label' => 'Name'], ['key' => 'narration', 'label' => 'Narration'],
                ['key' => 'debit_amount', 'label' => 'Debit', 'type' => 'money', 'total' => true], ['key' => 'credit_amount', 'label' => 'Credit', 'type' => 'money', 'total' => true],
            ],
            'rows' => $rows,
        ];
    }

    private function _xls_account_statement($data) {
        $st = $data['statement'] ?? ['opening_balance' => 0, 'transactions' => []];
        $acc = $data['account'] ?? null;
        $bal = (float) ($st['opening_balance'] ?? 0);
        $rows = [['date' => $data['from_date'], 'voucher' => '', 'narration' => 'Opening balance', 'debit' => '', 'credit' => '', 'balance' => $bal, '_style' => 'group']];
        foreach ($st['transactions'] ?? [] as $t) {
            $t = (array) $t;
            $dr = (float) ($t['debit_amount'] ?? $t['debit'] ?? 0); $cr = (float) ($t['credit_amount'] ?? $t['credit'] ?? 0);
            $bal += $dr - $cr;
            $rows[] = ['date' => $t['transaction_date'] ?? $t['voucher_date'] ?? '', 'voucher' => $t['voucher_number'] ?? '', 'narration' => $t['narration'] ?? '',
                       'debit' => $dr, 'credit' => $cr, 'balance' => round($bal, 2)];
        }
        $rows[] = ['date' => $data['to_date'], 'voucher' => '', 'narration' => 'Closing balance', 'debit' => '', 'credit' => '', 'balance' => round($bal, 2), '_style' => 'total'];
        return ['title' => 'Account Statement' . ($acc ? ' — ' . $acc->account_code . ' ' . $acc->account_name : ''),
                'subtitle' => self::_period($data['from_date'], $data['to_date']),
                'columns' => [['key' => 'date', 'label' => 'Date', 'type' => 'date'], ['key' => 'voucher', 'label' => 'Voucher', 'type' => 'code'],
                              ['key' => 'narration', 'label' => 'Narration', 'width' => 50], ['key' => 'debit', 'label' => 'Debit', 'type' => 'money', 'total' => true],
                              ['key' => 'credit', 'label' => 'Credit', 'type' => 'money', 'total' => true], ['key' => 'balance', 'label' => 'Balance', 'type' => 'money']],
                'rows' => $rows];
    }

    private function _xls_member_statement($data) {
        $s = $data['statement'] ?? null;
        $m = $s['member'] ?? null;
        $name = $m ? trim($m->member_code . ' — ' . $m->first_name . ' ' . $m->last_name) : 'Member';
        $map = function ($rows) { return array_map(function ($r) { $r = (array) $r; return $r + ['type_label' => self::_label($r['transaction_type'] ?? $r['fine_type'] ?? $r['status'] ?? '')]; }, $rows ?: []); };
        return [
            'title' => 'Member Statement — ' . $name,
            'subtitle' => ($data['from_date'] || $data['to_date']) ? self::_period($data['from_date'] ?: '2000-01-01', $data['to_date'] ?: date('Y-m-d')) : 'All dates',
            'sheet_title' => 'Ledger',
            'columns' => [['key' => 'transaction_date', 'label' => 'Date', 'type' => 'date'], ['key' => 'type_label', 'label' => 'Type'], ['key' => 'narration', 'label' => 'Narration', 'width' => 45],
                          ['key' => 'debit_amount', 'label' => 'Debit (paid to member)', 'type' => 'money', 'total' => true],
                          ['key' => 'credit_amount', 'label' => 'Credit (received)', 'type' => 'money', 'total' => true],
                          ['key' => 'balance_after', 'label' => 'Balance (+Cr / −Dr)', 'type' => 'money']],
            'rows' => $map($s['ledger'] ?? []),
            'sheets' => [
                ['title' => 'Savings', 'columns' => [['key' => 'transaction_date', 'label' => 'Date', 'type' => 'date'], ['key' => 'account_number', 'label' => 'Account', 'type' => 'code'],
                    ['key' => 'type_label', 'label' => 'Type'], ['key' => 'amount', 'label' => 'Amount', 'type' => 'money', 'total' => true],
                    ['key' => 'balance_after', 'label' => 'Balance', 'type' => 'money'], ['key' => 'narration', 'label' => 'Narration']], 'rows' => $map($s['savings'] ?? [])],
                ['title' => 'Loans', 'columns' => [['key' => 'loan_number', 'label' => 'Loan', 'type' => 'code'], ['key' => 'disbursement_date', 'label' => 'Disbursed', 'type' => 'date'],
                    ['key' => 'principal_amount', 'label' => 'Principal', 'type' => 'money', 'total' => true], ['key' => 'emi_amount', 'label' => 'EMI', 'type' => 'money'],
                    ['key' => 'outstanding_principal', 'label' => 'Outstanding', 'type' => 'money', 'total' => true], ['key' => 'status', 'label' => 'Status']], 'rows' => $map($s['loans'] ?? [])],
                ['title' => 'Fines', 'columns' => [['key' => 'fine_code', 'label' => 'Fine #', 'type' => 'code'], ['key' => 'fine_date', 'label' => 'Date', 'type' => 'date'],
                    ['key' => 'type_label', 'label' => 'Type'], ['key' => 'fine_amount', 'label' => 'Fine', 'type' => 'money', 'total' => true],
                    ['key' => 'paid_amount', 'label' => 'Paid', 'type' => 'money', 'total' => true], ['key' => 'balance_amount', 'label' => 'Balance', 'type' => 'money', 'total' => true],
                    ['key' => 'status', 'label' => 'Status']], 'rows' => $map($s['fines'] ?? [])],
            ],
        ];
    }

    private function _xls_monthly_summary($data) {
        $s = $data['summary'];
        $v = function ($k) use ($s) { return (float) ($s[$k] ?? 0); };
        $rows = [
            ['metric' => 'MEMBERS', '_style' => 'group'],
            ['metric' => 'New members', 'count' => $v('new_members')],
            ['metric' => 'New savings accounts', 'count' => $v('new_savings_accounts')],
            ['metric' => 'SAVINGS', '_style' => 'group'],
            ['metric' => 'Savings deposits collected', 'amount' => $v('savings_collected')],
            ['metric' => 'Savings withdrawn', 'amount' => $v('savings_withdrawn')],
            ['metric' => 'Net savings', 'amount' => $v('savings_collected') - $v('savings_withdrawn'), '_style' => 'subtotal'],
            ['metric' => 'LOANS', '_style' => 'group'],
            ['metric' => 'Loan applications received', 'count' => $v('loan_applications')],
            ['metric' => 'Loans disbursed', 'count' => $v('loans_disbursed_count'), 'amount' => $v('loans_disbursed_amount')],
            ['metric' => 'Loan repayments collected', 'count' => $v('loan_payments_count'), 'amount' => $v('loans_collected')],
            ['metric' => '   of which principal', 'amount' => $v('principal_collected')],
            ['metric' => '   of which interest', 'amount' => $v('interest_collected')],
            ['metric' => 'FINES & OTHER', '_style' => 'group'],
            ['metric' => 'Fines levied', 'amount' => $v('fines_applied')],
            ['metric' => 'Fines collected', 'amount' => $v('fines_collected')],
            ['metric' => 'Fees & other receipts', 'amount' => $v('other_income')],
            ['metric' => 'Total collection (savings + loan + fines + fees)', 'amount' => $v('savings_collected') + $v('loans_collected') + $v('fines_collected') + $v('other_income'), '_style' => 'total'],
        ];
        return ['title' => 'Monthly Summary', 'subtitle' => date('F Y', mktime(0, 0, 0, (int) $data['month'], 1, (int) $data['year'])), 'icon' => 'fa-calendar-alt',
                'kpis' => [
                    ['label' => 'Total collection', 'value' => $rows[count($rows) - 1]['amount'], 'type' => 'money', 'color' => 'success'],
                    ['label' => 'Loans disbursed', 'value' => $v('loans_disbursed_amount'), 'type' => 'money', 'color' => 'info'],
                    ['label' => 'Interest earned', 'value' => $v('interest_collected'), 'type' => 'money'],
                    ['label' => 'Net savings', 'value' => $v('savings_collected') - $v('savings_withdrawn'), 'type' => 'money'],
                    ['label' => 'New members', 'value' => $v('new_members'), 'type' => 'int'],
                ],
                'columns' => [['key' => 'metric', 'label' => 'Particulars', 'width' => 45], ['key' => 'count', 'label' => 'Count', 'type' => 'int', 'width' => 12],
                              ['key' => 'amount', 'label' => 'Amount', 'type' => 'money', 'width' => 20]],
                'rows' => $rows,
                'note' => 'Figures are by transaction / payment date; reversed entries are excluded.'];
    }

    private function _xls_member_summary($data) {
        $rows = array_map(function ($m) { $m = (array) $m; return $m + ['member_name' => self::_full_name($m), 'status_label' => ucfirst($m['status'] ?? '')]; }, $data['report'] ?? []);
        return ['title' => 'Member Summary', 'subtitle' => 'As on ' . format_date(date('Y-m-d')),
                'columns' => [['key' => 'member_code', 'label' => 'Member', 'type' => 'code'], ['key' => 'member_name', 'label' => 'Name'], ['key' => 'phone', 'label' => 'Phone', 'type' => 'code'],
                              ['key' => 'join_date', 'label' => 'Joined', 'type' => 'date'], ['key' => 'savings_accounts', 'label' => 'Savings a/c', 'type' => 'int', 'total' => true],
                              ['key' => 'total_savings', 'label' => 'Savings balance', 'type' => 'money', 'total' => true], ['key' => 'total_loans', 'label' => 'Loans', 'type' => 'int', 'total' => true],
                              ['key' => 'outstanding_loans', 'label' => 'Loan outstanding', 'type' => 'money', 'total' => true], ['key' => 'pending_fines', 'label' => 'Fines due', 'type' => 'money', 'total' => true],
                              ['key' => 'status_label', 'label' => 'Status']],
                'rows' => $rows];
    }

    private function _xls_ageing($data) {
        $rows = $this->_loan_rows($data['report'] ?? []);
        $buckets = [];
        foreach ($rows as $r) {
            $b = $r['ageing_bucket'];
            if (!isset($buckets[$b])) $buckets[$b] = ['bucket' => $b, 'loans' => 0, 'overdue' => 0, 'outstanding' => 0];
            $buckets[$b]['loans']++; $buckets[$b]['overdue'] += $r['overdue_amount']; $buckets[$b]['outstanding'] += $r['outstanding_principal'];
        }
        ksort($buckets);
        return ['title' => 'Ageing Analysis', 'subtitle' => 'As on ' . format_date(date('Y-m-d')),
                'columns' => [['key' => 'loan_number', 'label' => 'Loan', 'type' => 'code'], ['key' => 'member_code', 'label' => 'Member', 'type' => 'code'], ['key' => 'member_name', 'label' => 'Name'],
                              ['key' => 'product_name', 'label' => 'Product'], ['key' => 'disbursed', 'label' => 'Disbursed', 'type' => 'money', 'total' => true],
                              ['key' => 'outstanding_principal', 'label' => 'Outstanding', 'type' => 'money', 'total' => true], ['key' => 'days_overdue', 'label' => 'Days overdue', 'type' => 'int'],
                              ['key' => 'overdue_amount', 'label' => 'Overdue amount', 'type' => 'money', 'total' => true], ['key' => 'ageing_bucket', 'label' => 'Bucket']],
                'rows' => $rows,
                'sheets' => [['title' => 'By bucket', 'columns' => [['key' => 'bucket', 'label' => 'Bucket'], ['key' => 'loans', 'label' => 'Loans', 'type' => 'int', 'total' => true],
                              ['key' => 'overdue', 'label' => 'Overdue amount', 'type' => 'money', 'total' => true], ['key' => 'outstanding', 'label' => 'Outstanding', 'type' => 'money', 'total' => true]],
                              'rows' => array_values($buckets)]]];
    }

    private function _xls_other_transactions($data) {
        $rows = array_map(function ($t) { $t = (array) $t; return $t + ['member_name' => self::_full_name($t), 'type_label' => self::_label($t['transaction_type']), 'mode_label' => self::_label($t['payment_mode'])]; }, $data['transactions'] ?? []);
        return ['title' => 'Other Transactions', 'subtitle' => self::_period($data['from_date'], $data['to_date']),
                'columns' => [['key' => 'transaction_date', 'label' => 'Date', 'type' => 'date'], ['key' => 'member_code', 'label' => 'Member', 'type' => 'code'], ['key' => 'member_name', 'label' => 'Name'],
                              ['key' => 'phone', 'label' => 'Phone', 'type' => 'code'], ['key' => 'type_label', 'label' => 'Type'], ['key' => 'amount', 'label' => 'Amount', 'type' => 'money', 'total' => true],
                              ['key' => 'mode_label', 'label' => 'Mode'], ['key' => 'receipt_number', 'label' => 'Receipt', 'type' => 'code'], ['key' => 'description', 'label' => 'Description'],
                              ['key' => 'status', 'label' => 'Status']],
                'rows' => $rows];
    }

    // =========================================================================
    //  PROCESSING FEE CHECK — every loan's fee against rule / record / ledger
    // =========================================================================

    private function _pf_can_fix() {
        return in_array($this->admin_data->role ?? '', ['super_admin', 'admin'], true);
    }

    public function processing_fees() {
        $status = (string) $this->input->get('status');
        $issue  = (string) $this->input->get('issue');
        $all    = $this->Report_model->processing_fee_check($status);
        $orph   = $this->Report_model->processing_fee_orphans();
        $rows   = $issue === '' ? $all : array_values(array_filter($all, function ($r) use ($issue) {
            return $issue === 'ok' ? !$r['issues'] : in_array($issue, $r['issues'], true);
        }));
        $count = function ($code) use ($all) { return count(array_filter($all, function ($r) use ($code) { return in_array($code, $r['issues'], true); })); };
        $can = $this->_pf_can_fix();
        $csrf = form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash());
        $back = form_hidden('back', $this->input->server('QUERY_STRING') ?: '');

        // loans of each member that have a fee but no fee entry (targets for relinking orphan entries)
        $free = [];
        foreach ($all as $r) if (in_array('no_record', $r['issues'], true)) $free[$r['member_id']][] = $r;

        foreach ($rows as &$r) {
            $r['status_label'] = ucfirst($r['status']);
            $r['rule'] = $r['processing_fee_type'] === 'fixed' ? '₹' . number_format((float) $r['processing_fee_value'], 2) : (float) $r['processing_fee_value'] . '%';
            $act = '<a class="btn btn-xs btn-outline-secondary" href="' . site_url('admin/loans/view/' . $r['id']) . '" target="_blank">Loan</a>';
            if ($can && in_array('no_record', $r['issues'], true)) {
                $act .= ' <form method="post" action="' . site_url('admin/reports/pf_create_records') . '" class="d-inline">' . $csrf . $back
                      . '<input type="hidden" name="ids[]" value="' . $r['id'] . '"><button class="btn btn-xs btn-success" title="Add the fee entry (fee was deducted from the disbursement)">Create record</button></form>';
            }
            if ($can && in_array('not_charged', $r['issues'], true)) {
                $act .= ' <button type="button" class="btn btn-xs btn-warning pf-charge" data-id="' . $r['id'] . '" data-loan="' . html_escape($r['loan_number'])
                      . '" data-fee="' . $r['rule_fee'] . '" data-date="' . html_escape(date('Y-m-d')) . '">Charge fee</button>';
            }
            $r['actions'] = $act;
        }
        unset($r);

        $orows = [];
        foreach ($orph as $o) {
            $act = '';
            if ($can) {
                $opts = '';
                foreach ($free[$o['member_id']] ?? [] as $l) {
                    $opts .= '<option value="' . $l['id'] . '"' . (abs((float) $l['processing_fee'] - (float) $o['amount']) < 1 ? ' selected' : '') . '>'
                           . html_escape($l['loan_number'] . ' — fee ₹' . number_format((float) $l['processing_fee'], 2)) . '</option>';
                }
                $form = '<form method="post" action="' . site_url('admin/reports/pf_orphan') . '" class="d-inline">' . $csrf . $back . '<input type="hidden" name="txn_id" value="' . $o['id'] . '">';
                if ($opts !== '') {
                    $act .= $form . '<select name="loan_id" class="form-control form-control-sm d-inline-block" style="width:auto">' . $opts . '</select> '
                          . '<button name="do" value="link" class="btn btn-xs btn-primary">Link</button></form> ';
                }
                $act .= $form . '<button name="do" value="reverse" class="btn btn-xs btn-outline-danger" onclick="return confirm(\'Mark this fee entry as reversed (duplicate of a deleted loan)?\')">Reverse</button></form>';
            }
            $orows[] = $o + ['actions' => $act, 'loan_ref' => '#' . $o['reference_id'] . ' (deleted)'];
        }

        $nr = $count('no_record');
        $toolbar = '';
        if ($can && $nr) {
            $toolbar = '<form method="post" action="' . site_url('admin/reports/pf_create_records') . '" class="d-inline" onsubmit="return confirm(\'Create the missing fee entry for ' . $nr . ' loan(s)? The fee was already deducted from the disbursement and is in the ledger — only the fee record is added (no new money, no ledger change).\')">'
                     . $csrf . $back . '<input type="hidden" name="all" value="1"><button class="btn btn-sm btn-success"><i class="fas fa-plus-circle mr-1"></i>Create missing fee records (' . $nr . ' loans)</button></form>';
        }
        if (!$can) $toolbar = '<span class="text-muted small">Only admins can create or fix fee entries.</span>';

        $modal = !$can ? '' : '
<div class="modal fade" id="pfChargeModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <form method="post" action="' . site_url('admin/reports/pf_charge_fee') . '">' . $csrf . $back . '
  <div class="modal-header bg-warning"><h5 class="modal-title">Charge processing fee — <span id="pfLoan"></span></h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
  <div class="modal-body">
    <input type="hidden" name="loan_id" id="pfLoanId">
    <p class="small text-muted">This loan was disbursed without a processing fee. Use this when the member pays the fee now (or paid it separately). It records the fee on the loan, adds the fee entry and posts the receipt to the ledger (Cash/Bank Dr, Processing Fee Income Cr).</p>
    <div class="form-row">
      <div class="form-group col-6"><label>Amount (₹)</label><input type="number" step="0.01" min="1" name="amount" id="pfAmount" class="form-control" required><small class="text-muted">Default = product rule</small></div>
      <div class="form-group col-6"><label>Date received</label><input type="date" name="date" id="pfDate" class="form-control" required></div>
      <div class="form-group col-6"><label>Mode</label><select name="mode" class="form-control"><option value="cash">Cash</option><option value="bank_transfer">Bank transfer</option><option value="upi">UPI</option><option value="cheque">Cheque</option></select></div>
      <div class="form-group col-6"><label>Reference</label><input type="text" name="reference" class="form-control" maxlength="50" placeholder="UTR / receipt no."></div>
    </div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button><button class="btn btn-warning">Charge fee</button></div>
  </form>
</div></div></div>
<script>
$(document).on("click", ".pf-charge", function () {
    var b = $(this);
    $("#pfLoanId").val(b.data("id")); $("#pfLoan").text(b.data("loan")); $("#pfAmount").val(b.data("fee")); $("#pfDate").val(b.data("date"));
    $("#pfChargeModal").modal("show");
});
</script>';

        $this->_render_spec([
            'title' => 'Processing Fee Check', 'icon' => 'fa-file-invoice-dollar',
            'subtitle' => 'Every loan (active, closed, foreclosed) — fee on loan vs product rule, fee entry and ledger',
            'filters' => [
                ['name' => 'status', 'label' => 'Loan status', 'type' => 'select', 'value' => $status,
                 'options' => ['' => 'All', 'active' => 'Active', 'closed' => 'Closed', 'foreclosed' => 'Foreclosed', 'npa' => 'NPA', 'written_off' => 'Written off']],
                ['name' => 'issue', 'label' => 'Show', 'type' => 'select', 'value' => $issue,
                 'options' => ['' => 'All loans', 'no_record' => 'Fee entry missing', 'not_charged' => 'No fee charged', 'record_mismatch' => 'Entry amount differs',
                               'ledger_missing' => 'Not in ledger', 'differs_rule' => 'Differs from product rule', 'ok' => 'OK only']],
            ],
            'kpis' => [
                ['label' => 'Loans checked', 'value' => count($all), 'type' => 'int'],
                ['label' => 'Fee entry missing', 'value' => $nr, 'type' => 'int', 'color' => 'danger'],
                ['label' => 'No fee charged', 'value' => $count('not_charged'), 'type' => 'int', 'color' => 'warning'],
                ['label' => 'Not in ledger', 'value' => $count('ledger_missing'), 'type' => 'int', 'color' => 'danger'],
                ['label' => 'Differs from rule', 'value' => $count('differs_rule'), 'type' => 'int', 'color' => 'info'],
                ['label' => 'Orphan fee entries', 'value' => count($orph), 'type' => 'int', 'color' => 'warning', 'hint' => 'Fee entries of loans that were deleted'],
            ],
            'toolbar_html' => $toolbar,
            'columns' => [
                ['key' => 'loan_number', 'label' => 'Loan', 'type' => 'code'],
                ['key' => 'status_label', 'label' => 'Status'],
                ['key' => 'member_code', 'label' => 'Member', 'type' => 'code'],
                ['key' => 'member_name', 'label' => 'Name'],
                ['key' => 'disbursement_date', 'label' => 'Disbursed', 'type' => 'date'],
                ['key' => 'principal_amount', 'label' => 'Principal', 'type' => 'money', 'total' => true],
                ['key' => 'rule', 'label' => 'Rule'],
                ['key' => 'rule_fee', 'label' => 'Fee as per rule', 'type' => 'money', 'total' => true],
                ['key' => 'processing_fee', 'label' => 'Fee on loan', 'type' => 'money', 'total' => true],
                ['key' => 'fee_pct', 'label' => 'Fee %', 'type' => 'pct'],
                ['key' => 'recorded', 'label' => 'Fee entry', 'type' => 'money', 'total' => true],
                ['key' => 'ledger_fee', 'label' => 'In ledger', 'type' => 'money', 'total' => true],
                ['key' => 'issue_label', 'label' => 'Check'],
                ['key' => 'actions', 'label' => 'Action', 'type' => 'html'],
            ],
            'rows' => $rows,
            'sheets' => [[
                'title' => 'Orphan fee entries (loan deleted)',
                'columns' => [
                    ['key' => 'id', 'label' => 'Entry #', 'type' => 'int'], ['key' => 'transaction_date', 'label' => 'Date', 'type' => 'date'],
                    ['key' => 'member_code', 'label' => 'Member', 'type' => 'code'], ['key' => 'member_name', 'label' => 'Name'],
                    ['key' => 'loan_ref', 'label' => 'Was for loan'], ['key' => 'amount', 'label' => 'Amount', 'type' => 'money', 'total' => true],
                    ['key' => 'description', 'label' => 'Description'], ['key' => 'actions', 'label' => 'Action', 'type' => 'html'],
                ],
                'rows' => $orows,
            ]],
            'after_html' => $modal,
            'note' => 'Fee on loan = loans.processing_fee (deducted from the disbursement). Fee entry = the processing-fee record in member transactions (shows on the member\'s statement / receipts). '
                    . 'In ledger = Processing Fee Income (3200) posted for the loan. "Create record" only adds the missing entry — the money and ledger were already booked at disbursement. '
                    . 'Orphan entries belong to loans that were deleted: link them to the member\'s current loan, or reverse duplicates.',
        ]);
    }

    protected function _pf_back($msg, $ok = true) {
        $this->session->set_flashdata($ok ? 'success' : 'error', $msg);
        $q = (string) $this->input->post('back');
        redirect('admin/reports/processing_fees' . ($q !== '' ? '?' . preg_replace('/[^A-Za-z0-9_=&%\-.]/', '', $q) : ''));
    }

    /** Create the missing fee entry for loans whose fee was deducted at disbursement. POST ids[] or all=1 */
    public function pf_create_records() {
        if ($this->input->method() !== 'post' || !$this->_pf_can_fix()) return $this->_pf_back('Not allowed.', false);
        $want = array_map('intval', (array) $this->input->post('ids'));
        $rows = array_filter($this->Report_model->processing_fee_check(), function ($r) use ($want) {
            return in_array('no_record', $r['issues'], true) && ($this->input->post('all') ? true : in_array((int) $r['id'], $want, true));
        });
        if (!$rows) return $this->_pf_back('Nothing to create — every selected loan already has its fee entry.', false);

        $admin = (int) $this->session->userdata('admin_id');
        $this->db->trans_start();
        $n = 0; $sum = 0;
        foreach ($rows as $r) {
            // re-check inside the transaction (no double entries on a double click)
            $exists = $this->db->where('transaction_type', 'processing_fee')->where('status', 'completed')->where('reference_type', 'loan')
                               ->where('reference_id', $r['id'])->count_all_results('member_other_transactions');
            if ($exists) continue;
            $this->db->insert('member_other_transactions', [
                'member_id' => $r['member_id'], 'transaction_type' => 'processing_fee', 'amount' => $r['processing_fee'],
                'transaction_date' => $r['disbursement_date'] ?: date('Y-m-d'),
                'description' => 'Loan processing fee for Loan #' . $r['id'] . ' (' . $r['loan_number'] . ') — deducted from disbursement',
                'reference_type' => 'loan', 'reference_id' => $r['id'], 'payment_mode' => 'deducted', 'status' => 'completed',
                'created_by' => $admin, 'created_at' => date('Y-m-d H:i:s'),
            ]);
            $n++; $sum += (float) $r['processing_fee'];
        }
        $this->db->trans_complete();
        if (!$this->db->trans_status()) return $this->_pf_back('Database error — nothing was saved.', false);
        $this->log_audit('create', 'member_other_transactions', 'member_other_transactions', null, null,
                         ['processing_fee_records' => $n, 'amount' => $sum], 'Processing fee check: missing fee entries created');
        $this->_pf_back($n . ' fee entr' . ($n === 1 ? 'y' : 'ies') . ' created (₹' . number_format($sum, 2) . '). Ledger unchanged — the fees were already booked at disbursement.');
    }

    /** Loan disbursed without a fee: record the fee the member pays now (loan + entry + ledger receipt). */
    public function pf_charge_fee() {
        if ($this->input->method() !== 'post' || !$this->_pf_can_fix()) return $this->_pf_back('Not allowed.', false);
        $loan_id = (int) $this->input->post('loan_id');
        $amount  = round((float) $this->input->post('amount'), 2);
        $date    = $this->input->post('date');
        $mode    = in_array($this->input->post('mode'), ['cash', 'bank_transfer', 'upi', 'cheque'], true) ? $this->input->post('mode') : 'cash';
        $ref     = mb_substr(trim((string) $this->input->post('reference')), 0, 50);
        $loan = $this->db->where('id', $loan_id)->get('loans')->row();
        if (!$loan) return $this->_pf_back('Loan not found.', false);
        if ((float) $loan->processing_fee > 0) return $this->_pf_back($loan->loan_number . ' already has a processing fee of ₹' . number_format((float) $loan->processing_fee, 2) . '.', false);
        if ($amount <= 0 || $amount > (float) $loan->principal_amount * 0.2) return $this->_pf_back('Enter a fee between ₹1 and 20% of the principal.', false);
        $d = DateTime::createFromFormat('!Y-m-d', (string) $date);
        if (!$d || $d->format('Y-m-d') !== $date || $date > date('Y-m-d')) return $this->_pf_back('Enter a valid date (not in the future).', false);

        $admin = (int) $this->session->userdata('admin_id');
        $this->load->model('Ledger_model');
        $this->db->trans_begin();
        try {
            $this->db->where('id', $loan_id)->update('loans', ['processing_fee' => $amount, 'updated_at' => date('Y-m-d H:i:s')]);
            $this->db->insert('member_other_transactions', [
                'member_id' => $loan->member_id, 'transaction_type' => 'processing_fee', 'amount' => $amount, 'transaction_date' => $date,
                'description' => 'Loan processing fee for Loan #' . $loan->id . ' (' . $loan->loan_number . ') — collected separately' . ($ref !== '' ? ', ref ' . $ref : ''),
                'reference_type' => 'loan', 'reference_id' => $loan->id, 'payment_mode' => $mode, 'receipt_number' => $ref ?: null,
                'status' => 'completed', 'created_by' => $admin, 'created_at' => date('Y-m-d H:i:s'),
            ]);
            $already = $this->db->where('reference_type', 'processing_fee')->where('reference_id', $loan->id)->count_all_results('general_ledger');
            if (!$already && !$this->Ledger_model->post_transaction('processing_fee', $loan->id, $amount, $loan->member_id,
                    'Processing fee for loan: ' . $loan->loan_number, $admin, $date, $mode)) {
                throw new Exception('the ledger entry could not be posted');
            }
            if ($this->db->trans_status() === false) throw new Exception('database error');
            $this->db->trans_commit();
        } catch (Exception $e) {
            $this->db->trans_rollback();
            return $this->_pf_back('Nothing was saved — ' . $e->getMessage() . '.', false);
        }
        $this->log_audit('update', 'loans', 'loans', $loan->id, ['processing_fee' => 0], ['processing_fee' => $amount, 'mode' => $mode, 'date' => $date],
                         'Processing fee charged from Processing Fee Check');
        $this->_pf_back('Processing fee ₹' . number_format($amount, 2) . ' charged on ' . $loan->loan_number . ' and posted to the ledger.');
    }

    /** Fee entry of a deleted loan: link it to the member's loan that has no entry, or reverse it. */
    public function pf_orphan() {
        if ($this->input->method() !== 'post' || !$this->_pf_can_fix()) return $this->_pf_back('Not allowed.', false);
        $t = $this->db->where('id', (int) $this->input->post('txn_id'))->where('transaction_type', 'processing_fee')->get('member_other_transactions')->row();
        if (!$t || $t->status !== 'completed') return $this->_pf_back('Fee entry not found.', false);
        if ($t->reference_id && $this->db->where('id', $t->reference_id)->count_all_results('loans')) return $this->_pf_back('This entry already belongs to an existing loan.', false);

        if ($this->input->post('do') === 'link') {
            $loan = $this->db->where('id', (int) $this->input->post('loan_id'))->where('member_id', $t->member_id)->get('loans')->row();
            if (!$loan) return $this->_pf_back('Choose a loan of the same member.', false);
            $has = $this->db->where('transaction_type', 'processing_fee')->where('status', 'completed')->where('reference_type', 'loan')
                            ->where('reference_id', $loan->id)->count_all_results('member_other_transactions');
            if ($has) return $this->_pf_back($loan->loan_number . ' already has a fee entry — reverse this one instead.', false);
            $this->db->where('id', $t->id)->update('member_other_transactions', ['reference_type' => 'loan', 'reference_id' => $loan->id,
                'description' => 'Loan processing fee for Loan #' . $loan->id . ' (' . $loan->loan_number . ') — relinked from deleted loan #' . $t->reference_id]);
            $this->log_audit('update', 'member_other_transactions', 'member_other_transactions', $t->id, ['reference_id' => $t->reference_id],
                             ['reference_id' => $loan->id], 'Orphan processing fee linked to ' . $loan->loan_number);
            $msg = 'Fee entry #' . $t->id . ' linked to ' . $loan->loan_number . '.';
            if (abs((float) $t->amount - (float) $loan->processing_fee) > 1) $msg .= ' Note: entry ₹' . number_format((float) $t->amount, 2) . ' differs from the loan fee ₹' . number_format((float) $loan->processing_fee, 2) . '.';
            return $this->_pf_back($msg);
        }
        $this->load->model('Member_transaction_model');
        $this->Member_transaction_model->reverse($t->id, 'Duplicate processing fee of deleted loan #' . $t->reference_id . ' (Processing Fee Check)', (int) $this->session->userdata('admin_id'));
        $this->log_audit('reverse', 'member_other_transactions', 'member_other_transactions', $t->id, null, ['status' => 'reversed'], 'Orphan processing fee reversed');
        $this->_pf_back('Fee entry #' . $t->id . ' reversed (it belonged to deleted loan #' . $t->reference_id . ').');
    }
}
