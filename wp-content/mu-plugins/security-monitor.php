<?php
/**
 * Environmental Platform Security Monitor
 * Must-Use Plugin for real-time security monitoring and logging
 * 
 * This plugin provides comprehensive security monitoring including:
 * - Failed login attempt tracking
 * - Successful login monitoring
 * - Security event logging
 * - IP address and user agent tracking
 * - Email alerts for critical events
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Log security events to database
 */
function ep_log_security_event($event_type, $message, $severity = 'info') {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'security_logs';
    
    // Get client IP address
    $ip_address = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip_address = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip_address = $_SERVER['REMOTE_ADDR'];
    }
    
    // Get user agent
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    
    $wpdb->insert(
        $table_name,
        array(
            'timestamp' => current_time('mysql'),
            'event_type' => sanitize_text_field($event_type),
            'message' => sanitize_text_field($message),
            'severity' => sanitize_text_field($severity),
            'ip_address' => sanitize_text_field($ip_address),
            'user_agent' => sanitize_text_field($user_agent)
        ),
        array('%s', '%s', '%s', '%s', '%s', '%s')
    );
    
    // Send email alert for critical events
    if ($severity === 'critical' || $severity === 'error') {
        ep_send_security_alert($event_type, $message, $ip_address);
    }
}

/**
 * Send security alert email
 */
function ep_send_security_alert($event_type, $message, $ip_address) {
    $admin_email = get_option('admin_email');
    $site_name = get_option('blogname');
    
    $subject = "[{$site_name}] Security Alert: {$event_type}";
    $body = "Security Alert Details:\n\n";
    $body .= "Event Type: {$event_type}\n";
    $body .= "Message: {$message}\n";
    $body .= "IP Address: {$ip_address}\n";
    $body .= "Time: " . current_time('mysql') . "\n";
    $body .= "Site: " . home_url() . "\n\n";
    $body .= "Please review your security logs and take appropriate action if necessary.";
    
    wp_mail($admin_email, $subject, $body);
}

/**
 * Create security logs table if it doesn't exist
 */
function ep_create_security_logs_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'security_logs';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        timestamp datetime DEFAULT CURRENT_TIMESTAMP,
        event_type varchar(50) NOT NULL,
        message text NOT NULL,
        severity varchar(20) DEFAULT 'info',
        ip_address varchar(45),
        user_agent text,
        PRIMARY KEY (id),
        KEY event_type (event_type),
        KEY timestamp (timestamp),
        KEY severity (severity)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Create table on activation
add_action('plugins_loaded', 'ep_create_security_logs_table');

/**
 * Monitor failed login attempts
 */
add_action('wp_login_failed', function($username) {
    ep_log_security_event('login_failed', 'Failed login attempt for user: ' . $username, 'warning');
});

/**
 * Monitor successful logins
 */
add_action('wp_login', function($user_login, $user) {
    ep_log_security_event('login_success', 'Successful login for user: ' . $user_login, 'info');
}, 10, 2);

/**
 * Monitor admin user creation
 */
add_action('user_register', function($user_id) {
    $user = get_user_by('id', $user_id);
    if ($user && in_array('administrator', $user->roles)) {
        ep_log_security_event('admin_created', 'New administrator user created: ' . $user->user_login, 'warning');
    }
});

/**
 * Monitor plugin activation/deactivation
 */
add_action('activated_plugin', function($plugin) {
    ep_log_security_event('plugin_activated', 'Plugin activated: ' . $plugin, 'info');
});

add_action('deactivated_plugin', function($plugin) {
    ep_log_security_event('plugin_deactivated', 'Plugin deactivated: ' . $plugin, 'info');
});

/**
 * Monitor theme changes
 */
add_action('switch_theme', function($new_name, $new_theme) {
    ep_log_security_event('theme_changed', 'Theme changed to: ' . $new_name, 'info');
}, 10, 2);

/**
 * Monitor suspicious file uploads
 */
