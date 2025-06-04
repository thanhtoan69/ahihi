<?php
/**
 * Comprehensive Event Plugin Test Suite
 * Tests all functionality after plugin activation
 */

// Load WordPress
require_once __DIR__ . '/wp-load.php';

// Enable debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Event Plugin Test Suite</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.test { margin: 10px 0; padding: 10px; border-left: 4px solid #ccc; }
.pass { border-color: #4CAF50; background: #f0f8f0; }
.fail { border-color: #f44336; background: #fdf0f0; }
.info { border-color: #2196F3; background: #f0f8ff; }
h1, h2 { color: #333; }
pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
</style></head><body>";

echo "<h1>Environmental Platform Events - Test Suite</h1>";

function test_result($name, $condition, $message = '') {
    $class = $condition ? 'pass' : 'fail';
    $status = $condition ? '✓ PASS' : '✗ FAIL';
    echo "<div class='test $class'><strong>$name:</strong> $status";
    if ($message) echo " - $message";
    echo "</div>";
    return $condition;
}

function info_result($name, $message) {
    echo "<div class='test info'><strong>$name:</strong> $message</div>";
}

// Test 1: Plugin Activation
echo "<h2>1. Plugin Status</h2>";
$active_plugins = get_option('active_plugins', array());
$plugin_slug = 'environmental-platform-events/environmental-platform-events.php';
$is_active = in_array($plugin_slug, $active_plugins);
test_result('Plugin Active', $is_active, $is_active ? 'Plugin is activated' : 'Plugin needs to be activated');

// Test 2: Class Loading
echo "<h2>2. Class Loading</h2>";
$class_exists = class_exists('Environmental_Platform_Events');
test_result('Main Class', $class_exists, $class_exists ? 'Environmental_Platform_Events class loaded' : 'Class not found');

// Test 3: Post Types
echo "<h2>3. Post Types</h2>";
$event_post_type = post_type_exists('ep_event');
test_result('Event Post Type', $event_post_type, $event_post_type ? 'ep_event post type registered' : 'Post type not registered');

if ($event_post_type) {
    $post_type_obj = get_post_type_object('ep_event');
    info_result('Post Type Details', 'Label: ' . $post_type_obj->labels->name . ', Public: ' . ($post_type_obj->public ? 'Yes' : 'No'));
}

// Test 4: Taxonomies
echo "<h2>4. Taxonomies</h2>";
$event_category = taxonomy_exists('event_category');
$event_tag = taxonomy_exists('event_tag');
test_result('Event Categories', $event_category, $event_category ? 'event_category taxonomy registered' : 'Taxonomy not registered');
test_result('Event Tags', $event_tag, $event_tag ? 'event_tag taxonomy registered' : 'Taxonomy not registered');

// Test 5: Database Tables
echo "<h2>5. Database Tables</h2>";
global $wpdb;
$registration_table = $wpdb->prefix . 'ep_event_registrations';
$analytics_table = $wpdb->prefix . 'ep_event_analytics';

$reg_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$registration_table'") == $registration_table;
$analytics_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$analytics_table'") == $analytics_table;

test_result('Registration Table', $reg_table_exists, $reg_table_exists ? $registration_table . ' exists' : 'Table missing');
test_result('Analytics Table', $analytics_table_exists, $analytics_table_exists ? $analytics_table . ' exists' : 'Table missing');

// Test 6: Enqueued Scripts and Styles
echo "<h2>6. Assets</h2>";
global $wp_scripts, $wp_styles;

// Simulate frontend to check enqueues
$frontend_js = false;
$frontend_css = false;
$admin_js = false;
$admin_css = false;

// Check if scripts would be enqueued
if (is_object($wp_scripts)) {
    $frontend_js = isset($wp_scripts->registered['ep-events-frontend']);
    $admin_js = isset($wp_scripts->registered['ep-events-admin']);
}

if (is_object($wp_styles)) {
    $frontend_css = isset($wp_styles->registered['ep-events-frontend']);  
    $admin_css = isset($wp_styles->registered['ep-events-admin']);
}

test_result('Frontend JS', $frontend_js, 'ep-events-frontend script registration');
test_result('Frontend CSS', $frontend_css, 'ep-events-frontend style registration');
test_result('Admin JS', $admin_js, 'ep-events-admin script registration');
test_result('Admin CSS', $admin_css, 'ep-events-admin style registration');

// Test 7: Template Files
echo "<h2>7. Template Files</h2>";
$template_dir = WP_PLUGIN_DIR . '/environmental-platform-events/templates/';
$templates = array(
    'single-event.php' => 'Single Event Template',
    'archive-events.php' => 'Events Archive Template', 
    'calendar.php' => 'Calendar Template',
    'month-view.php' => 'Month View Template'
);

foreach ($templates as $file => $name) {
    $exists = file_exists($template_dir . $file);
    test_result($name, $exists, $exists ? 'Template file exists' : 'Template file missing');
}

// Test 8: Shortcodes
echo "<h2>8. Shortcodes</h2>";
$shortcodes_to_test = array(
    'ep_events_calendar' => 'Events Calendar',
    'ep_events_list' => 'Events List',
    'ep_single_event' => 'Single Event',
    'ep_event_registration' => 'Event Registration'
);

foreach ($shortcodes_to_test as $shortcode => $name) {
    $exists = shortcode_exists($shortcode);
    test_result($name, $exists, $exists ? "[$shortcode] shortcode registered" : 'Shortcode not registered');
}

// Test 9: AJAX Handlers
echo "<h2>9. AJAX Handlers</h2>";
info_result('AJAX Info', 'AJAX handlers are registered during WordPress init - cannot test directly in CLI mode');

// Test 10: Plugin Options
echo "<h2>10. Plugin Options</h2>";
$plugin_options = get_option('ep_events_options', array());
$has_options = !empty($plugin_options);
test_result('Plugin Options', $has_options, $has_options ? 'Plugin options configured' : 'No plugin options found');

if ($has_options) {
    info_result('Options Data', '<pre>' . print_r($plugin_options, true) . '</pre>');
}

// Test 11: Create Sample Event (if plugin is active)
if ($is_active && $event_post_type) {
    echo "<h2>11. Sample Event Creation</h2>";
    
    $sample_event = array(
        'post_title' => 'Test Environmental Event - ' . date('Y-m-d H:i:s'),
        'post_content' => 'This is a test event created by the plugin test suite.',
        'post_status' => 'publish',
        'post_type' => 'ep_event',
        'meta_input' => array(
            '_ep_event_start_date' => date('Y-m-d', strtotime('+7 days')),
            '_ep_event_start_time' => '10:00:00',
            '_ep_event_end_date' => date('Y-m-d', strtotime('+7 days')),
            '_ep_event_end_time' => '12:00:00',
            '_ep_event_location' => 'Test Venue',
            '_ep_event_max_attendees' => 50,
            '_ep_event_registration_enabled' => 1
        )
    );
    
    $event_id = wp_insert_post($sample_event);
    
    if ($event_id && !is_wp_error($event_id)) {
        test_result('Sample Event Creation', true, "Event created with ID: $event_id");
        info_result('Event URL', get_permalink($event_id));
        
        // Clean up - delete the test event
        wp_delete_post($event_id, true);
        info_result('Cleanup', 'Test event deleted');
    } else {
        test_result('Sample Event Creation', false, is_wp_error($event_id) ? $event_id->get_error_message() : 'Unknown error');
    }
}

// Test 12: File Permissions
echo "<h2>12. File Permissions</h2>";
$plugin_dir = WP_PLUGIN_DIR . '/environmental-platform-events/';
$writable_dirs = array(
    'assets/css/' => 'CSS Assets Directory',
    'assets/js/' => 'JS Assets Directory', 
    'templates/' => 'Templates Directory'
);

foreach ($writable_dirs as $dir => $name) {
    $path = $plugin_dir . $dir;
    $writable = is_writable($path);
    test_result($name, $writable, $writable ? 'Directory is writable' : 'Directory not writable');
}

echo "<h2>Summary</h2>";
echo "<div class='test info'>";
echo "<strong>Test completed at:</strong> " . date('Y-m-d H:i:s') . "<br>";
echo "<strong>WordPress Version:</strong> " . get_bloginfo('version') . "<br>";
echo "<strong>PHP Version:</strong> " . PHP_VERSION . "<br>";
echo "<strong>Plugin Directory:</strong> " . WP_PLUGIN_DIR . "/environmental-platform-events/<br>";
echo "</div>";

echo "</body></html>";
?>
