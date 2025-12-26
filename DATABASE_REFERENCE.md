# Database Schema Quick Reference

## Core Tables

### Members System
- **members** - Member master data
- **member_ledger** - Member-wise transaction ledger

### Savings System
- **savings_schemes** - Savings product definitions
- **savings_accounts** - Individual savings accounts
- **savings_transactions** - All savings deposits/withdrawals
- **savings_schedule** - Payment schedule per account

### Loan System
- **loan_products** - Loan product definitions
- **loan_applications** - Loan applications (pending approval)
- **loan_guarantors** - Guarantor details per application
- **loans** - Approved & disbursed loans
- **loan_installments** - EMI schedule
- **loan_payments** - Actual EMI payments received

### Fines & Penalties
- **fine_rules** - Configurable fine rules
- **fines** - Fine records
- **fine_payments** - Fine payment transactions

### Bank Integration
- **bank_accounts** - Organization bank accounts
- **bank_statement_imports** - Import tracking
- **bank_transactions** - Imported bank transactions
- **transaction_mappings** - Link bank txn to internal transactions

### Accounting & Ledger
- **chart_of_accounts** - COA master
- **general_ledger** - Double-entry GL
- **financial_years** - Financial period management

### System Tables
- **admin_users** - Admin user accounts
- **system_settings** - System configuration
- **audit_logs** - Complete audit trail
- **activity_logs** - User activity tracking
- **notifications** - System notifications

---

## Key Relationships

```
members
  ├─> savings_accounts (1:N)
  │     └─> savings_transactions (1:N)
  │     └─> savings_schedule (1:N)
  │
  ├─> loan_applications (1:N)
  │     └─> loan_guarantors (1:N)
  │     └─> loans (1:1 after approval)
  │           └─> loan_installments (1:N)
  │           └─> loan_payments (1:N)
  │
  ├─> fines (1:N)
  │     └─> fine_payments (1:N)
  │
  └─> member_ledger (1:N)
```

---

## Important Indexes

Performance-critical indexes already created:
- Member phone number (for quick search)
- Loan status + disbursed date (for reports)
- Transaction dates (for date-range queries)
- Bank transaction status (for matching)

---

## Common Queries

### Get Member Financial Summary
```sql
SELECT 
    m.*,
    COALESCE(SUM(sa.current_balance), 0) as total_savings,
    COALESCE(SUM(l.outstanding_principal), 0) as total_outstanding,
    COUNT(DISTINCT l.id) as active_loans
FROM members m
LEFT JOIN savings_accounts sa ON m.id = sa.member_id AND sa.status = 'active'
LEFT JOIN loans l ON m.id = l.member_id AND l.status IN ('active', 'overdue')
WHERE m.id = ?
GROUP BY m.id;
```

### Get Overdue Loans
```sql
SELECT l.*, m.first_name, m.last_name, m.phone,
    DATEDIFF(CURDATE(), (
        SELECT MAX(due_date) 
        FROM loan_installments 
        WHERE loan_id = l.id AND status = 'pending'
    )) as overdue_days
FROM loans l
INNER JOIN members m ON l.member_id = m.id
WHERE l.status = 'active'
AND EXISTS (
    SELECT 1 FROM loan_installments 
    WHERE loan_id = l.id 
    AND status = 'pending' 
    AND due_date < CURDATE()
)
ORDER BY overdue_days DESC;
```

### Get Collection Report
```sql
SELECT 
    DATE(st.transaction_date) as date,
    'Savings' as type,
    COUNT(*) as count,
    SUM(st.credit_amount) as amount
FROM savings_transactions st
WHERE st.transaction_date BETWEEN ? AND ?
AND st.transaction_type = 'deposit'
GROUP BY DATE(st.transaction_date)

UNION ALL

SELECT 
    DATE(lp.payment_date) as date,
    'Loan' as type,
    COUNT(*) as count,
    SUM(lp.amount_paid) as amount
FROM loan_payments lp
WHERE lp.payment_date BETWEEN ? AND ?
GROUP BY DATE(lp.payment_date)

ORDER BY date DESC;
```

---

## Status Values

### Member Status
- `active` - Active member
- `inactive` - Temporarily inactive
- `suspended` - Suspended by admin
- `deceased` - Deceased member

### Savings Account Status
- `active` - Currently active
- `matured` - Reached maturity
- `closed` - Closed by member/admin
- `dormant` - Inactive for long period

### Loan Status
- `active` - Active loan
- `overdue` - One or more EMIs overdue
- `npa` - Non-Performing Asset (90+ days)
- `closed` - Fully repaid
- `written_off` - Written off

### Loan Application Status
- `pending` - Awaiting review
- `under_review` - Being reviewed
- `approved` - Approved, pending disbursement
- `rejected` - Application rejected
- `disbursed` - Loan disbursed (moved to loans table)

### Payment Status
- `pending` - Not yet paid
- `partial` - Partially paid
- `paid` - Fully paid
- `skipped` - Skipped by admin
- `waived` - Waived

---

## Backup Strategy

Recommended backup frequency:
- **Daily**: Full database backup
- **Hourly**: Incremental transaction logs
- **Weekly**: Off-site backup

Critical tables (backup before bulk operations):
- loans
- loan_payments
- savings_transactions
- general_ledger

---

## Data Integrity Rules

1. **Soft Deletes**: Financial records use `deleted_at` instead of actual deletion
2. **Ledger Balance**: Always reconcile with GL after bulk operations
3. **Cascading**: Loan disbursement creates GL entries automatically
4. **Constraints**: Foreign keys enforce referential integrity
5. **Triggers**: Auto-update running balances (if implemented)

---

## Performance Tips

1. **Pagination**: Always use LIMIT/OFFSET for large datasets
2. **Date Ranges**: Use indexed date columns for report queries
3. **JOINs**: Limit to necessary columns only
4. **Aggregations**: Use summary tables for dashboard stats
5. **Caching**: Cache frequently accessed config data

---

## Migration Notes

To add new columns safely:
```sql
ALTER TABLE table_name 
ADD COLUMN new_column VARCHAR(255) NULL 
AFTER existing_column;
```

To modify existing columns:
```sql
ALTER TABLE table_name 
MODIFY COLUMN column_name NEW_DEFINITION;
```

Always test on backup database first!
