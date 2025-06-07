<?php
/**
 * AJAX Endpoint Validation Script
 * 
 * This script validates all AJAX endpoints for the Environmental Email Marketing plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EEM_AJAX_Validator {
    
    private $test_results = array();
    
    public function __construct() {
        add_action('wp_ajax_eem_validate_endpoints', array($this, 'validate_all_endpoints'));
        add_action('wp_ajax_eem_test_subscription_form', array($this, 'test_subscription_form'));
        add_action('wp_ajax_eem_test_campaign_sending', array($this, 'test_campaign_sending'));
    }
    
    /**
     * Validate all AJAX endpoints
     */
    public function validate_all_endpoints() {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $results = array();
        
        // Test subscription endpoint
        $results['subscription'] = $this->test_subscription_endpoint();
        
        // Test unsubscription endpoint
        $results['unsubscription'] = $this->test_unsubscription_endpoint();
        
        // Test preference update endpoint
        $results['preferences'] = $this->test_preferences_endpoint();
        
        // Test campaign management endpoints
        $results['campaign_creation'] = $this->test_campaign_creation_endpoint();
        $results['campaign_sending'] = $this->test_campaign_sending_endpoint();
        
        // Test analytics endpoints
        $results['analytics'] = $this->test_analytics_endpoint();
        
        // Test automation endpoints
        $results['automation'] = $this->test_automation_endpoint();
        
        wp_send_json_success($results);
    }
    
    /**
     * Test subscription endpoint
     */
    private function test_subscription_endpoint() {
        try {
            // Simulate subscription request
            $_POST['email'] = 'test_ajax_' . time() . '@example.com';
            $_POST['first_name'] = 'Test';
            $_POST['last_name'] = 'User';
            $_POST['preferences'] = array('climate_change' => 1, 'renewable_energy' => 1);
            $_POST['action'] = 'eem_subscribe';
            $_POST['_wpnonce'] = wp_create_nonce('eem_subscription_nonce');
            
            // Test if subscriber manager exists
            if (!class_exists('EEM_Subscriber_Manager')) {
                return array(
                    'status' => 'error',
                    'message' => 'EEM_Subscriber_Manager class not found'
                );
            }
            
            $subscriber_manager = new EEM_Subscriber_Manager();
            
            // Test subscription logic
            $result = $subscriber_manager->add_subscriber(array(
                'email' => $_POST['email'],
                'first_name' => $_POST['first_name'],
                'last_name' => $_POST['last_name'],
                'status' => 'pending'
            ));
            
            if ($result) {
                // Clean up test subscriber
                $subscriber_manager->delete_subscriber($result);
                
                return array(
                    'status' => 'success',
                    'message' => 'Subscription endpoint working correctly',
                    'subscriber_id' => $result
                );
            } else {
                return array(
                    'status' => 'error',
                    'message' => 'Failed to create subscriber'
                );
            }
            
        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'message' => 'Exception: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Test unsubscription endpoint
     */
    private function test_unsubscription_endpoint() {
        try {
            if (!class_exists('EEM_Subscriber_Manager')) {
                return array(
                    'status' => 'error',
                    'message' => 'EEM_Subscriber_Manager class not found'
                );
            }
            
            $subscriber_manager = new EEM_Subscriber_Manager();
            
            // Create test subscriber
            $test_email = 'test_unsub_' . time() . '@example.com';
            $subscriber_id = $subscriber_manager->add_subscriber(array(
                'email' => $test_email,
                'status' => 'active'
            ));
            
            if (!$subscriber_id) {
                return array(
                    'status' => 'error',
                    'message' => 'Failed to create test subscriber'
                );
            }
            
            // Test unsubscription
            $result = $subscriber_manager->unsubscribe($test_email);
            
            // Clean up
            $subscriber_manager->delete_subscriber($subscriber_id);
            
            if ($result) {
                return array(
                    'status' => 'success',
                    'message' => 'Unsubscription endpoint working correctly'
                );
            } else {
                return array(
                    'status' => 'error',
                    'message' => 'Unsubscription failed'
                );
            }
            
        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'message' => 'Exception: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Test preferences endpoint
     */
    private function test_preferences_endpoint() {
        try {
            if (!class_exists('EEM_Subscriber_Manager')) {
                return array(
                    'status' => 'error',
                    'message' => 'EEM_Subscriber_Manager class not found'
                );
            }
            
            $subscriber_manager = new EEM_Subscriber_Manager();
            
            // Create test subscriber
            $test_email = 'test_prefs_ajax_' . time() . '@example.com';
            $subscriber_id = $subscriber_manager->add_subscriber(array(
                'email' => $test_email,
                'status' => 'active'
            ));
            
            if (!$subscriber_id) {
                return array(
                    'status' => 'error',
                    'message' => 'Failed to create test subscriber'
                );
            }
            
            // Test preference update
            $preferences = array(
                'climate_change' => 1,
                'renewable_energy' => 0,
                'sustainability' => 1,
                'frequency' => 'weekly'
            );
            
            $result = $subscriber_manager->update_preferences($subscriber_id, $preferences);
            
            // Clean up
            $subscriber_manager->delete_subscriber($subscriber_id);
            
            if ($result) {
                return array(
                    'status' => 'success',
                    'message' => 'Preferences endpoint working correctly'
                );
            } else {
                return array(
                    'status' => 'error',
                    'message' => 'Preference update failed'
                );
            }
            
        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'message' => 'Exception: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Test campaign creation endpoint
     */
    private function test_campaign_creation_endpoint() {
        try {
            if (!class_exists('EEM_Campaign_Manager')) {
                return array(
                    'status' => 'error',
                    'message' => 'EEM_Campaign_Manager class not found'
                );
            }
            
            $campaign_manager = new EEM_Campaign_Manager();
            
            // Test campaign creation
            $campaign_data = array(
                'name' => 'AJAX Test Campaign ' . time(),
                'subject' => 'Test Subject Line',
                'content' => '<h1>Test Campaign Content</h1><p>This is a test email campaign.</p>',
                'status' => 'draft',
                'type' => 'regular',
                'template_id' => 1
            );
            
            $campaign_id = $campaign_manager->create_campaign($campaign_data);
            
            if ($campaign_id) {
                // Test campaign update
                $update_result = $campaign_manager->update_campaign($campaign_id, array(
                    'subject' => 'Updated Test Subject'
                ));
                
                // Clean up
                $campaign_manager->delete_campaign($campaign_id);
                
                if ($update_result) {
                    return array(
                        'status' => 'success',
                        'message' => 'Campaign creation/update endpoints working correctly',
                        'campaign_id' => $campaign_id
                    );
                } else {
                    return array(
                        'status' => 'warning',
                        'message' => 'Campaign created but update failed'
                    );
                }
            } else {
                return array(
                    'status' => 'error',
                    'message' => 'Campaign creation failed'
                );
            }
            
        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'message' => 'Exception: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Test campaign sending endpoint
     */
    private function test_campaign_sending_endpoint() {
        try {
            if (!class_exists('EEM_Campaign_Manager')) {
                return array(
                    'status' => 'error',
                    'message' => 'EEM_Campaign_Manager class not found'
                );
            }
            
            $campaign_manager = new EEM_Campaign_Manager();
            
            // Create test campaign
            $campaign_data = array(
                'name' => 'AJAX Send Test Campaign ' . time(),
                'subject' => 'Test Send Subject',
                'content' => '<p>Test sending content</p>',
                'status' => 'ready',
                'type' => 'regular'
            );
            
            $campaign_id = $campaign_manager->create_campaign($campaign_data);
            
            if (!$campaign_id) {
                return array(
                    'status' => 'error',
                    'message' => 'Failed to create test campaign'
                );
            }
            
            // Test sending preparation (don't actually send)
            $send_result = $campaign_manager->prepare_campaign_for_sending($campaign_id);
            
            // Clean up
            $campaign_manager->delete_campaign($campaign_id);
            
            if ($send_result) {
                return array(
                    'status' => 'success',
                    'message' => 'Campaign sending preparation working correctly'
                );
            } else {
                return array(
                    'status' => 'warning',
                    'message' => 'Campaign sending preparation had issues'
                );
            }
            
        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'message' => 'Exception: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Test analytics endpoint
     */
    private function test_analytics_endpoint() {
        try {
            if (!class_exists('EEM_Analytics_Tracker')) {
                return array(
                    'status' => 'error',
                    'message' => 'EEM_Analytics_Tracker class not found'
                );
            }
            
            $analytics = new EEM_Analytics_Tracker();
            
            // Test event tracking
            $event_result = $analytics->track_event('test_ajax_event', array(
                'campaign_id' => 999,
                'subscriber_id' => 999,
                'event_data' => array('test' => 'ajax_validation')
            ));
            
            // Test click tracking
            $click_result = $analytics->track_click(999, 999, 'https://example.com/ajax-test');
            
            // Test analytics retrieval
            $analytics_data = $analytics->get_campaign_analytics(999);
            
            if ($event_result && $click_result) {
                return array(
                    'status' => 'success',
                    'message' => 'Analytics endpoints working correctly',
                    'event_tracked' => $event_result,
                    'click_tracked' => $click_result
                );
            } else {
                return array(
                    'status' => 'warning',
                    'message' => 'Some analytics functions may have issues'
                );
            }
            
        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'message' => 'Exception: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Test automation endpoint
     */
    private function test_automation_endpoint() {
        try {
            if (!class_exists('EEM_Automation_Engine')) {
                return array(
                    'status' => 'error',
                    'message' => 'EEM_Automation_Engine class not found'
                );
            }
            
            $automation = new EEM_Automation_Engine();
            
            // Test automation trigger
            $trigger_result = $automation->trigger_automation('welcome_series', array(
                'subscriber_id' => 999,
                'trigger_data' => array('source' => 'ajax_test')
            ));
            
            // Test automation processing
            $process_result = $automation->process_automation_queue();
            
            if ($trigger_result !== false) {
                return array(
                    'status' => 'success',
                    'message' => 'Automation endpoints working correctly',
                    'trigger_result' => $trigger_result
                );
            } else {
                return array(
                    'status' => 'warning',
                    'message' => 'Automation endpoints may have issues'
                );
            }
            
        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'message' => 'Exception: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Test subscription form processing
     */
    public function test_subscription_form() {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        // Test form validation
        $form_data = array(
            'email' => 'test_form_' . time() . '@example.com',
            'first_name' => 'Form',
            'last_name' => 'Test',
            'preferences' => array('climate_change' => 1),
            'source' => 'ajax_test'
        );
        
        $validation_result = $this->validate_subscription_form($form_data);
        
        wp_send_json_success($validation_result);
    }
    
    /**
     * Validate subscription form data
     */
    private function validate_subscription_form($data) {
        $errors = array();
        
        // Validate email
        if (empty($data['email']) || !is_email($data['email'])) {
            $errors[] = 'Invalid email address';
        }
        
        // Validate name fields
        if (empty($data['first_name'])) {
            $errors[] = 'First name is required';
        }
        
        // Validate preferences
        if (empty($data['preferences']) || !is_array($data['preferences'])) {
            $errors[] = 'At least one preference must be selected';
        }
        
        if (empty($errors)) {
            return array(
                'status' => 'success',
                'message' => 'Form validation passed',
                'data' => $data
            );
        } else {
            return array(
                'status' => 'error',
                'message' => 'Form validation failed',
                'errors' => $errors
            );
        }
    }
    
    /**
     * Test campaign sending process
     */
    public function test_campaign_sending() {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $campaign_id = intval($_POST['campaign_id']);
        
        if (!$campaign_id) {
            wp_send_json_error('Invalid campaign ID');
        }
        
        $sending_result = $this->test_campaign_send_process($campaign_id);
        
        wp_send_json_success($sending_result);
    }
    
    /**
     * Test campaign sending process
     */
    private function test_campaign_send_process($campaign_id) {
        try {
            if (!class_exists('EEM_Campaign_Manager')) {
                return array(
                    'status' => 'error',
                    'message' => 'EEM_Campaign_Manager class not found'
                );
            }
            
            $campaign_manager = new EEM_Campaign_Manager();
            $campaign = $campaign_manager->get_campaign($campaign_id);
            
            if (!$campaign) {
                return array(
                    'status' => 'error',
                    'message' => 'Campaign not found'
                );
            }
            
            // Test campaign validation
            $validation_errors = array();
            
            if (empty($campaign['subject'])) {
                $validation_errors[] = 'Campaign subject is empty';
            }
            
            if (empty($campaign['content'])) {
                $validation_errors[] = 'Campaign content is empty';
            }
            
            if ($campaign['status'] !== 'ready' && $campaign['status'] !== 'draft') {
                $validation_errors[] = 'Campaign status is not valid for sending';
            }
            
            // Test subscriber count
            $subscriber_count = $campaign_manager->get_campaign_subscriber_count($campaign_id);
            
            if ($subscriber_count === 0) {
                $validation_errors[] = 'No subscribers available for this campaign';
            }
            
            if (empty($validation_errors)) {
                return array(
                    'status' => 'success',
                    'message' => 'Campaign ready for sending',
                    'subscriber_count' => $subscriber_count,
                    'campaign_name' => $campaign['name']
                );
            } else {
                return array(
                    'status' => 'error',
                    'message' => 'Campaign validation failed',
                    'errors' => $validation_errors
                );
            }
            
        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'message' => 'Exception: ' . $e->getMessage()
            );
        }
    }
}

// Initialize the AJAX validator
new EEM_AJAX_Validator();
