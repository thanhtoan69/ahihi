<?php
/**
 * Tests for EEM_Frontend class
 */

class Test_EEM_Frontend extends EEM_Test_Case {
    
    protected $frontend;
    
    public function setUp(): void {
        parent::setUp();
        $this->frontend = new EEM_Frontend();
    }
    
    public function test_subscription_form_shortcode() {
        // Test basic subscription form
        $output = $this->frontend->subscription_form_shortcode([]);
        
        $this->assertIsString($output);
        $this->assertStringContainsString('<form', $output);
        $this->assertStringContainsString('eem-subscription-form', $output);
        $this->assertStringContainsString('name="email"', $output);
        $this->assertStringContainsString('type="email"', $output);
        
        // Test with custom attributes
        $custom_output = $this->frontend->subscription_form_shortcode([
            'list_id' => '123',
            'style' => 'minimal',
            'button_text' => 'Join Our Mission'
        ]);
        
        $this->assertStringContainsString('data-list-id="123"', $custom_output);
        $this->assertStringContainsString('Join Our Mission', $custom_output);
        $this->assertStringContainsString('minimal', $custom_output);
    }
    
    public function test_preference_center_shortcode() {
        $subscriber_id = $this->create_test_subscriber('preference@example.com');
        
        // Mock user authentication
        $_GET['subscriber_id'] = $subscriber_id;
        $_GET['token'] = $this->subscriber_manager->generate_unsubscribe_token($subscriber_id);
        
        $output = $this->frontend->preference_center_shortcode([]);
        
        $this->assertIsString($output);
        $this->assertStringContainsString('eem-preference-center', $output);
        $this->assertStringContainsString('name="preferences', $output);
        $this->assertStringContainsString('Newsletter', $output);
        $this->assertStringContainsString('Environmental Tips', $output);
        
        // Cleanup
        unset($_GET['subscriber_id'], $_GET['token']);
    }
    
    public function test_unsubscribe_form_shortcode() {
        $subscriber_id = $this->create_test_subscriber('unsubscribe@example.com');
        $token = $this->subscriber_manager->generate_unsubscribe_token($subscriber_id);
        
        // Mock URL parameters
        $_GET['token'] = $token;
        
        $output = $this->frontend->unsubscribe_form_shortcode([]);
        
        $this->assertIsString($output);
        $this->assertStringContainsString('eem-unsubscribe-form', $output);
        $this->assertStringContainsString('unsubscribe@example.com', $output);
        $this->assertStringContainsString('Confirm Unsubscribe', $output);
        
        // Cleanup
        unset($_GET['token']);
    }
    
    public function test_handle_subscription_form() {
        // Mock AJAX request
        $_POST['action'] = 'eem_subscribe';
        $_POST['email'] = 'ajax@example.com';
        $_POST['first_name'] = 'Ajax';
        $_POST['last_name'] = 'Test';
        $_POST['eem_subscribe_nonce'] = wp_create_nonce('eem_subscribe');
        
        // Capture output
        ob_start();
        $this->frontend->handle_subscription_form();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        $this->assertIsArray($response);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('message', $response);
        
        // Verify subscriber created
        $subscriber = $this->subscriber_manager->get_subscriber_by_email('ajax@example.com');
        $this->assertNotFalse($subscriber);
        $this->assertEquals('Ajax', $subscriber['first_name']);
        
        // Cleanup
        unset($_POST['action'], $_POST['email'], $_POST['first_name'], $_POST['last_name'], $_POST['eem_subscribe_nonce']);
    }
    
    public function test_handle_unsubscribe_form() {
        $subscriber_id = $this->create_test_subscriber('unsubajax@example.com');
        $token = $this->subscriber_manager->generate_unsubscribe_token($subscriber_id);
        
        // Mock AJAX request
        $_POST['action'] = 'eem_unsubscribe';
        $_POST['token'] = $token;
        $_POST['eem_unsubscribe_nonce'] = wp_create_nonce('eem_unsubscribe');
        
        // Capture output
        ob_start();
        $this->frontend->handle_unsubscribe_form();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        $this->assertIsArray($response);
        $this->assertTrue($response['success']);
        
        // Verify unsubscription
        $subscriber = $this->subscriber_manager->get_subscriber($subscriber_id);
        $this->assertEquals('unsubscribed', $subscriber['status']);
        
        // Cleanup
        unset($_POST['action'], $_POST['token'], $_POST['eem_unsubscribe_nonce']);
    }
    
