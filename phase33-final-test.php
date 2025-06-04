<?php
/**
 * Phase 33 Forum System Final Test
 */

require_once 'wp-config.php';
require_once 'wp-load.php';

echo "=== PHASE 33: FORUM SYSTEM FINAL TEST ===\n";
echo "==========================================\n\n";

// Test 1: Plugin Status
echo "1. Plugin Status Check\n";
echo "----------------------\n";
$plugin_active = is_plugin_active('environmental-platform-forum/environmental-platform-forum.php');
echo "Environmental Platform Forum Plugin: " . ($plugin_active ? "✓ ACTIVE" : "✗ INACTIVE") . "\n\n";

// Test 2: Custom Post Types
echo "2. Custom Post Types Registration\n";
echo "----------------------------------\n";
$post_types = get_post_types(array('public' => true), 'names');
$forum_types = array('ep_forum', 'ep_topic', 'ep_reply');

foreach ($forum_types as $type) {
    $exists = in_array($type, $post_types);
    echo "$type: " . ($exists ? "✓ REGISTERED" : "✗ NOT FOUND") . "\n";
}
echo "\n";

// Test 3: Custom Taxonomies
echo "3. Custom Taxonomies Registration\n";
echo "----------------------------------\n";
$taxonomies = get_taxonomies(array('public' => true), 'names');
$forum_taxonomies = array('forum_category', 'forum_tag', 'environmental_topic');

foreach ($forum_taxonomies as $taxonomy) {
    $exists = in_array($taxonomy, $taxonomies);
    echo "$taxonomy: " . ($exists ? "✓ REGISTERED" : "✗ NOT FOUND") . "\n";
}
echo "\n";

// Test 4: Database Content
echo "4. Database Content Check\n";
echo "-------------------------\n";
global $wpdb;

// Original forum data
$original_forums = $wpdb->get_var("SELECT COUNT(*) FROM forums");
$original_topics = $wpdb->get_var("SELECT COUNT(*) FROM forum_topics");  
$original_posts = $wpdb->get_var("SELECT COUNT(*) FROM forum_posts");

echo "Original Forum Data:\n";
echo "- Forums: $original_forums\n";
echo "- Topics: $original_topics\n";
echo "- Posts: $original_posts\n\n";

// WordPress forum data
$wp_forums = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'ep_forum' AND post_status = 'publish'");
$wp_topics = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'ep_topic' AND post_status = 'publish'");
$wp_replies = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'ep_reply' AND post_status = 'publish'");

echo "WordPress Forum Data:\n";
echo "- Forums: $wp_forums\n";
echo "- Topics: $wp_topics\n";
echo "- Replies: $wp_replies\n\n";

// Test 5: User Roles and Capabilities
echo "5. User Roles and Capabilities\n";
echo "------------------------------\n";
$forum_moderator = get_role('forum_moderator');
echo "Forum Moderator Role: " . ($forum_moderator ? "✓ EXISTS" : "✗ NOT FOUND") . "\n";

if ($forum_moderator) {
    $caps = array('read', 'edit_posts', 'moderate_comments');
    foreach ($caps as $cap) {
        $has_cap = $forum_moderator->has_cap($cap);
        echo "- $cap: " . ($has_cap ? "✓" : "✗") . "\n";
    }
}
echo "\n";

// Test 6: File Structure
echo "6. Plugin File Structure\n";
echo "------------------------\n";
$plugin_path = 'wp-content/plugins/environmental-platform-forum/';
$required_files = array(
    'environmental-platform-forum.php' => 'Main plugin file',
    'migrate-forum-data.php' => 'Migration script',
    'assets/css/forum.css' => 'CSS styles',
    'assets/js/forum.js' => 'JavaScript functionality',
    'templates/single-ep_forum.php' => 'Forum template',
    'templates/single-ep_topic.php' => 'Topic template',
    'templates/archive-ep_forum.php' => 'Archive template'
);

foreach ($required_files as $file => $description) {
    $exists = file_exists($plugin_path . $file);
    echo "$description: " . ($exists ? "✓ EXISTS" : "✗ MISSING") . "\n";
}
echo "\n";

// Test 7: URL Rewrite Rules
echo "7. URL Rewrite Rules\n";
echo "-------------------\n";
global $wp_rewrite;
$rewrite_rules = get_option('rewrite_rules');
$forum_rules = array('/forums/', '/topics/');

foreach ($forum_rules as $rule) {
    $found = false;
    if ($rewrite_rules) {
        foreach ($rewrite_rules as $pattern => $replacement) {
            if (strpos($pattern, str_replace('/', '', $rule)) !== false) {
                $found = true;
                break;
            }
        }
    }
    echo "Rewrite rule for $rule: " . ($found ? "✓ ACTIVE" : "✗ NOT FOUND") . "\n";
}
echo "\n";

