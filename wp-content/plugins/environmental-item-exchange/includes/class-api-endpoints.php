<?php
/**
 * API Endpoints Class for Environmental Item Exchange
 * 
 * Handles REST API endpoints for external integrations and mobile app support
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Environmental_Item_Exchange_API_Endpoints {
    
    private $db_manager;
    private $geolocation;
    private $messaging;
    private $rating_system;
    private $matching_engine;
    private $notifications;
    private $mobile_app;
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('init', array($this, 'init_dependencies'));
    }
    
    public function init_dependencies() {
        $this->db_manager = new Environmental_Item_Exchange_Database_Manager();
        $this->geolocation = new Environmental_Item_Exchange_Geolocation();
        $this->messaging = new Environmental_Item_Exchange_Messaging_System();
        $this->rating_system = new Environmental_Item_Exchange_Rating_System();
        $this->matching_engine = new Environmental_Item_Exchange_Matching_Engine();
        $this->notifications = new Environmental_Item_Exchange_Notifications();
        $this->mobile_app = new Environmental_Item_Exchange_Mobile_App();
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Authentication endpoints
        register_rest_route('environmental-exchange/v1', '/auth/login', array(
            'methods' => 'POST',
            'callback' => array($this, 'auth_login'),
            'permission_callback' => '__return_true',
            'args' => array(
                'username' => array('required' => true, 'type' => 'string'),
                'password' => array('required' => true, 'type' => 'string')
            )
        ));
        
        register_rest_route('environmental-exchange/v1', '/auth/register', array(
            'methods' => 'POST',
            'callback' => array($this, 'auth_register'),
            'permission_callback' => '__return_true',
            'args' => array(
                'username' => array('required' => true, 'type' => 'string'),
                'email' => array('required' => true, 'type' => 'string'),
                'password' => array('required' => true, 'type' => 'string')
            )
        ));
        
        // Exchange endpoints
        register_rest_route('environmental-exchange/v1', '/exchanges', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_exchanges'),
            'permission_callback' => array($this, 'check_authentication')
        ));
        
        register_rest_route('environmental-exchange/v1', '/exchanges', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_exchange'),
            'permission_callback' => array($this, 'check_authentication')
        ));
        
        register_rest_route('environmental-exchange/v1', '/exchanges/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_exchange'),
            'permission_callback' => array($this, 'check_authentication')
        ));
        
        register_rest_route('environmental-exchange/v1', '/exchanges/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_exchange'),
            'permission_callback' => array($this, 'check_authentication')
        ));
        
        register_rest_route('environmental-exchange/v1', '/exchanges/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_exchange'),
            'permission_callback' => array($this, 'check_authentication')
        ));
        
        // Search and filter endpoints
        register_rest_route('environmental-exchange/v1', '/search', array(
            'methods' => 'GET',
            'callback' => array($this, 'search_exchanges'),
            'permission_callback' => array($this, 'check_authentication'),
            'args' => array(
                'query' => array('type' => 'string'),
                'category' => array('type' => 'string'),
                'location' => array('type' => 'string'),
                'radius' => array('type' => 'number'),
                'exchange_type' => array('type' => 'string')
            )
        ));
        
        // Matching endpoints
        register_rest_route('environmental-exchange/v1', '/matches', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_matches'),
            'permission_callback' => array($this, 'check_authentication')
        ));
        
        register_rest_route('environmental-exchange/v1', '/matches/(?P<id>\d+)/accept', array(
            'methods' => 'POST',
            'callback' => array($this, 'accept_match'),
            'permission_callback' => array($this, 'check_authentication')
        ));
        
        register_rest_route('environmental-exchange/v1', '/matches/(?P<id>\d+)/reject', array(
            'methods' => 'POST',
            'callback' => array($this, 'reject_match'),
            'permission_callback' => array($this, 'check_authentication')
        ));
        
        // Messaging endpoints
        register_rest_route('environmental-exchange/v1', '/conversations', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_conversations'),
            'permission_callback' => array($this, 'check_authentication')
        ));
        
        register_rest_route('environmental-exchange/v1', '/conversations/(?P<id>\d+)/messages', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_messages'),
            'permission_callback' => array($this, 'check_authentication')
        ));
        
        register_rest_route('environmental-exchange/v1', '/conversations/(?P<id>\d+)/messages', array(
            'methods' => 'POST',
            'callback' => array($this, 'send_message'),
            'permission_callback' => array($this, 'check_authentication')
        ));
        
        // Rating endpoints
        register_rest_route('environmental-exchange/v1', '/ratings', array(
            'methods' => 'POST',
            'callback' => array($this, 'submit_rating'),
            'permission_callback' => array($this, 'check_authentication')
        ));
        
        register_rest_route('environmental-exchange/v1', '/users/(?P<id>\d+)/ratings', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_user_ratings'),
            'permission_callback' => array($this, 'check_authentication')
        ));
        
        // User profile endpoints
        register_rest_route('environmental-exchange/v1', '/profile', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_profile'),
            'permission_callback' => array($this, 'check_authentication')
        ));
        
        register_rest_route('environmental-exchange/v1', '/profile', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_profile'),
            'permission_callback' => array($this, 'check_authentication')
        ));
        
        // Notification endpoints
        register_rest_route('environmental-exchange/v1', '/notifications', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_notifications'),
            'permission_callback' => array($this, 'check_authentication')
        ));
        
        register_rest_route('environmental-exchange/v1', '/notifications/(?P<id>\d+)/read', array(
            'methods' => 'POST',
            'callback' => array($this, 'mark_notification_read'),
            'permission_callback' => array($this, 'check_authentication')
        ));
        
        // Analytics endpoints
        register_rest_route('environmental-exchange/v1', '/analytics/dashboard', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_analytics_dashboard'),
            'permission_callback' => array($this, 'check_authentication')
        ));
        
        // Categories endpoint
        register_rest_route('environmental-exchange/v1', '/categories', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_categories'),
            'permission_callback' => array($this, 'check_authentication')
        ));
        
        // Webhook endpoints for external integrations
        register_rest_route('environmental-exchange/v1', '/webhooks/exchange-created', array(
            'methods' => 'POST',
            'callback' => array($this, 'webhook_exchange_created'),
            'permission_callback' => array($this, 'verify_webhook_signature')
        ));
    }
    
    /**
     * Authentication methods
     */
    public function auth_login($request) {
        $username = sanitize_text_field($request->get_param('username'));
        $password = $request->get_param('password');
        
        $user = wp_authenticate($username, $password);
        
        if (is_wp_error($user)) {
            return new WP_Error('authentication_failed', 'Invalid credentials', array('status' => 401));
        }
        
        // Generate API token
        $token = $this->generate_api_token($user->ID);
        
        return array(
            'success' => true,
            'token' => $token,
            'user' => array(
                'id' => $user->ID,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'display_name' => $user->display_name
            )
        );
    }
    
    public function auth_register($request) {
        $username = sanitize_text_field($request->get_param('username'));
        $email = sanitize_email($request->get_param('email'));
        $password = $request->get_param('password');
        
        // Create user
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        
        // Generate API token
        $token = $this->generate_api_token($user_id);
        
        $user = get_user_by('id', $user_id);
        
        return array(
            'success' => true,
            'token' => $token,
            'user' => array(
                'id' => $user->ID,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'display_name' => $user->display_name
            )
        );
    }
    
    /**
     * Exchange CRUD operations
     */
    public function get_exchanges($request) {
        $args = array(
            'post_type' => 'item_exchange',
            'post_status' => 'publish',
            'posts_per_page' => $request->get_param('per_page') ?: 20,
            'paged' => $request->get_param('page') ?: 1
        );
        
        $exchanges = get_posts($args);
        $formatted_exchanges = array();
        
        foreach ($exchanges as $exchange) {
            $formatted_exchanges[] = $this->format_exchange_for_api($exchange);
        }
        
        return array(
            'exchanges' => $formatted_exchanges,
            'total' => wp_count_posts('item_exchange')->publish,
            'page' => intval($args['paged']),
            'per_page' => intval($args['posts_per_page'])
        );
    }
    
    public function create_exchange($request) {
        $user_id = $this->get_current_user_id();
        if (!$user_id) {
            return new WP_Error('unauthorized', 'Authentication required', array('status' => 401));
        }
        
        $title = sanitize_text_field($request->get_param('title'));
        $description = wp_kses_post($request->get_param('description'));
        $category = sanitize_text_field($request->get_param('category'));
        $exchange_type = sanitize_text_field($request->get_param('exchange_type'));
        
        $post_data = array(
            'post_title' => $title,
            'post_content' => $description,
            'post_type' => 'item_exchange',
            'post_status' => 'publish',
            'post_author' => $user_id
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        // Set taxonomy terms
        if ($category) {
            wp_set_object_terms($post_id, $category, 'exchange_category');
        }
        if ($exchange_type) {
            wp_set_object_terms($post_id, $exchange_type, 'exchange_type');
        }
        
        // Update custom fields
        $custom_fields = $request->get_param('custom_fields');
        if ($custom_fields && is_array($custom_fields)) {
            foreach ($custom_fields as $key => $value) {
                update_post_meta($post_id, $key, sanitize_text_field($value));
            }
        }
        
        $exchange = get_post($post_id);
        return array(
            'success' => true,
            'exchange' => $this->format_exchange_for_api($exchange)
        );
    }
    
    public function get_exchange($request) {
        $id = intval($request['id']);
        $exchange = get_post($id);
        
        if (!$exchange || $exchange->post_type !== 'item_exchange') {
            return new WP_Error('not_found', 'Exchange not found', array('status' => 404));
        }
        
        return array(
            'exchange' => $this->format_exchange_for_api($exchange)
        );
    }
    
    /**
     * Search functionality
     */
    public function search_exchanges($request) {
        $query = sanitize_text_field($request->get_param('query'));
        $category = sanitize_text_field($request->get_param('category'));
        $location = sanitize_text_field($request->get_param('location'));
        $radius = floatval($request->get_param('radius')) ?: 25;
        $exchange_type = sanitize_text_field($request->get_param('exchange_type'));
        
        $args = array(
            'post_type' => 'item_exchange',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'meta_query' => array(),
            'tax_query' => array()
        );
        
        // Text search
        if ($query) {
            $args['s'] = $query;
        }
        
        // Category filter
        if ($category) {
            $args['tax_query'][] = array(
                'taxonomy' => 'exchange_category',
                'field' => 'slug',
                'terms' => $category
            );
        }
        
        // Exchange type filter
        if ($exchange_type) {
            $args['tax_query'][] = array(
                'taxonomy' => 'exchange_type',
                'field' => 'slug',
                'terms' => $exchange_type
            );
        }
        
        // Location-based search
        if ($location) {
            $coordinates = $this->geolocation->geocode_address($location);
            if ($coordinates) {
                $nearby_exchanges = $this->geolocation->find_nearby_exchanges(
                    $coordinates['lat'], 
                    $coordinates['lng'], 
                    $radius
                );
                
                if (!empty($nearby_exchanges)) {
                    $args['post__in'] = $nearby_exchanges;
                } else {
                    // No nearby exchanges found
                    return array('exchanges' => array(), 'total' => 0);
                }
            }
        }
        
        $exchanges = get_posts($args);
        $formatted_exchanges = array();
        
        foreach ($exchanges as $exchange) {
            $formatted_exchanges[] = $this->format_exchange_for_api($exchange);
        }
        
        return array(
            'exchanges' => $formatted_exchanges,
            'total' => count($formatted_exchanges)
        );
    }
    
    /**
     * Matching system integration
     */
    public function get_matches($request) {
        $user_id = $this->get_current_user_id();
        if (!$user_id) {
            return new WP_Error('unauthorized', 'Authentication required', array('status' => 401));
        }
        
        $matches = $this->matching_engine->get_user_matches($user_id);
        
        return array(
            'matches' => $matches,
            'total' => count($matches)
        );
    }
    
    public function accept_match($request) {
        $user_id = $this->get_current_user_id();
        $match_id = intval($request['id']);
        
        $result = $this->matching_engine->accept_match($match_id, $user_id);
        
        if ($result) {
            return array('success' => true, 'message' => 'Match accepted');
        } else {
            return new WP_Error('match_error', 'Failed to accept match', array('status' => 400));
        }
    }
    
    /**
     * Messaging integration
     */
    public function get_conversations($request) {
        $user_id = $this->get_current_user_id();
        if (!$user_id) {
            return new WP_Error('unauthorized', 'Authentication required', array('status' => 401));
        }
        
        $conversations = $this->messaging->get_user_conversations($user_id);
        
        return array(
            'conversations' => $conversations,
            'total' => count($conversations)
        );
    }
    
    public function send_message($request) {
        $user_id = $this->get_current_user_id();
        $conversation_id = intval($request['id']);
        $message = wp_kses_post($request->get_param('message'));
        
        $result = $this->messaging->send_message($conversation_id, $user_id, $message);
        
        if ($result) {
            return array('success' => true, 'message_id' => $result);
        } else {
            return new WP_Error('message_error', 'Failed to send message', array('status' => 400));
        }
    }
    
    /**
     * Rating system integration
     */
    public function submit_rating($request) {
        $user_id = $this->get_current_user_id();
        $rated_user_id = intval($request->get_param('rated_user_id'));
        $exchange_id = intval($request->get_param('exchange_id'));
        $ratings = $request->get_param('ratings');
        $comment = wp_kses_post($request->get_param('comment'));
        
        $result = $this->rating_system->submit_rating($user_id, $rated_user_id, $exchange_id, $ratings, $comment);
        
        if ($result) {
            return array('success' => true, 'rating_id' => $result);
        } else {
            return new WP_Error('rating_error', 'Failed to submit rating', array('status' => 400));
        }
    }
    
    /**
     * User profile management
     */
    public function get_profile($request) {
        $user_id = $this->get_current_user_id();
        if (!$user_id) {
            return new WP_Error('unauthorized', 'Authentication required', array('status' => 401));
        }
        
        $user = get_user_by('id', $user_id);
        $profile_data = array(
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'first_name' => get_user_meta($user_id, 'first_name', true),
            'last_name' => get_user_meta($user_id, 'last_name', true),
            'bio' => get_user_meta($user_id, 'description', true),
            'location' => get_user_meta($user_id, 'location', true),
            'trust_score' => $this->rating_system->get_user_trust_score($user_id),
            'exchange_count' => $this->db_manager->get_user_exchange_count($user_id),
            'member_since' => $user->user_registered
        );
        
        return $profile_data;
    }
    
    /**
     * Categories
     */
    public function get_categories($request) {
        $categories = get_terms(array(
            'taxonomy' => 'exchange_category',
            'hide_empty' => false
        ));
        
        $formatted_categories = array();
        foreach ($categories as $category) {
            $formatted_categories[] = array(
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'count' => $category->count
            );
        }
        
        return array('categories' => $formatted_categories);
    }
    
    /**
     * Analytics dashboard
     */
    public function get_analytics_dashboard($request) {
        $user_id = $this->get_current_user_id();
        if (!$user_id) {
            return new WP_Error('unauthorized', 'Authentication required', array('status' => 401));
        }
        
        $analytics = new Environmental_Item_Exchange_Analytics();
        
        return array(
            'dashboard_data' => $analytics->get_user_dashboard_data($user_id),
            'exchange_stats' => $analytics->get_user_exchange_statistics($user_id),
            'environmental_impact' => $analytics->get_user_environmental_impact($user_id)
        );
    }
    
    /**
     * Helper methods
     */
    private function check_authentication($request) {
        $token = $request->get_header('Authorization');
        if (!$token) {
            return false;
        }
        
        // Remove 'Bearer ' prefix if present
        $token = str_replace('Bearer ', '', $token);
        
        return $this->verify_api_token($token);
    }
    
    private function generate_api_token($user_id) {
        $token = wp_generate_password(32, false);
        update_user_meta($user_id, 'api_token', $token);
        update_user_meta($user_id, 'api_token_expires', time() + (30 * DAY_IN_SECONDS)); // 30 days
        return $token;
    }
    
    private function verify_api_token($token) {
        $users = get_users(array(
            'meta_key' => 'api_token',
            'meta_value' => $token,
            'number' => 1
        ));
        
        if (empty($users)) {
            return false;
        }
        
        $user = $users[0];
        $expires = get_user_meta($user->ID, 'api_token_expires', true);
        
        if ($expires && $expires < time()) {
            return false; // Token expired
        }
        
        wp_set_current_user($user->ID);
        return true;
    }
    
    private function get_current_user_id() {
        return get_current_user_id();
    }
    
    private function format_exchange_for_api($exchange) {
        return array(
            'id' => $exchange->ID,
            'title' => $exchange->post_title,
            'description' => $exchange->post_content,
            'author' => array(
                'id' => $exchange->post_author,
                'name' => get_the_author_meta('display_name', $exchange->post_author)
            ),
            'date_created' => $exchange->post_date,
            'status' => $exchange->post_status,
            'categories' => wp_get_post_terms($exchange->ID, 'exchange_category'),
            'exchange_type' => wp_get_post_terms($exchange->ID, 'exchange_type'),
            'custom_fields' => get_post_meta($exchange->ID),
            'images' => $this->get_exchange_images($exchange->ID),
            'location' => get_post_meta($exchange->ID, 'location', true)
        );
    }
    
    private function get_exchange_images($post_id) {
        $images = array();
        $gallery = get_post_meta($post_id, 'gallery', true);
        
        if ($gallery && is_array($gallery)) {
            foreach ($gallery as $image_id) {
                $image_url = wp_get_attachment_url($image_id);
                if ($image_url) {
                    $images[] = array(
                        'id' => $image_id,
                        'url' => $image_url,
                        'thumbnail' => wp_get_attachment_thumb_url($image_id)
                    );
                }
            }
        }
        
        return $images;
    }
    
    private function verify_webhook_signature($request) {
        // Implement webhook signature verification
        $signature = $request->get_header('X-Webhook-Signature');
        $payload = $request->get_body();
        
        // Verify signature logic here
        return true; // Simplified for now
    }
    
    public function webhook_exchange_created($request) {
        // Handle webhook for external integrations
        $data = $request->get_json_params();
        
        // Process external exchange creation
        // Log webhook received
        error_log('Exchange created webhook received: ' . json_encode($data));
        
        return array('success' => true, 'message' => 'Webhook processed');
    }
}

// Initialize the API endpoints
new Environmental_Item_Exchange_API_Endpoints();
