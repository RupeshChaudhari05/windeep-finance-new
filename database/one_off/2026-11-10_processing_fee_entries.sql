-- =====================================================================================
-- Processing fee entries for every disbursed loan (active, closed, foreclosed …)
-- Date: 2026-11-10 · checked against the live database (u682005304_windeep_db)
--
-- What it does
--   PART 1  For every loan that HAS a processing fee (loans.processing_fee > 0) but NO fee entry in
--           member_other_transactions, insert exactly one entry — same row the app's
--           "Processing Fee Check → Create missing fee records" button writes.
--           Live at the time of checking: 181 loans, ₹4,32,996.06 (63 active, 99 closed, 19 foreclosed).
--   PART 2  (optional, commented) 14 fee entries left over from DELETED loans — the re-created loans
--           already have their own entry, so these are duplicates. Marks them reversed (not deleted).
--   PART 3  (optional, commented) 5 active loans whose processing fee was never calculated (fee = 0).
--
-- Safe to run more than once: PART 1 only inserts where no entry exists (NOT EXISTS), so nothing is duplicated.
-- Ledger: not touched. The fee of these loans was withheld at disbursement and is already inside the
-- disbursement voucher (Cr 3200 Processing Fee Income); the ledger sync skips these entries on purpose.
-- Advance loans whose first part is not released yet are skipped (their fee is recorded on release).
--
-- Run in phpMyAdmin → SQL tab. Take a backup of member_other_transactions first.
-- =====================================================================================


-- ── PART 0 · Preview (read only) ─────────────────────────────────────────────────────
-- How many entries PART 1 will create, per loan status
SELECT l.status, COUNT(*) AS loans_missing_entry, SUM(l.processing_fee) AS amount
FROM loans l
WHERE l.processing_fee > 0
  AND NOT EXISTS (SELECT 1 FROM member_other_transactions t
                   WHERE t.transaction_type = 'processing_fee' AND t.status = 'completed'
                     AND t.reference_type = 'loan' AND t.reference_id = l.id)
  AND NOT EXISTS (SELECT 1 FROM disbursement_tracking d
                   WHERE d.loan_id = l.id AND d.status = 'pending' AND d.processing_fee > 0)
GROUP BY l.status;

-- The loans themselves
SELECT l.id, l.loan_number, m.member_code, CONCAT(m.first_name, ' ', m.last_name) AS member_name,
       l.status, l.principal_amount, l.processing_fee, l.disbursement_date
FROM loans l
JOIN members m ON m.id = l.member_id
WHERE l.processing_fee > 0
  AND NOT EXISTS (SELECT 1 FROM member_other_transactions t
                   WHERE t.transaction_type = 'processing_fee' AND t.status = 'completed'
                     AND t.reference_type = 'loan' AND t.reference_id = l.id)
  AND NOT EXISTS (SELECT 1 FROM disbursement_tracking d
                   WHERE d.loan_id = l.id AND d.status = 'pending' AND d.processing_fee > 0)
ORDER BY l.id;


-- ── PART 1 · Insert the missing fee entries ──────────────────────────────────────────
START TRANSACTION;

INSERT INTO member_other_transactions
    (member_id, transaction_type, amount, transaction_date, description,
     reference_type, reference_id, payment_mode, status, created_by, created_at)
SELECT l.member_id,
       'processing_fee',
       l.processing_fee,
       COALESCE(l.disbursement_date, DATE(l.created_at)),
       CONCAT('Loan processing fee for Loan #', l.id, ' (', l.loan_number, ') — deducted from disbursement'),
       'loan',
       l.id,
       'deducted',
       'completed',
       (SELECT a.id FROM admin_users a WHERE a.role = 'super_admin' ORDER BY a.id LIMIT 1),
       NOW()
FROM loans l
WHERE l.processing_fee > 0
  AND NOT EXISTS (SELECT 1 FROM member_other_transactions t
                   WHERE t.transaction_type = 'processing_fee' AND t.status = 'completed'
                     AND t.reference_type = 'loan' AND t.reference_id = l.id)
  AND NOT EXISTS (SELECT 1 FROM disbursement_tracking d
                   WHERE d.loan_id = l.id AND d.status = 'pending' AND d.processing_fee > 0)
ORDER BY l.id;

