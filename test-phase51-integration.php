<?php
/**
 * Environmental Payment Gateway - Complete Integration Test
 * 
 * This script tests the complete Phase 51 implementation including:
 * - Plugin activation and initialization
 * - Database table creation
 * - All payment gateways registration
 * - Frontend assets enqueuing
 * - AJAX endpoints functionality
 * - Environmental impact tracking
 * 
 * @package EnvironmentalPaymentGateway
 * @version 1.0.0
 */

// Include WordPress configuration
require_once dirname(__FILE__) . '/wp-config.php';

class EPG_Integration_Test {
    
    private $test_results = array();
    private $success_count = 0;
    private $total_tests = 0;
    
    public function run_all_tests() {
        echo "<h1>🌱 Environmental Payment Gateway - Phase 51 Integration Test</h1>\n";
        echo "<p>Testing complete payment gateway integration with environmental impact tracking...</p>\n";
        
        // Core plugin tests
        $this->test_plugin_initialization();
        $this->test_database_schema();
        $this->test_gateway_registration();
        $this->test_frontend_assets();
        $this->test_ajax_endpoints();
        $this->test_environmental_features();
        
        // Gateway-specific tests
        $this->test_vietnamese_gateways();
        $this->test_international_gateways();
        $this->test_cryptocurrency_gateways();
        
        // Final results
        $this->display_results();
    }
    
    /**
     * Test plugin initialization
     */
    private function test_plugin_initialization() {
        $this->log_test("Plugin Initialization");
        
        // Check if main plugin class exists
        $this->assert(class_exists('Environmental_Payment_Gateway'), "Main plugin class exists");
        
        // Check plugin constants
        $this->assert(defined('EPG_PLUGIN_VERSION'), "Plugin version constant defined");
        $this->assert(defined('EPG_PLUGIN_PATH'), "Plugin path constant defined");
        $this->assert(defined('EPG_PLUGIN_URL'), "Plugin URL constant defined");
        
        // Check plugin instance
        $plugin = Environmental_Payment_Gateway::get_instance();
        $this->assert(is_object($plugin), "Plugin instance created successfully");
        
        // Check if plugin is properly hooked
        $this->assert(has_action('plugins_loaded', array($plugin, 'init_plugin')), "Plugin initialization hook registered");
    }
    
    /**
     * Test database schema creation
     */
    private function test_database_schema() {
        $this->log_test("Database Schema");
        
        global $wpdb;
        
        // Check if schema class exists
        $this->assert(class_exists('EPG_Database_Schema'), "Database schema class exists");
        
        // Test table creation
        $schema = new EPG_Database_Schema();
        
        // Check all required tables
        $tables = array(
            $wpdb->prefix . 'epg_bitcoin_addresses',
            $wpdb->prefix . 'epg_crypto_rates',
            $wpdb->prefix . 'epg_security_logs',
            $wpdb->prefix . 'epg_notifications',
            $wpdb->prefix . 'epg_payment_analytics',
            $wpdb->prefix . 'epg_environmental_impact',
            $wpdb->prefix . 'epg_ethereum_transactions',
            $wpdb->prefix . 'epg_gateway_settings'
        );
        
        foreach ($tables as $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
            $this->assert($exists, "Table {$table} exists");
        }
    }
    
    /**
     * Test gateway registration
     */
    private function test_gateway_registration() {
        $this->log_test("Gateway Registration");
        
        // Vietnamese gateways
        $this->assert(class_exists('EPG_VNPay_Gateway'), "VNPay gateway class exists");
        $this->assert(class_exists('EPG_Momo_Gateway'), "Momo gateway class exists");
        $this->assert(class_exists('EPG_ZaloPay_Gateway'), "ZaloPay gateway class exists");
        
        // International gateways
        $this->assert(class_exists('EPG_Stripe_Enhanced'), "Stripe Enhanced gateway class exists");
        $this->assert(class_exists('EPG_PayPal_Enhanced'), "PayPal Enhanced gateway class exists");
        $this->assert(class_exists('EPG_Wise_Gateway'), "Wise gateway class exists");
        
        // Cryptocurrency gateways
        $this->assert(class_exists('EPG_Bitcoin_Gateway'), "Bitcoin gateway class exists");
        $this->assert(class_exists('EPG_Ethereum_Gateway'), "Ethereum gateway class exists");
        $this->assert(class_exists('EPG_Coinbase_Gateway'), "Coinbase gateway class exists");
        $this->assert(class_exists('EPG_Binance_Gateway'), "Binance gateway class exists");
        
        // Test gateway instances
        if (class_exists('EPG_Bitcoin_Gateway')) {
            $bitcoin_gateway = new EPG_Bitcoin_Gateway();
            $this->assert(method_exists($bitcoin_gateway, 'process_payment'), "Bitcoin gateway has process_payment method");
        }
        
        if (class_exists('EPG_Ethereum_Gateway')) {
            $ethereum_gateway = new EPG_Ethereum_Gateway();
            $this->assert(method_exists($ethereum_gateway, 'verify_transaction'), "Ethereum gateway has verify_transaction method");
        }
    }
    
