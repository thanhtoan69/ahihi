<?php
/**
 * Phase 49 Complete Integration Test
 * Environmental Admin Dashboard Plugin
 */

// Load WordPress
require_once __DIR__ . '/wp-config.php';
require_once __DIR__ . '/wp-load.php';

echo "<h1>🌱 Phase 49: Environmental Admin Dashboard - Complete Integration Test</h1>";

// Test 1: Plugin Activation
echo "<h2>1. Plugin Activation Test</h2>";
$plugin_path = 'environmental-admin-dashboard/environmental-admin-dashboard.php';

if (is_plugin_active($plugin_path)) {
    echo "<p style='color: green;'>✅ Environmental Admin Dashboard plugin is ACTIVE</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Plugin not active. Activating...</p>";
    $result = activate_plugin($plugin_path);
    if (is_wp_error($result)) {
        echo "<p style='color: red;'>❌ Activation failed: " . $result->get_error_message() . "</p>";
        exit(1);
    } else {
        echo "<p style='color: green;'>✅ Plugin activated successfully</p>";
    }
}

// Test 2: Class Loading
echo "<h2>2. Class Loading Test</h2>";
$required_classes = [
    'Environmental_Admin_Dashboard' => 'Main plugin class',
    'Environmental_Dashboard_Widgets' => 'Dashboard widgets manager',
    'Environmental_Content_Manager' => 'Content management system',
    'Environmental_Bulk_Operations' => 'Bulk operations handler',
    'Environmental_Reporting_Dashboard' => 'Reporting and analytics',
    'Environmental_Notifications_Manager' => 'Notifications system',
    'Environmental_Admin_Customizer' => 'Admin interface customizer'
];

$classes_loaded = 0;
foreach ($required_classes as $class => $description) {
    if (class_exists($class)) {
        echo "<p style='color: green;'>✅ $class - $description</p>";
        $classes_loaded++;
    } else {
        echo "<p style='color: red;'>❌ $class - $description (NOT LOADED)</p>";
    }
}

echo "<p><strong>Classes Loaded: $classes_loaded/" . count($required_classes) . "</strong></p>";

// Test 3: File Structure
echo "<h2>3. File Structure Test</h2>";
$plugin_dir = WP_PLUGIN_DIR . '/environmental-admin-dashboard';

$required_files = [
    'Admin Templates' => [
        'admin/dashboard-overview.php',
        'admin/bulk-operations.php',
        'admin/notifications.php',
        'admin/content-management.php',
        'admin/reporting-dashboard.php',
        'admin/settings.php'
    ],
    'Widget Files' => [
        'widgets/platform-overview-widget.php',
        'widgets/activities-progress-widget.php',
        'widgets/environmental-goals-widget.php',
        'widgets/performance-analytics-widget.php',
        'widgets/platform-health-widget.php',
        'widgets/quick-actions-widget.php'
    ],
    'Core Classes' => [
        'includes/dashboard-functions.php',
        'includes/class-dashboard-widgets.php',
        'includes/class-content-manager.php',
        'includes/class-bulk-operations.php',
        'includes/class-reporting-dashboard.php',
        'includes/class-notifications-manager.php',
        'includes/class-admin-customizer.php'
    ],
    'Assets' => [
        'assets/css/admin-dashboard.css',
        'assets/css/admin-customizer.css',
        'assets/js/admin-dashboard.js',
        'assets/js/admin-customizer.js'
    ]
];

$total_files = 0;
$existing_files = 0;

foreach ($required_files as $category => $files) {
    echo "<h3>$category</h3>";
    foreach ($files as $file) {
        $full_path = $plugin_dir . '/' . $file;
        $total_files++;
        if (file_exists($full_path)) {
            $size = filesize($full_path);
            echo "<p style='color: green;'>✅ $file (" . number_format($size) . " bytes)</p>";
            $existing_files++;
        } else {
            echo "<p style='color: red;'>❌ $file (MISSING)</p>";
        }
    }
}

echo "<p><strong>Files Present: $existing_files/$total_files</strong></p>";

// Test 4: Admin Menu Integration
echo "<h2>4. Admin Menu Integration Test</h2>";

// Simulate admin context for menu testing
set_current_screen('dashboard');
do_action('admin_menu');

global $menu, $submenu;

$main_menu_found = false;
if (isset($menu) && is_array($menu)) {
    foreach ($menu as $menu_item) {
        if (isset($menu_item[2]) && $menu_item[2] === 'env-dashboard') {
            echo "<p style='color: green;'>✅ Main menu found: " . $menu_item[0] . "</p>";
            $main_menu_found = true;
            break;
        }
    }
}

if (!$main_menu_found) {
    echo "<p style='color: red;'>❌ Main menu not found</p>";
}

// Check submenus
$expected_submenus = [
    'env-dashboard' => 'Dashboard Overview',
    'env-content-management' => 'Content Management',
    'env-bulk-operations' => 'Bulk Operations',
    'env-reporting' => 'Reporting Dashboard',
    'env-notifications' => 'Notifications',
    'environmental-admin-customizer' => 'Admin Customizer',
    'env-dashboard-settings' => 'Settings'
];

$submenus_found = 0;
if (isset($submenu['env-dashboard']) && is_array($submenu['env-dashboard'])) {
    foreach ($submenu['env-dashboard'] as $submenu_item) {
        $page_slug = $submenu_item[2];
        if (isset($expected_submenus[$page_slug])) {
            echo "<p style='color: green;'>✅ Submenu found: " . $expected_submenus[$page_slug] . " ($page_slug)</p>";
            $submenus_found++;
        }
    }
}

