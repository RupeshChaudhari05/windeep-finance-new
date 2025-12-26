# Windeep Finance Management System

A comprehensive banking and loan management system built with CodeIgniter 3.x, MySQL, and AdminLTE.

## 🚀 Quick Start

### Prerequisites
- PHP 8.0 or higher
- MySQL 5.7 or higher
- Apache with mod_rewrite enabled
- Composer (optional, for dependencies)

### Installation Steps

1. **Clone or Download the Project**
   ```bash
   git clone <repository-url>
   cd windeep_finance
   ```

2. **Configure Environment**
   
   Copy `.env.example` to `.env`:
   ```bash
   copy .env.example .env
   ```
   
   Edit `.env` and update your configuration:
   ```env
   APP_ENV=development
   APP_DEBUG=true
   APP_URL=http://localhost/windeep_finance
   
   DB_HOST=localhost
   DB_PORT=3306
   DB_NAME=windeep_finance_new
   DB_USERNAME=root
   DB_PASSWORD=
   ```

3. **Import Database**
   
   Import the database schema:
   ```bash
   mysql -u root -p < database/schema.sql
   ```
   
   Or use phpMyAdmin to import `database/schema.sql`

4. **Create Admin User**
   
   Run the admin creation script:
   ```bash
   php create_admin.php
   ```
   
   This will create/update admin user with:
   - Email: admin@windeep.com
   - Password: admin123
   
   **⚠️ IMPORTANT: Change the password after first login!**

5. **Configure Apache**
   
   Ensure your `.htaccess` is properly configured (already included).
   
   Make sure `mod_rewrite` is enabled in Apache:
   ```apache
   LoadModule rewrite_module modules/mod_rewrite.so
   ```

6. **Set Permissions** (Linux/Mac)
   ```bash
   chmod -R 755 application/cache
   chmod -R 755 application/logs
   chmod -R 755 uploads
   ```

7. **Access the Application**
   
   Open your browser and visit:
   ```
   http://localhost/windeep_finance
   ```
   
   Login with the admin credentials created in step 4.

## 🔧 Configuration

### Environment Variables

All configuration is managed through the `.env` file:

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_ENV` | Application environment (development/production) | development |
| `APP_DEBUG` | Enable debug mode | true |
| `APP_URL` | Application base URL | http://localhost/windeep_finance |
| `DB_HOST` | Database host | localhost |
| `DB_PORT` | Database port | 3306 |
| `DB_NAME` | Database name | windeep_finance_new |
| `DB_USERNAME` | Database username | root |
| `DB_PASSWORD` | Database password | (empty) |

### URL Configuration

The system is configured to work **without** `index.php` in URLs:

- ✅ `http://localhost/windeep_finance/admin/login`
- ❌ `http://localhost/windeep_finance/index.php/admin/login`

If you see `index.php` in URLs, ensure:
1. `.htaccess` file exists in root directory
2. Apache `mod_rewrite` is enabled
3. `AllowOverride All` is set in Apache config

## 📁 Project Structure

```
windeep_finance/
├── .env                    # Environment configuration (not in git)
├── .env.example           # Example environment file
├── .htaccess              # Apache rewrite rules
├── index.php              # Application entry point
├── create_admin.php       # Admin user creation script
├── application/
│   ├── config/            # Configuration files
│   ├── controllers/       # Application controllers
│   │   └── admin/         # Admin panel controllers
│   ├── models/            # Database models
│   ├── views/             # View templates
│   │   └── admin/         # Admin panel views
│   ├── helpers/           # Helper functions
│   │   └── env_helper.php # Environment variable loader
│   ├── libraries/         # Custom libraries
│   └── core/              # Core extensions (MY_Controller, MY_Model)
├── assets/                # CSS, JS, images
├── database/              # Database schemas and migrations
├── system/                # CodeIgniter core
├── uploads/               # Uploaded files
└── vendor/                # Composer dependencies
```

## 🔐 Security

### Important Security Measures

1. **Change Default Password**
   - After first login, immediately change the default admin password
   - Go to Settings → Admin Users → Edit Profile

2. **Environment File**
   - Never commit `.env` to version control
   - `.env` is already in `.gitignore`

3. **File Permissions**
   - Ensure `application/config/` is not writable by web server
   - Set proper permissions on `uploads/` directory

4. **Production Deployment**
   - Set `APP_ENV=production` in `.env`
   - Set `APP_DEBUG=false` in `.env`
   - Enable HTTPS
   - Use strong database passwords
   - Update `ENCRYPTION_KEY` in `.env`

## 🌐 URLs & Routes

### Public URLs
- Homepage: `/`
- Login: `/login` or `/admin/login`

### Admin URLs
- Dashboard: `/admin`
- Members: `/admin/members`
- Savings: `/admin/savings`
- Loans: `/admin/loans`
- Fines: `/admin/fines`
- Bank Import: `/admin/bank/import`
- Reports: `/admin/reports`
- Settings: `/admin/settings`

## 🐛 Troubleshooting

### Issue: 404 Page Not Found

**Solution:**
1. Check if `.htaccess` exists in root directory
2. Enable `mod_rewrite` in Apache
3. Set `AllowOverride All` in Apache virtual host config

### Issue: index.php appearing in URLs

**Solution:**
1. Verify `.htaccess` is properly configured
2. Check `$config['index_page'] = '';` in `application/config/config.php`
3. Restart Apache after changes

### Issue: Database Connection Error

**Solution:**
1. Verify database credentials in `.env`
2. Ensure MySQL service is running
3. Check database exists: `mysql -u root -p -e "SHOW DATABASES;"`
4. Import schema if missing: `mysql -u root -p < database/schema.sql`

### Issue: Blank Page or Errors

**Solution:**
1. Check PHP error log
2. Enable error display in `.env`: `APP_DEBUG=true`
3. Check file permissions on `application/cache` and `application/logs`
4. Verify PHP version: `php -v` (requires 8.0+)

### Issue: Redirect Loop on Login

**Solution:**
1. Clear browser cookies and cache
2. Check session configuration in `application/config/config.php`
3. Verify admin user exists and is active in database
4. Run `php create_admin.php` to recreate admin user

## 📊 Features

- ✅ Member Management
- ✅ Savings Accounts
- ✅ Loan Management (Flat & Reducing Balance)
- ✅ Fine Management
- ✅ Bank Statement Import & Auto-matching
- ✅ Financial Reports
- ✅ Audit Logging
- ✅ Multi-user Support with Roles & Permissions
- ✅ Responsive Design (AdminLTE 3.2)
- ✅ RESTful API Support

## 🔄 Updates & Maintenance

### Updating Configuration

1. Edit `.env` file for environment-specific changes
2. Changes take effect immediately (no cache clear needed)

### Database Migrations

1. Place migration files in `database/migrations/`
2. Run migrations manually or through admin panel

### Backup

Regular backups recommended:
```bash
# Database backup
mysqldump -u root -p windeep_finance_new > backup_$(date +%Y%m%d).sql

# File backup
tar -czf files_backup_$(date +%Y%m%d).tar.gz uploads/
```

## 📞 Support

For issues or questions:
1. Check this README
2. Review error logs in `application/logs/`
3. Enable debug mode: Set `APP_DEBUG=true` in `.env`

## 📝 License

This project is proprietary software. All rights reserved.

---

**Version:** 1.0.0  
**Last Updated:** December 26, 2025
