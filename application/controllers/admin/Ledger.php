<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ledger Controller - Member Ledger & Transaction History
 */
class Ledger extends Admin_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model(['Ledger_model', 'Member_model']);
    }
    
    /**
     * Member Ledger - View member-wise transaction history
     */
    public function member($member_id = null) {
        $data['title'] = 'Member Ledger';
        $data['page_title'] = 'Member Ledger';
        $data['breadcrumb'] = [
            ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['title' => 'Reports', 'url' => 'admin/reports'],
            ['title' => 'Member Ledger', 'url' => '']
        ];
        
        // Get filters
        $member_id = $member_id ?: $this->input->get('member_id');
        $from_date = $this->input->get('from_date');
        $to_date = $this->input->get('to_date');
        
        $data['member_id'] = $member_id;
        $data['from_date'] = $from_date;
        $data['to_date'] = $to_date;
        $data['member'] = null;
        $data['ledger'] = [];
        
        // Get member details if selected
        if ($member_id) {
            $data['member'] = $this->Member_model->get_by_id($member_id);
            
            if (!$data['member']) {
                $this->session->set_flashdata('error', 'Member not found');
                redirect('admin/ledger/member');
            }
            
            // Get ledger entries
            $data['ledger'] = $this->Ledger_model->get_member_ledger($member_id, $from_date, $to_date);
            
            // Calculate summary
            $data['summary'] = $this->_calculate_summary($data['ledger']);
        }
        
        // Get all members for dropdown
        $data['members'] = $this->db->select('id, member_code, first_name, last_name')
                                    ->where('status', 'active')
                                    ->where('deleted_at', null)
                                    ->order_by('member_code', 'ASC')
                                    ->get('members')
                                    ->result();
        
        $this->load_view('admin/ledger/member', $data);
    }
    
    /**
     * Calculate ledger summary
     */
    private function _calculate_summary($ledger) {
        $summary = [
            'opening_balance' => 0,
            'total_debit' => 0,
            'total_credit' => 0,
            'closing_balance' => 0
        ];
        
        if (!empty($ledger)) {
            $first_entry = reset($ledger);
            $last_entry = end($ledger);
            
            $summary['opening_balance'] = $first_entry->balance_after - 
                                         ($first_entry->credit_amount - $first_entry->debit_amount);
            
            foreach ($ledger as $entry) {
                $summary['total_debit'] += $entry->debit_amount;
                $summary['total_credit'] += $entry->credit_amount;
            }
            
            $summary['closing_balance'] = $last_entry->balance_after;
        }
        
        return $summary;
    }
    
    /**
     * Export member ledger to Excel
     */
    public function export() {
        $member_id = $this->input->get('member_id');
        $from_date = $this->input->get('from_date');
        $to_date = $this->input->get('to_date');
        
        if (!$member_id) {
            $this->session->set_flashdata('error', 'Please select a member');
            redirect('admin/ledger/member');
        }
        
        $member = $this->Member_model->get_by_id($member_id);
        $ledger = $this->Ledger_model->get_member_ledger($member_id, $from_date, $to_date);
        
        // Load PHPSpreadsheet
        require_once APPPATH . '../vendor/autoload.php';
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set headers
        $sheet->setCellValue('A1', 'Member Ledger');
        $sheet->setCellValue('A2', 'Member: ' . $member->first_name . ' ' . $member->last_name . ' (' . $member->member_code . ')');
        $sheet->setCellValue('A3', 'Period: ' . ($from_date ?: 'Beginning') . ' to ' . ($to_date ?: 'Today'));
        
        // Column headers
        $sheet->setCellValue('A5', 'Date');
        $sheet->setCellValue('B5', 'Type');
        $sheet->setCellValue('C5', 'Reference');
        $sheet->setCellValue('D5', 'Narration');
        $sheet->setCellValue('E5', 'Debit');
        $sheet->setCellValue('F5', 'Credit');
        $sheet->setCellValue('G5', 'Balance');
        
        // Style headers
        $sheet->getStyle('A5:G5')->getFont()->setBold(true);
        $sheet->getStyle('A5:G5')->getFill()
              ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
              ->getStartColor()->setRGB('4CAF50');
        
        // Add data
        $row = 6;
        foreach ($ledger as $entry) {
            $sheet->setCellValue('A' . $row, $entry->transaction_date);
            $sheet->setCellValue('B' . $row, ucfirst(str_replace('_', ' ', $entry->transaction_type)));
            $sheet->setCellValue('C' . $row, $entry->reference_type . ' #' . $entry->reference_id);
            $sheet->setCellValue('D' . $row, $entry->narration);
            $sheet->setCellValue('E' . $row, $entry->debit_amount);
            $sheet->setCellValue('F' . $row, $entry->credit_amount);
            $sheet->setCellValue('G' . $row, $entry->balance_after);
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Output file
        $filename = 'Member_Ledger_' . $member->member_code . '_' . date('Y-m-d') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
    
    /**
     * Print member ledger
     */
    public function print_ledger() {
        $member_id = $this->input->get('member_id');
        $from_date = $this->input->get('from_date');
        $to_date = $this->input->get('to_date');
        
        if (!$member_id) {
            show_error('Please select a member');
        }
        
        $data['member'] = $this->Member_model->get_by_id($member_id);
        $data['ledger'] = $this->Ledger_model->get_member_ledger($member_id, $from_date, $to_date);
        $data['summary'] = $this->_calculate_summary($data['ledger']);
        $data['from_date'] = $from_date;
        $data['to_date'] = $to_date;
        
        $this->load->view('admin/ledger/print_member', $data);
    }
    
    /**
     * AJAX: Search members
     */
    public function search_members() {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        
        $search = $this->input->get('q');
        
        $this->db->select('id, member_code, first_name, last_name, phone, status');
        $this->db->from('members');
        $this->db->group_start();
        $this->db->like('member_code', $search);
        $this->db->or_like('first_name', $search);
        $this->db->or_like('last_name', $search);
        $this->db->or_like('phone', $search);
        $this->db->group_end();
        $this->db->limit(20);
        
        $members = $this->db->get()->result();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $members
        ]);
        exit;
    }
}
