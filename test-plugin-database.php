<?php
/**
 * Environmental Item Exchange Plugin Database Test
 * 
 * Tests database table creation and plugin functionality
 */

// Load WordPress
require_once('wp-config.php');
require_once('wp-load.php');

// Load plugin files
require_once('wp-content/plugins/environmental-item-exchange/includes/class-database-setup.php');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Plugin Database Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block; margin: 5px; }
        .button:hover { background: #005a87; }
    </style>
</head>
<body>
    <h1>Environmental Item Exchange Plugin Database Test</h1>
    
    <?php
    global $wpdb;
    
    echo '<div class="info"><strong>WordPress Info:</strong><br>';
    echo 'WordPress Version: ' . get_bloginfo('version') . '<br>';
    echo 'Database Prefix: ' . $wpdb->prefix . '<br>';
    echo 'Plugin Directory: wp-content/plugins/environmental-item-exchange/</div>';
    
    // Check if plugin is active
    $active_plugins = get_option('active_plugins', array());
    $plugin_file = 'environmental-item-exchange/environmental-item-exchange.php';
    $is_active = in_array($plugin_file, $active_plugins);
    
    if ($is_active) {
        echo '<div class="success">✓ Plugin is ACTIVE</div>';
    } else {
        echo '<div class="warning">⚠ Plugin is NOT ACTIVE</div>';
        echo '<a href="wp-admin/plugins.php" class="button">Go to Plugins Page</a>';
        echo '<br><br>';
    }
    
    // Test database table creation
    echo '<h2>Database Table Creation Test</h2>';
    
    try {
        // Run database setup
        if (class_exists('EIE_Database_Setup')) {
            echo '<div class="info">Running database setup...</div>';
            EIE_Database_Setup::setup();
            echo '<div class="success">✓ Database setup completed successfully</div>';
        } else {
            echo '<div class="error">✗ EIE_Database_Setup class not found</div>';
        }
    } catch (Exception $e) {
        echo '<div class="error">✗ Database setup failed: ' . $e->getMessage() . '</div>';
    }
    
    // Check if tables exist
    $tables_to_check = array(
        'eie_conversations',
        'eie_messages', 
        'eie_ratings',
        'eie_saved_exchanges',
        'eie_locations',
        'eie_analytics'
    );
    
    echo '<h3>Database Tables Status</h3>';
    echo '<table>';
    echo '<tr><th>Table Name</th><th>Status</th><th>Row Count</th></tr>';
    
    foreach ($tables_to_check as $table) {
        $full_table_name = $wpdb->prefix . $table;
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") == $full_table_name;
        
        if ($table_exists) {
            $row_count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table_name");
            echo "<tr><td>$full_table_name</td><td style='color: green;'>✓ EXISTS</td><td>$row_count</td></tr>";
        } else {
            echo "<tr><td>$full_table_name</td><td style='color: red;'>✗ MISSING</td><td>N/A</td></tr>";
        }
    }
    echo '</table>';
    
    // Check WordPress post type
    echo '<h3>Custom Post Type Status</h3>';
    $post_types = get_post_types(array('public' => true), 'objects');
    
    if (isset($post_types['item_exchange'])) {
        echo '<div class="success">✓ item_exchange post type is registered</div>';
        
        // Count exchanges
        $exchange_count = wp_count_posts('item_exchange');
        echo '<div class="info">Exchange Posts: ' . $exchange_count->publish . ' published, ' . $exchange_count->draft . ' drafts</div>';
    } else {
        echo '<div class="warning">⚠ item_exchange post type not found</div>';
    }
    
    // Check plugin options
    echo '<h3>Plugin Options</h3>';
    $plugin_options = get_option('eie_plugin_options', array());
    
    if (!empty($plugin_options)) {
        echo '<div class="success">✓ Plugin options found</div>';
        echo '<pre>' . print_r($plugin_options, true) . '</pre>';
    } else {
        echo '<div class="warning">⚠ No plugin options found</div>';
    }
    
    // Test plugin file structure
    echo '<h3>Plugin File Structure</h3>';
    $plugin_path = WP_PLUGIN_DIR . '/environmental-item-exchange/';
    $required_files = array(
        'environmental-item-exchange.php',
        'includes/class-frontend-templates.php',
        'includes/class-database-setup.php',
        'assets/css/frontend.css',
        'assets/js/frontend.js',
        'templates/single-item_exchange.php',
        'templates/archive-item_exchange.php',
        'templates/partials/exchange-card.php'
    );
    
    echo '<table>';
    echo '<tr><th>File</th><th>Status</th><th>Size</th></tr>';
    
    foreach ($required_files as $file) {
        $full_path = $plugin_path . $file;
        if (file_exists($full_path)) {
            $size = filesize($full_path);
            $size_formatted = $size > 1024 ? round($size/1024, 1) . ' KB' : $size . ' bytes';
            echo "<tr><td>$file</td><td style='color: green;'>✓ EXISTS</td><td>$size_formatted</td></tr>";
        } else {
            echo "<tr><td>$file</td><td style='color: red;'>✗ MISSING</td><td>N/A</td></tr>";
        }
    }
    echo '</table>';
    
    // Test AJAX endpoints
    echo '<h3>AJAX Endpoints Test</h3>';
    if (is_admin()) {
        echo '<div class="info">Admin context - AJAX endpoints should be available</div>';
    } else {
        echo '<div class="info">Frontend context - Testing AJAX availability</div>';
    }
    
    // Quick JavaScript test
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Test if AJAX URL is available
        if (typeof ajaxurl !== 'undefined') {
            console.log('AJAX URL available:', ajaxurl);
        } else {
            console.log('AJAX URL not available in frontend');
        }
    });
    </script>
    
    <h3>Quick Actions</h3>
    <a href="wp-admin/edit.php?post_type=item_exchange" class="button">View Exchanges</a>
    <a href="wp-admin/post-new.php?post_type=item_exchange" class="button">Add New Exchange</a>
    <a href="wp-admin/plugins.php" class="button">Manage Plugins</a>
    <a href="/" class="button">View Site</a>
    
    <?php
    // Test database queries
    echo '<h3>Database Query Tests</h3>';
    
    try {
        // Test a simple query
        $test_query = $wpdb->get_results("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE '{$wpdb->prefix}eie_%'");
        
        if ($test_query) {
            echo '<div class="success">✓ Database connection working</div>';
            echo '<div class="info">Found ' . count($test_query) . ' EIE tables</div>';
        } else {
            echo '<div class="warning">⚠ No EIE tables found in database</div>';
        }
    } catch (Exception $e) {
        echo '<div class="error">✗ Database query failed: ' . $e->getMessage() . '</div>';
    }
    
    echo '<div class="info"><strong>Test completed at:</strong> ' . date('Y-m-d H:i:s') . '</div>';
    ?>
    
</body>
</html>
