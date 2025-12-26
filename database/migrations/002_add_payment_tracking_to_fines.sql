-- Migration: Add payment tracking columns to fines table
-- Date: 2025-12-26
-- Description: Adds payment_date, payment_mode, payment_reference, and received_by columns to track fine payments

ALTER TABLE fines
ADD COLUMN payment_date DATE NULL AFTER status,
ADD COLUMN payment_mode ENUM(
    'cash',
    'cheque',
    'bank_transfer',
    'online'
) NULL AFTER payment_date,
ADD COLUMN payment_reference VARCHAR(100) NULL AFTER payment_mode,
ADD COLUMN received_by INT(10) UNSIGNED NULL AFTER payment_reference,
ADD KEY idx_payment_date (payment_date);

-- Update existing paid fines with payment_date
UPDATE fines
SET
    payment_date = DATE(updated_at)
WHERE
    status IN ('paid', 'partial')
    AND payment_date IS NULL;