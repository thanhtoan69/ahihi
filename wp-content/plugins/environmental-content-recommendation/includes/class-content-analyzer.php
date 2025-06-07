<?php
/**
 * Content Analyzer Class
 * 
 * Analyzes content features, extracts keywords, calculates environmental scores,
 * and generates content vectors for similarity calculations.
 * 
 * @package Environmental_Content_Recommendation
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECR_Content_Analyzer {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Stop words for keyword extraction
     */
    private $stop_words = array();
    
    /**
     * Environmental keywords and their weights
     */
    private $environmental_keywords = array();
    
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
        $this->init_stop_words();
        $this->init_environmental_keywords();
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('save_post', array($this, 'analyze_content_on_save'), 10, 2);
        add_action('wp_ajax_ecr_analyze_content', array($this, 'ajax_analyze_content'));
        add_action('wp_ajax_ecr_bulk_analyze', array($this, 'ajax_bulk_analyze_content'));
        add_action('ECR_analyze_content_batch', array($this, 'process_content_batch'));
        
        // Hook into post updates
        add_action('post_updated', array($this, 'handle_post_update'), 10, 3);
        add_action('wp_insert_post', array($this, 'handle_new_post'), 10, 2);
    }
    
    /**
     * Initialize stop words
     */
    private function init_stop_words() {
        $this->stop_words = array(
            'a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for', 'from',
            'has', 'he', 'in', 'is', 'it', 'its', 'of', 'on', 'that', 'the',
            'to', 'was', 'were', 'will', 'with', 'the', 'this', 'but', 'they',
            'have', 'had', 'what', 'said', 'each', 'which', 'their', 'time',
            'also', 'can', 'may', 'use', 'your', 'how', 'about', 'all', 'would',
            'there', 'them', 'been', 'than', 'way', 'could', 'many', 'then',
            'these', 'two', 'more', 'very', 'after', 'our', 'just', 'first'
        );
        
        // Allow filtering of stop words
        $this->stop_words = apply_filters('ecr_stop_words', $this->stop_words);
    }
    
    /**
     * Initialize environmental keywords with weights
     */
    private function init_environmental_keywords() {
        $this->environmental_keywords = array(
            // High impact keywords
            'sustainability' => 10,
            'renewable' => 9,
            'climate' => 9,
            'carbon' => 8,
            'emission' => 8,
            'green' => 7,
            'eco' => 7,
            'environmental' => 8,
            'clean' => 6,
            'energy' => 7,
            'solar' => 8,
            'wind' => 7,
            'recycling' => 7,
            'biodegradable' => 8,
            'organic' => 6,
            'conservation' => 8,
            'pollution' => 7,
            'waste' => 6,
            'ecosystem' => 8,
            'biodiversity' => 9,
            
            // Medium impact keywords
            'nature' => 5,
            'natural' => 5,
            'earth' => 5,
            'planet' => 6,
            'forest' => 6,
            'ocean' => 6,
            'water' => 5,
            'air' => 4,
            'soil' => 5,
            'wildlife' => 6,
            'habitat' => 6,
            'species' => 5,
            'resource' => 5,
            'efficiency' => 5,
            'sustainable' => 8,
            'responsible' => 5,
            'conscious' => 5,
            'awareness' => 4,
            'protection' => 6,
            'preservation' => 7,
            
            // Action-oriented keywords
            'reduce' => 6,
            'reuse' => 6,
            'recycle' => 7,
            'save' => 5,
            'protect' => 6,
            'conserve' => 7,
            'restore' => 6,
            'prevent' => 5,
            'minimize' => 6,
            'optimize' => 5,
            'improve' => 4,
            'change' => 4,
            'action' => 5,
            'solution' => 5,
            'initiative' => 5
        );
        
        // Allow filtering of environmental keywords
        $this->environmental_keywords = apply_filters('ecr_environmental_keywords', $this->environmental_keywords);
    }
    
    /**
     * Analyze content when post is saved
     */
    public function analyze_content_on_save($post_id, $post) {
        // Skip auto-saves and revisions
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }
        
        // Skip if post is not published
        if ($post->post_status !== 'publish') {
            return;
        }
        
        // Analyze content asynchronously
        wp_schedule_single_event(time() + 10, 'ECR_analyze_content_batch', array(array($post_id)));
    }
    
    /**
     * Handle new post creation
     */
    public function handle_new_post($post_id, $post) {
        if ($post->post_status === 'publish') {
            $this->analyze_content($post_id);
        }
    }
    
    /**
     * Handle post updates
     */
    public function handle_post_update($post_id, $post_after, $post_before) {
        // Check if content has changed
        if ($post_after->post_content !== $post_before->post_content || 
            $post_after->post_title !== $post_before->post_title) {
            $this->analyze_content($post_id);
        }
    }
    
    /**
     * Main content analysis function
     */
    public function analyze_content($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return false;
        }
        
        // Extract content features
        $features = $this->extract_content_features($post);
        
        // Calculate environmental score
        $environmental_score = $this->calculate_environmental_score($features);
        
        // Generate content vector
        $content_vector = $this->generate_content_vector($features);
        
        // Store analysis results
        $this->store_content_analysis($post_id, $features, $environmental_score, $content_vector);
        
        // Update post meta with environmental score
        update_post_meta($post_id, '_environmental_score', $environmental_score);
        update_post_meta($post_id, '_content_analysis_date', current_time('mysql'));
        
        return array(
            'features' => $features,
            'environmental_score' => $environmental_score,
            'content_vector' => $content_vector
        );
    }
    
    /**
     * Extract content features from post
     */
    private function extract_content_features($post) {
        $features = array();
        
        // Basic content features
        $features['word_count'] = str_word_count(strip_tags($post->post_content));
        $features['reading_time'] = $this->calculate_reading_time($post->post_content);
        $features['content_length'] = strlen($post->post_content);
        
        // Extract keywords from title and content
        $text = $post->post_title . ' ' . strip_tags($post->post_content);
        $features['keywords'] = $this->extract_keywords($text);
        $features['environmental_keywords'] = $this->extract_environmental_keywords($text);
        
        // Extract categories and tags
        $features['categories'] = wp_get_post_categories($post->ID, array('fields' => 'names'));
        $features['tags'] = wp_get_post_terms($post->ID, 'post_tag', array('fields' => 'names'));
        
        // Content type specific features
        if ($post->post_type === 'product') {
            $features = array_merge($features, $this->extract_product_features($post));
        }
        
        // Extract media features
        $features['media'] = $this->extract_media_features($post);
        
        // Extract readability metrics
        $features['readability'] = $this->calculate_readability_score($post->post_content);
        
        // Extract semantic features
        $features['semantic'] = $this->extract_semantic_features($text);
        
        // Extract structural features
        $features['structure'] = $this->analyze_content_structure($post->post_content);
        
        return $features;
    }
    
    /**
     * Extract keywords from text
     */
    private function extract_keywords($text, $max_keywords = 20) {
        // Clean and normalize text
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
        $words = explode(' ', $text);
        
        // Remove stop words and short words
        $words = array_filter($words, function($word) {
            return strlen($word) > 2 && !in_array($word, $this->stop_words);
        });
        
        // Count word frequency
        $word_count = array_count_values($words);
        
        // Sort by frequency
        arsort($word_count);
        
        // Return top keywords
        return array_slice($word_count, 0, $max_keywords, true);
    }
    
    /**
     * Extract environmental keywords from text
     */
    private function extract_environmental_keywords($text) {
        $text = strtolower($text);
        $found_keywords = array();
        
        foreach ($this->environmental_keywords as $keyword => $weight) {
            $count = substr_count($text, $keyword);
            if ($count > 0) {
                $found_keywords[$keyword] = array(
                    'count' => $count,
                    'weight' => $weight,
                    'score' => $count * $weight
                );
            }
        }
        
        return $found_keywords;
    }
    
    /**
     * Calculate environmental score based on content
     */
    private function calculate_environmental_score($features) {
        $score = 0;
        
        // Base score from environmental keywords
        if (!empty($features['environmental_keywords'])) {
            foreach ($features['environmental_keywords'] as $keyword => $data) {
                $score += $data['score'];
            }
        }
        
        // Bonus for environmental categories
        $environmental_categories = array('environment', 'sustainability', 'green', 'eco', 'climate');
        foreach ($features['categories'] as $category) {
            if (in_array(strtolower($category), $environmental_categories)) {
                $score += 20;
            }
        }
        
        // Bonus for environmental tags
        foreach ($features['tags'] as $tag) {
            if (array_key_exists(strtolower($tag), $this->environmental_keywords)) {
                $score += $this->environmental_keywords[strtolower($tag)];
            }
        }
        
        // Normalize score to 0-100 range
        $max_possible_score = 200; // Adjust based on your content
        $normalized_score = min(($score / $max_possible_score) * 100, 100);
        
        return round($normalized_score, 2);
    }
    
    /**
     * Generate content vector for similarity calculations
     */
    private function generate_content_vector($features) {
        $vector = array();
        
        // TF-IDF vectors for keywords
        $total_words = array_sum($features['keywords']);
        foreach ($features['keywords'] as $keyword => $count) {
            $tf = $count / $total_words;
            $idf = $this->calculate_idf($keyword);
            $vector['keyword_' . $keyword] = $tf * $idf;
        }
        
        // Category vectors
        foreach ($features['categories'] as $category) {
            $vector['category_' . sanitize_key($category)] = 1.0;
        }
        
        // Tag vectors
        foreach ($features['tags'] as $tag) {
            $vector['tag_' . sanitize_key($tag)] = 1.0;
        }
        
        // Environmental score vector
        $vector['environmental_score'] = $features['environmental_keywords'] ? 
            array_sum(array_column($features['environmental_keywords'], 'score')) / 100 : 0;
        
        // Content length vector (normalized)
        $vector['content_length'] = min($features['word_count'] / 1000, 1.0);
        
        // Readability vector
        $vector['readability'] = isset($features['readability']) ? $features['readability'] / 100 : 0.5;
        
        // Media presence vector
        $vector['has_images'] = !empty($features['media']['images']) ? 1.0 : 0.0;
        $vector['has_videos'] = !empty($features['media']['videos']) ? 1.0 : 0.0;
        
        return $vector;
    }
    
    /**
     * Calculate IDF (Inverse Document Frequency) for a keyword
     */
    private function calculate_idf($keyword) {
        global $wpdb;
        
        // Get total number of documents
        $total_docs = wp_count_posts('post')->publish + wp_count_posts('page')->publish;
        
        // Get number of documents containing the keyword
        $docs_with_keyword = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
             WHERE post_status = 'publish' 
             AND (post_title LIKE %s OR post_content LIKE %s)",
            '%' . $wpdb->esc_like($keyword) . '%',
            '%' . $wpdb->esc_like($keyword) . '%'
        ));
        
        if ($docs_with_keyword == 0) {
            return 0;
        }
        
        return log($total_docs / $docs_with_keyword);
    }
    
    /**
     * Extract product-specific features
     */
    private function extract_product_features($post) {
        $features = array();
        
        if (function_exists('wc_get_product')) {
            $product = wc_get_product($post->ID);
            if ($product) {
                $features['price'] = $product->get_price();
                $features['sale_price'] = $product->get_sale_price();
                $features['rating'] = $product->get_average_rating();
                $features['review_count'] = $product->get_review_count();
                $features['stock_status'] = $product->get_stock_status();
                $features['product_type'] = $product->get_type();
                
                // Get product categories
                $product_cats = wp_get_post_terms($post->ID, 'product_cat', array('fields' => 'names'));
                $features['product_categories'] = $product_cats;
                
                // Get product attributes
                $attributes = $product->get_attributes();
                $features['attributes'] = array();
                foreach ($attributes as $attribute) {
                    if ($attribute->is_taxonomy()) {
                        $terms = wp_get_post_terms($post->ID, $attribute->get_name(), array('fields' => 'names'));
                        $features['attributes'][$attribute->get_name()] = $terms;
                    }
                }
            }
        }
        
        return $features;
    }
    
    /**
     * Extract media features from post
     */
    private function extract_media_features($post) {
        $features = array(
            'images' => array(),
            'videos' => array(),
            'attachments' => array()
        );
        
        // Get attached media
        $attachments = get_attached_media('', $post->ID);
        foreach ($attachments as $attachment) {
            $mime_type = get_post_mime_type($attachment->ID);
            if (strpos($mime_type, 'image/') === 0) {
                $features['images'][] = array(
                    'id' => $attachment->ID,
                    'url' => wp_get_attachment_url($attachment->ID),
                    'alt' => get_post_meta($attachment->ID, '_wp_attachment_image_alt', true)
                );
            } elseif (strpos($mime_type, 'video/') === 0) {
                $features['videos'][] = array(
                    'id' => $attachment->ID,
                    'url' => wp_get_attachment_url($attachment->ID)
                );
            }
            $features['attachments'][] = $attachment->ID;
        }
        
        // Count images and videos in content
        preg_match_all('/<img[^>]+>/i', $post->post_content, $content_images);
        preg_match_all('/<video[^>]+>/i', $post->post_content, $content_videos);
        
        $features['content_images_count'] = count($content_images[0]);
        $features['content_videos_count'] = count($content_videos[0]);
        
        return $features;
    }
    
    /**
     * Calculate reading time in minutes
     */
    private function calculate_reading_time($content) {
        $word_count = str_word_count(strip_tags($content));
        $reading_speed = 200; // words per minute
        return ceil($word_count / $reading_speed);
    }
    
    /**
     * Calculate readability score (simplified Flesch Reading Ease)
     */
    private function calculate_readability_score($content) {
        $text = strip_tags($content);
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $words = str_word_count($text);
        $syllables = $this->count_syllables($text);
        
        if (count($sentences) == 0 || $words == 0) {
            return 50; // Default score
        }
        
        $avg_sentence_length = $words / count($sentences);
        $avg_syllables_per_word = $syllables / $words;
        
        // Simplified Flesch Reading Ease formula
        $score = 206.835 - (1.015 * $avg_sentence_length) - (84.6 * $avg_syllables_per_word);
        
        return max(0, min(100, round($score)));
    }
    
    /**
     * Count syllables in text (simplified)
     */
    private function count_syllables($text) {
        $text = strtolower($text);
        $syllables = 0;
        $words = str_word_count($text, 1);
        
        foreach ($words as $word) {
            $syllables += max(1, preg_match_all('/[aeiouy]+/', $word));
        }
        
        return $syllables;
    }
    
    /**
     * Extract semantic features
     */
    private function extract_semantic_features($text) {
        $features = array();
        
        // Sentiment analysis (basic)
        $positive_words = array('good', 'great', 'excellent', 'amazing', 'wonderful', 'fantastic', 'love', 'best', 'perfect', 'awesome');
        $negative_words = array('bad', 'terrible', 'awful', 'horrible', 'hate', 'worst', 'disappointing', 'poor', 'useless');
        
        $text_lower = strtolower($text);
        $positive_count = 0;
        $negative_count = 0;
        
        foreach ($positive_words as $word) {
            $positive_count += substr_count($text_lower, $word);
        }
        
        foreach ($negative_words as $word) {
            $negative_count += substr_count($text_lower, $word);
        }
        
        $total_sentiment_words = $positive_count + $negative_count;
        if ($total_sentiment_words > 0) {
            $features['sentiment_score'] = ($positive_count - $negative_count) / $total_sentiment_words;
        } else {
            $features['sentiment_score'] = 0;
        }
        
        // Topic detection (basic)
        $topics = array(
            'technology' => array('tech', 'digital', 'computer', 'software', 'app', 'internet', 'online'),
            'health' => array('health', 'medical', 'doctor', 'medicine', 'wellness', 'fitness', 'nutrition'),
            'business' => array('business', 'company', 'market', 'sales', 'revenue', 'profit', 'customer'),
            'education' => array('education', 'school', 'student', 'teacher', 'learn', 'study', 'course'),
            'environment' => array_keys($this->environmental_keywords)
        );
        
        $features['topics'] = array();
        foreach ($topics as $topic => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                $score += substr_count($text_lower, $keyword);
            }
            if ($score > 0) {
                $features['topics'][$topic] = $score;
            }
        }
        
        return $features;
    }
    
    /**
     * Analyze content structure
     */
    private function analyze_content_structure($content) {
        $structure = array();
        
        // Count headings
        preg_match_all('/<h([1-6])[^>]*>/i', $content, $headings);
        $structure['headings'] = array();
        
        for ($i = 1; $i <= 6; $i++) {
            $count = substr_count(implode('', $headings[1]), (string) $i);
            if ($count > 0) {
                $structure['headings']['h' . $i] = $count;
            }
        }
        
        // Count lists
        $structure['lists'] = array(
            'ul' => substr_count($content, '<ul'),
            'ol' => substr_count($content, '<ol')
        );
        
        // Count paragraphs
        $structure['paragraphs'] = substr_count($content, '<p');
        
        // Count links
        $structure['links'] = substr_count($content, '<a ');
        
        // Calculate structure score
        $structure_score = 0;
        if (!empty($structure['headings'])) {
            $structure_score += 20;
        }
        if ($structure['lists']['ul'] > 0 || $structure['lists']['ol'] > 0) {
            $structure_score += 15;
        }
        if ($structure['links'] > 0) {
            $structure_score += 10;
        }
        
        $structure['score'] = min($structure_score, 100);
        
        return $structure;
    }
    
    /**
     * Store content analysis in database
     */
    private function store_content_analysis($post_id, $features, $environmental_score, $content_vector) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ecr_content_features';
        
        // Check if analysis exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE content_id = %d AND content_type = %s",
            $post_id, get_post_type($post_id)
        ));
        
        $data = array(
            'content_id' => $post_id,
            'content_type' => get_post_type($post_id),
            'features' => json_encode($features),
            'environmental_score' => $environmental_score,
            'content_vector' => json_encode($content_vector),
            'word_count' => $features['word_count'],
            'reading_time' => $features['reading_time'],
            'readability_score' => isset($features['readability']) ? $features['readability'] : 50,
            'updated_at' => current_time('mysql')
        );
        
        if ($existing) {
            $wpdb->update(
                $table_name,
                $data,
                array('id' => $existing->id),
                array('%d', '%s', '%s', '%f', '%s', '%d', '%d', '%d', '%s'),
                array('%d')
            );
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert(
                $table_name,
                $data,
                array('%d', '%s', '%s', '%f', '%s', '%d', '%d', '%d', '%s', '%s')
            );
        }
    }
    
    /**
     * AJAX handler for content analysis
     */
    public function ajax_analyze_content() {
        check_ajax_referer('ecr_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'environmental-content-recommendation'));
        }
        
        $post_id = intval($_POST['post_id']);
        $result = $this->analyze_content($post_id);
        
        if ($result) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error(__('Failed to analyze content', 'environmental-content-recommendation'));
        }
    }
    
    /**
     * AJAX handler for bulk content analysis
     */
    public function ajax_bulk_analyze_content() {
        check_ajax_referer('ecr_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'environmental-content-recommendation'));
        }
        
        $batch_size = intval($_POST['batch_size']) ?: 10;
        $offset = intval($_POST['offset']) ?: 0;
        
        $posts = get_posts(array(
            'post_type' => array('post', 'page', 'product'),
            'post_status' => 'publish',
            'numberposts' => $batch_size,
            'offset' => $offset,
            'fields' => 'ids'
        ));
        
        $results = array();
        foreach ($posts as $post_id) {
            $results[$post_id] = $this->analyze_content($post_id);
        }
        
        wp_send_json_success(array(
            'processed' => count($posts),
            'results' => $results,
            'has_more' => count($posts) === $batch_size
        ));
    }
    
    /**
     * Process content batch via cron
     */
    public function process_content_batch($post_ids) {
        foreach ($post_ids as $post_id) {
            $this->analyze_content($post_id);
        }
    }
    
    /**
     * Get content analysis
     */
    public function get_content_analysis($post_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ecr_content_features';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE content_id = %d",
            $post_id
        ));
    }
    
    /**
     * Get similar content based on analysis
     */
    public function get_similar_content($post_id, $limit = 5) {
        $current_analysis = $this->get_content_analysis($post_id);
        if (!$current_analysis) {
            return array();
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'ecr_content_features';
        
        // Get all content with analysis
        $all_content = $wpdb->get_results(
            "SELECT * FROM {$table_name} WHERE content_id != {$post_id}"
        );
        
        $similarities = array();
        $current_vector = json_decode($current_analysis->content_vector, true);
        
        foreach ($all_content as $content) {
            $content_vector = json_decode($content->content_vector, true);
            $similarity = $this->calculate_cosine_similarity($current_vector, $content_vector);
            
            if ($similarity > 0.1) { // Minimum similarity threshold
                $similarities[] = array(
                    'content_id' => $content->content_id,
                    'content_type' => $content->content_type,
                    'similarity' => $similarity,
                    'environmental_score' => $content->environmental_score
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
     * Calculate cosine similarity between two vectors
     */
    private function calculate_cosine_similarity($vector1, $vector2) {
        // Get all unique keys
        $all_keys = array_unique(array_merge(array_keys($vector1), array_keys($vector2)));
        
        $dot_product = 0;
        $norm1 = 0;
        $norm2 = 0;
        
        foreach ($all_keys as $key) {
            $val1 = isset($vector1[$key]) ? $vector1[$key] : 0;
            $val2 = isset($vector2[$key]) ? $vector2[$key] : 0;
            
            $dot_product += $val1 * $val2;
            $norm1 += $val1 * $val1;
            $norm2 += $val2 * $val2;
        }
        
        if ($norm1 == 0 || $norm2 == 0) {
            return 0;
        }
        
        return $dot_product / (sqrt($norm1) * sqrt($norm2));
    }
}
