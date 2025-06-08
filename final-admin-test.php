<?php
/**
 * Final WordPress Admin Test
 * Tests WordPress admin functionality after all fixes
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to catch any output before headers
ob_start();

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

try {
    // Load WordPress
    require_once(ABSPATH . 'wp-config.php');
    require_once(ABSPATH . 'wp-load.php');
    require_once(ABSPATH . 'wp-admin/includes/admin.php');
    
    // Clear output buffer
    ob_end_clean();
    
    echo "<h1>Final WordPress Admin Test</h1>\n";
    echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .test { margin: 10px 0; padding: 15px; border: 1px solid #ccc; border-radius: 5px; }
    .pass { background-color: #d4edda; border-color: #c3e6cb; }
    .fail { background-color: #f8d7da; border-color: #f5c6cb; }
    .warning { background-color: #fff3cd; border-color: #ffeaa7; }
    .info { background-color: #d1ecf1; border-color: #bee5eb; }
    .success { color: #155724; }
    .error { color: #721c24; }
    .warn { color: #856404; }
    .primary { color: #004085; }
    h2 { color: #007cba; }
    pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow: auto; }
    .btn { display: inline-block; padding: 10px 20px; margin: 5px; text-decoration: none; border-radius: 5px; color: white; }
    .btn-primary { background-color: #007cba; }
    .btn-success { background-color: #28a745; }
    .btn-warning { background-color: #ffc107; color: #333; }
    .btn-danger { background-color: #dc3545; }
    </style>\n";
    
    $test_results = array();
    $errors_found = array();
    
    echo "<h2>WordPress Core Tests</h2>\n";
    
    // Test 1: WordPress loaded
    if (function_exists('wp_get_current_user')) {
        $test_results['wp_loaded'] = true;
        echo "<div class='test pass'>\n";
        echo "<strong>✅ WordPress Core Loaded</strong><br>\n";
        echo "<span class='success'>WordPress functions are available</span>\n";
        echo "</div>\n";
    } else {
        $test_results['wp_loaded'] = false;
        $errors_found[] = "WordPress core not loaded";
        echo "<div class='test fail'>\n";
        echo "<strong>❌ WordPress Core Failed</strong><br>\n";
        echo "<span class='error'>WordPress functions not available</span>\n";
        echo "</div>\n";
    }
    
    // Test 2: Database connection
    global $wpdb;
    if ($wpdb && $wpdb instanceof wpdb) {
        $db_test = $wpdb->get_var("SELECT 1");
        if ($db_test == 1) {
            $test_results['db_connection'] = true;
            echo "<div class='test pass'>\n";
            echo "<strong>✅ Database Connection</strong><br>\n";
            echo "<span class='success'>Database is accessible</span>\n";
            echo "</div>\n";
        } else {
            $test_results['db_connection'] = false;
            $errors_found[] = "Database query failed";
            echo "<div class='test fail'>\n";
            echo "<strong>❌ Database Query Failed</strong><br>\n";
            echo "<span class='error'>Cannot execute database queries</span>\n";
            echo "</div>\n";
        }
    } else {
        $test_results['db_connection'] = false;
        $errors_found[] = "Database connection failed";
        echo "<div class='test fail'>\n";
        echo "<strong>❌ Database Connection Failed</strong><br>\n";
        echo "<span class='error'>wpdb object not available</span>\n";
        echo "</div>\n";
    }
    
    // Test 3: Admin functions
    if (function_exists('current_user_can') && function_exists('wp_get_current_user')) {
        $test_results['admin_functions'] = true;
        echo "<div class='test pass'>\n";
        echo "<strong>✅ Admin Functions</strong><br>\n";
        echo "<span class='success'>WordPress admin functions are available</span>\n";
        echo "</div>\n";
    } else {
        $test_results['admin_functions'] = false;
        $errors_found[] = "Admin functions not available";
        echo "<div class='test fail'>\n";
        echo "<strong>❌ Admin Functions Failed</strong><br>\n";
        echo "<span class='error'>WordPress admin functions not loaded</span>\n";
        echo "</div>\n";
    }
    
    echo "<h2>Plugin Tests</h2>\n";
    
    // Test 4: Active plugins
    $active_plugins = get_option('active_plugins', array());
    $environmental_active = 0;
    foreach ($active_plugins as $plugin) {
        if (strpos($plugin, 'environmental') !== false) {
            $environmental_active++;
        }
    }
    
    echo "<div class='test info'>\n";
    echo "<strong>ℹ️ Active Plugins Status</strong><br>\n";
    echo "<span class='primary'>Total Active Plugins: " . count($active_plugins) . "</span><br>\n";
    echo "<span class='primary'>Environmental Plugins Active: {$environmental_active}</span>\n";
    echo "</div>\n";
    
    // Test 5: EPP Classes
    $epp_classes = array(
        'EPP_Share_Manager',
        'EPP_Admin_Dashboard',
        'EPP_Analytics', 
        'EPP_Email_Notifications',
        'EPP_REST_API'
    );
    
    $epp_classes_loaded = 0;
    foreach ($epp_classes as $class_name) {
        if (class_exists($class_name)) {
            $epp_classes_loaded++;
        }
    }
    
    if ($epp_classes_loaded === count($epp_classes)) {
        $test_results['epp_classes'] = true;
        echo "<div class='test pass'>\n";
        echo "<strong>✅ EPP Classes Loaded</strong><br>\n";
        echo "<span class='success'>All {$epp_classes_loaded} EPP alias classes are available</span>\n";
        echo "</div>\n";
    } else {
        $test_results['epp_classes'] = false;
        $errors_found[] = "Missing EPP classes: " . ($epp_classes_loaded . "/" . count($epp_classes));
        echo "<div class='test fail'>\n";
        echo "<strong>❌ EPP Classes Missing</strong><br>\n";
        echo "<span class='error'>Only {$epp_classes_loaded}/" . count($epp_classes) . " EPP classes loaded</span>\n";
        echo "</div>\n";
    }
    
    echo "<h2>Cache and Performance Tests</h2>\n";
    
    // Test 6: Object cache
    if (function_exists('wp_cache_get')) {
        $cache_test = wp_cache_set('test_key', 'test_value');
        $cache_get = wp_cache_get('test_key');
        if ($cache_get === 'test_value') {
            $test_results['object_cache'] = true;
            echo "<div class='test pass'>\n";
            echo "<strong>✅ Object Cache Working</strong><br>\n";
            echo "<span class='success'>WordPress object cache is functional</span>\n";
            echo "</div>\n";
        } else {
            $test_results['object_cache'] = false;
            echo "<div class='test warning'>\n";
            echo "<strong>⚠️ Object Cache Issues</strong><br>\n";
            echo "<span class='warn'>Object cache not working properly</span>\n";
            echo "</div>\n";
        }
    } else {
        $test_results['object_cache'] = false;
        echo "<div class='test warning'>\n";
        echo "<strong>⚠️ Object Cache Not Available</strong><br>\n";
        echo "<span class='warn'>wp_cache functions not available</span>\n";
        echo "</div>\n";
    }
    
    // Test 7: WP_CACHE constant
    if (defined('WP_CACHE')) {
        echo "<div class='test info'>\n";
        echo "<strong>ℹ️ WP_CACHE Status</strong><br>\n";
        echo "<span class='primary'>WP_CACHE is " . (WP_CACHE ? "enabled" : "disabled") . "</span>\n";
        echo "</div>\n";
    } else {
        echo "<div class='test info'>\n";
        echo "<strong>ℹ️ WP_CACHE Status</strong><br>\n";
        echo "<span class='primary'>WP_CACHE constant not defined</span>\n";
        echo "</div>\n";
    }
    
    echo "<h2>Final Admin Access Test</h2>\n";
    
    // Test 8: Try to access admin area programmatically
    try {
        // Set up admin environment
        if (!defined('WP_ADMIN')) {
            define('WP_ADMIN', true);
        }
        
        // Test admin menu functionality
        if (function_exists('_get_plugin_data_markup_translate')) {
            $test_results['admin_access'] = true;
            echo "<div class='test pass'>\n";
            echo "<strong>✅ Admin Environment Ready</strong><br>\n";
            echo "<span class='success'>WordPress admin environment can be initialized</span>\n";
            echo "</div>\n";
        } else {
            $test_results['admin_access'] = false;
            $errors_found[] = "Admin environment not accessible";
            echo "<div class='test fail'>\n";
            echo "<strong>❌ Admin Environment Failed</strong><br>\n";
            echo "<span class='error'>Cannot initialize WordPress admin environment</span>\n";
            echo "</div>\n";
        }
    } catch (Exception $e) {
        $test_results['admin_access'] = false;
        $errors_found[] = "Admin access error: " . $e->getMessage();
        echo "<div class='test fail'>\n";
        echo "<strong>❌ Admin Access Error</strong><br>\n";
        echo "<span class='error'>Exception: " . htmlspecialchars($e->getMessage()) . "</span>\n";
        echo "</div>\n";
    }
    
    // Final results
    echo "<h2>Test Summary</h2>\n";
    
    $total_tests = count($test_results);
    $passed_tests = count(array_filter($test_results));
    $success_rate = round(($passed_tests / $total_tests) * 100, 1);
    
    $overall_status = 'pass';
    if (!empty($errors_found)) {
        $overall_status = 'fail';
    } elseif ($success_rate < 100) {
        $overall_status = 'warning';
    }
    
    echo "<div class='test {$overall_status}'>\n";
    echo "<h3>Overall Results</h3>\n";
    echo "<strong>Tests Run:</strong> {$total_tests}<br>\n";
    echo "<strong>Tests Passed:</strong> {$passed_tests}<br>\n";
    echo "<strong>Success Rate:</strong> {$success_rate}%<br>\n";
    
    if (empty($errors_found)) {
        echo "<h4 class='success'>🎉 All Critical Tests Passed!</h4>\n";
        echo "<p>WordPress admin should be accessible without major errors.</p>\n";
    } else {
        echo "<h4 class='error'>❌ Issues Found</h4>\n";
        echo "<p>The following errors were detected:</p>\n";
        echo "<ul>\n";
        foreach ($errors_found as $error) {
            echo "<li class='error'>{$error}</li>\n";
        }
        echo "</ul>\n";
    }
    
    echo "</div>\n";
    
    echo "<h2>Quick Actions</h2>\n";
    echo "<div class='test info'>\n";
    echo "<a href='http://localhost/moitruong/wp-admin/' class='btn btn-primary' target='_blank'>🔗 Open WordPress Admin</a>\n";
    echo "<a href='http://localhost/moitruong/wp-admin/plugins.php' class='btn btn-success' target='_blank'>🔌 View Plugins</a>\n";
    echo "<a href='http://localhost/moitruong/wp-admin/options-general.php' class='btn btn-warning' target='_blank'>⚙️ Settings</a>\n";
    echo "<a href='http://localhost/moitruong/test-all-class-aliases.php' class='btn btn-primary' target='_blank'>🧪 Class Tests</a>\n";
    echo "</div>\n";
    
    echo "<hr>\n";
    echo "<p><strong>Test completed at:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
    
} catch (Exception $e) {
    // Clear any output
    ob_end_clean();
    
    echo "<h1>WordPress Load Error</h1>\n";
    echo "<div style='color: red; border: 1px solid red; padding: 20px; margin: 20px; border-radius: 5px;'>\n";
    echo "<h2>❌ Critical Error</h2>\n";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>\n";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>\n";
    echo "<h3>Stack Trace:</h3>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
    echo "</div>\n";
}
?>
