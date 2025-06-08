<?php
/**
 * Direct Plugin Activation for Environmental Live Chat
 */

// Define WordPress environment
define('WP_USE_THEMES', false);
require_once('./wp-load.php');

// Force plugin activation
$plugin_file = 'environmental-live-chat/environmental-live-chat.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Plugin Activation</title>";
echo "<style>body{font-family:Arial;margin:40px;background:#f1f1f1;}";
echo ".container{background:white;padding:30px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}";
echo ".success{color:#008000;background:#d4edda;padding:10px;border-radius:4px;margin:10px 0;}";
echo ".error{color:#d00;background:#f8d7da;padding:10px;border-radius:4px;margin:10px 0;}";
echo ".info{color:#0066cc;background:#cce7ff;padding:10px;border-radius:4px;margin:10px 0;}";
echo "h1{color:#2e8b57;}</style></head><body>";
echo "<div class='container'>";
echo "<h1>🌱 Environmental Live Chat Plugin Activation</h1>";

// Check if plugin exists
$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
if (!file_exists($plugin_path)) {
    echo "<div class='error'>❌ Plugin file not found at: $plugin_path</div>";
    echo "</div></body></html>";
    exit;
}

echo "<div class='info'>📁 Plugin file found: $plugin_path</div>";

// Get current active plugins
$active_plugins = get_option('active_plugins', array());

// Check if already active
if (in_array($plugin_file, $active_plugins)) {
    echo "<div class='success'>✅ Plugin is already active!</div>";
} else {
    // Activate the plugin
    $result = activate_plugin($plugin_file);
    
    if (is_wp_error($result)) {
        echo "<div class='error'>❌ Activation failed: " . $result->get_error_message() . "</div>";
    } else {
        echo "<div class='success'>✅ Plugin activated successfully!</div>";
        
        // Trigger activation hook manually if needed
        if (function_exists('register_activation_hook')) {
            do_action('activate_' . $plugin_file);
        }
    }
}

// Verify activation
$active_plugins = get_option('active_plugins', array());
if (in_array($plugin_file, $active_plugins)) {
    echo "<div class='success'>✅ Verification: Plugin is now active in WordPress</div>";
    
    // Check if main class is loaded
    if (class_exists('Environmental_Live_Chat')) {
        echo "<div class='success'>✅ Main plugin class loaded successfully</div>";
        
        // Get plugin instance
        $instance = Environmental_Live_Chat::get_instance();
        if ($instance) {
            echo "<div class='success'>✅ Plugin instance created successfully</div>";
            
            // Force database table creation
            if (method_exists($instance, 'create_tables')) {
                $instance->create_tables();
                echo "<div class='success'>✅ Database tables created/verified</div>";
            }
        }
    } else {
        echo "<div class='error'>❌ Plugin class not found - may need manual activation</div>";
    }
} else {
    echo "<div class='error'>❌ Plugin activation verification failed</div>";
}

// Check database tables
global $wpdb;
echo "<h2>Database Table Status</h2>";

$tables_to_check = [
    'elc_chat_sessions',
    'elc_chat_messages', 
    'elc_support_tickets',
    'elc_ticket_replies',
    'elc_faq_items',
    'elc_support_analytics',
    'elc_chat_operators'
];

$all_tables_exist = true;
foreach ($tables_to_check as $table) {
    $full_table_name = $wpdb->prefix . $table;
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") === $full_table_name;
    
    if ($table_exists) {
        echo "<div class='success'>✅ Table exists: $full_table_name</div>";
    } else {
        echo "<div class='error'>❌ Table missing: $full_table_name</div>";
        $all_tables_exist = false;
    }
}

if ($all_tables_exist) {
    echo "<div class='success'>🎉 All database tables are ready!</div>";
} else {
    echo "<div class='info'>ℹ️ Some tables are missing. They will be created when the plugin initializes.</div>";
}

// Show admin links
echo "<h2>Next Steps</h2>";
echo "<div class='info'>";
echo "<p><strong>Plugin is ready! Access the admin dashboard:</strong></p>";
echo "<ul>";
echo "<li><a href='/moitruong/wp-admin/admin.php?page=env-live-chat' target='_blank'>Live Chat Dashboard</a></li>";
echo "<li><a href='/moitruong/wp-admin/admin.php?page=env-support-tickets' target='_blank'>Support Tickets</a></li>";
echo "<li><a href='/moitruong/wp-admin/admin.php?page=env-faq-manager' target='_blank'>FAQ Management</a></li>";
echo "<li><a href='/moitruong/wp-admin/admin.php?page=env-chat-analytics' target='_blank'>Analytics Dashboard</a></li>";
echo "<li><a href='/moitruong/wp-admin/admin.php?page=env-chat-settings' target='_blank'>Settings</a></li>";
echo "</ul>";
echo "<p><a href='/moitruong/wp-admin/plugins.php' target='_blank'>View All Plugins</a></p>";
echo "</div>";

echo "</div></body></html>";
?>
