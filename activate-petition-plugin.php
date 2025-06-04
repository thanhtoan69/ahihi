<?php
/**
 * Plugin Activation Script
 * 
 * This script manually activates the Environmental Platform Petitions plugin
 */

// Load WordPress
require_once('wp-config.php');

// Get the plugin file
$plugin_file = 'environmental-platform-petitions/environmental-platform-petitions.php';

// Check if plugin exists
$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
if (!file_exists($plugin_path)) {
    echo "❌ Plugin file not found: " . $plugin_path . "\n";
    exit(1);
}

// Get currently active plugins
$active_plugins = get_option('active_plugins', array());

// Check if already active
if (in_array($plugin_file, $active_plugins)) {
    echo "✅ Plugin is already active\n";
} else {
    // Add to active plugins
    $active_plugins[] = $plugin_file;
    update_option('active_plugins', $active_plugins);
    echo "✅ Plugin activated successfully\n";
    
    // Trigger activation hook
    if (function_exists('do_action')) {
        do_action('activate_' . $plugin_file);
    }
}

// Verify activation
$active_plugins = get_option('active_plugins', array());
if (in_array($plugin_file, $active_plugins)) {
    echo "✅ Plugin activation verified\n";
} else {
    echo "❌ Plugin activation failed\n";
    exit(1);
}

echo "🎉 Plugin activation complete!\n";
?>
