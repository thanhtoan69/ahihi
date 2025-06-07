<?php
/**
 * Native WordPress Email Provider for Environmental Email Marketing
 *
 * Uses WordPress's built-in wp_mail() function for email delivery.
 * Suitable for small lists and basic email functionality.
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
 * Native WordPress Email Provider Class
 *
 * Handles email sending using WordPress core functions with
 * additional tracking and environmental impact features.
 */
class EEM_Native_WordPress_Provider extends EEM_Email_Service_Provider {

    /**
     * Provider name
     *
     * @var string
     */
    protected $provider_name = 'native_wordpress';

    /**
     * Default from email
     *
     * @var string
     */
    private $from_email;

    /**
     * Default from name
     *
     * @var string
     */
    private $from_name;

    /**
     * SMTP configuration
     *
     * @var array
     */
    private $smtp_config;

    /**
     * Content type
     *
     * @var string
     */
    private $content_type = 'text/html';

    /**
     * Maximum emails per batch
     *
     * @var int
     */
    private $max_batch_size = 50;

    /**
     * Delay between batches (seconds)
     *
     * @var int
     */
    private $batch_delay = 5;

    /**
     * Constructor
     *
     * @param array $config Provider configuration
     * @since 1.0.0
     */
    public function __construct($config = []) {
        parent::__construct($config);

        $this->from_email = $config['from_email'] ?? get_option('admin_email');
        $this->from_name = $config['from_name'] ?? get_bloginfo('name');
        $this->smtp_config = $config['smtp'] ?? [];
        $this->content_type = $config['content_type'] ?? 'text/html';
        $this->max_batch_size = $config['max_batch_size'] ?? 50;
        $this->batch_delay = $config['batch_delay'] ?? 5;

        $this->setup_wordpress_hooks();
        $this->validate_configuration();
    }

    /**
     * Setup WordPress hooks for email configuration
     *
     * @since 1.0.0
     */
    private function setup_wordpress_hooks() {
        add_filter('wp_mail_from', [$this, 'filter_from_email']);
        add_filter('wp_mail_from_name', [$this, 'filter_from_name']);
        add_filter('wp_mail_content_type', [$this, 'filter_content_type']);
        
        if (!empty($this->smtp_config)) {
            add_action('phpmailer_init', [$this, 'configure_smtp']);
        }
    }

    /**
     * Validate provider configuration
     *
     * @return bool
     * @since 1.0.0
     */
    public function validate_configuration() {
        $errors = [];

        if (empty($this->from_email) || !is_email($this->from_email)) {
            $errors[] = 'Valid from email address is required';
        }

        if (empty($this->from_name)) {
            $errors[] = 'From name is required';
        }

        // Validate SMTP configuration if provided
        if (!empty($this->smtp_config)) {
            if (empty($this->smtp_config['host'])) {
                $errors[] = 'SMTP host is required when using SMTP';
            }
            if (empty($this->smtp_config['port'])) {
                $errors[] = 'SMTP port is required when using SMTP';
            }
        }

        if (!empty($errors)) {
            $this->logger->log_error('Native WordPress email configuration validation failed: ' . implode(', ', $errors));
            return false;
        }

        return true;
    }

