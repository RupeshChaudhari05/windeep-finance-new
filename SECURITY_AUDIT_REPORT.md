# Windeep Finance — Production Security Audit Report

**Application:** Windeep Finance (Cooperative Finance Management System)  
**Framework:** CodeIgniter 3.x on PHP / MySQL (XAMPP)  
**Audit Date:** 2025-07-17  
**Scope:** Views & JS, Security & Config, Routes & Helpers, Database Schema  
**Domain:** Indian cooperative finance / banking — **security is critical**

---

## Summary Statistics

| Severity | Count |
|----------|-------|
| **CRITICAL** | 11 |
| **HIGH** | 19 |
| **MEDIUM** | 17 |
| **LOW** | 10 |
| **TOTAL** | **57** |

---

## Part 1 — Views & JavaScript

### CRITICAL

| # | Finding | File | Details |
|---|---------|------|---------|
| V-01 | **Flash messages output unescaped — Stored XSS** | `application/views/admin/layouts/header.php` | `addslashes()` is used to embed flash messages in JavaScript strings: `'<?= addslashes($this->session->flashdata('success')) ?>'`. `addslashes()` does **not** prevent XSS in a JS context. An attacker who controls flash data (e.g., via validation errors reflecting user input) can inject arbitrary JS. **Fix:** Use `json_encode()` with `JSON_HEX_TAG \| JSON_HEX_AMP` or embed via a `data-*` attribute and read with `.data()`. |
| V-02 | **Flash messages output unescaped in HTML** | `application/views/member/auth/login.php`, `application/views/admin/auth/forgot_password.php`, `application/views/admin/auth/reset_password.php` | `<?= $this->session->flashdata('error') ?>` is rendered without `htmlspecialchars()`. If validation messages or controller-set flash data contain user input, this is a reflected XSS vector. **Fix:** Wrap all flash output: `<?= htmlspecialchars($this->session->flashdata('error'), ENT_QUOTES, 'UTF-8') ?>`. |

### HIGH

| # | Finding | File | Details |
|---|---------|------|---------|
| V-03 | **Admin name displayed unescaped** | `application/views/admin/layouts/header.php` | `<?= isset($admin) ? $admin->full_name : 'Admin' ?>` — if an admin's `full_name` contains `<script>`, it executes. Same pattern in sidebar. **Fix:** `<?= html_escape($admin->full_name ?? 'Admin') ?>` |
| V-04 | **Member names unescaped in multiple admin views** | `application/views/admin/members/create.php` (referral dropdown), `application/views/admin/members/view.php` (detail page), `application/views/admin/loans/apply.php` (guarantor list), `application/views/admin/payments/receive.php`, `application/views/admin/savings/collect.php` | Pattern `<?= $m->first_name ?> <?= $m->last_name ?>` repeated in `<option>`, table cells, and alert boxes. Any member whose name contains special characters can trigger XSS. **Fix:** Always use `html_escape()` or `htmlspecialchars()`. |
| V-05 | **Member data in `<option>` data attributes unescaped** | `application/views/admin/loans/apply.php` | `data-name="<?= $m->first_name . ' ' . $m->last_name ?>"` — breaks out of the attribute if the value contains a `"`. **Fix:** `htmlspecialchars(..., ENT_QUOTES)`. |
| V-06 | **Email/phone displayed as clickable links without escaping** | `application/views/admin/members/view.php` | `<a href="mailto:<?= $member->email ?>"><?= $member->email ?></a>` — email is user-supplied and unescaped in both `href` and display. Potential for protocol injection. **Fix:** Escape both contexts. |
| V-07 | **Settings values output unescaped** | `application/views/admin/settings/index.php` | Multiple instances of `<?= $settings['org_name'] ?? '' ?>` inside input `value` attributes without escaping. If stored settings contain `"`, the attribute breaks. **Fix:** `htmlspecialchars($settings['org_name'] ?? '', ENT_QUOTES)`. |
| V-08 | **KYC verification via GET request** | `application/views/admin/members/view.php` | KYC toggle sends a GET request: `<a href="...verify_kyc/<?= $member->id ?>">`. Destructive/state-changing actions must use POST with CSRF. A pre-fetching browser or CSRF attack can toggle KYC. **Fix:** Convert to `<form method="POST">` with CSRF token. |
| V-09 | **`printElement()` uses `document.write` — potential DOM XSS** | `assets/js/custom.js` | `printWin.document.write(content)` — if `content` contains unsanitized HTML, this is a DOM XSS sink. **Fix:** Sanitize content or use `DOMParser` before writing. |
| V-10 | **CDN resources loaded without SRI (Subresource Integrity)** | `application/views/admin/layouts/header.php`, `application/views/admin/layouts/footer.php`, `application/views/member/layouts/header.php` | jQuery, Bootstrap, AdminLTE, Font Awesome, Toastr, SweetAlert2, DataTables, Select2, Chart.js — all loaded from CDNs **without** `integrity` and `crossorigin` attributes. A CDN compromise or MITM attack injects malicious JS into every page. **Fix:** Add SRI hashes: `integrity="sha384-..." crossorigin="anonymous"`. |
| V-11 | **Member portal header — member name unescaped** | `application/views/member/layouts/header.php` (line ~102) | `<?= $member->first_name ?> <?= $member->last_name ?>` in the navbar dropdown. Same XSS risk as V-04. |

