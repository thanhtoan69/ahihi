<?php
/**
 * API Monitor Class
 *
 * Handles API health monitoring, error tracking, performance analytics,
 * and rate limiting for all integrated APIs.
 */

if (!defined('ABSPATH')) {
    exit;
}

class EIA_API_Monitor {
    
    private static $instance = null;
    private $monitoring_enabled = true;
    private $alert_thresholds = array();
    private $rate_limits = array();
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init();
    }
    
    private function init() {
        // Load monitoring settings
        $this->monitoring_enabled = get_option('eia_monitoring_enabled', true);
        $this->alert_thresholds = get_option('eia_alert_thresholds', $this->get_default_thresholds());
        $this->rate_limits = get_option('eia_rate_limits', $this->get_default_rate_limits());
        
        // Register hooks
        add_action('wp_ajax_eia_get_api_stats', array($this, 'ajax_get_api_stats'));
        add_action('wp_ajax_eia_get_api_health', array($this, 'ajax_get_api_health'));
        add_action('wp_ajax_eia_reset_api_stats', array($this, 'ajax_reset_api_stats'));
        
        // Monitoring tasks
        add_action('eia_monitor_apis', array($this, 'monitor_apis'));
        add_action('eia_cleanup_old_logs', array($this, 'cleanup_old_logs'));
        add_action('eia_check_rate_limits', array($this, 'check_rate_limits'));
        add_action('eia_generate_reports', array($this, 'generate_monitoring_reports'));
        
        // Schedule monitoring tasks
        if (!wp_next_scheduled('eia_monitor_apis')) {
            wp_schedule_event(time(), 'every_five_minutes', 'eia_monitor_apis');
        }
        
        if (!wp_next_scheduled('eia_cleanup_old_logs')) {
            wp_schedule_event(time(), 'daily', 'eia_cleanup_old_logs');
        }
        
        if (!wp_next_scheduled('eia_generate_reports')) {
            wp_schedule_event(time(), 'daily', 'eia_generate_reports');
        }
        
        // API request monitoring hooks
        add_action('eia_api_request_made', array($this, 'track_api_request'), 10, 4);
        add_action('eia_api_response_received', array($this, 'track_api_response'), 10, 5);
    }
    
    /**
     * Get default alert thresholds
     */
    private function get_default_thresholds() {
        return array(
            'response_time' => 5.0, // seconds
            'error_rate' => 0.1, // 10%
            'availability' => 0.95, // 95%
            'rate_limit_usage' => 0.8 // 80%
        );
    }
    
    /**
     * Get default rate limits
     */
    private function get_default_rate_limits() {
        return array(
            'google_maps' => array(
                'requests_per_minute' => 60,
                'requests_per_day' => 25000
            ),
            'openweathermap' => array(
                'requests_per_minute' => 60,
                'requests_per_day' => 1000
            ),
            'iqair' => array(
                'requests_per_minute' => 10,
                'requests_per_day' => 10000
            ),
            'facebook' => array(
                'requests_per_minute' => 200,
                'requests_per_day' => 200000
            ),
            'twitter' => array(
                'requests_per_minute' => 100,
                'requests_per_day' => 100000
            )
        );
    }
    
    /**
     * Track API request
     */
    public function track_api_request($service, $provider, $endpoint, $params) {
        if (!$this->monitoring_enabled) {
            return;
        }
        
        // Check rate limits
        if ($this->is_rate_limited($provider)) {
            throw new Exception('Rate limit exceeded for provider: ' . $provider);
        }
        
        // Update rate limit counters
        $this->increment_rate_limit_counter($provider);
        
        // Log request start
        do_action('eia_api_request_logged', $service, $provider, $endpoint, $params);
    }
    
    /**
     * Track API response
     */
    public function track_api_response($request_id, $status_code, $response_data, $response_time, $provider) {
        if (!$this->monitoring_enabled) {
            return;
        }
        
        // Analyze response
        $this->analyze_api_response($provider, $status_code, $response_time);
        
        // Check for alerts
        $this->check_for_alerts($provider, $status_code, $response_time);
        
        // Update provider stats
        $this->update_provider_stats($provider, $status_code, $response_time);
    }
    
    /**
     * Check if provider is rate limited
     */
    private function is_rate_limited($provider) {
        if (!isset($this->rate_limits[$provider])) {
            return false;
        }
        
        $limits = $this->rate_limits[$provider];
        $current_time = time();
        
        // Check per-minute limit
        $minute_key = "rate_limit_{$provider}_" . floor($current_time / 60);
        $minute_count = get_transient($minute_key) ?: 0;
        
        if ($minute_count >= $limits['requests_per_minute']) {
            return true;
        }
        
        // Check daily limit
        $daily_key = "rate_limit_{$provider}_" . date('Y-m-d');
        $daily_count = get_transient($daily_key) ?: 0;
        
        if ($daily_count >= $limits['requests_per_day']) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Increment rate limit counter
     */
    private function increment_rate_limit_counter($provider) {
        $current_time = time();
        
        // Increment per-minute counter
        $minute_key = "rate_limit_{$provider}_" . floor($current_time / 60);
        $minute_count = get_transient($minute_key) ?: 0;
        set_transient($minute_key, $minute_count + 1, 120); // 2 minutes
        
        // Increment daily counter
        $daily_key = "rate_limit_{$provider}_" . date('Y-m-d');
        $daily_count = get_transient($daily_key) ?: 0;
        set_transient($daily_key, $daily_count + 1, DAY_IN_SECONDS);
    }
    
    /**
     * Analyze API response
     */
    private function analyze_api_response($provider, $status_code, $response_time) {
        // Check for slow responses
        if ($response_time > $this->alert_thresholds['response_time']) {
            $this->log_alert('slow_response', array(
                'provider' => $provider,
                'response_time' => $response_time,
                'threshold' => $this->alert_thresholds['response_time']
            ));
        }
        
        // Check for errors
        if ($status_code >= 400) {
            $this->log_alert('api_error', array(
                'provider' => $provider,
                'status_code' => $status_code
            ));
        }
    }
    
    /**
     * Check for alerts
     */
    private function check_for_alerts($provider, $status_code, $response_time) {
        // Get recent error rate
        $error_rate = $this->get_provider_error_rate($provider, '1 HOUR');
        
        if ($error_rate > $this->alert_thresholds['error_rate']) {
            $this->trigger_alert('high_error_rate', array(
                'provider' => $provider,
                'error_rate' => $error_rate,
                'threshold' => $this->alert_thresholds['error_rate']
            ));
        }
        
        // Check availability
        $availability = $this->get_provider_availability($provider, '1 HOUR');
        
        if ($availability < $this->alert_thresholds['availability']) {
            $this->trigger_alert('low_availability', array(
                'provider' => $provider,
                'availability' => $availability,
                'threshold' => $this->alert_thresholds['availability']
            ));
        }
        
        // Check rate limit usage
        $rate_usage = $this->get_rate_limit_usage($provider);
        
        if ($rate_usage > $this->alert_thresholds['rate_limit_usage']) {
            $this->trigger_alert('rate_limit_warning', array(
                'provider' => $provider,
                'usage' => $rate_usage,
                'threshold' => $this->alert_thresholds['rate_limit_usage']
            ));
        }
    }
    
    /**
     * Get provider error rate
     */
    private function get_provider_error_rate($provider, $period = '1 HOUR') {
        global $wpdb;
        
        $total_requests = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}eia_api_logs 
             WHERE provider = %s AND created_at > DATE_SUB(NOW(), INTERVAL {$period})",
            $provider
        ));
        
        if ($total_requests == 0) {
            return 0;
        }
        
        $error_requests = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}eia_api_logs 
             WHERE provider = %s AND status_code >= 400 
             AND created_at > DATE_SUB(NOW(), INTERVAL {$period})",
            $provider
        ));
        
        return $error_requests / $total_requests;
    }
    
    /**
     * Get provider availability
     */
    private function get_provider_availability($provider, $period = '1 HOUR') {
        global $wpdb;
        
        $total_requests = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}eia_api_logs 
             WHERE provider = %s AND created_at > DATE_SUB(NOW(), INTERVAL {$period})",
            $provider
        ));
        
        if ($total_requests == 0) {
            return 1.0;
        }
        
        $successful_requests = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}eia_api_logs 
             WHERE provider = %s AND status_code >= 200 AND status_code < 400 
             AND created_at > DATE_SUB(NOW(), INTERVAL {$period})",
            $provider
        ));
        
        return $successful_requests / $total_requests;
    }
    
    /**
     * Get rate limit usage
     */
    private function get_rate_limit_usage($provider) {
        if (!isset($this->rate_limits[$provider])) {
            return 0;
        }
        
        $daily_key = "rate_limit_{$provider}_" . date('Y-m-d');
        $daily_count = get_transient($daily_key) ?: 0;
        $daily_limit = $this->rate_limits[$provider]['requests_per_day'];
        
        return $daily_count / $daily_limit;
    }
    
    /**
     * Update provider statistics
     */
    private function update_provider_stats($provider, $status_code, $response_time) {
        $stats_key = "api_stats_{$provider}_" . date('Y-m-d-H');
        $stats = get_transient($stats_key) ?: array(
            'total_requests' => 0,
            'successful_requests' => 0,
            'error_requests' => 0,
            'total_response_time' => 0,
            'min_response_time' => null,
            'max_response_time' => null
        );
        
        $stats['total_requests']++;
        $stats['total_response_time'] += $response_time;
        
        if ($status_code >= 200 && $status_code < 400) {
            $stats['successful_requests']++;
        } else {
            $stats['error_requests']++;
        }
        
        if ($stats['min_response_time'] === null || $response_time < $stats['min_response_time']) {
            $stats['min_response_time'] = $response_time;
        }
        
        if ($stats['max_response_time'] === null || $response_time > $stats['max_response_time']) {
            $stats['max_response_time'] = $response_time;
        }
        
        set_transient($stats_key, $stats, 2 * HOUR_IN_SECONDS);
    }
    
    /**
     * Get API statistics
     */
    public function get_api_statistics($provider = null, $period = '24 HOURS') {
        global $wpdb;
        
        $where_clause = '';
        $where_params = array();
        
        if ($provider) {
            $where_clause = 'WHERE provider = %s AND ';
            $where_params[] = $provider;
        } else {
            $where_clause = 'WHERE ';
        }
        
        $where_clause .= "created_at > DATE_SUB(NOW(), INTERVAL {$period})";
        
        $query = "
            SELECT 
                provider,
                COUNT(*) as total_requests,
                COUNT(CASE WHEN status_code >= 200 AND status_code < 400 THEN 1 END) as successful_requests,
                COUNT(CASE WHEN status_code >= 400 THEN 1 END) as error_requests,
                AVG(response_time) as avg_response_time,
                MIN(response_time) as min_response_time,
                MAX(response_time) as max_response_time
            FROM {$wpdb->prefix}eia_api_logs 
            {$where_clause}
            GROUP BY provider
            ORDER BY total_requests DESC
        ";
        
        if (!empty($where_params)) {
            $query = $wpdb->prepare($query, $where_params);
        }
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        // Calculate additional metrics
        foreach ($results as &$result) {
            $result['success_rate'] = $result['total_requests'] > 0 ? 
                $result['successful_requests'] / $result['total_requests'] : 0;
            $result['error_rate'] = $result['total_requests'] > 0 ? 
                $result['error_requests'] / $result['total_requests'] : 0;
            $result['rate_limit_usage'] = $this->get_rate_limit_usage($result['provider']);
        }
        
        return $results;
    }
    
    /**
     * Get API health status
     */
    public function get_api_health() {
        $providers = array('google_maps', 'openweathermap', 'iqair', 'facebook', 'twitter', 'instagram');
        $health_status = array();
        
        foreach ($providers as $provider) {
            $stats = $this->get_api_statistics($provider, '1 HOUR');
            
            if (empty($stats)) {
                $health_status[$provider] = array(
                    'status' => 'unknown',
                    'message' => 'No recent data available'
                );
                continue;
            }
            
            $provider_stats = $stats[0];
            $status = 'healthy';
            $issues = array();
            
            // Check error rate
            if ($provider_stats['error_rate'] > $this->alert_thresholds['error_rate']) {
                $status = 'degraded';
                $issues[] = 'High error rate: ' . round($provider_stats['error_rate'] * 100, 1) . '%';
            }
            
            // Check response time
            if ($provider_stats['avg_response_time'] > $this->alert_thresholds['response_time']) {
                $status = 'degraded';
                $issues[] = 'Slow response time: ' . round($provider_stats['avg_response_time'], 2) . 's';
            }
            
            // Check availability
            if ($provider_stats['success_rate'] < $this->alert_thresholds['availability']) {
                $status = 'unhealthy';
                $issues[] = 'Low availability: ' . round($provider_stats['success_rate'] * 100, 1) . '%';
            }
            
            // Check rate limit
            if ($provider_stats['rate_limit_usage'] > $this->alert_thresholds['rate_limit_usage']) {
                if ($status === 'healthy') $status = 'warning';
                $issues[] = 'High rate limit usage: ' . round($provider_stats['rate_limit_usage'] * 100, 1) . '%';
            }
            
            $health_status[$provider] = array(
                'status' => $status,
                'message' => empty($issues) ? 'All systems operational' : implode(', ', $issues),
                'stats' => $provider_stats
            );
        }
        
        return $health_status;
    }
    
    /**
     * Monitor APIs periodically
     */
    public function monitor_apis() {
        if (!$this->monitoring_enabled) {
            return;
        }
        
        $health_status = $this->get_api_health();
        
        // Store health status
        update_option('eia_last_health_check', array(
            'timestamp' => current_time('mysql'),
            'status' => $health_status
        ));
        
        // Check for critical issues
        foreach ($health_status as $provider => $status) {
            if ($status['status'] === 'unhealthy') {
                $this->trigger_alert('api_unhealthy', array(
                    'provider' => $provider,
                    'status' => $status
                ));
            }
        }
    }
    
    /**
     * Cleanup old logs
     */
    public function cleanup_old_logs() {
        global $wpdb;
        
        $retention_days = get_option('eia_log_retention_days', 30);
        
        // Clean up API logs
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}eia_api_logs 
             WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $retention_days
        ));
        
        // Clean up webhook logs
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}eia_webhook_logs 
             WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $retention_days
        ));
        
        // Clean up cache
        $wpdb->query(
            "DELETE FROM {$wpdb->prefix}eia_api_cache 
             WHERE expires_at < NOW()"
        );
    }
    
    /**
     * Log alert
     */
    private function log_alert($type, $data) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'eia_api_logs',
            array(
                'service' => 'monitoring',
                'provider' => $data['provider'] ?? 'system',
                'endpoint' => 'alert',
                'request_data' => json_encode(array('type' => $type, 'data' => $data)),
                'status_code' => 0,
                'response_data' => json_encode(array('alert_type' => $type)),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%d', '%s', '%s')
        );
    }
    
    /**
     * Trigger alert
     */
    private function trigger_alert($type, $data) {
        // Prevent spam - only send alert if last similar alert was more than 1 hour ago
        $last_alert_key = "last_alert_{$type}_{$data['provider']}";
        $last_alert = get_transient($last_alert_key);
        
        if ($last_alert) {
            return;
        }
        
        set_transient($last_alert_key, time(), HOUR_IN_SECONDS);
        
        // Log the alert
        $this->log_alert($type, $data);
        
        // Send notifications
        $this->send_alert_notification($type, $data);
        
        // Trigger webhook
        do_action('eia_api_alert', $type, $data);
    }
    
    /**
     * Send alert notification
     */
    private function send_alert_notification($type, $data) {
        $admin_email = get_option('admin_email');
        $subject = 'API Alert - ' . ucwords(str_replace('_', ' ', $type));
        
        $message = "An API alert has been triggered:\n\n";
        $message .= "Alert Type: " . ucwords(str_replace('_', ' ', $type)) . "\n";
        $message .= "Provider: " . $data['provider'] . "\n";
        $message .= "Time: " . current_time('mysql') . "\n\n";
        
        foreach ($data as $key => $value) {
            if ($key !== 'provider') {
                $message .= ucwords(str_replace('_', ' ', $key)) . ": " . $value . "\n";
            }
        }
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * AJAX handlers
     */
    public function ajax_get_api_stats() {
        check_ajax_referer('eia_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $provider = sanitize_text_field($_GET['provider'] ?? '');
        $period = sanitize_text_field($_GET['period'] ?? '24 HOURS');
        
        $stats = $this->get_api_statistics($provider ?: null, $period);
        
        wp_send_json_success($stats);
    }
    
    public function ajax_get_api_health() {
        check_ajax_referer('eia_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $health = $this->get_api_health();
        
        wp_send_json_success($health);
    }
    
    public function ajax_reset_api_stats() {
        check_ajax_referer('eia_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $provider = sanitize_text_field($_POST['provider'] ?? '');
        
        if ($provider) {
            $wpdb->delete(
                $wpdb->prefix . 'eia_api_logs',
                array('provider' => $provider),
                array('%s')
            );
        } else {
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}eia_api_logs");
        }
        
        wp_send_json_success('Statistics reset successfully');
    }
    
    /**
     * Generate monitoring reports
     */
    public function generate_monitoring_reports() {
        $report_data = array(
            'date' => date('Y-m-d'),
            'summary' => $this->get_api_statistics(null, '24 HOURS'),
            'health' => $this->get_api_health(),
            'alerts' => $this->get_daily_alerts()
        );
        
        // Store daily report
        update_option('eia_daily_report_' . date('Y-m-d'), $report_data);
        
        // Clean up old reports (keep 30 days)
        $old_reports = get_option('eia_daily_reports', array());
        $cutoff_date = date('Y-m-d', strtotime('-30 days'));
        
        foreach ($old_reports as $date => $report) {
            if ($date < $cutoff_date) {
                delete_option('eia_daily_report_' . $date);
            }
        }
    }
    
    /**
     * Get daily alerts
     */
    private function get_daily_alerts() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}eia_api_logs 
             WHERE service = 'monitoring' AND endpoint = 'alert' 
             AND DATE(created_at) = CURDATE()
             ORDER BY created_at DESC",
            ARRAY_A
        );
    }
}

// Initialize the class
EIA_API_Monitor::get_instance();
