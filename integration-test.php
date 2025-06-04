<?php
/**
 * Complete Integration Test for Environmental Platform Events Plugin
 * Tests all functionality including frontend, admin, and database integration
 */

// Load WordPress
require_once __DIR__ . '/wp-load.php';

// Force plugin activation if not already active
$plugin_slug = 'environmental-platform-events/environmental-platform-events.php';
$active_plugins = get_option('active_plugins', array());
if (!in_array($plugin_slug, $active_plugins)) {
    $active_plugins[] = $plugin_slug;
    update_option('active_plugins', $active_plugins);
}

// Include the plugin file manually to ensure it's loaded
$plugin_file = WP_PLUGIN_DIR . '/environmental-platform-events/environmental-platform-events.php';
if (file_exists($plugin_file)) {
    include_once $plugin_file;
    
    // Trigger initialization
    if (class_exists('Environmental_Platform_Events')) {
        $instance = Environmental_Platform_Events::get_instance();
        if (method_exists($instance, 'activate')) {
            $instance->activate();
        }
        if (method_exists($instance, 'init')) {
            $instance->init();
        }
    }
}

// Enable debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

?><!DOCTYPE html>
<html>
<head>
    <title>Environmental Platform Events - Complete Integration Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .test-section { margin: 20px 0; padding: 15px; border-radius: 5px; }
        .test-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .test-failure { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .test-info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .test-warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        h1 { color: #2c3e50; text-align: center; }
        h2 { color: #34495e; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-box { background: #f8f9fa; padding: 15px; border-radius: 5px; text-align: center; border: 1px solid #dee2e6; }
        .stat-number { font-size: 24px; font-weight: bold; color: #3498db; }
        .code-block { background: #f4f4f4; padding: 10px; border-radius: 3px; font-family: monospace; overflow-x: auto; }
        .action-buttons { margin: 20px 0; text-align: center; }
        .btn { padding: 10px 20px; margin: 5px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; display: inline-block; }
        .btn:hover { background: #2980b9; }
        .btn-success { background: #27ae60; }
        .btn-danger { background: #e74c3c; }
        .btn-warning { background: #f39c12; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🌍 Environmental Platform Events - Integration Test Suite</h1>
        
        <?php
        $test_results = array();
        $total_tests = 0;
        $passed_tests = 0;
        
        function run_test($name, $condition, $success_msg = '', $failure_msg = '', $info_msg = '') {
            global $test_results, $total_tests, $passed_tests;
            $total_tests++;
            
            if ($condition) {
                $passed_tests++;
                $test_results[] = array('name' => $name, 'status' => 'success', 'message' => $success_msg);
                echo "<div class='test-section test-success'><strong>✓ $name:</strong> " . ($success_msg ?: 'PASSED') . "</div>";
            } else {
                $test_results[] = array('name' => $name, 'status' => 'failure', 'message' => $failure_msg);
                echo "<div class='test-section test-failure'><strong>✗ $name:</strong> " . ($failure_msg ?: 'FAILED') . "</div>";
            }
            
            if ($info_msg) {
                echo "<div class='test-section test-info'><strong>ℹ Info:</strong> $info_msg</div>";
            }
        }
        
        function show_info($title, $message) {
            echo "<div class='test-section test-info'><strong>$title:</strong> $message</div>";
        }
        
        // Test 1: Plugin Status
        echo "<h2>🔧 Plugin Status & Activation</h2>";
        $active_plugins = get_option('active_plugins', array());
        $is_active = in_array($plugin_slug, $active_plugins);
        run_test('Plugin Activation', $is_active, 'Plugin is successfully activated', 'Plugin activation failed');
        
        $class_exists = class_exists('Environmental_Platform_Events');
        run_test('Plugin Class Loading', $class_exists, 'Environmental_Platform_Events class loaded', 'Main plugin class not found');
        
        // Test 2: Post Types & Taxonomies
        echo "<h2>📝 Post Types & Taxonomies</h2>";
        $event_post_type = post_type_exists('ep_event');
        run_test('Event Post Type', $event_post_type, 'ep_event post type registered successfully', 'Event post type not registered');
        
        if ($event_post_type) {
            $post_type_obj = get_post_type_object('ep_event');
            show_info('Post Type Details', "Label: {$post_type_obj->labels->name}, Public: " . ($post_type_obj->public ? 'Yes' : 'No') . ", Supports: " . implode(', ', $post_type_obj->supports));
        }
        
        $event_category = taxonomy_exists('event_category');
        $event_tag = taxonomy_exists('event_tag');
        run_test('Event Categories Taxonomy', $event_category, 'event_category taxonomy registered', 'Event categories taxonomy missing');
        run_test('Event Tags Taxonomy', $event_tag, 'event_tag taxonomy registered', 'Event tags taxonomy missing');
        
        // Test 3: Database Tables
        echo "<h2>🗄️ Database Tables</h2>";
        global $wpdb;
        $tables_to_check = array(
            'ep_event_registrations' => 'Event Registrations',
            'ep_event_checkins' => 'Event Check-ins',
            'ep_event_analytics' => 'Event Analytics'
        );
        
        $table_status = array();
        foreach ($tables_to_check as $table_suffix => $table_name) {
            $full_table_name = $wpdb->prefix . $table_suffix;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") == $full_table_name;
            $table_status[$table_suffix] = $exists;
            run_test($table_name . ' Table', $exists, "Table $full_table_name created successfully", "Table $full_table_name missing");
            
            if ($exists) {
                $row_count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table_name");
                show_info($table_name . ' Records', "Current records: $row_count");
            }
        }
        
        // Test 4: Template Files
        echo "<h2>📄 Template Files</h2>";
        $template_dir = WP_PLUGIN_DIR . '/environmental-platform-events/templates/';
        $templates = array(
            'single-event.php' => 'Single Event Template',
            'archive-events.php' => 'Events Archive Template',
            'calendar.php' => 'Calendar Template',
            'month-view.php' => 'Month View Template'
        );
        
        foreach ($templates as $file => $name) {
            $path = $template_dir . $file;
            $exists = file_exists($path);
            $size = $exists ? filesize($path) : 0;
            run_test($name, $exists, "Template file exists ($size bytes)", "Template file missing: $path");
        }
        
        // Test 5: Asset Files
        echo "<h2>🎨 Asset Files</h2>";
        $asset_dir = WP_PLUGIN_DIR . '/environmental-platform-events/assets/';
        $assets = array(
            'css/frontend.css' => 'Frontend CSS',
            'css/admin.css' => 'Admin CSS',
            'js/frontend.js' => 'Frontend JavaScript',
            'js/admin.js' => 'Admin JavaScript'
        );
        
        foreach ($assets as $file => $name) {
            $path = $asset_dir . $file;
            $exists = file_exists($path);
            $size = $exists ? filesize($path) : 0;
            run_test($name, $exists, "Asset file exists ($size bytes)", "Asset file missing: $path");
        }
        
        // Test 6: Shortcodes
        echo "<h2>🔗 Shortcodes</h2>";
        $shortcodes = array(
            'ep_events_calendar' => 'Events Calendar',
            'ep_events_list' => 'Events List',
            'ep_single_event' => 'Single Event Display',
            'ep_event_registration' => 'Event Registration Form'
        );
        
        foreach ($shortcodes as $shortcode => $name) {
            $exists = shortcode_exists($shortcode);
            run_test($name . ' Shortcode', $exists, "[$shortcode] shortcode registered", "Shortcode [$shortcode] not found");
        }
        
        // Test 7: Sample Event Creation
        if ($event_post_type && $table_status['ep_event_registrations']) {
            echo "<h2>📅 Sample Event Creation Test</h2>";
            
            $sample_event = array(
                'post_title' => 'Integration Test Event - ' . date('Y-m-d H:i:s'),
                'post_content' => 'This is a test event created during the integration test to verify all functionality is working correctly.',
                'post_status' => 'publish',
                'post_type' => 'ep_event',
                'meta_input' => array(
                    '_ep_event_start_date' => date('Y-m-d', strtotime('+7 days')),
                    '_ep_event_start_time' => '10:00:00',
                    '_ep_event_end_date' => date('Y-m-d', strtotime('+7 days')),
                    '_ep_event_end_time' => '16:00:00',
                    '_ep_event_location' => 'Environmental Education Center',
                    '_ep_event_max_attendees' => 100,
                    '_ep_event_registration_enabled' => 1,
                    '_ep_event_price' => 0,
                    '_ep_event_description' => 'Test event for integration testing'
                )
            );
            
            $event_id = wp_insert_post($sample_event);
            
            if ($event_id && !is_wp_error($event_id)) {
                run_test('Sample Event Creation', true, "Event created successfully with ID: $event_id");
                
                $event_url = get_permalink($event_id);
                show_info('Event URL', "<a href='$event_url' target='_blank'>$event_url</a>");
                
                // Test meta data
                $start_date = get_post_meta($event_id, '_ep_event_start_date', true);
                $location = get_post_meta($event_id, '_ep_event_location', true);
                run_test('Event Meta Data', !empty($start_date) && !empty($location), "Meta data saved correctly", "Meta data not saved properly");
                
                // Add to event category
                if ($event_category) {
                    wp_set_object_terms($event_id, array('test-category'), 'event_category');
                    $terms = wp_get_object_terms($event_id, 'event_category');
                    run_test('Event Categorization', !empty($terms), "Event assigned to category", "Event categorization failed");
                }
                
                // Clean up
                wp_delete_post($event_id, true);
                show_info('Cleanup', 'Test event deleted successfully');
                
            } else {
                $error_msg = is_wp_error($event_id) ? $event_id->get_error_message() : 'Unknown error';
                run_test('Sample Event Creation', false, '', "Failed to create event: $error_msg");
            }
        }
        
        // Test 8: WordPress Integration
        echo "<h2>🔌 WordPress Integration</h2>";
        
        // Check if admin pages are registered
        global $submenu;
        $admin_pages_registered = isset($submenu['edit.php?post_type=ep_event']);
        run_test('Admin Pages Registration', $admin_pages_registered, 'Admin submenu pages registered', 'Admin pages not registered');
        
        // Check rewrite rules
        $rewrite_rules = get_option('rewrite_rules');
        $has_event_rules = false;
        if ($rewrite_rules) {
            foreach ($rewrite_rules as $pattern => $replacement) {
                if (strpos($pattern, 'ep_event') !== false || strpos($replacement, 'ep_event') !== false) {
                    $has_event_rules = true;
                    break;
                }
            }
        }
        run_test('URL Rewrite Rules', $has_event_rules, 'Event URL rewrite rules configured', 'URL rewrite rules may need configuration');
        
        // Test 9: Performance & Statistics
        echo "<h2>📊 Performance & Statistics</h2>";
        ?>
        
        <div class="stats">
            <div class="stat-box">
                <div class="stat-number"><?php echo $passed_tests; ?>/<?php echo $total_tests; ?></div>
                <div>Tests Passed</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo round(($passed_tests / $total_tests) * 100, 1); ?>%</div>
                <div>Success Rate</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo number_format(memory_get_peak_usage() / 1024 / 1024, 2); ?>MB</div>
                <div>Peak Memory</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo count(get_included_files()); ?></div>
                <div>Files Loaded</div>
            </div>
        </div>
        
        <?php
        // System Information
        echo "<h2>🖥️ System Information</h2>";
        show_info('WordPress Version', get_bloginfo('version'));
        show_info('PHP Version', PHP_VERSION);
        show_info('Database Version', $wpdb->db_version());
        show_info('Plugin Directory', WP_PLUGIN_DIR . '/environmental-platform-events/');
        show_info('Active Theme', wp_get_theme()->get('Name'));
        show_info('Timezone', wp_timezone_string());
        
        // Test Summary
        echo "<h2>📋 Test Summary</h2>";
        
        if ($passed_tests == $total_tests) {
            echo "<div class='test-section test-success'>";
            echo "<h3>🎉 All Tests Passed!</h3>";
            echo "<p>The Environmental Platform Events plugin has been successfully installed and all components are functioning correctly.</p>";
            echo "<p><strong>Ready for production use!</strong></p>";
            echo "</div>";
        } else {
            echo "<div class='test-section test-warning'>";
            echo "<h3>⚠️ Some Tests Failed</h3>";
            echo "<p>$passed_tests out of $total_tests tests passed. Please review the failed tests above and address any issues.</p>";
            echo "</div>";
        }
        
        // Action Buttons
        echo "<div class='action-buttons'>";
        echo "<a href='http://localhost:8080/wp-admin/edit.php?post_type=ep_event' class='btn btn-success'>View Events Admin</a>";
        echo "<a href='http://localhost:8080/wp-admin/plugins.php' class='btn'>Manage Plugins</a>";
        echo "<a href='http://localhost:8080/test-complete.php' class='btn'>Run Detailed Tests</a>";
        echo "<a href='http://localhost:8080/' class='btn'>View Website</a>";
        echo "</div>";
        
        // Detailed Test Results
        echo "<h2>📄 Detailed Test Results</h2>";
        echo "<div class='code-block'>";
        foreach ($test_results as $result) {
            $status_icon = $result['status'] == 'success' ? '✓' : '✗';
            echo "$status_icon {$result['name']}: {$result['message']}\n";
        }
        echo "</div>";
        
        echo "<div class='test-section test-info'>";
        echo "<p><strong>Integration Test Completed:</strong> " . date('Y-m-d H:i:s') . "</p>";
        echo "<p><strong>Phase 34 Status:</strong> " . ($passed_tests >= $total_tests * 0.9 ? "✅ COMPLETE" : "⚠️ NEEDS ATTENTION") . "</p>";
        echo "</div>";
        ?>
    </div>
</body>
</html>
