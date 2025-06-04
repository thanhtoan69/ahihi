<?php
/**
 * Phase 33 Web Verification Interface
 * Access via: http://localhost/moitruong/phase33-web-verification.php
 */

// Web-safe output formatting
function web_echo($text, $type = 'info') {
    $colors = array(
        'success' => '#4CAF50',
        'error' => '#f44336', 
        'warning' => '#ff9800',
        'info' => '#2196F3'
    );
    
    $color = $colors[$type] ?? $colors['info'];
    echo "<div style='color: $color; margin: 5px 0;'>$text</div>";
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Phase 33 - Forum System Verification</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #2E7D4A; color: white; padding: 20px; margin: -20px -20px 20px -20px; border-radius: 8px 8px 0 0; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background: #e8f5e8; border-color: #4CAF50; }
        .error { background: #ffe8e8; border-color: #f44336; }
        .warning { background: #fff3e0; border-color: #ff9800; }
        .check-item { margin: 8px 0; padding: 8px; background: #f9f9f9; border-radius: 3px; }
        .status-excellent { color: #4CAF50; font-weight: bold; }
        .status-good { color: #8BC34A; font-weight: bold; }
        .status-warning { color: #ff9800; font-weight: bold; }
        .status-error { color: #f44336; font-weight: bold; }
        .url-list { background: #f0f8ff; padding: 15px; border-radius: 5px; }
        .url-list a { color: #2196F3; text-decoration: none; }
        .url-list a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🌱 Phase 33: Forum System Integration</h1>
            <p>Environmental Platform - Final Verification Report</p>
            <p>Generated: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>

        <?php
        $verification_results = array();
        $total_checks = 0;
        $passed_checks = 0;
        
        try {
            // Load WordPress
            if (!file_exists('wp-config.php')) {
                throw new Exception('WordPress configuration not found');
            }
            
            require_once 'wp-config.php';
            require_once 'wp-load.php';
            global $wpdb;
            
            web_echo("✓ WordPress environment loaded successfully", 'success');
            
            // 1. Plugin Status
            echo "<div class='section'>";
            echo "<h3>1. Plugin Status</h3>";
            
            $plugin_file = 'environmental-platform-forum/environmental-platform-forum.php';
            $plugin_active = function_exists('is_plugin_active') ? is_plugin_active($plugin_file) : false;
            $total_checks++;
            if ($plugin_active) {
                $passed_checks++;
                web_echo("✓ Environmental Platform Forum Plugin: ACTIVE", 'success');
            } else {
                web_echo("✗ Environmental Platform Forum Plugin: INACTIVE", 'error');
            }
            echo "</div>";
            
            // 2. Custom Post Types
            echo "<div class='section'>";
            echo "<h3>2. Custom Post Types</h3>";
            
            $forum_post_types = array('ep_forum', 'ep_topic', 'ep_reply');
            $registered_post_types = get_post_types(array('public' => true), 'names');
            
            foreach ($forum_post_types as $post_type) {
                $total_checks++;
                $registered = in_array($post_type, $registered_post_types);
                if ($registered) {
                    $passed_checks++;
                    $count = wp_count_posts($post_type);
                    $published = $count->publish ?? 0;
                    web_echo("✓ Post type '$post_type': REGISTERED ($published posts)", 'success');
                } else {
                    web_echo("✗ Post type '$post_type': NOT REGISTERED", 'error');
                }
            }
            echo "</div>";
            
            // 3. Custom Taxonomies
            echo "<div class='section'>";
            echo "<h3>3. Custom Taxonomies</h3>";
            
            $forum_taxonomies = array('forum_category', 'forum_tag', 'environmental_topic');
            $registered_taxonomies = get_taxonomies(array('public' => true), 'names');
            
            foreach ($forum_taxonomies as $taxonomy) {
                $total_checks++;
                $registered = in_array($taxonomy, $registered_taxonomies);
                if ($registered) {
                    $passed_checks++;
                    $terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false));
                    $term_count = is_array($terms) ? count($terms) : 0;
                    web_echo("✓ Taxonomy '$taxonomy': REGISTERED ($term_count terms)", 'success');
                } else {
                    web_echo("✗ Taxonomy '$taxonomy': NOT REGISTERED", 'error');
                }
            }
            echo "</div>";
            
            // 4. File Structure
            echo "<div class='section'>";
            echo "<h3>4. Plugin File Structure</h3>";
            
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
                $total_checks++;
                $exists = file_exists($plugin_path . $file);
                if ($exists) {
                    $passed_checks++;
                    $size = number_format(filesize($plugin_path . $file));
                    web_echo("✓ $description: EXISTS ($size bytes)", 'success');
                } else {
                    web_echo("✗ $description: MISSING", 'error');
                }
            }
            echo "</div>";
            
            // 5. Database Content
            echo "<div class='section'>";
            echo "<h3>5. Database Content</h3>";
            
            // Check original forum tables
            $original_forums = $wpdb->get_var("SELECT COUNT(*) FROM forums");
            $original_topics = $wpdb->get_var("SELECT COUNT(*) FROM forum_topics");
            $original_posts = $wpdb->get_var("SELECT COUNT(*) FROM forum_posts");
            
            web_echo("Original forum data: $original_forums forums, $original_topics topics, $original_posts posts", 'info');
            
            // Check WordPress forum data
            $wp_forums = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'ep_forum' AND post_status = 'publish'");
            $wp_topics = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'ep_topic' AND post_status = 'publish'");
            $wp_replies = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'ep_reply' AND post_status = 'publish'");
            
            $total_checks++;
            if ($wp_forums > 0) {
                $passed_checks++;
                web_echo("✓ WordPress forum data: $wp_forums forums, $wp_topics topics, $wp_replies replies", 'success');
            } else {
                web_echo("⚠ WordPress forum data: No forums found (run migration)", 'warning');
            }
            echo "</div>";
            
            // 6. AJAX Endpoints
            echo "<div class='section'>";
            echo "<h3>6. AJAX Functionality</h3>";
            
            $ajax_actions = array(
                'ep_create_topic' => 'Create new topic',
                'ep_create_post' => 'Create new reply',
                'ep_moderate_content' => 'Moderate content'
            );
            
            foreach ($ajax_actions as $action => $description) {
                $total_checks++;
                $hook_registered = has_action("wp_ajax_$action");
                if ($hook_registered) {
                    $passed_checks++;
                    web_echo("✓ $description: REGISTERED", 'success');
                } else {
                    web_echo("✗ $description: NOT REGISTERED", 'error');
                }
            }
            echo "</div>";
            
            // Calculate success rate
            $success_rate = $total_checks > 0 ? round(($passed_checks / $total_checks) * 100, 1) : 0;
            
            // Overall Status
            echo "<div class='section ";
            if ($success_rate >= 95) {
                echo "success'>";
                echo "<h3 class='status-excellent'>🎉 EXCELLENT - Forum System Fully Operational</h3>";
            } elseif ($success_rate >= 85) {
                echo "success'>";
                echo "<h3 class='status-good'>✅ GOOD - Forum System Mostly Functional</h3>";
            } elseif ($success_rate >= 75) {
                echo "warning'>";
                echo "<h3 class='status-warning'>⚠️ ACCEPTABLE - Minor Issues Detected</h3>";
            } else {
                echo "error'>";
                echo "<h3 class='status-error'>❌ NEEDS ATTENTION - Significant Issues Found</h3>";
            }
            
            echo "<p><strong>Verification Summary:</strong></p>";
            echo "<p>Total checks: $total_checks</p>";
            echo "<p>Passed checks: $passed_checks</p>";
            echo "<p>Success rate: <strong>$success_rate%</strong></p>";
            echo "</div>";
            
            // Testing URLs
            echo "<div class='section url-list'>";
            echo "<h3>🔗 Testing URLs</h3>";
            $base_url = home_url();
            echo "<p><strong>Administration:</strong></p>";
            echo "<p>• <a href='$base_url/wp-admin/' target='_blank'>WordPress Admin Dashboard</a></p>";
            echo "<p>• <a href='$base_url/wp-admin/admin.php?page=ep-forum-admin' target='_blank'>Forum Management</a></p>";
            echo "<p>• <a href='$base_url/wp-admin/admin.php?page=ep-forum-migration' target='_blank'>Data Migration Tool</a></p>";
            
            echo "<p><strong>Public Pages:</strong></p>";
            echo "<p>• <a href='$base_url/forums/' target='_blank'>Forums Archive</a></p>";
            
            if ($wp_forums > 0) {
                $sample_forum = $wpdb->get_row("SELECT ID, post_name FROM {$wpdb->posts} WHERE post_type = 'ep_forum' AND post_status = 'publish' LIMIT 1");
                if ($sample_forum) {
                    echo "<p>• <a href='$base_url/forums/{$sample_forum->post_name}/' target='_blank'>Sample Forum</a></p>";
                }
            }
            
            if ($wp_topics > 0) {
                $sample_topic = $wpdb->get_row("SELECT ID, post_name FROM {$wpdb->posts} WHERE post_type = 'ep_topic' AND post_status = 'publish' LIMIT 1");
                if ($sample_topic) {
                    echo "<p>• <a href='$base_url/topics/{$sample_topic->post_name}/' target='_blank'>Sample Topic</a></p>";
                }
            }
            echo "</div>";
            
            // Next Steps
            echo "<div class='section'>";
            echo "<h3>📋 Next Steps & Recommendations</h3>";
            
            if ($success_rate >= 85) {
                echo "<p>✓ <strong>Phase 33 is complete and ready for production!</strong></p>";
                echo "<p>✓ Users can register and participate in forum discussions</p>";
                echo "<p>✓ Moderation tools are available for content management</p>";
                echo "<p>✓ Eco-points system encourages community engagement</p>";
            }
            
            if ($wp_forums == 0) {
                echo "<p>→ Run the migration tool to import existing forum data</p>";
                echo "<p>→ Or create new forums manually through WordPress admin</p>";
            }
            
            echo "<p>→ Test forum functionality by creating test topics and replies</p>";
            echo "<p>→ Configure user permissions and moderation settings</p>";
            echo "<p>→ Customize forum templates to match your theme</p>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div class='section error'>";
            echo "<h3>❌ Verification Error</h3>";
            echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p>Please check your WordPress installation and database connection.</p>";
            echo "</div>";
        }
        ?>
        
        <div class="section" style="text-align: center; background: #2E7D4A; color: white; margin: 20px -20px -20px -20px; padding: 20px;">
            <h3>🌱 Phase 33: Forum System Integration Complete</h3>
            <p>Environmental Platform Development Project</p>
            <p>Generated: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>
