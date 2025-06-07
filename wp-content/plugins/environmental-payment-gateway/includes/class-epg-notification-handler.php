<?php
/**
 * Notification Handler for Environmental Payment Gateway
 * 
 * Manages notifications for payment events, including email notifications,
 * SMS alerts, webhooks, and integration with third-party services.
 * 
 * @package EnvironmentalPaymentGateway
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Notification Handler Class
 */
class EPG_Notification_Handler {
    
    /**
     * Notification types
     */
    const TYPE_EMAIL = 'email';
    const TYPE_SMS = 'sms';
    const TYPE_WEBHOOK = 'webhook';
    const TYPE_PUSH = 'push';
    const TYPE_SLACK = 'slack';
    
    /**
     * Event types
     */
    const EVENT_PAYMENT_SUCCESS = 'payment_success';
    const EVENT_PAYMENT_FAILED = 'payment_failed';
    const EVENT_PAYMENT_PENDING = 'payment_pending';
    const EVENT_REFUND_PROCESSED = 'refund_processed';
    const EVENT_SUBSCRIPTION_CREATED = 'subscription_created';
    const EVENT_SUBSCRIPTION_CANCELLED = 'subscription_cancelled';
    const EVENT_FRAUD_DETECTED = 'fraud_detected';
    const EVENT_HIGH_VALUE_TRANSACTION = 'high_value_transaction';
    
    /**
     * Notification templates
     */
    private $templates = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize templates
        $this->init_templates();
        
        // Hook into payment events
        add_action('woocommerce_payment_complete', array($this, 'handle_payment_success'));
        add_action('woocommerce_order_status_failed', array($this, 'handle_payment_failed'));
        add_action('woocommerce_order_status_on-hold', array($this, 'handle_payment_pending'));
        add_action('woocommerce_order_refunded', array($this, 'handle_refund_processed'));
        
        // Hook into security events
        add_action('epg_security_event', array($this, 'handle_security_event'), 10, 2);
        
