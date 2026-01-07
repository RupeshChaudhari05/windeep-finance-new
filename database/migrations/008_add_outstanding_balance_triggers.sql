-- Migration 008: Add Triggers for Outstanding Balance Auto-Update
-- Bug #14 Fix: Remove redundancy, make outstanding balances auto-calculated
-- Date: January 6, 2026

USE windeep_finance;

-- ============================================
-- PART 1: Create triggers for loan outstanding
-- ============================================

DELIMITER $$

-- Trigger: Update loan outstanding when installment is paid
CREATE TRIGGER trg_loan_installment_after_update
AFTER UPDATE ON loan_installments
FOR EACH ROW
BEGIN
    DECLARE total_outstanding DECIMAL(15,2);
    DECLARE total_interest_outstanding DECIMAL(15,2);
    
    -- Calculate outstanding from installments
    SELECT 
        COALESCE(SUM(principal_amount - principal_paid), 0),
        COALESCE(SUM(interest_amount - interest_paid), 0)
    INTO total_outstanding, total_interest_outstanding
    FROM loan_installments
    WHERE loan_id = NEW.loan_id;
    
    -- Update loans table
    UPDATE loans SET
        outstanding_principal = total_outstanding,
        outstanding_interest = total_interest_outstanding,
        updated_at = NOW()
    WHERE id = NEW.loan_id;
END$$

-- Trigger: Update loan outstanding when installment is created
CREATE TRIGGER trg_loan_installment_after_insert
AFTER INSERT ON loan_installments
FOR EACH ROW
BEGIN
    DECLARE total_outstanding DECIMAL(15,2);
    DECLARE total_interest_outstanding DECIMAL(15,2);
    
    -- Calculate outstanding from installments
    SELECT 
        COALESCE(SUM(principal_amount - principal_paid), 0),
        COALESCE(SUM(interest_amount - interest_paid), 0)
    INTO total_outstanding, total_interest_outstanding
    FROM loan_installments
    WHERE loan_id = NEW.loan_id;
    
    -- Update loans table
    UPDATE loans SET
        outstanding_principal = total_outstanding,
        outstanding_interest = total_interest_outstanding,
        updated_at = NOW()
    WHERE id = NEW.loan_id;
END$$

DELIMITER;

-- ============================================
-- PART 2: Add stored procedure to recalculate outstanding for all loans
-- ============================================

DELIMITER $$

CREATE PROCEDURE sp_recalculate_loan_outstanding()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_loan_id INT;
    DECLARE v_outstanding DECIMAL(15,2);
    DECLARE v_interest_outstanding DECIMAL(15,2);
    
    DECLARE loan_cursor CURSOR FOR SELECT id FROM loans WHERE status IN ('active', 'overdue');
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN loan_cursor;
    
    read_loop: LOOP
        FETCH loan_cursor INTO v_loan_id;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Calculate from installments
        SELECT 
            COALESCE(SUM(principal_amount - principal_paid), 0),
            COALESCE(SUM(interest_amount - interest_paid), 0)
        INTO v_outstanding, v_interest_outstanding
        FROM loan_installments
        WHERE loan_id = v_loan_id;
        
        -- Update loan record
        UPDATE loans SET
            outstanding_principal = v_outstanding,
            outstanding_interest = v_interest_outstanding,
            updated_at = NOW()
        WHERE id = v_loan_id;
    END LOOP;
    
    CLOSE loan_cursor;
END$$

DELIMITER;

-- ============================================
-- PART 3: Run one-time fix for existing data
-- ============================================

-- Recalculate all active/overdue loans
CALL sp_recalculate_loan_outstanding ();

-- Verify results
SELECT
    l.id,
    l.loan_number,
    l.outstanding_principal AS stored_outstanding,
    COALESCE(
        SUM(
            li.principal_amount - li.principal_paid
        ),
        0
    ) AS calculated_outstanding,
    (
        l.outstanding_principal - COALESCE(
            SUM(
                li.principal_amount - li.principal_paid
            ),
            0
        )
    ) AS difference
FROM loans l
    LEFT JOIN loan_installments li ON li.loan_id = l.id
WHERE
    l.status IN ('active', 'overdue')
GROUP BY
    l.id
HAVING
    ABS(difference) > 0.01
ORDER BY ABS(difference) DESC
LIMIT 10;

-- ============================================
-- PART 4: Add function to get loan outstanding (for queries)
-- ============================================

DELIMITER $$

CREATE FUNCTION fn_get_loan_outstanding(p_loan_id INT)
RETURNS DECIMAL(15,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_outstanding DECIMAL(15,2);
    
    SELECT COALESCE(SUM(principal_amount - principal_paid), 0)
    INTO v_outstanding
    FROM loan_installments
    WHERE loan_id = p_loan_id;
    
    RETURN v_outstanding;
END$$

CREATE FUNCTION fn_get_loan_interest_outstanding(p_loan_id INT)
RETURNS DECIMAL(15,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_outstanding DECIMAL(15,2);
    
    SELECT COALESCE(SUM(interest_amount - interest_paid), 0)
    INTO v_outstanding
    FROM loan_installments
    WHERE loan_id = p_loan_id;
    
    RETURN v_outstanding;
END$$

DELIMITER;

-- ============================================
-- USAGE NOTES
-- ============================================

/*
1. After running this migration:
- Triggers will automatically update outstanding_principal and outstanding_interest in loans table
- No need to manually update these fields in application code

2. To manually recalculate for all loans:
CALL sp_recalculate_loan_outstanding();

3. To get outstanding for a specific loan (in queries):
SELECT fn_get_loan_outstanding(loan_id) AS outstanding;

4. To verify data integrity:
SELECT * FROM loans WHERE ABS(outstanding_principal - fn_get_loan_outstanding(id)) > 0.01;
*/

-- ============================================
-- ROLLBACK SCRIPT (if needed)
-- ============================================

/*
DROP TRIGGER IF EXISTS trg_loan_installment_after_update;
DROP TRIGGER IF EXISTS trg_loan_installment_after_insert;
DROP PROCEDURE IF EXISTS sp_recalculate_loan_outstanding;
DROP FUNCTION IF EXISTS fn_get_loan_outstanding;
DROP FUNCTION IF EXISTS fn_get_loan_interest_outstanding;
*/