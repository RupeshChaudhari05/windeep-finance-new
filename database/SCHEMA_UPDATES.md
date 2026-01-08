# Database Schema Updates

## Recent Changes

### 2026-01-08: Added updated_at and updated_by Columns to Fine Rules Table

**Problem:** The application was trying to update fine rules with `updated_at` and `updated_by` fields, but these audit columns didn't exist in the `fine_rules` table. This caused SQL errors when saving fine rule updates.

**Solution:** Added audit columns to track when fine rules are modified and by whom:

```sql
ALTER TABLE `fine_rules`
ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT NULL AFTER `created_at`,
ADD COLUMN `updated_by` INT(10) UNSIGNED NULL AFTER `updated_at`;
```

**Columns Added:**
- `updated_at` (TIMESTAMP, NULL): Timestamp when the fine rule was last updated
- `updated_by` (INT UNSIGNED, NULL): Foreign key to admin_users - which admin last updated this rule

**Purpose:** Enables proper audit trail for fine rule modifications, allowing tracking of changes and who made them.

**Migration File:** Manual execution (direct ALTER TABLE)

### 2025-01-27: Added calculation_type Column to Fine Rules Table

**Problem:** The application was trying to save fine rules with a `calculation_type` field (fixed, percentage, per_day), but this column didn't exist in the `fine_rules` table. This caused SQL errors when creating or updating fine rules with the new per-day calculation type.

**Solution:** Added the `calculation_type` column to support different fine calculation methods:

```sql
ALTER TABLE `fine_rules`
ADD COLUMN `calculation_type` ENUM('fixed','percentage','per_day') NOT NULL DEFAULT 'fixed' AFTER `fine_value`,
ADD COLUMN `per_day_amount` DECIMAL(15,2) NULL COMMENT 'Amount per day for per_day calculation type' AFTER `calculation_type`;
```

**Columns Added:**
- `calculation_type` (ENUM, NOT NULL, DEFAULT 'fixed'): Type of fine calculation (fixed amount, percentage of amount, fixed + per day)
- `per_day_amount` (DECIMAL(15,2), NULL): Additional amount charged per day overdue for per_day calculation type

**Purpose:** Enables flexible fine calculation methods, specifically supporting the requirement for fines like ₹100 initial + ₹10 per day overdue.

**Migration File:** Manual execution (migration script had issues, column added directly)

### 2025-01-27: Added Manual Transaction Columns to Bank Transactions Table

**Problem:** The application was trying to query `paid_by_member_id`, `paid_for_member_id`, and `updated_by` columns in the `bank_transactions` table, but these columns didn't exist. This caused SQL errors when viewing bank transaction reports.

**Solution:** Added columns to support manual transaction recording by admins:

