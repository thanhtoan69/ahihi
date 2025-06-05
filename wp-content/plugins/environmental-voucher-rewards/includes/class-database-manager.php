<?php
/**
 * Database Manager Class
 * 
 * Handles all database operations for vouchers and rewards
 * 
 * @package Environmental_Voucher_Rewards
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EVR_Database_Manager {
    
    private static $instance = null;
    private $wpdb;
    
    // Table names
    private $voucher_campaigns_table;
    private $vouchers_table;
    private $voucher_usage_table;
    private $reward_programs_table;
    private $user_rewards_table;
    private $partner_discounts_table;
    private $reward_transactions_table;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        // Initialize table names
        $this->voucher_campaigns_table = $wpdb->prefix . 'voucher_campaigns';
        $this->vouchers_table = $wpdb->prefix . 'vouchers';
        $this->voucher_usage_table = $wpdb->prefix . 'voucher_usage';
        $this->reward_programs_table = $wpdb->prefix . 'reward_programs';
        $this->user_rewards_table = $wpdb->prefix . 'user_rewards';
        $this->partner_discounts_table = $wpdb->prefix . 'partner_discounts';
        $this->reward_transactions_table = $wpdb->prefix . 'reward_transactions';
    }
    
    /**
     * Get active vouchers for user
     */
    public function get_user_vouchers($user_id, $status = 'active') {
        $sql = $this->wpdb->prepare("
            SELECT v.*, vc.campaign_name, uvc.claimed_at, uvc.expires_at
            FROM {$this->vouchers_table} v
            LEFT JOIN {$this->voucher_campaigns_table} vc ON v.campaign_id = vc.campaign_id
            LEFT JOIN user_voucher_claims uvc ON v.voucher_id = uvc.voucher_id AND uvc.user_id = %d
            WHERE v.voucher_status = %s 
            AND v.valid_from <= NOW() 
            AND v.valid_until >= NOW()
            AND (uvc.user_id IS NOT NULL OR v.auto_apply = 1)
            ORDER BY v.discount_value DESC
        ", $user_id, $status);
        
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Get voucher by code
     */
    public function get_voucher_by_code($code) {
        $sql = $this->wpdb->prepare("
            SELECT * FROM {$this->vouchers_table}
            WHERE voucher_code = %s
            AND voucher_status = 'active'
            AND valid_from <= NOW()
            AND valid_until >= NOW()
        ", $code);
        
        return $this->wpdb->get_row($sql);
    }
    
    /**
     * Check voucher eligibility for user
     */
    public function check_voucher_eligibility($voucher_id, $user_id, $cart_total = 0) {
        $voucher = $this->wpdb->get_row($this->wpdb->prepare("
            SELECT * FROM {$this->vouchers_table} WHERE voucher_id = %d
        ", $voucher_id));
        
        if (!$voucher) {
            return array('eligible' => false, 'message' => 'Voucher not found');
        }
        
        // Check basic status
        if ($voucher->voucher_status !== 'active') {
            return array('eligible' => false, 'message' => 'Voucher is not active');
        }
        
        // Check date validity
        if (strtotime($voucher->valid_from) > time() || strtotime($voucher->valid_until) < time()) {
            return array('eligible' => false, 'message' => 'Voucher is expired or not yet valid');
        }
        
        // Check minimum order amount
        if ($cart_total < $voucher->min_order_amount) {
            return array(
                'eligible' => false, 
                'message' => sprintf('Minimum order amount is %s', number_format($voucher->min_order_amount))
            );
        }
        
        // Check usage limits
        if ($voucher->total_usage_limit && $voucher->current_usage >= $voucher->total_usage_limit) {
            return array('eligible' => false, 'message' => 'Voucher usage limit exceeded');
        }
        
        // Check user-specific eligibility
        $user_usage = $this->wpdb->get_var($this->wpdb->prepare("
            SELECT COUNT(*) FROM {$this->voucher_usage_table}
            WHERE voucher_id = %d AND user_id = %d AND usage_type = 'used'
        ", $voucher_id, $user_id));
        
        if ($voucher->usage_limit_per_user && $user_usage >= $voucher->usage_limit_per_user) {
            return array('eligible' => false, 'message' => 'Personal usage limit exceeded');
        }
        
        // Check eco score requirement
        if ($voucher->eco_score_requirement > 0) {
            $user_eco_score = get_user_meta($user_id, 'eco_score', true) ?: 0;
            if ($user_eco_score < $voucher->eco_score_requirement) {
                return array(
                    'eligible' => false, 
                    'message' => sprintf('Requires eco score of %d or higher', $voucher->eco_score_requirement)
                );
            }
        }
        
        return array('eligible' => true, 'message' => 'Voucher is eligible');
    }
    
    /**
     * Apply voucher usage
     */
    public function apply_voucher_usage($voucher_id, $user_id, $order_id, $discount_amount, $original_total) {
        $result = $this->wpdb->insert(
            $this->voucher_usage_table,
            array(
                'voucher_id' => $voucher_id,
                'user_id' => $user_id,
                'order_id' => $order_id,
                'usage_type' => 'used',
                'discount_amount' => $discount_amount,
                'original_order_amount' => $original_total,
                'final_order_amount' => $original_total - $discount_amount,
                'used_at' => current_time('mysql'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ),
            array('%d', '%d', '%d', '%s', '%f', '%f', '%f', '%s', '%s', '%s')
        );
        
        if ($result) {
            // Update voucher usage count
            $this->wpdb->query($this->wpdb->prepare("
                UPDATE {$this->vouchers_table} 
                SET current_usage = current_usage + 1 
                WHERE voucher_id = %d
            ", $voucher_id));
        }
        
        return $result;
    }
    
    /**
     * Get user rewards
     */
    public function get_user_rewards($user_id, $status = 'active') {
        $sql = $this->wpdb->prepare("
            SELECT ur.*, rp.program_name, rp.tier_benefits
            FROM {$this->user_rewards_table} ur
            LEFT JOIN {$this->reward_programs_table} rp ON ur.program_id = rp.program_id
            WHERE ur.user_id = %d AND ur.status = %s
            ORDER BY ur.earned_at DESC
        ", $user_id, $status);
        
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Add reward transaction
     */
    public function add_reward_transaction($user_id, $transaction_type, $points, $description, $reference_id = null) {
        return $this->wpdb->insert(
            $this->reward_transactions_table,
            array(
                'user_id' => $user_id,
                'transaction_type' => $transaction_type,
                'points' => $points,
                'description' => $description,
                'reference_id' => $reference_id,
                'transaction_date' => current_time('mysql')
            ),
            array('%d', '%s', '%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get partner discounts
     */
    public function get_active_partner_discounts($user_id = null) {
        $sql = "
            SELECT pd.*, p.partner_name, p.partner_logo
            FROM {$this->partner_discounts_table} pd
            LEFT JOIN partners p ON pd.partner_id = p.partner_id
            WHERE pd.status = 'active'
            AND pd.valid_from <= NOW()
            AND pd.valid_until >= NOW()
        ";
        
        if ($user_id) {
            $sql .= $this->wpdb->prepare(" AND pd.min_user_level <= (
                SELECT user_level FROM users WHERE user_id = %d
            )", $user_id);
        }
        
        $sql .= " ORDER BY pd.discount_percentage DESC";
        
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Get voucher analytics
     */
    public function get_voucher_analytics($date_from = null, $date_to = null) {
        $where = "WHERE 1=1";
        $params = array();
        
        if ($date_from) {
            $where .= " AND vu.used_at >= %s";
            $params[] = $date_from;
        }
        
        if ($date_to) {
            $where .= " AND vu.used_at <= %s";
            $params[] = $date_to;
        }
        
        $sql = "
            SELECT 
                v.voucher_code,
                v.voucher_name,
                v.discount_type,
                COUNT(vu.usage_id) as total_uses,
                SUM(vu.discount_amount) as total_discount,
                AVG(vu.discount_amount) as avg_discount,
                COUNT(DISTINCT vu.user_id) as unique_users
            FROM {$this->vouchers_table} v
            LEFT JOIN {$this->voucher_usage_table} vu ON v.voucher_id = vu.voucher_id AND vu.usage_type = 'used'
            {$where}
            GROUP BY v.voucher_id
            ORDER BY total_uses DESC
        ";
        
        if (!empty($params)) {
            $sql = $this->wpdb->prepare($sql, $params);
        }
        
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Get reward program statistics
     */
    public function get_reward_program_stats() {
        $sql = "
            SELECT 
                rp.program_name,
                COUNT(ur.user_id) as total_participants,
                SUM(ur.points_earned) as total_points_earned,
                AVG(ur.points_earned) as avg_points_per_user,
                COUNT(CASE WHEN ur.tier_level = 'gold' THEN 1 END) as gold_members,
                COUNT(CASE WHEN ur.tier_level = 'silver' THEN 1 END) as silver_members,
                COUNT(CASE WHEN ur.tier_level = 'bronze' THEN 1 END) as bronze_members
            FROM {$this->reward_programs_table} rp
            LEFT JOIN {$this->user_rewards_table} ur ON rp.program_id = ur.program_id
            WHERE rp.status = 'active'
            GROUP BY rp.program_id
        ";
        
        return $this->wpdb->get_results($sql);
    }
}
