<?php
/**
 * Bitcoin Gateway
 * 
 * Bitcoin payment gateway with HD wallet support, QR code generation,
 * transaction monitoring, and environmental impact tracking.
 * 
 * @package EnvironmentalPaymentGateway
 * @subpackage Gateways\Crypto
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * EPG Bitcoin Gateway Class
 */
class EPG_Bitcoin_Gateway extends EPG_Gateway_Base {
    
    /**
     * Gateway ID
     */
    public $id = 'bitcoin';
    
    /**
     * Gateway method title
     */
    public $method_title = 'Bitcoin';
    
    /**
     * Gateway method description
     */
    public $method_description = 'Accept Bitcoin payments with environmental impact tracking';
    
    /**
     * Supported features
     */
    public $supports = array(
        'products'
    );
    
    /**
     * Bitcoin network
     */
    private $network = 'mainnet'; // or 'testnet'
    
    /**
     * Blockchain API endpoints
     */
    private $blockchain_apis = array(
        'mainnet' => array(
            'blockstream' => 'https://blockstream.info/api',
            'blockcypher' => 'https://api.blockcypher.com/v1/btc/main',
            'blockchair' => 'https://api.blockchair.com/bitcoin'
        ),
        'testnet' => array(
            'blockstream' => 'https://blockstream.info/testnet/api',
            'blockcypher' => 'https://api.blockcypher.com/v1/btc/test3',
            'blockchair' => 'https://api.blockchair.com/bitcoin/testnet'
        )
    );
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        $this->title = __('Bitcoin', 'environmental-payment-gateway');
        $this->description = __('Pay with Bitcoin. Environmental impact tracked and offset automatically.', 'environmental-payment-gateway');
        $this->icon = EPG_PLUGIN_URL . 'assets/images/bitcoin-logo.png';
        
        // Set network based on environment
        $this->network = $this->is_testnet() ? 'testnet' : 'mainnet';
        
        // Initialize settings
        $this->init_form_fields();
        $this->init_settings();
        
        // Load settings
        $this->enabled = $this->get_option('enabled');
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        
        // Hooks
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Bitcoin-specific hooks
        add_action('wp_ajax_epg_bitcoin_check_payment', array($this, 'ajax_check_payment'));
        add_action('wp_ajax_nopriv_epg_bitcoin_check_payment', array($this, 'ajax_check_payment'));
        add_action('epg_bitcoin_payment_monitor', array($this, 'monitor_payments'));
        
