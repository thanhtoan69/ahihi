<?php
/**
 * Wise Gateway (formerly TransferWise)
 * 
 * International money transfer gateway with multi-currency support,
 * real exchange rates, and low-cost international payments.
 * 
 * @package EnvironmentalPaymentGateway
 * @subpackage Gateways\International
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * EPG Wise Gateway Class
 */
class EPG_Wise_Gateway extends EPG_Gateway_Base {
    
    /**
     * Gateway ID
     */
    public $id = 'wise';
    
    /**
     * Gateway method title
     */
    public $method_title = 'Wise';
    
    /**
     * Gateway method description
     */
    public $method_description = 'Accept international payments with real exchange rates via Wise (formerly TransferWise)';
    
    /**
     * Supported features
     */
    public $supports = array(
        'products',
        'refunds'
    );
    
    /**
     * Wise API base URL
     */
    private $api_base_url;
    
    /**
     * Supported currencies
     */
    private $supported_currencies = array(
        'USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'CHF', 'SGD', 'HKD', 'NZD',
        'SEK', 'NOK', 'DKK', 'PLN', 'CZK', 'HUF', 'RON', 'BGN', 'HRK', 'TRY',
        'ZAR', 'BRL', 'MXN', 'INR', 'CNY', 'KRW', 'THB', 'MYR', 'PHP', 'IDR',
        'VND', 'AED', 'SAR', 'EGP', 'NGN', 'KES', 'GHS', 'XOF', 'XAF', 'MAD'
    );
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        $this->title = __('Wise', 'environmental-payment-gateway');
        $this->description = __('Pay internationally with real exchange rates and low fees via Wise.', 'environmental-payment-gateway');
        $this->icon = EPG_PLUGIN_URL . 'assets/images/wise-logo.png';
        
        // Set API URL based on environment
        $this->api_base_url = $this->is_sandbox() ? 
            'https://api.sandbox.transferwise.tech' : 
            'https://api.wise.com';
        
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
        
