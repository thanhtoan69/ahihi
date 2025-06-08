<?php
/**
 * PHPUnit Test Manager for Environmental Testing & QA
 * 
 * Manages PHPUnit test execution, configuration, and results
 */

if (!defined('ABSPATH')) {
    exit;
}

class ETQ_PHPUnit_Manager {
    
    /**
     * Database instance
     */
    private $database;
    
    /**
     * PHPUnit executable path
     */
    private $phpunit_path;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new ETQ_Database();
        $this->phpunit_path = $this->get_phpunit_path();
        
        add_action('etq_run_phpunit_tests', array($this, 'run_scheduled_tests'));
    }
    
    /**
     * Get PHPUnit executable path
     */
    private function get_phpunit_path() {
        $config = $this->get_phpunit_config();
        
        if (isset($config['phpunit_path']) && file_exists($config['phpunit_path'])) {
            return $config['phpunit_path'];
        }
        
        // Try common locations
        $common_paths = array(
            'vendor/bin/phpunit',
            'vendor/phpunit/phpunit/phpunit',
            '/usr/local/bin/phpunit',
            '/usr/bin/phpunit',
            'phpunit'
        );
        
        foreach ($common_paths as $path) {
            if (file_exists(ABSPATH . $path) || $this->command_exists($path)) {
                return $path;
            }
        }
        
        return 'phpunit'; // Fallback to system PATH
    }
    
    /**
     * Check if command exists in system PATH
     */
    private function command_exists($command) {
        $return_var = null;
        $output = null;
        exec("which $command", $output, $return_var);
        return $return_var === 0;
    }
    
    /**
     * Get PHPUnit configuration
     */
    private function get_phpunit_config() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'etq_configurations';
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT configuration FROM $table WHERE type = 'phpunit' AND is_default = 1",
        ));
        
        if ($result) {
            return json_decode($result->configuration, true);
        }
        
        return array(
            'phpunit_path' => 'vendor/bin/phpunit',
            'configuration_file' => 'phpunit.xml',
            'coverage_format' => 'html',
            'coverage_target' => 80,
            'memory_limit' => '256M',
            'time_limit' => 300
        );
    }
    
    /**
     * Generate PHPUnit configuration file
     */
    public function generate_phpunit_config($suite_id = null) {
        $config = $this->get_phpunit_config();
        
        // Get test directories
        $test_directories = $this->get_test_directories($suite_id);
        
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        // Root element
        $phpunit = $xml->createElement('phpunit');
        $phpunit->setAttribute('bootstrap', $config['bootstrap'] ?? 'wp-tests-config.php');
        $phpunit->setAttribute('colors', 'true');
        $phpunit->setAttribute('convertErrorsToExceptions', 'true');
        $phpunit->setAttribute('convertNoticesToExceptions', 'true');
        $phpunit->setAttribute('convertWarningsToExceptions', 'true');
        $phpunit->setAttribute('stopOnFailure', 'false');
        $phpunit->setAttribute('timeoutForSmallTests', '10');
        $phpunit->setAttribute('timeoutForMediumTests', '30');
        $phpunit->setAttribute('timeoutForLargeTests', '60');
        $xml->appendChild($phpunit);
        
        // Test suites
        $testsuites = $xml->createElement('testsuites');
        $phpunit->appendChild($testsuites);
        
        foreach ($test_directories as $name => $directory) {
            $testsuite = $xml->createElement('testsuite');
            $testsuite->setAttribute('name', $name);
            $testsuites->appendChild($testsuite);
            
            $dir = $xml->createElement('directory');
            $dir->setAttribute('suffix', 'Test.php');
            $dir->nodeValue = $directory;
            $testsuite->appendChild($dir);
        }
        
        // Coverage filter
        if (isset($config['coverage_format'])) {
            $filter = $xml->createElement('filter');
            $phpunit->appendChild($filter);
            
            $whitelist = $xml->createElement('whitelist');
            $filter->appendChild($whitelist);
            
            $coverage_dirs = array(
                'wp-content/plugins/environmental-*',
                'wp-content/themes/environmental-*'
            );
            
            foreach ($coverage_dirs as $dir) {
                $directory = $xml->createElement('directory');
                $directory->setAttribute('suffix', '.php');
                $directory->nodeValue = $dir;
                $whitelist->appendChild($directory);
            }
            
            // Exclude test directories
            $exclude = $xml->createElement('exclude');
            $whitelist->appendChild($exclude);
            
            foreach ($test_directories as $dir) {
                $exclude_dir = $xml->createElement('directory');
                $exclude_dir->nodeValue = $dir;
                $exclude->appendChild($exclude_dir);
            }
        }
        
        // Logging
        $logging = $xml->createElement('logging');
        $phpunit->appendChild($logging);
        
        // Coverage HTML report
        if (isset($config['coverage_format']) && $config['coverage_format'] === 'html') {
            $log = $xml->createElement('log');
            $log->setAttribute('type', 'coverage-html');
            $log->setAttribute('target', 'tests/coverage');
            $logging->appendChild($log);
        }
        
        // Coverage XML report
        $log_xml = $xml->createElement('log');
        $log_xml->setAttribute('type', 'coverage-xml');
        $log_xml->setAttribute('target', 'tests/coverage-xml');
        $logging->appendChild($log_xml);
        
        // JUnit XML report
        $log_junit = $xml->createElement('log');
        $log_junit->setAttribute('type', 'junit');
        $log_junit->setAttribute('target', 'tests/results.xml');
        $logging->appendChild($log_junit);
        
        // Save configuration file
        $config_path = ABSPATH . 'phpunit.xml';
        $xml->save($config_path);
        
        return $config_path;
    }
    
    /**
     * Get test directories for configuration
     */
    private function get_test_directories($suite_id = null) {
        $directories = array();
        
        if ($suite_id) {
            // Get specific suite tests
            $tests = $this->database->get_tests($suite_id);
            foreach ($tests as $test) {
                if ($test->test_file) {
                    $dir = dirname($test->test_file);
                    $directories[basename($dir)] = $dir;
                }
            }
        } else {
            // Default test directories
            $directories = array(
                'Core Tests' => 'tests/core',
                'Plugin Tests' => 'tests/plugins',
                'Theme Tests' => 'tests/themes',
                'Integration Tests' => 'tests/integration',
                'Performance Tests' => 'tests/performance'
            );
        }
        
        return $directories;
    }
    
    /**
     * Run PHPUnit tests
     */
    public function run_tests($suite_id = null, $test_id = null) {
        $start_time = microtime(true);
        
        // Create test run record
        $run_data = array(
            'suite_id' => $suite_id,
            'test_id' => $test_id,
            'run_type' => $test_id ? 'single' : 'suite',
            'environment' => $this->get_current_environment(),
            'status' => 'running',
            'start_time' => current_time('mysql'),
            'created_by' => get_current_user_id()
        );
        
        $run_id = $this->database->save_test_run($run_data);
        
        if (!$run_id) {
            return array(
                'success' => false,
                'message' => 'Failed to create test run record'
            );
        }
        
        try {
            // Generate PHPUnit configuration
            $config_file = $this->generate_phpunit_config($suite_id);
            
            // Build command
            $command = $this->build_phpunit_command($config_file, $suite_id, $test_id);
            
            // Execute tests
            $output = array();
            $return_code = 0;
            
            exec($command . ' 2>&1', $output, $return_code);
            
            $end_time = microtime(true);
            $duration = ($end_time - $start_time) * 1000; // Convert to milliseconds
            
            // Parse results
            $results = $this->parse_phpunit_output($output);
            
            // Update test run
            $update_data = array(
                'status' => $return_code === 0 ? 'completed' : 'failed',
                'end_time' => current_time('mysql'),
                'duration' => $duration,
                'total_tests' => $results['total_tests'],
                'passed_tests' => $results['passed_tests'],
                'failed_tests' => $results['failed_tests'],
                'skipped_tests' => $results['skipped_tests'],
                'error_count' => $results['error_count'],
                'warning_count' => $results['warning_count'],
                'coverage_percentage' => $results['coverage_percentage'],
                'memory_usage' => $results['memory_usage'],
                'execution_log' => implode("\n", $output),
                'error_log' => $return_code !== 0 ? implode("\n", $output) : null
            );
            
            $this->database->update_test_run($run_id, $update_data);
            
            // Save individual test results
            $this->save_test_results($run_id, $results['tests']);
            
            // Parse coverage report
            $this->parse_coverage_report($run_id);
            
            return array(
                'success' => $return_code === 0,
                'run_id' => $run_id,
                'results' => $results,
                'message' => $return_code === 0 ? 'Tests completed successfully' : 'Some tests failed',
                'output' => $output
            );
            
        } catch (Exception $e) {
            // Update test run with error
            $this->database->update_test_run($run_id, array(
                'status' => 'failed',
                'end_time' => current_time('mysql'),
                'error_log' => $e->getMessage()
            ));
            
            return array(
                'success' => false,
                'run_id' => $run_id,
                'message' => 'Test execution failed: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Build PHPUnit command
     */
    private function build_phpunit_command($config_file, $suite_id = null, $test_id = null) {
        $config = $this->get_phpunit_config();
        
        $command = $this->phpunit_path;
        $command .= ' --configuration ' . escapeshellarg($config_file);
        $command .= ' --log-junit tests/results.xml';
        
        // Coverage options
        if (isset($config['coverage_format'])) {
            if ($config['coverage_format'] === 'html') {
                $command .= ' --coverage-html tests/coverage';
            }
            $command .= ' --coverage-xml tests/coverage-xml';
        }
        
        // Memory limit
        if (isset($config['memory_limit'])) {
            $command .= ' --memory-limit ' . $config['memory_limit'];
        }
        
        // Specific test or suite
        if ($test_id) {
            $test = $this->database->get_tests(null, 'active');
            foreach ($test as $t) {
                if ($t->id == $test_id && $t->test_file) {
                    $command .= ' ' . escapeshellarg($t->test_file);
                    break;
                }
            }
        } elseif ($suite_id) {
            $tests = $this->database->get_tests($suite_id);
            $test_files = array();
            foreach ($tests as $test) {
                if ($test->test_file) {
                    $test_files[] = $test->test_file;
                }
            }
            if (!empty($test_files)) {
                $command .= ' ' . implode(' ', array_map('escapeshellarg', $test_files));
            }
        }
        
        return $command;
    }
    
    /**
     * Parse PHPUnit output
     */
    private function parse_phpunit_output($output) {
        $results = array(
            'total_tests' => 0,
            'passed_tests' => 0,
            'failed_tests' => 0,
            'skipped_tests' => 0,
            'error_count' => 0,
            'warning_count' => 0,
            'coverage_percentage' => 0.0,
            'memory_usage' => 0,
            'tests' => array()
        );
        
        $current_test = null;
        
        foreach ($output as $line) {
            // Parse test results summary
            if (preg_match('/Tests: (\d+), Assertions: (\d+)/', $line, $matches)) {
                $results['total_tests'] = (int)$matches[1];
            }
            
            if (preg_match('/Failures: (\d+)/', $line, $matches)) {
                $results['failed_tests'] = (int)$matches[1];
            }
            
            if (preg_match('/Errors: (\d+)/', $line, $matches)) {
                $results['error_count'] = (int)$matches[1];
            }
            
            if (preg_match('/Skipped: (\d+)/', $line, $matches)) {
                $results['skipped_tests'] = (int)$matches[1];
            }
            
            // Parse coverage
            if (preg_match('/Lines:\s+(\d+\.\d+)%/', $line, $matches)) {
                $results['coverage_percentage'] = (float)$matches[1];
            }
            
            // Parse memory usage
            if (preg_match('/Memory: (\d+\.\d+) MB/', $line, $matches)) {
                $results['memory_usage'] = (float)$matches[1] * 1024 * 1024; // Convert to bytes
            }
            
            // Parse individual test results
            if (preg_match('/^(\w+::\w+)\s+(PASS|FAIL|SKIP|ERROR)\s+\(([0-9.]+)s\)/', $line, $matches)) {
                $test_name = $matches[1];
                $status = strtolower($matches[2]);
                $execution_time = (float)$matches[3];
                
                $results['tests'][] = array(
                    'test_name' => $test_name,
                    'test_class' => strstr($test_name, '::', true),
                    'test_method' => substr(strstr($test_name, '::'), 2),
                    'status' => $status === 'pass' ? 'pass' : $status,
                    'execution_time' => $execution_time,
                    'message' => null,
                    'error_message' => null
                );
            }
        }
        
        $results['passed_tests'] = $results['total_tests'] - $results['failed_tests'] - $results['error_count'] - $results['skipped_tests'];
        
        return $results;
    }
    
    /**
     * Save individual test results
     */
    private function save_test_results($run_id, $tests) {
        foreach ($tests as $test) {
            $test['run_id'] = $run_id;
            $test['test_id'] = 0; // Will be updated if we can match to existing test
            $this->database->save_test_result($test);
        }
    }
    
    /**
     * Parse coverage report
     */
    private function parse_coverage_report($run_id) {
        $coverage_xml_path = ABSPATH . 'tests/coverage-xml/index.xml';
        
        if (!file_exists($coverage_xml_path)) {
            return false;
        }
        
        $xml = simplexml_load_file($coverage_xml_path);
        
        if (!$xml) {
            return false;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'etq_coverage_reports';
        
        foreach ($xml->project->file as $file) {
            $file_path = (string)$file['name'];
            $lines_total = (int)$file->metrics['statements'];
            $lines_covered = (int)$file->metrics['coveredstatements'];
            $lines_percentage = $lines_total > 0 ? ($lines_covered / $lines_total) * 100 : 0;
            
            $functions_total = (int)$file->metrics['methods'];
            $functions_covered = (int)$file->metrics['coveredmethods'];
            $functions_percentage = $functions_total > 0 ? ($functions_covered / $functions_total) * 100 : 0;
            
            $classes_total = (int)$file->metrics['classes'];
            $classes_covered = (int)$file->metrics['coveredclasses'];
            $classes_percentage = $classes_total > 0 ? ($classes_covered / $classes_total) * 100 : 0;
            
            $coverage_data = array(
                'run_id' => $run_id,
                'file_path' => $file_path,
                'lines_total' => $lines_total,
                'lines_covered' => $lines_covered,
                'lines_percentage' => $lines_percentage,
                'functions_total' => $functions_total,
                'functions_covered' => $functions_covered,
                'functions_percentage' => $functions_percentage,
                'classes_total' => $classes_total,
                'classes_covered' => $classes_covered,
                'classes_percentage' => $classes_percentage
            );
            
            $wpdb->insert($table, $coverage_data);
        }
        
        return true;
    }
    
    /**
     * Get current environment
     */
    private function get_current_environment() {
        if (defined('WP_ENVIRONMENT_TYPE')) {
            return WP_ENVIRONMENT_TYPE;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return 'development';
        }
        
        return 'production';
    }
    
    /**
     * Run scheduled tests
     */
    public function run_scheduled_tests() {
        // Get active test suites
        $suites = $this->database->get_test_suites('phpunit', 'active');
        
        foreach ($suites as $suite) {
            $this->run_tests($suite->id);
        }
    }
    
    /**
     * Install PHPUnit if not present
     */
    public function install_phpunit() {
        if (file_exists(ABSPATH . 'composer.json')) {
            // Install via Composer
            $command = 'cd ' . ABSPATH . ' && composer require --dev phpunit/phpunit';
            exec($command, $output, $return_code);
            
            return $return_code === 0;
        }
        
        return false;
    }
    
    /**
     * Validate PHPUnit installation
     */
    public function validate_installation() {
        $issues = array();
        
        // Check PHPUnit executable
        if (!$this->command_exists($this->phpunit_path)) {
            $issues[] = 'PHPUnit executable not found at: ' . $this->phpunit_path;
        }
        
        // Check test directories
        $test_dirs = array('tests', 'tests/core', 'tests/plugins');
        foreach ($test_dirs as $dir) {
            if (!is_dir(ABSPATH . $dir)) {
                $issues[] = 'Test directory missing: ' . $dir;
            }
        }
        
        // Check write permissions
        $write_dirs = array('tests/coverage', 'tests/results');
        foreach ($write_dirs as $dir) {
            $full_path = ABSPATH . $dir;
            if (!is_dir($full_path)) {
                wp_mkdir_p($full_path);
            }
            if (!is_writable($full_path)) {
                $issues[] = 'Directory not writable: ' . $dir;
            }
        }
        
        return array(
            'valid' => empty($issues),
            'issues' => $issues
        );
    }
}
