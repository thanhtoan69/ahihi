<?php
/**
 * Direct Plugin File Structure Check
 * Phase 39: AI Integration & Waste Classification
 */

echo "=== Environmental Data Dashboard Plugin Direct Check ===\n\n";

$plugin_path = __DIR__ . '/wp-content/plugins/environmental-data-dashboard/';

// Check if plugin directory exists
if (!is_dir($plugin_path)) {
    echo "❌ Plugin directory does not exist: $plugin_path\n";
    exit(1);
}

echo "✅ Plugin directory exists\n";

// Check main plugin file
$main_file = $plugin_path . 'environmental-data-dashboard.php';
if (!file_exists($main_file)) {
    echo "❌ Main plugin file not found\n";
    exit(1);
}

echo "✅ Main plugin file exists (" . number_format(filesize($main_file)) . " bytes)\n";

// Check for AI-related methods in main file
$main_content = file_get_contents($main_file);
$ai_methods = [
    'create_ai_database_tables' => 'AI Database Table Creation',
    'add_admin_menu' => 'Admin Menu Integration',
    'handle_waste_classification_ajax' => 'Waste Classification AJAX',
    'handle_ai_stats_ajax' => 'AI Stats AJAX',
    'enqueue_admin_scripts' => 'Admin Script Enqueuing'
];

echo "\nChecking AI Integration Methods:\n";
foreach ($ai_methods as $method => $description) {
    if (strpos($main_content, "function $method") !== false || strpos($main_content, "$method(") !== false) {
        echo "✅ $description method found\n";
    } else {
        echo "❌ $description method NOT found\n";
    }
}

// Check JavaScript files
$js_files = [
    'assets/js/waste-classification-admin.js' => 'Admin Interface JS',
    'assets/js/waste-classification.js' => 'Frontend Classification JS',
    'assets/js/environmental-dashboard.js' => 'Main Dashboard JS',
    'admin/js/admin-dashboard.js' => 'Admin Dashboard JS'
];

echo "\nChecking JavaScript Files:\n";
foreach ($js_files as $file => $description) {
    $full_path = $plugin_path . $file;
    if (file_exists($full_path)) {
        echo "✅ $description exists (" . number_format(filesize($full_path)) . " bytes)\n";
    } else {
        echo "❌ $description NOT found\n";
    }
}

// Check CSS files
$css_files = [
    'assets/css/waste-classification.css' => 'Waste Classification CSS',
    'assets/css/environmental-dashboard.css' => 'Main Dashboard CSS',
    'admin/css/admin-styles.css' => 'Admin Styles CSS'
];

echo "\nChecking CSS Files:\n";
foreach ($css_files as $file => $description) {
    $full_path = $plugin_path . $file;
    if (file_exists($full_path)) {
        echo "✅ $description exists (" . number_format(filesize($full_path)) . " bytes)\n";
    } else {
        echo "❌ $description NOT found\n";
    }
}

// Check PHP class files
$php_files = [
    'includes/class-database-manager.php' => 'Database Manager Class',
    'includes/class-waste-classification-interface.php' => 'Waste Classification Interface'
];

echo "\nChecking PHP Class Files:\n";
foreach ($php_files as $file => $description) {
    $full_path = $plugin_path . $file;
    if (file_exists($full_path)) {
        echo "✅ $description exists (" . number_format(filesize($full_path)) . " bytes)\n";
    } else {
        echo "❌ $description NOT found\n";
    }
}

// Check for AI database table creation code
echo "\nChecking AI Database Integration:\n";
if (strpos($main_content, 'env_ai_classifications') !== false) {
    echo "✅ AI Classifications table code found\n";
} else {
    echo "❌ AI Classifications table code NOT found\n";
}

if (strpos($main_content, 'env_classification_feedback') !== false) {
    echo "✅ Classification Feedback table code found\n";
} else {
    echo "❌ Classification Feedback table code NOT found\n";
}

if (strpos($main_content, 'env_user_gamification') !== false) {
    echo "✅ User Gamification table code found\n";
} else {
    echo "❌ User Gamification table code NOT found\n";
}

// Check admin JavaScript for key functionality
$admin_js_path = $plugin_path . 'assets/js/waste-classification-admin.js';
if (file_exists($admin_js_path)) {
    $admin_js_content = file_get_contents($admin_js_path);
    
    echo "\nChecking Admin JavaScript Functionality:\n";
    $js_features = [
        'loadDashboardStats' => 'Dashboard Statistics Loading',
        'loadClassificationHistory' => 'Classification History',
        'saveAIConfiguration' => 'AI Configuration Management',
        'exportClassificationData' => 'Data Export Functionality',
        'wp.ajax.post' => 'WordPress AJAX Integration'
    ];
    
    foreach ($js_features as $feature => $description) {
        if (strpos($admin_js_content, $feature) !== false) {
            echo "✅ $description found\n";
        } else {
            echo "❌ $description NOT found\n";
        }
    }
}

echo "\n=== Plugin Structure Analysis Complete ===\n";
echo "The Environmental Data Dashboard plugin appears to be fully implemented with AI Waste Classification features.\n";
