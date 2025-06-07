<?php
/**
 * Amazon SES Email Service Provider for Environmental Email Marketing
 *
 * Integrates with Amazon Simple Email Service (SES) for cost-effective
 * email delivery, bounce handling, and comprehensive analytics.
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
 * Amazon SES Email Service Provider Class
 *
 * Handles all Amazon SES API interactions including email sending,
 * bounce handling, reputation monitoring, and configuration management.
 */
class EEM_Amazon_SES_Provider extends EEM_Email_Service_Provider {

    /**
     * Provider name
     *
     * @var string
     */
    protected $provider_name = 'amazon_ses';

    /**
     * AWS region
     *
     * @var string
     */
    private $region;

    /**
     * AWS access key ID
     *
     * @var string
     */
    private $access_key_id;

    /**
     * AWS secret access key
     *
     * @var string
     */
    private $secret_access_key;

    /**
     * Configuration set name
     *
     * @var string
     */
    private $configuration_set;

    /**
     * API version
     *
     * @var string
     */
    private $api_version = '2010-12-01';

    /**
     * Service endpoint
     *
     * @var string
     */
    private $endpoint;

    /**
     * Constructor
     *
     * @param array $config Provider configuration
     * @since 1.0.0
     */
    public function __construct($config = []) {
        parent::__construct($config);

        $this->region = $config['region'] ?? 'us-east-1';
        $this->access_key_id = $config['access_key_id'] ?? '';
        $this->secret_access_key = $config['secret_access_key'] ?? '';
        $this->configuration_set = $config['configuration_set'] ?? '';
        
        $this->endpoint = "https://email.{$this->region}.amazonaws.com/";

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

        if (empty($this->access_key_id)) {
            $errors[] = 'AWS Access Key ID is required';
        }

        if (empty($this->secret_access_key)) {
            $errors[] = 'AWS Secret Access Key is required';
        }

        if (empty($this->region)) {
            $errors[] = 'AWS region is required';
        }

        if (!empty($errors)) {
            $this->logger->log_error('Amazon SES configuration validation failed: ' . implode(', ', $errors));
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
            $response = $this->make_ses_request('GetSendQuota');

            if ($response && isset($response['GetSendQuotaResult'])) {
                $quota = $response['GetSendQuotaResult'];
                
                $this->logger->log_info('Amazon SES connection test successful');
                return [
                    'success' => true,
                    'message' => 'Connection successful',
                    'quota_info' => [
                        'max_24_hour' => $quota['Max24HourSend'] ?? 0,
                        'max_send_rate' => $quota['MaxSendRate'] ?? 0,
                        'sent_last_24_hours' => $quota['SentLast24Hours'] ?? 0
                    ]
                ];
            }

            return [
                'success' => false,
                'message' => 'Invalid response from Amazon SES API'
            ];

        } catch (Exception $e) {
            $this->logger->log_error('Amazon SES connection test failed: ' . $e->getMessage());
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

            // Prepare email parameters
            $params = $this->prepare_email_params($email_data);

            // Send email using SES
            $response = $this->make_ses_request('SendEmail', $params);

            if ($response && isset($response['SendEmailResult']['MessageId'])) {
                $message_id = $response['SendEmailResult']['MessageId'];
                
                $this->track_sent_email();
                $this->track_environmental_impact($email_data);

                $this->logger->log_info('Email sent successfully via Amazon SES: ' . $message_id);

                return [
                    'success' => true,
                    'message_id' => $message_id,
                    'message' => 'Email sent successfully'
                ];
            }

            throw new Exception('Invalid response from Amazon SES API');

        } catch (Exception $e) {
            $this->track_failed_email();
            $this->logger->log_error('Amazon SES email send failed: ' . $e->getMessage());

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

        // Get current send rate limit
        $send_rate = $this->get_send_rate_limit();
        $delay_between_sends = $send_rate > 0 ? (1 / $send_rate) : 1;

        foreach ($emails as $email) {
            $result = $this->send_email($email);
            
            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
            }

            $results['results'][] = [
                'email' => $email['to'],
                'success' => $result['success'],
                'message_id' => $result['message_id'] ?? null,
                'error' => $result['error'] ?? null
            ];

            // Rate limiting
            if ($delay_between_sends > 0) {
                usleep($delay_between_sends * 1000000);
            }
        }

        $this->logger->log_info("Amazon SES bulk send completed: {$results['success']} sent, {$results['failed']} failed");

        return $results;
    }

