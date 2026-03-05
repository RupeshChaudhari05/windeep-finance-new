-- ============================================================
-- Migration 008: Loan Part Payments (Partial Prepayment)
-- Date: 2026-03-04
-- Description: Adds loan_part_payments audit table for tracking
--              part payment history with full audit trail.
-- ============================================================

-- -----------------------------------------------------------
-- Table: loan_part_payments
-- Full audit trail for every part payment / partial prepayment
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `loan_part_payments` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `loan_id` int(10) unsigned NOT NULL,
    `payment_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to loan_payments after processing',
    `part_payment_amount` decimal(15, 2) NOT NULL,
    `prepayment_penalty_percent` decimal(5, 2) DEFAULT 0.00,
    `prepayment_penalty_amount` decimal(15, 2) DEFAULT 0.00,
    `previous_principal` decimal(15, 2) NOT NULL,
    `new_principal` decimal(15, 2) NOT NULL,
    `previous_emi` decimal(15, 2) NOT NULL,
    `new_emi` decimal(15, 2) NOT NULL,
    `previous_tenure` int(11) NOT NULL,
    `new_tenure` int(11) NOT NULL,
    `previous_total_interest` decimal(15, 2) DEFAULT 0.00,
    `new_total_interest` decimal(15, 2) DEFAULT 0.00,
    `interest_savings` decimal(15, 2) DEFAULT 0.00 COMMENT 'How much interest the member saves',
    `interest_rate` decimal(5, 2) NOT NULL COMMENT 'Rate at time of part payment',
    `interest_type` varchar(20) NOT NULL DEFAULT 'reducing',
    `adjustment_type` enum(
        'reduce_emi',
        'reduce_tenure',
        'manual'
    ) NOT NULL,
    `manual_emi_input` decimal(15, 2) DEFAULT NULL COMMENT 'If manual, admin-entered EMI',
    `manual_tenure_input` int(11) DEFAULT NULL COMMENT 'If manual, admin-entered tenure',
    `payment_mode` enum(
        'cash',
        'bank_transfer',
        'cheque',
        'upi'
    ) DEFAULT 'cash',
    `payment_reference` varchar(100) DEFAULT NULL,
    `payment_date` date NOT NULL,
    `remarks` text DEFAULT NULL,
    `status` enum(
        'pending',
        'approved',
        'rejected',
        'reversed'
    ) NOT NULL DEFAULT 'approved',
    `approved_by` int(10) unsigned DEFAULT NULL,
    `approved_at` timestamp NULL DEFAULT NULL,
    `rejected_by` int(10) unsigned DEFAULT NULL,
    `rejected_at` timestamp NULL DEFAULT NULL,
    `rejection_reason` varchar(255) DEFAULT NULL,
    `is_reversed` tinyint(1) DEFAULT 0,
    `reversed_by` int(10) unsigned DEFAULT NULL,
    `reversed_at` timestamp NULL DEFAULT NULL,
    `reversal_reason` varchar(255) DEFAULT NULL,
    `created_by` int(10) unsigned DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_lpp_loan_id` (`loan_id`),
    KEY `idx_lpp_payment_id` (`payment_id`),
    KEY `idx_lpp_status` (`status`),
    KEY `idx_lpp_payment_date` (`payment_date`),
    KEY `idx_lpp_adjustment_type` (`adjustment_type`),
    CONSTRAINT `fk_lpp_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`),
    CONSTRAINT `fk_lpp_payment` FOREIGN KEY (`payment_id`) REFERENCES `loan_payments` (`id`) ON DELETE SET NULL,
    CONSTRAINT `chk_lpp_amounts` CHECK (
        `part_payment_amount` > 0
        AND `new_principal` >= 0
        AND `new_emi` >= 0
        AND `new_tenure` >= 1
    )
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Audit trail for loan part payments / partial prepayments';

-- Track migration
INSERT IGNORE INTO
    `schema_migrations` (`filename`)
VALUES (
        '008_add_loan_part_payments.sql'
    );