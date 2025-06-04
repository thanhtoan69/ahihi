<?php
/**
 * Analytics Dashboard for Item Exchange Platform
 * 
 * Provides comprehensive analytics and insights for exchange activities,
 * user behavior, environmental impact, and system performance
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Item_Exchange_Analytics {
    
    private static $instance = null;
    private $db_manager;
    private $cache_duration = 3600; // 1 hour
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->db_manager = Environmental_Item_Exchange_Database_Manager::get_instance();
        
        add_action('wp_ajax_ep_get_analytics_data', array($this, 'ajax_get_analytics_data'));
        add_action('wp_ajax_ep_export_analytics', array($this, 'ajax_export_analytics'));
        add_action('wp_ajax_ep_get_chart_data', array($this, 'ajax_get_chart_data'));
        
        // Schedule daily analytics updates
        if (!wp_next_scheduled('ep_update_analytics')) {
            wp_schedule_event(time(), 'daily', 'ep_update_analytics');
        }
        add_action('ep_update_analytics', array($this, 'update_daily_analytics'));
    }
    
    /**
     * Get comprehensive dashboard overview
     */
    public function get_dashboard_overview($date_range = 30) {
        $cache_key = 'ep_dashboard_overview_' . $date_range;
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        global $wpdb;
        
        $date_condition = "DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL {$date_range} DAY)";
        
        $overview = array(
            'total_stats' => $this->get_total_statistics($date_range),
            'exchange_trends' => $this->get_exchange_trends($date_range),
            'category_performance' => $this->get_category_performance($date_range),
            'user_engagement' => $this->get_user_engagement_stats($date_range),
            'environmental_impact' => $this->get_environmental_impact_stats($date_range),
            'geographic_distribution' => $this->get_geographic_distribution($date_range),
            'success_metrics' => $this->get_success_metrics($date_range),
            'top_performers' => $this->get_top_performers($date_range)
        );
        
        set_transient($cache_key, $overview, $this->cache_duration);
        return $overview;
    }
    
    /**
     * Get total statistics
     */
    private function get_total_statistics($date_range) {
        global $wpdb;
        
        $stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_exchanges,
                COUNT(CASE WHEN post_status = 'active' THEN 1 END) as active_exchanges,
                COUNT(CASE WHEN post_status = 'completed' THEN 1 END) as completed_exchanges,
                COUNT(CASE WHEN exchange_type = 'give_away' THEN 1 END) as give_aways,
                COUNT(CASE WHEN exchange_type = 'exchange' THEN 1 END) as trades,
                COUNT(CASE WHEN exchange_type = 'lending' THEN 1 END) as lendings,
                COUNT(CASE WHEN is_urgent = 1 THEN 1 END) as urgent_items,
                SUM(CASE WHEN estimated_value > 0 THEN estimated_value ELSE 0 END) as total_value,
                AVG(CASE WHEN estimated_value > 0 THEN estimated_value ELSE NULL END) as avg_value,
                SUM(eco_points_reward) as total_eco_points,
                SUM(carbon_footprint_saved) as total_carbon_saved
            FROM {$wpdb->prefix}posts p
            LEFT JOIN {$wpdb->prefix}postmeta pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_exchange_type'
            LEFT JOIN {$wpdb->prefix}postmeta pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_exchange_status'
            LEFT JOIN {$wpdb->prefix}postmeta pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_is_urgent'
            LEFT JOIN {$wpdb->prefix}postmeta pm4 ON p.ID = pm4.post_id AND pm4.meta_key = '_item_estimated_value'
            LEFT JOIN {$wpdb->prefix}postmeta pm5 ON p.ID = pm5.post_id AND pm5.meta_key = '_eco_points_reward'
            LEFT JOIN {$wpdb->prefix}postmeta pm6 ON p.ID = pm6.post_id AND pm6.meta_key = '_carbon_footprint_saved'
            WHERE p.post_type = 'item_exchange'
            AND DATE(p.post_date) >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
        ", $date_range));
        
        // Calculate growth rates
        $previous_stats = $wpdb->get_row($wpdb->prepare("
            SELECT COUNT(*) as total_exchanges
            FROM {$wpdb->prefix}posts
            WHERE post_type = 'item_exchange'
            AND DATE(post_date) >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
            AND DATE(post_date) < DATE_SUB(CURDATE(), INTERVAL %d DAY)
        ", $date_range * 2, $date_range));
        
        $stats->growth_rate = $previous_stats->total_exchanges > 0 
            ? (($stats->total_exchanges - $previous_stats->total_exchanges) / $previous_stats->total_exchanges) * 100 
            : 0;
        
        $stats->success_rate = $stats->total_exchanges > 0 
            ? ($stats->completed_exchanges / $stats->total_exchanges) * 100 
            : 0;
        
        return $stats;
    }
    
    /**
     * Get exchange trends over time
     */
    private function get_exchange_trends($date_range) {
        global $wpdb;
        
        $trends = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(p.post_date) as date,
                COUNT(*) as total_posts,
                COUNT(CASE WHEN pm1.meta_value = 'give_away' THEN 1 END) as give_aways,
                COUNT(CASE WHEN pm1.meta_value = 'exchange' THEN 1 END) as trades,
                COUNT(CASE WHEN pm1.meta_value = 'lending' THEN 1 END) as lendings,
                COUNT(CASE WHEN pm2.meta_value = 'completed' THEN 1 END) as completed
            FROM {$wpdb->prefix}posts p
            LEFT JOIN {$wpdb->prefix}postmeta pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_exchange_type'
            LEFT JOIN {$wpdb->prefix}postmeta pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_exchange_status'
            WHERE p.post_type = 'item_exchange'
            AND DATE(p.post_date) >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
            GROUP BY DATE(p.post_date)
            ORDER BY date ASC
        ", $date_range));
        
        return $trends;
    }
    
    /**
     * Get category performance metrics
     */
    private function get_category_performance($date_range) {
        global $wpdb;
        
        $categories = $wpdb->get_results($wpdb->prepare("
            SELECT 
                t.name as category_name,
                t.slug as category_slug,
                COUNT(p.ID) as total_posts,
                COUNT(CASE WHEN pm1.meta_value = 'completed' THEN 1 END) as completed_exchanges,
                AVG(CASE WHEN pm2.meta_value > 0 THEN pm2.meta_value ELSE NULL END) as avg_value,
                SUM(CASE WHEN pm3.meta_value > 0 THEN pm3.meta_value ELSE 0 END) as total_eco_points,
                SUM(CASE WHEN pm4.meta_value > 0 THEN pm4.meta_value ELSE 0 END) as total_carbon_saved,
                (COUNT(CASE WHEN pm1.meta_value = 'completed' THEN 1 END) / COUNT(p.ID)) * 100 as success_rate
            FROM {$wpdb->prefix}terms t
            INNER JOIN {$wpdb->prefix}term_taxonomy tt ON t.term_id = tt.term_id
            INNER JOIN {$wpdb->prefix}term_relationships tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
            INNER JOIN {$wpdb->prefix}posts p ON tr.object_id = p.ID
            LEFT JOIN {$wpdb->prefix}postmeta pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_exchange_status'
            LEFT JOIN {$wpdb->prefix}postmeta pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_item_estimated_value'
            LEFT JOIN {$wpdb->prefix}postmeta pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_eco_points_reward'
            LEFT JOIN {$wpdb->prefix}postmeta pm4 ON p.ID = pm4.post_id AND pm4.meta_key = '_carbon_footprint_saved'
            WHERE tt.taxonomy = 'exchange_type'
            AND p.post_type = 'item_exchange'
            AND DATE(p.post_date) >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
            GROUP BY t.term_id, t.name, t.slug
            ORDER BY total_posts DESC
        ", $date_range));
        
        return $categories;
    }
    
    /**
     * Get user engagement statistics
     */
    private function get_user_engagement_stats($date_range) {
        global $wpdb;
        
        $engagement = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(DISTINCT p.post_author) as active_users,
                COUNT(DISTINCT CASE WHEN DATE(p.post_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN p.post_author END) as weekly_active_users,
                COUNT(DISTINCT CASE WHEN DATE(p.post_date) >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN p.post_author END) as daily_active_users,
                AVG(posts_per_user.post_count) as avg_posts_per_user,
                MAX(posts_per_user.post_count) as max_posts_per_user
            FROM {$wpdb->prefix}posts p
            LEFT JOIN (
                SELECT 
                    post_author,
                    COUNT(*) as post_count
                FROM {$wpdb->prefix}posts
                WHERE post_type = 'item_exchange'
                AND DATE(post_date) >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
                GROUP BY post_author
            ) posts_per_user ON p.post_author = posts_per_user.post_author
            WHERE p.post_type = 'item_exchange'
            AND DATE(p.post_date) >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
        ", $date_range, $date_range));
        
        // Get user retention data
        $retention = $wpdb->get_results($wpdb->prepare("
            SELECT 
                WEEK(p.post_date) as week_number,
                COUNT(DISTINCT p.post_author) as users,
                COUNT(DISTINCT returning_users.post_author) as returning_users
            FROM {$wpdb->prefix}posts p
            LEFT JOIN {$wpdb->prefix}posts returning_users ON p.post_author = returning_users.post_author 
                AND returning_users.post_type = 'item_exchange'
                AND DATE(returning_users.post_date) < DATE(p.post_date)
            WHERE p.post_type = 'item_exchange'
            AND DATE(p.post_date) >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
            GROUP BY WEEK(p.post_date)
            ORDER BY week_number
        ", $date_range));
        
        $engagement->retention_data = $retention;
        return $engagement;
    }
    
    /**
     * Get environmental impact statistics
     */
    private function get_environmental_impact_stats($date_range) {
        global $wpdb;
        
        $impact = $wpdb->get_row($wpdb->prepare("
            SELECT 
                SUM(CASE WHEN pm1.meta_value > 0 THEN pm1.meta_value ELSE 0 END) as total_eco_points,
                SUM(CASE WHEN pm2.meta_value > 0 THEN pm2.meta_value ELSE 0 END) as total_carbon_saved,
                COUNT(CASE WHEN pm3.meta_value = 'give_away' THEN 1 END) as items_given_away,
                COUNT(CASE WHEN pm4.meta_value = 'completed' THEN 1 END) as successful_reuses,
                AVG(CASE WHEN pm1.meta_value > 0 THEN pm1.meta_value ELSE NULL END) as avg_eco_points_per_item,
                AVG(CASE WHEN pm2.meta_value > 0 THEN pm2.meta_value ELSE NULL END) as avg_carbon_saved_per_item
            FROM {$wpdb->prefix}posts p
            LEFT JOIN {$wpdb->prefix}postmeta pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_eco_points_reward'
            LEFT JOIN {$wpdb->prefix}postmeta pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_carbon_footprint_saved'
            LEFT JOIN {$wpdb->prefix}postmeta pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_exchange_type'
            LEFT JOIN {$wpdb->prefix}postmeta pm4 ON p.ID = pm4.post_id AND pm4.meta_key = '_exchange_status'
            WHERE p.post_type = 'item_exchange'
            AND DATE(p.post_date) >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
        ", $date_range));
        
        // Calculate environmental impact trends
        $impact_trends = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(p.post_date) as date,
                SUM(CASE WHEN pm1.meta_value > 0 THEN pm1.meta_value ELSE 0 END) as daily_eco_points,
                SUM(CASE WHEN pm2.meta_value > 0 THEN pm2.meta_value ELSE 0 END) as daily_carbon_saved,
                COUNT(CASE WHEN pm3.meta_value = 'give_away' THEN 1 END) as daily_give_aways
            FROM {$wpdb->prefix}posts p
            LEFT JOIN {$wpdb->prefix}postmeta pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_eco_points_reward'
            LEFT JOIN {$wpdb->prefix}postmeta pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_carbon_footprint_saved'
            LEFT JOIN {$wpdb->prefix}postmeta pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_exchange_type'
            WHERE p.post_type = 'item_exchange'
            AND DATE(p.post_date) >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
            GROUP BY DATE(p.post_date)
            ORDER BY date ASC
        ", $date_range));
        
        $impact->trends = $impact_trends;
        return $impact;
    }
    
    /**
     * Get geographic distribution of exchanges
     */
    private function get_geographic_distribution($date_range) {
        global $wpdb;
        
        $geographic_data = $wpdb->get_results($wpdb->prepare("
            SELECT 
                pm.meta_value as location_data,
                COUNT(*) as exchange_count
            FROM {$wpdb->prefix}posts p
            INNER JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
            WHERE p.post_type = 'item_exchange'
            AND pm.meta_key = '_exchange_location'
            AND pm.meta_value != ''
            AND DATE(p.post_date) >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
            GROUP BY pm.meta_value
            ORDER BY exchange_count DESC
            LIMIT 20
        ", $date_range));
        
        // Process location data
        $processed_locations = array();
        foreach ($geographic_data as $location) {
            $location_info = maybe_unserialize($location->location_data);
            if (is_array($location_info) && isset($location_info['city'])) {
                $city = $location_info['city'];
                if (!isset($processed_locations[$city])) {
                    $processed_locations[$city] = 0;
                }
                $processed_locations[$city] += $location->exchange_count;
            }
        }
        
        arsort($processed_locations);
        return array_slice($processed_locations, 0, 10, true);
    }
    
    /**
     * Get success metrics and conversion rates
     */
    private function get_success_metrics($date_range) {
        global $wpdb;
        
        $metrics = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_posts,
                COUNT(CASE WHEN pm1.meta_value = 'completed' THEN 1 END) as completed_posts,
                COUNT(CASE WHEN pm1.meta_value = 'active' THEN 1 END) as active_posts,
                COUNT(CASE WHEN pm1.meta_value = 'expired' THEN 1 END) as expired_posts,
                AVG(DATEDIFF(IFNULL(pm2.meta_value, NOW()), p.post_date)) as avg_days_to_completion,
                COUNT(CASE WHEN pm3.meta_value > 0 THEN 1 END) as posts_with_interest
            FROM {$wpdb->prefix}posts p
            LEFT JOIN {$wpdb->prefix}postmeta pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_exchange_status'
            LEFT JOIN {$wpdb->prefix}postmeta pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_completion_date'
            LEFT JOIN {$wpdb->prefix}postmeta pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_view_count'
            WHERE p.post_type = 'item_exchange'
            AND DATE(p.post_date) >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
        ", $date_range));
        
        $metrics->completion_rate = $metrics->total_posts > 0 
            ? ($metrics->completed_posts / $metrics->total_posts) * 100 
            : 0;
        
        $metrics->engagement_rate = $metrics->total_posts > 0 
            ? ($metrics->posts_with_interest / $metrics->total_posts) * 100 
            : 0;
        
        return $metrics;
    }
    
    /**
     * Get top performing users
     */
    private function get_top_performers($date_range) {
        global $wpdb;
        
        $top_users = $wpdb->get_results($wpdb->prepare("
            SELECT 
                u.ID as user_id,
                u.display_name,
                u.user_email,
                COUNT(p.ID) as total_posts,
                COUNT(CASE WHEN pm1.meta_value = 'completed' THEN 1 END) as completed_exchanges,
                SUM(CASE WHEN pm2.meta_value > 0 THEN pm2.meta_value ELSE 0 END) as total_eco_points,
                AVG(CASE WHEN pm3.meta_value > 0 THEN pm3.meta_value ELSE NULL END) as avg_rating,
                (COUNT(CASE WHEN pm1.meta_value = 'completed' THEN 1 END) / COUNT(p.ID)) * 100 as success_rate
            FROM {$wpdb->prefix}users u
            INNER JOIN {$wpdb->prefix}posts p ON u.ID = p.post_author
            LEFT JOIN {$wpdb->prefix}postmeta pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_exchange_status'
            LEFT JOIN {$wpdb->prefix}postmeta pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_eco_points_reward'
            LEFT JOIN {$wpdb->prefix}usermeta pm3 ON u.ID = pm3.user_id AND pm3.meta_key = '_exchange_rating'
            WHERE p.post_type = 'item_exchange'
            AND DATE(p.post_date) >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
            GROUP BY u.ID, u.display_name, u.user_email
            HAVING total_posts >= 3
            ORDER BY success_rate DESC, total_posts DESC
            LIMIT 10
        ", $date_range));
        
        return $top_users;
    }
    
    /**
     * Generate analytics report
     */
    public function generate_report($date_range = 30, $format = 'array') {
        $report = array(
            'generated_at' => current_time('mysql'),
            'date_range' => $date_range,
            'summary' => $this->get_dashboard_overview($date_range),
            'detailed_metrics' => array(
                'conversion_funnel' => $this->get_conversion_funnel($date_range),
                'user_behavior' => $this->get_user_behavior_analysis($date_range),
                'item_lifecycle' => $this->get_item_lifecycle_analysis($date_range),
                'matching_effectiveness' => $this->get_matching_effectiveness($date_range)
            ),
            'recommendations' => $this->generate_recommendations($date_range)
        );
        
        if ($format === 'json') {
            return json_encode($report, JSON_PRETTY_PRINT);
        } elseif ($format === 'csv') {
            return $this->convert_to_csv($report);
        }
        
        return $report;
    }
    
    /**
     * Get conversion funnel analysis
     */
    private function get_conversion_funnel($date_range) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_views,
                COUNT(CASE WHEN pm1.meta_value > 0 THEN 1 END) as items_with_views,
                COUNT(CASE WHEN pm2.meta_value IS NOT NULL THEN 1 END) as items_with_messages,
                COUNT(CASE WHEN pm3.meta_value = 'completed' THEN 1 END) as completed_exchanges
            FROM {$wpdb->prefix}posts p
            LEFT JOIN {$wpdb->prefix}postmeta pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_view_count'
            LEFT JOIN {$wpdb->prefix}postmeta pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_message_count'
            LEFT JOIN {$wpdb->prefix}postmeta pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_exchange_status'
            WHERE p.post_type = 'item_exchange'
            AND DATE(p.post_date) >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
        ", $date_range));
    }
    
    /**
     * Get user behavior analysis
     */
    private function get_user_behavior_analysis($date_range) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                HOUR(p.post_date) as hour_of_day,
                DAYOFWEEK(p.post_date) as day_of_week,
                COUNT(*) as post_count,
                AVG(CASE WHEN pm1.meta_value > 0 THEN pm1.meta_value ELSE 0 END) as avg_completion_time
            FROM {$wpdb->prefix}posts p
            LEFT JOIN {$wpdb->prefix}postmeta pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_days_to_completion'
            WHERE p.post_type = 'item_exchange'
            AND DATE(p.post_date) >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
            GROUP BY HOUR(p.post_date), DAYOFWEEK(p.post_date)
            ORDER BY post_count DESC
        ", $date_range));
    }
    
    /**
     * Get item lifecycle analysis
     */
    private function get_item_lifecycle_analysis($date_range) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                pm1.meta_value as item_condition,
                COUNT(*) as total_items,
                AVG(DATEDIFF(IFNULL(pm2.meta_value, NOW()), p.post_date)) as avg_days_active,
                COUNT(CASE WHEN pm3.meta_value = 'completed' THEN 1 END) as completed_items,
                (COUNT(CASE WHEN pm3.meta_value = 'completed' THEN 1 END) / COUNT(*)) * 100 as completion_rate
            FROM {$wpdb->prefix}posts p
            LEFT JOIN {$wpdb->prefix}postmeta pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_item_condition'
            LEFT JOIN {$wpdb->prefix}postmeta pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_completion_date'
            LEFT JOIN {$wpdb->prefix}postmeta pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_exchange_status'
            WHERE p.post_type = 'item_exchange'
            AND DATE(p.post_date) >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
            GROUP BY pm1.meta_value
            ORDER BY completion_rate DESC
        ", $date_range));
    }
    
    /**
     * Get matching effectiveness metrics
     */
    private function get_matching_effectiveness($date_range) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'exchange_matches';
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_matches,
                AVG(compatibility_score) as avg_compatibility_score,
                COUNT(CASE WHEN match_status = 'interested' THEN 1 END) as interested_matches,
                COUNT(CASE WHEN match_status = 'contacted' THEN 1 END) as contacted_matches,
                COUNT(CASE WHEN match_status = 'completed' THEN 1 END) as completed_matches,
                (COUNT(CASE WHEN match_status = 'contacted' THEN 1 END) / COUNT(*)) * 100 as contact_rate,
                (COUNT(CASE WHEN match_status = 'completed' THEN 1 END) / COUNT(*)) * 100 as conversion_rate
            FROM {$table}
            WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
        ", $date_range));
    }
    
    /**
     * Generate AI-powered recommendations
     */
    private function generate_recommendations($date_range) {
        $overview = $this->get_dashboard_overview($date_range);
        $recommendations = array();
        
        // Low success rate recommendation
        if ($overview['total_stats']->success_rate < 30) {
            $recommendations[] = array(
                'type' => 'improvement',
                'priority' => 'high',
                'title' => __('Improve Success Rate', 'environmental-item-exchange'),
                'description' => __('Current success rate is below 30%. Consider improving matching algorithm or user guidance.', 'environmental-item-exchange')
            );
        }
        
        // High environmental impact opportunity
        if ($overview['environmental_impact']->total_carbon_saved > 100) {
            $recommendations[] = array(
                'type' => 'success',
                'priority' => 'medium',
                'title' => __('Excellent Environmental Impact', 'environmental-item-exchange'),
                'description' => sprintf(
                    __('Your platform has saved %.2f kg of CO2! Share this achievement with users.', 'environmental-item-exchange'),
                    $overview['environmental_impact']->total_carbon_saved
                )
            );
        }
        
        // User engagement recommendations
        if ($overview['user_engagement']->daily_active_users < $overview['user_engagement']->weekly_active_users * 0.2) {
            $recommendations[] = array(
                'type' => 'engagement',
                'priority' => 'medium',
                'title' => __('Increase Daily Engagement', 'environmental-item-exchange'),
                'description' => __('Consider sending daily reminders or implementing push notifications to increase daily active users.', 'environmental-item-exchange')
            );
        }
        
        return $recommendations;
    }
    
    /**
     * Update daily analytics cache
     */
    public function update_daily_analytics() {
        // Clear existing cache
        $cache_keys = array('ep_dashboard_overview_7', 'ep_dashboard_overview_30', 'ep_dashboard_overview_90');
        foreach ($cache_keys as $key) {
            delete_transient($key);
        }
        
        // Pre-warm cache with fresh data
        $this->get_dashboard_overview(7);
        $this->get_dashboard_overview(30);
        $this->get_dashboard_overview(90);
        
        // Store daily snapshot
        $snapshot = array(
            'date' => current_time('Y-m-d'),
            'data' => $this->get_dashboard_overview(1)
        );
        
        update_option('ep_daily_analytics_' . current_time('Y_m_d'), $snapshot);
    }
    
    /**
     * AJAX handler for analytics data
     */
    public function ajax_get_analytics_data() {
        check_ajax_referer('ep_exchange_nonce', 'nonce');
        
        $date_range = intval($_POST['date_range'] ?? 30);
        $chart_type = sanitize_text_field($_POST['chart_type'] ?? 'overview');
        
        switch ($chart_type) {
            case 'overview':
                $data = $this->get_dashboard_overview($date_range);
                break;
            case 'trends':
                $data = $this->get_exchange_trends($date_range);
                break;
            case 'categories':
                $data = $this->get_category_performance($date_range);
                break;
            case 'environmental':
                $data = $this->get_environmental_impact_stats($date_range);
                break;
            default:
                $data = array();
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX handler for chart data
     */
    public function ajax_get_chart_data() {
        check_ajax_referer('ep_exchange_nonce', 'nonce');
        
        $chart_type = sanitize_text_field($_POST['chart_type'] ?? '');
        $date_range = intval($_POST['date_range'] ?? 30);
        
        $data = array();
        
        switch ($chart_type) {
            case 'exchange_trends':
                $trends = $this->get_exchange_trends($date_range);
                $data = array(
                    'labels' => array_column($trends, 'date'),
                    'datasets' => array(
                        array(
                            'label' => __('Total Posts', 'environmental-item-exchange'),
                            'data' => array_column($trends, 'total_posts'),
                            'borderColor' => '#4CAF50',
                            'backgroundColor' => 'rgba(76, 175, 80, 0.1)'
                        ),
                        array(
                            'label' => __('Completed', 'environmental-item-exchange'),
                            'data' => array_column($trends, 'completed'),
                            'borderColor' => '#2196F3',
                            'backgroundColor' => 'rgba(33, 150, 243, 0.1)'
                        )
                    )
                );
                break;
                
            case 'category_distribution':
                $categories = $this->get_category_performance($date_range);
                $data = array(
                    'labels' => array_column($categories, 'category_name'),
                    'datasets' => array(
                        array(
                            'label' => __('Posts per Category', 'environmental-item-exchange'),
                            'data' => array_column($categories, 'total_posts'),
                            'backgroundColor' => array(
                                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                                '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
                            )
                        )
                    )
                );
                break;
                
            case 'environmental_impact':
                $impact = $this->get_environmental_impact_stats($date_range);
                $data = array(
                    'labels' => array_column($impact->trends, 'date'),
                    'datasets' => array(
                        array(
                            'label' => __('CO2 Saved (kg)', 'environmental-item-exchange'),
                            'data' => array_column($impact->trends, 'daily_carbon_saved'),
                            'borderColor' => '#4CAF50',
                            'backgroundColor' => 'rgba(76, 175, 80, 0.1)',
                            'yAxisID' => 'y'
                        ),
                        array(
                            'label' => __('Eco Points', 'environmental-item-exchange'),
                            'data' => array_column($impact->trends, 'daily_eco_points'),
                            'borderColor' => '#FF9800',
                            'backgroundColor' => 'rgba(255, 152, 0, 0.1)',
                            'yAxisID' => 'y1'
                        )
                    )
                );
                break;
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX handler for exporting analytics
     */
    public function ajax_export_analytics() {
        check_ajax_referer('ep_exchange_nonce', 'nonce');
        
        $format = sanitize_text_field($_POST['format'] ?? 'csv');
        $date_range = intval($_POST['date_range'] ?? 30);
        
        $report = $this->generate_report($date_range, $format);
        
        // Set appropriate headers
        if ($format === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="exchange_analytics_' . date('Y-m-d') . '.csv"');
        } elseif ($format === 'json') {
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="exchange_analytics_' . date('Y-m-d') . '.json"');
        }
        
        echo $report;
        wp_die();
    }
    
    /**
     * Convert report data to CSV format
     */
    private function convert_to_csv($report) {
        $csv_data = array();
        
        // Add headers
        $csv_data[] = array('Metric', 'Value', 'Description');
        
        // Add total statistics
        $stats = $report['summary']['total_stats'];
        $csv_data[] = array('Total Exchanges', $stats->total_exchanges, 'Total number of exchange posts');
        $csv_data[] = array('Active Exchanges', $stats->active_exchanges, 'Currently active exchanges');
        $csv_data[] = array('Completed Exchanges', $stats->completed_exchanges, 'Successfully completed exchanges');
        $csv_data[] = array('Success Rate', number_format($stats->success_rate, 2) . '%', 'Percentage of successful exchanges');
        $csv_data[] = array('Total Value', '$' . number_format($stats->total_value, 2), 'Total estimated value of items');
        $csv_data[] = array('Carbon Saved', $stats->total_carbon_saved . ' kg', 'Total CO2 savings');
        $csv_data[] = array('Eco Points', $stats->total_eco_points, 'Total eco points awarded');
        
        // Convert to CSV string
        $output = fopen('php://temp', 'r+');
        foreach ($csv_data as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csv_string = stream_get_contents($output);
        fclose($output);
        
        return $csv_string;
    }
}

// Initialize the analytics system
Environmental_Item_Exchange_Analytics::get_instance();
