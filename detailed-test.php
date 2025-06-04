<?php
// Detailed WordPress and Plugin Test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Environmental Platform Events Plugin Test ===\n";
echo "Testing WordPress and Plugin Integration...\n\n";

try {
    // Test 1: Load WordPress
    echo "1. Loading WordPress...\n";
    if (!file_exists('wp-config.php')) {
        throw new Exception("wp-config.php not found!");
    }
    
    require_once 'wp-config.php';
    echo "   ✅ wp-config.php loaded\n";
    
    require_once 'wp-load.php';
    echo "   ✅ WordPress loaded successfully\n";
    echo "   WordPress version: " . get_bloginfo('version') . "\n\n";
    
    // Test 2: Check plugin file
    echo "2. Checking plugin files...\n";
    $plugin_file = 'wp-content/plugins/environmental-platform-events/environmental-platform-events.php';
    if (file_exists($plugin_file)) {
        echo "   ✅ Plugin file exists\n";
        echo "   File size: " . number_format(filesize($plugin_file)) . " bytes\n";
    } else {
        echo "   ❌ Plugin file not found at: $plugin_file\n";
    }
    
    // Test 3: Check if plugin class exists
    echo "\n3. Checking plugin class...\n";
    if (class_exists('Environmental_Platform_Events')) {
        echo "   ✅ Plugin class exists\n";
        
        $instance = Environmental_Platform_Events::get_instance();
        if ($instance) {
            echo "   ✅ Plugin instance created\n";
        } else {
            echo "   ❌ Failed to create plugin instance\n";
        }
    } else {
        echo "   ❌ Plugin class not found\n";
        echo "   Available classes: " . implode(', ', array_slice(get_declared_classes(), -10)) . "\n";
    }
    
    // Test 4: Check post type
    echo "\n4. Checking post type registration...\n";
    if (post_type_exists('ep_event')) {
        echo "   ✅ Event post type registered\n";
        $post_type = get_post_type_object('ep_event');
        echo "   Post type public: " . ($post_type->public ? 'Yes' : 'No') . "\n";
    } else {
        echo "   ❌ Event post type not registered\n";
    }
    
    // Test 5: Check assets
    echo "\n5. Checking asset files...\n";
    $assets = [
        'frontend.css' => 'wp-content/plugins/environmental-platform-events/assets/css/frontend.css',
        'frontend.js' => 'wp-content/plugins/environmental-platform-events/assets/js/frontend.js',
        'admin.css' => 'wp-content/plugins/environmental-platform-events/assets/css/admin.css',
        'admin.js' => 'wp-content/plugins/environmental-platform-events/assets/js/admin.js'
    ];
    
    foreach ($assets as $name => $path) {
        if (file_exists($path)) {
            echo "   ✅ $name exists (" . number_format(filesize($path)) . " bytes)\n";
        } else {
            echo "   ❌ $name missing at: $path\n";
        }
    }
    
    // Test 6: Check templates
    echo "\n6. Checking template files...\n";
    $templates = [
        'single-event.php' => 'wp-content/plugins/environmental-platform-events/templates/single-event.php',
        'archive-events.php' => 'wp-content/plugins/environmental-platform-events/templates/archive-events.php',
        'calendar.php' => 'wp-content/plugins/environmental-platform-events/templates/calendar.php',
        'month-view.php' => 'wp-content/plugins/environmental-platform-events/templates/month-view.php'
    ];
    
    foreach ($templates as $name => $path) {
        if (file_exists($path)) {
            echo "   ✅ $name exists (" . number_format(filesize($path)) . " bytes)\n";
        } else {
            echo "   ❌ $name missing at: $path\n";
        }
    }
    
    echo "\n=== Test Completed ===\n";
    echo "If you see mostly ✅ marks, the plugin is set up correctly!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>
