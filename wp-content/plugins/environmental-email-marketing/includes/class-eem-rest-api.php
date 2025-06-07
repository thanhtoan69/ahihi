<?php
/**
 * Environmental Email Marketing REST API
 * 
 * Provides REST API endpoints for external integrations,
 * third-party applications, and headless implementations.
 *
 * @package     EnvironmentalEmailMarketing
 * @subpackage  API
 * @version     1.0.0
 * @author      Environmental Email Marketing Team
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EEM_REST_API {
    
    /**
     * API version
     */
    const API_VERSION = 'v1';
    
    /**
     * API namespace
     */
    const API_NAMESPACE = 'environmental-email-marketing/v1';

    /**
     * Initialize REST API
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
        add_filter('rest_pre_dispatch', array($this, 'handle_cors'), 10, 3);
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Subscribers endpoints
        register_rest_route(self::API_NAMESPACE, '/subscribers', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_subscribers'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => $this->get_subscribers_args()
        ));

        register_rest_route(self::API_NAMESPACE, '/subscribers', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_subscriber'),
            'permission_callback' => array($this, 'check_api_permissions'),
            'args' => $this->get_create_subscriber_args()
        ));

        register_rest_route(self::API_NAMESPACE, '/subscribers/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_subscriber'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                )
            )
        ));

        register_rest_route(self::API_NAMESPACE, '/subscribers/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_subscriber'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => $this->get_update_subscriber_args()
        ));

        register_rest_route(self::API_NAMESPACE, '/subscribers/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_subscriber'),
            'permission_callback' => array($this, 'check_admin_permissions')
        ));

        // Campaigns endpoints
        register_rest_route(self::API_NAMESPACE, '/campaigns', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_campaigns'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => $this->get_campaigns_args()
        ));

        register_rest_route(self::API_NAMESPACE, '/campaigns', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_campaign'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => $this->get_create_campaign_args()
        ));

        register_rest_route(self::API_NAMESPACE, '/campaigns/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_campaign'),
            'permission_callback' => array($this, 'check_admin_permissions')
        ));

        register_rest_route(self::API_NAMESPACE, '/campaigns/(?P<id>\d+)/send', array(
            'methods' => 'POST',
            'callback' => array($this, 'send_campaign'),
            'permission_callback' => array($this, 'check_admin_permissions')
        ));

        // Lists endpoints
        register_rest_route(self::API_NAMESPACE, '/lists', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_lists'),
            'permission_callback' => array($this, 'check_api_permissions')
        ));

        register_rest_route(self::API_NAMESPACE, '/lists', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_list'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => $this->get_create_list_args()
        ));

        // Analytics endpoints
        register_rest_route(self::API_NAMESPACE, '/analytics/overview', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_analytics_overview'),
            'permission_callback' => array($this, 'check_admin_permissions')
        ));

        register_rest_route(self::API_NAMESPACE, '/analytics/campaigns/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_campaign_analytics'),
            'permission_callback' => array($this, 'check_admin_permissions')
        ));

        // Webhooks endpoints
        register_rest_route(self::API_NAMESPACE, '/webhooks/mailchimp', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_mailchimp_webhook'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route(self::API_NAMESPACE, '/webhooks/sendgrid', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_sendgrid_webhook'),
            'permission_callback' => '__return_true'
        ));

        // Public endpoints
        register_rest_route(self::API_NAMESPACE, '/subscribe', array(
            'methods' => 'POST',
            'callback' => array($this, 'public_subscribe'),
            'permission_callback' => '__return_true',
            'args' => $this->get_subscribe_args()
        ));

        register_rest_route(self::API_NAMESPACE, '/unsubscribe', array(
            'methods' => 'POST',
            'callback' => array($this, 'public_unsubscribe'),
            'permission_callback' => '__return_true',
            'args' => $this->get_unsubscribe_args()
        ));

        // Environmental tracking endpoints
        register_rest_route(self::API_NAMESPACE, '/track/environmental-action', array(
            'methods' => 'POST',
            'callback' => array($this, 'track_environmental_action'),
            'permission_callback' => array($this, 'check_api_permissions'),
            'args' => $this->get_track_action_args()
        ));
    }

    /**
     * Handle CORS for cross-origin requests
     */
    public function handle_cors($result, $server, $request) {
        if (strpos($request->get_route(), '/environmental-email-marketing/') !== false) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-WP-Nonce');
            
            if ($request->get_method() === 'OPTIONS') {
                exit;
            }
        }
        
        return $result;
    }

    /**
     * Check admin permissions
     */
    public function check_admin_permissions() {
        return current_user_can('manage_options');
    }

    /**
     * Check API permissions (admin or API key)
     */
    public function check_api_permissions($request) {
        // Check for admin permissions first
        if (current_user_can('manage_options')) {
            return true;
        }

        // Check for API key
        $api_key = $request->get_header('X-API-Key');
        if (!$api_key) {
            $api_key = $request->get_param('api_key');
        }

        if ($api_key) {
            $stored_key = get_option('eem_api_key');
            return hash_equals($stored_key, $api_key);
        }

        return false;
    }

    /**
     * Get subscribers
     */
    public function get_subscribers($request) {
        $subscriber_manager = new EEM_Subscriber_Manager();
        
        $args = array(
            'limit' => $request->get_param('per_page') ?: 20,
            'offset' => ($request->get_param('page') ?: 1 - 1) * ($request->get_param('per_page') ?: 20),
            'status' => $request->get_param('status'),
            'search' => $request->get_param('search')
        );

        $subscribers = $subscriber_manager->get_subscribers($args);
        $total = $subscriber_manager->count_subscribers($args);

        return new WP_REST_Response(array(
            'subscribers' => $subscribers,
            'total' => $total,
            'page' => $request->get_param('page') ?: 1,
            'per_page' => $request->get_param('per_page') ?: 20
        ), 200);
    }

    /**
     * Create subscriber
     */
    public function create_subscriber($request) {
        $subscriber_manager = new EEM_Subscriber_Manager();
        
        $data = array(
            'email' => sanitize_email($request->get_param('email')),
            'first_name' => sanitize_text_field($request->get_param('first_name')),
            'last_name' => sanitize_text_field($request->get_param('last_name')),
            'status' => sanitize_text_field($request->get_param('status') ?: 'pending'),
            'source' => sanitize_text_field($request->get_param('source') ?: 'api'),
            'lists' => array_map('intval', $request->get_param('lists') ?: array()),
            'interests' => array_map('sanitize_text_field', $request->get_param('interests') ?: array()),
            'preferences' => $request->get_param('preferences') ?: array()
        );

        if (!is_email($data['email'])) {
            return new WP_Error('invalid_email', __('Invalid email address', 'environmental-email-marketing'), array('status' => 400));
        }

        $result = $subscriber_manager->add_subscriber($data);

        if ($result) {
            $subscriber = $subscriber_manager->get_subscriber($result);
            return new WP_REST_Response($subscriber, 201);
        } else {
            return new WP_Error('creation_failed', __('Failed to create subscriber', 'environmental-email-marketing'), array('status' => 500));
        }
    }

    /**
     * Get single subscriber
     */
    public function get_subscriber($request) {
        $subscriber_manager = new EEM_Subscriber_Manager();
        $subscriber = $subscriber_manager->get_subscriber($request->get_param('id'));

        if (!$subscriber) {
            return new WP_Error('not_found', __('Subscriber not found', 'environmental-email-marketing'), array('status' => 404));
        }

        return new WP_REST_Response($subscriber, 200);
    }

    /**
     * Update subscriber
     */
    public function update_subscriber($request) {
        $subscriber_manager = new EEM_Subscriber_Manager();
        $id = $request->get_param('id');

        $data = array();
        $allowed_fields = array('first_name', 'last_name', 'status', 'lists', 'interests', 'preferences');

        foreach ($allowed_fields as $field) {
            if ($request->has_param($field)) {
                $data[$field] = $request->get_param($field);
            }
        }

        if (empty($data)) {
            return new WP_Error('no_data', __('No data provided for update', 'environmental-email-marketing'), array('status' => 400));
        }

        $result = $subscriber_manager->update_subscriber($id, $data);

        if ($result) {
            $subscriber = $subscriber_manager->get_subscriber($id);
            return new WP_REST_Response($subscriber, 200);
        } else {
            return new WP_Error('update_failed', __('Failed to update subscriber', 'environmental-email-marketing'), array('status' => 500));
        }
    }

    /**
     * Delete subscriber
     */
    public function delete_subscriber($request) {
        $subscriber_manager = new EEM_Subscriber_Manager();
        $result = $subscriber_manager->delete_subscriber($request->get_param('id'));

        if ($result) {
            return new WP_REST_Response(array('deleted' => true), 200);
        } else {
            return new WP_Error('deletion_failed', __('Failed to delete subscriber', 'environmental-email-marketing'), array('status' => 500));
        }
    }

    /**
     * Get campaigns
     */
    public function get_campaigns($request) {
        $campaign_manager = new EEM_Campaign_Manager();
        
        $args = array(
            'limit' => $request->get_param('per_page') ?: 20,
            'offset' => ($request->get_param('page') ?: 1 - 1) * ($request->get_param('per_page') ?: 20),
            'status' => $request->get_param('status'),
            'type' => $request->get_param('type')
        );

        $campaigns = $campaign_manager->get_campaigns($args);
        $total = $campaign_manager->count_campaigns($args);

        return new WP_REST_Response(array(
            'campaigns' => $campaigns,
            'total' => $total,
            'page' => $request->get_param('page') ?: 1,
            'per_page' => $request->get_param('per_page') ?: 20
        ), 200);
    }

    /**
     * Create campaign
     */
    public function create_campaign($request) {
        $campaign_manager = new EEM_Campaign_Manager();
        
        $data = array(
            'name' => sanitize_text_field($request->get_param('name')),
            'subject' => sanitize_text_field($request->get_param('subject')),
            'content' => wp_kses_post($request->get_param('content')),
            'type' => sanitize_text_field($request->get_param('type') ?: 'newsletter'),
            'status' => sanitize_text_field($request->get_param('status') ?: 'draft'),
            'lists' => array_map('intval', $request->get_param('lists') ?: array()),
            'template_id' => intval($request->get_param('template_id')),
            'settings' => $request->get_param('settings') ?: array()
        );

        if (empty($data['name']) || empty($data['subject'])) {
            return new WP_Error('missing_data', __('Campaign name and subject are required', 'environmental-email-marketing'), array('status' => 400));
        }

        $result = $campaign_manager->create_campaign($data);

        if ($result) {
            $campaign = $campaign_manager->get_campaign($result);
            return new WP_REST_Response($campaign, 201);
        } else {
            return new WP_Error('creation_failed', __('Failed to create campaign', 'environmental-email-marketing'), array('status' => 500));
        }
    }

    /**
     * Get single campaign
     */
    public function get_campaign($request) {
        $campaign_manager = new EEM_Campaign_Manager();
        $campaign = $campaign_manager->get_campaign($request->get_param('id'));

        if (!$campaign) {
            return new WP_Error('not_found', __('Campaign not found', 'environmental-email-marketing'), array('status' => 404));
        }

        return new WP_REST_Response($campaign, 200);
    }

    /**
     * Send campaign
     */
    public function send_campaign($request) {
        $campaign_manager = new EEM_Campaign_Manager();
        $campaign_id = $request->get_param('id');
        
        $schedule_time = $request->get_param('schedule_time');
        
        if ($schedule_time) {
            $result = $campaign_manager->schedule_campaign($campaign_id, $schedule_time);
        } else {
            $result = $campaign_manager->send_campaign($campaign_id);
        }

        if ($result) {
            return new WP_REST_Response(array(
                'success' => true,
                'message' => $schedule_time ? __('Campaign scheduled successfully', 'environmental-email-marketing') : __('Campaign sent successfully', 'environmental-email-marketing')
            ), 200);
        } else {
            return new WP_Error('send_failed', __('Failed to send campaign', 'environmental-email-marketing'), array('status' => 500));
        }
    }

    /**
     * Get lists
     */
    public function get_lists($request) {
        global $wpdb;
        
        $lists = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}eem_lists ORDER BY name ASC",
            ARRAY_A
        );

        return new WP_REST_Response($lists, 200);
    }

    /**
     * Create list
     */
    public function create_list($request) {
        global $wpdb;
        
        $data = array(
            'name' => sanitize_text_field($request->get_param('name')),
            'description' => sanitize_textarea_field($request->get_param('description')),
            'created_at' => current_time('mysql')
        );

        if (empty($data['name'])) {
            return new WP_Error('missing_name', __('List name is required', 'environmental-email-marketing'), array('status' => 400));
        }

        $result = $wpdb->insert($wpdb->prefix . 'eem_lists', $data);

        if ($result) {
            $list = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}eem_lists WHERE id = %d",
                $wpdb->insert_id
            ), ARRAY_A);
            
            return new WP_REST_Response($list, 201);
        } else {
            return new WP_Error('creation_failed', __('Failed to create list', 'environmental-email-marketing'), array('status' => 500));
        }
    }

    /**
     * Get analytics overview
     */
    public function get_analytics_overview($request) {
        $analytics = new EEM_Analytics_Tracker();
        $overview = $analytics->get_overview_stats();

        return new WP_REST_Response($overview, 200);
    }

    /**
     * Get campaign analytics
     */
    public function get_campaign_analytics($request) {
        $analytics = new EEM_Analytics_Tracker();
        $campaign_stats = $analytics->get_campaign_stats($request->get_param('id'));

        if (!$campaign_stats) {
            return new WP_Error('not_found', __('Campaign analytics not found', 'environmental-email-marketing'), array('status' => 404));
        }

        return new WP_REST_Response($campaign_stats, 200);
    }

    /**
     * Handle Mailchimp webhook
     */
    public function handle_mailchimp_webhook($request) {
        $mailchimp_provider = new EEM_Mailchimp_Provider();
        $result = $mailchimp_provider->handle_webhook($request->get_body());

        return new WP_REST_Response(array('processed' => $result), 200);
    }

    /**
     * Handle SendGrid webhook
     */
    public function handle_sendgrid_webhook($request) {
        $sendgrid_provider = new EEM_SendGrid_Provider();
        $result = $sendgrid_provider->handle_webhook($request->get_body());

        return new WP_REST_Response(array('processed' => $result), 200);
    }

    /**
     * Public subscribe endpoint
     */
    public function public_subscribe($request) {
        $frontend = new EEM_Frontend();
        
        // Simulate AJAX request data
        $_POST = array(
            'email' => $request->get_param('email'),
            'first_name' => $request->get_param('first_name'),
            'last_name' => $request->get_param('last_name'),
            'lists' => $request->get_param('lists'),
            'interests' => $request->get_param('interests'),
            'source' => $request->get_param('source') ?: 'api',
            'nonce' => wp_create_nonce('eem_frontend_nonce')
        );

        ob_start();
        $frontend->handle_ajax_subscribe();
        $response = ob_get_clean();

        return new WP_REST_Response(json_decode($response, true), 200);
    }

    /**
     * Public unsubscribe endpoint
     */
    public function public_unsubscribe($request) {
        $subscriber_manager = new EEM_Subscriber_Manager();
        
        $email = sanitize_email($request->get_param('email'));
        $reason = sanitize_text_field($request->get_param('reason'));

        if (!is_email($email)) {
            return new WP_Error('invalid_email', __('Invalid email address', 'environmental-email-marketing'), array('status' => 400));
        }

        $result = $subscriber_manager->unsubscribe($email, $reason);

        if ($result) {
            return new WP_REST_Response(array('success' => true, 'message' => __('Successfully unsubscribed', 'environmental-email-marketing')), 200);
        } else {
            return new WP_Error('unsubscribe_failed', __('Failed to unsubscribe', 'environmental-email-marketing'), array('status' => 500));
        }
    }

    /**
     * Track environmental action
     */
    public function track_environmental_action($request) {
        $email = sanitize_email($request->get_param('email'));
        $action = sanitize_text_field($request->get_param('action'));
        $data = $request->get_param('data') ?: array();

        if (!is_email($email)) {
            return new WP_Error('invalid_email', __('Invalid email address', 'environmental-email-marketing'), array('status' => 400));
        }

        $subscriber_manager = new EEM_Subscriber_Manager();
        $subscriber = $subscriber_manager->get_subscriber_by_email($email);

        if (!$subscriber) {
            return new WP_Error('subscriber_not_found', __('Subscriber not found', 'environmental-email-marketing'), array('status' => 404));
        }

        // Update environmental score
        $score_update = $this->calculate_action_score($action, $data);
        $new_score = $subscriber['environmental_score'] + $score_update;
        
        $subscriber_manager->update_subscriber($subscriber['id'], array(
            'environmental_score' => $new_score
        ));

        // Track the action
        $analytics = new EEM_Analytics_Tracker();
        $analytics->track_event('environmental_action', array(
            'subscriber_id' => $subscriber['id'],
            'action' => $action,
            'score_change' => $score_update,
            'data' => $data
        ));

        // Trigger automation if applicable
        $automation = new EEM_Automation_Engine();
        $automation->trigger_automation('environmental_action', array(
            'subscriber_id' => $subscriber['id'],
            'action' => $action,
            'data' => $data
        ));

        return new WP_REST_Response(array(
            'success' => true,
            'score_change' => $score_update,
            'new_score' => $new_score
        ), 200);
    }

    /**
     * Calculate environmental action score
     */
    private function calculate_action_score($action, $data) {
        $score_map = array(
            'petition_signed' => 10,
            'event_attended' => 15,
            'quiz_completed' => 5,
            'product_purchased' => 20,
            'article_shared' => 3,
            'donation_made' => 25,
            'volunteer_signup' => 30
        );

        $base_score = $score_map[$action] ?? 1;
        
        // Apply multipliers based on data
        if (isset($data['impact_level'])) {
            switch ($data['impact_level']) {
                case 'high':
                    $base_score *= 2;
                    break;
                case 'medium':
                    $base_score *= 1.5;
                    break;
            }
        }

        return $base_score;
    }

    /**
     * Get subscribers endpoint arguments
     */
    private function get_subscribers_args() {
        return array(
            'page' => array(
                'default' => 1,
                'sanitize_callback' => 'absint'
            ),
            'per_page' => array(
                'default' => 20,
                'sanitize_callback' => 'absint'
            ),
            'status' => array(
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'search' => array(
                'sanitize_callback' => 'sanitize_text_field'
            )
        );
    }

    /**
     * Get create subscriber arguments
     */
    private function get_create_subscriber_args() {
        return array(
            'email' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_email',
                'validate_callback' => 'is_email'
            ),
            'first_name' => array(
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'last_name' => array(
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'status' => array(
                'default' => 'pending',
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'source' => array(
                'default' => 'api',
                'sanitize_callback' => 'sanitize_text_field'
            )
        );
    }

    /**
     * Get update subscriber arguments
     */
    private function get_update_subscriber_args() {
        return array(
            'first_name' => array(
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'last_name' => array(
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'status' => array(
                'sanitize_callback' => 'sanitize_text_field'
            )
        );
    }

    /**
     * Get campaigns arguments
     */
    private function get_campaigns_args() {
        return array(
            'page' => array(
                'default' => 1,
                'sanitize_callback' => 'absint'
            ),
            'per_page' => array(
                'default' => 20,
                'sanitize_callback' => 'absint'
            ),
            'status' => array(
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'type' => array(
                'sanitize_callback' => 'sanitize_text_field'
            )
        );
    }

    /**
     * Get create campaign arguments
     */
    private function get_create_campaign_args() {
        return array(
            'name' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'subject' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'content' => array(
                'sanitize_callback' => 'wp_kses_post'
            ),
            'type' => array(
                'default' => 'newsletter',
                'sanitize_callback' => 'sanitize_text_field'
            )
        );
    }

    /**
     * Get create list arguments
     */
    private function get_create_list_args() {
        return array(
            'name' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'description' => array(
                'sanitize_callback' => 'sanitize_textarea_field'
            )
        );
    }

    /**
     * Get subscribe arguments
     */
    private function get_subscribe_args() {
        return array(
            'email' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_email',
                'validate_callback' => 'is_email'
            ),
            'first_name' => array(
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'last_name' => array(
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'source' => array(
                'default' => 'api',
                'sanitize_callback' => 'sanitize_text_field'
            )
        );
    }

    /**
     * Get unsubscribe arguments
     */
    private function get_unsubscribe_args() {
        return array(
            'email' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_email',
                'validate_callback' => 'is_email'
            ),
            'reason' => array(
                'sanitize_callback' => 'sanitize_text_field'
            )
        );
    }

    /**
     * Get track action arguments
     */
    private function get_track_action_args() {
        return array(
            'email' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_email',
                'validate_callback' => 'is_email'
            ),
            'action' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field'
            )
        );
    }
}

// Initialize REST API
new EEM_REST_API();
