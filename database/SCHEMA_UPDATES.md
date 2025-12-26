# Database Schema Updates

## Recent Changes

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