    public function test_handle_preference_update() {
        $subscriber_id = $this->create_test_subscriber('prefupdate@example.com');
        $token = $this->subscriber_manager->generate_unsubscribe_token($subscriber_id);
        
        // Mock AJAX request
        $_POST['action'] = 'eem_update_preferences';
        $_POST['subscriber_id'] = $subscriber_id;
        $_POST['token'] = $token;
        $_POST['preferences'] = [
            'newsletter' => 'on',
            'environmental_tips' => 'on',
            'promotions' => ''
        ];
        $_POST['eem_preferences_nonce'] = wp_create_nonce('eem_preferences');
        
        // Capture output
        ob_start();
        $this->frontend->handle_preference_update();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        $this->assertIsArray($response);
        $this->assertTrue($response['success']);
        
        // Verify preferences updated
        $subscriber = $this->subscriber_manager->get_subscriber($subscriber_id);
        $preferences = json_decode($subscriber['preferences'], true);
        $this->assertTrue($preferences['newsletter']);
        $this->assertTrue($preferences['environmental_tips']);
        $this->assertFalse($preferences['promotions']);
        
        // Cleanup
        unset($_POST['action'], $_POST['subscriber_id'], $_POST['token'], $_POST['preferences'], $_POST['eem_preferences_nonce']);
    }
    
    public function test_handle_email_tracking() {
        $campaign_id = $this->create_test_campaign();
        $subscriber_id = $this->create_test_subscriber('tracking@example.com');
        
        // Mock tracking request
        $_GET['action'] = 'eem_track_open';
        $_GET['campaign_id'] = $campaign_id;
        $_GET['subscriber_id'] = $subscriber_id;
        
        // Capture output
        ob_start();
        $this->frontend->handle_email_tracking();
        $output = ob_get_clean();
        
        // Should output tracking pixel
        $this->assertStringContainsString('image/gif', $output);
        
        // Verify tracking recorded
        $this->assert_database_has_record('eem_analytics', [
            'event_type' => 'email_open',
            'campaign_id' => $campaign_id,
            'subscriber_id' => $subscriber_id
        ]);
        
        // Cleanup
        unset($_GET['action'], $_GET['campaign_id'], $_GET['subscriber_id']);
    }
    
    public function test_handle_click_tracking() {
        $campaign_id = $this->create_test_campaign();
        $subscriber_id = $this->create_test_subscriber('clicktrack@example.com');
        $url = 'https://example.com/test';
        
        // Mock click tracking request
        $_GET['action'] = 'eem_track_click';
        $_GET['campaign_id'] = $campaign_id;
        $_GET['subscriber_id'] = $subscriber_id;
        $_GET['url'] = urlencode($url);
        
        // Capture output (should redirect)
        ob_start();
        $this->frontend->handle_click_tracking();
        $output = ob_get_clean();
        
        // Verify tracking recorded
        global $wpdb;
        $record = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}eem_analytics 
            WHERE event_type = %s AND campaign_id = %d AND subscriber_id = %d
        ", 'email_click', $campaign_id, $subscriber_id));
        
        $this->assertNotNull($record);
        $this->assertEquals($url, $record->url);
        
