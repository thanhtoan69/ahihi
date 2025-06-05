<?php
/**
 * Environmental Social & Viral Analytics Tracker
 * 
 * Handles comprehensive analytics tracking for social sharing, viral metrics,
 * user engagement, and detailed reporting for the Environmental Platform.
 * 
 * @package Environmental_Social_Viral
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Social_Viral_Analytics {
    
    private static $instance = null;
    private $db_manager;
    private $sharing_manager;
    private $viral_engine;
    
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
        $this->db_manager = new Environmental_Social_Viral_Database();
        $this->sharing_manager = Environmental_Social_Viral_Sharing_Manager::get_instance();
        $this->viral_engine = Environmental_Social_Viral_Engine::get_instance();
        
        add_action('wp_ajax_env_social_get_analytics', array($this, 'get_analytics_data'));
        add_action('wp_ajax_env_social_export_analytics', array($this, 'export_analytics_data'));
    }
    
    /**
     * Track a social share event
     */
    public function track_share($platform, $content_id, $content_type, $user_id = 0) {
        global $wpdb;
        
        $user_id = $user_id ? $user_id : get_current_user_id();
        $content_info = $this->get_content_info($content_id, $content_type);
        
        if (!$content_info) {
            return false;
        }
        
        // Insert share record
        $share_data = array(
            'user_id' => $user_id,
            'content_type' => $content_type,
            'content_id' => $content_id,
            'platform' => $platform,
            'content_title' => $content_info['title'],
            'content_url' => $content_info['url'],
            'share_url' => $this->generate_share_url($platform, $content_info),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip_address' => $this->get_client_ip(),
            'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
            'device_info' => json_encode($this->get_device_info()),
            'share_time' => current_time('mysql')
        );
        
        $table_name = $wpdb->prefix . 'env_social_shares';
        $result = $wpdb->insert($table_name, $share_data);
        
        if ($result) {
            $share_id = $wpdb->insert_id;
            
            // Update analytics
            $this->update_share_analytics($platform, $content_id, $content_type, $user_id);
            
            // Calculate viral metrics
            $this->viral_engine->update_viral_metrics($content_id, $content_type, 'share', $platform);
            
            // Award points for sharing
            $this->award_sharing_points($user_id, $platform);
            
            // Track for viral coefficient calculation
            wp_schedule_single_event(time() + 300, 'env_social_viral_calculate_coefficients', array($content_id, $content_type));
            
            return $share_id;
        }
        
        return false;
    }
    
    /**
     * Track share click/engagement
     */
    public function track_share_click($share_id, $click_source = 'direct') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'env_social_shares';
        
        // Update click count
        $result = $wpdb->query($wpdb->prepare("
            UPDATE {$table_name} 
            SET clicks = clicks + 1, 
                last_click_time = %s,
                click_sources = JSON_ARRAY_APPEND(
                    COALESCE(click_sources, JSON_ARRAY()), 
                    '$', 
                    JSON_OBJECT('source', %s, 'time', %s, 'ip', %s)
                )
            WHERE share_id = %d
        ", current_time('mysql'), $click_source, current_time('mysql'), $this->get_client_ip(), $share_id));
        
        if ($result) {
            // Get share details for analytics
            $share = $wpdb->get_row($wpdb->prepare("
                SELECT * FROM {$table_name} WHERE share_id = %d
            ", $share_id));
            
            if ($share) {
                // Update viral metrics
                $this->viral_engine->update_viral_metrics($share->content_id, $share->content_type, 'click', $share->platform);
                
                // Award click points
                $this->award_click_points($share->user_id, $share->platform);
            }
        }
        
        return $result;
    }
    
    /**
     * Track conversion from share
     */
    public function track_share_conversion($share_id, $conversion_type, $conversion_value = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'env_social_shares';
        
        $result = $wpdb->query($wpdb->prepare("
            UPDATE {$table_name} 
            SET conversions = conversions + 1,
                conversion_value = conversion_value + %f,
                conversion_types = JSON_ARRAY_APPEND(
                    COALESCE(conversion_types, JSON_ARRAY()), 
                    '$', 
                    JSON_OBJECT('type', %s, 'value', %f, 'time', %s)
                )
            WHERE share_id = %d
        ", $conversion_value, $conversion_type, $conversion_value, current_time('mysql'), $share_id));
        
        if ($result) {
            // Get share details
            $share = $wpdb->get_row($wpdb->prepare("
                SELECT * FROM {$table_name} WHERE share_id = %d
            ", $share_id));
            
            if ($share) {
                // Update viral metrics
                $this->viral_engine->update_viral_metrics($share->content_id, $share->content_type, 'conversion', $share->platform);
                
                // Award conversion points
                $this->award_conversion_points($share->user_id, $share->platform, $conversion_value);
            }
        }
        
        return $result;
    }
    
    /**
     * Get comprehensive analytics data
     */
    public function get_analytics_data($params = array()) {
        global $wpdb;
        
        $defaults = array(
            'period' => '30days',
            'content_type' => 'all',
            'platform' => 'all',
            'user_id' => 0,
            'metrics' => array('shares', 'clicks', 'conversions', 'viral_coefficient')
        );
        
        $params = wp_parse_args($params, $defaults);
        $period_clause = $this->get_period_clause($params['period']);
        
        $analytics = array();
        
        // Share analytics
        if (in_array('shares', $params['metrics'])) {
            $analytics['shares'] = $this->get_share_analytics($params, $period_clause);
        }
        
        // Click analytics
        if (in_array('clicks', $params['metrics'])) {
            $analytics['clicks'] = $this->get_click_analytics($params, $period_clause);
        }
        
        // Conversion analytics
        if (in_array('conversions', $params['metrics'])) {
            $analytics['conversions'] = $this->get_conversion_analytics($params, $period_clause);
        }
        
        // Viral coefficient analytics
        if (in_array('viral_coefficient', $params['metrics'])) {
            $analytics['viral_coefficient'] = $this->get_viral_coefficient_analytics($params, $period_clause);
        }
        
        // Top performing content
        $analytics['top_content'] = $this->get_top_performing_content($params, $period_clause);
        
        // Platform performance
        $analytics['platform_performance'] = $this->get_platform_performance($params, $period_clause);
        
        // User engagement
        $analytics['user_engagement'] = $this->get_user_engagement_metrics($params, $period_clause);
        
        return $analytics;
    }
    
    /**
     * Get share analytics
     */
    private function get_share_analytics($params, $period_clause) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'env_social_shares';
        $where_clauses = array($period_clause);
        
        if ($params['content_type'] !== 'all') {
            $where_clauses[] = $wpdb->prepare("content_type = %s", $params['content_type']);
        }
        
        if ($params['platform'] !== 'all') {
            $where_clauses[] = $wpdb->prepare("platform = %s", $params['platform']);
        }
        
        if ($params['user_id']) {
            $where_clauses[] = $wpdb->prepare("user_id = %d", $params['user_id']);
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        // Total shares
        $total_shares = $wpdb->get_var("
            SELECT COUNT(*) FROM {$table_name} 
            WHERE {$where_sql}
        ");
        
        // Shares by platform
        $shares_by_platform = $wpdb->get_results("
            SELECT platform, COUNT(*) as share_count
            FROM {$table_name}
            WHERE {$where_sql}
            GROUP BY platform
            ORDER BY share_count DESC
        ");
        
        // Shares over time
        $shares_over_time = $wpdb->get_results("
            SELECT DATE(share_time) as date, COUNT(*) as shares
            FROM {$table_name}
            WHERE {$where_sql}
            GROUP BY DATE(share_time)
            ORDER BY date ASC
        ");
        
        return array(
            'total_shares' => $total_shares,
            'shares_by_platform' => $shares_by_platform,
            'shares_over_time' => $shares_over_time
        );
    }
    
    /**
     * Get click analytics
     */
    private function get_click_analytics($params, $period_clause) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'env_social_shares';
        $where_clauses = array($period_clause);
        
        if ($params['content_type'] !== 'all') {
            $where_clauses[] = $wpdb->prepare("content_type = %s", $params['content_type']);
        }
        
        if ($params['platform'] !== 'all') {
            $where_clauses[] = $wpdb->prepare("platform = %s", $params['platform']);
        }
        
        if ($params['user_id']) {
            $where_clauses[] = $wpdb->prepare("user_id = %d", $params['user_id']);
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        // Total clicks
        $total_clicks = $wpdb->get_var("
            SELECT SUM(clicks) FROM {$table_name} 
            WHERE {$where_sql}
        ");
        
        // Click-through rate
        $ctr_data = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_shares,
                SUM(clicks) as total_clicks,
                AVG(clicks) as avg_clicks_per_share
            FROM {$table_name}
            WHERE {$where_sql}
        ");
        
        $click_through_rate = $ctr_data->total_shares > 0 ? 
            ($ctr_data->total_clicks / $ctr_data->total_shares) * 100 : 0;
        
        return array(
            'total_clicks' => $total_clicks,
            'click_through_rate' => round($click_through_rate, 2),
            'avg_clicks_per_share' => round($ctr_data->avg_clicks_per_share, 2)
        );
    }
    
    /**
     * Get conversion analytics
     */
    private function get_conversion_analytics($params, $period_clause) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'env_social_shares';
        $where_clauses = array($period_clause);
        
        if ($params['content_type'] !== 'all') {
            $where_clauses[] = $wpdb->prepare("content_type = %s", $params['content_type']);
        }
        
        if ($params['platform'] !== 'all') {
            $where_clauses[] = $wpdb->prepare("platform = %s", $params['platform']);
        }
        
        if ($params['user_id']) {
            $where_clauses[] = $wpdb->prepare("user_id = %d", $params['user_id']);
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        // Conversion metrics
        $conversion_data = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_shares,
                SUM(conversions) as total_conversions,
                SUM(conversion_value) as total_conversion_value,
                AVG(conversions) as avg_conversions_per_share,
                AVG(conversion_value) as avg_conversion_value
            FROM {$table_name}
            WHERE {$where_sql}
        ");
        
        $conversion_rate = $conversion_data->total_shares > 0 ? 
            ($conversion_data->total_conversions / $conversion_data->total_shares) * 100 : 0;
        
        return array(
            'total_conversions' => $conversion_data->total_conversions,
            'conversion_rate' => round($conversion_rate, 2),
            'total_conversion_value' => $conversion_data->total_conversion_value,
            'avg_conversion_value' => round($conversion_data->avg_conversion_value, 2)
        );
    }
    
    /**
     * Get viral coefficient analytics
     */
    private function get_viral_coefficient_analytics($params, $period_clause) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'env_viral_coefficients';
        $where_clauses = array($period_clause);
        
        if ($params['platform'] !== 'all') {
            $where_clauses[] = $wpdb->prepare("platform = %s", $params['platform']);
        }
        
        $where_sql = str_replace('share_time', 'calculation_time', implode(' AND ', $where_clauses));
        
        // Viral coefficient data
        $viral_data = $wpdb->get_row("
            SELECT 
                AVG(viral_coefficient) as avg_coefficient,
                MAX(viral_coefficient) as max_coefficient,
                MIN(viral_coefficient) as min_coefficient,
                COUNT(*) as total_calculations
            FROM {$table_name}
            WHERE {$where_sql}
        ");
        
        // Viral coefficient trend
        $viral_trend = $wpdb->get_results("
            SELECT 
                DATE(calculation_time) as date,
                AVG(viral_coefficient) as avg_coefficient
            FROM {$table_name}
            WHERE {$where_sql}
            GROUP BY DATE(calculation_time)
            ORDER BY date ASC
        ");
        
        return array(
            'avg_coefficient' => round($viral_data->avg_coefficient, 4),
            'max_coefficient' => round($viral_data->max_coefficient, 4),
            'min_coefficient' => round($viral_data->min_coefficient, 4),
            'viral_trend' => $viral_trend
        );
    }
    
    /**
     * Get top performing content
     */
    private function get_top_performing_content($params, $period_clause, $limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'env_social_shares';
        $where_clauses = array($period_clause);
        
        if ($params['content_type'] !== 'all') {
            $where_clauses[] = $wpdb->prepare("content_type = %s", $params['content_type']);
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                content_id,
                content_type,
                content_title,
                COUNT(*) as total_shares,
                SUM(clicks) as total_clicks,
                SUM(conversions) as total_conversions,
                AVG(clicks) as avg_clicks_per_share
            FROM {$table_name}
            WHERE {$where_sql}
            GROUP BY content_id, content_type
            ORDER BY total_shares DESC, total_clicks DESC
            LIMIT %d
        ", $limit));
    }
    
    /**
     * Get platform performance
     */
    private function get_platform_performance($params, $period_clause) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'env_social_shares';
        $where_clauses = array($period_clause);
        
        if ($params['content_type'] !== 'all') {
            $where_clauses[] = $wpdb->prepare("content_type = %s", $params['content_type']);
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        return $wpdb->get_results("
            SELECT 
                platform,
                COUNT(*) as total_shares,
                SUM(clicks) as total_clicks,
                SUM(conversions) as total_conversions,
                AVG(clicks) as avg_clicks_per_share,
                (SUM(clicks) / COUNT(*)) * 100 as click_through_rate,
                (SUM(conversions) / COUNT(*)) * 100 as conversion_rate
            FROM {$table_name}
            WHERE {$where_sql}
            GROUP BY platform
            ORDER BY total_shares DESC
        ");
    }
    
    /**
     * Get user engagement metrics
     */
    private function get_user_engagement_metrics($params, $period_clause) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'env_social_shares';
        $where_clauses = array($period_clause);
        
        if ($params['content_type'] !== 'all') {
            $where_clauses[] = $wpdb->prepare("content_type = %s", $params['content_type']);
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        // Active users
        $active_users = $wpdb->get_var("
            SELECT COUNT(DISTINCT user_id) FROM {$table_name} 
            WHERE {$where_sql}
        ");
        
        // Top sharers
        $top_sharers = $wpdb->get_results($wpdb->prepare("
            SELECT 
                user_id,
                COUNT(*) as total_shares,
                SUM(clicks) as total_clicks,
                SUM(conversions) as total_conversions
            FROM {$table_name}
            WHERE {$where_sql}
            GROUP BY user_id
            ORDER BY total_shares DESC
            LIMIT 10
        "));
        
        return array(
            'active_users' => $active_users,
            'top_sharers' => $top_sharers
        );
    }
    
    /**
     * Render sharing stats
     */
    public function render_sharing_stats($atts) {
        $defaults = array(
            'content_id' => get_the_ID(),
            'display_type' => 'summary',
            'period' => '7days',
            'show_details' => 'false'
        );
        
        $atts = wp_parse_args($atts, $defaults);
        
        $analytics = $this->get_content_analytics($atts['content_id'], $atts['period']);
        
        ob_start();
        ?>
        <div class="env-sharing-stats" data-content-id="<?php echo esc_attr($atts['content_id']); ?>">
            <?php if ($atts['display_type'] === 'summary'): ?>
                <div class="stats-summary">
                    <span class="stat-item">
                        <i class="fas fa-share-alt"></i>
                        <?php echo number_format($analytics['total_shares']); ?> <?php _e('shares', 'environmental-social-viral'); ?>
                    </span>
                    <span class="stat-item">
                        <i class="fas fa-mouse-pointer"></i>
                        <?php echo number_format($analytics['total_clicks']); ?> <?php _e('clicks', 'environmental-social-viral'); ?>
                    </span>
                    <?php if ($analytics['total_conversions'] > 0): ?>
                    <span class="stat-item">
                        <i class="fas fa-chart-line"></i>
                        <?php echo number_format($analytics['total_conversions']); ?> <?php _e('conversions', 'environmental-social-viral'); ?>
                    </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($atts['show_details'] === 'true'): ?>
                <div class="stats-details">
                    <h4><?php _e('Platform Breakdown', 'environmental-social-viral'); ?></h4>
                    <div class="platform-stats">
                        <?php foreach ($analytics['platform_breakdown'] as $platform => $stats): ?>
                            <div class="platform-stat">
                                <strong><?php echo esc_html($platform); ?>:</strong>
                                <span><?php echo number_format($stats['shares']); ?> shares</span>
                                <span><?php echo number_format($stats['clicks']); ?> clicks</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get content-specific analytics
     */
    public function get_content_analytics($content_id, $period = '7days') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'env_social_shares';
        $period_clause = $this->get_period_clause($period);
        
        // Basic stats
        $stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_shares,
                SUM(clicks) as total_clicks,
                SUM(conversions) as total_conversions
            FROM {$table_name}
            WHERE content_id = %d AND {$period_clause}
        ", $content_id));
        
        // Platform breakdown
        $platform_breakdown = array();
        $platform_stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                platform,
                COUNT(*) as shares,
                SUM(clicks) as clicks,
                SUM(conversions) as conversions
            FROM {$table_name}
            WHERE content_id = %d AND {$period_clause}
            GROUP BY platform
        ", $content_id));
        
        foreach ($platform_stats as $stat) {
            $platform_breakdown[$stat->platform] = array(
                'shares' => $stat->shares,
                'clicks' => $stat->clicks,
                'conversions' => $stat->conversions
            );
        }
        
        return array(
            'total_shares' => $stats->total_shares ?: 0,
            'total_clicks' => $stats->total_clicks ?: 0,
            'total_conversions' => $stats->total_conversions ?: 0,
            'platform_breakdown' => $platform_breakdown
        );
    }
    
    /**
     * Export analytics data
     */
    public function export_analytics_data() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to export analytics data.', 'environmental-social-viral'));
        }
        
        check_ajax_referer('env_social_viral_nonce', 'nonce');
        
        $format = sanitize_text_field($_POST['format'] ?? 'csv');
        $period = sanitize_text_field($_POST['period'] ?? '30days');
        
        $analytics = $this->get_analytics_data(array('period' => $period));
        
        if ($format === 'csv') {
            $this->export_csv($analytics);
        } elseif ($format === 'json') {
            $this->export_json($analytics);
        }
        
        wp_die();
    }
    
    /**
     * Export to CSV
     */
    private function export_csv($analytics) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="social-viral-analytics-' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        fputcsv($output, array('Metric', 'Value', 'Period'));
        
        // Basic metrics
        fputcsv($output, array('Total Shares', $analytics['shares']['total_shares'], $_POST['period']));
        fputcsv($output, array('Total Clicks', $analytics['clicks']['total_clicks'], $_POST['period']));
        fputcsv($output, array('Total Conversions', $analytics['conversions']['total_conversions'], $_POST['period']));
        fputcsv($output, array('Click Through Rate', $analytics['clicks']['click_through_rate'] . '%', $_POST['period']));
        fputcsv($output, array('Conversion Rate', $analytics['conversions']['conversion_rate'] . '%', $_POST['period']));
        
        fclose($output);
    }
    
    /**
     * Export to JSON
     */
    private function export_json($analytics) {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="social-viral-analytics-' . date('Y-m-d') . '.json"');
        
        echo json_encode($analytics, JSON_PRETTY_PRINT);
    }
    
    /**
     * Get period SQL clause
     */
    private function get_period_clause($period) {
        switch ($period) {
            case '24hours':
                return "share_time >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
            case '7days':
                return "share_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            case '30days':
                return "share_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            case '90days':
                return "share_time >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
            case '1year':
                return "share_time >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            default:
                return "share_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        }
    }
    
    /**
     * Get content information
     */
    private function get_content_info($content_id, $content_type) {
        if ($content_type === 'post' || $content_type === 'page' || $content_type === 'article') {
            $post = get_post($content_id);
            if (!$post) return false;
            
            return array(
                'title' => $post->post_title,
                'url' => get_permalink($content_id),
                'type' => $post->post_type
            );
        }
        
        // Handle other content types
        return apply_filters('env_social_viral_content_info', false, $content_id, $content_type);
    }
    
    /**
     * Generate share URL
     */
    private function generate_share_url($platform, $content_info) {
        return $this->sharing_manager->generate_share_url($platform, $content_info['url'], $content_info['title']);
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Get device information
     */
    private function get_device_info() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $device_info = array(
            'user_agent' => $user_agent,
            'is_mobile' => wp_is_mobile(),
            'browser' => $this->get_browser_info($user_agent),
            'platform' => $this->get_platform_info($user_agent)
        );
        
        return $device_info;
    }
    
    /**
     * Get browser information
     */
    private function get_browser_info($user_agent) {
        $browsers = array(
            'Chrome' => '/Chrome/i',
            'Firefox' => '/Firefox/i',
            'Safari' => '/Safari/i',
            'Edge' => '/Edge/i',
            'Opera' => '/Opera/i',
            'Internet Explorer' => '/MSIE/i'
        );
        
        foreach ($browsers as $browser => $pattern) {
            if (preg_match($pattern, $user_agent)) {
                return $browser;
            }
        }
        
        return 'Unknown';
    }
    
    /**
     * Get platform information
     */
    private function get_platform_info($user_agent) {
        $platforms = array(
            'Windows' => '/Windows/i',
            'Mac' => '/Mac/i',
            'Linux' => '/Linux/i',
            'Android' => '/Android/i',
            'iOS' => '/iPhone|iPad/i'
        );
        
        foreach ($platforms as $platform => $pattern) {
            if (preg_match($pattern, $user_agent)) {
                return $platform;
            }
        }
        
        return 'Unknown';
    }
    
    /**
     * Update share analytics
     */
    private function update_share_analytics($platform, $content_id, $content_type, $user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'env_share_analytics';
        
        // Insert or update analytics record
        $wpdb->query($wpdb->prepare("
            INSERT INTO {$table_name} 
            (content_id, content_type, platform, user_id, shares, last_share_time, created_at)
            VALUES (%d, %s, %s, %d, 1, %s, %s)
            ON DUPLICATE KEY UPDATE
            shares = shares + 1,
            last_share_time = %s
        ", $content_id, $content_type, $platform, $user_id, current_time('mysql'), current_time('mysql'), current_time('mysql')));
    }
    
    /**
     * Award sharing points
     */
    private function award_sharing_points($user_id, $platform) {
        if (!$user_id) return;
        
        $settings = get_option('env_social_viral_settings', array());
        $points = $settings['points_per_share'][$platform] ?? 5;
        
        // Integration with rewards system
        if (class_exists('Environmental_Voucher_Rewards_Points')) {
            $points_system = Environmental_Voucher_Rewards_Points::get_instance();
            $points_system->add_points($user_id, $points, 'social_share', "Shared content on {$platform}");
        }
    }
    
    /**
     * Award click points
     */
    private function award_click_points($user_id, $platform) {
        if (!$user_id) return;
        
        $settings = get_option('env_social_viral_settings', array());
        $points = $settings['points_per_click'][$platform] ?? 1;
        
        // Integration with rewards system
        if (class_exists('Environmental_Voucher_Rewards_Points')) {
            $points_system = Environmental_Voucher_Rewards_Points::get_instance();
            $points_system->add_points($user_id, $points, 'share_click', "Content shared on {$platform} was clicked");
        }
    }
    
    /**
     * Award conversion points
     */
    private function award_conversion_points($user_id, $platform, $conversion_value) {
        if (!$user_id) return;
        
        $settings = get_option('env_social_viral_settings', array());
        $base_points = $settings['points_per_conversion'][$platform] ?? 10;
        $bonus_points = floor($conversion_value * 0.1); // 10% of conversion value as bonus
        $total_points = $base_points + $bonus_points;
        
        // Integration with rewards system
        if (class_exists('Environmental_Voucher_Rewards_Points')) {
            $points_system = Environmental_Voucher_Rewards_Points::get_instance();
            $points_system->add_points($user_id, $total_points, 'share_conversion', "Content shared on {$platform} generated conversion");
        }
    }
    
    /**
     * Clean up old analytics data
     */
    public static function cleanup_old_data() {
        global $wpdb;
        
        $settings = get_option('env_social_viral_settings', array());
        $retention_days = $settings['analytics_retention_days'] ?? 365;
        
        $tables = array(
            $wpdb->prefix . 'env_social_shares',
            $wpdb->prefix . 'env_share_analytics',
            $wpdb->prefix . 'env_viral_metrics'
        );
        
        foreach ($tables as $table) {
            $wpdb->query($wpdb->prepare("
                DELETE FROM {$table} 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)
            ", $retention_days));
        }
    }
    
    /**
     * AJAX handler for analytics data
     */
    public function get_analytics_data_ajax() {
        check_ajax_referer('env_social_viral_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'environmental-social-viral'));
        }
        
        $params = array(
            'period' => sanitize_text_field($_POST['period'] ?? '30days'),
            'content_type' => sanitize_text_field($_POST['content_type'] ?? 'all'),
            'platform' => sanitize_text_field($_POST['platform'] ?? 'all'),
            'user_id' => intval($_POST['user_id'] ?? 0),
            'metrics' => array_map('sanitize_text_field', $_POST['metrics'] ?? array('shares', 'clicks', 'conversions'))
        );
        
        $analytics = $this->get_analytics_data($params);
        
        wp_send_json_success($analytics);
    }
}
