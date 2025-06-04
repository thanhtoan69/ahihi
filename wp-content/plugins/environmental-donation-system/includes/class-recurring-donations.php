<?php
/**
 * Recurring Donations Handler
 * 
 * Manages recurring donation subscriptions, processing, and maintenance
 * 
 * @package Environmental_Donation_System
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class EDS_Recurring_Donations
 */
class EDS_Recurring_Donations {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks and filters
     */
    private function init_hooks() {
        // AJAX handlers
        add_action('wp_ajax_eds_create_subscription', array($this, 'handle_create_subscription'));
        add_action('wp_ajax_nopriv_eds_create_subscription', array($this, 'handle_create_subscription'));
        add_action('wp_ajax_eds_cancel_subscription', array($this, 'handle_cancel_subscription'));
        add_action('wp_ajax_eds_update_subscription', array($this, 'handle_update_subscription'));
        add_action('wp_ajax_eds_pause_subscription', array($this, 'handle_pause_subscription'));
        add_action('wp_ajax_eds_resume_subscription', array($this, 'handle_resume_subscription'));
        
        // Cron handlers
        add_action('eds_process_recurring_donations', array($this, 'process_recurring_donations'));
        add_action('eds_cleanup_failed_subscriptions', array($this, 'cleanup_failed_subscriptions'));
        add_action('eds_send_subscription_reminders', array($this, 'send_subscription_reminders'));
        
        // Payment processor hooks
        add_action('eds_subscription_payment_success', array($this, 'handle_payment_success'), 10, 2);
        add_action('eds_subscription_payment_failed', array($this, 'handle_payment_failed'), 10, 2);
        
        // Schedule cron events
        if (!wp_next_scheduled('eds_process_recurring_donations')) {
            wp_schedule_event(time(), 'hourly', 'eds_process_recurring_donations');
        }
        
        if (!wp_next_scheduled('eds_cleanup_failed_subscriptions')) {
            wp_schedule_event(time(), 'daily', 'eds_cleanup_failed_subscriptions');
        }
        
        if (!wp_next_scheduled('eds_send_subscription_reminders')) {
            wp_schedule_event(time(), 'daily', 'eds_send_subscription_reminders');
        }
    }
    
