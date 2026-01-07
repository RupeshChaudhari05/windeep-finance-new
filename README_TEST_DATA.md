# 📋 TEST DATA PACKAGE - COMPLETE SUMMARY

## ✅ What's Been Created For You

I've prepared everything you need to test **ALL features** of Windeep Finance, especially the transaction mapping feature.

## 📦 Package Contents

### 1. Test Data Files

#### ⭐ **START_HERE.md** (READ THIS FIRST!)
Your quick start guide with:
- 3-minute setup instructions
- All test scenarios explained
- Screen-by-screen testing checklist
- Video tutorial workflow

#### 🗄️ **simple_test_data.sql**
Clean, working test data:
- 5 members (TEST001 to TEST005)
- 3 active loans with EMI schedules
- 10 unmapped bank transactions (for mapping tests)
- 1 overdue loan with fine
- All data uses "TEST" prefix for easy identification

#### 🗄️ **full_test_data_with_transactions.sql**
Comprehensive test data (if you want more):
- 15 members (MEMB000001 to MEMB000015)
- 7 active loans with various statuses
- 20 unmapped bank transactions
- Multiple fines, guarantors, payment history
- Complex scenarios for thorough testing

### 2. Data Loaders

#### 🌐 **load_simple_test_data.php** (RECOMMENDED)
Beautiful browser interface:
- One-click data loading
- Visual summary with tables
- Shows all loans, members, transactions
- Color-coded status badges
- Next steps guide

#### 🌐 **load_test_data.php**
For full comprehensive data

### 3. Bank Statement Files

#### 📊 **bank_statement_jan2025.xlsx**
Excel file with 20 transactions:
- Ready to upload via Bank → Import
- Mix of EMI payments, savings, fines
- Includes special cases (unknown, split, overpayment)
- Tests auto-detection feature

