<?php
/**
 * API Manager Class
 * 
 * Central manager for all mobile API functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Mobile_API_Manager {
    
    private $namespace = 'environmental-mobile-api/v1';
    private $auth_manager;
    private $rate_limiter;
    private $cache_manager;
    private $webhook_manager;
    private $security;
    
    public function __construct() {
        $this->auth_manager = new Environmental_Mobile_API_Auth_Manager();
        $this->rate_limiter = new Environmental_Mobile_API_Rate_Limiter();
        $this->cache_manager = new Environmental_Mobile_API_Cache_Manager();
        $this->webhook_manager = new Environmental_Mobile_API_Webhook_Manager();
        $this->security = new Environmental_Mobile_API_Security();
        
        add_action('rest_api_init', array($this, 'register_core_routes'));
        add_filter('rest_authentication_errors', array($this, 'authenticate_request'));
        add_action('rest_api_init', array($this, 'add_cors_headers'));
    }
    
    /**
     * Register core API routes
     */
    public function register_core_routes() {
        // API info endpoint
        register_rest_route($this->namespace, '/info', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_api_info'),
            'permission_callback' => '__return_true'
        ));
        
        // Health check endpoint
        register_rest_route($this->namespace, '/health', array(
            'methods' => 'GET',
            'callback' => array($this, 'health_check'),
            'permission_callback' => '__return_true'
        ));
        
        // API status endpoint
        register_rest_route($this->namespace, '/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_api_status'),
            'permission_callback' => array($this, 'check_admin_permission')
        ));
    }
    
    /**
     * Get API information
     */
    public function get_api_info($request) {
        $info = array(
            'name' => 'Environmental Platform Mobile API',
            'version' => ENVIRONMENTAL_MOBILE_API_VERSION,
            'description' => 'Comprehensive REST API for Environmental Platform mobile applications',
            'authentication' => array(
                'type' => 'JWT',
                'endpoints' => array(
                    'login' => rest_url($this->namespace . '/auth/login'),
                    'register' => rest_url($this->namespace . '/auth/register'),
                    'refresh' => rest_url($this->namespace . '/auth/refresh')
                )
            ),
            'endpoints' => array(
                'users' => rest_url($this->namespace . '/users'),
                'content' => rest_url($this->namespace . '/content'),
                'environmental' => rest_url($this->namespace . '/environmental'),
                'analytics' => rest_url($this->namespace . '/analytics'),
                'notifications' => rest_url($this->namespace . '/notifications'),
                'media' => rest_url($this->namespace . '/media')
            ),
            'rate_limits' => $this->get_rate_limit_info(),
            'documentation' => rest_url($this->namespace . '/docs')
        );
        
        return rest_ensure_response($info);
    }
    
    /**
     * Health check endpoint
     */
    public function health_check($request) {
        $health = array(
            'status' => 'ok',
            'timestamp' => current_time('c'),
            'checks' => array()
        );
        
        // Database check
        global $wpdb;
        $db_check = $wpdb->get_var("SELECT 1");
        $health['checks']['database'] = $db_check === '1' ? 'ok' : 'error';
        
        // WordPress check
        $health['checks']['wordpress'] = is_wp_error(wp_remote_get(home_url())) ? 'error' : 'ok';
        
        // Cache check
        $health['checks']['cache'] = $this->cache_manager->is_available() ? 'ok' : 'warning';
        
        // JWT secret check
        $health['checks']['jwt_secret'] = get_option('environmental_mobile_api_jwt_secret') ? 'ok' : 'error';
        
        // Overall status
        $has_error = in_array('error', $health['checks']);
        $health['status'] = $has_error ? 'error' : 'ok';
        
        $response = rest_ensure_response($health);
        
        if ($has_error) {
            $response->set_status(503);
        }
        
        return $response;
    }
    
    /**
     * Get API status (admin only)
     */
    public function get_api_status($request) {
        global $wpdb;
        
        $status = array(
            'api_version' => ENVIRONMENTAL_MOBILE_API_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'memory_usage' => array(
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'limit' => ini_get('memory_limit')
            ),
            'statistics' => array(
                'total_requests_today' => $this->get_requests_count('today'),
                'total_requests_week' => $this->get_requests_count('week'),
                'active_tokens' => $this->get_active_tokens_count(),
                'registered_devices' => $this->get_registered_devices_count()
            ),
            'rate_limits' => $this->get_current_rate_limits(),
            'webhooks' => $this->get_webhook_status(),
            'cache_status' => $this->cache_manager->get_status()
        );
        
        return rest_ensure_response($status);
    }
    
    /**
     * Authenticate API request
     */
    public function authenticate_request($result) {
        // Skip if already authenticated or error
        if (is_wp_error($result) || $result !== null) {
            return $result;
        }
        
        // Skip for non-API requests
        if (strpos($_SERVER['REQUEST_URI'], '/wp-json/environmental-mobile-api/') === false) {
            return $result;
        }
        
        // Get authorization header
        $auth_header = $this->get_authorization_header();
        
        if (!$auth_header) {
            return null; // Let individual endpoints handle auth
        }
        
        // Validate JWT token
        $token = str_replace('Bearer ', '', $auth_header);
        $user_id = $this->auth_manager->validate_jwt_token($token);
        
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        
        if ($user_id) {
            wp_set_current_user($user_id);
            return true;
        }
        
        return null;
    }
    
    /**
     * Get authorization header
     */
    private function get_authorization_header() {
        $headers = array();
        
        if (isset($_SERVER['Authorization'])) {
            $headers['Authorization'] = $_SERVER['Authorization'];
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers['Authorization'] = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (function_exists('apache_request_headers')) {
            $request_headers = apache_request_headers();
            $request_headers = array_combine(array_map('ucwords', array_keys($request_headers)), array_values($request_headers));
            
            if (isset($request_headers['Authorization'])) {
                $headers['Authorization'] = $request_headers['Authorization'];
            }
        }
        
        return isset($headers['Authorization']) ? $headers['Authorization'] : null;
    }
    
    /**
     * Add CORS headers
     */
    public function add_cors_headers() {
        remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
        add_filter('rest_pre_serve_request', array($this, 'send_cors_headers'));
    }
    
    /**
     * Send CORS headers
     */
    public function send_cors_headers($value) {
        $origin = get_http_origin();
        
        if ($origin) {
            $allowed_origins = get_option('environmental_mobile_api_cors_origins', array('*'));
            
            if (in_array('*', $allowed_origins) || in_array($origin, $allowed_origins)) {
                header('Access-Control-Allow-Origin: ' . $origin);
                header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
                header('Access-Control-Allow-Credentials: true');
                header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
            }
        }
        
        return $value;
    }
    
    /**
     * Check admin permission
     */
    public function check_admin_permission($request) {
        return current_user_can('manage_options');
    }
    
    /**
     * Get rate limit information
     */
    private function get_rate_limit_info() {
        $settings = get_option('environmental_mobile_api_settings', array());
        
        return array(
            'requests_per_hour' => isset($settings['rate_limit_requests']) ? $settings['rate_limit_requests'] : 1000,
            'window_minutes' => isset($settings['rate_limit_window']) ? $settings['rate_limit_window'] / 60 : 60
        );
    }
    
    /**
     * Get requests count
     */
    private function get_requests_count($period) {
        global $wpdb;
        
        $date_query = '';
        switch ($period) {
            case 'today':
                $date_query = "DATE(created_at) = CURDATE()";
                break;
            case 'week':
                $date_query = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            default:
                return 0;
        }
        
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}environmental_mobile_api_logs WHERE $date_query"
        );
    }
    
    /**
     * Get active tokens count
     */
    private function get_active_tokens_count() {
        global $wpdb;
        
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}environmental_mobile_api_tokens 
             WHERE expires_at > NOW() AND is_revoked = 0"
        );
    }
    
    /**
     * Get registered devices count
     */
    private function get_registered_devices_count() {
        global $wpdb;
        
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}environmental_mobile_api_devices 
             WHERE is_active = 1"
        );
    }
    
    /**
     * Get current rate limits
     */
    private function get_current_rate_limits() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT identifier, endpoint, requests, window_start 
             FROM {$wpdb->prefix}environmental_mobile_api_rate_limits 
             WHERE window_start > DATE_SUB(NOW(), INTERVAL 1 HOUR)
             ORDER BY requests DESC
             LIMIT 10"
        );
    }
    
    /**
     * Get webhook status
     */
    private function get_webhook_status() {
        global $wpdb;
        
        $active_webhooks = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}environmental_mobile_api_webhooks WHERE is_active = 1"
        );
        
        $recent_errors = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}environmental_mobile_api_webhooks 
             WHERE last_error IS NOT NULL AND updated_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
        );
        
        return array(
            'active_webhooks' => (int) $active_webhooks,
            'recent_errors' => (int) $recent_errors
        );
    }
    
    /**
     * Log API request
     */
    public function log_request($endpoint, $method, $user_id = null, $response_code = 200, $response_time = 0, $request_data = null) {
        $settings = get_option('environmental_mobile_api_settings', array());
        
        if (!isset($settings['enable_logging']) || !$settings['enable_logging']) {
            return;
        }
        
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'environmental_mobile_api_logs',
            array(
                'user_id' => $user_id,
                'endpoint' => $endpoint,
                'method' => $method,
                'ip_address' => $this->get_client_ip(),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null,
                'request_data' => is_array($request_data) ? json_encode($request_data) : $request_data,
                'response_code' => $response_code,
                'response_time' => $response_time,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%f', '%s')
        );
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
}
