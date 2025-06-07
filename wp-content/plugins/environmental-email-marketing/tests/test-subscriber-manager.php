<?php
/**
 * Tests for EEM_Subscriber_Manager class
 */

class Test_EEM_Subscriber_Manager extends EEM_Test_Case {
    
    public function test_add_subscriber() {
        $subscriber_data = [
            'email' => 'newuser@example.com',
            'first_name' => 'New',
            'last_name' => 'User',
            'environmental_score' => 85
        ];
        
        $subscriber_id = $this->subscriber_manager->add_subscriber($subscriber_data);
        
        $this->assertIsInt($subscriber_id);
        $this->assertGreaterThan(0, $subscriber_id);
        
        // Verify subscriber exists in database
        $this->assert_database_has_record('eem_subscribers', [
            'email' => 'newuser@example.com',
            'first_name' => 'New'
        ]);
    }
    
    public function test_add_duplicate_subscriber() {
        $subscriber_data = [
            'email' => 'duplicate@example.com',
            'first_name' => 'Duplicate'
        ];
        
        // Add first subscriber
        $first_id = $this->subscriber_manager->add_subscriber($subscriber_data);
        $this->assertGreaterThan(0, $first_id);
        
        // Try to add duplicate
        $duplicate_result = $this->subscriber_manager->add_subscriber($subscriber_data);
        $this->assertFalse($duplicate_result);
    }
    
    public function test_get_subscriber() {
        $test_id = $this->create_test_subscriber('gettest@example.com');
        
        $subscriber = $this->subscriber_manager->get_subscriber($test_id);
        
        $this->assertIsArray($subscriber);
        $this->assertEquals('gettest@example.com', $subscriber['email']);
        $this->assert_array_has_keys($subscriber, [
            'id', 'email', 'first_name', 'status', 'environmental_score'
        ]);
    }
    
    public function test_get_subscriber_by_email() {
        $this->create_test_subscriber('emailtest@example.com');
        
        $subscriber = $this->subscriber_manager->get_subscriber_by_email('emailtest@example.com');
        
        $this->assertIsArray($subscriber);
        $this->assertEquals('emailtest@example.com', $subscriber['email']);
    }
    
    public function test_update_subscriber() {
        $test_id = $this->create_test_subscriber('updatetest@example.com');
        
        $update_data = [
            'first_name' => 'Updated',
            'environmental_score' => 90
        ];
        
        $result = $this->subscriber_manager->update_subscriber($test_id, $update_data);
        $this->assertTrue($result);
        
        // Verify update
        $updated_subscriber = $this->subscriber_manager->get_subscriber($test_id);
        $this->assertEquals('Updated', $updated_subscriber['first_name']);
        $this->assertEquals(90, $updated_subscriber['environmental_score']);
    }
    
    public function test_delete_subscriber() {
        $test_id = $this->create_test_subscriber('deletetest@example.com');
        
        $result = $this->subscriber_manager->delete_subscriber($test_id);
        $this->assertTrue($result);
        
        // Verify deletion
        $deleted_subscriber = $this->subscriber_manager->get_subscriber($test_id);
        $this->assertFalse($deleted_subscriber);
    }
    
    public function test_subscribe_to_list() {
        $subscriber_id = $this->create_test_subscriber('listtest@example.com');
        $list_id = $this->create_test_list('Test List');
        
        $result = $this->subscriber_manager->subscribe_to_list($subscriber_id, $list_id);
        $this->assertTrue($result);
        
        // Verify subscription
        $lists = $this->subscriber_manager->get_subscriber_lists($subscriber_id);
        $this->assertContains($list_id, array_column($lists, 'id'));
    }
    
    public function test_unsubscribe_from_list() {
        $subscriber_id = $this->create_test_subscriber('unsubtest@example.com');
        $list_id = $this->create_test_list('Unsub Test List');
        
        // Subscribe first
        $this->subscriber_manager->subscribe_to_list($subscriber_id, $list_id);
        
        // Then unsubscribe
        $result = $this->subscriber_manager->unsubscribe_from_list($subscriber_id, $list_id);
        $this->assertTrue($result);
        
        // Verify unsubscription
        $lists = $this->subscriber_manager->get_subscriber_lists($subscriber_id);
        $this->assertNotContains($list_id, array_column($lists, 'id'));
    }
    
    public function test_update_environmental_score() {
        $subscriber_id = $this->create_test_subscriber('scoretest@example.com', ['environmental_score' => 50]);
        
        $result = $this->subscriber_manager->update_environmental_score($subscriber_id, 15);
        $this->assertTrue($result);
        
        // Verify score update
        $subscriber = $this->subscriber_manager->get_subscriber($subscriber_id);
        $this->assertEquals(65, $subscriber['environmental_score']);
    }
    
