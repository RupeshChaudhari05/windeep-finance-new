# WINDEEP FINANCE - BUG LIST & PRIORITY MATRIX
**Date:** January 6, 2026  
**Classification:** Critical, High, Medium, Low

---

## CRITICAL SEVERITY (P0) - FIX BEFORE PRODUCTION

### BUG #4: EMI Rounding Causes Principal Mismatch
**Module:** Loan Management  
**Location:** `Loan_model::generate_installment_schedule()`  
**Impact:** Last EMI has incorrect amount due to cumulative rounding errors  
**Financial Impact:** High - Causes member confusion and payment mismatch  
**Reproduction:**
```
1. Create reducing balance loan ₹100,000 @ 12% for 12 months
2. Check last installment - amount differs from others
3. Sum all principal_amounts - doesn't equal principal
```
**Fix Effort:** 2 hours  
**Testing Required:** Unit tests for all interest types, multiple tenures

---

### BUG #7: Duplicate Fine Prevention is Broken
**Module:** Fine Management  
**Location:** `Fine_model::apply_loan_late_fine()`  
**Impact:** Member can be charged double fine if cron runs twice  
**Financial Impact:** Critical - Direct financial loss to members  
**Reproduction:**
```
1. Run fine cron job
2. Re-run immediately
3. Check fines table - duplicate entry for same installment
```
**Fix Effort:** 1 hour  
**Testing Required:** Idempotency tests, concurrent execution tests

---

### BUG #10: UTR Number Uniqueness NOT Enforced
**Module:** Bank Statement Import  
**Location:** Database schema + `Bank_model::parse_csv()`  
**Impact:** Same bank transaction imported multiple times  
**Financial Impact:** Critical - Duplicate payment recording  
**Reproduction:**
```
1. Import statement with UTR "ABC123"
2. Import same statement again
3. Check bank_transactions - duplicate UTR exists
```
**Fix Effort:** 30 minutes (schema) + 1 hour (validation)  
**Testing Required:** Import same file twice, verify rejection

---

### BUG #13: Running Balance Calculation is UNSAFE
**Module:** Ledger Management  
**Location:** `Ledger_model::create_member_ledger_entry()`  
**Impact:** Race condition causes balance inconsistency  
**Financial Impact:** Critical - Audit trail corruption  
**Reproduction:**
```
1. Make 2 simultaneous payments for same member
2. Check member_ledger running_balance
3. Balance will be incorrect
```
**Fix Effort:** 4 hours (implement locking or recalculation approach)  
**Testing Required:** Load testing with concurrent transactions

---

### BUG #16: Payment Allocation Order is INCONSISTENT
**Module:** Loan Payment  
**Location:** `Loan_model::record_payment()`  
**Impact:** Pays fine first instead of interest (wrong banking practice)  
**Financial Impact:** High - Increases interest burden on members  
**Reproduction:**
```
1. Member has overdue: Principal ₹5k + Interest ₹3k + Fine ₹500
2. Pay ₹4,000
3. System pays: Fine ₹500 + Interest ₹3,000 + Principal ₹500
4. CORRECT should be: Interest ₹3,000 + Principal ₹1,000 (fine unpaid)
```
**Fix Effort:** 6 hours (rewrite allocation logic + update installments)  
**Testing Required:** Multiple payment scenarios, RBI compliance verification

---

### BUG #17: Skip EMI Recalculation is MATHEMATICALLY WRONG
**Module:** Loan Management  
**Location:** `Loan_model::adjust_schedule_after_skip()`  
**Impact:** Distributes only principal, ignores interest recalculation  
**Financial Impact:** Critical - Wrong EMI amounts after skip  
**Reproduction:**
```
1. Create loan with 12 month tenure
2. Skip month 3
3. Check remaining installments - interest calculation is wrong
```
**Fix Effort:** 8 hours (regenerate entire schedule)  
**Testing Required:** Skip scenarios at different stages, verify totals

---

## HIGH SEVERITY (P1) - FIX IN PHASE 1

### BUG #1: Disbursement Date Validation Missing
**Module:** Loan Disbursement  
**Location:** `Loan_model::disburse_loan()`  
**Impact:** First EMI date can be before disbursement date  
**Financial Impact:** Medium - Invalid schedules, incorrect interest periods  
**Fix Effort:** 1 hour  
**Testing Required:** Edge case validation

---

