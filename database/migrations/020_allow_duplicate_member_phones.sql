-- Migration 020: Allow duplicate phone numbers for members
-- Drop UNIQUE index on `phone` and recreate as a non-unique index (for performance)

ALTER TABLE `members` DROP INDEX `idx_phone`;

ALTER TABLE `members` ADD KEY `idx_phone` (`phone`);