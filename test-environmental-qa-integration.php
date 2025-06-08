<?php
/**
 * Environmental Testing & QA Integration Test
 * 
 * Comprehensive integration test to verify all plugin functionality
 * after successful activation.
 * 
 * @package EnvironmentalTestingQA
 * @version 1.0.0
 */

// WordPress setup
require_once dirname(__FILE__) . '/wp-config.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

// Set content type for web output
if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
}

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Environmental Testing & QA - Integration Test</title>\n";
echo "<style>\n";
echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f6f7f7; }\n";
echo ".container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }\n";
echo ".test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 4px; }\n";
echo ".pass { background: #d4edda; border-color: #c3e6cb; color: #155724; }\n";
echo ".fail { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }\n";
echo ".info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }\n";
echo ".warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }\n";
echo "h1, h2, h3 { color: #333; }\n";
echo ".test-result { margin: 10px 0; padding: 8px; border-radius: 4px; }\n";
echo ".dashboard-preview { border: 2px solid #ddd; padding: 15px; margin: 15px 0; border-radius: 6px; }\n";
echo "table { width: 100%; border-collapse: collapse; margin: 10px 0; }\n";
echo "th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }\n";
echo "th { background: #f2f2f2; }\n";
echo ".nav-links { margin: 20px 0; }\n";
echo ".nav-links a { display: inline-block; margin-right: 15px; padding: 10px 20px; background: #0073aa; color: white; text-decoration: none; border-radius: 4px; }\n";
echo ".nav-links a:hover { background: #005177; }\n";
echo "</style></head><body>\n";

echo "<div class='container'>\n";
echo "<h1>🧪 Environmental Testing & QA - Integration Test</h1>\n";
echo "<p><strong>Test Date:</strong> " . date('Y-m-d H:i:s') . "</p>\n";

/**
 * Test Result Tracker for Web Output
 */
class WebTestResults {
    private $passed = 0;
    private $failed = 0;
    private $warnings = 0;
    
    public function test($name, $condition, $message = '', $type = 'test') {
        echo "<div class='test-result " . ($condition ? 'pass' : 'fail') . "'>\n";
        echo "<strong>" . ($condition ? '✓' : '✗') . " {$name}</strong>\n";
        if ($message) {
            echo " - {$message}";
        }
        echo "</div>\n";
        
        if ($condition) {
            $this->passed++;
        } else {
            $this->failed++;
        }
    }
    
    public function info($message) {
        echo "<div class='test-result info'>ℹ️ {$message}</div>\n";
    }
    
    public function warning($message) {
        echo "<div class='test-result warning'>⚠️ {$message}</div>\n";
        $this->warnings++;
    }
    
    public function section($title) {
        echo "<div class='test-section'>\n";
        echo "<h3>{$title}</h3>\n";
    }
    
    public function end_section() {
        echo "</div>\n";
    }
    
    public function summary() {
        $total = $this->passed + $this->failed;
        $success_rate = $total > 0 ? round(($this->passed / $total) * 100, 2) : 0;
        
        echo "<div class='test-section " . ($this->failed === 0 ? 'pass' : 'fail') . "'>\n";
        echo "<h2>📊 Test Summary</h2>\n";
        echo "<table>\n";
        echo "<tr><td><strong>Total Tests</strong></td><td>{$total}</td></tr>\n";
        echo "<tr><td><strong>Passed</strong></td><td>{$this->passed}</td></tr>\n";
        echo "<tr><td><strong>Failed</strong></td><td>{$this->failed}</td></tr>\n";
        echo "<tr><td><strong>Warnings</strong></td><td>{$this->warnings}</td></tr>\n";
        echo "<tr><td><strong>Success Rate</strong></td><td>{$success_rate}%</td></tr>\n";
        echo "</table>\n";
        
        if ($this->failed === 0) {
            echo "<div class='test-result pass'><strong>🎉 All tests passed! The Environmental Testing & QA system is fully functional.</strong></div>\n";
        } else {
            echo "<div class='test-result fail'><strong>❌ Some tests failed. Please review the issues above.</strong></div>\n";
        }
        
        echo "</div>\n";
    }
}

