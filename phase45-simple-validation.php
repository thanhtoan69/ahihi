<?php
/**
 * PHASE 45: SIMPLE PRODUCTION VALIDATION
 * Environmental Platform - Production Readiness Check
 */

// Simple health check without WordPress dependency
echo "<!DOCTYPE html>\n";
echo "<html>\n<head>\n";
echo "<title>Phase 45: Production Validation</title>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:40px;background:#f0f8ff;} .card{background:white;padding:20px;margin:20px 0;border-radius:8px;box-shadow:0 2px 5px rgba(0,0,0,0.1);} .success{border-left:5px solid #4CAF50;} .warning{border-left:5px solid #ff9800;} .error{border-left:5px solid #f44336;} h1{color:#2c3e50;} h2{color:#34495e;margin-top:0;} .status{display:inline-block;width:12px;height:12px;border-radius:50%;margin-right:8px;} .ok{background:#4CAF50;} .warn{background:#ff9800;} .fail{background:#f44336;}</style>\n";
echo "</head>\n<body>\n";

echo "<h1>🚀 Phase 45: Production Deployment Validation</h1>\n";
echo "<p><strong>Assessment Date:</strong> " . date('Y-m-d H:i:s') . "</p>\n";

$checks = [];
$score = 0;
$total = 0;

// 1. PHP Environment
echo "<div class='card'>\n<h2>⚙️ PHP Environment</h2>\n";
$php_version = PHP_VERSION;
$php_ok = version_compare($php_version, '7.4', '>=');
$checks['PHP Version'] = $php_ok;
echo "<p><span class='status " . ($php_ok ? 'ok' : 'fail') . "'></span>PHP Version: $php_version " . ($php_ok ? '✅' : '❌') . "</p>\n";

$memory_limit = ini_get('memory_limit');
$memory_ok = (int)$memory_limit >= 256;
$checks['Memory Limit'] = $memory_ok;
echo "<p><span class='status " . ($memory_ok ? 'ok' : 'warn') . "'></span>Memory Limit: $memory_limit " . ($memory_ok ? '✅' : '⚠️') . "</p>\n";

$uploads_ok = ini_get('file_uploads');
$checks['File Uploads'] = $uploads_ok;
echo "<p><span class='status " . ($uploads_ok ? 'ok' : 'fail') . "'></span>File Uploads: " . ($uploads_ok ? 'Enabled ✅' : 'Disabled ❌') . "</p>\n";
echo "</div>\n";

