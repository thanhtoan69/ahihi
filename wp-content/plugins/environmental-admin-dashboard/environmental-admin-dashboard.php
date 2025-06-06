<?php
/**
 * Plugin Name: Environmental Admin Dashboard Customization
 * Plugin URI: https://moitruong.local/environmental-admin-dashboard
 * Description: Phase 49 - Custom admin dashboard widgets, advanced content management interfaces, bulk operations, reporting views, and admin notifications system for the Environmental Platform.
 * Version: 1.0.0
 * Author: Environmental Platform Team
 * License: GPL v2 or later
 * Text Domain: env-admin-dashboard
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ENV_ADMIN_DASHBOARD_VERSION', '1.0.0');
define('ENV_ADMIN_DASHBOARD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ENV_ADMIN_DASHBOARD_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ENV_ADMIN_DASHBOARD_PLUGIN_FILE', __FILE__);

/**
 * Main Environmental Admin Dashboard Class
 */
class Environmental_Admin_Dashboard {
    
    private static $instance = null;
    
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
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'init'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_ajax_env_dashboard_action', array($this, 'ajax_dashboard_action'));
        add_action('wp_ajax_env_bulk_operation', array($this, 'ajax_bulk_operation'));
        add_action('wp_ajax_env_notification_action', array($this, 'ajax_notification_action'));
        add_action('admin_notices', array($this, 'display_admin_notices'));
        
        // Custom dashboard columns
        add_filter('dashboard_glance_items', array($this, 'add_glance_items'));
        
        // Bulk actions for posts
        add_filter('bulk_actions-edit-post', array($this, 'add_bulk_actions'));
        add_filter('handle_bulk_actions-edit-post', array($this, 'handle_bulk_actions'), 10, 3);
        
        // Custom admin bar
        add_action('admin_bar_menu', array($this, 'add_admin_bar_menu'), 100);
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once ENV_ADMIN_DASHBOARD_PLUGIN_PATH . 'includes/class-dashboard-widgets.php';
        require_once ENV_ADMIN_DASHBOARD_PLUGIN_PATH . 'includes/class-content-manager.php';
        require_once ENV_ADMIN_DASHBOARD_PLUGIN_PATH . 'includes/class-bulk-operations.php';
        require_once ENV_ADMIN_DASHBOARD_PLUGIN_PATH . 'includes/class-reporting-dashboard.php';
        require_once ENV_ADMIN_DASHBOARD_PLUGIN_PATH . 'includes/class-notifications-manager.php';
        require_once ENV_ADMIN_DASHBOARD_PLUGIN_PATH . 'includes/class-admin-customizer.php';
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        $this->create_database_tables();
        $this->set_default_options();
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('env-admin-dashboard', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize components
        if (class_exists('Environmental_Dashboard_Widgets')) {
            Environmental_Dashboard_Widgets::get_instance();
        }
        if (class_exists('Environmental_Content_Manager')) {
            Environmental_Content_Manager::get_instance();
        }
        if (class_exists('Environmental_Bulk_Operations')) {
            Environmental_Bulk_Operations::get_instance();
        }
        if (class_exists('Environmental_Reporting_Dashboard')) {
            Environmental_Reporting_Dashboard::get_instance();
        }
        if (class_exists('Environmental_Notifications_Manager')) {
            Environmental_Notifications_Manager::get_instance();
        }
        if (class_exists('Environmental_Admin_Customizer')) {
            Environmental_Admin_Customizer::get_instance();
        }
    }
    
