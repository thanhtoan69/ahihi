<?php
/**
 * Analytics Class
 * 
 * Handles support analytics, metrics collection, and reporting
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Analytics {
    
    private static $instance = null;
    private $table_analytics;
    private $table_sessions;
    private $table_tickets;
    private $table_faq;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->table_analytics = $wpdb->prefix . 'elc_analytics';
        $this->table_sessions = $wpdb->prefix . 'elc_chat_sessions';
        $this->table_tickets = $wpdb->prefix . 'elc_support_tickets';
        $this->table_faq = $wpdb->prefix . 'elc_faq';
        
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('wp_ajax_elc_get_analytics_dashboard', array($this, 'get_analytics_dashboard'));
        add_action('wp_ajax_elc_get_chat_analytics', array($this, 'get_chat_analytics'));
        add_action('wp_ajax_elc_get_ticket_analytics', array($this, 'get_ticket_analytics'));
        add_action('wp_ajax_elc_get_faq_analytics', array($this, 'get_faq_analytics'));
        add_action('wp_ajax_elc_export_analytics', array($this, 'export_analytics'));
        
        // Scheduled analytics tasks
        add_action('elc_daily_analytics', array($this, 'generate_daily_report'));
        add_action('elc_weekly_analytics', array($this, 'generate_weekly_report'));
        add_action('elc_monthly_analytics', array($this, 'generate_monthly_report'));
        
        // Real-time event tracking
        add_action('elc_new_message', array($this, 'track_message_event'), 10, 3);
        add_action('elc_ticket_created', array($this, 'track_ticket_event'), 10, 2);
        add_action('elc_chatbot_escalation', array($this, 'track_escalation_event'));
    }
    
    /**
     * Get comprehensive analytics dashboard data
     */
    public function get_analytics_dashboard() {
        check_ajax_referer('elc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'environmental-live-chat')));
        }
        
        $date_range = sanitize_text_field($_POST['date_range'] ?? '30days');
        $date_from = sanitize_text_field($_POST['date_from'] ?? '');
        $date_to = sanitize_text_field($_POST['date_to'] ?? '');
        
        // Calculate date range
        if ($date_range === 'custom' && !empty($date_from) && !empty($date_to)) {
            $start_date = $date_from;
            $end_date = $date_to;
        } else {
            list($start_date, $end_date) = $this->calculate_date_range($date_range);
        }
        
        $dashboard_data = array(
            'overview' => $this->get_overview_metrics($start_date, $end_date),
            'chat_metrics' => $this->get_chat_metrics($start_date, $end_date),
            'ticket_metrics' => $this->get_ticket_metrics($start_date, $end_date),
            'faq_metrics' => $this->get_faq_metrics($start_date, $end_date),
            'performance_metrics' => $this->get_performance_metrics($start_date, $end_date),
            'trends' => $this->get_trend_data($start_date, $end_date),
            'agent_performance' => $this->get_agent_performance($start_date, $end_date)
        );
        
        wp_send_json_success($dashboard_data);
    }
    
    /**
     * Get chat-specific analytics
     */
    public function get_chat_analytics() {
        check_ajax_referer('elc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'environmental-live-chat')));
        }
        
        $date_range = sanitize_text_field($_POST['date_range'] ?? '30days');
        list($start_date, $end_date) = $this->calculate_date_range($date_range);
        
        $chat_analytics = $this->get_detailed_chat_analytics($start_date, $end_date);
        
        wp_send_json_success($chat_analytics);
    }
    
    /**
     * Get ticket-specific analytics
     */
    public function get_ticket_analytics() {
        check_ajax_referer('elc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'environmental-live-chat')));
        }
        
        $date_range = sanitize_text_field($_POST['date_range'] ?? '30days');
        list($start_date, $end_date) = $this->calculate_date_range($date_range);
        
        $ticket_analytics = $this->get_detailed_ticket_analytics($start_date, $end_date);
        
        wp_send_json_success($ticket_analytics);
    }
    
    /**
     * Get FAQ-specific analytics
     */
    public function get_faq_analytics() {
        check_ajax_referer('elc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'environmental-live-chat')));
        }
        
        $date_range = sanitize_text_field($_POST['date_range'] ?? '30days');
        list($start_date, $end_date) = $this->calculate_date_range($date_range);
        
        $faq_analytics = $this->get_detailed_faq_analytics($start_date, $end_date);
        
        wp_send_json_success($faq_analytics);
    }
    
    /**
     * Export analytics data
     */
    public function export_analytics() {
        check_ajax_referer('elc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'environmental-live-chat')));
        }
        
        $export_type = sanitize_text_field($_POST['export_type'] ?? 'overview');
        $format = sanitize_text_field($_POST['format'] ?? 'csv');
        $date_range = sanitize_text_field($_POST['date_range'] ?? '30days');
        
        list($start_date, $end_date) = $this->calculate_date_range($date_range);
        
        $data = $this->prepare_export_data($export_type, $start_date, $end_date);
        
        if ($format === 'json') {
            $exported_data = json_encode($data, JSON_PRETTY_PRINT);
            $filename = "elc-analytics-{$export_type}-" . date('Y-m-d') . '.json';
        } else {
            $exported_data = $this->convert_to_csv($data);
            $filename = "elc-analytics-{$export_type}-" . date('Y-m-d') . '.csv';
        }
        
        wp_send_json_success(array(
            'data' => $exported_data,
            'filename' => $filename,
            'mime_type' => $format === 'json' ? 'application/json' : 'text/csv'
        ));
    }
    
    // Core Analytics Methods
    
    private function get_overview_metrics($start_date, $end_date) {
        global $wpdb;
        
        $metrics = array();
        
        // Total interactions
        $metrics['total_chats'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_sessions} 
             WHERE DATE(created_at) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        $metrics['total_tickets'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_tickets} 
             WHERE DATE(created_at) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        $metrics['total_faq_searches'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_analytics} 
             WHERE metric_type = 'faq_search'
             AND DATE(recorded_at) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        // Customer satisfaction
        $satisfaction_data = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                AVG(rating) as avg_chat_rating,
                COUNT(rating) as total_ratings
             FROM {$this->table_sessions} 
             WHERE rating > 0 
             AND DATE(created_at) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        $metrics['avg_satisfaction'] = round($satisfaction_data->avg_chat_rating ?? 0, 2);
        $metrics['total_ratings'] = $satisfaction_data->total_ratings ?? 0;
        
        // Response times
        $response_times = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                AVG(TIMESTAMPDIFF(MINUTE, created_at, 
                    (SELECT MIN(sent_at) FROM {$wpdb->prefix}elc_chat_messages m 
                     WHERE m.session_id = s.id AND m.sender_type = 'operator'))) as avg_chat_response,
                AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_ticket_resolution
             FROM {$this->table_sessions} s
             WHERE DATE(created_at) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        $metrics['avg_chat_response_time'] = round($response_times->avg_chat_response ?? 0, 1);
        $metrics['avg_ticket_resolution_time'] = round($response_times->avg_ticket_resolution ?? 0, 1);
        
        // Conversion rates
        $metrics['chat_to_ticket_rate'] = $this->calculate_chat_to_ticket_rate($start_date, $end_date);
        $metrics['chatbot_escalation_rate'] = $this->calculate_chatbot_escalation_rate($start_date, $end_date);
        
        return $metrics;
    }
    
    private function get_chat_metrics($start_date, $end_date) {
        global $wpdb;
        
        $metrics = array();
        
        // Chat volume by status
        $status_counts = $wpdb->get_results($wpdb->prepare(
            "SELECT status, COUNT(*) as count
             FROM {$this->table_sessions}
             WHERE DATE(created_at) BETWEEN %s AND %s
             GROUP BY status",
            $start_date, $end_date
        ), OBJECT_K);
        
        $metrics['by_status'] = $status_counts;
        
        // Chat volume by department
        $department_counts = $wpdb->get_results($wpdb->prepare(
            "SELECT department, COUNT(*) as count
             FROM {$this->table_sessions}
             WHERE DATE(created_at) BETWEEN %s AND %s
             GROUP BY department
             ORDER BY count DESC",
            $start_date, $end_date
        ));
        
        $metrics['by_department'] = $department_counts;
        
        // Average session duration
        $avg_duration = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, COALESCE(ended_at, last_activity)))
             FROM {$this->table_sessions}
             WHERE DATE(created_at) BETWEEN %s AND %s
             AND status = 'ended'",
            $start_date, $end_date
        ));
        
        $metrics['avg_session_duration'] = round($avg_duration ?? 0, 1);
        
        // Peak hours
        $hourly_distribution = $wpdb->get_results($wpdb->prepare(
            "SELECT HOUR(created_at) as hour, COUNT(*) as count
             FROM {$this->table_sessions}
             WHERE DATE(created_at) BETWEEN %s AND %s
             GROUP BY HOUR(created_at)
             ORDER BY hour",
            $start_date, $end_date
        ));
        
        $metrics['hourly_distribution'] = $hourly_distribution;
        
        return $metrics;
    }
    
    private function get_ticket_metrics($start_date, $end_date) {
        global $wpdb;
        
        $metrics = array();
        
        // Ticket volume by status
        $status_counts = $wpdb->get_results($wpdb->prepare(
            "SELECT status, COUNT(*) as count
             FROM {$this->table_tickets}
             WHERE DATE(created_at) BETWEEN %s AND %s
             GROUP BY status",
            $start_date, $end_date
        ), OBJECT_K);
        
        $metrics['by_status'] = $status_counts;
        
        // Ticket volume by category
        $category_counts = $wpdb->get_results($wpdb->prepare(
            "SELECT category, COUNT(*) as count
             FROM {$this->table_tickets}
             WHERE DATE(created_at) BETWEEN %s AND %s
             GROUP BY category
             ORDER BY count DESC",
            $start_date, $end_date
        ));
        
        $metrics['by_category'] = $category_counts;
        
        // Priority distribution
        $priority_counts = $wpdb->get_results($wpdb->prepare(
            "SELECT priority, COUNT(*) as count
             FROM {$this->table_tickets}
             WHERE DATE(created_at) BETWEEN %s AND %s
             GROUP BY priority",
            $start_date, $end_date
        ), OBJECT_K);
        
        $metrics['by_priority'] = $priority_counts;
        
        // Resolution metrics
        $resolution_data = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(CASE WHEN status IN ('resolved', 'closed') THEN 1 END) as resolved_count,
                COUNT(*) as total_count,
                AVG(CASE WHEN resolved_at IS NOT NULL 
                    THEN TIMESTAMPDIFF(HOUR, created_at, resolved_at) END) as avg_resolution_time
             FROM {$this->table_tickets}
             WHERE DATE(created_at) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        $metrics['resolution_rate'] = $resolution_data->total_count > 0 
            ? round(($resolution_data->resolved_count / $resolution_data->total_count) * 100, 1)
            : 0;
        $metrics['avg_resolution_time'] = round($resolution_data->avg_resolution_time ?? 0, 1);
        
        return $metrics;
    }
    
    private function get_faq_metrics($start_date, $end_date) {
        global $wpdb;
        
        $metrics = array();
        
        // FAQ searches
        $search_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_analytics}
             WHERE metric_type = 'faq_search'
             AND DATE(recorded_at) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        $metrics['total_searches'] = $search_count;
        
        // Most searched terms
        $search_terms = $wpdb->get_results($wpdb->prepare(
            "SELECT JSON_EXTRACT(metadata, '$.query') as search_term, COUNT(*) as count
             FROM {$this->table_analytics}
             WHERE metric_type = 'faq_search'
             AND DATE(recorded_at) BETWEEN %s AND %s
             GROUP BY JSON_EXTRACT(metadata, '$.query')
             ORDER BY count DESC
             LIMIT 10",
            $start_date, $end_date
        ));
        
        $metrics['popular_searches'] = $search_terms;
        
        // FAQ ratings
        $rating_data = $wpdb->get_row(
            "SELECT 
                SUM(helpful_votes) as total_helpful,
                SUM(not_helpful_votes) as total_not_helpful,
                COUNT(*) as total_faqs
             FROM {$this->table_faq}
             WHERE status = 'published'"
        );
        
        $total_votes = ($rating_data->total_helpful ?? 0) + ($rating_data->total_not_helpful ?? 0);
        $metrics['helpfulness_rate'] = $total_votes > 0 
            ? round(($rating_data->total_helpful / $total_votes) * 100, 1)
            : 0;
        
        $metrics['total_votes'] = $total_votes;
        
        // Most viewed FAQs
        $popular_faqs = $wpdb->get_results(
            "SELECT question, view_count, helpful_votes, not_helpful_votes
             FROM {$this->table_faq}
             WHERE status = 'published'
             ORDER BY view_count DESC
             LIMIT 5"
        );
        
        $metrics['popular_faqs'] = $popular_faqs;
        
        return $metrics;
    }
    
    private function get_performance_metrics($start_date, $end_date) {
        global $wpdb;
        
        $metrics = array();
        
        // Agent workload
        $agent_workload = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                u.display_name,
                COUNT(DISTINCT s.id) as chat_count,
                COUNT(DISTINCT t.id) as ticket_count,
                AVG(s.rating) as avg_rating
             FROM {$wpdb->users} u
             LEFT JOIN {$this->table_sessions} s ON u.ID = s.operator_id 
                 AND DATE(s.created_at) BETWEEN %s AND %s
             LEFT JOIN {$this->table_tickets} t ON u.ID = t.assigned_to 
                 AND DATE(t.created_at) BETWEEN %s AND %s
             WHERE u.ID IN (
                 SELECT DISTINCT operator_id FROM {$this->table_sessions} WHERE operator_id IS NOT NULL
                 UNION
                 SELECT DISTINCT assigned_to FROM {$this->table_tickets} WHERE assigned_to IS NOT NULL
             )
             GROUP BY u.ID, u.display_name
             ORDER BY (chat_count + ticket_count) DESC",
            $start_date, $end_date, $start_date, $end_date
        ));
        
        $metrics['agent_workload'] = $agent_workload;
        
        // System availability
        $total_hours = (strtotime($end_date) - strtotime($start_date)) / 3600;
        $business_hours = $this->calculate_business_hours($start_date, $end_date);
        
        $metrics['system_availability'] = array(
            'total_hours' => round($total_hours, 1),
            'business_hours' => round($business_hours, 1),
            'uptime_percentage' => 99.9 // This would be calculated from actual monitoring data
        );
        
        return $metrics;
    }
    
    private function get_trend_data($start_date, $end_date) {
        global $wpdb;
        
        // Daily trends
        $daily_trends = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(created_at) as date,
                COUNT(CASE WHEN 'chat' = 'chat' THEN 1 END) as chats,
                COUNT(CASE WHEN 'ticket' = 'ticket' THEN 1 END) as tickets
             FROM (
                 SELECT created_at, 'chat' as type FROM {$this->table_sessions}
                 WHERE DATE(created_at) BETWEEN %s AND %s
                 UNION ALL
                 SELECT created_at, 'ticket' as type FROM {$this->table_tickets}
                 WHERE DATE(created_at) BETWEEN %s AND %s
             ) combined
             GROUP BY DATE(created_at)
             ORDER BY date",
            $start_date, $end_date, $start_date, $end_date
        ));
        
        return array(
            'daily_trends' => $daily_trends
        );
    }
    
    private function get_agent_performance($start_date, $end_date) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT 
                u.ID,
                u.display_name,
                COUNT(DISTINCT s.id) as total_chats,
                COUNT(DISTINCT t.id) as total_tickets,
                AVG(s.rating) as avg_rating,
                AVG(TIMESTAMPDIFF(MINUTE, s.created_at, 
                    (SELECT MIN(sent_at) FROM {$wpdb->prefix}elc_chat_messages m 
                     WHERE m.session_id = s.id AND m.sender_type = 'operator'))) as avg_response_time
             FROM {$wpdb->users} u
             LEFT JOIN {$this->table_sessions} s ON u.ID = s.operator_id 
                 AND DATE(s.created_at) BETWEEN %s AND %s
             LEFT JOIN {$this->table_tickets} t ON u.ID = t.assigned_to 
                 AND DATE(t.created_at) BETWEEN %s AND %s
             WHERE u.ID IN (
                 SELECT user_id FROM {$wpdb->prefix}elc_chat_operators
             )
             GROUP BY u.ID, u.display_name
             HAVING total_chats > 0 OR total_tickets > 0
             ORDER BY (total_chats + total_tickets) DESC",
            $start_date, $end_date, $start_date, $end_date
        ));
    }
    
    // Event Tracking Methods
    
    public function track_message_event($session_id, $message_id, $sender_type) {
        $this->log_metric('message_sent', 1, array(
            'session_id' => $session_id,
            'message_id' => $message_id,
            'sender_type' => $sender_type
        ));
    }
    
    public function track_ticket_event($ticket_id, $ticket_data) {
        $this->log_metric('ticket_created', 1, array(
            'ticket_id' => $ticket_id,
            'category' => $ticket_data['category'],
            'priority' => $ticket_data['priority']
        ));
    }
    
    public function track_escalation_event($session_id) {
        $this->log_metric('chatbot_escalation', 1, array(
            'session_id' => $session_id,
            'escalation_time' => current_time('mysql')
        ));
    }
    
    // Report Generation Methods
    
    public function generate_daily_report() {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $report_data = $this->get_overview_metrics($yesterday, $yesterday);
        
        // Save report
        $this->save_report('daily', $yesterday, $report_data);
        
        // Send email if configured
        $send_email = get_option('elc_daily_reports_email', false);
        if ($send_email) {
            $this->send_analytics_email('daily', $yesterday, $report_data);
        }
    }
    
    public function generate_weekly_report() {
        $week_start = date('Y-m-d', strtotime('last monday', strtotime('-1 week')));
        $week_end = date('Y-m-d', strtotime('last sunday', strtotime('-1 week')));
        
        $report_data = $this->get_overview_metrics($week_start, $week_end);
        
        $this->save_report('weekly', $week_start, $report_data);
        
        $send_email = get_option('elc_weekly_reports_email', false);
        if ($send_email) {
            $this->send_analytics_email('weekly', $week_start, $report_data);
        }
    }
    
    public function generate_monthly_report() {
        $month_start = date('Y-m-01', strtotime('last month'));
        $month_end = date('Y-m-t', strtotime('last month'));
        
        $report_data = $this->get_overview_metrics($month_start, $month_end);
        
        $this->save_report('monthly', $month_start, $report_data);
        
        $send_email = get_option('elc_monthly_reports_email', false);
        if ($send_email) {
            $this->send_analytics_email('monthly', $month_start, $report_data);
        }
    }
    
    // Helper Methods
    
    private function log_metric($metric_type, $value = 1, $metadata = array()) {
        global $wpdb;
        
        $wpdb->insert(
            $this->table_analytics,
            array(
                'metric_type' => $metric_type,
                'metric_value' => $value,
                'metadata' => json_encode($metadata),
                'recorded_at' => current_time('mysql')
            )
        );
    }
    
    private function calculate_date_range($range) {
        switch ($range) {
            case '7days':
                return array(date('Y-m-d', strtotime('-7 days')), date('Y-m-d'));
            case '30days':
                return array(date('Y-m-d', strtotime('-30 days')), date('Y-m-d'));
            case '90days':
                return array(date('Y-m-d', strtotime('-90 days')), date('Y-m-d'));
            case 'thismonth':
                return array(date('Y-m-01'), date('Y-m-d'));
            case 'lastmonth':
                return array(date('Y-m-01', strtotime('last month')), date('Y-m-t', strtotime('last month')));
            default:
                return array(date('Y-m-d', strtotime('-30 days')), date('Y-m-d'));
        }
    }
    
    private function calculate_chat_to_ticket_rate($start_date, $end_date) {
        global $wpdb;
        
        $chat_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_sessions} 
             WHERE DATE(created_at) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        $ticket_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_tickets} 
             WHERE DATE(created_at) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        return $chat_count > 0 ? round(($ticket_count / $chat_count) * 100, 1) : 0;
    }
    
    private function calculate_chatbot_escalation_rate($start_date, $end_date) {
        global $wpdb;
        
        $total_chatbot_sessions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_sessions} 
             WHERE operator_id IS NULL 
             AND DATE(created_at) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        $escalated_sessions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_sessions} 
             WHERE chatbot_escalated = 1 
             AND DATE(created_at) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        return $total_chatbot_sessions > 0 ? round(($escalated_sessions / $total_chatbot_sessions) * 100, 1) : 0;
    }
    
    private function calculate_business_hours($start_date, $end_date) {
        // This is a simplified calculation - would need actual business hours configuration
        $days = (strtotime($end_date) - strtotime($start_date)) / (24 * 3600);
        $weekdays = $days * (5/7); // Assuming 5 working days per week
        return $weekdays * 8; // Assuming 8 hour work days
    }
    
    private function prepare_export_data($export_type, $start_date, $end_date) {
        switch ($export_type) {
            case 'overview':
                return $this->get_overview_metrics($start_date, $end_date);
            case 'chats':
                return $this->get_detailed_chat_analytics($start_date, $end_date);
            case 'tickets':
                return $this->get_detailed_ticket_analytics($start_date, $end_date);
            case 'faq':
                return $this->get_detailed_faq_analytics($start_date, $end_date);
            default:
                return array();
        }
    }
    
    private function convert_to_csv($data) {
        $csv = '';
        
        if (!empty($data)) {
            // Create header row
            $headers = array_keys($data);
            $csv .= implode(',', $headers) . "\n";
            
            // Add data rows
            if (is_array(reset($data))) {
                foreach (reset($data) as $row) {
                    $csv .= implode(',', array_values((array)$row)) . "\n";
                }
            } else {
                $csv .= implode(',', array_values($data)) . "\n";
            }
        }
        
        return $csv;
    }
    
    private function save_report($type, $date, $data) {
        global $wpdb;
        
        $wpdb->insert(
            $this->table_analytics,
            array(
                'metric_type' => "report_{$type}",
                'metric_value' => 1,
                'metadata' => json_encode(array(
                    'report_date' => $date,
                    'data' => $data
                )),
                'recorded_at' => current_time('mysql')
            )
        );
    }
    
    private function send_analytics_email($type, $date, $data) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = sprintf(__('[%s] %s Analytics Report - %s', 'environmental-live-chat'), 
            $site_name, ucfirst($type), $date);
        
        $message = sprintf(__("Analytics Report for %s\n\n", 'environmental-live-chat'), $date);
        $message .= sprintf(__("Total Chats: %d\n", 'environmental-live-chat'), $data['total_chats'] ?? 0);
        $message .= sprintf(__("Total Tickets: %d\n", 'environmental-live-chat'), $data['total_tickets'] ?? 0);
        $message .= sprintf(__("Average Satisfaction: %.1f/5\n", 'environmental-live-chat'), $data['avg_satisfaction'] ?? 0);
        $message .= sprintf(__("Average Response Time: %.1f minutes\n", 'environmental-live-chat'), $data['avg_chat_response_time'] ?? 0);
        
        wp_mail($admin_email, $subject, $message);
    }
    
    private function get_detailed_chat_analytics($start_date, $end_date) {
        return array_merge(
            $this->get_chat_metrics($start_date, $end_date),
            array(
                'chatbot_analytics' => Environmental_Chatbot_System::get_instance()->get_chatbot_analytics($start_date, $end_date),
                'live_chat_analytics' => Environmental_Live_Chat_System::get_instance()->get_chat_statistics($start_date, $end_date)
            )
        );
    }
    
    private function get_detailed_ticket_analytics($start_date, $end_date) {
        return Environmental_Support_Tickets::get_instance()->get_ticket_statistics($start_date, $end_date);
    }
    
    private function get_detailed_faq_analytics($start_date, $end_date) {
        return Environmental_FAQ_Manager::get_instance()->get_faq_statistics($start_date, $end_date);
    }
    
    /**
     * Get real-time metrics for dashboard
     */
    public function get_realtime_metrics() {
        global $wpdb;
        
        $metrics = array();
        
        // Active chats
        $metrics['active_chats'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_sessions} 
             WHERE status = 'active'"
        );
        
        // Waiting chats
        $metrics['waiting_chats'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_sessions} 
             WHERE status = 'waiting'"
        );
        
        // Open tickets
        $metrics['open_tickets'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_tickets} 
             WHERE status IN ('open', 'customer-reply')"
        );
        
        // Online operators
        $metrics['online_operators'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}elc_chat_operators 
             WHERE status = 'online' 
             AND last_seen > DATE_SUB(NOW(), INTERVAL 5 MINUTE)"
        );
        
        return $metrics;
    }
}

// Initialize the analytics system
Environmental_Analytics::get_instance();
