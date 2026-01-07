# 🚀 TEST DATA - QUICK START GUIDE

## What's Been Created

I've prepared comprehensive test data for you to test **ALL screens and features** of Windeep Finance:

### 📁 Files Created

1. **simple_test_data.sql** - Ready-to-use test data (5 members, 3 loans, 10 unmapped transactions)
2. **load_simple_test_data.php** - Browser-based loader with beautiful interface
3. **bank_statement_jan2025.xlsx** - Excel file with 20 bank transactions
4. **bank_statement_for_import.csv** - CSV version of the same
5. **TEST_DATA_GUIDE.md** - Comprehensive testing guide

## 🎯 Quick Start (3 Minutes)

### Step 1: Load Test Data
Open your browser and go to:
```
http://localhost/windeep_finance/database/load_simple_test_data.php
```

This will:
- ✅ Clean old test data (members starting with TEST*)
- ✅ Insert 5 test members
- ✅ Create 3 active loans with EMI schedules
- ✅ Add 10 unmapped bank transactions
- ✅ Show you a beautiful summary

### Step 2: Upload Bank Statement
1. Login to admin panel
2. Go to: **Bank → Bank Statements**
3. Click "Import Statement"
4. Upload: `database/bank_statement_jan2025.xlsx`
5. You'll see 20 transactions ready for mapping!

### Step 3: Start Testing!
Now you can test everything:
- Member screens
- Loan screens
- EMI schedules
- Payment recording
- **Transaction mapping** (the main feature you wanted to test)
- Reports
- Everything!

## 📊 What Data You Have

### Members (5)
| Code | Name | Phone | Savings | Status |
|------|------|-------|---------|--------|
| TEST001 | Rajesh Kumar | 9876543210 | ₹50,000 | Active |
| TEST002 | Priya Sharma | 9876543211 | ₹75,000 | Active |
| TEST003 | Amit Patel | 9876543212 | ₹35,000 | Active |
| TEST004 | Sneha Reddy | 9876543213 | ₹120,000 | Active |
| TEST005 | Vikram Singh | 9876543214 | ₹42,000 | Active |

### Loans (3 Active)
| Loan Number | Member | Amount | EMI | Paid/Pending/Overdue |
|-------------|--------|--------|-----|---------------------|
| TESTLN-001 | Rajesh Kumar | ₹100,000 | ₹8,884.88 | 6/6/0 |
| TESTLN-002 | Priya Sharma | ₹150,000 | ₹7,065.09 | 2/0/1 (overdue!) |
| TESTLN-003 | Amit Patel | ₹80,000 | ₹7,032.40 | 2/1/0 |

### Bank Transactions (10 Unmapped - Ready for Testing!)

#### EMI Payments
1. **TEST-UTR-001** - ₹8,884.88 - "NEFT-Rajesh K-EMI Payment"
   → Should map to TESTLN-001, EMI #7
   
2. **TEST-UTR-002** - ₹7,065.09 - "IMPS-9876543211-Priya Payment"
   → Should map to TESTLN-002 (can detect by phone)
   
3. **TEST-UTR-003** - ₹7,032.40 - "UPI-Amit@paytm-Loan EMI"
   → Should map to TESTLN-003

#### Special Cases
4. **TEST-UTR-004** - ₹5,000.00 - "RTGS-Unknown Sender"
   → Unknown - test manual search and mapping
   
5. **TEST-UTR-005** - ₹10,000.00 - "NEFT-TESTLN-001-Advance"
   → Overpayment - pays EMI #7 + part of #8
   
6. **TEST-UTR-006** - ₹15,000.00 - "Combined-TEST001-Multiple"
   → **SPLIT PAYMENT** test - split across multiple EMIs

#### Other Transactions
7. **TEST-UTR-007** - ₹250.00 - "Fine-Priya-Late Payment"
   → Fine payment for Priya
   
8. **TEST-UTR-008** - ₹5,000.00 - "Savings-TEST004-Deposit"
   → Savings deposit
   
9. **TEST-UTR-009** - ₹3,500.00 - "Partial-9876543214-Payment"
   → Partial EMI payment
   
10. **TEST-UTR-010** - ₹12,000.00 - "RTGS-Multiple Loans Split"
    → Another split payment test

## 🧪 Testing Scenarios

### Scenario 1: Simple EMI Mapping
```
1. Go to Bank → Unmapped Transactions
2. Find TEST-UTR-001 (₹8,884.88)
3. Click "Map Transaction"
4. Search for TESTLN-001 or Rajesh Kumar
5. Select EMI #7
6. Map and verify
```

### Scenario 2: Phone-Based Detection
```
1. Find TEST-UTR-002 (₹7,065.09)
2. System should auto-detect Priya by phone 9876543211
3. Map to her overdue EMI
4. Fine should also be paid
```

