# 🔄 Admin Migration Manager - Setup Guide

## Overview
This is a **one-click migration management system** for your admin panel. Execute database migrations with full tracking and history.

---

## 📦 Installation Steps

### Step 1: Create Migration Tracking Table
Run this SQL in **PHPMyAdmin** or via CLI:

```sql
-- From file: database/migrations/migration_tracking_table.sql
-- Copy entire content and execute
```

**Or quick paste:**
```sql
CREATE TABLE IF NOT EXISTS `migrations` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `migration_name` VARCHAR(255) NOT NULL UNIQUE,
    `migration_file` LONGTEXT NOT NULL,
    `status` ENUM('pending', 'running', 'completed', 'failed', 'rolled_back') DEFAULT 'pending',
    `executed_by` INT UNSIGNED,
    `execution_timestamp` TIMESTAMP NULL,
    `completion_timestamp` TIMESTAMP NULL,
    `duration_seconds` INT UNSIGNED,
    `error_message` TEXT,
    `output_log` LONGTEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`),
    KEY `idx_executed_by` (`executed_by`),
    KEY `idx_created_at` (`created_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
```

### Step 2: Add to Admin Menu
Edit: `application/views/admin/layout/sidebar.php` (or your admin menu file)

Add this link:
```html
<li class="nav-item">
    <a href="<?php echo site_url('admin/migrations'); ?>" class="nav-link">
        <i class="fas fa-sync-alt"></i> <span>Migrations</span>
    </a>
</li>
```

### Step 3: Update Admin Routes
Edit: `application/config/routes.php`

Add:
```php
$route['admin/migrations'] = 'admin/Migrations';
$route['admin/migrations/(:any)'] = 'admin/Migrations/$1';
```

### Step 4: Load Migration Helper
Edit: `application/config/autoload.php`

In `$autoload['helpers']` array, add:
```php
'migration'
```

Or load manually in controller:
```php
$this->load->helper('migration');
```

### Step 5: Create Backup Directory
Create folder: `database/backups/`

This will store database backups before running migrations.

---

## 🎯 How to Use

### Via Admin Panel (Easiest)
1. **Log in** to your admin panel
2. **Click** Admin Dashboard → Migrations
3. **See** all available migration files
4. **Click** "Execute" on any pending migration
5. **Monitor** execution in real-time
6. **View** history and details

### Execute Your First Migration
1. Go to **Admin Panel** → **Migrations**
2. Find `loan_schedule_integrity_constraints.sql`
3. Click **Execute**
4. Watch the progress modal
5. ✅ Done! Check history

### Via CLI (Optional)
```bash
php application/tests/Migration_Runner.php
```

---

## 🔒 Security Features

✅ **Admin-Only Access** - Only logged-in admins can run migrations
✅ **File Validation** - Only .sql files allowed
✅ **Audit Logging** - All executions tracked with admin ID
✅ **Error Handling** - Failed migrations logged with full error details
✅ **Database Backup** - Optional automatic backup before migration
✅ **Transaction Support** - Migrations wrapped in transactions
✅ **Idempotent** - Safe to re-run (uses DROP IF EXISTS)

---

## 📊 What Gets Tracked

Each migration execution records:
- ✓ Migration filename
- ✓ Execution status (pending/running/completed/failed)
- ✓ Admin who ran it
- ✓ Start & end time
- ✓ Duration in milliseconds
- ✓ Output log
- ✓ Error messages (if failed)

---

## 🚀 Auto-Run Migrations on Deployment

Add to `application/controllers/admin/Dashboard.php` constructor:

```php
public function __construct()
{
    parent::__construct();
    
    // Auto-run pending migrations on deployment
    if (ENVIRONMENT !== 'development') {
        $this->load->helper('migration');
        auto_run_migrations();
    }
}
```

---

## 📈 Migration History

View all executed migrations:
1. Admin Panel → Migrations → "View History"
2. See all migrations with status
3. Filter by status (completed/failed/pending)
4. Search migrations
5. View detailed execution logs

---

## ⚠️ Important Notes

### Before Running Migrations
- ✅ Take a database backup
- ✅ Test on local first
- ✅ Check migration file syntax
- ✅ Verify all dependencies

### Common Issues

**Issue: "Migration file not found"**
- Ensure file is in `database/migrations/`
- File must be named `*.sql`

**Issue: "Access denied"**
- Check user has database privileges
- User must be logged in as admin

**Issue: "Constraint already exists"**
- Migration is idempotent (uses IF NOT EXISTS, DROP IF EXISTS)
- Safe to re-run multiple times

---

## 🎓 File Structure

```
application/
├── controllers/
│   └── admin/
│       └── Migrations.php              (Main controller)
├── models/
│   └── Migration_model.php             (Database operations)
├── views/
│   └── admin/migrations/
│       ├── index.php                   (Main dashboard)
│       └── history.php                 (Migration history)
├── helpers/
│   └── migration_helper.php            (Utility functions)

database/
├── migrations/
│   ├── loan_schedule_integrity_constraints.sql
│   ├── migration_tracking_table.sql
│   └── backups/                        (Auto-created)
```

---

## 🔧 Customization

### Add Custom Migration
1. Create SQL file in `database/migrations/`
2. Name it descriptively: `add_feature_xyz.sql`
3. Go to Admin Panel → Migrations
4. Click Execute
5. ✓ Done!

### Modify Tracking
Edit `application/models/Migration_model.php` to:
- Add custom fields to migrations table
- Add email notifications on failure
- Add Slack notifications
- Add custom logging

---

## ✅ Next Steps

1. ✅ Run `migration_tracking_table.sql` in PHPMyAdmin
2. ✅ Add migration link to admin menu
3. ✅ Update routes.php
4. ✅ Load migration helper
5. ✅ Go to Admin Panel → Migrations
6. ✅ Execute `loan_schedule_integrity_constraints.sql`
7. ✅ Monitor execution in real-time
8. ✅ View history to confirm success

---

## 📞 Support

If migrations fail:
1. Check error message in modal
2. Review migration SQL for syntax errors
3. Verify database user has privileges
4. Check `loan_schedule_audit` table exists
5. Review error log in Migration History

**Test Migration Locally First!** ✅

---

**Created:** June 5, 2026
**Status:** Production Ready
**Version:** 1.0
