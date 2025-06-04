<?php
/**
 * Phase 28 Verification Script: Custom Database Integration
 * Verifies all database integration components are properly implemented
 */

// WordPress environment check
if (!defined('ABSPATH')) {
    // If not in WordPress, try to load it
    $wp_config_path = dirname(__FILE__) . '/../../../../wp-config.php';
    if (file_exists($wp_config_path)) {
        require_once $wp_config_path;
    } else {
        die("WordPress environment not found. Please run this script from WordPress admin or ensure wp-config.php is accessible.\n");
    }
}

class Phase28Verification {
    
    private $results = array();
    private $plugin_dir;
    
    public function __construct() {
        $this->plugin_dir = WP_PLUGIN_DIR . '/environmental-platform-core';
    }
    
    public function run_verification() {
        echo "<h1>Phase 28: Custom Database Integration - Verification Report</h1>\n";
        echo "<style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1, h2 { color: #333; }
            .pass { color: #28a745; font-weight: bold; }
            .fail { color: #dc3545; font-weight: bold; }
            .warning { color: #ffc107; font-weight: bold; }
            .info { color: #17a2b8; }
            table { border-collapse: collapse; width: 100%; margin: 20px 0; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .result-summary { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
            pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
        </style>\n";

        // Run all verification checks
        $this->verify_database_classes();
        $this->verify_admin_templates();
        $this->verify_plugin_integration();
        $this->verify_database_tables();
        $this->verify_ajax_handlers();
        $this->verify_wordpress_integration();
        
        // Display summary
        $this->display_summary();
        
        return $this->results;
    }
    
    private function verify_database_classes() {
        echo "<h2>1. Database Integration Classes</h2>\n";
        echo "<table>\n";
        echo "<tr><th>Component</th><th>Status</th><th>Details</th></tr>\n";
        
        $classes = array(
            'EP_Database_Manager' => 'includes/class-database-manager.php',
            'EP_Database_Migration' => 'includes/class-database-migration.php',
            'EP_Database_Version_Control' => 'includes/class-database-version-control.php'
        );
        
        foreach ($classes as $class_name => $file_path) {
            $full_path = $this->plugin_dir . '/' . $file_path;
            $exists = file_exists($full_path);
            $class_exists = class_exists($class_name);
            
            if ($exists && $class_exists) {
                echo "<tr><td>{$class_name}</td><td class='pass'>✓ PASS</td><td>File exists and class is loaded</td></tr>\n";
                $this->results[$class_name] = 'pass';
            } elseif ($exists && !$class_exists) {
                echo "<tr><td>{$class_name}</td><td class='warning'>⚠ WARNING</td><td>File exists but class not loaded</td></tr>\n";
                $this->results[$class_name] = 'warning';
            } else {
                echo "<tr><td>{$class_name}</td><td class='fail'>✗ FAIL</td><td>File missing: {$file_path}</td></tr>\n";
                $this->results[$class_name] = 'fail';
            }
        }
        
        echo "</table>\n";
    }
    
    private function verify_admin_templates() {
        echo "<h2>2. Admin Page Templates</h2>\n";
        echo "<table>\n";
        echo "<tr><th>Template</th><th>Status</th><th>Size</th><th>Details</th></tr>\n";
        
        $templates = array(
            'Database Manager' => 'admin/database-manager.php',
            'Migration Page' => 'admin/migration.php',
            'Version Control' => 'admin/version-control.php'
        );
        
        foreach ($templates as $name => $file_path) {
            $full_path = $this->plugin_dir . '/' . $file_path;
            $exists = file_exists($full_path);
            
            if ($exists) {
                $size = filesize($full_path);
                $readable = $this->format_bytes($size);
                echo "<tr><td>{$name}</td><td class='pass'>✓ PASS</td><td>{$readable}</td><td>Template file exists</td></tr>\n";
                $this->results["template_{$name}"] = 'pass';
            } else {
                echo "<tr><td>{$name}</td><td class='fail'>✗ FAIL</td><td>-</td><td>Template missing: {$file_path}</td></tr>\n";
                $this->results["template_{$name}"] = 'fail';
            }
        }
        
        echo "</table>\n";
    }
    
    private function verify_plugin_integration() {
        echo "<h2>3. WordPress Plugin Integration</h2>\n";
        echo "<table>\n";
        echo "<tr><th>Integration Check</th><th>Status</th><th>Details</th></tr>\n";
        
        // Check if plugin is active
        $active_plugins = get_option('active_plugins');
        $plugin_active = in_array('environmental-platform-core/environmental-platform-core.php', $active_plugins);
        
        if ($plugin_active) {
            echo "<tr><td>Plugin Activation</td><td class='pass'>✓ PASS</td><td>Environmental Platform Core plugin is active</td></tr>\n";
            $this->results['plugin_active'] = 'pass';
        } else {
            echo "<tr><td>Plugin Activation</td><td class='fail'>✗ FAIL</td><td>Plugin is not active</td></tr>\n";
            $this->results['plugin_active'] = 'fail';
        }
        
        // Check admin menu registration
        global $menu, $submenu;
        $ep_menu_exists = false;
        foreach ($menu as $menu_item) {
            if (isset($menu_item[2]) && $menu_item[2] === 'environmental-platform') {
                $ep_menu_exists = true;
                break;
            }
        }
        
        if ($ep_menu_exists) {
            echo "<tr><td>Admin Menu</td><td class='pass'>✓ PASS</td><td>Environmental Platform admin menu is registered</td></tr>\n";
            $this->results['admin_menu'] = 'pass';
        } else {
            echo "<tr><td>Admin Menu</td><td class='fail'>✗ FAIL</td><td>Admin menu not found</td></tr>\n";
            $this->results['admin_menu'] = 'fail';
        }
        
        // Check database integration submenu items
        $expected_submenus = array('ep-database', 'ep-migration', 'ep-versions');
        $existing_submenus = isset($submenu['environmental-platform']) ? array_column($submenu['environmental-platform'], 2) : array();
        
        foreach ($expected_submenus as $submenu_slug) {
            if (in_array($submenu_slug, $existing_submenus)) {
                echo "<tr><td>Submenu: {$submenu_slug}</td><td class='pass'>✓ PASS</td><td>Submenu item registered</td></tr>\n";
                $this->results["submenu_{$submenu_slug}"] = 'pass';
            } else {
                echo "<tr><td>Submenu: {$submenu_slug}</td><td class='fail'>✗ FAIL</td><td>Submenu item not found</td></tr>\n";
                $this->results["submenu_{$submenu_slug}"] = 'fail';
            }
        }
        
        echo "</table>\n";
    }
    
    private function verify_database_tables() {
        echo "<h2>4. Database Connection & Tables</h2>\n";
        echo "<table>\n";
        echo "<tr><th>Database Check</th><th>Status</th><th>Details</th></tr>\n";
        
        global $wpdb;
        
        // Check WordPress database connection
        $wp_connection = $wpdb->check_connection();
        if ($wp_connection) {
            echo "<tr><td>WordPress Database</td><td class='pass'>✓ PASS</td><td>Connection successful</td></tr>\n";
            $this->results['wp_db_connection'] = 'pass';
        } else {
            echo "<tr><td>WordPress Database</td><td class='fail'>✗ FAIL</td><td>Connection failed</td></tr>\n";
            $this->results['wp_db_connection'] = 'fail';
        }
        
        // Check if environmental_platform database exists
        $ep_db_exists = $wpdb->get_var("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'environmental_platform'");
        if ($ep_db_exists) {
            echo "<tr><td>Environmental Platform Database</td><td class='pass'>✓ PASS</td><td>Database exists</td></tr>\n";
            $this->results['ep_db_exists'] = 'pass';
            
            // Count tables in environmental_platform database
            $table_count = $wpdb->get_var("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'environmental_platform'");
            echo "<tr><td>EP Database Tables</td><td class='info'>ℹ INFO</td><td>{$table_count} tables found</td></tr>\n";
            $this->results['ep_table_count'] = $table_count;
        } else {
            echo "<tr><td>Environmental Platform Database</td><td class='fail'>✗ FAIL</td><td>Database not found</td></tr>\n";
            $this->results['ep_db_exists'] = 'fail';
        }
        
        echo "</table>\n";
    }
    
    private function verify_ajax_handlers() {
        echo "<h2>5. AJAX Action Handlers</h2>\n";
        echo "<table>\n";
        echo "<tr><th>AJAX Action</th><th>Status</th><th>Handler Class</th></tr>\n";
        
        $ajax_actions = array(
            'ep_full_sync' => 'EP_Database_Manager',
            'ep_selective_sync' => 'EP_Database_Manager', 
            'ep_sync_table' => 'EP_Database_Manager',
            'ep_run_migration' => 'EP_Database_Migration',
            'ep_check_migration_status' => 'EP_Database_Migration',
            'ep_rollback_migration' => 'EP_Database_Migration',
            'ep_update_database' => 'EP_Database_Version_Control',
            'ep_rollback_database' => 'EP_Database_Version_Control'
        );
        
        foreach ($ajax_actions as $action => $class) {
            $has_action = has_action("wp_ajax_{$action}");
            if ($has_action) {
                echo "<tr><td>{$action}</td><td class='pass'>✓ PASS</td><td>{$class}</td></tr>\n";
                $this->results["ajax_{$action}"] = 'pass';
            } else {
                echo "<tr><td>{$action}</td><td class='fail'>✗ FAIL</td><td>Handler not registered</td></tr>\n";
                $this->results["ajax_{$action}"] = 'fail';
            }
        }
        
        echo "</table>\n";
    }
    
    private function verify_wordpress_integration() {
        echo "<h2>6. WordPress Integration Features</h2>\n";
        echo "<table>\n";
        echo "<tr><th>Feature</th><th>Status</th><th>Details</th></tr>\n";
        
        // Check options
        $ep_version = get_option('ep_database_version');
        if ($ep_version) {
            echo "<tr><td>Database Version Option</td><td class='pass'>✓ PASS</td><td>Version: {$ep_version}</td></tr>\n";
            $this->results['ep_version_option'] = 'pass';
        } else {
            echo "<tr><td>Database Version Option</td><td class='warning'>⚠ WARNING</td><td>No version set</td></tr>\n";
            $this->results['ep_version_option'] = 'warning';
        }
        
        // Check if database integration is initialized
        if (class_exists('EP_Database_Manager')) {
            $manager = EP_Database_Manager::get_instance();
            if ($manager) {
                echo "<tr><td>Database Manager Instance</td><td class='pass'>✓ PASS</td><td>Singleton instance created</td></tr>\n";
                $this->results['db_manager_instance'] = 'pass';
            } else {
                echo "<tr><td>Database Manager Instance</td><td class='fail'>✗ FAIL</td><td>Failed to get instance</td></tr>\n";
                $this->results['db_manager_instance'] = 'fail';
            }
        }
        
        // Check plugin constants
        $constants = array('EP_CORE_VERSION', 'EP_CORE_PLUGIN_DIR', 'EP_CORE_PLUGIN_URL');
        foreach ($constants as $constant) {
            if (defined($constant)) {
                $value = constant($constant);
                echo "<tr><td>Constant: {$constant}</td><td class='pass'>✓ PASS</td><td>{$value}</td></tr>\n";
                $this->results["constant_{$constant}"] = 'pass';
            } else {
                echo "<tr><td>Constant: {$constant}</td><td class='fail'>✗ FAIL</td><td>Not defined</td></tr>\n";
                $this->results["constant_{$constant}"] = 'fail';
            }
        }
        
        echo "</table>\n";
    }
    
    private function display_summary() {
        $total_checks = count($this->results);
        $passed = count(array_filter($this->results, function($result) { return $result === 'pass'; }));
        $warnings = count(array_filter($this->results, function($result) { return $result === 'warning'; }));
        $failed = count(array_filter($this->results, function($result) { return $result === 'fail'; }));
        
        $pass_rate = $total_checks > 0 ? round(($passed / $total_checks) * 100, 1) : 0;
        
        echo "<div class='result-summary'>\n";
        echo "<h2>Phase 28 Verification Summary</h2>\n";
        echo "<p><strong>Total Checks:</strong> {$total_checks}</p>\n";
        echo "<p><strong>Passed:</strong> <span class='pass'>{$passed}</span></p>\n";
        echo "<p><strong>Warnings:</strong> <span class='warning'>{$warnings}</span></p>\n";
        echo "<p><strong>Failed:</strong> <span class='fail'>{$failed}</span></p>\n";
        echo "<p><strong>Pass Rate:</strong> {$pass_rate}%</p>\n";
        
        if ($pass_rate >= 90) {
            echo "<p class='pass'><strong>✓ Phase 28 COMPLETED SUCCESSFULLY</strong></p>\n";
            echo "<p>Custom Database Integration is properly implemented with all core components working.</p>\n";
        } elseif ($pass_rate >= 75) {
            echo "<p class='warning'><strong>⚠ Phase 28 MOSTLY COMPLETE</strong></p>\n";
            echo "<p>Most components are working but some issues need attention.</p>\n";
        } else {
            echo "<p class='fail'><strong>✗ Phase 28 NEEDS WORK</strong></p>\n";
            echo "<p>Significant issues found that need to be resolved.</p>\n";
        }
        
        echo "</div>\n";
        
        // Detailed results for debugging
        echo "<h2>Detailed Results (for debugging)</h2>\n";
        echo "<pre>\n";
        print_r($this->results);
        echo "</pre>\n";
    }
    
    private function format_bytes($size, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB');
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        return round($size, $precision) . ' ' . $units[$i];
    }
}

// Run verification if accessed directly
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    $verifier = new Phase28Verification();
    $verifier->run_verification();
}
?>
