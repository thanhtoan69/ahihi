<?php
/**
 * Environmental Social Viral Database Manager
 * 
 * Handles database operations for social sharing and viral features
 */

class Environmental_Social_Viral_Database {
    
    private $wpdb;
    private $tables;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        // Define table names
        $this->tables = array(
            'shares' => $wpdb->prefix . 'env_social_shares',
            'viral_metrics' => $wpdb->prefix . 'env_viral_metrics',
            'referrals' => $wpdb->prefix . 'env_referrals',
            'referral_rewards' => $wpdb->prefix . 'env_referral_rewards',
            'viral_content' => $wpdb->prefix . 'env_viral_content',
            'share_analytics' => $wpdb->prefix . 'env_share_analytics',
            'social_campaigns' => $wpdb->prefix . 'env_social_campaigns',
            'viral_coefficients' => $wpdb->prefix . 'env_viral_coefficients'
        );
    }
    
    /**
     * Initialize database
     */
    public function init() {
        $this->check_database_version();
    }
    
    /**
     * Check and update database version
     */
    private function check_database_version() {
        $installed_version = get_option('env_social_viral_db_version', '0.0.0');
        $current_version = '1.0.0';
        
        if (version_compare($installed_version, $current_version, '<')) {
            $this->create_tables();
            update_option('env_social_viral_db_version', $current_version);
        }
    }
    
    /**
     * Create database tables
     */
    public function create_tables() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Social shares table
        $sql_shares = "CREATE TABLE IF NOT EXISTS {$this->tables['shares']} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            content_id bigint(20) NOT NULL,
            content_type varchar(50) NOT NULL DEFAULT 'post',
            platform varchar(50) NOT NULL,
            share_url text NOT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            referrer_url text DEFAULT NULL,
            click_count int(11) DEFAULT 0,
            conversion_count int(11) DEFAULT 0,
            share_data longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_content (user_id, content_id),
            KEY idx_platform_date (platform, created_at),
            KEY idx_content_type (content_type, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        // Viral metrics table
        $sql_viral_metrics = "CREATE TABLE IF NOT EXISTS {$this->tables['viral_metrics']} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            content_id bigint(20) NOT NULL,
            content_type varchar(50) NOT NULL DEFAULT 'post',
            metric_type varchar(50) NOT NULL,
            metric_value decimal(10,4) NOT NULL DEFAULT 0,
            calculation_date date NOT NULL,
            period_type varchar(20) NOT NULL DEFAULT 'daily',
            additional_data longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_content_metric_date (content_id, metric_type, calculation_date),
            KEY idx_metric_type_date (metric_type, calculation_date),
            KEY idx_content_type (content_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        // Referrals table
        $sql_referrals = "CREATE TABLE IF NOT EXISTS {$this->tables['referrals']} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            referrer_id bigint(20) NOT NULL,
            referee_id bigint(20) DEFAULT NULL,
            referral_code varchar(50) NOT NULL,
            source_url text DEFAULT NULL,
            landing_page text DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            conversion_type varchar(50) DEFAULT NULL,
            conversion_value decimal(10,2) DEFAULT 0,
            visit_count int(11) DEFAULT 1,
            first_visit_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            conversion_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_referral_code (referral_code),
            KEY idx_referrer_status (referrer_id, status),
            KEY idx_referee_status (referee_id, status),
            KEY idx_conversion_date (conversion_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        // Referral rewards table
        $sql_referral_rewards = "CREATE TABLE IF NOT EXISTS {$this->tables['referral_rewards']} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            referral_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            reward_type varchar(50) NOT NULL,
            reward_amount decimal(10,2) NOT NULL,
            reward_currency varchar(10) DEFAULT 'points',
            status varchar(20) NOT NULL DEFAULT 'pending',
            processed_at datetime DEFAULT NULL,
            expires_at datetime DEFAULT NULL,
            metadata longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_referral_user (referral_id, user_id),
            KEY idx_user_status (user_id, status),
            KEY idx_reward_type (reward_type),
            KEY idx_expiration (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        // Viral content table
        $sql_viral_content = "CREATE TABLE IF NOT EXISTS {$this->tables['viral_content']} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            content_id bigint(20) NOT NULL,
            content_type varchar(50) NOT NULL DEFAULT 'post',
            viral_score decimal(10,4) NOT NULL DEFAULT 0,
            share_count int(11) NOT NULL DEFAULT 0,
            click_count int(11) NOT NULL DEFAULT 0,
            conversion_count int(11) NOT NULL DEFAULT 0,
            engagement_rate decimal(5,4) NOT NULL DEFAULT 0,
            viral_coefficient decimal(5,4) NOT NULL DEFAULT 0,
            trending_score decimal(10,4) NOT NULL DEFAULT 0,
            peak_viral_date datetime DEFAULT NULL,
            last_viral_activity datetime DEFAULT NULL,
            platforms_data longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_content_unique (content_id, content_type),
            KEY idx_viral_score (viral_score DESC),
            KEY idx_trending_score (trending_score DESC),
            KEY idx_viral_coefficient (viral_coefficient DESC),
            KEY idx_last_activity (last_viral_activity)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        // Share analytics table
        $sql_share_analytics = "CREATE TABLE IF NOT EXISTS {$this->tables['share_analytics']} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            content_id bigint(20) NOT NULL,
            content_type varchar(50) NOT NULL DEFAULT 'post',
            platform varchar(50) NOT NULL,
            analytics_date date NOT NULL,
            share_count int(11) NOT NULL DEFAULT 0,
            click_count int(11) NOT NULL DEFAULT 0,
            impression_count int(11) NOT NULL DEFAULT 0,
            engagement_count int(11) NOT NULL DEFAULT 0,
            conversion_count int(11) NOT NULL DEFAULT 0,
            reach_count int(11) NOT NULL DEFAULT 0,
            demographics_data longtext DEFAULT NULL,
            performance_data longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_content_platform_date (content_id, platform, analytics_date),
            KEY idx_platform_date (platform, analytics_date),
            KEY idx_content_date (content_id, analytics_date),
            KEY idx_performance (share_count, click_count, conversion_count)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        // Social campaigns table
        $sql_social_campaigns = "CREATE TABLE IF NOT EXISTS {$this->tables['social_campaigns']} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text DEFAULT NULL,
            campaign_type varchar(50) NOT NULL DEFAULT 'referral',
            status varchar(20) NOT NULL DEFAULT 'active',
            start_date datetime NOT NULL,
            end_date datetime DEFAULT NULL,
            target_metrics longtext DEFAULT NULL,
            reward_structure longtext DEFAULT NULL,
            tracking_parameters longtext DEFAULT NULL,
            performance_data longtext DEFAULT NULL,
            created_by bigint(20) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_status_dates (status, start_date, end_date),
            KEY idx_campaign_type (campaign_type),
            KEY idx_created_by (created_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        // Viral coefficients table
        $sql_viral_coefficients = "CREATE TABLE IF NOT EXISTS {$this->tables['viral_coefficients']} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            content_id bigint(20) NOT NULL,
            content_type varchar(50) NOT NULL DEFAULT 'post',
            platform varchar(50) NOT NULL,
            coefficient_type varchar(50) NOT NULL DEFAULT 'standard',
            coefficient_value decimal(8,6) NOT NULL DEFAULT 0,
            calculation_period varchar(20) NOT NULL DEFAULT 'daily',
            sample_size int(11) NOT NULL DEFAULT 0,
            confidence_level decimal(5,4) NOT NULL DEFAULT 0,
            calculation_date datetime NOT NULL,
            factors_data longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_content_platform_type_date (content_id, platform, coefficient_type, calculation_date),
            KEY idx_coefficient_value (coefficient_value DESC),
            KEY idx_calculation_date (calculation_date),
            KEY idx_platform_type (platform, coefficient_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        // Execute table creation
        dbDelta($sql_shares);
        dbDelta($sql_viral_metrics);
        dbDelta($sql_referrals);
        dbDelta($sql_referral_rewards);
        dbDelta($sql_viral_content);
        dbDelta($sql_share_analytics);
        dbDelta($sql_social_campaigns);
        dbDelta($sql_viral_coefficients);
        
        // Create indexes for better performance
        $this->create_additional_indexes();
        
        // Insert default data
        $this->insert_default_data();
    }
    
    /**
     * Create additional indexes for performance
     */
    private function create_additional_indexes() {
        $indexes = array(
            // Composite indexes for common queries
            "CREATE INDEX IF NOT EXISTS idx_shares_user_platform_date ON {$this->tables['shares']} (user_id, platform, created_at)",
            "CREATE INDEX IF NOT EXISTS idx_viral_content_score_date ON {$this->tables['viral_content']} (viral_score, created_at)",
            "CREATE INDEX IF NOT EXISTS idx_referrals_status_date ON {$this->tables['referrals']} (status, created_at)",
            "CREATE INDEX IF NOT EXISTS idx_analytics_platform_performance ON {$this->tables['share_analytics']} (platform, share_count, analytics_date)",
            
            // Full-text indexes for search
            "ALTER TABLE {$this->tables['social_campaigns']} ADD FULLTEXT(name, description)",
        );
        
        foreach ($indexes as $index_sql) {
            $this->wpdb->query($index_sql);
        }
    }
    
    /**
     * Insert default data
     */
    private function insert_default_data() {
        // Insert default social campaigns
        $default_campaigns = array(
            array(
                'name' => 'Default Referral Campaign',
                'description' => 'Standard referral program for environmental platform',
                'campaign_type' => 'referral',
                'status' => 'active',
                'start_date' => current_time('mysql'),
                'target_metrics' => json_encode(array(
                    'referral_rate' => 0.05,
                    'conversion_rate' => 0.1,
                    'viral_coefficient' => 0.3
                )),
                'reward_structure' => json_encode(array(
                    'referrer_reward' => 10,
                    'referee_reward' => 5,
                    'reward_type' => 'points'
                )),
                'tracking_parameters' => json_encode(array(
                    'utm_source' => 'referral',
                    'utm_medium' => 'social',
                    'utm_campaign' => 'default_referral'
                )),
                'created_by' => 1
            ),
            array(
                'name' => 'Viral Content Boost',
                'description' => 'Campaign to boost viral content sharing',
                'campaign_type' => 'viral_boost',
                'status' => 'active',
                'start_date' => current_time('mysql'),
                'target_metrics' => json_encode(array(
                    'share_rate' => 0.15,
                    'engagement_rate' => 0.05,
                    'viral_coefficient' => 0.5
                )),
                'reward_structure' => json_encode(array(
                    'share_reward' => 2,
                    'viral_bonus' => 5,
                    'reward_type' => 'points'
                )),
                'created_by' => 1
            )
        );
        
        foreach ($default_campaigns as $campaign) {
            $existing = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT id FROM {$this->tables['social_campaigns']} WHERE name = %s",
                    $campaign['name']
                )
            );
            
            if (!$existing) {
                $this->wpdb->insert($this->tables['social_campaigns'], $campaign);
            }
        }
    }
    
    /**
     * Drop all plugin tables
     */
    public function drop_tables() {
        foreach ($this->tables as $table) {
            $this->wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
    }
    
    /**
     * Get table name
     */
    public function get_table_name($table_key) {
        return isset($this->tables[$table_key]) ? $this->tables[$table_key] : null;
    }
    
    /**
     * Get all table names
     */
    public function get_all_tables() {
        return $this->tables;
    }
    
    /**
     * Optimize database tables
     */
    public function optimize_tables() {
        foreach ($this->tables as $table) {
            $this->wpdb->query("OPTIMIZE TABLE {$table}");
        }
    }
    
    /**
     * Clean up old data based on retention settings
     */
    public function cleanup_old_data($retention_days = 365) {
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        
        // Clean up old analytics data
        $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->tables['share_analytics']} WHERE analytics_date < %s",
                date('Y-m-d', strtotime("-{$retention_days} days"))
            )
        );
        
        // Clean up old share data (keep only last year)
        $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->tables['shares']} WHERE created_at < %s AND click_count = 0",
                $cutoff_date
            )
        );
        
        // Clean up expired referral rewards
        $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->tables['referral_rewards']} WHERE expires_at < %s AND status = 'expired'",
                current_time('mysql')
            )
        );
        
        // Clean up old viral metrics
        $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->tables['viral_metrics']} WHERE created_at < %s",
                $cutoff_date
            )
        );
    }
    
    /**
     * Get database statistics
     */
    public function get_database_stats() {
        $stats = array();
        
        foreach ($this->tables as $key => $table) {
            $count = $this->wpdb->get_var("SELECT COUNT(*) FROM {$table}");
            $size = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'size_mb' 
                     FROM information_schema.TABLES 
                     WHERE table_schema = %s AND table_name = %s",
                    DB_NAME,
                    $table
                )
            );
            
            $stats[$key] = array(
                'table_name' => $table,
                'row_count' => $count,
                'size_mb' => $size
            );
        }
        
        return $stats;
    }
    
    /**
     * Backup plugin data
     */
    public function backup_data() {
        $backup_data = array();
        
        foreach ($this->tables as $key => $table) {
            $data = $this->wpdb->get_results("SELECT * FROM {$table}", ARRAY_A);
            $backup_data[$key] = $data;
        }
        
        $backup_file = wp_upload_dir()['basedir'] . '/environmental-social-viral/backup_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($backup_file, json_encode($backup_data, JSON_PRETTY_PRINT));
        
        return $backup_file;
    }
    
    /**
     * Restore plugin data from backup
     */
    public function restore_data($backup_file) {
        if (!file_exists($backup_file)) {
            return false;
        }
        
        $backup_data = json_decode(file_get_contents($backup_file), true);
        
        if (!$backup_data) {
            return false;
        }
        
        // Truncate existing tables
        foreach ($this->tables as $table) {
            $this->wpdb->query("TRUNCATE TABLE {$table}");
        }
        
        // Restore data
        foreach ($backup_data as $key => $data) {
            if (isset($this->tables[$key])) {
                $table = $this->tables[$key];
                
                foreach ($data as $row) {
                    $this->wpdb->insert($table, $row);
                }
            }
        }
        
        return true;
    }
    
    /**
     * Validate table integrity
     */
    public function validate_table_integrity() {
        $issues = array();
        
        foreach ($this->tables as $key => $table) {
            // Check if table exists
            $table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$table}'");
            
            if (!$table_exists) {
                $issues[] = "Table {$table} does not exist";
                continue;
            }
            
            // Check table structure
            $check_result = $this->wpdb->get_results("CHECK TABLE {$table}");
            
            foreach ($check_result as $result) {
                if ($result->Msg_type === 'error') {
                    $issues[] = "Table {$table}: {$result->Msg_text}";
                }
            }
        }
        
        return empty($issues) ? true : $issues;
    }
}
