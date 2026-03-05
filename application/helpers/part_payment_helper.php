<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Part Payment Calculation Helper
 * 
 * Provides accurate EMI, tenure, and amortization recalculation
 * for loan part payments (partial prepayments).
 * 
 * EMI Formula: EMI = [P × R × (1+R)^N] / [(1+R)^N – 1]
 * Where: P = Principal, R = Monthly Interest Rate, N = Tenure in months
 * 
 * All financial calculations are rounded to 2 decimal places.
 */

if (!function_exists('calculate_new_emi')) {
    /**
     * Calculate new EMI for given principal, rate, and tenure (reducing balance)
     *
     * @param float $principal   Outstanding principal
     * @param float $annual_rate Annual interest rate (e.g., 8 for 8%)
     * @param int   $tenure      Tenure in months
     * @return float EMI amount (rounded to 2 decimal places)
     */
    function calculate_new_emi($principal, $annual_rate, $tenure) {
        if ($principal <= 0 || $tenure <= 0) return 0;

        $R = ($annual_rate / 12) / 100; // Monthly interest rate

        if ($R == 0) {
            return round($principal / $tenure, 2);
        }

        $pow = pow(1 + $R, $tenure);
        $emi = ($principal * $R * $pow) / ($pow - 1);

        return round($emi, 2);
    }
}

if (!function_exists('calculate_new_tenure')) {
    /**
     * Calculate new tenure keeping EMI same after part payment (reducing balance)
     *
     * Derived from: EMI = [P × R × (1+R)^N] / [(1+R)^N – 1]
     * Solving for N: N = -ln(1 - P×R/EMI) / ln(1+R)
     *
     * @param float $principal   New outstanding principal
     * @param float $annual_rate Annual interest rate (e.g., 8 for 8%)
     * @param float $emi         Current EMI amount
     * @return int   New tenure in months (ceiling to ensure full repayment)
     */
    function calculate_new_tenure($principal, $annual_rate, $emi) {
        if ($principal <= 0 || $emi <= 0) return 0;

        $R = ($annual_rate / 12) / 100;

        if ($R == 0) {
            return (int) ceil($principal / $emi);
        }

        // Check if EMI covers at least the interest — if not, loan can never be repaid
        $monthly_interest = $principal * $R;
        if ($emi <= $monthly_interest) {
            return -1; // Signal: EMI too low to cover interest
        }

        $N = -log(1 - ($principal * $R / $emi)) / log(1 + $R);
        $tenure = (int) ceil($N);

        return max(1, $tenure);
    }
}

if (!function_exists('calculate_total_interest_remaining')) {
    /**
     * Calculate total remaining interest for a loan with reducing balance
     *
     * @param float $principal   Outstanding principal
     * @param float $annual_rate Annual interest rate
     * @param int   $tenure      Remaining tenure in months
     * @param float $emi         EMI amount (if null, calculated from other params)
     * @return float Total interest remaining
     */
    function calculate_total_interest_remaining($principal, $annual_rate, $tenure, $emi = null) {
        if ($principal <= 0 || $tenure <= 0) return 0;

        if ($emi === null) {
            $emi = calculate_new_emi($principal, $annual_rate, $tenure);
        }

        $total_payable = $emi * $tenure;
        $total_interest = $total_payable - $principal;

        return round(max(0, $total_interest), 2);
    }
}

if (!function_exists('calculate_interest_savings')) {
    /**
     * Calculate interest savings from a part payment
     *
     * @param float $old_principal    Principal before part payment
     * @param float $new_principal    Principal after part payment
     * @param float $annual_rate      Annual interest rate
     * @param int   $old_tenure       Remaining tenure before part payment
     * @param float $old_emi          EMI before part payment
     * @param int   $new_tenure       Tenure after part payment
     * @param float $new_emi          EMI after part payment
     * @return float Interest savings (positive number)
     */
    function calculate_interest_savings($old_principal, $new_principal, $annual_rate, $old_tenure, $old_emi, $new_tenure, $new_emi) {
        $old_total_interest = calculate_total_interest_remaining($old_principal, $annual_rate, $old_tenure, $old_emi);
        $new_total_interest = calculate_total_interest_remaining($new_principal, $annual_rate, $new_tenure, $new_emi);

        return round(max(0, $old_total_interest - $new_total_interest), 2);
    }
}

