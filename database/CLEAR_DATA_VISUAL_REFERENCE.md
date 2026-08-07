# VISUAL REFERENCE - DATA CLEARANCE OVERVIEW

## System Data Flow

```
┌─────────────────────────────────────────────────────────────┐
│         WINDEEP FINANCE DATABASE STRUCTURE                   │
└─────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────────┐
│  CORE DATA (PROTECTED) ✅                                           │
├────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  members ────────────────────────────────────────────────────────┐ │
│  • ID, Name, Phone, Email                                       │ │
│  • Bank Details, KYC Documents                                  │ │
│  • PASSWORD (Login credentials) ⭐ PRESERVED                   │ │
│  • Membership Details, Status                                   │ │
│                                                                 │ │
│  member_code_sequence ◄──────────────────────────────────────┐ │ │
│  • Next member code generator                                 │ │ │
│  • Ensures continuous member numbering                        │ │ │
│                                                               │ │ │
└───────────────────────────────────────────────────────────────┘ │ │
    └──────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────┘


┌────────────────────────────────────────────────────────────────────┐
│  TRANSACTIONAL DATA (CLEARED) ❌                                    │
├────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  LOANS SUBSYSTEM                                                    │
│  ├─ loan_products (Loan schemes/products)                          │
│  ├─ loan_applications (Loan requests)                              │
│  ├─ loans (Disbursed loans)                                        │
│  ├─ loan_installments (EMI schedule)                               │
│  ├─ loan_payments (Payment transactions)                           │
│  └─ loan_guarantors (Guarantor records)                            │
│                                                                     │
│  SAVINGS SUBSYSTEM                                                  │
│  ├─ savings_schemes (Savings products)                             │
│  ├─ savings_accounts (Member accounts)                             │
│  ├─ savings_schedule (Monthly dues)                                │
│  └─ savings_transactions (Transactions)                            │
│                                                                     │
│  FINES & PENALTIES                                                  │
│  ├─ fine_rules (Penalty rules)                                     │
│  ├─ rule_code_sequence (Rule numbering)                            │
│  └─ fines (Fine records)                                           │
│                                                                     │
│  ACCOUNTING & BANK                                                  │
│  ├─ bank_accounts (Bank connections)                               │
│  ├─ bank_statement_imports (Imported statements)                   │
│  ├─ bank_transactions (Bank transactions)                          │
│  ├─ transaction_mappings (Reconciliation)                          │
│  ├─ chart_of_accounts (Account setup)                              │
│  ├─ general_ledger (GL entries)                                    │
│  └─ member_ledger (Member ledger)                                  │
│                                                                     │
│  ADMIN & SYSTEM                                                     │
│  ├─ admin_users (Admin accounts)                                   │
│  ├─ admin_sessions (Admin logins)                                  │
│  ├─ financial_years (Fiscal years)                                 │
│  └─ system_settings (Configuration)                                │
│                                                                     │
│  AUDIT & LOGS                                                       │
│  ├─ audit_logs (Audit trail)                                       │
│  ├─ activity_logs (Activity history)                               │
│  └─ notifications (Notifications)                                  │
│                                                                     │
│  TOTAL: 27 tables cleared                                           │
└────────────────────────────────────────────────────────────────────┘
```

---

## Execution Flow Chart

```
START
  │
  ├─➀ BACKUP DATABASE
  │   └─ mysqldump -u root -p database > backup.sql
  │
  ├─➁ VERIFY DATA
  │   └─ mysql < VERIFY_CLEAR_DATA.sql
  │   └─ Check record counts
  │   └─ Confirm preservation rules
  │
  ├─➂ CREATE PROCEDURE
  │   │
  │   ├─ Option A: TRUNCATE (Faster, fresh IDs)
  │   │  └─ mysql < CLEAR_DATA_SP.sql
  │   │
  │   └─ Option B: DELETE (Safer, preserve IDs)
  │      └─ mysql < CLEAR_DATA_DELETE_SP.sql
  │
  ├─➃ EXECUTE CLEARANCE
  │   │
  │   └─ CALL sp_clear_all_data();
  │
  │   [System Process]
  │   ├─ Disable Foreign Keys
  │   ├─ Delete Loan Data (6 tables)
  │   ├─ Delete Savings Data (4 tables)
  │   ├─ Delete Fine Data (3 tables)
  │   ├─ Delete Bank/Ledger Data (7 tables)
  │   ├─ Delete Admin Data (4 tables)
  │   ├─ Delete Logs (3 tables)
  │   ├─ PRESERVE Members + Member Codes
  │   └─ Re-enable Foreign Keys
  │
  ├─➄ VERIFY RESULTS
  │   ├─ SELECT COUNT(*) FROM members;     ✓ > 0
  │   ├─ SELECT COUNT(*) FROM loans;       ✓ = 0
  │   └─ SELECT COUNT(*) FROM savings;     ✓ = 0
  │
  ├─➅ POST-PROCESSING (OPTIONAL)
  │   ├─ Create admin user
  │   ├─ Set financial year
  │   └─ Notify team
  │
  └─✓ COMPLETE
```

