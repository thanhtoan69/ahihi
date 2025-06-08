<?php
/**
 * Plugin Name: Environmental Live Chat & Customer Support
 * Plugin URI: https://environmentalplatform.com/plugins/live-chat-support
 * Description: Comprehensive live chat system with automated chatbot, support tickets, FAQ management, and customer support analytics for the Environmental Platform.
 * Version: 1.0.0
 * Author: Environmental Platform Team
 * Author URI: https://environmentalplatform.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: environmental-live-chat
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * 
 * @package Environmental_Live_Chat
 * @version 1.0.0
 * @since Phase 56
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ELC_VERSION', '1.0.0');
define('ELC_PLUGIN_FILE', __FILE__);
define('ELC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ELC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ELC_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Environmental Live Chat Plugin Class
 * 
 * @since 1.0.0
 */
final class Environmental_Live_Chat {
    
    /**
     * Plugin instance
     * 
     * @var Environmental_Live_Chat
     */
    private static $instance = null;
    
    /**
     * Live Chat System
     * 
     * @var ELC_Live_Chat_System
     */
    public $live_chat;
    
    /**
     * Chatbot System
     * 
     * @var ELC_Chatbot_System
     */
    public $chatbot;
    
    /**
     * Support Tickets
     * 
     * @var ELC_Support_Tickets
     */
    public $support_tickets;
    
    /**
     * FAQ Manager
     * 
     * @var ELC_FAQ_Manager
     */
    public $faq_manager;
    
    /**
     * Analytics
     * 
     * @var ELC_Analytics
     */
    public $analytics;
    
    /**
     * Admin Interface
     * 
     * @var ELC_Admin_Interface
     */
    public $admin;
    
    /**
     * REST API
     * 
     * @var ELC_REST_API
     */
    public $api;
    
