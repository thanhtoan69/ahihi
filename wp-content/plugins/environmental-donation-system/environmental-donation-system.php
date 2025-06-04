<?php
/**
 * Plugin Name: Environmental Donation & Fundraising System
 * Plugin URI: https://moitruong.com/plugins/donation-system
 * Description: Comprehensive donation and fundraising platform with payment gateway integration, progress tracking, recurring donations, and environmental impact reporting for sustainable causes.
 * Version: 1.0.0
 * Author: Environmental Platform Team
 * License: GPL v2 or later
 * Text Domain: environmental-donation-system
 * Domain Path: /languages
 * 
 * @package EnvironmentalDonationSystem
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EDS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EDS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('EDS_PLUGIN_VERSION', '1.0.0');
define('EDS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Environmental Donation System Class
 */
class EnvironmentalDonationSystem {
    
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
        add_action('wp_ajax_eds_process_donation', array($this, 'handle_process_donation'));
        add_action('wp_ajax_nopriv_eds_process_donation', array($this, 'handle_process_donation'));
        add_action('wp_ajax_eds_get_campaign_data', array($this, 'handle_get_campaign_data'));
        add_action('wp_ajax_nopriv_eds_get_campaign_data', array($this, 'handle_get_campaign_data'));
        add_action('wp_ajax_eds_subscribe_donation', array($this, 'handle_subscribe_donation'));
        add_action('wp_ajax_eds_cancel_subscription', array($this, 'handle_cancel_subscription'));
        add_action('wp_ajax_eds_generate_receipt', array($this, 'handle_generate_receipt'));
        
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
        
        // Cron jobs for recurring donations
        add_action('eds_process_recurring_donations', array($this, 'process_recurring_donations'));
        add_action('eds_send_donation_receipts', array($this, 'send_donation_receipts'));
        add_action('eds_update_campaign_progress', array($this, 'update_campaign_progress'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once EDS_PLUGIN_PATH . 'includes/class-database-setup.php';
        require_once EDS_PLUGIN_PATH . 'includes/class-donation-manager.php';
        require_once EDS_PLUGIN_PATH . 'includes/class-campaign-manager.php';
        require_once EDS_PLUGIN_PATH . 'includes/class-payment-processor.php';
        require_once EDS_PLUGIN_PATH . 'includes/class-receipt-generator.php';
        require_once EDS_PLUGIN_PATH . 'includes/class-recurring-donations.php';
        require_once EDS_PLUGIN_PATH . 'includes/class-impact-tracker.php';
        require_once EDS_PLUGIN_PATH . 'includes/class-notification-system.php';
        require_once EDS_PLUGIN_PATH . 'includes/class-admin-dashboard.php';
        require_once EDS_PLUGIN_PATH . 'includes/class-frontend-templates.php';
        require_once EDS_PLUGIN_PATH . 'includes/class-api-endpoints.php';
        require_once EDS_PLUGIN_PATH . 'includes/class-analytics.php';
    }
    
    /**
     * Initialize plugin components
     */
    public function init_plugin() {
        // Load text domain
        load_plugin_textdomain('environmental-donation-system', false, dirname(EDS_PLUGIN_BASENAME) . '/languages');
        
        // Register custom post types
        $this->register_post_types();
        
        // Register taxonomies
        $this->register_taxonomies();
        
        // Initialize components
        EDS_Donation_Manager::get_instance();
        EDS_Campaign_Manager::get_instance();
        EDS_Payment_Processor::get_instance();
        EDS_Receipt_Generator::get_instance();
        EDS_Recurring_Donations::get_instance();
        EDS_Impact_Tracker::get_instance();
        EDS_Notification_System::get_instance();
        EDS_Frontend_Templates::get_instance();
        EDS_API_Endpoints::get_instance();
        EDS_Analytics::get_instance();
        
        // Custom shortcodes
        add_shortcode('donation_form', array($this, 'donation_form_shortcode'));
        add_shortcode('campaign_progress', array($this, 'campaign_progress_shortcode'));
        add_shortcode('donation_thermometer', array($this, 'donation_thermometer_shortcode'));
        add_shortcode('recent_donations', array($this, 'recent_donations_shortcode'));
        add_shortcode('donor_leaderboard', array($this, 'donor_leaderboard_shortcode'));
        add_shortcode('impact_dashboard', array($this, 'impact_dashboard_shortcode'));
        
        // Schedule cron events if not already scheduled
        if (!wp_next_scheduled('eds_process_recurring_donations')) {
            wp_schedule_event(time(), 'daily', 'eds_process_recurring_donations');
        }
        
        if (!wp_next_scheduled('eds_send_donation_receipts')) {
            wp_schedule_event(time(), 'hourly', 'eds_send_donation_receipts');
        }
        
        if (!wp_next_scheduled('eds_update_campaign_progress')) {
            wp_schedule_event(time(), 'hourly', 'eds_update_campaign_progress');
        }
    }
    
    /**
     * Register custom post types
     */
    private function register_post_types() {
        // Donation Campaign Post Type
        register_post_type('donation_campaign', array(
            'labels' => array(
                'name' => __('Donation Campaigns', 'environmental-donation-system'),
                'singular_name' => __('Donation Campaign', 'environmental-donation-system'),
                'add_new' => __('Add New Campaign', 'environmental-donation-system'),
                'add_new_item' => __('Add New Donation Campaign', 'environmental-donation-system'),
                'edit_item' => __('Edit Campaign', 'environmental-donation-system'),
                'new_item' => __('New Campaign', 'environmental-donation-system'),
                'view_item' => __('View Campaign', 'environmental-donation-system'),
                'search_items' => __('Search Campaigns', 'environmental-donation-system'),
                'not_found' => __('No campaigns found', 'environmental-donation-system'),
                'not_found_in_trash' => __('No campaigns found in trash', 'environmental-donation-system'),
                'menu_name' => __('Campaigns', 'environmental-donation-system')
            ),
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-heart',
            'menu_position' => 25,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'comments', 'author'),
            'rewrite' => array('slug' => 'campaigns'),
            'show_in_rest' => true,
            'capability_type' => 'post',
            'map_meta_cap' => true
        ));
        
        // Organization Post Type
        register_post_type('donation_org', array(
            'labels' => array(
                'name' => __('Organizations', 'environmental-donation-system'),
                'singular_name' => __('Organization', 'environmental-donation-system'),
                'add_new' => __('Add New Organization', 'environmental-donation-system'),
                'add_new_item' => __('Add New Organization', 'environmental-donation-system'),
                'edit_item' => __('Edit Organization', 'environmental-donation-system'),
                'new_item' => __('New Organization', 'environmental-donation-system'),
                'view_item' => __('View Organization', 'environmental-donation-system'),
                'search_items' => __('Search Organizations', 'environmental-donation-system'),
                'not_found' => __('No organizations found', 'environmental-donation-system'),
                'not_found_in_trash' => __('No organizations found in trash', 'environmental-donation-system'),
                'menu_name' => __('Organizations', 'environmental-donation-system')
            ),
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-building',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
            'rewrite' => array('slug' => 'organizations'),
            'show_in_rest' => true,
            'capability_type' => 'post',
            'map_meta_cap' => true
        ));
    }
    
