# Bulk Import Data Cleanup - SQL Queries

## Purpose
When re-uploading bulk import data for **Savings Transactions** and **Loan Transactions**, you need to clean up the previous import data. This guide provides SQL queries to safely remove imported transactions while keeping members and loan applications intact.

---

## Why Clean Up Before Re-Upload?

### Problem:
- Duplicate transactions if you re-upload without cleaning
- Incorrect account balances (will have doubled/tripled amounts)
- Conflicting payment records for same loan/savings account
- Inaccurate reports and calculations

### Solution:
Run these SQL queries in order to reset all transaction data while preserving member and loan structure.

---

## SQL Cleanup Queries

### 1. Reset Savings Account Balances
```sql
UPDATE savings_accounts SET 
    current_balance = 0,
    total_deposited = 0,
    total_interest_earned = 0,
    total_fines_paid = 0,
    updated_at = NOW();
```
**Why?** Before deleting transactions, reset the account balances to zero so that when you re-upload, the balances are calculated fresh from the new transaction data. Prevents carrying over old balances.

---

### 2. Reset Savings Schedule to Pending Status
```sql
UPDATE savings_schedule SET 
    status = 'pending',
    paid_amount = 0,
    fine_paid = 0,
    paid_date = NULL,
    is_late = 0,
    days_late = 0,
    updated_at = NOW();
```
**Why?** Savings schedules track which deposit dates have been paid. Resetting them to 'pending' ensures that when you re-upload transactions, the system will correctly mark which schedule entries are paid or unpaid. Otherwise, old 'paid' status will conflict with new data.

---

### 3. Delete All Savings Transactions
```sql
DELETE FROM savings_transactions;
```
**Why?** This is the core cleanup - removes all transaction records from bulk import. Savings transactions are the individual deposits/withdrawals recorded for each member. Deleting allows you to re-import fresh data without duplicates.

---

### 4. Reset Loan Installments to Pending Status
```sql
UPDATE loan_installments SET 
    principal_paid = 0,
    interest_paid = 0,
    fine_paid = 0,
    total_paid = 0,
    status = 'pending',
    paid_date = NULL,
    is_late = 0,
    days_late = 0,
    updated_at = NOW()
WHERE status != 'upcoming';
```
**Why?** Loan installments (EMIs) track monthly payment obligations. Resetting all paid amounts and status to 'pending' ensures that when you re-upload loan payments, they are applied to fresh installment records. The `WHERE status != 'upcoming'` preserves future scheduled installments that haven't occurred yet.

---

### 5. Reset Loan Outstanding Amounts
```sql
UPDATE loans l SET 
    l.outstanding_principal = l.principal_amount,
    l.outstanding_interest = l.total_interest,
    l.outstanding_fine = 0,
    l.total_amount_paid = 0,
    l.total_principal_paid = 0,
    l.total_interest_paid = 0,
    l.total_fine_paid = 0,
    l.updated_at = NOW();
```
**Why?** The loan summary table tracks how much money is still owed and how much has been paid. This resets it back to original loan amount + interest, with zero payments recorded. When you re-upload payments, these totals will be recalculated correctly.

---

### 6. Delete All Loan Payments
```sql
DELETE FROM loan_payments;
```
**Why?** This is the core cleanup for loans - removes all individual payment records from bulk import. Loan payments are the payment transactions recorded for each loan. Deleting allows you to re-import fresh payment data without duplicates.

---

### 7. Delete All Fines
```sql
DELETE FROM fines;
```
**Why?** Fines table tracks late payment penalties. These are typically calculated from loan payments (when paid late). Deleting ensures no orphaned fines remain from old import, and new fines will be calculated correctly when new payments are recorded.

---

### 8. Optional - Clean Ledger Entries (Audit Trail)
```sql
-- DELETE FROM ledger_entries WHERE transaction_type IN ('savings_payment', 'loan_payment');
```
**Why?** The ledger is an audit trail of all financial transactions. It's commented out by default because you might want to keep historical audit records. Uncomment only if you want to also remove the audit trail for old transactions. **Be careful - this may violate compliance/audit requirements.**

---

## Verification Query
```sql
SELECT 
    (SELECT COUNT(*) FROM savings_transactions) as savings_txn_count,
    (SELECT COUNT(*) FROM loan_payments) as loan_payments_count,
    (SELECT COUNT(*) FROM fines) as fines_count,
    (SELECT COUNT(*) FROM members) as members_count,
    (SELECT COUNT(*) FROM loan_applications) as loan_apps_count,
    (SELECT COUNT(*) FROM loans) as loans_count,
    (SELECT COUNT(*) FROM savings_accounts) as savings_accounts_count;
```
**Why?** Run this AFTER cleanup to verify:
- ✅ `savings_txn_count` = 0 (all transactions deleted)
- ✅ `loan_payments_count` = 0 (all loan payments deleted)
- ✅ `fines_count` = 0 (all fines deleted)
- ✅ `members_count` > 0 (members preserved)
- ✅ `loan_applications_count` > 0 (applications preserved)
- ✅ `loans_count` > 0 (loans preserved)
- ✅ `savings_accounts_count` > 0 (accounts preserved)

