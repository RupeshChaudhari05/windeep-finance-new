# Cron Jobs Setup Guide

## Overview

Windeep Finance requires 4 cron jobs to run scheduled tasks automatically:
1. **Hourly** - Queue processing, cache cleanup
2. **Daily** - Loan calculations, reminders, overdue checks
3. **Weekly** - Reports, integrity checks  
4. **Monthly** - Interest posting, statements, cleanup

---

## Step-by-Step Setup

### Step 1: Open Crontab Editor

**Linux/Ubuntu:**
```bash
crontab -e
```

**Windows (Task Scheduler):**
- Use Windows Task Scheduler to run the PHP commands below

---

### Step 2: Add These 4 Cron Entries

Copy and paste ALL 4 lines into your crontab:

```cron
# Windeep Finance - Hourly Tasks (every hour at minute 0)
0 * * * * php /path/to/windeep_finance/index.php cli/cron/hourly >> /path/to/windeep_finance/application/logs/cron_hourly.log 2>&1

# Windeep Finance - Daily Tasks (every day at 2:00 AM)
0 2 * * * php /path/to/windeep_finance/index.php cli/cron/daily >> /path/to/windeep_finance/application/logs/cron_daily.log 2>&1

# Windeep Finance - Weekly Tasks (every Sunday at 3:00 AM)
0 3 * * 0 php /path/to/windeep_finance/index.php cli/cron/weekly >> /path/to/windeep_finance/application/logs/cron_weekly.log 2>&1

# Windeep Finance - Monthly Tasks (1st of every month at 4:00 AM)
0 4 1 * * php /path/to/windeep_finance/index.php cli/cron/monthly >> /path/to/windeep_finance/application/logs/cron_monthly.log 2>&1
```

---

### Step 3: Replace Paths

Replace `/path/to/windeep_finance/` with your actual installation path.

**Example for XAMPP on Windows:**
```cron
0 * * * * C:\xampp_new\php\php.exe C:\xampp_new\htdocs\windeep_finance\index.php cli/cron/hourly
0 2 * * * C:\xampp_new\php\php.exe C:\xampp_new\htdocs\windeep_finance\index.php cli/cron/daily
0 3 * * 0 C:\xampp_new\php\php.exe C:\xampp_new\htdocs\windeep_finance\index.php cli/cron/weekly
0 4 1 * * C:\xampp_new\php\php.exe C:\xampp_new\htdocs\windeep_finance\index.php cli/cron/monthly
```

**Example for Linux (typical hosting):**
```cron
0 * * * * /usr/bin/php /var/www/html/windeep_finance/index.php cli/cron/hourly
0 2 * * * /usr/bin/php /var/www/html/windeep_finance/index.php cli/cron/daily
0 3 * * 0 /usr/bin/php /var/www/html/windeep_finance/index.php cli/cron/weekly
0 4 1 * * /usr/bin/php /var/www/html/windeep_finance/index.php cli/cron/monthly
```

---

### Step 4: Save and Exit

**Linux:**
- Press `Ctrl+X`, then `Y`, then `Enter`

**Windows Task Scheduler:**
- Create 4 separate tasks with the schedules above

---

## What Each Cron Job Does

### 1. HOURLY (`cli/cron/hourly`)
| Task | Description |
|------|-------------|
| Process Email Queue | Sends pending notification emails |
| Process SMS Queue | Sends pending SMS messages |
| Process WhatsApp Queue | Sends pending WhatsApp messages |
| Clean Cache | Removes old cached data |
| Check Active Loans | Monitors active loan status |

### 2. DAILY (`cli/cron/daily`) - Runs at 2:00 AM
| Task | Description |
|------|-------------|
| **Send Due Date Emails** | Sends email alerts to members with payments due TODAY (if email present) |
| Process Overdue EMIs | Marks EMIs as overdue after due date |
| **Apply Late Fees (Auto)** | Automatically adds penalty charges based on Fine Rules |
| Send Payment Reminders | Sends 3-day advance reminders for upcoming EMIs |
| Update Loan Status | Updates loan completion status |
| Update NPA Status | Marks loans as NPA after configured days |
| Daily Backup | Creates database backup |
| Archive Logs | Moves old log entries to archive |

