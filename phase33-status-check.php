<?php
/**
 * Phase 33: Forum System Integration
 * Check current forum system status and prepare for WordPress integration
 */

// WordPress bootstrap
require_once __DIR__ . '/wp-config.php';
require_once __DIR__ . '/wp-load.php';

echo "=== PHASE 33: FORUM SYSTEM INTEGRATION - STATUS CHECK ===\n\n";

// Database connection
global $wpdb;

// 1. Check existing forum tables
echo "1. Checking existing forum database tables:\n";
$forum_tables = array(
    'forums',
    'forum_topics', 
    'forum_posts'
);

$existing_tables = array();
foreach ($forum_tables as $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    if ($exists) {
        echo "   ✅ Table '$table' exists\n";
        $existing_tables[] = $table;
        
        // Get record count
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        echo "      Records: $count\n";
    } else {
        echo "   ❌ Table '$table' missing\n";
    }
}

// 2. Check WordPress forum-related content
echo "\n2. Checking WordPress forum integration:\n";

// Check if bbPress is installed
if (function_exists('bbp_get_version')) {
    echo "   ✅ bbPress is installed (Version: " . bbp_get_version() . ")\n";
} else {
    echo "   ⚠️ bbPress not installed\n";
}

// Check custom post types
$post_types = get_post_types(array('public' => true, '_builtin' => false), 'names');
echo "   ℹ️ Custom post types: " . implode(', ', $post_types) . "\n";

// 3. Check Environmental Platform Core plugin
echo "\n3. Checking Environmental Platform Core plugin:\n";
if (is_plugin_active('environmental-platform-core/environmental-platform-core.php')) {
    echo "   ✅ Environmental Platform Core plugin is active\n";
} else {
    echo "   ❌ Environmental Platform Core plugin not active\n";
}

// 4. Check forum data if tables exist
if (!empty($existing_tables)) {
    echo "\n4. Forum data analysis:\n";
    
    if (in_array('forums', $existing_tables)) {
        $forums = $wpdb->get_results("SELECT forum_id, forum_name, forum_type, topic_count, post_count FROM forums WHERE is_active = 1 LIMIT 10");
        echo "   📋 Active forums:\n";
        foreach ($forums as $forum) {
            echo "      - {$forum->forum_name} (Type: {$forum->forum_type}, Topics: {$forum->topic_count}, Posts: {$forum->post_count})\n";
        }
    }
    
    if (in_array('forum_topics', $existing_tables)) {
        $recent_topics = $wpdb->get_results("
            SELECT ft.title, ft.topic_type, ft.status, u.username, ft.created_at 
            FROM forum_topics ft 
            LEFT JOIN users u ON ft.author_id = u.user_id 
            ORDER BY ft.created_at DESC 
            LIMIT 5
        ");
        echo "\n   📝 Recent forum topics:\n";
        foreach ($recent_topics as $topic) {
            echo "      - {$topic->title} by {$topic->username} ({$topic->status})\n";
        }
    }
}

// 5. Check current user capabilities
echo "\n5. Current user capabilities check:\n";
if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    echo "   ✅ User logged in: {$current_user->user_login}\n";
    echo "   ✅ User roles: " . implode(', ', $current_user->roles) . "\n";
} else {
    echo "   ⚠️ No user logged in\n";
}

// 6. WordPress configuration
echo "\n6. WordPress configuration:\n";
echo "   ✅ WordPress Version: " . get_bloginfo('version') . "\n";
echo "   ✅ Active Theme: " . wp_get_theme()->get('Name') . "\n";
echo "   ✅ Database Prefix: " . $wpdb->prefix . "\n";

// 7. Check required WordPress features for forum integration
echo "\n7. WordPress feature requirements:\n";

$requirements = array(
    'Custom Post Types' => function_exists('register_post_type'),
    'Custom Taxonomies' => function_exists('register_taxonomy'),
    'WordPress REST API' => class_exists('WP_REST_Server'),
    'Ajax Support' => defined('DOING_AJAX'),
    'Rewrite Rules' => function_exists('add_rewrite_rule'),
    'User Capabilities' => function_exists('add_cap'),
);

foreach ($requirements as $feature => $available) {
    if ($available) {
        echo "   ✅ $feature: Available\n";
    } else {
        echo "   ❌ $feature: Not available\n";
    }
}

echo "\n=== Phase 33 Readiness Assessment ===\n";

// Calculate readiness score
$total_checks = 7;
$passed_checks = 0;

if (!empty($existing_tables)) $passed_checks++;
if (is_plugin_active('environmental-platform-core/environmental-platform-core.php')) $passed_checks++;
if (function_exists('register_post_type')) $passed_checks++;
if (function_exists('register_taxonomy')) $passed_checks++;
if (class_exists('WP_REST_Server')) $passed_checks++;
if (function_exists('add_rewrite_rule')) $passed_checks++;
if (function_exists('add_cap')) $passed_checks++;

$readiness_score = ($passed_checks / $total_checks) * 100;

echo "📊 Readiness Score: " . round($readiness_score, 1) . "% ($passed_checks/$total_checks)\n";

if ($readiness_score >= 80) {
    echo "🎉 Status: READY for Phase 33 implementation\n";
} elseif ($readiness_score >= 60) {
    echo "⚠️ Status: MOSTLY READY - some preparation needed\n";
} else {
    echo "❌ Status: NOT READY - significant preparation required\n";
}

echo "\n=== Next Steps for Phase 33 ===\n";
echo "1. Install bbPress plugin (if needed)\n";
echo "2. Create custom forum post types\n";
echo "3. Set up forum taxonomies\n";
echo "4. Integrate existing forum data\n";
echo "5. Create forum templates\n";
echo "6. Set up forum moderation\n";
echo "7. Add gamification integration\n";

echo "\n=== Phase 33 Status Check Complete ===\n";
?>