---

## How to Use

### Step 1: Backup Your Database (Recommended)
```sql
-- Export your database first in phpMyAdmin, just in case
```

### Step 2: Run Cleanup Queries
Copy and paste into phpMyAdmin > SQL tab, or your database client:

```sql
-- Execute in this order:
UPDATE savings_accounts SET current_balance = 0, total_deposited = 0, total_interest_earned = 0, total_fines_paid = 0, updated_at = NOW();
UPDATE savings_schedule SET status = 'pending', paid_amount = 0, fine_paid = 0, paid_date = NULL, is_late = 0, days_late = 0, updated_at = NOW();
DELETE FROM savings_transactions;
UPDATE loan_installments SET principal_paid = 0, interest_paid = 0, fine_paid = 0, total_paid = 0, status = 'pending', paid_date = NULL, is_late = 0, days_late = 0, updated_at = NOW() WHERE status != 'upcoming';
UPDATE loans SET outstanding_principal = principal_amount, outstanding_interest = total_interest, outstanding_fine = 0, total_amount_paid = 0, total_principal_paid = 0, total_interest_paid = 0, total_fine_paid = 0, updated_at = NOW();
DELETE FROM loan_payments;
DELETE FROM fines;
```

### Step 3: Verify Cleanup
```sql
SELECT (SELECT COUNT(*) FROM savings_transactions) as txn_count, (SELECT COUNT(*) FROM loan_payments) as payments_count, (SELECT COUNT(*) FROM members) as members_count, (SELECT COUNT(*) FROM fines) as fines_count;
```

### Step 4: Re-Upload Bulk Import
Now you can upload your CSV/Excel files for savings transactions and loan payments. The system will add fresh data to the cleaned tables.

---

## Important Notes

### ⚠️ What Gets Deleted:
- ❌ All savings transactions (individual deposits)
- ❌ All loan payments (individual payment records)
- ❌ All fines (late payment penalties)
- ❌ All schedule/installment payment history

### ✅ What Is Preserved:
- ✅ All members (users remain intact)
- ✅ All loan applications (original loan requests)
- ✅ All loans (loan accounts with reset balances)
- ✅ All savings accounts (accounts with reset balances)
- ✅ All loan/savings schedules (with reset status)

### 🔒 Safety Tips:
1. **Always backup first** - Run a full database export before executing these queries
2. **Run queries in order** - Don't skip any queries as they depend on each other
3. **Test on a development copy first** - If possible, test these queries on a copy of your database before running on production
4. **Verify results** - Always run the verification query at the end to confirm cleanup was successful

---

## Date Range Cleanup (Optional)

If you only want to clean transactions from a specific date range:

```sql
-- Delete only recent imports (example: from April 2026 onwards)
DELETE FROM savings_transactions WHERE transaction_date >= '2026-04-01';
DELETE FROM loan_payments WHERE payment_date >= '2026-04-01';

-- Reset only affected schedules
UPDATE savings_schedule SET status = 'pending', paid_amount = 0, fine_paid = 0 WHERE updated_at >= '2026-04-01';
UPDATE loan_installments SET principal_paid = 0, interest_paid = 0, fine_paid = 0, total_paid = 0, status = 'pending' WHERE updated_at >= '2026-04-01';

-- Recalculate account balances for affected savings accounts
UPDATE savings_accounts SET 
    current_balance = 0,
    total_deposited = 0,
    total_interest_earned = 0,
    total_fines_paid = 0
WHERE updated_at >= '2026-04-01';

-- Recalculate loan balances for affected loans
UPDATE loans SET 
    outstanding_principal = principal_amount,
    outstanding_interest = total_interest,
    outstanding_fine = 0,
    total_amount_paid = 0,
    total_principal_paid = 0,
    total_interest_paid = 0,
    total_fine_paid = 0
WHERE updated_at >= '2026-04-01';
```

---

## Support

If you encounter any issues:
1. Check that all table names match your actual database schema
2. Verify you have proper database permissions (usually requires admin/root)
3. Review the verification query results to identify which tables were/weren't cleaned
4. If needed, restore from backup and troubleshoot

---

**Last Updated:** April 19, 2026
