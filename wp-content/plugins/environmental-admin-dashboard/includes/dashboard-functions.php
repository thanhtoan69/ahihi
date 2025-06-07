<?php
/**
 * Environmental Admin Dashboard - Helper Functions
 *
 * @package Environmental_Admin_Dashboard
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get dashboard statistics
 */
function environmental_get_dashboard_stats() {
    global $wpdb;
    
    $stats = array();
    
    // Total users
    $stats['total_users'] = get_user_count();
    $stats['total_users_change'] = environmental_calculate_user_growth();
    
    // Activities stats
    $activities_table = $wpdb->prefix . 'environmental_activities';
    if ($wpdb->get_var("SHOW TABLES LIKE '$activities_table'") == $activities_table) {
        $stats['total_activities'] = $wpdb->get_var("SELECT COUNT(*) FROM $activities_table");
        $stats['active_activities'] = $wpdb->get_var("SELECT COUNT(*) FROM $activities_table WHERE status = 'active'");
        $stats['completed_activities'] = $wpdb->get_var("SELECT COUNT(*) FROM $activities_table WHERE status = 'completed'");
        $stats['activities_change'] = environmental_calculate_activities_growth();
    } else {
        $stats['total_activities'] = 0;
        $stats['active_activities'] = 0;
        $stats['completed_activities'] = 0;
        $stats['activities_change'] = 0;
    }
    
    // Goals stats
    $goals_table = $wpdb->prefix . 'environmental_goals';
    if ($wpdb->get_var("SHOW TABLES LIKE '$goals_table'") == $goals_table) {
        $stats['total_goals'] = $wpdb->get_var("SELECT COUNT(*) FROM $goals_table");
        $stats['completed_goals'] = $wpdb->get_var("SELECT COUNT(*) FROM $goals_table WHERE status = 'completed'");
        $stats['goals_completion_rate'] = $stats['total_goals'] > 0 ? round(($stats['completed_goals'] / $stats['total_goals']) * 100, 1) : 0;
        $stats['goals_change'] = environmental_calculate_goals_growth();
    } else {
        $stats['total_goals'] = 0;
        $stats['completed_goals'] = 0;
        $stats['goals_completion_rate'] = 0;
        $stats['goals_change'] = 0;
    }
    
    // Environmental impact
    $stats['co2_saved'] = environmental_calculate_co2_saved();
    $stats['water_saved'] = environmental_calculate_water_saved();
    $stats['energy_saved'] = environmental_calculate_energy_saved();
    $stats['environmental_impact_change'] = environmental_calculate_impact_growth();
    
    return apply_filters('environmental_dashboard_stats', $stats);
}

/**
 * Calculate user growth rate
 */
function environmental_calculate_user_growth() {
    global $wpdb;
    
    $current_month = date('Y-m');
    $last_month = date('Y-m', strtotime('-1 month'));
    
    $current_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->users} WHERE DATE_FORMAT(user_registered, '%Y-%m') = %s",
        $current_month
    ));
    
    $last_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->users} WHERE DATE_FORMAT(user_registered, '%Y-%m') = %s",
        $last_month
    ));
    
    if ($last_count == 0) {
        return $current_count > 0 ? 100 : 0;
    }
    
    return round((($current_count - $last_count) / $last_count) * 100, 1);
}

/**
 * Calculate activities growth rate
 */
function environmental_calculate_activities_growth() {
    global $wpdb;
    
    $activities_table = $wpdb->prefix . 'environmental_activities';
    if ($wpdb->get_var("SHOW TABLES LIKE '$activities_table'") != $activities_table) {
        return 0;
    }
    
    $current_month = date('Y-m');
    $last_month = date('Y-m', strtotime('-1 month'));
    
    $current_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $activities_table WHERE DATE_FORMAT(created_at, '%Y-%m') = %s",
        $current_month
    ));
    
    $last_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $activities_table WHERE DATE_FORMAT(created_at, '%Y-%m') = %s",
        $last_month
    ));
    
    if ($last_count == 0) {
        return $current_count > 0 ? 100 : 0;
    }
    
    return round((($current_count - $last_count) / $last_count) * 100, 1);
}

/**
 * Calculate goals growth rate
 */
