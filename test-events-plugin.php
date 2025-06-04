<?php
/**
 * Test script for Environmental Platform Events Plugin
 * This script tests the event management system functionality
 */

// Load WordPress environment
require_once 'wp-config.php';
require_once 'wp-load.php';

// Set content type
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Events Plugin Test</title></head><body>\n";
echo "<h1>Environmental Platform Events Plugin Test</h1>\n";

// Test 1: Check if plugin is activated
echo "<h2>1. Plugin Activation Test</h2>\n";
if (class_exists('Environmental_Platform_Events')) {
    echo "<p style='color: green;'>✅ Plugin class exists</p>\n";
    
    $plugin_instance = Environmental_Platform_Events::get_instance();
    if ($plugin_instance) {
        echo "<p style='color: green;'>✅ Plugin instance created successfully</p>\n";
    } else {
        echo "<p style='color: red;'>❌ Failed to create plugin instance</p>\n";
    }
} else {
    echo "<p style='color: red;'>❌ Plugin class not found</p>\n";
}

// Test 2: Check post type registration
echo "<h2>2. Post Type Registration Test</h2>\n";
if (post_type_exists('ep_event')) {
    echo "<p style='color: green;'>✅ Event post type registered</p>\n";
    
    $post_type = get_post_type_object('ep_event');
    echo "<p>Post type labels: " . json_encode($post_type->labels) . "</p>\n";
} else {
    echo "<p style='color: red;'>❌ Event post type not registered</p>\n";
}

// Test 3: Check taxonomies
echo "<h2>3. Taxonomy Registration Test</h2>\n";
$taxonomies = ['event_category', 'event_location', 'event_type'];
foreach ($taxonomies as $taxonomy) {
    if (taxonomy_exists($taxonomy)) {
        echo "<p style='color: green;'>✅ Taxonomy '{$taxonomy}' registered</p>\n";
    } else {
        echo "<p style='color: red;'>❌ Taxonomy '{$taxonomy}' not registered</p>\n";
    }
}

// Test 4: Check asset files
echo "<h2>4. Asset Files Test</h2>\n";
$assets = [
    'CSS Frontend' => 'wp-content/plugins/environmental-platform-events/assets/css/frontend.css',
    'JS Frontend' => 'wp-content/plugins/environmental-platform-events/assets/js/frontend.js',
    'CSS Admin' => 'wp-content/plugins/environmental-platform-events/assets/css/admin.css',
    'JS Admin' => 'wp-content/plugins/environmental-platform-events/assets/js/admin.js'
];

foreach ($assets as $name => $path) {
    if (file_exists(ABSPATH . $path)) {
        echo "<p style='color: green;'>✅ {$name} exists</p>\n";
        $size = filesize(ABSPATH . $path);
        echo "<p>&nbsp;&nbsp;&nbsp;Size: " . number_format($size) . " bytes</p>\n";
    } else {
        echo "<p style='color: red;'>❌ {$name} missing</p>\n";
    }
}

// Test 5: Check template files
echo "<h2>5. Template Files Test</h2>\n";
$templates = [
    'Single Event' => 'wp-content/plugins/environmental-platform-events/templates/single-event.php',
    'Events Archive' => 'wp-content/plugins/environmental-platform-events/templates/archive-events.php',
    'Calendar' => 'wp-content/plugins/environmental-platform-events/templates/calendar.php',
    'Month View' => 'wp-content/plugins/environmental-platform-events/templates/month-view.php'
];

foreach ($templates as $name => $path) {
    if (file_exists(ABSPATH . $path)) {
        echo "<p style='color: green;'>✅ {$name} template exists</p>\n";
        $size = filesize(ABSPATH . $path);
        echo "<p>&nbsp;&nbsp;&nbsp;Size: " . number_format($size) . " bytes</p>\n";
    } else {
        echo "<p style='color: red;'>❌ {$name} template missing</p>\n";
    }
}

