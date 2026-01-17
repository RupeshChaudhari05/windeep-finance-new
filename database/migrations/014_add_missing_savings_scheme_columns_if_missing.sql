-- Migration: Ensure savings_schemes has expected columns (safe, uses IF NOT EXISTS where supported)
ALTER TABLE `savings_schemes`
ADD COLUMN IF NOT EXISTS `min_deposit` DECIMAL(15, 2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS `deposit_frequency` ENUM(
    'daily',
    'weekly',
    'monthly',
    'quarterly',
    'yearly',
    'onetime'
) DEFAULT 'monthly',
ADD COLUMN IF NOT EXISTS `lock_in_period` INT UNSIGNED DEFAULT 0,
ADD COLUMN IF NOT EXISTS `penalty_rate` DECIMAL(5, 2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS `maturity_bonus` DECIMAL(5, 2) DEFAULT 0.00;

-- Note: MySQL supports ADD COLUMN IF NOT EXISTS in relatively recent versions (8.0.16+).
-- If your MySQL version does not support IF NOT EXISTS, run the following alternate SQL manually (one per missing column):
-- ALTER TABLE `savings_schemes` ADD COLUMN `min_deposit` DECIMAL(15,2) DEFAULT 0.00;
-- ALTER TABLE `savings_schemes` ADD COLUMN `deposit_frequency` ENUM('daily','weekly','monthly','quarterly','yearly','onetime') DEFAULT 'monthly';
-- ALTER TABLE `savings_schemes` ADD COLUMN `lock_in_period` INT UNSIGNED DEFAULT 0;
-- ALTER TABLE `savings_schemes` ADD COLUMN `penalty_rate` DECIMAL(5,2) DEFAULT 0.00;
-- ALTER TABLE `savings_schemes` ADD COLUMN `maturity_bonus` DECIMAL(5,2) DEFAULT 0.00;