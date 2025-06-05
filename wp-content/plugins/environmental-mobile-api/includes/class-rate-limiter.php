<?php
/**
 * Rate Limiter Class
 * 
 * Handles API rate limiting and throttling
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Mobile_API_Rate_Limiter {
    
    private $settings;
    
    public function __construct() {
        $this->settings = get_option('environmental_mobile_api_settings', array());
        
        add_filter('rest_pre_dispatch', array($this, 'check_rate_limit'), 10, 3);
    }
    
    /**
     * Check rate limit for request
     */
    public function check_rate_limit($result, $server, $request) {
        // Skip if not our API
        if (strpos($request->get_route(), '/environmental-mobile-api/') === false) {
            return $result;
        }
        
        // Skip for certain endpoints (like health check)
        $exempt_endpoints = array('/health', '/info');
        foreach ($exempt_endpoints as $endpoint) {
            if (strpos($request->get_route(), $endpoint) !== false) {
                return $result;
            }
        }
        
        $identifier = $this->get_rate_limit_identifier($request);
        $endpoint = $this->normalize_endpoint($request->get_route());
        
        if ($this->is_rate_limited($identifier, $endpoint)) {
            $retry_after = $this->get_retry_after($identifier, $endpoint);
            
            return new WP_Error(
                'rate_limit_exceeded',
                'Rate limit exceeded. Try again later.',
                array(
                    'status' => 429,
                    'headers' => array(
                        'Retry-After' => $retry_after,
                        'X-RateLimit-Limit' => $this->get_rate_limit(),
                        'X-RateLimit-Remaining' => 0,
                        'X-RateLimit-Reset' => time() + $retry_after
                    )
                )
            );
        }
        
        // Increment request count
        $this->increment_request_count($identifier, $endpoint);
        
        // Add rate limit headers to response
        add_filter('rest_post_dispatch', array($this, 'add_rate_limit_headers'), 10, 3);
        
        return $result;
    }
    
    /**
     * Add rate limit headers to response
     */
    public function add_rate_limit_headers($response, $server, $request) {
        if (strpos($request->get_route(), '/environmental-mobile-api/') === false) {
            return $response;
        }
        
        $identifier = $this->get_rate_limit_identifier($request);
        $endpoint = $this->normalize_endpoint($request->get_route());
        
        $limit = $this->get_rate_limit();
        $remaining = $this->get_remaining_requests($identifier, $endpoint);
        $reset_time = $this->get_reset_time($identifier, $endpoint);
        
        $response->header('X-RateLimit-Limit', $limit);
        $response->header('X-RateLimit-Remaining', max(0, $remaining));
        $response->header('X-RateLimit-Reset', $reset_time);
        
        return $response;
    }
    
    /**
     * Get rate limit identifier
     */
    private function get_rate_limit_identifier($request) {
        // Use user ID if authenticated
        $user_id = get_current_user_id();
        if ($user_id) {
            return 'user_' . $user_id;
        }
        
        // Use API key if present
        $api_key = $this->get_api_key_from_request($request);
        if ($api_key) {
            return 'api_key_' . $api_key;
        }
        
        // Fall back to IP address
        return 'ip_' . $this->get_client_ip();
    }
    
    /**
     * Get API key from request
     */
    private function get_api_key_from_request($request) {
        $headers = $request->get_headers();
        
        if (isset($headers['x_api_key'][0])) {
            return $headers['x_api_key'][0];
        }
        
        $params = $request->get_params();
        if (isset($params['api_key'])) {
            return $params['api_key'];
        }
        
        return null;
    }
    
    /**
     * Normalize endpoint for rate limiting
     */
    private function normalize_endpoint($route) {
        // Remove dynamic parts (IDs) from the route
        $route = preg_replace('/\/\d+/', '/{id}', $route);
        $route = preg_replace('/\/[a-f0-9-]{36}/', '/{uuid}', $route);
        
        return $route;
    }
    
    /**
     * Check if request is rate limited
     */
    private function is_rate_limited($identifier, $endpoint) {
        global $wpdb;
        
        $window_start = $this->get_current_window_start();
        $limit = $this->get_rate_limit($endpoint);
        
        $current_requests = $wpdb->get_var($wpdb->prepare(
            "SELECT requests FROM {$wpdb->prefix}environmental_mobile_api_rate_limits 
             WHERE identifier = %s AND endpoint = %s AND window_start = %s",
            $identifier,
            $endpoint,
            $window_start
        ));
        
        return $current_requests && $current_requests >= $limit;
    }
    
    /**
     * Increment request count
     */
    private function increment_request_count($identifier, $endpoint) {
        global $wpdb;
        
        $window_start = $this->get_current_window_start();
        
        $wpdb->query($wpdb->prepare(
            "INSERT INTO {$wpdb->prefix}environmental_mobile_api_rate_limits 
             (identifier, endpoint, requests, window_start) 
             VALUES (%s, %s, 1, %s)
             ON DUPLICATE KEY UPDATE requests = requests + 1",
            $identifier,
            $endpoint,
            $window_start
        ));
    }
    
    /**
     * Get current window start time
     */
    private function get_current_window_start() {
        $window_size = isset($this->settings['rate_limit_window']) ? $this->settings['rate_limit_window'] : 3600;
        $window_number = floor(time() / $window_size);
        
        return date('Y-m-d H:i:s', $window_number * $window_size);
    }
    
    /**
     * Get rate limit for endpoint
     */
    private function get_rate_limit($endpoint = null) {
        $default_limit = isset($this->settings['rate_limit_requests']) ? $this->settings['rate_limit_requests'] : 1000;
        
        // Custom limits for specific endpoints
        $endpoint_limits = array(
            '/auth/login' => 10,
            '/auth/register' => 5,
            '/media/upload' => 50,
            '/notifications/send' => 100
        );
        
        if ($endpoint && isset($endpoint_limits[$endpoint])) {
            return $endpoint_limits[$endpoint];
        }
        
        return $default_limit;
    }
    
    /**
     * Get remaining requests
     */
    private function get_remaining_requests($identifier, $endpoint) {
        global $wpdb;
        
        $window_start = $this->get_current_window_start();
        $limit = $this->get_rate_limit($endpoint);
        
        $current_requests = $wpdb->get_var($wpdb->prepare(
            "SELECT requests FROM {$wpdb->prefix}environmental_mobile_api_rate_limits 
             WHERE identifier = %s AND endpoint = %s AND window_start = %s",
            $identifier,
            $endpoint,
            $window_start
        ));
        
        return $limit - ($current_requests ?: 0);
    }
    
    /**
     * Get reset time
     */
    private function get_reset_time($identifier, $endpoint) {
        $window_size = isset($this->settings['rate_limit_window']) ? $this->settings['rate_limit_window'] : 3600;
        $window_number = floor(time() / $window_size);
        
        return ($window_number + 1) * $window_size;
    }
    
    /**
     * Get retry after seconds
     */
    private function get_retry_after($identifier, $endpoint) {
        return $this->get_reset_time($identifier, $endpoint) - time();
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
     * Reset rate limit for identifier
     */
    public function reset_rate_limit($identifier, $endpoint = null) {
        global $wpdb;
        
        $where = array('identifier' => $identifier);
        $where_format = array('%s');
        
        if ($endpoint) {
            $where['endpoint'] = $endpoint;
            $where_format[] = '%s';
        }
        
        return $wpdb->delete(
            $wpdb->prefix . 'environmental_mobile_api_rate_limits',
            $where,
            $where_format
        );
    }
    
    /**
     * Get rate limit stats
     */
    public function get_rate_limit_stats($identifier = null, $hours = 24) {
        global $wpdb;
        
        $where_clause = "WHERE window_start >= DATE_SUB(NOW(), INTERVAL %d HOUR)";
        $params = array($hours);
        
        if ($identifier) {
            $where_clause .= " AND identifier = %s";
            $params[] = $identifier;
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT identifier, endpoint, SUM(requests) as total_requests, 
                    COUNT(*) as windows, MAX(requests) as peak_requests,
                    MIN(window_start) as first_request, MAX(window_start) as last_request
             FROM {$wpdb->prefix}environmental_mobile_api_rate_limits 
             $where_clause
             GROUP BY identifier, endpoint
             ORDER BY total_requests DESC",
            ...$params
        ));
    }
    
    /**
     * Clean up old rate limit data
     */
    public function cleanup_old_data() {
        global $wpdb;
        
        return $wpdb->query(
            "DELETE FROM {$wpdb->prefix}environmental_mobile_api_rate_limits 
             WHERE window_start < DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
    }
    
    /**
     * Set custom rate limit
     */
    public function set_custom_rate_limit($identifier, $endpoint, $limit, $window_hours = 1) {
        $custom_limits = get_option('environmental_mobile_api_custom_rate_limits', array());
        
        $key = $identifier . '|' . $endpoint;
        $custom_limits[$key] = array(
            'limit' => $limit,
            'window_hours' => $window_hours,
            'created_at' => current_time('mysql')
        );
        
        return update_option('environmental_mobile_api_custom_rate_limits', $custom_limits);
    }
    
    /**
     * Remove custom rate limit
     */
    public function remove_custom_rate_limit($identifier, $endpoint) {
        $custom_limits = get_option('environmental_mobile_api_custom_rate_limits', array());
        
        $key = $identifier . '|' . $endpoint;
        unset($custom_limits[$key]);
        
        return update_option('environmental_mobile_api_custom_rate_limits', $custom_limits);
    }
    
    /**
     * Check if identifier is blacklisted
     */
    public function is_blacklisted($identifier) {
        $blacklist = get_option('environmental_mobile_api_blacklist', array());
        
        return in_array($identifier, $blacklist);
    }
    
    /**
     * Add to blacklist
     */
    public function add_to_blacklist($identifier, $reason = '') {
        $blacklist = get_option('environmental_mobile_api_blacklist', array());
        $blacklist_details = get_option('environmental_mobile_api_blacklist_details', array());
        
        if (!in_array($identifier, $blacklist)) {
            $blacklist[] = $identifier;
            $blacklist_details[$identifier] = array(
                'reason' => $reason,
                'added_at' => current_time('mysql'),
                'added_by' => get_current_user_id()
            );
            
            update_option('environmental_mobile_api_blacklist', $blacklist);
            update_option('environmental_mobile_api_blacklist_details', $blacklist_details);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Remove from blacklist
     */
    public function remove_from_blacklist($identifier) {
        $blacklist = get_option('environmental_mobile_api_blacklist', array());
        $blacklist_details = get_option('environmental_mobile_api_blacklist_details', array());
        
        $key = array_search($identifier, $blacklist);
        if ($key !== false) {
            unset($blacklist[$key]);
            unset($blacklist_details[$identifier]);
            
            update_option('environmental_mobile_api_blacklist', array_values($blacklist));
            update_option('environmental_mobile_api_blacklist_details', $blacklist_details);
            
            return true;
        }
        
        return false;
    }
}
