-- ============================================================
-- Migration: Loan disbursement in parts (tranches)
-- Date: 2026-10-19
-- Purpose:
--   A loan can be approved with "Disburse in parts". EMI / schedule stay on the full
--   sanctioned amount; the money is released in parts (e.g. 1 L now, 4 L later …).
--   * loan_applications.disburse_in_parts – chosen at approval
--   * loans.is_tranche                    – loan is disbursed in parts
--   * loans.disbursed_amount              – principal actually released so far
--                                           (NULL = fully disbursed, i.e. principal_amount)
--   * disbursement_tracking               – one row per part (tranche_no, source); every
--                                           existing loan gets its single part back-filled,
--                                           so Bank Mapping can link the bank debit to it
--                                           without posting the ledger a second time.
--   Also allows 'upi' as loans.disbursement_mode (the disburse form offered it already).
-- ============================================================

ALTER TABLE `loan_applications`
    ADD COLUMN IF NOT EXISTS `disburse_in_parts` TINYINT(1) NOT NULL DEFAULT 0 AFTER `approved_interest_rate`;

ALTER TABLE `loans`
    ADD COLUMN IF NOT EXISTS `is_tranche` TINYINT(1) NOT NULL DEFAULT 0 AFTER `principal_amount`,
    ADD COLUMN IF NOT EXISTS `disbursed_amount` DECIMAL(15,2) NULL DEFAULT NULL
        COMMENT 'Principal actually released; NULL = fully disbursed (= principal_amount)' AFTER `is_tranche`;

ALTER TABLE `loans`
    MODIFY COLUMN `disbursement_mode` ENUM('cash','bank_transfer','cheque','upi') NULL DEFAULT NULL;

ALTER TABLE `disbursement_tracking`
    ADD COLUMN IF NOT EXISTS `tranche_no` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `member_id`,
    ADD COLUMN IF NOT EXISTS `source` ENUM('disbursement','loan_page','bank_mapping','backfill') NOT NULL DEFAULT 'disbursement' AFTER `status`;

-- One completed part for every loan that has none yet (its original, full disbursement)
INSERT INTO `disbursement_tracking`
    (`loan_id`, `member_id`, `tranche_no`, `disbursement_amount`, `processing_fee`, `net_amount`, `disbursement_date`,
     `disbursement_mode`, `reference_number`, `status`, `source`, `remarks`, `created_by`, `created_at`)
SELECT l.id, l.member_id, 1, l.principal_amount, COALESCE(l.processing_fee, 0),
       COALESCE(l.net_disbursement, l.principal_amount - COALESCE(l.processing_fee, 0)),
       COALESCE(l.disbursement_date, DATE(l.created_at)),
       IF(l.disbursement_mode IN ('bank_transfer','cheque','cash','upi'), l.disbursement_mode, 'bank_transfer'),
       l.disbursement_reference, 'completed', 'backfill', 'Original disbursement (back-filled by migration 035)', l.disbursed_by, NOW()
FROM `loans` l
WHERE NOT EXISTS (SELECT 1 FROM `disbursement_tracking` t WHERE t.loan_id = l.id);

INSERT INTO `schema_migrations` (`filename`, `applied_at`)
VALUES ('035_loan_part_disbursement.sql', NOW())
ON DUPLICATE KEY UPDATE `applied_at` = NOW();

-- Verification:
-- SELECT COUNT(*) FROM loans l WHERE NOT EXISTS (SELECT 1 FROM disbursement_tracking t WHERE t.loan_id = l.id);  -- 0
