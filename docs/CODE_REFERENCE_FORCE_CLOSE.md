# Force Close Calculator - Code Reference

**Date:** 2026-07-02  
**Purpose:** Quick reference of all code changes

---

## 1. Model Function - `calculate_force_close_amount()`

**File:** `application/models/Loan_model.php` (Lines 1957-2003)

```php
/**
 * Calculate Force Close Amount (Next Month Interest Only)
 * 
 * NEW RULE: When a member chooses force close, they pay only the 
 * next month's interest. All outstanding interest and fines are waived.
 * Loan is closed immediately.
 * 
 * Formula: Next Month Interest = Outstanding Principal × (Annual Rate / 12 / 100)
 * 
 * @param int $loan_id Loan ID
 * @return array|false Array with calculation breakdown or false if loan not found
 */
public function calculate_force_close_amount($loan_id) {
    $loan = $this->db->where('id', $loan_id)->get('loans')->row();
    if (!$loan) {
        return false;
    }

    $outstanding_principal = $loan->outstanding_principal ?? 0;
    $annual_rate = $loan->interest_rate ?? 0;
    
    // Calculate next month's interest: Principal × (Annual Rate / 12 / 100)
    $monthly_interest = $outstanding_principal * ($annual_rate / 12) / 100;
    
    return [
        'outstanding_principal' => $outstanding_principal,
        'annual_interest_rate' => $annual_rate,
        'monthly_interest_rate' => round(($annual_rate / 12), 4),
        'next_month_interest' => round($monthly_interest, 2),
        'total_amount' => round($monthly_interest, 2),
        'amount_waived_interest' => $loan->outstanding_interest ?? 0,
        'amount_waived_fines' => $this->db->select_sum('f.fine_amount')
                                          ->from('fines f')
                                          ->join('loan_installments li', 'li.id = f.related_id AND f.related_type = "loan_installment"', 'inner')
                                          ->where('li.loan_id', $loan_id)
                                          ->where('f.status', 'pending')
                                          ->get()
                                          ->row()
                                          ->fine_amount ?? 0,
        'type' => 'force_close',
        'calculated_at' => date('Y-m-d H:i:s'),
        'note' => 'Force close: All outstanding interest and fines are waived. Only next month interest is charged.'
    ];
}
```

---

## 2. Controller Method - `calculate_force_close()`

**File:** `application/controllers/member/Loans.php` (Lines 726-778)

```php
/**
 * Calculate Force Close Amount (AJAX)
 * Returns the force close settlement amount (next month interest only)
 */
public function calculate_force_close() {
    // Check if AJAX request
    if (!$this->input->is_ajax_request()) {
        return json_encode(['success' => false, 'message' => 'Invalid request']);
    }

    $loan_id = $this->input->post('loan_id');

    // Validate loan ownership and eligibility
    $loan = $this->db->where('id', $loan_id)
                    ->where('member_id', $this->member->id)
                    ->where_in('status', ['active', 'overdue'])
                    ->get('loans')
                    ->row();

    if (!$loan) {
        echo json_encode([
            'success' => false,
            'message' => 'Loan not found or not eligible for force close.'
        ]);
        return;
    }

    // Calculate force close amount using model
    $calculation = $this->Loan_model->calculate_force_close_amount($loan_id);

    if (!$calculation) {
        echo json_encode([
            'success' => false,
            'message' => 'Unable to calculate force close amount. Please try again.'
        ]);
        return;
    }

    // Return JSON response with calculation
    echo json_encode([
        'success' => true,
        'data' => [
            'outstanding_principal' => $calculation['outstanding_principal'],
            'annual_interest_rate' => $calculation['annual_interest_rate'],
            'monthly_interest_rate' => $calculation['monthly_interest_rate'],
            'next_month_interest' => $calculation['next_month_interest'],
            'total_amount' => $calculation['total_amount'],
            'amount_waived_interest' => $calculation['amount_waived_interest'],
            'amount_waived_fines' => $calculation['amount_waived_fines'],
            'calculated_at' => $calculation['calculated_at'],
            'note' => $calculation['note']
        ]
    ]);
}
```

---

## 3. View - Closure Type Selection

**File:** `application/views/member/loans/request_foreclosure.php` (Lines ~82-105)

