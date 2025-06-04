<?php
/**
 * Real-time Messaging System for Item Exchange Platform
 * 
 * Handles private messaging between users for exchange negotiations,
 * real-time chat functionality, and message notifications.
 * 
 * @package EnvironmentalItemExchange
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EIE_Messaging_System {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Database manager instance
     */
    private $db_manager;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->db_manager = EIE_Database_Manager::get_instance();
        $this->init();
    }
    
    /**
     * Initialize messaging system
     */
    private function init() {
        // AJAX handlers
        add_action('wp_ajax_eie_send_message', array($this, 'send_message'));
        add_action('wp_ajax_eie_get_messages', array($this, 'get_messages'));
        add_action('wp_ajax_eie_get_conversations', array($this, 'get_conversations'));
        add_action('wp_ajax_eie_mark_read', array($this, 'mark_messages_read'));
        add_action('wp_ajax_eie_delete_conversation', array($this, 'delete_conversation'));
        add_action('wp_ajax_eie_block_user', array($this, 'block_user'));
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Create database tables
        add_action('init', array($this, 'create_database_tables'));
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // WebSocket support (if available)
        add_action('init', array($this, 'init_websocket_support'));
    }
    
    /**
     * Create database tables for messaging
     */
    public function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Messages table
        $table_messages = $wpdb->prefix . 'eie_messages';
        $sql_messages = "CREATE TABLE IF NOT EXISTS $table_messages (
            message_id INT PRIMARY KEY AUTO_INCREMENT,
            conversation_id VARCHAR(50) NOT NULL,
            sender_id INT NOT NULL,
            receiver_id INT NOT NULL,
            exchange_post_id INT NULL,
            message_type ENUM('text', 'image', 'file', 'system', 'offer') DEFAULT 'text',
            message_content TEXT NOT NULL,
            message_data JSON NULL,
            is_read BOOLEAN DEFAULT FALSE,
            is_deleted_sender BOOLEAN DEFAULT FALSE,
            is_deleted_receiver BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_conversation (conversation_id, created_at),
            INDEX idx_sender (sender_id, created_at),
            INDEX idx_receiver (receiver_id, created_at),
            INDEX idx_exchange_post (exchange_post_id),
            INDEX idx_unread (receiver_id, is_read, created_at)
        ) $charset_collate;";
        
        // Conversations table
        $table_conversations = $wpdb->prefix . 'eie_conversations';
        $sql_conversations = "CREATE TABLE IF NOT EXISTS $table_conversations (
            conversation_id VARCHAR(50) PRIMARY KEY,
            user1_id INT NOT NULL,
            user2_id INT NOT NULL,
            exchange_post_id INT NULL,
            last_message_id INT NULL,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            user1_deleted BOOLEAN DEFAULT FALSE,
            user2_deleted BOOLEAN DEFAULT FALSE,
            user1_blocked BOOLEAN DEFAULT FALSE,
            user2_blocked BOOLEAN DEFAULT FALSE,
            conversation_status ENUM('active', 'archived', 'blocked') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            INDEX idx_user1 (user1_id, last_activity),
            INDEX idx_user2 (user2_id, last_activity),
            INDEX idx_exchange_post (exchange_post_id),
            INDEX idx_activity (last_activity DESC)
        ) $charset_collate;";
        
        // Message attachments table
        $table_attachments = $wpdb->prefix . 'eie_message_attachments';
        $sql_attachments = "CREATE TABLE IF NOT EXISTS $table_attachments (
            attachment_id INT PRIMARY KEY AUTO_INCREMENT,
            message_id INT NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_url VARCHAR(500) NOT NULL,
            file_type VARCHAR(100) NOT NULL,
            file_size INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (message_id) REFERENCES $table_messages(message_id) ON DELETE CASCADE,
            INDEX idx_message (message_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_messages);
        dbDelta($sql_conversations);
        dbDelta($sql_attachments);
    }
    
    /**
     * Send message via AJAX
     */
    public function send_message() {
        check_ajax_referer('eie_messaging_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to send messages.', 'environmental-item-exchange'));
        }
        
        $sender_id = get_current_user_id();
        $receiver_id = intval($_POST['receiver_id']);
        $message_content = sanitize_textarea_field($_POST['message']);
        $exchange_post_id = isset($_POST['exchange_post_id']) ? intval($_POST['exchange_post_id']) : null;
        $message_type = isset($_POST['message_type']) ? sanitize_text_field($_POST['message_type']) : 'text';
        
        if (empty($message_content) || $receiver_id === $sender_id) {
            wp_send_json_error(__('Invalid message data.', 'environmental-item-exchange'));
        }
        
        // Check if users are blocked
        if ($this->are_users_blocked($sender_id, $receiver_id)) {
            wp_send_json_error(__('Cannot send message to this user.', 'environmental-item-exchange'));
        }
        
        // Create or get conversation
        $conversation_id = $this->get_or_create_conversation($sender_id, $receiver_id, $exchange_post_id);
        
        // Save message
        $message_id = $this->save_message($conversation_id, $sender_id, $receiver_id, $message_content, $message_type, $exchange_post_id);
        
        if ($message_id) {
            // Update conversation
            $this->update_conversation($conversation_id, $message_id);
            
            // Send notification
            $this->send_message_notification($receiver_id, $sender_id, $message_content);
            
            // Get message data for response
            $message_data = $this->get_message_by_id($message_id);
            
            wp_send_json_success(array(
                'message' => $message_data,
                'success_message' => __('Message sent successfully!', 'environmental-item-exchange')
            ));
        } else {
            wp_send_json_error(__('Failed to send message.', 'environmental-item-exchange'));
        }
    }
    
    /**
     * Get messages for a conversation
     */
    public function get_messages() {
        check_ajax_referer('eie_messaging_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'environmental-item-exchange'));
        }
        
        $user_id = get_current_user_id();
        $conversation_id = sanitize_text_field($_POST['conversation_id']);
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = 20;
        
        // Verify user is part of conversation
        if (!$this->is_user_in_conversation($user_id, $conversation_id)) {
            wp_send_json_error(__('Access denied.', 'environmental-item-exchange'));
        }
        
        $messages = $this->get_conversation_messages($conversation_id, $page, $per_page);
        
        // Mark messages as read
        $this->mark_messages_read($conversation_id, $user_id);
        
        wp_send_json_success($messages);
    }
    
    /**
     * Get user conversations
     */
    public function get_conversations() {
        check_ajax_referer('eie_messaging_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'environmental-item-exchange'));
        }
        
        $user_id = get_current_user_id();
        $conversations = $this->get_user_conversations($user_id);
        
        wp_send_json_success($conversations);
    }
    
    /**
     * Save message to database
     */
    private function save_message($conversation_id, $sender_id, $receiver_id, $content, $type = 'text', $exchange_post_id = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'eie_messages';
        
        $result = $wpdb->insert(
            $table,
            array(
                'conversation_id' => $conversation_id,
                'sender_id' => $sender_id,
                'receiver_id' => $receiver_id,
                'exchange_post_id' => $exchange_post_id,
                'message_type' => $type,
                'message_content' => $content,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%d', '%d', '%d', '%s', '%s', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Get or create conversation between two users
     */
    private function get_or_create_conversation($user1_id, $user2_id, $exchange_post_id = null) {
        global $wpdb;
        
        // Order user IDs for consistent conversation ID
        $min_id = min($user1_id, $user2_id);
        $max_id = max($user1_id, $user2_id);
        
        // Create conversation ID
        $conversation_id = "conv_{$min_id}_{$max_id}";
        if ($exchange_post_id) {
            $conversation_id .= "_{$exchange_post_id}";
        }
        
        $table = $wpdb->prefix . 'eie_conversations';
        
        // Check if conversation exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE conversation_id = %s",
            $conversation_id
        ));
        
        if (!$existing) {
            // Create new conversation
            $wpdb->insert(
                $table,
                array(
                    'conversation_id' => $conversation_id,
                    'user1_id' => $min_id,
                    'user2_id' => $max_id,
                    'exchange_post_id' => $exchange_post_id,
                    'created_at' => current_time('mysql')
                ),
                array('%s', '%d', '%d', '%d', '%s')
            );
        }
        
        return $conversation_id;
    }
    
    /**
     * Get conversation messages
     */
    private function get_conversation_messages($conversation_id, $page = 1, $per_page = 20) {
        global $wpdb;
        
        $offset = ($page - 1) * $per_page;
        $table = $wpdb->prefix . 'eie_messages';
        
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, 
                    u.display_name as sender_name,
                    u.user_email as sender_email
             FROM $table m
             LEFT JOIN {$wpdb->users} u ON m.sender_id = u.ID
             WHERE m.conversation_id = %s
             ORDER BY m.created_at DESC
             LIMIT %d OFFSET %d",
            $conversation_id, $per_page, $offset
        ));
        
        // Format messages
        foreach ($messages as &$message) {
            $message->time_ago = human_time_diff(strtotime($message->created_at), current_time('timestamp'));
            $message->avatar_url = get_avatar_url($message->sender_id, array('size' => 32));
        }
        
        return array_reverse($messages); // Show oldest first
    }
    
    /**
     * Get user conversations
     */
    private function get_user_conversations($user_id) {
        global $wpdb;
        
        $table_conv = $wpdb->prefix . 'eie_conversations';
        $table_msg = $wpdb->prefix . 'eie_messages';
        
        $conversations = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, 
                    CASE 
                        WHEN c.user1_id = %d THEN c.user2_id 
                        ELSE c.user1_id 
                    END as other_user_id,
                    m.message_content as last_message,
                    m.created_at as last_message_time,
                    (SELECT COUNT(*) FROM $table_msg 
                     WHERE conversation_id = c.conversation_id 
                     AND receiver_id = %d AND is_read = FALSE) as unread_count
             FROM $table_conv c
             LEFT JOIN $table_msg m ON c.last_message_id = m.message_id
             WHERE (c.user1_id = %d OR c.user2_id = %d)
             AND ((c.user1_id = %d AND c.user1_deleted = FALSE) OR 
                  (c.user2_id = %d AND c.user2_deleted = FALSE))
             ORDER BY c.last_activity DESC",
            $user_id, $user_id, $user_id, $user_id, $user_id, $user_id
        ));
        
        // Add user info for each conversation
        foreach ($conversations as &$conv) {
            $other_user = get_userdata($conv->other_user_id);
            $conv->other_user_name = $other_user ? $other_user->display_name : __('Unknown User', 'environmental-item-exchange');
            $conv->other_user_avatar = get_avatar_url($conv->other_user_id, array('size' => 40));
            $conv->last_message_time_ago = $conv->last_message_time ? 
                human_time_diff(strtotime($conv->last_message_time), current_time('timestamp')) . ' ' . __('ago', 'environmental-item-exchange') :
                '';
        }
        
        return $conversations;
    }
    
    /**
     * Check if users are blocked
     */
    private function are_users_blocked($user1_id, $user2_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'eie_conversations';
        
        $blocked = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table 
             WHERE ((user1_id = %d AND user2_id = %d AND (user1_blocked = TRUE OR user2_blocked = TRUE))
                OR (user1_id = %d AND user2_id = %d AND (user1_blocked = TRUE OR user2_blocked = TRUE)))",
            $user1_id, $user2_id, $user2_id, $user1_id
        ));
        
        return $blocked > 0;
    }
    
    /**
     * Mark messages as read
     */
    public function mark_messages_read($conversation_id = null, $user_id = null) {
        if ($conversation_id && $user_id) {
            global $wpdb;
            
            $table = $wpdb->prefix . 'eie_messages';
            $wpdb->update(
                $table,
                array('is_read' => true),
                array('conversation_id' => $conversation_id, 'receiver_id' => $user_id),
                array('%d'),
                array('%s', '%d')
            );
        } else {
            // Handle AJAX request
            check_ajax_referer('eie_messaging_nonce', 'nonce');
            
            if (!is_user_logged_in()) {
                wp_send_json_error(__('You must be logged in.', 'environmental-item-exchange'));
            }
            
            $conversation_id = sanitize_text_field($_POST['conversation_id']);
            $user_id = get_current_user_id();
            
            $this->mark_messages_read($conversation_id, $user_id);
            wp_send_json_success();
        }
    }
    
    /**
     * Send message notification
     */
    private function send_message_notification($receiver_id, $sender_id, $message_content) {
        $sender = get_userdata($sender_id);
        $receiver = get_userdata($receiver_id);
        
        if (!$sender || !$receiver) {
            return;
        }
        
        $subject = sprintf(
            __('New message from %s', 'environmental-item-exchange'),
            $sender->display_name
        );
        
        $message = sprintf(
            __("You have received a new message from %s:\n\n%s\n\nReply at: %s"),
            $sender->display_name,
            wp_trim_words($message_content, 20),
            admin_url('admin.php?page=eie-messages')
        );
        
        wp_mail($receiver->user_email, $subject, $message);
    }
    
    /**
     * Update conversation with last message
     */
    private function update_conversation($conversation_id, $message_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'eie_conversations';
        $wpdb->update(
            $table,
            array(
                'last_message_id' => $message_id,
                'last_activity' => current_time('mysql')
            ),
            array('conversation_id' => $conversation_id),
            array('%d', '%s'),
            array('%s')
        );
    }
    
    /**
     * Check if user is in conversation
     */
    private function is_user_in_conversation($user_id, $conversation_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'eie_conversations';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table 
             WHERE conversation_id = %s AND (user1_id = %d OR user2_id = %d)",
            $conversation_id, $user_id, $user_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Get message by ID
     */
    private function get_message_by_id($message_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'eie_messages';
        $message = $wpdb->get_row($wpdb->prepare(
            "SELECT m.*, u.display_name as sender_name
             FROM $table m
             LEFT JOIN {$wpdb->users} u ON m.sender_id = u.ID
             WHERE m.message_id = %d",
            $message_id
        ));
        
        if ($message) {
            $message->time_ago = human_time_diff(strtotime($message->created_at), current_time('timestamp'));
            $message->avatar_url = get_avatar_url($message->sender_id, array('size' => 32));
        }
        
        return $message;
    }
    
    /**
     * Delete conversation
     */
    public function delete_conversation() {
        check_ajax_referer('eie_messaging_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'environmental-item-exchange'));
        }
        
        $user_id = get_current_user_id();
        $conversation_id = sanitize_text_field($_POST['conversation_id']);
        
        // Mark as deleted for this user
        global $wpdb;
        $table = $wpdb->prefix . 'eie_conversations';
        
        $wpdb->query($wpdb->prepare(
            "UPDATE $table SET 
             user1_deleted = CASE WHEN user1_id = %d THEN TRUE ELSE user1_deleted END,
             user2_deleted = CASE WHEN user2_id = %d THEN TRUE ELSE user2_deleted END
             WHERE conversation_id = %s",
            $user_id, $user_id, $conversation_id
        ));
        
        wp_send_json_success(__('Conversation deleted.', 'environmental-item-exchange'));
    }
    
    /**
     * Block user
     */
    public function block_user() {
        check_ajax_referer('eie_messaging_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'environmental-item-exchange'));
        }
        
        $user_id = get_current_user_id();
        $conversation_id = sanitize_text_field($_POST['conversation_id']);
        
        // Block user in conversation
        global $wpdb;
        $table = $wpdb->prefix . 'eie_conversations';
        
        $wpdb->query($wpdb->prepare(
            "UPDATE $table SET 
             user1_blocked = CASE WHEN user1_id = %d THEN TRUE ELSE user1_blocked END,
             user2_blocked = CASE WHEN user2_id = %d THEN TRUE ELSE user2_blocked END,
             conversation_status = 'blocked'
             WHERE conversation_id = %s",
            $user_id, $user_id, $conversation_id
        ));
        
        wp_send_json_success(__('User blocked.', 'environmental-item-exchange'));
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        if (is_admin()) {
            return;
        }
        
        wp_enqueue_script(
            'eie-messaging',
            EIE_PLUGIN_URL . 'assets/js/messaging.js',
            array('jquery'),
            EIE_PLUGIN_VERSION,
            true
        );
        
        wp_enqueue_style(
            'eie-messaging',
            EIE_PLUGIN_URL . 'assets/css/messaging.css',
            array(),
            EIE_PLUGIN_VERSION
        );
        
        wp_localize_script('eie-messaging', 'eieMessaging', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eie_messaging_nonce'),
            'strings' => array(
                'sending' => __('Sending...', 'environmental-item-exchange'),
                'sent' => __('Sent', 'environmental-item-exchange'),
                'error' => __('Error sending message', 'environmental-item-exchange'),
                'confirm_delete' => __('Are you sure you want to delete this conversation?', 'environmental-item-exchange'),
                'confirm_block' => __('Are you sure you want to block this user?', 'environmental-item-exchange')
            )
        ));
    }
    
    /**
     * Initialize WebSocket support
     */
    public function init_websocket_support() {
        // Check if Ratchet/ReactPHP is available for real-time messaging
        if (class_exists('Ratchet\Server\IoServer')) {
            // Initialize WebSocket server for real-time messaging
            // This would require a separate server process
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'environmental-item-exchange',
            __('Messages', 'environmental-item-exchange'),
            __('Messages', 'environmental-item-exchange'),
            'manage_options',
            'eie-messages',
            array($this, 'admin_messages_page')
        );
    }
    
    /**
     * Admin messages page
     */
    public function admin_messages_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Exchange Messages', 'environmental-item-exchange'); ?></h1>
            
            <div class="eie-admin-messages">
                <div class="eie-stats-cards">
                    <div class="eie-stat-card">
                        <h3><?php echo $this->get_total_messages(); ?></h3>
                        <p><?php _e('Total Messages', 'environmental-item-exchange'); ?></p>
                    </div>
                    <div class="eie-stat-card">
                        <h3><?php echo $this->get_active_conversations(); ?></h3>
                        <p><?php _e('Active Conversations', 'environmental-item-exchange'); ?></p>
                    </div>
                    <div class="eie-stat-card">
                        <h3><?php echo $this->get_today_messages(); ?></h3>
                        <p><?php _e('Messages Today', 'environmental-item-exchange'); ?></p>
                    </div>
                </div>
                
                <div class="eie-recent-messages">
                    <h2><?php _e('Recent Messages', 'environmental-item-exchange'); ?></h2>
                    <?php $this->display_recent_messages(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get statistics for admin
     */
    private function get_total_messages() {
        global $wpdb;
        $table = $wpdb->prefix . 'eie_messages';
        return $wpdb->get_var("SELECT COUNT(*) FROM $table");
    }
    
    private function get_active_conversations() {
        global $wpdb;
        $table = $wpdb->prefix . 'eie_conversations';
        return $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE conversation_status = 'active'");
    }
    
    private function get_today_messages() {
        global $wpdb;
        $table = $wpdb->prefix . 'eie_messages';
        return $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE DATE(created_at) = CURDATE()");
    }
    
    /**
     * Display recent messages in admin
     */
    private function display_recent_messages() {
        global $wpdb;
        $table_msg = $wpdb->prefix . 'eie_messages';
        
        $messages = $wpdb->get_results(
            "SELECT m.*, 
                    sender.display_name as sender_name,
                    receiver.display_name as receiver_name
             FROM $table_msg m
             LEFT JOIN {$wpdb->users} sender ON m.sender_id = sender.ID
             LEFT JOIN {$wpdb->users} receiver ON m.receiver_id = receiver.ID
             ORDER BY m.created_at DESC
             LIMIT 20"
        );
        
        if ($messages) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('From', 'environmental-item-exchange') . '</th>';
            echo '<th>' . __('To', 'environmental-item-exchange') . '</th>';
            echo '<th>' . __('Message', 'environmental-item-exchange') . '</th>';
            echo '<th>' . __('Date', 'environmental-item-exchange') . '</th>';
            echo '<th>' . __('Status', 'environmental-item-exchange') . '</th>';
            echo '</tr></thead><tbody>';
            
            foreach ($messages as $message) {
                echo '<tr>';
                echo '<td>' . esc_html($message->sender_name) . '</td>';
                echo '<td>' . esc_html($message->receiver_name) . '</td>';
                echo '<td>' . esc_html(wp_trim_words($message->message_content, 10)) . '</td>';
                echo '<td>' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($message->created_at)) . '</td>';
                echo '<td>' . ($message->is_read ? __('Read', 'environmental-item-exchange') : __('Unread', 'environmental-item-exchange')) . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        } else {
            echo '<p>' . __('No messages found.', 'environmental-item-exchange') . '</p>';
        }
    }
    
    /**
     * Get user message statistics
     */
    public function get_user_stats($user_id) {
        global $wpdb;
        
        $table_msg = $wpdb->prefix . 'eie_messages';
        $table_conv = $wpdb->prefix . 'eie_conversations';
        
        $stats = array();
        
        // Total messages sent
        $stats['messages_sent'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_msg WHERE sender_id = %d", $user_id
        ));
        
        // Total messages received
        $stats['messages_received'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_msg WHERE receiver_id = %d", $user_id
        ));
        
        // Active conversations
        $stats['active_conversations'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_conv 
             WHERE (user1_id = %d OR user2_id = %d) AND conversation_status = 'active'",
            $user_id, $user_id
        ));
        
        // Unread messages
        $stats['unread_messages'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_msg WHERE receiver_id = %d AND is_read = FALSE", $user_id
        ));
        
        return $stats;
    }
}