**Important:** This job implements the Fixed Due Day feature - members receive email on due date and fines are auto-applied.

### 3. WEEKLY (`cli/cron/weekly`) - Runs Sunday 3:00 AM
| Task | Description |
|------|-------------|
| Generate Weekly Report | Creates summary for admins |
| Data Integrity Check | Validates loan vs EMI amounts |
| Member Activity Report | Identifies inactive members |
| Performance Optimization | Optimizes database tables |

### 4. MONTHLY (`cli/cron/monthly`) - Runs 1st of month 4:00 AM
| Task | Description |
|------|-------------|
| Post RD Interest | Credits monthly interest to RD accounts |
| Generate FD Interest | Processes FD maturity interest |
| Generate Statements | Creates monthly member statements |
| Process Loan Aging | Updates loan aging reports |
| Monthly Backup | Full system backup |
| Cleanup Old Data | Archives data older than 2 years |
| Recalculate Balances | Ensures all balances are accurate |

---

## Testing Cron Jobs

### Test Manually First

Before setting up automatic crons, test each job manually:

```bash
# Test Hourly
php index.php cli/cron/hourly

# Test Daily  
php index.php cli/cron/daily

# Test Weekly
php index.php cli/cron/weekly

# Test Monthly (be careful - this processes real data)
php index.php cli/cron/monthly
```

### Check Log Files

After running, check logs at:
- `application/logs/cron_hourly.log`
- `application/logs/cron_daily.log`
- `application/logs/cron_weekly.log`
- `application/logs/cron_monthly.log`

### Monitor in Admin Panel

Go to: **Admin > System > Cron Status**

---

## Windows Task Scheduler Setup

If you're on Windows without Linux cron:

### Create a Task for Each Job

1. Open **Task Scheduler**
2. Click **Create Basic Task**
3. Name it: "Windeep Finance - Hourly"
4. Trigger: Daily, repeat every 1 hour
5. Action: Start a program
6. Program: `C:\xampp_new\php\php.exe`
7. Arguments: `C:\xampp_new\htdocs\windeep_finance\index.php cli/cron/hourly`

Repeat for daily (2:00 AM), weekly (Sunday 3:00 AM), monthly (1st, 4:00 AM).

---

## Shared Hosting (cPanel)

1. Login to cPanel
2. Go to **Cron Jobs**
3. Add each command:

| Schedule | Command |
|----------|---------|
| Every hour (0 * * * *) | `php /home/yourusername/public_html/windeep_finance/index.php cli/cron/hourly` |
| Daily at 2am (0 2 * * *) | `php /home/yourusername/public_html/windeep_finance/index.php cli/cron/daily` |
| Sunday 3am (0 3 * * 0) | `php /home/yourusername/public_html/windeep_finance/index.php cli/cron/weekly` |
| 1st of month (0 4 1 * *) | `php /home/yourusername/public_html/windeep_finance/index.php cli/cron/monthly` |

---

## Troubleshooting

### Cron Not Running?

1. **Check PHP path:**
   ```bash
   which php
   ```

2. **Check file permissions:**
   ```bash
   chmod +x index.php
   ```

3. **Check cron log:**
   ```bash
   grep CRON /var/log/syslog
   ```

### Permission Denied?

```bash
sudo chown -R www-data:www-data /var/www/html/windeep_finance
chmod -R 755 /var/www/html/windeep_finance
```

### Check Cron is Running

```bash
sudo service cron status
```

---

## Summary

| # | Cron Job | Schedule | Time |
|---|----------|----------|------|
| 1 | Hourly | Every hour | :00 |
| 2 | Daily | Every day | 2:00 AM |
| 3 | Weekly | Every Sunday | 3:00 AM |
| 4 | Monthly | 1st of month | 4:00 AM |

**Total cron jobs needed: 4**
