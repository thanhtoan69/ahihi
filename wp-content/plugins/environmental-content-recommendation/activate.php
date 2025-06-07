<?php
/**
 * Simple plugin activation script
 */

// Define WordPress paths
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

if (!function_exists('activate_plugin')) {
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

// Plugin path
$plugin_path = 'environmental-content-recommendation/environmental-content-recommendation.php';

echo "Attempting to activate Environmental Content Recommendation plugin...\n";

// Check if plugin file exists
if (file_exists(WP_PLUGIN_DIR . '/' . $plugin_path)) {
    echo "Plugin file found.\n";
    
    // Activate plugin
    $result = activate_plugin($plugin_path);
    
    if (is_wp_error($result)) {
        echo "Error: " . $result->get_error_message() . "\n";
    } else {
        echo "Plugin activated successfully!\n";
        
        // Verify activation
        if (is_plugin_active($plugin_path)) {
            echo "Plugin is now active.\n";
        } else {
            echo "Plugin activation may have failed.\n";
        }
    }
} else {
    echo "Plugin file not found at: " . WP_PLUGIN_DIR . '/' . $plugin_path . "\n";
}
?>