        // Cleanup
        unset($_GET['action'], $_GET['campaign_id'], $_GET['subscriber_id'], $_GET['url']);
    }
    
    public function test_confirm_subscription() {
        $subscriber_id = $this->create_test_subscriber('confirm@example.com', ['status' => 'pending']);
        $token = $this->subscriber_manager->generate_unsubscribe_token($subscriber_id);
        
        // Mock confirmation request
        $_GET['action'] = 'eem_confirm_subscription';
        $_GET['token'] = $token;
        
        ob_start();
        $this->frontend->confirm_subscription();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('confirmed', $output);
        
        // Verify confirmation
        $subscriber = $this->subscriber_manager->get_subscriber($subscriber_id);
        $this->assertEquals('active', $subscriber['status']);
        $this->assertNotNull($subscriber['confirmed_at']);
        
        // Cleanup
        unset($_GET['action'], $_GET['token']);
    }
    
    public function test_load_subscription_widget() {
        $widget_id = 'eem_subscription_widget_123';
        $widget_settings = [
            'title' => 'Join Our Environmental Movement',
            'description' => 'Get eco-friendly tips',
            'list_id' => '1',
            'style' => 'modern'
        ];
        
        // Mock widget request
        $_POST['action'] = 'eem_load_widget';
        $_POST['widget_id'] = $widget_id;
        $_POST['settings'] = $widget_settings;
        
        ob_start();
        $this->frontend->load_subscription_widget();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        $this->assertIsArray($response);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('html', $response);
        
        $html = $response['html'];
        $this->assertStringContainsString('Join Our Environmental Movement', $html);
        $this->assertStringContainsString('Get eco-friendly tips', $html);
        $this->assertStringContainsString('modern', $html);
        
        // Cleanup
        unset($_POST['action'], $_POST['widget_id'], $_POST['settings']);
    }
    
    public function test_validate_subscription_data() {
        // Test valid data
        $valid_data = [
            'email' => 'valid@example.com',
            'first_name' => 'Valid',
            'last_name' => 'User'
        ];
        
        $validation = $this->frontend->validate_subscription_data($valid_data);
        $this->assertTrue($validation['valid']);
        $this->assertEmpty($validation['errors']);
        
        // Test invalid data
        $invalid_data = [
            'email' => 'invalid-email',
            'first_name' => '',
            'last_name' => str_repeat('a', 101) // Too long
        ];
        
        $invalid_validation = $this->frontend->validate_subscription_data($invalid_data);
        $this->assertFalse($invalid_validation['valid']);
        $this->assertNotEmpty($invalid_validation['errors']);
        $this->assertContains('Invalid email address', $invalid_validation['errors']);
    }
    
    public function test_get_subscription_form_fields() {
        $default_fields = $this->frontend->get_subscription_form_fields();
        
        $this->assertIsArray($default_fields);
        $this->assertArrayHasKey('email', $default_fields);
        $this->assertArrayHasKey('first_name', $default_fields);
        $this->assertArrayHasKey('last_name', $default_fields);
        
        // Test email field structure
        $email_field = $default_fields['email'];
        $this->assertEquals('email', $email_field['type']);
        $this->assertEquals('Email Address', $email_field['label']);
        $this->assertTrue($email_field['required']);
        
        // Test with custom fields
        $custom_fields = $this->frontend->get_subscription_form_fields([
            'show_phone' => true,
            'show_company' => true
        ]);
        
        $this->assertArrayHasKey('phone', $custom_fields);
        $this->assertArrayHasKey('company', $custom_fields);
    }
    
    public function test_render_environmental_score_widget() {
        $subscriber_id = $this->create_test_subscriber('score@example.com', ['environmental_score' => 85]);
        
        $widget_html = $this->frontend->render_environmental_score_widget($subscriber_id);
        
        $this->assertIsString($widget_html);
        $this->assertStringContainsString('environmental-score', $widget_html);
        $this->assertStringContainsString('85', $widget_html);
        $this->assertStringContainsString('Eco Champion', $widget_html); // High score badge
    }
    
    public function test_get_environmental_tips() {
        $tips = $this->frontend->get_environmental_tips(5);
        
        $this->assertIsArray($tips);
        $this->assertCount(5, $tips);
        
        foreach ($tips as $tip) {
            $this->assertArrayHasKey('title', $tip);
            $this->assertArrayHasKey('content', $tip);
            $this->assertArrayHasKey('category', $tip);
            $this->assertArrayHasKey('difficulty', $tip);
        }
    }
    
    public function test_render_subscription_popup() {
        $popup_settings = [
            'title' => 'Save the Planet',
            'description' => 'Join our mission',
            'trigger' => 'exit_intent',
            'delay' => 30
        ];
        
        $popup_html = $this->frontend->render_subscription_popup($popup_settings);
        
        $this->assertIsString($popup_html);
        $this->assertStringContainsString('eem-popup', $popup_html);
        $this->assertStringContainsString('Save the Planet', $popup_html);
        $this->assertStringContainsString('Join our mission', $popup_html);
        $this->assertStringContainsString('data-trigger="exit_intent"', $popup_html);
    }
    
    public function test_handle_environmental_action_tracking() {
        $subscriber_id = $this->create_test_subscriber('actiontrack@example.com');
        
        // Mock action tracking
        $_POST['action'] = 'eem_track_environmental_action';
        $_POST['subscriber_id'] = $subscriber_id;
        $_POST['action_type'] = 'petition_signed';
        $_POST['action_value'] = 15;
        $_POST['eem_action_nonce'] = wp_create_nonce('eem_action');
        
        ob_start();
        $this->frontend->handle_environmental_action_tracking();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        $this->assertIsArray($response);
        $this->assertTrue($response['success']);
        
        // Verify action tracked
        $this->assert_database_has_record('eem_analytics', [
            'event_type' => 'environmental_action',
            'subscriber_id' => $subscriber_id
        ]);
        
        // Cleanup
        unset($_POST['action'], $_POST['subscriber_id'], $_POST['action_type'], $_POST['action_value'], $_POST['eem_action_nonce']);
    }
}
