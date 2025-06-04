<?php
/**
 * Signature Manager for Environmental Platform Petitions
 * 
 * Handles petition signature collection, validation, and management
 * 
 * @package Environmental_Platform_Petitions
 * @since 1.0.0 - Phase 35
 */

if (!defined('ABSPATH')) {
    exit;
}

class EPP_Signature_Manager {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_footer', array($this, 'add_signature_modal'));
        add_filter('the_content', array($this, 'add_signature_form_to_petitions'));
    }
    
    /**
     * Add a signature to a petition
     */
    public function add_signature($petition_id, $signer_data) {
        global $wpdb;
        
        // Validate required fields
        $validation = $this->validate_signature_data($signer_data);
        if (!$validation['valid']) {
            return array(
                'success' => false,
                'message' => $validation['message']
            );
        }
        
        // Check for duplicate signatures
        if ($this->is_duplicate_signature($petition_id, $signer_data['email'])) {
            return array(
                'success' => false,
                'message' => __('You have already signed this petition.', 'environmental-platform-petitions')
            );
        }
        
        // Check petition settings
        $petition_settings = $this->get_petition_settings($petition_id);
        
        // Prepare signature data
        $signature_data = array(
            'petition_id' => $petition_id,
            'user_id' => get_current_user_id() ?: null,
            'signer_name' => sanitize_text_field($signer_data['name']),
            'signer_email' => sanitize_email($signer_data['email']),
            'signer_phone' => sanitize_text_field($signer_data['phone'] ?? ''),
            'signer_location' => sanitize_text_field($signer_data['location'] ?? ''),
            'signature_comment' => sanitize_textarea_field($signer_data['comment'] ?? ''),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'is_anonymous' => intval($signer_data['anonymous'] ?? 0),
            'source' => sanitize_text_field($signer_data['source'] ?? 'website'),
            'campaign_source' => sanitize_text_field($signer_data['campaign_source'] ?? ''),
            'signature_date' => current_time('mysql')
        );
        
        // Set initial status based on verification requirements
        if ($petition_settings['signature_verification_required']) {
            $signature_data['status'] = 'pending';
            $signature_data['verification_code'] = $this->generate_verification_code();
            $signature_data['verification_sent_at'] = current_time('mysql');
        } else {
            $signature_data['status'] = 'verified';
            $signature_data['is_verified'] = 1;
            $signature_data['verified_at'] = current_time('mysql');
        }
        
        $signatures_table = $wpdb->prefix . 'petition_signatures';
        
        // Insert signature
        $result = $wpdb->insert($signatures_table, $signature_data);
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => __('Error saving signature. Please try again.', 'environmental-platform-petitions')
            );
        }
        
        $signature_id = $wpdb->insert_id;
        
        // Send verification email if required
        if ($petition_settings['signature_verification_required']) {
            $this->send_verification_email($signature_id);
        }
        
        // Update petition signature count
        $this->update_petition_signature_count($petition_id);
        
        // Check for milestones
        $this->check_milestones($petition_id);
        
        // Track analytics event
        EPP_Database::track_event($petition_id, 'signature_added', array(
            'signature_id' => $signature_id,
            'verification_required' => $petition_settings['signature_verification_required']
        ));
        
        // Send notification to petition organizer if enabled
        if ($petition_settings['email_notifications']) {
            $this->notify_organizer($petition_id, $signature_data);
        }
        
        return array(
            'success' => true,
            'message' => $petition_settings['signature_verification_required'] 
                ? __('Thank you for signing! Please check your email to verify your signature.', 'environmental-platform-petitions')
                : __('Thank you for signing this petition!', 'environmental-platform-petitions'),
            'signature_id' => $signature_id,
            'requires_verification' => $petition_settings['signature_verification_required']
        );
    }
    
    /**
     * Validate signature data
     */
    private function validate_signature_data($signer_data) {
        // Required fields
        if (empty($signer_data['name'])) {
            return array(
                'valid' => false,
                'message' => __('Name is required.', 'environmental-platform-petitions')
            );
        }
        
        if (empty($signer_data['email']) || !is_email($signer_data['email'])) {
            return array(
                'valid' => false,
                'message' => __('Valid email address is required.', 'environmental-platform-petitions')
            );
        }
        
        // Spam check
        if ($this->is_spam_signature($signer_data)) {
            return array(
                'valid' => false,
                'message' => __('Your signature was flagged as spam. Please contact support if this is an error.', 'environmental-platform-petitions')
            );
        }
        
        return array('valid' => true);
    }
    
    /**
     * Check if signature is duplicate
     */
    private function is_duplicate_signature($petition_id, $email) {
        global $wpdb;
        
        $signatures_table = $wpdb->prefix . 'petition_signatures';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $signatures_table WHERE petition_id = %d AND signer_email = %s",
            $petition_id,
            $email
        ));
        
        return $count > 0;
    }
    
    /**
     * Basic spam detection
     */
    private function is_spam_signature($signer_data) {
        // Check for suspicious patterns
        $suspicious_patterns = array(
            '/\b(viagra|cialis|casino|poker)\b/i',
            '/\b\d{4,}\b.*\b\d{4,}\b/', // Multiple long numbers
            '/[^\w\s@.-].*[^\w\s@.-].*[^\w\s@.-]/', // Too many special characters
        );
        
        $text_to_check = $signer_data['name'] . ' ' . $signer_data['comment'];
        
        foreach ($suspicious_patterns as $pattern) {
            if (preg_match($pattern, $text_to_check)) {
                return true;
            }
        }
        
        // Check email domain against known spam domains
        $spam_domains = array('tempmail.com', '10minutemail.com', 'guerrillamail.com');
        $email_domain = substr(strrchr($signer_data['email'], "@"), 1);
        
        if (in_array($email_domain, $spam_domains)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get petition settings
     */
    private function get_petition_settings($petition_id) {
        return array(
            'signature_verification_required' => get_post_meta($petition_id, '_signature_verification_required', true),
            'email_notifications' => get_post_meta($petition_id, '_email_notifications', true),
            'allow_anonymous_signatures' => get_post_meta($petition_id, '_allow_anonymous_signatures', true),
            'allow_comments' => get_post_meta($petition_id, '_allow_comments', true)
        );
    }
    
    /**
     * Generate verification code
     */
    private function generate_verification_code() {
        return wp_generate_password(32, false);
    }
    
    /**
     * Send verification email
     */
    private function send_verification_email($signature_id) {
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
        
        $message = sprintf(
            __("Hello %s,\n\nThank you for signing the petition \"%s\".\n\nTo complete your signature, please click the link below:\n%s\n\nIf you didn't sign this petition, please ignore this email.\n\nBest regards,\nThe Environmental Platform Team", 'environmental-platform-petitions'),
            $signature->signer_name,
            $petition->post_title,
            $verification_url
        );
        
        return wp_mail($signature->signer_email, $subject, $message);
    }
    
    /**
     * Update petition signature count
     */
    private function update_petition_signature_count($petition_id) {
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
    }
    
    /**
     * Check and trigger milestones
     */
    private function check_milestones($petition_id) {
        $current_signatures = get_post_meta($petition_id, '_current_signatures', true);
        $milestones = EPP_Database::get_milestones($petition_id);
        
        foreach ($milestones as $milestone) {
            if ($current_signatures >= $milestone['milestone_value'] && !$milestone['reached_at']) {
                $this->trigger_milestone($petition_id, $milestone);
            }
        }
    }
    
    /**
     * Trigger milestone achievement
     */
    private function trigger_milestone($petition_id, $milestone) {
        global $wpdb;
        
        $milestones_table = $wpdb->prefix . 'petition_milestones';
        
        // Mark milestone as reached
        $wpdb->update(
            $milestones_table,
            array('reached_at' => current_time('mysql')),
            array('id' => $milestone['id']),
            array('%s'),
            array('%d')
        );
        
        // Track analytics event
        EPP_Database::track_event($petition_id, 'milestone_reached', array(
            'milestone_id' => $milestone['id'],
            'milestone_value' => $milestone['milestone_value'],
            'milestone_type' => $milestone['milestone_type']
        ));
        
        // Auto-share milestone if enabled
        $auto_share = get_post_meta($petition_id, '_auto_share_milestones', true);
        if ($auto_share) {
            $this->auto_share_milestone($petition_id, $milestone);
        }
        
        // Send milestone notification
        $this->send_milestone_notification($petition_id, $milestone);
    }
    
    /**
     * Get signatures for a petition
     */
    public function get_signatures($petition_id, $limit = 20, $offset = 0, $verified_only = true) {
        global $wpdb;
        
        $signatures_table = $wpdb->prefix . 'petition_signatures';
        
        $where = array('petition_id = %d');
        $params = array($petition_id);
        
        if ($verified_only) {
            $where[] = 'status = %s';
            $params[] = 'verified';
        }
        
        $where_clause = 'WHERE ' . implode(' AND ', $where);
        
        $query = "
            SELECT 
                id,
                signer_name,
                signer_location,
                signature_comment,
                signature_date,
                is_anonymous
            FROM $signatures_table 
            $where_clause
            ORDER BY signature_date DESC
            LIMIT %d OFFSET %d
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        return $wpdb->get_results(
            $wpdb->prepare($query, $params),
            ARRAY_A
        );
    }
    
    /**
     * Add signature form to petition content
     */
    public function add_signature_form_to_petitions($content) {
        if (is_singular('env_petition') && is_main_query() && in_the_loop()) {
            $signature_form = $this->render_signature_form(get_the_ID());
            $content .= $signature_form;
        }
        
        return $content;
    }
    
    /**
     * Render signature form
     */
    public function render_signature_form($petition_id, $style = 'default') {
        $petition_settings = $this->get_petition_settings($petition_id);
        $current_signatures = get_post_meta($petition_id, '_current_signatures', true) ?: 0;
        $target_signatures = get_post_meta($petition_id, '_signature_goal', true) ?: 1000;
        $progress_percentage = min(100, ($current_signatures / $target_signatures) * 100);
        
        ob_start();
        ?>
        <div class="epp-signature-form-container" id="petition-sign-form">
            <div class="epp-progress-section">
                <div class="epp-signature-count">
                    <strong><?php echo number_format($current_signatures); ?></strong>
                    <?php _e('signatures', 'environmental-platform-petitions'); ?>
                </div>
                <div class="epp-progress-bar">
                    <div class="epp-progress-fill" style="width: <?php echo $progress_percentage; ?>%"></div>
                </div>
                <div class="epp-target-info">
                    <?php printf(__('Goal: %s signatures', 'environmental-platform-petitions'), number_format($target_signatures)); ?>
                </div>
            </div>
            
            <form class="epp-signature-form" data-petition-id="<?php echo $petition_id; ?>">
                <?php wp_nonce_field('epp_nonce', 'epp_nonce'); ?>
                
                <div class="epp-form-row">
                    <div class="epp-form-field">
                        <label for="signer_name"><?php _e('Full Name *', 'environmental-platform-petitions'); ?></label>
                        <input type="text" id="signer_name" name="name" required>
                    </div>
                    <div class="epp-form-field">
                        <label for="signer_email"><?php _e('Email Address *', 'environmental-platform-petitions'); ?></label>
                        <input type="email" id="signer_email" name="email" required>
                    </div>
                </div>
                
                <div class="epp-form-row">
                    <div class="epp-form-field">
                        <label for="signer_phone"><?php _e('Phone Number', 'environmental-platform-petitions'); ?></label>
                        <input type="tel" id="signer_phone" name="phone">
                    </div>
                    <div class="epp-form-field">
                        <label for="signer_location"><?php _e('Location', 'environmental-platform-petitions'); ?></label>
                        <input type="text" id="signer_location" name="location" placeholder="<?php _e('City, Country', 'environmental-platform-petitions'); ?>">
                    </div>
                </div>
                
                <?php if ($petition_settings['allow_comments']): ?>
                <div class="epp-form-field">
                    <label for="signature_comment"><?php _e('Why are you signing?', 'environmental-platform-petitions'); ?></label>
                    <textarea id="signature_comment" name="comment" rows="3" placeholder="<?php _e('Share your reason for signing (optional)', 'environmental-platform-petitions'); ?>"></textarea>
                </div>
                <?php endif; ?>
                
                <div class="epp-form-options">
                    <?php if ($petition_settings['allow_anonymous_signatures']): ?>
                    <label class="epp-checkbox">
                        <input type="checkbox" name="anonymous" value="1">
                        <?php _e('Sign anonymously (your name will not be displayed publicly)', 'environmental-platform-petitions'); ?>
                    </label>
                    <?php endif; ?>
                    
                    <label class="epp-checkbox">
                        <input type="checkbox" name="email_updates" value="1">
                        <?php _e('Keep me updated on this petition and similar campaigns', 'environmental-platform-petitions'); ?>
                    </label>
                </div>
                
                <div class="epp-form-submit">
                    <button type="submit" class="epp-sign-button">
                        <span class="epp-button-text"><?php _e('Sign This Petition', 'environmental-platform-petitions'); ?></span>
                        <span class="epp-button-loading" style="display: none;"><?php _e('Signing...', 'environmental-platform-petitions'); ?></span>
                    </button>
                </div>
                
                <div class="epp-form-message"></div>
            </form>
        </div>
        
        <style>
        .epp-signature-form-container {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 2rem;
            margin: 2rem 0;
        }
        
        .epp-progress-section {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .epp-signature-count {
            font-size: 2rem;
            color: #28a745;
            margin-bottom: 0.5rem;
        }
        
        .epp-progress-bar {
            background: #e9ecef;
            height: 10px;
            border-radius: 5px;
            margin: 1rem auto;
            max-width: 400px;
            overflow: hidden;
        }
        
        .epp-progress-fill {
            background: linear-gradient(90deg, #28a745, #20c997);
            height: 100%;
            transition: width 0.3s ease;
        }
        
        .epp-target-info {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .epp-form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .epp-form-field {
            flex: 1;
        }
        
        .epp-form-field label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #495057;
        }
        
        .epp-form-field input,
        .epp-form-field textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .epp-form-field input:focus,
        .epp-form-field textarea:focus {
            outline: none;
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
        
        .epp-form-options {
            margin: 1.5rem 0;
        }
        
        .epp-checkbox {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            cursor: pointer;
        }
        
        .epp-checkbox input {
            margin-right: 0.5rem;
            width: auto;
        }
        
        .epp-sign-button {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .epp-sign-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }
        
        .epp-sign-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .epp-form-message {
            margin-top: 1rem;
            padding: 0.75rem;
            border-radius: 4px;
            display: none;
        }
        
        .epp-form-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .epp-form-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .epp-form-row {
                flex-direction: column;
            }
            
            .epp-signature-form-container {
                padding: 1rem;
            }
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Add signature modal to footer
     */
    public function add_signature_modal() {
        if (is_singular('env_petition')) {
            ?>
            <div id="epp-signature-modal" class="epp-modal" style="display: none;">
                <div class="epp-modal-content">
                    <span class="epp-modal-close">&times;</span>
                    <div id="epp-modal-body"></div>
                </div>
            </div>
            <?php
        }
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Notify organizer of new signature
     */
    private function notify_organizer($petition_id, $signature_data) {
        $organizer = get_post_field('post_author', $petition_id);
        $organizer_email = get_the_author_meta('email', $organizer);
        
        if (!$organizer_email) {
            return;
        }
        
        $petition = get_post($petition_id);
        $current_signatures = get_post_meta($petition_id, '_current_signatures', true);
        
        $subject = sprintf(
            __('New signature for your petition: %s', 'environmental-platform-petitions'),
            $petition->post_title
        );
        
        $message = sprintf(
            __("Hello,\n\nYour petition \"%s\" has received a new signature from %s.\n\nTotal signatures: %d\n\nView petition: %s\n\nBest regards,\nThe Environmental Platform Team", 'environmental-platform-petitions'),
            $petition->post_title,
            $signature_data['signer_name'],
            $current_signatures,
            get_permalink($petition_id)
        );
        
        wp_mail($organizer_email, $subject, $message);
    }
    
    /**
     * Auto-share milestone achievement
     */
    private function auto_share_milestone($petition_id, $milestone) {
        // This could integrate with social media APIs
        // For now, we'll just track the event
        EPP_Database::track_event($petition_id, 'auto_share_milestone', array(
            'milestone_id' => $milestone['id'],
            'milestone_value' => $milestone['milestone_value']
        ));
    }
    
    /**
     * Send milestone notification
     */
    private function send_milestone_notification($petition_id, $milestone) {
        // Get all signers who opted for updates
        global $wpdb;
        
        $signatures_table = $wpdb->prefix . 'petition_signatures';
        
        // This would send emails to signers about milestone achievement
        // Implementation would depend on email service integration
    }
}
