<?php
/**
 * Final Validation Script for Environmental Email Marketing Plugin
 * 
 * This script performs comprehensive validation of all plugin functionality
 * Run this script to ensure everything is working correctly before deployment
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EEM_Final_Validator {
    
    private $validation_results = array();
    private $error_count = 0;
    private $warning_count = 0;
    private $success_count = 0;
    
    public function __construct() {
        add_action('wp_ajax_eem_final_validation', array($this, 'run_final_validation'));
    }
    
    /**
     * Run complete validation of all plugin functionality
     */
    public function run_final_validation() {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $this->validation_results = array();
        $this->error_count = 0;
        $this->warning_count = 0;
        $this->success_count = 0;
        
        // Core System Validation
        $this->validate_core_system();
        
        // Database Validation
        $this->validate_database_structure();
        
        // Class Loading Validation
        $this->validate_class_loading();
        
        // AJAX Endpoints Validation
        $this->validate_ajax_endpoints();
        
        // REST API Validation
        $this->validate_rest_api();
        
        // Email Functionality Validation
        $this->validate_email_functionality();
        
        // Template System Validation
        $this->validate_template_system();
        
        // Analytics System Validation
        $this->validate_analytics_system();
        
        // Automation System Validation
        $this->validate_automation_system();
        
        // Integration Validation
        $this->validate_integrations();
        
        // Performance Validation
        $this->validate_performance();
        
        // Security Validation
        $this->validate_security();
        
        // Final Summary
        $summary = array(
            'total_tests' => count($this->validation_results),
            'passed' => $this->success_count,
            'warnings' => $this->warning_count,
            'failed' => $this->error_count,
            'overall_status' => $this->get_overall_status(),
            'results' => $this->validation_results
        );
        
        wp_send_json_success($summary);
    }
    
    /**
     * Validate core system functionality
     */
    private function validate_core_system() {
        $this->add_test_result(
            'Plugin Constants',
            defined('EEM_PLUGIN_VERSION') && defined('EEM_PLUGIN_PATH') && defined('EEM_PLUGIN_URL'),
            'Plugin constants are properly defined',
            'Plugin constants are missing or not properly defined'
        );
        
        $this->add_test_result(
            'WordPress Version',
            version_compare(get_bloginfo('version'), '5.0', '>='),
            'WordPress version is compatible (' . get_bloginfo('version') . ')',
            'WordPress version may not be compatible (' . get_bloginfo('version') . ')'
        );
        
        $this->add_test_result(
            'PHP Version',
            version_compare(PHP_VERSION, '7.4', '>='),
            'PHP version is compatible (' . PHP_VERSION . ')',
            'PHP version may not be compatible (' . PHP_VERSION . ')'
        );
        
        $this->add_test_result(
            'Plugin Activation',
            is_plugin_active(EEM_PLUGIN_BASENAME),
            'Plugin is properly activated',
            'Plugin activation status unclear'
        );
    }
    
    /**
     * Validate database structure
     */
    private function validate_database_structure() {
        global $wpdb;
        
        $required_tables = array(
            'eem_subscribers',
            'eem_lists',
            'eem_campaigns',  
            'eem_templates',
            'eem_automations',
            'eem_analytics',
            'eem_ab_tests',
            'eem_segments',
            'eem_webhooks',
            'eem_queue'
        );
        
        $missing_tables = array();
        
        foreach ($required_tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            
            if (!$exists) {
                $missing_tables[] = $table;
            }
        }
        
        $this->add_test_result(
            'Database Tables',
            empty($missing_tables),
            'All required database tables exist (' . count($required_tables) . ' tables)',
            'Missing database tables: ' . implode(', ', $missing_tables)
        );
        
        // Test database operations
        $test_email = 'validation_test_' . time() . '@example.com';
        
        $insert_result = $wpdb->insert(
            $wpdb->prefix . 'eem_subscribers',
            array(
                'email' => $test_email,
                'status' => 'test',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s')
        );
        
        if ($insert_result) {
            $test_id = $wpdb->insert_id;
            $delete_result = $wpdb->delete(
                $wpdb->prefix . 'eem_subscribers',
                array('id' => $test_id),
                array('%d')
            );
            
            $this->add_test_result(
                'Database Operations',
                $delete_result !== false,
                'Database insert/delete operations working correctly',
                'Database operations failed'
            );
        } else {
            $this->add_test_result(
                'Database Operations',
                false,
                '',
                'Database insert operation failed: ' . $wpdb->last_error
            );
        }
    }
    
    /**
     * Validate class loading
     */
    private function validate_class_loading() {
        $required_classes = array(
            'EEM_Database_Manager',
            'EEM_Subscriber_Manager',
            'EEM_Campaign_Manager',
            'EEM_Automation_Engine',
            'EEM_Template_Engine',
            'EEM_Analytics_Tracker',
            'EEM_Email_Service_Provider',
            'EEM_Mailchimp_Provider',
            'EEM_SendGrid_Provider',
            'EEM_Frontend',
            'EEM_REST_API',
            'EEM_Cron_Handler'
        );
        
        $missing_classes = array();
        
        foreach ($required_classes as $class) {
            if (!class_exists($class)) {
                $missing_classes[] = $class;
            }
        }
        
        $this->add_test_result(
            'Class Loading',
            empty($missing_classes),
            'All required classes loaded successfully (' . count($required_classes) . ' classes)',
            'Missing classes: ' . implode(', ', $missing_classes)
        );
    }
    
    /**
     * Validate AJAX endpoints
     */
    private function validate_ajax_endpoints() {
        global $wp_filter;
        
        $required_ajax_actions = array(
            'eem_subscribe',
            'eem_unsubscribe',
            'eem_update_preferences',
            'eem_send_campaign',
            'eem_get_analytics',
            'eem_create_campaign',
            'eem_delete_campaign',
            'eem_import_subscribers',
            'eem_export_subscribers'
        );
        
        $missing_actions = array();
        
        foreach ($required_ajax_actions as $action) {
            $registered = isset($wp_filter['wp_ajax_' . $action]) || isset($wp_filter['wp_ajax_nopriv_' . $action]);
            if (!$registered) {
                $missing_actions[] = $action;
            }
        }
        
        $this->add_test_result(
            'AJAX Endpoints',
            empty($missing_actions),
            'All AJAX endpoints registered (' . count($required_ajax_actions) . ' endpoints)',
            'Missing AJAX endpoints: ' . implode(', ', $missing_actions)
        );
    }
    
    /**
     * Validate REST API
     */
    private function validate_rest_api() {
        $rest_server = rest_get_server();
        $routes = $rest_server->get_routes();
        
        $required_routes = array(
            '/eem/v1/subscribers',
            '/eem/v1/campaigns',
            '/eem/v1/lists',
            '/eem/v1/analytics',
            '/eem/v1/webhooks'
        );
        
        $missing_routes = array();
        
        foreach ($required_routes as $route) {
            if (!isset($routes[$route])) {
                $missing_routes[] = $route;
            }
        }
        
        $this->add_test_result(
            'REST API Routes',
            empty($missing_routes),
            'All REST API routes registered (' . count($required_routes) . ' routes)',
            'Missing REST API routes: ' . implode(', ', $missing_routes)
        );
    }
    
    /**
     * Validate email functionality
     */
    private function validate_email_functionality() {
        // Test wp_mail function
        $this->add_test_result(
            'WP Mail Function',
            function_exists('wp_mail'),
            'wp_mail function is available',
            'wp_mail function is not available'
        );
        
        // Test email provider classes
        $providers = array('Mailchimp', 'SendGrid', 'Mailgun', 'Amazon_SES', 'Native');
        $provider_status = array();
        
        foreach ($providers as $provider) {
            $class_name = 'EEM_' . $provider . '_Provider';
            $provider_status[] = class_exists($class_name);
        }
        
        $this->add_test_result(
            'Email Providers',
            !in_array(false, $provider_status),
            'All email provider classes loaded (' . count($providers) . ' providers)',
            'Some email provider classes are missing'
        );
        
        // Test basic email sending
        $test_result = wp_mail(
            get_option('admin_email'),
            'EEM Final Validation Test - ' . date('Y-m-d H:i:s'),
            'This is a final validation test email from Environmental Email Marketing plugin.',
            array('Content-Type: text/plain; charset=UTF-8')
        );
        
        $this->add_test_result(
            'Email Sending',
            $test_result,
            'Test email sent successfully',
            'Email sending failed'
        );
    }
    
    /**
     * Validate template system
     */
    private function validate_template_system() {
        if (!class_exists('EEM_Template_Engine')) {
            $this->add_test_result(
                'Template Engine',
                false,
                '',
                'EEM_Template_Engine class not found'
            );
            return;
        }
        
        $template_engine = new EEM_Template_Engine();
        
        // Test template rendering
        $test_data = array(
            'subscriber_name' => 'Test User',
            'campaign_name' => 'Validation Campaign'
        );
        
        $template_content = 'Hello {{subscriber_name}}, welcome to {{campaign_name}}!';
        $rendered = $template_engine->render_template($template_content, $test_data);
        
        $this->add_test_result(
            'Template Rendering',
            strpos($rendered, 'Test User') !== false && strpos($rendered, 'Validation Campaign') !== false,
            'Template variable replacement working correctly',
            'Template rendering failed'
        );
        
        // Check template files
        $template_files = array(
            EEM_PLUGIN_PATH . 'templates/default.php',
            EEM_PLUGIN_PATH . 'templates/newsletter.php',
            EEM_PLUGIN_PATH . 'templates/promotional.php'
        );
        
        $missing_templates = array();
        foreach ($template_files as $file) {
            if (!file_exists($file)) {
                $missing_templates[] = basename($file);
            }
        }
        
        $this->add_test_result(
            'Template Files',
            empty($missing_templates),
            'All template files exist (' . count($template_files) . ' templates)',
            'Missing template files: ' . implode(', ', $missing_templates)
        );
    }
    
    /**
     * Validate analytics system
     */
    private function validate_analytics_system() {
        if (!class_exists('EEM_Analytics_Tracker')) {
            $this->add_test_result(
                'Analytics System',
                false,
                '',
                'EEM_Analytics_Tracker class not found'
            );
            return;
        }
        
        $analytics = new EEM_Analytics_Tracker();
        
        // Test event tracking
        $event_result = $analytics->track_event('validation_test', array(
            'campaign_id' => 9999,
            'subscriber_id' => 9999,
            'test_data' => 'final_validation'
        ));
        
        $this->add_test_result(
            'Event Tracking',
            $event_result !== false,
            'Event tracking working correctly',
            'Event tracking failed'
        );
        
        // Test click tracking
        $click_result = $analytics->track_click(9999, 9999, 'https://example.com/validation');
        
        $this->add_test_result(
            'Click Tracking',
            $click_result !== false,
            'Click tracking working correctly',
            'Click tracking failed'
        );
    }
    
    /**
     * Validate automation system
     */
    private function validate_automation_system() {
        if (!class_exists('EEM_Automation_Engine')) {
            $this->add_test_result(
                'Automation System',
                false,
                '',
                'EEM_Automation_Engine class not found'
            );
            return;
        }
        
        $automation = new EEM_Automation_Engine();
        
        // Test automation trigger
        $trigger_result = $automation->trigger_automation('validation_test', array(
            'subscriber_id' => 9999,
            'test_data' => 'final_validation'
        ));
        
        $this->add_test_result(
            'Automation Triggers',
            $trigger_result !== false,
            'Automation triggers working correctly',
            'Automation triggers failed'
        );
    }
    
    /**
     * Validate integrations
     */
    private function validate_integrations() {
        $integration_classes = array(
            'EEM_WooCommerce_Integration',
            'EEM_Petition_Integration', 
            'EEM_Widget_Integration',
            'EEM_Event_Integration',
            'EEM_Quiz_Integration'
        );
        
        $missing_integrations = array();
        
        foreach ($integration_classes as $class) {
            if (!class_exists($class)) {
                $missing_integrations[] = $class;
            }
        }
        
        $this->add_test_result(
            'Integration Classes',
            empty($missing_integrations),
            'All integration classes loaded (' . count($integration_classes) . ' integrations)',
            'Missing integration classes: ' . implode(', ', $missing_integrations)
        );
    }
    
    /**
     * Validate performance
     */
    private function validate_performance() {
        // Test memory usage
        $memory_usage = memory_get_usage(true);
        $memory_limit = $this->parse_memory_limit(ini_get('memory_limit'));
        $memory_percentage = ($memory_usage / $memory_limit) * 100;
        
        $this->add_test_result(
            'Memory Usage',
            $memory_percentage < 80,
            'Memory usage is acceptable (' . round($memory_percentage, 1) . '% of limit)',
            'High memory usage (' . round($memory_percentage, 1) . '% of limit)',
            $memory_percentage >= 80 && $memory_percentage < 95 ? 'warning' : 'error'
        );
        
        // Test database query performance
        global $wpdb;
        $start_time = microtime(true);
        
        $wpdb->get_results("SELECT COUNT(*) FROM {$wpdb->prefix}options");
        
        $query_time = microtime(true) - $start_time;
        
        $this->add_test_result(
            'Database Performance',
            $query_time < 0.1,
            'Database queries are fast (' . round($query_time * 1000, 2) . 'ms)',
            'Database queries are slow (' . round($query_time * 1000, 2) . 'ms)',
            $query_time >= 0.1 && $query_time < 0.5 ? 'warning' : 'error'
        );
    }
    
    /**
     * Validate security
     */
    private function validate_security() {
        // Check file permissions
        $critical_files = array(
            EEM_PLUGIN_PATH . 'environmental-email-marketing.php',
            EEM_PLUGIN_PATH . 'includes/class-eem-database-manager.php'
        );
        
        $permission_issues = array();
        
        foreach ($critical_files as $file) {
            if (file_exists($file) && is_writable($file)) {
                $permission_issues[] = basename($file);
            }
        }
        
        $this->add_test_result(
            'File Security',
            empty($permission_issues),
            'Critical files have appropriate permissions',
            'Security risk: Writable critical files: ' . implode(', ', $permission_issues),
            !empty($permission_issues) ? 'warning' : 'success'
        );
        
        // Check for debug mode in production
        $debug_enabled = defined('WP_DEBUG') && WP_DEBUG;
        
        $this->add_test_result(
            'Debug Mode',
            !$debug_enabled,
            'Debug mode is disabled',
            'Debug mode is enabled (should be disabled in production)',
            $debug_enabled ? 'warning' : 'success'
        );
        
        // Check nonce validation in AJAX handlers
        $nonce_check = wp_verify_nonce(wp_create_nonce('test'), 'test');
        
        $this->add_test_result(
            'Nonce System',
            $nonce_check,
            'WordPress nonce system is working',
            'WordPress nonce system may have issues'
        );
    }
    
    /**
     * Add test result
     */
    private function add_test_result($test_name, $passed, $success_message, $error_message, $status = null) {
        if ($status === null) {
            $status = $passed ? 'success' : 'error';
        }
        
        $result = array(
            'test' => $test_name,
            'status' => $status,
            'message' => $passed ? $success_message : $error_message,
            'timestamp' => current_time('mysql')
        );
        
        $this->validation_results[] = $result;
        
        switch ($status) {
            case 'success':
                $this->success_count++;
                break;
            case 'warning':
                $this->warning_count++;
                break;
            case 'error':
                $this->error_count++;
                break;
        }
    }
    
    /**
     * Get overall validation status
     */
    private function get_overall_status() {
        if ($this->error_count > 0) {
            return 'failed';
        } elseif ($this->warning_count > 0) {
            return 'warnings';
        } else {
            return 'passed';
        }
    }
    
    /**
     * Parse memory limit string to bytes
     */
    private function parse_memory_limit($limit) {
        if ($limit === '-1') {
            return PHP_INT_MAX;
        }
        
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $limit = (int) $limit;
        
        switch ($last) {
            case 'g':
                $limit *= 1024;
            case 'm':
                $limit *= 1024;
            case 'k':
                $limit *= 1024;
        }
        
        return $limit;
    }
}

// Initialize the final validator
new EEM_Final_Validator();