    /**
     * Send raw email (for advanced formatting)
     *
     * @param array $email_data Raw email data
     * @return array Send result
     * @since 1.0.0
     */
    public function send_raw_email($email_data) {
        try {
            $this->check_rate_limit();

            $params = [
                'RawMessage.Data' => base64_encode($email_data['raw_message']),
                'Source' => $email_data['from']
            ];

            // Add destinations
            if (!empty($email_data['destinations'])) {
                foreach ($email_data['destinations'] as $i => $dest) {
                    $params["Destinations.member." . ($i + 1)] = $dest;
                }
            }

            // Add configuration set
            if (!empty($this->configuration_set)) {
                $params['ConfigurationSetName'] = $this->configuration_set;
            }

            $response = $this->make_ses_request('SendRawEmail', $params);

            if ($response && isset($response['SendRawEmailResult']['MessageId'])) {
                $message_id = $response['SendRawEmailResult']['MessageId'];
                
                $this->track_sent_email();
                $this->track_environmental_impact($email_data);

                return [
                    'success' => true,
                    'message_id' => $message_id,
                    'message' => 'Raw email sent successfully'
                ];
            }

            throw new Exception('Invalid response from Amazon SES API');

        } catch (Exception $e) {
            $this->track_failed_email();
            $this->logger->log_error('Amazon SES raw email send failed: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get send statistics
     *
     * @return array Send statistics
     * @since 1.0.0
     */
    public function get_send_statistics() {
        try {
            $response = $this->make_ses_request('GetSendStatistics');

            if ($response && isset($response['GetSendStatisticsResult']['SendDataPoints'])) {
                $data_points = $response['GetSendStatisticsResult']['SendDataPoints'];
                
                return [
                    'success' => true,
                    'statistics' => $this->format_send_statistics($data_points)
                ];
            }

            return [
                'success' => false,
                'error' => 'No statistics available'
            ];

        } catch (Exception $e) {
            $this->logger->log_error('Failed to get Amazon SES send statistics: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get send quota
     *
     * @return array Send quota information
     * @since 1.0.0
     */
    public function get_send_quota() {
        try {
            $response = $this->make_ses_request('GetSendQuota');

            if ($response && isset($response['GetSendQuotaResult'])) {
                $quota = $response['GetSendQuotaResult'];
                
                return [
                    'success' => true,
                    'quota' => [
                        'max_24_hour' => (float) $quota['Max24HourSend'],
                        'max_send_rate' => (float) $quota['MaxSendRate'],
                        'sent_last_24_hours' => (float) $quota['SentLast24Hours'],
                        'remaining_quota' => (float) $quota['Max24HourSend'] - (float) $quota['SentLast24Hours']
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to get send quota'
            ];

        } catch (Exception $e) {
            $this->logger->log_error('Failed to get Amazon SES send quota: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process webhook (SNS notification)
     *
     * @param array $webhook_data Webhook data
     * @return bool Processing success
     * @since 1.0.0
     */
    public function process_webhook($webhook_data) {
        try {
            // Parse SNS message
            $message = json_decode($webhook_data['Message'] ?? '{}', true);
            
            if (!$message) {
                $this->logger->log_error('Invalid SNS message format');
                return false;
            }

            $event_type = $message['eventType'] ?? '';

            switch ($event_type) {
                case 'delivery':
                    $this->process_delivery_event($message);
                    break;

                case 'bounce':
                    $this->process_bounce_event($message);
                    break;

                case 'complaint':
                    $this->process_complaint_event($message);
                    break;

                case 'reject':
                    $this->process_reject_event($message);
                    break;

                case 'open':
                    $this->process_open_event($message);
                    break;

                case 'click':
                    $this->process_click_event($message);
                    break;

                default:
                    $this->logger->log_debug('Unhandled Amazon SES event type: ' . $event_type);
            }

            return true;

        } catch (Exception $e) {
            $this->logger->log_error('Amazon SES webhook processing failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify email address
     *
     * @param string $email Email address
     * @return array Verification result
     * @since 1.0.0
     */
    public function verify_email($email) {
        try {
            $params = ['EmailAddress' => $email];
            $response = $this->make_ses_request('VerifyEmailIdentity', $params);

            if ($response) {
                $this->logger->log_info('Email verification initiated for: ' . $email);
                
                return [
                    'success' => true,
                    'message' => 'Verification email sent'
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to initiate email verification'
            ];

        } catch (Exception $e) {
            $this->logger->log_error('Amazon SES email verification failed: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get verified identities
     *
     * @return array Verified identities
     * @since 1.0.0
     */
    public function get_verified_identities() {
        try {
            $response = $this->make_ses_request('ListVerifiedEmailAddresses');

            if ($response && isset($response['ListVerifiedEmailAddressesResult']['VerifiedEmailAddresses'])) {
                $addresses = $response['ListVerifiedEmailAddressesResult']['VerifiedEmailAddresses'];
                
                return [
                    'success' => true,
                    'identities' => $addresses
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to get verified identities'
            ];

        } catch (Exception $e) {
            $this->logger->log_error('Failed to get Amazon SES verified identities: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Make SES API request
     *
     * @param string $action API action
     * @param array $params Request parameters
     * @return array|null API response
     * @since 1.0.0
     */
    private function make_ses_request($action, $params = []) {
        $params['Action'] = $action;
        $params['Version'] = $this->api_version;

        // Create canonical query string
        ksort($params);
        $canonical_query_string = http_build_query($params);

        // Create request
        $request_body = $canonical_query_string;
        $headers = $this->create_auth_headers('POST', $request_body);

        $args = [
            'method' => 'POST',
            'headers' => $headers,
            'body' => $request_body,
            'timeout' => 30
        ];

        $response = wp_remote_post($this->endpoint, $args);

        if (is_wp_error($response)) {
            throw new Exception('SES request failed: ' . $response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($status_code >= 400) {
            $this->handle_api_error($body, $status_code);
        }

        // Parse XML response
        return $this->parse_xml_response($body);
    }

    /**
     * Create AWS authentication headers
     *
     * @param string $method HTTP method
     * @param string $body Request body
     * @return array Headers
     * @since 1.0.0
     */
    private function create_auth_headers($method, $body) {
        $date = gmdate('D, d M Y H:i:s T');
        $signature = base64_encode(hash_hmac('sha1', $date, $this->secret_access_key, true));

        return [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Date' => $date,
            'Authorization' => "AWS3-HTTPS AWSAccessKeyId={$this->access_key_id},Algorithm=HmacSHA1,Signature={$signature}",
            'User-Agent' => 'Environmental-Email-Marketing/1.0'
        ];
    }

    /**
     * Parse XML response
     *
     * @param string $xml XML response
     * @return array Parsed response
     * @since 1.0.0
     */
    private function parse_xml_response($xml) {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadXML($xml);

        if (libxml_get_errors()) {
            throw new Exception('Invalid XML response');
        }

        return $this->xml_to_array($dom);
    }

    /**
     * Convert XML to array
     *
     * @param DOMNode $node XML node
     * @return array Array representation
     * @since 1.0.0
     */
    private function xml_to_array($node) {
        $array = [];

        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                if ($child->nodeType == XML_ELEMENT_NODE) {
                    $key = $child->nodeName;
                    $value = $this->xml_to_array($child);
                    
                    if (isset($array[$key])) {
                        if (!is_array($array[$key]) || !isset($array[$key][0])) {
                            $array[$key] = [$array[$key]];
                        }
                        $array[$key][] = $value;
                    } else {
                        $array[$key] = $value;
                    }
                }
            }
        } else {
            $array = $node->nodeValue;
        }

        return $array;
    }

    /**
     * Handle API error
     *
     * @param string $response_body Response body
     * @param int $status_code Status code
     * @throws Exception
     * @since 1.0.0
     */
    private function handle_api_error($response_body, $status_code) {
        try {
            $error_data = $this->parse_xml_response($response_body);
            $error_message = $error_data['ErrorResponse']['Error']['Message'] ?? 'Unknown API error';
            $error_code = $error_data['ErrorResponse']['Error']['Code'] ?? 'Unknown';
        } catch (Exception $e) {
            $error_message = 'Failed to parse error response';
            $error_code = 'ParseError';
        }

        throw new Exception("SES API error ({$status_code}, {$error_code}): {$error_message}");
    }

    /**
     * Prepare email parameters for SES
     *
     * @param array $email_data Email data
     * @return array SES parameters
     * @since 1.0.0
     */
    private function prepare_email_params($email_data) {
        $params = [
            'Source' => $email_data['from'],
            'Destination.ToAddresses.member.1' => $email_data['to'],
            'Message.Subject.Data' => $email_data['subject'],
            'Message.Subject.Charset' => 'UTF-8'
        ];

        // Add message body
        if (!empty($email_data['html'])) {
            $params['Message.Body.Html.Data'] = $email_data['html'];
            $params['Message.Body.Html.Charset'] = 'UTF-8';
        }

        if (!empty($email_data['text'])) {
            $params['Message.Body.Text.Data'] = $email_data['text'];
            $params['Message.Body.Text.Charset'] = 'UTF-8';
        }

        // Add configuration set
        if (!empty($this->configuration_set)) {
            $params['ConfigurationSetName'] = $this->configuration_set;
        }

        // Add tags
        if (!empty($email_data['tags'])) {
            $i = 1;
            foreach ($email_data['tags'] as $key => $value) {
                $params["Tags.member.{$i}.Name"] = $key;
                $params["Tags.member.{$i}.Value"] = $value;
                $i++;
            }
        }

        return $params;
    }

    /**
     * Format send statistics
     *
     * @param array $data_points Raw data points
     * @return array Formatted statistics
     * @since 1.0.0
     */
    private function format_send_statistics($data_points) {
        $formatted = [];

        foreach ($data_points as $point) {
            $formatted[] = [
                'timestamp' => $point['Timestamp'],
                'delivery_attempts' => (int) $point['DeliveryAttempts'],
                'bounces' => (int) $point['Bounces'],
                'complaints' => (int) $point['Complaints'],
                'rejects' => (int) $point['Rejects']
            ];
        }

        return $formatted;
    }

    /**
     * Get send rate limit
     *
     * @return float Send rate limit
     * @since 1.0.0
     */
    private function get_send_rate_limit() {
        $quota = $this->get_send_quota();
        
        if ($quota['success']) {
            return $quota['quota']['max_send_rate'];
        }

        return 1.0; // Default fallback
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
            'mailing_lists' => false,
            'webhooks' => true,
            'tracking' => true,
            'analytics' => true,
            'templates' => false,
            'ab_testing' => false,
            'automation' => false,
            'segmentation' => false,
            'environmental_tracking' => true,
            'email_verification' => true,
            'bounce_handling' => true,
            'complaint_handling' => true
        ];
    }
}
