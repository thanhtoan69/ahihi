<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing WordPress load...\n";

if (file_exists('wp-load.php')) {
    echo "wp-load.php found\n";
    
    try {
        define('WP_USE_THEMES', false);
        require_once('wp-load.php');
        echo "WordPress loaded successfully\n";
        echo "WordPress version: " . get_bloginfo('version') . "\n";
        
        // Try to activate plugin directly through options
        $active_plugins = get_option('active_plugins', array());
        $plugin_file = 'environmental-platform-petitions/environmental-platform-petitions.php';
        
        if (!in_array($plugin_file, $active_plugins)) {
            $active_plugins[] = $plugin_file;
            $updated = update_option('active_plugins', $active_plugins);
            echo "Plugin added to active plugins list: " . ($updated ? 'Success' : 'Failed') . "\n";
        } else {
            echo "Plugin already in active list\n";
        }
        
        // Verify
        $current_active = get_option('active_plugins', array());
        if (in_array($plugin_file, $current_active)) {
            echo "✅ Plugin is now in active plugins list\n";
        } else {
            echo "❌ Plugin not in active plugins list\n";
        }
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "wp-load.php not found\n";
}
?>
