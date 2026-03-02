<?php
/**
 * db_check.php — Comprehensive Database Diagnostics
 *
 * Checks database connectivity, environment variables, PHP extensions,
 * and database state. Useful for debugging connection issues on any hosting.
 *
 * SECURITY: Remove this file after use. Do not leave it on production.
 */
header('Content-Type: text/html; charset=utf-8');

// Define required CI constants
define('BASEPATH', __DIR__);
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'development');
}
if (!defined('FCPATH')) {
    define('FCPATH', __DIR__ . '/');
}

// Load environment helper
require __DIR__ . '/application/helpers/env_helper.php';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Database Diagnostics</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 20px auto; }
        .section { border: 1px solid #ddd; margin: 20px 0; padding: 15px; border-radius: 5px; }
        .pass { background: #d4edda; border-color: #28a745; color: #155724; }
        .fail { background: #f8d7da; border-color: #dc3545; color: #721c24; }
        .info { background: #d1ecf1; border-color: #17a2b8; color: #0c5460; }
        h2 { margin-top: 0; }
        .detail { margin: 10px 0; padding: 10px; background: rgba(0,0,0,0.05); border-left: 3px solid #999; font-family: monospace; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        table td { padding: 8px; border-bottom: 1px solid #ddd; }
        table td:first-child { font-weight: bold; width: 30%; }
    </style>
</head>
<body>

<h1>🔍 Database Diagnostics</h1>
<p>Check date: <strong><?= date('Y-m-d H:i:s') ?></strong></p>

<?php
// ===== PHP Extensions Check =====
echo '<div class="section info"><h2>PHP Extensions</h2>';
$extensions = ['mysqli', 'pdo_mysql', 'mysql'];
$has_mysql = false;
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    $has_mysql = $has_mysql || $loaded;
    $class = $loaded ? 'pass' : 'fail';
    echo "<div class=\"detail $class\">";
    echo $loaded ? '✓' : '✗';
    echo " <strong>$ext</strong> - " . ($loaded ? 'Loaded' : 'Not loaded');
    echo "</div>";
}
if (!$has_mysql) {
    echo '<div class="detail fail">⚠️ No MySQL driver found! Install mysqli or pdo_mysql.</div>';
}
echo '</div>';

// ===== Environment Variables Check =====
echo '<div class="section info"><h2>Environment File (.env) Check</h2>';

$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    echo '<div class="detail pass">✓ .env file found at: <code>' . $env_file . '</code></div>';
    
    // Read and display .env contents (with password masking)
    $env_contents = file_get_contents($env_file);
    $lines = array_filter(array_map('trim', explode("\n", $env_contents)), fn($l) => !empty($l) && $l[0] !== '#');
    
    echo '<div class="detail"><strong>.env File Contents:</strong><br>';
    echo '<code style="display:block; white-space:pre-wrap;">';
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $val = trim($val, "\"' ");
            // Mask sensitive values
            if (stripos($key, 'password') !== false || stripos($key, 'secret') !== false || stripos($key, 'key') !== false) {
                $val = str_repeat('*', strlen($val));
            }
            echo htmlspecialchars("$key=$val") . "\n";
        }
    }
    echo '</code></div>';
    
    // Check if env helper loads it
    echo '<div class="detail"><strong>Loaded Values (via env() function):</strong><br>';
    $check_vars = ['DB_HOST', 'DB_USERNAME', 'DB_PASSWORD', 'DB_NAME', 'APP_NAME', 'APP_ENV'];
    foreach ($check_vars as $var) {
        $val = env($var);
        $display = $val ?? '(not loaded)';
        if (empty($display)) $display = '(empty string)';
        if (stripos($var, 'PASSWORD') !== false && !empty($val)) {
            $display = str_repeat('*', strlen($val));
        }
        echo '<code>' . htmlspecialchars($var) . ' = ' . htmlspecialchars($display) . '</code><br>';
    }
    echo '</div>';
} else {
    echo '<div class="detail fail">✗ .env file NOT found at: <code>' . $env_file . '</code></div>';
    echo '<div class="detail fail"><strong>How to fix:</strong>';
    echo '<ol>';
    echo '<li>Copy the example: <code>cp .env.example .env</code> (if it exists)</li>';
    echo '<li>Or create a new .env file in the root directory with:</li>';
    echo '<pre>
APP_NAME=Windeep Finance
APP_ENV=development
DB_HOST=localhost
DB_USERNAME=root
DB_PASSWORD=
DB_NAME=windeep_finance_new
</pre>';
    echo '<li>Make sure the file is readable by PHP (check file permissions)</li>';
    echo '<li>Restart your web server</li>';
    echo '</ol>';
    echo '</div>';
}
echo '</div>';

// ===== Environment Variables Check =====
echo '<div class="section info"><h2>Database Connection Variables</h2>';

// ===== Database Configuration =====
echo '<div class="section info"><h2>Database Configuration</h2>';
$config = [];
$db = [];
include __DIR__ . '/application/config/database.php';
$active = $active_group ?? 'default';
$conf = isset($db[$active]) ? $db[$active] : null;

if (!$conf) {
    echo '<div class="detail fail">⚠️ Could not load database configuration!</div>';
    exit;
}

$host = $conf['hostname'] ?? 'localhost';
$user = $conf['username'] ?? '';
$pass = $conf['password'] ?? '';
$name = $conf['database'] ?? '';
$port = isset($conf['port']) ? (int)$conf['port'] : 3306;

echo '<table>';
echo "<tr><td>Active Group</td><td><code>$active</code></td></tr>";
echo "<tr><td>Host</td><td><code>$host</code></td></tr>";
echo "<tr><td>Port</td><td><code>$port</code></td></tr>";
echo "<tr><td>Database</td><td><code>$name</code></td></tr>";
echo "<tr><td>User</td><td><code>$user</code></td></tr>";
echo "<tr><td>Password Set</td><td><code>" . (!empty($pass) ? 'Yes (length: ' . strlen($pass) . ')' : 'No') . "</code></td></tr>";
echo '</table>';
echo '</div>';

// ===== Network/Port Check =====
echo '<div class="section info"><h2>Network Connectivity</h2>';
$sock = @fsockopen($host, $port, $errno, $errstr, 3);
if ($sock) {
    fclose($sock);
    echo '<div class="detail pass">✓ Port ' . $port . ' on ' . $host . ' is reachable</div>';
} else {
    echo '<div class="detail fail">✗ Cannot reach ' . $host . ':' . $port . '</div>';
    echo '<div class="detail fail">Error: ' . htmlspecialchars($errstr) . ' (Code: ' . $errno . ')</div>';
}
echo '</div>';

// ===== Database Connection Test =====
echo '<div class="section"><h2>MySQL Connection Test</h2>';
$mysqli = @new mysqli($host, $user, $pass, $name, $port);

if ($mysqli->connect_errno) {
    echo '<div class="detail fail">';
    echo '<strong>✗ Connection Failed</strong><br>';
    echo 'Error Code: ' . $mysqli->connect_errno . '<br>';
    echo 'Error Message: ' . htmlspecialchars($mysqli->connect_error);
    echo '</div>';
} else {
    echo '<div class="detail pass"><strong>✓ Connected Successfully</strong></div>';
    
    // Get server info
    echo '<div class="detail">';
    echo 'MySQL Version: <code>' . $mysqli->server_info . '</code><br>';
    echo 'Server Character Set: <code>' . $mysqli->character_set_name() . '</code>';
    echo '</div>';
    
    // Test query execution
    echo '<div class="section info"><h2>Database Health</h2>';
    
    // Check if tables exist
    $result = $mysqli->query("SELECT COUNT(*) as count FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$name'");
    if ($result) {
        $row = $result->fetch_assoc();
        $table_count = $row['count'] ?? 0;
        echo '<div class="detail">';
        echo '<strong>Tables in database:</strong> ' . $table_count;
        if ($table_count === 0) {
            echo ' <span style="color:orange;">(Warning: Database is empty)</span>';
        }
        echo '</div>';
    }
    
    // Check if critical tables exist
    $critical_tables = ['members', 'loans', 'loan_installments', 'admin_users'];
    echo '<div class="detail"><strong>Critical Tables:</strong><br>';
    foreach ($critical_tables as $table) {
        $check = $mysqli->query("SHOW TABLES LIKE '$table'");
        $exists = $check && $check->num_rows > 0;
        echo ($exists ? '✓' : '✗') . ' ' . $table . '<br>';
    }
    echo '</div>';
    
    // Check database privileges
    echo '<div class="detail"><strong>User Privileges:</strong><br>';
    $priv_check = $mysqli->query("SELECT GRANTEE, PRIVILEGE_TYPE FROM information_schema.ROLE_PRIVILEGE_GRANTS WHERE GRANTEE = \"'$user'@'%'\" OR GRANTEE = \"'$user'@'$host'\" LIMIT 5");
    if ($priv_check && $priv_check->num_rows > 0) {
        while ($row = $priv_check->fetch_assoc()) {
            echo '✓ ' . htmlspecialchars($row['PRIVILEGE_TYPE']) . '<br>';
        }
    } else {
        echo '<span style="color:orange;">Could not fetch detailed privileges</span>';
    }
    echo '</div>';
    
    echo '</div>';
    $mysqli->close();
}

?>

<div class="section info">
    <h2>Troubleshooting Guide</h2>
    
    <h3>1️⃣ .env File Not Found</h3>
    <p><strong>Problem:</strong> The application cannot find the .env file.</p>
    <p><strong>Solution:</strong></p>
    <ul>
        <li>Create a file named <code>.env</code> (not .env.php, not .env.txt - just <code>.env</code>) in the root project directory</li>
        <li>Add these contents to it:
            <pre>
APP_NAME=Windeep Finance
APP_ENV=development
DB_HOST=localhost
DB_PORT=3306
DB_USERNAME=root
DB_PASSWORD=
DB_NAME=windeep_finance_new
            </pre>
        </li>
        <li>Save the file and reload this page</li>
    </ul>
    
    <h3>2️⃣ .env File Exists But Not Loading</h3>
    <p><strong>Problem:</strong> .env file exists but values show as "(not loaded)"</p>
    <p><strong>Solution:</strong></p>
    <ul>
        <li>Check file permissions: <code>ls -la .env</code> (should be readable by web server user)</li>
        <li>On UNIX/Linux: <code>chmod 644 .env</code></li>
        <li>Check file encoding: Make sure the file is in UTF-8 format (not UTF-8 BOM)</li>
        <li>Restart your web server:
            <ul>
                <li>XAMPP: Restart Apache from the control panel</li>
                <li>Linux: <code>sudo systemctl restart apache2</code> or <code>sudo systemctl restart nginx</code></li>
            </ul>
        </li>
    </ul>
    
    <h3>3️⃣ Database Connection Fails Despite Correct Config</h3>
    <p><strong>Problem:</strong> Config shows correct values but connection fails</p>
    <p><strong>Possible causes:</strong></p>
    <ul>
        <li><strong>MySQL server not running:</strong> Make sure MySQL/MariaDB is started (use XAMPP Control Panel or <code>sudo systemctl start mysql</code>)</li>
        <li><strong>Wrong host for remote database:</strong> If database is on a different server, use its IP address instead of "localhost"</li>
        <li><strong>Firewall blocking:</strong> Check if your firewall allows MySQL (port 3306) connections</li>
        <li><strong>Wrong credentials:</strong> Verify username and password are correct</li>
        <li><strong>User doesn't have permission:</strong> Create the MySQL user with proper permissions:
            <pre>
mysql -u root -p
CREATE USER 'windeep'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON windeep_finance_new.* TO 'windeep'@'localhost';
FLUSH PRIVILEGES;
            </pre>
        </li>
    </ul>
    
    <h3>4️⃣ Tables Missing or Database Empty</h3>
    <p><strong>Problem:</strong> Connected but critical tables not found</p>
    <p><strong>Solution:</strong> Import the database schema</p>
    <pre>
# Option 1: Using MySQL command line
mysql -u root -p windeep_finance_new < database/install_complete.sql

# Option 2: Using phpmyadmin
1. Go to http://localhost/phpmyadmin
2. Select your database
3. Click "Import"
4. Choose database/install_complete.sql
5. Click "Go"
    </pre>
    
    <h3>5️⃣ PHP Missing MySQL Extension</h3>
    <p><strong>Problem:</strong> "No MySQL driver found" message</p>
    <p><strong>Solution:</strong></p>
    <ul>
        <li><strong>For XAMPP:</strong> Edit php.ini and uncomment:
            <pre>
extension=mysqli
            </pre>
            Then restart Apache.
        </li>
        <li><strong>For Linux (Ubuntu/Debian):</strong>
            <pre>
sudo apt-get install php-mysqli
sudo systemctl restart apache2
            </pre>
        </li>
        <li><strong>Verify:</strong> Run <code>php -m | grep -i mysql</code> (should show mysqli or mysql)</li>
    </ul>
    
    <h3>📝 Environment Variables Reference</h3>
    <table>
        <tr>
            <td>DB_HOST</td>
            <td>Database server address (default: localhost)</td>
        </tr>
        <tr>
            <td>DB_PORT</td>
            <td>Database server port (default: 3306)</td>
        </tr>
        <tr>
            <td>DB_USERNAME</td>
            <td>MySQL username (default: root)</td>
        </tr>
        <tr>
            <td>DB_PASSWORD</td>
            <td>MySQL password (leave empty for no password)</td>
        </tr>
        <tr>
            <td>DB_NAME</td>
            <td>Database name (default: windeep_finance_new)</td>
        </tr>
    </table>
    
    <h3>⚠️ Important Security Notes</h3>
    <ul>
        <li><strong>Never commit .env to Git</strong> — It should be in .gitignore</li>
        <li><strong>Delete db_check.php</strong> after you're done debugging (it's a security risk on production)</li>
        <li><strong>Change default passwords</strong> before going live</li>
        <li><strong>Set strong DB_PASSWORD</strong> in production environments</li>
    </ul>
</div>

</body>
</html>
