-- Migration 021: Fix transaction_mappings schema
-- Adds related_type column, makes member_id nullable, changes mapping_type to varchar
-- Date: 2026-02-28

-- 1. Make member_id nullable (internal transactions have no member)
ALTER TABLE transaction_mappings
MODIFY COLUMN member_id int(10) unsigned DEFAULT NULL;

-- 2. Add related_type column if not exists
SET
    @col_exists = (
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE
            TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'transaction_mappings'
            AND COLUMN_NAME = 'related_type'
    );

SET
    @sql = IF(
        @col_exists = 0,
        'ALTER TABLE transaction_mappings ADD COLUMN related_type varchar(50) DEFAULT NULL AFTER mapping_type',
        'SELECT 1'
    );

PREPARE stmt FROM @sql;

EXECUTE stmt;

DEALLOCATE PREPARE stmt;

-- 3. Change mapping_type from enum to varchar for flexibility
-- (supports: savings, loan_payment, fine, other, disbursement, internal, etc.)
ALTER TABLE transaction_mappings
MODIFY COLUMN mapping_type varchar(50) NOT NULL DEFAULT 'other';