### BUG #2: Loan-to-Savings Ratio NOT Enforced
**Module:** Loan Approval  
**Location:** `Loan_model::admin_approve()`  
**Impact:** System allows loans exceeding product risk limits  
**Financial Impact:** High - Increased default risk  
**Fix Effort:** 2 hours  
**Testing Required:** Product rule enforcement tests

---

### BUG #5: Flat Interest Calculation Inconsistency
**Module:** EMI Calculation  
**Location:** `Loan_model::calculate_emi()` vs `generate_installment_schedule()`  
**Impact:** Two functions calculate flat interest differently  
**Financial Impact:** Medium - Potential incorrect totals  
**Fix Effort:** 3 hours  
**Testing Required:** Flat interest loans at various amounts/tenures

---

### BUG #11: Split Payment Mapping NOT Implemented
**Module:** Bank Transaction Mapping  
**Location:** `Bank_model::confirm_transaction()`  
**Impact:** Cannot map one bank transaction to multiple payments  
**Financial Impact:** Medium - Manual workarounds required  
**Fix Effort:** 6 hours  
**Testing Required:** Split transaction scenarios

---

### BUG #14: Loan Outstanding Data Redundancy
**Module:** Loan Management  
**Location:** `loans` table vs `loan_installments` aggregate  
**Impact:** Two sources of truth can diverge  
**Financial Impact:** High - Reconciliation failures  
**Fix Effort:** 8 hours (implement calculated fields or triggers)  
**Testing Required:** Concurrent payment processing

---

## MEDIUM SEVERITY (P2) - FIX IN PHASE 2

### BUG #3: Guarantor Release Logic Incomplete
**Module:** Loan Management  
**Location:** `Loan_model::release_guarantors()`  
**Impact:** Guarantors not released on foreclosure/write-off  
**Financial Impact:** Low - Process issue, not financial  
**Fix Effort:** 1 hour  
**Testing Required:** All loan closure scenarios

---

### BUG #6: Zero Interest Rate Edge Case
**Module:** EMI Calculation  
**Location:** `Loan_model::generate_installment_schedule()`  
**Impact:** Interest-free loans not handled in schedule generation  
**Financial Impact:** Low - Rare scenario  
**Fix Effort:** 30 minutes  
**Testing Required:** Interest-free loan test

---

### BUG #8: Daily Fine Update Race Condition
**Module:** Fine Management  
**Location:** `Fine_model::update_daily_fines()`  
**Impact:** Member payment can be lost during fine update  
**Financial Impact:** Medium - Payment attribution error  
**Fix Effort:** 2 hours  
**Testing Required:** Concurrent payment + fine update

---

### BUG #9: Fine Rule Matching is Ambiguous
**Module:** Fine Management  
**Location:** `Fine_model::apply_loan_late_fine()`  
**Impact:** Wrong rule selected when multiple rules match  
**Financial Impact:** Medium - Incorrect fine amounts  
**Fix Effort:** 2 hours  
**Testing Required:** Overlapping rule scenarios

---

### BUG #12: Overpayment Handling Incomplete
**Module:** Loan Payment  
**Location:** `Loan_model::record_payment()`  
**Impact:** Excess amount stored but not applied to future EMIs  
**Financial Impact:** Low - Member loses benefit of advance payment  
**Fix Effort:** 4 hours  
**Testing Required:** Advance payment scenarios

---

### BUG #15: Negative Balance Allowed
**Module:** Database Constraints  
**Location:** Multiple tables (loans, fines, savings)  
**Impact:** Invalid financial states possible  
**Financial Impact:** Low - Data integrity issue  
**Fix Effort:** 2 hours (schema updates)  
**Testing Required:** Constraint validation

---

## SECURITY ISSUES

### SQL Injection Risk
**Location:** `Loan_model::adjust_schedule_after_skip()`  
```php
$this->db->set('principal_amount', 'principal_amount + ' . $additional_per_emi, FALSE)
```
**Risk:** If `$additional_per_emi` is not sanitized, SQL injection possible  
**Fix:** Use query bindings  
**Priority:** HIGH

---

### Authorization Bypass
**Location:** Multiple controllers  
**Risk:** Member can access other member's data  
**Fix:** Add authorization checks in base controller  
**Priority:** HIGH

---

## DATABASE INTEGRITY ISSUES

