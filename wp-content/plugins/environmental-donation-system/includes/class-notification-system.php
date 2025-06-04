<?php
/**
 * Notification System
 * 
 * Handles email notifications and messaging for donation activities
 * 
 * @package Environmental_Donation_System
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class EDS_Notification_System
 */
class EDS_Notification_System {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Email templates
     */
    private $email_templates = array();
    
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
        $this->init_email_templates();
        $this->init_hooks();
    }
    
    /**
     * Initialize email templates
     */
    private function init_email_templates() {
        $this->email_templates = array(
            'donation_confirmation' => array(
                'subject' => __('Thank you for your donation!', 'environmental-donation-system'),
                'template' => 'donation-confirmation',
                'default_content' => $this->get_default_donation_confirmation_content()
            ),
            'donation_receipt' => array(
                'subject' => __('Your donation receipt', 'environmental-donation-system'),
                'template' => 'donation-receipt',
                'default_content' => $this->get_default_receipt_content()
            ),
            'recurring_confirmation' => array(
                'subject' => __('Recurring donation set up successfully', 'environmental-donation-system'),
                'template' => 'recurring-confirmation',
                'default_content' => $this->get_default_recurring_confirmation_content()
            ),
            'recurring_payment' => array(
                'subject' => __('Monthly donation processed', 'environmental-donation-system'),
                'template' => 'recurring-payment',
                'default_content' => $this->get_default_recurring_payment_content()
            ),
            'recurring_failed' => array(
                'subject' => __('Recurring donation payment failed', 'environmental-donation-system'),
                'template' => 'recurring-failed',
                'default_content' => $this->get_default_recurring_failed_content()
            ),
            'campaign_milestone' => array(
                'subject' => __('Campaign milestone reached!', 'environmental-donation-system'),
                'template' => 'campaign-milestone',
                'default_content' => $this->get_default_milestone_content()
            ),
            'campaign_complete' => array(
                'subject' => __('Campaign successfully completed!', 'environmental-donation-system'),
                'template' => 'campaign-complete',
                'default_content' => $this->get_default_campaign_complete_content()
            ),
            'impact_update' => array(
                'subject' => __('Your environmental impact update', 'environmental-donation-system'),
                'template' => 'impact-update',
                'default_content' => $this->get_default_impact_update_content()
            ),
            'admin_new_donation' => array(
                'subject' => __('[{{site_name}}] New donation received', 'environmental-donation-system'),
                'template' => 'admin-new-donation',
                'default_content' => $this->get_default_admin_donation_content()
            ),
            'admin_campaign_alert' => array(
                'subject' => __('[{{site_name}}] Campaign alert', 'environmental-donation-system'),
                'template' => 'admin-campaign-alert',
                'default_content' => $this->get_default_admin_campaign_alert_content()
            )
        );
        
        // Allow customization through filters
        $this->email_templates = apply_filters('eds_email_templates', $this->email_templates);
    }
    
    /**
     * Initialize hooks and filters
     */
    private function init_hooks() {
        // Donation events
        add_action('eds_donation_completed', array($this, 'send_donation_confirmation'), 10, 2);
        add_action('eds_donation_completed', array($this, 'notify_admin_new_donation'), 10, 2);
        add_action('eds_donation_refunded', array($this, 'send_refund_notification'), 10, 2);
        
        // Subscription events
        add_action('eds_subscription_created', array($this, 'send_recurring_confirmation'), 10, 2);
        add_action('eds_subscription_payment_success', array($this, 'send_recurring_payment_notification'), 10, 2);
        add_action('eds_subscription_payment_failed', array($this, 'send_recurring_failed_notification'), 10, 2);
        add_action('eds_subscription_cancelled', array($this, 'send_subscription_cancellation'), 10, 2);
        
        // Campaign events
        add_action('eds_campaign_milestone_reached', array($this, 'send_milestone_notifications'), 10, 3);
        add_action('eds_campaign_completed', array($this, 'send_campaign_completion_notifications'), 10, 2);
        add_action('eds_campaign_deadline_approaching', array($this, 'send_deadline_reminders'), 10, 2);
        
        // Impact updates
        add_action('eds_monthly_impact_report', array($this, 'send_impact_updates'), 10, 1);
        
        // Admin notifications
        add_action('eds_campaign_low_funding', array($this, 'notify_admin_low_funding'), 10, 2);
        add_action('eds_payment_processor_error', array($this, 'notify_admin_payment_error'), 10, 2);
        
        // AJAX handlers
        add_action('wp_ajax_eds_test_email', array($this, 'handle_test_email'));
        add_action('wp_ajax_eds_update_email_template', array($this, 'handle_update_email_template'));
        add_action('wp_ajax_eds_preview_email', array($this, 'handle_preview_email'));
        
        // Cron jobs
        add_action('eds_send_scheduled_notifications', array($this, 'send_scheduled_notifications'));
        add_action('eds_cleanup_notification_logs', array($this, 'cleanup_notification_logs'));
        
        // Schedule cron events
        if (!wp_next_scheduled('eds_send_scheduled_notifications')) {
            wp_schedule_event(time(), 'hourly', 'eds_send_scheduled_notifications');
        }
        
        if (!wp_next_scheduled('eds_cleanup_notification_logs')) {
            wp_schedule_event(time(), 'weekly', 'eds_cleanup_notification_logs');
        }
        
        // Email customization
        add_filter('wp_mail_content_type', array($this, 'set_html_mail_content_type'));
        add_filter('wp_mail_from', array($this, 'set_mail_from'));
        add_filter('wp_mail_from_name', array($this, 'set_mail_from_name'));
    }
    
    /**
     * Send donation confirmation email
     */
    public function send_donation_confirmation($donation_id, $donation_data) {
        try {
            $donor = get_userdata($donation_data['donor_id']);
            if (!$donor) return;
            
            // Get donation details
            $donation = $this->get_donation_details($donation_id);
            if (!$donation) return;
            
            // Prepare template variables
            $variables = array(
                'donor_name' => $donor->display_name,
                'donation_amount' => number_format($donation['amount'], 2),
                'donation_currency' => $donation['currency'],
                'donation_date' => date('F j, Y', strtotime($donation['created_at'])),
                'campaign_title' => $donation['campaign_title'] ?? '',
                'organization_name' => $donation['organization_name'] ?? '',
                'receipt_url' => $this->get_receipt_url($donation_id),
                'impact_summary' => $this->get_donation_impact_summary($donation_id),
                'site_name' => get_bloginfo('name'),
                'site_url' => home_url()
            );
            
            $this->send_email(
                $donor->user_email,
                'donation_confirmation',
                $variables
            );
            
            // Log notification
            $this->log_notification($donation_id, 'donation_confirmation', $donor->user_email, 'sent');
            
        } catch (Exception $e) {
            error_log('EDS Donation Confirmation Error: ' . $e->getMessage());
            $this->log_notification($donation_id, 'donation_confirmation', $donor->user_email ?? '', 'failed', $e->getMessage());
        }
    }
    
    /**
     * Send recurring donation confirmation
     */
    public function send_recurring_confirmation($subscription_id, $subscription_data) {
        try {
            $donor = get_userdata($subscription_data['donor_id']);
            if (!$donor) return;
            
            $subscription = $this->get_subscription_details($subscription_id);
            if (!$subscription) return;
            
            $variables = array(
                'donor_name' => $donor->display_name,
                'subscription_amount' => number_format($subscription['amount'], 2),
                'subscription_currency' => $subscription['currency'],
                'subscription_frequency' => $subscription['frequency'],
                'next_payment_date' => date('F j, Y', strtotime($subscription['next_payment_date'])),
                'campaign_title' => $subscription['campaign_title'] ?? '',
                'organization_name' => $subscription['organization_name'] ?? '',
                'manage_url' => $this->get_subscription_manage_url($subscription_id),
                'site_name' => get_bloginfo('name'),
                'site_url' => home_url()
            );
            
            $this->send_email(
                $donor->user_email,
                'recurring_confirmation',
                $variables
            );
            
            $this->log_notification($subscription_id, 'recurring_confirmation', $donor->user_email, 'sent');
            
        } catch (Exception $e) {
            error_log('EDS Recurring Confirmation Error: ' . $e->getMessage());
            $this->log_notification($subscription_id, 'recurring_confirmation', $donor->user_email ?? '', 'failed', $e->getMessage());
        }
    }
    
    /**
     * Send recurring payment notification
     */
    public function send_recurring_payment_notification($subscription_id, $payment_data) {
        try {
            $subscription = $this->get_subscription_details($subscription_id);
            if (!$subscription) return;
            
            $donor = get_userdata($subscription['donor_id']);
            if (!$donor) return;
            
            $variables = array(
                'donor_name' => $donor->display_name,
                'payment_amount' => number_format($payment_data['amount'], 2),
                'payment_currency' => $payment_data['currency'],
                'payment_date' => date('F j, Y'),
                'next_payment_date' => date('F j, Y', strtotime($subscription['next_payment_date'])),
                'campaign_title' => $subscription['campaign_title'] ?? '',
                'organization_name' => $subscription['organization_name'] ?? '',
                'receipt_url' => $this->get_receipt_url($payment_data['donation_id']),
                'manage_url' => $this->get_subscription_manage_url($subscription_id),
                'site_name' => get_bloginfo('name')
            );
            
            $this->send_email(
                $donor->user_email,
                'recurring_payment',
                $variables
            );
            
            $this->log_notification($subscription_id, 'recurring_payment', $donor->user_email, 'sent');
            
        } catch (Exception $e) {
            error_log('EDS Recurring Payment Notification Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Send campaign milestone notifications
     */
    public function send_milestone_notifications($campaign_id, $milestone_percentage, $current_amount) {
        try {
            // Get campaign donors
            $donors = $this->get_campaign_donors($campaign_id);
            
            // Get campaign details
            $campaign = $this->get_campaign_details($campaign_id);
            if (!$campaign) return;
            
            $variables = array(
                'campaign_title' => $campaign['title'],
                'milestone_percentage' => $milestone_percentage,
                'current_amount' => number_format($current_amount, 2),
                'goal_amount' => number_format($campaign['goal_amount'], 2),
                'currency' => $campaign['currency'],
                'campaign_url' => get_permalink($campaign_id),
                'impact_summary' => $this->get_campaign_impact_summary($campaign_id),
                'site_name' => get_bloginfo('name')
            );
            
            foreach ($donors as $donor) {
                $personal_variables = array_merge($variables, array(
                    'donor_name' => $donor['name'],
                    'donor_total_donated' => number_format($donor['total_donated'], 2)
                ));
                
                $this->send_email(
                    $donor['email'],
                    'campaign_milestone',
                    $personal_variables
                );
            }
            
            $this->log_notification($campaign_id, 'campaign_milestone', 'multiple', 'sent', count($donors) . ' emails sent');
            
        } catch (Exception $e) {
            error_log('EDS Milestone Notification Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Send campaign completion notifications
     */
    public function send_campaign_completion_notifications($campaign_id, $final_amount) {
        try {
            $donors = $this->get_campaign_donors($campaign_id);
            $campaign = $this->get_campaign_details($campaign_id);
            
            if (!$campaign) return;
            
            $variables = array(
                'campaign_title' => $campaign['title'],
                'final_amount' => number_format($final_amount, 2),
                'goal_amount' => number_format($campaign['goal_amount'], 2),
                'currency' => $campaign['currency'],
                'total_donors' => count($donors),
                'impact_summary' => $this->get_campaign_impact_summary($campaign_id),
                'completion_date' => date('F j, Y'),
                'site_name' => get_bloginfo('name')
            );
            
            foreach ($donors as $donor) {
                $personal_variables = array_merge($variables, array(
                    'donor_name' => $donor['name'],
                    'donor_total_donated' => number_format($donor['total_donated'], 2)
                ));
                
                $this->send_email(
                    $donor['email'],
                    'campaign_complete',
                    $personal_variables
                );
            }
            
            $this->log_notification($campaign_id, 'campaign_complete', 'multiple', 'sent', count($donors) . ' emails sent');
            
        } catch (Exception $e) {
            error_log('EDS Campaign Completion Notification Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Send impact updates to donors
     */
    public function send_impact_updates($period = 'monthly') {
        try {
            // Get active donors from last period
            $donors = $this->get_active_donors($period);
            
            foreach ($donors as $donor) {
                $impact_data = $this->get_donor_impact_summary($donor['id'], $period);
                
                $variables = array(
                    'donor_name' => $donor['name'],
                    'period' => $period,
                    'total_donated' => number_format($donor['period_donated'], 2),
                    'impact_summary' => $impact_data,
                    'campaigns_supported' => $donor['campaigns_count'],
                    'site_name' => get_bloginfo('name')
                );
                
                $this->send_email(
                    $donor['email'],
                    'impact_update',
                    $variables
                );
            }
            
            $this->log_notification(0, 'impact_update', 'multiple', 'sent', count($donors) . ' updates sent');
            
        } catch (Exception $e) {
            error_log('EDS Impact Update Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Notify admin of new donation
     */
    public function notify_admin_new_donation($donation_id, $donation_data) {
        $admin_email = get_option('admin_email');
        if (!$admin_email) return;
        
        $donor = get_userdata($donation_data['donor_id']);
        $donation = $this->get_donation_details($donation_id);
        
        $variables = array(
            'donation_id' => $donation_id,
            'donor_name' => $donor ? $donor->display_name : 'Unknown',
            'donor_email' => $donor ? $donor->user_email : 'Unknown',
            'donation_amount' => number_format($donation_data['amount'], 2),
            'donation_currency' => $donation_data['currency'],
            'payment_method' => $donation_data['payment_method'],
            'campaign_title' => $donation['campaign_title'] ?? 'General Donation',
            'organization_name' => $donation['organization_name'] ?? '',
            'donation_url' => admin_url('admin.php?page=eds-donations&donation_id=' . $donation_id),
            'site_name' => get_bloginfo('name')
        );
        
        $this->send_email(
            $admin_email,
            'admin_new_donation',
            $variables
        );
    }
    
    /**
     * Send email using template
     */
    private function send_email($to, $template_key, $variables = array()) {
        if (!isset($this->email_templates[$template_key])) {
            throw new Exception("Email template '{$template_key}' not found");
        }
        
        $template = $this->email_templates[$template_key];
        
        // Get custom template content or use default
        $subject = get_option("eds_email_subject_{$template_key}", $template['subject']);
        $content = get_option("eds_email_content_{$template_key}", $template['default_content']);
        
        // Replace variables in subject and content
        $subject = $this->replace_variables($subject, $variables);
        $content = $this->replace_variables($content, $variables);
        
        // Add email wrapper
        $content = $this->wrap_email_content($content, $variables);
        
        // Send email
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $result = wp_mail($to, $subject, $content, $headers);
        
        if (!$result) {
            throw new Exception('Failed to send email');
        }
        
        return true;
    }
    
    /**
     * Replace variables in email content
     */
    private function replace_variables($content, $variables) {
        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        
        // Replace common site variables
        $content = str_replace('{{site_name}}', get_bloginfo('name'), $content);
        $content = str_replace('{{site_url}}', home_url(), $content);
        $content = str_replace('{{current_date}}', date('F j, Y'), $content);
        
        return $content;
    }
    
    /**
     * Wrap email content with HTML template
     */
    private function wrap_email_content($content, $variables = array()) {
        $logo_url = get_option('eds_email_logo_url', '');
        $site_name = get_bloginfo('name');
        $primary_color = get_option('eds_email_primary_color', '#4CAF50');
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html($site_name); ?></title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .email-container { max-width: 600px; margin: 0 auto; background: #ffffff; }
                .email-header { background: <?php echo esc_attr($primary_color); ?>; color: white; padding: 20px; text-align: center; }
                .email-logo { max-height: 60px; margin-bottom: 10px; }
                .email-content { padding: 30px; }
                .email-footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .button { display: inline-block; padding: 12px 24px; background: <?php echo esc_attr($primary_color); ?>; color: white; text-decoration: none; border-radius: 4px; margin: 10px 0; }
                .impact-summary { background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 4px; }
                .impact-item { margin: 5px 0; }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="email-header">
                    <?php if ($logo_url): ?>
                        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($site_name); ?>" class="email-logo">
                    <?php endif; ?>
                    <h1><?php echo esc_html($site_name); ?></h1>
                </div>
                <div class="email-content">
                    <?php echo wpautop($content); ?>
                </div>
                <div class="email-footer">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo esc_html($site_name); ?>. <?php _e('All rights reserved.', 'environmental-donation-system'); ?></p>
                    <p>
                        <a href="<?php echo home_url(); ?>"><?php _e('Visit our website', 'environmental-donation-system'); ?></a> |
                        <a href="<?php echo home_url('/contact'); ?>"><?php _e('Contact us', 'environmental-donation-system'); ?></a>
                    </p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get donation details for email
     */
    private function get_donation_details($donation_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT d.*, c.title as campaign_title, o.name as organization_name
            FROM {$wpdb->prefix}eds_donations d
            LEFT JOIN {$wpdb->prefix}eds_donation_campaigns c ON d.campaign_id = c.id
            LEFT JOIN {$wpdb->prefix}eds_donation_organizations o ON d.organization_id = o.id
            WHERE d.id = %d
        ", $donation_id), ARRAY_A);
    }
    
    /**
     * Get subscription details for email
     */
    private function get_subscription_details($subscription_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT s.*, c.title as campaign_title, o.name as organization_name
            FROM {$wpdb->prefix}eds_donation_subscriptions s
            LEFT JOIN {$wpdb->prefix}eds_donation_campaigns c ON s.campaign_id = c.id
            LEFT JOIN {$wpdb->prefix}eds_donation_organizations o ON s.organization_id = o.id
            WHERE s.id = %d
        ", $subscription_id), ARRAY_A);
    }
    
    /**
     * Get campaign details for email
     */
    private function get_campaign_details($campaign_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}eds_donation_campaigns 
            WHERE id = %d
        ", $campaign_id), ARRAY_A);
    }
    
    /**
     * Get campaign donors
     */
    private function get_campaign_donors($campaign_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT u.ID as id, u.display_name as name, u.user_email as email,
                   SUM(d.amount) as total_donated
            FROM {$wpdb->prefix}eds_donations d
            JOIN {$wpdb->prefix}users u ON d.donor_id = u.ID
            WHERE d.campaign_id = %d AND d.status = 'completed'
            GROUP BY u.ID
            ORDER BY total_donated DESC
        ", $campaign_id), ARRAY_A);
    }
    
    /**
     * Get active donors for period
     */
    private function get_active_donors($period = 'monthly') {
        global $wpdb;
        
        $date_interval = $period === 'weekly' ? '1 WEEK' : ($period === 'yearly' ? '1 YEAR' : '1 MONTH');
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT u.ID as id, u.display_name as name, u.user_email as email,
                   SUM(d.amount) as period_donated,
                   COUNT(DISTINCT d.campaign_id) as campaigns_count
            FROM {$wpdb->prefix}eds_donations d
            JOIN {$wpdb->prefix}users u ON d.donor_id = u.ID
            WHERE d.created_at >= DATE_SUB(NOW(), INTERVAL {$date_interval})
            AND d.status = 'completed'
            GROUP BY u.ID
            HAVING period_donated > 0
            ORDER BY period_donated DESC
        "), ARRAY_A);
    }
    
    /**
     * Get donation impact summary for email
     */
    private function get_donation_impact_summary($donation_id) {
        $impact_tracker = EDS_Impact_Tracker::get_instance();
        $impacts = $impact_tracker->get_donation_impact($donation_id);
        
        if (empty($impacts)) {
            return '<p>' . __('Impact data will be calculated shortly.', 'environmental-donation-system') . '</p>';
        }
        
        $output = '<div class="impact-summary">';
        $output .= '<h3>' . __('Your Environmental Impact:', 'environmental-donation-system') . '</h3>';
        
        foreach ($impacts as $impact) {
            $output .= sprintf(
                '<div class="impact-item">• %s: %s %s</div>',
                esc_html($impact['metric_name']),
                number_format($impact['impact_value'], 2),
                esc_html($impact['unit'])
            );
        }
        
        $output .= '</div>';
        return $output;
    }
    
    /**
     * Get campaign impact summary for email
     */
    private function get_campaign_impact_summary($campaign_id) {
        $impact_tracker = EDS_Impact_Tracker::get_instance();
        $impacts = $impact_tracker->get_campaign_impact($campaign_id);
        
        if (empty($impacts)) {
            return '<p>' . __('Campaign impact data not available.', 'environmental-donation-system') . '</p>';
        }
        
        $output = '<div class="impact-summary">';
        $output .= '<h3>' . __('Campaign Impact So Far:', 'environmental-donation-system') . '</h3>';
        
        foreach ($impacts as $impact) {
            $output .= sprintf(
                '<div class="impact-item">• %s: %s %s</div>',
                esc_html($impact['name']),
                number_format($impact['total_impact'], 0),
                esc_html($impact['unit'])
            );
        }
        
        $output .= '</div>';
        return $output;
    }
    
    /**
     * Get donor impact summary for period
     */
    private function get_donor_impact_summary($donor_id, $period) {
        global $wpdb;
        
        $date_interval = $period === 'weekly' ? '1 WEEK' : ($period === 'yearly' ? '1 YEAR' : '1 MONTH');
        
        $impacts = $wpdb->get_results($wpdb->prepare("
            SELECT di.metric_key, di.metric_name, di.unit, SUM(di.impact_value) as total_impact
            FROM {$wpdb->prefix}eds_donation_impacts di
            JOIN {$wpdb->prefix}eds_donations d ON di.donation_id = d.id
            WHERE d.donor_id = %d 
            AND d.created_at >= DATE_SUB(NOW(), INTERVAL {$date_interval})
            AND d.status = 'completed'
            GROUP BY di.metric_key
            ORDER BY total_impact DESC
        ", $donor_id), ARRAY_A);
        
        if (empty($impacts)) {
            return '<p>' . __('No impact data available for this period.', 'environmental-donation-system') . '</p>';
        }
        
        $output = '<div class="impact-summary">';
        $output .= '<h3>' . sprintf(__('Your %s Impact:', 'environmental-donation-system'), ucfirst($period)) . '</h3>';
        
        foreach ($impacts as $impact) {
            $output .= sprintf(
                '<div class="impact-item">• %s: %s %s</div>',
                esc_html($impact['metric_name']),
                number_format($impact['total_impact'], 2),
                esc_html($impact['unit'])
            );
        }
        
        $output .= '</div>';
        return $output;
    }
    
    /**
     * Get receipt URL
     */
    private function get_receipt_url($donation_id) {
        return home_url('/donation-receipt/?id=' . $donation_id);
    }
    
    /**
     * Get subscription management URL
     */
    private function get_subscription_manage_url($subscription_id) {
        return home_url('/manage-donations/?subscription=' . $subscription_id);
    }
    
    /**
     * Log notification
     */
    private function log_notification($reference_id, $type, $recipient, $status, $message = '') {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'eds_notification_logs',
            array(
                'reference_id' => $reference_id,
                'type' => $type,
                'recipient' => $recipient,
                'status' => $status,
                'message' => $message,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Set HTML content type for emails
     */
    public function set_html_mail_content_type() {
        return 'text/html';
    }
    
    /**
     * Set mail from address
     */
    public function set_mail_from($from) {
        $custom_from = get_option('eds_email_from_address');
        return $custom_from ?: $from;
    }
    
    /**
     * Set mail from name
     */
    public function set_mail_from_name($from_name) {
        $custom_name = get_option('eds_email_from_name');
        return $custom_name ?: $from_name;
    }
    
    /**
     * Send scheduled notifications (cron job)
     */
    public function send_scheduled_notifications() {
        // Send pending reminders, follow-ups, etc.
        $this->process_pending_notifications();
    }
    
    /**
     * Process pending notifications
     */
    private function process_pending_notifications() {
        global $wpdb;
        
        // Get scheduled notifications
        $notifications = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}eds_scheduled_notifications 
            WHERE scheduled_time <= %s 
            AND status = 'pending'
            ORDER BY scheduled_time ASC
            LIMIT 50
        ", current_time('mysql')));
        
        foreach ($notifications as $notification) {
            try {
                $this->send_email(
                    $notification->recipient,
                    $notification->template,
                    json_decode($notification->variables, true)
                );
                
                // Mark as sent
                $wpdb->update(
                    $wpdb->prefix . 'eds_scheduled_notifications',
                    array('status' => 'sent', 'sent_at' => current_time('mysql')),
                    array('id' => $notification->id),
                    array('%s', '%s'),
                    array('%d')
                );
                
            } catch (Exception $e) {
                // Mark as failed
                $wpdb->update(
                    $wpdb->prefix . 'eds_scheduled_notifications',
                    array('status' => 'failed', 'error_message' => $e->getMessage()),
                    array('id' => $notification->id),
                    array('%s', '%s'),
                    array('%d')
                );
            }
        }
    }
    
    /**
     * Schedule notification
     */
    public function schedule_notification($recipient, $template, $variables, $send_time) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'eds_scheduled_notifications',
            array(
                'recipient' => $recipient,
                'template' => $template,
                'variables' => json_encode($variables),
                'scheduled_time' => $send_time,
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Cleanup old notification logs (cron job)
     */
    public function cleanup_notification_logs() {
        global $wpdb;
        
        // Delete logs older than 6 months
        $wpdb->query($wpdb->prepare("
            DELETE FROM {$wpdb->prefix}eds_notification_logs 
            WHERE created_at < %s
        ", date('Y-m-d H:i:s', time() - (6 * MONTH_IN_SECONDS))));
        
        // Delete old scheduled notifications
        $wpdb->query($wpdb->prepare("
            DELETE FROM {$wpdb->prefix}eds_scheduled_notifications 
            WHERE status IN ('sent', 'failed') 
            AND created_at < %s
        ", date('Y-m-d H:i:s', time() - (3 * MONTH_IN_SECONDS))));
    }
    
    /**
     * Get default email templates content
     */
    private function get_default_donation_confirmation_content() {
        return "Dear {{donor_name}},\n\nThank you so much for your generous donation of {{donation_currency}} {{donation_amount}}!\n\n{{impact_summary}}\n\nYou can view your receipt here: {{receipt_url}}\n\nThank you for making a difference!\n\nBest regards,\nThe {{site_name}} Team";
    }
    
    private function get_default_receipt_content() {
        return "Dear {{donor_name}},\n\nPlease find attached your official donation receipt for your contribution of {{donation_currency}} {{donation_amount}} made on {{donation_date}}.\n\nDonation Details:\n- Amount: {{donation_currency}} {{donation_amount}}\n- Date: {{donation_date}}\n- Campaign: {{campaign_title}}\n\nThank you for your support!\n\nBest regards,\nThe {{site_name}} Team";
    }
    
    private function get_default_recurring_confirmation_content() {
        return "Dear {{donor_name}},\n\nYour recurring donation has been set up successfully!\n\nDetails:\n- Amount: {{subscription_currency}} {{subscription_amount}}\n- Frequency: {{subscription_frequency}}\n- Next payment: {{next_payment_date}}\n\nYou can manage your subscription here: {{manage_url}}\n\nThank you for your ongoing support!\n\nBest regards,\nThe {{site_name}} Team";
    }
    
    private function get_default_recurring_payment_content() {
        return "Dear {{donor_name}},\n\nYour {{payment_currency}} {{payment_amount}} recurring donation has been processed successfully.\n\nNext payment date: {{next_payment_date}}\n\nView your receipt: {{receipt_url}}\nManage your subscription: {{manage_url}}\n\nThank you for your continued support!\n\nBest regards,\nThe {{site_name}} Team";
    }
    
    private function get_default_recurring_failed_content() {
        return "Dear {{donor_name}},\n\nWe were unable to process your recurring donation payment. Please update your payment information to continue supporting our cause.\n\nManage your subscription: {{manage_url}}\n\nIf you have any questions, please contact us.\n\nBest regards,\nThe {{site_name}} Team";
    }
    
    private function get_default_milestone_content() {
        return "Dear {{donor_name}},\n\nGreat news! The '{{campaign_title}}' campaign has reached {{milestone_percentage}}% of its goal!\n\nCurrent total: {{currency}} {{current_amount}} of {{currency}} {{goal_amount}}\n\n{{impact_summary}}\n\nThank you for being part of this amazing achievement!\n\nView campaign: {{campaign_url}}\n\nBest regards,\nThe {{site_name}} Team";
    }
    
    private function get_default_campaign_complete_content() {
        return "Dear {{donor_name}},\n\nWonderful news! The '{{campaign_title}}' campaign has been successfully completed!\n\nFinal amount raised: {{currency}} {{final_amount}}\nTotal donors: {{total_donors}}\n\n{{impact_summary}}\n\nThank you for making this possible with your donation of {{currency}} {{donor_total_donated}}!\n\nBest regards,\nThe {{site_name}} Team";
    }
    
    private function get_default_impact_update_content() {
        return "Dear {{donor_name}},\n\nHere's your {{period}} environmental impact update!\n\nYour total donations: {{currency}} {{total_donated}}\nCampaigns supported: {{campaigns_supported}}\n\n{{impact_summary}}\n\nThank you for making a difference!\n\nBest regards,\nThe {{site_name}} Team";
    }
    
    private function get_default_admin_donation_content() {
        return "New donation received:\n\nDonor: {{donor_name}} ({{donor_email}})\nAmount: {{donation_currency}} {{donation_amount}}\nPayment Method: {{payment_method}}\nCampaign: {{campaign_title}}\n\nView donation: {{donation_url}}";
    }
    
    private function get_default_admin_campaign_alert_content() {
        return "Campaign Alert:\n\n{{alert_message}}\n\nCampaign: {{campaign_title}}\nCurrent Status: {{campaign_status}}\n\nView campaign details in admin panel.";
    }
    
    /**
     * AJAX Handlers
     */
    public function handle_test_email() {
        check_ajax_referer('eds_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $email = sanitize_email($_POST['email']);
        $template = sanitize_text_field($_POST['template']);
        
        if (!$email || !isset($this->email_templates[$template])) {
            wp_send_json_error('Invalid email or template');
        }
        
        try {
            $test_variables = array(
                'donor_name' => 'Test User',
                'donation_amount' => '50.00',
                'donation_currency' => 'USD',
                'campaign_title' => 'Test Campaign'
            );
            
            $this->send_email($email, $template, $test_variables);
            wp_send_json_success('Test email sent successfully');
            
        } catch (Exception $e) {
            wp_send_json_error('Failed to send test email: ' . $e->getMessage());
        }
    }
    
    public function handle_update_email_template() {
        check_ajax_referer('eds_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $template = sanitize_text_field($_POST['template']);
        $subject = sanitize_text_field($_POST['subject']);
        $content = wp_kses_post($_POST['content']);
        
        if (!isset($this->email_templates[$template])) {
            wp_send_json_error('Invalid template');
        }
        
        update_option("eds_email_subject_{$template}", $subject);
        update_option("eds_email_content_{$template}", $content);
        
        wp_send_json_success('Template updated successfully');
    }
    
    public function handle_preview_email() {
        check_ajax_referer('eds_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $template = sanitize_text_field($_POST['template']);
        $subject = sanitize_text_field($_POST['subject']);
        $content = wp_kses_post($_POST['content']);
        
        $test_variables = array(
            'donor_name' => 'John Doe',
            'donation_amount' => '100.00',
            'donation_currency' => 'USD',
            'donation_date' => date('F j, Y'),
            'campaign_title' => 'Save the Forest Campaign',
            'organization_name' => 'Green Earth Foundation',
            'site_name' => get_bloginfo('name')
        );
        
        $preview_subject = $this->replace_variables($subject, $test_variables);
        $preview_content = $this->replace_variables($content, $test_variables);
        $preview_html = $this->wrap_email_content($preview_content, $test_variables);
        
        wp_send_json_success(array(
            'subject' => $preview_subject,
            'content' => $preview_html
        ));
    }
}