function environmental_calculate_goals_growth() {
    global $wpdb;
    
    $goals_table = $wpdb->prefix . 'environmental_goals';
    if ($wpdb->get_var("SHOW TABLES LIKE '$goals_table'") != $goals_table) {
        return 0;
    }
    
    $current_month = date('Y-m');
    $last_month = date('Y-m', strtotime('-1 month'));
    
    $current_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $goals_table WHERE DATE_FORMAT(created_at, '%Y-%m') = %s",
        $current_month
    ));
    
    $last_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $goals_table WHERE DATE_FORMAT(created_at, '%Y-%m') = %s",
        $last_month
    ));
    
    if ($last_count == 0) {
        return $current_count > 0 ? 100 : 0;
    }
    
    return round((($current_count - $last_count) / $last_count) * 100, 1);
}

/**
 * Calculate CO2 saved
 */
function environmental_calculate_co2_saved() {
    global $wpdb;
    
    $activities_table = $wpdb->prefix . 'environmental_activities';
    if ($wpdb->get_var("SHOW TABLES LIKE '$activities_table'") != $activities_table) {
        return 0;
    }
    
    $co2_saved = $wpdb->get_var(
        "SELECT SUM(COALESCE(co2_impact, 0)) FROM $activities_table WHERE status = 'completed'"
    );
    
    return round($co2_saved ?: 0, 2);
}

/**
 * Calculate water saved
 */
function environmental_calculate_water_saved() {
    global $wpdb;
    
    $activities_table = $wpdb->prefix . 'environmental_activities';
    if ($wpdb->get_var("SHOW TABLES LIKE '$activities_table'") != $activities_table) {
        return 0;
    }
    
    $water_saved = $wpdb->get_var(
        "SELECT SUM(COALESCE(water_impact, 0)) FROM $activities_table WHERE status = 'completed'"
    );
    
    return round($water_saved ?: 0, 2);
}

/**
 * Calculate energy saved
 */
function environmental_calculate_energy_saved() {
    global $wpdb;
    
    $activities_table = $wpdb->prefix . 'environmental_activities';
    if ($wpdb->get_var("SHOW TABLES LIKE '$activities_table'") != $activities_table) {
        return 0;
    }
    
    $energy_saved = $wpdb->get_var(
        "SELECT SUM(COALESCE(energy_impact, 0)) FROM $activities_table WHERE status = 'completed'"
    );
    
    return round($energy_saved ?: 0, 2);
}

/**
 * Calculate environmental impact growth
 */
function environmental_calculate_impact_growth() {
    global $wpdb;
    
    $activities_table = $wpdb->prefix . 'environmental_activities';
    if ($wpdb->get_var("SHOW TABLES LIKE '$activities_table'") != $activities_table) {
        return 0;
    }
    
    $current_month = date('Y-m');
    $last_month = date('Y-m', strtotime('-1 month'));
    
    $current_impact = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(COALESCE(co2_impact, 0) + COALESCE(water_impact, 0) + COALESCE(energy_impact, 0)) 
         FROM $activities_table 
         WHERE status = 'completed' AND DATE_FORMAT(completed_at, '%Y-%m') = %s",
        $current_month
    ));
    
    $last_impact = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(COALESCE(co2_impact, 0) + COALESCE(water_impact, 0) + COALESCE(energy_impact, 0)) 
         FROM $activities_table 
         WHERE status = 'completed' AND DATE_FORMAT(completed_at, '%Y-%m') = %s",
        $last_month
    ));
    
    if ($last_impact == 0) {
        return $current_impact > 0 ? 100 : 0;
    }
    
    return round((($current_impact - $last_impact) / $last_impact) * 100, 1);
}

/**
 * Get activities for dashboard
 */
function environmental_get_activities() {
    global $wpdb;
    
    $activities_table = $wpdb->prefix . 'environmental_activities';
    if ($wpdb->get_var("SHOW TABLES LIKE '$activities_table'") != $activities_table) {
        return array();
    }
    
    $activities = $wpdb->get_results(
        "SELECT id, title, description, status, participant_count, created_at 
         FROM $activities_table 
         ORDER BY created_at DESC 
         LIMIT 50"
    );
    
    return $activities ?: array();
}

