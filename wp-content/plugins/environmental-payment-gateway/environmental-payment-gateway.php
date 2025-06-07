<?php
/**
 * Plugin Name: Environmental Payment Gateway Integration
 * Plugin URI: https://moitruong.local/environmental-platform
 * Description: Phase 51 - Comprehensive payment gateway integration for Environmental Platform with Vietnamese payment gateways (VNPay, Momo), international options, cryptocurrency support, analytics, and automated invoicing.
 * Version: 1.0.0
 * Author: Environmental Platform Team
 * License: GPL v2 or later
 * Text Domain: environmental-payment-gateway
 * Domain Path: /languages
 * 
 * @package EnvironmentalPaymentGateway
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EPG_PLUGIN_VERSION', '1.0.0');
define('EPG_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('EPG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EPG_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Environmental Payment Gateway Class
 */
class Environmental_Payment_Gateway {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Payment gateways registry
     */
    private $gateways = array();
    
    /**
     * Analytics engine
     */
    private $analytics;
    
    /**
     * Invoice generator
     */
    private $invoice_generator;
    
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
        load_plugin_textdomain('environmental-payment-gateway', false, dirname(EPG_PLUGIN_BASENAME) . '/languages');
        
        // Initialize components
        $this->load_includes();
        $this->init_hooks();
        $this->register_gateways();
        
        // Initialize analytics and invoice system
        $this->analytics = new EPG_Payment_Analytics();
        $this->invoice_generator = new EPG_Invoice_Generator();
        
        // Admin interface
        if (is_admin()) {
            new EPG_Admin();
        }
        
        // REST API endpoints
        new EPG_REST_API();
        
