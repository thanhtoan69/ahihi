<?php
/**
 * Comprehensive Plugin Error Scanner
 * Scans all environmental plugins for syntax errors, missing dependencies, and other issues
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Load WordPress
require_once(ABSPATH . 'wp-config.php');
require_once(ABSPATH . 'wp-load.php');

echo "<h1>Environmental Plugins Error Scanner</h1>\n";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.plugin { margin: 15px 0; padding: 15px; border: 1px solid #ccc; border-radius: 5px; }
.clean { background-color: #d4edda; border-color: #c3e6cb; }
.warning { background-color: #fff3cd; border-color: #ffeaa7; }
.error { background-color: #f8d7da; border-color: #f5c6cb; }
.info { background-color: #d1ecf1; border-color: #bee5eb; }
.success { color: #155724; }
.warn { color: #856404; }
.danger { color: #721c24; }
.primary { color: #004085; }
h2 { color: #007cba; margin-top: 30px; }
h3 { color: #333; margin-bottom: 10px; }
pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow: auto; font-size: 12px; }
.issue-list { margin: 10px 0; }
.issue-item { margin: 5px 0; padding: 5px; background: rgba(0,0,0,0.05); border-radius: 3px; }
</style>\n";

echo "<h2>Scanning Environmental Plugins...</h2>\n";

// Get all plugins
$all_plugins = get_plugins();
$active_plugins = get_option('active_plugins', array());

// Filter environmental plugins
$environmental_plugins = array();
foreach ($all_plugins as $plugin_file => $plugin_data) {
    if (strpos($plugin_data['Name'], 'Environmental') !== false || 
        strpos($plugin_file, 'environmental-') !== false ||
        strpos($plugin_data['Description'], 'environmental') !== false) {
        $environmental_plugins[$plugin_file] = $plugin_data;
    }
}

$total_plugins = count($environmental_plugins);
$clean_plugins = 0;
$warning_plugins = 0;
$error_plugins = 0;

echo "<div class='info'>\n";
echo "<h3>Scan Overview</h3>\n";
echo "<strong>Total Environmental Plugins Found:</strong> {$total_plugins}<br>\n";
echo "<strong>Scan Started:</strong> " . date('Y-m-d H:i:s') . "<br>\n";
echo "</div>\n";

foreach ($environmental_plugins as $plugin_file => $plugin_data) {
    $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
    $plugin_dir = WP_PLUGIN_DIR . '/' . dirname($plugin_file);
    $is_active = in_array($plugin_file, $active_plugins);
    
    $issues = array();
    $warnings = array();
    $info = array();
    
    // Basic file checks
    if (!file_exists($plugin_path)) {
        $issues[] = "Plugin file not found: {$plugin_path}";
    } elseif (!is_readable($plugin_path)) {
        $issues[] = "Plugin file not readable: {$plugin_path}";
    } else {
        $info[] = "Plugin file exists and is readable";
        
        // Check syntax
        $syntax_check = shell_exec("\"C:\\xampp\\php\\php.exe\" -l \"{$plugin_path}\" 2>&1");
        if (strpos($syntax_check, 'No syntax errors') === false) {
            $issues[] = "Syntax errors detected";
            $issues[] = "Syntax check output: " . trim($syntax_check);
        } else {
            $info[] = "No syntax errors in main plugin file";
        }
        
        // Read plugin content for analysis
        $plugin_content = file_get_contents($plugin_path);
        
        // Check for common issues
        if (strpos($plugin_content, '<?php') !== 0) {
            $issues[] = "Plugin doesn't start with <?php tag";
        }
        
        // Check for WordPress security
        if (strpos($plugin_content, 'ABSPATH') === false) {
            $warnings[] = "Missing ABSPATH security check";
        }
        
        // Check for class definitions
        if (preg_match_all('/class\s+([A-Za-z_][A-Za-z0-9_]*)/i', $plugin_content, $matches)) {
            $classes = $matches[1];
            $info[] = "Found " . count($classes) . " class definitions: " . implode(', ', $classes);
            
            // Check for class existence if plugin is active
            if ($is_active) {
                foreach ($classes as $class_name) {
                    if (!class_exists($class_name)) {
                        $issues[] = "Class '{$class_name}' defined but not loaded";
                    }
                }
            }
        }
        
        // Check for function definitions
        if (preg_match_all('/function\s+([A-Za-z_][A-Za-z0-9_]*)/i', $plugin_content, $matches)) {
            $functions = $matches[1];
            if (count($functions) > 0) {
                $info[] = "Found " . count($functions) . " function definitions";
            }
        }
        
        // Check includes directory
        $includes_dir = $plugin_dir . '/includes';
        if (is_dir($includes_dir)) {
            $include_files = glob($includes_dir . '/*.php');
            $info[] = "Includes directory found with " . count($include_files) . " PHP files";
            
            // Check each include file for syntax errors
            $include_errors = array();
            foreach ($include_files as $include_file) {
                $syntax_check = shell_exec("\"C:\\xampp\\php\\php.exe\" -l \"{$include_file}\" 2>&1");
                if (strpos($syntax_check, 'No syntax errors') === false) {
                    $include_errors[] = basename($include_file) . ": " . trim($syntax_check);
                }
            }
            
            if (!empty($include_errors)) {
                $issues[] = "Syntax errors in include files:";
                $issues = array_merge($issues, $include_errors);
            } else {
                $info[] = "All include files have valid syntax";
            }
        }
        
        // Check for database dependencies
        if (strpos($plugin_content, '$wpdb') !== false || 
            strpos($plugin_content, 'get_option') !== false ||
            strpos($plugin_content, 'update_option') !== false) {
            $info[] = "Uses WordPress database functions";
        }
        
        // Check for admin dependencies
        if (strpos($plugin_content, 'admin_menu') !== false ||
            strpos($plugin_content, 'current_user_can') !== false) {
            $info[] = "Has admin interface functionality";
        }
        
        // Check for AJAX
        if (strpos($plugin_content, 'wp_ajax') !== false) {
            $info[] = "Implements AJAX functionality";
        }
        
        // Check for REST API
        if (strpos($plugin_content, 'rest_api') !== false ||
            strpos($plugin_content, 'register_rest_route') !== false) {
            $info[] = "Implements REST API endpoints";
        }
        
        // Check for activation/deactivation hooks
        if (strpos($plugin_content, 'register_activation_hook') !== false) {
            $info[] = "Has activation hook";
        }
        if (strpos($plugin_content, 'register_deactivation_hook') !== false) {
            $info[] = "Has deactivation hook";
        }
        
        // Check for uninstall
        $uninstall_file = $plugin_dir . '/uninstall.php';
        if (file_exists($uninstall_file)) {
            $info[] = "Has uninstall script";
        }
        
        // Specific checks for environmental platform petitions
        if (strpos($plugin_file, 'environmental-platform-petitions') !== false) {
            // Check for class aliases
            $alias_files = array(
                'class-share-manager.php',
                'class-admin-dashboard.php', 
                'class-analytics.php',
                'class-email-notifications.php',
                'class-rest-api.php'
            );
            
            $missing_aliases = array();
            foreach ($alias_files as $alias_file) {
                $alias_path = $includes_dir . '/' . $alias_file;
                if (file_exists($alias_path)) {
                    $alias_content = file_get_contents($alias_path);
                    if (strpos($alias_content, 'class_alias') === false) {
                        $missing_aliases[] = $alias_file;
                    }
                } else {
                    $missing_aliases[] = $alias_file . " (file not found)";
                }
            }
            
            if (!empty($missing_aliases)) {
                $warnings[] = "Missing class aliases in: " . implode(', ', $missing_aliases);
            } else {
                $info[] = "All required class aliases are present";
            }
        }
    }
    
    // Determine status
    $status_class = 'clean';
    if (!empty($issues)) {
        $status_class = 'error';
        $error_plugins++;
    } elseif (!empty($warnings)) {
        $status_class = 'warning';
        $warning_plugins++;
    } else {
        $clean_plugins++;
    }
    
    // Output plugin results
    echo "<div class='plugin {$status_class}'>\n";
    echo "<h3>{$plugin_data['Name']} v{$plugin_data['Version']}</h3>\n";
    echo "<strong>File:</strong> {$plugin_file}<br>\n";
    echo "<strong>Status:</strong> " . ($is_active ? "<span class='success'>Active</span>" : "<span class='warn'>Inactive</span>") . "<br>\n";
    
    if (!empty($issues)) {
        echo "<h4 class='danger'>❌ Issues Found (" . count($issues) . ")</h4>\n";
        echo "<div class='issue-list'>\n";
        foreach ($issues as $issue) {
            echo "<div class='issue-item danger'>• {$issue}</div>\n";
        }
        echo "</div>\n";
    }
    
    if (!empty($warnings)) {
        echo "<h4 class='warn'>⚠️ Warnings (" . count($warnings) . ")</h4>\n";
        echo "<div class='issue-list'>\n";
        foreach ($warnings as $warning) {
            echo "<div class='issue-item warn'>• {$warning}</div>\n";
        }
        echo "</div>\n";
    }
    
    if (!empty($info)) {
        echo "<h4 class='primary'>ℹ️ Information (" . count($info) . ")</h4>\n";
        echo "<div class='issue-list'>\n";
        foreach ($info as $info_item) {
            echo "<div class='issue-item primary'>• {$info_item}</div>\n";
        }
        echo "</div>\n";
    }
    
    echo "</div>\n";
}

// Final summary
echo "<h2>Scan Results Summary</h2>\n";
$overall_status = 'clean';
if ($error_plugins > 0) {
    $overall_status = 'error';
} elseif ($warning_plugins > 0) {
    $overall_status = 'warning';
}

echo "<div class='plugin {$overall_status}'>\n";
echo "<h3>Overall Status</h3>\n";
echo "<strong>Total Plugins Scanned:</strong> {$total_plugins}<br>\n";
echo "<strong>Clean Plugins:</strong> <span class='success'>{$clean_plugins}</span><br>\n";
echo "<strong>Plugins with Warnings:</strong> <span class='warn'>{$warning_plugins}</span><br>\n";
echo "<strong>Plugins with Errors:</strong> <span class='danger'>{$error_plugins}</span><br>\n";

$success_rate = round(($clean_plugins / $total_plugins) * 100, 1);
echo "<strong>Success Rate:</strong> {$success_rate}%<br>\n";

if ($error_plugins === 0 && $warning_plugins === 0) {
    echo "<h4 class='success'>🎉 All plugins are clean!</h4>\n";
    echo "<p>No issues found in any environmental plugins.</p>\n";
} elseif ($error_plugins === 0) {
    echo "<h4 class='warn'>⚠️ Minor issues found</h4>\n";
    echo "<p>Some warnings were found but no critical errors.</p>\n";
} else {
    echo "<h4 class='danger'>❌ Critical issues found</h4>\n";
    echo "<p>Please review and fix the errors listed above.</p>\n";
}

echo "</div>\n";

echo "<hr>\n";
echo "<p><strong>Scan completed at:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
echo "<p><strong>Next steps:</strong></p>\n";
echo "<ul>\n";
echo "<li><a href='http://localhost/moitruong/test-all-class-aliases.php' target='_blank'>Run Class Alias Tests</a></li>\n";
echo "<li><a href='http://localhost/moitruong/wp-admin/' target='_blank'>Test WordPress Admin</a></li>\n";
echo "<li><a href='http://localhost/moitruong/check-all-plugins.php' target='_blank'>Check Plugin Status</a></li>\n";
echo "</ul>\n";
?>
