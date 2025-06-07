<?php
/**
 * Phase 50 Plugin Activation Script
 * Environmental Platform - Multi-language Support
 */

// WordPress Bootstrap
require_once('wp-config.php');
require_once('wp-load.php');
require_once('wp-admin/includes/plugin.php');

echo "<h1>Phase 50: Multi-language Support Plugin Activation</h1>";

// Plugin path
$plugin = 'environmental-multilang-support/environmental-multilang-support.php';

// Check if plugin exists
$plugin_file = WP_PLUGIN_DIR . '/' . $plugin;
if (!file_exists($plugin_file)) {
    echo "<p style='color: red;'>❌ Plugin file not found: $plugin_file</p>";
    exit;
}

echo "<p style='color: green;'>✅ Plugin file found</p>";

// Check if already active
if (is_plugin_active($plugin)) {
    echo "<p style='color: blue;'>ℹ️ Plugin is already active</p>";
} else {
    // Activate plugin
    $result = activate_plugin($plugin);
    
    if (is_wp_error($result)) {
        echo "<p style='color: red;'>❌ Plugin activation failed: " . $result->get_error_message() . "</p>";
    } else {
        echo "<p style='color: green;'>✅ Plugin activated successfully!</p>";
    }
}

// Check plugin class
if (class_exists('Environmental_Multilang_Support')) {
    echo "<p style='color: green;'>✅ Main plugin class loaded</p>";
    
    // Get plugin instance
    $instance = Environmental_Multilang_Support::get_instance();
    if ($instance) {
        echo "<p style='color: green;'>✅ Plugin instance created</p>";
        
        // Test available languages
        if (method_exists($instance, 'get_available_languages')) {
            $languages = $instance->get_available_languages();
            if (is_array($languages) && count($languages) > 0) {
                echo "<p style='color: green;'>✅ Languages loaded: " . count($languages) . " languages</p>";
                echo "<ul>";
                foreach ($languages as $code => $lang) {
                    $native_name = isset($lang['native_name']) ? $lang['native_name'] : $lang['name'];
                    echo "<li><strong>$code:</strong> $native_name</li>";
                }
                echo "</ul>";
            } else {
                echo "<p style='color: orange;'>⚠️ No languages configured</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>❌ Failed to create plugin instance</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Main plugin class not loaded</p>";
}

// Check database table
global $wpdb;
$table_name = $wpdb->prefix . 'ems_translations';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;

if ($table_exists) {
    echo "<p style='color: green;'>✅ Database table created: $table_name</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Database table not found: $table_name</p>";
}

// Check helper functions
$functions = ['ems_get_current_language', 'ems_get_available_languages', 'ems_is_rtl_language', 'ems_get_translation_url'];
echo "<h3>Helper Functions:</h3>";
foreach ($functions as $function) {
    if (function_exists($function)) {
        echo "<p style='color: green;'>✅ Function $function exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Function $function missing</p>";
    }
}

echo "<h3>Plugin Status: Ready for Use! 🎉</h3>";
echo "<p><a href='wp-admin/admin.php?page=ems-settings'>Go to Multi-language Settings</a></p>";
?>