```html
<!-- Closure Type Selection -->
<div class="form-group mb-3 pb-3 border-bottom">
    <label class="mb-2"><i class="fas fa-clone mr-1"></i> <strong>Foreclosure Type</strong></label>
    <div class="custom-control custom-radio">
        <input type="radio" class="custom-control-input closure-type" id="closureRegular" name="closure_type" value="regular" checked>
        <label class="custom-control-label" for="closureRegular">
            <strong>Regular Foreclosure</strong>
            <br><small class="text-muted">Pay full amount (principal + all interest + fines)</small>
        </label>
    </div>
    <div class="custom-control custom-radio mt-2">
        <input type="radio" class="custom-control-input closure-type" id="closureForce" name="closure_type" value="force_close">
        <label class="custom-control-label" for="closureForce">
            <strong>Force Close</strong>
            <br><small class="text-muted">Pay only next month's interest (save up to 90%!)</small>
        </label>
    </div>
</div>
```

---

## 4. View - Force Close Display Section

**File:** `application/views/member/loans/request_foreclosure.php` (Lines ~148-175)

```html
<!-- Force Close Display -->
<div id="forceCloseDisplay" style="display: none;">
    <div class="alert alert-success mb-2">
        <i class="fas fa-leaf mr-1"></i>
        <strong>Save up to 90%!</strong> Pay only next month's interest and close your loan immediately.
    </div>
    <table class="table table-bordered table-sm">
        <tr>
            <th>Outstanding Principal</th>
            <td class="text-right"><span class="text-muted text-decoration-line-through"><?= $cs ?><?= number_format($settlement['outstanding_principal'] ?? 0, 2) ?></span></td>
        </tr>
        <tr>
            <th>Accrued Interest (Waived)</th>
            <td class="text-right"><span class="text-success"><i class="fas fa-check mr-1"></i>Waived</span></td>
        </tr>
        <tr>
            <th>Pending Fines (Waived)</th>
            <td class="text-right"><span class="text-success"><i class="fas fa-check mr-1"></i>Waived</span></td>
        </tr>
        <tr class="bg-light">
            <th class="text-primary font-weight-bold">Next Month Interest</th>
            <td class="text-right text-primary font-weight-bold" style="font-size: 1.2rem;" id="forceCloseAmount">
                <i class="fas fa-spinner fa-spin"></i> Calculating...
            </td>
        </tr>
    </table>
    <button type="button" class="btn btn-sm btn-info btn-block" id="recalculateBtn">
        <i class="fas fa-sync-alt mr-1"></i> Recalculate Force Close Amount
    </button>
</div>
```

---

## 5. View - Hidden Form Field

**File:** `application/views/member/loans/request_foreclosure.php` (Lines ~226-227)

```html
<form action="<?= site_url('member/loans/request_foreclosure/' . $loan->id) ?>" method="post">
    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
    <input type="hidden" name="closure_type" id="closureTypeInput" value="regular">
```

---

## 6. JavaScript - AJAX Handler

**File:** `application/views/member/loans/request_foreclosure.php` (Lines ~252-322)

```javascript
<script>
$(function() {
    const loanId = <?= $loan->id ?>;
    const currencySymbol = '<?= isset($settings['currency_symbol']) ? $settings['currency_symbol'] : get_currency_symbol() ?>';
    
    // Handle closure type change
    $('.closure-type').on('change', function() {
        const closureType = $(this).val();
        $('#closureTypeInput').val(closureType);
        
        if (closureType === 'force_close') {
            $('#regularDisplay').hide();
            $('#forceCloseDisplay').show();
            calculateForceClose();
        } else {
            $('#forceCloseDisplay').hide();
            $('#regularDisplay').show();
        }
    });
    
    // Calculate Force Close Amount
    function calculateForceClose() {
        $.ajax({
            url: '<?= site_url("member/loans/calculate_force_close") ?>',
            type: 'POST',
            dataType: 'json',
            data: {
                loan_id: loanId,
                csrf_token: '<?= $this->security->get_csrf_hash() ?>'
            },
            success: function(response) {
                if (response.success) {
                    const amount = parseFloat(response.data.next_month_interest).toFixed(2);
                    const waivedInterest = parseFloat(response.data.amount_waived_interest).toFixed(2);
                    const waivedFines = parseFloat(response.data.amount_waived_fines).toFixed(2);
                    const savings = parseFloat(response.data.amount_waived_interest) + parseFloat(response.data.amount_waived_fines);
                    
                    $('#forceCloseAmount').html(currencySymbol + parseFloat(amount).toLocaleString('en-IN', {minimumFractionDigits: 2}));
                    
                    // Show savings message
                    if (savings > 0) {
                        let savingsMsg = '<div class="alert alert-success mt-2 mb-0"><i class="fas fa-thumbs-up mr-1"></i>';
                        savingsMsg += '<strong>You save: ' + currencySymbol + parseFloat(savings).toLocaleString('en-IN', {minimumFractionDigits: 2}) + '</strong>';
                        savingsMsg += ' by choosing force close!</div>';
                        $('#forceCloseDisplay').append(savingsMsg);
                    }
                } else {
                    $('#forceCloseAmount').html('<span class="text-danger"><i class="fas fa-exclamation-circle mr-1"></i>' + response.message + '</span>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error calculating force close:', error);
                $('#forceCloseAmount').html('<span class="text-danger">Error calculating amount</span>');
            }
        });
    }
    
    // Recalculate button
    $('#recalculateBtn').on('click', function() {
        $('#forceCloseAmount').html('<i class="fas fa-spinner fa-spin"></i> Recalculating...');
        calculateForceClose();
    });
    
    // Enable submit only when terms agreed
    $('#agree_terms').on('change', function() {
        $('#submitBtn').prop('disabled', !this.checked);
    });
    $('#submitBtn').prop('disabled', true);
    
    // Add tooltips
    $('[title]').tooltip({placement: 'top', trigger: 'hover'});
});
</script>
```

