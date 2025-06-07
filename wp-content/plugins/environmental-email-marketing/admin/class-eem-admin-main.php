<?php
/**
 * Environmental Email Marketing - Main Admin Interface
 *
 * Main admin interface controller and dashboard
 *
 * @package Environmental_Email_Marketing
 * @subpackage Admin
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EEM_Admin_Main {

    /**
     * Admin pages
     *
     * @var array
     */
    private $admin_pages = array();

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_ajax_eem_dashboard_stats', array($this, 'ajax_dashboard_stats'));
        add_action('wp_ajax_eem_quick_action', array($this, 'ajax_quick_action'));
    }

    /**
     * Initialize admin
     */
    public function admin_init() {
        // Register settings
        register_setting('eem_settings', 'eem_general_settings');
        register_setting('eem_settings', 'eem_provider_settings');
        register_setting('eem_settings', 'eem_automation_settings');
        register_setting('eem_settings', 'eem_tracking_settings');
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu page
        $page_hook = add_menu_page(
            __('Email Marketing', 'environmental-email-marketing'),
            __('Email Marketing', 'environmental-email-marketing'),
            'manage_options',
            'eem-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-email-alt2',
            30
        );

        // Dashboard submenu
        add_submenu_page(
            'eem-dashboard',
            __('Dashboard', 'environmental-email-marketing'),
            __('Dashboard', 'environmental-email-marketing'),
            'manage_options',
            'eem-dashboard',
            array($this, 'dashboard_page')
        );

        // Campaigns submenu
        add_submenu_page(
            'eem-dashboard',
            __('Campaigns', 'environmental-email-marketing'),
            __('Campaigns', 'environmental-email-marketing'),
            'manage_options',
            'eem-campaigns',
            array($this, 'campaigns_page')
        );

        // Subscribers submenu
        add_submenu_page(
            'eem-dashboard',
            __('Subscribers', 'environmental-email-marketing'),
            __('Subscribers', 'environmental-email-marketing'),
            'manage_options',
            'eem-subscribers',
            array($this, 'subscribers_page')
        );

        // Templates submenu
        add_submenu_page(
            'eem-dashboard',
            __('Templates', 'environmental-email-marketing'),
            __('Templates', 'environmental-email-marketing'),
            'manage_options',
            'eem-templates',
            array($this, 'templates_page')
        );

        // Automations submenu
        add_submenu_page(
            'eem-dashboard',
            __('Automations', 'environmental-email-marketing'),
            __('Automations', 'environmental-email-marketing'),
            'manage_options',
            'eem-automations',
            array($this, 'automations_page')
        );

        // Analytics submenu
        add_submenu_page(
            'eem-dashboard',
            __('Analytics', 'environmental-email-marketing'),
            __('Analytics', 'environmental-email-marketing'),
            'manage_options',
            'eem-analytics',
            array($this, 'analytics_page')
        );

        // Settings submenu
        add_submenu_page(
            'eem-dashboard',
            __('Settings', 'environmental-email-marketing'),
            __('Settings', 'environmental-email-marketing'),
            'manage_options',
            'eem-settings',
            array($this, 'settings_page')
        );

        $this->admin_pages[$page_hook] = 'dashboard';
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'eem-') === false) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'eem-admin-css',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin.css',
            array(),
            EEM_VERSION
        );

        // JavaScript
        wp_enqueue_script(
            'eem-admin-js',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/admin.js',
            array('jquery', 'wp-util'),
            EEM_VERSION,
            true
        );

        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_style('wp-color-picker');

        // Chart.js for analytics
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js',
            array(),
            '3.9.1',
            true
        );

        // Localize script
        wp_localize_script('eem-admin-js', 'eemAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eem_admin_nonce'),
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to delete this item?', 'environmental-email-marketing'),
                'success' => __('Success!', 'environmental-email-marketing'),
                'error' => __('Error occurred!', 'environmental-email-marketing'),
                'loading' => __('Loading...', 'environmental-email-marketing'),
                'saving' => __('Saving...', 'environmental-email-marketing')
            )
        ));
    }

    /**
     * Dashboard page
     */
    public function dashboard_page() {
        include_once dirname(__FILE__) . '/views/dashboard.php';
    }

    /**
     * Campaigns page
     */
    public function campaigns_page() {
        $campaigns_admin = new EEM_Admin_Campaigns();
        $campaigns_admin->render_page();
    }

    /**
     * Subscribers page
     */
    public function subscribers_page() {
        $subscribers_admin = new EEM_Admin_Subscribers();
        $subscribers_admin->render_page();
    }

    /**
     * Templates page
     */
    public function templates_page() {
        include_once dirname(__FILE__) . '/views/templates.php';
    }

    /**
     * Automations page
     */
    public function automations_page() {
        include_once dirname(__FILE__) . '/views/automations.php';
    }

    /**
     * Analytics page
     */
    public function analytics_page() {
        $analytics_admin = new EEM_Admin_Analytics();
        $analytics_admin->render_page();
    }

    /**
     * Settings page
     */
    public function settings_page() {
        $settings_admin = new EEM_Admin_Settings();
        $settings_admin->render_page();
    }

    /**
     * AJAX handler for dashboard stats
     */
    public function ajax_dashboard_stats() {
        check_ajax_referer('eem_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $stats = $this->get_dashboard_stats();

        wp_send_json_success($stats);
    }

    /**
     * AJAX handler for quick actions
     */
    public function ajax_quick_action() {
        check_ajax_referer('eem_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $action = sanitize_text_field($_POST['quick_action']);
        $result = array();

        switch ($action) {
            case 'sync_subscribers':
                $result = $this->sync_all_subscribers();
                break;
            case 'process_queue':
                $result = $this->process_email_queue();
                break;
            case 'update_stats':
                $result = $this->update_analytics_stats();
                break;
            default:
                $result = array('success' => false, 'message' => 'Invalid action');
        }

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Get dashboard statistics
     */
    private function get_dashboard_stats() {
        global $wpdb;

        $stats = array();

        // Total subscribers
        $stats['total_subscribers'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}eem_subscribers WHERE status = 'active'"
        );

        // Total campaigns
        $stats['total_campaigns'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}eem_campaigns"
        );

        // Campaigns sent this month
        $stats['campaigns_this_month'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}eem_campaigns 
                WHERE status = 'sent' AND sent_at >= %s",
                date('Y-m-01')
            )
        );

        // Total emails sent
        $stats['total_emails_sent'] = $wpdb->get_var(
            "SELECT SUM(emails_sent) FROM {$wpdb->prefix}eem_campaigns WHERE status = 'sent'"
        ) ?: 0;

        // Average open rate
        $stats['avg_open_rate'] = $wpdb->get_var(
            "SELECT AVG(open_rate) FROM {$wpdb->prefix}eem_campaigns 
            WHERE status = 'sent' AND open_rate > 0"
        ) ?: 0;

        // Average click rate
        $stats['avg_click_rate'] = $wpdb->get_var(
            "SELECT AVG(click_rate) FROM {$wpdb->prefix}eem_campaigns 
            WHERE status = 'sent' AND click_rate > 0"
        ) ?: 0;

        // Active automations
        $stats['active_automations'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}eem_automations WHERE status = 'active'"
        );

        // Recent activity
        $stats['recent_campaigns'] = $wpdb->get_results(
            "SELECT name, status, sent_at, emails_sent, open_rate, click_rate
            FROM {$wpdb->prefix}eem_campaigns 
            ORDER BY created_at DESC LIMIT 5"
        );

        // Environmental impact
        $stats['environmental_impact'] = $this->get_environmental_impact_stats();

        // Growth stats (last 30 days)
        $stats['subscriber_growth'] = $this->get_subscriber_growth_stats();

        return $stats;
    }

    /**
     * Get environmental impact statistics
     */
    private function get_environmental_impact_stats() {
        global $wpdb;

        $total_emails = $wpdb->get_var(
            "SELECT SUM(emails_sent) FROM {$wpdb->prefix}eem_campaigns WHERE status = 'sent'"
        ) ?: 0;

        // Calculate environmental impact
        $carbon_per_email = 0.004; // kg CO2
        $total_carbon = $total_emails * $carbon_per_email;
        $trees_equivalent = $total_carbon / 21.77; // kg CO2 absorbed per tree per year

        return array(
            'total_emails' => $total_emails,
            'carbon_footprint_kg' => round($total_carbon, 2),
            'trees_equivalent' => round($trees_equivalent, 4),
            'energy_saved_actions' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}eem_analytics_events 
                WHERE event_type IN ('eco_action', 'green_purchase', 'petition_sign')"
            ) ?: 0
        );
    }

    /**
     * Get subscriber growth statistics
     */
    private function get_subscriber_growth_stats() {
        global $wpdb;

        $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));

        // New subscribers in last 30 days
        $new_subscribers = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}eem_subscribers 
                WHERE created_at >= %s",
                $thirty_days_ago
            )
        );

        // Unsubscribes in last 30 days
        $unsubscribes = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}eem_subscribers 
                WHERE status = 'unsubscribed' AND updated_at >= %s",
                $thirty_days_ago
            )
        );

        return array(
            'new_subscribers' => $new_subscribers,
            'unsubscribes' => $unsubscribes,
            'net_growth' => $new_subscribers - $unsubscribes
        );
    }

    /**
     * Sync all subscribers with email providers
     */
    private function sync_all_subscribers() {
        $subscriber_manager = new EEM_Subscriber_Manager();
        $result = $subscriber_manager->sync_all_providers();

        return array(
            'success' => true,
            'message' => sprintf(__('%d subscribers synced successfully.', 'environmental-email-marketing'), $result['synced'])
        );
    }

    /**
     * Process email queue
     */
    private function process_email_queue() {
        $campaign_manager = new EEM_Campaign_Manager();
        $result = $campaign_manager->process_queue();

        return array(
            'success' => true,
            'message' => sprintf(__('%d emails processed from queue.', 'environmental-email-marketing'), $result['processed'])
        );
    }

    /**
     * Update analytics statistics
     */
    private function update_analytics_stats() {
        $analytics_tracker = new EEM_Analytics_Tracker();
        $result = $analytics_tracker->update_campaign_stats();

        return array(
            'success' => true,
            'message' => __('Analytics statistics updated successfully.', 'environmental-email-marketing')
        );
    }
}
