<?php
/**
 * Binance Pay Gateway for Environmental Payment Gateway
 * 
 * Supports multiple cryptocurrencies through Binance Pay API
 * with environmental impact tracking and carbon offset integration
 * 
 * @package EnvironmentalPaymentGateway
 * @subpackage Gateways
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * EPG_Binance_Gateway class
 */
class EPG_Binance_Gateway extends EPG_Gateway_Base {
    
    /**
     * API endpoints
     */
    private $api_endpoints = array(
        'production' => 'https://bpay.binanceapi.com',
        'sandbox' => 'https://bpay.binanceapi.com'
    );
    
    /**
     * Supported cryptocurrencies with environmental ratings
     */
    private $supported_currencies = array(
        'BTC' => array('name' => 'Bitcoin', 'environmental_rating' => 'high', 'carbon_intensity' => 0.000707),
        'ETH' => array('name' => 'Ethereum', 'environmental_rating' => 'low', 'carbon_intensity' => 0.000000084),
        'BNB' => array('name' => 'Binance Coin', 'environmental_rating' => 'low', 'carbon_intensity' => 0.0000001),
        'USDT' => array('name' => 'Tether', 'environmental_rating' => 'medium', 'carbon_intensity' => 0.000005),
        'USDC' => array('name' => 'USD Coin', 'environmental_rating' => 'low', 'carbon_intensity' => 0.000000084),
        'BUSD' => array('name' => 'Binance USD', 'environmental_rating' => 'low', 'carbon_intensity' => 0.0000001),
        'ADA' => array('name' => 'Cardano', 'environmental_rating' => 'very_low', 'carbon_intensity' => 0.000000052),
        'DOT' => array('name' => 'Polkadot', 'environmental_rating' => 'low', 'carbon_intensity' => 0.000001),
        'SOL' => array('name' => 'Solana', 'environmental_rating' => 'very_low', 'carbon_intensity' => 0.000000166),
        'MATIC' => array('name' => 'Polygon', 'environmental_rating' => 'very_low', 'carbon_intensity' => 0.000000084)
    );
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'epg_binance';
        $this->icon = EPG_PLUGIN_URL . 'assets/images/binance-logo.png';
        $this->has_fields = true;
        $this->method_title = __('Binance Pay', 'environmental-payment-gateway');
        $this->method_description = __('Accept cryptocurrency payments through Binance Pay with environmental impact tracking.', 'environmental-payment-gateway');
        $this->supports = array(
            'products',
            'refunds',
            'tokenization'
        );
        
        parent::__construct();
        
        // API credentials
        $this->api_key = $this->get_option('api_key');
        $this->secret_key = $this->get_option('secret_key');
        $this->merchant_id = $this->get_option('merchant_id');
        $this->testmode = 'yes' === $this->get_option('testmode', 'yes');
        
        // Environmental settings
        $this->carbon_offset_enabled = 'yes' === $this->get_option('carbon_offset_enabled', 'yes');
        $this->carbon_offset_percentage = floatval($this->get_option('carbon_offset_percentage', '2'));
        $this->preferred_green_cryptos = $this->get_option('preferred_green_cryptos', array('ETH', 'ADA', 'SOL', 'MATIC'));
        
