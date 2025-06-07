<?php
/**
 * Security Handler for Environmental Payment Gateway
 * 
 * Provides security utilities for payment processing,
 * data validation, encryption, and fraud protection.
 * 
 * @package EnvironmentalPaymentGateway
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Security Handler Class
 */
class EPG_Security_Handler {
    
    /**
     * Encryption method
     */
    const ENCRYPTION_METHOD = 'AES-256-CBC';
    
    /**
     * Hash algorithm
     */
    const HASH_ALGORITHM = 'sha256';
    
    /**
     * Max request attempts per IP
     */
    const MAX_ATTEMPTS_PER_IP = 10;
    
    /**
     * Rate limiting window (in seconds)
     */
    const RATE_LIMIT_WINDOW = 300; // 5 minutes
    
    /**
     * Suspicious activity threshold
     */
    const SUSPICIOUS_THRESHOLD = 5;
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init_security_checks'));
        add_action('wp_login_failed', array($this, 'handle_failed_login'));
        add_filter('authenticate', array($this, 'check_brute_force'), 30, 3);
    }
    
    /**
     * Initialize security checks
     */
    public function init_security_checks() {
        // Check for suspicious activity
        $this->check_suspicious_activity();
        
        // Validate request integrity
        $this->validate_request_integrity();
    }
    
    /**
     * Encrypt sensitive data
     */
    public function encrypt($data, $key = null) {
        if (empty($data)) {
            return '';
        }
        
        if (!$key) {
            $key = $this->get_encryption_key();
        }
        
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::ENCRYPTION_METHOD));
        $encrypted = openssl_encrypt($data, self::ENCRYPTION_METHOD, $key, 0, $iv);
        
        return base64_encode($encrypted . '::' . $iv);
    }
    
    /**
     * Decrypt sensitive data
     */
    public function decrypt($encrypted_data, $key = null) {
        if (empty($encrypted_data)) {
            return '';
        }
        
        if (!$key) {
            $key = $this->get_encryption_key();
        }
        
        $data = base64_decode($encrypted_data);
        $parts = explode('::', $data, 2);
        
        if (count($parts) !== 2) {
            return false;
        }
        
        list($encrypted, $iv) = $parts;
        
        return openssl_decrypt($encrypted, self::ENCRYPTION_METHOD, $key, 0, $iv);
    }
    
    /**
     * Generate secure hash
     */
    public function generate_hash($data, $salt = null) {
        if (!$salt) {
            $salt = $this->generate_salt();
        }
        
        return hash(self::HASH_ALGORITHM, $data . $salt);
    }
    
    /**
     * Verify hash
     */
    public function verify_hash($data, $hash, $salt) {
        return hash_equals($hash, $this->generate_hash($data, $salt));
    }
    
    /**
     * Generate secure random salt
     */
    public function generate_salt($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Generate secure token
     */
    public function generate_token($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Validate payment signature
     */
    public function validate_signature($data, $signature, $secret_key, $algorithm = 'sha256') {
        $expected_signature = hash_hmac($algorithm, $data, $secret_key);
        return hash_equals($signature, $expected_signature);
    }
    
    /**
     * Generate payment signature
     */
    public function generate_signature($data, $secret_key, $algorithm = 'sha256') {
        return hash_hmac($algorithm, $data, $secret_key);
    }
    
    /**
     * Sanitize payment data
     */
    public function sanitize_payment_data($data) {
        if (is_array($data)) {
            return array_map(array($this, 'sanitize_payment_data'), $data);
        }
        
        // Remove potentially dangerous characters
        $data = preg_replace('/[<>"\']/', '', $data);
        
        // Sanitize for SQL injection prevention
        $data = sanitize_text_field($data);
        
        return $data;
    }
    
    /**
     * Validate payment amount
     */
    public function validate_amount($amount, $currency) {
        // Check if amount is numeric
        if (!is_numeric($amount)) {
            return new WP_Error('invalid_amount', 'Amount must be numeric');
        }
        
        $amount = floatval($amount);
        
        // Check for negative amounts
        if ($amount <= 0) {
            return new WP_Error('invalid_amount', 'Amount must be positive');
        }
        
        // Check minimum amounts by currency
        $min_amounts = array(
            'VND' => 1000,     // 1,000 VND
            'USD' => 0.50,     // $0.50
            'EUR' => 0.50,     // €0.50
            'GBP' => 0.30,     // £0.30
            'JPY' => 50,       // ¥50
            'BTC' => 0.00001,  // 0.00001 BTC
            'ETH' => 0.001,    // 0.001 ETH
        );
        
        $min_amount = isset($min_amounts[$currency]) ? $min_amounts[$currency] : 0.01;
        
        if ($amount < $min_amount) {
            return new WP_Error('amount_too_small', sprintf('Minimum amount for %s is %s', $currency, $min_amount));
        }
        
        // Check maximum amounts (anti-money laundering)
        $max_amounts = array(
            'VND' => 500000000,  // 500M VND
            'USD' => 25000,      // $25,000
            'EUR' => 25000,      // €25,000
            'GBP' => 20000,      // £20,000
            'JPY' => 2500000,    // ¥2.5M
            'BTC' => 10,         // 10 BTC
            'ETH' => 100,        // 100 ETH
        );
        
        $max_amount = isset($max_amounts[$currency]) ? $max_amounts[$currency] : 10000;
        
        if ($amount > $max_amount) {
            return new WP_Error('amount_too_large', sprintf('Maximum amount for %s is %s', $currency, $max_amount));
        }
        
        return true;
    }
    
    /**
     * Validate IP address
     */
    public function validate_ip_address($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
    
    /**
     * Check if IP is whitelisted
     */
    public function is_ip_whitelisted($ip) {
        $whitelist = get_option('epg_ip_whitelist', array());
        return in_array($ip, $whitelist);
    }
    
    /**
     * Check if IP is blacklisted
     */
    public function is_ip_blacklisted($ip) {
        $blacklist = get_option('epg_ip_blacklist', array());
        return in_array($ip, $blacklist);
    }
    
    /**
     * Add IP to blacklist
     */
    public function blacklist_ip($ip, $reason = '') {
        $blacklist = get_option('epg_ip_blacklist', array());
        $blacklist[$ip] = array(
            'reason' => $reason,
            'added_at' => current_time('mysql'),
        );
        update_option('epg_ip_blacklist', $blacklist);
        
        // Log the action
        $this->log_security_event('ip_blacklisted', array(
            'ip' => $ip,
            'reason' => $reason,
        ));
    }
    
    /**
     * Rate limiting check
     */
    public function check_rate_limit($identifier, $max_attempts = null, $window = null) {
        if (!$max_attempts) {
            $max_attempts = self::MAX_ATTEMPTS_PER_IP;
        }
        
        if (!$window) {
            $window = self::RATE_LIMIT_WINDOW;
        }
        
        $cache_key = 'epg_rate_limit_' . md5($identifier);
        $attempts = get_transient($cache_key);
        
        if ($attempts === false) {
            $attempts = 0;
        }
        
        $attempts++;
        
        if ($attempts > $max_attempts) {
            $this->log_security_event('rate_limit_exceeded', array(
                'identifier' => $identifier,
                'attempts' => $attempts,
            ));
            
            return new WP_Error('rate_limit_exceeded', 'Too many requests. Please try again later.');
        }
        
        set_transient($cache_key, $attempts, $window);
        
        return true;
    }
    
    /**
     * Check for suspicious activity
     */
    public function check_suspicious_activity() {
        $client_ip = $this->get_client_ip();
        
        // Check if IP is blacklisted
        if ($this->is_ip_blacklisted($client_ip)) {
            wp_die('Access denied. Your IP address has been blocked due to suspicious activity.');
        }
        
        // Check for suspicious patterns
        $this->check_suspicious_patterns();
    }
    
    /**
     * Check for suspicious patterns
     */
    private function check_suspicious_patterns() {
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        
        // Check for common attack patterns
        $suspicious_patterns = array(
            'sql injection' => array('union', 'select', 'insert', 'delete', 'drop', 'exec', 'script'),
            'xss' => array('<script', 'javascript:', 'vbscript:', 'onload=', 'onerror='),
            'path traversal' => array('../', '..\\', '/etc/passwd', '/proc/'),
            'malicious bots' => array('sqlmap', 'nikto', 'nmap', 'masscan'),
        );
        
        foreach ($suspicious_patterns as $type => $patterns) {
            foreach ($patterns as $pattern) {
                if (stripos($user_agent . $request_uri, $pattern) !== false) {
                    $this->log_security_event('suspicious_pattern_detected', array(
                        'type' => $type,
                        'pattern' => $pattern,
                        'user_agent' => $user_agent,
                        'request_uri' => $request_uri,
                        'ip' => $this->get_client_ip(),
                    ));
                    
                    // Temporarily block after multiple suspicious attempts
                    $this->handle_suspicious_activity();
                    break 2;
                }
            }
        }
    }
    
    /**
     * Handle suspicious activity
     */
    private function handle_suspicious_activity() {
        $client_ip = $this->get_client_ip();
        $cache_key = 'epg_suspicious_' . md5($client_ip);
        $count = get_transient($cache_key);
        
        if ($count === false) {
            $count = 0;
        }
        
        $count++;
        set_transient($cache_key, $count, 3600); // 1 hour
        
        if ($count >= self::SUSPICIOUS_THRESHOLD) {
            $this->blacklist_ip($client_ip, 'Automated suspicious activity detection');
            wp_die('Access denied. Your IP address has been blocked due to suspicious activity.');
        }
    }
    
    /**
     * Validate request integrity
     */
    public function validate_request_integrity() {
        // Check for required headers in payment requests
        if ($this->is_payment_request()) {
            $required_headers = array('User-Agent', 'Accept');
            
            foreach ($required_headers as $header) {
                $header_key = 'HTTP_' . str_replace('-', '_', strtoupper($header));
                if (!isset($_SERVER[$header_key]) || empty($_SERVER[$header_key])) {
                    $this->log_security_event('missing_required_header', array(
                        'header' => $header,
                        'ip' => $this->get_client_ip(),
                    ));
                    
                    wp_die('Invalid request format.');
                }
            }
        }
    }
    
    /**
     * Check if current request is a payment request
     */
    private function is_payment_request() {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        
        $payment_endpoints = array(
            '/wp-json/epg/v1/payment/',
            '/wc-api/',
            '?wc-api=',
        );
        
        foreach ($payment_endpoints as $endpoint) {
            if (strpos($request_uri, $endpoint) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Handle failed login attempts
     */
    public function handle_failed_login($username) {
        $client_ip = $this->get_client_ip();
        $cache_key = 'epg_failed_login_' . md5($client_ip);
        $attempts = get_transient($cache_key);
        
        if ($attempts === false) {
            $attempts = 0;
        }
        
        $attempts++;
        set_transient($cache_key, $attempts, 3600); // 1 hour
        
        if ($attempts >= 5) {
            $this->blacklist_ip($client_ip, 'Multiple failed login attempts');
        }
        
        $this->log_security_event('failed_login', array(
            'username' => $username,
            'ip' => $client_ip,
            'attempts' => $attempts,
        ));
    }
    
    /**
     * Check for brute force attacks
     */
    public function check_brute_force($user, $username, $password) {
        $client_ip = $this->get_client_ip();
        
        if ($this->is_ip_blacklisted($client_ip)) {
            return new WP_Error('ip_blacklisted', 'Your IP address has been blocked.');
        }
        
        return $user;
    }
    
    /**
     * Get client IP address
     */
    public function get_client_ip() {
        $ip_headers = array(
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        );
        
        foreach ($ip_headers as $header) {
            if (isset($_SERVER[$header]) && !empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                if ($this->validate_ip_address($ip)) {
                    return $ip;
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    }
    
    /**
     * Get encryption key
     */
    private function get_encryption_key() {
        $key = get_option('epg_encryption_key');
        
        if (!$key) {
            $key = wp_generate_password(32, false);
            update_option('epg_encryption_key', $key);
        }
        
        return $key;
    }
    
    /**
     * Log security events
     */
    public function log_security_event($event_type, $data = array()) {
        $log_entry = array(
            'event_type' => $event_type,
            'timestamp' => current_time('mysql'),
            'ip' => $this->get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'data' => $data,
        );
        
        // Store in database
        global $wpdb;
        $security_log_table = $wpdb->prefix . 'epg_security_log';
        
        $wpdb->insert($security_log_table, array(
            'event_type' => $event_type,
            'ip_address' => $log_entry['ip'],
            'user_agent' => $log_entry['user_agent'],
            'event_data' => json_encode($data),
            'created_at' => $log_entry['timestamp']
        ));
        
        // Also log to WooCommerce logger if available
        if (function_exists('wc_get_logger')) {
            $logger = wc_get_logger();
            $logger->warning(sprintf(
                'EPG Security Event: %s - IP: %s - Data: %s',
                $event_type,
                $log_entry['ip'],
                json_encode($data)
            ), array('source' => 'epg-security'));
        }
        
        // Trigger action for external integrations
        do_action('epg_security_event', $event_type, $log_entry);
    }
    
    /**
     * Get security log entries
     */
    public function get_security_log($limit = 100, $event_type = null) {
        global $wpdb;
        $security_log_table = $wpdb->prefix . 'epg_security_log';
        
        $where = array('1=1');
        $values = array();
        
        if ($event_type) {
            $where[] = 'event_type = %s';
            $values[] = $event_type;
        }
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$security_log_table} 
             WHERE " . implode(' AND ', $where) . "
             ORDER BY created_at DESC 
             LIMIT %d",
            array_merge($values, array($limit))
        );
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Clean up old security log entries
     */
    public function cleanup_security_log($days = 30) {
        global $wpdb;
        $security_log_table = $wpdb->prefix . 'epg_security_log';
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$security_log_table} 
             WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
        
        return $deleted;
    }
    
    /**
     * Generate CSP (Content Security Policy) header
     */
    public function generate_csp_header() {
        $csp_directives = array(
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' *.paypal.com *.vnpay.vn *.momo.vn *.zalopay.vn",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: *.paypal.com *.vnpay.vn *.momo.vn *.zalopay.vn",
            "connect-src 'self' *.paypal.com *.vnpay.vn *.momo.vn *.zalopay.vn api.exchangerate-api.com api.coingecko.com",
            "frame-src 'self' *.paypal.com *.vnpay.vn *.momo.vn *.zalopay.vn",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'"
        );
        
        return implode('; ', $csp_directives);
    }
    
    /**
     * Add security headers
     */
    public function add_security_headers() {
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header('Content-Security-Policy: ' . $this->generate_csp_header());
        }
    }
    
    /**
     * Validate webhook signature for different gateways
     */
    public function validate_webhook_signature($gateway_id, $payload, $signature, $secret) {
        switch ($gateway_id) {
            case 'vnpay':
                return $this->validate_vnpay_signature($payload, $signature, $secret);
            case 'momo':
                return $this->validate_momo_signature($payload, $signature, $secret);
            case 'zalopay':
                return $this->validate_zalopay_signature($payload, $signature, $secret);
            case 'stripe':
                return $this->validate_stripe_signature($payload, $signature, $secret);
            case 'paypal':
                return $this->validate_paypal_signature($payload, $signature, $secret);
            default:
                return $this->validate_signature($payload, $signature, $secret);
        }
    }
    
    /**
     * Validate VNPay signature
     */
    private function validate_vnpay_signature($payload, $signature, $secret) {
        ksort($payload);
        $query_string = http_build_query($payload);
        $expected_signature = hash_hmac('sha512', $query_string, $secret);
        return hash_equals($signature, $expected_signature);
    }
    
    /**
     * Validate Momo signature
     */
    private function validate_momo_signature($payload, $signature, $secret) {
        $raw_signature = 'partnerCode=' . $payload['partnerCode'] .
                         '&orderId=' . $payload['orderId'] .
                         '&requestId=' . $payload['requestId'] .
                         '&amount=' . $payload['amount'] .
                         '&orderInfo=' . $payload['orderInfo'] .
                         '&orderType=' . $payload['orderType'] .
                         '&transId=' . $payload['transId'] .
                         '&resultCode=' . $payload['resultCode'] .
                         '&message=' . $payload['message'] .
                         '&payType=' . $payload['payType'] .
                         '&responseTime=' . $payload['responseTime'] .
                         '&extraData=' . $payload['extraData'];
        
        $expected_signature = hash_hmac('sha256', $raw_signature, $secret);
        return hash_equals($signature, $expected_signature);
    }
    
    /**
     * Validate ZaloPay signature
     */
    private function validate_zalopay_signature($payload, $signature, $secret) {
        $data = $payload['data'];
        $expected_signature = hash_hmac('sha256', $data, $secret);
        return hash_equals($signature, $expected_signature);
    }
    
    /**
     * Validate Stripe signature
     */
    private function validate_stripe_signature($payload, $signature, $secret) {
        $elements = explode(',', $signature);
        $timestamp = null;
        $signatures = array();
        
        foreach ($elements as $element) {
            $parts = explode('=', $element, 2);
            if (count($parts) == 2) {
                if ($parts[0] === 't') {
                    $timestamp = $parts[1];
                } elseif ($parts[0] === 'v1') {
                    $signatures[] = $parts[1];
                }
            }
        }
        
        if (!$timestamp || empty($signatures)) {
            return false;
        }
        
        $signed_payload = $timestamp . '.' . $payload;
        $expected_signature = hash_hmac('sha256', $signed_payload, $secret);
        
        foreach ($signatures as $sig) {
            if (hash_equals($expected_signature, $sig)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Validate PayPal signature
     */
    private function validate_paypal_signature($payload, $signature, $secret) {
        // PayPal uses certificate-based validation
        // This would require additional implementation based on PayPal's specific requirements
        return true; // Placeholder
    }
}
