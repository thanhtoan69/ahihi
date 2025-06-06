<?php
/**
 * Environmental Platform Performance Monitoring Cron Jobs
 * Handles scheduled performance optimization tasks
 */

if (!defined('ABSPATH')) {
    exit;
}

class EnvironmentalPerformanceCron {
    
    public function __construct() {
        add_action('init', array($this, 'schedule_events'));
        
        // Register cron hooks
        add_action('env_hourly_performance_check', array($this, 'hourly_performance_check'));
        add_action('env_daily_optimization', array($this, 'daily_optimization'));
        add_action('env_weekly_cleanup', array($this, 'weekly_cleanup'));
        add_action('env_cache_preload', array($this, 'cache_preload'));
        add_action('env_database_optimization', array($this, 'database_optimization'));
    }
    
    public function schedule_events() {
        // Hourly performance monitoring
        if (!wp_next_scheduled('env_hourly_performance_check')) {
            wp_schedule_event(time(), 'hourly', 'env_hourly_performance_check');
        }
        
        // Daily optimization tasks
        if (!wp_next_scheduled('env_daily_optimization')) {
            wp_schedule_event(time(), 'daily', 'env_daily_optimization');
        }
        
        // Weekly cleanup
        if (!wp_next_scheduled('env_weekly_cleanup')) {
            wp_schedule_event(time(), 'weekly', 'env_weekly_cleanup');
        }
        
        // Cache preloading (every 4 hours)
        if (!wp_next_scheduled('env_cache_preload')) {
            wp_schedule_event(time(), 'fourly', 'env_cache_preload');
        }
        
        // Database optimization (twice daily)
        if (!wp_next_scheduled('env_database_optimization')) {
            wp_schedule_event(time(), 'twicedaily', 'env_database_optimization');
        }
        
        // Add custom cron schedules
        add_filter('cron_schedules', array($this, 'add_custom_schedules'));
    }
    
    public function add_custom_schedules($schedules) {
        $schedules['fourly'] = array(
            'interval' => 4 * HOUR_IN_SECONDS,
            'display' => __('Every 4 Hours')
        );
        
        $schedules['fifteen_minutes'] = array(
            'interval' => 15 * MINUTE_IN_SECONDS,
            'display' => __('Every 15 Minutes')
        );
        
        return $schedules;
    }
    
    public function hourly_performance_check() {
        $this->log_performance_metrics();
        $this->check_slow_queries();
        $this->monitor_memory_usage();
        $this->check_cache_hit_ratio();
        $this->optimize_images_batch();
    }
    
    public function daily_optimization() {
        $this->cleanup_expired_transients();
        $this->optimize_database_tables();
        $this->generate_performance_report();
        $this->update_performance_recommendations();
        $this->cleanup_log_files();
    }
    
    public function weekly_cleanup() {
        $this->cleanup_old_performance_data();
        $this->optimize_wp_options();
        $this->cleanup_orphaned_meta();
        $this->regenerate_critical_css();
        $this->send_performance_summary();
    }
    
    public function cache_preload() {
        $this->preload_popular_pages();
        $this->preload_environmental_data();
        $this->preload_api_endpoints();
    }
    
    public function database_optimization() {
        $this->optimize_database_queries();
        $this->update_database_indexes();
        $this->cleanup_database_bloat();
    }
    
    private function log_performance_metrics() {
        global $wpdb;
        
        $metrics = array(
            'timestamp' => current_time('mysql'),
            'memory_usage' => memory_get_peak_usage(true),
            'database_queries' => $wpdb->num_queries,
            'page_load_time' => $this->get_average_load_time(),
            'cache_hit_ratio' => $this->get_cache_hit_ratio(),
            'active_users' => $this->count_active_users(),
            'server_load' => $this->get_server_load()
        );
        
        $wpdb->insert(
            $wpdb->prefix . 'performance_metrics',
            $metrics
        );
        
        // Alert if performance degrades
        if ($metrics['page_load_time'] > 3.0 || $metrics['memory_usage'] > 512 * 1024 * 1024) {
            $this->send_performance_alert($metrics);
        }
    }
    
