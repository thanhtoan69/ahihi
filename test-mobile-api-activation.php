<?php
/**
 * Environmental Mobile API Plugin Activation Test
 * 
 * This script tests the plugin activation and verifies all components are working
 */

// WordPress setup
require_once 'wp-config.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';

echo "<h1>Environmental Mobile API Plugin Activation Test</h1>";

// Check if plugin exists
$plugin_file = 'environmental-mobile-api/environmental-mobile-api.php';
$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;

echo "<h2>1. Plugin File Check</h2>";
if (file_exists($plugin_path)) {
    echo "✅ Plugin file exists: " . $plugin_path . "<br>";
} else {
    echo "❌ Plugin file not found: " . $plugin_path . "<br>";
    exit;
}

// Check if plugin is already active
echo "<h2>2. Plugin Status Check</h2>";
if (is_plugin_active($plugin_file)) {
    echo "⚠️ Plugin is already active<br>";
    // Deactivate first
    deactivate_plugins($plugin_file);
    echo "🔄 Plugin deactivated for clean test<br>";
}

// Activate the plugin
echo "<h2>3. Plugin Activation</h2>";
$result = activate_plugin($plugin_file);

if (is_wp_error($result)) {
    echo "❌ Plugin activation failed: " . $result->get_error_message() . "<br>";
    echo "<h3>Error Details:</h3>";
    echo "<pre>" . print_r($result, true) . "</pre>";
} else {
    echo "✅ Plugin activated successfully<br>";
}

// Verify plugin is active
if (is_plugin_active($plugin_file)) {
    echo "✅ Plugin is now active<br>";
} else {
    echo "❌ Plugin activation verification failed<br>";
}

// Check if database tables were created
echo "<h2>4. Database Tables Check</h2>";
global $wpdb;

$tables_to_check = array(
    $wpdb->prefix . 'environmental_mobile_api_tokens',
    $wpdb->prefix . 'environmental_mobile_api_rate_limits',
    $wpdb->prefix . 'environmental_mobile_api_logs',
    $wpdb->prefix . 'environmental_mobile_api_webhooks',
    $wpdb->prefix . 'environmental_mobile_api_devices'
);

foreach ($tables_to_check as $table) {
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    if ($table_exists) {
        echo "✅ Table exists: $table<br>";
        
        // Show table structure
        $columns = $wpdb->get_results("DESCRIBE $table");
        echo "&nbsp;&nbsp;📋 Columns: ";
        $column_names = array();
        foreach ($columns as $column) {
            $column_names[] = $column->Field;
        }
        echo implode(', ', $column_names) . "<br>";
    } else {
        echo "❌ Table missing: $table<br>";
    }
}

// Check if options were created
echo "<h2>5. Plugin Options Check</h2>";
$options_to_check = array(
    'environmental_mobile_api_version',
    'environmental_mobile_api_jwt_secret',
    'environmental_mobile_api_settings',
    'environmental_mobile_api_cors_origins'
);

foreach ($options_to_check as $option) {
    $value = get_option($option);
    if ($value !== false) {
        echo "✅ Option exists: $option<br>";
        if ($option === 'environmental_mobile_api_jwt_secret') {
            echo "&nbsp;&nbsp;🔐 JWT Secret length: " . strlen($value) . " characters<br>";
        } elseif ($option === 'environmental_mobile_api_settings') {
            echo "&nbsp;&nbsp;📝 Settings: " . count($value) . " items<br>";
        } else {
            echo "&nbsp;&nbsp;📄 Value: " . (is_array($value) ? json_encode($value) : $value) . "<br>";
        }
    } else {
        echo "❌ Option missing: $option<br>";
    }
}

// Check upload directories
echo "<h2>6. Upload Directories Check</h2>";
$upload_dir = wp_upload_dir();
$mobile_api_dir = $upload_dir['basedir'] . '/environmental-mobile-api';

$dirs_to_check = array(
    $mobile_api_dir,
    $mobile_api_dir . '/logs',
    $mobile_api_dir . '/cache',
    $mobile_api_dir . '/temp'
);

foreach ($dirs_to_check as $dir) {
    if (file_exists($dir) && is_dir($dir)) {
        echo "✅ Directory exists: $dir<br>";
    } else {
        echo "❌ Directory missing: $dir<br>";
    }
}

// Test API endpoints
echo "<h2>7. REST API Endpoints Check</h2>";
$rest_url = rest_url('environmental-mobile-api/v1/');
echo "🌐 API Base URL: " . $rest_url . "<br>";

// Test health endpoint
$health_url = rest_url('environmental-mobile-api/v1/health');
echo "🏥 Testing health endpoint: " . $health_url . "<br>";

$health_response = wp_remote_get($health_url);
if (!is_wp_error($health_response)) {
    $status_code = wp_remote_retrieve_response_code($health_response);
    $body = wp_remote_retrieve_body($health_response);
    echo "&nbsp;&nbsp;📊 Status Code: " . $status_code . "<br>";
    echo "&nbsp;&nbsp;📄 Response: " . $body . "<br>";
    
    if ($status_code === 200) {
        echo "✅ Health endpoint working<br>";
    } else {
        echo "⚠️ Health endpoint returned status: " . $status_code . "<br>";
    }
} else {
    echo "❌ Health endpoint failed: " . $health_response->get_error_message() . "<br>";
}

// Check admin menu
echo "<h2>8. Admin Interface Check</h2>";
if (is_admin()) {
    echo "✅ Running in admin context<br>";
    // The admin menu should be registered
    echo "📋 Admin menu should be available under Settings > Mobile API<br>";
} else {
    echo "⚠️ Not running in admin context - admin menu check skipped<br>";
}

// Test class loading
echo "<h2>9. Class Loading Check</h2>";
$classes_to_check = array(
    'Environmental_Mobile_API',
    'Environmental_Mobile_API_Auth_Manager',
    'Environmental_Mobile_API_Rate_Limiter',
    'Environmental_Mobile_API_Cache_Manager',
    'Environmental_Mobile_API_Webhook_Manager',
    'Environmental_Mobile_API_Security',
    'Environmental_Mobile_API_Documentation',
    'Environmental_Mobile_API_Manager',
    'Environmental_Mobile_API_Admin_Dashboard',
    'Environmental_Mobile_API_Auth_Endpoints',
    'Environmental_Mobile_API_User_Endpoints',
    'Environmental_Mobile_API_Content_Endpoints',
    'Environmental_Mobile_API_Environmental_Data_Endpoints'
);

foreach ($classes_to_check as $class) {
    if (class_exists($class)) {
        echo "✅ Class loaded: $class<br>";
    } else {
        echo "❌ Class missing: $class<br>";
    }
}

// Summary
echo "<h2>10. Test Summary</h2>";
echo "🎯 Plugin activation test completed<br>";
echo "📅 Test date: " . date('Y-m-d H:i:s') . "<br>";
echo "🔗 Admin URL: " . admin_url('options-general.php?page=environmental-mobile-api') . "<br>";
echo "🌐 API Documentation: " . rest_url('environmental-mobile-api/v1/docs') . "<br>";

echo "<h3>Next Steps:</h3>";
echo "1. Visit the admin interface: <a href='" . admin_url('options-general.php?page=environmental-mobile-api') . "' target='_blank'>Settings > Mobile API</a><br>";
echo "2. Test API endpoints using the built-in tester<br>";
echo "3. Check API documentation: <a href='" . rest_url('environmental-mobile-api/v1/docs') . "' target='_blank'>API Docs</a><br>";
echo "4. Verify JWT authentication is working<br>";
echo "5. Test webhook functionality<br>";

?>