    /**
     * Register taxonomies
     */
    private function register_taxonomies() {
        // Campaign Category
        register_taxonomy('campaign_category', 'donation_campaign', array(
            'labels' => array(
                'name' => __('Campaign Categories', 'environmental-donation-system'),
                'singular_name' => __('Campaign Category', 'environmental-donation-system'),
                'search_items' => __('Search Categories', 'environmental-donation-system'),
                'all_items' => __('All Categories', 'environmental-donation-system'),
                'edit_item' => __('Edit Category', 'environmental-donation-system'),
                'update_item' => __('Update Category', 'environmental-donation-system'),
                'add_new_item' => __('Add New Category', 'environmental-donation-system'),
                'new_item_name' => __('New Category Name', 'environmental-donation-system'),
                'menu_name' => __('Categories', 'environmental-donation-system')
            ),
            'hierarchical' => true,
            'public' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'campaign-category')
        ));
        
        // Organization Type
        register_taxonomy('org_type', 'donation_org', array(
            'labels' => array(
                'name' => __('Organization Types', 'environmental-donation-system'),
                'singular_name' => __('Organization Type', 'environmental-donation-system'),
                'search_items' => __('Search Types', 'environmental-donation-system'),
                'all_items' => __('All Types', 'environmental-donation-system'),
                'edit_item' => __('Edit Type', 'environmental-donation-system'),
                'update_item' => __('Update Type', 'environmental-donation-system'),
                'add_new_item' => __('Add New Type', 'environmental-donation-system'),
                'new_item_name' => __('New Type Name', 'environmental-donation-system'),
                'menu_name' => __('Types', 'environmental-donation-system')
            ),
            'hierarchical' => true,
            'public' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'org-type')
        ));
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        if (is_singular('donation_campaign') || is_post_type_archive('donation_campaign') || is_tax(array('campaign_category'))) {
            // Main frontend styles
            wp_enqueue_style(
                'eds-frontend-style',
                EDS_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                EDS_PLUGIN_VERSION
            );
            
            // Main frontend JavaScript
            wp_enqueue_script(
                'eds-frontend-script',
                EDS_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                EDS_PLUGIN_VERSION,
                true
            );
            
            // Localize script for AJAX
            wp_localize_script('eds-frontend-script', 'eds_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('eds_ajax_nonce'),
                'strings' => array(
                    'processing' => __('Processing...', 'environmental-donation-system'),
                    'error' => __('An error occurred. Please try again.', 'environmental-donation-system'),
                    'success' => __('Thank you for your donation!', 'environmental-donation-system'),
                    'confirm_cancel' => __('Are you sure you want to cancel this subscription?', 'environmental-donation-system'),
                    'invalid_amount' => __('Please enter a valid donation amount.', 'environmental-donation-system'),
                    'min_amount' => __('Minimum donation amount is $5.', 'environmental-donation-system')
                )
            ));
            
            // Payment processor scripts
            wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/', array(), null, true);
            wp_enqueue_script('paypal-js', 'https://www.paypal.com/sdk/js?client-id=' . get_option('eds_paypal_client_id', ''), array(), null, true);
        }
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        $screens = array('post.php', 'post-new.php', 'edit.php', 'donation-campaigns_page_donation-analytics');
        
        if (in_array($hook, $screens) || (isset($_GET['post_type']) && in_array($_GET['post_type'], array('donation_campaign', 'donation_org')))) {
            // Admin styles
            wp_enqueue_style(
                'eds-admin-style',
                EDS_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                EDS_PLUGIN_VERSION
            );
            
            // Admin JavaScript
            wp_enqueue_script(
                'eds-admin-script',
                EDS_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery', 'jquery-ui-datepicker', 'wp-color-picker'),
                EDS_PLUGIN_VERSION,
                true
            );
            
            // Charts for analytics
            wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
            
            // Localize admin script
            wp_localize_script('eds-admin-script', 'eds_admin_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('eds_admin_nonce'),
                'strings' => array(
                    'confirm_delete' => __('Are you sure you want to delete this item?', 'environmental-donation-system'),
                    'saving' => __('Saving...', 'environmental-donation-system'),
                    'saved' => __('Saved successfully!', 'environmental-donation-system'),
                    'error' => __('An error occurred.', 'environmental-donation-system')
                )
            ));
            
            // WordPress media uploader
            wp_enqueue_media();
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('Donation System', 'environmental-donation-system'),
            __('Donations', 'environmental-donation-system'),
            'manage_options',
            'donation-dashboard',
            array($this, 'admin_dashboard_page'),
            'dashicons-heart',
            26
        );
        
        // Submenu pages
        add_submenu_page(
            'donation-dashboard',
            __('Dashboard', 'environmental-donation-system'),
            __('Dashboard', 'environmental-donation-system'),
            'manage_options',
            'donation-dashboard',
            array($this, 'admin_dashboard_page')
        );
        
        add_submenu_page(
            'donation-dashboard',
            __('All Donations', 'environmental-donation-system'),
            __('All Donations', 'environmental-donation-system'),
            'manage_options',
            'donation-list',
            array($this, 'admin_donations_page')
        );
        
        add_submenu_page(
            'donation-dashboard',
            __('Analytics', 'environmental-donation-system'),
            __('Analytics', 'environmental-donation-system'),
            'manage_options',
            'donation-analytics',
            array($this, 'admin_analytics_page')
        );
        
        add_submenu_page(
            'donation-dashboard',
            __('Settings', 'environmental-donation-system'),
            __('Settings', 'environmental-donation-system'),
            'manage_options',
            'donation-settings',
            array($this, 'admin_settings_page')
        );
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        EDS_Database_Setup::create_tables();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set default options
        $default_options = array(
            'eds_currency' => 'USD',
            'eds_currency_symbol' => '$',
            'eds_min_donation' => 5,
            'eds_enable_recurring' => 1,
            'eds_enable_anonymous' => 1,
            'eds_auto_receipt' => 1,
            'eds_stripe_enabled' => 0,
            'eds_paypal_enabled' => 0,
            'eds_bank_transfer_enabled' => 1
        );
        
        foreach ($default_options as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('eds_process_recurring_donations');
        wp_clear_scheduled_hook('eds_send_donation_receipts');
        wp_clear_scheduled_hook('eds_update_campaign_progress');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Template loader
     */
    public function template_loader($template) {
        if (is_singular('donation_campaign')) {
            $plugin_template = EDS_PLUGIN_PATH . 'templates/single-donation_campaign.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        if (is_post_type_archive('donation_campaign')) {
            $plugin_template = EDS_PLUGIN_PATH . 'templates/archive-donation_campaign.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        if (is_singular('donation_org')) {
            $plugin_template = EDS_PLUGIN_PATH . 'templates/single-donation_org.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'donation_receipt';
        $vars[] = 'campaign_id';
        $vars[] = 'donation_action';
        return $vars;
    }
    
    /**
     * Add rewrite rules
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^donation-receipt/([^/]+)/?$',
            'index.php?donation_receipt=$matches[1]',
            'top'
        );
    }
    
    // Admin page callbacks
    public function admin_dashboard_page() {
        include EDS_PLUGIN_PATH . 'admin/dashboard.php';
    }
    
    public function admin_donations_page() {
        include EDS_PLUGIN_PATH . 'admin/donations.php';
    }
    
    public function admin_analytics_page() {
        include EDS_PLUGIN_PATH . 'admin/analytics.php';
    }
    
    public function admin_settings_page() {
        include EDS_PLUGIN_PATH . 'admin/settings.php';
    }
    
    // AJAX handlers
    public function handle_process_donation() {
        check_ajax_referer('eds_ajax_nonce', 'nonce');
        
        $donation_manager = EDS_Donation_Manager::get_instance();
        $result = $donation_manager->process_donation($_POST);
        
        wp_send_json($result);
    }
    
    public function handle_get_campaign_data() {
        check_ajax_referer('eds_ajax_nonce', 'nonce');
        
        $campaign_manager = EDS_Campaign_Manager::get_instance();
        $data = $campaign_manager->get_campaign_data($_POST['campaign_id']);
        
        wp_send_json_success($data);
    }
    
    public function handle_subscribe_donation() {
        check_ajax_referer('eds_ajax_nonce', 'nonce');
        
        $recurring = EDS_Recurring_Donations::get_instance();
        $result = $recurring->create_subscription($_POST);
        
        wp_send_json($result);
    }
    
    public function handle_cancel_subscription() {
        check_ajax_referer('eds_ajax_nonce', 'nonce');
        
        $recurring = EDS_Recurring_Donations::get_instance();
        $result = $recurring->cancel_subscription($_POST['subscription_id']);
        
        wp_send_json($result);
    }
    
    public function handle_generate_receipt() {
        check_ajax_referer('eds_ajax_nonce', 'nonce');
        
        $receipt_generator = EDS_Receipt_Generator::get_instance();
        $result = $receipt_generator->generate_receipt($_POST['donation_id']);
        
        wp_send_json($result);
    }
    
    // Cron job handlers
    public function process_recurring_donations() {
        $recurring = EDS_Recurring_Donations::get_instance();
        $recurring->process_due_payments();
    }
    
    public function send_donation_receipts() {
        $receipt_generator = EDS_Receipt_Generator::get_instance();
        $receipt_generator->send_pending_receipts();
    }
    
    public function update_campaign_progress() {
        $campaign_manager = EDS_Campaign_Manager::get_instance();
        $campaign_manager->update_all_campaign_progress();
    }
    
    // Shortcode handlers
    public function donation_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'campaign_id' => '',
            'amount' => '',
            'style' => 'default'
        ), $atts);
        
        $frontend = EDS_Frontend_Templates::get_instance();
        return $frontend->render_donation_form($atts);
    }
    
    public function campaign_progress_shortcode($atts) {
        $atts = shortcode_atts(array(
            'campaign_id' => '',
            'style' => 'progress_bar'
        ), $atts);
        
        $frontend = EDS_Frontend_Templates::get_instance();
        return $frontend->render_campaign_progress($atts);
    }
    
    public function donation_thermometer_shortcode($atts) {
        $atts = shortcode_atts(array(
            'campaign_id' => '',
            'height' => '300px',
            'color' => '#2ecc71'
        ), $atts);
        
        $frontend = EDS_Frontend_Templates::get_instance();
        return $frontend->render_donation_thermometer($atts);
    }
    
    public function recent_donations_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 5,
            'campaign_id' => '',
            'show_amount' => 'yes',
            'show_anonymous' => 'no'
        ), $atts);
        
        $frontend = EDS_Frontend_Templates::get_instance();
        return $frontend->render_recent_donations($atts);
    }
    
    public function donor_leaderboard_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'period' => 'all_time',
            'campaign_id' => ''
        ), $atts);
        
        $frontend = EDS_Frontend_Templates::get_instance();
        return $frontend->render_donor_leaderboard($atts);
    }
    
    public function impact_dashboard_shortcode($atts) {
        $atts = shortcode_atts(array(
            'campaign_id' => '',
            'metrics' => 'co2,trees,water',
            'style' => 'cards'
        ), $atts);
        
        $frontend = EDS_Frontend_Templates::get_instance();
        return $frontend->render_impact_dashboard($atts);
    }
}

// Initialize the plugin
function eds_init() {
    return EnvironmentalDonationSystem::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'eds_init');

// Activation and deactivation hooks
register_activation_hook(__FILE__, array('EnvironmentalDonationSystem', 'activate'));
register_deactivation_hook(__FILE__, array('EnvironmentalDonationSystem', 'deactivate'));
