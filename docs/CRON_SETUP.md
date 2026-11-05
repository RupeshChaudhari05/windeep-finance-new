# Windeep Finance — Cron Jobs & Scheduled Tasks

This document covers **all automated scheduled tasks**, how to activate them, and how the **fine calculation** system works.

---

## Table of Contents

1. [Overview](#overview)
2. [Cron Schedule Summary](#cron-schedule-summary)
3. [Setting Up Cron Jobs](#setting-up-cron-jobs)
   - [Linux / VPS (crontab)](#linux--vps-crontab)
   - [cPanel Shared Hosting](#cpanel-shared-hosting)
   - [Manual Trigger via Web (Shared Hosting)](#manual-trigger-via-web-shared-hosting)
4. [Job Descriptions](#job-descriptions)
   - [Hourly Jobs](#hourly-jobs)
   - [Daily Jobs](#daily-jobs)
   - [Weekly Jobs](#weekly-jobs)
   - [Monthly Jobs](#monthly-jobs)
5. [Fine Calculation System](#fine-calculation-system)
   - [How Fines Work](#how-fines-work)
   - [Fine Rules Configuration](#fine-rules-configuration)
   - [Fine Types](#fine-types)
   - [Enabling Auto-Apply Fines](#enabling-auto-apply-fines)
6. [Web-Based Cron Trigger (API)](#web-based-cron-trigger-api)
7. [Monitoring & Logs](#monitoring--logs)
8. [Troubleshooting](#troubleshooting)

---

## Overview

Windeep Finance uses a CLI-based cron controller located at:

```
application/controllers/cli/Cron.php
```

It provides four entry points that should be scheduled at the appropriate intervals:

| Entry Point | Schedule | Purpose |
|-------------|----------|---------|
| `daily`     | Every day at 2:00 AM | Fines, overdue marking, reminders, NPA, session cleanup |
| `weekly`    | Every Sunday at 3:00 AM | Savings schedule extension, weekly report, notification cleanup |
| `monthly`   | 1st of each month at 4:00 AM | Interest calculation, monthly reports, log archival, DB backup |
| `hourly`    | Every hour | Email queue processing, guarantor consent reminders |

---

## Cron Schedule Summary

```
# ┌───────────── minute (0–59)
# │ ┌─────────── hour (0–23)
# │ │ ┌───────── day of month (1–31)
# │ │ │ ┌─────── month (1–12)
# │ │ │ │ ┌───── day of week (0–7, 0 or 7 = Sunday)
# │ │ │ │ │
# * * * * *  command

# Hourly
0 * * * *    cd /path/to/windeep_finance && php index.php cli/cron hourly

# Daily (2 AM)
0 2 * * *    cd /path/to/windeep_finance && php index.php cli/cron daily

# Weekly (Sunday 3 AM)
0 3 * * 0    cd /path/to/windeep_finance && php index.php cli/cron weekly

# Monthly (1st of month, 4 AM)
0 4 1 * *    cd /path/to/windeep_finance && php index.php cli/cron monthly
```

---

## Setting Up Cron Jobs

### Linux / VPS (crontab)

1. SSH into your server.
2. Open crontab editor:
   ```bash
   crontab -e
   ```
3. Add the four lines (replace `/path/to/windeep_finance` with your actual path):
   ```bash
   0 * * * *  cd /var/www/html/windeep_finance && /usr/bin/php index.php cli/cron hourly >> /var/log/windeep_cron.log 2>&1
   0 2 * * *  cd /var/www/html/windeep_finance && /usr/bin/php index.php cli/cron daily >> /var/log/windeep_cron.log 2>&1
   0 3 * * 0  cd /var/www/html/windeep_finance && /usr/bin/php index.php cli/cron weekly >> /var/log/windeep_cron.log 2>&1
   0 4 1 * *  cd /var/www/html/windeep_finance && /usr/bin/php index.php cli/cron monthly >> /var/log/windeep_cron.log 2>&1
   ```
4. Save and exit. Verify with:
   ```bash
   crontab -l
   ```

> **Tip:** Find PHP path with `which php` (usually `/usr/bin/php` or `/usr/local/bin/php`).

### cPanel Shared Hosting

1. Login to **cPanel** → **Cron Jobs**.
2. Add four cron jobs:

| Setting | Hourly | Daily | Weekly | Monthly |
|---------|--------|-------|--------|---------|
| Minute  | 0      | 0     | 0      | 0       |
| Hour    | *      | 2     | 3      | 4       |
| Day     | *      | *     | *      | 1       |
| Month   | *      | *     | *      | *       |
| Weekday | *      | *     | 0      | *       |

3. Command for each (adjust path):
   ```
   cd /home/username/public_html/windeep_finance && /usr/local/bin/php index.php cli/cron daily
   ```

> **Note:** On some shared hosts, use `/usr/local/bin/ea-php81` or ask your host for the PHP binary path.

### Manual Trigger via Web (Shared Hosting)

If your hosting does not support CLI cron jobs, use the **web-based cron trigger** built into the admin panel:

1. Go to **Admin → Settings → Maintenance** tab.
2. Click the **Run Cron Jobs** buttons to trigger daily/weekly/monthly/hourly jobs manually.
3. Alternatively, use the API URL with a secret key (see [Web-Based Cron Trigger](#web-based-cron-trigger-api) below).

You can also use free **webcron services** (e.g., cron-job.org, EasyCron) to hit the trigger URL periodically.

---

## Job Descriptions

### Hourly Jobs

| Job | Description |
|-----|-------------|
| **Process Email Queue** | Sends up to 50 pending emails from the `email_queue` table. Retries failed emails up to 3 times. |
| **Check Pending Consents** | Sends reminder emails to guarantors who haven't consented within 3 days (up to 3 reminders). |

### Daily Jobs

| Job | Description |
|-----|-------------|
| **Apply Overdue Fines** | Calculates and applies late fines to overdue loan/savings installments. Only runs if `auto_apply_fines` is **ON** in Settings. Uses `Fine_model::run_late_fine_job()`. |
| **Mark Overdue Installments** | Changes status from `pending` to `overdue` for loan installments and savings schedules past their due date. |
| **Send Due Reminders** | Sends email and in-app notifications for installments due today and 3 days ahead (both loans and savings). |
| **Update NPA Status** | Marks loans as NPA (Non-Performing Asset) when any installment is overdue beyond the configured `npa_days` threshold (default: 90 days). |
| **Cleanup Old Sessions** | Deletes `ci_sessions` records older than 7 days. |

### Weekly Jobs

| Job | Description |
|-----|-------------|
| **Extend Savings Schedules** | Auto-generates next 12 months of savings schedule entries for active accounts whose schedule ends within 3 months. |
| **Send Weekly Report** | Emails a weekly summary (new members, new loans, collections, savings deposits) to the admin email. |
| **Cleanup Old Notifications** | Deletes read notifications older than 30 days. |

### Monthly Jobs

| Job | Description |
|-----|-------------|
| **Calculate Savings Interest** | Calculates and posts monthly interest credit for active savings accounts. Interest = `(balance × annual_rate / 12 / 100)`. Posts to ledger with double-entry bookkeeping. |
| **Generate Monthly Reports** | Creates a summary report (collections + disbursements) for the previous month and stores it in `monthly_reports`. |
| **Archive Old Logs** | Moves application log files older than 30 days to `logs/archive/`. Deletes archives older than 90 days. |
| **Database Backup** | Creates a ZIP backup of the database and stores it in `application/backups/`. Keeps last 30 scheduled backups. |

---

## Fine Calculation System

### How Fines Work

1. The **daily cron** calls `apply_overdue_fines()`.
2. This method checks the `auto_apply_fines` setting — if **OFF**, it skips entirely.
3. If enabled, it delegates to `Fine_model::run_late_fine_job()` which:
   - Fetches all **active fine rules** from the `fine_rules` table.
   - Finds overdue installments (loan and/or savings based on rule's `applies_to` field).
   - Calculates the fine amount based on the rule type.
   - Creates/updates records in the `fines` table.
   - For `per_day` type fines, calls `update_daily_fines()` to recalculate based on current days overdue.

### Fine Rules Configuration

Fine rules are managed at **Admin → Settings → Fine Rules** tab.

Each rule has:

| Field | Description |
|-------|-------------|
| `rule_name` | Descriptive name (e.g., "Loan Late Fee") |
| `applies_to` | `loan`, `savings`, or `both` |
| `fine_type` | Calculation method (see below) |
| `fine_value` | Fixed amount or percentage value |
| `per_day_amount` | Additional daily charge (for per_day types) |
| `grace_period_days` | Days after due date before fine applies |
| `max_fine_amount` | Maximum cap on the total fine |
| `is_active` | Toggle rule on/off |
| `effective_from` | Date from which the rule takes effect |

### Fine Types

| Type | Formula | Example |
|------|---------|---------|
| **fixed** | `fine = fine_value` | Fine = ₹500 flat |
| **percentage** | `fine = (overdue_amount × fine_value / 100)` | Fine = 2% of ₹10,000 = ₹200 |
| **per_day** | `fine = per_day_amount × days_overdue` | ₹50/day × 10 days = ₹500 |
| **fixed_plus_daily** | `fine = fine_value + (per_day_amount × days_overdue)` | ₹200 + (₹25 × 10) = ₹450 |
| **slab** | Slab-based calculation (configurable) | Different rates for different ranges |

### Enabling Auto-Apply Fines

1. Go to **Admin → Settings → General Settings**.
2. Toggle **"Auto-apply late payment fines"** to **ON**.
3. Make sure at least one fine rule is **Active** in the Fine Rules tab.
4. Ensure the daily cron is running (or trigger it manually).

> **Important:** If `auto_apply_fines` is OFF, no fines will be applied automatically even if cron runs. You can still apply fines manually from the Fines module.

---

## Web-Based Cron Trigger (API)

For shared hosting or manual execution, use the admin panel:

### Admin Panel (GUI)
- Navigate to **Admin → Settings → Maintenance** tab.
- Click buttons to run individual cron jobs or all at once.
- Results display in real-time showing success/failure for each task.

### API URL (for Webcron Services)
```
GET/POST: https://yourdomain.com/admin/settings/run_cron?job=all&key=YOUR_CRON_SECRET_KEY
```

**Parameters:**
| Param | Values | Description |
|-------|--------|-------------|
| `job` | `daily`, `weekly`, `monthly`, `hourly`, `all` | Which job group to run |
| `key` | Your secret key | Must match the `cron_secret_key` in Settings |

**Setting up the secret key:**
1. Go to **Admin → Settings → Maintenance** tab.
2. Set the **Cron Secret Key** field.
3. Use this key in webcron services to authenticate requests.

---

## Monitoring & Logs

### Cron Log Files
Cron logs are written to:
```
application/logs/cron_YYYY-MM-DD.log
```

Each log entry contains timestamp, log level, and message:
```
[2026-01-15 02:00:01] [INFO] ========== DAILY CRON START ==========
[2026-01-15 02:00:01] [INFO] Starting: Apply overdue fines
[2026-01-15 02:00:02] [INFO] Applied/updated 5 fines
[2026-01-15 02:00:02] [INFO] Starting: Mark overdue installments
[2026-01-15 02:00:03] [INFO] Marked overdue: 3 loan installments, 1 savings schedules
...
```

### Test Cron Setup
Run the test command to verify everything is working:
```bash
cd /path/to/windeep_finance && php index.php cli/cron test
```
This checks: database connection, email configuration, and log file write permissions.

### Check Cron Status
```bash
cd /path/to/windeep_finance && php index.php cli/cron status
```
Shows: last run times, pending tasks count (overdue installments, pending emails, pending consents).

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Fines not being applied | Check `auto_apply_fines` is ON in Settings. Check at least one fine rule is active. |
| Cron not running | Verify cron schedule with `crontab -l`. Check PHP path is correct. |
| "CLI access only" error | The CLI controller blocks web access. Use the Admin → Maintenance trigger instead. |
| Emails not sending | Check email configuration in Settings → Email. Run hourly cron to process queue. |
| Interest not posting | Ensure savings schemes have `interest_rate > 0`. Monthly cron only posts once per month per account. |
| NPA not updating | Check `npa_days` setting (default: 90). Daily cron checks installments overdue beyond this threshold. |
| Permission denied on logs | Ensure `application/logs/` directory is writable by the web server user (`chmod 755`). |
| Backup failing | Ensure `application/backups/` directory exists and is writable. |

---

*Last updated: <?php echo date('Y-m-d'); ?>*