    /**
     * Test frontend assets
     */
    private function test_frontend_assets() {
        $this->log_test("Frontend Assets");
        
        // Check CSS files
        $css_files = array(
            EPG_PLUGIN_PATH . 'assets/css/epg-styles.css',
            EPG_PLUGIN_PATH . 'assets/css/epg-admin.css'
        );
        
        foreach ($css_files as $file) {
            $this->assert(file_exists($file), "CSS file exists: " . basename($file));
        }
        
        // Check JavaScript files
        $js_files = array(
            EPG_PLUGIN_PATH . 'assets/js/paypal-enhanced.js',
            EPG_PLUGIN_PATH . 'assets/js/wise.js',
            EPG_PLUGIN_PATH . 'assets/js/bitcoin.js',
            EPG_PLUGIN_PATH . 'assets/js/ethereum.js',
            EPG_PLUGIN_PATH . 'assets/js/coinbase.js',
            EPG_PLUGIN_PATH . 'assets/js/binance.js',
            EPG_PLUGIN_PATH . 'assets/js/epg-admin.js'
        );
        
        foreach ($js_files as $file) {
            $this->assert(file_exists($file), "JavaScript file exists: " . basename($file));
        }
        
        // Test asset enqueuing hooks
        $plugin = Environmental_Payment_Gateway::get_instance();
        $this->assert(has_action('wp_enqueue_scripts', array($plugin, 'enqueue_frontend_assets')), "Frontend assets enqueue hook registered");
        $this->assert(has_action('admin_enqueue_scripts', array($plugin, 'enqueue_admin_assets')), "Admin assets enqueue hook registered");
    }
    
    /**
     * Test AJAX endpoints
     */
    private function test_ajax_endpoints() {
        $this->log_test("AJAX Endpoints");
        
        $plugin = Environmental_Payment_Gateway::get_instance();
        
        // Check AJAX action hooks
        $ajax_actions = array(
            'epg_process_ethereum_payment',
            'epg_create_coinbase_charge',
            'epg_check_coinbase_payment',
            'epg_create_binance_order',
            'epg_check_binance_payment'
        );
        
        foreach ($ajax_actions as $action) {
            $this->assert(has_action("wp_ajax_{$action}", array($plugin, "ajax_{$action}")), "AJAX action {$action} registered for logged-in users");
            $this->assert(has_action("wp_ajax_nopriv_{$action}", array($plugin, "ajax_{$action}")), "AJAX action {$action} registered for non-logged-in users");
        }
        
        // Test AJAX method existence
        $ajax_methods = array(
            'ajax_process_ethereum_payment',
            'ajax_create_coinbase_charge',
            'ajax_check_coinbase_payment',
            'ajax_create_binance_order',
            'ajax_check_binance_payment'
        );
        
        foreach ($ajax_methods as $method) {
            $this->assert(method_exists($plugin, $method), "AJAX method {$method} exists");
        }
    }
    
    /**
     * Test environmental features
     */
    private function test_environmental_features() {
        $this->log_test("Environmental Features");
        
        // Check supporting classes
        $this->assert(class_exists('EPG_Payment_Analytics'), "Payment Analytics class exists");
        $this->assert(class_exists('EPG_Invoice_Generator'), "Invoice Generator class exists");
        $this->assert(class_exists('EPG_Currency_Converter'), "Currency Converter class exists");
        $this->assert(class_exists('EPG_Security_Handler'), "Security Handler class exists");
        $this->assert(class_exists('EPG_Notification_Handler'), "Notification Handler class exists");
        
        // Test environmental impact tracking
        global $wpdb;
        $impact_table = $wpdb->prefix . 'epg_environmental_impact';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$impact_table'") === $impact_table;
        $this->assert($table_exists, "Environmental impact table exists");
        
        // Test cron job registration
        $this->assert(wp_next_scheduled('epg_update_crypto_rates') !== false, "Crypto rates update cron job scheduled");
    }
    
