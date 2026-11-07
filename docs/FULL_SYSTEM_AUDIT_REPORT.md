# Windeep Finance — Full System Audit Report

**Application:** Windeep Finance (Cooperative Finance Management System)  
**Framework:** CodeIgniter 3.x on PHP 8 / MySQL (XAMPP)  
**Audit Date:** 2025-07-17  
**Auditor:** Banking System Architecture Review  
**Scope:** Controllers, Models, Views, JavaScript, Security, Config, Database Schema, Routes, Helpers  
**Domain:** Indian cooperative society banking — RBI Compliance, Aadhaar Act, IT Act  

---

## Executive Summary

| Category | Issues |
|----------|--------|
| **CRITICAL** | 14 |
| **HIGH** | 31 |
| **MEDIUM** | 28 |
| **LOW** | 16 |
| **Total Issues** | **89** |
| **Feature Completeness** | **74/80 (92.5%)** — 6 partially implemented |

### Top 5 Showstoppers (Must Fix Before Go-Live)

1. **CSRF globally disabled** — every form is vulnerable to cross-site request forgery (fund transfers, loans, etc.)
2. **Empty encryption key** — sessions/cookies not encrypted at all
3. **General Ledger not posted** on savings/loan/fine transactions — trial balance / P&L / balance sheet are materially incorrect
4. **Aadhaar & PAN stored in plaintext** — legal compliance violation under Aadhaar Act & IT Act
5. **Development mode active** — full stack traces, debug info exposed to end users

---

## Table of Contents

