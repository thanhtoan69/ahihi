<?php
/**
 * Database Migration Script for Environmental Platform Forum
 * Migrates existing forum data to WordPress custom post types
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EP_Forum_Migration {
    
    private $wpdb;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }
    
    /**
     * Run the complete migration process
     */
    public function run_migration() {
        echo "Starting Environmental Platform Forum Migration...\n";
        
        try {
            $this->migrate_forums();
            $this->migrate_topics();
            $this->migrate_posts();
            $this->update_user_points();
            
            echo "Migration completed successfully!\n";
            return true;
            
        } catch (Exception $e) {
            echo "Migration failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Migrate forums to WordPress posts
     */
    private function migrate_forums() {
        echo "Migrating forums...\n";
        
        $forums = $this->wpdb->get_results("SELECT * FROM forums ORDER BY id ASC");
        
        foreach ($forums as $forum) {
            // Check if already migrated
            $existing = get_posts(array(
                'post_type' => 'ep_forum',
                'meta_key' => '_original_forum_id',
                'meta_value' => $forum->id,
                'post_status' => 'any',
                'numberposts' => 1
            ));
            
            if (!empty($existing)) {
                echo "Forum ID {$forum->id} already migrated, skipping...\n";
                continue;
            }
            
            $post_data = array(
                'post_title' => $forum->name,
                'post_content' => $forum->description ?: '',
                'post_status' => 'publish',
                'post_type' => 'ep_forum',
                'post_author' => 1, // Default to admin
                'post_date' => $forum->created_at ?: current_time('mysql'),
                'menu_order' => $forum->sort_order ?: 0
            );
            
            $post_id = wp_insert_post($post_data);
            
            if ($post_id && !is_wp_error($post_id)) {
                // Store original forum ID
                update_post_meta($post_id, '_original_forum_id', $forum->id);
                
                // Set forum category if available
                if (!empty($forum->category)) {
                    wp_set_object_terms($post_id, $forum->category, 'forum_category');
                }
                
                echo "Migrated forum: {$forum->name} (ID: {$post_id})\n";
            } else {
                echo "Failed to migrate forum: {$forum->name}\n";
            }
        }
    }
    
    /**
     * Migrate forum topics to WordPress posts
     */
    private function migrate_topics() {
        echo "Migrating topics...\n";
        
        $topics = $this->wpdb->get_results("SELECT * FROM forum_topics ORDER BY id ASC");
        
        foreach ($topics as $topic) {
            // Check if already migrated
            $existing = get_posts(array(
                'post_type' => 'ep_topic',
                'meta_key' => '_original_topic_id',
                'meta_value' => $topic->id,
                'post_status' => 'any',
                'numberposts' => 1
            ));
            
            if (!empty($existing)) {
                echo "Topic ID {$topic->id} already migrated, skipping...\n";
                continue;
            }
            
            // Find the migrated forum post
            $forum_posts = get_posts(array(
                'post_type' => 'ep_forum',
                'meta_key' => '_original_forum_id',
                'meta_value' => $topic->forum_id,
                'post_status' => 'any',
                'numberposts' => 1
            ));
            
            $forum_post_id = !empty($forum_posts) ? $forum_posts[0]->ID : 0;
            
            $post_data = array(
                'post_title' => $topic->title,
                'post_content' => $topic->content ?: '',
                'post_status' => 'publish',
                'post_type' => 'ep_topic',
                'post_author' => $topic->user_id ?: 1,
                'post_date' => $topic->created_at ?: current_time('mysql'),
                'post_parent' => $forum_post_id
            );
            
            $post_id = wp_insert_post($post_data);
            
            if ($post_id && !is_wp_error($post_id)) {
                // Store original topic ID and forum relationship
                update_post_meta($post_id, '_original_topic_id', $topic->id);
                update_post_meta($post_id, '_forum_id', $forum_post_id);
                update_post_meta($post_id, '_views_count', $topic->views ?: 0);
                update_post_meta($post_id, '_is_sticky', $topic->is_sticky ?: 0);
                update_post_meta($post_id, '_is_locked', $topic->is_locked ?: 0);
                
                // Set environmental topic if available
                if (!empty($topic->environmental_category)) {
                    wp_set_object_terms($post_id, $topic->environmental_category, 'environmental_topic');
                }
                
                echo "Migrated topic: {$topic->title} (ID: {$post_id})\n";
            } else {
                echo "Failed to migrate topic: {$topic->title}\n";
            }
        }
    }
    
    /**
     * Migrate forum posts to WordPress replies
     */
    private function migrate_posts() {
        echo "Migrating posts...\n";
        
        $posts = $this->wpdb->get_results("SELECT * FROM forum_posts ORDER BY id ASC");
        
        foreach ($posts as $post) {
            // Check if already migrated
            $existing = get_posts(array(
                'post_type' => 'ep_reply',
                'meta_key' => '_original_post_id',
                'meta_value' => $post->id,
                'post_status' => 'any',
                'numberposts' => 1
            ));
            
            if (!empty($existing)) {
                echo "Post ID {$post->id} already migrated, skipping...\n";
                continue;
            }
            
            // Find the migrated topic post
            $topic_posts = get_posts(array(
                'post_type' => 'ep_topic',
                'meta_key' => '_original_topic_id',
                'meta_value' => $post->topic_id,
                'post_status' => 'any',
                'numberposts' => 1
            ));
            
            $topic_post_id = !empty($topic_posts) ? $topic_posts[0]->ID : 0;
            
            $post_data = array(
                'post_title' => 'Reply to: ' . ($topic_posts[0]->post_title ?? 'Topic'),
                'post_content' => $post->content ?: '',
                'post_status' => 'publish',
                'post_type' => 'ep_reply',
                'post_author' => $post->user_id ?: 1,
                'post_date' => $post->created_at ?: current_time('mysql'),
                'post_parent' => $topic_post_id
            );
            
            $reply_id = wp_insert_post($post_data);
            
            if ($reply_id && !is_wp_error($reply_id)) {
                // Store original post ID and topic relationship
                update_post_meta($reply_id, '_original_post_id', $post->id);
                update_post_meta($reply_id, '_topic_id', $topic_post_id);
                
                echo "Migrated post: Reply (ID: {$reply_id})\n";
            } else {
                echo "Failed to migrate post ID: {$post->id}\n";
            }
        }
    }
    
    /**
     * Update user eco-points based on migrated forum activity
     */
    private function update_user_points() {
        echo "Updating user eco-points...\n";
        
        // Get all users with forum activity
        $users_with_topics = $this->wpdb->get_results("
            SELECT DISTINCT post_author, COUNT(*) as topic_count 
            FROM {$this->wpdb->posts} 
            WHERE post_type = 'ep_topic' AND post_status = 'publish'
            GROUP BY post_author
        ");
        
        $users_with_replies = $this->wpdb->get_results("
            SELECT DISTINCT post_author, COUNT(*) as reply_count 
            FROM {$this->wpdb->posts} 
            WHERE post_type = 'ep_reply' AND post_status = 'publish'
            GROUP BY post_author
        ");
        
        // Award points for topics (10 points each)
        foreach ($users_with_topics as $user_data) {
            $user_id = $user_data->post_author;
            $points = $user_data->topic_count * 10;
            
            $current_points = get_user_meta($user_id, 'eco_points', true) ?: 0;
            update_user_meta($user_id, 'eco_points', $current_points + $points);
            
            echo "Awarded {$points} points to user {$user_id} for {$user_data->topic_count} topics\n";
        }
        
        // Award points for replies (5 points each)
        foreach ($users_with_replies as $user_data) {
            $user_id = $user_data->post_author;
            $points = $user_data->reply_count * 5;
            
            $current_points = get_user_meta($user_id, 'eco_points', true) ?: 0;
            update_user_meta($user_id, 'eco_points', $current_points + $points);
            
            echo "Awarded {$points} points to user {$user_id} for {$user_data->reply_count} replies\n";
        }
    }
    
    /**
     * Rollback migration (for testing purposes)
     */
    public function rollback_migration() {
        echo "Rolling back migration...\n";
        
        // Delete migrated posts
        $post_types = array('ep_forum', 'ep_topic', 'ep_reply');
        
        foreach ($post_types as $post_type) {
            $posts = get_posts(array(
                'post_type' => $post_type,
                'post_status' => 'any',
                'numberposts' => -1
            ));
            
            foreach ($posts as $post) {
                wp_delete_post($post->ID, true);
                echo "Deleted {$post_type}: {$post->post_title}\n";
            }
        }
        
        echo "Rollback completed!\n";
    }
}

// Run migration if called directly
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('ep-forum migrate', function($args, $assoc_args) {
        $migration = new EP_Forum_Migration();
        
        if (isset($assoc_args['rollback'])) {
            $migration->rollback_migration();
        } else {
            $migration->run_migration();
        }
    });
} else if (isset($_GET['migrate']) && current_user_can('manage_options')) {
    // Web interface for migration
    $migration = new EP_Forum_Migration();
    
    echo "<pre>";
    if (isset($_GET['rollback'])) {
        $migration->rollback_migration();
    } else {
        $migration->run_migration();
    }
    echo "</pre>";
}
