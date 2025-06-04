<?php
/**
 * Payment Processor Class
 * 
 * Handles payment gateway integrations including Stripe, PayPal, 
 * and local payment methods for the Environmental Donation System.
 * 
 * @package EnvironmentalDonationSystem
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EDS_Payment_Processor {
    
    /**
     * Available payment processors
     */
    private $processors = array();
    
    /**
     * Initialize payment processor
     */
    public function __construct() {
        $this->load_processors();
        add_action('wp_ajax_eds_create_payment_intent', array($this, 'ajax_create_payment_intent'));
        add_action('wp_ajax_nopriv_eds_create_payment_intent', array($this, 'ajax_create_payment_intent'));
        add_action('wp_ajax_eds_process_webhook', array($this, 'ajax_process_webhook'));
        add_action('wp_ajax_nopriv_eds_process_webhook', array($this, 'ajax_process_webhook'));
    }
    
    /**
     * Load available payment processors
     */
    private function load_processors() {
        // Load processor configurations from settings
        $settings = get_option('eds_payment_settings', array());
        
        // Stripe processor
        if (!empty($settings['stripe_enabled'])) {
            $this->processors['stripe'] = new EDS_Stripe_Processor($settings);
        }
        
        // PayPal processor
        if (!empty($settings['paypal_enabled'])) {
            $this->processors['paypal'] = new EDS_PayPal_Processor($settings);
        }
        
        // Bank transfer (local payment)
        if (!empty($settings['bank_transfer_enabled'])) {
            $this->processors['bank_transfer'] = new EDS_Bank_Transfer_Processor($settings);
        }
        
        // Credit card processor
        if (!empty($settings['credit_card_enabled'])) {
            $this->processors['credit_card'] = new EDS_Credit_Card_Processor($settings);
        }
        
        // Allow third-party processors
        $this->processors = apply_filters('eds_payment_processors', $this->processors);
    }
    
    /**
     * Get available payment methods
     */
    public function get_available_methods() {
        $methods = array();
        
        foreach ($this->processors as $key => $processor) {
            if ($processor->is_available()) {
                $methods[$key] = $processor->get_method_info();
            }
        }
        
        return $methods;
    }
    
    /**
     * Get processor instance
     */
    public function get_processor($processor_name) {
        if (isset($this->processors[$processor_name])) {
            return $this->processors[$processor_name];
        }
        
        return new WP_Error('processor_not_found', 'Payment processor not found');
    }
    
    /**
     * Process payment
     */
    public function process_payment($donation_id, $amount, $payment_data) {
        $processor_name = $payment_data['processor'];
        $processor = $this->get_processor($processor_name);
        
        if (is_wp_error($processor)) {
            return $processor;
        }
        
        return $processor->process_payment($donation_id, $amount, $payment_data);
    }
    
    /**
     * Process refund
     */
    public function process_refund($processor_name, $transaction_id, $amount) {
        $processor = $this->get_processor($processor_name);
        
        if (is_wp_error($processor)) {
            return $processor;
        }
        
        return $processor->process_refund($transaction_id, $amount);
    }
    
    /**
     * Create payment intent (for client-side processing)
     */
    public function ajax_create_payment_intent() {
        check_ajax_referer('eds_donation_nonce', 'nonce');
        
        $amount = floatval($_POST['amount']);
        $currency = sanitize_text_field($_POST['currency']);
        $processor_name = sanitize_text_field($_POST['processor']);
        
        $processor = $this->get_processor($processor_name);
        if (is_wp_error($processor)) {
            wp_send_json_error($processor->get_error_message());
        }
        
        $intent = $processor->create_payment_intent($amount, $currency);
        
        if (is_wp_error($intent)) {
            wp_send_json_error($intent->get_error_message());
        }
        
        wp_send_json_success($intent);
    }
    
    /**
     * Process webhook from payment processors
     */
    public function ajax_process_webhook() {
        $processor_name = sanitize_text_field($_GET['processor']);
        
        $processor = $this->get_processor($processor_name);
        if (is_wp_error($processor)) {
            wp_die('Invalid processor', 'Invalid Request', array('response' => 400));
        }
        
        $result = $processor->handle_webhook();
        
        if (is_wp_error($result)) {
            wp_die($result->get_error_message(), 'Webhook Error', array('response' => 400));
        }
        
        wp_die('OK', 'Success', array('response' => 200));
    }
}

/**
 * Abstract Payment Processor Base Class
 */
abstract class EDS_Payment_Processor_Base {
    
    protected $settings;
    protected $is_live_mode;
    
