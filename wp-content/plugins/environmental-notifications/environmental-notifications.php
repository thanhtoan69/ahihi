<?php
/**
 * Plugin Name: Environmental Notifications & Messaging System
 * Plugin URI: https://environmentalplatform.local
 * Description: Phase 54 - Real-time notification system with push notifications, email preferences, in-app messaging, and notification analytics for the Environmental Platform
 * Version: 1.0.0
 * Author: Environmental Platform Team
 * License: GPL v2 or later
 * Text Domain: environmental-notifications
 * Requires Plugins: environmental-platform-core
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EN_VERSION', '1.0.0');
define('EN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EN_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once EN_PLUGIN_DIR . 'includes/class-notification-engine.php';
require_once EN_PLUGIN_DIR . 'includes/class-push-notifications.php';
require_once EN_PLUGIN_DIR . 'includes/class-email-preferences.php';
require_once EN_PLUGIN_DIR . 'includes/class-messaging-system.php';
require_once EN_PLUGIN_DIR . 'includes/class-notification-analytics.php';
require_once EN_PLUGIN_DIR . 'includes/class-realtime-handler.php';
require_once EN_PLUGIN_DIR . 'includes/class-notification-templates.php';
require_once EN_PLUGIN_DIR . 'includes/class-ajax-handlers.php';
require_once EN_PLUGIN_DIR . 'includes/class-admin-interface.php';

/**
 * Main plugin class for Environmental Notifications & Messaging System
 */
class EnvironmentalNotifications {
    
    private static $instance = null;
    private $notification_engine;
    private $push_notifications;
    private $email_preferences;
    private $messaging_system;
    private $notification_analytics;
    private $realtime_handler;
    private $notification_templates;
    private $ajax_handlers;
    private $admin_interface;

    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Check dependencies
        if (!$this->check_dependencies()) {
            add_action('admin_notices', array($this, 'dependency_notice'));
            return;
        }

        // Load text domain
        load_plugin_textdomain('environmental-notifications', false, dirname(EN_PLUGIN_BASENAME) . '/languages/');

        // Initialize components
        $this->init_components();

        // Setup hooks
        $this->setup_hooks();

