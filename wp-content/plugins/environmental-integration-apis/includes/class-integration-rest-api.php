<?php
/**
 * Integration REST API
 *
 * @package Environmental_Integration_APIs
 * @subpackage Integration_REST_API
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Environmental Integration REST API class
 *
 * Provides RESTful endpoints for mobile app and external integrations
 */
class Environmental_Integration_REST_API {

    /**
     * API namespace
     */
    const NAMESPACE_V1 = 'environmental-integration/v1';

    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
        add_filter('rest_pre_dispatch', array($this, 'rate_limit_check'), 10, 3);
        add_filter('rest_request_before_callbacks', array($this, 'authenticate_request'), 10, 3);
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Google Maps endpoints
        register_rest_route(self::NAMESPACE_V1, '/maps/geocode', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'geocode_address'),
            'permission_callback' => array($this, 'check_api_permissions'),
            'args' => array(
                'address' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => __('Address to geocode', 'environmental-integration-apis'),
                ),
            ),
        ));

        register_rest_route(self::NAMESPACE_V1, '/maps/reverse-geocode', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'reverse_geocode'),
            'permission_callback' => array($this, 'check_api_permissions'),
            'args' => array(
                'lat' => array(
                    'required' => true,
                    'type' => 'number',
                    'description' => __('Latitude', 'environmental-integration-apis'),
                ),
                'lng' => array(
                    'required' => true,
                    'type' => 'number',
                    'description' => __('Longitude', 'environmental-integration-apis'),
                ),
            ),
        ));

        register_rest_route(self::NAMESPACE_V1, '/maps/nearby-places', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_nearby_places'),
            'permission_callback' => array($this, 'check_api_permissions'),
            'args' => array(
                'lat' => array(
                    'required' => true,
                    'type' => 'number',
                ),
                'lng' => array(
                    'required' => true,
                    'type' => 'number',
                ),
                'radius' => array(
                    'default' => 5000,
                    'type' => 'integer',
                ),
                'type' => array(
                    'default' => 'establishment',
                    'type' => 'string',
                ),
            ),
        ));

        // Weather endpoints
        register_rest_route(self::NAMESPACE_V1, '/weather/current', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_current_weather'),
            'permission_callback' => array($this, 'check_api_permissions'),
            'args' => array(
                'lat' => array(
                    'required' => true,
                    'type' => 'number',
                ),
                'lng' => array(
                    'required' => true,
                    'type' => 'number',
                ),
                'units' => array(
                    'default' => 'metric',
                    'type' => 'string',
                    'enum' => array('metric', 'imperial', 'kelvin'),
                ),
            ),
        ));

        register_rest_route(self::NAMESPACE_V1, '/weather/forecast', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_weather_forecast'),
            'permission_callback' => array($this, 'check_api_permissions'),
            'args' => array(
                'lat' => array(
                    'required' => true,
                    'type' => 'number',
                ),
                'lng' => array(
                    'required' => true,
                    'type' => 'number',
                ),
                'units' => array(
                    'default' => 'metric',
                    'type' => 'string',
                    'enum' => array('metric', 'imperial', 'kelvin'),
                ),
                'days' => array(
                    'default' => 5,
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 7,
                ),
            ),
        ));

        register_rest_route(self::NAMESPACE_V1, '/weather/alerts', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_weather_alerts'),
            'permission_callback' => array($this, 'check_api_permissions'),
            'args' => array(
                'lat' => array(
                    'required' => true,
                    'type' => 'number',
                ),
                'lng' => array(
                    'required' => true,
                    'type' => 'number',
                ),
            ),
        ));

        // Air Quality endpoints
        register_rest_route(self::NAMESPACE_V1, '/air-quality/current', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_current_air_quality'),
            'permission_callback' => array($this, 'check_api_permissions'),
            'args' => array(
                'lat' => array(
                    'required' => true,
                    'type' => 'number',
                ),
                'lng' => array(
                    'required' => true,
                    'type' => 'number',
                ),
            ),
        ));

        register_rest_route(self::NAMESPACE_V1, '/air-quality/forecast', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_air_quality_forecast'),
            'permission_callback' => array($this, 'check_api_permissions'),
            'args' => array(
                'lat' => array(
                    'required' => true,
                    'type' => 'number',
                ),
                'lng' => array(
                    'required' => true,
                    'type' => 'number',
                ),
                'hours' => array(
                    'default' => 24,
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 72,
                ),
            ),
        ));

        // Social Media endpoints
        register_rest_route(self::NAMESPACE_V1, '/social/share', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'share_to_social'),
            'permission_callback' => array($this, 'check_write_permissions'),
            'args' => array(
                'platforms' => array(
                    'required' => true,
                    'type' => 'array',
                    'items' => array(
                        'type' => 'string',
                        'enum' => array('facebook', 'twitter', 'instagram'),
                    ),
                ),
                'content' => array(
                    'required' => true,
                    'type' => 'string',
                ),
                'image_url' => array(
                    'type' => 'string',
                    'format' => 'uri',
                ),
                'schedule_time' => array(
                    'type' => 'string',
                    'format' => 'date-time',
                ),
            ),
        ));

        register_rest_route(self::NAMESPACE_V1, '/social/feed', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_social_feed'),
            'permission_callback' => array($this, 'check_api_permissions'),
            'args' => array(
                'platform' => array(
                    'required' => true,
                    'type' => 'string',
                    'enum' => array('facebook', 'twitter', 'instagram'),
                ),
                'limit' => array(
                    'default' => 10,
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 50,
                ),
            ),
        ));

        // Webhook endpoints
        register_rest_route(self::NAMESPACE_V1, '/webhooks', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_webhooks'),
                'permission_callback' => array($this, 'check_manage_permissions'),
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_webhook'),
                'permission_callback' => array($this, 'check_manage_permissions'),
                'args' => array(
                    'name' => array(
                        'required' => true,
                        'type' => 'string',
                    ),
                    'url' => array(
                        'required' => true,
                        'type' => 'string',
                        'format' => 'uri',
                    ),
                    'events' => array(
                        'required' => true,
                        'type' => 'array',
                    ),
                    'secret' => array(
                        'type' => 'string',
                    ),
                ),
            ),
        ));

        register_rest_route(self::NAMESPACE_V1, '/webhooks/(?P<id>\d+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_webhook'),
                'permission_callback' => array($this, 'check_manage_permissions'),
            ),
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_webhook'),
                'permission_callback' => array($this, 'check_manage_permissions'),
            ),
            array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_webhook'),
                'permission_callback' => array($this, 'check_manage_permissions'),
            ),
        ));

        register_rest_route(self::NAMESPACE_V1, '/webhooks/(?P<id>\d+)/test', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'test_webhook'),
            'permission_callback' => array($this, 'check_manage_permissions'),
        ));

        // Incoming webhook endpoint
        register_rest_route(self::NAMESPACE_V1, '/webhook/(?P<webhook_id>[a-zA-Z0-9-_]+)', array(
            'methods' => array(WP_REST_Server::CREATABLE, 'GET', 'PUT', 'PATCH'),
            'callback' => array($this, 'handle_incoming_webhook'),
            'permission_callback' => '__return_true', // Public endpoint with signature verification
        ));

        // API Status and Monitoring
        register_rest_route(self::NAMESPACE_V1, '/status', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_api_status'),
            'permission_callback' => array($this, 'check_api_permissions'),
        ));

        register_rest_route(self::NAMESPACE_V1, '/stats', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_api_stats'),
            'permission_callback' => array($this, 'check_manage_permissions'),
            'args' => array(
                'period' => array(
                    'default' => '24h',
                    'type' => 'string',
                    'enum' => array('1h', '24h', '7d', '30d'),
                ),
            ),
        ));

        // Cache management
        register_rest_route(self::NAMESPACE_V1, '/cache', array(
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => array($this, 'clear_cache'),
            'permission_callback' => array($this, 'check_manage_permissions'),
            'args' => array(
                'service' => array(
                    'type' => 'string',
                    'enum' => array('all', 'google_maps', 'weather', 'air_quality', 'social_media'),
                    'default' => 'all',
                ),
            ),
        ));
    }

    /**
     * Check API permissions
     */
    public function check_api_permissions($request) {
        // Allow authenticated users or valid API key
        if (is_user_logged_in()) {
            return true;
        }

        $api_key = $request->get_header('X-API-Key');
        if (empty($api_key)) {
            $api_key = $request->get_param('api_key');
        }

        return $this->validate_api_key($api_key);
    }

    /**
     * Check write permissions
     */
    public function check_write_permissions($request) {
        return current_user_can('edit_posts') || $this->validate_api_key($request->get_header('X-API-Key'));
    }

    /**
     * Check management permissions
     */
    public function check_manage_permissions($request) {
        return current_user_can('manage_options');
    }

    /**
     * Validate API key
     */
    private function validate_api_key($api_key) {
        if (empty($api_key)) {
            return false;
        }

        // Get valid API keys from options
        $valid_keys = get_option('eia_api_keys', array());
        return in_array($api_key, $valid_keys);
    }

    /**
     * Rate limit check
     */
    public function rate_limit_check($result, $server, $request) {
        $route = $request->get_route();
        
        // Only apply rate limiting to our API endpoints
        if (strpos($route, '/' . self::NAMESPACE_V1) !== 0) {
            return $result;
        }

        if (!get_option('eia_rate_limiting', 1)) {
            return $result;
        }

        $api_monitor = Environmental_Integration_APIs::get_instance()->api_monitor;
        if ($api_monitor && !$api_monitor->check_rate_limit('rest_api', $this->get_client_ip())) {
            return new WP_Error(
                'rate_limit_exceeded',
                __('Rate limit exceeded. Please try again later.', 'environmental-integration-apis'),
                array('status' => 429)
            );
        }

        return $result;
    }

    /**
     * Authenticate request and log API usage
     */
    public function authenticate_request($response, $handler, $request) {
        $route = $request->get_route();
        
        // Only log our API endpoints
        if (strpos($route, '/' . self::NAMESPACE_V1) !== 0) {
            return $response;
        }

        // Log API request
        $this->log_api_request($request);

        return $response;
    }

    /**
     * Geocode address endpoint
     */
    public function geocode_address($request) {
        $address = $request->get_param('address');
        
        $google_maps = Environmental_Integration_APIs::get_instance()->google_maps;
        if (!$google_maps) {
            return new WP_Error('service_unavailable', __('Google Maps service not available', 'environmental-integration-apis'), array('status' => 503));
        }

        $result = $google_maps->geocode($address);
        
        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response($result);
    }

    /**
     * Reverse geocode endpoint
     */
    public function reverse_geocode($request) {
        $lat = $request->get_param('lat');
        $lng = $request->get_param('lng');
        
        $google_maps = Environmental_Integration_APIs::get_instance()->google_maps;
        if (!$google_maps) {
            return new WP_Error('service_unavailable', __('Google Maps service not available', 'environmental-integration-apis'), array('status' => 503));
        }

        $result = $google_maps->reverse_geocode($lat, $lng);
        
        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response($result);
    }

    /**
     * Get nearby places endpoint
     */
    public function get_nearby_places($request) {
        $lat = $request->get_param('lat');
        $lng = $request->get_param('lng');
        $radius = $request->get_param('radius');
        $type = $request->get_param('type');
        
        $google_maps = Environmental_Integration_APIs::get_instance()->google_maps;
        if (!$google_maps) {
            return new WP_Error('service_unavailable', __('Google Maps service not available', 'environmental-integration-apis'), array('status' => 503));
        }

        $result = $google_maps->get_nearby_places($lat, $lng, $radius, $type);
        
        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response($result);
    }

    /**
     * Get current weather endpoint
     */
    public function get_current_weather($request) {
        $lat = $request->get_param('lat');
        $lng = $request->get_param('lng');
        $units = $request->get_param('units');
        
        $weather = Environmental_Integration_APIs::get_instance()->weather;
        if (!$weather) {
            return new WP_Error('service_unavailable', __('Weather service not available', 'environmental-integration-apis'), array('status' => 503));
        }

        $result = $weather->get_current_weather($lat, $lng, $units);
        
        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response($result);
    }

    /**
     * Get weather forecast endpoint
     */
    public function get_weather_forecast($request) {
        $lat = $request->get_param('lat');
        $lng = $request->get_param('lng');
        $units = $request->get_param('units');
        $days = $request->get_param('days');
        
        $weather = Environmental_Integration_APIs::get_instance()->weather;
        if (!$weather) {
            return new WP_Error('service_unavailable', __('Weather service not available', 'environmental-integration-apis'), array('status' => 503));
        }

        $result = $weather->get_forecast($lat, $lng, $days, $units);
        
        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response($result);
    }

    /**
     * Get weather alerts endpoint
     */
    public function get_weather_alerts($request) {
        $lat = $request->get_param('lat');
        $lng = $request->get_param('lng');
        
        $weather = Environmental_Integration_APIs::get_instance()->weather;
        if (!$weather) {
            return new WP_Error('service_unavailable', __('Weather service not available', 'environmental-integration-apis'), array('status' => 503));
        }

        $result = $weather->get_weather_alerts($lat, $lng);
        
        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response($result);
    }

    /**
     * Get current air quality endpoint
     */
    public function get_current_air_quality($request) {
        $lat = $request->get_param('lat');
        $lng = $request->get_param('lng');
        
        $air_quality = Environmental_Integration_APIs::get_instance()->air_quality;
        if (!$air_quality) {
            return new WP_Error('service_unavailable', __('Air quality service not available', 'environmental-integration-apis'), array('status' => 503));
        }

        $result = $air_quality->get_current_air_quality($lat, $lng);
        
        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response($result);
    }

    /**
     * Get air quality forecast endpoint
     */
    public function get_air_quality_forecast($request) {
        $lat = $request->get_param('lat');
        $lng = $request->get_param('lng');
        $hours = $request->get_param('hours');
        
        $air_quality = Environmental_Integration_APIs::get_instance()->air_quality;
        if (!$air_quality) {
            return new WP_Error('service_unavailable', __('Air quality service not available', 'environmental-integration-apis'), array('status' => 503));
        }

        $result = $air_quality->get_forecast($lat, $lng, $hours);
        
        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response($result);
    }

    /**
     * Share to social media endpoint
     */
    public function share_to_social($request) {
        $platforms = $request->get_param('platforms');
        $content = $request->get_param('content');
        $image_url = $request->get_param('image_url');
        $schedule_time = $request->get_param('schedule_time');
        
        $social_media = Environmental_Integration_APIs::get_instance()->social_media;
        if (!$social_media) {
            return new WP_Error('service_unavailable', __('Social media service not available', 'environmental-integration-apis'), array('status' => 503));
        }

        $results = array();
        foreach ($platforms as $platform) {
            $result = $social_media->share_content($platform, $content, $image_url, $schedule_time);
            $results[$platform] = $result;
        }

        return rest_ensure_response(array(
            'success' => true,
            'results' => $results
        ));
    }

    /**
     * Get social media feed endpoint
     */
    public function get_social_feed($request) {
        $platform = $request->get_param('platform');
        $limit = $request->get_param('limit');
        
        $social_media = Environmental_Integration_APIs::get_instance()->social_media;
        if (!$social_media) {
            return new WP_Error('service_unavailable', __('Social media service not available', 'environmental-integration-apis'), array('status' => 503));
        }

        $result = $social_media->get_feed($platform, $limit);
        
        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response($result);
    }

    /**
     * Get webhooks endpoint
     */
    public function get_webhooks($request) {
        $webhooks = Environmental_Integration_APIs::get_instance()->webhooks;
        if (!$webhooks) {
            return new WP_Error('service_unavailable', __('Webhook service not available', 'environmental-integration-apis'), array('status' => 503));
        }

        $result = $webhooks->get_all_webhooks();
        return rest_ensure_response($result);
    }

    /**
     * Create webhook endpoint
     */
    public function create_webhook($request) {
        $name = $request->get_param('name');
        $url = $request->get_param('url');
        $events = $request->get_param('events');
        $secret = $request->get_param('secret');
        
        $webhooks = Environmental_Integration_APIs::get_instance()->webhooks;
        if (!$webhooks) {
            return new WP_Error('service_unavailable', __('Webhook service not available', 'environmental-integration-apis'), array('status' => 503));
        }

        $result = $webhooks->create_webhook($name, $url, $events, $secret);
        
        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response($result);
    }

    /**
     * Get webhook endpoint
     */
    public function get_webhook($request) {
        $webhook_id = $request->get_param('id');
        
        $webhooks = Environmental_Integration_APIs::get_instance()->webhooks;
        if (!$webhooks) {
            return new WP_Error('service_unavailable', __('Webhook service not available', 'environmental-integration-apis'), array('status' => 503));
        }

        $result = $webhooks->get_webhook($webhook_id);
        
        if (!$result) {
            return new WP_Error('webhook_not_found', __('Webhook not found', 'environmental-integration-apis'), array('status' => 404));
        }

        return rest_ensure_response($result);
    }

    /**
     * Update webhook endpoint
     */
    public function update_webhook($request) {
        $webhook_id = $request->get_param('id');
        $data = $request->get_json_params();
        
        $webhooks = Environmental_Integration_APIs::get_instance()->webhooks;
        if (!$webhooks) {
            return new WP_Error('service_unavailable', __('Webhook service not available', 'environmental-integration-apis'), array('status' => 503));
        }

        $result = $webhooks->update_webhook($webhook_id, $data);
        
        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response($result);
    }

    /**
     * Delete webhook endpoint
     */
    public function delete_webhook($request) {
        $webhook_id = $request->get_param('id');
        
        $webhooks = Environmental_Integration_APIs::get_instance()->webhooks;
        if (!$webhooks) {
            return new WP_Error('service_unavailable', __('Webhook service not available', 'environmental-integration-apis'), array('status' => 503));
        }

        $result = $webhooks->delete_webhook($webhook_id);
        
        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response(array('success' => true));
    }

    /**
     * Test webhook endpoint
     */
    public function test_webhook($request) {
        $webhook_id = $request->get_param('id');
        
        $webhooks = Environmental_Integration_APIs::get_instance()->webhooks;
        if (!$webhooks) {
            return new WP_Error('service_unavailable', __('Webhook service not available', 'environmental-integration-apis'), array('status' => 503));
        }

        $result = $webhooks->test_webhook($webhook_id);
        
        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response($result);
    }

    /**
     * Handle incoming webhook
     */
    public function handle_incoming_webhook($request) {
        $webhook_id = $request->get_param('webhook_id');
        
        $webhooks = Environmental_Integration_APIs::get_instance()->webhooks;
        if (!$webhooks) {
            return new WP_Error('service_unavailable', __('Webhook service not available', 'environmental-integration-apis'), array('status' => 503));
        }

        $result = $webhooks->handle_incoming_webhook($webhook_id, $request);
        
        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response(array('success' => true));
    }

    /**
     * Get API status endpoint
     */
    public function get_api_status($request) {
        $api_monitor = Environmental_Integration_APIs::get_instance()->api_monitor;
        if (!$api_monitor) {
            return new WP_Error('service_unavailable', __('API monitoring service not available', 'environmental-integration-apis'), array('status' => 503));
        }

        $status = array(
            'google_maps' => $api_monitor->get_service_status('google_maps'),
            'weather' => $api_monitor->get_service_status('weather'),
            'air_quality' => $api_monitor->get_service_status('air_quality'),
            'social_media' => $api_monitor->get_service_status('social_media'),
            'webhooks' => $api_monitor->get_service_status('webhooks'),
        );

        return rest_ensure_response($status);
    }

    /**
     * Get API statistics endpoint
     */
    public function get_api_stats($request) {
        $period = $request->get_param('period');
        
        $api_monitor = Environmental_Integration_APIs::get_instance()->api_monitor;
        if (!$api_monitor) {
            return new WP_Error('service_unavailable', __('API monitoring service not available', 'environmental-integration-apis'), array('status' => 503));
        }

        $stats = $api_monitor->get_statistics($period);
        return rest_ensure_response($stats);
    }

    /**
     * Clear cache endpoint
     */
    public function clear_cache($request) {
        $service = $request->get_param('service');
        
        global $wpdb;
        $table_cache = $wpdb->prefix . EIA_TABLE_PREFIX . 'api_cache';
        
        if ($service === 'all') {
            $wpdb->query("DELETE FROM {$table_cache}");
        } else {
            $wpdb->delete($table_cache, array('service' => $service));
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => sprintf(__('Cache cleared for %s', 'environmental-integration-apis'), $service)
        ));
    }

    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );

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

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Log API request
     */
    private function log_api_request($request) {
        global $wpdb;

        $route = $request->get_route();
        $method = $request->get_method();
        $client_ip = $this->get_client_ip();

        // Extract service from route
        $service = 'rest_api';
        if (strpos($route, '/maps/') !== false) {
            $service = 'google_maps';
        } elseif (strpos($route, '/weather/') !== false) {
            $service = 'weather';
        } elseif (strpos($route, '/air-quality/') !== false) {
            $service = 'air_quality';
        } elseif (strpos($route, '/social/') !== false) {
            $service = 'social_media';
        } elseif (strpos($route, '/webhook') !== false) {
            $service = 'webhooks';
        }

        $table_logs = $wpdb->prefix . EIA_TABLE_PREFIX . 'api_logs';
        
        $wpdb->insert(
            $table_logs,
            array(
                'api_service' => $service,
                'endpoint' => $route,
                'method' => $method,
                'client_ip' => $client_ip,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'request_data' => wp_json_encode($request->get_params()),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Get API documentation
     */
    public function get_api_documentation() {
        return array(
            'version' => '1.0.0',
            'base_url' => rest_url(self::NAMESPACE_V1),
            'authentication' => array(
                'methods' => array('WordPress Auth', 'API Key'),
                'api_key_header' => 'X-API-Key',
                'api_key_param' => 'api_key'
            ),
            'rate_limiting' => array(
                'enabled' => get_option('eia_rate_limiting', 1),
                'limits' => array(
                    'per_minute' => 60,
                    'per_hour' => 1000,
                    'per_day' => 10000
                )
            ),
            'endpoints' => array(
                'maps' => array(
                    'geocode' => array(
                        'path' => '/maps/geocode',
                        'method' => 'GET',
                        'parameters' => array('address')
                    ),
                    'reverse_geocode' => array(
                        'path' => '/maps/reverse-geocode',
                        'method' => 'GET',
                        'parameters' => array('lat', 'lng')
                    ),
                    'nearby_places' => array(
                        'path' => '/maps/nearby-places',
                        'method' => 'GET',
                        'parameters' => array('lat', 'lng', 'radius', 'type')
                    )
                ),
                'weather' => array(
                    'current' => array(
                        'path' => '/weather/current',
                        'method' => 'GET',
                        'parameters' => array('lat', 'lng', 'units')
                    ),
                    'forecast' => array(
                        'path' => '/weather/forecast',
                        'method' => 'GET',
                        'parameters' => array('lat', 'lng', 'units', 'days')
                    ),
                    'alerts' => array(
                        'path' => '/weather/alerts',
                        'method' => 'GET',
                        'parameters' => array('lat', 'lng')
                    )
                ),
                'air_quality' => array(
                    'current' => array(
                        'path' => '/air-quality/current',
                        'method' => 'GET',
                        'parameters' => array('lat', 'lng')
                    ),
                    'forecast' => array(
                        'path' => '/air-quality/forecast',
                        'method' => 'GET',
                        'parameters' => array('lat', 'lng', 'hours')
                    )
                ),
                'social' => array(
                    'share' => array(
                        'path' => '/social/share',
                        'method' => 'POST',
                        'parameters' => array('platforms', 'content', 'image_url', 'schedule_time')
                    ),
                    'feed' => array(
                        'path' => '/social/feed',
                        'method' => 'GET',
                        'parameters' => array('platform', 'limit')
                    )
                ),
                'webhooks' => array(
                    'list' => array(
                        'path' => '/webhooks',
                        'method' => 'GET'
                    ),
                    'create' => array(
                        'path' => '/webhooks',
                        'method' => 'POST',
                        'parameters' => array('name', 'url', 'events', 'secret')
                    ),
                    'get' => array(
                        'path' => '/webhooks/{id}',
                        'method' => 'GET'
                    ),
                    'update' => array(
                        'path' => '/webhooks/{id}',
                        'method' => 'PUT'
                    ),
                    'delete' => array(
                        'path' => '/webhooks/{id}',
                        'method' => 'DELETE'
                    ),
                    'test' => array(
                        'path' => '/webhooks/{id}/test',
                        'method' => 'POST'
                    )
                ),
                'monitoring' => array(
                    'status' => array(
                        'path' => '/status',
                        'method' => 'GET'
                    ),
                    'stats' => array(
                        'path' => '/stats',
                        'method' => 'GET',
                        'parameters' => array('period')
                    )
                ),
                'cache' => array(
                    'clear' => array(
                        'path' => '/cache',
                        'method' => 'DELETE',
                        'parameters' => array('service')
                    )
                )
            )
        );
    }
}
