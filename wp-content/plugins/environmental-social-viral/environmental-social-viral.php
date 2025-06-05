<?php
/**
 * Plugin Name: Environmental Social & Viral Features
 * Description: Comprehensive social sharing and viral features system for the Environmental Platform with tracking, referral rewards, and analytics.
 * Version: 1.0.0
 * Author: Environmental Platform Team
 * License: GPL v2 or later
 * Text Domain: environmental-social-viral
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ENV_SOCIAL_VIRAL_VERSION', '1.0.0');
define('ENV_SOCIAL_VIRAL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ENV_SOCIAL_VIRAL_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ENV_SOCIAL_VIRAL_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Environmental Social Viral Plugin Class
 */
class Environmental_Social_Viral {
    
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
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array('Environmental_Social_Viral', 'uninstall'));
        
        // AJAX handlers
        add_action('wp_ajax_track_social_share', array($this, 'handle_track_social_share'));
        add_action('wp_ajax_nopriv_track_social_share', array($this, 'handle_track_social_share'));
        add_action('wp_ajax_process_referral', array($this, 'handle_process_referral'));
        add_action('wp_ajax_nopriv_process_referral', array($this, 'handle_process_referral'));
        add_action('wp_ajax_get_viral_stats', array($this, 'handle_get_viral_stats'));
        add_action('wp_ajax_generate_share_content', array($this, 'handle_generate_share_content'));
        
        // Shortcodes
        add_action('init', array($this, 'register_shortcodes'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Add referral tracking
        add_action('template_redirect', array($this, 'track_referral_visit'));
        
        // Add sharing buttons to content
        add_filter('the_content', array($this, 'add_sharing_buttons_to_content'));
        
        // User registration hook for referral rewards
        add_action('user_register', array($this, 'process_referral_registration'));
        
        // WordPress actions tracking
        add_action('wp_insert_post', array($this, 'track_content_creation'), 10, 3);
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Core classes
        require_once ENV_SOCIAL_VIRAL_PLUGIN_PATH . 'includes/class-database-manager.php';
        require_once ENV_SOCIAL_VIRAL_PLUGIN_PATH . 'includes/class-sharing-manager.php';
        require_once ENV_SOCIAL_VIRAL_PLUGIN_PATH . 'includes/class-viral-engine.php';
        require_once ENV_SOCIAL_VIRAL_PLUGIN_PATH . 'includes/class-referral-system.php';
        require_once ENV_SOCIAL_VIRAL_PLUGIN_PATH . 'includes/class-analytics-tracker.php';
        require_once ENV_SOCIAL_VIRAL_PLUGIN_PATH . 'includes/class-content-generator.php';
        
        // Admin classes
        if (is_admin()) {
            require_once ENV_SOCIAL_VIRAL_PLUGIN_PATH . 'admin/class-admin-controller.php';
            require_once ENV_SOCIAL_VIRAL_PLUGIN_PATH . 'admin/class-sharing-admin.php';
            require_once ENV_SOCIAL_VIRAL_PLUGIN_PATH . 'admin/class-viral-dashboard.php';
            require_once ENV_SOCIAL_VIRAL_PLUGIN_PATH . 'admin/class-referral-admin.php';
        }
        
        // Public classes
        require_once ENV_SOCIAL_VIRAL_PLUGIN_PATH . 'public/class-public-controller.php';
        require_once ENV_SOCIAL_VIRAL_PLUGIN_PATH . 'public/class-sharing-display.php';
        require_once ENV_SOCIAL_VIRAL_PLUGIN_PATH . 'public/class-viral-dashboard.php';
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize database
        $database_manager = new Environmental_Social_Viral_Database();
        $database_manager->init();
        
        // Initialize managers
        $sharing_manager = Environmental_Social_Viral_Sharing_Manager::get_instance();
        $viral_engine = Environmental_Social_Viral_Engine::get_instance();
        $referral_system = Environmental_Social_Viral_Referral_System::get_instance();
        $analytics_tracker = Environmental_Social_Viral_Analytics::get_instance();
        $content_generator = Environmental_Social_Viral_Content_Generator::get_instance();
          // Initialize admin controller
        if (is_admin()) {
            $admin_controller = new Environmental_Social_Viral_Admin_Controller();
        }
        
        // Initialize public controller
        $public_controller = Environmental_Social_Viral_Public_Controller::get_instance();
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'environmental-social-viral',
            false,
            dirname(ENV_SOCIAL_VIRAL_PLUGIN_BASENAME) . '/languages/'
        );
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        $database_manager = new Environmental_Social_Viral_Database();
        $database_manager->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Schedule cron jobs
        if (!wp_next_scheduled('env_social_viral_analytics_cleanup')) {
            wp_schedule_event(time(), 'daily', 'env_social_viral_analytics_cleanup');
        }
        
        if (!wp_next_scheduled('env_social_viral_calculate_coefficients')) {
            wp_schedule_event(time(), 'hourly', 'env_social_viral_calculate_coefficients');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Create upload directories
        $this->create_upload_directories();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('env_social_viral_analytics_cleanup');
        wp_clear_scheduled_hook('env_social_viral_calculate_coefficients');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin uninstall
     */
    public static function uninstall() {
        // Remove database tables
        $database_manager = new Environmental_Social_Viral_Database();
        $database_manager->drop_tables();
        
        // Remove options
        delete_option('env_social_viral_settings');
        delete_option('env_social_viral_version');
        delete_option('env_social_viral_db_version');
        
        // Remove user meta
        delete_metadata('user', 0, 'env_social_viral_referral_code', '', true);
        delete_metadata('user', 0, 'env_social_viral_total_referrals', '', true);
        delete_metadata('user', 0, 'env_social_viral_referral_earnings', '', true);
        
        // Remove transients
        $transients = array(
            'env_social_viral_trending_content',
            'env_social_viral_viral_coefficients',
            'env_social_viral_top_sharers',
            'env_social_viral_platform_stats'
        );
        
        foreach ($transients as $transient) {
            delete_transient($transient);
        }
    }
    
    /**
     * Set default plugin options
     */
    private function set_default_options() {
        $default_settings = array(
            'enabled_platforms' => array('facebook', 'twitter', 'linkedin', 'whatsapp', 'email'),
            'tracking_enabled' => true,
            'referral_rewards_enabled' => true,
            'viral_coefficient_tracking' => true,
            'auto_generate_content' => true,
            'sharing_button_style' => 'modern',
            'sharing_button_position' => 'bottom',
            'referral_reward_amount' => 10,
            'referral_reward_type' => 'points',
            'viral_threshold' => 0.3,
            'analytics_retention_days' => 365,
            'social_platforms' => array(
                'facebook' => array(
                    'enabled' => true,
                    'app_id' => '',
                    'app_secret' => ''
                ),
                'twitter' => array(
                    'enabled' => true,
                    'api_key' => '',
                    'api_secret' => '',
                    'access_token' => '',
                    'access_token_secret' => ''
                ),
                'linkedin' => array(
                    'enabled' => true,
                    'client_id' => '',
                    'client_secret' => ''
                ),
                'instagram' => array(
                    'enabled' => false,
                    'access_token' => ''
                )
            )
        );
        
        add_option('env_social_viral_settings', $default_settings);
        add_option('env_social_viral_version', ENV_SOCIAL_VIRAL_VERSION);
    }
    
    /**
     * Create upload directories
     */
    private function create_upload_directories() {
        $upload_dir = wp_upload_dir();
        $plugin_upload_dir = $upload_dir['basedir'] . '/environmental-social-viral';
        
        if (!file_exists($plugin_upload_dir)) {
            wp_mkdir_p($plugin_upload_dir);
            
            // Create subdirectories
            wp_mkdir_p($plugin_upload_dir . '/generated-content');
            wp_mkdir_p($plugin_upload_dir . '/social-images');
            wp_mkdir_p($plugin_upload_dir . '/analytics-exports');
            
            // Create .htaccess for security
            $htaccess_content = "Options -Indexes\n";
            file_put_contents($plugin_upload_dir . '/.htaccess', $htaccess_content);
        }
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('env_sharing_buttons', array($this, 'sharing_buttons_shortcode'));
        add_shortcode('env_viral_dashboard', array($this, 'viral_dashboard_shortcode'));
        add_shortcode('env_referral_link', array($this, 'referral_link_shortcode'));
        add_shortcode('env_sharing_stats', array($this, 'sharing_stats_shortcode'));
        add_shortcode('env_viral_content', array($this, 'viral_content_shortcode'));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_script(
            'env-social-viral-frontend',
            ENV_SOCIAL_VIRAL_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            ENV_SOCIAL_VIRAL_VERSION,
            true
        );
        
        wp_enqueue_style(
            'env-social-viral-frontend',
            ENV_SOCIAL_VIRAL_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            ENV_SOCIAL_VIRAL_VERSION
        );
        
        // Localize script
        wp_localize_script('env-social-viral-frontend', 'envSocialViral', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('env_social_viral_nonce'),
            'user_id' => get_current_user_id(),
            'site_url' => site_url(),
            'plugin_url' => ENV_SOCIAL_VIRAL_PLUGIN_URL,
            'strings' => array(
                'sharing_success' => __('Content shared successfully!', 'environmental-social-viral'),
                'sharing_error' => __('Error sharing content. Please try again.', 'environmental-social-viral'),
                'copy_success' => __('Link copied to clipboard!', 'environmental-social-viral'),
                'copy_error' => __('Unable to copy link. Please copy manually.', 'environmental-social-viral')
            )
        ));
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'environmental-social-viral') === false) {
            return;
        }
        
        wp_enqueue_script(
            'env-social-viral-admin',
            ENV_SOCIAL_VIRAL_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'chart-js'),
            ENV_SOCIAL_VIRAL_VERSION,
            true
        );
        
        wp_enqueue_style(
            'env-social-viral-admin',
            ENV_SOCIAL_VIRAL_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            ENV_SOCIAL_VIRAL_VERSION
        );
        
        // Enqueue Chart.js
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js',
            array(),
            '3.9.1',
            true
        );
        
        wp_localize_script('env-social-viral-admin', 'envSocialViralAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('env_social_viral_admin_nonce'),
            'plugin_url' => ENV_SOCIAL_VIRAL_PLUGIN_URL
        ));
    }
    
    /**
     * Handle social share tracking AJAX
     */
    public function handle_track_social_share() {
        check_ajax_referer('env_social_viral_nonce', 'nonce');
        
        $platform = sanitize_text_field($_POST['platform']);
        $content_id = intval($_POST['content_id']);
        $content_type = sanitize_text_field($_POST['content_type']);
        $user_id = get_current_user_id();
        
        $analytics = Environmental_Social_Viral_Analytics::get_instance();
        $result = $analytics->track_share($platform, $content_id, $content_type, $user_id);
        
        wp_send_json_success(array(
            'tracked' => $result,
            'message' => __('Share tracked successfully', 'environmental-social-viral')
        ));
    }
    
    /**
     * Handle referral processing AJAX
     */
    public function handle_process_referral() {
        check_ajax_referer('env_social_viral_nonce', 'nonce');
        
        $referral_code = sanitize_text_field($_POST['referral_code']);
        $action_type = sanitize_text_field($_POST['action_type']);
        $user_id = get_current_user_id();
        
        $referral_system = Environmental_Social_Viral_Referral_System::get_instance();
        $result = $referral_system->process_referral_action($referral_code, $action_type, $user_id);
        
        wp_send_json_success($result);
    }
    
    /**
     * Handle viral stats AJAX
     */
    public function handle_get_viral_stats() {
        check_ajax_referer('env_social_viral_nonce', 'nonce');
        
        $content_id = intval($_POST['content_id']);
        $period = sanitize_text_field($_POST['period']);
        
        $viral_engine = Environmental_Social_Viral_Engine::get_instance();
        $stats = $viral_engine->get_content_viral_stats($content_id, $period);
        
        wp_send_json_success($stats);
    }
    
    /**
     * Handle content generation AJAX
     */
    public function handle_generate_share_content() {
        check_ajax_referer('env_social_viral_nonce', 'nonce');
        
        $content_id = intval($_POST['content_id']);
        $platform = sanitize_text_field($_POST['platform']);
        $template_type = sanitize_text_field($_POST['template_type']);
        
        $content_generator = Environmental_Social_Viral_Content_Generator::get_instance();
        $generated_content = $content_generator->generate_share_content($content_id, $platform, $template_type);
        
        wp_send_json_success($generated_content);
    }
    
    /**
     * Track referral visits
     */
    public function track_referral_visit() {
        if (isset($_GET['ref']) && !empty($_GET['ref'])) {
            $referral_code = sanitize_text_field($_GET['ref']);
            
            // Store in session/cookie for later processing
            if (!session_id()) {
                session_start();
            }
            $_SESSION['env_referral_code'] = $referral_code;
            
            // Set cookie as backup
            setcookie('env_referral_code', $referral_code, time() + (30 * 24 * 60 * 60), '/');
            
            // Track the referral visit
            $referral_system = Environmental_Social_Viral_Referral_System::get_instance();
            $referral_system->track_referral_visit($referral_code);
        }
    }
    
    /**
     * Add sharing buttons to content
     */
    public function add_sharing_buttons_to_content($content) {
        if (is_single() || is_page()) {
            $settings = get_option('env_social_viral_settings', array());
            
            if (!empty($settings['auto_add_buttons'])) {
                $sharing_display = new Environmental_Social_Viral_Sharing_Display();
                $buttons = $sharing_display->render_sharing_buttons(get_the_ID());
                
                if ($settings['sharing_button_position'] === 'top') {
                    $content = $buttons . $content;
                } else {
                    $content = $content . $buttons;
                }
            }
        }
        
        return $content;
    }
    
    /**
     * Process referral registration
     */
    public function process_referral_registration($user_id) {
        $referral_code = '';
        
        // Check session first
        if (!session_id()) {
            session_start();
        }
        
        if (!empty($_SESSION['env_referral_code'])) {
            $referral_code = $_SESSION['env_referral_code'];
            unset($_SESSION['env_referral_code']);
        } elseif (!empty($_COOKIE['env_referral_code'])) {
            $referral_code = $_COOKIE['env_referral_code'];
            setcookie('env_referral_code', '', time() - 3600, '/');
        }
        
        if (!empty($referral_code)) {
            $referral_system = Environmental_Social_Viral_Referral_System::get_instance();
            $referral_system->process_referral_registration($user_id, $referral_code);
        }
    }
    
    /**
     * Track content creation for viral analysis
     */
    public function track_content_creation($post_id, $post, $update) {
        if (!$update && $post->post_status === 'publish') {
            $viral_engine = Environmental_Social_Viral_Engine::get_instance();
            $viral_engine->initialize_content_tracking($post_id, $post->post_type);
        }
    }
    
    /**
     * Sharing buttons shortcode
     */
    public function sharing_buttons_shortcode($atts) {
        $atts = shortcode_atts(array(
            'post_id' => get_the_ID(),
            'style' => 'default',
            'platforms' => '',
            'show_counts' => 'true'
        ), $atts);
        
        $sharing_display = new Environmental_Social_Viral_Sharing_Display();
        return $sharing_display->render_sharing_buttons($atts['post_id'], $atts);
    }
    
    /**
     * Viral dashboard shortcode
     */
    public function viral_dashboard_shortcode($atts) {
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id(),
            'period' => '30days'
        ), $atts);
        
        $viral_dashboard = new Environmental_Social_Viral_Public_Dashboard();
        return $viral_dashboard->render_viral_dashboard($atts);
    }
    
    /**
     * Referral link shortcode
     */
    public function referral_link_shortcode($atts) {
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id(),
            'page_id' => get_the_ID(),
            'text' => __('Share this page', 'environmental-social-viral')
        ), $atts);
        
        $referral_system = Environmental_Social_Viral_Referral_System::get_instance();
        return $referral_system->generate_referral_link_html($atts);
    }
    
    /**
     * Sharing stats shortcode
     */
    public function sharing_stats_shortcode($atts) {
        $atts = shortcode_atts(array(
            'content_id' => get_the_ID(),
            'display_type' => 'summary',
            'period' => '7days'
        ), $atts);
        
        $analytics = Environmental_Social_Viral_Analytics::get_instance();
        return $analytics->render_sharing_stats($atts);
    }
    
    /**
     * Viral content shortcode
     */
    public function viral_content_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 5,
            'period' => '7days',
            'content_type' => 'any',
            'display_type' => 'list'
        ), $atts);
        
        $viral_engine = Environmental_Social_Viral_Engine::get_instance();
        return $viral_engine->render_viral_content($atts);
    }
}

// Initialize the plugin
Environmental_Social_Viral::get_instance();

// Cron job handlers
add_action('env_social_viral_analytics_cleanup', array('Environmental_Social_Viral_Analytics', 'cleanup_old_data'));
add_action('env_social_viral_calculate_coefficients', array('Environmental_Social_Viral_Engine', 'calculate_viral_coefficients'));
