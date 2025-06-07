<?php
/**
 * Performance Tracker Class
 * 
 * Tracks and analyzes recommendation performance metrics including
 * click-through rates, conversion rates, and user engagement analytics.
 * 
 * @package Environmental_Content_Recommendation
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECR_Performance_Tracker {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Performance metrics cache
     */
    private $metrics_cache = array();
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_ecr_track_recommendation_performance', array($this, 'ajax_track_performance'));
        add_action('wp_ajax_nopriv_ecr_track_recommendation_performance', array($this, 'ajax_track_performance'));
        add_action('wp_ajax_ecr_get_performance_metrics', array($this, 'ajax_get_performance_metrics'));
        add_action('ECR_generate_performance_report', array($this, 'generate_daily_report'));
        add_action('ECR_cleanup_performance_data', array($this, 'cleanup_old_data'));
        
        // Schedule performance reports
        if (!wp_next_scheduled('ECR_generate_performance_report')) {
            wp_schedule_event(time(), 'daily', 'ECR_generate_performance_report');
        }
        
        // Schedule data cleanup
        if (!wp_next_scheduled('ECR_cleanup_performance_data')) {
            wp_schedule_event(time(), 'weekly', 'ECR_cleanup_performance_data');
        }
        
        add_action('wp_enqueue_scripts', array($this, 'enqueue_tracking_scripts'));
    }
    
    /**
     * Enqueue performance tracking scripts
     */
    public function enqueue_tracking_scripts() {
        wp_enqueue_script(
            'ecr-performance-tracker',
            ECR_PLUGIN_URL . 'assets/js/performance-tracker.js',
            array('jquery'),
            ECR_VERSION,
            true
        );
        
        wp_localize_script('ecr-performance-tracker', 'ecr_performance', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ecr_performance_nonce'),
            'user_id' => get_current_user_id(),
            'track_impressions' => get_option('ecr_track_impressions', true),
            'track_clicks' => get_option('ecr_track_clicks', true),
            'track_conversions' => get_option('ecr_track_conversions', true)
        ));
    }
    
    /**
     * Track recommendation performance event
     */
    public function track_performance($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ecr_recommendation_performance';
        
        // Validate required fields
        $required_fields = array('user_id', 'recommendation_id', 'event_type');
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }
        
        // Prepare data for insertion
        $insert_data = array(
            'user_id' => intval($data['user_id']),
            'recommendation_id' => intval($data['recommendation_id']),
            'content_id' => intval($data['content_id']),
            'event_type' => sanitize_text_field($data['event_type']),
            'recommendation_type' => sanitize_text_field($data['recommendation_type']),
            'position' => intval($data['position']),
            'session_id' => sanitize_text_field($data['session_id']),
            'timestamp' => current_time('mysql'),
            'page_url' => esc_url_raw($data['page_url']),
            'referrer' => esc_url_raw($data['referrer']),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT']),
            'device_type' => $this->detect_device_type(),
            'response_time' => floatval($data['response_time']),
            'additional_data' => json_encode($data['additional_data'])
        );
        
        $result = $wpdb->insert(
            $table_name,
            $insert_data,
            array('%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s')
        );
        
        if ($result) {
            // Update real-time metrics
            $this->update_real_time_metrics($data);
            
            // Clear cache for updated metrics
            $this->clear_metrics_cache($data['user_id']);
        }
        
        return $result;
    }
    
    /**
     * AJAX handler for tracking performance
     */
    public function ajax_track_performance() {
        check_ajax_referer('ecr_performance_nonce', 'nonce');
        
        $data = array(
            'user_id' => intval($_POST['user_id']),
            'recommendation_id' => intval($_POST['recommendation_id']),
            'content_id' => intval($_POST['content_id']),
            'event_type' => sanitize_text_field($_POST['event_type']),
            'recommendation_type' => sanitize_text_field($_POST['recommendation_type']),
            'position' => intval($_POST['position']),
            'session_id' => sanitize_text_field($_POST['session_id']),
            'page_url' => esc_url_raw($_POST['page_url']),
            'referrer' => esc_url_raw($_POST['referrer']),
            'response_time' => floatval($_POST['response_time']),
            'additional_data' => json_decode(stripslashes($_POST['additional_data']), true)
        );
        
        $result = $this->track_performance($data);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Performance tracked successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to track performance'));
        }
    }
    
    /**
     * Update real-time metrics
     */
    private function update_real_time_metrics($data) {
        $metrics_key = 'ecr_real_time_metrics_' . date('Y-m-d');
        $metrics = get_transient($metrics_key);
        
        if ($metrics === false) {
            $metrics = array(
                'impressions' => 0,
                'clicks' => 0,
                'conversions' => 0,
                'unique_users' => array(),
                'recommendation_types' => array()
            );
        }
        
        // Update metrics based on event type
        switch ($data['event_type']) {
            case 'impression':
                $metrics['impressions']++;
                break;
            case 'click':
                $metrics['clicks']++;
                break;
            case 'conversion':
                $metrics['conversions']++;
                break;
        }
        
        // Track unique users
        if (!in_array($data['user_id'], $metrics['unique_users'])) {
            $metrics['unique_users'][] = $data['user_id'];
        }
        
        // Track recommendation types
        $rec_type = $data['recommendation_type'];
        if (!isset($metrics['recommendation_types'][$rec_type])) {
            $metrics['recommendation_types'][$rec_type] = 0;
        }
        $metrics['recommendation_types'][$rec_type]++;
        
        // Store updated metrics (expires at end of day)
        $expires = strtotime('tomorrow') - time();
        set_transient($metrics_key, $metrics, $expires);
    }
    
    /**
     * Get performance metrics
     */
    public function get_performance_metrics($filters = array()) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ecr_recommendation_performance';
        
        // Default filters
        $defaults = array(
            'start_date' => date('Y-m-d', strtotime('-30 days')),
            'end_date' => date('Y-m-d'),
            'user_id' => null,
            'recommendation_type' => null,
            'content_id' => null,
            'event_type' => null
        );
        
        $filters = array_merge($defaults, $filters);
        
        // Build cache key
        $cache_key = 'ecr_metrics_' . md5(serialize($filters));
        
        // Check cache
        if (isset($this->metrics_cache[$cache_key])) {
            return $this->metrics_cache[$cache_key];
        }
        
        // Build WHERE clause
        $where_conditions = array();
        $where_values = array();
        
        $where_conditions[] = "timestamp >= %s";
        $where_values[] = $filters['start_date'] . ' 00:00:00';
        
        $where_conditions[] = "timestamp <= %s";
        $where_values[] = $filters['end_date'] . ' 23:59:59';
        
        if (!empty($filters['user_id'])) {
            $where_conditions[] = "user_id = %d";
            $where_values[] = intval($filters['user_id']);
        }
        
        if (!empty($filters['recommendation_type'])) {
            $where_conditions[] = "recommendation_type = %s";
            $where_values[] = $filters['recommendation_type'];
        }
        
        if (!empty($filters['content_id'])) {
            $where_conditions[] = "content_id = %d";
            $where_values[] = intval($filters['content_id']);
        }
        
        if (!empty($filters['event_type'])) {
            $where_conditions[] = "event_type = %s";
            $where_values[] = $filters['event_type'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Get basic metrics
        $metrics = array();
        
        // Total events by type
        $event_counts = $wpdb->get_results($wpdb->prepare(
            "SELECT event_type, COUNT(*) as count 
             FROM {$table_name} 
             WHERE {$where_clause}
             GROUP BY event_type",
            $where_values
        ));
        
        foreach ($event_counts as $event) {
            $metrics[$event->event_type] = intval($event->count);
        }
        
        // Calculate derived metrics
        $impressions = isset($metrics['impression']) ? $metrics['impression'] : 0;
        $clicks = isset($metrics['click']) ? $metrics['click'] : 0;
        $conversions = isset($metrics['conversion']) ? $metrics['conversion'] : 0;
        
        $metrics['ctr'] = $impressions > 0 ? ($clicks / $impressions) * 100 : 0;
        $metrics['conversion_rate'] = $clicks > 0 ? ($conversions / $clicks) * 100 : 0;
        $metrics['overall_conversion_rate'] = $impressions > 0 ? ($conversions / $impressions) * 100 : 0;
        
        // Get unique users
        $unique_users = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$table_name} WHERE {$where_clause}",
            $where_values
        ));
        $metrics['unique_users'] = intval($unique_users);
        
        // Get performance by recommendation type
        $metrics['by_recommendation_type'] = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                recommendation_type,
                COUNT(*) as total_events,
                COUNT(CASE WHEN event_type = 'impression' THEN 1 END) as impressions,
                COUNT(CASE WHEN event_type = 'click' THEN 1 END) as clicks,
                COUNT(CASE WHEN event_type = 'conversion' THEN 1 END) as conversions,
                AVG(response_time) as avg_response_time
             FROM {$table_name} 
             WHERE {$where_clause}
             GROUP BY recommendation_type
             ORDER BY total_events DESC",
            $where_values
        ));
        
        // Calculate CTR and conversion rates for each type
        foreach ($metrics['by_recommendation_type'] as &$type_data) {
            $type_data->ctr = $type_data->impressions > 0 ? 
                ($type_data->clicks / $type_data->impressions) * 100 : 0;
            $type_data->conversion_rate = $type_data->clicks > 0 ? 
                ($type_data->conversions / $type_data->clicks) * 100 : 0;
        }
        
        // Get performance by position
        $metrics['by_position'] = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                position,
                COUNT(*) as total_events,
                COUNT(CASE WHEN event_type = 'impression' THEN 1 END) as impressions,
                COUNT(CASE WHEN event_type = 'click' THEN 1 END) as clicks
             FROM {$table_name} 
             WHERE {$where_clause} AND position > 0
             GROUP BY position
             ORDER BY position",
            $where_values
        ));
        
        foreach ($metrics['by_position'] as &$position_data) {
            $position_data->ctr = $position_data->impressions > 0 ? 
                ($position_data->clicks / $position_data->impressions) * 100 : 0;
        }
        
        // Get performance by device type
        $metrics['by_device'] = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                device_type,
                COUNT(*) as total_events,
                COUNT(CASE WHEN event_type = 'click' THEN 1 END) as clicks,
                COUNT(CASE WHEN event_type = 'impression' THEN 1 END) as impressions
             FROM {$table_name} 
             WHERE {$where_clause}
             GROUP BY device_type",
            $where_values
        ));
        
        foreach ($metrics['by_device'] as &$device_data) {
            $device_data->ctr = $device_data->impressions > 0 ? 
                ($device_data->clicks / $device_data->impressions) * 100 : 0;
        }
        
        // Get daily performance trends
        $metrics['daily_trends'] = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(timestamp) as date,
                COUNT(CASE WHEN event_type = 'impression' THEN 1 END) as impressions,
                COUNT(CASE WHEN event_type = 'click' THEN 1 END) as clicks,
                COUNT(CASE WHEN event_type = 'conversion' THEN 1 END) as conversions,
                COUNT(DISTINCT user_id) as unique_users
             FROM {$table_name} 
             WHERE {$where_clause}
             GROUP BY DATE(timestamp)
             ORDER BY date",
            $where_values
        ));
        
        foreach ($metrics['daily_trends'] as &$day_data) {
            $day_data->ctr = $day_data->impressions > 0 ? 
                ($day_data->clicks / $day_data->impressions) * 100 : 0;
        }
        
        // Top performing content
        $metrics['top_content'] = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                content_id,
                COUNT(CASE WHEN event_type = 'impression' THEN 1 END) as impressions,
                COUNT(CASE WHEN event_type = 'click' THEN 1 END) as clicks,
                COUNT(CASE WHEN event_type = 'conversion' THEN 1 END) as conversions
             FROM {$table_name} 
             WHERE {$where_clause} AND content_id > 0
             GROUP BY content_id
             HAVING impressions > 0
             ORDER BY clicks DESC, impressions DESC
             LIMIT 10",
            $where_values
        ));
        
        foreach ($metrics['top_content'] as &$content_data) {
            $content_data->ctr = $content_data->impressions > 0 ? 
                ($content_data->clicks / $content_data->impressions) * 100 : 0;
            $content_data->title = get_the_title($content_data->content_id);
        }
        
        // Cache the results
        $this->metrics_cache[$cache_key] = $metrics;
        
        return $metrics;
    }
    
    /**
     * AJAX handler for getting performance metrics
     */
    public function ajax_get_performance_metrics() {
        check_ajax_referer('ecr_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'environmental-content-recommendation'));
        }
        
        $filters = array();
        
        if (!empty($_POST['start_date'])) {
            $filters['start_date'] = sanitize_text_field($_POST['start_date']);
        }
        
        if (!empty($_POST['end_date'])) {
            $filters['end_date'] = sanitize_text_field($_POST['end_date']);
        }
        
        if (!empty($_POST['recommendation_type'])) {
            $filters['recommendation_type'] = sanitize_text_field($_POST['recommendation_type']);
        }
        
        if (!empty($_POST['user_id'])) {
            $filters['user_id'] = intval($_POST['user_id']);
        }
        
        $metrics = $this->get_performance_metrics($filters);
        
        wp_send_json_success($metrics);
    }
    
    /**
     * Generate A/B test performance comparison
     */
    public function get_ab_test_metrics($test_id, $variant_a, $variant_b) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ecr_recommendation_performance';
        
        $metrics = array(
            'variant_a' => array(),
            'variant_b' => array(),
            'statistical_significance' => false
        );
        
        // Get metrics for variant A
        $variant_a_data = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(CASE WHEN event_type = 'impression' THEN 1 END) as impressions,
                COUNT(CASE WHEN event_type = 'click' THEN 1 END) as clicks,
                COUNT(CASE WHEN event_type = 'conversion' THEN 1 END) as conversions
             FROM {$table_name} 
             WHERE JSON_EXTRACT(additional_data, '$.ab_test_id') = %s 
             AND JSON_EXTRACT(additional_data, '$.variant') = %s",
            $test_id, $variant_a
        ));
        
        if ($variant_a_data) {
            $metrics['variant_a'] = array(
                'impressions' => intval($variant_a_data->impressions),
                'clicks' => intval($variant_a_data->clicks),
                'conversions' => intval($variant_a_data->conversions),
                'ctr' => $variant_a_data->impressions > 0 ? 
                    ($variant_a_data->clicks / $variant_a_data->impressions) * 100 : 0,
                'conversion_rate' => $variant_a_data->clicks > 0 ? 
                    ($variant_a_data->conversions / $variant_a_data->clicks) * 100 : 0
            );
        }
        
        // Get metrics for variant B
        $variant_b_data = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(CASE WHEN event_type = 'impression' THEN 1 END) as impressions,
                COUNT(CASE WHEN event_type = 'click' THEN 1 END) as clicks,
                COUNT(CASE WHEN event_type = 'conversion' THEN 1 END) as conversions
             FROM {$table_name} 
             WHERE JSON_EXTRACT(additional_data, '$.ab_test_id') = %s 
             AND JSON_EXTRACT(additional_data, '$.variant') = %s",
            $test_id, $variant_b
        ));
        
        if ($variant_b_data) {
            $metrics['variant_b'] = array(
                'impressions' => intval($variant_b_data->impressions),
                'clicks' => intval($variant_b_data->clicks),
                'conversions' => intval($variant_b_data->conversions),
                'ctr' => $variant_b_data->impressions > 0 ? 
                    ($variant_b_data->clicks / $variant_b_data->impressions) * 100 : 0,
                'conversion_rate' => $variant_b_data->clicks > 0 ? 
                    ($variant_b_data->conversions / $variant_b_data->clicks) * 100 : 0
            );
        }
        
        // Calculate statistical significance (simplified chi-square test)
        if (!empty($metrics['variant_a']) && !empty($metrics['variant_b'])) {
            $metrics['statistical_significance'] = $this->calculate_statistical_significance(
                $metrics['variant_a'], $metrics['variant_b']
            );
        }
        
        return $metrics;
    }
    
    /**
     * Calculate statistical significance between two variants
     */
    private function calculate_statistical_significance($variant_a, $variant_b) {
        $n1 = $variant_a['impressions'];
        $n2 = $variant_b['impressions'];
        $x1 = $variant_a['clicks'];
        $x2 = $variant_b['clicks'];
        
        if ($n1 < 100 || $n2 < 100) {
            return false; // Not enough data
        }
        
        $p1 = $x1 / $n1;
        $p2 = $x2 / $n2;
        $p_pool = ($x1 + $x2) / ($n1 + $n2);
        
        $se = sqrt($p_pool * (1 - $p_pool) * (1/$n1 + 1/$n2));
        
        if ($se == 0) {
            return false;
        }
        
        $z_score = abs($p1 - $p2) / $se;
        
        // Return true if z-score > 1.96 (95% confidence)
        return $z_score > 1.96;
    }
    
    /**
     * Generate daily performance report
     */
    public function generate_daily_report() {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $metrics = $this->get_performance_metrics(array(
            'start_date' => $yesterday,
            'end_date' => $yesterday
        ));
        
        // Store report
        $report_data = array(
            'date' => $yesterday,
            'metrics' => $metrics,
            'generated_at' => current_time('mysql')
        );
        
        update_option('ecr_daily_report_' . $yesterday, $report_data);
        
        // Send email report if enabled
        if (get_option('ecr_email_reports', false)) {
            $this->send_email_report($report_data);
        }
        
        return $report_data;
    }
    
    /**
     * Send email performance report
     */
    private function send_email_report($report_data) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = sprintf(__('[%s] Daily Recommendation Performance Report - %s', 'environmental-content-recommendation'), 
            $site_name, $report_data['date']);
        
        $metrics = $report_data['metrics'];
        $impressions = isset($metrics['impression']) ? $metrics['impression'] : 0;
        $clicks = isset($metrics['click']) ? $metrics['click'] : 0;
        $ctr = $metrics['ctr'];
        
        $message = sprintf(__("Daily Recommendation Performance Report for %s\n\n", 'environmental-content-recommendation'), $report_data['date']);
        $message .= sprintf(__("Impressions: %d\n", 'environmental-content-recommendation'), $impressions);
        $message .= sprintf(__("Clicks: %d\n", 'environmental-content-recommendation'), $clicks);
        $message .= sprintf(__("Click-through Rate: %.2f%%\n\n", 'environmental-content-recommendation'), $ctr);
        
        if (!empty($metrics['by_recommendation_type'])) {
            $message .= __("Performance by Recommendation Type:\n", 'environmental-content-recommendation');
            foreach ($metrics['by_recommendation_type'] as $type_data) {
                $message .= sprintf("- %s: %d impressions, %d clicks (%.2f%% CTR)\n", 
                    $type_data->recommendation_type, 
                    $type_data->impressions, 
                    $type_data->clicks, 
                    $type_data->ctr
                );
            }
        }
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Cleanup old performance data
     */
    public function cleanup_old_data() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ecr_recommendation_performance';
        
        $retention_days = get_option('ecr_data_retention_days', 90);
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table_name} WHERE timestamp < %s",
            $cutoff_date
        ));
        
        // Clean up cached metrics
        $this->clear_all_metrics_cache();
        
        return $deleted;
    }
    
    /**
     * Detect device type from user agent
     */
    private function detect_device_type() {
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            return 'unknown';
        }
        
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        
        if (preg_match('/mobile|android|iphone|ipad|phone/i', $user_agent)) {
            return 'mobile';
        } elseif (preg_match('/tablet|ipad/i', $user_agent)) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    }
    
    /**
     * Clear metrics cache for a specific user
     */
    private function clear_metrics_cache($user_id = null) {
        if ($user_id) {
            foreach ($this->metrics_cache as $key => $value) {
                if (strpos($key, "user_id_{$user_id}") !== false) {
                    unset($this->metrics_cache[$key]);
                }
            }
        } else {
            $this->metrics_cache = array();
        }
    }
    
    /**
     * Clear all metrics cache
     */
    private function clear_all_metrics_cache() {
        $this->metrics_cache = array();
        
        // Clear WordPress transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ecr_metrics_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_ecr_metrics_%'");
    }
    
    /**
     * Get real-time performance dashboard data
     */
    public function get_dashboard_data() {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $last_week = date('Y-m-d', strtotime('-7 days'));
        
        // Today's metrics
        $today_metrics = $this->get_performance_metrics(array(
            'start_date' => $today,
            'end_date' => $today
        ));
        
        // Yesterday's metrics for comparison
        $yesterday_metrics = $this->get_performance_metrics(array(
            'start_date' => $yesterday,
            'end_date' => $yesterday
        ));
        
        // Last 7 days trend
        $week_trend = $this->get_performance_metrics(array(
            'start_date' => $last_week,
            'end_date' => $today
        ));
        
        return array(
            'today' => $today_metrics,
            'yesterday' => $yesterday_metrics,
            'week_trend' => $week_trend,
            'real_time' => get_transient('ecr_real_time_metrics_' . $today)
        );
    }
    
    /**
     * Export performance data to CSV
     */
    public function export_to_csv($filters = array()) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ecr_recommendation_performance';
        
        // Build query with filters
        $where_conditions = array('1=1');
        $where_values = array();
        
        if (!empty($filters['start_date'])) {
            $where_conditions[] = "timestamp >= %s";
            $where_values[] = $filters['start_date'] . ' 00:00:00';
        }
        
        if (!empty($filters['end_date'])) {
            $where_conditions[] = "timestamp <= %s";
            $where_values[] = $filters['end_date'] . ' 23:59:59';
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY timestamp DESC",
            $where_values
        ));
        
        // Generate CSV
        $csv_data = array();
        $csv_data[] = array(
            'Timestamp', 'User ID', 'Content ID', 'Event Type', 
            'Recommendation Type', 'Position', 'Session ID', 'Device Type', 
            'Response Time', 'Page URL', 'Referrer'
        );
        
        foreach ($results as $row) {
            $csv_data[] = array(
                $row->timestamp,
                $row->user_id,
                $row->content_id,
                $row->event_type,
                $row->recommendation_type,
                $row->position,
                $row->session_id,
                $row->device_type,
                $row->response_time,
                $row->page_url,
                $row->referrer
            );
        }
        
        return $csv_data;
    }
}
