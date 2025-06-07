<?php
/**
 * Currency Converter for Environmental Payment Gateway
 * 
 * Handles real-time currency conversion with support for
 * fiat currencies and cryptocurrencies.
 * 
 * @package EnvironmentalPaymentGateway
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Currency Converter Class
 */
class EPG_Currency_Converter {
    
    /**
     * Exchange rates cache key
     */
    const CACHE_KEY = 'epg_exchange_rates';
    
    /**
     * Crypto rates cache key
     */
    const CRYPTO_CACHE_KEY = 'epg_crypto_rates';
    
    /**
     * Cache expiration time (1 hour)
     */
    const CACHE_EXPIRATION = 3600;
    
    /**
     * Supported fiat currencies
     */
    private $supported_fiat_currencies = array(
        'USD', 'EUR', 'VND', 'JPY', 'GBP', 'AUD', 'CAD', 'CHF', 'CNY', 'KRW',
        'SGD', 'THB', 'MYR', 'IDR', 'PHP', 'INR', 'HKD', 'TWD', 'NZD', 'SEK',
        'NOK', 'DKK', 'PLN', 'CZK', 'HUF', 'RON', 'BGN', 'HRK', 'RUB', 'BRL',
        'MXN', 'ARS', 'CLP', 'COP', 'PEN', 'UYU', 'ZAR', 'EGP', 'MAD', 'TND'
    );
    
    /**
     * Supported cryptocurrencies
     */
    private $supported_crypto_currencies = array(
        'BTC', 'ETH', 'BNB', 'ADA', 'XRP', 'SOL', 'DOT', 'DOGE', 'AVAX', 'SHIB',
        'MATIC', 'LTC', 'UNI', 'LINK', 'BCH', 'XLM', 'ALGO', 'VET', 'ICP', 'FIL'
    );
    
    /**
     * API endpoints
     */
    private $fiat_api_url = 'https://api.exchangerate-api.com/v4/latest/';
    private $crypto_api_url = 'https://api.coingecko.com/api/v3/simple/price';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Schedule rate updates
        add_action('epg_update_exchange_rates', array($this, 'update_exchange_rates'));
        add_action('epg_update_crypto_rates', array($this, 'update_crypto_rates'));
        
        if (!wp_next_scheduled('epg_update_exchange_rates')) {
            wp_schedule_event(time(), 'hourly', 'epg_update_exchange_rates');
        }
        
