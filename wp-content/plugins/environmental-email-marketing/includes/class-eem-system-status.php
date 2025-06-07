<?php
/**
 * System Status Checker for Environmental Email Marketing Plugin
 * 
 * This class provides comprehensive system health checks and diagnostics
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EEM_System_Status {
    
    private $status_data = array();
    
    public function __construct() {
        add_action('wp_ajax_eem_get_system_status', array($this, 'get_system_status'));
        add_action('wp_ajax_eem_run_diagnostics', array($this, 'run_diagnostics'));
    }
    
    /**
     * Get comprehensive system status
     */
    public function get_system_status() {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $status = array(
            'plugin_info' => $this->get_plugin_info(),
            'database_status' => $this->check_database_status(),
            'file_permissions' => $this->check_file_permissions(),
            'server_requirements' => $this->check_server_requirements(),
            'email_configuration' => $this->check_email_configuration(),
            'cron_status' => $this->check_cron_status(),
            'integration_status' => $this->check_integration_status(),
            'performance_metrics' => $this->get_performance_metrics()
        );
        
        wp_send_json_success($status);
    }
    
    /**
     * Get plugin information
     */
    private function get_plugin_info() {
        $plugin_data = get_plugin_data(EEM_PLUGIN_PATH . 'environmental-email-marketing.php');
        
        return array(
            'name' => $plugin_data['Name'],
            'version' => $plugin_data['Version'],
            'author' => $plugin_data['Author'],
            'description' => $plugin_data['Description'],
            'text_domain' => $plugin_data['TextDomain'],
            'network' => $plugin_data['Network'],
            'plugin_path' => EEM_PLUGIN_PATH,
            'plugin_url' => EEM_PLUGIN_URL,
            'active' => is_plugin_active(EEM_PLUGIN_BASENAME)
        );
    }
    
    /**
     * Check database status
     */
    private function check_database_status() {
        global $wpdb;
        
        $tables = array(
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
        
        $status = array(
            'database_version' => $wpdb->db_version(),
            'database_charset' => $wpdb->charset,
            'database_collate' => $wpdb->collate,
            'tables' => array(),
            'table_status' => 'healthy'
        );
        
        $missing_tables = array();
        $table_sizes = array();
        
        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            
            // Check if table exists
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            
            if ($exists) {
                // Get table size
                $size_query = $wpdb->get_row("
                    SELECT 
                        ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'size_mb',
                        table_rows
                    FROM information_schema.TABLES 
                    WHERE table_schema = DATABASE() 
                    AND table_name = '$table_name'
                ");
                
                $table_sizes[$table] = array(
                    'exists' => true,
                    'size_mb' => $size_query ? $size_query->size_mb : 0,
                    'rows' => $size_query ? $size_query->table_rows : 0
                );
            } else {
                $missing_tables[] = $table;
                $table_sizes[$table] = array(
                    'exists' => false,
                    'size_mb' => 0,
                    'rows' => 0
                );
            }
        }
        
        $status['tables'] = $table_sizes;
        $status['missing_tables'] = $missing_tables;
        
        if (!empty($missing_tables)) {
            $status['table_status'] = 'missing_tables';
        }
        
        return $status;
    }
    
    /**
     * Check file permissions
     */
    private function check_file_permissions() {
        $directories = array(
            'plugin_root' => EEM_PLUGIN_PATH,
            'uploads' => wp_upload_dir()['basedir'] . '/eem-uploads/',
            'logs' => EEM_PLUGIN_PATH . 'logs/',
            'templates' => EEM_PLUGIN_PATH . 'templates/',
            'assets' => EEM_PLUGIN_PATH . 'assets/'
        );
        
        $permissions = array();
        
        foreach ($directories as $name => $path) {
            if (file_exists($path)) {
                $perms = fileperms($path);
                $permissions[$name] = array(
                    'path' => $path,
                    'exists' => true,
                    'readable' => is_readable($path),
                    'writable' => is_writable($path),
                    'permissions' => substr(sprintf('%o', $perms), -4)
                );
            } else {
                $permissions[$name] = array(
                    'path' => $path,
                    'exists' => false,
                    'readable' => false,
                    'writable' => false,
                    'permissions' => null
                );
            }
        }
        
        return $permissions;
    }
    
    /**
     * Check server requirements
     */
    private function check_server_requirements() {
        $requirements = array(
            'php_version' => array(
                'required' => '7.4',
                'current' => PHP_VERSION,
                'status' => version_compare(PHP_VERSION, '7.4', '>=') ? 'passed' : 'failed'
            ),
            'wordpress_version' => array(
                'required' => '5.0',
                'current' => get_bloginfo('version'),
                'status' => version_compare(get_bloginfo('version'), '5.0', '>=') ? 'passed' : 'failed'
            ),
            'memory_limit' => array(
                'required' => '128M',
                'current' => ini_get('memory_limit'),
                'status' => $this->compare_memory_limit(ini_get('memory_limit'), '128M') ? 'passed' : 'warning'
            ),
            'max_execution_time' => array(
                'required' => '30',
                'current' => ini_get('max_execution_time'),
                'status' => intval(ini_get('max_execution_time')) >= 30 ? 'passed' : 'warning'
            ),
            'curl_extension' => array(
                'required' => true,
                'current' => extension_loaded('curl'),
                'status' => extension_loaded('curl') ? 'passed' : 'failed'
            ),
            'openssl_extension' => array(
                'required' => true,
                'current' => extension_loaded('openssl'),
                'status' => extension_loaded('openssl') ? 'passed' : 'failed'
            ),
            'json_extension' => array(
                'required' => true,
                'current' => extension_loaded('json'),
                'status' => extension_loaded('json') ? 'passed' : 'failed'
            ),
            'mbstring_extension' => array(
                'required' => true,
                'current' => extension_loaded('mbstring'),
                'status' => extension_loaded('mbstring') ? 'passed' : 'warning'
            )
        );
        
        return $requirements;
    }
    
    /**
     * Check email configuration
     */
    private function check_email_configuration() {
        $email_status = array(
            'wp_mail_function' => function_exists('wp_mail'),
            'smtp_settings' => array(),
            'provider_connections' => array(),
            'test_email_status' => null
        );
        
        // Check SMTP settings
        $email_status['smtp_settings'] = array(
            'smtp_host' => get_option('eem_smtp_host', ''),
            'smtp_port' => get_option('eem_smtp_port', ''),
            'smtp_auth' => get_option('eem_smtp_auth', false),
            'smtp_secure' => get_option('eem_smtp_secure', '')
        );
        
        // Check email provider connections
        $providers = array('mailchimp', 'sendgrid', 'mailgun', 'amazon_ses');
        
        foreach ($providers as $provider) {
            $api_key = get_option('eem_' . $provider . '_api_key', '');
            $email_status['provider_connections'][$provider] = array(
                'configured' => !empty($api_key),
                'api_key_length' => strlen($api_key),
                'last_test' => get_option('eem_' . $provider . '_last_test', null)
            );
        }
        
        // Test email sending (to admin)
        $test_result = wp_mail(
            get_option('admin_email'),
            'EEM System Test Email - ' . date('Y-m-d H:i:s'),
            'This is a test email from the Environmental Email Marketing plugin system status checker.',
            array('Content-Type: text/plain; charset=UTF-8')
        );
        
        $email_status['test_email_status'] = $test_result;
        
        return $email_status;
    }
    
    /**
     * Check cron status
     */
    private function check_cron_status() {
        $cron_jobs = array(
            'eem_process_automation_queue',
            'eem_process_campaign_queue',
            'eem_sync_providers',
            'eem_cleanup_data',
            'eem_update_analytics',
            'eem_process_webhooks'
        );
        
        $cron_status = array(
            'wp_cron_enabled' => !(defined('DISABLE_WP_CRON') && DISABLE_WP_CRON),
            'jobs' => array(),
            'next_run_times' => array()
        );
        
        foreach ($cron_jobs as $job) {
            $next_run = wp_next_scheduled($job);
            $cron_status['jobs'][$job] = array(
                'scheduled' => $next_run !== false,
                'next_run' => $next_run ? date('Y-m-d H:i:s', $next_run) : null,
                'interval' => $this->get_cron_interval($job)
            );
        }
        
        return $cron_status;
    }
    
    /**
     * Check integration status
     */
    private function check_integration_status() {
        $integrations = array(
            'woocommerce' => array(
                'plugin_active' => is_plugin_active('woocommerce/woocommerce.php'),
                'integration_class' => class_exists('EEM_WooCommerce_Integration'),
                'settings_configured' => get_option('eem_woocommerce_enabled', false)
            ),
            'contact_form_7' => array(
                'plugin_active' => is_plugin_active('contact-form-7/wp-contact-form-7.php'),
                'integration_class' => class_exists('EEM_Contact_Form_Integration'),
                'settings_configured' => get_option('eem_cf7_enabled', false)
            ),
            'elementor' => array(
                'plugin_active' => is_plugin_active('elementor/elementor.php'),
                'integration_class' => class_exists('EEM_Elementor_Integration'),
                'settings_configured' => get_option('eem_elementor_enabled', false)
            ),
            'gutenberg' => array(
                'plugin_active' => function_exists('register_block_type'),
                'integration_class' => class_exists('EEM_Gutenberg_Integration'),
                'settings_configured' => get_option('eem_gutenberg_enabled', false)
            )
        );
        
        return $integrations;
    }
    
    /**
     * Get performance metrics
     */
    private function get_performance_metrics() {
        global $wpdb;
        
        $metrics = array();
        
        // Database query performance
        $start_time = microtime(true);
        $wpdb->get_results("SELECT COUNT(*) FROM {$wpdb->prefix}eem_subscribers");
        $db_query_time = microtime(true) - $start_time;
        
        // File system performance
        $start_time = microtime(true);
        $temp_file = wp_upload_dir()['basedir'] . '/eem_test_' . time() . '.tmp';
        file_put_contents($temp_file, 'test');
        $file_contents = file_get_contents($temp_file);
        unlink($temp_file);
        $file_system_time = microtime(true) - $start_time;
        
        // Memory usage
        $metrics['memory_usage'] = array(
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => ini_get('memory_limit')
        );
        
        // Performance timings
        $metrics['performance'] = array(
            'database_query_time' => round($db_query_time * 1000, 2) . 'ms',
            'file_system_time' => round($file_system_time * 1000, 2) . 'ms',
            'plugin_load_time' => null // This would need to be measured during plugin initialization
        );
        
        // Cache status
        $metrics['cache'] = array(
            'object_cache' => wp_using_ext_object_cache(),
            'opcache_enabled' => function_exists('opcache_get_status') && opcache_get_status() !== false,
            'redis_available' => class_exists('Redis'),
            'memcached_available' => class_exists('Memcached')
        );
        
        return $metrics;
    }
    
    /**
     * Run comprehensive diagnostics
     */
    public function run_diagnostics() {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $diagnostics = array();
        
        // Test database connections
        $diagnostics['database_tests'] = $this->run_database_tests();
        
        // Test email functionality
        $diagnostics['email_tests'] = $this->run_email_tests();
        
        // Test API connections
        $diagnostics['api_tests'] = $this->run_api_tests();
        
        // Test file operations
        $diagnostics['file_tests'] = $this->run_file_tests();
        
        // Test performance
        $diagnostics['performance_tests'] = $this->run_performance_tests();
        
        wp_send_json_success($diagnostics);
    }
    
    /**
     * Run database tests
     */
    private function run_database_tests() {
        global $wpdb;
        
        $tests = array();
        
        // Test database connection
        $tests['connection'] = array(
            'name' => 'Database Connection',
            'status' => $wpdb->check_connection() ? 'passed' : 'failed',
            'message' => $wpdb->check_connection() ? 'Database connection successful' : 'Database connection failed'
        );
        
        // Test table creation
        $test_table = $wpdb->prefix . 'eem_test_' . time();
        $sql = "CREATE TABLE $test_table (id INT AUTO_INCREMENT PRIMARY KEY, test_data VARCHAR(255))";
        $result = $wpdb->query($sql);
        
        $tests['table_creation'] = array(
            'name' => 'Table Creation',
            'status' => $result !== false ? 'passed' : 'failed',
            'message' => $result !== false ? 'Table creation successful' : 'Table creation failed'
        );
        
        // Clean up test table
        if ($result !== false) {
            $wpdb->query("DROP TABLE $test_table");
        }
        
        // Test data operations
        $insert_result = $wpdb->insert(
            $wpdb->prefix . 'eem_subscribers',
            array('email' => 'diagnostic_test_' . time() . '@example.com', 'status' => 'test', 'created_at' => current_time('mysql')),
            array('%s', '%s', '%s')
        );
        
        if ($insert_result) {
            $test_id = $wpdb->insert_id;
            $delete_result = $wpdb->delete($wpdb->prefix . 'eem_subscribers', array('id' => $test_id), array('%d'));
            
            $tests['data_operations'] = array(
                'name' => 'Data Operations',
                'status' => $delete_result !== false ? 'passed' : 'failed',
                'message' => $delete_result !== false ? 'Insert/Delete operations successful' : 'Data operations failed'
            );
        } else {
            $tests['data_operations'] = array(
                'name' => 'Data Operations',
                'status' => 'failed',
                'message' => 'Insert operation failed'
            );
        }
        
        return $tests;
    }
    
    /**
     * Run email tests
     */
    private function run_email_tests() {
        $tests = array();
        
        // Test wp_mail function
        $tests['wp_mail_function'] = array(
            'name' => 'WP Mail Function',
            'status' => function_exists('wp_mail') ? 'passed' : 'failed',
            'message' => function_exists('wp_mail') ? 'wp_mail function available' : 'wp_mail function not available'
        );
        
        // Test email sending
        $email_result = wp_mail(
            get_option('admin_email'),
            'EEM Diagnostic Test Email - ' . date('Y-m-d H:i:s'),
            'This is a diagnostic test email from Environmental Email Marketing plugin.',
            array('Content-Type: text/plain; charset=UTF-8')
        );
        
        $tests['email_sending'] = array(
            'name' => 'Email Sending',
            'status' => $email_result ? 'passed' : 'failed',
            'message' => $email_result ? 'Test email sent successfully' : 'Email sending failed'
        );
        
        return $tests;
    }
    
    /**
     * Run API tests
     */
    private function run_api_tests() {
        $tests = array();
        
        // Test cURL functionality
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://httpbin.org/get');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $result = curl_exec($ch);
            $curl_error = curl_error($ch);
            curl_close($ch);
            
            $tests['curl_functionality'] = array(
                'name' => 'cURL Functionality',
                'status' => $result !== false && empty($curl_error) ? 'passed' : 'failed',
                'message' => $result !== false && empty($curl_error) ? 'cURL working correctly' : 'cURL error: ' . $curl_error
            );
        } else {
            $tests['curl_functionality'] = array(
                'name' => 'cURL Functionality',
                'status' => 'failed',
                'message' => 'cURL extension not available'
            );
        }
        
        // Test REST API endpoints
        $rest_request = new WP_REST_Request('GET', '/eem/v1/subscribers');
        $rest_response = rest_do_request($rest_request);
        
        $tests['rest_api'] = array(
            'name' => 'REST API',
            'status' => !is_wp_error($rest_response) ? 'passed' : 'failed',
            'message' => !is_wp_error($rest_response) ? 'REST API endpoints accessible' : 'REST API error: ' . $rest_response->get_error_message()
        );
        
        return $tests;
    }
    
    /**
     * Run file tests
     */
    private function run_file_tests() {
        $tests = array();
        
        // Test file creation
        $test_file = wp_upload_dir()['basedir'] . '/eem_diagnostic_test_' . time() . '.txt';
        $write_result = file_put_contents($test_file, 'Diagnostic test content');
        
        $tests['file_creation'] = array(
            'name' => 'File Creation',
            'status' => $write_result !== false ? 'passed' : 'failed',
            'message' => $write_result !== false ? 'File creation successful' : 'File creation failed'
        );
        
        // Test file reading
        if ($write_result !== false) {
            $read_result = file_get_contents($test_file);
            
            $tests['file_reading'] = array(
                'name' => 'File Reading',
                'status' => $read_result === 'Diagnostic test content' ? 'passed' : 'failed',
                'message' => $read_result === 'Diagnostic test content' ? 'File reading successful' : 'File reading failed'
            );
            
            // Clean up
            unlink($test_file);
        }
        
        return $tests;
    }
    
    /**
     * Run performance tests
     */
    private function run_performance_tests() {
        $tests = array();
        
        // Test database query performance
        global $wpdb;
        $start_time = microtime(true);
        
        for ($i = 0; $i < 10; $i++) {
            $wpdb->get_results("SELECT * FROM {$wpdb->prefix}options LIMIT 10");
        }
        
        $db_time = microtime(true) - $start_time;
        
        $tests['database_performance'] = array(
            'name' => 'Database Performance',
            'status' => $db_time < 1.0 ? 'passed' : ($db_time < 3.0 ? 'warning' : 'failed'),
            'message' => '10 queries executed in ' . round($db_time * 1000, 2) . 'ms',
            'value' => $db_time
        );
        
        // Test memory usage
        $memory_usage = memory_get_usage(true);
        $memory_limit = $this->parse_memory_limit(ini_get('memory_limit'));
        $memory_percentage = ($memory_usage / $memory_limit) * 100;
        
        $tests['memory_usage'] = array(
            'name' => 'Memory Usage',
            'status' => $memory_percentage < 70 ? 'passed' : ($memory_percentage < 90 ? 'warning' : 'failed'),
            'message' => 'Using ' . round($memory_percentage, 1) . '% of available memory',
            'value' => $memory_usage
        );
        
        return $tests;
    }
    
    /**
     * Helper function to compare memory limits
     */
    private function compare_memory_limit($current, $required) {
        $current_bytes = $this->parse_memory_limit($current);
        $required_bytes = $this->parse_memory_limit($required);
        
        return $current_bytes >= $required_bytes;
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
    
    /**
     * Get cron interval for a job
     */
    private function get_cron_interval($job) {
        $schedules = wp_get_schedules();
        $crons = get_option('cron');
        
        foreach ($crons as $timestamp => $cron) {
            if (isset($cron[$job])) {
                foreach ($cron[$job] as $hook_data) {
                    if (isset($hook_data['schedule'])) {
                        return isset($schedules[$hook_data['schedule']]) ? 
                               $schedules[$hook_data['schedule']]['display'] : 
                               $hook_data['schedule'];
                    }
                }
            }
        }
        
        return 'unknown';
    }
}

// Initialize the system status checker
new EEM_System_Status();