        // Currency conversion hooks
        add_filter('woocommerce_currency_symbol', array($this, 'maybe_override_currency_symbol'), 10, 2);
        add_action('woocommerce_checkout_process', array($this, 'validate_checkout_currency'));
    }
    
    /**
     * Initialize gateway settings form fields
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Enable Wise Gateway', 'environmental-payment-gateway'),
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'environmental-payment-gateway'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'environmental-payment-gateway'),
                'default' => __('Wise', 'environmental-payment-gateway'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'environmental-payment-gateway'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'environmental-payment-gateway'),
                'default' => __('Pay internationally with real exchange rates and transparent fees.', 'environmental-payment-gateway'),
            ),
            'environment' => array(
                'title' => __('Environment', 'environmental-payment-gateway'),
                'type' => 'select',
                'description' => __('Select the Wise environment.', 'environmental-payment-gateway'),
                'default' => 'sandbox',
                'desc_tip' => true,
                'options' => array(
                    'sandbox' => __('Sandbox', 'environmental-payment-gateway'),
                    'live' => __('Live', 'environmental-payment-gateway'),
                )
            ),
            'api_token' => array(
                'title' => __('API Token', 'environmental-payment-gateway'),
                'type' => 'password',
                'description' => __('Enter your Wise API Token.', 'environmental-payment-gateway'),
                'default' => '',
                'desc_tip' => true,
            ),
            'profile_id' => array(
                'title' => __('Profile ID', 'environmental-payment-gateway'),
                'type' => 'text',
                'description' => __('Enter your Wise Profile ID (Business or Personal).', 'environmental-payment-gateway'),
                'default' => '',
                'desc_tip' => true,
            ),
            'webhook_secret' => array(
                'title' => __('Webhook Secret', 'environmental-payment-gateway'),
                'type' => 'password',
                'description' => __('Enter your Wise Webhook Secret for secure notifications.', 'environmental-payment-gateway'),
                'default' => '',
                'desc_tip' => true,
            ),
            'currency_settings' => array(
                'title' => __('Currency Settings', 'environmental-payment-gateway'),
                'type' => 'title',
                'description' => '',
            ),
            'base_currency' => array(
                'title' => __('Base Currency', 'environmental-payment-gateway'),
                'type' => 'select',
                'description' => __('Select your base currency for Wise account.', 'environmental-payment-gateway'),
                'default' => 'USD',
                'options' => $this->get_currency_options()
            ),
            'auto_currency_conversion' => array(
                'title' => __('Auto Currency Conversion', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Automatically convert payments to base currency', 'environmental-payment-gateway'),
                'default' => 'yes'
            ),
            'show_exchange_rate' => array(
                'title' => __('Show Exchange Rate', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Display current exchange rate to customers', 'environmental-payment-gateway'),
                'default' => 'yes'
            ),
            'fee_settings' => array(
                'title' => __('Fee Settings', 'environmental-payment-gateway'),
                'type' => 'title',
                'description' => '',
            ),
            'fee_mode' => array(
                'title' => __('Fee Mode', 'environmental-payment-gateway'),
                'type' => 'select',
                'description' => __('How to handle Wise fees.', 'environmental-payment-gateway'),
                'default' => 'shared',
                'options' => array(
                    'shared' => __('Customer pays fee (transparent)', 'environmental-payment-gateway'),
                    'absorbed' => __('Merchant absorbs fee', 'environmental-payment-gateway'),
                    'markup' => __('Add markup percentage', 'environmental-payment-gateway')
                )
            ),
            'markup_percentage' => array(
                'title' => __('Markup Percentage', 'environmental-payment-gateway'),
                'type' => 'number',
                'description' => __('Additional markup percentage (only if fee mode is markup).', 'environmental-payment-gateway'),
                'default' => '2.5',
                'custom_attributes' => array(
                    'min' => '0',
                    'max' => '10',
                    'step' => '0.1'
                )
            ),
            'environmental_settings' => array(
                'title' => __('Environmental Integration', 'environmental-payment-gateway'),
                'type' => 'title',
                'description' => '',
            ),
            'carbon_neutral_transfers' => array(
                'title' => __('Carbon Neutral Transfers', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Offset carbon footprint of international transfers', 'environmental-payment-gateway'),
                'default' => 'yes',
                'description' => __('Small fee added to support carbon offset programs.', 'environmental-payment-gateway')
            ),
            'green_routing' => array(
                'title' => __('Green Routing', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Prefer environmentally friendly routing when possible', 'environmental-payment-gateway'),
                'default' => 'yes'
            )
        );
    }
    
    /**
     * Get currency options for select field
     */
    private function get_currency_options() {
        $options = array();
        foreach ($this->supported_currencies as $currency) {
            $options[$currency] = $currency;
        }
        return $options;
    }
    
    /**
     * Process payment
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        
        try {
            // Validate currency support
            if (!in_array($order->get_currency(), $this->supported_currencies)) {
                throw new Exception(sprintf(
                    __('Currency %s is not supported by Wise', 'environmental-payment-gateway'),
                    $order->get_currency()
                ));
            }
            
            // Get quote for the transfer
            $quote = $this->create_quote($order);
            if (!$quote) {
                throw new Exception(__('Unable to get Wise quote', 'environmental-payment-gateway'));
            }
            
            // Create recipient account
            $recipient = $this->create_recipient($order, $quote);
            if (!$recipient) {
                throw new Exception(__('Unable to create recipient account', 'environmental-payment-gateway'));
            }
            
            // Create transfer
            $transfer = $this->create_transfer($order, $quote, $recipient);
            if (!$transfer) {
                throw new Exception(__('Unable to create Wise transfer', 'environmental-payment-gateway'));
            }
            
            // Store transfer details
            $order->update_meta_data('_wise_quote_id', $quote['id']);
            $order->update_meta_data('_wise_transfer_id', $transfer['id']);
            $order->update_meta_data('_wise_recipient_id', $recipient['id']);
            $order->save();
            
            // Get payment URL
            $payment_url = $this->get_payment_url($transfer);
            
            if ($payment_url) {
                return array(
                    'result' => 'success',
                    'redirect' => $payment_url
                );
            } else {
                // For some transfers, payment might be immediate
                $order->payment_complete($transfer['id']);
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order)
                );
            }
            
        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            $this->log_error('Payment processing failed: ' . $e->getMessage());
            return array('result' => 'fail');
        }
    }
    
    /**
     * Create quote for transfer
     */
    private function create_quote($order) {
        $base_currency = $this->get_option('base_currency', 'USD');
        $source_currency = $base_currency;
        $target_currency = $order->get_currency();
        $amount = $order->get_total();
        
        // If currencies are the same, no conversion needed
        if ($source_currency === $target_currency) {
            return array(
                'id' => 'direct_' . uniqid(),
                'source_currency' => $source_currency,
                'target_currency' => $target_currency,
                'source_amount' => $amount,
                'target_amount' => $amount,
                'rate' => 1.0,
                'fee' => 0
            );
        }
        
        $quote_data = array(
            'sourceCurrency' => $source_currency,
            'targetCurrency' => $target_currency,
            'sourceAmount' => floatval($amount),
            'profile' => intval($this->get_option('profile_id'))
        );
        
        // Add environmental routing preferences
        if ($this->get_option('green_routing') === 'yes') {
            $quote_data['preferredRouting'] = 'GREEN';
        }
        
        $response = $this->make_api_request('POST', '/v2/quotes', $quote_data);
        
        if ($response && isset($response['id'])) {
            return $response;
        }
        
        return false;
    }
    
    /**
     * Create recipient account
     */
    private function create_recipient($order, $quote) {
        $billing_email = $order->get_billing_email();
        $billing_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        
        $recipient_data = array(
            'profile' => intval($this->get_option('profile_id')),
            'accountHolderName' => $billing_name,
            'currency' => $quote['targetCurrency'],
            'type' => 'email',
            'details' => array(
                'email' => $billing_email
            )
        );
        
        $response = $this->make_api_request('POST', '/v1/accounts', $recipient_data);
        
        if ($response && isset($response['id'])) {
            return $response;
        }
        
        return false;
    }
    
    /**
     * Create transfer
     */
    private function create_transfer($order, $quote, $recipient) {
        $transfer_data = array(
            'targetAccount' => $recipient['id'],
            'quoteUuid' => $quote['id'],
            'customerTransactionId' => $order->get_order_number(),
            'details' => array(
                'reference' => sprintf(
                    __('Payment for Order #%s from %s', 'environmental-payment-gateway'),
                    $order->get_order_number(),
                    get_bloginfo('name')
                ),
                'transferPurpose' => 'VERIFICATION_OF_DEPOSIT',
                'sourceOfFunds' => 'VERIFICATION_OF_DEPOSIT'
            )
        );
        
        // Add carbon offset if enabled
        if ($this->get_option('carbon_neutral_transfers') === 'yes') {
            $transfer_data['details']['carbonOffset'] = true;
            $transfer_data['details']['environmentalImpact'] = 'CARBON_NEUTRAL';
        }
        
        $response = $this->make_api_request('POST', '/v1/transfers', $transfer_data);
        
        if ($response && isset($response['id'])) {
            return $response;
        }
        
        return false;
    }
    
    /**
     * Get payment URL for transfer
     */
    private function get_payment_url($transfer) {
        if (isset($transfer['status']) && $transfer['status'] === 'incoming_payment_waiting') {
            // Return a URL to complete payment
            return add_query_arg(array(
                'wise_transfer_id' => $transfer['id'],
                'action' => 'complete_payment'
            ), wc_get_checkout_url());
        }
        
        return false;
    }
    
    /**
     * Make API request to Wise
     */
    private function make_api_request($method, $endpoint, $data = null) {
        $api_token = $this->get_option('api_token');
        if (empty($api_token)) {
            return false;
        }
        
        $url = $this->api_base_url . $endpoint;
        $headers = array(
            'Authorization' => 'Bearer ' . $api_token,
            'Content-Type' => 'application/json',
            'User-Agent' => 'WooCommerce Environmental Payment Gateway'
        );
        
        $args = array(
            'headers' => $headers,
            'timeout' => 60,
            'method' => $method
        );
        
        if ($data && ($method === 'POST' || $method === 'PUT')) {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            $this->log_error('Wise API Error: ' . $response->get_error_message());
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($status_code >= 200 && $status_code < 300) {
            return $body;
        } else {
            $error_message = isset($body['message']) ? $body['message'] : 'Unknown Wise API error';
            $this->log_error('Wise API Error: ' . $error_message);
            return false;
        }
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
            case 'transfers#state-change':
                $this->handle_transfer_state_change($webhook_data);
                break;
                
            case 'transfers#active-cases':
                $this->handle_transfer_active_cases($webhook_data);
                break;
                
            default:
                $this->log_info('Unhandled Wise webhook event: ' . $event_type);
        }
    }
    
    /**
     * Handle transfer state change
     */
    private function handle_transfer_state_change($webhook_data) {
        $transfer_id = $webhook_data['data']['resource']['id'];
        $current_state = $webhook_data['data']['current_state'];
        
        $order = $this->get_order_by_transfer_id($transfer_id);
        
        if (!$order) {
            return;
        }
        
        switch ($current_state) {
            case 'outgoing_payment_sent':
                $order->payment_complete($transfer_id);
                $order->add_order_note(__('Wise transfer completed successfully', 'environmental-payment-gateway'));
                
                // Process carbon offset if enabled
                if ($this->get_option('carbon_neutral_transfers') === 'yes') {
                    $this->process_carbon_offset($order);
                }
                break;
                
            case 'cancelled':
                $order->update_status('cancelled', __('Wise transfer was cancelled', 'environmental-payment-gateway'));
                break;
                
            case 'funds_refunded':
                $order->update_status('refunded', __('Wise transfer was refunded', 'environmental-payment-gateway'));
                break;
                
            default:
                $order->add_order_note(
                    sprintf(__('Wise transfer status updated: %s', 'environmental-payment-gateway'), $current_state)
                );
        }
    }
    
    /**
     * Process carbon offset for transfer
     */
    private function process_carbon_offset($order) {
        $transfer_amount = $order->get_total();
        
        // Calculate carbon offset based on transfer amount and distance
        // This is a simplified calculation - in reality would use more complex factors
        $carbon_offset_fee = $transfer_amount * 0.001; // 0.1%
        
        // Record carbon offset
        $order->update_meta_data('_wise_carbon_offset_fee', $carbon_offset_fee);
        $order->update_meta_data('_wise_carbon_neutral', 'yes');
        $order->save();
        
        // Trigger carbon offset action
        do_action('epg_carbon_offset_donation', $order->get_id(), $carbon_offset_fee);
        
        $order->add_order_note(
            sprintf(__('Carbon offset applied: %s %s', 'environmental-payment-gateway'), 
                $carbon_offset_fee, 
                $order->get_currency()
            )
        );
    }
    
    /**
     * Process refund
     */
    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);
        $transfer_id = $order->get_meta('_wise_transfer_id');
        
        if (!$transfer_id) {
            return new WP_Error('wise_refund_error', __('Transfer ID not found', 'environmental-payment-gateway'));
        }
        
        try {
            $refund_data = array(
                'amount' => floatval($amount ? $amount : $order->get_total()),
                'currency' => $order->get_currency(),
                'reason' => $reason ? $reason : __('Refund requested', 'environmental-payment-gateway')
            );
            
            $response = $this->make_api_request('POST', "/v1/transfers/{$transfer_id}/refund", $refund_data);
            
            if ($response && isset($response['id'])) {
                $order->add_order_note(
                    sprintf(__('Wise refund initiated. Refund ID: %s', 'environmental-payment-gateway'), $response['id'])
                );
                return true;
            } else {
                throw new Exception(__('Unable to process Wise refund', 'environmental-payment-gateway'));
            }
            
        } catch (Exception $e) {
            return new WP_Error('wise_refund_error', $e->getMessage());
        }
    }
    
    /**
     * Get current exchange rate
     */
    public function get_exchange_rate($from_currency, $to_currency) {
        if ($from_currency === $to_currency) {
            return 1.0;
        }
        
        $response = $this->make_api_request('GET', "/v1/rates?source={$from_currency}&target={$to_currency}");
        
        if ($response && isset($response[0]['rate'])) {
            return floatval($response[0]['rate']);
        }
        
        return false;
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        if (is_admin() || !is_checkout()) {
            return;
        }
        
        wp_enqueue_script(
            'epg-wise',
            EPG_PLUGIN_URL . 'assets/js/wise.js',
            array('jquery'),
            EPG_PLUGIN_VERSION,
            true
        );
        
        // Add exchange rate data if enabled
        if ($this->get_option('show_exchange_rate') === 'yes') {
            $base_currency = $this->get_option('base_currency', 'USD');
            $shop_currency = get_woocommerce_currency();
            
            if ($base_currency !== $shop_currency) {
                $rate = $this->get_exchange_rate($base_currency, $shop_currency);
                
                wp_localize_script('epg-wise', 'epg_wise_params', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('epg_wise_nonce'),
                    'exchange_rate' => $rate,
                    'base_currency' => $base_currency,
                    'shop_currency' => $shop_currency,
                    'show_rate' => true
                ));
            }
        }
    }
    
    /**
     * Verify webhook signature
     */
    private function verify_webhook_signature($raw_body, $headers) {
        $webhook_secret = $this->get_option('webhook_secret');
        if (empty($webhook_secret)) {
            return true; // Skip verification if secret not configured
        }
        
        $signature = isset($headers['X-Signature-SHA256']) ? $headers['X-Signature-SHA256'] : '';
        $expected_signature = hash_hmac('sha256', $raw_body, $webhook_secret);
        
        return hash_equals($expected_signature, $signature);
    }
    
    /**
     * Get order by Wise transfer ID
     */
    private function get_order_by_transfer_id($transfer_id) {
        $orders = wc_get_orders(array(
            'meta_key' => '_wise_transfer_id',
            'meta_value' => $transfer_id,
            'limit' => 1
        ));
        
        return !empty($orders) ? $orders[0] : false;
    }
    
    /**
     * Check if gateway is in sandbox mode
     */
    private function is_sandbox() {
        return $this->get_option('environment') === 'sandbox';
    }
    
    /**
     * Get EDS processor configuration
     */
    public function get_eds_processor() {
        return array(
            'id' => $this->id,
            'name' => $this->method_title,
            'description' => __('International money transfers with environmental impact features', 'environmental-payment-gateway'),
            'supports_currencies' => $this->supported_currencies,
            'environmental_features' => array(
                'carbon_neutral_transfers' => $this->get_option('carbon_neutral_transfers') === 'yes',
                'green_routing' => $this->get_option('green_routing') === 'yes'
            )
        );
    }
    
    /**
     * Validate checkout currency
     */
    public function validate_checkout_currency() {
        if ($this->enabled !== 'yes') {
            return;
        }
        
        $chosen_payment_method = WC()->session->get('chosen_payment_method');
        if ($chosen_payment_method === $this->id) {
            $currency = get_woocommerce_currency();
            if (!in_array($currency, $this->supported_currencies)) {
                wc_add_notice(
                    sprintf(__('Wise does not support %s currency. Please contact us for alternative payment methods.', 'environmental-payment-gateway'), $currency),
                    'error'
                );
            }
        }
    }
    
    /**
     * Maybe override currency symbol for display
     */
    public function maybe_override_currency_symbol($currency_symbol, $currency) {
        if ($this->enabled === 'yes' && $this->get_option('show_exchange_rate') === 'yes') {
            $base_currency = $this->get_option('base_currency', 'USD');
            if ($currency !== $base_currency) {
                $rate = $this->get_exchange_rate($base_currency, $currency);
                if ($rate) {
                    return $currency_symbol . sprintf(' (≈ %s %s)', number_format($rate, 4), $base_currency);
                }
            }
        }
        
        return $currency_symbol;
    }
}