/**
 * Get goals for dashboard
 */
function environmental_get_goals() {
    global $wpdb;
    
    $goals_table = $wpdb->prefix . 'environmental_goals';
    if ($wpdb->get_var("SHOW TABLES LIKE '$goals_table'") != $goals_table) {
        return array();
    }
    
    $goals = $wpdb->get_results(
        "SELECT id, title, description, progress, target_date, status, created_at 
         FROM $goals_table 
         ORDER BY created_at DESC 
         LIMIT 50"
    );
    
    return $goals ?: array();
}

/**
 * Get platform users
 */
function environmental_get_platform_users() {
    $users = get_users(array(
        'meta_key' => 'environmental_platform_user',
        'meta_value' => '1',
        'number' => 100
    ));
    
    // Add activity count for each user
    foreach ($users as $user) {
        $user->activity_count = environmental_get_user_activity_count($user->ID);
    }
    
    return $users;
}

/**
 * Get user activity count
 */
function environmental_get_user_activity_count($user_id) {
    global $wpdb;
    
    $user_activities_table = $wpdb->prefix . 'environmental_user_activities';
    if ($wpdb->get_var("SHOW TABLES LIKE '$user_activities_table'") != $user_activities_table) {
        return 0;
    }
    
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $user_activities_table WHERE user_id = %d",
        $user_id
    ));
    
    return intval($count);
}

/**
 * Get chart data for activities
 */
function environmental_get_activities_chart_data($period = '7d') {
    global $wpdb;
    
    $activities_table = $wpdb->prefix . 'environmental_activities';
    if ($wpdb->get_var("SHOW TABLES LIKE '$activities_table'") != $activities_table) {
        return array('labels' => array(), 'data' => array());
    }
    
    switch ($period) {
        case '7d':
            $date_format = '%Y-%m-%d';
            $date_condition = 'DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)';
            break;
        case '30d':
            $date_format = '%Y-%m-%d';
            $date_condition = 'DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)';
            break;
        case '3m':
            $date_format = '%Y-%m';
            $date_condition = 'DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)';
            break;
        case '1y':
            $date_format = '%Y-%m';
            $date_condition = 'DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)';
            break;
        default:
            $date_format = '%Y-%m-%d';
            $date_condition = 'DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)';
    }
    
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT DATE_FORMAT(created_at, %s) as date_label, 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
         FROM $activities_table 
         WHERE $date_condition
         GROUP BY DATE_FORMAT(created_at, %s)
         ORDER BY date_label",
        $date_format,
        $date_format
    ));
    
    $labels = array();
    $completed = array();
    $active = array();
    $pending = array();
    
    foreach ($results as $result) {
        $labels[] = $result->date_label;
        $completed[] = intval($result->completed);
        $active[] = intval($result->active);
        $pending[] = intval($result->pending);
    }
    
    return array(
        'labels' => $labels,
        'data' => array($completed, $active, $pending)
    );
}

/**
 * Get chart data for goals
 */
function environmental_get_goals_chart_data($period = '7d') {
    global $wpdb;
    
    $goals_table = $wpdb->prefix . 'environmental_goals';
    if ($wpdb->get_var("SHOW TABLES LIKE '$goals_table'") != $goals_table) {
        return array('labels' => array(), 'data' => array());
    }
    
    $results = $wpdb->get_results(
        "SELECT title, progress 
         FROM $goals_table 
         WHERE status = 'active' 
         ORDER BY progress DESC 
         LIMIT 10"
    );
    
    $labels = array();
    $data = array();
    
    foreach ($results as $result) {
        $labels[] = wp_trim_words($result->title, 3);
        $data[] = floatval($result->progress);
    }
    
    return array(
        'labels' => $labels,
        'data' => $data
    );
}

/**
 * Get performance chart data
 */
