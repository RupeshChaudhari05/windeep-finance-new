-- Migration 019: Normalize phone numbers for members
-- This will strip all non-digit characters and keep the last 10 digits (common national format)
-- Requires MySQL 8+ for REGEXP_REPLACE. If your MySQL version is older, run a PHP script to update records.

UPDATE `members`
SET
    `phone` = RIGHT(
        REGEXP_REPLACE(
            COALESCE(`phone`, ''),
            '[^0-9]',
            ''
        ),
        10
    )
WHERE
    `phone` IS NOT NULL
    AND `phone` != '';

UPDATE `members`
SET
    `alternate_phone` = RIGHT(
        REGEXP_REPLACE(
            COALESCE(`alternate_phone`, ''),
            '[^0-9]',
            ''
        ),
        10
    )
WHERE
    `alternate_phone` IS NOT NULL
    AND `alternate_phone` != '';

UPDATE `members`
SET
    `nominee_phone` = RIGHT(
        REGEXP_REPLACE(
            COALESCE(`nominee_phone`, ''),
            '[^0-9]',
            ''
        ),
        10
    )
WHERE
    `nominee_phone` IS NOT NULL
    AND `nominee_phone` != '';