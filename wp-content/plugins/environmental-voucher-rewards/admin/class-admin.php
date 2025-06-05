<?php
/**
 * Admin Controller Class
 * 
 * Main admin interface controller for Environmental Voucher & Rewards
 * Handles admin menu, settings, and navigation
 * 
 * @package Environmental_Voucher_Rewards
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Admin {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Admin menu slug
     */
    const MENU_SLUG = 'environmental-voucher-rewards';
    
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
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_env_admin_action', array($this, 'handle_admin_action'));
        
        // Settings
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('Environmental Rewards', 'environmental-voucher-rewards'),
            __('Env Rewards', 'environmental-voucher-rewards'),
            'manage_options',
            self::MENU_SLUG,
            array($this, 'admin_dashboard_page'),
            'dashicons-awards',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            self::MENU_SLUG,
            __('Dashboard', 'environmental-voucher-rewards'),
            __('Dashboard', 'environmental-voucher-rewards'),
            'manage_options',
            self::MENU_SLUG,
            array($this, 'admin_dashboard_page')
        );
        
        // Voucher Management
        add_submenu_page(
            self::MENU_SLUG,
            __('Voucher Management', 'environmental-voucher-rewards'),
            __('Vouchers', 'environmental-voucher-rewards'),
            'manage_options',
            self::MENU_SLUG . '-vouchers',
            array($this, 'voucher_management_page')
        );
        
        // Rewards Dashboard
        add_submenu_page(
            self::MENU_SLUG,
            __('Rewards Dashboard', 'environmental-voucher-rewards'),
            __('Rewards', 'environmental-voucher-rewards'),
            'manage_options',
            self::MENU_SLUG . '-rewards',
            array($this, 'rewards_dashboard_page')
        );
        
        // Partner Management
        add_submenu_page(
            self::MENU_SLUG,
            __('Partner Management', 'environmental-voucher-rewards'),
            __('Partners', 'environmental-voucher-rewards'),
            'manage_options',
            self::MENU_SLUG . '-partners',
            array($this, 'partner_management_page')
        );
        
        // Analytics
        add_submenu_page(
            self::MENU_SLUG,
            __('Analytics', 'environmental-voucher-rewards'),
            __('Analytics', 'environmental-voucher-rewards'),
            'manage_options',
            self::MENU_SLUG . '-analytics',
            array($this, 'analytics_page')
        );
        
        // Settings
        add_submenu_page(
            self::MENU_SLUG,
            __('Settings', 'environmental-voucher-rewards'),
            __('Settings', 'environmental-voucher-rewards'),
            'manage_options',
            self::MENU_SLUG . '-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Admin initialization
     */
    public function admin_init() {
        // Add admin notices
        add_action('admin_notices', array($this, 'admin_notices'));
        
        // Check for required dependencies
        $this->check_dependencies();
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our admin pages
        if (strpos($hook, self::MENU_SLUG) === false) {
            return;
        }
        
        // Admin CSS
        wp_enqueue_style(
            'env-admin-css',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin.css',
            array(),
            '1.0.0'
        );
        
        // Admin JS
        wp_enqueue_script(
            'env-admin-js',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-datepicker', 'chart-js'),
            '1.0.0',
            true
        );
        
        // Chart.js for analytics
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js',
            array(),
            '3.9.1',
            true
        );
        
        // Localize script
        wp_localize_script('env-admin-js', 'envAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('env_admin_nonce'),
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to delete this item?', 'environmental-voucher-rewards'),
                'saved' => __('Settings saved successfully!', 'environmental-voucher-rewards'),
                'error' => __('An error occurred. Please try again.', 'environmental-voucher-rewards'),
                'loading' => __('Loading...', 'environmental-voucher-rewards')
            )
        ));
    }
    
    /**
     * Handle admin AJAX actions
     */
    public function handle_admin_action() {
        check_ajax_referer('env_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $action = sanitize_text_field($_POST['action_type']);
        
        switch ($action) {
            case 'test_system':
                $this->test_system_functionality();
                break;
            case 'clear_cache':
                $this->clear_plugin_cache();
                break;
            case 'export_data':
                $this->export_plugin_data();
                break;
            case 'import_data':
                $this->import_plugin_data();
                break;
            default:
                wp_send_json_error('Invalid action');
        }
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('env_settings_group', 'env_general_settings');
        register_setting('env_settings_group', 'env_voucher_settings');
        register_setting('env_settings_group', 'env_reward_settings');
        register_setting('env_settings_group', 'env_loyalty_settings');
        register_setting('env_settings_group', 'env_partner_settings');
        register_setting('env_settings_group', 'env_notification_settings');
    }
    
    /**
     * Dashboard page
     */
    public function admin_dashboard_page() {
        // Get quick stats
        $stats = $this->get_dashboard_stats();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Environmental Rewards Dashboard', 'environmental-voucher-rewards'); ?></h1>
            
            <div class="env-dashboard-grid">
                <!-- Quick Stats -->
                <div class="env-dashboard-card">
                    <h2><?php _e('Quick Stats', 'environmental-voucher-rewards'); ?></h2>
                    <div class="env-stats-grid">
                        <div class="env-stat-item">
                            <span class="env-stat-number"><?php echo esc_html($stats['total_vouchers']); ?></span>
                            <span class="env-stat-label"><?php _e('Total Vouchers', 'environmental-voucher-rewards'); ?></span>
                        </div>
                        <div class="env-stat-item">
                            <span class="env-stat-number"><?php echo esc_html($stats['active_campaigns']); ?></span>
                            <span class="env-stat-label"><?php _e('Active Campaigns', 'environmental-voucher-rewards'); ?></span>
                        </div>
                        <div class="env-stat-item">
                            <span class="env-stat-number"><?php echo esc_html($stats['total_rewards']); ?></span>
                            <span class="env-stat-label"><?php _e('Points Distributed', 'environmental-voucher-rewards'); ?></span>
                        </div>
                        <div class="env-stat-item">
                            <span class="env-stat-number"><?php echo esc_html($stats['active_users']); ?></span>
                            <span class="env-stat-label"><?php _e('Active Users', 'environmental-voucher-rewards'); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="env-dashboard-card">
                    <h2><?php _e('Recent Activity', 'environmental-voucher-rewards'); ?></h2>
                    <div class="env-activity-list">
                        <?php foreach ($stats['recent_activity'] as $activity): ?>
                        <div class="env-activity-item">
                            <span class="env-activity-time"><?php echo esc_html($activity->time); ?></span>
                            <span class="env-activity-text"><?php echo esc_html($activity->description); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- System Status -->
                <div class="env-dashboard-card">
                    <h2><?php _e('System Status', 'environmental-voucher-rewards'); ?></h2>
                    <div class="env-system-status">
                        <?php $this->display_system_status(); ?>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="env-dashboard-card">
                    <h2><?php _e('Quick Actions', 'environmental-voucher-rewards'); ?></h2>
                    <div class="env-quick-actions">
                        <a href="<?php echo admin_url('admin.php?page=' . self::MENU_SLUG . '-vouchers&action=new'); ?>" class="button button-primary">
                            <?php _e('Create Voucher Campaign', 'environmental-voucher-rewards'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=' . self::MENU_SLUG . '-partners&action=new'); ?>" class="button">
                            <?php _e('Add Partner', 'environmental-voucher-rewards'); ?>
                        </a>
                        <button type="button" class="button" id="test-system">
                            <?php _e('Test System', 'environmental-voucher-rewards'); ?>
                        </button>
                        <button type="button" class="button" id="clear-cache">
                            <?php _e('Clear Cache', 'environmental-voucher-rewards'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#test-system').click(function() {
                $.post(ajaxurl, {
                    action: 'env_admin_action',
                    action_type: 'test_system',
                    nonce: envAdmin.nonce
                }, function(response) {
                    if (response.success) {
                        alert('System test completed successfully!');
                    } else {
                        alert('System test failed: ' + response.data);
                    }
                });
            });
            
            $('#clear-cache').click(function() {
                $.post(ajaxurl, {
                    action: 'env_admin_action',
                    action_type: 'clear_cache',
                    nonce: envAdmin.nonce
                }, function(response) {
                    if (response.success) {
                        alert('Cache cleared successfully!');
                    } else {
                        alert('Cache clear failed: ' + response.data);
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Voucher management page
     */
    public function voucher_management_page() {
        $voucher_admin = Environmental_Voucher_Admin::get_instance();
        $voucher_admin->display_page();
    }
    
    /**
     * Rewards dashboard page
     */
    public function rewards_dashboard_page() {
        $rewards_dashboard = Environmental_Rewards_Dashboard::get_instance();
        $rewards_dashboard->display_page();
    }
    
    /**
     * Partner management page
     */
    public function partner_management_page() {
        $partner_admin = Environmental_Partner_Admin::get_instance();
        $partner_admin->display_page();
    }
    
    /**
     * Analytics page
     */
    public function analytics_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Analytics & Reports', 'environmental-voucher-rewards'); ?></h1>
            
            <div class="env-analytics-tabs">
                <nav class="nav-tab-wrapper">
                    <a href="#voucher-analytics" class="nav-tab nav-tab-active"><?php _e('Voucher Analytics', 'environmental-voucher-rewards'); ?></a>
                    <a href="#reward-analytics" class="nav-tab"><?php _e('Reward Analytics', 'environmental-voucher-rewards'); ?></a>
                    <a href="#loyalty-analytics" class="nav-tab"><?php _e('Loyalty Analytics', 'environmental-voucher-rewards'); ?></a>
                    <a href="#partner-analytics" class="nav-tab"><?php _e('Partner Analytics', 'environmental-voucher-rewards'); ?></a>
                </nav>
                
                <div id="voucher-analytics" class="tab-content active">
                    <?php $this->display_voucher_analytics(); ?>
                </div>
                
                <div id="reward-analytics" class="tab-content">
                    <?php $this->display_reward_analytics(); ?>
                </div>
                
                <div id="loyalty-analytics" class="tab-content">
                    <?php $this->display_loyalty_analytics(); ?>
                </div>
                
                <div id="partner-analytics" class="tab-content">
                    <?php $this->display_partner_analytics(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Environmental Rewards Settings', 'environmental-voucher-rewards'); ?></h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('env_settings_group'); ?>
                
                <div class="env-settings-tabs">
                    <nav class="nav-tab-wrapper">
                        <a href="#general-settings" class="nav-tab nav-tab-active"><?php _e('General', 'environmental-voucher-rewards'); ?></a>
                        <a href="#voucher-settings" class="nav-tab"><?php _e('Vouchers', 'environmental-voucher-rewards'); ?></a>
                        <a href="#reward-settings" class="nav-tab"><?php _e('Rewards', 'environmental-voucher-rewards'); ?></a>
                        <a href="#loyalty-settings" class="nav-tab"><?php _e('Loyalty', 'environmental-voucher-rewards'); ?></a>
                        <a href="#partner-settings" class="nav-tab"><?php _e('Partners', 'environmental-voucher-rewards'); ?></a>
                        <a href="#notification-settings" class="nav-tab"><?php _e('Notifications', 'environmental-voucher-rewards'); ?></a>
                    </nav>
                    
                    <div id="general-settings" class="tab-content active">
                        <?php $this->display_general_settings(); ?>
                    </div>
                    
                    <div id="voucher-settings" class="tab-content">
                        <?php $this->display_voucher_settings(); ?>
                    </div>
                    
                    <div id="reward-settings" class="tab-content">
                        <?php $this->display_reward_settings(); ?>
                    </div>
                    
                    <div id="loyalty-settings" class="tab-content">
                        <?php $this->display_loyalty_settings(); ?>
                    </div>
                    
                    <div id="partner-settings" class="tab-content">
                        <?php $this->display_partner_settings(); ?>
                    </div>
                    
                    <div id="notification-settings" class="tab-content">
                        <?php $this->display_notification_settings(); ?>
                    </div>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Display admin notices
     */
    public function admin_notices() {
        // Check if plugin is properly configured
        if (!$this->is_plugin_configured()) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <?php _e('Environmental Voucher & Rewards plugin needs configuration. Please visit the', 'environmental-voucher-rewards'); ?>
                    <a href="<?php echo admin_url('admin.php?page=' . self::MENU_SLUG . '-settings'); ?>">
                        <?php _e('settings page', 'environmental-voucher-rewards'); ?>
                    </a>
                    <?php _e('to complete setup.', 'environmental-voucher-rewards'); ?>
                </p>
            </div>
            <?php
        }
        
        // Show success messages
        if (isset($_GET['message'])) {
            $message = sanitize_text_field($_GET['message']);
            switch ($message) {
                case 'saved':
                    ?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php _e('Settings saved successfully!', 'environmental-voucher-rewards'); ?></p>
                    </div>
                    <?php
                    break;
                case 'created':
                    ?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php _e('Item created successfully!', 'environmental-voucher-rewards'); ?></p>
                    </div>
                    <?php
                    break;
                case 'updated':
                    ?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php _e('Item updated successfully!', 'environmental-voucher-rewards'); ?></p>
                    </div>
                    <?php
                    break;
                case 'deleted':
                    ?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php _e('Item deleted successfully!', 'environmental-voucher-rewards'); ?></p>
                    </div>
                    <?php
                    break;
            }
        }
    }
    
    /**
     * Check plugin dependencies
     */
    private function check_dependencies() {
        $missing_dependencies = array();
        
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            $missing_dependencies[] = 'WooCommerce';
        }
        
        // Check if Environmental Platform is active
        if (!function_exists('environmental_platform_init')) {
            $missing_dependencies[] = 'Environmental Platform';
        }
        
        if (!empty($missing_dependencies)) {
            add_action('admin_notices', function() use ($missing_dependencies) {
                ?>
                <div class="notice notice-error">
                    <p>
                        <?php _e('Environmental Voucher & Rewards requires the following plugins to be active:', 'environmental-voucher-rewards'); ?>
                        <strong><?php echo implode(', ', $missing_dependencies); ?></strong>
                    </p>
                </div>
                <?php
            });
        }
    }
    
    /**
     * Get dashboard statistics
     */
    private function get_dashboard_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Total vouchers
        $stats['total_vouchers'] = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}environmental_vouchers
        ");
        
        // Active campaigns
        $stats['active_campaigns'] = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}environmental_voucher_campaigns
            WHERE status = 'active' AND (end_date IS NULL OR end_date > NOW())
        ");
        
        // Total reward points distributed
        $stats['total_rewards'] = $wpdb->get_var("
            SELECT SUM(points_amount) FROM {$wpdb->prefix}environmental_reward_transactions
        ") ?: 0;
        
        // Active users (users with activity in last 30 days)
        $stats['active_users'] = $wpdb->get_var("
            SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}environmental_reward_transactions
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        // Recent activity
        $stats['recent_activity'] = $wpdb->get_results("
            SELECT 
                CONCAT(u.display_name, ' earned ', rt.points_amount, ' points for ', rt.transaction_type) as description,
                DATE_FORMAT(rt.created_at, '%M %d, %Y at %h:%i %p') as time
            FROM {$wpdb->prefix}environmental_reward_transactions rt
            JOIN {$wpdb->prefix}users u ON rt.user_id = u.ID
            ORDER BY rt.created_at DESC
            LIMIT 10
        ");
        
        return $stats;
    }
    
    /**
     * Display system status
     */
    private function display_system_status() {
        $status_items = array(
            'Database Tables' => $this->check_database_tables(),
            'WooCommerce Integration' => class_exists('WooCommerce'),
            'Cron Jobs' => wp_next_scheduled('env_daily_reward_distribution'),
            'File Permissions' => is_writable(wp_upload_dir()['path']),
            'Memory Limit' => $this->check_memory_limit()
        );
        
        foreach ($status_items as $item => $status) {
            $status_class = $status ? 'status-ok' : 'status-error';
            $status_text = $status ? __('OK', 'environmental-voucher-rewards') : __('Error', 'environmental-voucher-rewards');
            
            echo '<div class="status-item ' . $status_class . '">';
            echo '<span class="status-label">' . esc_html($item) . ':</span>';
            echo '<span class="status-value">' . esc_html($status_text) . '</span>';
            echo '</div>';
        }
    }
    
    /**
     * Helper methods for settings display
     */
    private function display_general_settings() {
        $settings = get_option('env_general_settings', array());
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Enable Plugin', 'environmental-voucher-rewards'); ?></th>
                <td>
                    <input type="checkbox" name="env_general_settings[enabled]" value="1" 
                           <?php checked(1, $settings['enabled'] ?? 1); ?> />
                    <p class="description"><?php _e('Enable/disable the entire voucher and rewards system.', 'environmental-voucher-rewards'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Debug Mode', 'environmental-voucher-rewards'); ?></th>
                <td>
                    <input type="checkbox" name="env_general_settings[debug_mode]" value="1" 
                           <?php checked(1, $settings['debug_mode'] ?? 0); ?> />
                    <p class="description"><?php _e('Enable debug logging for troubleshooting.', 'environmental-voucher-rewards'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    private function display_voucher_settings() {
        $settings = get_option('env_voucher_settings', array());
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Default Expiry Days', 'environmental-voucher-rewards'); ?></th>
                <td>
                    <input type="number" name="env_voucher_settings[default_expiry_days]" 
                           value="<?php echo esc_attr($settings['default_expiry_days'] ?? 30); ?>" min="1" max="365" />
                    <p class="description"><?php _e('Default number of days before vouchers expire.', 'environmental-voucher-rewards'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Auto Apply Vouchers', 'environmental-voucher-rewards'); ?></th>
                <td>
                    <input type="checkbox" name="env_voucher_settings[auto_apply]" value="1" 
                           <?php checked(1, $settings['auto_apply'] ?? 1); ?> />
                    <p class="description"><?php _e('Automatically apply best available voucher at checkout.', 'environmental-voucher-rewards'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    private function display_reward_settings() {
        $settings = get_option('env_reward_settings', array());
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Quiz Completion Points', 'environmental-voucher-rewards'); ?></th>
                <td>
                    <input type="number" name="env_reward_settings[quiz_points]" 
                           value="<?php echo esc_attr($settings['quiz_points'] ?? 10); ?>" min="0" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Daily Login Points', 'environmental-voucher-rewards'); ?></th>
                <td>
                    <input type="number" name="env_reward_settings[login_points]" 
                           value="<?php echo esc_attr($settings['login_points'] ?? 5); ?>" min="0" />
                </td>
            </tr>
        </table>
        <?php
    }
    
    // Additional helper methods would continue here...
    
    private function display_loyalty_settings() {
        // Loyalty settings implementation
    }
    
    private function display_partner_settings() {
        // Partner settings implementation
    }
    
    private function display_notification_settings() {
        // Notification settings implementation  
    }
    
    private function check_database_tables() {
        global $wpdb;
        
        $required_tables = array(
            'environmental_voucher_campaigns',
            'environmental_vouchers',
            'environmental_voucher_usage',
            'environmental_reward_programs',
            'environmental_user_rewards',
            'environmental_partner_discounts',
            'environmental_reward_transactions'
        );
        
        foreach ($required_tables as $table) {
            $table_name = $wpdb->prefix . $table;
            if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) != $table_name) {
                return false;
            }
        }
        
        return true;
    }
    
    private function check_memory_limit() {
        $memory_limit = ini_get('memory_limit');
        $memory_limit_bytes = wp_convert_hr_to_bytes($memory_limit);
        
        // Require at least 128MB
        return $memory_limit_bytes >= 134217728;
    }
    
    private function is_plugin_configured() {
        $general_settings = get_option('env_general_settings');
        return !empty($general_settings) && !empty($general_settings['enabled']);
    }
    
    // AJAX handlers for admin actions
    private function test_system_functionality() {
        $tests = array();
        
        // Test database connection
        $tests['database'] = $this->check_database_tables();
        
        // Test voucher generation
        $voucher_manager = Environmental_Voucher_Manager::get_instance();
        $tests['voucher_generation'] = method_exists($voucher_manager, 'generate_voucher_code');
        
        // Test reward engine
        $reward_engine = Environmental_Reward_Engine::get_instance();
        $tests['reward_engine'] = method_exists($reward_engine, 'process_quiz_reward');
        
        $all_passed = !in_array(false, $tests);
        
        if ($all_passed) {
            wp_send_json_success('All system tests passed!');
        } else {
            wp_send_json_error('Some system tests failed: ' . json_encode($tests));
        }
    }
    
    private function clear_plugin_cache() {
        // Clear transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_env_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_env_%'");
        
        // Clear daily analytics cache
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'env_daily_analytics_%'");
        
        wp_send_json_success('Cache cleared successfully!');
    }
    
    private function export_plugin_data() {
        // Implementation for data export
        wp_send_json_success('Export functionality coming soon!');
    }
    
    private function import_plugin_data() {
        // Implementation for data import
        wp_send_json_success('Import functionality coming soon!');
    }
}

// Initialize the Admin class
Environmental_Admin::get_instance();