    /**
     * Admin initialization
     */
    public function admin_init() {
        // Register settings
        register_setting('env_dashboard_settings', 'env_dashboard_options');
        
        // Add meta boxes
        add_meta_box(
            'env_content_analytics',
            __('Environmental Analytics', 'env-admin-dashboard'),
            array($this, 'render_content_analytics_meta_box'),
            array('post', 'page'),
            'normal',
            'high'
        );
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main dashboard menu
        add_menu_page(
            __('Environmental Dashboard', 'env-admin-dashboard'),
            __('Env Dashboard', 'env-admin-dashboard'),
            'manage_options',
            'env-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-chart-area',
            2
        );
        
        // Submenu pages
        add_submenu_page(
            'env-dashboard',
            __('Dashboard Overview', 'env-admin-dashboard'),
            __('Overview', 'env-admin-dashboard'),
            'manage_options',
            'env-dashboard',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'env-dashboard',
            __('Content Management', 'env-admin-dashboard'),
            __('Content', 'env-admin-dashboard'),
            'manage_options',
            'env-content-management',
            array($this, 'content_management_page')
        );
        
        add_submenu_page(
            'env-dashboard',
            __('Bulk Operations', 'env-admin-dashboard'),
            __('Bulk Operations', 'env-admin-dashboard'),
            'manage_options',
            'env-bulk-operations',
            array($this, 'bulk_operations_page')
        );
        
        add_submenu_page(
            'env-dashboard',
            __('Reporting Dashboard', 'env-admin-dashboard'),
            __('Reports', 'env-admin-dashboard'),
            'manage_options',
            'env-reporting',
            array($this, 'reporting_page')
        );
        
        add_submenu_page(
            'env-dashboard',
            __('Notifications', 'env-admin-dashboard'),
            __('Notifications', 'env-admin-dashboard'),
            'manage_options',
            'env-notifications',
            array($this, 'notifications_page')
        );
        
        add_submenu_page(
            'env-dashboard',
            __('Dashboard Settings', 'env-admin-dashboard'),
            __('Settings', 'env-admin-dashboard'),
            'manage_options',
            'env-dashboard-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Add dashboard widgets
     */
    public function add_dashboard_widgets() {
        // Environmental Platform Overview Widget
        wp_add_dashboard_widget(
            'env_platform_overview',
            '🌱 Environmental Platform Overview',
            array($this, 'platform_overview_widget')
        );
        
        // Recent Environmental Activities Widget
        wp_add_dashboard_widget(
            'env_recent_activities',
            '🌍 Recent Environmental Activities',
            array($this, 'recent_activities_widget')
        );
        
        // Environmental Goals Progress Widget
        wp_add_dashboard_widget(
            'env_goals_progress',
            '🎯 Environmental Goals Progress',
            array($this, 'goals_progress_widget')
        );
        
        // Content Performance Widget
        wp_add_dashboard_widget(
            'env_content_performance',
            '📊 Content Performance',
            array($this, 'content_performance_widget')
        );
        
        // System Health Widget
        wp_add_dashboard_widget(
            'env_system_health',
            '⚡ System Health',
            array($this, 'system_health_widget')
        );
        
        // Quick Actions Widget
        wp_add_dashboard_widget(
            'env_quick_actions',
            '⚡ Quick Actions',
            array($this, 'quick_actions_widget')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'env-') === false && $hook !== 'index.php') {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'env-admin-dashboard',
            ENV_ADMIN_DASHBOARD_PLUGIN_URL . 'assets/css/admin-dashboard.css',
            array(),
            ENV_ADMIN_DASHBOARD_VERSION
        );
        
        // Enqueue JavaScript
        wp_enqueue_script('jquery');
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        wp_enqueue_script(
            'env-admin-dashboard',
            ENV_ADMIN_DASHBOARD_PLUGIN_URL . 'assets/js/admin-dashboard.js',
            array('jquery', 'chart-js'),
            ENV_ADMIN_DASHBOARD_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('env-admin-dashboard', 'envAdminDashboard', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('env_dashboard_nonce'),
            'strings' => array(
                'loading' => __('Loading...', 'env-admin-dashboard'),
                'error' => __('An error occurred', 'env-admin-dashboard'),
                'success' => __('Action completed successfully', 'env-admin-dashboard'),
                'confirm_bulk' => __('Are you sure you want to perform this bulk action?', 'env-admin-dashboard'),
                'no_items_selected' => __('Please select items to perform bulk action', 'env-admin-dashboard')
            )
        ));
    }
    
    /**
     * Create database tables
     */
    private function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Dashboard widgets table
        $table_widgets = $wpdb->prefix . 'env_dashboard_widgets';
        $sql_widgets = "CREATE TABLE $table_widgets (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            widget_id varchar(100) NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            widget_order int(11) NOT NULL DEFAULT 0,
            widget_settings longtext,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY widget_user (widget_id, user_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        // Admin notifications table
        $table_notifications = $wpdb->prefix . 'env_admin_notifications';
        $sql_notifications = "CREATE TABLE $table_notifications (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            message text NOT NULL,
            type varchar(50) NOT NULL DEFAULT 'info',
            priority int(11) NOT NULL DEFAULT 1,
            target_users longtext,
            is_dismissible tinyint(1) NOT NULL DEFAULT 1,
            expires_at datetime NULL,
            created_by bigint(20) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY type (type),
            KEY priority (priority),
            KEY created_by (created_by)
        ) $charset_collate;";
        
        // Bulk operations log table
        $table_bulk_ops = $wpdb->prefix . 'env_bulk_operations_log';
        $sql_bulk_ops = "CREATE TABLE $table_bulk_ops (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            operation_type varchar(100) NOT NULL,
            operation_data longtext,
            affected_items longtext,
            status varchar(50) NOT NULL DEFAULT 'pending',
            error_message text,
            user_id bigint(20) unsigned NOT NULL,
            started_at datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime NULL,
            PRIMARY KEY (id),
            KEY operation_type (operation_type),
            KEY status (status),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_widgets);
        dbDelta($sql_notifications);
        dbDelta($sql_bulk_ops);
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        $default_options = array(
            'dashboard_layout' => 'two_column',
            'enable_notifications' => true,
            'enable_analytics' => true,
            'default_widgets' => array(
                'env_platform_overview',
                'env_recent_activities',
                'env_goals_progress',
                'env_content_performance'
            ),
            'notification_settings' => array(
                'email_notifications' => true,
                'browser_notifications' => false,
                'notification_frequency' => 'daily'
            )
        );
        
