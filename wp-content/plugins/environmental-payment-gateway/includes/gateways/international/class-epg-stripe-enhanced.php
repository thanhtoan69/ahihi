<?php
/**
 * Enhanced Stripe Payment Gateway
 * 
 * Advanced Stripe integration with support for Payment Intents,
 * 3D Secure, Apple Pay, Google Pay, and subscription payments.
 * 
 * @package EnvironmentalPaymentGateway
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enhanced Stripe Gateway Class
 */
class EPG_Stripe_Enhanced extends EPG_Gateway_Base {
    
    /**
     * Gateway ID
     */
    public $id = 'stripe_enhanced';
    
    /**
     * Stripe API version
     */
    const API_VERSION = '2023-10-16';
    
    /**
     * Supported currencies
     */
    private $supported_currencies = array(
        'USD', 'EUR', 'GBP', 'AUD', 'CAD', 'CHF', 'DKK', 'NOK', 'SEK', 'JPY',
        'HKD', 'SGD', 'NZD', 'MXN', 'PLN', 'CZK', 'BGN', 'RON', 'HUF', 'HRK',
        'INR', 'KRW', 'THB', 'MYR', 'BRL', 'ZAR'
    );
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        // Additional hooks for Stripe
        add_action('wp_enqueue_scripts', array($this, 'enqueue_stripe_scripts'));
        add_action('woocommerce_api_stripe_enhanced_webhook', array($this, 'handle_stripe_webhook'));
        add_action('woocommerce_checkout_process', array($this, 'validate_checkout'));
        
