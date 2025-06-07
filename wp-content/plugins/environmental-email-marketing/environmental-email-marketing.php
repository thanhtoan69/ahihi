<?php
/**
 * Plugin Name: Environmental Email Marketing Integration
 * Plugin URI: https://moitruong.local/environmental-platform
 * Description: Phase 52 - Comprehensive email marketing integration for Environmental Platform with Mailchimp/SendGrid, automated sequences, newsletter management, and analytics.
 * Version: 1.0.0
 * Author: Environmental Platform Team
 * License: GPL v2 or later
 * Text Domain: environmental-email-marketing
 * Domain Path: /languages
 * 
 * @package EnvironmentalEmailMarketing
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EEM_PLUGIN_VERSION', '1.0.0');
define('EEM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('EEM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EEM_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Environmental Email Marketing Class
 */
class Environmental_Email_Marketing {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Email service providers
     */
    private $email_providers = array();
    
    /**
     * Campaign manager
     */
    private $campaign_manager;
    
    /**
     * Automation engine
     */
    private $automation_engine;
    
    /**
     * Analytics tracker
     */
    private $analytics_tracker;
    
    /**
     * Template engine
     */
    private $template_engine;
    
    /**
     * Get single instance
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
        add_action('plugins_loaded', array($this, 'init_plugin'));
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate_plugin'));
    }
    
    /**
     * Initialize plugin
     */
    public function init_plugin() {
        // Check dependencies
        if (!$this->check_dependencies()) {
            return;
        }
        
        // Load text domain
        load_plugin_textdomain('environmental-email-marketing', false, dirname(EEM_PLUGIN_BASENAME) . '/languages');
        
        // Initialize components
        $this->load_includes();
        $this->init_hooks();
        $this->init_email_providers();
        $this->init_components();
        
        // Admin interface
        if (is_admin()) {
            new EEM_Admin();
        }
        
        // REST API endpoints
        new EEM_REST_API();
        
        do_action('eem_plugin_loaded');
    }
    
