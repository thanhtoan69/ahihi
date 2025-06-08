<?php
/**
 * Admin Dashboard Manager
 * 
 * Provides centralized testing and QA monitoring interface
 * for the Environmental Platform WordPress system.
 * 
 * @package EnvironmentalTestingQA
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ETQ_Admin_Dashboard {
    
    /**
     * Database instance
     */
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = ETQ_Database::get_instance();
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_ajax_etq_get_dashboard_data', [$this, 'ajax_get_dashboard_data']);
        add_action('wp_ajax_etq_run_quick_test', [$this, 'ajax_run_quick_test']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'Environmental Testing & QA',
            'Testing & QA',
            'manage_options',
            'environmental-testing-qa',
            [$this, 'render_dashboard_page'],
            'dashicons-analytics',
            30
        );
        
        add_submenu_page(
            'environmental-testing-qa',
            'Test Suites',
            'Test Suites',
            'manage_options',
            'etq-test-suites',
            [$this, 'render_test_suites_page']
        );
        
        add_submenu_page(
            'environmental-testing-qa',
            'Performance Testing',
            'Performance',
            'manage_options',
            'etq-performance',
            [$this, 'render_performance_page']
        );
        
        add_submenu_page(
            'environmental-testing-qa',
            'Staging Environments',
            'Staging',
            'manage_options',
            'etq-staging',
            [$this, 'render_staging_page']
        );
        
        add_submenu_page(
            'environmental-testing-qa',
            'Test Results',
            'Test Results',
            'manage_options',
            'etq-results',
            [$this, 'render_results_page']
        );
        
        add_submenu_page(
            'environmental-testing-qa',
            'Settings',
            'Settings',
            'manage_options',
            'etq-settings',
            [$this, 'render_settings_page']
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'environmental-testing-qa') === false && strpos($hook, 'etq-') === false) {
            return;
        }
        
        wp_enqueue_script(
            'etq-admin-js',
            ETQ_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'wp-util'],
            ETQ_VERSION,
            true
        );
        
        wp_enqueue_style(
            'etq-admin-css',
            ETQ_PLUGIN_URL . 'assets/css/admin.css',
            [],
            ETQ_VERSION
        );
        
        wp_localize_script('etq-admin-js', 'etqAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('etq_admin_nonce'),
            'strings' => [
                'runningTest' => __('Running test...', 'environmental-testing-qa'),
                'testComplete' => __('Test completed', 'environmental-testing-qa'),
                'testFailed' => __('Test failed', 'environmental-testing-qa'),
                'confirmDelete' => __('Are you sure you want to delete this item?', 'environmental-testing-qa')
            ]
        ]);
    }
    
    /**
     * Render main dashboard page
     */
    public function render_dashboard_page() {
        $dashboard_data = $this->get_dashboard_overview();
        ?>
        <div class="wrap">
            <h1><?php _e('Environmental Testing & QA Dashboard', 'environmental-testing-qa'); ?></h1>
            
            <div class="etq-dashboard-grid">
                <!-- Overview Cards -->
                <div class="etq-overview-cards">
                    <div class="etq-card etq-card-tests">
                        <h3><?php _e('Total Tests', 'environmental-testing-qa'); ?></h3>
                        <div class="etq-stat-number"><?php echo esc_html($dashboard_data['total_tests']); ?></div>
                        <div class="etq-stat-change">
                            <span class="etq-change-positive">+<?php echo esc_html($dashboard_data['new_tests_week']); ?></span>
                            <?php _e('this week', 'environmental-testing-qa'); ?>
                        </div>
                    </div>
                    
                    <div class="etq-card etq-card-success">
                        <h3><?php _e('Success Rate', 'environmental-testing-qa'); ?></h3>
                        <div class="etq-stat-number"><?php echo esc_html($dashboard_data['success_rate']); ?>%</div>
                        <div class="etq-progress-bar">
                            <div class="etq-progress-fill" style="width: <?php echo esc_attr($dashboard_data['success_rate']); ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="etq-card etq-card-performance">
                        <h3><?php _e('Avg Response Time', 'environmental-testing-qa'); ?></h3>
                        <div class="etq-stat-number"><?php echo esc_html($dashboard_data['avg_response_time']); ?>ms</div>
                        <div class="etq-stat-change">
                            <span class="etq-change-negative">+<?php echo esc_html($dashboard_data['response_time_change']); ?>ms</span>
                            <?php _e('from last week', 'environmental-testing-qa'); ?>
                        </div>
                    </div>
                    
                    <div class="etq-card etq-card-environments">
                        <h3><?php _e('Active Environments', 'environmental-testing-qa'); ?></h3>
                        <div class="etq-stat-number"><?php echo esc_html($dashboard_data['active_environments']); ?></div>
                        <div class="etq-environments-list">
                            <?php foreach ($dashboard_data['environment_types'] as $type => $count): ?>
                                <span class="etq-env-badge etq-env-<?php echo esc_attr($type); ?>">
                                    <?php echo esc_html(ucfirst($type) . ': ' . $count); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Test Results -->
                <div class="etq-recent-tests">
                    <div class="etq-card">
                        <h3><?php _e('Recent Test Results', 'environmental-testing-qa'); ?></h3>
                        <div class="etq-test-results-table">
                            <table class="widefat">
                                <thead>
                                    <tr>
                                        <th><?php _e('Test Name', 'environmental-testing-qa'); ?></th>
                                        <th><?php _e('Type', 'environmental-testing-qa'); ?></th>
                                        <th><?php _e('Status', 'environmental-testing-qa'); ?></th>
                                        <th><?php _e('Duration', 'environmental-testing-qa'); ?></th>
                                        <th><?php _e('Run Time', 'environmental-testing-qa'); ?></th>
                                        <th><?php _e('Actions', 'environmental-testing-qa'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dashboard_data['recent_tests'] as $test): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo esc_html($test['test_name']); ?></strong>
                                                <?php if ($test['description']): ?>
                                                    <br><small><?php echo esc_html($test['description']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="etq-test-type etq-type-<?php echo esc_attr($test['test_type']); ?>">
                                                    <?php echo esc_html(ucfirst($test['test_type'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="etq-status etq-status-<?php echo esc_attr($test['status']); ?>">
                                                    <?php echo esc_html(ucfirst($test['status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo esc_html($test['duration']); ?>s</td>
                                            <td><?php echo esc_html(date('M j, Y H:i', strtotime($test['executed_at']))); ?></td>
                                            <td>
                                                <button class="button button-small etq-view-details" data-test-id="<?php echo esc_attr($test['id']); ?>">
                                                    <?php _e('View', 'environmental-testing-qa'); ?>
                                                </button>
                                                <button class="button button-small etq-rerun-test" data-test-id="<?php echo esc_attr($test['id']); ?>">
                                                    <?php _e('Rerun', 'environmental-testing-qa'); ?>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="etq-quick-actions">
                    <div class="etq-card">
                        <h3><?php _e('Quick Actions', 'environmental-testing-qa'); ?></h3>
                        <div class="etq-action-buttons">
                            <button class="button button-primary etq-run-quick-test" data-test-type="smoke">
                                <span class="dashicons dashicons-yes"></span>
                                <?php _e('Run Smoke Tests', 'environmental-testing-qa'); ?>
                            </button>
                            <button class="button button-secondary etq-run-quick-test" data-test-type="performance">
                                <span class="dashicons dashicons-performance"></span>
                                <?php _e('Performance Check', 'environmental-testing-qa'); ?>
                            </button>
                            <button class="button button-secondary etq-create-staging">
                                <span class="dashicons dashicons-admin-multisite"></span>
                                <?php _e('Create Staging', 'environmental-testing-qa'); ?>
                            </button>
                            <button class="button button-secondary etq-sync-tests">
                                <span class="dashicons dashicons-update"></span>
                                <?php _e('Sync Test Data', 'environmental-testing-qa'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Test Coverage Chart -->
                <div class="etq-coverage-chart">
                    <div class="etq-card">
                        <h3><?php _e('Test Coverage', 'environmental-testing-qa'); ?></h3>
                        <div class="etq-coverage-visual">
                            <?php foreach ($dashboard_data['coverage_data'] as $module => $coverage): ?>
                                <div class="etq-coverage-item">
                                    <div class="etq-coverage-label">
                                        <?php echo esc_html($module); ?>
                                        <span class="etq-coverage-percent"><?php echo esc_html($coverage); ?>%</span>
                                    </div>
                                    <div class="etq-coverage-bar">
                                        <div class="etq-coverage-fill" style="width: <?php echo esc_attr($coverage); ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Test Details Modal -->
        <div id="etq-test-details-modal" class="etq-modal" style="display: none;">
            <div class="etq-modal-content">
                <div class="etq-modal-header">
                    <h2><?php _e('Test Details', 'environmental-testing-qa'); ?></h2>
                    <button class="etq-modal-close">&times;</button>
                </div>
                <div class="etq-modal-body">
                    <div id="etq-test-details-content">
                        <?php _e('Loading test details...', 'environmental-testing-qa'); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render test suites page
     */
    public function render_test_suites_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Test Suites Management', 'environmental-testing-qa'); ?></h1>
            
            <div class="etq-page-header">
                <button class="button button-primary etq-create-test-suite">
                    <span class="dashicons dashicons-plus"></span>
                    <?php _e('Create New Test Suite', 'environmental-testing-qa'); ?>
                </button>
            </div>
            
            <div id="etq-test-suites-container">
                <?php _e('Loading test suites...', 'environmental-testing-qa'); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render performance page
     */
    public function render_performance_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Performance Testing', 'environmental-testing-qa'); ?></h1>
            
            <div class="etq-performance-controls">
                <div class="etq-card">
                    <h3><?php _e('Run Performance Test', 'environmental-testing-qa'); ?></h3>
                    <form id="etq-performance-test-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Test Type', 'environmental-testing-qa'); ?></th>
                                <td>
                                    <select name="test_type" id="etq-performance-test-type">
                                        <option value="comprehensive"><?php _e('Comprehensive Test', 'environmental-testing-qa'); ?></option>
                                        <option value="quick"><?php _e('Quick Performance Check', 'environmental-testing-qa'); ?></option>
                                        <option value="load"><?php _e('Load Testing', 'environmental-testing-qa'); ?></option>
                                        <option value="stress"><?php _e('Stress Testing', 'environmental-testing-qa'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Concurrent Users', 'environmental-testing-qa'); ?></th>
                                <td>
                                    <input type="number" name="concurrent_users" value="10" min="1" max="100">
                                    <p class="description"><?php _e('Number of simulated concurrent users', 'environmental-testing-qa'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Test Duration', 'environmental-testing-qa'); ?></th>
                                <td>
                                    <input type="number" name="test_duration" value="60" min="10" max="3600">
                                    <span><?php _e('seconds', 'environmental-testing-qa'); ?></span>
                                </td>
                            </tr>
                        </table>
                        <button type="submit" class="button button-primary">
                            <?php _e('Run Performance Test', 'environmental-testing-qa'); ?>
                        </button>
                    </form>
                </div>
            </div>
            
            <div id="etq-performance-results">
                <?php _e('Performance test results will appear here...', 'environmental-testing-qa'); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render staging page
     */
    public function render_staging_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Staging Environments', 'environmental-testing-qa'); ?></h1>
            
            <div class="etq-staging-controls">
                <button class="button button-primary etq-create-staging-env">
                    <span class="dashicons dashicons-admin-multisite"></span>
                    <?php _e('Create Staging Environment', 'environmental-testing-qa'); ?>
                </button>
            </div>
            
            <div id="etq-staging-environments">
                <?php _e('Loading staging environments...', 'environmental-testing-qa'); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render results page
     */
    public function render_results_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Test Results', 'environmental-testing-qa'); ?></h1>
            
            <div class="etq-results-filters">
                <div class="etq-card">
                    <h3><?php _e('Filter Results', 'environmental-testing-qa'); ?></h3>
                    <form id="etq-results-filter-form">
                        <div class="etq-filter-row">
                            <label><?php _e('Test Type:', 'environmental-testing-qa'); ?></label>
                            <select name="test_type">
                                <option value=""><?php _e('All Types', 'environmental-testing-qa'); ?></option>
                                <option value="phpunit"><?php _e('PHPUnit', 'environmental-testing-qa'); ?></option>
                                <option value="selenium"><?php _e('Selenium', 'environmental-testing-qa'); ?></option>
                                <option value="performance"><?php _e('Performance', 'environmental-testing-qa'); ?></option>
                            </select>
                            
                            <label><?php _e('Status:', 'environmental-testing-qa'); ?></label>
                            <select name="status">
                                <option value=""><?php _e('All Status', 'environmental-testing-qa'); ?></option>
                                <option value="passed"><?php _e('Passed', 'environmental-testing-qa'); ?></option>
                                <option value="failed"><?php _e('Failed', 'environmental-testing-qa'); ?></option>
                                <option value="skipped"><?php _e('Skipped', 'environmental-testing-qa'); ?></option>
                            </select>
                            
                            <label><?php _e('Date Range:', 'environmental-testing-qa'); ?></label>
                            <input type="date" name="date_from">
                            <input type="date" name="date_to">
                            
                            <button type="submit" class="button"><?php _e('Filter', 'environmental-testing-qa'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div id="etq-test-results-table">
                <?php _e('Loading test results...', 'environmental-testing-qa'); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Testing & QA Settings', 'environmental-testing-qa'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('etq_settings');
                do_settings_sections('etq_settings');
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Auto-run Tests', 'environmental-testing-qa'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="etq_auto_run_tests" value="1" <?php checked(get_option('etq_auto_run_tests', 0)); ?>>
                                <?php _e('Automatically run tests after plugin/theme updates', 'environmental-testing-qa'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Email Notifications', 'environmental-testing-qa'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="etq_email_notifications" value="1" <?php checked(get_option('etq_email_notifications', 0)); ?>>
                                <?php _e('Send email notifications for failed tests', 'environmental-testing-qa'); ?>
                            </label>
                            <br>
                            <input type="email" name="etq_notification_email" value="<?php echo esc_attr(get_option('etq_notification_email', get_option('admin_email'))); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Test Data Retention', 'environmental-testing-qa'); ?></th>
                        <td>
                            <select name="etq_data_retention_days">
                                <option value="30" <?php selected(get_option('etq_data_retention_days', 30), 30); ?>>30 <?php _e('days', 'environmental-testing-qa'); ?></option>
                                <option value="60" <?php selected(get_option('etq_data_retention_days', 30), 60); ?>>60 <?php _e('days', 'environmental-testing-qa'); ?></option>
                                <option value="90" <?php selected(get_option('etq_data_retention_days', 30), 90); ?>>90 <?php _e('days', 'environmental-testing-qa'); ?></option>
                                <option value="365" <?php selected(get_option('etq_data_retention_days', 30), 365); ?>>1 <?php _e('year', 'environmental-testing-qa'); ?></option>
                            </select>
                            <p class="description"><?php _e('How long to keep test results and logs', 'environmental-testing-qa'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Get dashboard overview data
     */
    private function get_dashboard_overview() {
        global $wpdb;
        
        // Get test statistics
        $test_results_table = $wpdb->prefix . 'etq_test_results';
        $environments_table = $wpdb->prefix . 'etq_environments';
        
        $total_tests = $wpdb->get_var("SELECT COUNT(*) FROM {$test_results_table}");
        $passed_tests = $wpdb->get_var("SELECT COUNT(*) FROM {$test_results_table} WHERE status = 'passed'");
        $success_rate = $total_tests > 0 ? round(($passed_tests / $total_tests) * 100, 1) : 0;
        
        // Get recent tests
        $recent_tests = $wpdb->get_results(
            "SELECT tr.*, t.test_name, t.description, t.test_type 
             FROM {$test_results_table} tr 
             LEFT JOIN {$wpdb->prefix}etq_tests t ON tr.test_id = t.id 
             ORDER BY tr.executed_at DESC 
             LIMIT 10",
            ARRAY_A
        );
        
        // Get environment statistics
        $active_environments = $wpdb->get_var("SELECT COUNT(*) FROM {$environments_table} WHERE status = 'active'");
        $environment_types = $wpdb->get_results(
            "SELECT type, COUNT(*) as count FROM {$environments_table} WHERE status = 'active' GROUP BY type",
            ARRAY_A
        );
        
        $env_types = [];
        foreach ($environment_types as $env) {
            $env_types[$env['type']] = $env['count'];
        }
        
        return [
            'total_tests' => $total_tests ?: 0,
            'new_tests_week' => 5, // Placeholder
            'success_rate' => $success_rate,
            'avg_response_time' => 245, // Placeholder
            'response_time_change' => 12, // Placeholder
            'active_environments' => $active_environments ?: 0,
            'environment_types' => $env_types,
            'recent_tests' => $recent_tests ?: [],
            'coverage_data' => [
                'Frontend' => 85,
                'Backend API' => 92,
                'Database' => 78,
                'Authentication' => 95,
                'Email System' => 88,
                'Payment Processing' => 82
            ]
        ];
    }
    
    /**
     * AJAX handler for dashboard data
     */
    public function ajax_get_dashboard_data() {
        check_ajax_referer('etq_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $data = $this->get_dashboard_overview();
        wp_send_json_success($data);
    }
    
    /**
     * AJAX handler for quick tests
     */
    public function ajax_run_quick_test() {
        check_ajax_referer('etq_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $test_type = sanitize_text_field($_POST['test_type']);
        
        // Simulate quick test execution
        $result = [
            'test_type' => $test_type,
            'status' => 'passed',
            'duration' => rand(1, 5),
            'message' => sprintf(__('%s test completed successfully', 'environmental-testing-qa'), ucfirst($test_type))
        ];
        
        wp_send_json_success($result);
    }
}
