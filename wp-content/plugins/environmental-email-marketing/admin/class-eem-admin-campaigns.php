<?php
/**
 * Campaign Management Admin Interface
 *
 * Handles the admin interface for managing email campaigns, including
 * creation, editing, scheduling, and campaign analytics.
 *
 * @package Environmental_Email_Marketing
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EEM_Admin_Campaigns {
    
    /**
     * Campaign manager instance
     */
    private $campaign_manager;
    
    /**
     * Template engine instance
     */
    private $template_engine;
    
    /**
     * Analytics tracker instance
     */
    private $analytics_tracker;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->campaign_manager = new EEM_Campaign_Manager();
        $this->template_engine = new EEM_Template_Engine();
        $this->analytics_tracker = new EEM_Analytics_Tracker();
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_eem_create_campaign', array($this, 'ajax_create_campaign'));
        add_action('wp_ajax_eem_update_campaign', array($this, 'ajax_update_campaign'));
        add_action('wp_ajax_eem_delete_campaign', array($this, 'ajax_delete_campaign'));
        add_action('wp_ajax_eem_schedule_campaign', array($this, 'ajax_schedule_campaign'));
        add_action('wp_ajax_eem_send_test_email', array($this, 'ajax_send_test_email'));
        add_action('wp_ajax_eem_preview_campaign', array($this, 'ajax_preview_campaign'));
        add_action('wp_ajax_eem_duplicate_campaign', array($this, 'ajax_duplicate_campaign'));
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        add_menu_page(
            'Environmental Email Marketing',
            'Email Marketing',
            'manage_options',
            'eem-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-email-alt',
            30
        );
        
        add_submenu_page(
            'eem-dashboard',
            'Campaigns',
            'Campaigns',
            'manage_options',
            'eem-campaigns',
            array($this, 'campaigns_page')
        );
        
        add_submenu_page(
            'eem-dashboard',
            'Create Campaign',
            'Create Campaign',
            'manage_options',
            'eem-create-campaign',
            array($this, 'create_campaign_page')
        );
        
        add_submenu_page(
            null, // Hidden from menu
            'Edit Campaign',
            'Edit Campaign',
            'manage_options',
            'eem-edit-campaign',
            array($this, 'edit_campaign_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'eem-') === false) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('jquery-ui-sortable');
        
        wp_enqueue_script(
            'eem-admin-campaigns',
            plugin_dir_url(__FILE__) . '../assets/js/admin-campaigns.js',
            array('jquery', 'wp-color-picker'),
            '1.0.0',
            true
        );
        
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style('jquery-ui-datepicker');
        
        wp_enqueue_style(
            'eem-admin-campaigns',
            plugin_dir_url(__FILE__) . '../assets/css/admin-campaigns.css',
            array(),
            '1.0.0'
        );
        
        wp_localize_script('eem-admin-campaigns', 'eemAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eem_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this campaign?', 'environmental-email-marketing'),
                'saving' => __('Saving...', 'environmental-email-marketing'),
                'saved' => __('Saved!', 'environmental-email-marketing'),
                'error' => __('Error occurred. Please try again.', 'environmental-email-marketing'),
                'test_email_sent' => __('Test email sent successfully!', 'environmental-email-marketing'),
                'campaign_scheduled' => __('Campaign scheduled successfully!', 'environmental-email-marketing')
            )
        ));
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        $stats = $this->analytics_tracker->get_dashboard_stats();
        $recent_campaigns = $this->campaign_manager->get_recent_campaigns(5);
        $upcoming_campaigns = $this->campaign_manager->get_upcoming_campaigns(5);
        
        include plugin_dir_path(__FILE__) . 'views/dashboard.php';
    }
    
    /**
     * Campaigns list page
     */
    public function campaigns_page() {
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'all';
        $page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        $per_page = 20;
        
        $campaigns = $this->campaign_manager->get_campaigns(array(
            'status' => $current_tab === 'all' ? '' : $current_tab,
            'page' => $page,
            'per_page' => $per_page
        ));
        
        $total_campaigns = $this->campaign_manager->count_campaigns($current_tab === 'all' ? '' : $current_tab);
        $total_pages = ceil($total_campaigns / $per_page);
        
        include plugin_dir_path(__FILE__) . 'views/campaigns-list.php';
    }
    
    /**
     * Create campaign page
     */
    public function create_campaign_page() {
        $templates = $this->template_engine->get_templates();
        $subscriber_lists = $this->get_subscriber_lists();
        $segments = $this->get_segments();
        
        include plugin_dir_path(__FILE__) . 'views/create-campaign.php';
    }
    
    /**
     * Edit campaign page
     */
    public function edit_campaign_page() {
        $campaign_id = isset($_GET['campaign_id']) ? intval($_GET['campaign_id']) : 0;
        
        if (!$campaign_id) {
            wp_die(__('Invalid campaign ID.', 'environmental-email-marketing'));
        }
        
        $campaign = $this->campaign_manager->get_campaign($campaign_id);
        if (!$campaign) {
            wp_die(__('Campaign not found.', 'environmental-email-marketing'));
        }
        
        $templates = $this->template_engine->get_templates();
        $subscriber_lists = $this->get_subscriber_lists();
        $segments = $this->get_segments();
        $analytics = $this->analytics_tracker->get_campaign_analytics($campaign_id);
        
        include plugin_dir_path(__FILE__) . 'views/edit-campaign.php';
    }
    
    /**
     * AJAX: Create campaign
     */
    public function ajax_create_campaign() {
        check_ajax_referer('eem_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'subject' => sanitize_text_field($_POST['subject']),
            'content' => wp_kses_post($_POST['content']),
            'template_id' => intval($_POST['template_id']),
            'list_ids' => array_map('intval', $_POST['list_ids']),
            'segment_ids' => array_map('intval', $_POST['segment_ids']),
            'sender_name' => sanitize_text_field($_POST['sender_name']),
            'sender_email' => sanitize_email($_POST['sender_email']),
            'reply_to' => sanitize_email($_POST['reply_to']),
            'preheader' => sanitize_text_field($_POST['preheader']),
            'environmental_theme' => sanitize_text_field($_POST['environmental_theme']),
            'carbon_neutral' => isset($_POST['carbon_neutral']) ? 1 : 0,
            'sustainability_message' => wp_kses_post($_POST['sustainability_message'])
        );
        
        $campaign_id = $this->campaign_manager->create_campaign($data);
        
        if ($campaign_id) {
            wp_send_json_success(array(
                'campaign_id' => $campaign_id,
                'message' => __('Campaign created successfully!', 'environmental-email-marketing'),
                'redirect_url' => admin_url('admin.php?page=eem-edit-campaign&campaign_id=' . $campaign_id)
            ));
        } else {
            wp_send_json_error(__('Failed to create campaign.', 'environmental-email-marketing'));
        }
    }
    
    /**
     * AJAX: Update campaign
     */
    public function ajax_update_campaign() {
        check_ajax_referer('eem_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $campaign_id = intval($_POST['campaign_id']);
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'subject' => sanitize_text_field($_POST['subject']),
            'content' => wp_kses_post($_POST['content']),
            'template_id' => intval($_POST['template_id']),
            'list_ids' => array_map('intval', $_POST['list_ids']),
            'segment_ids' => array_map('intval', $_POST['segment_ids']),
            'sender_name' => sanitize_text_field($_POST['sender_name']),
            'sender_email' => sanitize_email($_POST['sender_email']),
            'reply_to' => sanitize_email($_POST['reply_to']),
            'preheader' => sanitize_text_field($_POST['preheader']),
            'environmental_theme' => sanitize_text_field($_POST['environmental_theme']),
            'carbon_neutral' => isset($_POST['carbon_neutral']) ? 1 : 0,
            'sustainability_message' => wp_kses_post($_POST['sustainability_message'])
        );
        
        $updated = $this->campaign_manager->update_campaign($campaign_id, $data);
        
        if ($updated) {
            wp_send_json_success(__('Campaign updated successfully!', 'environmental-email-marketing'));
        } else {
            wp_send_json_error(__('Failed to update campaign.', 'environmental-email-marketing'));
        }
    }
    
    /**
     * AJAX: Delete campaign
     */
    public function ajax_delete_campaign() {
        check_ajax_referer('eem_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $campaign_id = intval($_POST['campaign_id']);
        $deleted = $this->campaign_manager->delete_campaign($campaign_id);
        
        if ($deleted) {
            wp_send_json_success(__('Campaign deleted successfully!', 'environmental-email-marketing'));
        } else {
            wp_send_json_error(__('Failed to delete campaign.', 'environmental-email-marketing'));
        }
    }
    
    /**
     * AJAX: Schedule campaign
     */
    public function ajax_schedule_campaign() {
        check_ajax_referer('eem_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $campaign_id = intval($_POST['campaign_id']);
        $schedule_time = sanitize_text_field($_POST['schedule_time']);
        $send_immediately = isset($_POST['send_immediately']) ? true : false;
        
        if ($send_immediately) {
            $result = $this->campaign_manager->send_campaign($campaign_id);
        } else {
            $result = $this->campaign_manager->schedule_campaign($campaign_id, $schedule_time);
        }
        
        if ($result) {
            $message = $send_immediately ? 
                __('Campaign sent successfully!', 'environmental-email-marketing') :
                __('Campaign scheduled successfully!', 'environmental-email-marketing');
            wp_send_json_success($message);
        } else {
            wp_send_json_error(__('Failed to schedule campaign.', 'environmental-email-marketing'));
        }
    }
    
    /**
     * AJAX: Send test email
     */
    public function ajax_send_test_email() {
        check_ajax_referer('eem_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $campaign_id = intval($_POST['campaign_id']);
        $test_email = sanitize_email($_POST['test_email']);
        
        $result = $this->campaign_manager->send_test_email($campaign_id, $test_email);
        
        if ($result) {
            wp_send_json_success(__('Test email sent successfully!', 'environmental-email-marketing'));
        } else {
            wp_send_json_error(__('Failed to send test email.', 'environmental-email-marketing'));
        }
    }
    
    /**
     * AJAX: Preview campaign
     */
    public function ajax_preview_campaign() {
        check_ajax_referer('eem_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $campaign_id = intval($_POST['campaign_id']);
        $preview_html = $this->campaign_manager->get_campaign_preview($campaign_id);
        
        if ($preview_html) {
            wp_send_json_success(array('html' => $preview_html));
        } else {
            wp_send_json_error(__('Failed to generate preview.', 'environmental-email-marketing'));
        }
    }
    
    /**
     * AJAX: Duplicate campaign
     */
    public function ajax_duplicate_campaign() {
        check_ajax_referer('eem_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $campaign_id = intval($_POST['campaign_id']);
        $new_campaign_id = $this->campaign_manager->duplicate_campaign($campaign_id);
        
        if ($new_campaign_id) {
            wp_send_json_success(array(
                'campaign_id' => $new_campaign_id,
                'message' => __('Campaign duplicated successfully!', 'environmental-email-marketing'),
                'redirect_url' => admin_url('admin.php?page=eem-edit-campaign&campaign_id=' . $new_campaign_id)
            ));
        } else {
            wp_send_json_error(__('Failed to duplicate campaign.', 'environmental-email-marketing'));
        }
    }
    
    /**
     * Get subscriber lists
     */
    private function get_subscriber_lists() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}eem_lists ORDER BY name ASC"
        );
    }
    
    /**
     * Get segments
     */
    private function get_segments() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}eem_segments ORDER BY name ASC"
        );
    }
    
    /**
     * Format campaign status for display
     */
    public function format_campaign_status($status) {
        $statuses = array(
            'draft' => __('Draft', 'environmental-email-marketing'),
            'scheduled' => __('Scheduled', 'environmental-email-marketing'),
            'sending' => __('Sending', 'environmental-email-marketing'),
            'sent' => __('Sent', 'environmental-email-marketing'),
            'paused' => __('Paused', 'environmental-email-marketing'),
            'cancelled' => __('Cancelled', 'environmental-email-marketing')
        );
        
        return isset($statuses[$status]) ? $statuses[$status] : $status;
    }
    
    /**
     * Get campaign type icon
     */
    public function get_campaign_type_icon($type) {
        $icons = array(
            'newsletter' => 'dashicons-email-alt',
            'promotional' => 'dashicons-megaphone',
            'environmental' => 'dashicons-palmtree',
            'automation' => 'dashicons-admin-generic',
            'transactional' => 'dashicons-migrate'
        );
        
        return isset($icons[$type]) ? $icons[$type] : 'dashicons-email';
    }
}

// Initialize
new EEM_Admin_Campaigns();
