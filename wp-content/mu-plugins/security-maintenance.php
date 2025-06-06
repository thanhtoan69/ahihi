<?php
/**
 * Environmental Platform Security Maintenance
 * Must-Use Plugin for automated security checks and maintenance tasks
 * 
 * This plugin provides automated daily security maintenance including:
 * - Suspicious file detection
 * - Plugin update monitoring
 * - WordPress core update checking
 * - Security scan scheduling
 * - Automated security reporting
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main security maintenance function
 */
function ep_security_maintenance() {
    // Log maintenance start
    ep_log_security_event('maintenance_start', 'Daily security maintenance started', 'info');
    
    // Check for suspicious files in uploads directory
    $suspicious_files = [];
    $upload_dir = wp_upload_dir();
    
    // Check for PHP files in uploads (should not exist)
    $php_files = glob($upload_dir['basedir'] . '/**/*.php', GLOB_BRACE);
    if ($php_files) {
        foreach ($php_files as $file) {
            $suspicious_files[] = str_replace($upload_dir['basedir'], '', $file);
        }
    }
    
    // Check for other suspicious file types
    $suspicious_extensions = ['phtml', 'php3', 'php4', 'php5', 'exe', 'bat', 'com', 'scr'];
    foreach ($suspicious_extensions as $ext) {
        $files = glob($upload_dir['basedir'] . '/**/*.' . $ext, GLOB_BRACE);
        if ($files) {
            foreach ($files as $file) {
                $suspicious_files[] = str_replace($upload_dir['basedir'], '', $file);
            }
        }
    }
    
    if (!empty($suspicious_files)) {
        ep_log_security_event(
            'suspicious_files', 
            'Found suspicious files in uploads: ' . implode(', ', $suspicious_files), 
            'warning'
        );
    }
    
    // Check for outdated plugins
    if (!function_exists('get_plugin_updates')) {
        require_once ABSPATH . 'wp-admin/includes/update.php';
    }
    
    $plugin_updates = get_plugin_updates();
    if (!empty($plugin_updates)) {
        $update_count = count($plugin_updates);
        $plugin_names = array_keys($plugin_updates);
        ep_log_security_event(
            'plugin_updates', 
            $update_count . ' plugin updates available: ' . implode(', ', $plugin_names), 
            'info'
        );
    }
    
    // Check WordPress core updates
    wp_version_check();
    $core_updates = get_core_updates();
    if (!empty($core_updates) && isset($core_updates[0]) && $core_updates[0]->response == 'upgrade') {
        ep_log_security_event(
            'core_update', 
            'WordPress core update available: ' . $core_updates[0]->version, 
            'warning'
        );
    }
    
    // Check for failed login attempts in the last 24 hours
    global $wpdb;
    $table_name = $wpdb->prefix . 'security_logs';
    $failed_logins = $wpdb->get_var(
        "SELECT COUNT(*) FROM $table_name 
         WHERE event_type = 'login_failed' 
         AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
    );
    
    if ($failed_logins > 10) {
        ep_log_security_event(
            'high_failed_logins', 
            $failed_logins . ' failed login attempts in the last 24 hours', 
            'warning'
        );
    }
    
    // Check for admin user accounts
    $admin_users = get_users(array('role' => 'administrator'));
    if (count($admin_users) > 3) {
        ep_log_security_event(
            'multiple_admins', 
            count($admin_users) . ' administrator accounts found', 
            'info'
        );
    }
    
    // Check for weak passwords (if user meta available)
    foreach ($admin_users as $user) {
        // Check if user hasn't changed password in 90 days
        $last_update = get_user_meta($user->ID, 'last_password_update', true);
        if ($last_update && (time() - $last_update) > (90 * 24 * 60 * 60)) {
            ep_log_security_event(
                'old_password', 
                'User ' . $user->user_login . ' hasn\'t updated password in 90+ days', 
                'info'
            );
        }
    }
    
    // Check file permissions
    $critical_files = [
        ABSPATH . 'wp-config.php',
        ABSPATH . '.htaccess'
    ];
    
    foreach ($critical_files as $file) {
        if (file_exists($file)) {
            $perms = substr(sprintf('%o', fileperms($file)), -4);
            if ($perms !== '0644' && $perms !== '0600') {
                ep_log_security_event(
                    'file_permissions', 
                    'File ' . basename($file) . ' has permissions ' . $perms . ' (should be 644 or 600)', 
                    'warning'
                );
            }
        }
    }
    
    // Check for debug mode in production
    if (defined('WP_DEBUG') && WP_DEBUG && !defined('WP_DEBUG_DISPLAY')) {
        ep_log_security_event(
            'debug_enabled', 
            'WP_DEBUG is enabled without WP_DEBUG_DISPLAY set to false', 
            'info'
        );
    }
    
    // Clean up old security logs (keep 30 days)
    $wpdb->query(
        "DELETE FROM $table_name 
         WHERE timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );
    
    // Log maintenance completion
    ep_log_security_event('maintenance_complete', 'Daily security maintenance completed', 'info');
    
    // Generate daily security report
    ep_generate_daily_security_report();
}

