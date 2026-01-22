-- Migration: Add due_day to savings_schemes (preferred day of month for dues)
ALTER TABLE `savings_schemes`
ADD COLUMN `due_day` TINYINT UNSIGNED DEFAULT 1;