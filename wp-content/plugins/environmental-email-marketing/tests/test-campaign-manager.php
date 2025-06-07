<?php
/**
 * Tests for EEM_Campaign_Manager class
 */

class Test_EEM_Campaign_Manager extends EEM_Test_Case {
    
    public function test_create_campaign() {
        $campaign_data = [
            'name' => 'Test Campaign Creation',
            'subject' => 'Test Subject Line',
            'content' => '<p>Test email content</p>',
            'type' => 'newsletter'
        ];
        
        $campaign_id = $this->campaign_manager->create_campaign($campaign_data);
        
        $this->assertIsInt($campaign_id);
        $this->assertGreaterThan(0, $campaign_id);
        
        // Verify campaign exists
        $this->assert_database_has_record('eem_campaigns', [
            'name' => 'Test Campaign Creation',
            'subject' => 'Test Subject Line'
        ]);
    }
    
    public function test_get_campaign() {
        $test_id = $this->create_test_campaign([
            'name' => 'Get Test Campaign',
            'subject' => 'Get Test Subject'
        ]);
        
        $campaign = $this->campaign_manager->get_campaign($test_id);
        
        $this->assertIsArray($campaign);
        $this->assertEquals('Get Test Campaign', $campaign['name']);
        $this->assertEquals('Get Test Subject', $campaign['subject']);
        
        $expected_keys = [
            'id', 'name', 'subject', 'content', 'type', 'status',
            'environmental_theme', 'created_at', 'updated_at'
        ];
        $this->assert_array_has_keys($campaign, $expected_keys);
    }
    
    public function test_update_campaign() {
        $test_id = $this->create_test_campaign(['name' => 'Original Name']);
        
        $update_data = [
            'name' => 'Updated Campaign Name',
            'subject' => 'Updated Subject',
            'environmental_theme' => 'ocean_conservation'
        ];
        
        $result = $this->campaign_manager->update_campaign($test_id, $update_data);
        $this->assertTrue($result);
        
        // Verify update
        $updated_campaign = $this->campaign_manager->get_campaign($test_id);
        $this->assertEquals('Updated Campaign Name', $updated_campaign['name']);
        $this->assertEquals('Updated Subject', $updated_campaign['subject']);
        $this->assertEquals('ocean_conservation', $updated_campaign['environmental_theme']);
    }
    
    public function test_delete_campaign() {
        $test_id = $this->create_test_campaign(['name' => 'Delete Test']);
        
        $result = $this->campaign_manager->delete_campaign($test_id);
        $this->assertTrue($result);
        
        // Verify deletion
        $deleted_campaign = $this->campaign_manager->get_campaign($test_id);
        $this->assertFalse($deleted_campaign);
    }
    
    public function test_duplicate_campaign() {
        $original_id = $this->create_test_campaign([
            'name' => 'Original Campaign',
            'subject' => 'Original Subject',
            'content' => '<p>Original content</p>'
        ]);
        
        $duplicate_id = $this->campaign_manager->duplicate_campaign($original_id);
        
        $this->assertIsInt($duplicate_id);
        $this->assertNotEquals($original_id, $duplicate_id);
        
        // Verify duplicate
        $duplicate = $this->campaign_manager->get_campaign($duplicate_id);
        $this->assertEquals('Original Campaign (Copy)', $duplicate['name']);
        $this->assertEquals('Original Subject', $duplicate['subject']);
        $this->assertEquals('<p>Original content</p>', $duplicate['content']);
    }
    
    public function test_schedule_campaign() {
        $test_id = $this->create_test_campaign(['status' => 'draft']);
        $send_time = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $result = $this->campaign_manager->schedule_campaign($test_id, $send_time);
        $this->assertTrue($result);
        
        // Verify schedule
        $campaign = $this->campaign_manager->get_campaign($test_id);
        $this->assertEquals('scheduled', $campaign['status']);
        $this->assertEquals($send_time, $campaign['send_at']);
    }
    
    public function test_send_campaign() {
        $campaign_id = $this->create_test_campaign(['status' => 'draft']);
        $list_id = $this->create_test_list('Campaign Test List');
        
        // Add subscribers to list
        $subscriber1 = $this->create_test_subscriber('send1@example.com');
        $subscriber2 = $this->create_test_subscriber('send2@example.com');
        $this->subscriber_manager->subscribe_to_list($subscriber1, $list_id);
        $this->subscriber_manager->subscribe_to_list($subscriber2, $list_id);
        
        // Send campaign
        $result = $this->campaign_manager->send_campaign($campaign_id, [$list_id]);
        $this->assertTrue($result);
        
        // Verify campaign status
        $campaign = $this->campaign_manager->get_campaign($campaign_id);
        $this->assertEquals('sending', $campaign['status']);
    }
    
    public function test_pause_campaign() {
        $test_id = $this->create_test_campaign(['status' => 'sending']);
        
        $result = $this->campaign_manager->pause_campaign($test_id);
        $this->assertTrue($result);
        
        // Verify pause
        $campaign = $this->campaign_manager->get_campaign($test_id);
        $this->assertEquals('paused', $campaign['status']);
    }
    
    public function test_resume_campaign() {
        $test_id = $this->create_test_campaign(['status' => 'paused']);
        
        $result = $this->campaign_manager->resume_campaign($test_id);
        $this->assertTrue($result);
        
        // Verify resume
        $campaign = $this->campaign_manager->get_campaign($test_id);
        $this->assertEquals('sending', $campaign['status']);
    }
    
