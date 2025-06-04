<?php
/**
 * Email Notifications Class
 * 
 * Handles automated email notifications for petition system
 * 
 * @package Environmental_Platform_Petitions
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Platform_Petitions_Email_Notifications {
    
    /**
     * Database instance
     */
    private $database;
    
    /**
     * Email templates
     */
    private $templates = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new Environmental_Platform_Petitions_Database();
        $this->init_hooks();
        $this->init_templates();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Petition events
        add_action('petition_signature_created', array($this, 'send_signature_confirmation'), 10, 2);
        add_action('petition_signature_verified', array($this, 'send_verification_success'), 10, 2);
        add_action('petition_milestone_reached', array($this, 'send_milestone_notification'), 10, 3);
        
        // Campaign events
        add_action('petition_campaign_started', array($this, 'send_campaign_started'), 10, 2);
        add_action('petition_campaign_ended', array($this, 'send_campaign_ended'), 10, 2);
        
        // Admin notifications
        add_action('petition_new_signature', array($this, 'send_admin_notification'), 10, 2);
        
        // Custom email settings
        add_filter('wp_mail_content_type', array($this, 'set_email_content_type'));
        add_action('wp_mail_failed', array($this, 'log_email_failure'));
    }
    
    /**
     * Initialize email templates
     */
    private function init_templates() {
        $this->templates = array(
            'signature_confirmation' => array(
                'subject' => 'Please confirm your petition signature',
                'template' => 'signature-confirmation.php'
            ),
            'verification_success' => array(
                'subject' => 'Your signature has been verified!',
                'template' => 'verification-success.php'
            ),
            'milestone_reached' => array(
                'subject' => 'Milestone reached: {milestone_title}',
                'template' => 'milestone-reached.php'
            ),
            'campaign_update' => array(
                'subject' => 'Campaign Update: {petition_title}',
                'template' => 'campaign-update.php'
            ),
            'admin_new_signature' => array(
                'subject' => 'New signature received for {petition_title}',
                'template' => 'admin-new-signature.php'
            )
        );
    }
    
    /**
     * Send signature confirmation email
     */
    public function send_signature_confirmation($signature_id, $signature_data) {
        $verification_link = $this->generate_verification_link($signature_id);
        $petition = get_post($signature_data['petition_id']);
        
        $template_data = array(
            'first_name' => $signature_data['first_name'],
            'last_name' => $signature_data['last_name'],
            'petition_title' => $petition->post_title,
            'petition_url' => get_permalink($petition->ID),
            'verification_link' => $verification_link,
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url()
        );
        
        $this->send_email(
            $signature_data['user_email'],
            'signature_confirmation',
            $template_data
        );
        
        // Track email send
        $this->track_email_event($signature_data['petition_id'], 'signature_confirmation', $signature_data['user_email']);
    }
    
    /**
     * Send verification success email
     */
    public function send_verification_success($signature_id, $signature_data) {
        $petition = get_post($signature_data['petition_id']);
        $current_signatures = $this->get_petition_signature_count($signature_data['petition_id']);
        
        $template_data = array(
            'first_name' => $signature_data['first_name'],
            'last_name' => $signature_data['last_name'],
            'petition_title' => $petition->post_title,
            'petition_url' => get_permalink($petition->ID),
            'current_signatures' => $current_signatures,
            'share_url' => $this->generate_share_url($petition->ID),
            'site_name' => get_bloginfo('name'),
            'unsubscribe_link' => $this->generate_unsubscribe_link($signature_data['user_email'])
        );
        
        $this->send_email(
            $signature_data['user_email'],
            'verification_success',
            $template_data
        );
        
        // Track email send
        $this->track_email_event($signature_data['petition_id'], 'verification_success', $signature_data['user_email']);
    }
    
    /**
     * Send milestone notification
     */
    public function send_milestone_notification($petition_id, $milestone, $current_count) {
        // Get all verified signers for this petition
        $signers = $this->get_petition_signers($petition_id);
        
        if (empty($signers)) {
            return;
        }
        
        $petition = get_post($petition_id);
        
        $template_data = array(
            'petition_title' => $petition->post_title,
            'petition_url' => get_permalink($petition_id),
            'milestone_title' => $milestone->title,
            'milestone_count' => $milestone->target_count,
            'current_count' => $current_count,
            'milestone_message' => $milestone->message,
            'share_url' => $this->generate_share_url($petition_id),
            'site_name' => get_bloginfo('name')
        );
        
        // Send to all signers
        foreach ($signers as $signer) {
            $template_data['first_name'] = $signer->first_name;
            $template_data['unsubscribe_link'] = $this->generate_unsubscribe_link($signer->user_email);
            
            $this->send_email(
                $signer->user_email,
                'milestone_reached',
                $template_data
            );
        }
        
        // Track milestone email
        $this->track_email_event($petition_id, 'milestone_reached', 'bulk', count($signers));
    }
    
    /**
     * Send admin notification for new signature
     */
    public function send_admin_notification($signature_id, $signature_data) {
        $admin_email = get_option('admin_email');
        $petition = get_post($signature_data['petition_id']);
        
        $template_data = array(
            'signer_name' => $signature_data['first_name'] . ' ' . $signature_data['last_name'],
            'signer_email' => $signature_data['user_email'],
            'petition_title' => $petition->post_title,
            'petition_edit_url' => get_edit_post_link($petition->ID),
            'signature_date' => current_time('mysql'),
            'site_name' => get_bloginfo('name'),
            'admin_dashboard_url' => admin_url('admin.php?page=petition-signatures')
        );
        
        $this->send_email(
            $admin_email,
            'admin_new_signature',
            $template_data
        );
    }
    
    /**
     * Send campaign update
     */
    public function send_campaign_update($petition_id, $update_data) {
        $signers = $this->get_petition_signers($petition_id);
        
        if (empty($signers)) {
            return;
        }
        
        $petition = get_post($petition_id);
        
        $template_data = array(
            'petition_title' => $petition->post_title,
            'petition_url' => get_permalink($petition_id),
            'update_title' => $update_data['title'],
            'update_content' => $update_data['content'],
            'update_date' => current_time('F j, Y'),
            'site_name' => get_bloginfo('name')
        );
        
        foreach ($signers as $signer) {
            $template_data['first_name'] = $signer->first_name;
            $template_data['unsubscribe_link'] = $this->generate_unsubscribe_link($signer->user_email);
            
            $this->send_email(
                $signer->user_email,
                'campaign_update',
                $template_data
            );
        }
        
        // Track campaign update email
        $this->track_email_event($petition_id, 'campaign_update', 'bulk', count($signers));
    }
    
    /**
     * Send email using template
     */
    private function send_email($to, $template_type, $template_data) {
        if (!isset($this->templates[$template_type])) {
            return false;
        }
        
        $template = $this->templates[$template_type];
        
        // Generate subject with placeholders
        $subject = $this->replace_placeholders($template['subject'], $template_data);
        
        // Generate email content
        $content = $this->render_email_template($template['template'], $template_data);
        
        if (!$content) {
            return false;
        }
        
        // Email headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        // Send email
        $sent = wp_mail($to, $subject, $content, $headers);
        
        // Log email attempt
        $this->log_email_attempt($to, $template_type, $sent);
        
        return $sent;
    }
    
    /**
     * Render email template
     */
    private function render_email_template($template_file, $data) {
        $template_path = plugin_dir_path(dirname(__FILE__)) . 'templates/emails/' . $template_file;
        
        if (!file_exists($template_path)) {
            // Fallback to default template
            return $this->render_default_template($data);
        }
        
        // Extract data for template
        extract($data);
        
        ob_start();
        include $template_path;
        return ob_get_clean();
    }
    
    /**
     * Render default email template
     */
    private function render_default_template($data) {
        $default_content = '<html><head><title>{site_name}</title></head><body>';
        $default_content .= '<div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;">';
        $default_content .= '<h2 style="color: #2271b1;">Environmental Platform</h2>';
        
        if (isset($data['first_name'])) {
            $default_content .= '<p>Hello ' . esc_html($data['first_name']) . ',</p>';
        }
        
        if (isset($data['petition_title'])) {
            $default_content .= '<p>Thank you for your interest in: <strong>' . esc_html($data['petition_title']) . '</strong></p>';
        }
        
        if (isset($data['verification_link'])) {
            $default_content .= '<p><a href="' . esc_url($data['verification_link']) . '" style="background: #2271b1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">Verify Your Signature</a></p>';
        }
        
        if (isset($data['petition_url'])) {
            $default_content .= '<p><a href="' . esc_url($data['petition_url']) . '">View Petition</a></p>';
        }
        
        $default_content .= '<p>Best regards,<br>The Environmental Platform Team</p>';
        $default_content .= '</div></body></html>';
        
        return $this->replace_placeholders($default_content, $data);
    }
    
    /**
     * Replace placeholders in text
     */
    private function replace_placeholders($text, $data) {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $text = str_replace('{' . $key . '}', $value, $text);
            }
        }
        
        return $text;
    }
    
    /**
     * Generate verification link
     */
    private function generate_verification_link($signature_id) {
        $verification_token = wp_generate_password(32, false);
        
        // Store token in database
        global $wpdb;
        $signatures_table = $this->database->get_table_name('signatures');
        
        $wpdb->update(
            $signatures_table,
            array('verification_token' => $verification_token),
            array('id' => $signature_id)
        );
        
        return add_query_arg(
            array(
                'action' => 'verify_signature',
                'token' => $verification_token,
                'id' => $signature_id
            ),
            home_url()
        );
    }
    
    /**
     * Generate share URL
     */
    private function generate_share_url($petition_id) {
        return add_query_arg(
            array('utm_source' => 'email', 'utm_medium' => 'notification'),
            get_permalink($petition_id)
        );
    }
    
    /**
     * Generate unsubscribe link
     */
    private function generate_unsubscribe_link($email) {
        $token = wp_hash($email . get_option('nonce_salt'));
        
        return add_query_arg(
            array(
                'action' => 'petition_unsubscribe',
                'email' => base64_encode($email),
                'token' => $token
            ),
            home_url()
        );
    }
    
    /**
     * Get petition signature count
     */
    private function get_petition_signature_count($petition_id) {
        global $wpdb;
        
        $table = $this->database->get_table_name('signatures');
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE petition_id = %d AND is_verified = 1",
            $petition_id
        ));
    }
    
    /**
     * Get petition signers
     */
    private function get_petition_signers($petition_id) {
        global $wpdb;
        
        $table = $this->database->get_table_name('signatures');
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT user_email, first_name, last_name 
            FROM {$table} 
            WHERE petition_id = %d AND is_verified = 1 
            AND user_email NOT IN (
                SELECT email FROM {$wpdb->prefix}petition_unsubscribes 
                WHERE petition_id = %d OR petition_id IS NULL
            )",
            $petition_id,
            $petition_id
        ));
    }
    
    /**
     * Track email event
     */
    private function track_email_event($petition_id, $email_type, $recipient, $count = 1) {
        global $wpdb;
        
        $analytics_table = $this->database->get_table_name('analytics');
        
        $wpdb->insert($analytics_table, array(
            'petition_id' => $petition_id,
            'event_type' => 'email_sent',
            'event_data' => wp_json_encode(array(
                'email_type' => $email_type,
                'recipient' => $recipient,
                'count' => $count
            )),
            'created_at' => current_time('mysql')
        ));
    }
    
    /**
     * Log email attempt
     */
    private function log_email_attempt($to, $template_type, $success) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'recipient' => $to,
            'template' => $template_type,
            'success' => $success ? 1 : 0,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        );
        
        // Store in transient for debugging
        $log_key = 'petition_email_log_' . date('Y_m_d');
        $existing_log = get_transient($log_key) ?: array();
        $existing_log[] = $log_entry;
        
        // Keep only last 100 entries
        if (count($existing_log) > 100) {
            $existing_log = array_slice($existing_log, -100);
        }
        
        set_transient($log_key, $existing_log, DAY_IN_SECONDS);
    }
    
    /**
     * Set email content type to HTML
     */
    public function set_email_content_type() {
        return 'text/html';
    }
    
    /**
     * Log email failure
     */
    public function log_email_failure($wp_error) {
        error_log('Petition email failed: ' . $wp_error->get_error_message());
    }
    
    /**
     * Get email statistics
     */
    public function get_email_statistics($petition_id = null) {
        global $wpdb;
        
        $analytics_table = $this->database->get_table_name('analytics');
        
        $where_clause = "WHERE event_type = 'email_sent'";
        $params = array();
        
        if ($petition_id) {
            $where_clause .= " AND petition_id = %d";
            $params[] = $petition_id;
        }
        
        $stats = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                JSON_EXTRACT(event_data, '$.email_type') as email_type,
                COUNT(*) as sent_count,
                SUM(JSON_EXTRACT(event_data, '$.count')) as total_recipients
            FROM {$analytics_table} 
            {$where_clause}
            GROUP BY JSON_EXTRACT(event_data, '$.email_type')",
            $params
        ));
        
        return $stats;
    }
    
    /**
     * Handle unsubscribe request
     */
    public function handle_unsubscribe() {
        if (!isset($_GET['action']) || $_GET['action'] !== 'petition_unsubscribe') {
            return;
        }
        
        $email = base64_decode($_GET['email'] ?? '');
        $token = $_GET['token'] ?? '';
        
        if (!$email || !$token) {
            wp_die('Invalid unsubscribe request.');
        }
        
        $expected_token = wp_hash($email . get_option('nonce_salt'));
        
        if (!hash_equals($expected_token, $token)) {
            wp_die('Invalid unsubscribe token.');
        }
        
        // Add to unsubscribe list
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'petition_unsubscribes',
            array(
                'email' => $email,
                'unsubscribed_at' => current_time('mysql'),
                'user_ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            )
        );
        
        wp_die('You have been successfully unsubscribed from petition notifications.');
    }
    
    /**
     * Create unsubscribe table
     */
    public function create_unsubscribe_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'petition_unsubscribes';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            petition_id bigint(20) unsigned NULL,
            unsubscribed_at datetime NOT NULL,
            user_ip varchar(45) NULL,
            PRIMARY KEY (id),
            UNIQUE KEY email_petition (email, petition_id),
            KEY email (email)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Schedule milestone check
     */
    public function schedule_milestone_check($petition_id) {
        if (!wp_next_scheduled('check_petition_milestones', array($petition_id))) {
            wp_schedule_event(time(), 'hourly', 'check_petition_milestones', array($petition_id));
        }
    }
      /**
     * Send test email (for debugging)
     */
    public function send_test_email($template_type = 'signature_confirmation', $to = null) {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        $to = $to ?: get_option('admin_email');
        
        $test_data = array(
            'first_name' => 'Test',
            'last_name' => 'User',
            'petition_title' => 'Test Petition for Email Configuration',
            'petition_url' => home_url(),
            'verification_link' => home_url(),
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url(),
            'unsubscribe_link' => home_url(),
            'signature_count' => 150,
            'milestone' => 500,
            'progress_percentage' => 30
        );
        
        return $this->send_email($to, $template_type, $test_data);
    }
}
