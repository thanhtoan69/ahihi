<?php
/**
 * Integration Tests for Environmental Email Marketing Plugin
 * Tests the integration between different components
 */

class Test_EEM_Integration extends EEM_Test_Case {
    
    public function test_full_subscription_workflow() {
        // 1. Create a subscriber through frontend
        $subscription_data = [
            'email' => 'workflow@example.com',
            'first_name' => 'Workflow',
            'last_name' => 'Test',
            'source' => 'website_form'
        ];
        
        $subscriber_id = $this->subscriber_manager->add_subscriber($subscription_data);
        $this->assertGreaterThan(0, $subscriber_id);
        
        // 2. Create a list and subscribe user
        $list_id = $this->create_test_list('Integration Test List');
        $subscribe_result = $this->subscriber_manager->subscribe_to_list($subscriber_id, $list_id);
        $this->assertTrue($subscribe_result);
        
        // 3. Create and send a campaign to the list
        $campaign_id = $this->create_test_campaign([
            'name' => 'Integration Test Campaign',
            'subject' => 'Welcome to our environmental mission!',
            'content' => '<p>Hello {{first_name}}, thank you for joining us!</p>'
        ]);
        
        $campaign_sent = $this->campaign_manager->send_campaign($campaign_id, [$list_id]);
        $this->assertTrue($campaign_sent);
        
        // 4. Verify analytics tracking
        $this->analytics_tracker->track_email_sent($campaign_id, $subscriber_id);
        $this->analytics_tracker->track_email_open($campaign_id, $subscriber_id);
        
        // 5. Check campaign analytics
        $analytics = $this->analytics_tracker->get_campaign_analytics($campaign_id);
        $this->assertEquals(1, $analytics['sent']);
        $this->assertEquals(1, $analytics['opens']);
        
        // 6. Update environmental score based on engagement
        $score_updated = $this->subscriber_manager->update_environmental_score($subscriber_id, 10);
        $this->assertTrue($score_updated);
        
        // 7. Verify subscriber data
        $subscriber = $this->subscriber_manager->get_subscriber($subscriber_id);
        $this->assertEquals(85, $subscriber['environmental_score']); // 75 + 10
    }
    
    public function test_automation_workflow() {
        // 1. Create subscriber
        $subscriber_id = $this->create_test_subscriber('automation@example.com');
        
        // 2. Create automation sequence
        global $wpdb;
        $automation_id = $wpdb->insert(
            $wpdb->prefix . 'eem_automations',
            [
                'name' => 'Welcome Series',
                'trigger_type' => 'subscription',
                'status' => 'active',
                'conditions' => wp_json_encode(['source' => 'website']),
                'actions' => wp_json_encode([
                    [
                        'type' => 'send_email',
                        'delay' => 0,
                        'template_id' => 1,
                        'subject' => 'Welcome to our community!'
                    ],
                    [
                        'type' => 'send_email', 
                        'delay' => 86400, // 1 day
                        'template_id' => 2,
                        'subject' => 'Your environmental impact guide'
                    ]
                ]),
                'created_at' => current_time('mysql')
            ]
        );
        
        // 3. Trigger automation
        $triggered = $this->automation_engine->trigger_automation('subscription', $subscriber_id);
        $this->assertTrue($triggered);
        
        // 4. Process automation queue
        $processed = $this->automation_engine->process_automation_queue();
        $this->assertTrue($processed);
        
        // 5. Verify automation was queued
        $queue_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}eem_automation_queue 
            WHERE subscriber_id = %d AND automation_id = %d
        ", $subscriber_id, $automation_id));
        
