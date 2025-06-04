<?php
/**
 * Proper Plugin Activation with Hooks
 */

define('WP_USE_THEMES', false);
require_once('wp-load.php');

echo "=== PLUGIN ACTIVATION WITH HOOKS ===\n";

// Deactivate first to ensure clean state
deactivate_plugins('environmental-platform-petitions/environmental-platform-petitions.php');
echo "1. Plugin deactivated\n";

// Activate properly with hooks
$result = activate_plugin('environmental-platform-petitions/environmental-platform-petitions.php');

if (is_wp_error($result)) {
    echo "❌ Activation failed: " . $result->get_error_message() . "\n";
} else {
    echo "✅ Plugin activated successfully with hooks\n";
    
    // Verify activation
    if (is_plugin_active('environmental-platform-petitions/environmental-platform-petitions.php')) {
        echo "✅ Plugin is now active\n";
        
        // Check if database tables were created
        global $wpdb;
        $tables = ['petition_signatures', 'petition_analytics', 'petition_milestones'];
        
        echo "\n--- Database Tables Check ---\n";
        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
            if ($exists) {
                echo "✅ {$table} table created\n";
            } else {
                echo "❌ {$table} table missing\n";
            }
        }
        
        // Check if classes are available
        echo "\n--- Classes Check ---\n";
        $classes = ['EPP_Database', 'EPP_Signature_Manager', 'EPP_Admin_Dashboard'];
        foreach ($classes as $class) {
            if (class_exists($class)) {
                echo "✅ {$class} class loaded\n";
            } else {
                echo "❌ {$class} class not found\n";
            }
        }
        
    } else {
        echo "❌ Plugin activation verification failed\n";
    }
}

echo "\n🎉 Activation process complete!\n";
echo "🌐 Test the system at: http://localhost/moitruong/wp-admin/tools.php?page=petition-system-test\n";
?>