/**
 * Generate daily security report
 */
function ep_generate_daily_security_report() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'security_logs';
    
    // Get today's security statistics
    $stats = array(
        'total_events' => $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_name WHERE DATE(timestamp) = CURDATE()"
        ),
        'failed_logins' => $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_name 
             WHERE event_type = 'login_failed' AND DATE(timestamp) = CURDATE()"
        ),
        'successful_logins' => $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_name 
             WHERE event_type = 'login_success' AND DATE(timestamp) = CURDATE()"
        ),
        'warnings' => $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_name 
             WHERE severity IN ('warning', 'error', 'critical') AND DATE(timestamp) = CURDATE()"
        ),
        'unique_ips' => $wpdb->get_var(
            "SELECT COUNT(DISTINCT ip_address) FROM $table_name 
             WHERE DATE(timestamp) = CURDATE()"
        )
    );
    
    // Only send report if there are significant events
    if ($stats['total_events'] > 0 || $stats['warnings'] > 0) {
        $admin_email = get_option('admin_email');
        $site_name = get_option('blogname');
        
        $subject = "[{$site_name}] Daily Security Report - " . date('Y-m-d');
        
        $body = "Daily Security Report for " . date('Y-m-d') . "\n\n";
        $body .= "Security Statistics:\n";
        $body .= "- Total Events: " . number_format($stats['total_events']) . "\n";
        $body .= "- Failed Logins: " . number_format($stats['failed_logins']) . "\n";
        $body .= "- Successful Logins: " . number_format($stats['successful_logins']) . "\n";
        $body .= "- Warnings/Errors: " . number_format($stats['warnings']) . "\n";
        $body .= "- Unique IP Addresses: " . number_format($stats['unique_ips']) . "\n\n";
        
        if ($stats['warnings'] > 0) {
            $body .= "Recent Warnings/Errors:\n";
            $recent_warnings = $wpdb->get_results(
                "SELECT timestamp, event_type, message FROM $table_name 
                 WHERE severity IN ('warning', 'error', 'critical') 
                 AND DATE(timestamp) = CURDATE() 
                 ORDER BY timestamp DESC LIMIT 10"
            );
            
            foreach ($recent_warnings as $warning) {
                $body .= "- " . $warning->timestamp . ": " . $warning->event_type . " - " . $warning->message . "\n";
            }
            $body .= "\n";
        }
        
        $body .= "View detailed logs: " . admin_url('admin.php?page=security-logs') . "\n";
        $body .= "Site: " . home_url() . "\n";
        
        wp_mail($admin_email, $subject, $body);
    }
}

/**
 * Check for security plugin updates
 */
function ep_check_security_plugin_updates() {
    $security_plugins = [
        'wordfence/wordfence.php' => 'Wordfence Security',
        'updraftplus/updraftplus.php' => 'UpdraftPlus Backup',
        'two-factor/two-factor.php' => 'Two Factor Authentication',
        'limit-login-attempts-reloaded/limit-login-attempts-reloaded.php' => 'Limit Login Attempts'
    ];
    
    foreach ($security_plugins as $plugin_file => $plugin_name) {
        if (is_plugin_active($plugin_file)) {
            // Check if plugin needs update
            if (!function_exists('get_plugin_updates')) {
                require_once ABSPATH . 'wp-admin/includes/update.php';
            }
            
            $plugin_updates = get_plugin_updates();
            if (isset($plugin_updates[$plugin_file])) {
                ep_log_security_event(
                    'security_plugin_update',
                    $plugin_name . ' security plugin has an available update',
                    'warning'
                );
            }
        }
    }
}

/**
 * Monitor disk space
 */
function ep_check_disk_space() {
    $free_bytes = disk_free_space(ABSPATH);
    $total_bytes = disk_total_space(ABSPATH);
    
    if ($free_bytes && $total_bytes) {
        $free_percentage = ($free_bytes / $total_bytes) * 100;
        
        if ($free_percentage < 10) {
            ep_log_security_event(
                'low_disk_space',
                'Low disk space: ' . round($free_percentage, 2) . '% free',
                'warning'
            );
        }
    }
}

/**
 * Schedule security maintenance if not already scheduled
 */
function ep_schedule_security_maintenance() {
    if (!wp_next_scheduled('ep_security_maintenance')) {
        // Schedule daily at 3 AM
        wp_schedule_event(
            strtotime('tomorrow 3:00 AM'),
            'daily',
            'ep_security_maintenance'
        );
    }
    
    if (!wp_next_scheduled('ep_security_plugin_check')) {
        // Schedule plugin checks twice daily
        wp_schedule_event(
            time(),
            'twicedaily',
            'ep_security_plugin_check'
        );
    }
    
    if (!wp_next_scheduled('ep_disk_space_check')) {
        // Schedule disk space check daily
        wp_schedule_event(
            time(),
            'daily',
            'ep_disk_space_check'
        );
    }
}

