<?php
// Simple test for Phase 50 plugin
require_once('c:\\xampp\\htdocs\\moitruong\\wp-load.php');

echo "=== Phase 50 Multi-language Support Test ===\n";

// Check if plugin file exists
$plugin_file = 'c:\\xampp\\htdocs\\moitruong\\wp-content\\plugins\\environmental-multilang-support\\environmental-multilang-support.php';
if (file_exists($plugin_file)) {
    echo "✓ Plugin file exists\n";
} else {
    echo "✗ Plugin file not found\n";
    exit(1);
}

// Test plugin activation
$plugin_path = 'environmental-multilang-support/environmental-multilang-support.php';
if (!is_plugin_active($plugin_path)) {
    $result = activate_plugin($plugin_path);
    if (is_wp_error($result)) {
        echo "✗ Plugin activation failed: " . $result->get_error_message() . "\n";
        exit(1);
    } else {
        echo "✓ Plugin activated successfully\n";
    }
} else {
    echo "✓ Plugin already active\n";
}

// Check if main class exists
if (class_exists('Environmental_Multilang_Support')) {
    echo "✓ Main plugin class loaded\n";
    
    $instance = Environmental_Multilang_Support::get_instance();
    if ($instance) {
        echo "✓ Plugin instance created\n";
    } else {
        echo "✗ Failed to create plugin instance\n";
    }
} else {
    echo "✗ Main plugin class not found\n";
}

echo "Test completed.\n";
?>