#### 📊 **bank_statement_for_import.csv**
CSV version (if Excel doesn't work)

### 4. Documentation

#### 📖 **TEST_DATA_GUIDE.md**
Comprehensive 500-line guide:
- Complete data breakdown
- All 10 testing scenarios
- Screen-by-screen checklist
- Bug verification steps
- Troubleshooting section

## 🚀 Quick Start (Choose One)

### Option A: Simple Test Data (Recommended for Quick Testing)
```
1. Open browser: http://localhost/windeep_finance/database/load_simple_test_data.php
2. Wait 5 seconds - data loads automatically
3. See beautiful summary with all details
4. Start testing!
```

**Gets you:**
- 5 members
- 3 loans (1 overdue)
- 10 unmapped transactions
- Ready in 30 seconds

### Option B: Full Test Data (For Comprehensive Testing)
```
1. Open browser: http://localhost/windeep_finance/database/load_test_data.php
2. Wait 10-15 seconds
3. See comprehensive data summary
4. More scenarios to test
```

**Gets you:**
- 15 members
- 7 loans (various statuses)
- 20 unmapped transactions
- All edge cases covered

### Option C: Manual SQL (For Database Experts)
```sql
-- Via phpMyAdmin or command line
SOURCE simple_test_data.sql;
-- or
SOURCE full_test_data_with_transactions.sql;
```

## 🎯 What You Can Test

### 🏦 Bank Transaction Mapping (YOUR MAIN NEED!)

#### Test Case 1: Simple EMI Payment
```
Transaction: TEST-UTR-001 (₹8,884.88)
Description: "NEFT-Rajesh K-EMI Payment"
Action: Map to TESTLN-001, EMI #7
Expected: EMI marked paid, loan balance updated
```

#### Test Case 2: Phone Number Detection
```
Transaction: TEST-UTR-002 (₹7,065.09)
Description: "IMPS-9876543211-Priya Payment"
Action: System auto-detects Priya by phone
Expected: Shows Priya as detected member
```

#### Test Case 3: Split Payment ⭐ (MAIN FEATURE)
```
Transaction: TEST-UTR-006 (₹15,000)
Description: "Combined-TEST001-Multiple"
Action: Split across 2 EMIs:
  - ₹8,884.88 → TESTLN-001 EMI #7
  - ₹6,115.12 → TESTLN-001 EMI #8 (partial)
Expected: Both EMIs updated, transaction fully mapped
```

#### Test Case 4: Unknown Sender
```
Transaction: TEST-UTR-004 (₹5,000)
Description: "RTGS-Unknown Sender"
Action: Manual search and map
Expected: Search works, can map to any member/loan
```

#### Test Case 5: Overpayment
```
Transaction: TEST-UTR-005 (₹10,000)
Description: "NEFT-TESTLN-001-Advance"
Action: Map to TESTLN-001
Expected: Pays EMI #7, excess goes to EMI #8
```

#### Test Case 6: Fine Payment
```
Transaction: TEST-UTR-007 (₹250)
Description: "Fine-Priya-Late Payment"
Action: Map to Priya's pending fine
Expected: Fine marked paid
```

#### Test Case 7: Savings Deposit
```
Transaction: TEST-UTR-008 (₹5,000)
Description: "Savings-TEST004-Deposit"
Action: Map to savings account
Expected: Savings balance increases
```

#### Test Case 8: Partial Payment
```
Transaction: TEST-UTR-009 (₹3,500)
Description: "Partial-9876543214-Payment"
Action: Partial EMI payment
Expected: EMI partially paid, balance remains
```

### 📊 All Other Screens

✅ Members list & details  
✅ Loan applications  
✅ Loan approval workflow  
✅ EMI schedules  
✅ Payment recording (manual)  
✅ Skip EMI feature  
✅ Fine management  
✅ Reports (portfolio, collection, trial balance)  
✅ Member ledger  
✅ Savings accounts  

## 📁 File Locations

```
windeep_finance/database/
├── START_HERE.md ⭐ (Read this first!)
├── load_simple_test_data.php (Run this!)
├── simple_test_data.sql
├── bank_statement_jan2025.xlsx (Upload this!)
├── bank_statement_for_import.csv
├── TEST_DATA_GUIDE.md (Detailed guide)
├── full_test_data_with_transactions.sql
└── load_test_data.php
```

## 🎬 Testing Workflow

### Phase 1: Load Data (2 min)
1. Run `load_simple_test_data.php`
2. Review summary
3. Login to admin panel

### Phase 2: Explore (5 min)
1. View Members → See TEST001-TEST005
2. View Loans → See TESTLN-001, 002, 003
3. View one EMI schedule
4. Note overdue loan TESTLN-002

### Phase 3: Bank Import (3 min)
1. Bank → Bank Statements → Import
2. Upload `bank_statement_jan2025.xlsx`
3. View imported transactions
4. See unmapped count

### Phase 4: Transaction Mapping (15 min)
Test all 8 scenarios above, focusing on:
- **Split payment** (most important!)
- Auto-detection
- Unknown handling
- Overpayment

### Phase 5: Verification (5 min)
1. Check EMI schedules updated
2. View payment history
3. Run trial balance
4. Check member ledger

**Total Time:** ~30 minutes for thorough testing

## 🔍 Member Credentials

### Test Members
| Code | Name | Phone | For Testing |
|------|------|-------|-------------|
| TEST001 | Rajesh Kumar | 9876543210 | Regular payments |
| TEST002 | Priya Sharma | 9876543211 | Overdue + fine |
| TEST003 | Amit Patel | 9876543212 | Regular |
| TEST004 | Sneha Reddy | 9876543213 | No loans |
| TEST005 | Vikram Singh | 9876543214 | Partial payment |

### Test Loans
| Loan | Member | Status | For Testing |
|------|--------|--------|-------------|
| TESTLN-001 | Rajesh | Active | Regular, split payment |
| TESTLN-002 | Priya | Overdue | Fine, overdue EMI |
| TESTLN-003 | Amit | Active | Regular payment |

## ✨ Key Features You'll Test

### 1. **Split Payment Mapping** ⭐⭐⭐
This is what you specifically asked for! Transaction TEST-UTR-006 (₹15,000) demonstrates:
- One bank transaction
- Split across multiple loan EMIs
- Proper allocation tracking
- Transaction mapping table usage

### 2. Member Auto-Detection
System detects members from transaction description:
- By member code (TEST001)
- By phone number (9876543211)
- By loan number (TESTLN-001)
- By name matching

### 3. UTR Uniqueness (Bug #10 Fix)
Try importing duplicate UTR - should fail!

### 4. Payment Allocation (Bug #16 Fix)
Payments follow: Interest → Principal → Fine order

### 5. Fine Prevention (Bug #7 Fix)
Can't create duplicate fine on same date

### 6. Running Balance (Bug #13 Fix)
Database locking prevents race conditions

## 🐛 Bug Verification

All fixed bugs can be verified with this test data:

- [x] **Bug #4** - EMI principal sums exactly
- [x] **Bug #7** - Fine duplicates prevented
- [x] **Bug #10** - UTR uniqueness enforced
- [x] **Bug #13** - Race conditions handled
- [x] **Bug #16** - Correct payment allocation
- [x] **Bug #17** - Skip EMI recalculation
- [x] **Bug #1** - Date validations
- [x] **Bug #2** - Savings ratio check
- [x] **Bug #11** - Split payment works
- [x] **Security** - bcrypt, rate limiting, CSRF

## 💡 Pro Tips

### To Clean Test Data Anytime:
```sql
DELETE FROM members WHERE member_code LIKE 'TEST%';
-- All related data deleted via foreign keys
```

### To Add More Test Transactions:
```sql
INSERT INTO bank_transactions (...) VALUES (...);
```

### To Check Unmapped Count:
```sql
SELECT COUNT(*) FROM bank_transactions 
WHERE mapping_status = 'unmapped';
```

### To Verify Payment Allocation:
```sql
SELECT payment_code, total_amount, 
       interest_component, principal_component, fine_component
FROM loan_payments WHERE loan_id = [loan_id]
ORDER BY payment_date DESC;
```

## 🎯 Success Criteria

You'll know testing is complete when you can:
✅ Map all 10 test transactions successfully  
✅ Split payment TEST-UTR-006 works correctly  
✅ Auto-detection identifies members  
✅ Unknown transactions can be manually mapped  
✅ Overpayments handled properly  
✅ Reports show correct data  
✅ Trial balance = ₹0.00  

## 🚨 Troubleshooting

### Database Connection Error
```php
// Check database/load_simple_test_data.php
$host = 'localhost';  // Correct?
$user = 'root';       // Correct?
$pass = '';           // Your password?
```

### Excel Upload Fails
1. Use CSV instead: `bank_statement_for_import.csv`
2. Check `uploads/bank_statements/` folder exists
3. Check folder permissions (writable)
4. Verify PhpSpreadsheet installed: `composer install`

### No Data After Loading
1. Check MySQL is running in XAMPP
2. Database 'windeep_finance' exists
3. Tables exist (run schema.sql first)
4. Check browser console for errors

### Split Payment Not Working
1. Verify `transaction_mappings` table exists
2. Check `Bank_model.php` has `map_split_payment()` function
3. Verify Bug #11 fix is applied

## 📞 Support Files

All these files work together:

1. **START_HERE.md** - Your entry point
2. **load_simple_test_data.php** - Easiest way to load
3. **simple_test_data.sql** - The actual data
4. **bank_statement_jan2025.xlsx** - For import testing
5. **TEST_DATA_GUIDE.md** - Detailed scenarios

## 🎉 You're Ready!

Everything is prepared for comprehensive testing. Just:

```
Step 1: http://localhost/windeep_finance/database/load_simple_test_data.php
Step 2: Login to admin panel
Step 3: Start testing!
```

**Focus on:** Split payment mapping (TEST-UTR-006) - that's your main requirement!

---

**Created:** January 6, 2026  
**Status:** ✅ Ready to Use  
**Test Duration:** ~30 minutes  
**Coverage:** All screens + All bug fixes  

🚀 **GO TEST EVERYTHING!** 🚀
