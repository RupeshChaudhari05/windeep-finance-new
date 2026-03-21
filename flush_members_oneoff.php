<?php
/**
 * One-off Member Data Flush Script
 * Access via browser — NO file writes needed, works on all hosting.
 * DELETE THIS FILE after use.
 *
 * How it works:
 *   1. Open the URL — a unique token is shown on the page.
 *   2. Click the link or copy the token into the form.
 *   3. Do a Dry Run first, then Flush All if correct.
 */

// ── Token: derived from server path (deterministic, no file write needed) ──
define('ROOT', __DIR__);
$_secret = hash('sha256', __DIR__ . '|windeep_flush_2026|' . ($_SERVER['SERVER_NAME'] ?? ''));

// ── Authenticate ────────────────────────────────────────────────────────────
$provided = $_POST['secret'] ?? $_GET['secret'] ?? '';
$authed   = !empty($provided) && hash_equals($_secret, $provided);

// ── Load DB credentials from .env or CI config ─────────────────────────────
function _fm_load_db() {
    $cfg = ['host' => 'localhost', 'user' => 'root', 'pass' => '', 'name' => ''];
    $env = ROOT . '/.env';
    if (file_exists($env)) {
        foreach (file($env, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if ($line[0] === '#' || strpos($line, '=') === false) continue;
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k); $v = trim(trim($v), '"\'');
            if ($k === 'DB_HOST')     $cfg['host'] = $v;
            if ($k === 'DB_USERNAME') $cfg['user'] = $v;
            if ($k === 'DB_PASSWORD') $cfg['pass'] = $v;
            if ($k === 'DB_NAME')     $cfg['name'] = $v;
        }
    }
    if (empty($cfg['name'])) {
        $f = ROOT . '/application/config/database.php';
        if (file_exists($f)) {
            $c = file_get_contents($f);
            preg_match("/'hostname'\s*=>\s*env\s*\([^,]+,\s*'([^']+)'\)/", $c, $m); if ($m) $cfg['host'] = $m[1];
            preg_match("/'username'\s*=>\s*env\s*\([^,]+,\s*'([^']+)'\)/", $c, $m); if ($m) $cfg['user'] = $m[1];
            preg_match("/'password'\s*=>\s*env\s*\([^,]+,\s*'([^']+)'\)/", $c, $m); if ($m) $cfg['pass'] = $m[1];
            preg_match("/'database'\s*=>\s*env\s*\([^,]+,\s*'([^']+)'\)/", $c, $m); if ($m) $cfg['name'] = $m[1];
        }
    }
    return $cfg;
}

// ── Perform flush ──────────────────────────────────────────────────────────
function _fm_flush($dry = true) {
    $cfg = _fm_load_db();
    if (empty($cfg['name'])) return ['error' => 'Could not read DB config. Check .env or application/config/database.php'];

    try {
        $dsn = "mysql:host={$cfg['host']};dbname={$cfg['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    } catch (PDOException $e) {
        return ['error' => 'DB connection failed: ' . $e->getMessage()];
    }

    // Flush order: children first, parents last.
    // Preserved (NOT touched): loan_products, savings_schemes, bank_accounts,
    //   settings, admin_users, financial_years, loan_fine_rules, loan_fine_settings
    $tables = [
        'loan_installments', 'loan_payments', 'loan_guarantors',
        'loans', 'loan_applications',
        'savings_transactions', 'savings_schedule', 'savings_accounts',
        'fines', 'member_other_transactions', 'bonus_transactions',
        'notifications',
        'bank_transactions', 'bank_statement_imports', 'bank_imports',
        'non_member_funds', 'non_members',
        'gl_entries', 'audit_log',
        'members',
    ];

    $existing = array_flip($pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN));
    $log = [];

    if (!$dry) $pdo->exec("SET FOREIGN_KEY_CHECKS=0");

    foreach ($tables as $t) {
        if (!isset($existing[$t])) {
            $log[] = ['skip', $t, 'table does not exist']; continue;
        }
        try {
            if ($dry) {
                $cnt = $pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
                $log[] = ['dry', $t, $cnt . ' rows would be deleted'];
            } else {
                $cnt = $pdo->exec("DELETE FROM `{$t}`");
                $log[] = ['ok', $t, $cnt . ' rows deleted'];
            }
        } catch (PDOException $e) {
            $log[] = ['err', $t, $e->getMessage()];
        }
    }

    if (!$dry) $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

    // Remove member upload directories
    $up = ROOT . '/members/uploads/';
    if (is_dir($up)) {
        foreach (glob($up . '*', GLOB_ONLYDIR) as $d) {
            if ($dry) {
                $log[] = ['dry', 'dir', $d . ' would be removed'];
            } else {
                array_map('unlink', glob($d . '/*'));
                @rmdir($d);
                $log[] = ['ok', 'dir', $d . ' removed'];
            }
        }
    }

    return ['log' => $log, 'dry' => $dry];
}

