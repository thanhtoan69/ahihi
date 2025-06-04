<?php
/**
 * Simple WordPress test
 */

// Check if WordPress can load
try {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/wp-load.php';
    
    echo "WordPress loaded successfully!\n";
    echo "WordPress version: " . get_bloginfo('version') . "\n";
    echo "Site URL: " . get_site_url() . "\n";
    
    // Check database connection
    global $wpdb;
    $table_prefix = $wpdb->prefix;
    echo "Database table prefix: " . $table_prefix . "\n";
    
    // Check if our plugin directory exists
    $plugin_dir = WP_PLUGIN_DIR . '/environmental-item-exchange/';
    echo "Plugin directory exists: " . (is_dir($plugin_dir) ? "YES" : "NO") . "\n";
    
} catch (Exception $e) {
    echo "Error loading WordPress: " . $e->getMessage() . "\n";
}
?>
