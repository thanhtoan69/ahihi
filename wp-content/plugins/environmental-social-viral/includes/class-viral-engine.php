<?php
/**
 * Environmental Social Viral Engine
 * 
 * Handles viral coefficient calculations and viral content analysis
 */

class Environmental_Social_Viral_Engine {
    
    private static $instance = null;
    private $wpdb;
    private $tables;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        $database = new Environmental_Social_Viral_Database();
        $this->tables = $database->get_all_tables();
        
        // Hook into WordPress cron
        add_action('env_social_viral_calculate_coefficients', array($this, 'calculate_viral_coefficients'));
    }
    
    /**
     * Calculate viral coefficients for content
     */
    public function calculate_viral_coefficients() {
        $this->calculate_content_viral_coefficients();
        $this->calculate_platform_viral_coefficients();
        $this->calculate_user_viral_coefficients();
        $this->update_trending_scores();
    }
    
    /**
     * Calculate viral coefficients for individual content
     */
    private function calculate_content_viral_coefficients() {
        // Get content with recent sharing activity
        $content_list = $this->wpdb->get_results(
            "SELECT DISTINCT content_id, content_type 
             FROM {$this->tables['shares']} 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             ORDER BY created_at DESC"
        );
        
        foreach ($content_list as $content) {
            $this->calculate_single_content_viral_coefficient($content->content_id, $content->content_type);
        }
    }
    
    /**
     * Calculate viral coefficient for single content
     */
    public function calculate_single_content_viral_coefficient($content_id, $content_type = 'post') {
        $periods = array('1day', '7days', '30days');
        
        foreach ($periods as $period) {
            $coefficient_data = $this->analyze_content_virality($content_id, $content_type, $period);
            
            if ($coefficient_data) {
                $this->store_viral_coefficient($content_id, $content_type, 'content', $coefficient_data, $period);
            }
        }
    }
    
    /**
     * Analyze content virality
     */
    private function analyze_content_virality($content_id, $content_type, $period) {
        $period_condition = $this->get_period_condition($period);
        
        // Get sharing data
        $sharing_data = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT 
                    COUNT(DISTINCT user_id) as unique_sharers,
                    COUNT(*) as total_shares,
                    SUM(click_count) as total_clicks,
                    SUM(conversion_count) as total_conversions,
                    COUNT(DISTINCT platform) as platforms_used
                 FROM {$this->tables['shares']} 
                 WHERE content_id = %d AND content_type = %s {$period_condition}",
                $content_id,
                $content_type
            )
        );
        
        if (!$sharing_data || $sharing_data->total_shares == 0) {
            return null;
        }
        
        // Get secondary shares (shares from people who came via shares)
        $secondary_shares = $this->calculate_secondary_shares($content_id, $content_type, $period);
        
        // Calculate base viral coefficient
        $viral_coefficient = $this->calculate_base_viral_coefficient($sharing_data, $secondary_shares);
        
        // Apply platform weights
        $platform_weighted_coefficient = $this->apply_platform_weights($content_id, $viral_coefficient, $period);
        
        // Apply engagement factors
        $engagement_weighted_coefficient = $this->apply_engagement_factors($sharing_data, $platform_weighted_coefficient);
        
        // Apply time decay
        $time_weighted_coefficient = $this->apply_time_decay($engagement_weighted_coefficient, $period);
        
        return array(
            'coefficient_value' => $time_weighted_coefficient,
            'sample_size' => $sharing_data->total_shares,
            'confidence_level' => $this->calculate_confidence_level($sharing_data->total_shares),
            'factors_data' => json_encode(array(
                'base_coefficient' => $viral_coefficient,
                'platform_weight' => $platform_weighted_coefficient / $viral_coefficient,
                'engagement_weight' => $engagement_weighted_coefficient / $platform_weighted_coefficient,
                'time_weight' => $time_weighted_coefficient / $engagement_weighted_coefficient,
                'sharing_data' => $sharing_data,
                'secondary_shares' => $secondary_shares
            ))
        );
    }
    
    /**
     * Calculate base viral coefficient (K-factor)
     */
    private function calculate_base_viral_coefficient($sharing_data, $secondary_shares) {
        if ($sharing_data->unique_sharers == 0) {
            return 0;
        }
        
        // K = (average invitations sent per user) × (conversion rate of invitations)
        $avg_shares_per_user = $sharing_data->total_shares / $sharing_data->unique_sharers;
        $conversion_rate = $sharing_data->total_conversions > 0 ? 
                          $sharing_data->total_conversions / $sharing_data->total_clicks : 0;
        
        // Factor in secondary shares for viral amplification
        $viral_amplification = $secondary_shares > 0 ? 
                              ($secondary_shares / $sharing_data->total_shares) + 1 : 1;
        
        $base_coefficient = $avg_shares_per_user * $conversion_rate * $viral_amplification;
        
        return min($base_coefficient, 10); // Cap at 10 to prevent extreme values
    }
    
    /**
     * Calculate secondary shares (viral loop)
     */
    private function calculate_secondary_shares($content_id, $content_type, $period) {
        $period_condition = $this->get_period_condition($period);
        
        // This is a simplified calculation - in reality, you'd track referral chains
        $secondary_shares = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) 
                 FROM {$this->tables['shares']} s1
                 INNER JOIN {$this->tables['shares']} s2 ON s1.user_id = s2.user_id
                 WHERE s1.content_id = %d AND s1.content_type = %s
                 AND s2.created_at > s1.created_at 
                 AND s2.created_at <= DATE_ADD(s1.created_at, INTERVAL 7 DAY)
                 {$period_condition}",
                $content_id,
                $content_type
            )
        );
        
        return intval($secondary_shares);
    }
    
    /**
     * Apply platform weights to viral coefficient
     */
    private function apply_platform_weights($content_id, $coefficient, $period) {
        $period_condition = $this->get_period_condition($period);
        
        // Get platform distribution
        $platform_stats = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT platform, 
                        COUNT(*) as share_count,
                        AVG(click_count) as avg_clicks,
                        AVG(conversion_count) as avg_conversions
                 FROM {$this->tables['shares']} 
                 WHERE content_id = %d {$period_condition}
                 GROUP BY platform",
                $content_id
            )
        );
        
        // Platform viral weights (based on typical sharing behavior)
        $platform_weights = array(
            'facebook' => 1.2,  // High reach, moderate engagement
            'twitter' => 1.5,   // High viral potential
            'linkedin' => 1.1,  // Professional network, lower viral
            'whatsapp' => 1.8,  // Very high engagement, trusted sharing
            'telegram' => 1.4,  // Good viral potential
            'email' => 1.3,     // High trust, good conversion
            'copy' => 1.0       // Baseline
        );
        
        $weighted_coefficient = 0;
        $total_shares = 0;
        
        foreach ($platform_stats as $stat) {
            $weight = $platform_weights[$stat->platform] ?? 1.0;
            $platform_coefficient = $coefficient * $weight * ($stat->avg_clicks + $stat->avg_conversions + 1);
            $weighted_coefficient += $platform_coefficient * $stat->share_count;
            $total_shares += $stat->share_count;
        }
        
        return $total_shares > 0 ? $weighted_coefficient / $total_shares : $coefficient;
    }
    
    /**
     * Apply engagement factors
     */
    private function apply_engagement_factors($sharing_data, $coefficient) {
        // Calculate engagement rate
        $engagement_rate = $sharing_data->total_clicks > 0 ? 
                          $sharing_data->total_clicks / $sharing_data->total_shares : 0;
        
        // Calculate conversion rate
        $conversion_rate = $sharing_data->total_clicks > 0 ? 
                          $sharing_data->total_conversions / $sharing_data->total_clicks : 0;
        
        // Apply engagement multiplier
        $engagement_multiplier = 1 + ($engagement_rate * 0.5) + ($conversion_rate * 1.0);
        
        return $coefficient * $engagement_multiplier;
    }
    
    /**
     * Apply time decay to coefficient
     */
    private function apply_time_decay($coefficient, $period) {
        // Recent activity gets higher weight
        $decay_factors = array(
            '1day' => 1.0,
            '7days' => 0.8,
            '30days' => 0.6
        );
        
        $decay_factor = $decay_factors[$period] ?? 0.5;
        
        return $coefficient * $decay_factor;
    }
    
    /**
     * Calculate confidence level based on sample size
     */
    private function calculate_confidence_level($sample_size) {
        if ($sample_size >= 100) {
            return 0.95;
        } elseif ($sample_size >= 50) {
            return 0.90;
        } elseif ($sample_size >= 25) {
            return 0.85;
        } elseif ($sample_size >= 10) {
            return 0.75;
        } else {
            return 0.60;
        }
    }
    
    /**
     * Store viral coefficient
     */
    private function store_viral_coefficient($content_id, $content_type, $coefficient_type, $coefficient_data, $period) {
        // Store for each platform
        $platforms = $this->get_content_platforms($content_id, $content_type, $period);
        
        foreach ($platforms as $platform) {
            $this->wpdb->replace(
                $this->tables['viral_coefficients'],
                array(
                    'content_id' => $content_id,
                    'content_type' => $content_type,
                    'platform' => $platform,
                    'coefficient_type' => $coefficient_type,
                    'coefficient_value' => $coefficient_data['coefficient_value'],
                    'calculation_period' => $period,
                    'sample_size' => $coefficient_data['sample_size'],
                    'confidence_level' => $coefficient_data['confidence_level'],
                    'calculation_date' => current_time('mysql'),
                    'factors_data' => $coefficient_data['factors_data']
                )
            );
        }
        
        // Update viral content table
        $this->update_viral_content_coefficient($content_id, $content_type, $coefficient_data['coefficient_value']);
    }
    
    /**
     * Get platforms used for content
     */
    private function get_content_platforms($content_id, $content_type, $period) {
        $period_condition = $this->get_period_condition($period);
        
        $platforms = $this->wpdb->get_col(
            $this->wpdb->prepare(
                "SELECT DISTINCT platform 
                 FROM {$this->tables['shares']} 
                 WHERE content_id = %d AND content_type = %s {$period_condition}",
                $content_id,
                $content_type
            )
        );
        
        return $platforms ?: array('overall');
    }
    
    /**
     * Update viral content coefficient
     */
    private function update_viral_content_coefficient($content_id, $content_type, $coefficient_value) {
        // Calculate engagement rate
        $viral_data = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT share_count, click_count, conversion_count 
                 FROM {$this->tables['viral_content']} 
                 WHERE content_id = %d AND content_type = %s",
                $content_id,
                $content_type
            )
        );
        
        if ($viral_data) {
            $engagement_rate = $viral_data->share_count > 0 ? 
                              $viral_data->click_count / $viral_data->share_count : 0;
            
            $this->wpdb->update(
                $this->tables['viral_content'],
                array(
                    'viral_coefficient' => $coefficient_value,
                    'engagement_rate' => $engagement_rate,
                    'updated_at' => current_time('mysql')
                ),
                array(
                    'content_id' => $content_id,
                    'content_type' => $content_type
                )
            );
        }
    }
    
    /**
     * Calculate platform viral coefficients
     */
    private function calculate_platform_viral_coefficients() {
        $platforms = array('facebook', 'twitter', 'linkedin', 'whatsapp', 'telegram', 'email');
        
        foreach ($platforms as $platform) {
            $this->calculate_platform_coefficient($platform);
        }
    }
    
    /**
     * Calculate viral coefficient for specific platform
     */
    private function calculate_platform_coefficient($platform) {
        $periods = array('1day', '7days', '30days');
        
        foreach ($periods as $period) {
            $period_condition = $this->get_period_condition($period);
            
            $platform_stats = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT 
                        COUNT(DISTINCT content_id) as content_count,
                        COUNT(DISTINCT user_id) as user_count,
                        COUNT(*) as total_shares,
                        SUM(click_count) as total_clicks,
                        SUM(conversion_count) as total_conversions,
                        AVG(click_count) as avg_clicks_per_share,
                        AVG(conversion_count) as avg_conversions_per_share
                     FROM {$this->tables['shares']} 
                     WHERE platform = %s {$period_condition}",
                    $platform
                )
            );
            
            if ($platform_stats && $platform_stats->total_shares > 0) {
                $platform_coefficient = $this->calculate_platform_viral_factor($platform_stats);
                
                // Store platform coefficient
                $this->wpdb->replace(
                    $this->tables['viral_coefficients'],
                    array(
                        'content_id' => 0, // 0 indicates platform-wide coefficient
                        'content_type' => 'platform',
                        'platform' => $platform,
                        'coefficient_type' => 'platform_average',
                        'coefficient_value' => $platform_coefficient,
                        'calculation_period' => $period,
                        'sample_size' => $platform_stats->total_shares,
                        'confidence_level' => $this->calculate_confidence_level($platform_stats->total_shares),
                        'calculation_date' => current_time('mysql'),
                        'factors_data' => json_encode($platform_stats)
                    )
                );
            }
        }
    }
    
    /**
     * Calculate platform viral factor
     */
    private function calculate_platform_viral_factor($stats) {
        $click_rate = $stats->total_shares > 0 ? $stats->total_clicks / $stats->total_shares : 0;
        $conversion_rate = $stats->total_clicks > 0 ? $stats->total_conversions / $stats->total_clicks : 0;
        $content_diversity = $stats->content_count > 0 ? $stats->total_shares / $stats->content_count : 0;
        
        // Platform viral factor combines engagement and spread
        $viral_factor = ($click_rate * 0.4) + ($conversion_rate * 0.4) + (min($content_diversity / 10, 1) * 0.2);
        
        return min($viral_factor, 5); // Cap at 5
    }
    
    /**
     * Calculate user viral coefficients
     */
    private function calculate_user_viral_coefficients() {
        // Get top sharers
        $top_sharers = $this->wpdb->get_results(
            "SELECT user_id, 
                    COUNT(*) as share_count,
                    SUM(click_count) as total_clicks,
                    SUM(conversion_count) as total_conversions
             FROM {$this->tables['shares']} 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             AND user_id IS NOT NULL
             GROUP BY user_id
             HAVING share_count >= 5
             ORDER BY total_conversions DESC, total_clicks DESC
             LIMIT 100"
        );
        
        foreach ($top_sharers as $sharer) {
            $user_coefficient = $this->calculate_user_viral_factor($sharer);
            
            // Store in user meta
            update_user_meta($sharer->user_id, 'env_viral_coefficient', $user_coefficient);
            update_user_meta($sharer->user_id, 'env_viral_influence_score', $this->calculate_influence_score($sharer));
        }
    }
    
    /**
     * Calculate user viral factor
     */
    private function calculate_user_viral_factor($user_stats) {
        $avg_clicks_per_share = $user_stats->share_count > 0 ? $user_stats->total_clicks / $user_stats->share_count : 0;
        $conversion_rate = $user_stats->total_clicks > 0 ? $user_stats->total_conversions / $user_stats->total_clicks : 0;
        
        return ($avg_clicks_per_share * 0.6) + ($conversion_rate * 0.4);
    }
    
    /**
     * Calculate user influence score
     */
    private function calculate_influence_score($user_stats) {
        // Influence is based on total impact
        $influence_score = ($user_stats->total_clicks * 1) + ($user_stats->total_conversions * 5) + ($user_stats->share_count * 0.5);
        
        return min($influence_score, 1000); // Cap at 1000
    }
    
    /**
     * Update trending scores
     */
    private function update_trending_scores() {
        // Calculate trending scores based on recent activity and viral coefficients
        $trending_content = $this->wpdb->get_results(
            "SELECT vc.content_id, vc.content_type,
                    vc.viral_coefficient,
                    vc.share_count,
                    vc.click_count,
                    vc.conversion_count,
                    COALESCE(recent.recent_shares, 0) as recent_shares,
                    COALESCE(recent.recent_clicks, 0) as recent_clicks
             FROM {$this->tables['viral_content']} vc
             LEFT JOIN (
                 SELECT content_id, content_type,
                        COUNT(*) as recent_shares,
                        SUM(click_count) as recent_clicks
                 FROM {$this->tables['shares']}
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                 GROUP BY content_id, content_type
             ) recent ON vc.content_id = recent.content_id AND vc.content_type = recent.content_type
             WHERE vc.last_viral_activity >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        
        foreach ($trending_content as $content) {
            $trending_score = $this->calculate_trending_score($content);
            
            $this->wpdb->update(
                $this->tables['viral_content'],
                array(
                    'trending_score' => $trending_score,
                    'updated_at' => current_time('mysql')
                ),
                array(
                    'content_id' => $content->content_id,
                    'content_type' => $content->content_type
                )
            );
        }
    }
    
    /**
     * Calculate trending score
     */
    private function calculate_trending_score($content) {
        // Trending score combines viral coefficient with recent activity
        $viral_weight = $content->viral_coefficient * 10;
        $recent_activity_weight = ($content->recent_shares * 2) + ($content->recent_clicks * 1);
        $historical_weight = log($content->share_count + 1) + log($content->click_count + 1);
        
        $trending_score = ($viral_weight * 0.4) + ($recent_activity_weight * 0.4) + ($historical_weight * 0.2);
        
        return min($trending_score, 1000);
    }
    
    /**
     * Get content viral stats
     */
    public function get_content_viral_stats($content_id, $period = '30days') {
        $period_condition = $this->get_period_condition($period);
        
        // Get basic stats
        $stats = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tables['viral_content']} 
                 WHERE content_id = %d",
                $content_id
            )
        );
        
        if (!$stats) {
            return null;
        }
        
        // Get platform breakdown
        $platform_stats = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT platform, 
                        COUNT(*) as shares,
                        SUM(click_count) as clicks,
                        SUM(conversion_count) as conversions
                 FROM {$this->tables['shares']} 
                 WHERE content_id = %d {$period_condition}
                 GROUP BY platform
                 ORDER BY shares DESC",
                $content_id
            )
        );
        
        // Get viral coefficients
        $coefficients = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT platform, coefficient_value, confidence_level, calculation_period
                 FROM {$this->tables['viral_coefficients']} 
                 WHERE content_id = %d
                 ORDER BY calculation_date DESC",
                $content_id
            )
        );
        
        return array(
            'basic_stats' => $stats,
            'platform_breakdown' => $platform_stats,
            'viral_coefficients' => $coefficients,
            'performance_grade' => $this->calculate_performance_grade($stats)
        );
    }
    
    /**
     * Calculate performance grade
     */
    private function calculate_performance_grade($stats) {
        $score = 0;
        
        // Viral coefficient (40% weight)
        if ($stats->viral_coefficient >= 1.0) $score += 40;
        elseif ($stats->viral_coefficient >= 0.5) $score += 30;
        elseif ($stats->viral_coefficient >= 0.3) $score += 20;
        elseif ($stats->viral_coefficient >= 0.1) $score += 10;
        
        // Engagement rate (30% weight)
        if ($stats->engagement_rate >= 0.1) $score += 30;
        elseif ($stats->engagement_rate >= 0.05) $score += 20;
        elseif ($stats->engagement_rate >= 0.02) $score += 10;
        
        // Share volume (20% weight)
        if ($stats->share_count >= 100) $score += 20;
        elseif ($stats->share_count >= 50) $score += 15;
        elseif ($stats->share_count >= 25) $score += 10;
        elseif ($stats->share_count >= 10) $score += 5;
        
        // Conversion rate (10% weight)
        $conversion_rate = $stats->click_count > 0 ? $stats->conversion_count / $stats->click_count : 0;
        if ($conversion_rate >= 0.1) $score += 10;
        elseif ($conversion_rate >= 0.05) $score += 7;
        elseif ($conversion_rate >= 0.02) $score += 5;
        
        // Grade assignment
        if ($score >= 90) return 'A+';
        elseif ($score >= 80) return 'A';
        elseif ($score >= 70) return 'B+';
        elseif ($score >= 60) return 'B';
        elseif ($score >= 50) return 'C+';
        elseif ($score >= 40) return 'C';
        elseif ($score >= 30) return 'D';
        else return 'F';
    }
    
    /**
     * Initialize content tracking
     */
    public function initialize_content_tracking($content_id, $content_type) {
        $existing = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT id FROM {$this->tables['viral_content']} 
                 WHERE content_id = %d AND content_type = %s",
                $content_id,
                $content_type
            )
        );
        
        if (!$existing) {
            $this->wpdb->insert(
                $this->tables['viral_content'],
                array(
                    'content_id' => $content_id,
                    'content_type' => $content_type,
                    'created_at' => current_time('mysql')
                )
            );
        }
    }
    
    /**
     * Render viral content widget
     */
    public function render_viral_content($atts) {
        $limit = intval($atts['limit']);
        $period = sanitize_text_field($atts['period']);
        $content_type = sanitize_text_field($atts['content_type']);
        $display_type = sanitize_text_field($atts['display_type']);
        
        $period_condition = $this->get_period_condition($period);
        
        $where_clause = "";
        if ($content_type !== 'any') {
            $where_clause = $this->wpdb->prepare("AND vc.content_type = %s", $content_type);
        }
        
        $viral_content = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT vc.*, p.post_title, p.post_excerpt
                 FROM {$this->tables['viral_content']} vc
                 LEFT JOIN {$this->wpdb->posts} p ON vc.content_id = p.ID
                 WHERE vc.trending_score > 0 {$where_clause}
                 ORDER BY vc.trending_score DESC
                 LIMIT %d",
                $limit
            )
        );
        
        ob_start();
        include ENV_SOCIAL_VIRAL_PLUGIN_PATH . 'templates/viral-content-widget.php';
        return ob_get_clean();
    }
    
    /**
     * Get period condition for queries
     */
    private function get_period_condition($period) {
        switch ($period) {
            case '1day':
                return "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
            case '7days':
                return "AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            case '30days':
                return "AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            case '90days':
                return "AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
            default:
                return "";
        }
    }
    
    /**
     * Get trending content
     */
    public function get_trending_content($limit = 10, $content_type = 'any') {
        $where_clause = "";
        if ($content_type !== 'any') {
            $where_clause = $this->wpdb->prepare("AND content_type = %s", $content_type);
        }
        
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tables['viral_content']} 
                 WHERE trending_score > 0 {$where_clause}
                 ORDER BY trending_score DESC
                 LIMIT %d",
                $limit
            )
        );
    }
}
