-- ============================================================
-- Migration: Add 'needs_revision' to loan_applications.status
-- Date: 2026-10-08
-- Purpose: "Request Revision" sets status = 'needs_revision', but the
--          enum didn't contain it, so MySQL stored '' and the
--          application vanished from every admin/member list.
-- ============================================================

ALTER TABLE `loan_applications`
    MODIFY `status` ENUM('draft','pending','under_review','needs_revision','guarantor_pending','admin_approved','member_review','member_approved','disbursed','rejected','cancelled','expired') NULL DEFAULT 'draft';

-- Repair rows that were blanked by the truncation (e.g. APP20260900278)
UPDATE `loan_applications` SET `status` = 'needs_revision'
 WHERE `status` = '' AND `revised_at` IS NOT NULL;

-- Same truncation hit foreclosure: remaining installments were set to
-- 'cancelled' before the enum had that value, leaving status = ''
-- (35 rows on loans 27, 47, 49, 155). Restore them.
UPDATE `loan_installments` SET `status` = 'cancelled'
 WHERE `status` = '' AND `remarks` LIKE 'Cancelled on loan foreclosure%';

INSERT INTO `schema_migrations` (`filename`, `applied_at`)
VALUES ('032_loan_application_needs_revision.sql', NOW())
ON DUPLICATE KEY UPDATE `applied_at` = NOW();

-- Verification
-- SELECT id, application_number, status FROM loan_applications WHERE id = 278;
