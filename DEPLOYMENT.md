# Windeep Finance - Production Deployment Guide

## System Requirements
- PHP 7.4+ with required extensions (mysqli, json, mbstring, curl)
- MySQL 5.7+ or MariaDB 10.3+
- Apache/Nginx web server
- Composer (for dependency management)

## Pre-Deployment Checklist

### 1. Environment Configuration
```bash
# Update .env file for production
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_HOST=your_db_host
DB_PORT=3306
DB_NAME=windeep_finance_new
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_password

ENCRYPTION_KEY=generate_secure_32_char_key
```

### 2. Database Setup
```sql
-- Import schema
mysql -u root -p windeep_finance_new < database/schema.sql

-- Run migrations (if needed)
-- Check application/migrations/ folder
```

### 3. File Permissions
```bash
# Linux/Unix
chmod 755 application/cache
chmod 755 application/logs
chmod 755 uploads
chmod 755 members/uploads

# Set ownership (replace with your web server user)
chown -R www-data:www-data application/cache
chown -R www-data:www-data application/logs
chown -R www-data:www-data uploads
chown -R www-data:www-data members/uploads
```

### 4. Security Hardening

#### Remove/Restrict Access to Sensitive Files
```apache
# Add to .htaccess in root
<FilesMatch "^\.env$">
    Order allow,deny
    Deny from all
</FilesMatch>

<FilesMatch "^composer\.(json|lock)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

#### Disable Directory Listing
```apache
Options -Indexes
```

#### Update base_url in config.php
```php
// application/config/config.php
$config['base_url'] = 'https://yourdomain.com/';
```

### 5. Install Dependencies
```bash
composer install --no-dev --optimize-autoloader
```

### 6. Configure Cron Jobs
```bash
# Add to crontab
0 0 * * * /usr/bin/php /path/to/windeep_finance/application/controllers/Cron.php calculate_fines
0 1 * * * /usr/bin/php /path/to/windeep_finance/application/controllers/Cron.php backup_database
```

## Post-Deployment Verification

### 1. Check Application Access
- [ ] Admin login works: `/admin`
- [ ] Member portal accessible: `/members`
- [ ] Dashboard loads properly
- [ ] Settings page functional

### 2. Test Core Features
- [ ] Member registration
- [ ] Loan application submission
- [ ] Savings account operations
- [ ] Fine calculations
- [ ] Report generation
- [ ] Email notifications

### 3. Database Backup
```bash
# Setup automated backups
mysqldump -u user -p windeep_finance_new > backup_$(date +%Y%m%d).sql
```

### 4. Monitor Logs
```bash
# Watch application logs
tail -f application/logs/log-$(date +%Y-%m-%d).php

# Watch web server logs
tail -f /var/log/apache2/error.log  # Apache
tail -f /var/log/nginx/error.log    # Nginx
```

## Rollback Plan
1. Keep database backup before deployment
2. Keep previous code version in separate directory
3. Document all configuration changes
4. Test rollback procedure in staging first

## Support & Maintenance
- Regular database backups (daily recommended)
- Update CodeIgniter framework for security patches
- Monitor error logs regularly
- Keep PHP and MySQL versions updated

## Production Database Structure
- **Database**: windeep_finance_new
- **Tables**: 50 (includes all core + security features)
- **Key Features**: 
  - Session management
  - API authentication
  - Security logging
  - Two-factor authentication support
  - Loan foreclosure system

## Contact
For technical support or deployment assistance, refer to README.md
