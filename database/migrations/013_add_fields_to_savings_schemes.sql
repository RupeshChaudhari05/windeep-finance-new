-- Migration: Add additional fields to savings_schemes
ALTER TABLE `savings_schemes`
ADD COLUMN `min_deposit` DECIMAL(15, 2) DEFAULT 0.00 NULL AFTER `description`,
ADD COLUMN `deposit_frequency` ENUM(
    'daily',
    'weekly',
    'monthly',
    'quarterly',
    'yearly',
    'onetime'
) DEFAULT 'monthly' AFTER `min_deposit`,
ADD COLUMN `lock_in_period` INT UNSIGNED DEFAULT 0 AFTER `deposit_frequency`,
ADD COLUMN `penalty_rate` DECIMAL(5, 2) DEFAULT 0.00 AFTER `lock_in_period`,
ADD COLUMN `maturity_bonus` DECIMAL(5, 2) DEFAULT 0.00 AFTER `penalty_rate`;