function environmental_get_performance_chart_data($period = '7d') {
    global $wpdb;
    
    $activities_table = $wpdb->prefix . 'environmental_activities';
    if ($wpdb->get_var("SHOW TABLES LIKE '$activities_table'") != $activities_table) {
        return array('labels' => array(), 'activity_scores' => array(), 'impact_scores' => array());
    }
    
    switch ($period) {
        case '7d':
            $date_format = '%Y-%m-%d';
            $date_condition = 'DATE(completed_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)';
            break;
        case '30d':
            $date_format = '%Y-%m-%d';
            $date_condition = 'DATE(completed_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)';
            break;
        case '3m':
            $date_format = '%Y-%m';
            $date_condition = 'DATE(completed_at) >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)';
            break;
        case '1y':
            $date_format = '%Y-%m';
            $date_condition = 'DATE(completed_at) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)';
            break;
        default:
            $date_format = '%Y-%m-%d';
            $date_condition = 'DATE(completed_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)';
    }
    
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT DATE_FORMAT(completed_at, %s) as date_label,
                COUNT(*) as activity_count,
                AVG(COALESCE(co2_impact, 0) + COALESCE(water_impact, 0) + COALESCE(energy_impact, 0)) as avg_impact
         FROM $activities_table 
         WHERE status = 'completed' AND $date_condition
         GROUP BY DATE_FORMAT(completed_at, %s)
         ORDER BY date_label",
        $date_format,
        $date_format
    ));
    
    $labels = array();
    $activity_scores = array();
    $impact_scores = array();
    
    foreach ($results as $result) {
        $labels[] = $result->date_label;
        $activity_scores[] = intval($result->activity_count);
        $impact_scores[] = round(floatval($result->avg_impact), 2);
    }
    
    return array(
        'labels' => $labels,
        'activity_scores' => $activity_scores,
        'impact_scores' => $impact_scores
    );
}

/**
 * Get platform health data
 */
function environmental_get_platform_health_data() {
    // Calculate health metrics
    $performance = environmental_calculate_platform_performance();
    $stability = environmental_calculate_platform_stability();
    $security = environmental_calculate_platform_security();
    $usage = environmental_calculate_platform_usage();
    $growth = environmental_calculate_platform_growth();
    
    return array(
        'data' => array($performance, $stability, $security, $usage, $growth)
    );
}

/**
 * Calculate platform performance score
 */
function environmental_calculate_platform_performance() {
    // Mock performance calculation - in real implementation,
    // this would check database query times, page load speeds, etc.
    return 85 + rand(-5, 10);
}

/**
 * Calculate platform stability score
 */
function environmental_calculate_platform_stability() {
    // Mock stability calculation - in real implementation,
    // this would check error rates, uptime, etc.
    return 90 + rand(-5, 8);
}

/**
 * Calculate platform security score
 */
function environmental_calculate_platform_security() {
    // Mock security calculation - in real implementation,
    // this would check security vulnerabilities, login attempts, etc.
    return 88 + rand(-3, 7);
}

/**
 * Calculate platform usage score
 */
function environmental_calculate_platform_usage() {
    // Calculate based on user activity
    $total_users = get_user_count();
    $active_users = environmental_get_active_users_count();
    
    if ($total_users == 0) {
        return 0;
    }
    
    return min(100, round(($active_users / $total_users) * 100));
}

/**
 * Calculate platform growth score
 */
function environmental_calculate_platform_growth() {
    $user_growth = environmental_calculate_user_growth();
    $activity_growth = environmental_calculate_activities_growth();
    
    // Average growth with weighting
    $growth_score = ($user_growth * 0.6) + ($activity_growth * 0.4);
    
    // Normalize to 0-100 scale
    return max(0, min(100, 50 + ($growth_score / 2)));
}

/**
 * Get active users count (users with activity in last 30 days)
 */
function environmental_get_active_users_count() {
    global $wpdb;
    
    $user_activities_table = $wpdb->prefix . 'environmental_user_activities';
    if ($wpdb->get_var("SHOW TABLES LIKE '$user_activities_table'") != $user_activities_table) {
        return 0;
    }
    
    $count = $wpdb->get_var(
        "SELECT COUNT(DISTINCT user_id) 
         FROM $user_activities_table 
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );
    
    return intval($count);
}

/**
 * Get recent alerts
 */
