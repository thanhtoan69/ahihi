<?php
/**
 * Performance Analytics Dashboard Widget
 *
 * @package Environmental_Admin_Dashboard
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Performance Analytics Widget Class
 */
class Environmental_Performance_Analytics_Widget {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        add_action('wp_ajax_get_performance_data', array($this, 'get_performance_data'));
        add_action('wp_ajax_export_performance_report', array($this, 'export_performance_report'));
    }
    
    /**
     * Add dashboard widget
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'environmental_performance_analytics',
            __('Performance Analytics', 'environmental-admin-dashboard'),
            array($this, 'render_widget'),
            array($this, 'configure_widget')
        );
    }
    
    /**
     * Render widget content
     */
    public function render_widget() {
        $performance_data = $this->get_analytics_data();
        $options = get_option('environmental_performance_widget_options', array('time_period' => '30days'));
        ?>
        <div class="environmental-performance-widget">
            <div class="performance-header">
                <div class="period-selector">
                    <select id="performance-period">
                        <option value="7days" <?php selected($options['time_period'], '7days'); ?>><?php _e('Last 7 Days', 'environmental-admin-dashboard'); ?></option>
                        <option value="30days" <?php selected($options['time_period'], '30days'); ?>><?php _e('Last 30 Days', 'environmental-admin-dashboard'); ?></option>
                        <option value="90days" <?php selected($options['time_period'], '90days'); ?>><?php _e('Last 90 Days', 'environmental-admin-dashboard'); ?></option>
                        <option value="1year" <?php selected($options['time_period'], '1year'); ?>><?php _e('Last Year', 'environmental-admin-dashboard'); ?></option>
                    </select>
                </div>
                <div class="performance-actions">
                    <button type="button" class="button button-small" id="refresh-analytics">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Refresh', 'environmental-admin-dashboard'); ?>
                    </button>
                    <button type="button" class="button button-small" id="export-report">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Export', 'environmental-admin-dashboard'); ?>
                    </button>
                </div>
            </div>
            
            <div class="performance-metrics">
                <div class="metrics-grid">
                    <div class="metric-card engagement">
                        <div class="metric-icon">
                            <span class="dashicons dashicons-chart-bar"></span>
                        </div>
                        <div class="metric-content">
                            <h4><?php echo number_format($performance_data['engagement_rate'], 1); ?>%</h4>
                            <p><?php _e('User Engagement Rate', 'environmental-admin-dashboard'); ?></p>
                            <span class="metric-trend <?php echo $performance_data['engagement_trend'] >= 0 ? 'positive' : 'negative'; ?>">
                                <span class="dashicons dashicons-arrow-<?php echo $performance_data['engagement_trend'] >= 0 ? 'up' : 'down'; ?>-alt"></span>
                                <?php echo abs($performance_data['engagement_trend']); ?>%
                            </span>
                        </div>
                    </div>
                    
                    <div class="metric-card completion">
                        <div class="metric-icon">
                            <span class="dashicons dashicons-yes-alt"></span>
                        </div>
                        <div class="metric-content">
                            <h4><?php echo number_format($performance_data['completion_rate'], 1); ?>%</h4>
                            <p><?php _e('Activity Completion Rate', 'environmental-admin-dashboard'); ?></p>
                            <span class="metric-trend <?php echo $performance_data['completion_trend'] >= 0 ? 'positive' : 'negative'; ?>">
                                <span class="dashicons dashicons-arrow-<?php echo $performance_data['completion_trend'] >= 0 ? 'up' : 'down'; ?>-alt"></span>
                                <?php echo abs($performance_data['completion_trend']); ?>%
                            </span>
                        </div>
                    </div>
                    
                    <div class="metric-card impact">
                        <div class="metric-icon">
                            <span class="dashicons dashicons-heart"></span>
                        </div>
                        <div class="metric-content">
                            <h4><?php echo number_format($performance_data['total_impact']); ?></h4>
                            <p><?php _e('Total Impact Score', 'environmental-admin-dashboard'); ?></p>
                            <span class="metric-trend <?php echo $performance_data['impact_trend'] >= 0 ? 'positive' : 'negative'; ?>">
                                <span class="dashicons dashicons-arrow-<?php echo $performance_data['impact_trend'] >= 0 ? 'up' : 'down'; ?>-alt"></span>
                                <?php echo abs($performance_data['impact_trend']); ?>%
                            </span>
                        </div>
                    </div>
                    
                    <div class="metric-card retention">
                        <div class="metric-icon">
                            <span class="dashicons dashicons-groups"></span>
                        </div>
                        <div class="metric-content">
                            <h4><?php echo number_format($performance_data['retention_rate'], 1); ?>%</h4>
                            <p><?php _e('User Retention Rate', 'environmental-admin-dashboard'); ?></p>
                            <span class="metric-trend <?php echo $performance_data['retention_trend'] >= 0 ? 'positive' : 'negative'; ?>">
                                <span class="dashicons dashicons-arrow-<?php echo $performance_data['retention_trend'] >= 0 ? 'up' : 'down'; ?>-alt"></span>
                                <?php echo abs($performance_data['retention_trend']); ?>%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="performance-charts">
                <div class="charts-tabs">
                    <button type="button" class="chart-tab active" data-chart="engagement"><?php _e('Engagement', 'environmental-admin-dashboard'); ?></button>
                    <button type="button" class="chart-tab" data-chart="activities"><?php _e('Activities', 'environmental-admin-dashboard'); ?></button>
                    <button type="button" class="chart-tab" data-chart="impact"><?php _e('Impact', 'environmental-admin-dashboard'); ?></button>
                    <button type="button" class="chart-tab" data-chart="categories"><?php _e('Categories', 'environmental-admin-dashboard'); ?></button>
                </div>
                
                <div class="chart-container">
                    <canvas id="performance-main-chart" width="400" height="250"></canvas>
                </div>
            </div>
            
            <div class="performance-insights">
                <h4><?php _e('Key Insights', 'environmental-admin-dashboard'); ?></h4>
                <div class="insights-list">
                    <?php foreach ($performance_data['insights'] as $insight): ?>
                        <div class="insight-item <?php echo esc_attr($insight['type']); ?>">
                            <span class="insight-icon dashicons dashicons-<?php echo esc_attr($insight['icon']); ?>"></span>
                            <div class="insight-content">
                                <strong><?php echo esc_html($insight['title']); ?></strong>
                                <p><?php echo esc_html($insight['description']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="performance-recommendations">
                <h4><?php _e('Recommendations', 'environmental-admin-dashboard'); ?></h4>
                <div class="recommendations-list">
                    <?php foreach ($performance_data['recommendations'] as $recommendation): ?>
                        <div class="recommendation-item">
                            <div class="recommendation-priority priority-<?php echo esc_attr($recommendation['priority']); ?>">
                                <?php echo strtoupper($recommendation['priority']); ?>
                            </div>
                            <div class="recommendation-content">
                                <strong><?php echo esc_html($recommendation['title']); ?></strong>
                                <p><?php echo esc_html($recommendation['description']); ?></p>
                                <?php if (!empty($recommendation['action_url'])): ?>
                                    <a href="<?php echo esc_url($recommendation['action_url']); ?>" class="button button-small">
                                        <?php echo esc_html($recommendation['action_text']); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="widget-actions">
                <a href="<?php echo admin_url('admin.php?page=environmental-reporting'); ?>" class="button button-primary">
                    <?php _e('View Full Analytics', 'environmental-admin-dashboard'); ?>
                </a>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var currentChart = null;
            var chartData = <?php echo json_encode($this->get_chart_datasets()); ?>;
            
            // Initialize default chart
            updateChart('engagement');
            
            // Chart tab switching
            $('.chart-tab').on('click', function() {
                $('.chart-tab').removeClass('active');
                $(this).addClass('active');
                
                var chartType = $(this).data('chart');
                updateChart(chartType);
            });
            
            // Update chart function
            function updateChart(type) {
                if (currentChart) {
                    currentChart.destroy();
                }
                
                if (typeof Chart !== 'undefined' && chartData[type]) {
                    var ctx = document.getElementById('performance-main-chart').getContext('2d');
                    
                    var config = {
                        type: chartData[type].type,
                        data: chartData[type].data,
                        options: chartData[type].options
                    };
                    
                    currentChart = new Chart(ctx, config);
                }
            }
            
            // Period selector
            $('#performance-period').on('change', function() {
                var period = $(this).val();
                $('.environmental-performance-widget').addClass('loading');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_performance_data',
                        period: period,
                        nonce: '<?php echo wp_create_nonce('get_performance_data'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        }
                    },
                    complete: function() {
                        $('.environmental-performance-widget').removeClass('loading');
                    }
                });
            });
            
            // Refresh analytics
            $('#refresh-analytics').on('click', function() {
                var button = $(this);
                button.prop('disabled', true);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_performance_data',
                        refresh: true,
                        nonce: '<?php echo wp_create_nonce('get_performance_data'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        }
                    },
                    complete: function() {
                        button.prop('disabled', false);
                    }
                });
            });
            
            // Export report
            $('#export-report').on('click', function() {
                window.open(ajaxurl + '?action=export_performance_report&nonce=' + 
                    '<?php echo wp_create_nonce('export_performance_report'); ?>', '_blank');
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
                'time_period' => sanitize_text_field($_POST['time_period']),
                'show_insights' => isset($_POST['show_insights']) ? 1 : 0,
                'show_recommendations' => isset($_POST['show_recommendations']) ? 1 : 0,
                'chart_type' => sanitize_text_field($_POST['chart_type'])
            );
            update_option('environmental_performance_widget_options', $options);
        }
        
        $options = get_option('environmental_performance_widget_options', array(
            'time_period' => '30days',
            'show_insights' => 1,
            'show_recommendations' => 1,
            'chart_type' => 'line'
        ));
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Default Time Period', 'environmental-admin-dashboard'); ?></th>
                <td>
                    <select name="time_period">
                        <option value="7days" <?php selected($options['time_period'], '7days'); ?>><?php _e('Last 7 Days', 'environmental-admin-dashboard'); ?></option>
                        <option value="30days" <?php selected($options['time_period'], '30days'); ?>><?php _e('Last 30 Days', 'environmental-admin-dashboard'); ?></option>
                        <option value="90days" <?php selected($options['time_period'], '90days'); ?>><?php _e('Last 90 Days', 'environmental-admin-dashboard'); ?></option>
                        <option value="1year" <?php selected($options['time_period'], '1year'); ?>><?php _e('Last Year', 'environmental-admin-dashboard'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Show Insights', 'environmental-admin-dashboard'); ?></th>
                <td>
                    <input type="checkbox" name="show_insights" value="1" <?php checked($options['show_insights'], 1); ?> />
                    <label><?php _e('Display performance insights', 'environmental-admin-dashboard'); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Show Recommendations', 'environmental-admin-dashboard'); ?></th>
                <td>
                    <input type="checkbox" name="show_recommendations" value="1" <?php checked($options['show_recommendations'], 1); ?> />
                    <label><?php _e('Display improvement recommendations', 'environmental-admin-dashboard'); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Default Chart Type', 'environmental-admin-dashboard'); ?></th>
                <td>
                    <select name="chart_type">
                        <option value="line" <?php selected($options['chart_type'], 'line'); ?>><?php _e('Line Chart', 'environmental-admin-dashboard'); ?></option>
                        <option value="bar" <?php selected($options['chart_type'], 'bar'); ?>><?php _e('Bar Chart', 'environmental-admin-dashboard'); ?></option>
                        <option value="area" <?php selected($options['chart_type'], 'area'); ?>><?php _e('Area Chart', 'environmental-admin-dashboard'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Get analytics data
     */
    private function get_analytics_data() {
        global $wpdb;
        
        $options = get_option('environmental_performance_widget_options', array('time_period' => '30days'));
        $days = $this->get_days_from_period($options['time_period']);
        
        // Calculate engagement rate
        $total_users = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
        $active_users = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}environmental_user_activities 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $days));
        
        $engagement_rate = $total_users > 0 ? ($active_users / $total_users * 100) : 0;
        
        // Calculate completion rate
        $total_participations = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}environmental_user_activities 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $days));
        
        $completed_participations = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}environmental_user_activities 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY) AND status = 'completed'
        ", $days));
        
        $completion_rate = $total_participations > 0 ? ($completed_participations / $total_participations * 100) : 0;
        
        // Calculate total impact
        $total_impact = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(ua.impact_score) FROM {$wpdb->prefix}environmental_user_activities ua
            WHERE ua.created_at >= DATE_SUB(NOW(), INTERVAL %d DAY) AND ua.status = 'completed'
        ", $days)) ?: 0;
        
        // Calculate retention rate
        $retention_rate = $this->calculate_retention_rate($days);
        
        // Get trends (compare with previous period)
        $previous_days = $days * 2;
        $previous_engagement = $this->get_previous_engagement_rate($previous_days, $days);
        $previous_completion = $this->get_previous_completion_rate($previous_days, $days);
        $previous_impact = $this->get_previous_impact($previous_days, $days);
        $previous_retention = $this->get_previous_retention_rate($previous_days, $days);
        
        return array(
            'engagement_rate' => $engagement_rate,
            'completion_rate' => $completion_rate,
            'total_impact' => $total_impact,
            'retention_rate' => $retention_rate,
            'engagement_trend' => $this->calculate_trend($engagement_rate, $previous_engagement),
            'completion_trend' => $this->calculate_trend($completion_rate, $previous_completion),
            'impact_trend' => $this->calculate_trend($total_impact, $previous_impact),
            'retention_trend' => $this->calculate_trend($retention_rate, $previous_retention),
            'insights' => $this->generate_insights($engagement_rate, $completion_rate, $total_impact, $retention_rate),
            'recommendations' => $this->generate_recommendations($engagement_rate, $completion_rate, $retention_rate)
        );
    }
    
    /**
     * Get chart datasets
     */
    private function get_chart_datasets() {
        global $wpdb;
        
        $options = get_option('environmental_performance_widget_options', array('time_period' => '30days'));
        $days = $this->get_days_from_period($options['time_period']);
        
        // Engagement chart
        $engagement_data = $this->get_engagement_chart_data($days);
        
        // Activities chart
        $activities_data = $this->get_activities_chart_data($days);
        
        // Impact chart
        $impact_data = $this->get_impact_chart_data($days);
        
        // Categories chart
        $categories_data = $this->get_categories_chart_data($days);
        
        return array(
            'engagement' => array(
                'type' => 'line',
                'data' => $engagement_data,
                'options' => array(
                    'responsive' => true,
                    'scales' => array(
                        'y' => array(
                            'beginAtZero' => true,
                            'max' => 100,
                            'ticks' => array(
                                'callback' => 'function(value) { return value + "%"; }'
                            )
                        )
                    )
                )
            ),
            'activities' => array(
                'type' => 'bar',
                'data' => $activities_data,
                'options' => array(
                    'responsive' => true,
                    'scales' => array(
                        'y' => array('beginAtZero' => true)
                    )
                )
            ),
            'impact' => array(
                'type' => 'line',
                'data' => $impact_data,
                'options' => array(
                    'responsive' => true,
                    'scales' => array(
                        'y' => array('beginAtZero' => true)
                    )
                )
            ),
            'categories' => array(
                'type' => 'doughnut',
                'data' => $categories_data,
                'options' => array(
                    'responsive' => true,
                    'plugins' => array(
                        'legend' => array(
                            'position' => 'bottom'
                        )
                    )
                )
            )
        );
    }
    
    /**
     * Generate insights based on performance data
     */
    private function generate_insights($engagement, $completion, $impact, $retention) {
        $insights = array();
        
        if ($engagement > 70) {
            $insights[] = array(
                'type' => 'positive',
                'icon' => 'yes',
                'title' => __('High User Engagement', 'environmental-admin-dashboard'),
                'description' => sprintf(__('Your platform has excellent user engagement at %s%%.', 'environmental-admin-dashboard'), number_format($engagement, 1))
            );
        } elseif ($engagement < 30) {
            $insights[] = array(
                'type' => 'warning',
                'icon' => 'warning',
                'title' => __('Low User Engagement', 'environmental-admin-dashboard'),
                'description' => sprintf(__('User engagement is at %s%%. Consider improving content quality.', 'environmental-admin-dashboard'), number_format($engagement, 1))
            );
        }
        
        if ($completion > 80) {
            $insights[] = array(
                'type' => 'positive',
                'icon' => 'yes-alt',
                'title' => __('Excellent Completion Rate', 'environmental-admin-dashboard'),
                'description' => sprintf(__('Activities have a high completion rate of %s%%.', 'environmental-admin-dashboard'), number_format($completion, 1))
            );
        }
        
        if ($retention < 50) {
            $insights[] = array(
                'type' => 'critical',
                'icon' => 'dismiss',
                'title' => __('User Retention Needs Attention', 'environmental-admin-dashboard'),
                'description' => sprintf(__('User retention is at %s%%. Focus on long-term engagement strategies.', 'environmental-admin-dashboard'), number_format($retention, 1))
            );
        }
        
        return $insights;
    }
    
    /**
     * Generate recommendations
     */
    private function generate_recommendations($engagement, $completion, $retention) {
        $recommendations = array();
        
        if ($engagement < 50) {
            $recommendations[] = array(
                'priority' => 'high',
                'title' => __('Improve User Engagement', 'environmental-admin-dashboard'),
                'description' => __('Consider adding gamification elements, rewards, or more interactive content.', 'environmental-admin-dashboard'),
                'action_text' => __('View Engagement Tools', 'environmental-admin-dashboard'),
                'action_url' => admin_url('admin.php?page=environmental-engagement')
            );
        }
        
        if ($completion < 60) {
            $recommendations[] = array(
                'priority' => 'medium',
                'title' => __('Optimize Activity Difficulty', 'environmental-admin-dashboard'),
                'description' => __('Activities might be too complex. Consider breaking them into smaller steps.', 'environmental-admin-dashboard'),
                'action_text' => __('Review Activities', 'environmental-admin-dashboard'),
                'action_url' => admin_url('edit.php?post_type=environmental_activity')
            );
        }
        
        if ($retention < 40) {
            $recommendations[] = array(
                'priority' => 'high',
                'title' => __('Implement Retention Strategy', 'environmental-admin-dashboard'),
                'description' => __('Set up email reminders, progress tracking, and community features.', 'environmental-admin-dashboard'),
                'action_text' => __('Configure Notifications', 'environmental-admin-dashboard'),
                'action_url' => admin_url('admin.php?page=environmental-notifications')
            );
        }
        
        return $recommendations;
    }
    
    /**
     * Helper methods for calculations
     */
    private function get_days_from_period($period) {
        switch ($period) {
            case '7days': return 7;
            case '30days': return 30;
            case '90days': return 90;
            case '1year': return 365;
            default: return 30;
        }
    }
    
    private function calculate_trend($current, $previous) {
        if ($previous == 0) return 0;
        return round((($current - $previous) / $previous) * 100, 1);
    }
    
    private function calculate_retention_rate($days) {
        global $wpdb;
        
        $returning_users = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}environmental_user_activities 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            AND user_id IN (
                SELECT DISTINCT user_id FROM {$wpdb->prefix}environmental_user_activities 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)
            )
        ", $days, $days));
        
        $total_previous_users = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}environmental_user_activities 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $days));
        
        return $total_previous_users > 0 ? ($returning_users / $total_previous_users * 100) : 0;
    }
    
    // Additional chart data methods would go here...
    private function get_engagement_chart_data($days) {
        // Implementation for engagement chart data
        return array(
            'labels' => array(),
            'datasets' => array()
        );
    }
    
    private function get_activities_chart_data($days) {
        // Implementation for activities chart data
        return array(
            'labels' => array(),
            'datasets' => array()
        );
    }
    
    private function get_impact_chart_data($days) {
        // Implementation for impact chart data
        return array(
            'labels' => array(),
            'datasets' => array()
        );
    }
    
    private function get_categories_chart_data($days) {
        // Implementation for categories chart data
        return array(
            'labels' => array(),
            'datasets' => array()
        );
    }
    
    // Previous period calculation methods
    private function get_previous_engagement_rate($total_days, $current_days) {
        // Implementation for previous engagement rate
        return 0;
    }
    
    private function get_previous_completion_rate($total_days, $current_days) {
        // Implementation for previous completion rate
        return 0;
    }
    
    private function get_previous_impact($total_days, $current_days) {
        // Implementation for previous impact
        return 0;
    }
    
    private function get_previous_retention_rate($total_days, $current_days) {
        // Implementation for previous retention rate
        return 0;
    }
    
    /**
     * AJAX handlers
     */
    public function get_performance_data() {
        check_ajax_referer('get_performance_data', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // Update options if period changed
        if (isset($_POST['period'])) {
            $options = get_option('environmental_performance_widget_options', array());
            $options['time_period'] = sanitize_text_field($_POST['period']);
            update_option('environmental_performance_widget_options', $options);
        }
        
        // Clear cache if refresh requested
        if (isset($_POST['refresh'])) {
            delete_transient('environmental_performance_cache');
        }
        
        wp_send_json_success(array('message' => __('Performance data updated', 'environmental-admin-dashboard')));
    }
    
    public function export_performance_report() {
        check_ajax_referer('export_performance_report', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // Generate CSV report
        $performance_data = $this->get_analytics_data();
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="environmental-performance-report-' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, array('Metric', 'Value', 'Trend'));
        
        // CSV data
        fputcsv($output, array('Engagement Rate', $performance_data['engagement_rate'] . '%', $performance_data['engagement_trend'] . '%'));
        fputcsv($output, array('Completion Rate', $performance_data['completion_rate'] . '%', $performance_data['completion_trend'] . '%'));
        fputcsv($output, array('Total Impact', $performance_data['total_impact'], $performance_data['impact_trend'] . '%'));
        fputcsv($output, array('Retention Rate', $performance_data['retention_rate'] . '%', $performance_data['retention_trend'] . '%'));
        
        fclose($output);
        exit;
    }
}

// Initialize the widget
new Environmental_Performance_Analytics_Widget();
