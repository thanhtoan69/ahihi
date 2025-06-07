<?php
/**
 * Base test case class for Environmental Email Marketing plugin
 */

class EEM_Test_Case extends WP_UnitTestCase {
    
    protected $database_manager;
    protected $subscriber_manager;
    protected $campaign_manager;
    protected $automation_engine;
    protected $template_engine;
    protected $analytics_tracker;
    
    public function setUp(): void {
        parent::setUp();
        
        // Initialize core components
        $this->database_manager = new EEM_Database_Manager();
        $this->subscriber_manager = new EEM_Subscriber_Manager();
        $this->campaign_manager = new EEM_Campaign_Manager();
        $this->automation_engine = new EEM_Automation_Engine();
        $this->template_engine = new EEM_Template_Engine();
        $this->analytics_tracker = new EEM_Analytics_Tracker();
        
        // Create test database tables
        $this->database_manager->create_tables();
        
        // Set up test options
        $this->setup_test_options();
    }
    
    public function tearDown(): void {
        // Clean up test data
        $this->cleanup_test_data();
        parent::tearDown();
    }
    
    protected function setup_test_options() {
        // General settings
        update_option('eem_general_settings', [
            'from_email' => 'test@example.com',
            'from_name' => 'Test Environmental Platform',
            'reply_to' => 'noreply@example.com',
            'double_optin' => true,
            'track_opens' => true,
            'track_clicks' => true,
            'unsubscribe_page' => 1,
            'subscription_page' => 2
        ]);
        
        // Provider settings
        update_option('eem_provider_settings', [
            'active_provider' => 'native',
            'providers' => [
                'native' => ['enabled' => true],
                'mailchimp' => ['enabled' => false, 'api_key' => ''],
                'sendgrid' => ['enabled' => false, 'api_key' => ''],
                'mailgun' => ['enabled' => false, 'api_key' => '', 'domain' => ''],
                'ses' => ['enabled' => false, 'access_key' => '', 'secret_key' => '', 'region' => 'us-east-1']
            ]
        ]);
        
        // Environmental settings
        update_option('eem_environmental_settings', [
            'carbon_tracking' => true,
            'eco_scoring' => true,
            'sustainability_metrics' => true,
            'green_themes' => true,
            'environmental_tips' => true
        ]);
    }
    
    protected function cleanup_test_data() {
        global $wpdb;
        
        // Clean up test database tables
        $tables = [
            $wpdb->prefix . 'eem_subscribers',
            $wpdb->prefix . 'eem_lists',
            $wpdb->prefix . 'eem_campaigns',
            $wpdb->prefix . 'eem_templates',
            $wpdb->prefix . 'eem_automations',
            $wpdb->prefix . 'eem_analytics',
            $wpdb->prefix . 'eem_ab_tests',
            $wpdb->prefix . 'eem_segments',
            $wpdb->prefix . 'eem_webhooks',
            $wpdb->prefix . 'eem_logs'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("TRUNCATE TABLE $table");
        }
    }
    
    protected function create_test_subscriber($email = 'test@example.com', $data = []) {
        $default_data = [
            'email' => $email,
            'first_name' => 'Test',
            'last_name' => 'User',
            'status' => 'active',
            'environmental_score' => 75,
            'preferences' => wp_json_encode([
                'newsletter' => true,
                'promotions' => false,
                'environmental_tips' => true
            ])
        ];
        
        $subscriber_data = array_merge($default_data, $data);
        return $this->subscriber_manager->add_subscriber($subscriber_data);
    }
    
    protected function create_test_campaign($data = []) {
        $default_data = [
            'name' => 'Test Campaign',
            'subject' => 'Test Subject',
            'content' => '<p>Test email content</p>',
            'type' => 'newsletter',
            'status' => 'draft',
            'environmental_theme' => 'eco_friendly'
        ];
        
        $campaign_data = array_merge($default_data, $data);
        return $this->campaign_manager->create_campaign($campaign_data);
    }
    
    protected function create_test_list($name = 'Test List', $data = []) {
        global $wpdb;
        
        $default_data = [
            'name' => $name,
            'description' => 'Test list description',
            'environmental_focus' => 'general',
            'created_at' => current_time('mysql')
        ];
        
        $list_data = array_merge($default_data, $data);
        
        $wpdb->insert(
            $wpdb->prefix . 'eem_lists',
            $list_data
        );
        
        return $wpdb->insert_id;
    }
    
    protected function assert_email_valid($email) {
        $this->assertTrue(is_email($email), "Email $email is not valid");
    }
    
    protected function assert_array_has_keys($array, $keys) {
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $array, "Array missing key: $key");
        }
    }
    
    protected function assert_database_has_record($table, $conditions) {
        global $wpdb;
        
        $where_clauses = [];
        $values = [];
        
        foreach ($conditions as $column => $value) {
            $where_clauses[] = "$column = %s";
            $values[] = $value;
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        $query = "SELECT COUNT(*) FROM {$wpdb->prefix}$table WHERE $where_sql";
        $count = $wpdb->get_var($wpdb->prepare($query, $values));
        
        $this->assertGreaterThan(0, $count, "Database table $table does not contain expected record");
    }
}