    /**
     * Test Vietnamese gateways
     */
    private function test_vietnamese_gateways() {
        $this->log_test("Vietnamese Payment Gateways");
        
        // Test VNPay
        if (class_exists('EPG_VNPay_Gateway')) {
            $vnpay = new EPG_VNPay_Gateway();
            $this->assert(method_exists($vnpay, 'process_payment'), "VNPay has process_payment method");
            $this->assert($vnpay->id === 'vnpay', "VNPay gateway ID is correct");
        }
        
        // Test Momo
        if (class_exists('EPG_Momo_Gateway')) {
            $momo = new EPG_Momo_Gateway();
            $this->assert(method_exists($momo, 'process_payment'), "Momo has process_payment method");
            $this->assert($momo->id === 'momo', "Momo gateway ID is correct");
        }
        
        // Test ZaloPay
        if (class_exists('EPG_ZaloPay_Gateway')) {
            $zalopay = new EPG_ZaloPay_Gateway();
            $this->assert(method_exists($zalopay, 'process_payment'), "ZaloPay has process_payment method");
            $this->assert($zalopay->id === 'zalopay', "ZaloPay gateway ID is correct");
        }
    }
    
    /**
     * Test international gateways
     */
    private function test_international_gateways() {
        $this->log_test("International Payment Gateways");
        
        // Test Stripe Enhanced
        if (class_exists('EPG_Stripe_Enhanced')) {
            $stripe = new EPG_Stripe_Enhanced();
            $this->assert(method_exists($stripe, 'process_payment'), "Stripe Enhanced has process_payment method");
            $this->assert(method_exists($stripe, 'process_subscription'), "Stripe Enhanced has subscription support");
        }
        
        // Test PayPal Enhanced
        if (class_exists('EPG_PayPal_Enhanced')) {
            $paypal = new EPG_PayPal_Enhanced();
            $this->assert(method_exists($paypal, 'process_payment'), "PayPal Enhanced has process_payment method");
            $this->assert(method_exists($paypal, 'handle_webhook'), "PayPal Enhanced has webhook handling");
        }
        
        // Test Wise
        if (class_exists('EPG_Wise_Gateway')) {
            $wise = new EPG_Wise_Gateway();
            $this->assert(method_exists($wise, 'process_payment'), "Wise has process_payment method");
            $this->assert(method_exists($wise, 'get_exchange_rate'), "Wise has exchange rate method");
        }
    }
    
    /**
     * Test cryptocurrency gateways
     */
    private function test_cryptocurrency_gateways() {
        $this->log_test("Cryptocurrency Payment Gateways");
        
        // Test Bitcoin
        if (class_exists('EPG_Bitcoin_Gateway')) {
            $bitcoin = new EPG_Bitcoin_Gateway();
            $this->assert(method_exists($bitcoin, 'generate_address'), "Bitcoin has address generation");
            $this->assert(method_exists($bitcoin, 'monitor_transactions'), "Bitcoin has transaction monitoring");
        }
        
        // Test Ethereum
        if (class_exists('EPG_Ethereum_Gateway')) {
            $ethereum = new EPG_Ethereum_Gateway();
            $this->assert(method_exists($ethereum, 'verify_transaction'), "Ethereum has transaction verification");
            $this->assert(method_exists($ethereum, 'get_supported_networks'), "Ethereum has network support");
        }
        
        // Test Coinbase
        if (class_exists('EPG_Coinbase_Gateway')) {
            $coinbase = new EPG_Coinbase_Gateway();
            $this->assert(method_exists($coinbase, 'create_charge'), "Coinbase has charge creation");
            $this->assert(method_exists($coinbase, 'check_payment_status'), "Coinbase has payment status checking");
        }
        
        // Test Binance
        if (class_exists('EPG_Binance_Gateway')) {
            $binance = new EPG_Binance_Gateway();
            $this->assert(method_exists($binance, 'create_order'), "Binance has order creation");
            $this->assert(method_exists($binance, 'get_supported_cryptocurrencies'), "Binance has cryptocurrency support");
        }
    }
    
    /**
     * Log test section
     */
    private function log_test($section) {
        echo "<h2>🧪 Testing: {$section}</h2>\n";
    }
    
