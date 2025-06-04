<?php
/**
 * Database Management for Environmental Platform Petitions
 * 
 * Handles database operations for petition signatures, verification, and analytics
 * 
 * @package Environmental_Platform_Petitions
 * @since 1.0.0 - Phase 35
 */

if (!defined('ABSPATH')) {
    exit;
}

class EPP_Database {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'maybe_upgrade_database'));
    }
    
    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Petition signatures table
        $signatures_table = $wpdb->prefix . 'petition_signatures';
        $signatures_sql = "CREATE TABLE $signatures_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            petition_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            signer_name varchar(255) NOT NULL,
            signer_email varchar(255) NOT NULL,
            signer_phone varchar(50) DEFAULT NULL,
            signer_location varchar(255) DEFAULT NULL,
            signature_comment text DEFAULT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text DEFAULT NULL,
            is_anonymous tinyint(1) DEFAULT 0,
            is_verified tinyint(1) DEFAULT 0,
            verification_code varchar(32) DEFAULT NULL,
            verification_sent_at datetime DEFAULT NULL,
            verified_at datetime DEFAULT NULL,
            signature_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            status enum('pending','verified','rejected','spam') DEFAULT 'pending',
            source varchar(50) DEFAULT 'website',
            campaign_source varchar(100) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY petition_id (petition_id),
            KEY user_id (user_id),
            KEY signer_email (signer_email),
            KEY status (status),
            KEY signature_date (signature_date),
            UNIQUE KEY unique_signature (petition_id, signer_email)
        ) $charset_collate;";
        
        // Petition analytics table
        $analytics_table = $wpdb->prefix . 'petition_analytics';
        $analytics_sql = "CREATE TABLE $analytics_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            petition_id bigint(20) unsigned NOT NULL,
            event_type varchar(50) NOT NULL,
            event_data longtext DEFAULT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text DEFAULT NULL,
            referrer varchar(500) DEFAULT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            event_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY petition_id (petition_id),
            KEY event_type (event_type),
            KEY event_date (event_date),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        // Petition milestones table
        $milestones_table = $wpdb->prefix . 'petition_milestones';
        $milestones_sql = "CREATE TABLE $milestones_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            petition_id bigint(20) unsigned NOT NULL,
            milestone_type varchar(50) NOT NULL,
            milestone_value int(11) NOT NULL,
            milestone_title varchar(255) NOT NULL,
            milestone_description text DEFAULT NULL,
            reached_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY petition_id (petition_id),
            KEY milestone_type (milestone_type),
            KEY reached_at (reached_at)
        ) $charset_collate;";
        
        // Petition shares table
        $shares_table = $wpdb->prefix . 'petition_shares';
        $shares_sql = "CREATE TABLE $shares_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            petition_id bigint(20) unsigned NOT NULL,
            platform varchar(50) NOT NULL,
            share_count int(11) DEFAULT 1,
            ip_address varchar(45) NOT NULL,
            user_agent text DEFAULT NULL,
            referrer varchar(500) DEFAULT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            shared_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY petition_id (petition_id),
            KEY platform (platform),
            KEY shared_at (shared_at),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        // Petition campaigns table
        $campaigns_table = $wpdb->prefix . 'petition_campaigns';
        $campaigns_sql = "CREATE TABLE $campaigns_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            petition_id bigint(20) unsigned NOT NULL,
            campaign_name varchar(255) NOT NULL,
            campaign_type varchar(50) NOT NULL,
            start_date datetime NOT NULL,
            end_date datetime DEFAULT NULL,
            target_signatures int(11) DEFAULT NULL,
            budget decimal(10,2) DEFAULT NULL,
            spend decimal(10,2) DEFAULT 0.00,
            impressions bigint(20) DEFAULT 0,
            clicks bigint(20) DEFAULT 0,
            conversions bigint(20) DEFAULT 0,
            status enum('draft','active','paused','completed') DEFAULT 'draft',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY petition_id (petition_id),
            KEY campaign_type (campaign_type),
            KEY status (status),
            KEY start_date (start_date)
        ) $charset_collate;";
        
        // Petition updates table
        $updates_table = $wpdb->prefix . 'petition_updates';
        $updates_sql = "CREATE TABLE $updates_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            petition_id bigint(20) unsigned NOT NULL,
            update_title varchar(255) NOT NULL,
            update_content longtext NOT NULL,
            update_type varchar(50) DEFAULT 'general',
            is_milestone tinyint(1) DEFAULT 0,
            milestone_value int(11) DEFAULT NULL,
            author_id bigint(20) unsigned NOT NULL,
            published_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY petition_id (petition_id),
            KEY update_type (update_type),
            KEY published_at (published_at),
            KEY author_id (author_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($signatures_sql);
        dbDelta($analytics_sql);
        dbDelta($milestones_sql);
        dbDelta($shares_sql);
        dbDelta($campaigns_sql);
        dbDelta($updates_sql);
        
        // Set database version
        update_option('epp_database_version', '1.0.0');
    }
    
    /**
     * Check if database needs upgrade
     */
    public function maybe_upgrade_database() {
        $installed_version = get_option('epp_database_version', '0.0.0');
        
        if (version_compare($installed_version, EPP_VERSION, '<')) {
            self::create_tables();
        }
    }
    
    /**
     * Get signature statistics
     */
    public static function get_signature_stats($petition_id = null) {
        global $wpdb;
        
        $signatures_table = $wpdb->prefix . 'petition_signatures';
        
        $where = '';
        $params = array();
        
        if ($petition_id) {
            $where = 'WHERE petition_id = %d';
            $params[] = $petition_id;
        }
        
        $query = "
            SELECT 
                COUNT(*) as total_signatures,
                COUNT(CASE WHEN status = 'verified' THEN 1 END) as verified_signatures,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_signatures,
                COUNT(CASE WHEN is_anonymous = 1 THEN 1 END) as anonymous_signatures,
                COUNT(CASE WHEN DATE(signature_date) = CURDATE() THEN 1 END) as today_signatures,
                COUNT(CASE WHEN signature_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as week_signatures,
                COUNT(CASE WHEN signature_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as month_signatures
            FROM $signatures_table 
            $where
        ";
        
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        
        return $wpdb->get_row($query, ARRAY_A);
    }
    
    /**
     * Get petition analytics data
     */
    public static function get_analytics_data($petition_id, $date_from = null, $date_to = null) {
        global $wpdb;
        
        $analytics_table = $wpdb->prefix . 'petition_analytics';
        
        $where = array('petition_id = %d');
        $params = array($petition_id);
        
        if ($date_from) {
            $where[] = 'event_date >= %s';
            $params[] = $date_from;
        }
        
        if ($date_to) {
            $where[] = 'event_date <= %s';
            $params[] = $date_to;
        }
        
        $where_clause = 'WHERE ' . implode(' AND ', $where);
        
        $query = "
            SELECT 
                event_type,
                COUNT(*) as event_count,
                DATE(event_date) as event_date
            FROM $analytics_table 
            $where_clause
            GROUP BY event_type, DATE(event_date)
            ORDER BY event_date DESC
        ";
        
        $query = $wpdb->prepare($query, $params);
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Get signature growth data
     */
    public static function get_signature_growth($petition_id, $days = 30) {
        global $wpdb;
        
        $signatures_table = $wpdb->prefix . 'petition_signatures';
        
        $query = "
            SELECT 
                DATE(signature_date) as date,
                COUNT(*) as daily_signatures,
                SUM(COUNT(*)) OVER (ORDER BY DATE(signature_date)) as cumulative_signatures
            FROM $signatures_table 
            WHERE petition_id = %d 
                AND signature_date >= DATE_SUB(NOW(), INTERVAL %d DAY)
                AND status != 'spam'
            GROUP BY DATE(signature_date)
            ORDER BY date ASC
        ";
        
        return $wpdb->get_results(
            $wpdb->prepare($query, $petition_id, $days),
            ARRAY_A
        );
    }
    
    /**
     * Get recent signatures for a petition
     */
    public static function get_recent_signatures($petition_id, $limit = 10, $verified_only = false) {
        global $wpdb;
        
        $signatures_table = $wpdb->prefix . 'petition_signatures';
        
        $where = array('petition_id = %d');
        $params = array($petition_id);
        
        if ($verified_only) {
            $where[] = 'status = %s';
            $params[] = 'verified';
        }
        
        $where_clause = 'WHERE ' . implode(' AND ', $where);
        
        $query = "
            SELECT 
                id,
                signer_name,
                signer_location,
                signature_comment,
                signature_date,
                is_anonymous,
                status
            FROM $signatures_table 
            $where_clause
            ORDER BY signature_date DESC
            LIMIT %d
        ";
        
        $params[] = $limit;
        
        return $wpdb->get_results(
            $wpdb->prepare($query, $params),
            ARRAY_A
        );
    }
    
    /**
     * Get signature count by location
     */
    public static function get_signatures_by_location($petition_id) {
        global $wpdb;
        
        $signatures_table = $wpdb->prefix . 'petition_signatures';
        
        $query = "
            SELECT 
                signer_location,
                COUNT(*) as signature_count
            FROM $signatures_table 
            WHERE petition_id = %d 
                AND signer_location IS NOT NULL 
                AND signer_location != ''
                AND status != 'spam'
            GROUP BY signer_location
            ORDER BY signature_count DESC
            LIMIT 20
        ";
        
        return $wpdb->get_results(
            $wpdb->prepare($query, $petition_id),
            ARRAY_A
        );
    }
    
    /**
     * Clean up old unverified signatures
     */
    public static function cleanup_old_signatures() {
        global $wpdb;
        
        $signatures_table = $wpdb->prefix . 'petition_signatures';
        
        // Delete unverified signatures older than 30 days
        $query = "
            DELETE FROM $signatures_table 
            WHERE status = 'pending' 
                AND signature_date < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ";
        
        return $wpdb->query($query);
    }
    
    /**
     * Get petition milestone data
     */
    public static function get_milestones($petition_id) {
        global $wpdb;
        
        $milestones_table = $wpdb->prefix . 'petition_milestones';
        
        $query = "
            SELECT *
            FROM $milestones_table 
            WHERE petition_id = %d
            ORDER BY milestone_value ASC
        ";
        
        return $wpdb->get_results(
            $wpdb->prepare($query, $petition_id),
            ARRAY_A
        );
    }
    
    /**
     * Track petition event
     */
    public static function track_event($petition_id, $event_type, $event_data = null) {
        global $wpdb;
        
        $analytics_table = $wpdb->prefix . 'petition_analytics';
        
        return $wpdb->insert(
            $analytics_table,
            array(
                'petition_id' => $petition_id,
                'event_type' => $event_type,
                'event_data' => $event_data ? json_encode($event_data) : null,
                'ip_address' => self::get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
                'user_id' => get_current_user_id() ?: null,
                'event_date' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
        );
    }
    
    /**
     * Get client IP address
     */
    private static function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
