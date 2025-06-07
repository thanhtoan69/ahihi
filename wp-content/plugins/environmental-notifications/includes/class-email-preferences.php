<?php

/**
 * Email Preferences Handler for Environmental Platform
 * Manages user email notification preferences and sends email notifications
 */
class EN_Email_Preferences {

    private $preferences_table;
    private $default_preferences;

    public function __construct() {
        global $wpdb;
        $this->preferences_table = $wpdb->prefix . 'en_email_preferences';
        
        $this->default_preferences = array(
            'waste_report' => array(
                'enabled' => true,
                'frequency' => 'immediate',
                'description' => __('Notifications about waste reports and recycling activities', 'environmental-notifications')
            ),
            'environmental_event' => array(
                'enabled' => true,
                'frequency' => 'immediate',
                'description' => __('New environmental events and activities in your area', 'environmental-notifications')
            ),
            'achievement' => array(
                'enabled' => true,
                'frequency' => 'immediate',
                'description' => __('Achievements and milestones you have earned', 'environmental-notifications')
            ),
            'forum_post' => array(
                'enabled' => true,
                'frequency' => 'daily',
                'description' => __('New posts and replies in environmental forums', 'environmental-notifications')
            ),
            'petition_signed' => array(
                'enabled' => true,
                'frequency' => 'immediate',
                'description' => __('Confirmations when you sign environmental petitions', 'environmental-notifications')
            ),
            'newsletter' => array(
                'enabled' => true,
                'frequency' => 'weekly',
                'description' => __('Weekly environmental news and platform updates', 'environmental-notifications')
            ),
            'carbon_tracking' => array(
                'enabled' => true,
                'frequency' => 'daily',
                'description' => __('Daily carbon footprint summaries and tips', 'environmental-notifications')
            ),
            'community_updates' => array(
                'enabled' => true,
                'frequency' => 'weekly',
                'description' => __('Updates from your environmental community', 'environmental-notifications')
            ),
            'donation_receipt' => array(
                'enabled' => true,
                'frequency' => 'immediate',
                'description' => __('Donation receipts and fundraising updates', 'environmental-notifications')
            ),
            'item_exchange' => array(
                'enabled' => true,
                'frequency' => 'immediate',
                'description' => __('Item exchange requests and confirmations', 'environmental-notifications')
            )
        );
    }

    /**
     * Get user email preferences
     */
    public function get_user_preferences($user_id) {
        global $wpdb;

        $preferences = $wpdb->get_results($wpdb->prepare(
            "SELECT preference_key, preference_value, is_enabled, frequency 
             FROM {$this->preferences_table} 
             WHERE user_id = %d",
            $user_id
        ));

        $user_prefs = array();
        foreach ($preferences as $pref) {
            $user_prefs[$pref->preference_key] = array(
                'value' => $pref->preference_value,
                'enabled' => (bool) $pref->is_enabled,
                'frequency' => $pref->frequency
            );
        }

        // Merge with defaults for missing preferences
        foreach ($this->default_preferences as $key => $default) {
            if (!isset($user_prefs[$key])) {
                $user_prefs[$key] = $default;
            } else {
                $user_prefs[$key]['description'] = $default['description'];
            }
        }

        return $user_prefs;
    }

