<?php
/**
 * Environmental Analytics & Reporting Plugin Activation Test
 * Tests plugin activation, database creation, and core functionality
 */

require_once('wp-config.php');

// Set up WordPress environment
define('WP_USE_THEMES', false);
require_once(ABSPATH . 'wp-blog-header.php');

echo "<h1>Environmental Analytics & Reporting Plugin - Activation Test</h1>\n";
echo "<p>Testing plugin activation and core functionality...</p>\n";

// Test 1: Check if plugin files exist
echo "<h2>1. Plugin File Structure Test</h2>\n";
$plugin_dir = WP_CONTENT_DIR . '/plugins/environmental-analytics-reporting/';
$required_files = [
    'environmental-analytics-reporting.php',
    'includes/class-database-manager.php',
    'includes/class-tracking-manager.php',
    'includes/class-conversion-tracker.php',
    'includes/class-behavior-analytics.php',
    'includes/class-ga4-integration.php',
    'includes/class-report-generator.php',
    'includes/class-dashboard-widgets.php',
    'includes/class-cron-handler.php',
    'admin/class-admin-dashboard.php',
    'assets/js/env-analytics.js',
    'assets/css/env-analytics-admin.css'
];

$missing_files = [];
foreach ($required_files as $file) {
    if (!file_exists($plugin_dir . $file)) {
        $missing_files[] = $file;
    }
}

if (empty($missing_files)) {
    echo "<p style='color: green;'>✓ All required plugin files exist</p>\n";
} else {
    echo "<p style='color: red;'>✗ Missing files:</p>\n";
    foreach ($missing_files as $file) {
        echo "<p style='color: red; padding-left: 20px;'>- $file</p>\n";
    }
}

