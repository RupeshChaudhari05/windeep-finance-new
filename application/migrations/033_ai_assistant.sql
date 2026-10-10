-- ============================================================
-- Migration: AI Assistant chat log
-- Date: 2026-10-10
-- Purpose: Keeps every admin <-> AI exchange (message, raw model reply,
--          proposed actions) for audit. Confirmed entries are also in
--          audit_logs with remark 'AI assistant'.
-- Provider settings (ai_provider, ai_local_url, ai_local_model,
-- ai_openai_model, ai_openai_key, ai_gemini_model, ai_gemini_key) are
-- stored in system_settings from the AI Settings dialog, or .env (AI_*).
-- ============================================================

CREATE TABLE IF NOT EXISTS `ai_chat_logs` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `admin_id`   INT UNSIGNED NOT NULL,
    `message`    TEXT NULL,
    `response`   MEDIUMTEXT NULL,
    `actions`    MEDIUMTEXT NULL,
    `provider`   VARCHAR(20) NULL,
    `model`      VARCHAR(80) NULL,
    `tokens_in`  INT UNSIGNED NOT NULL DEFAULT 0,
    `tokens_out` INT UNSIGNED NOT NULL DEFAULT 0,
    `cost_usd`   DECIMAL(12,6) NOT NULL DEFAULT 0 COMMENT 'Estimated from list price, used for the monthly cap',
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_admin_created` (`admin_id`, `created_at`),
    KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- If an earlier version of this table exists, add the usage columns
ALTER TABLE `ai_chat_logs`
    ADD COLUMN IF NOT EXISTS `provider`   VARCHAR(20) NULL AFTER `actions`,
    ADD COLUMN IF NOT EXISTS `model`      VARCHAR(80) NULL AFTER `provider`,
    ADD COLUMN IF NOT EXISTS `tokens_in`  INT UNSIGNED NOT NULL DEFAULT 0 AFTER `model`,
    ADD COLUMN IF NOT EXISTS `tokens_out` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `tokens_in`,
    ADD COLUMN IF NOT EXISTS `cost_usd`   DECIMAL(12,6) NOT NULL DEFAULT 0 AFTER `tokens_out`;

INSERT INTO `schema_migrations` (`filename`, `applied_at`)
VALUES ('033_ai_assistant.sql', NOW())
ON DUPLICATE KEY UPDATE `applied_at` = NOW();