    /**
     * Update user preference
     */
    public function update_user_preference($user_id, $preference_key, $is_enabled, $frequency = 'immediate', $value = '') {
        global $wpdb;

        $data = array(
            'user_id' => $user_id,
            'preference_key' => $preference_key,
            'preference_value' => $value,
            'is_enabled' => $is_enabled ? 1 : 0,
            'frequency' => $frequency,
            'updated_at' => current_time('mysql')
        );

        // Check if preference exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->preferences_table} WHERE user_id = %d AND preference_key = %s",
            $user_id,
            $preference_key
        ));

        if ($existing) {
            return $wpdb->update(
                $this->preferences_table,
                $data,
                array('id' => $existing)
            );
        } else {
            $data['created_at'] = current_time('mysql');
            return $wpdb->insert($this->preferences_table, $data);
        }
    }

    /**
     * Check if notification should be sent based on user preferences
     */
    public function should_send_notification($user_id, $notification_type) {
        $preferences = $this->get_user_preferences($user_id);
        
        if (!isset($preferences[$notification_type])) {
            return true; // Default to sending if preference not found
        }

        return $preferences[$notification_type]['enabled'];
    }

    /**
     * Send email notification
     */
    public function send_email_notification($notification) {
        if (!$this->should_send_notification($notification->user_id, $notification->type)) {
            return false;
        }

        $user = get_user_by('id', $notification->user_id);
        if (!$user) {
            return false;
        }

        $template = new EN_Notification_Templates();
        $email_content = $template->get_email_template($notification);

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_option('blogname') . ' <' . get_option('admin_email') . '>'
        );

        $subject = $this->get_email_subject($notification);
        
        return wp_mail($user->user_email, $subject, $email_content, $headers);
    }

    /**
     * Get email subject based on notification type
     */
    private function get_email_subject($notification) {
        $site_name = get_option('blogname');
        
        switch ($notification->type) {
            case 'waste_report':
                return sprintf(__('[%s] Your waste report has been submitted', 'environmental-notifications'), $site_name);
                
            case 'environmental_event':
                return sprintf(__('[%s] New environmental event: %s', 'environmental-notifications'), $site_name, $notification->title);
                
            case 'achievement':
                return sprintf(__('[%s] Achievement unlocked: %s', 'environmental-notifications'), $site_name, $notification->title);
                
            case 'forum_post':
                return sprintf(__('[%s] New forum activity', 'environmental-notifications'), $site_name);
                
            case 'petition_signed':
                return sprintf(__('[%s] Thank you for signing the petition', 'environmental-notifications'), $site_name);
                
            case 'newsletter':
                return sprintf(__('[%s] Weekly Environmental Newsletter', 'environmental-notifications'), $site_name);
                
            case 'carbon_tracking':
                return sprintf(__('[%s] Your daily carbon footprint summary', 'environmental-notifications'), $site_name);
                
            case 'community_updates':
                return sprintf(__('[%s] Community updates for this week', 'environmental-notifications'), $site_name);
                
            case 'donation_receipt':
                return sprintf(__('[%s] Donation receipt and thank you', 'environmental-notifications'), $site_name);
                
            case 'item_exchange':
                return sprintf(__('[%s] Item exchange update', 'environmental-notifications'), $site_name);
                
            default:
                return sprintf(__('[%s] %s', 'environmental-notifications'), $site_name, $notification->title);
        }
    }

    /**
     * Display user preferences in profile
     */
    public function display_user_preferences($user) {
        $preferences = $this->get_user_preferences($user->ID);
        ?>
        <h3><?php _e('Email Notification Preferences', 'environmental-notifications'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Notification Settings', 'environmental-notifications'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text">
                            <span><?php _e('Email Notification Preferences', 'environmental-notifications'); ?></span>
                        </legend>
                        
                        <?php foreach ($preferences as $key => $preference): ?>
                        <div class="en-preference-row" style="margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            <label style="display: block; font-weight: bold; margin-bottom: 5px;">
                                <input type="checkbox" 
                                       name="en_preferences[<?php echo esc_attr($key); ?>][enabled]" 
                                       value="1" 
                                       <?php checked($preference['enabled']); ?> />
                                <?php echo esc_html($this->get_preference_label($key)); ?>
                            </label>
                            
                            <p style="margin: 5px 0; color: #666; font-size: 0.9em;">
                                <?php echo esc_html($preference['description']); ?>
                            </p>
                            
                            <label style="font-size: 0.9em;">
                                <?php _e('Frequency:', 'environmental-notifications'); ?>
                                <select name="en_preferences[<?php echo esc_attr($key); ?>][frequency]" style="margin-left: 5px;">
                                    <option value="immediate" <?php selected($preference['frequency'], 'immediate'); ?>>
                                        <?php _e('Immediate', 'environmental-notifications'); ?>
                                    </option>
                                    <option value="hourly" <?php selected($preference['frequency'], 'hourly'); ?>>
                                        <?php _e('Hourly', 'environmental-notifications'); ?>
                                    </option>
                                    <option value="daily" <?php selected($preference['frequency'], 'daily'); ?>>
                                        <?php _e('Daily', 'environmental-notifications'); ?>
                                    </option>
                                    <option value="weekly" <?php selected($preference['frequency'], 'weekly'); ?>>
                                        <?php _e('Weekly', 'environmental-notifications'); ?>
                                    </option>
                                    <option value="never" <?php selected($preference['frequency'], 'never'); ?>>
                                        <?php _e('Never', 'environmental-notifications'); ?>
                                    </option>
                                </select>
                            </label>
                        </div>
                        <?php endforeach; ?>
                        
                        <div style="margin-top: 15px; padding: 10px; background: #f0f8e7; border-radius: 5px;">
                            <strong><?php _e('Global Email Settings:', 'environmental-notifications'); ?></strong>
                            <label style="display: block; margin-top: 5px;">
                                <input type="checkbox" 
                                       name="en_global_email_enabled" 
                                       value="1" 
                                       <?php checked(get_user_meta($user->ID, 'en_global_email_enabled', true) !== '0'); ?> />
                                <?php _e('Enable all email notifications', 'environmental-notifications'); ?>
                            </label>
                        </div>
                    </fieldset>
                </td>
            </tr>
        </table>
        
        <style>
        .en-preference-row input[type="checkbox"] {
            margin-right: 8px;
        }
        .en-preference-row select {
            padding: 2px 5px;
            border: 1px solid #ccc;
            border-radius: 3px;
        }
        </style>
        <?php
    }

    /**
     * Save user preferences from profile
     */
    public function save_user_preferences($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }

        // Save global email setting
        if (isset($_POST['en_global_email_enabled'])) {
            update_user_meta($user_id, 'en_global_email_enabled', '1');
        } else {
            update_user_meta($user_id, 'en_global_email_enabled', '0');
        }

        // Save individual preferences
        if (isset($_POST['en_preferences']) && is_array($_POST['en_preferences'])) {
            foreach ($_POST['en_preferences'] as $key => $settings) {
                $enabled = isset($settings['enabled']) ? true : false;
                $frequency = isset($settings['frequency']) ? sanitize_text_field($settings['frequency']) : 'immediate';
                
                $this->update_user_preference($user_id, $key, $enabled, $frequency);
            }
        }
    }

    /**
     * Get preference label
     */
    private function get_preference_label($key) {
        $labels = array(
            'waste_report' => __('Waste Report Notifications', 'environmental-notifications'),
            'environmental_event' => __('Environmental Events', 'environmental-notifications'),
            'achievement' => __('Achievement Notifications', 'environmental-notifications'),
            'forum_post' => __('Forum Activity', 'environmental-notifications'),
            'petition_signed' => __('Petition Confirmations', 'environmental-notifications'),
            'newsletter' => __('Weekly Newsletter', 'environmental-notifications'),
            'carbon_tracking' => __('Carbon Footprint Updates', 'environmental-notifications'),
            'community_updates' => __('Community Updates', 'environmental-notifications'),
            'donation_receipt' => __('Donation Receipts', 'environmental-notifications'),
            'item_exchange' => __('Item Exchange', 'environmental-notifications')
        );

        return isset($labels[$key]) ? $labels[$key] : ucwords(str_replace('_', ' ', $key));
    }

    /**
     * Batch send email notifications based on frequency
     */
    public function process_scheduled_emails() {
        $this->process_hourly_digest();
        $this->process_daily_digest();
        $this->process_weekly_digest();
    }

    /**
     * Process hourly email digest
     */
    private function process_hourly_digest() {
        global $wpdb;
        
        $users = $wpdb->get_results("
            SELECT DISTINCT user_id 
            FROM {$this->preferences_table} 
            WHERE frequency = 'hourly' AND is_enabled = 1
        ");

        foreach ($users as $user) {
            $this->send_digest_email($user->user_id, 'hourly');
        }
    }

    /**
     * Process daily email digest
     */
    private function process_daily_digest() {
        global $wpdb;
        
        $users = $wpdb->get_results("
            SELECT DISTINCT user_id 
            FROM {$this->preferences_table} 
            WHERE frequency = 'daily' AND is_enabled = 1
        ");

        foreach ($users as $user) {
            $this->send_digest_email($user->user_id, 'daily');
        }
    }

    /**
     * Process weekly email digest
     */
    private function process_weekly_digest() {
        global $wpdb;
        
        $users = $wpdb->get_results("
            SELECT DISTINCT user_id 
            FROM {$this->preferences_table} 
            WHERE frequency = 'weekly' AND is_enabled = 1
        ");

        foreach ($users as $user) {
            $this->send_digest_email($user->user_id, 'weekly');
        }
    }

    /**
     * Send digest email
     */
    private function send_digest_email($user_id, $frequency) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }

        // Get notifications for digest based on frequency
        $notifications = $this->get_digest_notifications($user_id, $frequency);
        if (empty($notifications)) {
            return false;
        }

        $template = new EN_Notification_Templates();
        $email_content = $template->get_digest_template($notifications, $frequency);

        $subject = sprintf(
            __('[%s] Your %s environmental digest', 'environmental-notifications'),
            get_option('blogname'),
            $frequency
        );

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_option('blogname') . ' <' . get_option('admin_email') . '>'
        );

        return wp_mail($user->user_email, $subject, $email_content, $headers);
    }

    /**
     * Get notifications for digest
     */
    private function get_digest_notifications($user_id, $frequency) {
        global $wpdb;
        
        $intervals = array(
            'hourly' => '1 HOUR',
            'daily' => '1 DAY',
            'weekly' => '1 WEEK'
        );

        $interval = isset($intervals[$frequency]) ? $intervals[$frequency] : '1 DAY';
        
        $notifications_table = $wpdb->prefix . 'en_notifications';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$notifications_table} 
            WHERE user_id = %d 
            AND created_at >= DATE_SUB(NOW(), INTERVAL {$interval})
            AND is_sent = 0
            ORDER BY created_at DESC
            LIMIT 20
        ", $user_id));
    }

    /**
     * Get email statistics
     */
    public function get_email_stats($days = 30) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(DISTINCT user_id) as total_users,
                SUM(CASE WHEN is_enabled = 1 THEN 1 ELSE 0 END) as enabled_preferences,
                COUNT(*) as total_preferences,
                COUNT(DISTINCT preference_key) as unique_types
            FROM {$this->preferences_table}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $days));
    }

    /**
     * Export user preferences
     */
    public function export_preferences($user_id = null) {
        global $wpdb;
        
        $where_clause = $user_id ? $wpdb->prepare('WHERE user_id = %d', $user_id) : '';
        
        return $wpdb->get_results("
            SELECT user_id, preference_key, is_enabled, frequency, created_at
            FROM {$this->preferences_table}
            {$where_clause}
            ORDER BY user_id, preference_key
        ");
    }
}