```sql
ALTER TABLE `bank_transactions`
ADD COLUMN `paid_by_member_id` INT UNSIGNED COMMENT 'Member who made the payment' AFTER `detection_confidence`,
ADD COLUMN `paid_for_member_id` INT UNSIGNED COMMENT 'Member who received the payment' AFTER `paid_by_member_id`,
ADD COLUMN `updated_by` INT UNSIGNED COMMENT 'Admin who recorded this transaction' AFTER `paid_for_member_id`;

-- Add foreign key constraints
ALTER TABLE `bank_transactions`
ADD CONSTRAINT `fk_bank_transactions_paid_by_member` FOREIGN KEY (`paid_by_member_id`) REFERENCES `members`(`id`),
ADD CONSTRAINT `fk_bank_transactions_paid_for_member` FOREIGN KEY (`paid_for_member_id`) REFERENCES `members`(`id`),
ADD CONSTRAINT `fk_bank_transactions_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `admin_users`(`id`);
```

**Columns Added:**
- `paid_by_member_id` (INT UNSIGNED, NULL): Foreign key to members table - which member made the payment
- `paid_for_member_id` (INT UNSIGNED, NULL): Foreign key to members table - which member received the payment
- `updated_by` (INT UNSIGNED, NULL): Foreign key to admin_users table - which admin recorded this transaction

**Purpose:** These columns enable the system to track manual bank transactions recorded by admins, such as member-to-member transfers, payments received, etc.

**Migration File:** `database/run_migration_009.php`

### 2025-12-26: Added Payment Tracking to Fines Table

**Problem:** The application code was referencing `payment_date`, `payment_mode`, `payment_reference`, and `received_by` columns in the `fines` table, but these columns didn't exist in the database schema.

**Solution:** Added the following columns to the `fines` table:

```sql
ALTER TABLE fines 
ADD COLUMN payment_date DATE NULL AFTER status,
ADD COLUMN payment_mode ENUM('cash','cheque','bank_transfer','online') NULL AFTER payment_date,
ADD COLUMN payment_reference VARCHAR(100) NULL AFTER payment_reference,
ADD COLUMN received_by INT(10) UNSIGNED NULL AFTER payment_reference,
ADD KEY idx_payment_date (payment_date);
```

**Columns Added:**
- `payment_date` (DATE, NULL): Date when the fine was paid
- `payment_mode` (ENUM, NULL): Method of payment (cash, cheque, bank_transfer, online)
- `payment_reference` (VARCHAR(100), NULL): Payment reference number (cheque number, transaction ID, etc.)
- `received_by` (INT UNSIGNED, NULL): Foreign key to admin_users - who received the payment

**Index Added:**
- `idx_payment_date`: Index on payment_date for faster date-range queries

**Data Migration:**
Existing paid/partial fines were updated to set payment_date based on their updated_at timestamp:
```sql
UPDATE fines 
SET payment_date = DATE(updated_at) 
WHERE status IN ('paid', 'partial') AND payment_date IS NULL;
```

**Files Affected:**
- `application/models/Fine_model.php` - Uses these columns in record_payment()
- `application/models/Report_model.php` - Uses payment_date for collection reports
- Database schema updated

**Migration File:** `database/migrations/002_add_payment_tracking_to_fines.sql`

## Complete Fines Table Structure

```sql
CREATE TABLE `fines` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fine_code` varchar(30) NOT NULL,
  `member_id` int(10) unsigned NOT NULL,
  `fine_type` enum('savings_late','loan_late','bounced_cheque','other') NOT NULL,
  `related_type` enum('savings_schedule','loan_installment','other') NOT NULL,
  `related_id` int(10) unsigned NOT NULL,
  `fine_rule_id` int(10) unsigned DEFAULT NULL,
  `fine_date` date NOT NULL,
  `due_date` date NOT NULL COMMENT 'Original due date',
  `days_late` int(11) NOT NULL,
  `fine_amount` decimal(15,2) NOT NULL,
  `paid_amount` decimal(15,2) DEFAULT 0.00,
  `waived_amount` decimal(15,2) DEFAULT 0.00,
  `balance_amount` decimal(15,2) NOT NULL,
  `status` enum('pending','partial','paid','waived','cancelled') DEFAULT 'pending',
  `payment_date` date DEFAULT NULL,                                   -- NEW
  `payment_mode` enum('cash','cheque','bank_transfer','online') DEFAULT NULL,  -- NEW
  `payment_reference` varchar(100) DEFAULT NULL,                      -- NEW
  `received_by` int(10) unsigned DEFAULT NULL,                        -- NEW
  `waived_by` int(10) unsigned DEFAULT NULL,
  `waived_at` timestamp NULL DEFAULT NULL,
  `waiver_reason` varchar(255) DEFAULT NULL,
  `waiver_approved_by` int(10) unsigned DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_fine_code` (`fine_code`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_status` (`status`),
  KEY `idx_fine_date` (`fine_date`),
  KEY `idx_payment_date` (`payment_date`),                            -- NEW
  KEY `fine_rule_id` (`fine_rule_id`),
  CONSTRAINT `fines_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `fines_ibfk_2` FOREIGN KEY (`fine_rule_id`) REFERENCES `fine_rules` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Usage in Application

### Recording Fine Payments
When a fine is paid, the following data is now tracked:
```php
$this->Fine_model->record_payment($fine_id, [
    'amount' => 500.00,
    'payment_mode' => 'cash',
    'reference' => 'CASH-001',
    'received_by' => $admin_id
]);
```

### Reporting Fine Collections
Fine collection reports now properly filter by payment_date:
```php
// Monthly collection report
$this->db->select_sum('paid_amount')
    ->where('payment_date >=', '2025-12-01')
    ->where('payment_date <=', '2025-12-31')
    ->get('fines');
```

## Notes

- **Backward Compatibility**: Existing code continues to work as these columns allow NULL values
- **Data Integrity**: Foreign key on `received_by` ensures valid admin user references
- **Performance**: Index on `payment_date` ensures fast date-range queries for reports
- **Audit Trail**: Combined with `updated_at`, provides complete payment tracking

## Future Considerations

If fine payments become more complex, consider creating a separate `fine_payments` table similar to `loan_payments` for:
- Multiple partial payments
- Payment reversals
- Detailed payment tracking
- Better audit trail

For now, the current structure is sufficient for basic payment tracking.
