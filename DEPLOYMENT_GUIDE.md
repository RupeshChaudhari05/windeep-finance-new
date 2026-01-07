# PRODUCTION DEPLOYMENT GUIDE
**Windeep Finance - Loan Management System**  
**Date:** January 6, 2026  
**Version:** 2.0 (Bug Fixes Applied)

---

## OVERVIEW

This guide provides step-by-step instructions to deploy the fixed Windeep Finance system to production safely.

**Total Deployment Time:** 4-6 hours  
**Recommended Window:** Off-peak hours (weekend/night)  
**Required Access:** Server admin, database admin, application admin

---

## PRE-DEPLOYMENT CHECKLIST

### 1. **Backup Everything** ✅

```bash
# Database backup
mysqldump -u root -p windeep_finance > backup_$(date +%Y%m%d_%H%M%S).sql

# Application backup
cd /var/www
tar -czf windeep_backup_$(date +%Y%m%d_%H%M%S).tar.gz windeep_finance/

# Upload to remote storage (AWS S3, Google Drive, etc.)
```

### 2. **Environment Verification** ✅

```bash
# Check PHP version (8.2 required)
php -v

# Check MySQL version (8.0+ required)
mysql --version

# Check required PHP extensions
php -m | grep -E 'mysqli|pdo_mysql|mbstring|openssl|curl|json|xml'

# Check disk space (need at least 2GB free)
df -h

# Check memory
free -h
```

### 3. **Code Review** ✅

Review all files changed:
- `application/models/Loan_model.php` ✅
- `application/models/Fine_model.php` ✅
- `application/models/Bank_model.php` ✅
- `application/models/Ledger_model.php` ✅
- `application/models/User_model.php` ✅
- `database/migrations/*.sql` ✅

---

## PHASE 1: DATABASE MIGRATIONS (30-45 minutes)

### Step 1.1: Test Migrations on Staging ⚠️

```bash
# Create staging database
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS windeep_finance_staging;"

# Import production data
mysql -u root -p windeep_finance_staging < backup_production.sql

# Test migrations
mysql -u root -p windeep_finance_staging < database/migrations/007_add_utr_unique_constraint.sql
mysql -u root -p windeep_finance_staging < database/migrations/008_add_outstanding_balance_triggers.sql
mysql -u root -p windeep_finance_staging < database/migrations/009_add_database_constraints.sql
mysql -u root -p windeep_finance_staging < database/migrations/010_create_security_tables.sql
```

**Expected Output:** All migrations should complete without errors.

### Step 1.2: Apply Migrations to Production 🔴

```bash
# Connect to production database
mysql -u root -p windeep_finance

# Run migrations one by one (in order)
SOURCE database/migrations/007_add_utr_unique_constraint.sql;
SOURCE database/migrations/008_add_outstanding_balance_triggers.sql;
SOURCE database/migrations/009_add_database_constraints.sql;
SOURCE database/migrations/010_create_security_tables.sql;
```

**Verification:**

```sql
-- Check triggers created
SHOW TRIGGERS LIKE 'loan_installments';

-- Check constraints added
SELECT CONSTRAINT_NAME, TABLE_NAME
FROM information_schema.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA = 'windeep_finance'
  AND CONSTRAINT_TYPE = 'CHECK';

-- Check new tables created
SHOW TABLES LIKE 'security_logs';
SHOW TABLES LIKE 'ci_sessions';

-- Verify UTR uniqueness
SHOW INDEX FROM bank_transactions WHERE Key_name = 'idx_utr_unique';
```

---

## PHASE 2: APPLICATION CODE DEPLOYMENT (15-30 minutes)

### Step 2.1: Deploy Code Files

```bash
# Navigate to application directory
cd /var/www/windeep_finance

# Create deployment backup
cp -r application application_backup_$(date +%Y%m%d)

# Copy fixed files (use Git or manual copy)
# Option A: Git pull (if using version control)
git pull origin main

# Option B: Manual copy
# Upload files via FTP/SFTP to respective locations

# Set correct permissions
sudo chown -R www-data:www-data application/
sudo chmod -R 755 application/
sudo chmod -R 777 application/cache/
sudo chmod -R 777 application/logs/
sudo chmod -R 777 uploads/
```

### Step 2.2: Update Configuration

```bash
# Update security config
cd application/config

# Enable CSRF protection
nano config.php
```

**In `config.php`, update:**

```php
$config['csrf_protection'] = TRUE;
$config['csrf_token_name'] = 'windeep_csrf_token';
$config['csrf_cookie_name'] = 'windeep_csrf_cookie';
$config['csrf_expire'] = 7200;
$config['csrf_regenerate'] = TRUE;

$config['cookie_secure'] = TRUE; // HTTPS only
$config['cookie_httponly'] = TRUE;
$config['cookie_samesite'] = 'Lax';

$config['sess_driver'] = 'database';
$config['sess_save_path'] = 'ci_sessions';
```

