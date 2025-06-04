<?php
/**
 * Plugin Status Page
 * 
 * This creates a WordPress admin page to test the petition system
 */

// Add admin menu hook
add_action('admin_menu', function() {
    add_management_page(
        'Petition System Test',
        'Petition Test',
        'manage_options',
        'petition-system-test',
        'petition_system_test_page'
    );
});

function petition_system_test_page() {
    ?>
    <div class="wrap">
        <h1>Petition System Test Results</h1>
        
        <div class="notice notice-info">
            <p><strong>Phase 35: Environmental Platform Petition System</strong></p>
        </div>
        
        <?php
        echo '<h2>1. Plugin Status</h2>';
        echo '<table class="widefat">';
        
        // Test 1: Plugin Active
        $plugin_active = is_plugin_active('environmental-platform-petitions/environmental-platform-petitions.php');
        echo '<tr><td>Plugin Active</td><td>' . ($plugin_active ? '✅ Yes' : '❌ No') . '</td></tr>';
        
        // Test 2: Classes
        $classes = ['EPP_Database', 'EPP_Signature_Manager', 'EPP_Verification_System', 'EPP_Campaign_Manager'];
        foreach ($classes as $class) {
            $exists = class_exists($class);
            echo '<tr><td>' . $class . '</td><td>' . ($exists ? '✅ Loaded' : '❌ Missing') . '</td></tr>';
        }
        
        echo '</table>';
        
        // Test 3: Database Tables
        echo '<h2>2. Database Tables</h2>';
        echo '<table class="widefat">';
        global $wpdb;
        $tables = ['petition_signatures', 'petition_analytics', 'petition_milestones', 'petition_shares'];
        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
            echo '<tr><td>' . $table . '</td><td>' . ($exists ? '✅ Exists' : '❌ Missing') . '</td></tr>';
        }
        echo '</table>';
        
        // Test 4: Post Types
        echo '<h2>3. Post Types & Taxonomies</h2>';
        echo '<table class="widefat">';
        echo '<tr><td>env_petition post type</td><td>' . (post_type_exists('env_petition') ? '✅ Registered' : '❌ Missing') . '</td></tr>';
        echo '<tr><td>petition_type taxonomy</td><td>' . (taxonomy_exists('petition_type') ? '✅ Registered' : '❌ Missing') . '</td></tr>';
        echo '</table>';
        
        // Test 5: Shortcodes
        echo '<h2>4. Shortcodes</h2>';
        echo '<table class="widefat">';
        $shortcodes = ['petition_signature_form', 'petition_progress', 'petition_share'];
        foreach ($shortcodes as $shortcode) {
            $exists = shortcode_exists($shortcode);
            echo '<tr><td>[' . $shortcode . ']</td><td>' . ($exists ? '✅ Registered' : '❌ Missing') . '</td></tr>';
        }
        echo '</table>';
        
        // Test 6: File Structure
        echo '<h2>5. File Structure</h2>';
        echo '<table class="widefat">';
        $files = [
            'Main Plugin' => WP_PLUGIN_DIR . '/environmental-platform-petitions/environmental-platform-petitions.php',
            'Database Class' => WP_PLUGIN_DIR . '/environmental-platform-petitions/includes/class-database.php',
            'Frontend JS' => WP_PLUGIN_DIR . '/environmental-platform-petitions/assets/js/frontend.js',
            'Frontend CSS' => WP_PLUGIN_DIR . '/environmental-platform-petitions/assets/css/frontend.css',
            'Admin CSS' => WP_PLUGIN_DIR . '/environmental-platform-petitions/assets/css/admin.css'
        ];
        
        foreach ($files as $name => $file) {
            $exists = file_exists($file);
            $size = $exists ? size_format(filesize($file)) : 'N/A';
            echo '<tr><td>' . $name . '</td><td>' . ($exists ? '✅ Exists (' . $size . ')' : '❌ Missing') . '</td></tr>';
        }
        echo '</table>';
        
        // Test 7: Create Test Petition Button
        echo '<h2>6. Test Operations</h2>';
        
        if (isset($_POST['create_test_petition'])) {
            $petition_data = array(
                'post_title'    => 'Test Petition - ' . date('Y-m-d H:i:s'),
                'post_content'  => 'This is a test petition created by the system verification.',
                'post_status'   => 'publish',
                'post_type'     => 'env_petition',
                'post_author'   => get_current_user_id()
            );
            
            $petition_id = wp_insert_post($petition_data);
            
            if ($petition_id && !is_wp_error($petition_id)) {
                update_post_meta($petition_id, 'petition_goal', 100);
                update_post_meta($petition_id, 'petition_current_signatures', 0);
                echo '<div class="notice notice-success"><p>✅ Test petition created with ID: ' . $petition_id . '</p></div>';
                echo '<p><a href="' . get_permalink($petition_id) . '" target="_blank">View Test Petition</a></p>';
            } else {
                echo '<div class="notice notice-error"><p>❌ Failed to create test petition</p></div>';
            }
        }
        
        echo '<form method="post">';
        echo '<input type="submit" name="create_test_petition" class="button button-primary" value="Create Test Petition">';
        echo '</form>';
        
        // Show sample shortcode
        echo '<h3>Sample Shortcode Usage:</h3>';
        echo '<code>[petition_signature_form petition_id="123"]</code><br>';
        echo '<code>[petition_progress petition_id="123"]</code><br>';
        echo '<code>[petition_share petition_id="123"]</code>';
        ?>
    </div>
    <?php
}

// Save as a plugin file
file_put_contents(WP_PLUGIN_DIR . '/petition-system-tester.php', '<?php
/**
 * Plugin Name: Petition System Tester
 * Description: Test page for Environmental Platform Petition System
 * Version: 1.0.0
 */

' . substr(file_get_contents(__FILE__), 5));

echo "Test plugin created: " . WP_PLUGIN_DIR . '/petition-system-tester.php';
?>
