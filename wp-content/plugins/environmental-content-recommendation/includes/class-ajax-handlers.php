<?php
/**
 * AJAX Handlers Class
 *
 * Handles AJAX requests for the Environmental Content Recommendation plugin
 * Manages frontend interactions, user behavior tracking, and dynamic content loading
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ECR_Ajax_Handlers {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Recommendation engine instance
     */
    private $recommendation_engine;
    
    /**
     * User behavior tracker instance
     */
    private $behavior_tracker;
    
    /**
     * Performance tracker instance
     */
    private $performance_tracker;
    
    /**
     * Content analyzer instance
     */
    private $content_analyzer;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->recommendation_engine = ECR_Recommendation_Engine::get_instance();
        $this->behavior_tracker = ECR_User_Behavior_Tracker::get_instance();
        $this->performance_tracker = ECR_Performance_Tracker::get_instance();
        $this->content_analyzer = ECR_Content_Analyzer::get_instance();
        
        // Frontend AJAX handlers (for both logged-in and non-logged-in users)
        add_action('wp_ajax_ecr_get_recommendations', array($this, 'ajax_get_recommendations'));
        add_action('wp_ajax_nopriv_ecr_get_recommendations', array($this, 'ajax_get_recommendations'));
        
        add_action('wp_ajax_ecr_track_interaction', array($this, 'ajax_track_interaction'));
        add_action('wp_ajax_nopriv_ecr_track_interaction', array($this, 'ajax_track_interaction'));
        
        add_action('wp_ajax_ecr_get_similar_content', array($this, 'ajax_get_similar_content'));
        add_action('wp_ajax_nopriv_ecr_get_similar_content', array($this, 'ajax_get_similar_content'));
        
        add_action('wp_ajax_ecr_rate_recommendation', array($this, 'ajax_rate_recommendation'));
        add_action('wp_ajax_nopriv_ecr_rate_recommendation', array($this, 'ajax_rate_recommendation'));
        
        add_action('wp_ajax_ecr_load_more_recommendations', array($this, 'ajax_load_more_recommendations'));
        add_action('wp_ajax_nopriv_ecr_load_more_recommendations', array($this, 'ajax_load_more_recommendations'));
        
        add_action('wp_ajax_ecr_update_preferences', array($this, 'ajax_update_preferences'));
        add_action('wp_ajax_nopriv_ecr_update_preferences', array($this, 'ajax_update_preferences'));
        
        add_action('wp_ajax_ecr_search_content', array($this, 'ajax_search_content'));
        add_action('wp_ajax_nopriv_ecr_search_content', array($this, 'ajax_search_content'));
        
        add_action('wp_ajax_ecr_get_trending', array($this, 'ajax_get_trending'));
        add_action('wp_ajax_nopriv_ecr_get_trending', array($this, 'ajax_get_trending'));
        
        add_action('wp_ajax_ecr_dismiss_recommendation', array($this, 'ajax_dismiss_recommendation'));
        add_action('wp_ajax_nopriv_ecr_dismiss_recommendation', array($this, 'ajax_dismiss_recommendation'));
        
        add_action('wp_ajax_ecr_get_user_stats', array($this, 'ajax_get_user_stats'));
        add_action('wp_ajax_nopriv_ecr_get_user_stats', array($this, 'ajax_get_user_stats'));
    }
    
    /**
     * Get recommendations via AJAX
     */
    public function ajax_get_recommendations() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ecr_frontend_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'environmental-content-recommendation')));
            return;
        }
        
        $user_id = get_current_user_id();
        $type = sanitize_text_field($_POST['type'] ?? 'personalized');
        $count = intval($_POST['count'] ?? 5);
        $exclude = array_map('intval', $_POST['exclude'] ?? array());
        $context = sanitize_text_field($_POST['context'] ?? '');
        $content_id = intval($_POST['content_id'] ?? 0);
        
        // Rate limiting check
        if (!$this->check_rate_limit($user_id)) {
            wp_send_json_error(array('message' => __('Rate limit exceeded', 'environmental-content-recommendation')));
            return;
        }
        
        try {
            $recommendations = array();
            
            switch ($type) {
                case 'personalized':
                    $recommendations = $this->recommendation_engine->get_personalized_recommendations($user_id, $count, $exclude);
                    break;
                    
                case 'similar':
                    if ($content_id > 0) {
                        $recommendations = $this->recommendation_engine->get_similar_content($content_id, $count, $exclude);
                    }
                    break;
                    
                case 'trending':
                    $recommendations = $this->recommendation_engine->get_trending_recommendations($count, $exclude);
                    break;
                    
                case 'environmental':
                    $recommendations = $this->recommendation_engine->get_environmental_recommendations($count, $exclude);
                    break;
                    
                case 'collaborative':
                    $recommendations = $this->recommendation_engine->get_collaborative_recommendations($user_id, $count, $exclude);
                    break;
                    
                default:
                    $recommendations = $this->recommendation_engine->get_hybrid_recommendations($user_id, $content_id, $count, $exclude);
                    break;
            }
            
            // Format recommendations for frontend
            $formatted_recommendations = $this->format_recommendations($recommendations, $context);
            
            // Track impression
            $this->performance_tracker->track_impressions($recommendations, array(
                'user_id' => $user_id,
                'type' => $type,
                'context' => $context,
                'position' => 'ajax_request'
            ));
            
            wp_send_json_success(array(
                'recommendations' => $formatted_recommendations,
                'total' => count($formatted_recommendations),
                'type' => $type
            ));
            
        } catch (Exception $e) {
            error_log('ECR AJAX Error: ' . $e->getMessage());
            wp_send_json_error(array('message' => __('Failed to load recommendations', 'environmental-content-recommendation')));
        }
    }
    
    /**
     * Track user interaction via AJAX
     */
    public function ajax_track_interaction() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ecr_frontend_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'environmental-content-recommendation')));
            return;
        }
        
        $user_id = get_current_user_id();
        $action = sanitize_text_field($_POST['action_type'] ?? '');
        $content_id = intval($_POST['content_id'] ?? 0);
        $recommendation_id = intval($_POST['recommendation_id'] ?? 0);
        $position = intval($_POST['position'] ?? 0);
        $context = sanitize_text_field($_POST['context'] ?? '');
        $value = floatval($_POST['value'] ?? 1.0);
        $metadata = $_POST['metadata'] ?? array();
        
        if (empty($action) || $content_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid parameters', 'environmental-content-recommendation')));
            return;
        }
        
        try {
            // Track the interaction
            $this->behavior_tracker->track_interaction($user_id, $content_id, $action, $value, $metadata);
            
            // Track recommendation performance if applicable
            if ($recommendation_id > 0) {
                $this->performance_tracker->track_click($recommendation_id, array(
                    'user_id' => $user_id,
                    'position' => $position,
                    'context' => $context
                ));
            }
            
            // Update user preferences based on interaction
            $this->update_user_preferences_from_interaction($user_id, $content_id, $action, $value);
            
            wp_send_json_success(array('message' => __('Interaction tracked', 'environmental-content-recommendation')));
            
        } catch (Exception $e) {
            error_log('ECR Tracking Error: ' . $e->getMessage());
            wp_send_json_error(array('message' => __('Failed to track interaction', 'environmental-content-recommendation')));
        }
    }
    
    /**
     * Get similar content via AJAX
     */
    public function ajax_get_similar_content() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ecr_frontend_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'environmental-content-recommendation')));
            return;
        }
        
        $content_id = intval($_POST['content_id'] ?? 0);
        $count = intval($_POST['count'] ?? 5);
        $exclude = array_map('intval', $_POST['exclude'] ?? array());
        $similarity_threshold = floatval($_POST['threshold'] ?? 0.3);
        
        if ($content_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid content ID', 'environmental-content-recommendation')));
            return;
        }
        
        try {
            $similar_content = $this->recommendation_engine->get_similar_content($content_id, $count, $exclude, $similarity_threshold);
            $formatted_content = $this->format_recommendations($similar_content, 'similar_content');
            
            wp_send_json_success(array(
                'similar_content' => $formatted_content,
                'total' => count($formatted_content),
                'source_id' => $content_id
            ));
            
        } catch (Exception $e) {
            error_log('ECR Similar Content Error: ' . $e->getMessage());
            wp_send_json_error(array('message' => __('Failed to load similar content', 'environmental-content-recommendation')));
        }
    }
    
    /**
     * Rate recommendation via AJAX
     */
    public function ajax_rate_recommendation() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ecr_frontend_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'environmental-content-recommendation')));
            return;
        }
        
        $user_id = get_current_user_id();
        $recommendation_id = intval($_POST['recommendation_id'] ?? 0);
        $content_id = intval($_POST['content_id'] ?? 0);
        $rating = floatval($_POST['rating'] ?? 0);
        $feedback = sanitize_textarea_field($_POST['feedback'] ?? '');
        
        if ($content_id <= 0 || $rating < 1 || $rating > 5) {
            wp_send_json_error(array('message' => __('Invalid rating parameters', 'environmental-content-recommendation')));
            return;
        }
        
        try {
            // Store rating in database
            global $wpdb;
            
            $wpdb->replace(
                $wpdb->prefix . 'ecr_user_ratings',
                array(
                    'user_id' => $user_id,
                    'content_id' => $content_id,
                    'recommendation_id' => $recommendation_id,
                    'rating' => $rating,
                    'feedback' => $feedback,
                    'timestamp' => current_time('mysql')
                ),
                array('%d', '%d', '%d', '%f', '%s', '%s')
            );
            
            // Update user preferences based on rating
            $this->behavior_tracker->learn_from_rating($user_id, $content_id, $rating);
            
            // Track performance
            if ($recommendation_id > 0) {
                $this->performance_tracker->track_rating($recommendation_id, $rating);
            }
            
            wp_send_json_success(array('message' => __('Rating saved', 'environmental-content-recommendation')));
            
        } catch (Exception $e) {
            error_log('ECR Rating Error: ' . $e->getMessage());
            wp_send_json_error(array('message' => __('Failed to save rating', 'environmental-content-recommendation')));
        }
    }
    
    /**
     * Load more recommendations via AJAX
     */
    public function ajax_load_more_recommendations() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ecr_frontend_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'environmental-content-recommendation')));
            return;
        }
        
        $user_id = get_current_user_id();
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 5);
        $type = sanitize_text_field($_POST['type'] ?? 'personalized');
        $exclude = array_map('intval', $_POST['exclude'] ?? array());
        
        $offset = ($page - 1) * $per_page;
        
        try {
            $recommendations = $this->recommendation_engine->get_recommendations_paginated(
                $user_id, 
                $type, 
                $per_page, 
                $offset, 
                $exclude
            );
            
            $formatted_recommendations = $this->format_recommendations($recommendations, 'load_more');
            
            wp_send_json_success(array(
                'recommendations' => $formatted_recommendations,
                'page' => $page,
                'has_more' => count($recommendations) === $per_page
            ));
            
        } catch (Exception $e) {
            error_log('ECR Load More Error: ' . $e->getMessage());
            wp_send_json_error(array('message' => __('Failed to load more recommendations', 'environmental-content-recommendation')));
        }
    }
    
    /**
     * Update user preferences via AJAX
     */
    public function ajax_update_preferences() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ecr_frontend_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'environmental-content-recommendation')));
            return;
        }
        
        $user_id = get_current_user_id();
        $preferences = $_POST['preferences'] ?? array();
        
        if (empty($preferences) || !is_array($preferences)) {
            wp_send_json_error(array('message' => __('Invalid preferences data', 'environmental-content-recommendation')));
            return;
        }
        
        try {
            // Sanitize preferences
            $sanitized_preferences = array();
            foreach ($preferences as $key => $value) {
                $sanitized_key = sanitize_key($key);
                if (is_numeric($value)) {
                    $sanitized_preferences[$sanitized_key] = floatval($value);
                } else {
                    $sanitized_preferences[$sanitized_key] = sanitize_text_field($value);
                }
            }
            
            // Update user preferences in database
            global $wpdb;
            
            $existing_prefs = $wpdb->get_var($wpdb->prepare(
                "SELECT preferences FROM {$wpdb->prefix}ecr_user_preferences WHERE user_id = %d",
                $user_id
            ));
            
            if ($existing_prefs) {
                $current_prefs = json_decode($existing_prefs, true) ?: array();
                $updated_prefs = array_merge($current_prefs, $sanitized_preferences);
            } else {
                $updated_prefs = $sanitized_preferences;
            }
            
            $wpdb->replace(
                $wpdb->prefix . 'ecr_user_preferences',
                array(
                    'user_id' => $user_id,
                    'preferences' => json_encode($updated_prefs),
                    'updated_at' => current_time('mysql')
                ),
                array('%d', '%s', '%s')
            );
            
            wp_send_json_success(array('message' => __('Preferences updated', 'environmental-content-recommendation')));
            
        } catch (Exception $e) {
            error_log('ECR Preferences Error: ' . $e->getMessage());
            wp_send_json_error(array('message' => __('Failed to update preferences', 'environmental-content-recommendation')));
        }
    }
    
    /**
     * Search content via AJAX
     */
    public function ajax_search_content() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ecr_frontend_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'environmental-content-recommendation')));
            return;
        }
        
        $query = sanitize_text_field($_POST['query'] ?? '');
        $content_types = $_POST['content_types'] ?? array('post', 'page');
        $count = intval($_POST['count'] ?? 10);
        $include_recommendations = $_POST['include_recommendations'] ?? true;
        
        if (empty($query) || strlen($query) < 3) {
            wp_send_json_error(array('message' => __('Search query too short', 'environmental-content-recommendation')));
            return;
        }
        
        try {
            // Perform search
            $search_args = array(
                'post_type' => array_map('sanitize_text_field', $content_types),
                'posts_per_page' => $count,
                's' => $query,
                'post_status' => 'publish'
            );
            
            $search_results = get_posts($search_args);
            $formatted_results = array();
            
            foreach ($search_results as $post) {
                $formatted_results[] = array(
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'excerpt' => wp_trim_words($post->post_content, 20),
                    'url' => get_permalink($post->ID),
                    'type' => $post->post_type,
                    'date' => get_the_date('', $post->ID),
                    'author' => get_the_author_meta('display_name', $post->post_author),
                    'thumbnail' => get_the_post_thumbnail_url($post->ID, 'medium'),
                    'environmental_score' => $this->content_analyzer->get_environmental_score($post->ID)
                );
            }
            
            $response = array(
                'results' => $formatted_results,
                'total' => count($formatted_results),
                'query' => $query
            );
            
            // Add personalized recommendations based on search
            if ($include_recommendations && !empty($search_results)) {
                $user_id = get_current_user_id();
                $content_ids = wp_list_pluck($search_results, 'ID');
                $related_recommendations = $this->recommendation_engine->get_search_based_recommendations($user_id, $query, $content_ids, 3);
                $response['recommendations'] = $this->format_recommendations($related_recommendations, 'search_based');
            }
            
            wp_send_json_success($response);
            
        } catch (Exception $e) {
            error_log('ECR Search Error: ' . $e->getMessage());
            wp_send_json_error(array('message' => __('Search failed', 'environmental-content-recommendation')));
        }
    }
    
    /**
     * Get trending content via AJAX
     */
    public function ajax_get_trending() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ecr_frontend_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'environmental-content-recommendation')));
            return;
        }
        
        $period = sanitize_text_field($_POST['period'] ?? 'week');
        $count = intval($_POST['count'] ?? 10);
        $content_types = $_POST['content_types'] ?? array('post');
        
        try {
            $trending_content = $this->recommendation_engine->get_trending_content($period, $count, $content_types);
            $formatted_content = $this->format_recommendations($trending_content, 'trending');
            
            wp_send_json_success(array(
                'trending' => $formatted_content,
                'period' => $period,
                'total' => count($formatted_content)
            ));
            
        } catch (Exception $e) {
            error_log('ECR Trending Error: ' . $e->getMessage());
            wp_send_json_error(array('message' => __('Failed to load trending content', 'environmental-content-recommendation')));
        }
    }
    
    /**
     * Dismiss recommendation via AJAX
     */
    public function ajax_dismiss_recommendation() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ecr_frontend_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'environmental-content-recommendation')));
            return;
        }
        
        $user_id = get_current_user_id();
        $content_id = intval($_POST['content_id'] ?? 0);
        $reason = sanitize_text_field($_POST['reason'] ?? 'not_interested');
        
        if ($content_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid content ID', 'environmental-content-recommendation')));
            return;
        }
        
        try {
            // Store dismissal in database
            global $wpdb;
            
            $wpdb->insert(
                $wpdb->prefix . 'ecr_user_dismissals',
                array(
                    'user_id' => $user_id,
                    'content_id' => $content_id,
                    'reason' => $reason,
                    'timestamp' => current_time('mysql')
                ),
                array('%d', '%d', '%s', '%s')
            );
            
            // Update user preferences to reduce similar recommendations
            $this->behavior_tracker->learn_from_dismissal($user_id, $content_id, $reason);
            
            wp_send_json_success(array('message' => __('Recommendation dismissed', 'environmental-content-recommendation')));
            
        } catch (Exception $e) {
            error_log('ECR Dismissal Error: ' . $e->getMessage());
            wp_send_json_error(array('message' => __('Failed to dismiss recommendation', 'environmental-content-recommendation')));
        }
    }
    
    /**
     * Get user statistics via AJAX
     */
    public function ajax_get_user_stats() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ecr_frontend_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'environmental-content-recommendation')));
            return;
        }
        
        $user_id = get_current_user_id();
        
        try {
            $stats = $this->behavior_tracker->get_user_stats($user_id);
            
            wp_send_json_success(array(
                'stats' => $stats,
                'user_id' => $user_id
            ));
            
        } catch (Exception $e) {
            error_log('ECR User Stats Error: ' . $e->getMessage());
            wp_send_json_error(array('message' => __('Failed to load user statistics', 'environmental-content-recommendation')));
        }
    }
    
    /**
     * Format recommendations for frontend display
     */
    private function format_recommendations($recommendations, $context = '') {
        $formatted = array();
        
        foreach ($recommendations as $rec) {
            $post_id = is_object($rec) ? $rec->ID : $rec['content_id'];
            $post = get_post($post_id);
            
            if (!$post) {
                continue;
            }
            
            $formatted[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'excerpt' => wp_trim_words($post->post_content, 20),
                'url' => get_permalink($post->ID),
                'type' => $post->post_type,
                'date' => get_the_date('', $post->ID),
                'author' => get_the_author_meta('display_name', $post->post_author),
                'thumbnail' => get_the_post_thumbnail_url($post->ID, 'medium'),
                'categories' => wp_get_post_categories($post->ID, array('fields' => 'names')),
                'tags' => wp_get_post_tags($post->ID, array('fields' => 'names')),
                'environmental_score' => $this->content_analyzer->get_environmental_score($post->ID),
                'recommendation_score' => is_object($rec) ? ($rec->score ?? 0) : ($rec['score'] ?? 0),
                'context' => $context,
                'timestamp' => current_time('timestamp')
            );
        }
        
        return $formatted;
    }
    
    /**
     * Check rate limiting for user
     */
    private function check_rate_limit($user_id) {
        $options = get_option('ecr_options', array());
        $rate_limit = intval($options['api_rate_limit'] ?? 100);
        $window = 3600; // 1 hour
        
        $cache_key = 'ecr_rate_limit_' . ($user_id ?: 'guest_' . $_SERVER['REMOTE_ADDR']);
        $requests = get_transient($cache_key) ?: 0;
        
        if ($requests >= $rate_limit) {
            return false;
        }
        
        set_transient($cache_key, $requests + 1, $window);
        return true;
    }
    
    /**
     * Update user preferences based on interaction
     */
    private function update_user_preferences_from_interaction($user_id, $content_id, $action, $value) {
        if ($user_id <= 0) {
            return;
        }
        
        // Get content features
        $features = $this->content_analyzer->get_content_features($content_id);
        if (empty($features)) {
            return;
        }
        
        // Calculate preference weights based on action and value
        $weight_multiplier = $this->get_action_weight($action) * $value;
        
        // Update preferences
        $this->behavior_tracker->update_preferences_from_features($user_id, $features, $weight_multiplier);
    }
    
    /**
     * Get weight multiplier for different actions
     */
    private function get_action_weight($action) {
        $weights = array(
            'view' => 1.0,
            'click' => 2.0,
            'share' => 3.0,
            'like' => 2.5,
            'comment' => 3.5,
            'scroll' => 0.5,
            'time_spent' => 1.5
        );
        
        return $weights[$action] ?? 1.0;
    }
}

// Initialize AJAX handlers
ECR_Ajax_Handlers::get_instance();
