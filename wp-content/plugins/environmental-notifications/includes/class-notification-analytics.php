<?php
/**
 * Notification Analytics Class
 * 
 * Provides comprehensive analytics and reporting for the notification system
 * including delivery rates, engagement metrics, and user behavior analysis.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Notification_Analytics {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_ajax_en_get_analytics_data', array($this, 'get_analytics_data'));
        add_action('wp_ajax_en_export_analytics', array($this, 'export_analytics'));
        add_filter('environmental_notifications_dashboard_widgets', array($this, 'add_analytics_widgets'));
    }
    
    /**
     * Track notification event
     */
    public function track_event($notification_id, $user_id, $event_type, $metadata = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'en_notification_analytics';
        
        // Get user agent and device info
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $device_info = $this->parse_user_agent($user_agent);
        
        $data = array(
            'notification_id' => $notification_id,
            'user_id' => $user_id,
            'event_type' => $event_type,
            'event_data' => wp_json_encode($metadata),
            'user_agent' => $user_agent,
            'device_type' => $device_info['device_type'],
            'platform' => $device_info['platform'],
            'browser' => $device_info['browser'],
            'ip_address' => $this->get_client_ip(),
            'created_at' => current_time('mysql')
        );
        
        return $wpdb->insert($table_name, $data);
    }
    
    /**
     * Get comprehensive analytics dashboard data
     */
    public function get_dashboard_analytics($date_range = 30) {
        global $wpdb;
        
        $analytics_table = $wpdb->prefix . 'en_notification_analytics';
        $notifications_table = $wpdb->prefix . 'en_notifications';
        $messages_table = $wpdb->prefix . 'en_messages';
        
        $date_filter = $wpdb->prepare("WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)", $date_range);
        
        // Overview metrics
        $overview = array(
            'total_notifications' => $this->get_total_notifications($date_range),
            'total_messages' => $this->get_total_messages($date_range),
            'delivery_rate' => $this->get_delivery_rate($date_range),
            'engagement_rate' => $this->get_engagement_rate($date_range),
            'active_users' => $this->get_active_users($date_range)
        );
        
        // Daily trends
        $daily_trends = $this->get_daily_trends($date_range);
        
        // Notification type breakdown
        $type_breakdown = $this->get_notification_type_breakdown($date_range);
        
        // Device and platform analytics
        $device_analytics = $this->get_device_analytics($date_range);
        
        // Top performing notifications
        $top_notifications = $this->get_top_performing_notifications($date_range);
        
        // User engagement patterns
        $engagement_patterns = $this->get_engagement_patterns($date_range);
        
        return array(
            'overview' => $overview,
            'daily_trends' => $daily_trends,
            'type_breakdown' => $type_breakdown,
            'device_analytics' => $device_analytics,
            'top_notifications' => $top_notifications,
            'engagement_patterns' => $engagement_patterns,
            'date_range' => $date_range,
            'last_updated' => current_time('mysql')
        );
    }
    
    /**
     * Get total notifications sent
     */
    private function get_total_notifications($date_range) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'en_notifications';
        
        return $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$table_name} 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $date_range));
    }
    
    /**
     * Get total messages sent
     */
    private function get_total_messages($date_range) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'en_messages';
        
        return $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$table_name} 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            AND deleted_at IS NULL
        ", $date_range));
    }
    
    /**
     * Calculate delivery rate
     */
    private function get_delivery_rate($date_range) {
        global $wpdb;
        
        $analytics_table = $wpdb->prefix . 'en_notification_analytics';
        
        $total = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$analytics_table} 
            WHERE event_type = 'sent' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $date_range));
        
        $delivered = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$analytics_table} 
            WHERE event_type = 'delivered' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $date_range));
        
        return $total > 0 ? round(($delivered / $total) * 100, 2) : 0;
    }
    
    /**
     * Calculate engagement rate
     */
    private function get_engagement_rate($date_range) {
        global $wpdb;
        
        $analytics_table = $wpdb->prefix . 'en_notification_analytics';
        
        $delivered = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT notification_id) 
            FROM {$analytics_table} 
            WHERE event_type = 'delivered' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $date_range));
        
        $engaged = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT notification_id) 
            FROM {$analytics_table} 
            WHERE event_type IN ('read', 'clicked') 
            AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $date_range));
        
        return $delivered > 0 ? round(($engaged / $delivered) * 100, 2) : 0;
    }
    
    /**
     * Get active users count
     */
    private function get_active_users($date_range) {
        global $wpdb;
        
        $analytics_table = $wpdb->prefix . 'en_notification_analytics';
        
        return $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT user_id) 
            FROM {$analytics_table} 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $date_range));
    }
    
    /**
     * Get daily trends data
     */
    private function get_daily_trends($date_range) {
        global $wpdb;
        
        $analytics_table = $wpdb->prefix . 'en_notification_analytics';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(created_at) as date,
                event_type,
                COUNT(*) as count
            FROM {$analytics_table}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(created_at), event_type
            ORDER BY date DESC, event_type
        ", $date_range));
    }
    
    /**
     * Get notification type breakdown
     */
    private function get_notification_type_breakdown($date_range) {
        global $wpdb;
        
        $notifications_table = $wpdb->prefix . 'en_notifications';
        $analytics_table = $wpdb->prefix . 'en_notification_analytics';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                n.type,
                COUNT(DISTINCT n.id) as total_sent,
                COUNT(DISTINCT CASE WHEN a.event_type = 'delivered' THEN a.notification_id END) as delivered,
                COUNT(DISTINCT CASE WHEN a.event_type = 'read' THEN a.notification_id END) as read_count,
                COUNT(DISTINCT CASE WHEN a.event_type = 'clicked' THEN a.notification_id END) as clicked
            FROM {$notifications_table} n
            LEFT JOIN {$analytics_table} a ON n.id = a.notification_id
            WHERE n.created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY n.type
            ORDER BY total_sent DESC
        ", $date_range));
    }
    
    /**
     * Get device and platform analytics
     */
    private function get_device_analytics($date_range) {
        global $wpdb;
        
        $analytics_table = $wpdb->prefix . 'en_notification_analytics';
        
        $device_breakdown = $wpdb->get_results($wpdb->prepare("
            SELECT 
                device_type,
                COUNT(*) as count,
                COUNT(DISTINCT user_id) as unique_users
            FROM {$analytics_table}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            AND device_type IS NOT NULL
            GROUP BY device_type
            ORDER BY count DESC
        ", $date_range));
        
        $platform_breakdown = $wpdb->get_results($wpdb->prepare("
            SELECT 
                platform,
                COUNT(*) as count,
                COUNT(DISTINCT user_id) as unique_users
            FROM {$analytics_table}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            AND platform IS NOT NULL
            GROUP BY platform
            ORDER BY count DESC
        ", $date_range));
        
        $browser_breakdown = $wpdb->get_results($wpdb->prepare("
            SELECT 
                browser,
                COUNT(*) as count,
                COUNT(DISTINCT user_id) as unique_users
            FROM {$analytics_table}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            AND browser IS NOT NULL
            GROUP BY browser
            ORDER BY count DESC
            LIMIT 10
        ", $date_range));
        
        return array(
            'devices' => $device_breakdown,
            'platforms' => $platform_breakdown,
            'browsers' => $browser_breakdown
        );
    }
    
    /**
     * Get top performing notifications
     */
    private function get_top_performing_notifications($date_range) {
        global $wpdb;
        
        $notifications_table = $wpdb->prefix . 'en_notifications';
        $analytics_table = $wpdb->prefix . 'en_notification_analytics';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                n.id,
                n.title,
                n.type,
                n.created_at,
                COUNT(DISTINCT CASE WHEN a.event_type = 'delivered' THEN a.user_id END) as delivered,
                COUNT(DISTINCT CASE WHEN a.event_type = 'read' THEN a.user_id END) as reads,
                COUNT(DISTINCT CASE WHEN a.event_type = 'clicked' THEN a.user_id END) as clicks,
                ROUND(
                    (COUNT(DISTINCT CASE WHEN a.event_type = 'read' THEN a.user_id END) / 
                     NULLIF(COUNT(DISTINCT CASE WHEN a.event_type = 'delivered' THEN a.user_id END), 0)) * 100, 2
                ) as read_rate
            FROM {$notifications_table} n
            LEFT JOIN {$analytics_table} a ON n.id = a.notification_id
            WHERE n.created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY n.id
            HAVING delivered > 0
            ORDER BY read_rate DESC, reads DESC
            LIMIT 10
        ", $date_range));
    }
    
    /**
     * Get user engagement patterns
     */
    private function get_engagement_patterns($date_range) {
        global $wpdb;
        
        $analytics_table = $wpdb->prefix . 'en_notification_analytics';
        
        // Hourly engagement patterns
        $hourly_patterns = $wpdb->get_results($wpdb->prepare("
            SELECT 
                HOUR(created_at) as hour,
                COUNT(*) as activity_count,
                COUNT(DISTINCT user_id) as unique_users
            FROM {$analytics_table}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            AND event_type IN ('read', 'clicked')
            GROUP BY HOUR(created_at)
            ORDER BY hour
        ", $date_range));
        
        // Daily engagement patterns
        $daily_patterns = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DAYOFWEEK(created_at) as day_of_week,
                COUNT(*) as activity_count,
                COUNT(DISTINCT user_id) as unique_users
            FROM {$analytics_table}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            AND event_type IN ('read', 'clicked')
            GROUP BY DAYOFWEEK(created_at)
            ORDER BY day_of_week
        ", $date_range));
        
        return array(
            'hourly' => $hourly_patterns,
            'daily' => $daily_patterns
        );
    }
    
    /**
     * Get user-specific analytics
     */
    public function get_user_analytics($user_id, $date_range = 30) {
        global $wpdb;
        
        $analytics_table = $wpdb->prefix . 'en_notification_analytics';
        $notifications_table = $wpdb->prefix . 'en_notifications';
        $messages_table = $wpdb->prefix . 'en_messages';
        
        // User engagement metrics
        $engagement_metrics = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(DISTINCT CASE WHEN event_type = 'delivered' THEN notification_id END) as notifications_received,
                COUNT(DISTINCT CASE WHEN event_type = 'read' THEN notification_id END) as notifications_read,
                COUNT(DISTINCT CASE WHEN event_type = 'clicked' THEN notification_id END) as notifications_clicked,
                MIN(created_at) as first_activity,
                MAX(created_at) as last_activity
            FROM {$analytics_table}
            WHERE user_id = %d
            AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $user_id, $date_range));
        
        // User messaging activity
        $messaging_activity = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as messages_sent,
                COUNT(DISTINCT conversation_id) as conversations_participated
            FROM {$messages_table}
            WHERE sender_id = %d
            AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            AND deleted_at IS NULL
        ", $user_id, $date_range));
        
        // Device preferences
        $device_preferences = $wpdb->get_results($wpdb->prepare("
            SELECT 
                device_type,
                platform,
                COUNT(*) as usage_count
            FROM {$analytics_table}
            WHERE user_id = %d
            AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            AND device_type IS NOT NULL
            GROUP BY device_type, platform
            ORDER BY usage_count DESC
        ", $user_id, $date_range));
        
        return array(
            'engagement' => $engagement_metrics,
            'messaging' => $messaging_activity,
            'devices' => $device_preferences,
            'user_id' => $user_id,
            'date_range' => $date_range
        );
    }
    
    /**
     * Export analytics data
     */
    public function export_analytics() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $date_range = intval($_GET['date_range'] ?? 30);
        $format = sanitize_text_field($_GET['format'] ?? 'csv');
        
        $analytics_data = $this->get_dashboard_analytics($date_range);
        
        if ($format === 'csv') {
            $this->export_csv($analytics_data, $date_range);
        } elseif ($format === 'json') {
            $this->export_json($analytics_data);
        }
    }
    
    /**
     * Export data as CSV
     */
    private function export_csv($data, $date_range) {
        $filename = 'environmental-notifications-analytics-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Overview section
        fputcsv($output, array('Environmental Notifications Analytics - Last ' . $date_range . ' Days'));
        fputcsv($output, array('Generated on: ' . current_time('Y-m-d H:i:s')));
        fputcsv($output, array(''));
        
        // Overview metrics
        fputcsv($output, array('Overview Metrics'));
        fputcsv($output, array('Metric', 'Value'));
        fputcsv($output, array('Total Notifications', $data['overview']['total_notifications']));
        fputcsv($output, array('Total Messages', $data['overview']['total_messages']));
        fputcsv($output, array('Delivery Rate (%)', $data['overview']['delivery_rate']));
        fputcsv($output, array('Engagement Rate (%)', $data['overview']['engagement_rate']));
        fputcsv($output, array('Active Users', $data['overview']['active_users']));
        fputcsv($output, array(''));
        
        // Notification type breakdown
        if (!empty($data['type_breakdown'])) {
            fputcsv($output, array('Notification Type Breakdown'));
            fputcsv($output, array('Type', 'Total Sent', 'Delivered', 'Read', 'Clicked'));
            
            foreach ($data['type_breakdown'] as $type) {
                fputcsv($output, array(
                    $type->type,
                    $type->total_sent,
                    $type->delivered,
                    $type->read_count,
                    $type->clicked
                ));
            }
            fputcsv($output, array(''));
        }
        
        // Device analytics
        if (!empty($data['device_analytics']['devices'])) {
            fputcsv($output, array('Device Analytics'));
            fputcsv($output, array('Device Type', 'Count', 'Unique Users'));
            
            foreach ($data['device_analytics']['devices'] as $device) {
                fputcsv($output, array(
                    $device->device_type,
                    $device->count,
                    $device->unique_users
                ));
            }
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export data as JSON
     */
    private function export_json($data) {
        $filename = 'environmental-notifications-analytics-' . date('Y-m-d') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo wp_json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * AJAX handler for getting analytics data
     */
    public function get_analytics_data() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $date_range = intval($_POST['date_range'] ?? 30);
        $analytics_data = $this->get_dashboard_analytics($date_range);
        
        wp_send_json_success($analytics_data);
    }
    
    /**
     * Add analytics widgets to dashboard
     */
    public function add_analytics_widgets($widgets) {
        $widgets['analytics_overview'] = array(
            'title' => __('Notification Analytics', 'environmental-notifications'),
            'callback' => array($this, 'render_analytics_widget'),
            'priority' => 1
        );
        
        return $widgets;
    }
    
    /**
     * Render analytics widget
     */
    public function render_analytics_widget() {
        $analytics = $this->get_dashboard_analytics(7); // Last 7 days for widget
        ?>
        <div class="en-analytics-widget">
            <div class="analytics-metrics">
                <div class="metric">
                    <span class="metric-value"><?php echo esc_html($analytics['overview']['total_notifications']); ?></span>
                    <span class="metric-label"><?php _e('Notifications Sent', 'environmental-notifications'); ?></span>
                </div>
                <div class="metric">
                    <span class="metric-value"><?php echo esc_html($analytics['overview']['delivery_rate']); ?>%</span>
                    <span class="metric-label"><?php _e('Delivery Rate', 'environmental-notifications'); ?></span>
                </div>
                <div class="metric">
                    <span class="metric-value"><?php echo esc_html($analytics['overview']['engagement_rate']); ?>%</span>
                    <span class="metric-label"><?php _e('Engagement Rate', 'environmental-notifications'); ?></span>
                </div>
                <div class="metric">
                    <span class="metric-value"><?php echo esc_html($analytics['overview']['active_users']); ?></span>
                    <span class="metric-label"><?php _e('Active Users', 'environmental-notifications'); ?></span>
                </div>
            </div>
            <div class="analytics-actions">
                <a href="<?php echo admin_url('admin.php?page=environmental-notifications-analytics'); ?>" class="button">
                    <?php _e('View Full Analytics', 'environmental-notifications'); ?>
                </a>
            </div>
        </div>
        <?php
    }
    
    /**
     * Parse user agent string
     */
    private function parse_user_agent($user_agent) {
        $device_type = 'desktop';
        $platform = 'unknown';
        $browser = 'unknown';
        
        // Detect mobile devices
        if (preg_match('/Mobile|Android|BlackBerry|iPhone|iPad|iPod|Opera Mini|IEMobile/i', $user_agent)) {
            $device_type = 'mobile';
        } elseif (preg_match('/Tablet|iPad/i', $user_agent)) {
            $device_type = 'tablet';
        }
        
        // Detect platform
        if (preg_match('/Windows NT/i', $user_agent)) {
            $platform = 'Windows';
        } elseif (preg_match('/Mac OS X/i', $user_agent)) {
            $platform = 'macOS';
        } elseif (preg_match('/Linux/i', $user_agent)) {
            $platform = 'Linux';
        } elseif (preg_match('/Android/i', $user_agent)) {
            $platform = 'Android';
        } elseif (preg_match('/iOS|iPhone|iPad/i', $user_agent)) {
            $platform = 'iOS';
        }
        
        // Detect browser
        if (preg_match('/Chrome/i', $user_agent) && !preg_match('/Edge/i', $user_agent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox/i', $user_agent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari/i', $user_agent) && !preg_match('/Chrome/i', $user_agent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Edge/i', $user_agent)) {
            $browser = 'Edge';
        } elseif (preg_match('/Opera/i', $user_agent)) {
            $browser = 'Opera';
        }
        
        return array(
            'device_type' => $device_type,
            'platform' => $platform,
            'browser' => $browser
        );
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
