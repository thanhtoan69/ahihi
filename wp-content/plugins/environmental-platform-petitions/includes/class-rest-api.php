<?php
/**
 * REST API Class
 * 
 * Handles REST API endpoints for petition system external integrations
 * 
 * @package Environmental_Platform_Petitions
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Platform_Petitions_REST_API {
    
    /**
     * API namespace
     */
    private $namespace = 'petition/v1';
    
    /**
     * Database instance
     */
    private $database;
    
    /**
     * Signature manager instance
     */
    private $signature_manager;
    
    /**
     * Analytics instance
     */
    private $analytics;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new Environmental_Platform_Petitions_Database();
        $this->signature_manager = new Environmental_Platform_Petitions_Signature_Manager();
        $this->analytics = new Environmental_Platform_Petitions_Analytics();
        
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Petitions endpoints
        register_rest_route($this->namespace, '/petitions', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_petitions'),
            'permission_callback' => array($this, 'check_read_permission'),
            'args' => $this->get_collection_params()
        ));
        
        register_rest_route($this->namespace, '/petitions/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_petition'),
            'permission_callback' => array($this, 'check_read_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param);
                    }
                )
            )
        ));
        
        // Signatures endpoints
        register_rest_route($this->namespace, '/petitions/(?P<id>\d+)/signatures', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_petition_signatures'),
                'permission_callback' => array($this, 'check_read_permission'),
                'args' => $this->get_collection_params()
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'create_signature'),
                'permission_callback' => array($this, 'check_create_signature_permission'),
                'args' => $this->get_signature_schema()
            )
        ));
        
        register_rest_route($this->namespace, '/signatures/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_signature'),
            'permission_callback' => array($this, 'check_read_permission')
        ));
        
        register_rest_route($this->namespace, '/signatures/(?P<id>\d+)/verify', array(
            'methods' => 'POST',
            'callback' => array($this, 'verify_signature'),
            'permission_callback' => array($this, 'check_verify_permission'),
            'args' => array(
                'token' => array(
                    'required' => true,
                    'type' => 'string'
                )
            )
        ));
        
        // Analytics endpoints
        register_rest_route($this->namespace, '/petitions/(?P<id>\d+)/analytics', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_petition_analytics'),
            'permission_callback' => array($this, 'check_analytics_permission'),
            'args' => array(
                'period' => array(
                    'default' => '30 days',
                    'enum' => array('7 days', '30 days', '90 days', '1 year')
                ),
                'type' => array(
                    'default' => 'overview',
                    'enum' => array('overview', 'time_series', 'demographics', 'funnel')
                )
            )
        ));
        
        // Statistics endpoints
        register_rest_route($this->namespace, '/statistics', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_global_statistics'),
            'permission_callback' => array($this, 'check_read_permission')
        ));
        
        // Campaign endpoints
        register_rest_route($this->namespace, '/campaigns', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_campaigns'),
            'permission_callback' => array($this, 'check_campaigns_permission')
        ));
        
        register_rest_route($this->namespace, '/campaigns/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_campaign'),
            'permission_callback' => array($this, 'check_campaigns_permission')
        ));
        
        // Webhook endpoints
        register_rest_route($this->namespace, '/webhooks/signature', array(
            'methods' => 'POST',
            'callback' => array($this, 'webhook_signature_created'),
            'permission_callback' => array($this, 'check_webhook_permission')
        ));
        
        register_rest_route($this->namespace, '/webhooks/milestone', array(
            'methods' => 'POST',
            'callback' => array($this, 'webhook_milestone_reached'),
            'permission_callback' => array($this, 'check_webhook_permission')
        ));
    }
    
    /**
     * Get petitions
     */
    public function get_petitions($request) {
        $params = $request->get_params();
        
        $args = array(
            'post_type' => 'env_petition',
            'post_status' => 'publish',
            'posts_per_page' => $params['per_page'] ?? 10,
            'paged' => $params['page'] ?? 1,
            'orderby' => $params['orderby'] ?? 'date',
            'order' => $params['order'] ?? 'DESC'
        );
        
        if (!empty($params['search'])) {
            $args['s'] = sanitize_text_field($params['search']);
        }
        
        if (!empty($params['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'petition_type',
                    'field' => 'slug',
                    'terms' => sanitize_text_field($params['category'])
                )
            );
        }
        
        $query = new WP_Query($args);
        $petitions = array();
        
        foreach ($query->posts as $post) {
            $petitions[] = $this->prepare_petition_for_response($post);
        }
        
        $response = rest_ensure_response($petitions);
        
        // Add pagination headers
        $response->header('X-WP-Total', $query->found_posts);
        $response->header('X-WP-TotalPages', $query->max_num_pages);
        
        return $response;
    }
    
    /**
     * Get single petition
     */
    public function get_petition($request) {
        $petition_id = $request['id'];
        $petition = get_post($petition_id);
        
        if (!$petition || $petition->post_type !== 'env_petition') {
            return new WP_Error('petition_not_found', 'Petition not found', array('status' => 404));
        }
        
        if ($petition->post_status !== 'publish' && !current_user_can('edit_post', $petition_id)) {
            return new WP_Error('petition_not_accessible', 'Petition not accessible', array('status' => 403));
        }
        
        return rest_ensure_response($this->prepare_petition_for_response($petition));
    }
    
    /**
     * Get petition signatures
     */
    public function get_petition_signatures($request) {
        $petition_id = $request['id'];
        $params = $request->get_params();
        
        // Verify petition exists
        $petition = get_post($petition_id);
        if (!$petition || $petition->post_type !== 'env_petition') {
            return new WP_Error('petition_not_found', 'Petition not found', array('status' => 404));
        }
        
        global $wpdb;
        $table = $this->database->get_table_name('signatures');
        
        $limit = min(100, $params['per_page'] ?? 20);
        $offset = (($params['page'] ?? 1) - 1) * $limit;
        
        $where_conditions = array("petition_id = %d");
        $where_values = array($petition_id);
        
        // Only show verified signatures to public
        if (!current_user_can('manage_options')) {
            $where_conditions[] = "is_verified = 1";
        }
        
        $where_clause = "WHERE " . implode(" AND ", $where_conditions);
        
        $signatures = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            array_merge($where_values, array($limit, $offset))
        ));
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} {$where_clause}",
            $where_values
        ));
        
        $prepared_signatures = array();
        foreach ($signatures as $signature) {
            $prepared_signatures[] = $this->prepare_signature_for_response($signature);
        }
        
        $response = rest_ensure_response($prepared_signatures);
        $response->header('X-WP-Total', $total);
        $response->header('X-WP-TotalPages', ceil($total / $limit));
        
        return $response;
    }
    
    /**
     * Create signature
     */
    public function create_signature($request) {
        $petition_id = $request['id'];
        $params = $request->get_params();
        
        // Verify petition exists and is published
        $petition = get_post($petition_id);
        if (!$petition || $petition->post_type !== 'env_petition' || $petition->post_status !== 'publish') {
            return new WP_Error('petition_not_found', 'Petition not found or not published', array('status' => 404));
        }
        
        // Validate required fields
        $required_fields = array('first_name', 'last_name', 'user_email');
        foreach ($required_fields as $field) {
            if (empty($params[$field])) {
                return new WP_Error('missing_field', "Field '{$field}' is required", array('status' => 400));
            }
        }
        
        // Check for duplicate signature
        if ($this->signature_manager->has_already_signed($petition_id, $params['user_email'])) {
            return new WP_Error('duplicate_signature', 'This email has already signed this petition', array('status' => 409));
        }
        
        // Create signature
        $signature_data = array(
            'first_name' => sanitize_text_field($params['first_name']),
            'last_name' => sanitize_text_field($params['last_name']),
            'user_email' => sanitize_email($params['user_email']),
            'user_phone' => sanitize_text_field($params['user_phone'] ?? ''),
            'user_location' => sanitize_text_field($params['user_location'] ?? ''),
            'comment' => sanitize_textarea_field($params['comment'] ?? ''),
            'is_anonymous' => !empty($params['is_anonymous']),
            'api_source' => true
        );
        
        $signature_id = $this->signature_manager->create_signature($petition_id, $signature_data);
        
        if (!$signature_id) {
            return new WP_Error('signature_creation_failed', 'Failed to create signature', array('status' => 500));
        }
        
        // Get created signature
        global $wpdb;
        $table = $this->database->get_table_name('signatures');
        $signature = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $signature_id
        ));
        
        $response = rest_ensure_response($this->prepare_signature_for_response($signature));
        $response->set_status(201);
        
        return $response;
    }
    
    /**
     * Get single signature
     */
    public function get_signature($request) {
        $signature_id = $request['id'];
        
        global $wpdb;
        $table = $this->database->get_table_name('signatures');
        
        $signature = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $signature_id
        ));
        
        if (!$signature) {
            return new WP_Error('signature_not_found', 'Signature not found', array('status' => 404));
        }
        
        // Check permissions for unverified signatures
        if (!$signature->is_verified && !current_user_can('manage_options')) {
            return new WP_Error('signature_not_accessible', 'Signature not accessible', array('status' => 403));
        }
        
        return rest_ensure_response($this->prepare_signature_for_response($signature));
    }
    
    /**
     * Verify signature
     */
    public function verify_signature($request) {
        $signature_id = $request['id'];
        $token = $request['token'];
        
        global $wpdb;
        $table = $this->database->get_table_name('signatures');
        
        $signature = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d AND verification_token = %s",
            $signature_id,
            $token
        ));
        
        if (!$signature) {
            return new WP_Error('invalid_verification', 'Invalid verification token', array('status' => 400));
        }
        
        if ($signature->is_verified) {
            return new WP_Error('already_verified', 'Signature already verified', array('status' => 409));
        }
        
        // Verify signature
        $verification_system = new Environmental_Platform_Petitions_Verification_System();
        $result = $verification_system->verify_signature($signature_id, 'email');
        
        if (!$result) {
            return new WP_Error('verification_failed', 'Failed to verify signature', array('status' => 500));
        }
        
        return rest_ensure_response(array(
            'verified' => true,
            'message' => 'Signature verified successfully'
        ));
    }
    
    /**
     * Get petition analytics
     */
    public function get_petition_analytics($request) {
        $petition_id = $request['id'];
        $period = $request['period'];
        $type = $request['type'];
        
        // Verify petition exists
        $petition = get_post($petition_id);
        if (!$petition || $petition->post_type !== 'env_petition') {
            return new WP_Error('petition_not_found', 'Petition not found', array('status' => 404));
        }
        
        switch ($type) {
            case 'overview':
                $data = $this->analytics->get_petition_overview($petition_id, $period);
                break;
            case 'time_series':
                $metric = $request['metric'] ?? 'signatures';
                $data = $this->analytics->get_time_series_data($petition_id, $metric, 'daily', $period);
                break;
            case 'demographics':
                $data = $this->analytics->get_demographic_data($petition_id);
                break;
            case 'funnel':
                $data = $this->analytics->get_funnel_analysis($petition_id, $period);
                break;
            default:
                return new WP_Error('invalid_analytics_type', 'Invalid analytics type', array('status' => 400));
        }
        
        return rest_ensure_response($data);
    }
    
    /**
     * Get global statistics
     */
    public function get_global_statistics($request) {
        global $wpdb;
        
        $signatures_table = $this->database->get_table_name('signatures');
        $shares_table = $this->database->get_table_name('shares');
        
        $stats = array(
            'total_petitions' => wp_count_posts('env_petition')->publish,
            'total_signatures' => $wpdb->get_var("SELECT COUNT(*) FROM {$signatures_table} WHERE is_verified = 1"),
            'total_shares' => $wpdb->get_var("SELECT COUNT(*) FROM {$shares_table}"),
            'active_petitions' => $wpdb->get_var(
                "SELECT COUNT(DISTINCT p.ID) 
                FROM {$wpdb->posts} p 
                INNER JOIN {$signatures_table} s ON p.ID = s.petition_id 
                WHERE p.post_type = 'env_petition' 
                AND p.post_status = 'publish' 
                AND s.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            )
        );
        
        return rest_ensure_response($stats);
    }
    
    /**
     * Get campaigns
     */
    public function get_campaigns($request) {
        if (!class_exists('Environmental_Platform_Petitions_Campaign_Manager')) {
            return new WP_Error('campaigns_not_available', 'Campaigns feature not available', array('status' => 501));
        }
        
        global $wpdb;
        $table = $this->database->get_table_name('campaigns');
        
        $campaigns = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC");
        
        $prepared_campaigns = array();
        foreach ($campaigns as $campaign) {
            $prepared_campaigns[] = $this->prepare_campaign_for_response($campaign);
        }
        
        return rest_ensure_response($prepared_campaigns);
    }
    
    /**
     * Get single campaign
     */
    public function get_campaign($request) {
        $campaign_id = $request['id'];
        
        global $wpdb;
        $table = $this->database->get_table_name('campaigns');
        
        $campaign = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $campaign_id
        ));
        
        if (!$campaign) {
            return new WP_Error('campaign_not_found', 'Campaign not found', array('status' => 404));
        }
        
        return rest_ensure_response($this->prepare_campaign_for_response($campaign));
    }
    
    /**
     * Webhook: Signature created
     */
    public function webhook_signature_created($request) {
        $params = $request->get_params();
        
        // Trigger webhook event
        do_action('petition_webhook_signature_created', $params);
        
        return rest_ensure_response(array(
            'received' => true,
            'timestamp' => current_time('mysql')
        ));
    }
    
    /**
     * Webhook: Milestone reached
     */
    public function webhook_milestone_reached($request) {
        $params = $request->get_params();
        
        // Trigger webhook event
        do_action('petition_webhook_milestone_reached', $params);
        
        return rest_ensure_response(array(
            'received' => true,
            'timestamp' => current_time('mysql')
        ));
    }
    
    /**
     * Prepare petition for response
     */
    private function prepare_petition_for_response($post) {
        $petition_data = array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'content' => $post->post_content,
            'excerpt' => $post->post_excerpt,
            'status' => $post->post_status,
            'date_created' => $post->post_date,
            'date_modified' => $post->post_modified,
            'author' => $post->post_author,
            'featured_image' => get_the_post_thumbnail_url($post->ID, 'large'),
            'permalink' => get_permalink($post->ID)
        );
        
        // Add petition-specific data
        $petition_settings = get_post_meta($post->ID, 'petition_signature_settings', true);
        if ($petition_settings) {
            $petition_data['settings'] = $petition_settings;
        }
        
        // Add signature count
        global $wpdb;
        $signatures_table = $this->database->get_table_name('signatures');
        $signature_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$signatures_table} WHERE petition_id = %d AND is_verified = 1",
            $post->ID
        ));
        
        $petition_data['signature_count'] = intval($signature_count);
        
        // Add categories
        $categories = wp_get_post_terms($post->ID, 'petition_type', array('fields' => 'names'));
        $petition_data['categories'] = $categories;
        
        return $petition_data;
    }
    
    /**
     * Prepare signature for response
     */
    private function prepare_signature_for_response($signature) {
        $signature_data = array(
            'id' => $signature->id,
            'petition_id' => $signature->petition_id,
            'first_name' => $signature->first_name,
            'last_name' => $signature->last_name,
            'is_verified' => $signature->is_verified == 1,
            'date_created' => $signature->created_at
        );
        
        // Only include email and other sensitive data for admins
        if (current_user_can('manage_options')) {
            $signature_data['user_email'] = $signature->user_email;
            $signature_data['user_phone'] = $signature->user_phone;
            $signature_data['user_location'] = $signature->user_location;
            $signature_data['user_ip'] = $signature->user_ip;
        }
        
        // Include comment if not anonymous or if admin
        if (!$signature->is_anonymous || current_user_can('manage_options')) {
            $signature_data['comment'] = $signature->comment;
        }
        
        return $signature_data;
    }
    
    /**
     * Prepare campaign for response
     */
    private function prepare_campaign_for_response($campaign) {
        return array(
            'id' => $campaign->id,
            'petition_id' => $campaign->petition_id,
            'name' => $campaign->name,
            'description' => $campaign->description,
            'start_date' => $campaign->start_date,
            'end_date' => $campaign->end_date,
            'target_signatures' => $campaign->target_signatures,
            'status' => $campaign->status,
            'campaign_type' => $campaign->campaign_type,
            'date_created' => $campaign->created_at
        );
    }
    
    /**
     * Get collection parameters
     */
    private function get_collection_params() {
        return array(
            'page' => array(
                'default' => 1,
                'minimum' => 1,
                'type' => 'integer'
            ),
            'per_page' => array(
                'default' => 10,
                'minimum' => 1,
                'maximum' => 100,
                'type' => 'integer'
            ),
            'search' => array(
                'type' => 'string'
            ),
            'orderby' => array(
                'default' => 'date',
                'enum' => array('date', 'title', 'signature_count')
            ),
            'order' => array(
                'default' => 'DESC',
                'enum' => array('ASC', 'DESC')
            )
        );
    }
    
    /**
     * Get signature schema
     */
    private function get_signature_schema() {
        return array(
            'first_name' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'last_name' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'user_email' => array(
                'required' => true,
                'type' => 'string',
                'format' => 'email',
                'sanitize_callback' => 'sanitize_email'
            ),
            'user_phone' => array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'user_location' => array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'comment' => array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_textarea_field'
            ),
            'is_anonymous' => array(
                'type' => 'boolean',
                'default' => false
            )
        );
    }
    
    /**
     * Check read permission
     */
    public function check_read_permission($request) {
        // Public endpoints - anyone can read
        return true;
    }
    
    /**
     * Check create signature permission
     */
    public function check_create_signature_permission($request) {
        // Anyone can create signatures
        return true;
    }
    
    /**
     * Check verify permission
     */
    public function check_verify_permission($request) {
        // Anyone with valid token can verify
        return true;
    }
    
    /**
     * Check analytics permission
     */
    public function check_analytics_permission($request) {
        // Only admins can access analytics
        return current_user_can('manage_options');
    }
    
    /**
     * Check campaigns permission
     */
    public function check_campaigns_permission($request) {
        // Only admins can access campaigns
        return current_user_can('manage_options');
    }
    
    /**
     * Check webhook permission
     */
    public function check_webhook_permission($request) {
        // Validate webhook secret if configured
        $webhook_secret = get_option('petition_webhook_secret');
        
        if (!$webhook_secret) {
            return true; // No secret configured, allow all
        }
        
        $signature = $request->get_header('X-Webhook-Signature');
        if (!$signature) {
            return false;
        }
        
        $payload = $request->get_body();
        $expected_signature = 'sha256=' . hash_hmac('sha256', $payload, $webhook_secret);
        
        return hash_equals($expected_signature, $signature);
    }
}
