<?php
/**
 * Plugin Name: Environmental Item Exchange Platform
 * Plugin URI: https://moitruong.com/plugins/item-exchange
 * Description: Advanced item exchange platform with geolocation, AI matching, real-time messaging, and comprehensive environmental impact tracking for sustainable community sharing.
 * Version: 2.0.0
 * Author: Environmental Platform Team
 * License: GPL v2 or later
 * Text Domain: environmental-item-exchange
 * Domain Path: /languages
 * 
 * @package EnvironmentalItemExchange
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EIE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EIE_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('EIE_PLUGIN_VERSION', '2.0.0');
define('EIE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Environmental Item Exchange Class
 */
class EnvironmentalItemExchange {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
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
        $this->init();
    }
    
    /**
     * Initialize plugin
     */
    private function init() {
        // Load plugin files
        $this->load_dependencies();
          // Initialize hooks
        add_action('init', array($this, 'init_plugin'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_eie_exchange_action', array($this, 'handle_ajax_actions'));
        add_action('wp_ajax_nopriv_eie_exchange_action', array($this, 'handle_ajax_actions'));
        add_action('wp_ajax_eie_search_exchanges', array($this, 'handle_search_exchanges'));
        add_action('wp_ajax_nopriv_eie_search_exchanges', array($this, 'handle_search_exchanges'));
        add_action('wp_ajax_eie_save_exchange', array($this, 'handle_save_exchange'));
        add_action('wp_ajax_eie_contact_owner', array($this, 'handle_contact_owner'));
        add_action('wp_ajax_eie_submit_rating', array($this, 'handle_submit_rating'));
        add_action('wp_ajax_eie_get_dashboard_data', array($this, 'handle_get_dashboard_data'));
        
        // Template filters
        add_filter('template_include', array($this, 'template_loader'));
        
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Custom query vars
        add_filter('query_vars', array($this, 'add_query_vars'));
        
        // Rewrite rules
        add_action('init', array($this, 'add_rewrite_rules'));
        
        // Plugin activation/deactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once EIE_PLUGIN_PATH . 'includes/class-database-setup.php';
        require_once EIE_PLUGIN_PATH . 'includes/class-database-manager.php';
        require_once EIE_PLUGIN_PATH . 'includes/class-geolocation.php';
        require_once EIE_PLUGIN_PATH . 'includes/class-matching-engine.php';
        require_once EIE_PLUGIN_PATH . 'includes/class-messaging-system.php';
        require_once EIE_PLUGIN_PATH . 'includes/class-rating-system.php';
        require_once EIE_PLUGIN_PATH . 'includes/class-analytics.php';
        require_once EIE_PLUGIN_PATH . 'includes/class-notifications.php';
        require_once EIE_PLUGIN_PATH . 'includes/class-admin-dashboard.php';
        require_once EIE_PLUGIN_PATH . 'includes/class-api-endpoints.php';
        require_once EIE_PLUGIN_PATH . 'includes/class-mobile-app.php';
        require_once EIE_PLUGIN_PATH . 'includes/class-frontend-templates.php';
    }
    
    /**
     * Initialize plugin components
     */
    public function init_plugin() {
        // Load text domain
        load_plugin_textdomain('environmental-item-exchange', false, dirname(EIE_PLUGIN_BASENAME) . '/languages');
          // Initialize components
        EIE_Database_Manager::get_instance();
        EIE_Geolocation::get_instance();
        EIE_Matching_Engine::get_instance();
        EIE_Messaging_System::get_instance();
        EIE_Rating_System::get_instance();
        EIE_Analytics::get_instance();
        EIE_Notifications::get_instance();
        EIE_API_Endpoints::get_instance();
        EIE_Mobile_App::get_instance();
        Environmental_Item_Exchange_Frontend_Templates::get_instance();
        
        // Add custom post type and taxonomy support
        $this->enhance_existing_post_types();
        
        // Custom shortcodes
        add_shortcode('exchange_map', array($this, 'exchange_map_shortcode'));
        add_shortcode('exchange_search', array($this, 'exchange_search_shortcode'));
        add_shortcode('user_exchanges', array($this, 'user_exchanges_shortcode'));
        add_shortcode('exchange_stats', array($this, 'exchange_stats_shortcode'));
    }
    
    /**
     * Enhance existing post types and taxonomies
     */
    private function enhance_existing_post_types() {
        // Add enhanced meta fields to existing item_exchange post type
        add_action('add_meta_boxes', array($this, 'add_enhanced_meta_boxes'));
        add_action('save_post_item_exchange', array($this, 'save_enhanced_meta_data'));
        
        // Enhance existing taxonomies
        add_action('exchange_type_add_form_fields', array($this, 'add_taxonomy_fields'));
        add_action('exchange_type_edit_form_fields', array($this, 'edit_taxonomy_fields'));
        add_action('created_exchange_type', array($this, 'save_taxonomy_fields'));
        add_action('edited_exchange_type', array($this, 'save_taxonomy_fields'));
    }
    
    /**
     * Add enhanced meta boxes
     */
    public function add_enhanced_meta_boxes() {
        add_meta_box(
            'eie_advanced_options',
            __('Advanced Exchange Options', 'environmental-item-exchange'),
            array($this, 'render_advanced_options_meta_box'),
            'item_exchange',
            'normal',
            'high'
        );
        
        add_meta_box(
            'eie_geolocation',
            __('Location & Delivery', 'environmental-item-exchange'),
            array($this, 'render_geolocation_meta_box'),
            'item_exchange',
            'side',
            'default'
        );
        
        add_meta_box(
            'eie_analytics',
            __('Exchange Analytics', 'environmental-item-exchange'),
            array($this, 'render_analytics_meta_box'),
            'item_exchange',
            'side',
            'default'
        );
    }
      /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        if (is_singular('item_exchange') || is_post_type_archive('item_exchange') || is_tax(array('exchange_type', 'exchange_category'))) {
            // Main frontend JavaScript
            wp_enqueue_script('eie-frontend', EIE_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), EIE_PLUGIN_VERSION, true);
            
            // Legacy scripts for compatibility
            wp_enqueue_script('eie-main', EIE_PLUGIN_URL . 'assets/js/exchange.js', array('jquery'), EIE_PLUGIN_VERSION, true);
            wp_enqueue_script('eie-geolocation', EIE_PLUGIN_URL . 'assets/js/geolocation.js', array('jquery'), EIE_PLUGIN_VERSION, true);
            wp_enqueue_script('eie-messaging', EIE_PLUGIN_URL . 'assets/js/messaging.js', array('jquery'), EIE_PLUGIN_VERSION, true);
            
            // Main frontend styles
            wp_enqueue_style('eie-frontend', EIE_PLUGIN_URL . 'assets/css/frontend.css', array(), EIE_PLUGIN_VERSION);
            
            // Legacy styles for compatibility
            wp_enqueue_style('eie-main', EIE_PLUGIN_URL . 'assets/css/exchange.css', array(), EIE_PLUGIN_VERSION);
            wp_enqueue_style('eie-responsive', EIE_PLUGIN_URL . 'assets/css/responsive.css', array(), EIE_PLUGIN_VERSION);
            
            // Google Maps API (if enabled)
            if (get_option('eie_google_maps_api_key')) {
                wp_enqueue_script('google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . get_option('eie_google_maps_api_key') . '&libraries=places', array(), null, true);
            }
            
            // Localize script
            wp_localize_script('eie-frontend', 'eie_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('eie_nonce'),
                'user_id' => get_current_user_id(),
                'is_logged_in' => is_user_logged_in(),
                'messages' => array(
                    'success' => __('Success!', 'environmental-item-exchange'),
                    'error' => __('An error occurred. Please try again.', 'environmental-item-exchange'),
                    'confirm_delete' => __('Are you sure you want to delete this?', 'environmental-item-exchange'),
                    'login_required' => __('Please log in to perform this action.', 'environmental-item-exchange'),
                    'saved' => __('Item saved successfully!', 'environmental-item-exchange'),
                    'unsaved' => __('Item removed from saved!', 'environmental-item-exchange'),
                    'message_sent' => __('Message sent successfully!', 'environmental-item-exchange'),
                    'rating_submitted' => __('Rating submitted successfully!', 'environmental-item-exchange'),
                    'loading' => __('Loading...', 'environmental-item-exchange'),
                )
            ));
        }
    }
      /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        
        if ($post_type === 'item_exchange' || strpos($hook, 'environmental-exchange') !== false) {
            // Admin dashboard scripts
            wp_enqueue_script('eie-admin-dashboard', EIE_PLUGIN_URL . 'assets/js/admin-dashboard.js', array('jquery', 'wp-color-picker'), EIE_PLUGIN_VERSION, true);
            wp_enqueue_style('eie-admin-dashboard', EIE_PLUGIN_URL . 'assets/css/admin-dashboard.css', array('wp-color-picker'), EIE_PLUGIN_VERSION);
            
            // Legacy admin scripts
            wp_enqueue_script('eie-admin', EIE_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'wp-color-picker'), EIE_PLUGIN_VERSION, true);
            wp_enqueue_style('eie-admin', EIE_PLUGIN_URL . 'assets/css/admin.css', array('wp-color-picker'), EIE_PLUGIN_VERSION);
            
            wp_localize_script('eie-admin-dashboard', 'eie_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('eie_admin_nonce'),
                'messages' => array(
                    'success' => __('Success!', 'environmental-item-exchange'),
                    'error' => __('An error occurred. Please try again.', 'environmental-item-exchange'),
                    'confirm_delete' => __('Are you sure you want to delete this?', 'environmental-item-exchange'),
                    'loading' => __('Loading...', 'environmental-item-exchange'),
                )
            ));
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Exchange Platform', 'environmental-item-exchange'),
            __('Exchange Platform', 'environmental-item-exchange'),
            'manage_options',
            'environmental-exchange',
            array($this, 'admin_dashboard_page'),
            'dashicons-randomize',
            30
        );
        
        add_submenu_page(
            'environmental-exchange',
            __('Dashboard', 'environmental-item-exchange'),
            __('Dashboard', 'environmental-item-exchange'),
            'manage_options',
            'environmental-exchange',
            array($this, 'admin_dashboard_page')
        );
        
        add_submenu_page(
            'environmental-exchange',
            __('Analytics', 'environmental-item-exchange'),
            __('Analytics', 'environmental-item-exchange'),
            'manage_options',
            'exchange-analytics',
            array($this, 'analytics_page')
        );
        
        add_submenu_page(
            'environmental-exchange',
            __('Messaging', 'environmental-item-exchange'),
            __('Messaging', 'environmental-item-exchange'),
            'manage_options',
            'exchange-messaging',
            array($this, 'messaging_page')
        );
        
        add_submenu_page(
            'environmental-exchange',
            __('Geolocation', 'environmental-item-exchange'),
            __('Geolocation', 'environmental-item-exchange'),
            'manage_options',
            'exchange-geolocation',
            array($this, 'geolocation_page')
        );
        
        add_submenu_page(
            'environmental-exchange',
            __('Settings', 'environmental-item-exchange'),
            __('Settings', 'environmental-item-exchange'),
            'manage_options',
            'exchange-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Admin dashboard page
     */
    public function admin_dashboard_page() {
        $dashboard = new EIE_Admin_Dashboard();
        $dashboard->render();
    }
    
    /**
     * Analytics page
     */
    public function analytics_page() {
        include EIE_PLUGIN_PATH . 'admin/analytics.php';
    }
    
    /**
     * Messaging page
     */
    public function messaging_page() {
        include EIE_PLUGIN_PATH . 'admin/messaging.php';
    }
    
    /**
     * Geolocation page
     */
    public function geolocation_page() {
        include EIE_PLUGIN_PATH . 'admin/geolocation.php';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        include EIE_PLUGIN_PATH . 'admin/settings.php';
    }
      /**
     * Load custom templates
     */
    public function template_loader($template) {
        if (is_singular('item_exchange')) {
            $custom_template = EIE_PLUGIN_PATH . 'templates/single-item_exchange.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        if (is_post_type_archive('item_exchange') || is_tax(array('exchange_type', 'exchange_category'))) {
            $custom_template = EIE_PLUGIN_PATH . 'templates/archive-item_exchange.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        return $template;
    }

    /**
     * Add custom query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'exchange_action';
        $vars[] = 'exchange_id';
        $vars[] = 'user_location';
        $vars[] = 'radius';
        return $vars;
    }
    
    /**
     * Add rewrite rules
     */
    public function add_rewrite_rules() {
        add_rewrite_rule('^exchange/map/?$', 'index.php?pagename=exchange-map', 'top');
        add_rewrite_rule('^exchange/search/?$', 'index.php?pagename=exchange-search', 'top');
        add_rewrite_rule('^exchange/message/([0-9]+)/?$', 'index.php?pagename=exchange-message&exchange_id=$matches[1]', 'top');
    }
    
    /**
     * Handle AJAX actions
     */
    public function handle_ajax_actions() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'eie_nonce')) {
            wp_die('Security check failed');
        }
        
        $action = sanitize_text_field($_POST['action_type']);
        
        switch ($action) {
            case 'save_exchange':
                $this->ajax_save_exchange();
                break;
                
            case 'send_message':
                $this->ajax_send_message();
                break;
                
            case 'update_location':
                $this->ajax_update_location();
                break;
                
            case 'get_nearby_exchanges':
                $this->ajax_get_nearby_exchanges();
                break;
                
            case 'rate_exchange':
                $this->ajax_rate_exchange();
                break;
                
            default:
                wp_send_json_error('Invalid action');
        }
    }
    
    /**
     * Exchange map shortcode
     */
    public function exchange_map_shortcode($atts) {
        $atts = shortcode_atts(array(
            'height' => '400px',
            'zoom' => '12',
            'category' => '',
            'type' => '',
            'radius' => '10'
        ), $atts);
        
        ob_start();
        include EIE_PLUGIN_PATH . 'templates/exchange-map.php';
        return ob_get_clean();
    }
    
    /**
     * Exchange search shortcode
     */
    public function exchange_search_shortcode($atts) {
        $atts = shortcode_atts(array(
            'layout' => 'grid',
            'posts_per_page' => '12',
            'show_filters' => 'true',
            'show_map' => 'false'
        ), $atts);
        
        ob_start();
        include EIE_PLUGIN_PATH . 'templates/exchange-search.php';
        return ob_get_clean();
    }
    
    /**
     * User exchanges shortcode
     */
    public function user_exchanges_shortcode($atts) {
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id(),
            'status' => 'all',
            'layout' => 'list'
        ), $atts);
        
        ob_start();
        include EIE_PLUGIN_PATH . 'templates/user-exchanges.php';
        return ob_get_clean();
    }
    
    /**
     * Exchange stats shortcode
     */
    public function exchange_stats_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => 'summary',
            'period' => 'all_time'
        ), $atts);
        
        ob_start();
        include EIE_PLUGIN_PATH . 'templates/exchange-stats.php';
        return ob_get_clean();
    }
      /**
     * Plugin activation
     */
    public function activate() {
        // Run database setup
        EIE_Database_Setup::setup();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Schedule cron jobs
        $this->schedule_cron_jobs();
        
        // Set activation flag
        update_option('eie_plugin_activated', true);
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled hooks
        wp_clear_scheduled_hook('eie_cleanup_expired_exchanges');
        wp_clear_scheduled_hook('eie_send_digest_notifications');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create enhanced database tables
     */
    private function create_enhanced_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
          // Enhanced messaging table
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
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
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
        
        dbDelta($sql);
        
        // Enhanced geolocation table
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
        
        // Enhanced ratings table
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
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY exchange_id (exchange_id),
            KEY rated_user_id (rated_user_id),
            KEY rating (rating),
            UNIQUE KEY unique_rating (exchange_id, rater_id)
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
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY exchange_id (exchange_id),
            KEY user_id (user_id),
            KEY action_type (action_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Set default plugin options
     */
    private function set_default_options() {
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
        );
        
        foreach ($defaults as $option => $value) {
            if (get_option($option) === false) {
                update_option($option, $value);
            }
        }
    }
    
    /**
     * Schedule cron jobs
     */
    private function schedule_cron_jobs() {
        if (!wp_next_scheduled('eie_cleanup_expired_exchanges')) {
            wp_schedule_event(time(), 'daily', 'eie_cleanup_expired_exchanges');
        }
        
        if (!wp_next_scheduled('eie_send_digest_notifications')) {
            wp_schedule_event(time(), 'weekly', 'eie_send_digest_notifications');
        }
        
        if (!wp_next_scheduled('eie_update_analytics')) {
            wp_schedule_event(time(), 'hourly', 'eie_update_analytics');
        }
    }
      // AJAX Methods
    
    /**
     * Handle search exchanges AJAX request
     */
    public function handle_search_exchanges() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'eie_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $frontend_templates = Environmental_Item_Exchange_Frontend_Templates::get_instance();
        $frontend_templates->ajax_search_exchanges();
    }
    
    /**
     * Handle save exchange AJAX request
     */
    public function handle_save_exchange() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'eie_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $frontend_templates = Environmental_Item_Exchange_Frontend_Templates::get_instance();
        $frontend_templates->ajax_save_exchange();
    }
    
    /**
     * Handle contact owner AJAX request
     */
    public function handle_contact_owner() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'eie_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $frontend_templates = Environmental_Item_Exchange_Frontend_Templates::get_instance();
        $frontend_templates->ajax_contact_owner();
    }
    
    /**
     * Handle submit rating AJAX request
     */
    public function handle_submit_rating() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'eie_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $frontend_templates = Environmental_Item_Exchange_Frontend_Templates::get_instance();
        $frontend_templates->ajax_submit_rating();
    }
    
    /**
     * Handle get dashboard data AJAX request
     */
    public function handle_get_dashboard_data() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'eie_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $frontend_templates = Environmental_Item_Exchange_Frontend_Templates::get_instance();
        $frontend_templates->ajax_get_dashboard_data();
    }
    
    private function ajax_save_exchange() {
        $exchange_id = intval($_POST['exchange_id']);
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        // Save to favorites
        $result = EIE_Database_Manager::save_exchange($user_id, $exchange_id);
        
        if ($result) {
            wp_send_json_success('Exchange saved successfully');
        } else {
            wp_send_json_error('Failed to save exchange');
        }
    }
    
    private function ajax_send_message() {
        $exchange_id = intval($_POST['exchange_id']);
        $receiver_id = intval($_POST['receiver_id']);
        $message = sanitize_textarea_field($_POST['message']);
        $sender_id = get_current_user_id();
        
        if (!$sender_id) {
            wp_send_json_error('User not logged in');
        }
        
        $messaging = EIE_Messaging_System::get_instance();
        $result = $messaging->send_message($exchange_id, $sender_id, $receiver_id, $message);
        
        if ($result) {
            wp_send_json_success('Message sent successfully');
        } else {
            wp_send_json_error('Failed to send message');
        }
    }
    
    private function ajax_update_location() {
        $exchange_id = intval($_POST['exchange_id']);
        $latitude = floatval($_POST['latitude']);
        $longitude = floatval($_POST['longitude']);
        $address = sanitize_text_field($_POST['address']);
        
        $geolocation = EIE_Geolocation::get_instance();
        $result = $geolocation->update_exchange_location($exchange_id, $latitude, $longitude, $address);
        
        if ($result) {
            wp_send_json_success('Location updated successfully');
        } else {
            wp_send_json_error('Failed to update location');
        }
    }
    
    private function ajax_get_nearby_exchanges() {
        $latitude = floatval($_POST['latitude']);
        $longitude = floatval($_POST['longitude']);
        $radius = intval($_POST['radius']) ?: 10;
        
        $geolocation = EIE_Geolocation::get_instance();
        $exchanges = $geolocation->get_nearby_exchanges($latitude, $longitude, $radius);
        
        wp_send_json_success($exchanges);
    }
    
    private function ajax_rate_exchange() {
        $exchange_id = intval($_POST['exchange_id']);
        $rated_user_id = intval($_POST['rated_user_id']);
        $rating = intval($_POST['rating']);
        $review = sanitize_textarea_field($_POST['review']);
        $rater_id = get_current_user_id();
        
        if (!$rater_id) {
            wp_send_json_error('User not logged in');
        }
        
        $rating_system = EIE_Rating_System::get_instance();
        $result = $rating_system->add_rating($exchange_id, $rater_id, $rated_user_id, $rating, $review);
        
        if ($result) {
            wp_send_json_success('Rating submitted successfully');
        } else {
            wp_send_json_error('Failed to submit rating');
        }
    }
}

// Initialize the plugin
function environmental_item_exchange_init() {
    return EnvironmentalItemExchange::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'environmental_item_exchange_init');

// Activation and deactivation hooks
register_activation_hook(__FILE__, array('EnvironmentalItemExchange', 'activate'));
register_deactivation_hook(__FILE__, array('EnvironmentalItemExchange', 'deactivate'));

?>
