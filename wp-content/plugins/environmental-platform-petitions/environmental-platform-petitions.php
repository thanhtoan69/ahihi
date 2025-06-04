<?php
/**
 * Plugin Name: Environmental Platform Petitions
 * Plugin URI: https://environmental-platform.com/petitions
 * Description: Comprehensive petition and campaign system for environmental advocacy with signature collection, verification, progress tracking, and social media integration.
 * Version: 1.0.0
 * Author: Environmental Platform Team
 * License: GPL v2 or later
 * Text Domain: environmental-platform-petitions
 * Domain Path: /languages
 * Requires PHP: 7.4
 * 
 * @package Environmental_Platform_Petitions
 * @since 1.0.0 - Phase 35
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EPP_VERSION', '1.0.0');
define('EPP_PLUGIN_FILE', __FILE__);
define('EPP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EPP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EPP_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Environmental Platform Petitions Class
 */
class Environmental_Platform_Petitions {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get the single instance of the class
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
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
          // AJAX hooks
        add_action('wp_ajax_sign_petition', array($this, 'handle_petition_signature'));
        add_action('wp_ajax_nopriv_sign_petition', array($this, 'handle_petition_signature'));
        add_action('wp_ajax_verify_signature', array($this, 'handle_signature_verification'));
        add_action('wp_ajax_petition_share', array($this, 'handle_petition_share'));
        add_action('wp_ajax_nopriv_petition_share', array($this, 'handle_petition_share'));
        add_action('wp_ajax_save_petition_settings', array($this, 'handle_save_petition_settings'));
        
        // Admin AJAX hooks
        add_action('wp_ajax_epp_save_settings', array($this, 'handle_save_settings_ajax'));
        add_action('wp_ajax_epp_test_email', array($this, 'handle_test_email'));
        add_action('wp_ajax_epp_reset_settings', array($this, 'handle_reset_settings'));
        add_action('wp_ajax_epp_quick_action', array($this, 'handle_quick_action'));
        add_action('wp_ajax_epp_bulk_action', array($this, 'handle_bulk_action'));
        add_action('wp_ajax_epp_verify_signature', array($this, 'handle_verify_signature_admin'));
        add_action('wp_ajax_epp_reject_verification', array($this, 'handle_reject_verification'));
        add_action('wp_ajax_epp_resend_verification', array($this, 'handle_resend_verification'));
        add_action('wp_ajax_epp_export_data', array($this, 'handle_export_data'));
        add_action('wp_ajax_epp_get_real_time_data', array($this, 'handle_get_real_time_data'));
        add_action('wp_ajax_epp_refresh_dashboard', array($this, 'handle_refresh_dashboard'));
        add_action('wp_ajax_epp_update_analytics', array($this, 'handle_update_analytics'));
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_petition_meta_boxes'));
        add_action('save_post', array($this, 'save_petition_meta'));
        
        // Shortcode
        add_shortcode('petition_signature_form', array($this, 'render_signature_form'));
        add_shortcode('petition_progress', array($this, 'render_petition_progress'));
        add_shortcode('petition_share', array($this, 'render_petition_share'));
        
        // REST API
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('environmental-platform-petitions', false, dirname(EPP_PLUGIN_BASENAME) . '/languages');
        
        // Include required files
        $this->include_files();
        
