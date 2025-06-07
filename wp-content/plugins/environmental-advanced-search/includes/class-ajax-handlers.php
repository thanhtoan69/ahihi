<?php
/**
 * AJAX Handlers Class
 *
 * @package Environmental_Advanced_Search
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAS_Ajax_Handlers {
    
    /**
     * Search engine instance
     */
    private $search_engine;
    
    /**
     * Faceted search instance
     */
    private $faceted_search;
    
    /**
     * Analytics instance
     */
    private $analytics;
    
    /**
     * Initialize AJAX handlers
     */
    public function __construct() {
        $this->search_engine = new EAS_Search_Engine();
        $this->faceted_search = new EAS_Faceted_Search();
        $this->analytics = new EAS_Search_Analytics();
        
        // Public AJAX handlers
        add_action('wp_ajax_eas_search', array($this, 'handle_search'));
        add_action('wp_ajax_nopriv_eas_search', array($this, 'handle_search'));
        
        add_action('wp_ajax_eas_get_suggestions', array($this, 'handle_get_suggestions'));
        add_action('wp_ajax_nopriv_eas_get_suggestions', array($this, 'handle_get_suggestions'));
        
        add_action('wp_ajax_eas_get_facets', array($this, 'handle_get_facets'));
        add_action('wp_ajax_nopriv_eas_get_facets', array($this, 'handle_get_facets'));
        
        add_action('wp_ajax_eas_track_click', array($this, 'handle_track_click'));
        add_action('wp_ajax_nopriv_eas_track_click', array($this, 'handle_track_click'));
        
        add_action('wp_ajax_eas_load_more', array($this, 'handle_load_more'));
        add_action('wp_ajax_nopriv_eas_load_more', array($this, 'handle_load_more'));
        
        // Admin AJAX handlers
        add_action('wp_ajax_eas_reindex_content', array($this, 'handle_reindex_content'));
        add_action('wp_ajax_eas_test_elasticsearch', array($this, 'handle_test_elasticsearch'));
        add_action('wp_ajax_eas_optimize_search', array($this, 'handle_optimize_search'));
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Handle search AJAX request
     */
    public function handle_search() {
        check_ajax_referer('eas_search_nonce', 'nonce');
        
        $start_time = microtime(true);
        
        $query = sanitize_text_field($_POST['query']);
        $filters = isset($_POST['filters']) ? $this->sanitize_filters($_POST['filters']) : array();
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 10;
        $post_types = isset($_POST['post_types']) ? array_map('sanitize_text_field', $_POST['post_types']) : array();
        
        // Perform search
        $results = $this->search_engine->search($query, array(
            'filters' => $filters,
            'page' => $page,
            'per_page' => $per_page,
            'post_types' => $post_types
        ));
        
        $execution_time = microtime(true) - $start_time;
        
        // Track search analytics
        do_action('eas_search_performed', $query, $filters, $results['total'], $execution_time);
        
        // Prepare response
        $response = array(
            'results' => $this->format_search_results($results['posts']),
            'total' => $results['total'],
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($results['total'] / $per_page),
            'execution_time' => round($execution_time, 4),
            'facets' => $this->faceted_search->get_facets($query, $filters)
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Handle search suggestions AJAX request
     */
    public function handle_get_suggestions() {
        check_ajax_referer('eas_search_nonce', 'nonce');
        
        $query = sanitize_text_field($_POST['query']);
        $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 10;
        
        if (strlen($query) < 2) {
            wp_send_json_success(array('suggestions' => array()));
        }
        
        $suggestions = $this->analytics->get_search_suggestions($query);
        
        wp_send_json_success(array(
            'suggestions' => array_slice($suggestions, 0, $limit)
        ));
    }
    
    /**
     * Handle get facets AJAX request
     */
    public function handle_get_facets() {
        check_ajax_referer('eas_search_nonce', 'nonce');
        
        $query = sanitize_text_field($_POST['query']);
        $filters = isset($_POST['filters']) ? $this->sanitize_filters($_POST['filters']) : array();
        $facet_type = sanitize_text_field($_POST['facet_type']);
        
        $facets = $this->faceted_search->get_facet_data($facet_type, $query, $filters);
        
        wp_send_json_success(array(
            'facets' => $facets
        ));
    }
    
    /**
     * Handle click tracking AJAX request
     */
    public function handle_track_click() {
        check_ajax_referer('eas_search_nonce', 'nonce');
        
        $post_id = absint($_POST['post_id']);
        $query = sanitize_text_field($_POST['query']);
        $position = absint($_POST['position']);
        
        if ($post_id && $query) {
            do_action('eas_search_result_clicked', $post_id, $query, $position);
        }
        
        wp_send_json_success();
    }
    
    /**
     * Handle load more results AJAX request
     */
    public function handle_load_more() {
        check_ajax_referer('eas_search_nonce', 'nonce');
        
        $query = sanitize_text_field($_POST['query']);
        $filters = isset($_POST['filters']) ? $this->sanitize_filters($_POST['filters']) : array();
        $page = absint($_POST['page']);
        $per_page = absint($_POST['per_page']);
        $post_types = isset($_POST['post_types']) ? array_map('sanitize_text_field', $_POST['post_types']) : array();
        
        $results = $this->search_engine->search($query, array(
            'filters' => $filters,
            'page' => $page,
            'per_page' => $per_page,
            'post_types' => $post_types
        ));
        
        $response = array(
            'results' => $this->format_search_results($results['posts']),
            'has_more' => ($page * $per_page) < $results['total']
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Handle content reindexing AJAX request
     */
    public function handle_reindex_content() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'environmental-advanced-search'));
        }
        
        check_ajax_referer('eas_admin_nonce', 'nonce');
        
        $batch_size = isset($_POST['batch_size']) ? absint($_POST['batch_size']) : 100;
        $offset = isset($_POST['offset']) ? absint($_POST['offset']) : 0;
        
        // Get Elasticsearch manager
        $elasticsearch = new EAS_Elasticsearch_Manager();
        
        if (!$elasticsearch->is_available()) {
            wp_send_json_error(__('Elasticsearch is not available', 'environmental-advanced-search'));
        }
        
        // Get posts to index
        $posts = get_posts(array(
            'post_type' => 'any',
            'post_status' => 'publish',
            'numberposts' => $batch_size,
            'offset' => $offset
        ));
        
        $indexed = 0;
        foreach ($posts as $post) {
            if ($elasticsearch->index_post($post->ID)) {
                $indexed++;
            }
        }
        
        $total_posts = wp_count_posts();
        $total_count = 0;
        foreach ($total_posts as $count) {
            $total_count += $count;
        }
        
        $response = array(
            'indexed' => $indexed,
            'offset' => $offset + $batch_size,
            'total' => $total_count,
            'completed' => ($offset + $batch_size) >= $total_count
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Handle Elasticsearch test AJAX request
     */
    public function handle_test_elasticsearch() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'environmental-advanced-search'));
        }
        
        check_ajax_referer('eas_admin_nonce', 'nonce');
        
        $elasticsearch = new EAS_Elasticsearch_Manager();
        
        $test_results = array(
            'connection' => $elasticsearch->is_available(),
            'index_exists' => false,
            'document_count' => 0,
            'can_index' => false,
            'can_search' => false
        );
        
        if ($test_results['connection']) {
            $test_results['index_exists'] = $elasticsearch->index_exists();
            
            if ($test_results['index_exists']) {
                $test_results['document_count'] = $elasticsearch->get_document_count();
                
                // Test indexing
                $test_post_id = wp_insert_post(array(
                    'post_title' => 'EAS Test Post',
                    'post_content' => 'This is a test post for Elasticsearch.',
                    'post_status' => 'draft'
                ));
                
                if ($test_post_id) {
                    $test_results['can_index'] = $elasticsearch->index_post($test_post_id);
                    
                    if ($test_results['can_index']) {
                        // Test searching
                        $search_results = $elasticsearch->search('EAS Test');
                        $test_results['can_search'] = !empty($search_results['hits']);
                    }
                    
                    // Clean up test post
                    wp_delete_post($test_post_id, true);
                }
            }
        }
        
        wp_send_json_success($test_results);
    }
    
    /**
     * Handle search optimization AJAX request
     */
    public function handle_optimize_search() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'environmental-advanced-search'));
        }
        
        check_ajax_referer('eas_admin_nonce', 'nonce');
        
        $optimization_type = sanitize_text_field($_POST['type']);
        $results = array();
        
        switch ($optimization_type) {
            case 'database':
                $results = $this->optimize_database();
                break;
                
            case 'cache':
                $results = $this->clear_search_cache();
                break;
                
            case 'elasticsearch':
                $results = $this->optimize_elasticsearch();
                break;
                
            case 'analytics':
                $results = $this->optimize_analytics();
                break;
                
            default:
                wp_send_json_error(__('Invalid optimization type', 'environmental-advanced-search'));
        }
        
        wp_send_json_success($results);
    }
    
    /**
     * Format search results for response
     *
     * @param WP_Post[] $posts Search results
     * @return array Formatted results
     */
    private function format_search_results($posts) {
        $results = array();
        
        foreach ($posts as $post) {
            $result = array(
                'id' => $post->ID,
                'title' => get_the_title($post),
                'excerpt' => get_the_excerpt($post),
                'url' => get_permalink($post),
                'post_type' => $post->post_type,
                'date' => get_the_date('c', $post),
                'author' => get_the_author_meta('display_name', $post->post_author),
                'featured_image' => get_the_post_thumbnail_url($post, 'medium'),
                'categories' => wp_get_post_categories($post->ID, array('fields' => 'names')),
                'tags' => wp_get_post_tags($post->ID, array('fields' => 'names'))
            );
            
            // Add custom fields
            $custom_fields = get_post_meta($post->ID);
            foreach ($custom_fields as $key => $values) {
                if (!str_starts_with($key, '_')) {
                    $result['meta'][$key] = $values[0];
                }
            }
            
            // Add relevance score if available
            if (isset($post->relevance_score)) {
                $result['relevance_score'] = $post->relevance_score;
            }
            
            $results[] = $result;
        }
        
        return $results;
    }
    
    /**
     * Sanitize search filters
     *
     * @param array $filters Raw filters
     * @return array Sanitized filters
     */
    private function sanitize_filters($filters) {
        $sanitized = array();
        
        foreach ($filters as $key => $value) {
            $key = sanitize_key($key);
            
            if (is_array($value)) {
                $sanitized[$key] = array_map('sanitize_text_field', $value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Optimize database for search
     *
     * @return array Optimization results
     */
    private function optimize_database() {
        global $wpdb;
        
        $results = array();
        
        // Optimize search-related tables
        $tables = array(
            $wpdb->posts,
            $wpdb->postmeta,
            $wpdb->terms,
            $wpdb->term_taxonomy,
            $wpdb->term_relationships,
            $wpdb->prefix . 'eas_search_analytics',
            $wpdb->prefix . 'eas_popular_searches'
        );
        
        foreach ($tables as $table) {
            $result = $wpdb->query("OPTIMIZE TABLE {$table}");
            $results[$table] = $result !== false;
        }
        
        // Update search index statistics
        $wpdb->query("ANALYZE TABLE {$wpdb->posts}");
        
        return array(
            'tables_optimized' => count(array_filter($results)),
            'total_tables' => count($tables),
            'results' => $results
        );
    }
    
    /**
     * Clear search cache
     *
     * @return array Cache clearing results
     */
    private function clear_search_cache() {
        // Clear WordPress object cache
        wp_cache_flush();
        
        // Clear search-specific transients
        $transients_cleared = 0;
        
        $search_transients = get_option('eas_search_transients', array());
        foreach ($search_transients as $transient) {
            if (delete_transient($transient)) {
                $transients_cleared++;
            }
        }
        
        // Clear facet cache
        delete_option('eas_facet_cache');
        
        // Clear suggestion cache
        $suggestion_keys = wp_cache_get('eas_suggestion_keys', 'eas_suggestions');
        if (is_array($suggestion_keys)) {
            foreach ($suggestion_keys as $key) {
                wp_cache_delete($key, 'eas_suggestions');
            }
        }
        wp_cache_delete('eas_suggestion_keys', 'eas_suggestions');
        
        return array(
            'transients_cleared' => $transients_cleared,
            'cache_flushed' => true,
            'facet_cache_cleared' => true,
            'suggestion_cache_cleared' => true
        );
    }
    
    /**
     * Optimize Elasticsearch
     *
     * @return array Optimization results
     */
    private function optimize_elasticsearch() {
        $elasticsearch = new EAS_Elasticsearch_Manager();
        
        if (!$elasticsearch->is_available()) {
            return array('error' => __('Elasticsearch not available', 'environmental-advanced-search'));
        }
        
        $results = array();
        
        // Force merge segments
        $merge_result = $elasticsearch->optimize_index();
        $results['segments_merged'] = $merge_result;
        
        // Refresh index
        $refresh_result = $elasticsearch->refresh_index();
        $results['index_refreshed'] = $refresh_result;
        
        // Get index statistics
        $stats = $elasticsearch->get_index_stats();
        $results['index_stats'] = $stats;
        
        return $results;
    }
    
    /**
     * Optimize analytics data
     *
     * @return array Optimization results
     */
    private function optimize_analytics() {
        // Clean up old analytics data
        $this->analytics->cleanup_old_data();
        
        // Optimize analytics tables
        global $wpdb;
        
        $analytics_table = $wpdb->prefix . 'eas_search_analytics';
        $popular_table = $wpdb->prefix . 'eas_popular_searches';
        
        $wpdb->query("OPTIMIZE TABLE {$analytics_table}");
        $wpdb->query("OPTIMIZE TABLE {$popular_table}");
        
        // Update search statistics
        $total_searches = $wpdb->get_var("SELECT COUNT(*) FROM {$analytics_table} WHERE is_click = 0");
        $unique_queries = $wpdb->get_var("SELECT COUNT(DISTINCT search_query) FROM {$analytics_table} WHERE is_click = 0");
        
        update_option('eas_total_searches', $total_searches);
        update_option('eas_unique_queries', $unique_queries);
        
        return array(
            'old_data_cleaned' => true,
            'tables_optimized' => true,
            'total_searches' => $total_searches,
            'unique_queries' => $unique_queries
        );
    }
    
    /**
     * Enqueue AJAX scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'eas-ajax',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/ajax.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_localize_script('eas-ajax', 'easAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'search_nonce' => wp_create_nonce('eas_search_nonce'),
            'admin_nonce' => wp_create_nonce('eas_admin_nonce'),
            'strings' => array(
                'searching' => __('Searching...', 'environmental-advanced-search'),
                'no_results' => __('No results found', 'environmental-advanced-search'),
                'load_more' => __('Load More', 'environmental-advanced-search'),
                'loading' => __('Loading...', 'environmental-advanced-search'),
                'error' => __('An error occurred', 'environmental-advanced-search')
            )
        ));
    }
}
