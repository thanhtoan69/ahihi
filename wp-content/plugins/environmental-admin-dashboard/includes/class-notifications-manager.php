<?php
/**
 * Notifications Manager Class
 * 
 * Handles admin notifications system, alerts, and communication
 * 
 * @package EnvironmentalAdminDashboard
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Notifications_Manager {
    
    private static $instance = null;
    private $notification_types = array();
    
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
        $this->init_notification_types();
        $this->init_hooks();
    }
    
    /**
     * Initialize notification types
     */
    private function init_notification_types() {
        $this->notification_types = array(
            'system_alert' => __('System Alert', 'env-admin-dashboard'),
            'user_notification' => __('User Notification', 'env-admin-dashboard'),
            'activity_reminder' => __('Activity Reminder', 'env-admin-dashboard'),
            'goal_deadline' => __('Goal Deadline', 'env-admin-dashboard'),
            'achievement_unlock' => __('Achievement Unlocked', 'env-admin-dashboard'),
            'system_maintenance' => __('System Maintenance', 'env-admin-dashboard')
        );
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_env_send_notification', array($this, 'ajax_send_notification'));
        add_action('wp_ajax_env_manage_notification', array($this, 'ajax_manage_notification'));
        add_action('admin_notices', array($this, 'display_admin_notices'));
        add_action('wp_dashboard_setup', array($this, 'add_notifications_widget'));
        
        // Scheduled notifications
        add_action('env_send_scheduled_notifications', array($this, 'send_scheduled_notifications'));
        if (!wp_next_scheduled('env_send_scheduled_notifications')) {
            wp_schedule_event(time(), 'hourly', 'env_send_scheduled_notifications');
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'env-admin-dashboard',
            __('Notifications', 'env-admin-dashboard'),
            __('Notifications', 'env-admin-dashboard'),
            'manage_options',
            'env-notifications',
            array($this, 'render_notifications_page')
        );
    }
    
    /**
     * Render notifications page
     */
    public function render_notifications_page() {
        // Include the notifications template
        $template_path = ENV_ADMIN_DASHBOARD_PLUGIN_PATH . 'admin/notifications.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="wrap"><h1>' . __('Notifications', 'env-admin-dashboard') . '</h1>';
            echo '<p>' . __('Notifications interface not found.', 'env-admin-dashboard') . '</p></div>';
        }
    }
    
    /**
     * Add notifications widget to dashboard
     */
    public function add_notifications_widget() {
        wp_add_dashboard_widget(
            'env_notifications_widget',
            __('Environmental Notifications', 'env-admin-dashboard'),
            array($this, 'render_notifications_widget')
        );
    }
    
    /**
     * Render notifications widget
     */
    public function render_notifications_widget() {
        $recent_notifications = $this->get_recent_notifications(5);
        ?>
        <div class="env-notifications-widget">
            <?php if (empty($recent_notifications)): ?>
                <p><?php _e('No recent notifications.', 'env-admin-dashboard'); ?></p>
            <?php else: ?>
                <ul class="env-notification-list">
                    <?php foreach ($recent_notifications as $notification): ?>
                    <li class="env-notification-item <?php echo esc_attr($notification['type']); ?>">
                        <div class="notification-content">
                            <strong><?php echo esc_html($notification['title']); ?></strong>
                            <p><?php echo esc_html($notification['message']); ?></p>
                            <small><?php echo esc_html($notification['date']); ?></small>
                        </div>
                        <div class="notification-actions">
                            <button class="button-link" onclick="envNotifications.markAsRead(<?php echo $notification['id']; ?>)">
                                <?php _e('Mark as Read', 'env-admin-dashboard'); ?>
                            </button>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div class="env-widget-footer">
                    <a href="<?php echo admin_url('admin.php?page=env-notifications'); ?>" class="button">
                        <?php _e('View All Notifications', 'env-admin-dashboard'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Get recent notifications
     */
    public function get_recent_notifications($limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'environmental_notifications';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return array();
        }
        
        $notifications = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $table_name 
            WHERE status = 'active' 
            ORDER BY created_date DESC 
            LIMIT %d
        ", $limit), ARRAY_A);
        
        return $notifications ?: array();
    }
    
    /**
     * Send notification
     */
    public function send_notification($type, $title, $message, $recipients = array(), $schedule_date = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'environmental_notifications';
        
        // Create table if it doesn't exist
        $this->create_notifications_table();
        
        $notification_data = array(
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'recipients' => is_array($recipients) ? json_encode($recipients) : $recipients,
            'status' => $schedule_date ? 'scheduled' : 'active',
            'schedule_date' => $schedule_date,
            'created_date' => current_time('mysql'),
            'created_by' => get_current_user_id()
        );
        
        $result = $wpdb->insert($table_name, $notification_data);
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => __('Failed to save notification', 'env-admin-dashboard')
            );
        }
        
        $notification_id = $wpdb->insert_id;
        
        // Send immediately if not scheduled
        if (!$schedule_date) {
            $this->deliver_notification($notification_id, $notification_data);
        }
        
        return array(
            'success' => true,
            'message' => __('Notification sent successfully', 'env-admin-dashboard'),
            'notification_id' => $notification_id
        );
    }
    
    /**
     * Deliver notification
     */
    private function deliver_notification($notification_id, $notification_data) {
        $recipients = json_decode($notification_data['recipients'], true);
        
        if (empty($recipients)) {
            // Send to all admins if no specific recipients
            $recipients = $this->get_admin_users();
        }
        
        foreach ($recipients as $recipient) {
            if (is_numeric($recipient)) {
                // User ID
                $this->send_user_notification($recipient, $notification_data);
            } elseif (is_email($recipient)) {
                // Email address
                $this->send_email_notification($recipient, $notification_data);
            }
        }
        
        // Mark as delivered
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'environmental_notifications',
            array('status' => 'delivered', 'delivered_date' => current_time('mysql')),
            array('id' => $notification_id),
            array('%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * Send user notification
     */
    private function send_user_notification($user_id, $notification_data) {
        // Add to user meta for dashboard display
        $user_notifications = get_user_meta($user_id, 'env_notifications', true);
        if (!is_array($user_notifications)) {
            $user_notifications = array();
        }
        
        $user_notifications[] = array(
            'type' => $notification_data['type'],
            'title' => $notification_data['title'],
            'message' => $notification_data['message'],
            'date' => current_time('mysql'),
            'read' => false
        );
        
        // Keep only latest 50 notifications per user
        if (count($user_notifications) > 50) {
            $user_notifications = array_slice($user_notifications, -50);
        }
        
        update_user_meta($user_id, 'env_notifications', $user_notifications);
    }
    
    /**
     * Send email notification
     */
    private function send_email_notification($email, $notification_data) {
        $subject = $notification_data['title'];
        $message = $notification_data['message'];
        
        // Add email template
        $email_content = $this->get_email_template($notification_data);
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail($email, $subject, $email_content, $headers);
    }
    
    /**
     * Get email template
     */
    private function get_email_template($notification_data) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php echo esc_html($notification_data['title']); ?></title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background: #2c5530; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; }
                .footer { background: #f4f4f4; padding: 20px; text-align: center; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1><?php echo esc_html($notification_data['title']); ?></h1>
            </div>
            <div class="content">
                <p><?php echo nl2br(esc_html($notification_data['message'])); ?></p>
                <p>
                    <strong><?php _e('Notification Type:', 'env-admin-dashboard'); ?></strong> 
                    <?php echo esc_html($this->notification_types[$notification_data['type']] ?? $notification_data['type']); ?>
                </p>
            </div>
            <div class="footer">
                <p><?php _e('This notification was sent from the Environmental Platform.', 'env-admin-dashboard'); ?></p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get admin users
     */
    private function get_admin_users() {
        $admin_users = get_users(array(
            'role' => 'administrator',
            'fields' => 'ID'
        ));
        
        return $admin_users;
    }
    
    /**
     * Create notifications table
     */
    private function create_notifications_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'environmental_notifications';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id int(11) NOT NULL AUTO_INCREMENT,
                type varchar(50) NOT NULL,
                title varchar(255) NOT NULL,
                message text NOT NULL,
                recipients text,
                status varchar(20) DEFAULT 'active',
                schedule_date datetime NULL,
                created_date datetime NOT NULL,
                delivered_date datetime NULL,
                created_by int(11) NOT NULL,
                PRIMARY KEY (id),
                KEY type (type),
                KEY status (status),
                KEY created_date (created_date)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
    
    /**
     * Handle notification action
     */
    public function handle_notification_action($action, $notification_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'environmental_notifications';
        
        switch ($action) {
            case 'mark_read':
                $result = $wpdb->update(
                    $table_name,
                    array('status' => 'read'),
                    array('id' => $notification_id),
                    array('%s'),
                    array('%d')
                );
                break;
                
            case 'delete':
                $result = $wpdb->delete(
                    $table_name,
                    array('id' => $notification_id),
                    array('%d')
                );
                break;
                
            case 'resend':
                $notification = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table_name WHERE id = %d",
                    $notification_id
                ), ARRAY_A);
                
                if ($notification) {
                    $this->deliver_notification($notification_id, $notification);
                    $result = true;
                } else {
                    $result = false;
                }
                break;
                
            default:
                $result = false;
        }
        
        if ($result !== false) {
            return array(
                'success' => true,
                'message' => __('Notification action completed successfully', 'env-admin-dashboard')
            );
        } else {
            return array(
                'success' => false,
                'message' => __('Failed to perform notification action', 'env-admin-dashboard')
            );
        }
    }
    
    /**
     * Send scheduled notifications
     */
    public function send_scheduled_notifications() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'environmental_notifications';
        
        $scheduled_notifications = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $table_name 
            WHERE status = 'scheduled' 
            AND schedule_date <= %s
        ", current_time('mysql')), ARRAY_A);
        
        foreach ($scheduled_notifications as $notification) {
            $this->deliver_notification($notification['id'], $notification);
        }
    }
    
    /**
     * Display admin notices
     */
    public function display_admin_notices() {
        $current_user_id = get_current_user_id();
        $notifications = get_user_meta($current_user_id, 'env_notifications', true);
        
        if (!is_array($notifications)) {
            return;
        }
        
        $unread_notifications = array_filter($notifications, function($notification) {
            return !$notification['read'];
        });
        
        if (empty($unread_notifications)) {
            return;
        }
        
        foreach (array_slice($unread_notifications, 0, 3) as $index => $notification) {
            $notice_class = 'notice notice-info';
            if ($notification['type'] === 'system_alert') {
                $notice_class = 'notice notice-error';
            } elseif ($notification['type'] === 'achievement_unlock') {
                $notice_class = 'notice notice-success';
            }
            
            ?>
            <div class="<?php echo esc_attr($notice_class); ?> is-dismissible env-admin-notification" data-notification-index="<?php echo $index; ?>">
                <h4><?php echo esc_html($notification['title']); ?></h4>
                <p><?php echo esc_html($notification['message']); ?></p>
                <button type="button" class="notice-dismiss" onclick="envNotifications.markNotificationRead(<?php echo $index; ?>)">
                    <span class="screen-reader-text"><?php _e('Dismiss this notice.', 'env-admin-dashboard'); ?></span>
                </button>
            </div>
            <?php
        }
    }
    
    /**
     * AJAX handler for sending notifications
     */
    public function ajax_send_notification() {
        check_ajax_referer('env_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Insufficient permissions')));
        }
        
        $type = sanitize_text_field($_POST['notification_type']);
        $title = sanitize_text_field($_POST['notification_title']);
        $message = sanitize_textarea_field($_POST['notification_message']);
        $recipients = array_map('sanitize_text_field', $_POST['recipients'] ?? array());
        $schedule_date = sanitize_text_field($_POST['schedule_date'] ?? '');
        
        $result = $this->send_notification($type, $title, $message, $recipients, $schedule_date);
        
        wp_die(json_encode($result));
    }
    
    /**
     * AJAX handler for managing notifications
     */
    public function ajax_manage_notification() {
        check_ajax_referer('env_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Insufficient permissions')));
        }
        
        $action = sanitize_text_field($_POST['notification_action']);
        $notification_id = intval($_POST['notification_id']);
        
        $result = $this->handle_notification_action($action, $notification_id);
        
        wp_die(json_encode($result));
    }
}
