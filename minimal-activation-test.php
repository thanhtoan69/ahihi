<?php
/**
 * Minimal Plugin Activation Test
 * 
 * Focuses on core functionality only
 */

// Load WordPress
require_once('wp-config.php');
require_once('wp-load.php');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Minimal Plugin Activation Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        .button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block; margin: 5px; }
    </style>
</head>
<body>
    <h1>Minimal Plugin Activation Test</h1>
    
    <?php
    // Check if user can activate plugins
    if (!current_user_can('activate_plugins')) {
        echo '<div class="error">You need to be logged in as an administrator to activate plugins.</div>';
        echo '<a href="wp-admin/" class="button">Login to Admin</a>';
        exit;
    }
    
    $plugin_file = 'environmental-item-exchange/environmental-item-exchange.php';
    $plugin_path = WP_PLUGIN_DIR . '/environmental-item-exchange/environmental-item-exchange.php';
    
    echo '<div class="info">Testing plugin activation for: ' . $plugin_file . '</div>';
    
    // Step 1: Check if plugin file exists
    if (!file_exists($plugin_path)) {
        echo '<div class="error">Plugin file not found: ' . $plugin_path . '</div>';
        exit;
    }
    
    echo '<div class="success">✓ Plugin file exists</div>';
    
    // Step 2: Check if plugin is already active
    $active_plugins = get_option('active_plugins', array());
    $is_active = in_array($plugin_file, $active_plugins);
    
    if ($is_active) {
        echo '<div class="success">✓ Plugin is already active</div>';
    } else {
        echo '<div class="info">Plugin is not active. Attempting activation...</div>';
        
        // Activate the plugin
        $result = activate_plugin($plugin_file);
        
        if (is_wp_error($result)) {
            echo '<div class="error">Plugin activation failed: ' . $result->get_error_message() . '</div>';
            echo '<div class="info">Error details: ' . $result->get_error_data() . '</div>';
        } else {
            echo '<div class="success">✓ Plugin activated successfully</div>';
            $is_active = true;
        }
    }
    
    if ($is_active) {
        // Step 3: Force database setup
        echo '<div class="info">Setting up database tables...</div>';
        
        if (file_exists(WP_PLUGIN_DIR . '/environmental-item-exchange/includes/class-database-setup.php')) {
            require_once(WP_PLUGIN_DIR . '/environmental-item-exchange/includes/class-database-setup.php');
            
            if (class_exists('EIE_Database_Setup')) {
                try {
                    EIE_Database_Setup::setup();
                    echo '<div class="success">✓ Database setup completed</div>';
                } catch (Exception $e) {
                    echo '<div class="error">Database setup failed: ' . $e->getMessage() . '</div>';
                }
            }
        }
        
        // Step 4: Check post type registration
        echo '<div class="info">Checking post type registration...</div>';
        
        // Force refresh post types and rewrite rules
        delete_option('rewrite_rules');
        flush_rewrite_rules(true);
        
        // Check if post type exists
        if (post_type_exists('item_exchange')) {
            echo '<div class="success">✓ item_exchange post type is registered</div>';
        } else {
            echo '<div class="warning">⚠ item_exchange post type not found - this may be normal on first activation</div>';
        }
        
        // Step 5: Create a simple test exchange
        echo '<div class="info">Creating test exchange...</div>';
        
        $test_post = array(
            'post_title' => 'Test Exchange - ' . date('Y-m-d H:i:s'),
            'post_content' => 'This is a test exchange created during plugin activation.',
            'post_status' => 'publish',
            'post_type' => 'item_exchange',
            'post_author' => get_current_user_id(),
            'meta_input' => array(
                'exchange_type' => 'free',
                'item_condition' => 'good',
                'environmental_impact' => 10,
                'co2_savings' => 5
            )
        );
        
        $post_id = wp_insert_post($test_post);
        
        if ($post_id && !is_wp_error($post_id)) {
            echo '<div class="success">✓ Test exchange created (ID: ' . $post_id . ')</div>';
            echo '<a href="' . get_edit_post_link($post_id) . '" class="button">Edit Test Exchange</a>';
        } else {
            echo '<div class="warning">⚠ Could not create test exchange</div>';
        }
        
        // Step 6: Test archive URL
        $archive_url = get_post_type_archive_link('item_exchange');
        if ($archive_url) {
            echo '<div class="success">✓ Archive URL available</div>';
            echo '<a href="' . $archive_url . '" class="button">View Exchange Archive</a>';
        }
        
        // Final success message
        echo '<div class="success"><strong>🎉 Plugin activation completed successfully!</strong></div>';
        
        // Quick links
        echo '<h3>Quick Links</h3>';
        echo '<a href="wp-admin/edit.php?post_type=item_exchange" class="button">Manage Exchanges</a>';
        echo '<a href="wp-admin/post-new.php?post_type=item_exchange" class="button">Add New Exchange</a>';
        echo '<a href="wp-admin/plugins.php" class="button">Plugins Page</a>';
        echo '<a href="test-frontend.php" class="button">Test Frontend</a>';
        
    } else {
        echo '<div class="error">Plugin activation failed. Please check the error messages above.</div>';
    }
    
    echo '<div class="info">Test completed at: ' . date('Y-m-d H:i:s') . '</div>';
    ?>
    
</body>
</html>
