<?php

/**
 * Push Notifications Handler for Environmental Platform
 * Manages web push notifications using service workers and VAPID
 */
class EN_Push_Notifications {

    private $subscriptions_table;
    private $vapid_public_key;
    private $vapid_private_key;

    public function __construct() {
        global $wpdb;
        $this->subscriptions_table = $wpdb->prefix . 'en_push_subscriptions';
        $this->vapid_public_key = get_option('en_vapid_public_key', '');
        $this->vapid_private_key = get_option('en_vapid_private_key', '');
    }

    /**
     * Subscribe user to push notifications
     */
    public function subscribe($user_id, $subscription_data) {
        global $wpdb;

        // Validate subscription data
        if (empty($subscription_data['endpoint']) || empty($subscription_data['keys']['p256dh']) || empty($subscription_data['keys']['auth'])) {
            return new WP_Error('invalid_subscription', __('Invalid subscription data.', 'environmental-notifications'));
        }

        $subscription = array(
            'user_id' => intval($user_id),
            'endpoint' => esc_url_raw($subscription_data['endpoint']),
            'p256dh' => sanitize_text_field($subscription_data['keys']['p256dh']),
            'auth' => sanitize_text_field($subscription_data['keys']['auth']),
            'device_type' => isset($subscription_data['device_type']) ? sanitize_text_field($subscription_data['device_type']) : 'web',
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
            'is_active' => 1,
            'created_at' => current_time('mysql')
        );

        // Check if subscription already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->subscriptions_table} WHERE user_id = %d AND endpoint = %s",
            $user_id,
            $subscription['endpoint']
        ));

        if ($existing) {
            // Update existing subscription
            $result = $wpdb->update(
                $this->subscriptions_table,
                array(
                    'p256dh' => $subscription['p256dh'],
                    'auth' => $subscription['auth'],
                    'is_active' => 1,
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $existing)
            );
        } else {
            // Insert new subscription
            $result = $wpdb->insert($this->subscriptions_table, $subscription);
        }

        if ($result === false) {
            return new WP_Error('subscription_failed', __('Failed to save subscription.', 'environmental-notifications'));
        }

        return array(
            'success' => true,
            'message' => __('Successfully subscribed to push notifications.', 'environmental-notifications')
        );
    }

    /**
     * Unsubscribe user from push notifications
     */
    public function unsubscribe($user_id, $endpoint = null) {
        global $wpdb;

        $where_conditions = array('user_id = %d');
        $where_values = array($user_id);

        if ($endpoint) {
            $where_conditions[] = 'endpoint = %s';
            $where_values[] = $endpoint;
        }

        $result = $wpdb->update(
            $this->subscriptions_table,
            array('is_active' => 0, 'updated_at' => current_time('mysql')),
            $where_conditions
        );

        return $result !== false;
    }

    /**
     * Send push notification
     */
    public function send_notification($notification) {
        if (!$this->is_configured()) {
            return false;
        }

        $subscriptions = $this->get_user_subscriptions($notification->user_id);
        if (empty($subscriptions)) {
            return false;
        }

        $payload = $this->prepare_payload($notification);
        $sent_count = 0;

        foreach ($subscriptions as $subscription) {
            if ($this->send_to_subscription($subscription, $payload)) {
                $sent_count++;
            }
        }

        return $sent_count > 0;
    }

    /**
     * Send notification to specific subscription
     */
    private function send_to_subscription($subscription, $payload) {
        if (!$this->is_configured()) {
            return false;
        }

        try {
            // Create WebPush instance (simplified - in real implementation use a library like minishlink/web-push)
            $webpush_payload = array(
                'endpoint' => $subscription->endpoint,
                'keys' => array(
                    'p256dh' => $subscription->p256dh,
                    'auth' => $subscription->auth
                ),
                'payload' => json_encode($payload)
            );

            // This is a simplified implementation
            // In production, use a proper WebPush library
            $result = $this->send_webpush($webpush_payload);

            if (!$result) {
                // Mark subscription as inactive if it fails
                $this->mark_subscription_inactive($subscription->id);
                return false;
            }

            return true;

        } catch (Exception $e) {
            error_log('Push notification error: ' . $e->getMessage());
            $this->mark_subscription_inactive($subscription->id);
            return false;
        }
    }

    /**
     * Prepare push notification payload
     */
    private function prepare_payload($notification) {
        $data = json_decode($notification->data, true) ?: array();
        
        return array(
            'title' => $notification->title,
            'body' => $notification->message,
            'icon' => get_option('en_push_icon', '/wp-content/plugins/environmental-notifications/assets/images/icon-192x192.png'),
            'badge' => get_option('en_push_badge', '/wp-content/plugins/environmental-notifications/assets/images/badge-72x72.png'),
            'tag' => 'environmental-notification-' . $notification->id,
            'data' => array_merge($data, array(
                'notification_id' => $notification->id,
                'type' => $notification->type,
                'url' => $this->get_notification_url($notification),
                'timestamp' => strtotime($notification->created_at)
            )),
            'actions' => $this->get_notification_actions($notification),
            'requireInteraction' => $notification->priority === 'urgent',
            'silent' => $notification->priority === 'low'
        );
    }

    /**
     * Get notification actions based on type
     */
    private function get_notification_actions($notification) {
        $actions = array();

        switch ($notification->type) {
            case 'waste_report':
                $actions[] = array(
                    'action' => 'view',
                    'title' => __('View Report', 'environmental-notifications'),
                    'icon' => '/wp-content/plugins/environmental-notifications/assets/images/view-icon.png'
                );
                break;

            case 'environmental_event':
                $actions[] = array(
                    'action' => 'rsvp',
                    'title' => __('RSVP', 'environmental-notifications'),
                    'icon' => '/wp-content/plugins/environmental-notifications/assets/images/rsvp-icon.png'
                );
                break;

            case 'achievement':
                $actions[] = array(
                    'action' => 'share',
                    'title' => __('Share', 'environmental-notifications'),
                    'icon' => '/wp-content/plugins/environmental-notifications/assets/images/share-icon.png'
                );
                break;

            case 'forum_post':
                $actions[] = array(
                    'action' => 'reply',
                    'title' => __('Reply', 'environmental-notifications'),
                    'icon' => '/wp-content/plugins/environmental-notifications/assets/images/reply-icon.png'
                );
                break;
        }

        // Always add mark as read action
        $actions[] = array(
            'action' => 'mark_read',
            'title' => __('Mark as Read', 'environmental-notifications'),
            'icon' => '/wp-content/plugins/environmental-notifications/assets/images/check-icon.png'
        );

        return $actions;
    }

    /**
     * Get notification URL based on type
     */
    private function get_notification_url($notification) {
        $data = json_decode($notification->data, true) ?: array();
        
        switch ($notification->type) {
            case 'waste_report':
                return home_url('/environmental-dashboard/waste-reports/');
                
            case 'environmental_event':
                return isset($data['event_id']) ? home_url('/events/' . $data['event_id']) : home_url('/events/');
                
            case 'achievement':
                return home_url('/profile/achievements/');
                
            case 'forum_post':
                return isset($data['forum_id']) ? home_url('/forums/' . $data['forum_id']) : home_url('/forums/');
                
            case 'petition_signed':
                return isset($data['petition_id']) ? home_url('/petitions/' . $data['petition_id']) : home_url('/petitions/');
                
            default:
                return home_url('/environmental-dashboard/notifications/');
        }
    }

    /**
     * Get user subscriptions
     */
    public function get_user_subscriptions($user_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->subscriptions_table} WHERE user_id = %d AND is_active = 1",
            $user_id
        ));
    }

    /**
     * Mark subscription as inactive
     */
    private function mark_subscription_inactive($subscription_id) {
        global $wpdb;
        
        return $wpdb->update(
            $this->subscriptions_table,
            array('is_active' => 0, 'updated_at' => current_time('mysql')),
            array('id' => $subscription_id)
        );
    }

    /**
     * Check if push notifications are properly configured
     */
    public function is_configured() {
        return !empty($this->vapid_public_key) && !empty($this->vapid_private_key);
    }

    /**
     * Generate VAPID keys
     */
    public function generate_vapid_keys() {
        // This is a simplified implementation
        // In production, use a proper VAPID key generation library
        $keys = array(
            'public' => $this->generate_public_key(),
            'private' => $this->generate_private_key()
        );

        update_option('en_vapid_public_key', $keys['public']);
        update_option('en_vapid_private_key', $keys['private']);

        return $keys;
    }

    /**
     * Send test push notification
     */
    public function send_test_notification($user_id) {
        $test_notification = (object) array(
            'id' => 0,
            'user_id' => $user_id,
            'type' => 'test',
            'title' => __('Test Push Notification', 'environmental-notifications'),
            'message' => __('This is a test push notification from the Environmental Platform.', 'environmental-notifications'),
            'data' => json_encode(array('test' => true)),
            'priority' => 'normal',
            'created_at' => current_time('mysql')
        );

        return $this->send_notification($test_notification);
    }

    /**
     * Bulk send notifications
     */
    public function bulk_send($notifications) {
        $results = array();
        
        foreach ($notifications as $notification) {
            $results[] = $this->send_notification($notification);
        }
        
        return $results;
    }

    /**
     * Clean up inactive subscriptions
     */
    public function cleanup_subscriptions() {
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime('-30 days'));
        
        return $wpdb->delete(
            $this->subscriptions_table,
            array(
                'is_active' => 0,
                'updated_at <' => $cutoff_date
            )
        );
    }

    /**
     * Get subscription statistics
     */
    public function get_subscription_stats() {
        global $wpdb;
        
        return $wpdb->get_row("
            SELECT 
                COUNT(*) as total_subscriptions,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_subscriptions,
                COUNT(DISTINCT user_id) as unique_users,
                COUNT(DISTINCT device_type) as device_types
            FROM {$this->subscriptions_table}
        ");
    }

    /**
     * Simplified WebPush implementation
     * In production, use a proper library like minishlink/web-push
     */
    private function send_webpush($webpush_payload) {
        // This is a placeholder for the actual WebPush implementation
        // You would need to implement proper VAPID authentication and encryption
        
        $response = wp_remote_post($webpush_payload['endpoint'], array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'TTL' => '86400',
                // Add proper VAPID headers here
            ),
            'body' => $webpush_payload['payload'],
            'timeout' => 30
        ));

        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }

    /**
     * Generate public key (simplified)
     */
    private function generate_public_key() {
        return 'BP' . wp_generate_password(85, false);
    }

    /**
     * Generate private key (simplified)
     */
    private function generate_private_key() {
        return wp_generate_password(43, false);
    }

    /**
     * Get push notification settings
     */
    public function get_settings() {
        return array(
            'enabled' => get_option('en_push_notifications_enabled', true),
            'vapid_configured' => $this->is_configured(),
            'icon' => get_option('en_push_icon', ''),
            'badge' => get_option('en_push_badge', ''),
            'public_key' => $this->vapid_public_key
        );
    }

    /**
     * Update push notification settings
     */
    public function update_settings($settings) {
        $options = array(
            'en_push_notifications_enabled' => isset($settings['enabled']) ? (bool) $settings['enabled'] : true,
            'en_push_icon' => isset($settings['icon']) ? esc_url($settings['icon']) : '',
            'en_push_badge' => isset($settings['badge']) ? esc_url($settings['badge']) : ''
        );

        foreach ($options as $key => $value) {
            update_option($key, $value);
        }

        return true;
    }
}
