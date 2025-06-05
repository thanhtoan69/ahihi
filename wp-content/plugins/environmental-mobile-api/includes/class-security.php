<?php
/**
 * Security Class
 * 
 * Handles security validation, sanitization, and protection for the mobile API
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Mobile_API_Security {
    
    private $settings;
    
    public function __construct() {
        $this->settings = get_option('environmental_mobile_api_settings', array());
        
        add_filter('rest_pre_dispatch', array($this, 'security_checks'), 1, 3);
        add_action('rest_api_init', array($this, 'add_security_headers'));
    }
    
    /**
     * Perform security checks on requests
     */
    public function security_checks($result, $server, $request) {
        // Skip if not our API
        if (strpos($request->get_route(), '/environmental-mobile-api/') === false) {
            return $result;
        }
        
        // Check for malicious requests
        if ($this->is_malicious_request($request)) {
            return new WP_Error(
                'malicious_request',
                'Request blocked for security reasons',
                array('status' => 403)
            );
        }
        
        // Validate content type for POST/PUT requests
        if (in_array($request->get_method(), array('POST', 'PUT', 'PATCH'))) {
            if (!$this->validate_content_type($request)) {
                return new WP_Error(
                    'invalid_content_type',
                    'Invalid or missing Content-Type header',
                    array('status' => 400)
                );
            }
        }
        
        // Check request size
        if (!$this->validate_request_size($request)) {
            return new WP_Error(
                'request_too_large',
                'Request payload too large',
                array('status' => 413)
            );
        }
        
        // Validate request parameters
        $validation_result = $this->validate_request_parameters($request);
        if (is_wp_error($validation_result)) {
            return $validation_result;
        }
        
        return $result;
    }
    
    /**
     * Add security headers
     */
    public function add_security_headers() {
        add_filter('rest_post_dispatch', array($this, 'add_response_headers'), 10, 3);
    }
    
    /**
     * Add security headers to responses
     */
    public function add_response_headers($response, $server, $request) {
        if (strpos($request->get_route(), '/environmental-mobile-api/') === false) {
            return $response;
        }
        
        // Security headers
        $response->header('X-Content-Type-Options', 'nosniff');
        $response->header('X-Frame-Options', 'DENY');
        $response->header('X-XSS-Protection', '1; mode=block');
        $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->header('Content-Security-Policy', "default-src 'none'");
        
        // API-specific headers
        $response->header('X-API-Version', ENVIRONMENTAL_MOBILE_API_VERSION);
        $response->header('X-Request-ID', $this->generate_request_id());
        
        return $response;
    }
    
    /**
     * Check if request is malicious
     */
    private function is_malicious_request($request) {
        $suspicious_patterns = array(
            // SQL injection patterns
            '/(\s*(union|select|insert|update|delete|drop|create|alter|exec|execute)\s+)/i',
            '/(\s*(or|and)\s+["\']?\d+["\']?\s*=\s*["\']?\d+)/i',
            '/(\s*["\']?\d+["\']?\s*(or|and)\s+["\']?\d+["\']?\s*=\s*["\']?\d+)/i',
            
            // XSS patterns
            '/<script[\s\S]*?>[\s\S]*?<\/script>/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            
            // Path traversal
            '/\.\.\//',
            '/\.\.\\\\/',
            
            // Command injection
            '/;\s*(ls|cat|wget|curl|ping|nslookup|dig|nc|netcat|telnet|ssh|ftp)/i',
            
            // PHP code injection
            '/<\?php/i',
            '/eval\s*\(/i',
            '/system\s*\(/i',
            '/shell_exec\s*\(/i'
        );
        
        // Check all request data
        $request_data = array(
            'body' => $request->get_body(),
            'params' => serialize($request->get_params()),
            'headers' => serialize($request->get_headers())
        );
        
        foreach ($request_data as $data) {
            foreach ($suspicious_patterns as $pattern) {
                if (preg_match($pattern, $data)) {
                    $this->log_security_event('malicious_pattern_detected', array(
                        'pattern' => $pattern,
                        'data' => substr($data, 0, 200),
                        'ip' => $this->get_client_ip(),
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                    ));
                    
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Validate content type
     */
    private function validate_content_type($request) {
        $content_type = $request->get_header('content_type');
        
        if (!$content_type) {
            return false;
        }
        
        $allowed_types = array(
            'application/json',
            'multipart/form-data',
            'application/x-www-form-urlencoded'
        );
        
        foreach ($allowed_types as $type) {
            if (strpos($content_type, $type) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Validate request size
     */
    private function validate_request_size($request) {
        $max_size = $this->get_max_request_size();
        $body = $request->get_body();
        
        if (strlen($body) > $max_size) {
            $this->log_security_event('request_too_large', array(
                'size' => strlen($body),
                'max_size' => $max_size,
                'ip' => $this->get_client_ip()
            ));
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Get maximum request size
     */
    private function get_max_request_size() {
        // Default to 10MB for file uploads, 1MB for other requests
        $route = $_SERVER['REQUEST_URI'] ?? '';
        
        if (strpos($route, '/upload') !== false || strpos($route, '/media') !== false) {
            return 10 * 1024 * 1024; // 10MB
        }
        
        return 1024 * 1024; // 1MB
    }
    
    /**
     * Validate request parameters
     */
    private function validate_request_parameters($request) {
        $params = $request->get_params();
        
        foreach ($params as $key => $value) {
            // Check parameter name
            if (!$this->is_valid_parameter_name($key)) {
                return new WP_Error(
                    'invalid_parameter_name',
                    'Invalid parameter name: ' . $key,
                    array('status' => 400)
                );
            }
            
            // Check parameter value
            if (!$this->is_safe_parameter_value($value)) {
                return new WP_Error(
                    'unsafe_parameter_value',
                    'Unsafe parameter value detected',
                    array('status' => 400)
                );
            }
        }
        
        return true;
    }
    
    /**
     * Check if parameter name is valid
     */
    private function is_valid_parameter_name($name) {
        // Allow alphanumeric, underscore, dash, dot
        return preg_match('/^[a-zA-Z0-9_\-\.]+$/', $name);
    }
    
    /**
     * Check if parameter value is safe
     */
    private function is_safe_parameter_value($value) {
        if (is_array($value)) {
            foreach ($value as $item) {
                if (!$this->is_safe_parameter_value($item)) {
                    return false;
                }
            }
            return true;
        }
        
        if (!is_string($value)) {
            return true; // Non-string values are generally safe
        }
        
        // Check for dangerous patterns
        $dangerous_patterns = array(
            '/\x00/', // null bytes
            '/<script/i',
            '/javascript:/i',
            '/<iframe/i',
            '/<object/i',
            '/<embed/i'
        );
        
        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Sanitize input data
     */
    public function sanitize_input($data, $type = 'text') {
        if (is_array($data)) {
            return array_map(function($item) use ($type) {
                return $this->sanitize_input($item, $type);
            }, $data);
        }
        
        switch ($type) {
            case 'email':
                return sanitize_email($data);
            
            case 'url':
                return esc_url_raw($data);
            
            case 'int':
                return (int) $data;
            
            case 'float':
                return (float) $data;
            
            case 'boolean':
                return (bool) $data;
            
            case 'html':
                return wp_kses_post($data);
            
            case 'filename':
                return sanitize_file_name($data);
            
            case 'key':
                return sanitize_key($data);
            
            case 'slug':
                return sanitize_title($data);
            
            case 'text':
            default:
                return sanitize_text_field($data);
        }
    }
    
    /**
     * Validate input data
     */
    public function validate_input($data, $rules) {
        $errors = array();
        
        foreach ($rules as $field => $rule_set) {
            $value = isset($data[$field]) ? $data[$field] : null;
            
            foreach ($rule_set as $rule => $rule_value) {
                switch ($rule) {
                    case 'required':
                        if ($rule_value && ($value === null || $value === '')) {
                            $errors[$field][] = 'Field is required';
                        }
                        break;
                    
                    case 'type':
                        if ($value !== null && !$this->validate_type($value, $rule_value)) {
                            $errors[$field][] = "Field must be of type $rule_value";
                        }
                        break;
                    
                    case 'min_length':
                        if ($value !== null && strlen($value) < $rule_value) {
                            $errors[$field][] = "Field must be at least $rule_value characters";
                        }
                        break;
                    
                    case 'max_length':
                        if ($value !== null && strlen($value) > $rule_value) {
                            $errors[$field][] = "Field must not exceed $rule_value characters";
                        }
                        break;
                    
                    case 'email':
                        if ($value !== null && $rule_value && !is_email($value)) {
                            $errors[$field][] = 'Field must be a valid email address';
                        }
                        break;
                    
                    case 'url':
                        if ($value !== null && $rule_value && !filter_var($value, FILTER_VALIDATE_URL)) {
                            $errors[$field][] = 'Field must be a valid URL';
                        }
                        break;
                    
                    case 'pattern':
                        if ($value !== null && !preg_match($rule_value, $value)) {
                            $errors[$field][] = 'Field format is invalid';
                        }
                        break;
                    
                    case 'in':
                        if ($value !== null && !in_array($value, $rule_value)) {
                            $errors[$field][] = 'Field value is not allowed';
                        }
                        break;
                }
            }
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * Validate data type
     */
    private function validate_type($value, $type) {
        switch ($type) {
            case 'string':
                return is_string($value);
            case 'int':
            case 'integer':
                return is_int($value) || ctype_digit($value);
            case 'float':
            case 'double':
                return is_float($value) || is_numeric($value);
            case 'boolean':
            case 'bool':
                return is_bool($value) || in_array($value, array('true', 'false', '1', '0', 1, 0));
            case 'array':
                return is_array($value);
            case 'object':
                return is_object($value) || (is_string($value) && json_decode($value) !== null);
            default:
                return true;
        }
    }
    
    /**
     * Generate request ID
     */
    private function generate_request_id() {
        return uniqid('req_', true);
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    }
    
    /**
     * Log security event
     */
    private function log_security_event($event_type, $data) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'environmental_mobile_api_logs',
            array(
                'user_id' => get_current_user_id() ?: null,
                'endpoint' => 'SECURITY',
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
                'ip_address' => $this->get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'request_data' => json_encode(array(
                    'event_type' => $event_type,
                    'data' => $data
                )),
                'response_code' => 403,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
        );
        
        // Also log to PHP error log for critical security events
        error_log("Environmental Mobile API Security Event: $event_type - " . json_encode($data));
    }
    
    /**
     * Check if IP is whitelisted
     */
    public function is_ip_whitelisted($ip) {
        $whitelist = get_option('environmental_mobile_api_ip_whitelist', array());
        
        if (empty($whitelist)) {
            return true; // No whitelist means all IPs are allowed
        }
        
        foreach ($whitelist as $allowed_ip) {
            if ($this->ip_in_range($ip, $allowed_ip)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if IP is in range
     */
    private function ip_in_range($ip, $range) {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }
        
        list($range_ip, $netmask) = explode('/', $range, 2);
        
        $range_decimal = ip2long($range_ip);
        $ip_decimal = ip2long($ip);
        $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
        $netmask_decimal = ~ $wildcard_decimal;
        
        return ($ip_decimal & $netmask_decimal) === ($range_decimal & $netmask_decimal);
    }
    
    /**
     * Encrypt sensitive data
     */
    public function encrypt($data) {
        $key = $this->get_encryption_key();
        $iv = openssl_random_pseudo_bytes(16);
        
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt sensitive data
     */
    public function decrypt($encrypted_data) {
        $key = $this->get_encryption_key();
        $data = base64_decode($encrypted_data);
        
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    /**
     * Get encryption key
     */
    private function get_encryption_key() {
        $key = get_option('environmental_mobile_api_encryption_key');
        
        if (!$key) {
            $key = wp_generate_password(32, true, true);
            update_option('environmental_mobile_api_encryption_key', $key);
        }
        
        return $key;
    }
    
    /**
     * Hash password securely
     */
    public function hash_password($password) {
        return wp_hash_password($password);
    }
    
    /**
     * Verify password hash
     */
    public function verify_password($password, $hash) {
        return wp_check_password($password, $hash);
    }
    
    /**
     * Generate secure random token
     */
    public function generate_token($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Get security statistics
     */
    public function get_security_stats() {
        global $wpdb;
        
        return array(
            'blocked_requests_today' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}environmental_mobile_api_logs 
                 WHERE endpoint = 'SECURITY' AND DATE(created_at) = CURDATE()"
            ),
            'blocked_requests_week' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}environmental_mobile_api_logs 
                 WHERE endpoint = 'SECURITY' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            ),
            'unique_blocked_ips' => $wpdb->get_var(
                "SELECT COUNT(DISTINCT ip_address) FROM {$wpdb->prefix}environmental_mobile_api_logs 
                 WHERE endpoint = 'SECURITY' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            )
        );
    }
}
