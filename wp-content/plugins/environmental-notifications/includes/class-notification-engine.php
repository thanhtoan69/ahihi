<?php

/**
 * Core notification engine for Environmental Platform
 * Handles notification creation, delivery, and management
 */
class EN_Notification_Engine {

    private $table_name;
    private $analytics_table;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'en_notifications';
        $this->analytics_table = $wpdb->prefix . 'en_notification_analytics';
    }

    /**
     * Create a new notification
     */
    public function create_notification($args) {
        global $wpdb;

        $defaults = array(
            'user_id' => 0,
            'type' => 'general',
            'title' => '',
            'message' => '',
            'data' => array(),
            'priority' => 'normal',
            'scheduled_at' => null,
            'expires_at' => null
        );

        $args = wp_parse_args($args, $defaults);

        // Validate required fields
        if (empty($args['user_id']) || empty($args['title']) || empty($args['message'])) {
            return new WP_Error('missing_required_fields', __('User ID, title, and message are required.', 'environmental-notifications'));
        }

        // Prepare data for insertion
        $notification_data = array(
            'user_id' => intval($args['user_id']),
            'type' => sanitize_text_field($args['type']),
            'title' => sanitize_text_field($args['title']),
            'message' => sanitize_textarea_field($args['message']),
            'data' => is_array($args['data']) ? json_encode($args['data']) : $args['data'],
            'priority' => in_array($args['priority'], array('low', 'normal', 'high', 'urgent')) ? $args['priority'] : 'normal',
            'scheduled_at' => $args['scheduled_at'],
            'expires_at' => $args['expires_at'],
            'created_at' => current_time('mysql')
        );

        $result = $wpdb->insert($this->table_name, $notification_data);

        if ($result === false) {
            return new WP_Error('db_insert_error', __('Failed to create notification.', 'environmental-notifications'));
        }

        $notification_id = $wpdb->insert_id;

        // Process immediate delivery if not scheduled
        if (empty($args['scheduled_at'])) {
            $this->process_notification($notification_id);
        }

        // Track analytics
        $this->track_notification_event($notification_id, $args['user_id'], 'created', $args['type']);

        return $notification_id;
    }

    /**
     * Process notification delivery
     */
    public function process_notification($notification_id) {
        global $wpdb;

        $notification = $this->get_notification($notification_id);
        if (!$notification) {
            return false;
        }

        // Check if user preferences allow this notification
        if (!$this->should_send_notification($notification)) {
            return false;
        }

        $sent_channels = array();

        // Send push notification
        if (get_option('en_push_notifications_enabled', true)) {
            $push_handler = new EN_Push_Notifications();
            if ($push_handler->send_notification($notification)) {
                $sent_channels[] = 'push';
            }
        }

        // Send email notification
        if (get_option('en_email_notifications_enabled', true)) {
            $email_handler = new EN_Email_Preferences();
            if ($email_handler->send_email_notification($notification)) {
                $sent_channels[] = 'email';
            }
        }

        // Send real-time notification
        if (get_option('en_real_time_enabled', true)) {
            $realtime_handler = new EN_Real_Time_Handler();
            if ($realtime_handler->send_notification($notification)) {
                $sent_channels[] = 'realtime';
            }
        }

        // Update notification as sent
        $wpdb->update(
            $this->table_name,
            array(
                'is_sent' => 1,
                'sent_at' => current_time('mysql')
            ),
            array('id' => $notification_id)
        );

        // Track delivery analytics
        foreach ($sent_channels as $channel) {
            $this->track_notification_event($notification_id, $notification->user_id, 'sent', $notification->type, $channel);
        }

        return true;
    }

    /**
     * Get notification by ID
     */
    public function get_notification($notification_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $notification_id
        ));
    }

    /**
     * Get user notifications
     */
    public function get_user_notifications($user_id, $args = array()) {
        global $wpdb;

        $defaults = array(
            'limit' => 20,
            'offset' => 0,
            'type' => '',
            'is_read' => '',
            'priority' => '',
            'order_by' => 'created_at',
            'order' => 'DESC'
        );

        $args = wp_parse_args($args, $defaults);

        $where_conditions = array('user_id = %d');
        $where_values = array($user_id);

        if (!empty($args['type'])) {
            $where_conditions[] = 'type = %s';
            $where_values[] = $args['type'];
        }

        if ($args['is_read'] !== '') {
            $where_conditions[] = 'is_read = %d';
            $where_values[] = intval($args['is_read']);
        }

        if (!empty($args['priority'])) {
            $where_conditions[] = 'priority = %s';
            $where_values[] = $args['priority'];
        }

        // Add expiration check
        $where_conditions[] = '(expires_at IS NULL OR expires_at > %s)';
        $where_values[] = current_time('mysql');

        $where_clause = implode(' AND ', $where_conditions);
        $order_by = sanitize_sql_orderby($args['order_by'] . ' ' . $args['order']);

        $query = $wpdb->prepare("
            SELECT * FROM {$this->table_name} 
            WHERE {$where_clause} 
            ORDER BY {$order_by} 
            LIMIT %d OFFSET %d
        ", array_merge($where_values, array($args['limit'], $args['offset'])));

        $notifications = $wpdb->get_results($query);

        // Parse JSON data
        foreach ($notifications as $notification) {
            $notification->data = json_decode($notification->data, true);
        }

        return $notifications;
    }

    /**
     * Mark notification as read
     */
    public function mark_as_read($notification_id, $user_id = null) {
        global $wpdb;

        $where_conditions = array('id = %d');
        $where_values = array($notification_id);

        if ($user_id) {
            $where_conditions[] = 'user_id = %d';
            $where_values[] = $user_id;
        }

        $result = $wpdb->update(
            $this->table_name,
            array(
                'is_read' => 1,
                'read_at' => current_time('mysql')
            ),
            $where_conditions,
            array('%d', '%s'),
            array('%d')
        );

        if ($result !== false && $user_id) {
            $this->track_notification_event($notification_id, $user_id, 'read', 'notification');
        }

        return $result !== false;
    }

    /**
     * Mark all notifications as read for user
     */
    public function mark_all_as_read($user_id) {
        global $wpdb;

        return $wpdb->update(
            $this->table_name,
            array(
                'is_read' => 1,
                'read_at' => current_time('mysql')
            ),
            array('user_id' => $user_id, 'is_read' => 0),
            array('%d', '%s'),
            array('%d', '%d')
        );
    }

    /**
     * Delete notification
     */
    public function delete_notification($notification_id, $user_id = null) {
        global $wpdb;

        $where_conditions = array('id = %d');
        $where_values = array($notification_id);

        if ($user_id) {
            $where_conditions[] = 'user_id = %d';
            $where_values[] = $user_id;
        }

        return $wpdb->delete($this->table_name, $where_conditions);
    }

    /**
     * Get unread notification count
     */
    public function get_unread_count($user_id) {
        global $wpdb;

        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} 
             WHERE user_id = %d AND is_read = 0 AND (expires_at IS NULL OR expires_at > %s)",
            $user_id,
            current_time('mysql')
        )));
    }

    /**
     * Process scheduled notifications
     */
    public function process_scheduled_notifications() {
        global $wpdb;

        $scheduled_notifications = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$this->table_name} 
             WHERE scheduled_at IS NOT NULL 
             AND scheduled_at <= %s 
             AND is_sent = 0 
             LIMIT 50",
            current_time('mysql')
        ));

        foreach ($scheduled_notifications as $notification) {
            $this->process_notification($notification->id);
        }

        return count($scheduled_notifications);
    }

    /**
     * Cleanup old notifications
     */
    public function cleanup_old_notifications() {
        global $wpdb;

        $retention_days = intval(get_option('en_notification_retention_days', 30));
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));

        // Delete old read notifications
        $deleted_count = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name} 
             WHERE created_at < %s AND is_read = 1",
            $cutoff_date
        ));

        // Delete expired notifications
        $expired_count = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name} 
             WHERE expires_at IS NOT NULL AND expires_at < %s",
            current_time('mysql')
        ));

        return $deleted_count + $expired_count;
    }

    /**
     * Check if notification should be sent based on user preferences
     */
    private function should_send_notification($notification) {
        $email_prefs = new EN_Email_Preferences();
        return $email_prefs->should_send_notification($notification->user_id, $notification->type);
    }

    /**
     * Track notification events for analytics
     */
    public function track_notification_event($notification_id, $user_id, $event_type, $notification_type, $channel = 'system') {
        global $wpdb;

        if (!get_option('en_analytics_enabled', true)) {
            return false;
        }

        $analytics_data = array(
            'notification_id' => $notification_id,
            'user_id' => $user_id,
            'event_type' => $event_type,
            'notification_type' => $notification_type,
            'channel' => $channel,
            'platform' => $this->detect_platform(),
            'device_info' => $this->get_device_info(),
            'created_at' => current_time('mysql')
        );

        return $wpdb->insert($this->analytics_table, $analytics_data);
    }

    /**
     * Detect user platform
     */
    private function detect_platform() {
        if (wp_is_mobile()) {
            return 'mobile';
        }
        return 'desktop';
    }

    /**
     * Get basic device information
     */
    private function get_device_info() {
        return json_encode(array(
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
            'ip_address' => $this->get_client_ip(),
            'timestamp' => current_time('timestamp')
        ));
    }

    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
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
     * Bulk create notifications
     */
    public function bulk_create_notifications($notifications) {
        $created_ids = array();
        
        foreach ($notifications as $notification) {
            $id = $this->create_notification($notification);
            if (!is_wp_error($id)) {
                $created_ids[] = $id;
            }
        }
        
        return $created_ids;
    }

    /**
     * Get notification statistics
     */
    public function get_notification_stats($user_id = null, $days = 30) {
        global $wpdb;

        $where_clause = '';
        $where_values = array();

        if ($user_id) {
            $where_clause = 'WHERE user_id = %d';
            $where_values[] = $user_id;
        }

        $date_condition = $where_clause ? 'AND' : 'WHERE';
        $where_clause .= " {$date_condition} created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)";
        $where_values[] = $days;

        $query = $wpdb->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as read_count,
                SUM(CASE WHEN is_sent = 1 THEN 1 ELSE 0 END) as sent_count,
                COUNT(DISTINCT type) as unique_types,
                AVG(CASE WHEN read_at IS NOT NULL AND sent_at IS NOT NULL 
                    THEN TIMESTAMPDIFF(SECOND, sent_at, read_at) ELSE NULL END) as avg_read_time
            FROM {$this->table_name} 
            {$where_clause}
        ", $where_values);

        return $wpdb->get_row($query);
    }

    /**
     * Send test notification
     */
    public function send_test_notification($user_id, $type = 'test') {
        return $this->create_notification(array(
            'user_id' => $user_id,
            'type' => $type,
            'title' => __('Test Notification', 'environmental-notifications'),
            'message' => __('This is a test notification from the Environmental Platform.', 'environmental-notifications'),
            'priority' => 'normal',
            'data' => array('test' => true)
        ));
    }
}
