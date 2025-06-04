<?php
/**
 * Environmental Platform Database Version Control
 * Handles database versioning and update mechanisms
 * Phase 28: Custom Database Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class EP_Database_Version_Control {
    
    private $wpdb;
    private $current_version = '1.0.0';
    private $version_history = array();
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        add_action('plugins_loaded', array($this, 'check_version'));
        add_action('wp_ajax_ep_update_database', array($this, 'update_database'));
        add_action('wp_ajax_ep_rollback_database', array($this, 'rollback_database'));
        
        $this->init_version_history();
    }
    
    /**
     * Initialize version history with update scripts
     */
    private function init_version_history() {
        $this->version_history = array(
            '1.0.0' => array(
                'description' => 'Initial database setup with WordPress integration',
                'updates' => array(
                    'create_wp_integration_tables',
                    'create_custom_meta_tables',
                    'setup_initial_configuration'
                ),
                'rollback' => array(
                    'drop_wp_integration_tables',
                    'cleanup_initial_configuration'
                )
            ),
            '1.1.0' => array(
                'description' => 'Enhanced environmental data tracking',
                'updates' => array(
                    'add_environmental_tracking_columns',
                    'create_carbon_tracking_views',
                    'update_user_level_system'
                ),
                'rollback' => array(
                    'remove_environmental_tracking_columns',
                    'drop_carbon_tracking_views'
                )
            ),
            '1.2.0' => array(
                'description' => 'Advanced gamification features',
                'updates' => array(
                    'create_achievement_system_tables',
                    'add_streak_tracking',
                    'setup_points_calculation'
                ),
                'rollback' => array(
                    'drop_achievement_system_tables',
                    'remove_streak_tracking'
                )
            ),
            '1.3.0' => array(
                'description' => 'E-commerce integration improvements',
                'updates' => array(
                    'enhance_product_sustainability',
                    'add_carbon_offset_tracking',
                    'create_eco_certification_system'
                ),
                'rollback' => array(
                    'revert_product_sustainability',
                    'remove_carbon_offset_tracking'
                )
            ),
            '1.4.0' => array(
                'description' => 'Community features enhancement',
                'updates' => array(
                    'improve_forum_structure',
                    'add_moderation_system',
                    'create_reputation_tracking'
                ),
                'rollback' => array(
                    'revert_forum_structure',
                    'remove_moderation_system'
                )
            ),
            '1.5.0' => array(
                'description' => 'AI and machine learning integration',
                'updates' => array(
                    'add_ai_classification_tables',
                    'create_ml_training_data_structure',
                    'setup_prediction_tracking'
                ),
                'rollback' => array(
                    'drop_ai_classification_tables',
                    'remove_ml_training_data'
                )
            )
        );
    }
    
    /**
     * Check current database version and update if needed
     */
    public function check_version() {
        $installed_version = get_option('ep_database_version', '0.0.0');
        
        if (version_compare($installed_version, $this->current_version, '<')) {
            $this->auto_update_database($installed_version, $this->current_version);
        }
    }
    
    /**
     * Automatically update database to current version
     */
    private function auto_update_database($from_version, $to_version) {
        try {
            $this->log_version_update("Starting automatic database update from {$from_version} to {$to_version}");
            
            foreach ($this->version_history as $version => $config) {
                if (version_compare($version, $from_version, '>') && 
                    version_compare($version, $to_version, '<=')) {
                    
                    $this->apply_version_updates($version, $config);
                }
            }
            
            update_option('ep_database_version', $to_version);
            $this->log_version_update("Database successfully updated to version {$to_version}");
            
        } catch (Exception $e) {
            $this->log_version_update("Database update failed: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * Apply updates for a specific version
     */
    private function apply_version_updates($version, $config) {
        $this->log_version_update("Applying updates for version {$version}: {$config['description']}");
        
        foreach ($config['updates'] as $update_method) {
            if (method_exists($this, $update_method)) {
                $this->log_version_update("Running update: {$update_method}");
                $this->$update_method();
            } else {
                $this->log_version_update("Update method not found: {$update_method}", 'warning');
            }
        }
    }
    
    /**
     * Manual database update via AJAX
     */
    public function update_database() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        check_ajax_referer('ep_database_update', 'nonce');
        
        $target_version = sanitize_text_field($_POST['target_version'] ?? $this->current_version);
        $current_version = get_option('ep_database_version', '0.0.0');
        
        try {
            $this->auto_update_database($current_version, $target_version);
            
            wp_send_json_success(array(
                'message' => sprintf(__('Database updated successfully from %s to %s.', 'environmental-platform-core'), $current_version, $target_version),
                'new_version' => $target_version
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Database update failed: ', 'environmental-platform-core') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Rollback database to previous version
     */
    public function rollback_database() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        check_ajax_referer('ep_database_rollback', 'nonce');
        
        $target_version = sanitize_text_field($_POST['target_version']);
        $current_version = get_option('ep_database_version', '0.0.0');
        
        if (version_compare($target_version, $current_version, '>=')) {
            wp_send_json_error(array(
                'message' => __('Cannot rollback to same or higher version.', 'environmental-platform-core')
            ));
            return;
        }
        
        try {
            $this->rollback_to_version($current_version, $target_version);
            
            wp_send_json_success(array(
                'message' => sprintf(__('Database rolled back successfully from %s to %s.', 'environmental-platform-core'), $current_version, $target_version),
                'new_version' => $target_version
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Database rollback failed: ', 'environmental-platform-core') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Rollback database to specific version
     */
    private function rollback_to_version($from_version, $to_version) {
        $this->log_version_update("Starting database rollback from {$from_version} to {$to_version}");
        
        // Apply rollback scripts in reverse order
        $versions_to_rollback = array();
        foreach ($this->version_history as $version => $config) {
            if (version_compare($version, $to_version, '>') && 
                version_compare($version, $from_version, '<=')) {
                $versions_to_rollback[] = array('version' => $version, 'config' => $config);
            }
        }
        
        // Reverse the array to rollback in correct order
        $versions_to_rollback = array_reverse($versions_to_rollback);
        
        foreach ($versions_to_rollback as $version_data) {
            $this->apply_version_rollback($version_data['version'], $version_data['config']);
        }
        
        update_option('ep_database_version', $to_version);
        $this->log_version_update("Database successfully rolled back to version {$to_version}");
    }
    
    /**
     * Apply rollback for a specific version
     */
    private function apply_version_rollback($version, $config) {
        $this->log_version_update("Rolling back version {$version}: {$config['description']}");
        
        if (isset($config['rollback'])) {
            foreach ($config['rollback'] as $rollback_method) {
                if (method_exists($this, $rollback_method)) {
                    $this->log_version_update("Running rollback: {$rollback_method}");
                    $this->$rollback_method();
                } else {
                    $this->log_version_update("Rollback method not found: {$rollback_method}", 'warning');
                }
            }
        }
    }
    
    // Version 1.0.0 Updates
    private function create_wp_integration_tables() {
        // This would be handled by the database manager
        EP_Database_Manager::get_instance();
    }
    
    private function create_custom_meta_tables() {
        // Custom meta tables creation logic
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}ep_version_log (
            log_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            version_from VARCHAR(20) NOT NULL,
            version_to VARCHAR(20) NOT NULL,
            update_type ENUM('update', 'rollback') NOT NULL,
            update_status ENUM('success', 'failed') NOT NULL,
            update_log LONGTEXT NULL,
            update_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (log_id),
            KEY idx_version_to (version_to),
            KEY idx_update_type (update_type),
            KEY idx_update_date (update_date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    private function setup_initial_configuration() {
        // Set default configuration values
        $default_config = array(
            'ep_sync_interval' => '3600', // 1 hour
            'ep_batch_size' => '100',
            'ep_auto_sync' => 'yes',
            'ep_debug_mode' => 'no',
            'ep_carbon_tracking' => 'yes',
            'ep_gamification' => 'yes'
        );
        
        foreach ($default_config as $key => $value) {
            if (!get_option($key)) {
                update_option($key, $value);
            }
        }
    }
    
    // Version 1.1.0 Updates
    private function add_environmental_tracking_columns() {
        $tables_to_update = array(
            $this->wpdb->prefix . 'ep_postmeta' => array(
                'air_quality_impact' => 'DECIMAL(5,2) DEFAULT NULL',
                'water_quality_impact' => 'DECIMAL(5,2) DEFAULT NULL',
                'biodiversity_impact' => 'DECIMAL(5,2) DEFAULT NULL'
            ),
            $this->wpdb->prefix . 'ep_usermeta' => array(
                'weekly_carbon_kg' => 'DECIMAL(8,3) DEFAULT NULL',
                'monthly_carbon_kg' => 'DECIMAL(10,3) DEFAULT NULL',
                'yearly_carbon_kg' => 'DECIMAL(12,3) DEFAULT NULL'
            )
        );
        
        foreach ($tables_to_update as $table => $columns) {
            foreach ($columns as $column => $definition) {
                $this->wpdb->query("ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS {$column} {$definition}");
            }
        }
    }
    
    private function create_carbon_tracking_views() {
        $sql = "CREATE VIEW IF NOT EXISTS ep_user_carbon_summary AS
                SELECT 
                    user_id,
                    SUM(CASE WHEN meta_key = 'ep_weekly_carbon_kg' THEN meta_value ELSE 0 END) as weekly_total,
                    SUM(CASE WHEN meta_key = 'ep_monthly_carbon_kg' THEN meta_value ELSE 0 END) as monthly_total,
                    SUM(CASE WHEN meta_key = 'ep_yearly_carbon_kg' THEN meta_value ELSE 0 END) as yearly_total
                FROM {$this->wpdb->prefix}ep_usermeta 
                WHERE meta_key IN ('ep_weekly_carbon_kg', 'ep_monthly_carbon_kg', 'ep_yearly_carbon_kg')
                GROUP BY user_id";
        
        $this->wpdb->query($sql);
    }
    
    private function update_user_level_system() {
        // Update user level calculation based on new environmental metrics
        $this->wpdb->query("
            UPDATE {$this->wpdb->usermeta} um1
            JOIN (
                SELECT user_id, 
                       FLOOR((green_points + environmental_score * 10) / 1000) + 1 as new_level
                FROM {$this->wpdb->prefix}ep_usermeta 
                WHERE meta_key = 'ep_green_points'
            ) calc ON um1.user_id = calc.user_id
            SET um1.meta_value = calc.new_level
            WHERE um1.meta_key = 'ep_user_level'
        ");
    }
    
    // Rollback methods for version 1.0.0
    private function drop_wp_integration_tables() {
        $tables = array(
            $this->wpdb->prefix . 'ep_data_mapping',
            $this->wpdb->prefix . 'ep_sync_log',
            $this->wpdb->prefix . 'ep_config',
            $this->wpdb->prefix . 'ep_version_log'
        );
        
        foreach ($tables as $table) {
            $this->wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
    }
    
    private function cleanup_initial_configuration() {
        $config_keys = array(
            'ep_sync_interval',
            'ep_batch_size', 
            'ep_auto_sync',
            'ep_debug_mode',
            'ep_carbon_tracking',
            'ep_gamification'
        );
        
        foreach ($config_keys as $key) {
            delete_option($key);
        }
    }
    
    /**
     * Get current database version
     */
    public function get_current_version() {
        return get_option('ep_database_version', '0.0.0');
    }
    
    /**
     * Get latest available version
     */
    public function get_latest_version() {
        return $this->current_version;
    }
    
    /**
     * Get version history
     */
    public function get_version_history() {
        return $this->version_history;
    }
    
    /**
     * Check if update is available
     */
    public function is_update_available() {
        $current = $this->get_current_version();
        $latest = $this->get_latest_version();
        
        return version_compare($current, $latest, '<');
    }
    
    /**
     * Get update log
     */
    public function get_update_log($limit = 50) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}ep_version_log 
                ORDER BY update_date DESC LIMIT %d",
                $limit
            ),
            ARRAY_A
        );
    }
    
    /**
     * Log version update
     */
    private function log_version_update($message, $level = 'info') {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'level' => $level,
            'message' => $message
        );
        
        // Store in transient for current session
        $current_log = get_transient('ep_version_update_log') ?: array();
        $current_log[] = $log_entry;
        set_transient('ep_version_update_log', $current_log, HOUR_IN_SECONDS);
        
        // Also log to error log if it's an error
        if ($level === 'error') {
            error_log("EP Version Control Error: " . $message);
        }
    }
    
    /**
     * Create database backup before major updates
     */
    public function create_backup($version) {
        // This would integrate with a backup plugin or create a simple backup
        $backup_dir = WP_CONTENT_DIR . '/ep-backups/';
        
        if (!file_exists($backup_dir)) {
            wp_mkdir_p($backup_dir);
        }
        
        $backup_file = $backup_dir . 'ep_backup_' . $version . '_' . date('Y-m-d_H-i-s') . '.sql';
        
        // Simple backup of EP tables (in production, use proper backup tools)
        $tables = $this->get_ep_tables();
        $backup_content = "-- Environmental Platform Database Backup\n";
        $backup_content .= "-- Version: {$version}\n";
        $backup_content .= "-- Date: " . current_time('mysql') . "\n\n";
        
        foreach ($tables as $table) {
            $backup_content .= "-- Table: {$table}\n";
            $backup_content .= $this->get_table_structure($table) . "\n";
            $backup_content .= $this->get_table_data($table) . "\n\n";
        }
        
        file_put_contents($backup_file, $backup_content);
        
        return $backup_file;
    }
    
    /**
     * Get list of EP-related tables
     */
    private function get_ep_tables() {
        $tables = $this->wpdb->get_col("SHOW TABLES LIKE '{$this->wpdb->prefix}ep_%'");
        return $tables;
    }
    
    /**
     * Get table structure for backup
     */
    private function get_table_structure($table) {
        $result = $this->wpdb->get_row("SHOW CREATE TABLE {$table}", ARRAY_N);
        return $result[1] . ";\n";
    }
    
    /**
     * Get table data for backup
     */
    private function get_table_data($table) {
        $data = $this->wpdb->get_results("SELECT * FROM {$table}", ARRAY_A);
        $sql = "";
        
        if (!empty($data)) {
            $columns = array_keys($data[0]);
            $sql .= "INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES\n";
            
            $values = array();
            foreach ($data as $row) {
                $row_values = array();
                foreach ($row as $value) {
                    $row_values[] = $this->wpdb->prepare('%s', $value);
                }
                $values[] = "(" . implode(', ', $row_values) . ")";
            }
            
            $sql .= implode(",\n", $values) . ";\n";
        }
        
        return $sql;
    }
}

// Initialize version control
new EP_Database_Version_Control();
