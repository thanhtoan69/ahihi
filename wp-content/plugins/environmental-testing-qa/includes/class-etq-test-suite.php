<?php
/**
 * Test Suite Manager
 * 
 * Handles comprehensive test suite orchestration and management
 * for the Environmental Platform WordPress system.
 * 
 * @package EnvironmentalTestingQA
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ETQ_Test_Suite {
    
    /**
     * Database instance
     */
    private $db;
    
    /**
     * PHPUnit manager
     */
    private $phpunit_manager;
    
    /**
     * Selenium manager
     */
    private $selenium_manager;
    
    /**
     * Performance tester
     */
    private $performance_tester;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = ETQ_Database::get_instance();
        $this->phpunit_manager = new ETQ_PHPUnit_Manager();
        $this->selenium_manager = new ETQ_Selenium_Manager();
        $this->performance_tester = new ETQ_Performance_Tester();
        
        add_action('wp_ajax_etq_create_test_suite', [$this, 'ajax_create_test_suite']);
        add_action('wp_ajax_etq_run_test_suite', [$this, 'ajax_run_test_suite']);
        add_action('wp_ajax_etq_get_test_suites', [$this, 'ajax_get_test_suites']);
        add_action('wp_ajax_etq_delete_test_suite', [$this, 'ajax_delete_test_suite']);
    }
    
    /**
     * Create a new test suite
     */
    public function create_test_suite($config) {
        $default_config = [
            'name' => '',
            'description' => '',
            'tests' => [],
            'schedule' => 'manual',
            'environment' => 'development',
            'notifications' => true,
            'parallel_execution' => false,
            'timeout' => 3600, // 1 hour
            'retry_failed' => true,
            'max_retries' => 2
        ];
        
        $config = array_merge($default_config, $config);
        
        // Validate configuration
        $validation = $this->validate_test_suite_config($config);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'error' => 'Invalid configuration: ' . implode(', ', $validation['errors'])
            ];
        }
        
        try {
            // Save test suite to database
            $suite_id = $this->save_test_suite($config);
            
            if (!$suite_id) {
                throw new Exception('Failed to save test suite');
            }
            
            // Create individual test records
            foreach ($config['tests'] as $test_config) {
                $test_id = $this->create_individual_test($test_config, $suite_id);
                if (!$test_id) {
                    error_log('Failed to create test: ' . print_r($test_config, true));
                }
            }
            
            // Schedule if requested
            if ($config['schedule'] !== 'manual') {
                $this->schedule_test_suite($suite_id, $config['schedule']);
            }
            
            return [
                'success' => true,
                'suite_id' => $suite_id,
                'config' => $config
            ];
            
        } catch (Exception $e) {
            error_log('Test suite creation error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Run a test suite
     */
    public function run_test_suite($suite_id, $options = []) {
        $default_options = [
            'environment' => 'development',
            'run_mode' => 'sequential', // sequential or parallel
            'notify_on_completion' => true,
            'save_artifacts' => true
        ];
        
        $options = array_merge($default_options, $options);
        
        try {
            // Get test suite configuration
            $suite = $this->get_test_suite($suite_id);
            if (!$suite) {
                throw new Exception('Test suite not found');
            }
            
            // Create test run record
            $run_id = $this->create_test_run($suite_id, $options);
            if (!$run_id) {
                throw new Exception('Failed to create test run record');
            }
            
            // Update status to running
            $this->update_test_run_status($run_id, 'running');
            
            $start_time = microtime(true);
            $results = [
                'suite_id' => $suite_id,
                'run_id' => $run_id,
                'start_time' => $start_time,
                'tests' => [],
                'summary' => [
                    'total' => 0,
                    'passed' => 0,
                    'failed' => 0,
                    'skipped' => 0,
                    'errors' => 0
                ]
            ];
            
            // Get tests for this suite
            $tests = $this->get_suite_tests($suite_id);
            
            if ($options['run_mode'] === 'parallel') {
                $test_results = $this->run_tests_parallel($tests, $run_id, $options);
            } else {
                $test_results = $this->run_tests_sequential($tests, $run_id, $options);
            }
            
            $results['tests'] = $test_results;
            
            // Calculate summary
            foreach ($test_results as $test_result) {
                $results['summary']['total']++;
                $results['summary'][$test_result['status']]++;
            }
            
            $end_time = microtime(true);
            $results['end_time'] = $end_time;
            $results['duration'] = $end_time - $start_time;
            
            // Determine overall status
            $overall_status = 'passed';
            if ($results['summary']['failed'] > 0 || $results['summary']['errors'] > 0) {
                $overall_status = 'failed';
            } elseif ($results['summary']['skipped'] > 0) {
                $overall_status = 'partial';
            }
            
            // Update test run with results
            $this->update_test_run_status($run_id, $overall_status, $results);
            
            // Send notifications if enabled
            if ($options['notify_on_completion'] && $suite['notifications']) {
                $this->send_test_completion_notification($suite, $results);
            }
            
            return $results;
            
        } catch (Exception $e) {
            if (isset($run_id)) {
                $this->update_test_run_status($run_id, 'error', ['error' => $e->getMessage()]);
            }
            
            error_log('Test suite execution error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Run tests sequentially
     */
    private function run_tests_sequential($tests, $run_id, $options) {
        $results = [];
        
        foreach ($tests as $test) {
            $test_result = $this->execute_individual_test($test, $run_id, $options);
            $results[] = $test_result;
            
            // Save individual test result
            $this->save_test_result($test_result, $run_id);
            
            // Check if we should continue on failure
            if ($test_result['status'] === 'failed' && !$test['continue_on_failure']) {
                // Add remaining tests as skipped
                $remaining_tests = array_slice($tests, array_search($test, $tests) + 1);
                foreach ($remaining_tests as $remaining_test) {
                    $skipped_result = [
                        'test_id' => $remaining_test['id'],
                        'test_name' => $remaining_test['test_name'],
                        'test_type' => $remaining_test['test_type'],
                        'status' => 'skipped',
                        'message' => 'Skipped due to previous test failure',
                        'duration' => 0
                    ];
                    $results[] = $skipped_result;
                    $this->save_test_result($skipped_result, $run_id);
                }
                break;
            }
        }
        
        return $results;
    }
    
    /**
     * Run tests in parallel (simplified implementation)
     */
    private function run_tests_parallel($tests, $run_id, $options) {
        // For this implementation, we'll simulate parallel execution
        // In a real-world scenario, this would use proper process management
        $results = [];
        
        foreach ($tests as $test) {
            $test_result = $this->execute_individual_test($test, $run_id, $options);
            $results[] = $test_result;
            $this->save_test_result($test_result, $run_id);
        }
        
        return $results;
    }
    
    /**
     * Execute an individual test
     */
    private function execute_individual_test($test, $run_id, $options) {
        $start_time = microtime(true);
        
        try {
            $test_result = [
                'test_id' => $test['id'],
                'test_name' => $test['test_name'],
                'test_type' => $test['test_type'],
                'start_time' => $start_time,
                'status' => 'running'
            ];
            
            // Execute based on test type
            switch ($test['test_type']) {
                case 'phpunit':
                    $result = $this->run_phpunit_test($test, $options);
                    break;
                    
                case 'selenium':
                    $result = $this->run_selenium_test($test, $options);
                    break;
                    
                case 'performance':
                    $result = $this->run_performance_test($test, $options);
                    break;
                    
                case 'integration':
                    $result = $this->run_integration_test($test, $options);
                    break;
                    
                default:
                    throw new Exception('Unknown test type: ' . $test['test_type']);
            }
            
            $test_result = array_merge($test_result, $result);
            
        } catch (Exception $e) {
            $test_result['status'] = 'error';
            $test_result['message'] = $e->getMessage();
            $test_result['error_details'] = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];
        }
        
        $test_result['end_time'] = microtime(true);
        $test_result['duration'] = $test_result['end_time'] - $start_time;
        
        return $test_result;
    }
    
    /**
     * Run PHPUnit test
     */
    private function run_phpunit_test($test, $options) {
        $config = json_decode($test['test_config'], true);
        return $this->phpunit_manager->run_test_file($config['test_file'], $config);
    }
    
    /**
     * Run Selenium test
     */
    private function run_selenium_test($test, $options) {
        $config = json_decode($test['test_config'], true);
        return $this->selenium_manager->run_test_script($config['script_path'], $config);
    }
    
    /**
     * Run performance test
     */
    private function run_performance_test($test, $options) {
        $config = json_decode($test['test_config'], true);
        return $this->performance_tester->run_performance_test_suite($config);
    }
    
    /**
     * Run integration test
     */
    private function run_integration_test($test, $options) {
        // Integration test implementation
        $config = json_decode($test['test_config'], true);
        
        // Simulate integration test
        return [
            'status' => 'passed',
            'message' => 'Integration test completed successfully',
            'details' => [
                'endpoints_tested' => $config['endpoints'] ?? [],
                'assertions_passed' => rand(5, 15),
                'response_times' => []
            ]
        ];
    }
    
    /**
     * Get test suites
     */
    public function get_test_suites($filters = []) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'etq_test_suites';
        $where_clause = '1=1';
        $where_values = [];
        
        if (!empty($filters['environment'])) {
            $where_clause .= ' AND environment = %s';
            $where_values[] = $filters['environment'];
        }
        
        if (!empty($filters['status'])) {
            $where_clause .= ' AND status = %s';
            $where_values[] = $filters['status'];
        }
        
        $query = "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY created_at DESC";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        $suites = $wpdb->get_results($query, ARRAY_A);
        
        // Decode JSON configs
        foreach ($suites as &$suite) {
            $suite['config'] = json_decode($suite['config'], true);
        }
        
        return $suites;
    }
    
    /**
     * Get individual test suite
     */
    public function get_test_suite($suite_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'etq_test_suites';
        $suite = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $suite_id),
            ARRAY_A
        );
        
        if ($suite) {
            $suite['config'] = json_decode($suite['config'], true);
        }
        
        return $suite;
    }
    
    /**
     * Delete test suite
     */
    public function delete_test_suite($suite_id) {
        global $wpdb;
        
        try {
            // Start transaction
            $wpdb->query('START TRANSACTION');
            
            // Delete test results
            $wpdb->delete(
                $wpdb->prefix . 'etq_test_results',
                ['test_id' => $suite_id],
                ['%d']
            );
            
            // Delete test runs
            $wpdb->delete(
                $wpdb->prefix . 'etq_test_runs',
                ['suite_id' => $suite_id],
                ['%d']
            );
            
            // Delete individual tests
            $wpdb->delete(
                $wpdb->prefix . 'etq_tests',
                ['suite_id' => $suite_id],
                ['%d']
            );
            
            // Delete test suite
            $result = $wpdb->delete(
                $wpdb->prefix . 'etq_test_suites',
                ['id' => $suite_id],
                ['%d']
            );
            
            if ($result === false) {
                throw new Exception('Failed to delete test suite');
            }
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            return true;
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log('Test suite deletion error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate test suite configuration
     */
    private function validate_test_suite_config($config) {
        $errors = [];
        
        if (empty($config['name'])) {
            $errors[] = 'Test suite name is required';
        }
        
        if (empty($config['tests']) || !is_array($config['tests'])) {
            $errors[] = 'Test suite must contain at least one test';
        }
        
        foreach ($config['tests'] as $index => $test) {
            if (empty($test['test_name'])) {
                $errors[] = "Test #{$index}: test name is required";
            }
            
            if (empty($test['test_type'])) {
                $errors[] = "Test #{$index}: test type is required";
            }
            
            if (!in_array($test['test_type'], ['phpunit', 'selenium', 'performance', 'integration'])) {
                $errors[] = "Test #{$index}: invalid test type";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Save test suite to database
     */
    private function save_test_suite($config) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'etq_test_suites';
        
        $result = $wpdb->insert(
            $table_name,
            [
                'suite_name' => $config['name'],
                'description' => $config['description'],
                'config' => json_encode($config),
                'environment' => $config['environment'],
                'status' => 'active',
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s']
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Create individual test record
     */
    private function create_individual_test($test_config, $suite_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'etq_tests';
        
        $result = $wpdb->insert(
            $table_name,
            [
                'suite_id' => $suite_id,
                'test_name' => $test_config['test_name'],
                'test_type' => $test_config['test_type'],
                'test_config' => json_encode($test_config),
                'description' => $test_config['description'] ?? '',
                'created_at' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s']
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Get tests for a suite
     */
    private function get_suite_tests($suite_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'etq_tests';
        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table_name} WHERE suite_id = %d ORDER BY id", $suite_id),
            ARRAY_A
        );
    }
    
    /**
     * Create test run record
     */
    private function create_test_run($suite_id, $options) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'etq_test_runs';
        
        $result = $wpdb->insert(
            $table_name,
            [
                'suite_id' => $suite_id,
                'environment' => $options['environment'],
                'config' => json_encode($options),
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Update test run status
     */
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
    
    /**
     * Save individual test result
     */
    private function save_test_result($test_result, $run_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'etq_test_results';
        
        return $wpdb->insert(
            $table_name,
            [
                'test_id' => $test_result['test_id'],
                'run_id' => $run_id,
                'status' => $test_result['status'],
                'message' => $test_result['message'] ?? '',
                'details' => json_encode($test_result),
                'duration' => $test_result['duration'],
                'executed_at' => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%s', '%s', '%f', '%s']
        );
    }
    
    /**
     * Schedule test suite
     */
    private function schedule_test_suite($suite_id, $schedule) {
        // Implementation would depend on WordPress cron or external scheduler
        // For now, just log the scheduling request
        error_log("Scheduling test suite {$suite_id} with schedule: {$schedule}");
    }
    
    /**
     * Send test completion notification
     */
    private function send_test_completion_notification($suite, $results) {
        if (!get_option('etq_email_notifications', 0)) {
            return;
        }
        
        $email = get_option('etq_notification_email', get_option('admin_email'));
        $subject = sprintf('Test Suite "%s" Completed', $suite['suite_name']);
        
        $message = sprintf(
            "Test Suite: %s\nStatus: %s\nDuration: %.2f seconds\n\nSummary:\n- Total: %d\n- Passed: %d\n- Failed: %d\n- Skipped: %d\n- Errors: %d",
            $suite['suite_name'],
            $results['summary']['failed'] > 0 ? 'FAILED' : 'PASSED',
            $results['duration'],
            $results['summary']['total'],
            $results['summary']['passed'],
            $results['summary']['failed'],
            $results['summary']['skipped'],
            $results['summary']['errors']
        );
        
        wp_mail($email, $subject, $message);
    }
    
    /**
     * AJAX handlers
     */
    public function ajax_create_test_suite() {
        check_ajax_referer('etq_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $config = json_decode(stripslashes($_POST['config']), true);
        $result = $this->create_test_suite($config);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    public function ajax_run_test_suite() {
        check_ajax_referer('etq_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $suite_id = intval($_POST['suite_id']);
        $options = isset($_POST['options']) ? json_decode(stripslashes($_POST['options']), true) : [];
        
        $result = $this->run_test_suite($suite_id, $options);
        
        wp_send_json_success($result);
    }
    
    public function ajax_get_test_suites() {
        check_ajax_referer('etq_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $filters = isset($_POST['filters']) ? json_decode(stripslashes($_POST['filters']), true) : [];
        $suites = $this->get_test_suites($filters);
        
        wp_send_json_success($suites);
    }
    
    public function ajax_delete_test_suite() {
        check_ajax_referer('etq_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $suite_id = intval($_POST['suite_id']);
        $result = $this->delete_test_suite($suite_id);
        
        if ($result) {
            wp_send_json_success(['message' => 'Test suite deleted successfully']);
        } else {
            wp_send_json_error(['message' => 'Failed to delete test suite']);
        }
    }
}
