<?php
/**
 * Webhook Manager Class
 * 
 * Handles webhook registration and delivery for real-time updates
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Mobile_API_Webhook_Manager {
    
    private $settings;
    
    public function __construct() {
        $this->settings = get_option('environmental_mobile_api_settings', array());
        
        // Hook into various WordPress actions for webhook triggers
        $this->init_webhook_triggers();
        
        // Schedule webhook retry
        add_action('environmental_mobile_api_retry_webhooks', array($this, 'retry_failed_webhooks'));
        if (!wp_next_scheduled('environmental_mobile_api_retry_webhooks')) {
            wp_schedule_event(time(), 'hourly', 'environmental_mobile_api_retry_webhooks');
        }
    }
    
    /**
     * Initialize webhook triggers
     */
    private function init_webhook_triggers() {
        // User events
        add_action('user_register', array($this, 'trigger_user_created'));
        add_action('profile_update', array($this, 'trigger_user_updated'));
        add_action('delete_user', array($this, 'trigger_user_deleted'));
        
        // Content events
        add_action('save_post', array($this, 'trigger_post_saved'), 10, 2);
        add_action('delete_post', array($this, 'trigger_post_deleted'));
        add_action('comment_post', array($this, 'trigger_comment_created'));
        
        // Environmental data events
        add_action('environmental_data_updated', array($this, 'trigger_environmental_data_updated'));
        add_action('environmental_alert_created', array($this, 'trigger_environmental_alert'));
        
        // Custom events
        add_action('environmental_petition_signed', array($this, 'trigger_petition_signed'));
        add_action('environmental_exchange_created', array($this, 'trigger_exchange_created'));
        add_action('environmental_event_registered', array($this, 'trigger_event_registration'));
    }
    
    /**
     * Register a webhook
     */
    public function register_webhook($name, $url, $events, $secret = null) {
        global $wpdb;
        
        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return new WP_Error('invalid_url', 'Invalid webhook URL');
        }
        
        // Validate events
        $valid_events = $this->get_valid_events();
        foreach ($events as $event) {
            if (!in_array($event, $valid_events)) {
                return new WP_Error('invalid_event', 'Invalid event: ' . $event);
            }
        }
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'environmental_mobile_api_webhooks',
            array(
                'name' => sanitize_text_field($name),
                'url' => esc_url_raw($url),
                'events' => json_encode($events),
                'secret' => $secret ? sanitize_text_field($secret) : wp_generate_password(32, false),
                'is_active' => 1,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%d', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to register webhook');
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update webhook
     */
    public function update_webhook($webhook_id, $data) {
        global $wpdb;
        
        $allowed_fields = array('name', 'url', 'events', 'secret', 'is_active');
        $update_data = array();
        $update_format = array();
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowed_fields)) {
                if ($field === 'events') {
                    $value = json_encode($value);
                    $update_format[] = '%s';
                } elseif ($field === 'is_active') {
                    $value = (int) $value;
                    $update_format[] = '%d';
                } else {
                    $update_format[] = '%s';
                }
                
                $update_data[$field] = $value;
            }
        }
        
        $update_data['updated_at'] = current_time('mysql');
        $update_format[] = '%s';
        
        return $wpdb->update(
            $wpdb->prefix . 'environmental_mobile_api_webhooks',
            $update_data,
            array('id' => $webhook_id),
            $update_format,
            array('%d')
        );
    }
    
    /**
     * Delete webhook
     */
    public function delete_webhook($webhook_id) {
        global $wpdb;
        
        return $wpdb->delete(
            $wpdb->prefix . 'environmental_mobile_api_webhooks',
            array('id' => $webhook_id),
            array('%d')
        );
    }
    
    /**
     * Get webhooks
     */
    public function get_webhooks($active_only = true) {
        global $wpdb;
        
        $where = $active_only ? 'WHERE is_active = 1' : '';
        
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}environmental_mobile_api_webhooks $where ORDER BY created_at DESC"
        );
    }
    
    /**
     * Get webhook by ID
     */
    public function get_webhook($webhook_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}environmental_mobile_api_webhooks WHERE id = %d",
            $webhook_id
        ));
    }
    
    /**
     * Trigger webhook event
     */
    public function trigger_event($event_type, $data) {
        $webhooks = $this->get_webhooks_for_event($event_type);
        
        foreach ($webhooks as $webhook) {
            $this->send_webhook($webhook, $event_type, $data);
        }
    }
    
    /**
     * Get webhooks for specific event
     */
    private function get_webhooks_for_event($event_type) {
        global $wpdb;
        
        $webhooks = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}environmental_mobile_api_webhooks 
             WHERE is_active = 1 AND JSON_CONTAINS(events, JSON_QUOTE('$event_type'))"
        );
        
        // Fallback for databases that don't support JSON_CONTAINS
        if (empty($webhooks)) {
            $all_webhooks = $this->get_webhooks();
            $webhooks = array();
            
            foreach ($all_webhooks as $webhook) {
                $events = json_decode($webhook->events, true);
                if (in_array($event_type, $events)) {
                    $webhooks[] = $webhook;
                }
            }
        }
        
        return $webhooks;
    }
    
    /**
     * Send webhook
     */
    private function send_webhook($webhook, $event_type, $data) {
        $payload = array(
            'event' => $event_type,
            'data' => $data,
            'timestamp' => current_time('c'),
            'webhook_id' => $webhook->id
        );
        
        $body = json_encode($payload);
        $signature = $this->generate_signature($body, $webhook->secret);
        
        $headers = array(
            'Content-Type' => 'application/json',
            'X-Webhook-Signature' => $signature,
            'X-Webhook-Event' => $event_type,
            'User-Agent' => 'Environmental-Platform-Webhook/1.0'
        );
        
        $timeout = isset($this->settings['webhook_timeout']) ? $this->settings['webhook_timeout'] : 30;
        
        $response = wp_remote_post($webhook->url, array(
            'body' => $body,
            'headers' => $headers,
            'timeout' => $timeout,
            'blocking' => false // Send asynchronously
        ));
        
        // Log the webhook attempt
        $this->log_webhook_attempt($webhook->id, $event_type, $response);
        
        if (is_wp_error($response)) {
            $this->handle_webhook_failure($webhook, $response->get_error_message());
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            
            if ($response_code >= 200 && $response_code < 300) {
                $this->handle_webhook_success($webhook);
            } else {
                $this->handle_webhook_failure($webhook, "HTTP $response_code");
            }
        }
    }
    
    /**
     * Generate webhook signature
     */
    private function generate_signature($body, $secret) {
        return 'sha256=' . hash_hmac('sha256', $body, $secret);
    }
    
    /**
     * Verify webhook signature
     */
    public function verify_signature($body, $signature, $secret) {
        $expected_signature = $this->generate_signature($body, $secret);
        return hash_equals($signature, $expected_signature);
    }
    
    /**
     * Handle webhook success
     */
    private function handle_webhook_success($webhook) {
        global $wpdb;
        
        $wpdb->update(
            $wpdb->prefix . 'environmental_mobile_api_webhooks',
            array(
                'last_success' => current_time('mysql'),
                'retry_count' => 0,
                'last_error' => null
            ),
            array('id' => $webhook->id),
            array('%s', '%d', '%s'),
            array('%d')
        );
    }
    
    /**
     * Handle webhook failure
     */
    private function handle_webhook_failure($webhook, $error_message) {
        global $wpdb;
        
        $retry_count = $webhook->retry_count + 1;
        $max_retries = isset($this->settings['webhook_retry_attempts']) ? $this->settings['webhook_retry_attempts'] : 3;
        
        $update_data = array(
            'retry_count' => $retry_count,
            'last_error' => $error_message
        );
        
        // Disable webhook if max retries exceeded
        if ($retry_count >= $max_retries) {
            $update_data['is_active'] = 0;
        }
        
        $wpdb->update(
            $wpdb->prefix . 'environmental_mobile_api_webhooks',
            $update_data,
            array('id' => $webhook->id),
            array('%d', '%s', '%d'),
            array('%d')
        );
    }
    
    /**
     * Log webhook attempt
     */
    private function log_webhook_attempt($webhook_id, $event_type, $response) {
        // Simple logging - in production, you might want more detailed logging
        error_log("Webhook $webhook_id: Event $event_type - " . 
                 (is_wp_error($response) ? $response->get_error_message() : 'Success'));
    }
    
    /**
     * Retry failed webhooks
     */
    public function retry_failed_webhooks() {
        global $wpdb;
        
        $failed_webhooks = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}environmental_mobile_api_webhooks 
             WHERE is_active = 1 AND retry_count > 0 AND retry_count < 3
             AND last_error IS NOT NULL"
        );
        
        foreach ($failed_webhooks as $webhook) {
            // Retry with exponential backoff
            $delay = pow(2, $webhook->retry_count) * 60; // 2, 4, 8 minutes
            
            if (strtotime($webhook->updated_at) + $delay <= time()) {
                // Trigger a test event to retry
                $this->send_webhook($webhook, 'webhook.retry', array(
                    'webhook_id' => $webhook->id,
                    'retry_attempt' => $webhook->retry_count + 1
                ));
            }
        }
    }
    
    /**
     * Get valid webhook events
     */
    public function get_valid_events() {
        return array(
            // User events
            'user.created',
            'user.updated',
            'user.deleted',
            
            // Content events
            'post.created',
            'post.updated',
            'post.deleted',
            'comment.created',
            
            // Environmental events
            'environmental.data_updated',
            'environmental.alert_created',
            'environmental.petition_signed',
            'environmental.exchange_created',
            'environmental.event_registered',
            
            // System events
            'system.maintenance',
            'webhook.retry'
        );
    }
    
    /**
     * Test webhook
     */
    public function test_webhook($webhook_id) {
        $webhook = $this->get_webhook($webhook_id);
        
        if (!$webhook) {
            return new WP_Error('webhook_not_found', 'Webhook not found');
        }
        
        $test_data = array(
            'message' => 'This is a test webhook',
            'timestamp' => current_time('c'),
            'test' => true
        );
        
        $this->send_webhook($webhook, 'webhook.test', $test_data);
        
        return array(
            'message' => 'Test webhook sent',
            'webhook_id' => $webhook_id,
            'url' => $webhook->url
        );
    }
    
    /**
     * Webhook event triggers
     */
    public function trigger_user_created($user_id) {
        $user = get_userdata($user_id);
        
        $this->trigger_event('user.created', array(
            'user_id' => $user_id,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'roles' => $user->roles
        ));
    }
    
    public function trigger_user_updated($user_id) {
        $user = get_userdata($user_id);
        
        $this->trigger_event('user.updated', array(
            'user_id' => $user_id,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'roles' => $user->roles
        ));
    }
    
    public function trigger_user_deleted($user_id) {
        $this->trigger_event('user.deleted', array(
            'user_id' => $user_id
        ));
    }
    
    public function trigger_post_saved($post_id, $post) {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }
        
        $event_type = $post->post_date === $post->post_modified ? 'post.created' : 'post.updated';
        
        $this->trigger_event($event_type, array(
            'post_id' => $post_id,
            'title' => $post->post_title,
            'content' => $post->post_content,
            'status' => $post->post_status,
            'type' => $post->post_type,
            'author' => $post->post_author,
            'date' => $post->post_date
        ));
    }
    
    public function trigger_post_deleted($post_id) {
        $this->trigger_event('post.deleted', array(
            'post_id' => $post_id
        ));
    }
    
    public function trigger_comment_created($comment_id) {
        $comment = get_comment($comment_id);
        
        $this->trigger_event('comment.created', array(
            'comment_id' => $comment_id,
            'post_id' => $comment->comment_post_ID,
            'author' => $comment->comment_author,
            'email' => $comment->comment_author_email,
            'content' => $comment->comment_content,
            'date' => $comment->comment_date
        ));
    }
    
    public function trigger_environmental_data_updated($data) {
        $this->trigger_event('environmental.data_updated', $data);
    }
    
    public function trigger_environmental_alert($alert_data) {
        $this->trigger_event('environmental.alert_created', $alert_data);
    }
    
    public function trigger_petition_signed($petition_data) {
        $this->trigger_event('environmental.petition_signed', $petition_data);
    }
    
    public function trigger_exchange_created($exchange_data) {
        $this->trigger_event('environmental.exchange_created', $exchange_data);
    }
    
    public function trigger_event_registration($event_data) {
        $this->trigger_event('environmental.event_registered', $event_data);
    }
    
    /**
     * Get webhook statistics
     */
    public function get_webhook_stats() {
        global $wpdb;
        
        return array(
            'total_webhooks' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}environmental_mobile_api_webhooks"
            ),
            'active_webhooks' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}environmental_mobile_api_webhooks WHERE is_active = 1"
            ),
            'failed_webhooks' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}environmental_mobile_api_webhooks 
                 WHERE last_error IS NOT NULL AND updated_at > DATE_SUB(NOW(), INTERVAL 1 DAY)"
            ),
            'successful_deliveries' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}environmental_mobile_api_webhooks 
                 WHERE last_success IS NOT NULL AND last_success > DATE_SUB(NOW(), INTERVAL 1 DAY)"
            )
        );
    }
}