$results = new WebTestResults();

// Test 1: Plugin Status
$results->section("🔌 Plugin Status & Configuration");
$plugin_slug = 'environmental-testing-qa/environmental-testing-qa.php';
$is_active = is_plugin_active($plugin_slug);
$results->test('Plugin Active', $is_active, $is_active ? 'Plugin is activated' : 'Plugin is not active');

if ($is_active) {
    $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_slug);
    $results->test('Plugin Metadata', !empty($plugin_data['Name']), "Name: {$plugin_data['Name']}, Version: {$plugin_data['Version']}");
    
    // Check settings
    $settings = get_option('etq_settings');
    $results->test('Settings Initialized', is_array($settings), $settings ? 'Settings found' : 'No settings found');
    
    $db_version = get_option('etq_db_version');
    $results->test('Database Version', !empty($db_version), "Version: {$db_version}");
}
$results->end_section();

// Test 2: Class Loading & Initialization
$results->section("🏗️ Class Loading & Component Initialization");
$required_classes = [
    'Environmental_Testing_QA' => 'Main Plugin Class',
    'ETQ_Database' => 'Database Manager',
    'ETQ_PHPUnit_Manager' => 'PHPUnit Integration',
    'ETQ_Selenium_Manager' => 'Selenium WebDriver',
    'ETQ_Performance_Tester' => 'Performance Testing',
    'ETQ_Staging_Manager' => 'Staging Environment',
    'ETQ_Test_Suite' => 'Test Suite Management',
    'ETQ_Test_Runner' => 'Test Execution Engine',
    'ETQ_Admin_Dashboard' => 'Admin Interface',
    'ETQ_Documentation' => 'Documentation System'
];

foreach ($required_classes as $class => $description) {
    $loaded = class_exists($class);
    $results->test($description, $loaded, $loaded ? "Class '{$class}' loaded" : "Class '{$class}' not found");
}

// Test singleton instances
try {
    $plugin_instance = Environmental_Testing_QA::get_instance();
    $results->test('Main Plugin Instance', $plugin_instance instanceof Environmental_Testing_QA, 'Singleton pattern working');
    
    if ($plugin_instance) {
        $components = [
            'database' => 'ETQ_Database',
            'phpunit_manager' => 'ETQ_PHPUnit_Manager',
            'selenium_manager' => 'ETQ_Selenium_Manager',
            'performance_tester' => 'ETQ_Performance_Tester',
            'staging_manager' => 'ETQ_Staging_Manager',
            'test_suite' => 'ETQ_Test_Suite',
            'test_runner' => 'ETQ_Test_Runner',
            'admin_dashboard' => 'ETQ_Admin_Dashboard',
            'documentation' => 'ETQ_Documentation'
        ];
        
        foreach ($components as $property => $expected_class) {
            if (property_exists($plugin_instance, $property)) {
                $component = $plugin_instance->$property;
                $initialized = $component instanceof $expected_class;
                $results->test("Component: {$property}", $initialized, $initialized ? "Initialized as {$expected_class}" : 'Not properly initialized');
            } else {
                $results->test("Component: {$property}", false, 'Property does not exist');
            }
        }
    }
} catch (Exception $e) {
    $results->test('Plugin Initialization', false, $e->getMessage());
}
$results->end_section();

// Test 3: Database Structure
$results->section("🗄️ Database Structure & Connectivity");
global $wpdb;

