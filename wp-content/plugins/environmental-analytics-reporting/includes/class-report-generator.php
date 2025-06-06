<?php
/**
 * Report Generator Class
 * 
 * Handles automated report generation and email notifications
 * 
 * @package Environmental_Analytics_Reporting
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Report_Generator {
    
    private $database_manager;
    private $behavior_analytics;
    private $conversion_tracker;
    
    public function __construct($database_manager, $behavior_analytics, $conversion_tracker) {
        $this->database_manager = $database_manager;
        $this->behavior_analytics = $behavior_analytics;
        $this->conversion_tracker = $conversion_tracker;
        
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Schedule automated reports
        add_action('init', array($this, 'schedule_automated_reports'));
        
        // Handle report generation cron jobs
        add_action('env_analytics_daily_report', array($this, 'generate_daily_report'));
        add_action('env_analytics_weekly_report', array($this, 'generate_weekly_report'));
        add_action('env_analytics_monthly_report', array($this, 'generate_monthly_report'));
        
        // AJAX handlers for manual report generation
        add_action('wp_ajax_generate_custom_report', array($this, 'ajax_generate_custom_report'));
        add_action('wp_ajax_schedule_report', array($this, 'ajax_schedule_report'));
        add_action('wp_ajax_download_report', array($this, 'ajax_download_report'));
    }
    
    /**
     * Schedule automated reports
     */
    public function schedule_automated_reports() {
        // Daily report at 6 AM
        if (!wp_next_scheduled('env_analytics_daily_report')) {
            wp_schedule_event(strtotime('6:00 AM'), 'daily', 'env_analytics_daily_report');
        }
        
        // Weekly report on Mondays at 7 AM
        if (!wp_next_scheduled('env_analytics_weekly_report')) {
            wp_schedule_event(strtotime('next Monday 7:00 AM'), 'weekly', 'env_analytics_weekly_report');
        }
        
        // Monthly report on 1st of month at 8 AM
        if (!wp_next_scheduled('env_analytics_monthly_report')) {
            wp_schedule_event(strtotime('first day of next month 8:00 AM'), 'monthly', 'env_analytics_monthly_report');
        }
    }
    
    /**
     * Generate daily report
     */
    public function generate_daily_report() {
        $data = $this->get_daily_report_data();
        $html_report = $this->generate_html_report($data, 'daily');
        
        // Send email if enabled
        if (get_option('env_analytics_daily_email_enabled', false)) {
            $this->send_email_report($html_report, 'Daily Environmental Analytics Report', 'daily');
        }
        
        // Save report to database
        $this->save_report_to_database($html_report, 'daily', $data);
    }
    
    /**
     * Generate weekly report
     */
    public function generate_weekly_report() {
        $data = $this->get_weekly_report_data();
        $html_report = $this->generate_html_report($data, 'weekly');
        
        // Send email if enabled
        if (get_option('env_analytics_weekly_email_enabled', true)) {
            $this->send_email_report($html_report, 'Weekly Environmental Analytics Report', 'weekly');
        }
        
        // Save report to database
        $this->save_report_to_database($html_report, 'weekly', $data);
    }
    
    /**
     * Generate monthly report
     */
    public function generate_monthly_report() {
        $data = $this->get_monthly_report_data();
        $html_report = $this->generate_html_report($data, 'monthly');
        
        // Send email if enabled
        if (get_option('env_analytics_monthly_email_enabled', true)) {
            $this->send_email_report($html_report, 'Monthly Environmental Analytics Report', 'monthly');
        }
        
        // Save report to database
        $this->save_report_to_database($html_report, 'monthly', $data);
    }
    
    /**
     * Get daily report data
     */
    private function get_daily_report_data() {
        global $wpdb;
        
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $week_ago = date('Y-m-d', strtotime('-7 days'));
        
        return array(
            'period' => 'Daily',
            'date_range' => $yesterday,
            'overview' => array(
                'total_sessions' => $this->get_sessions_count($yesterday, $yesterday),
                'unique_users' => $this->get_unique_users_count($yesterday, $yesterday),
                'page_views' => $this->get_page_views_count($yesterday, $yesterday),
                'avg_session_duration' => $this->get_avg_session_duration($yesterday, $yesterday),
                'bounce_rate' => $this->get_bounce_rate($yesterday, $yesterday),
            ),
            'environmental_actions' => array(
                'donations' => $this->get_environmental_actions_count('donation', $yesterday, $yesterday),
                'petitions' => $this->get_environmental_actions_count('petition_sign', $yesterday, $yesterday),
                'exchanges' => $this->get_environmental_actions_count('item_exchange', $yesterday, $yesterday),
                'forum_posts' => $this->get_environmental_actions_count('forum_post', $yesterday, $yesterday),
            ),
            'top_content' => $this->get_top_content($yesterday, $yesterday),
            'user_segments' => $this->behavior_analytics->get_user_segments(),
            'conversions' => $this->conversion_tracker->get_conversion_summary($yesterday, $yesterday),
            'growth_metrics' => array(
                'sessions_growth' => $this->calculate_growth_rate(
                    $this->get_sessions_count($yesterday, $yesterday),
                    $this->get_sessions_count($week_ago, $week_ago)
                ),
                'users_growth' => $this->calculate_growth_rate(
                    $this->get_unique_users_count($yesterday, $yesterday),
                    $this->get_unique_users_count($week_ago, $week_ago)
                ),
            ),
        );
    }
    
    /**
     * Get weekly report data
     */
    private function get_weekly_report_data() {
        $end_date = date('Y-m-d', strtotime('-1 day'));
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $prev_week_end = date('Y-m-d', strtotime('-8 days'));
        $prev_week_start = date('Y-m-d', strtotime('-14 days'));
        
        return array(
            'period' => 'Weekly',
            'date_range' => $start_date . ' to ' . $end_date,
            'overview' => array(
                'total_sessions' => $this->get_sessions_count($start_date, $end_date),
                'unique_users' => $this->get_unique_users_count($start_date, $end_date),
                'page_views' => $this->get_page_views_count($start_date, $end_date),
                'avg_session_duration' => $this->get_avg_session_duration($start_date, $end_date),
                'bounce_rate' => $this->get_bounce_rate($start_date, $end_date),
            ),
            'environmental_actions' => array(
                'donations' => $this->get_environmental_actions_count('donation', $start_date, $end_date),
                'petitions' => $this->get_environmental_actions_count('petition_sign', $start_date, $end_date),
                'exchanges' => $this->get_environmental_actions_count('item_exchange', $start_date, $end_date),
                'forum_posts' => $this->get_environmental_actions_count('forum_post', $start_date, $end_date),
            ),
            'top_content' => $this->get_top_content($start_date, $end_date),
            'user_segments' => $this->behavior_analytics->get_user_segments(),
            'conversions' => $this->conversion_tracker->get_conversion_summary($start_date, $end_date),
            'traffic_sources' => $this->get_traffic_sources($start_date, $end_date),
            'device_breakdown' => $this->get_device_breakdown($start_date, $end_date),
            'growth_metrics' => array(
                'sessions_growth' => $this->calculate_growth_rate(
                    $this->get_sessions_count($start_date, $end_date),
                    $this->get_sessions_count($prev_week_start, $prev_week_end)
                ),
                'users_growth' => $this->calculate_growth_rate(
                    $this->get_unique_users_count($start_date, $end_date),
                    $this->get_unique_users_count($prev_week_start, $prev_week_end)
                ),
            ),
        );
    }
    
    /**
     * Get monthly report data
     */
    private function get_monthly_report_data() {
        $end_date = date('Y-m-d', strtotime('-1 day'));
        $start_date = date('Y-m-d', strtotime('-30 days'));
        $prev_month_end = date('Y-m-d', strtotime('-31 days'));
        $prev_month_start = date('Y-m-d', strtotime('-60 days'));
        
        return array(
            'period' => 'Monthly',
            'date_range' => $start_date . ' to ' . $end_date,
            'overview' => array(
                'total_sessions' => $this->get_sessions_count($start_date, $end_date),
                'unique_users' => $this->get_unique_users_count($start_date, $end_date),
                'page_views' => $this->get_page_views_count($start_date, $end_date),
                'avg_session_duration' => $this->get_avg_session_duration($start_date, $end_date),
                'bounce_rate' => $this->get_bounce_rate($start_date, $end_date),
            ),
            'environmental_actions' => array(
                'donations' => $this->get_environmental_actions_count('donation', $start_date, $end_date),
                'petitions' => $this->get_environmental_actions_count('petition_sign', $start_date, $end_date),
                'exchanges' => $this->get_environmental_actions_count('item_exchange', $start_date, $end_date),
                'forum_posts' => $this->get_environmental_actions_count('forum_post', $start_date, $end_date),
            ),
            'top_content' => $this->get_top_content($start_date, $end_date),
            'user_segments' => $this->behavior_analytics->get_user_segments(),
            'conversions' => $this->conversion_tracker->get_conversion_summary($start_date, $end_date),
            'traffic_sources' => $this->get_traffic_sources($start_date, $end_date),
            'device_breakdown' => $this->get_device_breakdown($start_date, $end_date),
            'monthly_trends' => $this->get_monthly_trends(),
            'achievement_stats' => $this->get_achievement_statistics($start_date, $end_date),
            'growth_metrics' => array(
                'sessions_growth' => $this->calculate_growth_rate(
                    $this->get_sessions_count($start_date, $end_date),
                    $this->get_sessions_count($prev_month_start, $prev_month_end)
                ),
                'users_growth' => $this->calculate_growth_rate(
                    $this->get_unique_users_count($start_date, $end_date),
                    $this->get_unique_users_count($prev_month_start, $prev_month_end)
                ),
            ),
        );
    }
    
    /**
     * Generate HTML report
     */
    private function generate_html_report($data, $period) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php echo esc_html($data['period']); ?> Environmental Analytics Report</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
                .report-container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .report-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #2c5530; padding-bottom: 20px; }
                .report-title { color: #2c5530; font-size: 28px; margin: 0; }
                .report-period { color: #666; font-size: 16px; margin: 10px 0 0 0; }
                .section { margin: 30px 0; }
                .section-title { color: #2c5530; font-size: 20px; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
                .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
                .metric-card { background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; border-left: 4px solid #4CAF50; }
                .metric-value { font-size: 24px; font-weight: bold; color: #2c5530; margin-bottom: 5px; }
                .metric-label { color: #666; font-size: 14px; }
                .growth-indicator { font-size: 12px; margin-top: 5px; }
                .growth-positive { color: #4CAF50; }
                .growth-negative { color: #f44336; }
                .content-list { list-style: none; padding: 0; }
                .content-item { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; display: flex; justify-content: between; }
                .content-title { font-weight: bold; color: #2c5530; }
                .content-views { color: #666; font-size: 14px; }
                .user-segments { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; }
                .segment-card { background: #e8f5e8; padding: 15px; border-radius: 8px; text-align: center; }
                .segment-count { font-size: 20px; font-weight: bold; color: #2c5530; }
                .segment-label { font-size: 14px; color: #666; margin-top: 5px; }
                .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="report-container">
                <div class="report-header">
                    <h1 class="report-title">Environmental Platform Analytics</h1>
                    <p class="report-period"><?php echo esc_html($data['period']); ?> Report - <?php echo esc_html($data['date_range']); ?></p>
                </div>
                
                <div class="section">
                    <h2 class="section-title">📊 Overview Metrics</h2>
                    <div class="metrics-grid">
                        <div class="metric-card">
                            <div class="metric-value"><?php echo number_format($data['overview']['total_sessions']); ?></div>
                            <div class="metric-label">Total Sessions</div>
                            <?php if (isset($data['growth_metrics']['sessions_growth'])): ?>
                                <div class="growth-indicator <?php echo $data['growth_metrics']['sessions_growth'] >= 0 ? 'growth-positive' : 'growth-negative'; ?>">
                                    <?php echo ($data['growth_metrics']['sessions_growth'] >= 0 ? '↗' : '↘') . ' ' . number_format(abs($data['growth_metrics']['sessions_growth']), 1) . '%'; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="metric-card">
                            <div class="metric-value"><?php echo number_format($data['overview']['unique_users']); ?></div>
                            <div class="metric-label">Unique Users</div>
                            <?php if (isset($data['growth_metrics']['users_growth'])): ?>
                                <div class="growth-indicator <?php echo $data['growth_metrics']['users_growth'] >= 0 ? 'growth-positive' : 'growth-negative'; ?>">
                                    <?php echo ($data['growth_metrics']['users_growth'] >= 0 ? '↗' : '↘') . ' ' . number_format(abs($data['growth_metrics']['users_growth']), 1) . '%'; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="metric-card">
                            <div class="metric-value"><?php echo number_format($data['overview']['page_views']); ?></div>
                            <div class="metric-label">Page Views</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-value"><?php echo gmdate('i:s', $data['overview']['avg_session_duration']); ?></div>
                            <div class="metric-label">Avg Session Duration</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-value"><?php echo number_format($data['overview']['bounce_rate'], 1); ?>%</div>
                            <div class="metric-label">Bounce Rate</div>
                        </div>
                    </div>
                </div>
                
                <div class="section">
                    <h2 class="section-title">🌱 Environmental Actions</h2>
                    <div class="metrics-grid">
                        <div class="metric-card">
                            <div class="metric-value"><?php echo number_format($data['environmental_actions']['donations']); ?></div>
                            <div class="metric-label">Donations</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-value"><?php echo number_format($data['environmental_actions']['petitions']); ?></div>
                            <div class="metric-label">Petitions Signed</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-value"><?php echo number_format($data['environmental_actions']['exchanges']); ?></div>
                            <div class="metric-label">Item Exchanges</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-value"><?php echo number_format($data['environmental_actions']['forum_posts']); ?></div>
                            <div class="metric-label">Forum Posts</div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($data['top_content'])): ?>
                <div class="section">
                    <h2 class="section-title">📄 Top Content</h2>
                    <ul class="content-list">
                        <?php foreach (array_slice($data['top_content'], 0, 5) as $content): ?>
                        <li class="content-item">
                            <div>
                                <div class="content-title"><?php echo esc_html($content['page_title'] ?: $content['page_url']); ?></div>
                                <div class="content-views"><?php echo number_format($content['views']); ?> views</div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <div class="section">
                    <h2 class="section-title">👥 User Segments</h2>
                    <div class="user-segments">
                        <?php foreach ($data['user_segments'] as $segment => $count): ?>
                        <div class="segment-card">
                            <div class="segment-count"><?php echo number_format($count); ?></div>
                            <div class="segment-label"><?php echo esc_html(ucwords(str_replace('_', ' ', $segment))); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <?php if (!empty($data['conversions'])): ?>
                <div class="section">
                    <h2 class="section-title">🎯 Conversion Summary</h2>
                    <div class="metrics-grid">
                        <?php foreach ($data['conversions'] as $goal => $stats): ?>
                        <div class="metric-card">
                            <div class="metric-value"><?php echo number_format($stats['conversions']); ?></div>
                            <div class="metric-label"><?php echo esc_html(ucwords(str_replace('_', ' ', $goal))); ?></div>
                            <div class="growth-indicator">Rate: <?php echo number_format($stats['conversion_rate'], 2); ?>%</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="footer">
                    <p>Generated on <?php echo date('F j, Y \a\t g:i A'); ?> | Environmental Platform Analytics & Reporting</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Send email report
     */
    private function send_email_report($html_content, $subject, $period) {
        $recipients = get_option('env_analytics_email_recipients', array(get_option('admin_email')));
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        foreach ($recipients as $recipient) {
            wp_mail($recipient, $subject, $html_content, $headers);
        }
        
        // Log email sent
        error_log("Environmental Analytics: {$period} report sent to " . implode(', ', $recipients));
    }
    
    /**
     * Save report to database
     */
    private function save_report_to_database($html_content, $period, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'env_analytics_reports';
        
        // Create reports table if it doesn't exist
        $this->create_reports_table();
        
        $wpdb->insert(
            $table_name,
            array(
                'report_type' => $period,
                'report_data' => json_encode($data),
                'html_content' => $html_content,
                'generated_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Create reports table
     */
    private function create_reports_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'env_analytics_reports';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            report_type varchar(50) NOT NULL,
            report_data longtext,
            html_content longtext,
            generated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY report_type (report_type),
            KEY generated_at (generated_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    // Helper methods for data retrieval
    private function get_sessions_count($start_date, $end_date) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'env_user_sessions';
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
             WHERE DATE(session_start) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        return (int) $result;
    }
    
    private function get_unique_users_count($start_date, $end_date) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'env_user_sessions';
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM $table_name 
             WHERE DATE(session_start) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        return (int) $result;
    }
    
    private function get_page_views_count($start_date, $end_date) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'env_analytics_events';
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
             WHERE event_type = 'page_view' 
             AND DATE(created_at) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        return (int) $result;
    }
    
    private function get_avg_session_duration($start_date, $end_date) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'env_user_sessions';
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(session_duration) FROM $table_name 
             WHERE session_duration > 0 
             AND DATE(session_start) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        return (int) ($result ?: 0);
    }
    
    private function get_bounce_rate($start_date, $end_date) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'env_user_sessions';
        
        $total_sessions = $this->get_sessions_count($start_date, $end_date);
        
        if ($total_sessions == 0) return 0;
        
        $bounce_sessions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
             WHERE page_views = 1 
             AND DATE(session_start) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        return ($bounce_sessions / $total_sessions) * 100;
    }
    
    private function get_environmental_actions_count($action_type, $start_date, $end_date) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'env_analytics_events';
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
             WHERE event_type = %s 
             AND DATE(created_at) BETWEEN %s AND %s",
            $action_type, $start_date, $end_date
        ));
        
        return (int) $result;
    }
    
    private function get_top_content($start_date, $end_date) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'env_analytics_events';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT page_url, page_title, COUNT(*) as views
             FROM $table_name 
             WHERE event_type = 'page_view' 
             AND DATE(created_at) BETWEEN %s AND %s
             GROUP BY page_url 
             ORDER BY views DESC 
             LIMIT 10",
            $start_date, $end_date
        ), ARRAY_A);
        
        return $results ?: array();
    }
    
    private function get_traffic_sources($start_date, $end_date) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'env_user_sessions';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT traffic_source, COUNT(*) as sessions
             FROM $table_name 
             WHERE DATE(session_start) BETWEEN %s AND %s
             GROUP BY traffic_source 
             ORDER BY sessions DESC",
            $start_date, $end_date
        ), ARRAY_A);
        
        return $results ?: array();
    }
    
    private function get_device_breakdown($start_date, $end_date) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'env_user_sessions';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT device_type, COUNT(*) as sessions
             FROM $table_name 
             WHERE DATE(session_start) BETWEEN %s AND %s
             GROUP BY device_type 
             ORDER BY sessions DESC",
            $start_date, $end_date
        ), ARRAY_A);
        
        return $results ?: array();
    }
    
    private function get_monthly_trends() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'env_user_sessions';
        
        $results = $wpdb->get_results(
            "SELECT 
                DATE_FORMAT(session_start, '%Y-%m') as month,
                COUNT(*) as sessions,
                COUNT(DISTINCT user_id) as unique_users
             FROM $table_name 
             WHERE session_start >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             GROUP BY month 
             ORDER BY month ASC",
            ARRAY_A
        );
        
        return $results ?: array();
    }
    
    private function get_achievement_statistics($start_date, $end_date) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'env_analytics_events';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                event_data,
                COUNT(*) as count
             FROM $table_name 
             WHERE event_type = 'achievement_unlocked' 
             AND DATE(created_at) BETWEEN %s AND %s
             GROUP BY event_data 
             ORDER BY count DESC",
            $start_date, $end_date
        ), ARRAY_A);
        
        return $results ?: array();
    }
    
    private function calculate_growth_rate($current, $previous) {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return (($current - $previous) / $previous) * 100;
    }
    
    /**
     * AJAX handler for custom report generation
     */
    public function ajax_generate_custom_report() {
        check_ajax_referer('env_analytics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        $report_type = sanitize_text_field($_POST['report_type']);
        
        // Generate custom report data
        $data = array(
            'period' => 'Custom',
            'date_range' => $start_date . ' to ' . $end_date,
            'overview' => array(
                'total_sessions' => $this->get_sessions_count($start_date, $end_date),
                'unique_users' => $this->get_unique_users_count($start_date, $end_date),
                'page_views' => $this->get_page_views_count($start_date, $end_date),
                'avg_session_duration' => $this->get_avg_session_duration($start_date, $end_date),
                'bounce_rate' => $this->get_bounce_rate($start_date, $end_date),
            ),
            'environmental_actions' => array(
                'donations' => $this->get_environmental_actions_count('donation', $start_date, $end_date),
                'petitions' => $this->get_environmental_actions_count('petition_sign', $start_date, $end_date),
                'exchanges' => $this->get_environmental_actions_count('item_exchange', $start_date, $end_date),
                'forum_posts' => $this->get_environmental_actions_count('forum_post', $start_date, $end_date),
            ),
            'top_content' => $this->get_top_content($start_date, $end_date),
            'user_segments' => $this->behavior_analytics->get_user_segments(),
            'conversions' => $this->conversion_tracker->get_conversion_summary($start_date, $end_date),
        );
        
        $html_report = $this->generate_html_report($data, 'custom');
        
        // Save report
        $this->save_report_to_database($html_report, 'custom', $data);
        
        wp_send_json_success(array(
            'message' => 'Custom report generated successfully',
            'data' => $data
        ));
    }
    
    /**
     * AJAX handler for report scheduling
     */
    public function ajax_schedule_report() {
        check_ajax_referer('env_analytics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $report_type = sanitize_text_field($_POST['report_type']);
        $schedule = sanitize_text_field($_POST['schedule']);
        $email_enabled = (bool) $_POST['email_enabled'];
        
        update_option("env_analytics_{$report_type}_email_enabled", $email_enabled);
        
        wp_send_json_success(array(
            'message' => 'Report schedule updated successfully'
        ));
    }
    
    /**
     * AJAX handler for report download
     */
    public function ajax_download_report() {
        check_ajax_referer('env_analytics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $report_id = (int) $_POST['report_id'];
        $format = sanitize_text_field($_POST['format']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'env_analytics_reports';
        
        $report = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $report_id
        ));
        
        if (!$report) {
            wp_die('Report not found');
        }
        
        if ($format === 'pdf') {
            // For PDF generation, you might want to use a library like TCPDF or DOMPDF
            // For now, we'll just return the HTML
            wp_send_json_success(array(
                'message' => 'PDF generation requires additional library setup',
                'html' => $report->html_content
            ));
        } else {
            wp_send_json_success(array(
                'html' => $report->html_content,
                'data' => json_decode($report->report_data, true)
            ));
        }
    }
}