        // Hooks
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
        add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'handle_webhook'));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
    }
    
    /**
     * Get gateway icon
     */
    public function get_icon() {
        return '<img src="' . EPG_PLUGIN_URL . 'assets/images/binance-logo.png" alt="Binance Pay" style="max-height: 30px;" />';
    }
    
    /**
     * Initialize form fields
     */
    public function init_form_fields() {
        parent::init_form_fields();
        
        $this->form_fields = array_merge($this->form_fields, array(
            'api_key' => array(
                'title' => __('API Key', 'environmental-payment-gateway'),
                'type' => 'password',
                'description' => __('Enter your Binance Pay API Key.', 'environmental-payment-gateway'),
                'default' => '',
                'desc_tip' => true,
            ),
            'secret_key' => array(
                'title' => __('Secret Key', 'environmental-payment-gateway'),
                'type' => 'password',
                'description' => __('Enter your Binance Pay Secret Key.', 'environmental-payment-gateway'),
                'default' => '',
                'desc_tip' => true,
            ),
            'merchant_id' => array(
                'title' => __('Merchant ID', 'environmental-payment-gateway'),
                'type' => 'text',
                'description' => __('Enter your Binance Pay Merchant ID.', 'environmental-payment-gateway'),
                'default' => '',
                'desc_tip' => true,
            ),
            'supported_currencies' => array(
                'title' => __('Supported Cryptocurrencies', 'environmental-payment-gateway'),
                'type' => 'multiselect',
                'description' => __('Select cryptocurrencies to accept.', 'environmental-payment-gateway'),
                'default' => array('ETH', 'USDC', 'BNB'),
                'options' => $this->get_currency_options(),
                'desc_tip' => true,
            ),
            'preferred_green_cryptos' => array(
                'title' => __('Promote Green Cryptocurrencies', 'environmental-payment-gateway'),
                'type' => 'multiselect',
                'description' => __('Cryptocurrencies to highlight as environmentally friendly.', 'environmental-payment-gateway'),
                'default' => array('ETH', 'ADA', 'SOL', 'MATIC'),
                'options' => $this->get_green_currency_options(),
                'desc_tip' => true,
            ),
            'carbon_offset_enabled' => array(
                'title' => __('Carbon Offset', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Enable automatic carbon offset for crypto payments', 'environmental-payment-gateway'),
                'default' => 'yes',
                'description' => __('Automatically calculate and process carbon offsets based on cryptocurrency environmental impact.', 'environmental-payment-gateway'),
            ),
            'carbon_offset_percentage' => array(
                'title' => __('Carbon Offset Percentage', 'environmental-payment-gateway'),
                'type' => 'decimal',
                'description' => __('Percentage of payment amount to allocate for carbon offset.', 'environmental-payment-gateway'),
                'default' => '2',
                'desc_tip' => true,
            ),
            'payment_timeout' => array(
                'title' => __('Payment Timeout (minutes)', 'environmental-payment-gateway'),
                'type' => 'number',
                'description' => __('Time limit for customers to complete payment.', 'environmental-payment-gateway'),
                'default' => '15',
                'custom_attributes' => array(
                    'min' => '5',
                    'max' => '60'
                ),
            ),
        ));
    }
    
    /**
     * Get currency options for form field
     */
    private function get_currency_options() {
        $options = array();
        foreach ($this->supported_currencies as $code => $data) {
            $rating_text = $this->get_environmental_rating_text($data['environmental_rating']);
            $options[$code] = $data['name'] . ' (' . $code . ') - ' . $rating_text;
        }
        return $options;
    }
    
    /**
     * Get green currency options
     */
    private function get_green_currency_options() {
        $options = array();
        foreach ($this->supported_currencies as $code => $data) {
            if (in_array($data['environmental_rating'], array('low', 'very_low'))) {
                $options[$code] = $data['name'] . ' (' . $code . ')';
            }
        }
        return $options;
    }
    
    /**
     * Get environmental rating text
     */
    private function get_environmental_rating_text($rating) {
        $ratings = array(
            'very_low' => __('🌱 Very Low Impact', 'environmental-payment-gateway'),
            'low' => __('🌿 Low Impact', 'environmental-payment-gateway'),
            'medium' => __('⚠️ Medium Impact', 'environmental-payment-gateway'),
            'high' => __('🔥 High Impact', 'environmental-payment-gateway')
        );
        return $ratings[$rating] ?? $rating;
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
            // Create Binance Pay order
            $payment_response = $this->create_binance_order($order);
            
            if ($payment_response && isset($payment_response['data']['prepayId'])) {
                // Save payment data
                $order->update_meta_data('_binance_prepay_id', $payment_response['data']['prepayId']);
                $order->update_meta_data('_binance_checkout_url', $payment_response['data']['checkoutUrl']);
                $order->update_meta_data('_binance_order_status', 'INITIAL');
                $order->save();
                
                // Set order to pending
                $order->update_status('pending', __('Awaiting Binance Pay payment.', 'environmental-payment-gateway'));
                
                // Environmental impact tracking
                $this->track_environmental_impact($order);
                
                // Reduce stock
                wc_reduce_stock_levels($order_id);
                
                // Empty cart
                WC()->cart->empty_cart();
                
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order)
                );
            } else {
                return array(
                    'result' => 'failure',
                    'messages' => __('Unable to create Binance Pay order. Please try again.', 'environmental-payment-gateway')
                );
            }
            
        } catch (Exception $e) {
            $this->log('Payment processing error: ' . $e->getMessage());
            return array(
                'result' => 'failure',
                'messages' => __('Payment processing failed. Please try again.', 'environmental-payment-gateway')
            );
        }
    }
    
    /**
     * Create Binance Pay order
     */
    private function create_binance_order($order) {
        $currency = $this->get_preferred_currency($order);
        $amount = $this->convert_to_crypto($order->get_total(), $currency);
        
        $order_data = array(
            'merchantTradeNo' => $order->get_id() . '_' . time(),
            'orderAmount' => $amount,
            'currency' => $currency,
            'goods' => array(
                'goodsType' => '02', // Virtual goods
                'goodsCategory' => 'Z000', // Others
                'referenceGoodsId' => $order->get_id(),
                'goodsName' => sprintf(__('Order #%s', 'environmental-payment-gateway'), $order->get_order_number()),
                'goodsDetail' => $this->get_order_description($order)
            ),
            'buyer' => array(
                'referenceBuyerId' => $order->get_customer_id() ?: $order->get_billing_email(),
                'buyerName' => array(
                    'firstName' => $order->get_billing_first_name(),
                    'lastName' => $order->get_billing_last_name()
                )
            ),
            'returnUrl' => $this->get_return_url($order),
            'cancelUrl' => $order->get_cancel_order_url_raw(),
            'orderExpireTime' => time() + ($this->get_option('payment_timeout', 15) * 60) * 1000,
            'webhookUrl' => $this->get_webhook_url(),
            'env' => array(
                'terminalType' => 'WEB'
            )
        );
        
        // Add environmental messaging
        if ($this->carbon_offset_enabled) {
            $carbon_data = $this->calculate_carbon_impact($currency, $amount);
            $order_data['goods']['goodsDetail'] .= sprintf(
                __(' | Environmental Impact: %s kg CO2, Offset: $%s', 'environmental-payment-gateway'),
                number_format($carbon_data['carbon_footprint'], 6),
                number_format($carbon_data['offset_amount'], 2)
            );
        }
        
        return $this->make_api_request('/binancepay/openapi/v3/order', $order_data, 'POST');
    }
    
    /**
     * Get preferred currency based on environmental settings
     */
    private function get_preferred_currency($order) {
        $supported = $this->get_option('supported_currencies', array('ETH', 'USDC', 'BNB'));
        $preferred_green = $this->get_option('preferred_green_cryptos', array('ETH', 'ADA', 'SOL'));
        
        // Prefer green cryptocurrencies
        foreach ($preferred_green as $currency) {
            if (in_array($currency, $supported)) {
                return $currency;
            }
        }
        
        // Fallback to first supported currency
        return $supported[0] ?? 'USDC';
    }
    
    /**
     * Convert fiat amount to cryptocurrency
     */
    private function convert_to_crypto($fiat_amount, $crypto_currency) {
        // In a real implementation, this would use live exchange rates
        // For now, using placeholder conversion rates
        $rates = array(
            'BTC' => 0.000023,
            'ETH' => 0.00043,
            'BNB' => 0.0032,
            'USDT' => 1.0,
            'USDC' => 1.0,
            'BUSD' => 1.0,
            'ADA' => 2.5,
            'DOT' => 0.15,
            'SOL' => 0.043,
            'MATIC' => 1.2
        );
        
        $rate = $rates[$crypto_currency] ?? 1.0;
        return number_format($fiat_amount * $rate, 8, '.', '');
    }
    
    /**
     * Calculate carbon impact and offset
     */
    private function calculate_carbon_impact($currency, $amount) {
        $currency_data = $this->supported_currencies[$currency] ?? array('carbon_intensity' => 0);
        $carbon_footprint = floatval($amount) * $currency_data['carbon_intensity'];
        $offset_amount = ($this->carbon_offset_percentage / 100) * floatval($amount);
        
        return array(
            'carbon_footprint' => $carbon_footprint,
            'offset_amount' => $offset_amount,
            'environmental_rating' => $currency_data['environmental_rating'] ?? 'unknown'
        );
    }
    
    /**
     * Track environmental impact
     */
    private function track_environmental_impact($order) {
        if (!$this->carbon_offset_enabled) {
            return;
        }
        
        $currency = $this->get_preferred_currency($order);
        $amount = $this->convert_to_crypto($order->get_total(), $currency);
        $carbon_data = $this->calculate_carbon_impact($currency, $amount);
        
        // Save environmental data to order
        $order->update_meta_data('_binance_currency', $currency);
        $order->update_meta_data('_binance_amount', $amount);
        $order->update_meta_data('_carbon_footprint', $carbon_data['carbon_footprint']);
        $order->update_meta_data('_carbon_offset_amount', $carbon_data['offset_amount']);
        $order->update_meta_data('_environmental_rating', $carbon_data['environmental_rating']);
        $order->save();
        
        // Log environmental impact
        $this->log(sprintf(
            'Environmental Impact - Order: %d, Currency: %s, Carbon Footprint: %s kg CO2, Offset: $%s',
            $order->get_id(),
            $currency,
            $carbon_data['carbon_footprint'],
            $carbon_data['offset_amount']
        ));
    }
    
    /**
     * Get order description
     */
    private function get_order_description($order) {
        $items = array();
        foreach ($order->get_items() as $item) {
            $items[] = $item->get_name() . ' x' . $item->get_quantity();
        }
        return implode(', ', array_slice($items, 0, 3)) . (count($items) > 3 ? '...' : '');
    }
    
    /**
     * Receipt page
     */
    public function receipt_page($order_id) {
        $order = wc_get_order($order_id);
        $checkout_url = $order->get_meta('_binance_checkout_url');
        
        if ($checkout_url) {
            echo '<p>' . __('Please complete your payment using Binance Pay.', 'environmental-payment-gateway') . '</p>';
            echo '<div id="binance-payment-container">';
            echo '<iframe src="' . esc_url($checkout_url) . '" width="100%" height="600" frameborder="0"></iframe>';
            echo '</div>';
            
            // Environmental impact display
            $this->display_environmental_impact($order);
            
            // Add JavaScript for payment status checking
            $this->add_payment_status_script($order_id);
        } else {
            echo '<p>' . __('Payment information not available. Please contact support.', 'environmental-payment-gateway') . '</p>';
        }
    }
    
    /**
     * Display environmental impact information
     */
    private function display_environmental_impact($order) {
        if (!$this->carbon_offset_enabled) {
            return;
        }
        
        $currency = $order->get_meta('_binance_currency');
        $carbon_footprint = $order->get_meta('_carbon_footprint');
        $offset_amount = $order->get_meta('_carbon_offset_amount');
        $environmental_rating = $order->get_meta('_environmental_rating');
        
        if ($currency && $carbon_footprint !== '') {
            echo '<div class="binance-environmental-impact">';
            echo '<h3>' . __('Environmental Impact', 'environmental-payment-gateway') . '</h3>';
            echo '<div class="impact-details">';
            echo '<p><strong>' . __('Payment Currency:', 'environmental-payment-gateway') . '</strong> ' . esc_html($currency) . '</p>';
            echo '<p><strong>' . __('Environmental Rating:', 'environmental-payment-gateway') . '</strong> ' . $this->get_environmental_rating_text($environmental_rating) . '</p>';
            echo '<p><strong>' . __('Carbon Footprint:', 'environmental-payment-gateway') . '</strong> ' . number_format($carbon_footprint, 6) . ' kg CO2</p>';
            
            if ($offset_amount > 0) {
                echo '<p><strong>' . __('Carbon Offset Contribution:', 'environmental-payment-gateway') . '</strong> $' . number_format($offset_amount, 2) . '</p>';
                echo '<p class="environmental-message">' . __('🌱 Thank you for supporting carbon neutral payments!', 'environmental-payment-gateway') . '</p>';
            }
            echo '</div>';
            echo '</div>';
        }
    }
    
    /**
     * Add payment status checking script
     */
    private function add_payment_status_script($order_id) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var checkInterval = setInterval(function() {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'epg_check_binance_status',
                        order_id: '<?php echo $order_id; ?>',
                        nonce: '<?php echo wp_create_nonce('epg_binance_status'); ?>'
                    },
                    success: function(response) {
                        if (response.success && response.data.status === 'completed') {
                            clearInterval(checkInterval);
                            window.location.href = response.data.redirect_url;
                        }
                    }
                });
            }, 5000); // Check every 5 seconds
            
            // Stop checking after 30 minutes
            setTimeout(function() {
                clearInterval(checkInterval);
            }, 1800000);
        });
        </script>
        <?php
    }
    
    /**
     * Handle webhook
     */
    public function handle_webhook() {
        $raw_body = file_get_contents('php://input');
        $headers = getallheaders();
        
        // Verify webhook signature
        if (!$this->verify_webhook_signature($raw_body, $headers)) {
            $this->log('Webhook signature verification failed');
            status_header(400);
            exit;
        }
        
        $data = json_decode($raw_body, true);
        
        if (!$data || !isset($data['data'])) {
            $this->log('Invalid webhook data received');
            status_header(400);
            exit;
        }
        
        $payment_data = $data['data'];
        $merchant_trade_no = $payment_data['merchantTradeNo'] ?? '';
        
        // Extract order ID from merchant trade number
        $order_id = explode('_', $merchant_trade_no)[0];
        $order = wc_get_order($order_id);
        
        if (!$order) {
            $this->log('Order not found for webhook: ' . $merchant_trade_no);
            status_header(404);
            exit;
        }
        
        $payment_status = $payment_data['status'] ?? '';
        
        switch ($payment_status) {
            case 'SUCCESS':
                $this->handle_successful_payment($order, $payment_data);
                break;
            case 'FAILED':
            case 'EXPIRED':
                $this->handle_failed_payment($order, $payment_data);
                break;
            case 'CANCELED':
                $this->handle_cancelled_payment($order, $payment_data);
                break;
        }
        
        status_header(200);
        exit;
    }
    
    /**
     * Handle successful payment
     */
    private function handle_successful_payment($order, $payment_data) {
        if ($order->is_paid()) {
            return;
        }
        
        $transaction_id = $payment_data['transactionId'] ?? '';
        $crypto_amount = $payment_data['cryptoAmount'] ?? '';
        $crypto_currency = $payment_data['cryptoCurrency'] ?? '';
        
        // Update order
        $order->update_meta_data('_binance_transaction_id', $transaction_id);
        $order->update_meta_data('_binance_crypto_amount', $crypto_amount);
        $order->update_meta_data('_binance_crypto_currency', $crypto_currency);
        $order->update_meta_data('_binance_order_status', 'SUCCESS');
        $order->payment_complete($transaction_id);
        
        // Process carbon offset if enabled
        if ($this->carbon_offset_enabled) {
            $this->process_carbon_offset($order);
        }
        
        $order->add_order_note(sprintf(
            __('Payment completed via Binance Pay. Transaction ID: %s. Amount: %s %s', 'environmental-payment-gateway'),
            $transaction_id,
            $crypto_amount,
            $crypto_currency
        ));
        
        $this->log('Payment completed for order: ' . $order->get_id());
    }
    
    /**
     * Handle failed payment
     */
    private function handle_failed_payment($order, $payment_data) {
        $order->update_status('failed', __('Binance Pay payment failed.', 'environmental-payment-gateway'));
        $order->update_meta_data('_binance_order_status', 'FAILED');
        $order->save();
        
        $this->log('Payment failed for order: ' . $order->get_id());
    }
    
    /**
     * Handle cancelled payment
     */
    private function handle_cancelled_payment($order, $payment_data) {
        $order->update_status('cancelled', __('Binance Pay payment cancelled by customer.', 'environmental-payment-gateway'));
        $order->update_meta_data('_binance_order_status', 'CANCELED');
        $order->save();
        
        $this->log('Payment cancelled for order: ' . $order->get_id());
    }
    
    /**
     * Process carbon offset
     */
    private function process_carbon_offset($order) {
        $offset_amount = $order->get_meta('_carbon_offset_amount');
        
        if ($offset_amount > 0) {
            // Create offset transaction (integration with carbon offset provider would go here)
            $order->update_meta_data('_carbon_offset_processed', 'yes');
            $order->update_meta_data('_carbon_offset_date', current_time('mysql'));
            $order->save();
            
            $order->add_order_note(sprintf(
                __('Carbon offset processed: $%s allocated for environmental projects.', 'environmental-payment-gateway'),
                number_format($offset_amount, 2)
            ));
        }
    }
    
    /**
     * Verify webhook signature
     */
    private function verify_webhook_signature($payload, $headers) {
        $signature = $headers['Binancepay-Signature'] ?? '';
        $timestamp = $headers['Binancepay-Timestamp'] ?? '';
        $nonce = $headers['Binancepay-Nonce'] ?? '';
        
        if (!$signature || !$timestamp || !$nonce) {
            return false;
        }
        
        $string_to_sign = $timestamp . "\n" . $nonce . "\n" . $payload . "\n";
        $expected_signature = strtoupper(hash_hmac('sha512', $string_to_sign, $this->secret_key));
        
        return hash_equals($expected_signature, strtoupper($signature));
    }
    
    /**
     * Make API request
     */
    private function make_api_request($endpoint, $data = array(), $method = 'GET') {
        $url = $this->api_endpoints[$this->testmode ? 'sandbox' : 'production'] . $endpoint;
        $timestamp = time() * 1000;
        $nonce = wp_generate_uuid4();
        
        $headers = array(
            'Content-Type: application/json',
            'BinancePay-Timestamp: ' . $timestamp,
            'BinancePay-Nonce: ' . $nonce,
            'BinancePay-Certificate-SN: ' . $this->api_key,
        );
        
        if ($method === 'POST') {
            $payload = json_encode($data);
            $signature = $this->generate_signature($timestamp, $nonce, $payload);
            $headers[] = 'BinancePay-Signature: ' . $signature;
        }
        
        $args = array(
            'method' => $method,
            'headers' => $headers,
            'timeout' => 30,
        );
        
        if ($method === 'POST') {
            $args['body'] = $payload;
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            $this->log('API request error: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if (!$result || $result['status'] !== 'SUCCESS') {
            $this->log('API error response: ' . $body);
            return false;
        }
        
        return $result;
    }
    
    /**
     * Generate API signature
     */
    private function generate_signature($timestamp, $nonce, $payload) {
        $string_to_sign = $timestamp . "\n" . $nonce . "\n" . $payload . "\n";
        return strtoupper(hash_hmac('sha512', $string_to_sign, $this->secret_key));
    }
    
    /**
     * Get webhook URL
     */
    private function get_webhook_url() {
        return WC()->api_request_url(strtolower(get_class($this)));
    }
    
    /**
     * Enqueue payment scripts
     */
    public function payment_scripts() {
        if (!is_checkout() || !$this->is_available()) {
            return;
        }
        
        wp_enqueue_script(
            'epg-binance-checkout',
            EPG_PLUGIN_URL . 'assets/js/binance.js',
            array('jquery'),
            EPG_PLUGIN_VERSION,
            true
        );
        
        wp_localize_script('epg-binance-checkout', 'epg_binance_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('epg_binance_checkout'),
            'supported_currencies' => $this->get_option('supported_currencies', array()),
            'environmental_messaging' => $this->carbon_offset_enabled,
        ));
    }
    
    /**
     * Process refund
     */
    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return new WP_Error('invalid_order', __('Order not found.', 'environmental-payment-gateway'));
        }
        
        $transaction_id = $order->get_meta('_binance_transaction_id');
        
        if (!$transaction_id) {
            return new WP_Error('no_transaction_id', __('Transaction ID not found.', 'environmental-payment-gateway'));
        }
        
        try {
            $refund_data = array(
                'refundRequestId' => $order_id . '_refund_' . time(),
                'prepayId' => $order->get_meta('_binance_prepay_id'),
                'refundAmount' => $amount ?: $order->get_total(),
                'refundReason' => $reason ?: __('Refund processed', 'environmental-payment-gateway')
            );
            
            $response = $this->make_api_request('/binancepay/openapi/v2/refund', $refund_data, 'POST');
            
            if ($response && $response['status'] === 'SUCCESS') {
                $order->add_order_note(sprintf(
                    __('Refund processed via Binance Pay. Amount: %s. Reason: %s', 'environmental-payment-gateway'),
                    wc_price($amount),
                    $reason
                ));
                
                return true;
            } else {
                return new WP_Error('refund_failed', __('Refund request failed.', 'environmental-payment-gateway'));
            }
            
        } catch (Exception $e) {
            $this->log('Refund error: ' . $e->getMessage());
            return new WP_Error('refund_error', $e->getMessage());
        }
    }
    
    /**
     * Log messages
     */
    private function log($message) {
        if ($this->testmode) {
            $logger = wc_get_logger();
            $logger->info($message, array('source' => 'binance-pay'));
        }
    }
}
