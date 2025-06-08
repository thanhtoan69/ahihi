<?php
/**
 * Environmental Testing & QA Plugin Activation and Testing Script
 * 
 * This script activates the Environmental Testing & QA plugin and runs
 * comprehensive tests to verify all functionality is working correctly.
 * 
 * @package EnvironmentalTestingQA
 * @version 1.0.0
 */

// Basic WordPress setup
require_once dirname(__FILE__) . '/wp-config.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/file.php';

// Prevent timeout for long-running operations
set_time_limit(300);
ini_set('memory_limit', '512M');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Environmental Testing & QA Plugin Activation and Testing</h1>\n";
echo "<pre>\n";

/**
 * Test Results Tracker
 */
class TestResults {
    private $tests = [];
    private $passed = 0;
    private $failed = 0;
    
    public function add_test($name, $passed, $message = '') {
        $this->tests[] = [
            'name' => $name,
            'passed' => $passed,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($passed) {
            $this->passed++;
            echo "✓ PASS: {$name}\n";
        } else {
            $this->failed++;
            echo "✗ FAIL: {$name}";
            if ($message) {
                echo " - {$message}";
            }
            echo "\n";
        }
        
        if ($message && $passed) {
            echo "  Info: {$message}\n";
        }
    }
    
    public function get_summary() {
        $total = $this->passed + $this->failed;
        $success_rate = $total > 0 ? round(($this->passed / $total) * 100, 2) : 0;
        
        return [
            'total' => $total,
            'passed' => $this->passed,
            'failed' => $this->failed,
            'success_rate' => $success_rate,
            'tests' => $this->tests
        ];
    }
    
    public function print_summary() {
        $summary = $this->get_summary();
        
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "TEST SUMMARY\n";
        echo str_repeat("=", 60) . "\n";
        echo "Total Tests: {$summary['total']}\n";
        echo "Passed: {$summary['passed']}\n";
        echo "Failed: {$summary['failed']}\n";
        echo "Success Rate: {$summary['success_rate']}%\n";
        
        if ($summary['failed'] > 0) {
            echo "\nFAILED TESTS:\n";
            foreach ($summary['tests'] as $test) {
                if (!$test['passed']) {
                    echo "- {$test['name']}: {$test['message']}\n";
                }
            }
        }
        
        echo str_repeat("=", 60) . "\n";
    }
}

$results = new TestResults();

// Step 1: Check plugin files
echo "Step 1: Checking plugin files...\n";
$plugin_path = WP_PLUGIN_DIR . '/environmental-testing-qa/environmental-testing-qa.php';
$results->add_test(
    'Plugin main file exists',
    file_exists($plugin_path),
    $plugin_path
);

$required_files = [
    'includes/class-etq-database.php',
    'includes/class-etq-phpunit-manager.php',
    'includes/class-etq-selenium-manager.php',
    'includes/class-etq-performance-tester.php',
    'includes/class-etq-staging-manager.php',
    'includes/class-etq-test-suite.php',
    'includes/class-etq-test-runner.php',
    'includes/class-etq-admin-dashboard.php',
    'includes/class-etq-documentation.php',
    'assets/js/admin.js',
    'assets/css/admin.css'
];

foreach ($required_files as $file) {
    $file_path = WP_PLUGIN_DIR . '/environmental-testing-qa/' . $file;
    $results->add_test(
        "Required file: {$file}",
        file_exists($file_path),
        file_exists($file_path) ? 'Found' : 'Missing'
    );
}

// Step 2: Activate plugin
echo "\nStep 2: Activating plugin...\n";
$plugin_slug = 'environmental-testing-qa/environmental-testing-qa.php';

if (!is_plugin_active($plugin_slug)) {
    $activation_result = activate_plugin($plugin_slug);
    if (is_wp_error($activation_result)) {
        $results->add_test(
            'Plugin activation',
            false,
            $activation_result->get_error_message()
        );
    } else {
        $results->add_test('Plugin activation', true, 'Successfully activated');
    }
} else {
    $results->add_test('Plugin activation', true, 'Already active');
}

// Step 3: Check database tables
echo "\nStep 3: Checking database tables...\n";
global $wpdb;

$expected_tables = [
    'etq_test_suites',
    'etq_tests',
    'etq_test_runs',
    'etq_test_results',
    'etq_performance_benchmarks',
    'etq_test_environments',
    'etq_coverage_reports',
    'etq_test_configurations',
    'etq_selenium_scripts'
];

foreach ($expected_tables as $table) {
    $table_name = $wpdb->prefix . $table;
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
    $results->add_test(
        "Database table: {$table}",
        $table_exists,
        $table_exists ? 'Created' : 'Missing'
    );
}

// Step 4: Test class loading
echo "\nStep 4: Testing class loading...\n";
$required_classes = [
    'Environmental_Testing_QA',
    'ETQ_Database',
    'ETQ_PHPUnit_Manager',
    'ETQ_Selenium_Manager',
    'ETQ_Performance_Tester',
    'ETQ_Staging_Manager',
    'ETQ_Test_Suite',
    'ETQ_Test_Runner',
    'ETQ_Admin_Dashboard',
    'ETQ_Documentation'
];

foreach ($required_classes as $class) {
    $results->add_test(
        "Class exists: {$class}",
        class_exists($class),
        class_exists($class) ? 'Loaded' : 'Not found'
    );
}

// Step 5: Test plugin initialization
echo "\nStep 5: Testing plugin initialization...\n";
try {
    $plugin_instance = Environmental_Testing_QA::get_instance();
    $results->add_test(
        'Plugin singleton instance',
        $plugin_instance instanceof Environmental_Testing_QA,
        'Plugin instance created'
    );
    
    // Test component initialization
    $components = [
        'database' => 'ETQ_Database',
        'phpunit_manager' => 'ETQ_PHPUnit_Manager',
        'selenium_manager' => 'ETQ_Selenium_Manager',
        'performance_tester' => 'ETQ_Performance_Tester',
        'staging_manager' => 'ETQ_Staging_Manager',
        'test_suite' => 'ETQ_Test_Suite',
        'test_runner' => 'ETQ_Test_Runner',
        'documentation' => 'ETQ_Documentation',
        'admin_dashboard' => 'ETQ_Admin_Dashboard'
    ];
    
    foreach ($components as $property => $expected_class) {
        if (property_exists($plugin_instance, $property) && $plugin_instance->$property) {
            $results->add_test(
                "Component: {$property}",
                $plugin_instance->$property instanceof $expected_class,
                get_class($plugin_instance->$property)
            );
        } else {
            $results->add_test(
                "Component: {$property}",
                false,
                'Component not initialized'
            );
        }
    }
} catch (Exception $e) {
    $results->add_test(
        'Plugin initialization',
        false,
        $e->getMessage()
    );
}

// Step 6: Test database operations
echo "\nStep 6: Testing database operations...\n";
try {
    if (class_exists('ETQ_Database')) {
        $database = ETQ_Database::get_instance();
        
        // Test database connection
        $connection_test = $database->test_connection();
        $results->add_test(
            'Database connection',
            $connection_test,
            $connection_test ? 'Connected' : 'Connection failed'
        );
        
        // Test basic operations
        $test_suite_data = [
            'name' => 'Activation Test Suite',
            'description' => 'Test suite created during plugin activation',
            'created_by' => get_current_user_id()
        ];
        
        $suite_id = $database->create_test_suite($test_suite_data);
        $results->add_test(
            'Create test suite',
            $suite_id !== false,
            $suite_id ? "Created with ID: {$suite_id}" : 'Failed to create'
        );
        
        if ($suite_id) {
            $suite = $database->get_test_suite($suite_id);
            $results->add_test(
                'Retrieve test suite',
                $suite !== false && $suite['name'] === $test_suite_data['name'],
                $suite ? 'Retrieved successfully' : 'Failed to retrieve'
            );
            
            // Clean up test data
            $database->delete_test_suite($suite_id);
        }
    }
} catch (Exception $e) {
    $results->add_test(
        'Database operations',
        false,
        $e->getMessage()
    );
}

// Step 7: Test admin interface
echo "\nStep 7: Testing admin interface...\n";
try {
    // Check if admin menu is registered
    global $menu, $submenu;
    $admin_menu_found = false;
    
    if (is_array($menu)) {
        foreach ($menu as $menu_item) {
            if (isset($menu_item[2]) && $menu_item[2] === 'environmental-testing-qa') {
                $admin_menu_found = true;
                break;
            }
        }
    }
    
    $results->add_test(
        'Admin menu registration',
        $admin_menu_found,
        $admin_menu_found ? 'Menu registered' : 'Menu not found'
    );
    
    // Check submenu items
    $expected_submenus = [
        'etq-test-suites',
        'etq-phpunit',
        'etq-selenium',
        'etq-performance',
        'etq-staging',
        'etq-results',
        'etq-documentation'
    ];
    
    foreach ($expected_submenus as $submenu_slug) {
        $submenu_found = isset($submenu['environmental-testing-qa']) &&
                        array_search($submenu_slug, array_column($submenu['environmental-testing-qa'], 2)) !== false;
        
        $results->add_test(
            "Submenu: {$submenu_slug}",
            $submenu_found,
            $submenu_found ? 'Registered' : 'Not found'
        );
    }
} catch (Exception $e) {
    $results->add_test(
        'Admin interface',
        false,
        $e->getMessage()
    );
}

// Step 8: Test asset loading
echo "\nStep 8: Testing asset loading...\n";
$assets_dir = WP_PLUGIN_DIR . '/environmental-testing-qa/assets';
$js_file = $assets_dir . '/js/admin.js';
$css_file = $assets_dir . '/css/admin.css';

$results->add_test(
    'JavaScript file readable',
    is_readable($js_file),
    is_readable($js_file) ? 'File accessible' : 'File not accessible'
);

$results->add_test(
    'CSS file readable',
    is_readable($css_file),
    is_readable($css_file) ? 'File accessible' : 'File not accessible'
);

// Check file sizes
if (is_readable($js_file)) {
    $js_size = filesize($js_file);
    $results->add_test(
        'JavaScript file size',
        $js_size > 1000,
        "Size: " . number_format($js_size) . " bytes"
    );
}

if (is_readable($css_file)) {
    $css_size = filesize($css_file);
    $results->add_test(
        'CSS file size',
        $css_size > 1000,
        "Size: " . number_format($css_size) . " bytes"
    );
}

// Step 9: Test AJAX endpoints
echo "\nStep 9: Testing AJAX endpoints...\n";
$ajax_actions = [
    'etq_get_dashboard_data',
    'etq_run_quick_test',
    'etq_get_documentation',
    'etq_search_docs'
];

foreach ($ajax_actions as $action) {
    $hook_exists = has_action("wp_ajax_{$action}");
    $results->add_test(
        "AJAX action: {$action}",
        $hook_exists !== false,
        $hook_exists ? 'Hook registered' : 'Hook not found'
    );
}

// Step 10: Test WordPress integration
echo "\nStep 10: Testing WordPress integration...\n";

// Check plugin data
$plugin_data = get_plugin_data($plugin_path);
$results->add_test(
    'Plugin metadata',
    !empty($plugin_data['Name']) && !empty($plugin_data['Version']),
    "Name: {$plugin_data['Name']}, Version: {$plugin_data['Version']}"
);

// Check options
$etq_settings = get_option('etq_settings');
$results->add_test(
    'Plugin settings',
    is_array($etq_settings) && !empty($etq_settings),
    $etq_settings ? 'Settings initialized' : 'Settings not found'
);

$etq_db_version = get_option('etq_db_version');
$results->add_test(
    'Database version option',
    !empty($etq_db_version),
    $etq_db_version ? "Version: {$etq_db_version}" : 'Version not set'
);

// Step 11: Performance check
echo "\nStep 11: Performance check...\n";
$start_memory = memory_get_usage();
$start_time = microtime(true);

// Simulate some plugin operations
if (class_exists('ETQ_Database')) {
    $database = ETQ_Database::get_instance();
    for ($i = 0; $i < 10; $i++) {
        $database->get_test_suites();
    }
}

$end_time = microtime(true);
$end_memory = memory_get_usage();

$execution_time = round(($end_time - $start_time) * 1000, 2);
$memory_used = $end_memory - $start_memory;

$results->add_test(
    'Execution time',
    $execution_time < 1000,
    "{$execution_time}ms"
);

$results->add_test(
    'Memory usage',
    $memory_used < 10485760, // 10MB
    number_format($memory_used) . " bytes"
);

// Final summary
echo "\n";
$results->print_summary();

// Additional information
echo "\nADDITIONAL INFORMATION:\n";
echo str_repeat("-", 60) . "\n";
echo "WordPress Version: " . get_bloginfo('version') . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "MySQL Version: " . $wpdb->db_version() . "\n";
echo "Plugin Directory: " . WP_PLUGIN_DIR . "/environmental-testing-qa/\n";
echo "Plugin URL: " . admin_url('admin.php?page=environmental-testing-qa') . "\n";
echo "Test Completed: " . date('Y-m-d H:i:s') . "\n";

// Recommendations
$summary = $results->get_summary();
if ($summary['failed'] > 0) {
    echo "\nRECOMMENDATIONS:\n";
    echo str_repeat("-", 60) . "\n";
    echo "1. Check failed tests above for specific issues\n";
    echo "2. Verify file permissions in plugin directory\n";
    echo "3. Check WordPress error logs for detailed error messages\n";
    echo "4. Ensure all plugin dependencies are installed\n";
    echo "5. Try deactivating and reactivating the plugin\n";
} else {
    echo "\n🎉 CONGRATULATIONS!\n";
    echo str_repeat("-", 60) . "\n";
    echo "All tests passed! The Environmental Testing & QA plugin\n";
    echo "has been successfully activated and is ready for use.\n";
    echo "\nNext steps:\n";
    echo "1. Visit the admin dashboard: " . admin_url('admin.php?page=environmental-testing-qa') . "\n";
    echo "2. Configure your testing environments\n";
    echo "3. Create your first test suite\n";
    echo "4. Run initial smoke tests\n";
}

echo "\n</pre>";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Environmental Testing & QA - Activation Complete</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: #00a32a; font-weight: bold; }
        .error { color: #d63638; font-weight: bold; }
        .info { color: #0073aa; }
        pre { background: #f6f7f7; padding: 20px; border-radius: 4px; }
        .nav-links { margin: 20px 0; }
        .nav-links a { 
            display: inline-block; 
            margin-right: 15px; 
            padding: 10px 20px; 
            background: #0073aa; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px; 
        }
        .nav-links a:hover { background: #005177; }
    </style>
</head>
<body>
    <div class="nav-links">
        <a href="<?php echo admin_url('admin.php?page=environmental-testing-qa'); ?>">Open Testing Dashboard</a>
        <a href="<?php echo admin_url('plugins.php'); ?>">Plugins Page</a>
        <a href="<?php echo admin_url(); ?>">WordPress Admin</a>
        <a href="<?php echo home_url(); ?>">Visit Site</a>
    </div>
</body>
</html>
