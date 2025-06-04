<?php
/**
 * Test script for Environmental Item Exchange Plugin
 */

// Load WordPress
require_once __DIR__ . '/wp-config.php';
require_once __DIR__ . '/wp-load.php';

echo "=== Environmental Item Exchange Plugin Test ===\n\n";

// Check if plugin is active
$active_plugins = get_option('active_plugins', array());
$plugin_file = 'environmental-item-exchange/environmental-item-exchange.php';

echo "Plugin Status: ";
if (in_array($plugin_file, $active_plugins)) {
    echo "ACTIVE\n";
} else {
    echo "INACTIVE\n";
    
    // Try to activate the plugin
    echo "Attempting to activate plugin...\n";
    $result = activate_plugin($plugin_file);
    if (is_wp_error($result)) {
        echo "Error activating plugin: " . $result->get_error_message() . "\n";
    } else {
        echo "Plugin activated successfully!\n";
    }
}

// Check database tables
global $wpdb;
$tables = array(
    'eie_conversations',
    'eie_messages', 
    'eie_ratings',
    'eie_saved_exchanges',
    'eie_locations',
    'eie_analytics',
    'eie_user_activity'
);

echo "\nDatabase Tables Status:\n";
foreach ($tables as $table) {
    $table_name = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    echo "$table: " . ($exists ? "EXISTS" : "MISSING") . "\n";
}

// Check if tables need to be created
$missing_tables = array();
foreach ($tables as $table) {
    $table_name = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    if (!$exists) {
        $missing_tables[] = $table;
    }
}

if (!empty($missing_tables)) {
    echo "\nCreating missing tables...\n";
    
    // Include database setup class
    require_once __DIR__ . '/wp-content/plugins/environmental-item-exchange/includes/class-database-setup.php';
    
    // Run database setup
    EIE_Database_Setup::setup();
    
    echo "Database setup completed!\n";
}

// Check post type
$post_types = get_post_types();
echo "\nitem_exchange post type: " . (isset($post_types['item_exchange']) ? "EXISTS" : "MISSING") . "\n";

// Check taxonomies
$taxonomies = get_taxonomies();
echo "exchange_type taxonomy: " . (isset($taxonomies['exchange_type']) ? "EXISTS" : "MISSING") . "\n";
echo "exchange_category taxonomy: " . (isset($taxonomies['exchange_category']) ? "EXISTS" : "MISSING") . "\n";

// Check plugin files
$plugin_path = __DIR__ . '/wp-content/plugins/environmental-item-exchange/';
$required_files = array(
    'environmental-item-exchange.php',
    'includes/class-frontend-templates.php',
    'includes/class-database-setup.php',
    'assets/js/frontend.js',
    'assets/css/frontend.css',
    'templates/single-item_exchange.php',
    'templates/archive-item_exchange.php',
    'templates/partials/exchange-card.php'
);

echo "\nPlugin Files Status:\n";
foreach ($required_files as $file) {
    $file_path = $plugin_path . $file;
    echo "$file: " . (file_exists($file_path) ? "EXISTS" : "MISSING") . "\n";
}

// Test AJAX endpoints
echo "\nTesting AJAX endpoints...\n";
$ajax_actions = array(
    'eie_search_exchanges',
    'eie_save_exchange',
    'eie_contact_owner',
    'eie_submit_rating',
    'eie_get_dashboard_data'
);

foreach ($ajax_actions as $action) {
    $hook_exists = has_action("wp_ajax_$action") || has_action("wp_ajax_nopriv_$action");
    echo "$action: " . ($hook_exists ? "REGISTERED" : "NOT REGISTERED") . "\n";
}

// Check plugin options
echo "\nPlugin Options:\n";
$options = array(
    'eie_enable_geolocation',
    'eie_enable_messaging',
    'eie_enable_ratings',
    'eie_db_version'
);

foreach ($options as $option) {
    $value = get_option($option);
    echo "$option: " . ($value !== false ? "SET ($value)" : "NOT SET") . "\n";
}

echo "\n=== Test Complete ===\n";
?>
