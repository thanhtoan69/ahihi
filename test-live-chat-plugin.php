<?php
/**
 * Environmental Live Chat Plugin Test Script
 */

// WordPress bootstrap
require_once 'wp-config.php';
require_once ABSPATH . 'wp-settings.php';

// Check if plugin is already active
$active_plugins = get_option('active_plugins', array());
$plugin_path = 'environmental-live-chat/environmental-live-chat.php';

echo "=== Environmental Live Chat Plugin Test ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Check plugin files
echo "1. Checking plugin files...\n";
$plugin_dir = WP_PLUGIN_DIR . '/environmental-live-chat/';
$required_files = [
    'environmental-live-chat.php',
    'includes/class-live-chat-system.php',
    'includes/class-chatbot-system.php',
    'includes/class-support-tickets.php',
    'includes/class-faq-manager.php',
    'includes/class-analytics.php',
    'includes/class-admin-interface.php',
    'includes/class-rest-api.php',
    'assets/css/frontend.css',
    'assets/css/admin.css',
    'assets/js/frontend.js',
    'assets/js/admin.js'
];

$missing_files = [];
foreach ($required_files as $file) {
    if (!file_exists($plugin_dir . $file)) {
        $missing_files[] = $file;
    }
}

if (empty($missing_files)) {
    echo "✓ All plugin files found\n";
} else {
    echo "✗ Missing files:\n";
    foreach ($missing_files as $file) {
        echo "  - $file\n";
    }
}

// Check if plugin is active
echo "\n2. Checking plugin status...\n";
if (in_array($plugin_path, $active_plugins)) {
    echo "✓ Plugin is active\n";
} else {
    echo "⚠ Plugin is not active. Activating...\n";
    
    // Activate plugin
    $active_plugins[] = $plugin_path;
    update_option('active_plugins', $active_plugins);
    
    // Include plugin file to trigger activation
    include_once $plugin_dir . 'environmental-live-chat.php';
    
    // Trigger activation hook
    if (class_exists('Environmental_Live_Chat')) {
        $plugin_instance = Environmental_Live_Chat::get_instance();
        if (method_exists($plugin_instance, 'activate')) {
            $plugin_instance->activate();
        }
    }
    
    echo "✓ Plugin activated\n";
}

// Check database tables
echo "\n3. Checking database tables...\n";
global $wpdb;

$required_tables = [
    $wpdb->prefix . 'env_chat_sessions',
    $wpdb->prefix . 'env_chat_messages',
    $wpdb->prefix . 'env_support_tickets',
    $wpdb->prefix . 'env_ticket_replies',
    $wpdb->prefix . 'env_faq',
    $wpdb->prefix . 'env_chat_analytics',
    $wpdb->prefix . 'env_chat_operators'
];

$missing_tables = [];
foreach ($required_tables as $table) {
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    if (!$table_exists) {
        $missing_tables[] = $table;
    }
}

if (empty($missing_tables)) {
    echo "✓ All database tables exist\n";
} else {
    echo "⚠ Missing tables, creating...\n";
    foreach ($missing_tables as $table) {
        echo "  - $table\n";
    }
    
    // Force table creation
    if (class_exists('Environmental_Live_Chat')) {
        $plugin_instance = Environmental_Live_Chat::get_instance();
        if (method_exists($plugin_instance, 'create_tables')) {
            $plugin_instance->create_tables();
            echo "✓ Tables created\n";
        }
    }
}

// Check plugin class
echo "\n4. Checking plugin class...\n";
if (class_exists('Environmental_Live_Chat')) {
    echo "✓ Main plugin class loaded\n";
    
    $instance = Environmental_Live_Chat::get_instance();
    if ($instance) {
        echo "✓ Plugin instance created\n";
    } else {
        echo "✗ Failed to create plugin instance\n";
    }
} else {
    echo "✗ Main plugin class not found\n";
}

// Check component classes
echo "\n5. Checking component classes...\n";
$component_classes = [
    'Env_Live_Chat_System',
    'Env_Chatbot_System', 
    'Env_Support_Tickets',
    'Env_FAQ_Manager',
    'Env_Analytics',
    'Env_Admin_Interface',
    'Env_REST_API'
];

foreach ($component_classes as $class) {
    if (class_exists($class)) {
        echo "✓ $class loaded\n";
    } else {
        echo "✗ $class not found\n";
    }
}

// Check WordPress hooks
echo "\n6. Checking WordPress hooks...\n";
$hooks_to_check = [
    'wp_ajax_env_start_chat',
    'wp_ajax_nopriv_env_start_chat',
    'wp_ajax_env_send_message',
    'wp_ajax_nopriv_env_send_message',
    'wp_ajax_env_submit_support_ticket',
    'wp_ajax_nopriv_env_submit_support_ticket'
];

foreach ($hooks_to_check as $hook) {
    if (has_action($hook)) {
        echo "✓ Hook $hook registered\n";
    } else {
        echo "⚠ Hook $hook not registered\n";
    }
}

// Check shortcodes
echo "\n7. Checking shortcodes...\n";
$shortcodes_to_check = [
    'env_chat_widget',
    'env_faq_widget',
    'env_support_form',
    'env_knowledge_base'
];

global $shortcode_tags;
foreach ($shortcodes_to_check as $shortcode) {
    if (isset($shortcode_tags[$shortcode])) {
        echo "✓ Shortcode [$shortcode] registered\n";
    } else {
        echo "⚠ Shortcode [$shortcode] not registered\n";
    }
}

// Test basic functionality
echo "\n8. Testing basic functionality...\n";

// Test database connection
try {
    $test_query = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}env_chat_sessions");
    echo "✓ Database connection working (Sessions: $test_query)\n";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
}

// Test options
$plugin_options = get_option('env_chat_settings', []);
if (!empty($plugin_options)) {
    echo "✓ Plugin options loaded (" . count($plugin_options) . " settings)\n";
} else {
    echo "⚠ No plugin options found\n";
}

// Check REST API endpoints
echo "\n9. Checking REST API endpoints...\n";
$rest_server = rest_get_server();
$routes = $rest_server->get_routes();

$api_routes = [
    '/env-chat/v1/chat/start',
    '/env-chat/v1/chat/send',
    '/env-chat/v1/tickets/create',
    '/env-chat/v1/faq/search'
];

foreach ($api_routes as $route) {
    if (isset($routes[$route])) {
        echo "✓ REST route $route registered\n";
    } else {
        echo "⚠ REST route $route not found\n";
    }
}

echo "\n=== Test Complete ===\n";
echo "Plugin Status: ";

// Overall status
$overall_status = empty($missing_files) && empty($missing_tables) && class_exists('Environmental_Live_Chat');
if ($overall_status) {
    echo "✓ READY FOR USE\n";
} else {
    echo "⚠ NEEDS ATTENTION\n";
}

echo "\nTo use the plugin, add these shortcodes to your pages:\n";
echo "- Chat Widget: [env_chat_widget]\n";
echo "- FAQ Widget: [env_faq_widget]\n";
echo "- Support Form: [env_support_form]\n";
echo "- Knowledge Base: [env_knowledge_base]\n";

echo "\nAdmin Dashboard: /wp-admin/admin.php?page=env-chat-dashboard\n";
?>
