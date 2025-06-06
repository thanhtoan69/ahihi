<?php
/**
 * Platform Overview Dashboard Widget
 *
 * @package Environmental_Admin_Dashboard
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Platform Overview Widget Class
 */
class Environmental_Platform_Overview_Widget {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        add_action('wp_ajax_refresh_platform_overview', array($this, 'refresh_widget_data'));
    }
    
    /**
     * Add dashboard widget
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'environmental_platform_overview',
            __('Environmental Platform Overview', 'environmental-admin-dashboard'),
            array($this, 'render_widget'),
            array($this, 'configure_widget')
        );
    }
    
    /**
     * Render widget content
     */
    public function render_widget() {
        $stats = $this->get_platform_statistics();
        ?>
        <div class="environmental-overview-widget">
            <div class="overview-stats-grid">
                <div class="stat-item users">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-groups"></span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_users']); ?></h3>
                        <p><?php _e('Total Users', 'environmental-admin-dashboard'); ?></p>
                        <span class="stat-change <?php echo $stats['users_change'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo ($stats['users_change'] >= 0 ? '+' : '') . $stats['users_change']; ?>%
                        </span>
                    </div>
                </div>
                
                <div class="stat-item activities">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-chart-line"></span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_activities']); ?></h3>
                        <p><?php _e('Activities Completed', 'environmental-admin-dashboard'); ?></p>
                        <span class="stat-change <?php echo $stats['activities_change'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo ($stats['activities_change'] >= 0 ? '+' : '') . $stats['activities_change']; ?>%
                        </span>
                    </div>
                </div>
                
                <div class="stat-item goals">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-flag"></span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['active_goals']); ?></h3>
                        <p><?php _e('Active Goals', 'environmental-admin-dashboard'); ?></p>
                        <span class="stat-change <?php echo $stats['goals_change'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo ($stats['goals_change'] >= 0 ? '+' : '') . $stats['goals_change']; ?>%
                        </span>
                    </div>
                </div>
                
                <div class="stat-item impact">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-heart"></span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_impact']); ?></h3>
                        <p><?php _e('Environmental Impact Score', 'environmental-admin-dashboard'); ?></p>
                        <span class="stat-change <?php echo $stats['impact_change'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo ($stats['impact_change'] >= 0 ? '+' : '') . $stats['impact_change']; ?>%
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="overview-chart-container">
                <canvas id="platform-overview-chart" width="400" height="200"></canvas>
            </div>
            
            <div class="widget-actions">
                <button type="button" class="button button-secondary" id="refresh-overview">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Refresh Data', 'environmental-admin-dashboard'); ?>
                </button>
                <a href="<?php echo admin_url('admin.php?page=environmental-dashboard'); ?>" class="button button-primary">
                    <?php _e('View Full Dashboard', 'environmental-admin-dashboard'); ?>
                </a>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Initialize chart
            if (typeof Chart !== 'undefined') {
                var ctx = document.getElementById('platform-overview-chart').getContext('2d');
                var chartData = <?php echo json_encode($this->get_chart_data()); ?>;
                
                new Chart(ctx, {
                    type: 'line',
                    data: chartData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
            
            // Refresh functionality
            $('#refresh-overview').on('click', function() {
                var button = $(this);
                button.prop('disabled', true);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'refresh_platform_overview',
                        nonce: '<?php echo wp_create_nonce('refresh_platform_overview'); ?>'
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
                'show_chart' => isset($_POST['show_chart']) ? 1 : 0,
                'chart_period' => sanitize_text_field($_POST['chart_period']),
                'refresh_interval' => intval($_POST['refresh_interval'])
            );
            update_option('environmental_overview_widget_options', $options);
        }
        
        $options = get_option('environmental_overview_widget_options', array(
            'show_chart' => 1,
            'chart_period' => '7days',
            'refresh_interval' => 300
        ));
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Show Chart', 'environmental-admin-dashboard'); ?></th>
                <td>
                    <input type="checkbox" name="show_chart" value="1" <?php checked($options['show_chart'], 1); ?> />
                    <label><?php _e('Display activity chart', 'environmental-admin-dashboard'); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Chart Period', 'environmental-admin-dashboard'); ?></th>
                <td>
                    <select name="chart_period">
                        <option value="7days" <?php selected($options['chart_period'], '7days'); ?>><?php _e('Last 7 Days', 'environmental-admin-dashboard'); ?></option>
                        <option value="30days" <?php selected($options['chart_period'], '30days'); ?>><?php _e('Last 30 Days', 'environmental-admin-dashboard'); ?></option>
                        <option value="90days" <?php selected($options['chart_period'], '90days'); ?>><?php _e('Last 90 Days', 'environmental-admin-dashboard'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Auto Refresh', 'environmental-admin-dashboard'); ?></th>
                <td>
                    <select name="refresh_interval">
                        <option value="0" <?php selected($options['refresh_interval'], 0); ?>><?php _e('Disabled', 'environmental-admin-dashboard'); ?></option>
                        <option value="300" <?php selected($options['refresh_interval'], 300); ?>><?php _e('Every 5 minutes', 'environmental-admin-dashboard'); ?></option>
                        <option value="900" <?php selected($options['refresh_interval'], 900); ?>><?php _e('Every 15 minutes', 'environmental-admin-dashboard'); ?></option>
                        <option value="3600" <?php selected($options['refresh_interval'], 3600); ?>><?php _e('Every hour', 'environmental-admin-dashboard'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Get platform statistics
     */
    private function get_platform_statistics() {
        global $wpdb;
        
        // Get current stats
        $total_users = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
        $total_activities = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}environmental_activities WHERE status = 'completed'");
        $active_goals = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}environmental_goals WHERE status = 'active'");
        
        // Calculate total impact score
        $total_impact = $wpdb->get_var("
            SELECT SUM(impact_score) 
            FROM {$wpdb->prefix}environmental_user_activities ua
            JOIN {$wpdb->prefix}environmental_activities a ON ua.activity_id = a.id
            WHERE ua.status = 'completed'
        ") ?: 0;
        
        // Get previous period stats for comparison
        $previous_users = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->users} 
            WHERE user_registered < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        $previous_activities = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}environmental_activities 
            WHERE status = 'completed' AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        // Calculate percentage changes
        $users_change = $previous_users > 0 ? round((($total_users - $previous_users) / $previous_users) * 100, 1) : 0;
        $activities_change = $previous_activities > 0 ? round((($total_activities - $previous_activities) / $previous_activities) * 100, 1) : 0;
        
        return array(
            'total_users' => $total_users,
            'total_activities' => $total_activities,
            'active_goals' => $active_goals,
            'total_impact' => $total_impact,
            'users_change' => $users_change,
            'activities_change' => $activities_change,
            'goals_change' => rand(-5, 15), // Placeholder
            'impact_change' => rand(0, 25) // Placeholder
        );
    }
    
    /**
     * Get chart data
     */
    private function get_chart_data() {
        global $wpdb;
        
        $options = get_option('environmental_overview_widget_options', array('chart_period' => '7days'));
        $days = $options['chart_period'] === '30days' ? 30 : ($options['chart_period'] === '90days' ? 90 : 7);
        
        $data = array();
        $labels = array();
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $labels[] = date('M j', strtotime($date));
            
            $activities = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM {$wpdb->prefix}environmental_user_activities 
                WHERE DATE(completed_at) = %s AND status = 'completed'
            ", $date)) ?: 0;
            
            $data[] = $activities;
        }
        
        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => __('Activities Completed', 'environmental-admin-dashboard'),
                    'data' => $data,
                    'borderColor' => '#2ecc71',
                    'backgroundColor' => 'rgba(46, 204, 113, 0.1)',
                    'tension' => 0.4
                )
            )
        );
    }
    
    /**
     * AJAX handler for refreshing widget data
     */
    public function refresh_widget_data() {
        check_ajax_referer('refresh_platform_overview', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // Clear any cached data
        delete_transient('environmental_platform_stats');
        
        wp_send_json_success(array('message' => __('Data refreshed successfully', 'environmental-admin-dashboard')));
    }
}

// Initialize the widget
new Environmental_Platform_Overview_Widget();
