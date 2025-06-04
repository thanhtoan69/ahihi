<?php
/**
 * Database Setup and Migration Script
 * 
 * @package EnvironmentalItemExchange
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Environmental Item Exchange Database Setup
 */
class EIE_Database_Setup {
    
    /**
     * Run database setup
     */
    public static function setup() {
        self::create_tables();
        self::setup_default_data();
        self::update_version();
    }
    
    /**
     * Create all necessary database tables
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Conversations table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}eie_conversations (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            exchange_id bigint(20) NOT NULL,
            user1_id bigint(20) NOT NULL,
            user2_id bigint(20) NOT NULL,
            status varchar(20) DEFAULT 'active',
            last_message_id bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY exchange_id (exchange_id),
            KEY user1_id (user1_id),
            KEY user2_id (user2_id),
            KEY status (status),
            UNIQUE KEY unique_conversation (exchange_id, user1_id, user2_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Messages table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}eie_messages (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            conversation_id bigint(20) NOT NULL,
            exchange_id bigint(20) NOT NULL,
            sender_id bigint(20) NOT NULL,
            receiver_id bigint(20) NOT NULL,
            message_type varchar(20) DEFAULT 'text',
            content longtext NOT NULL,
            attachments longtext,
            read_status tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY conversation_id (conversation_id),
            KEY exchange_id (exchange_id),
            KEY sender_id (sender_id),
            KEY receiver_id (receiver_id),
            KEY read_status (read_status)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Ratings table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}eie_ratings (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            exchange_id bigint(20) NOT NULL,
            rater_id bigint(20) NOT NULL,
            rated_user_id bigint(20) NOT NULL,
            rating int(1) NOT NULL CHECK (rating >= 1 AND rating <= 5),
            review text,
            transaction_type varchar(50),
            item_quality_rating int(1),
            communication_rating int(1),
            reliability_rating int(1),
            helpful_votes int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY exchange_id (exchange_id),
            KEY rated_user_id (rated_user_id),
            KEY rating (rating),
            UNIQUE KEY unique_rating (exchange_id, rater_id)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // User saved exchanges table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}eie_saved_exchanges (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            exchange_id bigint(20) NOT NULL,
            saved_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY exchange_id (exchange_id),
            UNIQUE KEY unique_save (user_id, exchange_id)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Geolocation table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}eie_locations (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            exchange_id bigint(20) NOT NULL,
            latitude decimal(10,8) NOT NULL,
            longitude decimal(11,8) NOT NULL,
            address text,
            city varchar(100),
            state varchar(100),
            country varchar(100),
            postal_code varchar(20),
            radius_km int(11) DEFAULT 5,
            delivery_available tinyint(1) DEFAULT 0,
            pickup_available tinyint(1) DEFAULT 1,
            shipping_available tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY exchange_id (exchange_id),
            KEY location (latitude, longitude),
            KEY city_state (city, state)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Analytics table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}eie_analytics (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            exchange_id bigint(20),
            user_id bigint(20),
            action_type varchar(50) NOT NULL,
            action_data longtext,
            ip_address varchar(45),
            user_agent text,
            session_id varchar(100),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY exchange_id (exchange_id),
            KEY user_id (user_id),
            KEY action_type (action_type),
            KEY created_at (created_at),
            KEY session_id (session_id)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // User activity log
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}eie_user_activity (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            activity_type varchar(50) NOT NULL,
            activity_data longtext,
            exchange_id bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY activity_type (activity_type),
            KEY exchange_id (exchange_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Setup default data and options
     */
    private static function setup_default_data() {
        // Default plugin options
        $defaults = array(
            'eie_enable_geolocation' => true,
            'eie_enable_messaging' => true,
            'eie_enable_ratings' => true,
            'eie_enable_notifications' => true,
            'eie_default_radius' => 10,
            'eie_max_images' => 10,
            'eie_auto_expire_days' => 30,
            'eie_require_verification' => false,
            'eie_enable_social_sharing' => true,
            'eie_enable_mobile_app' => true,
            'eie_google_maps_api_key' => '',
            'eie_email_notifications' => true,
            'eie_push_notifications' => false,
            'eie_moderation_enabled' => true,
            'eie_auto_approve_exchanges' => true,
            'eie_minimum_rating_threshold' => 3,
            'eie_max_contact_attempts' => 5,
            'eie_conversation_timeout_days' => 7,
        );
        
        foreach ($defaults as $option => $value) {
            if (get_option($option) === false) {
                update_option($option, $value);
            }
        }
        
        // Create default exchange categories if none exist
        if (!term_exists('Electronics', 'exchange_category')) {
            wp_insert_term('Electronics', 'exchange_category', array(
                'description' => 'Electronic devices and gadgets',
                'slug' => 'electronics'
            ));
        }
        
        if (!term_exists('Clothing', 'exchange_category')) {
            wp_insert_term('Clothing', 'exchange_category', array(
                'description' => 'Clothing and accessories',
                'slug' => 'clothing'
            ));
        }
        
        if (!term_exists('Books', 'exchange_category')) {
            wp_insert_term('Books', 'exchange_category', array(
                'description' => 'Books and educational materials',
                'slug' => 'books'
            ));
        }
        
        if (!term_exists('Home & Garden', 'exchange_category')) {
            wp_insert_term('Home & Garden', 'exchange_category', array(
                'description' => 'Home and garden items',
                'slug' => 'home-garden'
            ));
        }
        
        // Create default exchange types if none exist
        if (!term_exists('Trade', 'exchange_type')) {
            wp_insert_term('Trade', 'exchange_type', array(
                'description' => 'Trade one item for another',
                'slug' => 'trade'
            ));
        }
        
        if (!term_exists('Give Away', 'exchange_type')) {
            wp_insert_term('Give Away', 'exchange_type', array(
                'description' => 'Give away items for free',
                'slug' => 'give-away'
            ));
        }
        
        if (!term_exists('Borrow', 'exchange_type')) {
            wp_insert_term('Borrow', 'exchange_type', array(
                'description' => 'Borrow items temporarily',
                'slug' => 'borrow'
            ));
        }
        
        if (!term_exists('Sell', 'exchange_type')) {
            wp_insert_term('Sell', 'exchange_type', array(
                'description' => 'Sell items at low prices',
                'slug' => 'sell'
            ));
        }
    }
    
