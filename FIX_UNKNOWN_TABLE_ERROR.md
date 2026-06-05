# 🔴 ERROR: Unknown Table 'loan_installments'

## What This Means

The query is trying to check for constraints/indices, but they don't exist yet.

**Reason:** The migration file hasn't been applied to the database yet.

---

## ✅ HOW TO FIX - PROPER DEPLOYMENT ORDER

### STEP 1: Connect to Correct Database

First, verify you're in the RIGHT database:

```sql
-- Run this query in PHPMyAdmin
SELECT DATABASE();
```

**Should show:** `windeep_finance` or `windeep_finance_new`

If it shows something else, select the correct database first.

---

### STEP 2: Deploy the Migration (REQUIRED FIRST)

You MUST deploy the migration BEFORE verifying.

#### Option A: Via PHPMyAdmin (EASIEST)

```
1. Login to Hostinger PHPMyAdmin
2. Select database: windeep_finance
3. Click "SQL" tab at top
4. CLEAR the text area completely
5. Copy and paste this entire file:
   application/migrations/001_add_loan_schedule_integrity.sql
6. Click the blue "Go" button
7. WAIT for success message
8. DO NOT close the window
```

#### Option B: Via Hostinger Database Manager

```
1. Login to Hostinger Control Panel
2. Databases → windeep_finance
3. Look for "Advanced Tools" or "Management"
4. Find "Database Health Check"
5. Click "Check Only" first
6. If ready, click "Fix Everything"
7. Wait for completion
```

---

### STEP 3: NOW Run Verification (Only AFTER deployment)

After migration completes successfully, run these queries:

#### Query 1 - Test Database Connection:
```sql
SELECT DATABASE();
```
**Expected:** `windeep_finance` (or your actual database name)

---

#### Query 2 - Check Constraints:
```sql
SELECT CONSTRAINT_NAME 
FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
WHERE TABLE_NAME = 'loan_installments' AND CONSTRAINT_NAME LIKE 'chk_%';
```
**Expected:** 2 rows
- chk_balance_progression
- chk_nonnegative_amounts

---

#### Query 3 - Check Indices:
```sql
SHOW INDEX FROM loan_installments 
WHERE Key_name IN ('idx_loan_status_date', 'idx_unpaid_installments');
```
**Expected:** 6 rows total

---

#### Query 4 - Check Audit Table:
```sql
DESCRIBE loan_schedule_audit;
```
**Expected:** 17 columns

---

#### Query 5 - Check Migrations Table:
```sql
DESCRIBE migrations;
```
**Expected:** 11 columns

---

## 🆘 Troubleshooting

### Issue: Still getting "Unknown table" error

**Solution 1:** Make sure you selected the database
```sql
-- Run this first
USE windeep_finance;

-- Then run your query
SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
WHERE TABLE_NAME = 'loan_installments' AND CONSTRAINT_NAME LIKE 'chk_%';
```

**Solution 2:** Check if the table exists at all
```sql
-- This should work if database is correct
SHOW TABLES LIKE 'loan_installments';
```

**Expected:** Shows `loan_installments` table name

---

### Issue: Migration didn't apply

**Signs:**
- Constraints still don't exist
- Audit table not found
- Indices not showing

**Solution:** Re-run the entire migration file:

1. Copy the complete content from:
   `application/migrations/001_add_loan_schedule_integrity.sql`

2. Paste into PHPMyAdmin SQL tab

3. Make sure there are NO errors in the output

4. If you see errors, take a screenshot and share them

---

## ✅ CORRECT ORDER (IMPORTANT!)

```
1. ✅ DEPLOY Migration (Step 2 above)
2. ✅ WAIT for success message
3. ✅ THEN run verification queries (Step 3 above)

DO NOT try to verify before deploying!
```

---

## 📝 Check Your Database Name

On Hostinger, it might be named differently. Run this to list all databases:

```sql
SHOW DATABASES;
```

Find your database (should contain "windeep" or "finance" in the name).

Then select it:
```sql
USE your_database_name;
```

Then run verification queries.

---

## 🎯 Summary

1. **Deploy Migration First** ← YOU ARE HERE (need to do this)
2. Verify it worked
3. Test the application

**Next Action:** Go to PHPMyAdmin and paste the migration file, then click Go.

