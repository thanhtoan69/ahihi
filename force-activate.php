<?php
/**
 * Force plugin activation script
 */

// Load WordPress
require_once __DIR__ . '/wp-load.php';

$plugin_slug = 'environmental-platform-events/environmental-platform-events.php';
$plugin_file = WP_PLUGIN_DIR . '/environmental-platform-events/environmental-platform-events.php';

echo "=== Force Plugin Activation ===\n";

// Check if file exists
if (!file_exists($plugin_file)) {
    echo "Error: Plugin file does not exist at: $plugin_file\n";
    exit;
}

// Get current active plugins
$active_plugins = get_option('active_plugins', array());
echo "Current active plugins: " . count($active_plugins) . "\n";

// Add our plugin if not already active
if (!in_array($plugin_slug, $active_plugins)) {
    $active_plugins[] = $plugin_slug;
    $result = update_option('active_plugins', $active_plugins);
    echo "Plugin added to active list: " . ($result ? "SUCCESS" : "FAILED") . "\n";
} else {
    echo "Plugin already in active list\n";
}

// Verify it's now active
$active_plugins = get_option('active_plugins', array());
$is_active = in_array($plugin_slug, $active_plugins);
echo "Plugin is now active: " . ($is_active ? "YES" : "NO") . "\n";

// Try to manually include and initialize the plugin
echo "\n=== Manual Plugin Loading ===\n";
if (file_exists($plugin_file)) {
    // Include the plugin file
    include_once $plugin_file;
    echo "Plugin file included\n";
    
    // Check if class exists
    if (class_exists('Environmental_Platform_Events')) {
        echo "Plugin class exists\n";
        
        // Get instance and trigger activation
        $instance = Environmental_Platform_Events::get_instance();
        if (method_exists($instance, 'activate')) {
            echo "Calling activation method...\n";
            $instance->activate();
            echo "Plugin activation method called\n";
        }
        
        // Trigger init
        if (method_exists($instance, 'init')) {
            echo "Calling init method...\n";
            $instance->init();
            echo "Plugin init method called\n";
        }
        
    } else {
        echo "Plugin class not found\n";
    }
}

// Final status check
echo "\n=== Final Status ===\n";
if (post_type_exists('ep_event')) {
    echo "Event post type: REGISTERED ✓\n";
} else {
    echo "Event post type: NOT REGISTERED ✗\n";
}

// Check database tables
global $wpdb;
$table_name = $wpdb->prefix . 'ep_event_registrations';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
echo "Registration table: " . ($table_exists ? "EXISTS ✓" : "NOT EXISTS ✗") . "\n";

echo "\nActivation process complete!\n";
?>