function environmental_get_recent_alerts() {
    $alerts = array();
    
    // Check for system alerts
    $system_alerts = environmental_check_system_alerts();
    $alerts = array_merge($alerts, $system_alerts);
    
    // Check for performance alerts
    $performance_alerts = environmental_check_performance_alerts();
    $alerts = array_merge($alerts, $performance_alerts);
    
    // Check for security alerts
    $security_alerts = environmental_check_security_alerts();
    $alerts = array_merge($alerts, $security_alerts);
    
    // Sort by priority and limit to recent ones
    usort($alerts, function($a, $b) {
        $priority_order = array('urgent' => 4, 'high' => 3, 'medium' => 2, 'low' => 1);
        return $priority_order[$b['priority']] - $priority_order[$a['priority']];
    });
    
    return array_slice($alerts, 0, 10);
}

/**
 * Check system alerts
 */
function environmental_check_system_alerts() {
    $alerts = array();
    
    // Check disk space
    if (function_exists('disk_free_space')) {
        $free_space = disk_free_space(ABSPATH);
        $total_space = disk_total_space(ABSPATH);
        
        if ($free_space && $total_space) {
            $usage_percent = (($total_space - $free_space) / $total_space) * 100;
            
            if ($usage_percent > 90) {
                $alerts[] = array(
                    'type' => 'error',
                    'title' => __('Low Disk Space', 'environmental-admin-dashboard'),
                    'message' => sprintf(__('Disk usage is at %d%%. Consider freeing up space.', 'environmental-admin-dashboard'), $usage_percent),
                    'priority' => 'high'
                );
            }
        }
    }
    
    // Check WordPress version
    global $wp_version;
    $latest_version = get_option('_site_transient_update_core');
    if ($latest_version && isset($latest_version->updates[0])) {
        $latest = $latest_version->updates[0]->current;
        if (version_compare($wp_version, $latest, '<')) {
            $alerts[] = array(
                'type' => 'warning',
                'title' => __('WordPress Update Available', 'environmental-admin-dashboard'),
                'message' => sprintf(__('WordPress %s is available. Current version: %s', 'environmental-admin-dashboard'), $latest, $wp_version),
                'priority' => 'medium'
            );
        }
    }
    
    return $alerts;
}

/**
 * Check performance alerts
 */
function environmental_check_performance_alerts() {
    $alerts = array();
    
    // Check query count
    if (defined('SAVEQUERIES') && SAVEQUERIES) {
        global $wpdb;
        $query_count = count($wpdb->queries);
        
        if ($query_count > 100) {
            $alerts[] = array(
                'type' => 'warning',
                'title' => __('High Query Count', 'environmental-admin-dashboard'),
                'message' => sprintf(__('%d database queries detected. Consider optimization.', 'environmental-admin-dashboard'), $query_count),
                'priority' => 'medium'
            );
        }
    }
    
    return $alerts;
}

/**
 * Check security alerts
 */
function environmental_check_security_alerts() {
    $alerts = array();
    
    // Check for failed login attempts
    $failed_logins = get_option('environmental_failed_logins', array());
    $recent_failures = 0;
    $one_hour_ago = time() - HOUR_IN_SECONDS;
    
    foreach ($failed_logins as $timestamp) {
        if ($timestamp > $one_hour_ago) {
            $recent_failures++;
        }
    }
    
    if ($recent_failures > 10) {
        $alerts[] = array(
            'type' => 'error',
            'title' => __('Multiple Failed Login Attempts', 'environmental-admin-dashboard'),
            'message' => sprintf(__('%d failed login attempts in the last hour.', 'environmental-admin-dashboard'), $recent_failures),
            'priority' => 'high'
        );
    }
    
    return $alerts;
}

/**
 * Process bulk operations
 */
