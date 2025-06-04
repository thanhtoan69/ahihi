<?php
/**
 * Environmental Platform Database Manager
 * Handles custom database integration and WordPress compatibility
 * Phase 28: Custom Database Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class EP_Database_Manager {
    
    private static $instance = null;
    private $wpdb;
    private $custom_tables = array();
    private $db_version = '1.0.0';
    private $table_prefix = '';
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_prefix = $wpdb->prefix;
        $this->init_custom_tables();
        
        add_action('plugins_loaded', array($this, 'check_database_version'));
        add_action('wp_ajax_ep_sync_database', array($this, 'sync_database'));
        add_action('wp_ajax_ep_migrate_data', array($this, 'migrate_data'));
    }
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize custom table mapping
     */
    private function init_custom_tables() {
        $this->custom_tables = array(
            // User Management Tables
            'users' => array(
                'wp_equivalent' => 'users',
                'sync_fields' => array('user_email', 'user_login', 'display_name'),
                'custom_fields' => array('green_points', 'user_level', 'carbon_footprint_kg', 'location_lat', 'location_lng')
            ),
            'user_sessions' => array(
                'wp_equivalent' => 'usermeta',
                'sync_fields' => array('user_id', 'session_token', 'ip_address'),
                'custom_fields' => array('device_info', 'location', 'last_activity')
            ),
            'user_preferences' => array(
                'wp_equivalent' => 'usermeta',
                'sync_fields' => array('user_id'),
                'custom_fields' => array('theme_preference', 'notification_preferences', 'privacy_settings')
            ),
            
            // Content Management Tables
            'articles' => array(
                'wp_equivalent' => 'posts',
                'sync_fields' => array('title', 'content', 'excerpt', 'status'),
                'custom_fields' => array('environmental_impact_score', 'sustainability_rating', 'carbon_impact_kg')
            ),
            'categories' => array(
                'wp_equivalent' => 'terms',
                'sync_fields' => array('name', 'slug', 'description'),
                'custom_fields' => array('environmental_category', 'impact_weight')
            ),
            'events' => array(
                'wp_equivalent' => 'posts',
                'post_type' => 'environmental_event',
                'sync_fields' => array('title', 'description', 'location', 'start_time', 'end_time'),
                'custom_fields' => array('max_participants', 'environmental_impact', 'carbon_saved_kg')
            ),
            
            // Environmental Data Tables
            'environmental_data' => array(
                'wp_equivalent' => 'postmeta',
                'sync_fields' => array('data_type', 'value', 'unit', 'timestamp'),
                'custom_fields' => array('source_id', 'location_lat', 'location_lng', 'quality_score')
            ),
            'carbon_footprints' => array(
                'wp_equivalent' => 'usermeta',
                'sync_fields' => array('user_id', 'total_carbon_kg', 'month', 'year'),
                'custom_fields' => array('transportation_kg', 'energy_kg', 'food_kg', 'waste_kg')
            ),
            'waste_items' => array(
                'wp_equivalent' => 'posts',
                'post_type' => 'waste_classification',
                'sync_fields' => array('item_name', 'description', 'category_id'),
                'custom_fields' => array('ai_confidence', 'recyclable', 'environmental_impact')
            ),
            
            // E-commerce & Marketplace Tables
            'products' => array(
                'wp_equivalent' => 'posts',
                'post_type' => 'product',
                'sync_fields' => array('name', 'description', 'price', 'status'),
                'custom_fields' => array('sustainability_score', 'carbon_footprint_kg', 'eco_certifications')
            ),
            'orders' => array(
                'wp_equivalent' => 'posts',
                'post_type' => 'shop_order',
                'sync_fields' => array('user_id', 'total_amount', 'order_status', 'order_date'),
                'custom_fields' => array('carbon_offset_kg', 'green_points_earned', 'eco_packaging')
            ),
            
            // Community & Social Tables
            'forums' => array(
                'wp_equivalent' => 'posts',
                'post_type' => 'forum',
                'sync_fields' => array('name', 'description', 'status'),
                'custom_fields' => array('environmental_focus', 'moderator_id', 'post_count')
            ),
            'forum_topics' => array(
                'wp_equivalent' => 'posts',
                'post_type' => 'topic',
                'sync_fields' => array('title', 'content', 'author_id', 'forum_id'),
                'custom_fields' => array('environmental_category', 'solution_status', 'impact_score')
            ),
            
            // Gamification Tables
            'achievements' => array(
                'wp_equivalent' => 'posts',
                'post_type' => 'achievement',
                'sync_fields' => array('name', 'description', 'badge_icon', 'points_required'),
                'custom_fields' => array('achievement_type', 'environmental_category', 'rarity_level')
            ),
            'user_achievements' => array(
                'wp_equivalent' => 'usermeta',
                'sync_fields' => array('user_id', 'achievement_id', 'earned_date'),
                'custom_fields' => array('progress_percentage', 'points_earned', 'environmental_impact')
            ),
            
            // Analytics Tables
            'user_activities_comprehensive' => array(
                'wp_equivalent' => 'usermeta',
                'sync_fields' => array('user_id', 'activity_type', 'activity_description'),
                'custom_fields' => array('environmental_score', 'carbon_impact_kg', 'points_earned')
            )
        );
    }
    
    /**
     * Check if database version needs updating
     */
    public function check_database_version() {
        $installed_version = get_option('ep_database_version', '0.0.0');
        
        if (version_compare($installed_version, $this->db_version, '<')) {
            $this->upgrade_database($installed_version);
            update_option('ep_database_version', $this->db_version);
        }
    }
    
    /**
     * Upgrade database to current version
     */
    private function upgrade_database($from_version) {
        if (version_compare($from_version, '1.0.0', '<')) {
            $this->create_wp_integration_tables();
            $this->add_custom_meta_tables();
        }
    }
    
    /**
     * Create WordPress integration tables
     */
    private function create_wp_integration_tables() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        // Custom table for mapping between WordPress and environmental platform data
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_prefix}ep_data_mapping (
            mapping_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            wp_post_id BIGINT(20) UNSIGNED NULL,
            wp_user_id BIGINT(20) UNSIGNED NULL,
            ep_table_name VARCHAR(64) NOT NULL,
            ep_record_id BIGINT(20) UNSIGNED NOT NULL,
            mapping_type ENUM('post', 'user', 'meta', 'taxonomy') NOT NULL,
            sync_status ENUM('synced', 'pending', 'error') DEFAULT 'pending',
            last_sync TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (mapping_id),
            KEY idx_wp_post_id (wp_post_id),
            KEY idx_wp_user_id (wp_user_id),
            KEY idx_ep_table_record (ep_table_name, ep_record_id),
            KEY idx_sync_status (sync_status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Environmental platform sync log
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_prefix}ep_sync_log (
            log_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            sync_type ENUM('import', 'export', 'bidirectional') NOT NULL,
            table_name VARCHAR(64) NOT NULL,
            records_processed INT(11) DEFAULT 0,
            records_success INT(11) DEFAULT 0,
            records_error INT(11) DEFAULT 0,
            error_details TEXT NULL,
            sync_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            sync_end TIMESTAMP NULL,
            status ENUM('running', 'completed', 'failed') DEFAULT 'running',
            PRIMARY KEY (log_id),
            KEY idx_sync_type (sync_type),
            KEY idx_table_name (table_name),
            KEY idx_status (status)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Environmental platform configuration
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_prefix}ep_config (
            config_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            config_key VARCHAR(255) NOT NULL,
            config_value LONGTEXT NULL,
            config_type ENUM('string', 'number', 'boolean', 'json', 'array') DEFAULT 'string',
            autoload ENUM('yes', 'no') DEFAULT 'yes',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (config_id),
            UNIQUE KEY config_key (config_key),
            KEY idx_autoload (autoload)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Add custom meta tables for environmental data
     */
    private function add_custom_meta_tables() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        // Environmental post meta
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_prefix}ep_postmeta (
            meta_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            meta_key VARCHAR(255) DEFAULT NULL,
            meta_value LONGTEXT DEFAULT NULL,
            environmental_score DECIMAL(5,2) DEFAULT NULL,
            carbon_impact_kg DECIMAL(10,3) DEFAULT NULL,
            sustainability_rating TINYINT(1) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (meta_id),
            KEY post_id (post_id),
            KEY meta_key (meta_key(191)),
            KEY environmental_score (environmental_score),
            KEY carbon_impact (carbon_impact_kg)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Environmental user meta
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_prefix}ep_usermeta (
            umeta_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            meta_key VARCHAR(255) DEFAULT NULL,
            meta_value LONGTEXT DEFAULT NULL,
            green_points INT(11) DEFAULT 0,
            user_level TINYINT(2) DEFAULT 1,
            carbon_footprint_kg DECIMAL(10,3) DEFAULT NULL,
            environmental_score DECIMAL(5,2) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (umeta_id),
            KEY user_id (user_id),
            KEY meta_key (meta_key(191)),
            KEY green_points (green_points),
            KEY user_level (user_level)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Sync data between WordPress and Environmental Platform tables
     */
    public function sync_database() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        check_ajax_referer('ep_sync_database', 'nonce');
        
        $sync_type = sanitize_text_field($_POST['sync_type'] ?? 'bidirectional');
        $table_name = sanitize_text_field($_POST['table_name'] ?? '');
        
        $log_id = $this->start_sync_log($sync_type, $table_name);
        
        try {
            if (empty($table_name)) {
                // Sync all tables
                foreach ($this->custom_tables as $table => $config) {
                    $this->sync_table($table, $config, $sync_type);
                }
            } else {
                // Sync specific table
                if (isset($this->custom_tables[$table_name])) {
                    $this->sync_table($table_name, $this->custom_tables[$table_name], $sync_type);
                }
            }
            
            $this->complete_sync_log($log_id, 'completed');
            
            wp_send_json_success(array(
                'message' => __('Database sync completed successfully.', 'environmental-platform-core'),
                'log_id' => $log_id
            ));
            
        } catch (Exception $e) {
            $this->complete_sync_log($log_id, 'failed', $e->getMessage());
            
            wp_send_json_error(array(
                'message' => __('Database sync failed: ', 'environmental-platform-core') . $e->getMessage(),
                'log_id' => $log_id
            ));
        }
    }
    
    /**
     * Sync individual table data
     */
    private function sync_table($table_name, $config, $sync_type) {
        $wp_table = $config['wp_equivalent'];
        $records_processed = 0;
        $records_success = 0;
        $records_error = 0;
        
        if ($sync_type === 'import' || $sync_type === 'bidirectional') {
            // Import from environmental platform to WordPress
            $this->import_from_ep_table($table_name, $config);
        }
        
        if ($sync_type === 'export' || $sync_type === 'bidirectional') {
            // Export from WordPress to environmental platform
            $this->export_to_ep_table($table_name, $config);
        }
    }
    
    /**
     * Import data from environmental platform table to WordPress
     */
    private function import_from_ep_table($table_name, $config) {
        $wp_equivalent = $config['wp_equivalent'];
        $sync_fields = $config['sync_fields'];
        $custom_fields = $config['custom_fields'] ?? array();
        
        // Get data from environmental platform table
        $ep_data = $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT * FROM %i WHERE updated_at > %s", 
                $table_name, 
                $this->get_last_sync_time($table_name)
            ), 
            ARRAY_A
        );
        
        foreach ($ep_data as $record) {
            try {
                if ($wp_equivalent === 'posts') {
                    $this->import_as_post($record, $config);
                } elseif ($wp_equivalent === 'users') {
                    $this->import_as_user($record, $config);
                } elseif ($wp_equivalent === 'terms') {
                    $this->import_as_term($record, $config);
                } elseif ($wp_equivalent === 'usermeta' || $wp_equivalent === 'postmeta') {
                    $this->import_as_meta($record, $config);
                }
                
                // Record successful mapping
                $this->record_data_mapping($wp_equivalent, $record, 'synced');
                
            } catch (Exception $e) {
                error_log("EP Sync Error for {$table_name}: " . $e->getMessage());
                $this->record_data_mapping($wp_equivalent, $record, 'error');
            }
        }
    }
    
    /**
     * Import record as WordPress post
     */
    private function import_as_post($record, $config) {
        $post_data = array(
            'post_title' => $record['title'] ?? $record['name'] ?? '',
            'post_content' => $record['content'] ?? $record['description'] ?? '',
            'post_excerpt' => $record['excerpt'] ?? '',
            'post_status' => $this->map_status($record['status'] ?? 'active'),
            'post_type' => $config['post_type'] ?? 'post',
            'post_author' => $record['user_id'] ?? $record['author_id'] ?? 1,
            'post_date' => $record['created_at'] ?? current_time('mysql'),
            'meta_input' => array()
        );
        
        // Add custom fields as meta
        if (isset($config['custom_fields'])) {
            foreach ($config['custom_fields'] as $field) {
                if (isset($record[$field])) {
                    $post_data['meta_input']['ep_' . $field] = $record[$field];
                }
            }
        }
        
        // Add environmental platform ID
        $post_data['meta_input']['ep_original_id'] = $record['id'] ?? $record[array_keys($record)[0]];
        $post_data['meta_input']['ep_table_name'] = $config['table_name'] ?? '';
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            throw new Exception($post_id->get_error_message());
        }
        
        return $post_id;
    }
    
    /**
     * Import record as WordPress user
     */
    private function import_as_user($record, $config) {
        $user_data = array(
            'user_login' => $record['username'] ?? $record['email'],
            'user_email' => $record['email'],
            'display_name' => $record['full_name'] ?? $record['display_name'] ?? $record['username'],
            'user_registered' => $record['created_at'] ?? current_time('mysql'),
            'role' => $this->map_user_role($record['user_type'] ?? 'subscriber')
        );
        
        // Check if user already exists
        $existing_user = get_user_by('email', $user_data['user_email']);
        if ($existing_user) {
            $user_id = $existing_user->ID;
            wp_update_user(array_merge(array('ID' => $user_id), $user_data));
        } else {
            $user_data['user_pass'] = wp_generate_password();
            $user_id = wp_insert_user($user_data);
            
            if (is_wp_error($user_id)) {
                throw new Exception($user_id->get_error_message());
            }
        }
        
        // Add custom user meta
        if (isset($config['custom_fields'])) {
            foreach ($config['custom_fields'] as $field) {
                if (isset($record[$field])) {
                    update_user_meta($user_id, 'ep_' . $field, $record[$field]);
                }
            }
        }
        
        // Add environmental platform user ID
        update_user_meta($user_id, 'ep_original_id', $record['user_id'] ?? $record['id']);
        
        return $user_id;
    }
    
    /**
     * Get last sync time for a table
     */
    private function get_last_sync_time($table_name) {
        $last_sync = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT MAX(sync_end) FROM {$this->table_prefix}ep_sync_log 
                WHERE table_name = %s AND status = 'completed'",
                $table_name
            )
        );
        
        return $last_sync ?: '1970-01-01 00:00:00';
    }
    
    /**
     * Start sync log entry
     */
    private function start_sync_log($sync_type, $table_name) {
        $this->wpdb->insert(
            $this->table_prefix . 'ep_sync_log',
            array(
                'sync_type' => $sync_type,
                'table_name' => $table_name,
                'status' => 'running',
                'sync_start' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s')
        );
        
        return $this->wpdb->insert_id;
    }
    
    /**
     * Complete sync log entry
     */
    private function complete_sync_log($log_id, $status, $error_details = null) {
        $this->wpdb->update(
            $this->table_prefix . 'ep_sync_log',
            array(
                'status' => $status,
                'sync_end' => current_time('mysql'),
                'error_details' => $error_details
            ),
            array('log_id' => $log_id),
            array('%s', '%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * Record data mapping between WordPress and EP tables
     */
    private function record_data_mapping($wp_equivalent, $record, $status) {
        $this->wpdb->insert(
            $this->table_prefix . 'ep_data_mapping',
            array(
                'ep_table_name' => $record['table_name'] ?? '',
                'ep_record_id' => $record['id'] ?? 0,
                'mapping_type' => $this->get_mapping_type($wp_equivalent),
                'sync_status' => $status
            ),
            array('%s', '%d', '%s', '%s')
        );
    }
    
    /**
     * Map environmental platform status to WordPress status
     */
    private function map_status($ep_status) {
        $status_map = array(
            'active' => 'publish',
            'inactive' => 'draft',
            'pending' => 'pending',
            'archived' => 'private'
        );
        
        return $status_map[$ep_status] ?? 'draft';
    }
    
    /**
     * Map environmental platform user type to WordPress role
     */
    private function map_user_role($user_type) {
        $role_map = array(
            'admin' => 'administrator',
            'moderator' => 'editor',
            'premium_user' => 'author',
            'regular_user' => 'subscriber',
            'eco_expert' => 'contributor'
        );
        
        return $role_map[$user_type] ?? 'subscriber';
    }
    
    /**
     * Get mapping type based on WordPress table
     */
    private function get_mapping_type($wp_table) {
        $type_map = array(
            'posts' => 'post',
            'users' => 'user',
            'postmeta' => 'meta',
            'usermeta' => 'meta',
            'terms' => 'taxonomy'
        );
        
        return $type_map[$wp_table] ?? 'post';
    }
    
    /**
     * Get custom table configuration
     */
    public function get_table_config($table_name) {
        return $this->custom_tables[$table_name] ?? null;
    }
    
    /**
     * Get all custom tables
     */
    public function get_all_tables() {
        return $this->custom_tables;
    }
    
    /**
     * Check if table exists in environmental platform database
     */
    public function table_exists($table_name) {
        $result = $this->wpdb->get_var(
            $this->wpdb->prepare("SHOW TABLES LIKE %s", $table_name)
        );
        
        return !empty($result);
    }
    
    /**
     * Get sync statistics
     */
    public function get_sync_stats() {
        $stats = $this->wpdb->get_results(
            "SELECT 
                table_name,
                COUNT(*) as total_syncs,
                SUM(records_success) as total_success,
                SUM(records_error) as total_errors,
                MAX(sync_end) as last_sync
            FROM {$this->table_prefix}ep_sync_log 
            WHERE status = 'completed'
            GROUP BY table_name",
            ARRAY_A
        );
        
        return $stats;
    }
    
    /**
     * Clean up old sync logs (older than 30 days)
     */
    public function cleanup_sync_logs() {
        $this->wpdb->query(
            "DELETE FROM {$this->table_prefix}ep_sync_log 
            WHERE sync_start < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
    }
}

// Initialize the database manager
EP_Database_Manager::get_instance();
