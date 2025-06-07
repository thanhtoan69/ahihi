<?php

/**
 * In-App Messaging System for Environmental Platform
 * Handles direct messages between users and system messages
 */
class EN_Messaging_System {

    private $messages_table;

    public function __construct() {
        global $wpdb;
        $this->messages_table = $wpdb->prefix . 'en_messages';
    }

    /**
     * Send a message
     */
    public function send_message($args) {
        global $wpdb;

        $defaults = array(
            'sender_id' => get_current_user_id(),
            'recipient_id' => 0,
            'subject' => '',
            'message' => '',
            'attachments' => array(),
            'conversation_id' => null
        );

        $args = wp_parse_args($args, $defaults);

        // Validate required fields
        if (empty($args['recipient_id']) || empty($args['message'])) {
            return new WP_Error('missing_required_fields', __('Recipient and message are required.', 'environmental-notifications'));
        }

        // Check if users can message each other
        if (!$this->can_send_message($args['sender_id'], $args['recipient_id'])) {
            return new WP_Error('permission_denied', __('You do not have permission to send messages to this user.', 'environmental-notifications'));
        }

        // Generate conversation ID if not provided
        if (empty($args['conversation_id'])) {
            $args['conversation_id'] = $this->generate_conversation_id($args['sender_id'], $args['recipient_id']);
        }

        // Prepare message data
        $message_data = array(
            'sender_id' => intval($args['sender_id']),
            'recipient_id' => intval($args['recipient_id']),
            'conversation_id' => sanitize_text_field($args['conversation_id']),
            'subject' => sanitize_text_field($args['subject']),
            'message' => wp_kses_post($args['message']),
            'attachments' => json_encode($args['attachments']),
            'created_at' => current_time('mysql')
        );

        $result = $wpdb->insert($this->messages_table, $message_data);

        if ($result === false) {
            return new WP_Error('db_insert_error', __('Failed to send message.', 'environmental-notifications'));
        }

        $message_id = $wpdb->insert_id;

        // Send notification to recipient
        $this->send_message_notification($message_id);

        // Track analytics
        $this->track_message_analytics($message_id, 'sent');

        return array(
            'success' => true,
            'message_id' => $message_id,
            'conversation_id' => $args['conversation_id']
        );
    }

