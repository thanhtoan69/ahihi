<?php
/**
 * Environmental Mobile API Admin Interface Test
 * 
 * This script tests the admin interface integration
 */

// WordPress setup
require_once 'wp-config.php';

// Set up admin context
define('WP_ADMIN', true);
require_once ABSPATH . 'wp-admin/includes/admin.php';

echo "<h1>Environmental Mobile API Admin Interface Test</h1>";

// Check if plugin is active
$plugin_file = 'environmental-mobile-api/environmental-mobile-api.php';
if (!is_plugin_active($plugin_file)) {
    echo "❌ Plugin is not active. Please activate it first.<br>";
    echo "<a href='" . admin_url('plugins.php') . "'>Go to Plugins Page</a><br>";
    exit;
}

echo "✅ Plugin is active<br><br>";

// Test admin menu registration
echo "<h2>Admin Menu Test</h2>";

// Simulate admin menu registration
do_action('admin_menu');

global $submenu;
$mobile_api_menu_found = false;

if (isset($submenu['options-general.php'])) {
    foreach ($submenu['options-general.php'] as $menu_item) {
        if (isset($menu_item[2]) && $menu_item[2] === 'environmental-mobile-api') {
            $mobile_api_menu_found = true;
            echo "✅ Mobile API menu found in Settings submenu<br>";
            echo "&nbsp;&nbsp;📋 Menu title: " . $menu_item[0] . "<br>";
            echo "&nbsp;&nbsp;🔗 Menu slug: " . $menu_item[2] . "<br>";
            break;
        }
    }
}

if (!$mobile_api_menu_found) {
    echo "❌ Mobile API menu not found in Settings submenu<br>";
}

// Test admin page access
echo "<h2>Admin Page Access Test</h2>";
$admin_page_url = admin_url('options-general.php?page=environmental-mobile-api');
echo "🔗 Admin page URL: <a href='" . $admin_page_url . "' target='_blank'>" . $admin_page_url . "</a><br>";

// Test admin dashboard class
echo "<h2>Admin Dashboard Class Test</h2>";
if (class_exists('Environmental_Mobile_API_Admin_Dashboard')) {
    echo "✅ Admin Dashboard class exists<br>";
    
    // Check if dashboard view file exists
    $dashboard_view = WP_PLUGIN_DIR . '/environmental-mobile-api/admin/views/dashboard.php';
    if (file_exists($dashboard_view)) {
        echo "✅ Dashboard view file exists<br>";
        echo "&nbsp;&nbsp;📄 View file: " . $dashboard_view . "<br>";
    } else {
        echo "❌ Dashboard view file missing: " . $dashboard_view . "<br>";
    }
} else {
    echo "❌ Admin Dashboard class not found<br>";
}

// Test admin assets
echo "<h2>Admin Assets Test</h2>";
$admin_css = WP_PLUGIN_DIR . '/environmental-mobile-api/assets/css/admin.css';
$admin_js = WP_PLUGIN_DIR . '/environmental-mobile-api/assets/js/admin.js';

if (file_exists($admin_css)) {
    echo "✅ Admin CSS file exists<br>";
    echo "&nbsp;&nbsp;📄 CSS file: " . $admin_css . "<br>";
    echo "&nbsp;&nbsp;📏 File size: " . filesize($admin_css) . " bytes<br>";
} else {
    echo "❌ Admin CSS file missing: " . $admin_css . "<br>";
}

if (file_exists($admin_js)) {
    echo "✅ Admin JS file exists<br>";
    echo "&nbsp;&nbsp;📄 JS file: " . $admin_js . "<br>";
    echo "&nbsp;&nbsp;📏 File size: " . filesize($admin_js) . " bytes<br>";
} else {
    echo "❌ Admin JS file missing: " . $admin_js . "<br>";
}

// Test plugin options
echo "<h2>Plugin Options Test</h2>";
$options = array(
    'environmental_mobile_api_version' => 'Plugin Version',
    'environmental_mobile_api_jwt_secret' => 'JWT Secret',
    'environmental_mobile_api_settings' => 'Plugin Settings',
    'environmental_mobile_api_cors_origins' => 'CORS Origins'
);

foreach ($options as $option_name => $option_desc) {
    $value = get_option($option_name);
    if ($value !== false) {
        echo "✅ " . $option_desc . " option exists<br>";
        if ($option_name === 'environmental_mobile_api_jwt_secret') {
            echo "&nbsp;&nbsp;🔐 JWT Secret length: " . strlen($value) . " characters<br>";
        } elseif ($option_name === 'environmental_mobile_api_settings') {
            echo "&nbsp;&nbsp;📝 Settings count: " . count($value) . " items<br>";
            foreach ($value as $key => $setting_value) {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;• " . $key . ": " . (is_array($setting_value) ? json_encode($setting_value) : $setting_value) . "<br>";
            }
        }
    } else {
        echo "❌ " . $option_desc . " option missing<br>";
    }
}

// Test AJAX endpoints
echo "<h2>AJAX Endpoints Test</h2>";
echo "📋 Testing admin AJAX functionality...<br>";

