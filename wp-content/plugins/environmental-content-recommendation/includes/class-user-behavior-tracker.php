<?php
/**
 * User Behavior Tracker Class
 * 
 * Handles user interaction tracking, behavior analysis, and preference learning.
 * 
 * @package Environmental_Content_Recommendation
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECR_User_Behavior_Tracker {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Current user ID
     */
    private $user_id;
    
    /**
     * Session start time
     */
    private $session_start;
    
    /**
     * Tracked events for current session
     */
    private $session_events = array();
    
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
        $this->init_hooks();
        $this->user_id = get_current_user_id();
        $this->session_start = time();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_tracking_scripts'));
        add_action('wp_ajax_ecr_track_behavior', array($this, 'ajax_track_behavior'));
        add_action('wp_ajax_nopriv_ecr_track_behavior', array($this, 'ajax_track_behavior'));
        add_action('wp_footer', array($this, 'output_tracking_data'));
        add_action('wp_login', array($this, 'handle_user_login'), 10, 2);
        add_action('wp_logout', array($this, 'handle_user_logout'));
        add_action('shutdown', array($this, 'save_session_data'));
    }
    
    /**
     * Enqueue tracking scripts
     */
    public function enqueue_tracking_scripts() {
        wp_enqueue_script(
            'ecr-behavior-tracker',
            ECR_PLUGIN_URL . 'assets/js/behavior-tracker.js',
            array('jquery'),
            ECR_VERSION,
            true
        );
        
        wp_localize_script('ecr-behavior-tracker', 'ecr_tracker', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ecr_tracker_nonce'),
            'user_id' => $this->user_id,
            'session_id' => $this->generate_session_id(),
            'track_scroll' => get_option('ecr_track_scroll_depth', true),
            'track_time' => get_option('ecr_track_time_on_page', true),
            'track_clicks' => get_option('ecr_track_click_events', true)
        ));
    }
    
    /**
     * Generate unique session ID
     */
    private function generate_session_id() {
        $session_key = 'ecr_session_' . $this->user_id . '_' . $this->session_start;
        return md5($session_key);
    }
    
    /**
     * Track user behavior via AJAX
     */
    public function ajax_track_behavior() {
        check_ajax_referer('ecr_tracker_nonce', 'nonce');
        
        $behavior_data = array(
            'user_id' => intval($_POST['user_id']),
            'session_id' => sanitize_text_field($_POST['session_id']),
            'event_type' => sanitize_text_field($_POST['event_type']),
            'content_id' => intval($_POST['content_id']),
            'content_type' => sanitize_text_field($_POST['content_type']),
            'event_data' => json_decode(stripslashes($_POST['event_data']), true),
            'timestamp' => current_time('mysql'),
            'page_url' => esc_url_raw($_POST['page_url']),
            'referrer' => esc_url_raw($_POST['referrer']),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'])
        );
        
        $this->record_behavior($behavior_data);
        
        wp_die();
    }
    
    /**
     * Record user behavior in database
     */
    public function record_behavior($behavior_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ecr_user_behavior';
        
        // Validate required fields
        if (empty($behavior_data['event_type']) || empty($behavior_data['user_id'])) {
            return false;
        }
        
        // Process event data based on type
        $processed_data = $this->process_event_data($behavior_data);
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $processed_data['user_id'],
                'session_id' => $processed_data['session_id'],
                'content_id' => $processed_data['content_id'],
                'content_type' => $processed_data['content_type'],
                'event_type' => $processed_data['event_type'],
                'event_data' => json_encode($processed_data['event_data']),
                'page_url' => $processed_data['page_url'],
                'referrer' => $processed_data['referrer'],
                'user_agent' => $processed_data['user_agent'],
                'timestamp' => $processed_data['timestamp'],
                'session_duration' => $this->calculate_session_duration(),
                'page_depth' => $this->calculate_page_depth($processed_data['page_url'])
            ),
            array('%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d')
        );
        
        if ($result) {
            // Update user preferences based on behavior
            $this->update_user_preferences($behavior_data);
            
            // Cache invalidation for related recommendations
            $this->invalidate_recommendation_cache($behavior_data['user_id']);
        }
        
        return $result;
    }
    
    /**
     * Process event data based on event type
     */
    private function process_event_data($behavior_data) {
        $event_data = $behavior_data['event_data'];
        
        switch ($behavior_data['event_type']) {
            case 'page_view':
                $event_data['view_duration'] = isset($event_data['duration']) ? intval($event_data['duration']) : 0;
                $event_data['scroll_depth'] = isset($event_data['scroll_depth']) ? floatval($event_data['scroll_depth']) : 0;
                break;
                
            case 'click':
                $event_data['element_type'] = sanitize_text_field($event_data['element_type']);
                $event_data['element_text'] = sanitize_text_field($event_data['element_text']);
                $event_data['position'] = array(
                    'x' => intval($event_data['position']['x']),
                    'y' => intval($event_data['position']['y'])
                );
                break;
                
            case 'scroll':
                $event_data['max_scroll'] = floatval($event_data['max_scroll']);
                $event_data['time_to_scroll'] = intval($event_data['time_to_scroll']);
                break;
                
            case 'engagement':
                $event_data['engagement_score'] = floatval($event_data['engagement_score']);
                $event_data['interaction_count'] = intval($event_data['interaction_count']);
                break;
                
            case 'content_interaction':
                $event_data['interaction_type'] = sanitize_text_field($event_data['interaction_type']);
                $event_data['content_section'] = sanitize_text_field($event_data['content_section']);
                break;
        }
        
        $behavior_data['event_data'] = $event_data;
        return $behavior_data;
    }
    
    /**
     * Update user preferences based on behavior
     */
    private function update_user_preferences($behavior_data) {
        if ($behavior_data['user_id'] <= 0) {
            return;
        }
        
        global $wpdb;
        $preferences_table = $wpdb->prefix . 'ecr_user_preferences';
        
        // Get content categories and tags
        $content_categories = $this->get_content_categories($behavior_data['content_id'], $behavior_data['content_type']);
        $content_tags = $this->get_content_tags($behavior_data['content_id'], $behavior_data['content_type']);
        
        // Calculate preference weight based on event type
        $weight = $this->calculate_preference_weight($behavior_data['event_type'], $behavior_data['event_data']);
        
        // Update category preferences
        foreach ($content_categories as $category) {
            $this->update_preference_weight($behavior_data['user_id'], 'category', $category, $weight);
        }
        
        // Update tag preferences
        foreach ($content_tags as $tag) {
            $this->update_preference_weight($behavior_data['user_id'], 'tag', $tag, $weight);
        }
        
        // Update content type preferences
        $this->update_preference_weight($behavior_data['user_id'], 'content_type', $behavior_data['content_type'], $weight);
        
        // Update environmental preferences if applicable
        $environmental_score = $this->get_content_environmental_score($behavior_data['content_id'], $behavior_data['content_type']);
        if ($environmental_score > 0) {
            $this->update_preference_weight($behavior_data['user_id'], 'environmental', 'high_impact', $weight * ($environmental_score / 100));
        }
    }
    
    /**
     * Calculate preference weight based on event type
     */
    private function calculate_preference_weight($event_type, $event_data) {
        $weights = array(
            'page_view' => 1.0,
            'click' => 2.0,
            'scroll' => 1.5,
            'engagement' => 3.0,
            'content_interaction' => 2.5,
            'share' => 4.0,
            'comment' => 5.0,
            'like' => 2.0,
            'bookmark' => 3.5,
            'download' => 4.5
        );
        
        $base_weight = isset($weights[$event_type]) ? $weights[$event_type] : 1.0;
        
        // Adjust weight based on event data
        if ($event_type === 'page_view' && isset($event_data['view_duration'])) {
            $duration_factor = min($event_data['view_duration'] / 60, 3); // Max 3x for 3+ minutes
            $base_weight *= (1 + $duration_factor);
        }
        
        if ($event_type === 'scroll' && isset($event_data['max_scroll'])) {
            $scroll_factor = $event_data['max_scroll'] / 100; // 0-1 based on scroll percentage
            $base_weight *= (0.5 + $scroll_factor); // 0.5x to 1.5x based on scroll
        }
        
        if ($event_type === 'engagement' && isset($event_data['engagement_score'])) {
            $base_weight *= $event_data['engagement_score'];
        }
        
        return $base_weight;
    }
    
    /**
     * Update preference weight in database
     */
    private function update_preference_weight($user_id, $preference_type, $preference_value, $weight) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ecr_user_preferences';
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE user_id = %d AND preference_type = %s AND preference_value = %s",
            $user_id, $preference_type, $preference_value
        ));
        
        if ($existing) {
            // Update existing preference with weighted average
            $new_weight = ($existing->weight * 0.8) + ($weight * 0.2); // 80% old, 20% new
            $new_count = $existing->interaction_count + 1;
            
            $wpdb->update(
                $table_name,
                array(
                    'weight' => $new_weight,
                    'interaction_count' => $new_count,
                    'last_updated' => current_time('mysql')
                ),
                array('id' => $existing->id),
                array('%f', '%d', '%s'),
                array('%d')
            );
        } else {
            // Insert new preference
            $wpdb->insert(
                $table_name,
                array(
                    'user_id' => $user_id,
                    'preference_type' => $preference_type,
                    'preference_value' => $preference_value,
                    'weight' => $weight,
                    'interaction_count' => 1,
                    'created_at' => current_time('mysql'),
                    'last_updated' => current_time('mysql')
                ),
                array('%d', '%s', '%s', '%f', '%d', '%s', '%s')
            );
        }
    }
    
    /**
     * Get content categories
     */
    private function get_content_categories($content_id, $content_type) {
        $categories = array();
        
        if ($content_type === 'post' || $content_type === 'page') {
            $terms = wp_get_post_terms($content_id, 'category', array('fields' => 'names'));
            if (!is_wp_error($terms)) {
                $categories = $terms;
            }
        } elseif ($content_type === 'product') {
            $terms = wp_get_post_terms($content_id, 'product_cat', array('fields' => 'names'));
            if (!is_wp_error($terms)) {
                $categories = $terms;
            }
        }
        
        return $categories;
    }
    
    /**
     * Get content tags
     */
    private function get_content_tags($content_id, $content_type) {
        $tags = array();
        
        if ($content_type === 'post') {
            $terms = wp_get_post_terms($content_id, 'post_tag', array('fields' => 'names'));
            if (!is_wp_error($terms)) {
                $tags = $terms;
            }
        } elseif ($content_type === 'product') {
            $terms = wp_get_post_terms($content_id, 'product_tag', array('fields' => 'names'));
            if (!is_wp_error($terms)) {
                $tags = $terms;
            }
        }
        
        return $tags;
    }
    
    /**
     * Get content environmental score
     */
    private function get_content_environmental_score($content_id, $content_type) {
        $score = 0;
        
        // Get environmental score from post meta
        $score = get_post_meta($content_id, '_environmental_score', true);
        if (empty($score)) {
            $score = 0;
        }
        
        return floatval($score);
    }
    
    /**
     * Calculate session duration
     */
    private function calculate_session_duration() {
        return time() - $this->session_start;
    }
    
    /**
     * Calculate page depth
     */
    private function calculate_page_depth($page_url) {
        $parsed_url = parse_url($page_url);
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '/';
        return substr_count($path, '/') - 1;
    }
    
    /**
     * Invalidate recommendation cache
     */
    private function invalidate_recommendation_cache($user_id) {
        $cache_keys = array(
            "ecr_recommendations_{$user_id}",
            "ecr_similar_content_{$user_id}",
            "ecr_personalized_{$user_id}"
        );
        
        foreach ($cache_keys as $key) {
            wp_cache_delete($key, 'ecr_recommendations');
        }
    }
    
    /**
     * Handle user login
     */
    public function handle_user_login($user_login, $user) {
        $this->user_id = $user->ID;
        $this->record_behavior(array(
            'user_id' => $user->ID,
            'session_id' => $this->generate_session_id(),
            'event_type' => 'login',
            'content_id' => 0,
            'content_type' => 'system',
            'event_data' => array('login_time' => current_time('mysql')),
            'timestamp' => current_time('mysql'),
            'page_url' => home_url(),
            'referrer' => '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        ));
    }
    
    /**
     * Handle user logout
     */
    public function handle_user_logout() {
        if ($this->user_id > 0) {
            $this->record_behavior(array(
                'user_id' => $this->user_id,
                'session_id' => $this->generate_session_id(),
                'event_type' => 'logout',
                'content_id' => 0,
                'content_type' => 'system',
                'event_data' => array(
                    'logout_time' => current_time('mysql'),
                    'session_duration' => $this->calculate_session_duration()
                ),
                'timestamp' => current_time('mysql'),
                'page_url' => home_url(),
                'referrer' => '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT']
            ));
        }
    }
    
    /**
     * Save session data on shutdown
     */
    public function save_session_data() {
        if (!empty($this->session_events) && $this->user_id > 0) {
            foreach ($this->session_events as $event) {
                $this->record_behavior($event);
            }
        }
    }
    
    /**
     * Output tracking data to frontend
     */
    public function output_tracking_data() {
        if (is_admin()) {
            return;
        }
        
        global $post;
        $content_id = is_singular() ? $post->ID : 0;
        $content_type = is_singular() ? $post->post_type : 'archive';
        
        echo '<script type="text/javascript">';
        echo 'window.ecrTrackingData = {';
        echo 'contentId: ' . $content_id . ',';
        echo 'contentType: "' . esc_js($content_type) . '",';
        echo 'pageUrl: "' . esc_js(get_permalink()) . '",';
        echo 'userId: ' . $this->user_id . ',';
        echo 'sessionId: "' . esc_js($this->generate_session_id()) . '"';
        echo '};';
        echo '</script>';
    }
    
    /**
     * Get user behavior analytics
     */
    public function get_user_behavior_analytics($user_id, $days = 30) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ecr_user_behavior';
        
        $since_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                event_type,
                content_type,
                COUNT(*) as event_count,
                AVG(session_duration) as avg_session_duration,
                DATE(timestamp) as event_date
             FROM {$table_name} 
             WHERE user_id = %d AND timestamp >= %s 
             GROUP BY event_type, content_type, DATE(timestamp)
             ORDER BY timestamp DESC",
            $user_id, $since_date
        ));
        
        return $results;
    }
    
    /**
     * Get popular content based on user behavior
     */
    public function get_popular_content($content_type = '', $limit = 10, $days = 7) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ecr_user_behavior';
        
        $since_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $where_content_type = !empty($content_type) ? $wpdb->prepare("AND content_type = %s", $content_type) : "";
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                content_id,
                content_type,
                COUNT(*) as interaction_count,
                COUNT(DISTINCT user_id) as unique_users,
                AVG(session_duration) as avg_engagement
             FROM {$table_name} 
             WHERE content_id > 0 AND timestamp >= %s {$where_content_type}
             GROUP BY content_id, content_type
             ORDER BY interaction_count DESC, unique_users DESC
             LIMIT %d",
            $since_date, $limit
        ));
        
        return $results;
    }
}
