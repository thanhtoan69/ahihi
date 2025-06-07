<?php
/**
 * Environmental Email Marketing - Email Service Provider Base Class
 *
 * Base class for email service provider integrations
 *
 * @package Environmental_Email_Marketing
 * @subpackage Providers
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

abstract class EEM_Email_Service_Provider {

    /**
     * Provider name
     *
     * @var string
     */
    protected $provider_name;

    /**
     * API credentials
     *
     * @var array
     */
    protected $credentials;

    /**
     * API base URL
     *
     * @var string
     */
    protected $api_base_url;

    /**
     * Rate limits
     *
     * @var array
     */
    protected $rate_limits;

    /**
     * Constructor
     *
     * @param array $credentials API credentials
     */
    public function __construct($credentials = array()) {
        $this->credentials = $credentials;
        $this->rate_limits = array(
            'requests_per_minute' => 100,
            'emails_per_hour' => 1000
        );
    }

    /**
     * Send email to single recipient
     *
     * @param array $email_data Email data
     * @return array Send result
     */
    abstract public function send_email($email_data);

    /**
     * Send bulk emails
     *
     * @param array $batch_data Batch email data
     * @return array Batch send results
     */
    abstract public function send_bulk_emails($batch_data);

    /**
     * Create or update subscriber
     *
     * @param array $subscriber_data Subscriber data
     * @return array Result
     */
    abstract public function sync_subscriber($subscriber_data);

    /**
     * Create or update mailing list
     *
     * @param array $list_data List data
     * @return array Result
     */
    abstract public function sync_list($list_data);

    /**
     * Get delivery statistics
     *
     * @param array $params Parameters
     * @return array Statistics
     */
    abstract public function get_delivery_stats($params = array());

    /**
     * Handle webhooks
     *
     * @param array $webhook_data Webhook data
     * @return array Processing result
     */
    abstract public function process_webhook($webhook_data);

    /**
     * Validate API credentials
     *
     * @return bool Validation result
     */
    abstract public function validate_credentials();

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
            'a_b_testing' => false
        );
    }

    /**
     * Make API request
     *
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @param string $method HTTP method
     * @return array Response
     */
    protected function make_api_request($endpoint, $data = array(), $method = 'POST') {
        $url = rtrim($this->api_base_url, '/') . '/' . ltrim($endpoint, '/');
        
        $args = array(
            'method' => $method,
            'headers' => $this->get_request_headers(),
            'timeout' => 30,
            'sslverify' => true
        );
        
        if ($method === 'POST' || $method === 'PUT') {
            $args['body'] = json_encode($data);
        } elseif ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception('API request failed: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code < 200 || $status_code >= 300) {
            throw new Exception("API request failed with status {$status_code}: {$body}");
        }
        
        return json_decode($body, true);
    }

    /**
     * Get request headers
     *
     * @return array Headers
     */
    protected function get_request_headers() {
        return array(
            'Content-Type' => 'application/json',
            'User-Agent' => 'Environmental Email Marketing Plugin/1.0'
        );
    }

    /**
     * Log API activity
     *
     * @param string $action Action performed
     * @param array $data Action data
     * @param string $result Result status
     */
    protected function log_activity($action, $data = array(), $result = 'success') {
        $log_data = array(
            'provider' => $this->provider_name,
            'action' => $action,
            'data' => $data,
            'result' => $result,
            'timestamp' => current_time('mysql')
        );
        
        // Store in custom log table or WordPress options
        $activity_log = get_option('eem_provider_activity_log', array());
        $activity_log[] = $log_data;
        
        // Keep only last 1000 entries
        if (count($activity_log) > 1000) {
            $activity_log = array_slice($activity_log, -1000);
        }
        
        update_option('eem_provider_activity_log', $activity_log);
    }

    /**
     * Rate limiting check
     *
     * @param string $action Action type
     * @return bool Whether action is allowed
     */
    protected function check_rate_limit($action = 'request') {
        $rate_key = "eem_rate_limit_{$this->provider_name}_{$action}";
        $current_count = get_transient($rate_key) ?: 0;
        
        $limit = $action === 'email' ? $this->rate_limits['emails_per_hour'] : $this->rate_limits['requests_per_minute'];
        $duration = $action === 'email' ? HOUR_IN_SECONDS : MINUTE_IN_SECONDS;
        
        if ($current_count >= $limit) {
            return false;
        }
        
        set_transient($rate_key, $current_count + 1, $duration);
        return true;
    }

    /**
     * Handle API errors
     *
     * @param Exception $e Exception
     * @param array $context Error context
     */
    protected function handle_api_error($e, $context = array()) {
        $error_data = array(
            'provider' => $this->provider_name,
            'error' => $e->getMessage(),
            'context' => $context,
            'timestamp' => current_time('mysql')
        );
        
        // Log error
        error_log('EEM Provider Error: ' . json_encode($error_data));
        
        // Store error for admin review
        $error_log = get_option('eem_provider_errors', array());
        $error_log[] = $error_data;
        
        // Keep only last 500 errors
        if (count($error_log) > 500) {
            $error_log = array_slice($error_log, -500);
        }
        
        update_option('eem_provider_errors', $error_log);
    }

    /**
     * Parse webhook data
     *
     * @param array $raw_data Raw webhook data
     * @return array Standardized webhook data
     */
    protected function parse_webhook_data($raw_data) {
        // This should be implemented by each provider
        return array(
            'event_type' => 'unknown',
            'email' => '',
            'campaign_id' => '',
            'timestamp' => current_time('mysql'),
            'data' => $raw_data
        );
    }

    /**
     * Get environmental impact data for emails
     *
     * @param int $emails_sent Number of emails sent
     * @return array Environmental impact data
     */
    protected function calculate_environmental_impact($emails_sent) {
        // Average email carbon footprint: ~4g CO2
        $carbon_per_email = 0.004; // kg CO2
        $total_carbon = $emails_sent * $carbon_per_email;
        
        // Calculate energy usage (approximate)
        $energy_per_email = 0.0006; // kWh per email
        $total_energy = $emails_sent * $energy_per_email;
        
        return array(
            'emails_sent' => $emails_sent,
            'carbon_footprint_kg' => round($total_carbon, 4),
            'energy_usage_kwh' => round($total_energy, 4),
            'equivalent_trees' => round($total_carbon / 21.77, 6), // kg CO2 absorbed per tree per year
            'calculation_date' => current_time('mysql')
        );
    }
}
