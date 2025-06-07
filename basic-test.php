<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting Phase 50 test...\n";

// Test basic functionality
if (file_exists('c:\\xampp\\htdocs\\moitruong\\wp-load.php')) {
    echo "WordPress found\n";
} else {
    echo "WordPress not found\n";
    exit(1);
}

// Check plugin directory
$plugin_dir = 'c:\\xampp\\htdocs\\moitruong\\wp-content\\plugins\\environmental-multilang-support\\';
if (is_dir($plugin_dir)) {
    echo "Plugin directory exists\n";
    
    // List files
    $files = scandir($plugin_dir);
    echo "Plugin files:\n";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "- $file\n";
        }
    }
} else {
    echo "Plugin directory not found\n";
}

echo "Test completed\n";
?>
