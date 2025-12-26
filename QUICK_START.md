# Quick Reference Guide

## 🚀 First Time Setup (5 Minutes)

### Step 1: Configure Environment
```bash
# Copy environment file
copy .env.example .env

# Edit .env and set your database details
notepad .env
```

### Step 2: Import Database
```bash
# Using MySQL command line
mysql -u root -p < database/schema.sql

# Or use phpMyAdmin to import database/schema.sql
```

### Step 3: Create Admin User
```bash
php create_admin.php
```

### Step 4: Access Application
Open browser: `http://localhost/windeep_finance`

**Login Credentials:**
- Email: `admin@windeep.com`
- Password: `admin123`

---

## 🔗 Important URLs

| Purpose | URL | Notes |
|---------|-----|-------|
| Login | `/login` or `/admin/login` | Redirects to login page |
| Dashboard | `/admin` | Main dashboard after login |
| Members | `/admin/members` | Member management |
| Savings | `/admin/savings` | Savings accounts |
| Loans | `/admin/loans` | Loan management |
| Bank Import | `/admin/bank/import` | Import bank statements |
| Reports | `/admin/reports` | Financial reports |
| Settings | `/admin/settings` | System settings |

**Note:** URLs work WITHOUT `index.php` (e.g., `/admin/login` not `/index.php/admin/login`)

---

## 🔧 Common Commands

### Reset Admin Password
```bash
php create_admin.php
```

### Check Database Connection
```bash
mysql -u root -p -e "USE windeep_finance_new; SELECT COUNT(*) FROM admin_users;"
```

### View Error Logs
```bash
# Windows
type application\logs\log-*.php

# Linux/Mac
tail -f application/logs/log-*.php
```

### Clear Cache
```bash
# Windows
del /Q application\cache\*

# Linux/Mac
rm -rf application/cache/*
```

---

## ⚙️ Configuration Files

### Environment Settings (.env)
```env
APP_ENV=development          # Change to 'production' for live
APP_DEBUG=true               # Set to 'false' for production
APP_URL=http://localhost/windeep_finance

DB_HOST=localhost
DB_NAME=windeep_finance_new
DB_USERNAME=root
DB_PASSWORD=
```

### URL Configuration
- File: `application/config/config.php`
- Remove index.php: `$config['index_page'] = '';`
- Base URL: Uses `APP_URL` from `.env`

### Database Configuration
- File: `application/config/database.php`
- Automatically loads from `.env` file

---

## 🐛 Troubleshooting Quick Fixes

### Problem: "404 Page Not Found"
**Fix:**
1. Check `.htaccess` exists
2. Restart Apache
3. Enable mod_rewrite: `a2enmod rewrite` (Linux)

### Problem: "index.php" in URLs
**Fix:**
1. Verify `$config['index_page'] = '';` in `config/config.php`
2. Check `.htaccess` file exists
3. Restart Apache

### Problem: Database connection error
**Fix:**
1. Check database exists: `SHOW DATABASES;`
2. Verify `.env` credentials
3. Import schema: `mysql -u root -p < database/schema.sql`

### Problem: Redirect loop on login
**Fix:**
1. Clear browser cookies
2. Run: `php create_admin.php`
3. Check admin is active in database

### Problem: Blank white page
**Fix:**
1. Check `application/logs/` for errors
2. Set `APP_DEBUG=true` in `.env`
3. Check PHP error log

---

## 📊 Database Tables Overview

| Table | Purpose |
|-------|---------|
| `admin_users` | Admin user accounts |
| `members` | Member information |
| `savings_accounts` | Savings account details |
| `savings_transactions` | Savings deposits/withdrawals |
| `loans` | Loan accounts |
| `loan_repayments` | Loan payment records |
| `fines` | Fines and penalties |
| `bank_imports` | Imported bank statements |
| `audit_logs` | System audit trail |
| `system_settings` | Application settings |
| `financial_years` | Financial year management |

---

## 🔐 Default Admin Credentials

**After running `create_admin.php`:**
- Email: `admin@windeep.com`
- Password: `admin123`

**⚠️ SECURITY:** Change password immediately after first login!

---

## 📁 Important Directories

| Directory | Purpose | Writable? |
|-----------|---------|-----------|
| `application/cache/` | Cache files | ✅ Yes |
| `application/logs/` | Error logs | ✅ Yes |
| `uploads/` | User uploads | ✅ Yes |
| `assets/` | CSS/JS/Images | ❌ No |
| `application/config/` | Configuration | ❌ No |

---

## 🌐 Apache Configuration

### Enable mod_rewrite (Linux)
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Enable mod_rewrite (Windows XAMPP)
1. Edit `C:\xampp\apache\conf\httpd.conf`
2. Uncomment: `LoadModule rewrite_module modules/mod_rewrite.so`
3. Restart Apache from XAMPP Control Panel

### Virtual Host Example
```apache
<VirtualHost *:80>
    ServerName windeep.local
    DocumentRoot "C:/xampp/htdocs/windeep_finance"
    
    <Directory "C:/xampp/htdocs/windeep_finance">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

---

## 🔄 Update Checklist

### Before Updating Code
- [ ] Backup database
- [ ] Backup `.env` file
- [ ] Backup `uploads/` directory
- [ ] Note current version

### After Updating Code
- [ ] Run database migrations if any
- [ ] Clear cache: `rm -rf application/cache/*`
- [ ] Check `.env` for new variables
- [ ] Test login functionality
- [ ] Verify all modules working

---

## 📞 Need Help?

1. **Check Logs:** `application/logs/log-YYYY-MM-DD.php`
2. **Enable Debug:** Set `APP_DEBUG=true` in `.env`
3. **Check README.md:** Full documentation
4. **Database Issues:** Run `php create_admin.php`

---

**Quick Start Time:** ~5 minutes  
**Last Updated:** December 26, 2025