    public function test_get_campaign_stats() {
        $campaign_id = $this->create_test_campaign(['status' => 'sent']);
        
        // Add some analytics data
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'eem_analytics',
            [
                'event_type' => 'email_sent',
                'campaign_id' => $campaign_id,
                'subscriber_id' => 1,
                'created_at' => current_time('mysql')
            ]
        );
        $wpdb->insert(
            $wpdb->prefix . 'eem_analytics',
            [
                'event_type' => 'email_open',
                'campaign_id' => $campaign_id,
                'subscriber_id' => 1,
                'created_at' => current_time('mysql')
            ]
        );
        
        $stats = $this->campaign_manager->get_campaign_stats($campaign_id);
        
        $this->assertIsArray($stats);
        $expected_keys = [
            'sent', 'delivered', 'opens', 'unique_opens', 'clicks',
            'unique_clicks', 'bounces', 'unsubscribes', 'open_rate',
            'click_rate', 'bounce_rate', 'unsubscribe_rate'
        ];
        $this->assert_array_has_keys($stats, $expected_keys);
        
        $this->assertEquals(1, $stats['sent']);
        $this->assertEquals(1, $stats['opens']);
    }
    
    public function test_get_campaigns() {
        // Create test campaigns
        $this->create_test_campaign(['name' => 'Campaign 1', 'type' => 'newsletter']);
        $this->create_test_campaign(['name' => 'Campaign 2', 'type' => 'promotional']);
        $this->create_test_campaign(['name' => 'Campaign 3', 'type' => 'newsletter']);
        
        $campaigns = $this->campaign_manager->get_campaigns();
        $this->assertIsArray($campaigns);
        $this->assertGreaterThanOrEqual(3, count($campaigns));
        
        // Test filtering by type
        $newsletters = $this->campaign_manager->get_campaigns(['type' => 'newsletter']);
        $this->assertCount(2, $newsletters);
        
        // Test pagination
        $paginated = $this->campaign_manager->get_campaigns(['limit' => 2]);
        $this->assertCount(2, $paginated);
    }
    
    public function test_personalize_content() {
        $subscriber = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'environmental_score' => 85
        ];
        
        $content = 'Hello {{first_name}}, your eco score is {{environmental_score}}!';
        
        $personalized = $this->campaign_manager->personalize_content($content, $subscriber);
        
        $this->assertEquals('Hello John, your eco score is 85!', $personalized);
    }
    
    public function test_calculate_environmental_impact() {
        $campaign_id = $this->create_test_campaign();
        
        // Mock some sent emails
        global $wpdb;
        for ($i = 0; $i < 100; $i++) {
            $wpdb->insert(
                $wpdb->prefix . 'eem_analytics',
                [
                    'event_type' => 'email_sent',
                    'campaign_id' => $campaign_id,
                    'subscriber_id' => $i + 1,
                    'created_at' => current_time('mysql')
                ]
            );
        }
        
        $impact = $this->campaign_manager->calculate_environmental_impact($campaign_id);
        
        $this->assertIsArray($impact);
        $this->assertArrayHasKey('carbon_footprint', $impact);
        $this->assertArrayHasKey('energy_consumption', $impact);
        $this->assertArrayHasKey('equivalent_trees', $impact);
        
        $this->assertGreaterThan(0, $impact['carbon_footprint']);
    }
    
    public function test_create_ab_test() {
        $campaign_a_id = $this->create_test_campaign(['name' => 'Campaign A', 'subject' => 'Subject A']);
        $campaign_b_id = $this->create_test_campaign(['name' => 'Campaign B', 'subject' => 'Subject B']);
        
        $ab_test_data = [
            'name' => 'Subject Line Test',
            'campaign_a_id' => $campaign_a_id,
            'campaign_b_id' => $campaign_b_id,
            'test_type' => 'subject',
            'traffic_split' => 50,
            'winning_metric' => 'open_rate'
        ];
        
        $ab_test_id = $this->campaign_manager->create_ab_test($ab_test_data);
        
        $this->assertIsInt($ab_test_id);
        $this->assertGreaterThan(0, $ab_test_id);
        
        // Verify A/B test creation
        $this->assert_database_has_record('eem_ab_tests', [
            'name' => 'Subject Line Test',
            'test_type' => 'subject'
        ]);
    }
    
    public function test_get_campaign_recipients() {
        $campaign_id = $this->create_test_campaign();
        $list_id = $this->create_test_list('Recipients Test');
        
        // Add subscribers
        $subscriber1 = $this->create_test_subscriber('recipient1@example.com');
        $subscriber2 = $this->create_test_subscriber('recipient2@example.com');
        $this->subscriber_manager->subscribe_to_list($subscriber1, $list_id);
        $this->subscriber_manager->subscribe_to_list($subscriber2, $list_id);
        
        $recipients = $this->campaign_manager->get_campaign_recipients($campaign_id, [$list_id]);
        
        $this->assertIsArray($recipients);
        $this->assertCount(2, $recipients);
        
        $emails = array_column($recipients, 'email');
        $this->assertContains('recipient1@example.com', $emails);
        $this->assertContains('recipient2@example.com', $emails);
    }
    
    public function test_validate_campaign_data() {
        $valid_data = [
            'name' => 'Valid Campaign',
            'subject' => 'Valid Subject',
            'content' => '<p>Valid content</p>',
            'type' => 'newsletter'
        ];
        
        $validation_result = $this->campaign_manager->validate_campaign_data($valid_data);
        $this->assertTrue($validation_result['valid']);
        $this->assertEmpty($validation_result['errors']);
        
        // Test invalid data
        $invalid_data = [
            'name' => '', // Empty name
            'subject' => '', // Empty subject
            'content' => '', // Empty content
            'type' => 'invalid_type'
        ];
        
        $invalid_result = $this->campaign_manager->validate_campaign_data($invalid_data);
        $this->assertFalse($invalid_result['valid']);
        $this->assertNotEmpty($invalid_result['errors']);
        $this->assertContains('Name is required', $invalid_result['errors']);
    }
}
