<?php
/**
 * Database Setup Class
 * 
 * Handles database table creation, updates, and schema management
 * for the Environmental Donation System plugin.
 * 
 * @package EnvironmentalDonationSystem
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EDS_Database_Setup {
    
    /**
     * Database version
     */
    const DB_VERSION = '1.0.0';
    
    /**
     * Initialize database setup
     */
    public function __construct() {
        add_action('init', array($this, 'maybe_upgrade_database'));
    }
    
    /**
     * Create all database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Organizations table
        $organizations_table = $wpdb->prefix . 'donation_organizations';
        $organizations_sql = "CREATE TABLE $organizations_table (
            organization_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            organization_name varchar(255) NOT NULL,
            organization_slug varchar(255) NOT NULL UNIQUE,
            organization_type varchar(50) NOT NULL,
            registration_number varchar(100) DEFAULT NULL UNIQUE,
            contact_email varchar(255) NOT NULL,
            contact_phone varchar(20) DEFAULT NULL,
            website_url varchar(500) DEFAULT NULL,
            address text DEFAULT NULL,
            description longtext DEFAULT NULL,
            mission_statement text DEFAULT NULL,
            established_year year DEFAULT NULL,
            environmental_focus longtext DEFAULT NULL,
            impact_areas longtext DEFAULT NULL,
            sdg_goals longtext DEFAULT NULL,
            verification_status varchar(20) DEFAULT 'pending',
            verified_by bigint(20) unsigned DEFAULT NULL,
            verification_date datetime DEFAULT NULL,
            tax_exempt_status tinyint(1) DEFAULT 0,
            transparency_score decimal(3,2) DEFAULT 0.00,
            total_donations_received decimal(15,2) DEFAULT 0.00,
            total_projects_funded int DEFAULT 0,
            administrative_percentage decimal(5,2) DEFAULT 0.00,
            logo_url varchar(500) DEFAULT NULL,
            cover_image_url varchar(500) DEFAULT NULL,
            documents longtext DEFAULT NULL,
            social_media longtext DEFAULT NULL,
            bank_account_info longtext DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            created_by bigint(20) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (organization_id),
            KEY idx_slug (organization_slug),
            KEY idx_type (organization_type),
            KEY idx_status (status),
            KEY idx_verification (verification_status),
            KEY idx_created_by (created_by)
        ) $charset_collate;";
        
        // Campaigns table
        $campaigns_table = $wpdb->prefix . 'donation_campaigns';
        $campaigns_sql = "CREATE TABLE $campaigns_table (
            campaign_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            organization_id bigint(20) unsigned NOT NULL,
            campaign_title varchar(255) NOT NULL,
            campaign_slug varchar(255) NOT NULL UNIQUE,
            campaign_description longtext DEFAULT NULL,
            campaign_goal decimal(15,2) NOT NULL,
            current_amount decimal(15,2) DEFAULT 0.00,
            currency_code varchar(3) DEFAULT 'USD',
            start_date datetime NOT NULL,
            end_date datetime DEFAULT NULL,
            campaign_status varchar(20) DEFAULT 'active',
            campaign_type varchar(50) DEFAULT 'general',
            environmental_category varchar(100) DEFAULT NULL,
            target_beneficiaries int DEFAULT NULL,
            expected_impact text DEFAULT NULL,
            featured_image_url varchar(500) DEFAULT NULL,
            gallery_images longtext DEFAULT NULL,
            documents longtext DEFAULT NULL,
            milestone_goals longtext DEFAULT NULL,
            milestone_achievements longtext DEFAULT NULL,
            impact_metrics longtext DEFAULT NULL,
            location_focus varchar(255) DEFAULT NULL,
            coordinates varchar(100) DEFAULT NULL,
            priority_level varchar(20) DEFAULT 'medium',
            min_donation_amount decimal(10,2) DEFAULT 1.00,
            suggested_amounts longtext DEFAULT NULL,
            allow_recurring tinyint(1) DEFAULT 1,
            tax_deductible tinyint(1) DEFAULT 0,
            thank_you_message text DEFAULT NULL,
            email_notifications tinyint(1) DEFAULT 1,
            public_donor_list tinyint(1) DEFAULT 1,
            anonymous_donations tinyint(1) DEFAULT 1,
            share_settings longtext DEFAULT NULL,
            total_donors int DEFAULT 0,
            average_donation decimal(10,2) DEFAULT 0.00,
            last_donation_date datetime DEFAULT NULL,
            featured tinyint(1) DEFAULT 0,
            trending_score decimal(8,2) DEFAULT 0.00,
            view_count int DEFAULT 0,
            share_count int DEFAULT 0,
            created_by bigint(20) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (campaign_id),
            KEY idx_organization (organization_id),
            KEY idx_slug (campaign_slug),
            KEY idx_status (campaign_status),
            KEY idx_type (campaign_type),
            KEY idx_dates (start_date, end_date),
            KEY idx_featured (featured),
            KEY idx_location (location_focus),
            KEY idx_created_by (created_by),
            FOREIGN KEY (organization_id) REFERENCES $organizations_table(organization_id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // Donations table
        $donations_table = $wpdb->prefix . 'donations';
        $donations_sql = "CREATE TABLE $donations_table (
            donation_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            campaign_id bigint(20) unsigned NOT NULL,
            donor_user_id bigint(20) unsigned DEFAULT NULL,
            donor_email varchar(255) NOT NULL,
            donor_name varchar(255) DEFAULT NULL,
            donor_phone varchar(20) DEFAULT NULL,
            is_anonymous tinyint(1) DEFAULT 0,
            donation_amount decimal(15,2) NOT NULL,
            currency_code varchar(3) DEFAULT 'USD',
            donation_type varchar(20) DEFAULT 'one_time',
            payment_method varchar(50) NOT NULL,
            payment_processor varchar(50) NOT NULL,
            transaction_id varchar(255) NOT NULL UNIQUE,
            processor_transaction_id varchar(255) DEFAULT NULL,
            payment_status varchar(20) DEFAULT 'pending',
            payment_date datetime DEFAULT NULL,
            processing_fee decimal(10,2) DEFAULT 0.00,
            net_amount decimal(15,2) NOT NULL,
            refund_amount decimal(10,2) DEFAULT 0.00,
            refund_date datetime DEFAULT NULL,
            refund_reason text DEFAULT NULL,
            subscription_id bigint(20) unsigned DEFAULT NULL,
            recurring_frequency varchar(20) DEFAULT NULL,
            next_payment_date datetime DEFAULT NULL,
            donor_message text DEFAULT NULL,
            dedication_type varchar(20) DEFAULT NULL,
            dedication_name varchar(255) DEFAULT NULL,
            dedication_message text DEFAULT NULL,
            notification_email varchar(255) DEFAULT NULL,
            tax_receipt_required tinyint(1) DEFAULT 0,
            tax_receipt_sent tinyint(1) DEFAULT 0,
            tax_receipt_date datetime DEFAULT NULL,
            impact_reported tinyint(1) DEFAULT 0,
            impact_report_date datetime DEFAULT NULL,
            thank_you_sent tinyint(1) DEFAULT 0,
            thank_you_date datetime DEFAULT NULL,
            source_campaign varchar(100) DEFAULT NULL,
            referral_source varchar(255) DEFAULT NULL,
            utm_source varchar(100) DEFAULT NULL,
            utm_medium varchar(100) DEFAULT NULL,
            utm_campaign varchar(100) DEFAULT NULL,
            device_type varchar(50) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (donation_id),
            KEY idx_campaign (campaign_id),
            KEY idx_donor (donor_user_id),
            KEY idx_email (donor_email),
            KEY idx_transaction (transaction_id),
            KEY idx_status (payment_status),
            KEY idx_date (payment_date),
            KEY idx_type (donation_type),
            KEY idx_processor (payment_processor),
            KEY idx_subscription (subscription_id),
            FOREIGN KEY (campaign_id) REFERENCES $campaigns_table(campaign_id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // Subscriptions table
        $subscriptions_table = $wpdb->prefix . 'donation_subscriptions';
        $subscriptions_sql = "CREATE TABLE $subscriptions_table (
            subscription_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            campaign_id bigint(20) unsigned NOT NULL,
            donor_user_id bigint(20) unsigned DEFAULT NULL,
            donor_email varchar(255) NOT NULL,
            donor_name varchar(255) DEFAULT NULL,
            subscription_amount decimal(15,2) NOT NULL,
            currency_code varchar(3) DEFAULT 'USD',
            frequency varchar(20) NOT NULL,
            payment_method varchar(50) NOT NULL,
            payment_processor varchar(50) NOT NULL,
            processor_subscription_id varchar(255) NOT NULL UNIQUE,
            subscription_status varchar(20) DEFAULT 'active',
            start_date datetime NOT NULL,
            end_date datetime DEFAULT NULL,
            next_payment_date datetime NOT NULL,
            last_payment_date datetime DEFAULT NULL,
            total_payments int DEFAULT 0,
            successful_payments int DEFAULT 0,
            failed_payments int DEFAULT 0,
            total_amount_paid decimal(15,2) DEFAULT 0.00,
            last_payment_amount decimal(15,2) DEFAULT 0.00,
            failure_count int DEFAULT 0,
            max_failures int DEFAULT 3,
            retry_date datetime DEFAULT NULL,
            cancellation_reason varchar(255) DEFAULT NULL,
            cancelled_by varchar(20) DEFAULT NULL,
            cancelled_at datetime DEFAULT NULL,
            paused_until datetime DEFAULT NULL,
            pause_reason varchar(255) DEFAULT NULL,
            notification_preferences longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (subscription_id),
            KEY idx_campaign (campaign_id),
            KEY idx_donor (donor_user_id),
            KEY idx_email (donor_email),
            KEY idx_status (subscription_status),
            KEY idx_processor_id (processor_subscription_id),
            KEY idx_next_payment (next_payment_date),
            KEY idx_frequency (frequency),
            FOREIGN KEY (campaign_id) REFERENCES $campaigns_table(campaign_id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // Reports table
        $reports_table = $wpdb->prefix . 'donation_reports';
        $reports_sql = "CREATE TABLE $reports_table (
            report_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            report_type varchar(50) NOT NULL,
            entity_type varchar(50) NOT NULL,
            entity_id bigint(20) unsigned NOT NULL,
            report_period varchar(20) NOT NULL,
            period_start datetime NOT NULL,
            period_end datetime NOT NULL,
            report_data longtext NOT NULL,
            total_donations decimal(15,2) DEFAULT 0.00,
            total_donors int DEFAULT 0,
            average_donation decimal(10,2) DEFAULT 0.00,
            currency_code varchar(3) DEFAULT 'USD',
            environmental_impact longtext DEFAULT NULL,
            milestone_achievements longtext DEFAULT NULL,
            beneficiary_feedback longtext DEFAULT NULL,
            media_coverage longtext DEFAULT NULL,
            challenges_faced text DEFAULT NULL,
            future_plans text DEFAULT NULL,
            financial_breakdown longtext DEFAULT NULL,
            report_status varchar(20) DEFAULT 'draft',
            published_at datetime DEFAULT NULL,
            created_by bigint(20) unsigned NOT NULL,
            approved_by bigint(20) unsigned DEFAULT NULL,
            approval_date datetime DEFAULT NULL,
            view_count int DEFAULT 0,
            download_count int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (report_id),
            KEY idx_type (report_type),
            KEY idx_entity (entity_type, entity_id),
            KEY idx_period (period_start, period_end),
            KEY idx_status (report_status),
            KEY idx_created_by (created_by)
        ) $charset_collate;";
        
        // Tax receipts table
        $receipts_table = $wpdb->prefix . 'donation_tax_receipts';
        $receipts_sql = "CREATE TABLE $receipts_table (
            receipt_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            donation_id bigint(20) unsigned NOT NULL,
            receipt_number varchar(100) NOT NULL UNIQUE,
            tax_year year NOT NULL,
            donor_name varchar(255) NOT NULL,
            donor_email varchar(255) NOT NULL,
            donor_address text DEFAULT NULL,
            organization_name varchar(255) NOT NULL,
            organization_address text DEFAULT NULL,
            organization_tax_id varchar(100) DEFAULT NULL,
            donation_amount decimal(15,2) NOT NULL,
            deductible_amount decimal(15,2) NOT NULL,
            currency_code varchar(3) DEFAULT 'USD',
            donation_date datetime NOT NULL,
            receipt_date datetime NOT NULL,
            receipt_status varchar(20) DEFAULT 'generated',
            pdf_file_path varchar(500) DEFAULT NULL,
            email_sent tinyint(1) DEFAULT 0,
            email_sent_date datetime DEFAULT NULL,
            download_count int DEFAULT 0,
            last_downloaded datetime DEFAULT NULL,
            notes text DEFAULT NULL,
            created_by bigint(20) unsigned DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (receipt_id),
            KEY idx_donation (donation_id),
            KEY idx_receipt_number (receipt_number),
            KEY idx_tax_year (tax_year),
            KEY idx_donor_email (donor_email),
            KEY idx_status (receipt_status),
            FOREIGN KEY (donation_id) REFERENCES $donations_table(donation_id) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($organizations_sql);
        dbDelta($campaigns_sql);
        dbDelta($donations_sql);
        dbDelta($subscriptions_sql);
        dbDelta($reports_sql);
        dbDelta($receipts_sql);
        
        // Update database version
        update_option('eds_db_version', self::DB_VERSION);
        
        // Create default data
        self::create_default_data();
    }
    
    /**
     * Create default data
     */
    private static function create_default_data() {
        global $wpdb;
        
        // Check if we already have default data
        $organizations_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}donation_organizations");
        
        if ($organizations_count > 0) {
            return; // Default data already exists
        }
        
        // Create default organization
        $default_org = array(
            'organization_name' => 'Environmental Protection Foundation',
            'organization_slug' => 'environmental-protection-foundation',
            'organization_type' => 'foundation',
            'contact_email' => get_option('admin_email'),
            'description' => 'Default environmental organization for donation campaigns.',
            'mission_statement' => 'Protecting and preserving our environment for future generations.',
            'environmental_focus' => json_encode(['climate_change', 'biodiversity', 'pollution']),
            'impact_areas' => json_encode(['local', 'national']),
            'verification_status' => 'verified',
            'tax_exempt_status' => 1,
            'status' => 'active',
            'created_by' => 1,
        );
        
        $wpdb->insert(
            $wpdb->prefix . 'donation_organizations',
            $default_org
        );
    }
    
    /**
     * Check if database needs upgrade
     */
    public function maybe_upgrade_database() {
        $installed_version = get_option('eds_db_version', '0');
        
        if (version_compare($installed_version, self::DB_VERSION, '<')) {
            self::create_tables();
        }
    }
    
    /**
     * Drop all plugin tables
     */
    public static function drop_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'donation_tax_receipts',
            $wpdb->prefix . 'donation_reports',
            $wpdb->prefix . 'donation_subscriptions',
            $wpdb->prefix . 'donations',
            $wpdb->prefix . 'donation_campaigns',
            $wpdb->prefix . 'donation_organizations'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        delete_option('eds_db_version');
    }
    
    /**
     * Get table name with prefix
     */
    public static function get_table_name($table) {
        global $wpdb;
        return $wpdb->prefix . 'donation_' . $table;
    }
    
    /**
     * Check if tables exist
     */
    public static function tables_exist() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'donation_organizations';
        $result = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        
        return $result === $table;
    }
}
