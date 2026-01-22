# Windeep Finance - Production Deployment Guide

## 🚀 Complete Production-Ready Application Guide

This document provides comprehensive instructions for deploying, configuring, and maintaining Windeep Finance in a production environment.

---

## Table of Contents

1. [System Requirements](#system-requirements)
2. [Pre-Deployment Checklist](#pre-deployment-checklist)
3. [Installation Steps](#installation-steps)
4. [Database Setup](#database-setup)
5. [Configuration Guide](#configuration-guide)
6. [Cron Job Setup](#cron-job-setup)
7. [Email & WhatsApp Integration](#email--whatsapp-integration)
8. [Security Hardening](#security-hardening)
9. [Performance Optimization](#performance-optimization)
10. [Backup & Recovery](#backup--recovery)
11. [Monitoring & Logging](#monitoring--logging)
12. [Troubleshooting](#troubleshooting)
13. [Maintenance Tasks](#maintenance-tasks)

---

## System Requirements

### Minimum Hardware
- **CPU:** 2 cores
- **RAM:** 4 GB
- **Storage:** 20 GB SSD
- **Network:** 100 Mbps

### Recommended Hardware
- **CPU:** 4+ cores
- **RAM:** 8+ GB
- **Storage:** 100 GB SSD
- **Network:** 1 Gbps

### Software Requirements
| Component | Minimum Version | Recommended |
|-----------|-----------------|-------------|
| PHP | 7.4 | 8.1+ |
| MySQL/MariaDB | 5.7 / 10.3 | 8.0 / 10.6 |
| Apache/Nginx | 2.4 / 1.18 | Latest |
| SSL Certificate | Required | Let's Encrypt |

### Required PHP Extensions
```
php-mysql
php-mysqli
php-pdo
php-mbstring
php-json
php-curl
php-zip
php-gd
php-xml
php-intl
```

---

## Pre-Deployment Checklist

### ✅ Before Going Live

- [ ] All migrations executed successfully
- [ ] Admin user created and tested
- [ ] Company settings configured
- [ ] Email settings tested (send test email)
- [ ] WhatsApp integration tested (if enabled)
- [ ] SSL certificate installed
- [ ] Database backups configured
- [ ] Cron jobs scheduled
- [ ] Error logging configured
- [ ] File permissions set correctly
- [ ] .htaccess security rules in place
- [ ] Remove/disable debug mode
- [ ] Test all critical workflows:
  - [ ] Member registration
  - [ ] Loan application and approval
  - [ ] Payment collection
  - [ ] Savings enrollment
  - [ ] Report generation

---

## Installation Steps

### 1. Upload Application Files
```bash
# Using Git
git clone <repository-url> /var/www/windeep_finance

# Or upload via FTP/SFTP
# Upload all files to web root
```

### 2. Set File Permissions
```bash
# On Linux/Unix
cd /var/www/windeep_finance

# Set ownership
chown -R www-data:www-data .

# Set directory permissions
find . -type d -exec chmod 755 {} \;

# Set file permissions
find . -type f -exec chmod 644 {} \;

# Writable directories
chmod -R 775 application/logs
chmod -R 775 application/cache
chmod -R 775 uploads
```

### 3. Install Dependencies
```bash
composer install --no-dev --optimize-autoloader
```

### 4. Configure Virtual Host

**Apache:**
```apache
<VirtualHost *:443>
    ServerName finance.yourdomain.com
    DocumentRoot /var/www/windeep_finance

    SSLEngine on
    SSLCertificateFile /path/to/cert.pem
    SSLCertificateKeyFile /path/to/key.pem

    <Directory /var/www/windeep_finance>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/windeep_error.log
    CustomLog ${APACHE_LOG_DIR}/windeep_access.log combined
</VirtualHost>
```

**Nginx:**
```nginx
server {
    listen 443 ssl;
    server_name finance.yourdomain.com;
    root /var/www/windeep_finance;
    index index.php;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(ht|git) {
        deny all;
    }
}
```

---

## Database Setup

### 1. Create Database
```sql
CREATE DATABASE windeep_finance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'windeep_user'@'localhost' IDENTIFIED BY 'StrongPassword123!';
GRANT ALL PRIVILEGES ON windeep_finance.* TO 'windeep_user'@'localhost';
FLUSH PRIVILEGES;
```

### 2. Import Schema
```bash
mysql -u windeep_user -p windeep_finance < database/schema.sql
```

### 3. Run Migrations
Execute all migrations in order:
```bash
# From project root
php database/run_migration_005.php
php database/run_migration_006.php
php database/run_migration_007.php
php database/run_migration_008.php
php database/run_migration_009.php
php database/run_migration_011.php

# Or execute SQL directly
mysql -u windeep_user -p windeep_finance < database/migrations/016_add_performance_indexes.sql
mysql -u windeep_user -p windeep_finance < database/migrations/017_email_whatsapp_features.sql
```

### 4. Create Admin User
```sql
INSERT INTO admin_users (name, email, password, role, status, created_at) 
VALUES ('Administrator', 'admin@yourdomain.com', 
        '$2y$10$YourHashedPasswordHere', 'super_admin', 'active', NOW());
```

Or use the seeder:
```bash
php index.php cli/seeder/run
```

---

## Configuration Guide

### Environment Configuration

Create or edit `application/config/database.php`:
```php
$db['default'] = array(
    'hostname' => getenv('DB_HOST') ?: 'localhost',
    'username' => getenv('DB_USER') ?: 'windeep_user',
    'password' => getenv('DB_PASS') ?: 'your_password',
    'database' => getenv('DB_NAME') ?: 'windeep_finance',
    'dbdriver' => 'mysqli',
    'char_set' => 'utf8mb4',
    'dbcollat' => 'utf8mb4_unicode_ci',
);
```

### Application Settings

Edit `application/config/config.php`:
```php
// Base URL (REQUIRED - set to your domain)
$config['base_url'] = 'https://finance.yourdomain.com/';

// Index page (leave empty with .htaccess)
$config['index_page'] = '';

// Session settings
$config['sess_driver'] = 'database';
$config['sess_save_path'] = 'ci_sessions';
$config['sess_expiration'] = 7200; // 2 hours
$config['sess_time_to_update'] = 300;

// Cookie settings
$config['cookie_secure'] = TRUE; // HTTPS only
$config['cookie_httponly'] = TRUE;

// CSRF Protection
$config['csrf_protection'] = TRUE;
$config['csrf_expire'] = 7200;
```

### System Settings (via Admin Panel)

Navigate to **Settings** in the admin panel:

| Setting | Description | Example |
|---------|-------------|---------|
| Company Name | Your organization name | Windeep Finance Ltd |
| Company Address | Full postal address | 123 Main Street, City |
| Company Phone | Contact number | +91 9876543210 |
| Company Email | Official email | info@yourdomain.com |
| Currency Symbol | Display currency | ₹ |
| Date Format | Date display format | d/m/Y |
| Financial Year Start | FY start month | April |

---

## Cron Job Setup

### Required Cron Jobs

Add to crontab (`crontab -e`):

```cron
# Windeep Finance Cron Jobs
# ============================

# Hourly: Process email queue, check guarantor consents
0 * * * * cd /var/www/windeep_finance && php index.php cli/cron/hourly >> /var/log/windeep_cron.log 2>&1

# Daily at 2 AM: Apply fines, mark overdue, send reminders, NPA updates
0 2 * * * cd /var/www/windeep_finance && php index.php cli/cron/daily >> /var/log/windeep_cron.log 2>&1

# Weekly on Sunday at 3 AM: Extend savings schedules, weekly reports
0 3 * * 0 cd /var/www/windeep_finance && php index.php cli/cron/weekly >> /var/log/windeep_cron.log 2>&1

# Monthly on 1st at 4 AM: Calculate interest, monthly reports, backup
0 4 1 * * cd /var/www/windeep_finance && php index.php cli/cron/monthly >> /var/log/windeep_cron.log 2>&1
```

### Cron Job Functions

| Schedule | Job | Description |
|----------|-----|-------------|
| Hourly | `process_pending_emails` | Send queued emails |
| Hourly | `check_pending_consents` | Remind guarantors |
| Daily | `apply_overdue_fines` | Calculate and apply late fees |
| Daily | `mark_overdue_installments` | Flag overdue payments |
| Daily | `send_due_reminders` | Notify upcoming dues |
| Daily | `update_npa_status` | Classify NPA loans |
| Weekly | `extend_savings_schedules` | Add new schedule entries |
| Weekly | `cleanup_old_notifications` | Remove old notifications |
| Monthly | `calculate_savings_interest` | Credit interest to savings |
| Monthly | `generate_monthly_reports` | Create period reports |
| Monthly | `database_backup` | Automated backup |

### Verify Cron Setup
```bash
# Test cron execution
php index.php cli/cron/test

# Check cron status
php index.php cli/cron/status
```

---

## Email & WhatsApp Integration

### Email Configuration (SMTP)

Navigate to **Settings > Email** or configure in `application/config/emailss.php`:

```php
$config['protocol'] = 'smtp';
$config['smtp_host'] = 'smtp.gmail.com';  // Or your SMTP server
$config['smtp_port'] = 587;
$config['smtp_user'] = 'your-email@gmail.com';
$config['smtp_pass'] = 'your-app-password';
$config['smtp_crypto'] = 'tls';
$config['mailtype'] = 'html';
$config['charset'] = 'utf-8';
$config['newline'] = "\r\n";
```

**For Gmail:** Create an App Password at https://myaccount.google.com/apppasswords

### WhatsApp Configuration

Navigate to **Settings > WhatsApp** in admin panel:

#### Option 1: Meta Cloud API (Recommended)
```
Provider: Meta Cloud API
API URL: https://graph.facebook.com/v18.0
Access Token: [Your permanent token]
Phone Number ID: [From Meta Business Suite]
Business Account ID: [From Meta Business Suite]
```

#### Option 2: Twilio
```
Provider: Twilio
Account SID: [Your Twilio SID]
Auth Token: [Your Twilio Token]
WhatsApp Number: +14155238886
```

#### Test WhatsApp
```php
$this->load->library('whatsapp');
$result = $this->whatsapp->send_message('919876543210', 'Test message');
var_dump($result);
```

### Email Verification Flow

1. Member registers or admin creates account
2. System sends verification email with token
3. Member clicks link: `/verify/email/{token}`
4. Email marked as verified
5. Full access granted

---

## Security Hardening

### 1. File Security

Create/verify `.htaccess` in root:
```apache
# Deny access to sensitive files
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

# Protect application directory
<FilesMatch "(config|database)\.php$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Disable directory listing
Options -Indexes

# Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 2. PHP Configuration

In `php.ini` or `.user.ini`:
```ini
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
```

### 3. Database Security

- Use strong passwords (16+ characters)
- Grant minimal privileges
- Enable SSL connections
- Regular credential rotation

### 4. Application Security

- Enable CSRF protection ✓
- Use prepared statements ✓
- Validate all inputs ✓
- Sanitize outputs ✓
- Rate limiting on login ✓
- Session timeout configured ✓

### 5. Security Headers

Add to `.htaccess`:
```apache
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
Header set Referrer-Policy "strict-origin-when-cross-origin"
Header set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdnjs.cloudflare.com;"
```

---

## Performance Optimization

### 1. Database Indexes

All critical indexes are in `database/migrations/016_add_performance_indexes.sql`:
```sql
-- Run to add all performance indexes
mysql -u user -p database < database/migrations/016_add_performance_indexes.sql
```

### 2. Query Optimization

Enable query caching in MySQL:
```sql
SET GLOBAL query_cache_type = ON;
SET GLOBAL query_cache_size = 67108864;
```

### 3. PHP OpCache

In `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0  # Disable in production
opcache.fast_shutdown=1
```

### 4. Compression

Enable in `.htaccess`:
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>
```

### 5. Browser Caching

```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access 1 year"
    ExpiresByType image/png "access 1 year"
    ExpiresByType text/css "access 1 month"
    ExpiresByType application/javascript "access 1 month"
</IfModule>
```

---

## Backup & Recovery

### Automated Backups

Backups are created automatically by the monthly cron job. Manual backups can be created from **System > Backups**.

### Manual Backup

```bash
# Database backup
mysqldump -u windeep_user -p windeep_finance > backup_$(date +%Y%m%d).sql

# Compress
gzip backup_$(date +%Y%m%d).sql

# Full backup including files
tar -czvf windeep_full_backup_$(date +%Y%m%d).tar.gz \
    /var/www/windeep_finance \
    --exclude='vendor' \
    --exclude='node_modules' \
    --exclude='application/logs/*'
```

### Restore Procedure

1. **Stop Application** (maintenance mode)
2. **Create Pre-Restore Backup** (automatic)
3. **Restore Database:**
   ```bash
   mysql -u windeep_user -p windeep_finance < backup_file.sql
   ```
4. **Verify Data Integrity**
5. **Clear Cache**
6. **Resume Application**

### Backup Retention Policy

| Type | Retention |
|------|-----------|
| Daily | 7 days |
| Weekly | 4 weeks |
| Monthly | 12 months |
| Yearly | Indefinite |

---

## Monitoring & Logging

### Application Logs

Location: `application/logs/log-YYYY-MM-DD.php`

View via admin panel: **System > Logs**

### Log Levels

| Level | Description |
|-------|-------------|
| ERROR | Critical errors requiring attention |
| WARNING | Non-critical issues |
| INFO | General information |
| DEBUG | Development debugging |

### Health Check Endpoint

```
GET /admin/system/health_check
```

Returns JSON with system status:
```json
{
    "status": "healthy",
    "checks": {
        "database": {"status": true},
        "disk_space": {"status": true},
        "cron_jobs": {"status": true}
    }
}
```

### Monitoring Recommendations

1. **Uptime Monitoring:** UptimeRobot, Pingdom
2. **Error Tracking:** Sentry, Bugsnag
3. **Performance:** New Relic, Datadog
4. **Log Aggregation:** ELK Stack, Papertrail

---

## Troubleshooting

### Common Issues

#### 1. 500 Internal Server Error
```bash
# Check Apache error log
tail -f /var/log/apache2/error.log

# Check PHP error log
tail -f /var/log/php_errors.log

# Check application log
tail -f application/logs/log-$(date +%Y-%m-%d).php
```

#### 2. Database Connection Failed
- Verify credentials in `database.php`
- Check MySQL service: `systemctl status mysql`
- Test connection: `mysql -u user -p database`

#### 3. Email Not Sending
- Check SMTP credentials
- Verify port not blocked by firewall
- Check email queue: `SELECT * FROM email_queue WHERE status = 'failed'`

#### 4. Cron Jobs Not Running
```bash
# Verify crontab
crontab -l

# Check cron log
grep CRON /var/log/syslog

# Manual test
cd /var/www/windeep_finance && php index.php cli/cron/test
```

#### 5. Session Issues
- Verify `ci_sessions` table exists
- Check session configuration
- Clear old sessions: `DELETE FROM ci_sessions WHERE timestamp < UNIX_TIMESTAMP(NOW() - INTERVAL 1 DAY)`

### Debug Mode (Development Only)

In `application/config/config.php`:
```php
$config['log_threshold'] = 4; // All messages
```

In `index.php`:
```php
define('ENVIRONMENT', 'development');
```

⚠️ **Never enable debug mode in production!**

---

## Maintenance Tasks

### Daily
- Monitor error logs
- Check failed email queue
- Verify cron execution

### Weekly
- Review audit logs
- Check disk space
- Verify backup integrity

### Monthly
- Update dependencies (security patches)
- Review user access
- Performance analysis
- Database optimization

### Quarterly
- Security audit
- Backup restoration test
- Disaster recovery drill
- Update documentation

### Database Maintenance

```sql
-- Optimize tables
OPTIMIZE TABLE members, loans, installments, transactions;

-- Analyze tables
ANALYZE TABLE members, loans, installments, transactions;

-- Clean old sessions
DELETE FROM ci_sessions WHERE timestamp < UNIX_TIMESTAMP(NOW() - INTERVAL 7 DAY);

-- Clean old notifications
DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

---

## Support & Resources

### Documentation
- [Architecture Guide](docs/ARCHITECTURE_AND_FEATURES.md)
- [Database Reference](DATABASE_REFERENCE.md)
- [API Documentation](docs/API.md)

### Getting Help
1. Check this guide first
2. Review application logs
3. Search existing issues
4. Contact support team

### Version Information
- **Application Version:** 2.0.0
- **Last Updated:** January 2025
- **CodeIgniter Version:** 3.1.13

---

## Quick Reference Commands

```bash
# Clear application cache
php index.php cli/cron/clear_cache

# Run manual backup
php index.php cli/cron/monthly backup

# Test email configuration
php index.php cli/integration/test_email

# Check system health
curl https://yourdomain.com/admin/system/health_check

# View recent errors
tail -n 100 application/logs/log-$(date +%Y-%m-%d).php

# Database backup
mysqldump -u windeep_user -p windeep_finance | gzip > backup.sql.gz
```

---

**🎉 Your Windeep Finance installation is now production-ready!**

For updates and releases, check the repository regularly.
