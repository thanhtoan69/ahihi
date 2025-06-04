<?php
/**
 * Settings page for Environmental Platform Petitions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current settings
$settings = get_option('epp_settings', array());
$email_settings = get_option('epp_email_settings', array());
$verification_settings = get_option('epp_verification_settings', array());
$social_settings = get_option('epp_social_settings', array());
?>

<div class="wrap epp-admin-page">
    <h1><?php echo esc_html__('Petition Settings', 'environmental-platform-petitions'); ?></h1>
    
    <div id="epp-notifications"></div>

    <div class="epp-tabs">
        <div class="tab-buttons">
            <button class="tab-button active" data-tab="general"><?php _e('General', 'environmental-platform-petitions'); ?></button>
            <button class="tab-button" data-tab="email"><?php _e('Email', 'environmental-platform-petitions'); ?></button>
            <button class="tab-button" data-tab="verification"><?php _e('Verification', 'environmental-platform-petitions'); ?></button>
            <button class="tab-button" data-tab="social"><?php _e('Social Media', 'environmental-platform-petitions'); ?></button>
            <button class="tab-button" data-tab="advanced"><?php _e('Advanced', 'environmental-platform-petitions'); ?></button>
        </div>

        <form id="epp-settings-form" method="post">
            <?php wp_nonce_field('epp_save_settings', 'epp_settings_nonce'); ?>

            <!-- General Settings -->
            <div class="tab-content active" data-tab="general">
                <div class="epp-settings-section">
                    <h2><?php _e('General Settings', 'environmental-platform-petitions'); ?></h2>
                    
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="default_signature_goal"><?php _e('Default Signature Goal', 'environmental-platform-petitions'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="default_signature_goal" 
                                           name="epp_settings[default_signature_goal]" 
                                           value="<?php echo esc_attr($settings['default_signature_goal'] ?? 1000); ?>" 
                                           min="1" 
                                           class="regular-text" />
                                    <p class="description"><?php _e('Default signature goal for new petitions.', 'environmental-platform-petitions'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="require_verification"><?php _e('Require Verification', 'environmental-platform-petitions'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" 
                                           id="require_verification" 
                                           name="epp_settings[require_verification]" 
                                           value="1" 
                                           <?php checked($settings['require_verification'] ?? 1); ?> />
                                    <label for="require_verification"><?php _e('Require email verification for signatures', 'environmental-platform-petitions'); ?></label>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="allow_anonymous"><?php _e('Anonymous Signatures', 'environmental-platform-petitions'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" 
                                           id="allow_anonymous" 
                                           name="epp_settings[allow_anonymous]" 
                                           value="1" 
                                           <?php checked($settings['allow_anonymous'] ?? 0); ?> />
                                    <label for="allow_anonymous"><?php _e('Allow anonymous signatures (name not displayed publicly)', 'environmental-platform-petitions'); ?></label>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="duplicate_protection"><?php _e('Duplicate Protection', 'environmental-platform-petitions'); ?></label>
                                </th>
                                <td>
                                    <select id="duplicate_protection" name="epp_settings[duplicate_protection]">
                                        <option value="email" <?php selected($settings['duplicate_protection'] ?? 'email', 'email'); ?>><?php _e('By Email', 'environmental-platform-petitions'); ?></option>
                                        <option value="ip" <?php selected($settings['duplicate_protection'] ?? 'email', 'ip'); ?>><?php _e('By IP Address', 'environmental-platform-petitions'); ?></option>
                                        <option value="both" <?php selected($settings['duplicate_protection'] ?? 'email', 'both'); ?>><?php _e('Both Email and IP', 'environmental-platform-petitions'); ?></option>
                                        <option value="none" <?php selected($settings['duplicate_protection'] ?? 'email', 'none'); ?>><?php _e('No Protection', 'environmental-platform-petitions'); ?></option>
                                    </select>
                                    <p class="description"><?php _e('How to prevent duplicate signatures.', 'environmental-platform-petitions'); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="signature_moderation"><?php _e('Signature Moderation', 'environmental-platform-petitions'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" 
                                           id="signature_moderation" 
                                           name="epp_settings[signature_moderation]" 
                                           value="1" 
                                           <?php checked($settings['signature_moderation'] ?? 0); ?> />
                                    <label for="signature_moderation"><?php _e('Require admin approval for signatures', 'environmental-platform-petitions'); ?></label>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="auto_milestones"><?php _e('Auto Milestones', 'environmental-platform-petitions'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="auto_milestones" 
                                           name="epp_settings[auto_milestones]" 
                                           value="<?php echo esc_attr($settings['auto_milestones'] ?? '100,500,1000,5000,10000'); ?>" 
                                           class="regular-text" />
                                    <p class="description"><?php _e('Comma-separated list of automatic milestone values.', 'environmental-platform-petitions'); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Email Settings -->
            <div class="tab-content" data-tab="email">
                <div class="epp-settings-section">
                    <h2><?php _e('Email Settings', 'environmental-platform-petitions'); ?></h2>
                    
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="from_name"><?php _e('From Name', 'environmental-platform-petitions'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="from_name" 
                                           name="epp_email_settings[from_name]" 
                                           value="<?php echo esc_attr($email_settings['from_name'] ?? get_bloginfo('name')); ?>" 
                                           class="regular-text" />
                                    <p class="description"><?php _e('Name used as sender for petition emails.', 'environmental-platform-petitions'); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="from_email"><?php _e('From Email', 'environmental-platform-petitions'); ?></label>
                                </th>
                                <td>
                                    <input type="email" 
                                           id="from_email" 
                                           name="epp_email_settings[from_email]" 
                                           value="<?php echo esc_attr($email_settings['from_email'] ?? get_option('admin_email')); ?>" 
                                           class="regular-text" />
                                    <p class="description"><?php _e('Email address used as sender for petition emails.', 'environmental-platform-petitions'); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="admin_notifications"><?php _e('Admin Notifications', 'environmental-platform-petitions'); ?></label>
                                </th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" 
                                                   name="epp_email_settings[admin_notifications][]" 
                                                   value="new_signature" 
                                                   <?php checked(in_array('new_signature', $email_settings['admin_notifications'] ?? [])); ?> />
                                            <?php _e('New signature received', 'environmental-platform-petitions'); ?>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" 
                                                   name="epp_email_settings[admin_notifications][]" 
                                                   value="milestone_reached" 
                                                   <?php checked(in_array('milestone_reached', $email_settings['admin_notifications'] ?? [])); ?> />
                                            <?php _e('Milestone reached', 'environmental-platform-petitions'); ?>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" 
                                                   name="epp_email_settings[admin_notifications][]" 
                                                   value="goal_reached" 
                                                   <?php checked(in_array('goal_reached', $email_settings['admin_notifications'] ?? [])); ?> />
                                            <?php _e('Goal reached', 'environmental-platform-petitions'); ?>
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="user_notifications"><?php _e('User Notifications', 'environmental-platform-petitions'); ?></label>
                                </th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" 
                                                   name="epp_email_settings[user_notifications][]" 
                                                   value="signature_confirmation" 
                                                   <?php checked(in_array('signature_confirmation', $email_settings['user_notifications'] ?? ['signature_confirmation'])); ?> />
                                            <?php _e('Signature confirmation', 'environmental-platform-petitions'); ?>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" 
                                                   name="epp_email_settings[user_notifications][]" 
                                                   value="milestone_updates" 
                                                   <?php checked(in_array('milestone_updates', $email_settings['user_notifications'] ?? [])); ?> />
                                            <?php _e('Milestone updates', 'environmental-platform-petitions'); ?>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" 
                                                   name="epp_email_settings[user_notifications][]" 
                                                   value="campaign_updates" 
                                                   <?php checked(in_array('campaign_updates', $email_settings['user_notifications'] ?? [])); ?> />
                                            <?php _e('Campaign updates', 'environmental-platform-petitions'); ?>
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="email_template"><?php _e('Email Template', 'environmental-platform-petitions'); ?></label>
                                </th>
                                <td>
                                    <select id="email_template" name="epp_email_settings[email_template]">
                                        <option value="default" <?php selected($email_settings['email_template'] ?? 'default', 'default'); ?>><?php _e('Default', 'environmental-platform-petitions'); ?></option>
                                        <option value="minimal" <?php selected($email_settings['email_template'] ?? 'default', 'minimal'); ?>><?php _e('Minimal', 'environmental-platform-petitions'); ?></option>
                                        <option value="branded" <?php selected($email_settings['email_template'] ?? 'default', 'branded'); ?>><?php _e('Branded', 'environmental-platform-petitions'); ?></option>
                                    </select>
                                    <p class="description"><?php _e('Choose email template style.', 'environmental-platform-petitions'); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <p class="submit">
                        <button type="button" id="test-email-settings" class="button button-secondary">
                            <?php _e('Send Test Email', 'environmental-platform-petitions'); ?>
                        </button>
                    </p>
                </div>
            </div>

            <!-- Verification Settings -->
            <div class="tab-content" data-tab="verification">
                <div class="epp-settings-section">
                    <h2><?php _e('Verification Settings', 'environmental-platform-petitions'); ?></h2>
                    
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="verification_methods"><?php _e('Verification Methods', 'environmental-platform-petitions'); ?></label>
                                </th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" 
                                                   name="epp_verification_settings[methods][]" 
                                                   value="email" 
                                                   <?php checked(in_array('email', $verification_settings['methods'] ?? ['email'])); ?> />
                                            <?php _e('Email verification', 'environmental-platform-petitions'); ?>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" 
                                                   name="epp_verification_settings[methods][]" 
                                                   value="phone" 
                                                   <?php checked(in_array('phone', $verification_settings['methods'] ?? [])); ?> />
                                            <?php _e('Phone verification (SMS)', 'environmental-platform-petitions'); ?>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" 
                                                   name="epp_verification_settings[methods][]" 
                                                   value="captcha" 
                                                   <?php checked(in_array('captcha', $verification_settings['methods'] ?? [])); ?> />
                                            <?php _e('Captcha verification', 'environmental-platform-petitions'); ?>
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="verification_expiry"><?php _e('Verification Link Expiry', 'environmental-platform-petitions'); ?></label>
                                </th>
                                <td>
                                    <select id="verification_expiry" name="epp_verification_settings[expiry]">
                                        <option value="1" <?php selected($verification_settings['expiry'] ?? 24, 1); ?>><?php _e('1 hour', 'environmental-platform-petitions'); ?></option>
                                        <option value="6" <?php selected($verification_settings['expiry'] ?? 24, 6); ?>><?php _e('6 hours', 'environmental-platform-petitions'); ?></option>
                                        <option value="24" <?php selected($verification_settings['expiry'] ?? 24, 24); ?>><?php _e('24 hours', 'environmental-platform-petitions'); ?></option>
                                        <option value="72" <?php selected($verification_settings['expiry'] ?? 24, 72); ?>><?php _e('72 hours', 'environmental-platform-petitions'); ?></option>
                                        <option value="168" <?php selected($verification_settings['expiry'] ?? 24, 168); ?>><?php _e('1 week', 'environmental-platform-petitions'); ?></option>
                                    </select>
                                    <p class="description"><?php _e('How long verification links remain valid.', 'environmental-platform-petitions'); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="max_verification_attempts"><?php _e('Max Verification Attempts', 'environmental-platform-petitions'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="max_verification_attempts" 
                                           name="epp_verification_settings[max_attempts]" 
                                           value="<?php echo esc_attr($verification_settings['max_attempts'] ?? 3); ?>" 
                                           min="1" 
                                           max="10" 
                                           class="small-text" />
                                    <p class="description"><?php _e('Maximum number of verification attempts allowed.', 'environmental-platform-petitions'); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="auto_approve_verified"><?php _e('Auto-approve Verified', 'environmental-platform-petitions'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" 
                                           id="auto_approve_verified" 
                                           name="epp_verification_settings[auto_approve]" 
                                           value="1" 
                                           <?php checked($verification_settings['auto_approve'] ?? 1); ?> />
                                    <label for="auto_approve_verified"><?php _e('Automatically approve signatures after verification', 'environmental-platform-petitions'); ?></label>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="recaptcha_site_key"><?php _e('reCAPTCHA Site Key', 'environmental-platform-petitions'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="recaptcha_site_key" 
                                           name="epp_verification_settings[recaptcha_site_key]" 
                                           value="<?php echo esc_attr($verification_settings['recaptcha_site_key'] ?? ''); ?>" 
                                           class="regular-text" />
                                    <p class="description"><?php _e('Google reCAPTCHA v3 site key for spam protection.', 'environmental-platform-petitions'); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="recaptcha_secret_key"><?php _e('reCAPTCHA Secret Key', 'environmental-platform-petitions'); ?></label>
                                </th>
                                <td>
                                    <input type="password" 
                                           id="recaptcha_secret_key" 
                                           name="epp_verification_settings[recaptcha_secret_key]" 
                                           value="<?php echo esc_attr($verification_settings['recaptcha_secret_key'] ?? ''); ?>" 
                                           class="regular-text" />
                                    <p class="description"><?php _e('Google reCAPTCHA v3 secret key.', 'environmental-platform-petitions'); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Social Media Settings -->
            <div class="tab-content" data-tab="social">
                <div class="epp-settings-section">
                    <h2><?php _e('Social Media Settings', 'environmental-platform-petitions'); ?></h2>
                    
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="enable_sharing"><?php _e('Enable Social Sharing', 'environmental-platform-petitions'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" 
                                           id="enable_sharing" 
                                           name="epp_social_settings[enable_sharing]" 
                                           value="1" 
                                           <?php checked($social_settings['enable_sharing'] ?? 1); ?> />
                                    <label for="enable_sharing"><?php _e('Enable social media sharing buttons', 'environmental-platform-petitions'); ?></label>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="share_platforms"><?php _e('Sharing Platforms', 'environmental-platform-petitions'); ?></label>
                                </th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" 
                                                   name="epp_social_settings[platforms][]" 
                                                   value="facebook" 
                                                   <?php checked(in_array('facebook', $social_settings['platforms'] ?? ['facebook', 'twitter', 'linkedin', 'whatsapp'])); ?> />
                                            <?php _e('Facebook', 'environmental-platform-petitions'); ?>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" 
                                                   name="epp_social_settings[platforms][]" 
                                                   value="twitter" 
                                                   <?php checked(in_array('twitter', $social_settings['platforms'] ?? ['facebook', 'twitter', 'linkedin', 'whatsapp'])); ?> />
                                            <?php _e('Twitter/X', 'environmental-platform-petitions'); ?>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" 
                                                   name="epp_social_settings[platforms][]" 
                                                   value="linkedin" 
                                                   <?php checked(in_array('linkedin', $social_settings['platforms'] ?? ['facebook', 'twitter', 'linkedin', 'whatsapp'])); ?> />
                                            <?php _e('LinkedIn', 'environmental-platform-petitions'); ?>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" 
                                                   name="epp_social_settings[platforms][]" 
                                                   value="whatsapp" 
                                                   <?php checked(in_array('whatsapp', $social_settings['platforms'] ?? ['facebook', 'twitter', 'linkedin', 'whatsapp'])); ?> />
                                            <?php _e('WhatsApp', 'environmental-platform-petitions'); ?>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" 
                                                   name="epp_social_settings[platforms][]" 
                                                   value="telegram" 
                                                   <?php checked(in_array('telegram', $social_settings['platforms'] ?? [])); ?> />
                                            <?php _e('Telegram', 'environmental-platform-petitions'); ?>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" 
                                                   name="epp_social_settings[platforms][]" 
                                                   value="email" 
                                                   <?php checked(in_array('email', $social_settings['platforms'] ?? [])); ?> />
                                            <?php _e('Email', 'environmental-platform-petitions'); ?>
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="share_text_template"><?php _e('Share Text Template', 'environmental-platform-petitions'); ?></label>
                                </th>
                                <td>
                                    <textarea id="share_text_template" 
                                              name="epp_social_settings[share_text_template]" 
                                              rows="3" 
                                              cols="50" 
                                              class="large-text"><?php echo esc_textarea($social_settings['share_text_template'] ?? 'I just signed this important petition: {petition_title}. Please join me! {petition_url}'); ?></textarea>
                                    <p class="description"><?php _e('Template for share text. Use {petition_title}, {petition_url}, {signature_count} as placeholders.', 'environmental-platform-petitions'); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="track_shares"><?php _e('Track Shares', 'environmental-platform-petitions'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" 
                                           id="track_shares" 
                                           name="epp_social_settings[track_shares]" 
                                           value="1" 
                                           <?php checked($social_settings['track_shares'] ?? 1); ?> />
                                    <label for="track_shares"><?php _e('Track social media shares for analytics', 'environmental-platform-petitions'); ?></label>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="auto_add_buttons"><?php _e('Auto-add Share Buttons', 'environmental-platform-petitions'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" 
                                           id="auto_add_buttons" 
                                           name="epp_social_settings[auto_add_buttons]" 
                                           value="1" 
                                           <?php checked($social_settings['auto_add_buttons'] ?? 1); ?> />
                                    <label for="auto_add_buttons"><?php _e('Automatically add share buttons to petition pages', 'environmental-platform-petitions'); ?></label>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Advanced Settings -->
            <div class="tab-content" data-tab="advanced">
                <div class="epp-settings-section">
                    <h2><?php _e('Advanced Settings', 'environmental-platform-petitions'); ?></h2>
                    
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="enable_analytics"><?php _e('Enable Analytics', 'environmental-platform-petitions'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" 
                                           id="enable_analytics" 
                                           name="epp_settings[enable_analytics]" 
                                           value="1" 
                                           <?php checked($settings['enable_analytics'] ?? 1); ?> />
                                    <label for="enable_analytics"><?php _e('Enable petition analytics tracking', 'environmental-platform-petitions'); ?></label>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="analytics_retention"><?php _e('Analytics Data Retention', 'environmental-platform-petitions'); ?></label>
                                </th>
                                <td>
                                    <select id="analytics_retention" name="epp_settings[analytics_retention]">
                                        <option value="30" <?php selected($settings['analytics_retention'] ?? 365, 30); ?>><?php _e('30 days', 'environmental-platform-petitions'); ?></option>
                                        <option value="90" <?php selected($settings['analytics_retention'] ?? 365, 90); ?>><?php _e('90 days', 'environmental-platform-petitions'); ?></option>
                                        <option value="365" <?php selected($settings['analytics_retention'] ?? 365, 365); ?>><?php _e('1 year', 'environmental-platform-petitions'); ?></option>
                                        <option value="730" <?php selected($settings['analytics_retention'] ?? 365, 730); ?>><?php _e('2 years', 'environmental-platform-petitions'); ?></option>
                                        <option value="0" <?php selected($settings['analytics_retention'] ?? 365, 0); ?>><?php _e('Forever', 'environmental-platform-petitions'); ?></option>
                                    </select>
                                    <p class="description"><?php _e('How long to keep analytics data.', 'environmental-platform-petitions'); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="cache_duration"><?php _e('Cache Duration', 'environmental-platform-petitions'); ?></label>
                                </th>
                                <td>
                                    <select id="cache_duration" name="epp_settings[cache_duration]">
                                        <option value="300" <?php selected($settings['cache_duration'] ?? 900, 300); ?>><?php _e('5 minutes', 'environmental-platform-petitions'); ?></option>
                                        <option value="900" <?php selected($settings['cache_duration'] ?? 900, 900); ?>><?php _e('15 minutes', 'environmental-platform-petitions'); ?></option>
                                        <option value="1800" <?php selected($settings['cache_duration'] ?? 900, 1800); ?>><?php _e('30 minutes', 'environmental-platform-petitions'); ?></option>
                                        <option value="3600" <?php selected($settings['cache_duration'] ?? 900, 3600); ?>><?php _e('1 hour', 'environmental-platform-petitions'); ?></option>
                                        <option value="0" <?php selected($settings['cache_duration'] ?? 900, 0); ?>><?php _e('Disable cache', 'environmental-platform-petitions'); ?></option>
                                    </select>
                                    <p class="description"><?php _e('How long to cache petition data for performance.', 'environmental-platform-petitions'); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="rate_limiting"><?php _e('Rate Limiting', 'environmental-platform-petitions'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" 
                                           id="rate_limiting" 
                                           name="epp_settings[rate_limiting]" 
                                           value="1" 
                                           <?php checked($settings['rate_limiting'] ?? 1); ?> />
                                    <label for="rate_limiting"><?php _e('Enable rate limiting for signature submissions', 'environmental-platform-petitions'); ?></label>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="rate_limit_window"><?php _e('Rate Limit Window', 'environmental-platform-petitions'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="rate_limit_window" 
                                           name="epp_settings[rate_limit_window]" 
                                           value="<?php echo esc_attr($settings['rate_limit_window'] ?? 60); ?>" 
                                           min="1" 
                                           class="small-text" />
                                    <span><?php _e('seconds', 'environmental-platform-petitions'); ?></span>
                                    <p class="description"><?php _e('Time window for rate limiting.', 'environmental-platform-petitions'); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="rate_limit_attempts"><?php _e('Max Attempts per Window', 'environmental-platform-petitions'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="rate_limit_attempts" 
                                           name="epp_settings[rate_limit_attempts]" 
                                           value="<?php echo esc_attr($settings['rate_limit_attempts'] ?? 3); ?>" 
                                           min="1" 
                                           class="small-text" />
                                    <p class="description"><?php _e('Maximum signature attempts allowed per window.', 'environmental-platform-petitions'); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="enable_webhooks"><?php _e('Enable Webhooks', 'environmental-platform-petitions'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" 
                                           id="enable_webhooks" 
                                           name="epp_settings[enable_webhooks]" 
                                           value="1" 
                                           <?php checked($settings['enable_webhooks'] ?? 0); ?> />
                                    <label for="enable_webhooks"><?php _e('Enable webhook notifications for external integrations', 'environmental-platform-petitions'); ?></label>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="webhook_url"><?php _e('Webhook URL', 'environmental-platform-petitions'); ?></label>
                                </th>
                                <td>
                                    <input type="url" 
                                           id="webhook_url" 
                                           name="epp_settings[webhook_url]" 
                                           value="<?php echo esc_attr($settings['webhook_url'] ?? ''); ?>" 
                                           class="regular-text" />
                                    <p class="description"><?php _e('URL to send webhook notifications to.', 'environmental-platform-petitions'); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="debug_mode"><?php _e('Debug Mode', 'environmental-platform-petitions'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" 
                                           id="debug_mode" 
                                           name="epp_settings[debug_mode]" 
                                           value="1" 
                                           <?php checked($settings['debug_mode'] ?? 0); ?> />
                                    <label for="debug_mode"><?php _e('Enable debug logging (for troubleshooting)', 'environmental-platform-petitions'); ?></label>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="epp-settings-section">
                    <h3><?php _e('Database Maintenance', 'environmental-platform-petitions'); ?></h3>
                    <p><?php _e('These actions will help maintain your petition database.', 'environmental-platform-petitions'); ?></p>
                    
                    <p class="submit">
                        <button type="button" id="cleanup-analytics" class="button button-secondary">
                            <?php _e('Cleanup Old Analytics Data', 'environmental-platform-petitions'); ?>
                        </button>
                        <button type="button" id="regenerate-stats" class="button button-secondary">
                            <?php _e('Regenerate Statistics', 'environmental-platform-petitions'); ?>
                        </button>
                        <button type="button" id="optimize-database" class="button button-secondary">
                            <?php _e('Optimize Database Tables', 'environmental-platform-petitions'); ?>
                        </button>
                    </p>
                </div>
            </div>

            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save Settings', 'environmental-platform-petitions'); ?>">
                <button type="button" id="reset-settings" class="button button-secondary">
                    <?php _e('Reset to Defaults', 'environmental-platform-petitions'); ?>
                </button>
            </p>
        </form>
    </div>
</div>

<script type="text/javascript">
// Add settings-specific JavaScript variables
var epp_admin = epp_admin || {};
epp_admin.settings_page = true;
</script>
