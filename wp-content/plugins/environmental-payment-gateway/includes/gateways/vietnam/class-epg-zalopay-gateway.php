<?php
/**
 * ZaloPay Payment Gateway for Environmental Platform
 *
 * @package EnvironmentalPaymentGateway
 * @subpackage VietnamGateways
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ZaloPay Payment Gateway Class
 */
class EPG_ZaloPay_Gateway extends EPG_Gateway_Base {
    
    /**
     * ZaloPay API endpoints
     */
    const SANDBOX_URL = 'https://sb-openapi.zalopay.vn/v2/create';
    const LIVE_URL = 'https://openapi.zalopay.vn/v2/create';
    
    const SANDBOX_QUERY_URL = 'https://sb-openapi.zalopay.vn/v2/query';
    const LIVE_QUERY_URL = 'https://openapi.zalopay.vn/v2/query';
    
    const SANDBOX_REFUND_URL = 'https://sb-openapi.zalopay.vn/v2/refund';
    const LIVE_REFUND_URL = 'https://openapi.zalopay.vn/v2/refund';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'epg_zalopay';
        $this->icon = EPG_PLUGIN_URL . 'assets/images/zalopay-logo.png';
        $this->has_fields = false;
        $this->method_title = __('ZaloPay Gateway', 'environmental-payment-gateway');
        $this->method_description = __('Accept payments through ZaloPay - Vietnam\'s trusted digital wallet', 'environmental-payment-gateway');
        $this->supports = array(
            'products',
            'refunds'
        );
        
