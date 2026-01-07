# 🧪 TEST DATA GUIDE

## Overview
This guide explains the comprehensive test data created for testing all features of Windeep Finance system.

## 📂 Files Created

1. **full_test_data_with_transactions.sql** - Complete SQL script with all test data
2. **bank_statement_jan2025.xlsx** - Excel file for bank statement import testing
3. **bank_statement_for_import.csv** - CSV version of bank statement
4. **load_test_data.php** - PHP script to load data easily via browser

## 🚀 Quick Start

### Option 1: Using PHP Script (Recommended)
```
1. Open browser
2. Go to: http://localhost/windeep_finance/database/load_test_data.php
3. Wait for completion
4. View summary
```

### Option 2: Using MySQL Command Line
```bash
cd c:\xampp_new\htdocs\windeep_finance\database
mysql -u root -p windeep_finance < full_test_data_with_transactions.sql
```

### Option 3: Using phpMyAdmin
```
1. Open phpMyAdmin
2. Select 'windeep_finance' database
3. Click 'Import' tab
4. Choose file: full_test_data_with_transactions.sql
5. Click 'Go'
```

## 📊 Test Data Summary

### 15 Members Created
| Member Code | Name | Status | Savings Balance | Purpose |
|-------------|------|--------|-----------------|---------|
| MEMB000001 | Rajesh Kumar | Active | ₹50,000 | Regular payments, good credit |
| MEMB000002 | Priya Sharma | Active | ₹75,000 | Has overdue EMI with fine |
| MEMB000003 | Amit Patel | Active | ₹35,000 | Regular education loan |
| MEMB000004 | Sneha Reddy | Active | ₹120,000 | New loan, just started |
| MEMB000005 | Vikram Singh | Active | ₹42,000 | Partial payments, testing |
| MEMB000006 | Anjali Mehta | Active | ₹38,000 | Irregular payments, fines |
| MEMB000007 | Rahul Verma | Active | ₹28,000 | Business loan, fresh |
| MEMB000008 | Kavita Desai | Active | ₹62,000 | No active loans |
| MEMB000009 | Suresh Nair | Active | ₹15,000 | New member, pending loan |
| MEMB000010 | Deepa Iyer | Active | ₹25,000 | New member, pending loan |
| MEMB000011 | Manoj Joshi | Active | ₹85,000 | Guarantor for loans |
| MEMB000012 | Pooja Agarwal | Active | ₹32,000 | Pending application review |
| MEMB000013 | Sanjay Kapoor | Active | ₹500,000 (FD) | High savings, guarantor |
| MEMB000014 | Neha Gupta | Active | ₹95,000 | Guarantor |
| MEMB000015 | Arun Rao | Active | ₹125,000 | Guarantor |

### 7 Active Loans
| Loan Number | Member | Principal | EMI | Tenure | Status | Paid EMIs | Pending | Overdue |
|-------------|--------|-----------|-----|--------|--------|-----------|---------|---------|
| LN2024010001 | Rajesh Kumar | ₹100,000 | ₹8,884.88 | 12 | Active | 6 | 6 | 0 |
| LN2024020001 | Priya Sharma | ₹150,000 | ₹7,065.09 | 24 | Overdue | 10 | 13 | 1 |
| LN2024030001 | Amit Patel | ₹80,000 | ₹3,695.78 | 24 | Active | 8 | 16 | 0 |
| LN2024040001 | Sneha Reddy | ₹200,000 | ₹6,545.35 | 36 | Active | 2 | 34 | 0 |
| LN2024050001 | Vikram Singh | ₹120,000 | ₹7,378.98 | 18 | Active | 5 | 11 | 2 |
| LN2024060001 | Anjali Mehta | ₹90,000 | ₹8,019.12 | 12 | Overdue | 5 | 6 | 1 |
| LN2024070001 | Rahul Verma | ₹250,000 | ₹8,538.95 | 36 | Active | 0 | 36 | 0 |

### 3 Pending Applications
| Application | Member | Amount | Purpose | Status |
|-------------|--------|--------|---------|--------|
| APP202412001 | Suresh Nair | ₹75,000 | Personal needs | Pending |
| APP202412002 | Deepa Iyer | ₹150,000 | Masters degree | Pending |
| APP202412003 | Pooja Agarwal | ₹50,000 | Business startup | Member Review |

### 20 Unmapped Bank Transactions
Perfect for testing the transaction mapping feature! Includes:

#### EMI Payments (8 transactions)
- **UTR1234567899** - ₹8,884.88 - Rajesh K Payment
- **UTR2234567899** - ₹7,065.09 - Priya Sharma EMI
- **UTR3234567899** - ₹3,695.78 - Amit Patel EMI
- **UTR4234567899** - ₹6,545.35 - Sneha Reddy EMI
- **UTR1234567810** - ₹7,378.98 - Vikram Singh EMI
- **UTR1234567811** - ₹8,019.12 - Anjali Mehta EMI
- **UTR1234567812** - ₹3,500.00 - Partial Payment
- **UTR1234567813** - ₹12,000.00 - Business Loan EMI

#### Special Cases
- **UTR5234567899** - ₹5,000.00 - Unknown Sender (No match found)
- **UTR0234567899** - ₹10,000.00 - Advance/Overpayment
- **UTR1134567899** - ₹15,000.00 - Combined payment (Split testing)
- **UTR1234567814** - ₹8,884.88 - Extra payment

#### Savings Deposits (2)
- **UTR6234567899** - ₹5,000.00 - Suresh Nair savings
- **UTR7234567899** - ₹10,000.00 - Deepa Iyer FD

#### Fine Payments (2)
- **UTR8234567899** - ₹250.00 - Priya fine
- **UTR9234567899** - ₹300.00 - Anjali penalty

#### Other Transactions (4)
- **UTR1234567815** - ₹2,000.00 - Regular savings
- **UTR1234567816** - ₹15,000.00 - Fixed deposit
- **UTR1234567817** - ₹500.00 - Processing fee
- **UTR1234567818** - ₹25,000.00 - Bulk payment

### 3 Active Fines
| Fine Code | Member | Type | Amount | Days Late | Status |
|-----------|--------|------|--------|-----------|--------|
| FIN-20241220-001 | Priya Sharma | Loan Late | ₹250.00 | 15 | Pending |
| FIN-20241015-002 | Anjali Mehta | Loan Late | ₹200.00 | 14 | Paid |
| FIN-20241225-003 | Anjali Mehta | Loan Late | ₹300.00 | 20 | Pending |

## 🧪 Testing Scenarios

### 1. Member Management
```
✓ View all 15 members
✓ Check member details
✓ View savings accounts
✓ View loan history
✓ Search by member code, name, phone
```

### 2. Loan Applications
```
✓ View 3 pending applications
✓ Approve/reject applications
✓ Check eligibility criteria
✓ Test savings balance requirement
✓ Loan-to-savings ratio validation
```

### 3. Loan Disbursement
```
✓ Disburse pending applications
✓ Test date validation (7-60 days)
✓ EMI schedule generation
✓ Verify installment accuracy
```

### 4. EMI Payment Processing
```
✓ Regular payment (full EMI)
✓ Partial payment
✓ Advance payment (multiple EMIs)
✓ Overpayment (excess amount handling)
✓ Late payment with fine
✓ Payment allocation order (Interest→Principal→Fine)
```

### 5. Bank Statement Import
```
✓ Upload bank_statement_jan2025.xlsx
✓ View imported transactions
✓ Auto-detection of members
✓ Manual mapping of unmapped transactions
✓ UTR uniqueness validation
✓ Duplicate transaction prevention
```

### 6. Transaction Mapping
```
✓ Map by member code (MEMB000001)
✓ Map by phone number (9876543211)
✓ Map by loan number (LN2024030001)
✓ Map by UTR search
✓ Split payment mapping (UTR1134567899 - ₹15,000 split across multiple loans)
✓ Unknown sender handling
```

### 7. Fine Management
```
✓ View pending fines
✓ Apply late payment fines
✓ Fine calculation (per day)
✓ Fine payment processing
✓ Waive fines (with approval)
✓ Duplicate fine prevention
```

### 8. Reports Testing
```
✓ Loan portfolio report
✓ Outstanding EMI report
✓ Overdue loans report
✓ Member ledger
✓ Collection report
✓ Fine report
✓ Trial balance
```

### 9. Skip EMI Feature
```
✓ Request skip for active loan
✓ Admin approval
✓ Schedule recalculation
✓ Interest adjustment
✓ Verify Bug #17 fix (correct interest recalculation)
```

### 10. Edge Cases
```
✓ Payment on overdue EMI
✓ Multiple partial payments
✓ Payment exceeding outstanding
✓ Duplicate UTR attempt
✓ Same-day fine duplicate
✓ Race condition in ledger (concurrent payments)
```

## 📝 Test Credentials

### Admin Login
```
Username: admin
Password: [Your existing admin password]
```

### Test Member Contacts
All members have phone numbers starting with 9876543210-9876543224

## 🎯 Testing Workflow

