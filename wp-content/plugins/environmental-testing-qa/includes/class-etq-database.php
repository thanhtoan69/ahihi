<?php
/**
 * Database Manager for Environmental Testing & QA
 * 
 * Handles all database operations for test results, configurations, and reports
 */

if (!defined('ABSPATH')) {
    exit;
}

class ETQ_Database {
    
    /**
     * Database version
     */
    const DB_VERSION = '1.0.0';
    
    /**
     * Initialize database
     */
    public function __construct() {
        add_action('etq_init', array($this, 'check_database_version'));
    }
    
    /**
     * Create all required database tables
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Test suites table
        $table_test_suites = $wpdb->prefix . 'etq_test_suites';
        $sql_test_suites = "CREATE TABLE $table_test_suites (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            type enum('phpunit', 'selenium', 'performance', 'integration', 'manual') NOT NULL DEFAULT 'phpunit',
            status enum('active', 'inactive', 'disabled') NOT NULL DEFAULT 'active',
            configuration longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by bigint(20) UNSIGNED,
            PRIMARY KEY (id),
            KEY idx_type_status (type, status),
            KEY idx_created_at (created_at),
            FOREIGN KEY (created_by) REFERENCES {$wpdb->users}(ID) ON DELETE SET NULL
        ) $charset_collate;";
        
        // Individual tests table
        $table_tests = $wpdb->prefix . 'etq_tests';
        $sql_tests = "CREATE TABLE $table_tests (
            id int(11) NOT NULL AUTO_INCREMENT,
            suite_id int(11) NOT NULL,
            name varchar(255) NOT NULL,
            description text,
            test_class varchar(255),
            test_method varchar(255),
            test_file varchar(500),
            parameters longtext,
            expected_result longtext,
            priority enum('critical', 'high', 'medium', 'low') NOT NULL DEFAULT 'medium',
            tags varchar(500),
            status enum('active', 'inactive', 'disabled') NOT NULL DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_suite_status (suite_id, status),
            KEY idx_priority (priority),
            KEY idx_tags (tags),
            FOREIGN KEY (suite_id) REFERENCES $table_test_suites(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // Test runs table
        $table_test_runs = $wpdb->prefix . 'etq_test_runs';
        $sql_test_runs = "CREATE TABLE $table_test_runs (
            id int(11) NOT NULL AUTO_INCREMENT,
            suite_id int(11),
            test_id int(11),
            run_type enum('single', 'suite', 'scheduled', 'manual') NOT NULL DEFAULT 'manual',
            environment varchar(100) DEFAULT 'development',
            status enum('running', 'completed', 'failed', 'cancelled') NOT NULL DEFAULT 'running',
            start_time datetime DEFAULT CURRENT_TIMESTAMP,
            end_time datetime NULL,
            duration int(11) DEFAULT 0,
            total_tests int(11) DEFAULT 0,
            passed_tests int(11) DEFAULT 0,
            failed_tests int(11) DEFAULT 0,
            skipped_tests int(11) DEFAULT 0,
            error_count int(11) DEFAULT 0,
            warning_count int(11) DEFAULT 0,
            coverage_percentage decimal(5,2) DEFAULT 0.00,
            memory_usage bigint(20) DEFAULT 0,
            execution_log longtext,
            error_log longtext,
            created_by bigint(20) UNSIGNED,
            PRIMARY KEY (id),
            KEY idx_suite_status (suite_id, status),
            KEY idx_test_status (test_id, status),
            KEY idx_run_type (run_type),
            KEY idx_environment (environment),
            KEY idx_start_time (start_time),
            FOREIGN KEY (suite_id) REFERENCES $table_test_suites(id) ON DELETE SET NULL,
            FOREIGN KEY (test_id) REFERENCES $table_tests(id) ON DELETE SET NULL,
            FOREIGN KEY (created_by) REFERENCES {$wpdb->users}(ID) ON DELETE SET NULL
        ) $charset_collate;";
        
        // Test results table
        $table_test_results = $wpdb->prefix . 'etq_test_results';
        $sql_test_results = "CREATE TABLE $table_test_results (
            id int(11) NOT NULL AUTO_INCREMENT,
            run_id int(11) NOT NULL,
            test_id int(11) NOT NULL,
            test_name varchar(255) NOT NULL,
            test_class varchar(255),
            test_method varchar(255),
            status enum('pass', 'fail', 'skip', 'error') NOT NULL,
            execution_time decimal(10,4) DEFAULT 0.0000,
            memory_usage int(11) DEFAULT 0,
            assertions_count int(11) DEFAULT 0,
            message text,
            error_message text,
            stack_trace longtext,
            output longtext,
            screenshot_path varchar(500),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_run_status (run_id, status),
            KEY idx_test_status (test_id, status),
            KEY idx_execution_time (execution_time),
            KEY idx_created_at (created_at),
            FOREIGN KEY (run_id) REFERENCES $table_test_runs(id) ON DELETE CASCADE,
            FOREIGN KEY (test_id) REFERENCES $table_tests(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // Performance benchmarks table
        $table_performance = $wpdb->prefix . 'etq_performance_benchmarks';
        $sql_performance = "CREATE TABLE $table_performance (
            id int(11) NOT NULL AUTO_INCREMENT,
            test_name varchar(255) NOT NULL,
            url varchar(500),
            method varchar(10) DEFAULT 'GET',
            payload longtext,
            response_time decimal(10,4) NOT NULL,
            memory_usage bigint(20) DEFAULT 0,
            cpu_usage decimal(5,2) DEFAULT 0.00,
            database_queries int(11) DEFAULT 0,
            query_time decimal(10,4) DEFAULT 0.0000,
            page_size int(11) DEFAULT 0,
            load_time decimal(10,4) DEFAULT 0.0000,
            ttfb decimal(10,4) DEFAULT 0.0000,
            dom_ready decimal(10,4) DEFAULT 0.0000,
            status_code int(3),
            environment varchar(100) DEFAULT 'development',
            user_agent varchar(500),
            ip_address varchar(45),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_test_name (test_name),
            KEY idx_url (url(255)),
            KEY idx_response_time (response_time),
            KEY idx_environment (environment),
            KEY idx_created_at (created_at)
        ) $charset_collate;";
        
        // Test environments table
        $table_environments = $wpdb->prefix . 'etq_environments';
        $sql_environments = "CREATE TABLE $table_environments (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL UNIQUE,
            type enum('development', 'testing', 'staging', 'production') NOT NULL DEFAULT 'development',
            url varchar(500),
            database_host varchar(255),
            database_name varchar(255),
            database_user varchar(255),
            database_password varchar(255),
            ftp_host varchar(255),
            ftp_user varchar(255),
            ftp_password varchar(255),
            ftp_path varchar(500),
            ssh_host varchar(255),
            ssh_user varchar(255),
            ssh_key longtext,
            configuration longtext,
            status enum('active', 'inactive', 'maintenance') NOT NULL DEFAULT 'active',
            last_sync datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_type_status (type, status),
            KEY idx_name (name)
        ) $charset_collate;";
        
        // Coverage reports table
        $table_coverage = $wpdb->prefix . 'etq_coverage_reports';
        $sql_coverage = "CREATE TABLE $table_coverage (
            id int(11) NOT NULL AUTO_INCREMENT,
            run_id int(11) NOT NULL,
            file_path varchar(500) NOT NULL,
            lines_total int(11) DEFAULT 0,
            lines_covered int(11) DEFAULT 0,
            lines_percentage decimal(5,2) DEFAULT 0.00,
            functions_total int(11) DEFAULT 0,
            functions_covered int(11) DEFAULT 0,
            functions_percentage decimal(5,2) DEFAULT 0.00,
            classes_total int(11) DEFAULT 0,
            classes_covered int(11) DEFAULT 0,
            classes_percentage decimal(5,2) DEFAULT 0.00,
            complexity_total int(11) DEFAULT 0,
            complexity_covered int(11) DEFAULT 0,
            complexity_percentage decimal(5,2) DEFAULT 0.00,
            report_data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_run_id (run_id),
            KEY idx_file_path (file_path(255)),
            KEY idx_lines_percentage (lines_percentage),
            FOREIGN KEY (run_id) REFERENCES $table_test_runs(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // Test configurations table
        $table_configurations = $wpdb->prefix . 'etq_configurations';
        $sql_configurations = "CREATE TABLE $table_configurations (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            type enum('phpunit', 'selenium', 'performance', 'general') NOT NULL,
            configuration longtext NOT NULL,
            is_default tinyint(1) DEFAULT 0,
            environment varchar(100) DEFAULT 'all',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by bigint(20) UNSIGNED,
            PRIMARY KEY (id),
            KEY idx_type_default (type, is_default),
            KEY idx_environment (environment),
            FOREIGN KEY (created_by) REFERENCES {$wpdb->users}(ID) ON DELETE SET NULL
        ) $charset_collate;";
        
        // Selenium test scripts table
        $table_selenium = $wpdb->prefix . 'etq_selenium_scripts';
        $sql_selenium = "CREATE TABLE $table_selenium (
            id int(11) NOT NULL AUTO_INCREMENT,
            test_id int(11) NOT NULL,
            script_name varchar(255) NOT NULL,
            browser varchar(50) DEFAULT 'chrome',
            resolution varchar(20) DEFAULT '1920x1080',
            script_content longtext NOT NULL,
            page_objects longtext,
            expected_elements longtext,
            timeout_seconds int(11) DEFAULT 30,
            retry_count int(11) DEFAULT 0,
            screenshot_on_failure tinyint(1) DEFAULT 1,
            video_recording tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_test_id (test_id),
            KEY idx_browser (browser),
            FOREIGN KEY (test_id) REFERENCES $table_tests(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_test_suites);
        dbDelta($sql_tests);
        dbDelta($sql_test_runs);
        dbDelta($sql_test_results);
        dbDelta($sql_performance);
        dbDelta($sql_environments);
        dbDelta($sql_coverage);
        dbDelta($sql_configurations);
        dbDelta($sql_selenium);
        
        // Insert default data
        $this->insert_default_data();
        
        // Update database version
        update_option('etq_db_version', self::DB_VERSION);
    }
    
    /**
     * Insert default data
     */
    private function insert_default_data() {
        global $wpdb;
        
        // Insert default test suites
        $default_suites = array(
            array(
                'name' => 'Core WordPress Tests',
                'description' => 'Tests for core WordPress functionality',
                'type' => 'phpunit',
                'configuration' => json_encode(array(
                    'test_path' => 'tests/core',
                    'bootstrap' => 'tests/bootstrap.php',
                    'groups' => array('core', 'database', 'security')
                ))
            ),
            array(
                'name' => 'Environmental Platform Tests',
                'description' => 'Tests for all Environmental Platform components',
                'type' => 'phpunit',
                'configuration' => json_encode(array(
                    'test_path' => 'tests/environmental',
                    'bootstrap' => 'tests/bootstrap.php',
                    'groups' => array('plugins', 'integration', 'features')
                ))
            ),
            array(
                'name' => 'Frontend Integration Tests',
                'description' => 'Selenium tests for frontend functionality',
                'type' => 'selenium',
                'configuration' => json_encode(array(
                    'browser' => 'chrome',
                    'resolution' => '1920x1080',
                    'timeout' => 30
                ))
            ),
            array(
                'name' => 'Performance Benchmarks',
                'description' => 'Performance and load testing suite',
                'type' => 'performance',
                'configuration' => json_encode(array(
                    'concurrent_users' => 10,
                    'duration' => 60,
                    'ramp_up' => 10
                ))
            )
        );
        
        $table_suites = $wpdb->prefix . 'etq_test_suites';
        foreach ($default_suites as $suite) {
            $wpdb->insert($table_suites, $suite);
        }
        
        // Insert default environments
        $default_environments = array(
            array(
                'name' => 'local_development',
                'type' => 'development',
                'url' => get_site_url(),
                'configuration' => json_encode(array(
                    'debug' => true,
                    'log_level' => 'debug'
                ))
            ),
            array(
                'name' => 'staging',
                'type' => 'staging',
                'url' => '',
                'configuration' => json_encode(array(
                    'debug' => false,
                    'log_level' => 'error'
                ))
            )
        );
        
        $table_environments = $wpdb->prefix . 'etq_environments';
        foreach ($default_environments as $environment) {
            $wpdb->insert($table_environments, $environment);
        }
        
        // Insert default configurations
        $default_configurations = array(
            array(
                'name' => 'Default PHPUnit Configuration',
                'type' => 'phpunit',
                'configuration' => json_encode(array(
                    'phpunit_path' => 'vendor/bin/phpunit',
                    'configuration_file' => 'phpunit.xml',
                    'coverage_format' => 'html',
                    'coverage_target' => 80,
                    'memory_limit' => '256M',
                    'time_limit' => 300
                )),
                'is_default' => 1
            ),
            array(
                'name' => 'Default Selenium Configuration',
                'type' => 'selenium',
                'configuration' => json_encode(array(
                    'hub_url' => 'http://localhost:4444/wd/hub',
                    'browsers' => array('chrome', 'firefox'),
                    'implicit_wait' => 10,
                    'page_load_timeout' => 30,
                    'screenshot_path' => 'tests/screenshots',
                    'video_path' => 'tests/videos'
                )),
                'is_default' => 1
            ),
            array(
                'name' => 'Default Performance Configuration',
                'type' => 'performance',
                'configuration' => json_encode(array(
                    'response_time_threshold' => 2.0,
                    'memory_threshold' => 128,
                    'query_threshold' => 50,
                    'concurrent_users' => 10,
                    'test_duration' => 60
                )),
                'is_default' => 1
            )
        );
        
        $table_configurations = $wpdb->prefix . 'etq_configurations';
        foreach ($default_configurations as $config) {
            $wpdb->insert($table_configurations, $config);
        }
    }
    
