<?php
echo "PHP Test - Start\n";

try {
    if (file_exists('wp-load.php')) {
        echo "Loading WordPress...\n";
        define('WP_USE_THEMES', false);
        require_once('wp-load.php');
        echo "WordPress loaded!\n";
        
        // Check plugin
        $plugin = 'environmental-platform-petitions/environmental-platform-petitions.php';
        if (is_plugin_active($plugin)) {
            echo "Plugin is active!\n";
        } else {
            echo "Plugin is not active\n";
        }
        
        // Check if class exists
        if (class_exists('EPP_Database')) {
            echo "EPP_Database class found!\n";
        } else {
            echo "EPP_Database class not found\n";
        }
        
    } else {
        echo "wp-load.php not found\n";
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "PHP Test - End\n";
?>
