<?php
/**
 * Loyalty Program Class
 * 
 * Manages user loyalty tiers, progression, and tier-based benefits
 * 
 * @package Environmental_Voucher_Rewards
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EVR_Loyalty_Program {
    
    private static $instance = null;
    private $db_manager;
    
    /**
     * Loyalty tiers configuration
     */
    private $loyalty_tiers = array(
        'bronze' => array(
            'name' => 'Bronze Member',
            'min_points' => 0,
            'benefits' => array('basic_vouchers', 'monthly_newsletter'),
            'voucher_bonus' => 0,
            'color' => '#CD7F32'
        ),
        'silver' => array(
            'name' => 'Silver Member',
            'min_points' => 1000,
            'benefits' => array('priority_support', 'exclusive_discounts', 'early_access'),
            'voucher_bonus' => 10,
            'color' => '#C0C0C0'
        ),
        'gold' => array(
            'name' => 'Gold Member',
            'min_points' => 5000,
            'benefits' => array('premium_vouchers', 'birthday_rewards', 'referral_bonuses'),
            'voucher_bonus' => 25,
            'color' => '#FFD700'
        ),
        'platinum' => array(
            'name' => 'Platinum Member',
            'min_points' => 15000,
            'benefits' => array('vip_experiences', 'custom_rewards', 'direct_support'),
            'voucher_bonus' => 50,
            'color' => '#E5E4E2'
        ),
        'diamond' => array(
            'name' => 'Diamond Elite',
            'min_points' => 50000,
            'benefits' => array('unlimited_vouchers', 'exclusive_events', 'personal_advisor'),
            'voucher_bonus' => 100,
            'color' => '#B9F2FF'
        )
    );
    
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
        // User progression hooks
        add_action('evr_points_awarded', array($this, 'check_tier_progression'), 10, 2);
        add_action('evr_voucher_redeemed', array($this, 'process_tier_benefits'), 10, 2);
        
        // AJAX handlers
        add_action('wp_ajax_evr_get_loyalty_status', array($this, 'ajax_get_loyalty_status'));
        add_action('wp_ajax_evr_claim_tier_reward', array($this, 'ajax_claim_tier_reward'));
    }
    
    /**
     * Get user's current loyalty tier
     */
    public function get_user_tier($user_id) {
        $total_points = $this->get_user_lifetime_points($user_id);
        
        $current_tier = 'bronze';
        foreach ($this->loyalty_tiers as $tier => $config) {
            if ($total_points >= $config['min_points']) {
                $current_tier = $tier;
            } else {
                break;
            }
        }
        
        return $current_tier;
    }
    
    /**
     * Get user's lifetime points
     */
    public function get_user_lifetime_points($user_id) {
        global $wpdb;
        
        $lifetime_points = $wpdb->get_var($wpdb->prepare("
            SELECT COALESCE(SUM(points), 0) 
            FROM {$wpdb->prefix}reward_transactions 
            WHERE user_id = %d AND transaction_type IN ('earned', 'bonus')
        ", $user_id));
        
        return intval($lifetime_points);
    }
    
    /**
     * Check and process tier progression
     */
    public function check_tier_progression($user_id, $points_awarded) {
        $previous_tier = get_user_meta($user_id, 'loyalty_tier', true) ?: 'bronze';
        $current_tier = $this->get_user_tier($user_id);
        
        if ($previous_tier !== $current_tier) {
            update_user_meta($user_id, 'loyalty_tier', $current_tier);
            update_user_meta($user_id, 'tier_promotion_date', current_time('mysql'));
            
            $this->award_tier_progression_bonus($user_id, $current_tier);
            $this->send_tier_progression_notification($user_id, $previous_tier, $current_tier);
            
            do_action('evr_tier_progression', $user_id, $previous_tier, $current_tier);
        }
    }
    
    /**
     * Award tier progression bonus
     */
    private function award_tier_progression_bonus($user_id, $new_tier) {
        $tier_config = $this->loyalty_tiers[$new_tier];
        $bonus_points = $tier_config['min_points'] * 0.1;
        
        if ($bonus_points > 0) {
            $current_points = get_user_meta($user_id, 'green_points', true) ?: 0;
            update_user_meta($user_id, 'green_points', $current_points + $bonus_points);
            
            $this->db_manager->add_reward_transaction(
                $user_id,
                'bonus',
                $bonus_points,
                sprintf('Tier progression bonus - Welcome to %s!', $tier_config['name']),
                null
            );
        }
        
        $this->award_tier_welcome_vouchers($user_id, $new_tier);
    }
    
    /**
     * Award tier-specific welcome vouchers
     */
    private function award_tier_welcome_vouchers($user_id, $tier) {
        $voucher_manager = EVR_Voucher_Manager::get_instance();
        
        $voucher_configs = array(
            'silver' => array('value' => 15, 'expiry' => 30),
            'gold' => array('value' => 25, 'expiry' => 60),
            'platinum' => array('value' => 40, 'expiry' => 90),
            'diamond' => array('value' => 60, 'expiry' => 365)
        );
        
        if (isset($voucher_configs[$tier])) {
            $config = $voucher_configs[$tier];
            $voucher_manager->create_voucher(array(
                'voucher_name' => sprintf('%s Member Welcome Discount', $this->loyalty_tiers[$tier]['name']),
                'description' => sprintf('Congratulations on reaching %s tier!', $this->loyalty_tiers[$tier]['name']),
                'discount_type' => 'percentage',
                'discount_value' => $config['value'],
                'valid_from' => current_time('mysql'),
                'valid_until' => date('Y-m-d H:i:s', strtotime('+' . $config['expiry'] . ' days')),
                'usage_limit_per_user' => 1
            ));
        }
    }
    
    /**
     * Send tier progression notification
     */
    private function send_tier_progression_notification($user_id, $old_tier, $new_tier) {
        $user = get_user_by('id', $user_id);
        $tier_config = $this->loyalty_tiers[$new_tier];
        
        $subject = sprintf('Congratulations! You\'re now a %s!', $tier_config['name']);
        $message = sprintf(
            'Dear %s,\n\nYou have been promoted to %s tier! Enjoy your new benefits.\n\nBest regards,\nEnvironmental Platform Team',
            $user->display_name,
            $tier_config['name']
        );
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * Get user loyalty dashboard data
     */
    public function get_user_loyalty_dashboard($user_id) {
        $current_tier = $this->get_user_tier($user_id);
        $tier_config = $this->loyalty_tiers[$current_tier];
        $lifetime_points = $this->get_user_lifetime_points($user_id);
        $current_points = get_user_meta($user_id, 'green_points', true) ?: 0;
        
        $next_tier = $this->get_next_tier($current_tier);
        $next_tier_config = $next_tier ? $this->loyalty_tiers[$next_tier] : null;
        
        $progress_data = array();
        if ($next_tier_config) {
            $points_needed = $next_tier_config['min_points'] - $lifetime_points;
            $progress_percentage = min(($lifetime_points / $next_tier_config['min_points']) * 100, 100);
            
            $progress_data = array(
                'next_tier' => $next_tier,
                'next_tier_name' => $next_tier_config['name'],
                'points_needed' => max($points_needed, 0),
                'progress_percentage' => $progress_percentage
            );
        }
        
        return array(
            'current_tier' => $current_tier,
            'tier_name' => $tier_config['name'],
            'tier_color' => $tier_config['color'],
            'benefits' => $tier_config['benefits'],
            'voucher_bonus' => $tier_config['voucher_bonus'],
            'lifetime_points' => $lifetime_points,
            'current_points' => $current_points,
            'progress' => $progress_data
        );
    }
    
    /**
     * Get next tier in progression
     */
    private function get_next_tier($current_tier) {
        $tiers = array_keys($this->loyalty_tiers);
        $current_index = array_search($current_tier, $tiers);
        
        return isset($tiers[$current_index + 1]) ? $tiers[$current_index + 1] : null;
    }
    
    /**
     * AJAX: Get loyalty status
     */
    public function ajax_get_loyalty_status() {
        check_ajax_referer('evr_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        $loyalty_data = $this->get_user_loyalty_dashboard($user_id);
        wp_send_json_success($loyalty_data);
    }
    
    /**
     * AJAX: Claim tier reward
     */
    public function ajax_claim_tier_reward() {
        check_ajax_referer('evr_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $reward_type = sanitize_text_field($_POST['reward_type'] ?? '');
        
        if (!$user_id || !$reward_type) {
            wp_send_json_error('Invalid request');
        }
        
        $result = $this->claim_tier_reward($user_id, $reward_type);
        
        if ($result) {
            wp_send_json_success('Reward claimed successfully');
        } else {
            wp_send_json_error('Failed to claim reward');
        }
    }
    
    /**
     * Claim tier-specific reward
     */
    public function claim_tier_reward($user_id, $reward_type) {
        $user_tier = $this->get_user_tier($user_id);
        $tier_config = $this->loyalty_tiers[$user_tier];
        
        if (!in_array($reward_type, $tier_config['benefits'])) {
            return false;
        }
        
        $last_claim = get_user_meta($user_id, "last_{$reward_type}_claim", true);
        if ($last_claim && date('Y-m', strtotime($last_claim)) === current_time('Y-m')) {
            return false;
        }
        
        update_user_meta($user_id, "last_{$reward_type}_claim", current_time('mysql'));
        return true;
    }
    
    /**
     * Get all loyalty tiers configuration
     */
    public function get_loyalty_tiers() {
        return $this->loyalty_tiers;
    }
}
