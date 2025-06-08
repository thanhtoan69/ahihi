<?php
/**
 * REST API Class
 * 
 * Handles REST API endpoints for mobile app and external integrations
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_REST_API {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
        add_filter('rest_authentication_errors', array($this, 'authenticate_request'));
    }
    
    public function register_routes() {
        $namespace = 'environmental-support/v1';
        
        // Chat endpoints
        register_rest_route($namespace, '/chat/start', array(
            'methods' => 'POST',
            'callback' => array($this, 'start_chat'),
            'permission_callback' => array($this, 'permission_check'),
            'args' => array(
                'customer_name' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'customer_email' => array(
                    'required' => true,
                    'type' => 'string',
                    'format' => 'email',
                    'sanitize_callback' => 'sanitize_email'
                ),
                'department' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => 'general'
                ),
                'initial_message' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field'
                )
            )
        ));
        
        register_rest_route($namespace, '/chat/(?P<session_id>\d+)/messages', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_chat_messages'),
            'permission_callback' => array($this, 'permission_check'),
            'args' => array(
                'session_id' => array(
                    'required' => true,
                    'type' => 'integer'
                ),
                'since' => array(
                    'required' => false,
                    'type' => 'string',
                    'format' => 'date-time'
                )
            )
        ));
        
        register_rest_route($namespace, '/chat/(?P<session_id>\d+)/send', array(
            'methods' => 'POST',
            'callback' => array($this, 'send_chat_message'),
            'permission_callback' => array($this, 'permission_check'),
            'args' => array(
                'session_id' => array(
                    'required' => true,
                    'type' => 'integer'
                ),
                'message' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field'
                ),
                'sender_type' => array(
                    'required' => true,
                    'type' => 'string',
                    'enum' => array('customer', 'operator', 'system')
                )
            )
        ));
        
        register_rest_route($namespace, '/chat/(?P<session_id>\d+)/end', array(
            'methods' => 'POST',
            'callback' => array($this, 'end_chat'),
            'permission_callback' => array($this, 'permission_check'),
            'args' => array(
                'session_id' => array(
                    'required' => true,
                    'type' => 'integer'
                ),
                'rating' => array(
                    'required' => false,
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 5
                ),
                'feedback' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field'
                )
            )
        ));
        
        register_rest_route($namespace, '/chat/(?P<session_id>\d+)/upload', array(
            'methods' => 'POST',
            'callback' => array($this, 'upload_chat_file'),
            'permission_callback' => array($this, 'permission_check'),
            'args' => array(
                'session_id' => array(
                    'required' => true,
                    'type' => 'integer'
                )
            )
        ));
        
        // Support Ticket endpoints
        register_rest_route($namespace, '/tickets', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_tickets'),
            'permission_callback' => array($this, 'permission_check'),
            'args' => array(
                'status' => array(
                    'required' => false,
                    'type' => 'string',
                    'enum' => array('open', 'in-progress', 'resolved', 'closed')
                ),
                'customer_email' => array(
                    'required' => false,
                    'type' => 'string',
                    'format' => 'email'
                ),
                'limit' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 20,
                    'maximum' => 100
                )
            )
        ));
        
        register_rest_route($namespace, '/tickets', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_ticket'),
            'permission_callback' => array($this, 'permission_check'),
            'args' => array(
                'customer_name' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'customer_email' => array(
                    'required' => true,
                    'type' => 'string',
                    'format' => 'email',
                    'sanitize_callback' => 'sanitize_email'
                ),
                'subject' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'message' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field'
                ),
                'category' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => 'general'
                ),
                'priority' => array(
                    'required' => false,
                    'type' => 'string',
                    'enum' => array('low', 'medium', 'high', 'urgent'),
                    'default' => 'medium'
                )
            )
        ));
        
        register_rest_route($namespace, '/tickets/(?P<ticket_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_ticket'),
            'permission_callback' => array($this, 'permission_check'),
            'args' => array(
                'ticket_id' => array(
                    'required' => true,
                    'type' => 'integer'
                )
            )
        ));
        
        register_rest_route($namespace, '/tickets/(?P<ticket_id>\d+)/replies', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_ticket_replies'),
            'permission_callback' => array($this, 'permission_check'),
            'args' => array(
                'ticket_id' => array(
                    'required' => true,
                    'type' => 'integer'
                )
            )
        ));
        
        register_rest_route($namespace, '/tickets/(?P<ticket_id>\d+)/reply', array(
            'methods' => 'POST',
            'callback' => array($this, 'reply_ticket'),
            'permission_callback' => array($this, 'permission_check'),
            'args' => array(
                'ticket_id' => array(
                    'required' => true,
                    'type' => 'integer'
                ),
                'message' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field'
                ),
                'sender_type' => array(
                    'required' => true,
                    'type' => 'string',
                    'enum' => array('customer', 'agent')
                )
            )
        ));
        
        // FAQ endpoints
        register_rest_route($namespace, '/faq/search', array(
            'methods' => 'GET',
            'callback' => array($this, 'search_faq'),
            'permission_callback' => array($this, 'public_permission_check'),
            'args' => array(
                'query' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'category' => array(
                    'required' => false,
                    'type' => 'string'
                ),
                'limit' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 10,
                    'maximum' => 50
                )
            )
        ));
        
        register_rest_route($namespace, '/faq/categories', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_faq_categories'),
            'permission_callback' => array($this, 'public_permission_check')
        ));
        
        register_rest_route($namespace, '/faq/(?P<faq_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_faq_item'),
            'permission_callback' => array($this, 'public_permission_check'),
            'args' => array(
                'faq_id' => array(
                    'required' => true,
                    'type' => 'integer'
                )
            )
        ));
        
        register_rest_route($namespace, '/faq/(?P<faq_id>\d+)/rate', array(
            'methods' => 'POST',
            'callback' => array($this, 'rate_faq'),
            'permission_callback' => array($this, 'public_permission_check'),
            'args' => array(
                'faq_id' => array(
                    'required' => true,
                    'type' => 'integer'
                ),
                'helpful' => array(
                    'required' => true,
                    'type' => 'boolean'
                ),
                'user_ip' => array(
                    'required' => false,
                    'type' => 'string'
                )
            )
        ));
        
        // Chatbot endpoints
        register_rest_route($namespace, '/chatbot/query', array(
            'methods' => 'POST',
            'callback' => array($this, 'chatbot_query'),
            'permission_callback' => array($this, 'public_permission_check'),
            'args' => array(
                'message' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field'
                ),
                'session_id' => array(
                    'required' => false,
                    'type' => 'integer'
                )
            )
        ));
        
        // Analytics endpoints (admin only)
        register_rest_route($namespace, '/analytics/overview', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_analytics_overview'),
            'permission_callback' => array($this, 'admin_permission_check')
        ));
        
        register_rest_route($namespace, '/analytics/chat-stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_chat_analytics'),
            'permission_callback' => array($this, 'admin_permission_check'),
            'args' => array(
                'date_from' => array(
                    'required' => false,
                    'type' => 'string',
                    'format' => 'date'
                ),
                'date_to' => array(
                    'required' => false,
                    'type' => 'string',
                    'format' => 'date'
                )
            )
        ));
        
        // Operator endpoints (admin only)
        register_rest_route($namespace, '/operators', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_operators'),
            'permission_callback' => array($this, 'admin_permission_check')
        ));
        
        register_rest_route($namespace, '/operators/(?P<operator_id>\d+)/status', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_operator_status'),
            'permission_callback' => array($this, 'admin_permission_check'),
            'args' => array(
                'operator_id' => array(
                    'required' => true,
                    'type' => 'integer'
                ),
                'status' => array(
                    'required' => true,
                    'type' => 'string',
                    'enum' => array('online', 'away', 'offline')
                )
            )
        ));
        
        // System status endpoint
        register_rest_route($namespace, '/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_system_status'),
            'permission_callback' => array($this, 'public_permission_check')
        ));
    }
    
    // Permission callbacks
    public function permission_check($request) {
        // For now, allow all authenticated requests
        // In production, implement proper API key authentication
        return true;
    }
    
    public function public_permission_check($request) {
        return true;
    }
    
    public function admin_permission_check($request) {
        return current_user_can('manage_options');
    }
    
    public function authenticate_request($result) {
        // Skip authentication for public endpoints
        if (!empty($result)) {
            return $result;
        }
        
        // Check for API key in headers
        $api_key = $_SERVER['HTTP_X_API_KEY'] ?? '';
        
        if (empty($api_key)) {
            return new WP_Error(
                'missing_api_key',
                __('API key is required', 'environmental-live-chat'),
                array('status' => 401)
            );
        }
        
        // Validate API key (implement your validation logic here)
        $valid_keys = get_option('environmental_api_keys', array());
        
        if (!in_array($api_key, $valid_keys)) {
            return new WP_Error(
                'invalid_api_key',
                __('Invalid API key', 'environmental-live-chat'),
                array('status' => 401)
            );
        }
        
        return $result;
    }
    
    // Chat endpoints
    public function start_chat($request) {
        $live_chat = Environmental_Live_Chat_System::get_instance();
        
        $customer_name = $request->get_param('customer_name');
        $customer_email = $request->get_param('customer_email');
        $department = $request->get_param('department');
        $initial_message = $request->get_param('initial_message');
        
        $session_id = $live_chat->start_chat_session(
            $customer_name,
            $customer_email,
            $department
        );
        
        if (!$session_id) {
            return new WP_Error(
                'chat_start_failed',
                __('Failed to start chat session', 'environmental-live-chat'),
                array('status' => 500)
            );
        }
        
        // Send initial message if provided
        if (!empty($initial_message)) {
            $live_chat->send_message($session_id, $initial_message, 'customer', $customer_name);
        }
        
        // Try chatbot response first
        if (!empty($initial_message)) {
            $chatbot = Environmental_Chatbot_System::get_instance();
            $bot_response = $chatbot->process_message($initial_message, $session_id);
            
            if ($bot_response && $bot_response['confidence'] >= 70) {
                $live_chat->send_message($session_id, $bot_response['response'], 'system', 'Environmental Bot');
            }
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'session_id' => $session_id,
            'message' => __('Chat session started successfully', 'environmental-live-chat')
        ));
    }
    
    public function get_chat_messages($request) {
        $session_id = $request->get_param('session_id');
        $since = $request->get_param('since');
        
        global $wpdb;
        
        $query = "SELECT * FROM {$wpdb->prefix}environmental_chat_messages 
                 WHERE session_id = %d";
        $params = array($session_id);
        
        if ($since) {
            $query .= " AND created_at > %s";
            $params[] = $since;
        }
        
        $query .= " ORDER BY created_at ASC";
        
        $messages = $wpdb->get_results($wpdb->prepare($query, $params));
        
        // Format messages for API response
        $formatted_messages = array();
        foreach ($messages as $message) {
            $formatted_messages[] = array(
                'id' => $message->id,
                'message' => $message->message,
                'sender_type' => $message->sender_type,
                'sender_name' => $message->sender_name,
                'created_at' => $message->created_at,
                'has_attachment' => !empty($message->attachment_url)
            );
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'messages' => $formatted_messages
        ));
    }
    
    public function send_chat_message($request) {
        $session_id = $request->get_param('session_id');
        $message = $request->get_param('message');
        $sender_type = $request->get_param('sender_type');
        
        $live_chat = Environmental_Live_Chat_System::get_instance();
        
        // Get sender name based on type
        $sender_name = '';
        if ($sender_type === 'customer') {
            global $wpdb;
            $session = $wpdb->get_row($wpdb->prepare(
                "SELECT customer_name FROM {$wpdb->prefix}environmental_chat_sessions WHERE id = %d",
                $session_id
            ));
            $sender_name = $session->customer_name ?? 'Customer';
        } elseif ($sender_type === 'operator') {
            $sender_name = wp_get_current_user()->display_name ?? 'Operator';
        } else {
            $sender_name = 'System';
        }
        
        $message_id = $live_chat->send_message($session_id, $message, $sender_type, $sender_name);
        
        if (!$message_id) {
            return new WP_Error(
                'message_send_failed',
                __('Failed to send message', 'environmental-live-chat'),
                array('status' => 500)
            );
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message_id' => $message_id,
            'message' => __('Message sent successfully', 'environmental-live-chat')
        ));
    }
    
    public function end_chat($request) {
        $session_id = $request->get_param('session_id');
        $rating = $request->get_param('rating');
        $feedback = $request->get_param('feedback');
        
        $live_chat = Environmental_Live_Chat_System::get_instance();
        
        $result = $live_chat->end_chat_session($session_id, $rating, $feedback);
        
        if (!$result) {
            return new WP_Error(
                'chat_end_failed',
                __('Failed to end chat session', 'environmental-live-chat'),
                array('status' => 500)
            );
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Chat session ended successfully', 'environmental-live-chat')
        ));
    }
    
    public function upload_chat_file($request) {
        $session_id = $request->get_param('session_id');
        
        if (empty($_FILES['file'])) {
            return new WP_Error(
                'no_file',
                __('No file uploaded', 'environmental-live-chat'),
                array('status' => 400)
            );
        }
        
        $file = $_FILES['file'];
        
        // Validate file
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt');
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            return new WP_Error(
                'invalid_file_type',
                __('File type not allowed', 'environmental-live-chat'),
                array('status' => 400)
            );
        }
        
        $max_size = get_option('environmental_live_chat_options')['max_file_size'] ?? 10;
        if ($file['size'] > ($max_size * 1024 * 1024)) {
            return new WP_Error(
                'file_too_large',
                sprintf(__('File size exceeds %d MB limit', 'environmental-live-chat'), $max_size),
                array('status' => 400)
            );
        }
        
        // Upload file
        $upload_dir = wp_upload_dir();
        $chat_dir = $upload_dir['basedir'] . '/environmental-chat-files/';
        
        if (!file_exists($chat_dir)) {
            wp_mkdir_p($chat_dir);
        }
        
        $filename = uniqid() . '_' . sanitize_file_name($file['name']);
        $file_path = $chat_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            $file_url = $upload_dir['baseurl'] . '/environmental-chat-files/' . $filename;
            
            // Send file message
            $live_chat = Environmental_Live_Chat_System::get_instance();
            global $wpdb;
            $session = $wpdb->get_row($wpdb->prepare(
                "SELECT customer_name FROM {$wpdb->prefix}environmental_chat_sessions WHERE id = %d",
                $session_id
            ));
            
            $message_id = $live_chat->send_message(
                $session_id,
                __('File uploaded: ', 'environmental-live-chat') . $file['name'],
                'customer',
                $session->customer_name ?? 'Customer',
                $file_url
            );
            
            return rest_ensure_response(array(
                'success' => true,
                'file_url' => $file_url,
                'message_id' => $message_id,
                'message' => __('File uploaded successfully', 'environmental-live-chat')
            ));
        }
        
        return new WP_Error(
            'upload_failed',
            __('Failed to upload file', 'environmental-live-chat'),
            array('status' => 500)
        );
    }
    
    // Support Ticket endpoints
    public function create_ticket($request) {
        $tickets = Environmental_Support_Tickets::get_instance();
        
        $customer_name = $request->get_param('customer_name');
        $customer_email = $request->get_param('customer_email');
        $subject = $request->get_param('subject');
        $message = $request->get_param('message');
        $category = $request->get_param('category');
        $priority = $request->get_param('priority');
        
        $ticket_id = $tickets->create_ticket(
            $customer_name,
            $customer_email,
            $subject,
            $message,
            $category,
            $priority
        );
        
        if (!$ticket_id) {
            return new WP_Error(
                'ticket_creation_failed',
                __('Failed to create ticket', 'environmental-live-chat'),
                array('status' => 500)
            );
        }
        
        global $wpdb;
        $ticket = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}environmental_support_tickets WHERE id = %d",
            $ticket_id
        ));
        
        return rest_ensure_response(array(
            'success' => true,
            'ticket_id' => $ticket_id,
            'ticket_number' => $ticket->ticket_number,
            'message' => __('Ticket created successfully', 'environmental-live-chat')
        ));
    }
    
    public function get_tickets($request) {
        $status = $request->get_param('status');
        $customer_email = $request->get_param('customer_email');
        $limit = $request->get_param('limit');
        
        $tickets = Environmental_Support_Tickets::get_instance();
        
        $args = array('limit' => $limit);
        if ($status) $args['status'] = $status;
        if ($customer_email) $args['customer_email'] = $customer_email;
        
        $results = $tickets->get_tickets($args);
        
        return rest_ensure_response(array(
            'success' => true,
            'tickets' => $results
        ));
    }
    
    public function get_ticket($request) {
        $ticket_id = $request->get_param('ticket_id');
        
        global $wpdb;
        $ticket = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}environmental_support_tickets WHERE id = %d",
            $ticket_id
        ));
        
        if (!$ticket) {
            return new WP_Error(
                'ticket_not_found',
                __('Ticket not found', 'environmental-live-chat'),
                array('status' => 404)
            );
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'ticket' => $ticket
        ));
    }
    
    public function get_ticket_replies($request) {
        $ticket_id = $request->get_param('ticket_id');
        
        global $wpdb;
        $replies = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}environmental_ticket_replies 
             WHERE ticket_id = %d ORDER BY created_at ASC",
            $ticket_id
        ));
        
        return rest_ensure_response(array(
            'success' => true,
            'replies' => $replies
        ));
    }
    
    public function reply_ticket($request) {
        $ticket_id = $request->get_param('ticket_id');
        $message = $request->get_param('message');
        $sender_type = $request->get_param('sender_type');
        
        $tickets = Environmental_Support_Tickets::get_instance();
        
        $sender_name = '';
        $sender_email = '';
        
        if ($sender_type === 'customer') {
            global $wpdb;
            $ticket = $wpdb->get_row($wpdb->prepare(
                "SELECT customer_name, customer_email FROM {$wpdb->prefix}environmental_support_tickets WHERE id = %d",
                $ticket_id
            ));
            $sender_name = $ticket->customer_name;
            $sender_email = $ticket->customer_email;
        } else {
            $current_user = wp_get_current_user();
            $sender_name = $current_user->display_name ?? 'Support Agent';
            $sender_email = $current_user->user_email ?? get_option('admin_email');
        }
        
        $reply_id = $tickets->add_reply($ticket_id, $message, $sender_type, $sender_name, $sender_email);
        
        if (!$reply_id) {
            return new WP_Error(
                'reply_failed',
                __('Failed to add reply', 'environmental-live-chat'),
                array('status' => 500)
            );
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'reply_id' => $reply_id,
            'message' => __('Reply added successfully', 'environmental-live-chat')
        ));
    }
    
    // FAQ endpoints
    public function search_faq($request) {
        $query = $request->get_param('query');
        $category = $request->get_param('category');
        $limit = $request->get_param('limit');
        
        $faq = Environmental_FAQ_Manager::get_instance();
        $results = $faq->search_faq($query, $category, $limit);
        
        return rest_ensure_response(array(
            'success' => true,
            'results' => $results
        ));
    }
    
    public function get_faq_categories($request) {
        $faq = Environmental_FAQ_Manager::get_instance();
        $categories = $faq->get_categories();
        
        return rest_ensure_response(array(
            'success' => true,
            'categories' => $categories
        ));
    }
    
    public function get_faq_item($request) {
        $faq_id = $request->get_param('faq_id');
        
        global $wpdb;
        $faq_item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}environmental_faq WHERE id = %d",
            $faq_id
        ));
        
        if (!$faq_item) {
            return new WP_Error(
                'faq_not_found',
                __('FAQ item not found', 'environmental-live-chat'),
                array('status' => 404)
            );
        }
        
        // Increment view count
        $wpdb->update(
            $wpdb->prefix . 'environmental_faq',
            array('view_count' => $faq_item->view_count + 1),
            array('id' => $faq_id),
            array('%d'),
            array('%d')
        );
        
        return rest_ensure_response(array(
            'success' => true,
            'faq' => $faq_item
        ));
    }
    
    public function rate_faq($request) {
        $faq_id = $request->get_param('faq_id');
        $helpful = $request->get_param('helpful');
        $user_ip = $request->get_param('user_ip') ?: $_SERVER['REMOTE_ADDR'];
        
        $faq = Environmental_FAQ_Manager::get_instance();
        $result = $faq->rate_faq($faq_id, $helpful, $user_ip);
        
        if (!$result) {
            return new WP_Error(
                'rating_failed',
                __('Failed to save rating', 'environmental-live-chat'),
                array('status' => 500)
            );
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Rating saved successfully', 'environmental-live-chat')
        ));
    }
    
    // Chatbot endpoint
    public function chatbot_query($request) {
        $message = $request->get_param('message');
        $session_id = $request->get_param('session_id');
        
        $chatbot = Environmental_Chatbot_System::get_instance();
        $response = $chatbot->process_message($message, $session_id);
        
        if (!$response) {
            return rest_ensure_response(array(
                'success' => false,
                'message' => __('Unable to process query', 'environmental-live-chat'),
                'escalate' => true
            ));
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'response' => $response['response'],
            'confidence' => $response['confidence'],
            'escalate' => $response['confidence'] < 70
        ));
    }
    
    // Analytics endpoints
    public function get_analytics_overview($request) {
        $analytics = Environmental_Analytics::get_instance();
        $overview = $analytics->get_overview_metrics();
        
        return rest_ensure_response(array(
            'success' => true,
            'analytics' => $overview
        ));
    }
    
    public function get_chat_analytics($request) {
        $date_from = $request->get_param('date_from');
        $date_to = $request->get_param('date_to');
        
        $analytics = Environmental_Analytics::get_instance();
        $chat_analytics = $analytics->get_chat_analytics($date_from, $date_to);
        
        return rest_ensure_response(array(
            'success' => true,
            'analytics' => $chat_analytics
        ));
    }
    
    // Operator endpoints
    public function get_operators($request) {
        global $wpdb;
        
        $operators = $wpdb->get_results(
            "SELECT id, name, email, department, status, max_concurrent_chats, current_chats, last_seen 
             FROM {$wpdb->prefix}environmental_chat_operators 
             ORDER BY name ASC"
        );
        
        return rest_ensure_response(array(
            'success' => true,
            'operators' => $operators
        ));
    }
    
    public function update_operator_status($request) {
        $operator_id = $request->get_param('operator_id');
        $status = $request->get_param('status');
        
        global $wpdb;
        
        $updated = $wpdb->update(
            $wpdb->prefix . 'environmental_chat_operators',
            array(
                'status' => $status,
                'last_seen' => current_time('mysql')
            ),
            array('id' => $operator_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($updated === false) {
            return new WP_Error(
                'update_failed',
                __('Failed to update operator status', 'environmental-live-chat'),
                array('status' => 500)
            );
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Operator status updated', 'environmental-live-chat')
        ));
    }
    
    // System status endpoint
    public function get_system_status($request) {
        $analytics = Environmental_Analytics::get_instance();
        $stats = $analytics->get_real_time_stats();
        
        $options = get_option('environmental_live_chat_options', array());
        
        return rest_ensure_response(array(
            'success' => true,
            'status' => array(
                'chat_enabled' => $options['enable_chat'] ?? true,
                'tickets_enabled' => $options['enable_tickets'] ?? true,
                'chatbot_enabled' => $options['enable_chatbot'] ?? true,
                'active_chats' => $stats['active_chats'],
                'online_operators' => $stats['online_operators'],
                'pending_tickets' => $stats['pending_tickets'],
                'system_healthy' => true
            )
        ));
    }
}
