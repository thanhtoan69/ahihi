<?php
/**
 * Ethereum Gateway
 * 
 * Ethereum payment gateway with smart contract integration, ERC-20 token support,
 * Layer 2 scaling solutions, and environmental impact tracking.
 * 
 * @package EnvironmentalPaymentGateway
 * @subpackage Gateways\Crypto
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * EPG Ethereum Gateway Class
 */
class EPG_Ethereum_Gateway extends EPG_Gateway_Base {
    
    /**
     * Gateway ID
     */
    public $id = 'ethereum';
    
    /**
     * Gateway method title
     */
    public $method_title = 'Ethereum';
    
    /**
     * Gateway method description
     */
    public $method_description = 'Accept Ethereum and ERC-20 token payments with environmental tracking';
    
    /**
     * Supported features
     */
    public $supports = array(
        'products'
    );
    
    /**
     * Ethereum network
     */
    private $network = 'mainnet'; // mainnet, goerli, sepolia
    
    /**
     * Supported networks and their RPC endpoints
     */
    private $networks = array(
        'mainnet' => array(
            'name' => 'Ethereum Mainnet',
            'chain_id' => 1,
            'rpc_urls' => array(
                'infura' => 'https://mainnet.infura.io/v3/',
                'alchemy' => 'https://eth-mainnet.alchemyapi.io/v2/',
                'public' => 'https://cloudflare-eth.com'
            ),
            'explorer' => 'https://etherscan.io'
        ),
        'goerli' => array(
            'name' => 'Goerli Testnet',
            'chain_id' => 5,
            'rpc_urls' => array(
                'infura' => 'https://goerli.infura.io/v3/',
                'alchemy' => 'https://eth-goerli.alchemyapi.io/v2/',
                'public' => 'https://rpc.goerli.mudit.blog'
            ),
            'explorer' => 'https://goerli.etherscan.io'
        ),
        'sepolia' => array(
            'name' => 'Sepolia Testnet',
            'chain_id' => 11155111,
            'rpc_urls' => array(
                'infura' => 'https://sepolia.infura.io/v3/',
                'alchemy' => 'https://eth-sepolia.alchemyapi.io/v2/',
                'public' => 'https://rpc.sepolia.org'
            ),
            'explorer' => 'https://sepolia.etherscan.io'
        )
    );
    
    /**
     * Popular ERC-20 tokens
     */
    private $erc20_tokens = array(
        'USDC' => array(
            'mainnet' => '0xA0b86991c8bb36c62b4c4c9fdc11b11b7a6c132e0',
            'goerli' => '0x07865c6e87b9f70255377e024ace6630c1eaa37f',
            'decimals' => 6
        ),
        'USDT' => array(
            'mainnet' => '0xdAC17F958D2ee523a2206206994597C13D831ec7',
            'goerli' => '0x509Ee0d083DdF8AC028f2a56731412edD63223B9',
            'decimals' => 6
        ),
        'DAI' => array(
            'mainnet' => '0x6B175474E89094C44Da98B954EeDeAC495271d0F',
            'goerli' => '0x11fE4B6AE13d2a6055C8D9cF65c55bac32B5d844',
            'decimals' => 18
        ),
        'WETH' => array(
            'mainnet' => '0xC02aaA39b223FE8D0A0e5C4F27eAD9083C756Cc2',
            'goerli' => '0xB4FBF271143F4FBf7B91A5ded31805e42b2208d6',
            'decimals' => 18
        )
    );
    