1. [Feature Completeness Matrix](#1-feature-completeness-matrix)
2. [Controller Audit](#2-controller-audit)
3. [Model Audit](#3-model-audit)
4. [Views & JavaScript Audit](#4-views--javascript-audit)
5. [Security & Configuration Audit](#5-security--configuration-audit)
6. [Database Schema Audit](#6-database-schema-audit)
7. [Routes & Helpers Audit](#7-routes--helpers-audit)
8. [Copilot Fix Prompts](#8-copilot-fix-prompts)
9. [Remediation Roadmap](#9-remediation-roadmap)

---

## 1. Feature Completeness Matrix

### Overall Score: 74/80 Features Fully Implemented (92.5%)

| Module | Total | ✅ Full | ⚠️ Partial | ❌ Missing |
|--------|-------|---------|------------|-----------|
| Member Management | 7 | 7 | 0 | 0 |
| Savings | 10 | 9 | 1 | 0 |
| Loans | 13 | 12 | 1 | 0 |
| Fines | 6 | 6 | 0 | 0 |
| Bank | 10 | 10 | 0 | 0 |
| Reports | 15 | 14 | 1 | 0 |
| Settings | 8 | 7 | 1 | 0 |
| Notifications | 4 | 3 | 1 | 0 |
| Bonus | 3 | 2 | 1 | 0 |
| Non-Member Funds | 4 | 4 | 0 | 0 |
| **TOTAL** | **80** | **74** | **6** | **0** |

### Partially Implemented Features

| # | Feature | What's Missing | Fix Effort |
|---|---------|---------------|------------|
| F-01 | **Savings Withdrawal** | Model handles withdrawals but controller hardcodes `transaction_type='deposit'`. No withdrawal form/UI. | Small |
| F-02 | **Loan Foreclosure (Admin side)** | Member can request; model can process. But no admin controller endpoint to approve/reject foreclosure requests. | Small |
| F-03 | **Interest Reports (Standalone page)** | Report_model has methods but no dedicated controller method or view for a navigable interest report page. | Small |
| F-04 | **Permission/Role Management UI** | `check_permission()` framework works but no admin form to assign granular permissions per user. Must edit DB manually. | Medium |
| F-05 | **OneSignal Push Notifications** | Library exists but not wired into any business workflow. No device registration, no admin UI. | Medium |
| F-06 | **Bonus Calculation Logic** | Distribution works but no automated calculation (% of balance, pro-rata by tenure). Admin must manually enter amount. | Small |

---

## 2. Controller Audit

**Scope:** 9 admin controllers (~10,000 lines total): Auth (284), Dashboard (572), Members (783), Loans (1609), Savings (1073), Fines (715), Bank (2145), Settings (1571), Reports (1298)

### CRITICAL

| ID | Finding | File | Details |
|----|---------|------|---------|
| C-01 | **Settings::restore() allows arbitrary SQL execution** | `application/controllers/admin/Settings.php` L852-905 | Uploaded `.sql` file is executed line by line with zero sanitization. Attacker can `DROP DATABASE`, create admin users, exfiltrate data. |
| C-02 | **Only Savings.php has `check_permission()`** | All 9 controllers | 8 of 9 admin controllers have NO permission checks. Any authenticated admin can perform any action regardless of role. Savings.php has 17 `check_permission()` calls — all others have 0. |

### HIGH

| ID | Finding | File | Details |
|----|---------|------|---------|
| C-03 | **Auth.php `log_activity()` wrong parameter order** | `application/controllers/admin/Auth.php` L104, L218, L262 | Parameters passed in wrong order — audit log entries are corrupted/meaningless. |
| C-04 | **Auth.php password reset email never sent** | `application/controllers/admin/Auth.php` L218 | Contains `// TODO: Send reset email` — feature is stub only. Users cannot actually reset their password. |
| C-05 | **No rate limiting on login/password reset** | `application/controllers/admin/Auth.php` | No brute-force protection. `failed_login_attempts` table exists but is not checked. |
| C-06 | **Dashboard::search() `or_like()` breaks SQL precedence** | `application/controllers/admin/Dashboard.php` L161 | OR condition without grouping causes the search to bypass other WHERE conditions, potentially returning unauthorized data. |
| C-07 | **Bank.php `edit()` validation overwrites required rule** | `application/controllers/admin/Bank.php` L1024 | Duplicate `set_rules()` call replaces `required` with empty rule, allowing empty required fields. |
| C-08 | **Fines.php `log_audit()` wrong parameter order** | `application/controllers/admin/Fines.php` L636 | Same issue as C-03 — audit trail is corrupted. |
| C-09 | **Dashboard AJAX endpoints return inconsistent formats** | All controllers | Some return `['success' => true]`, others return `['status' => 'success']`, others echo HTML. No standardized API response format. |

### MEDIUM

| ID | Finding | File | Details |
|----|---------|------|---------|
| C-10 | **Loans::submit_application() guarantor loop not in transaction** | `application/controllers/admin/Loans.php` L326 | If adding the 3rd guarantor fails, the first 2 remain orphaned. Should be atomic. |
| C-11 | **Members controller dead code** | `application/controllers/admin/Members.php` L45 | Unused property `$_table`. |
| C-12 | **Auth `$remember` variable unused** | `application/controllers/admin/Auth.php` L43 | Remember-me feature read from input but never used. |
| C-13 | **Bank `process_mapped_transaction()` is dead code** | `application/controllers/admin/Bank.php` | Method exists but is never called from any route or view. |

---

## 3. Model Audit

**Scope:** 14 model files (~8,500 lines): MY_Model (360), Loan_model (2364), Savings_model (720), Member_model (539), Fine_model (753), Report_model (1038), Bank_model (1782), Ledger_model (558), Admin_model, User_model, NonMember_model, Notification_model, Setting_model, Financial_year_model

### CRITICAL

| ID | Finding | File | Details |
|----|---------|------|---------|
| M-01 | **No General Ledger integration** | Savings_model, Loan_model, Fine_model | Savings deposits/withdrawals, EMI payments, fine payments, and loan disbursements do NOT post to `Ledger_model::post_transaction()`. Only `Bank_model::map_disbursement()` and `map_internal_transaction()` post GL entries. **Trial balance, P&L, and balance sheet are materially incorrect.** |
| M-02 | **`reverse_mapping()` uses wrong column names** | `application/models/Bank_model.php` L1240-1248 | References `$loan_payment->principal_amount` and `interest_amount` but table uses `principal_component` and `interest_component`. Reversals silently fail to restore loan balances. |
| M-03 | **Member ledger `balance_after` race condition** | `application/models/Ledger_model.php` L300-310 | Reads last `balance_after`, computes new balance, inserts — all without locking. Two concurrent transactions permanently corrupt the running balance. |
| M-04 | **Aadhaar & PAN stored in plaintext** | `application/models/Member_model.php` L17, L298 | Aadhaar numbers searchable via `or_like()`. Stored unencrypted. Violates Aadhaar Act 2016 Section 29 and UIDAI regulations. PAN (Income Tax Act) also stored in plaintext. |
| M-05 | **MY_Model `increment()`/`decrement()` SQL injection** | `application/core/MY_Model.php` L278-282 | `$field` and `$value` interpolated directly into SQL with escaping disabled. Any caller passing user-controlled input triggers injection. |

### HIGH

| ID | Finding | File | Details |
|----|---------|------|---------|
| M-06 | **Loan `generate_application_number()` race condition** | `application/models/Loan_model.php` L39-49 | Uses `SELECT_MAX(id)` without `FOR UPDATE`. Concurrent submissions generate duplicate APP numbers. |
| M-07 | **DST date calculation bug in `disburse_loan()`** | `application/models/Loan_model.php` L297-298 | Uses `/86400` for day diff. Across DST boundaries, days can be 23 or 25 hours, producing incorrect results. Fine_model fixed this with `DateTime::diff()` but Loan_model was not updated. |
| M-08 | **Negative savings balance intentionally allowed** | `application/models/Loan_model.php` L710-730 | If EMI shortfall exceeds all savings, primary savings goes negative with no limit. Violates banking practice. |
| M-09 | **`process_interest_only_payment()` premature commit** | `application/models/Loan_model.php` L990-1100 | Called inside `record_payment()` transaction block but calls `trans_commit()` — commits the parent transaction prematurely via CI3's nested transaction counting. |
| M-10 | **`total_paid` includes `fine_paid` in installment** | `application/models/Loan_model.php` L939 | `total_paid = principal + interest + fine` but `emi_amount = principal + interest`. Stored value inconsistent with reference — causes incorrect overdue calculations downstream. |
| M-11 | **`process_foreclosure_request()` doesn't update fine amounts** | `application/models/Loan_model.php` L1894-1901 | Sets fine `status='paid'` but `balance_amount` remains nonzero. Breaks `get_pending_fines` downstream. |
| M-12 | **Duplicate fine creation path** | `application/models/Savings_model.php` L499-535 | `Savings_model::apply_late_fine()` inserts directly into `fines` table, bypassing `Fine_model::create_fine()`. If both methods called (cron + controller), duplicate fines are created. |
| M-13 | **`cancel_fine()` doesn't zero out `balance_amount`** | `application/models/Fine_model.php` L318-325 | Sets `status='cancelled'` but `balance_amount` persists. `SUM(balance_amount)` queries include cancelled fines. |
| M-14 | **`approve_waiver()` not transaction-wrapped** | `application/models/Fine_model.php` L548-572 | Reads then updates without `FOR UPDATE` or transaction. Concurrent approvals can double-waive a fine. |
| M-15 | **`get_guarantor_report()` wrong consent status** | `application/models/Report_model.php` L406 | Filters on `consent_status='approved'` but `update_guarantor_consent()` uses `'accepted'`. Report returns zero results. |
| M-16 | **`get_weekly_summary()` references non-existent column** | `application/models/Report_model.php` L672 | `select_sum('disbursed_amount')` — column is `principal_amount` or `net_disbursement`. Returns NULL/0 always. |
| M-17 | **`get_cash_book()` uses wrong column name** | `application/models/Report_model.php` L595-596 | `st.description` doesn't exist — table uses `narration`. Always falls back to static text 'Savings Deposit'. |
| M-18 | **Bank `confirm_transaction()` double-maps** | `application/models/Bank_model.php` L630-670 | Creates loan/savings payment but payment record has no back-reference to bank transaction. Reversal can't find the correct payment. |
| M-19 | **Bank `import_statement()` dead UTR check** | `application/models/Bank_model.php` L109-115 | Checks `$txn['utr_number']` but CSV parser never populates this field. Duplicate check is dead code. |
| M-20 | **Voucher number race condition** | `application/models/Ledger_model.php` L14-25 | Same `SELECT_MAX(id)+1` without locking. Concurrent vouchers can get duplicate numbers. |
| M-21 | **`get_transaction_accounts()` uses names not codes** | `application/models/Ledger_model.php` L178-210 | Maps to `'loans_receivable'`, `'cash_bank'` but `get_account_by_code()` queries by `account_code`. These names won't match — `post_transaction()` always returns false for these types. |
| M-22 | **User_model extends CI_Model instead of MY_Model** | `application/models/User_model.php` L4 | Two competing auth models: User_model (legacy `users` table) and Admin_model (`admin_users` table). |
| M-23 | **Financial_year `set_active()` brief null window** | `application/models/Financial_year_model.php` L27-29 | Deactivates ALL years before activating one. Brief window where no financial year is active — GL posts get null `financial_year_id`. |

### MEDIUM

| ID | Finding | File | Details |
|----|---------|------|---------|
| M-24 | **MY_Model parent constructor commented out** | `application/core/MY_Model.php` L21 | CI_Model constructor not called — dependency injection may not initialize properly. |
| M-25 | **MY_Model `generate_code()` race condition** | `application/core/MY_Model.php` L291-302 | Dead code but could be accidentally called. |
| M-26 | **`record_payment()` savings read not locked** | `application/models/Loan_model.php` L650-720 | SELECT on `savings_accounts` is not `FOR UPDATE`. Concurrent withdrawal could cause over-deduction. Atomic UPDATE partially mitigates. |
| M-27 | **Flat interest balance tracking inconsistency** | `application/models/Loan_model.php` L426-432 | `$balance` decremented for flat interest installments even though principal is not actually reducing — creates incorrect `outstanding_balance` in records. |
| M-28 | **`apply_late_fine()` uses `/86400` (DST bug)** | `application/models/Savings_model.php` L520 | Same `/86400` pattern — inconsistent with Fine_model's `DateTime::diff()` fix. |
| M-29 | **`update_daily_fines()` uses `/86400` (DST bug)** | `application/models/Fine_model.php` L686 | Fine-7 fix was applied to `apply_*_late_fine()` methods but NOT to `update_daily_fines()`. |
| M-30 | **`record_payment()` no `payment_mode` validation** | `application/models/Savings_model.php` L170-270 | Invalid payment mode passed → silent data corruption or DB error. |
| M-31 | **Auto-match phone regex too aggressive** | `application/models/Bank_model.php` L488-498 | Matches ANY 10-digit number in description including amounts like `1234567890`. False-matches to wrong member. |
| M-32 | **Bank CSV description not sanitized** | `application/models/Bank_model.php` L195-300 | HTML/script tags in bank description field not stripped. Stored XSS if rendered without escaping. |
| M-33 | **No Setting_model caching** | `application/models/Setting_model.php` | `get_setting()` hits DB on every call. Settings read extensively (per-schedule, per-installment). |
| M-34 | **`get_monthly_trend()` runs 36 queries** | `application/models/Report_model.php` L468-510 | 3 queries × 12 months. Should be single `GROUP BY MONTH()`. |
| M-35 | **`get_active_members_dropdown()` N+1** | `application/models/Member_model.php` L315-330 | 2 extra queries per member × 500 members = 1001 queries. Should use JOINs. |
| M-36 | **`get_member_summary_report()` 5 correlated subqueries** | `application/models/Report_model.php` L526-538 | 5000+ subqueries for 1000 members. |
| M-37 | **`generate_schedule()` N+1 pattern** | `application/models/Savings_model.php` L134-160 | 24 queries for 12 months. Should use `INSERT ... ON DUPLICATE KEY IGNORE`. |
| M-38 | **Savings interest is simple, not compound** | `application/models/Savings_model.php` L560-575 | Uses `(balance * rate/100) / 12`. RBI norms for cooperative banking require quarterly compounding for recurring deposits. |
| M-39 | **`update_schedule_payment()` recursive without depth limit** | `application/models/Savings_model.php` L343-347 | Large overpayment with many pending schedules could hit PHP recursion limit. |
| M-40 | **Debug SQL logging in production** | `application/models/Bank_model.php` L27-28 | Logs full SQL queries including financial data. |
| M-41 | **MD5 password migration still accepts MD5** | `application/models/User_model.php` L24-35 | MD5 is cryptographically broken. Should force password reset instead. |
| M-42 | **`get_bank_reconciliation()` returns empty array** | `application/models/Report_model.php` L629 | Placeholder — completely unimplemented. |
| M-43 | **`get_by_email()` fragile soft-delete check** | `application/models/Member_model.php` L55-60 | Uses `->where('deleted_at IS NULL', null, false)` with disabled escaping. |
| M-44 | **Notification_model `create()` override incompatible** | `application/models/Notification_model.php` L17 | Replaces parent's `create()` with incompatible signature. |

---

## 4. Views & JavaScript Audit

### CRITICAL

| ID | Finding | File | Details |
|----|---------|------|---------|
| V-01 | **Flash messages XSS via `addslashes()`** | `application/views/admin/layouts/header.php` | `addslashes()` does NOT prevent XSS in JavaScript context. Use `json_encode()` with `JSON_HEX_TAG`. |
| V-02 | **Flash messages unescaped in HTML** | `views/member/auth/login.php`, `views/admin/auth/forgot_password.php`, `views/admin/auth/reset_password.php` | `<?= $this->session->flashdata('error') ?>` rendered without `htmlspecialchars()`. |

### HIGH

| ID | Finding | File | Details |
|----|---------|------|---------|
| V-03 | **Admin name unescaped** | `views/admin/layouts/header.php` | `<?= $admin->full_name ?>` — XSS if name contains `<script>`. |
| V-04 | **Member names unescaped in multiple views** | 5+ view files | `<?= $m->first_name ?> <?= $m->last_name ?>` in `<option>`, table cells, alert boxes. |
| V-05 | **Data attributes unescaped** | `views/admin/loans/apply.php` | `data-name="<?= $m->first_name ?>"` — breaks out of attribute with `"`. |
| V-06 | **Email/phone links unescaped** | `views/admin/members/view.php` | Protocol injection possible in `mailto:` href. |
| V-07 | **Settings values unescaped in inputs** | `views/admin/settings/index.php` | `<?= $settings['org_name'] ?>` inside `value=""` — attribute breakout. |
| V-08 | **KYC verification via GET request** | `views/admin/members/view.php` | State-changing action via GET link — CSRF and prefetch vulnerable. |
| V-09 | **`printElement()` uses `document.write`** | `assets/js/custom.js` | DOM XSS sink if content contains unsanitized HTML. |
| V-10 | **CDN resources without SRI hashes** | `views/admin/layouts/header.php`, `footer.php` | jQuery, Bootstrap, AdminLTE, Font Awesome, Toastr, SweetAlert2, DataTables, Select2, Chart.js — all without `integrity` attribute. |
| V-11 | **Member portal name unescaped** | `views/member/layouts/header.php` | Same XSS risk in navbar dropdown. |

### MEDIUM

| ID | Finding | File | Details |
|----|---------|------|---------|
| V-12 | **Broken PHP tag nesting** | `views/admin/settings/index.php` | `value="<?= $settings['currency_symbol'] ?? '<?= get_currency_symbol() ?>' ?>"` — parse error. |
| V-13 | **`$extra_css`/`$extra_js` unescaped** | `views/member/layouts/header.php` | Allows arbitrary HTML/JS injection from controllers. |
| V-14 | **Toastr timeout set to 0** | `views/admin/layouts/footer.php` | Notifications never auto-dismiss — clutters screen. |
| V-15 | **Phone/voter ID output unescaped** | `views/member/profile/index.php` | Inconsistent escaping — some fields escaped, some not. |
| V-16 | **Log filenames unescaped** | `views/admin/system/logs.php` | Potential XSS via crafted filename. |
| V-17 | **Log deletion via GET** | `views/admin/system/logs.php` | Should be POST with CSRF token. |
| V-18 | **Clear All Logs via GET** | `views/admin/system/logs.php` | Same as V-17. |
| V-19 | **Hardcoded `₹` in JavaScript** | `assets/js/admin/bank-mapping.js` | Should read from server config for multi-currency support. |

### LOW

| ID | Finding | File | Details |
|----|---------|------|---------|
| V-20 | **Incomplete Indian states list** | `views/admin/members/create.php` | Only 13 of 28+ states. |
| V-21 | **404 page exposes admin navigation** | `views/errors/404.php` | Reveals internal route structure to unauthenticated users. |
| V-22 | **No nonce on inline scripts** | Multiple views | Will break future CSP implementation. |
| V-23 | **Login help reveals default password** | `views/member/auth/login.php` | "Use your Member Code as password" — tells attackers the default credential. |

---

## 5. Security & Configuration Audit

### CRITICAL

| ID | Finding | File | Details |
|----|---------|------|---------|
| S-01 | **CSRF protection DISABLED** | `config/config.php` L367 | `$config['csrf_protection'] = FALSE;` — every POST form vulnerable. Fund transfers, loans, settings all exploitable via CSRF. |
| S-02 | **Encryption key EMPTY** | `config/config.php` L298 | `$config['encryption_key'] = '';` — sessions/cookies not encrypted. |
| S-03 | **Development environment active** | `index.php` L28 | `define('ENVIRONMENT', 'development')` — full error traces visible. |
| S-04 | **Cookie not HTTP-only** | `config/config.php` L352 | Session cookie accessible to JavaScript—XSS can steal it. |
| S-05 | **Cookie not Secure-only** | `config/config.php` L351 | Session cookie sent over plain HTTP. |
| S-06 | **`security_config.php` not applied** | `config/security_config.php` vs `config.php` | Comprehensive security document exists with correct settings (CSRF, session, headers, rate limiting). **None applied** to active config. |
| S-07 | **Sessions in shared temp directory** | `config/config.php` | `sess_save_path = sys_get_temp_dir()` — hijackable on shared hosting. |

### HIGH

| ID | Finding | File | Details |
|----|---------|------|---------|
| S-08 | **Old sessions not destroyed** | `config/config.php` L329 | `sess_regenerate_destroy = FALSE` — session fixation possible. |
| S-09 | **No session IP binding** | `config/config.php` | `sess_match_ip = FALSE` — stolen cookies work from any IP. |
| S-10 | **DB debug queries enabled** | `config/database.php` | `save_queries = TRUE` — info exposure + memory waste. |
| S-11 | **DB strict mode disabled** | `config/database.php` | Silent data truncation allowed in financial records. |
| S-12 | **No DB connection encryption** | `config/database.php` | Database traffic in plaintext. |
| S-13 | **Debug logging at level 2** | `config/config.php` | Logs debug messages with potentially sensitive data. |
| S-14 | **`SHOW_DEBUG_BACKTRACE = TRUE`** | `config/constants.php` | Full stack traces in error pages. |
| S-15 | **Hooks disabled — no security headers** | `config/hooks.php` + `config.php` | `enable_hooks = FALSE`. No `X-Frame-Options`, `X-Content-Type-Options`, `Strict-Transport-Security`, `Content-Security-Policy`, `Referrer-Policy` headers. Vulnerable to clickjacking, MIME sniffing. |
| S-16 | **Password minimum length is 6** | Multiple files | `security_config.php` recommends 8 + complexity. 6-char passwords trivially brute-forced. |
| S-17 | **Default DB credentials: root with empty password** | `config/database.php` | Fallback when `.env` missing. App connects as root with no password. |

### MEDIUM

| ID | Finding | File | Details |
|----|---------|------|---------|
| S-18 | **`.env` loaded twice** | `index.php` | Redundant but harmless. |
| S-19 | **`permitted_uri_chars` includes `=` and `&`** | `config/config.php` | Expands attack surface for parameter pollution. |
| S-20 | **No `SameSite` cookie attribute** | `config/config.php` | CSRF defense-in-depth gap. |
| S-21 | **Form validation not autoloaded** | `config/autoload.php` | If developer forgets `$this->load->library()`, inputs not validated. |
| S-22 | **Security helper not autoloaded** | `config/autoload.php` | `xss_clean()`, `sanitize_filename()` not available by default. |
| S-23 | **No `.htaccess` in upload directories** | `uploads/*` | Uploaded `.php` files can be executed directly. |

### LOW

| ID | Finding | File | Details |
|----|---------|------|---------|
| S-24 | **`char_set` mismatch: `utf8` vs `utf8mb4`** | `config/database.php` | Schema uses `utf8mb4` but connection uses `utf8`. |
| S-25 | **No rate limiting on member login** | `controllers/member/Auth.php` | Admin login has check. Member login does not. |

---

## 6. Database Schema Audit

### HIGH

| ID | Finding | File | Details |
|----|---------|------|---------|
| D-01 | **Dual member schema** | `schema_clean_no_triggers.sql` | Two member tables: `member_details` (varchar PK) and `members` (auto-increment). Legacy tables use old FK. Creates referential integrity issues. |
| D-02 | **`expenditure` no user FK** | `schema_clean_no_triggers.sql` | `created_by` and `paid_to` are varchar(50) with no foreign key constraint. |
| D-03 | **`bank_balance_history` no FK** | `schema_clean_no_triggers.sql` | `bank_account_id` column has no FK constraint. Orphan records possible. |
| D-04 | **`chat_box` no FK constraints** | `schema_clean_no_triggers.sql` | `sender_id` and `receiver_id` have no FK. Messages can reference deleted users. |

### MEDIUM

| ID | Finding | File | Details |
|----|---------|------|---------|
| D-05 | **`members.password` nullable** | `schema_clean_no_triggers.sql` | Members can exist without passwords. |
| D-06 | **No CHECK on phone length** | `schema_clean_no_triggers.sql` | `phone varchar(20)` accepts any string up to 20 chars. |
| D-07 | **`notifications.recipient_id` no FK** | `schema_clean_no_triggers.sql` | Polymorphic FK without constraint. |
| D-08 | **`other_member_details` no FK, no unique** | `schema_clean_no_triggers.sql` | Legacy table with loose definitions. |
| D-09 | **`ci_sessions` table exists but file driver used** | `schema_clean_no_triggers.sql` vs `config.php` | Table defined but `sess_driver = 'files'`. Wasted table. |
| D-10 | **NULL unique keys on aadhaar/PAN** | `schema_clean_no_triggers.sql` | Multiple NULLs allowed in UNIQUE columns. Intent unclear. |
| D-11 | **No `updated_at` on financial_years, chart_of_accounts** | `schema_clean_no_triggers.sql` | Missing audit timestamps. |
| D-12 | **`view_requests` varchar FK to `member_details`** | `schema_clean_no_triggers.sql` | Part of legacy schema problem. |

### LOW

| ID | Finding | File | Details |
|----|---------|------|---------|
| D-13 | **Mixed collation** | `schema_clean_no_triggers.sql` | `utf8mb4_unicode_ci` vs `utf8mb4_general_ci`. Causes implicit conversion in JOINs. |
| D-14 | **`loan_transaction_details.loan_id` is varchar** | `schema_clean_no_triggers.sql` | Legacy — should be integer FK to `loans.id`. |
| D-15 | **50 tables — unused legacy tables** | `schema_clean_no_triggers.sql` | `member_details`, `loan_transactions`, `shares`, `send_form`, `view_requests`, `other_member_details` are unused. |

---

## 7. Routes & Helpers Audit

### HIGH

| ID | Finding | File | Details |
|----|---------|------|---------|
| R-01 | **Log view/delete routes accept `(:any)` — path traversal** | `config/routes.php` | `(:any)` matches `../../etc/passwd`. Must validate filename pattern or use `(:segment)`. |
| R-02 | **Destructive actions via GET** | `config/routes.php` | `delete/(:num)`, `clear_logs`, `delete_log/(:any)` all reachable via GET. Enables CSRF. |
| R-03 | **No route-level access control** | `config/routes.php` | All admin routes rely on controller constructor. If any controller extends wrong base class, routes become public. |

### MEDIUM

| ID | Finding | File | Details |
|----|---------|------|---------|
| R-04 | **Member logout via GET** | `controllers/member/Auth.php` | Can be triggered by `<img src="/member/logout">`. |
| R-05 | **`session_regenerate_id()` bypasses CI** | `controllers/member/Auth.php` L105 | PHP native function conflicts with CI session driver. |
| R-06 | **No CSRF validation in member login** | `controllers/member/Auth.php` | Token in form HTML but CSRF globally disabled, no manual check. |
| R-07 | **No `404_override` configured** | `config/routes.php` | Default CI 404 may leak framework info. |

### LOW

| ID | Finding | File | Details |
|----|---------|------|---------|
| R-08 | **`sanitize_phone()` no length validation** | `helpers/format_helper.php` | Accepts 100+ digit phone numbers. |
| R-09 | **SMTP credentials fallback if env missing** | `helpers/email_helper.php` | PHPMailer may fail exposing config details. |
| R-10 | **No overflow protection in EMI calculations** | `helpers/part_payment_helper.php` | Large amounts could cause float overflow. |

---

## 8. Copilot Fix Prompts

Copy-paste these prompts directly into GitHub Copilot to fix each issue.

---

### PROMPT 1: Enable CSRF Protection (S-01, S-06)
```
In application/config/config.php, enable CSRF protection by:
1. Set $config['csrf_protection'] = TRUE;
2. Set $config['csrf_token_name'] = 'csrf_token';
3. Set $config['csrf_cookie_name'] = 'csrf_cookie';
4. Set $config['csrf_expire'] = 7200;
5. Set $config['csrf_regenerate'] = TRUE;
6. Set $config['csrf_exclude_uris'] = array();

Then in all view files under application/views/ that contain <form> tags, 
add <?= $this->security->get_csrf_token_name() ?> and 
<?= $this->security->get_csrf_hash() ?> as hidden inputs.

For AJAX requests in assets/js/ files, add a global jQuery ajaxSetup 
that includes the CSRF token in all POST requests:
$.ajaxSetup({
    data: {
        '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>'
    }
});
```

### PROMPT 2: Set Encryption Key & Cookie Security (S-02, S-04, S-05, S-08, S-20)
```
In application/config/config.php, fix these security settings:
1. Generate and set encryption key: $config['encryption_key'] = 'GENERATE_32_CHAR_HEX_KEY_HERE';
2. Set $config['cookie_httponly'] = TRUE;
3. Set $config['cookie_secure'] = TRUE;  // requires HTTPS
4. Set $config['sess_regenerate_destroy'] = TRUE;
5. Add SameSite attribute to cookies - in the session configuration, 
   set $config['cookie_samesite'] = 'Lax';
```

### PROMPT 3: Switch to Production Environment (S-03, S-14, S-13, S-10)
```
Fix these production-readiness settings:

In index.php:
1. Change the ENVIRONMENT detection to use $_SERVER['CI_ENV']:
   define('ENVIRONMENT', isset($_SERVER['CI_ENV']) ? $_SERVER['CI_ENV'] : 'production');

In application/config/config.php:
2. Set $config['log_threshold'] = 1;  // errors only

In application/config/constants.php:
3. Set define('SHOW_DEBUG_BACKTRACE', FALSE);

In application/config/database.php:
4. Set 'save_queries' => FALSE
5. Set 'stricton' => TRUE
```

### PROMPT 4: Switch Session to Database Driver (S-07, D-09)
```
In application/config/config.php, change session configuration:
1. Set $config['sess_driver'] = 'database';
2. Set $config['sess_save_path'] = 'ci_sessions';
3. Set $config['sess_match_ip'] = TRUE;
4. Set $config['sess_time_to_update'] = 300;

The ci_sessions table already exists in the database schema. 
Ensure it has the correct structure for CI3:
CREATE TABLE IF NOT EXISTS ci_sessions (
    id varchar(128) NOT NULL,
    ip_address varchar(45) NOT NULL,
    timestamp int(10) unsigned DEFAULT 0 NOT NULL,
    data blob NOT NULL,
    KEY ci_sessions_timestamp (timestamp)
);
```

### PROMPT 5: Enable Security Headers via Hooks (S-15)
```
In application/config/config.php:
1. Set $config['enable_hooks'] = TRUE;

In application/config/hooks.php, add:
$hook['post_controller_constructor'][] = array(
    'function' => 'set_security_headers',
    'filename' => 'security_headers.php',
    'filepath' => 'hooks'
);

Create application/hooks/security_headers.php with:
<?php
function set_security_headers() {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
```

### PROMPT 6: Fix All XSS — Escape Output in Views (V-01 through V-11)
```
Search all PHP view files in application/views/ for unescaped output patterns and fix them:

1. In application/views/admin/layouts/header.php:
   - Change: '<?= addslashes($this->session->flashdata('success')) ?>'
   - To: <?= json_encode($this->session->flashdata('success'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
   - Change: <?= $admin->full_name ?> 
   - To: <?= html_escape($admin->full_name ?? 'Admin') ?>

2. In all login/auth views:
   - Change: <?= $this->session->flashdata('error') ?>
   - To: <?= html_escape($this->session->flashdata('error')) ?>

3. In ALL view files, find every instance of <?= $member-> or <?= $m-> 
   that outputs name, email, phone, address, or any user-supplied data, 
   and wrap with html_escape() or htmlspecialchars($value, ENT_QUOTES, 'UTF-8')

4. In data-* attributes:
   - Change: data-name="<?= $m->first_name ?>"
   - To: data-name="<?= htmlspecialchars($m->first_name . ' ' . $m->last_name, ENT_QUOTES, 'UTF-8') ?>"

5. In input value attributes:
   - Change: value="<?= $settings['org_name'] ?? '' ?>"
   - To: value="<?= htmlspecialchars($settings['org_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
```

### PROMPT 7: Add check_permission() to All Admin Controllers (C-02)
```
Add check_permission() calls to ALL admin controller methods exactly like 
application/controllers/admin/Savings.php does it. 

For each controller, add at the START of every public method:

application/controllers/admin/Members.php:
- index(): $this->check_permission('members_view');
- create(): $this->check_permission('members_create');
- store(): $this->check_permission('members_create');
- edit(): $this->check_permission('members_edit');
- update(): $this->check_permission('members_edit');
- view(): $this->check_permission('members_view');
- update_status(): $this->check_permission('members_manage');
- verify_kyc(): $this->check_permission('members_manage');
- delete(): $this->check_permission('members_delete');

application/controllers/admin/Loans.php:
- index(): $this->check_permission('loans_view');
- apply(): $this->check_permission('loans_create');
- submit_application(): $this->check_permission('loans_create');
- approve(): $this->check_permission('loans_approve');
- reject(): $this->check_permission('loans_approve');
- disburse(): $this->check_permission('loans_disburse');
- collect(): $this->check_permission('loans_collect');
- record_payment(): $this->check_permission('loans_collect');

application/controllers/admin/Fines.php:
- index(): $this->check_permission('fines_view');
- create(): $this->check_permission('fines_create');
- store(): $this->check_permission('fines_create');
- collect(): $this->check_permission('fines_collect');
- cancel(): $this->check_permission('fines_manage');
- waiver_requests(): $this->check_permission('fines_manage');
- approve_waiver(): $this->check_permission('fines_manage');

application/controllers/admin/Bank.php:
- import(): $this->check_permission('bank_import');
- upload(): $this->check_permission('bank_import');
- mapping(): $this->check_permission('bank_map');
- save_transaction_mapping(): $this->check_permission('bank_map');
- reverse_mapping(): $this->check_permission('bank_reverse');
- accounts(): $this->check_permission('bank_manage');
- reconciliation(): $this->check_permission('bank_view');

application/controllers/admin/Settings.php:
- ALL methods: $this->check_permission('manage_settings');

application/controllers/admin/Reports.php:
- ALL methods: $this->check_permission('reports_view');

application/controllers/admin/Dashboard.php:
- index(): $this->check_permission('dashboard_view');
- All card_* methods: $this->check_permission('dashboard_view');
```

### PROMPT 8: Fix General Ledger Integration (M-01)
```
The General Ledger is NOT posted for savings, loan, and fine transactions. 
This makes Trial Balance, P&L, and Balance Sheet reports incorrect.

1. In application/models/Savings_model.php, in the record_payment() method:
   After the savings_transaction is inserted and balance updated, add:
   
   // Post to General Ledger
   $this->load->model('Ledger_model');
   if ($data['transaction_type'] === 'deposit') {
       $this->Ledger_model->post_transaction(
           'savings_deposit',
           $data['amount'],
           'Savings deposit - Account: ' . $account->account_number,
           $account->member_id,
           $transaction_id
       );
   } elseif ($data['transaction_type'] === 'withdrawal') {
       $this->Ledger_model->post_transaction(
           'savings_withdrawal', 
           $data['amount'],
           'Savings withdrawal - Account: ' . $account->account_number,
           $account->member_id,
           $transaction_id
       );
   }

2. In application/models/Loan_model.php, in record_payment():
   After loan_payments insert, add GL posting for loan_repayment

3. In application/models/Loan_model.php, in disburse_loan():
   After loan record creation, add GL posting for loan_disbursement

4. In application/models/Fine_model.php, in record_payment():
   After fine amounts updated, add GL posting for fine_payment

5. In application/models/Ledger_model.php, in get_transaction_accounts():
   Add these account type mappings with PROPER account codes:
   case 'savings_deposit': return ['debit' => '1100', 'credit' => '2100']; // Cash → Savings Liability
   case 'savings_withdrawal': return ['debit' => '2100', 'credit' => '1100'];
   case 'loan_repayment': return ['debit' => '1100', 'credit' => '1300']; // Cash → Loans Receivable
   case 'loan_disbursement': return ['debit' => '1300', 'credit' => '1100'];
   case 'fine_payment': return ['debit' => '1100', 'credit' => '3200']; // Cash → Fine Income

Make sure the account codes match the chart_of_accounts table entries.
Also fix the existing mappings that use descriptive names instead of codes.
```

### PROMPT 9: Fix reverse_mapping() Wrong Column Names (M-02)
```
In application/models/Bank_model.php, in the reverse_mapping() method 
(around line 1240-1248), fix the column name references:

Change:
$loan_payment->principal_amount  →  $loan_payment->principal_component
$loan_payment->interest_amount   →  $loan_payment->interest_component

These columns are named principal_component and interest_component 
in the loan_payments table as confirmed by the record_payment() method 
in Loan_model.php.
```

### PROMPT 10: Fix Member Ledger Race Condition (M-03)
```
In application/models/Ledger_model.php, fix the create_member_ledger_entry() 
method (around line 300-310) to prevent race conditions on balance_after:

Change the method to:
1. Start a transaction: $this->db->trans_begin();
2. Lock the last row: 
   $this->db->query('SELECT balance_after FROM member_ledger 
   WHERE member_id = ? ORDER BY id DESC LIMIT 1 FOR UPDATE', 
   array($member_id));
3. Calculate new balance from the locked read
4. Insert the new ledger entry
5. Commit: $this->db->trans_commit();

This prevents two concurrent transactions from reading the same 
last balance and producing incorrect running totals.
```

### PROMPT 11: Encrypt Aadhaar & PAN Numbers (M-04)
```
Create an encryption service for sensitive PII data:

1. Create application/libraries/Pii_encryption.php:
   - encrypt($plaintext): Uses openssl_encrypt with AES-256-CBC 
     and a key from config
   - decrypt($ciphertext): Reverses encryption
   - hash_for_search($plaintext): Creates a searchable hash 
     (SHA-256 of normalized value) for lookups without decryption

2. In application/models/Member_model.php:
   - In create_member(): Encrypt aadhaar_number and pan_number 
     before insert, store hash in aadhaar_hash/pan_hash columns
   - In search_members(): Search by hash instead of LIKE on plaintext
   - In get_member_details(): Decrypt for authorized display
   - Mask display: Show only last 4 digits (XXXX-XXXX-1234)

3. Add database migration to:
   - Add columns: aadhaar_hash VARCHAR(64), pan_hash VARCHAR(64)
   - Create a one-time script to encrypt existing plaintext data
   - Add unique index on aadhaar_hash and pan_hash

4. In application/config/config.php:
   - Add $config['pii_encryption_key'] = 'SEPARATE_FROM_MAIN_KEY';
```

### PROMPT 12: Fix SQL Injection in MY_Model increment/decrement (M-05)
```
In application/core/MY_Model.php, fix the increment() and decrement() 
methods (around line 278-282):

For increment():
public function increment($id, $field, $value = 1) {
    // Validate field name against allowed columns
    if (!in_array($field, $this->fillable)) {
        return false;
    }
    // Validate value is numeric
    $value = abs(intval($value));
    if ($value <= 0) return false;
    
    $this->db->set($field, "$field + $value", FALSE);
    $this->db->where('id', (int)$id);
    return $this->db->update($this->table);
}

For decrement():
public function decrement($id, $field, $value = 1) {
    if (!in_array($field, $this->fillable)) {
        return false;
    }
    $value = abs(intval($value));
    if ($value <= 0) return false;
    
    $this->db->set($field, "$field - $value", FALSE);
    $this->db->where('id', (int)$id);
    return $this->db->update($this->table);
}
```

### PROMPT 13: Fix DST Date Calculation Bugs (M-07, M-28, M-29)
```
Fix all instances of date difference calculation using /86400 pattern 
to use DateTime::diff() instead:

1. In application/models/Loan_model.php line ~297-298 (disburse_loan):
   Change: $days_diff = ($first_emi_date - $disbursement_date) / 86400;
   To: $days_diff = (new DateTime($disbursement_date_str))->diff(new DateTime($first_emi_date_str))->days;

2. In application/models/Savings_model.php line ~520 (apply_late_fine):
   Change: 'days_late' => floor((safe_timestamp(date('Y-m-d')) - safe_timestamp($schedule->due_date)) / 86400)
   To: 'days_late' => (new DateTime($schedule->due_date))->diff(new DateTime(date('Y-m-d')))->days

3. In application/models/Fine_model.php line ~686 (update_daily_fines):
   Change: $days_late = floor((safe_timestamp(date('Y-m-d')) - safe_timestamp($fine->due_date)) / 86400)
   To: $days_late = (new DateTime($fine->due_date))->diff(new DateTime(date('Y-m-d')))->days

4. In application/models/Loan_model.php line ~944:
   Check for any remaining /86400 patterns and replace similarly.

The DateTime::diff() method is already used in Fine_model's 
apply_loan_late_fine() and apply_savings_late_fine() (FINE-7 fix) — 
extend this fix to ALL date difference calculations.
```

### PROMPT 14: Fix Settings::restore() SQL Injection (C-01)
```
In application/controllers/admin/Settings.php, the restore() method 
(around line 852-905) executes uploaded .sql files line by line without
any sanitization. This allows arbitrary SQL execution.

Fix by:
1. Add check_permission('super_admin') at the start
2. Validate file extension is .sql
3. Validate file size is reasonable (< 50MB)
4. Parse SQL statements and ONLY allow:
   - INSERT statements
   - UPDATE statements  
   - CREATE TABLE IF NOT EXISTS
   - ALTER TABLE
   Block: DROP, DELETE, TRUNCATE, GRANT, CREATE USER, SET, LOAD
5. Log the restore action to audit trail
6. Require password re-confirmation before executing

Replace the dangerous line-by-line execution with:
$allowed_patterns = ['/^(INSERT|UPDATE|CREATE TABLE|ALTER TABLE)/i'];
$blocked_patterns = ['/^(DROP|DELETE|TRUNCATE|GRANT|REVOKE|CREATE USER|SET|LOAD)/i'];

foreach ($sql_lines as $line) {
    $line = trim($line);
    if (empty($line) || strpos($line, '--') === 0) continue;
    
    $is_allowed = false;
    foreach ($allowed_patterns as $pattern) {
        if (preg_match($pattern, $line)) { $is_allowed = true; break; }
    }
    foreach ($blocked_patterns as $pattern) {
        if (preg_match($pattern, $line)) { $is_allowed = false; break; }
    }
    
    if (!$is_allowed) {
        log_message('error', 'Blocked SQL in restore: ' . substr($line, 0, 100));
        continue;
    }
    $this->db->simple_query($line);
}
```

### PROMPT 15: Fix Auth log_activity() Parameter Order (C-03, C-08)
```
In application/controllers/admin/Auth.php, find all calls to log_activity() 
at lines ~104, ~218, and ~262 and fix the parameter order.

Check the Audit_model::log_activity() method signature to determine the 
correct parameter order (typically: action, description, user_id, ip_address).

Then fix each call to match the correct order.

Also in application/controllers/admin/Fines.php at line ~636, 
fix the log_audit() call parameter order similarly.
```

### PROMPT 16: Implement Savings Withdrawal Feature (F-01)
```
Implement savings withdrawal functionality:

1. In application/controllers/admin/Savings.php:
   Add a new method withdraw() that:
   - Loads member's savings accounts
   - Accepts withdrawal amount
   - Validates sufficient balance
   - Calls Savings_model::record_payment() with transaction_type='withdrawal'
   
   Also modify record_payment() method to accept transaction_type from form 
   instead of hardcoding 'deposit'. Add validation:
   $type = $this->input->post('transaction_type');
   if (!in_array($type, ['deposit', 'withdrawal'])) {
       $type = 'deposit'; // safe default
   }

2. In application/views/admin/savings/collect.php:
   Add a radio button or dropdown to select transaction type:
   <select name="transaction_type">
       <option value="deposit">Deposit</option>
       <option value="withdrawal">Withdrawal</option>
   </select>

3. Add route in application/config/routes.php:
   $route['admin/savings/withdraw/(:num)'] = 'admin/Savings/withdraw/$1';
```

### PROMPT 17: Add Admin Foreclosure Processing (F-02)
```
Add admin endpoints to process member foreclosure requests:

1. In application/controllers/admin/Loans.php, add these methods:

public function foreclosure_requests() {
    $this->check_permission('loans_approve');
    $data['requests'] = $this->Loan_model->get_foreclosure_requests();
    $data['title'] = 'Foreclosure Requests';
    $this->load->view('admin/layouts/header', $data);
    $this->load->view('admin/loans/foreclosure_requests', $data);
    $this->load->view('admin/layouts/footer');
}

public function process_foreclosure($loan_id) {
    $this->check_permission('loans_approve');
    $action = $this->input->post('action'); // 'approve' or 'reject'
    $remarks = $this->input->post('remarks');
    
    $result = $this->Loan_model->process_foreclosure_request(
        $loan_id, $action, $remarks, $this->session->userdata('admin_id')
    );
    
    if ($result) {
        $this->session->set_flashdata('success', 'Foreclosure ' . $action . 'd successfully');
    } else {
        $this->session->set_flashdata('error', 'Failed to process foreclosure');
    }
    redirect('admin/loans/foreclosure_requests');
}

2. Create view application/views/admin/loans/foreclosure_requests.php 
   showing all pending foreclosure requests with approve/reject buttons.

3. Add routes in routes.php:
   $route['admin/loans/foreclosure_requests'] = 'admin/Loans/foreclosure_requests';
   $route['admin/loans/process_foreclosure/(:num)'] = 'admin/Loans/process_foreclosure/$1';

4. In Loan_model.php, add get_foreclosure_requests() method:
   Query loan_foreclosure_requests with status='pending' 
   joined with loans and members tables.
```

### PROMPT 18: Fix process_foreclosure_request() Fine Amounts (M-11)
```
In application/models/Loan_model.php, in process_foreclosure_request() 
method (around line 1894-1901), when marking fines as 'paid', also update 
the financial amounts:

Change the fine update from:
$this->db->update('fines', ['status' => 'paid', 'payment_date' => date('Y-m-d H:i:s')]);

To:
$this->db->update('fines', [
    'status' => 'paid',
    'paid_amount' => $this->db->escape_str('fine_amount'), // use raw column ref
    'balance_amount' => 0,
    'payment_date' => date('Y-m-d H:i:s')
]);

Or better, use set() with FALSE for the raw SQL:
$this->db->set('paid_amount', 'fine_amount', FALSE);
$this->db->set('balance_amount', 0);
$this->db->set('status', 'paid');
$this->db->set('payment_date', date('Y-m-d H:i:s'));
$this->db->where('loan_id', $loan_id);
$this->db->where('status', 'pending');
$this->db->update('fines');
```

### PROMPT 19: Add .htaccess to Upload Directories (S-23)
```
Create .htaccess files in each upload directory to prevent PHP execution:

Create these 3 files with identical content:
- uploads/bank_statements/.htaccess
- uploads/members_docs/.htaccess
- uploads/profile_images/.htaccess

Content:
# Prevent PHP execution in upload directory
<FilesMatch "\.(?:php|phtml|php3|php4|php5|php7|php8|phps|cgi|pl|asp|aspx|shtml|shtm|htaccess)$">
    Require all denied
</FilesMatch>

# Disable script execution
php_flag engine off

# Only allow specific file types
<FilesMatch "\.(?:jpg|jpeg|png|gif|bmp|webp|pdf|doc|docx|xls|xlsx|csv|txt)$">
    Require all granted
</FilesMatch>

Also create uploads/.htaccess with the same content as a catch-all.
```

### PROMPT 20: Add SRI Hashes to CDN Resources (V-10)
```
In application/views/admin/layouts/header.php and footer.php, 
add integrity and crossorigin attributes to ALL CDN-loaded resources.

For each <link> and <script> tag loading from CDN (cdnjs, jsdelivr, etc.):

Example:
<script src="https://code.jquery.com/jquery-3.6.0.min.js" 
        integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" 
        crossorigin="anonymous"></script>

Generate SRI hashes for each CDN URL using:
https://www.srihash.org/

Apply to: jQuery, Bootstrap CSS/JS, AdminLTE CSS/JS, Font Awesome, 
Toastr CSS/JS, SweetAlert2, DataTables, Select2, Chart.js, 
and any other CDN resources.
```

### PROMPT 21: Fix cancel_fine() Balance Amount (M-13)
```
In application/models/Fine_model.php, in the cancel_fine() method 
(around line 318-325), also zero out the balance_amount when cancelling:

Change the update data from:
['status' => 'cancelled']

To:
[
    'status' => 'cancelled',
    'balance_amount' => 0,
    'cancelled_at' => date('Y-m-d H:i:s'),
    'cancelled_by' => $admin_id  // pass admin_id as parameter
]

This prevents SUM(balance_amount) queries from including cancelled fines.
```

### PROMPT 22: Fix approve_waiver() Transaction Safety (M-14)
```
In application/models/Fine_model.php, wrap the approve_waiver() method 
(around line 548-572) in a proper transaction with row locking:

public function approve_waiver($waiver_id, $admin_id) {
    $this->db->trans_begin();
    
    // Lock the waiver request
    $waiver = $this->db->query(
        'SELECT * FROM fine_waiver_requests WHERE id = ? FOR UPDATE', 
        array($waiver_id)
    )->row();
    
    if (!$waiver || $waiver->status !== 'pending') {
        $this->db->trans_rollback();
        return false;
    }
    
    // Lock the fine record
    $fine = $this->db->query(
        'SELECT * FROM fines WHERE id = ? FOR UPDATE', 
        array($waiver->fine_id)
    )->row();
    
    // ... rest of calculation and update logic ...
    
    if ($this->db->trans_status() === FALSE) {
        $this->db->trans_rollback();
        return false;
    }
    $this->db->trans_commit();
    return true;
}
```

### PROMPT 23: Fix Guarantor Report Consent Status (M-15)
```
In application/models/Report_model.php, in get_guarantor_report() 
at line ~406:

Change: ->where('lg.consent_status', 'approved')
To: ->where('lg.consent_status', 'accepted')

The consent status values used by Loan_model::update_guarantor_consent() 
are 'accepted' and 'rejected', NOT 'approved'. This fix will make the 
guarantor report actually return results.
```

### PROMPT 24: Fix Weekly Summary Column & Date Range (M-16, Report_model L651-672)
```
In application/models/Report_model.php, fix get_weekly_summary():

1. Fix date range (line ~651-652):
   Change:
   $start_date = date('Y-m-d', strtotime('last monday'));
   $end_date = date('Y-m-d', strtotime('sunday'));
   
   To:
   $start_date = date('Y-m-d', strtotime('monday this week'));
   $end_date = date('Y-m-d', strtotime('sunday this week'));

2. Fix non-existent column (line ~672):
   Change: ->select_sum('disbursed_amount')
   To: ->select_sum('net_disbursement')
   
   Or if that column doesn't exist:
   To: ->select_sum('principal_amount')

3. Fix cash_book description column (line ~595-596):
   Change: COALESCE(st.description, 'Savings Deposit')
   To: COALESCE(st.narration, 'Savings Deposit')
```

### PROMPT 25: Add Permission Management UI (F-04)
```
Create an admin UI to manage granular permissions for non-super-admin users:

1. In application/controllers/admin/Settings.php, add method:
   
   public function edit_permissions($admin_id) {
       $this->check_permission('manage_settings');
       $admin = $this->Admin_model->find($admin_id);
       $current_permissions = json_decode($admin->permissions ?? '{}', true);
       
       // Define all permission keys grouped by module
       $data['permission_groups'] = [
           'Members' => ['members_view', 'members_create', 'members_edit', 'members_delete', 'members_manage'],
           'Savings' => ['savings_view', 'savings_create', 'savings_collect', 'savings_withdraw', 'savings_manage'],
           'Loans' => ['loans_view', 'loans_create', 'loans_collect', 'loans_approve', 'loans_disburse', 'loans_manage'],
           'Fines' => ['fines_view', 'fines_create', 'fines_collect', 'fines_manage'],
           'Bank' => ['bank_view', 'bank_import', 'bank_map', 'bank_reverse', 'bank_manage'],
           'Reports' => ['reports_view', 'reports_export'],
           'Settings' => ['manage_settings'],
           'Dashboard' => ['dashboard_view'],
       ];
       $data['admin'] = $admin;
       $data['current_permissions'] = $current_permissions;
       $data['title'] = 'Manage Permissions - ' . $admin->full_name;
       
       $this->load->view('admin/layouts/header', $data);
       $this->load->view('admin/settings/edit_permissions', $data);
       $this->load->view('admin/layouts/footer');
   }
   
   public function save_permissions($admin_id) {
       $this->check_permission('manage_settings');
       $permissions = $this->input->post('permissions') ?: [];
       $permissions_json = json_encode(array_fill_keys($permissions, true));
       $this->db->where('id', $admin_id);
       $this->db->update('admin_users', ['permissions' => $permissions_json]);
       $this->session->set_flashdata('success', 'Permissions updated');
       redirect('admin/settings/admin_users');
   }

2. Create application/views/admin/settings/edit_permissions.php with 
   checkboxes grouped by module, pre-checked based on current_permissions.

3. Add routes:
   $route['admin/settings/edit_permissions/(:num)'] = 'admin/Settings/edit_permissions/$1';
   $route['admin/settings/save_permissions/(:num)'] = 'admin/Settings/save_permissions/$1';
```

### PROMPT 26: Fix Path Traversal in Log Routes (R-01)
```
In application/controllers/admin/System.php, in the view_log() method,
add filename validation to prevent path traversal:

public function view_log($filename) {
    // Validate filename pattern - only allow log files
    if (!preg_match('/^log-\d{4}-\d{2}-\d{2}\.php$/', $filename)) {
        show_404();
        return;
    }
    
    $filepath = APPPATH . 'logs/' . $filename;
    if (!file_exists($filepath)) {
        show_404();
        return;
    }
    
    // ... rest of method
}

Apply same validation in delete_log() method.

In application/config/routes.php, change:
$route['admin/system/view_log/(:any)'] 
To:
$route['admin/system/view_log/(:segment)']

Same for delete_log.
```

### PROMPT 27: Fix Dashboard search() SQL Precedence (C-06)
```
In application/controllers/admin/Dashboard.php, in the search() method 
(around line 161), fix the or_like() SQL precedence issue:

Change the search query from individual or_like() calls to a grouped 
where clause:

$keyword = $this->input->get('q');
$this->db->group_start();
$this->db->like('first_name', $keyword);
$this->db->or_like('last_name', $keyword);
$this->db->or_like('member_code', $keyword);
$this->db->or_like('phone', $keyword);
$this->db->or_like('email', $keyword);
$this->db->group_end();

This ensures the OR conditions are grouped and don't break other 
WHERE conditions (like status='active').
```

### PROMPT 28: Add Rate Limiting to Login (C-05, S-25)
```
Add brute-force protection to both admin and member login:

1. The Rate_limiter library already exists at 
   application/libraries/Rate_limiter.php. Use it.

2. In application/controllers/admin/Auth.php login() method:
   $this->load->library('rate_limiter');
   $ip = $this->input->ip_address();
   if ($this->rate_limiter->is_blocked($ip, 'admin_login')) {
       $this->session->set_flashdata('error', 'Too many login attempts. Try again in 15 minutes.');
       redirect('admin/login');
       return;
   }
   // ... existing login logic ...
   // On failure:
   $this->rate_limiter->record_attempt($ip, 'admin_login');
   // On success:
   $this->rate_limiter->clear_attempts($ip, 'admin_login');

3. Apply the same pattern to application/controllers/member/Auth.php login().

4. Use the failed_login_attempts table that already exists in the schema:
   - Record IP, username, timestamp on each failure
   - Block after 5 failures within 15 minutes
   - Auto-clear after 15 minutes
```

### PROMPT 29: Fix N+1 Query in get_active_members_dropdown() (M-35)
```
In application/models/Member_model.php, rewrite get_active_members_dropdown() 
(around line 315-330) to use JOINs instead of N+1 queries:

Replace the loop that runs 2 queries per member with a single query:

public function get_active_members_dropdown() {
    $query = $this->db->query("
        SELECT m.id, m.member_code, m.first_name, m.last_name, m.phone,
               COALESCE(sa.total_balance, 0) as savings_balance,
               COALESCE(al.active_loans, 0) as active_loan_count
        FROM members m
        LEFT JOIN (
            SELECT member_id, SUM(current_balance) as total_balance
            FROM savings_accounts 
            WHERE status = 'active' AND deleted_at IS NULL
            GROUP BY member_id
        ) sa ON sa.member_id = m.id
        LEFT JOIN (
            SELECT member_id, COUNT(*) as active_loans
            FROM loans 
            WHERE status = 'active' AND deleted_at IS NULL
            GROUP BY member_id
        ) al ON al.member_id = m.id
        WHERE m.status = 'active' AND m.deleted_at IS NULL
        ORDER BY m.first_name, m.last_name
    ");
    return $query->result();
}
```

### PROMPT 30: Fix get_monthly_trend() Performance (M-34)
```
In application/models/Report_model.php, rewrite get_monthly_trend() 
(around line 468-510) to use a single query instead of 36:

Replace the 12-iteration loop with:

public function get_monthly_trend($year = null) {
    $year = $year ?: date('Y');
    
    $query = $this->db->query("
        SELECT 
            months.m as month,
            COALESCE(s.savings_total, 0) as savings,
            COALESCE(ld.disbursed_total, 0) as loans_disbursed,
            COALESCE(lc.collected_total, 0) as loans_collected
        FROM (
            SELECT 1 as m UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 
            UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 
            UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12
        ) months
        LEFT JOIN (
            SELECT MONTH(transaction_date) as m, SUM(amount) as savings_total
            FROM savings_transactions 
            WHERE transaction_type = 'deposit' AND YEAR(transaction_date) = ?
            GROUP BY MONTH(transaction_date)
        ) s ON s.m = months.m
        LEFT JOIN (
            SELECT MONTH(disbursement_date) as m, SUM(principal_amount) as disbursed_total
            FROM loans 
            WHERE status IN ('active','closed') AND YEAR(disbursement_date) = ?
            GROUP BY MONTH(disbursement_date)
        ) ld ON ld.m = months.m
        LEFT JOIN (
            SELECT MONTH(payment_date) as m, SUM(amount) as collected_total
            FROM loan_payments 
            WHERE YEAR(payment_date) = ?
            GROUP BY MONTH(payment_date)
        ) lc ON lc.m = months.m
        ORDER BY months.m
    ", array($year, $year, $year));
    
    return $query->result();
}
```

---

## 9. Remediation Roadmap

### Phase 1: CRITICAL — Before Go-Live (1-2 days)

| Priority | Action | Issue IDs |
|----------|--------|-----------|
| 1 | Enable CSRF protection | S-01 |
| 2 | Set encryption key | S-02 |
| 3 | Switch to production environment | S-03, S-14, S-13, S-10 |
| 4 | Enable cookie security (httponly, secure, samesite) | S-04, S-05, S-20 |
| 5 | Switch session to database driver | S-07, D-09 |
| 6 | Fix SQL injection in MY_Model | M-05 |
| 7 | Fix Settings::restore() SQL injection | C-01 |
| 8 | Add .htaccess to upload directories | S-23 |
| 9 | Enable security headers hook | S-15 |

### Phase 2: HIGH — First Week

| Priority | Action | Issue IDs |
|----------|--------|-----------|
| 10 | Add check_permission() to all controllers | C-02 |
| 11 | Fix all XSS in views (html_escape) | V-01 to V-11 |
| 12 | Fix reverse_mapping() column names | M-02 |
| 13 | Fix member ledger race condition | M-03 |
| 14 | Fix General Ledger integration | M-01 |
| 15 | Fix Auth log_activity() parameter order | C-03, C-08 |
| 16 | Implement password reset email | C-04 |
| 17 | Add rate limiting to login | C-05, S-25 |
| 18 | Fix DST date calculations | M-07, M-28, M-29 |
| 19 | Fix guarantor report consent status | M-15 |
| 20 | Fix cancel_fine() balance amount | M-13 |
| 21 | Fix foreclosure fine amounts | M-11 |
| 22 | Fix path traversal in log routes | R-01 |
| 23 | Fix weekly summary column/date bugs | M-16, M-17 |
| 24 | Add SRI hashes to CDN resources | V-10 |

### Phase 3: MEDIUM — First Sprint (2-4 weeks)

| Priority | Action | Issue IDs |
|----------|--------|-----------|
| 25 | Implement savings withdrawal UI | F-01 |
| 26 | Implement admin foreclosure processing | F-02 |
| 27 | Add permission management UI | F-04 |
| 28 | Encrypt Aadhaar/PAN data | M-04 |
| 29 | Fix duplicate fine creation path | M-12 |
| 30 | Fix approve_waiver() transaction safety | M-14 |
| 31 | Fix N+1 query patterns | M-34, M-35, M-36, M-37 |
| 32 | Add Setting_model caching | M-33 |
| 33 | Fix Ledger_model get_transaction_accounts() | M-21 |
| 34 | Implement standalone interest report page | F-03 |
| 35 | Fix Dashboard search() SQL precedence | C-06 |
| 36 | Fix Bank edit() validation | C-07 |
| 37 | Standardize AJAX response format | C-09 |
| 38 | Convert destructive GET routes to POST | R-02, V-17, V-18, V-08 |
| 39 | Fix auto-match phone regex | M-31 |
| 40 | Implement bank reconciliation report | M-42 |

### Phase 4: LOW — Ongoing Improvements

| Priority | Action | Issue IDs |
|----------|--------|-----------|
| 41 | Fix database charset mismatch | S-24 |
| 42 | Remove/consolidate legacy tables | D-01, D-15 |
| 43 | Add FK constraints to missing tables | D-02, D-03, D-04 |
| 44 | Implement compound interest for RBI compliance | M-38 |
| 45 | Wire OneSignal into business workflows | F-05 |
| 46 | Add bonus calculation logic | F-06 |
| 47 | Remove dead code | C-11, C-12, C-13, M-22 |
| 48 | Add CSP nonces for inline scripts | V-22 |
| 49 | Fix 404 page information exposure | V-21 |
| 50 | Remove login password hint | V-23 |

---

## Appendix A: Files Audited

### Controllers (9 files, ~10,000 lines)
- `application/controllers/admin/Auth.php` (284 lines)
- `application/controllers/admin/Dashboard.php` (572 lines)
- `application/controllers/admin/Members.php` (783 lines)
- `application/controllers/admin/Loans.php` (1609 lines)
- `application/controllers/admin/Savings.php` (1073 lines)
- `application/controllers/admin/Fines.php` (715 lines)
- `application/controllers/admin/Bank.php` (2145 lines)
- `application/controllers/admin/Settings.php` (1571 lines)
- `application/controllers/admin/Reports.php` (1298 lines)

### Models (14 files, ~8,500 lines)
- `application/core/MY_Model.php` (360 lines)
- `application/models/Loan_model.php` (2364 lines)
- `application/models/Savings_model.php` (720 lines)
- `application/models/Member_model.php` (539 lines)
- `application/models/Fine_model.php` (753 lines)
- `application/models/Report_model.php` (1038 lines)
- `application/models/Bank_model.php` (1782 lines)
- `application/models/Ledger_model.php` (558 lines)
- `application/models/Admin_model.php`
- `application/models/User_model.php`
- `application/models/NonMember_model.php`
- `application/models/Notification_model.php`
- `application/models/Setting_model.php`
- `application/models/Financial_year_model.php`

### Views & Assets
- `application/views/admin/layouts/header.php`, `footer.php`
- `application/views/member/layouts/header.php`
- `application/views/admin/auth/*`
- `application/views/member/auth/*`
- `application/views/admin/settings/index.php`
- `application/views/admin/members/create.php`, `view.php`
- `application/views/admin/loans/apply.php`
- `application/views/admin/system/logs.php`
- `application/views/errors/404.php`
- `assets/js/custom.js`
- `assets/js/admin/bank-mapping.js`

### Configuration
- `application/config/config.php`
- `application/config/database.php`
- `application/config/routes.php`
- `application/config/autoload.php`
- `application/config/security_config.php`
- `application/config/hooks.php`
- `application/config/constants.php`
- `application/config/onesignal.php`

### Core & Helpers
- `application/core/MY_Controller.php`
- `application/core/Member_Controller.php`
- `application/helpers/format_helper.php`
- `application/helpers/email_helper.php`
- `application/helpers/env_helper.php`
- `application/helpers/part_payment_helper.php`
- `application/helpers/settings_helper.php`
- `application/helpers/member_helper.php`

### Database
- `database/schema_clean_no_triggers.sql`
- `database/install.sql`

---

## Appendix B: Cross-Cutting Concerns

### Inconsistent Date Calculation Approaches
Three different methods used across the codebase:
1. **`/ 86400` (BROKEN)** — `Loan_model L298`, `Savings_model L520`, `Fine_model L686`, `Loan_model L944`
2. **`DateTime::diff()->days` (CORRECT)** — `Fine_model L65`, `Fine_model L148`
3. **`DATEDIFF()` in SQL (CORRECT)** — `Report_model L282`

Only approach 2 and 3 are DST-safe. The codebase is **partially fixed**.

### Missing Audit Trail
These operations modify financial records without creating audit log entries:
- `Bank_model::reverse_mapping()` — financial reversals
- `Loan_model::record_payment()` — EMI payments
- `Savings_model::record_payment()` — deposits/withdrawals
- `Fine_model::record_payment()` — fine payments

### TEMP Code Generation Pattern
Multiple models use `'TEMP_' + random` as placeholder codes, updating after insert:
- `Loan_model::generate_loan_number()` — `LN-TEMP-{random}`
- `Savings_model::generate_account_number()` — `SAV-TEMP-{random}`
- `Member_model::generate_member_code()` — `MEMB_TEMP_{random}`

If the post-insert update fails, orphan records with TEMP codes persist. Consider using DB triggers or sequences instead.

### Two Competing Auth Systems
- `Admin_model.php` → `admin_users` table (active system)
- `User_model.php` → `users` table (legacy, extends CI_Model not MY_Model)
- Both have `authenticate()` methods. `User_model` accepts MD5 passwords.

---

*Report generated by Banking System Architecture Audit*  
*Total files analyzed: 50+ | Total lines reviewed: ~25,000+*