    /**
     * Check database version and upgrade if needed
     */
    public function check_database_version() {
        $current_version = get_option('etq_db_version', '0.0.0');
        
        if (version_compare($current_version, self::DB_VERSION, '<')) {
            $this->create_tables();
        }
    }
    
    /**
     * Get test suites
     */
    public function get_test_suites($type = null, $status = 'active') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'etq_test_suites';
        $where = array("status = %s");
        $params = array($status);
        
        if ($type) {
            $where[] = "type = %s";
            $params[] = $type;
        }
        
        $sql = "SELECT * FROM $table WHERE " . implode(' AND ', $where) . " ORDER BY name";
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }
    
    /**
     * Get tests for a suite
     */
    public function get_tests($suite_id, $status = 'active') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'etq_tests';
        
        $sql = "SELECT * FROM $table WHERE suite_id = %d AND status = %s ORDER BY name";
        
        return $wpdb->get_results($wpdb->prepare($sql, $suite_id, $status));
    }
    
    /**
     * Get test results
     */
    public function get_test_results($run_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'etq_test_results';
        
        $sql = "SELECT * FROM $table WHERE run_id = %d ORDER BY created_at";
        
        return $wpdb->get_results($wpdb->prepare($sql, $run_id));
    }
    
    /**
     * Get recent test runs
     */
    public function get_recent_test_runs($limit = 20) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'etq_test_runs';
        
        $sql = "SELECT * FROM $table ORDER BY start_time DESC LIMIT %d";
        
        return $wpdb->get_results($wpdb->prepare($sql, $limit));
    }
    
    /**
     * Save test run
     */
    public function save_test_run($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'etq_test_runs';
        
        $result = $wpdb->insert($table, $data);
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update test run
     */
    public function update_test_run($run_id, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'etq_test_runs';
        
        return $wpdb->update($table, $data, array('id' => $run_id));
    }
    
    /**
     * Save test result
     */
    public function save_test_result($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'etq_test_results';
        
        return $wpdb->insert($table, $data);
    }
    
    /**
     * Clean up old test data
     */
    public function cleanup_old_data($days = 30) {
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // Clean up test runs
        $table_runs = $wpdb->prefix . 'etq_test_runs';
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_runs WHERE start_time < %s",
            $cutoff_date
        ));
        
        // Clean up performance benchmarks
        $table_performance = $wpdb->prefix . 'etq_performance_benchmarks';
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_performance WHERE created_at < %s",
            $cutoff_date
        ));
        
        return true;
    }
    
    /**
     * Get test statistics
     */
    public function get_test_statistics($days = 30) {
        global $wpdb;
        
        $start_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $table_runs = $wpdb->prefix . 'etq_test_runs';
        $table_results = $wpdb->prefix . 'etq_test_results';
        
        // Get run statistics
        $run_stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_runs,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_runs,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_runs,
                AVG(duration) as avg_duration,
                SUM(total_tests) as total_tests,
                SUM(passed_tests) as total_passed,
                SUM(failed_tests) as total_failed
            FROM $table_runs 
            WHERE start_time >= %s
        ", $start_date));
        
        // Get coverage statistics
        $coverage_stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                AVG(coverage_percentage) as avg_coverage,
                MAX(coverage_percentage) as max_coverage,
                MIN(coverage_percentage) as min_coverage
            FROM $table_runs 
            WHERE start_time >= %s AND coverage_percentage > 0
        ", $start_date));
        
        return array(
            'runs' => $run_stats,
            'coverage' => $coverage_stats
        );
    }
}
