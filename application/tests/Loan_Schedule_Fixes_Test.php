<?php
/**
 * LOAN SCHEDULE INTEGRITY FIXES - Test Suite
 * 
 * Test cases for validating the three fixes:
 * 1. Interest-Only EMI Display
 * 2. Balance Progression Validation
 * 3. EMI Consistency After Part Payment
 * 
 * Usage: Run via CLI or integrate with PHPUnit
 * php tests/loan_schedule_fixes_test.php
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Loan_Schedule_Fixes_Test {
    private $CI;
    private $test_results = [];
    private $test_loan_id = null;
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model('Loan_model');
        $this->CI->load->model('Member_model');
    }
    
    /**
     * Run all tests
     */
    public function run_all_tests() {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "LOAN SCHEDULE INTEGRITY FIXES - TEST SUITE\n";
        echo str_repeat("=", 80) . "\n\n";
        
        try {
            $this->test_interest_only_emi_display();
            $this->test_balance_validation();
            $this->test_emi_consistency_after_part_payment();
            $this->test_schedule_validation_function();
            
            $this->print_results();
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * TEST 1: Interest-Only EMI Display
     * 
     * Verify that when installment status is 'interest_only',
     * the displayed EMI shows actual payment (interest + fine) not original EMI
     */
    private function test_interest_only_emi_display() {
        echo "TEST 1: Interest-Only EMI Display\n";
        echo str_repeat("-", 80) . "\n";
        
        $test_name = "interest_only_emi_display";
        
        try {
            // Get a loan with interest-only payment
            $query = $this->CI->db->query("
                SELECT li.*, l.id as loan_id 
                FROM loan_installments li
                JOIN loans l ON l.id = li.loan_id
                WHERE li.status = 'interest_only' 
                LIMIT 1
            ");
            
            if ($query->num_rows() === 0) {
                $this->record_test($test_name, false, 'No interest-only installments found in database');
                return;
            }
            
            $inst = $query->row();
            
            // Calculate what EMI should show
            $actual_interest_paid = (float)($inst->interest_paid ?? $inst->interest_amount ?? 0);
            $actual_fine_paid = (float)($inst->fine_paid ?? 0);
            $expected_emi_display = round($actual_interest_paid + $actual_fine_paid, 2);
            
            // Load the loan view through controller normalization
            $installments = $this->CI->Loan_model->get_loan_installments($inst->loan_id);
            $found_inst = null;
            foreach ($installments as $i) {
                if ($i->id == $inst->id) {
                    $found_inst = $i;
                    break;
                }
            }
            
            if (!$found_inst) {
                $this->record_test($test_name, false, 'Installment not found after normalization');
                return;
            }
            
            // After controller normalization, EMI should be recalculated
            $passed = abs((float)$found_inst->emi_amount - $expected_emi_display) < 0.01;
            
            echo "  Loan ID: {$inst->loan_id}\n";
            echo "  Installment Number: {$inst->installment_number}\n";
            echo "  Original EMI Amount: ₹" . number_format($inst->emi_amount, 2) . "\n";
            echo "  Expected Display EMI: ₹" . number_format($expected_emi_display, 2) . "\n";
            echo "  Actual Display EMI: ₹" . number_format($found_inst->emi_amount, 2) . "\n";
            echo "  Status: " . ($passed ? "✅ PASS" : "❌ FAIL") . "\n\n";
            
            $this->record_test($test_name, $passed, 
                "Interest-only EMI display should show ₹{$expected_emi_display}, got ₹{$found_inst->emi_amount}");
                
        } catch (Exception $e) {
            $this->record_test($test_name, false, "Exception: " . $e->getMessage());
        }
    }
    
    /**
     * TEST 2: Balance Validation
     * 
     * Verify that outstanding_principal_after = outstanding_principal_before - principal_amount
     * for non-interest-only, non-waived installments
     */
    private function test_balance_validation() {
        echo "TEST 2: Balance Progression Validation\n";
        echo str_repeat("-", 80) . "\n";
        
        $test_name = "balance_validation";
        
        try {
            // Find any balance discrepancies in the database
            $query = $this->CI->db->query("
                SELECT 
                    li.id,
                    li.loan_id,
                    li.installment_number,
                    li.outstanding_principal_before,
                    li.principal_amount,
                    li.outstanding_principal_after,
                    li.status,
                    (li.outstanding_principal_before - li.principal_amount) as calculated_after
                FROM loan_installments li
                WHERE li.status NOT IN ('interest_only', 'waived', 'skipped')
                  AND ABS((li.outstanding_principal_before - li.principal_amount) - li.outstanding_principal_after) > 0.01
                LIMIT 5
            ");
            
            if ($query->num_rows() > 0) {
                $this->record_test($test_name, false, 
                    "Found {$query->num_rows()} balance discrepancies: \n");
                
                foreach ($query->result() as $row) {
                    $expected = $row->outstanding_principal_before - $row->principal_amount;
                    $actual = $row->outstanding_principal_after;
                    echo "  Loan {$row->loan_id}, Installment {$row->installment_number}:\n";
                    echo "    Before: ₹" . number_format($row->outstanding_principal_before, 2) . "\n";
                    echo "    Principal Paid: ₹" . number_format($row->principal_amount, 2) . "\n";
                    echo "    Expected After: ₹" . number_format($expected, 2) . "\n";
                    echo "    Actual After: ₹" . number_format($actual, 2) . "\n";
                    echo "    Difference: ₹" . number_format(abs($expected - $actual), 2) . "\n\n";
                }
                return;
            }
            
            echo "  Checked all non-interest-only installments\n";
            echo "  Status: ✅ PASS - No balance discrepancies found\n\n";
            
            $this->record_test($test_name, true, "All balance progressions are valid");
            
        } catch (Exception $e) {
            $this->record_test($test_name, false, "Exception: " . $e->getMessage());
        }
    }
    
    /**
     * TEST 3: EMI Consistency After Part Payment
     * 
     * Verify that regenerated schedules have consistent EMI values
     * (except for final installment which balances the remainder)
     */
    private function test_emi_consistency_after_part_payment() {
        echo "TEST 3: EMI Consistency After Part Payment\n";
        echo str_repeat("-", 80) . "\n";
        
        $test_name = "emi_consistency";
        
        try {
            // Find schedules with high EMI variance (potential issue)
            $query = $this->CI->db->query("
                SELECT 
                    l.id as loan_id,
                    l.loan_number,
                    l.outstanding_principal,
                    l.emi_amount as current_emi,
                    COUNT(DISTINCT li.emi_amount) as distinct_emi_count,
                    MIN(li.emi_amount) as min_emi,
                    MAX(li.emi_amount) as max_emi,
                    (MAX(li.emi_amount) - MIN(li.emi_amount)) as emi_variance,
                    COUNT(*) as total_installments
                FROM loans l
                JOIN loan_installments li ON li.loan_id = l.id
                WHERE li.status NOT IN ('interest_only', 'waived', 'skipped')
                    AND li.installment_number < (SELECT MAX(installment_number) FROM loan_installments WHERE loan_id = l.id)
                GROUP BY l.id
                HAVING emi_variance > 0.10
                LIMIT 5
            ");
            
            if ($query->num_rows() > 0) {
                echo "  Found {$query->num_rows()} loans with EMI variance > ₹0.10:\n\n";
                
                foreach ($query->result() as $row) {
                    echo "  Loan: {$row->loan_number} (ID: {$row->loan_id})\n";
                    echo "    Current EMI: ₹" . number_format($row->current_emi, 2) . "\n";
                    echo "    Distinct EMI Values: {$row->distinct_emi_count}\n";
                    echo "    Min EMI: ₹" . number_format($row->min_emi, 2) . "\n";
                    echo "    Max EMI: ₹" . number_format($row->max_emi, 2) . "\n";
                    echo "    Variance: ₹" . number_format($row->emi_variance, 2) . "\n";
                    echo "    Total Installments: {$row->total_installments}\n\n";
                    
                    // Check logs for warnings
                    echo "    Action: Review loan_schedule_audit for this loan\n";
                    $audit = $this->CI->db->where('loan_id', $row->loan_id)
                                         ->order_by('performed_at', 'DESC')
                                         ->limit(1)
                                         ->get('loan_schedule_audit')
                                         ->row();
                    
                    if ($audit) {
                        echo "    Last Regeneration: {$audit->performed_at}\n";
                        echo "    Reason: {$audit->reason}\n";
                        if ($audit->validation_warnings) {
                            echo "    Warnings: {$audit->validation_warnings}\n";
                        }
                    }
                    echo "\n";
                }
                
                $this->record_test($test_name, false, 
                    "Found {$query->num_rows()} loans with EMI variance. Review recommended.");
                return;
            }
            
            echo "  Checked all loans for EMI consistency\n";
            echo "  Status: ✅ PASS - All EMI values consistent (variance <= ₹0.10)\n\n";
            
            $this->record_test($test_name, true, "All EMI schedules are consistent");
            
        } catch (Exception $e) {
            $this->record_test($test_name, false, "Exception: " . $e->getMessage());
        }
    }
    
    /**
     * TEST 4: Validate Schedule Integrity Function
     * 
     * Test the new validate_schedule_integrity() function
     */
    private function test_schedule_validation_function() {
        echo "TEST 4: Schedule Validation Function\n";
        echo str_repeat("-", 80) . "\n";
        
        $test_name = "schedule_validation_function";
        
        try {
            // Get a random loan with installments
            $loan = $this->CI->db->where('status', 'active')
                                 ->limit(1)
                                 ->get('loans')
                                 ->row();
            
            if (!$loan) {
                $this->record_test($test_name, false, "No active loans found");
                return;
            }
            
            // Call the validation function
            $result = $this->CI->Loan_model->validate_schedule_integrity($loan->id);
            
            echo "  Loan ID: {$loan->id}\n";
            echo "  Loan Number: {$loan->loan_number}\n";
            echo "  Validation Result:\n";
            echo "    Valid: " . ($result['valid'] ? "YES" : "NO") . "\n";
            echo "    Errors: " . (count($result['errors']) ?? 0) . "\n";
            echo "    Warnings: " . (count($result['warnings']) ?? 0) . "\n";
            
            if (!empty($result['errors'])) {
                echo "\n    Error Details:\n";
                foreach ($result['errors'] as $error) {
                    echo "      - " . substr($error, 0, 70) . "...\n";
                }
            }
            
            if (!empty($result['warnings'])) {
                echo "\n    Warning Details:\n";
                foreach ($result['warnings'] as $warning) {
                    echo "      - " . substr($warning, 0, 70) . "...\n";
                }
            }
            echo "\n";
            
            $this->record_test($test_name, $result['valid'], 
                "Validation returned " . ($result['valid'] ? "valid" : "invalid") . " with " 
                . count($result['errors']) . " errors");
            
        } catch (Exception $e) {
            $this->record_test($test_name, false, "Exception: " . $e->getMessage());
        }
    }
    
    /**
     * Record test result
     */
    private function record_test($name, $passed, $message) {
        $this->test_results[] = [
            'name' => $name,
            'passed' => $passed,
            'message' => $message
        ];
    }
    
    /**
     * Print all test results
     */
    private function print_results() {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "TEST RESULTS SUMMARY\n";
        echo str_repeat("=", 80) . "\n\n";
        
        $passed = 0;
        $failed = 0;
        
        foreach ($this->test_results as $result) {
            $status = $result['passed'] ? "✅ PASS" : "❌ FAIL";
            echo "{$status} - {$result['name']}\n";
            echo "  Message: {$result['message']}\n\n";
            
            if ($result['passed']) {
                $passed++;
            } else {
                $failed++;
            }
        }
        
        echo str_repeat("=", 80) . "\n";
        echo "Total: " . ($passed + $failed) . " tests | Passed: {$passed} | Failed: {$failed}\n";
        echo str_repeat("=", 80) . "\n\n";
    }
}

// Run tests if called from CLI
if (php_sapi_name() === 'cli') {
    require_once APPPATH . 'config/database.php';
    $tester = new Loan_Schedule_Fixes_Test();
    $tester->run_all_tests();
}
?>