        // Register REST API routes
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }

    /**
     * Check plugin dependencies
     */
    private function check_dependencies() {
        return class_exists('EnvironmentalPlatformCore');
    }

    /**
     * Show dependency notice
     */
    public function dependency_notice() {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('Environmental Notifications plugin requires Environmental Platform Core to be active.', 'environmental-notifications');
        echo '</p></div>';
    }    /**
     * Initialize components
     */
    private function init_components() {
        $this->notification_engine = Environmental_Notification_Engine::get_instance();
        $this->push_notifications = Environmental_Push_Notifications::get_instance();
        $this->email_preferences = Environmental_Email_Preferences::get_instance();
        $this->messaging_system = Environmental_Messaging_System::get_instance();
        $this->notification_analytics = Environmental_Notification_Analytics::get_instance();
        $this->realtime_handler = Environmental_Realtime_Handler::get_instance();
        $this->notification_templates = Environmental_Notification_Templates::get_instance();
        $this->ajax_handlers = Environmental_AJAX_Handlers::get_instance();
        
        if (is_admin()) {
            $this->admin_interface = Environmental_Admin_Interface::get_instance();
        }
    }    /**
     * Setup hooks
     */
    private function setup_hooks() {
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // User profile hooks
        add_action('show_user_profile', array($this, 'show_notification_preferences'));
        add_action('edit_user_profile', array($this, 'show_notification_preferences'));
        add_action('personal_options_update', array($this, 'save_notification_preferences'));
        add_action('edit_user_profile_update', array($this, 'save_notification_preferences'));

        // Environmental platform hooks
        add_action('environmental_waste_reported', array($this, 'handle_waste_report_notification'));
        add_action('environmental_event_created', array($this, 'handle_event_notification'));
        add_action('environmental_achievement_earned', array($this, 'handle_achievement_notification'));
        add_action('environmental_forum_post_created', array($this, 'handle_forum_notification'));
        add_action('environmental_petition_signed', array($this, 'handle_petition_notification'));

        // WordPress hooks
        add_action('wp_footer', array($this, 'add_notification_container'));
        add_action('admin_footer', array($this, 'add_notification_container'));
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('environmental-notifications/v1', '/notifications', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_notifications'),
            'permission_callback' => array($this, 'check_permissions')
        ));

        register_rest_route('environmental-notifications/v1', '/notifications/(?P<id>\d+)/read', array(
            'methods' => 'POST',
            'callback' => array($this, 'mark_notification_read'),
            'permission_callback' => array($this, 'check_permissions')
        ));

        register_rest_route('environmental-notifications/v1', '/messages', array(
            'methods' => array('GET', 'POST'),
            'callback' => array($this, 'handle_messages'),
            'permission_callback' => array($this, 'check_permissions')
        ));

        register_rest_route('environmental-notifications/v1', '/push/subscribe', array(
            'methods' => 'POST',
            'callback' => array($this, 'subscribe_push'),
            'permission_callback' => array($this, 'check_permissions')
        ));
    }

    /**
     * Check REST API permissions
     */
    public function check_permissions() {
        return is_user_logged_in();
    }

    /**
     * Plugin activation
     */
    public function activate() {
        $this->create_database_tables();
        $this->set_default_options();
        
        // Schedule notification cleanup
        if (!wp_next_scheduled('en_cleanup_notifications')) {
            wp_schedule_event(time(), 'daily', 'en_cleanup_notifications');
        }

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('en_cleanup_notifications');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     */
    private function create_database_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Notifications table
        $notifications_table = $wpdb->prefix . 'en_notifications';
        $sql_notifications = "CREATE TABLE $notifications_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            type varchar(50) NOT NULL,
            title varchar(255) NOT NULL,
            message text NOT NULL,
            data longtext,
            is_read tinyint(1) DEFAULT 0,
            is_sent tinyint(1) DEFAULT 0,
            priority enum('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
            scheduled_at datetime DEFAULT NULL,
            sent_at datetime DEFAULT NULL,
            read_at datetime DEFAULT NULL,
            expires_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY type (type),
            KEY is_read (is_read),
            KEY priority (priority),
            KEY scheduled_at (scheduled_at),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Messages table
        $messages_table = $wpdb->prefix . 'en_messages';
        $sql_messages = "CREATE TABLE $messages_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            sender_id bigint(20) NOT NULL,
            recipient_id bigint(20) NOT NULL,
            conversation_id varchar(100) NOT NULL,
            subject varchar(255) DEFAULT NULL,
            message text NOT NULL,
            attachments longtext,
            is_read tinyint(1) DEFAULT 0,
            is_deleted_by_sender tinyint(1) DEFAULT 0,
            is_deleted_by_recipient tinyint(1) DEFAULT 0,
            read_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY sender_id (sender_id),
            KEY recipient_id (recipient_id),
            KEY conversation_id (conversation_id),
            KEY is_read (is_read),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Push subscriptions table
        $push_subscriptions_table = $wpdb->prefix . 'en_push_subscriptions';
        $sql_push = "CREATE TABLE $push_subscriptions_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            endpoint text NOT NULL,
            p256dh varchar(255) NOT NULL,
            auth varchar(255) NOT NULL,
            device_type varchar(50) DEFAULT 'web',
            user_agent text,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY is_active (is_active),
            UNIQUE KEY unique_subscription (user_id, endpoint(100))
        ) $charset_collate;";

        // Notification analytics table
        $analytics_table = $wpdb->prefix . 'en_notification_analytics';
        $sql_analytics = "CREATE TABLE $analytics_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            notification_id bigint(20),
            user_id bigint(20) NOT NULL,
            event_type varchar(50) NOT NULL,
            notification_type varchar(50) NOT NULL,
            channel varchar(50) NOT NULL,
            platform varchar(50) DEFAULT 'web',
            device_info text,
            response_time int DEFAULT NULL,
            additional_data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY notification_id (notification_id),
            KEY user_id (user_id),
            KEY event_type (event_type),
            KEY notification_type (notification_type),
            KEY channel (channel),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Email preferences table
        $preferences_table = $wpdb->prefix . 'en_email_preferences';
        $sql_preferences = "CREATE TABLE $preferences_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            preference_key varchar(100) NOT NULL,
            preference_value text NOT NULL,
            is_enabled tinyint(1) DEFAULT 1,
            frequency varchar(50) DEFAULT 'immediate',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY preference_key (preference_key),
            UNIQUE KEY unique_user_preference (user_id, preference_key)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_notifications);
        dbDelta($sql_messages);
        dbDelta($sql_push);
        dbDelta($sql_analytics);
        dbDelta($sql_preferences);
    }

    /**
     * Set default options
     */
    private function set_default_options() {
        $default_options = array(
            'en_real_time_enabled' => true,
            'en_push_notifications_enabled' => true,
            'en_email_notifications_enabled' => true,
            'en_in_app_notifications_enabled' => true,
            'en_notification_retention_days' => 30,
            'en_max_notifications_per_user' => 100,
            'en_batch_size' => 50,
            'en_vapid_public_key' => '',
            'en_vapid_private_key' => '',
            'en_notification_sound_enabled' => true,
            'en_auto_cleanup_enabled' => true,
            'en_analytics_enabled' => true,
            'en_rate_limiting_enabled' => true,
            'en_rate_limit_per_minute' => 10
        );

        foreach ($default_options as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Only load for logged-in users
        if (!is_user_logged_in()) {
            return;
        }

        // Frontend JavaScript
        wp_enqueue_script(
            'environmental-notifications-frontend',
            EN_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            EN_VERSION,
            true
        );

        // Frontend CSS
        wp_enqueue_style(
            'environmental-notifications-frontend',
            EN_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            EN_VERSION
        );

        // Service Worker registration script
        wp_add_inline_script('environmental-notifications-frontend', 
            "if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('" . EN_PLUGIN_URL . "assets/js/sw.js')
                .then(function(registration) {
                    console.log('SW registered: ', registration);
                }).catch(function(registrationError) {
                    console.log('SW registration failed: ', registrationError);
                });
            }"
        );

        // Localize script with configuration
        wp_localize_script('environmental-notifications-frontend', 'enFrontend', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('environmental-notifications/v1/'),
            'nonce' => wp_create_nonce('en_frontend_nonce'),
            'user_id' => get_current_user_id(),
            'plugin_url' => EN_PLUGIN_URL,
            'real_time_enabled' => get_option('en_real_time_enabled', true),
            'sound_enabled' => get_option('en_notification_sound_enabled', true),
            'vapid_public_key' => get_option('en_vapid_public_key', ''),
            'icons' => array(
                'alert' => EN_PLUGIN_URL . 'assets/images/icon-alert.svg',
                'info' => EN_PLUGIN_URL . 'assets/images/icon-info.svg',
                'warning' => EN_PLUGIN_URL . 'assets/images/icon-warning.svg',
                'message' => EN_PLUGIN_URL . 'assets/images/icon-message.svg',
                'success' => EN_PLUGIN_URL . 'assets/images/icon-success.svg',
                'environment' => EN_PLUGIN_URL . 'assets/images/icon-environment.svg'
            ),
            'strings' => array(
                'new_notification' => __('New Notification', 'environmental-notifications'),
                'mark_as_read' => __('Mark as Read', 'environmental-notifications'),
                'mark_all_read' => __('Mark All as Read', 'environmental-notifications'),
                'view_all' => __('View All Notifications', 'environmental-notifications'),
                'no_notifications' => __('No notifications', 'environmental-notifications'),
                'loading' => __('Loading...', 'environmental-notifications'),
                'error' => __('Error loading notifications', 'environmental-notifications'),
                'send_message' => __('Send Message', 'environmental-notifications'),
                'type_message' => __('Type your message...', 'environmental-notifications'),
                'message_sent' => __('Message sent successfully', 'environmental-notifications'),
                'connection_lost' => __('Connection lost. Trying to reconnect...', 'environmental-notifications'),
                'connection_restored' => __('Connection restored', 'environmental-notifications'),
                'push_permission_denied' => __('Push notifications blocked. Please enable in browser settings.', 'environmental-notifications'),
                'push_not_supported' => __('Push notifications are not supported in this browser.', 'environmental-notifications')
            )
        ));
    }    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'environmental-notifications') === false) {
            return;
        }

        // Admin JavaScript
        wp_enqueue_script(
            'environmental-notifications-admin',
            EN_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker'),
            EN_VERSION,
            true
        );

        // Admin CSS
        wp_enqueue_style(
            'environmental-notifications-admin',
            EN_PLUGIN_URL . 'assets/css/admin.css',
            array('wp-color-picker'),
            EN_VERSION
        );

        // Chart.js for analytics
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
            array(),
            '3.9.1',
            true
        );

        // Moment.js for date handling
        wp_enqueue_script(
            'moment',
            'https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js',
            array(),
            '2.29.4',
            true
        );

        // Chart.js date adapter
        wp_enqueue_script(
            'chartjs-adapter-moment',
            'https://cdn.jsdelivr.net/npm/chartjs-adapter-moment@1.0.0/dist/chartjs-adapter-moment.min.js',
            array('chartjs', 'moment'),
            '1.0.0',
            true
        );

        // Localize admin script
        wp_localize_script('environmental-notifications-admin', 'enAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('environmental-notifications/v1/'),
            'nonce' => wp_create_nonce('en_admin_nonce'),
            'plugin_url' => EN_PLUGIN_URL,
            'current_user_id' => get_current_user_id(),
            'icons' => array(
                'alert' => EN_PLUGIN_URL . 'assets/images/icon-alert.svg',
                'info' => EN_PLUGIN_URL . 'assets/images/icon-info.svg',
                'warning' => EN_PLUGIN_URL . 'assets/images/icon-warning.svg',
                'message' => EN_PLUGIN_URL . 'assets/images/icon-message.svg',
                'success' => EN_PLUGIN_URL . 'assets/images/icon-success.svg',
                'environment' => EN_PLUGIN_URL . 'assets/images/icon-environment.svg'
            ),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this item?', 'environmental-notifications'),
                'confirm_bulk_delete' => __('Are you sure you want to delete the selected items?', 'environmental-notifications'),
                'test_notification_sent' => __('Test notification sent successfully', 'environmental-notifications'),
                'settings_saved' => __('Settings saved successfully', 'environmental-notifications'),
                'template_saved' => __('Template saved successfully', 'environmental-notifications'),
                'error_occurred' => __('An error occurred. Please try again.', 'environmental-notifications'),
                'loading' => __('Loading...', 'environmental-notifications'),
                'no_data' => __('No data available', 'environmental-notifications'),
                'select_template' => __('Select a template...', 'environmental-notifications'),
                'preview_template' => __('Preview Template', 'environmental-notifications'),
                'send_test' => __('Send Test Notification', 'environmental-notifications'),
                'export_data' => __('Export Data', 'environmental-notifications'),
                'import_data' => __('Import Data', 'environmental-notifications'),
                'date_format' => get_option('date_format') . ' ' . get_option('time_format')
            )
        ));
    }/**
     * Show notification preferences on user profile
     */
    public function show_notification_preferences($user) {
        $this->email_preferences->display_user_preferences($user);
    }

    /**
     * Save notification preferences
     */
    public function save_notification_preferences($user_id) {
        $this->email_preferences->save_user_preferences($user_id);
    }

    /**
     * Handle environmental notifications
     */
    public function handle_waste_report_notification($data) {
        $this->notification_engine->create_notification(array(
            'type' => 'waste_report',
            'title' => __('Waste Report Submitted', 'environmental-notifications'),
            'message' => sprintf(__('Your waste report for %s has been submitted successfully.', 'environmental-notifications'), $data['location']),
            'user_id' => $data['user_id'],
            'data' => $data
        ));
    }

    public function handle_event_notification($data) {
        $this->notification_engine->create_notification(array(
            'type' => 'environmental_event',
            'title' => __('New Environmental Event', 'environmental-notifications'),
            'message' => sprintf(__('New event: %s', 'environmental-notifications'), $data['title']),
            'user_id' => $data['user_id'],
            'data' => $data
        ));
    }

    public function handle_achievement_notification($data) {
        $this->notification_engine->create_notification(array(
            'type' => 'achievement',
            'title' => __('Achievement Unlocked!', 'environmental-notifications'),
            'message' => sprintf(__('Congratulations! You earned the "%s" achievement.', 'environmental-notifications'), $data['achievement_name']),
            'user_id' => $data['user_id'],
            'priority' => 'high',
            'data' => $data
        ));
    }

    public function handle_forum_notification($data) {
        $this->notification_engine->create_notification(array(
            'type' => 'forum_post',
            'title' => __('New Forum Post', 'environmental-notifications'),
            'message' => sprintf(__('New post in %s', 'environmental-notifications'), $data['forum_name']),
            'user_id' => $data['user_id'],
            'data' => $data
        ));
    }

    public function handle_petition_notification($data) {
        $this->notification_engine->create_notification(array(
            'type' => 'petition_signed',
            'title' => __('Petition Signed', 'environmental-notifications'),
            'message' => sprintf(__('Thank you for signing "%s"', 'environmental-notifications'), $data['petition_title']),
            'user_id' => $data['user_id'],
            'data' => $data
        ));
    }

    /**
     * Add notification container to footer
     */
    public function add_notification_container() {
        if (!is_user_logged_in()) {
            return;
        }

        echo '<div id="en-notification-container"></div>';
        echo '<div id="en-message-container"></div>';
    }

    /**
     * REST API Callbacks
     */
    public function get_notifications($request) {
        return $this->notification_engine->get_user_notifications(get_current_user_id(), $request->get_params());
    }

    public function mark_notification_read($request) {
        return $this->notification_engine->mark_as_read($request['id'], get_current_user_id());
    }

    public function handle_messages($request) {
        if ($request->get_method() === 'GET') {
            return $this->messaging_system->get_user_messages(get_current_user_id(), $request->get_params());
        } else {
            return $this->messaging_system->send_message($request->get_json_params());
        }
    }

    public function subscribe_push($request) {
        return $this->push_notifications->subscribe(get_current_user_id(), $request->get_json_params());
    }
}

// Initialize the plugin
function environmental_notifications() {
    return EnvironmentalNotifications::getInstance();
}

// Start the plugin
environmental_notifications();

// Cleanup scheduled event
add_action('en_cleanup_notifications', function() {
    $notification_engine = Environmental_Notification_Engine::get_instance();
    $notification_engine->cleanup_old_notifications();
});
