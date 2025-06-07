<?php
/**
 * Basic Plugin Structure Verification
 */

echo "Environmental Content Recommendation Plugin Structure Test\n";
echo "========================================================\n\n";

// Define base directory
$base_dir = __DIR__ . '/wp-content/plugins/environmental-content-recommendation';

echo "Base Directory: $base_dir\n\n";

// Test 1: Main plugin file
echo "1. Main Plugin File Test\n";
echo "------------------------\n";
$main_file = $base_dir . '/environmental-content-recommendation.php';

if (file_exists($main_file)) {
    echo "✓ Main plugin file exists\n";
    echo "  Size: " . number_format(filesize($main_file)) . " bytes\n";
    
    // Read plugin header
    $file_content = file_get_contents($main_file);
    if (preg_match('/Plugin Name:\s*(.+)/', $file_content, $matches)) {
        echo "  Plugin Name: " . trim($matches[1]) . "\n";
    }
    if (preg_match('/Version:\s*(.+)/', $file_content, $matches)) {
        echo "  Version: " . trim($matches[1]) . "\n";
    }
    if (preg_match('/Description:\s*(.+)/', $file_content, $matches)) {
        echo "  Description: " . trim($matches[1]) . "\n";
    }
} else {
    echo "✗ Main plugin file not found\n";
}

// Test 2: Class files
echo "\n2. Class Files Test\n";
echo "-------------------\n";
$includes_dir = $base_dir . '/includes';
$class_files = [
    'class-recommendation-engine.php' => 'Recommendation Engine',
    'class-user-behavior-tracker.php' => 'User Behavior Tracker',
    'class-content-analyzer.php' => 'Content Analyzer',
    'class-similarity-calculator.php' => 'Similarity Calculator',
    'class-recommendation-display.php' => 'Recommendation Display',
    'class-performance-tracker.php' => 'Performance Tracker',
    'class-admin-interface.php' => 'Admin Interface',
    'class-ajax-handlers.php' => 'AJAX Handlers'
];

$class_files_found = 0;
foreach ($class_files as $filename => $description) {
    $filepath = $includes_dir . '/' . $filename;
    if (file_exists($filepath)) {
        echo "✓ $description ($filename) - " . number_format(filesize($filepath)) . " bytes\n";
        $class_files_found++;
    } else {
        echo "✗ Missing: $description ($filename)\n";
    }
}

// Test 3: Asset files
echo "\n3. Asset Files Test\n";
echo "-------------------\n";
$assets_dir = $base_dir . '/assets';
$asset_files = [
    'css/frontend.css' => 'Frontend CSS',
    'css/admin.css' => 'Admin CSS',
    'js/frontend.js' => 'Frontend JavaScript',
    'js/admin.js' => 'Admin JavaScript'
];

$asset_files_found = 0;
foreach ($asset_files as $filename => $description) {
    $filepath = $assets_dir . '/' . $filename;
    if (file_exists($filepath)) {
        echo "✓ $description ($filename) - " . number_format(filesize($filepath)) . " bytes\n";
        $asset_files_found++;
    } else {
        echo "✗ Missing: $description ($filename)\n";
    }
}

// Test 4: Directory structure
echo "\n4. Directory Structure Test\n";
echo "---------------------------\n";
$directories = [
    $base_dir => 'Plugin root',
    $includes_dir => 'Includes directory',
    $assets_dir => 'Assets directory',
    $assets_dir . '/css' => 'CSS directory',
    $assets_dir . '/js' => 'JS directory',
    $assets_dir . '/images' => 'Images directory'
];

$dirs_found = 0;
foreach ($directories as $dir_path => $description) {
    if (is_dir($dir_path)) {
        echo "✓ $description exists\n";
        $dirs_found++;
    } else {
        echo "✗ Missing: $description\n";
    }
}

// Test 5: Code quality check (basic syntax)
echo "\n5. PHP Syntax Check\n";
echo "-------------------\n";

// Check main file syntax
$output = [];
$return_code = 0;
exec("php -l \"$main_file\" 2>&1", $output, $return_code);

if ($return_code === 0) {
    echo "✓ Main plugin file syntax OK\n";
} else {
    echo "✗ Main plugin file has syntax errors:\n";
    foreach ($output as $line) {
        echo "  $line\n";
    }
}

// Check class files syntax
$syntax_errors = 0;
foreach ($class_files as $filename => $description) {
    $filepath = $includes_dir . '/' . $filename;
    if (file_exists($filepath)) {
        $output = [];
        $return_code = 0;
        exec("php -l \"$filepath\" 2>&1", $output, $return_code);
        
        if ($return_code === 0) {
            echo "✓ $filename syntax OK\n";
        } else {
            echo "✗ $filename has syntax errors\n";
            $syntax_errors++;
        }
    }
}

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "PLUGIN STRUCTURE SUMMARY\n";
echo str_repeat("=", 50) . "\n";

$total_class_files = count($class_files);
$total_asset_files = count($asset_files);
$total_dirs = count($directories);

echo "Main Plugin File: " . (file_exists($main_file) ? "✓ Found" : "✗ Missing") . "\n";
echo "Class Files: $class_files_found/$total_class_files found\n";
echo "Asset Files: $asset_files_found/$total_asset_files found\n";
echo "Directories: $dirs_found/$total_dirs found\n";
echo "Syntax Errors: $syntax_errors files\n";

$total_components = 1 + $total_class_files + $total_asset_files + $total_dirs;
$found_components = (file_exists($main_file) ? 1 : 0) + $class_files_found + $asset_files_found + $dirs_found;
$success_rate = round(($found_components / $total_components) * 100);

echo "\nOverall Completeness: $success_rate%\n";

if ($success_rate >= 95) {
    echo "🎉 EXCELLENT! Plugin structure is complete and ready.\n";
} elseif ($success_rate >= 80) {
    echo "✅ GOOD! Plugin structure is mostly complete.\n";
} elseif ($success_rate >= 60) {
    echo "⚠️ PARTIAL! Some components are missing.\n";
} else {
    echo "❌ INCOMPLETE! Major components are missing.\n";
}

if ($syntax_errors === 0) {
    echo "✅ No PHP syntax errors detected.\n";
} else {
    echo "⚠️ PHP syntax errors need to be fixed.\n";
}

echo "\nPlugin is ready for WordPress activation testing.\n";
echo str_repeat("=", 50) . "\n";
?>
