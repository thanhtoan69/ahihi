<?php
// Activate the Environmental Platform Events plugin
require_once 'wp-config.php';
require_once 'wp-load.php';

echo "=== Plugin Activation Test ===\n";

// Check current active plugins
$active_plugins = get_option('active_plugins', []);
echo "Currently active plugins:\n";
foreach ($active_plugins as $plugin) {
    echo "  - $plugin\n";
}

$plugin_path = 'environmental-platform-events/environmental-platform-events.php';

// Check if our plugin is in the list
if (in_array($plugin_path, $active_plugins)) {
    echo "\n✅ Environmental Platform Events plugin is already active\n";
} else {
    echo "\n❌ Environmental Platform Events plugin is not active\n";
    echo "Attempting to activate...\n";
    
    // Activate the plugin
    $result = activate_plugin($plugin_path);
    
    if (is_wp_error($result)) {
        echo "❌ Activation failed: " . $result->get_error_message() . "\n";
    } else {
        echo "✅ Plugin activated successfully!\n";
    }
}

// Force reload and test again
if (!class_exists('Environmental_Platform_Events')) {
    echo "\nForce loading plugin file...\n";
    require_once 'wp-content/plugins/environmental-platform-events/environmental-platform-events.php';
    
    if (class_exists('Environmental_Platform_Events')) {
        echo "✅ Plugin class loaded after manual include\n";
        
        // Initialize the plugin
        $instance = Environmental_Platform_Events::get_instance();
        if ($instance) {
            echo "✅ Plugin instance created\n";
        }
    } else {
        echo "❌ Plugin class still not found\n";
    }
}

echo "\n=== Testing Complete ===\n";
?>
