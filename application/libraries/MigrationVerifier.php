<?php
/**
 * Migration Verification System
 * 
 * Purpose: Verify all migration components are correctly deployed
 * Usage: Run from admin panel or CLI after deploying migration #001
 * 
 * @author System
 * @date 2026-06-05
 * @version 1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class MigrationVerifier
{
    private $db;
    private $results = [];
    private $errors = [];
    
    public function __construct()
    {
        $this->load = &get_instance()->load;
        $this->db = &get_instance()->db;
    }
    
    /**
     * Run complete verification
     */
    public function verify_all()
    {
        $this->verify_constraints();
        $this->verify_indices();
        $this->verify_audit_table();
        $this->verify_migrations_table();
        $this->verify_data_integrity();
        
        return [
            'status' => empty($this->errors) ? 'success' : 'warning',
            'results' => $this->results,
            'errors' => $this->errors,
            'summary' => $this->get_summary()
        ];
    }
    
    /**
     * Verify CHECK constraints exist
     */
    private function verify_constraints()
    {
        $query = "SELECT CONSTRAINT_NAME, CONSTRAINT_TYPE 
                  FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
                  WHERE TABLE_NAME = 'loan_installments' 
                  AND CONSTRAINT_NAME LIKE 'chk_%'";
        
        $result = $this->db->query($query)->result();
        
        $constraint_names = array_map(function($c) { return $c->CONSTRAINT_NAME; }, $result);
        
        $this->results['constraints'] = [
            'required' => ['chk_balance_progression', 'chk_nonnegative_amounts'],
            'present' => array_unique($constraint_names),
            'count' => count(array_unique($constraint_names)),
            'status' => count(array_unique($constraint_names)) >= 2 ? 'PASS' : 'FAIL'
        ];
        
        if ($this->results['constraints']['status'] !== 'PASS') {
            $this->errors[] = 'Constraints missing: ' . implode(', ', 
                array_diff($this->results['constraints']['required'], 
                          $this->results['constraints']['present']));
        }
    }
    
    /**
     * Verify performance indices exist
     */
    private function verify_indices()
    {
        $query = "SHOW INDEX FROM loan_installments 
                  WHERE Key_name IN ('idx_loan_status_date', 'idx_unpaid_installments')";
        
        $result = $this->db->query($query)->result();
        
        $indices = [];
        foreach ($result as $row) {
            $indices[$row->Key_name][] = $row->Column_name;
        }
        
        $this->results['indices'] = [
            'required' => [
                'idx_loan_status_date' => ['loan_id', 'status', 'due_date'],
                'idx_unpaid_installments' => ['loan_id', 'status', 'installment_number']
            ],
            'present' => $indices,
            'count' => count($indices),
            'status' => count($indices) >= 2 ? 'PASS' : 'FAIL'
        ];
        
        if ($this->results['indices']['status'] !== 'PASS') {
            $this->errors[] = 'Indices missing or incomplete';
        }
    }
    
    /**
     * Verify audit table structure
     */
    private function verify_audit_table()
    {
        $query = "SELECT COUNT(*) as column_count 
                  FROM INFORMATION_SCHEMA.COLUMNS 
                  WHERE TABLE_NAME = 'loan_schedule_audit' 
                  AND TABLE_SCHEMA = DATABASE()";
        
        $result = $this->db->query($query)->row();
        
        $required_columns = [
            'id', 'loan_id', 'action', 'previous_principal', 'new_principal',
            'previous_tenure', 'new_tenure', 'previous_emi', 'new_emi',
            'previous_installment_count', 'new_installment_count',
            'reason', 'validation_errors', 'validation_warnings',
            'performed_by', 'performed_at', 'created_at'
        ];
        
        $this->results['audit_table'] = [
            'required_columns' => count($required_columns),
            'present_columns' => $result->column_count,
            'status' => $result->column_count >= count($required_columns) ? 'PASS' : 'FAIL'
        ];
        
        if ($this->results['audit_table']['status'] !== 'PASS') {
            $this->errors[] = "Audit table missing columns: expected {$required_columns}, found {$result->column_count}";
        }
    }
    
    /**
     * Verify migrations table exists
     */
    private function verify_migrations_table()
    {
        $query = "SELECT COUNT(*) as column_count 
                  FROM INFORMATION_SCHEMA.COLUMNS 
                  WHERE TABLE_NAME = 'migrations' 
                  AND TABLE_SCHEMA = DATABASE()";
        
        $result = $this->db->query($query)->row();
        
        $this->results['migrations_table'] = [
            'columns' => $result->column_count,
            'status' => $result->column_count >= 11 ? 'PASS' : 'FAIL'
        ];
        
        if ($this->results['migrations_table']['status'] !== 'PASS') {
            $this->errors[] = "Migrations table invalid or missing";
        }
    }
    
    /**
     * Verify data integrity (sample check)
     */
    private function verify_data_integrity()
    {
        $query = "SELECT COUNT(*) as invalid_records 
                  FROM loan_installments 
                  WHERE outstanding_principal_after > outstanding_principal_before 
                  AND status != 'interest_only'";
        
        $result = $this->db->query($query)->row();
        
        $this->results['data_integrity'] = [
            'invalid_records' => $result->invalid_records,
            'status' => $result->invalid_records == 0 ? 'PASS' : 'WARNING'
        ];
        
        if ($this->results['data_integrity']['status'] !== 'PASS') {
            $this->errors[] = "Found {$result->invalid_records} records with invalid balance progression";
        }
    }
    
    /**
     * Get summary report
     */
    private function get_summary()
    {
        $total_checks = 5;
        $passed = 0;
        
        foreach ($this->results as $result) {
            if (isset($result['status']) && $result['status'] === 'PASS') {
                $passed++;
            }
        }
        
        return [
            'total_checks' => $total_checks,
            'passed' => $passed,
            'failed' => $total_checks - $passed,
            'overall_status' => $passed === $total_checks ? '✓ ALL CHECKS PASSED' : '⚠ SOME CHECKS FAILED'
        ];
    }
    
    /**
     * Generate HTML report
     */
    public function get_html_report()
    {
        $verification = $this->verify_all();
        
        $html = '<div class="migration-report">';
        $html .= '<h3>Migration #001 Verification Report</h3>';
        $html .= '<p>Date: ' . date('Y-m-d H:i:s') . '</p>';
        
        // Summary
        $summary = $verification['summary'];
        $html .= '<div class="summary">';
        $html .= '<p><strong>Status: </strong>' . $summary['overall_status'] . '</p>';
        $html .= '<p>Passed: ' . $summary['passed'] . '/' . $summary['total_checks'] . '</p>';
        $html .= '</div>';
        
        // Details
        $html .= '<h4>Detailed Results:</h4>';
        foreach ($verification['results'] as $check => $result) {
            $status_icon = (isset($result['status']) && $result['status'] === 'PASS') ? '✓' : '✗';
            $html .= '<div class="check-result">';
            $html .= '<p><strong>' . ucfirst(str_replace('_', ' ', $check)) . ':</strong> ' . $status_icon . '</p>';
            $html .= '<pre>' . json_encode($result, JSON_PRETTY_PRINT) . '</pre>';
            $html .= '</div>';
        }
        
        // Errors
        if (!empty($verification['errors'])) {
            $html .= '<h4>Issues Found:</h4>';
            foreach ($verification['errors'] as $error) {
                $html .= '<div class="error"><p>⚠ ' . $error . '</p></div>';
            }
        }
        
        $html .= '</div>';
        return $html;
    }
}

// Example usage:
// $verifier = new MigrationVerifier();
// $report = $verifier->verify_all();
// echo json_encode($report);
