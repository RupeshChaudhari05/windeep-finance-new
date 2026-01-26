<?php
// Clear OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache cleared successfully!<br>";
} else {
    echo "OPcache not available<br>";
}

// Clear APC cache if available
if (function_exists('apc_clear_cache')) {
    apc_clear_cache();
    echo "APC cache cleared successfully!<br>";
}

// Clear application cache folder
$cache_dir = __DIR__ . '/application/cache/';
if (is_dir($cache_dir)) {
    $files = glob($cache_dir . '*');
    foreach ($files as $file) {
        if (is_file($file) && basename($file) !== 'index.html' && basename($file) !== '.htaccess') {
            unlink($file);
        }
    }
    echo "Application cache cleared!<br>";
}

echo "<br>All caches cleared. Now delete this file for security.";
?>
