<?php
/**
 * Environmental Email Marketing - SendGrid Provider
 *
 * SendGrid email service provider integration
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

class EEM_SendGrid_Provider extends EEM_Email_Service_Provider {

    /**
     * Constructor
     *
     * @param array $credentials API credentials
     */
    public function __construct($credentials = array()) {
        parent::__construct($credentials);
        
        $this->provider_name = 'sendgrid';
        $this->api_base_url = 'https://api.sendgrid.com/v3';
        
        $this->rate_limits = array(
            'requests_per_minute' => 600,
            'emails_per_hour' => 10000
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
            $mail_data = array(
                'personalizations' => array(
                    array(
                        'to' => array(
                            array(
                                'email' => $email_data['to_email'],
                                'name' => $email_data['to_name'] ?? ''
                            )
                        ),
                        'subject' => $email_data['subject']
                    )
                ),
                'from' => array(
                    'email' => $email_data['from_email'] ?? get_option('admin_email'),
                    'name' => $email_data['from_name'] ?? get_bloginfo('name')
                ),
                'content' => array(
                    array(
                        'type' => 'text/html',
                        'value' => $email_data['html_content']
                    )
                )
            );
            
            // Add plain text version if available
            if (!empty($email_data['text_content'])) {
                $mail_data['content'][] = array(
                    'type' => 'text/plain',
                    'value' => $email_data['text_content']
                );
            }
            
            // Add tracking settings
            $mail_data['tracking_settings'] = array(
                'click_tracking' => array('enable' => true),
                'open_tracking' => array('enable' => true),
                'subscription_tracking' => array('enable' => false)
            );
            
            // Add environmental custom fields
            if (!empty($email_data['environmental_data'])) {
                $mail_data['personalizations'][0]['custom_args'] = array(
                    'environmental_score' => $email_data['environmental_data']['score'] ?? 0,
                    'campaign_type' => $email_data['environmental_data']['campaign_type'] ?? 'general',
                    'carbon_offset' => $email_data['environmental_data']['carbon_offset'] ?? 0
                );
            }
            
            $response = $this->make_api_request('mail/send', $mail_data, 'POST');
            
            $this->log_activity('send_email', array(
                'recipient' => $email_data['to_email'],
                'subject' => $email_data['subject']
            ));
            
            return array(
                'success' => true,
                'provider_id' => $response['x-message-id'] ?? 'unknown',
                'message' => 'Email sent successfully'
            );
            
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
            // SendGrid supports up to 1000 personalizations per request
            $recipients = $batch_data['recipients'] ?? array();
            $batches = array_chunk($recipients, 1000);
            
            foreach ($batches as $batch) {
                $personalizations = array();
                
                foreach ($batch as $recipient) {
                    $personalization = array(
                        'to' => array(
                            array(
                                'email' => $recipient['email'],
                                'name' => $recipient['name'] ?? ''
                            )
                        )
                    );
                    
                    // Add custom args for environmental data
                    if (!empty($recipient['environmental_data'])) {
                        $personalization['custom_args'] = array(
                            'environmental_score' => $recipient['environmental_data']['score'] ?? 0,
                            'interests' => implode(',', $recipient['environmental_data']['interests'] ?? array()),
                            'subscriber_id' => $recipient['subscriber_id'] ?? 0
                        );
                    }
                    
                    // Add substitutions for personalization
                    if (!empty($recipient['substitutions'])) {
                        $personalization['substitutions'] = $recipient['substitutions'];
                    }
                    
                    $personalizations[] = $personalization;
                }
                
                $mail_data = array(
                    'personalizations' => $personalizations,
                    'from' => array(
                        'email' => $batch_data['from_email'] ?? get_option('admin_email'),
                        'name' => $batch_data['from_name'] ?? get_bloginfo('name')
                    ),
                    'subject' => $batch_data['subject'],
                    'content' => array(
                        array(
                            'type' => 'text/html',
                            'value' => $batch_data['html_content']
                        )
                    ),
                    'tracking_settings' => array(
                        'click_tracking' => array('enable' => true),
                        'open_tracking' => array('enable' => true)
                    )
                );
                
                // Add plain text version
                if (!empty($batch_data['text_content'])) {
                    $mail_data['content'][] = array(
                        'type' => 'text/plain',
                        'value' => $batch_data['text_content']
                    );
                }
                
                // Add template ID if using template
                if (!empty($batch_data['template_id'])) {
                    $mail_data['template_id'] = $batch_data['template_id'];
                }
                
                $response = $this->make_api_request('mail/send', $mail_data, 'POST');
                $total_sent += count($batch);
            }
            
            $this->log_activity('send_bulk_emails', array(
                'recipients_count' => $total_sent,
                'subject' => $batch_data['subject']
            ));
            
            $results = array(
                'success' => true,
                'sent_count' => $total_sent,
                'environmental_impact' => $this->calculate_environmental_impact($total_sent)
            );
            
        } catch (Exception $e) {
            $this->handle_api_error($e, $batch_data);
            $results = array(
                'success' => false,
                'error' => $e->getMessage(),
                'sent_count' => $total_sent
            );
        }
        
        return $results;
    }

    /**
     * Create or update subscriber (using Marketing Campaigns API)
     *
     * @param array $subscriber_data Subscriber data
     * @return array Result
     */
    public function sync_subscriber($subscriber_data) {
        try {
            $contact_data = array(
                'email' => $subscriber_data['email'],
                'first_name' => $subscriber_data['first_name'] ?? '',
                'last_name' => $subscriber_data['last_name'] ?? ''
            );
            
            // Add environmental custom fields
            if (!empty($subscriber_data['environmental_score'])) {
                $contact_data['custom_fields'] = array(
                    'environmental_score' => $subscriber_data['environmental_score'],
                    'sustainability_interests' => implode(',', $subscriber_data['sustainability_interests'] ?? array()),
                    'carbon_footprint' => $subscriber_data['carbon_footprint'] ?? 0,
                    'eco_engagement_level' => $subscriber_data['eco_engagement_level'] ?? 'beginner'
                );
            }
            
            // Add to lists if specified
            if (!empty($subscriber_data['list_ids'])) {
                $contact_data['list_ids'] = $subscriber_data['list_ids'];
            }
            
            $contacts_data = array(
                'contacts' => array($contact_data)
            );
            
            $response = $this->make_api_request('marketing/contacts', $contacts_data, 'PUT');
            
            $this->log_activity('sync_subscriber', array(
                'email' => $subscriber_data['email']
            ));
            
            return array(
                'success' => true,
                'provider_id' => $response['job_id'] ?? 'pending',
                'status' => 'synced'
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
            $sendgrid_list_data = array(
                'name' => $list_data['name']
            );
            
            if (!empty($list_data['provider_id'])) {
                // Update existing list
                $response = $this->make_api_request(
                    "marketing/lists/{$list_data['provider_id']}",
                    $sendgrid_list_data,
                    'PATCH'
                );
            } else {
                // Create new list
                $response = $this->make_api_request('marketing/lists', $sendgrid_list_data, 'POST');
            }
            
            $this->log_activity('sync_list', array(
                'list_name' => $list_data['name'],
                'list_id' => $response['id']
            ));
            
            return array(
                'success' => true,
                'provider_id' => $response['id']
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
            
            // Get overall stats for date range
            $end_date = $params['end_date'] ?? date('Y-m-d');
            $start_date = $params['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
            
            // Get global stats
            $global_stats = $this->make_api_request(
                "stats?start_date={$start_date}&end_date={$end_date}",
                array(),
                'GET'
            );
            
            $total_stats = array(
                'requests' => 0,
                'delivered' => 0,
                'opens' => 0,
                'unique_opens' => 0,
                'clicks' => 0,
                'unique_clicks' => 0,
                'bounces' => 0,
                'blocks' => 0,
                'spam_reports' => 0,
                'unsubscribes' => 0
            );
            
            foreach ($global_stats as $day_stats) {
                foreach ($day_stats['stats'] as $stat) {
                    foreach ($total_stats as $key => $value) {
                        if (isset($stat['metrics'][$key])) {
                            $total_stats[$key] += $stat['metrics'][$key];
                        }
                    }
                }
            }
            
            // Calculate rates
            $requests = $total_stats['requests'];
            $stats = array(
                'emails_sent' => $requests,
                'delivered' => $total_stats['delivered'],
                'opens' => $total_stats['opens'],
                'unique_opens' => $total_stats['unique_opens'],
                'clicks' => $total_stats['clicks'],
                'unique_clicks' => $total_stats['unique_clicks'],
                'bounces' => $total_stats['bounces'],
                'unsubscribes' => $total_stats['unsubscribes'],
                'spam_reports' => $total_stats['spam_reports']
            );
            
            if ($requests > 0) {
                $stats['delivery_rate'] = round(($total_stats['delivered'] / $requests) * 100, 2);
                $stats['open_rate'] = round(($total_stats['unique_opens'] / $requests) * 100, 2);
                $stats['click_rate'] = round(($total_stats['unique_clicks'] / $requests) * 100, 2);
                $stats['bounce_rate'] = round(($total_stats['bounces'] / $requests) * 100, 2);
                $stats['unsubscribe_rate'] = round(($total_stats['unsubscribes'] / $requests) * 100, 2);
            }
            
            // Add environmental impact
            $stats['environmental_impact'] = $this->calculate_environmental_impact($requests);
            
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
            $results = array();
            
            // SendGrid sends arrays of events
            $events = is_array($webhook_data) ? $webhook_data : array($webhook_data);
            
            foreach ($events as $event) {
                $parsed_data = $this->parse_webhook_data($event);
                $result = $this->process_single_webhook_event($parsed_data);
                $results[] = $result;
            }
            
            $this->log_activity('process_webhook', array(
                'events_count' => count($events)
            ));
            
            return array(
                'success' => true,
                'processed_count' => count($results),
                'results' => $results
            );
            
        } catch (Exception $e) {
            $this->handle_api_error($e, $webhook_data);
            return array('error' => $e->getMessage());
        }
    }

    /**
     * Process single webhook event
     *
     * @param array $parsed_data Parsed event data
     * @return array Result
     */
    private function process_single_webhook_event($parsed_data) {
        $result = array('processed' => false);
        
        switch ($parsed_data['event_type']) {
            case 'delivered':
                do_action('eem_sendgrid_email_delivered', $parsed_data['email'], $parsed_data);
                $result = array('processed' => true, 'action' => 'delivered');
                break;
                
            case 'open':
                do_action('eem_sendgrid_email_opened', $parsed_data['email'], $parsed_data);
                $result = array('processed' => true, 'action' => 'opened');
                break;
                
            case 'click':
                do_action('eem_sendgrid_email_clicked', $parsed_data['email'], $parsed_data['url'] ?? '', $parsed_data);
                $result = array('processed' => true, 'action' => 'clicked');
                break;
                
            case 'bounce':
            case 'blocked':
                do_action('eem_sendgrid_email_bounced', $parsed_data['email'], $parsed_data);
                $result = array('processed' => true, 'action' => 'bounced');
                break;
                
            case 'unsubscribe':
                do_action('eem_sendgrid_subscriber_unsubscribed', $parsed_data['email'], $parsed_data);
                $result = array('processed' => true, 'action' => 'unsubscribed');
                break;
                
            case 'spamreport':
                do_action('eem_sendgrid_spam_report', $parsed_data['email'], $parsed_data);
                $result = array('processed' => true, 'action' => 'spam_reported');
                break;
        }
        
        return $result;
    }

    /**
     * Parse webhook data for SendGrid
     *
     * @param array $raw_data Raw webhook data
     * @return array Standardized webhook data
     */
    protected function parse_webhook_data($raw_data) {
        return array(
            'event_type' => $raw_data['event'] ?? 'unknown',
            'email' => $raw_data['email'] ?? '',
            'timestamp' => $raw_data['timestamp'] ?? time(),
            'url' => $raw_data['url'] ?? '',
            'user_agent' => $raw_data['useragent'] ?? '',
            'ip' => $raw_data['ip'] ?? '',
            'reason' => $raw_data['reason'] ?? '',
            'data' => $raw_data
        );
    }

    /**
     * Validate API credentials
     *
     * @return bool Validation result
     */
    public function validate_credentials() {
        try {
            $response = $this->make_api_request('user/account', array(), 'GET');
            return !empty($response['type']);
            
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get request headers for SendGrid
     *
     * @return array Headers
     */
    protected function get_request_headers() {
        $headers = parent::get_request_headers();
        
        if (!empty($this->credentials['api_key'])) {
            $headers['Authorization'] = 'Bearer ' . $this->credentials['api_key'];
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
            'automation' => false,
            'a_b_testing' => true,
            'segmentation' => true,
            'custom_fields' => true,
            'transactional' => true
        );
    }

    /**
     * Create email template
     *
     * @param array $template_data Template data
     * @return array Result
     */
    public function create_template($template_data) {
        try {
            $sendgrid_template_data = array(
                'name' => $template_data['name'],
                'generation' => 'dynamic'
            );
            
            $response = $this->make_api_request('templates', $sendgrid_template_data, 'POST');
            $template_id = $response['id'];
            
            // Create template version
            $version_data = array(
                'template_id' => $template_id,
                'active' => 1,
                'name' => $template_data['name'] . ' v1',
                'html_content' => $template_data['html_content'],
                'plain_content' => $template_data['text_content'] ?? strip_tags($template_data['html_content']),
                'subject' => $template_data['subject'] ?? '{{subject}}'
            );
            
            $version_response = $this->make_api_request('templates/' . $template_id . '/versions', $version_data, 'POST');
            
            return array(
                'success' => true,
                'template_id' => $template_id,
                'version_id' => $version_response['id']
            );
            
        } catch (Exception $e) {
            $this->handle_api_error($e, $template_data);
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Create environmental custom fields
     *
     * @return array Result
     */
    public function create_environmental_custom_fields() {
        $custom_fields = array(
            array(
                'name' => 'environmental_score',
                'field_type' => 'Number'
            ),
            array(
                'name' => 'sustainability_interests',
                'field_type' => 'Text'
            ),
            array(
                'name' => 'carbon_footprint',
                'field_type' => 'Number'
            ),
            array(
                'name' => 'eco_engagement_level',
                'field_type' => 'Text'
            ),
            array(
                'name' => 'last_environmental_action',
                'field_type' => 'Date'
            )
        );
        
        $results = array();
        
        foreach ($custom_fields as $field) {
            try {
                $response = $this->make_api_request('marketing/field_definitions', $field, 'POST');
                
                $results[] = array(
                    'field' => $field['name'],
                    'success' => true,
                    'field_id' => $response['id']
                );
                
            } catch (Exception $e) {
                $results[] = array(
                    'field' => $field['name'],
                    'success' => false,
                    'error' => $e->getMessage()
                );
            }
        }
        
        return $results;
    }

    /**
     * Create A/B test for email subject lines
     *
     * @param array $test_data A/B test data
     * @return array Result
     */
    public function create_ab_test($test_data) {
        try {
            // SendGrid A/B testing is typically done through campaigns
            // This would require the Marketing Campaigns API
            
            $campaign_data = array(
                'title' => $test_data['campaign_name'],
                'subject' => $test_data['subject_variants'][0], // Use first variant as default
                'sender_id' => $test_data['sender_id'] ?? $this->get_default_sender_id(),
                'list_ids' => $test_data['list_ids'],
                'html_content' => $test_data['html_content'],
                'plain_content' => $test_data['text_content'] ?? strip_tags($test_data['html_content'])
            );
            
            $response = $this->make_api_request('marketing/singlesends', $campaign_data, 'POST');
            
            return array(
                'success' => true,
                'campaign_id' => $response['id'],
                'message' => 'A/B test campaign created (manual testing required)'
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
     * Get default sender ID
     *
     * @return int|null Sender ID
     */
    private function get_default_sender_id() {
        try {
            $senders = $this->make_api_request('marketing/senders', array(), 'GET');
            
            if (!empty($senders) && is_array($senders)) {
                return $senders[0]['id'] ?? null;
            }
            
            return null;
            
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Set up environmental webhook endpoints
     *
     * @param string $webhook_url Webhook URL
     * @return array Result
     */
    public function setup_environmental_webhooks($webhook_url) {
        try {
            $webhook_data = array(
                'enabled' => true,
                'url' => $webhook_url,
                'group_resubscribe' => true,
                'delivered' => true,
                'group_unsubscribe' => true,
                'spam_report' => true,
                'bounce' => true,
                'deferred' => true,
                'unsubscribe' => true,
                'processed' => true,
                'open' => true,
                'click' => true,
                'dropped' => true
            );
            
            $response = $this->make_api_request('user/webhooks/event', $webhook_data, 'POST');
            
            return array(
                'success' => true,
                'webhook_id' => $response['webhook_id'] ?? 'configured'
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }
}
