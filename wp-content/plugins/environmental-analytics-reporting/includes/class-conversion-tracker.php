<?php
/**
 * Conversion Tracker for Environmental Analytics
 * 
 * Handles conversion goal management, funnel analysis, and goal tracking
 * for environmental actions and user engagement metrics.
 * 
 * @package Environmental_Analytics
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Conversion_Tracker {    
    private $db_manager;
    private $tracking_manager;
    
    /**
     * Constructor
     */
    public function __construct($db_manager, $tracking_manager) {
        $this->db_manager = $db_manager;
        $this->tracking_manager = $tracking_manager;
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Track conversions automatically
        add_action('env_donation_completed', array($this, 'track_donation_conversion'), 10, 2);
        add_action('env_petition_signed', array($this, 'track_petition_conversion'), 10, 2);
        add_action('env_item_exchanged', array($this, 'track_exchange_conversion'), 10, 2);
        add_action('env_user_registered', array($this, 'track_registration_conversion'), 10, 2);
        add_action('env_voucher_redeemed', array($this, 'track_voucher_conversion'), 10, 2);
        add_action('env_forum_post_created', array($this, 'track_engagement_conversion'), 10, 2);
        add_action('env_achievement_unlocked', array($this, 'track_achievement_conversion'), 10, 2);
        
        // Admin hooks
        add_action('wp_ajax_env_create_conversion_goal', array($this, 'ajax_create_goal'));
        add_action('wp_ajax_env_update_conversion_goal', array($this, 'ajax_update_goal'));
        add_action('wp_ajax_env_delete_conversion_goal', array($this, 'ajax_delete_goal'));
        add_action('wp_ajax_env_get_conversion_data', array($this, 'ajax_get_conversion_data'));
        add_action('wp_ajax_env_get_funnel_data', array($this, 'ajax_get_funnel_data'));
    }
    
    /**
     * Create a new conversion goal
     */
    public function create_goal($goal_data) {
        global $wpdb;
        
        $defaults = array(
            'name' => '',
            'description' => '',
            'type' => 'action', // action, page_view, time_spent, value
            'target_action' => '',
            'target_value' => 0,
            'target_url' => '',
            'value_per_conversion' => 0,
            'is_active' => 1,
            'funnel_steps' => array(),
            'attribution_window' => 30, // days
            'created_by' => get_current_user_id()
        );
        
        $goal_data = wp_parse_args($goal_data, $defaults);
        
        // Validate required fields
        if (empty($goal_data['name']) || empty($goal_data['type'])) {
            return new WP_Error('invalid_data', 'Goal name and type are required');
        }
        
        $table_name = $wpdb->prefix . 'env_conversion_goals';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'name' => sanitize_text_field($goal_data['name']),
                'description' => sanitize_textarea_field($goal_data['description']),
                'type' => sanitize_text_field($goal_data['type']),
                'target_action' => sanitize_text_field($goal_data['target_action']),
                'target_value' => floatval($goal_data['target_value']),
                'target_url' => esc_url_raw($goal_data['target_url']),
                'value_per_conversion' => floatval($goal_data['value_per_conversion']),
                'is_active' => intval($goal_data['is_active']),
                'funnel_steps' => json_encode($goal_data['funnel_steps']),
                'attribution_window' => intval($goal_data['attribution_window']),
                'created_by' => intval($goal_data['created_by']),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%f', '%s', '%f', '%d', '%s', '%d', '%d', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to create conversion goal');
        }
        
        $goal_id = $wpdb->insert_id;
        
        // Log the goal creation
        $this->tracking_manager->track_event('conversion_goal_created', array(
            'goal_id' => $goal_id,
            'goal_name' => $goal_data['name'],
            'goal_type' => $goal_data['type']
        ));
        
        return $goal_id;
    }
    
    /**
     * Track a conversion
     */
    public function track_conversion($goal_id, $user_id = null, $session_id = null, $conversion_data = array()) {
        global $wpdb;
        
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$session_id) {
            $session_id = $this->tracking_manager->get_current_session_id();
        }
        
        // Get goal details
        $goal = $this->get_goal($goal_id);
        if (!$goal) {
            return false;
        }
        
        // Check if this conversion already exists (prevent duplicates)
        $existing_conversion = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}env_conversion_tracking 
             WHERE goal_id = %d AND user_id = %d AND session_id = %s 
             AND conversion_date > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            $goal_id, $user_id, $session_id
        ));
        
        if ($existing_conversion) {
            return false; // Prevent duplicate conversions within 1 hour
        }
        
        // Get attribution data from session
        $attribution_data = $this->get_attribution_data($session_id, $goal->attribution_window);
        
        $table_name = $wpdb->prefix . 'env_conversion_tracking';
        
        $conversion_value = isset($conversion_data['value']) ? 
            floatval($conversion_data['value']) : $goal->value_per_conversion;
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'goal_id' => intval($goal_id),
                'user_id' => intval($user_id),
                'session_id' => sanitize_text_field($session_id),
                'conversion_value' => $conversion_value,
                'conversion_data' => json_encode($conversion_data),
                'attribution_source' => $attribution_data['source'],
                'attribution_medium' => $attribution_data['medium'],
                'attribution_campaign' => $attribution_data['campaign'],
                'attribution_path' => json_encode($attribution_data['path']),
                'conversion_date' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return false;
        }
        
        $conversion_id = $wpdb->insert_id;
        
        // Update goal statistics
        $this->update_goal_stats($goal_id);
        
        // Track the conversion event
        $this->tracking_manager->track_event('conversion_completed', array(
            'goal_id' => $goal_id,
            'goal_name' => $goal->name,
            'conversion_value' => $conversion_value,
            'conversion_id' => $conversion_id
        ));
        
        return $conversion_id;
    }
    
    /**
     * Track donation conversion
     */
    public function track_donation_conversion($donation_id, $amount) {
        $goal = $this->get_goal_by_action('donation_completed');
        if ($goal) {
            $this->track_conversion($goal->id, null, null, array(
                'donation_id' => $donation_id,
                'value' => $amount,
                'action_type' => 'donation'
            ));
        }
    }
    
    /**
     * Track petition signing conversion
     */
    public function track_petition_conversion($petition_id, $user_id) {
        $goal = $this->get_goal_by_action('petition_signed');
        if ($goal) {
            $this->track_conversion($goal->id, $user_id, null, array(
                'petition_id' => $petition_id,
                'action_type' => 'petition'
            ));
        }
    }
    
    /**
     * Track item exchange conversion
     */
    public function track_exchange_conversion($exchange_id, $item_value) {
        $goal = $this->get_goal_by_action('item_exchanged');
        if ($goal) {
            $this->track_conversion($goal->id, null, null, array(
                'exchange_id' => $exchange_id,
                'value' => $item_value,
                'action_type' => 'exchange'
            ));
        }
    }
    
    /**
     * Track user registration conversion
     */
    public function track_registration_conversion($user_id, $registration_source) {
        $goal = $this->get_goal_by_action('user_registered');
        if ($goal) {
            $this->track_conversion($goal->id, $user_id, null, array(
                'registration_source' => $registration_source,
                'action_type' => 'registration'
            ));
        }
    }
    
    /**
     * Track voucher redemption conversion
     */
    public function track_voucher_conversion($voucher_id, $voucher_value) {
        $goal = $this->get_goal_by_action('voucher_redeemed');
        if ($goal) {
            $this->track_conversion($goal->id, null, null, array(
                'voucher_id' => $voucher_id,
                'value' => $voucher_value,
                'action_type' => 'voucher'
            ));
        }
    }
    
    /**
     * Track engagement conversion (forum posts, etc.)
     */
    public function track_engagement_conversion($post_id, $user_id) {
        $goal = $this->get_goal_by_action('forum_engagement');
        if ($goal) {
            $this->track_conversion($goal->id, $user_id, null, array(
                'post_id' => $post_id,
                'action_type' => 'forum_post'
            ));
        }
    }
    
    /**
     * Track achievement unlock conversion
     */
    public function track_achievement_conversion($achievement_id, $user_id) {
        $goal = $this->get_goal_by_action('achievement_unlocked');
        if ($goal) {
            $this->track_conversion($goal->id, $user_id, null, array(
                'achievement_id' => $achievement_id,
                'action_type' => 'achievement'
            ));
        }
    }
    
    /**
     * Get conversion goal by ID
     */
    public function get_goal($goal_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}env_conversion_goals WHERE id = %d",
            $goal_id
        ));
    }
    
    /**
     * Get conversion goal by target action
     */
    public function get_goal_by_action($action) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}env_conversion_goals 
             WHERE target_action = %s AND is_active = 1 
             ORDER BY created_at DESC LIMIT 1",
            $action
        ));
    }
    
    /**
     * Get all active conversion goals
     */
    public function get_active_goals() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}env_conversion_goals 
             WHERE is_active = 1 
             ORDER BY name ASC"
        );
    }
    
    /**
     * Get conversion data for a goal
     */
    public function get_conversion_data($goal_id, $date_from = null, $date_to = null) {
        global $wpdb;
        
        if (!$date_from) {
            $date_from = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$date_to) {
            $date_to = date('Y-m-d');
        }
        
        $conversions = $wpdb->get_results($wpdb->prepare(
            "SELECT ct.*, cg.name as goal_name, cg.type as goal_type
             FROM {$wpdb->prefix}env_conversion_tracking ct
             JOIN {$wpdb->prefix}env_conversion_goals cg ON ct.goal_id = cg.id
             WHERE ct.goal_id = %d 
             AND DATE(ct.conversion_date) BETWEEN %s AND %s
             ORDER BY ct.conversion_date DESC",
            $goal_id, $date_from, $date_to
        ));
        
        // Calculate conversion metrics
        $total_conversions = count($conversions);
        $total_value = array_sum(array_column($conversions, 'conversion_value'));
        $unique_users = count(array_unique(array_column($conversions, 'user_id')));
        
        // Group by date for trend analysis
        $daily_conversions = array();
        foreach ($conversions as $conversion) {
            $date = date('Y-m-d', strtotime($conversion->conversion_date));
            if (!isset($daily_conversions[$date])) {
                $daily_conversions[$date] = array(
                    'date' => $date,
                    'conversions' => 0,
                    'value' => 0,
                    'unique_users' => array()
                );
            }
            $daily_conversions[$date]['conversions']++;
            $daily_conversions[$date]['value'] += $conversion->conversion_value;
            $daily_conversions[$date]['unique_users'][] = $conversion->user_id;
        }
        
        // Convert unique users arrays to counts
        foreach ($daily_conversions as &$day) {
            $day['unique_users'] = count(array_unique($day['unique_users']));
        }
        
        return array(
            'goal_id' => $goal_id,
            'total_conversions' => $total_conversions,
            'total_value' => $total_value,
            'unique_users' => $unique_users,
            'average_value' => $total_conversions > 0 ? $total_value / $total_conversions : 0,
            'daily_data' => array_values($daily_conversions),
            'conversions' => $conversions
        );
    }
    
    /**
     * Get funnel analysis data
     */
    public function get_funnel_data($goal_id, $date_from = null, $date_to = null) {
        $goal = $this->get_goal($goal_id);
        if (!$goal || empty($goal->funnel_steps)) {
            return array();
        }
        
        $funnel_steps = json_decode($goal->funnel_steps, true);
        if (!is_array($funnel_steps)) {
            return array();
        }
        
        if (!$date_from) {
            $date_from = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$date_to) {
            $date_to = date('Y-m-d');
        }
        
        global $wpdb;
        $funnel_data = array();
        
        foreach ($funnel_steps as $index => $step) {
            $step_name = $step['name'];
            $step_action = $step['action'];
            
            // Count users who completed this step
            $users_completed = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT user_id) 
                 FROM {$wpdb->prefix}env_analytics_events 
                 WHERE event_action = %s 
                 AND DATE(event_date) BETWEEN %s AND %s",
                $step_action, $date_from, $date_to
            ));
            
            $funnel_data[] = array(
                'step_index' => $index + 1,
                'step_name' => $step_name,
                'step_action' => $step_action,
                'users_completed' => intval($users_completed),
                'conversion_rate' => 0 // Will be calculated after all steps
            );
        }
        
        // Calculate conversion rates
        if (!empty($funnel_data)) {
            $first_step_users = $funnel_data[0]['users_completed'];
            
            foreach ($funnel_data as &$step) {
                if ($first_step_users > 0) {
                    $step['conversion_rate'] = ($step['users_completed'] / $first_step_users) * 100;
                }
            }
        }
        
        return $funnel_data;
    }
    
    /**
     * Get attribution data for a session
     */
    private function get_attribution_data($session_id, $attribution_window_days) {
        global $wpdb;
        
        // Get session data
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}env_user_sessions 
             WHERE session_id = %s",
            $session_id
        ));
        
        if (!$session) {
            return array(
                'source' => 'direct',
                'medium' => 'none',
                'campaign' => '',
                'path' => array()
            );
        }
        
        // Get attribution path within the window
        $attribution_date = date('Y-m-d H:i:s', strtotime("-{$attribution_window_days} days"));
        
        $attribution_path = $wpdb->get_results($wpdb->prepare(
            "SELECT traffic_source, traffic_medium, traffic_campaign, session_start
             FROM {$wpdb->prefix}env_user_sessions 
             WHERE user_id = %d 
             AND session_start >= %s
             ORDER BY session_start ASC",
            $session->user_id, $attribution_date
        ));
        
        return array(
            'source' => $session->traffic_source ?: 'direct',
            'medium' => $session->traffic_medium ?: 'none',
            'campaign' => $session->traffic_campaign ?: '',
            'path' => $attribution_path
        );
    }
    
    /**
     * Update goal statistics
     */
    private function update_goal_stats($goal_id) {
        global $wpdb;
        
        // Calculate total conversions and value
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) as total_conversions, 
                    SUM(conversion_value) as total_value,
                    COUNT(DISTINCT user_id) as unique_users
             FROM {$wpdb->prefix}env_conversion_tracking 
             WHERE goal_id = %d",
            $goal_id
        ));
        
        // Update goal with latest stats
        $wpdb->update(
            $wpdb->prefix . 'env_conversion_goals',
            array(
                'total_conversions' => intval($stats->total_conversions),
                'total_value' => floatval($stats->total_value),
                'updated_at' => current_time('mysql')
            ),
            array('id' => $goal_id),
            array('%d', '%f', '%s'),
            array('%d')
        );
    }
    
    /**
     * AJAX: Create conversion goal
     */
    public function ajax_create_goal() {
        check_ajax_referer('env_analytics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $goal_data = array(
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'type' => sanitize_text_field($_POST['type']),
            'target_action' => sanitize_text_field($_POST['target_action']),
            'target_value' => floatval($_POST['target_value']),
            'target_url' => esc_url_raw($_POST['target_url']),
            'value_per_conversion' => floatval($_POST['value_per_conversion']),
            'attribution_window' => intval($_POST['attribution_window']),
            'funnel_steps' => isset($_POST['funnel_steps']) ? $_POST['funnel_steps'] : array()
        );
        
        $result = $this->create_goal($goal_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(array('goal_id' => $result));
        }
    }
    
    /**
     * AJAX: Get conversion data
     */
    public function ajax_get_conversion_data() {
        check_ajax_referer('env_analytics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $goal_id = intval($_POST['goal_id']);
        $date_from = sanitize_text_field($_POST['date_from']);
        $date_to = sanitize_text_field($_POST['date_to']);
        
        $data = $this->get_conversion_data($goal_id, $date_from, $date_to);
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX: Get funnel data
     */
    public function ajax_get_funnel_data() {
        check_ajax_referer('env_analytics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $goal_id = intval($_POST['goal_id']);
        $date_from = sanitize_text_field($_POST['date_from']);
        $date_to = sanitize_text_field($_POST['date_to']);
        
        $data = $this->get_funnel_data($goal_id, $date_from, $date_to);
        
        wp_send_json_success($data);
    }
    
    /**
     * Create default conversion goals
     */
    public function create_default_goals() {
        $default_goals = array(
            array(
                'name' => 'Environmental Donation',
                'description' => 'User completes a donation to environmental causes',
                'type' => 'action',
                'target_action' => 'donation_completed',
                'value_per_conversion' => 25.00,
                'funnel_steps' => array(
                    array('name' => 'View Donation Page', 'action' => 'page_view_donation'),
                    array('name' => 'Start Donation Process', 'action' => 'donation_started'),
                    array('name' => 'Complete Donation', 'action' => 'donation_completed')
                )
            ),
            array(
                'name' => 'Petition Signature',
                'description' => 'User signs an environmental petition',
                'type' => 'action',
                'target_action' => 'petition_signed',
                'value_per_conversion' => 5.00,
                'funnel_steps' => array(
                    array('name' => 'View Petition', 'action' => 'page_view_petition'),
                    array('name' => 'Sign Petition', 'action' => 'petition_signed')
                )
            ),
            array(
                'name' => 'Item Exchange',
                'description' => 'User completes an item exchange',
                'type' => 'action',
                'target_action' => 'item_exchanged',
                'value_per_conversion' => 15.00
            ),
            array(
                'name' => 'User Registration',
                'description' => 'New user completes registration',
                'type' => 'action',
                'target_action' => 'user_registered',
                'value_per_conversion' => 10.00,
                'funnel_steps' => array(
                    array('name' => 'View Registration Page', 'action' => 'page_view_register'),
                    array('name' => 'Complete Registration', 'action' => 'user_registered'),
                    array('name' => 'Email Verification', 'action' => 'email_verified')
                )
            ),
            array(
                'name' => 'Forum Engagement',
                'description' => 'User creates forum posts or engages in discussions',
                'type' => 'action',
                'target_action' => 'forum_engagement',
                'value_per_conversion' => 2.00
            )
        );
        
        foreach ($default_goals as $goal_data) {
            // Check if goal already exists
            $existing = $this->get_goal_by_action($goal_data['target_action']);
            if (!$existing) {
                $this->create_goal($goal_data);
            }
        }
    }
        $update_data = array();
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed_fields)) {
                $update_data[$key] = $value;
            }
        }
        
        if (!empty($update_data)) {
            return $this->wpdb->update($table_name, $update_data, array('id' => $goal_id));
        }
        
        return false;
    }
    
    /**
     * Get all conversion goals
     */
    public function get_goals($active_only = false) {
        $table_name = $this->wpdb->prefix . 'env_conversion_goals';
        
        $where_clause = $active_only ? "WHERE is_active = 1" : "";
        
        return $this->wpdb->get_results("SELECT * FROM $table_name $where_clause ORDER BY created_at DESC");
    }
    
    /**
     * Get conversion goal by ID
     */
    public function get_goal($goal_id) {
        $table_name = $this->wpdb->prefix . 'env_conversion_goals';
        
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $goal_id
        ));
    }
    
    /**
     * Get conversion statistics for a goal
     */
    public function get_goal_statistics($goal_id, $start_date, $end_date) {
        $tracking_table = $this->wpdb->prefix . 'env_conversion_tracking';
        $goals_table = $this->wpdb->prefix . 'env_conversion_goals';
        
        // Get basic conversion data
        $conversions = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT DATE(converted_at) as date, COUNT(*) as conversions, SUM(conversion_value) as total_value
             FROM $tracking_table 
             WHERE goal_id = %d AND converted_at >= %s AND converted_at <= %s
             GROUP BY DATE(converted_at)
             ORDER BY date ASC",
            $goal_id, $start_date, $end_date
        ));
        
        // Get total conversions and value
        $totals = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT COUNT(*) as total_conversions, 
                    SUM(conversion_value) as total_value,
                    AVG(conversion_value) as avg_value,
                    AVG(time_to_conversion) as avg_time_to_conversion
             FROM $tracking_table 
             WHERE goal_id = %d AND converted_at >= %s AND converted_at <= %s",
            $goal_id, $start_date, $end_date
        ));
        
        // Get conversion rate (conversions vs sessions)
        $sessions_table = $this->wpdb->prefix . 'env_user_sessions';
        $total_sessions = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM $sessions_table WHERE start_time >= %s AND start_time <= %s",
            $start_date, $end_date
        ));
        
        $conversion_rate = $total_sessions > 0 ? ($totals->total_conversions / $total_sessions) * 100 : 0;
        
        // Get top conversion sources
        $top_sources = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT JSON_EXTRACT(attribution_data, '$.traffic_source') as source,
                    COUNT(*) as conversions,
                    SUM(conversion_value) as total_value
             FROM $tracking_table 
             WHERE goal_id = %d AND converted_at >= %s AND converted_at <= %s
             GROUP BY JSON_EXTRACT(attribution_data, '$.traffic_source')
             ORDER BY conversions DESC
             LIMIT 10",
            $goal_id, $start_date, $end_date
        ));
        
        return array(
            'daily_conversions' => $conversions,
            'totals' => $totals,
            'conversion_rate' => round($conversion_rate, 2),
            'total_sessions' => $total_sessions,
            'top_sources' => $top_sources
        );
    }
    
    /**
     * Get conversion funnel analysis
     */
    public function get_conversion_funnel($start_date, $end_date, $steps = array()) {
        $events_table = $this->wpdb->prefix . 'env_analytics_events';
        $funnel_data = array();
        
        foreach ($steps as $index => $step) {
            $count = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT COUNT(DISTINCT user_id) 
                 FROM $events_table 
                 WHERE event_action = %s AND created_at >= %s AND created_at <= %s",
                $step['action'], $start_date, $end_date
            ));
            
            $funnel_data[] = array(
                'step' => $index + 1,
                'name' => $step['name'],
                'action' => $step['action'],
                'users' => intval($count),
                'drop_off_rate' => $index > 0 ? 
                    round((($funnel_data[$index - 1]['users'] - $count) / $funnel_data[$index - 1]['users']) * 100, 2) : 0
            );
        }
        
        return $funnel_data;
    }
    
    /**
     * Get attribution analysis
     */
    public function get_attribution_analysis($goal_id, $start_date, $end_date) {
        $tracking_table = $this->wpdb->prefix . 'env_conversion_tracking';
        
        // First-touch attribution
        $first_touch = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT JSON_EXTRACT(attribution_data, '$.traffic_source') as source,
                    COUNT(*) as conversions,
                    SUM(conversion_value) as total_value
             FROM $tracking_table 
             WHERE goal_id = %d AND converted_at >= %s AND converted_at <= %s
             GROUP BY JSON_EXTRACT(attribution_data, '$.traffic_source')
             ORDER BY conversions DESC",
            $goal_id, $start_date, $end_date
        ));
        
        // Last-touch attribution (same as above for now, could be enhanced)
        $last_touch = $first_touch;
        
        // Multi-touch attribution (simplified)
        $multi_touch = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT JSON_EXTRACT(attribution_data, '$.traffic_source') as source,
                    COUNT(*) as conversions,
                    SUM(conversion_value) as total_value,
                    AVG(time_to_conversion) as avg_time_to_conversion
             FROM $tracking_table 
             WHERE goal_id = %d AND converted_at >= %s AND converted_at <= %s
             GROUP BY JSON_EXTRACT(attribution_data, '$.traffic_source')
             ORDER BY conversions DESC",
            $goal_id, $start_date, $end_date
        ));
        
        return array(
            'first_touch' => $first_touch,
            'last_touch' => $last_touch,
            'multi_touch' => $multi_touch
        );
    }
    
    /**
     * Get conversion path analysis
     */
    public function get_conversion_paths($goal_id, $start_date, $end_date, $limit = 20) {
        $tracking_table = $this->wpdb->prefix . 'env_conversion_tracking';
        
        $paths = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT conversion_path, COUNT(*) as frequency, AVG(conversion_value) as avg_value
             FROM $tracking_table 
             WHERE goal_id = %d AND converted_at >= %s AND converted_at <= %s 
               AND conversion_path IS NOT NULL
             GROUP BY conversion_path
             ORDER BY frequency DESC
             LIMIT %d",
            $goal_id, $start_date, $end_date, $limit
        ));
        
        // Process paths to make them more readable
        $processed_paths = array();
        foreach ($paths as $path) {
            $path_data = json_decode($path->conversion_path, true);
            if (is_array($path_data)) {
                $path_summary = array_map(function($step) {
                    return parse_url($step['url'], PHP_URL_PATH);
                }, $path_data);
                
                $processed_paths[] = array(
                    'path' => implode(' → ', $path_summary),
                    'steps' => count($path_data),
                    'frequency' => intval($path->frequency),
                    'avg_value' => floatval($path->avg_value)
                );
            }
        }
        
        return $processed_paths;
    }
    
    /**
     * Get time-based conversion analysis
     */
    public function get_time_analysis($goal_id, $start_date, $end_date) {
        $tracking_table = $this->wpdb->prefix . 'env_conversion_tracking';
        
        // Hourly distribution
        $hourly = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT HOUR(converted_at) as hour, COUNT(*) as conversions, SUM(conversion_value) as total_value
             FROM $tracking_table 
             WHERE goal_id = %d AND converted_at >= %s AND converted_at <= %s
             GROUP BY HOUR(converted_at)
             ORDER BY hour ASC",
            $goal_id, $start_date, $end_date
        ));
        
        // Daily distribution
        $daily = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT DAYOFWEEK(converted_at) as day_of_week, COUNT(*) as conversions, SUM(conversion_value) as total_value
             FROM $tracking_table 
             WHERE goal_id = %d AND converted_at >= %s AND converted_at <= %s
             GROUP BY DAYOFWEEK(converted_at)
             ORDER BY day_of_week ASC",
            $goal_id, $start_date, $end_date
        ));
        
        // Time to conversion distribution
        $time_to_conversion = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT 
                CASE 
                    WHEN time_to_conversion <= 300 THEN '0-5 minutes'
                    WHEN time_to_conversion <= 1800 THEN '5-30 minutes'
                    WHEN time_to_conversion <= 3600 THEN '30-60 minutes'
                    WHEN time_to_conversion <= 86400 THEN '1-24 hours'
                    ELSE '24+ hours'
                END as time_range,
                COUNT(*) as conversions
             FROM $tracking_table 
             WHERE goal_id = %d AND converted_at >= %s AND converted_at <= %s
               AND time_to_conversion IS NOT NULL
             GROUP BY time_range
             ORDER BY conversions DESC",
            $goal_id, $start_date, $end_date
        ));
        
        return array(
            'hourly' => $hourly,
            'daily' => $daily,
            'time_to_conversion' => $time_to_conversion
        );
    }
    
    /**
     * Get cohort analysis for conversions
     */
    public function get_cohort_analysis($goal_id, $start_date, $end_date) {
        $tracking_table = $this->wpdb->prefix . 'env_conversion_tracking';
        $sessions_table = $this->wpdb->prefix . 'env_user_sessions';
        
        // Get conversion cohorts by registration date
        $cohorts = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT 
                DATE_FORMAT(u.user_registered, '%%Y-%%m') as cohort_month,
                COUNT(DISTINCT ct.user_id) as converting_users,
                COUNT(ct.id) as total_conversions,
                SUM(ct.conversion_value) as total_value
             FROM {$this->wpdb->users} u
             LEFT JOIN $tracking_table ct ON u.ID = ct.user_id 
                AND ct.goal_id = %d 
                AND ct.converted_at >= %s 
                AND ct.converted_at <= %s
             WHERE u.user_registered >= %s
             GROUP BY DATE_FORMAT(u.user_registered, '%%Y-%%m')
             ORDER BY cohort_month ASC",
            $goal_id, $start_date, $end_date, $start_date
        ));
        
        return $cohorts;
    }
    
    /**
     * Calculate conversion value optimization suggestions
     */
    public function get_optimization_suggestions($goal_id, $start_date, $end_date) {
        $suggestions = array();
        
        // Get goal statistics
        $stats = $this->get_goal_statistics($goal_id, $start_date, $end_date);
        
        // Low conversion rate suggestion
        if ($stats['conversion_rate'] < 2.0) {
            $suggestions[] = array(
                'type' => 'conversion_rate',
                'priority' => 'high',
                'title' => 'Low Conversion Rate Detected',
                'description' => "Your conversion rate is {$stats['conversion_rate']}%, which is below the typical 2-5% range.",
                'recommendation' => 'Consider A/B testing your call-to-action buttons, simplifying your conversion process, or improving page load times.'
            );
        }
        
        // Time to conversion analysis
        $time_analysis = $this->get_time_analysis($goal_id, $start_date, $end_date);
        $long_conversions = array_filter($time_analysis['time_to_conversion'], function($item) {
            return $item->time_range === '24+ hours';
        });
        
        if (!empty($long_conversions) && $long_conversions[0]->conversions > $stats['totals']->total_conversions * 0.3) {
            $suggestions[] = array(
                'type' => 'conversion_time',
                'priority' => 'medium',
                'title' => 'Long Conversion Times',
                'description' => 'Many users are taking more than 24 hours to convert.',
                'recommendation' => 'Implement email remarketing campaigns or push notifications to re-engage users who haven\'t completed the conversion process.'
            );
        }
        
        // Attribution analysis for optimization
        $attribution = $this->get_attribution_analysis($goal_id, $start_date, $end_date);
        $direct_traffic = array_filter($attribution['first_touch'], function($item) {
            return $item->source === '"direct"';
        });
        
        if (!empty($direct_traffic) && $direct_traffic[0]->conversions > $stats['totals']->total_conversions * 0.6) {
            $suggestions[] = array(
                'type' => 'traffic_source',
                'priority' => 'low',
                'title' => 'High Direct Traffic',
                'description' => 'Most conversions come from direct traffic.',
                'recommendation' => 'Consider investing in SEO and social media marketing to diversify your traffic sources and potentially increase overall conversions.'
            );
        }
        
        return $suggestions;
    }
    
    /**
     * Delete conversion goal
     */
    public function delete_goal($goal_id) {
        $table_name = $this->wpdb->prefix . 'env_conversion_goals';
        
        return $this->wpdb->delete($table_name, array('id' => $goal_id));
    }
    
    /**
     * Export conversion data
     */
    public function export_conversion_data($goal_id, $start_date, $end_date, $format = 'csv') {
        $tracking_table = $this->wpdb->prefix . 'env_conversion_tracking';
        $goals_table = $this->wpdb->prefix . 'env_conversion_goals';
        
        $data = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT ct.*, cg.name as goal_name, cg.goal_type,
                    u.user_login, u.user_email
             FROM $tracking_table ct
             LEFT JOIN $goals_table cg ON ct.goal_id = cg.id
             LEFT JOIN {$this->wpdb->users} u ON ct.user_id = u.ID
             WHERE ct.goal_id = %d AND ct.converted_at >= %s AND ct.converted_at <= %s
             ORDER BY ct.converted_at DESC",
            $goal_id, $start_date, $end_date
        ), ARRAY_A);
        
        if ($format === 'csv') {
            return $this->convert_to_csv($data);
        }
        
        return $data;
    }
    
    /**
     * Convert data to CSV format
     */
    private function convert_to_csv($data) {
        if (empty($data)) {
            return '';
        }
        
        $csv = '';
        
        // Headers
        $headers = array_keys($data[0]);
        $csv .= implode(',', $headers) . "\n";
        
        // Data rows
        foreach ($data as $row) {
            $csv .= implode(',', array_map(function($value) {
                return '"' . str_replace('"', '""', $value) . '"';
            }, $row)) . "\n";
        }
        
        return $csv;
    }
}