        if (!get_option('env_dashboard_options')) {
            add_option('env_dashboard_options', $default_options);
        }
    }
    
    /**
     * AJAX handler for dashboard actions
     */
    public function ajax_dashboard_action() {
        check_ajax_referer('env_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Insufficient permissions')));
        }
        
        $action = sanitize_text_field($_POST['dashboard_action']);
        $data = array();
        
        switch ($action) {
            case 'get_analytics':
                $data = $this->get_analytics_data();
                break;
            case 'update_widget_order':
                $data = $this->update_widget_order($_POST['widgets']);
                break;
            case 'toggle_widget':
                $data = $this->toggle_widget($_POST['widget_id']);
                break;
            default:
                $data = array('success' => false, 'message' => 'Invalid action');
        }
        
        wp_die(json_encode($data));
    }
    
    /**
     * AJAX handler for bulk operations
     */
    public function ajax_bulk_operation() {
        check_ajax_referer('env_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Insufficient permissions')));
        }
        
        $operation = sanitize_text_field($_POST['operation']);
        $items = array_map('intval', $_POST['items']);
        
        $bulk_ops = Environmental_Bulk_Operations::get_instance();
        $result = $bulk_ops->perform_bulk_operation($operation, $items);
        
        wp_die(json_encode($result));
    }
    
    /**
     * AJAX handler for notification actions
     */
    public function ajax_notification_action() {
        check_ajax_referer('env_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Insufficient permissions')));
        }
        
        $action = sanitize_text_field($_POST['notification_action']);
        $notification_id = intval($_POST['notification_id']);
        
        $notifications = Environmental_Notifications_Manager::get_instance();
        $result = $notifications->handle_notification_action($action, $notification_id);
        
        wp_die(json_encode($result));
    }
    
    /**
     * Display admin notices
     */
    public function display_admin_notices() {
        $notifications = Environmental_Notifications_Manager::get_instance();
        $notifications->display_admin_notices();
    }
    
    /**
     * Add custom items to dashboard glance widget
     */
    public function add_glance_items($items) {
        global $wpdb;
        
        // Get environmental data counts
        $env_data = array();
        
        // Check if environmental tables exist
        $tables_exist = $wpdb->get_var("SHOW TABLES LIKE 'users'");
        if ($tables_exist) {
            $env_data['users'] = $wpdb->get_var("SELECT COUNT(*) FROM users");
            $env_data['events'] = $wpdb->get_var("SELECT COUNT(*) FROM events");
            $env_data['achievements'] = $wpdb->get_var("SELECT COUNT(*) FROM achievements");
        }
        
        if (!empty($env_data['users'])) {
            $items[] = sprintf(
                '<a class="env-glance-count" href="%s">%s %s</a>',
                admin_url('admin.php?page=env-content-management'),
                number_format_i18n($env_data['users']),
                _n('Environmental User', 'Environmental Users', $env_data['users'], 'env-admin-dashboard')
            );
        }
        
        if (!empty($env_data['events'])) {
            $items[] = sprintf(
                '<a class="env-glance-count" href="%s">%s %s</a>',
                admin_url('admin.php?page=env-content-management'),
                number_format_i18n($env_data['events']),
                _n('Environmental Event', 'Environmental Events', $env_data['events'], 'env-admin-dashboard')
            );
        }
        
        return $items;
    }
    
    /**
     * Add bulk actions for posts
     */
    public function add_bulk_actions($bulk_actions) {
        $bulk_actions['env_analyze_impact'] = __('Analyze Environmental Impact', 'env-admin-dashboard');
        $bulk_actions['env_add_tags'] = __('Add Environmental Tags', 'env-admin-dashboard');
        $bulk_actions['env_update_status'] = __('Update Environmental Status', 'env-admin-dashboard');
        return $bulk_actions;
    }
    
    /**
     * Handle bulk actions
     */
    public function handle_bulk_actions($redirect_to, $doaction, $post_ids) {
        if (strpos($doaction, 'env_') !== 0) {
            return $redirect_to;
        }
        
        $bulk_ops = Environmental_Bulk_Operations::get_instance();
        $result = $bulk_ops->handle_post_bulk_action($doaction, $post_ids);
        
        if ($result['success']) {
            $redirect_to = add_query_arg('env_bulk_action', $doaction, $redirect_to);
            $redirect_to = add_query_arg('env_processed', count($post_ids), $redirect_to);
        } else {
            $redirect_to = add_query_arg('env_bulk_error', urlencode($result['message']), $redirect_to);
        }
        
        return $redirect_to;
    }
    
    /**
     * Add admin bar menu
     */
    public function add_admin_bar_menu($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $wp_admin_bar->add_node(array(
            'id' => 'env-dashboard',
            'title' => '🌱 Env Dashboard',
            'href' => admin_url('admin.php?page=env-dashboard')
        ));
        
        $wp_admin_bar->add_node(array(
            'id' => 'env-quick-content',
            'parent' => 'env-dashboard',
            'title' => 'Content Management',
            'href' => admin_url('admin.php?page=env-content-management')
        ));
        
        $wp_admin_bar->add_node(array(
            'id' => 'env-quick-reports',
            'parent' => 'env-dashboard',
            'title' => 'Reports',
            'href' => admin_url('admin.php?page=env-reporting')
        ));
    }
    
    /**
     * Get analytics data
     */
    private function get_analytics_data() {
        global $wpdb;
        
        $data = array(
            'posts' => array(
                'total' => wp_count_posts()->publish,
                'today' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND DATE(post_date) = CURDATE()"),
                'week' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)")
            ),
            'users' => array(
                'total' => count_users()['total_users'],
                'today' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users} WHERE DATE(user_registered) = CURDATE()"),
                'week' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users} WHERE user_registered >= DATE_SUB(NOW(), INTERVAL 7 DAY)")
            )
        );
        
        // Add environmental data if available
        $tables_exist = $wpdb->get_var("SHOW TABLES LIKE 'users'");
        if ($tables_exist) {
            $data['environmental'] = array(
                'total_env_users' => $wpdb->get_var("SELECT COUNT(*) FROM users"),
                'active_events' => $wpdb->get_var("SELECT COUNT(*) FROM events WHERE status = 'active'"),
                'total_achievements' => $wpdb->get_var("SELECT COUNT(*) FROM achievements")
            );
        }
        
        return array('success' => true, 'data' => $data);
    }
    
    /**
     * Update widget order
     */
    private function update_widget_order($widgets) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'env_dashboard_widgets';
        $user_id = get_current_user_id();
        
        foreach ($widgets as $index => $widget_id) {
            $wpdb->replace($table, array(
                'widget_id' => sanitize_text_field($widget_id),
                'user_id' => $user_id,
                'widget_order' => $index,
                'is_active' => 1
            ));
        }
        
        return array('success' => true, 'message' => 'Widget order updated');
    }
    
    /**
     * Toggle widget visibility
     */
    private function toggle_widget($widget_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'env_dashboard_widgets';
        $user_id = get_current_user_id();
        
        $current = $wpdb->get_var($wpdb->prepare(
            "SELECT is_active FROM $table WHERE widget_id = %s AND user_id = %d",
            $widget_id, $user_id
        ));
        
        $new_status = $current ? 0 : 1;
        
        $wpdb->replace($table, array(
            'widget_id' => sanitize_text_field($widget_id),
            'user_id' => $user_id,
            'is_active' => $new_status
        ));
        
        return array('success' => true, 'active' => $new_status);
    }
    
    // Dashboard page methods
    public function dashboard_page() {
        include ENV_ADMIN_DASHBOARD_PLUGIN_PATH . 'admin/dashboard-overview.php';
    }
    
    public function content_management_page() {
        include ENV_ADMIN_DASHBOARD_PLUGIN_PATH . 'admin/content-management.php';
    }
    
    public function bulk_operations_page() {
        include ENV_ADMIN_DASHBOARD_PLUGIN_PATH . 'admin/bulk-operations.php';
    }
    
    public function reporting_page() {
        include ENV_ADMIN_DASHBOARD_PLUGIN_PATH . 'admin/reporting-dashboard.php';
    }
    
    public function notifications_page() {
        include ENV_ADMIN_DASHBOARD_PLUGIN_PATH . 'admin/notifications.php';
    }
    
    public function settings_page() {
        include ENV_ADMIN_DASHBOARD_PLUGIN_PATH . 'admin/settings.php';
    }
    
    // Widget rendering methods
    public function platform_overview_widget() {
        include ENV_ADMIN_DASHBOARD_PLUGIN_PATH . 'widgets/platform-overview.php';
    }
    
    public function recent_activities_widget() {
        include ENV_ADMIN_DASHBOARD_PLUGIN_PATH . 'widgets/recent-activities.php';
    }
    
    public function goals_progress_widget() {
        include ENV_ADMIN_DASHBOARD_PLUGIN_PATH . 'widgets/goals-progress.php';
    }
    
    public function content_performance_widget() {
        include ENV_ADMIN_DASHBOARD_PLUGIN_PATH . 'widgets/content-performance.php';
    }
    
    public function system_health_widget() {
        include ENV_ADMIN_DASHBOARD_PLUGIN_PATH . 'widgets/system-health.php';
    }
    
    public function quick_actions_widget() {
        include ENV_ADMIN_DASHBOARD_PLUGIN_PATH . 'widgets/quick-actions.php';
    }
    
    public function render_content_analytics_meta_box($post) {
        include ENV_ADMIN_DASHBOARD_PLUGIN_PATH . 'meta-boxes/content-analytics.php';
    }
}

// Initialize the plugin
function environmental_admin_dashboard_init() {
    return Environmental_Admin_Dashboard::get_instance();
}

// Hook into plugins_loaded
add_action('plugins_loaded', 'environmental_admin_dashboard_init');