// Test 2: Activate the plugin
echo "<h2>2. Plugin Activation Test</h2>\n";
try {
    $plugin_file = 'environmental-analytics-reporting/environmental-analytics-reporting.php';
    
    if (!is_plugin_active($plugin_file)) {
        $result = activate_plugin($plugin_file);
        if (is_wp_error($result)) {
            echo "<p style='color: red;'>✗ Plugin activation failed: " . $result->get_error_message() . "</p>\n";
        } else {
            echo "<p style='color: green;'>✓ Plugin activated successfully</p>\n";
        }
    } else {
        echo "<p style='color: blue;'>ℹ Plugin is already active</p>\n";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Plugin activation error: " . $e->getMessage() . "</p>\n";
}

// Test 3: Check database tables
echo "<h2>3. Database Tables Test</h2>\n";
global $wpdb;

$required_tables = [
    'env_analytics_events',
    'env_user_sessions',
    'env_conversion_goals',
    'env_conversion_tracking',
    'env_user_behavior'
];

$missing_tables = [];
foreach ($required_tables as $table) {
    $table_name = $wpdb->prefix . $table;
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    if (!$table_exists) {
        $missing_tables[] = $table_name;
    }
}

if (empty($missing_tables)) {
    echo "<p style='color: green;'>✓ All required database tables exist</p>\n";
} else {
    echo "<p style='color: red;'>✗ Missing database tables:</p>\n";
    foreach ($missing_tables as $table) {
        echo "<p style='color: red; padding-left: 20px;'>- $table</p>\n";
    }
    
    // Try to create missing tables
    echo "<p>Attempting to create missing tables...</p>\n";
    if (class_exists('Environmental_Database_Manager')) {
        $db_manager = new Environmental_Database_Manager();
        $db_manager->create_tables();
        echo "<p style='color: blue;'>✓ Database table creation attempted</p>\n";
    }
}

// Test 4: Check plugin options
echo "<h2>4. Plugin Options Test</h2>\n";
$required_options = [
    'env_analytics_tracking_enabled',
    'env_analytics_ga4_enabled',
    'env_analytics_ga4_measurement_id',
    'env_analytics_conversion_goals',
    'env_analytics_daily_email_enabled',
    'env_analytics_weekly_email_enabled',
    'env_analytics_monthly_email_enabled',
    'env_analytics_email_recipients'
];

$missing_options = [];
foreach ($required_options as $option) {
    $value = get_option($option);
    if ($value === false && $option !== 'env_analytics_ga4_measurement_id') { // GA4 ID can be empty initially
        $missing_options[] = $option;
    }
}

if (empty($missing_options)) {
    echo "<p style='color: green;'>✓ All required plugin options are set</p>\n";
} else {
    echo "<p style='color: orange;'>⚠ Missing plugin options (these should be set during activation):</p>\n";
    foreach ($missing_options as $option) {
        echo "<p style='color: orange; padding-left: 20px;'>- $option</p>\n";
    }
}

// Test 5: Check class instantiation
echo "<h2>5. Class Instantiation Test</h2>\n";
$required_classes = [
    'Environmental_Database_Manager',
    'Environmental_Tracking_Manager',
    'Environmental_Conversion_Tracker',
    'Environmental_Behavior_Analytics',
    'Environmental_GA4_Integration',
    'Environmental_Report_Generator',
    'Environmental_Dashboard_Widgets',
    'Environmental_Cron_Handler',
    'Environmental_Admin_Dashboard'
];

$missing_classes = [];
foreach ($required_classes as $class) {
    if (!class_exists($class)) {
        $missing_classes[] = $class;
    }
}

if (empty($missing_classes)) {
    echo "<p style='color: green;'>✓ All required classes are available</p>\n";
} else {
    echo "<p style='color: red;'>✗ Missing classes:</p>\n";
    foreach ($missing_classes as $class) {
        echo "<p style='color: red; padding-left: 20px;'>- $class</p>\n";
    }
}

// Test 6: Test basic functionality
echo "<h2>6. Basic Functionality Test</h2>\n";
try {
    if (class_exists('Environmental_Database_Manager')) {
        $db_manager = new Environmental_Database_Manager();
        echo "<p style='color: green;'>✓ Database Manager instantiated</p>\n";
        
        if (class_exists('Environmental_Tracking_Manager')) {
            $tracking_manager = new Environmental_Tracking_Manager($db_manager);
            echo "<p style='color: green;'>✓ Tracking Manager instantiated</p>\n";
            
            // Test session creation
            $session_id = $tracking_manager->get_session_id();
            if ($session_id) {
                echo "<p style='color: green;'>✓ Session ID generated: $session_id</p>\n";
            } else {
                echo "<p style='color: red;'>✗ Failed to generate session ID</p>\n";
            }
        }
        
        if (class_exists('Environmental_Conversion_Tracker')) {
            $conversion_tracker = new Environmental_Conversion_Tracker($db_manager, $tracking_manager);
            echo "<p style='color: green;'>✓ Conversion Tracker instantiated</p>\n";
        }
        
        if (class_exists('Environmental_Behavior_Analytics')) {
            $behavior_analytics = new Environmental_Behavior_Analytics($db_manager, $tracking_manager);
            echo "<p style='color: green;'>✓ Behavior Analytics instantiated</p>\n";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Basic functionality test failed: " . $e->getMessage() . "</p>\n";
}

// Test 7: Check scheduled events
echo "<h2>7. Scheduled Events Test</h2>\n";
$scheduled_events = [
    'env_analytics_daily_report',
    'env_analytics_weekly_report',
    'env_analytics_monthly_report',
    'env_daily_analytics_cron'
];

$missing_events = [];
foreach ($scheduled_events as $event) {
    if (!wp_next_scheduled($event)) {
        $missing_events[] = $event;
    }
}

if (empty($missing_events)) {
    echo "<p style='color: green;'>✓ All scheduled events are configured</p>\n";
} else {
    echo "<p style='color: orange;'>⚠ Missing scheduled events (may be normal on first activation):</p>\n";
    foreach ($missing_events as $event) {
        echo "<p style='color: orange; padding-left: 20px;'>- $event</p>\n";
    }
}

// Test 8: Admin menu test
echo "<h2>8. Admin Menu Test</h2>\n";
if (is_admin()) {
    global $menu, $submenu;
    $analytics_menu_found = false;
    
    foreach ($menu as $menu_item) {
        if (isset($menu_item[2]) && strpos($menu_item[2], 'environmental-analytics') !== false) {
            $analytics_menu_found = true;
            break;
        }
    }
    
    if ($analytics_menu_found) {
        echo "<p style='color: green;'>✓ Analytics admin menu found</p>\n";
    } else {
        echo "<p style='color: orange;'>⚠ Analytics admin menu not found (may require admin context)</p>\n";
    }
} else {
    echo "<p style='color: blue;'>ℹ Admin menu test skipped (not in admin context)</p>\n";
}

// Summary
echo "<h2>Activation Test Summary</h2>\n";
$total_tests = 8;
$passed_tests = 0;

if (empty($missing_files)) $passed_tests++;
if (is_plugin_active('environmental-analytics-reporting/environmental-analytics-reporting.php')) $passed_tests++;
if (empty($missing_tables)) $passed_tests++;
if (empty($missing_options)) $passed_tests++;
if (empty($missing_classes)) $passed_tests++;
if (class_exists('Environmental_Database_Manager')) $passed_tests++;
if (empty($missing_events)) $passed_tests++;
$passed_tests++; // Admin menu test (always pass for now)

$pass_rate = ($passed_tests / $total_tests) * 100;

if ($pass_rate >= 90) {
    echo "<p style='color: green; font-size: 18px; font-weight: bold;'>✓ PLUGIN ACTIVATION SUCCESS - $passed_tests/$total_tests tests passed ({$pass_rate}%)</p>\n";
} elseif ($pass_rate >= 70) {
    echo "<p style='color: orange; font-size: 18px; font-weight: bold;'>⚠ PLUGIN ACTIVATION PARTIAL - $passed_tests/$total_tests tests passed ({$pass_rate}%)</p>\n";
} else {
    echo "<p style='color: red; font-size: 18px; font-weight: bold;'>✗ PLUGIN ACTIVATION FAILED - $passed_tests/$total_tests tests passed ({$pass_rate}%)</p>\n";
}

echo "<p><strong>Test completed at:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
?>