-- Check: every loan with a fee now has exactly ONE entry (both counts must be 0)
SELECT
  (SELECT COUNT(*) FROM loans l WHERE l.processing_fee > 0
     AND NOT EXISTS (SELECT 1 FROM member_other_transactions t WHERE t.transaction_type = 'processing_fee'
                      AND t.status = 'completed' AND t.reference_type = 'loan' AND t.reference_id = l.id)
     AND NOT EXISTS (SELECT 1 FROM disbursement_tracking d WHERE d.loan_id = l.id AND d.status = 'pending' AND d.processing_fee > 0)
  ) AS loans_still_missing,
  (SELECT COUNT(*) FROM (SELECT reference_id FROM member_other_transactions
                          WHERE transaction_type = 'processing_fee' AND status = 'completed' AND reference_type = 'loan'
                            AND reference_id IN (SELECT id FROM loans)
                          GROUP BY reference_id HAVING COUNT(*) > 1) x
  ) AS loans_with_duplicate_entries;

-- If both counts above are 0 → COMMIT. Otherwise → ROLLBACK and tell me the numbers.
COMMIT;


-- ── PART 2 (optional) · Leftover entries of DELETED loans ────────────────────────────
-- 14 entries (ids 10, 18, 26, 35, 38, 39, 41, 42, 43, 44, 52, 53, 281, 282) point to loans that were deleted
-- and re-created; each re-created loan already has its own fee entry, so these double the member's fees in
-- reports and statements. This marks them reversed (kept for audit, not deleted). Remove the "-- " to run.
--
-- SELECT t.id, t.member_id, t.amount, t.transaction_date, t.reference_id AS deleted_loan_id, t.description
-- FROM member_other_transactions t
-- WHERE t.transaction_type = 'processing_fee' AND t.status = 'completed' AND t.reference_type = 'loan'
--   AND t.reference_id NOT IN (SELECT id FROM loans);
--
-- UPDATE member_other_transactions
-- SET status = 'reversed', reversed_at = NOW(),
--     reversal_reason = 'Duplicate: fee entry of a deleted loan (the re-created loan has its own entry)'
-- WHERE transaction_type = 'processing_fee' AND status = 'completed' AND reference_type = 'loan'
--   AND reference_id NOT IN (SELECT id FROM loans);


-- ── PART 3 (optional) · Loans whose processing fee was never calculated ──────────────
-- 5 active loans have processing_fee = 0 and the member received the full amount (no fee withheld):
--   LN2026000129 ₹75,000 → 1.5% = ₹1,125      LN2026000130 ₹1,30,000 → ₹1,950
--   LN2026000131 ₹1,00,000 → ₹1,500           LN2026000175 ₹1,20,000 → ₹1,800
--   LN2026000231 ₹1,40,259 → ₹2,103.89
-- Recommended: when the member pays, use Reports → Processing Fee Check → "Charge fee" on that loan.
-- It sets the fee on the loan, adds the entry AND posts the ledger receipt (Dr Cash/Bank / Cr 3200).
--
-- Only if you want to record the fee as DUE now (no money received yet, no ledger entry), remove the "-- ":
--
-- SELECT l.id, l.loan_number, l.principal_amount,
--        CASE WHEN lp.processing_fee_type = 'fixed' THEN lp.processing_fee_value
--             ELSE ROUND(l.principal_amount * lp.processing_fee_value / 100, 2) END AS rule_fee
-- FROM loans l JOIN loan_products lp ON lp.id = l.loan_product_id
-- WHERE COALESCE(l.processing_fee, 0) = 0;
--
-- START TRANSACTION;
-- UPDATE loans l JOIN loan_products lp ON lp.id = l.loan_product_id
-- SET l.processing_fee = CASE WHEN lp.processing_fee_type = 'fixed' THEN lp.processing_fee_value
--                             ELSE ROUND(l.principal_amount * lp.processing_fee_value / 100, 2) END,
--     l.updated_at = NOW()
-- WHERE COALESCE(l.processing_fee, 0) = 0 AND lp.processing_fee_value > 0;
--
-- INSERT INTO member_other_transactions
--     (member_id, transaction_type, amount, transaction_date, description,
--      reference_type, reference_id, payment_mode, status, created_by, created_at)
-- SELECT l.member_id, 'processing_fee', l.processing_fee, CURDATE(),
--        CONCAT('Loan processing fee for Loan #', l.id, ' (', l.loan_number, ') — due, not yet collected'),
--        'loan', l.id, NULL, 'pending',
--        (SELECT a.id FROM admin_users a WHERE a.role = 'super_admin' ORDER BY a.id LIMIT 1), NOW()
-- FROM loans l
-- WHERE l.id IN (129, 130, 131, 175, 231)
--   AND NOT EXISTS (SELECT 1 FROM member_other_transactions t
--                    WHERE t.transaction_type = 'processing_fee' AND t.reference_type = 'loan' AND t.reference_id = l.id
--                      AND t.status IN ('completed', 'pending'));
-- COMMIT;