// Test 6: Create test event
echo "<h2>6. Event Creation Test</h2>\n";
$test_event_id = wp_insert_post([
    'post_title' => 'Test Environmental Event - ' . date('Y-m-d H:i:s'),
    'post_content' => 'This is a test event to verify the event management system.',
    'post_status' => 'publish',
    'post_type' => 'ep_event',
    'meta_input' => [
        '_event_start_date' => date('Y-m-d'),
        '_event_end_date' => date('Y-m-d', strtotime('+1 day')),
        '_event_start_time' => '10:00',
        '_event_end_time' => '16:00',
        '_event_location' => 'Test Location',
        '_event_max_participants' => 50,
        '_event_price' => 0,
        '_event_registration_required' => 'yes'
    ]
]);

if ($test_event_id && !is_wp_error($test_event_id)) {
    echo "<p style='color: green;'>✅ Test event created successfully (ID: {$test_event_id})</p>\n";
    echo "<p><a href='/wp-admin/post.php?post={$test_event_id}&action=edit' target='_blank'>Edit event in admin</a></p>\n";
    echo "<p><a href='/?p={$test_event_id}' target='_blank'>View event on frontend</a></p>\n";
} else {
    echo "<p style='color: red;'>❌ Failed to create test event</p>\n";
    if (is_wp_error($test_event_id)) {
        echo "<p>Error: " . $test_event_id->get_error_message() . "</p>\n";
    }
}

// Test 7: Check shortcodes
echo "<h2>7. Shortcode Registration Test</h2>\n";
global $shortcode_tags;
$event_shortcodes = ['ep_events_calendar', 'ep_event_list', 'ep_event_registration'];

foreach ($event_shortcodes as $shortcode) {
    if (isset($shortcode_tags[$shortcode])) {
        echo "<p style='color: green;'>✅ Shortcode [{$shortcode}] registered</p>\n";
    } else {
        echo "<p style='color: red;'>❌ Shortcode [{$shortcode}] not registered</p>\n";
    }
}

// Test 8: Database tables
echo "<h2>8. Database Tables Test</h2>\n";
global $wpdb;
$table_prefix = $wpdb->prefix . 'ep_';
$tables = [
    'event_registrations',
    'event_analytics', 
    'event_check_ins'
];

foreach ($tables as $table) {
    $table_name = $table_prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
    
    if ($exists) {
        echo "<p style='color: green;'>✅ Table '{$table_name}' exists</p>\n";
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        echo "<p>&nbsp;&nbsp;&nbsp;Records: {$count}</p>\n";
    } else {
        echo "<p style='color: red;'>❌ Table '{$table_name}' missing</p>\n";
    }
}

// Test 9: Plugin options
echo "<h2>9. Plugin Options Test</h2>\n";
$options = [
    'ep_events_google_maps_api_key',
    'ep_events_email_notifications',
    'ep_events_qr_code_enabled',
    'ep_events_analytics_enabled'
];

foreach ($options as $option) {
    $value = get_option($option);
    if ($value !== false) {
        echo "<p style='color: green;'>✅ Option '{$option}' exists</p>\n";
        echo "<p>&nbsp;&nbsp;&nbsp;Value: " . (is_array($value) ? json_encode($value) : $value) . "</p>\n";
    } else {
        echo "<p style='color: orange;'>⚠️ Option '{$option}' not set (may be intentional)</p>\n";
    }
}

// Test 10: AJAX endpoints
echo "<h2>10. AJAX Endpoints Test</h2>\n";
$ajax_actions = [
    'ep_register_for_event',
    'ep_cancel_event_registration',
    'ep_get_calendar_events',
    'ep_check_in_event'
];

foreach ($ajax_actions as $action) {
    if (has_action("wp_ajax_{$action}") || has_action("wp_ajax_nopriv_{$action}")) {
        echo "<p style='color: green;'>✅ AJAX action '{$action}' registered</p>\n";
    } else {
        echo "<p style='color: red;'>❌ AJAX action '{$action}' not registered</p>\n";
    }
}

echo "<h2>Test Summary</h2>\n";
echo "<p>Testing completed. Check the results above to identify any issues.</p>\n";
echo "<p><strong>Next steps:</strong></p>\n";
echo "<ul>\n";
echo "<li>If all tests pass, the plugin is ready for use</li>\n";
echo "<li>If some tests fail, review the plugin code and configuration</li>\n";
echo "<li>Test frontend functionality by visiting event pages</li>\n";
echo "<li>Test admin functionality in the WordPress dashboard</li>\n";
echo "</ul>\n";

echo "</body></html>\n";
?>
