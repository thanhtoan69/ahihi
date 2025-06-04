<?php
// Simple test script
echo "Testing PHP execution...\n";

if (file_exists('wp-config.php')) {
    echo "WordPress config found.\n";
    
    require_once 'wp-config.php';
    echo "Config loaded.\n";
    
    if (defined('DB_NAME')) {
        echo "Database: " . DB_NAME . "\n";
    }
    
    if (file_exists('wp-load.php')) {
        echo "WordPress load file found.\n";
        require_once 'wp-load.php';
        echo "WordPress loaded.\n";
    }
} else {
    echo "WordPress config not found!\n";
}

echo "Test completed.\n";
?>