### Step 2.3: Update Environment to Production

```bash
# Edit index.php
nano index.php
```

**Change:**

```php
define('ENVIRONMENT', 'production'); // Was 'development'
```

---

## PHASE 3: PASSWORD MIGRATION (30 minutes)

### Step 3.1: Rehash Existing Passwords

**Option A: Force password reset (Recommended)**

```sql
-- Update all admin users to require password reset
UPDATE admin SET 
    password = '', 
    password_reset_required = 1,
    password_reset_token = MD5(CONCAT(email, NOW())),
    updated_at = NOW();

-- Send password reset emails (implement in application)
```

**Option B: Migrate MD5 to bcrypt (if users have email access)**

```php
// Run migration script (create in database/)
// This will be handled automatically on first login by User_model
```

---

## PHASE 4: VERIFICATION & TESTING (1-2 hours)

### Step 4.1: Run Validation Queries

```bash
mysql -u root -p windeep_finance < database/validation_queries.sql > validation_results.txt
```

**Review `validation_results.txt`** for any ❌ CRITICAL issues.

### Step 4.2: Functional Testing Checklist

Test these critical flows:

- [ ] User login works
- [ ] Member registration
- [ ] Loan application creation
- [ ] Loan approval workflow
- [ ] Loan disbursement
- [ ] EMI calculation (verify numbers)
- [ ] Payment recording (verify allocation order)
- [ ] Fine calculation
- [ ] Bank statement import
- [ ] Bank transaction mapping
- [ ] Split payment mapping
- [ ] Report generation (trial balance, P&L)
- [ ] Skip EMI functionality
- [ ] Ledger reconciliation

### Step 4.3: Security Testing

```bash
# Test rate limiting
curl -X POST http://yourdomain.com/auth/login \
  -d "email=test@test.com&password=wrong" \
  -c cookies.txt -b cookies.txt

# Repeat 6 times - should get locked out

# Test CSRF protection (should fail without token)
curl -X POST http://yourdomain.com/loans/create \
  -d "amount=10000" \
  -H "Content-Type: application/x-www-form-urlencoded"
```

### Step 4.4: Performance Testing

```bash
# Install Apache Bench (if not installed)
sudo apt-get install apache2-utils

# Test login page
ab -n 100 -c 10 http://yourdomain.com/auth/login

# Test dashboard (with session cookie)
ab -n 100 -c 10 -C "session_cookie=your_session_id" http://yourdomain.com/dashboard
```

**Target Metrics:**
- Response time < 500ms (p95)
- No errors in 100 requests
- Database connections < 50

---

## PHASE 5: DATA RECONCILIATION (30 minutes)

### Step 5.1: Verify Outstanding Balances

```sql
-- Run reconciliation
CALL sp_recalculate_loan_outstanding();

-- Check for discrepancies
SELECT 
    l.id,
    l.loan_number,
    l.outstanding_principal AS stored,
    fn_get_loan_outstanding(l.id) AS calculated,
    ABS(l.outstanding_principal - fn_get_loan_outstanding(l.id)) AS diff
FROM loans l
WHERE l.status IN ('active', 'overdue')
  AND ABS(l.outstanding_principal - fn_get_loan_outstanding(l.id)) > 0.01;
```

### Step 5.2: Verify Trial Balance

```sql
-- Trial balance should balance
SELECT 
    SUM(debit_amount) AS total_debit,
    SUM(credit_amount) AS total_credit,
    ABS(SUM(debit_amount) - SUM(credit_amount)) AS difference
FROM general_ledger;

-- Difference should be 0.00
```

---

## PHASE 6: MONITORING SETUP (30 minutes)

### Step 6.1: Enable Error Logging

```bash
# Create logs directory
mkdir -p /var/log/windeep_finance
sudo chown www-data:www-data /var/log/windeep_finance
sudo chmod 755 /var/log/windeep_finance
```

**In `application/config/config.php`:**

```php
$config['log_threshold'] = 1; // Errors only
$config['log_path'] = '/var/log/windeep_finance/';
$config['log_file_permissions'] = 0644;
```

### Step 6.2: Setup Log Rotation

```bash
sudo nano /etc/logrotate.d/windeep_finance
```

**Add:**

```
/var/log/windeep_finance/*.log {
    daily
    rotate 90
    compress
    delaycompress
    notifempty
    create 0644 www-data www-data
    sharedscripts
    postrotate
        /usr/bin/systemctl reload php8.2-fpm > /dev/null 2>&1 || true
    endscript
}
```

