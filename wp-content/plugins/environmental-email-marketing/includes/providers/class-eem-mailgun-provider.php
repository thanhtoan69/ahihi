<?php
/**
 * Mailgun Email Service Provider for Environmental Email Marketing
 *
 * Integrates with Mailgun API for reliable email delivery, bulk sending,
 * domain verification, and advanced email analytics.
 *
 * @package Environmental_Email_Marketing
 * @subpackage Providers
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Mailgun Email Service Provider Class
 *
 * Handles all Mailgun API interactions including campaign sending,
 * subscriber management, webhook processing, and analytics tracking.
 */
class EEM_Mailgun_Provider extends EEM_Email_Service_Provider {

    /**
     * Provider name
     *
     * @var string
     */
    protected $provider_name = 'mailgun';

    /**
     * API base URL
     *
     * @var string
     */
    private $api_base_url = 'https://api.mailgun.net/v3/';

    /**
     * EU API base URL
     *
     * @var string
     */
    private $eu_api_base_url = 'https://api.eu.mailgun.net/v3/';

    /**
     * API key
     *
     * @var string
     */
    private $api_key;

    /**
     * Domain name
     *
     * @var string
     */
    private $domain;

    /**
     * Use EU servers
     *
     * @var bool
     */
    private $use_eu_servers;

    /**
     * Webhook signing key
     *
     * @var string
     */
    private $webhook_signing_key;

    /**
     * Constructor
     *
     * @param array $config Provider configuration
     * @since 1.0.0
     */
    public function __construct($config = []) {
        parent::__construct($config);

        $this->api_key = $config['api_key'] ?? '';
        $this->domain = $config['domain'] ?? '';
        $this->use_eu_servers = $config['use_eu_servers'] ?? false;
        $this->webhook_signing_key = $config['webhook_signing_key'] ?? '';

        $this->validate_configuration();
    }

    /**
     * Validate provider configuration
     *
     * @return bool
     * @since 1.0.0
     */
    public function validate_configuration() {
        $errors = [];

        if (empty($this->api_key)) {
            $errors[] = 'Mailgun API key is required';
        }

        if (empty($this->domain)) {
            $errors[] = 'Mailgun domain is required';
        }

        if (!empty($errors)) {
            $this->logger->log_error('Mailgun configuration validation failed: ' . implode(', ', $errors));
            return false;
        }

        return true;
    }