echo "<p><strong>Submenus Found: $submenus_found/" . count($expected_submenus) . "</strong></p>";

// Test 5: Dashboard Widgets
echo "<h2>5. Dashboard Widgets Test</h2>";

global $wp_dashboard_setup;
if (!$wp_dashboard_setup) {
    do_action('wp_dashboard_setup');
}

global $wp_meta_boxes;
$dashboard_widgets = [];

if (isset($wp_meta_boxes['dashboard']['normal']['core'])) {
    foreach ($wp_meta_boxes['dashboard']['normal']['core'] as $widget_id => $widget_data) {
        if (strpos($widget_id, 'env_') === 0 || strpos($widget_id, 'environmental_') === 0) {
            $dashboard_widgets[] = $widget_data['title'];
        }
    }
}

if (!empty($dashboard_widgets)) {
    echo "<p style='color: green;'>✅ Dashboard widgets registered:</p>";
    foreach ($dashboard_widgets as $widget_title) {
        echo "<p style='margin-left: 20px;'>• $widget_title</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ No environmental dashboard widgets found</p>";
}

// Test 6: AJAX Endpoints
echo "<h2>6. AJAX Endpoints Test</h2>";

$ajax_actions = [
    'env_dashboard_action' => 'Dashboard actions',
    'env_bulk_operation' => 'Bulk operations',
    'env_notification_action' => 'Notification actions',
    'env_save_customizer_settings' => 'Save customizer settings',
    'env_reset_customizer_settings' => 'Reset customizer settings',
    'env_export_customizer_settings' => 'Export customizer settings',
    'env_import_customizer_settings' => 'Import customizer settings'
];

foreach ($ajax_actions as $action => $description) {
    if (has_action("wp_ajax_$action")) {
        echo "<p style='color: green;'>✅ AJAX action registered: $action - $description</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ AJAX action not found: $action - $description</p>";
    }
}

// Test 7: Database Tables
echo "<h2>7. Database Tables Test</h2>";

global $wpdb;
$expected_tables = [
    $wpdb->prefix . 'env_dashboard_widgets' => 'Dashboard widget settings',
    $wpdb->prefix . 'env_notifications' => 'Notification system',
    $wpdb->prefix . 'env_bulk_operations_log' => 'Bulk operations log'
];

foreach ($expected_tables as $table_name => $description) {
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    if ($table_exists) {
        $row_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        echo "<p style='color: green;'>✅ Table exists: $table_name - $description ($row_count rows)</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Table not found: $table_name - $description</p>";
    }
}

// Test 8: Admin Customizer Integration
echo "<h2>8. Admin Customizer Integration Test</h2>";

if (class_exists('Environmental_Admin_Customizer')) {
    $customizer = Environmental_Admin_Customizer::get_instance();
    echo "<p style='color: green;'>✅ Admin Customizer instance created</p>";
    
    // Test if customizer options are loaded
    $options = get_option('env_admin_customizer_options', array());
    if (is_array($options)) {
        echo "<p style='color: green;'>✅ Customizer options accessible (" . count($options) . " settings)</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Customizer options not found (using defaults)</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Admin Customizer class not available</p>";
}

// Summary
echo "<h2>🎯 Phase 49 Integration Summary</h2>";

$total_score = 0;
$max_score = 8;

if ($classes_loaded == count($required_classes)) $total_score++;
if ($existing_files == $total_files) $total_score++;
if ($main_menu_found) $total_score++;
if ($submenus_found >= 5) $total_score++;
if (count($dashboard_widgets) >= 3) $total_score++;
if (has_action('wp_ajax_env_dashboard_action')) $total_score++;
if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}env_dashboard_widgets'")) $total_score++;
if (class_exists('Environmental_Admin_Customizer')) $total_score++;

$percentage = round(($total_score / $max_score) * 100);

echo "<div style='background: #f0f0f1; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>Integration Score: $total_score/$max_score ($percentage%)</h3>";

if ($percentage >= 90) {
    echo "<p style='color: green; font-size: 18px;'><strong>🎉 EXCELLENT! Phase 49 integration is complete and ready for production.</strong></p>";
} elseif ($percentage >= 75) {
    echo "<p style='color: orange; font-size: 18px;'><strong>✅ GOOD! Phase 49 integration is mostly complete with minor issues.</strong></p>";
} else {
    echo "<p style='color: red; font-size: 18px;'><strong>⚠️ WARNING! Phase 49 integration needs attention.</strong></p>";
}

echo "</div>";

// Next Steps
echo "<h2>🚀 Next Steps</h2>";
echo "<ol>";
echo "<li><a href='/moitruong/wp-admin/admin.php?page=env-dashboard'>→ Visit Environmental Dashboard</a></li>";
echo "<li><a href='/moitruong/wp-admin/admin.php?page=environmental-admin-customizer'>→ Test Admin Customizer</a></li>";
echo "<li><a href='/moitruong/wp-admin/admin.php?page=env-bulk-operations'>→ Test Bulk Operations</a></li>";
echo "<li><a href='/moitruong/wp-admin/admin.php?page=env-reporting'>→ View Reporting Dashboard</a></li>";
echo "<li><a href='/moitruong/wp-admin/admin.php?page=env-notifications'>→ Check Notifications System</a></li>";
echo "</ol>";

echo "<p><strong>Phase 49 testing completed!</strong></p>";
?>
