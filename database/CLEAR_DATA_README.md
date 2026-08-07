# DATA CLEARANCE PROCEDURE DOCUMENTATION

## Overview

This documentation covers the data clearance procedures for Windeep Finance system. These procedures will clear all transactional data (loans, savings, fines, etc.) while preserving member records and their login information.

## Files Included

1. **CLEAR_DATA_SP.sql** - Main stored procedure using TRUNCATE (faster)
2. **CLEAR_DATA_DELETE_SP.sql** - Alternative stored procedure using DELETE (safer)
3. **VERIFY_CLEAR_DATA.sql** - Verification script to check what will be cleared
4. **CLEAR_DATA_README.md** - This file

---

## What Gets CLEARED ❌

### Loan Management
- `loan_products` - Loan schemes/products
- `loan_applications` - Loan applications
- `loan_guarantors` - Guarantor records
- `loans` - Disbursed loans
- `loan_installments` - EMI schedules
- `loan_payments` - Loan payment transactions

### Savings Management
- `savings_schemes` - Savings schemes/products
- `savings_accounts` - Member savings accounts
- `savings_schedule` - Monthly savings dues
- `savings_transactions` - Savings transactions

### Fines & Penalties
- `fine_rules` - Fine calculation rules
- `rule_code_sequence` - Rule code sequences
- `fines` - Fine records

### Bank & Accounting
- `bank_accounts` - Bank account definitions
- `bank_statement_imports` - Imported bank statements
- `bank_transactions` - Bank transaction records
- `transaction_mappings` - Transaction mappings
- `chart_of_accounts` - Chart of accounts
- `general_ledger` - General ledger entries
- `member_ledger` - Member ledger entries

### Admin & System
- `admin_users` - Admin user accounts
- `admin_sessions` - Admin login sessions
- `financial_years` - Financial year definitions
- `system_settings` - System configuration

### Logs & Audit
- `audit_logs` - Audit trail
- `activity_logs` - Activity logs
- `notifications` - System notifications

---

## What Gets PRESERVED ✅

### Member Data (NEVER DELETED)
- `members` - All member records with:
  - Personal information
  - Contact details
  - Bank details
  - **Member login passwords** ⭐
  - KYC documents
  - Status information
  - All member profile data

### Supporting Sequences
- `member_code_sequence` - Member code generation counter

---

## Pre-Execution Checklist

Before running any clearance procedure:

- [ ] **BACKUP DATABASE** - Take a complete backup
- [ ] **Notify Users** - Inform all users about data clearance
- [ ] **Schedule Maintenance Window** - Run during off-peak hours
- [ ] **Test Environment** - First run in staging/development
- [ ] **Verify Backup** - Confirm backup is valid and restorable
- [ ] **Document Reason** - Record why data was cleared
- [ ] **Get Approval** - Obtain authorization from manager/admin

---

## OPTION 1: Using TRUNCATE (RECOMMENDED for Fresh Start)

**File:** `CLEAR_DATA_SP.sql`

**Characteristics:**
- ✅ Much faster (milliseconds for large datasets)
- ✅ Resets auto-increment counters to 1
- ✅ Best for complete system reset
- ❌ Cannot be rolled back after commit
- ⚠️ Use only after confirming you want fresh IDs

### Step-by-Step Execution

#### Step 1: Create the Stored Procedure

```bash
mysql -u root -p [database_name] < CLEAR_DATA_SP.sql
```

Or in MySQL client:
```sql
SOURCE CLEAR_DATA_SP.sql;
```

#### Step 2: Verify What Will Be Cleared

```bash
mysql -u root -p [database_name] < VERIFY_CLEAR_DATA.sql
```

Review the output to confirm:
- Members count should be > 0
- Loan/Savings/Transaction counts will show current data

#### Step 3: Run the Clearance Procedure

```sql
CALL sp_clear_all_data();
```

**Output will show:**
```
status   | message                                               | cleared_at
---------|-------------------------------------------------------|---------------------
SUCCESS  | All data cleared except members and login information | 2026-07-12 10:30:45
```

#### Step 4: Verify Completion

```sql
-- Check that tables are empty
SELECT TABLE_NAME, TABLE_ROWS 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_ROWS DESC;

-- Check members are still there
SELECT COUNT(*) as total_members FROM members;
```

---

## OPTION 2: Using DELETE (SAFER for Testing)

**File:** `CLEAR_DATA_DELETE_SP.sql`

**Characteristics:**
- ✅ Full transaction support
- ✅ Can be rolled back if something goes wrong
- ✅ Preserves auto-increment sequences (IDs continue from last used)
- ❌ Slower than TRUNCATE for very large datasets
- ✅ Safer for testing before using TRUNCATE

### Step-by-Step Execution

#### Step 1: Create the Stored Procedure

```bash
mysql -u root -p [database_name] < CLEAR_DATA_DELETE_SP.sql
```

#### Step 2: Verify What Will Be Cleared

```bash
mysql -u root -p [database_name] < VERIFY_CLEAR_DATA.sql
```

#### Step 3: Run in a Transaction (with rollback option)

```sql
START TRANSACTION;
CALL sp_clear_all_data_with_delete();
-- Review the output
-- If something looks wrong: ROLLBACK;
-- If everything is correct: COMMIT;
COMMIT;
```

---

## QUICK START (Command Line)

### For Linux/Mac:

```bash
# Step 1: Backup
mysqldump -u root -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# Step 2: Verify
mysql -u root -p database_name < VERIFY_CLEAR_DATA.sql

# Step 3: Clear (choose one)
echo "CALL sp_clear_all_data();" | mysql -u root -p database_name
# OR
echo "CALL sp_clear_all_data_with_delete();" | mysql -u root -p database_name
```

