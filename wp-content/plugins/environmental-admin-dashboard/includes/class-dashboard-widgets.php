<?php
/**
 * Dashboard Widgets Manager Class
 * 
 * Manages and coordinates all dashboard widgets for the Environmental Admin Dashboard
 * 
 * @package EnvironmentalAdminDashboard
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Dashboard_Widgets {
    
    private static $instance = null;
    private $registered_widgets = array();
    
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
        $this->load_widgets();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('wp_dashboard_setup', array($this, 'register_dashboard_widgets'));
        add_action('wp_ajax_widget_refresh', array($this, 'ajax_refresh_widget'));
        add_action('wp_ajax_widget_toggle', array($this, 'ajax_toggle_widget'));
        add_action('wp_ajax_widget_reorder', array($this, 'ajax_reorder_widgets'));
    }
    
    /**
     * Load all widget files
     */
    private function load_widgets() {
        $widget_files = array(
            'environmental-platform-overview-widget.php',
            'environmental-activities-progress-widget.php',
            'environmental-environmental-goals-widget.php',
            'environmental-performance-analytics-widget.php',
            'environmental-platform-health-widget.php',
            'environmental-quick-actions-widget.php'
        );
        
        foreach ($widget_files as $widget_file) {
            $widget_path = ENV_ADMIN_DASHBOARD_PLUGIN_PATH . 'widgets/' . $widget_file;
            if (file_exists($widget_path)) {
                require_once $widget_path;
            }
        }
    }
    
    /**
     * Register all dashboard widgets
     */
    public function register_dashboard_widgets() {
        // Platform Overview Widget
        wp_add_dashboard_widget(
            'environmental_platform_overview',
            __('Environmental Platform Overview', 'env-admin-dashboard'),
            array($this, 'render_platform_overview_widget')
        );
        
        // Activities Progress Widget
        wp_add_dashboard_widget(
            'environmental_activities_progress',
            __('Activities Progress', 'env-admin-dashboard'),
            array($this, 'render_activities_progress_widget')
        );
        
        // Environmental Goals Widget
        wp_add_dashboard_widget(
            'environmental_goals',
            __('Environmental Goals', 'env-admin-dashboard'),
            array($this, 'render_environmental_goals_widget')
        );
        
        // Performance Analytics Widget
        wp_add_dashboard_widget(
            'environmental_performance_analytics',
            __('Performance Analytics', 'env-admin-dashboard'),
            array($this, 'render_performance_analytics_widget')
        );
        
        // Platform Health Widget
        wp_add_dashboard_widget(
            'environmental_platform_health',
            __('Platform Health', 'env-admin-dashboard'),
            array($this, 'render_platform_health_widget')
        );
        
        // Quick Actions Widget
        wp_add_dashboard_widget(
            'environmental_quick_actions',
            __('Quick Actions', 'env-admin-dashboard'),
            array($this, 'render_quick_actions_widget')
        );
    }
    
    /**
     * Render Platform Overview Widget
     */
    public function render_platform_overview_widget() {
        if (function_exists('environmental_platform_overview_widget')) {
            environmental_platform_overview_widget();
        } else {
            echo '<p>' . __('Platform overview data not available', 'env-admin-dashboard') . '</p>';
        }
    }
    
    /**
     * Render Activities Progress Widget
     */
    public function render_activities_progress_widget() {
        if (function_exists('environmental_activities_progress_widget')) {
            environmental_activities_progress_widget();
        } else {
            echo '<p>' . __('Activities progress data not available', 'env-admin-dashboard') . '</p>';
        }
    }
    
    /**
     * Render Environmental Goals Widget
     */
    public function render_environmental_goals_widget() {
        if (function_exists('environmental_environmental_goals_widget')) {
            environmental_environmental_goals_widget();
        } else {
            echo '<p>' . __('Environmental goals data not available', 'env-admin-dashboard') . '</p>';
        }
    }
    
    /**
     * Render Performance Analytics Widget
     */
    public function render_performance_analytics_widget() {
        if (function_exists('environmental_performance_analytics_widget')) {
            environmental_performance_analytics_widget();
        } else {
            echo '<p>' . __('Performance analytics data not available', 'env-admin-dashboard') . '</p>';
        }
    }
    
    /**
     * Render Platform Health Widget
     */
    public function render_platform_health_widget() {
        if (function_exists('environmental_platform_health_widget')) {
            environmental_platform_health_widget();
        } else {
            echo '<p>' . __('Platform health data not available', 'env-admin-dashboard') . '</p>';
        }
    }
    
    /**
     * Render Quick Actions Widget
     */
    public function render_quick_actions_widget() {
        if (function_exists('environmental_quick_actions_widget')) {
            environmental_quick_actions_widget();
        } else {
            echo '<p>' . __('Quick actions not available', 'env-admin-dashboard') . '</p>';
        }
    }
    
    /**
     * AJAX handler for widget refresh
     */
    public function ajax_refresh_widget() {
        check_ajax_referer('env_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Insufficient permissions')));
        }
        
        $widget_id = sanitize_text_field($_POST['widget_id']);
        
        // Clear widget cache
        delete_transient('env_widget_' . $widget_id);
        
        wp_die(json_encode(array('success' => true, 'message' => 'Widget refreshed')));
    }
    
    /**
     * AJAX handler for widget toggle
     */
    public function ajax_toggle_widget() {
        check_ajax_referer('env_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Insufficient permissions')));
        }
        
        $widget_id = sanitize_text_field($_POST['widget_id']);
        $visible = (bool) $_POST['visible'];
        
        // Save widget visibility preference
        $hidden_widgets = get_user_meta(get_current_user_id(), 'metaboxhidden_dashboard', true);
        if (!$hidden_widgets) {
            $hidden_widgets = array();
        }
        
        if ($visible) {
            $hidden_widgets = array_diff($hidden_widgets, array($widget_id));
        } else {
            if (!in_array($widget_id, $hidden_widgets)) {
                $hidden_widgets[] = $widget_id;
            }
        }
        
        update_user_meta(get_current_user_id(), 'metaboxhidden_dashboard', $hidden_widgets);
        
        wp_die(json_encode(array('success' => true, 'message' => 'Widget visibility updated')));
    }
    
    /**
     * AJAX handler for widget reorder
     */
    public function ajax_reorder_widgets() {
        check_ajax_referer('env_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Insufficient permissions')));
        }
        
        $widget_order = array_map('sanitize_text_field', $_POST['widget_order']);
        
        // Save widget order preference
        update_user_meta(get_current_user_id(), 'meta-box-order_dashboard', array(
            'normal' => implode(',', $widget_order)
        ));
        
        wp_die(json_encode(array('success' => true, 'message' => 'Widget order updated')));
    }
}
