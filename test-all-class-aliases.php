<?php
/**
 * Test All Class Aliases
 * Comprehensive test for Environmental Platform Petitions class aliases
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Load WordPress
require_once(ABSPATH . 'wp-config.php');
require_once(ABSPATH . 'wp-load.php');

echo "<h1>Environmental Platform Petitions - Class Aliases Test</h1>\n";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.test { margin: 10px 0; padding: 10px; border: 1px solid #ccc; border-radius: 5px; }
.pass { background-color: #d4edda; border-color: #c3e6cb; }
.fail { background-color: #f8d7da; border-color: #f5c6cb; }
.warning { background-color: #fff3cd; border-color: #ffeaa7; }
.success { color: #155724; }
.error { color: #721c24; }
.warn { color: #856404; }
h2 { color: #007cba; }
pre { background: #f4f4f4; padding: 10px; border-radius: 3px; }
</style>\n";

$tests_passed = 0;
$tests_failed = 0;
$total_tests = 0;

// Test helper function
function run_test($test_name, $condition, $success_message, $failure_message) {
    global $tests_passed, $tests_failed, $total_tests;
    $total_tests++;
    
    if ($condition) {
        echo "<div class='test pass'>\n";
        echo "<strong>✅ {$test_name}</strong><br>\n";
        echo "<span class='success'>{$success_message}</span>\n";
        echo "</div>\n";
        $tests_passed++;
        return true;
    } else {
        echo "<div class='test fail'>\n";
        echo "<strong>❌ {$test_name}</strong><br>\n";
        echo "<span class='error'>{$failure_message}</span>\n";
        echo "</div>\n";
        $tests_failed++;
        return false;
    }
}

echo "<h2>1. Plugin Activation Status</h2>\n";

// Check if petition plugin is active
$active_plugins = get_option('active_plugins', array());
$petition_plugin_active = false;
foreach ($active_plugins as $plugin) {
    if (strpos($plugin, 'environmental-platform-petitions') !== false) {
        $petition_plugin_active = true;
        break;
    }
}

run_test(
    "Petition Plugin Active",
    $petition_plugin_active,
    "Environmental Platform Petitions plugin is active",
    "Environmental Platform Petitions plugin is not active"
);

echo "<h2>2. Class Existence Tests</h2>\n";

// Test original class names
$original_classes = array(
    'Environmental_Platform_Petitions_Share_Manager',
    'Environmental_Platform_Petitions_Admin_Dashboard',
    'Environmental_Platform_Petitions_Analytics',
    'Environmental_Platform_Petitions_Email_Notifications',
    'Environmental_Platform_Petitions_REST_API'
);

foreach ($original_classes as $class_name) {
    run_test(
        "Original Class: {$class_name}",
        class_exists($class_name),
        "Class {$class_name} exists and is loaded",
        "Class {$class_name} does not exist or failed to load"
    );
}

echo "<h2>3. Class Alias Tests</h2>\n";

// Test alias class names
$alias_classes = array(
    'EPP_Share_Manager',
    'EPP_Admin_Dashboard', 
    'EPP_Analytics',
    'EPP_Email_Notifications',
    'EPP_REST_API'
);

foreach ($alias_classes as $class_name) {
    run_test(
        "Alias Class: {$class_name}",
        class_exists($class_name),
        "Alias {$class_name} exists and is available",
        "Alias {$class_name} does not exist - class_alias may be missing"
    );
}

echo "<h2>4. Class Instantiation Tests</h2>\n";

// Test if classes can be instantiated
$instantiation_tests = array(
    'EPP_Share_Manager' => 'Share Manager',
    'EPP_Admin_Dashboard' => 'Admin Dashboard',
    'EPP_Analytics' => 'Analytics',
    'EPP_Email_Notifications' => 'Email Notifications',
    'EPP_REST_API' => 'REST API'
);

foreach ($instantiation_tests as $class_name => $display_name) {
    try {
        if (class_exists($class_name)) {
            // Try to create instance
            $reflection = new ReflectionClass($class_name);
            if ($reflection->isInstantiable()) {
                // Some classes might require parameters, so we'll just check if they're instantiable
                run_test(
                    "Instantiate: {$display_name}",
                    true,
                    "Class {$class_name} can be instantiated",
                    "Class {$class_name} cannot be instantiated"
                );
            } else {
                run_test(
                    "Instantiate: {$display_name}",
                    false,
                    "Class {$class_name} can be instantiated",
                    "Class {$class_name} is not instantiable (abstract or interface)"
                );
            }
        } else {
            run_test(
                "Instantiate: {$display_name}",
                false,
                "Class {$class_name} can be instantiated",
                "Class {$class_name} does not exist"
            );
        }
    } catch (Exception $e) {
        run_test(
            "Instantiate: {$display_name}",
            false,
            "Class {$class_name} can be instantiated",
            "Error checking class {$class_name}: " . $e->getMessage()
        );
    }
}

echo "<h2>5. File System Tests</h2>\n";

// Check if class files exist
$class_files = array(
    'class-share-manager.php' => 'Share Manager',
    'class-admin-dashboard.php' => 'Admin Dashboard',
    'class-analytics.php' => 'Analytics',
    'class-email-notifications.php' => 'Email Notifications',
    'class-rest-api.php' => 'REST API'
);

$plugin_includes_dir = WP_PLUGIN_DIR . '/environmental-platform-petitions/includes/';

foreach ($class_files as $file_name => $display_name) {
    $file_path = $plugin_includes_dir . $file_name;
    run_test(
        "File Exists: {$display_name}",
        file_exists($file_path),
        "File {$file_name} exists at {$file_path}",
        "File {$file_name} not found at {$file_path}"
    );
    
    if (file_exists($file_path)) {
        // Check for class_alias in file
        $file_content = file_get_contents($file_path);
        $has_alias = strpos($file_content, 'class_alias') !== false;
        run_test(
            "Has Alias: {$display_name}",
            $has_alias,
            "File {$file_name} contains class_alias statement",
            "File {$file_name} missing class_alias statement"
        );
    }
}

echo "<h2>6. WordPress Admin Integration Tests</h2>\n";

// Test WordPress functions
run_test(
    "WordPress Loaded",
    function_exists('wp_get_current_user'),
    "WordPress core functions are available",
    "WordPress core functions not loaded"
);

run_test(
    "Admin Functions",
    function_exists('current_user_can'),
    "WordPress admin functions are available", 
    "WordPress admin functions not loaded"
);

// Test database connection
global $wpdb;
run_test(
    "Database Connection",
    ($wpdb instanceof wpdb),
    "WordPress database connection is active",
    "WordPress database connection failed"
);

echo "<h2>7. Plugin Dependency Tests</h2>\n";

// Check for other required classes
$dependency_classes = array(
    'Environmental_Platform_Petitions_Database',
    'Environmental_Platform_Petitions_Signature_Manager',
    'Environmental_Platform_Petitions_Verification_System'
);

foreach ($dependency_classes as $class_name) {
    run_test(
        "Dependency: {$class_name}",
        class_exists($class_name),
        "Required dependency {$class_name} is loaded",
        "Required dependency {$class_name} is missing"
    );
}

echo "<h2>8. Recent Error Log Check</h2>\n";

// Check for recent errors in WordPress log
$error_log_path = WP_CONTENT_DIR . '/debug.log';
if (file_exists($error_log_path)) {
    $log_content = file_get_contents($error_log_path);
    $recent_content = substr($log_content, -5000); // Last 5KB
    
    // Check for EPP-related errors
    $epp_errors = array();
    if (preg_match_all('/.*EPP_.*/', $recent_content, $matches)) {
        $epp_errors = array_unique($matches[0]);
    }
    
    if (empty($epp_errors)) {
        run_test(
            "No EPP Errors",
            true,
            "No EPP-related errors found in recent logs",
            "EPP-related errors found in logs"
        );
    } else {
        echo "<div class='test warning'>\n";
        echo "<strong>⚠️ EPP Errors Found</strong><br>\n";
        echo "<span class='warn'>Found " . count($epp_errors) . " EPP-related errors in logs:</span><br>\n";
        foreach (array_slice($epp_errors, 0, 5) as $error) {
            echo "<pre>" . htmlspecialchars($error) . "</pre>\n";
        }
        echo "</div>\n";
    }
} else {
    run_test(
        "Error Log Available",
        false,
        "WordPress error log is available",
        "WordPress error log not found - debugging may not be enabled"
    );
}

