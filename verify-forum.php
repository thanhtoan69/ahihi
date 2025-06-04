<?php
/**
 * Forum Verification Script
 */

try {
    echo "Starting forum verification...\n";
    
    // Load WordPress with error handling
    if (!file_exists('wp-config.php')) {
        throw new Exception('WordPress config not found');
    }
    
    require_once 'wp-config.php';
    require_once 'wp-load.php';
    
    echo "WordPress loaded.\n";
    
    // Check database connection
    global $wpdb;
    $result = $wpdb->get_var("SELECT 1");
    if ($result !== '1') {
        throw new Exception('Database connection failed');
    }
    
    echo "Database connected.\n";
    
    // Check forum tables
    $forums_table = $wpdb->get_var("SHOW TABLES LIKE 'forums'");
    $topics_table = $wpdb->get_var("SHOW TABLES LIKE 'forum_topics'");
    $posts_table = $wpdb->get_var("SHOW TABLES LIKE 'forum_posts'");
    
    echo "Original forum tables:\n";
    echo "- forums: " . ($forums_table ? 'EXISTS' : 'NOT FOUND') . "\n";
    echo "- forum_topics: " . ($topics_table ? 'EXISTS' : 'NOT FOUND') . "\n";
    echo "- forum_posts: " . ($posts_table ? 'EXISTS' : 'NOT FOUND') . "\n";
    
    if ($forums_table) {
        $forum_count = $wpdb->get_var("SELECT COUNT(*) FROM forums");
        echo "Original forums count: $forum_count\n";
    }
    
    // Check WordPress post types
    $wp_forums = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'ep_forum' AND post_status = 'publish'");
    $wp_topics = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'ep_topic' AND post_status = 'publish'");
    $wp_replies = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'ep_reply' AND post_status = 'publish'");
    
    echo "\nWordPress forum posts:\n";
    echo "- Forums: $wp_forums\n";
    echo "- Topics: $wp_topics\n";
    echo "- Replies: $wp_replies\n";
    
    // Check plugin status
    if (function_exists('is_plugin_active')) {
        $is_active = is_plugin_active('environmental-platform-forum/environmental-platform-forum.php');
        echo "\nPlugin status: " . ($is_active ? 'ACTIVE' : 'INACTIVE') . "\n";
    }
    
    echo "\nVerification completed successfully.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