        // AJAX handlers
        add_action('wp_ajax_epg_stripe_create_payment_intent', array($this, 'ajax_create_payment_intent'));
        add_action('wp_ajax_nopriv_epg_stripe_create_payment_intent', array($this, 'ajax_create_payment_intent'));
        add_action('wp_ajax_epg_stripe_confirm_payment', array($this, 'ajax_confirm_payment'));
        add_action('wp_ajax_nopriv_epg_stripe_confirm_payment', array($this, 'ajax_confirm_payment'));
    }
    
    /**
     * Get method title
     */
    public function get_method_title() {
        return __('Stripe Enhanced', 'environmental-payment-gateway');
    }
    
    /**
     * Get method description
     */
    public function get_method_description() {
        return __('Accept payments via Stripe with advanced features including 3D Secure, Apple Pay, Google Pay, and subscription support.', 'environmental-payment-gateway');
    }
    
    /**
     * Get icon
     */
    public function get_icon() {
        return '<img src="' . EPG_PLUGIN_URL . 'assets/images/stripe-logo.png" alt="Stripe" style="max-height: 30px;" />';
    }
    
    /**
     * Initialize form fields
     */
    public function init_form_fields() {
        parent::init_form_fields();
        
        $this->form_fields = array_merge($this->form_fields, array(
            'publishable_key' => array(
                'title' => __('Publishable Key', 'environmental-payment-gateway'),
                'type' => 'text',
                'description' => __('Enter your Stripe publishable key.', 'environmental-payment-gateway'),
                'default' => '',
                'desc_tip' => true,
            ),
            'secret_key' => array(
                'title' => __('Secret Key', 'environmental-payment-gateway'),
                'type' => 'password',
                'description' => __('Enter your Stripe secret key.', 'environmental-payment-gateway'),
                'default' => '',
                'desc_tip' => true,
            ),
            'webhook_secret' => array(
                'title' => __('Webhook Secret', 'environmental-payment-gateway'),
                'type' => 'password',
                'description' => __('Enter your Stripe webhook endpoint secret.', 'environmental-payment-gateway'),
                'default' => '',
                'desc_tip' => true,
            ),
            'payment_methods' => array(
                'title' => __('Payment Methods', 'environmental-payment-gateway'),
                'type' => 'multiselect',
                'description' => __('Select available payment methods.', 'environmental-payment-gateway'),
                'default' => array('card'),
                'options' => array(
                    'card' => __('Credit/Debit Cards', 'environmental-payment-gateway'),
                    'apple_pay' => __('Apple Pay', 'environmental-payment-gateway'),
                    'google_pay' => __('Google Pay', 'environmental-payment-gateway'),
                    'sepa_debit' => __('SEPA Direct Debit', 'environmental-payment-gateway'),
                    'giropay' => __('Giropay', 'environmental-payment-gateway'),
                    'ideal' => __('iDEAL', 'environmental-payment-gateway'),
                    'bancontact' => __('Bancontact', 'environmental-payment-gateway'),
                    'sofort' => __('SOFORT', 'environmental-payment-gateway'),
                    'p24' => __('Przelewy24', 'environmental-payment-gateway'),
                    'eps' => __('EPS', 'environmental-payment-gateway'),
                ),
                'desc_tip' => true,
            ),
            'capture_method' => array(
                'title' => __('Capture Method', 'environmental-payment-gateway'),
                'type' => 'select',
                'description' => __('Choose when to capture payments.', 'environmental-payment-gateway'),
                'default' => 'automatic',
                'options' => array(
                    'automatic' => __('Automatic (Immediate)', 'environmental-payment-gateway'),
                    'manual' => __('Manual (Authorize only)', 'environmental-payment-gateway'),
                ),
                'desc_tip' => true,
            ),
            '3d_secure' => array(
                'title' => __('3D Secure', 'environmental-payment-gateway'),
                'type' => 'select',
                'description' => __('3D Secure authentication settings.', 'environmental-payment-gateway'),
                'default' => 'automatic',
                'options' => array(
                    'automatic' => __('Automatic (Recommended)', 'environmental-payment-gateway'),
                    'any' => __('Request when supported', 'environmental-payment-gateway'),
                    'challenge_only' => __('Request challenge flow', 'environmental-payment-gateway'),
                ),
                'desc_tip' => true,
            ),
            'save_cards' => array(
                'title' => __('Save Cards', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Allow customers to save cards for future purchases', 'environmental-payment-gateway'),
                'default' => 'yes',
                'description' => __('Enable to allow customers to save their payment methods.', 'environmental-payment-gateway'),
            ),
            'statement_descriptor' => array(
                'title' => __('Statement Descriptor', 'environmental-payment-gateway'),
                'type' => 'text',
                'description' => __('Text that appears on customer bank statements (22 characters max).', 'environmental-payment-gateway'),
                'default' => substr(get_bloginfo('name'), 0, 22),
                'desc_tip' => true,
            ),
            'receipt_email' => array(
                'title' => __('Receipt Email', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Send Stripe receipt emails to customers', 'environmental-payment-gateway'),
                'default' => 'no',
            ),
        ));
    }
    
    /**
     * Get supported currencies
     */
    public function get_supported_currencies() {
        return $this->supported_currencies;
    }
    
    /**
     * Check if gateway is available
     */
    public function is_available() {
        if (!parent::is_available()) {
            return false;
        }
        
        // Check required API keys
        if (empty($this->get_option('publishable_key')) || empty($this->get_option('secret_key'))) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Enqueue Stripe scripts
     */
    public function enqueue_stripe_scripts() {
        if (!is_checkout() && !is_account_page()) {
            return;
        }
        
        // Enqueue Stripe.js
        wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/', array(), null, true);
        
        // Enqueue our Stripe handler
        wp_enqueue_script(
            'epg-stripe-enhanced',
            EPG_PLUGIN_URL . 'assets/js/stripe-enhanced.js',
            array('jquery', 'stripe-js', 'wc-checkout'),
            EPG_PLUGIN_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('epg-stripe-enhanced', 'epg_stripe_params', array(
            'publishable_key' => $this->get_option('publishable_key'),
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('epg_stripe_nonce'),
            'payment_methods' => $this->get_option('payment_methods', array('card')),
            'save_cards' => $this->get_option('save_cards', 'no'),
            'appearance' => array(
                'theme' => 'stripe',
                'variables' => array(
                    'colorPrimary' => get_theme_mod('primary_color', '#0073aa'),
                ),
            ),
            'messages' => array(
                'payment_failed' => __('Payment failed. Please try again.', 'environmental-payment-gateway'),
                'processing' => __('Processing payment...', 'environmental-payment-gateway'),
                'confirm' => __('Confirm Payment', 'environmental-payment-gateway'),
            ),
        ));
        
        // Enqueue styles
        wp_enqueue_style(
            'epg-stripe-enhanced',
            EPG_PLUGIN_URL . 'assets/css/stripe-enhanced.css',
            array(),
            EPG_PLUGIN_VERSION
        );
    }
    
    /**
     * Payment fields
     */
    public function payment_fields() {
        $description = $this->get_description();
        if ($description) {
            echo wpautop(wptexturize($description));
        }
        
        echo '<div id="epg-stripe-card-element">';
        echo '<div id="epg-stripe-elements">';
        echo '<!-- Stripe Elements will be inserted here -->';
        echo '</div>';
        echo '<div id="epg-stripe-card-errors" role="alert"></div>';
        echo '</div>';
        
        // Saved payment methods
        if ('yes' === $this->get_option('save_cards') && is_user_logged_in()) {
            $this->display_saved_payment_methods();
        }
        
        echo '<input type="hidden" id="epg-stripe-payment-intent-id" name="stripe_payment_intent_id" />';
        echo '<input type="hidden" id="epg-stripe-payment-method-id" name="stripe_payment_method_id" />';
    }
    
    /**
     * Display saved payment methods
     */
    private function display_saved_payment_methods() {
        $customer_id = get_current_user_id();
        $saved_methods = $this->get_saved_payment_methods($customer_id);
        
        if (!empty($saved_methods)) {
            echo '<div id="epg-stripe-saved-methods">';
            echo '<h4>' . __('Saved Payment Methods', 'environmental-payment-gateway') . '</h4>';
            
            foreach ($saved_methods as $method) {
                echo '<label>';
                echo '<input type="radio" name="epg_stripe_saved_method" value="' . esc_attr($method['id']) . '" />';
                echo esc_html($method['display_name']);
                echo '</label>';
            }
            
            echo '<label>';
            echo '<input type="radio" name="epg_stripe_saved_method" value="" checked />';
            echo __('Use a new payment method', 'environmental-payment-gateway');
            echo '</label>';
            echo '</div>';
        }
        
        if ('yes' === $this->get_option('save_cards')) {
            echo '<p>';
            echo '<label>';
            echo '<input type="checkbox" id="epg-stripe-save-method" name="epg_stripe_save_method" value="1" />';
            echo __('Save payment method for future purchases', 'environmental-payment-gateway');
            echo '</label>';
            echo '</p>';
        }
    }
    
    /**
     * Process payment
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return array(
                'result' => 'failure',
                'messages' => __('Invalid order.', 'environmental-payment-gateway'),
            );
        }
        
        try {
            // Get payment intent ID from form
            $payment_intent_id = isset($_POST['stripe_payment_intent_id']) ? sanitize_text_field($_POST['stripe_payment_intent_id']) : '';
            $payment_method_id = isset($_POST['stripe_payment_method_id']) ? sanitize_text_field($_POST['stripe_payment_method_id']) : '';
            $saved_method_id = isset($_POST['epg_stripe_saved_method']) ? sanitize_text_field($_POST['epg_stripe_saved_method']) : '';
            
            if ($saved_method_id) {
                // Use saved payment method
                return $this->process_payment_with_saved_method($order, $saved_method_id);
            }
            
            if ($payment_intent_id) {
                // Confirm existing payment intent
                return $this->confirm_payment_intent($order, $payment_intent_id);
            }
            
            // Create new payment intent
            return $this->create_and_confirm_payment_intent($order, $payment_method_id);
            
        } catch (Exception $e) {
            $this->log('Payment processing error: ' . $e->getMessage(), 'error');
            wc_add_notice(__('Payment processing failed. Please try again.', 'environmental-payment-gateway'), 'error');
            return array('result' => 'failure');
        }
    }
    
    /**
     * AJAX: Create payment intent
     */
    public function ajax_create_payment_intent() {
        check_ajax_referer('epg_stripe_nonce', 'nonce');
        
        $order_id = intval($_POST['order_id']);
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wp_send_json_error(array('message' => 'Invalid order'));
        }
        
        try {
            $payment_intent = $this->create_payment_intent($order);
            
            wp_send_json_success(array(
                'client_secret' => $payment_intent->client_secret,
                'payment_intent_id' => $payment_intent->id,
            ));
            
        } catch (Exception $e) {
            $this->log('Payment intent creation error: ' . $e->getMessage(), 'error');
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * AJAX: Confirm payment
     */
    public function ajax_confirm_payment() {
        check_ajax_referer('epg_stripe_nonce', 'nonce');
        
        $order_id = intval($_POST['order_id']);
        $payment_intent_id = sanitize_text_field($_POST['payment_intent_id']);
        
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wp_send_json_error(array('message' => 'Invalid order'));
        }
        
        try {
            $result = $this->confirm_payment_intent($order, $payment_intent_id);
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            $this->log('Payment confirmation error: ' . $e->getMessage(), 'error');
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * Create payment intent
     */
    private function create_payment_intent($order) {
        $amount = intval($order->get_total() * 100); // Convert to cents
        $currency = strtolower($order->get_currency());
        
        $args = array(
            'amount' => $amount,
            'currency' => $currency,
            'payment_method_types' => $this->get_option('payment_methods', array('card')),
            'capture_method' => $this->get_option('capture_method', 'automatic'),
            'confirmation_method' => 'manual',
            'confirm' => false,
            'metadata' => array(
                'order_id' => $order->get_id(),
                'order_number' => $order->get_order_number(),
                'customer_id' => $order->get_customer_id(),
                'site_url' => get_site_url(),
            ),
        );
        
        // Statement descriptor
        if ($descriptor = $this->get_option('statement_descriptor')) {
            $args['statement_descriptor'] = substr($descriptor, 0, 22);
        }
        
        // Receipt email
        if ('yes' === $this->get_option('receipt_email') && $order->get_billing_email()) {
            $args['receipt_email'] = $order->get_billing_email();
        }
        
        // Customer information
        if ($order->get_customer_id()) {
            $stripe_customer_id = $this->get_or_create_stripe_customer($order);
            if ($stripe_customer_id) {
                $args['customer'] = $stripe_customer_id;
            }
        }
        
        // Shipping information
        if ($order->needs_shipping_address()) {
            $args['shipping'] = array(
                'name' => $order->get_formatted_shipping_full_name(),
                'address' => array(
                    'line1' => $order->get_shipping_address_1(),
                    'line2' => $order->get_shipping_address_2(),
                    'city' => $order->get_shipping_city(),
                    'state' => $order->get_shipping_state(),
                    'postal_code' => $order->get_shipping_postcode(),
                    'country' => $order->get_shipping_country(),
                ),
            );
        }
        
        return $this->stripe_api_request('payment_intents', $args, 'POST');
    }
    
    /**
     * Confirm payment intent
     */
    private function confirm_payment_intent($order, $payment_intent_id) {
        $payment_intent = $this->stripe_api_request("payment_intents/{$payment_intent_id}");
        
        if ($payment_intent->status === 'succeeded') {
            return $this->handle_successful_payment($order, $payment_intent);
        }
        
        if ($payment_intent->status === 'requires_action') {
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order),
                'requires_action' => true,
                'client_secret' => $payment_intent->client_secret,
            );
        }
        
        throw new Exception('Payment intent in unexpected status: ' . $payment_intent->status);
    }
    
    /**
     * Handle successful payment
     */
    private function handle_successful_payment($order, $payment_intent) {
        // Record payment
        $order->payment_complete($payment_intent->id);
        
        // Add order note
        $order->add_order_note(sprintf(
            __('Stripe payment completed. Payment Intent ID: %s', 'environmental-payment-gateway'),
            $payment_intent->id
        ));
        
        // Save payment method if requested
        if (isset($_POST['epg_stripe_save_method']) && $_POST['epg_stripe_save_method'] && $order->get_customer_id()) {
            $this->save_payment_method($order->get_customer_id(), $payment_intent->payment_method);
        }
        
        // Update analytics
        $this->update_payment_analytics($order, 'completed', $payment_intent);
        
        // Clear cart
        WC()->cart->empty_cart();
        
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order),
        );
    }
    
    /**
     * Process refund
     */
    public function process_gateway_refund($order, $amount, $reason) {
        if (empty($amount) || $amount <= 0) {
            return new WP_Error('invalid_amount', 'Invalid refund amount');
        }
        
        $payment_intent_id = $order->get_transaction_id();
        
        if (empty($payment_intent_id)) {
            return new WP_Error('no_transaction_id', 'No transaction ID found for this order');
        }
        
        try {
            $refund_amount = intval($amount * 100); // Convert to cents
            
            $args = array(
                'payment_intent' => $payment_intent_id,
                'amount' => $refund_amount,
                'metadata' => array(
                    'order_id' => $order->get_id(),
                    'reason' => $reason,
                ),
            );
            
            if ($reason) {
                $args['reason'] = 'requested_by_customer';
            }
            
            $refund = $this->stripe_api_request('refunds', $args, 'POST');
            
            $order->add_order_note(sprintf(
                __('Stripe refund completed. Refund ID: %s, Amount: %s %s', 'environmental-payment-gateway'),
                $refund->id,
                $amount,
                $order->get_currency()
            ));
            
            // Update analytics
            $this->update_refund_analytics($order, $amount, $refund);
            
            return true;
            
        } catch (Exception $e) {
            $this->log('Refund error: ' . $e->getMessage(), 'error');
            return new WP_Error('refund_failed', $e->getMessage());
        }
    }
    
    /**
     * Handle Stripe webhook
     */
    public function handle_stripe_webhook() {
        $payload = @file_get_contents('php://input');
        $signature = isset($_SERVER['HTTP_STRIPE_SIGNATURE']) ? $_SERVER['HTTP_STRIPE_SIGNATURE'] : '';
        
        $webhook_secret = $this->get_option('webhook_secret');
        
        if (empty($webhook_secret)) {
            $this->log('Webhook secret not configured', 'error');
            http_response_code(400);
            exit;
        }
        
        try {
            // Verify webhook signature
            if (!$this->security->validate_stripe_signature($payload, $signature, $webhook_secret)) {
                $this->log('Invalid webhook signature', 'error');
                http_response_code(401);
                exit;
            }
            
            $event = json_decode($payload, true);
            
            $this->log('Webhook received: ' . $event['type'] . ' - ' . $event['id']);
            
            // Handle different event types
            switch ($event['type']) {
                case 'payment_intent.succeeded':
                    $this->handle_payment_intent_succeeded($event['data']['object']);
                    break;
                    
                case 'payment_intent.payment_failed':
                    $this->handle_payment_intent_failed($event['data']['object']);
                    break;
                    
                case 'charge.dispute.created':
                    $this->handle_dispute_created($event['data']['object']);
                    break;
                    
                case 'invoice.payment_succeeded':
                    $this->handle_subscription_payment($event['data']['object']);
                    break;
                    
                case 'customer.subscription.deleted':
                    $this->handle_subscription_cancelled($event['data']['object']);
                    break;
                    
                default:
                    $this->log('Unhandled webhook event: ' . $event['type']);
            }
            
            http_response_code(200);
            echo 'OK';
            
        } catch (Exception $e) {
            $this->log('Webhook processing error: ' . $e->getMessage(), 'error');
            http_response_code(500);
            exit;
        }
        
        exit;
    }
    
    /**
     * Get or create Stripe customer
     */
    private function get_or_create_stripe_customer($order) {
        $customer_id = $order->get_customer_id();
        
        if (!$customer_id) {
            return null;
        }
        
        // Check if customer already has Stripe ID
        $stripe_customer_id = get_user_meta($customer_id, '_stripe_customer_id', true);
        
        if ($stripe_customer_id) {
            return $stripe_customer_id;
        }
        
        // Create new Stripe customer
        try {
            $args = array(
                'email' => $order->get_billing_email(),
                'name' => $order->get_formatted_billing_full_name(),
                'phone' => $order->get_billing_phone(),
                'address' => array(
                    'line1' => $order->get_billing_address_1(),
                    'line2' => $order->get_billing_address_2(),
                    'city' => $order->get_billing_city(),
                    'state' => $order->get_billing_state(),
                    'postal_code' => $order->get_billing_postcode(),
                    'country' => $order->get_billing_country(),
                ),
                'metadata' => array(
                    'wp_user_id' => $customer_id,
                    'site_url' => get_site_url(),
                ),
            );
            
            $customer = $this->stripe_api_request('customers', $args, 'POST');
            
            // Save Stripe customer ID
            update_user_meta($customer_id, '_stripe_customer_id', $customer->id);
            
            return $customer->id;
            
        } catch (Exception $e) {
            $this->log('Error creating Stripe customer: ' . $e->getMessage(), 'error');
            return null;
        }
    }
    
    /**
     * Make Stripe API request
     */
    private function stripe_api_request($endpoint, $args = array(), $method = 'GET') {
        $api_key = $this->get_option('secret_key');
        $base_url = 'https://api.stripe.com/v1/';
        
        $headers = array(
            'Authorization' => 'Bearer ' . $api_key,
            'Stripe-Version' => self::API_VERSION,
            'Content-Type' => 'application/x-www-form-urlencoded',
        );
        
        $request_args = array(
            'headers' => $headers,
            'method' => $method,
            'timeout' => 30,
        );
        
        if ($method === 'POST' && !empty($args)) {
            $request_args['body'] = http_build_query($args);
        }
        
        $url = $base_url . $endpoint;
        
        if ($method === 'GET' && !empty($args)) {
            $url .= '?' . http_build_query($args);
        }
        
        $response = wp_remote_request($url, $request_args);
        
        if (is_wp_error($response)) {
            throw new Exception('API request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
        
        if (isset($data->error)) {
            throw new Exception('Stripe API error: ' . $data->error->message);
        }
        
        return $data;
    }
    
    /**
     * Health check
     */
    public function health_check() {
        $checks = array(
            'api_keys' => false,
            'connection' => false,
            'webhooks' => false,
        );
        
        // Check API keys
        if (!empty($this->get_option('publishable_key')) && !empty($this->get_option('secret_key'))) {
            $checks['api_keys'] = true;
        }
        
        // Test API connection
        try {
            $this->stripe_api_request('account');
            $checks['connection'] = true;
        } catch (Exception $e) {
            $this->log('Health check failed: ' . $e->getMessage(), 'error');
        }
        
        // Check webhook configuration
        if (!empty($this->get_option('webhook_secret'))) {
            $checks['webhooks'] = true;
        }
        
        return $checks;
    }
    
    /**
     * Get gateway capabilities
     */
    public function get_gateway_capabilities() {
        return array(
            'refunds' => true,
            'subscriptions' => true,
            'pre_auth' => true,
            'tokenization' => true,
            'apple_pay' => in_array('apple_pay', $this->get_option('payment_methods', array())),
            'google_pay' => in_array('google_pay', $this->get_option('payment_methods', array())),
            '3d_secure' => true,
            'multi_currency' => true,
            'webhooks' => true,
        );
    }
    
    /**
     * Update payment analytics
     */
    private function update_payment_analytics($order, $status, $payment_intent) {
        $analytics = new EPG_Payment_Analytics();
        
        $data = array(
            'transaction_id' => $payment_intent->id,
            'order_id' => $order->get_id(),
            'gateway' => $this->id,
            'amount' => $order->get_total(),
            'currency' => $order->get_currency(),
            'status' => $status,
            'customer_country' => $order->get_billing_country(),
            'payment_method' => 'stripe_card',
        );
        
        $analytics->record_transaction($data);
    }
}
