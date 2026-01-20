<?php
if (!defined('BASEPATH')) define('BASEPATH', true);
if (!defined('ENVIRONMENT')) define('ENVIRONMENT', 'development');
if (!function_exists('env')) { function env($k,$d=null){$v=getenv($k);return $v===false?$d:$v;} }
require __DIR__ . '/../application/config/database.php';
$c=$db['default'];
$mysqli=new mysqli($c['hostname'],$c['username'],$c['password'],$c['database']);
if($mysqli->connect_errno){echo "DB connect failed: {$mysqli->connect_error}\n"; exit(1);} 

// Create bank_statement_imports if not exists
$sql1 = <<<SQL
CREATE TABLE IF NOT EXISTS `bank_statement_imports` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `import_code` VARCHAR(30) NOT NULL,
    `bank_account_id` INT UNSIGNED NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_type` ENUM('csv', 'excel', 'pdf') NOT NULL,
    `statement_from_date` DATE,
    `statement_to_date` DATE,
    `total_transactions` INT DEFAULT 0,
    `total_credits` DECIMAL(15, 2) DEFAULT 0.00,
    `total_debits` DECIMAL(15, 2) DEFAULT 0.00,
    `mapped_count` INT DEFAULT 0,
    `unmapped_count` INT DEFAULT 0,
    `status` ENUM('uploaded','parsing','parsed','mapping','completed','failed') DEFAULT 'uploaded',
    `error_message` TEXT,
    `imported_by` INT UNSIGNED,
    `imported_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `completed_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_import_code` (`import_code`),
    KEY `idx_bank_account_id` (`bank_account_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
SQL;

if($mysqli->query($sql1) === TRUE) echo "bank_statement_imports ensured\n"; else echo "ERROR creating bank_statement_imports: " . $mysqli->error . "\n";

// Create bank_transactions if not exists
$sql2 = <<<SQL
CREATE TABLE IF NOT EXISTS `bank_transactions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `import_id` INT UNSIGNED NOT NULL,
    `bank_account_id` INT UNSIGNED NOT NULL,
    `transaction_date` DATE NOT NULL,
    `value_date` DATE,
    `transaction_type` ENUM('credit','debit') NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `balance_after` DECIMAL(15,2),
    `description` TEXT,
    `reference_number` VARCHAR(100),
    `utr_number` VARCHAR(50),
    `cheque_number` VARCHAR(20),
    `mapping_status` ENUM('unmapped','partial','mapped','ignored','split') DEFAULT 'unmapped',
    `mapped_amount` DECIMAL(15, 2) DEFAULT 0.00,
    `unmapped_amount` DECIMAL(15, 2),
    `detected_member_id` INT UNSIGNED,
    `detection_confidence` DECIMAL(5, 2),
    `paid_by_member_id` INT UNSIGNED,
    `paid_for_member_id` INT UNSIGNED,
    `updated_by` INT UNSIGNED,
    `remarks` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_import_id` (`import_id`),
    KEY `idx_transaction_date` (`transaction_date`),
    KEY `idx_mapping_status` (`mapping_status`),
    KEY `idx_utr_number` (`utr_number`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
SQL;

if($mysqli->query($sql2) === TRUE) echo "bank_transactions ensured\n"; else echo "ERROR creating bank_transactions: " . $mysqli->error . "\n";

$mysqli->close();
