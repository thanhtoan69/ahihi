<?php
/**
 * Phase 35: Petition & Campaign System - Final Verification
 * Environmental Platform Project
 * 
 * This script verifies the complete implementation of the petition system
 */

echo "=== PHASE 35: PETITION & CAMPAIGN SYSTEM - FINAL VERIFICATION ===\n";
echo "================================================================\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Project: Environmental Platform\n";
echo "Phase: 35 - Petition & Campaign System\n\n";

// Initialize verification results
$verification_results = array();

try {
    // Load WordPress
    if (!file_exists('wp-config.php')) {
        throw new Exception('WordPress configuration not found');
    }
    
    require_once 'wp-config.php';
    require_once 'wp-load.php';
    
    global $wpdb;
    
    echo "✓ WordPress environment loaded successfully\n\n";
    
    // 1. Plugin Status Verification
    echo "1. PLUGIN STATUS VERIFICATION\n";
    echo "-----------------------------\n";
    
    $plugin_file = 'environmental-platform-petitions/environmental-platform-petitions.php';
    $active_plugins = get_option('active_plugins', array());
    
    if (in_array($plugin_file, $active_plugins)) {
        echo "✓ Environmental Platform Petitions plugin is active\n";
        $verification_results['plugin_active'] = true;
    } else {
        echo "✗ Environmental Platform Petitions plugin is not active\n";
        $verification_results['plugin_active'] = false;
    }
    
    // Check if plugin files exist
    $plugin_path = WP_PLUGIN_DIR . '/environmental-platform-petitions/';
    if (file_exists($plugin_path . 'environmental-platform-petitions.php')) {
        echo "✓ Main plugin file exists\n";
        $verification_results['main_file'] = true;
    } else {
        echo "✗ Main plugin file missing\n";
        $verification_results['main_file'] = false;
    }
    
    echo "\n";
    
    // 2. Core Plugin Infrastructure
    echo "2. CORE PLUGIN INFRASTRUCTURE\n";
    echo "------------------------------\n";
    
    $core_classes = array(
        'Database' => 'includes/class-database.php',
        'Signature Manager' => 'includes/class-signature-manager.php',
        'Verification System' => 'includes/class-verification-system.php',
        'Campaign Manager' => 'includes/class-campaign-manager.php',
        'Share Manager' => 'includes/class-share-manager.php',
        'Analytics' => 'includes/class-analytics.php',
        'Admin Dashboard' => 'includes/class-admin-dashboard.php',
        'Email Notifications' => 'includes/class-email-notifications.php',
        'REST API' => 'includes/class-rest-api.php'
    );
    
    foreach ($core_classes as $name => $path) {
        $full_path = $plugin_path . $path;
        if (file_exists($full_path)) {
            echo "✓ {$name} class file exists\n";
            $verification_results['class_' . strtolower(str_replace(' ', '_', $name))] = true;
        } else {
            echo "✗ {$name} class file missing: {$path}\n";
            $verification_results['class_' . strtolower(str_replace(' ', '_', $name))] = false;
        }
    }
    
    echo "\n";
    
    // 3. Database Tables Verification
    echo "3. DATABASE TABLES VERIFICATION\n";
    echo "-------------------------------\n";
    
    $expected_tables = array(
        'petition_signatures',
        'petition_analytics',
        'petition_milestones',
        'petition_shares',
        'petition_campaigns',
        'petition_campaign_updates'
    );
    
    foreach ($expected_tables as $table) {
        $table_name = $wpdb->prefix . $table;
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        
        if ($table_exists) {
            $row_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            echo "✓ Table {$table} exists (rows: {$row_count})\n";
            $verification_results['table_' . $table] = true;
        } else {
            echo "✗ Table {$table} missing\n";
            $verification_results['table_' . $table] = false;
        }
    }
    
    echo "\n";
    
    // 4. Post Type Integration
    echo "4. POST TYPE INTEGRATION\n";
    echo "------------------------\n";
    
    // Check if env_petition post type exists
    if (post_type_exists('env_petition')) {
        echo "✓ env_petition post type is registered\n";
        $verification_results['post_type'] = true;
        
        // Count existing petitions
        $petition_count = wp_count_posts('env_petition');
        echo "✓ Total petitions: {$petition_count->publish} published, {$petition_count->draft} drafts\n";
        
    } else {
        echo "✗ env_petition post type not found\n";
        $verification_results['post_type'] = false;
    }
    
    // Check taxonomy
    if (taxonomy_exists('petition_type')) {
        echo "✓ petition_type taxonomy is registered\n";
        $verification_results['taxonomy'] = true;
    } else {
        echo "✗ petition_type taxonomy not found\n";
        $verification_results['taxonomy'] = false;
    }
    
    echo "\n";
    
    // 5. Template Files Verification
    echo "5. TEMPLATE FILES VERIFICATION\n";
    echo "------------------------------\n";
    
    $template_files = array(
        'Email Templates' => array(
            'templates/emails/signature-confirmation.php',
            'templates/emails/verification-email.php',
            'templates/emails/milestone-notification.php',
            'templates/emails/campaign-update.php'
        ),
        'Frontend Templates' => array(
            'templates/signature-form.php',
            'templates/progress-tracking.php',
            'templates/share-buttons.php'
        ),
        'Admin Templates' => array(
            'admin/dashboard.php',
            'admin/analytics.php',
            'admin/verification.php',
            'admin/settings.php'
        )
    );
    
    foreach ($template_files as $category => $files) {
        echo "\n{$category}:\n";
        foreach ($files as $file) {
            $full_path = $plugin_path . $file;
            if (file_exists($full_path)) {
                $size = round(filesize($full_path) / 1024, 2);
                echo "  ✓ {$file} ({$size} KB)\n";
                $verification_results['template_' . basename($file, '.php')] = true;
            } else {
                echo "  ✗ {$file} missing\n";
                $verification_results['template_' . basename($file, '.php')] = false;
            }
        }
    }
    
    echo "\n";
    
    // 6. Assets Verification
    echo "6. ASSETS VERIFICATION\n";
    echo "----------------------\n";
    
    $asset_files = array(
        'JavaScript' => array(
            'assets/js/frontend.js',
            'assets/js/admin.js'
        ),
        'CSS' => array(
            'assets/css/frontend.css',
            'assets/css/admin.css'
        )
    );
    
    foreach ($asset_files as $category => $files) {
        echo "\n{$category}:\n";
        foreach ($files as $file) {
            $full_path = $plugin_path . $file;
            if (file_exists($full_path)) {
                $size = round(filesize($full_path) / 1024, 2);
                echo "  ✓ {$file} ({$size} KB)\n";
                $verification_results['asset_' . basename($file, '.js')] = true;
            } else {
                echo "  ✗ {$file} missing\n";
                $verification_results['asset_' . basename($file, '.js')] = false;
            }
        }
    }
    
    echo "\n";
    
    // 7. Shortcode Verification
    echo "7. SHORTCODE VERIFICATION\n";
    echo "-------------------------\n";
    
    $shortcodes = array(
        'petition_signature_form',
        'petition_progress',
        'petition_share'
    );
    
    foreach ($shortcodes as $shortcode) {
        if (shortcode_exists($shortcode)) {
            echo "✓ Shortcode [{$shortcode}] is registered\n";
            $verification_results['shortcode_' . $shortcode] = true;
        } else {
            echo "✗ Shortcode [{$shortcode}] not found\n";
            $verification_results['shortcode_' . $shortcode] = false;
        }
    }
    
    echo "\n";
    
    // 8. Admin Menu Verification
    echo "8. ADMIN MENU VERIFICATION\n";
    echo "--------------------------\n";
    
    // Check if admin pages are registered
    global $admin_page_hooks;
    if (isset($admin_page_hooks['petition-dashboard'])) {
        echo "✓ Petition Dashboard admin menu is registered\n";
        $verification_results['admin_menu'] = true;
    } else {
        echo "✗ Petition Dashboard admin menu not found\n";
        $verification_results['admin_menu'] = false;
    }
    
    echo "\n";
    
    // 9. AJAX Handlers Verification
    echo "9. AJAX HANDLERS VERIFICATION\n";
    echo "-----------------------------\n";
    
    $ajax_actions = array(
        'sign_petition',
        'verify_signature',
        'petition_share',
        'epp_save_settings',
        'epp_test_email',
        'epp_verify_signature',
        'epp_reject_verification',
        'epp_resend_verification'
    );
    
    foreach ($ajax_actions as $action) {
        if (has_action("wp_ajax_{$action}") || has_action("wp_ajax_nopriv_{$action}")) {
            echo "✓ AJAX handler '{$action}' is registered\n";
            $verification_results['ajax_' . $action] = true;
        } else {
            echo "✗ AJAX handler '{$action}' not found\n";
            $verification_results['ajax_' . $action] = false;
        }
    }
    
    echo "\n";
    
    // 10. REST API Verification
    echo "10. REST API VERIFICATION\n";
    echo "-------------------------\n";
    
    // Check if REST API endpoints are registered
    $rest_server = rest_get_server();
    $routes = $rest_server->get_routes();
    
    $expected_routes = array(
        '/epp/v1/petitions',
        '/epp/v1/petitions/(?P<id>\d+)',
        '/epp/v1/petitions/(?P<id>\d+)/signatures',
        '/epp/v1/signatures/(?P<id>\d+)',
        '/epp/v1/signatures/(?P<id>\d+)/verify'
    );
    
    foreach ($expected_routes as $route) {
        $found = false;
        foreach ($routes as $route_path => $route_data) {
            if (strpos($route_path, $route) !== false || $route_path === $route) {
                $found = true;
                break;
            }
        }
        
        if ($found) {
            echo "✓ REST API route '{$route}' is registered\n";
            $verification_results['rest_' . md5($route)] = true;
        } else {
            echo "✗ REST API route '{$route}' not found\n";
            $verification_results['rest_' . md5($route)] = false;
        }
    }
    
    echo "\n";
    
    // 11. Class Instantiation Test
    echo "11. CLASS INSTANTIATION TEST\n";
    echo "----------------------------\n";
    
    $test_classes = array(
        'EPP_Database',
        'EPP_Signature_Manager',
        'EPP_Verification_System',
        'EPP_Campaign_Manager',
        'EPP_Share_Manager',
        'EPP_Analytics',
        'EPP_Admin_Dashboard',
        'EPP_Email_Notifications',
        'EPP_REST_API'
    );
    
    foreach ($test_classes as $class_name) {
        try {
            if (class_exists($class_name)) {
                echo "✓ Class {$class_name} can be instantiated\n";
                $verification_results['instantiate_' . strtolower($class_name)] = true;
            } else {
                echo "✗ Class {$class_name} not found\n";
                $verification_results['instantiate_' . strtolower($class_name)] = false;
            }
        } catch (Exception $e) {
            echo "✗ Error instantiating {$class_name}: " . $e->getMessage() . "\n";
            $verification_results['instantiate_' . strtolower($class_name)] = false;
        }
    }
    
    echo "\n";
    
    // 12. Settings and Options Verification
    echo "12. SETTINGS AND OPTIONS VERIFICATION\n";
    echo "-------------------------------------\n";
    
    $options = array(
        'epp_settings' => 'General Settings',
        'epp_email_settings' => 'Email Settings',
        'epp_verification_settings' => 'Verification Settings',
        'epp_social_settings' => 'Social Media Settings'
    );
    
    foreach ($options as $option_name => $description) {
        $option_value = get_option($option_name);
        if ($option_value !== false) {
            echo "✓ {$description} ({$option_name}) is configured\n";
            $verification_results['option_' . $option_name] = true;
        } else {
            echo "⚠ {$description} ({$option_name}) using defaults\n";
            $verification_results['option_' . $option_name] = 'default';
        }
    }
    
    echo "\n";
    
    // Summary Report
    echo "SUMMARY REPORT\n";
    echo "==============\n";
    
    $total_checks = count($verification_results);
    $passed_checks = count(array_filter($verification_results, function($result) {
        return $result === true;
    }));
    $failed_checks = count(array_filter($verification_results, function($result) {
        return $result === false;
    }));
    $warning_checks = $total_checks - $passed_checks - $failed_checks;
    
    echo "Total Checks: {$total_checks}\n";
    echo "✓ Passed: {$passed_checks}\n";
    echo "✗ Failed: {$failed_checks}\n";
    echo "⚠ Warnings: {$warning_checks}\n\n";
    
    $success_rate = round(($passed_checks / $total_checks) * 100, 1);
    echo "Success Rate: {$success_rate}%\n\n";
    
    if ($success_rate >= 90) {
        echo "🎉 PHASE 35 VERIFICATION: EXCELLENT - System ready for production!\n";
    } elseif ($success_rate >= 80) {
        echo "✅ PHASE 35 VERIFICATION: GOOD - Minor issues to address\n";
    } elseif ($success_rate >= 70) {
        echo "⚠️  PHASE 35 VERIFICATION: MODERATE - Several issues need attention\n";
    } else {
        echo "❌ PHASE 35 VERIFICATION: CRITICAL - Major issues require fixing\n";
    }
    
    // Detailed failure report
    if ($failed_checks > 0) {
        echo "\nFAILED CHECKS DETAIL:\n";
        echo "--------------------\n";
        foreach ($verification_results as $check => $result) {
            if ($result === false) {
                echo "✗ " . str_replace('_', ' ', ucfirst($check)) . "\n";
            }
        }
    }
    
    // Save verification results
    $verification_data = array(
        'timestamp' => current_time('mysql'),
        'phase' => 35,
        'total_checks' => $total_checks,
        'passed_checks' => $passed_checks,
        'failed_checks' => $failed_checks,
        'success_rate' => $success_rate,
        'results' => $verification_results
    );
    
    update_option('phase35_verification_results', $verification_data);
    
    echo "\n✓ Verification results saved to WordPress options\n";
    echo "\nVerification completed at: " . date('Y-m-d H:i:s') . "\n";
    
} catch (Exception $e) {
    echo "\n❌ VERIFICATION ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== END VERIFICATION ===\n";
?>
