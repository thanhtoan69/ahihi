<?php
/**
 * Simple plugin activation test
 */

// Basic plugin file check
$plugin_path = __DIR__ . '/wp-content/plugins/environmental-item-exchange/';

echo "=== Environmental Item Exchange Plugin Check ===\n\n";

// Check if plugin directory exists
if (is_dir($plugin_path)) {
    echo "Plugin directory: EXISTS\n";
} else {
    echo "Plugin directory: MISSING\n";
    exit(1);
}

// Check main plugin file
$main_file = $plugin_path . 'environmental-item-exchange.php';
if (file_exists($main_file)) {
    echo "Main plugin file: EXISTS\n";
} else {
    echo "Main plugin file: MISSING\n";
    exit(1);
}

// Check required files
$required_files = array(
    'includes/class-frontend-templates.php',
    'includes/class-database-setup.php',
    'includes/class-admin-dashboard.php',
    'assets/js/frontend.js',
    'assets/css/frontend.css',
    'templates/single-item_exchange.php',
    'templates/archive-item_exchange.php',
    'templates/partials/exchange-card.php'
);

echo "\nPlugin Files Status:\n";
$all_files_exist = true;
foreach ($required_files as $file) {
    $file_path = $plugin_path . $file;
    $exists = file_exists($file_path);
    echo "$file: " . ($exists ? "EXISTS" : "MISSING") . "\n";
    if (!$exists) {
        $all_files_exist = false;
    }
}

if ($all_files_exist) {
    echo "\n✓ All required plugin files are present!\n";
} else {
    echo "\n✗ Some plugin files are missing!\n";
}

echo "\n=== Plugin Structure Complete ===\n";
?>
