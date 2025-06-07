<?php
/**
 * Direct WordPress Plugin Activation for Environmental Content Recommendation
 */

// Load WordPress
require_once('./wp-config.php');
require_once('./wp-admin/includes/plugin.php');

$plugin_slug = 'environmental-content-recommendation/environmental-content-recommendation.php';

echo "Environmental Content Recommendation Plugin Activation\n";
echo "====================================================\n\n";

// Check if plugin file exists
$plugin_file = WP_PLUGIN_DIR . '/' . $plugin_slug;
echo "1. Checking plugin file: $plugin_file\n";

if (file_exists($plugin_file)) {
    echo "   ✓ Plugin file exists\n";
    
    // Check if already active
    if (is_plugin_active($plugin_slug)) {
        echo "   ✓ Plugin is already active\n";
    } else {
        echo "   → Activating plugin...\n";
        
        // Attempt activation
        $result = activate_plugin($plugin_slug);
        
        if (is_wp_error($result)) {
            echo "   ✗ Activation failed: " . $result->get_error_message() . "\n";
        } else {
            echo "   ✓ Plugin activated successfully\n";
        }
    }
    
    // Verify current status
    if (is_plugin_active($plugin_slug)) {
        echo "   ✓ Plugin is currently active\n";
        
        // Check if main class is available
        if (class_exists('Environmental_Content_Recommendation')) {
            echo "   ✓ Main plugin class is loaded\n";
            
            // Try to get instance
            if (method_exists('Environmental_Content_Recommendation', 'get_instance')) {
                $instance = Environmental_Content_Recommendation::get_instance();
                if ($instance) {
                    echo "   ✓ Plugin instance created successfully\n";
                } else {
                    echo "   ⚠ Could not create plugin instance\n";
                }
            }
        } else {
            echo "   ⚠ Main plugin class not found (may load on init)\n";
        }
        
        // Check database tables
        global $wpdb;
        $tables = [
            $wpdb->prefix . 'ecr_user_behavior',
            $wpdb->prefix . 'ecr_content_features', 
            $wpdb->prefix . 'ecr_user_recommendations',
            $wpdb->prefix . 'ecr_recommendation_performance',
            $wpdb->prefix . 'ecr_user_preferences'
        ];
        
        echo "\n2. Database Tables Check:\n";
        foreach ($tables as $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;
            if ($exists) {
                echo "   ✓ $table exists\n";
            } else {
                echo "   ✗ $table missing\n";
            }
        }
        
        // Check plugin options
        echo "\n3. Plugin Options Check:\n";
        $options = get_option('ecr_options');
        if ($options) {
            echo "   ✓ Plugin options found\n";
            if (isset($options['enabled'])) {
                echo "   ✓ Enabled: " . ($options['enabled'] ? 'Yes' : 'No') . "\n";
            }
            if (isset($options['max_recommendations'])) {
                echo "   ✓ Max recommendations: " . $options['max_recommendations'] . "\n";
            }
        } else {
            echo "   ⚠ Plugin options not found\n";
        }
        
    } else {
        echo "   ✗ Plugin is not active\n";
    }
    
} else {
    echo "   ✗ Plugin file not found\n";
}

echo "\n====================================================\n";
echo "Activation process complete.\n";
?>
