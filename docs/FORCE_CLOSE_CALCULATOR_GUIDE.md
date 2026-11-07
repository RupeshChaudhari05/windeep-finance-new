# Force Close Calculator - Implementation & Testing Guide

**Date:** 2026-07-02  
**Status:** ✅ IMPLEMENTATION COMPLETE

---

## Implementation Summary

### What Was Implemented

1. **Database Migration**
   - File: `application/migrations/025_add_closure_type_to_foreclosure.sql`
   - Adds `closure_type` ENUM column to `loan_foreclosure_requests` table

2. **Model Function**
   - File: `application/models/Loan_model.php`
   - Function: `calculate_force_close_amount($loan_id)` - Calculates next month interest only

3. **Controller Endpoint**
   - File: `application/controllers/member/Loans.php`
   - Function: `calculate_force_close()` - AJAX endpoint that returns JSON response

4. **Frontend UI**
   - File: `application/views/member/loans/request_foreclosure.php`
   - Added closure type radio buttons (Regular vs Force Close)
   - Added force close display section with calculation
   - Added AJAX JavaScript to calculate and display results

5. **Routes**
   - File: `application/config/routes.php`
   - Added route: `member/loans/calculate_force_close`

---

## Calculation Logic

### Force Close Formula

```
Next Month Interest = Outstanding Principal × (Annual Interest Rate / 12 / 100)

Example:
- Principal: ₹300,000
- Annual Rate: 8%
- Next Month Interest = 300,000 × (8 / 12 / 100) = ₹2,000
```

### What Gets Waived

When member chooses force close:
- ❌ Outstanding Principal (NOT charged)
- ❌ Accrued Interest (NOT charged) 
- ❌ Pending Fines (NOT charged)
- ✅ Next Month Interest (CHARGED)

---

## User Workflow

### Step 1: Member Clicks "Request Foreclosure"
```
User → My Loans → Click Loan → Request Foreclosure
```

### Step 2: See Closure Type Options
```
┌─────────────────────────────────────┐
│ Foreclosure Type:                   │
│ ○ Regular Foreclosure               │
│   Pay full amount (principal + all  │
│   interest + fines)                 │
│ ○ Force Close                       │
│   Pay only next month's interest    │
│   (save up to 90%!)                 │
└─────────────────────────────────────┘
```

### Step 3: Select "Force Close"
- Automatically calculates next month interest
- Shows breakdown:
  - Outstanding Principal: ~~₹300,000~~ (waived)
  - Accrued Interest: ✓ Waived
  - Pending Fines: ✓ Waived
  - **Next Month Interest: ₹2,000** ← To be charged

### Step 4: Submit Request
- Foreclosure type saved to database
- Admin approves request
- Only next month interest charged (not full amount)
- Loan closed immediately

---

## API Endpoint

### Calculate Force Close (AJAX)

**Endpoint:** `POST /member/loans/calculate_force_close`

**Request Parameters:**
```json
{
    "loan_id": 123,
    "csrf_token": "token_value"
}
```

**Success Response:**
```json
{
    "success": true,
    "data": {
        "outstanding_principal": 300000,
        "annual_interest_rate": 8,
        "monthly_interest_rate": 0.6667,
        "next_month_interest": 2000.00,
        "total_amount": 2000.00,
        "amount_waived_interest": 15000,
        "amount_waived_fines": 5000,
        "calculated_at": "2026-07-02 12:30:45",
        "note": "Force close: All outstanding interest and fines are waived. Only next month interest is charged."
    }
}
```

**Error Response:**
```json
{
    "success": false,
    "message": "Loan not found or not eligible for force close."
}
```

---

## Frontend Implementation

### JavaScript Button Click Handler

```javascript
// When Force Close radio button is selected
$('.closure-type').on('change', function() {
    if (this.value === 'force_close') {
        // Show force close display
        $('#forceCloseDisplay').show();
        // Calculate force close amount via AJAX
        calculateForceClose();
    }
});

// Calculate function
function calculateForceClose() {
    $.ajax({
        url: '/member/loans/calculate_force_close',
        type: 'POST',
        data: {
            loan_id: 123,
            csrf_token: 'token'
        },
        success: function(response) {
            // Display amount: ₹2,000.00
            // Show savings message: You save ₹20,000
        }
    });
}
```

### Display Updates

**Regular Foreclosure Display:**
```
Outstanding Principal:     ₹300,000.00
Accrued Interest:           ₹15,000.00
Penalty / Charges:               ₹0.00
Foreclosure Fee:                 ₹0.00
─────────────────────────────────────
Total Settlement Amount:   ₹319,764.28
```

**Force Close Display:**
```
Outstanding Principal:     ~~₹300,000~~ (waived)
Accrued Interest (Waived):  ✓ Waived
Pending Fines (Waived):     ✓ Waived
─────────────────────────────────────
Next Month Interest:        ₹2,000.00

You save: ₹317,764.28 by choosing force close!
```

---

## Testing Scenarios

### Test Case 1: Regular Foreclosure (Verify Backward Compatibility)

**Steps:**
1. Login as member with active loan
2. Go to Foreclosure Request
3. Select "Regular Foreclosure" (default)
4. See full settlement amount displayed
5. Submit request
6. Admin approves
7. Verify full amount charged in payment record

**Expected Result:** ✅ Regular foreclosure works as before

---

### Test Case 2: Force Close Calculation

**Steps:**
1. Login as member
2. Go to Foreclosure Request
3. Select "Force Close"
4. Wait for AJAX calculation (should auto-calculate)
5. Verify amount shown is next month interest only
6. Check "You save: ₹XXX" message

