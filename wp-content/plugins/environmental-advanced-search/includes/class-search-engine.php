<?php
/**
 * Enhanced Search Engine Class
 * 
 * Handles advanced search functionality with weighted scoring,
 * multi-field search, and integration with external search engines
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAS_Search_Engine {
    
    private $elasticsearch_enabled = false;
    private $search_weights = array();
    private $min_search_length = 3;
    
    public function __construct() {
        $this->elasticsearch_enabled = get_option('eas_enable_elasticsearch', 'no') === 'yes';
        $this->load_search_weights();
        $this->min_search_length = intval(get_option('eas_min_search_length', 3));
        
        add_action('wp_head', array($this, 'add_search_schema'));
        add_filter('body_class', array($this, 'add_search_body_class'));
    }
    
    /**
     * Load search weights from options
     */
    private function load_search_weights() {
        $this->search_weights = array(
            'title' => floatval(get_option('eas_search_weight_title', 10)),
            'content' => floatval(get_option('eas_search_weight_content', 5)),
            'excerpt' => floatval(get_option('eas_search_weight_excerpt', 7)),
            'meta' => floatval(get_option('eas_search_weight_meta', 3)),
            'taxonomy' => floatval(get_option('eas_search_weight_taxonomy', 4))
        );
    }
    
    /**
     * Modify search query to include all post types
     */
    public function include_all_post_types_in_search($query) {
        if (!is_admin() && $query->is_search() && $query->is_main_query()) {
            // Include all public post types in search
            $post_types = get_post_types(array('public' => true, 'exclude_from_search' => false), 'names');
            $query->set('post_type', $post_types);
            
            // Set posts per page
            $posts_per_page = intval(get_option('eas_results_per_page', 10));
            $query->set('posts_per_page', $posts_per_page);
        }
        
        return $query;
    }
    
    /**
     * Modify search query object
     */
    public function modify_search_query($query) {
        if (!is_admin() && $query->is_search() && $query->is_main_query()) {
            $search_term = $query->get('s');
            
            // Check minimum search length
            if (strlen($search_term) < $this->min_search_length) {
                return;
            }
            
            // Log search analytics
            $this->log_search_analytics($search_term, $query);
            
            // If Elasticsearch is enabled, use it
            if ($this->elasticsearch_enabled) {
                $this->use_elasticsearch_search($query);
            }
        }
    }
    
    /**
     * Modify search SQL to include weighted scoring
     */
    public function modify_search_sql($search, $query) {
        global $wpdb;
        
        if (!$query->is_search() || is_admin()) {
            return $search;
        }
        
        $search_term = $query->get('s');
        if (empty($search_term) || strlen($search_term) < $this->min_search_length) {
            return $search;
        }
        
        // If Elasticsearch is enabled, let it handle the search
        if ($this->elasticsearch_enabled) {
            return '';
        }
        
        // Build advanced search query with weighted scoring
        $search = $this->build_weighted_search_query($search_term, $wpdb);
        
        return $search;
    }
    
    /**
     * Build weighted search query
     */
    private function build_weighted_search_query($search_term, $wpdb) {
        $search_term_escaped = $wpdb->esc_like($search_term);
        $search_term_escaped = '%' . $search_term_escaped . '%';
        
        $weight_clauses = array();
        
        // Title search with highest weight
        if ($this->search_weights['title'] > 0) {
            $weight_clauses[] = "
                CASE WHEN {$wpdb->posts}.post_title LIKE %s THEN {$this->search_weights['title']}
                     WHEN {$wpdb->posts}.post_title LIKE %s THEN " . ($this->search_weights['title'] * 0.8) . "
                     ELSE 0 END";
        }
        
        // Content search
        if ($this->search_weights['content'] > 0) {
            $weight_clauses[] = "
                CASE WHEN {$wpdb->posts}.post_content LIKE %s THEN {$this->search_weights['content']}
                     ELSE 0 END";
        }
        
        // Excerpt search
        if ($this->search_weights['excerpt'] > 0) {
            $weight_clauses[] = "
                CASE WHEN {$wpdb->posts}.post_excerpt LIKE %s THEN {$this->search_weights['excerpt']}
                     ELSE 0 END";
        }
        
        // Meta fields search
        if ($this->search_weights['meta'] > 0) {
            $weight_clauses[] = "
                CASE WHEN {$wpdb->posts}.ID IN (
                    SELECT post_id FROM {$wpdb->postmeta} 
                    WHERE meta_value LIKE %s
                ) THEN {$this->search_weights['meta']}
                ELSE 0 END";
        }
        
        // Taxonomy terms search
        if ($this->search_weights['taxonomy'] > 0) {
            $weight_clauses[] = "
                CASE WHEN {$wpdb->posts}.ID IN (
                    SELECT tr.object_id FROM {$wpdb->term_relationships} tr
                    INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                    INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
                    WHERE t.name LIKE %s OR t.slug LIKE %s
                ) THEN {$this->search_weights['taxonomy']}
                ELSE 0 END";
        }
        
        if (empty($weight_clauses)) {
            return '';
        }
        
        // Combine all weight clauses
        $relevance_score = '(' . implode(' + ', $weight_clauses) . ')';
        
        // Build search WHERE clause
        $search_conditions = array();
        
        $search_conditions[] = "({$wpdb->posts}.post_title LIKE %s)";
        $search_conditions[] = "({$wpdb->posts}.post_content LIKE %s)";
        $search_conditions[] = "({$wpdb->posts}.post_excerpt LIKE %s)";
        
        // Meta search
        $search_conditions[] = "({$wpdb->posts}.ID IN (
            SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_value LIKE %s
        ))";
        
        // Taxonomy search
        $search_conditions[] = "({$wpdb->posts}.ID IN (
            SELECT tr.object_id FROM {$wpdb->term_relationships} tr
            INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
            WHERE t.name LIKE %s OR t.slug LIKE %s
        ))";
        
        $search_where = '(' . implode(' OR ', $search_conditions) . ')';
        
        // Prepare parameters for all clauses
        $search_params = array();
        
        // Title parameters (exact and partial match)
        $search_params[] = $search_term_escaped;
        $search_params[] = '%' . $search_term . '%';
        
        // Content parameter
        $search_params[] = $search_term_escaped;
        
        // Excerpt parameter
        $search_params[] = $search_term_escaped;
        
        // Meta parameter
        $search_params[] = $search_term_escaped;
        
        // Taxonomy parameters
        $search_params[] = $search_term_escaped;
        $search_params[] = $search_term_escaped;
        
        // WHERE clause parameters
        $where_params = array(
            $search_term_escaped, // title
            $search_term_escaped, // content
            $search_term_escaped, // excerpt
            $search_term_escaped, // meta
            $search_term_escaped, // taxonomy name
            $search_term_escaped  // taxonomy slug
        );
        
        // Store relevance score for ordering
        global $eas_relevance_score, $eas_search_params, $eas_where_params;
        $eas_relevance_score = $relevance_score;
        $eas_search_params = $search_params;
        $eas_where_params = $where_params;
        
        return " AND $search_where ";
    }
    
    /**
     * Modify search JOIN clause
     */
    public function modify_search_join($join, $query) {
        global $wpdb;
        
        if (!$query->is_search() || is_admin() || $this->elasticsearch_enabled) {
            return $join;
        }
        
        // No additional joins needed for our search
        return $join;
    }
    
    /**
     * Modify search WHERE clause
     */
    public function modify_search_where($where, $query) {
        global $wpdb, $eas_where_params;
        
        if (!$query->is_search() || is_admin() || $this->elasticsearch_enabled) {
            return $where;
        }
        
        if (!empty($eas_where_params)) {
            // Apply prepared statement parameters
            $where = $wpdb->prepare($where, $eas_where_params);
        }
        
        return $where;
    }
    
    /**
     * Modify search ORDER BY clause to use relevance scoring
     */
    public function modify_search_orderby($orderby, $query) {
        global $wpdb, $eas_relevance_score, $eas_search_params;
        
        if (!$query->is_search() || is_admin() || $this->elasticsearch_enabled) {
            return $orderby;
        }
        
        if (!empty($eas_relevance_score) && !empty($eas_search_params)) {
            // Prepare the relevance score calculation
            $relevance_prepared = $wpdb->prepare($eas_relevance_score, $eas_search_params);
            
            // Order by relevance score descending, then by date
            $orderby = "($relevance_prepared) DESC, {$wpdb->posts}.post_date DESC";
        }
        
        return $orderby;
    }
    
    /**
     * Use Elasticsearch for search
     */
    private function use_elasticsearch_search($query) {
        $elasticsearch_manager = EnvironmentalAdvancedSearch::getInstance()->get_elasticsearch_manager();
        
        if ($elasticsearch_manager && $elasticsearch_manager->is_available()) {
            $search_term = $query->get('s');
            $filters = $this->get_current_filters();
            
            $results = $elasticsearch_manager->search($search_term, $filters);
            
            if ($results && !empty($results['hits'])) {
                $post_ids = array();
                foreach ($results['hits'] as $hit) {
                    $post_ids[] = $hit['_source']['post_id'];
                }
                
                if (!empty($post_ids)) {
                    $query->set('post__in', $post_ids);
                    $query->set('orderby', 'post__in');
                }
            }
        }
    }
    
    /**
     * Get current search filters
     */
    private function get_current_filters() {
        $filters = array();
        
        // Get filters from URL parameters
        if (isset($_GET['post_type']) && !empty($_GET['post_type'])) {
            $filters['post_type'] = sanitize_text_field($_GET['post_type']);
        }
        
        if (isset($_GET['category']) && !empty($_GET['category'])) {
            $filters['category'] = sanitize_text_field($_GET['category']);
        }
        
        if (isset($_GET['location']) && !empty($_GET['location'])) {
            $filters['location'] = sanitize_text_field($_GET['location']);
        }
        
        if (isset($_GET['date_range']) && !empty($_GET['date_range'])) {
            $filters['date_range'] = sanitize_text_field($_GET['date_range']);
        }
        
        return $filters;
    }
    
    /**
     * Enhanced search form
     */
    public function enhanced_search_form($form) {
        $search_value = get_search_query();
        $placeholder = get_option('eas_search_placeholder', __('Search environmental content...', 'environmental-advanced-search'));
        
        $enhanced_form = '
        <form role="search" method="get" class="eas-search-form" action="' . esc_url(home_url('/')) . '">
            <div class="eas-search-wrapper">
                <div class="eas-search-input-wrapper">
                    <input type="search" 
                           class="eas-search-field" 
                           placeholder="' . esc_attr($placeholder) . '" 
                           value="' . esc_attr($search_value) . '" 
                           name="s" 
                           autocomplete="off"
                           data-eas-search="true" />
                    <button type="submit" class="eas-search-submit">
                        <svg class="eas-search-icon" width="16" height="16" viewBox="0 0 16 16">
                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                        </svg>
                        <span class="screen-reader-text">' . __('Search', 'environmental-advanced-search') . '</span>
                    </button>
                </div>
                <div class="eas-search-suggestions" style="display: none;"></div>
            </div>
        </form>';
        
        return $enhanced_form;
    }
    
    /**
     * Log search analytics
     */
    private function log_search_analytics($search_term, $query) {
        $analytics = EnvironmentalAdvancedSearch::getInstance()->get_search_analytics();
        if ($analytics) {
            $filters = $this->get_current_filters();
            $analytics->log_search($search_term, $filters);
        }
    }
    
    /**
     * Add search schema markup
     */
    public function add_search_schema() {
        if (is_search()) {
            $search_term = get_search_query();
            global $wp_query;
            $results_count = $wp_query->found_posts;
            
            $schema = array(
                '@context' => 'https://schema.org',
                '@type' => 'SearchResultsPage',
                'mainEntity' => array(
                    '@type' => 'SearchAction',
                    'query' => $search_term,
                    'result' => array(
                        '@type' => 'SearchResults',
                        'numberOfItems' => $results_count
                    )
                )
            );
            
            echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>';
        }
    }
    
    /**
     * Add search-specific body classes
     */
    public function add_search_body_class($classes) {
        if (is_search()) {
            $classes[] = 'eas-search-results';
            
            global $wp_query;
            if ($wp_query->found_posts == 0) {
                $classes[] = 'eas-no-results';
            } else {
                $classes[] = 'eas-has-results';
            }
            
            if ($this->elasticsearch_enabled) {
                $classes[] = 'eas-elasticsearch-enabled';
            }
        }
        
        return $classes;
    }
    
    /**
     * Get search suggestions
     */
    public function get_search_suggestions($term, $limit = 5) {
        if (strlen($term) < 2) {
            return array();
        }
        
        global $wpdb;
        
        $suggestions = array();
        
        // Get popular searches
        $popular_table = $wpdb->prefix . 'eas_popular_searches';
        $popular_searches = $wpdb->get_results($wpdb->prepare("
            SELECT search_term, search_count 
            FROM $popular_table 
            WHERE search_term LIKE %s 
            ORDER BY search_count DESC 
            LIMIT %d
        ", '%' . $wpdb->esc_like($term) . '%', $limit));
        
        foreach ($popular_searches as $search) {
            $suggestions[] = array(
                'term' => $search->search_term,
                'type' => 'popular',
                'count' => $search->search_count
            );
        }
        
        // Get content suggestions if not enough popular searches
        if (count($suggestions) < $limit) {
            $remaining = $limit - count($suggestions);
            
            $content_suggestions = $wpdb->get_results($wpdb->prepare("
                SELECT post_title as term, post_type, ID
                FROM {$wpdb->posts} 
                WHERE post_status = 'publish' 
                AND post_title LIKE %s 
                ORDER BY post_date DESC 
                LIMIT %d
            ", '%' . $wpdb->esc_like($term) . '%', $remaining));
            
            foreach ($content_suggestions as $content) {
                $suggestions[] = array(
                    'term' => $content->term,
                    'type' => 'content',
                    'post_type' => $content->post_type,
                    'post_id' => $content->ID
                );
            }
        }
        
        return $suggestions;
    }
    
    /**
     * Get search statistics
     */
    public function get_search_stats() {
        global $wpdb;
        
        $analytics_table = $wpdb->prefix . 'eas_search_analytics';
        $popular_table = $wpdb->prefix . 'eas_popular_searches';
        
        $stats = array();
        
        // Total searches today
        $stats['searches_today'] = $wpdb->get_var("
            SELECT COUNT(*) FROM $analytics_table 
            WHERE DATE(search_timestamp) = CURDATE()
        ");
        
        // Total searches this week
        $stats['searches_week'] = $wpdb->get_var("
            SELECT COUNT(*) FROM $analytics_table 
            WHERE search_timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        
        // Average response time
        $stats['avg_response_time'] = $wpdb->get_var("
            SELECT AVG(response_time) FROM $analytics_table 
            WHERE search_timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        
        // Most popular searches
        $stats['popular_terms'] = $wpdb->get_results("
            SELECT search_term, search_count 
            FROM $popular_table 
            ORDER BY search_count DESC 
            LIMIT 10
        ");
        
        // Zero result searches
        $stats['zero_results'] = $wpdb->get_results("
            SELECT search_term, COUNT(*) as count
            FROM $analytics_table 
            WHERE results_count = 0 
            AND search_timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY search_term 
            ORDER BY count DESC 
            LIMIT 10
        ");
        
        return $stats;
    }
}
