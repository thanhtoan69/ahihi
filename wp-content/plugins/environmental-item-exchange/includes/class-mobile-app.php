<?php
/**
 * Mobile App Integration for Item Exchange Platform
 * 
 * Provides REST API endpoints and mobile-specific functionality
 * for iOS and Android applications
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Item_Exchange_Mobile_App {
    
    private static $instance = null;
    private $api_version = 'v1';
    private $namespace;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->namespace = 'environmental-exchange/' . $this->api_version;
        
        add_action('rest_api_init', array($this, 'register_api_routes'));
        add_action('init', array($this, 'add_cors_headers'));
        
        // Mobile-specific hooks
        add_filter('rest_authentication_errors', array($this, 'authenticate_mobile_request'));
        add_action('wp_ajax_ep_register_device', array($this, 'register_device'));
        add_action('wp_ajax_ep_upload_mobile_image', array($this, 'handle_mobile_image_upload'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_api_routes() {
        // Authentication routes
        register_rest_route($this->namespace, '/auth/login', array(
            'methods' => 'POST',
            'callback' => array($this, 'mobile_login'),
            'permission_callback' => '__return_true',
            'args' => array(
                'username' => array('required' => true, 'type' => 'string'),
                'password' => array('required' => true, 'type' => 'string'),
                'device_info' => array('required' => false, 'type' => 'object')
            )
        ));
        
        register_rest_route($this->namespace, '/auth/register', array(
            'methods' => 'POST',
            'callback' => array($this, 'mobile_register'),
            'permission_callback' => '__return_true',
            'args' => array(
                'username' => array('required' => true, 'type' => 'string'),
                'email' => array('required' => true, 'type' => 'string'),
                'password' => array('required' => true, 'type' => 'string'),
                'profile_data' => array('required' => false, 'type' => 'object')
            )
        ));
        
        register_rest_route($this->namespace, '/auth/refresh', array(
            'methods' => 'POST',
            'callback' => array($this, 'refresh_token'),
            'permission_callback' => array($this, 'check_mobile_auth')
        ));
        
        // Exchange routes
        register_rest_route($this->namespace, '/exchanges', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_exchanges'),
            'permission_callback' => '__return_true',
            'args' => array(
                'page' => array('default' => 1, 'type' => 'integer'),
                'per_page' => array('default' => 20, 'type' => 'integer'),
                'search' => array('type' => 'string'),
                'category' => array('type' => 'string'),
                'exchange_type' => array('type' => 'string'),
                'location' => array('type' => 'object'),
                'radius' => array('default' => 25, 'type' => 'integer')
            )
        ));
        
        register_rest_route($this->namespace, '/exchanges', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_exchange'),
            'permission_callback' => array($this, 'check_mobile_auth'),
            'args' => array(
                'title' => array('required' => true, 'type' => 'string'),
                'description' => array('required' => true, 'type' => 'string'),
                'exchange_type' => array('required' => true, 'type' => 'string'),
                'category' => array('required' => true, 'type' => 'string'),
                'item_details' => array('required' => true, 'type' => 'object'),
                'location' => array('required' => true, 'type' => 'object'),
                'images' => array('type' => 'array')
            )
        ));
        
        register_rest_route($this->namespace, '/exchanges/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_exchange'),
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array('required' => true, 'type' => 'integer')
            )
        ));
        
        register_rest_route($this->namespace, '/exchanges/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_exchange'),
            'permission_callback' => array($this, 'check_mobile_auth'),
            'args' => array(
                'id' => array('required' => true, 'type' => 'integer')
            )
        ));
        
        register_rest_route($this->namespace, '/exchanges/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_exchange'),
            'permission_callback' => array($this, 'check_mobile_auth'),
            'args' => array(
                'id' => array('required' => true, 'type' => 'integer')
            )
        ));
        
        // User profile routes
        register_rest_route($this->namespace, '/profile', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_user_profile'),
            'permission_callback' => array($this, 'check_mobile_auth')
        ));
        
        register_rest_route($this->namespace, '/profile', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_user_profile'),
            'permission_callback' => array($this, 'check_mobile_auth'),
            'args' => array(
                'display_name' => array('type' => 'string'),
                'bio' => array('type' => 'string'),
                'location' => array('type' => 'object'),
                'preferences' => array('type' => 'object')
            )
        ));
        
        // Messaging routes
        register_rest_route($this->namespace, '/messages', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_conversations'),
            'permission_callback' => array($this, 'check_mobile_auth')
        ));
        
        register_rest_route($this->namespace, '/messages/(?P<conversation_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_conversation_messages'),
            'permission_callback' => array($this, 'check_mobile_auth'),
            'args' => array(
                'conversation_id' => array('required' => true, 'type' => 'integer'),
                'page' => array('default' => 1, 'type' => 'integer'),
                'per_page' => array('default' => 50, 'type' => 'integer')
            )
        ));
        
        register_rest_route($this->namespace, '/messages', array(
            'methods' => 'POST',
            'callback' => array($this, 'send_message'),
            'permission_callback' => array($this, 'check_mobile_auth'),
            'args' => array(
                'recipient_id' => array('required' => true, 'type' => 'integer'),
                'exchange_id' => array('required' => true, 'type' => 'integer'),
                'message' => array('required' => true, 'type' => 'string'),
                'attachments' => array('type' => 'array')
            )
        ));
        
        // Matching routes
        register_rest_route($this->namespace, '/matches/(?P<exchange_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_exchange_matches'),
            'permission_callback' => array($this, 'check_mobile_auth'),
            'args' => array(
                'exchange_id' => array('required' => true, 'type' => 'integer'),
                'limit' => array('default' => 10, 'type' => 'integer')
            )
        ));
        
        // Notifications routes
        register_rest_route($this->namespace, '/notifications', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_notifications'),
            'permission_callback' => array($this, 'check_mobile_auth'),
            'args' => array(
                'page' => array('default' => 1, 'type' => 'integer'),
                'per_page' => array('default' => 20, 'type' => 'integer'),
                'unread_only' => array('default' => false, 'type' => 'boolean')
            )
        ));
        
        register_rest_route($this->namespace, '/notifications/(?P<id>\d+)/read', array(
            'methods' => 'POST',
            'callback' => array($this, 'mark_notification_read'),
            'permission_callback' => array($this, 'check_mobile_auth'),
            'args' => array(
                'id' => array('required' => true, 'type' => 'integer')
            )
        ));
        
        // Analytics routes (for user dashboard)
        register_rest_route($this->namespace, '/analytics/dashboard', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_user_analytics'),
            'permission_callback' => array($this, 'check_mobile_auth')
        ));
        
        // Categories and metadata
        register_rest_route($this->namespace, '/categories', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_categories'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route($this->namespace, '/metadata', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_app_metadata'),
            'permission_callback' => '__return_true'
        ));
    }
    
    /**
     * Add CORS headers for mobile app requests
     */
    public function add_cors_headers() {
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            $allowed_origins = array(
                'http://localhost:3000', // React Native development
                'https://your-app-domain.com' // Production mobile app
            );
            
            if (in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
                header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
                header('Access-Control-Allow-Credentials: true');
                header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
                header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
            }
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit(0);
        }
    }
    
    /**
     * Mobile authentication
     */
    public function mobile_login($request) {
        $username = sanitize_text_field($request['username']);
        $password = $request['password'];
        $device_info = $request['device_info'] ?? array();
        
        $user = wp_authenticate($username, $password);
        
        if (is_wp_error($user)) {
            return new WP_Error(
                'authentication_failed',
                __('Invalid username or password', 'environmental-item-exchange'),
                array('status' => 401)
            );
        }
        
        // Generate JWT token (simplified - use proper JWT library in production)
        $token = $this->generate_mobile_token($user->ID);
        
        // Store device info
        if (!empty($device_info)) {
            $this->store_device_info($user->ID, $device_info);
        }
        
        return array(
            'success' => true,
            'data' => array(
                'token' => $token,
                'user' => $this->format_user_data($user),
                'expires_in' => 7 * DAY_IN_SECONDS
            )
        );
    }
    
    /**
     * Mobile registration
     */
    public function mobile_register($request) {
        $username = sanitize_text_field($request['username']);
        $email = sanitize_email($request['email']);
        $password = $request['password'];
        $profile_data = $request['profile_data'] ?? array();
        
        // Validate input
        if (empty($username) || empty($email) || empty($password)) {
            return new WP_Error(
                'missing_fields',
                __('Username, email, and password are required', 'environmental-item-exchange'),
                array('status' => 400)
            );
        }
        
        if (username_exists($username) || email_exists($email)) {
            return new WP_Error(
                'user_exists',
                __('Username or email already exists', 'environmental-item-exchange'),
                array('status' => 409)
            );
        }
        
        // Create user
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        
        // Set user role
        $user = new WP_User($user_id);
        $user->set_role('eco_user');
        
        // Save profile data
        if (!empty($profile_data)) {
            foreach ($profile_data as $key => $value) {
                update_user_meta($user_id, '_mobile_' . sanitize_key($key), sanitize_text_field($value));
            }
        }
        
        // Generate token
        $token = $this->generate_mobile_token($user_id);
        
        return array(
            'success' => true,
            'data' => array(
                'token' => $token,
                'user' => $this->format_user_data($user),
                'expires_in' => 7 * DAY_IN_SECONDS
            )
        );
    }
    
    /**
     * Get exchanges with mobile optimization
     */
    public function get_exchanges($request) {
        $page = $request['page'];
        $per_page = min($request['per_page'], 50); // Limit for mobile
        $search = $request['search'] ?? '';
        $category = $request['category'] ?? '';
        $exchange_type = $request['exchange_type'] ?? '';
        $location = $request['location'] ?? array();
        $radius = $request['radius'];
        
        $args = array(
            'post_type' => 'item_exchange',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'meta_query' => array(
                array(
                    'key' => '_exchange_status',
                    'value' => 'active',
                    'compare' => '='
                )
            )
        );
        
        // Add search
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        // Add category filter
        if (!empty($category)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'exchange_type',
                    'field' => 'slug',
                    'terms' => $category
                )
            );
        }
        
        // Add exchange type filter
        if (!empty($exchange_type)) {
            $args['meta_query'][] = array(
                'key' => '_exchange_type',
                'value' => $exchange_type,
                'compare' => '='
            );
        }
        
        $query = new WP_Query($args);
        $exchanges = array();
        
        foreach ($query->posts as $post) {
            $exchange_data = $this->format_exchange_data($post);
            
            // Add distance if location provided
            if (!empty($location) && isset($location['lat']) && isset($location['lng'])) {
                $exchange_location = get_post_meta($post->ID, '_exchange_location', true);
                if ($exchange_location && isset($exchange_location['lat']) && isset($exchange_location['lng'])) {
                    $geolocation = Environmental_Item_Exchange_Geolocation::get_instance();
                    $distance = $geolocation->calculate_distance(
                        $location['lat'],
                        $location['lng'],
                        $exchange_location['lat'],
                        $exchange_location['lng']
                    );
                    
                    // Filter by radius
                    if ($distance <= $radius) {
                        $exchange_data['distance'] = round($distance, 1);
                        $exchanges[] = $exchange_data;
                    }
                } else {
                    $exchanges[] = $exchange_data;
                }
            } else {
                $exchanges[] = $exchange_data;
            }
        }
        
        // Sort by distance if location provided
        if (!empty($location)) {
            usort($exchanges, function($a, $b) {
                $dist_a = $a['distance'] ?? 999;
                $dist_b = $b['distance'] ?? 999;
                return $dist_a <=> $dist_b;
            });
        }
        
        return array(
            'success' => true,
            'data' => array(
                'exchanges' => $exchanges,
                'pagination' => array(
                    'current_page' => $page,
                    'total_pages' => $query->max_num_pages,
                    'total_items' => $query->found_posts,
                    'per_page' => $per_page
                )
            )
        );
    }
    
    /**
     * Create new exchange via mobile
     */
    public function create_exchange($request) {
        $current_user = wp_get_current_user();
        
        $post_data = array(
            'post_title' => sanitize_text_field($request['title']),
            'post_content' => sanitize_textarea_field($request['description']),
            'post_type' => 'item_exchange',
            'post_status' => 'publish',
            'post_author' => $current_user->ID
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        // Set category
        wp_set_post_terms($post_id, $request['category'], 'exchange_type');
        
        // Set exchange meta
        update_post_meta($post_id, '_exchange_type', sanitize_text_field($request['exchange_type']));
        update_post_meta($post_id, '_exchange_status', 'active');
        update_post_meta($post_id, '_created_via', 'mobile_app');
        
        // Set item details
        $item_details = $request['item_details'];
        foreach ($item_details as $key => $value) {
            update_post_meta($post_id, '_' . sanitize_key($key), sanitize_text_field($value));
        }
        
        // Set location
        if (!empty($request['location'])) {
            update_post_meta($post_id, '_exchange_location', $request['location']);
        }
        
        // Handle images
        if (!empty($request['images'])) {
            $this->process_mobile_images($post_id, $request['images']);
        }
        
        // Trigger new exchange action
        do_action('ep_new_exchange_posted', $post_id, $current_user->ID);
        
        return array(
            'success' => true,
            'data' => array(
                'exchange_id' => $post_id,
                'exchange' => $this->format_exchange_data(get_post($post_id))
            )
        );
    }
    
    /**
     * Get user profile for mobile
     */
    public function get_user_profile($request) {
        $current_user = wp_get_current_user();
        
        $profile_data = array(
            'id' => $current_user->ID,
            'username' => $current_user->user_login,
            'display_name' => $current_user->display_name,
            'email' => $current_user->user_email,
            'avatar_url' => get_avatar_url($current_user->ID, array('size' => 150)),
            'member_since' => $current_user->user_registered,
            'bio' => get_user_meta($current_user->ID, '_user_bio', true),
            'location' => get_user_meta($current_user->ID, '_user_location', true),
            'trust_score' => intval(get_user_meta($current_user->ID, '_trust_score', true) ?: 50),
            'trust_level' => get_user_meta($current_user->ID, '_trust_level', true) ?: 'new',
            'exchange_rating' => floatval(get_user_meta($current_user->ID, '_exchange_rating', true) ?: 3.0),
            'total_exchanges' => $this->get_user_exchange_count($current_user->ID),
            'successful_exchanges' => $this->get_user_successful_exchanges($current_user->ID),
            'eco_points' => intval(get_user_meta($current_user->ID, '_eco_points', true) ?: 0),
            'carbon_saved' => floatval(get_user_meta($current_user->ID, '_carbon_saved', true) ?: 0.0),
            'preferences' => get_user_meta($current_user->ID, '_mobile_preferences', true) ?: array()
        );
        
        return array(
            'success' => true,
            'data' => $profile_data
        );
    }
    
    /**
     * Get conversations for mobile
     */
    public function get_conversations($request) {
        $messaging = Environmental_Item_Exchange_Messaging_System::get_instance();
        $conversations = $messaging->get_user_conversations(get_current_user_id());
        
        // Format for mobile
        $mobile_conversations = array();
        foreach ($conversations as $conversation) {
            $last_message = $messaging->get_conversation_messages($conversation->conversation_id, 1);
            
            $mobile_conversations[] = array(
                'id' => $conversation->conversation_id,
                'exchange_id' => $conversation->exchange_id,
                'other_user' => $this->format_user_data(get_userdata($conversation->other_user_id)),
                'last_message' => !empty($last_message) ? array(
                    'message' => $last_message[0]->message_content,
                    'sent_at' => $last_message[0]->sent_at,
                    'is_read' => (bool) $last_message[0]->is_read
                ) : null,
                'unread_count' => $conversation->unread_count,
                'created_at' => $conversation->created_at
            );
        }
        
        return array(
            'success' => true,
            'data' => $mobile_conversations
        );
    }
    
    /**
     * Get app metadata for mobile
     */
    public function get_app_metadata($request) {
        return array(
            'success' => true,
            'data' => array(
                'app_version' => get_option('ep_mobile_app_version', '1.0.0'),
                'api_version' => $this->api_version,
                'supported_image_formats' => array('jpg', 'jpeg', 'png', 'webp'),
                'max_image_size' => 5 * 1024 * 1024, // 5MB
                'max_images_per_post' => 10,
                'pagination_limits' => array(
                    'exchanges' => 50,
                    'messages' => 100,
                    'notifications' => 50
                ),
                'features' => array(
                    'push_notifications' => true,
                    'offline_mode' => true,
                    'geolocation' => true,
                    'image_upload' => true,
                    'real_time_messaging' => false // Future feature
                )
            )
        );
    }
    
    /**
     * Format exchange data for mobile consumption
     */
    private function format_exchange_data($post) {
        $post_meta = get_post_meta($post->ID);
        $categories = wp_get_post_terms($post->ID, 'exchange_type');
        $author = get_userdata($post->post_author);
        
        // Get images
        $images = array();
        $gallery = get_post_meta($post->ID, '_item_images', true);
        if ($gallery && is_array($gallery)) {
            foreach ($gallery as $image_id) {
                $image_url = wp_get_attachment_image_url($image_id, 'medium');
                $image_url_large = wp_get_attachment_image_url($image_id, 'large');
                if ($image_url) {
                    $images[] = array(
                        'id' => $image_id,
                        'thumbnail' => $image_url,
                        'full_size' => $image_url_large,
                        'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true)
                    );
                }
            }
        }
        
        return array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'description' => $post->post_content,
            'excerpt' => wp_trim_words($post->post_content, 30),
            'exchange_type' => $post_meta['_exchange_type'][0] ?? 'exchange',
            'status' => $post_meta['_exchange_status'][0] ?? 'active',
            'condition' => $post_meta['_item_condition'][0] ?? 'good',
            'estimated_value' => floatval($post_meta['_item_estimated_value'][0] ?? 0),
            'eco_points' => intval($post_meta['_eco_points_reward'][0] ?? 0),
            'carbon_saved' => floatval($post_meta['_carbon_footprint_saved'][0] ?? 0),
            'is_urgent' => (bool) ($post_meta['_is_urgent'][0] ?? false),
            'categories' => array_map(function($term) {
                return array(
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug
                );
            }, $categories),
            'location' => json_decode($post_meta['_exchange_location'][0] ?? '{}', true),
            'images' => $images,
            'author' => array(
                'id' => $author->ID,
                'display_name' => $author->display_name,
                'avatar_url' => get_avatar_url($author->ID, array('size' => 50)),
                'trust_score' => intval(get_user_meta($author->ID, '_trust_score', true) ?: 50),
                'rating' => floatval(get_user_meta($author->ID, '_exchange_rating', true) ?: 3.0)
            ),
            'created_at' => $post->post_date,
            'updated_at' => $post->post_modified,
            'view_count' => intval($post_meta['_view_count'][0] ?? 0),
            'favorite_count' => intval($post_meta['_favorite_count'][0] ?? 0)
        );
    }
    
    /**
     * Format user data for mobile
     */
    private function format_user_data($user) {
        if (!$user || is_wp_error($user)) {
            return null;
        }
        
        return array(
            'id' => $user->ID,
            'username' => $user->user_login,
            'display_name' => $user->display_name,
            'email' => $user->user_email,
            'avatar_url' => get_avatar_url($user->ID, array('size' => 50)),
            'trust_score' => intval(get_user_meta($user->ID, '_trust_score', true) ?: 50),
            'trust_level' => get_user_meta($user->ID, '_trust_level', true) ?: 'new',
            'rating' => floatval(get_user_meta($user->ID, '_exchange_rating', true) ?: 3.0),
            'member_since' => $user->user_registered
        );
    }
    
    /**
     * Generate mobile authentication token
     */
    private function generate_mobile_token($user_id) {
        $payload = array(
            'user_id' => $user_id,
            'issued_at' => time(),
            'expires_at' => time() + (7 * DAY_IN_SECONDS)
        );
        
        // In production, use proper JWT library
        $token = base64_encode(json_encode($payload));
        
        // Store token for validation
        update_user_meta($user_id, '_mobile_token', $token);
        update_user_meta($user_id, '_mobile_token_expires', $payload['expires_at']);
        
        return $token;
    }
    
    /**
     * Check mobile authentication
     */
    public function check_mobile_auth($request) {
        $auth_header = $request->get_header('Authorization');
        
        if (!$auth_header) {
            return new WP_Error(
                'no_auth_header',
                __('Authorization header is required', 'environmental-item-exchange'),
                array('status' => 401)
            );
        }
        
        $token = str_replace('Bearer ', '', $auth_header);
        $payload = json_decode(base64_decode($token), true);
        
        if (!$payload || !isset($payload['user_id'])) {
            return new WP_Error(
                'invalid_token',
                __('Invalid authentication token', 'environmental-item-exchange'),
                array('status' => 401)
            );
        }
        
        $user_id = $payload['user_id'];
        $stored_token = get_user_meta($user_id, '_mobile_token', true);
        $expires_at = get_user_meta($user_id, '_mobile_token_expires', true);
        
        if ($stored_token !== $token || time() > $expires_at) {
            return new WP_Error(
                'expired_token',
                __('Authentication token has expired', 'environmental-item-exchange'),
                array('status' => 401)
            );
        }
        
        // Set current user for the request
        wp_set_current_user($user_id);
        
        return true;
    }
    
    /**
     * Store device information
     */
    private function store_device_info($user_id, $device_info) {
        $devices = get_user_meta($user_id, '_mobile_devices', true) ?: array();
        
        $device_id = $device_info['device_id'] ?? uniqid();
        $devices[$device_id] = array(
            'device_id' => $device_id,
            'platform' => sanitize_text_field($device_info['platform'] ?? 'unknown'),
            'version' => sanitize_text_field($device_info['version'] ?? 'unknown'),
            'app_version' => sanitize_text_field($device_info['app_version'] ?? 'unknown'),
            'push_token' => sanitize_text_field($device_info['push_token'] ?? ''),
            'last_active' => current_time('mysql')
        );
        
        update_user_meta($user_id, '_mobile_devices', $devices);
    }
    
    /**
     * Process mobile uploaded images
     */
    private function process_mobile_images($post_id, $images) {
        $attachment_ids = array();
        
        foreach ($images as $image_data) {
            if (isset($image_data['base64'])) {
                // Handle base64 encoded images
                $upload = $this->handle_base64_upload($image_data['base64'], $image_data['filename'] ?? 'mobile-upload.jpg');
                if (!is_wp_error($upload)) {
                    $attachment_ids[] = $upload['attachment_id'];
                }
            } elseif (isset($image_data['url'])) {
                // Handle image URLs (from camera or gallery)
                $upload = $this->handle_url_upload($image_data['url'], $post_id);
                if (!is_wp_error($upload)) {
                    $attachment_ids[] = $upload['attachment_id'];
                }
            }
        }
        
        if (!empty($attachment_ids)) {
            update_post_meta($post_id, '_item_images', $attachment_ids);
            
            // Set featured image to first uploaded image
            set_post_thumbnail($post_id, $attachment_ids[0]);
        }
    }
    
    /**
     * Handle base64 image upload
     */
    private function handle_base64_upload($base64_data, $filename) {
        // Remove data URL prefix if present
        if (strpos($base64_data, ',') !== false) {
            list($type, $base64_data) = explode(',', $base64_data);
        }
        
        $image_data = base64_decode($base64_data);
        if ($image_data === false) {
            return new WP_Error('invalid_image', 'Invalid base64 image data');
        }
        
        $upload_dir = wp_upload_dir();
        $filename = sanitize_file_name($filename);
        $file_path = $upload_dir['path'] . '/' . $filename;
        
        // Save file
        file_put_contents($file_path, $image_data);
        
        // Create attachment
        $attachment = array(
            'guid' => $upload_dir['url'] . '/' . basename($file_path),
            'post_mime_type' => wp_check_filetype($file_path)['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        $attachment_id = wp_insert_attachment($attachment, $file_path);
        
        if (!is_wp_error($attachment_id)) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);
            wp_update_attachment_metadata($attachment_id, $attachment_data);
            
            return array('attachment_id' => $attachment_id, 'url' => wp_get_attachment_url($attachment_id));
        }
        
        return $attachment_id;
    }
    
    /**
     * Get user exchange statistics
     */
    private function get_user_exchange_count($user_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->posts}
            WHERE post_author = %d AND post_type = 'item_exchange' AND post_status = 'publish'
        ", $user_id));
    }
    
    private function get_user_successful_exchanges($user_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_author = %d 
            AND p.post_type = 'item_exchange' 
            AND pm.meta_key = '_exchange_status' 
            AND pm.meta_value = 'completed'
        ", $user_id));
    }
    
    /**
     * Authentication error handler
     */
    public function authenticate_mobile_request($error) {
        // Allow mobile API endpoints to bypass standard WordPress authentication
        if (strpos($_SERVER['REQUEST_URI'], '/wp-json/environmental-exchange/') !== false) {
            return null;
        }
        return $error;
    }
}

// Initialize mobile app integration
Environmental_Item_Exchange_Mobile_App::get_instance();
