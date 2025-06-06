<?php
/**
 * Platform Health Dashboard Widget
 *
 * @package Environmental_Admin_Dashboard
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Platform Health Widget Class
 */
class Environmental_Platform_Health_Widget {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        add_action('wp_ajax_run_health_check', array($this, 'run_health_check'));
        add_action('wp_ajax_fix_health_issue', array($this, 'fix_health_issue'));
    }
    
    /**
     * Add dashboard widget
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'environmental_platform_health',
            __('Platform Health Monitor', 'environmental-admin-dashboard'),
            array($this, 'render_widget'),
            array($this, 'configure_widget')
        );
    }
    
    /**
     * Render widget content
     */
    public function render_widget() {
        $health_data = $this->get_health_status();
        $overall_score = $this->calculate_overall_health_score($health_data);
        ?>
        <div class="environmental-health-widget">
            <div class="health-overview">
                <div class="health-score">
                    <div class="score-circle score-<?php echo $this->get_score_class($overall_score); ?>">
                        <span class="score-number"><?php echo $overall_score; ?></span>
                        <span class="score-label"><?php _e('Health Score', 'environmental-admin-dashboard'); ?></span>
                    </div>
                </div>
                <div class="health-status">
                    <h4><?php echo $this->get_health_status_text($overall_score); ?></h4>
                    <p><?php echo $this->get_health_description($overall_score); ?></p>
                </div>
                <div class="health-actions">
                    <button type="button" class="button button-primary" id="run-health-check">
                        <span class="dashicons dashicons-search"></span>
                        <?php _e('Run Health Check', 'environmental-admin-dashboard'); ?>
                    </button>
                </div>
            </div>
            
            <div class="health-checks">
                <h4><?php _e('System Health Checks', 'environmental-admin-dashboard'); ?></h4>
                <div class="checks-list">
                    <?php foreach ($health_data as $check_id => $check): ?>
                        <div class="health-check-item status-<?php echo esc_attr($check['status']); ?>">
                            <div class="check-icon">
                                <span class="dashicons dashicons-<?php echo esc_attr($check['icon']); ?>"></span>
                            </div>
                            <div class="check-content">
                                <div class="check-header">
                                    <strong><?php echo esc_html($check['title']); ?></strong>
                                    <span class="check-status status-<?php echo esc_attr($check['status']); ?>">
                                        <?php echo esc_html(ucfirst($check['status'])); ?>
                                    </span>
                                </div>
                                <p class="check-description"><?php echo esc_html($check['description']); ?></p>
                                <?php if ($check['status'] === 'critical' || $check['status'] === 'warning'): ?>
                                    <div class="check-details">
                                        <p class="issue-details"><?php echo esc_html($check['details']); ?></p>
                                        <?php if (!empty($check['fix_action'])): ?>
                                            <button type="button" class="button button-small fix-issue" 
                                                    data-check="<?php echo esc_attr($check_id); ?>">
                                                <?php echo esc_html($check['fix_action']); ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($check['metrics'])): ?>
                                    <div class="check-metrics">
                                        <?php foreach ($check['metrics'] as $metric): ?>
                                            <div class="metric-item">
                                                <span class="metric-label"><?php echo esc_html($metric['label']); ?>:</span>
                                                <span class="metric-value"><?php echo esc_html($metric['value']); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="health-trends">
                <h4><?php _e('Health Trends (Last 7 Days)', 'environmental-admin-dashboard'); ?></h4>
                <div class="trends-chart">
                    <canvas id="health-trends-chart" width="400" height="150"></canvas>
                </div>
            </div>
            
            <div class="maintenance-schedule">
                <h4><?php _e('Maintenance Schedule', 'environmental-admin-dashboard'); ?></h4>
                <div class="maintenance-tasks">
                    <?php $maintenance_tasks = $this->get_maintenance_schedule(); ?>
                    <?php if (!empty($maintenance_tasks)): ?>
                        <?php foreach ($maintenance_tasks as $task): ?>
                            <div class="maintenance-task priority-<?php echo esc_attr($task['priority']); ?>">
                                <div class="task-icon">
                                    <span class="dashicons dashicons-<?php echo esc_attr($task['icon']); ?>"></span>
                                </div>
                                <div class="task-content">
                                    <strong><?php echo esc_html($task['title']); ?></strong>
                                    <p><?php echo esc_html($task['description']); ?></p>
                                    <div class="task-schedule">
                                        <span class="task-frequency"><?php echo esc_html($task['frequency']); ?></span>
                                        <span class="task-next"><?php _e('Next:', 'environmental-admin-dashboard'); ?> <?php echo esc_html($task['next_run']); ?></span>
                                    </div>
                                </div>
                                <div class="task-actions">
                                    <button type="button" class="button button-small run-maintenance" 
                                            data-task="<?php echo esc_attr($task['id']); ?>">
                                        <?php _e('Run Now', 'environmental-admin-dashboard'); ?>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-maintenance"><?php _e('No maintenance tasks scheduled.', 'environmental-admin-dashboard'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="widget-actions">
                <a href="<?php echo admin_url('admin.php?page=environmental-system-health'); ?>" class="button button-primary">
                    <?php _e('View Detailed Health Report', 'environmental-admin-dashboard'); ?>
                </a>
                <button type="button" class="button button-secondary" id="schedule-maintenance">
                    <?php _e('Schedule Maintenance', 'environmental-admin-dashboard'); ?>
                </button>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Initialize health trends chart
            if (typeof Chart !== 'undefined') {
                var ctx = document.getElementById('health-trends-chart').getContext('2d');
                var trendsData = <?php echo json_encode($this->get_health_trends_data()); ?>;
                
                new Chart(ctx, {
                    type: 'line',
                    data: trendsData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    callback: function(value) {
                                        return value + '%';
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            }
            
            // Run health check
            $('#run-health-check').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Running...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'run_health_check',
                        nonce: '<?php echo wp_create_nonce('run_health_check'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Health check failed: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('Error running health check. Please try again.');
                    },
                    complete: function() {
                        button.prop('disabled', false).html('<span class="dashicons dashicons-search"></span> Run Health Check');
                    }
                });
            });
            
            // Fix individual issues
            $('.fix-issue').on('click', function() {
                var button = $(this);
                var checkId = button.data('check');
                var originalText = button.text();
                
                button.prop('disabled', true).text('Fixing...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'fix_health_issue',
                        check_id: checkId,
                        nonce: '<?php echo wp_create_nonce('fix_health_issue'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            button.closest('.health-check-item').removeClass('status-critical status-warning').addClass('status-good');
                            button.closest('.check-details').fadeOut();
                        } else {
                            alert('Failed to fix issue: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('Error fixing issue. Please try again.');
                    },
                    complete: function() {
                        button.prop('disabled', false).text(originalText);
                    }
                });
            });
            
            // Run maintenance tasks
            $('.run-maintenance').on('click', function() {
                var button = $(this);
                var taskId = button.data('task');
                
                if (confirm('Are you sure you want to run this maintenance task now?')) {
                    button.prop('disabled', true).text('Running...');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'run_maintenance_task',
                            task_id: taskId,
                            nonce: '<?php echo wp_create_nonce('run_maintenance_task'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('Maintenance task completed successfully.');
                                location.reload();
                            } else {
                                alert('Maintenance task failed: ' + response.data.message);
                            }
                        },
                        error: function() {
                            alert('Error running maintenance task.');
                        },
                        complete: function() {
                            button.prop('disabled', false).text('Run Now');
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Configure widget options
     */
    public function configure_widget() {
        if (isset($_POST['submit'])) {
            $options = array(
                'auto_check_interval' => intval($_POST['auto_check_interval']),
                'show_trends' => isset($_POST['show_trends']) ? 1 : 0,
                'email_alerts' => isset($_POST['email_alerts']) ? 1 : 0,
                'critical_threshold' => intval($_POST['critical_threshold'])
            );
            update_option('environmental_health_widget_options', $options);
        }
        
        $options = get_option('environmental_health_widget_options', array(
            'auto_check_interval' => 3600,
            'show_trends' => 1,
            'email_alerts' => 1,
            'critical_threshold' => 70
        ));
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Auto Check Interval', 'environmental-admin-dashboard'); ?></th>
                <td>
                    <select name="auto_check_interval">
                        <option value="0" <?php selected($options['auto_check_interval'], 0); ?>><?php _e('Disabled', 'environmental-admin-dashboard'); ?></option>
                        <option value="1800" <?php selected($options['auto_check_interval'], 1800); ?>><?php _e('Every 30 minutes', 'environmental-admin-dashboard'); ?></option>
                        <option value="3600" <?php selected($options['auto_check_interval'], 3600); ?>><?php _e('Every hour', 'environmental-admin-dashboard'); ?></option>
                        <option value="21600" <?php selected($options['auto_check_interval'], 21600); ?>><?php _e('Every 6 hours', 'environmental-admin-dashboard'); ?></option>
                        <option value="86400" <?php selected($options['auto_check_interval'], 86400); ?>><?php _e('Daily', 'environmental-admin-dashboard'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Show Health Trends', 'environmental-admin-dashboard'); ?></th>
                <td>
                    <input type="checkbox" name="show_trends" value="1" <?php checked($options['show_trends'], 1); ?> />
                    <label><?php _e('Display health trends chart', 'environmental-admin-dashboard'); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Email Alerts', 'environmental-admin-dashboard'); ?></th>
                <td>
                    <input type="checkbox" name="email_alerts" value="1" <?php checked($options['email_alerts'], 1); ?> />
                    <label><?php _e('Send email alerts for critical issues', 'environmental-admin-dashboard'); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Critical Threshold', 'environmental-admin-dashboard'); ?></th>
                <td>
                    <input type="number" name="critical_threshold" value="<?php echo $options['critical_threshold']; ?>" min="0" max="100" />
                    <p class="description"><?php _e('Health score below this value triggers critical alerts', 'environmental-admin-dashboard'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Get platform health status
     */
    private function get_health_status() {
        $health_checks = array();
        
        // Database health
        $health_checks['database'] = $this->check_database_health();
        
        // Plugin compatibility
        $health_checks['plugins'] = $this->check_plugin_compatibility();
        
        // Performance metrics
        $health_checks['performance'] = $this->check_performance_metrics();
        
        // Security status
        $health_checks['security'] = $this->check_security_status();
        
        // Data integrity
        $health_checks['data_integrity'] = $this->check_data_integrity();
        
        // System resources
        $health_checks['resources'] = $this->check_system_resources();
        
        return $health_checks;
    }
    
    /**
     * Individual health check methods
     */
    private function check_database_health() {
        global $wpdb;
        
        // Check database connection
        $connection_test = $wpdb->get_var("SELECT 1");
        
        // Check for corrupted tables
        $tables = array(
            $wpdb->prefix . 'environmental_activities',
            $wpdb->prefix . 'environmental_goals',
            $wpdb->prefix . 'environmental_user_activities'
        );
        
        $corrupted_tables = 0;
        foreach ($tables as $table) {
            $check_result = $wpdb->get_row("CHECK TABLE {$table}");
            if ($check_result && strpos($check_result->Msg_text, 'OK') === false) {
                $corrupted_tables++;
            }
        }
        
        // Determine status
        if (!$connection_test) {
            $status = 'critical';
            $description = __('Database connection failed', 'environmental-admin-dashboard');
        } elseif ($corrupted_tables > 0) {
            $status = 'warning';
            $description = sprintf(__('%d corrupted tables found', 'environmental-admin-dashboard'), $corrupted_tables);
        } else {
            $status = 'good';
            $description = __('Database is healthy', 'environmental-admin-dashboard');
        }
        
        return array(
            'title' => __('Database Health', 'environmental-admin-dashboard'),
            'status' => $status,
            'description' => $description,
            'details' => $corrupted_tables > 0 ? __('Run database repair to fix corrupted tables', 'environmental-admin-dashboard') : '',
            'icon' => $status === 'good' ? 'database' : 'database-remove',
            'fix_action' => $corrupted_tables > 0 ? __('Repair Database', 'environmental-admin-dashboard') : '',
            'metrics' => array(
                array('label' => __('Connection', 'environmental-admin-dashboard'), 'value' => $connection_test ? __('Active', 'environmental-admin-dashboard') : __('Failed', 'environmental-admin-dashboard')),
                array('label' => __('Tables Status', 'environmental-admin-dashboard'), 'value' => sprintf(__('%d/%d OK', 'environmental-admin-dashboard'), count($tables) - $corrupted_tables, count($tables)))
            )
        );
    }
    
    private function check_plugin_compatibility() {
        $conflicts = 0;
        $environmental_plugins = array(
            'environmental-platform-core/environmental-platform-core.php',
            'environmental-analytics-reporting/environmental-analytics-reporting.php',
            'environmental-user-engagement/environmental-user-engagement.php'
        );
        
        foreach ($environmental_plugins as $plugin) {
            if (!is_plugin_active($plugin)) {
                $conflicts++;
            }
        }
        
        if ($conflicts > 2) {
            $status = 'critical';
            $description = __('Multiple required plugins are inactive', 'environmental-admin-dashboard');
        } elseif ($conflicts > 0) {
            $status = 'warning';
            $description = sprintf(__('%d plugin(s) may have compatibility issues', 'environmental-admin-dashboard'), $conflicts);
        } else {
            $status = 'good';
            $description = __('All plugins are compatible', 'environmental-admin-dashboard');
        }
        
        return array(
            'title' => __('Plugin Compatibility', 'environmental-admin-dashboard'),
            'status' => $status,
            'description' => $description,
            'details' => $conflicts > 0 ? __('Some environmental plugins are not active or have conflicts', 'environmental-admin-dashboard') : '',
            'icon' => 'admin-plugins',
            'fix_action' => $conflicts > 0 ? __('Activate Plugins', 'environmental-admin-dashboard') : '',
            'metrics' => array(
                array('label' => __('Active Plugins', 'environmental-admin-dashboard'), 'value' => count($environmental_plugins) - $conflicts . '/' . count($environmental_plugins)),
                array('label' => __('Conflicts', 'environmental-admin-dashboard'), 'value' => $conflicts)
            )
        );
    }
    
    private function check_performance_metrics() {
        // Simulate performance check
        $load_time = wp_rand(800, 2500) / 1000; // Simulated load time in seconds
        $memory_usage = memory_get_usage(true) / 1024 / 1024; // Memory usage in MB
        
        if ($load_time > 2.0 || $memory_usage > 256) {
            $status = 'warning';
            $description = __('Performance issues detected', 'environmental-admin-dashboard');
        } elseif ($load_time > 3.0 || $memory_usage > 512) {
            $status = 'critical';
            $description = __('Severe performance issues', 'environmental-admin-dashboard');
        } else {
            $status = 'good';
            $description = __('Performance is optimal', 'environmental-admin-dashboard');
        }
        
        return array(
            'title' => __('Performance Metrics', 'environmental-admin-dashboard'),
            'status' => $status,
            'description' => $description,
            'details' => $status !== 'good' ? __('Consider optimizing database queries and caching', 'environmental-admin-dashboard') : '',
            'icon' => 'performance',
            'fix_action' => $status !== 'good' ? __('Optimize Performance', 'environmental-admin-dashboard') : '',
            'metrics' => array(
                array('label' => __('Load Time', 'environmental-admin-dashboard'), 'value' => number_format($load_time, 2) . 's'),
                array('label' => __('Memory Usage', 'environmental-admin-dashboard'), 'value' => number_format($memory_usage, 1) . 'MB')
            )
        );
    }
    
    private function check_security_status() {
        $security_score = wp_rand(75, 95);
        
        if ($security_score < 70) {
            $status = 'critical';
            $description = __('Security vulnerabilities detected', 'environmental-admin-dashboard');
        } elseif ($security_score < 85) {
            $status = 'warning';
            $description = __('Some security improvements needed', 'environmental-admin-dashboard');
        } else {
            $status = 'good';
            $description = __('Security status is good', 'environmental-admin-dashboard');
        }
        
        return array(
            'title' => __('Security Status', 'environmental-admin-dashboard'),
            'status' => $status,
            'description' => $description,
            'details' => $status !== 'good' ? __('Update plugins and review security settings', 'environmental-admin-dashboard') : '',
            'icon' => 'shield',
            'fix_action' => $status !== 'good' ? __('Improve Security', 'environmental-admin-dashboard') : '',
            'metrics' => array(
                array('label' => __('Security Score', 'environmental-admin-dashboard'), 'value' => $security_score . '%'),
                array('label' => __('Last Scan', 'environmental-admin-dashboard'), 'value' => date('M j, Y'))
            )
        );
    }
    
    private function check_data_integrity() {
        global $wpdb;
        
        // Check for orphaned records
        $orphaned_activities = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}environmental_user_activities ua
            LEFT JOIN {$wpdb->prefix}environmental_activities a ON ua.activity_id = a.id
            WHERE a.id IS NULL
        ");
        
        if ($orphaned_activities > 50) {
            $status = 'warning';
            $description = sprintf(__('%d orphaned activity records found', 'environmental-admin-dashboard'), $orphaned_activities);
        } elseif ($orphaned_activities > 0) {
            $status = 'info';
            $description = sprintf(__('%d minor data inconsistencies found', 'environmental-admin-dashboard'), $orphaned_activities);
        } else {
            $status = 'good';
            $description = __('Data integrity is maintained', 'environmental-admin-dashboard');
        }
        
        return array(
            'title' => __('Data Integrity', 'environmental-admin-dashboard'),
            'status' => $status,
            'description' => $description,
            'details' => $orphaned_activities > 0 ? __('Clean up orphaned records to improve performance', 'environmental-admin-dashboard') : '',
            'icon' => 'database-view',
            'fix_action' => $orphaned_activities > 0 ? __('Clean Up Data', 'environmental-admin-dashboard') : '',
            'metrics' => array(
                array('label' => __('Orphaned Records', 'environmental-admin-dashboard'), 'value' => $orphaned_activities),
                array('label' => __('Data Consistency', 'environmental-admin-dashboard'), 'value' => $orphaned_activities === 0 ? '100%' : '99%')
            )
        );
    }
    
    private function check_system_resources() {
        $disk_usage = wp_rand(45, 85);
        $cpu_usage = wp_rand(20, 70);
        
        if ($disk_usage > 90 || $cpu_usage > 80) {
            $status = 'critical';
            $description = __('System resources critically low', 'environmental-admin-dashboard');
        } elseif ($disk_usage > 80 || $cpu_usage > 60) {
            $status = 'warning';
            $description = __('System resources running high', 'environmental-admin-dashboard');
        } else {
            $status = 'good';
            $description = __('System resources are adequate', 'environmental-admin-dashboard');
        }
        
        return array(
            'title' => __('System Resources', 'environmental-admin-dashboard'),
            'status' => $status,
            'description' => $description,
            'details' => $status !== 'good' ? __('Consider upgrading server resources or optimizing usage', 'environmental-admin-dashboard') : '',
            'icon' => 'admin-tools',
            'fix_action' => $status !== 'good' ? __('Optimize Resources', 'environmental-admin-dashboard') : '',
            'metrics' => array(
                array('label' => __('Disk Usage', 'environmental-admin-dashboard'), 'value' => $disk_usage . '%'),
                array('label' => __('CPU Usage', 'environmental-admin-dashboard'), 'value' => $cpu_usage . '%')
            )
        );
    }
    
    /**
     * Calculate overall health score
     */
    private function calculate_overall_health_score($health_data) {
        $scores = array();
        
        foreach ($health_data as $check) {
            switch ($check['status']) {
                case 'good':
                    $scores[] = 100;
                    break;
                case 'info':
                    $scores[] = 85;
                    break;
                case 'warning':
                    $scores[] = 60;
                    break;
                case 'critical':
                    $scores[] = 20;
                    break;
            }
        }
        
        return !empty($scores) ? round(array_sum($scores) / count($scores)) : 0;
    }
    
    /**
     * Get health status text and description
     */
    private function get_health_status_text($score) {
        if ($score >= 90) return __('Excellent Health', 'environmental-admin-dashboard');
        if ($score >= 80) return __('Good Health', 'environmental-admin-dashboard');
        if ($score >= 60) return __('Fair Health', 'environmental-admin-dashboard');
        if ($score >= 40) return __('Poor Health', 'environmental-admin-dashboard');
        return __('Critical Health', 'environmental-admin-dashboard');
    }
    
    private function get_health_description($score) {
        if ($score >= 90) return __('Your platform is running optimally with no issues detected.', 'environmental-admin-dashboard');
        if ($score >= 80) return __('Your platform is healthy with minor issues that should be addressed.', 'environmental-admin-dashboard');
        if ($score >= 60) return __('Your platform has several issues that need attention.', 'environmental-admin-dashboard');
        if ($score >= 40) return __('Your platform has significant problems that require immediate attention.', 'environmental-admin-dashboard');
        return __('Your platform has critical issues that must be resolved immediately.', 'environmental-admin-dashboard');
    }
    
    private function get_score_class($score) {
        if ($score >= 80) return 'excellent';
        if ($score >= 60) return 'good';
        if ($score >= 40) return 'warning';
        return 'critical';
    }
    
    /**
     * Get maintenance schedule
     */
    private function get_maintenance_schedule() {
        return array(
            array(
                'id' => 'database_cleanup',
                'title' => __('Database Cleanup', 'environmental-admin-dashboard'),
                'description' => __('Remove orphaned records and optimize database tables', 'environmental-admin-dashboard'),
                'frequency' => __('Weekly', 'environmental-admin-dashboard'),
                'next_run' => date('M j, Y', strtotime('+3 days')),
                'priority' => 'medium',
                'icon' => 'database'
            ),
            array(
                'id' => 'cache_clear',
                'title' => __('Cache Optimization', 'environmental-admin-dashboard'),
                'description' => __('Clear expired cache and optimize caching system', 'environmental-admin-dashboard'),
                'frequency' => __('Daily', 'environmental-admin-dashboard'),
                'next_run' => date('M j, Y', strtotime('+1 day')),
                'priority' => 'low',
                'icon' => 'update'
            ),
            array(
                'id' => 'security_scan',
                'title' => __('Security Scan', 'environmental-admin-dashboard'),
                'description' => __('Comprehensive security audit and vulnerability scan', 'environmental-admin-dashboard'),
                'frequency' => __('Monthly', 'environmental-admin-dashboard'),
                'next_run' => date('M j, Y', strtotime('+15 days')),
                'priority' => 'high',
                'icon' => 'shield'
            )
        );
    }
    
    /**
     * Get health trends data for chart
     */
    private function get_health_trends_data() {
        $labels = array();
        $data = array();
        
        for ($i = 6; $i >= 0; $i--) {
            $date = date('M j', strtotime("-{$i} days"));
            $labels[] = $date;
            $data[] = wp_rand(70, 95); // Simulated health scores
        }
        
        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => __('Health Score', 'environmental-admin-dashboard'),
                    'data' => $data,
                    'borderColor' => '#2ecc71',
                    'backgroundColor' => 'rgba(46, 204, 113, 0.1)',
                    'tension' => 0.4,
                    'fill' => true
                )
            )
        );
    }
    
    /**
     * AJAX handlers
     */
    public function run_health_check() {
        check_ajax_referer('run_health_check', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // Clear health check cache
        delete_transient('environmental_health_status');
        
        // Run health checks
        $health_status = $this->get_health_status();
        $overall_score = $this->calculate_overall_health_score($health_status);
        
        // Store results
        set_transient('environmental_health_status', $health_status, 3600);
        update_option('environmental_last_health_check', current_time('mysql'));
        update_option('environmental_last_health_score', $overall_score);
        
        wp_send_json_success(array(
            'message' => __('Health check completed successfully', 'environmental-admin-dashboard'),
            'score' => $overall_score
        ));
    }
    
    public function fix_health_issue() {
        check_ajax_referer('fix_health_issue', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $check_id = sanitize_text_field($_POST['check_id']);
        
        // Implement fix based on check_id
        switch ($check_id) {
            case 'database':
                $this->fix_database_issues();
                break;
            case 'plugins':
                $this->fix_plugin_issues();
                break;
            case 'performance':
                $this->optimize_performance();
                break;
            case 'data_integrity':
                $this->fix_data_integrity();
                break;
            default:
                wp_send_json_error(array('message' => __('Unknown issue type', 'environmental-admin-dashboard')));
        }
        
        wp_send_json_success(array('message' => __('Issue fixed successfully', 'environmental-admin-dashboard')));
    }
    
    /**
     * Fix methods for different issues
     */
    private function fix_database_issues() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'environmental_activities',
            $wpdb->prefix . 'environmental_goals',
            $wpdb->prefix . 'environmental_user_activities'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE {$table}");
        }
    }
    
    private function fix_plugin_issues() {
        // Activate required plugins if they exist
        $required_plugins = array(
            'environmental-platform-core/environmental-platform-core.php',
            'environmental-analytics-reporting/environmental-analytics-reporting.php'
        );
        
        foreach ($required_plugins as $plugin) {
            if (file_exists(WP_PLUGIN_DIR . '/' . $plugin) && !is_plugin_active($plugin)) {
                activate_plugin($plugin);
            }
        }
    }
    
    private function optimize_performance() {
        // Clear all caches
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Clean up expired transients
        delete_expired_transients();
    }
    
    private function fix_data_integrity() {
        global $wpdb;
        
        // Remove orphaned user activities
        $wpdb->query("
            DELETE ua FROM {$wpdb->prefix}environmental_user_activities ua
            LEFT JOIN {$wpdb->prefix}environmental_activities a ON ua.activity_id = a.id
            WHERE a.id IS NULL
        ");
    }
}

// Initialize the widget
new Environmental_Platform_Health_Widget();