// ── Output ──────────────────────────────────────────────────────────────────
$result = null;
if ($authed) {
    $action = $_POST['action'] ?? '';
    if ($action === 'dry')   $result = _fm_flush(true);
    if ($action === 'flush') $result = _fm_flush(false);
}

$proto    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base_url = $proto . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $_SERVER['REQUEST_URI'];
$base_url = strtok($base_url, '?');
$auth_url = $base_url . '?secret=' . urlencode($_secret);

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Flush Members</title>
<style>
body{font-family:Arial,sans-serif;max-width:820px;margin:36px auto;padding:0 16px;background:#f4f6f9;}
h2{color:#c0392b;margin-top:0;}
.card{background:#fff;border-radius:6px;padding:22px;box-shadow:0 2px 8px rgba(0,0,0,.08);margin-bottom:18px;}
.btn{display:inline-block;padding:10px 24px;border-radius:4px;cursor:pointer;font-size:14px;border:none;font-weight:600;}
.btn-info{background:#2980b9;color:#fff;} .btn-danger{background:#c0392b;color:#fff;} .btn-warn{background:#e67e22;color:#fff;}
.btn:hover{opacity:.88;}
pre{background:#111;color:#eee;padding:14px;border-radius:4px;max-height:500px;overflow:auto;line-height:1.7;font-size:13px;}
code{background:#eee;color:#333;padding:2px 6px;border-radius:3px;word-break:break-all;}
.alert{padding:12px 16px;border-radius:4px;margin-bottom:12px;}
.alert-warn{background:#fef9e7;border:1px solid #f1c40f;}
.alert-danger{background:#fdecea;border:1px solid #e74c3c;color:#c0392b;}
.alert-success{background:#eafaf1;border:1px solid #2ecc71;}
.lbl-skip{color:#aaa;} .lbl-dry{color:#e67e22;font-weight:bold;}
.lbl-ok{color:#27ae60;font-weight:bold;} .lbl-err{color:#c0392b;font-weight:bold;}
input[type=text]{width:100%;box-sizing:border-box;padding:8px 10px;font-size:14px;border:1px solid #ccc;border-radius:4px;}
</style>
</head>
<body>
<div class="card">
  <h2>⚠ Member Data Flush — One-Off</h2>
  <p>Deletes <strong>all member &amp; transaction data</strong>. Preserved: loan products, savings schemes, bank accounts, admin users, settings.</p>
  <p style="color:#c0392b;font-weight:bold;">⚠ DELETE THIS FILE after use.</p>

<?php if (!$authed): ?>
  <div class="alert alert-warn">
    <strong>Step 1 — Authenticate</strong><br><br>
    Your session token (auto-generated, no file write needed):<br>
    <code><?= htmlspecialchars($_secret) ?></code>
    <br><br>
    Direct authenticated link (click or copy):<br>
    <a href="<?= htmlspecialchars($auth_url) ?>"><code><?= htmlspecialchars($auth_url) ?></code></a>
    <br><br>
    Or paste the token below:
    <form method="post" style="margin-top:10px;">
      <input type="text" name="secret" placeholder="Paste token here" autofocus>
      <input type="hidden" name="action" value="">
      <br><br>
      <button type="submit" class="btn btn-info">🔑 Authenticate</button>
    </form>
  </div>

<?php else: ?>
  <p style="color:green;margin:0 0 14px;">✔ Authenticated.</p>

  <?php if ($result && isset($result['error'])): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($result['error']) ?></div>
  <?php endif; ?>

  <?php if ($result && isset($result['log'])): ?>
  <div class="alert <?= $result['dry'] ? 'alert-warn' : 'alert-success' ?>">
    <?= $result['dry'] ? '🔍 Dry Run Complete — no changes made.' : '✅ Flush complete.' ?>
  </div>
  <pre><?php foreach ($result['log'] as [$s, $t, $msg]):
    $cl = ['skip'=>'lbl-skip','dry'=>'lbl-dry','ok'=>'lbl-ok','err'=>'lbl-err'][$s] ?? '';
    echo "<span class='{$cl}'>" . strtoupper($s) . "</span>  " . htmlspecialchars($t) . "  —  " . htmlspecialchars($msg) . "\n";
  endforeach; ?></pre>
  <?php endif; ?>

  <form method="post" onsubmit="return confirm('Run DRY RUN? No data will be deleted.')">
    <input type="hidden" name="secret" value="<?= htmlspecialchars($_secret) ?>">
    <input type="hidden" name="action" value="dry">
    <button type="submit" class="btn btn-warn">🔍 Dry Run (preview)</button>
  </form>

  <br>
  <form method="post" onsubmit="return confirm('⚠ PERMANENTLY DELETE all member data? This cannot be undone!\n\nClick OK only if you are sure.')">
    <input type="hidden" name="secret" value="<?= htmlspecialchars($_secret) ?>">
    <input type="hidden" name="action" value="flush">
    <button type="submit" class="btn btn-danger">🗑 Flush All Member Data</button>
  </form>

<?php endif; ?>
</div>
</body>
</html>

// ── Paths ──────────────────────────────────────────────────────────────────
define('ROOT', __DIR__);
$secret_file = ROOT . DIRECTORY_SEPARATOR . 'flush_secret.txt';

// ── Auto-generate secret on first visit ────────────────────────────────────
if (!file_exists($secret_file)) {
    $token = bin2hex(random_bytes(20));
    file_put_contents($secret_file, $token);
}
$expected_secret = trim(file_get_contents($secret_file));

// ── Authenticate ────────────────────────────────────────────────────────────
$provided = $_REQUEST['secret'] ?? '';
$authed   = !empty($provided) && hash_equals($expected_secret, $provided);

// ── Load DB credentials from .env or CI config ─────────────────────────────
function load_db_config() {
    $env_file = ROOT . DIRECTORY_SEPARATOR . '.env';
    $cfg = ['host' => 'localhost', 'user' => 'root', 'pass' => '', 'name' => ''];

    if (file_exists($env_file)) {
        foreach (file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k); $v = trim(trim($v), '"\'');
            if ($k === 'DB_HOST')     $cfg['host'] = $v;
            if ($k === 'DB_USERNAME') $cfg['user'] = $v;
            if ($k === 'DB_PASSWORD') $cfg['pass'] = $v;
            if ($k === 'DB_NAME')     $cfg['name'] = $v;
        }
    }

    // Fall back to CI database.php if .env not found / incomplete
    if (empty($cfg['name'])) {
        $db_php = ROOT . '/application/config/database.php';
        if (file_exists($db_php)) {
            $content = file_get_contents($db_php);
            preg_match("/'hostname'\s*=>\s*env\([^,]+,\s*'([^']+)'\)/", $content, $m); if ($m) $cfg['host'] = $m[1];
            preg_match("/'username'\s*=>\s*env\([^,]+,\s*'([^']+)'\)/", $content, $m); if ($m) $cfg['user'] = $m[1];
            preg_match("/'password'\s*=>\s*env\([^,]+,\s*'([^']+)'\)/", $content, $m); if ($m) $cfg['pass'] = $m[1];
            preg_match("/'database'\s*=>\s*env\([^,]+,\s*'([^']+)'\)/", $content, $m); if ($m) $cfg['name'] = $m[1];
        }
    }
    return $cfg;
}

// ── Perform flush ──────────────────────────────────────────────────────────
function do_flush($dry = true) {
    $cfg = load_db_config();
    if (empty($cfg['name'])) return ['error' => 'Could not read DB config.'];

    try {
        $dsn = "mysql:host={$cfg['host']};dbname={$cfg['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    } catch (PDOException $e) {
        return ['error' => 'DB connection failed: ' . $e->getMessage()];
    }

    // Tables to flush in dependency order (child → parent)
    // Mandatory tables kept intact: loan_products, savings_schemes, bank_accounts,
    //   settings, admin_users, financial_years, loan_fine_rules, loan_fine_settings
    $steps = [
        // Loan children
        ['table' => 'loan_installments',     'where' => null],
        ['table' => 'loan_payments',         'where' => null],
        ['table' => 'loan_guarantors',       'where' => null],
        // Loans + applications
        ['table' => 'loans',                 'where' => null],
        ['table' => 'loan_applications',     'where' => null],
        // Savings children
        ['table' => 'savings_transactions',  'where' => null],
        ['table' => 'savings_schedule',      'where' => null],
        ['table' => 'savings_accounts',      'where' => null],
        // Fines / bonuses / other charges
        ['table' => 'fines',                 'where' => null],
        ['table' => 'member_other_transactions', 'where' => null],
        ['table' => 'bonus_transactions',    'where' => null],
        // Notifications
        ['table' => 'notifications',         'where' => null],
        // Bank statement rows & imports
        ['table' => 'bank_transactions',     'where' => null],
        ['table' => 'bank_statement_imports','where' => null],
        ['table' => 'bank_imports',          'where' => null],
        // Non-member fund providers
        ['table' => 'non_member_funds',      'where' => null],
        ['table' => 'non_members',           'where' => null],
        // General ledger / audit
        ['table' => 'gl_entries',            'where' => null],
        ['table' => 'audit_log',             'where' => null],
        // Members last
        ['table' => 'members',               'where' => null],
    ];

    $log = [];
    // Check which tables actually exist
    $existing = [];
    foreach ($pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN) as $t) {
        $existing[$t] = true;
    }

    // Disable FK checks for the duration
    if (!$dry) $pdo->exec("SET FOREIGN_KEY_CHECKS=0");

    foreach ($steps as $step) {
        $t = $step['table'];
        if (!isset($existing[$t])) {
            $log[] = "<span class='text-muted'>SKIP</span> <code>{$t}</code> (table does not exist)";
            continue;
        }
        try {
            if ($dry) {
                $cnt = $pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
                $log[] = "<span class='text-warning'>[DRY]</span> Would delete <strong>{$cnt}</strong> rows from <code>{$t}</code>";
            } else {
                $cnt = $pdo->exec("DELETE FROM `{$t}`");
                $log[] = "<span class='text-success'>DELETED</span> <strong>{$cnt}</strong> rows from <code>{$t}</code>";
            }
        } catch (PDOException $e) {
            $log[] = "<span class='text-danger'>ERROR</span> <code>{$t}</code>: " . htmlspecialchars($e->getMessage());
        }
    }

    // Also remove member upload directories
    $uploads_base = ROOT . '/members/uploads/';
    if (is_dir($uploads_base)) {
        $dirs = glob($uploads_base . '*', GLOB_ONLYDIR);
        foreach ($dirs as $d) {
            if ($dry) {
                $log[] = "<span class='text-warning'>[DRY]</span> Would remove directory: <code>" . htmlspecialchars($d) . "</code>";
            } else {
                array_map('unlink', glob($d . '/*.*'));
                @rmdir($d);
                $log[] = "<span class='text-info'>REMOVED</span> directory <code>" . htmlspecialchars($d) . "</code>";
            }
        }
    }

    if (!$dry) $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

    return ['log' => $log, 'dry' => $dry];
}

// ── HTML ────────────────────────────────────────────────────────────────────
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Flush Members – One-Off</title>
<style>
  body{font-family:sans-serif;max-width:860px;margin:40px auto;padding:0 16px;background:#f4f6f9;}
  h2{color:#c0392b;}
  .box{background:#fff;border-radius:6px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,.1);margin-bottom:20px;}
  .btn{display:inline-block;padding:10px 22px;border-radius:4px;cursor:pointer;font-size:15px;border:none;}
  .btn-danger{background:#c0392b;color:#fff;} .btn-warning{background:#e67e22;color:#fff;}
  .btn-info{background:#2980b9;color:#fff;}
  pre{background:#111;color:#eee;padding:14px;border-radius:4px;max-height:480px;overflow:auto;line-height:1.6;}
  code{background:#eee;padding:2px 5px;border-radius:3px;}
  .warn{color:#c0392b;font-weight:bold;}
  .secret-box{background:#fef9e7;border:1px solid #f1c40f;padding:14px;border-radius:4px;}
</style>
</head>
<body>
<div class="box">
  <h2>⚠ Member Data Flush – One-Off Script</h2>
  <p>This script deletes <strong>all member and transaction data</strong> while preserving mandatory config (loan products, savings schemes, bank accounts, admin users, settings).</p>
  <p class="warn">DELETE THIS FILE AND flush_secret.txt AFTER USE.</p>

<?php if (!$authed): ?>
  <div class="secret-box">
    <strong>Your one-time secret has been auto-generated:</strong><br>
    <code id="sec"><?= htmlspecialchars($expected_secret) ?></code>
    <br><br>
    Bookmark or copy the link below and open it to access the flush form:<br>
    <code><?= htmlspecialchars('http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . '://' . ($_SERVER['HTTP_HOST'] ?? 'your-host') . $_SERVER['REQUEST_URI'] . (strpos($_SERVER['REQUEST_URI'], '?') !== false ? '&' : '?') . 'secret=' . urlencode($expected_secret)) ?></code>
    <br><br>
    Or paste the secret below:
    <form method="get">
      <input type="text" name="secret" placeholder="Paste secret here" style="width:340px;padding:6px;">
      <button class="btn btn-info" type="submit">Authenticate</button>
    </form>
  </div>

<?php else:
    $action   = $_POST['action'] ?? '';
    $result   = null;
    if ($action === 'dry_run') {
        $result = do_flush(true);
    } elseif ($action === 'flush_all') {
        $result = do_flush(false);
    }
?>
  <p style="color:green;">✔ Authenticated. Secret accepted.</p>

  <?php if ($result && isset($result['error'])): ?>
  <div style="color:#c0392b;background:#fdecea;padding:12px;border-radius:4px;"><?= htmlspecialchars($result['error']) ?></div>
  <?php endif; ?>

  <?php if ($result && isset($result['log'])): ?>
  <h3><?= $result['dry'] ? '🔍 Dry Run Results' : '✅ Flush Complete' ?></h3>
  <pre><?= implode("\n", $result['log']) ?></pre>
  <?php endif; ?>

  <form method="post" style="margin-top:20px;" onsubmit="return confirm('Run DRY RUN — no changes will be made. Continue?')">
    <input type="hidden" name="secret" value="<?= htmlspecialchars($expected_secret) ?>">
    <input type="hidden" name="action" value="dry_run">
    <button type="submit" class="btn btn-info">🔍 Dry Run (preview only)</button>
  </form>

  <form method="post" style="margin-top:12px;" onsubmit="return confirm('⚠ THIS WILL DELETE ALL MEMBER DATA PERMANENTLY. Are you absolutely sure?')">
    <input type="hidden" name="secret" value="<?= htmlspecialchars($expected_secret) ?>">
    <input type="hidden" name="action" value="flush_all">
    <button type="submit" class="btn btn-danger">🗑 Flush All Member Data (DESTRUCTIVE)</button>
  </form>

<?php endif; ?>
</div>
</body>
</html>

// CLI-only helper: generate a one-time secret for web access
if (php_sapi_name() === 'cli') {
    $argv = $_SERVER['argv'];
    array_shift($argv); // drop script name

    if (!empty($argv) && $argv[0] === 'generate-secret') {
        $token = bin2hex(random_bytes(16));
        file_put_contents($secret_file, $token);
        echo "Generated secret and saved to flush_secret.txt\n";
        echo "Secret: {$token}\n";
        echo "Use this value in the web form as 'secret' parameter.\n";
        exit(0);
    }

    if (empty($argv)) {
        echo "Usage:\n";
        echo "  php flush_members_oneoff.php ids 1,2,3 --dry\n";
        echo "  php flush_members_oneoff.php ids 1,2,3 --confirm\n";
        echo "  php flush_members_oneoff.php pattern MEMB --confirm\n";
        echo "  php flush_members_oneoff.php all --confirm --really=yes\n";
        echo "  php flush_members_oneoff.php generate-secret   # create flush_secret.txt for web use\n";
        exit(1);
    }

    $mode = array_shift($argv);
    $value = isset($argv[0]) && strpos($argv[0], '--') !== 0 ? array_shift($argv) : '';

    // Build forwarded args, include any flags passed
    $forward = [];
    $forward[] = 'cli';
    $forward[] = 'flush_members';
    $forward[] = 'run';
    $forward[] = $mode;
    if ($value !== '') $forward[] = $value;

    // Append remaining flags
    foreach ($argv as $a) {
        $forward[] = $a;
    }

    // Locate PHP binary and project index.php
    $php = defined('PHP_BINARY') ? PHP_BINARY : 'php';
    $index = __DIR__ . DIRECTORY_SEPARATOR . 'index.php';
    if (!file_exists($index)) {
        echo "index.php not found in project root. Run this from project root.\n";
        exit(1);
    }

    // Build command
    $parts = array_map('escapeshellarg', $forward);
    $cmd = escapeshellarg($php) . ' ' . escapeshellarg($index) . ' ' . implode(' ', $parts);

    echo "Running: " . $cmd . "\n\n";

    // Execute and stream output
    passthru($cmd, $exitCode);

    if ($exitCode !== 0) {
        echo "Command exited with code: {$exitCode}\n";
    }

    echo "Done. Remove this file when finished.\n";
    exit(0);

} else {
    // Web access path — require secret file and confirm flag
    header('Content-Type: text/html; charset=utf-8');

    if (!file_exists($secret_file)) {
        echo "<h3>Secret not set</h3><p>Please run on the server via CLI to generate a secret:<br><code>php flush_members_oneoff.php generate-secret</code></p>";
        exit;
    }
    $expected = trim(file_get_contents($secret_file));
    $provided = $_REQUEST['secret'] ?? '';
    if (empty($provided) || !hash_equals($expected, $provided)) {
        echo "<h3>Unauthorized</h3><p>Provide valid 'secret' parameter.</p>";
        exit;
    }

    // Gather parameters from request
    $mode = $_REQUEST['mode'] ?? 'ids';
    $value = $_REQUEST['value'] ?? '';
    $confirm = isset($_REQUEST['confirm']) ? true : false;
    $dry = isset($_REQUEST['dry']) ? true : false;

    if (!$confirm && !$dry) {
        echo "<h3>Confirmation required</h3><p>Include <code>confirm=1</code> for destructive actions, or <code>dry=1</code> to preview.</p>";
        exit;
    }

    $forward = ['cli', 'flush_members', 'run', $mode];
    if ($value !== '') $forward[] = $value;
    if ($dry) $forward[] = '--dry';
    if ($confirm) $forward[] = '--confirm';
    if (isset($_REQUEST['really']) && $_REQUEST['really'] === 'yes') $forward[] = '--really=yes';

    $php = defined('PHP_BINARY') ? PHP_BINARY : 'php';
    $index = __DIR__ . DIRECTORY_SEPARATOR . 'index.php';
    if (!file_exists($index)) {
        echo "<h3>Error</h3><p>index.php not found in project root.</p>";
        exit;
    }

    $parts = array_map('escapeshellarg', $forward);
    $cmd = escapeshellarg($php) . ' ' . escapeshellarg($index) . ' ' . implode(' ', $parts);

    echo "<h3>Running flush (one-off)</h3>";
    echo "<p>Command: <code>" . htmlspecialchars($cmd) . "</code></p>";
    echo "<pre style=\"background:#111;color:#eee;padding:12px;max-height:400px;overflow:auto;\">";

    // Execute and capture output
    $last_line = system($cmd, $ret);
    echo "\n</pre>";
    echo "<p>Exit code: " . intval($ret) . "</p>";
    echo "<p>Important: delete <code>flush_members_oneoff.php</code> and <code>flush_secret.txt</code> when finished.</p>";
    exit;
}
