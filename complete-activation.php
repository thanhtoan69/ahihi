<?php
/**
 * Complete Plugin Activation and Testing Script
 * 
 * This script will:
 * 1. Activate the Environmental Item Exchange plugin
 * 2. Run database setup
 * 3. Create test data
 * 4. Verify all functionality
 */

// Load WordPress
require_once('wp-config.php');
require_once('wp-load.php');

// Must be admin to activate plugins
if (!current_user_can('activate_plugins')) {
    wp_die('You do not have sufficient permissions to activate plugins.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Complete Plugin Activation</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        .button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block; margin: 5px; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Environmental Item Exchange Plugin - Complete Activation</h1>
    
    <?php
    $plugin_file = 'environmental-item-exchange/environmental-item-exchange.php';
    $plugin_path = WP_PLUGIN_DIR . '/environmental-item-exchange/environmental-item-exchange.php';
    
    echo '<div class="section">';
    echo '<h2>Step 1: Plugin File Check</h2>';
    
    if (file_exists($plugin_path)) {
        echo '<div class="success">✓ Plugin file exists: ' . $plugin_path . '</div>';
        
        // Get plugin data
        if (!function_exists('get_plugin_data')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        
        $plugin_data = get_plugin_data($plugin_path);
        if (!empty($plugin_data['Name'])) {
            echo '<div class="info">Plugin Name: ' . $plugin_data['Name'] . '</div>';
            echo '<div class="info">Version: ' . $plugin_data['Version'] . '</div>';
            echo '<div class="info">Description: ' . $plugin_data['Description'] . '</div>';
        }
    } else {
        echo '<div class="error">✗ Plugin file not found: ' . $plugin_path . '</div>';
        echo '</div>';
        exit;
    }
    echo '</div>';
    
    echo '<div class="section">';
    echo '<h2>Step 2: Plugin Activation</h2>';
    
    // Check if plugin is already active
    $active_plugins = get_option('active_plugins', array());
    $is_active = in_array($plugin_file, $active_plugins);
    
    if ($is_active) {
        echo '<div class="success">✓ Plugin is already active</div>';
    } else {
        echo '<div class="info">Plugin is not active. Attempting activation...</div>';
        
        // Activate plugin
        $result = activate_plugin($plugin_file);
        
        if (is_wp_error($result)) {
            echo '<div class="error">✗ Plugin activation failed: ' . $result->get_error_message() . '</div>';
        } else {
            echo '<div class="success">✓ Plugin activated successfully</div>';
            
            // Refresh to get the activated plugin
            $active_plugins = get_option('active_plugins', array());
            $is_active = in_array($plugin_file, $active_plugins);
        }
    }
    echo '</div>';
    
    if ($is_active) {
        echo '<div class="section">';
        echo '<h2>Step 3: Database Setup</h2>';
        
        // Load plugin classes
        if (file_exists(WP_PLUGIN_DIR . '/environmental-item-exchange/includes/class-database-setup.php')) {
            require_once(WP_PLUGIN_DIR . '/environmental-item-exchange/includes/class-database-setup.php');
            
            if (class_exists('EIE_Database_Setup')) {
                try {
                    EIE_Database_Setup::setup();
                    echo '<div class="success">✓ Database setup completed</div>';
                } catch (Exception $e) {
                    echo '<div class="error">✗ Database setup failed: ' . $e->getMessage() . '</div>';
                }
            } else {
                echo '<div class="error">✗ EIE_Database_Setup class not found</div>';
            }
        } else {
            echo '<div class="error">✗ Database setup file not found</div>';
        }
        echo '</div>';
        
        echo '<div class="section">';
        echo '<h2>Step 4: Post Type Registration Check</h2>';
        
        // Force refresh rewrite rules
        flush_rewrite_rules();
        
        $post_types = get_post_types(array(), 'objects');
        if (isset($post_types['item_exchange'])) {
            echo '<div class="success">✓ item_exchange post type registered</div>';
            echo '<div class="info">Public: ' . ($post_types['item_exchange']->public ? 'Yes' : 'No') . '</div>';
            echo '<div class="info">Has Archive: ' . ($post_types['item_exchange']->has_archive ? 'Yes' : 'No') . '</div>';
        } else {
            echo '<div class="warning">⚠ item_exchange post type not found - may need plugin reactivation</div>';
        }
        echo '</div>';
        
        echo '<div class="section">';
        echo '<h2>Step 5: Create Test Data</h2>';
        
        // Check if test data already exists
        $existing_exchanges = get_posts(array(
            'post_type' => 'item_exchange',
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ));
        
        if ($existing_exchanges) {
            echo '<div class="info">Test data already exists (' . count($existing_exchanges) . ' exchanges found)</div>';
        } else {
            echo '<div class="info">Creating test exchange data...</div>';
            
            // Create test exchanges
            $test_exchanges = array(
                array(
                    'title' => 'Vintage Bicycle - Great Condition',
                    'content' => 'Classic vintage bicycle in excellent condition. Perfect for city commuting or weekend rides. Has been well maintained and recently serviced.',
                    'type' => 'trade',
                    'condition' => 'excellent',
                    'category' => 'transportation',
                    'impact' => 25,
                    'co2' => 8
                ),
                array(
                    'title' => 'Organic Garden Starter Kit',
                    'content' => 'Complete organic garden starter kit with seeds, soil, and basic tools. Perfect for beginners wanting to start their sustainable garden.',
                    'type' => 'free',
                    'condition' => 'new',
                    'category' => 'gardening',
                    'impact' => 15,
                    'co2' => 5
                ),
                array(
                    'title' => 'Solar Panel Charger',
                    'content' => 'Portable solar panel charger for phones and small devices. Great for camping or emergency backup power.',
                    'type' => 'trade',
                    'condition' => 'good',
                    'category' => 'electronics',
                    'impact' => 35,
                    'co2' => 12
                ),
                array(
                    'title' => 'Reusable Shopping Bags Set',
                    'content' => 'Set of 5 high-quality reusable shopping bags made from recycled materials. Various sizes for different shopping needs.',
                    'type' => 'free',
                    'condition' => 'good',
                    'category' => 'household',
                    'impact' => 10,
                    'co2' => 3
                ),
                array(
                    'title' => 'Composting Bin - Tumbler Style',
                    'content' => 'Large tumbler-style composting bin. Easy to use and maintains proper composting conditions. Includes instruction manual.',
                    'type' => 'trade',
                    'condition' => 'good',
                    'category' => 'gardening',
                    'impact' => 40,
                    'co2' => 15
                )
            );
            
            $created_count = 0;
            foreach ($test_exchanges as $exchange) {
                $post_data = array(
                    'post_title' => $exchange['title'],
                    'post_content' => $exchange['content'],
                    'post_status' => 'publish',
                    'post_type' => 'item_exchange',
                    'post_author' => 1, // Admin user
                    'meta_input' => array(
                        'exchange_type' => $exchange['type'],
                        'item_condition' => $exchange['condition'],
                        'item_category' => $exchange['category'],
                        'environmental_impact' => $exchange['impact'],
                        'co2_savings' => $exchange['co2'],
                        'location' => 'Test Location',
                        'contact_method' => 'message',
                        'availability' => 'available'
                    )
                );
                
                $post_id = wp_insert_post($post_data);
                if ($post_id && !is_wp_error($post_id)) {
                    $created_count++;
                }
            }
            
            echo '<div class="success">✓ Created ' . $created_count . ' test exchanges</div>';
        }
        echo '</div>';
        
        echo '<div class="section">';
        echo '<h2>Step 6: Verification Links</h2>';
        
        $archive_url = get_post_type_archive_link('item_exchange');
        echo '<p><strong>Frontend Links:</strong></p>';
        echo '<a href="' . home_url() . '" class="button">Home Page</a>';
        if ($archive_url) {
            echo '<a href="' . $archive_url . '" class="button">Exchange Archive</a>';
        }
        echo '<a href="test-frontend.php" class="button">Frontend Test</a>';
        
        echo '<p><strong>Admin Links:</strong></p>';
        echo '<a href="' . admin_url('edit.php?post_type=item_exchange') . '" class="button">Manage Exchanges</a>';
        echo '<a href="' . admin_url('post-new.php?post_type=item_exchange') . '" class="button">Add New Exchange</a>';
        echo '<a href="' . admin_url('plugins.php') . '" class="button">Plugins Page</a>';
        
        echo '<p><strong>Test Pages:</strong></p>';
        echo '<a href="test-plugin-database.php" class="button">Database Test</a>';
        echo '<a href="plugin-status.php" class="button">Plugin Status</a>';
        echo '</div>';
        
        echo '<div class="section">';
        echo '<h2>Step 7: Final Status Summary</h2>';
        
        // Final checks
        $final_checks = array();
        
        // Plugin active check
        $final_checks['Plugin Active'] = in_array($plugin_file, get_option('active_plugins', array()));
        
        // Post type check
        $final_checks['Post Type Registered'] = post_type_exists('item_exchange');
        
        // Database tables check
        global $wpdb;
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}eie_conversations'") == $wpdb->prefix . 'eie_conversations';
        $final_checks['Database Tables'] = $table_exists;
        
        // Test data check
        $has_data = !empty(get_posts(array('post_type' => 'item_exchange', 'posts_per_page' => 1)));
        $final_checks['Test Data'] = $has_data;
        
        // Template files check
        $template_exists = file_exists(WP_PLUGIN_DIR . '/environmental-item-exchange/templates/archive-item_exchange.php');
        $final_checks['Templates'] = $template_exists;
        
        echo '<table style="width: 100%; border-collapse: collapse;">';
        echo '<tr><th style="border: 1px solid #ddd; padding: 8px;">Check</th><th style="border: 1px solid #ddd; padding: 8px;">Status</th></tr>';
        
        $all_passed = true;
        foreach ($final_checks as $check => $status) {
            $status_text = $status ? '✓ PASS' : '✗ FAIL';
            $status_color = $status ? 'green' : 'red';
            echo "<tr><td style='border: 1px solid #ddd; padding: 8px;'>$check</td><td style='border: 1px solid #ddd; padding: 8px; color: $status_color;'>$status_text</td></tr>";
            if (!$status) $all_passed = false;
        }
        echo '</table>';
        
        if ($all_passed) {
            echo '<div class="success"><strong>🎉 ALL CHECKS PASSED! The Environmental Item Exchange plugin is fully activated and ready to use.</strong></div>';
        } else {
            echo '<div class="warning"><strong>⚠ Some checks failed. Please review the issues above.</strong></div>';
        }
        echo '</div>';
    }
    
    echo '<div class="info"><strong>Activation completed at:</strong> ' . date('Y-m-d H:i:s') . '</div>';
    ?>
    
</body>
</html>
