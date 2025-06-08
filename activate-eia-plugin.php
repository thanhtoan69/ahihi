<?php
/**
 * Activate Environmental Integration APIs Plugin
 * Phase 57: Integration APIs & Webhooks - Final Activation
 */

// WordPress setup
require_once('wp-config.php');
require_once('wp-load.php');
require_once('wp-admin/includes/plugin.php');

echo "<h1>Environmental Integration APIs Plugin - Final Activation</h1>\n";
echo "<div style='background: #f1f1f1; padding: 20px; margin: 20px 0; border-radius: 5px;'>\n";

// Plugin path
$plugin_file = 'environmental-integration-apis/environmental-integration-apis.php';

try {
    // Check if plugin exists
    $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
    if (!file_exists($plugin_path)) {
        throw new Exception("Plugin file not found: $plugin_path");
    }
    
    echo "<p style='color: #46b450;'>✓ Plugin files found</p>\n";
    
    // Check if already activated
    if (is_plugin_active($plugin_file)) {
        echo "<p style='color: #ffb900;'>⚠ Plugin already activated, deactivating first...</p>\n";
        deactivate_plugins($plugin_file);
        sleep(2);
    }
    
    // Activate plugin
    echo "<p>Activating Environmental Integration APIs plugin...</p>\n";
    $result = activate_plugin($plugin_file);
    
    if (is_wp_error($result)) {
        throw new Exception("Activation failed: " . $result->get_error_message());
    }
    
    echo "<p style='color: #46b450;'>✓ Plugin activated successfully</p>\n";
    
    // Verify activation
    if (is_plugin_active($plugin_file)) {
        echo "<p style='color: #46b450;'>✓ Plugin activation verified</p>\n";
    } else {
        throw new Exception("Plugin activation verification failed");
    }
    
    // Check database tables
    global $wpdb;
    
    $tables = array(
        'eia_api_connections',
        'eia_api_logs', 
        'eia_webhooks',
        'eia_webhook_logs',
        'eia_api_cache'
    );
    
    echo "<h3>Database Tables Verification:</h3>\n";
    foreach ($tables as $table) {
        $table_name = $wpdb->prefix . $table;
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if ($exists) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            echo "<p style='color: #46b450;'>✓ Table '$table' exists (rows: $count)</p>\n";
        } else {
            echo "<p style='color: #dc3232;'>✗ Table '$table' not found</p>\n";
        }
    }
    
    // Check plugin classes
    echo "<h3>Plugin Classes Verification:</h3>\n";
    $classes = array(
        'Environmental_Integration_APIs' => 'Main plugin class',
        'EIA_Google_Maps_Integration' => 'Google Maps integration',
        'EIA_Weather_Integration' => 'Weather integration',
        'EIA_Air_Quality_Integration' => 'Air quality integration',
        'EIA_Social_Media_Integration' => 'Social media integration',
        'EIA_Webhook_System' => 'Webhook system',
        'EIA_API_Monitor' => 'API monitor',
        'EIA_Integration_Admin' => 'Admin interface',
        'EIA_Integration_REST_API' => 'REST API'
    );
    
    foreach ($classes as $class => $description) {
        if (class_exists($class)) {
            echo "<p style='color: #46b450;'>✓ $description loaded</p>\n";
        } else {
            echo "<p style='color: #dc3232;'>✗ $description not loaded</p>\n";
        }
    }
    
    // Check shortcodes
    echo "<h3>Shortcodes Verification:</h3>\n";
    $shortcodes = array(
        'env_google_map' => 'Google Maps',
        'env_location_picker' => 'Location picker',
        'env_weather' => 'Weather display',
        'env_weather_widget' => 'Weather widget',
        'env_weather_forecast' => 'Weather forecast',
        'env_air_quality' => 'Air quality display',
        'env_air_quality_widget' => 'Air quality widget',
        'env_social_share' => 'Social sharing',
        'env_social_feed' => 'Social feed'
    );
    
    foreach ($shortcodes as $shortcode => $description) {
        if (shortcode_exists($shortcode)) {
            echo "<p style='color: #46b450;'>✓ $description shortcode registered</p>\n";
        } else {
            echo "<p style='color: #dc3232;'>✗ $description shortcode not registered</p>\n";
        }
    }
    
    // Check REST API endpoints
    echo "<h3>REST API Endpoints Verification:</h3>\n";
    $rest_server = rest_get_server();
    $routes = $rest_server->get_routes();
    $eia_routes = array();
    
    foreach ($routes as $route => $handlers) {
        if (strpos($route, '/environmental-integration/v1/') === 0) {
            $eia_routes[] = $route;
        }
    }
    
    if (!empty($eia_routes)) {
        echo "<p style='color: #46b450;'>✓ REST API endpoints registered (" . count($eia_routes) . " endpoints)</p>\n";
        foreach ($eia_routes as $route) {
            echo "<p style='margin-left: 20px; color: #666;'>- $route</p>\n";
        }
    } else {
        echo "<p style='color: #dc3232;'>✗ No REST API endpoints found</p>\n";
    }
    
    // Check admin menu
    echo "<h3>Admin Interface Verification:</h3>\n";
    if (current_user_can('manage_options')) {
        global $menu, $submenu;
        $admin_menu_found = false;
        
        foreach ($menu as $menu_item) {
            if (isset($menu_item[2]) && strpos($menu_item[2], 'eia-') === 0) {
                $admin_menu_found = true;
                break;
            }
        }
        
        if ($admin_menu_found) {
            echo "<p style='color: #46b450;'>✓ Admin menu registered</p>\n";
        } else {
            echo "<p style='color: #dc3232;'>✗ Admin menu not found</p>\n";
        }
    } else {
        echo "<p style='color: #ffb900;'>⚠ Cannot verify admin menu (insufficient permissions)</p>\n";
    }
    
    // Test basic functionality
    echo "<h3>Basic Functionality Test:</h3>\n";
    
    // Test shortcode rendering
    $test_shortcode = do_shortcode('[env_weather location="Hanoi" units="metric"]');
    if (!empty($test_shortcode) && strpos($test_shortcode, 'env-weather-widget') !== false) {
        echo "<p style='color: #46b450;'>✓ Shortcode rendering works</p>\n";
    } else {
        echo "<p style='color: #ffb900;'>⚠ Shortcode rendering needs API configuration</p>\n";
    }
    
    // Check options
    $options = array(
        'eia_google_maps_api_key',
        'eia_weather_provider',
        'eia_air_quality_provider',
        'eia_cache_duration',
        'eia_debug_mode'
    );
    
    $options_set = 0;
    foreach ($options as $option) {
        if (get_option($option) !== false) {
            $options_set++;
        }
    }
    
    echo "<p style='color: #46b450;'>✓ Plugin options initialized ($options_set/" . count($options) . " options set)</p>\n";
    
    echo "<div style='background: #d7eddd; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
    echo "<h3>🎉 Plugin Successfully Activated!</h3>\n";
    echo "<p><strong>Environmental Integration APIs plugin is now active and ready for configuration.</strong></p>\n";
    echo "<p>Next steps:</p>\n";
    echo "<ol>\n";
    echo "<li>Navigate to <strong>WordPress Admin → Environmental APIs</strong></li>\n";
    echo "<li>Configure your API keys in <strong>API Configuration</strong></li>\n";
    echo "<li>Test API connections</li>\n";
    echo "<li>Create webhooks if needed</li>\n";
    echo "<li>Add environmental widgets to your pages using shortcodes</li>\n";
    echo "</ol>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
    echo "<h3>❌ Activation Failed</h3>\n";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>\n";
    echo "</div>\n";
}

echo "</div>\n";

// Show admin link if user has permissions
if (current_user_can('manage_options')) {
    echo "<p><a href='" . admin_url('admin.php?page=eia-dashboard') . "' style='background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px;'>Go to Environmental APIs Dashboard</a></p>\n";
}
?>