    private function check_slow_queries() {
        global $wpdb;
        
        // Enable slow query logging temporarily
        $wpdb->query("SET SESSION long_query_time = " . ENV_SLOW_QUERY_THRESHOLD);
        
        // Check for slow queries in the last hour
        $slow_queries = $wpdb->get_results("
            SELECT * FROM performance_log 
            WHERE query_time > " . ENV_SLOW_QUERY_THRESHOLD . " 
            AND timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ORDER BY query_time DESC
            LIMIT 10
        ");
        
        if (!empty($slow_queries)) {
            $this->process_slow_queries($slow_queries);
        }
    }
    
    private function monitor_memory_usage() {
        $memory_usage = memory_get_peak_usage(true);
        $memory_limit = wp_convert_hr_to_bytes(WP_MEMORY_LIMIT);
        
        $usage_percentage = ($memory_usage / $memory_limit) * 100;
        
        if ($usage_percentage > 80) {
            $this->log_memory_warning($memory_usage, $memory_limit, $usage_percentage);
        }
        
        // Log memory usage by plugin
        $this->log_plugin_memory_usage();
    }
    
    private function check_cache_hit_ratio() {
        if (function_exists('wp_cache_get_stats')) {
            $stats = wp_cache_get_stats();
            $hit_ratio = $stats['ratio'] ?? 0;
            
            if ($hit_ratio < 70) {
                $this->optimize_cache_configuration();
            }
        }
    }
    
    private function optimize_images_batch() {
        $attachments = get_posts(array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'post_status' => 'inherit',
            'meta_query' => array(
                array(
                    'key' => '_env_optimized',
                    'compare' => 'NOT EXISTS'
                )
            ),
            'posts_per_page' => 10
        ));
        
        foreach ($attachments as $attachment) {
            $this->optimize_image($attachment->ID);
        }
    }
    