    /**
     * Get plugin instance
     * 
     * @return Environmental_Live_Chat
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
        $this->init_components();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_footer', array($this, 'add_chat_widget'));
        add_action('rest_api_init', array($this, 'init_rest_api'));
        
        // AJAX hooks
        add_action('wp_ajax_elc_send_message', array($this, 'handle_send_message'));
        add_action('wp_ajax_nopriv_elc_send_message', array($this, 'handle_send_message'));
        add_action('wp_ajax_elc_get_messages', array($this, 'handle_get_messages'));
        add_action('wp_ajax_nopriv_elc_get_messages', array($this, 'handle_get_messages'));
        add_action('wp_ajax_elc_submit_ticket', array($this, 'handle_submit_ticket'));
        add_action('wp_ajax_nopriv_elc_submit_ticket', array($this, 'handle_submit_ticket'));
        add_action('wp_ajax_elc_search_faq', array($this, 'handle_search_faq'));
        add_action('wp_ajax_nopriv_elc_search_faq', array($this, 'handle_search_faq'));
        
        // Shortcodes
        add_shortcode('elc_chat_widget', array($this, 'chat_widget_shortcode'));
        add_shortcode('elc_faq', array($this, 'faq_shortcode'));
        add_shortcode('elc_support_form', array($this, 'support_form_shortcode'));
        add_shortcode('elc_knowledge_base', array($this, 'knowledge_base_shortcode'));
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Load required files
        require_once ELC_PLUGIN_DIR . 'includes/class-live-chat-system.php';
        require_once ELC_PLUGIN_DIR . 'includes/class-chatbot-system.php';
        require_once ELC_PLUGIN_DIR . 'includes/class-support-tickets.php';
        require_once ELC_PLUGIN_DIR . 'includes/class-faq-manager.php';
        require_once ELC_PLUGIN_DIR . 'includes/class-analytics.php';
        require_once ELC_PLUGIN_DIR . 'includes/class-admin-interface.php';
        require_once ELC_PLUGIN_DIR . 'includes/class-rest-api.php';
        
        // Initialize components
        $this->live_chat = new ELC_Live_Chat_System();
        $this->chatbot = new ELC_Chatbot_System();
        $this->support_tickets = new ELC_Support_Tickets();
        $this->faq_manager = new ELC_FAQ_Manager();
        $this->analytics = new ELC_Analytics();
        $this->admin = new ELC_Admin_Interface();
        $this->api = new ELC_REST_API();
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        $this->create_database_tables();
        $this->set_default_options();
        $this->schedule_events();
        
        // Create upload directory for chat attachments
        $upload_dir = wp_upload_dir();
        $chat_dir = $upload_dir['basedir'] . '/environmental-chat';
        if (!file_exists($chat_dir)) {
            wp_mkdir_p($chat_dir);
        }
        
        // Add rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        wp_clear_scheduled_hook('elc_cleanup_old_sessions');
        wp_clear_scheduled_hook('elc_send_daily_reports');
        wp_clear_scheduled_hook('elc_backup_chat_data');
    }
    
    /**
     * Create database tables
     */
    private function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Chat sessions table
        $sql_sessions = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}elc_chat_sessions (
            session_id bigint(20) NOT NULL AUTO_INCREMENT,
            session_key varchar(64) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            visitor_id varchar(64) NOT NULL,
            operator_id bigint(20) DEFAULT NULL,
            status enum('waiting','active','ended','transferred') DEFAULT 'waiting',
            priority enum('low','normal','high','urgent') DEFAULT 'normal',
            department varchar(100) DEFAULT 'general',
            started_at datetime NOT NULL,
            ended_at datetime DEFAULT NULL,
            rating tinyint(1) DEFAULT NULL,
            feedback text DEFAULT NULL,
            user_agent text DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            referrer_url text DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (session_id),
            UNIQUE KEY session_key (session_key),
            KEY user_id (user_id),
            KEY operator_id (operator_id),
            KEY status (status),
            KEY started_at (started_at)
        ) $charset_collate;";
        
        // Chat messages table
        $sql_messages = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}elc_chat_messages (
            message_id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id bigint(20) NOT NULL,
            sender_type enum('user','operator','bot') NOT NULL,
            sender_id bigint(20) DEFAULT NULL,
            message_type enum('text','image','file','system','bot_response') DEFAULT 'text',
            message_content text NOT NULL,
            attachment_url varchar(255) DEFAULT NULL,
            is_read tinyint(1) DEFAULT 0,
            sent_at datetime NOT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (message_id),
            KEY session_id (session_id),
            KEY sender_type (sender_type),
            KEY sent_at (sent_at),
            FOREIGN KEY (session_id) REFERENCES {$wpdb->prefix}elc_chat_sessions(session_id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // Support tickets table
        $sql_tickets = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}elc_support_tickets (
            ticket_id bigint(20) NOT NULL AUTO_INCREMENT,
            ticket_number varchar(20) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            user_email varchar(100) NOT NULL,
            user_name varchar(100) NOT NULL,
            subject varchar(255) NOT NULL,
            description text NOT NULL,
            priority enum('low','normal','high','urgent') DEFAULT 'normal',
            status enum('open','in_progress','waiting','resolved','closed') DEFAULT 'open',
            category varchar(100) DEFAULT 'general',
            assigned_to bigint(20) DEFAULT NULL,
            resolution text DEFAULT NULL,
            satisfaction_rating tinyint(1) DEFAULT NULL,
            satisfaction_feedback text DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            resolved_at datetime DEFAULT NULL,
            PRIMARY KEY (ticket_id),
            UNIQUE KEY ticket_number (ticket_number),
            KEY user_id (user_id),
            KEY user_email (user_email),
            KEY status (status),
            KEY priority (priority),
            KEY category (category),
            KEY assigned_to (assigned_to)
        ) $charset_collate;";
        
        // Ticket replies table
        $sql_replies = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}elc_ticket_replies (
            reply_id bigint(20) NOT NULL AUTO_INCREMENT,
            ticket_id bigint(20) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            author_type enum('user','support','system') NOT NULL,
            reply_content text NOT NULL,
            attachment_url varchar(255) DEFAULT NULL,
            is_internal tinyint(1) DEFAULT 0,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (reply_id),
            KEY ticket_id (ticket_id),
            KEY user_id (user_id),
            KEY author_type (author_type),
            FOREIGN KEY (ticket_id) REFERENCES {$wpdb->prefix}elc_support_tickets(ticket_id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // FAQ table
        $sql_faq = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}elc_faq (
            faq_id bigint(20) NOT NULL AUTO_INCREMENT,
            question varchar(500) NOT NULL,
            answer text NOT NULL,
            category varchar(100) DEFAULT 'general',
            keywords text DEFAULT NULL,
            view_count bigint(20) DEFAULT 0,
            helpful_votes bigint(20) DEFAULT 0,
            unhelpful_votes bigint(20) DEFAULT 0,
            is_featured tinyint(1) DEFAULT 0,
            is_published tinyint(1) DEFAULT 1,
            display_order int(11) DEFAULT 0,
            created_by bigint(20) DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (faq_id),
            KEY category (category),
            KEY is_published (is_published),
            KEY is_featured (is_featured),
            KEY display_order (display_order),
            FULLTEXT KEY search_content (question, answer, keywords)
        ) $charset_collate;";
        
        // Analytics table
        $sql_analytics = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}elc_analytics (
            analytics_id bigint(20) NOT NULL AUTO_INCREMENT,
            metric_type varchar(50) NOT NULL,
            metric_value decimal(10,2) NOT NULL,
            date_recorded date NOT NULL,
            hour_recorded tinyint(2) DEFAULT NULL,
            additional_data longtext DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (analytics_id),
            KEY metric_type (metric_type),
            KEY date_recorded (date_recorded),
            KEY metric_combination (metric_type, date_recorded)
        ) $charset_collate;";
        
        // Chat operators table
        $sql_operators = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}elc_chat_operators (
            operator_id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            status enum('online','busy','away','offline') DEFAULT 'offline',
            max_concurrent_chats int(3) DEFAULT 5,
            current_chat_count int(3) DEFAULT 0,
            department varchar(100) DEFAULT 'general',
            skills text DEFAULT NULL,
            auto_accept tinyint(1) DEFAULT 1,
            last_activity datetime DEFAULT NULL,
            total_chats_handled bigint(20) DEFAULT 0,
            average_rating decimal(3,2) DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (operator_id),
            UNIQUE KEY user_id (user_id),
            KEY status (status),
            KEY department (department)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_sessions);
        dbDelta($sql_messages);
        dbDelta($sql_tickets);
        dbDelta($sql_replies);
        dbDelta($sql_faq);
        dbDelta($sql_analytics);
        dbDelta($sql_operators);
    }
    
    /**
     * Set default plugin options
     */
    private function set_default_options() {
        $default_options = array(
            'chat_enabled' => true,
            'chatbot_enabled' => true,
            'support_tickets_enabled' => true,
            'faq_enabled' => true,
            'chat_widget_position' => 'bottom-right',
            'chat_widget_color' => '#2ecc71',
            'business_hours' => array(
                'enabled' => true,
                'timezone' => 'Asia/Ho_Chi_Minh',
                'monday' => array('start' => '09:00', 'end' => '18:00'),
                'tuesday' => array('start' => '09:00', 'end' => '18:00'),
                'wednesday' => array('start' => '09:00', 'end' => '18:00'),
                'thursday' => array('start' => '09:00', 'end' => '18:00'),
                'friday' => array('start' => '09:00', 'end' => '18:00'),
                'saturday' => array('start' => '10:00', 'end' => '16:00'),
                'sunday' => array('enabled' => false)
            ),
            'chatbot_responses' => array(
                'greeting' => 'Hello! Welcome to Environmental Platform. How can I help you today?',
                'offline' => 'Sorry, our support team is currently offline. Please leave a message and we\'ll get back to you soon.',
                'transfer' => 'Let me connect you with a human agent.',
                'no_match' => 'I\'m not sure I understand. Would you like to speak with a human agent?'
            ),
            'email_notifications' => true,
            'analytics_enabled' => true,
            'file_uploads_enabled' => true,
            'max_file_size' => 5242880, // 5MB
            'allowed_file_types' => array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'),
            'cleanup_days' => 90,
            'rating_system_enabled' => true
        );
        
        add_option('elc_options', $default_options);
    }
    
    /**
     * Schedule plugin events
     */
    private function schedule_events() {
        if (!wp_next_scheduled('elc_cleanup_old_sessions')) {
            wp_schedule_event(time(), 'daily', 'elc_cleanup_old_sessions');
        }
        
        if (!wp_next_scheduled('elc_send_daily_reports')) {
            wp_schedule_event(time(), 'daily', 'elc_send_daily_reports');
        }
        
        if (!wp_next_scheduled('elc_backup_chat_data')) {
            wp_schedule_event(time(), 'weekly', 'elc_backup_chat_data');
        }
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('environmental-live-chat', false, dirname(ELC_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Register custom post types if needed
        $this->register_post_types();
        
        // Initialize real-time functionality
        add_action('wp_ajax_elc_operator_status', array($this, 'update_operator_status'));
        add_action('wp_ajax_elc_get_chat_updates', array($this, 'get_chat_updates'));
    }
    
    /**
     * Register custom post types
     */
    private function register_post_types() {
        // Knowledge base post type
        register_post_type('elc_knowledge_base', array(
            'label' => __('Knowledge Base', 'environmental-live-chat'),
            'public' => true,
            'show_in_menu' => false,
            'supports' => array('title', 'editor', 'excerpt', 'custom-fields'),
            'has_archive' => true,
            'rewrite' => array('slug' => 'knowledge-base')
        ));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Enqueue styles
        wp_enqueue_style(
            'elc-frontend',
            ELC_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            ELC_VERSION
        );
        
        // Enqueue scripts
        wp_enqueue_script(
            'elc-frontend',
            ELC_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            ELC_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('elc-frontend', 'elcAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('elc_nonce'),
            'user_id' => get_current_user_id(),
            'visitor_id' => $this->get_visitor_id(),
            'options' => get_option('elc_options', array())
        ));
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'environmental-live-chat') === false) {
            return;
        }
        
        // Enqueue Chart.js
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        
        // Enqueue admin styles
        wp_enqueue_style(
            'elc-admin',
            ELC_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            ELC_VERSION
        );
        
        // Enqueue admin scripts
        wp_enqueue_script(
            'elc-admin',
            ELC_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'chart-js'),
            ELC_VERSION,
            true
        );
        
        // Localize admin script
        wp_localize_script('elc-admin', 'elcAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('elc_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this item?', 'environmental-live-chat'),
                'saving' => __('Saving...', 'environmental-live-chat'),
                'saved' => __('Saved successfully!', 'environmental-live-chat'),
                'error' => __('An error occurred. Please try again.', 'environmental-live-chat')
            )
        ));
    }
    
    /**
     * Add chat widget to footer
     */
    public function add_chat_widget() {
        $options = get_option('elc_options', array());
        
        if (empty($options['chat_enabled'])) {
            return;
        }
        
        // Don't show on admin pages
        if (is_admin()) {
            return;
        }
        
        echo $this->live_chat->render_chat_widget();
    }
    
    /**
     * Initialize REST API
     */
    public function init_rest_api() {
        $this->api->register_routes();
    }
    
    /**
     * Get visitor ID
     */
    private function get_visitor_id() {
        if (!isset($_COOKIE['elc_visitor_id'])) {
            $visitor_id = wp_generate_uuid4();
            setcookie('elc_visitor_id', $visitor_id, time() + (365 * 24 * 60 * 60), '/');
            return $visitor_id;
        }
        return sanitize_text_field($_COOKIE['elc_visitor_id']);
    }
    
    /**
     * AJAX Handlers
     */
    public function handle_send_message() {
        check_ajax_referer('elc_nonce', 'nonce');
        
        $session_id = intval($_POST['session_id']);
        $message = sanitize_textarea_field($_POST['message']);
        $message_type = sanitize_text_field($_POST['message_type'] ?? 'text');
        
        $result = $this->live_chat->send_message($session_id, $message, $message_type);
        
        wp_send_json($result);
    }
    
    public function handle_get_messages() {
        check_ajax_referer('elc_nonce', 'nonce');
        
        $session_id = intval($_POST['session_id']);
        $last_message_id = intval($_POST['last_message_id'] ?? 0);
        
        $messages = $this->live_chat->get_new_messages($session_id, $last_message_id);
        
        wp_send_json_success($messages);
    }
    
    public function handle_submit_ticket() {
        check_ajax_referer('elc_nonce', 'nonce');
        
        $ticket_data = array(
            'user_email' => sanitize_email($_POST['email']),
            'user_name' => sanitize_text_field($_POST['name']),
            'subject' => sanitize_text_field($_POST['subject']),
            'description' => sanitize_textarea_field($_POST['description']),
            'priority' => sanitize_text_field($_POST['priority'] ?? 'normal'),
            'category' => sanitize_text_field($_POST['category'] ?? 'general')
        );
        
        $result = $this->support_tickets->create_ticket($ticket_data);
        
        wp_send_json($result);
    }
    
    public function handle_search_faq() {
        check_ajax_referer('elc_nonce', 'nonce');
        
        $query = sanitize_text_field($_POST['query']);
        $category = sanitize_text_field($_POST['category'] ?? '');
        
        $results = $this->faq_manager->search_faq($query, $category);
        
        wp_send_json_success($results);
    }
    
    public function update_operator_status() {
        check_ajax_referer('elc_admin_nonce', 'nonce');
        
        $status = sanitize_text_field($_POST['status']);
        $user_id = get_current_user_id();
        
        $result = $this->live_chat->update_operator_status($user_id, $status);
        
        wp_send_json($result);
    }
    
    public function get_chat_updates() {
        check_ajax_referer('elc_admin_nonce', 'nonce');
        
        $operator_id = get_current_user_id();
        $updates = $this->live_chat->get_operator_updates($operator_id);
        
        wp_send_json_success($updates);
    }
    
    /**
     * Shortcode handlers
     */
    public function chat_widget_shortcode($atts) {
        $atts = shortcode_atts(array(
            'position' => 'inline',
            'height' => '400px',
            'width' => '100%'
        ), $atts);
        
        return $this->live_chat->render_inline_chat($atts);
    }
    
    public function faq_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'limit' => 10,
            'search' => true,
            'featured_only' => false
        ), $atts);
        
        return $this->faq_manager->render_faq_list($atts);
    }
    
    public function support_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => __('Contact Support', 'environmental-live-chat'),
            'show_categories' => true,
            'redirect' => ''
        ), $atts);
        
        return $this->support_tickets->render_support_form($atts);
    }
    
    public function knowledge_base_shortcode($atts) {
        $atts = shortcode_atts(array(
            'categories' => '',
            'layout' => 'grid',
            'limit' => 12
        ), $atts);
        
        return $this->faq_manager->render_knowledge_base($atts);
    }
}

/**
 * Initialize the plugin
 */
function environmental_live_chat() {
    return Environmental_Live_Chat::get_instance();
}

// Start the plugin
environmental_live_chat();
