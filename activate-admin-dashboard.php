<?php
/**
 * Activate Environmental Admin Dashboard Plugin
 * Phase 49 Plugin Activation Test
 */

// Include WordPress
require_once __DIR__ . '/wp-config.php';
require_once __DIR__ . '/wp-load.php';

echo "<h1>Environmental Admin Dashboard Plugin Activation</h1>";

$plugin_path = 'environmental-admin-dashboard/environmental-admin-dashboard.php';

// Check if plugin exists
$plugin_file = WP_PLUGIN_DIR . '/' . $plugin_path;
if (!file_exists($plugin_file)) {
    echo "<p style='color: red;'>❌ Plugin file not found: $plugin_file</p>";
    exit;
}

echo "<p>✅ Plugin file exists: $plugin_file</p>";

// Check if plugin is already active
if (is_plugin_active($plugin_path)) {
    echo "<p style='color: green;'>✅ Plugin is already active!</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Plugin is not active. Activating now...</p>";
    
    // Activate the plugin
    $result = activate_plugin($plugin_path);
    
    if (is_wp_error($result)) {
        echo "<p style='color: red;'>❌ Activation failed: " . $result->get_error_message() . "</p>";
    } else {
        echo "<p style='color: green;'>✅ Plugin activated successfully!</p>";
    }
}

// Test class loading
echo "<h2>Testing Class Loading</h2>";
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
        echo "<p style='color: green;'>✅ Class loaded: $class</p>";
    } else {
        echo "<p style='color: red;'>❌ Class missing: $class</p>";
    }
}

// Check admin menu
echo "<h2>Testing Admin Menu Integration</h2>";
global $menu, $submenu;

$found_menu = false;
if (isset($menu)) {
    foreach ($menu as $menu_item) {
        if (isset($menu_item[2]) && $menu_item[2] === 'env-dashboard') {
            echo "<p style='color: green;'>✅ Main admin menu found: " . $menu_item[0] . "</p>";
            $found_menu = true;
            break;
        }
    }
}

if (!$found_menu) {
    echo "<p style='color: orange;'>⚠️ Main admin menu not found (may need admin context)</p>";
}

// Check for admin customizer submenu
if (isset($submenu['env-dashboard'])) {
    $customizer_found = false;
    foreach ($submenu['env-dashboard'] as $submenu_item) {
        if ($submenu_item[2] === 'environmental-admin-customizer') {
            echo "<p style='color: green;'>✅ Admin Customizer submenu found</p>";
            $customizer_found = true;
            break;
        }
    }
    if (!$customizer_found) {
        echo "<p style='color: orange;'>⚠️ Admin Customizer submenu not found</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ No submenus found for environmental dashboard</p>";
}

echo "<h2>Plugin File Structure Check</h2>";
$required_files = [
    'admin/dashboard-overview.php',
    'admin/bulk-operations.php', 
    'admin/notifications.php',
    'admin/content-management.php',
    'admin/reporting-dashboard.php',
    'admin/settings.php',
    'assets/css/admin-dashboard.css',
    'assets/css/admin-customizer.css',
    'assets/js/admin-dashboard.js',
    'assets/js/admin-customizer.js'
];

$plugin_dir = WP_PLUGIN_DIR . '/environmental-admin-dashboard/';
foreach ($required_files as $file) {
    $full_path = $plugin_dir . $file;
    if (file_exists($full_path)) {
        $size = filesize($full_path);
        echo "<p style='color: green;'>✅ $file exists (" . number_format($size) . " bytes)</p>";
    } else {
        echo "<p style='color: red;'>❌ $file missing</p>";
    }
}

echo "<h2>Conclusion</h2>";
echo "<p>Environmental Admin Dashboard Plugin activation test completed.</p>";
echo "<p><a href='/moitruong/wp-admin/admin.php?page=env-dashboard' target='_blank'>→ Visit Admin Dashboard</a></p>";
echo "<p><a href='/moitruong/wp-admin/admin.php?page=environmental-admin-customizer' target='_blank'>→ Visit Admin Customizer</a></p>";
?>
