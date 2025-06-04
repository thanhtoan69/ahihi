<?php
/**
 * Forum Testing and Setup Script
 */

echo "=== Environmental Platform Forum Setup ===\n";

// Load WordPress
require_once 'wp-config.php';
require_once 'wp-load.php';

echo "WordPress loaded successfully.\n";

// Check plugin status
$plugin_file = 'environmental-platform-forum/environmental-platform-forum.php';
if (!is_plugin_active($plugin_file)) {
    echo "Activating forum plugin...\n";
    $result = activate_plugin($plugin_file);
    if (is_wp_error($result)) {
        echo "Plugin activation failed: " . $result->get_error_message() . "\n";
        exit(1);
    }
}

echo "Plugin is active.\n";

// Check if migration is needed
$forums_count = wp_count_posts('ep_forum');
$existing_forums = $forums_count->publish ?? 0;

echo "Current WordPress forums: $existing_forums\n";

// Check original forum data
global $wpdb;
$original_forums = $wpdb->get_var("SELECT COUNT(*) FROM forums");
$original_topics = $wpdb->get_var("SELECT COUNT(*) FROM forum_topics");
$original_posts = $wpdb->get_var("SELECT COUNT(*) FROM forum_posts");

echo "Original forum data:\n";
echo "- Forums: $original_forums\n";
echo "- Topics: $original_topics\n";
echo "- Posts: $original_posts\n";

// Create sample forum if none exist
if ($existing_forums == 0) {
    echo "Creating sample forums...\n";
    
    // Create sample forums
    $forum_data = array(
        array(
            'title' => 'Thảo luận chung về Môi trường',
            'content' => 'Nơi thảo luận các vấn đề môi trường tổng quát và chia sẻ kiến thức.',
            'category' => 'general'
        ),
        array(
            'title' => 'Phân loại rác thải',
            'content' => 'Hướng dẫn và thảo luận về phân loại rác thải hiệu quả.',
            'category' => 'waste-management'
        ),
        array(
            'title' => 'Năng lượng tái tạo',
            'content' => 'Chia sẻ về các giải pháp năng lượng sạch và bền vững.',
            'category' => 'renewable-energy'
        ),
        array(
            'title' => 'Bảo vệ thiên nhiên',
            'content' => 'Thảo luận về bảo tồn đa dạng sinh học và bảo vệ môi trường tự nhiên.',
            'category' => 'conservation'
        )
    );
    
    foreach ($forum_data as $forum) {
        $post_id = wp_insert_post(array(
            'post_title' => $forum['title'],
            'post_content' => $forum['content'],
            'post_status' => 'publish',
            'post_type' => 'ep_forum',
            'post_author' => 1
        ));
        
        if ($post_id && !is_wp_error($post_id)) {
            // Set forum category
            wp_set_object_terms($post_id, $forum['category'], 'forum_category');
            echo "Created forum: {$forum['title']}\n";
        }
    }
}

// Create sample topic
$topics_count = wp_count_posts('ep_topic');
if (($topics_count->publish ?? 0) == 0) {
    echo "Creating sample topics...\n";
    
    $forums = get_posts(array(
        'post_type' => 'ep_forum',
        'numberposts' => 1,
        'post_status' => 'publish'
    ));
    
    if (!empty($forums)) {
        $forum_id = $forums[0]->ID;
        
        $topic_id = wp_insert_post(array(
            'post_title' => 'Chào mừng đến với Forum Môi trường!',
            'post_content' => 'Đây là chủ đề đầu tiên trong forum. Hãy cùng nhau chia sẻ kiến thức và kinh nghiệm về bảo vệ môi trường!',
            'post_status' => 'publish',
            'post_type' => 'ep_topic',
            'post_author' => 1,
            'post_parent' => $forum_id
        ));
        
        if ($topic_id && !is_wp_error($topic_id)) {
            update_post_meta($topic_id, '_forum_id', $forum_id);
            update_post_meta($topic_id, '_views_count', 0);
            update_post_meta($topic_id, '_is_sticky', 1);
            
            echo "Created sample topic: Welcome topic\n";
            
            // Create sample reply
            $reply_id = wp_insert_post(array(
                'post_content' => 'Cảm ơn bạn đã tham gia forum! Đây là một phản hồi mẫu. Hãy chia sẻ ý kiến của bạn về các vấn đề môi trường.',
                'post_status' => 'publish',
                'post_type' => 'ep_reply',
                'post_author' => 1,
                'post_parent' => $topic_id
            ));
            
            if ($reply_id && !is_wp_error($reply_id)) {
                update_post_meta($reply_id, '_topic_id', $topic_id);
                update_post_meta($topic_id, '_topic_replies', 1);
                echo "Created sample reply\n";
            }
        }
    }
}

// Flush rewrite rules
flush_rewrite_rules();
echo "Rewrite rules flushed.\n";

// Final statistics
$final_forums = wp_count_posts('ep_forum');
$final_topics = wp_count_posts('ep_topic'); 
$final_replies = wp_count_posts('ep_reply');

echo "\n=== Forum Setup Complete ===\n";
echo "Current WordPress forum data:\n";
echo "- Forums: " . ($final_forums->publish ?? 0) . "\n";
echo "- Topics: " . ($final_topics->publish ?? 0) . "\n";
echo "- Replies: " . ($final_replies->publish ?? 0) . "\n";

echo "\nForum URLs:\n";
echo "- Main Forum: " . home_url('/forums/') . "\n";
echo "- Admin: " . admin_url('admin.php?page=ep-forum-admin') . "\n";

echo "\nSetup completed successfully!\n";
?>