---

## Data Before & After Comparison

```
BEFORE CLEARANCE:
═════════════════════════════════════════════════════════════

Members Table:
├─ Member 1: John Doe
│  ├─ Email: john@example.com
│  ├─ Password: SHA2(...) ⭐ PRESERVED
│  ├─ Status: Active
│  ├─ Loans: 2
│  ├─ Savings Accounts: 1
│  └─ Outstanding: $5,000

├─ Member 2: Jane Smith
│  ├─ Email: jane@example.com
│  ├─ Password: SHA2(...) ⭐ PRESERVED
│  └─ [Similar structure]

└─ ... 5,430 more members

Related Transaction Data:
├─ 1,245 Loans
├─ 8,764 Loan Payments
├─ 892 Savings Accounts
├─ 12,456 Savings Transactions
├─ 234 Fines
└─ 5,000+ other transaction records

TOTAL RECORDS: 29,023


AFTER CLEARANCE:
═════════════════════════════════════════════════════════════

Members Table:
├─ Member 1: John Doe
│  ├─ Email: john@example.com
│  ├─ Password: SHA2(...) ⭐ STILL THERE (Can login!)
│  ├─ Status: Active
│  ├─ Loans: 0 (Cleared)
│  ├─ Savings Accounts: 0 (Cleared)
│  └─ Outstanding: $0 (Cleared)

├─ Member 2: Jane Smith
│  ├─ Email: jane@example.com
│  ├─ Password: SHA2(...) ⭐ STILL THERE (Can login!)
│  └─ [All transaction history cleared]

└─ ... 5,430 more members (UNCHANGED)

Related Transaction Data:
├─ 0 Loans (Cleared)
├─ 0 Loan Payments (Cleared)
├─ 0 Savings Accounts (Cleared)
├─ 0 Savings Transactions (Cleared)
├─ 0 Fines (Cleared)
└─ All other transaction records (Cleared)

TOTAL RECORDS: 5,432 (Only members remain)

✓ Members can login immediately with original passwords!
```

---

## Decision Tree

```
                    DO YOU WANT TO CLEAR DATA?
                              │
                          YES / NO
                          /      \
                        NO        YES
                        │         │
                      STOP     ┌──────────────────────────────┐
                               │ Do you want to preserve ID   │
                               │ sequences (1, 2, 3... next   │
                               │ from where they left off)?   │
                               └──────────────────────────────┘
                                   /                \
                                 YES              NO
                                  │                 │
                        ┌──────────┴──────────┐    │
                        │ DELETE VERSION     │    │ TRUNCATE VERSION
                        │ (SAFER)            │    │ (FASTER)
                        │                    │    │
                        │ File:              │    │ File:
                        │ CLEAR_DATA_DELETE  │    │ CLEAR_DATA_SP.sql
                        │ _SP.sql            │    │
                        │                    │    │
                        │ Speed: < 5 sec     │    │ Speed: < 1 sec
                        │ IDs: Preserved ✓   │    │ IDs: Reset to 1
                        │ Risk: Lower ✓      │    │ Risk: None
                        │                    │    │
                        │ Best for:          │    │ Best for:
                        │ • Testing          │    │ • Production
                        │ • Validation       │    │ • Fresh start
                        │ • Careful ops      │    │ • Most cases
                        └────────────────────┘    │
                               │                  │
                               └──────┬───────────┘
                                      │
                         CHOOSE YOUR PROCEDURE
                                      │
                    ┌─────────────────┼─────────────────┐
                    │                 │                 │
              BACKUP            VERIFY           EXECUTE
              Database          Data              Procedure
                    │             │                 │
                    │             │                 │
           mysqldump...     VERIFY_CLEAR_DATA    CALL sp_clear_...
                    │         .sql                   ()
                    │             │                 │
                    └─────────────┼─────────────────┘
                                  │
                         VERIFY RESULTS
                                  │
                         ✓ Members OK?
                         ✓ Loans = 0?
                         ✓ Savings = 0?
                                  │
                            SUCCESS ✓
```

---

## Quick Reference Poster

