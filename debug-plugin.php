<?php
/**
 * Debug script to check plugin loading and identify issues
 */

// Include WordPress core
require_once __DIR__ . '/wp-config.php';
require_once __DIR__ . '/wp-load.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Environmental Platform Events Plugin Debug ===\n\n";

// Check if plugin file exists
$plugin_file = __DIR__ . '/wp-content/plugins/environmental-platform-events/environmental-platform-events.php';
echo "Plugin file exists: " . (file_exists($plugin_file) ? "YES" : "NO") . "\n";

if (file_exists($plugin_file)) {
    echo "Plugin file path: $plugin_file\n";
    
    // Check for syntax errors
    $output = shell_exec("c:\\xampp\\php\\php.exe -l \"$plugin_file\" 2>&1");
    echo "Syntax check: $output\n";
    
    // Try to include the plugin file manually
    echo "Attempting to include plugin file...\n";
    try {
        include_once $plugin_file;
        echo "Plugin file included successfully\n";
        
        // Check if class exists
        if (class_exists('Environmental_Platform_Events')) {
            echo "Environmental_Platform_Events class: EXISTS\n";
            
            // Try to get instance
            $instance = Environmental_Platform_Events::get_instance();
            echo "Plugin instance created: " . (is_object($instance) ? "SUCCESS" : "FAILED") . "\n";
            
        } else {
            echo "Environmental_Platform_Events class: NOT FOUND\n";
        }
        
    } catch (Exception $e) {
        echo "Error including plugin: " . $e->getMessage() . "\n";
    }
}

// Check WordPress plugin status
echo "\n=== WordPress Plugin Status ===\n";
$active_plugins = get_option('active_plugins', array());
echo "Active plugins:\n";
foreach ($active_plugins as $plugin) {
    echo "- $plugin\n";
}

$plugin_slug = 'environmental-platform-events/environmental-platform-events.php';
echo "\nOur plugin active: " . (in_array($plugin_slug, $active_plugins) ? "YES" : "NO") . "\n";

// Check for plugin data
if (function_exists('get_plugin_data')) {
    $plugin_data = get_plugin_data($plugin_file);
    if (!empty($plugin_data['Name'])) {
        echo "Plugin detected by WordPress: YES\n";
        echo "Plugin Name: " . $plugin_data['Name'] . "\n";
        echo "Plugin Version: " . $plugin_data['Version'] . "\n";
    } else {
        echo "Plugin detected by WordPress: NO\n";
    }
}

// Check post types
echo "\n=== Post Types ===\n";
$post_types = get_post_types(array('public' => true), 'names');
echo "Registered post types: " . implode(', ', $post_types) . "\n";

if (post_type_exists('ep_event')) {
    echo "ep_event post type: REGISTERED\n";
} else {
    echo "ep_event post type: NOT REGISTERED\n";
}

echo "\n=== Debug Complete ===\n";
?>
