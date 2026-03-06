<?php
/**
 * One-time script to verify and fix bonus_transactions table
 * Run: php db_check_bonus.php
 * Delete after use.
 */
define('BASEPATH', __DIR__ . '/system/');
define('ENVIRONMENT', 'development');

// Load env helper
require_once 'application/helpers/env_helper.php';
require 'application/config/database.php';
$c = $db['default'];
$conn = new mysqli($c['hostname'], $c['username'], $c['password'], $c['database']);
if ($conn->connect_error) {
    die('CONNECT FAIL: ' . $conn->connect_error . PHP_EOL);
}

echo "=== Bonus Transactions Table Check ===" . PHP_EOL;

// 1. Check if table exists
$r = $conn->query("SHOW TABLES LIKE 'bonus_transactions'");
$exists = $r->num_rows > 0;
echo "Table exists: " . ($exists ? 'YES' : 'NO') . PHP_EOL;

if (!$exists) {
    echo "Creating bonus_transactions table..." . PHP_EOL;
    $sql = "CREATE TABLE IF NOT EXISTS `bonus_transactions` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `member_id` INT UNSIGNED NOT NULL,
        `savings_account_id` INT UNSIGNED DEFAULT NULL,
        `bonus_year` YEAR NOT NULL,
        `amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
        `description` VARCHAR(500) DEFAULT NULL,
        `status` ENUM('pending', 'credited', 'reversed') NOT NULL DEFAULT 'credited',
        `savings_transaction_id` INT UNSIGNED DEFAULT NULL,
        `created_by` INT UNSIGNED DEFAULT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_bonus_member` (`member_id`),
        INDEX `idx_bonus_year` (`bonus_year`),
        INDEX `idx_bonus_status` (`status`),
        UNIQUE KEY `uk_member_year` (`member_id`, `bonus_year`),
        FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;";
    if ($conn->query($sql)) {
        echo "Table created successfully." . PHP_EOL;
    } else {
        echo "Error creating table: " . $conn->error . PHP_EOL;
    }
} else {
    // 2. Check if unique constraint exists
    $idx = $conn->query("SHOW INDEX FROM `bonus_transactions` WHERE Key_name = 'uk_member_year'");
    if ($idx->num_rows == 0) {
        echo "Adding unique constraint (member_id, bonus_year)..." . PHP_EOL;
        if ($conn->query("ALTER TABLE `bonus_transactions` ADD UNIQUE KEY `uk_member_year` (`member_id`, `bonus_year`)")) {
            echo "Unique constraint added." . PHP_EOL;
        } else {
            echo "Error (may have duplicates): " . $conn->error . PHP_EOL;
        }
    } else {
        echo "Unique constraint uk_member_year already exists." . PHP_EOL;
    }
}

// 3. Show current data
$r2 = $conn->query("SELECT COUNT(*) as cnt FROM `bonus_transactions`");
$row = $r2->fetch_assoc();
echo "Current bonus_transactions rows: " . $row['cnt'] . PHP_EOL;

// 4. Check for orphaned bonus deposits in savings_transactions (deposits with narration like 'Bonus for year%' but no matching bonus_transactions record)
$orphans = $conn->query("
    SELECT st.id, st.savings_account_id, st.amount, st.narration, st.transaction_date, st.created_at,
           sa.member_id
    FROM savings_transactions st
    JOIN savings_accounts sa ON sa.id = st.savings_account_id
    WHERE st.narration LIKE 'Bonus for year%'
    AND st.transaction_type = 'deposit'
    AND st.id NOT IN (SELECT COALESCE(savings_transaction_id, 0) FROM bonus_transactions)
    ORDER BY st.created_at DESC
");

if ($orphans && $orphans->num_rows > 0) {
    echo PHP_EOL . "=== ORPHANED BONUS DEPOSITS FOUND ===" . PHP_EOL;
    echo "These deposits were made but bonus_transactions record was NOT created (due to the bug)." . PHP_EOL;
    echo str_repeat('-', 90) . PHP_EOL;
    printf("%-8s %-12s %-12s %-10s %-40s %s\n", "Txn ID", "Member ID", "SA ID", "Amount", "Narration", "Date");
    echo str_repeat('-', 90) . PHP_EOL;
    
    $fix_count = 0;
    while ($o = $orphans->fetch_assoc()) {
        printf("%-8s %-12s %-12s %-10s %-40s %s\n", 
            $o['id'], $o['member_id'], $o['savings_account_id'], 
            number_format($o['amount'], 2), substr($o['narration'], 0, 40), $o['created_at']);
        
        // Extract bonus_year from narration
        if (preg_match('/Bonus for year (\d{4})/', $o['narration'], $m)) {
            $bonus_year = $m[1];
            
            // Check if a bonus_transactions row already exists for this member+year
            $chk = $conn->query("SELECT id FROM bonus_transactions WHERE member_id = {$o['member_id']} AND bonus_year = {$bonus_year}");
            if ($chk->num_rows == 0) {
                $stmt = $conn->prepare("INSERT INTO bonus_transactions (member_id, savings_account_id, bonus_year, amount, description, status, savings_transaction_id, created_at) VALUES (?, ?, ?, ?, 'Auto-recovered from orphan deposit', 'credited', ?, ?)");
                $stmt->bind_param('iisdis', $o['member_id'], $o['savings_account_id'], $bonus_year, $o['amount'], $o['id'], $o['created_at']);
                if ($stmt->execute()) {
                    $fix_count++;
                    echo "  -> FIXED: Created bonus_transactions record" . PHP_EOL;
                } else {
                    echo "  -> ERROR: " . $stmt->error . PHP_EOL;
                }
                $stmt->close();
            } else {
                echo "  -> SKIP: bonus_transactions record already exists for year " . $bonus_year . PHP_EOL;
            }
        }
    }
    echo PHP_EOL . "Fixed $fix_count orphaned records." . PHP_EOL;
} else {
    echo PHP_EOL . "No orphaned bonus deposits found. All clean." . PHP_EOL;
}

// Final count
$r3 = $conn->query("SELECT COUNT(*) as cnt FROM bonus_transactions");
$row3 = $r3->fetch_assoc();
echo PHP_EOL . "Final bonus_transactions rows: " . $row3['cnt'] . PHP_EOL;

$conn->close();
echo PHP_EOL . "=== Done. Delete this file after use. ===" . PHP_EOL;