        $this->assertGreaterThan(0, $queue_count);
    }
    
    public function test_email_provider_integration() {
        // Test with native provider
        $provider = new EEM_Provider_Native();
        
        $email_data = [
            'to' => 'provider@example.com',
            'subject' => 'Provider Test',
            'content' => '<p>Testing email provider integration</p>',
            'from_email' => 'test@example.com',
            'from_name' => 'Test Platform'
        ];
        
        $send_result = $provider->send_email($email_data);
        
        // Native provider should return true for successful queue
        $this->assertTrue($send_result['success']);
        $this->assertArrayHasKey('message_id', $send_result);
    }
    
    public function test_template_rendering_with_personalization() {
        // 1. Create subscriber with environmental data
        $subscriber_id = $this->create_test_subscriber('template@example.com', [
            'first_name' => 'Template',
            'environmental_score' => 95,
            'preferences' => wp_json_encode([
                'newsletter' => true,
                'environmental_tips' => true
            ])
        ]);
        
        // 2. Create campaign with personalization
        $campaign_id = $this->create_test_campaign([
            'content' => '
                <p>Hello {{first_name}}!</p>
                <p>Your eco score is {{environmental_score}}!</p>
                {{#if environmental_score > 90}}
                <p>You are an environmental champion!</p>
                {{/if}}
                <p>Visit {{site_url}} for more tips.</p>
            '
        ]);
        
        // 3. Render template
        $subscriber = $this->subscriber_manager->get_subscriber($subscriber_id);
        $campaign = $this->campaign_manager->get_campaign($campaign_id);
        
        $rendered = $this->template_engine->render_template('default', $campaign, $subscriber);
        
        // 4. Verify personalization
        $this->assertStringContainsString('Hello Template!', $rendered);
        $this->assertStringContainsString('score is 95!', $rendered);
        $this->assertStringContainsString('environmental champion', $rendered);
        $this->assertStringContainsString(get_site_url(), $rendered);
    }
    
    public function test_ab_testing_workflow() {
        // 1. Create two campaign variants
        $campaign_a = $this->create_test_campaign([
            'name' => 'Version A',
            'subject' => 'Join our environmental mission!'
        ]);
        
        $campaign_b = $this->create_test_campaign([
            'name' => 'Version B', 
            'subject' => 'Help save the planet today!'
        ]);
        
        // 2. Create A/B test
        $ab_test_data = [
            'name' => 'Subject Line Test',
            'campaign_a_id' => $campaign_a,
            'campaign_b_id' => $campaign_b,
            'test_type' => 'subject',
            'traffic_split' => 50,
            'winning_metric' => 'open_rate'
        ];
        
        $ab_test_id = $this->campaign_manager->create_ab_test($ab_test_data);
        $this->assertGreaterThan(0, $ab_test_id);
        
        // 3. Create test subscribers
        $subscribers = [];
        for ($i = 0; $i < 10; $i++) {
            $subscribers[] = $this->create_test_subscriber("abtest{$i}@example.com");
        }
        
        // 4. Simulate A/B test sending (would normally be handled by campaign manager)
        $half = count($subscribers) / 2;
        
        // Send Version A to first half
        for ($i = 0; $i < $half; $i++) {
            $this->analytics_tracker->track_email_sent($campaign_a, $subscribers[$i]);
            if ($i % 3 == 0) { // 33% open rate for A
                $this->analytics_tracker->track_email_open($campaign_a, $subscribers[$i]);
            }
        }
        
        // Send Version B to second half  
        for ($i = $half; $i < count($subscribers); $i++) {
            $this->analytics_tracker->track_email_sent($campaign_b, $subscribers[$i]);
            if ($i % 2 == 0) { // 50% open rate for B
                $this->analytics_tracker->track_email_open($campaign_b, $subscribers[$i]);
            }
        }
        
        // 5. Get A/B test results
        $stats_a = $this->analytics_tracker->get_campaign_analytics($campaign_a);
        $stats_b = $this->analytics_tracker->get_campaign_analytics($campaign_b);
        
        $this->assertGreaterThan($stats_a['open_rate'], $stats_b['open_rate']);
    }
    
    public function test_environmental_scoring_integration() {
        $subscriber_id = $this->create_test_subscriber('scoring@example.com', [
            'environmental_score' => 50
        ]);
        
        // Simulate various environmental actions
        $actions = [
            ['action' => 'petition_signed', 'points' => 15],
            ['action' => 'article_shared', 'points' => 5],
            ['action' => 'quiz_completed', 'points' => 10],
            ['action' => 'event_attended', 'points' => 20]
        ];
        
        foreach ($actions as $action) {
            // Track environmental action
            $this->analytics_tracker->track_environmental_action(
                $subscriber_id, 
                $action['action'], 
                $action['points']
            );
            
            // Update subscriber score
            $this->subscriber_manager->update_environmental_score($subscriber_id, $action['points']);
        }
        
        // Verify final score
        $subscriber = $this->subscriber_manager->get_subscriber($subscriber_id);
        $expected_score = 50 + 15 + 5 + 10 + 20; // 100
        $this->assertEquals($expected_score, $subscriber['environmental_score']);
        
        // Test segmentation based on score
        $high_score_subscribers = $this->subscriber_manager->get_subscribers_by_segment([
            'environmental_score_min' => 90
        ]);
        
        $this->assertCount(1, $high_score_subscribers);
        $this->assertEquals($subscriber_id, $high_score_subscribers[0]['id']);
    }
    
    public function test_webhook_integration() {
        // 1. Create webhook
        global $wpdb;
        $webhook_id = $wpdb->insert(
            $wpdb->prefix . 'eem_webhooks',
            [
                'name' => 'Test Webhook',
                'url' => 'https://example.com/webhook',
                'events' => wp_json_encode(['email_open', 'email_click']),
                'status' => 'active',
                'secret' => 'test_secret_key',
                'created_at' => current_time('mysql')
            ]
        );
        
        // 2. Trigger webhook event
        $campaign_id = $this->create_test_campaign();
        $subscriber_id = $this->create_test_subscriber('webhook@example.com');
        
        // Track email open (should trigger webhook)
        $this->analytics_tracker->track_email_open($campaign_id, $subscriber_id);
        
        // 3. Verify webhook queue
        $webhook_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}eem_webhook_queue 
            WHERE webhook_id = %d AND event_type = %s
        ", $webhook_id, 'email_open'));
        
        $this->assertGreaterThan(0, $webhook_count);
    }
    
    public function test_gdpr_compliance_workflow() {
        $subscriber_id = $this->create_test_subscriber('gdpr@example.com');
        
        // 1. Test data export
        $export_data = $this->subscriber_manager->export_subscriber_data($subscriber_id);
        $this->assertIsArray($export_data);
        $this->assertArrayHasKey('subscriber_data', $export_data);
        $this->assertArrayHasKey('analytics_data', $export_data);
        $this->assertArrayHasKey('campaign_data', $export_data);
        
        // 2. Test data anonymization
        $anonymize_result = $this->subscriber_manager->anonymize_subscriber_data($subscriber_id);
        $this->assertTrue($anonymize_result);
        
        // Verify anonymization
        $subscriber = $this->subscriber_manager->get_subscriber($subscriber_id);
        $this->assertStringContainsString('anonymized', $subscriber['email']);
        $this->assertEquals('Anonymous', $subscriber['first_name']);
        
        // 3. Test data deletion
        $delete_result = $this->subscriber_manager->delete_subscriber_data($subscriber_id);
        $this->assertTrue($delete_result);
        
        // Verify deletion
        $deleted_subscriber = $this->subscriber_manager->get_subscriber($subscriber_id);
        $this->assertFalse($deleted_subscriber);
    }
    
    public function test_rest_api_integration() {
        $api = new EEM_REST_API();
        
        // Test subscriber creation via API
        $request_data = [
            'email' => 'api@example.com',
            'first_name' => 'API',
            'last_name' => 'User'
        ];
        
        // Mock WP_REST_Request
        $request = new WP_REST_Request('POST', '/eem/v1/subscribers');
        $request->set_body_params($request_data);
        
        $response = $api->create_subscriber($request);
        
        $this->assertInstanceOf('WP_REST_Response', $response);
        $this->assertEquals(201, $response->get_status());
        
        $response_data = $response->get_data();
        $this->assertArrayHasKey('id', $response_data);
        $this->assertEquals('api@example.com', $response_data['email']);
    }
    
    public function test_performance_with_large_dataset() {
        // Create large dataset
        $start_time = microtime(true);
        
        // Create 1000 subscribers
        $subscriber_ids = [];
        for ($i = 0; $i < 1000; $i++) {
            $subscriber_ids[] = $this->create_test_subscriber("perf{$i}@example.com");
        }
        
        $creation_time = microtime(true) - $start_time;
        $this->assertLessThan(10, $creation_time, "Subscriber creation took too long: {$creation_time}s");
        
        // Test bulk operations
        $start_time = microtime(true);
        
        $campaign_id = $this->create_test_campaign();
        
        // Simulate sending to all subscribers
        foreach ($subscriber_ids as $subscriber_id) {
            $this->analytics_tracker->track_email_sent($campaign_id, $subscriber_id);
        }
        
        $tracking_time = microtime(true) - $start_time;
        $this->assertLessThan(5, $tracking_time, "Analytics tracking took too long: {$tracking_time}s");
        
        // Test analytics aggregation
        $start_time = microtime(true);
        $analytics = $this->analytics_tracker->get_campaign_analytics($campaign_id);
        $analytics_time = microtime(true) - $start_time;
        
        $this->assertLessThan(2, $analytics_time, "Analytics calculation took too long: {$analytics_time}s");
        $this->assertEquals(1000, $analytics['sent']);
    }
}