### For Windows:

```cmd
REM Step 1: Backup
mysqldump -u root -p database_name > backup_%date:~-4,4%%date:~-10,2%%date:~-7,2%.sql

REM Step 2: Verify
mysql -u root -p database_name < VERIFY_CLEAR_DATA.sql

REM Step 3: Clear
mysql -u root -p database_name -e "CALL sp_clear_all_data();"
```

---

## TROUBLESHOOTING

### Issue: "Access Denied" Error

**Solution:**
```sql
-- Connect with appropriate user
mysql -u admin -p --host=localhost database_name
```

### Issue: Foreign Key Constraint Error

**Solution:**
The procedures already handle this by disabling foreign key checks. If you get this error when running manually, ensure this code is first:

```sql
SET FOREIGN_KEY_CHECKS = 0;
```

### Issue: Stored Procedure Already Exists

**Solution:**
The DROP PROCEDURE IF EXISTS statement handles this automatically.

### Issue: Rollback Needed

**Solution:** (Only with DELETE option)

```sql
-- If you ran the DELETE version in a transaction:
ROLLBACK;
```

---

## Post-Execution Tasks

After running the clearance procedure:

1. **Verify Data Integrity**
   ```sql
   -- Check members still exist
   SELECT COUNT(*) FROM members;
   
   -- Check login data is preserved
   SELECT COUNT(*) FROM members WHERE password IS NOT NULL;
   
   -- Confirm loans are cleared
   SELECT COUNT(*) FROM loans;
   ```

2. **Reset Financial Year** (if needed)
   ```sql
   INSERT INTO financial_years (year_code, start_date, end_date, is_active)
   VALUES ('2026-27', '2026-04-01', '2027-03-31', 1);
   ```

3. **Create Admin User** (if admin_users was cleared)
   ```sql
   INSERT INTO admin_users (username, email, password, full_name, role)
   VALUES ('admin', 'admin@windeep.local', SHA2('password123', 256), 'Administrator', 'super_admin');
   ```

4. **Reset System Settings** (if needed)
   ```sql
   INSERT INTO system_settings (setting_key, setting_value, setting_type)
   VALUES 
   ('company_name', 'Windeep Finance', 'string'),
   ('company_email', 'info@windeep.local', 'string');
   ```

5. **Test Member Login**
   - Verify members can still login with their stored passwords
   - Confirm their profile information is intact

6. **Document the Clearance**
   ```sql
   -- Create a clearance log entry
   INSERT INTO activity_logs (action, description, created_at)
   VALUES ('SYSTEM_DATA_CLEAR', 'Complete system data clearance performed', NOW());
   ```

---

## Member Data Preservation Details

### What is Preserved in `members` table:

✅ Member ID and Code
✅ Personal Information (name, DOB, gender, etc.)
✅ Contact Details (phone, email, address)
✅ KYC Data (Aadhaar, PAN, voter ID)
✅ Bank Details (account, IFSC, etc.)
✅ **Member Passwords** (for portal login)
✅ Membership Status
✅ Nominee Information
✅ Guarantor Limits
✅ All metadata and notes

### Why This is Important:

Members don't need to re-register or re-setup their login credentials. They can immediately login after the clearance and start fresh with new loans/savings.

---

## Best Practices

1. **Always Backup First**
   ```bash
   mysqldump -u root -p database_name > backup.sql
   ```

2. **Use Verification Script First**
   - Always run VERIFY_CLEAR_DATA.sql before clearance
   - Review record counts carefully

3. **Test in Development**
   - Never clear production data without testing in dev first
   - Verify the exact tables being cleared

4. **Communicate with Team**
   - Notify team members before running
   - Inform them that member data will be preserved
   - Let them know which data will be cleared

5. **Keep Audit Trail**
   - Document when and why data was cleared
   - Keep backup copies for regulatory compliance
   - Record who authorized the clearance

6. **Use Appropriate Credentials**
   - Use database root or admin account
   - Avoid using application service account
   - Log all administrative actions

---

## FAQs

### Q: Will members lose their login access?
**A:** NO. Member passwords are preserved. They can login normally after clearance.

### Q: Can I recover cleared data?
**A:** YES, if you have a backup. Restore from backup file. That's why backups are critical.

### Q: What if I accidentally cleared production?
**A:** 
1. Stop all application access immediately
2. Restore from most recent backup
3. Verify restore is complete
4. Gradually bring services back online
5. Audit what was restored

### Q: How long does clearance take?
**A:** 
- TRUNCATE: Usually < 1 second
- DELETE: Usually < 5 seconds (depends on dataset size)

### Q: Can I use TRUNCATE without losing auto-increment?
**A:** NO. If you need to preserve IDs, use the DELETE version.

### Q: What about financial reports or compliance?
**A:** Keep backup files for audit trail. Export reports before clearing if needed.

### Q: Can I clear only specific data?
**A:** YES. Modify the SQL to comment out tables you want to keep.

---

## Support & Questions

For issues or questions about these procedures:

1. Review this documentation
2. Check the troubleshooting section
3. Review backup strategy
4. Test in development environment
5. Contact database administrator

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2026-07-12 | Initial version with TRUNCATE and DELETE options |

---

## References

- Main Schema: `database/schema.sql`
- Members Table: Preserves all personal and login data
- Loan Module: `loans`, `loan_installments`, `loan_payments`
- Savings Module: `savings_accounts`, `savings_transactions`

