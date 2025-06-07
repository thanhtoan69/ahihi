<?php
/**
 * Environmental Email Marketing - Mailchimp Provider
 *
 * Mailchimp email service provider integration
 *
 * @package Environmental_Email_Marketing
 * @subpackage Providers
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

require_once 'class-eem-email-service-provider.php';

class EEM_Mailchimp_Provider extends EEM_Email_Service_Provider {

    /**
     * Constructor
     *
     * @param array $credentials API credentials
     */
    public function __construct($credentials = array()) {
        parent::__construct($credentials);
        
        $this->provider_name = 'mailchimp';
        $this->api_base_url = 'https://' . ($credentials['datacenter'] ?? 'us1') . '.api.mailchimp.com/3.0';
        
        $this->rate_limits = array(
            'requests_per_minute' => 500,
            'emails_per_hour' => 2000
        );
    }

    /**
     * Send email to single recipient
     *
     * @param array $email_data Email data
     * @return array Send result
     */
    public function send_email($email_data) {
        if (!$this->check_rate_limit('email')) {
            throw new Exception('Rate limit exceeded for email sending');
        }
        
        try {
            // Mailchimp uses campaigns for sending emails
            $campaign_id = $this->create_campaign($email_data);
            
            if ($campaign_id) {
                $send_result = $this->send_campaign($campaign_id);
                
                $this->log_activity('send_email', array(
                    'campaign_id' => $campaign_id,
                    'recipient' => $email_data['to_email']
                ));
                
                return array(
                    'success' => true,
                    'provider_id' => $campaign_id,
                    'message' => 'Email sent successfully'
                );
            }
            
            throw new Exception('Failed to create campaign');
            
        } catch (Exception $e) {
            $this->handle_api_error($e, $email_data);
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Send bulk emails
     *
     * @param array $batch_data Batch email data
     * @return array Batch send results
     */
    public function send_bulk_emails($batch_data) {
        $results = array();
        $total_sent = 0;
        
        try {
            // Create campaign for bulk sending
            $campaign_data = array(
                'type' => 'regular',
                'recipients' => array(
                    'list_id' => $batch_data['list_id']
                ),
                'settings' => array(
                    'subject_line' => $batch_data['subject'],
                    'from_name' => $batch_data['from_name'] ?? get_bloginfo('name'),
                    'reply_to' => $batch_data['reply_to'] ?? get_option('admin_email'),
                    'title' => $batch_data['campaign_name'] ?? $batch_data['subject']
                )
            );
            
            $campaign_response = $this->make_api_request('campaigns', $campaign_data, 'POST');
            $campaign_id = $campaign_response['id'];
            
            // Set campaign content
            $content_data = array(
                'html' => $batch_data['html_content'],
                'plain_text' => $batch_data['text_content'] ?? strip_tags($batch_data['html_content'])
            );
            
            $this->make_api_request("campaigns/{$campaign_id}/content", $content_data, 'PUT');
            
            // Send campaign
            $send_response = $this->make_api_request("campaigns/{$campaign_id}/actions/send", array(), 'POST');
            
            $total_sent = count($batch_data['recipients'] ?? array());
            
            $this->log_activity('send_bulk_emails', array(
                'campaign_id' => $campaign_id,
                'recipients_count' => $total_sent
            ));
            
            $results = array(
                'success' => true,
                'provider_id' => $campaign_id,
                'sent_count' => $total_sent,
                'environmental_impact' => $this->calculate_environmental_impact($total_sent)
            );
            
        } catch (Exception $e) {
            $this->handle_api_error($e, $batch_data);
            $results = array(
                'success' => false,
                'error' => $e->getMessage(),
                'sent_count' => 0
            );
        }
        
        return $results;
    }

    /**
     * Create campaign
     *
     * @param array $email_data Email data
     * @return string Campaign ID
     */
    private function create_campaign($email_data) {
        $campaign_data = array(
            'type' => 'regular',
            'recipients' => array(
                'list_id' => $email_data['list_id']
            ),
            'settings' => array(
                'subject_line' => $email_data['subject'],
                'from_name' => $email_data['from_name'] ?? get_bloginfo('name'),
                'reply_to' => $email_data['reply_to'] ?? get_option('admin_email'),
                'title' => $email_data['campaign_name'] ?? $email_data['subject']
            )
        );
        
        $response = $this->make_api_request('campaigns', $campaign_data, 'POST');
        return $response['id'];
    }

    /**
     * Send campaign
     *
     * @param string $campaign_id Campaign ID
     * @return array Send result
     */
    private function send_campaign($campaign_id) {
        return $this->make_api_request("campaigns/{$campaign_id}/actions/send", array(), 'POST');
    }

    /**
     * Create or update subscriber
     *
     * @param array $subscriber_data Subscriber data
     * @return array Result
     */
    public function sync_subscriber($subscriber_data) {
        try {
            $list_id = $subscriber_data['list_id'];
            $email_hash = md5(strtolower($subscriber_data['email']));
            
            $member_data = array(
                'email_address' => $subscriber_data['email'],
                'status' => $subscriber_data['status'] ?? 'subscribed',
                'merge_fields' => array(
                    'FNAME' => $subscriber_data['first_name'] ?? '',
                    'LNAME' => $subscriber_data['last_name'] ?? ''
                ),
                'tags' => $subscriber_data['tags'] ?? array()
            );
            
            // Add environmental data to merge fields
            if (!empty($subscriber_data['environmental_score'])) {
                $member_data['merge_fields']['ECOSCORE'] = $subscriber_data['environmental_score'];
            }
            
            if (!empty($subscriber_data['sustainability_interests'])) {
                $member_data['merge_fields']['ECOINTEREST'] = implode(',', $subscriber_data['sustainability_interests']);
            }
            
            // Use PUT to create or update
            $response = $this->make_api_request(
                "lists/{$list_id}/members/{$email_hash}",
                $member_data,
                'PUT'
            );
            
            $this->log_activity('sync_subscriber', array(
                'email' => $subscriber_data['email'],
                'list_id' => $list_id
            ));
            
            return array(
                'success' => true,
                'provider_id' => $response['id'],
                'status' => $response['status']
            );
            
        } catch (Exception $e) {
            $this->handle_api_error($e, $subscriber_data);
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Create or update mailing list
     *
     * @param array $list_data List data
     * @return array Result
     */
    public function sync_list($list_data) {
        try {
            $mailchimp_list_data = array(
                'name' => $list_data['name'],
                'contact' => array(
                    'company' => get_bloginfo('name'),
                    'address1' => $list_data['contact']['address'] ?? '',
                    'city' => $list_data['contact']['city'] ?? '',
                    'state' => $list_data['contact']['state'] ?? '',
                    'zip' => $list_data['contact']['zip'] ?? '',
                    'country' => $list_data['contact']['country'] ?? 'US'
                ),
                'permission_reminder' => $list_data['permission_reminder'] ?? 'You are receiving this email because you subscribed to our environmental newsletter.',
                'campaign_defaults' => array(
                    'from_name' => get_bloginfo('name'),
                    'from_email' => get_option('admin_email'),
                    'subject' => 'Environmental Update',
                    'language' => 'en'
                ),
                'email_type_option' => true
            );
            
            if (!empty($list_data['provider_id'])) {
                // Update existing list
                $response = $this->make_api_request(
                    "lists/{$list_data['provider_id']}",
                    $mailchimp_list_data,
                    'PATCH'
                );
            } else {
                // Create new list
                $response = $this->make_api_request('lists', $mailchimp_list_data, 'POST');
            }
            
            $this->log_activity('sync_list', array(
                'list_name' => $list_data['name'],
                'list_id' => $response['id']
            ));
            
            return array(
                'success' => true,
                'provider_id' => $response['id'],
                'web_id' => $response['web_id'] ?? null
            );
            
        } catch (Exception $e) {
            $this->handle_api_error($e, $list_data);
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Get delivery statistics
     *
     * @param array $params Parameters
     * @return array Statistics
     */
    public function get_delivery_stats($params = array()) {
        try {
            $stats = array();
            
            // Get campaign stats if campaign_id provided
            if (!empty($params['campaign_id'])) {
                $campaign_stats = $this->make_api_request("campaigns/{$params['campaign_id']}", array(), 'GET');
                
                $stats = array(
                    'emails_sent' => $campaign_stats['emails_sent'] ?? 0,
                    'opens' => $campaign_stats['opens']['opens_total'] ?? 0,
                    'unique_opens' => $campaign_stats['opens']['unique_opens'] ?? 0,
                    'open_rate' => $campaign_stats['opens']['open_rate'] ?? 0,
                    'clicks' => $campaign_stats['clicks']['clicks_total'] ?? 0,
                    'unique_clicks' => $campaign_stats['clicks']['unique_clicks'] ?? 0,
                    'click_rate' => $campaign_stats['clicks']['click_rate'] ?? 0,
                    'unsubscribes' => $campaign_stats['unsubscribed'] ?? 0,
                    'bounces' => $campaign_stats['bounces']['hard_bounces'] + $campaign_stats['bounces']['soft_bounces'] ?? 0
                );
                
                // Add environmental impact
                $stats['environmental_impact'] = $this->calculate_environmental_impact($stats['emails_sent']);
            }
            
            // Get overall account stats
            if (empty($params['campaign_id']) || !empty($params['include_account_stats'])) {
                $account_stats = $this->make_api_request('', array(), 'GET');
                
                $stats['account'] = array(
                    'total_subscribers' => $account_stats['total_subscribers'] ?? 0,
                    'total_sent' => $account_stats['total_sent'] ?? 0,
                    'account_name' => $account_stats['account_name'] ?? ''
                );
            }
            
            return $stats;
            
        } catch (Exception $e) {
            $this->handle_api_error($e, $params);
            return array('error' => $e->getMessage());
        }
    }

    /**
     * Handle webhooks
     *
     * @param array $webhook_data Webhook data
     * @return array Processing result
     */
    public function process_webhook($webhook_data) {
        try {
            $parsed_data = $this->parse_webhook_data($webhook_data);
            
            // Process different webhook events
            $result = array('processed' => false);
            
            switch ($parsed_data['event_type']) {
                case 'subscribe':
                    $result = $this->process_subscribe_webhook($parsed_data);
                    break;
                    
                case 'unsubscribe':
                    $result = $this->process_unsubscribe_webhook($parsed_data);
                    break;
                    
                case 'cleaned':
                    $result = $this->process_cleaned_webhook($parsed_data);
                    break;
                    
                case 'upemail':
                    $result = $this->process_email_change_webhook($parsed_data);
                    break;
                    
                case 'profile':
                    $result = $this->process_profile_update_webhook($parsed_data);
                    break;
            }
            
            $this->log_activity('process_webhook', array(
                'event_type' => $parsed_data['event_type'],
                'email' => $parsed_data['email']
            ));
            
            return $result;
            
        } catch (Exception $e) {
            $this->handle_api_error($e, $webhook_data);
            return array('error' => $e->getMessage());
        }
    }

    /**
     * Parse webhook data for Mailchimp
     *
     * @param array $raw_data Raw webhook data
     * @return array Standardized webhook data
     */
    protected function parse_webhook_data($raw_data) {
        return array(
            'event_type' => $raw_data['type'] ?? 'unknown',
            'email' => $raw_data['data']['email'] ?? '',
            'list_id' => $raw_data['data']['list_id'] ?? '',
            'timestamp' => current_time('mysql'),
            'data' => $raw_data
        );
    }

    /**
     * Process subscribe webhook
     *
     * @param array $data Webhook data
     * @return array Result
     */
    private function process_subscribe_webhook($data) {
        // Update local subscriber status
        do_action('eem_mailchimp_subscriber_subscribed', $data['email'], $data);
        
        return array('processed' => true, 'action' => 'subscribed');
    }

    /**
     * Process unsubscribe webhook
     *
     * @param array $data Webhook data
     * @return array Result
     */
    private function process_unsubscribe_webhook($data) {
        // Update local subscriber status
        do_action('eem_mailchimp_subscriber_unsubscribed', $data['email'], $data);
        
        return array('processed' => true, 'action' => 'unsubscribed');
    }

    /**
     * Process cleaned webhook (bounced/invalid emails)
     *
     * @param array $data Webhook data
     * @return array Result
     */
    private function process_cleaned_webhook($data) {
        // Mark subscriber as cleaned/bounced
        do_action('eem_mailchimp_subscriber_cleaned', $data['email'], $data);
        
        return array('processed' => true, 'action' => 'cleaned');
    }

    /**
     * Process email change webhook
     *
     * @param array $data Webhook data
     * @return array Result
     */
    private function process_email_change_webhook($data) {
        // Update subscriber email address
        do_action('eem_mailchimp_subscriber_email_changed', $data);
        
        return array('processed' => true, 'action' => 'email_changed');
    }

    /**
     * Process profile update webhook
     *
     * @param array $data Webhook data
     * @return array Result
     */
    private function process_profile_update_webhook($data) {
        // Update subscriber profile
        do_action('eem_mailchimp_subscriber_profile_updated', $data['email'], $data);
        
        return array('processed' => true, 'action' => 'profile_updated');
    }

    /**
     * Validate API credentials
     *
     * @return bool Validation result
     */
    public function validate_credentials() {
        try {
            $response = $this->make_api_request('', array(), 'GET');
            return !empty($response['account_id']);
            
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get request headers for Mailchimp
     *
     * @return array Headers
     */
    protected function get_request_headers() {
        $headers = parent::get_request_headers();
        
        if (!empty($this->credentials['api_key'])) {
            $headers['Authorization'] = 'Basic ' . base64_encode('anystring:' . $this->credentials['api_key']);
        }
        
        return $headers;
    }

    /**
     * Get provider capabilities
     *
     * @return array Capabilities
     */
    public function get_capabilities() {
        return array(
            'bulk_sending' => true,
            'templates' => true,
            'analytics' => true,
            'webhooks' => true,
            'automation' => true,
            'a_b_testing' => true,
            'segmentation' => true,
            'tagging' => true,
            'merge_fields' => true
        );
    }

    /**
     * Create A/B test campaign
     *
     * @param array $test_data A/B test data
     * @return array Result
     */
    public function create_ab_test($test_data) {
        try {
            $ab_campaign_data = array(
                'type' => 'variate',
                'recipients' => array(
                    'list_id' => $test_data['list_id']
                ),
                'variate_settings' => array(
                    'winner_criteria' => $test_data['winner_criteria'] ?? 'opens',
                    'wait_time' => $test_data['wait_time'] ?? 4, // hours
                    'test_size' => $test_data['test_size'] ?? 50, // percentage
                    'subject_lines' => $test_data['subject_variants']
                ),
                'settings' => array(
                    'from_name' => $test_data['from_name'] ?? get_bloginfo('name'),
                    'reply_to' => $test_data['reply_to'] ?? get_option('admin_email'),
                    'title' => $test_data['campaign_name']
                )
            );
            
            $response = $this->make_api_request('campaigns', $ab_campaign_data, 'POST');
            
            return array(
                'success' => true,
                'campaign_id' => $response['id'],
                'test_id' => $response['variate_settings']['test_id'] ?? null
            );
            
        } catch (Exception $e) {
            $this->handle_api_error($e, $test_data);
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Get A/B test results
     *
     * @param string $campaign_id Campaign ID
     * @return array Test results
     */
    public function get_ab_test_results($campaign_id) {
        try {
            $campaign = $this->make_api_request("campaigns/{$campaign_id}", array(), 'GET');
            
            if ($campaign['type'] !== 'variate') {
                throw new Exception('Campaign is not an A/B test');
            }
            
            return array(
                'success' => true,
                'test_complete' => $campaign['variate_settings']['test_complete'] ?? false,
                'winner_campaign_id' => $campaign['variate_settings']['winner_campaign_id'] ?? null,
                'variants' => $campaign['variate_settings']['combinations'] ?? array()
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Create environmental tags for subscriber segmentation
     *
     * @param string $list_id List ID
     * @return array Result
     */
    public function create_environmental_tags($list_id) {
        $environmental_tags = array(
            'Climate-Activist',
            'Eco-Shopper',
            'Green-Living',
            'Environmental-News',
            'Sustainability-Expert',
            'Climate-Concerned',
            'Renewable-Energy',
            'Zero-Waste',
            'Organic-Living',
            'Conservation-Supporter'
        );
        
        $results = array();
        
        foreach ($environmental_tags as $tag) {
            try {
                $tag_data = array(
                    'name' => $tag,
                    'static_segment' => false
                );
                
                $response = $this->make_api_request("lists/{$list_id}/segments", $tag_data, 'POST');
                
                $results[] = array(
                    'tag' => $tag,
                    'success' => true,
                    'segment_id' => $response['id']
                );
                
            } catch (Exception $e) {
                $results[] = array(
                    'tag' => $tag,
                    'success' => false,
                    'error' => $e->getMessage()
                );
            }
        }
        
        return $results;
    }
}