    /**
     * Create new subscription
     */
    public function create_subscription($data) {
        global $wpdb;
        
        try {
            // Validate subscription data
            $validation = $this->validate_subscription_data($data);
            if (!$validation['valid']) {
                return array(
                    'success' => false,
                    'message' => $validation['message']
                );
            }
            
            // Prepare subscription data
            $subscription_data = array(
                'donor_id' => intval($data['donor_id']),
                'campaign_id' => isset($data['campaign_id']) ? intval($data['campaign_id']) : null,
                'organization_id' => isset($data['organization_id']) ? intval($data['organization_id']) : null,
                'amount' => floatval($data['amount']),
                'currency' => sanitize_text_field($data['currency']),
                'frequency' => sanitize_text_field($data['frequency']),
                'payment_method' => sanitize_text_field($data['payment_method']),
                'payment_token' => sanitize_text_field($data['payment_token']),
                'start_date' => current_time('mysql'),
                'next_payment_date' => $this->calculate_next_payment_date($data['frequency']),
                'status' => 'active',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            );
            
            // Insert subscription
            $result = $wpdb->insert(
                $wpdb->prefix . 'eds_donation_subscriptions',
                $subscription_data,
                array('%d', '%d', '%d', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
            );
            
            if ($result === false) {
                throw new Exception('Failed to create subscription');
            }
            
            $subscription_id = $wpdb->insert_id;
            
            // Create first donation
            $first_donation = $this->create_subscription_donation($subscription_id);
            
            if (!$first_donation['success']) {
                // Rollback subscription creation
                $this->cancel_subscription($subscription_id);
                throw new Exception('Failed to process first donation: ' . $first_donation['message']);
            }
            
            // Log subscription creation
            $this->log_subscription_activity($subscription_id, 'created', 'Subscription created successfully');
            
            // Send confirmation email
            $this->send_subscription_confirmation($subscription_id);
            
            return array(
                'success' => true,
                'subscription_id' => $subscription_id,
                'message' => __('Subscription created successfully', 'environmental-donation-system')
            );
            
        } catch (Exception $e) {
            error_log('EDS Subscription Creation Error: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Cancel subscription
     */
    public function cancel_subscription($subscription_id, $reason = '') {
        global $wpdb;
        
        try {
            // Get subscription details
            $subscription = $this->get_subscription($subscription_id);
            if (!$subscription) {
                throw new Exception('Subscription not found');
            }
            
            // Update subscription status
            $result = $wpdb->update(
                $wpdb->prefix . 'eds_donation_subscriptions',
                array(
                    'status' => 'cancelled',
                    'cancelled_at' => current_time('mysql'),
                    'cancellation_reason' => sanitize_text_field($reason),
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $subscription_id),
                array('%s', '%s', '%s', '%s'),
                array('%d')
            );
            
            if ($result === false) {
                throw new Exception('Failed to cancel subscription');
            }
            
            // Cancel with payment processor
            if (!empty($subscription->payment_token)) {
                $payment_processor = EDS_Payment_Processor::get_processor($subscription->payment_method);
                if ($payment_processor) {
                    $payment_processor->cancel_subscription($subscription->payment_token);
                }
            }
            
            // Log cancellation
            $this->log_subscription_activity($subscription_id, 'cancelled', 'Subscription cancelled: ' . $reason);
            
            // Send cancellation email
            $this->send_subscription_cancellation($subscription_id);
            
            return array(
                'success' => true,
                'message' => __('Subscription cancelled successfully', 'environmental-donation-system')
            );
            
        } catch (Exception $e) {
            error_log('EDS Subscription Cancellation Error: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Update subscription
     */
    public function update_subscription($subscription_id, $data) {
        global $wpdb;
        
        try {
            // Get current subscription
            $subscription = $this->get_subscription($subscription_id);
            if (!$subscription) {
                throw new Exception('Subscription not found');
            }
            
            $update_data = array();
            $update_format = array();
            
            // Update amount
            if (isset($data['amount']) && floatval($data['amount']) > 0) {
                $update_data['amount'] = floatval($data['amount']);
                $update_format[] = '%f';
            }
            
            // Update frequency
            if (isset($data['frequency']) && in_array($data['frequency'], $this->get_valid_frequencies())) {
                $update_data['frequency'] = sanitize_text_field($data['frequency']);
                $update_data['next_payment_date'] = $this->calculate_next_payment_date($data['frequency']);
                $update_format[] = '%s';
                $update_format[] = '%s';
            }
            
            // Update payment method
            if (isset($data['payment_method']) && isset($data['payment_token'])) {
                $update_data['payment_method'] = sanitize_text_field($data['payment_method']);
                $update_data['payment_token'] = sanitize_text_field($data['payment_token']);
                $update_format[] = '%s';
                $update_format[] = '%s';
            }
            
            if (empty($update_data)) {
                throw new Exception('No valid update data provided');
            }
            
            $update_data['updated_at'] = current_time('mysql');
            $update_format[] = '%s';
            
            // Update subscription
            $result = $wpdb->update(
                $wpdb->prefix . 'eds_donation_subscriptions',
                $update_data,
                array('id' => $subscription_id),
                $update_format,
                array('%d')
            );
            
            if ($result === false) {
                throw new Exception('Failed to update subscription');
            }
            
            // Log update
            $changes = array_keys($update_data);
            $this->log_subscription_activity($subscription_id, 'updated', 'Subscription updated: ' . implode(', ', $changes));
            
            // Send update confirmation
            $this->send_subscription_update_confirmation($subscription_id, $changes);
            
            return array(
                'success' => true,
                'message' => __('Subscription updated successfully', 'environmental-donation-system')
            );
            
        } catch (Exception $e) {
            error_log('EDS Subscription Update Error: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Pause subscription
     */
    public function pause_subscription($subscription_id, $reason = '') {
        global $wpdb;
        
        try {
            $result = $wpdb->update(
                $wpdb->prefix . 'eds_donation_subscriptions',
                array(
                    'status' => 'paused',
                    'paused_at' => current_time('mysql'),
                    'pause_reason' => sanitize_text_field($reason),
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $subscription_id),
                array('%s', '%s', '%s', '%s'),
                array('%d')
            );
            
            if ($result === false) {
                throw new Exception('Failed to pause subscription');
            }
            
            $this->log_subscription_activity($subscription_id, 'paused', 'Subscription paused: ' . $reason);
            
            return array(
                'success' => true,
                'message' => __('Subscription paused successfully', 'environmental-donation-system')
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Resume subscription
     */
    public function resume_subscription($subscription_id) {
        global $wpdb;
        
        try {
            $subscription = $this->get_subscription($subscription_id);
            if (!$subscription || $subscription->status !== 'paused') {
                throw new Exception('Subscription not found or not paused');
            }
            
            $result = $wpdb->update(
                $wpdb->prefix . 'eds_donation_subscriptions',
                array(
                    'status' => 'active',
                    'next_payment_date' => $this->calculate_next_payment_date($subscription->frequency),
                    'paused_at' => null,
                    'pause_reason' => null,
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $subscription_id),
                array('%s', '%s', null, null, '%s'),
                array('%d')
            );
            
            if ($result === false) {
                throw new Exception('Failed to resume subscription');
            }
            
            $this->log_subscription_activity($subscription_id, 'resumed', 'Subscription resumed');
            
            return array(
                'success' => true,
                'message' => __('Subscription resumed successfully', 'environmental-donation-system')
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Process recurring donations (cron job)
     */
    public function process_recurring_donations() {
        global $wpdb;
        
        try {
            // Get subscriptions due for payment
            $due_subscriptions = $wpdb->get_results($wpdb->prepare("
                SELECT * FROM {$wpdb->prefix}eds_donation_subscriptions 
                WHERE status = 'active' 
                AND next_payment_date <= %s
                ORDER BY next_payment_date ASC
                LIMIT 100
            ", current_time('mysql')));
            
            $processed = 0;
            $failed = 0;
            
            foreach ($due_subscriptions as $subscription) {
                $result = $this->create_subscription_donation($subscription->id);
                
                if ($result['success']) {
                    $processed++;
                    // Update next payment date
                    $this->update_next_payment_date($subscription->id, $subscription->frequency);
                } else {
                    $failed++;
                    $this->handle_subscription_failure($subscription->id, $result['message']);
                }
                
                // Add small delay to prevent overwhelming payment processors
                usleep(100000); // 0.1 second
            }
            
            // Log processing results
            error_log("EDS Recurring Donations Processed: {$processed} successful, {$failed} failed");
            
        } catch (Exception $e) {
            error_log('EDS Recurring Donations Processing Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Create donation from subscription
     */
    private function create_subscription_donation($subscription_id) {
        try {
            $subscription = $this->get_subscription($subscription_id);
            if (!$subscription) {
                throw new Exception('Subscription not found');
            }
            
            // Create donation data
            $donation_data = array(
                'donor_id' => $subscription->donor_id,
                'campaign_id' => $subscription->campaign_id,
                'organization_id' => $subscription->organization_id,
                'amount' => $subscription->amount,
                'currency' => $subscription->currency,
                'payment_method' => $subscription->payment_method,
                'payment_token' => $subscription->payment_token,
                'subscription_id' => $subscription_id,
                'type' => 'recurring'
            );
            
            // Process donation through donation manager
            $donation_manager = EDS_Donation_Manager::get_instance();
            return $donation_manager->create_donation($donation_data);
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Handle subscription payment failure
     */
    private function handle_subscription_failure($subscription_id, $error_message) {
        global $wpdb;
        
        // Increment failure count
        $wpdb->query($wpdb->prepare("
            UPDATE {$wpdb->prefix}eds_donation_subscriptions 
            SET failure_count = failure_count + 1,
                last_failure_at = %s,
                last_failure_reason = %s,
                updated_at = %s
            WHERE id = %d
        ", current_time('mysql'), $error_message, current_time('mysql'), $subscription_id));
        
        // Get updated subscription
        $subscription = $this->get_subscription($subscription_id);
        
        // Cancel subscription after too many failures
        if ($subscription && $subscription->failure_count >= 3) {
            $this->cancel_subscription($subscription_id, 'Multiple payment failures');
        } else {
            // Schedule retry
            $this->schedule_retry($subscription_id);
        }
        
        $this->log_subscription_activity($subscription_id, 'payment_failed', $error_message);
    }
    
    /**
     * Get subscription by ID
     */
    public function get_subscription($subscription_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}eds_donation_subscriptions 
            WHERE id = %d
        ", $subscription_id));
    }
    
    /**
     * Get subscriptions by donor
     */
    public function get_donor_subscriptions($donor_id, $status = null) {
        global $wpdb;
        
        $where_clause = "WHERE donor_id = %d";
        $params = array($donor_id);
        
        if ($status) {
            $where_clause .= " AND status = %s";
            $params[] = $status;
        }
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT s.*, 
                   c.title as campaign_title,
                   o.name as organization_name
            FROM {$wpdb->prefix}eds_donation_subscriptions s
            LEFT JOIN {$wpdb->prefix}eds_donation_campaigns c ON s.campaign_id = c.id
            LEFT JOIN {$wpdb->prefix}eds_donation_organizations o ON s.organization_id = o.id
            {$where_clause}
            ORDER BY s.created_at DESC
        ", $params));
    }
    
    /**
     * Validate subscription data
     */
    private function validate_subscription_data($data) {
        // Check required fields
        $required_fields = array('donor_id', 'amount', 'currency', 'frequency', 'payment_method');
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return array(
                    'valid' => false,
                    'message' => sprintf(__('Missing required field: %s', 'environmental-donation-system'), $field)
                );
            }
        }
        
        // Validate amount
        if (floatval($data['amount']) <= 0) {
            return array(
                'valid' => false,
                'message' => __('Invalid donation amount', 'environmental-donation-system')
            );
        }
        
        // Validate frequency
        if (!in_array($data['frequency'], $this->get_valid_frequencies())) {
            return array(
                'valid' => false,
                'message' => __('Invalid frequency', 'environmental-donation-system')
            );
        }
        
        // Must have either campaign or organization
        if (empty($data['campaign_id']) && empty($data['organization_id'])) {
            return array(
                'valid' => false,
                'message' => __('Must specify either campaign or organization', 'environmental-donation-system')
            );
        }
        
        return array('valid' => true);
    }
    
    /**
     * Get valid subscription frequencies
     */
    private function get_valid_frequencies() {
        return array('weekly', 'monthly', 'quarterly', 'yearly');
    }
    
    /**
     * Calculate next payment date
     */
    private function calculate_next_payment_date($frequency, $from_date = null) {
        $from_date = $from_date ?: current_time('mysql');
        $date = new DateTime($from_date);
        
        switch ($frequency) {
            case 'weekly':
                $date->add(new DateInterval('P1W'));
                break;
            case 'monthly':
                $date->add(new DateInterval('P1M'));
                break;
            case 'quarterly':
                $date->add(new DateInterval('P3M'));
                break;
            case 'yearly':
                $date->add(new DateInterval('P1Y'));
                break;
        }
        
        return $date->format('Y-m-d H:i:s');
    }
    
    /**
     * Update next payment date
     */
    private function update_next_payment_date($subscription_id, $frequency) {
        global $wpdb;
        
        $wpdb->update(
            $wpdb->prefix . 'eds_donation_subscriptions',
            array(
                'next_payment_date' => $this->calculate_next_payment_date($frequency),
                'updated_at' => current_time('mysql')
            ),
            array('id' => $subscription_id),
            array('%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * Log subscription activity
     */
    private function log_subscription_activity($subscription_id, $action, $message) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'eds_subscription_logs',
            array(
                'subscription_id' => $subscription_id,
                'action' => $action,
                'message' => $message,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Send subscription confirmation email
     */
    private function send_subscription_confirmation($subscription_id) {
        $subscription = $this->get_subscription($subscription_id);
        if (!$subscription) return;
        
        $donor = get_userdata($subscription->donor_id);
        if (!$donor) return;
        
        $subject = __('Recurring Donation Confirmation', 'environmental-donation-system');
        $message = sprintf(
            __('Thank you for setting up a recurring donation of %s %s %s.', 'environmental-donation-system'),
            $subscription->currency,
            number_format($subscription->amount, 2),
            $subscription->frequency
        );
        
        wp_mail($donor->user_email, $subject, $message);
    }
    
    /**
     * Send subscription cancellation email
     */
    private function send_subscription_cancellation($subscription_id) {
        $subscription = $this->get_subscription($subscription_id);
        if (!$subscription) return;
        
        $donor = get_userdata($subscription->donor_id);
        if (!$donor) return;
        
        $subject = __('Recurring Donation Cancelled', 'environmental-donation-system');
        $message = __('Your recurring donation has been cancelled as requested.', 'environmental-donation-system');
        
        wp_mail($donor->user_email, $subject, $message);
    }
    
    /**
     * Send subscription update confirmation
     */
    private function send_subscription_update_confirmation($subscription_id, $changes) {
        $subscription = $this->get_subscription($subscription_id);
        if (!$subscription) return;
        
        $donor = get_userdata($subscription->donor_id);
        if (!$donor) return;
        
        $subject = __('Recurring Donation Updated', 'environmental-donation-system');
        $message = sprintf(
            __('Your recurring donation has been updated. Changes: %s', 'environmental-donation-system'),
            implode(', ', $changes)
        );
        
        wp_mail($donor->user_email, $subject, $message);
    }
    
    /**
     * Schedule retry for failed subscription
     */
    private function schedule_retry($subscription_id) {
        // Schedule retry in 24 hours
        wp_schedule_single_event(
            time() + (24 * HOUR_IN_SECONDS),
            'eds_retry_subscription_payment',
            array($subscription_id)
        );
    }
    
    /**
     * Clean up failed subscriptions (cron job)
     */
    public function cleanup_failed_subscriptions() {
        global $wpdb;
        
        // Cancel subscriptions with multiple failures that haven't been processed in 30 days
        $wpdb->query($wpdb->prepare("
            UPDATE {$wpdb->prefix}eds_donation_subscriptions 
            SET status = 'cancelled',
                cancelled_at = %s,
                cancellation_reason = 'Multiple payment failures'
            WHERE status = 'active'
            AND failure_count >= 5
            AND last_failure_at < %s
        ", current_time('mysql'), date('Y-m-d H:i:s', time() - (30 * DAY_IN_SECONDS))));
    }
    
    /**
     * Send subscription reminders (cron job)
     */
    public function send_subscription_reminders() {
        global $wpdb;
        
        // Get subscriptions due for reminder (3 days before next payment)
        $reminder_date = date('Y-m-d H:i:s', time() + (3 * DAY_IN_SECONDS));
        
        $subscriptions = $wpdb->get_results($wpdb->prepare("
            SELECT s.*, u.user_email, u.display_name
            FROM {$wpdb->prefix}eds_donation_subscriptions s
            JOIN {$wpdb->prefix}users u ON s.donor_id = u.ID
            WHERE s.status = 'active'
            AND s.next_payment_date <= %s
            AND s.next_payment_date > %s
            AND (s.last_reminder_sent IS NULL OR s.last_reminder_sent < %s)
        ", $reminder_date, current_time('mysql'), date('Y-m-d H:i:s', time() - (7 * DAY_IN_SECONDS))));
        
        foreach ($subscriptions as $subscription) {
            $this->send_payment_reminder($subscription);
            
            // Update reminder sent timestamp
            $wpdb->update(
                $wpdb->prefix . 'eds_donation_subscriptions',
                array('last_reminder_sent' => current_time('mysql')),
                array('id' => $subscription->id),
                array('%s'),
                array('%d')
            );
        }
    }
    
    /**
     * Send payment reminder email
     */
    private function send_payment_reminder($subscription) {
        $subject = __('Upcoming Recurring Donation', 'environmental-donation-system');
        $message = sprintf(
            __('Hello %s, your recurring donation of %s %s is scheduled for %s.', 'environmental-donation-system'),
            $subscription->display_name,
            $subscription->currency,
            number_format($subscription->amount, 2),
            date('F j, Y', strtotime($subscription->next_payment_date))
        );
        
        wp_mail($subscription->user_email, $subject, $message);
    }
    
    /**
     * AJAX handler: Create subscription
     */
    public function handle_create_subscription() {
        check_ajax_referer('eds_ajax_nonce', 'nonce');
        
        $data = array(
            'donor_id' => intval($_POST['donor_id']),
            'campaign_id' => isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : null,
            'organization_id' => isset($_POST['organization_id']) ? intval($_POST['organization_id']) : null,
            'amount' => floatval($_POST['amount']),
            'currency' => sanitize_text_field($_POST['currency']),
            'frequency' => sanitize_text_field($_POST['frequency']),
            'payment_method' => sanitize_text_field($_POST['payment_method']),
            'payment_token' => sanitize_text_field($_POST['payment_token'])
        );
        
        $result = $this->create_subscription($data);
        wp_send_json($result);
    }
    
    /**
     * AJAX handler: Cancel subscription
     */
    public function handle_cancel_subscription() {
        check_ajax_referer('eds_ajax_nonce', 'nonce');
        
        $subscription_id = intval($_POST['subscription_id']);
        $reason = sanitize_text_field($_POST['reason']);
        
        $result = $this->cancel_subscription($subscription_id, $reason);
        wp_send_json($result);
    }
    
    /**
     * AJAX handler: Update subscription
     */
    public function handle_update_subscription() {
        check_ajax_referer('eds_ajax_nonce', 'nonce');
        
        $subscription_id = intval($_POST['subscription_id']);
        $data = array();
        
        if (isset($_POST['amount'])) {
            $data['amount'] = floatval($_POST['amount']);
        }
        
        if (isset($_POST['frequency'])) {
            $data['frequency'] = sanitize_text_field($_POST['frequency']);
        }
        
        if (isset($_POST['payment_method']) && isset($_POST['payment_token'])) {
            $data['payment_method'] = sanitize_text_field($_POST['payment_method']);
            $data['payment_token'] = sanitize_text_field($_POST['payment_token']);
        }
        
        $result = $this->update_subscription($subscription_id, $data);
        wp_send_json($result);
    }
    
    /**
     * AJAX handler: Pause subscription
     */
    public function handle_pause_subscription() {
        check_ajax_referer('eds_ajax_nonce', 'nonce');
        
        $subscription_id = intval($_POST['subscription_id']);
        $reason = sanitize_text_field($_POST['reason']);
        
        $result = $this->pause_subscription($subscription_id, $reason);
        wp_send_json($result);
    }
    
    /**
     * AJAX handler: Resume subscription
     */
    public function handle_resume_subscription() {
        check_ajax_referer('eds_ajax_nonce', 'nonce');
        
        $subscription_id = intval($_POST['subscription_id']);
        
        $result = $this->resume_subscription($subscription_id);
        wp_send_json($result);
    }
    
    /**
     * Handle successful subscription payment
     */
    public function handle_payment_success($subscription_id, $payment_data) {
        global $wpdb;
        
        // Reset failure count
        $wpdb->update(
            $wpdb->prefix . 'eds_donation_subscriptions',
            array(
                'failure_count' => 0,
                'last_failure_at' => null,
                'last_failure_reason' => null,
                'last_payment_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('id' => $subscription_id),
            array('%d', null, null, '%s', '%s'),
            array('%d')
        );
        
        $this->log_subscription_activity($subscription_id, 'payment_success', 'Payment processed successfully');
    }
    
    /**
     * Handle failed subscription payment
     */
    public function handle_payment_failed($subscription_id, $error_message) {
        $this->handle_subscription_failure($subscription_id, $error_message);
    }
}