### Day 1: Basic Operations
1. ✓ Login as admin
2. ✓ View dashboard
3. ✓ Browse members list
4. ✓ View loan applications
5. ✓ Check active loans
6. ✓ View EMI schedules

### Day 2: Payment Processing
1. ✓ Record manual EMI payment
2. ✓ Test partial payment
3. ✓ Test advance payment
4. ✓ Verify payment allocation order
5. ✓ Check outstanding balance updates

### Day 3: Bank Transaction Mapping
1. ✓ Go to Bank > Bank Statements
2. ✓ Upload bank_statement_jan2025.xlsx
3. ✓ View imported transactions (20 total)
4. ✓ Check auto-detected members
5. ✓ Map transaction UTR1234567899 to LN2024010001
6. ✓ Map transaction UTR2234567899 to LN2024020001
7. ✓ Test split payment: UTR1134567899 (₹15,000)
   - Split ₹7,378.98 to LN2024050001 (EMI 6)
   - Split ₹7,378.98 to LN2024050001 (EMI 7)
8. ✓ Handle unknown sender: UTR5234567899
9. ✓ Process fine payments: UTR8234567899, UTR9234567899
10. ✓ Map savings deposits: UTR6234567899, UTR7234567899

### Day 4: Reports & Reconciliation
1. ✓ Generate loan portfolio report
2. ✓ View overdue loans
3. ✓ Check collection report
4. ✓ Run trial balance
5. ✓ Verify all balances match

### Day 5: Edge Cases & Security
1. ✓ Test duplicate UTR rejection
2. ✓ Test duplicate fine prevention
3. ✓ Test concurrent payment (race condition)
4. ✓ Test password change
5. ✓ Test rate limiting
6. ✓ Check audit logs

## 📊 Expected Results

### EMI Accuracy (Bug #4 Fix)
```sql
-- Verify principal sum matches exactly
SELECT 
    loan_number,
    principal_amount,
    SUM(principal_amount) as total_principal_in_schedule,
    principal_amount - SUM(principal_amount) as difference
FROM loans l
JOIN loan_installments li ON li.loan_id = l.id
GROUP BY l.id
HAVING difference != 0;

-- Should return 0 rows (no differences)
```

### Payment Allocation (Bug #16 Fix)
```sql
-- Check payment allocation order in payment history
SELECT 
    payment_code,
    total_amount,
    interest_component,
    principal_component,
    fine_component
FROM loan_payments
WHERE loan_id = [loan_id]
ORDER BY payment_date DESC;

-- Interest should be paid before principal
```

### UTR Uniqueness (Bug #10 Fix)
```sql
-- Try inserting duplicate UTR
INSERT INTO bank_transactions (utr_number, ...) 
VALUES ('UTR1234567899', ...);

-- Should fail with: Duplicate entry 'UTR1234567899' for key 'idx_utr_unique'
```

## 🐛 Bug Verification Checklist

- [x] **Bug #4** - EMI rounding: Principal sum = ₹100,000.00 exactly
- [x] **Bug #7** - Fine duplicates: Cannot create same fine on same date
- [x] **Bug #10** - UTR duplicates: Database constraint prevents duplicates
- [x] **Bug #13** - Race condition: SELECT FOR UPDATE locks in place
- [x] **Bug #16** - Payment order: Interest→Principal→Fine allocation
- [x] **Bug #17** - Skip EMI: Correct interest recalculation
- [x] **Bug #1** - Date validation: 7-60 days between disbursement and first EMI
- [x] **Bug #2** - Savings ratio: Enforced at approval
- [x] **Bug #5** - Flat interest: Consistent formula (years = tenure/12)
- [x] **Bug #11** - Split payment: map_split_payment() function works
- [x] **Bug #14** - Outstanding sync: Triggers auto-update from installments

## 📞 Support

If you encounter issues:
1. Check database connection in config/database.php
2. Ensure all migrations are applied
3. Verify MySQL server is running
4. Check error logs in application/logs/
5. Review security_logs table for audit trail

## 🎉 Success Criteria

Your system is production-ready when:
✅ All 15 members visible
✅ All 7 loans with correct schedules
✅ 20 bank transactions imported
✅ Transaction mapping works
✅ Split payment mapping works
✅ Payment allocation follows RBI order
✅ No duplicate UTR/fines possible
✅ Trial balance = ₹0.00
✅ All reports generate correctly
✅ Security features working (rate limiting, CSRF, bcrypt)

---

**Last Updated:** January 6, 2026  
**Version:** 1.0  
**Status:** ✅ Ready for Testing
