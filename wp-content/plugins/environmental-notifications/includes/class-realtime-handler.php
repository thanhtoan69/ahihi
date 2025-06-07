<?php
/**
 * Real-time Handler Class
 * 
 * Handles real-time notifications using Server-Sent Events (SSE) and WebSocket fallback
 * for instant notification delivery and live messaging functionality.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Realtime_Handler {
    
    private static $instance = null;
    private $connections = array();
    private $heartbeat_interval = 30; // seconds
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_ajax_en_sse_connect', array($this, 'handle_sse_connection'));
        add_action('wp_ajax_nopriv_en_sse_connect', array($this, 'handle_sse_connection'));
        add_action('wp_ajax_en_send_realtime_notification', array($this, 'send_realtime_notification'));
        add_action('wp_ajax_en_heartbeat', array($this, 'handle_heartbeat'));
        add_action('wp_ajax_nopriv_en_heartbeat', array($this, 'handle_heartbeat'));
        
        // Hook into notification sending
        add_action('environmental_notification_sent', array($this, 'broadcast_notification'), 10, 2);
        add_action('environmental_message_sent', array($this, 'broadcast_message'), 10, 2);
        
        // WebSocket support (if available)
        if (class_exists('Ratchet\Server\IoServer')) {
            add_action('init', array($this, 'init_websocket_server'));
        }
        
        // Clean up old connections
        add_action('environmental_notifications_cleanup', array($this, 'cleanup_connections'));
    }
    
    /**
     * Handle Server-Sent Events connection
     */
    public function handle_sse_connection() {
        if (!is_user_logged_in()) {
            wp_die('Unauthorized', 401);
        }
        
        $user_id = get_current_user_id();
        
        // Set headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Credentials: true');
        
        // Disable output buffering
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Send initial connection event
        $this->send_sse_event('connected', array(
            'user_id' => $user_id,
            'timestamp' => time(),
            'message' => 'Real-time connection established'
        ));
        
        // Register connection
        $connection_id = $this->register_connection($user_id, 'sse');
        
        // Keep connection alive and send pending notifications
        $last_heartbeat = time();
        $last_notification_check = time();
        
        while (connection_status() == CONNECTION_NORMAL && !connection_aborted()) {
            $current_time = time();
            
            // Send heartbeat
            if (($current_time - $last_heartbeat) >= $this->heartbeat_interval) {
                $this->send_sse_event('heartbeat', array('timestamp' => $current_time));
                $last_heartbeat = $current_time;
            }
            
            // Check for new notifications
            if (($current_time - $last_notification_check) >= 1) {
                $this->check_and_send_pending_notifications($user_id, $connection_id);
                $last_notification_check = $current_time;
            }
            
            // Small delay to prevent excessive CPU usage
            usleep(100000); // 0.1 seconds
            
            // Flush output
            if (ob_get_level()) {
                ob_flush();
            }
            flush();
        }
        
        // Clean up connection
        $this->unregister_connection($connection_id);
        exit;
    }
    
    /**
     * Send SSE event
     */
    private function send_sse_event($event, $data) {
        echo "event: {$event}\n";
        echo "data: " . wp_json_encode($data) . "\n\n";
        
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }
    
    /**
     * Register a real-time connection
     */
    private function register_connection($user_id, $type = 'sse') {
        global $wpdb;
        
        $connection_id = wp_generate_uuid4();
        $table_name = $wpdb->prefix . 'en_realtime_connections';
        
        // Create table if it doesn't exist
        $this->create_connections_table();
        
        $wpdb->insert($table_name, array(
            'connection_id' => $connection_id,
            'user_id' => $user_id,
            'connection_type' => $type,
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'connected_at' => current_time('mysql'),
            'last_activity' => current_time('mysql')
        ));
        
        return $connection_id;
    }
    
    /**
     * Unregister a connection
     */
    private function unregister_connection($connection_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'en_realtime_connections';
        $wpdb->delete($table_name, array('connection_id' => $connection_id));
    }
    
    /**
     * Update connection activity
     */
    private function update_connection_activity($connection_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'en_realtime_connections';
        $wpdb->update(
            $table_name,
            array('last_activity' => current_time('mysql')),
            array('connection_id' => $connection_id)
        );
    }
    
    /**
     * Check and send pending notifications
     */
    private function check_and_send_pending_notifications($user_id, $connection_id) {
        global $wpdb;
        
        $notifications_table = $wpdb->prefix . 'en_notifications';
        $analytics_table = $wpdb->prefix . 'en_notification_analytics';
        
        // Get undelivered real-time notifications
        $pending_notifications = $wpdb->get_results($wpdb->prepare("
            SELECT n.* 
            FROM {$notifications_table} n
            LEFT JOIN {$analytics_table} a ON n.id = a.notification_id 
                AND a.user_id = %d 
                AND a.event_type = 'realtime_delivered'
            WHERE (n.user_id = %d OR n.user_id IS NULL)
            AND n.status = 'active'
            AND (n.scheduled_at IS NULL OR n.scheduled_at <= NOW())
            AND (n.expires_at IS NULL OR n.expires_at > NOW())
            AND a.id IS NULL
            AND n.created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ORDER BY n.priority DESC, n.created_at DESC
            LIMIT 10
        ", $user_id, $user_id));
        
        foreach ($pending_notifications as $notification) {
            $this->send_notification_to_connection($notification, $user_id, $connection_id);
            
            // Track delivery
            $analytics = Environmental_Notification_Analytics::get_instance();
            $analytics->track_event($notification->id, $user_id, 'realtime_delivered', array(
                'connection_id' => $connection_id,
                'delivery_method' => 'sse'
            ));
        }
        
        // Check for new messages
        $this->check_and_send_pending_messages($user_id, $connection_id);
        
        // Update connection activity
        $this->update_connection_activity($connection_id);
    }
    
    /**
     * Check and send pending messages
     */
    private function check_and_send_pending_messages($user_id, $connection_id) {
        global $wpdb;
        
        $messages_table = $wpdb->prefix . 'en_messages';
        
        // Get unread messages in conversations user is part of
        $pending_messages = $wpdb->get_results($wpdb->prepare("
            SELECT m.* 
            FROM {$messages_table} m
            WHERE m.recipient_id = %d
            AND m.read_at IS NULL
            AND m.deleted_at IS NULL
            AND m.created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
            ORDER BY m.created_at DESC
            LIMIT 5
        ", $user_id));
        
        foreach ($pending_messages as $message) {
            $this->send_message_to_connection($message, $user_id, $connection_id);
        }
    }
    
    /**
     * Send notification to specific connection
     */
    private function send_notification_to_connection($notification, $user_id, $connection_id) {
        $notification_data = array(
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'message' => $notification->message,
            'priority' => $notification->priority,
            'data' => json_decode($notification->data, true),
            'created_at' => $notification->created_at,
            'action_url' => $notification->action_url
        );
        
        $this->send_sse_event('notification', array(
            'notification' => $notification_data,
            'user_id' => $user_id,
            'timestamp' => time()
        ));
    }
    
    /**
     * Send message to specific connection
     */
    private function send_message_to_connection($message, $user_id, $connection_id) {
        $sender = get_user_by('id', $message->sender_id);
        
        $message_data = array(
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id' => $message->sender_id,
            'sender_name' => $sender ? $sender->display_name : 'Unknown',
            'message' => $message->message,
            'attachments' => json_decode($message->attachments, true),
            'created_at' => $message->created_at
        );
        
        $this->send_sse_event('message', array(
            'message' => $message_data,
            'user_id' => $user_id,
            'timestamp' => time()
        ));
    }
    
    /**
     * Broadcast notification to all relevant users
     */
    public function broadcast_notification($notification_id, $user_ids) {
        global $wpdb;
        
        $notifications_table = $wpdb->prefix . 'en_notifications';
        $connections_table = $wpdb->prefix . 'en_realtime_connections';
        
        $notification = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$notifications_table} WHERE id = %d
        ", $notification_id));
        
        if (!$notification) {
            return;
        }
        
        // If user_ids is empty, broadcast to all active connections
        if (empty($user_ids)) {
            $active_connections = $wpdb->get_results("
                SELECT * FROM {$connections_table}
                WHERE last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ");
        } else {
            $user_ids_str = implode(',', array_map('intval', $user_ids));
            $active_connections = $wpdb->get_results("
                SELECT * FROM {$connections_table}
                WHERE user_id IN ({$user_ids_str})
                AND last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ");
        }
        
        // Store notification for real-time delivery
        foreach ($active_connections as $connection) {
            $this->queue_realtime_notification($notification, $connection->user_id, $connection->connection_id);
        }
    }
    
    /**
     * Broadcast message to relevant users
     */
    public function broadcast_message($message_id, $recipient_ids) {
        global $wpdb;
        
        $messages_table = $wpdb->prefix . 'en_messages';
        $connections_table = $wpdb->prefix . 'en_realtime_connections';
        
        $message = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$messages_table} WHERE id = %d
        ", $message_id));
        
        if (!$message || empty($recipient_ids)) {
            return;
        }
        
        $user_ids_str = implode(',', array_map('intval', $recipient_ids));
        $active_connections = $wpdb->get_results("
            SELECT * FROM {$connections_table}
            WHERE user_id IN ({$user_ids_str})
            AND last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ");
        
        // Queue message for real-time delivery
        foreach ($active_connections as $connection) {
            $this->queue_realtime_message($message, $connection->user_id, $connection->connection_id);
        }
    }
    
    /**
     * Queue notification for real-time delivery
     */
    private function queue_realtime_notification($notification, $user_id, $connection_id) {
        global $wpdb;
        
        $queue_table = $wpdb->prefix . 'en_realtime_queue';
        $this->create_queue_table();
        
        $wpdb->insert($queue_table, array(
            'connection_id' => $connection_id,
            'user_id' => $user_id,
            'type' => 'notification',
            'item_id' => $notification->id,
            'data' => wp_json_encode($notification),
            'created_at' => current_time('mysql')
        ));
    }
    
    /**
     * Queue message for real-time delivery
     */
    private function queue_realtime_message($message, $user_id, $connection_id) {
        global $wpdb;
        
        $queue_table = $wpdb->prefix . 'en_realtime_queue';
        $this->create_queue_table();
        
        $wpdb->insert($queue_table, array(
            'connection_id' => $connection_id,
            'user_id' => $user_id,
            'type' => 'message',
            'item_id' => $message->id,
            'data' => wp_json_encode($message),
            'created_at' => current_time('mysql')
        ));
    }
    
    /**
     * Handle heartbeat requests
     */
    public function handle_heartbeat() {
        if (!is_user_logged_in()) {
            wp_send_json_error('Not authenticated');
        }
        
        $connection_id = sanitize_text_field($_POST['connection_id'] ?? '');
        
        if ($connection_id) {
            $this->update_connection_activity($connection_id);
        }
        
        wp_send_json_success(array(
            'timestamp' => time(),
            'status' => 'alive'
        ));
    }
    
    /**
     * Send real-time notification via AJAX
     */
    public function send_realtime_notification() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $notification_id = intval($_POST['notification_id'] ?? 0);
        $user_ids = array_map('intval', $_POST['user_ids'] ?? array());
        
        if ($notification_id) {
            $this->broadcast_notification($notification_id, $user_ids);
            wp_send_json_success('Notification broadcasted');
        } else {
            wp_send_json_error('Invalid notification ID');
        }
    }
    
    /**
     * Get active connections count
     */
    public function get_active_connections_count() {
        global $wpdb;
        
        $connections_table = $wpdb->prefix . 'en_realtime_connections';
        
        return $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$connections_table}
            WHERE last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ");
    }
    
    /**
     * Get user's active connections
     */
    public function get_user_connections($user_id) {
        global $wpdb;
        
        $connections_table = $wpdb->prefix . 'en_realtime_connections';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$connections_table}
            WHERE user_id = %d
            AND last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ORDER BY connected_at DESC
        ", $user_id));
    }
    
    /**
     * Clean up old connections
     */
    public function cleanup_connections() {
        global $wpdb;
        
        $connections_table = $wpdb->prefix . 'en_realtime_connections';
        $queue_table = $wpdb->prefix . 'en_realtime_queue';
        
        // Remove connections inactive for more than 1 hour
        $wpdb->query("
            DELETE FROM {$connections_table}
            WHERE last_activity < DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        
        // Clean up old queue items
        $wpdb->query("
            DELETE FROM {$queue_table}
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)
        ");
    }
    
    /**
     * Create connections table
     */
    private function create_connections_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'en_realtime_connections';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            connection_id varchar(100) NOT NULL,
            user_id bigint(20) NOT NULL,
            connection_type varchar(20) DEFAULT 'sse',
            ip_address varchar(45),
            user_agent text,
            connected_at datetime NOT NULL,
            last_activity datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY connection_id (connection_id),
            KEY user_id (user_id),
            KEY last_activity (last_activity)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create queue table
     */
    private function create_queue_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'en_realtime_queue';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            connection_id varchar(100) NOT NULL,
            user_id bigint(20) NOT NULL,
            type varchar(20) NOT NULL,
            item_id bigint(20) NOT NULL,
            data longtext,
            delivered tinyint(1) DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY connection_id (connection_id),
            KEY user_id (user_id),
            KEY type_item (type, item_id),
            KEY created_at (created_at)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Initialize WebSocket server (if Ratchet is available)
     */
    public function init_websocket_server() {
        // This would require additional WebSocket server setup
        // For now, we're focusing on SSE implementation
        // WebSocket implementation would go here if needed
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
