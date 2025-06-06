<?php
/**
 * Admin Dashboard for Environmental Analytics
 * 
 * Provides the main administrative interface for analytics management,
 * reporting, and configuration.
 * 
 * @package Environmental_Analytics
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Admin_Dashboard {
    
    private $db_manager;
    private $tracking_manager;
    private $conversion_tracker;
    private $behavior_analytics;
    private $ga4_integration;
    
    /**
     * Constructor
     */
    public function __construct($db_manager, $tracking_manager, $conversion_tracker, $behavior_analytics, $ga4_integration) {
        $this->db_manager = $db_manager;
        $this->tracking_manager = $tracking_manager;
        $this->conversion_tracker = $conversion_tracker;
        $this->behavior_analytics = $behavior_analytics;
        $this->ga4_integration = $ga4_integration;
        
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'));
        
        // AJAX endpoints
        add_action('wp_ajax_env_analytics_get_overview', array($this, 'ajax_get_overview'));
        add_action('wp_ajax_env_analytics_get_charts_data', array($this, 'ajax_get_charts_data'));
        add_action('wp_ajax_env_analytics_export_data', array($this, 'ajax_export_data'));
        add_action('wp_ajax_env_analytics_save_settings', array($this, 'ajax_save_settings'));
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('Environmental Analytics', 'env-analytics'),
            __('Analytics', 'env-analytics'),
            'manage_options',
            'env-analytics',
            array($this, 'display_dashboard'),
            'dashicons-chart-area',
            30
        );
        
        // Submenu pages
        add_submenu_page(
            'env-analytics',
            __('Analytics Dashboard', 'env-analytics'),
            __('Dashboard', 'env-analytics'),
            'manage_options',
            'env-analytics',
            array($this, 'display_dashboard')
        );
        
        add_submenu_page(
            'env-analytics',
            __('Conversion Goals', 'env-analytics'),
            __('Conversions', 'env-analytics'),
            'manage_options',
            'env-analytics-conversions',
            array($this, 'display_conversions_page')
        );
        
        add_submenu_page(
            'env-analytics',
            __('User Behavior', 'env-analytics'),
            __('Behavior', 'env-analytics'),
            'manage_options',
            'env-analytics-behavior',
            array($this, 'display_behavior_page')
        );
        
        add_submenu_page(
            'env-analytics',
            __('Reports', 'env-analytics'),
            __('Reports', 'env-analytics'),
            'manage_options',
            'env-analytics-reports',
            array($this, 'display_reports_page')
        );
        
        add_submenu_page(
            'env-analytics',
            __('Settings', 'env-analytics'),
            __('Settings', 'env-analytics'),
            'manage_options',
            'env-analytics-settings',
            array($this, 'display_settings_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'env-analytics') === false) {
            return;
        }
        
        // Chart.js for analytics charts
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
            array(),
            '3.9.1',
            true
        );
        
        // Main admin script
        wp_enqueue_script(
            'env-analytics-admin',
            ENV_ANALYTICS_PLUGIN_URL . 'admin/js/admin-dashboard.js',
            array('jquery', 'chart-js'),
            ENV_ANALYTICS_VERSION,
            true
        );
        
        // Admin styles
        wp_enqueue_style(
            'env-analytics-admin',
            ENV_ANALYTICS_PLUGIN_URL . 'admin/css/admin-dashboard.css',
            array(),
            ENV_ANALYTICS_VERSION
        );
        
        // Localize script with data
        wp_localize_script('env-analytics-admin', 'envAnalyticsAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('env_analytics_nonce'),
            'strings' => array(
                'loading' => __('Loading...', 'env-analytics'),
                'error' => __('An error occurred', 'env-analytics'),
                'success' => __('Success', 'env-analytics'),
                'confirmDelete' => __('Are you sure you want to delete this item?', 'env-analytics')
            )
        ));
    }
    
    /**
     * Display main dashboard page
     */
    public function display_dashboard() {
        $overview_data = $this->get_overview_data();
        ?>
        <div class="wrap env-analytics-dashboard">
            <h1><?php _e('Environmental Analytics Dashboard', 'env-analytics'); ?></h1>
            
            <!-- Overview Cards -->
            <div class="env-analytics-overview-cards">
                <div class="overview-card">
                    <h3><?php _e('Total Events', 'env-analytics'); ?></h3>
                    <div class="metric-value"><?php echo number_format($overview_data['total_events']); ?></div>
                    <div class="metric-change <?php echo $overview_data['events_change'] >= 0 ? 'positive' : 'negative'; ?>">
                        <?php echo ($overview_data['events_change'] >= 0 ? '+' : '') . $overview_data['events_change']; ?>%
                    </div>
                </div>
                
                <div class="overview-card">
                    <h3><?php _e('Active Users', 'env-analytics'); ?></h3>
                    <div class="metric-value"><?php echo number_format($overview_data['active_users']); ?></div>
                    <div class="metric-change <?php echo $overview_data['users_change'] >= 0 ? 'positive' : 'negative'; ?>">
                        <?php echo ($overview_data['users_change'] >= 0 ? '+' : '') . $overview_data['users_change']; ?>%
                    </div>
                </div>
                
                <div class="overview-card">
                    <h3><?php _e('Conversions', 'env-analytics'); ?></h3>
                    <div class="metric-value"><?php echo number_format($overview_data['total_conversions']); ?></div>
                    <div class="metric-change <?php echo $overview_data['conversions_change'] >= 0 ? 'positive' : 'negative'; ?>">
                        <?php echo ($overview_data['conversions_change'] >= 0 ? '+' : '') . $overview_data['conversions_change']; ?>%
                    </div>
                </div>
                
                <div class="overview-card">
                    <h3><?php _e('Avg. Engagement', 'env-analytics'); ?></h3>
                    <div class="metric-value"><?php echo number_format($overview_data['avg_engagement'], 1); ?></div>
                    <div class="metric-change <?php echo $overview_data['engagement_change'] >= 0 ? 'positive' : 'negative'; ?>">
                        <?php echo ($overview_data['engagement_change'] >= 0 ? '+' : '') . $overview_data['engagement_change']; ?>%
                    </div>
                </div>
            </div>
            
            <!-- Charts Section -->
            <div class="env-analytics-charts-section">
                <div class="chart-container">
                    <h3><?php _e('Events Over Time', 'env-analytics'); ?></h3>
                    <canvas id="eventsChart" width="400" height="200"></canvas>
                </div>
                
                <div class="chart-container">
                    <h3><?php _e('User Engagement Distribution', 'env-analytics'); ?></h3>
                    <canvas id="engagementChart" width="400" height="200"></canvas>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="env-analytics-quick-actions">
                <h3><?php _e('Quick Actions', 'env-analytics'); ?></h3>
                <div class="action-buttons">
                    <a href="<?php echo admin_url('admin.php?page=env-analytics-conversions'); ?>" class="button button-primary">
                        <?php _e('Manage Conversion Goals', 'env-analytics'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=env-analytics-reports'); ?>" class="button">
                        <?php _e('Generate Report', 'env-analytics'); ?>
                    </a>
                    <button type="button" class="button" id="export-data-btn">
                        <?php _e('Export Data', 'env-analytics'); ?>
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=env-analytics-settings'); ?>" class="button">
                        <?php _e('Settings', 'env-analytics'); ?>
                    </a>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="env-analytics-recent-activity">
                <h3><?php _e('Recent Activity', 'env-analytics'); ?></h3>
                <div id="recent-activity-list">
                    <?php $this->display_recent_activity(); ?>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize dashboard
            envAnalyticsDashboard.init();
            
            // Load charts data
            envAnalyticsDashboard.loadChartsData();
            
            // Auto-refresh every 5 minutes
            setInterval(function() {
                envAnalyticsDashboard.refreshData();
            }, 300000);
        });
        </script>
        <?php
    }
    
    /**
     * Display conversions page
     */
    public function display_conversions_page() {
        ?>
        <div class="wrap env-analytics-conversions">
            <h1><?php _e('Conversion Goals Management', 'env-analytics'); ?></h1>
            
            <div class="env-analytics-page-header">
                <button type="button" class="button button-primary" id="add-goal-btn">
                    <?php _e('Add New Goal', 'env-analytics'); ?>
                </button>
            </div>
            
            <!-- Goals List -->
            <div class="env-analytics-goals-list">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Goal Name', 'env-analytics'); ?></th>
                            <th><?php _e('Type', 'env-analytics'); ?></th>
                            <th><?php _e('Conversions', 'env-analytics'); ?></th>
                            <th><?php _e('Value', 'env-analytics'); ?></th>
                            <th><?php _e('Conversion Rate', 'env-analytics'); ?></th>
                            <th><?php _e('Status', 'env-analytics'); ?></th>
                            <th><?php _e('Actions', 'env-analytics'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="goals-table-body">
                        <?php $this->display_goals_table(); ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Goal Creation Modal -->
            <div id="goal-modal" class="env-analytics-modal" style="display: none;">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2><?php _e('Create Conversion Goal', 'env-analytics'); ?></h2>
                    <form id="goal-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Goal Name', 'env-analytics'); ?></th>
                                <td><input type="text" name="name" required class="regular-text"></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Description', 'env-analytics'); ?></th>
                                <td><textarea name="description" rows="3" class="large-text"></textarea></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Goal Type', 'env-analytics'); ?></th>
                                <td>
                                    <select name="type">
                                        <option value="action"><?php _e('Action', 'env-analytics'); ?></option>
                                        <option value="page_view"><?php _e('Page View', 'env-analytics'); ?></option>
                                        <option value="time_spent"><?php _e('Time Spent', 'env-analytics'); ?></option>
                                        <option value="value"><?php _e('Value', 'env-analytics'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Target Action', 'env-analytics'); ?></th>
                                <td><input type="text" name="target_action" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Value per Conversion', 'env-analytics'); ?></th>
                                <td><input type="number" name="value_per_conversion" step="0.01" class="regular-text"></td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php _e('Create Goal', 'env-analytics'); ?></button>
                            <button type="button" class="button cancel-btn"><?php _e('Cancel', 'env-analytics'); ?></button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Display behavior analytics page
     */
    public function display_behavior_page() {
        ?>
        <div class="wrap env-analytics-behavior">
            <h1><?php _e('User Behavior Analytics', 'env-analytics'); ?></h1>
            
            <!-- Date Range Selector -->
            <div class="env-analytics-date-range">
                <label for="date-from"><?php _e('From:', 'env-analytics'); ?></label>
                <input type="date" id="date-from" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                
                <label for="date-to"><?php _e('To:', 'env-analytics'); ?></label>
                <input type="date" id="date-to" value="<?php echo date('Y-m-d'); ?>">
                
                <button type="button" class="button" id="refresh-behavior-data">
                    <?php _e('Refresh', 'env-analytics'); ?>
                </button>
            </div>
            
            <!-- Engagement Metrics -->
            <div class="env-analytics-engagement-metrics">
                <h3><?php _e('Engagement Overview', 'env-analytics'); ?></h3>
                <div id="engagement-metrics-container">
                    <!-- Loaded via AJAX -->
                </div>
            </div>
            
            <!-- User Segments -->
            <div class="env-analytics-user-segments">
                <h3><?php _e('User Segments', 'env-analytics'); ?></h3>
                <div id="user-segments-container">
                    <!-- Loaded via AJAX -->
                </div>
            </div>
            
            <!-- Content Performance -->
            <div class="env-analytics-content-performance">
                <h3><?php _e('Content Performance', 'env-analytics'); ?></h3>
                <div id="content-performance-container">
                    <!-- Loaded via AJAX -->
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Display reports page
     */
    public function display_reports_page() {
        ?>
        <div class="wrap env-analytics-reports">
            <h1><?php _e('Analytics Reports', 'env-analytics'); ?></h1>
            
            <!-- Report Generation -->
            <div class="env-analytics-report-generator">
                <h3><?php _e('Generate Custom Report', 'env-analytics'); ?></h3>
                <form id="report-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Report Type', 'env-analytics'); ?></th>
                            <td>
                                <select name="report_type" id="report-type">
                                    <option value="overview"><?php _e('Overview Report', 'env-analytics'); ?></option>
                                    <option value="conversions"><?php _e('Conversions Report', 'env-analytics'); ?></option>
                                    <option value="behavior"><?php _e('User Behavior Report', 'env-analytics'); ?></option>
                                    <option value="content"><?php _e('Content Performance Report', 'env-analytics'); ?></option>
                                    <option value="environmental"><?php _e('Environmental Impact Report', 'env-analytics'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Date Range', 'env-analytics'); ?></th>
                            <td>
                                <input type="date" name="date_from" id="report-date-from" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                                <span> to </span>
                                <input type="date" name="date_to" id="report-date-to" value="<?php echo date('Y-m-d'); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Format', 'env-analytics'); ?></th>
                            <td>
                                <label><input type="radio" name="format" value="html" checked> <?php _e('HTML', 'env-analytics'); ?></label>
                                <label><input type="radio" name="format" value="pdf"> <?php _e('PDF', 'env-analytics'); ?></label>
                                <label><input type="radio" name="format" value="csv"> <?php _e('CSV', 'env-analytics'); ?></label>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e('Generate Report', 'env-analytics'); ?></button>
                    </p>
                </form>
            </div>
            
            <!-- Report Display Area -->
            <div id="report-display-area" style="display: none;">
                <h3><?php _e('Generated Report', 'env-analytics'); ?></h3>
                <div id="report-content">
                    <!-- Report content will be loaded here -->
                </div>
            </div>
            
            <!-- Scheduled Reports -->
            <div class="env-analytics-scheduled-reports">
                <h3><?php _e('Scheduled Reports', 'env-analytics'); ?></h3>
                <div id="scheduled-reports-list">
                    <!-- Will be loaded via AJAX -->
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Display settings page
     */
    public function display_settings_page() {
        $ga4_config = $this->ga4_integration->get_configuration_status();
        ?>
        <div class="wrap env-analytics-settings">
            <h1><?php _e('Environmental Analytics Settings', 'env-analytics'); ?></h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('env_analytics_settings'); ?>
                
                <!-- Google Analytics 4 Settings -->
                <h3><?php _e('Google Analytics 4 Integration', 'env-analytics'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('GA4 Measurement ID', 'env-analytics'); ?></th>
                        <td>
                            <input type="text" name="env_analytics_ga4_measurement_id" 
                                   value="<?php echo esc_attr(get_option('env_analytics_ga4_measurement_id')); ?>" 
                                   class="regular-text" placeholder="G-XXXXXXXXXX">
                            <p class="description"><?php _e('Enter your GA4 Measurement ID (starts with G-)', 'env-analytics'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('GA4 API Secret', 'env-analytics'); ?></th>
                        <td>
                            <input type="password" name="env_analytics_ga4_api_secret" 
                                   value="<?php echo esc_attr(get_option('env_analytics_ga4_api_secret')); ?>" 
                                   class="regular-text">
                            <p class="description"><?php _e('API Secret for Measurement Protocol', 'env-analytics'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Debug Mode', 'env-analytics'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="env_analytics_ga4_debug" value="1" 
                                       <?php checked(get_option('env_analytics_ga4_debug'), 1); ?>>
                                <?php _e('Enable debug mode for GA4 tracking', 'env-analytics'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Exclude Admins', 'env-analytics'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="env_analytics_exclude_admins" value="1" 
                                       <?php checked(get_option('env_analytics_exclude_admins', 1), 1); ?>>
                                <?php _e('Exclude admin users from tracking', 'env-analytics'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <!-- GA4 Connection Status -->
                <div class="env-analytics-ga4-status">
                    <h4><?php _e('Connection Status', 'env-analytics'); ?></h4>
                    <div class="status-indicator <?php echo $ga4_config['is_configured'] ? 'connected' : 'disconnected'; ?>">
                        <span class="status-dot"></span>
                        <span class="status-text">
                            <?php echo $ga4_config['is_configured'] ? 
                                __('Connected', 'env-analytics') : 
                                __('Not Connected', 'env-analytics'); ?>
                        </span>
                    </div>
                    <button type="button" class="button" id="test-ga4-connection">
                        <?php _e('Test Connection', 'env-analytics'); ?>
                    </button>
                </div>
                
                <!-- Tracking Settings -->
                <h3><?php _e('Tracking Settings', 'env-analytics'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Data Retention', 'env-analytics'); ?></th>
                        <td>
                            <select name="env_analytics_data_retention">
                                <option value="90" <?php selected(get_option('env_analytics_data_retention', 90), 90); ?>>
                                    <?php _e('90 days', 'env-analytics'); ?>
                                </option>
                                <option value="180" <?php selected(get_option('env_analytics_data_retention', 90), 180); ?>>
                                    <?php _e('180 days', 'env-analytics'); ?>
                                </option>
                                <option value="365" <?php selected(get_option('env_analytics_data_retention', 90), 365); ?>>
                                    <?php _e('1 year', 'env-analytics'); ?>
                                </option>
                                <option value="0" <?php selected(get_option('env_analytics_data_retention', 90), 0); ?>>
                                    <?php _e('Keep forever', 'env-analytics'); ?>
                                </option>
                            </select>
                            <p class="description"><?php _e('How long to keep analytics data', 'env-analytics'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Anonymous Tracking', 'env-analytics'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="env_analytics_anonymous_tracking" value="1" 
                                       <?php checked(get_option('env_analytics_anonymous_tracking'), 1); ?>>
                                <?php _e('Enable anonymous user tracking', 'env-analytics'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <!-- Report Settings -->
                <h3><?php _e('Automated Reports', 'env-analytics'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Email Reports', 'env-analytics'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="env_analytics_email_reports" value="1" 
                                       <?php checked(get_option('env_analytics_email_reports'), 1); ?>>
                                <?php _e('Send weekly email reports', 'env-analytics'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Report Recipients', 'env-analytics'); ?></th>
                        <td>
                            <input type="text" name="env_analytics_report_emails" 
                                   value="<?php echo esc_attr(get_option('env_analytics_report_emails', get_option('admin_email'))); ?>" 
                                   class="large-text">
                            <p class="description"><?php _e('Comma-separated list of email addresses', 'env-analytics'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Get overview data for dashboard
     */
    private function get_overview_data() {
        global $wpdb;
        
        $current_period_start = date('Y-m-d', strtotime('-30 days'));
        $previous_period_start = date('Y-m-d', strtotime('-60 days'));
        $previous_period_end = date('Y-m-d', strtotime('-31 days'));
        
        // Current period stats
        $current_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_events,
                COUNT(DISTINCT user_id) as active_users
             FROM {$wpdb->prefix}env_analytics_events 
             WHERE DATE(event_date) >= %s",
            $current_period_start
        ));
        
        // Previous period stats for comparison
        $previous_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_events,
                COUNT(DISTINCT user_id) as active_users
             FROM {$wpdb->prefix}env_analytics_events 
             WHERE DATE(event_date) BETWEEN %s AND %s",
            $previous_period_start, $previous_period_end
        ));
        
        // Conversion stats
        $conversion_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) as total_conversions
             FROM {$wpdb->prefix}env_conversion_tracking 
             WHERE DATE(conversion_date) >= %s",
            $current_period_start
        ));
        
        $previous_conversions = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) as total_conversions
             FROM {$wpdb->prefix}env_conversion_tracking 
             WHERE DATE(conversion_date) BETWEEN %s AND %s",
            $previous_period_start, $previous_period_end
        ));
        
        // Engagement stats
        $engagement_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT AVG(engagement_score) as avg_engagement
             FROM {$wpdb->prefix}env_user_behavior 
             WHERE DATE(analyzed_at) >= %s",
            $current_period_start
        ));
        
        $previous_engagement = $wpdb->get_row($wpdb->prepare(
            "SELECT AVG(engagement_score) as avg_engagement
             FROM {$wpdb->prefix}env_user_behavior 
             WHERE DATE(analyzed_at) BETWEEN %s AND %s",
            $previous_period_start, $previous_period_end
        ));
        
        // Calculate percentage changes
        $events_change = $this->calculate_percentage_change(
            $previous_stats->total_events ?? 0, 
            $current_stats->total_events ?? 0
        );
        
        $users_change = $this->calculate_percentage_change(
            $previous_stats->active_users ?? 0, 
            $current_stats->active_users ?? 0
        );
        
        $conversions_change = $this->calculate_percentage_change(
            $previous_conversions->total_conversions ?? 0, 
            $conversion_stats->total_conversions ?? 0
        );
        
        $engagement_change = $this->calculate_percentage_change(
            $previous_engagement->avg_engagement ?? 0, 
            $engagement_stats->avg_engagement ?? 0
        );
        
        return array(
            'total_events' => $current_stats->total_events ?? 0,
            'active_users' => $current_stats->active_users ?? 0,
            'total_conversions' => $conversion_stats->total_conversions ?? 0,
            'avg_engagement' => $engagement_stats->avg_engagement ?? 0,
            'events_change' => $events_change,
            'users_change' => $users_change,
            'conversions_change' => $conversions_change,
            'engagement_change' => $engagement_change
        );
    }
    
    /**
     * Calculate percentage change
     */
    private function calculate_percentage_change($old_value, $new_value) {
        if ($old_value == 0) {
            return $new_value > 0 ? 100 : 0;
        }
        
        return round((($new_value - $old_value) / $old_value) * 100, 1);
    }
    
    /**
     * Display recent activity
     */
    private function display_recent_activity() {
        global $wpdb;
        
        $recent_events = $wpdb->get_results($wpdb->prepare(
            "SELECT event_action, event_category, user_id, event_date, event_data
             FROM {$wpdb->prefix}env_analytics_events 
             ORDER BY event_date DESC 
             LIMIT %d",
            10
        ));
        
        if (empty($recent_events)) {
            echo '<p>' . __('No recent activity found.', 'env-analytics') . '</p>';
            return;
        }
        
        echo '<ul class="recent-activity-list">';
        foreach ($recent_events as $event) {
            $user_info = get_userdata($event->user_id);
            $username = $user_info ? $user_info->display_name : __('Anonymous', 'env-analytics');
            $time_ago = human_time_diff(strtotime($event->event_date), current_time('timestamp'));
            
            echo '<li>';
            echo '<strong>' . esc_html($username) . '</strong> ';
            echo esc_html($event->event_action) . ' ';
            echo '<span class="activity-time">' . sprintf(__('%s ago', 'env-analytics'), $time_ago) . '</span>';
            echo '</li>';
        }
        echo '</ul>';
    }
    
    /**
     * Display goals table
     */
    private function display_goals_table() {
        $goals = $this->conversion_tracker->get_active_goals();
        
        if (empty($goals)) {
            echo '<tr><td colspan="7">' . __('No conversion goals found.', 'env-analytics') . '</td></tr>';
            return;
        }
        
        foreach ($goals as $goal) {
            $conversion_data = $this->conversion_tracker->get_conversion_data($goal->id);
            $conversion_rate = $conversion_data['total_conversions'] > 0 ? 
                round(($conversion_data['total_conversions'] / $conversion_data['total_conversions']) * 100, 2) : 0;
            
            echo '<tr>';
            echo '<td><strong>' . esc_html($goal->name) . '</strong><br><small>' . esc_html($goal->description) . '</small></td>';
            echo '<td>' . esc_html(ucfirst($goal->type)) . '</td>';
            echo '<td>' . number_format($conversion_data['total_conversions']) . '</td>';
            echo '<td>$' . number_format($conversion_data['total_value'], 2) . '</td>';
            echo '<td>' . $conversion_rate . '%</td>';
            echo '<td><span class="status-' . ($goal->is_active ? 'active' : 'inactive') . '">' . 
                 ($goal->is_active ? __('Active', 'env-analytics') : __('Inactive', 'env-analytics')) . '</span></td>';
            echo '<td>';
            echo '<button type="button" class="button edit-goal" data-goal-id="' . $goal->id . '">' . __('Edit', 'env-analytics') . '</button> ';
            echo '<button type="button" class="button delete-goal" data-goal-id="' . $goal->id . '">' . __('Delete', 'env-analytics') . '</button>';
            echo '</td>';
            echo '</tr>';
        }
    }
    
    /**
     * Add dashboard widgets
     */
    public function add_dashboard_widgets() {
        wp_add_dashboard_widget(
            'env_analytics_overview',
            __('Environmental Analytics Overview', 'env-analytics'),
            array($this, 'dashboard_widget_overview')
        );
    }
    
    /**
     * Dashboard widget overview
     */
    public function dashboard_widget_overview() {
        $overview_data = $this->get_overview_data();
        ?>
        <div class="env-analytics-widget-overview">
            <div class="widget-metrics">
                <div class="metric">
                    <span class="metric-label"><?php _e('Events (30 days)', 'env-analytics'); ?></span>
                    <span class="metric-value"><?php echo number_format($overview_data['total_events']); ?></span>
                </div>
                <div class="metric">
                    <span class="metric-label"><?php _e('Active Users', 'env-analytics'); ?></span>
                    <span class="metric-value"><?php echo number_format($overview_data['active_users']); ?></span>
                </div>
                <div class="metric">
                    <span class="metric-label"><?php _e('Conversions', 'env-analytics'); ?></span>
                    <span class="metric-value"><?php echo number_format($overview_data['total_conversions']); ?></span>
                </div>
            </div>
            <div class="widget-actions">
                <a href="<?php echo admin_url('admin.php?page=env-analytics'); ?>" class="button button-small">
                    <?php _e('View Full Dashboard', 'env-analytics'); ?>
                </a>
            </div>
        </div>
        <?php
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('env_analytics_settings', 'env_analytics_ga4_measurement_id');
        register_setting('env_analytics_settings', 'env_analytics_ga4_api_secret');
        register_setting('env_analytics_settings', 'env_analytics_ga4_debug');
        register_setting('env_analytics_settings', 'env_analytics_exclude_admins');
        register_setting('env_analytics_settings', 'env_analytics_data_retention');
        register_setting('env_analytics_settings', 'env_analytics_anonymous_tracking');
        register_setting('env_analytics_settings', 'env_analytics_email_reports');
        register_setting('env_analytics_settings', 'env_analytics_report_emails');
    }
    
    /**
     * AJAX: Get overview data
     */
    public function ajax_get_overview() {
        check_ajax_referer('env_analytics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $data = $this->get_overview_data();
        wp_send_json_success($data);
    }
    
    /**
     * AJAX: Get charts data
     */
    public function ajax_get_charts_data() {
        check_ajax_referer('env_analytics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $date_from = sanitize_text_field($_POST['date_from'] ?? date('Y-m-d', strtotime('-30 days')));
        $date_to = sanitize_text_field($_POST['date_to'] ?? date('Y-m-d'));
        
        global $wpdb;
        
        // Events over time
        $events_data = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(event_date) as date, COUNT(*) as events
             FROM {$wpdb->prefix}env_analytics_events 
             WHERE DATE(event_date) BETWEEN %s AND %s
             GROUP BY DATE(event_date)
             ORDER BY date",
            $date_from, $date_to
        ));
        
        // Engagement distribution
        $engagement_data = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                CASE 
                    WHEN engagement_score >= 40 THEN 'High'
                    WHEN engagement_score >= 20 THEN 'Medium'
                    ELSE 'Low'
                END as engagement_level,
                COUNT(*) as user_count
             FROM {$wpdb->prefix}env_user_behavior 
             WHERE DATE(analyzed_at) BETWEEN %s AND %s
             GROUP BY engagement_level",
            $date_from, $date_to
        ));
        
        wp_send_json_success(array(
            'events_over_time' => $events_data,
            'engagement_distribution' => $engagement_data
        ));
    }
    
    /**
     * AJAX: Export data
     */
    public function ajax_export_data() {
        check_ajax_referer('env_analytics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $format = sanitize_text_field($_POST['format'] ?? 'csv');
        $date_from = sanitize_text_field($_POST['date_from'] ?? date('Y-m-d', strtotime('-30 days')));
        $date_to = sanitize_text_field($_POST['date_to'] ?? date('Y-m-d'));
        
        // Generate export file
        $export_url = $this->generate_export_file($format, $date_from, $date_to);
        
        wp_send_json_success(array('download_url' => $export_url));
    }
    
    /**
     * Generate export file
     */
    private function generate_export_file($format, $date_from, $date_to) {
        global $wpdb;
        
        $data = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}env_analytics_events 
             WHERE DATE(event_date) BETWEEN %s AND %s
             ORDER BY event_date DESC",
            $date_from, $date_to
        ));
        
        $upload_dir = wp_upload_dir();
        $filename = 'env-analytics-export-' . date('Y-m-d-H-i-s') . '.' . $format;
        $filepath = $upload_dir['path'] . '/' . $filename;
        
        if ($format === 'csv') {
            $this->generate_csv_file($data, $filepath);
        } elseif ($format === 'json') {
            $this->generate_json_file($data, $filepath);
        }
        
        return $upload_dir['url'] . '/' . $filename;
    }
    
    /**
     * Generate CSV file
     */
    private function generate_csv_file($data, $filepath) {
        $file = fopen($filepath, 'w');
        
        // Write headers
        fputcsv($file, array('ID', 'User ID', 'Event Action', 'Event Category', 'Event Date', 'Event Data'));
        
        // Write data
        foreach ($data as $row) {
            fputcsv($file, array(
                $row->id,
                $row->user_id,
                $row->event_action,
                $row->event_category,
                $row->event_date,
                $row->event_data
            ));
        }
        
        fclose($file);
    }
    
    /**
     * Generate JSON file
     */
    private function generate_json_file($data, $filepath) {
        file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    /**
     * AJAX: Save settings
     */
    public function ajax_save_settings() {
        check_ajax_referer('env_analytics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $settings = $_POST['settings'] ?? array();
        
        foreach ($settings as $key => $value) {
            update_option($key, sanitize_text_field($value));
        }
        
        wp_send_json_success(array('message' => __('Settings saved successfully', 'env-analytics')));
    }
}
