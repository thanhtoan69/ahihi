<?php
/**
 * Advanced Rating and Review System for Item Exchange Platform
 * 
 * Handles user ratings, reviews, trust scores, and reputation management
 * for the environmental item exchange platform.
 * 
 * @package EnvironmentalItemExchange
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EIE_Rating_System {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Database manager instance
     */
    private $db_manager;
    
    /**
     * Get instance
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
        $this->db_manager = EIE_Database_Manager::get_instance();
        $this->init();
    }
    
    /**
     * Initialize rating system
     */
    private function init() {
        // AJAX handlers
        add_action('wp_ajax_eie_submit_rating', array($this, 'submit_rating'));
        add_action('wp_ajax_eie_get_ratings', array($this, 'get_ratings'));
        add_action('wp_ajax_eie_report_review', array($this, 'report_review'));
        add_action('wp_ajax_eie_helpful_review', array($this, 'mark_helpful'));
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Create database tables
        add_action('init', array($this, 'create_database_tables'));
        
        // Display hooks
        add_action('wp_footer', array($this, 'rating_modal_html'));
        
        // Trust score calculation
        add_action('eie_exchange_completed', array($this, 'update_trust_scores'));
        add_action('eie_rating_submitted', array($this, 'recalculate_user_rating'));
        
        // Cron job for trust score updates
        add_action('eie_daily_trust_update', array($this, 'daily_trust_score_update'));
        if (!wp_next_scheduled('eie_daily_trust_update')) {
            wp_schedule_event(time(), 'daily', 'eie_daily_trust_update');
        }
    }
    
    /**
     * Create database tables for rating system
     */
    public function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Exchange ratings table
        $table_ratings = $wpdb->prefix . 'eie_exchange_ratings';
        $sql_ratings = "CREATE TABLE IF NOT EXISTS $table_ratings (
            rating_id INT PRIMARY KEY AUTO_INCREMENT,
            exchange_request_id INT NOT NULL,
            exchange_post_id INT NOT NULL,
            rater_id INT NOT NULL,
            rated_user_id INT NOT NULL,
            
            -- Rating scores (1-5 scale)
            overall_rating DECIMAL(2,1) NOT NULL,
            communication_rating DECIMAL(2,1) DEFAULT NULL,
            reliability_rating DECIMAL(2,1) DEFAULT NULL,
            item_condition_rating DECIMAL(2,1) DEFAULT NULL,
            environmental_impact_rating DECIMAL(2,1) DEFAULT NULL,
            
            -- Review content
            review_title VARCHAR(200) DEFAULT NULL,
            review_content TEXT DEFAULT NULL,
            review_pros TEXT DEFAULT NULL,
            review_cons TEXT DEFAULT NULL,
            
            -- Review metadata
            is_verified BOOLEAN DEFAULT FALSE,
            is_anonymous BOOLEAN DEFAULT FALSE,
            helpful_count INT DEFAULT 0,
            reported_count INT DEFAULT 0,
            review_status ENUM('active', 'pending', 'hidden', 'removed') DEFAULT 'active',
            
            -- Environmental impact
            carbon_saved_estimate DECIMAL(8,2) DEFAULT NULL,
            waste_prevented_kg DECIMAL(8,2) DEFAULT NULL,
            
            -- System data
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            UNIQUE KEY unique_rating (exchange_request_id, rater_id),
            INDEX idx_rated_user (rated_user_id, review_status, created_at),
            INDEX idx_exchange_post (exchange_post_id),
            INDEX idx_rating_score (overall_rating DESC),
            INDEX idx_verification (is_verified, review_status)
        ) $charset_collate;";
        
        // Trust scores table
        $table_trust = $wpdb->prefix . 'eie_user_trust_scores';
        $sql_trust = "CREATE TABLE IF NOT EXISTS $table_trust (
            user_id INT PRIMARY KEY,
            
            -- Trust metrics
            overall_trust_score DECIMAL(5,2) DEFAULT 50.00,
            communication_score DECIMAL(5,2) DEFAULT 50.00,
            reliability_score DECIMAL(5,2) DEFAULT 50.00,
            environmental_score DECIMAL(5,2) DEFAULT 50.00,
            
            -- Rating statistics
            total_ratings_received INT DEFAULT 0,
            average_rating DECIMAL(3,2) DEFAULT 0.00,
            five_star_count INT DEFAULT 0,
            four_star_count INT DEFAULT 0,
            three_star_count INT DEFAULT 0,
            two_star_count INT DEFAULT 0,
            one_star_count INT DEFAULT 0,
            
            -- Activity metrics
            successful_exchanges INT DEFAULT 0,
            cancelled_exchanges INT DEFAULT 0,
            response_rate DECIMAL(5,2) DEFAULT 100.00,
            average_response_time_hours DECIMAL(8,2) DEFAULT NULL,
            
            -- Trust level
            trust_level ENUM('new', 'bronze', 'silver', 'gold', 'platinum', 'verified') DEFAULT 'new',
            verification_status ENUM('unverified', 'phone', 'email', 'id', 'background') DEFAULT 'unverified',
            
            -- Badges and achievements
            badges JSON DEFAULT NULL,
            achievements JSON DEFAULT NULL,
            
            -- Risk factors
            risk_flags JSON DEFAULT NULL,
            dispute_count INT DEFAULT 0,
            last_negative_review DATE DEFAULT NULL,
            
            -- Update tracking
            last_calculated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            calculation_version INT DEFAULT 1,
            
            FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
            INDEX idx_trust_score (overall_trust_score DESC),
            INDEX idx_trust_level (trust_level),
            INDEX idx_verification (verification_status),
            INDEX idx_last_calculated (last_calculated)
        ) $charset_collate;";
        
        // Review helpfulness table
        $table_helpful = $wpdb->prefix . 'eie_review_helpfulness';
        $sql_helpful = "CREATE TABLE IF NOT EXISTS $table_helpful (
            id INT PRIMARY KEY AUTO_INCREMENT,
            rating_id INT NOT NULL,
            user_id INT NOT NULL,
            is_helpful BOOLEAN NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (rating_id) REFERENCES $table_ratings(rating_id) ON DELETE CASCADE,
            UNIQUE KEY unique_helpfulness (rating_id, user_id),
            INDEX idx_rating_helpful (rating_id, is_helpful)
        ) $charset_collate;";
        
        // Rating disputes table
        $table_disputes = $wpdb->prefix . 'eie_rating_disputes';
        $sql_disputes = "CREATE TABLE IF NOT EXISTS $table_disputes (
            dispute_id INT PRIMARY KEY AUTO_INCREMENT,
            rating_id INT NOT NULL,
            disputed_by_user_id INT NOT NULL,
            dispute_reason ENUM('fake', 'inappropriate', 'irrelevant', 'personal_attack', 'other') NOT NULL,
            dispute_description TEXT,
            dispute_status ENUM('pending', 'resolved', 'dismissed') DEFAULT 'pending',
            admin_notes TEXT,
            resolved_by INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            resolved_at TIMESTAMP NULL,
            
            FOREIGN KEY (rating_id) REFERENCES $table_ratings(rating_id) ON DELETE CASCADE,
            INDEX idx_dispute_status (dispute_status, created_at),
            INDEX idx_disputed_user (disputed_by_user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_ratings);
        dbDelta($sql_trust);
        dbDelta($sql_helpful);
        dbDelta($sql_disputes);
    }
    
    /**
     * Submit rating via AJAX
     */
    public function submit_rating() {
        check_ajax_referer('eie_rating_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to submit ratings.', 'environmental-item-exchange'));
        }
        
        $user_id = get_current_user_id();
        $exchange_request_id = intval($_POST['exchange_request_id']);
        $exchange_post_id = intval($_POST['exchange_post_id']);
        $rated_user_id = intval($_POST['rated_user_id']);
        
        // Validate data
        if (!$this->can_user_rate($user_id, $exchange_request_id, $rated_user_id)) {
            wp_send_json_error(__('You are not authorized to rate this exchange.', 'environmental-item-exchange'));
        }
        
        // Get rating data
        $rating_data = array(
            'overall_rating' => floatval($_POST['overall_rating']),
            'communication_rating' => isset($_POST['communication_rating']) ? floatval($_POST['communication_rating']) : null,
            'reliability_rating' => isset($_POST['reliability_rating']) ? floatval($_POST['reliability_rating']) : null,
            'item_condition_rating' => isset($_POST['item_condition_rating']) ? floatval($_POST['item_condition_rating']) : null,
            'environmental_impact_rating' => isset($_POST['environmental_impact_rating']) ? floatval($_POST['environmental_impact_rating']) : null,
            'review_title' => sanitize_text_field($_POST['review_title']),
            'review_content' => sanitize_textarea_field($_POST['review_content']),
            'review_pros' => sanitize_textarea_field($_POST['review_pros']),
            'review_cons' => sanitize_textarea_field($_POST['review_cons']),
            'is_anonymous' => isset($_POST['is_anonymous']) && $_POST['is_anonymous'] === 'true',
            'carbon_saved_estimate' => isset($_POST['carbon_saved']) ? floatval($_POST['carbon_saved']) : null,
            'waste_prevented_kg' => isset($_POST['waste_prevented']) ? floatval($_POST['waste_prevented']) : null
        );
        
        // Validate ratings
        if ($rating_data['overall_rating'] < 1 || $rating_data['overall_rating'] > 5) {
            wp_send_json_error(__('Overall rating must be between 1 and 5.', 'environmental-item-exchange'));
        }
        
        // Save rating
        $rating_id = $this->save_rating($exchange_request_id, $exchange_post_id, $user_id, $rated_user_id, $rating_data);
        
        if ($rating_id) {
            // Update user trust scores
            $this->recalculate_user_rating($rated_user_id);
            
            // Trigger action
            do_action('eie_rating_submitted', $rating_id, $rated_user_id, $rating_data);
            
            wp_send_json_success(array(
                'rating_id' => $rating_id,
                'message' => __('Rating submitted successfully!', 'environmental-item-exchange')
            ));
        } else {
            wp_send_json_error(__('Failed to submit rating.', 'environmental-item-exchange'));
        }
    }
    
    /**
     * Get ratings for a user or exchange
     */
    public function get_ratings() {
        check_ajax_referer('eie_rating_nonce', 'nonce');
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;
        $exchange_post_id = isset($_POST['exchange_post_id']) ? intval($_POST['exchange_post_id']) : null;
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = 10;
        
        if ($user_id) {
            $ratings = $this->get_user_ratings($user_id, $page, $per_page);
        } elseif ($exchange_post_id) {
            $ratings = $this->get_exchange_ratings($exchange_post_id, $page, $per_page);
        } else {
            wp_send_json_error(__('Invalid request.', 'environmental-item-exchange'));
        }
        
        wp_send_json_success($ratings);
    }
    
    /**
     * Save rating to database
     */
    private function save_rating($exchange_request_id, $exchange_post_id, $rater_id, $rated_user_id, $rating_data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'eie_exchange_ratings';
        
        $data = array_merge($rating_data, array(
            'exchange_request_id' => $exchange_request_id,
            'exchange_post_id' => $exchange_post_id,
            'rater_id' => $rater_id,
            'rated_user_id' => $rated_user_id,
            'ip_address' => $this->get_user_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'created_at' => current_time('mysql')
        ));
        
        $result = $wpdb->insert($table, $data);
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Check if user can rate an exchange
     */
    private function can_user_rate($user_id, $exchange_request_id, $rated_user_id) {
        global $wpdb;
        
        // Check if exchange request exists and user is involved
        $table_requests = $wpdb->prefix . 'exchange_requests';
        $request = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_requests 
             WHERE request_id = %d AND (requester_id = %d OR poster_id = %d)
             AND request_status = 'completed'",
            $exchange_request_id, $user_id, $user_id
        ));
        
        if (!$request) {
            return false;
        }
        
        // Check if already rated
        $table_ratings = $wpdb->prefix . 'eie_exchange_ratings';
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_ratings 
             WHERE exchange_request_id = %d AND rater_id = %d",
            $exchange_request_id, $user_id
        ));
        
        return $existing == 0;
    }
    
    /**
     * Get user ratings
     */
    private function get_user_ratings($user_id, $page = 1, $per_page = 10) {
        global $wpdb;
        
        $offset = ($page - 1) * $per_page;
        $table_ratings = $wpdb->prefix . 'eie_exchange_ratings';
        
        $ratings = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, 
                    rater.display_name as rater_name,
                    ep.title as exchange_title
             FROM $table_ratings r
             LEFT JOIN {$wpdb->users} rater ON r.rater_id = rater.ID
             LEFT JOIN {$wpdb->posts} ep ON r.exchange_post_id = ep.ID
             WHERE r.rated_user_id = %d AND r.review_status = 'active'
             ORDER BY r.created_at DESC
             LIMIT %d OFFSET %d",
            $user_id, $per_page, $offset
        ));
        
        // Get total count
        $total_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_ratings 
             WHERE rated_user_id = %d AND review_status = 'active'",
            $user_id
        ));
        
        // Format ratings
        foreach ($ratings as &$rating) {
            $rating->time_ago = human_time_diff(strtotime($rating->created_at), current_time('timestamp')) . ' ' . __('ago', 'environmental-item-exchange');
            $rating->rater_avatar = get_avatar_url($rating->rater_id, array('size' => 40));
            
            if ($rating->is_anonymous) {
                $rating->rater_name = __('Anonymous', 'environmental-item-exchange');
                $rating->rater_avatar = get_avatar_url(0, array('size' => 40));
            }
        }
        
        return array(
            'ratings' => $ratings,
            'total_count' => $total_count,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total_count / $per_page)
        );
    }
    
    /**
     * Get exchange ratings
     */
    private function get_exchange_ratings($exchange_post_id, $page = 1, $per_page = 10) {
        global $wpdb;
        
        $offset = ($page - 1) * $per_page;
        $table_ratings = $wpdb->prefix . 'eie_exchange_ratings';
        
        $ratings = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, 
                    rater.display_name as rater_name,
                    rated.display_name as rated_user_name
             FROM $table_ratings r
             LEFT JOIN {$wpdb->users} rater ON r.rater_id = rater.ID
             LEFT JOIN {$wpdb->users} rated ON r.rated_user_id = rated.ID
             WHERE r.exchange_post_id = %d AND r.review_status = 'active'
             ORDER BY r.created_at DESC
             LIMIT %d OFFSET %d",
            $exchange_post_id, $per_page, $offset
        ));
        
        // Format ratings
        foreach ($ratings as &$rating) {
            $rating->time_ago = human_time_diff(strtotime($rating->created_at), current_time('timestamp')) . ' ' . __('ago', 'environmental-item-exchange');
            $rating->rater_avatar = get_avatar_url($rating->rater_id, array('size' => 40));
            
            if ($rating->is_anonymous) {
                $rating->rater_name = __('Anonymous', 'environmental-item-exchange');
                $rating->rater_avatar = get_avatar_url(0, array('size' => 40));
            }
        }
        
        return $ratings;
    }
    
    /**
     * Recalculate user rating and trust score
     */
    public function recalculate_user_rating($user_id) {
        global $wpdb;
        
        $table_ratings = $wpdb->prefix . 'eie_exchange_ratings';
        $table_trust = $wpdb->prefix . 'eie_user_trust_scores';
        
        // Get rating statistics
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_ratings,
                AVG(overall_rating) as avg_rating,
                AVG(communication_rating) as avg_communication,
                AVG(reliability_rating) as avg_reliability,
                AVG(environmental_impact_rating) as avg_environmental,
                SUM(CASE WHEN overall_rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN overall_rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN overall_rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN overall_rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN overall_rating = 1 THEN 1 ELSE 0 END) as one_star
             FROM $table_ratings 
             WHERE rated_user_id = %d AND review_status = 'active'",
            $user_id
        ));
        
        if ($stats->total_ratings > 0) {
            // Calculate trust scores
            $communication_score = $this->calculate_score_component($stats->avg_communication, $stats->total_ratings);
            $reliability_score = $this->calculate_score_component($stats->avg_reliability, $stats->total_ratings);
            $environmental_score = $this->calculate_score_component($stats->avg_environmental, $stats->total_ratings);
            
            // Overall trust score (weighted average)
            $overall_trust = ($communication_score * 0.3) + ($reliability_score * 0.4) + ($environmental_score * 0.3);
            
            // Determine trust level
            $trust_level = $this->calculate_trust_level($overall_trust, $stats->total_ratings);
            
            // Update or insert trust scores
            $wpdb->replace(
                $table_trust,
                array(
                    'user_id' => $user_id,
                    'overall_trust_score' => $overall_trust,
                    'communication_score' => $communication_score,
                    'reliability_score' => $reliability_score,
                    'environmental_score' => $environmental_score,
                    'total_ratings_received' => $stats->total_ratings,
                    'average_rating' => $stats->avg_rating,
                    'five_star_count' => $stats->five_star,
                    'four_star_count' => $stats->four_star,
                    'three_star_count' => $stats->three_star,
                    'two_star_count' => $stats->two_star,
                    'one_star_count' => $stats->one_star,
                    'trust_level' => $trust_level,
                    'last_calculated' => current_time('mysql')
                ),
                array('%d', '%f', '%f', '%f', '%f', '%d', '%f', '%d', '%d', '%d', '%d', '%d', '%s', '%s')
            );
            
            // Update user meta
            update_user_meta($user_id, 'eie_trust_score', $overall_trust);
            update_user_meta($user_id, 'eie_average_rating', $stats->avg_rating);
            update_user_meta($user_id, 'eie_total_ratings', $stats->total_ratings);
        }
    }
    
    /**
     * Calculate score component
     */
    private function calculate_score_component($avg_rating, $total_ratings) {
        if (!$avg_rating || $total_ratings == 0) {
            return 50.0; // Default score
        }
        
        // Convert 1-5 rating to 0-100 score
        $base_score = (($avg_rating - 1) / 4) * 100;
        
        // Apply confidence factor based on number of ratings
        $confidence_factor = min(1.0, $total_ratings / 20); // Full confidence at 20+ ratings
        
        return ($base_score * $confidence_factor) + (50 * (1 - $confidence_factor));
    }
    
    /**
     * Calculate trust level
     */
    private function calculate_trust_level($trust_score, $total_ratings) {
        if ($total_ratings >= 50 && $trust_score >= 90) {
            return 'platinum';
        } elseif ($total_ratings >= 25 && $trust_score >= 80) {
            return 'gold';
        } elseif ($total_ratings >= 10 && $trust_score >= 70) {
            return 'silver';
        } elseif ($total_ratings >= 5 && $trust_score >= 60) {
            return 'bronze';
        } else {
            return 'new';
        }
    }
    
    /**
     * Mark review as helpful
     */
    public function mark_helpful() {
        check_ajax_referer('eie_rating_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'environmental-item-exchange'));
        }
        
        $user_id = get_current_user_id();
        $rating_id = intval($_POST['rating_id']);
        $is_helpful = $_POST['is_helpful'] === 'true';
        
        global $wpdb;
        $table_helpful = $wpdb->prefix . 'eie_review_helpfulness';
        
        // Check if already marked
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT is_helpful FROM $table_helpful 
             WHERE rating_id = %d AND user_id = %d",
            $rating_id, $user_id
        ));
        
        if ($existing === null) {
            // Insert new
            $wpdb->insert(
                $table_helpful,
                array(
                    'rating_id' => $rating_id,
                    'user_id' => $user_id,
                    'is_helpful' => $is_helpful
                ),
                array('%d', '%d', '%d')
            );
        } else {
            // Update existing
            $wpdb->update(
                $table_helpful,
                array('is_helpful' => $is_helpful),
                array('rating_id' => $rating_id, 'user_id' => $user_id),
                array('%d'),
                array('%d', '%d')
            );
        }
        
        // Update helpful count
        $helpful_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_helpful 
             WHERE rating_id = %d AND is_helpful = TRUE",
            $rating_id
        ));
        
        $table_ratings = $wpdb->prefix . 'eie_exchange_ratings';
        $wpdb->update(
            $table_ratings,
            array('helpful_count' => $helpful_count),
            array('rating_id' => $rating_id),
            array('%d'),
            array('%d')
        );
        
        wp_send_json_success(array(
            'helpful_count' => $helpful_count,
            'user_marked_helpful' => $is_helpful
        ));
    }
    
    /**
     * Report review
     */
    public function report_review() {
        check_ajax_referer('eie_rating_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'environmental-item-exchange'));
        }
        
        $user_id = get_current_user_id();
        $rating_id = intval($_POST['rating_id']);
        $reason = sanitize_text_field($_POST['reason']);
        $description = sanitize_textarea_field($_POST['description']);
        
        global $wpdb;
        $table_disputes = $wpdb->prefix . 'eie_rating_disputes';
        
        $result = $wpdb->insert(
            $table_disputes,
            array(
                'rating_id' => $rating_id,
                'disputed_by_user_id' => $user_id,
                'dispute_reason' => $reason,
                'dispute_description' => $description,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );
        
        if ($result) {
            wp_send_json_success(__('Review reported successfully.', 'environmental-item-exchange'));
        } else {
            wp_send_json_error(__('Failed to report review.', 'environmental-item-exchange'));
        }
    }
    
    /**
     * Daily trust score update
     */
    public function daily_trust_score_update() {
        global $wpdb;
        
        // Get users who need trust score recalculation
        $table_trust = $wpdb->prefix . 'eie_user_trust_scores';
        $users = $wpdb->get_col(
            "SELECT user_id FROM $table_trust 
             WHERE last_calculated < DATE_SUB(NOW(), INTERVAL 1 DAY)
             LIMIT 100"
        );
        
        foreach ($users as $user_id) {
            $this->recalculate_user_rating($user_id);
        }
    }
    
    /**
     * Get user trust information
     */
    public function get_user_trust_info($user_id) {
        global $wpdb;
        
        $table_trust = $wpdb->prefix . 'eie_user_trust_scores';
        $trust_info = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_trust WHERE user_id = %d", $user_id
        ));
        
        if (!$trust_info) {
            // Create default trust info
            $this->recalculate_user_rating($user_id);
            $trust_info = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_trust WHERE user_id = %d", $user_id
            ));
        }
        
        return $trust_info;
    }
    
    /**
     * Get user's rating distribution
     */
    public function get_rating_distribution($user_id) {
        $trust_info = $this->get_user_trust_info($user_id);
        
        if (!$trust_info || $trust_info->total_ratings_received == 0) {
            return array();
        }
        
        return array(
            5 => array('count' => $trust_info->five_star_count, 'percentage' => ($trust_info->five_star_count / $trust_info->total_ratings_received) * 100),
            4 => array('count' => $trust_info->four_star_count, 'percentage' => ($trust_info->four_star_count / $trust_info->total_ratings_received) * 100),
            3 => array('count' => $trust_info->three_star_count, 'percentage' => ($trust_info->three_star_count / $trust_info->total_ratings_received) * 100),
            2 => array('count' => $trust_info->two_star_count, 'percentage' => ($trust_info->two_star_count / $trust_info->total_ratings_received) * 100),
            1 => array('count' => $trust_info->one_star_count, 'percentage' => ($trust_info->one_star_count / $trust_info->total_ratings_received) * 100)
        );
    }
    
    /**
     * Rating modal HTML
     */
    public function rating_modal_html() {
        if (is_admin() || !is_user_logged_in()) {
            return;
        }
        ?>
        <div id="eie-rating-modal" class="eie-modal" style="display: none;">
            <div class="eie-modal-content">
                <span class="eie-modal-close">&times;</span>
                <h3><?php _e('Rate This Exchange', 'environmental-item-exchange'); ?></h3>
                
                <form id="eie-rating-form">
                    <input type="hidden" id="eie-exchange-request-id" name="exchange_request_id">
                    <input type="hidden" id="eie-exchange-post-id" name="exchange_post_id">
                    <input type="hidden" id="eie-rated-user-id" name="rated_user_id">
                    
                    <div class="eie-rating-section">
                        <label><?php _e('Overall Rating', 'environmental-item-exchange'); ?> <span class="required">*</span></label>
                        <div class="eie-star-rating" data-rating="overall_rating">
                            <?php for ($i = 1; $i <= 5; $i++) : ?>
                                <span class="eie-star" data-value="<?php echo $i; ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="overall_rating" required>
                    </div>
                    
                    <div class="eie-rating-section">
                        <label><?php _e('Communication', 'environmental-item-exchange'); ?></label>
                        <div class="eie-star-rating" data-rating="communication_rating">
                            <?php for ($i = 1; $i <= 5; $i++) : ?>
                                <span class="eie-star" data-value="<?php echo $i; ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="communication_rating">
                    </div>
                    
                    <div class="eie-rating-section">
                        <label><?php _e('Reliability', 'environmental-item-exchange'); ?></label>
                        <div class="eie-star-rating" data-rating="reliability_rating">
                            <?php for ($i = 1; $i <= 5; $i++) : ?>
                                <span class="eie-star" data-value="<?php echo $i; ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="reliability_rating">
                    </div>
                    
                    <div class="eie-rating-section">
                        <label><?php _e('Item Condition', 'environmental-item-exchange'); ?></label>
                        <div class="eie-star-rating" data-rating="item_condition_rating">
                            <?php for ($i = 1; $i <= 5; $i++) : ?>
                                <span class="eie-star" data-value="<?php echo $i; ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="item_condition_rating">
                    </div>
                    
                    <div class="eie-form-group">
                        <label><?php _e('Review Title', 'environmental-item-exchange'); ?></label>
                        <input type="text" name="review_title" placeholder="<?php _e('Brief summary of your experience', 'environmental-item-exchange'); ?>">
                    </div>
                    
                    <div class="eie-form-group">
                        <label><?php _e('Review', 'environmental-item-exchange'); ?></label>
                        <textarea name="review_content" rows="4" placeholder="<?php _e('Share your experience with this exchange...', 'environmental-item-exchange'); ?>"></textarea>
                    </div>
                    
                    <div class="eie-form-row">
                        <div class="eie-form-group">
                            <label><?php _e('What went well?', 'environmental-item-exchange'); ?></label>
                            <textarea name="review_pros" rows="2" placeholder="<?php _e('Positive aspects...', 'environmental-item-exchange'); ?>"></textarea>
                        </div>
                        <div class="eie-form-group">
                            <label><?php _e('What could be improved?', 'environmental-item-exchange'); ?></label>
                            <textarea name="review_cons" rows="2" placeholder="<?php _e('Areas for improvement...', 'environmental-item-exchange'); ?>"></textarea>
                        </div>
                    </div>
                    
                    <div class="eie-form-group">
                        <label>
                            <input type="checkbox" name="is_anonymous" value="true">
                            <?php _e('Submit anonymously', 'environmental-item-exchange'); ?>
                        </label>
                    </div>
                    
                    <div class="eie-form-actions">
                        <button type="button" class="eie-btn-secondary" onclick="EIE.closeRatingModal()"><?php _e('Cancel', 'environmental-item-exchange'); ?></button>
                        <button type="submit" class="eie-btn-primary"><?php _e('Submit Rating', 'environmental-item-exchange'); ?></button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'environmental-item-exchange',
            __('Ratings & Reviews', 'environmental-item-exchange'),
            __('Ratings', 'environmental-item-exchange'),
            'manage_options',
            'eie-ratings',
            array($this, 'admin_ratings_page')
        );
    }
    
    /**
     * Admin ratings page
     */
    public function admin_ratings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Ratings & Reviews', 'environmental-item-exchange'); ?></h1>
            
            <div class="eie-admin-ratings">
                <div class="eie-stats-cards">
                    <div class="eie-stat-card">
                        <h3><?php echo $this->get_total_ratings(); ?></h3>
                        <p><?php _e('Total Ratings', 'environmental-item-exchange'); ?></p>
                    </div>
                    <div class="eie-stat-card">
                        <h3><?php echo number_format($this->get_average_rating(), 1); ?></h3>
                        <p><?php _e('Average Rating', 'environmental-item-exchange'); ?></p>
                    </div>
                    <div class="eie-stat-card">
                        <h3><?php echo $this->get_flagged_reviews(); ?></h3>
                        <p><?php _e('Flagged Reviews', 'environmental-item-exchange'); ?></p>
                    </div>
                </div>
                
                <div class="eie-recent-ratings">
                    <h2><?php _e('Recent Ratings', 'environmental-item-exchange'); ?></h2>
                    <?php $this->display_recent_ratings(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get statistics for admin
     */
    private function get_total_ratings() {
        global $wpdb;
        $table = $wpdb->prefix . 'eie_exchange_ratings';
        return $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE review_status = 'active'");
    }
    
    private function get_average_rating() {
        global $wpdb;
        $table = $wpdb->prefix . 'eie_exchange_ratings';
        return $wpdb->get_var("SELECT AVG(overall_rating) FROM $table WHERE review_status = 'active'") ?: 0;
    }
    
    private function get_flagged_reviews() {
        global $wpdb;
        $table = $wpdb->prefix . 'eie_rating_disputes';
        return $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE dispute_status = 'pending'");
    }
    
    /**
     * Display recent ratings in admin
     */
    private function display_recent_ratings() {
        global $wpdb;
        $table_ratings = $wpdb->prefix . 'eie_exchange_ratings';
        
        $ratings = $wpdb->get_results(
            "SELECT r.*, 
                    rater.display_name as rater_name,
                    rated.display_name as rated_user_name
             FROM $table_ratings r
             LEFT JOIN {$wpdb->users} rater ON r.rater_id = rater.ID
             LEFT JOIN {$wpdb->users} rated ON r.rated_user_id = rated.ID
             WHERE r.review_status = 'active'
             ORDER BY r.created_at DESC
             LIMIT 20"
        );
        
        if ($ratings) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('Rater', 'environmental-item-exchange') . '</th>';
            echo '<th>' . __('Rated User', 'environmental-item-exchange') . '</th>';
            echo '<th>' . __('Rating', 'environmental-item-exchange') . '</th>';
            echo '<th>' . __('Review', 'environmental-item-exchange') . '</th>';
            echo '<th>' . __('Date', 'environmental-item-exchange') . '</th>';
            echo '</tr></thead><tbody>';
            
            foreach ($ratings as $rating) {
                echo '<tr>';
                echo '<td>' . esc_html($rating->rater_name) . '</td>';
                echo '<td>' . esc_html($rating->rated_user_name) . '</td>';
                echo '<td>' . str_repeat('★', $rating->overall_rating) . str_repeat('☆', 5 - $rating->overall_rating) . '</td>';
                echo '<td>' . esc_html(wp_trim_words($rating->review_content, 10)) . '</td>';
                echo '<td>' . date_i18n(get_option('date_format'), strtotime($rating->created_at)) . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        } else {
            echo '<p>' . __('No ratings found.', 'environmental-item-exchange') . '</p>';
        }
    }
    
    /**
     * Get user IP address
     */
    private function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
}