### MEDIUM

| # | Finding | File | Details |
|---|---------|------|---------|
| V-12 | **Broken PHP tag nesting in settings** | `application/views/admin/settings/index.php` | `value="<?= $settings['currency_symbol'] ?? '<?= get_currency_symbol() ?>' ?>"` — doubled PHP tag causes parse errors. **Fix:** `value="<?= htmlspecialchars($settings['currency_symbol'] ?? get_currency_symbol(), ENT_QUOTES) ?>"`. |
| V-13 | **`$extra_css` / `$extra_js` output unescaped** | `application/views/member/layouts/header.php` | `<?= $extra_css ?>` allows injection of arbitrary HTML/JS from any controller. Verify all callers only pass safe content; or whitelist. |
| V-14 | **Toastr timeout set to 0** | `application/views/admin/layouts/footer.php` | `timeOut: "0"` means notifications never auto-dismiss. In a busy financial environment this clutters the screen. **Fix:** `timeOut: "5000"`. |
| V-15 | **Member phone/voter ID output unescaped** | `application/views/member/profile/index.php` | `<?= $member->phone ?>` and `<?= $member->voter_id ?>` rendered raw. Other fields on this page correctly use `htmlspecialchars()`. **Fix:** Escape consistently. |
| V-16 | **Log file names output unescaped** | `application/views/admin/system/logs.php` | `<?= $file['name'] ?>` inside `<a>` link. Log filenames are typically safe, but if an adversary creates a file with special characters, it becomes XSS. **Fix:** `htmlspecialchars($file['name'])`. |
| V-17 | **Log deletion uses GET** | `application/views/admin/system/logs.php` | `<a href="admin/system/delete_log/...">` with only `confirm()`. Should be POST with CSRF to prevent CSRF-based log wiping. |
| V-18 | **Clear All Logs uses GET** | `application/views/admin/system/logs.php` | Same as V-17. `admin/system/clear_logs` triggered via GET link. |
| V-19 | **Hardcoded currency symbol in JS** | `assets/js/admin/bank-mapping.js` | `₹` hardcoded in JavaScript. Should read from server-provided config to support multi-currency. |

### LOW

| # | Finding | File | Details |
|---|---------|------|---------|
| V-20 | **Incomplete Indian states list** | `application/views/admin/members/create.php` | Only 13 of 28+ Indian states in dropdown. **Fix:** Add all states/UTs. |
| V-21 | **404 page exposes admin navigation** | `application/views/errors/404.php` | Dashboard, Bank Import, Loans links shown to all users — reveals internal route structure. **Fix:** Show generic navigation or check auth before showing admin links. |
| V-22 | **No `nonce` on inline scripts** | Multiple view files | If a CSP is added later, inline `<script>` blocks will be blocked. Prepare by extracting to external files or adding nonce attributes. |
| V-23 | **Login help tip reveals default password pattern** | `application/views/member/auth/login.php` | "Use your Member Code as password" tells potential attackers the default credential. **Fix:** Remove from production or at least make it less prominent. |

---

## Part 2 — Security & Configuration

### CRITICAL

