<?php
/**
 * Performance Testing Manager
 * 
 * Handles load testing, performance benchmarking, and optimization analysis
 * for the Environmental Platform WordPress system.
 * 
 * @package EnvironmentalTestingQA
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ETQ_Performance_Tester {
    
    /**
     * Database instance
     */
    private $db;
    
    /**
     * Performance test results
     */
    private $results = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = ETQ_Database::get_instance();
        add_action('wp_ajax_etq_run_performance_test', [$this, 'ajax_run_performance_test']);
        add_action('wp_ajax_etq_get_performance_metrics', [$this, 'ajax_get_performance_metrics']);
    }
    
    /**
     * Run comprehensive performance test suite
     */
    public function run_performance_test_suite($test_config = []) {
        $start_time = microtime(true);
        
        $default_config = [
            'concurrent_users' => 10,
            'test_duration' => 60, // seconds
            'endpoints' => [
                '/',
                '/shop/',
                '/donate/',
                '/petitions/',
                '/environmental-data/'
            ],
            'include_database' => true,
            'include_memory' => true,
            'include_queries' => true
        ];
        
        $config = array_merge($default_config, $test_config);
        
        try {
            // Initialize performance monitoring
            $this->start_performance_monitoring();
            
            // Run endpoint load tests
            $endpoint_results = $this->run_endpoint_load_tests($config);
            
            // Run database performance tests
            $database_results = $config['include_database'] ? $this->run_database_performance_tests() : [];
            
            // Run memory usage tests
            $memory_results = $config['include_memory'] ? $this->run_memory_tests() : [];
            
            // Run query optimization tests
            $query_results = $config['include_queries'] ? $this->run_query_optimization_tests() : [];
            
            // Compile results
            $test_results = [
                'test_id' => uniqid('perf_'),
                'start_time' => $start_time,
                'end_time' => microtime(true),
                'duration' => microtime(true) - $start_time,
                'config' => $config,
                'endpoints' => $endpoint_results,
                'database' => $database_results,
                'memory' => $memory_results,
                'queries' => $query_results,
                'overall_score' => $this->calculate_performance_score($endpoint_results, $database_results, $memory_results),
                'recommendations' => $this->generate_performance_recommendations($endpoint_results, $database_results, $memory_results)
            ];
            
            // Save results to database
            $this->save_performance_results($test_results);
            
            // Stop performance monitoring
            $this->stop_performance_monitoring();
            
            return $test_results;
            
        } catch (Exception $e) {
            error_log('Performance test error: ' . $e->getMessage());
            return [
                'error' => true,
                'message' => 'Performance test failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Run load tests on specific endpoints
     */
    private function run_endpoint_load_tests($config) {
        $results = [];
        
        foreach ($config['endpoints'] as $endpoint) {
            $endpoint_results = [
                'url' => $endpoint,
                'requests' => [],
                'avg_response_time' => 0,
                'min_response_time' => PHP_FLOAT_MAX,
                'max_response_time' => 0,
                'error_rate' => 0,
                'throughput' => 0
            ];
            
            $total_requests = $config['concurrent_users'] * 5; // 5 requests per user
            $errors = 0;
            
            for ($i = 0; $i < $total_requests; $i++) {
                $request_start = microtime(true);
                
                try {
                    $response = wp_remote_get(home_url($endpoint), [
                        'timeout' => 30,
                        'headers' => [
                            'User-Agent' => 'ETQ-Performance-Tester/1.0'
                        ]
                    ]);
                    
                    $request_time = microtime(true) - $request_start;
                    
                    if (is_wp_error($response)) {
                        $errors++;
                        continue;
                    }
                    
                    $status_code = wp_remote_retrieve_response_code($response);
                    
                    $endpoint_results['requests'][] = [
                        'time' => $request_time,
                        'status' => $status_code,
                        'size' => strlen(wp_remote_retrieve_body($response))
                    ];
                    
                    // Update metrics
                    $endpoint_results['min_response_time'] = min($endpoint_results['min_response_time'], $request_time);
                    $endpoint_results['max_response_time'] = max($endpoint_results['max_response_time'], $request_time);
                    
                    if ($status_code >= 400) {
                        $errors++;
                    }
                    
                } catch (Exception $e) {
                    $errors++;
                }
                
                // Brief pause to prevent overwhelming the server
                usleep(100000); // 0.1 second
            }
            
            // Calculate final metrics
            $successful_requests = count($endpoint_results['requests']);
            if ($successful_requests > 0) {
                $total_time = array_sum(array_column($endpoint_results['requests'], 'time'));
                $endpoint_results['avg_response_time'] = $total_time / $successful_requests;
                $endpoint_results['throughput'] = $successful_requests / $config['test_duration'];
            }
            
            $endpoint_results['error_rate'] = ($errors / $total_requests) * 100;
            $endpoint_results['total_requests'] = $total_requests;
            $endpoint_results['successful_requests'] = $successful_requests;
            $endpoint_results['failed_requests'] = $errors;
            
            $results[] = $endpoint_results;
        }
        
        return $results;
    }
    
    /**
     * Run database performance tests
     */
    private function run_database_performance_tests() {
        global $wpdb;
        
        $results = [
            'query_tests' => [],
            'table_sizes' => [],
            'index_usage' => [],
            'slow_queries' => []
        ];
        
        // Test common queries
        $test_queries = [
            'posts_query' => "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish'",
            'users_query' => "SELECT COUNT(*) FROM {$wpdb->users}",
            'options_query' => "SELECT COUNT(*) FROM {$wpdb->options}",
            'metadata_query' => "SELECT COUNT(*) FROM {$wpdb->postmeta}",
            'comments_query' => "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = '1'"
        ];
        
        foreach ($test_queries as $test_name => $query) {
            $start_time = microtime(true);
            $result = $wpdb->get_var($query);
            $execution_time = microtime(true) - $start_time;
            
            $results['query_tests'][] = [
                'name' => $test_name,
                'query' => $query,
                'execution_time' => $execution_time,
                'result_count' => $result
            ];
        }
        
        // Get table sizes
        $table_size_query = "SELECT 
            table_name,
            ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
            table_rows
        FROM information_schema.TABLES 
        WHERE table_schema = DATABASE()
        ORDER BY (data_length + index_length) DESC";
        
        $table_sizes = $wpdb->get_results($table_size_query, ARRAY_A);
        $results['table_sizes'] = $table_sizes ?: [];
        
        // Check for slow queries (if slow query log is available)
        $slow_query_check = "SHOW VARIABLES LIKE 'slow_query_log'";
        $slow_log_status = $wpdb->get_row($slow_query_check, ARRAY_A);
        
        if ($slow_log_status && $slow_log_status['Value'] === 'ON') {
            // Get recent slow queries if available
            $results['slow_queries'] = $this->get_recent_slow_queries();
        }
        
        return $results;
    }
    
    /**
     * Run memory usage tests
     */
    private function run_memory_tests() {
        $results = [
            'peak_memory' => memory_get_peak_usage(true),
            'current_memory' => memory_get_usage(true),
            'memory_limit' => ini_get('memory_limit'),
            'memory_usage_percentage' => 0,
            'object_cache_stats' => [],
            'plugin_memory_impact' => []
        ];
        
        // Calculate memory usage percentage
        $memory_limit_bytes = $this->convert_memory_limit_to_bytes($results['memory_limit']);
        if ($memory_limit_bytes > 0) {
            $results['memory_usage_percentage'] = ($results['peak_memory'] / $memory_limit_bytes) * 100;
        }
        
        // Get object cache statistics if available
        if (function_exists('wp_cache_get_stats')) {
            $results['object_cache_stats'] = wp_cache_get_stats();
        }
        
        // Test memory impact of loading different components
        $memory_before = memory_get_usage(true);
        
        // Load a large query to test memory impact
        global $wpdb;
        $large_query = $wpdb->get_results("SELECT * FROM {$wpdb->posts} LIMIT 100", ARRAY_A);
        $memory_after_query = memory_get_usage(true);
        
        $results['query_memory_impact'] = $memory_after_query - $memory_before;
        
        return $results;
    }
    
    /**
     * Run query optimization tests
     */
    private function run_query_optimization_tests() {
        global $wpdb;
        
        $results = [
            'total_queries' => 0,
            'slow_queries' => [],
            'duplicate_queries' => [],
            'missing_indexes' => [],
            'optimization_suggestions' => []
        ];
        
        // Enable query logging temporarily
        $queries_before = get_num_queries();
        
        // Simulate a typical page load to capture queries
        $this->simulate_page_load_queries();
        
        $queries_after = get_num_queries();
        $results['total_queries'] = $queries_after - $queries_before;
        
        // Check for common performance issues
        $results['optimization_suggestions'] = $this->analyze_query_patterns();
        
        return $results;
    }
    
    /**
     * Calculate overall performance score
     */
    private function calculate_performance_score($endpoint_results, $database_results, $memory_results) {
        $score = 100;
        
        // Deduct points for slow response times
        foreach ($endpoint_results as $endpoint) {
            if ($endpoint['avg_response_time'] > 2.0) {
                $score -= 20;
            } elseif ($endpoint['avg_response_time'] > 1.0) {
                $score -= 10;
            }
            
            // Deduct points for high error rates
            if ($endpoint['error_rate'] > 10) {
                $score -= 30;
            } elseif ($endpoint['error_rate'] > 5) {
                $score -= 15;
            }
        }
        
        // Deduct points for high memory usage
        if (!empty($memory_results) && $memory_results['memory_usage_percentage'] > 80) {
            $score -= 20;
        } elseif (!empty($memory_results) && $memory_results['memory_usage_percentage'] > 60) {
            $score -= 10;
        }
        
        // Deduct points for slow database queries
        if (!empty($database_results['query_tests'])) {
            foreach ($database_results['query_tests'] as $query_test) {
                if ($query_test['execution_time'] > 1.0) {
                    $score -= 10;
                }
            }
        }
        
        return max(0, $score);
    }
    
    /**
     * Generate performance recommendations
     */
    private function generate_performance_recommendations($endpoint_results, $database_results, $memory_results) {
        $recommendations = [];
        
        // Analyze endpoint performance
        foreach ($endpoint_results as $endpoint) {
            if ($endpoint['avg_response_time'] > 2.0) {
                $recommendations[] = "Optimize {$endpoint['url']} - response time is {$endpoint['avg_response_time']}s (target: <2s)";
            }
            
            if ($endpoint['error_rate'] > 5) {
                $recommendations[] = "Fix errors on {$endpoint['url']} - error rate is {$endpoint['error_rate']}%";
            }
        }
        
        // Analyze memory usage
        if (!empty($memory_results) && $memory_results['memory_usage_percentage'] > 70) {
            $recommendations[] = "Consider increasing PHP memory limit or optimizing memory usage ({$memory_results['memory_usage_percentage']}% used)";
        }
        
        // Analyze database performance
        if (!empty($database_results['query_tests'])) {
            $slow_queries = array_filter($database_results['query_tests'], function($test) {
                return $test['execution_time'] > 0.5;
            });
            
            if (!empty($slow_queries)) {
                $recommendations[] = "Optimize slow database queries - " . count($slow_queries) . " queries taking >0.5s";
            }
        }
        
        // General recommendations
        if (empty($recommendations)) {
            $recommendations[] = "Performance is good! Consider implementing caching for further optimization.";
        } else {
            $recommendations[] = "Consider implementing object caching (Redis/Memcached) for better performance.";
            $recommendations[] = "Enable gzip compression and browser caching.";
            $recommendations[] = "Consider using a CDN for static assets.";
        }
        
        return $recommendations;
    }
    
    /**
     * Save performance test results to database
     */
    private function save_performance_results($results) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'etq_performance_benchmarks';
        
        return $wpdb->insert(
            $table_name,
            [
                'test_name' => 'Performance Suite - ' . date('Y-m-d H:i:s'),
                'test_type' => 'comprehensive',
                'metrics' => json_encode($results),
                'baseline_value' => $results['overall_score'],
                'current_value' => $results['overall_score'],
                'threshold_min' => 70,
                'threshold_max' => 100,
                'status' => $results['overall_score'] >= 70 ? 'passed' : 'failed',
                'environment' => 'development',
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%f', '%f', '%f', '%f', '%s', '%s', '%s']
        );
    }
    
    /**
     * AJAX handler for running performance tests
     */
    public function ajax_run_performance_test() {
        check_ajax_referer('etq_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $test_config = [];
        if (isset($_POST['config'])) {
            $test_config = json_decode(stripslashes($_POST['config']), true);
        }
        
        $results = $this->run_performance_test_suite($test_config);
        
        wp_send_json_success($results);
    }
    
    /**
     * AJAX handler for getting performance metrics
     */
    public function ajax_get_performance_metrics() {
        check_ajax_referer('etq_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'etq_performance_benchmarks';
        $metrics = $wpdb->get_results(
            "SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT 10",
            ARRAY_A
        );
        
        wp_send_json_success($metrics);
    }
    
    /**
     * Helper methods
     */
    private function start_performance_monitoring() {
        // Initialize performance monitoring
        $this->results['start_memory'] = memory_get_usage(true);
        $this->results['start_time'] = microtime(true);
    }
    
    private function stop_performance_monitoring() {
        $this->results['end_memory'] = memory_get_usage(true);
        $this->results['end_time'] = microtime(true);
    }
    
    private function convert_memory_limit_to_bytes($memory_limit) {
        $memory_limit = trim($memory_limit);
        $last = strtolower($memory_limit[strlen($memory_limit)-1]);
        $memory_limit = (int) $memory_limit;
        
        switch($last) {
            case 'g':
                $memory_limit *= 1024;
            case 'm':
                $memory_limit *= 1024;
            case 'k':
                $memory_limit *= 1024;
        }
        
        return $memory_limit;
    }
    
    private function get_recent_slow_queries() {
        // This would require access to slow query log
        // Implementation depends on server configuration
        return [];
    }
    
    private function simulate_page_load_queries() {
        // Simulate typical WordPress queries
        global $wpdb;
        
        // Typical queries that happen on page load
        $wpdb->get_results("SELECT * FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' LIMIT 10");
        $wpdb->get_results("SELECT * FROM {$wpdb->options} WHERE autoload = 'yes'");
        $wpdb->get_results("SELECT * FROM {$wpdb->users} LIMIT 5");
    }
    
    private function analyze_query_patterns() {
        $suggestions = [];
        
        // Basic optimization suggestions
        $suggestions[] = "Consider adding database indexes for frequently queried columns";
        $suggestions[] = "Use object caching to reduce database queries";
        $suggestions[] = "Optimize plugins that generate many queries";
        $suggestions[] = "Consider query result caching for expensive operations";
        
        return $suggestions;
    }
}
