<?php
/**
 * Selenium Test Manager for Environmental Testing & QA
 * 
 * Manages Selenium WebDriver tests for frontend automation testing
 */

if (!defined('ABSPATH')) {
    exit;
}

class ETQ_Selenium_Manager {
    
    /**
     * Database instance
     */
    private $database;
    
    /**
     * Selenium Hub URL
     */
    private $hub_url;
    
    /**
     * Test configurations
     */
    private $config;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new ETQ_Database();
        $this->config = $this->get_selenium_config();
        $this->hub_url = $this->config['hub_url'] ?? 'http://localhost:4444/wd/hub';
        
        add_action('etq_run_selenium_tests', array($this, 'run_scheduled_tests'));
    }
    
    /**
     * Get Selenium configuration
     */
    private function get_selenium_config() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'etq_configurations';
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT configuration FROM $table WHERE type = 'selenium' AND is_default = 1"
        ));
        
        if ($result) {
            return json_decode($result->configuration, true);
        }
        
        return array(
            'hub_url' => 'http://localhost:4444/wd/hub',
            'browsers' => array('chrome', 'firefox'),
            'implicit_wait' => 10,
            'page_load_timeout' => 30,
            'screenshot_path' => 'tests/screenshots',
            'video_path' => 'tests/videos',
            'resolution' => '1920x1080'
        );
    }
    
    /**
     * Check if Selenium is available
     */
    public function is_selenium_available() {
        $context = stream_context_create(array(
            'http' => array(
                'timeout' => 5,
                'method' => 'GET'
            )
        ));
        
        $status_url = str_replace('/wd/hub', '/status', $this->hub_url);
        $response = @file_get_contents($status_url, false, $context);
        
        if ($response) {
            $data = json_decode($response, true);
            return isset($data['value']['ready']) && $data['value']['ready'] === true;
        }
        
        return false;
    }
    
    /**
     * Run Selenium tests
     */
    public function run_tests($suite_id = null, $test_id = null) {
        if (!$this->is_selenium_available()) {
            return array(
                'success' => false,
                'message' => 'Selenium Grid is not available at: ' . $this->hub_url
            );
        }
        
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
            // Get tests to run
            $tests = $this->get_tests_to_run($suite_id, $test_id);
            
            if (empty($tests)) {
                throw new Exception('No tests found to run');
            }
            
            $results = array(
                'total_tests' => count($tests),
                'passed_tests' => 0,
                'failed_tests' => 0,
                'skipped_tests' => 0,
                'error_count' => 0,
                'tests' => array()
            );
            
            // Run each test
            foreach ($tests as $test) {
                $test_result = $this->run_single_test($test, $run_id);
                $results['tests'][] = $test_result;
                
                switch ($test_result['status']) {
                    case 'pass':
                        $results['passed_tests']++;
                        break;
                    case 'fail':
                        $results['failed_tests']++;
                        break;
                    case 'skip':
                        $results['skipped_tests']++;
                        break;
                    case 'error':
                        $results['error_count']++;
                        break;
                }
            }
            
            $end_time = microtime(true);
            $duration = ($end_time - $start_time) * 1000;
            
            // Update test run
            $update_data = array(
                'status' => $results['failed_tests'] === 0 && $results['error_count'] === 0 ? 'completed' : 'failed',
                'end_time' => current_time('mysql'),
                'duration' => $duration,
                'total_tests' => $results['total_tests'],
                'passed_tests' => $results['passed_tests'],
                'failed_tests' => $results['failed_tests'],
                'skipped_tests' => $results['skipped_tests'],
                'error_count' => $results['error_count']
            );
            
            $this->database->update_test_run($run_id, $update_data);
            
            return array(
                'success' => $results['failed_tests'] === 0 && $results['error_count'] === 0,
                'run_id' => $run_id,
                'results' => $results,
                'message' => $results['failed_tests'] === 0 && $results['error_count'] === 0 
                    ? 'All tests passed' 
                    : sprintf('%d tests failed, %d errors', $results['failed_tests'], $results['error_count'])
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
     * Get tests to run
     */
    private function get_tests_to_run($suite_id = null, $test_id = null) {
        if ($test_id) {
            $tests = $this->database->get_tests(null, 'active');
            foreach ($tests as $test) {
                if ($test->id == $test_id) {
                    return array($test);
                }
            }
            return array();
        } elseif ($suite_id) {
            return $this->database->get_tests($suite_id, 'active');
        } else {
            // Get all Selenium tests
            $suites = $this->database->get_test_suites('selenium', 'active');
            $tests = array();
            foreach ($suites as $suite) {
                $suite_tests = $this->database->get_tests($suite->id, 'active');
                $tests = array_merge($tests, $suite_tests);
            }
            return $tests;
        }
    }
    
    /**
     * Run a single Selenium test
     */
    private function run_single_test($test, $run_id) {
        $start_time = microtime(true);
        
        try {
            // Get Selenium script
            $script = $this->get_selenium_script($test->id);
            
            if (!$script) {
                throw new Exception('No Selenium script found for test: ' . $test->name);
            }
            
            // Initialize WebDriver
            $driver = $this->create_webdriver($script->browser ?? 'chrome');
            
            // Set timeouts
            $driver->manage()->timeouts()->implicitlyWait($this->config['implicit_wait'] ?? 10);
            $driver->manage()->timeouts()->pageLoadTimeout($this->config['page_load_timeout'] ?? 30);
            
            // Set window size
            $resolution = explode('x', $script->resolution ?? $this->config['resolution'] ?? '1920x1080');
            $driver->manage()->window()->setSize(new WebDriverDimension((int)$resolution[0], (int)$resolution[1]));
            
            // Execute test script
            $result = $this->execute_test_script($driver, $script, $test);
            
            $driver->quit();
            
            $end_time = microtime(true);
            $execution_time = ($end_time - $start_time);
            
            // Save test result
            $test_result_data = array(
                'run_id' => $run_id,
                'test_id' => $test->id,
                'test_name' => $test->name,
                'test_class' => $test->test_class,
                'test_method' => $test->test_method,
                'status' => $result['success'] ? 'pass' : 'fail',
                'execution_time' => $execution_time,
                'message' => $result['message'],
                'error_message' => $result['error'] ?? null,
                'screenshot_path' => $result['screenshot'] ?? null,
                'output' => json_encode($result['output'] ?? array())
            );
            
            $this->database->save_test_result($test_result_data);
            
            return $test_result_data;
            
        } catch (Exception $e) {
            $end_time = microtime(true);
            $execution_time = ($end_time - $start_time);
            
            // Take screenshot on error if driver is available
            $screenshot_path = null;
            if (isset($driver)) {
                try {
                    $screenshot_path = $this->take_screenshot($driver, $test->name . '_error');
                    $driver->quit();
                } catch (Exception $screenshot_error) {
                    // Ignore screenshot errors
                }
            }
            
            $test_result_data = array(
                'run_id' => $run_id,
                'test_id' => $test->id,
                'test_name' => $test->name,
                'test_class' => $test->test_class,
                'test_method' => $test->test_method,
                'status' => 'error',
                'execution_time' => $execution_time,
                'message' => 'Test execution failed',
                'error_message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
                'screenshot_path' => $screenshot_path
            );
            
            $this->database->save_test_result($test_result_data);
            
            return $test_result_data;
        }
    }
    
    /**
     * Create WebDriver instance
     */
    private function create_webdriver($browser = 'chrome') {
        require_once ETQ_PLUGIN_PATH . 'vendor/autoload.php';
        
        $capabilities = array();
        
        switch (strtolower($browser)) {
            case 'chrome':
                $options = new ChromeOptions();
                $options->addArguments(array(
                    '--no-sandbox',
                    '--disable-dev-shm-usage',
                    '--disable-gpu',
                    '--window-size=1920,1080'
                ));
                $capabilities = DesiredCapabilities::chrome();
                $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
                break;
                
            case 'firefox':
                $profile = new FirefoxProfile();
                $profile->setPreference('browser.download.folderList', 2);
                $capabilities = DesiredCapabilities::firefox();
                $capabilities->setCapability(FirefoxDriver::PROFILE, $profile);
                break;
                
            case 'edge':
                $capabilities = DesiredCapabilities::microsoftEdge();
                break;
                
            default:
                $capabilities = DesiredCapabilities::chrome();
        }
        
        return RemoteWebDriver::create($this->hub_url, $capabilities);
    }
    
    /**
     * Get Selenium script for test
     */
    private function get_selenium_script($test_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'etq_selenium_scripts';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE test_id = %d",
            $test_id
        ));
    }
    
    /**
     * Execute test script
     */
    private function execute_test_script($driver, $script, $test) {
        $result = array(
            'success' => false,
            'message' => '',
            'error' => null,
            'output' => array(),
            'screenshot' => null
        );
        
        try {
            // Parse script content
            $steps = json_decode($script->script_content, true);
            
            if (!$steps) {
                throw new Exception('Invalid script content');
            }
            
            // Execute each step
            foreach ($steps as $step) {
                $this->execute_script_step($driver, $step, $result);
            }
            
            // Check expected elements if defined
            if ($script->expected_elements) {
                $expected = json_decode($script->expected_elements, true);
                $this->verify_expected_elements($driver, $expected, $result);
            }
            
            $result['success'] = true;
            $result['message'] = 'Test completed successfully';
            
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
            $result['message'] = 'Test failed: ' . $e->getMessage();
            
            // Take screenshot on failure
            if ($script->screenshot_on_failure) {
                $result['screenshot'] = $this->take_screenshot($driver, $test->name . '_failure');
            }
        }
        
        return $result;
    }
    
    /**
     * Execute a single script step
     */
    private function execute_script_step($driver, $step, &$result) {
        $action = $step['action'] ?? '';
        $target = $step['target'] ?? '';
        $value = $step['value'] ?? '';
        $timeout = $step['timeout'] ?? 5;
        
        switch ($action) {
            case 'open':
                $driver->get($target);
                $result['output'][] = "Opened: $target";
                break;
                
            case 'click':
                $element = $this->find_element($driver, $target, $timeout);
                $element->click();
                $result['output'][] = "Clicked: $target";
                break;
                
            case 'type':
                $element = $this->find_element($driver, $target, $timeout);
                $element->clear();
                $element->sendKeys($value);
                $result['output'][] = "Typed '$value' into: $target";
                break;
                
            case 'select':
                $element = $this->find_element($driver, $target, $timeout);
                $select = new WebDriverSelect($element);
                if (is_numeric($value)) {
                    $select->selectByIndex((int)$value);
                } else {
                    $select->selectByValue($value);
                }
                $result['output'][] = "Selected '$value' from: $target";
                break;
                
            case 'wait':
                sleep((int)$value);
                $result['output'][] = "Waited: {$value}s";
                break;
                
            case 'assert_text':
                $element = $this->find_element($driver, $target, $timeout);
                $actual_text = $element->getText();
                if (strpos($actual_text, $value) === false) {
                    throw new Exception("Expected text '$value' not found. Actual: '$actual_text'");
                }
                $result['output'][] = "Verified text '$value' in: $target";
                break;
                
            case 'assert_element':
                $this->find_element($driver, $target, $timeout);
                $result['output'][] = "Verified element exists: $target";
                break;
                
            case 'assert_title':
                $actual_title = $driver->getTitle();
                if (strpos($actual_title, $value) === false) {
                    throw new Exception("Expected title '$value' not found. Actual: '$actual_title'");
                }
                $result['output'][] = "Verified title contains: $value";
                break;
                
            case 'screenshot':
                $screenshot_path = $this->take_screenshot($driver, $value ?: 'step_' . time());
                $result['output'][] = "Screenshot taken: $screenshot_path";
                break;
                
            default:
                throw new Exception("Unknown action: $action");
        }
    }
    
    /**
     * Find element with various selectors
     */
    private function find_element($driver, $selector, $timeout = 5) {
        $wait = new WebDriverWait($driver, $timeout);
        
        // Determine selector type and create appropriate By
        if (strpos($selector, 'id=') === 0) {
            $by = WebDriverBy::id(substr($selector, 3));
        } elseif (strpos($selector, 'name=') === 0) {
            $by = WebDriverBy::name(substr($selector, 5));
        } elseif (strpos($selector, 'class=') === 0) {
            $by = WebDriverBy::className(substr($selector, 6));
        } elseif (strpos($selector, 'css=') === 0) {
            $by = WebDriverBy::cssSelector(substr($selector, 4));
        } elseif (strpos($selector, 'xpath=') === 0) {
            $by = WebDriverBy::xpath(substr($selector, 6));
        } elseif (strpos($selector, '//') === 0) {
            $by = WebDriverBy::xpath($selector);
        } else {
            // Default to ID
            $by = WebDriverBy::id($selector);
        }
        
        return $wait->until(WebDriverExpectedCondition::presenceOfElementLocated($by));
    }
    
    /**
     * Verify expected elements
     */
    private function verify_expected_elements($driver, $expected, &$result) {
        foreach ($expected as $element) {
            try {
                $this->find_element($driver, $element['selector'], $element['timeout'] ?? 5);
                $result['output'][] = "Verified expected element: " . $element['selector'];
            } catch (Exception $e) {
                throw new Exception("Expected element not found: " . $element['selector']);
            }
        }
    }
    
    /**
     * Take screenshot
     */
    private function take_screenshot($driver, $filename) {
        $screenshots_dir = ABSPATH . ($this->config['screenshot_path'] ?? 'tests/screenshots');
        
        if (!is_dir($screenshots_dir)) {
            wp_mkdir_p($screenshots_dir);
        }
        
        $screenshot_path = $screenshots_dir . '/' . $filename . '_' . date('Y-m-d_H-i-s') . '.png';
        $driver->takeScreenshot($screenshot_path);
        
        return $screenshot_path;
    }
    
    /**
     * Get current environment
     */
    private function get_current_environment() {
        if (defined('WP_ENVIRONMENT_TYPE')) {
            return WP_ENVIRONMENT_TYPE;
        }
        
        return 'development';
    }
    
    /**
     * Run scheduled tests
     */
    public function run_scheduled_tests() {
        $suites = $this->database->get_test_suites('selenium', 'active');
        
        foreach ($suites as $suite) {
            $this->run_tests($suite->id);
        }
    }
    
    /**
     * Create test script
     */
    public function create_test_script($test_id, $script_data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'etq_selenium_scripts';
        
        $data = array(
            'test_id' => $test_id,
            'script_name' => $script_data['script_name'],
            'browser' => $script_data['browser'] ?? 'chrome',
            'resolution' => $script_data['resolution'] ?? '1920x1080',
            'script_content' => json_encode($script_data['steps']),
            'page_objects' => json_encode($script_data['page_objects'] ?? array()),
            'expected_elements' => json_encode($script_data['expected_elements'] ?? array()),
            'timeout_seconds' => $script_data['timeout_seconds'] ?? 30,
            'retry_count' => $script_data['retry_count'] ?? 0,
            'screenshot_on_failure' => $script_data['screenshot_on_failure'] ?? 1,
            'video_recording' => $script_data['video_recording'] ?? 0
        );
        
        return $wpdb->insert($table, $data);
    }
    
    /**
     * Install Selenium dependencies
     */
    public function install_selenium_dependencies() {
        // Install via Composer
        $composer_file = ABSPATH . 'composer.json';
        
        if (!file_exists($composer_file)) {
            // Create basic composer.json
            $composer_data = array(
                'require-dev' => array(
                    'php-webdriver/webdriver' => '^1.8'
                )
            );
            file_put_contents($composer_file, json_encode($composer_data, JSON_PRETTY_PRINT));
        }
        
        $command = 'cd ' . ABSPATH . ' && composer install --dev';
        exec($command, $output, $return_code);
        
        return $return_code === 0;
    }
    
    /**
     * Validate Selenium installation
     */
    public function validate_installation() {
        $issues = array();
        
        // Check WebDriver classes
        if (!class_exists('RemoteWebDriver')) {
            $issues[] = 'Selenium WebDriver library not found. Run: composer install --dev';
        }
        
        // Check Selenium Grid
        if (!$this->is_selenium_available()) {
            $issues[] = 'Selenium Grid not available at: ' . $this->hub_url;
        }
        
        // Check screenshot directory
        $screenshot_dir = ABSPATH . ($this->config['screenshot_path'] ?? 'tests/screenshots');
        if (!is_dir($screenshot_dir)) {
            wp_mkdir_p($screenshot_dir);
        }
        if (!is_writable($screenshot_dir)) {
            $issues[] = 'Screenshot directory not writable: ' . $screenshot_dir;
        }
        
        return array(
            'valid' => empty($issues),
            'issues' => $issues
        );
    }
}
