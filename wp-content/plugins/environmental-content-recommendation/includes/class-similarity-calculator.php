<?php
/**
 * Similarity Calculator Class
 * 
 * Calculates content similarity using various algorithms including cosine similarity,
 * Jaccard similarity, and environmental similarity for content recommendations.
 * 
 * @package Environmental_Content_Recommendation
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECR_Similarity_Calculator {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Cache for similarity calculations
     */
    private $similarity_cache = array();
    
    /**
     * Content analyzer instance
     */
    private $content_analyzer;
    
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
        $this->content_analyzer = ECR_Content_Analyzer::get_instance();
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_ecr_calculate_similarity', array($this, 'ajax_calculate_similarity'));
        add_action('wp_ajax_ecr_update_similarity_matrix', array($this, 'ajax_update_similarity_matrix'));
        add_action('ECR_update_similarity_matrix', array($this, 'update_similarity_matrix_cron'));
        
        // Schedule similarity matrix updates
        if (!wp_next_scheduled('ECR_update_similarity_matrix')) {
            wp_schedule_event(time(), 'daily', 'ECR_update_similarity_matrix');
        }
    }
    
    /**
     * Calculate cosine similarity between two content vectors
     */
    public function calculate_cosine_similarity($vector1, $vector2) {
        if (empty($vector1) || empty($vector2)) {
            return 0;
        }
        
        // Convert to arrays if they're JSON strings
        if (is_string($vector1)) {
            $vector1 = json_decode($vector1, true);
        }
        if (is_string($vector2)) {
            $vector2 = json_decode($vector2, true);
        }
        
        // Get all unique keys
        $all_keys = array_unique(array_merge(array_keys($vector1), array_keys($vector2)));
        
        $dot_product = 0;
        $norm1 = 0;
        $norm2 = 0;
        
        foreach ($all_keys as $key) {
            $val1 = isset($vector1[$key]) ? floatval($vector1[$key]) : 0;
            $val2 = isset($vector2[$key]) ? floatval($vector2[$key]) : 0;
            
            $dot_product += $val1 * $val2;
            $norm1 += $val1 * $val1;
            $norm2 += $val2 * $val2;
        }
        
        if ($norm1 == 0 || $norm2 == 0) {
            return 0;
        }
        
        return $dot_product / (sqrt($norm1) * sqrt($norm2));
    }
    
    /**
     * Calculate Jaccard similarity between two sets
     */
    public function calculate_jaccard_similarity($set1, $set2) {
        if (empty($set1) || empty($set2)) {
            return 0;
        }
        
        // Convert to arrays if needed
        if (!is_array($set1)) {
            $set1 = explode(',', $set1);
        }
        if (!is_array($set2)) {
            $set2 = explode(',', $set2);
        }
        
        $set1 = array_map('trim', array_map('strtolower', $set1));
        $set2 = array_map('trim', array_map('strtolower', $set2));
        
        $intersection = array_intersect($set1, $set2);
        $union = array_unique(array_merge($set1, $set2));
        
        if (count($union) == 0) {
            return 0;
        }
        
        return count($intersection) / count($union);
    }
    
    /**
     * Calculate environmental similarity based on environmental scores and keywords
     */
    public function calculate_environmental_similarity($content1_id, $content2_id) {
        $analysis1 = $this->content_analyzer->get_content_analysis($content1_id);
        $analysis2 = $this->content_analyzer->get_content_analysis($content2_id);
        
        if (!$analysis1 || !$analysis2) {
            return 0;
        }
        
        $features1 = json_decode($analysis1->features, true);
        $features2 = json_decode($analysis2->features, true);
        
        $similarity = 0;
        $factors = 0;
        
        // Environmental score similarity
        $score_diff = abs($analysis1->environmental_score - $analysis2->environmental_score);
        $score_similarity = 1 - ($score_diff / 100);
        $similarity += $score_similarity * 0.4;
        $factors += 0.4;
        
        // Environmental keywords similarity
        if (isset($features1['environmental_keywords']) && isset($features2['environmental_keywords'])) {
            $env_keywords1 = array_keys($features1['environmental_keywords']);
            $env_keywords2 = array_keys($features2['environmental_keywords']);
            $keyword_similarity = $this->calculate_jaccard_similarity($env_keywords1, $env_keywords2);
            $similarity += $keyword_similarity * 0.3;
            $factors += 0.3;
        }
        
        // Category similarity (environmental focus)
        $categories1 = isset($features1['categories']) ? $features1['categories'] : array();
        $categories2 = isset($features2['categories']) ? $features2['categories'] : array();
        $category_similarity = $this->calculate_jaccard_similarity($categories1, $categories2);
        $similarity += $category_similarity * 0.2;
        $factors += 0.2;
        
        // Tag similarity (environmental focus)
        $tags1 = isset($features1['tags']) ? $features1['tags'] : array();
        $tags2 = isset($features2['tags']) ? $features2['tags'] : array();
        $tag_similarity = $this->calculate_jaccard_similarity($tags1, $tags2);
        $similarity += $tag_similarity * 0.1;
        $factors += 0.1;
        
        return $factors > 0 ? $similarity / $factors : 0;
    }
    
    /**
     * Calculate content similarity using multiple algorithms
     */
    public function calculate_content_similarity($content1_id, $content2_id, $algorithm = 'hybrid') {
        // Check cache first
        $cache_key = "similarity_{$content1_id}_{$content2_id}_{$algorithm}";
        if (isset($this->similarity_cache[$cache_key])) {
            return $this->similarity_cache[$cache_key];
        }
        
        $similarity = 0;
        
        switch ($algorithm) {
            case 'cosine':
                $similarity = $this->calculate_cosine_similarity_by_id($content1_id, $content2_id);
                break;
                
            case 'jaccard':
                $similarity = $this->calculate_jaccard_similarity_by_id($content1_id, $content2_id);
                break;
                
            case 'environmental':
                $similarity = $this->calculate_environmental_similarity($content1_id, $content2_id);
                break;
                
            case 'behavioral':
                $similarity = $this->calculate_behavioral_similarity($content1_id, $content2_id);
                break;
                
            case 'hybrid':
            default:
                $similarity = $this->calculate_hybrid_similarity($content1_id, $content2_id);
                break;
        }
        
        // Cache the result
        $this->similarity_cache[$cache_key] = $similarity;
        
        return $similarity;
    }
    
    /**
     * Calculate cosine similarity by content IDs
     */
    private function calculate_cosine_similarity_by_id($content1_id, $content2_id) {
        $analysis1 = $this->content_analyzer->get_content_analysis($content1_id);
        $analysis2 = $this->content_analyzer->get_content_analysis($content2_id);
        
        if (!$analysis1 || !$analysis2) {
            return 0;
        }
        
        $vector1 = json_decode($analysis1->content_vector, true);
        $vector2 = json_decode($analysis2->content_vector, true);
        
        return $this->calculate_cosine_similarity($vector1, $vector2);
    }
    
    /**
     * Calculate Jaccard similarity by content IDs
     */
    private function calculate_jaccard_similarity_by_id($content1_id, $content2_id) {
        $analysis1 = $this->content_analyzer->get_content_analysis($content1_id);
        $analysis2 = $this->content_analyzer->get_content_analysis($content2_id);
        
        if (!$analysis1 || !$analysis2) {
            return 0;
        }
        
        $features1 = json_decode($analysis1->features, true);
        $features2 = json_decode($analysis2->features, true);
        
        // Combine categories and tags for Jaccard similarity
        $set1 = array_merge(
            isset($features1['categories']) ? $features1['categories'] : array(),
            isset($features1['tags']) ? $features1['tags'] : array(),
            array_keys(isset($features1['keywords']) ? $features1['keywords'] : array())
        );
        
        $set2 = array_merge(
            isset($features2['categories']) ? $features2['categories'] : array(),
            isset($features2['tags']) ? $features2['tags'] : array(),
            array_keys(isset($features2['keywords']) ? $features2['keywords'] : array())
        );
        
        return $this->calculate_jaccard_similarity($set1, $set2);
    }
    
    /**
     * Calculate behavioral similarity based on user interactions
     */
    private function calculate_behavioral_similarity($content1_id, $content2_id) {
        global $wpdb;
        $behavior_table = $wpdb->prefix . 'ecr_user_behavior';
        
        // Get users who interacted with content1
        $users1 = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT user_id FROM {$behavior_table} 
             WHERE content_id = %d AND user_id > 0",
            $content1_id
        ));
        
        // Get users who interacted with content2
        $users2 = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT user_id FROM {$behavior_table} 
             WHERE content_id = %d AND user_id > 0",
            $content2_id
        ));
        
        if (empty($users1) || empty($users2)) {
            return 0;
        }
        
        // Calculate Jaccard similarity of user sets
        return $this->calculate_jaccard_similarity($users1, $users2);
    }
    
    /**
     * Calculate hybrid similarity combining multiple algorithms
     */
    private function calculate_hybrid_similarity($content1_id, $content2_id) {
        $weights = array(
            'cosine' => 0.4,
            'jaccard' => 0.2,
            'environmental' => 0.3,
            'behavioral' => 0.1
        );
        
        $similarities = array();
        $similarities['cosine'] = $this->calculate_cosine_similarity_by_id($content1_id, $content2_id);
        $similarities['jaccard'] = $this->calculate_jaccard_similarity_by_id($content1_id, $content2_id);
        $similarities['environmental'] = $this->calculate_environmental_similarity($content1_id, $content2_id);
        $similarities['behavioral'] = $this->calculate_behavioral_similarity($content1_id, $content2_id);
        
        $weighted_similarity = 0;
        $total_weight = 0;
        
        foreach ($similarities as $algorithm => $similarity) {
            if ($similarity > 0) {
                $weighted_similarity += $similarity * $weights[$algorithm];
                $total_weight += $weights[$algorithm];
            }
        }
        
        return $total_weight > 0 ? $weighted_similarity / $total_weight : 0;
    }
    
    /**
     * Find similar content using various algorithms
     */
    public function find_similar_content($content_id, $limit = 10, $algorithm = 'hybrid', $threshold = 0.1) {
        global $wpdb;
        $features_table = $wpdb->prefix . 'ecr_content_features';
        
        // Get all other content
        $other_content = $wpdb->get_results($wpdb->prepare(
            "SELECT content_id, content_type, environmental_score 
             FROM {$features_table} 
             WHERE content_id != %d",
            $content_id
        ));
        
        $similarities = array();
        
        foreach ($other_content as $content) {
            $similarity = $this->calculate_content_similarity($content_id, $content->content_id, $algorithm);
            
            if ($similarity >= $threshold) {
                $similarities[] = array(
                    'content_id' => $content->content_id,
                    'content_type' => $content->content_type,
                    'similarity' => $similarity,
                    'environmental_score' => $content->environmental_score,
                    'algorithm' => $algorithm
                );
            }
        }
        
        // Sort by similarity score
        usort($similarities, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });
        
        return array_slice($similarities, 0, $limit);
    }
    
    /**
     * Calculate user similarity based on preferences and behavior
     */
    public function calculate_user_similarity($user1_id, $user2_id) {
        global $wpdb;
        $preferences_table = $wpdb->prefix . 'ecr_user_preferences';
        $behavior_table = $wpdb->prefix . 'ecr_user_behavior';
        
        // Get user preferences
        $prefs1 = $wpdb->get_results($wpdb->prepare(
            "SELECT preference_type, preference_value, weight 
             FROM {$preferences_table} WHERE user_id = %d",
            $user1_id
        ));
        
        $prefs2 = $wpdb->get_results($wpdb->prepare(
            "SELECT preference_type, preference_value, weight 
             FROM {$preferences_table} WHERE user_id = %d",
            $user2_id
        ));
        
        if (empty($prefs1) || empty($prefs2)) {
            return 0;
        }
        
        // Convert to vectors
        $vector1 = array();
        $vector2 = array();
        
        foreach ($prefs1 as $pref) {
            $key = $pref->preference_type . '_' . $pref->preference_value;
            $vector1[$key] = $pref->weight;
        }
        
        foreach ($prefs2 as $pref) {
            $key = $pref->preference_type . '_' . $pref->preference_value;
            $vector2[$key] = $pref->weight;
        }
        
        return $this->calculate_cosine_similarity($vector1, $vector2);
    }
    
    /**
     * Find similar users for collaborative filtering
     */
    public function find_similar_users($user_id, $limit = 20, $threshold = 0.1) {
        global $wpdb;
        $preferences_table = $wpdb->prefix . 'ecr_user_preferences';
        
        // Get all other users with preferences
        $other_users = $wpdb->get_col(
            "SELECT DISTINCT user_id FROM {$preferences_table} WHERE user_id != {$user_id}"
        );
        
        $similarities = array();
        
        foreach ($other_users as $other_user_id) {
            $similarity = $this->calculate_user_similarity($user_id, $other_user_id);
            
            if ($similarity >= $threshold) {
                $similarities[] = array(
                    'user_id' => $other_user_id,
                    'similarity' => $similarity
                );
            }
        }
        
        // Sort by similarity
        usort($similarities, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });
        
        return array_slice($similarities, 0, $limit);
    }
    
    /**
     * Update similarity matrix for all content
     */
    public function update_similarity_matrix($batch_size = 100) {
        global $wpdb;
        $features_table = $wpdb->prefix . 'ecr_content_features';
        $similarity_table = $wpdb->prefix . 'ecr_content_similarity';
        
        // Create similarity table if it doesn't exist
        $this->create_similarity_table();
        
        // Get all content IDs
        $content_ids = $wpdb->get_col("SELECT content_id FROM {$features_table}");
        $total_combinations = count($content_ids) * (count($content_ids) - 1) / 2;
        
        $processed = 0;
        $batch_count = 0;
        
        for ($i = 0; $i < count($content_ids); $i++) {
            for ($j = $i + 1; $j < count($content_ids); $j++) {
                $content1_id = $content_ids[$i];
                $content2_id = $content_ids[$j];
                
                // Check if similarity already exists
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$similarity_table} 
                     WHERE (content1_id = %d AND content2_id = %d) 
                     OR (content1_id = %d AND content2_id = %d)",
                    $content1_id, $content2_id, $content2_id, $content1_id
                ));
                
                if (!$existing) {
                    $similarity = $this->calculate_content_similarity($content1_id, $content2_id, 'hybrid');
                    
                    if ($similarity > 0.05) { // Only store meaningful similarities
                        $wpdb->insert(
                            $similarity_table,
                            array(
                                'content1_id' => $content1_id,
                                'content2_id' => $content2_id,
                                'similarity_score' => $similarity,
                                'algorithm' => 'hybrid',
                                'calculated_at' => current_time('mysql')
                            ),
                            array('%d', '%d', '%f', '%s', '%s')
                        );
                    }
                }
                
                $processed++;
                
                if ($processed % $batch_size === 0) {
                    $batch_count++;
                    // Allow other processes to run
                    sleep(1);
                }
            }
        }
        
        return array(
            'processed' => $processed,
            'total' => $total_combinations,
            'batches' => $batch_count
        );
    }
    
    /**
     * Create similarity table
     */
    private function create_similarity_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ecr_content_similarity';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            content1_id bigint(20) unsigned NOT NULL,
            content2_id bigint(20) unsigned NOT NULL,
            similarity_score decimal(5,4) NOT NULL,
            algorithm varchar(50) NOT NULL DEFAULT 'hybrid',
            calculated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY content1_id (content1_id),
            KEY content2_id (content2_id),
            KEY similarity_score (similarity_score),
            UNIQUE KEY unique_pair (content1_id, content2_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Get cached similarity from database
     */
    public function get_cached_similarity($content1_id, $content2_id) {
        global $wpdb;
        $similarity_table = $wpdb->prefix . 'ecr_content_similarity';
        
        $similarity = $wpdb->get_var($wpdb->prepare(
            "SELECT similarity_score FROM {$similarity_table} 
             WHERE (content1_id = %d AND content2_id = %d) 
             OR (content1_id = %d AND content2_id = %d)",
            $content1_id, $content2_id, $content2_id, $content1_id
        ));
        
        return $similarity ? floatval($similarity) : null;
    }
    
    /**
     * AJAX handler for similarity calculation
     */
    public function ajax_calculate_similarity() {
        check_ajax_referer('ecr_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'environmental-content-recommendation'));
        }
        
        $content1_id = intval($_POST['content1_id']);
        $content2_id = intval($_POST['content2_id']);
        $algorithm = sanitize_text_field($_POST['algorithm']) ?: 'hybrid';
        
        $similarity = $this->calculate_content_similarity($content1_id, $content2_id, $algorithm);
        
        wp_send_json_success(array(
            'similarity' => $similarity,
            'algorithm' => $algorithm,
            'content1_id' => $content1_id,
            'content2_id' => $content2_id
        ));
    }
    
    /**
     * AJAX handler for similarity matrix update
     */
    public function ajax_update_similarity_matrix() {
        check_ajax_referer('ecr_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'environmental-content-recommendation'));
        }
        
        $batch_size = intval($_POST['batch_size']) ?: 100;
        $result = $this->update_similarity_matrix($batch_size);
        
        wp_send_json_success($result);
    }
    
    /**
     * Cron handler for similarity matrix update
     */
    public function update_similarity_matrix_cron() {
        $this->update_similarity_matrix(50); // Smaller batch size for cron
    }
    
    /**
     * Get similarity statistics
     */
    public function get_similarity_statistics() {
        global $wpdb;
        $similarity_table = $wpdb->prefix . 'ecr_content_similarity';
        
        $stats = array();
        
        // Total similarities calculated
        $stats['total_similarities'] = $wpdb->get_var("SELECT COUNT(*) FROM {$similarity_table}");
        
        // Average similarity score
        $stats['avg_similarity'] = $wpdb->get_var("SELECT AVG(similarity_score) FROM {$similarity_table}");
        
        // Distribution of similarity scores
        $stats['distribution'] = $wpdb->get_results(
            "SELECT 
                CASE 
                    WHEN similarity_score >= 0.8 THEN 'Very High (0.8+)'
                    WHEN similarity_score >= 0.6 THEN 'High (0.6-0.8)'
                    WHEN similarity_score >= 0.4 THEN 'Medium (0.4-0.6)'
                    WHEN similarity_score >= 0.2 THEN 'Low (0.2-0.4)'
                    ELSE 'Very Low (0-0.2)'
                END as similarity_range,
                COUNT(*) as count
             FROM {$similarity_table}
             GROUP BY similarity_range
             ORDER BY MIN(similarity_score) DESC"
        );
        
        // Most recent calculation
        $stats['last_update'] = $wpdb->get_var("SELECT MAX(calculated_at) FROM {$similarity_table}");
        
        return $stats;
    }
}
