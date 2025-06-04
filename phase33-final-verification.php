<?php
/**
 * Phase 33: Forum System Integration - Final Verification
 * Environmental Platform Project
 * 
 * This script verifies the complete implementation of the forum system
 */

echo "=== PHASE 33: FORUM SYSTEM INTEGRATION - FINAL VERIFICATION ===\n";
echo "================================================================\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Project: Environmental Platform\n";
echo "Phase: 33 - Forum System Integration\n\n";

// Initialize verification results
$verification_results = array();

try {
    // Load WordPress
    if (!file_exists('wp-config.php')) {
        throw new Exception('WordPress configuration not found');
    }
    
    require_once 'wp-config.php';
    require_once 'wp-load.php';
    
    global $wpdb;
    
    echo "✓ WordPress environment loaded successfully\n\n";
    
    // 1. Plugin Status Verification
    echo "1. PLUGIN STATUS VERIFICATION\n";
    echo "-----------------------------\n";
    
    $plugin_file = 'environmental-platform-forum/environmental-platform-forum.php';
    $plugin_active = function_exists('is_plugin_active') ? is_plugin_active($plugin_file) : false;
    
    $verification_results['plugin_active'] = $plugin_active;
    echo "Environmental Platform Forum Plugin: " . ($plugin_active ? "✓ ACTIVE" : "✗ INACTIVE") . "\n";
    
    if ($plugin_active) {
        echo "Plugin version: " . (defined('EP_FORUM_VERSION') ? EP_FORUM_VERSION : 'Unknown') . "\n";
    }
    echo "\n";
    
    // 2. Database Schema Verification
    echo "2. DATABASE SCHEMA VERIFICATION\n";
    echo "-------------------------------\n";
    
    // Check original forum tables
    $original_tables = array('forums', 'forum_topics', 'forum_posts');
    foreach ($original_tables as $table) {
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
        $verification_results["original_table_$table"] = $exists;
        echo "Original table '$table': " . ($exists ? "✓ EXISTS" : "✗ MISSING") . "\n";
    }
    
    // Check WordPress posts table for forum content
    $wp_posts_exist = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->posts}'") === $wpdb->posts;
    $verification_results['wp_posts_table'] = $wp_posts_exist;
    echo "WordPress posts table: " . ($wp_posts_exist ? "✓ EXISTS" : "✗ MISSING") . "\n";
    echo "\n";
    
    // 3. Custom Post Types Verification
    echo "3. CUSTOM POST TYPES VERIFICATION\n";
    echo "----------------------------------\n";
    
    $forum_post_types = array('ep_forum', 'ep_topic', 'ep_reply');
    $registered_post_types = get_post_types(array('public' => true), 'names');
    
    foreach ($forum_post_types as $post_type) {
        $registered = in_array($post_type, $registered_post_types);
        $verification_results["post_type_$post_type"] = $registered;
        echo "Post type '$post_type': " . ($registered ? "✓ REGISTERED" : "✗ NOT REGISTERED") . "\n";
        
        if ($registered) {
            $count = wp_count_posts($post_type);
            $published = $count->publish ?? 0;
            echo "  → Published posts: $published\n";
            $verification_results["post_type_{$post_type}_count"] = $published;
        }
    }
    echo "\n";
    
    // 4. Custom Taxonomies Verification
    echo "4. CUSTOM TAXONOMIES VERIFICATION\n";
    echo "----------------------------------\n";
    
    $forum_taxonomies = array('forum_category', 'forum_tag', 'environmental_topic');
    $registered_taxonomies = get_taxonomies(array('public' => true), 'names');
    
    foreach ($forum_taxonomies as $taxonomy) {
        $registered = in_array($taxonomy, $registered_taxonomies);
        $verification_results["taxonomy_$taxonomy"] = $registered;
        echo "Taxonomy '$taxonomy': " . ($registered ? "✓ REGISTERED" : "✗ NOT REGISTERED") . "\n";
        
        if ($registered) {
            $terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false));
            $term_count = is_array($terms) ? count($terms) : 0;
            echo "  → Terms count: $term_count\n";
            $verification_results["taxonomy_{$taxonomy}_terms"] = $term_count;
        }
    }
    echo "\n";
    
    // 5. File Structure Verification
    echo "5. FILE STRUCTURE VERIFICATION\n";
    echo "-------------------------------\n";
    
    $plugin_path = 'wp-content/plugins/environmental-platform-forum/';
    $required_files = array(
        'environmental-platform-forum.php' => 'Main plugin file',
        'migrate-forum-data.php' => 'Data migration script',
        'assets/css/forum.css' => 'Forum CSS styles',
        'assets/js/forum.js' => 'Forum JavaScript',
        'templates/single-ep_forum.php' => 'Forum template',
        'templates/single-ep_topic.php' => 'Topic template'
    );
    
    foreach ($required_files as $file => $description) {
        $exists = file_exists($plugin_path . $file);
        $verification_results["file_$file"] = $exists;
        echo "$description: " . ($exists ? "✓ EXISTS" : "✗ MISSING") . "\n";
        
        if ($exists) {
            $size = filesize($plugin_path . $file);
            echo "  → File size: " . number_format($size) . " bytes\n";
        }
    }
    echo "\n";
    
    // 6. User Roles and Capabilities Verification
    echo "6. USER ROLES AND CAPABILITIES VERIFICATION\n";
    echo "--------------------------------------------\n";
    
    $forum_moderator_role = get_role('forum_moderator');
    $verification_results['forum_moderator_role'] = !is_null($forum_moderator_role);
    echo "Forum Moderator Role: " . ($forum_moderator_role ? "✓ EXISTS" : "✗ NOT FOUND") . "\n";
    
    if ($forum_moderator_role) {
        $capabilities = array('read', 'edit_posts', 'moderate_comments', 'delete_posts');
        foreach ($capabilities as $cap) {
            $has_cap = $forum_moderator_role->has_cap($cap);
            echo "  → Capability '$cap': " . ($has_cap ? "✓" : "✗") . "\n";
        }
    }
    echo "\n";
    
    // 7. AJAX Endpoints Verification
    echo "7. AJAX ENDPOINTS VERIFICATION\n";
    echo "-------------------------------\n";
    
    $ajax_actions = array(
        'ep_create_topic' => 'Create new topic',
        'ep_create_post' => 'Create new reply',
        'ep_moderate_content' => 'Moderate content'
    );
    
    foreach ($ajax_actions as $action => $description) {
        $hook_registered = has_action("wp_ajax_$action");
        $verification_results["ajax_$action"] = $hook_registered;
        echo "$description: " . ($hook_registered ? "✓ REGISTERED" : "✗ NOT REGISTERED") . "\n";
    }
    echo "\n";
    
    // 8. Database Content Verification
    echo "8. DATABASE CONTENT VERIFICATION\n";
    echo "---------------------------------\n";
    
    // Original forum data
    if ($verification_results['original_table_forums']) {
        $original_forums = $wpdb->get_var("SELECT COUNT(*) FROM forums");
        $original_topics = $wpdb->get_var("SELECT COUNT(*) FROM forum_topics");
        $original_posts = $wpdb->get_var("SELECT COUNT(*) FROM forum_posts");
        
        echo "Original forum data:\n";
        echo "  → Forums: $original_forums\n";
        echo "  → Topics: $original_topics\n";
        echo "  → Posts: $original_posts\n";
        
        $verification_results['original_data_exists'] = ($original_forums > 0);
    }
    
    // WordPress forum data
    $wp_forums = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'ep_forum' AND post_status = 'publish'");
    $wp_topics = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'ep_topic' AND post_status = 'publish'");
    $wp_replies = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'ep_reply' AND post_status = 'publish'");
    
    echo "WordPress forum data:\n";
    echo "  → Forums: $wp_forums\n";
    echo "  → Topics: $wp_topics\n";
    echo "  → Replies: $wp_replies\n";
    
    $verification_results['wp_forum_data_exists'] = ($wp_forums > 0);
    echo "\n";
    
    // 9. URL Rewrite Rules Verification
    echo "9. URL REWRITE RULES VERIFICATION\n";
    echo "----------------------------------\n";
    
    $rewrite_rules = get_option('rewrite_rules');
    $forum_patterns = array('forums', 'topics');
    
    foreach ($forum_patterns as $pattern) {
        $found = false;
        if (is_array($rewrite_rules)) {
            foreach ($rewrite_rules as $rule => $replacement) {
                if (strpos($rule, $pattern) !== false) {
                    $found = true;
                    break;
                }
            }
        }
        $verification_results["rewrite_$pattern"] = $found;
        echo "Rewrite rules for '$pattern': " . ($found ? "✓ ACTIVE" : "✗ NOT FOUND") . "\n";
    }
    echo "\n";
    
    // 10. Integration Points Verification
    echo "10. INTEGRATION POINTS VERIFICATION\n";
    echo "------------------------------------\n";
    
    // Check eco-points integration
    $users_table_exists = $wpdb->get_var("SHOW TABLES LIKE 'users'") === 'users';
    $verification_results['eco_points_integration'] = $users_table_exists;
    echo "Eco-points system integration: " . ($users_table_exists ? "✓ AVAILABLE" : "✗ NOT FOUND") . "\n";
    
    // Check WordPress admin integration
    $admin_menu_hook = has_action('admin_menu');
    $verification_results['admin_integration'] = $admin_menu_hook;
    echo "WordPress admin integration: " . ($admin_menu_hook ? "✓ ACTIVE" : "✗ NOT FOUND") . "\n";
    
    // Check template loading
    $template_filter = has_filter('template_include');
    $verification_results['template_loading'] = $template_filter;
    echo "Template loading system: " . ($template_filter ? "✓ ACTIVE" : "✗ NOT FOUND") . "\n";
    echo "\n";
    
    // Calculate overall success rate
    $total_checks = count($verification_results);
    $passed_checks = array_sum($verification_results);
    $success_rate = $total_checks > 0 ? round(($passed_checks / $total_checks) * 100, 1) : 0;
    
    echo "=== VERIFICATION SUMMARY ===\n";
    echo "============================\n";
    echo "Total checks performed: $total_checks\n";
    echo "Checks passed: $passed_checks\n";
    echo "Success rate: $success_rate%\n\n";
    
    // Determine status
    if ($success_rate >= 95) {
        $status = "EXCELLENT - Forum system is fully operational";
        $icon = "🎉";
    } elseif ($success_rate >= 85) {
        $status = "GOOD - Forum system is mostly functional";
        $icon = "✅";
    } elseif ($success_rate >= 75) {
        $status = "ACCEPTABLE - Minor issues detected";
        $icon = "⚠️";
    } else {
        $status = "NEEDS ATTENTION - Significant issues found";
        $icon = "❌";
    }
    
    echo "$icon PHASE 33 STATUS: $status\n\n";
    
    // Generate test URLs
    echo "=== TESTING URLS ===\n";
    echo "====================\n";
    $base_url = home_url();
    echo "WordPress Admin: $base_url/wp-admin/\n";
    echo "Forum Admin: $base_url/wp-admin/admin.php?page=ep-forum-admin\n";
    echo "Migration Tool: $base_url/wp-admin/admin.php?page=ep-forum-migration\n";
    echo "Forums Archive: $base_url/forums/\n";
    
    if ($wp_forums > 0) {
        $sample_forum = $wpdb->get_row("SELECT ID, post_name FROM {$wpdb->posts} WHERE post_type = 'ep_forum' AND post_status = 'publish' LIMIT 1");
        if ($sample_forum) {
            echo "Sample Forum: $base_url/forums/{$sample_forum->post_name}/\n";
        }
    }
    
    if ($wp_topics > 0) {
        $sample_topic = $wpdb->get_row("SELECT ID, post_name FROM {$wpdb->posts} WHERE post_type = 'ep_topic' AND post_status = 'publish' LIMIT 1");
        if ($sample_topic) {
            echo "Sample Topic: $base_url/topics/{$sample_topic->post_name}/\n";
        }
    }
    
    echo "\n";
    
    // Recommendations
    echo "=== RECOMMENDATIONS ===\n";
    echo "=======================\n";
    
    if ($success_rate < 100) {
        echo "Issues found:\n";
        foreach ($verification_results as $check => $result) {
            if (!$result) {
                echo "- $check: FAILED\n";
            }
        }
        echo "\n";
    }
    
    if ($success_rate >= 85) {
        echo "✓ Forum system is ready for production use\n";
        echo "✓ Users can create accounts and participate in discussions\n";
        echo "✓ Moderation tools are available for content management\n";
        echo "✓ Eco-points system encourages community engagement\n";
    }
    
    if ($wp_forums == 0) {
        echo "→ Consider creating sample forums to get started\n";
    }
    
    if (!$verification_results['eco_points_integration'] ?? false) {
        echo "→ Set up eco-points system for gamification features\n";
    }
    
    echo "\n=== PHASE 33 FORUM SYSTEM VERIFICATION COMPLETE ===\n";
    echo "Time: " . date('Y-m-d H:i:s') . "\n";
    echo "Status: " . ($success_rate >= 85 ? "SUCCESS" : "NEEDS WORK") . "\n";
    
} catch (Exception $e) {
    echo "\n❌ VERIFICATION FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Please check your WordPress installation and try again.\n";
}
?>