        // Schedule notification cleanup
        add_action('epg_cleanup_notifications', array($this, 'cleanup_old_notifications'));
        if (!wp_next_scheduled('epg_cleanup_notifications')) {
            wp_schedule_event(time(), 'daily', 'epg_cleanup_notifications');
        }
    }
    
    /**
     * Initialize notification templates
     */
    private function init_templates() {
        $this->templates = array(
            self::EVENT_PAYMENT_SUCCESS => array(
                'email' => array(
                    'subject' => __('Payment Confirmation - Order #{order_number}', 'environmental-payment-gateway'),
                    'template' => 'payment-success-email.php',
                ),
                'sms' => array(
                    'message' => __('Payment of {amount} {currency} received for order #{order_number}. Thank you!', 'environmental-payment-gateway'),
                ),
                'webhook' => array(
                    'event' => 'payment.completed',
                ),
            ),
            self::EVENT_PAYMENT_FAILED => array(
                'email' => array(
                    'subject' => __('Payment Failed - Order #{order_number}', 'environmental-payment-gateway'),
                    'template' => 'payment-failed-email.php',
                ),
                'sms' => array(
                    'message' => __('Payment failed for order #{order_number}. Please try again or contact support.', 'environmental-payment-gateway'),
                ),
                'webhook' => array(
                    'event' => 'payment.failed',
                ),
            ),
            self::EVENT_REFUND_PROCESSED => array(
                'email' => array(
                    'subject' => __('Refund Processed - Order #{order_number}', 'environmental-payment-gateway'),
                    'template' => 'refund-processed-email.php',
                ),
                'webhook' => array(
                    'event' => 'refund.processed',
                ),
            ),
            self::EVENT_FRAUD_DETECTED => array(
                'email' => array(
                    'subject' => __('⚠️ Fraud Alert - Order #{order_number}', 'environmental-payment-gateway'),
                    'template' => 'fraud-alert-email.php',
                ),
                'slack' => array(
                    'message' => __('🚨 Fraud detected for order #{order_number}. Amount: {amount} {currency}. IP: {ip}', 'environmental-payment-gateway'),
                    'channel' => '#security-alerts',
                ),
            ),
            self::EVENT_HIGH_VALUE_TRANSACTION => array(
                'email' => array(
                    'subject' => __('High Value Transaction Alert - {amount} {currency}', 'environmental-payment-gateway'),
                    'template' => 'high-value-alert-email.php',
                ),
                'slack' => array(
                    'message' => __('💰 High value transaction: {amount} {currency} for order #{order_number}', 'environmental-payment-gateway'),
                    'channel' => '#finance-alerts',
                ),
            ),
        );
        
        // Allow customization of templates
        $this->templates = apply_filters('epg_notification_templates', $this->templates);
    }
    
    /**
     * Send notification
     */
    public function send_notification($event_type, $data, $recipients = null, $notification_types = null) {
        // Get notification settings
        $settings = $this->get_notification_settings();
        
        if (!isset($this->templates[$event_type])) {
            return new WP_Error('invalid_event', 'Invalid event type');
        }
        
        $template = $this->templates[$event_type];
        
        // Determine which notification types to send
        if (!$notification_types) {
            $notification_types = array_keys($template);
        }
        
        $results = array();
        
        foreach ($notification_types as $type) {
            if (!isset($template[$type]) || !$this->is_notification_enabled($event_type, $type)) {
                continue;
            }
            
            $result = $this->send_single_notification($type, $event_type, $template[$type], $data, $recipients);
            $results[$type] = $result;
            
            // Log notification
            $this->log_notification($event_type, $type, $data, $result);
        }
        
        return $results;
    }
    
    /**
     * Send single notification
     */
    private function send_single_notification($type, $event_type, $template, $data, $recipients) {
        switch ($type) {
            case self::TYPE_EMAIL:
                return $this->send_email_notification($template, $data, $recipients);
            case self::TYPE_SMS:
                return $this->send_sms_notification($template, $data, $recipients);
            case self::TYPE_WEBHOOK:
                return $this->send_webhook_notification($template, $data);
            case self::TYPE_PUSH:
                return $this->send_push_notification($template, $data, $recipients);
            case self::TYPE_SLACK:
                return $this->send_slack_notification($template, $data);
            default:
                return new WP_Error('invalid_type', 'Invalid notification type');
        }
    }
    
    /**
     * Send email notification
     */
    private function send_email_notification($template, $data, $recipients = null) {
        if (!$recipients) {
            $recipients = $this->get_default_email_recipients();
        }
        
        if (!is_array($recipients)) {
            $recipients = array($recipients);
        }
        
        $subject = $this->parse_template($template['subject'], $data);
        
        // Get email content
        if (isset($template['template'])) {
            $content = $this->get_email_template_content($template['template'], $data);
        } else {
            $content = isset($template['content']) ? $this->parse_template($template['content'], $data) : '';
        }
        
        // Set email headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_option('woocommerce_email_from_name', get_bloginfo('name')) . ' <' . get_option('woocommerce_email_from_address', get_option('admin_email')) . '>',
        );
        
        $results = array();
        
        foreach ($recipients as $recipient) {
            $sent = wp_mail($recipient, $subject, $content, $headers);
            $results[$recipient] = $sent;
        }
        
        return $results;
    }
    
    /**
     * Send SMS notification
     */
    private function send_sms_notification($template, $data, $recipients = null) {
        if (!$recipients) {
            $recipients = $this->get_default_sms_recipients();
        }
        
        if (!is_array($recipients)) {
            $recipients = array($recipients);
        }
        
        $message = $this->parse_template($template['message'], $data);
        
        // Get SMS provider settings
        $sms_provider = get_option('epg_sms_provider', 'twilio');
        $sms_settings = get_option('epg_sms_settings', array());
        
        $results = array();
        
        foreach ($recipients as $recipient) {
            $result = $this->send_sms_via_provider($sms_provider, $recipient, $message, $sms_settings);
            $results[$recipient] = $result;
        }
        
        return $results;
    }
    
    /**
     * Send SMS via provider
     */
    private function send_sms_via_provider($provider, $to, $message, $settings) {
        switch ($provider) {
            case 'twilio':
                return $this->send_twilio_sms($to, $message, $settings);
            case 'nexmo':
                return $this->send_nexmo_sms($to, $message, $settings);
            case 'aws_sns':
                return $this->send_aws_sns_sms($to, $message, $settings);
            default:
                return new WP_Error('unsupported_provider', 'SMS provider not supported');
        }
    }
    
    /**
     * Send Twilio SMS
     */
    private function send_twilio_sms($to, $message, $settings) {
        if (empty($settings['account_sid']) || empty($settings['auth_token']) || empty($settings['from_number'])) {
            return new WP_Error('missing_settings', 'Twilio settings incomplete');
        }
        
        $url = 'https://api.twilio.com/2010-04-01/Accounts/' . $settings['account_sid'] . '/Messages.json';
        
        $data = array(
            'From' => $settings['from_number'],
            'To' => $to,
            'Body' => $message,
        );
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($settings['account_sid'] . ':' . $settings['auth_token']),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
            'body' => http_build_query($data),
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if (isset($result['error_code'])) {
            return new WP_Error('twilio_error', $result['message']);
        }
        
        return true;
    }
    
    /**
     * Send webhook notification
     */
    private function send_webhook_notification($template, $data) {
        $webhook_urls = get_option('epg_webhook_urls', array());
        
        if (empty($webhook_urls)) {
            return new WP_Error('no_webhooks', 'No webhook URLs configured');
        }
        
        $payload = array(
            'event' => $template['event'],
            'data' => $data,
            'timestamp' => current_time('timestamp'),
            'signature' => $this->generate_webhook_signature($data),
        );
        
        $results = array();
        
        foreach ($webhook_urls as $url) {
            $result = $this->send_webhook_to_url($url, $payload);
            $results[$url] = $result;
        }
        
        return $results;
    }
    
    /**
     * Send webhook to URL
     */
    private function send_webhook_to_url($url, $payload) {
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
                'User-Agent' => 'EPG-Webhook/1.0',
                'X-EPG-Event' => $payload['event'],
                'X-EPG-Signature' => $payload['signature'],
            ),
            'body' => json_encode($payload),
            'timeout' => 30,
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code >= 200 && $status_code < 300) {
            return true;
        } else {
            return new WP_Error('webhook_failed', 'Webhook returned status code: ' . $status_code);
        }
    }
    
    /**
     * Send Slack notification
     */
    private function send_slack_notification($template, $data) {
        $slack_webhook = get_option('epg_slack_webhook_url');
        
        if (empty($slack_webhook)) {
            return new WP_Error('no_slack_webhook', 'Slack webhook URL not configured');
        }
        
        $message = $this->parse_template($template['message'], $data);
        $channel = isset($template['channel']) ? $template['channel'] : '#general';
        
        $payload = array(
            'text' => $message,
            'channel' => $channel,
            'username' => 'EPG Bot',
            'icon_emoji' => ':money_with_wings:',
        );
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($payload),
        );
        
        $response = wp_remote_post($slack_webhook, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return true;
    }
    
    /**
     * Parse template with data placeholders
     */
    private function parse_template($template, $data) {
        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $template = str_replace('{' . $key . '}', $value, $template);
            }
        }
        
        return $template;
    }
    
    /**
     * Get email template content
     */
    private function get_email_template_content($template_file, $data) {
        $template_path = EPG_PLUGIN_PATH . 'templates/emails/' . $template_file;
        
        if (!file_exists($template_path)) {
            return $this->get_default_email_template($data);
        }
        
        ob_start();
        extract($data);
        include $template_path;
        return ob_get_clean();
    }
    
    /**
     * Get default email template
     */
    private function get_default_email_template($data) {
        $content = '<html><body>';
        $content .= '<h2>' . get_bloginfo('name') . '</h2>';
        $content .= '<p>Payment notification for order #' . (isset($data['order_number']) ? $data['order_number'] : 'N/A') . '</p>';
        $content .= '<ul>';
        
        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $content .= '<li><strong>' . ucfirst(str_replace('_', ' ', $key)) . ':</strong> ' . $value . '</li>';
            }
        }
        
        $content .= '</ul>';
        $content .= '<p>Thank you for your business!</p>';
        $content .= '</body></html>';
        
        return $content;
    }
    
    /**
     * Handle payment success event
     */
    public function handle_payment_success($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        $data = $this->get_order_notification_data($order);
        
        // Check for high value transaction
        if ($order->get_total() > $this->get_high_value_threshold()) {
            $this->send_notification(self::EVENT_HIGH_VALUE_TRANSACTION, $data);
        }
        
        $this->send_notification(self::EVENT_PAYMENT_SUCCESS, $data);
    }
    
    /**
     * Handle payment failed event
     */
    public function handle_payment_failed($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        $data = $this->get_order_notification_data($order);
        $this->send_notification(self::EVENT_PAYMENT_FAILED, $data);
    }
    
    /**
     * Handle payment pending event
     */
    public function handle_payment_pending($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        $data = $this->get_order_notification_data($order);
        $this->send_notification(self::EVENT_PAYMENT_PENDING, $data);
    }
    
    /**
     * Handle refund processed event
     */
    public function handle_refund_processed($order_id, $refund_id) {
        $order = wc_get_order($order_id);
        $refund = wc_get_order($refund_id);
        
        if (!$order || !$refund) {
            return;
        }
        
        $data = $this->get_order_notification_data($order);
        $data['refund_amount'] = $refund->get_total();
        $data['refund_reason'] = $refund->get_reason();
        
        $this->send_notification(self::EVENT_REFUND_PROCESSED, $data);
    }
    
    /**
     * Handle security events
     */
    public function handle_security_event($event_type, $event_data) {
        if ($event_type === 'fraud_detected' || $event_type === 'suspicious_pattern_detected') {
            $data = array_merge($event_data, array(
                'event_type' => $event_type,
                'timestamp' => current_time('mysql'),
            ));
            
            $this->send_notification(self::EVENT_FRAUD_DETECTED, $data, null, array(self::TYPE_EMAIL, self::TYPE_SLACK));
        }
    }
    
    /**
     * Get order notification data
     */
    private function get_order_notification_data($order) {
        return array(
            'order_id' => $order->get_id(),
            'order_number' => $order->get_order_number(),
            'amount' => $order->get_total(),
            'currency' => $order->get_currency(),
            'payment_method' => $order->get_payment_method_title(),
            'customer_name' => $order->get_formatted_billing_full_name(),
            'customer_email' => $order->get_billing_email(),
            'customer_phone' => $order->get_billing_phone(),
            'order_date' => $order->get_date_created()->format('Y-m-d H:i:s'),
            'order_status' => $order->get_status(),
            'site_name' => get_bloginfo('name'),
            'site_url' => get_site_url(),
        );
    }
    
    /**
     * Get notification settings
     */
    private function get_notification_settings() {
        return get_option('epg_notification_settings', array(
            'email_enabled' => true,
            'sms_enabled' => false,
            'webhook_enabled' => true,
            'slack_enabled' => false,
        ));
    }
    
    /**
     * Check if notification type is enabled for event
     */
    private function is_notification_enabled($event_type, $notification_type) {
        $settings = $this->get_notification_settings();
        $key = $notification_type . '_enabled';
        
        return isset($settings[$key]) && $settings[$key];
    }
    
    /**
     * Get default email recipients
     */
    private function get_default_email_recipients() {
        $recipients = get_option('epg_email_recipients', array());
        
        if (empty($recipients)) {
            $recipients = array(get_option('admin_email'));
        }
        
        return $recipients;
    }
    
    /**
     * Get default SMS recipients
     */
    private function get_default_sms_recipients() {
        return get_option('epg_sms_recipients', array());
    }
    
    /**
     * Get high value threshold
     */
    private function get_high_value_threshold() {
        return get_option('epg_high_value_threshold', 10000);
    }
    
    /**
     * Generate webhook signature
     */
    private function generate_webhook_signature($data) {
        $secret = get_option('epg_webhook_secret', wp_generate_password(32, false));
        return hash_hmac('sha256', json_encode($data), $secret);
    }
    
    /**
     * Log notification
     */
    private function log_notification($event_type, $notification_type, $data, $result) {
        global $wpdb;
        $notifications_table = $wpdb->prefix . 'epg_notifications';
        
        $status = is_wp_error($result) ? 'failed' : 'sent';
        $error_message = is_wp_error($result) ? $result->get_error_message() : null;
        
        $wpdb->insert($notifications_table, array(
            'event_type' => $event_type,
            'notification_type' => $notification_type,
            'status' => $status,
            'error_message' => $error_message,
            'data' => json_encode($data),
            'created_at' => current_time('mysql'),
        ));
    }
    
    /**
     * Get notification history
     */
    public function get_notification_history($limit = 100, $event_type = null, $notification_type = null) {
        global $wpdb;
        $notifications_table = $wpdb->prefix . 'epg_notifications';
        
        $where = array('1=1');
        $values = array();
        
        if ($event_type) {
            $where[] = 'event_type = %s';
            $values[] = $event_type;
        }
        
        if ($notification_type) {
            $where[] = 'notification_type = %s';
            $values[] = $notification_type;
        }
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$notifications_table} 
             WHERE " . implode(' AND ', $where) . "
             ORDER BY created_at DESC 
             LIMIT %d",
            array_merge($values, array($limit))
        );
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Clean up old notifications
     */
    public function cleanup_old_notifications($days = 30) {
        global $wpdb;
        $notifications_table = $wpdb->prefix . 'epg_notifications';
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$notifications_table} 
             WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
        
        return $deleted;
    }
    
    /**
     * Test notification sending
     */
    public function test_notification($notification_type, $test_data = null) {
        if (!$test_data) {
            $test_data = array(
                'order_id' => 12345,
                'order_number' => 'TEST-12345',
                'amount' => 100.00,
                'currency' => 'USD',
                'payment_method' => 'Test Gateway',
                'customer_name' => 'Test Customer',
                'customer_email' => get_option('admin_email'),
                'customer_phone' => '+1234567890',
                'order_date' => current_time('mysql'),
                'order_status' => 'processing',
                'site_name' => get_bloginfo('name'),
                'site_url' => get_site_url(),
            );
        }
        
        return $this->send_notification(self::EVENT_PAYMENT_SUCCESS, $test_data, null, array($notification_type));
    }
}
