<?php
/**
 * Momo Payment Gateway for Environmental Platform
 *
 * @package EnvironmentalPaymentGateway
 * @subpackage VietnamGateways
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Momo Payment Gateway Class
 */
class EPG_Momo_Gateway extends EPG_Gateway_Base {
    
    /**
     * Momo API endpoints
     */
    const SANDBOX_URL = 'https://test-payment.momo.vn/v2/gateway/api/create';
    const LIVE_URL = 'https://payment.momo.vn/v2/gateway/api/create';
    
    const SANDBOX_QUERY_URL = 'https://test-payment.momo.vn/v2/gateway/api/query';
    const LIVE_QUERY_URL = 'https://payment.momo.vn/v2/gateway/api/query';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'epg_momo';
        $this->icon = EPG_PLUGIN_URL . 'assets/images/momo-logo.png';
        $this->has_fields = false;
        $this->method_title = __('Momo Gateway', 'environmental-payment-gateway');
        $this->method_description = __('Accept payments through Momo - Vietnam\'s popular e-wallet', 'environmental-payment-gateway');
        $this->supports = array(
            'products',
            'refunds'
        );
        
        parent::__construct();
        
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->testmode = 'yes' === $this->get_option('testmode');
        $this->partner_code = $this->get_option('partner_code');
        $this->access_key = $this->get_option('access_key');
        $this->secret_key = $this->get_option('secret_key');
        $this->request_type = $this->get_option('request_type', 'payWithMethod');
        
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
                'default' => __('Momo E-Wallet', 'environmental-payment-gateway'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'environmental-payment-gateway'),
                'type' => 'textarea',
                'description' => __('Payment method description that the customer will see on your checkout.', 'environmental-payment-gateway'),
                'default' => __('Pay quickly and securely using your Momo e-wallet.', 'environmental-payment-gateway'),
            ),
            'partner_code' => array(
                'title' => __('Partner Code', 'environmental-payment-gateway'),
                'type' => 'text',
                'description' => __('Enter your Momo Partner Code.', 'environmental-payment-gateway'),
                'desc_tip' => true,
            ),
            'access_key' => array(
                'title' => __('Access Key', 'environmental-payment-gateway'),
                'type' => 'text',
                'description' => __('Enter your Momo Access Key.', 'environmental-payment-gateway'),
                'desc_tip' => true,
            ),
            'secret_key' => array(
                'title' => __('Secret Key', 'environmental-payment-gateway'),
                'type' => 'password',
                'description' => __('Enter your Momo Secret Key.', 'environmental-payment-gateway'),
                'desc_tip' => true,
            ),
            'testmode' => array(
                'title' => __('Test Mode', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Enable Test Mode', 'environmental-payment-gateway'),
                'default' => 'yes',
                'description' => __('Place the payment gateway in test mode using test API keys.', 'environmental-payment-gateway'),
            ),
            'request_type' => array(
                'title' => __('Request Type', 'environmental-payment-gateway'),
                'type' => 'select',
                'description' => __('Select the Momo payment request type.', 'environmental-payment-gateway'),
                'options' => array(
                    'payWithMethod' => __('Pay with Method', 'environmental-payment-gateway'),
                    'payWithATM' => __('Pay with ATM', 'environmental-payment-gateway'),
                    'payWithCC' => __('Pay with Credit Card', 'environmental-payment-gateway'),
                ),
                'default' => 'payWithMethod',
            ),
            'auto_capture' => array(
                'title' => __('Auto Capture', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Automatically capture payments', 'environmental-payment-gateway'),
                'default' => 'yes',
                'description' => __('When enabled, payments are automatically captured.', 'environmental-payment-gateway'),
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
            // Create Momo payment
            $payment_response = $this->create_momo_payment($order);
            
            if ($payment_response['resultCode'] == 0) {
                // Payment request successful
                $order->update_status('pending-payment', __('Awaiting Momo payment', 'environmental-payment-gateway'));
                $order->add_order_note(sprintf(
                    __('Momo payment request created. Request ID: %s', 'environmental-payment-gateway'),
                    $payment_response['requestId']
                ));
                
                $this->log('Momo payment initiated for order: ' . $order_id);
                
                return array(
                    'result' => 'success',
                    'redirect' => $payment_response['payUrl']
                );
                
            } else {
                throw new Exception(sprintf(
                    __('Momo payment failed: %s (Code: %s)', 'environmental-payment-gateway'),
                    $payment_response['message'],
                    $payment_response['resultCode']
                ));
            }
            
        } catch (Exception $e) {
            $this->log('Momo payment error: ' . $e->getMessage());
            wc_add_notice($e->getMessage(), 'error');
            return array(
                'result' => 'failure'
            );
        }
    }
    
    /**
     * Create Momo payment
     */
    private function create_momo_payment($order) {
        $order_id = $order->get_id();
        $amount = intval($order->get_total());
        $request_id = $order_id . '_' . time();
        
        $params = array(
            'partnerCode' => $this->partner_code,
            'partnerName' => get_bloginfo('name'),
            'storeId' => $this->partner_code,
            'requestId' => $request_id,
            'amount' => $amount,
            'orderId' => (string)$order_id,
            'orderInfo' => sprintf(__('Payment for order #%s', 'environmental-payment-gateway'), $order_id),
            'redirectUrl' => $this->get_return_url($order),
            'ipnUrl' => WC()->api_request_url($this->id),
            'lang' => $this->get_momo_locale(),
            'requestType' => $this->request_type,
            'autoCapture' => $this->get_option('auto_capture') === 'yes',
            'extraData' => '',
        );
        
        // Create signature
        $raw_signature = 'accessKey=' . $this->access_key . 
                        '&amount=' . $amount . 
                        '&extraData=' . $params['extraData'] . 
                        '&ipnUrl=' . $params['ipnUrl'] . 
                        '&orderId=' . $order_id . 
                        '&orderInfo=' . $params['orderInfo'] . 
                        '&partnerCode=' . $this->partner_code . 
                        '&redirectUrl=' . $params['redirectUrl'] . 
                        '&requestId=' . $request_id . 
                        '&requestType=' . $this->request_type;
        
        $signature = hash_hmac('sha256', $raw_signature, $this->secret_key);
        $params['signature'] = $signature;
        
        // Make API request
        $api_url = $this->testmode ? self::SANDBOX_URL : self::LIVE_URL;
        
        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($params),
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('Momo API request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid response from Momo API');
        }
        
        return $data;
    }
    
    /**
     * Get Momo locale
     */
    private function get_momo_locale() {
        $locale = get_locale();
        if (strpos($locale, 'vi') === 0) {
            return 'vi';
        }
        return 'en';
    }
    
    /**
     * Handle webhook/IPN from Momo
     */
    public function handle_webhook() {
        $this->log('Momo webhook received');
        
        $raw_data = file_get_contents('php://input');
        $data = json_decode($raw_data, true);
        
        if (empty($data)) {
            wp_die('Momo webhook error: No data received');
        }
        
        // Verify webhook signature
        if (!$this->verify_webhook_signature($data)) {
            wp_die('Momo webhook error: Invalid signature');
        }
        
        $order_id = intval($data['orderId']);
        $result_code = intval($data['resultCode']);
        $transaction_id = sanitize_text_field($data['transId']);
        $request_id = sanitize_text_field($data['requestId']);
        
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wp_die('Order not found');
        }
        
        // Process payment result
        if ($result_code === 0) {
            // Payment successful
            $order->payment_complete($transaction_id);
            $order->add_order_note(sprintf(
                __('Momo payment completed. Transaction ID: %s', 'environmental-payment-gateway'),
                $transaction_id
            ));
            
            $this->log('Momo payment completed for order: ' . $order_id);
            
            // Return success response
            status_header(200);
            echo json_encode(array('status' => 'success'));
            
        } else {
            // Payment failed
            $error_message = $this->get_momo_error_message($result_code);
            $order->update_status('failed', sprintf(
                __('Momo payment failed: %s (Code: %s)', 'environmental-payment-gateway'),
                $error_message,
                $result_code
            ));
            
            $this->log('Momo payment failed for order: ' . $order_id . ' - ' . $error_message);
            
            // Return error response
            status_header(400);
            echo json_encode(array('status' => 'failed', 'message' => $error_message));
        }
        
        exit;
    }
    
    /**
     * Verify webhook signature
     */
    private function verify_webhook_signature($data) {
        $received_signature = $data['signature'];
        
        // Create signature string
        $raw_signature = 'accessKey=' . $this->access_key .
                        '&amount=' . $data['amount'] .
                        '&extraData=' . $data['extraData'] .
                        '&message=' . $data['message'] .
                        '&orderId=' . $data['orderId'] .
                        '&orderInfo=' . $data['orderInfo'] .
                        '&orderType=' . $data['orderType'] .
                        '&partnerCode=' . $data['partnerCode'] .
                        '&payType=' . $data['payType'] .
                        '&requestId=' . $data['requestId'] .
                        '&responseTime=' . $data['responseTime'] .
                        '&resultCode=' . $data['resultCode'] .
                        '&transId=' . $data['transId'];
        
        $calculated_signature = hash_hmac('sha256', $raw_signature, $this->secret_key);
        
        return hash_equals($received_signature, $calculated_signature);
    }
    
    /**
     * Get Momo error message
     */
    private function get_momo_error_message($code) {
        $messages = array(
            0 => __('Success', 'environmental-payment-gateway'),
            9 => __('Transaction is being processed', 'environmental-payment-gateway'),
            10 => __('Transaction failed', 'environmental-payment-gateway'),
            11 => __('Access denied', 'environmental-payment-gateway'),
            12 => __('Invalid amount', 'environmental-payment-gateway'),
            13 => __('Invalid amount', 'environmental-payment-gateway'),
            20 => __('Bad format', 'environmental-payment-gateway'),
            21 => __('Invalid signature', 'environmental-payment-gateway'),
            40 => __('RequestId not found', 'environmental-payment-gateway'),
            41 => __('OrderId not found', 'environmental-payment-gateway'),
            42 => __('OrderId already exists', 'environmental-payment-gateway'),
            43 => __('Access denied', 'environmental-payment-gateway'),
            1000 => __('Transaction initialized', 'environmental-payment-gateway'),
            1001 => __('Transaction completed', 'environmental-payment-gateway'),
            1002 => __('Transaction canceled', 'environmental-payment-gateway'),
            1003 => __('Transaction pending', 'environmental-payment-gateway'),
            1004 => __('Transaction declined', 'environmental-payment-gateway'),
            1005 => __('Transaction failed', 'environmental-payment-gateway'),
            1006 => __('Transaction expired', 'environmental-payment-gateway'),
            2001 => __('Limit exceeded', 'environmental-payment-gateway'),
            2002 => __('Kyc required', 'environmental-payment-gateway'),
            2003 => __('Receiver not found', 'environmental-payment-gateway'),
            2004 => __('Receiver exceeded limit', 'environmental-payment-gateway'),
            2005 => __('Transfer same account', 'environmental-payment-gateway'),
            2006 => __('Invalid receiver', 'environmental-payment-gateway'),
            2007 => __('Invalid bank', 'environmental-payment-gateway'),
            4001 => __('Transaction pending', 'environmental-payment-gateway'),
            4100 => __('Transaction canceled', 'environmental-payment-gateway'),
            7000 => __('Debit success', 'environmental-payment-gateway'),
            7002 => __('Payment rejected', 'environmental-payment-gateway'),
            8000 => __('Bonus success', 'environmental-payment-gateway'),
            9000 => __('Transaction completed', 'environmental-payment-gateway'),
        );
        
        return isset($messages[$code]) ? $messages[$code] : __('Unknown error', 'environmental-payment-gateway');
    }
    
    /**
     * Query transaction status
     */
    public function query_transaction($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return false;
        }
        
        $request_id = $order_id . '_' . strtotime($order->get_date_created());
        
        $params = array(
            'partnerCode' => $this->partner_code,
            'requestId' => $request_id,
            'orderId' => (string)$order_id,
            'lang' => $this->get_momo_locale(),
        );
        
        // Create signature
        $raw_signature = 'accessKey=' . $this->access_key .
                        '&orderId=' . $order_id .
                        '&partnerCode=' . $this->partner_code .
                        '&requestId=' . $request_id;
        
        $signature = hash_hmac('sha256', $raw_signature, $this->secret_key);
        $params['signature'] = $signature;
        
        // Make API request
        $api_url = $this->testmode ? self::SANDBOX_QUERY_URL : self::LIVE_QUERY_URL;
        
        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($params),
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
            // Query transaction first to get full details
            $transaction_data = $this->query_transaction($order_id);
            
            if (!$transaction_data || $transaction_data['resultCode'] !== 0) {
                return new WP_Error('transaction_not_found', __('Transaction not found or invalid.', 'environmental-payment-gateway'));
            }
            
            // Momo refund implementation
            $refund_result = $this->process_momo_refund($order, $amount, $reason, $transaction_data);
            
            if ($refund_result['resultCode'] === 0) {
                $order->add_order_note(sprintf(
                    __('Momo refund completed. Refund ID: %s', 'environmental-payment-gateway'),
                    $refund_result['transId']
                ));
                
                $this->log('Momo refund completed for order: ' . $order_id);
                return true;
            } else {
                return new WP_Error('refund_failed', $this->get_momo_error_message($refund_result['resultCode']));
            }
            
        } catch (Exception $e) {
            $this->log('Momo refund error: ' . $e->getMessage());
            return new WP_Error('refund_error', $e->getMessage());
        }
    }
    
    /**
     * Process Momo refund
     */
    private function process_momo_refund($order, $amount, $reason, $transaction_data) {
        $order_id = $order->get_id();
        $refund_amount = $amount ? intval($amount) : intval($order->get_total());
        $request_id = 'RF_' . $order_id . '_' . time();
        
        $params = array(
            'partnerCode' => $this->partner_code,
            'requestId' => $request_id,
            'orderId' => (string)$order_id,
            'transId' => $transaction_data['transId'],
            'amount' => $refund_amount,
            'description' => $reason ?: __('Refund for order', 'environmental-payment-gateway'),
            'lang' => $this->get_momo_locale(),
        );
        
        // Create signature
        $raw_signature = 'accessKey=' . $this->access_key .
                        '&amount=' . $refund_amount .
                        '&description=' . $params['description'] .
                        '&orderId=' . $order_id .
                        '&partnerCode=' . $this->partner_code .
                        '&requestId=' . $request_id .
                        '&transId=' . $transaction_data['transId'];
        
        $signature = hash_hmac('sha256', $raw_signature, $this->secret_key);
        $params['signature'] = $signature;
        
        // Make refund API request
        // Note: Actual Momo refund endpoint would be used here
        $refund_url = str_replace('/create', '/refund', ($this->testmode ? self::SANDBOX_URL : self::LIVE_URL));
        
        $response = wp_remote_post($refund_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($params),
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('Momo refund API request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid response from Momo refund API');
        }
        
        return $data;
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
                'domestic' => 1.0,
                'international' => 0
            )
        );
    }
}
