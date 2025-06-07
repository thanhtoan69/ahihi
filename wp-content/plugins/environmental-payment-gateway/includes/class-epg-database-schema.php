<?php
/**
 * Database Schema Creation for Environmental Payment Gateway
 * 
 * Creates additional database tables needed for advanced payment gateway functionality
 * 
 * @package EnvironmentalPaymentGateway
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * EPG_Database_Schema class
 */
class EPG_Database_Schema {
    
    /**
     * Create all required tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Bitcoin addresses table for HD wallet management
        $table_name = $wpdb->prefix . 'epg_bitcoin_addresses';
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            address varchar(255) NOT NULL,
            private_key text NOT NULL,
            public_key text NOT NULL,
            derivation_path varchar(255) NOT NULL,
            address_type enum('legacy','segwit','native_segwit') DEFAULT 'native_segwit',
            balance decimal(20,8) DEFAULT 0.00000000,
            transactions_count int(11) DEFAULT 0,
            status enum('active','used','expired') DEFAULT 'active',
            network enum('mainnet','testnet') DEFAULT 'testnet',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            expires_at datetime,
            PRIMARY KEY (id),
            UNIQUE KEY address_unique (address),
            KEY order_id_index (order_id),
            KEY status_index (status),
            KEY network_index (network)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Crypto rates table for historical tracking
        $table_name = $wpdb->prefix . 'epg_crypto_rates';
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            currency_pair varchar(20) NOT NULL,
            rate decimal(20,8) NOT NULL,
            source varchar(50) NOT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            volume_24h decimal(20,8),
            market_cap decimal(20,2),
            change_24h decimal(10,4),
            environmental_rating enum('very_low','low','medium','high') DEFAULT 'medium',
            carbon_intensity decimal(15,10),
            PRIMARY KEY (id),
            KEY currency_timestamp (currency_pair, timestamp),
            KEY source_index (source)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Security logs table for payment monitoring
        $table_name = $wpdb->prefix . 'epg_security_logs';
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            gateway varchar(50) NOT NULL,
            event_type varchar(100) NOT NULL,
            severity enum('low','medium','high','critical') DEFAULT 'medium',
            user_id bigint(20),
            order_id bigint(20),
            ip_address varchar(45),
            user_agent text,
            request_data longtext,
            response_data longtext,
            error_message text,
            status varchar(50),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY gateway_index (gateway),
            KEY event_type_index (event_type),
            KEY severity_index (severity),
            KEY created_at_index (created_at),
            KEY order_id_index (order_id)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Notifications table for webhook events
        $table_name = $wpdb->prefix . 'epg_notifications';
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            gateway varchar(50) NOT NULL,
            order_id bigint(20),
            notification_type varchar(100) NOT NULL,
            status enum('pending','processing','completed','failed') DEFAULT 'pending',
            webhook_data longtext,
            processed_at datetime,
            retry_count int(11) DEFAULT 0,
            max_retries int(11) DEFAULT 3,
            next_retry_at datetime,
            error_message text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY gateway_index (gateway),
            KEY order_id_index (order_id),
            KEY status_index (status),
            KEY notification_type_index (notification_type),
            KEY next_retry_index (next_retry_at)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Payment analytics table
        $table_name = $wpdb->prefix . 'epg_payment_analytics';
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            date date NOT NULL,
            gateway varchar(50) NOT NULL,
            currency varchar(10) NOT NULL,
            total_amount decimal(20,8) NOT NULL,
            transaction_count int(11) DEFAULT 0,
            success_count int(11) DEFAULT 0,
            failed_count int(11) DEFAULT 0,
            refund_count int(11) DEFAULT 0,
            refund_amount decimal(20,8) DEFAULT 0.00000000,
            carbon_footprint decimal(15,10) DEFAULT 0.0000000000,
            carbon_offset_amount decimal(10,2) DEFAULT 0.00,
            average_transaction_time int(11),
            peak_hour tinyint(2),
            conversion_rate decimal(5,2),
            customer_satisfaction decimal(3,2),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY date_gateway_currency (date, gateway, currency),
            KEY gateway_index (gateway),
            KEY date_index (date)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Environmental impact tracking table
        $table_name = $wpdb->prefix . 'epg_environmental_impact';
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            gateway varchar(50) NOT NULL,
            payment_currency varchar(10) NOT NULL,
            payment_amount decimal(20,8) NOT NULL,
            carbon_footprint decimal(15,10) NOT NULL,
            environmental_rating enum('very_low','low','medium','high') NOT NULL,
            offset_enabled tinyint(1) DEFAULT 0,
            offset_amount decimal(10,2) DEFAULT 0.00,
            offset_processed tinyint(1) DEFAULT 0,
            offset_provider varchar(100),
            offset_certificate_id varchar(255),
            trees_planted int(11) DEFAULT 0,
            renewable_energy_kwh decimal(10,4) DEFAULT 0.0000,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            processed_at datetime,
            PRIMARY KEY (id),
            UNIQUE KEY order_id_unique (order_id),
            KEY gateway_index (gateway),
            KEY environmental_rating_index (environmental_rating),
            KEY created_at_index (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Ethereum transactions table
        $table_name = $wpdb->prefix . 'epg_ethereum_transactions';
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            transaction_hash varchar(66) NOT NULL,
            from_address varchar(42) NOT NULL,
            to_address varchar(42) NOT NULL,
            token_contract varchar(42),
            token_symbol varchar(10),
            amount decimal(30,18) NOT NULL,
            gas_price decimal(30,0) NOT NULL,
            gas_limit int(11) NOT NULL,
            gas_used int(11),
            network varchar(20) NOT NULL DEFAULT 'mainnet',
            block_number bigint(20),
            block_hash varchar(66),
            transaction_index int(11),
            confirmations int(11) DEFAULT 0,
            status enum('pending','confirmed','failed') DEFAULT 'pending',
            nonce int(11),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            confirmed_at datetime,
            PRIMARY KEY (id),
            UNIQUE KEY transaction_hash_unique (transaction_hash),
            KEY order_id_index (order_id),
            KEY status_index (status),
            KEY network_index (network),
            KEY block_number_index (block_number)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Gateway settings table for dynamic configuration
        $table_name = $wpdb->prefix . 'epg_gateway_settings';
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            gateway_id varchar(50) NOT NULL,
            setting_key varchar(100) NOT NULL,
            setting_value longtext,
            setting_type enum('string','number','boolean','array','object') DEFAULT 'string',
            is_encrypted tinyint(1) DEFAULT 0,
            environment enum('production','sandbox','test') DEFAULT 'production',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY gateway_setting_env (gateway_id, setting_key, environment),
            KEY gateway_id_index (gateway_id),
            KEY environment_index (environment)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Insert initial crypto rates
        self::insert_initial_data();
        
        // Update database version
        update_option('epg_database_version', '1.0.0');
    }
    
    /**
     * Insert initial data
     */
    private static function insert_initial_data() {
        global $wpdb;
        
        // Initial crypto rates
        $initial_rates = array(
            array('BTC/USD', 43250.00, 'coinbase', 0.000707, 'high'),
            array('ETH/USD', 2340.50, 'coinbase', 0.000000084, 'low'),
            array('BNB/USD', 315.75, 'binance', 0.0000001, 'low'),
            array('USDT/USD', 1.00, 'tether', 0.000005, 'medium'),
            array('USDC/USD', 1.00, 'coinbase', 0.000000084, 'low'),
            array('ADA/USD', 0.42, 'cardano', 0.000000052, 'very_low'),
            array('SOL/USD', 98.30, 'solana', 0.000000166, 'very_low'),
            array('MATIC/USD', 0.82, 'polygon', 0.000000084, 'very_low'),
        );
        
        $table_name = $wpdb->prefix . 'epg_crypto_rates';
        
        foreach ($initial_rates as $rate) {
            $wpdb->insert(
                $table_name,
                array(
                    'currency_pair' => $rate[0],
                    'rate' => $rate[1],
                    'source' => $rate[2],
                    'carbon_intensity' => $rate[3],
                    'environmental_rating' => $rate[4],
                    'timestamp' => current_time('mysql')
                ),
                array('%s', '%f', '%s', '%f', '%s', '%s')
            );
        }
        
        // Insert default gateway settings
        $default_settings = array(
            array('epg_bitcoin', 'network', 'testnet', 'string', 0),
            array('epg_bitcoin', 'confirmation_required', '3', 'number', 0),
            array('epg_ethereum', 'network', 'goerli', 'string', 0),
            array('epg_ethereum', 'gas_limit', '21000', 'number', 0),
            array('epg_coinbase', 'webhook_timeout', '30', 'number', 0),
            array('epg_binance', 'payment_timeout', '15', 'number', 0),
        );
        
        $settings_table = $wpdb->prefix . 'epg_gateway_settings';
        
        foreach ($default_settings as $setting) {
            $wpdb->insert(
                $settings_table,
                array(
                    'gateway_id' => $setting[0],
                    'setting_key' => $setting[1],
                    'setting_value' => $setting[2],
                    'setting_type' => $setting[3],
                    'is_encrypted' => $setting[4],
                    'environment' => 'production'
                ),
                array('%s', '%s', '%s', '%s', '%d', '%s')
            );
        }
    }
    
    /**
     * Drop all tables (for uninstall)
     */
    public static function drop_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'epg_bitcoin_addresses',
            $wpdb->prefix . 'epg_crypto_rates',
            $wpdb->prefix . 'epg_security_logs',
            $wpdb->prefix . 'epg_notifications',
            $wpdb->prefix . 'epg_payment_analytics',
            $wpdb->prefix . 'epg_environmental_impact',
            $wpdb->prefix . 'epg_ethereum_transactions',
            $wpdb->prefix . 'epg_gateway_settings',
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        delete_option('epg_database_version');
    }
    
    /**
     * Check if tables exist
     */
    public static function tables_exist() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'epg_bitcoin_addresses';
        $result = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        
        return $result === $table_name;
    }
    
    /**
     * Update database schema
     */
    public static function update_schema() {
        $current_version = get_option('epg_database_version', '0.0.0');
        
        if (version_compare($current_version, EPG_PLUGIN_VERSION, '<')) {
            self::create_tables();
        }
    }
}