    public function __construct($settings) {
        $this->settings = $settings;
        $this->is_live_mode = !empty($settings['live_mode']);
    }
    
    abstract public function is_available();
    abstract public function get_method_info();
    abstract public function process_payment($donation_id, $amount, $payment_data);
    abstract public function process_refund($transaction_id, $amount);
    abstract public function handle_webhook();
    
    /**
     * Create payment intent for client-side processing
     */
    public function create_payment_intent($amount, $currency) {
        return new WP_Error('not_supported', 'Payment intent creation not supported by this processor');
    }
    
    /**
     * Log payment processor events
     */
    protected function log($message, $level = 'info') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('[EDS Payment] %s: %s', strtoupper($level), $message));
        }
    }
}

/**
 * Stripe Payment Processor
 */
class EDS_Stripe_Processor extends EDS_Payment_Processor_Base {
    
    private $stripe_api;
    
    public function __construct($settings) {
        parent::__construct($settings);
        
        if ($this->is_available()) {
            require_once EDS_PLUGIN_PATH . 'vendor/stripe/stripe-php/init.php';
            \Stripe\Stripe::setApiKey($this->get_secret_key());
        }
    }
    
    public function is_available() {
        return !empty($this->settings['stripe_enabled']) && 
               !empty($this->settings['stripe_publishable_key']) && 
               !empty($this->settings['stripe_secret_key']);
    }
    
    public function get_method_info() {
        return array(
            'id' => 'stripe',
            'name' => 'Credit/Debit Card',
            'description' => 'Pay securely with your credit or debit card via Stripe',
            'icon' => EDS_PLUGIN_URL . 'assets/images/stripe-icon.png',
            'supports' => array('recurring', 'refunds', 'webhooks'),
        );
    }
    
    public function process_payment($donation_id, $amount, $payment_data) {
        try {
            $payment_intent = \Stripe\PaymentIntent::create([
                'amount' => $amount * 100, // Stripe uses cents
                'currency' => strtolower($payment_data['currency']),
                'payment_method' => $payment_data['payment_method_id'],
                'confirmation_method' => 'manual',
                'confirm' => true,
                'metadata' => [
                    'donation_id' => $donation_id,
                    'source' => 'environmental_donation_system',
                ],
            ]);
            
            if ($payment_intent->status === 'succeeded') {
                return array(
                    'status' => 'completed',
                    'transaction_id' => $payment_intent->id,
                    'fee' => $this->calculate_stripe_fee($amount),
                    'net_amount' => $amount - $this->calculate_stripe_fee($amount),
                );
            } elseif ($payment_intent->status === 'requires_action') {
                return array(
                    'status' => 'requires_action',
                    'client_secret' => $payment_intent->client_secret,
                    'transaction_id' => $payment_intent->id,
                );
            } else {
                return new WP_Error('payment_failed', 'Payment failed: ' . $payment_intent->status);
            }
            
        } catch (\Stripe\Exception\CardException $e) {
            return new WP_Error('card_error', $e->getError()->message);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            return new WP_Error('invalid_request', $e->getError()->message);
        } catch (Exception $e) {
            return new WP_Error('payment_error', 'Payment processing failed');
        }
    }
    
    public function create_payment_intent($amount, $currency) {
        try {
            $payment_intent = \Stripe\PaymentIntent::create([
                'amount' => $amount * 100,
                'currency' => strtolower($currency),
                'metadata' => [
                    'source' => 'environmental_donation_system',
                ],
            ]);
            
            return array(
                'client_secret' => $payment_intent->client_secret,
                'publishable_key' => $this->get_publishable_key(),
            );
            
        } catch (Exception $e) {
            return new WP_Error('intent_creation_failed', $e->getMessage());
        }
    }
    
    public function process_refund($transaction_id, $amount) {
        try {
            $refund = \Stripe\Refund::create([
                'payment_intent' => $transaction_id,
                'amount' => $amount * 100,
            ]);
            
            return array(
                'status' => 'success',
                'refund_id' => $refund->id,
                'amount' => $amount,
            );
            
        } catch (Exception $e) {
            return new WP_Error('refund_failed', $e->getMessage());
        }
    }
    
