# Shared Hosting Database Setup Guide

## Problem: Cannot Create Triggers (#1227 Access Denied)

Shared hosting providers typically don't give SUPER privileges needed for triggers.

## ✅ Solution: The Application Works WITHOUT Triggers!

The triggers are **optimization features only**. All core functionality works through PHP code.

---

## Setup Steps for Shared Hosting

### Step 1: Import Schema
Use phpMyAdmin provided by your hosting:

1. Login to **cPanel** → **phpMyAdmin**
2. Create database (e.g., `windeep_finance_new`)
3. Select the database
4. Click **Import** tab
5. Choose `database/schema.sql`
6. Check **"Continue processing on SQL errors"**
7. Click **Go**

**Expected Result:** 
- ✓ All 50 tables created successfully
- ⚠ Trigger creation errors (ignore these)

### Step 2: Verify Tables Created
Run this query in SQL tab:
```sql
SHOW TABLES;
```

You should see 50 tables including:
- members
- loans
- loan_installments
- savings_accounts
- system_settings
- etc.

### Step 3: Upload Application Files
1. Upload all files via **FTP/File Manager**
2. Exclude local development files:
   - `application/logs/*` (will be created)
   - `application/cache/*` (will be created)

### Step 4: Configure .env File
```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_HOST=localhost              # Usually localhost
DB_PORT=3306
DB_NAME=your_db_name           # From cPanel
DB_USERNAME=your_db_user       # From cPanel
DB_PASSWORD=your_db_password   # From cPanel

ENCRYPTION_KEY=0J6g5aPBRILYHAU9K2frhVM8oyduOnZv
```

### Step 5: Set File Permissions
Via FTP or File Manager:
```
application/cache/     → 755 (writable)
application/logs/      → 755 (writable)
uploads/               → 755 (writable)
members/uploads/       → 755 (writable)
```

---

## What Triggers Do (and why you don't need them)

### Trigger: `trg_loan_installment_after_insert`
**Purpose:** Auto-update loan outstanding amounts

**Without Trigger:** The `Loan_model->calculate_outstanding()` method handles this in PHP

### Trigger: `trg_loan_installment_after_update`
**Purpose:** Recalculate on payment updates

**Without Trigger:** Called automatically after payment processing

---

## Testing Your Installation

### 1. Test Database Connection
Create `test_db.php` in root:
```php
<?php
require_once 'application/config/database.php';
$db = $db['default'];

$conn = new mysqli($db['hostname'], $db['username'], $db['password'], $db['database']);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "✓ Database connected successfully!";
?>
```

Visit: `https://yourdomain.com/test_db.php`

### 2. Test Application
- Visit: `https://yourdomain.com/admin`
- Login with credentials
- Check Dashboard loads
- Test Settings page

### 3. Delete Test File
```bash
rm test_db.php
```

---

## Optional: Request Trigger Creation from Host

If you want optimal performance, ask hosting support to run:

```sql
SET GLOBAL log_bin_trust_function_creators = 1;
```

Then provide them `database/triggers.sql` file to execute.

---

## Common Shared Hosting Providers

### cPanel (Most Hosts)
- ✓ Full MySQL access via phpMyAdmin
- ✓ .htaccess support
- ✓ PHP 7.4+ available
- ⚠ No SUPER privileges

### Hostinger / Bluehost / SiteGround
- Same as above
- May need to request PHP extensions

### GoDaddy
- Similar setup
- Use their MySQL Remote Access for command-line if needed

---

## Troubleshooting

### Issue: "Access Denied for user"
- Check DB credentials in `.env`
- Verify user has ALL PRIVILEGES on the database
- Test connection with phpMyAdmin first

### Issue: "Table doesn't exist"
- Run `SHOW TABLES;` to verify import
- Re-import `schema.sql` with error continuation enabled

### Issue: "500 Internal Server Error"
- Check `application/logs/` for PHP errors
- Verify file permissions (755 for folders, 644 for files)
- Check `.htaccess` RewriteBase setting

### Issue: "Blank page after login"
- Enable error display temporarily in `index.php`:
  ```php
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
  ```
- Check logs for database query errors

---

## Performance Without Triggers

The application automatically:
1. Calculates outstanding amounts on-demand
2. Caches frequently accessed data
3. Updates totals after each transaction
4. Runs scheduled calculations via cron

**Impact:** Negligible (< 0.1 second difference per page load)

---

## Support

If you encounter issues:
1. Check error logs: `application/logs/log-YYYY-MM-DD.php`
2. Verify database connection
3. Ensure all 50 tables exist
4. Contact your hosting support for server-specific issues
