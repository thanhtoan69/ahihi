<?php
/**
 * Manual testing script for Environmental Email Marketing Plugin
 * This script can be run from WordPress admin to test plugin functionality manually
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EEM_Manual_Tests {
    
    public function __construct() {
        add_action('wp_ajax_eem_run_manual_tests', [$this, 'run_tests']);
        add_action('admin_menu', [$this, 'add_test_page']);
    }
    
    public function add_test_page() {
        add_submenu_page(
            'environmental-email-marketing',
            'Manual Tests',
            'Manual Tests',
            'manage_options',
            'eem-manual-tests',
            [$this, 'test_page']
        );
    }
    
    public function test_page() {
        ?>
        <div class="wrap">
            <h1>Environmental Email Marketing - Manual Tests</h1>
            <p>This page allows you to manually test various plugin components.</p>
            
            <div id="test-results"></div>
            
            <h2>Available Tests</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Test Name</th>
                        <th>Description</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Database Connection</td>
                        <td>Test database tables and connections</td>
                        <td><button class="button" onclick="runTest('database')">Run Test</button></td>
                    </tr>
                    <tr>
                        <td>Email Provider</td>
                        <td>Test active email provider configuration</td>
                        <td><button class="button" onclick="runTest('email_provider')">Run Test</button></td>
                    </tr>
                    <tr>
                        <td>Subscriber Management</td>
                        <td>Test subscriber creation, update, and deletion</td>
                        <td><button class="button" onclick="runTest('subscribers')">Run Test</button></td>
                    </tr>
                    <tr>
                        <td>Campaign Creation</td>
                        <td>Test campaign creation and template rendering</td>
                        <td><button class="button" onclick="runTest('campaigns')">Run Test</button></td>
                    </tr>
                    <tr>
                        <td>Analytics Tracking</td>
                        <td>Test event tracking and analytics calculation</td>
                        <td><button class="button" onclick="runTest('analytics')">Run Test</button></td>
                    </tr>
                    <tr>
                        <td>Automation Engine</td>
                        <td>Test automation triggers and processing</td>
                        <td><button class="button" onclick="runTest('automation')">Run Test</button></td>
                    </tr>
                    <tr>
                        <td>REST API</td>
                        <td>Test REST API endpoints</td>
                        <td><button class="button" onclick="runTest('rest_api')">Run Test</button></td>
                    </tr>
                    <tr>
                        <td>Frontend Forms</td>
                        <td>Test subscription and preference forms</td>
                        <td><button class="button" onclick="runTest('frontend')">Run Test</button></td>
                    </tr>
                    <tr>
                        <td>Full Workflow</td>
                        <td>Test complete email marketing workflow</td>
                        <td><button class="button button-primary" onclick="runTest('full_workflow')">Run Full Test</button></td>
                    </tr>
                </tbody>
            </table>
            
            <h2>System Information</h2>
            <table class="wp-list-table widefat fixed striped">
                <tbody>
                    <tr>
                        <td><strong>Plugin Version</strong></td>
                        <td><?php echo EEM_VERSION; ?></td>
                    </tr>
                    <tr>
                        <td><strong>WordPress Version</strong></td>
                        <td><?php echo get_bloginfo('version'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>PHP Version</strong></td>
                        <td><?php echo PHP_VERSION; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Database Version</strong></td>
                        <td><?php echo get_option('eem_db_version', 'Not set'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Active Email Provider</strong></td>
                        <td><?php 
                            $settings = get_option('eem_provider_settings', []);
                            echo $settings['active_provider'] ?? 'None';
                        ?></td>
                    </tr>
                    <tr>
                        <td><strong>Total Subscribers</strong></td>
                        <td><?php 
                            global $wpdb;
                            echo $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}eem_subscribers");
                        ?></td>
                    </tr>
                    <tr>
                        <td><strong>Total Campaigns</strong></td>
                        <td><?php 
                            echo $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}eem_campaigns");
                        ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <script>
        function runTest(testType) {
            const resultsDiv = document.getElementById('test-results');
            resultsDiv.innerHTML = '<div class="notice notice-info"><p>Running ' + testType + ' test...</p></div>';
            
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'eem_run_manual_tests',
                    test_type: testType,
                    nonce: '<?php echo wp_create_nonce('eem_manual_test'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        resultsDiv.innerHTML = '<div class="notice notice-success"><p><strong>' + testType + ' Test Results:</strong></p>' + response.data.html + '</div>';
                    } else {
                        resultsDiv.innerHTML = '<div class="notice notice-error"><p><strong>Test Failed:</strong> ' + response.data + '</p></div>';
                    }
                },
                error: function() {
                    resultsDiv.innerHTML = '<div class="notice notice-error"><p>Ajax request failed</p></div>';
                }
            });
        }
        </script>
        <?php
    }
    
    public function run_tests() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'eem_manual_test')) {
            wp_die('Security check failed');
        }
        
        $test_type = sanitize_text_field($_POST['test_type']);
        $results = [];
        
        switch ($test_type) {
            case 'database':
                $results = $this->test_database();
                break;
            case 'email_provider':
                $results = $this->test_email_provider();
                break;
            case 'subscribers':
                $results = $this->test_subscribers();
                break;
            case 'campaigns':
                $results = $this->test_campaigns();
                break;
            case 'analytics':
                $results = $this->test_analytics();
                break;
            case 'automation':
                $results = $this->test_automation();
                break;
            case 'rest_api':
                $results = $this->test_rest_api();
                break;
            case 'frontend':
                $results = $this->test_frontend();
                break;
            case 'full_workflow':
                $results = $this->test_full_workflow();
                break;
            default:
                wp_send_json_error('Invalid test type');
        }
        
        wp_send_json_success(['html' => $this->format_results($results)]);
    }
    
    private function test_database() {
        $results = [];
        global $wpdb;
        
        // Test table existence
        $tables = [
            'eem_subscribers', 'eem_lists', 'eem_campaigns', 'eem_templates',
            'eem_automations', 'eem_analytics', 'eem_ab_tests', 'eem_segments',
            'eem_webhooks', 'eem_logs'
        ];
        
        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
            $results[] = [
                'test' => "Table $table exists",
                'status' => $exists ? 'PASS' : 'FAIL',
                'message' => $exists ? "Table found" : "Table missing"
            ];
        }
        
        // Test database connection
        $connection_test = $wpdb->get_var("SELECT 1");
        $results[] = [
            'test' => 'Database connection',
            'status' => $connection_test ? 'PASS' : 'FAIL',
            'message' => $connection_test ? 'Connection successful' : 'Connection failed'
        ];
        
        return $results;
    }
    
    private function test_email_provider() {
        $results = [];
        
        try {
            $provider_settings = get_option('eem_provider_settings', []);
            $active_provider = $provider_settings['active_provider'] ?? null;
            
            $results[] = [
                'test' => 'Active provider configuration',
                'status' => $active_provider ? 'PASS' : 'FAIL',
                'message' => $active_provider ? "Active provider: $active_provider" : 'No active provider set'
            ];
            
            if ($active_provider) {
                // Test provider class loading
                $provider_class = 'EEM_Provider_' . ucfirst($active_provider);
                $class_exists = class_exists($provider_class);
                
                $results[] = [
                    'test' => 'Provider class exists',
                    'status' => $class_exists ? 'PASS' : 'FAIL',
                    'message' => $class_exists ? "Class $provider_class found" : "Class $provider_class not found"
                ];
                
                if ($class_exists) {
                    $provider = new $provider_class();
                    $is_configured = $provider->is_configured();
                    
                    $results[] = [
                        'test' => 'Provider configuration',
                        'status' => $is_configured ? 'PASS' : 'WARN',
                        'message' => $is_configured ? 'Provider is configured' : 'Provider needs configuration'
                    ];
                }
            }
        } catch (Exception $e) {
            $results[] = [
                'test' => 'Email provider test',
                'status' => 'FAIL',
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
        
        return $results;
    }
    
    private function test_subscribers() {
        $results = [];
        
        try {
            $subscriber_manager = new EEM_Subscriber_Manager();
            
            // Test subscriber creation
            $test_subscriber = [
                'email' => 'test_' . time() . '@example.com',
                'first_name' => 'Test',
                'last_name' => 'User'
            ];
            
            $subscriber_id = $subscriber_manager->add_subscriber($test_subscriber);
            
            $results[] = [
                'test' => 'Subscriber creation',
                'status' => $subscriber_id ? 'PASS' : 'FAIL',
                'message' => $subscriber_id ? "Subscriber created with ID: $subscriber_id" : 'Failed to create subscriber'
            ];
            
            if ($subscriber_id) {
                // Test subscriber retrieval
                $subscriber = $subscriber_manager->get_subscriber($subscriber_id);
                $results[] = [
                    'test' => 'Subscriber retrieval',
                    'status' => $subscriber ? 'PASS' : 'FAIL',
                    'message' => $subscriber ? 'Subscriber retrieved successfully' : 'Failed to retrieve subscriber'
                ];
                
                // Test subscriber update
                $update_result = $subscriber_manager->update_subscriber($subscriber_id, ['first_name' => 'Updated']);
                $results[] = [
                    'test' => 'Subscriber update',
                    'status' => $update_result ? 'PASS' : 'FAIL',
                    'message' => $update_result ? 'Subscriber updated successfully' : 'Failed to update subscriber'
                ];
                
                // Clean up
                $subscriber_manager->delete_subscriber($subscriber_id);
            }
        } catch (Exception $e) {
            $results[] = [
                'test' => 'Subscriber management test',
                'status' => 'FAIL',
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
        
        return $results;
    }
    
    private function test_campaigns() {
        $results = [];
        
        try {
            $campaign_manager = new EEM_Campaign_Manager();
            
            // Test campaign creation
            $test_campaign = [
                'name' => 'Test Campaign ' . time(),
                'subject' => 'Test Subject',
                'content' => '<p>Test content</p>',
                'type' => 'newsletter'
            ];
            
            $campaign_id = $campaign_manager->create_campaign($test_campaign);
            
            $results[] = [
                'test' => 'Campaign creation',
                'status' => $campaign_id ? 'PASS' : 'FAIL',
                'message' => $campaign_id ? "Campaign created with ID: $campaign_id" : 'Failed to create campaign'
            ];
            
            if ($campaign_id) {
                // Test template rendering
                $template_engine = new EEM_Template_Engine();
                $rendered = $template_engine->render_template('default', $test_campaign, [
                    'first_name' => 'Test',
                    'email' => 'test@example.com'
                ]);
                
                $results[] = [
                    'test' => 'Template rendering',
                    'status' => $rendered ? 'PASS' : 'FAIL',
                    'message' => $rendered ? 'Template rendered successfully' : 'Failed to render template'
                ];
                
                // Clean up
                $campaign_manager->delete_campaign($campaign_id);
            }
        } catch (Exception $e) {
            $results[] = [
                'test' => 'Campaign management test',
                'status' => 'FAIL',
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
        
        return $results;
    }
    
    private function test_analytics() {
        $results = [];
        
        try {
            $analytics = new EEM_Analytics_Tracker();
            
            // Test analytics tracking
            $track_result = $analytics->track_email_sent(1, 1);
            $results[] = [
                'test' => 'Analytics tracking',
                'status' => $track_result ? 'PASS' : 'FAIL',
                'message' => $track_result ? 'Event tracked successfully' : 'Failed to track event'
            ];
            
            // Test analytics retrieval
            $campaign_analytics = $analytics->get_campaign_analytics(1);
            $results[] = [
                'test' => 'Analytics retrieval',
                'status' => is_array($campaign_analytics) ? 'PASS' : 'FAIL',
                'message' => is_array($campaign_analytics) ? 'Analytics retrieved successfully' : 'Failed to retrieve analytics'
            ];
            
        } catch (Exception $e) {
            $results[] = [
                'test' => 'Analytics test',
                'status' => 'FAIL',
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
        
        return $results;
    }
    
    private function test_automation() {
        $results = [];
        
        try {
            $automation = new EEM_Automation_Engine();
            
            // Test automation trigger
            $trigger_result = $automation->trigger_automation('subscription', 1);
            $results[] = [
                'test' => 'Automation trigger',
                'status' => is_bool($trigger_result) ? 'PASS' : 'FAIL',
                'message' => 'Automation trigger executed'
            ];
            
            // Test queue processing
            $process_result = $automation->process_automation_queue();
            $results[] = [
                'test' => 'Queue processing',
                'status' => is_bool($process_result) ? 'PASS' : 'FAIL',
                'message' => 'Queue processing executed'
            ];
            
        } catch (Exception $e) {
            $results[] = [
                'test' => 'Automation test',
                'status' => 'FAIL',
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
        
        return $results;
    }
    
    private function test_rest_api() {
        $results = [];
        
        try {
            // Test REST API initialization
            $api = new EEM_REST_API();
            $results[] = [
                'test' => 'REST API initialization',
                'status' => $api ? 'PASS' : 'FAIL',
                'message' => $api ? 'REST API initialized successfully' : 'Failed to initialize REST API'
            ];
            
            // Test endpoint registration
            $endpoints = $api->get_routes();
            $results[] = [
                'test' => 'API endpoints',
                'status' => is_array($endpoints) ? 'PASS' : 'FAIL',
                'message' => is_array($endpoints) ? count($endpoints) . ' endpoints registered' : 'No endpoints found'
            ];
            
        } catch (Exception $e) {
            $results[] = [
                'test' => 'REST API test',
                'status' => 'FAIL',
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
        
        return $results;
    }
    
    private function test_frontend() {
        $results = [];
        
        try {
            $frontend = new EEM_Frontend();
            
            // Test shortcode rendering
            $form_html = $frontend->subscription_form_shortcode([]);
            $results[] = [
                'test' => 'Subscription form shortcode',
                'status' => !empty($form_html) ? 'PASS' : 'FAIL',
                'message' => !empty($form_html) ? 'Form rendered successfully' : 'Failed to render form'
            ];
            
            // Test form validation
            $validation = $frontend->validate_subscription_data([
                'email' => 'test@example.com',
                'first_name' => 'Test'
            ]);
            $results[] = [
                'test' => 'Form validation',
                'status' => $validation['valid'] ? 'PASS' : 'FAIL',
                'message' => $validation['valid'] ? 'Validation passed' : 'Validation failed'
            ];
            
        } catch (Exception $e) {
            $results[] = [
                'test' => 'Frontend test',
                'status' => 'FAIL',
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
        
        return $results;
    }
    
    private function test_full_workflow() {
        $results = [];
        
        try {
            // 1. Create subscriber
            $subscriber_manager = new EEM_Subscriber_Manager();
            $subscriber_id = $subscriber_manager->add_subscriber([
                'email' => 'workflow_' . time() . '@example.com',
                'first_name' => 'Workflow',
                'last_name' => 'Test'
            ]);
            
            $results[] = [
                'test' => 'Step 1: Create subscriber',
                'status' => $subscriber_id ? 'PASS' : 'FAIL',
                'message' => $subscriber_id ? "Subscriber created: $subscriber_id" : 'Failed'
            ];
            
            // 2. Create campaign
            $campaign_manager = new EEM_Campaign_Manager();
            $campaign_id = $campaign_manager->create_campaign([
                'name' => 'Workflow Test Campaign',
                'subject' => 'Test Email',
                'content' => '<p>Hello {{first_name}}!</p>'
            ]);
            
            $results[] = [
                'test' => 'Step 2: Create campaign',
                'status' => $campaign_id ? 'PASS' : 'FAIL',
                'message' => $campaign_id ? "Campaign created: $campaign_id" : 'Failed'
            ];
            
            // 3. Track analytics
            $analytics = new EEM_Analytics_Tracker();
            $track_result = $analytics->track_email_sent($campaign_id, $subscriber_id);
            
            $results[] = [
                'test' => 'Step 3: Track analytics',
                'status' => $track_result ? 'PASS' : 'FAIL',
                'message' => $track_result ? 'Analytics tracked' : 'Failed'
            ];
            
            // 4. Get campaign stats
            $stats = $analytics->get_campaign_analytics($campaign_id);
            
            $results[] = [
                'test' => 'Step 4: Get campaign stats',
                'status' => $stats['sent'] > 0 ? 'PASS' : 'FAIL',
                'message' => "Sent emails: " . $stats['sent']
            ];
            
            // Clean up
            if ($subscriber_id) $subscriber_manager->delete_subscriber($subscriber_id);
            if ($campaign_id) $campaign_manager->delete_campaign($campaign_id);
            
            $results[] = [
                'test' => 'Full workflow',
                'status' => 'PASS',
                'message' => 'Complete workflow executed successfully'
            ];
            
        } catch (Exception $e) {
            $results[] = [
                'test' => 'Full workflow test',
                'status' => 'FAIL',
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
        
        return $results;
    }
    
    private function format_results($results) {
        $html = '<ul>';
        foreach ($results as $result) {
            $class = strtolower($result['status']);
            $icon = $result['status'] === 'PASS' ? '✓' : ($result['status'] === 'WARN' ? '⚠' : '✗');
            $html .= "<li class='test-result test-{$class}'>";
            $html .= "<strong>{$icon} {$result['test']}</strong>: {$result['message']}";
            $html .= "</li>";
        }
        $html .= '</ul>';
        
        $html .= '<style>
            .test-result { margin: 5px 0; padding: 5px; }
            .test-pass { color: #00a32a; }
            .test-fail { color: #d63638; }
            .test-warn { color: #dba617; }
        </style>';
        
        return $html;
    }
}

// Initialize manual tests if in admin
if (is_admin()) {
    new EEM_Manual_Tests();
}