        parent::__construct();
        
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->testmode = 'yes' === $this->get_option('testmode');
        $this->app_id = $this->get_option('app_id');
        $this->key1 = $this->get_option('key1');
        $this->key2 = $this->get_option('key2');
        
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_' . $this->id, array($this, 'handle_webhook'));
    }
    
    /**
     * Initialize gateway settings form fields
     */
    public function init_form_fields() {
        parent::init_form_fields();
        
        $this->form_fields = array_merge($this->form_fields, array(
            'title' => array(
                'title' => __('Title', 'environmental-payment-gateway'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'environmental-payment-gateway'),
                'default' => __('ZaloPay', 'environmental-payment-gateway'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'environmental-payment-gateway'),
                'type' => 'textarea',
                'description' => __('Payment method description that the customer will see on your checkout.', 'environmental-payment-gateway'),
                'default' => __('Pay securely with ZaloPay digital wallet - fast, safe and convenient.', 'environmental-payment-gateway'),
            ),
            'app_id' => array(
                'title' => __('App ID', 'environmental-payment-gateway'),
                'type' => 'text',
                'description' => __('Enter your ZaloPay App ID.', 'environmental-payment-gateway'),
                'desc_tip' => true,
            ),
            'key1' => array(
                'title' => __('Key 1', 'environmental-payment-gateway'),
                'type' => 'password',
                'description' => __('Enter your ZaloPay Key 1.', 'environmental-payment-gateway'),
                'desc_tip' => true,
            ),
            'key2' => array(
                'title' => __('Key 2', 'environmental-payment-gateway'),
                'type' => 'password',
                'description' => __('Enter your ZaloPay Key 2.', 'environmental-payment-gateway'),
                'desc_tip' => true,
            ),
            'testmode' => array(
                'title' => __('Test Mode', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Enable Test Mode', 'environmental-payment-gateway'),
                'default' => 'yes',
                'description' => __('Place the payment gateway in test mode using test API keys.', 'environmental-payment-gateway'),
            ),
            'payment_method' => array(
                'title' => __('Payment Method', 'environmental-payment-gateway'),
                'type' => 'select',
                'description' => __('Select the ZaloPay payment method.', 'environmental-payment-gateway'),
                'options' => array(
                    'zalopayapp' => __('ZaloPay App', 'environmental-payment-gateway'),
                    'zalopaycc' => __('ZaloPay Credit Card', 'environmental-payment-gateway'),
                    'zalopayatm' => __('ZaloPay ATM', 'environmental-payment-gateway'),
                ),
                'default' => 'zalopayapp',
            ),
            'embed_data' => array(
                'title' => __('Enable QR Code', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Show QR code for payment', 'environmental-payment-gateway'),
                'default' => 'no',
                'description' => __('When enabled, customers can scan QR code to pay.', 'environmental-payment-gateway'),
            )
        ));
    }
    
    /**
     * Process payment
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return array(
                'result' => 'failure',
                'messages' => __('Order not found.', 'environmental-payment-gateway')
            );
        }
        
        try {
            // Create ZaloPay payment
            $payment_response = $this->create_zalopay_payment($order);
            
            if ($payment_response['return_code'] == 1) {
                // Payment request successful
                $order->update_status('pending-payment', __('Awaiting ZaloPay payment', 'environmental-payment-gateway'));
                $order->add_order_note(sprintf(
                    __('ZaloPay payment request created. ZP Trans Token: %s', 'environmental-payment-gateway'),
                    $payment_response['zp_trans_token']
                ));
                
                $this->log('ZaloPay payment initiated for order: ' . $order_id);
                
                return array(
                    'result' => 'success',
                    'redirect' => $payment_response['order_url']
                );
                
            } else {
                throw new Exception(sprintf(
                    __('ZaloPay payment failed: %s (Code: %s)', 'environmental-payment-gateway'),
                    $payment_response['return_message'],
                    $payment_response['return_code']
                ));
            }
            
        } catch (Exception $e) {
            $this->log('ZaloPay payment error: ' . $e->getMessage());
            wc_add_notice($e->getMessage(), 'error');
            return array(
                'result' => 'failure'
            );
        }
    }
    
    /**
     * Create ZaloPay payment
     */
    private function create_zalopay_payment($order) {
        $order_id = $order->get_id();
        $amount = intval($order->get_total());
        $app_trans_id = date('ymd') . '_' . $order_id . '_' . time();
        
        $embed_data = array();
        if ($this->get_option('embed_data') === 'yes') {
            $embed_data['redirecturl'] = $this->get_return_url($order);
        }
        
        $item = array(
            array(
                'itemid' => (string)$order_id,
                'itemname' => sprintf(__('Order #%s', 'environmental-payment-gateway'), $order_id),
                'itemprice' => $amount,
                'itemquantity' => 1
            )
        );
        
        $params = array(
            'app_id' => $this->app_id,
            'app_user' => $order->get_billing_email() ?: 'user_' . $order_id,
            'app_time' => round(microtime(true) * 1000), // milliseconds
            'amount' => $amount,
            'app_trans_id' => $app_trans_id,
            'embed_data' => json_encode($embed_data),
            'item' => json_encode($item),
            'description' => sprintf(__('Payment for order #%s at %s', 'environmental-payment-gateway'), $order_id, get_bloginfo('name')),
            'bank_code' => $this->get_option('payment_method', 'zalopayapp'),
            'callback_url' => WC()->api_request_url($this->id),
        );
        
        // Create MAC
        $data = $params['app_id'] . '|' . $params['app_trans_id'] . '|' . $params['app_user'] . '|' . 
                $params['amount'] . '|' . $params['app_time'] . '|' . $params['embed_data'] . '|' . 
                $params['item'];
        
        $params['mac'] = hash_hmac('sha256', $data, $this->key1);
        
        // Make API request
        $api_url = $this->testmode ? self::SANDBOX_URL : self::LIVE_URL;
        
        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
            'body' => http_build_query($params),
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('ZaloPay API request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid response from ZaloPay API');
        }
        
        return $data;
    }
    
    /**
     * Handle webhook/callback from ZaloPay
     */
    public function handle_webhook() {
        $this->log('ZaloPay webhook received');
        
        $raw_data = file_get_contents('php://input');
        
        if (empty($raw_data)) {
            wp_die('ZaloPay webhook error: No data received');
        }
        
        // Parse callback data
        parse_str($raw_data, $callback_data);
        
        if (empty($callback_data)) {
            wp_die('ZaloPay webhook error: Invalid data format');
        }
        
        // Verify webhook signature
        if (!$this->verify_webhook_signature($callback_data)) {
            wp_die('ZaloPay webhook error: Invalid signature');
        }
        
        $app_trans_id = sanitize_text_field($callback_data['data']['app_trans_id']);
        $zp_trans_id = sanitize_text_field($callback_data['data']['zp_trans_id']);
        $status = intval($callback_data['data']['status']);
        
        // Extract order ID from app_trans_id
        $parts = explode('_', $app_trans_id);
        $order_id = isset($parts[1]) ? intval($parts[1]) : 0;
        
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wp_die('Order not found');
        }
        
        // Process payment result
        if ($status === 1) {
            // Payment successful
            $order->payment_complete($zp_trans_id);
            $order->add_order_note(sprintf(
                __('ZaloPay payment completed. Transaction ID: %s', 'environmental-payment-gateway'),
                $zp_trans_id
            ));
            
            $this->log('ZaloPay payment completed for order: ' . $order_id);
            
            // Return success response
            echo json_encode(array('return_code' => 1, 'return_message' => 'success'));
            
        } else {
            // Payment failed
            $order->update_status('failed', __('ZaloPay payment failed', 'environmental-payment-gateway'));
            
            $this->log('ZaloPay payment failed for order: ' . $order_id);
            
            // Return error response
            echo json_encode(array('return_code' => -1, 'return_message' => 'failed'));
        }
        
        exit;
    }
    
    /**
     * Verify webhook signature
     */
    private function verify_webhook_signature($callback_data) {
        $mac = $callback_data['mac'];
        $data = $callback_data['data'];
        
        $calculated_mac = hash_hmac('sha256', $data, $this->key2);
        
        return hash_equals($mac, $calculated_mac);
    }
    
    /**
     * Query transaction status
     */
    public function query_transaction($app_trans_id) {
        $params = array(
            'app_id' => $this->app_id,
            'app_trans_id' => $app_trans_id,
        );
        
        $data = $params['app_id'] . '|' . $params['app_trans_id'] . '|' . $this->key1;
        $params['mac'] = hash_hmac('sha256', $data, $this->key1);
        
        // Make API request
        $api_url = $this->testmode ? self::SANDBOX_QUERY_URL : self::LIVE_QUERY_URL;
        
        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
            'body' => http_build_query($params),
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return $data;
    }
    
    /**
     * Process refund
     */
    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return new WP_Error('invalid_order', __('Order not found.', 'environmental-payment-gateway'));
        }
        
        $transaction_id = $order->get_transaction_id();
        
        if (empty($transaction_id)) {
            return new WP_Error('no_transaction_id', __('No transaction ID found.', 'environmental-payment-gateway'));
        }
        
        try {
            // Get app_trans_id from order notes or reconstruct it
            $app_trans_id = $this->get_app_trans_id_from_order($order);
            
            if (!$app_trans_id) {
                return new WP_Error('no_app_trans_id', __('Could not find ZaloPay transaction reference.', 'environmental-payment-gateway'));
            }
            
            // ZaloPay refund implementation
            $refund_result = $this->process_zalopay_refund($order, $amount, $reason, $app_trans_id);
            
            if ($refund_result['return_code'] === 1) {
                $order->add_order_note(sprintf(
                    __('ZaloPay refund completed. Refund ID: %s', 'environmental-payment-gateway'),
                    $refund_result['refund_id']
                ));
                
                $this->log('ZaloPay refund completed for order: ' . $order_id);
                return true;
            } else {
                return new WP_Error('refund_failed', $refund_result['return_message']);
            }
            
        } catch (Exception $e) {
            $this->log('ZaloPay refund error: ' . $e->getMessage());
            return new WP_Error('refund_error', $e->getMessage());
        }
    }
    
    /**
     * Get app_trans_id from order
     */
    private function get_app_trans_id_from_order($order) {
        // Try to reconstruct app_trans_id
        $order_id = $order->get_id();
        $date_created = $order->get_date_created();
        
        if ($date_created) {
            $date_str = $date_created->format('ymd');
            // This is an approximation - ideally we'd store the exact app_trans_id
            return $date_str . '_' . $order_id . '_*';
        }
        
        return false;
    }
    
    /**
     * Process ZaloPay refund
     */
    private function process_zalopay_refund($order, $amount, $reason, $app_trans_id) {
        $order_id = $order->get_id();
        $refund_amount = $amount ? intval($amount) : intval($order->get_total());
        $timestamp = round(microtime(true) * 1000);
        $m_refund_id = date('ymd') . '_' . $order_id . '_' . time() . '_refund';
        
        $params = array(
            'app_id' => $this->app_id,
            'zp_trans_id' => $order->get_transaction_id(),
            'amount' => $refund_amount,
            'description' => $reason ?: __('Refund for order', 'environmental-payment-gateway'),
            'timestamp' => $timestamp,
            'm_refund_id' => $m_refund_id,
        );
        
        // Create MAC
        $data = $params['app_id'] . '|' . $params['zp_trans_id'] . '|' . $params['amount'] . '|' . 
                $params['description'] . '|' . $params['timestamp'];
        
        $params['mac'] = hash_hmac('sha256', $data, $this->key1);
        
        // Make refund API request
        $api_url = $this->testmode ? self::SANDBOX_REFUND_URL : self::LIVE_REFUND_URL;
        
        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
            'body' => http_build_query($params),
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('ZaloPay refund API request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid response from ZaloPay refund API');
        }
        
        return $data;
    }
    
    /**
     * Add payment method to checkout
     */
    public function payment_fields() {
        if ($this->description) {
            echo wpautop(wptexturize($this->description));
        }
        
        if ($this->get_option('embed_data') === 'yes') {
            echo '<div class="zalopay-qr-info">';
            echo '<p>' . __('You can pay by scanning QR code with ZaloPay app after clicking "Place Order".', 'environmental-payment-gateway') . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Get supported currencies
     */
    public function get_supported_currencies() {
        return array('VND');
    }
    
    /**
     * Check if currency is supported
     */
    public function is_currency_supported($currency = null) {
        if (!$currency) {
            $currency = get_woocommerce_currency();
        }
        
        return in_array($currency, $this->get_supported_currencies());
    }
    
    /**
     * Get EDS processor configuration
     */
    public function get_eds_processor() {
        return array(
            'id' => $this->id,
            'name' => $this->method_title,
            'description' => $this->method_description,
            'supports_recurring' => false,
            'supported_currencies' => array('VND'),
            'fee_structure' => array(
                'type' => 'percentage',
                'domestic' => 1.1,
                'international' => 0
            )
        );
    }
    
    /**
     * Admin options
     */
    public function admin_options() {
        if (!$this->is_currency_supported()) {
            echo '<div class="notice notice-warning"><p>' . 
                 sprintf(__('ZaloPay only supports VND currency. Current currency is %s.', 'environmental-payment-gateway'), get_woocommerce_currency()) . 
                 '</p></div>';
        }
        
        parent::admin_options();
    }
}