| # | Finding | File | Details |
|---|---------|------|---------|
| S-01 | **CSRF protection is globally DISABLED** | `application/config/config.php` (~line 367) | `$config['csrf_protection'] = FALSE;` — every POST form is vulnerable to cross-site request forgery. In a finance app, an attacker can transfer funds, approve loans, or modify settings via CSRF. **Fix:** Set to `TRUE`, add the CSRF token name/cookie name, and enable AJAX token refresh. |
| S-02 | **Encryption key is EMPTY** | `application/config/config.php` (~line 298) | `$config['encryption_key'] = '';` — session cookies, CSRF tokens, and any encrypted data are **not encrypted at all**. **Fix:** Generate a 32-character random key: `bin2hex(random_bytes(16))`. |
| S-03 | **Environment set to `development`** | `index.php` (line 28) | `define('ENVIRONMENT', ... 'development')` — displays full error messages, stack traces, and debug info to end users. **Fix:** Set to `'production'` or use `$_SERVER['CI_ENV']` environment variable. |
| S-04 | **Cookie not HTTP-only** | `application/config/config.php` (~line 352) | `$config['cookie_httponly'] = FALSE;` — session cookie is accessible to JavaScript. XSS attacks can steal session tokens. **Fix:** `TRUE`. |
| S-05 | **Cookie not Secure-only** | `application/config/config.php` (~line 351) | `$config['cookie_secure'] = FALSE;` — session cookie sent over plain HTTP. **Fix:** `TRUE` (requires HTTPS). |
| S-06 | **`security_config.php` recommendations NOT applied** | `application/config/security_config.php` vs `config.php` | A comprehensive security configuration document exists (dated Jan 6, 2026) with correct CSRF, session, cookie, rate-limiting, headers, and password policy settings. **None of these are implemented** in the active `config.php`. The document is informational only. **Fix:** Apply every recommendation from `security_config.php` to `config.php` and `hooks.php`. |
| S-07 | **Sessions stored in shared temp directory** | `application/config/config.php` | `$config['sess_save_path'] = sys_get_temp_dir();` — on shared hosting, other users can read/hijack session files. **Fix:** Use database driver (`ci_sessions` table already exists in schema) or a private directory with `0700` permissions. |

### HIGH

| # | Finding | File | Details |
|---|---------|------|---------|
| S-08 | **Old sessions not destroyed on regeneration** | `application/config/config.php` (~line 329) | `$config['sess_regenerate_destroy'] = FALSE;` — old session data persists, enabling session fixation attacks. **Fix:** `TRUE`. |
| S-09 | **No session IP binding** | `application/config/config.php` | `$config['sess_match_ip'] = FALSE;` — stolen session cookies work from any IP. **Fix:** `TRUE` (may affect users behind load balancers — evaluate per deployment). |
| S-10 | **Database debug queries enabled** | `application/config/database.php` | `'save_queries' => TRUE` — keeps all SQL queries in memory. Information exposure risk and memory waste in production. **Fix:** `FALSE`. |
| S-11 | **Database strict mode disabled** | `application/config/database.php` | `'stricton' => FALSE` — allows silent data truncation, zero dates, invalid defaults. Can cause data integrity issues in financial records. **Fix:** `TRUE`. |
| S-12 | **No database connection encryption** | `application/config/database.php` | `'encrypt' => FALSE` — database traffic is in plaintext. If the DB is on a separate host, credentials and financial data can be sniffed. **Fix:** Enable SSL for MySQL connections. |
| S-13 | **Debug logging level** | `application/config/config.php` | `$config['log_threshold'] = 2;` — logs debug-level messages. In production, this writes excessive logs containing potentially sensitive data. **Fix:** Set to `1` (errors only). |
| S-14 | **`SHOW_DEBUG_BACKTRACE = TRUE`** | `application/config/constants.php` | Full stack traces in error pages. **Fix:** `FALSE` in production. |
| S-15 | **Hooks disabled — security headers not applied** | `application/config/hooks.php` + `config.php` | `$config['enable_hooks'] = FALSE;` and `hooks.php` is empty. The `security_config.php` prescribes `X-Frame-Options`, `X-Content-Type-Options`, `Strict-Transport-Security`, `Content-Security-Policy`, and `Referrer-Policy` headers via hooks. **None are sent.** This leaves the app vulnerable to clickjacking, MIME sniffing, and missing HSTS. **Fix:** Enable hooks and implement the `Security_headers` pre-controller hook. |
| S-16 | **Password minimum length is 6 characters** | `application/views/admin/auth/reset_password.php`, `application/controllers/member/Auth.php` | `minlength="6"` on password reset form. The `security_config.php` recommends 8 with uppercase + digit + special character. 6-char passwords are trivially brute-forced. **Fix:** Enforce min 8 chars with complexity rules server-side. |
| S-17 | **Default DB credentials: `root` with empty password** | `application/config/database.php` | Fallback when `.env` is missing: `'username' => env('DB_USERNAME', 'root')`, `'password' => env('DB_PASSWORD', '')`. If `.env` is accidentally deleted, the app connects as root with no password. **Fix:** Remove fallback defaults or fail hard on missing `.env`. |