### Step 6.3: Setup Database Monitoring

```bash
# Install Percona Monitoring (optional but recommended)
# OR setup simple monitoring queries

# Create monitoring script
nano /usr/local/bin/windeep_monitor.sh
```

**Add:**

```bash
#!/bin/bash
LOG_FILE="/var/log/windeep_finance/monitor.log"

echo "=== Monitoring Check: $(date) ===" >> $LOG_FILE

# Check database connection
mysql -u root -p'password' -e "SELECT 1" &>/dev/null
if [ $? -ne 0 ]; then
    echo "CRITICAL: Database connection failed" >> $LOG_FILE
    # Send alert email
fi

# Check active loans count
ACTIVE_LOANS=$(mysql -u root -p'password' windeep_finance -N -e "SELECT COUNT(*) FROM loans WHERE status='active'")
echo "Active loans: $ACTIVE_LOANS" >> $LOG_FILE

# Check pending EMIs
PENDING_EMIS=$(mysql -u root -p'password' windeep_finance -N -e "SELECT COUNT(*) FROM loan_installments WHERE status='pending'")
echo "Pending EMIs: $PENDING_EMIS" >> $LOG_FILE

# Check trial balance
BALANCE_DIFF=$(mysql -u root -p'password' windeep_finance -N -e "SELECT ABS(SUM(debit_amount) - SUM(credit_amount)) FROM general_ledger")
echo "Trial balance difference: $BALANCE_DIFF" >> $LOG_FILE

if (( $(echo "$BALANCE_DIFF > 1.0" | bc -l) )); then
    echo "WARNING: Trial balance not balanced!" >> $LOG_FILE
    # Send alert
fi
```

**Make executable and schedule:**

```bash
chmod +x /usr/local/bin/windeep_monitor.sh

# Add to crontab (run every hour)
crontab -e
```

**Add:**

```
0 * * * * /usr/local/bin/windeep_monitor.sh
```

---

## PHASE 7: ROLLBACK PLAN (In case of issues)

### Rollback Procedure

```bash
# 1. Stop application (put in maintenance mode)
touch /var/www/windeep_finance/maintenance.flag

# 2. Restore database
mysql -u root -p windeep_finance < backup_YYYYMMDD_HHMMSS.sql

# 3. Restore application code
rm -rf /var/www/windeep_finance/application
cp -r /var/www/windeep_finance/application_backup_YYYYMMDD /var/www/windeep_finance/application

# 4. Restart services
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx # or apache2

# 5. Remove maintenance flag
rm /var/www/windeep_finance/maintenance.flag
```

---

## PHASE 8: POST-DEPLOYMENT (First 24 hours)

### Monitoring Checklist

- [ ] Monitor error logs every 2 hours
- [ ] Check security_logs for suspicious activity
- [ ] Verify EMI calculations for new loans
- [ ] Monitor database performance (slow queries)
- [ ] Check trial balance daily
- [ ] Verify backup jobs running
- [ ] Monitor user feedback/support tickets

### Day 1 Tasks

```sql
-- Check for any negative balances
SELECT 'Negative Balances', COUNT(*)
FROM loans
WHERE outstanding_principal < 0;

-- Check for new fines duplicates
SELECT related_id, COUNT(*)
FROM fines
WHERE fine_date = CURDATE()
GROUP BY related_id
HAVING COUNT(*) > 1;

-- Check failed logins
SELECT COUNT(*) AS failed_login_count
FROM security_logs
WHERE event_type = 'login_failed'
  AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR);
```

---

## EMERGENCY CONTACTS

**Technical Issues:**
- Developer: [Your Name/Contact]
- DBA: [Database Admin Contact]
- DevOps: [Server Admin Contact]

**Business Issues:**
- Finance Team Lead: [Contact]
- Operations Manager: [Contact]

---

## SUCCESS CRITERIA

Deployment is considered successful when:

✅ All migrations applied without errors  
✅ All validation queries return clean (no ❌ CRITICAL)  
✅ Trial balance is balanced (difference = 0.00)  
✅ User login works with rate limiting  
✅ New loan creation and disbursement works  
✅ Payment allocation follows RBI order (Interest → Principal → Fine)  
✅ EMI calculations match expected values  
✅ No duplicate fines created  
✅ No negative balances in any account  
✅ Security logs recording events  
✅ No critical errors in application logs  
✅ Response times < 500ms  
✅ Backup jobs scheduled and working  

---

## FINAL NOTES

1. **Keep backups for 90 days** before deleting
2. **Monitor logs daily** for first week
3. **Run validation queries weekly** for first month
4. **Schedule monthly security audits**
5. **Update documentation** as system evolves

**Deployment completed by:** _________________  
**Date:** _________________  
**Sign-off:** _________________
