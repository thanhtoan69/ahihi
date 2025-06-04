<?php
/**
 * Plugin Check Results Output
 */

ob_start();

echo "=== Environmental Data Dashboard Plugin Check ===\n\n";

$plugin_path = __DIR__ . '/wp-content/plugins/environmental-data-dashboard/';

// Check if plugin directory exists
if (!is_dir($plugin_path)) {
    echo "FAIL: Plugin directory does not exist\n";
    exit(1);
}

echo "PASS: Plugin directory exists\n";

// Check main plugin file
$main_file = $plugin_path . 'environmental-data-dashboard.php';
if (!file_exists($main_file)) {
    echo "FAIL: Main plugin file not found\n";
    exit(1);
}

echo "PASS: Main plugin file exists (" . number_format(filesize($main_file)) . " bytes)\n";

// Check for AI-related methods
$main_content = file_get_contents($main_file);

$checks = [
    'create_ai_database_tables' => 'AI Database Creation',
    'add_admin_menu' => 'Admin Menu',
    'handle_waste_classification_ajax' => 'Waste Classification AJAX',
    'enqueue_admin_scripts' => 'Admin Scripts'
];

foreach ($checks as $method => $desc) {
    if (strpos($main_content, $method) !== false) {
        echo "PASS: $desc found\n";
    } else {
        echo "FAIL: $desc NOT found\n";
    }
}

// Check key files
$files = [
    'assets/js/waste-classification-admin.js' => 'Admin JS',
    'assets/css/waste-classification.css' => 'CSS Styles',
    'includes/class-database-manager.php' => 'Database Manager'
];

foreach ($files as $file => $desc) {
    $full_path = $plugin_path . $file;
    if (file_exists($full_path)) {
        echo "PASS: $desc exists (" . number_format(filesize($full_path)) . " bytes)\n";
    } else {
        echo "FAIL: $desc NOT found\n";
    }
}

// Check for AI table definitions
if (strpos($main_content, 'env_ai_classifications') !== false) {
    echo "PASS: AI Classifications table found\n";
} else {
    echo "FAIL: AI Classifications table NOT found\n";
}

if (strpos($main_content, 'env_user_gamification') !== false) {
    echo "PASS: User Gamification table found\n";
} else {
    echo "FAIL: User Gamification table NOT found\n";
}

echo "\n=== Check Complete ===\n";

$output = ob_get_clean();
file_put_contents(__DIR__ . '/plugin-check-results.txt', $output);
echo $output;
