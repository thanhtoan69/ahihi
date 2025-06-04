<?php
/**
 * AI-Powered Matching Engine for Item Exchange
 * 
 * Provides intelligent matching between items and requests
 * using machine learning algorithms and environmental scoring
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Item_Exchange_Matching_Engine {
    
    private static $instance = null;
    private $db_manager;
    private $geolocation;
    private $weights;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
      private function __construct() {
        $this->db_manager = Environmental_Item_Exchange_Database_Manager::get_instance();
        $this->geolocation = Environmental_Item_Exchange_Geolocation::get_instance();
        
        // Load optimized weights or use defaults
        $saved_weights = get_option('ep_matching_weights');
        $this->weights = $saved_weights ?: array(
            'category_match' => 0.25,
            'location_proximity' => 0.20,
            'environmental_impact' => 0.15,
            'user_compatibility' => 0.15,
            'item_condition' => 0.10,
            'value_range' => 0.10,
            'urgency' => 0.05
        );
        
        // Normalize weights to ensure they sum to 1.0
        $total_weight = array_sum($this->weights);
        if ($total_weight > 0) {
            foreach ($this->weights as $factor => $weight) {
                $this->weights[$factor] = $weight / $total_weight;
            }
        }
        
        add_action('wp_ajax_ep_get_matches', array($this, 'ajax_get_matches'));
        add_action('wp_ajax_ep_update_match_feedback', array($this, 'ajax_update_match_feedback'));
        add_action('wp_ajax_ep_get_match_explanation', array($this, 'ajax_get_match_explanation'));
        add_action('wp_ajax_ep_batch_match', array($this, 'ajax_batch_match'));
        add_action('wp_cron_update_matches', array($this, 'update_all_matches'));
        add_action('wp_cron_optimize_weights', array($this, 'update_weights_from_feedback'));
        
        // Schedule regular match updates
        if (!wp_next_scheduled('ep_cron_update_matches')) {
            wp_schedule_event(time(), 'hourly', 'ep_cron_update_matches');
        }
        
        // Schedule weekly weight optimization
        if (!wp_next_scheduled('ep_cron_optimize_weights')) {
            wp_schedule_event(time(), 'weekly', 'ep_cron_optimize_weights');
        }
    }
    
    /**
     * Find matches for a specific exchange post
     */
    public function find_matches($post_id, $limit = 10) {
        global $wpdb;
        
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'item_exchange') {
            return array();
        }
        
        $post_meta = get_post_meta($post_id);
        $exchange_type = $post_meta['_exchange_type'][0] ?? 'exchange';
        $category = wp_get_post_terms($post_id, 'exchange_type', array('fields' => 'slugs'));
        $location = $post_meta['_exchange_location'][0] ?? '';
        
        // Find potential matches
        $potential_matches = $this->get_potential_matches($post_id, $exchange_type, $category, $location);
        
        // Score each match
        $scored_matches = array();
        foreach ($potential_matches as $match) {
            $score = $this->calculate_match_score($post_id, $match->ID);
            if ($score > 0.3) { // Minimum threshold
                $scored_matches[] = array(
                    'post_id' => $match->ID,
                    'score' => $score,
                    'reasons' => $this->get_match_reasons($post_id, $match->ID),
                    'post' => $match
                );
            }
        }
        
        // Sort by score and return top matches
        usort($scored_matches, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return array_slice($scored_matches, 0, $limit);
    }
    
    /**
     * Get potential matches from database
     */
    private function get_potential_matches($post_id, $exchange_type, $categories, $location) {
        $complementary_types = array(
            'give_away' => array('request'),
            'exchange' => array('exchange', 'request'),
            'lending' => array('request'),
            'request' => array('give_away', 'exchange', 'lending')
        );
        
        $target_types = $complementary_types[$exchange_type] ?? array();
        
        $args = array(
            'post_type' => 'item_exchange',
            'post_status' => 'publish',
            'posts_per_page' => 50,
            'post__not_in' => array($post_id),
            'meta_query' => array(
                array(
                    'key' => '_exchange_status',
                    'value' => 'active',
                    'compare' => '='
                )
            )
        );
        
        if (!empty($target_types)) {
            $args['meta_query'][] = array(
                'key' => '_exchange_type',
                'value' => $target_types,
                'compare' => 'IN'
            );
        }
        
        if (!empty($categories)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'exchange_type',
                    'field' => 'slug',
                    'terms' => $categories,
                    'operator' => 'IN'
                )
            );
        }
        
        return get_posts($args);
    }
      /**
     * Calculate comprehensive match score
     */
    private function calculate_match_score($post_id_1, $post_id_2) {
        $scores = array();
        
        // Category compatibility
        $scores['category_match'] = $this->calculate_category_score($post_id_1, $post_id_2);
        
        // Location proximity
        $scores['location_proximity'] = $this->calculate_location_score($post_id_1, $post_id_2);
        
        // Environmental impact
        $scores['environmental_impact'] = $this->calculate_environmental_score($post_id_1, $post_id_2);
        
        // User compatibility
        $scores['user_compatibility'] = $this->calculate_user_compatibility_score($post_id_1, $post_id_2);
        
        // Item condition matching
        $scores['item_condition'] = $this->calculate_condition_score($post_id_1, $post_id_2);
        
        // Value range compatibility
        $scores['value_range'] = $this->calculate_value_score($post_id_1, $post_id_2);
        
        // Urgency factor
        $scores['urgency'] = $this->calculate_urgency_score($post_id_1, $post_id_2);
        
        // Add semantic text matching
        $semantic_score = $this->calculate_semantic_score($post_id_1, $post_id_2);
        if ($semantic_score > 0) {
            // Boost overall score based on semantic similarity
            foreach ($scores as $factor => $score) {
                $scores[$factor] = $score + ($semantic_score * 0.1); // 10% boost
            }
        }
        
        // Calculate weighted total
        $total_score = 0;
        foreach ($scores as $factor => $score) {
            $total_score += $score * $this->weights[$factor];
        }
        
        return min(1.0, max(0.0, $total_score));
    }
    
    /**
     * Calculate semantic similarity score between posts
     */
    private function calculate_semantic_score($post_id_1, $post_id_2) {
        $post1 = get_post($post_id_1);
        $post2 = get_post($post_id_2);
        
        if (!$post1 || !$post2) {
            return 0.0;
        }
        
        // Combine title and content for semantic analysis
        $text1 = $post1->post_title . ' ' . $post1->post_content;
        $text2 = $post2->post_title . ' ' . $post2->post_content;
        
        // Add meta fields that might contain relevant keywords
        $meta1 = get_post_meta($post_id_1, '_item_keywords', true);
        $meta2 = get_post_meta($post_id_2, '_item_keywords', true);
        
        if ($meta1) $text1 .= ' ' . $meta1;
        if ($meta2) $text2 .= ' ' . $meta2;
        
        return $this->semantic_text_match($text1, $text2);
    }
    
    /**
     * Calculate category compatibility score
     */
    private function calculate_category_score($post_id_1, $post_id_2) {
        $categories_1 = wp_get_post_terms($post_id_1, 'exchange_type', array('fields' => 'slugs'));
        $categories_2 = wp_get_post_terms($post_id_2, 'exchange_type', array('fields' => 'slugs'));
        
        if (empty($categories_1) || empty($categories_2)) {
            return 0.0;
        }
        
        $intersection = array_intersect($categories_1, $categories_2);
        $union = array_unique(array_merge($categories_1, $categories_2));
        
        return count($intersection) / count($union);
    }
    
    /**
     * Calculate location proximity score
     */
    private function calculate_location_score($post_id_1, $post_id_2) {
        $location_1 = get_post_meta($post_id_1, '_exchange_location', true);
        $location_2 = get_post_meta($post_id_2, '_exchange_location', true);
        
        if (empty($location_1) || empty($location_2)) {
            return 0.5; // Neutral score if no location data
        }
        
        $distance = $this->geolocation->calculate_distance(
            $location_1['lat'] ?? 0,
            $location_1['lng'] ?? 0,
            $location_2['lat'] ?? 0,
            $location_2['lng'] ?? 0
        );
        
        // Score decreases with distance (max useful distance: 50km)
        return max(0.0, 1.0 - ($distance / 50));
    }
    
    /**
     * Calculate environmental impact score
     */
    private function calculate_environmental_score($post_id_1, $post_id_2) {
        $eco_points_1 = get_post_meta($post_id_1, '_eco_points_reward', true) ?: 0;
        $eco_points_2 = get_post_meta($post_id_2, '_eco_points_reward', true) ?: 0;
        $carbon_saved_1 = get_post_meta($post_id_1, '_carbon_footprint_saved', true) ?: 0;
        $carbon_saved_2 = get_post_meta($post_id_2, '_carbon_footprint_saved', true) ?: 0;
        
        $total_eco_impact = ($eco_points_1 + $eco_points_2 + $carbon_saved_1 + $carbon_saved_2);
        
        // Normalize to 0-1 range (assuming max combined impact of 100)
        return min(1.0, $total_eco_impact / 100);
    }
    
    /**
     * Calculate user compatibility score
     */
    private function calculate_user_compatibility_score($post_id_1, $post_id_2) {
        $user_1 = get_post_field('post_author', $post_id_1);
        $user_2 = get_post_field('post_author', $post_id_2);
        
        if ($user_1 == $user_2) {
            return 0.0; // Same user
        }
        
        // Check user ratings and trust scores
        $rating_1 = get_user_meta($user_1, '_exchange_rating', true) ?: 3.0;
        $rating_2 = get_user_meta($user_2, '_exchange_rating', true) ?: 3.0;
        $trust_1 = get_user_meta($user_1, '_trust_score', true) ?: 50;
        $trust_2 = get_user_meta($user_2, '_trust_score', true) ?: 50;
        
        // Higher compatibility for users with good ratings
        $avg_rating = ($rating_1 + $rating_2) / 2;
        $avg_trust = ($trust_1 + $trust_2) / 2;
        
        return ($avg_rating / 5.0) * 0.6 + ($avg_trust / 100.0) * 0.4;
    }
    
    /**
     * Calculate item condition compatibility score
     */
    private function calculate_condition_score($post_id_1, $post_id_2) {
        $condition_1 = get_post_meta($post_id_1, '_item_condition', true);
        $condition_2 = get_post_meta($post_id_2, '_item_condition', true);
        
        $condition_values = array(
            'new' => 5,
            'like_new' => 4,
            'good' => 3,
            'fair' => 2,
            'needs_repair' => 1
        );
        
        $value_1 = $condition_values[$condition_1] ?? 3;
        $value_2 = $condition_values[$condition_2] ?? 3;
        
        // Better score for similar conditions
        $difference = abs($value_1 - $value_2);
        return max(0.0, 1.0 - ($difference / 4));
    }
    
    /**
     * Calculate value compatibility score
     */
    private function calculate_value_score($post_id_1, $post_id_2) {
        $value_1 = floatval(get_post_meta($post_id_1, '_item_estimated_value', true) ?: 0);
        $value_2 = floatval(get_post_meta($post_id_2, '_item_estimated_value', true) ?: 0);
        
        if ($value_1 == 0 || $value_2 == 0) {
            return 0.7; // Neutral for free items
        }
        
        $ratio = min($value_1, $value_2) / max($value_1, $value_2);
        return $ratio;
    }
    
    /**
     * Calculate urgency score
     */
    private function calculate_urgency_score($post_id_1, $post_id_2) {
        $urgent_1 = get_post_meta($post_id_1, '_is_urgent', true);
        $urgent_2 = get_post_meta($post_id_2, '_is_urgent', true);
        
        if ($urgent_1 || $urgent_2) {
            return 1.0; // Boost urgent items
        }
        
        return 0.5; // Neutral
    }
    
    /**
     * Get human-readable match reasons
     */
    private function get_match_reasons($post_id_1, $post_id_2) {
        $reasons = array();
        
        // Category match
        $category_score = $this->calculate_category_score($post_id_1, $post_id_2);
        if ($category_score > 0.8) {
            $reasons[] = __('Perfect category match', 'environmental-item-exchange');
        } elseif ($category_score > 0.5) {
            $reasons[] = __('Similar categories', 'environmental-item-exchange');
        }
        
        // Location proximity
        $location_score = $this->calculate_location_score($post_id_1, $post_id_2);
        if ($location_score > 0.8) {
            $reasons[] = __('Very close location', 'environmental-item-exchange');
        } elseif ($location_score > 0.5) {
            $reasons[] = __('Nearby location', 'environmental-item-exchange');
        }
        
        // Environmental impact
        $eco_score = $this->calculate_environmental_score($post_id_1, $post_id_2);
        if ($eco_score > 0.7) {
            $reasons[] = __('High environmental impact', 'environmental-item-exchange');
        }
        
        // User compatibility
        $user_score = $this->calculate_user_compatibility_score($post_id_1, $post_id_2);
        if ($user_score > 0.8) {
            $reasons[] = __('Highly rated users', 'environmental-item-exchange');
        }
        
        // Urgency
        if (get_post_meta($post_id_1, '_is_urgent', true) || get_post_meta($post_id_2, '_is_urgent', true)) {
            $reasons[] = __('Urgent exchange', 'environmental-item-exchange');
        }
        
        return $reasons;
    }
    
    /**
     * Store match in database
     */
    public function store_match($post_id_1, $post_id_2, $score, $reasons = array()) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'exchange_matches';
        
        return $wpdb->insert(
            $table,
            array(
                'post_id_1' => $post_id_1,
                'post_id_2' => $post_id_2,
                'compatibility_score' => $score,
                'match_reasons' => json_encode($reasons),
                'match_status' => 'suggested',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%f', '%s', '%s', '%s')
        );
    }
    
    /**
     * Update all matches for active exchanges
     */
    public function update_all_matches() {
        $active_exchanges = get_posts(array(
            'post_type' => 'item_exchange',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_exchange_status',
                    'value' => 'active',
                    'compare' => '='
                )
            )
        ));
        
        foreach ($active_exchanges as $exchange) {
            $matches = $this->find_matches($exchange->ID, 5);
            
            foreach ($matches as $match) {
                $this->store_match(
                    $exchange->ID,
                    $match['post_id'],
                    $match['score'],
                    $match['reasons']
                );
            }
        }
    }
    
    /**
     * AJAX handler for getting matches
     */
    public function ajax_get_matches() {
        check_ajax_referer('ep_exchange_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$post_id) {
            wp_die('Invalid post ID');
        }
        
        $matches = $this->find_matches($post_id);
        
        wp_send_json_success(array(
            'matches' => $matches,
            'count' => count($matches)
        ));
    }
    
    /**
     * AJAX handler for updating match feedback
     */
    public function ajax_update_match_feedback() {
        check_ajax_referer('ep_exchange_nonce', 'nonce');
        
        $match_id = intval($_POST['match_id'] ?? 0);
        $feedback = sanitize_text_field($_POST['feedback'] ?? '');
        
        if (!$match_id || !in_array($feedback, array('interested', 'not_interested', 'contacted'))) {
            wp_die('Invalid parameters');
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'exchange_matches';
        
        $updated = $wpdb->update(
            $table,
            array(
                'match_status' => $feedback,
                'user_feedback_at' => current_time('mysql')
            ),
            array('match_id' => $match_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($updated) {
            wp_send_json_success(array('message' => __('Feedback updated', 'environmental-item-exchange')));
        } else {
            wp_send_json_error(array('message' => __('Failed to update feedback', 'environmental-item-exchange')));
        }
    }
    
    /**
     * Get match statistics
     */
    public function get_match_statistics() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'exchange_matches';
        
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_matches,
                AVG(compatibility_score) as avg_score,
                COUNT(CASE WHEN match_status = 'interested' THEN 1 END) as interested_matches,
                COUNT(CASE WHEN match_status = 'contacted' THEN 1 END) as contacted_matches
            FROM {$table}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        return $stats;
    }
      /**
     * Machine learning feedback integration
     */
    public function update_weights_from_feedback() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'exchange_matches';
        
        // Get successful matches (those that led to contact/exchange)
        $successful_matches = $wpdb->get_results("
            SELECT post_id_1, post_id_2, compatibility_score, match_reasons
            FROM {$table}
            WHERE match_status IN ('contacted', 'completed')
            AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
        ");
        
        // Analyze which factors contributed most to successful matches
        $factor_success_rates = array();
        $total_successful = count($successful_matches);
        
        foreach ($successful_matches as $match) {
            $reasons = json_decode($match->match_reasons, true);
            if (is_array($reasons)) {
                foreach ($reasons as $reason) {
                    if (!isset($factor_success_rates[$reason])) {
                        $factor_success_rates[$reason] = 0;
                    }
                    $factor_success_rates[$reason]++;
                }
            }
        }
        
        // Adjust weights based on success rates
        if ($total_successful > 10) { // Minimum threshold for reliable data
            $this->optimize_weights($factor_success_rates, $total_successful);
        }
    }
    
    /**
     * Optimize matching weights based on success data
     */
    private function optimize_weights($success_rates, $total_matches) {
        $factor_mappings = array(
            'Perfect category match' => 'category_match',
            'Similar categories' => 'category_match',
            'Very close location' => 'location_proximity',
            'Nearby location' => 'location_proximity',
            'High environmental impact' => 'environmental_impact',
            'Highly rated users' => 'user_compatibility',
            'Urgent exchange' => 'urgency'
        );
        
        $weight_adjustments = array();
        foreach ($success_rates as $reason => $count) {
            if (isset($factor_mappings[$reason])) {
                $factor = $factor_mappings[$reason];
                $success_rate = $count / $total_matches;
                
                if (!isset($weight_adjustments[$factor])) {
                    $weight_adjustments[$factor] = 0;
                }
                $weight_adjustments[$factor] += $success_rate;
            }
        }
        
        // Apply conservative weight adjustments
        foreach ($weight_adjustments as $factor => $adjustment) {
            if ($adjustment > 0.7) { // High success rate
                $this->weights[$factor] = min(0.4, $this->weights[$factor] * 1.1);
            } elseif ($adjustment < 0.3) { // Low success rate
                $this->weights[$factor] = max(0.05, $this->weights[$factor] * 0.9);
            }
        }
        
        // Normalize weights to sum to 1.0
        $total_weight = array_sum($this->weights);
        foreach ($this->weights as $factor => $weight) {
            $this->weights[$factor] = $weight / $total_weight;
        }
          // Save optimized weights
        update_option('ep_matching_weights', $this->weights);
        update_option('ep_last_weight_optimization', current_time('mysql'));
        
        error_log('Exchange Matching Weights Optimized: ' . print_r($this->weights, true));
    }
    
    /**
     * Advanced semantic matching using NLP techniques
     */
    public function semantic_text_match($text1, $text2) {
        // Simple semantic matching - in production, integrate with external NLP service
        $text1 = strtolower(trim($text1));
        $text2 = strtolower(trim($text2));
        
        // Extract keywords
        $keywords1 = $this->extract_keywords($text1);
        $keywords2 = $this->extract_keywords($text2);
        
        if (empty($keywords1) || empty($keywords2)) {
            return 0.0;
        }
        
        // Calculate Jaccard similarity
        $intersection = array_intersect($keywords1, $keywords2);
        $union = array_unique(array_merge($keywords1, $keywords2));
        
        return count($intersection) / count($union);
    }
    
    /**
     * Extract keywords from text
     */
    private function extract_keywords($text) {
        // Remove common stop words
        $stop_words = array('the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should');
        
        $words = preg_split('/\s+/', $text);
        $keywords = array();
        
        foreach ($words as $word) {
            $word = preg_replace('/[^a-zA-Z0-9]/', '', $word);
            if (strlen($word) > 2 && !in_array(strtolower($word), $stop_words)) {
                $keywords[] = strtolower($word);
            }
        }
        
        return array_unique($keywords);
    }
    
    /**
     * Batch matching for multiple items
     */
    public function batch_match($post_ids, $limit_per_item = 5) {
        $all_matches = array();
        
        foreach ($post_ids as $post_id) {
            $matches = $this->find_matches($post_id, $limit_per_item);
            $all_matches[$post_id] = $matches;
        }
        
        return $all_matches;
    }
    
    /**
     * Get trending matches based on recent activity
     */
    public function get_trending_matches($limit = 20) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'exchange_matches';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT m.*, p1.post_title as item1_title, p2.post_title as item2_title,
                   u1.display_name as user1_name, u2.display_name as user2_name
            FROM {$table} m
            JOIN {$wpdb->posts} p1 ON m.post_id_1 = p1.ID
            JOIN {$wpdb->posts} p2 ON m.post_id_2 = p2.ID
            JOIN {$wpdb->users} u1 ON p1.post_author = u1.ID
            JOIN {$wpdb->users} u2 ON p2.post_author = u2.ID
            WHERE m.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            AND m.compatibility_score > 0.7
            ORDER BY m.compatibility_score DESC, m.created_at DESC
            LIMIT %d
        ", $limit));
    }
    
    /**
     * Advanced filtering with multiple criteria
     */
    public function find_matches_with_filters($post_id, $filters = array(), $limit = 10) {
        $matches = $this->find_matches($post_id, $limit * 2); // Get more to filter
        
        if (empty($filters)) {
            return array_slice($matches, 0, $limit);
        }
        
        $filtered_matches = array();
        
        foreach ($matches as $match) {
            $include = true;
            
            // Distance filter
            if (isset($filters['max_distance'])) {
                $location_score = $this->calculate_location_score($post_id, $match['post_id']);
                $estimated_distance = (1.0 - $location_score) * 50; // Rough estimation
                if ($estimated_distance > $filters['max_distance']) {
                    $include = false;
                }
            }
            
            // Minimum score filter
            if (isset($filters['min_score']) && $match['score'] < $filters['min_score']) {
                $include = false;
            }
            
            // Category filter
            if (isset($filters['categories']) && !empty($filters['categories'])) {
                $match_categories = wp_get_post_terms($match['post_id'], 'exchange_type', array('fields' => 'slugs'));
                if (empty(array_intersect($filters['categories'], $match_categories))) {
                    $include = false;
                }
            }
            
            // User rating filter
            if (isset($filters['min_user_rating'])) {
                $match_author = get_post_field('post_author', $match['post_id']);
                $user_rating = get_user_meta($match_author, '_exchange_rating', true) ?: 3.0;
                if ($user_rating < $filters['min_user_rating']) {
                    $include = false;
                }
            }
            
            if ($include) {
                $filtered_matches[] = $match;
            }
            
            if (count($filtered_matches) >= $limit) {
                break;
            }
        }
        
        return $filtered_matches;
    }
    
    /**
     * Generate match explanation for users
     */
    public function get_match_explanation($post_id_1, $post_id_2) {
        $score = $this->calculate_match_score($post_id_1, $post_id_2);
        $reasons = $this->get_match_reasons($post_id_1, $post_id_2);
        
        $explanation = array(
            'overall_score' => $score,
            'score_percentage' => round($score * 100, 1),
            'compatibility_level' => $this->get_compatibility_level($score),
            'reasons' => $reasons,
            'detailed_scores' => array(
                'category_match' => $this->calculate_category_score($post_id_1, $post_id_2),
                'location_proximity' => $this->calculate_location_score($post_id_1, $post_id_2),
                'environmental_impact' => $this->calculate_environmental_score($post_id_1, $post_id_2),
                'user_compatibility' => $this->calculate_user_compatibility_score($post_id_1, $post_id_2),
                'item_condition' => $this->calculate_condition_score($post_id_1, $post_id_2),
                'value_range' => $this->calculate_value_score($post_id_1, $post_id_2),
                'urgency' => $this->calculate_urgency_score($post_id_1, $post_id_2)
            )
        );
        
        return $explanation;
    }
      /**
     * Get compatibility level description
     */
    private function get_compatibility_level($score) {
        if ($score >= 0.9) return __('Excellent Match', 'environmental-item-exchange');
        if ($score >= 0.8) return __('Very Good Match', 'environmental-item-exchange');
        if ($score >= 0.7) return __('Good Match', 'environmental-item-exchange');
        if ($score >= 0.6) return __('Fair Match', 'environmental-item-exchange');
        if ($score >= 0.5) return __('Possible Match', 'environmental-item-exchange');
        return __('Poor Match', 'environmental-item-exchange');
    }
    
    /**
     * AJAX handler for getting match explanation
     */
    public function ajax_get_match_explanation() {
        check_ajax_referer('ep_exchange_nonce', 'nonce');
        
        $post_id_1 = intval($_POST['post_id_1'] ?? 0);
        $post_id_2 = intval($_POST['post_id_2'] ?? 0);
        
        if (!$post_id_1 || !$post_id_2) {
            wp_die('Invalid post IDs');
        }
        
        $explanation = $this->get_match_explanation($post_id_1, $post_id_2);
        
        wp_send_json_success($explanation);
    }
    
    /**
     * AJAX handler for batch matching
     */
    public function ajax_batch_match() {
        check_ajax_referer('ep_exchange_nonce', 'nonce');
        
        $post_ids = array_map('intval', $_POST['post_ids'] ?? array());
        $limit = intval($_POST['limit'] ?? 5);
        
        if (empty($post_ids)) {
            wp_die('No post IDs provided');
        }
        
        $matches = $this->batch_match($post_ids, $limit);
        
        wp_send_json_success(array(
            'matches' => $matches,
            'total_items' => count($post_ids)
        ));
    }
    
    /**
     * Get comprehensive matching insights for admin
     */
    public function get_matching_insights() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'exchange_matches';
        
        // Get recent matching performance
        $performance = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_matches_generated,
                AVG(compatibility_score) as average_score,
                COUNT(CASE WHEN match_status = 'interested' THEN 1 END) as user_interest,
                COUNT(CASE WHEN match_status = 'contacted' THEN 1 END) as contacts_made,
                COUNT(CASE WHEN match_status = 'completed' THEN 1 END) as successful_exchanges
            FROM {$table}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        // Get top performing categories
        $top_categories = $wpdb->get_results("
            SELECT t.name as category_name, COUNT(*) as match_count,
                   AVG(m.compatibility_score) as avg_score
            FROM {$table} m
            JOIN {$wpdb->posts} p ON m.post_id_1 = p.ID
            JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
            WHERE tt.taxonomy = 'exchange_type'
            AND m.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY t.term_id
            ORDER BY match_count DESC
            LIMIT 10
        ");
        
        // Calculate success rates
        $success_rate = 0;
        if ($performance->total_matches_generated > 0) {
            $success_rate = ($performance->contacts_made / $performance->total_matches_generated) * 100;
        }
        
        return array(
            'performance' => $performance,
            'success_rate' => round($success_rate, 2),
            'top_categories' => $top_categories,
            'current_weights' => $this->weights,
            'last_optimization' => get_option('ep_last_weight_optimization', 'Never')
        );
    }
}
}

// Initialize the matching engine
Environmental_Item_Exchange_Matching_Engine::get_instance();
