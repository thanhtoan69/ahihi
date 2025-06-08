<?php
/**
 * Webhook System Class
 *
 * Handles webhook management for third-party service integrations,
 * including webhook registration, processing, and delivery management.
 */

if (!defined('ABSPATH')) {
    exit;
}

class EIA_Webhook_System {
    
    private static $instance = null;
    private $webhook_endpoints = array();
    private $max_retry_attempts = 3;
    private $retry_delays = array(60, 300, 900); // 1min, 5min, 15min
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init();
    }
    
    private function init() {
        // Register webhook endpoints
        add_action('rest_api_init', array($this, 'register_webhook_endpoints'));
        
        // Register admin hooks
        add_action('wp_ajax_eia_test_webhook', array($this, 'ajax_test_webhook'));
        add_action('wp_ajax_eia_create_webhook', array($this, 'ajax_create_webhook'));
        add_action('wp_ajax_eia_delete_webhook', array($this, 'ajax_delete_webhook'));
        add_action('wp_ajax_eia_webhook_logs', array($this, 'ajax_get_webhook_logs'));
        
        // Process webhook queue
        add_action('eia_process_webhook_queue', array($this, 'process_webhook_queue'));
        add_action('eia_retry_failed_webhooks', array($this, 'retry_failed_webhooks'));
        
        // Schedule webhook processing
        if (!wp_next_scheduled('eia_process_webhook_queue')) {
            wp_schedule_event(time(), 'every_minute', 'eia_process_webhook_queue');
        }
        
        if (!wp_next_scheduled('eia_retry_failed_webhooks')) {
            wp_schedule_event(time(), 'every_five_minutes', 'eia_retry_failed_webhooks');
        }
        
        // Environmental event hooks
        add_action('eia_weather_alert', array($this, 'trigger_weather_webhook'));
        add_action('eia_air_quality_alert', array($this, 'trigger_air_quality_webhook'));
        add_action('eia_environmental_data_update', array($this, 'trigger_data_webhook'));
    }
    
    /**
     * Register REST API endpoints for webhooks
     */
    public function register_webhook_endpoints() {
        // Incoming webhook receiver
        register_rest_route('eia/v1', '/webhook/(?P<id>[a-zA-Z0-9-]+)', array(
            'methods' => array('POST', 'PUT', 'PATCH'),
            'callback' => array($this, 'handle_incoming_webhook'),
            'permission_callback' => array($this, 'verify_webhook_signature'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return !empty($param);
                    }
                )
            )
        ));
        
        // Webhook status endpoint
        register_rest_route('eia/v1', '/webhook/(?P<id>[a-zA-Z0-9-]+)/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_webhook_status'),
            'permission_callback' => array($this, 'check_webhook_permissions')
        ));
        
        // Webhook management endpoints
        register_rest_route('eia/v1', '/webhooks', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'list_webhooks'),
                'permission_callback' => array($this, 'check_admin_permissions')
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'create_webhook'),
                'permission_callback' => array($this, 'check_admin_permissions')
            )
        ));
        
        register_rest_route('eia/v1', '/webhooks/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_webhook'),
                'permission_callback' => array($this, 'check_admin_permissions')
            ),
            array(
                'methods' => 'PUT',
                'callback' => array($this, 'update_webhook'),
                'permission_callback' => array($this, 'check_admin_permissions')
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array($this, 'delete_webhook'),
                'permission_callback' => array($this, 'check_admin_permissions')
            )
        ));
    }
    
    /**
     * Create a new webhook
     */
    public function create_webhook($name, $url, $events, $options = array()) {
        global $wpdb;
        
        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return new WP_Error('invalid_url', 'Invalid webhook URL provided');
        }
        
        // Generate webhook ID and secret
        $webhook_id = wp_generate_uuid4();
        $secret = wp_generate_password(32, false);
        
        // Prepare webhook data
        $webhook_data = array(
            'webhook_id' => $webhook_id,
            'name' => sanitize_text_field($name),
            'url' => esc_url_raw($url),
            'events' => json_encode($events),
            'secret' => $secret,
            'status' => 'active',
            'retry_count' => 0,
            'last_triggered' => null,
            'last_success' => null,
            'last_error' => null,
            'options' => json_encode($options),
            'created_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'eia_webhooks',
            $webhook_data,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('database_error', 'Failed to create webhook');
        }
        
        $webhook_data['id'] = $wpdb->insert_id;
        
        return $webhook_data;
    }
    
    /**
     * Update webhook
     */
    public function update_webhook($webhook_id, $data) {
        global $wpdb;
        
        $allowed_fields = array('name', 'url', 'events', 'status', 'options');
        $update_data = array();
        $update_format = array();
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                switch ($field) {
                    case 'url':
                        if (!filter_var($data[$field], FILTER_VALIDATE_URL)) {
                            return new WP_Error('invalid_url', 'Invalid webhook URL provided');
                        }
                        $update_data[$field] = esc_url_raw($data[$field]);
                        $update_format[] = '%s';
                        break;
                    case 'events':
                    case 'options':
                        $update_data[$field] = json_encode($data[$field]);
                        $update_format[] = '%s';
                        break;
                    default:
                        $update_data[$field] = sanitize_text_field($data[$field]);
                        $update_format[] = '%s';
                        break;
                }
            }
        }
        
        if (empty($update_data)) {
            return new WP_Error('no_data', 'No valid data to update');
        }
        
        $update_data['updated_at'] = current_time('mysql');
        $update_format[] = '%s';
        
        $result = $wpdb->update(
            $wpdb->prefix . 'eia_webhooks',
            $update_data,
            array('id' => $webhook_id),
            $update_format,
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Delete webhook
     */
    public function delete_webhook($webhook_id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $wpdb->prefix . 'eia_webhooks',
            array('id' => $webhook_id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get webhook by ID
     */
    public function get_webhook($webhook_id) {
        global $wpdb;
        
        $webhook = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}eia_webhooks WHERE id = %d",
            $webhook_id
        ), ARRAY_A);
        
        if ($webhook) {
            $webhook['events'] = json_decode($webhook['events'], true);
            $webhook['options'] = json_decode($webhook['options'], true);
        }
        
        return $webhook;
    }
    
    /**
     * List all webhooks
     */
    public function list_webhooks($filters = array()) {
        global $wpdb;
        
        $where_clauses = array();
        $where_values = array();
        
        if (!empty($filters['status'])) {
            $where_clauses[] = 'status = %s';
            $where_values[] = $filters['status'];
        }
        
        if (!empty($filters['event'])) {
            $where_clauses[] = 'events LIKE %s';
            $where_values[] = '%' . $wpdb->esc_like($filters['event']) . '%';
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $query = "SELECT * FROM {$wpdb->prefix}eia_webhooks {$where_sql} ORDER BY created_at DESC";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        $webhooks = $wpdb->get_results($query, ARRAY_A);
        
        foreach ($webhooks as &$webhook) {
            $webhook['events'] = json_decode($webhook['events'], true);
            $webhook['options'] = json_decode($webhook['options'], true);
        }
        
        return $webhooks;
    }
    
    /**
     * Trigger webhook for specific event
     */
    public function trigger_webhook($event, $data, $webhook_id = null) {
        global $wpdb;
        
        // Get webhooks that listen to this event
        $where_clause = "status = 'active' AND events LIKE %s";
        $where_values = array('%' . $wpdb->esc_like($event) . '%');
        
        if ($webhook_id) {
            $where_clause .= " AND id = %d";
            $where_values[] = $webhook_id;
        }
        
        $webhooks = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}eia_webhooks WHERE {$where_clause}",
            $where_values
        ), ARRAY_A);
        
        foreach ($webhooks as $webhook) {
            $webhook_events = json_decode($webhook['events'], true);
            
            // Check if webhook listens to this specific event
            if (in_array($event, $webhook_events) || in_array('*', $webhook_events)) {
                $this->queue_webhook_delivery($webhook['id'], $event, $data);
            }
        }
    }
    
    /**
     * Queue webhook for delivery
     */
    private function queue_webhook_delivery($webhook_id, $event, $data) {
        global $wpdb;
        
        $delivery_data = array(
            'webhook_id' => $webhook_id,
            'event' => $event,
            'payload' => json_encode($data),
            'status' => 'pending',
            'attempts' => 0,
            'scheduled_at' => current_time('mysql'),
            'created_at' => current_time('mysql')
        );
        
        $wpdb->insert(
            $wpdb->prefix . 'eia_webhook_logs',
            $delivery_data,
            array('%d', '%s', '%s', '%s', '%d', '%s', '%s')
        );
    }
    
    /**
     * Process webhook delivery queue
     */
    public function process_webhook_queue() {
        global $wpdb;
        
        // Get pending webhook deliveries
        $deliveries = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}eia_webhook_logs 
             WHERE status = 'pending' AND scheduled_at <= NOW() 
             ORDER BY created_at ASC LIMIT 10",
            ARRAY_A
        );
        
        foreach ($deliveries as $delivery) {
            $this->deliver_webhook($delivery);
        }
    }
    
    /**
     * Deliver individual webhook
     */
    private function deliver_webhook($delivery) {
        global $wpdb;
        
        $webhook = $this->get_webhook($delivery['webhook_id']);
        if (!$webhook || $webhook['status'] !== 'active') {
            // Mark as failed - webhook no longer active
            $wpdb->update(
                $wpdb->prefix . 'eia_webhook_logs',
                array(
                    'status' => 'failed',
                    'error_message' => 'Webhook no longer active',
                    'delivered_at' => current_time('mysql')
                ),
                array('id' => $delivery['id']),
                array('%s', '%s', '%s'),
                array('%d')
            );
            return;
        }
        
        // Prepare payload
        $payload = array(
            'event' => $delivery['event'],
            'data' => json_decode($delivery['payload'], true),
            'webhook_id' => $webhook['webhook_id'],
            'timestamp' => current_time('timestamp'),
            'delivery_id' => $delivery['id']
        );
        
        // Generate signature
        $signature = $this->generate_webhook_signature($payload, $webhook['secret']);
        
        // Prepare headers
        $headers = array(
            'Content-Type' => 'application/json',
            'User-Agent' => 'EIA-Webhook/1.0',
            'X-EIA-Event' => $delivery['event'],
            'X-EIA-Signature' => $signature,
            'X-EIA-Delivery' => $delivery['id']
        );
        
        // Add custom headers from webhook options
        if (!empty($webhook['options']['headers'])) {
            $headers = array_merge($headers, $webhook['options']['headers']);
        }
        
        $start_time = microtime(true);
        
        // Send webhook
        $response = wp_remote_post($webhook['url'], array(
            'headers' => $headers,
            'body' => json_encode($payload),
            'timeout' => 30,
            'blocking' => true
        ));
        
        $response_time = microtime(true) - $start_time;
        $attempts = intval($delivery['attempts']) + 1;
        
        // Update delivery log
        if (is_wp_error($response)) {
            // Request failed
            $this->handle_webhook_failure($delivery, $webhook, $response->get_error_message(), $attempts, $response_time);
        } else {
            $status_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            
            if ($status_code >= 200 && $status_code < 300) {
                // Success
                $this->handle_webhook_success($delivery, $webhook, $status_code, $response_body, $attempts, $response_time);
            } else {
                // HTTP error
                $this->handle_webhook_failure($delivery, $webhook, "HTTP {$status_code}: {$response_body}", $attempts, $response_time);
            }
        }
    }
    
    /**
     * Handle successful webhook delivery
     */
    private function handle_webhook_success($delivery, $webhook, $status_code, $response_body, $attempts, $response_time) {
        global $wpdb;
        
        // Update delivery log
        $wpdb->update(
            $wpdb->prefix . 'eia_webhook_logs',
            array(
                'status' => 'success',
                'attempts' => $attempts,
                'status_code' => $status_code,
                'response_body' => $response_body,
                'response_time' => $response_time,
                'delivered_at' => current_time('mysql')
            ),
            array('id' => $delivery['id']),
            array('%s', '%d', '%d', '%s', '%f', '%s'),
            array('%d')
        );
        
        // Update webhook success timestamp
        $wpdb->update(
            $wpdb->prefix . 'eia_webhooks',
            array(
                'last_triggered' => current_time('mysql'),
                'last_success' => current_time('mysql'),
                'last_error' => null
            ),
            array('id' => $webhook['id']),
            array('%s', '%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * Handle failed webhook delivery
     */
    private function handle_webhook_failure($delivery, $webhook, $error_message, $attempts, $response_time) {
        global $wpdb;
        
        $status = 'failed';
        $next_attempt = null;
        
        // Check if we should retry
        if ($attempts < $this->max_retry_attempts) {
            $status = 'pending';
            $delay = $this->retry_delays[$attempts - 1] ?? 900; // Default 15min
            $next_attempt = date('Y-m-d H:i:s', time() + $delay);
        }
        
        // Update delivery log
        $wpdb->update(
            $wpdb->prefix . 'eia_webhook_logs',
            array(
                'status' => $status,
                'attempts' => $attempts,
                'error_message' => $error_message,
                'response_time' => $response_time,
                'scheduled_at' => $next_attempt,
                'delivered_at' => $status === 'failed' ? current_time('mysql') : null
            ),
            array('id' => $delivery['id']),
            array('%s', '%d', '%s', '%f', '%s', '%s'),
            array('%d')
        );
        
        // Update webhook error timestamp
        $wpdb->update(
            $wpdb->prefix . 'eia_webhooks',
            array(
                'last_triggered' => current_time('mysql'),
                'last_error' => $error_message
            ),
            array('id' => $webhook['id']),
            array('%s', '%s'),
            array('%d')
        );
        
        // Disable webhook after max failures
        if ($status === 'failed') {
            $recent_failures = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}eia_webhook_logs 
                 WHERE webhook_id = %d AND status = 'failed' 
                 AND delivered_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
                $webhook['id']
            ));
            
            if ($recent_failures >= 5) {
                $wpdb->update(
                    $wpdb->prefix . 'eia_webhooks',
                    array('status' => 'disabled'),
                    array('id' => $webhook['id']),
                    array('%s'),
                    array('%d')
                );
            }
        }
    }
    
    /**
     * Generate webhook signature
     */
    private function generate_webhook_signature($payload, $secret) {
        $json_payload = json_encode($payload);
        return 'sha256=' . hash_hmac('sha256', $json_payload, $secret);
    }
    
    /**
     * Verify webhook signature
     */
    public function verify_webhook_signature($request) {
        $webhook_id = $request->get_param('id');
        $signature = $request->get_header('X-EIA-Signature');
        
        if (!$signature) {
            return false;
        }
        
        // Get webhook by webhook_id (not database id)
        global $wpdb;
        $webhook = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}eia_webhooks WHERE webhook_id = %s",
            $webhook_id
        ), ARRAY_A);
        
        if (!$webhook) {
            return false;
        }
        
        $body = $request->get_body();
        $expected_signature = 'sha256=' . hash_hmac('sha256', $body, $webhook['secret']);
        
        return hash_equals($expected_signature, $signature);
    }
    
    /**
     * Handle incoming webhook
     */
    public function handle_incoming_webhook($request) {
        $webhook_id = $request->get_param('id');
        $body = json_decode($request->get_body(), true);
        
        // Log incoming webhook
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'eia_webhook_logs',
            array(
                'webhook_id' => 0, // Incoming webhook
                'event' => 'incoming_webhook',
                'payload' => $request->get_body(),
                'status' => 'received',
                'attempts' => 1,
                'created_at' => current_time('mysql'),
                'delivered_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%d', '%s', '%s')
        );
        
        // Process incoming webhook based on webhook_id
        do_action('eia_incoming_webhook', $webhook_id, $body, $request);
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Webhook received successfully'
        ));
    }
    
    /**
     * Environmental event webhook triggers
     */
    public function trigger_weather_webhook($data) {
        $this->trigger_webhook('weather.alert', $data);
    }
    
    public function trigger_air_quality_webhook($data) {
        $this->trigger_webhook('air_quality.alert', $data);
    }
    
    public function trigger_data_webhook($data) {
        $this->trigger_webhook('data.update', $data);
    }
    
    /**
     * AJAX handlers
     */
    public function ajax_test_webhook() {
        check_ajax_referer('eia_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $webhook_id = intval($_POST['webhook_id'] ?? 0);
        
        if (!$webhook_id) {
            wp_send_json_error('Webhook ID is required');
        }
        
        $test_data = array(
            'test' => true,
            'message' => 'This is a test webhook delivery',
            'timestamp' => current_time('mysql')
        );
        
        $this->trigger_webhook('test', $test_data, $webhook_id);
        
        wp_send_json_success('Test webhook queued for delivery');
    }
    
    /**
     * Permission callbacks
     */
    public function check_admin_permissions() {
        return current_user_can('manage_options');
    }
    
    public function check_webhook_permissions() {
        return true; // Allow status checks
    }
}

// Initialize the class
EIA_Webhook_System::get_instance();