    public function handle_webhook() {
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $endpoint_secret = $this->settings['stripe_webhook_secret'];
        
        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
            
            switch ($event['type']) {
                case 'payment_intent.succeeded':
                    $this->handle_payment_succeeded($event['data']['object']);
                    break;
                case 'payment_intent.payment_failed':
                    $this->handle_payment_failed($event['data']['object']);
                    break;
                case 'invoice.payment_succeeded':
                    $this->handle_subscription_payment($event['data']['object']);
                    break;
            }
            
            return true;
            
        } catch (Exception $e) {
            return new WP_Error('webhook_error', $e->getMessage());
        }
    }
    
    private function handle_payment_succeeded($payment_intent) {
        $donation_id = $payment_intent['metadata']['donation_id'];
        if ($donation_id) {
            $donation_manager = new EDS_Donation_Manager();
            $donation_manager->update_donation_status($donation_id, 'completed');
        }
    }
    
    private function handle_payment_failed($payment_intent) {
        $donation_id = $payment_intent['metadata']['donation_id'];
        if ($donation_id) {
            $donation_manager = new EDS_Donation_Manager();
            $donation_manager->update_donation_status($donation_id, 'failed');
        }
    }
    
    private function handle_subscription_payment($invoice) {
        // Handle recurring donation payment
        $subscription_id = $invoice['subscription'];
        // Process recurring donation logic here
    }
    
    private function get_publishable_key() {
        return $this->is_live_mode ? 
            $this->settings['stripe_live_publishable_key'] : 
            $this->settings['stripe_test_publishable_key'];
    }
    
    private function get_secret_key() {
        return $this->is_live_mode ? 
            $this->settings['stripe_live_secret_key'] : 
            $this->settings['stripe_test_secret_key'];
    }
    
    private function calculate_stripe_fee($amount) {
        // Stripe fee: 2.9% + $0.30 for US cards
        return ($amount * 0.029) + 0.30;
    }
}

/**
 * PayPal Payment Processor
 */
class EDS_PayPal_Processor extends EDS_Payment_Processor_Base {
    
    public function is_available() {
        return !empty($this->settings['paypal_enabled']) && 
               !empty($this->settings['paypal_client_id']) && 
               !empty($this->settings['paypal_client_secret']);
    }
    
    public function get_method_info() {
        return array(
            'id' => 'paypal',
            'name' => 'PayPal',
            'description' => 'Pay with your PayPal account or credit card',
            'icon' => EDS_PLUGIN_URL . 'assets/images/paypal-icon.png',
            'supports' => array('recurring', 'refunds', 'webhooks'),
        );
    }
    
    public function process_payment($donation_id, $amount, $payment_data) {
        // PayPal payment processing logic
        $api_url = $this->is_live_mode ? 
            'https://api.paypal.com' : 
            'https://api.sandbox.paypal.com';
        
        $access_token = $this->get_access_token();
        if (is_wp_error($access_token)) {
            return $access_token;
        }
        
        $payment_data = array(
            'intent' => 'CAPTURE',
            'purchase_units' => array(
                array(
                    'amount' => array(
                        'currency_code' => $payment_data['currency'],
                        'value' => number_format($amount, 2, '.', ''),
                    ),
                    'custom_id' => $donation_id,
                )
            ),
        );
        
        $response = wp_remote_post($api_url . '/v2/checkout/orders', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $access_token,
            ),
            'body' => wp_json_encode($payment_data),
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['id'])) {
            return array(
                'status' => 'pending',
                'transaction_id' => $data['id'],
                'approval_url' => $this->get_approval_url($data['links']),
            );
        }
        
        return new WP_Error('paypal_error', 'PayPal payment creation failed');
    }
    
    public function process_refund($transaction_id, $amount) {
        $access_token = $this->get_access_token();
        if (is_wp_error($access_token)) {
            return $access_token;
        }
        
        $api_url = $this->is_live_mode ? 
            'https://api.paypal.com' : 
            'https://api.sandbox.paypal.com';
        
        $refund_data = array(
            'amount' => array(
                'value' => number_format($amount, 2, '.', ''),
                'currency_code' => 'USD', // Should get from original transaction
            ),
        );
        
        $response = wp_remote_post($api_url . '/v2/payments/captures/' . $transaction_id . '/refund', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $access_token,
            ),
            'body' => wp_json_encode($refund_data),
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['id'])) {
            return array(
                'status' => 'success',
                'refund_id' => $data['id'],
                'amount' => $amount,
            );
        }
        
        return new WP_Error('paypal_refund_error', 'PayPal refund failed');
    }
    
    public function handle_webhook() {
        $payload = @file_get_contents('php://input');
        $data = json_decode($payload, true);
        
        if (isset($data['event_type'])) {
            switch ($data['event_type']) {
                case 'PAYMENT.CAPTURE.COMPLETED':
                    $this->handle_payment_completed($data['resource']);
                    break;
                case 'PAYMENT.CAPTURE.DENIED':
                    $this->handle_payment_failed($data['resource']);
                    break;
            }
        }
        
        return true;
    }
    
    private function get_access_token() {
        $api_url = $this->is_live_mode ? 
            'https://api.paypal.com' : 
            'https://api.sandbox.paypal.com';
        
        $client_id = $this->settings['paypal_client_id'];
        $client_secret = $this->settings['paypal_client_secret'];
        
        $response = wp_remote_post($api_url . '/v1/oauth2/token', array(
            'headers' => array(
                'Accept' => 'application/json',
                'Accept-Language' => 'en_US',
                'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $client_secret),
            ),
            'body' => 'grant_type=client_credentials',
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['access_token'])) {
            return $data['access_token'];
        }
        
        return new WP_Error('paypal_auth_error', 'Failed to get PayPal access token');
    }
    
    private function get_approval_url($links) {
        foreach ($links as $link) {
            if ($link['rel'] === 'approve') {
                return $link['href'];
            }
        }
        return null;
    }
    
    private function handle_payment_completed($resource) {
        $donation_id = $resource['custom_id'];
        if ($donation_id) {
            $donation_manager = new EDS_Donation_Manager();
            $donation_manager->update_donation_status($donation_id, 'completed');
        }
    }
    
    private function handle_payment_failed($resource) {
        $donation_id = $resource['custom_id'];
        if ($donation_id) {
            $donation_manager = new EDS_Donation_Manager();
            $donation_manager->update_donation_status($donation_id, 'failed');
        }
    }
}