    /**
     * Check plugin dependencies
     */
    private function check_dependencies() {
        // Check if WooCommerce is active (optional)
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
        }
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', array($this, 'php_version_notice'));
            return false;
        }
        
        return true;
    }
    
    /**
     * Load plugin includes
     */
    private function load_includes() {
        // Core classes
        require_once EEM_PLUGIN_PATH . 'includes/class-eem-database-manager.php';
        require_once EEM_PLUGIN_PATH . 'includes/class-eem-subscriber-manager.php';
        require_once EEM_PLUGIN_PATH . 'includes/class-eem-campaign-manager.php';
        require_once EEM_PLUGIN_PATH . 'includes/class-eem-automation-engine.php';
        require_once EEM_PLUGIN_PATH . 'includes/class-eem-template-engine.php';
        require_once EEM_PLUGIN_PATH . 'includes/class-eem-analytics-tracker.php';
        require_once EEM_PLUGIN_PATH . 'includes/class-eem-list-manager.php';
        require_once EEM_PLUGIN_PATH . 'includes/class-eem-segmentation-engine.php';
        
        // Email service providers
        require_once EEM_PLUGIN_PATH . 'includes/providers/class-eem-mailchimp-provider.php';
        require_once EEM_PLUGIN_PATH . 'includes/providers/class-eem-sendgrid-provider.php';
        require_once EEM_PLUGIN_PATH . 'includes/providers/class-eem-mailgun-provider.php';
        require_once EEM_PLUGIN_PATH . 'includes/providers/class-eem-amazon-ses-provider.php';
        require_once EEM_PLUGIN_PATH . 'includes/providers/class-eem-native-provider.php';
        
        // Integration classes
        require_once EEM_PLUGIN_PATH . 'includes/integrations/class-eem-woocommerce-integration.php';
        require_once EEM_PLUGIN_PATH . 'includes/integrations/class-eem-petition-integration.php';
        require_once EEM_PLUGIN_PATH . 'includes/integrations/class-eem-event-integration.php';
        require_once EEM_PLUGIN_PATH . 'includes/integrations/class-eem-quiz-integration.php';
        
        // Admin classes
        if (is_admin()) {
            require_once EEM_PLUGIN_PATH . 'includes/admin/class-eem-admin.php';
            require_once EEM_PLUGIN_PATH . 'includes/admin/class-eem-admin-campaigns.php';
            require_once EEM_PLUGIN_PATH . 'includes/admin/class-eem-admin-subscribers.php';
            require_once EEM_PLUGIN_PATH . 'includes/admin/class-eem-admin-analytics.php';
            require_once EEM_PLUGIN_PATH . 'includes/admin/class-eem-admin-settings.php';
        }
        
        // Frontend classes
        require_once EEM_PLUGIN_PATH . 'includes/frontend/class-eem-subscription-forms.php';
        require_once EEM_PLUGIN_PATH . 'includes/frontend/class-eem-unsubscribe-handler.php';
        require_once EEM_PLUGIN_PATH . 'includes/frontend/class-eem-preference-center.php';
        
        // REST API
        require_once EEM_PLUGIN_PATH . 'includes/api/class-eem-rest-api.php';
        
        // Utilities
        require_once EEM_PLUGIN_PATH . 'includes/utils/class-eem-logger.php';
        require_once EEM_PLUGIN_PATH . 'includes/utils/class-eem-validator.php';
        require_once EEM_PLUGIN_PATH . 'includes/utils/class-eem-encryption.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('wp_footer', array($this, 'add_newsletter_modal'));
        
        // Admin hooks
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX hooks
        add_action('wp_ajax_eem_subscribe', array($this, 'handle_subscription'));
        add_action('wp_ajax_nopriv_eem_subscribe', array($this, 'handle_subscription'));
        add_action('wp_ajax_eem_unsubscribe', array($this, 'handle_unsubscription'));
        add_action('wp_ajax_nopriv_eem_unsubscribe', array($this, 'handle_unsubscription'));
        add_action('wp_ajax_eem_update_preferences', array($this, 'handle_preference_update'));
        add_action('wp_ajax_nopriv_eem_update_preferences', array($this, 'handle_preference_update'));
        
        // Cron hooks
        add_action('eem_send_scheduled_campaigns', array($this, 'send_scheduled_campaigns'));
        add_action('eem_process_automation_sequences', array($this, 'process_automation_sequences'));
        add_action('eem_cleanup_old_analytics', array($this, 'cleanup_old_analytics'));
        add_action('eem_sync_with_providers', array($this, 'sync_with_providers'));
        
        // Integration hooks
        add_action('woocommerce_order_status_completed', array($this, 'trigger_purchase_automation'));
        add_action('wp_insert_post', array($this, 'trigger_content_automation'), 10, 2);
        add_action('user_register', array($this, 'trigger_welcome_automation'));
        add_action('epp_petition_signed', array($this, 'trigger_petition_automation'));
        
        // Shortcodes
        add_shortcode('eem_subscription_form', array($this, 'subscription_form_shortcode'));
        add_shortcode('eem_unsubscribe_form', array($this, 'unsubscribe_form_shortcode'));
        add_shortcode('eem_preference_center', array($this, 'preference_center_shortcode'));
        
        // Widget support
        add_action('widgets_init', array($this, 'register_widgets'));
    }
    
    /**
     * Initialize email service providers
     */
    private function init_email_providers() {
        $this->email_providers = array(
            'mailchimp' => new EEM_Mailchimp_Provider(),
            'sendgrid' => new EEM_SendGrid_Provider(),
            'mailgun' => new EEM_Mailgun_Provider(),
            'amazon_ses' => new EEM_Amazon_SES_Provider(),
            'native' => new EEM_Native_Provider()
        );
        
        // Apply filters for custom providers
        $this->email_providers = apply_filters('eem_email_providers', $this->email_providers);
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        $this->campaign_manager = new EEM_Campaign_Manager();
        $this->automation_engine = new EEM_Automation_Engine();
        $this->analytics_tracker = new EEM_Analytics_Tracker();
        $this->template_engine = new EEM_Template_Engine();
        
        // Initialize integrations
        new EEM_WooCommerce_Integration();
        new EEM_Petition_Integration();
        new EEM_Event_Integration();
        new EEM_Quiz_Integration();
        
        // Initialize frontend components
        new EEM_Subscription_Forms();
        new EEM_Unsubscribe_Handler();
        new EEM_Preference_Center();
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        wp_enqueue_script(
            'eem-frontend',
            EEM_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            EEM_PLUGIN_VERSION,
            true
        );
        
        wp_enqueue_style(
            'eem-frontend',
            EEM_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            EEM_PLUGIN_VERSION
        );
        
        wp_localize_script('eem-frontend', 'eem_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eem_nonce'),
            'messages' => array(
                'subscribing' => __('Subscribing...', 'environmental-email-marketing'),
                'subscribed' => __('Successfully subscribed!', 'environmental-email-marketing'),
                'error' => __('An error occurred. Please try again.', 'environmental-email-marketing'),
                'invalid_email' => __('Please enter a valid email address.', 'environmental-email-marketing'),
                'already_subscribed' => __('This email is already subscribed.', 'environmental-email-marketing')
            )
        ));
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'environmental-email') === false) {
            return;
        }
        
        wp_enqueue_script(
            'eem-admin',
            EEM_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker'),
            EEM_PLUGIN_VERSION,
            true
        );
        
        wp_enqueue_style(
            'eem-admin',
            EEM_PLUGIN_URL . 'assets/css/admin.css',
            array('wp-color-picker'),
            EEM_PLUGIN_VERSION
        );
        
        wp_localize_script('eem-admin', 'eem_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eem_admin_nonce'),
            'messages' => array(
                'campaign_sent' => __('Campaign sent successfully!', 'environmental-email-marketing'),
                'campaign_saved' => __('Campaign saved successfully!', 'environmental-email-marketing'),
                'confirm_delete' => __('Are you sure you want to delete this campaign?', 'environmental-email-marketing'),
                'test_email_sent' => __('Test email sent successfully!', 'environmental-email-marketing')
            )
        ));
    }
    
    /**
     * Add newsletter modal to footer
     */
    public function add_newsletter_modal() {
        $modal_enabled = get_option('eem_modal_enabled', true);
        $modal_delay = get_option('eem_modal_delay', 5000);
        
        if (!$modal_enabled) {
            return;
        }
        
        include EEM_PLUGIN_PATH . 'templates/newsletter-modal.php';
    }
    
    /**
     * Handle subscription
     */
    public function handle_subscription() {
        check_ajax_referer('eem_nonce', 'nonce');
        
        $email = sanitize_email($_POST['email']);
        $name = sanitize_text_field($_POST['name'] ?? '');
        $lists = array_map('sanitize_text_field', $_POST['lists'] ?? array('environmental_newsletter'));
        $source = sanitize_text_field($_POST['source'] ?? 'website');
        $preferences = array_map('sanitize_text_field', $_POST['preferences'] ?? array());
        
        if (!is_email($email)) {
            wp_send_json_error(__('Invalid email address', 'environmental-email-marketing'));
        }
        
        $subscriber_manager = new EEM_Subscriber_Manager();
        $result = $subscriber_manager->subscribe($email, $name, $lists, $source, $preferences);
        
        if ($result) {
            // Track subscription event
            $this->analytics_tracker->track_subscription($email, $source, $lists);
            
            // Trigger welcome automation
            $this->automation_engine->trigger_welcome_sequence($email, $lists);
            
            wp_send_json_success(__('Successfully subscribed!', 'environmental-email-marketing'));
        } else {
            wp_send_json_error(__('Subscription failed. Please try again.', 'environmental-email-marketing'));
        }
    }
    
    /**
     * Handle unsubscription
     */
    public function handle_unsubscription() {
        check_ajax_referer('eem_nonce', 'nonce');
        
        $email = sanitize_email($_POST['email']);
        $lists = array_map('sanitize_text_field', $_POST['lists'] ?? array());
        
        if (!is_email($email)) {
            wp_send_json_error(__('Invalid email address', 'environmental-email-marketing'));
        }
        
        $subscriber_manager = new EEM_Subscriber_Manager();
        $result = $subscriber_manager->unsubscribe($email, $lists);
        
        if ($result) {
            // Track unsubscription event
            $this->analytics_tracker->track_unsubscription($email, $lists);
            
            wp_send_json_success(__('Successfully unsubscribed!', 'environmental-email-marketing'));
        } else {
            wp_send_json_error(__('Unsubscription failed. Please try again.', 'environmental-email-marketing'));
        }
    }
    
    /**
     * Handle preference update
     */
    public function handle_preference_update() {
        check_ajax_referer('eem_nonce', 'nonce');
        
        $email = sanitize_email($_POST['email']);
        $preferences = array_map('sanitize_text_field', $_POST['preferences'] ?? array());
        
        if (!is_email($email)) {
            wp_send_json_error(__('Invalid email address', 'environmental-email-marketing'));
        }
        
        $subscriber_manager = new EEM_Subscriber_Manager();
        $result = $subscriber_manager->update_preferences($email, $preferences);
        
        if ($result) {
            wp_send_json_success(__('Preferences updated successfully!', 'environmental-email-marketing'));
        } else {
            wp_send_json_error(__('Failed to update preferences. Please try again.', 'environmental-email-marketing'));
        }
    }
    
    /**
     * Send scheduled campaigns
     */
    public function send_scheduled_campaigns() {
        $this->campaign_manager->send_scheduled_campaigns();
    }
    
    /**
     * Process automation sequences
     */
    public function process_automation_sequences() {
        $this->automation_engine->process_sequences();
    }
    
    /**
     * Cleanup old analytics
     */
    public function cleanup_old_analytics() {
        $this->analytics_tracker->cleanup_old_data();
    }
    
    /**
     * Sync with providers
     */
    public function sync_with_providers() {
        $active_provider = get_option('eem_active_provider', 'native');
        
        if (isset($this->email_providers[$active_provider])) {
            $this->email_providers[$active_provider]->sync_subscribers();
        }
    }
    
    /**
     * Trigger purchase automation
     */
    public function trigger_purchase_automation($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        $email = $order->get_billing_email();
        $products = array();
        
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product) {
                $products[] = array(
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'category' => wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'names')),
                    'eco_score' => get_post_meta($product->get_id(), '_eco_score', true)
                );
            }
        }
        
        $this->automation_engine->trigger_purchase_sequence($email, $order_id, $products);
    }
    
    /**
     * Trigger content automation
     */
    public function trigger_content_automation($post_id, $post) {
        if ($post->post_status !== 'publish' || wp_is_post_revision($post_id)) {
            return;
        }
        
        $allowed_types = array('post', 'environmental_post', 'env_event', 'env_petition');
        if (!in_array($post->post_type, $allowed_types)) {
            return;
        }
        
        $this->automation_engine->trigger_content_sequence($post_id, $post->post_type);
    }
    
    /**
     * Trigger welcome automation
     */
    public function trigger_welcome_automation($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }
        
        $this->automation_engine->trigger_welcome_sequence($user->user_email, array('environmental_newsletter'));
    }
    
    /**
     * Trigger petition automation
     */
    public function trigger_petition_automation($petition_id, $email) {
        $this->automation_engine->trigger_petition_sequence($petition_id, $email);
    }
    
    /**
     * Subscription form shortcode
     */
    public function subscription_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'lists' => 'environmental_newsletter',
            'style' => 'default',
            'show_name' => 'true',
            'show_preferences' => 'false',
            'button_text' => __('Subscribe', 'environmental-email-marketing'),
            'success_message' => __('Thank you for subscribing!', 'environmental-email-marketing')
        ), $atts);
        
        ob_start();
        include EEM_PLUGIN_PATH . 'templates/subscription-form.php';
        return ob_get_clean();
    }
    
    /**
     * Unsubscribe form shortcode
     */
    public function unsubscribe_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'style' => 'default',
            'button_text' => __('Unsubscribe', 'environmental-email-marketing'),
            'success_message' => __('Successfully unsubscribed!', 'environmental-email-marketing')
        ), $atts);
        
        ob_start();
        include EEM_PLUGIN_PATH . 'templates/unsubscribe-form.php';
        return ob_get_clean();
    }
    
    /**
     * Preference center shortcode
     */
    public function preference_center_shortcode($atts) {
        $atts = shortcode_atts(array(
            'email' => '',
            'token' => ''
        ), $atts);
        
        ob_start();
        include EEM_PLUGIN_PATH . 'templates/preference-center.php';
        return ob_get_clean();
    }
    
    /**
     * Register widgets
     */
    public function register_widgets() {
        require_once EEM_PLUGIN_PATH . 'includes/widgets/class-eem-subscription-widget.php';
        register_widget('EEM_Subscription_Widget');
    }
    
    /**
     * Plugin activation
     */
    public function activate_plugin() {
        // Create database tables
        $database_manager = new EEM_Database_Manager();
        $database_manager->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Schedule cron jobs
        $this->schedule_cron_jobs();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set activation flag
        update_option('eem_plugin_activated', true);
        update_option('eem_plugin_version', EEM_PLUGIN_VERSION);
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate_plugin() {
        // Clear scheduled cron jobs
        wp_clear_scheduled_hook('eem_send_scheduled_campaigns');
        wp_clear_scheduled_hook('eem_process_automation_sequences');
        wp_clear_scheduled_hook('eem_cleanup_old_analytics');
        wp_clear_scheduled_hook('eem_sync_with_providers');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        $defaults = array(
            'eem_active_provider' => 'native',
            'eem_from_name' => get_bloginfo('name'),
            'eem_from_email' => get_option('admin_email'),
            'eem_reply_to' => get_option('admin_email'),
            'eem_unsubscribe_page' => 0,
            'eem_preference_page' => 0,
            'eem_double_optin' => true,
            'eem_track_opens' => true,
            'eem_track_clicks' => true,
            'eem_modal_enabled' => true,
            'eem_modal_delay' => 5000,
            'eem_gdpr_compliance' => true,
            'eem_data_retention_days' => 365,
            'eem_environmental_focus' => true
        );
        
        foreach ($defaults as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }
    
    /**
     * Schedule cron jobs
     */
    private function schedule_cron_jobs() {
        // Send scheduled campaigns - every 5 minutes
        if (!wp_next_scheduled('eem_send_scheduled_campaigns')) {
            wp_schedule_event(time(), 'eem_5min', 'eem_send_scheduled_campaigns');
        }
        
        // Process automation sequences - every 10 minutes
        if (!wp_next_scheduled('eem_process_automation_sequences')) {
            wp_schedule_event(time(), 'eem_10min', 'eem_process_automation_sequences');
        }
        
        // Cleanup old analytics - daily
        if (!wp_next_scheduled('eem_cleanup_old_analytics')) {
            wp_schedule_event(time(), 'daily', 'eem_cleanup_old_analytics');
        }
        
        // Sync with providers - hourly
        if (!wp_next_scheduled('eem_sync_with_providers')) {
            wp_schedule_event(time(), 'hourly', 'eem_sync_with_providers');
        }
    }
    
    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-warning"><p>' . 
             __('Environmental Email Marketing: WooCommerce integration features will be limited without WooCommerce installed.', 'environmental-email-marketing') . 
             '</p></div>';
    }
    
    /**
     * PHP version notice
     */
    public function php_version_notice() {
        echo '<div class="notice notice-error"><p>' . 
             sprintf(__('Environmental Email Marketing requires PHP 7.4 or higher. You are running PHP %s.', 'environmental-email-marketing'), PHP_VERSION) . 
             '</p></div>';
    }
    
    /**
     * Get email provider
     */
    public function get_email_provider($provider_name = null) {
        if (!$provider_name) {
            $provider_name = get_option('eem_active_provider', 'native');
        }
        
        return isset($this->email_providers[$provider_name]) ? $this->email_providers[$provider_name] : null;
    }
    
    /**
     * Get campaign manager
     */
    public function get_campaign_manager() {
        return $this->campaign_manager;
    }
    
    /**
     * Get automation engine
     */
    public function get_automation_engine() {
        return $this->automation_engine;
    }
    
    /**
     * Get analytics tracker
     */
    public function get_analytics_tracker() {
        return $this->analytics_tracker;
    }
    
    /**
     * Get template engine
     */
    public function get_template_engine() {
        return $this->template_engine;
    }
}

// Add custom cron schedules
add_filter('cron_schedules', function($schedules) {
    $schedules['eem_5min'] = array(
        'interval' => 300,
        'display' => __('Every 5 minutes', 'environmental-email-marketing')
    );
    
    $schedules['eem_10min'] = array(
        'interval' => 600,
        'display' => __('Every 10 minutes', 'environmental-email-marketing')
    );
    
    return $schedules;
});

// Initialize the plugin
function eem_init() {
    return Environmental_Email_Marketing::get_instance();
}

// Hook into WordPress
add_action('plugins_loaded', 'eem_init');

// Global access function
function eem() {
    return Environmental_Email_Marketing::get_instance();
}