function environmental_process_bulk_operation($action, $selected_items) {
    $results = array('success' => false, 'message' => '');
    
    if (empty($selected_items)) {
        $results['message'] = __('No items selected.', 'environmental-admin-dashboard');
        return $results;
    }
    
    switch ($action) {
        case 'activate_activities':
            $count = environmental_bulk_activate_activities($selected_items);
            $results['success'] = true;
            $results['message'] = sprintf(__('%d activities activated.', 'environmental-admin-dashboard'), $count);
            break;
            
        case 'deactivate_activities':
            $count = environmental_bulk_deactivate_activities($selected_items);
            $results['success'] = true;
            $results['message'] = sprintf(__('%d activities deactivated.', 'environmental-admin-dashboard'), $count);
            break;
            
        case 'delete_activities':
            $count = environmental_bulk_delete_activities($selected_items);
            $results['success'] = true;
            $results['message'] = sprintf(__('%d activities deleted.', 'environmental-admin-dashboard'), $count);
            break;
            
        case 'reset_progress':
            $count = environmental_bulk_reset_progress($selected_items);
            $results['success'] = true;
            $results['message'] = sprintf(__('Progress reset for %d items.', 'environmental-admin-dashboard'), $count);
            break;
            
        case 'export_activities':
            $file_url = environmental_export_activities($selected_items);
            $results['success'] = true;
            $results['message'] = __('Export completed.', 'environmental-admin-dashboard');
            $results['file_url'] = $file_url;
            break;
            
        default:
            $results['message'] = __('Unknown action.', 'environmental-admin-dashboard');
    }
    
    return $results;
}

/**
 * Bulk activate activities
 */
function environmental_bulk_activate_activities($activity_ids) {
    global $wpdb;
    
    $activities_table = $wpdb->prefix . 'environmental_activities';
    $count = 0;
    
    foreach ($activity_ids as $id) {
        $updated = $wpdb->update(
            $activities_table,
            array('status' => 'active'),
            array('id' => intval($id)),
            array('%s'),
            array('%d')
        );
        
        if ($updated) {
            $count++;
        }
    }
    
    return $count;
}

/**
 * Bulk deactivate activities
 */
function environmental_bulk_deactivate_activities($activity_ids) {
    global $wpdb;
    
    $activities_table = $wpdb->prefix . 'environmental_activities';
    $count = 0;
    
    foreach ($activity_ids as $id) {
        $updated = $wpdb->update(
            $activities_table,
            array('status' => 'inactive'),
            array('id' => intval($id)),
            array('%s'),
            array('%d')
        );
        
        if ($updated) {
            $count++;
        }
    }
    
    return $count;
}

/**
 * Bulk delete activities
 */
function environmental_bulk_delete_activities($activity_ids) {
    global $wpdb;
    
    $activities_table = $wpdb->prefix . 'environmental_activities';
    $count = 0;
    
    foreach ($activity_ids as $id) {
        $deleted = $wpdb->delete(
            $activities_table,
            array('id' => intval($id)),
            array('%d')
        );
        
        if ($deleted) {
            $count++;
        }
    }
    
    return $count;
}

/**
 * Bulk reset progress
 */
function environmental_bulk_reset_progress($item_ids) {
    global $wpdb;
    
    $user_activities_table = $wpdb->prefix . 'environmental_user_activities';
    $count = 0;
    
    foreach ($item_ids as $id) {
        $deleted = $wpdb->delete(
            $user_activities_table,
            array('activity_id' => intval($id)),
            array('%d')
        );
        
        if ($deleted !== false) {
            $count++;
        }
    }
    
    return $count;
}

/**
 * Export activities to CSV
 */
function environmental_export_activities($activity_ids) {
    global $wpdb;
    
    $activities_table = $wpdb->prefix . 'environmental_activities';
    $upload_dir = wp_upload_dir();
    $filename = 'environmental_activities_' . date('Y-m-d_H-i-s') . '.csv';
    $filepath = $upload_dir['path'] . '/' . $filename;
    
    $ids_placeholder = implode(',', array_fill(0, count($activity_ids), '%d'));
    $activities = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $activities_table WHERE id IN ($ids_placeholder)",
        ...$activity_ids
    ));
    
    $file = fopen($filepath, 'w');
    
    // Write header
    fputcsv($file, array(
        'ID', 'Title', 'Description', 'Status', 'Participants', 
        'CO2 Impact', 'Water Impact', 'Energy Impact', 'Created Date'
    ));
    
    // Write data
    foreach ($activities as $activity) {
        fputcsv($file, array(
            $activity->id,
            $activity->title,
            $activity->description,
            $activity->status,
            $activity->participant_count,
            $activity->co2_impact,
            $activity->water_impact,
            $activity->energy_impact,
            $activity->created_at
        ));
    }
    
    fclose($file);
    
    return $upload_dir['url'] . '/' . $filename;
}
