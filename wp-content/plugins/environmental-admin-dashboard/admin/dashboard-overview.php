<?php
/**
 * Admin Dashboard Overview Page Template
 * 
 * Provides comprehensive overview of environmental platform
 * with all dashboard widgets integrated
 * 
 * @package Environmental_Admin_Dashboard
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Dashboard_Overview {
    
    /**
     * Initialize the dashboard overview
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_refresh_dashboard_data', array($this, 'ajax_refresh_dashboard_data'));
        add_action('wp_ajax_export_dashboard_data', array($this, 'ajax_export_dashboard_data'));
    }
    
    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Environmental Dashboard', 'environmental-admin'),
            __('Environmental Dashboard', 'environmental-admin'),
            'manage_options',
            'environmental-dashboard',
            array($this, 'render_dashboard_page'),
            'dashicons-chart-area',
            2
        );
        
        add_submenu_page(
            'environmental-dashboard',
            __('Dashboard Overview', 'environmental-admin'),
            __('Overview', 'environmental-admin'),
            'manage_options',
            'environmental-dashboard',
            array($this, 'render_dashboard_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'environmental-dashboard') === false) {
            return;
        }
        
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-draggable');
        
        wp_enqueue_script(
            'environmental-dashboard-admin',
            plugin_dir_url(__FILE__) . '../assets/js/dashboard-admin.js',
            array('jquery', 'chart-js'),
            '1.0.0',
            true
        );
        
        wp_enqueue_style(
            'environmental-dashboard-admin',
            plugin_dir_url(__FILE__) . '../assets/css/dashboard-admin.css',
            array(),
            '1.0.0'
        );
        
        wp_localize_script('environmental-dashboard-admin', 'envDashboard', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('environmental_dashboard_nonce'),
            'strings' => array(
                'refreshing' => __('Refreshing data...', 'environmental-admin'),
                'exporting' => __('Exporting data...', 'environmental-admin'),
                'error' => __('An error occurred. Please try again.', 'environmental-admin'),
                'success' => __('Operation completed successfully.', 'environmental-admin')
            )
        ));
    }
    
    /**
     * Render the main dashboard page
     */
    public function render_dashboard_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $dashboard_data = $this->get_dashboard_data();
        ?>
        <div class="wrap environmental-dashboard-wrap">
            <h1 class="wp-heading-inline">
                <?php _e('Environmental Platform Dashboard', 'environmental-admin'); ?>
            </h1>
            
            <div class="dashboard-actions">
                <button type="button" class="button button-primary" id="refresh-dashboard">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Refresh Data', 'environmental-admin'); ?>
                </button>
                
                <button type="button" class="button button-secondary" id="export-dashboard">
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Export Report', 'environmental-admin'); ?>
                </button>
                
                <button type="button" class="button button-secondary" id="customize-dashboard">
                    <span class="dashicons dashicons-admin-customizer"></span>
                    <?php _e('Customize Layout', 'environmental-admin'); ?>
                </button>
            </div>
            
            <div class="dashboard-stats-overview">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-groups"></span>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($dashboard_data['total_users']); ?></h3>
                            <p><?php _e('Total Users', 'environmental-admin'); ?></p>
                            <span class="stat-change positive">+<?php echo $dashboard_data['users_growth']; ?>%</span>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-chart-line"></span>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($dashboard_data['total_activities']); ?></h3>
                            <p><?php _e('Activities Completed', 'environmental-admin'); ?></p>
                            <span class="stat-change positive">+<?php echo $dashboard_data['activities_growth']; ?>%</span>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-awards"></span>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($dashboard_data['environmental_impact']); ?></h3>
                            <p><?php _e('CO2 Saved (kg)', 'environmental-admin'); ?></p>
                            <span class="stat-change positive">+<?php echo $dashboard_data['impact_growth']; ?>%</span>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-money-alt"></span>
                        </div>
                        <div class="stat-content">
                            <h3>$<?php echo number_format($dashboard_data['total_donations']); ?></h3>
                            <p><?php _e('Total Donations', 'environmental-admin'); ?></p>
                            <span class="stat-change positive">+<?php echo $dashboard_data['donations_growth']; ?>%</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Dashboard Widgets Container -->
            <div id="dashboard-widgets" class="metabox-holder columns-<?php echo get_current_screen()->get_columns(); ?>">
                <div class="postbox-container" id="postbox-container-1">
                    <div id="normal-sortables" class="meta-box-sortables ui-sortable">
                        
                        <!-- Platform Overview Widget -->
                        <div class="postbox" id="platform-overview-widget">
                            <div class="postbox-header">
                                <h2 class="hndle ui-sortable-handle">
                                    <span><?php _e('Platform Overview', 'environmental-admin'); ?></span>
                                </h2>
                                <div class="handle-actions">
                                    <button type="button" class="handlediv" aria-expanded="true">
                                        <span class="screen-reader-text"><?php _e('Toggle panel: Platform Overview'); ?></span>
                                        <span class="toggle-indicator" aria-hidden="true"></span>
                                    </button>
                                </div>
                            </div>
                            <div class="inside">
                                <?php
                                if (class_exists('Environmental_Platform_Overview_Widget')) {
                                    $widget = new Environmental_Platform_Overview_Widget();
                                    $widget->render_widget_content();
                                }
                                ?>
                            </div>
                        </div>
                        
                        <!-- Activities Progress Widget -->
                        <div class="postbox" id="activities-progress-widget">
                            <div class="postbox-header">
                                <h2 class="hndle ui-sortable-handle">
                                    <span><?php _e('Activities Progress', 'environmental-admin'); ?></span>
                                </h2>
                                <div class="handle-actions">
                                    <button type="button" class="handlediv" aria-expanded="true">
                                        <span class="screen-reader-text"><?php _e('Toggle panel: Activities Progress'); ?></span>
                                        <span class="toggle-indicator" aria-hidden="true"></span>
                                    </button>
                                </div>
                            </div>
                            <div class="inside">
                                <?php
                                if (class_exists('Environmental_Activities_Progress_Widget')) {
                                    $widget = new Environmental_Activities_Progress_Widget();
                                    $widget->render_widget_content();
                                }
                                ?>
                            </div>
                        </div>
                        
                        <!-- Environmental Goals Widget -->
                        <div class="postbox" id="environmental-goals-widget">
                            <div class="postbox-header">
                                <h2 class="hndle ui-sortable-handle">
                                    <span><?php _e('Environmental Goals', 'environmental-admin'); ?></span>
                                </h2>
                                <div class="handle-actions">
                                    <button type="button" class="handlediv" aria-expanded="true">
                                        <span class="screen-reader-text"><?php _e('Toggle panel: Environmental Goals'); ?></span>
                                        <span class="toggle-indicator" aria-hidden="true"></span>
                                    </button>
                                </div>
                            </div>
                            <div class="inside">
                                <?php
                                if (class_exists('Environmental_Goals_Widget')) {
                                    $widget = new Environmental_Goals_Widget();
                                    $widget->render_widget_content();
                                }
                                ?>
                            </div>
                        </div>
                        
                    </div>
                </div>
                
                <div class="postbox-container" id="postbox-container-2">
                    <div id="side-sortables" class="meta-box-sortables ui-sortable">
                        
                        <!-- Performance Analytics Widget -->
                        <div class="postbox" id="performance-analytics-widget">
                            <div class="postbox-header">
                                <h2 class="hndle ui-sortable-handle">
                                    <span><?php _e('Performance Analytics', 'environmental-admin'); ?></span>
                                </h2>
                                <div class="handle-actions">
                                    <button type="button" class="handlediv" aria-expanded="true">
                                        <span class="screen-reader-text"><?php _e('Toggle panel: Performance Analytics'); ?></span>
                                        <span class="toggle-indicator" aria-hidden="true"></span>
                                    </button>
                                </div>
                            </div>
                            <div class="inside">
                                <?php
                                if (class_exists('Environmental_Performance_Analytics_Widget')) {
                                    $widget = new Environmental_Performance_Analytics_Widget();
                                    $widget->render_widget_content();
                                }
                                ?>
                            </div>
                        </div>
                        
                        <!-- Platform Health Widget -->
                        <div class="postbox" id="platform-health-widget">
                            <div class="postbox-header">
                                <h2 class="hndle ui-sortable-handle">
                                    <span><?php _e('Platform Health', 'environmental-admin'); ?></span>
                                </h2>
                                <div class="handle-actions">
                                    <button type="button" class="handlediv" aria-expanded="true">
                                        <span class="screen-reader-text"><?php _e('Toggle panel: Platform Health'); ?></span>
                                        <span class="toggle-indicator" aria-hidden="true"></span>
                                    </button>
                                </div>
                            </div>
                            <div class="inside">
                                <?php
                                if (class_exists('Environmental_Platform_Health_Widget')) {
                                    $widget = new Environmental_Platform_Health_Widget();
                                    $widget->render_widget_content();
                                }
                                ?>
                            </div>
                        </div>
                        
                        <!-- Quick Actions Widget -->
                        <div class="postbox" id="quick-actions-widget">
                            <div class="postbox-header">
                                <h2 class="hndle ui-sortable-handle">
                                    <span><?php _e('Quick Actions', 'environmental-admin'); ?></span>
                                </h2>
                                <div class="handle-actions">
                                    <button type="button" class="handlediv" aria-expanded="true">
                                        <span class="screen-reader-text"><?php _e('Toggle panel: Quick Actions'); ?></span>
                                        <span class="toggle-indicator" aria-hidden="true"></span>
                                    </button>
                                </div>
                            </div>
                            <div class="inside">
                                <?php
                                if (class_exists('Environmental_Quick_Actions_Widget')) {
                                    $widget = new Environmental_Quick_Actions_Widget();
                                    $widget->render_widget_content();
                                }
                                ?>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
            
            <!-- Dashboard Customization Modal -->
            <div id="dashboard-customize-modal" class="environmental-modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2><?php _e('Customize Dashboard', 'environmental-admin'); ?></h2>
                        <span class="close">&times;</span>
                    </div>
                    <div class="modal-body">
                        <div class="customize-options">
                            <h3><?php _e('Widget Visibility', 'environmental-admin'); ?></h3>
                            <div class="widget-toggles">
                                <label>
                                    <input type="checkbox" name="widget_visibility[]" value="platform-overview" checked>
                                    <?php _e('Platform Overview', 'environmental-admin'); ?>
                                </label>
                                <label>
                                    <input type="checkbox" name="widget_visibility[]" value="activities-progress" checked>
                                    <?php _e('Activities Progress', 'environmental-admin'); ?>
                                </label>
                                <label>
                                    <input type="checkbox" name="widget_visibility[]" value="environmental-goals" checked>
                                    <?php _e('Environmental Goals', 'environmental-admin'); ?>
                                </label>
                                <label>
                                    <input type="checkbox" name="widget_visibility[]" value="performance-analytics" checked>
                                    <?php _e('Performance Analytics', 'environmental-admin'); ?>
                                </label>
                                <label>
                                    <input type="checkbox" name="widget_visibility[]" value="platform-health" checked>
                                    <?php _e('Platform Health', 'environmental-admin'); ?>
                                </label>
                                <label>
                                    <input type="checkbox" name="widget_visibility[]" value="quick-actions" checked>
                                    <?php _e('Quick Actions', 'environmental-admin'); ?>
                                </label>
                            </div>
                            
                            <h3><?php _e('Dashboard Layout', 'environmental-admin'); ?></h3>
                            <div class="layout-options">
                                <label>
                                    <input type="radio" name="dashboard_layout" value="2-columns" checked>
                                    <?php _e('2 Columns', 'environmental-admin'); ?>
                                </label>
                                <label>
                                    <input type="radio" name="dashboard_layout" value="3-columns">
                                    <?php _e('3 Columns', 'environmental-admin'); ?>
                                </label>
                                <label>
                                    <input type="radio" name="dashboard_layout" value="1-column">
                                    <?php _e('Single Column', 'environmental-admin'); ?>
                                </label>
                            </div>
                            
                            <h3><?php _e('Refresh Intervals', 'environmental-admin'); ?></h3>
                            <div class="refresh-options">
                                <label>
                                    <?php _e('Auto-refresh data every:', 'environmental-admin'); ?>
                                    <select name="refresh_interval">
                                        <option value="0"><?php _e('Never', 'environmental-admin'); ?></option>
                                        <option value="30"><?php _e('30 seconds', 'environmental-admin'); ?></option>
                                        <option value="60" selected><?php _e('1 minute', 'environmental-admin'); ?></option>
                                        <option value="300"><?php _e('5 minutes', 'environmental-admin'); ?></option>
                                        <option value="900"><?php _e('15 minutes', 'environmental-admin'); ?></option>
                                    </select>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="button button-primary" id="save-dashboard-settings">
                            <?php _e('Save Settings', 'environmental-admin'); ?>
                        </button>
                        <button type="button" class="button button-secondary" id="cancel-customize">
                            <?php _e('Cancel', 'environmental-admin'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get dashboard data
     */
    private function get_dashboard_data() {
        global $wpdb;
        
        // Cache key for dashboard data
        $cache_key = 'environmental_dashboard_data';
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        $data = array(
            'total_users' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}"),
            'total_activities' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}user_activities WHERE status = 'completed'"),
            'environmental_impact' => $wpdb->get_var("SELECT SUM(co2_saved) FROM {$wpdb->prefix}environmental_impact"),
            'total_donations' => $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}donations WHERE status = 'completed'"),
            'users_growth' => $this->calculate_growth_rate('users', 30),
            'activities_growth' => $this->calculate_growth_rate('activities', 30),
            'impact_growth' => $this->calculate_growth_rate('impact', 30),
            'donations_growth' => $this->calculate_growth_rate('donations', 30)
        );
        
        // Cache for 5 minutes
        set_transient($cache_key, $data, 300);
        
        return $data;
    }
    
    /**
     * Calculate growth rate for metrics
     */
    private function calculate_growth_rate($metric, $days = 30) {
        global $wpdb;
        
        $current_date = date('Y-m-d');
        $past_date = date('Y-m-d', strtotime("-{$days} days"));
        
        switch ($metric) {
            case 'users':
                $current = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) FROM {$wpdb->users} 
                    WHERE user_registered >= %s
                ", $past_date));
                $previous = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) FROM {$wpdb->users} 
                    WHERE user_registered >= %s AND user_registered < %s
                ", date('Y-m-d', strtotime("-" . ($days * 2) . " days")), $past_date));
                break;
                
            case 'activities':
                $current = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) FROM {$wpdb->prefix}user_activities 
                    WHERE completed_date >= %s AND status = 'completed'
                ", $past_date));
                $previous = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) FROM {$wpdb->prefix}user_activities 
                    WHERE completed_date >= %s AND completed_date < %s AND status = 'completed'
                ", date('Y-m-d', strtotime("-" . ($days * 2) . " days")), $past_date));
                break;
                
            default:
                return 0;
        }
        
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 1);
    }
    
    /**
     * AJAX handler for refreshing dashboard data
     */
    public function ajax_refresh_dashboard_data() {
        check_ajax_referer('environmental_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Insufficient permissions')));
        }
        
        // Clear cached data
        delete_transient('environmental_dashboard_data');
        
        // Get fresh data
        $data = $this->get_dashboard_data();
        
        wp_die(json_encode(array('success' => true, 'data' => $data)));
    }
    
    /**
     * AJAX handler for exporting dashboard data
     */
    public function ajax_export_dashboard_data() {
        check_ajax_referer('environmental_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Insufficient permissions')));
        }
        
        $data = $this->get_dashboard_data();
        $export_data = array(
            'generated_at' => current_time('mysql'),
            'platform_statistics' => $data,
            'detailed_metrics' => $this->get_detailed_metrics()
        );
        
        $filename = 'environmental-dashboard-export-' . date('Y-m-d-H-i-s') . '.json';
        
        wp_die(json_encode(array(
            'success' => true, 
            'data' => $export_data,
            'filename' => $filename
        )));
    }
    
    /**
     * Get detailed metrics for export
     */
    private function get_detailed_metrics() {
        global $wpdb;
        
        return array(
            'user_registrations_30_days' => $wpdb->get_results("
                SELECT DATE(user_registered) as date, COUNT(*) as count 
                FROM {$wpdb->users} 
                WHERE user_registered >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(user_registered)
                ORDER BY date DESC
            "),
            'activities_by_category' => $wpdb->get_results("
                SELECT category, COUNT(*) as count 
                FROM {$wpdb->prefix}user_activities 
                WHERE status = 'completed'
                GROUP BY category
            "),
            'top_performing_goals' => $wpdb->get_results("
                SELECT goal_name, completion_percentage, participant_count 
                FROM {$wpdb->prefix}environmental_goals 
                WHERE status = 'active'
                ORDER BY completion_percentage DESC
                LIMIT 10
            ")
        );
    }
}

// Initialize the dashboard overview
new Environmental_Dashboard_Overview();
?>
