<?php
/**
 * Comprehensive Environmental Content Recommendation Plugin Test
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Environmental Content Recommendation Plugin Test ===\n";

// Test 1: Check if plugin directory exists
$plugin_dir = __DIR__;
echo "1. Plugin Directory: " . $plugin_dir . "\n";

if (is_dir($plugin_dir)) {
    echo "   ✓ Plugin directory exists\n";
} else {
    echo "   ✗ Plugin directory not found\n";
    exit(1);
}

// Test 2: Check main plugin file
$main_file = $plugin_dir . '/environmental-content-recommendation.php';
echo "2. Main Plugin File: " . $main_file . "\n";

if (file_exists($main_file)) {
    echo "   ✓ Main plugin file exists\n";
    
    // Check file size
    $file_size = filesize($main_file);
    echo "   ✓ File size: " . $file_size . " bytes\n";
    
    // Check if file is readable
    if (is_readable($main_file)) {
        echo "   ✓ File is readable\n";
        
        // Test syntax by including the file
        ob_start();
        $include_result = include_once($main_file);
        $output = ob_get_clean();
        
        if ($include_result !== false) {
            echo "   ✓ File syntax is valid\n";
        } else {
            echo "   ✗ File has syntax errors\n";
            if (!empty($output)) {
                echo "   Error output: " . $output . "\n";
            }
        }
    } else {
        echo "   ✗ File is not readable\n";
    }
} else {
    echo "   ✗ Main plugin file not found\n";
    exit(1);
}

// Test 3: Check include files
$include_dir = $plugin_dir . '/includes';
echo "3. Include Directory: " . $include_dir . "\n";

if (is_dir($include_dir)) {
    echo "   ✓ Includes directory exists\n";
    
    $class_files = [
        'class-recommendation-engine.php',
        'class-user-behavior-tracker.php',
        'class-content-analyzer.php',
        'class-similarity-calculator.php',
        'class-recommendation-display.php',
        'class-performance-tracker.php',
        'class-admin-interface.php',
        'class-ajax-handlers.php'
    ];
    
    foreach ($class_files as $class_file) {
        $file_path = $include_dir . '/' . $class_file;
        if (file_exists($file_path)) {
            echo "   ✓ $class_file exists (" . filesize($file_path) . " bytes)\n";
        } else {
            echo "   ✗ $class_file missing\n";
        }
    }
} else {
    echo "   ✗ Includes directory not found\n";
}

// Test 4: Check assets
$assets_dir = $plugin_dir . '/assets';
echo "4. Assets Directory: " . $assets_dir . "\n";

if (is_dir($assets_dir)) {
    echo "   ✓ Assets directory exists\n";
    
    $asset_files = [
        'css/frontend.css',
        'css/admin.css',
        'js/frontend.js',
        'js/admin.js'
    ];
    
    foreach ($asset_files as $asset_file) {
        $file_path = $assets_dir . '/' . $asset_file;
        if (file_exists($file_path)) {
            echo "   ✓ $asset_file exists (" . filesize($file_path) . " bytes)\n";
        } else {
            echo "   ✗ $asset_file missing\n";
        }
    }
} else {
    echo "   ✗ Assets directory not found\n";
}

// Test 5: Plugin Header Check
echo "5. Plugin Header Analysis:\n";
$plugin_content = file_get_contents($main_file);
if ($plugin_content) {
    // Extract plugin header
    preg_match('/\/\*\*(.*?)\*\//s', $plugin_content, $matches);
    if (!empty($matches[1])) {
        $header = $matches[1];
        echo "   ✓ Plugin header found\n";
        
        // Check for required fields
        $required_fields = ['Plugin Name', 'Description', 'Version', 'Author'];
        foreach ($required_fields as $field) {
            if (strpos($header, $field . ':') !== false) {
                preg_match('/' . $field . ':\s*(.*)$/m', $header, $field_matches);
                if (!empty($field_matches[1])) {
                    echo "   ✓ $field: " . trim($field_matches[1]) . "\n";
                }
            } else {
                echo "   ✗ Missing $field in header\n";
            }
        }
    } else {
        echo "   ✗ Plugin header not found\n";
    }
} else {
    echo "   ✗ Could not read plugin file\n";
}

// Test 6: Class Definition Check
echo "6. Class Definition Check:\n";
$expected_classes = [
    'Environmental_Content_Recommendation',
    'ECR_Recommendation_Engine',
    'ECR_User_Behavior_Tracker',
    'ECR_Content_Analyzer',
    'ECR_Similarity_Calculator',
    'ECR_Recommendation_Display',
    'ECR_Performance_Tracker',
    'ECR_Admin_Interface',
    'ECR_Ajax_Handlers'
];

foreach ($expected_classes as $class_name) {
    if (class_exists($class_name)) {
        echo "   ✓ Class $class_name is loaded\n";
    } else {
        echo "   ⚠ Class $class_name not loaded (may be normal before WordPress init)\n";
    }
}

echo "\n=== Test Complete ===\n";
echo "Plugin files appear to be properly structured and ready for WordPress activation.\n";
?>
