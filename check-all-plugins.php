<?php
/**
 * Check All Plugins Status
 * Comprehensive plugin error detection and status check
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Load WordPress
require_once(ABSPATH . 'wp-config.php');
require_once(ABSPATH . 'wp-load.php');
require_once(ABSPATH . 'wp-admin/includes/plugin.php');

echo "<h1>WordPress Plugins Status Check</h1>\n";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.plugin { margin: 10px 0; padding: 10px; border: 1px solid #ccc; border-radius: 5px; }
.active { background-color: #d4edda; border-color: #c3e6cb; }
.inactive { background-color: #f8d7da; border-color: #f5c6cb; }
.error { background-color: #fff3cd; border-color: #ffeaa7; }
.success { color: #155724; }
.warning { color: #856404; }
.danger { color: #721c24; }
h2 { color: #007cba; }
h3 { color: #333; }
pre { background: #f4f4f4; padding: 10px; border-radius: 3px; overflow: auto; }
</style>\n";

// Check if WordPress is loaded
if (!function_exists('get_plugins')) {
    echo "<div class='error'><h2>Error: WordPress not properly loaded</h2></div>";
    exit;
}

echo "<h2>WordPress Environment Status</h2>\n";
echo "<div class='plugin active'>\n";
echo "<strong>WordPress Version:</strong> " . get_bloginfo('version') . "<br>\n";
echo "<strong>PHP Version:</strong> " . PHP_VERSION . "<br>\n";
echo "<strong>Current User:</strong> " . (is_user_logged_in() ? wp_get_current_user()->user_login : 'Not logged in') . "<br>\n";
echo "<strong>Admin URL:</strong> <a href='" . admin_url() . "' target='_blank'>" . admin_url() . "</a><br>\n";
echo "</div>\n";

// Get all plugins
$all_plugins = get_plugins();
$active_plugins = get_option('active_plugins', array());

echo "<h2>All Plugins Status (" . count($all_plugins) . " total)</h2>\n";

$error_count = 0;
$active_count = 0;
$inactive_count = 0;

foreach ($all_plugins as $plugin_file => $plugin_data) {
    $is_active = in_array($plugin_file, $active_plugins);
    $plugin_dir = dirname($plugin_file);
    $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
    
    $status_class = $is_active ? 'active' : 'inactive';
    if ($is_active) $active_count++;
    else $inactive_count++;
    
    echo "<div class='plugin {$status_class}'>\n";
    echo "<h3>{$plugin_data['Name']} v{$plugin_data['Version']}</h3>\n";
    echo "<strong>File:</strong> {$plugin_file}<br>\n";
    echo "<strong>Status:</strong> " . ($is_active ? "<span class='success'>Active</span>" : "<span class='danger'>Inactive</span>") . "<br>\n";
    echo "<strong>Description:</strong> " . $plugin_data['Description'] . "<br>\n";
    
    // Check if plugin file exists and is readable
    if (!file_exists($plugin_path)) {
        echo "<span class='danger'>❌ Plugin file not found: {$plugin_path}</span><br>\n";
        $error_count++;
    } elseif (!is_readable($plugin_path)) {
        echo "<span class='danger'>❌ Plugin file not readable: {$plugin_path}</span><br>\n";
        $error_count++;
    } else {
        echo "<span class='success'>✅ Plugin file exists and readable</span><br>\n";
        
        // Check for syntax errors
        $syntax_check = shell_exec("php -l \"{$plugin_path}\" 2>&1");
        if (strpos($syntax_check, 'No syntax errors') !== false) {
            echo "<span class='success'>✅ No syntax errors</span><br>\n";
        } else {
            echo "<span class='danger'>❌ Syntax errors found:</span><br>\n";
            echo "<pre>" . htmlspecialchars($syntax_check) . "</pre>\n";
            $error_count++;
        }
    }
    
    // Check if plugin has specific errors
    if ($is_active) {
        // Try to detect common errors
        $plugin_content = file_get_contents($plugin_path);
        
        // Check for class conflicts
        if (strpos($plugin_content, 'class_alias') !== false) {
            echo "<span class='warning'>⚠️ Contains class aliases (compatibility fix)</span><br>\n";
        }
        
        // Check for undefined class references
        if (preg_match('/new\s+([A-Z][A-Za-z_0-9]+)/', $plugin_content, $matches)) {
            $class_name = $matches[1];
            if (!class_exists($class_name)) {
                echo "<span class='danger'>❌ Missing class: {$class_name}</span><br>\n";
                $error_count++;
            }
        }
    }
    
    echo "</div>\n";
}

echo "<h2>Summary</h2>\n";
echo "<div class='plugin'>\n";
echo "<strong>Total Plugins:</strong> " . count($all_plugins) . "<br>\n";
echo "<strong>Active Plugins:</strong> <span class='success'>{$active_count}</span><br>\n";
echo "<strong>Inactive Plugins:</strong> <span class='warning'>{$inactive_count}</span><br>\n";
echo "<strong>Errors Found:</strong> <span class='" . ($error_count > 0 ? 'danger' : 'success') . "'>{$error_count}</span><br>\n";
echo "</div>\n";

// Check for plugin-specific issues
echo "<h2>Environmental Platform Plugins Check</h2>\n";

$environmental_plugins = array();
foreach ($all_plugins as $plugin_file => $plugin_data) {
    if (strpos($plugin_data['Name'], 'Environmental') !== false || 
        strpos($plugin_file, 'environmental-') !== false) {
        $environmental_plugins[$plugin_file] = $plugin_data;
    }
}

if (!empty($environmental_plugins)) {
    echo "<div class='plugin'>\n";
    echo "<h3>Environmental Platform Plugins (" . count($environmental_plugins) . " found)</h3>\n";
    
    foreach ($environmental_plugins as $plugin_file => $plugin_data) {
        $is_active = in_array($plugin_file, $active_plugins);
        $status = $is_active ? "<span class='success'>Active</span>" : "<span class='danger'>Inactive</span>";
        echo "• {$plugin_data['Name']} - {$status}<br>\n";
        
        // Check for class alias issues specifically for petitions plugin
        if (strpos($plugin_file, 'environmental-platform-petitions') !== false && $is_active) {
            echo "&nbsp;&nbsp;<strong>Checking EPP class aliases...</strong><br>\n";
            
            $classes_to_check = array(
                'EPP_Share_Manager',
                'EPP_Admin_Dashboard',
                'EPP_Analytics',
                'EPP_Email_Notifications',
                'EPP_REST_API'
            );
            
            foreach ($classes_to_check as $class_name) {
                if (class_exists($class_name)) {
                    echo "&nbsp;&nbsp;✅ {$class_name} - Available<br>\n";
                } else {
                    echo "&nbsp;&nbsp;❌ {$class_name} - Missing<br>\n";
                    $error_count++;
                }
            }
        }
    }
    echo "</div>\n";
} else {
    echo "<div class='plugin error'>\n";
    echo "<h3>No Environmental Platform plugins found!</h3>\n";
    echo "</div>\n";
}

// Check for recent WordPress errors
echo "<h2>Recent WordPress Errors</h2>\n";
$debug_log = WP_CONTENT_DIR . '/debug.log';
if (file_exists($debug_log) && is_readable($debug_log)) {
    $log_content = file_get_contents($debug_log);
    $recent_errors = array();
    
    // Get last 20 lines of log
    $log_lines = explode("\n", $log_content);
    $recent_lines = array_slice($log_lines, -20);
    
    echo "<div class='plugin'>\n";
    echo "<h3>Last 20 log entries:</h3>\n";
    echo "<pre>" . htmlspecialchars(implode("\n", $recent_lines)) . "</pre>\n";
    echo "</div>\n";
} else {
    echo "<div class='plugin'>\n";
    echo "<h3>No debug log found or not readable</h3>\n";
    echo "</div>\n";
}

// Final recommendations
echo "<h2>Recommendations</h2>\n";
echo "<div class='plugin'>\n";

if ($error_count > 0) {
    echo "<h3 class='danger'>❌ Issues Found ({$error_count} errors)</h3>\n";
    echo "<p>The following actions are recommended:</p>\n";
    echo "<ul>\n";
    echo "<li>Fix syntax errors in plugin files</li>\n";
    echo "<li>Ensure all required classes are properly defined</li>\n";
    echo "<li>Add missing class aliases for EPP plugins</li>\n";
    echo "<li>Check WordPress error logs for detailed information</li>\n";
    echo "</ul>\n";
} else {
    echo "<h3 class='success'>✅ No Major Issues Found</h3>\n";
    echo "<p>All plugins appear to be functioning correctly.</p>\n";
}

echo "</div>\n";

echo "<hr>\n";
echo "<p><strong>Check completed at:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
?>
