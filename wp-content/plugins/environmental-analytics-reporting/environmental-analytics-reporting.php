<?php
/**
 * Plugin Name: Environmental Analytics & Reporting
 * Plugin URI: https://environmental-platform.local
 * Description: Advanced analytics and reporting system for tracking environmental actions, user behavior, and automated insights.
 * Version: 1.0.0
 * Author: Environmental Platform Team
 * License: GPL v2 or later
 * Text Domain: env-analytics
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ENV_ANALYTICS_VERSION', '1.0.0');
define('ENV_ANALYTICS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ENV_ANALYTICS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ENV_ANALYTICS_PLUGIN_FILE', __FILE__);

/**
 * Main Environmental Analytics class
 */
class Environmental_Analytics_Reporting {
    
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
      /**
     * Load plugin dependencies
     */    private function load_dependencies() {
        require_once ENV_ANALYTICS_PLUGIN_DIR . 'includes/class-database-manager.php';
        require_once ENV_ANALYTICS_PLUGIN_DIR . 'includes/class-tracking-manager.php';
        require_once ENV_ANALYTICS_PLUGIN_DIR . 'includes/class-conversion-tracker.php';
        require_once ENV_ANALYTICS_PLUGIN_DIR . 'includes/class-behavior-analytics.php';
        require_once ENV_ANALYTICS_PLUGIN_DIR . 'includes/class-ga4-integration.php';
        require_once ENV_ANALYTICS_PLUGIN_DIR . 'includes/class-report-generator.php';        require_once ENV_ANALYTICS_PLUGIN_DIR . 'includes/class-dashboard-widgets.php';
        require_once ENV_ANALYTICS_PLUGIN_DIR . 'includes/class-cron-handler.php';
        require_once ENV_ANALYTICS_PLUGIN_DIR . 'includes/class-optimization-manager.php';
        require_once ENV_ANALYTICS_PLUGIN_DIR . 'admin/class-admin-dashboard.php';
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize core managers
        $this->database_manager = new Environmental_Database_Manager();
        $this->tracking_manager = new Environmental_Tracking_Manager($this->database_manager);
        $this->conversion_tracker = new Environmental_Conversion_Tracker($this->database_manager, $this->tracking_manager);
        $this->behavior_analytics = new Environmental_Behavior_Analytics($this->database_manager, $this->tracking_manager);
        $this->ga4_integration = new Environmental_GA4_Integration($this->tracking_manager);
          // Initialize reporting and dashboard components
        if (class_exists('Environmental_Report_Generator')) {
            $this->report_generator = new Environmental_Report_Generator(
                $this->database_manager,
                $this->behavior_analytics,
                $this->conversion_tracker
            );
        }        if (class_exists('Environmental_Dashboard_Widgets')) {
            $this->dashboard_widgets = new Environmental_Dashboard_Widgets(
                $this->database_manager,
                $this->behavior_analytics,
                $this->conversion_tracker
            );
        }
        
        // Initialize cron handler
        if (class_exists('Environmental_Cron_Handler')) {
            $this->cron_handler = new Environmental_Cron_Handler(
                $this->database_manager,
                $this->behavior_analytics,
                $this->report_generator
            );
        }
        
        // Initialize admin dashboard if in admin
        if (is_admin() && class_exists('Environmental_Admin_Dashboard')) {
            $this->admin_dashboard = new Environmental_Admin_Dashboard(
                $this->database_manager,
                $this->tracking_manager,
                $this->conversion_tracker,
                $this->behavior_analytics,
                $this->ga4_integration
            );
        }        
        // Hook into environmental actions for automatic tracking
        $this->setup_environmental_hooks();
    }
    
    /**
     * Setup environmental platform tracking hooks
     */
    private function setup_environmental_hooks() {
        // Track forum posts
        add_action('wp_insert_post', array($this->tracking_manager, 'track_forum_post'), 10, 2);
        
        // Track donations
        add_action('env_donation_completed', array($this->tracking_manager, 'track_donation'), 10, 2);
        
        // Track item exchanges
        add_action('env_item_exchange_completed', array($this->tracking_manager, 'track_item_exchange'), 10, 2);
        
        // Track petitions
        add_action('env_petition_signed', array($this->tracking_manager, 'track_petition_signature'), 10, 2);
        
        // Track user registrations
        add_action('user_register', array($this->tracking_manager, 'track_user_registration'));
        
        // Track login/logout
        add_action('wp_login', array($this->tracking_manager, 'track_user_login'), 10, 2);
        add_action('wp_logout', array($this->tracking_manager, 'track_user_logout'));
        
        // Track page views
        add_action('wp', array($this->tracking_manager, 'track_page_view'));
        
        // Track achievements
        add_action('env_achievement_earned', array($this->tracking_manager, 'track_achievement'), 10, 3);
          // Track voucher redemptions
        add_action('env_voucher_redeemed', array($this->tracking_manager, 'track_voucher_redemption'), 10, 2);
        
        // Schedule daily analytics cron job
        if (!wp_next_scheduled('env_daily_analytics_cron')) {
            wp_schedule_event(time(), 'daily', 'env_daily_analytics_cron');
        }
        
        // Setup cron actions
        add_action('env_daily_analytics_cron', array($this, 'run_daily_analytics'));
        add_action('env_analytics_daily_report', array($this, 'send_daily_report'));
        add_action('env_analytics_weekly_report', array($this, 'send_weekly_report'));
        add_action('env_analytics_monthly_report', array($this, 'send_monthly_report'));
    }
    