### Missing Indexes
```sql
-- Slow query: Search members by name
ALTER TABLE members ADD INDEX idx_full_name (first_name, last_name);

-- Slow query: Bank transaction description search
ALTER TABLE bank_transactions ADD FULLTEXT INDEX idx_description_fulltext (description);

-- Slow query: Overdue loans
ALTER TABLE loan_installments ADD INDEX idx_overdue (loan_id, status, due_date);
```

### Missing Foreign Key Cascades
```sql
ALTER TABLE loan_payments 
ADD CONSTRAINT fk_loan_payments_loan 
FOREIGN KEY (loan_id) REFERENCES loans(id) 
ON DELETE CASCADE;
```

### Missing Check Constraints
```sql
ALTER TABLE loans 
ADD CONSTRAINT chk_emi_date_valid
CHECK (first_emi_date > disbursement_date);

ALTER TABLE loans 
ADD CONSTRAINT chk_outstanding_positive 
CHECK (outstanding_principal >= 0);
```

---

## BUG FIX PRIORITY ROADMAP

### Phase 0 (Pre-Production) - CRITICAL ONLY
**Timeline:** 1 week  
**Bugs:** #4, #7, #10, #13, #16, #17  
**Effort:** ~30 hours  
**Must-Have:** All P0 bugs fixed + comprehensive testing

### Phase 1 (Production Launch) - HIGH SEVERITY
**Timeline:** 2 weeks post-launch  
**Bugs:** #1, #2, #5, #11, #14  
**Effort:** ~27 hours  
**Goal:** Operational stability and financial accuracy

### Phase 2 (Enhancement) - MEDIUM SEVERITY
**Timeline:** 1 month post-launch  
**Bugs:** #3, #6, #8, #9, #12, #15  
**Effort:** ~16 hours  
**Goal:** Process improvements and edge case handling

### Phase 3 (Optimization) - LOW SEVERITY & PERFORMANCE
**Timeline:** Ongoing  
**Focus:** Database optimization, security hardening, UX improvements

---

## TESTING CHECKLIST

### Unit Tests Required
- [ ] EMI calculation for all interest types (reducing, flat)
- [ ] Payment allocation hierarchy
- [ ] Fine calculation with all rule types
- [ ] Ledger balance recalculation
- [ ] Skip EMI schedule regeneration

### Integration Tests Required
- [ ] Full loan lifecycle (application → disbursement → payments → closure)
- [ ] Bank statement import with duplicate detection
- [ ] Concurrent payment processing
- [ ] Fine cron job idempotency
- [ ] Member ledger reconciliation

### Load Tests Required
- [ ] 100 concurrent loan payments
- [ ] 1000 bank transaction imports
- [ ] Daily cron jobs with 10,000 active loans

### Security Tests Required
- [ ] SQL injection attempts
- [ ] Authorization bypass attempts
- [ ] Session hijacking
- [ ] File upload validation

---

## POST-FIX VALIDATION

After each bug fix, run:
1. Unit test for specific bug
2. Relevant validation query from `validation_queries.sql`
3. End-to-end test with realistic data
4. Performance test (if applicable)
5. Manual UAT by business team

---

## ESTIMATED TOTAL FIX EFFORT

| Priority | Bugs | Hours | Days (@8h) |
|----------|------|-------|------------|
| P0       | 6    | 30    | 4          |
| P1       | 5    | 27    | 3-4        |
| P2       | 6    | 16    | 2          |
| Security | 2    | 8     | 1          |
| DB/Schema| -    | 4     | 0.5        |
| **TOTAL**| **19** | **85** | **10-11** |

**Note:** Times are developer hours. With testing and review, expect 2-3 weeks total.

---

## SIGN-OFF CRITERIA

### Code Sign-off
- [ ] All P0 bugs fixed
- [ ] All P1 bugs fixed or documented workarounds
- [ ] Code review completed
- [ ] Unit test coverage > 80%
- [ ] Integration tests passing

### Data Sign-off
- [ ] All validation queries return clean results
- [ ] Trial balance is balanced
- [ ] No negative balances exist
- [ ] No orphan records
- [ ] Audit trail is complete

### Business Sign-off
- [ ] UAT completed with test data
- [ ] Finance team approval
- [ ] Operations team training completed
- [ ] Disaster recovery plan tested
- [ ] Go-live checklist completed

---

**Document Version:** 1.0  
**Last Updated:** January 6, 2026  
**Next Review:** After Phase 0 fixes completed
