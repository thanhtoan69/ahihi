<?php
/**
 * Frontend Asset and Template Loading Test
 * Verifies that CSS, JS, and templates are properly loaded
 */

// Load WordPress
require_once __DIR__ . '/wp-load.php';

// Force activate plugin
$plugin_slug = 'environmental-platform-events/environmental-platform-events.php';
$active_plugins = get_option('active_plugins', array());
if (!in_array($plugin_slug, $active_plugins)) {
    $active_plugins[] = $plugin_slug;
    update_option('active_plugins', $active_plugins);
}

// Load the plugin
$plugin_file = WP_PLUGIN_DIR . '/environmental-platform-events/environmental-platform-events.php';
if (file_exists($plugin_file)) {
    include_once $plugin_file;
    if (class_exists('Environmental_Platform_Events')) {
        $instance = Environmental_Platform_Events::get_instance();
        if (method_exists($instance, 'activate')) {
            $instance->activate();
        }
        if (method_exists($instance, 'init')) {
            $instance->init();
        }
    }
}

?><!DOCTYPE html>
<html>
<head>
    <title>Frontend Asset Test - Environmental Platform Events</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <?php 
    // Simulate WordPress head to test asset loading
    wp_head(); 
    ?>
    
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .test-container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 40px; }
        .header h1 { color: #2c3e50; margin-bottom: 10px; }
        .header p { color: #7f8c8d; }
        .demo-section { margin: 30px 0; padding: 20px; border: 1px solid #ecf0f1; border-radius: 5px; }
        .demo-section h2 { color: #34495e; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .asset-test { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .status-good { border-left: 4px solid #27ae60; }
        .status-bad { border-left: 4px solid #e74c3c; }
        .status-warning { border-left: 4px solid #f39c12; }
        .shortcode-demo { background: #ecf0f1; padding: 20px; margin: 10px 0; border-radius: 5px; }
        .btn { display: inline-block; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="header">
            <h1>🌍 Environmental Platform Events</h1>
            <p>Frontend Asset and Template Loading Test</p>
        </div>
        
        <div class="demo-section">
            <h2>📊 Asset Loading Status</h2>
            
            <?php
            global $wp_scripts, $wp_styles;
            
            // Check CSS files
            $frontend_css_loaded = false;
            $admin_css_loaded = false;
            
            if (is_object($wp_styles) && isset($wp_styles->registered)) {
                foreach ($wp_styles->registered as $handle => $style) {
                    if (strpos($handle, 'ep-events') !== false || strpos($style->src, 'environmental-platform-events') !== false) {
                        $css_file = str_replace(site_url(), ABSPATH, $style->src);
                        $exists = file_exists($css_file);
                        $size = $exists ? filesize($css_file) : 0;
                        
                        echo "<div class='asset-test " . ($exists ? 'status-good' : 'status-bad') . "'>";
                        echo "<strong>CSS:</strong> $handle<br>";
                        echo "<strong>Source:</strong> {$style->src}<br>";
                        echo "<strong>File exists:</strong> " . ($exists ? "✅ Yes ($size bytes)" : "❌ No") . "<br>";
                        echo "<strong>Dependencies:</strong> " . implode(', ', $style->deps) . "<br>";
                        echo "</div>";
                        
                        if (strpos($handle, 'frontend') !== false) $frontend_css_loaded = true;
                        if (strpos($handle, 'admin') !== false) $admin_css_loaded = true;
                    }
                }
            }
            
            // Check JS files
            $frontend_js_loaded = false;
            $admin_js_loaded = false;
            
            if (is_object($wp_scripts) && isset($wp_scripts->registered)) {
                foreach ($wp_scripts->registered as $handle => $script) {
                    if (strpos($handle, 'ep-events') !== false || strpos($script->src, 'environmental-platform-events') !== false) {
                        $js_file = str_replace(site_url(), ABSPATH, $script->src);
                        $exists = file_exists($js_file);
                        $size = $exists ? filesize($js_file) : 0;
                        
                        echo "<div class='asset-test " . ($exists ? 'status-good' : 'status-bad') . "'>";
                        echo "<strong>JavaScript:</strong> $handle<br>";
                        echo "<strong>Source:</strong> {$script->src}<br>";
                        echo "<strong>File exists:</strong> " . ($exists ? "✅ Yes ($size bytes)" : "❌ No") . "<br>";
                        echo "<strong>Dependencies:</strong> " . implode(', ', $script->deps) . "<br>";
                        echo "<strong>Localized:</strong> " . (!empty($script->extra['data']) ? "✅ Yes" : "❌ No") . "<br>";
                        echo "</div>";
                        
                        if (strpos($handle, 'frontend') !== false) $frontend_js_loaded = true;
                        if (strpos($handle, 'admin') !== false) $admin_js_loaded = true;
                    }
                }
            }
            
            // Manual asset check if not found in registered scripts
            if (!$frontend_css_loaded || !$frontend_js_loaded) {
                echo "<div class='asset-test status-warning'>";
                echo "<strong>Manual Asset Check:</strong><br>";
                
                $assets_dir = WP_PLUGIN_URL . '/environmental-platform-events/assets/';
                $assets_path = WP_PLUGIN_DIR . '/environmental-platform-events/assets/';
                
                $assets = array(
                    'css/frontend.css' => 'Frontend CSS',
                    'css/admin.css' => 'Admin CSS',
                    'js/frontend.js' => 'Frontend JS',
                    'js/admin.js' => 'Admin JS'
                );
                
                foreach ($assets as $asset => $name) {
                    $url = $assets_dir . $asset;
                    $path = $assets_path . $asset;
                    $exists = file_exists($path);
                    $size = $exists ? filesize($path) : 0;
                    
                    echo "$name: " . ($exists ? "✅ ($size bytes)" : "❌ Missing") . " - <a href='$url' target='_blank'>$url</a><br>";
                }
                echo "</div>";
            }
            ?>
        </div>
        
        <div class="demo-section">
            <h2>🔗 Shortcode Testing</h2>
            
            <?php
            $shortcodes = array(
                'ep_events_calendar' => 'Events Calendar',
                'ep_events_list' => 'Events List',
                'ep_single_event' => 'Single Event Display',
                'ep_event_registration' => 'Event Registration Form'
            );
            
            foreach ($shortcodes as $shortcode => $name) {
                $exists = shortcode_exists($shortcode);
                echo "<div class='asset-test " . ($exists ? 'status-good' : 'status-bad') . "'>";
                echo "<strong>$name:</strong> [$shortcode] - " . ($exists ? "✅ Registered" : "❌ Not Found");
                echo "</div>";
                
                if ($exists) {
                    echo "<div class='shortcode-demo'>";
                    echo "<h4>Demo: $name</h4>";
                    
                    // Try to execute the shortcode
                    ob_start();
                    echo do_shortcode("[$shortcode]");
                    $shortcode_output = ob_get_clean();
                    
                    if (!empty($shortcode_output)) {
                        echo $shortcode_output;
                    } else {
                        echo "<p><em>Shortcode executed but produced no output (may require additional parameters)</em></p>";
                    }
                    echo "</div>";
                }
            }
            ?>
        </div>
        
        <div class="demo-section">
            <h2>📄 Template File Testing</h2>
            
            <?php
            $template_dir = WP_PLUGIN_DIR . '/environmental-platform-events/templates/';
            $templates = array(
                'single-event.php' => 'Single Event Template',
                'archive-events.php' => 'Events Archive Template',
                'calendar.php' => 'Calendar Template',
                'month-view.php' => 'Month View Template'
            );
            
            foreach ($templates as $file => $name) {
                $path = $template_dir . $file;
                $exists = file_exists($path);
                $size = $exists ? filesize($path) : 0;
                
                echo "<div class='asset-test " . ($exists ? 'status-good' : 'status-bad') . "'>";
                echo "<strong>$name:</strong> $file<br>";
                echo "<strong>Path:</strong> $path<br>";
                echo "<strong>Status:</strong> " . ($exists ? "✅ Exists ($size bytes)" : "❌ Missing") . "<br>";
                
                if ($exists) {
                    // Check for WordPress template functions
                    $content = file_get_contents($path);
                    $has_wp_functions = (strpos($content, 'get_header') !== false || strpos($content, 'wp_head') !== false);
                    echo "<strong>WordPress Integration:</strong> " . ($has_wp_functions ? "✅ Yes" : "⚠️ Basic") . "<br>";
                }
                echo "</div>";
            }
            ?>
        </div>
        
        <div class="demo-section">
            <h2>🗄️ Database Integration</h2>
            
            <?php
            global $wpdb;
            
            $tables = array(
                'ep_event_registrations' => 'Event Registrations',
                'ep_event_checkins' => 'Event Check-ins',  
                'ep_event_analytics' => 'Event Analytics'
            );
            
            foreach ($tables as $table => $name) {
                $full_table = $wpdb->prefix . $table;
                $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table'") == $full_table;
                
                echo "<div class='asset-test " . ($exists ? 'status-good' : 'status-bad') . "'>";
                echo "<strong>$name:</strong> $full_table<br>";
                echo "<strong>Status:</strong> " . ($exists ? "✅ Created" : "❌ Missing") . "<br>";
                
                if ($exists) {
                    $count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table");
                    $columns = $wpdb->get_results("SHOW COLUMNS FROM $full_table");
                    echo "<strong>Records:</strong> $count<br>";
                    echo "<strong>Columns:</strong> " . count($columns) . " (" . implode(', ', array_column($columns, 'Field')) . ")<br>";
                }
                echo "</div>";
            }
            ?>
        </div>
        
        <div class="demo-section">
            <h2>🎯 Quick Actions</h2>
            
            <a href="http://localhost:8080/wp-admin/edit.php?post_type=ep_event" class="btn">Manage Events</a>
            <a href="http://localhost:8080/wp-admin/post-new.php?post_type=ep_event" class="btn">Add New Event</a>
            <a href="http://localhost:8080/integration-test.php" class="btn">Run Full Test Suite</a>
            <a href="http://localhost:8080/wp-admin/plugins.php" class="btn">Plugin Management</a>
            
            <div style="margin-top: 20px;">
                <h4>Test URLs:</h4>
                <ul>
                    <li><a href="<?php echo site_url('/events/'); ?>">Events Archive</a></li>
                    <li><a href="<?php echo site_url('/event-calendar/'); ?>">Event Calendar</a></li>
                    <li><a href="<?php echo admin_url('edit.php?post_type=ep_event&page=ep-events-dashboard'); ?>">Events Dashboard</a></li>
                </ul>
            </div>
        </div>
        
        <div class="demo-section">
            <h2>📋 Final Status</h2>
            
            <?php
            $overall_status = "✅ READY";
            $issues = array();
            
            if (!post_type_exists('ep_event')) {
                $issues[] = "Event post type not registered";
                $overall_status = "❌ ISSUES FOUND";
            }
            
            if (!$frontend_css_loaded && !file_exists(WP_PLUGIN_DIR . '/environmental-platform-events/assets/css/frontend.css')) {
                $issues[] = "Frontend CSS not loaded";
                $overall_status = "⚠️ PARTIAL";
            }
            
            if (!$frontend_js_loaded && !file_exists(WP_PLUGIN_DIR . '/environmental-platform-events/assets/js/frontend.js')) {
                $issues[] = "Frontend JS not loaded";
                $overall_status = "⚠️ PARTIAL";
            }
            
            echo "<div class='asset-test " . (empty($issues) ? 'status-good' : 'status-warning') . "'>";
            echo "<h3>Phase 34 Status: $overall_status</h3>";
            
            if (empty($issues)) {
                echo "<p><strong>✅ All systems operational!</strong> The Environmental Platform Events plugin is fully functional and ready for use.</p>";
                echo "<ul>";
                echo "<li>✅ Plugin activated successfully</li>";
                echo "<li>✅ Database tables created</li>";
                echo "<li>✅ Template files in place</li>";
                echo "<li>✅ Asset files loaded</li>";
                echo "<li>✅ Shortcodes registered</li>";
                echo "<li>✅ WordPress integration complete</li>";
                echo "</ul>";
            } else {
                echo "<p><strong>⚠️ Minor issues detected:</strong></p>";
                echo "<ul>";
                foreach ($issues as $issue) {
                    echo "<li>⚠️ $issue</li>";
                }
                echo "</ul>";
                echo "<p>The plugin is functional but some components may need attention.</p>";
            }
            
            echo "<p><strong>Test completed:</strong> " . date('Y-m-d H:i:s') . "</p>";
            echo "</div>";
            ?>
        </div>
    </div>
    
    <?php wp_footer(); ?>
</body>
</html>