    /**
     * Test API connection
     *
     * @return array Connection test results
     * @since 1.0.0
     */
    public function test_connection() {
        try {
            $response = $this->make_api_request('GET', 'domains/' . $this->domain);

            if ($response && isset($response['domain'])) {
                $this->logger->log_info('Mailgun connection test successful');
                return [
                    'success' => true,
                    'message' => 'Connection successful',
                    'domain_info' => $response['domain']
                ];
            }

            return [
                'success' => false,
                'message' => 'Invalid response from Mailgun API'
            ];

        } catch (Exception $e) {
            $this->logger->log_error('Mailgun connection test failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Send single email
     *
     * @param array $email_data Email data
     * @return array Send result
     * @since 1.0.0
     */
    public function send_email($email_data) {
        try {
            $this->check_rate_limit();

            // Prepare email data
            $mailgun_data = $this->prepare_email_data($email_data);

            // Send email
            $response = $this->make_api_request('POST', $this->domain . '/messages', $mailgun_data);

            if ($response && isset($response['id'])) {
                $this->track_sent_email();
                $this->track_environmental_impact($email_data);

                $this->logger->log_info('Email sent successfully via Mailgun: ' . $response['id']);

                return [
                    'success' => true,
                    'message_id' => $response['id'],
                    'message' => $response['message'] ?? 'Email sent successfully'
                ];
            }

            throw new Exception('Invalid response from Mailgun API');

        } catch (Exception $e) {
            $this->track_failed_email();
            $this->logger->log_error('Mailgun email send failed: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send bulk emails
     *
     * @param array $emails Array of email data
     * @return array Bulk send results
     * @since 1.0.0
     */
    public function send_bulk_emails($emails) {
        $results = [
            'success' => 0,
            'failed' => 0,
            'results' => []
        ];

        foreach (array_chunk($emails, 1000) as $batch) {
            $batch_result = $this->send_email_batch($batch);
            
            $results['success'] += $batch_result['success'];
            $results['failed'] += $batch_result['failed'];
            $results['results'] = array_merge($results['results'], $batch_result['results']);

            // Rate limiting between batches
            if (count($batch) >= 1000) {
                sleep(1);
            }
        }

        $this->logger->log_info("Mailgun bulk send completed: {$results['success']} sent, {$results['failed']} failed");

        return $results;
    }

    /**
     * Send email batch
     *
     * @param array $batch Email batch
     * @return array Batch results
     * @since 1.0.0
     */
    private function send_email_batch($batch) {
        $results = [
            'success' => 0,
            'failed' => 0,
            'results' => []
        ];

        try {
            // Prepare batch data for Mailgun
            $batch_data = $this->prepare_batch_data($batch);

            // Send batch
            $response = $this->make_api_request('POST', $this->domain . '/messages', $batch_data);

            if ($response && isset($response['id'])) {
                $results['success'] = count($batch);
                $this->track_sent_emails(count($batch));

                foreach ($batch as $email) {
                    $results['results'][] = [
                        'email' => $email['to'],
                        'success' => true,
                        'message_id' => $response['id']
                    ];
                    $this->track_environmental_impact($email);
                }

            } else {
                throw new Exception('Batch send failed');
            }

        } catch (Exception $e) {
            $results['failed'] = count($batch);
            $this->track_failed_emails(count($batch));

            foreach ($batch as $email) {
                $results['results'][] = [
                    'email' => $email['to'],
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }

            $this->logger->log_error('Mailgun batch send failed: ' . $e->getMessage());
        }

        return $results;
    }

    /**
     * Create mailing list
     *
     * @param array $list_data List data
     * @return array Creation result
     * @since 1.0.0
     */
    public function create_list($list_data) {
        try {
            $mailgun_data = [
                'address' => $list_data['address'],
                'name' => $list_data['name'],
                'description' => $list_data['description'] ?? '',
                'access_level' => $list_data['access_level'] ?? 'readonly'
            ];

            $response = $this->make_api_request('POST', 'lists', $mailgun_data);

            if ($response && isset($response['list'])) {
                $this->logger->log_info('Mailgun list created: ' . $list_data['address']);

                return [
                    'success' => true,
                    'list_id' => $response['list']['address'],
                    'list_data' => $response['list']
                ];
            }

            throw new Exception('Failed to create list');

        } catch (Exception $e) {
            $this->logger->log_error('Mailgun list creation failed: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Add subscriber to list
     *
     * @param string $list_id List ID
     * @param array $subscriber_data Subscriber data
     * @return array Addition result
     * @since 1.0.0
     */
    public function add_subscriber($list_id, $subscriber_data) {
        try {
            $mailgun_data = [
                'address' => $subscriber_data['email'],
                'name' => $subscriber_data['name'] ?? '',
                'vars' => json_encode($subscriber_data['vars'] ?? []),
                'subscribed' => true
            ];

            $response = $this->make_api_request('POST', "lists/{$list_id}/members", $mailgun_data);

            if ($response && isset($response['member'])) {
                $this->logger->log_info('Subscriber added to Mailgun list: ' . $subscriber_data['email']);

                return [
                    'success' => true,
                    'subscriber_id' => $response['member']['address'],
                    'subscriber_data' => $response['member']
                ];
            }

            throw new Exception('Failed to add subscriber');

        } catch (Exception $e) {
            $this->logger->log_error('Mailgun subscriber addition failed: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Remove subscriber from list
     *
     * @param string $list_id List ID
     * @param string $email Subscriber email
     * @return array Removal result
     * @since 1.0.0
     */
    public function remove_subscriber($list_id, $email) {
        try {
            $response = $this->make_api_request('DELETE', "lists/{$list_id}/members/{$email}");

            if ($response && isset($response['message'])) {
                $this->logger->log_info('Subscriber removed from Mailgun list: ' . $email);

                return [
                    'success' => true,
                    'message' => $response['message']
                ];
            }

            throw new Exception('Failed to remove subscriber');

        } catch (Exception $e) {
            $this->logger->log_error('Mailgun subscriber removal failed: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get campaign statistics
     *
     * @param string $campaign_id Campaign ID
     * @return array Campaign statistics
     * @since 1.0.0
     */
    public function get_campaign_stats($campaign_id) {
        try {
            // Mailgun uses tags for campaign tracking
            $response = $this->make_api_request('GET', $this->domain . '/tags/' . $campaign_id . '/stats');

            if ($response && isset($response['stats'])) {
                return [
                    'success' => true,
                    'stats' => $this->format_campaign_stats($response['stats'])
                ];
            }

            return [
                'success' => false,
                'error' => 'No stats available'
            ];

        } catch (Exception $e) {
            $this->logger->log_error('Failed to get Mailgun campaign stats: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process webhook
     *
     * @param array $webhook_data Webhook data
     * @return bool Processing success
     * @since 1.0.0
     */
    public function process_webhook($webhook_data) {
        try {
            // Verify webhook signature
            if (!$this->verify_webhook_signature($webhook_data)) {
                $this->logger->log_error('Mailgun webhook signature verification failed');
                return false;
            }

            $event_data = $webhook_data['event-data'] ?? [];
            $event_type = $event_data['event'] ?? '';

            switch ($event_type) {
                case 'delivered':
                    $this->process_delivered_event($event_data);
                    break;

                case 'opened':
                    $this->process_opened_event($event_data);
                    break;

                case 'clicked':
                    $this->process_clicked_event($event_data);
                    break;

                case 'bounced':
                    $this->process_bounced_event($event_data);
                    break;

                case 'unsubscribed':
                    $this->process_unsubscribed_event($event_data);
                    break;

                case 'complained':
                    $this->process_complaint_event($event_data);
                    break;

                default:
                    $this->logger->log_debug('Unhandled Mailgun webhook event: ' . $event_type);
            }

            return true;

        } catch (Exception $e) {
            $this->logger->log_error('Mailgun webhook processing failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify webhook signature
     *
     * @param array $webhook_data Webhook data
     * @return bool Verification result
     * @since 1.0.0
     */
    private function verify_webhook_signature($webhook_data) {
        if (empty($this->webhook_signing_key)) {
            return true; // Skip verification if no key set
        }

        $signature = $webhook_data['signature'] ?? [];
        $token = $signature['token'] ?? '';
        $timestamp = $signature['timestamp'] ?? '';
        $signature_hash = $signature['signature'] ?? '';

        $expected_signature = hash_hmac('sha256', $timestamp . $token, $this->webhook_signing_key);

        return hash_equals($expected_signature, $signature_hash);
    }

    /**
     * Make API request to Mailgun
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @return array|null API response
     * @since 1.0.0
     */
    private function make_api_request($method, $endpoint, $data = []) {
        $base_url = $this->use_eu_servers ? $this->eu_api_base_url : $this->api_base_url;
        $url = $base_url . $endpoint;

        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode('api:' . $this->api_key),
                'User-Agent' => 'Environmental-Email-Marketing/1.0'
            ],
            'timeout' => 30
        ];

        if (!empty($data)) {
            if ($method === 'GET') {
                $url .= '?' . http_build_query($data);
            } else {
                $args['body'] = $data;
            }
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new Exception('API request failed: ' . $response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($status_code >= 400) {
            $error_data = json_decode($body, true);
            $error_message = $error_data['message'] ?? 'API request failed';
            throw new Exception("API error ({$status_code}): {$error_message}");
        }

        return json_decode($body, true);
    }

    /**
     * Prepare email data for Mailgun
     *
     * @param array $email_data Email data
     * @return array Formatted data
     * @since 1.0.0
     */
    private function prepare_email_data($email_data) {
        $data = [
            'from' => $email_data['from'],
            'to' => $email_data['to'],
            'subject' => $email_data['subject']
        ];

        // Add content
        if (!empty($email_data['html'])) {
            $data['html'] = $email_data['html'];
        }
        if (!empty($email_data['text'])) {
            $data['text'] = $email_data['text'];
        }

        // Add headers
        if (!empty($email_data['headers'])) {
            foreach ($email_data['headers'] as $key => $value) {
                $data["h:{$key}"] = $value;
            }
        }

        // Add tracking
        $data['o:tracking'] = 'yes';
        $data['o:tracking-clicks'] = 'yes';
        $data['o:tracking-opens'] = 'yes';

        // Add campaign tag
        if (!empty($email_data['campaign_id'])) {
            $data['o:tag'] = $email_data['campaign_id'];
        }

        // Add custom variables
        if (!empty($email_data['variables'])) {
            foreach ($email_data['variables'] as $key => $value) {
                $data["v:{$key}"] = $value;
            }
        }

        return $data;
    }

    /**
     * Prepare batch data for Mailgun
     *
     * @param array $batch Email batch
     * @return array Formatted batch data
     * @since 1.0.0
     */
    private function prepare_batch_data($batch) {
        if (empty($batch)) {
            return [];
        }

        $first_email = $batch[0];
        $data = [
            'from' => $first_email['from'],
            'subject' => $first_email['subject'],
            'html' => $first_email['html'] ?? '',
            'text' => $first_email['text'] ?? ''
        ];

        // Collect all recipients
        $recipients = [];
        foreach ($batch as $email) {
            $recipients[] = $email['to'];
        }
        $data['to'] = implode(',', $recipients);

        // Add tracking
        $data['o:tracking'] = 'yes';
        $data['o:tracking-clicks'] = 'yes';
        $data['o:tracking-opens'] = 'yes';

        return $data;
    }

    /**
     * Format campaign statistics
     *
     * @param array $stats Raw stats from Mailgun
     * @return array Formatted stats
     * @since 1.0.0
     */
    private function format_campaign_stats($stats) {
        return [
            'sent' => $stats['total']['sent'] ?? 0,
            'delivered' => $stats['total']['delivered'] ?? 0,
            'opened' => $stats['total']['opened'] ?? 0,
            'clicked' => $stats['total']['clicked'] ?? 0,
            'bounced' => $stats['total']['bounced'] ?? 0,
            'unsubscribed' => $stats['total']['unsubscribed'] ?? 0,
            'complained' => $stats['total']['complained'] ?? 0,
            'open_rate' => $this->calculate_rate($stats['total']['opened'] ?? 0, $stats['total']['delivered'] ?? 0),
            'click_rate' => $this->calculate_rate($stats['total']['clicked'] ?? 0, $stats['total']['delivered'] ?? 0),
            'bounce_rate' => $this->calculate_rate($stats['total']['bounced'] ?? 0, $stats['total']['sent'] ?? 0)
        ];
    }

    /**
     * Calculate percentage rate
     *
     * @param int $numerator Numerator
     * @param int $denominator Denominator
     * @return float Percentage rate
     * @since 1.0.0
     */
    private function calculate_rate($numerator, $denominator) {
        if ($denominator == 0) {
            return 0.0;
        }
        return round(($numerator / $denominator) * 100, 2);
    }

    /**
     * Get provider capabilities
     *
     * @return array Provider capabilities
     * @since 1.0.0
     */
    public function get_capabilities() {
        return [
            'transactional_email' => true,
            'bulk_email' => true,
            'mailing_lists' => true,
            'webhooks' => true,
            'tracking' => true,
            'analytics' => true,
            'templates' => false,
            'ab_testing' => false,
            'automation' => false,
            'segmentation' => false,
            'environmental_tracking' => true
        ];
    }
}
