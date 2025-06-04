<?php
/**
 * Push Notifications System for Item Exchange Platform
 * 
 * Handles real-time notifications for exchange activities,
 * matches, messages, and environmental milestones
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Item_Exchange_Notifications {
    
    private static $instance = null;
    private $channels;
    private $notification_types;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_notification_channels();
        $this->init_notification_types();
        
        // Hook into WordPress actions
        add_action('wp_ajax_ep_mark_notification_read', array($this, 'ajax_mark_notification_read'));
        add_action('wp_ajax_ep_get_notifications', array($this, 'ajax_get_notifications'));
        add_action('wp_ajax_ep_update_notification_preferences', array($this, 'ajax_update_preferences'));
        
        // Hook into exchange events
        add_action('ep_new_exchange_posted', array($this, 'notify_new_exchange'), 10, 2);
        add_action('ep_exchange_matched', array($this, 'notify_exchange_match'), 10, 3);
        add_action('ep_message_received', array($this, 'notify_new_message'), 10, 3);
        add_action('ep_exchange_completed', array($this, 'notify_exchange_completed'), 10, 2);
        add_action('ep_environmental_milestone', array($this, 'notify_environmental_milestone'), 10, 3);
        
        // Schedule notification cleanup
        if (!wp_next_scheduled('ep_cleanup_notifications')) {
            wp_schedule_event(time(), 'daily', 'ep_cleanup_notifications');
        }
        add_action('ep_cleanup_notifications', array($this, 'cleanup_old_notifications'));
        
        // WebSocket support preparation
        add_action('init', array($this, 'maybe_init_websocket'));
    }
    
    /**
     * Initialize notification channels
     */
    private function init_notification_channels() {
        $this->channels = array(
            'in_app' => array(
                'name' => __('In-App Notifications', 'environmental-item-exchange'),
                'enabled' => true,
                'settings' => array()
            ),
            'email' => array(
                'name' => __('Email Notifications', 'environmental-item-exchange'),
                'enabled' => true,
                'settings' => array(
                    'digest_frequency' => 'daily' // immediate, hourly, daily, weekly
                )
            ),
            'push' => array(
                'name' => __('Push Notifications', 'environmental-item-exchange'),
                'enabled' => false, // Requires setup
                'settings' => array(
                    'service_worker_url' => '',
                    'vapid_public_key' => get_option('ep_vapid_public_key', ''),
                    'vapid_private_key' => get_option('ep_vapid_private_key', '')
                )
            ),
            'sms' => array(
                'name' => __('SMS Notifications', 'environmental-item-exchange'),
                'enabled' => false, // Requires Twilio setup
                'settings' => array(
                    'twilio_sid' => get_option('ep_twilio_sid', ''),
                    'twilio_token' => get_option('ep_twilio_token', ''),
                    'twilio_phone' => get_option('ep_twilio_phone', '')
                )
            ),
            'webhook' => array(
                'name' => __('Webhook Notifications', 'environmental-item-exchange'),
                'enabled' => false,
                'settings' => array(
                    'webhook_url' => get_option('ep_webhook_url', ''),
                    'webhook_secret' => get_option('ep_webhook_secret', '')
                )
            )
        );
    }
    
    /**
     * Initialize notification types
     */
    private function init_notification_types() {
        $this->notification_types = array(
            'new_match' => array(
                'title' => __('New Match Found', 'environmental-item-exchange'),
                'template' => __('We found a perfect match for your item: {item_title}', 'environmental-item-exchange'),
                'icon' => '🎯',
                'priority' => 'high',
                'channels' => array('in_app', 'email', 'push')
            ),
            'new_message' => array(
                'title' => __('New Message', 'environmental-item-exchange'),
                'template' => __('You have a new message about: {item_title}', 'environmental-item-exchange'),
                'icon' => '💬',
                'priority' => 'high',
                'channels' => array('in_app', 'email', 'push')
            ),
            'exchange_completed' => array(
                'title' => __('Exchange Completed', 'environmental-item-exchange'),
                'template' => __('Congratulations! Your exchange of {item_title} has been completed.', 'environmental-item-exchange'),
                'icon' => '✅',
                'priority' => 'medium',
                'channels' => array('in_app', 'email')
            ),
            'environmental_milestone' => array(
                'title' => __('Environmental Milestone', 'environmental-item-exchange'),
                'template' => __('Amazing! You\'ve saved {carbon_amount} kg of CO2 through exchanges!', 'environmental-item-exchange'),
                'icon' => '🌱',
                'priority' => 'medium',
                'channels' => array('in_app', 'email', 'push')
            ),
            'item_expiring' => array(
                'title' => __('Item Expiring Soon', 'environmental-item-exchange'),
                'template' => __('Your item {item_title} will expire in {days} days.', 'environmental-item-exchange'),
                'icon' => '⏰',
                'priority' => 'low',
                'channels' => array('in_app', 'email')
            ),
            'trust_level_up' => array(
                'title' => __('Trust Level Increased', 'environmental-item-exchange'),
                'template' => __('Congratulations! You\'ve reached {trust_level} trust level!', 'environmental-item-exchange'),
                'icon' => '⭐',
                'priority' => 'medium',
                'channels' => array('in_app', 'email')
            ),
            'saved_search_alert' => array(
                'title' => __('Saved Search Alert', 'environmental-item-exchange'),
                'template' => __('New items matching your saved search "{search_name}" are available!', 'environmental-item-exchange'),
                'icon' => '🔍',
                'priority' => 'medium',
                'channels' => array('in_app', 'email', 'push')
            ),
            'rating_request' => array(
                'title' => __('Rate Your Exchange', 'environmental-item-exchange'),
                'template' => __('Please rate your recent exchange with {user_name}.', 'environmental-item-exchange'),
                'icon' => '⭐',
                'priority' => 'low',
                'channels' => array('in_app', 'email')
            )
        );
    }
    
    /**
     * Send notification to user
     */
    public function send_notification($user_id, $type, $data = array(), $channels = null) {
        if (!isset($this->notification_types[$type])) {
            return false;
        }
        
        $notification_config = $this->notification_types[$type];
        $user_preferences = $this->get_user_preferences($user_id);
        
        // Use specified channels or default ones
        $target_channels = $channels ?: $notification_config['channels'];
        
        // Filter channels based on user preferences
        $target_channels = array_filter($target_channels, function($channel) use ($user_preferences, $type) {
            return $user_preferences[$channel][$type] ?? true;
        });
        
        if (empty($target_channels)) {
            return false;
        }
        
        // Prepare notification data
        $notification_data = array(
            'user_id' => $user_id,
            'type' => $type,
            'title' => $notification_config['title'],
            'message' => $this->render_template($notification_config['template'], $data),
            'icon' => $notification_config['icon'],
            'priority' => $notification_config['priority'],
            'data' => $data,
            'channels' => $target_channels,
            'created_at' => current_time('mysql'),
            'read_at' => null
        );
        
        // Store notification in database
        $notification_id = $this->store_notification($notification_data);
        
        // Send via each channel
        $results = array();
        foreach ($target_channels as $channel) {
            $results[$channel] = $this->send_via_channel($channel, $notification_data);
        }
        
        return array(
            'notification_id' => $notification_id,
            'channels' => $results
        );
    }
    
    /**
     * Store notification in database
     */
    private function store_notification($notification_data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'exchange_notifications';
        
        $wpdb->insert(
            $table,
            array(
                'user_id' => $notification_data['user_id'],
                'type' => $notification_data['type'],
                'title' => $notification_data['title'],
                'message' => $notification_data['message'],
                'icon' => $notification_data['icon'],
                'priority' => $notification_data['priority'],
                'data' => json_encode($notification_data['data']),
                'channels' => json_encode($notification_data['channels']),
                'created_at' => $notification_data['created_at'],
                'read_at' => $notification_data['read_at']
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Send notification via specific channel
     */
    private function send_via_channel($channel, $notification_data) {
        switch ($channel) {
            case 'in_app':
                return $this->send_in_app_notification($notification_data);
            case 'email':
                return $this->send_email_notification($notification_data);
            case 'push':
                return $this->send_push_notification($notification_data);
            case 'sms':
                return $this->send_sms_notification($notification_data);
            case 'webhook':
                return $this->send_webhook_notification($notification_data);
            default:
                return false;
        }
    }
    
    /**
     * Send in-app notification
     */
    private function send_in_app_notification($notification_data) {
        // In-app notifications are stored in database and displayed in UI
        // This could be enhanced with WebSocket for real-time delivery
        return true;
    }
    
    /**
     * Send email notification
     */
    private function send_email_notification($notification_data) {
        $user = get_userdata($notification_data['user_id']);
        if (!$user) {
            return false;
        }
        
        $subject = $notification_data['title'];
        $message = $this->render_email_template($notification_data);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_option('blogname') . ' <' . get_option('admin_email') . '>'
        );
        
        return wp_mail($user->user_email, $subject, $message, $headers);
    }
    
    /**
     * Send push notification
     */
    private function send_push_notification($notification_data) {
        if (!$this->channels['push']['enabled']) {
            return false;
        }
        
        $user_tokens = get_user_meta($notification_data['user_id'], '_push_tokens', true) ?: array();
        if (empty($user_tokens)) {
            return false;
        }
        
        $payload = array(
            'title' => $notification_data['title'],
            'body' => $notification_data['message'],
            'icon' => $notification_data['icon'],
            'data' => $notification_data['data']
        );
        
        // Use Web Push library (would need to be installed)
        return $this->send_web_push($user_tokens, $payload);
    }
    
    /**
     * Send SMS notification
     */
    private function send_sms_notification($notification_data) {
        if (!$this->channels['sms']['enabled']) {
            return false;
        }
        
        $user_phone = get_user_meta($notification_data['user_id'], '_phone_number', true);
        if (!$user_phone) {
            return false;
        }
        
        // Use Twilio API (would need to be implemented)
        return $this->send_twilio_sms($user_phone, $notification_data['message']);
    }
    
    /**
     * Send webhook notification
     */
    private function send_webhook_notification($notification_data) {
        if (!$this->channels['webhook']['enabled']) {
            return false;
        }
        
        $webhook_url = $this->channels['webhook']['settings']['webhook_url'];
        if (!$webhook_url) {
            return false;
        }
        
        $payload = array(
            'event' => 'notification',
            'user_id' => $notification_data['user_id'],
            'type' => $notification_data['type'],
            'data' => $notification_data,
            'timestamp' => time()
        );
        
        // Add signature for security
        $secret = $this->channels['webhook']['settings']['webhook_secret'];
        $signature = hash_hmac('sha256', json_encode($payload), $secret);
        
        $response = wp_remote_post($webhook_url, array(
            'body' => json_encode($payload),
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-Signature' => 'sha256=' . $signature
            ),
            'timeout' => 30
        ));
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
    
    /**
     * Render notification template
     */
    private function render_template($template, $data) {
        foreach ($data as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        return $template;
    }
    
    /**
     * Render email template
     */
    private function render_email_template($notification_data) {
        $template = '
        <html>
        <head>
            <style>
                .notification-container { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; }
                .notification-header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
                .notification-body { padding: 20px; background: #f9f9f9; }
                .notification-footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .cta-button { background: #4CAF50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class="notification-container">
                <div class="notification-header">
                    <h2>' . $notification_data['icon'] . ' ' . $notification_data['title'] . '</h2>
                </div>
                <div class="notification-body">
                    <p>' . $notification_data['message'] . '</p>';
        
        // Add action button if data contains URL
        if (isset($notification_data['data']['action_url'])) {
            $template .= '<p><a href="' . $notification_data['data']['action_url'] . '" class="cta-button">' . 
                        __('View Details', 'environmental-item-exchange') . '</a></p>';
        }
        
        $template .= '
                </div>
                <div class="notification-footer">
                    <p>' . sprintf(__('Sent by %s Environmental Platform', 'environmental-item-exchange'), get_option('blogname')) . '</p>
                    <p><a href="' . home_url('/my-account/notifications/') . '">' . __('Manage Notification Preferences', 'environmental-item-exchange') . '</a></p>
                </div>
            </div>
        </body>
        </html>';
        
        return $template;
    }
    
    /**
     * Get user notification preferences
     */
    private function get_user_preferences($user_id) {
        $default_preferences = array(
            'in_app' => array(
                'new_match' => true,
                'new_message' => true,
                'exchange_completed' => true,
                'environmental_milestone' => true,
                'item_expiring' => true,
                'trust_level_up' => true,
                'saved_search_alert' => true,
                'rating_request' => true
            ),
            'email' => array(
                'new_match' => true,
                'new_message' => true,
                'exchange_completed' => true,
                'environmental_milestone' => true,
                'item_expiring' => false,
                'trust_level_up' => true,
                'saved_search_alert' => true,
                'rating_request' => false
            ),
            'push' => array(
                'new_match' => true,
                'new_message' => true,
                'exchange_completed' => false,
                'environmental_milestone' => true,
                'item_expiring' => false,
                'trust_level_up' => false,
                'saved_search_alert' => true,
                'rating_request' => false
            ),
            'sms' => array(
                'new_match' => false,
                'new_message' => false,
                'exchange_completed' => false,
                'environmental_milestone' => false,
                'item_expiring' => false,
                'trust_level_up' => false,
                'saved_search_alert' => false,
                'rating_request' => false
            ),
            'webhook' => array(
                'new_match' => false,
                'new_message' => false,
                'exchange_completed' => false,
                'environmental_milestone' => false,
                'item_expiring' => false,
                'trust_level_up' => false,
                'saved_search_alert' => false,
                'rating_request' => false
            )
        );
        
        $user_preferences = get_user_meta($user_id, '_notification_preferences', true);
        return wp_parse_args($user_preferences, $default_preferences);
    }
    
    /**
     * Get user notifications
     */
    public function get_user_notifications($user_id, $limit = 20, $unread_only = false) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'exchange_notifications';
        $where_clause = "WHERE user_id = %d";
        $params = array($user_id);
        
        if ($unread_only) {
            $where_clause .= " AND read_at IS NULL";
        }
        
        $notifications = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$table}
            {$where_clause}
            ORDER BY created_at DESC
            LIMIT %d
        ", array_merge($params, array($limit))));
        
        // Parse JSON data
        foreach ($notifications as &$notification) {
            $notification->data = json_decode($notification->data, true);
            $notification->channels = json_decode($notification->channels, true);
        }
        
        return $notifications;
    }
    
    /**
     * Mark notification as read
     */
    public function mark_as_read($notification_id, $user_id = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'exchange_notifications';
        $where = array('id' => $notification_id);
        $where_format = array('%d');
        
        if ($user_id) {
            $where['user_id'] = $user_id;
            $where_format[] = '%d';
        }
        
        return $wpdb->update(
            $table,
            array('read_at' => current_time('mysql')),
            $where,
            array('%s'),
            $where_format
        );
    }
    
    /**
     * Notification event handlers
     */
    public function notify_new_exchange($post_id, $user_id) {
        // Notify users with saved searches that match this exchange
        $this->check_saved_searches($post_id);
    }
    
    public function notify_exchange_match($post_id_1, $post_id_2, $score) {
        $post_1 = get_post($post_id_1);
        $post_2 = get_post($post_id_2);
        
        if (!$post_1 || !$post_2) return;
        
        // Notify both users about the match
        $this->send_notification($post_1->post_author, 'new_match', array(
            'item_title' => $post_2->post_title,
            'match_score' => $score,
            'action_url' => get_permalink($post_2->ID)
        ));
        
        $this->send_notification($post_2->post_author, 'new_match', array(
            'item_title' => $post_1->post_title,
            'match_score' => $score,
            'action_url' => get_permalink($post_1->ID)
        ));
    }
    
    public function notify_new_message($sender_id, $recipient_id, $message_data) {
        $this->send_notification($recipient_id, 'new_message', array(
            'sender_name' => get_userdata($sender_id)->display_name,
            'item_title' => $message_data['item_title'] ?? __('Exchange Item', 'environmental-item-exchange'),
            'action_url' => home_url('/messages/')
        ));
    }
    
    public function notify_exchange_completed($post_id, $user_id) {
        $post = get_post($post_id);
        if (!$post) return;
        
        $this->send_notification($user_id, 'exchange_completed', array(
            'item_title' => $post->post_title,
            'action_url' => get_permalink($post_id)
        ));
        
        // Schedule rating request notification for later
        wp_schedule_single_event(
            time() + DAY_IN_SECONDS,
            'ep_send_rating_request',
            array($post_id, $user_id)
        );
    }
    
    public function notify_environmental_milestone($user_id, $milestone_type, $milestone_data) {
        $this->send_notification($user_id, 'environmental_milestone', $milestone_data);
    }
    
    /**
     * Check saved searches for new items
     */
    private function check_saved_searches($post_id) {
        global $wpdb;
        
        $post = get_post($post_id);
        if (!$post) return;
        
        $post_categories = wp_get_post_terms($post_id, 'exchange_type', array('fields' => 'slugs'));
        $post_meta = get_post_meta($post_id);
        
        // Get saved searches that might match
        $saved_searches = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}exchange_saved_searches 
            WHERE is_active = 1
        ");
        
        foreach ($saved_searches as $search) {
            $criteria = json_decode($search->search_criteria, true);
            
            // Simple matching logic (can be enhanced)
            $matches = true;
            
            // Check category
            if (isset($criteria['category']) && !in_array($criteria['category'], $post_categories)) {
                $matches = false;
            }
            
            // Check exchange type
            if (isset($criteria['exchange_type'])) {
                $post_exchange_type = $post_meta['_exchange_type'][0] ?? '';
                if ($post_exchange_type !== $criteria['exchange_type']) {
                    $matches = false;
                }
            }
            
            // Check keywords
            if (isset($criteria['keywords']) && !empty($criteria['keywords'])) {
                $content = $post->post_title . ' ' . $post->post_content;
                if (stripos($content, $criteria['keywords']) === false) {
                    $matches = false;
                }
            }
            
            if ($matches) {
                $this->send_notification($search->user_id, 'saved_search_alert', array(
                    'search_name' => $search->search_name,
                    'item_title' => $post->post_title,
                    'action_url' => get_permalink($post_id)
                ));
            }
        }
    }
    
    /**
     * Cleanup old notifications
     */
    public function cleanup_old_notifications() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'exchange_notifications';
        
        // Delete read notifications older than 30 days
        $wpdb->query("
            DELETE FROM {$table}
            WHERE read_at IS NOT NULL
            AND read_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        // Delete unread notifications older than 90 days
        $wpdb->query("
            DELETE FROM {$table}
            WHERE read_at IS NULL
            AND created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
        ");
    }
    
    /**
     * WebSocket initialization (placeholder for future implementation)
     */
    public function maybe_init_websocket() {
        // This would initialize WebSocket connections for real-time notifications
        // Implementation would depend on the WebSocket library used
    }
    
    /**
     * AJAX handlers
     */
    public function ajax_get_notifications() {
        check_ajax_referer('ep_exchange_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_die('Unauthorized');
        }
        
        $limit = intval($_POST['limit'] ?? 20);
        $unread_only = isset($_POST['unread_only']) && $_POST['unread_only'] === 'true';
        
        $notifications = $this->get_user_notifications($user_id, $limit, $unread_only);
        $unread_count = $this->get_unread_count($user_id);
        
        wp_send_json_success(array(
            'notifications' => $notifications,
            'unread_count' => $unread_count
        ));
    }
    
    public function ajax_mark_notification_read() {
        check_ajax_referer('ep_exchange_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_die('Unauthorized');
        }
        
        $notification_id = intval($_POST['notification_id'] ?? 0);
        if (!$notification_id) {
            wp_die('Invalid notification ID');
        }
        
        $success = $this->mark_as_read($notification_id, $user_id);
        
        if ($success) {
            wp_send_json_success(array('message' => __('Notification marked as read', 'environmental-item-exchange')));
        } else {
            wp_send_json_error(array('message' => __('Failed to mark notification as read', 'environmental-item-exchange')));
        }
    }
    
    public function ajax_update_preferences() {
        check_ajax_referer('ep_exchange_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_die('Unauthorized');
        }
        
        $preferences = $_POST['preferences'] ?? array();
        
        // Sanitize preferences
        $sanitized_preferences = array();
        foreach ($preferences as $channel => $types) {
            if (!isset($this->channels[$channel])) continue;
            
            $sanitized_preferences[$channel] = array();
            foreach ($types as $type => $enabled) {
                if (!isset($this->notification_types[$type])) continue;
                $sanitized_preferences[$channel][$type] = (bool) $enabled;
            }
        }
        
        $success = update_user_meta($user_id, '_notification_preferences', $sanitized_preferences);
        
        if ($success) {
            wp_send_json_success(array('message' => __('Preferences updated successfully', 'environmental-item-exchange')));
        } else {
            wp_send_json_error(array('message' => __('Failed to update preferences', 'environmental-item-exchange')));
        }
    }
    
    /**
     * Get unread notification count
     */
    public function get_unread_count($user_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'exchange_notifications';
        
        return $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$table}
            WHERE user_id = %d AND read_at IS NULL
        ", $user_id));
    }
    
    /**
     * Placeholder methods for external service integration
     */
    private function send_web_push($tokens, $payload) {
        // Implement Web Push Protocol
        // This would use libraries like web-push-php
        return false;
    }
    
    private function send_twilio_sms($phone, $message) {
        // Implement Twilio SMS sending
        return false;
    }
}

// Initialize the notifications system
Environmental_Item_Exchange_Notifications::get_instance();