    /**
     * Layer 2 solutions
     */
    private $layer2_networks = array(
        'polygon' => array(
            'name' => 'Polygon',
            'chain_id' => 137,
            'rpc_url' => 'https://polygon-rpc.com',
            'gas_efficiency' => 'high',
            'carbon_footprint' => 'low'
        ),
        'arbitrum' => array(
            'name' => 'Arbitrum One',
            'chain_id' => 42161,
            'rpc_url' => 'https://arb1.arbitrum.io/rpc',
            'gas_efficiency' => 'high',
            'carbon_footprint' => 'low'
        ),
        'optimism' => array(
            'name' => 'Optimism',
            'chain_id' => 10,
            'rpc_url' => 'https://mainnet.optimism.io',
            'gas_efficiency' => 'high',
            'carbon_footprint' => 'low'
        )
    );
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        $this->title = __('Ethereum', 'environmental-payment-gateway');
        $this->description = __('Pay with Ethereum or ERC-20 tokens. Eco-friendly Layer 2 options available.', 'environmental-payment-gateway');
        $this->icon = EPG_PLUGIN_URL . 'assets/images/ethereum-logo.png';
        
        // Set network based on settings
        $this->network = $this->get_option('network', 'mainnet');
        
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
        
        // Ethereum-specific hooks
        add_action('wp_ajax_epg_ethereum_check_payment', array($this, 'ajax_check_payment'));
        add_action('wp_ajax_nopriv_epg_ethereum_check_payment', array($this, 'ajax_check_payment'));
        add_action('epg_ethereum_payment_monitor', array($this, 'monitor_payments'));
        
        // Web3 integration hooks
        add_action('wp_ajax_epg_ethereum_connect_wallet', array($this, 'ajax_connect_wallet'));
        add_action('wp_ajax_nopriv_epg_ethereum_connect_wallet', array($this, 'ajax_connect_wallet'));
        
