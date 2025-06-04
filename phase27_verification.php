<?php
/**
 * Phase 27 Verification Script
 * WordPress Core Setup & Configuration Verification
 * 
 * This script verifies that WordPress has been properly installed and configured
 * for the Environmental Platform project.
 */

// Load WordPress
require_once('wp-config.php');
require_once('wp-load.php');

class Phase27Verification {
    
    private $results = array();
    private $errors = array();
    private $warnings = array();
    
    public function run_verification() {
        echo "<h1>Phase 27: WordPress Core Setup & Configuration Verification</h1>\n";
        echo "<p>Verifying WordPress installation and configuration for Environmental Platform...</p>\n\n";
        
        $this->check_wordpress_installation();
        $this->check_database_connection();
        $this->check_wp_config();
        $this->check_file_permissions();
        $this->check_plugin_structure();
        $this->check_custom_post_types();
        $this->check_rest_api();
        $this->check_security_configuration();
        
        $this->display_results();
        $this->generate_completion_report();
        
        return empty($this->errors);
    }
    
    private function check_wordpress_installation() {
        echo "=== WordPress Installation Check ===\n";
        
        // Check WordPress version
        $wp_version = get_bloginfo('version');
        if (version_compare($wp_version, '5.0', '>=')) {
            $this->results['wp_version'] = "✅ WordPress version: {$wp_version}";
        } else {
            $this->errors[] = "❌ WordPress version too old: {$wp_version}";
        }
        
        // Check if WordPress is installed
        if (is_blog_installed()) {
            $this->results['wp_installed'] = "✅ WordPress is properly installed";
        } else {
            $this->errors[] = "❌ WordPress installation incomplete";
        }
        
        // Check core files
        $core_files = array('wp-load.php', 'wp-settings.php', 'wp-blog-header.php');
        foreach ($core_files as $file) {
            if (file_exists(ABSPATH . $file)) {
                $this->results['core_files'][] = "✅ Core file exists: {$file}";
            } else {
                $this->errors[] = "❌ Missing core file: {$file}";
            }
        }
        
        echo "WordPress installation check completed.\n\n";
    }
    
    private function check_database_connection() {
        echo "=== Database Connection Check ===\n";
        
        global $wpdb;
        
        // Check database connection
        if ($wpdb->check_connection()) {
            $this->results['db_connection'] = "✅ Database connection successful";
        } else {
            $this->errors[] = "❌ Database connection failed";
            return;
        }
        
        // Check database name
        $db_name = $wpdb->get_var("SELECT DATABASE()");
        if ($db_name === 'environmental_platform') {
            $this->results['db_name'] = "✅ Connected to correct database: {$db_name}";
        } else {
            $this->errors[] = "❌ Connected to wrong database: {$db_name}";
        }
        
        // Check custom tables exist
        $required_tables = array('users', 'posts', 'events', 'achievements', 'categories');
        foreach ($required_tables as $table) {
            $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
            if ($exists) {
                $this->results['custom_tables'][] = "✅ Custom table exists: {$table}";
            } else {
                $this->warnings[] = "⚠️  Custom table missing: {$table}";
            }
        }
        
        // Check total tables count
        $table_count = $wpdb->get_var("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'environmental_platform'");
        if ($table_count >= 120) {
            $this->results['table_count'] = "✅ Database has {$table_count} tables (expected 120+)";
        } else {
            $this->warnings[] = "⚠️  Database has only {$table_count} tables (expected 120+)";
        }
        
        echo "Database connection check completed.\n\n";
    }
    
    private function check_wp_config() {
        echo "=== wp-config.php Configuration Check ===\n";
        
        // Check if wp-config.php exists
        if (file_exists(ABSPATH . 'wp-config.php')) {
            $this->results['wp_config_exists'] = "✅ wp-config.php exists";
        } else {
            $this->errors[] = "❌ wp-config.php not found";
            return;
        }
        
        // Check database configuration
        if (defined('DB_NAME') && DB_NAME === 'environmental_platform') {
            $this->results['db_config'] = "✅ Database name configured correctly";
        } else {
            $this->errors[] = "❌ Database name not configured correctly";
        }
        
        // Check security keys
        $security_keys = array('AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY');
        $keys_configured = 0;
        foreach ($security_keys as $key) {
            if (defined($key) && strlen(constant($key)) > 20) {
                $keys_configured++;
            }
        }
        
        if ($keys_configured === count($security_keys)) {
            $this->results['security_keys'] = "✅ All security keys configured";
        } else {
            $this->warnings[] = "⚠️  Only {$keys_configured}/{count($security_keys)} security keys configured";
        }
        
        // Check debug configuration
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->results['debug_mode'] = "✅ Debug mode enabled (development)";
        } else {
            $this->warnings[] = "⚠️  Debug mode disabled";
        }
        
