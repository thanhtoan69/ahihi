<?php
/**
 * Tests for EEM_Analytics_Tracker class
 */

class Test_EEM_Analytics_Tracker extends EEM_Test_Case {
    
    public function test_track_email_sent() {
        $campaign_id = $this->create_test_campaign();
        $subscriber_id = $this->create_test_subscriber('analytics@example.com');
        
        $result = $this->analytics_tracker->track_email_sent($campaign_id, $subscriber_id);
        $this->assertTrue($result);
        
        // Verify tracking record
        $this->assert_database_has_record('eem_analytics', [
            'event_type' => 'email_sent',
            'campaign_id' => $campaign_id,
            'subscriber_id' => $subscriber_id
        ]);
    }
    
    public function test_track_email_open() {
        $campaign_id = $this->create_test_campaign();
        $subscriber_id = $this->create_test_subscriber('open@example.com');
        
        $result = $this->analytics_tracker->track_email_open($campaign_id, $subscriber_id);
        $this->assertTrue($result);
        
        // Verify tracking record
        $this->assert_database_has_record('eem_analytics', [
            'event_type' => 'email_open',
            'campaign_id' => $campaign_id,
            'subscriber_id' => $subscriber_id
        ]);
    }
    
    public function test_track_email_click() {
        $campaign_id = $this->create_test_campaign();
        $subscriber_id = $this->create_test_subscriber('click@example.com');
        $url = 'https://example.com/test';
        
        $result = $this->analytics_tracker->track_email_click($campaign_id, $subscriber_id, $url);
        $this->assertTrue($result);
        
        // Verify tracking record
        global $wpdb;
        $record = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}eem_analytics 
            WHERE event_type = %s AND campaign_id = %d AND subscriber_id = %d
        ", 'email_click', $campaign_id, $subscriber_id));
        