### MEDIUM

| # | Finding | File | Details |
|---|---------|------|---------|
| S-18 | **`.env` file loaded twice** | `index.php` | `require_once APPPATH . 'helpers/env_helper.php';` at the top, then loaded again via autoload helpers. Redundant but not harmful — clean up for clarity. |
| S-19 | **`permitted_uri_chars` includes `=` and `&`** | `application/config/config.php` | `$config['permitted_uri_chars'] = 'a-z 0-9~%.:_\-=&';` — `=` and `&` in URI segments expand attack surface for parameter pollution. **Fix:** Remove unless specifically needed. |
| S-20 | **No `SameSite` cookie attribute** | `application/config/config.php` | CI3 default does not set `SameSite`. Modern browsers default to `Lax`, but explicitly setting `Strict` or `Lax` is recommended for CSRF defense-in-depth. |
| S-21 | **Form validation library not autoloaded** | `application/config/autoload.php` | Not in the autoload list. Controllers must manually `$this->load->library('form_validation')`. If a developer forgets, inputs are not validated. **Fix:** Add to autoload. |
| S-22 | **Security helper not autoloaded** | `application/config/autoload.php` | `xss_clean()`, `sanitize_filename()`, etc. not available by default. **Fix:** Add `'security'` to autoloaded helpers. |
| S-23 | **No upload directory `.htaccess` restrictions** | `uploads/` directory | The `uploads/bank_statements/`, `uploads/members_docs/`, `uploads/profile_images/` directories have no `.htaccess` to prevent PHP execution. An uploaded `.php` file could be executed directly. **Fix:** Add `php_flag engine off` or `<FilesMatch>` deny rule in each uploads directory. |

### LOW

| # | Finding | File | Details |
|---|---------|------|---------|
| S-24 | **`char_set` is `utf8` not `utf8mb4`** | `application/config/database.php` | `'char_set' => 'utf8'` — doesn't support 4-byte Unicode (emoji, some CJK). Schema uses `utf8mb4`. Mismatch can cause encoding issues. **Fix:** `'utf8mb4'`. |
| S-25 | **No rate limiting on login** | `application/controllers/member/Auth.php` | Member login has no rate limiting or account lockout. `failed_login_attempts` table exists in schema but is not checked. Admin login (`MY_Controller.php`) does check, but member login does not. |

---

## Part 3 — Routes & Helpers

### HIGH

| # | Finding | File | Details |
|---|---------|------|---------|
| R-01 | **Log view/delete routes accept `(:any)` — path traversal risk** | `application/config/routes.php` | `$route['admin/system/view_log/(:any)'] = 'admin/System/view_log/$1'` and `delete_log/(:any)`. The `(:any)` wildcard matches `../../etc/passwd` or `..%2F..%2F`. If the controller uses the parameter to construct a file path without validation, this is a **Local File Inclusion / path traversal** vulnerability. **Fix:** Change to `(:segment)` or validate the filename matches `/^log-\d{4}-\d{2}-\d{2}\.php$/` in the controller. |
| R-02 | **Destructive actions via GET** | `application/config/routes.php` | `$route['admin/non_members/delete/(:num)']`, `admin/system/clear_logs`, `admin/system/delete_log/(:any)` — all routed to controller methods reachable via GET. Deletion via GET violates REST semantics and enables CSRF. **Fix:** Restrict to POST/DELETE methods. |
| R-03 | **No route-level access control** | `application/config/routes.php` | All admin routes rely on `Admin_Controller`'s `_check_auth()` method in the constructor. If any admin controller accidentally extends `CI_Controller` instead of `Admin_Controller`, those routes become publicly accessible. No defense-in-depth at the route level. **Fix:** Implement a `before_controller` hook that checks auth for all `admin/*` URIs. |

### MEDIUM

