<?php
/**
 * Environmental Recommendation Engine
 * 
 * Main recommendation engine that combines multiple algorithms to generate
 * personalized content and product recommendations
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Recommendation_Engine {
    
    private static $instance = null;
    private $behavior_tracker;
    private $content_analyzer;
    private $similarity_calculator;

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
        $this->behavior_tracker = Environmental_User_Behavior_Tracker::get_instance();
        $this->content_analyzer = Environmental_Content_Analyzer::get_instance();
        $this->similarity_calculator = Environmental_Similarity_Calculator::get_instance();
    }

    /**
     * Get recommendations for a user
     */
    public function get_user_recommendations($user_id, $type = 'personalized', $limit = 6) {
        $cache_key = "ecr_user_recommendations_{$user_id}_{$type}_{$limit}";
        $cached = wp_cache_get($cache_key, 'environmental_recommendations');
        
        if ($cached !== false) {
            return $cached;
        }

        $recommendations = array();

        switch ($type) {
            case 'personalized':
                $recommendations = $this->get_personalized_recommendations($user_id, $limit);
                break;
            case 'similar_content':
                $recommendations = $this->get_similar_content_recommendations($user_id, $limit);
                break;
            case 'trending':
                $recommendations = $this->get_trending_recommendations($user_id, $limit);
                break;
            case 'environmental':
                $recommendations = $this->get_environmental_recommendations($user_id, $limit);
                break;
            case 'collaborative':
                $recommendations = $this->get_collaborative_recommendations($user_id, $limit);
                break;
            default:
                $recommendations = $this->get_hybrid_recommendations($user_id, $limit);
        }

        // Apply diversity filter
        $recommendations = $this->apply_diversity_filter($recommendations);

        // Cache results
        wp_cache_set($cache_key, $recommendations, 'environmental_recommendations', get_option('ecr_cache_duration', 3600));

        return $recommendations;
    }

    /**
     * Get personalized recommendations based on user behavior
     */
    private function get_personalized_recommendations($user_id, $limit) {
        global $wpdb;

        // Get user preferences
        $user_preferences = $this->get_user_preferences($user_id);
        if (empty($user_preferences)) {
            return $this->get_trending_recommendations($user_id, $limit);
        }

        // Build recommendation query based on preferences
        $recommendations = array();
        $content_types = array('post', 'product', 'event', 'petition');

        foreach ($content_types as $content_type) {
            $type_recommendations = $this->get_content_by_preferences($user_preferences, $content_type, $limit);
            $recommendations = array_merge($recommendations, $type_recommendations);
        }

        // Score and sort recommendations
        $recommendations = $this->score_recommendations($recommendations, $user_preferences);
        
        return array_slice($recommendations, 0, $limit);
    }

    /**
     * Get similar content recommendations
     */
    private function get_similar_content_recommendations($user_id, $limit) {
        global $wpdb;

        // Get recently viewed content
        $recent_content = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT content_id, content_type 
            FROM {$wpdb->prefix}ecr_user_behavior 
            WHERE user_id = %d 
            AND action_type IN ('view', 'read', 'purchase') 
            AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY created_at DESC 
            LIMIT 10
        ", $user_id));

        $recommendations = array();

        foreach ($recent_content as $content) {
            $similar = $this->similarity_calculator->get_similar_content(
                $content->content_id, 
                $content->content_type, 
                ceil($limit / count($recent_content))
            );
            $recommendations = array_merge($recommendations, $similar);
        }

        // Remove duplicates and limit
        $recommendations = $this->remove_duplicates($recommendations);
        return array_slice($recommendations, 0, $limit);
    }

    /**
     * Get trending recommendations
     */
    private function get_trending_recommendations($user_id, $limit) {
        global $wpdb;

        $recommendations = $wpdb->get_results($wpdb->prepare("
            SELECT 
                cf.content_id,
                cf.content_type,
                cf.popularity_score,
                cf.engagement_score,
                (cf.popularity_score * 0.6 + cf.engagement_score * 0.4) as trend_score
            FROM {$wpdb->prefix}ecr_content_features cf
            WHERE cf.content_id NOT IN (
                SELECT DISTINCT content_id 
                FROM {$wpdb->prefix}ecr_user_behavior 
                WHERE user_id = %d 
                AND action_type IN ('view', 'read', 'purchase')
                AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
            )
            ORDER BY trend_score DESC
            LIMIT %d
        ", $user_id, $limit));

        return $this->format_recommendations($recommendations, 'trending');
    }

    /**
     * Get environmental recommendations
     */
    private function get_environmental_recommendations($user_id, $limit) {
        global $wpdb;

        $recommendations = $wpdb->get_results($wpdb->prepare("
            SELECT 
                cf.content_id,
                cf.content_type,
                cf.environmental_score,
                cf.sustainability_rating,
                (cf.environmental_score * 0.7 + cf.sustainability_rating * 0.3) as eco_score
            FROM {$wpdb->prefix}ecr_content_features cf
            WHERE cf.environmental_score > 0.5
            AND cf.content_id NOT IN (
                SELECT DISTINCT content_id 
                FROM {$wpdb->prefix}ecr_user_behavior 
                WHERE user_id = %d 
                AND action_type IN ('view', 'read', 'purchase')
                AND created_at > DATE_SUB(NOW(), INTERVAL 14 DAY)
            )
            ORDER BY eco_score DESC
            LIMIT %d
        ", $user_id, $limit));

        return $this->format_recommendations($recommendations, 'environmental');
    }

    /**
     * Get collaborative filtering recommendations
     */
    private function get_collaborative_recommendations($user_id, $limit) {
        global $wpdb;

        // Find similar users based on behavior
        $similar_users = $this->find_similar_users($user_id, 50);
        
        if (empty($similar_users)) {
            return $this->get_trending_recommendations($user_id, $limit);
        }

        $user_ids = implode(',', array_map('intval', array_keys($similar_users)));

        $recommendations = $wpdb->get_results($wpdb->prepare("
            SELECT 
                ub.content_id,
                ub.content_type,
                AVG(ub.interaction_score) as avg_score,
                COUNT(*) as interaction_count
            FROM {$wpdb->prefix}ecr_user_behavior ub
            WHERE ub.user_id IN ($user_ids)
            AND ub.content_id NOT IN (
                SELECT DISTINCT content_id 
                FROM {$wpdb->prefix}ecr_user_behavior 
                WHERE user_id = %d
            )
            AND ub.interaction_score > 0.5
            GROUP BY ub.content_id, ub.content_type
            HAVING interaction_count >= 2
            ORDER BY avg_score DESC, interaction_count DESC
            LIMIT %d
        ", $user_id, $limit));

        return $this->format_recommendations($recommendations, 'collaborative');
    }

    /**
     * Get hybrid recommendations combining multiple approaches
     */
    private function get_hybrid_recommendations($user_id, $limit) {
        $weights = array(
            'personalized' => get_option('ecr_personalization_weight', 0.3),
            'environmental' => get_option('ecr_environmental_weight', 0.3),
            'trending' => get_option('ecr_popularity_weight', 0.2),
            'collaborative' => get_option('ecr_similarity_weight', 0.2)
        );

        $all_recommendations = array();

        foreach ($weights as $type => $weight) {
            if ($weight > 0) {
                $type_limit = ceil($limit * $weight * 2); // Get more to mix
                $type_recommendations = $this->get_user_recommendations($user_id, $type, $type_limit);
                
                // Apply weight to scores
                foreach ($type_recommendations as &$rec) {
                    $rec['weighted_score'] = $rec['score'] * $weight;
                    $rec['source_type'] = $type;
                }
                
                $all_recommendations = array_merge($all_recommendations, $type_recommendations);
            }
        }

        // Sort by weighted score and remove duplicates
        usort($all_recommendations, function($a, $b) {
            return $b['weighted_score'] <=> $a['weighted_score'];
        });

        $unique_recommendations = $this->remove_duplicates($all_recommendations);
        return array_slice($unique_recommendations, 0, $limit);
    }

    /**
     * Get user preferences from behavior data
     */
    private function get_user_preferences($user_id) {
        global $wpdb;

        $preferences = wp_cache_get("user_preferences_$user_id", 'environmental_recommendations');
        
        if ($preferences === false) {
            $preferences = $wpdb->get_results($wpdb->prepare("
                SELECT preference_key, preference_value, weight, confidence_score
                FROM {$wpdb->prefix}ecr_user_preferences
                WHERE user_id = %d
                ORDER BY weight DESC, confidence_score DESC
            ", $user_id), ARRAY_A);

            wp_cache_set("user_preferences_$user_id", $preferences, 'environmental_recommendations', 1800);
        }

        return $preferences;
    }

    /**
     * Find users with similar behavior patterns
     */
    private function find_similar_users($user_id, $limit = 50) {
        global $wpdb;

        // Get user's content interactions
        $user_interactions = $wpdb->get_results($wpdb->prepare("
            SELECT content_id, content_type, AVG(interaction_score) as score
            FROM {$wpdb->prefix}ecr_user_behavior
            WHERE user_id = %d
            AND created_at > DATE_SUB(NOW(), INTERVAL 60 DAY)
            GROUP BY content_id, content_type
            HAVING score > 0.3
        ", $user_id), ARRAY_A);

        if (empty($user_interactions)) {
            return array();
        }

        $content_ids = array_column($user_interactions, 'content_id');
        $content_ids_str = implode(',', array_map('intval', $content_ids));

        // Find users who interacted with similar content
        $similar_users = $wpdb->get_results($wpdb->prepare("
            SELECT 
                user_id,
                COUNT(DISTINCT content_id) as common_items,
                AVG(interaction_score) as avg_score,
                (COUNT(DISTINCT content_id) * AVG(interaction_score)) as similarity_score
            FROM {$wpdb->prefix}ecr_user_behavior
            WHERE content_id IN ($content_ids_str)
            AND user_id != %d
            AND created_at > DATE_SUB(NOW(), INTERVAL 60 DAY)
            GROUP BY user_id
            HAVING common_items >= 3
            ORDER BY similarity_score DESC
            LIMIT %d
        ", $user_id, $limit), ARRAY_A);

        $result = array();
        foreach ($similar_users as $user) {
            $result[$user['user_id']] = $user['similarity_score'];
        }

        return $result;
    }

    /**
     * Score recommendations based on user preferences
     */
    private function score_recommendations($recommendations, $user_preferences) {
        $preference_weights = array();
        foreach ($user_preferences as $pref) {
            $preference_weights[$pref['preference_key']] = $pref['weight'] * $pref['confidence_score'];
        }

        foreach ($recommendations as &$rec) {
            $score = 0;
            $total_weight = 0;

            // Get content features
            $features = $this->content_analyzer->get_content_features($rec['content_id'], $rec['content_type']);

            foreach ($preference_weights as $key => $weight) {
                if (isset($features[$key])) {
                    $score += $features[$key] * $weight;
                    $total_weight += $weight;
                }
            }

            $rec['score'] = $total_weight > 0 ? $score / $total_weight : 0.5;
        }

        // Sort by score
        usort($recommendations, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $recommendations;
    }

    /**
     * Apply diversity filter to avoid too similar recommendations
     */
    private function apply_diversity_filter($recommendations) {
        $diversity_threshold = get_option('ecr_diversity_threshold', 0.7);
        $filtered = array();
        
        foreach ($recommendations as $rec) {
            $is_diverse = true;
            
            foreach ($filtered as $existing) {
                $similarity = $this->calculate_content_similarity($rec, $existing);
                if ($similarity > $diversity_threshold) {
                    $is_diverse = false;
                    break;
                }
            }
            
            if ($is_diverse) {
                $filtered[] = $rec;
            }
        }
        
        return $filtered;
    }

    /**
     * Calculate similarity between two content items
     */
    private function calculate_content_similarity($content1, $content2) {
        // Simple similarity based on content type and categories
        if ($content1['content_type'] !== $content2['content_type']) {
            return 0.0;
        }

        $features1 = $this->content_analyzer->get_content_features($content1['content_id'], $content1['content_type']);
        $features2 = $this->content_analyzer->get_content_features($content2['content_id'], $content2['content_type']);

        // Calculate cosine similarity if vectors exist
        if (isset($features1['similarity_vector']) && isset($features2['similarity_vector'])) {
            return $this->cosine_similarity($features1['similarity_vector'], $features2['similarity_vector']);
        }

        return 0.3; // Default moderate similarity
    }

    /**
     * Calculate cosine similarity between two vectors
     */
    private function cosine_similarity($vector1, $vector2) {
        $vector1 = json_decode($vector1, true);
        $vector2 = json_decode($vector2, true);
        
        if (!is_array($vector1) || !is_array($vector2) || count($vector1) !== count($vector2)) {
            return 0.0;
        }

        $dot_product = 0;
        $norm_a = 0;
        $norm_b = 0;

        for ($i = 0; $i < count($vector1); $i++) {
            $dot_product += $vector1[$i] * $vector2[$i];
            $norm_a += $vector1[$i] * $vector1[$i];
            $norm_b += $vector2[$i] * $vector2[$i];
        }

        $norm_a = sqrt($norm_a);
        $norm_b = sqrt($norm_b);

        if ($norm_a == 0 || $norm_b == 0) {
            return 0.0;
        }

        return $dot_product / ($norm_a * $norm_b);
    }

    /**
     * Remove duplicate recommendations
     */
    private function remove_duplicates($recommendations) {
        $seen = array();
        $unique = array();

        foreach ($recommendations as $rec) {
            $key = $rec['content_id'] . '_' . $rec['content_type'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $rec;
            }
        }

        return $unique;
    }

    /**
     * Format recommendations with consistent structure
     */
    private function format_recommendations($recommendations, $type) {
        $formatted = array();

        foreach ($recommendations as $rec) {
            $formatted[] = array(
                'content_id' => $rec->content_id,
                'content_type' => $rec->content_type,
                'score' => isset($rec->score) ? $rec->score : 0.5,
                'type' => $type,
                'reasoning' => $this->generate_reasoning($rec, $type)
            );
        }

        return $formatted;
    }

    /**
     * Generate reasoning for recommendation
     */
    private function generate_reasoning($rec, $type) {
        switch ($type) {
            case 'trending':
                return __('Popular right now', 'environmental-content-recommendation');
            case 'environmental':
                return __('High environmental impact', 'environmental-content-recommendation');
            case 'collaborative':
                return __('Users with similar interests liked this', 'environmental-content-recommendation');
            case 'personalized':
                return __('Based on your preferences', 'environmental-content-recommendation');
            default:
                return __('Recommended for you', 'environmental-content-recommendation');
        }
    }

    /**
     * Update user recommendations (scheduled task)
     */
    public function update_user_recommendations() {
        global $wpdb;

        // Get active users from last 30 days
        $active_users = $wpdb->get_col("
            SELECT DISTINCT user_id 
            FROM {$wpdb->prefix}ecr_user_behavior 
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");

        foreach ($active_users as $user_id) {
            $this->update_user_preferences($user_id);
            $this->generate_fresh_recommendations($user_id);
        }
    }

    /**
     * Update user preferences based on recent behavior
     */
    private function update_user_preferences($user_id) {
        $this->behavior_tracker->analyze_user_preferences($user_id);
    }

    /**
     * Generate fresh recommendations for user
     */
    private function generate_fresh_recommendations($user_id) {
        global $wpdb;

        // Clear old recommendations
        $wpdb->delete(
            $wpdb->prefix . 'ecr_user_recommendations',
            array('user_id' => $user_id),
            array('%d')
        );

        $types = get_option('ecr_recommendation_types', array('personalized', 'environmental', 'trending'));
        $max_per_type = ceil(get_option('ecr_max_recommendations', 6) / count($types));

        foreach ($types as $type) {
            $recommendations = $this->get_user_recommendations($user_id, $type, $max_per_type);
            
            foreach ($recommendations as $rec) {
                $wpdb->insert(
                    $wpdb->prefix . 'ecr_user_recommendations',
                    array(
                        'user_id' => $user_id,
                        'content_id' => $rec['content_id'],
                        'content_type' => $rec['content_type'],
                        'recommendation_type' => $type,
                        'score' => $rec['score'],
                        'reasoning' => $rec['reasoning'],
                        'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours'))
                    ),
                    array('%d', '%d', '%s', '%s', '%f', '%s', '%s')
                );
            }
        }

        // Clear cache
        wp_cache_delete("ecr_user_recommendations_{$user_id}_*", 'environmental_recommendations');
    }

    /**
     * Get content by preferences
     */
    private function get_content_by_preferences($preferences, $content_type, $limit) {
        global $wpdb;

        if (empty($preferences)) {
            return array();
        }

        // Build query based on preferences
        $where_conditions = array();
        $preference_scores = array();

        foreach ($preferences as $pref) {
            $key = $pref['preference_key'];
            $value = $pref['preference_value'];
            $weight = $pref['weight'];

            if (in_array($key, array('category', 'tag', 'sustainability_level'))) {
                $where_conditions[] = "JSON_CONTAINS(cf.{$key}s, '\"$value\"')";
                $preference_scores[] = "$weight";
            }
        }

        if (empty($where_conditions)) {
            return array();
        }

        $where_clause = implode(' OR ', $where_conditions);
        $score_clause = implode(' + ', $preference_scores);

        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                cf.content_id,
                cf.content_type,
                ($score_clause) as preference_score
            FROM {$wpdb->prefix}ecr_content_features cf
            WHERE cf.content_type = %s
            AND ($where_clause)
            ORDER BY preference_score DESC
            LIMIT %d
        ", $content_type, $limit));

        return $this->format_recommendations($results, 'preference_based');
    }
}
