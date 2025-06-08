<?php
/**
 * Plugin Name: Environmental Testing & Quality Assurance
 * Plugin URI: https://environmental-platform.local
 * Description: Comprehensive testing and quality assurance suite for the Environmental Platform - Phase 58
 * Version: 1.0.0
 * Author: Environmental Platform Team
 * License: GPL v2 or later
 * Text Domain: environmental-testing-qa
 * Domain Path: /languages
 * 
 * @package EnvironmentalTestingQA
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ETQ_VERSION', '1.0.0');
define('ETQ_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ETQ_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ETQ_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Environmental Testing & QA Plugin Class
 */
class Environmental_Testing_QA {
    
    /**
     * Single instance of the plugin
     */
    private static $instance = null;
    
    /**
     * Plugin version
     */
    public $version = ETQ_VERSION;
      /**
     * Core components
     */
    public $database;
    public $phpunit_manager;
    public $selenium_manager;
    public $performance_tester;
    public $staging_manager;
    public $admin_dashboard;
    public $test_suite;
    public $test_runner;
    public $documentation;
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
        $this->init_components();
    }
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
      /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'), 0);
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
        
        // AJAX hooks for testing
        add_action('wp_ajax_etq_run_test', array($this, 'ajax_run_test'));
        add_action('wp_ajax_etq_run_test_suite', array($this, 'ajax_run_test_suite'));
        add_action('wp_ajax_etq_get_test_results', array($this, 'ajax_get_test_results'));
        
        // Activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Core classes in order of dependency
        require_once ETQ_PLUGIN_PATH . 'includes/class-etq-database.php';
        require_once ETQ_PLUGIN_PATH . 'includes/class-etq-phpunit-manager.php';
        require_once ETQ_PLUGIN_PATH . 'includes/class-etq-selenium-manager.php';
        require_once ETQ_PLUGIN_PATH . 'includes/class-etq-performance-tester.php';
        require_once ETQ_PLUGIN_PATH . 'includes/class-etq-staging-manager.php';
        require_once ETQ_PLUGIN_PATH . 'includes/class-etq-test-suite.php';
        require_once ETQ_PLUGIN_PATH . 'includes/class-etq-test-runner.php';
        require_once ETQ_PLUGIN_PATH . 'includes/class-etq-documentation.php';
        require_once ETQ_PLUGIN_PATH . 'includes/class-etq-admin-dashboard.php';
    }
      /**
     * Initialize all components
     */
    private function init_components() {
        // Initialize core components in order
        $this->database = ETQ_Database::get_instance();
        $this->phpunit_manager = ETQ_PHPUnit_Manager::get_instance();
        $this->selenium_manager = ETQ_Selenium_Manager::get_instance();
        $this->performance_tester = ETQ_Performance_Tester::get_instance();
        $this->staging_manager = ETQ_Staging_Manager::get_instance();
        $this->test_suite = ETQ_Test_Suite::get_instance();
        $this->test_runner = ETQ_Test_Runner::get_instance();
        $this->documentation = ETQ_Documentation::get_instance();
        $this->admin_dashboard = new ETQ_Admin_Dashboard();
        
        // Hook components together
        do_action('etq_components_loaded');
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('environmental-testing-qa', false, dirname(ETQ_PLUGIN_BASENAME) . '/languages');
        
        // Initialize database if needed
        if (get_option('etq_db_version') !== ETQ_VERSION) {
            $this->database->create_tables();
            update_option('etq_db_version', ETQ_VERSION);
        }
        
        do_action('etq_init');
    }
    
    /**
     * Add admin menu
     */    /**
     * Enqueue admin scripts and styles
     */
    public function admin_scripts($hook) {
        if (strpos($hook, 'environmental-testing-qa') === false && strpos($hook, 'etq-') === false) {
            return;
        }
        
        wp_enqueue_script(
            'etq-admin',
            ETQ_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-util'),
            ETQ_VERSION,
            true
        );
        
        wp_enqueue_style(
            'etq-admin',
            ETQ_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            ETQ_VERSION
        );
        
        // Localize script for AJAX
        wp_localize_script('etq-admin', 'etq_ajax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('etq_nonce'),
            'strings' => array(
                'running_test' => __('Running test...', 'environmental-testing-qa'),
                'test_completed' => __('Test completed', 'environmental-testing-qa'),
                'test_failed' => __('Test failed', 'environmental-testing-qa'),
                'confirm_delete' => __('Are you sure you want to delete this test?', 'environmental-testing-qa'),
            )
        ));
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function frontend_scripts() {
        wp_enqueue_script(
            'etq-frontend',
            ETQ_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            ETQ_VERSION,
            true
        );
        
        wp_enqueue_style(
            'etq-frontend',
            ETQ_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            ETQ_VERSION
        );
    }
    
    /**
     * AJAX handler for running individual tests
     */
    public function ajax_run_test() {
        check_ajax_referer('etq_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'environmental-testing-qa'));
        }
        
        $test_id = sanitize_text_field($_POST['test_id']);
        $test_type = sanitize_text_field($_POST['test_type']);
        
        $result = $this->test_runner->run_test($test_id, $test_type);
        
        wp_send_json($result);
    }
    
    /**
     * AJAX handler for running test suites
     */
    public function ajax_run_test_suite() {
        check_ajax_referer('etq_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'environmental-testing-qa'));
        }
        
        $suite_id = sanitize_text_field($_POST['suite_id']);
        
        $result = $this->test_runner->run_test_suite($suite_id);
        
        wp_send_json($result);
    }
    
    /**
     * AJAX handler for getting test results
     */
    public function ajax_get_test_results() {
        check_ajax_referer('etq_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'environmental-testing-qa'));
        }
        
        $test_run_id = sanitize_text_field($_POST['test_run_id']);
        
        $results = $this->database->get_test_results($test_run_id);
        
        wp_send_json_success($results);
    }
      /**
     * Plugin activation
     */
    public function activate() {
        // Load dependencies first
        $this->load_dependencies();
        
        // Create database tables
        $database = ETQ_Database::get_instance();
        $database->create_tables();
        
        // Set default options
        add_option('etq_db_version', ETQ_VERSION);
        add_option('etq_settings', array(
            'enable_phpunit' => true,
            'enable_selenium' => false,
            'enable_performance' => true,
            'enable_staging' => true,
            'auto_run_tests' => false,
            'email_notifications' => false,
            'retention_days' => 30,
        ));
        
        // Schedule cron events
        if (!wp_next_scheduled('etq_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'etq_daily_cleanup');
        }
        
        if (!wp_next_scheduled('etq_weekly_report')) {
            wp_schedule_event(time(), 'weekly', 'etq_weekly_report');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('etq_daily_cleanup');
        wp_clear_scheduled_hook('etq_weekly_report');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

/**
 * Initialize the plugin
 */
function etq_init() {
    return Environmental_Testing_QA::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'etq_init');

/**
 * Plugin activation/deactivation hooks
 */
register_activation_hook(__FILE__, function() {
    Environmental_Testing_QA::get_instance()->activate();
});

register_deactivation_hook(__FILE__, function() {
    Environmental_Testing_QA::get_instance()->deactivate();
});
