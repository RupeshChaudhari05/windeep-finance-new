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
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Headers based on type
        switch ($type) {
            case 'outstanding':
                fputcsv($output, ['Loan No', 'Member Code', 'Member Name', 'Product', 'Principal', 'Outstanding Principal', 'Outstanding Interest', 'Overdue Amount']);
                foreach ($data as $row) {
                    fputcsv($output, [
                        $row->loan_number,
                        $row->member_code,
                        $row->first_name . ' ' . $row->last_name,
                        $row->product_name,
                        $row->principal_amount,
                        $row->outstanding_principal,
                        $row->outstanding_interest,
                        $row->overdue_amount
                    ]);
                }
                break;
        }
        
        fclose($output);
        exit;
    }
}