        $this->assertNotNull($record);
        $this->assertEquals($url, $record->url);
    }
    
    public function test_track_unsubscribe() {
        $campaign_id = $this->create_test_campaign();
        $subscriber_id = $this->create_test_subscriber('unsub@example.com');
        
        $result = $this->analytics_tracker->track_unsubscribe($campaign_id, $subscriber_id);
        $this->assertTrue($result);
        
        // Verify tracking record
        $this->assert_database_has_record('eem_analytics', [
            'event_type' => 'unsubscribe',
            'campaign_id' => $campaign_id,
            'subscriber_id' => $subscriber_id
        ]);
    }
    
    public function test_track_bounce() {
        $campaign_id = $this->create_test_campaign();
        $subscriber_id = $this->create_test_subscriber('bounce@example.com');
        $bounce_type = 'hard';
        $reason = 'Invalid email address';
        
        $result = $this->analytics_tracker->track_bounce($campaign_id, $subscriber_id, $bounce_type, $reason);
        $this->assertTrue($result);
        
        // Verify tracking record
        global $wpdb;
        $record = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}eem_analytics 
            WHERE event_type = %s AND campaign_id = %d AND subscriber_id = %d
        ", 'bounce', $campaign_id, $subscriber_id));
        
        $this->assertNotNull($record);
        $metadata = json_decode($record->metadata, true);
        $this->assertEquals($bounce_type, $metadata['bounce_type']);
        $this->assertEquals($reason, $metadata['reason']);
    }
    
    public function test_track_environmental_action() {
        $subscriber_id = $this->create_test_subscriber('envaction@example.com');
        $action = 'petition_signed';
        $value = 15; // Points earned
        
        $result = $this->analytics_tracker->track_environmental_action($subscriber_id, $action, $value);
        $this->assertTrue($result);
        
        // Verify tracking record
        global $wpdb;
        $record = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}eem_analytics 
            WHERE event_type = %s AND subscriber_id = %d
        ", 'environmental_action', $subscriber_id));
        
        $this->assertNotNull($record);
        $metadata = json_decode($record->metadata, true);
        $this->assertEquals($action, $metadata['action']);
        $this->assertEquals($value, $metadata['value']);
    }
    
    public function test_get_campaign_analytics() {
        $campaign_id = $this->create_test_campaign();
        $subscriber1 = $this->create_test_subscriber('user1@example.com');
        $subscriber2 = $this->create_test_subscriber('user2@example.com');
        
        // Create test analytics data
        $this->analytics_tracker->track_email_sent($campaign_id, $subscriber1);
        $this->analytics_tracker->track_email_sent($campaign_id, $subscriber2);
        $this->analytics_tracker->track_email_open($campaign_id, $subscriber1);
        $this->analytics_tracker->track_email_click($campaign_id, $subscriber1, 'https://example.com');
        
        $analytics = $this->analytics_tracker->get_campaign_analytics($campaign_id);
        
        $this->assertIsArray($analytics);
        $expected_keys = [
            'sent', 'delivered', 'opens', 'unique_opens', 'clicks',
            'unique_clicks', 'bounces', 'unsubscribes', 'open_rate',
            'click_rate', 'bounce_rate', 'unsubscribe_rate'
        ];
        $this->assert_array_has_keys($analytics, $expected_keys);
        
        $this->assertEquals(2, $analytics['sent']);
        $this->assertEquals(1, $analytics['opens']);
        $this->assertEquals(1, $analytics['clicks']);
        $this->assertEquals(50.0, $analytics['open_rate']); // 1/2 * 100
    }
    
    public function test_get_subscriber_analytics() {
        $subscriber_id = $this->create_test_subscriber('subanalytics@example.com');
        $campaign1 = $this->create_test_campaign(['name' => 'Campaign 1']);
        $campaign2 = $this->create_test_campaign(['name' => 'Campaign 2']);
        
        // Create test data
        $this->analytics_tracker->track_email_sent($campaign1, $subscriber_id);
        $this->analytics_tracker->track_email_open($campaign1, $subscriber_id);
        $this->analytics_tracker->track_email_sent($campaign2, $subscriber_id);
        $this->analytics_tracker->track_environmental_action($subscriber_id, 'quiz_completed', 10);
        
        $analytics = $this->analytics_tracker->get_subscriber_analytics($subscriber_id);
        
        $this->assertIsArray($analytics);
        $expected_keys = [
            'emails_received', 'emails_opened', 'emails_clicked',
            'environmental_actions', 'engagement_score', 'last_activity'
        ];
        $this->assert_array_has_keys($analytics, $expected_keys);
        
        $this->assertEquals(2, $analytics['emails_received']);
        $this->assertEquals(1, $analytics['emails_opened']);
        $this->assertEquals(1, $analytics['environmental_actions']);
    }
    
    public function test_get_environmental_impact() {
        $campaign_id = $this->create_test_campaign();
        
        // Create test data for 1000 sent emails
        global $wpdb;
        for ($i = 0; $i < 1000; $i++) {
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
        
        $impact = $this->analytics_tracker->get_environmental_impact($campaign_id);
        
        $this->assertIsArray($impact);
        $expected_keys = [
            'carbon_footprint', 'energy_consumption', 'water_usage',
            'equivalent_trees', 'equivalent_km_driven'
        ];
        $this->assert_array_has_keys($impact, $expected_keys);
        
        $this->assertGreaterThan(0, $impact['carbon_footprint']);
        $this->assertGreaterThan(0, $impact['energy_consumption']);
    }
    
    public function test_get_engagement_trends() {
        $days = 7;
        $campaign_id = $this->create_test_campaign();
        
        // Create test data for the past week
        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d H:i:s', strtotime("-$i days"));
            global $wpdb;
            
            // Add varying amounts of activity per day
            for ($j = 0; $j < ($i + 1) * 10; $j++) {
                $wpdb->insert(
                    $wpdb->prefix . 'eem_analytics',
                    [
                        'event_type' => 'email_open',
                        'campaign_id' => $campaign_id,
                        'subscriber_id' => $j + 1,
                        'created_at' => $date
                    ]
                );
            }
        }
        
        $trends = $this->analytics_tracker->get_engagement_trends($days);
        
        $this->assertIsArray($trends);
        $this->assertCount($days, $trends);
        
        foreach ($trends as $day_data) {
            $this->assertArrayHasKey('date', $day_data);
            $this->assertArrayHasKey('opens', $day_data);
            $this->assertArrayHasKey('clicks', $day_data);
            $this->assertArrayHasKey('unsubscribes', $day_data);
        }
    }
    
    public function test_get_top_performing_campaigns() {
        // Create campaigns with different performance
        $campaign1 = $this->create_test_campaign(['name' => 'High Performer']);
        $campaign2 = $this->create_test_campaign(['name' => 'Medium Performer']);
        $campaign3 = $this->create_test_campaign(['name' => 'Low Performer']);
        
        // Add performance data
        global $wpdb;
        
        // Campaign 1: High performance
        for ($i = 0; $i < 100; $i++) {
            $wpdb->insert($wpdb->prefix . 'eem_analytics', [
                'event_type' => 'email_sent',
                'campaign_id' => $campaign1,
                'subscriber_id' => $i + 1,
                'created_at' => current_time('mysql')
            ]);
            if ($i < 80) { // 80% open rate
                $wpdb->insert($wpdb->prefix . 'eem_analytics', [
                    'event_type' => 'email_open',
                    'campaign_id' => $campaign1,
                    'subscriber_id' => $i + 1,
                    'created_at' => current_time('mysql')
                ]);
            }
        }
        
        // Campaign 2: Medium performance  
        for ($i = 0; $i < 100; $i++) {
            $wpdb->insert($wpdb->prefix . 'eem_analytics', [
                'event_type' => 'email_sent',
                'campaign_id' => $campaign2,
                'subscriber_id' => $i + 1,
                'created_at' => current_time('mysql')
            ]);
            if ($i < 50) { // 50% open rate
                $wpdb->insert($wpdb->prefix . 'eem_analytics', [
                    'event_type' => 'email_open',
                    'campaign_id' => $campaign2,
                    'subscriber_id' => $i + 1,
                    'created_at' => current_time('mysql')
                ]);
            }
        }
        
        $top_campaigns = $this->analytics_tracker->get_top_performing_campaigns(3);
        
        $this->assertIsArray($top_campaigns);
        $this->assertCount(2, $top_campaigns); // Only campaigns with data
        
        // Should be ordered by performance
        $this->assertEquals('High Performer', $top_campaigns[0]['name']);
        $this->assertGreaterThan($top_campaigns[1]['open_rate'], $top_campaigns[0]['open_rate']);
    }
    
    public function test_generate_report() {
        $campaign_id = $this->create_test_campaign();
        $subscriber_id = $this->create_test_subscriber('report@example.com');
        
        // Add comprehensive test data
        $this->analytics_tracker->track_email_sent($campaign_id, $subscriber_id);
        $this->analytics_tracker->track_email_open($campaign_id, $subscriber_id);
        $this->analytics_tracker->track_email_click($campaign_id, $subscriber_id, 'https://example.com');
        $this->analytics_tracker->track_environmental_action($subscriber_id, 'article_read', 5);
        
        $report_data = [
            'type' => 'campaign',
            'campaign_id' => $campaign_id,
            'date_range' => [
                'start' => date('Y-m-d', strtotime('-7 days')),
                'end' => date('Y-m-d')
            ]
        ];
        
        $report = $this->analytics_tracker->generate_report($report_data);
        
        $this->assertIsArray($report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('metrics', $report);
        $this->assertArrayHasKey('charts', $report);
        $this->assertArrayHasKey('environmental_impact', $report);
        
        // Check summary data
        $summary = $report['summary'];
        $this->assertArrayHasKey('total_sent', $summary);
        $this->assertArrayHasKey('total_opens', $summary);
        $this->assertArrayHasKey('total_clicks', $summary);
    }
    
    public function test_cleanup_old_analytics() {
        global $wpdb;
        
        // Add old analytics data (beyond retention period)
        $old_date = date('Y-m-d H:i:s', strtotime('-95 days'));
        $wpdb->insert(
            $wpdb->prefix . 'eem_analytics',
            [
                'event_type' => 'email_open',
                'campaign_id' => 1,
                'subscriber_id' => 1,
                'created_at' => $old_date
            ]
        );
        
        // Add recent data (within retention period)
        $wpdb->insert(
            $wpdb->prefix . 'eem_analytics',
            [
                'event_type' => 'email_open',
                'campaign_id' => 1,
                'subscriber_id' => 2,
                'created_at' => current_time('mysql')
            ]
        );
        
        $cleanup_result = $this->analytics_tracker->cleanup_old_analytics(90);
        $this->assertTrue($cleanup_result);
        
        // Verify old data removed but recent data kept
        $old_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}eem_analytics WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
        $recent_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}eem_analytics WHERE created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)");
        
        $this->assertEquals(0, $old_count);
        $this->assertGreaterThan(0, $recent_count);
    }
    
    public function test_get_real_time_stats() {
        $campaign_id = $this->create_test_campaign();
        
        // Add recent activity
        $this->analytics_tracker->track_email_sent($campaign_id, 1);
        $this->analytics_tracker->track_email_open($campaign_id, 1);
        
        $stats = $this->analytics_tracker->get_real_time_stats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('last_hour', $stats);
        $this->assertArrayHasKey('last_24_hours', $stats);
        $this->assertArrayHasKey('active_campaigns', $stats);
        
        $last_hour = $stats['last_hour'];
        $this->assertArrayHasKey('opens', $last_hour);
        $this->assertArrayHasKey('clicks', $last_hour);
        $this->assertArrayHasKey('unsubscribes', $last_hour);
    }
}
