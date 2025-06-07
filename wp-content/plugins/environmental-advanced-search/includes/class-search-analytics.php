<?php
/**
 * Search Analytics Class
 *
 * @package Environmental_Advanced_Search
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAS_Search_Analytics {
    
    /**
     * Analytics table name
     */
    private $analytics_table;
    
    /**
     * Popular searches table name
     */
    private $popular_table;
    
    /**
     * Initialize analytics
     */
    public function __construct() {
        global $wpdb;
        
        $this->analytics_table = $wpdb->prefix . 'eas_search_analytics';
        $this->popular_table = $wpdb->prefix . 'eas_popular_searches';
        
        add_action('wp_ajax_eas_get_analytics_data', array($this, 'ajax_get_analytics_data'));
        add_action('wp_ajax_eas_export_analytics', array($this, 'ajax_export_analytics'));
        
        // Hook into search to track analytics
        add_action('eas_search_performed', array($this, 'track_search'), 10, 4);
        add_action('eas_search_result_clicked', array($this, 'track_click'), 10, 3);
        
        // Schedule cleanup of old analytics data
        add_action('eas_cleanup_analytics', array($this, 'cleanup_old_data'));
        if (!wp_next_scheduled('eas_cleanup_analytics')) {
            wp_schedule_event(time(), 'daily', 'eas_cleanup_analytics');
        }
    }
    
    /**
     * Track search query
     *
     * @param string $query Search query
     * @param array $filters Applied filters
     * @param int $results_count Number of results found
     * @param float $execution_time Search execution time
     */
    public function track_search($query, $filters = array(), $results_count = 0, $execution_time = 0) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        $ip_address = $this->get_client_ip();
        $user_agent = sanitize_text_field($_SERVER['HTTP_USER_AGENT']);
        $referer = wp_get_referer();
        
        // Insert analytics record
        $wpdb->insert(
            $this->analytics_table,
            array(
                'search_query' => sanitize_text_field($query),
                'filters_used' => json_encode($filters),
                'results_count' => absint($results_count),
                'execution_time' => floatval($execution_time),
                'user_id' => $user_id ? $user_id : null,
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
                'referer' => $referer,
                'search_date' => current_time('mysql')
            ),
            array(
                '%s', '%s', '%d', '%f', '%d', '%s', '%s', '%s', '%s'
            )
        );
        
        // Update popular searches
        $this->update_popular_searches($query);
        
        // Track filters usage
        if (!empty($filters)) {
            $this->track_filter_usage($filters);
        }
    }
    
    /**
     * Track search result click
     *
     * @param int $post_id Post ID that was clicked
     * @param string $query Search query
     * @param int $position Result position
     */
    public function track_click($post_id, $query, $position = 0) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        $ip_address = $this->get_client_ip();
        
        // Record click in analytics table
        $wpdb->insert(
            $this->analytics_table,
            array(
                'search_query' => sanitize_text_field($query),
                'clicked_post_id' => absint($post_id),
                'click_position' => absint($position),
                'user_id' => $user_id ? $user_id : null,
                'ip_address' => $ip_address,
                'search_date' => current_time('mysql'),
                'is_click' => 1
            ),
            array(
                '%s', '%d', '%d', '%d', '%s', '%s', '%d'
            )
        );
    }
    
    /**
     * Update popular searches
     *
     * @param string $query Search query
     */
    private function update_popular_searches($query) {
        global $wpdb;
        
        if (empty($query) || strlen($query) < 2) {
            return;
        }
        
        $normalized_query = strtolower(trim($query));
        
        // Check if query already exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->popular_table} WHERE search_term = %s",
            $normalized_query
        ));
        
        if ($existing) {
            // Update count and last searched
            $wpdb->update(
                $this->popular_table,
                array(
                    'search_count' => $existing->search_count + 1,
                    'last_searched' => current_time('mysql')
                ),
                array('id' => $existing->id),
                array('%d', '%s'),
                array('%d')
            );
        } else {
            // Insert new popular search
            $wpdb->insert(
                $this->popular_table,
                array(
                    'search_term' => $normalized_query,
                    'search_count' => 1,
                    'first_searched' => current_time('mysql'),
                    'last_searched' => current_time('mysql')
                ),
                array('%s', '%d', '%s', '%s')
            );
        }
    }
    
    /**
     * Track filter usage
     *
     * @param array $filters Filters used
     */
    private function track_filter_usage($filters) {
        $filter_stats = get_option('eas_filter_usage_stats', array());
        
        foreach ($filters as $filter_type => $filter_value) {
            if (!isset($filter_stats[$filter_type])) {
                $filter_stats[$filter_type] = array();
            }
            
            if (is_array($filter_value)) {
                foreach ($filter_value as $value) {
                    $key = $filter_type . ':' . $value;
                    $filter_stats[$filter_type][$key] = isset($filter_stats[$filter_type][$key]) 
                        ? $filter_stats[$filter_type][$key] + 1 
                        : 1;
                }
            } else {
                $key = $filter_type . ':' . $filter_value;
                $filter_stats[$filter_type][$key] = isset($filter_stats[$filter_type][$key]) 
                    ? $filter_stats[$filter_type][$key] + 1 
                    : 1;
            }
        }
        
        update_option('eas_filter_usage_stats', $filter_stats);
    }
    
    /**
     * Get search analytics data
     *
     * @param array $args Query arguments
     * @return array Analytics data
     */
    public function get_analytics_data($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'date_from' => date('Y-m-d', strtotime('-30 days')),
            'date_to' => date('Y-m-d'),
            'limit' => 100,
            'group_by' => 'day'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $data = array();
        
        // Get search volume over time
        $data['search_volume'] = $this->get_search_volume($args);
        
        // Get top searches
        $data['top_searches'] = $this->get_top_searches($args);
        
        // Get search performance metrics
        $data['performance'] = $this->get_performance_metrics($args);
        
        // Get filter usage statistics
        $data['filter_usage'] = $this->get_filter_usage_stats();
        
        // Get no results queries
        $data['no_results'] = $this->get_no_results_queries($args);
        
        // Get click-through rates
        $data['ctr'] = $this->get_click_through_rates($args);
        
        return $data;
    }
    
    /**
     * Get search volume data
     *
     * @param array $args Query arguments
     * @return array Search volume data
     */
    private function get_search_volume($args) {
        global $wpdb;
        
        $group_format = '%Y-%m-%d';
        if ($args['group_by'] === 'hour') {
            $group_format = '%Y-%m-%d %H:00:00';
        } elseif ($args['group_by'] === 'month') {
            $group_format = '%Y-%m-01';
        }
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE_FORMAT(search_date, %s) as period,
                COUNT(*) as search_count,
                COUNT(DISTINCT CASE WHEN user_id IS NOT NULL THEN user_id ELSE ip_address END) as unique_users
            FROM {$this->analytics_table}
            WHERE search_date >= %s 
                AND search_date <= %s
                AND is_click = 0
            GROUP BY period
            ORDER BY period ASC
        ", $group_format, $args['date_from'], $args['date_to'] . ' 23:59:59'));
        
        return $results;
    }
    
    /**
     * Get top searches
     *
     * @param array $args Query arguments
     * @return array Top searches
     */
    private function get_top_searches($args) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                search_query,
                COUNT(*) as search_count,
                AVG(results_count) as avg_results,
                AVG(execution_time) as avg_execution_time
            FROM {$this->analytics_table}
            WHERE search_date >= %s 
                AND search_date <= %s
                AND is_click = 0
                AND search_query != ''
            GROUP BY search_query
            ORDER BY search_count DESC
            LIMIT %d
        ", $args['date_from'], $args['date_to'] . ' 23:59:59', $args['limit']));
        
        return $results;
    }
    
    /**
     * Get performance metrics
     *
     * @param array $args Query arguments
     * @return array Performance metrics
     */
    private function get_performance_metrics($args) {
        global $wpdb;
        
        $results = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_searches,
                AVG(results_count) as avg_results_count,
                AVG(execution_time) as avg_execution_time,
                COUNT(CASE WHEN results_count = 0 THEN 1 END) as zero_results_count,
                COUNT(DISTINCT CASE WHEN user_id IS NOT NULL THEN user_id ELSE ip_address END) as unique_users
            FROM {$this->analytics_table}
            WHERE search_date >= %s 
                AND search_date <= %s
                AND is_click = 0
        ", $args['date_from'], $args['date_to'] . ' 23:59:59'));
        
        return $results;
    }
    
    /**
     * Get filter usage statistics
     *
     * @return array Filter usage stats
     */
    private function get_filter_usage_stats() {
        return get_option('eas_filter_usage_stats', array());
    }
    
    /**
     * Get queries with no results
     *
     * @param array $args Query arguments
     * @return array No results queries
     */
    private function get_no_results_queries($args) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                search_query,
                COUNT(*) as search_count
            FROM {$this->analytics_table}
            WHERE search_date >= %s 
                AND search_date <= %s
                AND results_count = 0
                AND is_click = 0
                AND search_query != ''
            GROUP BY search_query
            ORDER BY search_count DESC
            LIMIT 50
        ", $args['date_from'], $args['date_to'] . ' 23:59:59'));
        
        return $results;
    }
    
    /**
     * Get click-through rates
     *
     * @param array $args Query arguments
     * @return array CTR data
     */
    private function get_click_through_rates($args) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                search_query,
                COUNT(CASE WHEN is_click = 0 THEN 1 END) as impressions,
                COUNT(CASE WHEN is_click = 1 THEN 1 END) as clicks,
                CASE 
                    WHEN COUNT(CASE WHEN is_click = 0 THEN 1 END) > 0 
                    THEN (COUNT(CASE WHEN is_click = 1 THEN 1 END) * 100.0 / COUNT(CASE WHEN is_click = 0 THEN 1 END))
                    ELSE 0 
                END as ctr
            FROM {$this->analytics_table}
            WHERE search_date >= %s 
                AND search_date <= %s
                AND search_query != ''
            GROUP BY search_query
            HAVING impressions > 5
            ORDER BY ctr DESC
            LIMIT 20
        ", $args['date_from'], $args['date_to'] . ' 23:59:59'));
        
        return $results;
    }
    
    /**
     * Get popular searches for suggestions
     *
     * @param string $query Partial query
     * @param int $limit Number of suggestions
     * @return array Popular searches
     */
    public function get_popular_searches($query = '', $limit = 10) {
        global $wpdb;
        
        $where = '';
        $params = array();
        
        if (!empty($query)) {
            $where = "WHERE search_term LIKE %s";
            $params[] = $query . '%';
        }
        
        $params[] = $limit;
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT search_term, search_count
            FROM {$this->popular_table}
            {$where}
            ORDER BY search_count DESC
            LIMIT %d
        ", $params));
        
        return $results;
    }
    
    /**
     * Get search suggestions based on query
     *
     * @param string $query Partial search term
     * @return array Suggestions
     */
    public function get_search_suggestions($query) {
        if (strlen($query) < 2) {
            return array();
        }
        
        $suggestions = array();
        
        // Get popular searches that match
        $popular = $this->get_popular_searches($query, 5);
        foreach ($popular as $item) {
            $suggestions[] = array(
                'text' => $item->search_term,
                'type' => 'popular',
                'count' => $item->search_count
            );
        }
        
        // Get content-based suggestions
        $content_suggestions = $this->get_content_suggestions($query, 5);
        $suggestions = array_merge($suggestions, $content_suggestions);
        
        return array_slice($suggestions, 0, 10);
    }
    
    /**
     * Get content-based suggestions
     *
     * @param string $query Search query
     * @param int $limit Number of suggestions
     * @return array Content suggestions
     */
    private function get_content_suggestions($query, $limit = 5) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT post_title
            FROM {$wpdb->posts}
            WHERE post_status = 'publish'
                AND post_type IN ('post', 'page', 'product')
                AND post_title LIKE %s
            ORDER BY post_title ASC
            LIMIT %d
        ", '%' . $query . '%', $limit));
        
        $suggestions = array();
        foreach ($results as $post) {
            $suggestions[] = array(
                'text' => $post->post_title,
                'type' => 'content',
                'count' => 0
            );
        }
        
        return $suggestions;
    }
    
    /**
     * Clean up old analytics data
     */
    public function cleanup_old_data() {
        global $wpdb;
        
        $retention_days = get_option('eas_analytics_retention_days', 365);
        $cutoff_date = date('Y-m-d', strtotime("-{$retention_days} days"));
        
        // Delete old analytics data
        $wpdb->delete(
            $this->analytics_table,
            array('search_date' => $cutoff_date),
            array('%s')
        );
        
        // Clean up popular searches that haven't been used in 90 days
        $wpdb->delete(
            $this->popular_table,
            array('last_searched' => date('Y-m-d', strtotime('-90 days'))),
            array('%s')
        );
    }
    
    /**
     * Export analytics data
     *
     * @param array $args Export arguments
     * @return string CSV data
     */
    public function export_analytics($args = array()) {
        $data = $this->get_analytics_data($args);
        
        $csv = "Search Query,Search Count,Avg Results,Avg Execution Time,Date\n";
        
        foreach ($data['top_searches'] as $search) {
            $csv .= sprintf(
                '"%s",%d,%.2f,%.4f,%s' . "\n",
                str_replace('"', '""', $search->search_query),
                $search->search_count,
                $search->avg_results,
                $search->avg_execution_time,
                date('Y-m-d')
            );
        }
        
        return $csv;
    }
    
    /**
     * Get client IP address
     *
     * @return string IP address
     */
    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
    
    /**
     * AJAX handler for getting analytics data
     */
    public function ajax_get_analytics_data() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'environmental-advanced-search'));
        }
        
        check_ajax_referer('eas_admin_nonce', 'nonce');
        
        $args = array(
            'date_from' => sanitize_text_field($_POST['date_from']),
            'date_to' => sanitize_text_field($_POST['date_to']),
            'limit' => absint($_POST['limit']),
            'group_by' => sanitize_text_field($_POST['group_by'])
        );
        
        $data = $this->get_analytics_data($args);
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX handler for exporting analytics
     */
    public function ajax_export_analytics() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'environmental-advanced-search'));
        }
        
        check_ajax_referer('eas_admin_nonce', 'nonce');
        
        $args = array(
            'date_from' => sanitize_text_field($_POST['date_from']),
            'date_to' => sanitize_text_field($_POST['date_to'])
        );
        
        $csv = $this->export_analytics($args);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="search-analytics-' . date('Y-m-d') . '.csv"');
        
        echo $csv;
        exit;
    }
}