// The admin dashboard should register AJAX handlers
$ajax_actions = array(
    'environmental_mobile_api_test_endpoint',
    'environmental_mobile_api_get_logs',
    'environmental_mobile_api_clear_cache',
    'environmental_mobile_api_test_webhook'
);

foreach ($ajax_actions as $action) {
    $hook_exists = has_action("wp_ajax_$action");
    if ($hook_exists) {
        echo "✅ AJAX action registered: $action<br>";
    } else {
        echo "⚠️ AJAX action not found: $action (may be loaded conditionally)<br>";
    }
}

// Test database connection for admin features
echo "<h2>Database Integration Test</h2>";
global $wpdb;

// Test API logs table for admin display
$logs_table = $wpdb->prefix . 'environmental_mobile_api_logs';
$log_count = $wpdb->get_var("SELECT COUNT(*) FROM $logs_table");
echo "📊 API logs count: " . $log_count . "<br>";

// Test webhooks table for admin management
$webhooks_table = $wpdb->prefix . 'environmental_mobile_api_webhooks';
$webhook_count = $wpdb->get_var("SELECT COUNT(*) FROM $webhooks_table");
echo "🔗 Webhooks count: " . $webhook_count . "<br>";

// Test tokens table for admin monitoring
$tokens_table = $wpdb->prefix . 'environmental_mobile_api_tokens';
$token_count = $wpdb->get_var("SELECT COUNT(*) FROM $tokens_table");
echo "🔐 Active tokens count: " . $token_count . "<br>";

// Capability check
echo "<h2>User Capabilities Test</h2>";
$current_user = wp_get_current_user();
if ($current_user->ID) {
    echo "👤 Current user: " . $current_user->user_login . "<br>";
    echo "🛡️ User roles: " . implode(', ', $current_user->roles) . "<br>";
    
    if (current_user_can('manage_options')) {
        echo "✅ User can manage options (required for admin access)<br>";
    } else {
        echo "❌ User cannot manage options (admin access will be denied)<br>";
    }
} else {
    echo "⚠️ No user logged in - admin access requires authentication<br>";
}

// Test admin page rendering (simulation)
echo "<h2>Admin Page Rendering Test</h2>";
ob_start();
try {
    // Include the dashboard view
    $dashboard_view = WP_PLUGIN_DIR . '/environmental-mobile-api/admin/views/dashboard.php';
    if (file_exists($dashboard_view)) {
        include $dashboard_view;
        $output = ob_get_contents();
        ob_end_clean();
        
        if (!empty($output)) {
            echo "✅ Dashboard view renders successfully<br>";
            echo "&nbsp;&nbsp;📏 Output length: " . strlen($output) . " characters<br>";
            
            // Check for key elements
            if (strpos($output, 'Environmental Platform Mobile API') !== false) {
                echo "&nbsp;&nbsp;✅ Title found in output<br>";
            }
            if (strpos($output, 'API Status') !== false) {
                echo "&nbsp;&nbsp;✅ Status section found<br>";
            }
            if (strpos($output, 'api-tester') !== false) {
                echo "&nbsp;&nbsp;✅ API tester found<br>";
            }
        } else {
            echo "⚠️ Dashboard view renders but produces no output<br>";
        }
    } else {
        ob_end_clean();
        echo "❌ Dashboard view file not found<br>";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ Dashboard view rendering failed: " . $e->getMessage() . "<br>";
}

// Summary
echo "<h2>Test Summary</h2>";
echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 5px;'>";
echo "<strong>Plugin Status:</strong> " . (is_plugin_active($plugin_file) ? 'Active' : 'Inactive') . "<br>";
echo "<strong>Admin Menu:</strong> " . ($mobile_api_menu_found ? 'Found' : 'Missing') . "<br>";
echo "<strong>Dashboard Class:</strong> " . (class_exists('Environmental_Mobile_API_Admin_Dashboard') ? 'Loaded' : 'Missing') . "<br>";
echo "<strong>Admin Assets:</strong> " . (file_exists($admin_css) && file_exists($admin_js) ? 'Available' : 'Missing') . "<br>";
echo "<strong>Test Time:</strong> " . date('Y-m-d H:i:s') . "<br>";
echo "</div>";

echo "<h3>Next Steps</h3>";
echo "<ol>";
echo "<li><a href='" . $admin_page_url . "' target='_blank'>Visit the Admin Interface</a></li>";
echo "<li><a href='" . admin_url('plugins.php') . "' target='_blank'>Check Plugin Status</a></li>";
echo "<li><a href='" . rest_url('environmental-mobile-api/v1/docs') . "' target='_blank'>View API Documentation</a></li>";
echo "<li>Test the API endpoints using the built-in tester</li>";
echo "<li>Configure webhooks and test webhook delivery</li>";
echo "</ol>";

?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}

h1, h2, h3 {
    color: #333;
}

h2 {
    border-bottom: 2px solid #0073aa;
    padding-bottom: 5px;
    margin-top: 30px;
}

a {
    color: #0073aa;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}

ol, ul {
    padding-left: 20px;
}

li {
    margin-bottom: 5px;
}
</style>