$expected_tables = [
    'etq_test_suites' => 'Test Suite Management',
    'etq_tests' => 'Individual Tests',
    'etq_test_runs' => 'Test Execution Records',
    'etq_test_results' => 'Test Results Storage',
    'etq_performance_benchmarks' => 'Performance Metrics',
    'etq_test_environments' => 'Environment Configuration',
    'etq_coverage_reports' => 'Code Coverage Data',
    'etq_test_configurations' => 'Test Settings',
    'etq_selenium_scripts' => 'Selenium Automation Scripts'
];

foreach ($expected_tables as $table => $description) {
    $table_name = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
    $results->test($description, $exists, $exists ? "Table '{$table}' exists" : "Table '{$table}' missing");
    
    if ($exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        $results->info("Table '{$table}' has {$count} records");
    }
}

// Test database operations
try {
    if (class_exists('ETQ_Database')) {
        $database = ETQ_Database::get_instance();
        $connection_test = $database->test_connection();
        $results->test('Database Connection', $connection_test, $connection_test ? 'Connection successful' : 'Connection failed');
    }
} catch (Exception $e) {
    $results->test('Database Operations', false, $e->getMessage());
}
$results->end_section();

// Test 4: Admin Interface
$results->section("👨‍💼 Admin Interface & Menu Structure");
global $menu, $submenu;

// Check main menu
$admin_menu_found = false;
if (is_array($menu)) {
    foreach ($menu as $menu_item) {
        if (isset($menu_item[2]) && $menu_item[2] === 'environmental-testing-qa') {
            $admin_menu_found = true;
            break;
        }
    }
}
$results->test('Main Admin Menu', $admin_menu_found, $admin_menu_found ? 'Menu registered successfully' : 'Menu not found');

// Check submenus
$expected_submenus = [
    'environmental-testing-qa' => 'Dashboard',
    'etq-test-suites' => 'Test Suites',
    'etq-phpunit' => 'PHPUnit Tests',
    'etq-selenium' => 'Selenium Tests',
    'etq-performance' => 'Performance Testing',
    'etq-staging' => 'Staging Environment',
    'etq-results' => 'Test Results',
    'etq-documentation' => 'Documentation'
];

if (isset($submenu['environmental-testing-qa'])) {
    foreach ($expected_submenus as $slug => $name) {
        $found = false;
        foreach ($submenu['environmental-testing-qa'] as $submenu_item) {
            if (isset($submenu_item[2]) && $submenu_item[2] === $slug) {
                $found = true;
                break;
            }
        }
        $results->test("Submenu: {$name}", $found, $found ? 'Submenu registered' : 'Submenu missing');
    }
} else {
    $results->test('Submenu Structure', false, 'No submenus found');
}

// Test admin URLs
$base_url = admin_url('admin.php?page=environmental-testing-qa');
$results->info("Admin Dashboard URL: <a href='{$base_url}' target='_blank'>{$base_url}</a>");
$results->end_section();

// Test 5: AJAX Endpoints
$results->section("🔄 AJAX Endpoints & Functionality");
$ajax_actions = [
    'etq_get_dashboard_data' => 'Dashboard Data Retrieval',
    'etq_run_quick_test' => 'Quick Test Execution',
    'etq_get_documentation' => 'Documentation Loading',
    'etq_search_docs' => 'Documentation Search',
    'etq_save_configuration' => 'Configuration Saving',
    'etq_delete_test_suite' => 'Test Suite Deletion',
    'etq_run_test_suite' => 'Test Suite Execution'
];

foreach ($ajax_actions as $action => $description) {
    $hook_exists = has_action("wp_ajax_{$action}");
    $results->test($description, $hook_exists !== false, $hook_exists ? "AJAX action '{$action}' registered" : "Action '{$action}' not found");
}
$results->end_section();

// Test 6: File Assets
$results->section("📁 Asset Files & Resources");
$assets_base = WP_PLUGIN_DIR . '/environmental-testing-qa/assets';

$required_assets = [
    'js/admin.js' => 'Admin JavaScript',
    'css/admin.css' => 'Admin Stylesheet'
];

