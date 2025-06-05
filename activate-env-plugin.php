<?php
/**
 * Activate Environmental Data Dashboard Plugin
 * Phase 40 Plugin Activation
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h1>Environmental Data Dashboard Plugin Activation</h1>";

$plugin_path = 'environmental-data-dashboard/environmental-data-dashboard.php';

// Check if plugin exists
$plugin_file = WP_PLUGIN_DIR . '/' . $plugin_path;
if (!file_exists($plugin_file)) {
    echo "<p style='color: red;'>❌ Plugin file not found: $plugin_file</p>";
    exit;
}

echo "<p>✅ Plugin file exists: $plugin_file</p>";

// Check if plugin is already active
if (is_plugin_active($plugin_path)) {
    echo "<p style='color: green;'>✅ Plugin is already active!</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Plugin is not active. Activating now...</p>";
    
    // Activate the plugin
    $result = activate_plugin($plugin_path);
    
    if (is_wp_error($result)) {
        echo "<p style='color: red;'>❌ Failed to activate plugin: " . $result->get_error_message() . "</p>";
    } else {
        echo "<p style='color: green;'>✅ Plugin activated successfully!</p>";
        
        // Trigger the activation hook manually to create database tables
        if (function_exists('activate_environmental_data_dashboard')) {
            activate_environmental_data_dashboard();
            echo "<p>✅ Database tables created via activation hook</p>";
        }
    }
}

// Check plugin status after activation
if (is_plugin_active($plugin_path)) {
    echo "<h2>Plugin Status: Active ✅</h2>";
    
    // Check if required classes are available
    $classes = array(
        'Environmental_Quiz_Manager',
        'Environmental_Challenge_System',
        'Environmental_Sample_Data_Inserter'
    );
    
    echo "<h3>Class Availability:</h3>";
    foreach ($classes as $class) {
        if (class_exists($class)) {
            echo "<p>✅ $class is available</p>";
        } else {
            echo "<p>❌ $class is not available</p>";
        }
    }
    
    // Test database tables
    global $wpdb;
    $tables = array(
        'quiz_categories',
        'quiz_questions',
        'quiz_sessions',
        'quiz_responses',
        'env_challenges',
        'env_challenge_participants'
    );
    
    echo "<h3>Database Tables:</h3>";
    foreach ($tables as $table) {
        $full_table = $wpdb->prefix . $table;
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table'");
        if ($exists) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table");
            echo "<p>✅ $full_table exists (rows: $count)</p>";
        } else {
            echo "<p>❌ $full_table does not exist</p>";
        }
    }
    
} else {
    echo "<h2>Plugin Status: Inactive ❌</h2>";
}

echo "<hr>";
echo "<p><a href='test-phase40-database.php'>Test Database Status</a> | ";
echo "<a href='test-quiz-interface.php'>Test Quiz Interface</a> | ";
echo "<a href='" . admin_url('plugins.php') . "'>WordPress Admin Plugins</a></p>";

?>