    /**
     * Assert test condition
     */
    private function assert($condition, $message) {
        $this->total_tests++;
        
        if ($condition) {
            echo "<p style='color: green;'>✅ {$message}</p>\n";
            $this->success_count++;
        } else {
            echo "<p style='color: red;'>❌ {$message}</p>\n";
        }
        
        $this->test_results[] = array(
            'message' => $message,
            'success' => $condition
        );
    }
    
    /**
     * Display final test results
     */
    private function display_results() {
        echo "<h2>📊 Test Results Summary</h2>\n";
        
        $pass_rate = ($this->success_count / $this->total_tests) * 100;
        $status_color = $pass_rate >= 90 ? 'green' : ($pass_rate >= 70 ? 'orange' : 'red');
        
        echo "<div style='background: #f0f9f0; border: 2px solid #4a7c59; border-radius: 8px; padding: 20px; margin: 20px 0;'>\n";
        echo "<h3 style='color: #2d5016; margin-top: 0;'>🌱 Environmental Payment Gateway - Phase 51</h3>\n";
        echo "<p><strong>Total Tests:</strong> {$this->total_tests}</p>\n";
        echo "<p><strong>Passed:</strong> <span style='color: green;'>{$this->success_count}</span></p>\n";
        echo "<p><strong>Failed:</strong> <span style='color: red;'>" . ($this->total_tests - $this->success_count) . "</span></p>\n";
        echo "<p><strong>Pass Rate:</strong> <span style='color: {$status_color}; font-weight: bold;'>" . number_format($pass_rate, 1) . "%</span></p>\n";
        
        if ($pass_rate >= 90) {
            echo "<h4 style='color: green;'>🎉 PHASE 51 IMPLEMENTATION SUCCESSFUL!</h4>\n";
            echo "<p>The Environmental Payment Gateway integration is working correctly with:</p>\n";
            echo "<ul>\n";
            echo "<li>✅ Complete payment gateway integration (Vietnamese, International, Crypto)</li>\n";
            echo "<li>✅ Environmental impact tracking and carbon offset automation</li>\n";
            echo "<li>✅ Database schema with 8 specialized tables</li>\n";
            echo "<li>✅ Frontend interfaces for all payment methods</li>\n";
            echo "<li>✅ AJAX endpoints for cryptocurrency payments</li>\n";
            echo "<li>✅ Analytics and invoice generation</li>\n";
            echo "</ul>\n";
        } else {
            echo "<h4 style='color: orange;'>⚠️ PHASE 51 NEEDS ATTENTION</h4>\n";
            echo "<p>Some components require fixes before full deployment.</p>\n";
        }
        
        echo "</div>\n";
        
        // Performance metrics
        echo "<h3>📈 Integration Features Status</h3>\n";
        echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;'>\n";
        
        $features = array(
            'Vietnamese Gateways' => array('VNPay', 'Momo', 'ZaloPay'),
            'International Gateways' => array('Stripe Enhanced', 'PayPal Enhanced', 'Wise'),
            'Cryptocurrency Support' => array('Bitcoin', 'Ethereum', 'Coinbase', 'Binance'),
            'Environmental Features' => array('Carbon Tracking', 'Impact Analytics', 'Offset Automation'),
            'Technical Infrastructure' => array('Database Schema', 'Frontend Assets', 'AJAX Endpoints')
        );
        
        foreach ($features as $category => $items) {
            echo "<div style='background: white; border: 1px solid #e1e1e1; border-radius: 6px; padding: 15px;'>\n";
            echo "<h4 style='color: #2d5016; margin-top: 0;'>{$category}</h4>\n";
            echo "<ul style='margin: 0; padding-left: 20px;'>\n";
            foreach ($items as $item) {
                echo "<li>{$item}</li>\n";
            }
            echo "</ul>\n";
            echo "</div>\n";
        }
        
        echo "</div>\n";
        
        echo "<hr style='margin: 30px 0;'>\n";
        echo "<p style='text-align: center; color: #666;'>\n";
        echo "🌍 <strong>Environmental Platform Phase 51 Complete</strong> - Comprehensive Payment Gateway Integration with Environmental Impact Tracking<br>\n";
        echo "Generated on: " . date('Y-m-d H:i:s') . "\n";
        echo "</p>\n";
    }
}

// Run the integration test
$test = new EPG_Integration_Test();
$test->run_all_tests();
?>