// 2. Database Connection
echo "<div class='card'>\n<h2>🗄️ Database Connection</h2>\n";
try {
    $mysqli = new mysqli('localhost', 'root', '', 'environmental_platform');
    if ($mysqli->connect_error) {
        throw new Exception("Connection failed: " . $mysqli->connect_error);
    }
    $db_ok = true;
    $checks['Database Connection'] = true;
    echo "<p><span class='status ok'></span>Database Connection: Connected ✅</p>\n";
    
    // Check database tables
    $tables = ['env_users', 'env_posts', 'env_donations', 'env_items', 'env_events'];
    $table_count = 0;
    foreach ($tables as $table) {
        $result = $mysqli->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            $table_count++;
        }
    }
    $tables_ok = $table_count >= 3;
    $checks['Critical Tables'] = $tables_ok;
    echo "<p><span class='status " . ($tables_ok ? 'ok' : 'warn') . "'></span>Critical Tables: $table_count/5 found " . ($tables_ok ? '✅' : '⚠️') . "</p>\n";
    
    $mysqli->close();
} catch (Exception $e) {
    $db_ok = false;
    $checks['Database Connection'] = false;
    echo "<p><span class='status fail'></span>Database Connection: Failed ❌</p>\n";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
echo "</div>\n";

// 3. Plugin Files
echo "<div class='card'>\n<h2>🔌 Plugin Files</h2>\n";
$plugin_dir = 'wp-content/plugins/';
$required_plugins = [
    'environmental-platform-core',
    'environmental-platform-forum', 
    'environmental-donation-system',
    'environmental-item-exchange',
    'environmental-voucher-rewards',
    'environmental-platform-events',
    'environmental-data-dashboard',
    'environmental-mobile-api',
    'environmental-analytics-reporting'
];

$plugin_count = 0;
foreach ($required_plugins as $plugin) {
    $plugin_exists = is_dir($plugin_dir . $plugin);
    if ($plugin_exists) $plugin_count++;
    $checks[$plugin] = $plugin_exists;
    echo "<p><span class='status " . ($plugin_exists ? 'ok' : 'fail') . "'></span>$plugin: " . ($plugin_exists ? 'Found ✅' : 'Missing ❌') . "</p>\n";
}

$plugins_ok = $plugin_count >= 7;
echo "<p><strong>Total Plugins Found: $plugin_count/9 " . ($plugins_ok ? '✅' : '⚠️') . "</strong></p>\n";
echo "</div>\n";

// 4. File Permissions
echo "<div class='card'>\n<h2>📁 File Permissions</h2>\n";
$uploads_dir = 'wp-content/uploads';
$uploads_writable = is_writable($uploads_dir);
$checks['Uploads Writable'] = $uploads_writable;
echo "<p><span class='status " . ($uploads_writable ? 'ok' : 'fail') . "'></span>Uploads Directory: " . ($uploads_writable ? 'Writable ✅' : 'Not Writable ❌') . "</p>\n";

$config_readable = file_exists('wp-config.php') && is_readable('wp-config.php');
$checks['Config File'] = $config_readable;
echo "<p><span class='status " . ($config_readable ? 'ok' : 'fail') . "'></span>WordPress Config: " . ($config_readable ? 'Found ✅' : 'Missing ❌') . "</p>\n";
echo "</div>\n";

// 5. Security Checks
echo "<div class='card'>\n<h2>🔒 Security Assessment</h2>\n";

// Check if wp-config.php contains security keys
$config_content = file_exists('wp-config.php') ? file_get_contents('wp-config.php') : '';
$has_salts = strpos($config_content, 'AUTH_KEY') !== false;
$checks['Security Keys'] = $has_salts;
echo "<p><span class='status " . ($has_salts ? 'ok' : 'warn') . "'></span>Security Keys: " . ($has_salts ? 'Configured ✅' : 'Missing ⚠️') . "</p>\n";

$debug_disabled = strpos($config_content, "define( 'WP_DEBUG', false )") !== false;
$checks['Debug Mode'] = $debug_disabled;
echo "<p><span class='status " . ($debug_disabled ? 'ok' : 'warn') . "'></span>Debug Mode: " . ($debug_disabled ? 'Disabled ✅' : 'Enabled ⚠️') . "</p>\n";

$disallow_edit = strpos($config_content, "define( 'DISALLOW_FILE_EDIT', true )") !== false;
$checks['File Editing'] = $disallow_edit;
echo "<p><span class='status " . ($disallow_edit ? 'ok' : 'warn') . "'></span>File Editing: " . ($disallow_edit ? 'Disabled ✅' : 'Enabled ⚠️') . "</p>\n";
echo "</div>\n";

// Calculate Score
foreach ($checks as $check => $result) {
    $total++;
    if ($result) $score++;
}

$percentage = $total > 0 ? round(($score / $total) * 100) : 0;

// Overall Score
echo "<div class='card " . ($percentage >= 80 ? 'success' : ($percentage >= 60 ? 'warning' : 'error')) . "'>\n";
echo "<h2>🎯 Production Readiness Score</h2>\n";
echo "<div style='text-align:center;'>\n";
echo "<div style='font-size:48px;font-weight:bold;color:" . ($percentage >= 80 ? '#4CAF50' : ($percentage >= 60 ? '#ff9800' : '#f44336')) . ";'>$percentage%</div>\n";
echo "<p><strong>Status: ";
if ($percentage >= 90) {
    echo "<span style='color:#4CAF50;'>PRODUCTION READY ✅</span>";
} elseif ($percentage >= 80) {
    echo "<span style='color:#4CAF50;'>MOSTLY READY ✅</span>";
} elseif ($percentage >= 60) {
    echo "<span style='color:#ff9800;'>NEEDS IMPROVEMENT ⚠️</span>";
} else {
    echo "<span style='color:#f44336;'>NOT READY ❌</span>";
}
echo "</strong></p>\n";
echo "<p>Passed: $score/$total checks</p>\n";
echo "</div>\n";
echo "</div>\n";

// Recommendations
if ($percentage < 100) {
    echo "<div class='card warning'>\n<h2>💡 Recommendations</h2>\n<ul>\n";
    
    if (!$checks['Database Connection']) {
        echo "<li>Fix database connection issues</li>\n";
    }
    if (!$checks['Debug Mode']) {
        echo "<li>Disable WordPress debug mode for production</li>\n";
    }
    if (!$checks['File Editing']) {
        echo "<li>Disable file editing in WordPress admin</li>\n";
    }
    if ($plugin_count < 9) {
        echo "<li>Ensure all required Environmental Platform plugins are installed</li>\n";
    }
    if (!$checks['Uploads Writable']) {
        echo "<li>Fix file permissions for uploads directory</li>\n";
    }
    
    echo "<li>Set up SSL/HTTPS encryption</li>\n";
    echo "<li>Configure automated backups</li>\n";
    echo "<li>Implement caching for better performance</li>\n";
    echo "<li>Set up monitoring and alerting</li>\n";
    echo "</ul>\n</div>\n";
}

// System Info
echo "<div class='card'>\n<h2>📊 System Information</h2>\n";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>\n";
echo "<p><strong>Server Software:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>\n";
echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>\n";
echo "<p><strong>Assessment Time:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
echo "<p><strong>Platform Version:</strong> Environmental Platform v1.0</p>\n";
echo "</div>\n";

echo "<div style='text-align:center;margin-top:30px;color:#666;'>\n";
echo "<p>&copy; 2024 Environmental Platform - Phase 45: Production Deployment Assessment</p>\n";
echo "</div>\n";

echo "</body>\n</html>\n";

// Save results to log
$log_data = [
    'timestamp' => date('Y-m-d H:i:s'),
    'score' => $percentage,
    'checks' => $checks,
    'passed' => $score,
    'total' => $total
];

file_put_contents('phase45-validation-log.json', json_encode($log_data, JSON_PRETTY_PRINT));
?>
