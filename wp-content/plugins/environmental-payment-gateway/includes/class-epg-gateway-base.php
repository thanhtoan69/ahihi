<?php
/**
 * Base Payment Gateway Class
 * 
 * Provides common functionality for all payment gateways
 * in the Environmental Payment Gateway system.
 * 
 * @package EnvironmentalPaymentGateway
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base class for all payment gateways
 */
abstract class EPG_Gateway_Base extends WC_Payment_Gateway {
    
    /**
     * Security handler
     */
    protected $security;
    
    /**
     * Notification handler
     */
    protected $notifications;
    
    /**
     * Currency converter
     */
    protected $currency_converter;
    
    /**
     * Gateway capabilities
     */
    protected $gateway_capabilities = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize security and utilities
        $this->security = new EPG_Security_Handler();
        $this->notifications = new EPG_Notification_Handler();
        $this->currency_converter = new EPG_Currency_Converter();
        
        // Set default properties
        $this->has_fields = true;
        $this->method_title = $this->get_method_title();
        $this->method_description = $this->get_method_description();
        
        // Initialize form fields and settings
        $this->init_form_fields();
        $this->init_settings();
        
        // Set gateway properties from settings
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        
        // Add hooks
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    protected function init_hooks() {
        // Settings save hook
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        
        // Webhook handler
        add_action('woocommerce_api_' . $this->id, array($this, 'handle_webhook'));
        
        // Return handler
        add_action('woocommerce_api_' . $this->id . '_return', array($this, 'handle_return'));
        
        // Scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Get method title - to be overridden by child classes
     */
    abstract protected function get_method_title();
    
    /**
     * Get method description - to be overridden by child classes
     */
    abstract protected function get_method_description();
    
    /**
     * Initialize form fields - to be overridden by child classes
     */
    abstract public function init_form_fields();
    
    /**
     * Process payment - to be overridden by child classes
     */
    abstract public function process_payment($order_id);
    
    /**
     * Common form fields
     */
    protected function get_common_form_fields() {
        return array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => sprintf(__('Enable %s', 'environmental-payment-gateway'), $this->get_method_title()),
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'environmental-payment-gateway'),
                'type' => 'text',
                'description' => __('This controls the title customers see during checkout.', 'environmental-payment-gateway'),
                'default' => $this->get_method_title(),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'environmental-payment-gateway'),
                'type' => 'textarea',
                'description' => __('Payment method description that customers will see on your checkout.', 'environmental-payment-gateway'),
                'default' => $this->get_method_description(),
                'desc_tip' => true,
            ),
            'test_mode' => array(
                'title' => __('Test Mode', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Enable Test Mode', 'environmental-payment-gateway'),
                'default' => 'yes',
                'description' => __('Use sandbox/test environment for transactions.', 'environmental-payment-gateway'),
            ),
            'debug_mode' => array(
                'title' => __('Debug Mode', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Enable Debug Logging', 'environmental-payment-gateway'),
                'default' => 'no',
                'description' => __('Save debug messages to the WooCommerce System Status log.', 'environmental-payment-gateway'),
            ),
        );
    }
    
    /**
     * Check if gateway is available
     */
    public function is_available() {
        if (!parent::is_available()) {
            return false;
        }
        
        // Check currency support
        if (!$this->is_currency_supported()) {
            return false;
        }
        
        // Check country restrictions
        if (!$this->is_country_supported()) {
            return false;
        }
        
        // Check if properly configured
        if (!$this->is_properly_configured()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if currency is supported
     */
    protected function is_currency_supported() {
        $supported_currencies = $this->get_supported_currencies();
        return empty($supported_currencies) || in_array(get_woocommerce_currency(), $supported_currencies);
    }
    
    /**
     * Check if country is supported
     */
    protected function is_country_supported() {
        $supported_countries = $this->get_supported_countries();
        if (empty($supported_countries)) {
            return true;
        }
        
        $customer_country = WC()->customer ? WC()->customer->get_billing_country() : '';
        return in_array($customer_country, $supported_countries);
    }
    
    /**
     * Check if gateway is properly configured
     */
    protected function is_properly_configured() {
        $required_settings = $this->get_required_settings();
        foreach ($required_settings as $setting) {
            if (empty($this->get_option($setting))) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Get supported currencies - to be overridden by child classes
     */
    protected function get_supported_currencies() {
        return array();
    }
    
    /**
     * Get supported countries - to be overridden by child classes
     */
    protected function get_supported_countries() {
        return array();
    }
    
    /**
     * Get required settings - to be overridden by child classes
     */
    protected function get_required_settings() {
        return array();
    }
    
    /**
     * Handle webhook notifications
     */
    public function handle_webhook() {
        $this->log('Webhook received');
        
        // Verify webhook signature
        if (!$this->verify_webhook_signature()) {
            $this->log('Invalid webhook signature', 'error');
            wp_die('Invalid signature', 'Webhook Error', array('response' => 400));
        }
        
        // Process webhook data
        $result = $this->process_webhook_data();
        
        if (is_wp_error($result)) {
            $this->log('Webhook processing failed: ' . $result->get_error_message(), 'error');
            wp_die($result->get_error_message(), 'Webhook Error', array('response' => 400));
        }
        
        wp_die('OK', 'Success', array('response' => 200));
    }
    
    /**
     * Handle return from payment processor
     */
    public function handle_return() {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wp_die('Invalid order', 'Payment Error', array('response' => 400));
        }
        
        // Process return data
        $result = $this->process_return_data($order);
        
        if (is_wp_error($result)) {
            wc_add_notice($result->get_error_message(), 'error');
            wp_redirect($order->get_checkout_payment_url());
            exit;
        }
        
        // Redirect to order received page
        wp_redirect($this->get_return_url($order));
        exit;
    }
    
    /**
     * Verify webhook signature - to be overridden by child classes
     */
    protected function verify_webhook_signature() {
        return true;
    }
    
    /**
     * Process webhook data - to be overridden by child classes
     */
    protected function process_webhook_data() {
        return new WP_Error('not_implemented', 'Webhook processing not implemented');
    }
    
    /**
     * Process return data - to be overridden by child classes
     */
    protected function process_return_data($order) {
        return new WP_Error('not_implemented', 'Return processing not implemented');
    }
    
    /**
     * Log messages
     */
    protected function log($message, $level = 'info') {
        if ('yes' === $this->get_option('debug_mode')) {
            if (empty($this->logger)) {
                $this->logger = wc_get_logger();
            }
            $this->logger->log($level, $message, array('source' => $this->id));
        }
    }
    
    /**
     * Process refund
     */
    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return new WP_Error('invalid_order', 'Invalid order for refund');
        }
        
        // Implement refund logic in child classes
        return $this->process_gateway_refund($order, $amount, $reason);
    }
    
    /**
     * Process gateway-specific refund - to be overridden by child classes
     */
    protected function process_gateway_refund($order, $amount, $reason) {
        return new WP_Error('not_supported', 'Refunds not supported by this gateway');
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_scripts() {
        if (!is_checkout() && !is_account_page()) {
            return;
        }
        
        wp_enqueue_script(
            'epg-' . $this->id,
            EPG_PLUGIN_URL . 'assets/js/gateway-' . $this->id . '.js',
            array('jquery', 'wc-checkout'),
            EPG_PLUGIN_VERSION,
            true
        );
        
        wp_localize_script('epg-' . $this->id, 'epg_' . $this->id . '_params', $this->get_js_params());
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ('woocommerce_page_wc-settings' !== $hook) {
            return;
        }
        
        wp_enqueue_script(
            'epg-admin-' . $this->id,
            EPG_PLUGIN_URL . 'assets/js/admin-' . $this->id . '.js',
            array('jquery'),
            EPG_PLUGIN_VERSION,
            true
        );
    }
    
    /**
     * Get JavaScript parameters - to be overridden by child classes
     */
    protected function get_js_params() {
        return array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('epg_' . $this->id . '_nonce'),
            'gateway_id' => $this->id,
        );
    }
    
    /**
     * Get Environmental Donation System processor
     */
    public function get_eds_processor() {
        return new EPG_EDS_Processor_Adapter($this);
    }
    
    /**
     * Update payment status
     */
    protected function update_payment_status($order, $status, $transaction_id = '', $note = '') {
        switch ($status) {
            case 'completed':
                $order->payment_complete($transaction_id);
                break;
            case 'pending':
                $order->update_status('pending', $note);
                break;
            case 'failed':
                $order->update_status('failed', $note);
                break;
            case 'cancelled':
                $order->update_status('cancelled', $note);
                break;
        }
        
        if (!empty($note)) {
            $order->add_order_note($note);
        }
        
        $order->save();
    }
    
    /**
     * Format amount for gateway
     */
    protected function format_amount($amount, $currency = null) {
        if (null === $currency) {
            $currency = get_woocommerce_currency();
        }
        
        $decimals = $this->get_currency_decimals($currency);
        return number_format($amount, $decimals, '.', '');
    }
    
    /**
     * Get currency decimals
     */
    protected function get_currency_decimals($currency) {
        $zero_decimal_currencies = array('JPY', 'KRW', 'VND');
        return in_array($currency, $zero_decimal_currencies) ? 0 : 2;
    }
}