// Test 8: Eco-Points Integration
echo "8. Eco-Points Integration\n";
echo "-------------------------\n";
$users_table_exists = $wpdb->get_var("SHOW TABLES LIKE 'users'");
echo "Users table: " . ($users_table_exists ? "✓ EXISTS" : "✗ NOT FOUND") . "\n";

if ($users_table_exists) {
    $eco_points_column = $wpdb->get_var("SHOW COLUMNS FROM users LIKE 'total_eco_points'");
    echo "Eco-points column: " . ($eco_points_column ? "✓ EXISTS" : "✗ NOT FOUND") . "\n";
}
echo "\n";

// Test 9: AJAX Endpoints
echo "9. AJAX Endpoints\n";
echo "-----------------\n";
$ajax_actions = array(
    'ep_create_topic' => 'Create topic',
    'ep_create_post' => 'Create reply', 
    'ep_moderate_content' => 'Moderate content'
);

foreach ($ajax_actions as $action => $description) {
    $hook_exists = has_action("wp_ajax_$action");
    echo "$description: " . ($hook_exists ? "✓ REGISTERED" : "✗ NOT FOUND") . "\n";
}
echo "\n";

// Test 10: Sample Data
echo "10. Sample Data Verification\n";
echo "----------------------------\n";
if ($wp_forums > 0) {
    $forums = get_posts(array(
        'post_type' => 'ep_forum',
        'numberposts' => 3,
        'post_status' => 'publish'
    ));
    
    echo "Sample Forums:\n";
    foreach ($forums as $forum) {
        echo "- {$forum->post_title}\n";
    }
    echo "\n";
}

if ($wp_topics > 0) {
    $topics = get_posts(array(
        'post_type' => 'ep_topic', 
        'numberposts' => 3,
        'post_status' => 'publish'
    ));
    
    echo "Sample Topics:\n";
    foreach ($topics as $topic) {
        echo "- {$topic->post_title}\n";
    }
    echo "\n";
}

// Final Score Calculation
echo "=== FINAL ASSESSMENT ===\n";
echo "========================\n";

$total_tests = 25; // Approximate number of individual tests
$passed_tests = 0;

// Calculate passed tests based on checks above
if ($plugin_active) $passed_tests++;
if (in_array('ep_forum', $post_types)) $passed_tests++;
if (in_array('ep_topic', $post_types)) $passed_tests++;
if (in_array('ep_reply', $post_types)) $passed_tests++;
if (in_array('forum_category', $taxonomies)) $passed_tests++;
if (in_array('forum_tag', $taxonomies)) $passed_tests++;
if (in_array('environmental_topic', $taxonomies)) $passed_tests++;
if ($wp_forums > 0) $passed_tests++;
if ($wp_topics > 0) $passed_tests++;
if ($wp_replies > 0) $passed_tests++;
if ($forum_moderator) $passed_tests++;
if (file_exists($plugin_path . 'environmental-platform-forum.php')) $passed_tests++;
if (file_exists($plugin_path . 'assets/css/forum.css')) $passed_tests++;
if (file_exists($plugin_path . 'assets/js/forum.js')) $passed_tests++;
if (has_action('wp_ajax_ep_create_topic')) $passed_tests++;

$score = round(($passed_tests / $total_tests) * 100, 1);

echo "Tests Passed: $passed_tests / $total_tests\n";
echo "Success Rate: $score%\n\n";

if ($score >= 90) {
    echo "🎉 EXCELLENT! Forum system is fully functional.\n";
} elseif ($score >= 80) {
    echo "✅ GOOD! Forum system is mostly functional with minor issues.\n";  
} elseif ($score >= 70) {
    echo "⚠️  ACCEPTABLE! Forum system has some issues that need attention.\n";
} else {
    echo "❌ NEEDS WORK! Forum system has significant issues.\n";
}

echo "\n=== Phase 33 Forum System Test Completed ===\n";

// Generate URLs for testing
echo "\nTesting URLs:\n";
echo "- Forum Archive: " . home_url('/forums/') . "\n";
echo "- Admin Panel: " . admin_url('admin.php?page=ep-forum-admin') . "\n";
echo "- Migration Tool: " . admin_url('admin.php?page=ep-forum-migration') . "\n";

if ($wp_forums > 0) {
    $first_forum = get_posts(array('post_type' => 'ep_forum', 'numberposts' => 1, 'post_status' => 'publish'));
    if (!empty($first_forum)) {
        echo "- Sample Forum: " . get_permalink($first_forum[0]->ID) . "\n";
    }
}

if ($wp_topics > 0) {
    $first_topic = get_posts(array('post_type' => 'ep_topic', 'numberposts' => 1, 'post_status' => 'publish'));
    if (!empty($first_topic)) {
        echo "- Sample Topic: " . get_permalink($first_topic[0]->ID) . "\n";
    }
}
?>