/**
 * Bank Transfer Payment Processor (Local Payment)
 */
class EDS_Bank_Transfer_Processor extends EDS_Payment_Processor_Base {
    
    public function is_available() {
        return !empty($this->settings['bank_transfer_enabled']);
    }
    
    public function get_method_info() {
        return array(
            'id' => 'bank_transfer',
            'name' => 'Bank Transfer',
            'description' => 'Transfer funds directly to our bank account',
            'icon' => EDS_PLUGIN_URL . 'assets/images/bank-icon.png',
            'supports' => array(),
            'instructions' => $this->settings['bank_transfer_instructions'],
        );
    }
    
    public function process_payment($donation_id, $amount, $payment_data) {
        // Bank transfer is manual - just create pending transaction
        return array(
            'status' => 'pending_bank_transfer',
            'transaction_id' => 'BT_' . $donation_id . '_' . time(),
            'net_amount' => $amount,
            'fee' => 0,
            'instructions' => $this->settings['bank_transfer_instructions'],
        );
    }
    
    public function process_refund($transaction_id, $amount) {
        // Bank transfer refunds are manual
        return array(
            'status' => 'manual_refund_required',
            'message' => 'Bank transfer refund must be processed manually',
        );
    }
    
    public function handle_webhook() {
        // Bank transfers don't have webhooks
        return true;
    }
}

/**
 * Credit Card Processor (Generic)
 */
class EDS_Credit_Card_Processor extends EDS_Payment_Processor_Base {
    
    public function is_available() {
        return !empty($this->settings['credit_card_enabled']) && 
               !empty($this->settings['credit_card_gateway']);
    }
    
    public function get_method_info() {
        return array(
            'id' => 'credit_card',
            'name' => 'Credit Card',
            'description' => 'Pay with your credit card',
            'icon' => EDS_PLUGIN_URL . 'assets/images/credit-card-icon.png',
            'supports' => array('refunds'),
        );
    }
    
    public function process_payment($donation_id, $amount, $payment_data) {
        // Implement based on configured gateway
        $gateway = $this->settings['credit_card_gateway'];
        
        switch ($gateway) {
            case 'authorize_net':
                return $this->process_authorize_net_payment($donation_id, $amount, $payment_data);
            case 'square':
                return $this->process_square_payment($donation_id, $amount, $payment_data);
            default:
                return new WP_Error('unsupported_gateway', 'Credit card gateway not supported');
        }
    }
    
    public function process_refund($transaction_id, $amount) {
        // Implement based on configured gateway
        return new WP_Error('refund_not_implemented', 'Credit card refund not implemented');
    }
    
    public function handle_webhook() {
        // Implement based on configured gateway
        return true;
    }
    
    private function process_authorize_net_payment($donation_id, $amount, $payment_data) {
        // Authorize.Net implementation
        return new WP_Error('not_implemented', 'Authorize.Net integration not implemented');
    }
    
    private function process_square_payment($donation_id, $amount, $payment_data) {
        // Square implementation
        return new WP_Error('not_implemented', 'Square integration not implemented');
    }
}