        do_action('epg_plugin_loaded');
    }
    
    /**
     * Check plugin dependencies
     */
    private function check_dependencies() {
        $dependencies = array(
            'WooCommerce' => 'woocommerce/woocommerce.php',
            'Environmental Platform Core' => 'environmental-platform-core/environmental-platform-core.php'
        );
        
        $missing = array();
        foreach ($dependencies as $name => $file) {
            if (!is_plugin_active($file)) {
                $missing[] = $name;
            }
        }
        
        if (!empty($missing)) {
            add_action('admin_notices', function() use ($missing) {
                echo '<div class="error"><p>';
                echo sprintf(
                    __('Environmental Payment Gateway requires the following plugins to be activated: %s', 'environmental-payment-gateway'),
                    implode(', ', $missing)
                );
                echo '</p></div>';
            });
            return false;
        }
        
        return true;
    }
    
    /**
     * Load required files
     */
    private function load_includes() {
        // Core files
        require_once EPG_PLUGIN_PATH . 'includes/class-epg-gateway-base.php';
        require_once EPG_PLUGIN_PATH . 'includes/class-epg-payment-analytics.php';
        require_once EPG_PLUGIN_PATH . 'includes/class-epg-invoice-generator.php';
        require_once EPG_PLUGIN_PATH . 'includes/class-epg-admin.php';
        require_once EPG_PLUGIN_PATH . 'includes/class-epg-rest-api.php';
        
        // Vietnamese payment gateways
        require_once EPG_PLUGIN_PATH . 'includes/gateways/vietnam/class-epg-vnpay-gateway.php';
        require_once EPG_PLUGIN_PATH . 'includes/gateways/vietnam/class-epg-momo-gateway.php';
        require_once EPG_PLUGIN_PATH . 'includes/gateways/vietnam/class-epg-zalopay-gateway.php';
        
        // International payment gateways
        require_once EPG_PLUGIN_PATH . 'includes/gateways/international/class-epg-stripe-enhanced.php';
        require_once EPG_PLUGIN_PATH . 'includes/gateways/international/class-epg-paypal-enhanced.php';
        require_once EPG_PLUGIN_PATH . 'includes/gateways/international/class-epg-wise-gateway.php';
        
        // Cryptocurrency gateways
        require_once EPG_PLUGIN_PATH . 'includes/gateways/crypto/class-epg-bitcoin-gateway.php';
        require_once EPG_PLUGIN_PATH . 'includes/gateways/crypto/class-epg-ethereum-gateway.php';
        require_once EPG_PLUGIN_PATH . 'includes/gateways/crypto/class-epg-coinbase-gateway.php';
        require_once EPG_PLUGIN_PATH . 'includes/gateways/crypto/class-epg-binance-gateway.php';
          // Database schema
        require_once EPG_PLUGIN_PATH . 'includes/class-epg-database-schema.php';
        
        // Utilities
        require_once EPG_PLUGIN_PATH . 'includes/class-epg-currency-converter.php';
        require_once EPG_PLUGIN_PATH . 'includes/class-epg-security-handler.php';
        require_once EPG_PLUGIN_PATH . 'includes/class-epg-notification-handler.php';
    }
      /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // WooCommerce hooks
        add_filter('woocommerce_payment_gateways', array($this, 'add_woocommerce_gateways'));
        add_action('woocommerce_order_status_completed', array($this, 'process_completed_order'));
        add_action('woocommerce_order_refunded', array($this, 'process_refund'));
        
        // Environmental Donation System integration
        add_filter('eds_payment_processors', array($this, 'add_eds_processors'));
        
        // Cryptocurrency price updates
        add_action('epg_update_crypto_rates', array($this, 'update_crypto_rates'));
        if (!wp_next_scheduled('epg_update_crypto_rates')) {
            wp_schedule_event(time(), 'hourly', 'epg_update_crypto_rates');
        }
        
        // Invoice generation
        add_action('woocommerce_order_status_processing', array($this, 'generate_invoice'));
        add_action('woocommerce_order_status_completed', array($this, 'generate_invoice'));
        
        // AJAX endpoints
        add_action('wp_ajax_epg_process_ethereum_payment', array($this, 'ajax_process_ethereum_payment'));
        add_action('wp_ajax_nopriv_epg_process_ethereum_payment', array($this, 'ajax_process_ethereum_payment'));
        add_action('wp_ajax_epg_create_coinbase_charge', array($this, 'ajax_create_coinbase_charge'));
        add_action('wp_ajax_nopriv_epg_create_coinbase_charge', array($this, 'ajax_create_coinbase_charge'));
        add_action('wp_ajax_epg_check_coinbase_payment', array($this, 'ajax_check_coinbase_payment'));
        add_action('wp_ajax_nopriv_epg_check_coinbase_payment', array($this, 'ajax_check_coinbase_payment'));
        add_action('wp_ajax_epg_create_binance_order', array($this, 'ajax_create_binance_order'));
        add_action('wp_ajax_nopriv_epg_create_binance_order', array($this, 'ajax_create_binance_order'));
        add_action('wp_ajax_epg_check_binance_payment', array($this, 'ajax_check_binance_payment'));
        add_action('wp_ajax_nopriv_epg_check_binance_payment', array($this, 'ajax_check_binance_payment'));
    }
    
    /**
     * Register payment gateways
     */
    private function register_gateways() {
        // Vietnamese gateways
        $this->gateways['vnpay'] = 'EPG_VNPay_Gateway';
        $this->gateways['momo'] = 'EPG_Momo_Gateway';
        $this->gateways['zalopay'] = 'EPG_ZaloPay_Gateway';
        
        // Enhanced international gateways
        $this->gateways['stripe_enhanced'] = 'EPG_Stripe_Enhanced';
        $this->gateways['paypal_enhanced'] = 'EPG_PayPal_Enhanced';
        $this->gateways['wise'] = 'EPG_Wise_Gateway';
        
        // Cryptocurrency gateways
        $this->gateways['bitcoin'] = 'EPG_Bitcoin_Gateway';
        $this->gateways['ethereum'] = 'EPG_Ethereum_Gateway';
        $this->gateways['coinbase'] = 'EPG_Coinbase_Gateway';
        $this->gateways['binance'] = 'EPG_Binance_Gateway';
        
        // Allow third-party gateway registration
        $this->gateways = apply_filters('epg_register_gateways', $this->gateways);
    }
    
    /**
     * Add gateways to WooCommerce
     */
    public function add_woocommerce_gateways($gateways) {
        foreach ($this->gateways as $gateway_id => $gateway_class) {
            if (class_exists($gateway_class)) {
                $gateways[] = $gateway_class;
            }
        }
        return $gateways;
    }
    
    /**
     * Add processors to Environmental Donation System
     */
    public function add_eds_processors($processors) {
        foreach ($this->gateways as $gateway_id => $gateway_class) {
            if (class_exists($gateway_class)) {
                $gateway = new $gateway_class();
                if (method_exists($gateway, 'get_eds_processor')) {
                    $processors[$gateway_id] = $gateway->get_eds_processor();
                }
            }
        }
        return $processors;
    }
    
    /**
     * Process completed order
     */
    public function process_completed_order($order_id) {
        $this->analytics->record_payment($order_id);
        do_action('epg_order_completed', $order_id);
    }
    
    /**
     * Process refund
     */
    public function process_refund($order_id, $refund_id) {
        $this->analytics->record_refund($order_id, $refund_id);
        do_action('epg_order_refunded', $order_id, $refund_id);
    }
    
    /**
     * Update cryptocurrency rates
     */
    public function update_crypto_rates() {
        $converter = new EPG_Currency_Converter();
        $converter->update_crypto_rates();
    }
    
    /**
     * Generate invoice
     */
    public function generate_invoice($order_id) {
        $this->invoice_generator->generate_invoice($order_id);
    }
    
    /**
     * Plugin activation
     */
    public function activate_plugin() {
        // Create database tables
        $this->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Schedule cron jobs
        if (!wp_next_scheduled('epg_update_crypto_rates')) {
            wp_schedule_event(time(), 'hourly', 'epg_update_crypto_rates');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate_plugin() {
        // Clear scheduled events
        wp_clear_scheduled_hook('epg_update_crypto_rates');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
      /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Don't load on admin pages
        if (is_admin()) {
            return;
        }
        
        // Main stylesheet
        wp_enqueue_style(
            'epg-frontend-styles',
            EPG_PLUGIN_URL . 'assets/css/epg-styles.css',
            array(),
            EPG_PLUGIN_VERSION
        );
        
        // Load scripts only on checkout and payment pages
        if (is_checkout() || is_wc_endpoint_url('order-pay')) {
            // PayPal Enhanced
            wp_enqueue_script(
                'epg-paypal-enhanced',
                EPG_PLUGIN_URL . 'assets/js/paypal-enhanced.js',
                array('jquery'),
                EPG_PLUGIN_VERSION,
                true
            );
            
            // Wise Transfer
            wp_enqueue_script(
                'epg-wise',
                EPG_PLUGIN_URL . 'assets/js/wise.js',
                array('jquery'),
                EPG_PLUGIN_VERSION,
                true
            );
            
            // Bitcoin payments
            wp_enqueue_script(
                'epg-bitcoin',
                EPG_PLUGIN_URL . 'assets/js/bitcoin.js',
                array('jquery'),
                EPG_PLUGIN_VERSION,
                true
            );
            
            // Ethereum/Web3 payments
            wp_enqueue_script(
                'epg-ethereum',
                EPG_PLUGIN_URL . 'assets/js/ethereum.js',
                array('jquery'),
                EPG_PLUGIN_VERSION,
                true
            );
            
            // Coinbase Commerce
            wp_enqueue_script(
                'epg-coinbase',
                EPG_PLUGIN_URL . 'assets/js/coinbase.js',
                array('jquery'),
                EPG_PLUGIN_VERSION,
                true
            );
            
            // Binance Pay
            wp_enqueue_script(
                'epg-binance',
                EPG_PLUGIN_URL . 'assets/js/binance.js',
                array('jquery'),
                EPG_PLUGIN_VERSION,
                true
            );
            
            // Localize scripts with AJAX URLs and nonces
            wp_localize_script('epg-ethereum', 'epg_ethereum_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('epg_ethereum_nonce'),
                'messages' => array(
                    'processing' => __('Processing payment...', 'environmental-payment-gateway'),
                    'success' => __('Payment successful!', 'environmental-payment-gateway'),
                    'error' => __('Payment failed. Please try again.', 'environmental-payment-gateway')
                )
            ));
            
            wp_localize_script('epg-coinbase', 'epg_coinbase_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('epg_coinbase_nonce'),
                'messages' => array(
                    'processing' => __('Creating payment charge...', 'environmental-payment-gateway'),
                    'checking' => __('Checking payment status...', 'environmental-payment-gateway'),
                    'success' => __('Payment confirmed!', 'environmental-payment-gateway'),
                    'error' => __('Payment error. Please try again.', 'environmental-payment-gateway')
                )
            ));
            
            wp_localize_script('epg-binance', 'epg_binance_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('epg_binance_nonce'),
                'messages' => array(
                    'processing' => __('Creating Binance order...', 'environmental-payment-gateway'),
                    'checking' => __('Checking order status...', 'environmental-payment-gateway'),
                    'success' => __('Payment completed!', 'environmental-payment-gateway'),
                    'error' => __('Order failed. Please try again.', 'environmental-payment-gateway')
                )
            ));
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on relevant admin pages
        if (!in_array($hook, array('woocommerce_page_wc-settings', 'toplevel_page_environmental-platform'))) {
            return;
        }
        
        // Admin stylesheet
        wp_enqueue_style(
            'epg-admin-styles',
            EPG_PLUGIN_URL . 'assets/css/epg-admin.css',
            array(),
            EPG_PLUGIN_VERSION
        );
        
        // Admin JavaScript
        wp_enqueue_script(
            'epg-admin-script',
            EPG_PLUGIN_URL . 'assets/js/epg-admin.js',
            array('jquery'),
            EPG_PLUGIN_VERSION,
            true
        );
        
        // Localize admin script
        wp_localize_script('epg-admin-script', 'epg_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('epg_admin_nonce')
        ));
    }
    
    /**
     * AJAX handler for Ethereum payments
     */
    public function ajax_process_ethereum_payment() {
        check_ajax_referer('epg_ethereum_nonce', 'nonce');
        
        $order_id = intval($_POST['order_id']);
        $tx_hash = sanitize_text_field($_POST['tx_hash']);
        $network = sanitize_text_field($_POST['network']);
        
        if (!$order_id || !$tx_hash) {
            wp_send_json_error(__('Missing required data', 'environmental-payment-gateway'));
        }
        
        // Get Ethereum gateway instance
        $gateway = new EPG_Ethereum_Gateway();
        $result = $gateway->verify_transaction($tx_hash, $order_id, $network);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX handler for creating Coinbase charges
     */
    public function ajax_create_coinbase_charge() {
        check_ajax_referer('epg_coinbase_nonce', 'nonce');
        
        $order_id = intval($_POST['order_id']);
        $cryptocurrency = sanitize_text_field($_POST['cryptocurrency']);
        
        if (!$order_id) {
            wp_send_json_error(__('Invalid order ID', 'environmental-payment-gateway'));
        }
        
        // Get Coinbase gateway instance
        $gateway = new EPG_Coinbase_Gateway();
        $result = $gateway->create_charge($order_id, $cryptocurrency);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX handler for checking Coinbase payment status
     */
    public function ajax_check_coinbase_payment() {
        check_ajax_referer('epg_coinbase_nonce', 'nonce');
        
        $charge_id = sanitize_text_field($_POST['charge_id']);
        
        if (!$charge_id) {
            wp_send_json_error(__('Invalid charge ID', 'environmental-payment-gateway'));
        }
        
        // Get Coinbase gateway instance
        $gateway = new EPG_Coinbase_Gateway();
        $result = $gateway->check_payment_status($charge_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX handler for creating Binance orders
     */
    public function ajax_create_binance_order() {
        check_ajax_referer('epg_binance_nonce', 'nonce');
        
        $order_id = intval($_POST['order_id']);
        $cryptocurrency = sanitize_text_field($_POST['cryptocurrency']);
        
        if (!$order_id) {
            wp_send_json_error(__('Invalid order ID', 'environmental-payment-gateway'));
        }
        
        // Get Binance gateway instance
        $gateway = new EPG_Binance_Gateway();
        $result = $gateway->create_order($order_id, $cryptocurrency);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX handler for checking Binance payment status
     */
    public function ajax_check_binance_payment() {
        check_ajax_referer('epg_binance_nonce', 'nonce');
        
        $order_id = sanitize_text_field($_POST['order_id']);
        
        if (!$order_id) {
            wp_send_json_error(__('Invalid order ID', 'environmental-payment-gateway'));
        }
        
        // Get Binance gateway instance
        $gateway = new EPG_Binance_Gateway();
        $result = $gateway->check_order_status($order_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Create database tables using schema class
     */
    private function create_tables() {
        $schema = new EPG_Database_Schema();
        $schema->create_all_tables();
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        $default_options = array(
            'epg_analytics_enabled' => 'yes',
            'epg_invoice_enabled' => 'yes',
            'epg_crypto_enabled' => 'no',
            'epg_vietnamese_gateways_enabled' => 'yes',
            'epg_notification_enabled' => 'yes',
            'epg_security_level' => 'high'
        );
        
        foreach ($default_options as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }
}

// Initialize the plugin
Environmental_Payment_Gateway::get_instance();
