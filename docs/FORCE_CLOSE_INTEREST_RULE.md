# Force Close Interest Rule - Implementation Guide

## Overview

This document explains the foreclosure/force close calculation system and the new rule for charging only next month's interest on force closes.

---

## Current Foreclosure Calculation

**Current Formula (Full Foreclosure):**
```
Total Foreclosure Amount = Outstanding Principal 
                         + Outstanding Interest (accrued to date)
                         + Prepayment Charge (0% by default)
                         + Pending Fines/Charges
```

**Example Breakdown:**
```
Outstanding Principal:     ₹300,000.00
Outstanding Interest:       ₹15,000.00
Prepayment Charge (0%):           ₹0.00
Pending Fines:               ₹4,764.28
────────────────────────────────────
Total Due for Full Close:  ₹319,764.28
```

**Current Implementation Location:**
- File: [application/models/Loan_model.php](application/models/Loan_model.php#L1906)
- Method: `calculate_foreclosure_amount($loan_id)`

---

## New Rule: Force Close with Next Month Interest Only

When a member chooses **Force Close** (instead of regular foreclosure), they pay:
- **Only the next month's interest** (all principal + past interest + fines are waived)

### Formula for Force Close

```
Next Month Interest = Outstanding Principal × (Annual Interest Rate / 12 / 100)
```

### Example

For same loan with 8% annual interest:
```
Outstanding Principal:        ₹300,000.00
Annual Interest Rate:               8%
Monthly Interest Rate:         8 / 12 / 100 = 0.00667

Force Close Amount = ₹300,000 × 0.00667 = ₹2,000.00
```

**Benefit to Member:** Save ₹317,764.28 by paying only ₹2,000!

---

## Implementation Details

### 1. Database Schema Changes

Add `closure_type` column to `loan_foreclosure_requests` table:

```sql
ALTER TABLE loan_foreclosure_requests 
ADD COLUMN closure_type ENUM('regular', 'force_close') DEFAULT 'regular' 
AFTER foreclosure_amount;
```

### 2. Calculation Functions

#### A. Calculate Next Month Interest Only

```php
/**
 * Calculate Force Close Amount (next month interest only)
 * @param int $loan_id
 * @return array With keys: principal, next_month_interest, total_amount
 */
public function calculate_force_close_amount($loan_id) {
    $loan = $this->db->where('id', $loan_id)->get('loans')->row();
    if (!$loan) return false;

    $outstanding_principal = $loan->outstanding_principal ?? 0;
    $annual_rate = $loan->interest_rate ?? 0;
    
    // Monthly interest = Principal × (Annual Rate / 12 / 100)
    $monthly_interest = $outstanding_principal * ($annual_rate / 12 / 100);
    
    return [
        'outstanding_principal' => $outstanding_principal,
        'annual_interest_rate' => $annual_rate,
        'next_month_interest' => round($monthly_interest, 2),
        'total_amount' => round($monthly_interest, 2),
        'type' => 'force_close',
        'calculated_at' => date('Y-m-d H:i:s')
    ];
}
```

#### B. Updated Main Calculation Function

```php
/**
 * Calculate Foreclosure Amount
 * Supports both regular foreclosure and force close
 */
public function calculate_foreclosure_amount($loan_id, $closure_type = 'regular') {
    if ($closure_type === 'force_close') {
        return $this->calculate_force_close_amount($loan_id);
    }
    
    // Default: regular foreclosure calculation (existing code)
    return $this->calculate_foreclosure_amount_full($loan_id);
}
```

### 3. Processing Logic Changes

When processing a force close approval:

```php
// In process_foreclosure_request()
if ($request->closure_type === 'force_close') {
    // Charge only next month's interest
    $settlement_amount = $this->calculate_force_close_amount($loan->id);
    
    // Mark principal as paid in full (even though they're only paying interest)
    $outstanding_principal_after = 0;
    $outstanding_interest_after = 0;
    
    // Waive all fines (not charged in force close)
    // Waive all accumulated interest (only next month charged)
}
```

---

## User Interface Changes

### Request Form

**Option 1: Radio Buttons**
```
Closure Type:
○ Regular Foreclosure (pay full amount including all interest)
○ Force Close (pay only next month's interest - faster closure)
```

**Option 2: Inline Toggle**
```
Closure Method: [Regular ▼] 
Show total amount: ₹319,764.28
```

### Settlement Breakdown Display

**Regular Foreclosure:**
```
Outstanding Principal:     ₹300,000.00
Outstanding Interest:       ₹15,000.00
Prepayment Charge:               ₹0.00
Pending Fines:               ₹4,764.28
Total Due:                 ₹319,764.28
```

**Force Close:**
```
Outstanding Principal:     ₹300,000.00
Next Month Interest:         ₹2,000.00
────────────────────
Total Due:                   ₹2,000.00

Note: By choosing force close, all outstanding interest 
and fines are waived. Only next month's interest is charged.
```

---

## Code Implementation Files

### Files to Modify

1. **Database**
   - `database/install.sql` - Update table creation
   - `database/schema.sql` - Update schema
   - Create migration: `application/migrations/025_add_closure_type_to_foreclosure.sql`

2. **Model** - [application/models/Loan_model.php](application/models/Loan_model.php)
   - Add `calculate_force_close_amount()` function
   - Update `calculate_foreclosure_amount()` to accept `$closure_type` parameter
   - Update `process_foreclosure_request()` to handle force close

3. **Controller** - [application/controllers/member/Loans.php](application/controllers/member/Loans.php)
   - Update request form to accept closure type selection
   - Update settlement calculation display

4. **Views**
   - [application/views/member/loans/request_foreclosure.php](application/views/member/loans/request_foreclosure.php) - Add closure type selection
   - [application/views/admin/loans/view_foreclosure_request.php](application/views/admin/loans/view_foreclosure_request.php) - Show which type was requested

---

## Examples

### Example 1: Regular Foreclosure vs Force Close

**Loan Details:**
- Principal: ₹500,000
- Interest Rate: 8% p.a.
- Outstanding Interest: ₹25,000
- Pending Fines: ₹5,000

**Regular Foreclosure:** ₹530,000 total
**Force Close:** ₹3,333.33 (next month interest only)

### Example 2: Loan Closure Workflow

**Scenario:** Member wants to close loan immediately

1. Member clicks "Request Foreclosure"
2. Selects "Force Close" option
3. System shows: "Pay ₹2,000 to close, all interest waived"
4. Member requests foreclosure
5. Admin approves
6. Loan status changes to "closed"
7. Only next month's interest is charged

---

## Validation Rules

- Force close is only available for active/overdue loans
- Force close amount is always calculated fresh at approval time
- Cannot change closure type after request is submitted
- Force close cannot be used if loan is already in final month
- Member can only have one pending foreclosure request per loan

---

## Reporting & Analytics

### Dashboard Impact

- Add column showing foreclosure type in requests list
- Track savings from force close usage
- Report on force close vs regular foreclosure trends

### Audit Trail

All changes logged with:
- `closure_type`: regular or force_close
- `settlement_amount`: calculated amount
- `amount_waived`: for force close, show all waived interest/fines

---

## Testing Checklist

- [ ] Create migration and apply to database
- [ ] Test regular foreclosure calculation (should remain unchanged)
- [ ] Test force close calculation with various interest rates
- [ ] Test force close approval (marks loan as closed with minimal payment)
- [ ] Verify fines/interest are properly marked as waived
- [ ] Test with multiple loans and interest rates
- [ ] Verify audit logs capture closure type

---

## Rollback Plan

If needed to rollback:

```sql
-- Remove the column
ALTER TABLE loan_foreclosure_requests DROP COLUMN closure_type;

-- Revert code to original calculate_foreclosure_amount() function
```

---

## Future Enhancements

1. **Partial Force Close:** Allow paying interest and waiving only past interest
2. **Penalties:** Add option to charge penalties for force close
3. **Approval Rules:** Different approval workflows for regular vs force close
4. **Notifications:** Email member about force close benefits
5. **Analytics:** Dashboard widget for force close usage trends

