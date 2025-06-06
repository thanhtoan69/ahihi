<?php
/**
 * Behavior Analytics for Environmental Analytics
 * 
 * Analyzes user behavior patterns, engagement metrics, and provides
 * insights into user interactions with environmental content.
 * 
 * @package Environmental_Analytics
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Behavior_Analytics {
    
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
        // Analyze behavior patterns daily
        add_action('env_daily_analytics_cron', array($this, 'analyze_daily_behavior'));
        
        // AJAX endpoints for behavior data
        add_action('wp_ajax_env_get_behavior_data', array($this, 'ajax_get_behavior_data'));
        add_action('wp_ajax_env_get_user_journey', array($this, 'ajax_get_user_journey'));
        add_action('wp_ajax_env_get_engagement_metrics', array($this, 'ajax_get_engagement_metrics'));
        add_action('wp_ajax_env_get_content_performance', array($this, 'ajax_get_content_performance'));
        add_action('wp_ajax_env_get_user_segments', array($this, 'ajax_get_user_segments'));
    }
    
    /**
     * Analyze user behavior patterns
     */
    public function analyze_user_behavior($user_id, $date_from = null, $date_to = null) {
        if (!$date_from) {
            $date_from = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$date_to) {
            $date_to = date('Y-m-d');
        }
        
        global $wpdb;
        
        // Get user activity data
        $activity_data = $wpdb->get_results($wpdb->prepare(
            "SELECT event_action, event_category, COUNT(*) as event_count,
                    AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.time_spent')) AS UNSIGNED)) as avg_time_spent,
                    DATE(event_date) as event_day
             FROM {$wpdb->prefix}env_analytics_events 
             WHERE user_id = %d 
             AND DATE(event_date) BETWEEN %s AND %s
             GROUP BY event_action, event_category, DATE(event_date)
             ORDER BY event_date DESC",
            $user_id, $date_from, $date_to
        ));
        
        // Get session data
        $session_data = $wpdb->get_results($wpdb->prepare(
            "SELECT session_id, session_start, session_end, 
                    TIMESTAMPDIFF(MINUTE, session_start, session_end) as session_duration,
                    page_views, device_type, browser, traffic_source
             FROM {$wpdb->prefix}env_user_sessions 
             WHERE user_id = %d 
             AND DATE(session_start) BETWEEN %s AND %s
             ORDER BY session_start DESC",
            $user_id, $date_from, $date_to
        ));
        
        // Calculate behavior metrics
        $behavior_metrics = $this->calculate_behavior_metrics($activity_data, $session_data);
        
        // Store behavior analysis
        $this->store_behavior_analysis($user_id, $behavior_metrics, $date_from, $date_to);
        
        return $behavior_metrics;
    }
    
    /**
     * Calculate behavior metrics
     */
    private function calculate_behavior_metrics($activity_data, $session_data) {
        $metrics = array(
            'total_sessions' => count($session_data),
            'total_events' => 0,
            'avg_session_duration' => 0,
            'total_page_views' => 0,
            'bounce_rate' => 0,
            'engagement_score' => 0,
            'preferred_device' => 'desktop',
            'preferred_browser' => 'chrome',
            'activity_patterns' => array(),
            'content_preferences' => array(),
            'environmental_interest_score' => 0
        );
        
        if (empty($session_data)) {
            return $metrics;
        }
        
        // Calculate session metrics
        $total_duration = 0;
        $bounced_sessions = 0;
        $total_page_views = 0;
        $device_counts = array();
        $browser_counts = array();
        
        foreach ($session_data as $session) {
            $total_duration += $session->session_duration;
            $total_page_views += $session->page_views;
            
            if ($session->page_views <= 1) {
                $bounced_sessions++;
            }
            
            // Count devices and browsers
            $device = $session->device_type ?: 'unknown';
            $browser = $session->browser ?: 'unknown';
            
            $device_counts[$device] = ($device_counts[$device] ?? 0) + 1;
            $browser_counts[$browser] = ($browser_counts[$browser] ?? 0) + 1;
        }
        
        $metrics['avg_session_duration'] = $total_duration / count($session_data);
        $metrics['total_page_views'] = $total_page_views;
        $metrics['bounce_rate'] = ($bounced_sessions / count($session_data)) * 100;
        
        // Determine preferred device and browser
        if (!empty($device_counts)) {
            $metrics['preferred_device'] = array_keys($device_counts, max($device_counts))[0];
        }
        if (!empty($browser_counts)) {
            $metrics['preferred_browser'] = array_keys($browser_counts, max($browser_counts))[0];
        }
        
        // Calculate activity patterns from events
        if (!empty($activity_data)) {
            $category_counts = array();
            $action_counts = array();
            $total_events = 0;
            
            foreach ($activity_data as $activity) {
                $total_events += $activity->event_count;
                $category_counts[$activity->event_category] = 
                    ($category_counts[$activity->event_category] ?? 0) + $activity->event_count;
                $action_counts[$activity->event_action] = 
                    ($action_counts[$activity->event_action] ?? 0) + $activity->event_count;
            }
            
            $metrics['total_events'] = $total_events;
            $metrics['activity_patterns'] = $category_counts;
            $metrics['content_preferences'] = $action_counts;
            
            // Calculate environmental interest score
            $environmental_actions = array(
                'donation_completed' => 10,
                'petition_signed' => 8,
                'forum_post_created' => 6,
                'item_exchanged' => 7,
                'achievement_unlocked' => 5,
                'page_view_environmental' => 2
            );
            
            $env_score = 0;
            foreach ($action_counts as $action => $count) {
                if (isset($environmental_actions[$action])) {
                    $env_score += $environmental_actions[$action] * $count;
                }
            }
            
            $metrics['environmental_interest_score'] = min(100, $env_score);
        }
        
        // Calculate overall engagement score
        $engagement_factors = array(
            'session_duration' => min(10, $metrics['avg_session_duration'] / 5), // Max 10 points
            'page_views' => min(10, $total_page_views / 10), // Max 10 points
            'bounce_rate' => 10 - ($metrics['bounce_rate'] / 10), // Inverse of bounce rate
            'event_frequency' => min(10, $metrics['total_events'] / 5), // Max 10 points
            'environmental_interest' => $metrics['environmental_interest_score'] / 10 // Max 10 points
        );
        
        $metrics['engagement_score'] = array_sum($engagement_factors);
        
        return $metrics;
    }
    
    /**
     * Store behavior analysis results
     */
    private function store_behavior_analysis($user_id, $metrics, $date_from, $date_to) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'env_user_behavior';
        
        // Check if analysis already exists for this period
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_name} 
             WHERE user_id = %d AND analysis_period_start = %s AND analysis_period_end = %s",
            $user_id, $date_from, $date_to
        ));
        
        $behavior_data = array(
            'user_id' => $user_id,
            'analysis_period_start' => $date_from,
            'analysis_period_end' => $date_to,
            'total_sessions' => $metrics['total_sessions'],
            'avg_session_duration' => $metrics['avg_session_duration'],
            'total_page_views' => $metrics['total_page_views'],
            'bounce_rate' => $metrics['bounce_rate'],
            'engagement_score' => $metrics['engagement_score'],
            'environmental_interest_score' => $metrics['environmental_interest_score'],
            'preferred_device' => $metrics['preferred_device'],
            'preferred_browser' => $metrics['preferred_browser'],
            'behavior_patterns' => json_encode($metrics['activity_patterns']),
            'content_preferences' => json_encode($metrics['content_preferences']),
            'analyzed_at' => current_time('mysql')
        );
        
        if ($existing) {
            // Update existing analysis
            $wpdb->update(
                $table_name,
                $behavior_data,
                array('id' => $existing),
                array('%d', '%s', '%s', '%d', '%f', '%d', '%f', '%f', '%f', '%s', '%s', '%s', '%s', '%s'),
                array('%d')
            );
        } else {
            // Insert new analysis
            $wpdb->insert(
                $table_name,
                $behavior_data,
                array('%d', '%s', '%s', '%d', '%f', '%d', '%f', '%f', '%f', '%s', '%s', '%s', '%s', '%s')
            );
        }
    }
    
    /**
     * Get user journey data
     */
    public function get_user_journey($user_id, $session_id = null, $limit = 50) {
        global $wpdb;
        
        $where_clause = "WHERE user_id = %d";
        $params = array($user_id);
        
        if ($session_id) {
            $where_clause .= " AND session_id = %s";
            $params[] = $session_id;
        }
        
        $journey_data = $wpdb->get_results($wpdb->prepare(
            "SELECT event_action, event_category, event_data, event_date, session_id,
                    page_url, referrer_url
             FROM {$wpdb->prefix}env_analytics_events 
             {$where_clause}
             ORDER BY event_date DESC
             LIMIT %d",
            array_merge($params, array($limit))
        ));
        
        // Process journey data
        $processed_journey = array();
        $current_session = null;
        $session_events = array();
        
        foreach ($journey_data as $event) {
            if ($current_session !== $event->session_id) {
                if (!empty($session_events)) {
                    $processed_journey[] = array(
                        'session_id' => $current_session,
                        'events' => array_reverse($session_events),
                        'event_count' => count($session_events)
                    );
                }
                $current_session = $event->session_id;
                $session_events = array();
            }
            
            $event_data = json_decode($event->event_data, true);
            $session_events[] = array(
                'action' => $event->event_action,
                'category' => $event->event_category,
                'timestamp' => $event->event_date,
                'page_url' => $event->page_url,
                'referrer' => $event->referrer_url,
                'data' => $event_data
            );
        }
        
        // Add the last session
        if (!empty($session_events)) {
            $processed_journey[] = array(
                'session_id' => $current_session,
                'events' => array_reverse($session_events),
                'event_count' => count($session_events)
            );
        }
        
        return $processed_journey;
    }
    
    /**
     * Get engagement metrics for dashboard
     */
    public function get_engagement_metrics($date_from = null, $date_to = null) {
        if (!$date_from) {
            $date_from = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$date_to) {
            $date_to = date('Y-m-d');
        }
        
        global $wpdb;
        
        // Get overall engagement stats
        $engagement_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(DISTINCT user_id) as active_users,
                AVG(engagement_score) as avg_engagement_score,
                AVG(environmental_interest_score) as avg_environmental_interest,
                AVG(bounce_rate) as avg_bounce_rate,
                AVG(avg_session_duration) as avg_session_duration,
                SUM(total_page_views) as total_page_views,
                SUM(total_sessions) as total_sessions
             FROM {$wpdb->prefix}env_user_behavior 
             WHERE DATE(analyzed_at) BETWEEN %s AND %s",
            $date_from, $date_to
        ));
        
        // Get engagement distribution
        $engagement_distribution = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                CASE 
                    WHEN engagement_score >= 40 THEN 'High'
                    WHEN engagement_score >= 20 THEN 'Medium'
                    ELSE 'Low'
                END as engagement_level,
                COUNT(*) as user_count
             FROM {$wpdb->prefix}env_user_behavior 
             WHERE DATE(analyzed_at) BETWEEN %s AND %s
             GROUP BY engagement_level",
            $date_from, $date_to
        ));
        
        // Get top content categories
        $top_categories = $wpdb->get_results($wpdb->prepare(
            "SELECT event_category, COUNT(*) as event_count
             FROM {$wpdb->prefix}env_analytics_events 
             WHERE DATE(event_date) BETWEEN %s AND %s
             GROUP BY event_category
             ORDER BY event_count DESC
             LIMIT 10",
            $date_from, $date_to
        ));
        
        // Get daily engagement trends
        $daily_trends = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(analyzed_at) as analysis_date,
                AVG(engagement_score) as avg_engagement,
                COUNT(DISTINCT user_id) as active_users
             FROM {$wpdb->prefix}env_user_behavior 
             WHERE DATE(analyzed_at) BETWEEN %s AND %s
             GROUP BY DATE(analyzed_at)
             ORDER BY analysis_date",
            $date_from, $date_to
        ));
        
        return array(
            'overview' => $engagement_stats,
            'engagement_distribution' => $engagement_distribution,
            'top_categories' => $top_categories,
            'daily_trends' => $daily_trends
        );
    }
    
    /**
     * Get content performance metrics
     */
    public function get_content_performance($date_from = null, $date_to = null) {
        if (!$date_from) {
            $date_from = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$date_to) {
            $date_to = date('Y-m-d');
        }
        
        global $wpdb;
        
        // Get page performance
        $page_performance = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                page_url,
                COUNT(*) as page_views,
                COUNT(DISTINCT user_id) as unique_visitors,
                AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.time_spent')) AS UNSIGNED)) as avg_time_on_page
             FROM {$wpdb->prefix}env_analytics_events 
             WHERE event_action = 'page_view' 
             AND DATE(event_date) BETWEEN %s AND %s
             AND page_url IS NOT NULL
             GROUP BY page_url
             ORDER BY page_views DESC
             LIMIT 20",
            $date_from, $date_to
        ));
        
        // Get action performance
        $action_performance = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                event_action,
                COUNT(*) as action_count,
                COUNT(DISTINCT user_id) as unique_users
             FROM {$wpdb->prefix}env_analytics_events 
             WHERE DATE(event_date) BETWEEN %s AND %s
             AND event_action != 'page_view'
             GROUP BY event_action
             ORDER BY action_count DESC",
            $date_from, $date_to
        ));
        
        // Get referrer performance
        $referrer_performance = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                referrer_url,
                COUNT(*) as referrals,
                COUNT(DISTINCT user_id) as unique_referrals
             FROM {$wpdb->prefix}env_analytics_events 
             WHERE DATE(event_date) BETWEEN %s AND %s
             AND referrer_url IS NOT NULL
             AND referrer_url != ''
             GROUP BY referrer_url
             ORDER BY referrals DESC
             LIMIT 15",
            $date_from, $date_to
        ));
        
        return array(
            'page_performance' => $page_performance,
            'action_performance' => $action_performance,
            'referrer_performance' => $referrer_performance
        );
    }
    
    /**
     * Segment users based on behavior
     */
    public function segment_users($date_from = null, $date_to = null) {
        if (!$date_from) {
            $date_from = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$date_to) {
            $date_to = date('Y-m-d');
        }
        
        global $wpdb;
        
        // Define user segments
        $segments = array(
            'Environmental Champions' => array(
                'condition' => 'environmental_interest_score >= 80 AND engagement_score >= 35',
                'description' => 'Highly engaged users with strong environmental interest'
            ),
            'Active Contributors' => array(
                'condition' => 'engagement_score >= 30 AND total_sessions >= 5',
                'description' => 'Regular users who actively participate'
            ),
            'Casual Visitors' => array(
                'condition' => 'engagement_score BETWEEN 10 AND 29 AND bounce_rate < 70',
                'description' => 'Moderate engagement, potential for growth'
            ),
            'New Users' => array(
                'condition' => 'total_sessions <= 2',
                'description' => 'Recently registered or first-time visitors'
            ),
            'At Risk' => array(
                'condition' => 'bounce_rate >= 70 AND engagement_score < 15',
                'description' => 'Users with low engagement, risk of churning'
            )
        );
        
        $segment_results = array();
        
        foreach ($segments as $segment_name => $segment_info) {
            $users = $wpdb->get_results($wpdb->prepare(
                "SELECT user_id, engagement_score, environmental_interest_score, 
                        bounce_rate, total_sessions
                 FROM {$wpdb->prefix}env_user_behavior 
                 WHERE DATE(analyzed_at) BETWEEN %s AND %s
                 AND {$segment_info['condition']}
                 ORDER BY engagement_score DESC",
                $date_from, $date_to
            ));
            
            $segment_results[$segment_name] = array(
                'description' => $segment_info['description'],
                'user_count' => count($users),
                'users' => $users
            );
        }
        
        return $segment_results;
    }
    
    /**
     * Analyze daily behavior patterns (cron job)
     */
    public function analyze_daily_behavior() {
        global $wpdb;
        
        // Get users who had activity yesterday
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        $active_users = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT user_id 
             FROM {$wpdb->prefix}env_analytics_events 
             WHERE DATE(event_date) = %s",
            $yesterday
        ));
        
        foreach ($active_users as $user_id) {
            $this->analyze_user_behavior($user_id, $yesterday, $yesterday);
        }
        
        // Log the analysis completion
        error_log("Environmental Analytics: Daily behavior analysis completed for " . count($active_users) . " users");
    }
    
    /**
     * AJAX: Get behavior data
     */
    public function ajax_get_behavior_data() {
        check_ajax_referer('env_analytics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $user_id = intval($_POST['user_id']);
        $date_from = sanitize_text_field($_POST['date_from']);
        $date_to = sanitize_text_field($_POST['date_to']);
        
        $data = $this->analyze_user_behavior($user_id, $date_from, $date_to);
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX: Get user journey
     */
    public function ajax_get_user_journey() {
        check_ajax_referer('env_analytics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $user_id = intval($_POST['user_id']);
        $session_id = sanitize_text_field($_POST['session_id']);
        $limit = intval($_POST['limit']) ?: 50;
        
        $data = $this->get_user_journey($user_id, $session_id, $limit);
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX: Get engagement metrics
     */
    public function ajax_get_engagement_metrics() {
        check_ajax_referer('env_analytics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $date_from = sanitize_text_field($_POST['date_from']);
        $date_to = sanitize_text_field($_POST['date_to']);
        
        $data = $this->get_engagement_metrics($date_from, $date_to);
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX: Get content performance
     */
    public function ajax_get_content_performance() {
        check_ajax_referer('env_analytics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $date_from = sanitize_text_field($_POST['date_from']);
        $date_to = sanitize_text_field($_POST['date_to']);
        
        $data = $this->get_content_performance($date_from, $date_to);
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX: Get user segments
     */
    public function ajax_get_user_segments() {
        check_ajax_referer('env_analytics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $date_from = sanitize_text_field($_POST['date_from']);
        $date_to = sanitize_text_field($_POST['date_to']);
        
        $data = $this->segment_users($date_from, $date_to);
        
        wp_send_json_success($data);
    }
}
