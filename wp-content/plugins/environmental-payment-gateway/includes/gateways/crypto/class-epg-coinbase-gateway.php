<?php
/**
 * Coinbase Gateway
 * 
 * Coinbase Commerce integration for cryptocurrency payments with
 * multiple coin support and environmental impact tracking.
 * 
 * @package EnvironmentalPaymentGateway
 * @subpackage Gateways\Crypto
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * EPG Coinbase Gateway Class
 */
class EPG_Coinbase_Gateway extends EPG_Gateway_Base {
    
    /**
     * Gateway ID
     */
    public $id = 'coinbase';
    
    /**
     * Gateway method title
     */
    public $method_title = 'Coinbase Commerce';
    
    /**
     * Gateway method description
     */
    public $method_description = 'Accept cryptocurrency payments via Coinbase Commerce with environmental tracking';
    
    /**
     * Supported features
     */
    public $supports = array(
        'products',
        'refunds'
    );
    
    /**
     * Coinbase Commerce API base URL
     */
    private $api_base_url = 'https://api.commerce.coinbase.com';
    
    /**
     * Supported cryptocurrencies
     */
    private $supported_cryptocurrencies = array(
        'BTC' => array(
            'name' => 'Bitcoin',
            'symbol' => 'BTC',
            'carbon_intensity' => 'high', // Due to Proof of Work
            'energy_per_tx' => 700 // kWh per transaction
        ),
        'ETH' => array(
            'name' => 'Ethereum',
            'symbol' => 'ETH',
            'carbon_intensity' => 'low', // Post-merge Proof of Stake
            'energy_per_tx' => 0.0026
        ),
        'LTC' => array(
            'name' => 'Litecoin',
            'symbol' => 'LTC',
            'carbon_intensity' => 'medium',
            'energy_per_tx' => 18
        ),
        'BCH' => array(
            'name' => 'Bitcoin Cash',
            'symbol' => 'BCH',
            'carbon_intensity' => 'high',
            'energy_per_tx' => 80
        ),
        'USDC' => array(
            'name' => 'USD Coin',
            'symbol' => 'USDC',
            'carbon_intensity' => 'low',
            'energy_per_tx' => 0.0026 // Ethereum-based
        ),
        'DAI' => array(
            'name' => 'Dai',
            'symbol' => 'DAI',
            'carbon_intensity' => 'low',
            'energy_per_tx' => 0.0026 // Ethereum-based
        )
    );
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        $this->title = __('Coinbase Commerce', 'environmental-payment-gateway');
        $this->description = __('Pay with cryptocurrency via Coinbase Commerce. Environmental impact tracked and offset.', 'environmental-payment-gateway');
        $this->icon = EPG_PLUGIN_URL . 'assets/images/coinbase-logo.png';
        
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
        
        // Coinbase-specific hooks
        add_action('epg_coinbase_payment_monitor', array($this, 'monitor_payments'));
        
