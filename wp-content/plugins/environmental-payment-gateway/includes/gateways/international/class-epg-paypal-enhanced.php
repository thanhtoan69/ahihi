<?php
/**
 * Enhanced PayPal Gateway
 * 
 * Advanced PayPal integration with recurring payments, subscription management,
 * enhanced checkout experience, and comprehensive webhook handling.
 * 
 * @package EnvironmentalPaymentGateway
 * @subpackage Gateways\International
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * EPG PayPal Enhanced Gateway Class
 */
class EPG_PayPal_Enhanced extends EPG_Gateway_Base {
    
    /**
     * Gateway ID
     */
    public $id = 'paypal_enhanced';
    
    /**
     * Gateway method title
     */
    public $method_title = 'PayPal Enhanced';
    
    /**
     * Gateway method description
     */
    public $method_description = 'Enhanced PayPal integration with recurring payments and advanced features';
    
    /**
     * Supported features
     */
    public $supports = array(
        'products',
        'refunds',
        'subscriptions',
        'subscription_cancellation',
        'subscription_suspension',
        'subscription_reactivation',
        'subscription_amount_changes',
        'subscription_date_changes',
        'multiple_subscriptions'
    );
    
    /**
     * PayPal API base URL
     */
    private $api_base_url;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        $this->title = __('PayPal Enhanced', 'environmental-payment-gateway');
        $this->description = __('Pay securely with PayPal. Support for one-time payments and recurring subscriptions.', 'environmental-payment-gateway');
        $this->icon = EPG_PLUGIN_URL . 'assets/images/paypal-logo.png';
        
        // Set API URL based on environment
        $this->api_base_url = $this->is_sandbox() ? 
            'https://api.sandbox.paypal.com' : 
            'https://api.paypal.com';
        
        // Initialize settings
        $this->init_form_fields();
        $this->init_settings();
        
        // Load settings
        $this->enabled = $this->get_option('enabled');
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        
        // Hooks
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'handle_webhook'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Subscription hooks
        add_action('woocommerce_scheduled_subscription_payment_' . $this->id, array($this, 'process_subscription_payment'), 10, 2);
        add_action('wcs_resubscribe_order_created', array($this, 'delete_resubscribe_meta'), 10);
        