    private function cleanup_expired_transients() {
        global $wpdb;
        
        $time = time();
        $expired = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} 
                 WHERE option_name LIKE %s 
                 AND CAST(option_value AS UNSIGNED) < %d",
                '_transient_timeout_%',
                $time
            )
        );
        
        foreach ($expired as $transient) {
            $key = str_replace('_transient_timeout_', '', $transient);
            delete_transient($key);
        }
        
        $this->log_cleanup_action('transients', count($expired));
    }
    
    private function optimize_database_tables() {
        global $wpdb;
        
        $tables = $wpdb->get_col("SHOW TABLES");
        $optimized = 0;
        
        foreach ($tables as $table) {
            $result = $wpdb->query("OPTIMIZE TABLE `$table`");
            if ($result !== false) {
                $optimized++;
            }
        }
        
        $this->log_cleanup_action('table_optimization', $optimized);
    }
    
    private function generate_performance_report() {
        global $wpdb;
        
        $report_data = array(
            'date' => current_time('Y-m-d'),
            'avg_load_time' => $this->get_average_load_time(24),
            'avg_memory_usage' => $this->get_average_memory_usage(24),
            'cache_hit_ratio' => $this->get_cache_hit_ratio(),
            'total_page_views' => $this->get_page_views(24),
            'unique_visitors' => $this->get_unique_visitors(24),
            'slow_queries_count' => $this->get_slow_queries_count(24),
            'error_rate' => $this->get_error_rate(24)
        );
        
        $wpdb->insert(
            $wpdb->prefix . 'performance_reports',
            $report_data
        );
    }
    
    private function update_performance_recommendations() {
        $recommendations = array();
        
        // Check load time
        $avg_load_time = $this->get_average_load_time(24);
        if ($avg_load_time > 2.0) {
            $recommendations[] = array(
                'type' => 'performance',
                'priority' => 'high',
                'message' => 'Average load time is ' . number_format($avg_load_time, 2) . 's. Consider enabling more aggressive caching.',
                'action' => 'enable_aggressive_caching'
            );
        }
        
        // Check cache hit ratio
        $cache_ratio = $this->get_cache_hit_ratio();
        if ($cache_ratio < 70) {
            $recommendations[] = array(
                'type' => 'caching',
                'priority' => 'medium',
                'message' => 'Cache hit ratio is ' . number_format($cache_ratio, 1) . '%. Consider cache preloading.',
                'action' => 'preload_cache'
            );
        }
        
        // Check database optimization
        if ($this->needs_database_optimization()) {
            $recommendations[] = array(
                'type' => 'database',
                'priority' => 'medium',
                'message' => 'Database needs optimization. Run OPTIMIZE TABLE on large tables.',
                'action' => 'optimize_database'
            );
        }
        
        update_option('env_performance_recommendations', $recommendations);
    }
    
    private function cleanup_log_files() {
        $log_dir = WP_CONTENT_DIR . '/cache/';
        
        if (is_dir($log_dir)) {
            $log_files = glob($log_dir . '*.log');
            
            foreach ($log_files as $log_file) {
                if (filemtime($log_file) < strtotime('-30 days')) {
                    unlink($log_file);
                }
            }
        }
    }
    
    private function cleanup_old_performance_data() {
        global $wpdb;
        
        // Remove performance metrics older than 30 days
        $wpdb->query("
            DELETE FROM {$wpdb->prefix}performance_metrics 
            WHERE timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        // Remove old performance reports
        $wpdb->query("
            DELETE FROM {$wpdb->prefix}performance_reports 
            WHERE date < DATE_SUB(CURDATE(), INTERVAL 90 DAY)
        ");
    }
    
    private function optimize_wp_options() {
        global $wpdb;
        
        // Remove expired transients
        $wpdb->query("
            DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_%' 
            AND option_value < UNIX_TIMESTAMP()
        ");
        
        // Remove orphaned metadata
        $wpdb->query("
            DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_site_transient_timeout_%' 
            AND option_value < UNIX_TIMESTAMP()
        ");
    }
    
    private function cleanup_orphaned_meta() {
        global $wpdb;
        
        // Clean up postmeta for non-existent posts
        $wpdb->query("
            DELETE pm FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE p.ID IS NULL
        ");
        
        // Clean up usermeta for non-existent users
        $wpdb->query("
            DELETE um FROM {$wpdb->usermeta} um
            LEFT JOIN {$wpdb->users} u ON u.ID = um.user_id
            WHERE u.ID IS NULL
        ");
    }
    
    private function regenerate_critical_css() {
        // Regenerate critical CSS for main pages
        $critical_pages = array(
            home_url('/'),
            home_url('/green-marketplace/'),
            home_url('/environmental-data/'),
            home_url('/events/')
        );
        
        foreach ($critical_pages as $page) {
            $this->generate_critical_css($page);
        }
    }
    
    private function send_performance_summary() {
        $summary = $this->generate_weekly_summary();
        
        $admin_email = get_option('admin_email');
        $subject = 'Environmental Platform - Weekly Performance Summary';
        
        $message = $this->format_performance_email($summary);
        
        wp_mail($admin_email, $subject, $message, array('Content-Type: text/html; charset=UTF-8'));
    }
    
    private function preload_popular_pages() {
        $popular_pages = $this->get_popular_pages();
        
        foreach ($popular_pages as $page) {
            wp_remote_get($page, array(
                'timeout' => 10,
                'user-agent' => 'Environmental Platform Cache Preloader'
            ));
        }
    }
    
    private function preload_environmental_data() {
        // Preload environmental data API endpoints
        $api_endpoints = array(
            home_url('/wp-json/environmental/v1/air-quality/'),
            home_url('/wp-json/environmental/v1/waste-data/'),
            home_url('/wp-json/environmental/v1/statistics/')
        );
        
        foreach ($api_endpoints as $endpoint) {
            wp_remote_get($endpoint, array('timeout' => 10));
        }
    }
    
    private function preload_api_endpoints() {
        // Preload critical API endpoints
        $endpoints = array(
            '/wp-json/wp/v2/posts',
            '/wp-json/wp/v2/events',
            '/wp-json/wc/v3/products'
        );
        
        foreach ($endpoints as $endpoint) {
            wp_remote_get(home_url($endpoint), array('timeout' => 10));
        }
    }
    
    // Helper methods
    private function get_average_load_time($hours = 1) {
        global $wpdb;
        
        $result = $wpdb->get_var($wpdb->prepare("
            SELECT AVG(load_time) 
            FROM {$wpdb->prefix}performance_metrics 
            WHERE timestamp > DATE_SUB(NOW(), INTERVAL %d HOUR)
        ", $hours));
        
        return $result ? floatval($result) : 0;
    }
    
    private function get_cache_hit_ratio() {
        if (function_exists('wp_cache_get_stats')) {
            $stats = wp_cache_get_stats();
            return $stats['ratio'] ?? 0;
        }
        return 0;
    }
    
    private function count_active_users() {
        return count(wp_list_pluck(get_users(array(
            'meta_key' => 'session_tokens',
            'meta_compare' => 'EXISTS'
        )), 'ID'));
    }
    
    private function get_server_load() {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return $load[0];
        }
        return 0;
    }
    
    private function log_cleanup_action($action, $count) {
        error_log("Environmental Platform: $action cleanup completed - $count items processed");
    }
}

// Initialize performance cron
new EnvironmentalPerformanceCron();