    /**
     * Update database version
     */
    private static function update_version() {
        update_option('eie_db_version', EIE_PLUGIN_VERSION);
        update_option('eie_db_setup_complete', true);
    }
    
    /**
     * Check if database setup is needed
     */
    public static function needs_setup() {
        $current_version = get_option('eie_db_version', '0.0.0');
        return version_compare($current_version, EIE_PLUGIN_VERSION, '<');
    }
    
    /**
     * Run database cleanup for uninstall
     */
    public static function cleanup() {
        global $wpdb;
        
        // Drop custom tables
        $tables = array(
            'eie_conversations',
            'eie_messages',
            'eie_ratings',
            'eie_saved_exchanges',
            'eie_locations',
            'eie_analytics',
            'eie_user_activity'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
        }
        
        // Remove plugin options
        $options = array(
            'eie_enable_geolocation',
            'eie_enable_messaging',
            'eie_enable_ratings',
            'eie_enable_notifications',
            'eie_default_radius',
            'eie_max_images',
            'eie_auto_expire_days',
            'eie_require_verification',
            'eie_enable_social_sharing',
            'eie_enable_mobile_app',
            'eie_google_maps_api_key',
            'eie_email_notifications',
            'eie_push_notifications',
            'eie_moderation_enabled',
            'eie_auto_approve_exchanges',
            'eie_minimum_rating_threshold',
            'eie_max_contact_attempts',
            'eie_conversation_timeout_days',
            'eie_db_version',
            'eie_db_setup_complete'
        );
        
        foreach ($options as $option) {
            delete_option($option);
        }
    }
}