        if (!wp_next_scheduled('epg_update_crypto_rates')) {
            wp_schedule_event(time(), 'hourly', 'epg_update_crypto_rates');
        }
    }
    
    /**
     * Convert amount from one currency to another
     */
    public function convert($amount, $from_currency, $to_currency) {
        $from_currency = strtoupper($from_currency);
        $to_currency = strtoupper($to_currency);
        
        if ($from_currency === $to_currency) {
            return $amount;
        }
        
        $exchange_rate = $this->get_exchange_rate($from_currency, $to_currency);
        
        if (is_wp_error($exchange_rate)) {
            return $exchange_rate;
        }
        
        return round($amount * $exchange_rate, 8);
    }
    
    /**
     * Get exchange rate between two currencies
     */
    public function get_exchange_rate($from_currency, $to_currency) {
        $from_currency = strtoupper($from_currency);
        $to_currency = strtoupper($to_currency);
        
        if ($from_currency === $to_currency) {
            return 1;
        }
        
        // Determine currency types
        $from_is_crypto = $this->is_cryptocurrency($from_currency);
        $to_is_crypto = $this->is_cryptocurrency($to_currency);
        
        if ($from_is_crypto && $to_is_crypto) {
            // Crypto to crypto conversion (via USD)
            return $this->get_crypto_to_crypto_rate($from_currency, $to_currency);
        } elseif ($from_is_crypto) {
            // Crypto to fiat conversion
            return $this->get_crypto_to_fiat_rate($from_currency, $to_currency);
        } elseif ($to_is_crypto) {
            // Fiat to crypto conversion
            return $this->get_fiat_to_crypto_rate($from_currency, $to_currency);
        } else {
            // Fiat to fiat conversion
            return $this->get_fiat_to_fiat_rate($from_currency, $to_currency);
        }
    }
    
    /**
     * Get fiat to fiat exchange rate
     */
    private function get_fiat_to_fiat_rate($from_currency, $to_currency) {
        $rates = $this->get_fiat_exchange_rates($from_currency);
        
        if (is_wp_error($rates)) {
            return $rates;
        }
        
        if (!isset($rates[$to_currency])) {
            return new WP_Error('currency_not_supported', sprintf('Currency %s not supported', $to_currency));
        }
        
        return $rates[$to_currency];
    }
    
    /**
     * Get crypto to fiat exchange rate
     */
    private function get_crypto_to_fiat_rate($from_currency, $to_currency) {
        $crypto_rates = $this->get_crypto_exchange_rates();
        
        if (is_wp_error($crypto_rates)) {
            return $crypto_rates;
        }
        
        $crypto_symbol = strtolower($from_currency);
        $fiat_symbol = strtolower($to_currency);
        
        if (!isset($crypto_rates[$crypto_symbol][$fiat_symbol])) {
            return new WP_Error('rate_not_available', sprintf('Rate from %s to %s not available', $from_currency, $to_currency));
        }
        
        return $crypto_rates[$crypto_symbol][$fiat_symbol];
    }
    
    /**
     * Get fiat to crypto exchange rate
     */
    private function get_fiat_to_crypto_rate($from_currency, $to_currency) {
        $crypto_rate = $this->get_crypto_to_fiat_rate($to_currency, $from_currency);
        
        if (is_wp_error($crypto_rate)) {
            return $crypto_rate;
        }
        
        return 1 / $crypto_rate;
    }
    
    /**
     * Get crypto to crypto exchange rate (via USD)
     */
    private function get_crypto_to_crypto_rate($from_currency, $to_currency) {
        $from_usd_rate = $this->get_crypto_to_fiat_rate($from_currency, 'USD');
        $to_usd_rate = $this->get_crypto_to_fiat_rate($to_currency, 'USD');
        
        if (is_wp_error($from_usd_rate)) {
            return $from_usd_rate;
        }
        
        if (is_wp_error($to_usd_rate)) {
            return $to_usd_rate;
        }
        
        return $from_usd_rate / $to_usd_rate;
    }
    
    /**
     * Get fiat exchange rates
     */
    private function get_fiat_exchange_rates($base_currency = 'USD') {
        $cache_key = self::CACHE_KEY . '_' . $base_currency;
        $cached_rates = get_transient($cache_key);
        
        if ($cached_rates !== false) {
            return $cached_rates;
        }
        
        // Fetch from API
        $response = wp_remote_get($this->fiat_api_url . $base_currency);
        
        if (is_wp_error($response)) {
            return new WP_Error('api_error', 'Failed to fetch exchange rates');
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['rates'])) {
            return new WP_Error('invalid_response', 'Invalid API response');
        }
        
        $rates = $data['rates'];
        
        // Cache for 1 hour
        set_transient($cache_key, $rates, self::CACHE_EXPIRATION);
        
        return $rates;
    }
    
    /**
     * Get cryptocurrency exchange rates
     */
    private function get_crypto_exchange_rates() {
        $cached_rates = get_transient(self::CRYPTO_CACHE_KEY);
        
        if ($cached_rates !== false) {
            return $cached_rates;
        }
        
        // Build API URL
        $crypto_symbols = implode(',', array_map('strtolower', $this->supported_crypto_currencies));
        $fiat_symbols = implode(',', array_map('strtolower', $this->supported_fiat_currencies));
        
        $url = add_query_arg(array(
            'ids' => $crypto_symbols,
            'vs_currencies' => $fiat_symbols,
            'include_24hr_change' => 'true'
        ), $this->crypto_api_url);
        
        // Fetch from API
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            return new WP_Error('api_error', 'Failed to fetch crypto rates');
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data) {
            return new WP_Error('invalid_response', 'Invalid API response');
        }
        
        // Store in database for analytics
        $this->store_crypto_rates($data);
        
        // Cache for 1 hour
        set_transient(self::CRYPTO_CACHE_KEY, $data, self::CACHE_EXPIRATION);
        
        return $data;
    }
    
    /**
     * Store crypto rates in database for analytics
     */
    private function store_crypto_rates($rates_data) {
        global $wpdb;
        $crypto_rates_table = $wpdb->prefix . 'epg_crypto_rates';
        
        foreach ($rates_data as $symbol => $rates) {
            foreach ($rates as $currency => $rate) {
                if ($currency === 'usd_24h_change') {
                    continue; // Skip change percentage
                }
                
                $change_24h = isset($rates['usd_24h_change']) ? $rates['usd_24h_change'] : 0;
                
                $wpdb->replace($crypto_rates_table, array(
                    'crypto_symbol' => strtoupper($symbol),
                    'fiat_currency' => strtoupper($currency),
                    'rate' => $rate,
                    'change_24h' => $change_24h,
                    'updated_at' => current_time('mysql')
                ));
            }
        }
    }
    
    /**
     * Check if currency is cryptocurrency
     */
    public function is_cryptocurrency($currency) {
        return in_array(strtoupper($currency), $this->supported_crypto_currencies);
    }
    
    /**
     * Check if currency is fiat currency
     */
    public function is_fiat_currency($currency) {
        return in_array(strtoupper($currency), $this->supported_fiat_currencies);
    }
    
    /**
     * Get supported currencies
     */
    public function get_supported_currencies() {
        return array(
            'fiat' => $this->supported_fiat_currencies,
            'crypto' => $this->supported_crypto_currencies,
        );
    }
    
    /**
     * Get supported fiat currencies
     */
    public function get_supported_fiat_currencies() {
        return $this->supported_fiat_currencies;
    }
    
    /**
     * Get supported cryptocurrencies
     */
    public function get_supported_crypto_currencies() {
        return $this->supported_crypto_currencies;
    }
    
    /**
     * Format currency amount
     */
    public function format_amount($amount, $currency, $decimals = null) {
        $currency = strtoupper($currency);
        
        // Default decimals based on currency type
        if ($decimals === null) {
            if ($this->is_cryptocurrency($currency)) {
                $decimals = 8;
            } else {
                $decimals = 2;
            }
        }
        
        // Special formatting for some currencies
        switch ($currency) {
            case 'VND':
                $decimals = 0; // Vietnamese Dong doesn't use decimals
                break;
            case 'JPY':
            case 'KRW':
                $decimals = 0; // Yen and Won don't use decimals
                break;
        }
        
        return number_format($amount, $decimals, '.', ',');
    }
    
    /**
     * Get currency symbol
     */
    public function get_currency_symbol($currency) {
        $currency = strtoupper($currency);
        
        $symbols = array(
            'USD' => '$',
            'EUR' => '€',
            'VND' => '₫',
            'JPY' => '¥',
            'GBP' => '£',
            'AUD' => 'A$',
            'CAD' => 'C$',
            'CHF' => 'CHF',
            'CNY' => '¥',
            'KRW' => '₩',
            'SGD' => 'S$',
            'THB' => '฿',
            'MYR' => 'RM',
            'IDR' => 'Rp',
            'PHP' => '₱',
            'INR' => '₹',
            'HKD' => 'HK$',
            'BTC' => '₿',
            'ETH' => 'Ξ',
        );
        
        return isset($symbols[$currency]) ? $symbols[$currency] : $currency;
    }
    
    /**
     * Update exchange rates (scheduled task)
     */
    public function update_exchange_rates() {
        // Clear fiat rates cache
        $base_currencies = array('USD', 'EUR', 'VND');
        foreach ($base_currencies as $base) {
            delete_transient(self::CACHE_KEY . '_' . $base);
        }
        
        // Pre-fetch popular rates
        $this->get_fiat_exchange_rates('USD');
        $this->get_fiat_exchange_rates('EUR');
        $this->get_fiat_exchange_rates('VND');
        
        do_action('epg_exchange_rates_updated');
    }
    
    /**
     * Update crypto rates (scheduled task)
     */
    public function update_crypto_rates() {
        // Clear crypto rates cache
        delete_transient(self::CRYPTO_CACHE_KEY);
        
        // Pre-fetch crypto rates
        $this->get_crypto_exchange_rates();
        
        do_action('epg_crypto_rates_updated');
    }
    
    /**
     * Get historical rates for analytics
     */
    public function get_historical_crypto_rates($symbol, $fiat_currency, $days = 30) {
        global $wpdb;
        $crypto_rates_table = $wpdb->prefix . 'epg_crypto_rates';
        
        $rates = $wpdb->get_results($wpdb->prepare(
            "SELECT rate, updated_at 
             FROM {$crypto_rates_table} 
             WHERE crypto_symbol = %s 
             AND fiat_currency = %s 
             AND updated_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             ORDER BY updated_at DESC",
            strtoupper($symbol),
            strtoupper($fiat_currency),
            $days
        ));
        
        return $rates;
    }
    
    /**
     * Get rate trend (increase/decrease percentage)
     */
    public function get_rate_trend($symbol, $fiat_currency = 'USD', $period = '24h') {
        global $wpdb;
        $crypto_rates_table = $wpdb->prefix . 'epg_crypto_rates';
        
        $interval_map = array(
            '1h' => '1 HOUR',
            '24h' => '1 DAY',
            '7d' => '7 DAY',
            '30d' => '30 DAY',
        );
        
        if (!isset($interval_map[$period])) {
            return new WP_Error('invalid_period', 'Invalid period specified');
        }
        
        $current_rate = $wpdb->get_var($wpdb->prepare(
            "SELECT rate FROM {$crypto_rates_table} 
             WHERE crypto_symbol = %s AND fiat_currency = %s 
             ORDER BY updated_at DESC LIMIT 1",
            strtoupper($symbol),
            strtoupper($fiat_currency)
        ));
        
        $past_rate = $wpdb->get_var($wpdb->prepare(
            "SELECT rate FROM {$crypto_rates_table} 
             WHERE crypto_symbol = %s AND fiat_currency = %s 
             AND updated_at <= DATE_SUB(NOW(), INTERVAL {$interval_map[$period]})
             ORDER BY updated_at DESC LIMIT 1",
            strtoupper($symbol),
            strtoupper($fiat_currency)
        ));
        
        if (!$current_rate || !$past_rate) {
            return null;
        }
        
        $change_percentage = (($current_rate - $past_rate) / $past_rate) * 100;
        
        return array(
            'current_rate' => $current_rate,
            'past_rate' => $past_rate,
            'change_percentage' => round($change_percentage, 2),
            'change_direction' => $change_percentage >= 0 ? 'up' : 'down',
        );
    }
    
    /**
     * Clean up old rate data
     */
    public function cleanup_old_rates($days = 90) {
        global $wpdb;
        $crypto_rates_table = $wpdb->prefix . 'epg_crypto_rates';
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$crypto_rates_table} 
             WHERE updated_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
        
        return $deleted;
    }
}
