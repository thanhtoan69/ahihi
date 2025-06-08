<?php
/**
 * WordPress Diagnostic Tool
 * Checks for common WordPress issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>WordPress Diagnostic Report</h1>\n";
echo "<p>Generated: " . date('Y-m-d H:i:s') . "</p>\n";

// Check 1: File permissions and existence
echo "<h2>1. File System Check</h2>\n";

$files_to_check = [
    'wp-config.php',
    'wp-load.php',
    'wp-admin/index.php',
    'wp-content/advanced-cache.php',
    'wp-includes/functions.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        echo "<p>✓ $file (permissions: $perms)</p>\n";
    } else {
        echo "<p>❌ $file - NOT FOUND</p>\n";
    }
}

// Check 2: PHP Configuration
echo "<h2>2. PHP Configuration</h2>\n";
echo "<p>PHP Version: " . PHP_VERSION . "</p>\n";
echo "<p>Memory Limit: " . ini_get('memory_limit') . "</p>\n";
echo "<p>Max Execution Time: " . ini_get('max_execution_time') . "s</p>\n";
echo "<p>Error Reporting: " . error_reporting() . "</p>\n";

// Check 3: Database Connection Test
echo "<h2>3. Database Connection Test</h2>\n";

if (file_exists('wp-config.php')) {
    // Read database credentials from wp-config.php
    $config_content = file_get_contents('wp-config.php');
    
    preg_match("/define\(\s*'DB_NAME',\s*'([^']+)'\s*\)/", $config_content, $db_name_match);
    preg_match("/define\(\s*'DB_USER',\s*'([^']+)'\s*\)/", $config_content, $db_user_match);
    preg_match("/define\(\s*'DB_PASSWORD',\s*'([^']+)'\s*\)/", $config_content, $db_pass_match);
    preg_match("/define\(\s*'DB_HOST',\s*'([^']+)'\s*\)/", $config_content, $db_host_match);
    
    if ($db_name_match && $db_user_match && $db_host_match) {
        $db_name = $db_name_match[1];
        $db_user = $db_user_match[1];
        $db_pass = isset($db_pass_match[1]) ? $db_pass_match[1] : '';
        $db_host = $db_host_match[1];
        
        echo "<p>Database Name: $db_name</p>\n";
        echo "<p>Database User: $db_user</p>\n";
        echo "<p>Database Host: $db_host</p>\n";
        
        try {
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
            echo "<p>✓ Database connection successful</p>\n";
            
            // Test if WordPress tables exist
            $stmt = $pdo->query("SHOW TABLES LIKE 'wp_%'");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "<p>WordPress tables found: " . count($tables) . "</p>\n";
            
        } catch (PDOException $e) {
            echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>\n";
        }
    }
}

// Check 4: WordPress Loading Test
echo "<h2>4. WordPress Loading Test</h2>\n";

// Capture any errors during WordPress loading
ob_start();
$wp_load_success = false;

try {
    // Temporarily disable the advanced cache to test core WordPress
    if (file_exists('wp-content/advanced-cache.php')) {
        rename('wp-content/advanced-cache.php', 'wp-content/advanced-cache-temp-disabled.php');
    }
    
    if (file_exists('wp-load.php')) {
        require_once 'wp-load.php';
        $wp_load_success = true;
        echo "<p>✓ WordPress core loaded successfully</p>\n";
        
        // Test key WordPress functions
        if (function_exists('wp_is_mobile')) {
            echo "<p>✓ wp_is_mobile() available</p>\n";
        }
        
        if (function_exists('is_user_logged_in')) {
            echo "<p>✓ is_user_logged_in() available</p>\n";
        }
        
        if (function_exists('sanitize_text_field')) {
            echo "<p>✓ sanitize_text_field() available</p>\n";
        }
        
        // Test database connection through WordPress
        global $wpdb;
        if ($wpdb) {
            echo "<p>✓ WordPress database connection established</p>\n";
        }
        
    } else {
        echo "<p>❌ wp-load.php not found</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Exception: " . $e->getMessage() . "</p>\n";
} catch (Error $e) {
    echo "<p>❌ Fatal Error: " . $e->getMessage() . "</p>\n";
}

$output = ob_get_clean();
echo $output;

// Re-enable advanced cache if it was disabled
if (file_exists('wp-content/advanced-cache-temp-disabled.php')) {
    rename('wp-content/advanced-cache-temp-disabled.php', 'wp-content/advanced-cache.php');
}

// Check 5: Recommendations
echo "<h2>5. Recommendations</h2>\n";

if ($wp_load_success) {
    echo "<p>✓ WordPress core is working properly</p>\n";
    echo "<p>✓ You should now be able to access <a href='wp-admin/'>WordPress Admin</a></p>\n";
} else {
    echo "<p>❌ WordPress core has issues that need to be resolved</p>\n";
}

echo "<hr>\n";
echo "<p><strong>Diagnostic completed.</strong></p>\n";
?>