```
╔═══════════════════════════════════════════════════════════════╗
║          DATA CLEARANCE QUICK REFERENCE POSTER               ║
╠═══════════════════════════════════════════════════════════════╣
║                                                               ║
║  STEP 1: BACKUP                                              ║
║  $ mysqldump -u root -p database > backup.sql                ║
║                                                               ║
║  STEP 2: VERIFY                                              ║
║  $ mysql -u root -p database < VERIFY_CLEAR_DATA.sql         ║
║                                                               ║
║  STEP 3: CREATE PROCEDURE                                    ║
║  $ mysql -u root -p database < CLEAR_DATA_SP.sql             ║
║                                                               ║
║  STEP 4: CLEAR DATA                                          ║
║  $ mysql -u root -p database                                 ║
║  > CALL sp_clear_all_data();                                 ║
║                                                               ║
║  STEP 5: VERIFY SUCCESS                                      ║
║  > SELECT COUNT(*) FROM members;     (Should be > 0)        ║
║  > SELECT COUNT(*) FROM loans;       (Should be 0)          ║
║                                                               ║
║  ✓ MEMBERS ARE PRESERVED!                                    ║
║  ✓ LOGIN PASSWORDS INTACT!                                   ║
║  ✓ ALL TRANSACTIONS CLEARED!                                 ║
║                                                               ║
╚═══════════════════════════════════════════════════════════════╝

TABLES AFFECTED:                TABLES PRESERVED:
├─ 27 Tables Cleared           ├─ members
├─ All Loan Data                └─ member_code_sequence
├─ All Savings Data
├─ All Fine Data                KEY POINT:
├─ All Bank Data                Members can LOGIN
├─ All Ledger Data              immediately after!
├─ All Admin Data
├─ All Log Data
└─ All Notifications

Total: ~29,000 records deleted
Remaining: 5,400+ member records only
```

---

## Table Dependency Map

```
MEMBER-DEPENDENT TABLES (Cleared if member not deleted):
═════════════════════════════════════════════════════════

members (✓ PRESERVED)
  ├─→ loan_applications
  │    ├─→ loans ──→ loan_installments ──→ loan_payments
  │    └─→ loan_guarantors
  │
  ├─→ savings_accounts
  │    ├─→ savings_schedule
  │    └─→ savings_transactions
  │
  ├─→ member_ledger
  │
  └─→ fines

INDEPENDENT TABLES (System-wide, all deleted):
════════════════════════════════════════════════

├─ loan_products
├─ savings_schemes
├─ fine_rules
├─ bank_accounts
├─ chart_of_accounts
├─ financial_years
├─ admin_users
├─ system_settings
└─ etc.


SEQUENCE TABLES (Preserved):
═════════════════════════════

└─ member_code_sequence ──→ members (for ID generation)
```

---

## Success Criteria Checklist

After execution, verify:

```
✓ PRESERVATION CHECKS:
  □ Members table has data: SELECT COUNT(*) FROM members; (> 0)
  □ Members can login: Passwords exist for all active members
  □ Member profiles intact: KYC, bank details preserved
  □ Member codes work: member_code_sequence at correct value

✗ CLEARANCE CHECKS:
  □ Loans cleared: SELECT COUNT(*) FROM loans; (= 0)
  □ Payments cleared: SELECT COUNT(*) FROM loan_payments; (= 0)
  □ Savings cleared: SELECT COUNT(*) FROM savings_accounts; (= 0)
  □ Fines cleared: SELECT COUNT(*) FROM fines; (= 0)
  □ Admin cleared: SELECT COUNT(*) FROM admin_users; (= 0)
  □ All 27 target tables: All at 0 records
```

---

## Performance Expectations

```
Operation          Duration        Status    Notes
─────────────────────────────────────────────────────────
BACKUP              30-60 sec       ✓         Depends on size
VERIFY              < 1 sec         ✓         Quick count
CREATE PROCEDURE    < 1 sec         ✓         Just registration
CLEAR DATA          < 1 sec (T)     ✓         TRUNCATE: very fast
                    < 5 sec (D)     ✓         DELETE: slower but safe
VERIFY RESULT       < 1 sec         ✓         Quick count
TOTAL               ~60 sec         ✓         Including backup

T = TRUNCATE version
D = DELETE version
```

---

## File Organization

```
DATABASE FOLDER STRUCTURE:
════════════════════════════════════════════════════════════

database/
│
├─ Schema Files
│  ├─ schema.sql                    (Main schema)
│  ├─ schema_clean_no_triggers.sql  (Clean version)
│  └─ ... [other schemas]
│
├─ CLEARANCE PACKAGE FILES
│  ├─ CLEAR_DATA_SP.sql                    ⭐ Main procedure
│  ├─ CLEAR_DATA_DELETE_SP.sql             ⭐ Alternative
│  ├─ VERIFY_CLEAR_DATA.sql                ⭐ Pre-check
│  ├─ QUICK_CLEAR_COMMANDS.sql             ⭐ Copy-paste
│  ├─ CLEAR_DATA_README.md                 ⭐ Full docs
│  ├─ CLEARANCE_EXECUTION_LOG.md           ⭐ Checklist
│  ├─ CLEAR_DATA_PACKAGE_SUMMARY.md        ⭐ Summary
│  └─ CLEAR_DATA_VISUAL_REFERENCE.md       ⭐ This file
│
└─ ... [other files]

⭐ = Clearance package files (work together)
```

---

Print this reference and keep it handy during execution!
