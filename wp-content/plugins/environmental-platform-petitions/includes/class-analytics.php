<?php
/**
 * Analytics Class
 * 
 * Handles comprehensive analytics tracking and reporting for petitions
 * 
 * @package Environmental_Platform_Petitions
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Platform_Petitions_Analytics {
    
    /**
     * Database instance
     */
    private $database;
    
    /**
     * Tracked events
     */
    private $tracked_events = array(
        'page_view',
        'form_view',
        'signature_attempt',
        'signature_success',
        'signature_verified',
        'share',
        'share_click',
        'milestone_reached',
        'email_open',
        'email_click',
        'campaign_start',
        'campaign_end'
    );
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new Environmental_Platform_Petitions_Database();
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_track_petition_event', array($this, 'ajax_track_event'));
        add_action('wp_ajax_nopriv_track_petition_event', array($this, 'ajax_track_event'));
        add_action('wp_ajax_get_petition_analytics', array($this, 'ajax_get_analytics'));
        
        // Auto-track page views for petitions
        add_action('wp', array($this, 'auto_track_page_view'));
        
        // Track form views when shortcode is rendered
        add_filter('petition_signature_form_rendered', array($this, 'track_form_view'));
    }
    
    /**
     * Track an event
     */
    public function track_event($petition_id, $event_type, $event_data = array(), $user_id = null) {
        global $wpdb;
        
        if (!in_array($event_type, $this->tracked_events)) {
            return false;
        }
        
        $table = $this->database->get_table_name('analytics');
        
        $analytics_data = array(
            'petition_id' => absint($petition_id),
            'user_id' => $user_id ?: (is_user_logged_in() ? get_current_user_id() : null),
            'event_type' => sanitize_text_field($event_type),
            'event_data' => wp_json_encode($this->sanitize_event_data($event_data)),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'user_ip' => $this->get_user_ip(),
            'referrer' => sanitize_url($_SERVER['HTTP_REFERER'] ?? ''),
            'page_url' => sanitize_url($_SERVER['REQUEST_URI'] ?? ''),
            'device_type' => $this->detect_device_type(),
            'browser' => $this->detect_browser(),
            'created_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert($table, $analytics_data);
        
        if ($result) {
            // Process real-time analytics
            $this->process_real_time_event($petition_id, $event_type, $event_data);
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Get petition analytics overview
     */
    public function get_petition_overview($petition_id, $date_range = '30 days') {
        global $wpdb;
        
        $table = $this->database->get_table_name('analytics');
        $signatures_table = $this->database->get_table_name('signatures');
        $shares_table = $this->database->get_table_name('shares');
        
        $date_condition = $this->get_date_condition($date_range);
        
        // Basic metrics
        $overview = array();
        
        // Event counts
        $events = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                event_type,
                COUNT(*) as count,
                COUNT(DISTINCT user_ip) as unique_users
            FROM {$table} 
            WHERE petition_id = %d {$date_condition}
            GROUP BY event_type",
            $petition_id
        ));
        
        $overview['events'] = array();
        foreach ($events as $event) {
            $overview['events'][$event->event_type] = array(
                'count' => $event->count,
                'unique_users' => $event->unique_users
            );
        }
        
        // Signature metrics
        $signature_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_signatures,
                COUNT(CASE WHEN is_verified = 1 THEN 1 END) as verified_signatures,
                COUNT(DISTINCT user_email) as unique_signers
            FROM {$signatures_table} 
            WHERE petition_id = %d {$date_condition}",
            $petition_id
        ));
        
        $overview['signatures'] = (array) $signature_stats;
        
        // Share metrics
        $share_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_shares,
                SUM(clicks) as total_clicks,
                COUNT(DISTINCT platform) as platforms_used
            FROM {$shares_table} 
            WHERE petition_id = %d {$date_condition}",
            $petition_id
        ));
        
        $overview['shares'] = (array) $share_stats;
        
        // Conversion rates
        $page_views = $overview['events']['page_view']['count'] ?? 0;
        $form_views = $overview['events']['form_view']['count'] ?? 0;
        $signature_attempts = $overview['events']['signature_attempt']['count'] ?? 0;
        $signature_success = $overview['events']['signature_success']['count'] ?? 0;
        
        $overview['conversion_rates'] = array(
            'page_to_form' => $page_views > 0 ? round(($form_views / $page_views) * 100, 2) : 0,
            'form_to_attempt' => $form_views > 0 ? round(($signature_attempts / $form_views) * 100, 2) : 0,
            'attempt_to_success' => $signature_attempts > 0 ? round(($signature_success / $signature_attempts) * 100, 2) : 0,
            'overall_conversion' => $page_views > 0 ? round(($signature_success / $page_views) * 100, 2) : 0
        );
        
        return $overview;
    }
    
    /**
     * Get time-series data for petition
     */
    public function get_time_series_data($petition_id, $metric = 'signatures', $period = 'daily', $date_range = '30 days') {
        global $wpdb;
        
        $date_condition = $this->get_date_condition($date_range);
        $date_format = $this->get_date_format($period);
        
        switch ($metric) {
            case 'signatures':
                $table = $this->database->get_table_name('signatures');
                $data = $wpdb->get_results($wpdb->prepare(
                    "SELECT 
                        DATE_FORMAT(created_at, %s) as period,
                        COUNT(*) as count,
                        COUNT(CASE WHEN is_verified = 1 THEN 1 END) as verified_count
                    FROM {$table} 
                    WHERE petition_id = %d {$date_condition}
                    GROUP BY DATE_FORMAT(created_at, %s)
                    ORDER BY period ASC",
                    $date_format,
                    $petition_id,
                    $date_format
                ));
                break;
                
            case 'page_views':
                $table = $this->database->get_table_name('analytics');
                $data = $wpdb->get_results($wpdb->prepare(
                    "SELECT 
                        DATE_FORMAT(created_at, %s) as period,
                        COUNT(*) as count,
                        COUNT(DISTINCT user_ip) as unique_count
                    FROM {$table} 
                    WHERE petition_id = %d AND event_type = 'page_view' {$date_condition}
                    GROUP BY DATE_FORMAT(created_at, %s)
                    ORDER BY period ASC",
                    $date_format,
                    $petition_id,
                    $date_format
                ));
                break;
                
            case 'shares':
                $table = $this->database->get_table_name('shares');
                $data = $wpdb->get_results($wpdb->prepare(
                    "SELECT 
                        DATE_FORMAT(created_at, %s) as period,
                        COUNT(*) as count,
                        SUM(clicks) as clicks
                    FROM {$table} 
                    WHERE petition_id = %d {$date_condition}
                    GROUP BY DATE_FORMAT(created_at, %s)
                    ORDER BY period ASC",
                    $date_format,
                    $petition_id,
                    $date_format
                ));
                break;
                
            default:
                return array();
        }
        
        return $data;
    }
    
    /**
     * Get demographic data
     */
    public function get_demographic_data($petition_id) {
        global $wpdb;
        
        $signatures_table = $this->database->get_table_name('signatures');
        $analytics_table = $this->database->get_table_name('analytics');
        
        $demographics = array();
        
        // Device types
        $demographics['devices'] = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                device_type,
                COUNT(*) as count
            FROM {$analytics_table} 
            WHERE petition_id = %d AND event_type = 'page_view'
            GROUP BY device_type
            ORDER BY count DESC",
            $petition_id
        ));
        
        // Browsers
        $demographics['browsers'] = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                browser,
                COUNT(*) as count
            FROM {$analytics_table} 
            WHERE petition_id = %d AND event_type = 'page_view'
            GROUP BY browser
            ORDER BY count DESC
            LIMIT 10",
            $petition_id
        ));
        
        // Geographic data (if available)
        $demographics['locations'] = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                user_location,
                COUNT(*) as count
            FROM {$signatures_table} 
            WHERE petition_id = %d AND user_location IS NOT NULL AND user_location != ''
            GROUP BY user_location
            ORDER BY count DESC
            LIMIT 20",
            $petition_id
        ));
        
        // Referrer sources
        $demographics['referrers'] = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                CASE 
                    WHEN referrer LIKE '%%google.%%' THEN 'Google'
                    WHEN referrer LIKE '%%facebook.%%' THEN 'Facebook'
                    WHEN referrer LIKE '%%twitter.%%' THEN 'Twitter'
                    WHEN referrer LIKE '%%linkedin.%%' THEN 'LinkedIn'
                    WHEN referrer = '' OR referrer IS NULL THEN 'Direct'
                    ELSE 'Other'
                END as source,
                COUNT(*) as count
            FROM {$analytics_table} 
            WHERE petition_id = %d AND event_type = 'page_view'
            GROUP BY source
            ORDER BY count DESC",
            $petition_id
        ));
        
        return $demographics;
    }
    
    /**
     * Get funnel analysis
     */
    public function get_funnel_analysis($petition_id, $date_range = '30 days') {
        global $wpdb;
        
        $table = $this->database->get_table_name('analytics');
        $date_condition = $this->get_date_condition($date_range);
        
        $funnel_steps = array(
            'page_view' => 'Page Views',
            'form_view' => 'Form Views',
            'signature_attempt' => 'Signature Attempts',
            'signature_success' => 'Signatures Completed',
            'signature_verified' => 'Signatures Verified'
        );
        
        $funnel_data = array();
        $previous_count = 0;
        
        foreach ($funnel_steps as $event_type => $label) {
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT user_ip) 
                FROM {$table} 
                WHERE petition_id = %d AND event_type = %s {$date_condition}",
                $petition_id,
                $event_type
            ));
            
            $drop_off_rate = $previous_count > 0 ? round((($previous_count - $count) / $previous_count) * 100, 2) : 0;
            
            $funnel_data[] = array(
                'step' => $event_type,
                'label' => $label,
                'count' => intval($count),
                'drop_off_rate' => $drop_off_rate
            );
            
            $previous_count = $count;
        }
        
        return $funnel_data;
    }
    
    /**
     * Generate analytics report
     */
    public function generate_report($petition_id, $format = 'array') {
        $report = array(
            'petition_id' => $petition_id,
            'petition_title' => get_the_title($petition_id),
            'generated_at' => current_time('mysql'),
            'overview' => $this->get_petition_overview($petition_id),
            'time_series' => array(
                'signatures' => $this->get_time_series_data($petition_id, 'signatures'),
                'page_views' => $this->get_time_series_data($petition_id, 'page_views'),
                'shares' => $this->get_time_series_data($petition_id, 'shares')
            ),
            'demographics' => $this->get_demographic_data($petition_id),
            'funnel' => $this->get_funnel_analysis($petition_id)
        );
        
        if ($format === 'json') {
            return wp_json_encode($report, JSON_PRETTY_PRINT);
        }
        
        return $report;
    }
    
    /**
     * Auto-track page views
     */
    public function auto_track_page_view() {
        if (is_singular('env_petition')) {
            $petition_id = get_the_ID();
            
            // Only track once per session
            $session_key = 'petition_page_view_' . $petition_id;
            if (!isset($_SESSION[$session_key])) {
                $this->track_event($petition_id, 'page_view', array(
                    'petition_title' => get_the_title($petition_id),
                    'request_time' => current_time('timestamp')
                ));
                
                $_SESSION[$session_key] = true;
            }
        }
    }
    
    /**
     * Track form view
     */
    public function track_form_view($petition_id) {
        $this->track_event($petition_id, 'form_view', array(
            'form_location' => 'shortcode',
            'request_time' => current_time('timestamp')
        ));
        
        return $petition_id;
    }
    
    /**
     * Process real-time events
     */
    private function process_real_time_event($petition_id, $event_type, $event_data) {
        // Check for milestone achievements
        if ($event_type === 'signature_success') {
            $this->check_milestones($petition_id);
        }
        
        // Update petition meta statistics
        $this->update_petition_stats($petition_id, $event_type);
    }
    
    /**
     * Check and trigger milestones
     */
    private function check_milestones($petition_id) {
        global $wpdb;
        
        $signatures_table = $this->database->get_table_name('signatures');
        $milestones_table = $this->database->get_table_name('milestones');
        
        $signature_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$signatures_table} WHERE petition_id = %d AND is_verified = 1",
            $petition_id
        ));
        
        $pending_milestones = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$milestones_table} 
            WHERE petition_id = %d AND target_count <= %d AND is_achieved = 0
            ORDER BY target_count ASC",
            $petition_id,
            $signature_count
        ));
        
        foreach ($pending_milestones as $milestone) {
            // Mark milestone as achieved
            $wpdb->update(
                $milestones_table,
                array(
                    'is_achieved' => 1,
                    'achieved_at' => current_time('mysql')
                ),
                array('id' => $milestone->id)
            );
            
            // Track milestone event
            $this->track_event($petition_id, 'milestone_reached', array(
                'milestone_id' => $milestone->id,
                'target_count' => $milestone->target_count,
                'current_count' => $signature_count,
                'milestone_title' => $milestone->title
            ));
            
            // Trigger milestone actions (notifications, etc.)
            do_action('petition_milestone_reached', $petition_id, $milestone, $signature_count);
        }
    }
    
    /**
     * Update petition statistics
     */
    private function update_petition_stats($petition_id, $event_type) {
        $stats_key = 'petition_analytics_stats';
        $stats = get_post_meta($petition_id, $stats_key, true) ?: array();
        
        if (!isset($stats[$event_type])) {
            $stats[$event_type] = 0;
        }
        
        $stats[$event_type]++;
        $stats['last_updated'] = current_time('mysql');
        
        update_post_meta($petition_id, $stats_key, $stats);
    }
    
    /**
     * Sanitize event data
     */
    private function sanitize_event_data($data) {
        $sanitized = array();
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = sanitize_text_field($value);
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitize_event_data($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Get date condition for SQL queries
     */
    private function get_date_condition($date_range) {
        switch ($date_range) {
            case '7 days':
                return "AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            case '30 days':
                return "AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            case '90 days':
                return "AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
            case '1 year':
                return "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            default:
                return "";
        }
    }
    
    /**
     * Get date format for time series
     */
    private function get_date_format($period) {
        switch ($period) {
            case 'hourly':
                return '%Y-%m-%d %H:00:00';
            case 'daily':
                return '%Y-%m-%d';
            case 'weekly':
                return '%Y-%u';
            case 'monthly':
                return '%Y-%m';
            default:
                return '%Y-%m-%d';
        }
    }
    
    /**
     * Get user IP address
     */
    private function get_user_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                
                if (filter_var(trim($ip), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return trim($ip);
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * Detect device type
     */
    private function detect_device_type() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (preg_match('/mobile|android|iphone|phone/i', $user_agent)) {
            return 'mobile';
        } elseif (preg_match('/tablet|ipad/i', $user_agent)) {
            return 'tablet';
        }
        
        return 'desktop';
    }
    
    /**
     * Detect browser
     */
    private function detect_browser() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (strpos($user_agent, 'Chrome') !== false) {
            return 'Chrome';
        } elseif (strpos($user_agent, 'Firefox') !== false) {
            return 'Firefox';
        } elseif (strpos($user_agent, 'Safari') !== false) {
            return 'Safari';
        } elseif (strpos($user_agent, 'Edge') !== false) {
            return 'Edge';
        } elseif (strpos($user_agent, 'Opera') !== false) {
            return 'Opera';
        }
        
        return 'Unknown';
    }
    
    /**
     * AJAX: Track event
     */
    public function ajax_track_event() {
        check_ajax_referer('petition_nonce', 'nonce');
        
        $petition_id = absint($_POST['petition_id']);
        $event_type = sanitize_text_field($_POST['event_type']);
        $event_data = isset($_POST['event_data']) ? $_POST['event_data'] : array();
        
        $event_id = $this->track_event($petition_id, $event_type, $event_data);
        
        if ($event_id) {
            wp_send_json_success(array(
                'event_id' => $event_id,
                'message' => 'Event tracked successfully'
            ));
        } else {
            wp_send_json_error('Failed to track event');
        }
    }
    
    /**
     * AJAX: Get analytics
     */
    public function ajax_get_analytics() {
        check_ajax_referer('petition_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $petition_id = absint($_POST['petition_id']);
        $type = sanitize_text_field($_POST['type'] ?? 'overview');
        $date_range = sanitize_text_field($_POST['date_range'] ?? '30 days');
        
        switch ($type) {
            case 'overview':
                $data = $this->get_petition_overview($petition_id, $date_range);
                break;
            case 'time_series':
                $metric = sanitize_text_field($_POST['metric'] ?? 'signatures');
                $period = sanitize_text_field($_POST['period'] ?? 'daily');
                $data = $this->get_time_series_data($petition_id, $metric, $period, $date_range);
                break;
            case 'demographics':
                $data = $this->get_demographic_data($petition_id);
                break;
            case 'funnel':
                $data = $this->get_funnel_analysis($petition_id, $date_range);
                break;
            case 'report':
                $data = $this->generate_report($petition_id);
                break;
            default:
                wp_send_json_error('Invalid analytics type');
                return;
        }
        
        wp_send_json_success($data);
    }
}
