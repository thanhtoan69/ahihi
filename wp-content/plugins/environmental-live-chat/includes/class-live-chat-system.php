<?php
/**
 * Live Chat System Class
 * 
 * Handles real-time chat functionality, session management, and operator assignment
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Live_Chat_System {
    
    private static $instance = null;
    private $table_sessions;
    private $table_messages;
    private $table_operators;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->table_sessions = $wpdb->prefix . 'elc_chat_sessions';
        $this->table_messages = $wpdb->prefix . 'elc_chat_messages';
        $this->table_operators = $wpdb->prefix . 'elc_chat_operators';
        
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('wp_ajax_elc_start_chat', array($this, 'start_chat_session'));
        add_action('wp_ajax_nopriv_elc_start_chat', array($this, 'start_chat_session'));
        
        add_action('wp_ajax_elc_send_message', array($this, 'send_message'));
        add_action('wp_ajax_nopriv_elc_send_message', array($this, 'send_message'));
        
        add_action('wp_ajax_elc_get_messages', array($this, 'get_messages'));
        add_action('wp_ajax_nopriv_elc_get_messages', array($this, 'get_messages'));
        
        add_action('wp_ajax_elc_end_chat', array($this, 'end_chat_session'));
        add_action('wp_ajax_nopriv_elc_end_chat', array($this, 'end_chat_session'));
        
        add_action('wp_ajax_elc_operator_join', array($this, 'operator_join_chat'));
        add_action('wp_ajax_elc_operator_status', array($this, 'update_operator_status'));
        
        add_action('wp_ajax_elc_upload_file', array($this, 'handle_file_upload'));
        add_action('wp_ajax_nopriv_elc_upload_file', array($this, 'handle_file_upload'));
    }
    
    /**
     * Start a new chat session
     */
    public function start_chat_session() {
        check_ajax_referer('elc_chat_nonce', 'nonce');
        
        global $wpdb;
        
        $visitor_name = sanitize_text_field($_POST['visitor_name'] ?? '');
        $visitor_email = sanitize_email($_POST['visitor_email'] ?? '');
        $department = sanitize_text_field($_POST['department'] ?? 'general');
        $initial_message = sanitize_textarea_field($_POST['initial_message'] ?? '');
        
        // Check business hours
        if (!$this->is_business_hours()) {
            wp_send_json_error(array(
                'message' => __('Chat is currently unavailable. Please try again during business hours or submit a support ticket.', 'environmental-live-chat')
            ));
        }
        
        // Find available operator
        $operator = $this->find_available_operator($department);
        
        // Create chat session
        $session_data = array(
            'visitor_name' => $visitor_name,
            'visitor_email' => $visitor_email,
            'visitor_ip' => $this->get_visitor_ip(),
            'department' => $department,
            'operator_id' => $operator ? $operator->ID : null,
            'status' => $operator ? 'active' : 'waiting',
            'created_at' => current_time('mysql'),
            'last_activity' => current_time('mysql')
        );
        
        $session_id = $wpdb->insert($this->table_sessions, $session_data);
        
        if ($session_id) {
            $session_id = $wpdb->insert_id;
            
            // Send initial message if provided
            if (!empty($initial_message)) {
                $this->save_message($session_id, 'visitor', $initial_message, $visitor_name);
            }
            
            // Send welcome message
            $welcome_message = $operator 
                ? sprintf(__('Hello %s! An operator will be with you shortly.', 'environmental-live-chat'), $visitor_name)
                : __('Thank you for contacting us. All operators are currently busy. Please wait or submit a support ticket.', 'environmental-live-chat');
            
            $this->save_message($session_id, 'system', $welcome_message);
            
            wp_send_json_success(array(
                'session_id' => $session_id,
                'status' => $operator ? 'connected' : 'waiting',
                'operator_name' => $operator ? $operator->display_name : null,
                'welcome_message' => $welcome_message
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to start chat session. Please try again.', 'environmental-live-chat')
            ));
        }
    }
    
    /**
     * Send a chat message
     */
    public function send_message() {
        check_ajax_referer('elc_chat_nonce', 'nonce');
        
        $session_id = intval($_POST['session_id'] ?? 0);
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $sender_type = sanitize_text_field($_POST['sender_type'] ?? 'visitor');
        $sender_name = sanitize_text_field($_POST['sender_name'] ?? '');
        
        if (!$session_id || empty($message)) {
            wp_send_json_error(array('message' => __('Invalid message data.', 'environmental-live-chat')));
        }
        
        // Verify session exists and is active
        $session = $this->get_session($session_id);
        if (!$session || $session->status === 'ended') {
            wp_send_json_error(array('message' => __('Chat session not found or ended.', 'environmental-live-chat')));
        }
        
        // Save message
        $message_id = $this->save_message($session_id, $sender_type, $message, $sender_name);
        
        if ($message_id) {
            // Update session activity
            $this->update_session_activity($session_id);
            
            // Trigger real-time notification
            do_action('elc_new_message', $session_id, $message_id, $sender_type);
            
            wp_send_json_success(array(
                'message_id' => $message_id,
                'timestamp' => current_time('mysql')
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to send message.', 'environmental-live-chat')));
        }
    }
    
    /**
     * Get chat messages for a session
     */
    public function get_messages() {
        check_ajax_referer('elc_chat_nonce', 'nonce');
        
        $session_id = intval($_POST['session_id'] ?? 0);
        $last_message_id = intval($_POST['last_message_id'] ?? 0);
        
        if (!$session_id) {
            wp_send_json_error(array('message' => __('Invalid session ID.', 'environmental-live-chat')));
        }
        
        $messages = $this->get_session_messages($session_id, $last_message_id);
        
        wp_send_json_success(array(
            'messages' => $messages,
            'session_status' => $this->get_session_status($session_id)
        ));
    }
    
    /**
     * End chat session
     */
    public function end_chat_session() {
        check_ajax_referer('elc_chat_nonce', 'nonce');
        
        global $wpdb;
        
        $session_id = intval($_POST['session_id'] ?? 0);
        $rating = intval($_POST['rating'] ?? 0);
        $feedback = sanitize_textarea_field($_POST['feedback'] ?? '');
        
        if (!$session_id) {
            wp_send_json_error(array('message' => __('Invalid session ID.', 'environmental-live-chat')));
        }
        
        // Update session status
        $update_data = array(
            'status' => 'ended',
            'ended_at' => current_time('mysql')
        );
        
        if ($rating > 0) {
            $update_data['rating'] = $rating;
        }
        
        if (!empty($feedback)) {
            $update_data['feedback'] = $feedback;
        }
        
        $updated = $wpdb->update(
            $this->table_sessions,
            $update_data,
            array('id' => $session_id),
            array('%s', '%s', '%d', '%s'),
            array('%d')
        );
        
        if ($updated !== false) {
            // Send goodbye message
            $this->save_message($session_id, 'system', __('Chat session ended. Thank you for contacting us!', 'environmental-live-chat'));
            
            wp_send_json_success(array('message' => __('Chat session ended successfully.', 'environmental-live-chat')));
        } else {
            wp_send_json_error(array('message' => __('Failed to end chat session.', 'environmental-live-chat')));
        }
    }
    
    /**
     * Operator joins chat session
     */
    public function operator_join_chat() {
        check_ajax_referer('elc_operator_nonce', 'nonce');
        
        global $wpdb;
        
        $session_id = intval($_POST['session_id'] ?? 0);
        $operator_id = get_current_user_id();
        
        if (!$session_id || !$operator_id) {
            wp_send_json_error(array('message' => __('Invalid session or operator.', 'environmental-live-chat')));
        }
        
        // Update session with operator
        $updated = $wpdb->update(
            $this->table_sessions,
            array(
                'operator_id' => $operator_id,
                'status' => 'active',
                'last_activity' => current_time('mysql')
            ),
            array('id' => $session_id),
            array('%d', '%s', '%s'),
            array('%d')
        );
        
        if ($updated !== false) {
            // Send operator joined message
            $operator = get_user_by('ID', $operator_id);
            $message = sprintf(__('%s has joined the chat.', 'environmental-live-chat'), $operator->display_name);
            $this->save_message($session_id, 'system', $message);
            
            wp_send_json_success(array('message' => __('Successfully joined chat.', 'environmental-live-chat')));
        } else {
            wp_send_json_error(array('message' => __('Failed to join chat.', 'environmental-live-chat')));
        }
    }
    
    /**
     * Update operator status
     */
    public function update_operator_status() {
        check_ajax_referer('elc_operator_nonce', 'nonce');
        
        global $wpdb;
        
        $status = sanitize_text_field($_POST['status'] ?? 'offline');
        $operator_id = get_current_user_id();
        
        if (!$operator_id) {
            wp_send_json_error(array('message' => __('Invalid operator.', 'environmental-live-chat')));
        }
        
        // Update or insert operator status
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_operators} WHERE user_id = %d",
            $operator_id
        ));
        
        if ($existing) {
            $updated = $wpdb->update(
                $this->table_operators,
                array(
                    'status' => $status,
                    'last_seen' => current_time('mysql')
                ),
                array('user_id' => $operator_id),
                array('%s', '%s'),
                array('%d')
            );
        } else {
            $updated = $wpdb->insert(
                $this->table_operators,
                array(
                    'user_id' => $operator_id,
                    'status' => $status,
                    'department' => 'general',
                    'max_chats' => 5,
                    'last_seen' => current_time('mysql')
                )
            );
        }
        
        if ($updated !== false) {
            wp_send_json_success(array('status' => $status));
        } else {
            wp_send_json_error(array('message' => __('Failed to update status.', 'environmental-live-chat')));
        }
    }
    
    /**
     * Handle file upload in chat
     */
    public function handle_file_upload() {
        check_ajax_referer('elc_chat_nonce', 'nonce');
        
        $session_id = intval($_POST['session_id'] ?? 0);
        
        if (!$session_id) {
            wp_send_json_error(array('message' => __('Invalid session.', 'environmental-live-chat')));
        }
        
        if (!isset($_FILES['file'])) {
            wp_send_json_error(array('message' => __('No file uploaded.', 'environmental-live-chat')));
        }
        
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx');
        $file_extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            wp_send_json_error(array('message' => __('File type not allowed.', 'environmental-live-chat')));
        }
        
        if ($_FILES['file']['size'] > 5 * 1024 * 1024) { // 5MB limit
            wp_send_json_error(array('message' => __('File size too large. Maximum 5MB allowed.', 'environmental-live-chat')));
        }
        
        // Upload file
        $upload = wp_handle_upload($_FILES['file'], array('test_form' => false));
        
        if ($upload && !isset($upload['error'])) {
            // Save file message
            $message = sprintf(__('File uploaded: %s', 'environmental-live-chat'), basename($upload['file']));
            $message_id = $this->save_message($session_id, 'visitor', $message, '', $upload['url']);
            
            wp_send_json_success(array(
                'message_id' => $message_id,
                'file_url' => $upload['url'],
                'file_name' => basename($upload['file'])
            ));
        } else {
            wp_send_json_error(array('message' => $upload['error'] ?? __('Upload failed.', 'environmental-live-chat')));
        }
    }
    
    // Helper Methods
    
    private function is_business_hours() {
        $options = get_option('elc_business_hours', array());
        if (empty($options['enabled'])) {
            return true; // Always available if not configured
        }
        
        $current_day = strtolower(date('l'));
        $current_time = date('H:i');
        
        if (!isset($options[$current_day]) || !$options[$current_day]['enabled']) {
            return false;
        }
        
        $start = $options[$current_day]['start'];
        $end = $options[$current_day]['end'];
        
        return ($current_time >= $start && $current_time <= $end);
    }
    
    private function find_available_operator($department = 'general') {
        global $wpdb;
        
        $operators = $wpdb->get_results($wpdb->prepare(
            "SELECT o.*, u.display_name, u.user_email,
                    (SELECT COUNT(*) FROM {$this->table_sessions} s 
                     WHERE s.operator_id = o.user_id AND s.status = 'active') as active_chats
             FROM {$this->table_operators} o
             JOIN {$wpdb->users} u ON o.user_id = u.ID
             WHERE o.status = 'online' 
             AND (o.department = %s OR o.department = 'all')
             ORDER BY active_chats ASC, o.last_seen DESC
             LIMIT 1",
            $department
        ));
        
        if (!empty($operators)) {
            $operator = $operators[0];
            if ($operator->active_chats < $operator->max_chats) {
                return get_user_by('ID', $operator->user_id);
            }
        }
        
        return null;
    }
    
    private function save_message($session_id, $sender_type, $message, $sender_name = '', $attachment_url = '') {
        global $wpdb;
        
        $message_data = array(
            'session_id' => $session_id,
            'sender_type' => $sender_type,
            'sender_name' => $sender_name,
            'message' => $message,
            'attachment_url' => $attachment_url,
            'sent_at' => current_time('mysql')
        );
        
        $inserted = $wpdb->insert($this->table_messages, $message_data);
        
        return $inserted ? $wpdb->insert_id : false;
    }
    
    private function get_session($session_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_sessions} WHERE id = %d",
            $session_id
        ));
    }
    
    private function get_session_messages($session_id, $last_message_id = 0) {
        global $wpdb;
        
        $where_clause = $last_message_id > 0 ? "AND id > %d" : "";
        $params = $last_message_id > 0 ? array($session_id, $last_message_id) : array($session_id);
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_messages} 
             WHERE session_id = %d {$where_clause}
             ORDER BY sent_at ASC",
            ...$params
        ));
    }
    
    private function get_session_status($session_id) {
        $session = $this->get_session($session_id);
        return $session ? $session->status : 'not_found';
    }
    
    private function update_session_activity($session_id) {
        global $wpdb;
        
        $wpdb->update(
            $this->table_sessions,
            array('last_activity' => current_time('mysql')),
            array('id' => $session_id),
            array('%s'),
            array('%d')
        );
    }
    
    private function get_visitor_ip() {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        return sanitize_text_field($ip);
    }
    
    /**
     * Get active chat sessions for operator dashboard
     */
    public function get_active_sessions($operator_id = null) {
        global $wpdb;
        
        $where_clause = $operator_id ? "AND (operator_id = %d OR operator_id IS NULL)" : "";
        $params = $operator_id ? array($operator_id) : array();
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, 
                    (SELECT COUNT(*) FROM {$this->table_messages} m WHERE m.session_id = s.id) as message_count,
                    (SELECT m.message FROM {$this->table_messages} m WHERE m.session_id = s.id ORDER BY sent_at DESC LIMIT 1) as last_message
             FROM {$this->table_sessions} s
             WHERE s.status IN ('active', 'waiting') {$where_clause}
             ORDER BY s.last_activity DESC",
            ...$params
        ));
    }
    
    /**
     * Get chat statistics
     */
    public function get_chat_statistics($date_from = null, $date_to = null) {
        global $wpdb;
        
        $date_from = $date_from ?: date('Y-m-d', strtotime('-30 days'));
        $date_to = $date_to ?: date('Y-m-d');
        
        $stats = array();
        
        // Total chats
        $stats['total_chats'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_sessions} 
             WHERE DATE(created_at) BETWEEN %s AND %s",
            $date_from, $date_to
        ));
        
        // Average response time
        $stats['avg_response_time'] = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(TIMESTAMPDIFF(SECOND, created_at, 
                (SELECT MIN(sent_at) FROM {$this->table_messages} m 
                 WHERE m.session_id = s.id AND m.sender_type = 'operator')))
             FROM {$this->table_sessions} s
             WHERE DATE(created_at) BETWEEN %s AND %s
             AND status = 'ended'",
            $date_from, $date_to
        ));
        
        // Average rating
        $stats['avg_rating'] = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(rating) FROM {$this->table_sessions} 
             WHERE DATE(created_at) BETWEEN %s AND %s
             AND rating > 0",
            $date_from, $date_to
        ));
        
        // Chats by status
        $status_counts = $wpdb->get_results($wpdb->prepare(
            "SELECT status, COUNT(*) as count 
             FROM {$this->table_sessions} 
             WHERE DATE(created_at) BETWEEN %s AND %s
             GROUP BY status",
            $date_from, $date_to
        ), OBJECT_K);
        
        $stats['by_status'] = $status_counts;
        
        return $stats;
    }
}

// Initialize the live chat system
Environmental_Live_Chat_System::get_instance();
