<?php
/**
 * Plugin Name: Environmental Integration APIs & Webhooks
 * Plugin URI: https://environmentalplatform.com/plugins/integration-apis
 * Description: Comprehensive integration system with Google Maps, weather/air quality APIs, social media APIs, and webhook management for the Environmental Platform.
 * Version: 1.0.0
 * Author: Environmental Platform Team
 * Author URI: https://environmentalplatform.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: environmental-integration-apis
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 *
 * @package Environmental_Integration_APIs
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EIA_VERSION', '1.0.0');
define('EIA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EIA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EIA_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('EIA_TABLE_PREFIX', 'eia_');

/**
 * Main Environmental Integration APIs class
 *
 * @class Environmental_Integration_APIs
 * @version 1.0.0
 */
final class Environmental_Integration_APIs {

    /**
     * Plugin instance
     *
     * @var Environmental_Integration_APIs
     */
    private static $instance = null;

    /**
     * Google Maps integration
     *
     * @var Environmental_Google_Maps_Integration
     */
    public $google_maps;

    /**
     * Weather integration
     *
     * @var Environmental_Weather_Integration
     */
    public $weather;

    /**
     * Air quality integration
     *
     * @var Environmental_Air_Quality_Integration
     */
    public $air_quality;

    /**
     * Social media integration
     *
     * @var Environmental_Social_Media_Integration
     */
    public $social_media;

    /**
     * Webhook system
     *
     * @var Environmental_Webhook_System
     */
    public $webhooks;

    /**
     * API monitoring
     *
     * @var Environmental_API_Monitor
     */
    public $api_monitor;

    /**
     * Admin interface
     *
     * @var Environmental_Integration_Admin
     */
    public $admin;

    /**
     * REST API
     *
     * @var Environmental_Integration_REST_API
     */
    public $rest_api;

    /**
     * Get main instance
     *
     * @return Environmental_Integration_APIs
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
        $this->includes();
        $this->init_components();
    }

    /**
     * Hook into actions and filters
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'), 0);
        add_action('plugins_loaded', array($this, 'plugins_loaded'), 11);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_eia_api_request', array($this, 'handle_ajax_api_request'));
        add_action('wp_ajax_nopriv_eia_api_request', array($this, 'handle_ajax_api_request'));
        add_action('wp_ajax_eia_webhook_test', array($this, 'handle_webhook_test'));
        add_action('rest_api_init', array($this, 'init_rest_api'));
        
        // Scheduled tasks
        add_action('eia_monitor_apis', array($this, 'monitor_api_health'));
        add_action('eia_cleanup_logs', array($this, 'cleanup_old_logs'));
        
        // Plugin lifecycle hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Include required files
     */
    private function includes() {
        require_once EIA_PLUGIN_DIR . 'includes/class-google-maps-integration.php';
        require_once EIA_PLUGIN_DIR . 'includes/class-weather-integration.php';
        require_once EIA_PLUGIN_DIR . 'includes/class-air-quality-integration.php';
        require_once EIA_PLUGIN_DIR . 'includes/class-social-media-integration.php';
        require_once EIA_PLUGIN_DIR . 'includes/class-webhook-system.php';
        require_once EIA_PLUGIN_DIR . 'includes/class-api-monitor.php';
        require_once EIA_PLUGIN_DIR . 'includes/class-integration-admin.php';
        require_once EIA_PLUGIN_DIR . 'includes/class-integration-rest-api.php';
    }

    /**
     * Initialize components
     */
    private function init_components() {
        $this->google_maps = new Environmental_Google_Maps_Integration();
        $this->weather = new Environmental_Weather_Integration();
        $this->air_quality = new Environmental_Air_Quality_Integration();
        $this->social_media = new Environmental_Social_Media_Integration();
        $this->webhooks = new Environmental_Webhook_System();
        $this->api_monitor = new Environmental_API_Monitor();
        $this->admin = new Environmental_Integration_Admin();
        $this->rest_api = new Environmental_Integration_REST_API();
    }

    /**
     * Init Environmental Integration APIs when WordPress Initialises
     */
    public function init() {
        // Before init action
        do_action('eia_before_init');

        // Set up localisation
        $this->load_plugin_textdomain();

        // Init action
        do_action('eia_init');
    }