    /**
     * Get user messages
     */
    public function get_user_messages($user_id, $args = array()) {
        global $wpdb;

        $defaults = array(
            'conversation_id' => '',
            'limit' => 20,
            'offset' => 0,
            'type' => 'all', // 'sent', 'received', 'all'
            'is_read' => '',
            'order_by' => 'created_at',
            'order' => 'DESC'
        );

        $args = wp_parse_args($args, $defaults);

        $where_conditions = array();
        $where_values = array();

        // Filter by conversation
        if (!empty($args['conversation_id'])) {
            $where_conditions[] = 'conversation_id = %s';
            $where_values[] = $args['conversation_id'];
        }

        // Filter by message type
        switch ($args['type']) {
            case 'sent':
                $where_conditions[] = 'sender_id = %d AND is_deleted_by_sender = 0';
                $where_values[] = $user_id;
                break;
            case 'received':
                $where_conditions[] = 'recipient_id = %d AND is_deleted_by_recipient = 0';
                $where_values[] = $user_id;
                break;
            default:
                $where_conditions[] = '((sender_id = %d AND is_deleted_by_sender = 0) OR (recipient_id = %d AND is_deleted_by_recipient = 0))';
                $where_values[] = $user_id;
                $where_values[] = $user_id;
        }

        // Filter by read status
        if ($args['is_read'] !== '') {
            $where_conditions[] = 'is_read = %d';
            $where_values[] = intval($args['is_read']);
        }

        $where_clause = implode(' AND ', $where_conditions);
        $order_by = sanitize_sql_orderby($args['order_by'] . ' ' . $args['order']);

        $query = $wpdb->prepare("
            SELECT m.*, 
                   u1.display_name as sender_name,
                   u1.user_email as sender_email,
                   u2.display_name as recipient_name,
                   u2.user_email as recipient_email
            FROM {$this->messages_table} m
            LEFT JOIN {$wpdb->users} u1 ON m.sender_id = u1.ID
            LEFT JOIN {$wpdb->users} u2 ON m.recipient_id = u2.ID
            WHERE {$where_clause}
            ORDER BY {$order_by}
            LIMIT %d OFFSET %d
        ", array_merge($where_values, array($args['limit'], $args['offset'])));

        $messages = $wpdb->get_results($query);

        // Parse attachments
        foreach ($messages as $message) {
            $message->attachments = json_decode($message->attachments, true) ?: array();
        }

        return $messages;
    }

    /**
     * Get conversations for user
     */
    public function get_user_conversations($user_id, $limit = 20) {
        global $wpdb;

        $query = $wpdb->prepare("
            SELECT 
                conversation_id,
                MAX(created_at) as last_message_date,
                COUNT(*) as message_count,
                SUM(CASE WHEN recipient_id = %d AND is_read = 0 THEN 1 ELSE 0 END) as unread_count,
                (SELECT subject FROM {$this->messages_table} m2 
                 WHERE m2.conversation_id = m1.conversation_id 
                 ORDER BY created_at DESC LIMIT 1) as last_subject,
                (SELECT message FROM {$this->messages_table} m2 
                 WHERE m2.conversation_id = m1.conversation_id 
                 ORDER BY created_at DESC LIMIT 1) as last_message,
                (SELECT sender_id FROM {$this->messages_table} m2 
                 WHERE m2.conversation_id = m1.conversation_id 
                 ORDER BY created_at DESC LIMIT 1) as last_sender_id,
                CASE 
                    WHEN sender_id = %d THEN recipient_id 
                    ELSE sender_id 
                END as other_user_id
            FROM {$this->messages_table} m1
            WHERE (sender_id = %d AND is_deleted_by_sender = 0) 
               OR (recipient_id = %d AND is_deleted_by_recipient = 0)
            GROUP BY conversation_id
            ORDER BY last_message_date DESC
            LIMIT %d
        ", $user_id, $user_id, $user_id, $user_id, $limit);

        $conversations = $wpdb->get_results($query);

        // Get other user details
        foreach ($conversations as $conversation) {
            $other_user = get_user_by('id', $conversation->other_user_id);
            if ($other_user) {
                $conversation->other_user_name = $other_user->display_name;
                $conversation->other_user_email = $other_user->user_email;
                $conversation->other_user_avatar = get_avatar_url($other_user->ID, array('size' => 32));
            }
        }

        return $conversations;
    }

    /**
     * Mark message as read
     */
    public function mark_as_read($message_id, $user_id) {
        global $wpdb;

        $result = $wpdb->update(
            $this->messages_table,
            array(
                'is_read' => 1,
                'read_at' => current_time('mysql')
            ),
            array(
                'id' => $message_id,
                'recipient_id' => $user_id
            )
        );

        if ($result !== false) {
            $this->track_message_analytics($message_id, 'read');
        }

        return $result !== false;
    }

    /**
     * Mark conversation as read
     */
    public function mark_conversation_as_read($conversation_id, $user_id) {
        global $wpdb;

        return $wpdb->update(
            $this->messages_table,
            array(
                'is_read' => 1,
                'read_at' => current_time('mysql')
            ),
            array(
                'conversation_id' => $conversation_id,
                'recipient_id' => $user_id,
                'is_read' => 0
            )
        );
    }

    /**
     * Delete message
     */
    public function delete_message($message_id, $user_id) {
        global $wpdb;

        $message = $this->get_message($message_id);
        if (!$message) {
            return false;
        }

        // Soft delete based on user role in conversation
        if ($message->sender_id == $user_id) {
            $update_field = 'is_deleted_by_sender';
        } elseif ($message->recipient_id == $user_id) {
            $update_field = 'is_deleted_by_recipient';
        } else {
            return false; // User not part of conversation
        }

        $result = $wpdb->update(
            $this->messages_table,
            array($update_field => 1),
            array('id' => $message_id)
        );

        // If both users deleted, permanently delete
        $updated_message = $this->get_message($message_id);
        if ($updated_message && $updated_message->is_deleted_by_sender && $updated_message->is_deleted_by_recipient) {
            $wpdb->delete($this->messages_table, array('id' => $message_id));
        }

        return $result !== false;
    }

    /**
     * Delete conversation
     */
    public function delete_conversation($conversation_id, $user_id) {
        global $wpdb;

        // Get all messages in conversation
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT id, sender_id, recipient_id FROM {$this->messages_table} WHERE conversation_id = %s",
            $conversation_id
        ));

        foreach ($messages as $message) {
            $this->delete_message($message->id, $user_id);
        }

        return true;
    }