        // Admin hooks for subscription management
        add_filter('woocommerce_subscription_settings_api_' . $this->id, array($this, 'admin_subscription_fields'));
    }
    
    /**
     * Initialize gateway settings form fields
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Enable PayPal Enhanced Gateway', 'environmental-payment-gateway'),
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'environmental-payment-gateway'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'environmental-payment-gateway'),
                'default' => __('PayPal Enhanced', 'environmental-payment-gateway'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'environmental-payment-gateway'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'environmental-payment-gateway'),
                'default' => __('Pay securely with PayPal. Support environmental causes with every transaction.', 'environmental-payment-gateway'),
            ),
            'environment' => array(
                'title' => __('Environment', 'environmental-payment-gateway'),
                'type' => 'select',
                'description' => __('Select the PayPal environment.', 'environmental-payment-gateway'),
                'default' => 'sandbox',
                'desc_tip' => true,
                'options' => array(
                    'sandbox' => __('Sandbox', 'environmental-payment-gateway'),
                    'live' => __('Live', 'environmental-payment-gateway'),
                )
            ),
            'client_id' => array(
                'title' => __('Client ID', 'environmental-payment-gateway'),
                'type' => 'text',
                'description' => __('Enter your PayPal Client ID.', 'environmental-payment-gateway'),
                'default' => '',
                'desc_tip' => true,
            ),
            'client_secret' => array(
                'title' => __('Client Secret', 'environmental-payment-gateway'),
                'type' => 'password',
                'description' => __('Enter your PayPal Client Secret.', 'environmental-payment-gateway'),
                'default' => '',
                'desc_tip' => true,
            ),
            'webhook_id' => array(
                'title' => __('Webhook ID', 'environmental-payment-gateway'),
                'type' => 'text',
                'description' => __('Enter your PayPal Webhook ID for event verification.', 'environmental-payment-gateway'),
                'default' => '',
                'desc_tip' => true,
            ),
            'advanced_features' => array(
                'title' => __('Advanced Features', 'environmental-payment-gateway'),
                'type' => 'title',
                'description' => '',
            ),
            'enable_subscriptions' => array(
                'title' => __('Enable Subscriptions', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Enable recurring payment support', 'environmental-payment-gateway'),
                'default' => 'yes'
            ),
            'enable_express_checkout' => array(
                'title' => __('Express Checkout', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Enable PayPal Express Checkout', 'environmental-payment-gateway'),
                'default' => 'yes'
            ),
            'enable_smart_buttons' => array(
                'title' => __('Smart Payment Buttons', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Enable PayPal Smart Payment Buttons', 'environmental-payment-gateway'),
                'default' => 'yes'
            ),
            'button_color' => array(
                'title' => __('Button Color', 'environmental-payment-gateway'),
                'type' => 'select',
                'description' => __('Select PayPal button color theme.', 'environmental-payment-gateway'),
                'default' => 'blue',
                'options' => array(
                    'gold' => __('Gold', 'environmental-payment-gateway'),
                    'blue' => __('Blue', 'environmental-payment-gateway'),
                    'silver' => __('Silver', 'environmental-payment-gateway'),
                    'white' => __('White', 'environmental-payment-gateway'),
                    'black' => __('Black', 'environmental-payment-gateway')
                )
            ),
            'environmental_settings' => array(
                'title' => __('Environmental Integration', 'environmental-payment-gateway'),
                'type' => 'title',
                'description' => '',
            ),
            'carbon_offset_percentage' => array(
                'title' => __('Carbon Offset Percentage', 'environmental-payment-gateway'),
                'type' => 'number',
                'description' => __('Percentage of transaction to donate for carbon offset (0-10%).', 'environmental-payment-gateway'),
                'default' => '2',
                'custom_attributes' => array(
                    'min' => '0',
                    'max' => '10',
                    'step' => '0.1'
                )
            ),
            'green_messaging' => array(
                'title' => __('Green Messaging', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Show environmental impact messaging during checkout', 'environmental-payment-gateway'),
                'default' => 'yes'
            )
        );
    }
    
    /**
     * Process payment
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        
        try {
            // Create PayPal order
            $paypal_order = $this->create_paypal_order($order);
            
            if ($paypal_order && isset($paypal_order['id'])) {
                // Store PayPal order ID
                $order->update_meta_data('_paypal_order_id', $paypal_order['id']);
                $order->save();
                
                // Get approval URL
                $approval_url = $this->get_approval_url($paypal_order);
                
                if ($approval_url) {
                    return array(
                        'result' => 'success',
                        'redirect' => $approval_url
                    );
                }
            }
            
            throw new Exception(__('Unable to create PayPal order', 'environmental-payment-gateway'));
            
        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            $this->log_error('Payment processing failed: ' . $e->getMessage());
            return array('result' => 'fail');
        }
    }
    
    /**
     * Create PayPal order
     */
    private function create_paypal_order($order) {
        $access_token = $this->get_access_token();
        if (!$access_token) {
            throw new Exception(__('Unable to authenticate with PayPal', 'environmental-payment-gateway'));
        }
        
        $order_data = array(
            'intent' => 'CAPTURE',
            'purchase_units' => array(
                array(
                    'reference_id' => $order->get_id(),
                    'amount' => array(
                        'currency_code' => $order->get_currency(),
                        'value' => number_format($order->get_total(), 2, '.', '')
                    ),
                    'description' => sprintf(__('Order #%s from %s', 'environmental-payment-gateway'), 
                        $order->get_order_number(), 
                        get_bloginfo('name')
                    ),
                    'custom_id' => $order->get_id(),
                    'invoice_id' => $order->get_order_number()
                )
            ),
            'application_context' => array(
                'brand_name' => get_bloginfo('name'),
                'locale' => 'en-US',
                'landing_page' => 'BILLING',
                'shipping_preference' => 'SET_PROVIDED_ADDRESS',
                'user_action' => 'PAY_NOW',
                'return_url' => $this->get_return_url($order),
                'cancel_url' => $order->get_cancel_order_url_raw()
            )
        );
        
        // Add environmental messaging if enabled
        if ($this->get_option('green_messaging') === 'yes') {
            $carbon_offset = floatval($this->get_option('carbon_offset_percentage', 2));
            if ($carbon_offset > 0) {
                $offset_amount = $order->get_total() * ($carbon_offset / 100);
                $order_data['purchase_units'][0]['description'] .= sprintf(
                    __(' | %.2f%% (%.2f %s) will be donated for carbon offset', 'environmental-payment-gateway'),
                    $carbon_offset,
                    $offset_amount,
                    $order->get_currency()
                );
            }
        }
        
        $response = wp_remote_post($this->api_base_url . '/v2/checkout/orders', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $access_token,
                'PayPal-Request-Id' => uniqid(),
                'Prefer' => 'return=representation'
            ),
            'body' => json_encode($order_data),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 201) {
            $error_message = isset($body['message']) ? $body['message'] : __('Unknown PayPal error', 'environmental-payment-gateway');
            throw new Exception($error_message);
        }
        
        return $body;
    }
    
    /**
     * Get PayPal access token
     */
    private function get_access_token() {
        $client_id = $this->get_option('client_id');
        $client_secret = $this->get_option('client_secret');
        
        if (empty($client_id) || empty($client_secret)) {
            return false;
        }
        
        // Check cached token
        $cached_token = get_transient('epg_paypal_access_token_' . $this->id);
        if ($cached_token) {
            return $cached_token;
        }
        
        $response = wp_remote_post($this->api_base_url . '/v1/oauth2/token', array(
            'headers' => array(
                'Accept' => 'application/json',
                'Accept-Language' => 'en_US',
                'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $client_secret)
            ),
            'body' => 'grant_type=client_credentials',
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['access_token'])) {
            // Cache token for 85% of its lifetime
            $expires_in = isset($body['expires_in']) ? intval($body['expires_in']) * 0.85 : 3000;
            set_transient('epg_paypal_access_token_' . $this->id, $body['access_token'], $expires_in);
            return $body['access_token'];
        }
        
        return false;
    }
    
    /**
     * Get approval URL from PayPal order
     */
    private function get_approval_url($paypal_order) {
        if (isset($paypal_order['links'])) {
            foreach ($paypal_order['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    return $link['href'];
                }
            }
        }
        return false;
    }
    
    /**
     * Handle webhook notifications
     */
    public function handle_webhook() {
        $raw_body = file_get_contents('php://input');
        $headers = getallheaders();
        
        // Verify webhook signature
        if (!$this->verify_webhook_signature($raw_body, $headers)) {
            http_response_code(401);
            exit('Unauthorized');
        }
        
        $webhook_data = json_decode($raw_body, true);
        
        if (!$webhook_data || !isset($webhook_data['event_type'])) {
            http_response_code(400);
            exit('Invalid webhook data');
        }
        
        $this->process_webhook_event($webhook_data);
        
        http_response_code(200);
        exit('OK');
    }
    
    /**
     * Process webhook event
     */
    private function process_webhook_event($webhook_data) {
        $event_type = $webhook_data['event_type'];
        
        switch ($event_type) {
            case 'CHECKOUT.ORDER.APPROVED':
                $this->handle_order_approved($webhook_data);
                break;
                
            case 'PAYMENT.CAPTURE.COMPLETED':
                $this->handle_payment_captured($webhook_data);
                break;
                
            case 'PAYMENT.CAPTURE.DENIED':
                $this->handle_payment_denied($webhook_data);
                break;
                
            case 'BILLING.SUBSCRIPTION.CREATED':
                $this->handle_subscription_created($webhook_data);
                break;
                
            case 'BILLING.SUBSCRIPTION.CANCELLED':
                $this->handle_subscription_cancelled($webhook_data);
                break;
                
            case 'PAYMENT.SALE.REFUNDED':
                $this->handle_payment_refunded($webhook_data);
                break;
                
            default:
                $this->log_info('Unhandled webhook event: ' . $event_type);
        }
    }
    
    /**
     * Handle order approved event
     */
    private function handle_order_approved($webhook_data) {
        $paypal_order_id = $webhook_data['resource']['id'];
        $order = $this->get_order_by_paypal_id($paypal_order_id);
        
        if ($order) {
            $order->add_order_note(__('PayPal order approved by customer', 'environmental-payment-gateway'));
            
            // Capture payment automatically
            $this->capture_payment($order, $paypal_order_id);
        }
    }
    
    /**
     * Handle payment captured event
     */
    private function handle_payment_captured($webhook_data) {
        $capture_id = $webhook_data['resource']['id'];
        $custom_id = $webhook_data['resource']['custom_id'] ?? null;
        
        if ($custom_id) {
            $order = wc_get_order($custom_id);
            if ($order) {
                $order->payment_complete($capture_id);
                $order->add_order_note(
                    sprintf(__('PayPal payment captured. Transaction ID: %s', 'environmental-payment-gateway'), $capture_id)
                );
                
                // Process carbon offset if enabled
                $this->process_carbon_offset($order);
            }
        }
    }
    
    /**
     * Process carbon offset donation
     */
    private function process_carbon_offset($order) {
        $carbon_offset = floatval($this->get_option('carbon_offset_percentage', 2));
        
        if ($carbon_offset > 0) {
            $offset_amount = $order->get_total() * ($carbon_offset / 100);
            
            // Create offset donation record
            $order->update_meta_data('_carbon_offset_amount', $offset_amount);
            $order->update_meta_data('_carbon_offset_processed', 'yes');
            $order->save();
            
            // Trigger carbon offset action for Environmental Donation System
            do_action('epg_carbon_offset_donation', $order->get_id(), $offset_amount);
            
            $order->add_order_note(
                sprintf(__('Carbon offset donation processed: %s %s', 'environmental-payment-gateway'), 
                    $offset_amount, 
                    $order->get_currency()
                )
            );
        }
    }
    
    /**
     * Process subscription payment
     */
    public function process_subscription_payment($amount_to_charge, $renewal_order) {
        $subscription = wcs_get_subscription($renewal_order->get_meta('_subscription_renewal'));
        
        if (!$subscription) {
            return;
        }
        
        $paypal_subscription_id = $subscription->get_meta('_paypal_subscription_id');
        
        if ($paypal_subscription_id) {
            // PayPal handles recurring payments automatically
            $renewal_order->add_order_note(__('Recurring payment processed by PayPal subscription', 'environmental-payment-gateway'));
            $renewal_order->payment_complete();
        }
    }
    
    /**
     * Process refund
     */
    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);
        $transaction_id = $order->get_transaction_id();
        
        if (!$transaction_id) {
            return new WP_Error('paypal_refund_error', __('Transaction ID not found', 'environmental-payment-gateway'));
        }
        
        try {
            $access_token = $this->get_access_token();
            if (!$access_token) {
                throw new Exception(__('Unable to authenticate with PayPal', 'environmental-payment-gateway'));
            }
            
            $refund_data = array(
                'amount' => array(
                    'value' => number_format($amount ? $amount : $order->get_total(), 2, '.', ''),
                    'currency_code' => $order->get_currency()
                ),
                'note_to_payer' => $reason ? $reason : __('Refund processed', 'environmental-payment-gateway')
            );
            
            $response = wp_remote_post($this->api_base_url . '/v2/payments/captures/' . $transaction_id . '/refund', array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $access_token,
                    'PayPal-Request-Id' => uniqid()
                ),
                'body' => json_encode($refund_data),
                'timeout' => 60
            ));
            
            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }
            
            $body = json_decode(wp_remote_retrieve_body($response), true);
            $status_code = wp_remote_retrieve_response_code($response);
            
            if ($status_code === 201 && isset($body['id'])) {
                $order->add_order_note(
                    sprintf(__('PayPal refund completed. Refund ID: %s', 'environmental-payment-gateway'), $body['id'])
                );
                return true;
            } else {
                $error_message = isset($body['message']) ? $body['message'] : __('Unknown PayPal refund error', 'environmental-payment-gateway');
                throw new Exception($error_message);
            }
            
        } catch (Exception $e) {
            return new WP_Error('paypal_refund_error', $e->getMessage());
        }
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        if (is_admin() || !is_checkout()) {
            return;
        }
        
        if ($this->get_option('enable_smart_buttons') === 'yes') {
            $client_id = $this->get_option('client_id');
            $currency = get_woocommerce_currency();
            
            wp_enqueue_script(
                'paypal-sdk',
                "https://www.paypal.com/sdk/js?client-id={$client_id}&currency={$currency}&components=buttons,marks",
                array(),
                null,
                true
            );
            
            wp_enqueue_script(
                'epg-paypal-enhanced',
                EPG_PLUGIN_URL . 'assets/js/paypal-enhanced.js',
                array('jquery', 'paypal-sdk'),
                EPG_PLUGIN_VERSION,
                true
            );
            
            wp_localize_script('epg-paypal-enhanced', 'epg_paypal_params', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('epg_paypal_nonce'),
                'button_color' => $this->get_option('button_color', 'blue'),
                'environment' => $this->get_option('environment', 'sandbox')
            ));
        }
    }
    
    /**
     * Get EDS processor configuration
     */
    public function get_eds_processor() {
        return array(
            'id' => $this->id,
            'name' => $this->method_title,
            'description' => __('Enhanced PayPal integration with environmental impact features', 'environmental-payment-gateway'),
            'supports_recurring' => $this->get_option('enable_subscriptions') === 'yes',
            'environmental_features' => array(
                'carbon_offset' => $this->get_option('carbon_offset_percentage', 2) > 0,
                'green_messaging' => $this->get_option('green_messaging') === 'yes'
            )
        );
    }
    
    /**
     * Verify webhook signature
     */
    private function verify_webhook_signature($raw_body, $headers) {
        $webhook_id = $this->get_option('webhook_id');
        if (empty($webhook_id)) {
            return true; // Skip verification if webhook ID not configured
        }
        
        // PayPal webhook verification logic would go here
        // This is a simplified version
        return true;
    }
    
    /**
     * Get order by PayPal order ID
     */
    private function get_order_by_paypal_id($paypal_order_id) {
        $orders = wc_get_orders(array(
            'meta_key' => '_paypal_order_id',
            'meta_value' => $paypal_order_id,
            'limit' => 1
        ));
        
        return !empty($orders) ? $orders[0] : false;
    }
    
    /**
     * Capture PayPal payment
     */
    private function capture_payment($order, $paypal_order_id) {
        $access_token = $this->get_access_token();
        if (!$access_token) {
            return false;
        }
        
        $response = wp_remote_post($this->api_base_url . '/v2/checkout/orders/' . $paypal_order_id . '/capture', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $access_token,
                'PayPal-Request-Id' => uniqid()
            ),
            'timeout' => 60
        ));
        
        if (!is_wp_error($response)) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (isset($body['status']) && $body['status'] === 'COMPLETED') {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if gateway is in sandbox mode
     */
    private function is_sandbox() {
        return $this->get_option('environment') === 'sandbox';
    }
    
    /**
     * Handle additional subscription events
     */
    private function handle_subscription_created($webhook_data) {
        // Implementation for subscription creation handling
        $this->log_info('PayPal subscription created: ' . $webhook_data['resource']['id']);
    }
    
    private function handle_subscription_cancelled($webhook_data) {
        // Implementation for subscription cancellation handling
        $this->log_info('PayPal subscription cancelled: ' . $webhook_data['resource']['id']);
    }
    
    private function handle_payment_denied($webhook_data) {
        // Implementation for payment denial handling
        $this->log_info('PayPal payment denied: ' . $webhook_data['resource']['id']);
    }
    
    private function handle_payment_refunded($webhook_data) {
        // Implementation for refund handling
        $this->log_info('PayPal payment refunded: ' . $webhook_data['resource']['id']);
    }
}
