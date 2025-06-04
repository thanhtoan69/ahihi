<?php
/**
 * Plugin Name: Petition System Tester
 * Plugin URI: https://environmental-platform.local
 * Description: Test page for Environmental Platform Petition System - Phase 35
 * Version: 1.0.0
 * Author: Environmental Platform Team
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
add_action('admin_menu', 'petition_test_admin_menu');

function petition_test_admin_menu() {
    add_management_page(
        'Petition System Test',
        'Petition Test',
        'manage_options',
        'petition-system-test',
        'petition_system_test_page'
    );
}

function petition_system_test_page() {
    ?>
    <div class="wrap">
        <h1>🧪 Petition System Test Results</h1>
        
        <div class="notice notice-info">
            <p><strong>Phase 35: Environmental Platform Petition System - Comprehensive Test</strong></p>
        </div>
        
        <style>
        .test-table { margin: 20px 0; }
        .test-table td:first-child { font-weight: bold; width: 300px; }
        .test-section { margin: 30px 0; }
        .success { color: #46b450; }
        .error { color: #dc3232; }
        .test-actions { margin: 20px 0; }
        .test-actions .button { margin-right: 10px; }
        </style>
        
        <?php
        echo '<div class="test-section">';
        echo '<h2>1. 🔌 Plugin Status</h2>';
        echo '<table class="widefat test-table">';
        
        // Test Plugin Active
        $plugin_active = is_plugin_active('environmental-platform-petitions/environmental-platform-petitions.php');
        echo '<tr><td>Environmental Platform Petitions</td><td class="' . ($plugin_active ? 'success' : 'error') . '">' . ($plugin_active ? '✅ Active' : '❌ Inactive') . '</td></tr>';
        
        $core_active = is_plugin_active('environmental-platform-core/environmental-platform-core.php');
        echo '<tr><td>Environmental Platform Core</td><td class="' . ($core_active ? 'success' : 'error') . '">' . ($core_active ? '✅ Active' : '❌ Inactive') . '</td></tr>';
        
        echo '</table></div>';
        
        // Test Classes
        echo '<div class="test-section">';
        echo '<h2>2. 🏗️ Core Classes</h2>';
        echo '<table class="widefat test-table">';
        $classes = [
            'EPP_Database' => 'Database Management',
            'EPP_Signature_Manager' => 'Signature Collection',
            'EPP_Verification_System' => 'Email/Phone Verification',
            'EPP_Campaign_Manager' => 'Campaign Management',
            'EPP_Share_Manager' => 'Social Media Sharing',
            'EPP_Analytics' => 'Analytics & Reporting',
            'EPP_Admin_Dashboard' => 'Admin Interface',
            'EPP_Email_Notifications' => 'Email System',
            'EPP_REST_API' => 'REST API Endpoints'
        ];
        
        foreach ($classes as $class => $description) {
            $exists = class_exists($class);
            echo '<tr><td>' . $description . ' (' . $class . ')</td><td class="' . ($exists ? 'success' : 'error') . '">' . ($exists ? '✅ Loaded' : '❌ Missing') . '</td></tr>';
        }
        echo '</table></div>';
        
        // Test Database Tables
        echo '<div class="test-section">';
        echo '<h2>3. 🗄️ Database Tables</h2>';
        echo '<table class="widefat test-table">';
        global $wpdb;
        $tables = [
            'petition_signatures' => 'Signature Storage',
            'petition_analytics' => 'Event Tracking',
            'petition_milestones' => 'Progress Milestones',
            'petition_shares' => 'Social Media Shares',
            'petition_campaigns' => 'Campaign Data',
            'petition_campaign_updates' => 'Campaign Updates'
        ];
        
        foreach ($tables as $table => $description) {
            $table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
            $count = $exists ? $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}") : 0;
            echo '<tr><td>' . $description . ' (' . $table . ')</td><td class="' . ($exists ? 'success' : 'error') . '">' . ($exists ? '✅ Exists (' . $count . ' records)' : '❌ Missing') . '</td></tr>';
        }
        echo '</table></div>';
        
        // Test Post Types
        echo '<div class="test-section">';
        echo '<h2>4. 📝 Post Types & Taxonomies</h2>';
        echo '<table class="widefat test-table">';
        
        $post_type_exists = post_type_exists('env_petition');
        $petition_count = $post_type_exists ? wp_count_posts('env_petition')->publish : 0;
        echo '<tr><td>Petition Post Type (env_petition)</td><td class="' . ($post_type_exists ? 'success' : 'error') . '">' . ($post_type_exists ? '✅ Registered (' . $petition_count . ' petitions)' : '❌ Missing') . '</td></tr>';
        
        $taxonomy_exists = taxonomy_exists('petition_type');
        $terms_count = $taxonomy_exists ? wp_count_terms(['taxonomy' => 'petition_type']) : 0;
        echo '<tr><td>Petition Type Taxonomy</td><td class="' . ($taxonomy_exists ? 'success' : 'error') . '">' . ($taxonomy_exists ? '✅ Registered (' . $terms_count . ' terms)' : '❌ Missing') . '</td></tr>';
        
        echo '</table></div>';
        
        // Test Shortcodes
        echo '<div class="test-section">';
        echo '<h2>5. 🔗 Shortcodes</h2>';
        echo '<table class="widefat test-table">';
        $shortcodes = [
            'petition_signature_form' => 'Signature Collection Form',
            'petition_progress' => 'Progress Tracking Display',
            'petition_share' => 'Social Media Share Buttons'
        ];
        
        foreach ($shortcodes as $shortcode => $description) {
            $exists = shortcode_exists($shortcode);
            echo '<tr><td>' . $description . ' ([' . $shortcode . '])</td><td class="' . ($exists ? 'success' : 'error') . '">' . ($exists ? '✅ Registered' : '❌ Missing') . '</td></tr>';
        }
        echo '</table></div>';
        
        // Test File Structure
        echo '<div class="test-section">';
        echo '<h2>6. 📁 File Structure</h2>';
        echo '<table class="widefat test-table">';
        $files = [
            'Main Plugin File' => 'environmental-platform-petitions/environmental-platform-petitions.php',
            'Database Class' => 'environmental-platform-petitions/includes/class-database.php',
            'Signature Manager' => 'environmental-platform-petitions/includes/class-signature-manager.php',
            'Verification System' => 'environmental-platform-petitions/includes/class-verification-system.php',
            'Campaign Manager' => 'environmental-platform-petitions/includes/class-campaign-manager.php',
            'Frontend JavaScript' => 'environmental-platform-petitions/assets/js/frontend.js',
            'Frontend CSS' => 'environmental-platform-petitions/assets/css/frontend.css',
            'Admin JavaScript' => 'environmental-platform-petitions/assets/js/admin.js',
            'Admin CSS' => 'environmental-platform-petitions/assets/css/admin.css'
        ];
        
        foreach ($files as $name => $path) {
            $full_path = WP_PLUGIN_DIR . '/' . $path;
            $exists = file_exists($full_path);
            $size = $exists ? size_format(filesize($full_path)) : 'N/A';
            echo '<tr><td>' . $name . '</td><td class="' . ($exists ? 'success' : 'error') . '">' . ($exists ? '✅ Found (' . $size . ')' : '❌ Missing') . '</td></tr>';
        }
        echo '</table></div>';
        
        // Test Operations
        echo '<div class="test-section">';
        echo '<h2>7. 🧪 Test Operations</h2>';
        
        // Handle test petition creation
        if (isset($_POST['create_test_petition']) && wp_verify_nonce($_POST['test_nonce'], 'petition_test')) {
            $petition_data = array(
                'post_title'    => 'Test Petition - ' . date('Y-m-d H:i:s'),
                'post_content'  => 'This is a test petition created by the system verification tool to test the petition functionality.',
                'post_status'   => 'publish',
                'post_type'     => 'env_petition',
                'post_author'   => get_current_user_id(),
                'meta_input'    => array(
                    'petition_goal' => 100,
                    'petition_current_signatures' => 0,
                    'petition_status' => 'active',
                    'petition_description' => 'System test petition',
                    'petition_target' => 'Test Target',
                    'petition_category' => 'Testing'
                )
            );
            
            $petition_id = wp_insert_post($petition_data);
            
            if ($petition_id && !is_wp_error($petition_id)) {
                echo '<div class="notice notice-success"><p>✅ Test petition created successfully!</p>';
                echo '<p><strong>Petition ID:</strong> ' . $petition_id . '</p>';
                echo '<p><strong>Title:</strong> ' . get_the_title($petition_id) . '</p>';
                echo '<p><a href="' . get_permalink($petition_id) . '" target="_blank" class="button">View Petition</a> ';
                echo '<a href="' . get_edit_post_link($petition_id) . '" target="_blank" class="button">Edit Petition</a></p>';
                echo '</div>';
            } else {
                echo '<div class="notice notice-error"><p>❌ Failed to create test petition</p>';
                if (is_wp_error($petition_id)) {
                    echo '<p>Error: ' . $petition_id->get_error_message() . '</p>';
                }
                echo '</div>';
            }
        }
        
        // Handle database setup
        if (isset($_POST['setup_database']) && wp_verify_nonce($_POST['test_nonce'], 'petition_test')) {
            if (class_exists('EPP_Database')) {
                $database = new EPP_Database();
                $database->create_tables();
                echo '<div class="notice notice-success"><p>✅ Database tables setup initiated</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>❌ EPP_Database class not found</p></div>';
            }
        }
        
        echo '<div class="test-actions">';
        echo '<form method="post" style="display: inline-block;">';
        wp_nonce_field('petition_test', 'test_nonce');
        echo '<input type="submit" name="create_test_petition" class="button button-primary" value="🆕 Create Test Petition">';
        echo '</form>';
        
        echo '<form method="post" style="display: inline-block;">';
        wp_nonce_field('petition_test', 'test_nonce');
        echo '<input type="submit" name="setup_database" class="button button-secondary" value="🗄️ Setup Database Tables">';
        echo '</form>';
        echo '</div>';
        
        // Show usage examples
        echo '<h3>📋 Usage Examples:</h3>';
        echo '<div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #0073aa;">';
        echo '<p><strong>Shortcode Examples:</strong></p>';
        echo '<code>[petition_signature_form petition_id="123"]</code><br>';
        echo '<code>[petition_progress petition_id="123"]</code><br>';
        echo '<code>[petition_share petition_id="123"]</code><br><br>';
        
        echo '<p><strong>Admin Pages:</strong></p>';
        echo '<a href="' . admin_url('admin.php?page=petition-dashboard') . '">Petition Dashboard</a><br>';
        echo '<a href="' . admin_url('admin.php?page=petition-analytics') . '">Analytics</a><br>';
        echo '<a href="' . admin_url('admin.php?page=petition-verification') . '">Verification Management</a><br>';
        echo '<a href="' . admin_url('admin.php?page=petition-settings') . '">Settings</a>';
        echo '</div>';
        echo '</div>';
        ?>
    </div>
    
    <script>
    // Auto-refresh every 30 seconds for real-time updates
    setTimeout(function() {
        if (confirm('Refresh test results to see latest status?')) {
            location.reload();
        }
    }, 30000);
    </script>
    <?php
}
?>
