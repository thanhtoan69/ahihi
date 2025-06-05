<?php
/**
 * Content API Endpoints
 *
 * @package EnvironmentalMobileAPI
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Environmental_Mobile_API_Content_Endpoints
 */
class Environmental_Mobile_API_Content_Endpoints {
    
    /**
     * Auth manager instance
     */
    private $auth_manager;
    
    /**
     * Cache manager instance
     */
    private $cache_manager;
    
    /**
     * Security instance
     */
    private $security;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->auth_manager = new Environmental_Mobile_API_Auth_Manager();
        $this->cache_manager = new Environmental_Mobile_API_Cache_Manager();
        $this->security = new Environmental_Mobile_API_Security();
        
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        $namespace = 'environmental-mobile-api/v1';
        
        // Get posts/content
        register_rest_route($namespace, '/content/posts', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_posts'),
            'permission_callback' => '__return_true',
            'args' => array(
                'page' => array(
                    'default' => 1,
                    'type' => 'integer',
                    'minimum' => 1,
                ),
                'per_page' => array(
                    'default' => 10,
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 50,
                ),
                'post_type' => array(
                    'default' => 'post',
                    'type' => 'string',
                ),
                'category' => array(
                    'type' => 'string',
                ),
                'search' => array(
                    'type' => 'string',
                ),
                'order' => array(
                    'default' => 'desc',
                    'enum' => array('asc', 'desc'),
                ),
                'orderby' => array(
                    'default' => 'date',
                    'enum' => array('date', 'title', 'popularity', 'relevance'),
                ),
            ),
        ));
        
        // Get single post
        register_rest_route($namespace, '/content/posts/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_post'),
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                ),
            ),
        ));
        
        // Get petitions
        register_rest_route($namespace, '/content/petitions', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_petitions'),
            'permission_callback' => '__return_true',
            'args' => array(
                'page' => array(
                    'default' => 1,
                    'type' => 'integer',
                    'minimum' => 1,
                ),
                'per_page' => array(
                    'default' => 10,
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 50,
                ),
                'status' => array(
                    'default' => 'active',
                    'enum' => array('active', 'completed', 'all'),
                ),
                'category' => array(
                    'type' => 'string',
                ),
                'search' => array(
                    'type' => 'string',
                ),
            ),
        ));
        
        // Get single petition
        register_rest_route($namespace, '/content/petitions/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_petition'),
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                ),
            ),
        ));
        
        // Sign petition
        register_rest_route($namespace, '/content/petitions/(?P<id>\d+)/sign', array(
            'methods' => 'POST',
            'callback' => array($this, 'sign_petition'),
            'permission_callback' => array($this, 'check_authentication'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                ),
                'comment' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ),
                'anonymous' => array(
                    'type' => 'boolean',
                    'default' => false,
                ),
            ),
        ));
        
        // Get events
        register_rest_route($namespace, '/content/events', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_events'),
            'permission_callback' => '__return_true',
            'args' => array(
                'page' => array(
                    'default' => 1,
                    'type' => 'integer',
                    'minimum' => 1,
                ),
                'per_page' => array(
                    'default' => 10,
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 50,
                ),
                'time_filter' => array(
                    'default' => 'upcoming',
                    'enum' => array('upcoming', 'past', 'all'),
                ),
                'location' => array(
                    'type' => 'string',
                ),
            ),
        ));
        
        // Get single event
        register_rest_route($namespace, '/content/events/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_event'),
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                ),
            ),
        ));
        
        // RSVP to event
        register_rest_route($namespace, '/content/events/(?P<id>\d+)/rsvp', array(
            'methods' => 'POST',
            'callback' => array($this, 'rsvp_event'),
            'permission_callback' => array($this, 'check_authentication'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                ),
                'status' => array(
                    'required' => true,
                    'enum' => array('attending', 'not_attending', 'maybe'),
                ),
            ),
        ));
        
        // Get item exchange listings
        register_rest_route($namespace, '/content/items', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_items'),
            'permission_callback' => '__return_true',
            'args' => array(
                'page' => array(
                    'default' => 1,
                    'type' => 'integer',
                    'minimum' => 1,
                ),
                'per_page' => array(
                    'default' => 10,
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 50,
                ),
                'category' => array(
                    'type' => 'string',
                ),
                'condition' => array(
                    'type' => 'string',
                ),
                'location' => array(
                    'type' => 'string',
                ),
                'search' => array(
                    'type' => 'string',
                ),
                'sort' => array(
                    'default' => 'newest',
                    'enum' => array('newest', 'oldest', 'distance', 'popularity'),
                ),
            ),
        ));
        
        // Get single item
        register_rest_route($namespace, '/content/items/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_item'),
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                ),
            ),
        ));
        
        // Get categories
        register_rest_route($namespace, '/content/categories', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_categories'),
            'permission_callback' => '__return_true',
            'args' => array(
                'taxonomy' => array(
                    'default' => 'category',
                    'type' => 'string',
                ),
            ),
        ));
        
        // Search content
        register_rest_route($namespace, '/content/search', array(
            'methods' => 'GET',
            'callback' => array($this, 'search_content'),
            'permission_callback' => '__return_true',
            'args' => array(
                'query' => array(
                    'required' => true,
                    'type' => 'string',
                    'minLength' => 3,
                ),
                'type' => array(
                    'default' => 'all',
                    'enum' => array('all', 'posts', 'petitions', 'events', 'items'),
                ),
                'page' => array(
                    'default' => 1,
                    'type' => 'integer',
                    'minimum' => 1,
                ),
                'per_page' => array(
                    'default' => 10,
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 50,
                ),
            ),
        ));
    }
    
    /**
     * Get posts
     */
    public function get_posts($request) {
        try {
            $page = $request->get_param('page');
            $per_page = $request->get_param('per_page');
            $post_type = $request->get_param('post_type');
            $category = $request->get_param('category');
            $search = $request->get_param('search');
            $order = $request->get_param('order');
            $orderby = $request->get_param('orderby');
            
            $cache_key = "posts_" . md5(serialize($request->get_params()));
            $cached_result = $this->cache_manager->get($cache_key);
            
            if ($cached_result !== false) {
                return new WP_REST_Response($cached_result, 200);
            }
            
            $args = array(
                'post_type' => $post_type,
                'post_status' => 'publish',
                'posts_per_page' => $per_page,
                'paged' => $page,
                'order' => $order,
            );
            
            // Set orderby
            switch ($orderby) {
                case 'popularity':
                    $args['meta_key'] = 'post_views';
                    $args['orderby'] = 'meta_value_num';
                    break;
                case 'relevance':
                    if ($search) {
                        $args['orderby'] = 'relevance';
                    } else {
                        $args['orderby'] = 'date';
                    }
                    break;
                default:
                    $args['orderby'] = $orderby;
            }
            
            // Add category filter
            if ($category) {
                $args['category_name'] = $category;
            }
            
            // Add search
            if ($search) {
                $args['s'] = $search;
            }
            
            $query = new WP_Query($args);
            
            $posts = array();
            
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $posts[] = $this->format_post_data(get_post());
                }
                wp_reset_postdata();
            }
            
            $result = array(
                'success' => true,
                'data' => array(
                    'posts' => $posts,
                    'pagination' => array(
                        'page' => $page,
                        'per_page' => $per_page,
                        'total' => $query->found_posts,
                        'total_pages' => $query->max_num_pages,
                    ),
                ),
            );
            
            // Cache for 5 minutes
            $this->cache_manager->set($cache_key, $result, 300);
            
            return new WP_REST_Response($result, 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Get Posts Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Get single post
     */
    public function get_post($request) {
        try {
            $post_id = $request->get_param('id');
            $cache_key = "post_{$post_id}";
            
            $cached_result = $this->cache_manager->get($cache_key);
            
            if ($cached_result !== false) {
                return new WP_REST_Response($cached_result, 200);
            }
            
            $post = get_post($post_id);
            
            if (!$post || $post->post_status !== 'publish') {
                return new WP_Error('post_not_found', 'Post not found.', array('status' => 404));
            }
            
            // Update view count
            $views = (int) get_post_meta($post_id, 'post_views', true);
            update_post_meta($post_id, 'post_views', $views + 1);
            
            $post_data = $this->format_post_data($post, true);
            
            $result = array(
                'success' => true,
                'data' => $post_data,
            );
            
            // Cache for 10 minutes
            $this->cache_manager->set($cache_key, $result, 600);
            
            return new WP_REST_Response($result, 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Get Post Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Get petitions
     */
    public function get_petitions($request) {
        try {
            $page = $request->get_param('page');
            $per_page = $request->get_param('per_page');
            $status = $request->get_param('status');
            $category = $request->get_param('category');
            $search = $request->get_param('search');
            
            $cache_key = "petitions_" . md5(serialize($request->get_params()));
            $cached_result = $this->cache_manager->get($cache_key);
            
            if ($cached_result !== false) {
                return new WP_REST_Response($cached_result, 200);
            }
            
            $args = array(
                'post_type' => 'environmental_petition',
                'post_status' => 'publish',
                'posts_per_page' => $per_page,
                'paged' => $page,
                'orderby' => 'date',
                'order' => 'DESC',
            );
            
            // Filter by status
            if ($status !== 'all') {
                $args['meta_query'] = array(
                    array(
                        'key' => 'petition_status',
                        'value' => $status,
                        'compare' => '=',
                    ),
                );
            }
            
            // Add category filter
            if ($category) {
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'petition_category',
                        'field' => 'slug',
                        'terms' => $category,
                    ),
                );
            }
            
            // Add search
            if ($search) {
                $args['s'] = $search;
            }
            
            $query = new WP_Query($args);
            
            $petitions = array();
            
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $petitions[] = $this->format_petition_data(get_post());
                }
                wp_reset_postdata();
            }
            
            $result = array(
                'success' => true,
                'data' => array(
                    'petitions' => $petitions,
                    'pagination' => array(
                        'page' => $page,
                        'per_page' => $per_page,
                        'total' => $query->found_posts,
                        'total_pages' => $query->max_num_pages,
                    ),
                ),
            );
            
            // Cache for 5 minutes
            $this->cache_manager->set($cache_key, $result, 300);
            
            return new WP_REST_Response($result, 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Get Petitions Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Get single petition
     */
    public function get_petition($request) {
        try {
            $petition_id = $request->get_param('id');
            $cache_key = "petition_{$petition_id}";
            
            $cached_result = $this->cache_manager->get($cache_key);
            
            if ($cached_result !== false) {
                return new WP_REST_Response($cached_result, 200);
            }
            
            $petition = get_post($petition_id);
            
            if (!$petition || $petition->post_type !== 'environmental_petition' || $petition->post_status !== 'publish') {
                return new WP_Error('petition_not_found', 'Petition not found.', array('status' => 404));
            }
            
            $petition_data = $this->format_petition_data($petition, true);
            
            $result = array(
                'success' => true,
                'data' => $petition_data,
            );
            
            // Cache for 10 minutes
            $this->cache_manager->set($cache_key, $result, 600);
            
            return new WP_REST_Response($result, 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Get Petition Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Sign petition
     */
    public function sign_petition($request) {
        try {
            $petition_id = $request->get_param('id');
            $comment = $request->get_param('comment');
            $anonymous = $request->get_param('anonymous');
            $user_id = get_current_user_id();
            
            $petition = get_post($petition_id);
            
            if (!$petition || $petition->post_type !== 'environmental_petition') {
                return new WP_Error('petition_not_found', 'Petition not found.', array('status' => 404));
            }
            
            global $wpdb;
            $table_signatures = $wpdb->prefix . 'environmental_petition_signatures';
            
            // Check if user already signed
            $existing = $wpdb->get_var($wpdb->prepare("
                SELECT id FROM {$table_signatures} 
                WHERE petition_id = %d AND user_id = %d
            ", $petition_id, $user_id));
            
            if ($existing) {
                return new WP_Error('already_signed', 'You have already signed this petition.', array('status' => 409));
            }
            
            // Add signature
            $result = $wpdb->insert(
                $table_signatures,
                array(
                    'petition_id' => $petition_id,
                    'user_id' => $user_id,
                    'comment' => $comment,
                    'anonymous' => $anonymous ? 1 : 0,
                    'ip_address' => $this->security->get_client_ip(),
                    'created_at' => current_time('mysql'),
                ),
                array('%d', '%d', '%s', '%d', '%s', '%s')
            );
            
            if ($result === false) {
                return new WP_Error('signature_failed', 'Failed to sign petition.', array('status' => 500));
            }
            
            // Update signature count
            $signature_count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM {$table_signatures} WHERE petition_id = %d
            ", $petition_id));
            
            update_post_meta($petition_id, 'signature_count', $signature_count);
            
            // Clear cache
            $this->cache_manager->delete("petition_{$petition_id}");
            
            // Award points to user
            $current_points = (int) get_user_meta($user_id, 'environmental_points', true);
            update_user_meta($user_id, 'environmental_points', $current_points + 10);
            
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Petition signed successfully',
                'data' => array(
                    'signature_count' => $signature_count,
                    'points_earned' => 10,
                ),
            ), 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Sign Petition Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Get events
     */
    public function get_events($request) {
        try {
            $page = $request->get_param('page');
            $per_page = $request->get_param('per_page');
            $time_filter = $request->get_param('time_filter');
            $location = $request->get_param('location');
            
            $cache_key = "events_" . md5(serialize($request->get_params()));
            $cached_result = $this->cache_manager->get($cache_key);
            
            if ($cached_result !== false) {
                return new WP_REST_Response($cached_result, 200);
            }
            
            $args = array(
                'post_type' => 'environmental_event',
                'post_status' => 'publish',
                'posts_per_page' => $per_page,
                'paged' => $page,
                'meta_key' => 'event_date',
                'orderby' => 'meta_value',
                'order' => 'ASC',
            );
            
            // Filter by time
            if ($time_filter === 'upcoming') {
                $args['meta_query'] = array(
                    array(
                        'key' => 'event_date',
                        'value' => current_time('Y-m-d H:i:s'),
                        'compare' => '>=',
                        'type' => 'DATETIME',
                    ),
                );
            } elseif ($time_filter === 'past') {
                $args['meta_query'] = array(
                    array(
                        'key' => 'event_date',
                        'value' => current_time('Y-m-d H:i:s'),
                        'compare' => '<',
                        'type' => 'DATETIME',
                    ),
                );
                $args['order'] = 'DESC';
            }
            
            // Filter by location
            if ($location) {
                if (!isset($args['meta_query'])) {
                    $args['meta_query'] = array();
                }
                $args['meta_query'][] = array(
                    'key' => 'event_location',
                    'value' => $location,
                    'compare' => 'LIKE',
                );
            }
            
            $query = new WP_Query($args);
            
            $events = array();
            
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $events[] = $this->format_event_data(get_post());
                }
                wp_reset_postdata();
            }
            
            $result = array(
                'success' => true,
                'data' => array(
                    'events' => $events,
                    'pagination' => array(
                        'page' => $page,
                        'per_page' => $per_page,
                        'total' => $query->found_posts,
                        'total_pages' => $query->max_num_pages,
                    ),
                ),
            );
            
            // Cache for 5 minutes
            $this->cache_manager->set($cache_key, $result, 300);
            
            return new WP_REST_Response($result, 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Get Events Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Get single event
     */
    public function get_event($request) {
        try {
            $event_id = $request->get_param('id');
            $cache_key = "event_{$event_id}";
            
            $cached_result = $this->cache_manager->get($cache_key);
            
            if ($cached_result !== false) {
                return new WP_REST_Response($cached_result, 200);
            }
            
            $event = get_post($event_id);
            
            if (!$event || $event->post_type !== 'environmental_event' || $event->post_status !== 'publish') {
                return new WP_Error('event_not_found', 'Event not found.', array('status' => 404));
            }
            
            $event_data = $this->format_event_data($event, true);
            
            $result = array(
                'success' => true,
                'data' => $event_data,
            );
            
            // Cache for 10 minutes
            $this->cache_manager->set($cache_key, $result, 600);
            
            return new WP_REST_Response($result, 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Get Event Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * RSVP to event
     */
    public function rsvp_event($request) {
        try {
            $event_id = $request->get_param('id');
            $status = $request->get_param('status');
            $user_id = get_current_user_id();
            
            $event = get_post($event_id);
            
            if (!$event || $event->post_type !== 'environmental_event') {
                return new WP_Error('event_not_found', 'Event not found.', array('status' => 404));
            }
            
            global $wpdb;
            $table_rsvps = $wpdb->prefix . 'environmental_event_rsvps';
            
            // Check if user already RSVP'd
            $existing = $wpdb->get_var($wpdb->prepare("
                SELECT id FROM {$table_rsvps} 
                WHERE event_id = %d AND user_id = %d
            ", $event_id, $user_id));
            
            if ($existing) {
                // Update existing RSVP
                $wpdb->update(
                    $table_rsvps,
                    array('status' => $status, 'updated_at' => current_time('mysql')),
                    array('id' => $existing),
                    array('%s', '%s'),
                    array('%d')
                );
            } else {
                // Create new RSVP
                $wpdb->insert(
                    $table_rsvps,
                    array(
                        'event_id' => $event_id,
                        'user_id' => $user_id,
                        'status' => $status,
                        'created_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql'),
                    ),
                    array('%d', '%d', '%s', '%s', '%s')
                );
            }
            
            // Clear cache
            $this->cache_manager->delete("event_{$event_id}");
            
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'RSVP updated successfully',
                'data' => array(
                    'status' => $status,
                ),
            ), 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Event RSVP Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Get items
     */
    public function get_items($request) {
        try {
            $page = $request->get_param('page');
            $per_page = $request->get_param('per_page');
            $category = $request->get_param('category');
            $condition = $request->get_param('condition');
            $location = $request->get_param('location');
            $search = $request->get_param('search');
            $sort = $request->get_param('sort');
            
            $cache_key = "items_" . md5(serialize($request->get_params()));
            $cached_result = $this->cache_manager->get($cache_key);
            
            if ($cached_result !== false) {
                return new WP_REST_Response($cached_result, 200);
            }
            
            $args = array(
                'post_type' => 'environmental_item',
                'post_status' => 'publish',
                'posts_per_page' => $per_page,
                'paged' => $page,
            );
            
            // Set sorting
            switch ($sort) {
                case 'oldest':
                    $args['orderby'] = 'date';
                    $args['order'] = 'ASC';
                    break;
                case 'popularity':
                    $args['meta_key'] = 'item_views';
                    $args['orderby'] = 'meta_value_num';
                    $args['order'] = 'DESC';
                    break;
                default: // newest
                    $args['orderby'] = 'date';
                    $args['order'] = 'DESC';
            }
            
            $meta_query = array();
            
            // Filter by condition
            if ($condition) {
                $meta_query[] = array(
                    'key' => 'item_condition',
                    'value' => $condition,
                    'compare' => '=',
                );
            }
            
            // Filter by location
            if ($location) {
                $meta_query[] = array(
                    'key' => 'item_location',
                    'value' => $location,
                    'compare' => 'LIKE',
                );
            }
            
            if (!empty($meta_query)) {
                $args['meta_query'] = $meta_query;
            }
            
            // Add category filter
            if ($category) {
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'item_category',
                        'field' => 'slug',
                        'terms' => $category,
                    ),
                );
            }
            
            // Add search
            if ($search) {
                $args['s'] = $search;
            }
            
            $query = new WP_Query($args);
            
            $items = array();
            
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $items[] = $this->format_item_data(get_post());
                }
                wp_reset_postdata();
            }
            
            $result = array(
                'success' => true,
                'data' => array(
                    'items' => $items,
                    'pagination' => array(
                        'page' => $page,
                        'per_page' => $per_page,
                        'total' => $query->found_posts,
                        'total_pages' => $query->max_num_pages,
                    ),
                ),
            );
            
            // Cache for 5 minutes
            $this->cache_manager->set($cache_key, $result, 300);
            
            return new WP_REST_Response($result, 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Get Items Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Get single item
     */
    public function get_item($request) {
        try {
            $item_id = $request->get_param('id');
            $cache_key = "item_{$item_id}";
            
            $cached_result = $this->cache_manager->get($cache_key);
            
            if ($cached_result !== false) {
                return new WP_REST_Response($cached_result, 200);
            }
            
            $item = get_post($item_id);
            
            if (!$item || $item->post_type !== 'environmental_item' || $item->post_status !== 'publish') {
                return new WP_Error('item_not_found', 'Item not found.', array('status' => 404));
            }
            
            // Update view count
            $views = (int) get_post_meta($item_id, 'item_views', true);
            update_post_meta($item_id, 'item_views', $views + 1);
            
            $item_data = $this->format_item_data($item, true);
            
            $result = array(
                'success' => true,
                'data' => $item_data,
            );
            
            // Cache for 10 minutes
            $this->cache_manager->set($cache_key, $result, 600);
            
            return new WP_REST_Response($result, 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Get Item Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Get categories
     */
    public function get_categories($request) {
        try {
            $taxonomy = $request->get_param('taxonomy');
            $cache_key = "categories_{$taxonomy}";
            
            $cached_result = $this->cache_manager->get($cache_key);
            
            if ($cached_result !== false) {
                return new WP_REST_Response($cached_result, 200);
            }
            
            $categories = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC',
            ));
            
            if (is_wp_error($categories)) {
                return new WP_Error('categories_failed', 'Failed to retrieve categories.', array('status' => 500));
            }
            
            $formatted_categories = array();
            
            foreach ($categories as $category) {
                $formatted_categories[] = array(
                    'id' => $category->term_id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'count' => $category->count,
                    'parent' => $category->parent,
                );
            }
            
            $result = array(
                'success' => true,
                'data' => $formatted_categories,
            );
            
            // Cache for 1 hour
            $this->cache_manager->set($cache_key, $result, 3600);
            
            return new WP_REST_Response($result, 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Get Categories Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Search content
     */
    public function search_content($request) {
        try {
            $query = $request->get_param('query');
            $type = $request->get_param('type');
            $page = $request->get_param('page');
            $per_page = $request->get_param('per_page');
            
            $cache_key = "search_" . md5(serialize($request->get_params()));
            $cached_result = $this->cache_manager->get($cache_key);
            
            if ($cached_result !== false) {
                return new WP_REST_Response($cached_result, 200);
            }
            
            $results = array();
            
            if ($type === 'all' || $type === 'posts') {
                $posts = $this->search_posts($query, $page, $per_page);
                $results['posts'] = $posts;
            }
            
            if ($type === 'all' || $type === 'petitions') {
                $petitions = $this->search_petitions($query, $page, $per_page);
                $results['petitions'] = $petitions;
            }
            
            if ($type === 'all' || $type === 'events') {
                $events = $this->search_events($query, $page, $per_page);
                $results['events'] = $events;
            }
            
            if ($type === 'all' || $type === 'items') {
                $items = $this->search_items($query, $page, $per_page);
                $results['items'] = $items;
            }
            
            $result = array(
                'success' => true,
                'data' => array(
                    'query' => $query,
                    'results' => $results,
                ),
            );
            
            // Cache for 2 minutes (search results change frequently)
            $this->cache_manager->set($cache_key, $result, 120);
            
            return new WP_REST_Response($result, 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Search Content Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Check authentication permission callback
     */
    public function check_authentication($request) {
        return $this->auth_manager->authenticate_request($request);
    }
    
    /**
     * Format post data
     */
    private function format_post_data($post, $detailed = false) {
        $data = array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'excerpt' => get_the_excerpt($post),
            'date' => $post->post_date,
            'modified' => $post->post_modified,
            'author' => array(
                'id' => $post->post_author,
                'name' => get_the_author_meta('display_name', $post->post_author),
                'avatar' => get_avatar_url($post->post_author),
            ),
            'featured_image' => get_the_post_thumbnail_url($post->ID, 'medium'),
            'categories' => wp_get_post_categories($post->ID, array('fields' => 'names')),
            'tags' => wp_get_post_tags($post->ID, array('fields' => 'names')),
            'permalink' => get_permalink($post->ID),
            'views' => (int) get_post_meta($post->ID, 'post_views', true),
        );
        
        if ($detailed) {
            $data['content'] = apply_filters('the_content', $post->post_content);
        }
        
        return $data;
    }
    
    /**
     * Format petition data
     */
    private function format_petition_data($petition, $detailed = false) {
        $signature_count = (int) get_post_meta($petition->ID, 'signature_count', true);
        $target_signatures = (int) get_post_meta($petition->ID, 'target_signatures', true);
        
        $data = array(
            'id' => $petition->ID,
            'title' => $petition->post_title,
            'slug' => $petition->post_name,
            'excerpt' => get_the_excerpt($petition),
            'date' => $petition->post_date,
            'author' => array(
                'id' => $petition->post_author,
                'name' => get_the_author_meta('display_name', $petition->post_author),
                'avatar' => get_avatar_url($petition->post_author),
            ),
            'featured_image' => get_the_post_thumbnail_url($petition->ID, 'medium'),
            'signature_count' => $signature_count,
            'target_signatures' => $target_signatures,
            'progress_percentage' => $target_signatures > 0 ? round(($signature_count / $target_signatures) * 100, 2) : 0,
            'status' => get_post_meta($petition->ID, 'petition_status', true),
            'categories' => wp_get_object_terms($petition->ID, 'petition_category', array('fields' => 'names')),
            'permalink' => get_permalink($petition->ID),
        );
        
        if ($detailed) {
            $data['content'] = apply_filters('the_content', $petition->post_content);
            $data['target_description'] = get_post_meta($petition->ID, 'target_description', true);
            $data['petition_updates'] = get_post_meta($petition->ID, 'petition_updates', true);
        }
        
        return $data;
    }
    
    /**
     * Format event data
     */
    private function format_event_data($event, $detailed = false) {
        $data = array(
            'id' => $event->ID,
            'title' => $event->post_title,
            'slug' => $event->post_name,
            'excerpt' => get_the_excerpt($event),
            'date' => $event->post_date,
            'event_date' => get_post_meta($event->ID, 'event_date', true),
            'event_end_date' => get_post_meta($event->ID, 'event_end_date', true),
            'location' => get_post_meta($event->ID, 'event_location', true),
            'organizer' => array(
                'id' => $event->post_author,
                'name' => get_the_author_meta('display_name', $event->post_author),
                'avatar' => get_avatar_url($event->post_author),
            ),
            'featured_image' => get_the_post_thumbnail_url($event->ID, 'medium'),
            'categories' => wp_get_object_terms($event->ID, 'event_category', array('fields' => 'names')),
            'attendee_count' => $this->get_event_attendee_count($event->ID),
            'permalink' => get_permalink($event->ID),
        );
        
        if ($detailed) {
            $data['content'] = apply_filters('the_content', $event->post_content);
            $data['event_details'] = array(
                'capacity' => get_post_meta($event->ID, 'event_capacity', true),
                'price' => get_post_meta($event->ID, 'event_price', true),
                'contact_info' => get_post_meta($event->ID, 'contact_info', true),
            );
        }
        
        return $data;
    }
    
    /**
     * Format item data
     */
    private function format_item_data($item, $detailed = false) {
        $data = array(
            'id' => $item->ID,
            'title' => $item->post_title,
            'slug' => $item->post_name,
            'excerpt' => get_the_excerpt($item),
            'date' => $item->post_date,
            'owner' => array(
                'id' => $item->post_author,
                'name' => get_the_author_meta('display_name', $item->post_author),
                'avatar' => get_avatar_url($item->post_author),
            ),
            'featured_image' => get_the_post_thumbnail_url($item->ID, 'medium'),
            'condition' => get_post_meta($item->ID, 'item_condition', true),
            'location' => get_post_meta($item->ID, 'item_location', true),
            'exchange_type' => get_post_meta($item->ID, 'exchange_type', true),
            'categories' => wp_get_object_terms($item->ID, 'item_category', array('fields' => 'names')),
            'views' => (int) get_post_meta($item->ID, 'item_views', true),
            'permalink' => get_permalink($item->ID),
        );
        
        if ($detailed) {
            $data['content'] = apply_filters('the_content', $item->post_content);
            $data['item_details'] = array(
                'dimensions' => get_post_meta($item->ID, 'item_dimensions', true),
                'weight' => get_post_meta($item->ID, 'item_weight', true),
                'brand' => get_post_meta($item->ID, 'item_brand', true),
                'year' => get_post_meta($item->ID, 'item_year', true),
            );
            $data['gallery'] = $this->get_item_gallery($item->ID);
        }
        
        return $data;
    }
    
    /**
     * Get event attendee count
     */
    private function get_event_attendee_count($event_id) {
        global $wpdb;
        $table_rsvps = $wpdb->prefix . 'environmental_event_rsvps';
        
        return (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$table_rsvps} 
            WHERE event_id = %d AND status = 'attending'
        ", $event_id));
    }
    
    /**
     * Get item gallery images
     */
    private function get_item_gallery($item_id) {
        $gallery_ids = get_post_meta($item_id, 'item_gallery', true);
        
        if (empty($gallery_ids) || !is_array($gallery_ids)) {
            return array();
        }
        
        $gallery = array();
        
        foreach ($gallery_ids as $image_id) {
            $image_url = wp_get_attachment_image_url($image_id, 'medium');
            if ($image_url) {
                $gallery[] = array(
                    'id' => $image_id,
                    'url' => $image_url,
                    'thumbnail' => wp_get_attachment_image_url($image_id, 'thumbnail'),
                    'full' => wp_get_attachment_image_url($image_id, 'full'),
                );
            }
        }
        
        return $gallery;
    }
    
    /**
     * Search posts
     */
    private function search_posts($query, $page, $per_page) {
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            's' => $query,
            'orderby' => 'relevance',
        );
        
        $search_query = new WP_Query($args);
        $posts = array();
        
        if ($search_query->have_posts()) {
            while ($search_query->have_posts()) {
                $search_query->the_post();
                $posts[] = $this->format_post_data(get_post());
            }
            wp_reset_postdata();
        }
        
        return $posts;
    }
    
    /**
     * Search petitions
     */
    private function search_petitions($query, $page, $per_page) {
        $args = array(
            'post_type' => 'environmental_petition',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            's' => $query,
            'orderby' => 'relevance',
        );
        
        $search_query = new WP_Query($args);
        $petitions = array();
        
        if ($search_query->have_posts()) {
            while ($search_query->have_posts()) {
                $search_query->the_post();
                $petitions[] = $this->format_petition_data(get_post());
            }
            wp_reset_postdata();
        }
        
        return $petitions;
    }
    
    /**
     * Search events
     */
    private function search_events($query, $page, $per_page) {
        $args = array(
            'post_type' => 'environmental_event',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            's' => $query,
            'orderby' => 'relevance',
        );
        
        $search_query = new WP_Query($args);
        $events = array();
        
        if ($search_query->have_posts()) {
            while ($search_query->have_posts()) {
                $search_query->the_post();
                $events[] = $this->format_event_data(get_post());
            }
            wp_reset_postdata();
        }
        
        return $events;
    }
    
    /**
     * Search items
     */
    private function search_items($query, $page, $per_page) {
        $args = array(
            'post_type' => 'environmental_item',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            's' => $query,
            'orderby' => 'relevance',
        );
        
        $search_query = new WP_Query($args);
        $items = array();
        
        if ($search_query->have_posts()) {
            while ($search_query->have_posts()) {
                $search_query->the_post();
                $items[] = $this->format_item_data(get_post());
            }
            wp_reset_postdata();
        }
        
        return $items;
    }
}

// Initialize the content endpoints
new Environmental_Mobile_API_Content_Endpoints();
