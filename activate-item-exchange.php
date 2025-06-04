<?php
/**
 * Activate Environmental Item Exchange Plugin and Setup Database
 */

// Suppress output buffering and set headers
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Load WordPress environment
define('WP_USE_THEMES', false);
require_once __DIR__ . '/wp-load.php';

echo "=== Environmental Item Exchange Plugin Activation ===\n\n";

// Check if plugin is already active
$active_plugins = get_option('active_plugins', array());
$plugin_file = 'environmental-item-exchange/environmental-item-exchange.php';

if (in_array($plugin_file, $active_plugins)) {
    echo "Plugin Status: ALREADY ACTIVE\n";
} else {
    echo "Activating plugin...\n";
    
    // Activate the plugin
    $result = activate_plugin($plugin_file);
    
    if (is_wp_error($result)) {
        echo "Error activating plugin: " . $result->get_error_message() . "\n";
    } else {
        echo "Plugin activated successfully!\n";
    }
}

// Include the database setup class
$setup_file = WP_PLUGIN_DIR . '/environmental-item-exchange/includes/class-database-setup.php';
if (file_exists($setup_file)) {
    require_once $setup_file;
    
    echo "\nRunning database setup...\n";
    
    // Run database setup
    EIE_Database_Setup::setup();
    
    echo "Database setup completed!\n";
} else {
    echo "Database setup file not found!\n";
}

// Verify database tables
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

// Check post type registration
$post_types = get_post_types();
echo "\nPost Type Status:\n";
echo "item_exchange: " . (isset($post_types['item_exchange']) ? "REGISTERED" : "NOT REGISTERED") . "\n";

// Check taxonomies
$taxonomies = get_taxonomies();
echo "\nTaxonomy Status:\n";
echo "exchange_type: " . (isset($taxonomies['exchange_type']) ? "REGISTERED" : "NOT REGISTERED") . "\n";
echo "exchange_category: " . (isset($taxonomies['exchange_category']) ? "REGISTERED" : "NOT REGISTERED") . "\n";

// Check plugin options
echo "\nPlugin Options:\n";
$options = array(
    'eie_enable_geolocation',
    'eie_enable_messaging',
    'eie_enable_ratings',
    'eie_db_version',
    'eie_db_setup_complete'
);

foreach ($options as $option) {
    $value = get_option($option);
    if ($value !== false) {
        echo "$option: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "\n";
    } else {
        echo "$option: NOT SET\n";
    }
}

// Flush rewrite rules to ensure custom post type URLs work
flush_rewrite_rules();

echo "\n=== Plugin Activation Complete ===\n";
echo "The Environmental Item Exchange plugin is now active and ready to use!\n";
echo "Visit your WordPress admin dashboard to configure settings.\n";
?>
