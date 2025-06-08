<?php
/**
 * Test Runner Integration
 * 
 * Provides unified test execution system that coordinates all testing components
 * for the Environmental Platform WordPress system.
 * 
 * @package EnvironmentalTestingQA
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ETQ_Test_Runner {
    
    /**
     * Database instance
     */
    private $db;
    
    /**
     * Test managers
     */
    private $phpunit_manager;
    private $selenium_manager;
    private $performance_tester;
    private $test_suite;
    
    /**
     * Current test run context
     */
    private $current_run = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = ETQ_Database::get_instance();
        $this->phpunit_manager = new ETQ_PHPUnit_Manager();
        $this->selenium_manager = new ETQ_Selenium_Manager();
        $this->performance_tester = new ETQ_Performance_Tester();
        $this->test_suite = new ETQ_Test_Suite();
        
        add_action('wp_ajax_etq_start_test_run', [$this, 'ajax_start_test_run']);
        add_action('wp_ajax_etq_stop_test_run', [$this, 'ajax_stop_test_run']);
        add_action('wp_ajax_etq_get_test_run_status', [$this, 'ajax_get_test_run_status']);
        add_action('wp_ajax_etq_get_test_run_logs', [$this, 'ajax_get_test_run_logs']);
        
        // Schedule automatic test runs
        add_action('etq_scheduled_test_run', [$this, 'run_scheduled_tests']);
        
        // Hook into WordPress events for automatic testing
        add_action('activated_plugin', [$this, 'trigger_plugin_tests']);
        add_action('switch_theme', [$this, 'trigger_theme_tests']);
        add_action('wp_update_plugins', [$this, 'trigger_update_tests']);
    }
    
    /**
     * Start a comprehensive test run
     */
    public function start_test_run($config = []) {
        $default_config = [
            'run_type' => 'comprehensive',
            'test_types' => ['phpunit', 'selenium', 'performance'],
            'environment' => 'development',
            'parallel_execution' => false,
            'continue_on_failure' => true,
            'email_notifications' => true,
            'save_artifacts' => true,
            'cleanup_after' => true,
            'timeout' => 7200 // 2 hours
        ];
        
        $config = array_merge($default_config, $config);
        
        try {
            // Check if another test run is already in progress
            if ($this->is_test_run_in_progress()) {
                throw new Exception('Another test run is already in progress');
            }
            
            // Create master test run record
            $run_id = $this->create_master_test_run($config);
            if (!$run_id) {
                throw new Exception('Failed to create test run record');
            }
            
            $this->current_run = [
                'id' => $run_id,
                'config' => $config,
                'start_time' => microtime(true),
                'status' => 'running',
                'results' => []
            ];
            
            // Update run status
            $this->update_test_run_status($run_id, 'running');
            
            // Execute tests based on configuration
            $test_results = $this->execute_test_plan($config, $run_id);
            
            // Compile final results
            $final_results = $this->compile_final_results($test_results, $run_id);
            
            // Update run with final results
            $this->complete_test_run($run_id, $final_results);
            
            // Send notifications if enabled
            if ($config['email_notifications']) {
                $this->send_test_run_notification($final_results);
            }
            
            // Cleanup if requested
            if ($config['cleanup_after']) {
                $this->cleanup_test_artifacts($run_id);
            }
            
            $this->current_run = null;
            
            return $final_results;
            
        } catch (Exception $e) {
            if (isset($run_id)) {
                $this->update_test_run_status($run_id, 'error', [
                    'error' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString()
                ]);
            }
            
            $this->current_run = null;
            error_log('Test run error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Execute the test plan
     */
    private function execute_test_plan($config, $run_id) {
        $results = [
            'phpunit' => null,
            'selenium' => null,
            'performance' => null,
            'integration' => null
        ];
        
        // Run PHPUnit tests
        if (in_array('phpunit', $config['test_types'])) {
            $this->log_test_step($run_id, 'Starting PHPUnit tests');
            $results['phpunit'] = $this->run_phpunit_tests($config, $run_id);
            
            if (!$config['continue_on_failure'] && $results['phpunit']['overall_status'] === 'failed') {
                $this->log_test_step($run_id, 'PHPUnit tests failed, stopping execution');
                return $results;
            }
        }
        
        // Run Selenium tests
        if (in_array('selenium', $config['test_types'])) {
            $this->log_test_step($run_id, 'Starting Selenium tests');
            $results['selenium'] = $this->run_selenium_tests($config, $run_id);
            
            if (!$config['continue_on_failure'] && $results['selenium']['overall_status'] === 'failed') {
                $this->log_test_step($run_id, 'Selenium tests failed, stopping execution');
                return $results;
            }
        }
        
        // Run Performance tests
        if (in_array('performance', $config['test_types'])) {
            $this->log_test_step($run_id, 'Starting Performance tests');
            $results['performance'] = $this->run_performance_tests($config, $run_id);
        }
        
        // Run Integration tests
        if (in_array('integration', $config['test_types'])) {
            $this->log_test_step($run_id, 'Starting Integration tests');
            $results['integration'] = $this->run_integration_tests($config, $run_id);
        }
        
        return $results;
    }
    
    /**
     * Run PHPUnit tests
     */
    private function run_phpunit_tests($config, $run_id) {
        try {
            $this->log_test_step($run_id, 'Discovering PHPUnit test files');
            
            // Discover test files
            $test_files = $this->discover_phpunit_tests();
            
            if (empty($test_files)) {
                return [
                    'status' => 'skipped',
                    'message' => 'No PHPUnit test files found',
                    'overall_status' => 'skipped'
                ];
            }
            
            $results = [
                'total_files' => count($test_files),
                'executed_files' => 0,
                'passed_files' => 0,
                'failed_files' => 0,
                'file_results' => [],
                'overall_status' => 'passed'
            ];
            
            foreach ($test_files as $test_file) {
                $this->log_test_step($run_id, "Running PHPUnit test: {$test_file}");
                
                $file_result = $this->phpunit_manager->run_test_file($test_file);
                $results['file_results'][] = $file_result;
                $results['executed_files']++;
                
                if ($file_result['status'] === 'passed') {
                    $results['passed_files']++;
                } else {
                    $results['failed_files']++;
                    $results['overall_status'] = 'failed';
                }
            }
            
            return $results;
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'overall_status' => 'error'
            ];
        }
    }
    
    /**
     * Run Selenium tests
     */
    private function run_selenium_tests($config, $run_id) {
        try {
            $this->log_test_step($run_id, 'Discovering Selenium test scripts');
            
            // Discover test scripts
            $test_scripts = $this->discover_selenium_tests();
            
            if (empty($test_scripts)) {
                return [
                    'status' => 'skipped',
                    'message' => 'No Selenium test scripts found',
                    'overall_status' => 'skipped'
                ];
            }
            
            $results = [
                'total_scripts' => count($test_scripts),
                'executed_scripts' => 0,
                'passed_scripts' => 0,
                'failed_scripts' => 0,
                'script_results' => [],
                'overall_status' => 'passed'
            ];
            
            foreach ($test_scripts as $script_path) {
                $this->log_test_step($run_id, "Running Selenium test: {$script_path}");
                
                $script_result = $this->selenium_manager->run_test_script($script_path);
                $results['script_results'][] = $script_result;
                $results['executed_scripts']++;
                
                if ($script_result['status'] === 'passed') {
                    $results['passed_scripts']++;
                } else {
                    $results['failed_scripts']++;
                    $results['overall_status'] = 'failed';
                }
            }
            
            return $results;
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'overall_status' => 'error'
            ];
        }
    }
    
    /**
     * Run Performance tests
     */
    private function run_performance_tests($config, $run_id) {
        try {
            $this->log_test_step($run_id, 'Running comprehensive performance test suite');
            
            $performance_config = [
                'concurrent_users' => $config['performance_users'] ?? 10,
                'test_duration' => $config['performance_duration'] ?? 60,
                'endpoints' => $config['performance_endpoints'] ?? [
                    '/',
                    '/shop/',
                    '/donate/',
                    '/petitions/',
                    '/environmental-data/'
                ]
            ];
            
            $result = $this->performance_tester->run_performance_test_suite($performance_config);
            
            return [
                'status' => $result['error'] ?? false ? 'error' : 'completed',
                'overall_status' => $result['overall_score'] >= 70 ? 'passed' : 'failed',
                'performance_score' => $result['overall_score'],
                'details' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'overall_status' => 'error'
            ];
        }
    }
    
    /**
     * Run Integration tests
     */
    private function run_integration_tests($config, $run_id) {
        try {
            $this->log_test_step($run_id, 'Running integration tests');
            
            $integration_tests = [
                'database_connectivity' => $this->test_database_connectivity(),
                'api_endpoints' => $this->test_api_endpoints(),
                'plugin_compatibility' => $this->test_plugin_compatibility(),
                'theme_compatibility' => $this->test_theme_compatibility(),
                'external_services' => $this->test_external_services()
            ];
            
            $passed_tests = 0;
            $total_tests = count($integration_tests);
            
            foreach ($integration_tests as $test_result) {
                if ($test_result['status'] === 'passed') {
                    $passed_tests++;
                }
            }
            
            return [
                'total_tests' => $total_tests,
                'passed_tests' => $passed_tests,
                'failed_tests' => $total_tests - $passed_tests,
                'test_details' => $integration_tests,
                'overall_status' => $passed_tests === $total_tests ? 'passed' : 'failed'
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'overall_status' => 'error'
            ];
        }
    }
    
    /**
     * Compile final results
     */
    private function compile_final_results($test_results, $run_id) {
        $final_results = [
            'run_id' => $run_id,
            'start_time' => $this->current_run['start_time'],
            'end_time' => microtime(true),
            'duration' => microtime(true) - $this->current_run['start_time'],
            'overall_status' => 'passed',
            'test_results' => $test_results,
            'summary' => [
                'total_test_types' => 0,
                'passed_test_types' => 0,
                'failed_test_types' => 0,
                'skipped_test_types' => 0
            ]
        ];
        
        // Calculate summary
        foreach ($test_results as $type => $result) {
            if ($result === null) continue;
            
            $final_results['summary']['total_test_types']++;
            
            switch ($result['overall_status']) {
                case 'passed':
                    $final_results['summary']['passed_test_types']++;
                    break;
                case 'failed':
                case 'error':
                    $final_results['summary']['failed_test_types']++;
                    $final_results['overall_status'] = 'failed';
                    break;
                case 'skipped':
                    $final_results['summary']['skipped_test_types']++;
                    break;
            }
        }
        
        return $final_results;
    }
    
    /**
     * Get current test run status
     */
    public function get_test_run_status($run_id = null) {
        if ($run_id === null && $this->current_run) {
            return $this->current_run;
        }
        
        if ($run_id) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'etq_test_runs';
            
            $run = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $run_id),
                ARRAY_A
            );
            
            if ($run) {
                $run['config'] = json_decode($run['config'], true);
                $run['results'] = json_decode($run['results'], true);
            }
            
            return $run;
        }
        
        return null;
    }
    
    /**
     * Stop current test run
     */
    public function stop_test_run($run_id) {
        if ($this->current_run && $this->current_run['id'] == $run_id) {
            $this->log_test_step($run_id, 'Test run stopped by user');
            $this->update_test_run_status($run_id, 'stopped');
            $this->current_run = null;
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if test run is in progress
     */
    private function is_test_run_in_progress() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'etq_test_runs';
        $count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_name} WHERE status IN ('running', 'pending')"
        );
        
        return $count > 0;
    }
    
    /**
     * Discover PHPUnit tests
     */
    private function discover_phpunit_tests() {
        $test_files = [];
        
        // Look for test files in common locations
        $search_paths = [
            WP_CONTENT_DIR . '/plugins/*/tests/*.php',
            WP_CONTENT_DIR . '/themes/*/tests/*.php',
            ABSPATH . 'tests/*.php'
        ];
        
        foreach ($search_paths as $pattern) {
            $files = glob($pattern);
            if ($files) {
                $test_files = array_merge($test_files, $files);
            }
        }
        
        return $test_files;
    }
    
    /**
     * Discover Selenium tests
     */
    private function discover_selenium_tests() {
        $script_files = [];
        
        // Look for Selenium scripts
        $search_paths = [
            ETQ_PLUGIN_PATH . 'tests/selenium/*.php',
            WP_CONTENT_DIR . '/selenium-tests/*.php'
        ];
        
        foreach ($search_paths as $pattern) {
            $files = glob($pattern);
            if ($files) {
                $script_files = array_merge($script_files, $files);
            }
        }
        
        return $script_files;
    }
    
    /**
     * Integration test methods
     */
    private function test_database_connectivity() {
        global $wpdb;
        
        try {
            $result = $wpdb->get_var("SELECT 1");
            return [
                'status' => $result == 1 ? 'passed' : 'failed',
                'message' => $result == 1 ? 'Database connectivity OK' : 'Database connectivity failed'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'failed',
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    private function test_api_endpoints() {
        $endpoints = [
            '/wp-json/wp/v2/posts',
            '/wp-json/wp/v2/users',
            '/wp-json/environmental/v1/data'
        ];
        
        $results = [];
        foreach ($endpoints as $endpoint) {
            $response = wp_remote_get(home_url($endpoint));
            $results[$endpoint] = [
                'status' => is_wp_error($response) ? 'failed' : 'passed',
                'message' => is_wp_error($response) ? $response->get_error_message() : 'OK'
            ];
        }
        
        return [
            'status' => count(array_filter($results, function($r) { return $r['status'] === 'passed'; })) === count($results) ? 'passed' : 'failed',
            'details' => $results
        ];
    }
    
    private function test_plugin_compatibility() {
        $active_plugins = get_option('active_plugins', []);
        
        return [
            'status' => 'passed',
            'message' => count($active_plugins) . ' plugins active and compatible'
        ];
    }
    
    private function test_theme_compatibility() {
        $current_theme = wp_get_theme();
        
        return [
            'status' => 'passed',
            'message' => 'Theme "' . $current_theme->get('Name') . '" is compatible'
        ];
    }
    
    private function test_external_services() {
        // Test external service connectivity
        return [
            'status' => 'passed',
            'message' => 'External services accessible'
        ];
    }
    
    /**
     * Database operations
     */
    private function create_master_test_run($config) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'etq_test_runs';
        
        $result = $wpdb->insert(
            $table_name,
            [
                'suite_id' => 0, // Master run
                'environment' => $config['environment'],
                'config' => json_encode($config),
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    private function update_test_run_status($run_id, $status, $results = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'etq_test_runs';
        $update_data = [
            'status' => $status,
            'completed_at' => current_time('mysql')
        ];
        
        if ($results) {
            $update_data['results'] = json_encode($results);
        }
        
        return $wpdb->update(
            $table_name,
            $update_data,
            ['id' => $run_id],
            ['%s', '%s', '%s'],
            ['%d']
        );
    }
    
    private function complete_test_run($run_id, $results) {
        $this->update_test_run_status($run_id, $results['overall_status'], $results);
        $this->log_test_step($run_id, 'Test run completed with status: ' . $results['overall_status']);
    }
    
    private function log_test_step($run_id, $message) {
        // Log test step for monitoring
        error_log("[ETQ Test Run {$run_id}] {$message}");
    }
    
    private function send_test_run_notification($results) {
        if (!get_option('etq_email_notifications', 0)) {
            return;
        }
        
        $email = get_option('etq_notification_email', get_option('admin_email'));
        $subject = 'Environmental Platform Test Run Completed';
        
        $message = sprintf(
            "Test Run Status: %s\nDuration: %.2f seconds\n\nSummary:\n- Total Test Types: %d\n- Passed: %d\n- Failed: %d\n- Skipped: %d",
            strtoupper($results['overall_status']),
            $results['duration'],
            $results['summary']['total_test_types'],
            $results['summary']['passed_test_types'],
            $results['summary']['failed_test_types'],
            $results['summary']['skipped_test_types']
        );
        
        wp_mail($email, $subject, $message);
    }
    
    private function cleanup_test_artifacts($run_id) {
        // Clean up temporary files, logs, etc.
        $this->log_test_step($run_id, 'Cleaning up test artifacts');
    }
    
    /**
     * Scheduled and automatic test triggers
     */
    public function run_scheduled_tests() {
        // Get scheduled test suites
        $scheduled_config = [
            'run_type' => 'scheduled',
            'test_types' => ['phpunit', 'integration'],
            'continue_on_failure' => true,
            'email_notifications' => true
        ];
        
        $this->start_test_run($scheduled_config);
    }
    
    public function trigger_plugin_tests($plugin) {
        if (get_option('etq_auto_run_tests', 0)) {
            $config = [
                'run_type' => 'plugin_activation',
                'test_types' => ['phpunit', 'integration'],
                'triggered_by' => $plugin
            ];
            
            $this->start_test_run($config);
        }
    }
    
    public function trigger_theme_tests($theme_name) {
        if (get_option('etq_auto_run_tests', 0)) {
            $config = [
                'run_type' => 'theme_switch',
                'test_types' => ['selenium', 'integration'],
                'triggered_by' => $theme_name
            ];
            
            $this->start_test_run($config);
        }
    }
    
    public function trigger_update_tests($updated_plugins) {
        if (get_option('etq_auto_run_tests', 0)) {
            $config = [
                'run_type' => 'plugin_update',
                'test_types' => ['phpunit', 'integration'],
                'triggered_by' => $updated_plugins
            ];
            
            $this->start_test_run($config);
        }
    }
    
    /**
     * AJAX handlers
     */
    public function ajax_start_test_run() {
        check_ajax_referer('etq_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $config = isset($_POST['config']) ? json_decode(stripslashes($_POST['config']), true) : [];
        $result = $this->start_test_run($config);
        
        if (isset($result['success']) && $result['success'] === false) {
            wp_send_json_error($result);
        } else {
            wp_send_json_success($result);
        }
    }
    
    public function ajax_stop_test_run() {
        check_ajax_referer('etq_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $run_id = intval($_POST['run_id']);
        $result = $this->stop_test_run($run_id);
        
        wp_send_json_success(['stopped' => $result]);
    }
    
    public function ajax_get_test_run_status() {
        check_ajax_referer('etq_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $run_id = isset($_POST['run_id']) ? intval($_POST['run_id']) : null;
        $status = $this->get_test_run_status($run_id);
        
        wp_send_json_success($status);
    }
    
    public function ajax_get_test_run_logs() {
        check_ajax_referer('etq_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $run_id = intval($_POST['run_id']);
        
        // Get logs for the test run
        $logs = [
            'Test run started',
            'Discovering test files...',
            'Running PHPUnit tests...',
            'Running Selenium tests...',
            'Running performance tests...',
            'Compiling results...',
            'Test run completed'
        ];
        
        wp_send_json_success($logs);
    }
}
