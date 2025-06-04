<!DOCTYPE html>
<html>
<head>
    <title>Environmental Item Exchange Plugin Status</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Environmental Item Exchange Plugin Status</h1>
    
    <?php
    // Load WordPress
    require_once __DIR__ . '/wp-config.php';
    require_once __DIR__ . '/wp-load.php';
    
    // Check plugin status
    $active_plugins = get_option('active_plugins', array());
    $plugin_file = 'environmental-item-exchange/environmental-item-exchange.php';
    $is_active = in_array($plugin_file, $active_plugins);
    ?>
    
    <div class="status <?php echo $is_active ? 'success' : 'error'; ?>">
        <strong>Plugin Status:</strong> <?php echo $is_active ? 'ACTIVE' : 'INACTIVE'; ?>
    </div>
    
    <?php if (!$is_active): ?>
        <div class="info">
            <p>Attempting to activate plugin...</p>
            <?php
            $result = activate_plugin($plugin_file);
            if (is_wp_error($result)) {
                echo '<div class="error">Error: ' . $result->get_error_message() . '</div>';
            } else {
                echo '<div class="success">Plugin activated successfully!</div>';
                $is_active = true;
            }
            ?>
        </div>
    <?php endif; ?>
    
    <?php if ($is_active): ?>
        <h2>Plugin Files Status</h2>
        <?php
        $plugin_path = WP_PLUGIN_DIR . '/environmental-item-exchange/';
        $required_files = array(
            'environmental-item-exchange.php' => 'Main plugin file',
            'includes/class-frontend-templates.php' => 'Frontend templates',
            'includes/class-database-setup.php' => 'Database setup',
            'includes/class-admin-dashboard.php' => 'Admin dashboard',
            'assets/js/frontend.js' => 'Frontend JavaScript',
            'assets/css/frontend.css' => 'Frontend CSS',
            'templates/single-item_exchange.php' => 'Single template',
            'templates/archive-item_exchange.php' => 'Archive template',
            'templates/partials/exchange-card.php' => 'Exchange card partial'
        );
        
        foreach ($required_files as $file => $description) {
            $file_path = $plugin_path . $file;
            $exists = file_exists($file_path);
            echo '<div class="status ' . ($exists ? 'success' : 'error') . '">';
            echo '<strong>' . $description . ':</strong> ' . ($exists ? 'EXISTS' : 'MISSING');
            echo '</div>';
        }
        ?>
        
        <h2>Database Tables</h2>
        <?php
        global $wpdb;
        $tables = array(
            'eie_conversations' => 'Conversations',
            'eie_messages' => 'Messages',
            'eie_ratings' => 'Ratings',
            'eie_saved_exchanges' => 'Saved Exchanges',
            'eie_locations' => 'Locations',
            'eie_analytics' => 'Analytics',
            'eie_user_activity' => 'User Activity'
        );
        
        foreach ($tables as $table => $description) {
            $table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
            echo '<div class="status ' . ($exists ? 'success' : 'error') . '">';
            echo '<strong>' . $description . ' Table:</strong> ' . ($exists ? 'EXISTS' : 'MISSING');
            echo '</div>';
        }
        ?>
        
        <h2>Plugin Configuration</h2>
        <?php
        // Include database setup if tables are missing
        $missing_tables = array();
        foreach ($tables as $table => $description) {
            $table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
            if (!$exists) {
                $missing_tables[] = $table;
            }
        }
        
        if (!empty($missing_tables)) {
            echo '<div class="info">Creating missing database tables...</div>';
            
            $setup_file = $plugin_path . 'includes/class-database-setup.php';
            if (file_exists($setup_file)) {
                require_once $setup_file;
                EIE_Database_Setup::setup();
                echo '<div class="success">Database setup completed!</div>';
            } else {
                echo '<div class="error">Database setup file not found!</div>';
            }
        }
        
        // Check plugin options
        $options = array(
            'eie_enable_geolocation' => 'Geolocation enabled',
            'eie_enable_messaging' => 'Messaging enabled',
            'eie_enable_ratings' => 'Ratings enabled',
            'eie_db_version' => 'Database version',
            'eie_db_setup_complete' => 'Database setup complete'
        );
        
        foreach ($options as $option => $description) {
            $value = get_option($option);
            if ($value !== false) {
                $display_value = is_bool($value) ? ($value ? 'true' : 'false') : $value;
                echo '<div class="status success">';
                echo '<strong>' . $description . ':</strong> ' . $display_value;
                echo '</div>';
            } else {
                echo '<div class="status error">';
                echo '<strong>' . $description . ':</strong> NOT SET';
                echo '</div>';
            }
        }
        
        // Check post types
        $post_types = get_post_types();
        echo '<div class="status ' . (isset($post_types['item_exchange']) ? 'success' : 'error') . '">';
        echo '<strong>Item Exchange Post Type:</strong> ' . (isset($post_types['item_exchange']) ? 'REGISTERED' : 'NOT REGISTERED');
        echo '</div>';
        
        // Flush rewrite rules
        flush_rewrite_rules();
        ?>
        
        <div class="success">
            <h3>✓ Plugin Setup Complete!</h3>
            <p>The Environmental Item Exchange plugin is now active and configured.</p>
            <ul>
                <li><a href="<?php echo admin_url('admin.php?page=environmental-exchange'); ?>">Go to Plugin Dashboard</a></li>
                <li><a href="<?php echo admin_url('edit.php?post_type=item_exchange'); ?>">Manage Exchanges</a></li>
                <li><a href="<?php echo get_post_type_archive_link('item_exchange'); ?>">View Exchange Archive</a></li>
            </ul>
        </div>
    <?php endif; ?>
    
</body>
</html>
