<?php
/**
 * Environmental Data Dashboard Admin Test
 * Phase 39: AI Integration & Waste Classification - Admin Interface Test
 */

// Load WordPress with admin context
define('WP_ADMIN', true);
require_once __DIR__ . '/wp-config.php';
require_once __DIR__ . '/wp-load.php';
require_once __DIR__ . '/wp-admin/includes/admin.php';

// Set current user as admin
wp_set_current_user(1);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Environmental Dashboard Admin Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block; margin: 5px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Environmental Data Dashboard - Admin Interface Test</h1>

    <?php
    echo '<div class="test-section">';
    echo '<h2>1. Plugin Status</h2>';
    
    $plugin_file = 'environmental-data-dashboard/environmental-data-dashboard.php';
    $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
    
    if (!file_exists($plugin_path)) {
        echo '<div class="error">❌ Plugin file not found: ' . $plugin_path . '</div>';
        echo '</div></body></html>';
        exit;
    }
    
    // Check if plugin is active
    $active_plugins = get_option('active_plugins', array());
    $is_active = in_array($plugin_file, $active_plugins);
    
    if ($is_active) {
        echo '<div class="success">✅ Plugin is ACTIVE</div>';
    } else {
        echo '<div class="info">⏳ Plugin is not active. Attempting activation...</div>';
        
        $result = activate_plugin($plugin_file);
        if (is_wp_error($result)) {
            echo '<div class="error">❌ Activation failed: ' . $result->get_error_message() . '</div>';
        } else {
            echo '<div class="success">✅ Plugin activated successfully</div>';
            $is_active = true;
        }
    }
    echo '</div>';
    
    if ($is_active) {
        echo '<div class="test-section">';
        echo '<h2>2. Database Tables Verification</h2>';
        
        global $wpdb;
        
        // Force table creation if needed
        if (class_exists('Environmental_Data_Dashboard')) {
            $plugin_instance = Environmental_Data_Dashboard::get_instance();
            
            // Try to trigger database creation
            try {
                $reflection = new ReflectionClass($plugin_instance);
                if ($reflection->hasMethod('create_database_tables')) {
                    $method = $reflection->getMethod('create_database_tables');
                    $method->setAccessible(true);
                    $method->invoke($plugin_instance);
                    echo '<div class="info">📋 Database tables creation triggered</div>';
                }
            } catch (Exception $e) {
                echo '<div class="error">⚠️ Could not trigger table creation: ' . $e->getMessage() . '</div>';
            }
        }
        
        // Test AI tables
        $ai_tables = array(
            'env_ai_classifications' => 'AI Classifications',
            'env_classification_feedback' => 'Classification Feedback', 
            'env_user_gamification' => 'User Gamification',
            'env_achievements' => 'Achievements',
            'env_user_achievements' => 'User Achievements',
            'env_challenges' => 'Challenges',
            'env_user_challenges' => 'User Challenges',
            'env_ai_service_config' => 'AI Service Config',
            'env_ai_usage_log' => 'AI Usage Log'
        );
        
        echo '<table>';
        echo '<tr><th>Table Name</th><th>Status</th><th>Record Count</th></tr>';
        
        $tables_exist = 0;
        foreach ($ai_tables as $table => $description) {
            $full_table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");
            
            if ($exists) {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table_name");
                echo '<tr><td>' . $description . '</td><td style="color: green;">✅ EXISTS</td><td>' . $count . '</td></tr>';
                $tables_exist++;
            } else {
                echo '<tr><td>' . $description . '</td><td style="color: red;">❌ MISSING</td><td>-</td></tr>';
            }
        }
        echo '</table>';
        
        echo '<div class="info">📊 AI Tables Status: ' . $tables_exist . '/' . count($ai_tables) . ' tables exist</div>';
        echo '</div>';
        
        echo '<div class="test-section">';
        echo '<h2>3. Admin Menu Integration Test</h2>';
        
        // Force admin menu setup
        do_action('admin_menu');
        
        global $menu, $submenu;
        
        $env_menu_found = false;
        $ai_submenu_found = false;
        
        if (is_array($menu)) {
            foreach ($menu as $menu_item) {
                if (isset($menu_item[0]) && (
                    strpos(strtolower($menu_item[0]), 'environmental') !== false ||
                    strpos(strtolower($menu_item[0]), 'env dashboard') !== false
                )) {
                    $env_menu_found = true;
                    echo '<div class="success">✅ Environmental Dashboard main menu found: ' . $menu_item[0] . '</div>';
                    
                    // Check submenu
                    $menu_slug = $menu_item[2];
                    if (isset($submenu[$menu_slug])) {
                        echo '<div class="info">📋 Submenus:</div>';
                        echo '<ul>';
                        foreach ($submenu[$menu_slug] as $submenu_item) {
                            echo '<li>' . $submenu_item[0] . ' (' . $submenu_item[2] . ')</li>';
                            if (strpos(strtolower($submenu_item[0]), 'ai') !== false || 
                                strpos(strtolower($submenu_item[0]), 'waste') !== false) {
                                $ai_submenu_found = true;
                            }
                        }
                        echo '</ul>';
                    }
                    break;
                }
            }
        }
        
        if (!$env_menu_found) {
            echo '<div class="error">❌ Environmental Dashboard main menu not found</div>';
        }
        
        if ($ai_submenu_found) {
            echo '<div class="success">✅ AI Waste Classification submenu found</div>';
        } else {
            echo '<div class="error">❌ AI Waste Classification submenu not found</div>';
        }
        echo '</div>';
        
        echo '<div class="test-section">';
        echo '<h2>4. AJAX Handlers Test</h2>';
        
        global $wp_filter;
        
        $ajax_actions = array(
            'wp_ajax_classify_waste_image' => 'Classify Waste Image',
            'wp_ajax_get_ai_classification_stats' => 'Get AI Stats',
            'wp_ajax_save_ai_configuration' => 'Save AI Config',
            'wp_ajax_test_ai_connection' => 'Test AI Connection',
            'wp_ajax_get_recent_classifications' => 'Get Recent Classifications',
            'wp_ajax_update_classification_status' => 'Update Classification Status',
            'wp_ajax_export_classification_data' => 'Export Classification Data'
        );
        
        echo '<table>';
        echo '<tr><th>AJAX Action</th><th>Status</th></tr>';
        
        $ajax_registered = 0;
        foreach ($ajax_actions as $action => $description) {
            $registered = isset($wp_filter[$action]) && !empty($wp_filter[$action]);
            if ($registered) {
                echo '<tr><td>' . $description . '</td><td style="color: green;">✅ REGISTERED</td></tr>';
                $ajax_registered++;
            } else {
                echo '<tr><td>' . $description . '</td><td style="color: red;">❌ NOT REGISTERED</td></tr>';
            }
        }
        echo '</table>';
        
        echo '<div class="info">📊 AJAX Handlers: ' . $ajax_registered . '/' . count($ajax_actions) . ' registered</div>';
        echo '</div>';
        
        echo '<div class="test-section">';
        echo '<h2>5. Asset Enqueuing Test</h2>';
        
        // Simulate admin page load for asset enqueuing
        global $wp_scripts, $wp_styles;
        
        // Force script registration
        do_action('admin_enqueue_scripts', 'environmental-dashboard_page_env-ai-waste-classification');
        
        $admin_js_found = false;
        $admin_css_found = false;
        
        if (isset($wp_scripts->registered['waste-classification-admin-js'])) {
            $admin_js_found = true;
            echo '<div class="success">✅ Admin JavaScript registered: waste-classification-admin-js</div>';
        }
        
        if (isset($wp_styles->registered['waste-classification-admin-css'])) {
            $admin_css_found = true;
            echo '<div class="success">✅ Admin CSS registered: waste-classification-admin-css</div>';
        }
        
        if (!$admin_js_found) {
            echo '<div class="error">❌ Admin JavaScript not registered</div>';
        }
        
        if (!$admin_css_found) {
            echo '<div class="error">❌ Admin CSS not registered</div>';
        }
        echo '</div>';
        
        echo '<div class="test-section">';
        echo '<h2>6. Sample Data Verification</h2>';
        
        $achievements_table = $wpdb->prefix . 'env_achievements';
        $gamification_table = $wpdb->prefix . 'env_user_gamification';
        $service_config_table = $wpdb->prefix . 'env_ai_service_config';
        
        $achievements_count = $wpdb->get_var("SELECT COUNT(*) FROM $achievements_table");
        $config_count = $wpdb->get_var("SELECT COUNT(*) FROM $service_config_table");
        
        echo '<table>';
        echo '<tr><th>Data Type</th><th>Count</th><th>Status</th></tr>';
        echo '<tr><td>Achievements</td><td>' . $achievements_count . '</td><td>' . 
             ($achievements_count > 0 ? '<span style="color: green;">✅ DATA EXISTS</span>' : '<span style="color: red;">❌ NO DATA</span>') . '</td></tr>';
        echo '<tr><td>AI Service Config</td><td>' . $config_count . '</td><td>' . 
             ($config_count > 0 ? '<span style="color: green;">✅ DATA EXISTS</span>' : '<span style="color: red;">❌ NO DATA</span>') . '</td></tr>';
        echo '</table>';
        echo '</div>';
        
        echo '<div class="test-section">';
        echo '<h2>7. Class Loading Test</h2>';
        
        $required_classes = array(
            'Environmental_Data_Dashboard' => 'Main Plugin Class',
            'Environmental_Database_Manager' => 'Database Manager',
            'Environmental_AI_Service_Manager' => 'AI Service Manager',
            'Environmental_Waste_Classification_Interface' => 'Waste Classification Interface',
            'Environmental_Gamification_System' => 'Gamification System'
        );
        
        echo '<table>';
        echo '<tr><th>Class</th><th>Status</th></tr>';
        
        foreach ($required_classes as $class => $description) {
            $exists = class_exists($class);
            echo '<tr><td>' . $description . '</td><td>' . 
                 ($exists ? '<span style="color: green;">✅ LOADED</span>' : '<span style="color: red;">❌ NOT FOUND</span>') . '</td></tr>';
        }
        echo '</table>';
        echo '</div>';
        
        // Summary
        echo '<div class="test-section">';
        echo '<h2>8. Test Summary</h2>';
        
        $total_score = 0;
        $max_score = 0;
        
        // Calculate score
        $max_score += count($ai_tables);
        $total_score += $tables_exist;
        
        $max_score += 2; // Menu items
        $total_score += ($env_menu_found ? 1 : 0) + ($ai_submenu_found ? 1 : 0);
        
        $max_score += count($ajax_actions);
        $total_score += $ajax_registered;
        
        $max_score += 2; // Assets
        $total_score += ($admin_js_found ? 1 : 0) + ($admin_css_found ? 1 : 0);
        
        $max_score += 2; // Sample data
        $total_score += ($achievements_count > 0 ? 1 : 0) + ($config_count > 0 ? 1 : 0);
        
        $max_score += count($required_classes);
        foreach ($required_classes as $class => $description) {
            $total_score += class_exists($class) ? 1 : 0;
        }
        
        $percentage = round(($total_score / $max_score) * 100);
        
        echo '<div class="info">';
        echo '<h3>Overall Test Results</h3>';
        echo '<p><strong>Score:</strong> ' . $total_score . '/' . $max_score . ' (' . $percentage . '%)</p>';
        
        if ($percentage >= 90) {
            echo '<p style="color: green;"><strong>Status: ✅ EXCELLENT</strong> - Phase 39 AI Integration is fully implemented!</p>';
        } elseif ($percentage >= 70) {
            echo '<p style="color: orange;"><strong>Status: ⚠️ GOOD</strong> - Most features implemented, minor issues need fixing</p>';
        } else {
            echo '<p style="color: red;"><strong>Status: ❌ NEEDS WORK</strong> - Several components need attention</p>';
        }
        echo '</div>';
        
        // Quick Actions
        echo '<div style="margin-top: 20px;">';
        echo '<h3>Quick Actions</h3>';
        if (current_user_can('manage_options')) {
            echo '<a href="' . admin_url('admin.php?page=environmental-dashboard') . '" class="button">Go to Dashboard</a>';
            echo '<a href="' . admin_url('admin.php?page=env-ai-waste-classification') . '" class="button">Go to AI Waste Classification</a>';
            echo '<a href="' . admin_url('plugins.php') . '" class="button">Manage Plugins</a>';
        }
        echo '</div>';
        
        echo '</div>';
    }
    ?>

</body>
</html>