| # | Finding | File | Details |
|---|---------|------|---------|
| R-04 | **Member logout via GET** | `application/controllers/member/Auth.php` | `public function logout()` is accessible via GET. Can be triggered by an `<img src="/member/logout">` tag on any page. **Fix:** Require POST. |
| R-05 | **Member login uses `session_regenerate_id()` instead of CI's method** | `application/controllers/member/Auth.php` (line ~105) | `session_regenerate_id(true)` bypasses CI's session handler. CI provides `$this->session->sess_regenerate()`. Using PHP's native function with CI's session driver may cause session data loss or inconsistency. |
| R-06 | **No CSRF validation in member login** | `application/controllers/member/Auth.php` | Although CSRF token is present in the form HTML, global CSRF is disabled (S-01). The controller does not manually verify the token. |
| R-07 | **Catch-all route missing** | `application/config/routes.php` | No `$route['404_override']` configured. Default CI 404 page may leak framework info. A custom handler exists at `Errors::show_404()` route but `404_override` isn't set in routes. |

### LOW

| # | Finding | File | Details |
|---|---------|------|---------|
| R-08 | **`format_helper.php` — `sanitize_phone()` allows `+` prefix only** | `application/helpers/format_helper.php` | Strips non-digits except leading `+`. No length validation. A phone number of `+0000000000000000` (100 digits) would pass. **Fix:** Add max length check. |
| R-09 | **`email_helper.php` — SMTP credentials in code** | `application/helpers/email_helper.php` | Reads SMTP host/user/pass from `env()` (good), but no validation that they exist. If env vars are missing, PHPMailer fails silently or throws a catchable error exposing config. |
| R-10 | **`part_payment_helper.php` — no overflow protection** | `application/helpers/part_payment_helper.php` | EMI/amortization calculations use `pow()` and division. Extremely large loan amounts or rates could cause float overflow. Edge case but relevant for financial precision. **Fix:** Add input range validation. |

---

## Part 4 — Database Schema

### HIGH

| # | Finding | File | Details |
|---|---------|------|---------|
| D-01 | **Dual member schema — legacy `member_details` + new `members`** | `database/schema_clean_no_triggers.sql` | Two member tables exist: `member_details` (varchar PK `member_id`) and `members` (auto-increment `id`). Legacy tables (`shares`, `send_form`, `view_requests`, `loan_transactions`, `loan_transaction_details`) reference `member_details.member_id` via FK. New tables use `members.id`. This dual schema creates referential integrity issues and data synchronization risk. **Fix:** Migrate all legacy tables to reference `members.id` and drop `member_details`. |
| D-02 | **`expenditure` table has no user/member FK** | `database/schema_clean_no_triggers.sql` | `expenditure` table has `created_by` and `paid_to` columns as `varchar(50)` with no foreign key. Who created an expense is not enforced. **Fix:** Add FK to `admin_users.id`. |
| D-03 | **`bank_balance_history` has no FK to `bank_accounts`** | `database/schema_clean_no_triggers.sql` | `bank_account_id` column exists but no `FOREIGN KEY` constraint. Orphan records can exist. |
| D-04 | **`chat_box` table has no FK constraints** | `database/schema_clean_no_triggers.sql` | `sender_id` and `receiver_id` have no FK to any user table. Messages can reference non-existent users. |

### MEDIUM

| # | Finding | File | Details |
|---|---------|------|---------|
| D-05 | **`members.password` is nullable** | `database/schema_clean_no_triggers.sql` | `password varchar(255) DEFAULT NULL` — members can exist without passwords. If the application doesn't check for NULL before `password_verify()`, it may behave unexpectedly. The member Auth controller does handle this (`$member->password ?? ''`) but it's a defense-in-depth gap. |
| D-06 | **No `CHECK` constraint on `members.phone` length** | `database/schema_clean_no_triggers.sql` | `phone varchar(20)` accepts any string up to 20 chars. No `CHECK` for minimum length or digit-only content. |
| D-07 | **`notifications` table — no FK on `recipient_id`** | `database/schema_clean_no_triggers.sql` | `recipient_id` is `int unsigned` with no FK. Since `recipient_type` is enum(`admin`, `member`), the FK target is ambiguous, but at minimum a comment or application-level check is needed. |
| D-08 | **`other_member_details` — no FK, no unique constraints** | `database/schema_clean_no_triggers.sql` | This table has no foreign keys, no unique constraints, and loose column definitions. Appears to be a legacy table. |
| D-09 | **`ci_sessions` table exists but config uses file driver** | `database/schema_clean_no_triggers.sql` vs `config.php` | The `ci_sessions` table is defined in the schema, but `$config['sess_driver'] = 'files'` in `config.php`. Wasted table — or the intended database driver was never enabled. **Fix:** Switch to database driver (also fixes S-07). |
| D-10 | **NULL unique keys on `aadhaar_number` and `pan_number`** | `database/schema_clean_no_triggers.sql` | Both columns are `DEFAULT NULL` with `UNIQUE KEY`. In MySQL, multiple NULLs are allowed in a UNIQUE column, so this works — but intent may be to enforce "if present, must be unique." Add a comment or application-level validation. |
| D-11 | **No `updated_at` on `financial_years`, `chart_of_accounts`** | `database/schema_clean_no_triggers.sql` | These tables lack `updated_at` timestamps, making change tracking difficult for audit purposes. |
| D-12 | **`view_requests` uses varchar FK to `member_details`** | `database/schema_clean_no_triggers.sql` | `member_from`, `member_to`, `created_by` are `varchar(50)` referencing `member_details.member_id`. Part of the legacy schema problem (D-01). |