### Scenario 3: Split Payment (Main Feature!)
```
1. Find TEST-UTR-006 (₹15,000)
2. Click "Split Payment"
3. Add mapping: ₹8,884.88 → TESTLN-001 EMI #7
4. Add mapping: ₹6,115.12 → TESTLN-001 EMI #8 (partial)
5. Save split mapping
6. Verify both EMIs updated correctly
```

### Scenario 4: Unknown Sender
```
1. Find TEST-UTR-004 (₹5,000 unknown)
2. Manual search by member name/loan
3. Map to appropriate account
4. Test the search functionality
```

### Scenario 5: Overpayment
```
1. Find TEST-UTR-005 (₹10,000 advance)
2. Map to TESTLN-001
3. System should pay EMI #7 (₹8,884.88) 
4. Excess ₹1,115.12 should go to next EMI
5. Verify excess handling
```

## 📈 All Screens You Can Test

### ✅ Member Management
- [ ] Members list
- [ ] Member details
- [ ] Savings accounts
- [ ] Member ledger
- [ ] Member search

### ✅ Loan Management
- [ ] Loan applications
- [ ] Loan approvals
- [ ] Loan disbursement
- [ ] EMI schedules
- [ ] Payment recording
- [ ] Skip EMI feature
- [ ] Guarantor management

### ✅ Bank Transactions (YOUR MAIN FOCUS)
- [ ] Bank statement import
- [ ] Transaction listing
- [ ] Member auto-detection
- [ ] Manual transaction mapping
- [ ] **Split payment mapping** ⭐
- [ ] UTR search
- [ ] Duplicate prevention
- [ ] Mapping history

### ✅ Fine Management
- [ ] Fine listing
- [ ] Fine calculation
- [ ] Fine payments
- [ ] Fine waivers
- [ ] Late payment fines

### ✅ Reports
- [ ] Loan portfolio report
- [ ] Outstanding EMI report
- [ ] Collection report
- [ ] Member ledger report
- [ ] Trial balance
- [ ] Fine report

### ✅ Advanced Features
- [ ] Concurrent payment handling
- [ ] Partial payments
- [ ] Advance payments
- [ ] Payment allocation (Interest→Principal→Fine)
- [ ] Running balance accuracy
- [ ] Date validations

## 🎬 Video Tutorial Workflow

If you were making a tutorial, here's the perfect sequence:

### Part 1: Setup (2 min)
1. Load test data via browser
2. Show the summary screen
3. Login to admin

### Part 2: Browse Data (3 min)
1. View members
2. View loans
3. View EMI schedules
4. Show overdue loans

### Part 3: Bank Import (5 min)
1. Go to Bank → Bank Statements
2. Upload Excel file
3. Show imported transactions
4. Explain auto-detection

### Part 4: Transaction Mapping (10 min)
1. **Simple mapping** - TEST-UTR-001
2. **Phone detection** - TEST-UTR-002
3. **Split payment** - TEST-UTR-006 ⭐ (THIS IS THE KEY!)
4. **Unknown mapping** - TEST-UTR-004
5. **Overpayment** - TEST-UTR-005

### Part 5: Verification (3 min)
1. Check EMI schedules updated
2. Check payment history
3. Show member ledger
4. Run reports

## 🔧 Troubleshooting

### If loader shows error:
```
1. Check XAMPP MySQL is running
2. Verify database 'windeep_finance' exists
3. Check database credentials in load_simple_test_data.php
4. Try running simple_test_data.sql via phpMyAdmin
```

### If Excel import fails:
```
1. Check PhpSpreadsheet is installed (composer install)
2. Verify upload folder permissions
3. Use CSV file instead: bank_statement_for_import.csv
```

### To clean and restart:
Just run load_simple_test_data.php again - it cleans old TEST* data automatically!

## 📞 Need Help?

All test data uses prefix "TEST" so you can easily:
- Find test members: `SELECT * FROM members WHERE member_code LIKE 'TEST%'`
- Find test loans: `SELECT * FROM loans WHERE loan_number LIKE 'TESTLN%'`
- Find test transactions: `SELECT * FROM bank_transactions WHERE utr_number LIKE 'TEST-UTR%'`

## 🎯 Success Checklist

After testing, you should be able to:
- ✅ View all members and their details
- ✅ View all loan schedules
- ✅ Import bank statement (Excel/CSV)
- ✅ See auto-detected members
- ✅ Map simple transactions
- ✅ **Map split payments** (main feature!)
- ✅ Handle unknown transactions
- ✅ Process overpayments
- ✅ Generate all reports
- ✅ Verify trial balance

## 🚀 Ready to Go!

Everything is prepared. Just open:
```
http://localhost/windeep_finance/database/load_simple_test_data.php
```

And start testing! 🎉

---

**Files Location:**
- `database/load_simple_test_data.php` - Run this first
- `database/bank_statement_jan2025.xlsx` - Upload this for testing
- `database/TEST_DATA_GUIDE.md` - Detailed guide

**Test Duration:** ~30 minutes to test everything thoroughly

**Ready?** Let's go! 🚀
