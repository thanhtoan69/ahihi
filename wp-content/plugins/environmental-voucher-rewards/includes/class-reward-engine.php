<?php
/**
 * Reward Engine Class
 * 
 * Handles automated reward distribution and loyalty programs
 * 
 * @package Environmental_Voucher_Rewards
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EVR_Reward_Engine {
    
    private static $instance = null;
    private $db_manager;
    
    // Reward types
    const REWARD_QUIZ_COMPLETION = 'quiz_completion';
    const REWARD_WASTE_CLASSIFICATION = 'waste_classification';
    const REWARD_CARBON_SAVING = 'carbon_saving';
    const REWARD_DAILY_LOGIN = 'daily_login';
    const REWARD_REFERRAL = 'referral';
    const REWARD_MILESTONE = 'milestone';
    const REWARD_CHALLENGE_COMPLETION = 'challenge_completion';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->db_manager = EVR_Database_Manager::get_instance();
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Environmental action hooks
        add_action('environmental_quiz_completed', array($this, 'handle_quiz_reward'), 10, 3);
        add_action('environmental_waste_classified', array($this, 'handle_waste_reward'), 10, 3);
        add_action('environmental_carbon_saved', array($this, 'handle_carbon_reward'), 10, 3);
        add_action('environmental_challenge_completed', array($this, 'handle_challenge_reward'), 10, 3);
        
        // User engagement hooks
        add_action('wp_login', array($this, 'handle_daily_login_reward'), 10, 2);
        add_action('user_register', array($this, 'handle_registration_reward'), 10, 1);
        
        // Milestone hooks
        add_action('evr_check_milestones', array($this, 'check_user_milestones'), 10, 1);
        
        // AJAX handlers
        add_action('wp_ajax_evr_claim_reward', array($this, 'ajax_claim_reward'));
        add_action('wp_ajax_evr_get_available_rewards', array($this, 'ajax_get_available_rewards'));
    }
    
    /**
     * Award points to user
     */
    public function award_points($user_id, $points, $reason, $reference_id = null) {
        // Get current user points
        $current_points = get_user_meta($user_id, 'green_points', true) ?: 0;
        $new_total = $current_points + $points;
        
        // Update user points
        update_user_meta($user_id, 'green_points', $new_total);
        
        // Record transaction
        $this->db_manager->add_reward_transaction(
            $user_id,
            'earned',
            $points,
            $reason,
            $reference_id
        );
        
        // Check for milestones
        $this->check_user_milestones($user_id);
        
        // Trigger action
        do_action('evr_points_awarded', $user_id, $points, $new_total, $reason);
        
        return $new_total;
    }
    
    /**
     * Handle quiz completion reward
     */
    public function handle_quiz_reward($user_id, $quiz_id, $score) {
        $reward_config = $this->get_reward_config(self::REWARD_QUIZ_COMPLETION);
        
        if (!$reward_config['enabled']) {
            return;
        }
        
        // Calculate points based on score
        $base_points = $reward_config['base_points'];
        $bonus_multiplier = $score >= 80 ? 1.5 : ($score >= 60 ? 1.2 : 1.0);
        $points = round($base_points * $bonus_multiplier);
        
        $reason = sprintf('Quiz completed with %d%% score', $score);
        $this->award_points($user_id, $points, $reason, $quiz_id);
        
        // Check for perfect score bonus
        if ($score >= 100 && $reward_config['perfect_score_bonus']) {
            $this->award_points($user_id, $reward_config['perfect_score_bonus'], 'Perfect quiz score bonus', $quiz_id);
        }
        
        // Generate voucher for high scores
        if ($score >= 90 && $reward_config['voucher_threshold']) {
            $this->generate_achievement_voucher($user_id, 'quiz_master', array(
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'valid_days' => 30
            ));
        }
    }
    
    /**
     * Handle waste classification reward
     */
    public function handle_waste_reward($user_id, $waste_type, $environmental_impact) {
        $reward_config = $this->get_reward_config(self::REWARD_WASTE_CLASSIFICATION);
        
        if (!$reward_config['enabled']) {
            return;
        }
        
        // Points based on environmental impact
        $impact_multiplier = array(
            'high' => 3.0,
            'medium' => 2.0,
            'low' => 1.0
        );
        
        $multiplier = $impact_multiplier[$environmental_impact] ?? 1.0;
        $points = round($reward_config['base_points'] * $multiplier);
        
        $reason = sprintf('Waste classified: %s (Impact: %s)', $waste_type, $environmental_impact);
        $this->award_points($user_id, $points, $reason);
        
        // Daily classification bonus
        $today_classifications = $this->get_daily_activity_count($user_id, 'waste_classification');
        if ($today_classifications >= 5) {
            $this->award_points($user_id, 50, 'Daily waste classification bonus');
        }
    }
    
    /**
     * Handle carbon saving reward
     */
    public function handle_carbon_reward($user_id, $carbon_saved, $activity_type) {
        $reward_config = $this->get_reward_config(self::REWARD_CARBON_SAVING);
        
        if (!$reward_config['enabled']) {
            return;
        }
        
        // Points per kg of CO2 saved
        $points_per_kg = $reward_config['points_per_kg'] ?? 10;
        $points = round($carbon_saved * $points_per_kg);
        
        $reason = sprintf('Carbon saved: %.2f kg CO2 (%s)', $carbon_saved, $activity_type);
        $this->award_points($user_id, $points, $reason);
        
        // Update user's total carbon saved
        $total_saved = get_user_meta($user_id, 'total_carbon_saved', true) ?: 0;
        update_user_meta($user_id, 'total_carbon_saved', $total_saved + $carbon_saved);
    }
    
    /**
     * Handle challenge completion reward
     */
    public function handle_challenge_reward($user_id, $challenge_id, $completion_data) {
        $reward_config = $this->get_reward_config(self::REWARD_CHALLENGE_COMPLETION);
        
        if (!$reward_config['enabled']) {
            return;
        }
        
        // Get challenge details
        global $wpdb;
        $challenge = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}environmental_challenges 
            WHERE challenge_id = %d
        ", $challenge_id));
        
        if (!$challenge) {
            return;
        }
        
        // Parse challenge rewards
        $rewards = json_decode($challenge->rewards, true);
        
        if (isset($rewards['points'])) {
            $this->award_points($user_id, $rewards['points'], 
                sprintf('Challenge completed: %s', $challenge->challenge_name), $challenge_id);
        }
        
        if (isset($rewards['voucher'])) {
            $this->generate_achievement_voucher($user_id, 'challenge_completion', $rewards['voucher']);
        }
        
        if (isset($rewards['badge'])) {
            $this->award_badge($user_id, $rewards['badge']);
        }
    }
    
    /**
     * Handle daily login reward
     */
    public function handle_daily_login_reward($user_login, $user) {
        $user_id = $user->ID;
        $reward_config = $this->get_reward_config(self::REWARD_DAILY_LOGIN);
        
        if (!$reward_config['enabled']) {
            return;
        }
        
        // Check if already rewarded today
        $last_login_reward = get_user_meta($user_id, 'last_login_reward_date', true);
        $today = date('Y-m-d');
        
        if ($last_login_reward === $today) {
            return;
        }
        
        // Award daily login points
        $points = $reward_config['base_points'];
        $this->award_points($user_id, $points, 'Daily login bonus');
        
        // Update last reward date
        update_user_meta($user_id, 'last_login_reward_date', $today);
        
        // Check for consecutive login streak
        $this->check_login_streak($user_id);
    }
    
    /**
     * Check login streak
     */
    private function check_login_streak($user_id) {
        $streak = get_user_meta($user_id, 'login_streak', true) ?: 0;
        $last_login = get_user_meta($user_id, 'last_login_date', true);
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        if ($last_login === $yesterday) {
            // Continue streak
            $streak++;
        } elseif ($last_login !== $today) {
            // Reset streak
            $streak = 1;
        }
        
        update_user_meta($user_id, 'login_streak', $streak);
        update_user_meta($user_id, 'last_login_date', $today);
        
        // Streak rewards
        $streak_rewards = array(
            7 => array('points' => 100, 'message' => '7-day login streak!'),
            30 => array('points' => 500, 'message' => '30-day login streak!'),
            100 => array('points' => 2000, 'message' => '100-day login streak!')
        );
        
        if (isset($streak_rewards[$streak])) {
            $reward = $streak_rewards[$streak];
            $this->award_points($user_id, $reward['points'], $reward['message']);
            
            // Generate special voucher for long streaks
            if ($streak >= 30) {
                $this->generate_achievement_voucher($user_id, 'loyalty_streak', array(
                    'discount_type' => 'percentage',
                    'discount_value' => 15,
                    'valid_days' => 60
                ));
            }
        }
    }
    
    /**
     * Check user milestones
     */
    public function check_user_milestones($user_id) {
        $current_points = get_user_meta($user_id, 'green_points', true) ?: 0;
        $achieved_milestones = get_user_meta($user_id, 'achieved_milestones', true) ?: array();
        
        $milestones = array(
            100 => array('badge' => 'Eco Beginner', 'voucher' => array('discount_value' => 5)),
            500 => array('badge' => 'Green Warrior', 'voucher' => array('discount_value' => 10)),
            1000 => array('badge' => 'Eco Champion', 'voucher' => array('discount_value' => 15)),
            2500 => array('badge' => 'Environmental Hero', 'voucher' => array('discount_value' => 20)),
            5000 => array('badge' => 'Planet Protector', 'voucher' => array('discount_value' => 25))
        );
        
        foreach ($milestones as $points_required => $rewards) {
            if ($current_points >= $points_required && !in_array($points_required, $achieved_milestones)) {
                // Award milestone
                $this->award_badge($user_id, $rewards['badge']);
                
                if (isset($rewards['voucher'])) {
                    $this->generate_achievement_voucher($user_id, 'milestone', $rewards['voucher']);
                }
                
                $achieved_milestones[] = $points_required;
                update_user_meta($user_id, 'achieved_milestones', $achieved_milestones);
                
                // Trigger action
                do_action('evr_milestone_achieved', $user_id, $points_required, $rewards);
            }
        }
    }
    
    /**
     * Generate achievement voucher
     */
    public function generate_achievement_voucher($user_id, $achievement_type, $voucher_config) {
        $voucher_manager = EVR_Voucher_Manager::get_instance();
        
        $defaults = array(
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'valid_days' => 30,
            'min_order_amount' => 50000
        );
        
        $config = wp_parse_args($voucher_config, $defaults);
        
        $voucher_data = array(
            'voucher_name' => sprintf('Achievement Reward - %s', ucwords(str_replace('_', ' ', $achievement_type))),
            'description' => sprintf('Congratulations on your %s achievement!', $achievement_type),
            'discount_type' => $config['discount_type'],
            'discount_value' => $config['discount_value'],
            'min_order_amount' => $config['min_order_amount'],
            'valid_from' => current_time('mysql'),
            'valid_until' => date('Y-m-d H:i:s', strtotime('+' . $config['valid_days'] . ' days')),
            'usage_limit_per_user' => 1,
            'auto_apply' => false
        );
        
        $voucher_id = $voucher_manager->create_voucher($voucher_data);
        
        if (!is_wp_error($voucher_id)) {
            // Grant voucher to user
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix . 'user_voucher_claims',
                array(
                    'user_id' => $user_id,
                    'voucher_id' => $voucher_id,
                    'claim_source' => 'achievement',
                    'expires_at' => $voucher_data['valid_until'],
                    'claimed_at' => current_time('mysql')
                )
            );
        }
        
        return $voucher_id;
    }
    
    /**
     * Award badge to user
     */
    public function award_badge($user_id, $badge_name) {
        $user_badges = get_user_meta($user_id, 'user_badges', true) ?: array();
        
        if (!in_array($badge_name, $user_badges)) {
            $user_badges[] = $badge_name;
            update_user_meta($user_id, 'user_badges', $user_badges);
            
            // Trigger action
            do_action('evr_badge_awarded', $user_id, $badge_name);
        }
    }
    
    /**
     * Get reward configuration
     */
    private function get_reward_config($reward_type) {
        $defaults = array(
            self::REWARD_QUIZ_COMPLETION => array(
                'enabled' => true,
                'base_points' => 50,
                'perfect_score_bonus' => 25,
                'voucher_threshold' => 90
            ),
            self::REWARD_WASTE_CLASSIFICATION => array(
                'enabled' => true,
                'base_points' => 20
            ),
            self::REWARD_CARBON_SAVING => array(
                'enabled' => true,
                'points_per_kg' => 10
            ),
            self::REWARD_DAILY_LOGIN => array(
                'enabled' => true,
                'base_points' => 10
            ),
            self::REWARD_CHALLENGE_COMPLETION => array(
                'enabled' => true
            )
        );
        
        $config = get_option('evr_reward_config', array());
        
        return wp_parse_args(
            $config[$reward_type] ?? array(),
            $defaults[$reward_type] ?? array()
        );
    }
    
    /**
     * Get daily activity count
     */
    private function get_daily_activity_count($user_id, $activity_type) {
        global $wpdb;
        
        $today = date('Y-m-d');
        
        return $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}reward_transactions
            WHERE user_id = %d 
            AND description LIKE %s
            AND DATE(transaction_date) = %s
        ", $user_id, $activity_type . '%', $today));
    }
    
    /**
     * AJAX: Claim reward
     */
    public function ajax_claim_reward() {
        check_ajax_referer('evr_nonce', 'nonce');
        
        $reward_id = intval($_POST['reward_id'] ?? 0);
        $user_id = get_current_user_id();
        
        if (!$reward_id || !$user_id) {
            wp_send_json_error('Invalid reward or user');
        }
        
        // Process reward claim
        $result = $this->claim_reward($user_id, $reward_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX: Get available rewards
     */
    public function ajax_get_available_rewards() {
        check_ajax_referer('evr_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        $rewards = $this->get_available_rewards($user_id);
        wp_send_json_success($rewards);
    }
    
    /**
     * Get available rewards for user
     */
    public function get_available_rewards($user_id) {
        return $this->db_manager->get_user_rewards($user_id, 'active');
    }
    
    /**
     * Claim reward
     */
    public function claim_reward($user_id, $reward_id) {
        // Implementation for claiming specific rewards
        // This would depend on the reward type and requirements
        
        return array(
            'success' => true,
            'message' => 'Reward claimed successfully'
        );
    }
    
    /**
     * Get user reward summary
     */
    public function get_user_reward_summary($user_id) {
        $points = get_user_meta($user_id, 'green_points', true) ?: 0;
        $badges = get_user_meta($user_id, 'user_badges', true) ?: array();
        $streak = get_user_meta($user_id, 'login_streak', true) ?: 0;
        $carbon_saved = get_user_meta($user_id, 'total_carbon_saved', true) ?: 0;
        
        return array(
            'points' => $points,
            'badges' => $badges,
            'login_streak' => $streak,
            'carbon_saved' => $carbon_saved,
            'available_rewards' => $this->get_available_rewards($user_id)
        );
    }
}