echo "<h2>Test Results Summary</h2>\n";
echo "<div class='test " . ($tests_failed === 0 ? "pass" : "fail") . "'>\n";
echo "<h3>Overall Results</h3>\n";
echo "<strong>Total Tests:</strong> {$total_tests}<br>\n";
echo "<strong>Tests Passed:</strong> <span class='success'>{$tests_passed}</span><br>\n";
echo "<strong>Tests Failed:</strong> <span class='" . ($tests_failed > 0 ? "error" : "success") . "'>{$tests_failed}</span><br>\n";
echo "<strong>Success Rate:</strong> " . round(($tests_passed / $total_tests) * 100, 1) . "%<br>\n";

if ($tests_failed === 0) {
    echo "<h3 class='success'>🎉 All Tests Passed!</h3>\n";
    echo "<p>All Environmental Platform Petitions class aliases are working correctly.</p>\n";
} else {
    echo "<h3 class='error'>❌ Issues Found</h3>\n";
    echo "<p>Some tests failed. Please review the results above and fix the identified issues.</p>\n";
}

echo "</div>\n";

echo "<hr>\n";
echo "<p><strong>Test completed at:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
echo "<p><strong>Next step:</strong> <a href='http://localhost/moitruong/wp-admin/' target='_blank'>Test WordPress Admin</a></p>\n";
?>