foreach ($required_assets as $file => $description) {
    $file_path = $assets_base . '/' . $file;
    $exists = file_exists($file_path);
    $readable = is_readable($file_path);
    $results->test($description, $exists && $readable, $exists ? 'File exists and readable' : 'File missing or not readable');
    
    if ($exists) {
        $size = filesize($file_path);
        $results->info("File size: " . number_format($size) . " bytes");
    }
}
$results->end_section();

// Test 7: Performance Check
$results->section("⚡ Performance & Resource Usage");
$start_time = microtime(true);
$start_memory = memory_get_usage();

// Simulate operations
if (class_exists('ETQ_Database')) {
    $database = ETQ_Database::get_instance();
    for ($i = 0; $i < 5; $i++) {
        $database->get_test_suites();
    }
}

$end_time = microtime(true);
$end_memory = memory_get_usage();

$execution_time = round(($end_time - $start_time) * 1000, 2);
$memory_used = $end_memory - $start_memory;

$results->test('Execution Time', $execution_time < 500, "{$execution_time}ms (target: <500ms)");
$results->test('Memory Usage', $memory_used < 5242880, number_format($memory_used) . " bytes (target: <5MB)");

$results->info("Current memory usage: " . number_format(memory_get_usage()) . " bytes");
$results->info("Peak memory usage: " . number_format(memory_get_peak_usage()) . " bytes");
$results->end_section();

// Test 8: Environment Information
$results->section("🌍 Environment Information");
echo "<table>\n";
echo "<tr><td><strong>WordPress Version</strong></td><td>" . get_bloginfo('version') . "</td></tr>\n";
echo "<tr><td><strong>PHP Version</strong></td><td>" . PHP_VERSION . "</td></tr>\n";
echo "<tr><td><strong>MySQL Version</strong></td><td>" . $wpdb->db_version() . "</td></tr>\n";
echo "<tr><td><strong>WordPress Memory Limit</strong></td><td>" . WP_MEMORY_LIMIT . "</td></tr>\n";
echo "<tr><td><strong>Plugin Directory</strong></td><td>" . WP_PLUGIN_DIR . "/environmental-testing-qa/</td></tr>\n";
echo "<tr><td><strong>Upload Directory</strong></td><td>" . wp_upload_dir()['basedir'] . "</td></tr>\n";
echo "<tr><td><strong>Active Theme</strong></td><td>" . get_option('stylesheet') . "</td></tr>\n";
echo "<tr><td><strong>Site URL</strong></td><td>" . site_url() . "</td></tr>\n";
echo "</table>\n";
$results->end_section();

// Summary
$results->summary();

// Navigation Links
echo "<div class='nav-links'>\n";
echo "<h3>🔗 Quick Navigation</h3>\n";
echo "<a href='" . admin_url('admin.php?page=environmental-testing-qa') . "'>Testing Dashboard</a>\n";
echo "<a href='" . admin_url('admin.php?page=etq-test-suites') . "'>Test Suites</a>\n";
echo "<a href='" . admin_url('admin.php?page=etq-phpunit') . "'>PHPUnit Tests</a>\n";
echo "<a href='" . admin_url('admin.php?page=etq-selenium') . "'>Selenium Tests</a>\n";
echo "<a href='" . admin_url('admin.php?page=etq-performance') . "'>Performance Testing</a>\n";
echo "<a href='" . admin_url('admin.php?page=etq-staging') . "'>Staging Environment</a>\n";
echo "<a href='" . admin_url('admin.php?page=etq-results') . "'>Test Results</a>\n";
echo "<a href='" . admin_url('admin.php?page=etq-documentation') . "'>Documentation</a>\n";
echo "<a href='" . admin_url('plugins.php') . "'>Plugins Page</a>\n";
echo "<a href='" . admin_url() . "'>WordPress Admin</a>\n";
echo "</div>\n";

echo "</div>\n";
echo "</body></html>\n";
?>