    /**
     * Test email functionality
     *
     * @return array Test results
     * @since 1.0.0
     */
    public function test_connection() {
        try {
            $test_email = [
                'to' => $this->from_email,
                'subject' => 'Environmental Email Marketing - Test Email',
                'html' => '<p>This is a test email from the Environmental Email Marketing plugin.</p>',
                'text' => 'This is a test email from the Environmental Email Marketing plugin.'
            ];

            $result = $this->send_email($test_email);

            if ($result['success']) {
                $this->logger->log_info('Native WordPress email test successful');
                return [
                    'success' => true,
                    'message' => 'Test email sent successfully',
                    'provider_info' => $this->get_provider_info()
                ];
            }

            return [
                'success' => false,
                'message' => $result['error'] ?? 'Test email failed'
            ];

        } catch (Exception $e) {
            $this->logger->log_error('Native WordPress email test failed: ' . $e->getMessage());
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

            // Prepare email headers
            $headers = $this->prepare_headers($email_data);

            // Prepare email content
            $message = $this->prepare_message($email_data);

            // Add tracking pixels if HTML content
            if ($this->content_type === 'text/html' && !empty($email_data['campaign_id'])) {
                $message = $this->add_tracking_pixels($message, $email_data);
            }

            // Send email using wp_mail
            $sent = wp_mail(
                $email_data['to'],
                $email_data['subject'],
                $message,
                $headers
            );

            if ($sent) {
                $this->track_sent_email();
                $this->track_environmental_impact($email_data);

                // Generate mock message ID for consistency
                $message_id = $this->generate_message_id($email_data);

                $this->logger->log_info('Email sent successfully via Native WordPress: ' . $email_data['to']);

                return [
                    'success' => true,
                    'message_id' => $message_id,
                    'message' => 'Email sent successfully'
                ];
            }

            throw new Exception('wp_mail() returned false');

        } catch (Exception $e) {
            $this->track_failed_email();
            $this->logger->log_error('Native WordPress email send failed: ' . $e->getMessage());

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

        $batches = array_chunk($emails, $this->max_batch_size);
        $total_batches = count($batches);

        $this->logger->log_info("Starting bulk email send: {$total_batches} batches, " . count($emails) . " total emails");

        foreach ($batches as $batch_index => $batch) {
            $batch_results = $this->send_email_batch($batch, $batch_index + 1, $total_batches);
            
            $results['success'] += $batch_results['success'];
            $results['failed'] += $batch_results['failed'];
            $results['results'] = array_merge($results['results'], $batch_results['results']);

            // Delay between batches to prevent overwhelming the server
            if ($batch_index < $total_batches - 1 && $this->batch_delay > 0) {
                sleep($this->batch_delay);
            }
        }

        $this->logger->log_info("Native WordPress bulk send completed: {$results['success']} sent, {$results['failed']} failed");

        return $results;
    }

    /**
     * Send email batch
     *
     * @param array $batch Email batch
     * @param int $batch_number Current batch number
     * @param int $total_batches Total number of batches
     * @return array Batch results
     * @since 1.0.0
     */
    private function send_email_batch($batch, $batch_number, $total_batches) {
        $results = [
            'success' => 0,
            'failed' => 0,
            'results' => []
        ];

        $this->logger->log_debug("Processing batch {$batch_number}/{$total_batches} with " . count($batch) . " emails");

        foreach ($batch as $email) {
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

            // Small delay between individual emails
            usleep(100000); // 0.1 seconds
        }

        return $results;
    }

    /**
     * Get email statistics (limited functionality)
     *
     * @param string $campaign_id Campaign ID
     * @return array Campaign statistics
     * @since 1.0.0
     */
    public function get_campaign_stats($campaign_id) {
        // Native WordPress provider has limited tracking capabilities
        // Stats are primarily tracked by our internal analytics system
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'eem_analytics_events';

        try {
            $stats = $wpdb->get_row($wpdb->prepare("
                SELECT 
                    SUM(CASE WHEN event_type = 'email_sent' THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN event_type = 'email_opened' THEN 1 ELSE 0 END) as opened,
                    SUM(CASE WHEN event_type = 'email_clicked' THEN 1 ELSE 0 END) as clicked,
                    SUM(CASE WHEN event_type = 'email_bounced' THEN 1 ELSE 0 END) as bounced,
                    SUM(CASE WHEN event_type = 'email_unsubscribed' THEN 1 ELSE 0 END) as unsubscribed
                FROM {$table_name} 
                WHERE campaign_id = %s
            ", $campaign_id), ARRAY_A);

            if ($stats) {
                return [
                    'success' => true,
                    'stats' => [
                        'sent' => (int) $stats['sent'],
                        'opened' => (int) $stats['opened'],
                        'clicked' => (int) $stats['clicked'],
                        'bounced' => (int) $stats['bounced'],
                        'unsubscribed' => (int) $stats['unsubscribed'],
                        'delivered' => (int) $stats['sent'] - (int) $stats['bounced'],
                        'open_rate' => $this->calculate_rate($stats['opened'], $stats['sent']),
                        'click_rate' => $this->calculate_rate($stats['clicked'], $stats['sent']),
                        'bounce_rate' => $this->calculate_rate($stats['bounced'], $stats['sent'])
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => 'No statistics available'
            ];

        } catch (Exception $e) {
            $this->logger->log_error('Failed to get Native WordPress campaign stats: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process webhook (not applicable for native WordPress)
     *
     * @param array $webhook_data Webhook data
     * @return bool Always returns false
     * @since 1.0.0
     */
    public function process_webhook($webhook_data) {
        // Native WordPress provider doesn't support webhooks
        $this->logger->log_warning('Webhook processing not supported by Native WordPress provider');
        return false;
    }

    /**
     * Filter from email address
     *
     * @param string $from_email Original from email
     * @return string Filtered from email
     * @since 1.0.0
     */
    public function filter_from_email($from_email) {
        return $this->from_email ?: $from_email;
    }

    /**
     * Filter from name
     *
     * @param string $from_name Original from name
     * @return string Filtered from name
     * @since 1.0.0
     */
    public function filter_from_name($from_name) {
        return $this->from_name ?: $from_name;
    }

    /**
     * Filter content type
     *
     * @param string $content_type Original content type
     * @return string Filtered content type
     * @since 1.0.0
     */
    public function filter_content_type($content_type) {
        return $this->content_type;
    }

    /**
     * Configure SMTP settings
     *
     * @param PHPMailer $phpmailer PHPMailer instance
     * @since 1.0.0
     */
    public function configure_smtp($phpmailer) {
        if (empty($this->smtp_config)) {
            return;
        }

        $phpmailer->isSMTP();
        $phpmailer->Host = $this->smtp_config['host'];
        $phpmailer->Port = $this->smtp_config['port'];
        $phpmailer->SMTPSecure = $this->smtp_config['security'] ?? 'tls';

        if (!empty($this->smtp_config['username']) && !empty($this->smtp_config['password'])) {
            $phpmailer->SMTPAuth = true;
            $phpmailer->Username = $this->smtp_config['username'];
            $phpmailer->Password = $this->smtp_config['password'];
        }

        if (!empty($this->smtp_config['debug'])) {
            $phpmailer->SMTPDebug = (int) $this->smtp_config['debug'];
        }

        $this->logger->log_debug('SMTP configuration applied');
    }

    /**
     * Prepare email headers
     *
     * @param array $email_data Email data
     * @return array Headers
     * @since 1.0.0
     */
    private function prepare_headers($email_data) {
        $headers = [];

        // Set content type
        $headers[] = 'Content-Type: ' . $this->content_type . '; charset=UTF-8';

        // Add custom headers
        if (!empty($email_data['headers'])) {
            foreach ($email_data['headers'] as $key => $value) {
                $headers[] = "{$key}: {$value}";
            }
        }

        // Add campaign tracking headers
        if (!empty($email_data['campaign_id'])) {
            $headers[] = 'X-EEM-Campaign-ID: ' . $email_data['campaign_id'];
        }

        if (!empty($email_data['subscriber_id'])) {
            $headers[] = 'X-EEM-Subscriber-ID: ' . $email_data['subscriber_id'];
        }

        return $headers;
    }

    /**
     * Prepare email message
     *
     * @param array $email_data Email data
     * @return string Message content
     * @since 1.0.0
     */
    private function prepare_message($email_data) {
        if ($this->content_type === 'text/html' && !empty($email_data['html'])) {
            return $email_data['html'];
        } elseif (!empty($email_data['text'])) {
            return $email_data['text'];
        } elseif (!empty($email_data['html'])) {
            // Convert HTML to text if no text version provided
            return wp_strip_all_tags($email_data['html']);
        }

        return '';
    }

    /**
     * Add tracking pixels to HTML content
     *
     * @param string $html HTML content
     * @param array $email_data Email data
     * @return string HTML with tracking pixels
     * @since 1.0.0
     */
    private function add_tracking_pixels($html, $email_data) {
        if (empty($email_data['campaign_id']) || empty($email_data['subscriber_id'])) {
            return $html;
        }

        $tracking_url = add_query_arg([
            'eem_track' => 'open',
            'campaign_id' => $email_data['campaign_id'],
            'subscriber_id' => $email_data['subscriber_id'],
            'hash' => $this->generate_tracking_hash($email_data)
        ], home_url());

        $tracking_pixel = '<img src="' . esc_url($tracking_url) . '" width="1" height="1" style="display:none;" alt="" />';

        // Add tracking pixel before closing body tag
        if (strpos($html, '</body>') !== false) {
            $html = str_replace('</body>', $tracking_pixel . '</body>', $html);
        } else {
            $html .= $tracking_pixel;
        }

        return $html;
    }

    /**
     * Generate message ID
     *
     * @param array $email_data Email data
     * @return string Message ID
     * @since 1.0.0
     */
    private function generate_message_id($email_data) {
        $domain = parse_url(home_url(), PHP_URL_HOST);
        $unique_id = uniqid('eem_', true);
        return "<{$unique_id}@{$domain}>";
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
     * Get provider information
     *
     * @return array Provider information
     * @since 1.0.0
     */
    private function get_provider_info() {
        global $phpmailer;
        
        $info = [
            'mailer' => 'WordPress wp_mail()',
            'from_email' => $this->from_email,
            'from_name' => $this->from_name,
            'content_type' => $this->content_type,
            'smtp_enabled' => !empty($this->smtp_config)
        ];

        if (!empty($this->smtp_config)) {
            $info['smtp_host'] = $this->smtp_config['host'];
            $info['smtp_port'] = $this->smtp_config['port'];
            $info['smtp_security'] = $this->smtp_config['security'] ?? 'none';
        }

        return $info;
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
            'webhooks' => false,
            'tracking' => true, // Limited tracking via our own system
            'analytics' => true, // Limited analytics via our own system
            'templates' => false,
            'ab_testing' => false,
            'automation' => false,
            'segmentation' => false,
            'environmental_tracking' => true,
            'smtp_support' => true,
            'bounce_handling' => false,
            'complaint_handling' => false
        ];
    }

    /**
     * Clean up WordPress hooks
     *
     * @since 1.0.0
     */
    public function __destruct() {
        remove_filter('wp_mail_from', [$this, 'filter_from_email']);
        remove_filter('wp_mail_from_name', [$this, 'filter_from_name']);
        remove_filter('wp_mail_content_type', [$this, 'filter_content_type']);
        remove_action('phpmailer_init', [$this, 'configure_smtp']);
    }
}
