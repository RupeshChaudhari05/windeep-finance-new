# 🚀 DEPLOYMENT & VERIFICATION GUIDE
## Loan Schedule Integrity Migration #001

**Status:** ✅ Ready for Production  
**Target:** Hostinger windeep_finance database  
**Estimated Time:** 5-10 minutes  

---

## ✅ PRE-DEPLOYMENT CHECKLIST

- [ ] Backup database (PHPMyAdmin Export)
- [ ] Test on local XAMPP (✅ Already verified)
- [ ] Review migration file (✅ Complete)
- [ ] Code changes verified (✅ Loans.php updated)

---

## 🚀 DEPLOYMENT METHODS

### Method 1: Recommended (Admin Panel - Automatic)

```
1. Login to Hostinger Control Panel
2. Navigate: Databases → windeep_finance
3. Click "Database Health Check" button
4. System auto-detects: 001_add_loan_schedule_integrity.sql
5. Click "Check Only" (verify first)
6. If all checks pass, click "Fix Everything"
7. Wait for completion message
```

**Expected Result:**
```
✓ 2 CHECK constraints added
✓ 2 Performance indices created
✓ 2 Tracking tables created
✓ No errors
```

---

### Method 2: Manual (PHPMyAdmin - If needed)

```
1. Login to Hostinger PHPMyAdmin
2. Select database: windeep_finance
3. Click "SQL" tab
4. Copy entire content from: application/migrations/001_add_loan_schedule_integrity.sql
5. Paste into SQL editor
6. Click "Go" button
7. Wait for completion
```

---

### Method 3: CLI (SSH Access)

```bash
# SSH into Hostinger
ssh user@windeepfinance.com

# Navigate to application
cd /public_html

# Run migration
mysql -u root -p windeep_finance < application/migrations/001_add_loan_schedule_integrity.sql

# Verify (see verification section below)
```

---

## ✅ POST-DEPLOYMENT VERIFICATION

Run these 4 queries in PHPMyAdmin to verify everything deployed correctly:

### Query 1: Verify Constraints

```sql
SELECT CONSTRAINT_NAME, CONSTRAINT_TYPE
FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
WHERE TABLE_NAME = 'loan_installments' AND CONSTRAINT_NAME LIKE 'chk_%';
```

**Expected Result:**
```
chk_balance_progression   | CHECK
chk_nonnegative_amounts   | CHECK
```

---

### Query 2: Verify Indices

```sql
SHOW INDEX FROM loan_installments 
WHERE Key_name IN ('idx_loan_status_date', 'idx_unpaid_installments');
```

**Expected Result:**
```
6 rows total
- idx_loan_status_date (3 columns)
- idx_unpaid_installments (3 columns)
```

---

### Query 3: Verify Audit Table

```sql
DESCRIBE loan_schedule_audit;
```

**Expected Result:**
```
17 columns:
- id, loan_id, action, previous_principal, new_principal
- previous_tenure, new_tenure, previous_emi, new_emi
- previous_installment_count, new_installment_count
- reason, validation_errors, validation_warnings
- performed_by, performed_at, created_at
```

---

### Query 4: Verify Migrations Table

```sql
DESCRIBE migrations;
```

**Expected Result:**
```
11 columns:
- id, migration_name, migration_file, status
- executed_by, execution_timestamp, completion_timestamp
- duration_seconds, error_message, output_log, created_at
```

---

## 🧪 FUNCTIONAL TESTING

After deployment, test these scenarios:

### Test 1: Interest-Only Payment

```
Loan: Any active loan
Create Interest-only payment:
  ✓ Payment shows only interest (not principal)
  ✓ Balance stays same after payment
  ✓ No error in validation
```

### Test 2: Regular Payment

```
Create Regular EMI payment:
  ✓ Payment shows principal + interest
  ✓ Balance decreases by principal amount
  ✓ Interest decreases monthly
```

### Test 3: Constraint Protection

```
Attempt invalid update (via PHPMyAdmin):
  UPDATE loan_installments 
  SET outstanding_principal_after = 999999 
  WHERE installment_number = 1 AND status != 'interest_only';
  
Expected: ❌ Query fails with CONSTRAINT FAILED error
```

---

## 🔍 MONITORING AFTER DEPLOYMENT

### Week 1: Daily Checks

- [ ] Check application logs for validation errors
- [ ] Verify loan schedule displays correctly
- [ ] Test 3-4 loan payments manually
- [ ] Confirm balance calculations are correct

### Week 2-4: Regular Checks

- [ ] Monitor loan_schedule_audit table for entries
- [ ] Review constraint violations (should be none)
- [ ] Check query performance (indices working)

### Ongoing: Monthly Checks

- [ ] Review audit trail for patterns
- [ ] Verify constraint still preventing errors
- [ ] Check index usage statistics

---

## ✅ SUCCESS CRITERIA

Migration is **SUCCESSFUL** when all of these are true:

```
✅ 2 CHECK constraints present in INFORMATION_SCHEMA
✅ 2 indices showing in SHOW INDEX results
✅ loan_schedule_audit table exists with 17 columns
✅ migrations table exists with 11 columns
✅ No errors during execution
✅ Constraint blocks invalid updates
✅ Application logs show no new errors
✅ Loan schedule displays correctly (interest-only stays same, regular decreases)
✅ Interest-only balances display as ₹75,000 (not increasing)
```

---

## 🆘 TROUBLESHOOTING

### Issue: "CONSTRAINT chk_balance_progression FAILED"

**Cause:** Trying to update with invalid balance  
**Solution:** This is GOOD - constraint is protecting data. Check the update logic.

```
❌ This will fail (as expected):
UPDATE loan_installments 
SET outstanding_principal_after = 999999 
WHERE status != 'interest_only';

✅ This will pass:
UPDATE loan_installments 
SET outstanding_principal_after = outstanding_principal_before 
WHERE status = 'interest_only';
```

---

### Issue: "Table loan_schedule_audit not found"

**Cause:** Migration didn't complete fully  
**Solution:** Re-run deployment using Method 1 (Database Health Check)

---

### Issue: "Indices not improving performance"

**Cause:** Indices exist but queries not using them  
**Solution:** Verify with EXPLAIN command:

```sql
EXPLAIN SELECT * FROM loan_installments 
WHERE loan_id = 129 AND status = 'pending' AND due_date = '2024-11-01';

-- Should show: Using index
```

---

## 📝 DEPLOYMENT RECORD

After successful deployment, record:

```
Deployment Date: _____________
Deployed By: _____________
Method Used: [ ] Admin Panel  [ ] PHPMyAdmin  [ ] CLI
All Verifications Passed: [ ] Yes  [ ] No

Issues Encountered: _______________

Notes: _______________
```

---

## ✅ DEPLOYMENT READY

**Status:** ✅ PRODUCTION READY FOR DEPLOYMENT

**Next Step:** Choose deployment method above and follow steps.

**Questions?** See PROFESSIONAL_BANKING_STANDARD_FIX.md for technical details.

---

**Version:** 1.0  
**Date:** June 5, 2026  
**Estimated Duration:** 5-10 minutes  
**Risk Level:** LOW (Idempotent, no data loss)
