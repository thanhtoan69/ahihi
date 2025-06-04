<?php

class Personal_Environmental_Dashboard {
    
    private $wpdb;
    private $table_prefix;
    private $user_id;
    
    public function __construct($user_id = null) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_prefix = $wpdb->prefix . 'env_';
        $this->user_id = $user_id ?? get_current_user_id();
    }
    
    /**
     * Get user's environmental dashboard data
     */
    public function get_dashboard_data($period = 'month') {
        return [
            'overview' => $this->get_overview_stats($period),
            'carbon_footprint' => $this->get_carbon_footprint_data($period),
            'goals_progress' => $this->get_goals_progress(),
            'achievements' => $this->get_achievements(),
            'recommendations' => $this->get_personalized_recommendations(),
            'trends' => $this->get_trend_analysis($period),
            'comparisons' => $this->get_peer_comparisons($period),
            'upcoming_goals' => $this->get_upcoming_goals(),
            'environmental_impact' => $this->calculate_environmental_impact($period)
        ];
    }
    
    /**
     * Get overview statistics for the user
     */
    public function get_overview_stats($period = 'month') {
        $days = $period === 'week' ? 7 : ($period === 'year' ? 365 : 30);
        
        // Carbon footprint overview
        $carbon_stats = $this->wpdb->get_row($this->wpdb->prepare("
            SELECT 
                SUM(emission_amount) as total_emissions,
                AVG(emission_amount) as avg_emission_per_entry,
                COUNT(*) as total_entries,
                COUNT(DISTINCT category) as categories_tracked,
                COUNT(DISTINCT DATE(recorded_at)) as active_days
            FROM {$this->table_prefix}carbon_footprint 
            WHERE user_id = %d 
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $this->user_id, $days));
        
        // Goal completion rate
        $goals_stats = $this->wpdb->get_row($this->wpdb->prepare("
            SELECT 
                COUNT(*) as total_goals,
                SUM(CASE WHEN status = 'achieved' THEN 1 ELSE 0 END) as completed_goals,
                AVG(CASE WHEN target_amount > 0 THEN (current_amount / target_amount) * 100 ELSE 0 END) as avg_progress
            FROM {$this->table_prefix}user_goals 
            WHERE user_id = %d 
            AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $this->user_id, $days));
        
        // Calculate improvement from previous period
        $previous_emissions = $this->wpdb->get_var($this->wpdb->prepare("
            SELECT SUM(emission_amount)
            FROM {$this->table_prefix}carbon_footprint 
            WHERE user_id = %d 
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            AND recorded_at < DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $this->user_id, $days * 2, $days));
        
        $improvement_percentage = 0;
        if ($previous_emissions > 0 && $carbon_stats->total_emissions > 0) {
            $improvement_percentage = (($previous_emissions - $carbon_stats->total_emissions) / $previous_emissions) * 100;
        }
        
        return [
            'total_emissions' => round($carbon_stats->total_emissions ?? 0, 2),
            'avg_daily_emissions' => round(($carbon_stats->total_emissions ?? 0) / max($carbon_stats->active_days ?? 1, 1), 2),
            'total_entries' => $carbon_stats->total_entries ?? 0,
            'categories_tracked' => $carbon_stats->categories_tracked ?? 0,
            'active_days' => $carbon_stats->active_days ?? 0,
            'goal_completion_rate' => $goals_stats->total_goals > 0 ? round(($goals_stats->completed_goals / $goals_stats->total_goals) * 100, 2) : 0,
            'avg_goal_progress' => round($goals_stats->avg_progress ?? 0, 2),
            'improvement_percentage' => round($improvement_percentage, 2),
            'streak_days' => $this->calculate_tracking_streak(),
            'environmental_score' => $this->calculate_environmental_score()
        ];
    }
    
    /**
     * Get detailed carbon footprint data
     */
    public function get_carbon_footprint_data($period = 'month') {
        $days = $period === 'week' ? 7 : ($period === 'year' ? 365 : 30);
        
        // Category breakdown
        $category_breakdown = $this->wpdb->get_results($this->wpdb->prepare("
            SELECT 
                category,
                SUM(emission_amount) as total_emissions,
                AVG(emission_amount) as avg_emissions,
                COUNT(*) as entry_count,
                MAX(recorded_at) as last_entry
            FROM {$this->table_prefix}carbon_footprint 
            WHERE user_id = %d 
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY category
            ORDER BY total_emissions DESC
        ", $this->user_id, $days));
        
        // Daily breakdown for trend analysis
        $daily_breakdown = $this->wpdb->get_results($this->wpdb->prepare("
            SELECT 
                DATE(recorded_at) as date,
                SUM(emission_amount) as daily_emissions,
                COUNT(*) as entry_count
            FROM {$this->table_prefix}carbon_footprint 
            WHERE user_id = %d 
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(recorded_at)
            ORDER BY date ASC
        ", $this->user_id, $days));
        
        // Recent activities
        $recent_activities = $this->wpdb->get_results($this->wpdb->prepare("
            SELECT 
                category,
                emission_amount,
                activity_description,
                recorded_at
            FROM {$this->table_prefix}carbon_footprint 
            WHERE user_id = %d 
            ORDER BY recorded_at DESC
            LIMIT 10
        ", $this->user_id));
        
        return [
            'category_breakdown' => $category_breakdown,
            'daily_breakdown' => $daily_breakdown,
            'recent_activities' => $recent_activities,
            'highest_emission_day' => $this->get_highest_emission_day($days),
            'lowest_emission_day' => $this->get_lowest_emission_day($days),
            'category_trends' => $this->analyze_category_trends($days)
        ];
    }
    
    /**
     * Get user's goals progress
     */
    public function get_goals_progress() {
        $goals = $this->wpdb->get_results($this->wpdb->prepare("
            SELECT 
                id,
                goal_type,
                target_amount,
                current_amount,
                target_date,
                status,
                created_at,
                CASE 
                    WHEN target_amount > 0 THEN (current_amount / target_amount) * 100 
                    ELSE 0 
                END as progress_percentage
            FROM {$this->table_prefix}user_goals 
            WHERE user_id = %d 
            ORDER BY 
                CASE status 
                    WHEN 'active' THEN 1 
                    WHEN 'achieved' THEN 2 
                    ELSE 3 
                END,
                target_date ASC
        ", $this->user_id));
        
        $active_goals = array_filter($goals, function($goal) {
            return $goal->status === 'active';
        });
        
        $completed_goals = array_filter($goals, function($goal) {
            return $goal->status === 'achieved';
        });
        
        return [
            'all_goals' => $goals,
            'active_goals' => array_values($active_goals),
            'completed_goals' => array_values($completed_goals),
            'completion_rate' => count($goals) > 0 ? round((count($completed_goals) / count($goals)) * 100, 2) : 0,
            'overdue_goals' => $this->get_overdue_goals(),
            'goals_by_category' => $this->group_goals_by_category($goals)
        ];
    }
    
    /**
     * Get user achievements
     */
    public function get_achievements() {
        $achievements = [];
        
        // Tracking consistency achievements
        $streak = $this->calculate_tracking_streak();
        if ($streak >= 7) $achievements[] = ['type' => 'streak', 'title' => 'Week Warrior', 'description' => '7 days tracking streak'];
        if ($streak >= 30) $achievements[] = ['type' => 'streak', 'title' => 'Monthly Master', 'description' => '30 days tracking streak'];
        if ($streak >= 100) $achievements[] = ['type' => 'streak', 'title' => 'Century Tracker', 'description' => '100 days tracking streak'];
        
        // Emission reduction achievements
        $improvement = $this->get_overview_stats()['improvement_percentage'];
        if ($improvement >= 10) $achievements[] = ['type' => 'reduction', 'title' => 'Eco Improver', 'description' => '10% emission reduction'];
        if ($improvement >= 25) $achievements[] = ['type' => 'reduction', 'title' => 'Green Champion', 'description' => '25% emission reduction'];
        if ($improvement >= 50) $achievements[] = ['type' => 'reduction', 'title' => 'Climate Hero', 'description' => '50% emission reduction'];
        
        // Goal completion achievements
        $completed_goals = count($this->wpdb->get_results($this->wpdb->prepare("
            SELECT id FROM {$this->table_prefix}user_goals 
            WHERE user_id = %d AND status = 'achieved'
        ", $this->user_id)));
        
        if ($completed_goals >= 1) $achievements[] = ['type' => 'goals', 'title' => 'Goal Getter', 'description' => 'First goal completed'];
        if ($completed_goals >= 5) $achievements[] = ['type' => 'goals', 'title' => 'Goal Master', 'description' => '5 goals completed'];
        if ($completed_goals >= 10) $achievements[] = ['type' => 'goals', 'title' => 'Achievement Unlocked', 'description' => '10 goals completed'];
        
        // Category diversity achievements
        $categories_tracked = $this->wpdb->get_var($this->wpdb->prepare("
            SELECT COUNT(DISTINCT category)
            FROM {$this->table_prefix}carbon_footprint 
            WHERE user_id = %d
        ", $this->user_id));
        
        if ($categories_tracked >= 3) $achievements[] = ['type' => 'diversity', 'title' => 'Well Rounded', 'description' => 'Tracking 3+ categories'];
        if ($categories_tracked >= 5) $achievements[] = ['type' => 'diversity', 'title' => 'Complete Tracker', 'description' => 'Tracking all categories'];
        
        return $achievements;
    }
    
    /**
     * Get personalized recommendations
     */
    public function get_personalized_recommendations() {
        $recommendations = [];
        
        // Analyze user's highest emission categories
        $top_categories = $this->wpdb->get_results($this->wpdb->prepare("
            SELECT 
                category,
                SUM(emission_amount) as total_emissions,
                COUNT(*) as entry_count
            FROM {$this->table_prefix}carbon_footprint 
            WHERE user_id = %d 
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY category
            ORDER BY total_emissions DESC
            LIMIT 3
        ", $this->user_id));
        
        foreach ($top_categories as $category) {
            $recommendations[] = $this->get_category_recommendations($category->category, $category->total_emissions);
        }
        
        // Check for tracking gaps
        $missing_categories = $this->get_missing_categories();
        if (!empty($missing_categories)) {
            $recommendations[] = [
                'type' => 'tracking',
                'priority' => 'medium',
                'title' => 'Complete Your Profile',
                'description' => 'Start tracking ' . implode(', ', $missing_categories) . ' to get a complete picture of your environmental impact.',
                'action' => 'Add entries for missing categories'
            ];
        }
        
        // Goal-based recommendations
        $goals_without_progress = $this->wpdb->get_results($this->wpdb->prepare("
            SELECT goal_type, target_amount, target_date
            FROM {$this->table_prefix}user_goals 
            WHERE user_id = %d 
            AND status = 'active'
            AND current_amount = 0
            AND target_date > NOW()
        ", $this->user_id));
        
        foreach ($goals_without_progress as $goal) {
            $recommendations[] = [
                'type' => 'goal',
                'priority' => 'high',
                'title' => 'Start Working on Your Goal',
                'description' => "Your {$goal->goal_type} goal needs attention. Start taking action to reach your target.",
                'action' => 'Log activities related to your goal'
            ];
        }
        
        return array_slice($recommendations, 0, 5); // Limit to top 5 recommendations
    }
    
    /**
     * Get trend analysis for the user
     */
    public function get_trend_analysis($period = 'month') {
        $days = $period === 'week' ? 7 : ($period === 'year' ? 365 : 30);
        
        // Overall trend
        $trend_data = $this->wpdb->get_results($this->wpdb->prepare("
            SELECT 
                DATE(recorded_at) as date,
                SUM(emission_amount) as daily_emissions
            FROM {$this->table_prefix}carbon_footprint 
            WHERE user_id = %d 
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(recorded_at)
            ORDER BY date ASC
        ", $this->user_id, $days));
        
        // Calculate trend direction
        $trend_direction = $this->calculate_trend_direction($trend_data);
        
        // Category-specific trends
        $category_trends = $this->wpdb->get_results($this->wpdb->prepare("
            SELECT 
                category,
                DATE(recorded_at) as date,
                SUM(emission_amount) as daily_emissions
            FROM {$this->table_prefix}carbon_footprint 
            WHERE user_id = %d 
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY category, DATE(recorded_at)
            ORDER BY category, date ASC
        ", $this->user_id, $days));
        
        return [
            'overall_trend' => $trend_direction,
            'trend_data' => $trend_data,
            'category_trends' => $this->group_category_trends($category_trends),
            'trend_analysis' => $this->analyze_trends($trend_data),
            'prediction' => $this->predict_future_emissions($trend_data)
        ];
    }
    
    /**
     * Get peer comparisons
     */
    public function get_peer_comparisons($period = 'month') {
        $days = $period === 'week' ? 7 : ($period === 'year' ? 365 : 30);
        
        // User's total emissions
        $user_emissions = $this->wpdb->get_var($this->wpdb->prepare("
            SELECT SUM(emission_amount)
            FROM {$this->table_prefix}carbon_footprint 
            WHERE user_id = %d 
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $this->user_id, $days));
        
        // Community average
        $community_avg = $this->wpdb->get_var($this->wpdb->prepare("
            SELECT AVG(user_total)
            FROM (
                SELECT user_id, SUM(emission_amount) as user_total
                FROM {$this->table_prefix}carbon_footprint 
                WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
                GROUP BY user_id
            ) as user_totals
        ", $days));
        
        // User's rank
        $user_rank = $this->wpdb->get_var($this->wpdb->prepare("
            SELECT rank_position
            FROM (
                SELECT 
                    user_id,
                    SUM(emission_amount) as total_emissions,
                    RANK() OVER (ORDER BY SUM(emission_amount) ASC) as rank_position
                FROM {$this->table_prefix}carbon_footprint 
                WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
                GROUP BY user_id
            ) as ranked_users
            WHERE user_id = %d
        ", $days, $this->user_id));
        
        // Category comparisons
        $category_comparisons = $this->wpdb->get_results($this->wpdb->prepare("
            SELECT 
                cf.category,
                SUM(cf.emission_amount) as user_emissions,
                (
                    SELECT AVG(category_total)
                    FROM (
                        SELECT user_id, SUM(emission_amount) as category_total
                        FROM {$this->table_prefix}carbon_footprint 
                        WHERE category = cf.category
                        AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
                        GROUP BY user_id
                    ) as category_totals
                ) as category_avg
            FROM {$this->table_prefix}carbon_footprint cf
            WHERE cf.user_id = %d 
            AND cf.recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY cf.category
        ", $days, $this->user_id, $days));
        
        return [
            'user_emissions' => round($user_emissions ?? 0, 2),
            'community_average' => round($community_avg ?? 0, 2),
            'user_rank' => $user_rank ?? 0,
            'percentile' => $this->calculate_percentile($user_emissions, $days),
            'category_comparisons' => $category_comparisons,
            'better_than_percentage' => $this->calculate_better_than_percentage($user_rank)
        ];
    }
    
    /**
     * Helper functions
     */
    private function calculate_tracking_streak() {
        $results = $this->wpdb->get_results($this->wpdb->prepare("
            SELECT DISTINCT DATE(recorded_at) as tracking_date
            FROM {$this->table_prefix}carbon_footprint 
            WHERE user_id = %d 
            ORDER BY tracking_date DESC
        ", $this->user_id));
        
        if (empty($results)) return 0;
        
        $streak = 1;
        $current_date = new DateTime($results[0]->tracking_date);
        
        for ($i = 1; $i < count($results); $i++) {
            $prev_date = new DateTime($results[$i]->tracking_date);
            $diff = $current_date->diff($prev_date)->days;
            
            if ($diff == 1) {
                $streak++;
                $current_date = $prev_date;
            } else {
                break;
            }
        }
        
        return $streak;
    }
    
    private function calculate_environmental_score() {
        // Simple scoring algorithm based on various factors
        $score = 100; // Start with perfect score
        
        // Deduct points based on emissions (lower is better)
        $monthly_emissions = $this->wpdb->get_var($this->wpdb->prepare("
            SELECT SUM(emission_amount)
            FROM {$this->table_prefix}carbon_footprint 
            WHERE user_id = %d 
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ", $this->user_id));
        
        $score -= min(50, ($monthly_emissions / 100) * 10); // Deduct up to 50 points
        
        // Add points for consistency
        $streak = $this->calculate_tracking_streak();
        $score += min(20, $streak * 0.5);
        
        // Add points for goal achievement
        $goal_completion_rate = $this->get_goals_progress()['completion_rate'];
        $score += ($goal_completion_rate / 100) * 30;
        
        return max(0, min(100, round($score, 1)));
    }
    
    private function get_highest_emission_day($days) {
        return $this->wpdb->get_row($this->wpdb->prepare("
            SELECT 
                DATE(recorded_at) as date,
                SUM(emission_amount) as total_emissions
            FROM {$this->table_prefix}carbon_footprint 
            WHERE user_id = %d 
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(recorded_at)
            ORDER BY total_emissions DESC
            LIMIT 1
        ", $this->user_id, $days));
    }
    
    private function get_lowest_emission_day($days) {
        return $this->wpdb->get_row($this->wpdb->prepare("
            SELECT 
                DATE(recorded_at) as date,
                SUM(emission_amount) as total_emissions
            FROM {$this->table_prefix}carbon_footprint 
            WHERE user_id = %d 
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(recorded_at)
            ORDER BY total_emissions ASC
            LIMIT 1
        ", $this->user_id, $days));
    }
    
    private function analyze_category_trends($days) {
        // Analyze trends for each category
        $categories = ['transportation', 'energy', 'food', 'waste', 'consumption'];
        $trends = [];
        
        foreach ($categories as $category) {
            $category_data = $this->wpdb->get_results($this->wpdb->prepare("
                SELECT 
                    DATE(recorded_at) as date,
                    SUM(emission_amount) as daily_emissions
                FROM {$this->table_prefix}carbon_footprint 
                WHERE user_id = %d 
                AND category = %s
                AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
                GROUP BY DATE(recorded_at)
                ORDER BY date ASC
            ", $this->user_id, $category, $days));
            
            $trends[$category] = $this->calculate_trend_direction($category_data);
        }
        
        return $trends;
    }
    
    private function get_upcoming_goals() {
        return $this->wpdb->get_results($this->wpdb->prepare("
            SELECT *
            FROM {$this->table_prefix}user_goals 
            WHERE user_id = %d 
            AND status = 'active'
            AND target_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)
            ORDER BY target_date ASC
        ", $this->user_id));
    }
    
    private function calculate_environmental_impact($period) {
        $days = $period === 'week' ? 7 : ($period === 'year' ? 365 : 30);
        
        $total_emissions = $this->wpdb->get_var($this->wpdb->prepare("
            SELECT SUM(emission_amount)
            FROM {$this->table_prefix}carbon_footprint 
            WHERE user_id = %d 
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $this->user_id, $days));
        
        // Convert to meaningful equivalents
        $trees_needed = round($total_emissions / 21.77, 1); // 1 tree absorbs ~21.77 kg CO2/year
        $miles_driven = round($total_emissions / 0.404, 1); // ~0.404 kg CO2 per mile
        $coal_burned = round($total_emissions / 2.23, 1); // ~2.23 kg CO2 per kg coal
        
        return [
            'total_co2_kg' => round($total_emissions ?? 0, 2),
            'trees_to_offset' => $trees_needed,
            'equivalent_miles_driven' => $miles_driven,
            'equivalent_coal_kg' => $coal_burned,
            'impact_level' => $this->categorize_impact_level($total_emissions)
        ];
    }
    
    private function get_overdue_goals() {
        return $this->wpdb->get_results($this->wpdb->prepare("
            SELECT *
            FROM {$this->table_prefix}user_goals 
            WHERE user_id = %d 
            AND status = 'active'
            AND target_date < NOW()
            ORDER BY target_date ASC
        ", $this->user_id));
    }
    
    private function group_goals_by_category($goals) {
        $grouped = [];
        foreach ($goals as $goal) {
            $grouped[$goal->goal_type][] = $goal;
        }
        return $grouped;
    }
    
    private function get_category_recommendations($category, $emissions) {
        $recommendations_map = [
            'transportation' => [
                'type' => 'reduction',
                'priority' => 'high',
                'title' => 'Reduce Transportation Emissions',
                'description' => 'Transportation is your highest emission source. Consider carpooling, public transit, or cycling.',
                'action' => 'Try one car-free day per week'
            ],
            'energy' => [
                'type' => 'reduction',
                'priority' => 'high',
                'title' => 'Energy Efficiency at Home',
                'description' => 'Home energy use is significant. Consider LED bulbs, smart thermostats, or energy-efficient appliances.',
                'action' => 'Audit your home energy usage'
            ],
            'food' => [
                'type' => 'reduction',
                'priority' => 'medium',
                'title' => 'Sustainable Diet Choices',
                'description' => 'Food choices impact your carbon footprint. Try reducing meat consumption or buying local produce.',
                'action' => 'Plan one plant-based meal per day'
            ]
        ];
        
        return $recommendations_map[$category] ?? [
            'type' => 'general',
            'priority' => 'medium',
            'title' => 'Reduce ' . ucfirst($category) . ' Impact',
            'description' => 'Look for ways to reduce emissions in the ' . $category . ' category.',
            'action' => 'Research sustainable alternatives'
        ];
    }
    
    private function get_missing_categories() {
        $all_categories = ['transportation', 'energy', 'food', 'waste', 'consumption'];
        $tracked_categories = $this->wpdb->get_col($this->wpdb->prepare("
            SELECT DISTINCT category
            FROM {$this->table_prefix}carbon_footprint 
            WHERE user_id = %d
        ", $this->user_id));
        
        return array_diff($all_categories, $tracked_categories);
    }
    
    private function calculate_trend_direction($data) {
        if (count($data) < 2) return 'stable';
        
        $first_half = array_slice($data, 0, floor(count($data) / 2));
        $second_half = array_slice($data, ceil(count($data) / 2));
        
        $first_avg = array_sum(array_column($first_half, 'daily_emissions')) / count($first_half);
        $second_avg = array_sum(array_column($second_half, 'daily_emissions')) / count($second_half);
        
        $change_percentage = $first_avg > 0 ? (($second_avg - $first_avg) / $first_avg) * 100 : 0;
        
        if ($change_percentage > 10) return 'increasing';
        elseif ($change_percentage < -10) return 'decreasing';
        else return 'stable';
    }
    
    private function group_category_trends($category_trends) {
        $grouped = [];
        foreach ($category_trends as $trend) {
            $grouped[$trend->category][] = $trend;
        }
        return $grouped;
    }
    
    private function analyze_trends($trend_data) {
        // Simple trend analysis
        if (empty($trend_data)) return 'No data available';
        
        $direction = $this->calculate_trend_direction($trend_data);
        $volatility = $this->calculate_volatility($trend_data);
        
        return [
            'direction' => $direction,
            'volatility' => $volatility,
            'summary' => $this->generate_trend_summary($direction, $volatility)
        ];
    }
    
    private function predict_future_emissions($trend_data) {
        if (count($trend_data) < 7) return null;
        
        // Simple linear prediction based on recent trend
        $recent_data = array_slice($trend_data, -7);
        $emissions = array_column($recent_data, 'daily_emissions');
        $avg_change = (end($emissions) - $emissions[0]) / count($emissions);
        
        return [
            'next_week_prediction' => round(end($emissions) + ($avg_change * 7), 2),
            'trend_confidence' => min(100, max(0, 100 - ($this->calculate_volatility($trend_data) * 10)))
        ];
    }
    
    private function calculate_percentile($user_emissions, $days) {
        $users_below = $this->wpdb->get_var($this->wpdb->prepare("
            SELECT COUNT(*)
            FROM (
                SELECT user_id, SUM(emission_amount) as total_emissions
                FROM {$this->table_prefix}carbon_footprint 
                WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
                GROUP BY user_id
                HAVING total_emissions > %f
            ) as users_with_higher_emissions
        ", $days, $user_emissions));
        
        $total_users = $this->wpdb->get_var($this->wpdb->prepare("
            SELECT COUNT(DISTINCT user_id)
            FROM {$this->table_prefix}carbon_footprint 
            WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $days));
        
        return $total_users > 0 ? round(($users_below / $total_users) * 100, 1) : 50;
    }
    
    private function calculate_better_than_percentage($rank) {
        $total_users = $this->wpdb->get_var("
            SELECT COUNT(DISTINCT user_id)
            FROM {$this->table_prefix}carbon_footprint 
            WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        return $total_users > 0 ? round((($total_users - $rank) / $total_users) * 100, 1) : 0;
    }
    
    private function calculate_volatility($data) {
        if (count($data) < 2) return 0;
        
        $emissions = array_column($data, 'daily_emissions');
        $mean = array_sum($emissions) / count($emissions);
        $variance = array_sum(array_map(function($x) use ($mean) { return pow($x - $mean, 2); }, $emissions)) / count($emissions);
        
        return sqrt($variance) / max($mean, 1);
    }
    
    private function generate_trend_summary($direction, $volatility) {
        $direction_text = [
            'increasing' => 'Your emissions are trending upward',
            'decreasing' => 'Great! Your emissions are trending downward',
            'stable' => 'Your emissions are relatively stable'
        ];
        
        $volatility_text = $volatility > 0.5 ? ' with high variability' : ' with consistent patterns';
        
        return $direction_text[$direction] . $volatility_text . '.';
    }
    
    private function categorize_impact_level($emissions) {
        if ($emissions < 100) return 'Low Impact';
        elseif ($emissions < 500) return 'Moderate Impact';
        elseif ($emissions < 1000) return 'High Impact';
        else return 'Very High Impact';
    }
}

?>
