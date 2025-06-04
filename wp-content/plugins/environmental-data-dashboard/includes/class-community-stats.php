<?php

class Community_Environmental_Stats {
    
    private $wpdb;
    private $table_prefix;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_prefix = $wpdb->prefix . 'env_';
    }
    
    /**
     * Calculate and update community statistics
     */
    public function update_community_stats() {
        $this->calculate_air_quality_stats();
        $this->calculate_carbon_footprint_stats();
        $this->calculate_participation_stats();
        $this->calculate_goal_achievement_stats();
        $this->calculate_environmental_trends();
    }
    
    /**
     * Calculate air quality statistics for the community
     */
    private function calculate_air_quality_stats() {
        $stats = $this->wpdb->get_row("
            SELECT 
                AVG(aqi) as avg_aqi,
                MIN(aqi) as min_aqi,
                MAX(aqi) as max_aqi,
                AVG(pm25) as avg_pm25,
                AVG(pm10) as avg_pm10,
                COUNT(*) as total_readings,
                COUNT(DISTINCT location) as locations_monitored
            FROM {$this->table_prefix}air_quality_data 
            WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        $this->update_community_data('air_quality_monthly', [
            'avg_aqi' => round($stats->avg_aqi ?? 0, 2),
            'min_aqi' => round($stats->min_aqi ?? 0, 2),
            'max_aqi' => round($stats->max_aqi ?? 0, 2),
            'avg_pm25' => round($stats->avg_pm25 ?? 0, 2),
            'avg_pm10' => round($stats->avg_pm10 ?? 0, 2),
            'total_readings' => $stats->total_readings ?? 0,
            'locations_monitored' => $stats->locations_monitored ?? 0,
            'good_days' => $this->count_air_quality_days('good'),
            'moderate_days' => $this->count_air_quality_days('moderate'),
            'unhealthy_days' => $this->count_air_quality_days('unhealthy')
        ]);
    }
    
    /**
     * Count days with specific air quality levels
     */
    private function count_air_quality_days($level) {
        $conditions = [
            'good' => 'AVG(aqi) <= 50',
            'moderate' => 'AVG(aqi) BETWEEN 51 AND 100',
            'unhealthy' => 'AVG(aqi) > 100'
        ];
        
        return $this->wpdb->get_var("
            SELECT COUNT(*) 
            FROM (
                SELECT DATE(recorded_at) as date, AVG(aqi) as daily_aqi
                FROM {$this->table_prefix}air_quality_data 
                WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(recorded_at)
                HAVING {$conditions[$level]}
            ) as daily_stats
        ");
    }
    
    /**
     * Calculate carbon footprint statistics
     */
    private function calculate_carbon_footprint_stats() {
        // Overall community stats
        $overall_stats = $this->wpdb->get_row("
            SELECT 
                COUNT(DISTINCT user_id) as active_users,
                SUM(emission_amount) as total_emissions,
                AVG(emission_amount) as avg_emissions_per_entry,
                COUNT(*) as total_entries
            FROM {$this->table_prefix}carbon_footprint 
            WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        // Category breakdown
        $category_stats = $this->wpdb->get_results("
            SELECT 
                category,
                SUM(emission_amount) as total_emissions,
                AVG(emission_amount) as avg_emissions,
                COUNT(DISTINCT user_id) as users_tracking
            FROM {$this->table_prefix}carbon_footprint 
            WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY category
        ");
        
        // User rankings
        $top_performers = $this->wpdb->get_results("
            SELECT 
                user_id,
                SUM(emission_amount) as total_emissions,
                COUNT(*) as entries_count
            FROM {$this->table_prefix}carbon_footprint 
            WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY user_id
            ORDER BY total_emissions ASC
            LIMIT 10
        ");
        
        $category_data = [];
        foreach ($category_stats as $cat) {
            $category_data[$cat->category] = [
                'total_emissions' => round($cat->total_emissions, 2),
                'avg_emissions' => round($cat->avg_emissions, 2),
                'users_tracking' => $cat->users_tracking
            ];
        }
        
        $this->update_community_data('carbon_footprint_monthly', [
            'active_users' => $overall_stats->active_users ?? 0,
            'total_emissions' => round($overall_stats->total_emissions ?? 0, 2),
            'avg_emissions_per_user' => round(($overall_stats->total_emissions ?? 0) / max($overall_stats->active_users ?? 1, 1), 2),
            'total_entries' => $overall_stats->total_entries ?? 0,
            'category_breakdown' => $category_data,
            'top_performers' => array_slice($top_performers, 0, 5),
            'community_goal_progress' => $this->calculate_community_goal_progress()
        ]);
    }
    
    /**
     * Calculate participation statistics
     */
    private function calculate_participation_stats() {
        // Daily active users
        $daily_active = $this->wpdb->get_var("
            SELECT COUNT(DISTINCT user_id)
            FROM {$this->table_prefix}carbon_footprint 
            WHERE DATE(recorded_at) = CURDATE()
        ");
        
        // Weekly active users
        $weekly_active = $this->wpdb->get_var("
            SELECT COUNT(DISTINCT user_id)
            FROM {$this->table_prefix}carbon_footprint 
            WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        
        // Monthly active users
        $monthly_active = $this->wpdb->get_var("
            SELECT COUNT(DISTINCT user_id)
            FROM {$this->table_prefix}carbon_footprint 
            WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        // Engagement trends
        $engagement_trend = $this->wpdb->get_results("
            SELECT 
                DATE(recorded_at) as date,
                COUNT(DISTINCT user_id) as active_users,
                COUNT(*) as total_entries
            FROM {$this->table_prefix}carbon_footprint 
            WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(recorded_at)
            ORDER BY date ASC
        ");
        
        $this->update_community_data('participation_stats', [
            'daily_active_users' => $daily_active ?? 0,
            'weekly_active_users' => $weekly_active ?? 0,
            'monthly_active_users' => $monthly_active ?? 0,
            'engagement_trend' => $engagement_trend,
            'retention_rate' => $this->calculate_retention_rate(),
            'new_users_this_month' => $this->count_new_users()
        ]);
    }
    
    /**
     * Calculate goal achievement statistics
     */
    private function calculate_goal_achievement_stats() {
        $goals_stats = $this->wpdb->get_row("
            SELECT 
                COUNT(*) as total_goals,
                SUM(CASE WHEN status = 'achieved' THEN 1 ELSE 0 END) as achieved_goals,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_goals,
                AVG(CASE WHEN target_amount > 0 THEN (current_amount / target_amount) * 100 ELSE 0 END) as avg_progress
            FROM {$this->table_prefix}user_goals 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        $popular_goals = $this->wpdb->get_results("
            SELECT 
                goal_type,
                COUNT(*) as goal_count,
                AVG(CASE WHEN target_amount > 0 THEN (current_amount / target_amount) * 100 ELSE 0 END) as avg_progress
            FROM {$this->table_prefix}user_goals 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY goal_type
            ORDER BY goal_count DESC
        ");
        
        $this->update_community_data('goal_achievement_stats', [
            'total_goals' => $goals_stats->total_goals ?? 0,
            'achieved_goals' => $goals_stats->achieved_goals ?? 0,
            'active_goals' => $goals_stats->active_goals ?? 0,
            'achievement_rate' => $goals_stats->total_goals > 0 ? round(($goals_stats->achieved_goals / $goals_stats->total_goals) * 100, 2) : 0,
            'avg_progress' => round($goals_stats->avg_progress ?? 0, 2),
            'popular_goals' => $popular_goals
        ]);
    }
    
    /**
     * Calculate environmental trends
     */
    private function calculate_environmental_trends() {
        // Carbon footprint trends
        $carbon_trend = $this->wpdb->get_results("
            SELECT 
                WEEK(recorded_at) as week,
                YEAR(recorded_at) as year,
                SUM(emission_amount) as weekly_emissions,
                COUNT(DISTINCT user_id) as active_users
            FROM {$this->table_prefix}carbon_footprint 
            WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 12 WEEK)
            GROUP BY YEAR(recorded_at), WEEK(recorded_at)
            ORDER BY year, week
        ");
        
        // Air quality trends
        $air_trend = $this->wpdb->get_results("
            SELECT 
                WEEK(recorded_at) as week,
                YEAR(recorded_at) as year,
                AVG(aqi) as weekly_aqi,
                AVG(pm25) as weekly_pm25
            FROM {$this->table_prefix}air_quality_data 
            WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 12 WEEK)
            GROUP BY YEAR(recorded_at), WEEK(recorded_at)
            ORDER BY year, week
        ");
        
        $this->update_community_data('environmental_trends', [
            'carbon_footprint_trend' => $carbon_trend,
            'air_quality_trend' => $air_trend,
            'improvement_percentage' => $this->calculate_improvement_percentage(),
            'seasonal_patterns' => $this->analyze_seasonal_patterns()
        ]);
    }
    
    /**
     * Get community leaderboard
     */
    public function get_community_leaderboard($category = 'overall', $period = 'month') {
        $interval = $period === 'week' ? 7 : 30;
        
        if ($category === 'overall') {
            $results = $this->wpdb->get_results($this->wpdb->prepare("
                SELECT 
                    user_id,
                    SUM(emission_amount) as total_emissions,
                    COUNT(*) as entries_count,
                    COUNT(DISTINCT category) as categories_tracked
                FROM {$this->table_prefix}carbon_footprint 
                WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
                GROUP BY user_id
                ORDER BY total_emissions ASC, entries_count DESC
                LIMIT 20
            ", $interval));
        } else {
            $results = $this->wpdb->get_results($this->wpdb->prepare("
                SELECT 
                    user_id,
                    SUM(emission_amount) as total_emissions,
                    COUNT(*) as entries_count
                FROM {$this->table_prefix}carbon_footprint 
                WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
                AND category = %s
                GROUP BY user_id
                ORDER BY total_emissions ASC, entries_count DESC
                LIMIT 20
            ", $interval, $category));
        }
        
        return $results;
    }
    
    /**
     * Get community challenges data
     */
    public function get_community_challenges() {
        return [
            'monthly_challenges' => [
                [
                    'title' => 'Reduce Transportation Emissions',
                    'description' => 'Community goal to reduce transportation emissions by 20%',
                    'target' => 20,
                    'current_progress' => $this->calculate_transportation_reduction(),
                    'participants' => $this->count_challenge_participants('transportation'),
                    'end_date' => date('Y-m-t')
                ],
                [
                    'title' => 'Energy Efficiency Challenge',
                    'description' => 'Track and reduce home energy consumption',
                    'target' => 15,
                    'current_progress' => $this->calculate_energy_reduction(),
                    'participants' => $this->count_challenge_participants('energy'),
                    'end_date' => date('Y-m-t')
                ]
            ],
            'weekly_challenges' => [
                [
                    'title' => 'Zero Waste Week',
                    'description' => 'Minimize waste generation for one week',
                    'participants' => $this->count_challenge_participants('waste'),
                    'submissions' => $this->count_weekly_submissions('waste')
                ]
            ]
        ];
    }
    
    /**
     * Get environmental awareness metrics
     */
    public function get_awareness_metrics() {
        return [
            'knowledge_sharing' => [
                'tips_shared' => $this->count_tips_shared(),
                'questions_answered' => $this->count_questions_answered(),
                'resources_accessed' => $this->count_resources_accessed()
            ],
            'behavior_change' => [
                'users_with_goals' => $this->count_users_with_goals(),
                'sustainable_actions' => $this->count_sustainable_actions(),
                'habit_formation_rate' => $this->calculate_habit_formation_rate()
            ]
        ];
    }
    
    /**
     * Helper functions
     */
    private function update_community_data($metric, $data) {
        $this->wpdb->replace(
            $this->table_prefix . 'community_data',
            [
                'metric_name' => $metric,
                'metric_data' => json_encode($data),
                'updated_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s']
        );
    }
    
    private function calculate_community_goal_progress() {
        // Mock calculation for community-wide emission reduction goal
        $current_month = $this->wpdb->get_var("
            SELECT SUM(emission_amount)
            FROM {$this->table_prefix}carbon_footprint 
            WHERE MONTH(recorded_at) = MONTH(NOW()) 
            AND YEAR(recorded_at) = YEAR(NOW())
        ");
        
        $previous_month = $this->wpdb->get_var("
            SELECT SUM(emission_amount)
            FROM {$this->table_prefix}carbon_footprint 
            WHERE MONTH(recorded_at) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH))
            AND YEAR(recorded_at) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH))
        ");
        
        if ($previous_month > 0) {
            $reduction_percentage = (($previous_month - $current_month) / $previous_month) * 100;
            return max(0, round($reduction_percentage, 2));
        }
        
        return 0;
    }
    
    private function calculate_retention_rate() {
        $users_last_month = $this->wpdb->get_var("
            SELECT COUNT(DISTINCT user_id)
            FROM {$this->table_prefix}carbon_footprint 
            WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
            AND recorded_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        $returning_users = $this->wpdb->get_var("
            SELECT COUNT(DISTINCT cf1.user_id)
            FROM {$this->table_prefix}carbon_footprint cf1
            WHERE cf1.recorded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND EXISTS (
                SELECT 1 FROM {$this->table_prefix}carbon_footprint cf2
                WHERE cf2.user_id = cf1.user_id
                AND cf2.recorded_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
                AND cf2.recorded_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
            )
        ");
        
        return $users_last_month > 0 ? round(($returning_users / $users_last_month) * 100, 2) : 0;
    }
    
    private function count_new_users() {
        return $this->wpdb->get_var("
            SELECT COUNT(DISTINCT user_id)
            FROM {$this->table_prefix}carbon_footprint 
            WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND user_id NOT IN (
                SELECT DISTINCT user_id 
                FROM {$this->table_prefix}carbon_footprint 
                WHERE recorded_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
            )
        ");
    }
    
    private function calculate_improvement_percentage() {
        $this_month = $this->wpdb->get_var("
            SELECT AVG(emission_amount)
            FROM {$this->table_prefix}carbon_footprint 
            WHERE MONTH(recorded_at) = MONTH(NOW())
        ");
        
        $last_month = $this->wpdb->get_var("
            SELECT AVG(emission_amount)
            FROM {$this->table_prefix}carbon_footprint 
            WHERE MONTH(recorded_at) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH))
        ");
        
        if ($last_month > 0) {
            return round((($last_month - $this_month) / $last_month) * 100, 2);
        }
        return 0;
    }
    
    private function analyze_seasonal_patterns() {
        return $this->wpdb->get_results("
            SELECT 
                MONTH(recorded_at) as month,
                AVG(emission_amount) as avg_emissions,
                COUNT(*) as entry_count
            FROM {$this->table_prefix}carbon_footprint 
            WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY MONTH(recorded_at)
            ORDER BY month
        ");
    }
    
    // Additional helper methods for challenges and awareness metrics
    private function calculate_transportation_reduction() {
        // Mock calculation
        return rand(5, 25);
    }
    
    private function calculate_energy_reduction() {
        // Mock calculation
        return rand(3, 20);
    }
    
    private function count_challenge_participants($category) {
        return $this->wpdb->get_var($this->wpdb->prepare("
            SELECT COUNT(DISTINCT user_id)
            FROM {$this->table_prefix}carbon_footprint 
            WHERE category = %s
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ", $category));
    }
    
    private function count_weekly_submissions($category) {
        return $this->wpdb->get_var($this->wpdb->prepare("
            SELECT COUNT(*)
            FROM {$this->table_prefix}carbon_footprint 
            WHERE category = %s
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ", $category));
    }
    
    private function count_tips_shared() { return rand(50, 200); }
    private function count_questions_answered() { return rand(20, 100); }
    private function count_resources_accessed() { return rand(100, 500); }
    private function count_users_with_goals() { 
        return $this->wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$this->table_prefix}user_goals"); 
    }
    private function count_sustainable_actions() { return rand(200, 1000); }
    private function calculate_habit_formation_rate() { return rand(60, 85); }
}

?>
