<?php
/**
 * Verification System for Environmental Platform Petitions
 * 
 * Handles signature verification via email, SMS, and ID verification
 * 
 * @package Environmental_Platform_Petitions
 * @since 1.0.0 - Phase 35
 */

if (!defined('ABSPATH')) {
    exit;
}

class EPP_Verification_System {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'handle_verification_request'));
        add_action('wp_ajax_resend_verification', array($this, 'resend_verification_email'));
        add_action('wp_ajax_nopriv_resend_verification', array($this, 'resend_verification_email'));
        add_action('wp_ajax_verify_phone', array($this, 'verify_phone_number'));
        add_action('wp_ajax_nopriv_verify_phone', array($this, 'verify_phone_number'));
    }
    
    /**
     * Handle verification request from email link
     */
    public function handle_verification_request() {
        if (isset($_GET['verify_signature']) && isset($_GET['signature_id'])) {
            $verification_code = sanitize_text_field($_GET['verify_signature']);
            $signature_id = intval($_GET['signature_id']);
            
            $result = $this->verify_signature($signature_id, $verification_code);
            
            if ($result['success']) {
                wp_redirect(add_query_arg('verification', 'success', remove_query_arg(array('verify_signature', 'signature_id'))));
            } else {
                wp_redirect(add_query_arg('verification', 'failed', remove_query_arg(array('verify_signature', 'signature_id'))));
            }
            exit;
        }
        
        // Display verification messages
        if (isset($_GET['verification'])) {
            add_action('wp_footer', array($this, 'show_verification_message'));
        }
    }
    
    /**
     * Verify a signature
     */
    public function verify_signature($signature_id, $verification_code) {
        global $wpdb;
        
        $signatures_table = $wpdb->prefix . 'petition_signatures';
        
        // Get signature
        $signature = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $signatures_table WHERE id = %d AND verification_code = %s AND status = 'pending'",
            $signature_id,
            $verification_code
        ));
        
        if (!$signature) {
            return array(
                'success' => false,
                'message' => __('Invalid verification code or signature already verified.', 'environmental-platform-petitions')
            );
        }
        
        // Check if verification code has expired (24 hours)
        $verification_sent = strtotime($signature->verification_sent_at);
        $expiry_time = $verification_sent + (24 * 60 * 60); // 24 hours
        
        if (current_time('timestamp') > $expiry_time) {
            return array(
                'success' => false,
                'message' => __('Verification code has expired. Please request a new one.', 'environmental-platform-petitions')
            );
        }
        
        // Verify signature
        $result = $wpdb->update(
            $signatures_table,
            array(
                'status' => 'verified',
                'is_verified' => 1,
                'verified_at' => current_time('mysql'),
                'verification_code' => null
            ),
            array('id' => $signature_id),
            array('%s', '%d', '%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => __('Error verifying signature. Please try again.', 'environmental-platform-petitions')
            );
        }
        
        // Update petition signature count
        $this->update_petition_counts($signature->petition_id);
        
        // Track verification event
        EPP_Database::track_event($signature->petition_id, 'signature_verified', array(
            'signature_id' => $signature_id,
            'verification_method' => 'email'
        ));
        
        // Send confirmation email
        $this->send_verification_confirmation($signature);
        
        return array(
            'success' => true,
            'message' => __('Your signature has been verified successfully!', 'environmental-platform-petitions')
        );
    }
    
    /**
     * Resend verification email
     */
    public function resend_verification_email() {
        check_ajax_referer('epp_nonce', 'nonce');
        
        $signature_id = intval($_POST['signature_id']);
        
        global $wpdb;
        $signatures_table = $wpdb->prefix . 'petition_signatures';
        
        $signature = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $signatures_table WHERE id = %d AND status = 'pending'",
            $signature_id
        ));
        
        if (!$signature) {
            wp_send_json_error(__('Signature not found or already verified.', 'environmental-platform-petitions'));
        }
        
        // Generate new verification code
        $new_code = wp_generate_password(32, false);
        
        // Update signature with new code
        $wpdb->update(
            $signatures_table,
            array(
                'verification_code' => $new_code,
                'verification_sent_at' => current_time('mysql')
            ),
            array('id' => $signature_id),
            array('%s', '%s'),
            array('%d')
        );
        
        // Send new verification email
        $this->send_verification_email_by_id($signature_id);
        
        wp_send_json_success(__('Verification email sent successfully.', 'environmental-platform-petitions'));
    }
    
    /**
     * Send verification email by signature ID
     */
    private function send_verification_email_by_id($signature_id) {
        global $wpdb;
        
        $signatures_table = $wpdb->prefix . 'petition_signatures';
        
        $signature = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $signatures_table WHERE id = %d",
            $signature_id
        ));
        
        if (!$signature) {
            return false;
        }
        
        $petition = get_post($signature->petition_id);
        $verification_url = add_query_arg(array(
            'verify_signature' => $signature->verification_code,
            'signature_id' => $signature_id
        ), get_permalink($petition->ID));
        
        $subject = sprintf(
            __('Verify your signature for: %s', 'environmental-platform-petitions'),
            $petition->post_title
        );
        
        $message = $this->get_verification_email_template($signature, $petition, $verification_url);
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        return wp_mail($signature->signer_email, $subject, $message, $headers);
    }
    
    /**
     * Get verification email template
     */
    private function get_verification_email_template($signature, $petition, $verification_url) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php _e('Verify Your Signature', 'environmental-platform-petitions'); ?></title>
            <style>
                .email-container { max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif; }
                .header { background: #28a745; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px 20px; background: #f8f9fa; }
                .petition-info { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .verify-button { 
                    display: inline-block; 
                    background: #28a745; 
                    color: white; 
                    padding: 15px 30px; 
                    text-decoration: none; 
                    border-radius: 5px; 
                    font-weight: bold;
                    margin: 20px 0;
                }
                .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="header">
                    <h1><?php _e('Environmental Platform', 'environmental-platform-petitions'); ?></h1>
                    <p><?php _e('Verify Your Petition Signature', 'environmental-platform-petitions'); ?></p>
                </div>
                
                <div class="content">
                    <h2><?php printf(__('Hello %s,', 'environmental-platform-petitions'), esc_html($signature->signer_name)); ?></h2>
                    
                    <p><?php _e('Thank you for signing our petition! To complete your signature and make it count, please verify your email address by clicking the button below.', 'environmental-platform-petitions'); ?></p>
                    
                    <div class="petition-info">
                        <h3><?php echo esc_html($petition->post_title); ?></h3>
                        <p><?php echo wp_trim_words(strip_tags($petition->post_content), 30); ?></p>
                    </div>
                    
                    <div style="text-align: center;">
                        <a href="<?php echo esc_url($verification_url); ?>" class="verify-button">
                            <?php _e('Verify My Signature', 'environmental-platform-petitions'); ?>
                        </a>
                    </div>
                    
                    <p><strong><?php _e('Important:', 'environmental-platform-petitions'); ?></strong> <?php _e('This verification link will expire in 24 hours.', 'environmental-platform-petitions'); ?></p>
                    
                    <p><?php _e('If the button doesn\'t work, you can copy and paste this link into your browser:', 'environmental-platform-petitions'); ?></p>
                    <p style="word-break: break-all; color: #28a745;"><?php echo esc_url($verification_url); ?></p>
                    
                    <hr style="margin: 30px 0; border: none; border-top: 1px solid #ddd;">
                    
                    <p><small><?php _e('If you didn\'t sign this petition, please ignore this email. Your email address will not be added to any lists.', 'environmental-platform-petitions'); ?></small></p>
                </div>
                
                <div class="footer">
                    <p><?php _e('This email was sent by Environmental Platform', 'environmental-platform-petitions'); ?></p>
                    <p><?php printf(__('You received this because you signed the petition "%s"', 'environmental-platform-petitions'), esc_html($petition->post_title)); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Send verification confirmation email
     */
    private function send_verification_confirmation($signature) {
        $petition = get_post($signature->petition_id);
        
        $subject = sprintf(
            __('Signature confirmed for: %s', 'environmental-platform-petitions'),
            $petition->post_title
        );
        
        $current_signatures = get_post_meta($signature->petition_id, '_current_signatures', true);
        $target_signatures = get_post_meta($signature->petition_id, '_signature_goal', true);
        
        $message = sprintf(
            __("Hello %s,\n\nYour signature for the petition \"%s\" has been successfully verified!\n\nCurrent signatures: %d\nTarget: %d\n\nThank you for your support. Together we can make a difference!\n\nView petition: %s\n\nBest regards,\nThe Environmental Platform Team", 'environmental-platform-petitions'),
            $signature->signer_name,
            $petition->post_title,
            $current_signatures,
            $target_signatures,
            get_permalink($petition->ID)
        );
        
        return wp_mail($signature->signer_email, $subject, $message);
    }
    
    /**
     * Phone number verification (basic implementation)
     */
    public function verify_phone_number() {
        check_ajax_referer('epp_nonce', 'nonce');
        
        $phone_number = sanitize_text_field($_POST['phone_number']);
        $signature_id = intval($_POST['signature_id']);
        
        // Basic phone validation
        if (!$this->is_valid_phone_number($phone_number)) {
            wp_send_json_error(__('Invalid phone number format.', 'environmental-platform-petitions'));
        }
        
        // In a real implementation, this would integrate with SMS services like Twilio
        // For now, we'll simulate the process
        
        $verification_code = rand(100000, 999999);
        
        // Store verification code temporarily
        set_transient('epp_phone_verification_' . $signature_id, $verification_code, 300); // 5 minutes
        
        // Simulate SMS sending (would integrate with actual SMS service)
        $sms_sent = $this->send_sms_verification($phone_number, $verification_code);
        
        if ($sms_sent) {
            wp_send_json_success(__('Verification code sent to your phone.', 'environmental-platform-petitions'));
        } else {
            wp_send_json_error(__('Failed to send verification code. Please try again.', 'environmental-platform-petitions'));
        }
    }
    
    /**
     * Validate phone number format
     */
    private function is_valid_phone_number($phone) {
        // Basic international phone number validation
        $pattern = '/^[\+]?[1-9][\d]{0,15}$/';
        return preg_match($pattern, preg_replace('/[^\d\+]/', '', $phone));
    }
    
    /**
     * Simulate SMS verification sending
     */
    private function send_sms_verification($phone_number, $verification_code) {
        // In production, integrate with SMS services like:
        // - Twilio
        // - AWS SNS
        // - Firebase
        // - Local SMS gateways
        
        // For now, just log the attempt
        error_log("SMS Verification: Phone {$phone_number}, Code: {$verification_code}");
        
        return true; // Simulate successful sending
    }
    
    /**
     * ID verification system (placeholder for future implementation)
     */
    public function verify_id_document($signature_id, $document_data) {
        // This would integrate with ID verification services like:
        // - Jumio
        // - Onfido
        // - AWS Rekognition
        // - Government ID verification APIs
        
        return array(
            'success' => false,
            'message' => __('ID verification is not yet implemented.', 'environmental-platform-petitions')
        );
    }
    
    /**
     * Update petition signature counts
     */
    private function update_petition_counts($petition_id) {
        global $wpdb;
        
        $signatures_table = $wpdb->prefix . 'petition_signatures';
        
        $verified_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $signatures_table WHERE petition_id = %d AND status = 'verified'",
            $petition_id
        ));
        
        $total_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $signatures_table WHERE petition_id = %d AND status != 'spam'",
            $petition_id
        ));
        
        update_post_meta($petition_id, '_current_signatures', $verified_count);
        update_post_meta($petition_id, '_total_signatures', $total_count);
        
        // Check for milestones
        $this->check_milestones($petition_id, $verified_count);
    }
    
    /**
     * Check if milestones have been reached
     */
    private function check_milestones($petition_id, $current_signatures) {
        $milestones = EPP_Database::get_milestones($petition_id);
        
        foreach ($milestones as $milestone) {
            if ($current_signatures >= $milestone['milestone_value'] && !$milestone['reached_at']) {
                // Trigger milestone
                global $wpdb;
                $milestones_table = $wpdb->prefix . 'petition_milestones';
                
                $wpdb->update(
                    $milestones_table,
                    array('reached_at' => current_time('mysql')),
                    array('id' => $milestone['id']),
                    array('%s'),
                    array('%d')
                );
                
                // Track event
                EPP_Database::track_event($petition_id, 'milestone_reached', array(
                    'milestone_id' => $milestone['id'],
                    'milestone_value' => $milestone['milestone_value']
                ));
            }
        }
    }
    
    /**
     * Show verification status message
     */
    public function show_verification_message() {
        $status = $_GET['verification'];
        
        $message = '';
        $class = '';
        
        switch ($status) {
            case 'success':
                $message = __('Your signature has been verified successfully! Thank you for your support.', 'environmental-platform-petitions');
                $class = 'success';
                break;
            case 'failed':
                $message = __('Signature verification failed. Please check your verification link or contact support.', 'environmental-platform-petitions');
                $class = 'error';
                break;
        }
        
        if ($message) {
            ?>
            <script>
            jQuery(document).ready(function($) {
                $('body').prepend('<div class="epp-verification-message epp-' + '<?php echo $class; ?>' + '"><?php echo esc_js($message); ?><span class="epp-close-message">&times;</span></div>');
                
                $('.epp-close-message').on('click', function() {
                    $('.epp-verification-message').fadeOut();
                });
                
                setTimeout(function() {
                    $('.epp-verification-message').fadeOut();
                }, 5000);
            });
            </script>
            
            <style>
            .epp-verification-message {
                position: fixed;
                top: 20px;
                left: 50%;
                transform: translateX(-50%);
                padding: 15px 20px;
                border-radius: 5px;
                font-weight: bold;
                z-index: 9999;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                cursor: pointer;
            }
            
            .epp-verification-message.epp-success {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            
            .epp-verification-message.epp-error {
                background: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
            
            .epp-close-message {
                float: right;
                margin-left: 10px;
                font-size: 18px;
                font-weight: bold;
                cursor: pointer;
            }
            </style>
            <?php
        }
    }
    
    /**
     * Get verification statistics
     */
    public function get_verification_stats($petition_id = null) {
        global $wpdb;
        
        $signatures_table = $wpdb->prefix . 'petition_signatures';
        
        $where = '';
        $params = array();
        
        if ($petition_id) {
            $where = 'WHERE petition_id = %d';
            $params[] = $petition_id;
        }
        
        $query = "
            SELECT 
                COUNT(*) as total_signatures,
                COUNT(CASE WHEN status = 'verified' THEN 1 END) as verified_signatures,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_verification,
                COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_signatures,
                AVG(TIMESTAMPDIFF(MINUTE, verification_sent_at, verified_at)) as avg_verification_time_minutes
            FROM $signatures_table 
            $where
        ";
        
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        
        return $wpdb->get_row($query, ARRAY_A);
    }
    
    /**
     * Bulk verify signatures (admin function)
     */
    public function bulk_verify_signatures($signature_ids) {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        global $wpdb;
        $signatures_table = $wpdb->prefix . 'petition_signatures';
        
        $ids_placeholder = implode(',', array_fill(0, count($signature_ids), '%d'));
        
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE $signatures_table 
             SET status = 'verified', is_verified = 1, verified_at = %s 
             WHERE id IN ($ids_placeholder) AND status = 'pending'",
            array_merge([current_time('mysql')], $signature_ids)
        ));
        
        // Update petition counts for affected petitions
        $petition_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT petition_id FROM $signatures_table WHERE id IN ($ids_placeholder)",
            $signature_ids
        ));
        
        foreach ($petition_ids as $petition_id) {
            $this->update_petition_counts($petition_id);
        }
        
        return $result;
    }
    
    /**
     * Reject a signature (admin function)
     */
    public function reject_signature($signature_id, $reason = '') {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        global $wpdb;
        $signatures_table = $wpdb->prefix . 'petition_signatures';
        
        $result = $wpdb->update(
            $signatures_table,
            array(
                'status' => 'rejected',
                'is_verified' => 0,
                'verification_notes' => sanitize_text_field($reason),
                'verified_at' => current_time('mysql')
            ),
            array('id' => $signature_id),
            array('%s', '%d', '%s', '%s'),
            array('%d')
        );
        
        if ($result) {
            // Get petition ID to update counts
            $petition_id = $wpdb->get_var($wpdb->prepare(
                "SELECT petition_id FROM $signatures_table WHERE id = %d",
                $signature_id
            ));
            
            if ($petition_id) {
                $this->update_petition_counts($petition_id);
                
                // Track rejection event
                EPP_Database::track_event($petition_id, 'signature_rejected', array(
                    'signature_id' => $signature_id,
                    'reason' => $reason
                ));
            }
        }
        
        return $result !== false;
    }
    
    /**
     * Bulk reject signatures (admin function)
     */
    public function bulk_reject_signatures($signature_ids, $reason = '') {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        global $wpdb;
        $signatures_table = $wpdb->prefix . 'petition_signatures';
        
        $ids_placeholder = implode(',', array_fill(0, count($signature_ids), '%d'));
        
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE $signatures_table 
             SET status = 'rejected', is_verified = 0, verification_notes = %s, verified_at = %s 
             WHERE id IN ($ids_placeholder)",
            array_merge([sanitize_text_field($reason), current_time('mysql')], $signature_ids)
        ));
        
        // Update petition counts for affected petitions
        $petition_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT petition_id FROM $signatures_table WHERE id IN ($ids_placeholder)",
            $signature_ids
        ));
        
        foreach ($petition_ids as $petition_id) {
            $this->update_petition_counts($petition_id);
            
            // Track bulk rejection event
            EPP_Database::track_event($petition_id, 'signatures_bulk_rejected', array(
                'count' => count($signature_ids),
                'reason' => $reason
            ));
        }
        
        return $result;
    }
    
    /**
     * Get pending verifications for admin
     */
    public function get_pending_verifications($limit = 10, $offset = 0, $petition_id = null) {
        global $wpdb;
        $signatures_table = $wpdb->prefix . 'petition_signatures';
        
        $where_clause = "WHERE status = 'pending'";
        $params = array();
        
        if ($petition_id) {
            $where_clause .= " AND petition_id = %d";
            $params[] = $petition_id;
        }
        
        $query = "
            SELECT 
                id, petition_id, first_name, last_name, email, 
                created_at, verification_sent_at, verification_token
            FROM $signatures_table 
            $where_clause
            ORDER BY created_at DESC
            LIMIT %d OFFSET %d
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        return $wpdb->get_results($wpdb->prepare($query, $params));
    }
    
    /**
     * Resend verification email
     */
    public function resend_verification($signature_id) {
        global $wpdb;
        $signatures_table = $wpdb->prefix . 'petition_signatures';
        
        $signature = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $signatures_table WHERE id = %d",
            $signature_id
        ));
        
        if (!$signature || $signature->status !== 'pending') {
            return false;
        }
        
        // Generate new verification token if needed
        if (empty($signature->verification_token)) {
            $verification_token = wp_generate_uuid4();
            $wpdb->update(
                $signatures_table,
                array('verification_token' => $verification_token),
                array('id' => $signature_id),
                array('%s'),
                array('%d')
            );
            $signature->verification_token = $verification_token;
        }
        
        // Send verification email
        $email_notifications = new EPP_Email_Notifications();
        $result = $email_notifications->send_verification_email(
            $signature->email,
            $signature->first_name,
            get_the_title($signature->petition_id),
            $signature->verification_token,
            $signature_id
        );
        
        if ($result) {
            // Update verification sent timestamp
            $wpdb->update(
                $signatures_table,
                array('verification_sent_at' => current_time('mysql')),
                array('id' => $signature_id),
                array('%s'),
                array('%d')
            );
            
            // Track resend event
            EPP_Database::track_event($signature->petition_id, 'verification_resent', array(
                'signature_id' => $signature_id
            ));
        }
        
        return $result;
    }
    
    /**
     * Get verification by signature ID (admin function)
     */
    public function get_verification_by_signature_id($signature_id) {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        global $wpdb;
        $signatures_table = $wpdb->prefix . 'petition_signatures';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $signatures_table WHERE id = %d",
            $signature_id
        ));
    }
    
    /**
     * Manual verification by admin
     */
    public function manual_verify_signature($signature_id, $admin_notes = '') {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        global $wpdb;
        $signatures_table = $wpdb->prefix . 'petition_signatures';
        
        $result = $wpdb->update(
            $signatures_table,
            array(
                'status' => 'verified',
                'is_verified' => 1,
                'verified_at' => current_time('mysql'),
                'verification_method' => 'manual',
                'verification_notes' => sanitize_text_field($admin_notes)
            ),
            array('id' => $signature_id),
            array('%s', '%d', '%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result) {
            // Get petition ID to update counts
            $petition_id = $wpdb->get_var($wpdb->prepare(
                "SELECT petition_id FROM $signatures_table WHERE id = %d",
                $signature_id
            ));
            
            if ($petition_id) {
                $this->update_petition_counts($petition_id);
                $this->check_milestones($petition_id);
                
                // Track manual verification event
                EPP_Database::track_event($petition_id, 'signature_manual_verified', array(
                    'signature_id' => $signature_id,
                    'admin_notes' => $admin_notes
                ));
            }
        }
        
        return $result !== false;
    }
    
    /**
     * Check if verification has expired
     */
    public function is_verification_expired($signature_id) {
        global $wpdb;
        $signatures_table = $wpdb->prefix . 'petition_signatures';
        
        $signature = $wpdb->get_row($wpdb->prepare(
            "SELECT verification_sent_at FROM $signatures_table WHERE id = %d",
            $signature_id
        ));
        
        if (!$signature || !$signature->verification_sent_at) {
            return false;
        }
        
        $settings = get_option('epp_verification_settings', array());
        $expiry_hours = $settings['expiry'] ?? 24;
        
        $sent_time = strtotime($signature->verification_sent_at);
        $expiry_time = $sent_time + ($expiry_hours * 3600);
        
        return time() > $expiry_time;
    }
    
    /**
     * Clean up expired verifications
     */
    public function cleanup_expired_verifications() {
        global $wpdb;
        $signatures_table = $wpdb->prefix . 'petition_signatures';
        
        $settings = get_option('epp_verification_settings', array());
        $expiry_hours = $settings['expiry'] ?? 24;
        
        $expiry_date = date('Y-m-d H:i:s', time() - ($expiry_hours * 3600));
        
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE $signatures_table 
             SET status = 'expired' 
             WHERE status = 'pending' 
             AND verification_sent_at < %s 
             AND verification_sent_at IS NOT NULL",
            $expiry_date
        ));
        
        return $result;
    }
}
