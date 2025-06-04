<?php
/**
 * Donation Manager Class
 * 
 * Handles core donation processing, validation, and management
 * for the Environmental Donation System plugin.
 * 
 * @package EnvironmentalDonationSystem
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EDS_Donation_Manager {
    
    /**
     * Initialize donation manager
     */
    public function __construct() {
        add_action('wp_ajax_eds_process_donation', array($this, 'ajax_process_donation'));
        add_action('wp_ajax_nopriv_eds_process_donation', array($this, 'ajax_process_donation'));
        add_action('wp_ajax_eds_get_donation_status', array($this, 'ajax_get_donation_status'));
        add_action('wp_ajax_nopriv_eds_get_donation_status', array($this, 'ajax_get_donation_status'));
        add_action('wp_ajax_eds_cancel_donation', array($this, 'ajax_cancel_donation'));
        add_action('wp_ajax_eds_refund_donation', array($this, 'ajax_refund_donation'));
    }
    
    /**
     * Process donation AJAX handler
     */
    public function ajax_process_donation() {
        check_ajax_referer('eds_donation_nonce', 'nonce');
        
        $campaign_id = intval($_POST['campaign_id']);
        $amount = floatval($_POST['amount']);
        $donor_data = $this->sanitize_donor_data($_POST);
        $payment_data = $this->sanitize_payment_data($_POST);
        
        // Validate donation data
        $validation = $this->validate_donation_data($campaign_id, $amount, $donor_data, $payment_data);
        if (is_wp_error($validation)) {
            wp_send_json_error($validation->get_error_message());
        }
        
        // Process the donation
        $result = $this->process_donation($campaign_id, $amount, $donor_data, $payment_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Process a donation
     */
    public function process_donation($campaign_id, $amount, $donor_data, $payment_data) {
        global $wpdb;
        
        // Get campaign details
        $campaign = $this->get_campaign($campaign_id);
        if (!$campaign) {
            return new WP_Error('invalid_campaign', 'Campaign not found');
        }
        
        // Check if campaign is active and accepting donations
        if (!$this->is_campaign_accepting_donations($campaign)) {
            return new WP_Error('campaign_closed', 'Campaign is not accepting donations');
        }
        
        // Generate transaction ID
        $transaction_id = $this->generate_transaction_id();
        
        // Prepare donation data
        $donation_data = array(
            'campaign_id' => $campaign_id,
            'donor_user_id' => is_user_logged_in() ? get_current_user_id() : null,
            'donor_email' => $donor_data['email'],
            'donor_name' => $donor_data['name'],
            'donor_phone' => isset($donor_data['phone']) ? $donor_data['phone'] : null,
            'is_anonymous' => isset($donor_data['anonymous']) ? 1 : 0,
            'donation_amount' => $amount,
            'currency_code' => $campaign->currency_code,
            'donation_type' => isset($payment_data['recurring']) ? 'recurring' : 'one_time',
            'payment_method' => $payment_data['method'],
            'payment_processor' => $payment_data['processor'],
            'transaction_id' => $transaction_id,
            'payment_status' => 'pending',
            'net_amount' => $amount, // Will be updated after processing fee calculation
            'donor_message' => isset($donor_data['message']) ? $donor_data['message'] : null,
            'dedication_type' => isset($donor_data['dedication_type']) ? $donor_data['dedication_type'] : null,
            'dedication_name' => isset($donor_data['dedication_name']) ? $donor_data['dedication_name'] : null,
            'dedication_message' => isset($donor_data['dedication_message']) ? $donor_data['dedication_message'] : null,
            'notification_email' => isset($donor_data['notification_email']) ? $donor_data['notification_email'] : null,
            'tax_receipt_required' => isset($donor_data['tax_receipt']) ? 1 : 0,
            'source_campaign' => isset($donor_data['source']) ? $donor_data['source'] : null,
            'referral_source' => isset($donor_data['referral']) ? $donor_data['referral'] : null,
            'utm_source' => isset($donor_data['utm_source']) ? $donor_data['utm_source'] : null,
            'utm_medium' => isset($donor_data['utm_medium']) ? $donor_data['utm_medium'] : null,
            'utm_campaign' => isset($donor_data['utm_campaign']) ? $donor_data['utm_campaign'] : null,
            'device_type' => $this->detect_device_type(),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null,
        );
        
        // Insert donation record
        $result = $wpdb->insert(
            $wpdb->prefix . 'donations',
            $donation_data,
            array(
                '%d', '%d', '%s', '%s', '%s', '%d', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s',
                '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
            )
        );
        
        if ($result === false) {
            return new WP_Error('database_error', 'Failed to create donation record');
        }
        
        $donation_id = $wpdb->insert_id;
        
        // Process payment through payment processor
        $payment_processor = $this->get_payment_processor($payment_data['processor']);
        $payment_result = $payment_processor->process_payment($donation_id, $amount, $payment_data);
        
        if (is_wp_error($payment_result)) {
            // Update donation status to failed
            $this->update_donation_status($donation_id, 'failed');
            return $payment_result;
        }
        
        // Update donation with payment processor response
        $this->update_donation_payment_info($donation_id, $payment_result);
        
        // Handle recurring donation setup if applicable
        if (isset($payment_data['recurring']) && $payment_data['recurring']) {
            $this->setup_recurring_donation($donation_id, $payment_data);
        }
        
        // Update campaign totals
        $this->update_campaign_totals($campaign_id);
        
        // Send notifications
        $this->send_donation_notifications($donation_id);
        
        // Generate tax receipt if required
        if ($donation_data['tax_receipt_required']) {
            $this->generate_tax_receipt($donation_id);
        }
        
        return array(
            'donation_id' => $donation_id,
            'transaction_id' => $transaction_id,
            'status' => 'success',
            'message' => 'Donation processed successfully',
            'receipt_url' => $this->get_receipt_url($donation_id)
        );
    }
    
    /**
     * Validate donation data
     */
    private function validate_donation_data($campaign_id, $amount, $donor_data, $payment_data) {
        // Validate campaign
        if (empty($campaign_id) || !is_numeric($campaign_id)) {
            return new WP_Error('invalid_campaign', 'Invalid campaign ID');
        }
        
        // Validate amount
        if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
            return new WP_Error('invalid_amount', 'Invalid donation amount');
        }
        
        // Check minimum donation amount
        $campaign = $this->get_campaign($campaign_id);
        if ($campaign && $amount < $campaign->min_donation_amount) {
            return new WP_Error('amount_too_low', 
                sprintf('Minimum donation amount is %s', $campaign->min_donation_amount));
        }
        
        // Validate donor data
        if (empty($donor_data['email']) || !is_email($donor_data['email'])) {
            return new WP_Error('invalid_email', 'Valid email address is required');
        }
        
        if (empty($donor_data['name'])) {
            return new WP_Error('invalid_name', 'Donor name is required');
        }
        
        // Validate payment data
        if (empty($payment_data['method']) || empty($payment_data['processor'])) {
            return new WP_Error('invalid_payment', 'Payment method and processor are required');
        }
        
        return true;
    }
    
    /**
     * Get campaign details
     */
    private function get_campaign($campaign_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}donation_campaigns WHERE campaign_id = %d",
            $campaign_id
        ));
    }
    
    /**
     * Check if campaign is accepting donations
     */
    private function is_campaign_accepting_donations($campaign) {
        if ($campaign->campaign_status !== 'active') {
            return false;
        }
        
        $now = current_time('mysql');
        
        if ($campaign->start_date > $now) {
            return false; // Campaign hasn't started yet
        }
        
        if ($campaign->end_date && $campaign->end_date < $now) {
            return false; // Campaign has ended
        }
        
        // Check if goal is reached (if configured to stop at goal)
        $stop_at_goal = get_option('eds_stop_at_goal', false);
        if ($stop_at_goal && $campaign->current_amount >= $campaign->campaign_goal) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Generate unique transaction ID
     */
    private function generate_transaction_id() {
        return 'EDS_' . date('Ymd') . '_' . wp_generate_password(12, false, false);
    }
    
    /**
     * Update donation status
     */
    public function update_donation_status($donation_id, $status) {
        global $wpdb;
        
        $result = $wpdb->update(
            $wpdb->prefix . 'donations',
            array('payment_status' => $status),
            array('donation_id' => $donation_id),
            array('%s'),
            array('%d')
        );
        
        // Trigger status change actions
        do_action('eds_donation_status_changed', $donation_id, $status);
        
        return $result !== false;
    }
    
    /**
     * Update donation payment information
     */
    private function update_donation_payment_info($donation_id, $payment_result) {
        global $wpdb;
        
        $update_data = array(
            'processor_transaction_id' => $payment_result['transaction_id'],
            'payment_status' => $payment_result['status'],
            'processing_fee' => isset($payment_result['fee']) ? $payment_result['fee'] : 0,
            'net_amount' => isset($payment_result['net_amount']) ? $payment_result['net_amount'] : 0,
        );
        
        if ($payment_result['status'] === 'completed') {
            $update_data['payment_date'] = current_time('mysql');
        }
        
        $wpdb->update(
            $wpdb->prefix . 'donations',
            $update_data,
            array('donation_id' => $donation_id),
            array('%s', '%s', '%f', '%f', '%s'),
            array('%d')
        );
    }
    
    /**
     * Update campaign totals
     */
    private function update_campaign_totals($campaign_id) {
        global $wpdb;
        
        // Calculate totals from successful donations
        $totals = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                SUM(net_amount) as total_amount,
                COUNT(*) as total_donors,
                AVG(net_amount) as average_donation,
                MAX(payment_date) as last_donation
            FROM {$wpdb->prefix}donations 
            WHERE campaign_id = %d AND payment_status = 'completed'",
            $campaign_id
        ));
        
        if ($totals) {
            $wpdb->update(
                $wpdb->prefix . 'donation_campaigns',
                array(
                    'current_amount' => $totals->total_amount ?: 0,
                    'total_donors' => $totals->total_donors ?: 0,
                    'average_donation' => $totals->average_donation ?: 0,
                    'last_donation_date' => $totals->last_donation
                ),
                array('campaign_id' => $campaign_id),
                array('%f', '%d', '%f', '%s'),
                array('%d')
            );
        }
    }
    
    /**
     * Send donation notifications
     */
    private function send_donation_notifications($donation_id) {
        $notification_system = new EDS_Notification_System();
        $notification_system->send_donation_confirmation($donation_id);
        $notification_system->send_admin_notification($donation_id);
    }
    
    /**
     * Generate tax receipt
     */
    private function generate_tax_receipt($donation_id) {
        $receipt_generator = new EDS_Receipt_Generator();
        return $receipt_generator->generate_receipt($donation_id);
    }
    
    /**
     * Get receipt URL
     */
    private function get_receipt_url($donation_id) {
        return add_query_arg(array(
            'eds_action' => 'view_receipt',
            'donation_id' => $donation_id
        ), home_url());
    }
    
    /**
     * Setup recurring donation
     */
    private function setup_recurring_donation($donation_id, $payment_data) {
        $recurring_handler = new EDS_Recurring_Donations();
        return $recurring_handler->create_subscription($donation_id, $payment_data);
    }
    
    /**
     * Get payment processor instance
     */
    private function get_payment_processor($processor_name) {
        $payment_processor = new EDS_Payment_Processor();
        return $payment_processor->get_processor($processor_name);
    }
    
    /**
     * Sanitize donor data
     */
    private function sanitize_donor_data($data) {
        return array(
            'name' => sanitize_text_field($data['donor_name']),
            'email' => sanitize_email($data['donor_email']),
            'phone' => isset($data['donor_phone']) ? sanitize_text_field($data['donor_phone']) : '',
            'message' => isset($data['donor_message']) ? sanitize_textarea_field($data['donor_message']) : '',
            'anonymous' => isset($data['anonymous']),
            'tax_receipt' => isset($data['tax_receipt']),
            'dedication_type' => isset($data['dedication_type']) ? sanitize_text_field($data['dedication_type']) : '',
            'dedication_name' => isset($data['dedication_name']) ? sanitize_text_field($data['dedication_name']) : '',
            'dedication_message' => isset($data['dedication_message']) ? sanitize_textarea_field($data['dedication_message']) : '',
            'notification_email' => isset($data['notification_email']) ? sanitize_email($data['notification_email']) : '',
            'source' => isset($data['source']) ? sanitize_text_field($data['source']) : '',
            'referral' => isset($data['referral']) ? sanitize_text_field($data['referral']) : '',
            'utm_source' => isset($data['utm_source']) ? sanitize_text_field($data['utm_source']) : '',
            'utm_medium' => isset($data['utm_medium']) ? sanitize_text_field($data['utm_medium']) : '',
            'utm_campaign' => isset($data['utm_campaign']) ? sanitize_text_field($data['utm_campaign']) : '',
        );
    }
    
    /**
     * Sanitize payment data
     */
    private function sanitize_payment_data($data) {
        return array(
            'method' => sanitize_text_field($data['payment_method']),
            'processor' => sanitize_text_field($data['payment_processor']),
            'recurring' => isset($data['recurring']),
            'frequency' => isset($data['frequency']) ? sanitize_text_field($data['frequency']) : 'monthly',
        );
    }
    
    /**
     * Detect device type
     */
    private function detect_device_type() {
        if (wp_is_mobile()) {
            return 'mobile';
        }
        
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        if (strpos($user_agent, 'Tablet') !== false || strpos($user_agent, 'iPad') !== false) {
            return 'tablet';
        }
        
        return 'desktop';
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
    
    /**
     * AJAX: Get donation status
     */
    public function ajax_get_donation_status() {
        check_ajax_referer('eds_donation_nonce', 'nonce');
        
        $donation_id = intval($_POST['donation_id']);
        $donation = $this->get_donation($donation_id);
        
        if (!$donation) {
            wp_send_json_error('Donation not found');
        }
        
        wp_send_json_success(array(
            'status' => $donation->payment_status,
            'amount' => $donation->donation_amount,
            'currency' => $donation->currency_code,
            'date' => $donation->payment_date,
            'receipt_url' => $this->get_receipt_url($donation_id)
        ));
    }
    
    /**
     * AJAX: Cancel donation
     */
    public function ajax_cancel_donation() {
        check_ajax_referer('eds_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $donation_id = intval($_POST['donation_id']);
        $reason = sanitize_textarea_field($_POST['reason']);
        
        $result = $this->cancel_donation($donation_id, $reason);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success('Donation cancelled successfully');
    }
    
    /**
     * AJAX: Refund donation
     */
    public function ajax_refund_donation() {
        check_ajax_referer('eds_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $donation_id = intval($_POST['donation_id']);
        $amount = floatval($_POST['refund_amount']);
        $reason = sanitize_textarea_field($_POST['reason']);
        
        $result = $this->refund_donation($donation_id, $amount, $reason);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success('Donation refunded successfully');
    }
    
    /**
     * Get donation details
     */
    public function get_donation($donation_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}donations WHERE donation_id = %d",
            $donation_id
        ));
    }
    
    /**
     * Cancel donation
     */
    public function cancel_donation($donation_id, $reason = '') {
        global $wpdb;
        
        $donation = $this->get_donation($donation_id);
        if (!$donation) {
            return new WP_Error('not_found', 'Donation not found');
        }
        
        if ($donation->payment_status === 'completed') {
            return new WP_Error('cannot_cancel', 'Cannot cancel completed donation. Use refund instead.');
        }
        
        $result = $wpdb->update(
            $wpdb->prefix . 'donations',
            array(
                'payment_status' => 'cancelled',
                'refund_reason' => $reason,
                'refund_date' => current_time('mysql')
            ),
            array('donation_id' => $donation_id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            do_action('eds_donation_cancelled', $donation_id, $reason);
        }
        
        return $result !== false;
    }
    
    /**
     * Refund donation
     */
    public function refund_donation($donation_id, $refund_amount, $reason = '') {
        global $wpdb;
        
        $donation = $this->get_donation($donation_id);
        if (!$donation) {
            return new WP_Error('not_found', 'Donation not found');
        }
        
        if ($donation->payment_status !== 'completed') {
            return new WP_Error('cannot_refund', 'Can only refund completed donations');
        }
        
        if ($refund_amount > $donation->net_amount) {
            return new WP_Error('invalid_amount', 'Refund amount cannot exceed donation amount');
        }
        
        // Process refund through payment processor
        $payment_processor = $this->get_payment_processor($donation->payment_processor);
        $refund_result = $payment_processor->process_refund($donation->processor_transaction_id, $refund_amount);
        
        if (is_wp_error($refund_result)) {
            return $refund_result;
        }
        
        // Update donation record
        $result = $wpdb->update(
            $wpdb->prefix . 'donations',
            array(
                'refund_amount' => $refund_amount,
                'refund_date' => current_time('mysql'),
                'refund_reason' => $reason,
                'payment_status' => $refund_amount >= $donation->net_amount ? 'refunded' : 'partial_refund'
            ),
            array('donation_id' => $donation_id),
            array('%f', '%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            // Update campaign totals
            $this->update_campaign_totals($donation->campaign_id);
            
            do_action('eds_donation_refunded', $donation_id, $refund_amount, $reason);
        }
        
        return $result !== false;
    }
}