        // Schedule payment monitoring
        if (!wp_next_scheduled('epg_bitcoin_payment_monitor')) {
            wp_schedule_event(time(), 'every_minute', 'epg_bitcoin_payment_monitor');
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
                'label' => __('Enable Bitcoin Gateway', 'environmental-payment-gateway'),
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'environmental-payment-gateway'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'environmental-payment-gateway'),
                'default' => __('Bitcoin', 'environmental-payment-gateway'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'environmental-payment-gateway'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'environmental-payment-gateway'),
                'default' => __('Pay with Bitcoin. Your transaction carbon footprint will be automatically offset.', 'environmental-payment-gateway'),
            ),
            'network' => array(
                'title' => __('Network', 'environmental-payment-gateway'),
                'type' => 'select',
                'description' => __('Select Bitcoin network.', 'environmental-payment-gateway'),
                'default' => 'mainnet',
                'desc_tip' => true,
                'options' => array(
                    'mainnet' => __('Mainnet (Live)', 'environmental-payment-gateway'),
                    'testnet' => __('Testnet (Testing)', 'environmental-payment-gateway'),
                )
            ),
            'wallet_settings' => array(
                'title' => __('Wallet Settings', 'environmental-payment-gateway'),
                'type' => 'title',
                'description' => '',
            ),
            'master_public_key' => array(
                'title' => __('Master Public Key (xpub)', 'environmental-payment-gateway'),
                'type' => 'textarea',
                'description' => __('Enter your Bitcoin master public key for address generation.', 'environmental-payment-gateway'),
                'default' => '',
                'desc_tip' => true,
            ),
            'address_gap_limit' => array(
                'title' => __('Address Gap Limit', 'environmental-payment-gateway'),
                'type' => 'number',
                'description' => __('Number of unused addresses to generate ahead.', 'environmental-payment-gateway'),
                'default' => '20',
                'custom_attributes' => array(
                    'min' => '5',
                    'max' => '100'
                )
            ),
            'payment_settings' => array(
                'title' => __('Payment Settings', 'environmental-payment-gateway'),
                'type' => 'title',
                'description' => '',
            ),
            'confirmation_blocks' => array(
                'title' => __('Required Confirmations', 'environmental-payment-gateway'),
                'type' => 'number',
                'description' => __('Number of confirmations required before payment is complete.', 'environmental-payment-gateway'),
                'default' => '1',
                'custom_attributes' => array(
                    'min' => '0',
                    'max' => '6'
                )
            ),
            'payment_timeout' => array(
                'title' => __('Payment Timeout (minutes)', 'environmental-payment-gateway'),
                'type' => 'number',
                'description' => __('Time limit for customers to complete Bitcoin payment.', 'environmental-payment-gateway'),
                'default' => '30',
                'custom_attributes' => array(
                    'min' => '10',
                    'max' => '120'
                )
            ),
            'fee_strategy' => array(
                'title' => __('Fee Strategy', 'environmental-payment-gateway'),
                'type' => 'select',
                'description' => __('How to handle Bitcoin network fees.', 'environmental-payment-gateway'),
                'default' => 'customer_pays',
                'options' => array(
                    'customer_pays' => __('Customer pays network fees', 'environmental-payment-gateway'),
                    'merchant_absorbs' => __('Merchant absorbs fees', 'environmental-payment-gateway'),
                    'shared' => __('Shared between customer and merchant', 'environmental-payment-gateway')
                )
            ),
            'environmental_settings' => array(
                'title' => __('Environmental Settings', 'environmental-payment-gateway'),
                'type' => 'title',
                'description' => '',
            ),
            'carbon_offset_enabled' => array(
                'title' => __('Carbon Offset', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Automatically offset Bitcoin transaction carbon footprint', 'environmental-payment-gateway'),
                'default' => 'yes',
                'description' => __('Calculates and offsets the estimated energy consumption of Bitcoin transactions.', 'environmental-payment-gateway')
            ),
            'green_mining_preference' => array(
                'title' => __('Green Mining Preference', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Show preference for green Bitcoin mining pools', 'environmental-payment-gateway'),
                'default' => 'yes'
            ),
            'energy_efficiency_info' => array(
                'title' => __('Show Energy Info', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Display Bitcoin energy consumption information to customers', 'environmental-payment-gateway'),
                'default' => 'yes'
            ),
            'lightning_settings' => array(
                'title' => __('Lightning Network', 'environmental-payment-gateway'),
                'type' => 'title',
                'description' => '',
            ),
            'enable_lightning' => array(
                'title' => __('Enable Lightning Network', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Accept Lightning Network payments (lower energy consumption)', 'environmental-payment-gateway'),
                'default' => 'no'
            ),
            'lightning_node_url' => array(
                'title' => __('Lightning Node URL', 'environmental-payment-gateway'),
                'type' => 'url',
                'description' => __('URL of your Lightning Network node.', 'environmental-payment-gateway'),
                'default' => ''
            )
        );
    }
    
    /**
     * Process payment
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        
        try {
            // Get Bitcoin price
            $btc_price = $this->get_bitcoin_price($order->get_currency());
            if (!$btc_price) {
                throw new Exception(__('Unable to get Bitcoin exchange rate', 'environmental-payment-gateway'));
            }
            
            // Calculate BTC amount
            $btc_amount = $order->get_total() / $btc_price;
            
            // Generate payment address
            $payment_address = $this->generate_payment_address($order);
            if (!$payment_address) {
                throw new Exception(__('Unable to generate Bitcoin address', 'environmental-payment-gateway'));
            }
            
            // Store payment details
            $order->update_meta_data('_bitcoin_address', $payment_address);
            $order->update_meta_data('_bitcoin_amount', $btc_amount);
            $order->update_meta_data('_bitcoin_price', $btc_price);
            $order->update_meta_data('_bitcoin_payment_timeout', time() + ($this->get_option('payment_timeout', 30) * 60));
            $order->update_meta_data('_bitcoin_confirmations_required', $this->get_option('confirmation_blocks', 1));
            $order->save();
            
            // Set order status to pending payment
            $order->update_status('pending', __('Awaiting Bitcoin payment', 'environmental-payment-gateway'));
            
            // Calculate and store carbon footprint
            $this->calculate_carbon_footprint($order, $btc_amount);
            
            // Redirect to payment page
            return array(
                'result' => 'success',
                'redirect' => add_query_arg('bitcoin_payment', $order->get_id(), $this->get_return_url($order))
            );
            
        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            $this->log_error('Bitcoin payment processing failed: ' . $e->getMessage());
            return array('result' => 'fail');
        }
    }
    
    /**
     * Get Bitcoin price in specified currency
     */
    private function get_bitcoin_price($currency = 'USD') {
        // Try multiple price APIs for reliability
        $apis = array(
            'coingecko' => "https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies={$currency}",
            'coinbase' => "https://api.coinbase.com/v2/exchange-rates?currency=BTC",
            'binance' => "https://api.binance.com/api/v3/ticker/price?symbol=BTC{$currency}"
        );
        
        foreach ($apis as $api_name => $url) {
            $response = wp_remote_get($url, array('timeout' => 10));
            
            if (!is_wp_error($response)) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                
                switch ($api_name) {
                    case 'coingecko':
                        if (isset($body['bitcoin'][strtolower($currency)])) {
                            return floatval($body['bitcoin'][strtolower($currency)]);
                        }
                        break;
                        
                    case 'coinbase':
                        if (isset($body['data']['rates'][$currency])) {
                            return floatval($body['data']['rates'][$currency]);
                        }
                        break;
                        
                    case 'binance':
                        if (isset($body['price'])) {
                            return floatval($body['price']);
                        }
                        break;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Generate payment address for order
     */
    private function generate_payment_address($order) {
        $master_public_key = $this->get_option('master_public_key');
        
        if (empty($master_public_key)) {
            // Fallback to static address if xpub not provided
            return $this->get_option('static_address');
        }
        
        // Get next available address index
        $address_index = $this->get_next_address_index();
        
        // Generate address from master public key
        // This is a simplified version - in production you'd use proper BIP32 libraries
        $address = $this->derive_address_from_xpub($master_public_key, $address_index);
        
        if ($address) {
            // Store address mapping
            $this->store_address_mapping($address, $order->get_id(), $address_index);
            return $address;
        }
        
        return false;
    }
    
    /**
     * Derive address from extended public key
     */
    private function derive_address_from_xpub($xpub, $index) {
        // This is a placeholder - in production you would use proper cryptographic libraries
        // like BitWasp/bitcoin-php or similar to properly derive addresses
        
        // For demonstration, we'll generate a mock address
        $hash = hash('sha256', $xpub . $index . $this->network);
        
        if ($this->network === 'testnet') {
            return 'tb1' . substr($hash, 0, 39); // Bech32 testnet address
        } else {
            return 'bc1' . substr($hash, 0, 39); // Bech32 mainnet address
        }
    }
    
    /**
     * Get next available address index
     */
    private function get_next_address_index() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'epg_bitcoin_addresses';
        
        $max_index = $wpdb->get_var(
            "SELECT MAX(address_index) FROM {$table_name} WHERE network = %s",
            $this->network
        );
        
        return intval($max_index) + 1;
    }
    
    /**
     * Store address mapping
     */
    private function store_address_mapping($address, $order_id, $index) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'epg_bitcoin_addresses';
        
        $wpdb->insert(
            $table_name,
            array(
                'address' => $address,
                'order_id' => $order_id,
                'address_index' => $index,
                'network' => $this->network,
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%d', '%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Monitor payments
     */
    public function monitor_payments() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'epg_bitcoin_addresses';
        
        // Get pending payments
        $pending_payments = $wpdb->get_results(
            "SELECT * FROM {$table_name} WHERE status = 'pending' AND network = %s",
            ARRAY_A,
            $this->network
        );
        
        foreach ($pending_payments as $payment) {
            $this->check_address_payment($payment);
        }
    }
    
    /**
     * Check payment for specific address
     */
    private function check_address_payment($payment_data) {
        $address = $payment_data['address'];
        $order_id = $payment_data['order_id'];
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        // Check if payment timeout has passed
        $timeout = $order->get_meta('_bitcoin_payment_timeout');
        if ($timeout && time() > $timeout) {
            $order->update_status('cancelled', __('Bitcoin payment timeout expired', 'environmental-payment-gateway'));
            $this->update_address_status($address, 'expired');
            return;
        }
        
        // Check blockchain for transactions
        $transactions = $this->get_address_transactions($address);
        
        if (!empty($transactions)) {
            $expected_amount = floatval($order->get_meta('_bitcoin_amount'));
            $required_confirmations = intval($order->get_meta('_bitcoin_confirmations_required'));
            
            foreach ($transactions as $tx) {
                if ($tx['amount'] >= $expected_amount && $tx['confirmations'] >= $required_confirmations) {
                    // Payment confirmed
                    $order->payment_complete($tx['txid']);
                    $order->add_order_note(
                        sprintf(__('Bitcoin payment received. Transaction: %s', 'environmental-payment-gateway'), $tx['txid'])
                    );
                    
                    $this->update_address_status($address, 'completed');
                    
                    // Process carbon offset if enabled
                    if ($this->get_option('carbon_offset_enabled') === 'yes') {
                        $this->process_carbon_offset($order);
                    }
                    
                    break;
                }
            }
        }
    }
    
    /**
     * Get transactions for address
     */
    private function get_address_transactions($address) {
        $api_url = $this->blockchain_apis[$this->network]['blockstream'] . "/address/{$address}/txs";
        
        $response = wp_remote_get($api_url, array('timeout' => 15));
        
        if (is_wp_error($response)) {
            return array();
        }
        
        $transactions = json_decode(wp_remote_retrieve_body($response), true);
        $processed_txs = array();
        
        if (is_array($transactions)) {
            foreach ($transactions as $tx) {
                // Find outputs to our address
                foreach ($tx['vout'] as $output) {
                    if (isset($output['scriptpubkey_address']) && $output['scriptpubkey_address'] === $address) {
                        $processed_txs[] = array(
                            'txid' => $tx['txid'],
                            'amount' => $output['value'] / 100000000, // Convert satoshis to BTC
                            'confirmations' => isset($tx['status']['confirmed']) && $tx['status']['confirmed'] ? 
                                ($tx['status']['block_height'] ? $this->get_current_block_height() - $tx['status']['block_height'] + 1 : 0) : 0
                        );
                    }
                }
            }
        }
        
        return $processed_txs;
    }
    
    /**
     * Get current block height
     */
    private function get_current_block_height() {
        $api_url = $this->blockchain_apis[$this->network]['blockstream'] . "/blocks/tip/height";
        
        $response = wp_remote_get($api_url, array('timeout' => 10));
        
        if (!is_wp_error($response)) {
            return intval(wp_remote_retrieve_body($response));
        }
        
        return 0;
    }
    
    /**
     * Update address status
     */
    private function update_address_status($address, $status) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'epg_bitcoin_addresses';
        
        $wpdb->update(
            $table_name,
            array('status' => $status, 'updated_at' => current_time('mysql')),
            array('address' => $address),
            array('%s', '%s'),
            array('%s')
        );
    }
    
    /**
     * Calculate carbon footprint
     */
    private function calculate_carbon_footprint($order, $btc_amount) {
        // Bitcoin network energy consumption estimates
        // These are approximate values and should be updated with real-time data
        $kwh_per_transaction = 700; // kWh per transaction (average estimate)
        $co2_per_kwh = 0.5; // kg CO2 per kWh (global average)
        
        $carbon_footprint = $kwh_per_transaction * $co2_per_kwh; // kg CO2
        
        // Store carbon footprint data
        $order->update_meta_data('_bitcoin_carbon_footprint_kg', $carbon_footprint);
        $order->update_meta_data('_bitcoin_energy_consumption_kwh', $kwh_per_transaction);
        $order->save();
        
        return $carbon_footprint;
    }
    
    /**
     * Process carbon offset
     */
    private function process_carbon_offset($order) {
        $carbon_footprint = floatval($order->get_meta('_bitcoin_carbon_footprint_kg'));
        
        if ($carbon_footprint > 0) {
            // Calculate offset cost (example: $20 per ton of CO2)
            $offset_cost_per_kg = 0.02; // $0.02 per kg CO2
            $offset_amount = $carbon_footprint * $offset_cost_per_kg;
            
            // Store offset data
            $order->update_meta_data('_bitcoin_carbon_offset_amount', $offset_amount);
            $order->update_meta_data('_bitcoin_carbon_offset_processed', 'yes');
            $order->save();
            
            // Trigger carbon offset action
            do_action('epg_carbon_offset_donation', $order->get_id(), $offset_amount);
            
            $order->add_order_note(
                sprintf(__('Bitcoin carbon footprint offset: %.2f kg CO2, Cost: %s %s', 'environmental-payment-gateway'), 
                    $carbon_footprint,
                    $offset_amount,
                    $order->get_currency()
                )
            );
        }
    }
    
    /**
     * AJAX check payment status
     */
    public function ajax_check_payment() {
        check_ajax_referer('epg_bitcoin_nonce', 'nonce');
        
        $order_id = intval($_POST['order_id']);
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wp_die('Invalid order');
        }
        
        $address = $order->get_meta('_bitcoin_address');
        $expected_amount = floatval($order->get_meta('_bitcoin_amount'));
        
        $transactions = $this->get_address_transactions($address);
        $payment_found = false;
        
        foreach ($transactions as $tx) {
            if ($tx['amount'] >= $expected_amount) {
                $payment_found = true;
                wp_send_json_success(array(
                    'payment_found' => true,
                    'confirmations' => $tx['confirmations'],
                    'txid' => $tx['txid']
                ));
            }
        }
        
        if (!$payment_found) {
            wp_send_json_success(array(
                'payment_found' => false,
                'timeout' => $order->get_meta('_bitcoin_payment_timeout')
            ));
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
            'epg-bitcoin',
            EPG_PLUGIN_URL . 'assets/js/bitcoin.js',
            array('jquery'),
            EPG_PLUGIN_VERSION,
            true
        );
        
        wp_localize_script('epg-bitcoin', 'epg_bitcoin_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('epg_bitcoin_nonce'),
            'network' => $this->network,
            'confirmation_blocks' => $this->get_option('confirmation_blocks', 1),
            'show_energy_info' => $this->get_option('energy_efficiency_info') === 'yes'
        ));
        
        // Include QR code library
        wp_enqueue_script(
            'qrcode-js',
            'https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js',
            array(),
            '1.5.3',
            true
        );
    }
    
    /**
     * Check if using testnet
     */
    private function is_testnet() {
        return $this->get_option('network') === 'testnet';
    }
    
    /**
     * Get EDS processor configuration
     */
    public function get_eds_processor() {
        return array(
            'id' => $this->id,
            'name' => $this->method_title,
            'description' => __('Bitcoin payments with carbon footprint tracking and offset', 'environmental-payment-gateway'),
            'environmental_features' => array(
                'carbon_tracking' => true,
                'carbon_offset' => $this->get_option('carbon_offset_enabled') === 'yes',
                'energy_transparency' => $this->get_option('energy_efficiency_info') === 'yes',
                'green_mining_support' => $this->get_option('green_mining_preference') === 'yes'
            ),
            'network' => $this->network
        );
    }
}
