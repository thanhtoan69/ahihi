<?php
/**
 * Simple Live Chat Plugin Activation Script
 */

// WordPress bootstrap
require_once 'wp-config.php';
require_once ABSPATH . 'wp-settings.php';

echo "Environmental Live Chat Plugin Activation\n";
echo "=========================================\n\n";

// Get current active plugins
$active_plugins = get_option('active_plugins', array());
$plugin_path = 'environmental-live-chat/environmental-live-chat.php';

echo "1. Current plugin status:\n";
if (in_array($plugin_path, $active_plugins)) {
    echo "   ✓ Plugin is already active\n";
} else {
    echo "   ⚠ Plugin is not active\n";
    echo "   → Activating plugin...\n";
    
    // Add to active plugins
    $active_plugins[] = $plugin_path;
    update_option('active_plugins', $active_plugins);
    
    echo "   ✓ Plugin activated in WordPress\n";
}

// Include and initialize plugin
echo "\n2. Loading plugin files:\n";
$plugin_file = WP_PLUGIN_DIR . '/environmental-live-chat/environmental-live-chat.php';

if (file_exists($plugin_file)) {
    echo "   ✓ Main plugin file found\n";
    
    // Include the plugin
    include_once $plugin_file;
    
    echo "   ✓ Plugin file loaded\n";
    
    // Check if main class exists
    if (class_exists('Environmental_Live_Chat')) {
        echo "   ✓ Main class loaded\n";
        
        // Get instance and run activation
        $instance = Environmental_Live_Chat::get_instance();
        if ($instance) {
            echo "   ✓ Plugin instance created\n";
            
            // Run activation if method exists
            if (method_exists($instance, 'activate')) {
                $instance->activate();
                echo "   ✓ Activation hook executed\n";
            }
        }
    } else {
        echo "   ✗ Main class not found\n";
    }
} else {
    echo "   ✗ Main plugin file not found at: $plugin_file\n";
}

// Check database tables
echo "\n3. Checking database tables:\n";
global $wpdb;

$tables = [
    'env_chat_sessions' => 'Chat Sessions',
    'env_chat_messages' => 'Chat Messages', 
    'env_support_tickets' => 'Support Tickets',
    'env_ticket_replies' => 'Ticket Replies',
    'env_faq' => 'FAQ',
    'env_chat_analytics' => 'Analytics',
    'env_chat_operators' => 'Chat Operators'
];

foreach ($tables as $table => $description) {
    $full_table_name = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");
    
    if ($exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table_name");
        echo "   ✓ $description ($full_table_name) - $count records\n";
    } else {
        echo "   ✗ $description ($full_table_name) - Table missing\n";
    }
}

// Check plugin options
echo "\n4. Checking plugin options:\n";
$options = get_option('env_chat_settings');
if ($options) {
    echo "   ✓ Plugin settings found (" . count($options) . " options)\n";
} else {
    echo "   ⚠ No plugin settings found (will use defaults)\n";
}

echo "\n5. Plugin URLs:\n";
echo "   Admin Dashboard: " . admin_url('admin.php?page=env-chat-dashboard') . "\n";
echo "   Live Chat: " . admin_url('admin.php?page=env-chat-live-chat') . "\n";
echo "   Settings: " . admin_url('admin.php?page=env-chat-settings') . "\n";

echo "\n6. Shortcodes available:\n";
echo "   [env_chat_widget] - Chat widget\n";
echo "   [env_faq_widget] - FAQ widget\n";
echo "   [env_support_form] - Support form\n";
echo "   [env_knowledge_base] - Knowledge base\n";

echo "\n✓ ACTIVATION COMPLETE!\n";
echo "\nNext steps:\n";
echo "1. Visit the admin dashboard to configure settings\n";
echo "2. Add shortcodes to your pages\n";
echo "3. Test the chat functionality\n";
?>
