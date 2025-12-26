# WinDeep Finance — Architecture, Flows & Feature Reference 📚

**Purpose:** This document captures the implemented architecture, data flows, calculations, and features added to the system (bank import/mapping, fines, loan interest controls, guarantor/accounting settings, audit/tracking, etc.). It highlights important files, DB changes and the runtime flows so developers and reviewers can understand and extend the system.

---

## Table of Contents

- Overview
- High-level Architecture
- Database Schema Changes (migrations)
- Bank Import & Transaction Mapping — flow & files
- Transaction Processing & Accounting Touchpoints
- Fine System (Indian banking style) — logic & job
- Loan Interest Rate Handling & Loan Approval
- Guarantor & Accounting Settings
- Audit / Tracking / Security
- Key Endpoints & Example Payloads
- Recommended Cron / Jobs
- Notes & Next Steps

---

## Overview

- Platform: CodeIgniter 3 (MVC)
- Languages: PHP, MySQL, JS front-end (jQuery, DataTables), Bootstrap
- Purpose of changes: Add robust bank import + mapping UI, implement Indian-style fines (initial fixed fine + per-day), dynamic loan product interest settings, guarantor & accounting settings, and transaction tracking/auditing.

---

## High-level Architecture

- MVC layers:
  - Controllers (admin/*): entry points for admin UI and AJAX endpoints. Key controllers: `application/controllers/admin/Bank.php`, `application/controllers/admin/Settings.php`.
  - Models: data access & business logic: `application/models/Bank_model.php`, `application/models/Fine_model.php`, `application/models/Loan_model.php`, `application/models/Savings_model.php`.
  - Views: admin pages & mapping UIs: `application/views/admin/bank/transactions.php`, `application/views/admin/settings/loan_products.php`, `application/views/admin/settings/fine_rules.php`, `application/views/admin/settings/guarantor_settings.php`, `application/views/admin/settings/accounting_settings.php`.
  - DB Migrations: under `database/migrations/003_add_transaction_tracking_and_fine_enhancements.sql` and `application/migrations/003_add_transaction_tracking_and_fine_enhancements.sql`.

---

## Database Schema Changes (summary)

A migration was added to enable tracking and features. Important changes include:

- New / updated columns and tables:
  - `bank_transactions`: `paid_by_member_id`, `paid_for_member_id`, `transaction_category`, `mapping_remarks`, `mapped_by`, `mapped_at`, `related_type`, `related_id`, `credit_amount`, `debit_amount`, `running_balance`, `description2`, `updated_by`, `import_id`, `import_code`.
  - `bank_statement_imports`: `import_code`, `statement_date` (and import-code update logic).
  - `bank_imports` table created (if present) to track import files and status.
  - `fine_rules`: added `per_day_amount`, `grace_period_days`, `max_fine_amount`, `is_active`, `created_by`, `updated_by` and extended enum to include `fixed_plus_daily`.
  - `fines`: added `updated_by` and tracking columns.
  - `loan_products`: `min_interest_rate`, `max_interest_rate`, `default_interest_rate`, `late_fee_type`, `late_fee_value`, `late_fee_per_day`, `grace_period_days`, `product_code`, `is_active`, `created_by`, `updated_by`.
  - `loan_applications`: `assigned_interest_rate`, `approved_by`, `disbursed_by`.
  - `loan_payments` & `savings_payments`: fields for `bank_transaction_id`, `created_by`, `updated_by`.
  - `guarantor_settings` and `accounting_settings`: new tables for admin-managed settings.

- Default seed inserts included examples of Indian-style fines (e.g., EMI Late Standard: ₹100 + ₹10/day).

> See `database/migrations/003_add_transaction_tracking_and_fine_enhancements.sql` for exact SQL.

---

## Bank Import & Transaction Mapping — Flow & Files

High-level flow:
1. Admin uploads a bank statement (CSV/Excel) through the import UI (`admin/bank/import`). The file is saved and parsed into `bank_transactions` with an `import_id` linking back to `bank_statement_imports` / `bank_imports`.
2. Admin opens the mapping UI: `application/views/admin/bank/transactions.php`.
3. The UI lists transactions (`Bank_model::get_transactions_for_mapping()` and related methods), showing `Trans ID`, `Bank Name`, `Date`, `Descriptions`, `Debit/Credit/Balance`, and mapping status.
4. Admin searches members (AJAX endpoint `admin/bank/search_members`) and selects `Paid By` and optionally `Paid For` members.
5. Admin selects a `Transaction Type` (EMI, savings, fine, etc.) and a related account (savings account / loan) if applicable; clicks **Save Mapping**.
6. The controller `admin/Bank::save_transaction_mapping()` updates `bank_transactions` with mapping data (`paid_by_member_id`, `paid_for_member_id`, `transaction_category`, `mapped_by`, `mapped_at`, `related_type`, `related_id`, etc.) and then calls `process_mapped_transaction()`.
7. `process_mapped_transaction()` executes business actions depending on type:
   - `emi`: calls `Loan_model::record_payment(loan_id, amount, 'bank_transfer', transaction_id, admin_id)`
   - `savings`: calls `Savings_model::record_deposit(savings_id, amount, 'bank_transfer', transaction_id, admin_id)`
   - `fine`: finds an outstanding fine for the member and calls `Fine_model::record_payment(fine_id, amount, 'bank_transfer', transaction_id, admin_id)`

Files to inspect for code:
- Controller: `application/controllers/admin/Bank.php`
  - `save_transaction_mapping()` — writes mapping and calls `process_mapped_transaction()`.
  - `process_mapped_transaction()` — dispatch logic for types.
  - AJAX helpers: `ajax_response()`.
- Model: `application/models/Bank_model.php`
  - `get_imports()`, `get_import_transactions()`, `get_pending_transactions()`, `generate_import_code()` etc.
- View/UI: `application/views/admin/bank/transactions.php` (member search, mapping UI, JS flows)

UI behavior & UX details:
- Real-time member search with `search_members()` (AJAX)
- Related accounts loaded via `get_member_accounts()` for selecting savings/loan targets
- Row highlighting for mapped/mismatch/unmapped
- Audit badges showing `Updated By` name and mapping status

---

## Transaction Processing & Accounting Touchpoints

- When mapped, transactions are programmatically converted into domain records (loan payment, savings deposit, fine payment).
- The payment functions record the `bank_transaction_id` on the corresponding payment records (loan/savings payment tables). This creates a direct audit link between ledger entries and bank transactions.
- Accounting settings exist in `accounting_settings` table to map default GL accounts (cash, bank, loan receivable, interest income, fine income, processing fee, etc.). These are available to use when creating journal entries but the current integration surfaces the settings for later accounting automation.

---

## Fine System (Indian Banking Style) — Logic & Code

Location: `application/models/Fine_model.php`

Key functions:
- `calculate_fine_amount($rule, $days_late, $due_amount = 0)`
  - Grace days: `effective_days = max(0, days_late - grace_period)`. If `effective_days <= 0` return 0.
  - Calculation types supported:
    - `fixed`: one-time fixed fee (e.g., ₹100)
    - `percentage`: percentage of `due_amount` (rate from rule)
    - `per_day`: `per_day_amount * effective_days`
    - `fixed_plus_daily` (Indian banking style): `initial_fixed + (per_day_amount * effective_days)`
  - Maximum cap applied when `max_fine` is set.
  - Round to two decimals.

- `run_late_fine_job($created_by = 1)` — job wrapper used for cron to:
  1. `update_daily_fines()` to recompute fines that grow daily (per_day/fixed_plus_daily). It iterates pending fines, recalculates and updates only when the fine amount grows.
  2. Find overdue loan installments and savings schedules and create new fines for them using `apply_loan_late_fine()` and `apply_savings_late_fine()`.

Default rules (seeded from migration):
- Example: `EMI Late Payment - Standard` with `fine_type = fixed_plus_daily`, `fine_amount = 100`, `per_day_amount = 10`, `grace_period_days = 0`, `max_fine_amount = 500`.

Notes on integration:
- Once a fine is recorded, payment against a bank transaction is handled via `Fine_model::record_payment()` which updates `paid_amount`, `balance_amount`, and `status`.
- Cron job should run once daily (recommendation below).

---

## Loan Interest Rate Handling & Loan Approval

- Loan product configuration (admin view at `application/views/admin/settings/loan_products.php`) now supports:
  - `min_interest_rate`, `max_interest_rate`, `default_interest_rate` per product
  - Late fee settings (`late_fee_type`, `late_fee_value`, `late_fee_per_day`, `grace_period_days`)
  - `is_active` toggle and audit fields `created_by`, `updated_by`.

- Runtime flow:
  - Admin can set the default/min/max for each product.
  - During loan approval, business logic should read `min_interest_rate` and `max_interest_rate` and allow the approver to assign `assigned_interest_rate` (stored on `loan_applications.assigned_interest_rate`).
  - The database column exists (`assigned_interest_rate`) — ensure the loan approval controller populates it on approval.

Files to look at:
- `application/views/admin/settings/loan_products.php` (product editor + UI)
- `application/controllers/admin/Settings.php::save_loan_product()` (saves the fields to `loan_products` table)

---

## Guarantor & Accounting Settings

- `guarantor_settings` table introduced to store keys such as `min_guarantors`, `max_guarantors`, `guarantor_coverage_percent`, `auto_debit_from_guarantor`, etc.
- `accounting_settings` table introduced to store GL mapping and voucher prefixes (`default_cash_account`, `default_bank_account`, `interest_income_account`, `fine_income_account`, `voucher_prefix_*`, `financial_year_start_month`, etc.)
- Admin views exist under `application/views/admin/settings/` to manage these.

---

## Audit / Tracking

- Many tables have tracking columns added:
  - `created_by`, `updated_by` (loans, products, fines, payments)
  - `mapped_by`, `mapped_at` and `updated_by` on `bank_transactions`
  - `bank_transaction_id` on `loan_payments` / `savings_payments` for traceability
- These provide the data needed to show who performed mapping/approval/disbursement and for creating audit trails.
- There is an `audit_logs()` view and `Audit_model` which stores change events (`log_audit()` and `log_activity()` called by controllers).

---

## Key Endpoints & Example Payloads

- `POST admin/bank/search_members` — data: `{search: 'text'}` → result: members array
- `POST admin/bank/get_member_accounts` — data: `{member_id: id}` → result: savings accounts + loans
- `POST admin/bank/save_transaction_mapping` — data: `{transaction_id, paying_member_id, paid_for_member_id, transaction_type, related_account, remarks}` → maps transaction and triggers processing
- `POST admin/settings/save_loan_product` — fields: product settings (`min_interest_rate`, `max_interest_rate`, `default_interest_rate`, `late_fee_*`, etc.)

All AJAX responses follow JSON `{success: bool, message: string, ...}` via `ajax_response()` in controllers.

---

## Cron / Background Jobs

- Fine maintenance job: call `Fine_model::run_late_fine_job($created_by)` daily.
  - Recommended crontab entry (Linux): `0 2 * * * php /path/to/index.php cron run_fines >/dev/null 2>&1` (replace with your cron runner / controller)
- Bank import parsing and heavy tasks can be queued or executed via background workers if files are large.

---

## Developer Notes & Next Steps

- Verify loan approval flow sets `loan_applications.assigned_interest_rate`. If not implemented, update the approval controller to accept and persist the admin-assigned rate and ensure `loans` use that rate for schedule calculation.
- Improve accounting automation: wire `loan_payments` and `savings_payments` to generate journal/voucher entries using `accounting_settings` automatically.
- Add automated tests to:
  - Validate `calculate_fine_amount()` logic with varied inputs (grace period, per-day growth, and cap)
  - Simulate a bank import file and exercise the mapping flow end-to-end (import -> map -> payment recorded)
- Add an optional notification to guarantors when the borrower misses payments (respecting `guarantor_settings`).

---

## Where to look in the codebase (quick map)

- Controllers: `application/controllers/admin/Bank.php`, `application/controllers/admin/Settings.php`
- Models: `application/models/Bank_model.php`, `application/models/Fine_model.php`, `application/models/Loan_model.php`, `application/models/Savings_model.php`
- Views: `application/views/admin/bank/transactions.php`, `application/views/admin/settings/loan_products.php`, `application/views/admin/settings/fine_rules.php`, `application/views/admin/settings/guarantor_settings.php`, `application/views/admin/settings/accounting_settings.php`
- Migrations: `database/migrations/003_add_transaction_tracking_and_fine_enhancements.sql`

---

## Appendix: Fine Calculation Examples

- Example A: 1-day late, grace = 0, rule = `fixed_plus_daily` (initial=100, per_day=10)
  - effective_days = 1 → fine = 100 + (1 * 10) = ₹110

- Example B: 7 days late, grace = 5, rule = `fixed_plus_daily` (initial=100, per_day=10)
  - effective_days = 7 - 5 = 2 → fine = 100 + (2 * 10) = ₹120

- Example C: `per_day` rule with `per_day_amount = 5`, 10 effective days → fine = 5 * 10 = ₹50

---

If you want, I can:
- add a small integration test demonstrating import → mapping → payment recording, or
- add diagrams (sequence or ERD images) to the docs folder.

---

_Last updated: 2025-12-26_