---

## 7. Route Configuration

**File:** `application/config/routes.php` (Added Line ~273)

```php
$route['member/loans/calculate_force_close'] = 'member/loans/calculate_force_close';
```

---

## 8. Database Migration

**File:** `application/migrations/025_add_closure_type_to_foreclosure.sql`

```sql
-- Add closure_type column if not exists
ALTER TABLE `loan_foreclosure_requests` 
ADD COLUMN `closure_type` ENUM('regular', 'force_close') 
NOT NULL DEFAULT 'regular' 
COMMENT 'Type of foreclosure: regular (full amount) or force_close (next month interest only)' 
AFTER `foreclosure_amount`;

-- Create index for faster filtering
ALTER TABLE `loan_foreclosure_requests`
ADD INDEX `idx_closure_type` (`closure_type`);
```

---

## 9. Updated Model - Process Foreclosure (Excerpt)

**File:** `application/models/Loan_model.php` (Lines ~2078-2120)

```php
if ($action === 'approve') {
    $update_data['status'] = 'approved';

    // Get loan details for breakdown calculation
    $loan = $this->db->where('id', $request->loan_id)->get('loans')->row();
    if (!$loan) {
        return ['success' => false, 'message' => 'Loan not found'];
    }

    // Check if this is a force close or regular foreclosure
    $is_force_close = (isset($request->closure_type) && $request->closure_type === 'force_close');
    
    // Get breakdown of foreclosure amount based on closure type
    if ($is_force_close) {
        $breakdown = $this->calculate_force_close_amount($request->loan_id);
        $settlement_note = 'Force Close: Next month interest only. All outstanding interest and fines waived.';
    } else {
        $breakdown = $this->calculate_foreclosure_amount($request->loan_id);
        $settlement_note = 'Regular Foreclosure Settlement';
    }
    
    // ... rest of payment processing ...
    
    // Create payment record with complete details
    $payment_data = [
        'loan_id' => $request->loan_id,
        'payment_date' => isset($payment_details['payment_date']) && !empty($payment_details['payment_date']) 
                         ? $payment_details['payment_date'] 
                         : date('Y-m-d'),
        'payment_type' => 'foreclosure',
        'payment_mode' => $payment_mode,
        'reference_number' => $payment_details['transaction_id'] ?? 'Foreclosure-Req#' . $request_id,
        'total_amount' => $is_force_close ? ($breakdown['next_month_interest'] ?? 0) : $request->foreclosure_amount,
        'principal_component' => $is_force_close ? 0 : floatval($breakdown['outstanding_principal'] ?? $loan->outstanding_principal ?? 0),
        'interest_component' => $is_force_close ? floatval($breakdown['next_month_interest'] ?? 0) : floatval($breakdown['outstanding_interest'] ?? 0),
        'fine_component' => 0, // Force close doesn't charge fines
        'outstanding_principal_after' => 0,
        'outstanding_interest_after' => 0,
        'payment_code' => 'FEC' . date('YmdHis'),
        'is_reversed' => 0,
        'narration' => $settlement_note . ': ' . $comments,
        'created_at' => date('Y-m-d H:i:s'),
        'created_by' => $admin_id
    ];
    
    // Insert payment record
    $insert_result = $this->db->insert('loan_payments', $payment_data);
}
```

---

## Summary of Changes

| Component | Type | Status |
|-----------|------|--------|
| calculate_force_close_amount() | New Function | ✅ Added |
| calculate_force_close() | New Controller Method | ✅ Added |
| Closure Type UI | New UI Section | ✅ Added |
| AJAX Handler | New JavaScript | ✅ Added |
| Route | New Route | ✅ Added |
| Database Migration | New Migration | ✅ Created |
| process_foreclosure_request() | Updated Function | ✅ Updated |
| Request Form | Updated Form | ✅ Updated |
| Debug Statements | Bug Fix | ✅ Fixed |

---

