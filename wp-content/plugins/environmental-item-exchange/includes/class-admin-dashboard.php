<?php
/**
 * Admin Dashboard for Environmental Item Exchange
 * 
 * Provides comprehensive management interface for administrators
 * including analytics, user management, and system monitoring
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Item_Exchange_Admin_Dashboard {
    
    private static $instance = null;
    private $matching_engine;
    private $analytics;
    private $notifications;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->matching_engine = Environmental_Item_Exchange_Matching_Engine::get_instance();
        $this->analytics = Environmental_Item_Exchange_Analytics::get_instance();
        $this->notifications = Environmental_Item_Exchange_Notifications::get_instance();
        
        add_action('admin_menu', array($this, 'add_admin_menus'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_ep_admin_get_dashboard_data', array($this, 'ajax_get_dashboard_data'));
        add_action('wp_ajax_ep_admin_manage_user', array($this, 'ajax_manage_user'));
        add_action('wp_ajax_ep_admin_system_action', array($this, 'ajax_system_action'));
        add_action('wp_ajax_ep_admin_export_data', array($this, 'ajax_export_data'));
        add_action('wp_ajax_ep_admin_update_settings', array($this, 'ajax_update_settings'));
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menus() {
        // Main dashboard page
        add_menu_page(
            __('Item Exchange Dashboard', 'environmental-item-exchange'),
            __('Exchange Platform', 'environmental-item-exchange'),
            'manage_options',
            'ep-dashboard',
            array($this, 'render_dashboard_page'),
            'dashicons-randomize',
            25
        );
        
        // Sub-menu pages
        add_submenu_page(
            'ep-dashboard',
            __('Analytics & Reports', 'environmental-item-exchange'),
            __('Analytics', 'environmental-item-exchange'),
            'manage_options',
            'ep-analytics',
            array($this, 'render_analytics_page')
        );
        
        add_submenu_page(
            'ep-dashboard',
            __('User Management', 'environmental-item-exchange'),
            __('Users', 'environmental-item-exchange'),
            'manage_options',
            'ep-users',
            array($this, 'render_users_page')
        );
        
        add_submenu_page(
            'ep-dashboard',
            __('Matching Engine', 'environmental-item-exchange'),
            __('Matching', 'environmental-item-exchange'),
            'manage_options',
            'ep-matching',
            array($this, 'render_matching_page')
        );
        
        add_submenu_page(
            'ep-dashboard',
            __('System Settings', 'environmental-item-exchange'),
            __('Settings', 'environmental-item-exchange'),
            'manage_options',
            'ep-settings',
            array($this, 'render_settings_page')
        );
        
        add_submenu_page(
            'ep-dashboard',
            __('System Health', 'environmental-item-exchange'),
            __('System Health', 'environmental-item-exchange'),
            'manage_options',
            'ep-health',
            array($this, 'render_health_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'ep-') === false) {
            return;
        }
        
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        wp_enqueue_script('ep-admin-dashboard', 
            plugin_dir_url(__FILE__) . '../assets/js/admin-dashboard.js', 
            array('jquery', 'chart-js'), 
            '1.0.0', 
            true
        );
        
        wp_enqueue_style('ep-admin-dashboard', 
            plugin_dir_url(__FILE__) . '../assets/css/admin-dashboard.css', 
            array(), 
            '1.0.0'
        );
        
        wp_localize_script('ep-admin-dashboard', 'epAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ep_admin_nonce'),
            'strings' => array(
                'loading' => __('Loading...', 'environmental-item-exchange'),
                'error' => __('An error occurred', 'environmental-item-exchange'),
                'success' => __('Action completed successfully', 'environmental-item-exchange'),
                'confirm' => __('Are you sure?', 'environmental-item-exchange')
            )
        ));
    }
    
    /**
     * Render main dashboard page
     */
    public function render_dashboard_page() {
        $dashboard_data = $this->get_dashboard_overview();
        ?>
        <div class="wrap ep-admin-dashboard">
            <h1><?php _e('Environmental Item Exchange Dashboard', 'environmental-item-exchange'); ?></h1>
            
            <div class="ep-dashboard-stats">
                <div class="ep-stat-card">
                    <h3><?php _e('Total Exchanges', 'environmental-item-exchange'); ?></h3>
                    <div class="ep-stat-number"><?php echo number_format($dashboard_data['total_exchanges']); ?></div>
                    <div class="ep-stat-change <?php echo $dashboard_data['exchanges_trend'] > 0 ? 'positive' : 'negative'; ?>">
                        <?php echo $dashboard_data['exchanges_trend'] > 0 ? '+' : ''; ?><?php echo $dashboard_data['exchanges_trend']; ?>% this month
                    </div>
                </div>
                
                <div class="ep-stat-card">
                    <h3><?php _e('Active Users', 'environmental-item-exchange'); ?></h3>
                    <div class="ep-stat-number"><?php echo number_format($dashboard_data['active_users']); ?></div>
                    <div class="ep-stat-change <?php echo $dashboard_data['users_trend'] > 0 ? 'positive' : 'negative'; ?>">
                        <?php echo $dashboard_data['users_trend'] > 0 ? '+' : ''; ?><?php echo $dashboard_data['users_trend']; ?>% this month
                    </div>
                </div>
                
                <div class="ep-stat-card">
                    <h3><?php _e('Environmental Impact', 'environmental-item-exchange'); ?></h3>
                    <div class="ep-stat-number"><?php echo number_format($dashboard_data['co2_saved']); ?> kg</div>
                    <div class="ep-stat-subtitle"><?php _e('CO2 Saved', 'environmental-item-exchange'); ?></div>
                </div>
                
                <div class="ep-stat-card">
                    <h3><?php _e('Match Success Rate', 'environmental-item-exchange'); ?></h3>
                    <div class="ep-stat-number"><?php echo $dashboard_data['match_success_rate']; ?>%</div>
                    <div class="ep-stat-subtitle"><?php _e('Matches to Exchanges', 'environmental-item-exchange'); ?></div>
                </div>
            </div>
            
            <div class="ep-dashboard-charts">
                <div class="ep-chart-container">
                    <h3><?php _e('Exchange Activity (Last 30 Days)', 'environmental-item-exchange'); ?></h3>
                    <canvas id="ep-activity-chart"></canvas>
                </div>
                
                <div class="ep-chart-container">
                    <h3><?php _e('Popular Categories', 'environmental-item-exchange'); ?></h3>
                    <canvas id="ep-categories-chart"></canvas>
                </div>
            </div>
            
            <div class="ep-dashboard-tables">
                <div class="ep-recent-exchanges">
                    <h3><?php _e('Recent Exchanges', 'environmental-item-exchange'); ?></h3>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php _e('Item', 'environmental-item-exchange'); ?></th>
                                <th><?php _e('User', 'environmental-item-exchange'); ?></th>
                                <th><?php _e('Type', 'environmental-item-exchange'); ?></th>
                                <th><?php _e('Status', 'environmental-item-exchange'); ?></th>
                                <th><?php _e('Date', 'environmental-item-exchange'); ?></th>
                                <th><?php _e('Actions', 'environmental-item-exchange'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="ep-recent-exchanges-tbody">
                            <!-- Populated via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="ep-dashboard-alerts">
                <h3><?php _e('System Alerts', 'environmental-item-exchange'); ?></h3>
                <div id="ep-system-alerts">
                    <!-- Populated via AJAX -->
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize dashboard
            EpAdminDashboard.init();
        });
        </script>
        <?php
    }
    
    /**
     * Render analytics page
     */
    public function render_analytics_page() {
        ?>
        <div class="wrap ep-admin-analytics">
            <h1><?php _e('Analytics & Reports', 'environmental-item-exchange'); ?></h1>
            
            <div class="ep-analytics-filters">
                <form id="ep-analytics-filter-form">
                    <label for="ep-date-range"><?php _e('Date Range:', 'environmental-item-exchange'); ?></label>
                    <select id="ep-date-range" name="date_range">
                        <option value="7"><?php _e('Last 7 Days', 'environmental-item-exchange'); ?></option>
                        <option value="30" selected><?php _e('Last 30 Days', 'environmental-item-exchange'); ?></option>
                        <option value="90"><?php _e('Last 3 Months', 'environmental-item-exchange'); ?></option>
                        <option value="365"><?php _e('Last Year', 'environmental-item-exchange'); ?></option>
                    </select>
                    
                    <label for="ep-report-type"><?php _e('Report Type:', 'environmental-item-exchange'); ?></label>
                    <select id="ep-report-type" name="report_type">
                        <option value="overview"><?php _e('Overview', 'environmental-item-exchange'); ?></option>
                        <option value="users"><?php _e('User Activity', 'environmental-item-exchange'); ?></option>
                        <option value="matching"><?php _e('Matching Performance', 'environmental-item-exchange'); ?></option>
                        <option value="environmental"><?php _e('Environmental Impact', 'environmental-item-exchange'); ?></option>
                    </select>
                    
                    <button type="submit" class="button button-primary"><?php _e('Generate Report', 'environmental-item-exchange'); ?></button>
                    <button type="button" id="ep-export-report" class="button"><?php _e('Export Report', 'environmental-item-exchange'); ?></button>
                </form>
            </div>
            
            <div id="ep-analytics-content">
                <div class="ep-analytics-loading">
                    <p><?php _e('Loading analytics data...', 'environmental-item-exchange'); ?></p>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            EpAnalytics.init();
        });
        </script>
        <?php
    }
    
    /**
     * Render users management page
     */
    public function render_users_page() {
        ?>
        <div class="wrap ep-admin-users">
            <h1><?php _e('User Management', 'environmental-item-exchange'); ?></h1>
            
            <div class="ep-users-filters">
                <form id="ep-users-filter-form">
                    <label for="ep-user-status"><?php _e('Status:', 'environmental-item-exchange'); ?></label>
                    <select id="ep-user-status" name="status">
                        <option value="all"><?php _e('All Users', 'environmental-item-exchange'); ?></option>
                        <option value="active"><?php _e('Active', 'environmental-item-exchange'); ?></option>
                        <option value="inactive"><?php _e('Inactive', 'environmental-item-exchange'); ?></option>
                        <option value="suspended"><?php _e('Suspended', 'environmental-item-exchange'); ?></option>
                        <option value="high_rating"><?php _e('High Rating (4.5+)', 'environmental-item-exchange'); ?></option>
                    </select>
                    
                    <label for="ep-user-search"><?php _e('Search:', 'environmental-item-exchange'); ?></label>
                    <input type="text" id="ep-user-search" name="search" placeholder="<?php _e('Search users...', 'environmental-item-exchange'); ?>">
                    
                    <button type="submit" class="button button-primary"><?php _e('Filter', 'environmental-item-exchange'); ?></button>
                </form>
            </div>
            
            <div class="ep-users-table-container">
                <table class="widefat striped" id="ep-users-table">
                    <thead>
                        <tr>
                            <th><?php _e('User', 'environmental-item-exchange'); ?></th>
                            <th><?php _e('Rating', 'environmental-item-exchange'); ?></th>
                            <th><?php _e('Exchanges', 'environmental-item-exchange'); ?></th>
                            <th><?php _e('Eco Points', 'environmental-item-exchange'); ?></th>
                            <th><?php _e('Status', 'environmental-item-exchange'); ?></th>
                            <th><?php _e('Last Active', 'environmental-item-exchange'); ?></th>
                            <th><?php _e('Actions', 'environmental-item-exchange'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="ep-users-tbody">
                        <!-- Populated via AJAX -->
                    </tbody>
                </table>
            </div>
            
            <div class="ep-users-pagination">
                <div id="ep-users-pagination-controls"></div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            EpUsersManager.init();
        });
        </script>
        <?php
    }
    
    /**
     * Render matching engine management page
     */
    public function render_matching_page() {
        $matching_insights = $this->matching_engine->get_matching_insights();
        ?>
        <div class="wrap ep-admin-matching">
            <h1><?php _e('Matching Engine Management', 'environmental-item-exchange'); ?></h1>
            
            <div class="ep-matching-overview">
                <div class="ep-matching-stats">
                    <div class="ep-stat-card">
                        <h3><?php _e('Matches Generated', 'environmental-item-exchange'); ?></h3>
                        <div class="ep-stat-number"><?php echo number_format($matching_insights['performance']->total_matches_generated); ?></div>
                        <div class="ep-stat-subtitle"><?php _e('Last 30 Days', 'environmental-item-exchange'); ?></div>
                    </div>
                    
                    <div class="ep-stat-card">
                        <h3><?php _e('Success Rate', 'environmental-item-exchange'); ?></h3>
                        <div class="ep-stat-number"><?php echo $matching_insights['success_rate']; ?>%</div>
                        <div class="ep-stat-subtitle"><?php _e('Matches to Contacts', 'environmental-item-exchange'); ?></div>
                    </div>
                    
                    <div class="ep-stat-card">
                        <h3><?php _e('Avg. Match Score', 'environmental-item-exchange'); ?></h3>
                        <div class="ep-stat-number"><?php echo round($matching_insights['performance']->average_score, 2); ?></div>
                        <div class="ep-stat-subtitle"><?php _e('Out of 1.0', 'environmental-item-exchange'); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="ep-matching-weights">
                <h3><?php _e('Matching Algorithm Weights', 'environmental-item-exchange'); ?></h3>
                <form id="ep-matching-weights-form">
                    <?php foreach ($matching_insights['current_weights'] as $factor => $weight): ?>
                    <div class="ep-weight-control">
                        <label for="weight_<?php echo $factor; ?>">
                            <?php echo ucwords(str_replace('_', ' ', $factor)); ?>
                        </label>
                        <input type="range" 
                               id="weight_<?php echo $factor; ?>" 
                               name="weights[<?php echo $factor; ?>]" 
                               min="0" max="1" step="0.01" 
                               value="<?php echo $weight; ?>">
                        <span class="ep-weight-value"><?php echo round($weight * 100, 1); ?>%</span>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="ep-weight-actions">
                        <button type="submit" class="button button-primary"><?php _e('Update Weights', 'environmental-item-exchange'); ?></button>
                        <button type="button" id="ep-reset-weights" class="button"><?php _e('Reset to Default', 'environmental-item-exchange'); ?></button>
                        <button type="button" id="ep-optimize-weights" class="button"><?php _e('Auto-Optimize', 'environmental-item-exchange'); ?></button>
                    </div>
                </form>
                
                <p class="description">
                    <?php _e('Last optimization:', 'environmental-item-exchange'); ?> 
                    <?php echo $matching_insights['last_optimization']; ?>
                </p>
            </div>
            
            <div class="ep-matching-categories">
                <h3><?php _e('Top Performing Categories', 'environmental-item-exchange'); ?></h3>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Category', 'environmental-item-exchange'); ?></th>
                            <th><?php _e('Matches', 'environmental-item-exchange'); ?></th>
                            <th><?php _e('Avg. Score', 'environmental-item-exchange'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matching_insights['top_categories'] as $category): ?>
                        <tr>
                            <td><?php echo esc_html($category->category_name); ?></td>
                            <td><?php echo number_format($category->match_count); ?></td>
                            <td><?php echo round($category->avg_score, 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            EpMatchingManager.init();
        });
        </script>
        <?php
    }
    
    /**
     * Render system settings page
     */
    public function render_settings_page() {
        $settings = $this->get_system_settings();
        ?>
        <div class="wrap ep-admin-settings">
            <h1><?php _e('System Settings', 'environmental-item-exchange'); ?></h1>
            
            <form id="ep-settings-form">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Enable Auto-Matching', 'environmental-item-exchange'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="auto_matching" value="1" 
                                       <?php checked($settings['auto_matching']); ?>>
                                <?php _e('Automatically generate matches for new items', 'environmental-item-exchange'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Match Update Frequency', 'environmental-item-exchange'); ?></th>
                        <td>
                            <select name="match_update_frequency">
                                <option value="hourly" <?php selected($settings['match_update_frequency'], 'hourly'); ?>><?php _e('Hourly', 'environmental-item-exchange'); ?></option>
                                <option value="twicedaily" <?php selected($settings['match_update_frequency'], 'twicedaily'); ?>><?php _e('Twice Daily', 'environmental-item-exchange'); ?></option>
                                <option value="daily" <?php selected($settings['match_update_frequency'], 'daily'); ?>><?php _e('Daily', 'environmental-item-exchange'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Minimum Match Score', 'environmental-item-exchange'); ?></th>
                        <td>
                            <input type="number" name="min_match_score" value="<?php echo $settings['min_match_score']; ?>" 
                                   min="0" max="1" step="0.1">
                            <p class="description"><?php _e('Minimum compatibility score to suggest matches', 'environmental-item-exchange'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Enable Notifications', 'environmental-item-exchange'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_notifications" value="1" 
                                       <?php checked($settings['enable_notifications']); ?>>
                                <?php _e('Send notifications for matches and updates', 'environmental-item-exchange'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Environmental Tracking', 'environmental-item-exchange'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="track_environmental_impact" value="1" 
                                       <?php checked($settings['track_environmental_impact']); ?>>
                                <?php _e('Track and display environmental impact metrics', 'environmental-item-exchange'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('API Access', 'environmental-item-exchange'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_api" value="1" 
                                       <?php checked($settings['enable_api']); ?>>
                                <?php _e('Enable REST API for external integrations', 'environmental-item-exchange'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary"><?php _e('Save Settings', 'environmental-item-exchange'); ?></button>
                </p>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            EpSettings.init();
        });
        </script>
        <?php
    }
    
    /**
     * Render system health page
     */
    public function render_health_page() {
        $health_data = $this->get_system_health();
        ?>
        <div class="wrap ep-admin-health">
            <h1><?php _e('System Health', 'environmental-item-exchange'); ?></h1>
            
            <div class="ep-health-overview">
                <div class="ep-health-status <?php echo $health_data['overall_status']; ?>">
                    <h2><?php _e('Overall System Status:', 'environmental-item-exchange'); ?> 
                        <?php echo ucfirst($health_data['overall_status']); ?>
                    </h2>
                </div>
            </div>
            
            <div class="ep-health-checks">
                <?php foreach ($health_data['checks'] as $check): ?>
                <div class="ep-health-check <?php echo $check['status']; ?>">
                    <h3><?php echo $check['name']; ?></h3>
                    <p><?php echo $check['description']; ?></p>
                    <?php if (!empty($check['action'])): ?>
                    <button class="button" onclick="<?php echo $check['action']; ?>"><?php echo $check['action_label']; ?></button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="ep-health-logs">
                <h3><?php _e('Recent System Activity', 'environmental-item-exchange'); ?></h3>
                <div id="ep-system-logs">
                    <!-- Populated via AJAX -->
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            EpSystemHealth.init();
        });
        </script>
        <?php
    }
    
    /**
     * Get dashboard overview data
     */
    private function get_dashboard_overview() {
        global $wpdb;
        
        // Get total exchanges
        $total_exchanges = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type = 'item_exchange' AND post_status = 'publish'
        ");
        
        // Get active users (users with exchanges in last 30 days)
        $active_users = $wpdb->get_var("
            SELECT COUNT(DISTINCT post_author) FROM {$wpdb->posts} 
            WHERE post_type = 'item_exchange' 
            AND post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        // Get environmental impact
        $co2_saved = $wpdb->get_var("
            SELECT SUM(CAST(meta_value AS DECIMAL(10,2))) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_carbon_footprint_saved'
        ");
        
        // Get match success rate
        $matching_stats = $this->matching_engine->get_match_statistics();
        $match_success_rate = 0;
        if ($matching_stats && $matching_stats->total_matches > 0) {
            $match_success_rate = round(($matching_stats->contacted_matches / $matching_stats->total_matches) * 100, 1);
        }
        
        return array(
            'total_exchanges' => intval($total_exchanges),
            'active_users' => intval($active_users),
            'co2_saved' => floatval($co2_saved) ?: 0,
            'match_success_rate' => $match_success_rate,
            'exchanges_trend' => $this->calculate_trend('exchanges'),
            'users_trend' => $this->calculate_trend('users')
        );
    }
    
    /**
     * Calculate trend percentage
     */
    private function calculate_trend($type) {
        global $wpdb;
        
        $current_month = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type = 'item_exchange'
            AND post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        $previous_month = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type = 'item_exchange'
            AND post_date >= DATE_SUB(NOW(), INTERVAL 60 DAY)
            AND post_date < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        if ($previous_month == 0) {
            return $current_month > 0 ? 100 : 0;
        }
        
        return round((($current_month - $previous_month) / $previous_month) * 100, 1);
    }
    
    /**
     * Get system settings
     */
    private function get_system_settings() {
        return array(
            'auto_matching' => get_option('ep_auto_matching', true),
            'match_update_frequency' => get_option('ep_match_update_frequency', 'hourly'),
            'min_match_score' => get_option('ep_min_match_score', 0.3),
            'enable_notifications' => get_option('ep_enable_notifications', true),
            'track_environmental_impact' => get_option('ep_track_environmental_impact', true),
            'enable_api' => get_option('ep_enable_api', true)
        );
    }
    
    /**
     * Get system health data
     */
    private function get_system_health() {
        $checks = array();
        
        // Database check
        $db_check = $this->check_database_health();
        $checks[] = array(
            'name' => __('Database Health', 'environmental-item-exchange'),
            'status' => $db_check['status'],
            'description' => $db_check['message']
        );
        
        // Cron jobs check
        $cron_check = $this->check_cron_jobs();
        $checks[] = array(
            'name' => __('Scheduled Tasks', 'environmental-item-exchange'),
            'status' => $cron_check['status'],
            'description' => $cron_check['message']
        );
        
        // API check
        $api_check = $this->check_api_health();
        $checks[] = array(
            'name' => __('API Endpoints', 'environmental-item-exchange'),
            'status' => $api_check['status'],
            'description' => $api_check['message']
        );
        
        // Determine overall status
        $overall_status = 'healthy';
        foreach ($checks as $check) {
            if ($check['status'] === 'error') {
                $overall_status = 'error';
                break;
            } elseif ($check['status'] === 'warning' && $overall_status !== 'error') {
                $overall_status = 'warning';
            }
        }
        
        return array(
            'overall_status' => $overall_status,
            'checks' => $checks
        );
    }
    
    /**
     * Check database health
     */
    private function check_database_health() {
        global $wpdb;
        
        try {
            // Check if required tables exist
            $required_tables = array(
                $wpdb->prefix . 'exchange_matches',
                $wpdb->prefix . 'exchange_messages',
                $wpdb->prefix . 'exchange_ratings'
            );
            
            foreach ($required_tables as $table) {
                $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
                if (!$exists) {
                    return array(
                        'status' => 'error',
                        'message' => sprintf(__('Required table %s is missing', 'environmental-item-exchange'), $table)
                    );
                }
            }
            
            return array(
                'status' => 'healthy',
                'message' => __('All database tables are present and accessible', 'environmental-item-exchange')
            );
            
        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'message' => __('Database connection error', 'environmental-item-exchange')
            );
        }
    }
    
    /**
     * Check cron jobs status
     */
    private function check_cron_jobs() {
        $cron_jobs = array(
            'ep_cron_update_matches',
            'ep_cron_optimize_weights'
        );
        
        $issues = array();
        foreach ($cron_jobs as $job) {
            if (!wp_next_scheduled($job)) {
                $issues[] = $job;
            }
        }
        
        if (empty($issues)) {
            return array(
                'status' => 'healthy',
                'message' => __('All scheduled tasks are running properly', 'environmental-item-exchange')
            );
        } else {
            return array(
                'status' => 'warning',
                'message' => sprintf(__('%d scheduled task(s) are not running', 'environmental-item-exchange'), count($issues))
            );
        }
    }
    
    /**
     * Check API health
     */
    private function check_api_health() {
        if (!get_option('ep_enable_api', true)) {
            return array(
                'status' => 'warning',
                'message' => __('API is disabled in settings', 'environmental-item-exchange')
            );
        }
        
        return array(
            'status' => 'healthy',
            'message' => __('API endpoints are enabled and functioning', 'environmental-item-exchange')
        );
    }
    
    /**
     * AJAX handler for dashboard data
     */
    public function ajax_get_dashboard_data() {
        check_ajax_referer('ep_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'environmental-item-exchange'));
        }
        
        $data_type = sanitize_text_field($_POST['data_type'] ?? '');
        
        switch ($data_type) {
            case 'recent_exchanges':
                $data = $this->get_recent_exchanges();
                break;
            case 'system_alerts':
                $data = $this->get_system_alerts();
                break;
            case 'chart_data':
                $data = $this->get_chart_data();
                break;
            default:
                $data = array();
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * Get recent exchanges data
     */
    private function get_recent_exchanges() {
        global $wpdb;
        
        return $wpdb->get_results("
            SELECT p.ID, p.post_title, p.post_author, p.post_date,
                   pm1.meta_value as exchange_type,
                   pm2.meta_value as exchange_status,
                   u.display_name
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_exchange_type'
            LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_exchange_status'
            LEFT JOIN {$wpdb->users} u ON p.post_author = u.ID
            WHERE p.post_type = 'item_exchange' AND p.post_status = 'publish'
            ORDER BY p.post_date DESC
            LIMIT 10
        ");
    }
    
    /**
     * Get system alerts
     */
    private function get_system_alerts() {
        $alerts = array();
        
        // Check for pending matches
        global $wpdb;
        $pending_matches = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}exchange_matches 
            WHERE match_status = 'suggested' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        
        if ($pending_matches > 50) {
            $alerts[] = array(
                'type' => 'warning',
                'message' => sprintf(__('%d new matches generated in the last 24 hours', 'environmental-item-exchange'), $pending_matches)
            );
        }
        
        return $alerts;
    }
    
    /**
     * Get chart data
     */
    private function get_chart_data() {
        global $wpdb;
        
        // Activity chart data (last 30 days)
        $activity_data = $wpdb->get_results("
            SELECT DATE(post_date) as date, COUNT(*) as count
            FROM {$wpdb->posts}
            WHERE post_type = 'item_exchange'
            AND post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(post_date)
            ORDER BY date ASC
        ");
        
        // Categories chart data
        $categories_data = $wpdb->get_results("
            SELECT t.name, COUNT(*) as count
            FROM {$wpdb->posts} p
            JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
            WHERE p.post_type = 'item_exchange'
            AND tt.taxonomy = 'exchange_type'
            AND p.post_status = 'publish'
            GROUP BY t.term_id
            ORDER BY count DESC
            LIMIT 10
        ");
        
        return array(
            'activity' => $activity_data,
            'categories' => $categories_data
        );
    }
    
    /**
     * AJAX handler for user management actions
     */
    public function ajax_manage_user() {
        check_ajax_referer('ep_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'environmental-item-exchange'));
        }
        
        $action = sanitize_text_field($_POST['action_type'] ?? '');
        $user_id = intval($_POST['user_id'] ?? 0);
        
        switch ($action) {
            case 'suspend':
                update_user_meta($user_id, '_ep_account_status', 'suspended');
                break;
            case 'activate':
                update_user_meta($user_id, '_ep_account_status', 'active');
                break;
            case 'reset_rating':
                delete_user_meta($user_id, '_exchange_rating');
                break;
        }
        
        wp_send_json_success(array('message' => __('User updated successfully', 'environmental-item-exchange')));
    }
    
    /**
     * AJAX handler for system actions
     */
    public function ajax_system_action() {
        check_ajax_referer('ep_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'environmental-item-exchange'));
        }
        
        $action = sanitize_text_field($_POST['action_type'] ?? '');
        
        switch ($action) {
            case 'clear_cache':
                wp_cache_flush();
                wp_send_json_success(array('message' => __('Cache cleared', 'environmental-item-exchange')));
                break;
                
            case 'regenerate_matches':
                $this->matching_engine->update_all_matches();
                wp_send_json_success(array('message' => __('Matches regenerated', 'environmental-item-exchange')));
                break;
                
            case 'optimize_weights':
                $this->matching_engine->update_weights_from_feedback();
                wp_send_json_success(array('message' => __('Weights optimized', 'environmental-item-exchange')));
                break;
        }
    }
    
    /**
     * AJAX handler for data export
     */
    public function ajax_export_data() {
        check_ajax_referer('ep_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'environmental-item-exchange'));
        }
        
        $export_type = sanitize_text_field($_POST['export_type'] ?? '');
        
        // Generate export data based on type
        $data = array();
        
        switch ($export_type) {
            case 'exchanges':
                $data = $this->export_exchanges_data();
                break;
            case 'users':
                $data = $this->export_users_data();
                break;
            case 'matches':
                $data = $this->export_matches_data();
                break;
        }
        
        wp_send_json_success(array(
            'data' => $data,
            'filename' => 'ep_' . $export_type . '_' . date('Y-m-d') . '.csv'
        ));
    }
    
    /**
     * Export exchanges data
     */
    private function export_exchanges_data() {
        global $wpdb;
        
        return $wpdb->get_results("
            SELECT 
                p.ID, p.post_title, p.post_date,
                u.display_name as user_name,
                pm1.meta_value as exchange_type,
                pm2.meta_value as exchange_status,
                pm3.meta_value as item_condition,
                pm4.meta_value as estimated_value
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->users} u ON p.post_author = u.ID
            LEFT JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_exchange_type'
            LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_exchange_status'
            LEFT JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_item_condition'
            LEFT JOIN {$wpdb->postmeta} pm4 ON p.ID = pm4.post_id AND pm4.meta_key = '_item_estimated_value'
            WHERE p.post_type = 'item_exchange' AND p.post_status = 'publish'
            ORDER BY p.post_date DESC
        ", ARRAY_A);
    }
    
    /**
     * Export users data
     */
    private function export_users_data() {
        global $wpdb;
        
        return $wpdb->get_results("
            SELECT 
                u.ID, u.display_name, u.user_email, u.user_registered,
                um1.meta_value as exchange_rating,
                um2.meta_value as eco_points,
                um3.meta_value as total_exchanges
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = '_exchange_rating'
            LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = '_eco_points'
            LEFT JOIN {$wpdb->usermeta} um3 ON u.ID = um3.user_id AND um3.meta_key = '_total_exchanges'
            ORDER BY u.user_registered DESC
        ", ARRAY_A);
    }
    
    /**
     * Export matches data
     */
    private function export_matches_data() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'exchange_matches';
        return $wpdb->get_results("
            SELECT 
                m.*,
                p1.post_title as item1_title,
                p2.post_title as item2_title,
                u1.display_name as user1_name,
                u2.display_name as user2_name
            FROM {$table} m
            LEFT JOIN {$wpdb->posts} p1 ON m.post_id_1 = p1.ID
            LEFT JOIN {$wpdb->posts} p2 ON m.post_id_2 = p2.ID
            LEFT JOIN {$wpdb->users} u1 ON p1.post_author = u1.ID
            LEFT JOIN {$wpdb->users} u2 ON p2.post_author = u2.ID
            ORDER BY m.created_at DESC
        ", ARRAY_A);
    }
    
    /**
     * AJAX handler for updating settings
     */
    public function ajax_update_settings() {
        check_ajax_referer('ep_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'environmental-item-exchange'));
        }
        
        $settings = $_POST['settings'] ?? array();
        
        foreach ($settings as $key => $value) {
            $option_key = 'ep_' . sanitize_key($key);
            update_option($option_key, sanitize_text_field($value));
        }
        
        wp_send_json_success(array('message' => __('Settings updated successfully', 'environmental-item-exchange')));
    }
}

// Initialize the admin dashboard
Environmental_Item_Exchange_Admin_Dashboard::get_instance();