        // Schedule payment monitoring
        if (!wp_next_scheduled('epg_ethereum_payment_monitor')) {
            wp_schedule_event(time(), 'every_minute', 'epg_ethereum_payment_monitor');
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
                'label' => __('Enable Ethereum Gateway', 'environmental-payment-gateway'),
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'environmental-payment-gateway'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'environmental-payment-gateway'),
                'default' => __('Ethereum', 'environmental-payment-gateway'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'environmental-payment-gateway'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'environmental-payment-gateway'),
                'default' => __('Pay with Ethereum or ERC-20 tokens. Choose eco-friendly Layer 2 for lower carbon footprint.', 'environmental-payment-gateway'),
            ),
            'network_settings' => array(
                'title' => __('Network Settings', 'environmental-payment-gateway'),
                'type' => 'title',
                'description' => '',
            ),
            'network' => array(
                'title' => __('Ethereum Network', 'environmental-payment-gateway'),
                'type' => 'select',
                'description' => __('Select Ethereum network.', 'environmental-payment-gateway'),
                'default' => 'mainnet',
                'desc_tip' => true,
                'options' => array(
                    'mainnet' => __('Mainnet (Live)', 'environmental-payment-gateway'),
                    'goerli' => __('Goerli Testnet', 'environmental-payment-gateway'),
                    'sepolia' => __('Sepolia Testnet', 'environmental-payment-gateway'),
                )
            ),
            'rpc_provider' => array(
                'title' => __('RPC Provider', 'environmental-payment-gateway'),
                'type' => 'select',
                'description' => __('Select RPC provider for blockchain interaction.', 'environmental-payment-gateway'),
                'default' => 'public',
                'options' => array(
                    'public' => __('Public RPC', 'environmental-payment-gateway'),
                    'infura' => __('Infura', 'environmental-payment-gateway'),
                    'alchemy' => __('Alchemy', 'environmental-payment-gateway'),
                    'custom' => __('Custom RPC', 'environmental-payment-gateway')
                )
            ),
            'rpc_api_key' => array(
                'title' => __('RPC API Key', 'environmental-payment-gateway'),
                'type' => 'password',
                'description' => __('API key for Infura or Alchemy (if selected).', 'environmental-payment-gateway'),
                'default' => ''
            ),
            'custom_rpc_url' => array(
                'title' => __('Custom RPC URL', 'environmental-payment-gateway'),
                'type' => 'url',
                'description' => __('Custom RPC endpoint URL.', 'environmental-payment-gateway'),
                'default' => ''
            ),
            'wallet_settings' => array(
                'title' => __('Wallet Settings', 'environmental-payment-gateway'),
                'type' => 'title',
                'description' => '',
            ),
            'receiving_address' => array(
                'title' => __('Receiving Address', 'environmental-payment-gateway'),
                'type' => 'text',
                'description' => __('Ethereum address to receive payments.', 'environmental-payment-gateway'),
                'default' => '',
                'desc_tip' => true,
            ),
            'accepted_tokens' => array(
                'title' => __('Accepted Tokens', 'environmental-payment-gateway'),
                'type' => 'multiselect',
                'description' => __('Select which tokens to accept (in addition to ETH).', 'environmental-payment-gateway'),
                'default' => array('ETH'),
                'options' => array(
                    'ETH' => 'Ethereum (ETH)',
                    'USDC' => 'USD Coin (USDC)',
                    'USDT' => 'Tether (USDT)',
                    'DAI' => 'Dai Stablecoin (DAI)',
                    'WETH' => 'Wrapped Ethereum (WETH)'
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
                'default' => '12',
                'custom_attributes' => array(
                    'min' => '1',
                    'max' => '50'
                )
            ),
            'payment_timeout' => array(
                'title' => __('Payment Timeout (minutes)', 'environmental-payment-gateway'),
                'type' => 'number',
                'description' => __('Time limit for customers to complete Ethereum payment.', 'environmental-payment-gateway'),
                'default' => '30',
                'custom_attributes' => array(
                    'min' => '10',
                    'max' => '120'
                )
            ),
            'gas_settings' => array(
                'title' => __('Gas Settings', 'environmental-payment-gateway'),
                'type' => 'title',
                'description' => '',
            ),
            'gas_price_strategy' => array(
                'title' => __('Gas Price Strategy', 'environmental-payment-gateway'),
                'type' => 'select',
                'description' => __('How to handle gas fees.', 'environmental-payment-gateway'),
                'default' => 'standard',
                'options' => array(
                    'slow' => __('Slow (Lower fees, eco-friendly)', 'environmental-payment-gateway'),
                    'standard' => __('Standard', 'environmental-payment-gateway'),
                    'fast' => __('Fast (Higher fees)', 'environmental-payment-gateway'),
                    'custom' => __('Custom Gas Price', 'environmental-payment-gateway')
                )
            ),
            'custom_gas_price' => array(
                'title' => __('Custom Gas Price (Gwei)', 'environmental-payment-gateway'),
                'type' => 'number',
                'description' => __('Custom gas price in Gwei.', 'environmental-payment-gateway'),
                'default' => '20',
                'custom_attributes' => array(
                    'min' => '1',
                    'max' => '1000'
                )
            ),
            'layer2_settings' => array(
                'title' => __('Layer 2 Networks', 'environmental-payment-gateway'),
                'type' => 'title',
                'description' => __('Enable Layer 2 solutions for lower fees and environmental impact.', 'environmental-payment-gateway'),
            ),
            'enable_layer2' => array(
                'title' => __('Enable Layer 2', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Enable Layer 2 networks (Polygon, Arbitrum, Optimism)', 'environmental-payment-gateway'),
                'default' => 'yes'
            ),
            'preferred_layer2' => array(
                'title' => __('Preferred Layer 2', 'environmental-payment-gateway'),
                'type' => 'select',
                'description' => __('Suggest this Layer 2 network to users.', 'environmental-payment-gateway'),
                'default' => 'polygon',
                'options' => array(
                    'polygon' => __('Polygon (MATIC)', 'environmental-payment-gateway'),
                    'arbitrum' => __('Arbitrum One', 'environmental-payment-gateway'),
                    'optimism' => __('Optimism', 'environmental-payment-gateway')
                )
            ),
            'environmental_settings' => array(
                'title' => __('Environmental Settings', 'environmental-payment-gateway'),
                'type' => 'title',
                'description' => '',
            ),
            'carbon_tracking' => array(
                'title' => __('Carbon Tracking', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Track carbon footprint of Ethereum transactions', 'environmental-payment-gateway'),
                'default' => 'yes'
            ),
            'carbon_offset_enabled' => array(
                'title' => __('Carbon Offset', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Automatically offset transaction carbon footprint', 'environmental-payment-gateway'),
                'default' => 'yes'
            ),
            'promote_pos' => array(
                'title' => __('Promote Proof of Stake', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Show information about Ethereum\'s transition to Proof of Stake', 'environmental-payment-gateway'),
                'default' => 'yes',
                'description' => __('Educate users about the environmental benefits of Ethereum 2.0.', 'environmental-payment-gateway')
            ),
            'web3_integration' => array(
                'title' => __('Web3 Integration', 'environmental-payment-gateway'),
                'type' => 'title',
                'description' => '',
            ),
            'enable_web3' => array(
                'title' => __('Enable Web3 Wallets', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Allow payments directly from Web3 wallets (MetaMask, WalletConnect)', 'environmental-payment-gateway'),
                'default' => 'yes'
            ),
            'enable_smart_contracts' => array(
                'title' => __('Smart Contract Integration', 'environmental-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Use smart contracts for automated payments and refunds', 'environmental-payment-gateway'),
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
            // Get selected token
            $selected_token = isset($_POST['ethereum_token']) ? sanitize_text_field($_POST['ethereum_token']) : 'ETH';
            
            // Get token price
            $token_price = $this->get_token_price($selected_token, $order->get_currency());
            if (!$token_price) {
                throw new Exception(sprintf(__('Unable to get %s exchange rate', 'environmental-payment-gateway'), $selected_token));
            }
            
            // Calculate token amount
            $token_amount = $order->get_total() / $token_price;
            
            // Get receiving address
            $receiving_address = $this->get_option('receiving_address');
            if (empty($receiving_address)) {
                throw new Exception(__('Receiving address not configured', 'environmental-payment-gateway'));
            }
            
            // Store payment details
            $order->update_meta_data('_ethereum_token', $selected_token);
            $order->update_meta_data('_ethereum_address', $receiving_address);
            $order->update_meta_data('_ethereum_amount', $token_amount);
            $order->update_meta_data('_ethereum_price', $token_price);
            $order->update_meta_data('_ethereum_network', $this->network);
            $order->update_meta_data('_ethereum_payment_timeout', time() + ($this->get_option('payment_timeout', 30) * 60));
            $order->save();
            
            // Set order status to pending payment
            $order->update_status('pending', __('Awaiting Ethereum payment', 'environmental-payment-gateway'));
            
            // Calculate carbon footprint
            $this->calculate_carbon_footprint($order, $selected_token);
            
            // Check if Web3 payment is enabled
            if ($this->get_option('enable_web3') === 'yes' && isset($_POST['web3_payment'])) {
                return array(
                    'result' => 'success',
                    'redirect' => add_query_arg('ethereum_web3_payment', $order->get_id(), $this->get_return_url($order))
                );
            } else {
                return array(
                    'result' => 'success',
                    'redirect' => add_query_arg('ethereum_payment', $order->get_id(), $this->get_return_url($order))
                );
            }
            
        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            $this->log_error('Ethereum payment processing failed: ' . $e->getMessage());
            return array('result' => 'fail');
        }
    }
    
    /**
     * Get token price
     */
    private function get_token_price($token, $currency = 'USD') {
        $token_id_map = array(
            'ETH' => 'ethereum',
            'USDC' => 'usd-coin',
            'USDT' => 'tether',
            'DAI' => 'dai',
            'WETH' => 'weth'
        );
        
        $token_id = isset($token_id_map[$token]) ? $token_id_map[$token] : strtolower($token);
        
        // Try CoinGecko API
        $url = "https://api.coingecko.com/api/v3/simple/price?ids={$token_id}&vs_currencies={$currency}";
        $response = wp_remote_get($url, array('timeout' => 10));
        
        if (!is_wp_error($response)) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (isset($body[$token_id][strtolower($currency)])) {
                return floatval($body[$token_id][strtolower($currency)]);
            }
        }
        
        // Fallback to other APIs if needed
        return false;
    }
    
    /**
     * Calculate carbon footprint
     */
    private function calculate_carbon_footprint($order, $token) {
        // Ethereum carbon footprint calculation
        // Note: These values should be updated based on current network status
        
        if ($this->network === 'mainnet') {
            // Post-merge Ethereum (Proof of Stake)
            $kwh_per_transaction = 0.0026; // Much lower after The Merge
            $co2_per_kwh = 0.5; // kg CO2 per kWh
        } else {
            // Testnet (minimal energy consumption)
            $kwh_per_transaction = 0.001;
            $co2_per_kwh = 0.5;
        }
        
        // ERC-20 tokens require more gas
        if ($token !== 'ETH') {
            $kwh_per_transaction *= 2; // Approximate multiplier for token transfers
        }
        
        $carbon_footprint = $kwh_per_transaction * $co2_per_kwh; // kg CO2
        
        // Store carbon footprint data
        $order->update_meta_data('_ethereum_carbon_footprint_kg', $carbon_footprint);
        $order->update_meta_data('_ethereum_energy_consumption_kwh', $kwh_per_transaction);
        $order->save();
        
        return $carbon_footprint;
    }
    
    /**
     * Monitor payments
     */
    public function monitor_payments() {
        global $wpdb;
        
        // Get pending Ethereum payments
        $pending_orders = wc_get_orders(array(
            'status' => 'pending',
            'payment_method' => $this->id,
            'meta_query' => array(
                array(
                    'key' => '_ethereum_address',
                    'compare' => 'EXISTS'
                )
            )
        ));
        
        foreach ($pending_orders as $order) {
            $this->check_payment_status($order);
        }
    }
    
    /**
     * Check payment status for order
     */
    private function check_payment_status($order) {
        $address = $order->get_meta('_ethereum_address');
        $expected_amount = floatval($order->get_meta('_ethereum_amount'));
        $token = $order->get_meta('_ethereum_token');
        $timeout = $order->get_meta('_ethereum_payment_timeout');
        
        // Check timeout
        if ($timeout && time() > $timeout) {
            $order->update_status('cancelled', __('Ethereum payment timeout expired', 'environmental-payment-gateway'));
            return;
        }
        
        // Check for transactions
        $transactions = $this->get_address_transactions($address, $token);
        
        foreach ($transactions as $tx) {
            if ($tx['amount'] >= $expected_amount && $tx['confirmations'] >= $this->get_option('confirmation_blocks', 12)) {
                // Payment confirmed
                $order->payment_complete($tx['hash']);
                $order->add_order_note(
                    sprintf(__('Ethereum payment received. Transaction: %s', 'environmental-payment-gateway'), $tx['hash'])
                );
                
                // Process carbon offset if enabled
                if ($this->get_option('carbon_offset_enabled') === 'yes') {
                    $this->process_carbon_offset($order);
                }
                
                break;
            }
        }
    }
    
    /**
     * Get transactions for address
     */
    private function get_address_transactions($address, $token = 'ETH') {
        $rpc_url = $this->get_rpc_url();
        
        if ($token === 'ETH') {
            return $this->get_eth_transactions($address, $rpc_url);
        } else {
            return $this->get_erc20_transactions($address, $token, $rpc_url);
        }
    }
    
    /**
     * Get ETH transactions
     */
    private function get_eth_transactions($address, $rpc_url) {
        // This would typically use etherscan API or similar
        $api_key = $this->get_option('etherscan_api_key');
        $network_prefix = $this->network === 'mainnet' ? '' : '-' . $this->network;
        $url = "https://api{$network_prefix}.etherscan.io/api?module=account&action=txlist&address={$address}&sort=desc&apikey={$api_key}";
        
        $response = wp_remote_get($url, array('timeout' => 15));
        
        if (is_wp_error($response)) {
            return array();
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $transactions = array();
        
        if (isset($body['result']) && is_array($body['result'])) {
            foreach ($body['result'] as $tx) {
                if ($tx['to'] === strtolower($address) && $tx['value'] > 0) {
                    $transactions[] = array(
                        'hash' => $tx['hash'],
                        'amount' => hexdec($tx['value']) / pow(10, 18), // Convert Wei to ETH
                        'confirmations' => $this->get_current_block_number() - intval($tx['blockNumber'])
                    );
                }
            }
        }
        
        return $transactions;
    }
    
    /**
     * Get ERC-20 token transactions
     */
    private function get_erc20_transactions($address, $token, $rpc_url) {
        if (!isset($this->erc20_tokens[$token][$this->network])) {
            return array();
        }
        
        $token_address = $this->erc20_tokens[$token][$this->network];
        $decimals = $this->erc20_tokens[$token]['decimals'];
        
        // Use etherscan API for token transfers
        $api_key = $this->get_option('etherscan_api_key');
        $network_prefix = $this->network === 'mainnet' ? '' : '-' . $this->network;
        $url = "https://api{$network_prefix}.etherscan.io/api?module=account&action=tokentx&contractaddress={$token_address}&address={$address}&sort=desc&apikey={$api_key}";
        
        $response = wp_remote_get($url, array('timeout' => 15));
        
        if (is_wp_error($response)) {
            return array();
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $transactions = array();
        
        if (isset($body['result']) && is_array($body['result'])) {
            foreach ($body['result'] as $tx) {
                if ($tx['to'] === strtolower($address) && $tx['value'] > 0) {
                    $transactions[] = array(
                        'hash' => $tx['hash'],
                        'amount' => intval($tx['value']) / pow(10, $decimals),
                        'confirmations' => $this->get_current_block_number() - intval($tx['blockNumber'])
                    );
                }
            }
        }
        
        return $transactions;
    }
    
    /**
     * Get current block number
     */
    private function get_current_block_number() {
        $rpc_url = $this->get_rpc_url();
        
        $response = wp_remote_post($rpc_url, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode(array(
                'jsonrpc' => '2.0',
                'method' => 'eth_blockNumber',
                'params' => array(),
                'id' => 1
            )),
            'timeout' => 10
        ));
        
        if (!is_wp_error($response)) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (isset($body['result'])) {
                return hexdec($body['result']);
            }
        }
        
        return 0;
    }
    
    /**
     * Get RPC URL
     */
    private function get_rpc_url() {
        $provider = $this->get_option('rpc_provider', 'public');
        $network_config = $this->networks[$this->network];
        
        switch ($provider) {
            case 'infura':
                $api_key = $this->get_option('rpc_api_key');
                return $network_config['rpc_urls']['infura'] . $api_key;
                
            case 'alchemy':
                $api_key = $this->get_option('rpc_api_key');
                return $network_config['rpc_urls']['alchemy'] . $api_key;
                
            case 'custom':
                return $this->get_option('custom_rpc_url');
                
            default:
                return $network_config['rpc_urls']['public'];
        }
    }
    
    /**
     * Process carbon offset
     */
    private function process_carbon_offset($order) {
        $carbon_footprint = floatval($order->get_meta('_ethereum_carbon_footprint_kg'));
        
        if ($carbon_footprint > 0) {
            // Calculate offset cost
            $offset_cost_per_kg = 0.02; // $0.02 per kg CO2
            $offset_amount = $carbon_footprint * $offset_cost_per_kg;
            
            // Store offset data
            $order->update_meta_data('_ethereum_carbon_offset_amount', $offset_amount);
            $order->update_meta_data('_ethereum_carbon_offset_processed', 'yes');
            $order->save();
            
            // Trigger carbon offset action
            do_action('epg_carbon_offset_donation', $order->get_id(), $offset_amount);
            
            $order->add_order_note(
                sprintf(__('Ethereum carbon footprint offset: %.6f kg CO2, Cost: %s %s', 'environmental-payment-gateway'), 
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
        check_ajax_referer('epg_ethereum_nonce', 'nonce');
        
        $order_id = intval($_POST['order_id']);
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wp_die('Invalid order');
        }
        
        $this->check_payment_status($order);
        
        wp_send_json_success(array(
            'status' => $order->get_status(),
            'payment_complete' => $order->is_paid()
        ));
    }
    
    /**
     * AJAX connect wallet
     */
    public function ajax_connect_wallet() {
        check_ajax_referer('epg_ethereum_nonce', 'nonce');
        
        $wallet_address = sanitize_text_field($_POST['wallet_address']);
        $order_id = intval($_POST['order_id']);
        
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error('Invalid order');
        }
        
        // Store connected wallet address
        $order->update_meta_data('_ethereum_sender_address', $wallet_address);
        $order->save();
        
        wp_send_json_success(array(
            'message' => __('Wallet connected successfully', 'environmental-payment-gateway'),
            'receiving_address' => $order->get_meta('_ethereum_address'),
            'amount' => $order->get_meta('_ethereum_amount'),
            'token' => $order->get_meta('_ethereum_token')
        ));
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        if (is_admin() || !is_checkout()) {
            return;
        }
        
        wp_enqueue_script(
            'epg-ethereum',
            EPG_PLUGIN_URL . 'assets/js/ethereum.js',
            array('jquery'),
            EPG_PLUGIN_VERSION,
            true
        );
        
        // Web3 integration
        if ($this->get_option('enable_web3') === 'yes') {
            wp_enqueue_script(
                'web3',
                'https://cdn.jsdelivr.net/npm/web3@1.8.0/dist/web3.min.js',
                array(),
                '1.8.0',
                true
            );
            
            wp_enqueue_script(
                'walletconnect',
                'https://cdn.jsdelivr.net/npm/@walletconnect/web3-provider@1.8.0/dist/umd/index.min.js',
                array(),
                '1.8.0',
                true
            );
        }
        
        wp_localize_script('epg-ethereum', 'epg_ethereum_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('epg_ethereum_nonce'),
            'network' => $this->network,
            'chain_id' => $this->networks[$this->network]['chain_id'],
            'confirmation_blocks' => $this->get_option('confirmation_blocks', 12),
            'enable_web3' => $this->get_option('enable_web3') === 'yes',
            'accepted_tokens' => $this->get_option('accepted_tokens', array('ETH')),
            'layer2_enabled' => $this->get_option('enable_layer2') === 'yes',
            'preferred_layer2' => $this->get_option('preferred_layer2', 'polygon')
        ));
    }
    
    /**
     * Get EDS processor configuration
     */
    public function get_eds_processor() {
        return array(
            'id' => $this->id,
            'name' => $this->method_title,
            'description' => __('Ethereum and ERC-20 token payments with environmental tracking', 'environmental-payment-gateway'),
            'environmental_features' => array(
                'carbon_tracking' => $this->get_option('carbon_tracking') === 'yes',
                'carbon_offset' => $this->get_option('carbon_offset_enabled') === 'yes',
                'layer2_support' => $this->get_option('enable_layer2') === 'yes',
                'pos_education' => $this->get_option('promote_pos') === 'yes'
            ),
            'supported_tokens' => $this->get_option('accepted_tokens', array('ETH')),
            'network' => $this->network
        );
    }
}