add_filter('wp_handle_upload_prefilter', function($file) {
    $suspicious_extensions = array('php', 'phtml', 'php3', 'php4', 'php5', 'exe', 'bat', 'com', 'scr');
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (in_array($file_extension, $suspicious_extensions)) {
        ep_log_security_event('suspicious_upload', 'Suspicious file upload blocked: ' . $file['name'], 'error');
        $file['error'] = 'This file type is not allowed for security reasons.';
    }
    
    return $file;
});

/**
 * Log security events for debugging (development only)
 */
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('init', function() {
        ep_log_security_event('debug_session', 'Debug session started', 'info');
    });
}

/**
 * Get security statistics for dashboard
 */
function ep_get_security_stats() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'security_logs';
    
    return array(
        'total_events' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name"),
        'failed_logins_today' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE event_type = 'login_failed' AND DATE(timestamp) = CURDATE()"),
        'critical_events_week' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE severity IN ('critical', 'error') AND timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)"),
        'unique_ips_today' => $wpdb->get_var("SELECT COUNT(DISTINCT ip_address) FROM $table_name WHERE DATE(timestamp) = CURDATE()")
    );
}

/**
 * Security dashboard widget
 */
add_action('wp_dashboard_setup', function() {
    if (current_user_can('manage_options')) {
        wp_add_dashboard_widget(
            'ep_security_dashboard',
            'Environmental Platform Security Status',
            'ep_security_dashboard_widget'
        );
    }
});

function ep_security_dashboard_widget() {
    $stats = ep_get_security_stats();
    
    echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">';
    echo '<div><strong>Total Events:</strong> ' . number_format($stats['total_events']) . '</div>';
    echo '<div><strong>Failed Logins Today:</strong> ' . number_format($stats['failed_logins_today']) . '</div>';
    echo '<div><strong>Critical Events (7 days):</strong> ' . number_format($stats['critical_events_week']) . '</div>';
    echo '<div><strong>Unique IPs Today:</strong> ' . number_format($stats['unique_ips_today']) . '</div>';
    echo '</div>';
    
    echo '<p style="margin-top: 15px;"><a href="' . admin_url('admin.php?page=security-logs') . '" class="button">View All Security Logs</a></p>';
}

/**
 * Add security logs admin page
 */
add_action('admin_menu', function() {
    if (current_user_can('manage_options')) {
        add_management_page(
            'Security Logs',
            'Security Logs',
            'manage_options',
            'security-logs',
            'ep_security_logs_page'
        );
    }
});

function ep_security_logs_page() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'security_logs';
    $per_page = 50;
    $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($page - 1) * $per_page;
    
    $total_logs = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $logs = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT %d OFFSET %d",
        $per_page,
        $offset
    ));
    
    echo '<div class="wrap">';
    echo '<h1>Security Logs</h1>';
    
    if ($logs) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>Timestamp</th>';
        echo '<th>Event Type</th>';
        echo '<th>Message</th>';
        echo '<th>Severity</th>';
        echo '<th>IP Address</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($logs as $log) {
            $severity_class = '';
            switch ($log->severity) {
                case 'critical':
                case 'error':
                    $severity_class = 'style="color: #dc3232; font-weight: bold;"';
                    break;
                case 'warning':
                    $severity_class = 'style="color: #ffb900; font-weight: bold;"';
                    break;
            }
            
            echo '<tr>';
            echo '<td>' . esc_html($log->timestamp) . '</td>';
            echo '<td>' . esc_html($log->event_type) . '</td>';
            echo '<td>' . esc_html($log->message) . '</td>';
            echo '<td ' . $severity_class . '>' . esc_html(strtoupper($log->severity)) . '</td>';
            echo '<td>' . esc_html($log->ip_address) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        
        // Pagination
        $total_pages = ceil($total_logs / $per_page);
        if ($total_pages > 1) {
            echo '<div class="tablenav bottom">';
            echo '<div class="tablenav-pages">';
            echo paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'total' => $total_pages,
                'current' => $page
            ));
            echo '</div>';
            echo '</div>';
        }
    } else {
        echo '<p>No security logs found.</p>';
    }
    
    echo '</div>';
}
?>
