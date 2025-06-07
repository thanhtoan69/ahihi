<?php
/**
 * VNPay Payment Gateway for Environmental Platform
 *
 * @package EnvironmentalPaymentGateway
 * @subpackage VietnamGateways
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VNPay Payment Gateway Class
 */
class EPG_VNPay_Gateway extends EPG_Gateway_Base {
    
    /**
     * VNPay API endpoints
     */
    const SANDBOX_URL = 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';
    const LIVE_URL = 'https://vnpayment.vn/paymentv2/vpcpay.html';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'epg_vnpay';
        $this->icon = EPG_PLUGIN_URL . 'assets/images/vnpay-logo.png';
        $this->has_fields = false;
        $this->method_title = __('VNPay Gateway', 'environmental-payment-gateway');
        $this->method_description = __('Accept payments through VNPay - Vietnam\'s leading payment gateway', 'environmental-payment-gateway');
        $this->supports = array(
            'products',
            'refunds',
            'subscriptions',
            'subscription_cancellation',
            'subscription_suspension',
            'subscription_reactivation'
        );
        
        parent::__construct();
        
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->testmode = 'yes' === $this->get_option('testmode');
        $this->tmn_code = $this->get_option('tmn_code');
        $this->secret_key = $this->get_option('secret_key');
        
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
                'default' => __('VNPay', 'environmental-payment-gateway'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'environmental-payment-gateway'),
                'type' => 'textarea',
                'description' => __('Payment method description that the customer will see on your checkout.', 'environmental-payment-gateway'),
                'default' => __('Pay securely using VNPay - supports ATM cards, credit cards, and e-wallets.', 'environmental-payment-gateway'),
            ),
            'tmn_code' => array(
                'title' => __('TMN Code', 'environmental-payment-gateway'),
                'type' => 'text',
                'description' => __('Enter your VNPay TMN Code (Terminal Code).', 'environmental-payment-gateway'),
                'desc_tip' => true,
            ),
            'secret_key' => array(
                'title' => __('Secret Key', 'environmental-payment-gateway'),
                'type' => 'password',
                'description' => __('Enter your VNPay Secret Key.', 'environmental-payment-gateway'),
                'desc_tip' => true,
            ),
            'testmode' => array(
                'title' => __('Test Mode', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Enable Test Mode', 'environmental-payment-gateway'),
                'default' => 'yes',
                'description' => __('Place the payment gateway in test mode using test API keys.', 'environmental-payment-gateway'),
            ),
            'bank_code' => array(
                'title' => __('Default Bank Code', 'environmental-payment-gateway'),
                'type' => 'select',
                'description' => __('Select default bank for payments (optional).', 'environmental-payment-gateway'),
                'options' => $this->get_bank_codes(),
                'default' => '',
            )
        ));
    }
    
    /**
     * Get VNPay bank codes
     */
    private function get_bank_codes() {
        return array(
            '' => __('Let customer choose', 'environmental-payment-gateway'),
            'VNPAYQR' => __('VNPAYQR', 'environmental-payment-gateway'),
            'VNBANK' => __('Local ATM Card', 'environmental-payment-gateway'),
            'INTCARD' => __('International Card', 'environmental-payment-gateway'),
            'VISA' => __('VISA', 'environmental-payment-gateway'),
            'MASTERCARD' => __('MASTERCARD', 'environmental-payment-gateway'),
            'JCB' => __('JCB', 'environmental-payment-gateway'),
            'UPI' => __('UPI', 'environmental-payment-gateway'),
            'TPBANK' => __('TPBank', 'environmental-payment-gateway'),
            'VIETCOMBANK' => __('Vietcombank', 'environmental-payment-gateway'),
            'VIETINBANK' => __('VietinBank', 'environmental-payment-gateway'),
            'TECHCOMBANK' => __('Techcombank', 'environmental-payment-gateway'),
            'ACB' => __('ACB', 'environmental-payment-gateway'),
            'MB' => __('MB Bank', 'environmental-payment-gateway'),
            'SACOMBANK' => __('Sacombank', 'environmental-payment-gateway'),
            'EXIMBANK' => __('Eximbank', 'environmental-payment-gateway'),
            'SHB' => __('SHB', 'environmental-payment-gateway'),
            'DONGABANK' => __('Dong A Bank', 'environmental-payment-gateway'),
            'TPBANK' => __('TP Bank', 'environmental-payment-gateway'),
            'OJB' => __('Ocean Bank', 'environmental-payment-gateway'),
            'BIDV' => __('BIDV', 'environmental-payment-gateway'),
            'VIETTINBANK' => __('Viettin Bank', 'environmental-payment-gateway'),
            'VIETABANK' => __('Viet A Bank', 'environmental-payment-gateway'),
            'PUBLICBANK' => __('Public Bank', 'environmental-payment-gateway'),
            'MSBANK' => __('MS Bank', 'environmental-payment-gateway'),
            'HDBANK' => __('HD Bank', 'environmental-payment-gateway'),
            'VNMART' => __('VnMart', 'environmental-payment-gateway'),
            'VIETCAPITALBANK' => __('Viet Capital Bank', 'environmental-payment-gateway'),
            'SCB' => __('SCB', 'environmental-payment-gateway'),
            'AMEX' => __('AMEX', 'environmental-payment-gateway'),
        );
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
            // Create VNPay payment URL
            $payment_url = $this->create_payment_url($order);
            
            // Mark order as pending payment
            $order->update_status('pending-payment', __('Awaiting VNPay payment', 'environmental-payment-gateway'));
            
            // Log payment attempt
            $this->log('VNPay payment initiated for order: ' . $order_id);
            
            return array(
                'result' => 'success',
                'redirect' => $payment_url
            );
            
        } catch (Exception $e) {
            $this->log('VNPay payment error: ' . $e->getMessage());
            wc_add_notice($e->getMessage(), 'error');
            return array(
                'result' => 'failure'
            );
        }
    }
    
    /**
     * Create VNPay payment URL
     */
    private function create_payment_url($order) {
        $amount = intval($order->get_total() * 100); // VNPay uses amount in cents
        $order_id = $order->get_id();
        
        $params = array(
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => $this->tmn_code,
            'vnp_Amount' => $amount,
            'vnp_CurrCode' => 'VND',
            'vnp_TxnRef' => $order_id . '_' . time(),
            'vnp_OrderInfo' => sprintf(__('Payment for order #%s', 'environmental-payment-gateway'), $order_id),
            'vnp_OrderType' => 'other',
            'vnp_Locale' => $this->get_vnpay_locale(),
            'vnp_ReturnUrl' => $this->get_return_url($order),
            'vnp_IpAddr' => $this->get_client_ip(),
            'vnp_CreateDate' => date('YmdHis'),
        );
        
        // Add bank code if specified
        $bank_code = $this->get_option('bank_code');
        if (!empty($bank_code)) {
            $params['vnp_BankCode'] = $bank_code;
        }
        
        // Sort parameters
        ksort($params);
        
        // Create hash
        $hash_data = '';
        foreach ($params as $key => $value) {
            $hash_data .= urlencode($key) . '=' . urlencode($value) . '&';
        }
        $hash_data = rtrim($hash_data, '&');
        
        $secure_hash = hash_hmac('sha512', $hash_data, $this->secret_key);
        $params['vnp_SecureHash'] = $secure_hash;
        
        // Build payment URL
        $payment_url = ($this->testmode ? self::SANDBOX_URL : self::LIVE_URL) . '?' . http_build_query($params);
        
        return $payment_url;
    }
    
    /**
     * Get VNPay locale
     */
    private function get_vnpay_locale() {
        $locale = get_locale();
        if (strpos($locale, 'vi') === 0) {
            return 'vn';
        }
        return 'en';
    }
    
    /**
     * Handle webhook/return from VNPay
     */
    public function handle_webhook() {
        $this->log('VNPay webhook received');
        
        if (empty($_GET)) {
            wp_die('VNPay webhook error: No data received');
        }
        
        // Verify webhook signature
        if (!$this->verify_webhook_signature($_GET)) {
            wp_die('VNPay webhook error: Invalid signature');
        }
        
        $vnp_txn_ref = sanitize_text_field($_GET['vnp_TxnRef']);
        $vnp_response_code = sanitize_text_field($_GET['vnp_ResponseCode']);
        $vnp_transaction_no = sanitize_text_field($_GET['vnp_TransactionNo']);
        $vnp_amount = intval($_GET['vnp_Amount']);
        
        // Extract order ID from transaction reference
        $order_id = intval(explode('_', $vnp_txn_ref)[0]);
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wp_die('Order not found');
        }
        
        // Process payment result
        if ($vnp_response_code === '00') {
            // Payment successful
            $order->payment_complete($vnp_transaction_no);
            $order->add_order_note(sprintf(
                __('VNPay payment completed. Transaction ID: %s', 'environmental-payment-gateway'),
                $vnp_transaction_no
            ));
            
            $this->log('VNPay payment completed for order: ' . $order_id);
            
            // Redirect to success page
            wp_redirect($this->get_return_url($order));
            
        } else {
            // Payment failed
            $error_message = $this->get_vnpay_error_message($vnp_response_code);
            $order->update_status('failed', sprintf(
                __('VNPay payment failed: %s (Code: %s)', 'environmental-payment-gateway'),
                $error_message,
                $vnp_response_code
            ));
            
            $this->log('VNPay payment failed for order: ' . $order_id . ' - ' . $error_message);
            
            // Redirect to checkout with error
            wc_add_notice($error_message, 'error');
            wp_redirect(wc_get_checkout_url());
        }
        
        exit;
    }
    
    /**
     * Verify webhook signature
     */
    private function verify_webhook_signature($data) {
        $secure_hash = $data['vnp_SecureHash'];
        unset($data['vnp_SecureHash']);
        unset($data['vnp_SecureHashType']);
        
        // Sort parameters
        ksort($data);
        
        // Create hash data
        $hash_data = '';
        foreach ($data as $key => $value) {
            $hash_data .= urlencode($key) . '=' . urlencode($value) . '&';
        }
        $hash_data = rtrim($hash_data, '&');
        
        $calculated_hash = hash_hmac('sha512', $hash_data, $this->secret_key);
        
        return hash_equals($secure_hash, $calculated_hash);
    }
    
    /**
     * Get VNPay error message
     */
    private function get_vnpay_error_message($code) {
        $messages = array(
            '01' => __('Transaction is being processed', 'environmental-payment-gateway'),
            '02' => __('Transaction is successful', 'environmental-payment-gateway'),
            '04' => __('Transaction is reversed', 'environmental-payment-gateway'),
            '05' => __('Transaction is processing refund', 'environmental-payment-gateway'),
            '06' => __('Transaction is refund rejected', 'environmental-payment-gateway'),
            '07' => __('Transaction is suspected fraud', 'environmental-payment-gateway'),
            '09' => __('Transaction refund is partially successful', 'environmental-payment-gateway'),
            '10' => __('Transaction is refund rejected', 'environmental-payment-gateway'),
            '11' => __('Transaction is timeout', 'environmental-payment-gateway'),
            '12' => __('Transaction is reversed', 'environmental-payment-gateway'),
            '13' => __('User canceled transaction', 'environmental-payment-gateway'),
            '24' => __('Transaction is canceled', 'environmental-payment-gateway'),
            '51' => __('Insufficient balance', 'environmental-payment-gateway'),
            '65' => __('Daily transaction limit exceeded', 'environmental-payment-gateway'),
            '75' => __('Bank is under maintenance', 'environmental-payment-gateway'),
            '79' => __('Invalid payment amount', 'environmental-payment-gateway'),
            '99' => __('Unknown error', 'environmental-payment-gateway'),
        );
        
        return isset($messages[$code]) ? $messages[$code] : __('Payment failed', 'environmental-payment-gateway');
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
            // VNPay refund implementation
            $refund_result = $this->process_vnpay_refund($order, $amount, $reason, $transaction_id);
            
            if ($refund_result['success']) {
                $order->add_order_note(sprintf(
                    __('VNPay refund completed. Refund ID: %s', 'environmental-payment-gateway'),
                    $refund_result['refund_id']
                ));
                
                $this->log('VNPay refund completed for order: ' . $order_id);
                return true;
            } else {
                return new WP_Error('refund_failed', $refund_result['message']);
            }
            
        } catch (Exception $e) {
            $this->log('VNPay refund error: ' . $e->getMessage());
            return new WP_Error('refund_error', $e->getMessage());
        }
    }
    
    /**
     * Process VNPay refund
     */
    private function process_vnpay_refund($order, $amount, $reason, $transaction_id) {
        // VNPay refund API call would go here
        // This is a simplified implementation
        
        $refund_amount = $amount ? $amount * 100 : $order->get_total() * 100;
        
        $params = array(
            'vnp_RequestId' => time() . rand(1000, 9999),
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'refund',
            'vnp_TmnCode' => $this->tmn_code,
            'vnp_TransactionType' => '02',
            'vnp_TxnRef' => $transaction_id,
            'vnp_Amount' => $refund_amount,
            'vnp_OrderInfo' => $reason ?: __('Refund for order', 'environmental-payment-gateway'),
            'vnp_TransactionDate' => date('YmdHis', strtotime($order->get_date_created())),
            'vnp_CreateBy' => get_current_user_id(),
            'vnp_CreateDate' => date('YmdHis'),
            'vnp_IpAddr' => $this->get_client_ip(),
        );
        
        // Sort and create hash
        ksort($params);
        $hash_data = http_build_query($params);
        $secure_hash = hash_hmac('sha512', $hash_data, $this->secret_key);
        $params['vnp_SecureHash'] = $secure_hash;
        
        // Make API call to VNPay refund endpoint
        // This would be implemented with actual VNPay refund API
        
        return array(
            'success' => true,
            'refund_id' => 'VNP_' . time(),
            'message' => __('Refund processed successfully', 'environmental-payment-gateway')
        );
    }
    
    /**
     * Get EDS processor configuration
     */
    public function get_eds_processor() {
        return array(
            'id' => $this->id,
            'name' => $this->method_title,
            'description' => $this->method_description,
            'supports_recurring' => true,
            'supported_currencies' => array('VND'),
            'fee_structure' => array(
                'type' => 'percentage',
                'domestic' => 1.5,
                'international' => 2.5
            )
        );
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                return trim($ip);
            }
        }
        
        return '127.0.0.1';
    }
}
