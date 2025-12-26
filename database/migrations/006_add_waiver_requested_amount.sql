ALTER TABLE `fines`
ADD COLUMN `waiver_requested_amount` DECIMAL(15, 2) NULL AFTER `waiver_requested_at`;