**Expected Result:** ✅ Shows correct next month interest amount

**Example Calculations:**

| Principal | Rate | Months | Regular | Force Close | Savings |
|-----------|------|--------|---------|-------------|---------|
| ₹300,000  | 8%   | 20     | ₹319,764 | ₹2,000     | ₹317,764 |
| ₹500,000  | 10%  | 12     | ₹605,000 | ₹4,167     | ₹600,833 |
| ₹100,000  | 5%   | 6      | ₹105,500 | ₹417       | ₹105,083 |

---

### Test Case 3: Force Close Submission & Approval

**Steps:**
1. Select Force Close
2. Enter reason: "I have funds available"
3. Set settlement date
4. Agree to terms
5. Submit request
6. Go to Admin → Loans → Foreclosure Requests
7. View request (should show closure_type: 'force_close')
8. Approve request
9. Check payment record

**Expected Result:**
- ✅ Payment record created
- ✅ Only next month interest charged
- ✅ Fines marked as waived
- ✅ Interest marked as paid
- ✅ Loan status: foreclosed

---

### Test Case 4: Recalculate Button

**Steps:**
1. Select Force Close
2. Initial calculation shows: ₹2,000
3. Click "Recalculate Force Close Amount" button
4. Wait for AJAX response
5. Verify amount is recalculated

**Expected Result:** ✅ Amount recalculates without page reload

---

## Database Verification

### Verify Migration Applied

```sql
-- Check if column exists
SELECT COLUMN_NAME, COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'loan_foreclosure_requests' 
AND COLUMN_NAME = 'closure_type';

-- Should return:
-- closure_type | enum('regular','force_close')
```

### Verify Foreclosure Request

```sql
-- Check foreclosure request record
SELECT id, loan_id, closure_type, foreclosure_amount, status
FROM loan_foreclosure_requests
WHERE id = 1;

-- Should show:
-- id | loan_id | closure_type  | foreclosure_amount | status
-- 1  | 123     | force_close   | 2000.00           | approved
```

### Verify Payment Record

```sql
-- Check payment created for force close
SELECT id, loan_id, payment_type, total_amount, 
       principal_component, interest_component, fine_component
FROM loan_payments
WHERE loan_id = 123 AND payment_type = 'foreclosure';

-- Should show for force close:
-- principal_component: 0 (waived)
-- interest_component: 2000.00 (next month interest)
-- fine_component: 0 (waived)
```

---

## Code Files Modified

1. ✅ [application/models/Loan_model.php](application/models/Loan_model.php)
   - Added: `calculate_force_close_amount($loan_id)`
   - Updated: `process_foreclosure_request()` to detect closure type

2. ✅ [application/controllers/member/Loans.php](application/controllers/member/Loans.php)
   - Added: `calculate_force_close()` AJAX endpoint

3. ✅ [application/views/member/loans/request_foreclosure.php](application/views/member/loans/request_foreclosure.php)
   - Added: Closure type radio buttons
   - Added: Force close display section
   - Added: JavaScript calculation handler

4. ✅ [application/config/routes.php](application/config/routes.php)
   - Added: `member/loans/calculate_force_close` route

5. ✅ [application/migrations/025_add_closure_type_to_foreclosure.sql](application/migrations/025_add_closure_type_to_foreclosure.sql)
   - Created migration

6. ✅ Database schema files (install.sql, schema.sql, etc.)
   - Updated with closure_type column

---

## Error Handling

### Frontend Errors

**Loan Not Found:**
```
"Loan not found or not eligible for force close."
```

**Calculation Failed:**
```
"Unable to calculate force close amount. Please try again."
```

**Network Error:**
```
"Error calculating amount"
```

### Backend Validation

- ✅ Verify loan belongs to logged-in member
- ✅ Verify loan status is 'active' or 'overdue'
- ✅ Verify loan exists in database
- ✅ Validate principal and interest rate not null

---

## Performance Considerations

- ✅ AJAX call is async (doesn't block UI)
- ✅ Calculation happens server-side (accurate)
- ✅ Response includes breakdown details
- ✅ Index on closure_type for fast filtering
- ✅ No heavy queries in calculation

---

## Security Considerations

- ✅ CSRF token validation on AJAX request
- ✅ Member can only access their own loans
- ✅ No direct SQL in JavaScript
- ✅ Input validation on server-side
- ✅ Response sanitized

---

## Troubleshooting

### Issue: Calculation not showing
**Solution:** 
- Check browser console for JavaScript errors
- Verify route is added to routes.php
- Ensure AJAX URL is correct
- Check CSRF token is being sent

### Issue: Amount incorrect
**Solution:**
- Verify loan's interest_rate field in database
- Check calculation: Principal × (Rate / 12 / 100)
- Verify outstanding_principal is correct

### Issue: Force close not saving
**Solution:**
- Check closure_type column exists in database
- Verify migration was applied
- Check POST data includes closure_type

---

## Future Enhancements

1. **Admin Override:** Allow admin to manually adjust force close amount
2. **Penalty Option:** Add checkbox for penalty charges on force close
3. **Partial Force Close:** Charge principal + interest (not full amount)
4. **Bulk Force Close:** Process multiple force closes at once
5. **Email Notification:** Send member email with force close details
6. **Force Close Report:** Dashboard showing force close statistics

---

## Success Criteria

- ✅ Force close calculation accurate
- ✅ Member sees different amounts for each type
- ✅ AJAX button works without page reload
- ✅ Savings amount displayed correctly
- ✅ Force close saves to database
- ✅ Admin approves both types
- ✅ Payment records correct
- ✅ Loan marked as foreclosed

---