        // Schedule payment monitoring
        if (!wp_next_scheduled('epg_coinbase_payment_monitor')) {
            wp_schedule_event(time(), 'every_minute', 'epg_coinbase_payment_monitor');
        }
    }
    
    /**
     * Initialize gateway settings form fields
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Enable Coinbase Commerce Gateway', 'environmental-payment-gateway'),
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'environmental-payment-gateway'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'environmental-payment-gateway'),
                'default' => __('Coinbase Commerce', 'environmental-payment-gateway'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'environmental-payment-gateway'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'environmental-payment-gateway'),
                'default' => __('Pay with Bitcoin, Ethereum, and other cryptocurrencies. Carbon footprint automatically tracked and offset.', 'environmental-payment-gateway'),
            ),
            'api_settings' => array(
                'title' => __('API Settings', 'environmental-payment-gateway'),
                'type' => 'title',
                'description' => '',
            ),
            'api_key' => array(
                'title' => __('API Key', 'environmental-payment-gateway'),
                'type' => 'password',
                'description' => __('Enter your Coinbase Commerce API Key.', 'environmental-payment-gateway'),
                'default' => '',
                'desc_tip' => true,
            ),
            'webhook_secret' => array(
                'title' => __('Webhook Secret', 'environmental-payment-gateway'),
                'type' => 'password',
                'description' => __('Enter your Coinbase Commerce Webhook Secret.', 'environmental-payment-gateway'),
                'default' => '',
                'desc_tip' => true,
            ),
            'payment_settings' => array(
                'title' => __('Payment Settings', 'environmental-payment-gateway'),
                'type' => 'title',
                'description' => '',
            ),
            'accepted_currencies' => array(
                'title' => __('Accepted Cryptocurrencies', 'environmental-payment-gateway'),
                'type' => 'multiselect',
                'description' => __('Select which cryptocurrencies to accept.', 'environmental-payment-gateway'),
                'default' => array('BTC', 'ETH', 'USDC'),
                'options' => array(
                    'BTC' => 'Bitcoin (BTC)',
                    'ETH' => 'Ethereum (ETH)',
                    'LTC' => 'Litecoin (LTC)',
                    'BCH' => 'Bitcoin Cash (BCH)',
                    'USDC' => 'USD Coin (USDC)',
                    'DAI' => 'Dai (DAI)'
                )
            ),
            'payment_timeout' => array(
                'title' => __('Payment Timeout (minutes)', 'environmental-payment-gateway'),
                'type' => 'number',
                'description' => __('Time limit for customers to complete payment.', 'environmental-payment-gateway'),
                'default' => '60',
                'custom_attributes' => array(
                    'min' => '15',
                    'max' => '180'
                )
            ),
            'redirect_after_payment' => array(
                'title' => __('Redirect After Payment', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Redirect customers back to store after payment', 'environmental-payment-gateway'),
                'default' => 'yes'
            ),
            'environmental_settings' => array(
                'title' => __('Environmental Settings', 'environmental-payment-gateway'),
                'type' => 'title',
                'description' => '',
            ),
            'carbon_tracking' => array(
                'title' => __('Carbon Tracking', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Track carbon footprint of cryptocurrency payments', 'environmental-payment-gateway'),
                'default' => 'yes'
            ),
            'carbon_offset_enabled' => array(
                'title' => __('Carbon Offset', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Automatically offset payment carbon footprint', 'environmental-payment-gateway'),
                'default' => 'yes'
            ),
            'show_environmental_info' => array(
                'title' => __('Show Environmental Info', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Display environmental impact information for each cryptocurrency', 'environmental-payment-gateway'),
                'default' => 'yes'
            ),
            'prefer_green_crypto' => array(
                'title' => __('Prefer Green Cryptocurrencies', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Highlight environmentally friendly cryptocurrencies (ETH, USDC)', 'environmental-payment-gateway'),
                'default' => 'yes'
            ),
            'advanced_settings' => array(
                'title' => __('Advanced Settings', 'environmental-payment-gateway'),
                'type' => 'title',
                'description' => '',
            ),
            'enable_underpayment_detection' => array(
                'title' => __('Underpayment Detection', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Detect and handle underpayments automatically', 'environmental-payment-gateway'),
                'default' => 'yes'
            ),
            'overpayment_threshold' => array(
                'title' => __('Overpayment Threshold (%)', 'environmental-payment-gateway'),
                'type' => 'number',
                'description' => __('Percentage overpayment before considering it significant.', 'environmental-payment-gateway'),
                'default' => '5',
                'custom_attributes' => array(
                    'min' => '1',
                    'max' => '20',
                    'step' => '0.1'
                )
            ),
            'enable_partial_payments' => array(
                'title' => __('Partial Payments', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Allow partial payments (useful for high-value orders)', 'environmental-payment-gateway'),
                'default' => 'no'
            )
        );
    }
    
    /**
     * Process payment
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        
        try {
            // Create Coinbase Commerce charge
            $charge = $this->create_charge($order);
            
            if (!$charge || !isset($charge['data']['id'])) {
                throw new Exception(__('Unable to create Coinbase Commerce charge', 'environmental-payment-gateway'));
            }
            
            // Store charge details
            $order->update_meta_data('_coinbase_charge_id', $charge['data']['id']);
            $order->update_meta_data('_coinbase_charge_code', $charge['data']['code']);
            $order->update_meta_data('_coinbase_hosted_url', $charge['data']['hosted_url']);
            $order->save();
            
            // Set order status to pending payment
            $order->update_status('pending', __('Awaiting Coinbase Commerce payment', 'environmental-payment-gateway'));
            
            // Calculate environmental impact for accepted currencies
            $this->calculate_environmental_impact($order, $charge['data']['pricing']);
            
            // Redirect to Coinbase Commerce payment page
            return array(
                'result' => 'success',
                'redirect' => $charge['data']['hosted_url']
            );
            
        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            $this->log_error('Coinbase Commerce payment processing failed: ' . $e->getMessage());
            return array('result' => 'fail');
        }
    }
    
    /**
     * Create Coinbase Commerce charge
     */
    private function create_charge($order) {
        $api_key = $this->get_option('api_key');
        if (empty($api_key)) {
            throw new Exception(__('Coinbase Commerce API key not configured', 'environmental-payment-gateway'));
        }
        
        $accepted_currencies = $this->get_option('accepted_currencies', array('BTC', 'ETH', 'USDC'));
        
        $charge_data = array(
            'name' => sprintf(__('Order #%s', 'environmental-payment-gateway'), $order->get_order_number()),
            'description' => sprintf(
                __('Payment for Order #%s from %s', 'environmental-payment-gateway'),
                $order->get_order_number(),
                get_bloginfo('name')
            ),
            'local_price' => array(
                'amount' => number_format($order->get_total(), 2, '.', ''),
                'currency' => $order->get_currency()
            ),
            'pricing_type' => 'fixed_price',
            'metadata' => array(
                'order_id' => $order->get_id(),
                'customer_id' => $order->get_customer_id(),
                'customer_email' => $order->get_billing_email()
            ),
            'redirect_url' => $this->get_return_url($order),
            'cancel_url' => $order->get_cancel_order_url_raw()
        );
        
        // Add accepted currencies if specified
        if (!empty($accepted_currencies)) {
            $charge_data['requested_info'] = array('email');
        }
        
        // Add environmental messaging
        if ($this->get_option('show_environmental_info') === 'yes') {
            $charge_data['description'] .= __(' | Environmental impact will be tracked and offset automatically.', 'environmental-payment-gateway');
        }
        
        $response = wp_remote_post($this->api_base_url . '/charges', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-CC-Api-Key' => $api_key,
                'X-CC-Version' => '2018-03-22'
            ),
            'body' => json_encode($charge_data),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 201) {
            $error_message = isset($body['error']['message']) ? $body['error']['message'] : __('Unknown Coinbase Commerce error', 'environmental-payment-gateway');
            throw new Exception($error_message);
        }
        
        return $body;
    }
    
    /**
     * Calculate environmental impact
     */
    private function calculate_environmental_impact($order, $pricing) {
        if ($this->get_option('carbon_tracking') !== 'yes') {
            return;
        }
        
        $environmental_data = array();
        $total_carbon_footprint = 0;
        
        foreach ($pricing as $currency => $price_data) {
            if (isset($this->supported_cryptocurrencies[$currency])) {
                $crypto_data = $this->supported_cryptocurrencies[$currency];
                $energy_consumption = $crypto_data['energy_per_tx'];
                
                // Calculate carbon footprint (using global average of 0.5 kg CO2 per kWh)
                $carbon_footprint = $energy_consumption * 0.5;
                
                $environmental_data[$currency] = array(
                    'energy_kwh' => $energy_consumption,
                    'carbon_kg' => $carbon_footprint,
                    'carbon_intensity' => $crypto_data['carbon_intensity']
                );
                
                $total_carbon_footprint += $carbon_footprint;
            }
        }
        
        // Store environmental impact data
        $order->update_meta_data('_coinbase_environmental_impact', $environmental_data);
        $order->update_meta_data('_coinbase_total_carbon_footprint', $total_carbon_footprint);
        $order->save();
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
        
        if (!$webhook_data || !isset($webhook_data['event'])) {
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
        $event_type = $webhook_data['event']['type'];
        $charge_data = $webhook_data['event']['data'];
        
        // Find order by charge ID
        $order = $this->get_order_by_charge_id($charge_data['id']);
        if (!$order) {
            $this->log_error('Order not found for Coinbase charge: ' . $charge_data['id']);
            return;
        }
        
        switch ($event_type) {
            case 'charge:created':
                $this->handle_charge_created($order, $charge_data);
                break;
                
            case 'charge:confirmed':
                $this->handle_charge_confirmed($order, $charge_data);
                break;
                
            case 'charge:failed':
                $this->handle_charge_failed($order, $charge_data);
                break;
                
            case 'charge:delayed':
                $this->handle_charge_delayed($order, $charge_data);
                break;
                
            case 'charge:pending':
                $this->handle_charge_pending($order, $charge_data);
                break;
                
            case 'charge:resolved':
                $this->handle_charge_resolved($order, $charge_data);
                break;
                
            default:
                $this->log_info('Unhandled Coinbase webhook event: ' . $event_type);
        }
    }
    
    /**
     * Handle charge created event
     */
    private function handle_charge_created($order, $charge_data) {
        $order->add_order_note(__('Coinbase Commerce charge created', 'environmental-payment-gateway'));
    }
    
    /**
     * Handle charge confirmed event
     */
    private function handle_charge_confirmed($order, $charge_data) {
        $order->payment_complete($charge_data['id']);
        
        // Get payment details
        $payments = $charge_data['payments'] ?? array();
        foreach ($payments as $payment) {
            if ($payment['status'] === 'CONFIRMED') {
                $currency = $payment['network'];
                $amount = $payment['value']['crypto']['amount'];
                
                $order->add_order_note(
                    sprintf(__('Coinbase Commerce payment confirmed: %s %s (Transaction: %s)', 'environmental-payment-gateway'), 
                        $amount, 
                        $currency,
                        $payment['transaction_id']
                    )
                );
                
                // Process carbon offset for the specific cryptocurrency used
                $this->process_carbon_offset($order, $currency);
                break;
            }
        }
    }
    
    /**
     * Handle charge failed event
     */
    private function handle_charge_failed($order, $charge_data) {
        $order->update_status('failed', __('Coinbase Commerce payment failed', 'environmental-payment-gateway'));
    }
    
    /**
     * Handle charge delayed event
     */
    private function handle_charge_delayed($order, $charge_data) {
        $order->add_order_note(__('Coinbase Commerce payment delayed - under review', 'environmental-payment-gateway'));
    }
    
    /**
     * Handle charge pending event
     */
    private function handle_charge_pending($order, $charge_data) {
        $order->add_order_note(__('Coinbase Commerce payment detected - awaiting confirmations', 'environmental-payment-gateway'));
    }
    
    /**
     * Handle charge resolved event
     */
    private function handle_charge_resolved($order, $charge_data) {
        $resolution = $charge_data['timeline'][count($charge_data['timeline']) - 1];
        
        if ($resolution['status'] === 'COMPLETED') {
            $order->payment_complete($charge_data['id']);
            $order->add_order_note(__('Coinbase Commerce payment resolved and completed', 'environmental-payment-gateway'));
        } else {
            $order->add_order_note(
                sprintf(__('Coinbase Commerce payment resolved: %s', 'environmental-payment-gateway'), $resolution['status'])
            );
        }
    }
    
    /**
     * Process carbon offset
     */
    private function process_carbon_offset($order, $currency) {
        if ($this->get_option('carbon_offset_enabled') !== 'yes') {
            return;
        }
        
        $environmental_data = $order->get_meta('_coinbase_environmental_impact');
        
        if (isset($environmental_data[$currency])) {
            $carbon_footprint = $environmental_data[$currency]['carbon_kg'];
            
            // Calculate offset cost ($20 per ton of CO2)
            $offset_cost_per_kg = 0.02;
            $offset_amount = $carbon_footprint * $offset_cost_per_kg;
            
            // Store offset data
            $order->update_meta_data('_coinbase_carbon_offset_amount', $offset_amount);
            $order->update_meta_data('_coinbase_carbon_offset_currency', $currency);
            $order->update_meta_data('_coinbase_carbon_offset_processed', 'yes');
            $order->save();
            
            // Trigger carbon offset action
            do_action('epg_carbon_offset_donation', $order->get_id(), $offset_amount);
            
            $order->add_order_note(
                sprintf(__('Carbon offset applied for %s payment: %.3f kg CO2, Cost: %s %s', 'environmental-payment-gateway'), 
                    $currency,
                    $carbon_footprint,
                    $offset_amount,
                    $order->get_currency()
                )
            );
        }
    }
    
    /**
     * Process refund
     */
    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);
        $charge_id = $order->get_meta('_coinbase_charge_id');
        
        if (!$charge_id) {
            return new WP_Error('coinbase_refund_error', __('Charge ID not found', 'environmental-payment-gateway'));
        }
        
        // Note: Coinbase Commerce doesn't support automatic refunds
        // This would typically require manual processing
        $order->add_order_note(
            sprintf(__('Refund requested: %s %s. Manual processing required via Coinbase Commerce dashboard.', 'environmental-payment-gateway'), 
                $amount ? $amount : $order->get_total(),
                $order->get_currency()
            )
        );
        
        return true;
    }
    
    /**
     * Monitor payments
     */
    public function monitor_payments() {
        $pending_orders = wc_get_orders(array(
            'status' => 'pending',
            'payment_method' => $this->id,
            'meta_query' => array(
                array(
                    'key' => '_coinbase_charge_id',
                    'compare' => 'EXISTS'
                )
            )
        ));
        
        foreach ($pending_orders as $order) {
            $this->check_charge_status($order);
        }
    }
    
    /**
     * Check charge status
     */
    private function check_charge_status($order) {
        $charge_id = $order->get_meta('_coinbase_charge_id');
        if (!$charge_id) {
            return;
        }
        
        $api_key = $this->get_option('api_key');
        $response = wp_remote_get($this->api_base_url . '/charges/' . $charge_id, array(
            'headers' => array(
                'X-CC-Api-Key' => $api_key,
                'X-CC-Version' => '2018-03-22'
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['data'])) {
            $charge_data = $body['data'];
            
            // Check if status has changed
            $current_status = $order->get_meta('_coinbase_charge_status');
            $new_status = $charge_data['timeline'][count($charge_data['timeline']) - 1]['status'];
            
            if ($current_status !== $new_status) {
                $order->update_meta_data('_coinbase_charge_status', $new_status);
                $order->save();
                
                // Process status change
                $webhook_event = array(
                    'event' => array(
                        'type' => 'charge:' . strtolower($new_status),
                        'data' => $charge_data
                    )
                );
                
                $this->process_webhook_event($webhook_event);
            }
        }
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        if (is_admin() || !is_checkout()) {
            return;
        }
        
        wp_enqueue_script(
            'epg-coinbase',
            EPG_PLUGIN_URL . 'assets/js/coinbase.js',
            array('jquery'),
            EPG_PLUGIN_VERSION,
            true
        );
        
        wp_localize_script('epg-coinbase', 'epg_coinbase_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('epg_coinbase_nonce'),
            'accepted_currencies' => $this->get_option('accepted_currencies', array('BTC', 'ETH', 'USDC')),
            'show_environmental_info' => $this->get_option('show_environmental_info') === 'yes',
            'prefer_green_crypto' => $this->get_option('prefer_green_crypto') === 'yes',
            'environmental_data' => $this->supported_cryptocurrencies
        ));
    }
    
    /**
     * Verify webhook signature
     */
    private function verify_webhook_signature($raw_body, $headers) {
        $webhook_secret = $this->get_option('webhook_secret');
        if (empty($webhook_secret)) {
            return true; // Skip verification if secret not configured
        }
        
        $signature = isset($headers['X-CC-Webhook-Signature']) ? $headers['X-CC-Webhook-Signature'] : '';
        $expected_signature = hash_hmac('sha256', $raw_body, $webhook_secret);
        
        return hash_equals($expected_signature, $signature);
    }
    
    /**
     * Get order by charge ID
     */
    private function get_order_by_charge_id($charge_id) {
        $orders = wc_get_orders(array(
            'meta_key' => '_coinbase_charge_id',
            'meta_value' => $charge_id,
            'limit' => 1
        ));
        
        return !empty($orders) ? $orders[0] : false;
    }
    
    /**
     * Get EDS processor configuration
     */
    public function get_eds_processor() {
        return array(
            'id' => $this->id,
            'name' => $this->method_title,
            'description' => __('Multi-cryptocurrency payments via Coinbase Commerce with environmental tracking', 'environmental-payment-gateway'),
            'environmental_features' => array(
                'carbon_tracking' => $this->get_option('carbon_tracking') === 'yes',
                'carbon_offset' => $this->get_option('carbon_offset_enabled') === 'yes',
                'environmental_education' => $this->get_option('show_environmental_info') === 'yes',
                'green_crypto_preference' => $this->get_option('prefer_green_crypto') === 'yes'
            ),
            'supported_cryptocurrencies' => $this->get_option('accepted_currencies', array('BTC', 'ETH', 'USDC'))
        );
    }
}