    /**
     * Get message by ID
     */
    public function get_message($message_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->messages_table} WHERE id = %d",
            $message_id
        ));
    }

    /**
     * Get unread message count
     */
    public function get_unread_count($user_id) {
        global $wpdb;

        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->messages_table} 
             WHERE recipient_id = %d AND is_read = 0 AND is_deleted_by_recipient = 0",
            $user_id
        )));
    }

    /**
     * Search messages
     */
    public function search_messages($user_id, $search_term, $limit = 20) {
        global $wpdb;

        $search_term = '%' . $wpdb->esc_like($search_term) . '%';

        return $wpdb->get_results($wpdb->prepare("
            SELECT m.*, 
                   u1.display_name as sender_name,
                   u2.display_name as recipient_name
            FROM {$this->messages_table} m
            LEFT JOIN {$wpdb->users} u1 ON m.sender_id = u1.ID
            LEFT JOIN {$wpdb->users} u2 ON m.recipient_id = u2.ID
            WHERE ((sender_id = %d AND is_deleted_by_sender = 0) 
                OR (recipient_id = %d AND is_deleted_by_recipient = 0))
            AND (subject LIKE %s OR message LIKE %s)
            ORDER BY created_at DESC
            LIMIT %d
        ", $user_id, $user_id, $search_term, $search_term, $limit));
    }

    /**
     * Generate conversation ID
     */
    private function generate_conversation_id($user1_id, $user2_id) {
        $ids = array($user1_id, $user2_id);
        sort($ids);
        return 'conv_' . implode('_', $ids) . '_' . time();
    }

    /**
     * Check if user can send message
     */
    private function can_send_message($sender_id, $recipient_id) {
        // Check if users exist
        if (!get_user_by('id', $sender_id) || !get_user_by('id', $recipient_id)) {
            return false;
        }

        // Check rate limiting
        if (!$this->check_rate_limit($sender_id)) {
            return false;
        }

        // Check if recipient has blocked sender
        if ($this->is_user_blocked($sender_id, $recipient_id)) {
            return false;
        }

        // Check messaging permissions
        return apply_filters('en_can_send_message', true, $sender_id, $recipient_id);
    }

    /**
     * Check rate limiting
     */
    private function check_rate_limit($user_id) {
        global $wpdb;

        $rate_limit = get_option('en_message_rate_limit', 50); // Messages per hour
        $time_window = 3600; // 1 hour in seconds

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->messages_table} 
             WHERE sender_id = %d AND created_at > DATE_SUB(NOW(), INTERVAL %d SECOND)",
            $user_id,
            $time_window
        ));

        return intval($count) < $rate_limit;
    }

    /**
     * Check if user is blocked
     */
    private function is_user_blocked($sender_id, $recipient_id) {
        // This would integrate with a user blocking system
        // For now, return false (no blocking)
        return apply_filters('en_is_user_blocked', false, $sender_id, $recipient_id);
    }

    /**
     * Send message notification
     */
    private function send_message_notification($message_id) {
        $message = $this->get_message($message_id);
        if (!$message) {
            return false;
        }

        $notification_engine = new EN_Notification_Engine();
        
        return $notification_engine->create_notification(array(
            'user_id' => $message->recipient_id,
            'type' => 'new_message',
            'title' => __('New Message', 'environmental-notifications'),
            'message' => sprintf(
                __('You have a new message from %s', 'environmental-notifications'),
                get_user_by('id', $message->sender_id)->display_name
            ),
            'data' => array(
                'message_id' => $message_id,
                'conversation_id' => $message->conversation_id,
                'sender_id' => $message->sender_id
            ),
            'priority' => 'normal'
        ));
    }

    /**
     * Track message analytics
     */
    private function track_message_analytics($message_id, $event_type) {
        $analytics = new EN_Notification_Analytics();
        return $analytics->track_event(array(
            'event_type' => $event_type,
            'object_type' => 'message',
            'object_id' => $message_id,
            'additional_data' => array(
                'timestamp' => current_time('timestamp')
            )
        ));
    }

    /**
     * Get message statistics
     */
    public function get_message_stats($user_id = null, $days = 30) {
        global $wpdb;

        $where_clause = '';
        $where_values = array();

        if ($user_id) {
            $where_clause = 'WHERE (sender_id = %d OR recipient_id = %d)';
            $where_values = array($user_id, $user_id);
        }

        $date_condition = $where_clause ? 'AND' : 'WHERE';
        $where_clause .= " {$date_condition} created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)";
        $where_values[] = $days;

        $query = $wpdb->prepare("
            SELECT 
                COUNT(*) as total_messages,
                COUNT(DISTINCT conversation_id) as total_conversations,
                COUNT(DISTINCT sender_id) as unique_senders,
                COUNT(DISTINCT recipient_id) as unique_recipients,
                SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as read_messages,
                AVG(CASE WHEN read_at IS NOT NULL 
                    THEN TIMESTAMPDIFF(MINUTE, created_at, read_at) ELSE NULL END) as avg_read_time_minutes
            FROM {$this->messages_table} 
            {$where_clause}
        ", $where_values);

        return $wpdb->get_row($query);
    }

    /**
     * Cleanup old messages
     */
    public function cleanup_old_messages() {
        global $wpdb;

        $retention_days = intval(get_option('en_message_retention_days', 365));
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));

        // Delete messages that are deleted by both parties and older than retention period
        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->messages_table} 
             WHERE created_at < %s 
             AND is_deleted_by_sender = 1 
             AND is_deleted_by_recipient = 1",
            $cutoff_date
        ));
    }

    /**
     * Export user messages
     */
    public function export_user_messages($user_id, $format = 'json') {
        $messages = $this->get_user_messages($user_id, array('limit' => 999999));
        
        switch ($format) {
            case 'csv':
                return $this->export_to_csv($messages);
            case 'json':
            default:
                return json_encode($messages, JSON_PRETTY_PRINT);
        }
    }

    /**
     * Export messages to CSV
     */
    private function export_to_csv($messages) {
        $output = fopen('php://temp', 'r+');
        
        // Add headers
        fputcsv($output, array(
            'ID', 'Conversation ID', 'Sender', 'Recipient', 'Subject', 
            'Message', 'Is Read', 'Created At', 'Read At'
        ));
        
        foreach ($messages as $message) {
            fputcsv($output, array(
                $message->id,
                $message->conversation_id,
                $message->sender_name,
                $message->recipient_name,
                $message->subject,
                strip_tags($message->message),
                $message->is_read ? 'Yes' : 'No',
                $message->created_at,
                $message->read_at
            ));
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}