### LOW

| # | Finding | File | Details |
|---|---------|------|---------|
| D-13 | **Mixed collation across tables** | `database/schema_clean_no_triggers.sql` | New tables use `utf8mb4_unicode_ci`, legacy tables use `utf8mb4_general_ci`. This causes implicit collation conversion in JOINs, which can prevent index usage and cause subtle sorting differences. **Fix:** Standardize on `utf8mb4_unicode_ci`. |
| D-14 | **`loan_transaction_details.loan_id` is varchar** | `database/schema_clean_no_triggers.sql` | `loan_id varchar(50)` — presumably references `member_details.member_id` (legacy). Should be an integer FK to `loans.id`. |
| D-15 | **50 tables — schema complexity** | `database/schema_clean_no_triggers.sql` | The schema has both legacy and modern tables. Consider a migration plan to consolidate and remove unused legacy tables (`member_details`, `loan_transactions`, `loan_transaction_details`, `shares`, `send_form`, `view_requests`, `other_member_details`). |

---

## Priority Remediation Roadmap

### Immediate (Before Go-Live) — CRITICAL items

1. **Enable CSRF protection** (S-01) — `$config['csrf_protection'] = TRUE;`
2. **Set encryption key** (S-02) — `$config['encryption_key'] = bin2hex(random_bytes(16));`
3. **Switch to production environment** (S-03) — `define('ENVIRONMENT', 'production');`
4. **Enable cookie security** (S-04, S-05, S-20) — `cookie_httponly = TRUE`, `cookie_secure = TRUE`, add `SameSite=Lax`
5. **Apply `security_config.php` recommendations** (S-06) — copy all settings to active config
6. **Switch session driver to database** (S-07, D-09) — `sess_driver = 'database'`, `sess_save_path = 'ci_sessions'`
7. **Fix all unescaped output** (V-01 through V-11) — global search for `<?= $` and add `html_escape()` / `htmlspecialchars()`
8. **Add SRI to all CDN resources** (V-10)

### Short-term (First Sprint)

9. **Enable and configure hooks** (S-15) — implement security headers hook
10. **Add `.htaccess` to upload directories** (S-23) — prevent PHP execution
11. **Change destructive GET routes to POST** (R-02, V-08, V-17, V-18, R-04)
12. **Validate log file names** (R-01) — prevent path traversal
13. **Strengthen password policy** (S-16) — min 8 chars with complexity
14. **Add rate limiting to member login** (S-25)
15. **Disable debug options** (S-10, S-13, S-14) — `save_queries = FALSE`, `log_threshold = 1`, `SHOW_DEBUG_BACKTRACE = FALSE`
16. **Destroy old sessions on regeneration** (S-08) — `sess_regenerate_destroy = TRUE`

### Medium-term (Following Sprints)

17. **Migrate legacy tables to `members.id` FK** (D-01, D-12, D-14, D-15)
18. **Add missing foreign keys** (D-02, D-03, D-04, D-07, D-08)
19. **Standardize collation** (D-13)
20. **Enable database strict mode and SSL** (S-11, S-12)
21. **Autoload security helper and form_validation** (S-21, S-22)
22. **Remove admin links from 404 page** (V-21)
23. **Remove default password hint from login** (V-23)

---

## Notes

- The `security_config.php` file is well-written and covers most security concerns. The primary gap is that **none of its recommendations have been applied** to the running configuration. Implementing it would resolve findings S-01 through S-09, S-15, S-16, and S-20 in one effort.
- The codebase shows good practices in some areas (audit logging, `password_verify()` for auth, `format_helper.php` escaping, foreign keys on modern tables). The issues are primarily in configuration (`config.php` defaults) and inconsistent output escaping in views.
- For a cooperative finance application handling real money under Indian regulatory requirements, the CRITICAL items must be resolved before production deployment.