    /**
     * Load plugin textdomain
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain('environmental-integration-apis', false, dirname(EIA_PLUGIN_BASENAME) . '/languages');
    }

    /**
     * When WP has loaded all plugins, trigger the `eia_loaded` hook
     */
    public function plugins_loaded() {
        do_action('eia_loaded');
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

        wp_enqueue_style(
            'eia-frontend',
            EIA_PLUGIN_URL . 'assets/css/frontend' . $suffix . '.css',
            array(),
            EIA_VERSION
        );

        wp_enqueue_script(
            'eia-frontend',
            EIA_PLUGIN_URL . 'assets/js/frontend' . $suffix . '.js',
            array('jquery'),
            EIA_VERSION,
            true
        );

        // Localize script
        wp_localize_script('eia-frontend', 'eia_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eia_nonce'),
            'google_maps_api_key' => get_option('eia_google_maps_api_key', ''),
            'translations' => array(
                'loading' => __('Loading...', 'environmental-integration-apis'),
                'error' => __('An error occurred', 'environmental-integration-apis'),
                'no_data' => __('No data available', 'environmental-integration-apis'),
            )
        ));
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'eia-') === false) {
            return;
        }

        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

        wp_enqueue_style(
            'eia-admin',
            EIA_PLUGIN_URL . 'assets/css/admin' . $suffix . '.css',
            array(),
            EIA_VERSION
        );

        wp_enqueue_script(
            'eia-admin',
            EIA_PLUGIN_URL . 'assets/js/admin' . $suffix . '.js',
            array('jquery', 'wp-util', 'wp-backbone'),
            EIA_VERSION,
            true
        );

        // Include Chart.js for analytics
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js',
            array(),
            '3.9.1',
            true
        );

        wp_localize_script('eia-admin', 'eia_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eia_admin_nonce'),
            'api_monitor_interval' => 30000, // 30 seconds
        ));
    }

    /**
     * Handle AJAX API requests
     */
    public function handle_ajax_api_request() {
        check_ajax_referer('eia_nonce', 'nonce');

        $action = sanitize_text_field($_POST['api_action'] ?? '');
        $data = wp_unslash($_POST['data'] ?? array());

        $response = array('success' => false, 'data' => null);

        switch ($action) {
            case 'get_weather':
                $response = $this->weather->get_weather_data($data);
                break;
            case 'get_air_quality':
                $response = $this->air_quality->get_air_quality_data($data);
                break;
            case 'geocode_address':
                $response = $this->google_maps->geocode_address($data['address'] ?? '');
                break;
            case 'post_to_social':
                $response = $this->social_media->post_to_platform($data);
                break;
        }

        wp_send_json($response);
    }

    /**
     * Handle webhook test
     */
    public function handle_webhook_test() {
        check_ajax_referer('eia_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $webhook_id = intval($_POST['webhook_id'] ?? 0);
        $result = $this->webhooks->test_webhook($webhook_id);

        wp_send_json($result);
    }

    /**
     * Initialize REST API
     */
    public function init_rest_api() {
        $this->rest_api->register_routes();
    }

    /**
     * Monitor API health
     */
    public function monitor_api_health() {
        $this->api_monitor->check_all_apis();
    }

    /**
     * Cleanup old logs
     */
    public function cleanup_old_logs() {
        $this->api_monitor->cleanup_old_logs();
    }

    /**
     * Plugin activation
     */
    public function activate() {
        $this->create_tables();
        $this->setup_default_options();
        $this->schedule_events();
        
        // Clear rewrite rules
        flush_rewrite_rules();
        
        do_action('eia_activated');
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        $this->clear_scheduled_events();
        
        // Clear rewrite rules
        flush_rewrite_rules();
        
        do_action('eia_deactivated');
    }

    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // API connections table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}eia_api_connections (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            type varchar(50) NOT NULL,
            endpoint varchar(500) NOT NULL,
            api_key varchar(255) DEFAULT NULL,
            settings longtext DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            rate_limit_per_hour int(11) DEFAULT 1000,
            last_used datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_type (type),
            KEY idx_status (status),
            KEY idx_last_used (last_used)
        ) $charset_collate;";

        // API logs table
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}eia_api_logs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            connection_id bigint(20) unsigned NOT NULL,
            request_url varchar(1000) NOT NULL,
            request_method varchar(10) DEFAULT 'GET',
            request_data longtext DEFAULT NULL,
            response_code int(11) DEFAULT NULL,
            response_data longtext DEFAULT NULL,
            response_time float DEFAULT NULL,
            error_message text DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent varchar(500) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_connection_id (connection_id),
            KEY idx_response_code (response_code),
            KEY idx_created_at (created_at),
            FOREIGN KEY (connection_id) REFERENCES {$wpdb->prefix}eia_api_connections(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Webhooks table
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}eia_webhooks (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            url varchar(500) NOT NULL,
            events text NOT NULL,
            secret varchar(255) DEFAULT NULL,
            headers longtext DEFAULT NULL,
            retry_attempts int(11) DEFAULT 3,
            timeout int(11) DEFAULT 30,
            status varchar(20) DEFAULT 'active',
            last_triggered datetime DEFAULT NULL,
            success_count int(11) DEFAULT 0,
            failure_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_status (status),
            KEY idx_last_triggered (last_triggered)
        ) $charset_collate;";

        // Webhook logs table
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}eia_webhook_logs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            webhook_id bigint(20) unsigned NOT NULL,
            event varchar(100) NOT NULL,
            payload longtext DEFAULT NULL,
            response_code int(11) DEFAULT NULL,
            response_data longtext DEFAULT NULL,
            response_time float DEFAULT NULL,
            attempt_number int(11) DEFAULT 1,
            status varchar(20) DEFAULT 'pending',
            error_message text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_webhook_id (webhook_id),
            KEY idx_event (event),
            KEY idx_status (status),
            KEY idx_created_at (created_at),
            FOREIGN KEY (webhook_id) REFERENCES {$wpdb->prefix}eia_webhooks(id) ON DELETE CASCADE
        ) $charset_collate;";

        // API cache table
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}eia_api_cache (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            cache_key varchar(255) NOT NULL,
            cache_data longtext NOT NULL,
            expires_at datetime NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_cache_key (cache_key),
            KEY idx_expires_at (expires_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Setup default options
     */
    private function setup_default_options() {
        $default_options = array(
            'eia_google_maps_api_key' => '',
            'eia_weather_api_key' => '',
            'eia_air_quality_api_key' => '',
            'eia_facebook_app_id' => '',
            'eia_facebook_app_secret' => '',
            'eia_twitter_api_key' => '',
            'eia_twitter_api_secret' => '',
            'eia_instagram_access_token' => '',
            'eia_api_rate_limit' => 1000,
            'eia_cache_duration' => 3600,
            'eia_monitor_interval' => 300,
            'eia_webhook_timeout' => 30,
            'eia_webhook_retry_attempts' => 3,
            'eia_log_retention_days' => 30,
        );

        foreach ($default_options as $option => $value) {
            if (!get_option($option)) {
                add_option($option, $value);
            }
        }
    }

    /**
     * Schedule events
     */
    private function schedule_events() {
        if (!wp_next_scheduled('eia_monitor_apis')) {
            wp_schedule_event(time(), 'every_five_minutes', 'eia_monitor_apis');
        }
        
        if (!wp_next_scheduled('eia_cleanup_logs')) {
            wp_schedule_event(time(), 'daily', 'eia_cleanup_logs');
        }
    }

    /**
     * Clear scheduled events
     */
    private function clear_scheduled_events() {
        wp_clear_scheduled_hook('eia_monitor_apis');
        wp_clear_scheduled_hook('eia_cleanup_logs');
    }

    /**
     * Add custom cron intervals
     */
    public function add_cron_intervals($schedules) {
        $schedules['every_five_minutes'] = array(
            'interval' => 300,
            'display' => __('Every 5 Minutes', 'environmental-integration-apis')
        );
        
        $schedules['every_fifteen_minutes'] = array(
            'interval' => 900,
            'display' => __('Every 15 Minutes', 'environmental-integration-apis')
        );
        
        return $schedules;
    }

    /**
     * Get API connection
     */
    public function get_api_connection($type) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}eia_api_connections WHERE type = %s AND status = 'active' LIMIT 1",
            $type
        ));
    }

    /**
     * Log API request
     */
    public function log_api_request($connection_id, $request_url, $method, $request_data, $response_code, $response_data, $response_time, $error = null) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'eia_api_logs',
            array(
                'connection_id' => $connection_id,
                'request_url' => $request_url,
                'request_method' => $method,
                'request_data' => is_array($request_data) ? wp_json_encode($request_data) : $request_data,
                'response_code' => $response_code,
                'response_data' => is_array($response_data) ? wp_json_encode($response_data) : $response_data,
                'response_time' => $response_time,
                'error_message' => $error,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ),
            array('%d', '%s', '%s', '%s', '%d', '%s', '%f', '%s', '%s', '%s')
        );
    }

    /**
     * Get cached data
     */
    public function get_cache($key) {
        global $wpdb;
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT cache_data FROM {$wpdb->prefix}eia_api_cache WHERE cache_key = %s AND expires_at > NOW()",
            $key
        ));
        
        return $result ? json_decode($result->cache_data, true) : null;
    }

    /**
     * Set cached data
     */
    public function set_cache($key, $data, $duration = 3600) {
        global $wpdb;
        
        $expires_at = date('Y-m-d H:i:s', time() + $duration);
        
        $wpdb->replace(
            $wpdb->prefix . 'eia_api_cache',
            array(
                'cache_key' => $key,
                'cache_data' => wp_json_encode($data),
                'expires_at' => $expires_at,
            ),
            array('%s', '%s', '%s')
        );
    }
}

// Initialize the plugin
function EIA() {
    return Environmental_Integration_APIs::get_instance();
}

// Global for backwards compatibility
$GLOBALS['environmental_integration_apis'] = EIA();

// Add custom cron intervals
add_filter('cron_schedules', array(EIA(), 'add_cron_intervals'));
