<?php
/**
 * Plugin Name: Environmental Platform Mobile API
 * Plugin URI: https://environmental-platform.com/mobile-api
 * Description: Comprehensive REST API for mobile app integration with JWT authentication, rate limiting, caching, and real-time webhooks for the Environmental Platform.
 * Version: 1.0.0
 * Author: Environmental Platform Team
 * License: GPL-2.0+
 * Text Domain: environmental-mobile-api
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ENVIRONMENTAL_MOBILE_API_VERSION', '1.0.0');
define('ENVIRONMENTAL_MOBILE_API_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ENVIRONMENTAL_MOBILE_API_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ENVIRONMENTAL_MOBILE_API_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class Environmental_Mobile_API {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Components
     */
    private $api_manager;
    private $auth_manager;
    private $rate_limiter;
    private $cache_manager;
    private $webhook_manager;
    private $documentation;
    private $security;
    
    /**
     * Get instance
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
        register_uninstall_hook(__FILE__, array('Environmental_Mobile_API', 'uninstall'));
        
        add_action('plugins_loaded', array($this, 'init'));
        add_action('init', array($this, 'load_textdomain'));
        add_action('rest_api_init', array($this, 'init_rest_api'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // CORS support
        add_action('init', array($this, 'handle_cors'));
        
        // JWT token cleanup
        add_action('environmental_mobile_api_cleanup_tokens', array($this, 'cleanup_expired_tokens'));
        if (!wp_next_scheduled('environmental_mobile_api_cleanup_tokens')) {
            wp_schedule_event(time(), 'hourly', 'environmental_mobile_api_cleanup_tokens');
        }
    }
    
    /**
     * Load dependencies
     */
    private function load_dependencies() {
        // Core includes
        require_once ENVIRONMENTAL_MOBILE_API_PLUGIN_DIR . 'includes/class-api-manager.php';
        require_once ENVIRONMENTAL_MOBILE_API_PLUGIN_DIR . 'includes/class-auth-manager.php';
        require_once ENVIRONMENTAL_MOBILE_API_PLUGIN_DIR . 'includes/class-rate-limiter.php';
        require_once ENVIRONMENTAL_MOBILE_API_PLUGIN_DIR . 'includes/class-cache-manager.php';
        require_once ENVIRONMENTAL_MOBILE_API_PLUGIN_DIR . 'includes/class-webhook-manager.php';
        require_once ENVIRONMENTAL_MOBILE_API_PLUGIN_DIR . 'includes/class-security.php';
        require_once ENVIRONMENTAL_MOBILE_API_PLUGIN_DIR . 'includes/class-documentation.php';
          // Endpoint classes
        require_once ENVIRONMENTAL_MOBILE_API_PLUGIN_DIR . 'includes/endpoints/class-auth-endpoints.php';
        require_once ENVIRONMENTAL_MOBILE_API_PLUGIN_DIR . 'includes/endpoints/class-user-endpoints.php';
        require_once ENVIRONMENTAL_MOBILE_API_PLUGIN_DIR . 'includes/endpoints/class-content-endpoints.php';
        require_once ENVIRONMENTAL_MOBILE_API_PLUGIN_DIR . 'includes/endpoints/class-environmental-data-endpoints.php';
        
        // Admin classes
        require_once ENVIRONMENTAL_MOBILE_API_PLUGIN_DIR . 'admin/class-admin-dashboard.php';
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize managers
        $this->auth_manager = new Environmental_Mobile_API_Auth_Manager();
        $this->rate_limiter = new Environmental_Mobile_API_Rate_Limiter();
        $this->cache_manager = new Environmental_Mobile_API_Cache_Manager();
        $this->webhook_manager = new Environmental_Mobile_API_Webhook_Manager();
        $this->security = new Environmental_Mobile_API_Security();
        $this->documentation = new Environmental_Mobile_API_Documentation();
        $this->api_manager = new Environmental_Mobile_API_Manager();
          // Initialize admin components
        if (is_admin()) {
            new Environmental_Mobile_API_Admin_Dashboard();
        }
    }
      /**
     * Initialize REST API
     */
    public function init_rest_api() {
        // Register API endpoints
        new Environmental_Mobile_API_Auth_Endpoints();
        new Environmental_Mobile_API_User_Endpoints();
        new Environmental_Mobile_API_Content_Endpoints();
        new Environmental_Mobile_API_Environmental_Data_Endpoints();
    }
    
    /**
     * Handle CORS
     */
    public function handle_cors() {
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            $allowed_origins = get_option('environmental_mobile_api_cors_origins', array());
            
            if (in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins) || in_array('*', $allowed_origins)) {
                header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
                header('Access-Control-Allow-Credentials: true');
                header('Access-Control-Max-Age: 86400');
            }
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            }
            
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                header('Access-Control-Allow-Headers: ' . $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
            }
            
            exit(0);
        }
    }
    
    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'environmental-mobile-api',
            false,
            dirname(ENVIRONMENTAL_MOBILE_API_BASENAME) . '/languages'
        );
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        // Only enqueue if needed
        if (!is_admin() && is_user_logged_in()) {
            wp_enqueue_script(
                'environmental-mobile-api-frontend',
                ENVIRONMENTAL_MOBILE_API_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                ENVIRONMENTAL_MOBILE_API_VERSION,
                true
            );
            
            wp_localize_script('environmental-mobile-api-frontend', 'environmentalMobileAPI', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'restUrl' => rest_url('environmental-mobile-api/v1/'),
                'nonce' => wp_create_nonce('environmental_mobile_api_nonce')
            ));
        }
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'environmental-mobile-api') !== false) {
            wp_enqueue_script(
                'environmental-mobile-api-admin',
                ENVIRONMENTAL_MOBILE_API_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery', 'wp-api'),
                ENVIRONMENTAL_MOBILE_API_VERSION,
                true
            );
            
            wp_enqueue_style(
                'environmental-mobile-api-admin',
                ENVIRONMENTAL_MOBILE_API_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                ENVIRONMENTAL_MOBILE_API_VERSION
            );
            
            wp_localize_script('environmental-mobile-api-admin', 'environmentalMobileAPIAdmin', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'restUrl' => rest_url('environmental-mobile-api/v1/'),
                'nonce' => wp_create_nonce('environmental_mobile_api_admin_nonce')
            ));
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('Mobile API Settings', 'environmental-mobile-api'),
            __('Mobile API', 'environmental-mobile-api'),
            'manage_options',
            'environmental-mobile-api',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Admin page callback
     */
    public function admin_page() {
        include ENVIRONMENTAL_MOBILE_API_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        $this->create_tables();
        $this->create_default_options();
        $this->generate_jwt_secret();
        
        // Create upload directories
        $upload_dir = wp_upload_dir();
        $mobile_api_dir = $upload_dir['basedir'] . '/environmental-mobile-api';
        
        if (!file_exists($mobile_api_dir)) {
            wp_mkdir_p($mobile_api_dir);
            wp_mkdir_p($mobile_api_dir . '/logs');
            wp_mkdir_p($mobile_api_dir . '/cache');
            wp_mkdir_p($mobile_api_dir . '/temp');
        }
        
        // Set up rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        wp_clear_scheduled_hook('environmental_mobile_api_cleanup_tokens');
        flush_rewrite_rules();
    }
    
    /**
     * Plugin uninstall
     */
    public static function uninstall() {
        global $wpdb;
        
        // Remove tables
        $tables = array(
            $wpdb->prefix . 'environmental_mobile_api_tokens',
            $wpdb->prefix . 'environmental_mobile_api_rate_limits',
            $wpdb->prefix . 'environmental_mobile_api_logs',
            $wpdb->prefix . 'environmental_mobile_api_webhooks',
            $wpdb->prefix . 'environmental_mobile_api_devices'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // Remove options
        $options = array(
            'environmental_mobile_api_version',
            'environmental_mobile_api_jwt_secret',
            'environmental_mobile_api_settings',
            'environmental_mobile_api_rate_limits',
            'environmental_mobile_api_cors_origins'
        );
        
        foreach ($options as $option) {
            delete_option($option);
        }
        
        // Remove upload directory
        $upload_dir = wp_upload_dir();
        $mobile_api_dir = $upload_dir['basedir'] . '/environmental-mobile-api';
        
        if (file_exists($mobile_api_dir)) {
            wp_delete_file($mobile_api_dir);
        }
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // JWT tokens table
        $sql = "CREATE TABLE {$wpdb->prefix}environmental_mobile_api_tokens (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            token_type varchar(20) DEFAULT 'access',
            token_hash varchar(255) NOT NULL,
            device_id varchar(100) DEFAULT NULL,
            expires_at datetime NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            last_used_at datetime DEFAULT NULL,
            is_revoked tinyint(1) DEFAULT 0,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY token_hash (token_hash),
            KEY device_id (device_id),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Rate limiting table
        $sql = "CREATE TABLE {$wpdb->prefix}environmental_mobile_api_rate_limits (
            id int(11) NOT NULL AUTO_INCREMENT,
            identifier varchar(255) NOT NULL,
            endpoint varchar(255) NOT NULL,
            requests int(11) DEFAULT 0,
            window_start datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY identifier_endpoint (identifier, endpoint),
            KEY window_start (window_start)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // API logs table
        $sql = "CREATE TABLE {$wpdb->prefix}environmental_mobile_api_logs (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            endpoint varchar(255) NOT NULL,
            method varchar(10) NOT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            request_data longtext DEFAULT NULL,
            response_code int(11) DEFAULT NULL,
            response_time float DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY endpoint (endpoint),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Webhooks table
        $sql = "CREATE TABLE {$wpdb->prefix}environmental_mobile_api_webhooks (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            url varchar(500) NOT NULL,
            events text NOT NULL,
            secret varchar(255) DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            retry_count int(11) DEFAULT 0,
            last_success datetime DEFAULT NULL,
            last_error text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Device registration table
        $sql = "CREATE TABLE {$wpdb->prefix}environmental_mobile_api_devices (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            device_id varchar(100) NOT NULL,
            device_type varchar(20) DEFAULT NULL,
            device_name varchar(255) DEFAULT NULL,
            app_version varchar(50) DEFAULT NULL,
            os_version varchar(50) DEFAULT NULL,
            push_token varchar(500) DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            registered_at datetime DEFAULT CURRENT_TIMESTAMP,
            last_active datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_device (user_id, device_id),
            KEY device_id (device_id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create default options
     */
    private function create_default_options() {
        $default_settings = array(
            'jwt_expiration' => 3600, // 1 hour
            'refresh_token_expiration' => 604800, // 1 week
            'rate_limit_requests' => 1000,
            'rate_limit_window' => 3600, // 1 hour
            'enable_logging' => true,
            'log_retention_days' => 30,
            'cache_ttl' => 300, // 5 minutes
            'webhook_timeout' => 30,
            'webhook_retry_attempts' => 3
        );
        
        add_option('environmental_mobile_api_settings', $default_settings);
        add_option('environmental_mobile_api_version', ENVIRONMENTAL_MOBILE_API_VERSION);
        add_option('environmental_mobile_api_cors_origins', array('*'));
    }
    
    /**
     * Generate JWT secret
     */
    private function generate_jwt_secret() {
        if (!get_option('environmental_mobile_api_jwt_secret')) {
            $secret = wp_generate_password(64, true, true);
            update_option('environmental_mobile_api_jwt_secret', $secret);
        }
    }
    
    /**
     * Cleanup expired tokens
     */
    public function cleanup_expired_tokens() {
        global $wpdb;
        
        $wpdb->query(
            "DELETE FROM {$wpdb->prefix}environmental_mobile_api_tokens 
             WHERE expires_at < NOW() OR is_revoked = 1"
        );
        
        // Clean up old logs
        $settings = get_option('environmental_mobile_api_settings', array());
        $retention_days = isset($settings['log_retention_days']) ? $settings['log_retention_days'] : 30;
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}environmental_mobile_api_logs 
             WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $retention_days
        ));
        
        // Clean up old rate limit entries
        $wpdb->query(
            "DELETE FROM {$wpdb->prefix}environmental_mobile_api_rate_limits 
             WHERE window_start < DATE_SUB(NOW(), INTERVAL 1 DAY)"
        );
    }
}

// Initialize the plugin
Environmental_Mobile_API::get_instance();