if (!function_exists('generate_amortization_schedule')) {
    /**
     * Generate amortization schedule for reducing balance loan
     *
     * @param float  $principal      Outstanding principal
     * @param float  $annual_rate    Annual interest rate
     * @param int    $tenure         Tenure in months
     * @param float  $emi            EMI amount
     * @param string $first_due_date First installment due date (Y-m-d)
     * @return array Array of installments with keys:
     *   installment_number, due_date, emi_amount, principal_amount, interest_amount,
     *   outstanding_before, outstanding_after
     */
    function generate_amortization_schedule($principal, $annual_rate, $tenure, $emi, $first_due_date) {
        $R = ($annual_rate / 12) / 100;
        $balance = $principal;
        $schedule = [];
        $total_principal_allocated = 0;

        $due_date = new DateTime($first_due_date);

        for ($i = 1; $i <= $tenure; $i++) {
            $interest = round($balance * $R, 2);
            $principal_part = $emi - $interest;

            $outstanding_before = round($balance, 2);

            // Last installment: allocate remaining principal exactly
            if ($i === $tenure) {
                $principal_part = round($principal - $total_principal_allocated, 2);
                $interest = round($balance * $R, 2);
                $emi_actual = $principal_part + $interest;
                $balance = 0;
            } else {
                $principal_part = round($principal_part, 2);
                $balance -= $principal_part;
                $total_principal_allocated += $principal_part;
                $emi_actual = $emi;
            }

            $schedule[] = [
                'installment_number' => $i,
                'due_date'           => $due_date->format('Y-m-d'),
                'emi_amount'         => round($emi_actual, 2),
                'principal_amount'   => $principal_part,
                'interest_amount'    => $interest,
                'outstanding_before' => $outstanding_before,
                'outstanding_after'  => round(max(0, $balance), 2),
            ];

            $due_date->modify('+1 month');
        }

        return $schedule;
    }
}

if (!function_exists('validate_part_payment')) {
    /**
     * Validate a part payment request and return calculated options
     *
     * @param object $loan            Loan object from DB
     * @param float  $part_amount     Part payment amount
     * @param float  $min_emi         Minimum EMI threshold (default: 100)
     * @param int    $min_tenure      Minimum tenure in months (default: 1)
     * @return array ['valid' => bool, 'errors' => [], 'options' => [...]]
     */
    function validate_part_payment($loan, $part_amount, $min_emi = 100, $min_tenure = 1) {
        $errors = [];
        $principal = (float)$loan->outstanding_principal;
        $annual_rate = (float)$loan->interest_rate;
        $current_emi = (float)$loan->emi_amount;

        // Calculate remaining tenure from unpaid installments
        $CI =& get_instance();
        $remaining_tenure = (int) $CI->db->where('loan_id', $loan->id)
                                          ->where_in('status', ['upcoming', 'pending', 'partial', 'overdue'])
                                          ->count_all_results('loan_installments');

        if ($remaining_tenure <= 0) {
            $remaining_tenure = (int)$loan->tenure_months;
        }

        // Edge case: part payment >= principal (foreclosure territory)
        if ($part_amount >= $principal) {
            $errors[] = 'Part payment amount (₹' . number_format($part_amount, 2) . ') must be less than outstanding principal (₹' . number_format($principal, 2) . '). For full repayment, use Foreclosure.';
        }

        if ($part_amount <= 0) {
            $errors[] = 'Part payment amount must be greater than zero.';
        }

        // Check minimum part payment (e.g., at least 1% of outstanding or ₹1000)
        $min_part_payment = max(1000, $principal * 0.01);
        if ($part_amount > 0 && $part_amount < $min_part_payment) {
            $errors[] = 'Minimum part payment is ₹' . number_format($min_part_payment, 2) . '.';
        }

        if (!empty($errors)) {
            return ['valid' => false, 'errors' => $errors, 'options' => null];
        }

        $new_principal = round($principal - $part_amount, 2);

        // --- Option A: Reduce EMI, keep tenure ---
        $new_emi_a = calculate_new_emi($new_principal, $annual_rate, $remaining_tenure);
        $interest_savings_a = calculate_interest_savings(
            $principal, $new_principal, $annual_rate,
            $remaining_tenure, $current_emi, $remaining_tenure, $new_emi_a
        );

        // --- Option B: Reduce Tenure, keep EMI ---
        $new_tenure_b = calculate_new_tenure($new_principal, $annual_rate, $current_emi);
        $tenure_valid = ($new_tenure_b > 0 && $new_tenure_b >= $min_tenure);
        $interest_savings_b = 0;
        if ($tenure_valid) {
            $interest_savings_b = calculate_interest_savings(
                $principal, $new_principal, $annual_rate,
                $remaining_tenure, $current_emi, $new_tenure_b, $current_emi
            );
        }

        // Validate minimum EMI
        if ($new_emi_a < $min_emi) {
            $errors[] = 'Calculated new EMI (₹' . number_format($new_emi_a, 2) . ') is below minimum threshold (₹' . number_format($min_emi, 2) . ').';
        }

        // Total interest remaining calculations
        $old_total_interest = calculate_total_interest_remaining($principal, $annual_rate, $remaining_tenure, $current_emi);
        $new_total_interest_a = calculate_total_interest_remaining($new_principal, $annual_rate, $remaining_tenure, $new_emi_a);
        $new_total_interest_b = $tenure_valid
            ? calculate_total_interest_remaining($new_principal, $annual_rate, $new_tenure_b, $current_emi)
            : 0;

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
            'options' => [
                'current' => [
                    'principal'      => $principal,
                    'emi'            => $current_emi,
                    'tenure'         => $remaining_tenure,
                    'interest_rate'  => $annual_rate,
                    'total_interest' => $old_total_interest,
                ],
                'part_payment_amount' => $part_amount,
                'new_principal'       => $new_principal,
                'option_a' => [
                    'label'           => 'Reduce EMI (Keep Tenure Same)',
                    'new_emi'         => $new_emi_a,
                    'new_tenure'      => $remaining_tenure,
                    'interest_savings' => $interest_savings_a,
                    'new_total_interest' => $new_total_interest_a,
                ],
                'option_b' => [
                    'label'           => 'Reduce Tenure (Keep EMI Same)',
                    'new_emi'         => $current_emi,
                    'new_tenure'      => $tenure_valid ? $new_tenure_b : null,
                    'tenure_valid'    => $tenure_valid,
                    'interest_savings' => $tenure_valid ? $interest_savings_b : 0,
                    'new_total_interest' => $new_total_interest_b,
                    'error'           => !$tenure_valid ? 'EMI is too low to cover monthly interest on new principal.' : null,
                ],
            ],
        ];
    }
}

