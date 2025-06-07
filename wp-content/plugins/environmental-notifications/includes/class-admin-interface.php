<?php
/**
 * Admin Interface Class
 * 
 * Handles the administrative interface for the notification and messaging system
 * including settings pages, analytics dashboard, and notification management.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Admin_Interface {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'));
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('Notifications', 'environmental-notifications'),
            __('Notifications', 'environmental-notifications'),
            'manage_options',
            'environmental-notifications',
            array($this, 'render_main_page'),
            'dashicons-bell',
            30
        );
        
        // Analytics submenu
        add_submenu_page(
            'environmental-notifications',
            __('Analytics', 'environmental-notifications'),
            __('Analytics', 'environmental-notifications'),
            'manage_options',
            'environmental-notifications-analytics',
            array($this, 'render_analytics_page')
        );
        
        // Templates submenu
        add_submenu_page(
            'environmental-notifications',
            __('Templates', 'environmental-notifications'),
            __('Templates', 'environmental-notifications'),
            'manage_options',
            'environmental-notifications-templates',
            array($this, 'render_templates_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'environmental-notifications',
            __('Settings', 'environmental-notifications'),
            __('Settings', 'environmental-notifications'),
            'manage_options',
            'environmental-notifications-settings',
            array($this, 'render_settings_page')
        );
        
        // Messages submenu
        add_submenu_page(
            'environmental-notifications',
            __('Messages', 'environmental-notifications'),
            __('Messages', 'environmental-notifications'),
            'manage_options',
            'environmental-notifications-messages',
            array($this, 'render_messages_page')
        );
    }
    
    /**
     * Register admin settings
     */
    public function register_settings() {
        // General settings
        register_setting('environmental_notifications_general', 'en_enable_push_notifications');
        register_setting('environmental_notifications_general', 'en_enable_email_notifications');
        register_setting('environmental_notifications_general', 'en_enable_real_time');
        register_setting('environmental_notifications_general', 'en_cleanup_days');
        register_setting('environmental_notifications_general', 'en_rate_limit_messages');
        
        // Push notification settings
        register_setting('environmental_notifications_push', 'en_vapid_public_key');
        register_setting('environmental_notifications_push', 'en_vapid_private_key');
        register_setting('environmental_notifications_push', 'en_vapid_email');
        
        // Email settings
        register_setting('environmental_notifications_email', 'en_email_from_name');
        register_setting('environmental_notifications_email', 'en_email_from_address');
        register_setting('environmental_notifications_email', 'en_default_digest_frequency');
        
        // Real-time settings
        register_setting('environmental_notifications_realtime', 'en_sse_heartbeat_interval');
        register_setting('environmental_notifications_realtime', 'en_connection_timeout');
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'environmental-notifications') === false) {
            return;
        }
        
        wp_enqueue_script(
            'environmental-notifications-admin',
            EN_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-api', 'chart-js'),
            EN_VERSION,
            true
        );
        
        wp_enqueue_style(
            'environmental-notifications-admin',
            EN_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            EN_VERSION
        );
        
        // Chart.js for analytics
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js',
            array(),
            '3.9.1',
            true
        );
        
        wp_localize_script('environmental-notifications-admin', 'enAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('environmental_notifications_admin'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this?', 'environmental-notifications'),
                'loading' => __('Loading...', 'environmental-notifications'),
                'error' => __('An error occurred. Please try again.', 'environmental-notifications'),
                'success' => __('Operation completed successfully.', 'environmental-notifications')
            )
        ));
    }
    
    /**
     * Add dashboard widgets
     */
    public function add_dashboard_widgets() {
        wp_add_dashboard_widget(
            'environmental_notifications_overview',
            __('Notifications Overview', 'environmental-notifications'),
            array($this, 'render_dashboard_widget')
        );
    }
    
    /**
     * Render main admin page
     */
    public function render_main_page() {
        $analytics = Environmental_Notification_Analytics::get_instance();
        $overview_data = $analytics->get_dashboard_analytics(30);
        ?>
        <div class="wrap">
            <h1><?php _e('Environmental Notifications Dashboard', 'environmental-notifications'); ?></h1>
            
            <div class="en-admin-overview">
                <div class="en-stats-grid">
                    <div class="en-stat-card">
                        <div class="en-stat-number"><?php echo esc_html($overview_data['overview']['total_notifications']); ?></div>
                        <div class="en-stat-label"><?php _e('Notifications Sent (30 days)', 'environmental-notifications'); ?></div>
                    </div>
                    
                    <div class="en-stat-card">
                        <div class="en-stat-number"><?php echo esc_html($overview_data['overview']['total_messages']); ?></div>
                        <div class="en-stat-label"><?php _e('Messages Sent (30 days)', 'environmental-notifications'); ?></div>
                    </div>
                    
                    <div class="en-stat-card">
                        <div class="en-stat-number"><?php echo esc_html($overview_data['overview']['delivery_rate']); ?>%</div>
                        <div class="en-stat-label"><?php _e('Delivery Rate', 'environmental-notifications'); ?></div>
                    </div>
                    
                    <div class="en-stat-card">
                        <div class="en-stat-number"><?php echo esc_html($overview_data['overview']['engagement_rate']); ?>%</div>
                        <div class="en-stat-label"><?php _e('Engagement Rate', 'environmental-notifications'); ?></div>
                    </div>
                    
                    <div class="en-stat-card">
                        <div class="en-stat-number"><?php echo esc_html($overview_data['overview']['active_users']); ?></div>
                        <div class="en-stat-label"><?php _e('Active Users', 'environmental-notifications'); ?></div>
                    </div>
                    
                    <div class="en-stat-card">
                        <?php
                        $realtime = Environmental_Realtime_Handler::get_instance();
                        $active_connections = $realtime->get_active_connections_count();
                        ?>
                        <div class="en-stat-number"><?php echo esc_html($active_connections); ?></div>
                        <div class="en-stat-label"><?php _e('Active Real-time Connections', 'environmental-notifications'); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="en-admin-actions">
                <h2><?php _e('Quick Actions', 'environmental-notifications'); ?></h2>
                <div class="en-action-buttons">
                    <a href="<?php echo admin_url('admin.php?page=environmental-notifications-analytics'); ?>" class="button button-primary">
                        <?php _e('View Detailed Analytics', 'environmental-notifications'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=environmental-notifications-settings'); ?>" class="button">
                        <?php _e('Configure Settings', 'environmental-notifications'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=environmental-notifications-templates'); ?>" class="button">
                        <?php _e('Manage Templates', 'environmental-notifications'); ?>
                    </a>
                    <button type="button" class="button" id="en-send-test-notification">
                        <?php _e('Send Test Notification', 'environmental-notifications'); ?>
                    </button>
                </div>
            </div>
            
            <div class="en-admin-recent">
                <h2><?php _e('Recent Activity', 'environmental-notifications'); ?></h2>
                <div id="en-recent-notifications">
                    <?php $this->render_recent_notifications(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render analytics page
     */
    public function render_analytics_page() {
        $date_range = intval($_GET['date_range'] ?? 30);
        $analytics = Environmental_Notification_Analytics::get_instance();
        $analytics_data = $analytics->get_dashboard_analytics($date_range);
        ?>
        <div class="wrap">
            <h1><?php _e('Notification Analytics', 'environmental-notifications'); ?></h1>
            
            <div class="en-analytics-filters">
                <select id="en-date-range" onchange="location.href='<?php echo admin_url('admin.php?page=environmental-notifications-analytics&date_range='); ?>' + this.value">
                    <option value="7" <?php selected($date_range, 7); ?>><?php _e('Last 7 days', 'environmental-notifications'); ?></option>
                    <option value="30" <?php selected($date_range, 30); ?>><?php _e('Last 30 days', 'environmental-notifications'); ?></option>
                    <option value="90" <?php selected($date_range, 90); ?>><?php _e('Last 90 days', 'environmental-notifications'); ?></option>
                    <option value="365" <?php selected($date_range, 365); ?>><?php _e('Last year', 'environmental-notifications'); ?></option>
                </select>
                
                <div class="en-export-buttons">
                    <a href="<?php echo admin_url('admin-ajax.php?action=en_export_analytics&date_range=' . $date_range . '&format=csv&_wpnonce=' . wp_create_nonce('en_export')); ?>" class="button">
                        <?php _e('Export CSV', 'environmental-notifications'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin-ajax.php?action=en_export_analytics&date_range=' . $date_range . '&format=json&_wpnonce=' . wp_create_nonce('en_export')); ?>" class="button">
                        <?php _e('Export JSON', 'environmental-notifications'); ?>
                    </a>
                </div>
            </div>
            
            <div class="en-analytics-dashboard">
                <!-- Daily Trends Chart -->
                <div class="en-chart-container">
                    <h3><?php _e('Daily Trends', 'environmental-notifications'); ?></h3>
                    <canvas id="en-daily-trends-chart"></canvas>
                </div>
                
                <!-- Notification Type Breakdown -->
                <div class="en-chart-container">
                    <h3><?php _e('Notification Types', 'environmental-notifications'); ?></h3>
                    <canvas id="en-type-breakdown-chart"></canvas>
                </div>
                
                <!-- Device Analytics -->
                <div class="en-chart-container">
                    <h3><?php _e('Device Usage', 'environmental-notifications'); ?></h3>
                    <canvas id="en-device-analytics-chart"></canvas>
                </div>
                
                <!-- Engagement Patterns -->
                <div class="en-chart-container">
                    <h3><?php _e('Hourly Engagement Patterns', 'environmental-notifications'); ?></h3>
                    <canvas id="en-engagement-patterns-chart"></canvas>
                </div>
            </div>
            
            <!-- Top Performing Notifications Table -->
            <div class="en-top-notifications">
                <h3><?php _e('Top Performing Notifications', 'environmental-notifications'); ?></h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Title', 'environmental-notifications'); ?></th>
                            <th><?php _e('Type', 'environmental-notifications'); ?></th>
                            <th><?php _e('Delivered', 'environmental-notifications'); ?></th>
                            <th><?php _e('Read', 'environmental-notifications'); ?></th>
                            <th><?php _e('Clicked', 'environmental-notifications'); ?></th>
                            <th><?php _e('Read Rate', 'environmental-notifications'); ?></th>
                            <th><?php _e('Date', 'environmental-notifications'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analytics_data['top_notifications'] as $notification): ?>
                        <tr>
                            <td><?php echo esc_html($notification->title); ?></td>
                            <td><?php echo esc_html($notification->type); ?></td>
                            <td><?php echo esc_html($notification->delivered); ?></td>
                            <td><?php echo esc_html($notification->reads); ?></td>
                            <td><?php echo esc_html($notification->clicks); ?></td>
                            <td><?php echo esc_html($notification->read_rate); ?>%</td>
                            <td><?php echo esc_html(date('M j, Y', strtotime($notification->created_at))); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <script>
                // Pass analytics data to JavaScript
                window.enAnalyticsData = <?php echo wp_json_encode($analytics_data); ?>;
            </script>
        </div>
        <?php
    }
    
    /**
     * Render templates page
     */
    public function render_templates_page() {
        $templates = Environmental_Notification_Templates::get_instance();
        $all_templates = $templates->get_all_templates();
        ?>
        <div class="wrap">
            <h1><?php _e('Notification Templates', 'environmental-notifications'); ?></h1>
            
            <div class="en-templates-toolbar">
                <button type="button" class="button button-primary" id="en-add-template">
                    <?php _e('Add New Template', 'environmental-notifications'); ?>
                </button>
            </div>
            
            <div class="en-templates-list">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'environmental-notifications'); ?></th>
                            <th><?php _e('Type', 'environmental-notifications'); ?></th>
                            <th><?php _e('Category', 'environmental-notifications'); ?></th>
                            <th><?php _e('Last Updated', 'environmental-notifications'); ?></th>
                            <th><?php _e('Actions', 'environmental-notifications'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_templates as $template): ?>
                        <tr>
                            <td><?php echo esc_html($template->name); ?></td>
                            <td><?php echo esc_html($template->type); ?></td>
                            <td><?php echo esc_html($template->category); ?></td>
                            <td><?php echo esc_html(date('M j, Y g:i A', strtotime($template->updated_at))); ?></td>
                            <td>
                                <button type="button" class="button en-edit-template" data-template-id="<?php echo esc_attr($template->template_id); ?>">
                                    <?php _e('Edit', 'environmental-notifications'); ?>
                                </button>
                                <button type="button" class="button en-preview-template" data-template-id="<?php echo esc_attr($template->template_id); ?>">
                                    <?php _e('Preview', 'environmental-notifications'); ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Template Editor Modal -->
            <div id="en-template-modal" class="en-modal" style="display: none;">
                <div class="en-modal-content">
                    <div class="en-modal-header">
                        <h3 id="en-modal-title"><?php _e('Edit Template', 'environmental-notifications'); ?></h3>
                        <button type="button" class="en-modal-close">&times;</button>
                    </div>
                    <div class="en-modal-body">
                        <form id="en-template-form">
                            <table class="form-table">
                                <tr>
                                    <th><label for="template-name"><?php _e('Template Name', 'environmental-notifications'); ?></label></th>
                                    <td><input type="text" id="template-name" name="name" class="regular-text" required></td>
                                </tr>
                                <tr>
                                    <th><label for="template-type"><?php _e('Type', 'environmental-notifications'); ?></label></th>
                                    <td>
                                        <select id="template-type" name="type">
                                            <option value="notification"><?php _e('Notification', 'environmental-notifications'); ?></option>
                                            <option value="digest"><?php _e('Digest', 'environmental-notifications'); ?></option>
                                            <option value="system"><?php _e('System', 'environmental-notifications'); ?></option>
                                            <option value="message"><?php _e('Message', 'environmental-notifications'); ?></option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="template-category"><?php _e('Category', 'environmental-notifications'); ?></label></th>
                                    <td><input type="text" id="template-category" name="category" class="regular-text" required></td>
                                </tr>
                                <tr>
                                    <th><label for="email-subject"><?php _e('Email Subject', 'environmental-notifications'); ?></label></th>
                                    <td><input type="text" id="email-subject" name="email_subject" class="large-text"></td>
                                </tr>
                                <tr>
                                    <th><label for="email-template"><?php _e('Email Template', 'environmental-notifications'); ?></label></th>
                                    <td><textarea id="email-template" name="email_template" rows="10" class="large-text"></textarea></td>
                                </tr>
                                <tr>
                                    <th><label for="push-template"><?php _e('Push Template', 'environmental-notifications'); ?></label></th>
                                    <td><textarea id="push-template" name="push_template" rows="5" class="large-text"></textarea></td>
                                </tr>
                            </table>
                        </form>
                    </div>
                    <div class="en-modal-footer">
                        <button type="button" class="button button-primary" id="en-save-template">
                            <?php _e('Save Template', 'environmental-notifications'); ?>
                        </button>
                        <button type="button" class="button en-modal-close">
                            <?php _e('Cancel', 'environmental-notifications'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Notification Settings', 'environmental-notifications'); ?></h1>
            
            <div class="nav-tab-wrapper">
                <a href="#general" class="nav-tab nav-tab-active"><?php _e('General', 'environmental-notifications'); ?></a>
                <a href="#push" class="nav-tab"><?php _e('Push Notifications', 'environmental-notifications'); ?></a>
                <a href="#email" class="nav-tab"><?php _e('Email', 'environmental-notifications'); ?></a>
                <a href="#realtime" class="nav-tab"><?php _e('Real-time', 'environmental-notifications'); ?></a>
            </div>
            
            <form method="post" action="options.php">
                <!-- General Settings Tab -->
                <div id="general" class="en-tab-content">
                    <?php settings_fields('environmental_notifications_general'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Enable Push Notifications', 'environmental-notifications'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="en_enable_push_notifications" value="1" <?php checked(get_option('en_enable_push_notifications', 1)); ?>>
                                    <?php _e('Allow users to receive push notifications', 'environmental-notifications'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Enable Email Notifications', 'environmental-notifications'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="en_enable_email_notifications" value="1" <?php checked(get_option('en_enable_email_notifications', 1)); ?>>
                                    <?php _e('Allow users to receive email notifications', 'environmental-notifications'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Enable Real-time Notifications', 'environmental-notifications'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="en_enable_real_time" value="1" <?php checked(get_option('en_enable_real_time', 1)); ?>>
                                    <?php _e('Enable real-time notification delivery', 'environmental-notifications'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Cleanup Old Notifications', 'environmental-notifications'); ?></th>
                            <td>
                                <input type="number" name="en_cleanup_days" value="<?php echo esc_attr(get_option('en_cleanup_days', 90)); ?>" min="1" max="365">
                                <?php _e('days (automatically delete notifications older than this)', 'environmental-notifications'); ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Message Rate Limit', 'environmental-notifications'); ?></th>
                            <td>
                                <input type="number" name="en_rate_limit_messages" value="<?php echo esc_attr(get_option('en_rate_limit_messages', 50)); ?>" min="1" max="1000">
                                <?php _e('messages per hour per user', 'environmental-notifications'); ?>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Push Notifications Settings Tab -->
                <div id="push" class="en-tab-content" style="display: none;">
                    <?php settings_fields('environmental_notifications_push'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('VAPID Public Key', 'environmental-notifications'); ?></th>
                            <td>
                                <textarea name="en_vapid_public_key" rows="3" class="large-text"><?php echo esc_textarea(get_option('en_vapid_public_key')); ?></textarea>
                                <p class="description"><?php _e('VAPID public key for push notifications', 'environmental-notifications'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('VAPID Private Key', 'environmental-notifications'); ?></th>
                            <td>
                                <textarea name="en_vapid_private_key" rows="3" class="large-text"><?php echo esc_textarea(get_option('en_vapid_private_key')); ?></textarea>
                                <p class="description"><?php _e('VAPID private key for push notifications', 'environmental-notifications'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('VAPID Email', 'environmental-notifications'); ?></th>
                            <td>
                                <input type="email" name="en_vapid_email" value="<?php echo esc_attr(get_option('en_vapid_email')); ?>" class="regular-text">
                                <p class="description"><?php _e('Contact email for VAPID (mailto: or https: URL)', 'environmental-notifications'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"></th>
                            <td>
                                <button type="button" class="button" id="en-generate-vapid-keys">
                                    <?php _e('Generate New VAPID Keys', 'environmental-notifications'); ?>
                                </button>
                                <p class="description"><?php _e('Warning: Generating new keys will invalidate all existing push subscriptions', 'environmental-notifications'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Email Settings Tab -->
                <div id="email" class="en-tab-content" style="display: none;">
                    <?php settings_fields('environmental_notifications_email'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('From Name', 'environmental-notifications'); ?></th>
                            <td>
                                <input type="text" name="en_email_from_name" value="<?php echo esc_attr(get_option('en_email_from_name', get_bloginfo('name'))); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('From Email Address', 'environmental-notifications'); ?></th>
                            <td>
                                <input type="email" name="en_email_from_address" value="<?php echo esc_attr(get_option('en_email_from_address', get_option('admin_email'))); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Default Digest Frequency', 'environmental-notifications'); ?></th>
                            <td>
                                <select name="en_default_digest_frequency">
                                    <option value="immediate" <?php selected(get_option('en_default_digest_frequency', 'daily'), 'immediate'); ?>><?php _e('Immediate', 'environmental-notifications'); ?></option>
                                    <option value="hourly" <?php selected(get_option('en_default_digest_frequency', 'daily'), 'hourly'); ?>><?php _e('Hourly', 'environmental-notifications'); ?></option>
                                    <option value="daily" <?php selected(get_option('en_default_digest_frequency', 'daily'), 'daily'); ?>><?php _e('Daily', 'environmental-notifications'); ?></option>
                                    <option value="weekly" <?php selected(get_option('en_default_digest_frequency', 'daily'), 'weekly'); ?>><?php _e('Weekly', 'environmental-notifications'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Real-time Settings Tab -->
                <div id="realtime" class="en-tab-content" style="display: none;">
                    <?php settings_fields('environmental_notifications_realtime'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('SSE Heartbeat Interval', 'environmental-notifications'); ?></th>
                            <td>
                                <input type="number" name="en_sse_heartbeat_interval" value="<?php echo esc_attr(get_option('en_sse_heartbeat_interval', 30)); ?>" min="5" max="300">
                                <?php _e('seconds', 'environmental-notifications'); ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Connection Timeout', 'environmental-notifications'); ?></th>
                            <td>
                                <input type="number" name="en_connection_timeout" value="<?php echo esc_attr(get_option('en_connection_timeout', 300)); ?>" min="60" max="3600">
                                <?php _e('seconds', 'environmental-notifications'); ?>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render messages page
     */
    public function render_messages_page() {
        global $wpdb;
        
        $messages_table = $wpdb->prefix . 'en_messages';
        $page = intval($_GET['paged'] ?? 1);
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        $total_messages = $wpdb->get_var("SELECT COUNT(*) FROM {$messages_table} WHERE deleted_at IS NULL");
        $messages = $wpdb->get_results($wpdb->prepare("
            SELECT m.*, 
                   u1.display_name as sender_name,
                   u2.display_name as recipient_name
            FROM {$messages_table} m
            LEFT JOIN {$wpdb->users} u1 ON m.sender_id = u1.ID
            LEFT JOIN {$wpdb->users} u2 ON m.recipient_id = u2.ID
            WHERE m.deleted_at IS NULL
            ORDER BY m.created_at DESC
            LIMIT %d OFFSET %d
        ", $per_page, $offset));
        
        $total_pages = ceil($total_messages / $per_page);
        ?>
        <div class="wrap">
            <h1><?php _e('Messages Management', 'environmental-notifications'); ?></h1>
            
            <div class="en-messages-stats">
                <div class="en-stat-card">
                    <div class="en-stat-number"><?php echo esc_html($total_messages); ?></div>
                    <div class="en-stat-label"><?php _e('Total Messages', 'environmental-notifications'); ?></div>
                </div>
                
                <?php
                $recent_messages = $wpdb->get_var("
                    SELECT COUNT(*) FROM {$messages_table} 
                    WHERE deleted_at IS NULL 
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ");
                ?>
                <div class="en-stat-card">
                    <div class="en-stat-number"><?php echo esc_html($recent_messages); ?></div>
                    <div class="en-stat-label"><?php _e('Messages Today', 'environmental-notifications'); ?></div>
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('From', 'environmental-notifications'); ?></th>
                        <th><?php _e('To', 'environmental-notifications'); ?></th>
                        <th><?php _e('Message', 'environmental-notifications'); ?></th>
                        <th><?php _e('Date', 'environmental-notifications'); ?></th>
                        <th><?php _e('Status', 'environmental-notifications'); ?></th>
                        <th><?php _e('Actions', 'environmental-notifications'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $message): ?>
                    <tr>
                        <td><?php echo esc_html($message->sender_name); ?></td>
                        <td><?php echo esc_html($message->recipient_name); ?></td>
                        <td><?php echo esc_html(wp_trim_words($message->message, 10)); ?></td>
                        <td><?php echo esc_html(date('M j, Y g:i A', strtotime($message->created_at))); ?></td>
                        <td>
                            <?php if ($message->read_at): ?>
                                <span class="en-status-read"><?php _e('Read', 'environmental-notifications'); ?></span>
                            <?php else: ?>
                                <span class="en-status-unread"><?php _e('Unread', 'environmental-notifications'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="button en-view-message" data-message-id="<?php echo esc_attr($message->id); ?>">
                                <?php _e('View', 'environmental-notifications'); ?>
                            </button>
                            <button type="button" class="button en-delete-message" data-message-id="<?php echo esc_attr($message->id); ?>">
                                <?php _e('Delete', 'environmental-notifications'); ?>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if ($total_pages > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $total_pages,
                        'current' => $page
                    ));
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render dashboard widget
     */
    public function render_dashboard_widget() {
        $analytics = Environmental_Notification_Analytics::get_instance();
        $overview_data = $analytics->get_dashboard_analytics(7);
        ?>
        <div class="en-dashboard-widget">
            <div class="en-widget-stats">
                <div class="en-widget-stat">
                    <span class="en-stat-value"><?php echo esc_html($overview_data['overview']['total_notifications']); ?></span>
                    <span class="en-stat-label"><?php _e('Notifications (7d)', 'environmental-notifications'); ?></span>
                </div>
                <div class="en-widget-stat">
                    <span class="en-stat-value"><?php echo esc_html($overview_data['overview']['delivery_rate']); ?>%</span>
                    <span class="en-stat-label"><?php _e('Delivery Rate', 'environmental-notifications'); ?></span>
                </div>
                <div class="en-widget-stat">
                    <span class="en-stat-value"><?php echo esc_html($overview_data['overview']['active_users']); ?></span>
                    <span class="en-stat-label"><?php _e('Active Users', 'environmental-notifications'); ?></span>
                </div>
            </div>
            <div class="en-widget-actions">
                <a href="<?php echo admin_url('admin.php?page=environmental-notifications'); ?>" class="button button-small">
                    <?php _e('View Dashboard', 'environmental-notifications'); ?>
                </a>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render recent notifications
     */
    private function render_recent_notifications() {
        global $wpdb;
        
        $notifications_table = $wpdb->prefix . 'en_notifications';
        $recent_notifications = $wpdb->get_results("
            SELECT * FROM {$notifications_table}
            WHERE status = 'active'
            ORDER BY created_at DESC
            LIMIT 10
        ");
        
        if (empty($recent_notifications)) {
            echo '<p>' . __('No recent notifications found.', 'environmental-notifications') . '</p>';
            return;
        }
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Title', 'environmental-notifications'); ?></th>
                    <th><?php _e('Type', 'environmental-notifications'); ?></th>
                    <th><?php _e('Priority', 'environmental-notifications'); ?></th>
                    <th><?php _e('Created', 'environmental-notifications'); ?></th>
                    <th><?php _e('Status', 'environmental-notifications'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_notifications as $notification): ?>
                <tr>
                    <td><?php echo esc_html($notification->title); ?></td>
                    <td><?php echo esc_html($notification->type); ?></td>
                    <td>
                        <span class="en-priority en-priority-<?php echo esc_attr($notification->priority); ?>">
                            <?php echo esc_html(ucfirst($notification->priority)); ?>
                        </span>
                    </td>
                    <td><?php echo esc_html(human_time_diff(strtotime($notification->created_at))); ?> <?php _e('ago', 'environmental-notifications'); ?></td>
                    <td><?php echo esc_html(ucfirst($notification->status)); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
}
