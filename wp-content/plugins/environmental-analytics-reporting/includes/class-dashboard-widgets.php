<?php
/**
 * Dashboard Widgets Class
 * 
 * Adds environmental analytics widgets to WordPress dashboard
 * 
 * @package Environmental_Analytics_Reporting
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Dashboard_Widgets {
    
    private $database_manager;
    private $behavior_analytics;
    private $conversion_tracker;
    
    public function __construct($database_manager = null, $behavior_analytics = null, $conversion_tracker = null) {
        $this->database_manager = $database_manager;
        $this->behavior_analytics = $behavior_analytics;
        $this->conversion_tracker = $conversion_tracker;
        
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'));
        add_action('wp_ajax_env_dashboard_widget_data', array($this, 'get_widget_data'));
    }
    
    /**
     * Add dashboard widgets
     */
    public function add_dashboard_widgets() {
        // Environmental analytics overview widget
        wp_add_dashboard_widget(
            'env_analytics_overview',
            '🌱 Environmental Analytics Overview',
            array($this, 'render_analytics_overview_widget')
        );
        
        // Environmental actions widget
        wp_add_dashboard_widget(
            'env_environmental_actions',
            '🌍 Environmental Actions Today',
            array($this, 'render_environmental_actions_widget')
        );
        
        // User engagement widget
        wp_add_dashboard_widget(
            'env_user_engagement',
            '👥 User Engagement Summary',
            array($this, 'render_user_engagement_widget')
        );
        
        // Conversion goals widget
        wp_add_dashboard_widget(
            'env_conversion_goals',
            '🎯 Conversion Goals Status',
            array($this, 'render_conversion_goals_widget')
        );
    }
    
    /**
     * Render analytics overview widget
     */
    public function render_analytics_overview_widget() {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $week_ago = date('Y-m-d', strtotime('-7 days'));
        
        $today_data = $this->get_daily_stats($today);
        $yesterday_data = $this->get_daily_stats($yesterday);
        $week_data = $this->get_period_stats($week_ago, $today);
        
        ?>
        <div class="env-dashboard-widget">
            <div class="env-widget-metrics">
                <div class="env-metric-item">
                    <div class="env-metric-value"><?php echo number_format($today_data['sessions']); ?></div>
                    <div class="env-metric-label">Sessions Today</div>
                    <div class="env-metric-change <?php echo $this->get_change_class($today_data['sessions'], $yesterday_data['sessions']); ?>">
                        <?php echo $this->format_change($today_data['sessions'], $yesterday_data['sessions']); ?>
                    </div>
                </div>
                
                <div class="env-metric-item">
                    <div class="env-metric-value"><?php echo number_format($today_data['users']); ?></div>
                    <div class="env-metric-label">Unique Users</div>
                    <div class="env-metric-change <?php echo $this->get_change_class($today_data['users'], $yesterday_data['users']); ?>">
                        <?php echo $this->format_change($today_data['users'], $yesterday_data['users']); ?>
                    </div>
                </div>
                
                <div class="env-metric-item">
                    <div class="env-metric-value"><?php echo number_format($week_data['page_views']); ?></div>
                    <div class="env-metric-label">Page Views (7 days)</div>
                </div>
                
                <div class="env-metric-item">
                    <div class="env-metric-value"><?php echo gmdate('i:s', $week_data['avg_duration']); ?></div>
                    <div class="env-metric-label">Avg. Session Duration</div>
                </div>
            </div>
            
            <div class="env-widget-actions">
                <a href="<?php echo admin_url('admin.php?page=environmental-analytics'); ?>" class="button button-primary">
                    View Full Analytics
                </a>
                <a href="<?php echo admin_url('admin.php?page=environmental-analytics-reports'); ?>" class="button">
                    Generate Report
                </a>
            </div>
        </div>
        
        <style>
        .env-dashboard-widget {
            padding: 0;
        }
        .env-widget-metrics {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        .env-metric-item {
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 3px solid #4CAF50;
        }
        .env-metric-value {
            font-size: 20px;
            font-weight: bold;
            color: #2c5530;
            margin-bottom: 5px;
        }
        .env-metric-label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .env-metric-change {
            font-size: 10px;
            margin-top: 5px;
            padding: 2px 6px;
            border-radius: 10px;
        }
        .env-metric-change.positive {
            background: #e8f5e8;
            color: #2e7d32;
        }
        .env-metric-change.negative {
            background: #ffebee;
            color: #c62828;
        }
        .env-widget-actions {
            text-align: center;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        .env-widget-actions .button {
            margin: 0 5px;
        }
        </style>
        <?php
    }
    
    /**
     * Render environmental actions widget
     */
    public function render_environmental_actions_widget() {
        $today = date('Y-m-d');
        $actions = $this->get_environmental_actions($today);
        
        ?>
        <div class="env-dashboard-widget">
            <div class="env-actions-grid">
                <div class="env-action-item">
                    <div class="env-action-icon">💰</div>
                    <div class="env-action-details">
                        <div class="env-action-count"><?php echo number_format($actions['donations']); ?></div>
                        <div class="env-action-label">Donations</div>
                    </div>
                </div>
                
                <div class="env-action-item">
                    <div class="env-action-icon">📝</div>
                    <div class="env-action-details">
                        <div class="env-action-count"><?php echo number_format($actions['petitions']); ?></div>
                        <div class="env-action-label">Petitions Signed</div>
                    </div>
                </div>
                
                <div class="env-action-item">
                    <div class="env-action-icon">🔄</div>
                    <div class="env-action-details">
                        <div class="env-action-count"><?php echo number_format($actions['exchanges']); ?></div>
                        <div class="env-action-label">Item Exchanges</div>
                    </div>
                </div>
                
                <div class="env-action-item">
                    <div class="env-action-icon">💬</div>
                    <div class="env-action-details">
                        <div class="env-action-count"><?php echo number_format($actions['forum_posts']); ?></div>
                        <div class="env-action-label">Forum Posts</div>
                    </div>
                </div>
            </div>
            
            <div class="env-total-impact">
                <strong>Total Environmental Actions Today: <?php echo number_format(array_sum($actions)); ?></strong>
            </div>
        </div>
        
        <style>
        .env-actions-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }
        .env-action-item {
            display: flex;
            align-items: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 3px solid #4CAF50;
        }
        .env-action-icon {
            font-size: 20px;
            margin-right: 10px;
        }
        .env-action-count {
            font-size: 16px;
            font-weight: bold;
            color: #2c5530;
        }
        .env-action-label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
        }
        .env-total-impact {
            text-align: center;
            padding: 10px;
            background: linear-gradient(135deg, #e8f5e8 0%, #f1f8e9 100%);
            border-radius: 5px;
            color: #2c5530;
        }
        </style>
        <?php
    }
    
    /**
     * Render user engagement widget
     */
    public function render_user_engagement_widget() {
        if ($this->behavior_analytics) {
            $segments = $this->behavior_analytics->get_user_segments();
            $engagement_stats = $this->get_engagement_stats();
        } else {
            $segments = array();
            $engagement_stats = array();
        }
        
        ?>
        <div class="env-dashboard-widget">
            <?php if (!empty($segments)): ?>
            <div class="env-segments-summary">
                <h4>User Segments</h4>
                <div class="env-segments-grid">
                    <?php foreach ($segments as $segment => $count): ?>
                    <div class="env-segment-item">
                        <div class="env-segment-count"><?php echo number_format($count); ?></div>
                        <div class="env-segment-label"><?php echo esc_html(ucwords(str_replace('_', ' ', $segment))); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="env-engagement-metrics">
                <div class="env-engagement-item">
                    <span>Average Engagement Score:</span>
                    <strong><?php echo number_format($engagement_stats['avg_score'] ?? 0, 1); ?></strong>
                </div>
                <div class="env-engagement-item">
                    <span>Active Users (24h):</span>
                    <strong><?php echo number_format($engagement_stats['active_users'] ?? 0); ?></strong>
                </div>
            </div>
        </div>
        
        <style>
        .env-segments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            gap: 8px;
            margin: 10px 0;
        }
        .env-segment-item {
            text-align: center;
            padding: 8px;
            background: #e8f5e8;
            border-radius: 5px;
        }
        .env-segment-count {
            font-size: 14px;
            font-weight: bold;
            color: #2c5530;
        }
        .env-segment-label {
            font-size: 9px;
            color: #558b5a;
        }
        .env-engagement-metrics {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        .env-engagement-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 12px;
        }
        </style>
        <?php
    }
    
    /**
     * Render conversion goals widget
     */
    public function render_conversion_goals_widget() {
        if ($this->conversion_tracker) {
            $goals = $this->conversion_tracker->get_all_goals();
            $today_conversions = $this->get_today_conversions();
        } else {
            $goals = array();
            $today_conversions = array();
        }
        
        ?>
        <div class="env-dashboard-widget">
            <?php if (!empty($goals)): ?>
            <div class="env-goals-list">
                <?php foreach (array_slice($goals, 0, 4) as $goal): ?>
                <div class="env-goal-item">
                    <div class="env-goal-name"><?php echo esc_html($goal->goal_name); ?></div>
                    <div class="env-goal-progress">
                        <?php 
                        $conversions = $today_conversions[$goal->id] ?? 0;
                        $target = $goal->target_value ?: 1;
                        $percentage = min(($conversions / $target) * 100, 100);
                        ?>
                        <div class="env-progress-bar">
                            <div class="env-progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                        <div class="env-progress-text">
                            <?php echo number_format($conversions); ?> / <?php echo number_format($target); ?>
                            (<?php echo number_format($percentage, 1); ?>%)
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="env-no-goals">
                <p>No conversion goals set up yet.</p>
                <a href="<?php echo admin_url('admin.php?page=environmental-analytics-goals'); ?>" class="button">
                    Create Goals
                </a>
            </div>
            <?php endif; ?>
        </div>
        
        <style>
        .env-goal-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f1f3f4;
        }
        .env-goal-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .env-goal-name {
            font-weight: 600;
            color: #2c5530;
            margin-bottom: 8px;
            font-size: 13px;
        }
        .env-progress-bar {
            height: 8px;
            background: #f1f3f4;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 5px;
        }
        .env-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4CAF50 0%, #66BB6A 100%);
            transition: width 0.3s ease;
        }
        .env-progress-text {
            font-size: 11px;
            color: #666;
        }
        .env-no-goals {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        </style>
        <?php
    }
    
    /**
     * Get daily statistics
     */
    private function get_daily_stats($date) {
        global $wpdb;
        
        $sessions_table = $wpdb->prefix . 'env_user_sessions';
        $events_table = $wpdb->prefix . 'env_analytics_events';
        
        // Get session data
        $sessions_data = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as sessions,
                COUNT(DISTINCT user_id) as users,
                AVG(session_duration) as avg_duration
             FROM $sessions_table 
             WHERE DATE(session_start) = %s",
            $date
        ));
        
        // Get page views
        $page_views = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $events_table 
             WHERE event_type = 'page_view' 
             AND DATE(created_at) = %s",
            $date
        ));
        
        return array(
            'sessions' => (int) ($sessions_data->sessions ?? 0),
            'users' => (int) ($sessions_data->users ?? 0),
            'avg_duration' => (int) ($sessions_data->avg_duration ?? 0),
            'page_views' => (int) $page_views
        );
    }
    
    /**
     * Get period statistics
     */
    private function get_period_stats($start_date, $end_date) {
        global $wpdb;
        
        $sessions_table = $wpdb->prefix . 'env_user_sessions';
        $events_table = $wpdb->prefix . 'env_analytics_events';
        
        // Get session data
        $sessions_data = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as sessions,
                COUNT(DISTINCT user_id) as users,
                AVG(session_duration) as avg_duration
             FROM $sessions_table 
             WHERE DATE(session_start) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        // Get page views
        $page_views = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $events_table 
             WHERE event_type = 'page_view' 
             AND DATE(created_at) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        return array(
            'sessions' => (int) ($sessions_data->sessions ?? 0),
            'users' => (int) ($sessions_data->users ?? 0),
            'avg_duration' => (int) ($sessions_data->avg_duration ?? 0),
            'page_views' => (int) $page_views
        );
    }
    
    /**
     * Get environmental actions for a date
     */
    private function get_environmental_actions($date) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'env_analytics_events';
        
        $actions = array(
            'donations' => 0,
            'petitions' => 0,
            'exchanges' => 0,
            'forum_posts' => 0
        );
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT event_type, COUNT(*) as count 
             FROM $table_name 
             WHERE DATE(created_at) = %s 
             AND event_type IN ('donation', 'petition_sign', 'item_exchange', 'forum_post')
             GROUP BY event_type",
            $date
        ));
        
        foreach ($results as $result) {
            switch ($result->event_type) {
                case 'donation':
                    $actions['donations'] = (int) $result->count;
                    break;
                case 'petition_sign':
                    $actions['petitions'] = (int) $result->count;
                    break;
                case 'item_exchange':
                    $actions['exchanges'] = (int) $result->count;
                    break;
                case 'forum_post':
                    $actions['forum_posts'] = (int) $result->count;
                    break;
            }
        }
        
        return $actions;
    }
    
    /**
     * Get engagement statistics
     */
    private function get_engagement_stats() {
        global $wpdb;
        
        $behavior_table = $wpdb->prefix . 'env_user_behavior';
        $sessions_table = $wpdb->prefix . 'env_user_sessions';
        
        $avg_score = $wpdb->get_var(
            "SELECT AVG(engagement_score) FROM $behavior_table 
             WHERE DATE(last_updated) = CURDATE()"
        );
        
        $active_users = $wpdb->get_var(
            "SELECT COUNT(DISTINCT user_id) FROM $sessions_table 
             WHERE session_start >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
        
        return array(
            'avg_score' => (float) ($avg_score ?? 0),
            'active_users' => (int) ($active_users ?? 0)
        );
    }
    
    /**
     * Get today's conversions
     */
    private function get_today_conversions() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'env_conversion_tracking';
        
        $results = $wpdb->get_results(
            "SELECT goal_id, COUNT(*) as conversions 
             FROM $table_name 
             WHERE DATE(converted_at) = CURDATE()
             GROUP BY goal_id",
            ARRAY_A
        );
        
        $conversions = array();
        foreach ($results as $result) {
            $conversions[$result['goal_id']] = (int) $result['conversions'];
        }
        
        return $conversions;
    }
    
    /**
     * Format percentage change
     */
    private function format_change($current, $previous) {
        if ($previous == 0) {
            return $current > 0 ? '+100%' : '0%';
        }
        
        $change = (($current - $previous) / $previous) * 100;
        return ($change >= 0 ? '+' : '') . number_format($change, 1) . '%';
    }
    
    /**
     * Get CSS class for change indicator
     */
    private function get_change_class($current, $previous) {
        if ($current > $previous) {
            return 'positive';
        } elseif ($current < $previous) {
            return 'negative';
        }
        return '';
    }
    
    /**
     * AJAX handler for widget data
     */
    public function get_widget_data() {
        check_ajax_referer('env_analytics_nonce', 'nonce');
        
        if (!current_user_can('read')) {
            wp_die('Unauthorized');
        }
        
        $widget_type = sanitize_text_field($_POST['widget_type']);
        $date = sanitize_text_field($_POST['date'] ?? date('Y-m-d'));
        
        switch ($widget_type) {
            case 'overview':
                $data = $this->get_daily_stats($date);
                break;
            case 'actions':
                $data = $this->get_environmental_actions($date);
                break;
            case 'engagement':
                $data = $this->get_engagement_stats();
                break;
            default:
                $data = array();
        }
        
        wp_send_json_success($data);
    }
}
