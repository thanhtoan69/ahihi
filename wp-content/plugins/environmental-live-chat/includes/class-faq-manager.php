<?php
/**
 * FAQ Manager Class
 * 
 * Handles FAQ and knowledge base management with search functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_FAQ_Manager {
    
    private static $instance = null;
    private $table_faq;
    private $table_analytics;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->table_faq = $wpdb->prefix . 'elc_faq';
        $this->table_analytics = $wpdb->prefix . 'elc_analytics';
        
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('wp_ajax_elc_search_faq', array($this, 'search_faq'));
        add_action('wp_ajax_nopriv_elc_search_faq', array($this, 'search_faq'));
        
        add_action('wp_ajax_elc_get_faq_categories', array($this, 'get_faq_categories'));
        add_action('wp_ajax_nopriv_elc_get_faq_categories', array($this, 'get_faq_categories'));
        
        add_action('wp_ajax_elc_rate_faq', array($this, 'rate_faq'));
        add_action('wp_ajax_nopriv_elc_rate_faq', array($this, 'rate_faq'));
        
        add_action('wp_ajax_elc_add_faq', array($this, 'add_faq'));
        add_action('wp_ajax_elc_update_faq', array($this, 'update_faq'));
        add_action('wp_ajax_elc_delete_faq', array($this, 'delete_faq'));
        add_action('wp_ajax_elc_get_faq_list', array($this, 'get_faq_list'));
        
        add_action('wp_ajax_elc_import_faq', array($this, 'import_faq'));
        add_action('wp_ajax_elc_export_faq', array($this, 'export_faq'));
        
        // Register shortcodes
        add_shortcode('elc_faq_search', array($this, 'faq_search_shortcode'));
        add_shortcode('elc_faq_list', array($this, 'faq_list_shortcode'));
        add_shortcode('elc_knowledge_base', array($this, 'knowledge_base_shortcode'));
    }
    
    /**
     * Search FAQ entries
     */
    public function search_faq() {
        check_ajax_referer('elc_faq_nonce', 'nonce');
        
        global $wpdb;
        
        $query = sanitize_text_field($_POST['query'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? '');
        $limit = intval($_POST['limit'] ?? 10);
        
        if (empty($query)) {
            wp_send_json_error(array('message' => __('Search query is required.', 'environmental-live-chat')));
        }
        
        // Build search query
        $search_conditions = array();
        $search_params = array();
        
        // Text search in question and answer
        $search_conditions[] = "(question LIKE %s OR answer LIKE %s OR tags LIKE %s)";
        $search_term = '%' . $wpdb->esc_like($query) . '%';
        $search_params = array_merge($search_params, array($search_term, $search_term, $search_term));
        
        // Category filter
        if (!empty($category) && $category !== 'all') {
            $search_conditions[] = "category = %s";
            $search_params[] = $category;
        }
        
        // Only published FAQs
        $search_conditions[] = "status = 'published'";
        
        $where_clause = 'WHERE ' . implode(' AND ', $search_conditions);
        
        // Get FAQs with relevance scoring
        $faqs = $wpdb->get_results($wpdb->prepare(
            "SELECT *, 
                    (CASE 
                        WHEN question LIKE %s THEN 3
                        WHEN answer LIKE %s THEN 2
                        WHEN tags LIKE %s THEN 1
                        ELSE 0
                    END) as relevance_score,
                    COALESCE(helpful_votes, 0) as helpful_votes,
                    COALESCE(not_helpful_votes, 0) as not_helpful_votes
             FROM {$this->table_faq}
             {$where_clause}
             ORDER BY relevance_score DESC, helpful_votes DESC, view_count DESC
             LIMIT %d",
            array_merge(array($search_term, $search_term, $search_term), $search_params, array($limit))
        ));
        
        // Log search analytics
        $this->log_faq_metric('faq_search', array(
            'query' => $query,
            'category' => $category,
            'results_count' => count($faqs)
        ));
        
        // Update view counts
        foreach ($faqs as $faq) {
            $this->increment_view_count($faq->id);
        }
        
        wp_send_json_success(array(
            'faqs' => $faqs,
            'total_results' => count($faqs),
            'search_query' => $query
        ));
    }
    
    /**
     * Get FAQ categories
     */
    public function get_faq_categories() {
        global $wpdb;
        
        $categories = $wpdb->get_results(
            "SELECT category, COUNT(*) as count
             FROM {$this->table_faq}
             WHERE status = 'published'
             GROUP BY category
             ORDER BY count DESC"
        );
        
        wp_send_json_success(array('categories' => $categories));
    }
    
    /**
     * Rate FAQ entry
     */
    public function rate_faq() {
        check_ajax_referer('elc_faq_nonce', 'nonce');
        
        global $wpdb;
        
        $faq_id = intval($_POST['faq_id'] ?? 0);
        $rating = sanitize_text_field($_POST['rating'] ?? '');
        
        if (!$faq_id || !in_array($rating, array('helpful', 'not_helpful'))) {
            wp_send_json_error(array('message' => __('Invalid rating data.', 'environmental-live-chat')));
        }
        
        // Check if user already rated this FAQ (using IP tracking)
        $user_ip = $this->get_user_ip();
        $existing_rating = get_transient("elc_faq_rating_{$faq_id}_{$user_ip}");
        
        if ($existing_rating) {
            wp_send_json_error(array('message' => __('You have already rated this FAQ.', 'environmental-live-chat')));
        }
        
        // Update vote count
        $vote_column = $rating === 'helpful' ? 'helpful_votes' : 'not_helpful_votes';
        $updated = $wpdb->query($wpdb->prepare(
            "UPDATE {$this->table_faq} 
             SET {$vote_column} = COALESCE({$vote_column}, 0) + 1,
                 updated_at = %s
             WHERE id = %d",
            current_time('mysql'),
            $faq_id
        ));
        
        if ($updated) {
            // Set transient to prevent duplicate ratings (24 hour expiration)
            set_transient("elc_faq_rating_{$faq_id}_{$user_ip}", $rating, 24 * HOUR_IN_SECONDS);
            
            // Log analytics
            $this->log_faq_metric('faq_rated', array(
                'faq_id' => $faq_id,
                'rating' => $rating
            ));
            
            wp_send_json_success(array('message' => __('Thank you for your feedback!', 'environmental-live-chat')));
        } else {
            wp_send_json_error(array('message' => __('Failed to save rating.', 'environmental-live-chat')));
        }
    }
    
    /**
     * Add new FAQ entry
     */
    public function add_faq() {
        check_ajax_referer('elc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'environmental-live-chat')));
        }
        
        global $wpdb;
        
        $question = sanitize_textarea_field($_POST['question'] ?? '');
        $answer = wp_kses_post($_POST['answer'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? 'general');
        $tags = sanitize_text_field($_POST['tags'] ?? '');
        $status = sanitize_text_field($_POST['status'] ?? 'published');
        $priority = intval($_POST['priority'] ?? 0);
        
        if (empty($question) || empty($answer)) {
            wp_send_json_error(array('message' => __('Question and answer are required.', 'environmental-live-chat')));
        }
        
        $faq_data = array(
            'question' => $question,
            'answer' => $answer,
            'category' => $category,
            'tags' => $tags,
            'status' => $status,
            'priority' => $priority,
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $faq_id = $wpdb->insert($this->table_faq, $faq_data);
        
        if ($faq_id) {
            // Log analytics
            $this->log_faq_metric('faq_created', array(
                'faq_id' => $wpdb->insert_id,
                'category' => $category
            ));
            
            wp_send_json_success(array(
                'faq_id' => $wpdb->insert_id,
                'message' => __('FAQ added successfully.', 'environmental-live-chat')
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to add FAQ.', 'environmental-live-chat')));
        }
    }
    
    /**
     * Update FAQ entry
     */
    public function update_faq() {
        check_ajax_referer('elc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'environmental-live-chat')));
        }
        
        global $wpdb;
        
        $faq_id = intval($_POST['faq_id'] ?? 0);
        $question = sanitize_textarea_field($_POST['question'] ?? '');
        $answer = wp_kses_post($_POST['answer'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? 'general');
        $tags = sanitize_text_field($_POST['tags'] ?? '');
        $status = sanitize_text_field($_POST['status'] ?? 'published');
        $priority = intval($_POST['priority'] ?? 0);
        
        if (!$faq_id || empty($question) || empty($answer)) {
            wp_send_json_error(array('message' => __('FAQ ID, question and answer are required.', 'environmental-live-chat')));
        }
        
        $updated = $wpdb->update(
            $this->table_faq,
            array(
                'question' => $question,
                'answer' => $answer,
                'category' => $category,
                'tags' => $tags,
                'status' => $status,
                'priority' => $priority,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $faq_id)
        );
        
        if ($updated !== false) {
            wp_send_json_success(array('message' => __('FAQ updated successfully.', 'environmental-live-chat')));
        } else {
            wp_send_json_error(array('message' => __('Failed to update FAQ.', 'environmental-live-chat')));
        }
    }
    
    /**
     * Delete FAQ entry
     */
    public function delete_faq() {
        check_ajax_referer('elc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'environmental-live-chat')));
        }
        
        global $wpdb;
        
        $faq_id = intval($_POST['faq_id'] ?? 0);
        
        if (!$faq_id) {
            wp_send_json_error(array('message' => __('FAQ ID is required.', 'environmental-live-chat')));
        }
        
        $deleted = $wpdb->delete($this->table_faq, array('id' => $faq_id));
        
        if ($deleted) {
            wp_send_json_success(array('message' => __('FAQ deleted successfully.', 'environmental-live-chat')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete FAQ.', 'environmental-live-chat')));
        }
    }
    
    /**
     * Get FAQ list for admin
     */
    public function get_faq_list() {
        check_ajax_referer('elc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'environmental-live-chat')));
        }
        
        global $wpdb;
        
        $status = sanitize_text_field($_POST['status'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? '');
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 20);
        $search = sanitize_text_field($_POST['search'] ?? '');
        
        $offset = ($page - 1) * $per_page;
        
        // Build query
        $where_conditions = array();
        $params = array();
        
        if (!empty($status) && $status !== 'all') {
            $where_conditions[] = "status = %s";
            $params[] = $status;
        }
        
        if (!empty($category) && $category !== 'all') {
            $where_conditions[] = "category = %s";
            $params[] = $category;
        }
        
        if (!empty($search)) {
            $where_conditions[] = "(question LIKE %s OR answer LIKE %s OR tags LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $params = array_merge($params, array($search_term, $search_term, $search_term));
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Get FAQs
        $faqs = $wpdb->get_results($wpdb->prepare(
            "SELECT f.*, u.display_name as author_name
             FROM {$this->table_faq} f
             LEFT JOIN {$wpdb->users} u ON f.created_by = u.ID
             {$where_clause}
             ORDER BY f.priority DESC, f.created_at DESC
             LIMIT %d OFFSET %d",
            array_merge($params, array($per_page, $offset))
        ));
        
        // Get total count
        $total_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_faq} {$where_clause}",
            $params
        ));
        
        wp_send_json_success(array(
            'faqs' => $faqs,
            'total' => intval($total_count),
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total_count / $per_page)
        ));
    }
    
    /**
     * Import FAQ from CSV/JSON
     */
    public function import_faq() {
        check_ajax_referer('elc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'environmental-live-chat')));
        }
        
        if (!isset($_FILES['import_file'])) {
            wp_send_json_error(array('message' => __('No file uploaded.', 'environmental-live-chat')));
        }
        
        $file = $_FILES['import_file'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, array('csv', 'json'))) {
            wp_send_json_error(array('message' => __('Only CSV and JSON files are supported.', 'environmental-live-chat')));
        }
        
        $file_content = file_get_contents($file['tmp_name']);
        $imported_count = 0;
        
        if ($file_extension === 'json') {
            $data = json_decode($file_content, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                $imported_count = $this->import_faq_data($data);
            } else {
                wp_send_json_error(array('message' => __('Invalid JSON format.', 'environmental-live-chat')));
            }
        } else {
            // CSV import
            $lines = str_getcsv($file_content, "\n");
            $header = str_getcsv(array_shift($lines));
            $data = array();
            
            foreach ($lines as $line) {
                $row = str_getcsv($line);
                if (count($row) === count($header)) {
                    $data[] = array_combine($header, $row);
                }
            }
            
            $imported_count = $this->import_faq_data($data);
        }
        
        if ($imported_count > 0) {
            wp_send_json_success(array(
                'message' => sprintf(__('%d FAQ entries imported successfully.', 'environmental-live-chat'), $imported_count)
            ));
        } else {
            wp_send_json_error(array('message' => __('No valid data found to import.', 'environmental-live-chat')));
        }
    }
    
    /**
     * Export FAQ to JSON
     */
    public function export_faq() {
        check_ajax_referer('elc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'environmental-live-chat')));
        }
        
        global $wpdb;
        
        $faqs = $wpdb->get_results(
            "SELECT question, answer, category, tags, status, priority
             FROM {$this->table_faq}
             ORDER BY priority DESC, created_at DESC"
        );
        
        $json_data = json_encode($faqs, JSON_PRETTY_PRINT);
        
        wp_send_json_success(array(
            'data' => $json_data,
            'filename' => 'elc-faq-export-' . date('Y-m-d') . '.json'
        ));
    }
    
    // Shortcode Methods
    
    /**
     * FAQ search shortcode
     */
    public function faq_search_shortcode($atts) {
        $atts = shortcode_atts(array(
            'placeholder' => __('Search FAQ...', 'environmental-live-chat'),
            'show_categories' => 'true',
            'max_results' => '10'
        ), $atts);
        
        ob_start();
        ?>
        <div class="elc-faq-search-widget">
            <div class="elc-search-form">
                <input type="text" id="elc-faq-search-input" placeholder="<?php echo esc_attr($atts['placeholder']); ?>" />
                <?php if ($atts['show_categories'] === 'true'): ?>
                <select id="elc-faq-category-filter">
                    <option value="all"><?php _e('All Categories', 'environmental-live-chat'); ?></option>
                </select>
                <?php endif; ?>
                <button type="button" id="elc-faq-search-btn"><?php _e('Search', 'environmental-live-chat'); ?></button>
            </div>
            <div id="elc-faq-results" class="elc-faq-results"></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * FAQ list shortcode
     */
    public function faq_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'limit' => '10',
            'show_ratings' => 'true'
        ), $atts);
        
        global $wpdb;
        
        $where_conditions = array("status = 'published'");
        $params = array();
        
        if (!empty($atts['category'])) {
            $where_conditions[] = "category = %s";
            $params[] = $atts['category'];
        }
        
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        $limit = intval($atts['limit']);
        
        $faqs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_faq}
             {$where_clause}
             ORDER BY priority DESC, helpful_votes DESC
             LIMIT %d",
            array_merge($params, array($limit))
        ));
        
        ob_start();
        ?>
        <div class="elc-faq-list">
            <?php foreach ($faqs as $faq): ?>
            <div class="elc-faq-item" data-faq-id="<?php echo $faq->id; ?>">
                <h4 class="elc-faq-question"><?php echo esc_html($faq->question); ?></h4>
                <div class="elc-faq-answer"><?php echo wp_kses_post($faq->answer); ?></div>
                <?php if ($atts['show_ratings'] === 'true'): ?>
                <div class="elc-faq-rating">
                    <button class="elc-rate-helpful" data-faq-id="<?php echo $faq->id; ?>">
                        👍 <?php _e('Helpful', 'environmental-live-chat'); ?> (<?php echo intval($faq->helpful_votes); ?>)
                    </button>
                    <button class="elc-rate-not-helpful" data-faq-id="<?php echo $faq->id; ?>">
                        👎 <?php _e('Not Helpful', 'environmental-live-chat'); ?> (<?php echo intval($faq->not_helpful_votes); ?>)
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Knowledge base shortcode
     */
    public function knowledge_base_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_search' => 'true',
            'show_categories' => 'true',
            'items_per_page' => '10'
        ), $atts);
        
        ob_start();
        ?>
        <div class="elc-knowledge-base">
            <?php if ($atts['show_search'] === 'true'): ?>
            <div class="elc-kb-search">
                <?php echo $this->faq_search_shortcode(array('show_categories' => $atts['show_categories'])); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($atts['show_categories'] === 'true'): ?>
            <div class="elc-kb-categories">
                <h3><?php _e('Browse by Category', 'environmental-live-chat'); ?></h3>
                <div id="elc-kb-category-list"></div>
            </div>
            <?php endif; ?>
            
            <div class="elc-kb-popular">
                <h3><?php _e('Popular Questions', 'environmental-live-chat'); ?></h3>
                <?php echo $this->faq_list_shortcode(array('limit' => $atts['items_per_page'])); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    // Helper Methods
    
    private function increment_view_count($faq_id) {
        global $wpdb;
        
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->table_faq} 
             SET view_count = COALESCE(view_count, 0) + 1
             WHERE id = %d",
            $faq_id
        ));
    }
    
    private function log_faq_metric($metric_type, $metadata = array()) {
        global $wpdb;
        
        $wpdb->insert(
            $this->table_analytics,
            array(
                'metric_type' => $metric_type,
                'metric_value' => 1,
                'metadata' => json_encode($metadata),
                'recorded_at' => current_time('mysql')
            )
        );
    }
    
    private function get_user_ip() {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        return sanitize_text_field($ip);
    }
    
    private function import_faq_data($data) {
        global $wpdb;
        
        $imported_count = 0;
        $current_user_id = get_current_user_id();
        
        foreach ($data as $item) {
            if (empty($item['question']) || empty($item['answer'])) {
                continue;
            }
            
            $faq_data = array(
                'question' => sanitize_textarea_field($item['question']),
                'answer' => wp_kses_post($item['answer']),
                'category' => sanitize_text_field($item['category'] ?? 'general'),
                'tags' => sanitize_text_field($item['tags'] ?? ''),
                'status' => sanitize_text_field($item['status'] ?? 'published'),
                'priority' => intval($item['priority'] ?? 0),
                'created_by' => $current_user_id,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            );
            
            if ($wpdb->insert($this->table_faq, $faq_data)) {
                $imported_count++;
            }
        }
        
        return $imported_count;
    }
    
    /**
     * Get FAQ statistics
     */
    public function get_faq_statistics($date_from = null, $date_to = null) {
        global $wpdb;
        
        $date_from = $date_from ?: date('Y-m-d', strtotime('-30 days'));
        $date_to = $date_to ?: date('Y-m-d');
        
        $stats = array();
        
        // Total FAQs
        $stats['total_faqs'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_faq}");
        
        // FAQs by status
        $status_counts = $wpdb->get_results(
            "SELECT status, COUNT(*) as count
             FROM {$this->table_faq}
             GROUP BY status",
            OBJECT_K
        );
        $stats['by_status'] = $status_counts;
        
        // FAQs by category
        $category_counts = $wpdb->get_results(
            "SELECT category, COUNT(*) as count
             FROM {$this->table_faq}
             WHERE status = 'published'
             GROUP BY category
             ORDER BY count DESC"
        );
        $stats['by_category'] = $category_counts;
        
        // Search analytics
        $search_stats = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_analytics}
             WHERE metric_type = 'faq_search'
             AND DATE(recorded_at) BETWEEN %s AND %s",
            $date_from, $date_to
        ));
        $stats['total_searches'] = $search_stats;
        
        // Most viewed FAQs
        $popular_faqs = $wpdb->get_results(
            "SELECT question, view_count, helpful_votes, not_helpful_votes
             FROM {$this->table_faq}
             WHERE status = 'published'
             ORDER BY view_count DESC
             LIMIT 10"
        );
        $stats['popular_faqs'] = $popular_faqs;
        
        // Rating statistics
        $rating_stats = $wpdb->get_row(
            "SELECT 
                SUM(helpful_votes) as total_helpful,
                SUM(not_helpful_votes) as total_not_helpful,
                AVG(helpful_votes / GREATEST(helpful_votes + not_helpful_votes, 1)) * 100 as avg_helpfulness
             FROM {$this->table_faq}
             WHERE status = 'published'"
        );
        $stats['ratings'] = $rating_stats;
        
        return $stats;
    }
    
    /**
     * Get suggested FAQs based on keywords
     */
    public function get_suggested_faqs($keywords, $limit = 5) {
        global $wpdb;
        
        if (empty($keywords)) {
            return array();
        }
        
        $search_terms = explode(' ', strtolower($keywords));
        $search_conditions = array();
        $search_params = array();
        
        foreach ($search_terms as $term) {
            if (strlen($term) > 2) {
                $search_conditions[] = "(question LIKE %s OR answer LIKE %s OR tags LIKE %s)";
                $term_like = '%' . $wpdb->esc_like($term) . '%';
                $search_params = array_merge($search_params, array($term_like, $term_like, $term_like));
            }
        }
        
        if (empty($search_conditions)) {
            return array();
        }
        
        $where_clause = 'WHERE status = \'published\' AND (' . implode(' OR ', $search_conditions) . ')';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_faq}
             {$where_clause}
             ORDER BY helpful_votes DESC, view_count DESC
             LIMIT %d",
            array_merge($search_params, array($limit))
        ));
    }
}

// Initialize the FAQ manager
Environmental_FAQ_Manager::get_instance();
