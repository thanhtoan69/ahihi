<?php
/**
 * Direct plugin activation through WordPress
 */

// Load WordPress
require_once __DIR__ . '/wp-load.php';

// Check if we're admin
if (!current_user_can('activate_plugins')) {
    echo "Error: Need admin privileges to activate plugins\n";
    exit;
}

$plugin_slug = 'environmental-platform-events/environmental-platform-events.php';
$plugin_file = WP_PLUGIN_DIR . '/environmental-platform-events/environmental-platform-events.php';

echo "=== Plugin Activation Script ===\n";
echo "Plugin file: $plugin_file\n";
echo "Plugin exists: " . (file_exists($plugin_file) ? "YES" : "NO") . "\n";

if (!file_exists($plugin_file)) {
    echo "Error: Plugin file not found!\n";
    exit;
}

// Check if already active
$active_plugins = get_option('active_plugins', array());
if (in_array($plugin_slug, $active_plugins)) {
    echo "Plugin is already active\n";
} else {
    echo "Activating plugin...\n";
    
    // Activate the plugin
    $result = activate_plugin($plugin_slug);
    
    if (is_wp_error($result)) {
        echo "Error activating plugin: " . $result->get_error_message() . "\n";
    } else {
        echo "Plugin activated successfully!\n";
        
        // Verify activation
        $active_plugins = get_option('active_plugins', array());
        if (in_array($plugin_slug, $active_plugins)) {
            echo "Verification: Plugin is now active\n";
        } else {
            echo "Verification failed: Plugin not in active list\n";
        }
    }
}

// Check post types after activation
echo "\n=== Post Types Check ===\n";
if (post_type_exists('ep_event')) {
    echo "ep_event post type: REGISTERED ✓\n";
} else {
    echo "ep_event post type: NOT REGISTERED ✗\n";
}

// Check for custom tables
global $wpdb;
$table_name = $wpdb->prefix . 'ep_event_registrations';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
echo "Event registrations table: " . ($table_exists ? "EXISTS ✓" : "NOT EXISTS ✗") . "\n";

echo "\n=== Activation Complete ===\n";
?>
