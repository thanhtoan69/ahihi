<?php
/**
 * Analytics and Reporting Class
 * 
 * Handles voucher usage analytics, reward distribution statistics,
 * loyalty program insights, and comprehensive reporting
 * 
 * @package Environmental_Voucher_Rewards
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Analytics {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Database manager instance
     */
    private $db_manager;
    
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
        $this->db_manager = Environmental_Database_Manager::get_instance();
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_env_get_voucher_analytics', array($this, 'get_voucher_analytics'));
        add_action('wp_ajax_env_get_reward_analytics', array($this, 'get_reward_analytics'));
        add_action('wp_ajax_env_get_loyalty_analytics', array($this, 'get_loyalty_analytics'));
        add_action('wp_ajax_env_get_partner_analytics', array($this, 'get_partner_analytics'));
        add_action('wp_ajax_env_export_analytics', array($this, 'export_analytics'));
        
        // Schedule daily analytics updates
        add_action('env_daily_analytics_update', array($this, 'update_daily_analytics'));
        if (!wp_next_scheduled('env_daily_analytics_update')) {
            wp_schedule_event(time(), 'daily', 'env_daily_analytics_update');
        }
    }
    
    /**
     * Get voucher usage analytics
     */
    public function get_voucher_analytics() {
        check_ajax_referer('env_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $period = sanitize_text_field($_POST['period'] ?? '30');
        $campaign_id = intval($_POST['campaign_id'] ?? 0);
        
        $analytics = $this->calculate_voucher_analytics($period, $campaign_id);
        
        wp_send_json_success($analytics);
    }
    
    /**
     * Calculate voucher analytics
     */
    public function calculate_voucher_analytics($period = '30', $campaign_id = 0) {
        global $wpdb;
        
        $date_from = date('Y-m-d H:i:s', strtotime("-{$period} days"));
        $campaign_condition = $campaign_id ? "AND vc.id = {$campaign_id}" : "";
        
        // Voucher generation stats
        $generation_stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(v.created_at) as date,
                COUNT(*) as generated_count,
                vc.name as campaign_name,
                vc.discount_type,
                vc.discount_value
            FROM {$wpdb->prefix}environmental_vouchers v
            JOIN {$wpdb->prefix}environmental_voucher_campaigns vc ON v.campaign_id = vc.id
            WHERE v.created_at >= %s {$campaign_condition}
            GROUP BY DATE(v.created_at), vc.id
            ORDER BY v.created_at DESC
        ", $date_from));
        
        // Voucher usage stats
        $usage_stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(vu.used_at) as date,
                COUNT(*) as usage_count,
                SUM(vu.discount_amount) as total_discount,
                AVG(vu.discount_amount) as avg_discount,
                vc.name as campaign_name
            FROM {$wpdb->prefix}environmental_voucher_usage vu
            JOIN {$wpdb->prefix}environmental_vouchers v ON vu.voucher_id = v.id
            JOIN {$wpdb->prefix}environmental_voucher_campaigns vc ON v.campaign_id = vc.id
            WHERE vu.used_at >= %s {$campaign_condition}
            GROUP BY DATE(vu.used_at), vc.id
            ORDER BY vu.used_at DESC
        ", $date_from));
        
        // Top performing campaigns
        $top_campaigns = $wpdb->get_results($wpdb->prepare("
            SELECT 
                vc.name,
                vc.discount_type,
                vc.discount_value,
                COUNT(DISTINCT v.id) as total_vouchers,
                COUNT(DISTINCT vu.id) as used_vouchers,
                ROUND((COUNT(DISTINCT vu.id) / COUNT(DISTINCT v.id)) * 100, 2) as usage_rate,
                SUM(vu.discount_amount) as total_savings
            FROM {$wpdb->prefix}environmental_voucher_campaigns vc
            LEFT JOIN {$wpdb->prefix}environmental_vouchers v ON vc.id = v.campaign_id
            LEFT JOIN {$wpdb->prefix}environmental_voucher_usage vu ON v.id = vu.voucher_id
            WHERE vc.created_at >= %s {$campaign_condition}
            GROUP BY vc.id
            ORDER BY usage_rate DESC, total_savings DESC
            LIMIT 10
        ", $date_from));
        
        // User engagement stats
        $user_stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                COUNT(DISTINCT vu.user_id) as active_users,
                COUNT(DISTINCT v.user_id) as voucher_recipients,
                AVG(user_usage.usage_count) as avg_usage_per_user
            FROM {$wpdb->prefix}environmental_voucher_usage vu
            JOIN {$wpdb->prefix}environmental_vouchers v ON vu.voucher_id = v.id
            JOIN (
                SELECT user_id, COUNT(*) as usage_count
                FROM {$wpdb->prefix}environmental_voucher_usage
                WHERE used_at >= %s
                GROUP BY user_id
            ) user_usage ON vu.user_id = user_usage.user_id
            WHERE vu.used_at >= %s
        ", $date_from, $date_from));
        
        return array(
            'generation_stats' => $generation_stats,
            'usage_stats' => $usage_stats,
            'top_campaigns' => $top_campaigns,
            'user_stats' => $user_stats[0] ?? null,
            'summary' => array(
                'total_generated' => array_sum(array_column($generation_stats, 'generated_count')),
                'total_used' => array_sum(array_column($usage_stats, 'usage_count')),
                'total_savings' => array_sum(array_column($usage_stats, 'total_discount')),
                'usage_rate' => $this->calculate_overall_usage_rate($period, $campaign_id)
            )
        );
    }
    
    /**
     * Get reward distribution analytics
     */
    public function get_reward_analytics() {
        check_ajax_referer('env_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $period = sanitize_text_field($_POST['period'] ?? '30');
        $reward_type = sanitize_text_field($_POST['reward_type'] ?? '');
        
        $analytics = $this->calculate_reward_analytics($period, $reward_type);
        
        wp_send_json_success($analytics);
    }
    
    /**
     * Calculate reward analytics
     */
    public function calculate_reward_analytics($period = '30', $reward_type = '') {
        global $wpdb;
        
        $date_from = date('Y-m-d H:i:s', strtotime("-{$period} days"));
        $type_condition = $reward_type ? $wpdb->prepare("AND rt.transaction_type = %s", $reward_type) : "";
        
        // Reward distribution by type
        $distribution_stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                rt.transaction_type,
                COUNT(*) as transaction_count,
                SUM(rt.points_amount) as total_points,
                AVG(rt.points_amount) as avg_points,
                COUNT(DISTINCT rt.user_id) as unique_users
            FROM {$wpdb->prefix}environmental_reward_transactions rt
            WHERE rt.created_at >= %s {$type_condition}
            GROUP BY rt.transaction_type
            ORDER BY total_points DESC
        ", $date_from));
        
        // Daily reward trends
        $daily_trends = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(rt.created_at) as date,
                rt.transaction_type,
                COUNT(*) as transaction_count,
                SUM(rt.points_amount) as daily_points
            FROM {$wpdb->prefix}environmental_reward_transactions rt
            WHERE rt.created_at >= %s {$type_condition}
            GROUP BY DATE(rt.created_at), rt.transaction_type
            ORDER BY rt.created_at DESC
        ", $date_from));
        
        // Top reward earners
        $top_earners = $wpdb->get_results($wpdb->prepare("
            SELECT 
                u.display_name,
                u.user_email,
                SUM(rt.points_amount) as total_points,
                COUNT(rt.id) as transaction_count,
                ur.current_points,
                ur.lifetime_points
            FROM {$wpdb->prefix}environmental_reward_transactions rt
            JOIN {$wpdb->prefix}users u ON rt.user_id = u.ID
            JOIN {$wpdb->prefix}environmental_user_rewards ur ON rt.user_id = ur.user_id
            WHERE rt.created_at >= %s {$type_condition}
            GROUP BY rt.user_id
            ORDER BY total_points DESC
            LIMIT 20
        ", $date_from));
        
        // Reward program performance
        $program_stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                rp.name as program_name,
                rp.program_type,
                COUNT(DISTINCT rt.user_id) as participants,
                SUM(rt.points_amount) as total_rewards,
                AVG(rt.points_amount) as avg_reward
            FROM {$wpdb->prefix}environmental_reward_programs rp
            JOIN {$wpdb->prefix}environmental_reward_transactions rt ON rp.id = rt.program_id
            WHERE rt.created_at >= %s {$type_condition}
            GROUP BY rp.id
            ORDER BY total_rewards DESC
        ", $date_from));
        
        return array(
            'distribution_stats' => $distribution_stats,
            'daily_trends' => $daily_trends,
            'top_earners' => $top_earners,
            'program_stats' => $program_stats,
            'summary' => array(
                'total_transactions' => array_sum(array_column($distribution_stats, 'transaction_count')),
                'total_points_distributed' => array_sum(array_column($distribution_stats, 'total_points')),
                'unique_participants' => $this->get_unique_reward_participants($period, $reward_type),
                'avg_points_per_user' => $this->calculate_avg_points_per_user($period, $reward_type)
            )
        );
    }
    
    /**
     * Get loyalty program analytics
     */
    public function get_loyalty_analytics() {
        check_ajax_referer('env_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $analytics = $this->calculate_loyalty_analytics();
        
        wp_send_json_success($analytics);
    }
    
    /**
     * Calculate loyalty program analytics
     */
    public function calculate_loyalty_analytics() {
        global $wpdb;
        
        // Tier distribution
        $tier_distribution = $wpdb->get_results("
            SELECT 
                ur.loyalty_tier,
                COUNT(*) as user_count,
                AVG(ur.current_points) as avg_points,
                AVG(ur.lifetime_points) as avg_lifetime_points
            FROM {$wpdb->prefix}environmental_user_rewards ur
            GROUP BY ur.loyalty_tier
            ORDER BY 
                CASE ur.loyalty_tier
                    WHEN 'bronze' THEN 1
                    WHEN 'silver' THEN 2
                    WHEN 'gold' THEN 3
                    WHEN 'platinum' THEN 4
                    WHEN 'diamond' THEN 5
                    ELSE 6
                END
        ");
        
        // Tier progression trends
        $progression_trends = $wpdb->get_results("
            SELECT 
                DATE(rt.created_at) as date,
                rt.transaction_type,
                COUNT(DISTINCT rt.user_id) as users_progressed
            FROM {$wpdb->prefix}environmental_reward_transactions rt
            WHERE rt.transaction_type LIKE '%tier_%'
            AND rt.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
            GROUP BY DATE(rt.created_at), rt.transaction_type
            ORDER BY rt.created_at DESC
        ");
        
        // Loyalty benefits usage
        $benefits_usage = $wpdb->get_results("
            SELECT 
                ur.loyalty_tier,
                COUNT(DISTINCT v.user_id) as voucher_users,
                COUNT(v.id) as total_vouchers,
                AVG(vu.discount_amount) as avg_discount
            FROM {$wpdb->prefix}environmental_user_rewards ur
            LEFT JOIN {$wpdb->prefix}environmental_vouchers v ON ur.user_id = v.user_id
            LEFT JOIN {$wpdb->prefix}environmental_voucher_usage vu ON v.id = vu.voucher_id
            WHERE v.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY ur.loyalty_tier
            ORDER BY 
                CASE ur.loyalty_tier
                    WHEN 'bronze' THEN 1
                    WHEN 'silver' THEN 2
                    WHEN 'gold' THEN 3
                    WHEN 'platinum' THEN 4
                    WHEN 'diamond' THEN 5
                    ELSE 6
                END
        ");
        
        // Tier retention analysis
        $retention_stats = $wpdb->get_results("
            SELECT 
                ur.loyalty_tier,
                COUNT(*) as total_users,
                COUNT(CASE WHEN rt.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as active_users,
                ROUND(
                    (COUNT(CASE WHEN rt.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) / COUNT(*)) * 100, 
                    2
                ) as retention_rate
            FROM {$wpdb->prefix}environmental_user_rewards ur
            LEFT JOIN {$wpdb->prefix}environmental_reward_transactions rt ON ur.user_id = rt.user_id
            GROUP BY ur.loyalty_tier
        ");
        
        return array(
            'tier_distribution' => $tier_distribution,
            'progression_trends' => $progression_trends,
            'benefits_usage' => $benefits_usage,
            'retention_stats' => $retention_stats,
            'summary' => array(
                'total_loyalty_members' => array_sum(array_column($tier_distribution, 'user_count')),
                'avg_tier_progression_rate' => $this->calculate_tier_progression_rate(),
                'highest_tier_members' => $this->get_highest_tier_count(),
                'loyalty_engagement_score' => $this->calculate_loyalty_engagement_score()
            )
        );
    }
    
    /**
     * Get partner discount analytics
     */
    public function get_partner_analytics() {
        check_ajax_referer('env_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $period = sanitize_text_field($_POST['period'] ?? '30');
        $partner_id = intval($_POST['partner_id'] ?? 0);
        
        $analytics = $this->calculate_partner_analytics($period, $partner_id);
        
        wp_send_json_success($analytics);
    }
    
    /**
     * Calculate partner analytics
     */
    public function calculate_partner_analytics($period = '30', $partner_id = 0) {
        global $wpdb;
        
        $date_from = date('Y-m-d H:i:s', strtotime("-{$period} days"));
        $partner_condition = $partner_id ? "AND pd.id = {$partner_id}" : "";
        
        // Partner discount usage
        $usage_stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                pd.partner_name,
                pd.discount_type,
                pd.discount_value,
                COUNT(vu.id) as usage_count,
                SUM(vu.discount_amount) as total_savings,
                COUNT(DISTINCT vu.user_id) as unique_users
            FROM {$wpdb->prefix}environmental_partner_discounts pd
            LEFT JOIN {$wpdb->prefix}environmental_voucher_usage vu ON pd.id = vu.partner_discount_id
            WHERE vu.used_at >= %s {$partner_condition}
            GROUP BY pd.id
            ORDER BY usage_count DESC
        ", $date_from));
        
        // Partner performance comparison
        $performance_comparison = $wpdb->get_results($wpdb->prepare("
            SELECT 
                pd.partner_name,
                pd.category,
                COUNT(vu.id) as total_redemptions,
                AVG(vu.discount_amount) as avg_discount,
                COUNT(DISTINCT vu.user_id) as unique_customers,
                ROUND(AVG(vu.discount_amount), 2) as customer_lifetime_value
            FROM {$wpdb->prefix}environmental_partner_discounts pd
            LEFT JOIN {$wpdb->prefix}environmental_voucher_usage vu ON pd.id = vu.partner_discount_id
            WHERE vu.used_at >= %s {$partner_condition}
            GROUP BY pd.id
            ORDER BY total_redemptions DESC, customer_lifetime_value DESC
        ", $date_from));
        
        // Category performance
        $category_stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                pd.category,
                COUNT(DISTINCT pd.id) as partner_count,
                COUNT(vu.id) as total_usage,
                SUM(vu.discount_amount) as category_savings,
                AVG(vu.discount_amount) as avg_discount
            FROM {$wpdb->prefix}environmental_partner_discounts pd
            LEFT JOIN {$wpdb->prefix}environmental_voucher_usage vu ON pd.id = vu.partner_discount_id
            WHERE vu.used_at >= %s {$partner_condition}
            GROUP BY pd.category
            ORDER BY total_usage DESC
        ", $date_from));
        
        return array(
            'usage_stats' => $usage_stats,
            'performance_comparison' => $performance_comparison,
            'category_stats' => $category_stats,
            'summary' => array(
                'total_partners' => count($usage_stats),
                'total_redemptions' => array_sum(array_column($usage_stats, 'usage_count')),
                'total_partner_savings' => array_sum(array_column($usage_stats, 'total_savings')),
                'avg_redemptions_per_partner' => round(array_sum(array_column($usage_stats, 'usage_count')) / max(count($usage_stats), 1), 2)
            )
        );
    }
    
    /**
     * Export analytics data
     */
    public function export_analytics() {
        check_ajax_referer('env_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $export_type = sanitize_text_field($_POST['export_type']);
        $period = sanitize_text_field($_POST['period'] ?? '30');
        $format = sanitize_text_field($_POST['format'] ?? 'csv');
        
        $data = array();
        
        switch ($export_type) {
            case 'voucher':
                $data = $this->calculate_voucher_analytics($period);
                break;
            case 'reward':
                $data = $this->calculate_reward_analytics($period);
                break;
            case 'loyalty':
                $data = $this->calculate_loyalty_analytics();
                break;
            case 'partner':
                $data = $this->calculate_partner_analytics($period);
                break;
            default:
                wp_send_json_error('Invalid export type');
                return;
        }
        
        if ($format === 'csv') {
            $this->export_to_csv($data, $export_type);
        } else {
            $this->export_to_json($data, $export_type);
        }
    }
    
    /**
     * Export data to CSV
     */
    private function export_to_csv($data, $type) {
        $filename = "environmental_analytics_{$type}_" . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Write headers and data based on type
        switch ($type) {
            case 'voucher':
                fputcsv($output, array('Date', 'Campaign', 'Generated', 'Used', 'Usage Rate', 'Total Savings'));
                foreach ($data['usage_stats'] as $stat) {
                    fputcsv($output, array(
                        $stat->date,
                        $stat->campaign_name,
                        $stat->generated_count ?? 0,
                        $stat->usage_count,
                        '0%', // Calculate usage rate
                        $stat->total_discount
                    ));
                }
                break;
                
            case 'reward':
                fputcsv($output, array('Type', 'Transactions', 'Total Points', 'Avg Points', 'Unique Users'));
                foreach ($data['distribution_stats'] as $stat) {
                    fputcsv($output, array(
                        $stat->transaction_type,
                        $stat->transaction_count,
                        $stat->total_points,
                        $stat->avg_points,
                        $stat->unique_users
                    ));
                }
                break;
                
            case 'loyalty':
                fputcsv($output, array('Tier', 'Users', 'Avg Points', 'Avg Lifetime Points'));
                foreach ($data['tier_distribution'] as $tier) {
                    fputcsv($output, array(
                        ucfirst($tier->loyalty_tier),
                        $tier->user_count,
                        $tier->avg_points,
                        $tier->avg_lifetime_points
                    ));
                }
                break;
                
            case 'partner':
                fputcsv($output, array('Partner', 'Category', 'Redemptions', 'Total Savings', 'Unique Users'));
                foreach ($data['usage_stats'] as $stat) {
                    fputcsv($output, array(
                        $stat->partner_name,
                        $stat->category ?? 'N/A',
                        $stat->usage_count,
                        $stat->total_savings,
                        $stat->unique_users
                    ));
                }
                break;
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export data to JSON
     */
    private function export_to_json($data, $type) {
        $filename = "environmental_analytics_{$type}_" . date('Y-m-d') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Helper methods for calculations
     */
    private function calculate_overall_usage_rate($period, $campaign_id) {
        global $wpdb;
        
        $date_from = date('Y-m-d H:i:s', strtotime("-{$period} days"));
        $campaign_condition = $campaign_id ? "AND vc.id = {$campaign_id}" : "";
        
        $result = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(DISTINCT v.id) as total_vouchers,
                COUNT(DISTINCT vu.voucher_id) as used_vouchers
            FROM {$wpdb->prefix}environmental_vouchers v
            JOIN {$wpdb->prefix}environmental_voucher_campaigns vc ON v.campaign_id = vc.id
            LEFT JOIN {$wpdb->prefix}environmental_voucher_usage vu ON v.id = vu.voucher_id
            WHERE v.created_at >= %s {$campaign_condition}
        ", $date_from));
        
        if ($result && $result->total_vouchers > 0) {
            return round(($result->used_vouchers / $result->total_vouchers) * 100, 2);
        }
        
        return 0;
    }
    
    private function get_unique_reward_participants($period, $reward_type) {
        global $wpdb;
        
        $date_from = date('Y-m-d H:i:s', strtotime("-{$period} days"));
        $type_condition = $reward_type ? $wpdb->prepare("AND transaction_type = %s", $reward_type) : "";
        
        $result = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT user_id)
            FROM {$wpdb->prefix}environmental_reward_transactions
            WHERE created_at >= %s {$type_condition}
        ", $date_from));
        
        return intval($result);
    }
    
    private function calculate_avg_points_per_user($period, $reward_type) {
        global $wpdb;
        
        $date_from = date('Y-m-d H:i:s', strtotime("-{$period} days"));
        $type_condition = $reward_type ? $wpdb->prepare("AND transaction_type = %s", $reward_type) : "";
        
        $result = $wpdb->get_var($wpdb->prepare("
            SELECT AVG(user_points.total_points)
            FROM (
                SELECT user_id, SUM(points_amount) as total_points
                FROM {$wpdb->prefix}environmental_reward_transactions
                WHERE created_at >= %s {$type_condition}
                GROUP BY user_id
            ) user_points
        ", $date_from));
        
        return round(floatval($result), 2);
    }
    
    private function calculate_tier_progression_rate() {
        global $wpdb;
        
        $result = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->prefix}environmental_reward_transactions
            WHERE transaction_type LIKE '%tier_%'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        return intval($result);
    }
    
    private function get_highest_tier_count() {
        global $wpdb;
        
        $result = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->prefix}environmental_user_rewards
            WHERE loyalty_tier IN ('platinum', 'diamond')
        ");
        
        return intval($result);
    }
    
    private function calculate_loyalty_engagement_score() {
        global $wpdb;
        
        // Calculate based on recent activity, tier distribution, and progression
        $recent_activity = $wpdb->get_var("
            SELECT COUNT(DISTINCT user_id)
            FROM {$wpdb->prefix}environmental_reward_transactions
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        
        $total_users = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->prefix}environmental_user_rewards
        ");
        
        if ($total_users > 0) {
            return round(($recent_activity / $total_users) * 100, 2);
        }
        
        return 0;
    }
    
    /**
     * Update daily analytics cache
     */
    public function update_daily_analytics() {
        $analytics_data = array(
            'voucher' => $this->calculate_voucher_analytics(1),
            'reward' => $this->calculate_reward_analytics(1),
            'loyalty' => $this->calculate_loyalty_analytics(),
            'partner' => $this->calculate_partner_analytics(1)
        );
        
        update_option('env_daily_analytics_' . date('Y-m-d'), $analytics_data);
        
        // Clean up old analytics data (keep 90 days)
        $this->cleanup_old_analytics();
    }
    
    /**
     * Clean up old analytics data
     */
    private function cleanup_old_analytics() {
        global $wpdb;
        
        // Get all analytics options older than 90 days
        $old_options = $wpdb->get_col("
            SELECT option_name
            FROM {$wpdb->options}
            WHERE option_name LIKE 'env_daily_analytics_%'
            AND option_name < 'env_daily_analytics_" . date('Y-m-d', strtotime('-90 days')) . "'
        ");
        
        foreach ($old_options as $option) {
            delete_option($option);
        }
    }
}

// Initialize the Analytics class
Environmental_Analytics::get_instance();