    public function test_get_subscribers_by_segment() {
        // Create test subscribers with different scores
        $this->create_test_subscriber('high@example.com', ['environmental_score' => 90]);
        $this->create_test_subscriber('medium@example.com', ['environmental_score' => 60]);
        $this->create_test_subscriber('low@example.com', ['environmental_score' => 30]);
        
        // Test high engagement segment
        $high_subscribers = $this->subscriber_manager->get_subscribers_by_segment([
            'environmental_score_min' => 80
        ]);
        
        $this->assertCount(1, $high_subscribers);
        $this->assertEquals('high@example.com', $high_subscribers[0]['email']);
    }
    
    public function test_confirm_subscription() {
        $subscriber_id = $this->create_test_subscriber('confirm@example.com', ['status' => 'pending']);
        
        $result = $this->subscriber_manager->confirm_subscription($subscriber_id);
        $this->assertTrue($result);
        
        // Verify confirmation
        $subscriber = $this->subscriber_manager->get_subscriber($subscriber_id);
        $this->assertEquals('active', $subscriber['status']);
        $this->assertNotNull($subscriber['confirmed_at']);
    }
    
    public function test_unsubscribe_subscriber() {
        $subscriber_id = $this->create_test_subscriber('globalunsub@example.com');
        
        $result = $this->subscriber_manager->unsubscribe_subscriber($subscriber_id);
        $this->assertTrue($result);
        
        // Verify unsubscription
        $subscriber = $this->subscriber_manager->get_subscriber($subscriber_id);
        $this->assertEquals('unsubscribed', $subscriber['status']);
    }
    
    public function test_get_subscriber_stats() {
        // Create test subscribers with different statuses
        $this->create_test_subscriber('active1@example.com', ['status' => 'active']);
        $this->create_test_subscriber('active2@example.com', ['status' => 'active']);
        $this->create_test_subscriber('pending1@example.com', ['status' => 'pending']);
        $this->create_test_subscriber('unsubscribed1@example.com', ['status' => 'unsubscribed']);
        
        $stats = $this->subscriber_manager->get_subscriber_stats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('active', $stats);
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('unsubscribed', $stats);
        
        $this->assertGreaterThanOrEqual(2, $stats['active']);
        $this->assertGreaterThanOrEqual(1, $stats['pending']);
        $this->assertGreaterThanOrEqual(1, $stats['unsubscribed']);
    }
    
    public function test_export_subscribers() {
        $this->create_test_subscriber('export1@example.com');
        $this->create_test_subscriber('export2@example.com');
        
        $export_data = $this->subscriber_manager->export_subscribers();
        
        $this->assertIsArray($export_data);
        $this->assertGreaterThanOrEqual(2, count($export_data));
        
        // Check CSV headers
        $headers = array_keys($export_data[0]);
        $this->assertContains('email', $headers);
        $this->assertContains('first_name', $headers);
        $this->assertContains('status', $headers);
    }
    
    public function test_import_subscribers() {
        $import_data = [
            [
                'email' => 'import1@example.com',
                'first_name' => 'Import1',
                'last_name' => 'Test'
            ],
            [
                'email' => 'import2@example.com',
                'first_name' => 'Import2',
                'last_name' => 'Test'
            ]
        ];
        
        $result = $this->subscriber_manager->import_subscribers($import_data);
        
        $this->assertIsArray($result);
        $this->assertEquals(2, $result['imported']);
        $this->assertEquals(0, $result['errors']);
        
        // Verify imports
        $subscriber1 = $this->subscriber_manager->get_subscriber_by_email('import1@example.com');
        $this->assertNotFalse($subscriber1);
        $this->assertEquals('Import1', $subscriber1['first_name']);
    }
    
    public function test_validate_email() {
        $this->assertTrue($this->subscriber_manager->validate_email('valid@example.com'));
        $this->assertFalse($this->subscriber_manager->validate_email('invalid-email'));
        $this->assertFalse($this->subscriber_manager->validate_email(''));
    }
    
    public function test_generate_unsubscribe_token() {
        $subscriber_id = $this->create_test_subscriber('token@example.com');
        
        $token = $this->subscriber_manager->generate_unsubscribe_token($subscriber_id);
        
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        $this->assertGreaterThan(20, strlen($token));
    }
    
    public function test_verify_unsubscribe_token() {
        $subscriber_id = $this->create_test_subscriber('verify@example.com');
        $token = $this->subscriber_manager->generate_unsubscribe_token($subscriber_id);
        
        $verified_id = $this->subscriber_manager->verify_unsubscribe_token($token);
        $this->assertEquals($subscriber_id, $verified_id);
        
        // Test invalid token
        $invalid_result = $this->subscriber_manager->verify_unsubscribe_token('invalid_token');
        $this->assertFalse($invalid_result);
    }
}