        // Check memory limit
        if (defined('WP_MEMORY_LIMIT')) {
            $this->results['memory_limit'] = "✅ Memory limit set: " . WP_MEMORY_LIMIT;
        } else {
            $this->warnings[] = "⚠️  Memory limit not explicitly set";
        }
        
        echo "wp-config.php configuration check completed.\n\n";
    }
    
    private function check_file_permissions() {
        echo "=== File Permissions Check ===\n";
        
        // Check wp-content directory permissions
        $wp_content_path = ABSPATH . 'wp-content';
        if (is_writable($wp_content_path)) {
            $this->results['wp_content_writable'] = "✅ wp-content directory is writable";
        } else {
            $this->errors[] = "❌ wp-content directory is not writable";
        }
        
        // Check uploads directory
        $uploads_dir = wp_upload_dir();
        if (is_writable($uploads_dir['basedir'])) {
            $this->results['uploads_writable'] = "✅ Uploads directory is writable";
        } else {
            $this->errors[] = "❌ Uploads directory is not writable: " . $uploads_dir['basedir'];
        }
        
        // Check plugins directory
        $plugins_path = WP_PLUGIN_DIR;
        if (is_writable($plugins_path)) {
            $this->results['plugins_writable'] = "✅ Plugins directory is writable";
        } else {
            $this->warnings[] = "⚠️  Plugins directory is not writable";
        }
        
        echo "File permissions check completed.\n\n";
    }
    
    private function check_plugin_structure() {
        echo "=== Plugin Structure Check ===\n";
        
        // Check if our custom plugin exists
        $plugin_file = WP_PLUGIN_DIR . '/environmental-platform-core/environmental-platform-core.php';
        if (file_exists($plugin_file)) {
            $this->results['custom_plugin'] = "✅ Environmental Platform Core plugin exists";
        } else {
            $this->errors[] = "❌ Environmental Platform Core plugin not found";
        }
        
        // Check plugin directories
        $plugin_dirs = array('admin', 'assets');
        foreach ($plugin_dirs as $dir) {
            $dir_path = WP_PLUGIN_DIR . '/environmental-platform-core/' . $dir;
            if (is_dir($dir_path)) {
                $this->results['plugin_dirs'][] = "✅ Plugin directory exists: {$dir}";
            } else {
                $this->warnings[] = "⚠️  Plugin directory missing: {$dir}";
            }
        }
        
        // Check default plugins
        $default_plugins = array('akismet/akismet.php', 'hello.php');
        foreach ($default_plugins as $plugin) {
            if (file_exists(WP_PLUGIN_DIR . '/' . $plugin)) {
                $this->results['default_plugins'][] = "✅ Default plugin exists: {$plugin}";
            } else {
                $this->warnings[] = "⚠️  Default plugin missing: {$plugin}";
            }
        }
        
        echo "Plugin structure check completed.\n\n";
    }
    
    private function check_custom_post_types() {
        echo "=== Custom Post Types Check ===\n";
        
        // This would normally check if post types are registered
        // For now, we'll check if the plugin file contains the registration code
        $plugin_file = WP_PLUGIN_DIR . '/environmental-platform-core/environmental-platform-core.php';
        if (file_exists($plugin_file)) {
            $plugin_content = file_get_contents($plugin_file);
            
            $post_types = array('environmental_post', 'environmental_event', 'waste_classification');
            foreach ($post_types as $post_type) {
                if (strpos($plugin_content, $post_type) !== false) {
                    $this->results['post_types'][] = "✅ Post type defined: {$post_type}";
                } else {
                    $this->warnings[] = "⚠️  Post type not found: {$post_type}";
                }
            }
        }
        
        echo "Custom post types check completed.\n\n";
    }
    
    private function check_rest_api() {
        echo "=== REST API Check ===\n";
        
        // Check if REST API is enabled
        if (function_exists('rest_url')) {
            $rest_url = rest_url();
            $this->results['rest_api'] = "✅ REST API available at: {$rest_url}";
        } else {
            $this->warnings[] = "⚠️  REST API not available";
        }
        
        echo "REST API check completed.\n\n";
    }
    
    private function check_security_configuration() {
        echo "=== Security Configuration Check ===\n";
        
        // Check if file editing is disabled
        if (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) {
            $this->results['file_edit_disabled'] = "✅ File editing disabled for security";
        } else {
            $this->warnings[] = "⚠️  File editing not disabled";
        }
        
        // Check debug log configuration
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            $this->results['debug_log'] = "✅ Debug logging enabled";
        } else {
            $this->warnings[] = "⚠️  Debug logging not enabled";
        }
        
        // Check if admin user exists
        $admin_user = get_user_by('login', 'admin');
        if ($admin_user) {
            $this->warnings[] = "⚠️  Default 'admin' user exists - consider changing username";
        } else {
            $this->results['no_admin_user'] = "✅ No default 'admin' user found";
        }
        
        echo "Security configuration check completed.\n\n";
    }
    
    private function display_results() {
        echo "=== PHASE 27 VERIFICATION RESULTS ===\n\n";
        
        // Display successful checks
        echo "✅ SUCCESSFUL CHECKS:\n";
        foreach ($this->results as $category => $result) {
            if (is_array($result)) {
                foreach ($result as $item) {
                    echo "   {$item}\n";
                }
            } else {
                echo "   {$result}\n";
            }
        }
        echo "\n";
        
        // Display warnings
        if (!empty($this->warnings)) {
            echo "⚠️  WARNINGS:\n";
            foreach ($this->warnings as $warning) {
                echo "   {$warning}\n";
            }
            echo "\n";
        }
        
        // Display errors
        if (!empty($this->errors)) {
            echo "❌ ERRORS:\n";
            foreach ($this->errors as $error) {
                echo "   {$error}\n";
            }
            echo "\n";
        }
        
        // Summary
        $total_checks = count($this->results) + count($this->warnings) + count($this->errors);
        $successful_checks = count($this->results);
        $success_rate = round(($successful_checks / $total_checks) * 100, 1);
        
        echo "SUMMARY:\n";
        echo "- Total Checks: {$total_checks}\n";
        echo "- Successful: {$successful_checks}\n";
        echo "- Warnings: " . count($this->warnings) . "\n";
        echo "- Errors: " . count($this->errors) . "\n";
        echo "- Success Rate: {$success_rate}%\n\n";
        
        if (empty($this->errors)) {
            echo "🎉 PHASE 27 COMPLETED SUCCESSFULLY!\n";
            echo "WordPress Core Setup & Configuration is ready for the Environmental Platform.\n\n";
        } else {
            echo "❌ PHASE 27 COMPLETED WITH ERRORS\n";
            echo "Please fix the errors above before proceeding.\n\n";
        }
    }
    
    private function generate_completion_report() {
        $report_content = "# Phase 27 Completion Report: WordPress Core Setup & Configuration\n\n";
        $report_content .= "**Date:** " . date('Y-m-d H:i:s') . "\n";
        $report_content .= "**Phase:** 27 - WordPress Core Setup & Configuration\n";
        $report_content .= "**Status:** " . (empty($this->errors) ? "COMPLETED SUCCESSFULLY" : "COMPLETED WITH ERRORS") . "\n\n";
        
        $report_content .= "## WordPress Configuration\n\n";
        $report_content .= "- **WordPress Version:** " . get_bloginfo('version') . "\n";
        $report_content .= "- **Database:** environmental_platform\n";
        $report_content .= "- **PHP Version:** " . PHP_VERSION . "\n";
        $report_content .= "- **Site URL:** " . get_site_url() . "\n\n";
        
        $report_content .= "## Verification Results\n\n";
        $report_content .= "### ✅ Successful Checks\n";
        foreach ($this->results as $result) {
            if (is_array($result)) {
                foreach ($result as $item) {
                    $report_content .= "- " . strip_tags($item) . "\n";
                }
            } else {
                $report_content .= "- " . strip_tags($result) . "\n";
            }
        }
        
        if (!empty($this->warnings)) {
            $report_content .= "\n### ⚠️ Warnings\n";
            foreach ($this->warnings as $warning) {
                $report_content .= "- " . strip_tags($warning) . "\n";
            }
        }
        
        if (!empty($this->errors)) {
            $report_content .= "\n### ❌ Errors\n";
            foreach ($this->errors as $error) {
                $report_content .= "- " . strip_tags($error) . "\n";
            }
        }
        
        $report_content .= "\n## Next Steps\n\n";
        if (empty($this->errors)) {
            $report_content .= "✅ Phase 27 completed successfully! You can now proceed with:\n\n";
            $report_content .= "1. **Phase 28:** Theme Development & Customization\n";
            $report_content .= "2. **Phase 29:** User Interface Design\n";
            $report_content .= "3. **Phase 30:** Environmental Features Integration\n\n";
            $report_content .= "WordPress is now properly configured and ready for the Environmental Platform development.\n";
        } else {
            $report_content .= "❌ Please resolve the errors listed above before proceeding to the next phase.\n";
        }
        
        file_put_contents('PHASE27_COMPLETION_REPORT.md', $report_content);
        echo "📄 Completion report saved to: PHASE27_COMPLETION_REPORT.md\n\n";
    }
}

// Run verification if accessed directly
if (php_sapi_name() === 'cli' || (isset($_GET['verify']) && $_GET['verify'] === 'true')) {
    $verifier = new Phase27Verification();
    $success = $verifier->run_verification();
    
    if (php_sapi_name() === 'cli') {
        exit($success ? 0 : 1);
    }
} else {
    echo "<p>To run Phase 27 verification, visit: <a href='?verify=true'>Run Verification</a></p>";
}
?>
