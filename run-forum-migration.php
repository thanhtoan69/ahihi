<?php
/**
 * Standalone Forum Migration Script
 * Run this to migrate existing forum data to WordPress
 */

// Include WordPress
require_once 'wp-config.php';
require_once 'wp-load.php';

echo "Environmental Platform Forum Migration\n";
echo "=====================================\n\n";

// Check if plugin is active
if (!is_plugin_active('environmental-platform-forum/environmental-platform-forum.php')) {
    echo "Activating Environmental Platform Forum plugin...\n";
    $result = activate_plugin('environmental-platform-forum/environmental-platform-forum.php');
    if (is_wp_error($result)) {
        echo "Plugin activation failed: " . $result->get_error_message() . "\n";
        exit(1);
    } else {
        echo "Plugin activated successfully!\n\n";
    }
}

// Include the migration class
require_once 'wp-content/plugins/environmental-platform-forum/migrate-forum-data.php';

// Run the migration
if (class_exists('EP_Forum_Migration')) {
    $migration = new EP_Forum_Migration();
    $result = $migration->run_migration();
    
    if ($result) {
        echo "\n=== MIGRATION COMPLETED SUCCESSFULLY ===\n";
        
        // Show statistics
        echo "\nForum Statistics after migration:\n";
        $forums_count = wp_count_posts('ep_forum');
        $topics_count = wp_count_posts('ep_topic');
        $replies_count = wp_count_posts('ep_reply');
        
        echo "- Forums: " . ($forums_count->publish ?? 0) . "\n";
        echo "- Topics: " . ($topics_count->publish ?? 0) . "\n";
        echo "- Replies: " . ($replies_count->publish ?? 0) . "\n";
        
        // Flush rewrite rules
        flush_rewrite_rules();
        echo "\nRewrite rules flushed.\n";
        
    } else {
        echo "\n=== MIGRATION FAILED ===\n";
        exit(1);
    }
} else {
    echo "Migration class not found!\n";
    exit(1);
}

echo "\nMigration process completed.\n";
?>
