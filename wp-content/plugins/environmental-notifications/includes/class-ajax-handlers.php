<?php
/**
 * AJAX Handlers Class
 * 
 * Handles all AJAX requests for the notification and messaging system
 * including real-time interactions, user preferences, and messaging operations.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_AJAX_Handlers {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->register_ajax_actions();
    }
    
    /**
     * Register AJAX actions
     */
    private function register_ajax_actions() {
        // Notification actions
        add_action('wp_ajax_en_mark_notification_read', array($this, 'mark_notification_read'));
        add_action('wp_ajax_en_mark_all_notifications_read', array($this, 'mark_all_notifications_read'));
        add_action('wp_ajax_en_delete_notification', array($this, 'delete_notification'));
        add_action('wp_ajax_en_get_notifications', array($this, 'get_notifications'));
        add_action('wp_ajax_en_get_notification_count', array($this, 'get_notification_count'));
        
        // Messaging actions
        add_action('wp_ajax_en_send_message', array($this, 'send_message'));
        add_action('wp_ajax_en_get_messages', array($this, 'get_messages'));
        add_action('wp_ajax_en_get_conversations', array($this, 'get_conversations'));
        add_action('wp_ajax_en_mark_message_read', array($this, 'mark_message_read'));
        add_action('wp_ajax_en_delete_message', array($this, 'delete_message'));
        add_action('wp_ajax_en_search_messages', array($this, 'search_messages'));
        
        // Push notification actions
        add_action('wp_ajax_en_subscribe_push', array($this, 'subscribe_push_notifications'));
        add_action('wp_ajax_en_unsubscribe_push', array($this, 'unsubscribe_push_notifications'));
        add_action('wp_ajax_en_get_push_status', array($this, 'get_push_notification_status'));
        
        // Email preference actions
        add_action('wp_ajax_en_update_email_preferences', array($this, 'update_email_preferences'));
        add_action('wp_ajax_en_get_email_preferences', array($this, 'get_email_preferences'));
        
        // User search for messaging
        add_action('wp_ajax_en_search_users', array($this, 'search_users'));
        
        // File upload for messages
        add_action('wp_ajax_en_upload_message_attachment', array($this, 'upload_message_attachment'));
    }
    
    /**
     * Mark notification as read
     */
    public function mark_notification_read() {
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to perform this action.', 'environmental-notifications'));
        }
        
        $notification_id = intval($_POST['notification_id'] ?? 0);
        $user_id = get_current_user_id();
        
        if (!$notification_id) {
            wp_send_json_error(__('Invalid notification ID.', 'environmental-notifications'));
        }
        
        $engine = Environmental_Notification_Engine::get_instance();
        $result = $engine->mark_notification_read($notification_id, $user_id);
        
        if ($result) {
            // Track analytics
            $analytics = Environmental_Notification_Analytics::get_instance();
            $analytics->track_event($notification_id, $user_id, 'read');
            
            wp_send_json_success(__('Notification marked as read.', 'environmental-notifications'));
        } else {
            wp_send_json_error(__('Failed to mark notification as read.', 'environmental-notifications'));
        }
    }
    
    /**
     * Mark all notifications as read
     */
    public function mark_all_notifications_read() {
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to perform this action.', 'environmental-notifications'));
        }
        
        $user_id = get_current_user_id();
        $engine = Environmental_Notification_Engine::get_instance();
        
        $result = $engine->mark_all_notifications_read($user_id);
        
        if ($result) {
            wp_send_json_success(__('All notifications marked as read.', 'environmental-notifications'));
        } else {
            wp_send_json_error(__('Failed to mark notifications as read.', 'environmental-notifications'));
        }
    }
    
    /**
     * Delete notification
     */
    public function delete_notification() {
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to perform this action.', 'environmental-notifications'));
        }
        
        $notification_id = intval($_POST['notification_id'] ?? 0);
        $user_id = get_current_user_id();
        
        if (!$notification_id) {
            wp_send_json_error(__('Invalid notification ID.', 'environmental-notifications'));
        }
        
        $engine = Environmental_Notification_Engine::get_instance();
        $result = $engine->delete_user_notification($notification_id, $user_id);
        
        if ($result) {
            wp_send_json_success(__('Notification deleted.', 'environmental-notifications'));
        } else {
            wp_send_json_error(__('Failed to delete notification.', 'environmental-notifications'));
        }
    }
    
    /**
     * Get user notifications
     */
    public function get_notifications() {
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to perform this action.', 'environmental-notifications'));
        }
        
        $user_id = get_current_user_id();
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 20);
        $type = sanitize_text_field($_POST['type'] ?? '');
        $unread_only = isset($_POST['unread_only']) ? (bool) $_POST['unread_only'] : false;
        
        $engine = Environmental_Notification_Engine::get_instance();
        $notifications = $engine->get_user_notifications($user_id, $page, $per_page, array(
            'type' => $type,
            'unread_only' => $unread_only
        ));
        
        // Format notifications for frontend
        $formatted_notifications = array();
        foreach ($notifications as $notification) {
            $formatted_notifications[] = array(
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'message' => $notification->message,
                'priority' => $notification->priority,
                'data' => json_decode($notification->data, true),
                'action_url' => $notification->action_url,
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at,
                'human_time' => human_time_diff(strtotime($notification->created_at)) . ' ' . __('ago', 'environmental-notifications'),
                'is_read' => !is_null($notification->read_at)
            );
        }
        
        wp_send_json_success(array(
            'notifications' => $formatted_notifications,
            'has_more' => count($notifications) === $per_page
        ));
    }
    
    /**
     * Get notification count
     */
    public function get_notification_count() {
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to perform this action.', 'environmental-notifications'));
        }
        
        $user_id = get_current_user_id();
        $engine = Environmental_Notification_Engine::get_instance();
        
        $total_count = $engine->get_user_notification_count($user_id);
        $unread_count = $engine->get_user_notification_count($user_id, true);
        
        wp_send_json_success(array(
            'total' => $total_count,
            'unread' => $unread_count
        ));
    }
    
    /**
     * Send message
     */
    public function send_message() {
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to send messages.', 'environmental-notifications'));
        }
        
        $sender_id = get_current_user_id();
        $recipient_id = intval($_POST['recipient_id'] ?? 0);
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $conversation_id = sanitize_text_field($_POST['conversation_id'] ?? '');
        $attachments = $_POST['attachments'] ?? array();
        
        if (!$recipient_id || empty($message)) {
            wp_send_json_error(__('Recipient and message are required.', 'environmental-notifications'));
        }
        
        // Validate recipient exists
        $recipient = get_user_by('id', $recipient_id);
        if (!$recipient) {
            wp_send_json_error(__('Invalid recipient.', 'environmental-notifications'));
        }
        
        $messaging = Environmental_Messaging_System::get_instance();
        
        // Check rate limiting
        if (!$messaging->check_rate_limit($sender_id)) {
            wp_send_json_error(__('You are sending messages too quickly. Please wait a moment.', 'environmental-notifications'));
        }
        
        $result = $messaging->send_message($sender_id, $recipient_id, $message, $conversation_id, $attachments);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Message sent successfully.', 'environmental-notifications'),
                'message_id' => $result
            ));
        } else {
            wp_send_json_error(__('Failed to send message.', 'environmental-notifications'));
        }
    }
    
    /**
     * Get messages
     */
    public function get_messages() {
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to view messages.', 'environmental-notifications'));
        }
        
        $user_id = get_current_user_id();
        $conversation_id = sanitize_text_field($_POST['conversation_id'] ?? '');
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 50);
        
        if (!$conversation_id) {
            wp_send_json_error(__('Conversation ID is required.', 'environmental-notifications'));
        }
        
        $messaging = Environmental_Messaging_System::get_instance();
        $messages = $messaging->get_conversation_messages($conversation_id, $user_id, $page, $per_page);
        
        // Format messages for frontend
        $formatted_messages = array();
        foreach ($messages as $message) {
            $sender = get_user_by('id', $message->sender_id);
            
            $formatted_messages[] = array(
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'sender_id' => $message->sender_id,
                'sender_name' => $sender ? $sender->display_name : __('Unknown User', 'environmental-notifications'),
                'sender_avatar' => get_avatar_url($message->sender_id),
                'message' => $message->message,
                'attachments' => json_decode($message->attachments, true),
                'created_at' => $message->created_at,
                'read_at' => $message->read_at,
                'human_time' => human_time_diff(strtotime($message->created_at)) . ' ' . __('ago', 'environmental-notifications'),
                'is_own' => $message->sender_id == $user_id,
                'is_read' => !is_null($message->read_at)
            );
        }
        
        wp_send_json_success(array(
            'messages' => $formatted_messages,
            'has_more' => count($messages) === $per_page
        ));
    }
    
    /**
     * Get user conversations
     */
    public function get_conversations() {
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to view conversations.', 'environmental-notifications'));
        }
        
        $user_id = get_current_user_id();
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 20);
        
        $messaging = Environmental_Messaging_System::get_instance();
        $conversations = $messaging->get_user_conversations($user_id, $page, $per_page);
        
        wp_send_json_success(array(
            'conversations' => $conversations,
            'has_more' => count($conversations) === $per_page
        ));
    }
    
    /**
     * Mark message as read
     */
    public function mark_message_read() {
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to perform this action.', 'environmental-notifications'));
        }
        
        $message_id = intval($_POST['message_id'] ?? 0);
        $user_id = get_current_user_id();
        
        if (!$message_id) {
            wp_send_json_error(__('Invalid message ID.', 'environmental-notifications'));
        }
        
        $messaging = Environmental_Messaging_System::get_instance();
        $result = $messaging->mark_message_read($message_id, $user_id);
        
        if ($result) {
            wp_send_json_success(__('Message marked as read.', 'environmental-notifications'));
        } else {
            wp_send_json_error(__('Failed to mark message as read.', 'environmental-notifications'));
        }
    }
    
    /**
     * Delete message
     */
    public function delete_message() {
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to perform this action.', 'environmental-notifications'));
        }
        
        $message_id = intval($_POST['message_id'] ?? 0);
        $user_id = get_current_user_id();
        
        if (!$message_id) {
            wp_send_json_error(__('Invalid message ID.', 'environmental-notifications'));
        }
        
        $messaging = Environmental_Messaging_System::get_instance();
        $result = $messaging->delete_message($message_id, $user_id);
        
        if ($result) {
            wp_send_json_success(__('Message deleted.', 'environmental-notifications'));
        } else {
            wp_send_json_error(__('Failed to delete message.', 'environmental-notifications'));
        }
    }
    
    /**
     * Search messages
     */
    public function search_messages() {
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to search messages.', 'environmental-notifications'));
        }
        
        $user_id = get_current_user_id();
        $query = sanitize_text_field($_POST['query'] ?? '');
        $conversation_id = sanitize_text_field($_POST['conversation_id'] ?? '');
        
        if (empty($query)) {
            wp_send_json_error(__('Search query is required.', 'environmental-notifications'));
        }
        
        $messaging = Environmental_Messaging_System::get_instance();
        $results = $messaging->search_messages($user_id, $query, $conversation_id);
        
        wp_send_json_success(array(
            'results' => $results,
            'count' => count($results)
        ));
    }
    
    /**
     * Subscribe to push notifications
     */
    public function subscribe_push_notifications() {
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to subscribe to notifications.', 'environmental-notifications'));
        }
        
        $user_id = get_current_user_id();
        $subscription_data = $_POST['subscription'] ?? array();
        
        if (empty($subscription_data)) {
            wp_send_json_error(__('Subscription data is required.', 'environmental-notifications'));
        }
        
        $push_notifications = Environmental_Push_Notifications::get_instance();
        $result = $push_notifications->save_subscription($user_id, $subscription_data);
        
        if ($result) {
            wp_send_json_success(__('Successfully subscribed to push notifications.', 'environmental-notifications'));
        } else {
            wp_send_json_error(__('Failed to subscribe to push notifications.', 'environmental-notifications'));
        }
    }
    
    /**
     * Unsubscribe from push notifications
     */
    public function unsubscribe_push_notifications() {
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to unsubscribe from notifications.', 'environmental-notifications'));
        }
        
        $user_id = get_current_user_id();
        $endpoint = sanitize_url($_POST['endpoint'] ?? '');
        
        if (empty($endpoint)) {
            wp_send_json_error(__('Endpoint is required.', 'environmental-notifications'));
        }
        
        $push_notifications = Environmental_Push_Notifications::get_instance();
        $result = $push_notifications->remove_subscription($user_id, $endpoint);
        
        if ($result) {
            wp_send_json_success(__('Successfully unsubscribed from push notifications.', 'environmental-notifications'));
        } else {
            wp_send_json_error(__('Failed to unsubscribe from push notifications.', 'environmental-notifications'));
        }
    }
    
    /**
     * Get push notification status
     */
    public function get_push_notification_status() {
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to check notification status.', 'environmental-notifications'));
        }
        
        $user_id = get_current_user_id();
        $push_notifications = Environmental_Push_Notifications::get_instance();
        
        $subscriptions = $push_notifications->get_user_subscriptions($user_id);
        $vapid_key = $push_notifications->get_vapid_public_key();
        
        wp_send_json_success(array(
            'is_subscribed' => !empty($subscriptions),
            'subscription_count' => count($subscriptions),
            'vapid_public_key' => $vapid_key
        ));
    }
    
    /**
     * Update email preferences
     */
    public function update_email_preferences() {
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to update preferences.', 'environmental-notifications'));
        }
        
        $user_id = get_current_user_id();
        $preferences = $_POST['preferences'] ?? array();
        
        if (empty($preferences)) {
            wp_send_json_error(__('Preferences data is required.', 'environmental-notifications'));
        }
        
        $email_preferences = Environmental_Email_Preferences::get_instance();
        $result = $email_preferences->update_user_preferences($user_id, $preferences);
        
        if ($result) {
            wp_send_json_success(__('Email preferences updated successfully.', 'environmental-notifications'));
        } else {
            wp_send_json_error(__('Failed to update email preferences.', 'environmental-notifications'));
        }
    }
    
    /**
     * Get email preferences
     */
    public function get_email_preferences() {
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to view preferences.', 'environmental-notifications'));
        }
        
        $user_id = get_current_user_id();
        $email_preferences = Environmental_Email_Preferences::get_instance();
        
        $preferences = $email_preferences->get_user_preferences($user_id);
        
        wp_send_json_success(array(
            'preferences' => $preferences
        ));
    }
    
    /**
     * Search users for messaging
     */
    public function search_users() {
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to search users.', 'environmental-notifications'));
        }
        
        $query = sanitize_text_field($_POST['query'] ?? '');
        $exclude_current = isset($_POST['exclude_current']) ? (bool) $_POST['exclude_current'] : true;
        
        if (empty($query) || strlen($query) < 2) {
            wp_send_json_error(__('Search query must be at least 2 characters.', 'environmental-notifications'));
        }
        
        $args = array(
            'search' => '*' . esc_attr($query) . '*',
            'search_columns' => array('user_login', 'user_nicename', 'display_name'),
            'number' => 10,
            'fields' => array('ID', 'display_name', 'user_login')
        );
        
        if ($exclude_current) {
            $args['exclude'] = array(get_current_user_id());
        }
        
        $users = get_users($args);
        
        $formatted_users = array();
        foreach ($users as $user) {
            $formatted_users[] = array(
                'id' => $user->ID,
                'name' => $user->display_name,
                'username' => $user->user_login,
                'avatar' => get_avatar_url($user->ID, array('size' => 32))
            );
        }
        
        wp_send_json_success(array(
            'users' => $formatted_users,
            'count' => count($formatted_users)
        ));
    }
    
    /**
     * Upload message attachment
     */
    public function upload_message_attachment() {
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to upload attachments.', 'environmental-notifications'));
        }
        
        if (!isset($_FILES['attachment'])) {
            wp_send_json_error(__('No file uploaded.', 'environmental-notifications'));
        }
        
        // Check file type and size
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt');
        $max_size = 5 * 1024 * 1024; // 5MB
        
        $file = $_FILES['attachment'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            wp_send_json_error(__('File type not allowed.', 'environmental-notifications'));
        }
        
        if ($file['size'] > $max_size) {
            wp_send_json_error(__('File size too large. Maximum 5MB allowed.', 'environmental-notifications'));
        }
        
        // Handle the upload
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $attachment_id = media_handle_upload('attachment', 0);
        
        if (is_wp_error($attachment_id)) {
            wp_send_json_error($attachment_id->get_error_message());
        }
        
        $attachment_url = wp_get_attachment_url($attachment_id);
        $attachment_meta = wp_get_attachment_metadata($attachment_id);
        
        wp_send_json_success(array(
            'attachment_id' => $attachment_id,
            'url' => $attachment_url,
            'filename' => get_the_title($attachment_id),
            'filesize' => size_format($file['size']),
            'type' => $file_extension
        ));
    }
}
