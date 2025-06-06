<?php
/**
 * Database Manager for Environmental Analytics
 * Handles database operations and table creation
 */

if (!defined('ABSPATH')) {
    exit;
}

class ENV_Analytics_Database_Manager {
    
    private $wpdb;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }
    
    /**
     * Create all analytics tables
     */
    public function create_tables() {
        $this->create_analytics_events_table();
        $this->create_user_sessions_table();
        $this->create_conversion_goals_table();
        $this->create_conversion_tracking_table();
        $this->create_behavior_analytics_table();
        $this->create_reports_table();
        $this->create_report_subscriptions_table();
    }
    
    /**
     * Create analytics events table
     */
    private function create_analytics_events_table() {
        $table_name = $this->wpdb->prefix . 'env_analytics_events';
        
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned DEFAULT NULL,
            session_id varchar(32) NOT NULL,
            event_type varchar(50) NOT NULL,
            event_category varchar(50) NOT NULL,
            event_action varchar(100) NOT NULL,
            event_label varchar(255) DEFAULT NULL,
            event_value decimal(10,2) DEFAULT NULL,
            page_url varchar(500) DEFAULT NULL,
            page_title varchar(255) DEFAULT NULL,
            referrer varchar(500) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            browser varchar(50) DEFAULT NULL,
            device_type varchar(20) DEFAULT NULL,
            operating_system varchar(50) DEFAULT NULL,
            screen_resolution varchar(20) DEFAULT NULL,
            event_data longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY session_id (session_id),
            KEY event_type (event_type),
            KEY event_category (event_category),
            KEY created_at (created_at),
            KEY user_session (user_id, session_id),
            KEY event_tracking (event_type, event_category, created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create user sessions table
     */
    private function create_user_sessions_table() {
        $table_name = $this->wpdb->prefix . 'env_user_sessions';
        
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            session_id varchar(32) NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            start_time datetime NOT NULL,
            end_time datetime DEFAULT NULL,
            duration int(11) DEFAULT NULL,
            page_views int(11) DEFAULT 0,
            bounce boolean DEFAULT false,
            entry_page varchar(500) DEFAULT NULL,
            exit_page varchar(500) DEFAULT NULL,
            referrer varchar(500) DEFAULT NULL,
            traffic_source varchar(100) DEFAULT NULL,
            campaign varchar(100) DEFAULT NULL,
            medium varchar(50) DEFAULT NULL,
            device_info longtext DEFAULT NULL,
            location_data longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY session_id (session_id),
            KEY user_id (user_id),
            KEY start_time (start_time),
            KEY traffic_source (traffic_source),
            KEY session_duration (start_time, end_time, duration)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create conversion goals table
     */
    private function create_conversion_goals_table() {
        $table_name = $this->wpdb->prefix . 'env_conversion_goals';
        
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text DEFAULT NULL,
            goal_type varchar(50) NOT NULL,
            target_action varchar(100) NOT NULL,
            target_value decimal(10,2) DEFAULT NULL,
            conversion_value decimal(10,2) DEFAULT NULL,
            is_active boolean DEFAULT true,
            created_by bigint(20) unsigned NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY goal_type (goal_type),
            KEY target_action (target_action),
            KEY is_active (is_active),
            KEY created_by (created_by)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create conversion tracking table
     */
    private function create_conversion_tracking_table() {
        $table_name = $this->wpdb->prefix . 'env_conversion_tracking';
        
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            goal_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            session_id varchar(32) NOT NULL,
            conversion_value decimal(10,2) DEFAULT NULL,
            attribution_data longtext DEFAULT NULL,
            conversion_path longtext DEFAULT NULL,
            time_to_conversion int(11) DEFAULT NULL,
            converted_at datetime NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY goal_id (goal_id),
            KEY user_id (user_id),
            KEY session_id (session_id),
            KEY converted_at (converted_at),
            KEY goal_conversion (goal_id, converted_at),
            FOREIGN KEY (goal_id) REFERENCES {$this->wpdb->prefix}env_conversion_goals(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create behavior analytics table
     */
    private function create_behavior_analytics_table() {
        $table_name = $this->wpdb->prefix . 'env_behavior_analytics';
        
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned DEFAULT NULL,
            session_id varchar(32) NOT NULL,
            page_url varchar(500) NOT NULL,
            element_id varchar(100) DEFAULT NULL,
            element_class varchar(100) DEFAULT NULL,
            action_type varchar(50) NOT NULL,
            action_data longtext DEFAULT NULL,
            scroll_depth int(3) DEFAULT NULL,
            time_on_page int(11) DEFAULT NULL,
            click_position varchar(20) DEFAULT NULL,
            viewport_size varchar(20) DEFAULT NULL,
            timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY session_id (session_id),
            KEY page_url (page_url(191)),
            KEY action_type (action_type),
            KEY timestamp (timestamp),
            KEY user_behavior (user_id, action_type, timestamp)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create reports table
     */
    private function create_reports_table() {
        $table_name = $this->wpdb->prefix . 'env_reports';
        
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            report_type varchar(50) NOT NULL,
            report_name varchar(255) NOT NULL,
            report_data longtext NOT NULL,
            date_range varchar(50) NOT NULL,
            start_date date NOT NULL,
            end_date date NOT NULL,
            generated_by bigint(20) unsigned DEFAULT NULL,
            file_path varchar(500) DEFAULT NULL,
            is_automated boolean DEFAULT false,
            status varchar(20) DEFAULT 'completed',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY report_type (report_type),
            KEY date_range (start_date, end_date),
            KEY generated_by (generated_by),
            KEY is_automated (is_automated),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create report subscriptions table
     */
    private function create_report_subscriptions_table() {
        $table_name = $this->wpdb->prefix . 'env_report_subscriptions';
        
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            report_type varchar(50) NOT NULL,
            frequency varchar(20) NOT NULL,
            email varchar(100) NOT NULL,
            is_active boolean DEFAULT true,
            last_sent datetime DEFAULT NULL,
            next_send datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY report_type (report_type),
            KEY frequency (frequency),
            KEY is_active (is_active),
            KEY next_send (next_send)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Insert default conversion goals
     */
    public function insert_default_goals() {
        $goals = array(
            array(
                'name' => 'User Registration',
                'description' => 'Track new user registrations',
                'goal_type' => 'event',
                'target_action' => 'user_registration',
                'conversion_value' => 5.00
            ),
            array(
                'name' => 'Donation Completed',
                'description' => 'Track completed donations',
                'goal_type' => 'event',
                'target_action' => 'donation_completed',
                'conversion_value' => 10.00
            ),
            array(
                'name' => 'Item Exchange',
                'description' => 'Track item exchange completions',
                'goal_type' => 'event',
                'target_action' => 'item_exchange_completed',
                'conversion_value' => 3.00
            ),
            array(
                'name' => 'Petition Signature',
                'description' => 'Track petition signatures',
                'goal_type' => 'event',
                'target_action' => 'petition_signed',
                'conversion_value' => 2.00
            ),
            array(
                'name' => 'Forum Engagement',
                'description' => 'Track forum post creation',
                'goal_type' => 'event',
                'target_action' => 'forum_post_created',
                'conversion_value' => 1.00
            )
        );
        
        $table_name = $this->wpdb->prefix . 'env_conversion_goals';
        
        foreach ($goals as $goal) {
            $existing = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT id FROM $table_name WHERE target_action = %s",
                $goal['target_action']
            ));
            
            if (!$existing) {
                $goal['created_by'] = 1; // Admin user
                $this->wpdb->insert($table_name, $goal);
            }
        }
    }
    
    /**
     * Get analytics data by date range
     */
    public function get_analytics_data($start_date, $end_date, $event_types = array()) {
        $table_name = $this->wpdb->prefix . 'env_analytics_events';
        
        $where_clause = "WHERE created_at >= %s AND created_at <= %s";
        $prepare_values = array($start_date, $end_date);
        
        if (!empty($event_types)) {
            $placeholders = implode(',', array_fill(0, count($event_types), '%s'));
            $where_clause .= " AND event_type IN ($placeholders)";
            $prepare_values = array_merge($prepare_values, $event_types);
        }
        
        $sql = "SELECT * FROM $table_name $where_clause ORDER BY created_at DESC";
        
        return $this->wpdb->get_results($this->wpdb->prepare($sql, $prepare_values));
    }
    
    /**
     * Get conversion data
     */
    public function get_conversion_data($start_date, $end_date, $goal_ids = array()) {
        $table_name = $this->wpdb->prefix . 'env_conversion_tracking';
        $goals_table = $this->wpdb->prefix . 'env_conversion_goals';
        
        $where_clause = "WHERE ct.converted_at >= %s AND ct.converted_at <= %s";
        $prepare_values = array($start_date, $end_date);
        
        if (!empty($goal_ids)) {
            $placeholders = implode(',', array_fill(0, count($goal_ids), '%d'));
            $where_clause .= " AND ct.goal_id IN ($placeholders)";
            $prepare_values = array_merge($prepare_values, $goal_ids);
        }
        
        $sql = "SELECT ct.*, cg.name as goal_name, cg.goal_type 
                FROM $table_name ct 
                JOIN $goals_table cg ON ct.goal_id = cg.id 
                $where_clause 
                ORDER BY ct.converted_at DESC";
        
        return $this->wpdb->get_results($this->wpdb->prepare($sql, $prepare_values));
    }
    
    /**
     * Get user behavior data
     */
    public function get_behavior_data($start_date, $end_date, $action_types = array()) {
        $table_name = $this->wpdb->prefix . 'env_behavior_analytics';
        
        $where_clause = "WHERE timestamp >= %s AND timestamp <= %s";
        $prepare_values = array($start_date, $end_date);
        
        if (!empty($action_types)) {
            $placeholders = implode(',', array_fill(0, count($action_types), '%s'));
            $where_clause .= " AND action_type IN ($placeholders)";
            $prepare_values = array_merge($prepare_values, $action_types);
        }
        
        $sql = "SELECT * FROM $table_name $where_clause ORDER BY timestamp DESC";
        
        return $this->wpdb->get_results($this->wpdb->prepare($sql, $prepare_values));
    }
}
