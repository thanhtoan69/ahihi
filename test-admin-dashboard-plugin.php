<?php
/**
 * Test Environmental Admin Dashboard Plugin
 * Phase 49 Testing Script
 */

// Load WordPress
require_once __DIR__ . '/wp-config.php';
require_once __DIR__ . '/wp-load.php';

echo "=== Environmental Admin Dashboard Plugin Test ===\n\n";

// Check plugin directory and files
$plugin_dir = WP_PLUGIN_DIR . '/environmental-admin-dashboard';
$plugin_file = $plugin_dir . '/environmental-admin-dashboard.php';

echo "Testing Plugin Structure:\n";
if (file_exists($plugin_dir)) {
    echo "✅ Plugin directory exists\n";
} else {
    echo "❌ Plugin directory not found: $plugin_dir\n";
    exit(1);
}

if (file_exists($plugin_file)) {
    echo "✅ Main plugin file exists (" . number_format(filesize($plugin_file)) . " bytes)\n";
} else {
    echo "❌ Main plugin file not found: $plugin_file\n";
    exit(1);
}

// Check key directories
$directories = [
    'admin' => $plugin_dir . '/admin',
    'assets/css' => $plugin_dir . '/assets/css', 
    'assets/js' => $plugin_dir . '/assets/js',
    'includes' => $plugin_dir . '/includes',
    'widgets' => $plugin_dir . '/widgets'
];

echo "\nTesting Directory Structure:\n";
foreach ($directories as $name => $path) {
    if (file_exists($path) && is_dir($path)) {
        echo "✅ $name directory exists\n";
    } else {
        echo "❌ $name directory missing: $path\n";
    }
}

// Check key files
$key_files = [
    'Admin Templates' => [
        'dashboard-overview.php' => $plugin_dir . '/admin/dashboard-overview.php',
        'bulk-operations.php' => $plugin_dir . '/admin/bulk-operations.php',
        'notifications.php' => $plugin_dir . '/admin/notifications.php',
        'content-management.php' => $plugin_dir . '/admin/content-management.php',
        'reporting-dashboard.php' => $plugin_dir . '/admin/reporting-dashboard.php',
        'settings.php' => $plugin_dir . '/admin/settings.php'
    ],
    'CSS Assets' => [
        'admin-dashboard.css' => $plugin_dir . '/assets/css/admin-dashboard.css',
        'admin-customizer.css' => $plugin_dir . '/assets/css/admin-customizer.css'
    ],
    'JS Assets' => [
        'admin-dashboard.js' => $plugin_dir . '/assets/js/admin-dashboard.js',
        'admin-customizer.js' => $plugin_dir . '/assets/js/admin-customizer.js'
    ],
    'Core Classes' => [
        'dashboard-functions.php' => $plugin_dir . '/includes/dashboard-functions.php',
        'class-dashboard-widgets.php' => $plugin_dir . '/includes/class-dashboard-widgets.php',
        'class-content-manager.php' => $plugin_dir . '/includes/class-content-manager.php',
        'class-bulk-operations.php' => $plugin_dir . '/includes/class-bulk-operations.php',
        'class-reporting-dashboard.php' => $plugin_dir . '/includes/class-reporting-dashboard.php',
        'class-notifications-manager.php' => $plugin_dir . '/includes/class-notifications-manager.php',
        'class-admin-customizer.php' => $plugin_dir . '/includes/class-admin-customizer.php'
    ],
    'Widget Files' => [
        'platform-overview-widget.php' => $plugin_dir . '/widgets/platform-overview-widget.php',
        'activities-progress-widget.php' => $plugin_dir . '/widgets/activities-progress-widget.php',
        'environmental-goals-widget.php' => $plugin_dir . '/widgets/environmental-goals-widget.php',
        'performance-analytics-widget.php' => $plugin_dir . '/widgets/performance-analytics-widget.php',
        'platform-health-widget.php' => $plugin_dir . '/widgets/platform-health-widget.php',
        'quick-actions-widget.php' => $plugin_dir . '/widgets/quick-actions-widget.php'
    ]
];

echo "\nTesting Key Files:\n";
foreach ($key_files as $category => $files) {
    echo "\n$category:\n";
    foreach ($files as $name => $path) {
        if (file_exists($path)) {
            $size = filesize($path);
            echo "✅ $name exists (" . number_format($size) . " bytes)\n";
        } else {
            echo "❌ $name missing: $path\n";
        }
    }
}

// Test plugin activation
echo "\nTesting Plugin Activation:\n";
$plugin_path = 'environmental-admin-dashboard/environmental-admin-dashboard.php';

if (is_plugin_active($plugin_path)) {
    echo "✅ Plugin is already active\n";
} else {
    echo "⚠️ Plugin is not active. Attempting activation...\n";
    
    $result = activate_plugin($plugin_path);
    if (is_wp_error($result)) {
        echo "❌ Activation failed: " . $result->get_error_message() . "\n";
    } else {
        echo "✅ Plugin activated successfully\n";
    }
}

// Test class loading after activation
echo "\nTesting Class Loading:\n";
$classes = [
    'Environmental_Admin_Dashboard',
    'Environmental_Dashboard_Widgets',
    'Environmental_Content_Manager', 
    'Environmental_Bulk_Operations',
    'Environmental_Reporting_Dashboard',
    'Environmental_Notifications_Manager',
    'Environmental_Admin_Customizer'
];

foreach ($classes as $class) {
    if (class_exists($class)) {
        echo "✅ Class loaded: $class\n";
    } else {
        echo "❌ Class missing: $class\n";
    }
}

// Test WordPress hooks and actions
echo "\nTesting WordPress Integration:\n";
if (has_action('admin_menu', array('Environmental_Admin_Dashboard', 'add_admin_menu'))) {
    echo "✅ Admin menu hook registered\n";
} else {
    echo "⚠️ Admin menu hook not found\n";
}

if (has_action('admin_enqueue_scripts')) {
    echo "✅ Admin scripts hook registered\n";
} else {
    echo "⚠️ Admin scripts hook not found\n";
}

echo "\n=== Plugin Test Complete ===\n";
