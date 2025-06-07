<?php
/**
 * Reporting Dashboard Class
 * 
 * Handles reporting views, analytics, and data visualization
 * 
 * @package EnvironmentalAdminDashboard
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Reporting_Dashboard {
    
    private static $instance = null;
    
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
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_env_generate_report', array($this, 'ajax_generate_report'));
        add_action('wp_ajax_env_export_report', array($this, 'ajax_export_report'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'env-admin-dashboard',
            __('Reports & Analytics', 'env-admin-dashboard'),
            __('Reports', 'env-admin-dashboard'),
            'manage_options',
            'env-reporting',
            array($this, 'render_reporting_dashboard')
        );
    }
    
    /**
     * Render reporting dashboard
     */
    public function render_reporting_dashboard() {
        ?>
        <div class="wrap env-reporting-dashboard">
            <h1><?php _e('Environmental Platform Reports', 'env-admin-dashboard'); ?></h1>
            
            <div class="env-report-filters">
                <form id="env-report-form">
                    <div class="filter-group">
                        <label for="report-type"><?php _e('Report Type:', 'env-admin-dashboard'); ?></label>
                        <select id="report-type" name="report_type">
                            <option value="overview"><?php _e('Platform Overview', 'env-admin-dashboard'); ?></option>
                            <option value="users"><?php _e('User Analytics', 'env-admin-dashboard'); ?></option>
                            <option value="activities"><?php _e('Activities Report', 'env-admin-dashboard'); ?></option>
                            <option value="goals"><?php _e('Goals Progress', 'env-admin-dashboard'); ?></option>
                            <option value="performance"><?php _e('Performance Metrics', 'env-admin-dashboard'); ?></option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="date-range"><?php _e('Date Range:', 'env-admin-dashboard'); ?></label>
                        <select id="date-range" name="date_range">
                            <option value="7days"><?php _e('Last 7 Days', 'env-admin-dashboard'); ?></option>
                            <option value="30days"><?php _e('Last 30 Days', 'env-admin-dashboard'); ?></option>
                            <option value="90days"><?php _e('Last 90 Days', 'env-admin-dashboard'); ?></option>
                            <option value="1year"><?php _e('Last Year', 'env-admin-dashboard'); ?></option>
                            <option value="custom"><?php _e('Custom Range', 'env-admin-dashboard'); ?></option>
                        </select>
                    </div>
                    
                    <div class="filter-group custom-date-range" style="display:none;">
                        <label for="start-date"><?php _e('Start Date:', 'env-admin-dashboard'); ?></label>
                        <input type="date" id="start-date" name="start_date">
                        
                        <label for="end-date"><?php _e('End Date:', 'env-admin-dashboard'); ?></label>
                        <input type="date" id="end-date" name="end_date">
                    </div>
                    
                    <div class="filter-actions">
                        <button type="button" class="button button-primary" onclick="envReporting.generateReport()">
                            <?php _e('Generate Report', 'env-admin-dashboard'); ?>
                        </button>
                        <button type="button" class="button" onclick="envReporting.exportReport()">
                            <?php _e('Export Report', 'env-admin-dashboard'); ?>
                        </button>
                    </div>
                </form>
            </div>
            
            <div id="env-report-content" class="env-report-content">
                <div class="env-loading" style="display:none;">
                    <span class="spinner is-active"></span>
                    <?php _e('Generating report...', 'env-admin-dashboard'); ?>
                </div>
                
                <div class="env-report-summary">
                    <?php $this->render_default_summary(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render default summary
     */
    private function render_default_summary() {
        $summary_data = $this->get_summary_data();
        ?>
        <h2><?php _e('Platform Summary', 'env-admin-dashboard'); ?></h2>
        
        <div class="env-summary-grid">
            <div class="env-summary-card">
                <h3><?php _e('Total Users', 'env-admin-dashboard'); ?></h3>
                <span class="summary-number"><?php echo $summary_data['users']; ?></span>
                <span class="summary-change positive">+<?php echo $summary_data['users_growth']; ?>%</span>
            </div>
            
            <div class="env-summary-card">
                <h3><?php _e('Active Activities', 'env-admin-dashboard'); ?></h3>
                <span class="summary-number"><?php echo $summary_data['activities']; ?></span>
                <span class="summary-change positive">+<?php echo $summary_data['activities_growth']; ?>%</span>
            </div>
            
            <div class="env-summary-card">
                <h3><?php _e('Completed Goals', 'env-admin-dashboard'); ?></h3>
                <span class="summary-number"><?php echo $summary_data['goals']; ?></span>
                <span class="summary-change positive">+<?php echo $summary_data['goals_growth']; ?>%</span>
            </div>
            
            <div class="env-summary-card">
                <h3><?php _e('Platform Health', 'env-admin-dashboard'); ?></h3>
                <span class="summary-number"><?php echo $summary_data['health_score']; ?>%</span>
                <span class="summary-status excellent"><?php _e('Excellent', 'env-admin-dashboard'); ?></span>
            </div>
        </div>
        
        <div class="env-chart-container">
            <h3><?php _e('Activity Trends', 'env-admin-dashboard'); ?></h3>
            <canvas id="activityTrendsChart"></canvas>
        </div>
        <?php
    }
    
    /**
     * Get summary data
     */
    private function get_summary_data() {
        global $wpdb;
        
        // Get cached data first
        $cache_key = 'env_reporting_summary_' . date('Y-m-d-H');
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        $data = array(
            'users' => $this->get_users_count(),
            'activities' => $this->get_activities_count(),
            'goals' => $this->get_goals_count(),
            'health_score' => $this->calculate_health_score(),
            'users_growth' => $this->calculate_growth_rate('users'),
            'activities_growth' => $this->calculate_growth_rate('activities'),
            'goals_growth' => $this->calculate_growth_rate('goals')
        );
        
        // Cache for 1 hour
        set_transient($cache_key, $data, HOUR_IN_SECONDS);
        
        return $data;
    }
    
    /**
     * Get users count
     */
    private function get_users_count() {
        return count_users()['total_users'];
    }
    
    /**
     * Get activities count
     */
    private function get_activities_count() {
        global $wpdb;
        
        $count = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}environmental_activities 
            WHERE status = 'active'
        ");
        
        return intval($count);
    }
    
    /**
     * Get goals count
     */
    private function get_goals_count() {
        global $wpdb;
        
        $count = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}environmental_goals 
            WHERE status = 'completed'
        ");
        
        return intval($count);
    }
    
    /**
     * Calculate platform health score
     */
    private function calculate_health_score() {
        $factors = array(
            'user_activity' => $this->get_user_activity_score(),
            'content_quality' => $this->get_content_quality_score(),
            'performance' => $this->get_performance_score(),
            'engagement' => $this->get_engagement_score()
        );
        
        $total_score = array_sum($factors);
        $max_score = count($factors) * 100;
        
        return round(($total_score / $max_score) * 100);
    }
    
    /**
     * Get user activity score
     */
    private function get_user_activity_score() {
        global $wpdb;
        
        $active_users = $wpdb->get_var("
            SELECT COUNT(DISTINCT user_id) 
            FROM {$wpdb->prefix}user_activity_log 
            WHERE activity_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        
        $total_users = $this->get_users_count();
        
        if ($total_users == 0) return 0;
        
        return min(100, ($active_users / $total_users) * 100);
    }
    
    /**
     * Get content quality score
     */
    private function get_content_quality_score() {
        $published_posts = wp_count_posts()->publish;
        $draft_posts = wp_count_posts()->draft;
        
        if ($published_posts + $draft_posts == 0) return 0;
        
        return min(100, ($published_posts / ($published_posts + $draft_posts)) * 100);
    }
    
    /**
     * Get performance score
     */
    private function get_performance_score() {
        // Placeholder - could integrate with actual performance monitoring
        return 85;
    }
    
    /**
     * Get engagement score
     */
    private function get_engagement_score() {
        $total_comments = wp_count_comments()->approved;
        $total_posts = wp_count_posts()->publish;
        
        if ($total_posts == 0) return 0;
        
        $engagement_ratio = $total_comments / $total_posts;
        return min(100, $engagement_ratio * 10); // Scale to 0-100
    }
    
    /**
     * Calculate growth rate
     */
    private function calculate_growth_rate($metric) {
        global $wpdb;
        
        $current_date = date('Y-m-d');
        $past_date = date('Y-m-d', strtotime('-30 days'));
        
        switch ($metric) {
            case 'users':
                $current = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) FROM {$wpdb->users} 
                    WHERE user_registered <= %s
                ", $current_date));
                
                $previous = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) FROM {$wpdb->users} 
                    WHERE user_registered <= %s
                ", $past_date));
                break;
                
            case 'activities':
                $current = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) FROM {$wpdb->prefix}environmental_activities 
                    WHERE created_date <= %s
                ", $current_date));
                
                $previous = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) FROM {$wpdb->prefix}environmental_activities 
                    WHERE created_date <= %s
                ", $past_date));
                break;
                
            case 'goals':
                $current = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) FROM {$wpdb->prefix}environmental_goals 
                    WHERE created_date <= %s
                ", $current_date));
                
                $previous = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) FROM {$wpdb->prefix}environmental_goals 
                    WHERE created_date <= %s
                ", $past_date));
                break;
                
            default:
                return 0;
        }
        
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 1);
    }
    
    /**
     * Generate report based on parameters
     */
    public function generate_report($type, $date_range, $start_date = null, $end_date = null) {
        $report_data = array();
        
        switch ($type) {
            case 'overview':
                $report_data = $this->generate_overview_report($date_range, $start_date, $end_date);
                break;
            case 'users':
                $report_data = $this->generate_users_report($date_range, $start_date, $end_date);
                break;
            case 'activities':
                $report_data = $this->generate_activities_report($date_range, $start_date, $end_date);
                break;
            case 'goals':
                $report_data = $this->generate_goals_report($date_range, $start_date, $end_date);
                break;
            case 'performance':
                $report_data = $this->generate_performance_report($date_range, $start_date, $end_date);
                break;
        }
        
        return $report_data;
    }
    
    /**
     * Generate overview report
     */
    private function generate_overview_report($date_range, $start_date, $end_date) {
        return array(
            'title' => __('Platform Overview Report', 'env-admin-dashboard'),
            'summary' => $this->get_summary_data(),
            'charts' => array(
                'users_trend' => $this->get_users_trend_data($date_range, $start_date, $end_date),
                'activities_trend' => $this->get_activities_trend_data($date_range, $start_date, $end_date)
            )
        );
    }
    
    /**
     * Generate users report
     */
    private function generate_users_report($date_range, $start_date, $end_date) {
        global $wpdb;
        
        return array(
            'title' => __('Users Analytics Report', 'env-admin-dashboard'),
            'total_users' => $this->get_users_count(),
            'new_users' => $this->get_new_users_count($date_range, $start_date, $end_date),
            'active_users' => $this->get_active_users_count($date_range, $start_date, $end_date),
            'user_levels' => $this->get_user_levels_distribution()
        );
    }
    
    /**
     * Get new users count
     */
    private function get_new_users_count($date_range, $start_date, $end_date) {
        global $wpdb;
        
        $date_condition = $this->get_date_condition($date_range, $start_date, $end_date);
        
        $count = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->users} 
            WHERE user_registered >= '{$date_condition['start']}'
            AND user_registered <= '{$date_condition['end']}'
        ");
        
        return intval($count);
    }
    
    /**
     * Get active users count
     */
    private function get_active_users_count($date_range, $start_date, $end_date) {
        global $wpdb;
        
        $date_condition = $this->get_date_condition($date_range, $start_date, $end_date);
        
        $count = $wpdb->get_var("
            SELECT COUNT(DISTINCT user_id) 
            FROM {$wpdb->prefix}user_activity_log 
            WHERE activity_date >= '{$date_condition['start']}'
            AND activity_date <= '{$date_condition['end']}'
        ");
        
        return intval($count);
    }
    
    /**
     * Get user levels distribution
     */
    private function get_user_levels_distribution() {
        global $wpdb;
        
        $results = $wpdb->get_results("
            SELECT user_level, COUNT(*) as count 
            FROM {$wpdb->users} 
            GROUP BY user_level 
            ORDER BY user_level
        ", ARRAY_A);
        
        return $results ?: array();
    }
    
    /**
     * Get date condition for queries
     */
    private function get_date_condition($date_range, $start_date, $end_date) {
        $end = date('Y-m-d 23:59:59');
        
        switch ($date_range) {
            case '7days':
                $start = date('Y-m-d 00:00:00', strtotime('-7 days'));
                break;
            case '30days':
                $start = date('Y-m-d 00:00:00', strtotime('-30 days'));
                break;
            case '90days':
                $start = date('Y-m-d 00:00:00', strtotime('-90 days'));
                break;
            case '1year':
                $start = date('Y-m-d 00:00:00', strtotime('-1 year'));
                break;
            case 'custom':
                $start = $start_date ? $start_date . ' 00:00:00' : date('Y-m-d 00:00:00', strtotime('-30 days'));
                $end = $end_date ? $end_date . ' 23:59:59' : date('Y-m-d 23:59:59');
                break;
            default:
                $start = date('Y-m-d 00:00:00', strtotime('-30 days'));
        }
        
        return array('start' => $start, 'end' => $end);
    }
    
    /**
     * AJAX handler for generating reports
     */
    public function ajax_generate_report() {
        check_ajax_referer('env_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Insufficient permissions')));
        }
        
        $type = sanitize_text_field($_POST['report_type']);
        $date_range = sanitize_text_field($_POST['date_range']);
        $start_date = sanitize_text_field($_POST['start_date'] ?? '');
        $end_date = sanitize_text_field($_POST['end_date'] ?? '');
        
        $report_data = $this->generate_report($type, $date_range, $start_date, $end_date);
        
        wp_die(json_encode(array(
            'success' => true,
            'data' => $report_data
        )));
    }
    
    /**
     * AJAX handler for exporting reports
     */
    public function ajax_export_report() {
        check_ajax_referer('env_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Insufficient permissions')));
        }
        
        $type = sanitize_text_field($_POST['report_type']);
        $date_range = sanitize_text_field($_POST['date_range']);
        $start_date = sanitize_text_field($_POST['start_date'] ?? '');
        $end_date = sanitize_text_field($_POST['end_date'] ?? '');
        
        $report_data = $this->generate_report($type, $date_range, $start_date, $end_date);
        
        // Generate export file
        $filename = 'environmental-report-' . $type . '-' . date('Y-m-d-H-i-s') . '.json';
        
        wp_die(json_encode(array(
            'success' => true,
            'data' => $report_data,
            'filename' => $filename
        )));
    }
}