if (!function_exists('validate_manual_override')) {
    /**
     * Validate manual EMI/Tenure override by admin
     *
     * @param float  $new_principal  New outstanding principal
     * @param float  $annual_rate    Annual interest rate
     * @param float  $manual_emi     Admin-entered EMI (null if not provided)
     * @param int    $manual_tenure  Admin-entered tenure (null if not provided)
     * @param float  $min_emi        Minimum EMI threshold
     * @param int    $min_tenure     Minimum tenure
     * @return array ['valid' => bool, 'errors' => [], 'calculated_emi' => float, 'calculated_tenure' => int, 'interest_savings' => float]
     */
    function validate_manual_override($new_principal, $annual_rate, $manual_emi, $manual_tenure, $min_emi = 100, $min_tenure = 1) {
        $errors = [];
        $calculated_emi = null;
        $calculated_tenure = null;

        if ($manual_emi !== null && $manual_tenure !== null) {
            $errors[] = 'Please provide either EMI or Tenure, not both. The system will calculate the other.';
            return ['valid' => false, 'errors' => $errors];
        }

        if ($manual_emi === null && $manual_tenure === null) {
            $errors[] = 'Please provide either a custom EMI or a custom Tenure.';
            return ['valid' => false, 'errors' => $errors];
        }

        if ($manual_emi !== null) {
            // Admin provided EMI → calculate tenure
            if ($manual_emi < $min_emi) {
                $errors[] = 'EMI must be at least ₹' . number_format($min_emi, 2);
            }

            $R = ($annual_rate / 12) / 100;
            $monthly_interest = $new_principal * $R;

            if ($manual_emi <= $monthly_interest && $R > 0) {
                $errors[] = 'EMI (₹' . number_format($manual_emi, 2) . ') must be greater than monthly interest (₹' . number_format($monthly_interest, 2) . ').';
            }

            if (empty($errors)) {
                $calculated_tenure = calculate_new_tenure($new_principal, $annual_rate, $manual_emi);
                $calculated_emi = $manual_emi;

                if ($calculated_tenure < $min_tenure) {
                    $errors[] = 'Calculated tenure (' . $calculated_tenure . ' months) is below minimum (' . $min_tenure . ').';
                }
                if ($calculated_tenure > 360) {
                    $errors[] = 'Calculated tenure (' . $calculated_tenure . ' months) exceeds maximum limit (360 months / 30 years).';
                }
            }
        } else {
            // Admin provided Tenure → calculate EMI
            if ($manual_tenure < $min_tenure) {
                $errors[] = 'Tenure must be at least ' . $min_tenure . ' month(s).';
            }
            if ($manual_tenure > 360) {
                $errors[] = 'Tenure cannot exceed 360 months (30 years).';
            }

            if (empty($errors)) {
                $calculated_emi = calculate_new_emi($new_principal, $annual_rate, $manual_tenure);
                $calculated_tenure = $manual_tenure;

                if ($calculated_emi < $min_emi) {
                    $errors[] = 'Calculated EMI (₹' . number_format($calculated_emi, 2) . ') is below minimum (₹' . number_format($min_emi, 2) . ').';
                }
            }
        }

        $total_interest = 0;
        if (!empty($errors) === false && $calculated_emi && $calculated_tenure) {
            $total_interest = calculate_total_interest_remaining($new_principal, $annual_rate, $calculated_tenure, $calculated_emi);
        }

        return [
            'valid'             => empty($errors),
            'errors'            => $errors,
            'calculated_emi'    => $calculated_emi,
            'calculated_tenure' => $calculated_tenure,
            'total_interest'    => $total_interest,
        ];
    }
}