    /**
     * Run daily analytics processing
     */
    public function run_daily_analytics() {
        if (isset($this->cron_handler)) {
            $this->cron_handler->process_daily_analytics();
        }
    }
    
    /**
     * Send daily report
     */
    public function send_daily_report() {
        if (isset($this->report_generator) && get_option('env_analytics_daily_email_enabled', false)) {
            $this->report_generator->send_automated_report('daily');
        }
    }
    
    /**
     * Send weekly report
     */
    public function send_weekly_report() {
        if (isset($this->report_generator) && get_option('env_analytics_weekly_email_enabled', true)) {
            $this->report_generator->send_automated_report('weekly');
        }
    }
    
    /**
     * Send monthly report
     */
    public function send_monthly_report() {
        if (isset($this->report_generator) && get_option('env_analytics_monthly_email_enabled', true)) {
            $this->report_generator->send_automated_report('monthly');
        }
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_scripts() {        wp_enqueue_script(
            'env-analytics-frontend',
            ENV_ANALYTICS_PLUGIN_URL . 'assets/js/env-analytics.js',
            array('jquery'),
            ENV_ANALYTICS_VERSION,
            true
        );
          wp_localize_script('env-analytics-frontend', 'env_analytics_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('env_analytics_nonce'),
            'user_id' => get_current_user_id(),
            'session_id' => $this->get_session_id(),
            'tracking_enabled' => get_option('env_analytics_tracking_enabled', 1),
            'ga4_measurement_id' => get_option('env_analytics_ga4_measurement_id', '')
        ));
    }
    
    /**
     * Get current session ID
     */
    private function get_session_id() {
        if (isset($this->tracking_manager)) {
            return $this->tracking_manager->get_session_id();
        }
        return session_id() ?: uniqid('env_session_');
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'environmental-analytics') === false) {
            return;
        }
        
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1');
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui-datepicker-style', 'https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css');
          wp_enqueue_script(
            'env-analytics-admin',
            ENV_ANALYTICS_PLUGIN_URL . 'assets/js/env-analytics.js',
            array('jquery', 'chart-js', 'jquery-ui-datepicker'),
            ENV_ANALYTICS_VERSION,
            true
        );
        
        wp_enqueue_style(
            'env-analytics-admin',
            ENV_ANALYTICS_PLUGIN_URL . 'assets/css/env-analytics-admin.css',
            array(),
            ENV_ANALYTICS_VERSION
        );
        
        wp_localize_script('env-analytics-admin', 'env_analytics_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('env_analytics_nonce'),
            'user_id' => get_current_user_id(),
            'tracking_enabled' => get_option('env_analytics_tracking_enabled', 1),
            'ga4_measurement_id' => get_option('env_analytics_ga4_measurement_id', '')
        ));
    }
      /**
     * Activate plugin
     */
    public function activate() {
        // Create database tables
        $database_manager = new Environmental_Database_Manager();
        $database_manager->create_tables();
        
        // Set default options
        add_option('env_analytics_tracking_enabled', 1);
        add_option('env_analytics_ga4_enabled', 0);
        add_option('env_analytics_ga4_measurement_id', '');
        add_option('env_analytics_conversion_goals', array());
        add_option('env_analytics_daily_email_enabled', false);
        add_option('env_analytics_weekly_email_enabled', true);
        add_option('env_analytics_monthly_email_enabled', true);
        add_option('env_analytics_email_recipients', array(get_option('admin_email')));
        
        // Schedule automated reports
        if (!wp_next_scheduled('env_analytics_daily_report')) {
            wp_schedule_event(strtotime('6:00 AM'), 'daily', 'env_analytics_daily_report');
        }
        if (!wp_next_scheduled('env_analytics_weekly_report')) {
            wp_schedule_event(strtotime('next Monday 7:00 AM'), 'weekly', 'env_analytics_weekly_report');
        }
        if (!wp_next_scheduled('env_analytics_monthly_report')) {
            wp_schedule_event(strtotime('first day of next month 8:00 AM'), 'monthly', 'env_analytics_monthly_report');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
      /**
     * Deactivate plugin
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('env_analytics_daily_report');
        wp_clear_scheduled_hook('env_analytics_weekly_report');
        wp_clear_scheduled_hook('env_analytics_monthly_report');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Initialize plugin
add_action('plugins_loaded', array('Environmental_Analytics_Reporting', 'get_instance'));
