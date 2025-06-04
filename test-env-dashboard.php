<?php
/**
 * Test Environmental Data Dashboard Plugin Status
 * Phase 39: AI Integration & Waste Classification Testing
 */

// Load WordPress
require_once __DIR__ . '/wp-config.php';
require_once __DIR__ . '/wp-load.php';

echo "=== Environmental Data Dashboard Plugin Test ===\n\n";

// Check if plugin is active
$plugin_file = 'environmental-data-dashboard/environmental-data-dashboard.php';
$active_plugins = get_option('active_plugins', array());
$is_active = in_array($plugin_file, $active_plugins);

if ($is_active) {
    echo "✅ Environmental Data Dashboard plugin is ACTIVE\n";
} else {
    echo "❌ Environmental Data Dashboard plugin is NOT ACTIVE\n";
    echo "Attempting to activate plugin...\n";
    
    $result = activate_plugin($plugin_file);
    if (is_wp_error($result)) {
        echo "❌ Activation failed: " . $result->get_error_message() . "\n";
        exit(1);
    } else {
        echo "✅ Plugin activated successfully\n";
        $is_active = true;
    }
}

if ($is_active) {
    echo "\n=== Database Tables Check ===\n";
    
    global $wpdb;
    
    // Check main Environmental Data Dashboard tables
    $main_tables = array(
        'env_air_quality_data' => 'Air Quality Data',
        'env_weather_data' => 'Weather Data',
        'env_carbon_footprint' => 'Carbon Footprint',
        'env_user_goals' => 'User Goals',
        'env_community_data' => 'Community Data'
    );
    
    echo "Main Tables:\n";
    foreach ($main_tables as $table => $description) {
        $full_table_name = $wpdb->prefix . $table;
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");
        echo "  {$description}: " . ($exists ? "✅ EXISTS" : "❌ MISSING") . "\n";
    }
    
    // Check AI & Waste Classification tables
    $ai_tables = array(
        'env_ai_classifications' => 'AI Classifications',
        'env_classification_feedback' => 'Classification Feedback',
        'env_user_gamification' => 'User Gamification',
        'env_achievements' => 'Achievements',
        'env_user_achievements' => 'User Achievements',
        'env_challenges' => 'Challenges',
        'env_user_challenges' => 'User Challenges',
        'env_ai_service_config' => 'AI Service Config',
        'env_ai_usage_log' => 'AI Usage Log'
    );
    
    echo "\nAI & Waste Classification Tables:\n";
    foreach ($ai_tables as $table => $description) {
        $full_table_name = $wpdb->prefix . $table;
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");
        echo "  {$description}: " . ($exists ? "✅ EXISTS" : "❌ MISSING") . "\n";
    }
    
    // Check class existence
    echo "\n=== Class Check ===\n";
    
    $classes = array(
        'Environmental_Data_Dashboard' => 'Main Plugin Class',
        'Environmental_Database_Manager' => 'Database Manager',
        'Environmental_AI_Service_Manager' => 'AI Service Manager',
        'Environmental_Waste_Classification_Interface' => 'Waste Classification Interface',
        'Environmental_Gamification_System' => 'Gamification System'
    );
    
    foreach ($classes as $class => $description) {
        echo "  {$description}: " . (class_exists($class) ? "✅ LOADED" : "❌ NOT FOUND") . "\n";
    }
    
    // Test admin menu registration
    echo "\n=== Admin Menu Check ===\n";
    
    // Check if admin hooks are registered
    global $wp_filter;
    $admin_menu_hooks = isset($wp_filter['admin_menu']) && !empty($wp_filter['admin_menu']);
    echo "  Admin Menu Hooks: " . ($admin_menu_hooks ? "✅ REGISTERED" : "❌ NOT REGISTERED") . "\n";
    
    // Check AJAX actions
    echo "\n=== AJAX Actions Check ===\n";
    
    $ajax_actions = array(
        'wp_ajax_classify_waste_image',
        'wp_ajax_get_ai_classification_stats',
        'wp_ajax_save_ai_configuration',
        'wp_ajax_test_ai_connection',
        'wp_ajax_get_recent_classifications'
    );
    
    foreach ($ajax_actions as $action) {
        $registered = isset($wp_filter[$action]) && !empty($wp_filter[$action]);
        echo "  {$action}: " . ($registered ? "✅ REGISTERED" : "❌ NOT REGISTERED") . "\n";
    }
    
    // Check sample data
    echo "\n=== Sample Data Check ===\n";
    
    $achievements_table = $wpdb->prefix . 'env_achievements';
    $achievements_count = $wpdb->get_var("SELECT COUNT(*) FROM $achievements_table");
    echo "  Achievements: " . ($achievements_count > 0 ? "✅ {$achievements_count} records" : "❌ No data") . "\n";
    
    $service_config_table = $wpdb->prefix . 'env_ai_service_config';
    $service_config_count = $wpdb->get_var("SELECT COUNT(*) FROM $service_config_table");
    echo "  AI Service Config: " . ($service_config_count > 0 ? "✅ {$service_config_count} records" : "❌ No data") . "\n";
    
    echo "\n=== WordPress Admin Menu Test ===\n";
    
    // Simulate admin environment
    if (!is_admin()) {
        define('WP_ADMIN', true);
        set_current_screen('dashboard');
    }
    
    // Force admin menu initialization
    do_action('admin_menu');
    
    // Check global menu
    global $menu, $submenu;
    
    $found_main_menu = false;
    $found_submenu = false;
    
    if (is_array($menu)) {
        foreach ($menu as $menu_item) {
            if (isset($menu_item[0]) && strpos($menu_item[0], 'Environmental') !== false) {
                $found_main_menu = true;
                echo "  Main Menu: ✅ Found - {$menu_item[0]}\n";
                break;
            }
        }
    }
    
    if (!$found_main_menu) {
        echo "  Main Menu: ❌ Not found\n";
    }
    
    if (is_array($submenu)) {
        foreach ($submenu as $parent => $items) {
            foreach ($items as $item) {
                if (isset($item[0]) && strpos($item[0], 'AI Waste') !== false) {
                    $found_submenu = true;
                    echo "  AI Waste Submenu: ✅ Found - {$item[0]}\n";
                    break 2;
                }
            }
        }
    }
    
    if (!$found_submenu) {
        echo "  AI Waste Submenu: ❌ Not found\n";
    }
}

echo "\n=== Test Complete ===\n";
?>
