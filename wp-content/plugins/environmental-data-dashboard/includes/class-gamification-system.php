<?php
/**
 * Gamification System
 * 
 * Handles gamification features including points, badges, achievements,
 * leaderboards, and user engagement for waste classification.
 * 
 * @package Environmental_Data_Dashboard
 * @since 1.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Gamification_System {
    
    private static $instance = null;
    
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
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // AJAX handlers
        add_action('wp_ajax_get_user_achievements', array($this, 'ajax_get_user_achievements'));
        add_action('wp_ajax_get_leaderboard', array($this, 'ajax_get_leaderboard'));
        add_action('wp_ajax_claim_achievement', array($this, 'ajax_claim_achievement'));
        add_action('wp_ajax_get_user_progress', array($this, 'ajax_get_user_progress'));
        
        // Hooks for awarding points and achievements
        add_action('env_ai_classification_completed', array($this, 'award_classification_points'), 10, 3);
        add_action('env_ai_feedback_submitted', array($this, 'award_feedback_points'), 10, 2);
        add_action('env_ai_achievement_unlocked', array($this, 'notify_achievement_unlocked'), 10, 2);
        
        // Daily/weekly challenges
        add_action('env_gamification_daily_reset', array($this, 'reset_daily_challenges'));
        add_action('env_gamification_weekly_reset', array($this, 'reset_weekly_challenges'));
        
        // Shortcodes
        add_shortcode('user_achievements', array($this, 'user_achievements_shortcode'));
        add_shortcode('classification_leaderboard', array($this, 'leaderboard_shortcode'));
        add_shortcode('user_progress', array($this, 'user_progress_shortcode'));
        add_shortcode('achievement_showcase', array($this, 'achievement_showcase_shortcode'));
    }
    
    /**
     * Award points for successful classification
     */
    public function award_classification_points($user_id, $classification_data, $confidence) {
        if (!$user_id) return;
        
        $base_points = 10;
        $confidence_bonus = round($confidence * 10); // 0-10 bonus points
        $category_bonus = $this->get_category_bonus($classification_data['category']);
        
        $total_points = $base_points + $confidence_bonus + $category_bonus;
        
        $this->add_user_points($user_id, $total_points, 'classification');
        
        // Check for achievements
        $this->check_classification_achievements($user_id);
        
        // Update daily/weekly progress
        $this->update_challenge_progress($user_id, 'daily_classifications', 1);
        $this->update_challenge_progress($user_id, 'weekly_classifications', 1);
        
        return $total_points;
    }
    
    /**
     * Award points for providing feedback
     */
    public function award_feedback_points($user_id, $is_correct) {
        if (!$user_id) return;
        
        $points = $is_correct ? 5 : 3; // More points for correct feedback
        
        $this->add_user_points($user_id, $points, 'feedback');
        
        // Check for feedback achievements
        $this->check_feedback_achievements($user_id);
        
        return $points;
    }
    
    /**
     * Get category-specific bonus points
     */
    private function get_category_bonus($category) {
        $bonuses = array(
            'hazardous' => 5, // Harder to classify correctly
            'electronic' => 4,
            'recyclable' => 2,
            'organic' => 1,
            'general' => 0
        );
        
        return $bonuses[$category] ?? 0;
    }
    
    /**
     * Add points to user's account
     */
    public function add_user_points($user_id, $points, $reason = '') {
        global $wpdb;
        
        $points_table = $wpdb->prefix . 'env_gamification_points';
        
        // Add to points history
        $wpdb->insert(
            $points_table,
            array(
                'user_id' => $user_id,
                'points' => $points,
                'reason' => $reason,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s')
        );
        
        // Update user's total points
        $current_total = get_user_meta($user_id, 'env_total_points', true) ?: 0;
        $new_total = $current_total + $points;
        update_user_meta($user_id, 'env_total_points', $new_total);
        
        // Update level based on points
        $this->update_user_level($user_id, $new_total);
        
        return $new_total;
    }
    
    /**
     * Update user level based on points
     */
    private function update_user_level($user_id, $total_points) {
        $current_level = get_user_meta($user_id, 'env_user_level', true) ?: 1;
        $new_level = $this->calculate_level_from_points($total_points);
        
        if ($new_level > $current_level) {
            update_user_meta($user_id, 'env_user_level', $new_level);
            
            // Award level-up achievement
            $this->unlock_achievement($user_id, 'level_' . $new_level);
            
            // Trigger level-up notification
            do_action('env_user_leveled_up', $user_id, $new_level, $current_level);
        }
        
        return $new_level;
    }
    
    /**
     * Calculate level from total points
     */
    private function calculate_level_from_points($points) {
        // Level progression: 0->100->300->600->1000->1500->2100->2800->3600->4500->5500...
        $level = 1;
        $required_points = 0;
        $increment = 100;
        
        while ($points >= $required_points + $increment) {
            $required_points += $increment;
            $level++;
            $increment += 100; // Increasing difficulty
        }
        
        return $level;
    }
    
    /**
     * Get points required for next level
     */
    public function get_points_for_next_level($current_points) {
        $current_level = $this->calculate_level_from_points($current_points);
        $next_level_points = 0;
        $increment = 100;
        
        for ($i = 1; $i <= $current_level; $i++) {
            $next_level_points += $increment;
            $increment += 100;
        }
        
        return $next_level_points;
    }
    
    /**
     * Check and unlock classification achievements
     */
    private function check_classification_achievements($user_id) {
        global $wpdb;
        
        $classifications_table = $wpdb->prefix . 'env_ai_classifications';
        
        // Get user classification stats
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total,
                COUNT(DISTINCT category) as unique_categories,
                AVG(confidence) as avg_confidence
             FROM {$classifications_table} 
             WHERE user_id = %d",
            $user_id
        ));
        
        $achievements_to_check = array(
            // Classification count achievements
            'first_classification' => array('condition' => $stats->total >= 1, 'name' => __('First Steps', 'env-data-dashboard')),
            'novice_classifier' => array('condition' => $stats->total >= 10, 'name' => __('Novice Classifier', 'env-data-dashboard')),
            'experienced_classifier' => array('condition' => $stats->total >= 25, 'name' => __('Experienced Classifier', 'env-data-dashboard')),
            'expert_classifier' => array('condition' => $stats->total >= 50, 'name' => __('Expert Classifier', 'env-data-dashboard')),
            'master_classifier' => array('condition' => $stats->total >= 100, 'name' => __('Master Classifier', 'env-data-dashboard')),
            'classification_legend' => array('condition' => $stats->total >= 250, 'name' => __('Classification Legend', 'env-data-dashboard')),
            
            // Category diversity achievements
            'category_explorer' => array('condition' => $stats->unique_categories >= 3, 'name' => __('Category Explorer', 'env-data-dashboard')),
            'waste_specialist' => array('condition' => $stats->unique_categories >= 5, 'name' => __('Waste Specialist', 'env-data-dashboard')),
            
            // Confidence achievements
            'confident_classifier' => array('condition' => $stats->avg_confidence >= 0.8, 'name' => __('Confident Classifier', 'env-data-dashboard')),
            'precision_master' => array('condition' => $stats->avg_confidence >= 0.9, 'name' => __('Precision Master', 'env-data-dashboard'))
        );
        
        foreach ($achievements_to_check as $achievement_key => $achievement) {
            if ($achievement['condition'] && !$this->user_has_achievement($user_id, $achievement_key)) {
                $this->unlock_achievement($user_id, $achievement_key);
            }
        }
        
        // Check streak achievements
        $this->check_streak_achievements($user_id);
        
        // Check time-based achievements
        $this->check_time_based_achievements($user_id);
    }
    
    /**
     * Check streak achievements
     */
    private function check_streak_achievements($user_id) {
        $current_streak = $this->get_user_classification_streak($user_id);
        
        $streak_achievements = array(
            'streak_3' => array('days' => 3, 'name' => __('3-Day Streak', 'env-data-dashboard')),
            'streak_7' => array('days' => 7, 'name' => __('Week Warrior', 'env-data-dashboard')),
            'streak_14' => array('days' => 14, 'name' => __('Two Week Champion', 'env-data-dashboard')),
            'streak_30' => array('days' => 30, 'name' => __('Monthly Master', 'env-data-dashboard')),
            'streak_100' => array('days' => 100, 'name' => __('Centurion', 'env-data-dashboard'))
        );
        
        foreach ($streak_achievements as $achievement_key => $achievement) {
            if ($current_streak >= $achievement['days'] && !$this->user_has_achievement($user_id, $achievement_key)) {
                $this->unlock_achievement($user_id, $achievement_key);
            }
        }
    }
    
    /**
     * Check time-based achievements
     */
    private function check_time_based_achievements($user_id) {
        global $wpdb;
        
        $classifications_table = $wpdb->prefix . 'env_ai_classifications';
        
        // Early bird achievement (classifications before 8 AM)
        $early_classifications = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$classifications_table} 
             WHERE user_id = %d AND HOUR(created_at) < 8",
            $user_id
        ));
        
        if ($early_classifications >= 5 && !$this->user_has_achievement($user_id, 'early_bird')) {
            $this->unlock_achievement($user_id, 'early_bird');
        }
        
        // Night owl achievement (classifications after 10 PM)
        $night_classifications = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$classifications_table} 
             WHERE user_id = %d AND HOUR(created_at) >= 22",
            $user_id
        ));
        
        if ($night_classifications >= 5 && !$this->user_has_achievement($user_id, 'night_owl')) {
            $this->unlock_achievement($user_id, 'night_owl');
        }
        
        // Weekend warrior achievement (classifications on weekends)
        $weekend_classifications = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$classifications_table} 
             WHERE user_id = %d AND DAYOFWEEK(created_at) IN (1, 7)",
            $user_id
        ));
        
        if ($weekend_classifications >= 10 && !$this->user_has_achievement($user_id, 'weekend_warrior')) {
            $this->unlock_achievement($user_id, 'weekend_warrior');
        }
    }
    
    /**
     * Check feedback achievements
     */
    private function check_feedback_achievements($user_id) {
        global $wpdb;
        
        $feedback_table = $wpdb->prefix . 'env_ai_feedback';
        
        $feedback_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_feedback,
                SUM(is_correct) as correct_feedback,
                AVG(is_correct) as accuracy_rate
             FROM {$feedback_table} 
             WHERE user_id = %d",
            $user_id
        ));
        
        $feedback_achievements = array(
            'feedback_provider' => array('condition' => $feedback_stats->total_feedback >= 5, 'name' => __('Feedback Provider', 'env-data-dashboard')),
            'quality_controller' => array('condition' => $feedback_stats->total_feedback >= 20, 'name' => __('Quality Controller', 'env-data-dashboard')),
            'accuracy_expert' => array('condition' => $feedback_stats->accuracy_rate >= 0.9 && $feedback_stats->total_feedback >= 10, 'name' => __('Accuracy Expert', 'env-data-dashboard')),
            'helpful_reviewer' => array('condition' => $feedback_stats->total_feedback >= 50, 'name' => __('Helpful Reviewer', 'env-data-dashboard'))
        );
        
        foreach ($feedback_achievements as $achievement_key => $achievement) {
            if ($achievement['condition'] && !$this->user_has_achievement($user_id, $achievement_key)) {
                $this->unlock_achievement($user_id, $achievement_key);
            }
        }
    }
    
    /**
     * Get user's classification streak
     */
    public function get_user_classification_streak($user_id) {
        global $wpdb;
        
        $classifications_table = $wpdb->prefix . 'env_ai_classifications';
        
        // Get dates with classifications in descending order
        $classification_dates = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT DATE(created_at) as classification_date 
             FROM {$classifications_table} 
             WHERE user_id = %d 
             ORDER BY classification_date DESC",
            $user_id
        ));
        
        if (empty($classification_dates)) {
            return 0;
        }
        
        $streak = 0;
        $current_date = current_time('Y-m-d');
        
        foreach ($classification_dates as $date) {
            $expected_date = date('Y-m-d', strtotime("-{$streak} days", strtotime($current_date)));
            
            if ($date === $expected_date) {
                $streak++;
            } else {
                break;
            }
        }
        
        return $streak;
    }
    
    /**
     * Check if user has specific achievement
     */
    public function user_has_achievement($user_id, $achievement_key) {
        $user_achievements = get_user_meta($user_id, 'env_achievements', true) ?: array();
        return in_array($achievement_key, $user_achievements);
    }
    
    /**
     * Unlock achievement for user
     */
    public function unlock_achievement($user_id, $achievement_key) {
        $user_achievements = get_user_meta($user_id, 'env_achievements', true) ?: array();
        
        if (!in_array($achievement_key, $user_achievements)) {
            $user_achievements[] = $achievement_key;
            update_user_meta($user_id, 'env_achievements', $user_achievements);
            
            // Award points for achievement
            $achievement_points = $this->get_achievement_points($achievement_key);
            $this->add_user_points($user_id, $achievement_points, 'achievement_' . $achievement_key);
            
            // Log achievement unlock
            $this->log_achievement_unlock($user_id, $achievement_key);
            
            // Trigger achievement notification
            do_action('env_ai_achievement_unlocked', $user_id, $achievement_key);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get points awarded for specific achievement
     */
    private function get_achievement_points($achievement_key) {
        $achievement_points = array(
            'first_classification' => 50,
            'novice_classifier' => 100,
            'experienced_classifier' => 200,
            'expert_classifier' => 300,
            'master_classifier' => 500,
            'classification_legend' => 1000,
            'category_explorer' => 150,
            'waste_specialist' => 250,
            'confident_classifier' => 200,
            'precision_master' => 400,
            'streak_3' => 100,
            'streak_7' => 200,
            'streak_14' => 400,
            'streak_30' => 800,
            'streak_100' => 2000,
            'early_bird' => 150,
            'night_owl' => 150,
            'weekend_warrior' => 300,
            'feedback_provider' => 100,
            'quality_controller' => 300,
            'accuracy_expert' => 500,
            'helpful_reviewer' => 750
        );
        
        return $achievement_points[$achievement_key] ?? 50;
    }
    
    /**
     * Log achievement unlock
     */
    private function log_achievement_unlock($user_id, $achievement_key) {
        global $wpdb;
        
        $achievement_log_table = $wpdb->prefix . 'env_achievement_log';
        
        $wpdb->insert(
            $achievement_log_table,
            array(
                'user_id' => $user_id,
                'achievement_key' => $achievement_key,
                'unlocked_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s')
        );
    }
    
    /**
     * Get all available achievements with metadata
     */
    public function get_all_achievements() {
        return array(
            'first_classification' => array(
                'name' => __('First Steps', 'env-data-dashboard'),
                'description' => __('Complete your first waste classification', 'env-data-dashboard'),
                'icon' => '🌱',
                'category' => 'milestone',
                'points' => 50,
                'difficulty' => 'easy'
            ),
            'novice_classifier' => array(
                'name' => __('Novice Classifier', 'env-data-dashboard'),
                'description' => __('Complete 10 waste classifications', 'env-data-dashboard'),
                'icon' => '📚',
                'category' => 'milestone',
                'points' => 100,
                'difficulty' => 'easy'
            ),
            'experienced_classifier' => array(
                'name' => __('Experienced Classifier', 'env-data-dashboard'),
                'description' => __('Complete 25 waste classifications', 'env-data-dashboard'),
                'icon' => '🎓',
                'category' => 'milestone',
                'points' => 200,
                'difficulty' => 'medium'
            ),
            'expert_classifier' => array(
                'name' => __('Expert Classifier', 'env-data-dashboard'),
                'description' => __('Complete 50 waste classifications', 'env-data-dashboard'),
                'icon' => '🏆',
                'category' => 'milestone',
                'points' => 300,
                'difficulty' => 'medium'
            ),
            'master_classifier' => array(
                'name' => __('Master Classifier', 'env-data-dashboard'),
                'description' => __('Complete 100 waste classifications', 'env-data-dashboard'),
                'icon' => '👑',
                'category' => 'milestone',
                'points' => 500,
                'difficulty' => 'hard'
            ),
            'classification_legend' => array(
                'name' => __('Classification Legend', 'env-data-dashboard'),
                'description' => __('Complete 250 waste classifications', 'env-data-dashboard'),
                'icon' => '⭐',
                'category' => 'milestone',
                'points' => 1000,
                'difficulty' => 'legendary'
            ),
            'category_explorer' => array(
                'name' => __('Category Explorer', 'env-data-dashboard'),
                'description' => __('Classify waste from 3 different categories', 'env-data-dashboard'),
                'icon' => '🔍',
                'category' => 'diversity',
                'points' => 150,
                'difficulty' => 'medium'
            ),
            'waste_specialist' => array(
                'name' => __('Waste Specialist', 'env-data-dashboard'),
                'description' => __('Classify waste from all 5 categories', 'env-data-dashboard'),
                'icon' => '⚡',
                'category' => 'diversity',
                'points' => 250,
                'difficulty' => 'hard'
            ),
            'confident_classifier' => array(
                'name' => __('Confident Classifier', 'env-data-dashboard'),
                'description' => __('Maintain 80% average confidence score', 'env-data-dashboard'),
                'icon' => '🎯',
                'category' => 'precision',
                'points' => 200,
                'difficulty' => 'medium'
            ),
            'precision_master' => array(
                'name' => __('Precision Master', 'env-data-dashboard'),
                'description' => __('Maintain 90% average confidence score', 'env-data-dashboard'),
                'icon' => '🔬',
                'category' => 'precision',
                'points' => 400,
                'difficulty' => 'hard'
            ),
            'streak_3' => array(
                'name' => __('3-Day Streak', 'env-data-dashboard'),
                'description' => __('Classify waste for 3 consecutive days', 'env-data-dashboard'),
                'icon' => '🔥',
                'category' => 'consistency',
                'points' => 100,
                'difficulty' => 'easy'
            ),
            'streak_7' => array(
                'name' => __('Week Warrior', 'env-data-dashboard'),
                'description' => __('Classify waste for 7 consecutive days', 'env-data-dashboard'),
                'icon' => '📅',
                'category' => 'consistency',
                'points' => 200,
                'difficulty' => 'medium'
            ),
            'streak_14' => array(
                'name' => __('Two Week Champion', 'env-data-dashboard'),
                'description' => __('Classify waste for 14 consecutive days', 'env-data-dashboard'),
                'icon' => '🏅',
                'category' => 'consistency',
                'points' => 400,
                'difficulty' => 'hard'
            ),
            'streak_30' => array(
                'name' => __('Monthly Master', 'env-data-dashboard'),
                'description' => __('Classify waste for 30 consecutive days', 'env-data-dashboard'),
                'icon' => '💎',
                'category' => 'consistency',
                'points' => 800,
                'difficulty' => 'legendary'
            ),
            'streak_100' => array(
                'name' => __('Centurion', 'env-data-dashboard'),
                'description' => __('Classify waste for 100 consecutive days', 'env-data-dashboard'),
                'icon' => '🌟',
                'category' => 'consistency',
                'points' => 2000,
                'difficulty' => 'mythical'
            ),
            'early_bird' => array(
                'name' => __('Early Bird', 'env-data-dashboard'),
                'description' => __('Complete 5 classifications before 8 AM', 'env-data-dashboard'),
                'icon' => '🌅',
                'category' => 'timing',
                'points' => 150,
                'difficulty' => 'medium'
            ),
            'night_owl' => array(
                'name' => __('Night Owl', 'env-data-dashboard'),
                'description' => __('Complete 5 classifications after 10 PM', 'env-data-dashboard'),
                'icon' => '🦉',
                'category' => 'timing',
                'points' => 150,
                'difficulty' => 'medium'
            ),
            'weekend_warrior' => array(
                'name' => __('Weekend Warrior', 'env-data-dashboard'),
                'description' => __('Complete 10 classifications on weekends', 'env-data-dashboard'),
                'icon' => '🏖️',
                'category' => 'timing',
                'points' => 300,
                'difficulty' => 'medium'
            ),
            'feedback_provider' => array(
                'name' => __('Feedback Provider', 'env-data-dashboard'),
                'description' => __('Provide feedback on 5 classifications', 'env-data-dashboard'),
                'icon' => '💬',
                'category' => 'community',
                'points' => 100,
                'difficulty' => 'easy'
            ),
            'quality_controller' => array(
                'name' => __('Quality Controller', 'env-data-dashboard'),
                'description' => __('Provide feedback on 20 classifications', 'env-data-dashboard'),
                'icon' => '🔍',
                'category' => 'community',
                'points' => 300,
                'difficulty' => 'medium'
            ),
            'accuracy_expert' => array(
                'name' => __('Accuracy Expert', 'env-data-dashboard'),
                'description' => __('Maintain 90% feedback accuracy with 10+ feedback submissions', 'env-data-dashboard'),
                'icon' => '🎯',
                'category' => 'community',
                'points' => 500,
                'difficulty' => 'hard'
            ),
            'helpful_reviewer' => array(
                'name' => __('Helpful Reviewer', 'env-data-dashboard'),
                'description' => __('Provide feedback on 50 classifications', 'env-data-dashboard'),
                'icon' => '⭐',
                'category' => 'community',
                'points' => 750,
                'difficulty' => 'hard'
            )
        );
    }
    
    /**
     * Get user achievements with progress
     */
    public function get_user_achievements_with_progress($user_id) {
        $all_achievements = $this->get_all_achievements();
        $user_achievements = get_user_meta($user_id, 'env_achievements', true) ?: array();
        
        global $wpdb;
        $classifications_table = $wpdb->prefix . 'env_ai_classifications';
        $feedback_table = $wpdb->prefix . 'env_ai_feedback';
        
        // Get current user stats for progress calculation
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(c.id) as total_classifications,
                COUNT(DISTINCT c.category) as unique_categories,
                AVG(c.confidence) as avg_confidence
             FROM {$classifications_table} c
             WHERE c.user_id = %d",
            $user_id
        ));
        
        $feedback_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_feedback,
                AVG(is_correct) as accuracy_rate
             FROM {$feedback_table} 
             WHERE user_id = %d",
            $user_id
        ));
        
        $streak = $this->get_user_classification_streak($user_id);
        
        $achievements_with_progress = array();
        
        foreach ($all_achievements as $key => $achievement) {
            $is_unlocked = in_array($key, $user_achievements);
            $progress = $this->calculate_achievement_progress($key, $stats, $feedback_stats, $streak);
            
            $achievements_with_progress[] = array_merge($achievement, array(
                'key' => $key,
                'unlocked' => $is_unlocked,
                'progress' => $progress['current'],
                'target' => $progress['target'],
                'progress_percentage' => $progress['target'] > 0 ? min(100, ($progress['current'] / $progress['target']) * 100) : 0
            ));
        }
        
        return $achievements_with_progress;
    }
    
    /**
     * Calculate progress toward specific achievement
     */
    private function calculate_achievement_progress($achievement_key, $stats, $feedback_stats, $streak) {
        $progress_map = array(
            'first_classification' => array('current' => $stats->total_classifications, 'target' => 1),
            'novice_classifier' => array('current' => $stats->total_classifications, 'target' => 10),
            'experienced_classifier' => array('current' => $stats->total_classifications, 'target' => 25),
            'expert_classifier' => array('current' => $stats->total_classifications, 'target' => 50),
            'master_classifier' => array('current' => $stats->total_classifications, 'target' => 100),
            'classification_legend' => array('current' => $stats->total_classifications, 'target' => 250),
            'category_explorer' => array('current' => $stats->unique_categories, 'target' => 3),
            'waste_specialist' => array('current' => $stats->unique_categories, 'target' => 5),
            'confident_classifier' => array('current' => $stats->avg_confidence * 100, 'target' => 80),
            'precision_master' => array('current' => $stats->avg_confidence * 100, 'target' => 90),
            'streak_3' => array('current' => $streak, 'target' => 3),
            'streak_7' => array('current' => $streak, 'target' => 7),
            'streak_14' => array('current' => $streak, 'target' => 14),
            'streak_30' => array('current' => $streak, 'target' => 30),
            'streak_100' => array('current' => $streak, 'target' => 100),
            'feedback_provider' => array('current' => $feedback_stats->total_feedback, 'target' => 5),
            'quality_controller' => array('current' => $feedback_stats->total_feedback, 'target' => 20),
            'accuracy_expert' => array('current' => min($feedback_stats->total_feedback, $feedback_stats->accuracy_rate * 100), 'target' => 10),
            'helpful_reviewer' => array('current' => $feedback_stats->total_feedback, 'target' => 50)
        );
        
        return $progress_map[$achievement_key] ?? array('current' => 0, 'target' => 1);
    }
    
    /**
     * Update challenge progress
     */
    public function update_challenge_progress($user_id, $challenge_type, $amount) {
        $current_progress = get_user_meta($user_id, 'env_challenge_' . $challenge_type, true) ?: 0;
        $new_progress = $current_progress + $amount;
        update_user_meta($user_id, 'env_challenge_' . $challenge_type, $new_progress);
        
        // Check if challenge is completed
        $this->check_challenge_completion($user_id, $challenge_type, $new_progress);
        
        return $new_progress;
    }
    
    /**
     * Check challenge completion
     */
    private function check_challenge_completion($user_id, $challenge_type, $progress) {
        $challenges = $this->get_active_challenges();
        
        foreach ($challenges as $challenge) {
            if ($challenge['type'] === $challenge_type && $progress >= $challenge['target']) {
                $this->complete_challenge($user_id, $challenge['id']);
            }
        }
    }
    
    /**
     * Get active challenges
     */
    public function get_active_challenges() {
        $today = current_time('Y-m-d');
        
        return array(
            array(
                'id' => 'daily_classification_5',
                'type' => 'daily_classifications',
                'name' => __('Daily Classifier', 'env-data-dashboard'),
                'description' => __('Complete 5 classifications today', 'env-data-dashboard'),
                'target' => 5,
                'points' => 25,
                'expires' => $today . ' 23:59:59'
            ),
            array(
                'id' => 'weekly_classification_25',
                'type' => 'weekly_classifications',
                'name' => __('Weekly Champion', 'env-data-dashboard'),
                'description' => __('Complete 25 classifications this week', 'env-data-dashboard'),
                'target' => 25,
                'points' => 150,
                'expires' => date('Y-m-d', strtotime('next Sunday')) . ' 23:59:59'
            )
        );
    }
    
    /**
     * Complete challenge
     */
    private function complete_challenge($user_id, $challenge_id) {
        $completed_challenges = get_user_meta($user_id, 'env_completed_challenges', true) ?: array();
        
        if (!in_array($challenge_id, $completed_challenges)) {
            $completed_challenges[] = $challenge_id;
            update_user_meta($user_id, 'env_completed_challenges', $completed_challenges);
            
            // Award challenge points
            $challenges = $this->get_active_challenges();
            foreach ($challenges as $challenge) {
                if ($challenge['id'] === $challenge_id) {
                    $this->add_user_points($user_id, $challenge['points'], 'challenge_' . $challenge_id);
                    break;
                }
            }
            
            // Trigger challenge completion notification
            do_action('env_challenge_completed', $user_id, $challenge_id);
        }
    }
    
    /**
     * Get leaderboard data
     */
    public function get_leaderboard($period = 'all-time', $limit = 10) {
        global $wpdb;
        
        $date_filter = '';
        switch ($period) {
            case 'daily':
                $date_filter = "AND DATE(p.created_at) = CURDATE()";
                break;
            case 'weekly':
                $date_filter = "AND p.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'monthly':
                $date_filter = "AND p.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
        }
        
        $points_table = $wpdb->prefix . 'env_gamification_points';
        $users_table = $wpdb->prefix . 'users';
        
        $query = "
            SELECT 
                u.ID as user_id,
                u.display_name,
                u.user_email,
                SUM(p.points) as total_points,
                COUNT(p.id) as activity_count
            FROM {$users_table} u
            INNER JOIN {$points_table} p ON u.ID = p.user_id
            WHERE 1=1 {$date_filter}
            GROUP BY u.ID
            ORDER BY total_points DESC, activity_count DESC
            LIMIT %d
        ";
        
        $results = $wpdb->get_results($wpdb->prepare($query, $limit));
        
        // Add rank and additional user data
        foreach ($results as $index => &$user) {
            $user->rank = $index + 1;
            $user->level = get_user_meta($user->user_id, 'env_user_level', true) ?: 1;
            $user->achievements_count = count(get_user_meta($user->user_id, 'env_achievements', true) ?: array());
            $user->avatar_url = get_avatar_url($user->user_id, array('size' => 50));
        }
        
        return $results;
    }
    
    /**
     * AJAX handler for getting user achievements
     */
    public function ajax_get_user_achievements() {
        check_ajax_referer('environmental_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('User not logged in', 'env-data-dashboard')));
        }
        
        $achievements = $this->get_user_achievements_with_progress($user_id);
        $user_level = get_user_meta($user_id, 'env_user_level', true) ?: 1;
        $total_points = get_user_meta($user_id, 'env_total_points', true) ?: 0;
        $next_level_points = $this->get_points_for_next_level($total_points);
        
        wp_send_json_success(array(
            'achievements' => $achievements,
            'user_level' => $user_level,
            'total_points' => $total_points,
            'next_level_points' => $next_level_points,
            'level_progress' => $next_level_points > 0 ? ($total_points / $next_level_points) * 100 : 100
        ));
    }
    
    /**
     * AJAX handler for getting leaderboard
     */
    public function ajax_get_leaderboard() {
        check_ajax_referer('environmental_dashboard_nonce', 'nonce');
        
        $period = sanitize_text_field($_POST['period'] ?? 'all-time');
        $limit = intval($_POST['limit'] ?? 10);
        
        $leaderboard = $this->get_leaderboard($period, $limit);
        
        wp_send_json_success(array('leaderboard' => $leaderboard));
    }
    
    /**
     * AJAX handler for getting user progress
     */
    public function ajax_get_user_progress() {
        check_ajax_referer('environmental_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('User not logged in', 'env-data-dashboard')));
        }
        
        $challenges = $this->get_active_challenges();
        $challenge_progress = array();
        
        foreach ($challenges as $challenge) {
            $progress = get_user_meta($user_id, 'env_challenge_' . $challenge['type'], true) ?: 0;
            $challenge_progress[] = array_merge($challenge, array(
                'progress' => $progress,
                'completed' => $progress >= $challenge['target']
            ));
        }
        
        $streak = $this->get_user_classification_streak($user_id);
        
        wp_send_json_success(array(
            'challenges' => $challenge_progress,
            'streak' => $streak
        ));
    }
    
    /**
     * Notify when achievement is unlocked
     */
    public function notify_achievement_unlocked($user_id, $achievement_key) {
        $achievements = $this->get_all_achievements();
        $achievement = $achievements[$achievement_key] ?? null;
        
        if ($achievement) {
            // Store notification for user
            $notifications = get_user_meta($user_id, 'env_notifications', true) ?: array();
            $notifications[] = array(
                'type' => 'achievement',
                'achievement' => $achievement,
                'timestamp' => current_time('mysql'),
                'read' => false
            );
            
            // Keep only last 20 notifications
            $notifications = array_slice($notifications, -20);
            update_user_meta($user_id, 'env_notifications', $notifications);
        }
    }
    
    /**
     * Reset daily challenges (cron job)
     */
    public function reset_daily_challenges() {
        global $wpdb;
        
        // Reset daily challenge progress for all users
        $wpdb->query("DELETE FROM {$wpdb->prefix}usermeta WHERE meta_key LIKE 'env_challenge_daily_%'");
        
        // Reset daily completed challenges
        $wpdb->query("UPDATE {$wpdb->prefix}usermeta SET meta_value = '' WHERE meta_key = 'env_completed_challenges'");
    }
    
    /**
     * Reset weekly challenges (cron job)
     */
    public function reset_weekly_challenges() {
        global $wpdb;
        
        // Reset weekly challenge progress for all users
        $wpdb->query("DELETE FROM {$wpdb->prefix}usermeta WHERE meta_key LIKE 'env_challenge_weekly_%'");
    }
    
    /**
     * User achievements shortcode
     */
    public function user_achievements_shortcode($atts) {
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id(),
            'show_progress' => 'true',
            'limit' => '12'
        ), $atts);
        
        if (!$atts['user_id']) {
            return '<p>' . __('Please log in to view achievements.', 'env-data-dashboard') . '</p>';
        }
        
        ob_start();
        $this->render_user_achievements($atts);
        return ob_get_clean();
    }
    
    /**
     * Leaderboard shortcode
     */
    public function leaderboard_shortcode($atts) {
        $atts = shortcode_atts(array(
            'period' => 'all-time',
            'limit' => '10',
            'show_avatars' => 'true'
        ), $atts);
        
        ob_start();
        $this->render_leaderboard($atts);
        return ob_get_clean();
    }
    
    /**
     * User progress shortcode
     */
    public function user_progress_shortcode($atts) {
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id(),
            'show_challenges' => 'true',
            'show_streak' => 'true'
        ), $atts);
        
        if (!$atts['user_id']) {
            return '<p>' . __('Please log in to view progress.', 'env-data-dashboard') . '</p>';
        }
        
        ob_start();
        $this->render_user_progress($atts);
        return ob_get_clean();
    }
    
    /**
     * Render user achievements
     */
    private function render_user_achievements($atts) {
        $user_id = intval($atts['user_id']);
        $show_progress = filter_var($atts['show_progress'], FILTER_VALIDATE_BOOLEAN);
        $limit = intval($atts['limit']);
        
        $achievements = $this->get_user_achievements_with_progress($user_id);
        $total_points = get_user_meta($user_id, 'env_total_points', true) ?: 0;
        $user_level = get_user_meta($user_id, 'env_user_level', true) ?: 1;
        
        ?>
        <div class="gamification-achievements">
            <div class="achievements-header">
                <h3><?php _e('Achievements', 'env-data-dashboard'); ?></h3>
                <div class="user-summary">
                    <span class="level-badge">Level <?php echo $user_level; ?></span>
                    <span class="points-total"><?php echo number_format($total_points); ?> pts</span>
                </div>
            </div>
            
            <div class="achievements-grid">
                <?php 
                $displayed = 0;
                foreach ($achievements as $achievement): 
                    if ($displayed >= $limit) break;
                    $displayed++;
                ?>
                    <div class="achievement-card <?php echo $achievement['unlocked'] ? 'unlocked' : 'locked'; ?>" data-difficulty="<?php echo esc_attr($achievement['difficulty']); ?>">
                        <div class="achievement-icon">
                            <?php echo $achievement['unlocked'] ? $achievement['icon'] : '🔒'; ?>
                        </div>
                        
                        <div class="achievement-info">
                            <h4 class="achievement-name"><?php echo esc_html($achievement['name']); ?></h4>
                            <p class="achievement-description"><?php echo esc_html($achievement['description']); ?></p>
                            
                            <?php if ($show_progress && !$achievement['unlocked']): ?>
                                <div class="achievement-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo min(100, $achievement['progress_percentage']); ?>%"></div>
                                    </div>
                                    <span class="progress-text">
                                        <?php printf('%d / %d', $achievement['progress'], $achievement['target']); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="achievement-meta">
                                <span class="achievement-points"><?php echo $achievement['points']; ?> pts</span>
                                <span class="achievement-category"><?php echo ucfirst($achievement['category']); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render leaderboard
     */
    private function render_leaderboard($atts) {
        $period = $atts['period'];
        $limit = intval($atts['limit']);
        $show_avatars = filter_var($atts['show_avatars'], FILTER_VALIDATE_BOOLEAN);
        
        $leaderboard = $this->get_leaderboard($period, $limit);
        
        ?>
        <div class="gamification-leaderboard">
            <div class="leaderboard-header">
                <h3><?php _e('Leaderboard', 'env-data-dashboard'); ?></h3>
                <div class="period-selector">
                    <button class="period-btn <?php echo $period === 'daily' ? 'active' : ''; ?>" data-period="daily">
                        <?php _e('Daily', 'env-data-dashboard'); ?>
                    </button>
                    <button class="period-btn <?php echo $period === 'weekly' ? 'active' : ''; ?>" data-period="weekly">
                        <?php _e('Weekly', 'env-data-dashboard'); ?>
                    </button>
                    <button class="period-btn <?php echo $period === 'monthly' ? 'active' : ''; ?>" data-period="monthly">
                        <?php _e('Monthly', 'env-data-dashboard'); ?>
                    </button>
                    <button class="period-btn <?php echo $period === 'all-time' ? 'active' : ''; ?>" data-period="all-time">
                        <?php _e('All Time', 'env-data-dashboard'); ?>
                    </button>
                </div>
            </div>
            
            <div class="leaderboard-list">
                <?php foreach ($leaderboard as $user): ?>
                    <div class="leaderboard-item rank-<?php echo $user->rank; ?>">
                        <div class="rank-badge">
                            <?php if ($user->rank <= 3): ?>
                                <span class="medal"><?php echo array('', '🥇', '🥈', '🥉')[$user->rank]; ?></span>
                            <?php else: ?>
                                <span class="rank-number"><?php echo $user->rank; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($show_avatars): ?>
                            <div class="user-avatar">
                                <img src="<?php echo esc_url($user->avatar_url); ?>" alt="<?php echo esc_attr($user->display_name); ?>">
                            </div>
                        <?php endif; ?>
                        
                        <div class="user-info">
                            <h4 class="user-name"><?php echo esc_html($user->display_name); ?></h4>
                            <div class="user-stats">
                                <span class="level">Level <?php echo $user->level; ?></span>
                                <span class="achievements"><?php echo $user->achievements_count; ?> achievements</span>
                            </div>
                        </div>
                        
                        <div class="user-points">
                            <span class="points-value"><?php echo number_format($user->total_points); ?></span>
                            <span class="points-label">pts</span>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($leaderboard)): ?>
                    <div class="no-data">
                        <p><?php _e('No leaderboard data available yet.', 'env-data-dashboard'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render user progress
     */
    private function render_user_progress($atts) {
        $user_id = intval($atts['user_id']);
        $show_challenges = filter_var($atts['show_challenges'], FILTER_VALIDATE_BOOLEAN);
        $show_streak = filter_var($atts['show_streak'], FILTER_VALIDATE_BOOLEAN);
        
        $challenges = $this->get_active_challenges();
        $streak = $this->get_user_classification_streak($user_id);
        $total_points = get_user_meta($user_id, 'env_total_points', true) ?: 0;
        $user_level = get_user_meta($user_id, 'env_user_level', true) ?: 1;
        $next_level_points = $this->get_points_for_next_level($total_points);
        
        ?>
        <div class="gamification-progress">
            <?php if ($show_streak): ?>
                <div class="streak-display">
                    <div class="streak-icon">🔥</div>
                    <div class="streak-info">
                        <h4><?php printf(__('%d Day Streak', 'env-data-dashboard'), $streak); ?></h4>
                        <p><?php _e('Keep classifying to maintain your streak!', 'env-data-dashboard'); ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="level-progress">
                <h4><?php printf(__('Level %d Progress', 'env-data-dashboard'), $user_level); ?></h4>
                <div class="level-bar">
                    <div class="level-fill" style="width: <?php echo $next_level_points > 0 ? min(100, ($total_points / $next_level_points) * 100) : 100; ?>%"></div>
                </div>
                <p class="level-text">
                    <?php printf(
                        __('%s / %s points to next level', 'env-data-dashboard'),
                        number_format($total_points),
                        number_format($next_level_points)
                    ); ?>
                </p>
            </div>
            
            <?php if ($show_challenges): ?>
                <div class="challenges-section">
                    <h4><?php _e('Active Challenges', 'env-data-dashboard'); ?></h4>
                    
                    <?php foreach ($challenges as $challenge): 
                        $progress = get_user_meta($user_id, 'env_challenge_' . $challenge['type'], true) ?: 0;
                        $completed = $progress >= $challenge['target'];
                    ?>
                        <div class="challenge-item <?php echo $completed ? 'completed' : ''; ?>">
                            <div class="challenge-info">
                                <h5><?php echo esc_html($challenge['name']); ?></h5>
                                <p><?php echo esc_html($challenge['description']); ?></p>
                            </div>
                            
                            <div class="challenge-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo min(100, ($progress / $challenge['target']) * 100); ?>%"></div>
                                </div>
                                <span class="progress-text">
                                    <?php printf('%d / %d', $progress, $challenge['target']); ?>
                                </span>
                            </div>
                            
                            <div class="challenge-reward">
                                <span class="reward-points"><?php echo $challenge['points']; ?> pts</span>
                                <?php if ($completed): ?>
                                    <span class="completed-badge">✓</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}

// Initialize the Gamification System
Gamification_System::get_instance();
