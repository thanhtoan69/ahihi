<?php
/**
 * Manual Test Runner for Environmental Email Marketing Plugin
 * 
 * This file allows manual testing of plugin functionality
 * Run by accessing: /wp-admin/admin.php?page=eem-test-runner
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EEM_Manual_Test_Runner {
    
    private $results = [];
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_test_menu'));
        add_action('wp_ajax_eem_run_manual_test', array($this, 'run_manual_test'));
    }
    
    public function add_test_menu() {
        add_submenu_page(
            'environmental-email-marketing',
            'Test Runner',
            'Test Runner',
            'manage_options',
            'eem-test-runner',
            array($this, 'test_runner_page')
        );
    }
    
    public function test_runner_page() {
        ?>
        <div class="wrap">
            <h1>Environmental Email Marketing - Test Runner</h1>
            
            <div class="test-runner-controls">
                <button type="button" class="button button-primary" onclick="runAllTests()">Run All Tests</button>
                <button type="button" class="button" onclick="clearResults()">Clear Results</button>
            </div>
            
            <div id="test-results" class="test-results">
                <h2>Test Results</h2>
                <div id="test-output"></div>
            </div>
            
            <div class="test-categories">
                <h2>Available Tests</h2>
                
                <div class="test-category">
                    <h3>Database Tests</h3>
                    <button class="button" onclick="runTest('database_creation')">Test Database Creation</button>
                    <button class="button" onclick="runTest('database_operations')">Test Database Operations</button>
                </div>
                
                <div class="test-category">
                    <h3>Subscriber Tests</h3>
                    <button class="button" onclick="runTest('subscriber_creation')">Test Subscriber Creation</button>
                    <button class="button" onclick="runTest('subscriber_preferences')">Test Subscriber Preferences</button>
                    <button class="button" onclick="runTest('double_optin')">Test Double Opt-in</button>
                </div>
                
                <div class="test-category">
                    <h3>Campaign Tests</h3>
                    <button class="button" onclick="runTest('campaign_creation')">Test Campaign Creation</button>
                    <button class="button" onclick="runTest('campaign_scheduling')">Test Campaign Scheduling</button>
                    <button class="button" onclick="runTest('email_sending')">Test Email Sending</button>
                </div>
                
                <div class="test-category">
                    <h3>Template Tests</h3>
                    <button class="button" onclick="runTest('template_rendering')">Test Template Rendering</button>
                    <button class="button" onclick="runTest('template_variables')">Test Template Variables</button>
                </div>
                
                <div class="test-category">
                    <h3>Analytics Tests</h3>
                    <button class="button" onclick="runTest('analytics_tracking')">Test Analytics Tracking</button>
                    <button class="button" onclick="runTest('click_tracking')">Test Click Tracking</button>
                </div>
                
                <div class="test-category">
                    <h3>Integration Tests</h3>
                    <button class="button" onclick="runTest('ajax_endpoints')">Test AJAX Endpoints</button>
                    <button class="button" onclick="runTest('rest_api')">Test REST API</button>
                    <button class="button" onclick="runTest('cron_jobs')">Test Cron Jobs</button>
                </div>
            </div>
        </div>
        
        <style>
            .test-runner-controls {
                margin: 20px 0;
                padding: 15px;
                background: #f1f1f1;
                border-radius: 5px;
            }
            
            .test-results {
                margin: 20px 0;
                padding: 15px;
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 5px;
                min-height: 200px;
            }
            
            .test-categories {
                margin: 20px 0;
            }
            
            .test-category {
                margin: 15px 0;
                padding: 15px;
                background: #f9f9f9;
                border-radius: 5px;
            }
            
            .test-category h3 {
                margin-top: 0;
                color: #2c5aa0;
            }
            
            .test-category button {
                margin: 5px 5px 5px 0;
            }
            
            .test-result {
                margin: 10px 0;
                padding: 10px;
                border-radius: 3px;
            }
            
            .test-result.passed {
                background: #d4edda;
                border: 1px solid #c3e6cb;
                color: #155724;
            }
            
            .test-result.failed {
                background: #f8d7da;
                border: 1px solid #f5c6cb;
                color: #721c24;
            }
            
            .test-result.warning {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                color: #856404;
            }
        </style>
        
        <script>
            function runTest(testName) {
                const output = document.getElementById('test-output');
                
                // Add loading indicator
                const loadingDiv = document.createElement('div');
                loadingDiv.className = 'test-result';
                loadingDiv.innerHTML = `<strong>${testName}</strong>: Running...`;
                output.appendChild(loadingDiv);
                
                // Make AJAX request
                fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=eem_run_manual_test&test_name=${testName}&_wpnonce=${eem_test_nonce}`
                })
                .then(response => response.json())
                .then(data => {
                    loadingDiv.className = `test-result ${data.success ? 'passed' : 'failed'}`;
                    loadingDiv.innerHTML = `<strong>${testName}</strong>: ${data.message}`;
                    if (data.details) {
                        loadingDiv.innerHTML += `<br><small>${data.details}</small>`;
                    }
                })
                .catch(error => {
                    loadingDiv.className = 'test-result failed';
                    loadingDiv.innerHTML = `<strong>${testName}</strong>: Error - ${error.message}`;
                });
            }
            
            function runAllTests() {
                const tests = [
                    'database_creation', 'database_operations',
                    'subscriber_creation', 'subscriber_preferences', 'double_optin',
                    'campaign_creation', 'campaign_scheduling', 'email_sending',
                    'template_rendering', 'template_variables',
                    'analytics_tracking', 'click_tracking',
                    'ajax_endpoints', 'rest_api', 'cron_jobs'
                ];
                
                tests.forEach((test, index) => {
                    setTimeout(() => runTest(test), index * 500);
                });
            }
            
            function clearResults() {
                document.getElementById('test-output').innerHTML = '';
            }
        </script>
        
        <?php
        // Add nonce for AJAX security
        wp_nonce_field('eem_test_nonce', 'eem_test_nonce');
        ?>
        <script>
            const eem_test_nonce = '<?php echo wp_create_nonce('eem_test_nonce'); ?>';
        </script>
        <?php
    }
    
    public function run_manual_test() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'], 'eem_test_nonce')) {
            wp_die('Security check failed');
        }
        
        $test_name = sanitize_text_field($_POST['test_name']);
        $result = $this->execute_test($test_name);
        
        wp_send_json($result);
    }
    
    private function execute_test($test_name) {
        global $wpdb;
        
        try {
            switch ($test_name) {
                case 'database_creation':
                    return $this->test_database_creation();
                    
                case 'database_operations':
                    return $this->test_database_operations();
                    
                case 'subscriber_creation':
                    return $this->test_subscriber_creation();
                    
                case 'subscriber_preferences':
                    return $this->test_subscriber_preferences();
                    
                case 'double_optin':
                    return $this->test_double_optin();
                    
                case 'campaign_creation':
                    return $this->test_campaign_creation();
                    
                case 'campaign_scheduling':
                    return $this->test_campaign_scheduling();
                    
                case 'email_sending':
                    return $this->test_email_sending();
                    
                case 'template_rendering':
                    return $this->test_template_rendering();
                    
                case 'template_variables':
                    return $this->test_template_variables();
                    
                case 'analytics_tracking':
                    return $this->test_analytics_tracking();
                    
                case 'click_tracking':
                    return $this->test_click_tracking();
                    
                case 'ajax_endpoints':
                    return $this->test_ajax_endpoints();
                    
                case 'rest_api':
                    return $this->test_rest_api();
                    
                case 'cron_jobs':
                    return $this->test_cron_jobs();
                    
                default:
                    return array(
                        'success' => false,
                        'message' => 'Unknown test: ' . $test_name
                    );
            }
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Test failed with exception',
                'details' => $e->getMessage()
            );
        }
    }
    
    private function test_database_creation() {
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
        
        $missing_tables = array();
        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $result = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
            if ($result !== $table_name) {
                $missing_tables[] = $table;
            }
        }
        
        if (empty($missing_tables)) {
            return array(
                'success' => true,
                'message' => 'All database tables exist',
                'details' => count($tables) . ' tables verified'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Missing database tables',
                'details' => 'Missing: ' . implode(', ', $missing_tables)
            );
        }
    }
    
    private function test_database_operations() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eem_subscribers';
        
        // Test insert
        $test_email = 'test_' . time() . '@example.com';
        $result = $wpdb->insert(
            $table_name,
            array(
                'email' => $test_email,
                'status' => 'active',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s')
        );
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => 'Database insert failed',
                'details' => $wpdb->last_error
            );
        }
        
        $subscriber_id = $wpdb->insert_id;
        
        // Test select
        $subscriber = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $subscriber_id)
        );
        
        if (!$subscriber) {
            return array(
                'success' => false,
                'message' => 'Database select failed'
            );
        }
        
        // Test update
        $result = $wpdb->update(
            $table_name,
            array('first_name' => 'Test User'),
            array('id' => $subscriber_id),
            array('%s'),
            array('%d')
        );
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => 'Database update failed',
                'details' => $wpdb->last_error
            );
        }
        
        // Test delete
        $result = $wpdb->delete(
            $table_name,
            array('id' => $subscriber_id),
            array('%d')
        );
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => 'Database delete failed',
                'details' => $wpdb->last_error
            );
        }
        
        return array(
            'success' => true,
            'message' => 'Database operations successful',
            'details' => 'Insert, select, update, and delete operations completed'
        );
    }
    
    private function test_subscriber_creation() {
        if (!class_exists('EEM_Subscriber_Manager')) {
            return array(
                'success' => false,
                'message' => 'EEM_Subscriber_Manager class not found'
            );
        }
        
        $subscriber_manager = new EEM_Subscriber_Manager();
        $test_email = 'test_subscriber_' . time() . '@example.com';
        
        $result = $subscriber_manager->add_subscriber(array(
            'email' => $test_email,
            'first_name' => 'Test',
            'last_name' => 'User',
            'status' => 'pending'
        ));
        
        if ($result) {
            // Clean up
            $subscriber_manager->delete_subscriber($result);
            
            return array(
                'success' => true,
                'message' => 'Subscriber creation successful',
                'details' => 'Subscriber ID: ' . $result
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Subscriber creation failed'
            );
        }
    }
    
    private function test_subscriber_preferences() {
        if (!class_exists('EEM_Subscriber_Manager')) {
            return array(
                'success' => false,
                'message' => 'EEM_Subscriber_Manager class not found'
            );
        }
        
        $subscriber_manager = new EEM_Subscriber_Manager();
        $test_email = 'test_prefs_' . time() . '@example.com';
        
        // Create test subscriber
        $subscriber_id = $subscriber_manager->add_subscriber(array(
            'email' => $test_email,
            'status' => 'active'
        ));
        
        if (!$subscriber_id) {
            return array(
                'success' => false,
                'message' => 'Failed to create test subscriber'
            );
        }
        
        // Test setting preferences
        $preferences = array(
            'climate_change' => 1,
            'renewable_energy' => 1,
            'sustainability' => 0,
            'frequency' => 'weekly'
        );
        
        $result = $subscriber_manager->update_preferences($subscriber_id, $preferences);
        
        // Clean up
        $subscriber_manager->delete_subscriber($subscriber_id);
        
        if ($result) {
            return array(
                'success' => true,
                'message' => 'Subscriber preferences test successful'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Subscriber preferences test failed'
            );
        }
    }
    
    private function test_double_optin() {
        if (!class_exists('EEM_Subscriber_Manager')) {
            return array(
                'success' => false,
                'message' => 'EEM_Subscriber_Manager class not found'
            );
        }
        
        $subscriber_manager = new EEM_Subscriber_Manager();
        $test_email = 'test_optin_' . time() . '@example.com';
        
        // Create subscriber with pending status
        $subscriber_id = $subscriber_manager->add_subscriber(array(
            'email' => $test_email,
            'status' => 'pending'
        ));
        
        if (!$subscriber_id) {
            return array(
                'success' => false,
                'message' => 'Failed to create test subscriber'
            );
        }
        
        // Test confirmation
        $token = $subscriber_manager->get_confirmation_token($subscriber_id);
        if (!$token) {
            $subscriber_manager->delete_subscriber($subscriber_id);
            return array(
                'success' => false,
                'message' => 'Failed to generate confirmation token'
            );
        }
        
        $result = $subscriber_manager->confirm_subscription($token);
        
        // Clean up
        $subscriber_manager->delete_subscriber($subscriber_id);
        
        if ($result) {
            return array(
                'success' => true,
                'message' => 'Double opt-in test successful'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Double opt-in test failed'
            );
        }
    }
    
    private function test_campaign_creation() {
        if (!class_exists('EEM_Campaign_Manager')) {
            return array(
                'success' => false,
                'message' => 'EEM_Campaign_Manager class not found'
            );
        }
        
        $campaign_manager = new EEM_Campaign_Manager();
        
        $campaign_data = array(
            'name' => 'Test Campaign ' . time(),
            'subject' => 'Test Subject',
            'content' => 'Test content',
            'status' => 'draft',
            'type' => 'regular'
        );
        
        $campaign_id = $campaign_manager->create_campaign($campaign_data);
        
        if ($campaign_id) {
            // Clean up
            $campaign_manager->delete_campaign($campaign_id);
            
            return array(
                'success' => true,
                'message' => 'Campaign creation successful',
                'details' => 'Campaign ID: ' . $campaign_id
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Campaign creation failed'
            );
        }
    }
    
    private function test_campaign_scheduling() {
        if (!class_exists('EEM_Campaign_Manager')) {
            return array(
                'success' => false,
                'message' => 'EEM_Campaign_Manager class not found'
            );
        }
        
        $campaign_manager = new EEM_Campaign_Manager();
        
        // Create test campaign
        $campaign_data = array(
            'name' => 'Test Scheduled Campaign ' . time(),
            'subject' => 'Test Subject',
            'content' => 'Test content',
            'status' => 'draft',
            'type' => 'regular'
        );
        
        $campaign_id = $campaign_manager->create_campaign($campaign_data);
        
        if (!$campaign_id) {
            return array(
                'success' => false,
                'message' => 'Failed to create test campaign'
            );
        }
        
        // Schedule campaign
        $scheduled_time = current_time('mysql', 1) + 3600; // 1 hour from now
        $result = $campaign_manager->schedule_campaign($campaign_id, $scheduled_time);
        
        // Clean up
        $campaign_manager->delete_campaign($campaign_id);
        
        if ($result) {
            return array(
                'success' => true,
                'message' => 'Campaign scheduling successful'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Campaign scheduling failed'
            );
        }
    }
    
    private function test_email_sending() {
        // Test email sending functionality
        $to = get_option('admin_email');
        $subject = 'EEM Test Email - ' . date('Y-m-d H:i:s');
        $message = 'This is a test email from the Environmental Email Marketing plugin.';
        
        $result = wp_mail($to, $subject, $message);
        
        if ($result) {
            return array(
                'success' => true,
                'message' => 'Email sending test successful',
                'details' => 'Test email sent to: ' . $to
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Email sending test failed'
            );
        }
    }
    
    private function test_template_rendering() {
        if (!class_exists('EEM_Template_Engine')) {
            return array(
                'success' => false,
                'message' => 'EEM_Template_Engine class not found'
            );
        }
        
        $template_engine = new EEM_Template_Engine();
        
        $template_data = array(
            'subscriber_name' => 'Test User',
            'campaign_name' => 'Test Campaign',
            'unsubscribe_url' => 'https://example.com/unsubscribe'
        );
        
        $content = 'Hello {{subscriber_name}}, welcome to {{campaign_name}}!';
        $rendered = $template_engine->render_template($content, $template_data);
        
        if (strpos($rendered, 'Test User') !== false && strpos($rendered, 'Test Campaign') !== false) {
            return array(
                'success' => true,
                'message' => 'Template rendering successful',
                'details' => 'Variables replaced correctly'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Template rendering failed',
                'details' => 'Variables not replaced properly'
            );
        }
    }
    
    private function test_template_variables() {
        if (!class_exists('EEM_Template_Engine')) {
            return array(
                'success' => false,
                'message' => 'EEM_Template_Engine class not found'
            );
        }
        
        $template_engine = new EEM_Template_Engine();
        
        // Test complex template with conditionals
        $template_data = array(
            'subscriber_name' => 'John Doe',
            'show_promo' => true,
            'promo_code' => 'SAVE20',
            'carbon_savings' => '50kg'
        );
        
        $content = 'Hello {{subscriber_name}}! {{#show_promo}}Use code {{promo_code}} for savings!{{/show_promo}} You saved {{carbon_savings}} of CO2.';
        $rendered = $template_engine->render_template($content, $template_data);
        
        $expected_parts = array('John Doe', 'SAVE20', '50kg');
        $all_present = true;
        
        foreach ($expected_parts as $part) {
            if (strpos($rendered, $part) === false) {
                $all_present = false;
                break;
            }
        }
        
        if ($all_present) {
            return array(
                'success' => true,
                'message' => 'Template variables test successful',
                'details' => 'All variables and conditionals rendered correctly'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Template variables test failed',
                'details' => 'Some variables not rendered properly'
            );
        }
    }
    
    private function test_analytics_tracking() {
        if (!class_exists('EEM_Analytics_Tracker')) {
            return array(
                'success' => false,
                'message' => 'EEM_Analytics_Tracker class not found'
            );
        }
        
        $analytics = new EEM_Analytics_Tracker();
        
        // Test tracking an event
        $result = $analytics->track_event('test_event', array(
            'campaign_id' => 999,
            'subscriber_id' => 999,
            'event_data' => array('test' => 'data')
        ));
        
        if ($result) {
            return array(
                'success' => true,
                'message' => 'Analytics tracking successful',
                'details' => 'Test event tracked successfully'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Analytics tracking failed'
            );
        }
    }
    
    private function test_click_tracking() {
        if (!class_exists('EEM_Analytics_Tracker')) {
            return array(
                'success' => false,
                'message' => 'EEM_Analytics_Tracker class not found'
            );
        }
        
        $analytics = new EEM_Analytics_Tracker();
        
        // Test click tracking
        $result = $analytics->track_click(999, 999, 'https://example.com');
        
        if ($result) {
            return array(
                'success' => true,
                'message' => 'Click tracking successful'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Click tracking failed'
            );
        }
    }
    
    private function test_ajax_endpoints() {
        // Test if AJAX actions are registered
        global $wp_filter;
        
        $ajax_actions = array(
            'eem_subscribe',
            'eem_unsubscribe',
            'eem_update_preferences',
            'eem_send_campaign',
            'eem_get_analytics'
        );
        
        $missing_actions = array();
        foreach ($ajax_actions as $action) {
            if (!isset($wp_filter['wp_ajax_' . $action]) && !isset($wp_filter['wp_ajax_nopriv_' . $action])) {
                $missing_actions[] = $action;
            }
        }
        
        if (empty($missing_actions)) {
            return array(
                'success' => true,
                'message' => 'AJAX endpoints registered successfully',
                'details' => count($ajax_actions) . ' endpoints found'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Missing AJAX endpoints',
                'details' => 'Missing: ' . implode(', ', $missing_actions)
            );
        }
    }
    
    private function test_rest_api() {
        if (!class_exists('EEM_REST_API')) {
            return array(
                'success' => false,
                'message' => 'EEM_REST_API class not found'
            );
        }
        
        // Test REST API registration
        $rest_server = rest_get_server();
        $routes = $rest_server->get_routes();
        
        $api_routes = array(
            '/eem/v1/subscribers',
            '/eem/v1/campaigns',
            '/eem/v1/analytics'
        );
        
        $found_routes = 0;
        foreach ($api_routes as $route) {
            if (isset($routes[$route])) {
                $found_routes++;
            }
        }
        
        if ($found_routes === count($api_routes)) {
            return array(
                'success' => true,
                'message' => 'REST API endpoints registered successfully',
                'details' => $found_routes . ' routes found'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Some REST API endpoints missing',
                'details' => $found_routes . ' of ' . count($api_routes) . ' routes found'
            );
        }
    }
    
    private function test_cron_jobs() {
        // Test cron job registration
        $cron_jobs = array(
            'eem_process_automation_queue',
            'eem_process_campaign_queue',
            'eem_sync_providers',
            'eem_cleanup_data'
        );
        
        $missing_jobs = array();
        foreach ($cron_jobs as $job) {
            if (!wp_next_scheduled($job)) {
                $missing_jobs[] = $job;
            }
        }
        
        if (empty($missing_jobs)) {
            return array(
                'success' => true,
                'message' => 'Cron jobs scheduled successfully',
                'details' => count($cron_jobs) . ' jobs scheduled'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Some cron jobs not scheduled',
                'details' => 'Missing: ' . implode(', ', $missing_jobs)
            );
        }
    }
}

// Initialize the test runner
new EEM_Manual_Test_Runner();
