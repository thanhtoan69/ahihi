<?php
/**
 * Cron Jobs Handler
 * 
 * Handles automated analytics processing and data cleanup
 * 
 * @package Environmental_Analytics_Reporting
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Analytics_Cron {
    
    private $database_manager;
    private $behavior_analytics;
    private $report_generator;
    
    public function __construct($database_manager, $behavior_analytics, $report_generator) {
        $this->database_manager = $database_manager;
        $this->behavior_analytics = $behavior_analytics;
        $this->report_generator = $report_generator;
        
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Daily analytics processing
        add_action('env_analytics_daily_processing', array($this, 'process_daily_analytics'));
        
        // Weekly data cleanup
        add_action('env_analytics_weekly_cleanup', array($this, 'cleanup_old_data'));
        
        // Schedule cron jobs
        add_action('init', array($this, 'schedule_cron_jobs'));
    }
    
    /**
     * Schedule cron jobs
     */
    public function schedule_cron_jobs() {
        // Daily processing at 2 AM
        if (!wp_next_scheduled('env_analytics_daily_processing')) {
            wp_schedule_event(strtotime('2:00 AM'), 'daily', 'env_analytics_daily_processing');
        }
        
        // Weekly cleanup on Sundays at 3 AM
        if (!wp_next_scheduled('env_analytics_weekly_cleanup')) {
            wp_schedule_event(strtotime('next Sunday 3:00 AM'), 'weekly', 'env_analytics_weekly_cleanup');
        }
    }
    
    /**
     * Process daily analytics
     */
    public function process_daily_analytics() {
        error_log('Environmental Analytics: Starting daily processing');
        
        try {
            // Update user behavior analytics
            if ($this->behavior_analytics) {
                $this->behavior_analytics->analyze_daily_behavior();
                error_log('Environmental Analytics: Daily behavior analysis completed');
            }
            
            // Process session data
            $this->process_session_data();
            
            // Update engagement scores
            $this->update_engagement_scores();
            
            // Clean up temporary data
            $this->cleanup_temporary_data();
            
            error_log('Environmental Analytics: Daily processing completed successfully');
            
        } catch (Exception $e) {
            error_log('Environmental Analytics: Daily processing failed - ' . $e->getMessage());
        }
    }
    
    /**
     * Process session data
     */
    private function process_session_data() {
        global $wpdb;
        
        $sessions_table = $wpdb->prefix . 'env_user_sessions';
        
        // Update session durations for sessions that ended yesterday
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        $wpdb->query($wpdb->prepare(
            "UPDATE $sessions_table 
             SET session_duration = TIMESTAMPDIFF(SECOND, session_start, session_end)
             WHERE DATE(session_start) = %s 
             AND session_duration = 0 
             AND session_end IS NOT NULL",
            $yesterday
        ));
        
        // Mark sessions as ended if no activity for 30 minutes
        $cutoff_time = date('Y-m-d H:i:s', strtotime('-30 minutes'));
        
        $wpdb->query($wpdb->prepare(
            "UPDATE $sessions_table 
             SET session_end = last_activity,
                 session_duration = TIMESTAMPDIFF(SECOND, session_start, last_activity)
             WHERE session_end IS NULL 
             AND last_activity < %s",
            $cutoff_time
        ));
    }
    
    /**
     * Update engagement scores
     */
    private function update_engagement_scores() {
        global $wpdb;
        
        $behavior_table = $wpdb->prefix . 'env_user_behavior';
        $events_table = $wpdb->prefix . 'env_analytics_events';
        $sessions_table = $wpdb->prefix . 'env_user_sessions';
        
        // Get users active in the last 24 hours
        $active_users = $wpdb->get_results(
            "SELECT DISTINCT user_id 
             FROM $sessions_table 
             WHERE session_start >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
             AND user_id > 0"
        );
        
        foreach ($active_users as $user) {
            $user_id = $user->user_id;
            
            // Calculate engagement score based on recent activity
            $engagement_score = $this->calculate_engagement_score($user_id);
            
            // Update or insert behavior record
            $wpdb->query($wpdb->prepare(
                "INSERT INTO $behavior_table 
                 (user_id, engagement_score, last_updated) 
                 VALUES (%d, %f, NOW())
                 ON DUPLICATE KEY UPDATE 
                 engagement_score = %f,
                 last_updated = NOW()",
                $user_id, $engagement_score, $engagement_score
            ));
        }
    }
    
    /**
     * Calculate engagement score for a user
     */
    private function calculate_engagement_score($user_id) {
        global $wpdb;
        
        $events_table = $wpdb->prefix . 'env_analytics_events';
        $sessions_table = $wpdb->prefix . 'env_user_sessions';
        
        $score = 0;
        
        // Recent events (last 7 days)
        $event_counts = $wpdb->get_results($wpdb->prepare(
            "SELECT event_type, COUNT(*) as count 
             FROM $events_table 
             WHERE user_id = %d 
             AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY event_type",
            $user_id
        ));
        
        foreach ($event_counts as $event) {
            switch ($event->event_type) {
                case 'page_view':
                    $score += $event->count * 1;
                    break;
                case 'donation':
                    $score += $event->count * 20;
                    break;
                case 'petition_sign':
                    $score += $event->count * 15;
                    break;
                case 'item_exchange':
                    $score += $event->count * 10;
                    break;
                case 'forum_post':
                    $score += $event->count * 8;
                    break;
                case 'social_share':
                    $score += $event->count * 5;
                    break;
                case 'file_download':
                    $score += $event->count * 3;
                    break;
                case 'search':
                    $score += $event->count * 2;
                    break;
                default:
                    $score += $event->count * 1;
            }
        }
        
        // Session quality (average session duration)
        $avg_session_duration = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(session_duration) 
             FROM $sessions_table 
             WHERE user_id = %d 
             AND session_start >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             AND session_duration > 0",
            $user_id
        ));
        
        if ($avg_session_duration) {
            $score += min($avg_session_duration / 60, 10); // Max 10 points for session duration
        }
        
        return min($score, 100); // Cap at 100
    }
    
    /**
     * Clean up temporary data
     */
    private function cleanup_temporary_data() {
        global $wpdb;
        
        $events_table = $wpdb->prefix . 'env_analytics_events';
        
        // Delete temporary events older than 1 day
        $wpdb->query(
            "DELETE FROM $events_table 
             WHERE event_type = 'temp_event' 
             AND created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)"
        );
        
        // Delete page view events older than 90 days (keep other events longer)
        $wpdb->query(
            "DELETE FROM $events_table 
             WHERE event_type = 'page_view' 
             AND created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"
        );
    }
    
    /**
     * Weekly data cleanup
     */
    public function cleanup_old_data() {
        error_log('Environmental Analytics: Starting weekly cleanup');
        
        try {
            global $wpdb;
            
            $events_table = $wpdb->prefix . 'env_analytics_events';
            $sessions_table = $wpdb->prefix . 'env_user_sessions';
            $behavior_table = $wpdb->prefix . 'env_user_behavior';
            $reports_table = $wpdb->prefix . 'env_analytics_reports';
            
            // Delete old events (keep for 1 year)
            $deleted_events = $wpdb->query(
                "DELETE FROM $events_table 
                 WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)"
            );
            
            // Delete old sessions (keep for 6 months)
            $deleted_sessions = $wpdb->query(
                "DELETE FROM $sessions_table 
                 WHERE session_start < DATE_SUB(NOW(), INTERVAL 6 MONTH)"
            );
            
            // Clean up old behavior records (keep for 3 months)
            $deleted_behavior = $wpdb->query(
                "DELETE FROM $behavior_table 
                 WHERE last_updated < DATE_SUB(NOW(), INTERVAL 3 MONTH)"
            );
            
            // Delete old reports (keep for 2 years)
            $deleted_reports = $wpdb->query(
                "DELETE FROM $reports_table 
                 WHERE generated_at < DATE_SUB(NOW(), INTERVAL 2 YEAR)"
            );
            
            // Optimize tables
            $wpdb->query("OPTIMIZE TABLE $events_table");
            $wpdb->query("OPTIMIZE TABLE $sessions_table");
            $wpdb->query("OPTIMIZE TABLE $behavior_table");
            
            error_log("Environmental Analytics: Weekly cleanup completed - Events: $deleted_events, Sessions: $deleted_sessions, Behavior: $deleted_behavior, Reports: $deleted_reports");
            
        } catch (Exception $e) {
            error_log('Environmental Analytics: Weekly cleanup failed - ' . $e->getMessage());
        }
    }
    
    /**
     * Get cron job status
     */
    public static function get_cron_status() {
        return array(
            'daily_processing' => array(
                'scheduled' => wp_next_scheduled('env_analytics_daily_processing'),
                'last_run' => get_option('env_analytics_last_daily_processing'),
            ),
            'weekly_cleanup' => array(
                'scheduled' => wp_next_scheduled('env_analytics_weekly_cleanup'),
                'last_run' => get_option('env_analytics_last_weekly_cleanup'),
            ),
            'daily_report' => array(
                'scheduled' => wp_next_scheduled('env_analytics_daily_report'),
                'last_run' => get_option('env_analytics_last_daily_report'),
            ),
            'weekly_report' => array(
                'scheduled' => wp_next_scheduled('env_analytics_weekly_report'),
                'last_run' => get_option('env_analytics_last_weekly_report'),
            ),
            'monthly_report' => array(
                'scheduled' => wp_next_scheduled('env_analytics_monthly_report'),
                'last_run' => get_option('env_analytics_last_monthly_report'),
            ),
        );
    }
    
    /**
     * Manual trigger for daily processing
     */
    public static function manual_daily_processing() {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        do_action('env_analytics_daily_processing');
        update_option('env_analytics_last_manual_processing', current_time('mysql'));
        return true;
    }
    
    /**
     * Manual trigger for cleanup
     */
    public static function manual_cleanup() {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        do_action('env_analytics_weekly_cleanup');
        update_option('env_analytics_last_manual_cleanup', current_time('mysql'));
        return true;
    }
}
