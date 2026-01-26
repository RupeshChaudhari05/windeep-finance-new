-- Migration: Add email configuration settings
-- Date: 2026-01-26
-- Description: Adds email settings to system_settings table

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `is_editable`) VALUES
('mail_driver', 'smtp', 'string', 'Email driver (smtp)', 1),
('mail_host', 'smtp.gmail.com', 'string', 'SMTP host server', 1),
('mail_port', '587', 'number', 'SMTP port (587 for TLS, 465 for SSL)', 1),
('mail_username', '', 'string', 'SMTP username/email', 1),
('mail_password', '', 'string', 'SMTP password', 1),
('mail_encryption', 'tls', 'string', 'Email encryption (tls or ssl)', 1),
('mail_from_address', 'noreply@windeepfinance.com', 'string', 'From email address', 1),
('mail_from_name', 'Windeep Finance', 'string', 'From name', 1)
ON DUPLICATE KEY UPDATE 
    `description` = VALUES(`description`),
    `is_editable` = VALUES(`is_editable`);