        // Initialize components
        $this->init_components();
    }
    
    /**
     * Include required files
     */
    private function include_files() {
        require_once EPP_PLUGIN_DIR . 'includes/class-signature-manager.php';
        require_once EPP_PLUGIN_DIR . 'includes/class-verification-system.php';
        require_once EPP_PLUGIN_DIR . 'includes/class-campaign-manager.php';
        require_once EPP_PLUGIN_DIR . 'includes/class-share-manager.php';
        require_once EPP_PLUGIN_DIR . 'includes/class-admin-dashboard.php';
        require_once EPP_PLUGIN_DIR . 'includes/class-analytics.php';
        require_once EPP_PLUGIN_DIR . 'includes/class-database.php';
        require_once EPP_PLUGIN_DIR . 'includes/class-email-notifications.php';
        require_once EPP_PLUGIN_DIR . 'includes/class-rest-api.php';
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        new EPP_Database();
        new EPP_Signature_Manager();
        new EPP_Verification_System();
        new EPP_Campaign_Manager();
        new EPP_Share_Manager();
        new EPP_Admin_Dashboard();
        new EPP_Analytics();
        new EPP_Email_Notifications();
        new EPP_REST_API();
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        if (is_singular('env_petition') || is_post_type_archive('env_petition')) {
            wp_enqueue_script(
                'epp-frontend',
                EPP_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                EPP_VERSION,
                true
            );
            
            wp_enqueue_style(
                'epp-frontend',
                EPP_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                EPP_VERSION
            );
            
            wp_localize_script('epp-frontend', 'epp_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('epp_nonce'),
                'messages' => array(
                    'signing' => __('Signing petition...', 'environmental-platform-petitions'),
                    'success' => __('Thank you for signing!', 'environmental-platform-petitions'),
                    'error' => __('Error signing petition. Please try again.', 'environmental-platform-petitions'),
                    'email_required' => __('Email address is required.', 'environmental-platform-petitions'),
                    'name_required' => __('Name is required.', 'environmental-platform-petitions')
                )
            ));
        }
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'environmental-petitions') !== false || 
            get_current_screen()->post_type === 'env_petition') {
            
            wp_enqueue_script(
                'epp-admin',
                EPP_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery', 'chart-js'),
                EPP_VERSION,
                true
            );
            
            wp_enqueue_style(
                'epp-admin',
                EPP_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                EPP_VERSION
            );
              // Enqueue Chart.js for analytics
            wp_enqueue_script(
                'chart-js',
                'https://cdn.jsdelivr.net/npm/chart.js',
                array(),
                '3.9.1',
                true
            );
            
            // Localize admin script
            wp_localize_script('epp-admin', 'epp_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('epp_nonce'),
                'messages' => array(
                    'confirm_delete' => __('Are you sure you want to delete this item?', 'environmental-platform-petitions'),
                    'confirm_bulk_action' => __('Are you sure you want to perform this bulk action?', 'environmental-platform-petitions'),
                    'saving' => __('Saving...', 'environmental-platform-petitions'),
                    'saved' => __('Saved successfully', 'environmental-platform-petitions'),
                    'error' => __('An error occurred', 'environmental-platform-petitions'),
                    'loading' => __('Loading...', 'environmental-platform-petitions')
                )
            ));
        }
    }
    
    /**
     * Handle petition signature AJAX
     */
    public function handle_petition_signature() {
        check_ajax_referer('epp_nonce', 'nonce');
        
        $petition_id = intval($_POST['petition_id']);
        $signer_data = array(
            'name' => sanitize_text_field($_POST['name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'location' => sanitize_text_field($_POST['location'] ?? ''),
            'comment' => sanitize_textarea_field($_POST['comment'] ?? ''),
            'anonymous' => isset($_POST['anonymous']) ? 1 : 0
        );
        
        $signature_manager = new EPP_Signature_Manager();
        $result = $signature_manager->add_signature($petition_id, $signer_data);
        
        wp_send_json($result);
    }
    
    /**
     * Handle signature verification AJAX
     */
    public function handle_signature_verification() {
        check_ajax_referer('epp_nonce', 'nonce');
        
        $signature_id = intval($_POST['signature_id']);
        $verification_code = sanitize_text_field($_POST['verification_code']);
        
        $verification_system = new EPP_Verification_System();
        $result = $verification_system->verify_signature($signature_id, $verification_code);
        
        wp_send_json($result);
    }
    
    /**
     * Handle petition sharing AJAX
     */
    public function handle_petition_share() {
        check_ajax_referer('epp_nonce', 'nonce');
        
        $petition_id = intval($_POST['petition_id']);
        $platform = sanitize_text_field($_POST['platform']);
        
        $share_manager = new EPP_Share_Manager();
        $result = $share_manager->track_share($petition_id, $platform);
        
        wp_send_json($result);
    }
    
    /**
     * Handle saving petition settings AJAX
     */
    public function handle_save_petition_settings() {
        check_ajax_referer('epp_nonce', 'nonce');
        
        $settings = isset($_POST['settings']) ? $_POST['settings'] : array();
        $result = array('success' => false);
        
        // Validate and sanitize settings
        $valid_fields = array(
            'auto_share_milestones',
            'email_notifications',
            'signature_verification_required',
            'allow_anonymous_signatures',
            'display_signature_count',
            'allow_comments'
        );
        
        foreach ($valid_fields as $field) {
            if (isset($settings[$field])) {
                update_option($field, sanitize_text_field($settings[$field]));
            }
        }
        
        $result['success'] = true;
        wp_send_json($result);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Petition Dashboard', 'environmental-platform-petitions'),
            __('Petitions', 'environmental-platform-petitions'),
            'manage_options',
            'environmental-petitions',
            array($this, 'render_admin_dashboard'),
            'dashicons-megaphone',
            30
        );
        
        add_submenu_page(
            'environmental-petitions',
            __('Signature Analytics', 'environmental-platform-petitions'),
            __('Analytics', 'environmental-platform-petitions'),
            'manage_options',
            'petition-analytics',
            array($this, 'render_analytics_page')
        );
        
        add_submenu_page(
            'environmental-petitions',
            __('Signature Verification', 'environmental-platform-petitions'),
            __('Verification', 'environmental-platform-petitions'),
            'manage_options',
            'petition-verification',
            array($this, 'render_verification_page')
        );
        
        add_submenu_page(
            'environmental-petitions',
            __('Petition Settings', 'environmental-platform-petitions'),
            __('Settings', 'environmental-platform-petitions'),
            'manage_options',
            'petition-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Add petition meta boxes
     */
    public function add_petition_meta_boxes() {
        add_meta_box(
            'petition-signatures',
            __('Signature Management', 'environmental-platform-petitions'),
            array($this, 'render_signature_meta_box'),
            'env_petition',
            'normal',
            'high'
        );
        
        add_meta_box(
            'petition-campaign',
            __('Campaign Settings', 'environmental-platform-petitions'),
            array($this, 'render_campaign_meta_box'),
            'env_petition',
            'side',
            'default'
        );
    }
    
    /**
     * Render signature form shortcode
     */
    public function render_signature_form($atts) {
        $atts = shortcode_atts(array(
            'petition_id' => get_the_ID(),
            'style' => 'default'
        ), $atts);
        
        ob_start();
        include EPP_PLUGIN_DIR . 'templates/signature-form.php';
        return ob_get_clean();
    }
    
    /**
     * Render petition progress shortcode
     */
    public function render_petition_progress($atts) {
        $atts = shortcode_atts(array(
            'petition_id' => get_the_ID(),
            'style' => 'default'
        ), $atts);
        
        ob_start();
        include EPP_PLUGIN_DIR . 'templates/petition-progress.php';
        return ob_get_clean();
    }
    
    /**
     * Render petition share shortcode
     */
    public function render_petition_share($atts) {
        $atts = shortcode_atts(array(
            'petition_id' => get_the_ID(),
            'platforms' => 'facebook,twitter,email,whatsapp'
        ), $atts);
        
        ob_start();
        include EPP_PLUGIN_DIR . 'templates/petition-share.php';
        return ob_get_clean();
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('epp/v1', '/petitions/(?P<id>\d+)/signatures', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_petition_signatures'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('epp/v1', '/petitions/(?P<id>\d+)/sign', array(
            'methods' => 'POST',
            'callback' => array($this, 'sign_petition_api'),
            'permission_callback' => '__return_true'
        ));
    }
    
    /**
     * Render admin dashboard
     */
    public function render_admin_dashboard() {
        include EPP_PLUGIN_DIR . 'admin/dashboard.php';
    }
    
    /**
     * Render analytics page
     */
    public function render_analytics_page() {
        include EPP_PLUGIN_DIR . 'admin/analytics.php';
    }
    
    /**
     * Render verification page
     */
    public function render_verification_page() {
        include EPP_PLUGIN_DIR . 'admin/verification.php';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['epp_settings_nonce'], 'epp_save_settings')) {
            $this->save_settings();
        }
        
        include EPP_PLUGIN_DIR . 'admin/settings.php';
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        $settings = $_POST['epp_settings'] ?? array();
        $email_settings = $_POST['epp_email_settings'] ?? array();
        $verification_settings = $_POST['epp_verification_settings'] ?? array();
        $social_settings = $_POST['epp_social_settings'] ?? array();
        
        // Sanitize settings
        $settings = array_map('sanitize_text_field', $settings);
        $email_settings = array_map('sanitize_text_field', $email_settings);
        $verification_settings = array_map('sanitize_text_field', $verification_settings);
        $social_settings = array_map('sanitize_text_field', $social_settings);
        
        // Handle array fields
        if (isset($_POST['epp_email_settings']['admin_notifications'])) {
            $email_settings['admin_notifications'] = array_map('sanitize_text_field', $_POST['epp_email_settings']['admin_notifications']);
        }
        
        if (isset($_POST['epp_email_settings']['user_notifications'])) {
            $email_settings['user_notifications'] = array_map('sanitize_text_field', $_POST['epp_email_settings']['user_notifications']);
        }
        
        if (isset($_POST['epp_verification_settings']['methods'])) {
            $verification_settings['methods'] = array_map('sanitize_text_field', $_POST['epp_verification_settings']['methods']);
        }
        
        if (isset($_POST['epp_social_settings']['platforms'])) {
            $social_settings['platforms'] = array_map('sanitize_text_field', $_POST['epp_social_settings']['platforms']);
        }
        
        // Save settings
        update_option('epp_settings', $settings);
        update_option('epp_email_settings', $email_settings);
        update_option('epp_verification_settings', $verification_settings);
        update_option('epp_social_settings', $social_settings);
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully!', 'environmental-platform-petitions') . '</p></div>';
        });
    }
    
    /**
     * Handle AJAX settings save
     */
    public function handle_save_settings_ajax() {
        check_ajax_referer('epp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'environmental-platform-petitions'));
        }
        
        $this->save_settings();
        wp_send_json_success(__('Settings saved successfully', 'environmental-platform-petitions'));
    }
    
    /**
     * Handle test email
     */
    public function handle_test_email() {
        check_ajax_referer('epp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'environmental-platform-petitions'));
        }
        
        $email_notifications = new EPP_Email_Notifications();
        $result = $email_notifications->send_test_email();
        
        if ($result) {
            wp_send_json_success(__('Test email sent successfully', 'environmental-platform-petitions'));
        } else {
            wp_send_json_error(__('Failed to send test email', 'environmental-platform-petitions'));
        }
    }
    
    /**
     * Handle reset settings
     */
    public function handle_reset_settings() {
        check_ajax_referer('epp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'environmental-platform-petitions'));
        }
        
        delete_option('epp_settings');
        delete_option('epp_email_settings');
        delete_option('epp_verification_settings');
        delete_option('epp_social_settings');
        
        wp_send_json_success(__('Settings reset to defaults', 'environmental-platform-petitions'));
    }
    
    /**
     * Handle quick actions
     */
    public function handle_quick_action() {
        check_ajax_referer('epp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'environmental-platform-petitions'));
        }
        
        $action = sanitize_text_field($_POST['quick_action']);
        
        switch ($action) {
            case 'create_petition':
                wp_send_json_success(array(
                    'message' => __('Redirecting to create petition', 'environmental-platform-petitions'),
                    'redirect' => admin_url('post-new.php?post_type=env_petition')
                ));
                break;
                
            case 'export_signatures':
                wp_send_json_success(array(
                    'message' => __('Redirecting to export', 'environmental-platform-petitions'),
                    'redirect' => admin_url('admin.php?page=petition-analytics&tab=export')
                ));
                break;
                
            default:
                wp_send_json_error(__('Unknown action', 'environmental-platform-petitions'));
        }
    }
    
    /**
     * Handle bulk actions
     */
    public function handle_bulk_action() {
        check_ajax_referer('epp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'environmental-platform-petitions'));
        }
        
        $type = sanitize_text_field($_POST['bulk_type']);
        $action = sanitize_text_field($_POST['bulk_action']);
        $items = array_map('intval', $_POST['items']);
        
        $admin_dashboard = new EPP_Admin_Dashboard();
        $result = $admin_dashboard->handle_bulk_action($type, $action, $items);
        
        wp_send_json($result);
    }
      /**
     * Handle admin signature verification
     */
    public function handle_verify_signature_admin() {
        check_ajax_referer('petition_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'environmental-platform-petitions'));
        }
        
        $signature_id = intval($_POST['signature_id']);
        $verification_system = new EPP_Verification_System();
        $result = $verification_system->manual_verify_signature($signature_id);
        
        if ($result) {
            wp_send_json_success(__('Signature verified successfully', 'environmental-platform-petitions'));
        } else {
            wp_send_json_error(__('Failed to verify signature', 'environmental-platform-petitions'));
        }
    }
      /**
     * Handle reject verification
     */
    public function handle_reject_verification() {
        check_ajax_referer('petition_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'environmental-platform-petitions'));
        }
        
        $signature_id = intval($_POST['signature_id']);
        $reason = sanitize_text_field($_POST['reason'] ?? '');
        
        $verification_system = new EPP_Verification_System();
        $result = $verification_system->reject_signature($signature_id, $reason);
        
        if ($result) {
            wp_send_json_success(__('Verification rejected successfully', 'environmental-platform-petitions'));
        } else {
            wp_send_json_error(__('Failed to reject verification', 'environmental-platform-petitions'));
        }
    }
      /**
     * Handle resend verification
     */
    public function handle_resend_verification() {
        check_ajax_referer('petition_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'environmental-platform-petitions'));
        }
        
        $signature_id = intval($_POST['signature_id']);
        $verification_system = new EPP_Verification_System();
        $result = $verification_system->resend_verification($signature_id);
        
        if ($result) {
            wp_send_json_success(__('Verification email resent successfully', 'environmental-platform-petitions'));
        } else {
            wp_send_json_error(__('Failed to resend verification email', 'environmental-platform-petitions'));
        }
    }
      /**
     * Handle data export
     */
    public function handle_export_data() {
        check_ajax_referer('petition_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'environmental-platform-petitions'));
        }
        
        $type = sanitize_text_field($_POST['export_type']);
        $format = sanitize_text_field($_POST['export_format']);
        $filters = $_POST['filters'] ?? array();
        
        $admin_dashboard = new EPP_Admin_Dashboard();
        $admin_dashboard->export_data($type, $format, $filters);
    }
      /**
     * Handle real-time data updates
     */
    public function handle_get_real_time_data() {
        check_ajax_referer('petition_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'environmental-platform-petitions'));
        }
        
        $database = new EPP_Database();
        $stats = $database->get_dashboard_stats();
        
        wp_send_json_success($stats);
    }
      /**
     * Handle dashboard refresh
     */
    public function handle_refresh_dashboard() {
        check_ajax_referer('petition_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'environmental-platform-petitions'));
        }
        
        // Clear any cached data
        wp_cache_flush();
        
        wp_send_json_success(__('Dashboard refreshed', 'environmental-platform-petitions'));
    }
      /**
     * Handle analytics updates
     */
    public function handle_update_analytics() {
        check_ajax_referer('petition_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'environmental-platform-petitions'));
        }
        
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        
        $analytics = new EPP_Analytics();
        $data = $analytics->get_analytics_data($start_date, $end_date);
        
        wp_send_json_success($data);
    }
}

/**
 * Initialize the plugin
 */
function environmental_platform_petitions() {
    return Environmental_Platform_Petitions::get_instance();
}

// Start the plugin
environmental_platform_petitions();

/**
 * Activation hook
 */
register_activation_hook(__FILE__, function() {
    require_once EPP_PLUGIN_DIR . 'includes/class-database.php';
    EPP_Database::create_tables();
    flush_rewrite_rules();
});

/**
 * Deactivation hook
 */
register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});