// Hook the maintenance functions
add_action('ep_security_maintenance', 'ep_security_maintenance');
add_action('ep_security_plugin_check', 'ep_check_security_plugin_updates');
add_action('ep_disk_space_check', 'ep_check_disk_space');

// Schedule events on plugin load
add_action('plugins_loaded', 'ep_schedule_security_maintenance');

/**
 * Add security maintenance admin page
 */
add_action('admin_menu', function() {
    if (current_user_can('manage_options')) {
        add_management_page(
            'Security Maintenance',
            'Security Maintenance',
            'manage_options',
            'security-maintenance',
            'ep_security_maintenance_page'
        );
    }
});

function ep_security_maintenance_page() {
    echo '<div class="wrap">';
    echo '<h1>Security Maintenance</h1>';
    
    // Manual maintenance trigger
    if (isset($_POST['run_maintenance']) && wp_verify_nonce($_POST['maintenance_nonce'], 'run_maintenance')) {
        ep_security_maintenance();
        echo '<div class="notice notice-success"><p>Security maintenance completed successfully!</p></div>';
    }
    
    // Next scheduled times
    $next_maintenance = wp_next_scheduled('ep_security_maintenance');
    $next_plugin_check = wp_next_scheduled('ep_security_plugin_check');
    $next_disk_check = wp_next_scheduled('ep_disk_space_check');
    
    echo '<h2>Scheduled Maintenance</h2>';
    echo '<table class="form-table">';
    echo '<tr><th>Daily Security Maintenance</th><td>' . ($next_maintenance ? date('Y-m-d H:i:s', $next_maintenance) : 'Not scheduled') . '</td></tr>';
    echo '<tr><th>Plugin Security Check</th><td>' . ($next_plugin_check ? date('Y-m-d H:i:s', $next_plugin_check) : 'Not scheduled') . '</td></tr>';
    echo '<tr><th>Disk Space Check</th><td>' . ($next_disk_check ? date('Y-m-d H:i:s', $next_disk_check) : 'Not scheduled') . '</td></tr>';
    echo '</table>';
    
    echo '<h2>Manual Maintenance</h2>';
    echo '<form method="post">';
    wp_nonce_field('run_maintenance', 'maintenance_nonce');
    echo '<p>Click the button below to run security maintenance immediately:</p>';
    echo '<p><input type="submit" name="run_maintenance" class="button button-primary" value="Run Security Maintenance Now"></p>';
    echo '</form>';
    
    // Recent maintenance activities
    global $wpdb;
    $table_name = $wpdb->prefix . 'security_logs';
    $maintenance_logs = $wpdb->get_results(
        "SELECT timestamp, message FROM $table_name 
         WHERE event_type IN ('maintenance_start', 'maintenance_complete') 
         ORDER BY timestamp DESC LIMIT 10"
    );
    
    if ($maintenance_logs) {
        echo '<h2>Recent Maintenance Activity</h2>';
        echo '<table class="wp-list-table widefat">';
        echo '<thead><tr><th>Time</th><th>Activity</th></tr></thead>';
        echo '<tbody>';
        foreach ($maintenance_logs as $log) {
            echo '<tr>';
            echo '<td>' . esc_html($log->timestamp) . '</td>';
            echo '<td>' . esc_html($log->message) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
    
    echo '</div>';
}

/**
 * Add admin notice for security issues
 */
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'security_logs';
    
    // Check for recent critical events
    $critical_events = $wpdb->get_var(
        "SELECT COUNT(*) FROM $table_name 
         WHERE severity IN ('critical', 'error') 
         AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
    );
    
    if ($critical_events > 0) {
        echo '<div class="notice notice-error">';
        echo '<p><strong>Security Alert:</strong> ' . $critical_events . ' critical security events in the last 24 hours. ';
        echo '<a href="' . admin_url('admin.php?page=security-logs') . '">View Security Logs</a></p>';
        echo '</div>';
    }
    
    // Check for high failed login attempts
    $failed_logins = $wpdb->get_var(
        "SELECT COUNT(*) FROM $table_name 
         WHERE event_type = 'login_failed' 
         AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
    );
    
    if ($failed_logins > 20) {
        echo '<div class="notice notice-warning">';
        echo '<p><strong>Security Warning:</strong> ' . $failed_logins . ' failed login attempts in the last 24 hours. ';
        echo '<a href="' . admin_url('admin.php?page=security-logs') . '">View Details</a></p>';
        echo '</div>';
    }
});

/**
 * Track password updates
 */
add_action('password_reset', function($user, $new_pass) {
    update_user_meta($user->ID, 'last_password_update', time());
}, 10, 2);

add_action('profile_update', function($user_id, $old_user_data) {
    $user = get_userdata($user_id);
    if ($user->user_pass !== $old_user_data->user_pass) {
        update_user_meta($user_id, 'last_password_update', time());
    }
}, 10, 2);
?>
