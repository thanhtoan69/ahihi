<?php
/**
 * Settings View
 * 
 * @package Environmental_Email_Marketing
 */

if (!defined('ABSPATH')) {
    exit;
}

$settings = get_option('eem_settings', array());
?>

<div class="eem-admin-page">
    <div class="eem-page-header">
        <div class="eem-page-title">
            <h1>
                <span class="eem-icon">⚙️</span>
                Email Marketing Settings
            </h1>
            <p class="eem-page-description">
                Configure your email marketing system and environmental tracking
            </p>
        </div>
        <div class="eem-page-actions">
            <button class="eem-btn eem-btn-secondary" id="test-connection">
                <span class="dashicons dashicons-admin-network"></span>
                Test Connection
            </button>
            <button class="eem-btn eem-btn-primary" id="save-settings">
                <span class="dashicons dashicons-yes"></span>
                Save Settings
            </button>
        </div>
    </div>

    <form id="eem-settings-form">
        <div class="eem-settings-tabs">
            <button type="button" class="eem-tab-btn active" data-tab="email-providers">Email Providers</button>
            <button type="button" class="eem-tab-btn" data-tab="general-settings">General Settings</button>
            <button type="button" class="eem-tab-btn" data-tab="environmental">Environmental Tracking</button>
            <button type="button" class="eem-tab-btn" data-tab="automation">Automation</button>
            <button type="button" class="eem-tab-btn" data-tab="tracking">Tracking & Analytics</button>
            <button type="button" class="eem-tab-btn" data-tab="advanced">Advanced</button>
        </div>

        <!-- Email Providers Tab -->
        <div class="eem-tab-content active" id="email-providers-tab">
            <div class="eem-settings-section">
                <h3>Email Service Provider Configuration</h3>
                <p class="eem-section-description">
                    Choose and configure your email service provider for sending campaigns.
                </p>
                
                <div class="eem-form-group">
                    <label for="email-provider">Primary Email Provider:</label>
                    <select id="email-provider" name="email_provider" class="eem-select">
                        <option value="wordpress" <?php selected($settings['email_provider'] ?? 'wordpress', 'wordpress'); ?>>WordPress (wp_mail)</option>
                        <option value="mailchimp" <?php selected($settings['email_provider'] ?? '', 'mailchimp'); ?>>Mailchimp</option>
                        <option value="sendgrid" <?php selected($settings['email_provider'] ?? '', 'sendgrid'); ?>>SendGrid</option>
                        <option value="mailgun" <?php selected($settings['email_provider'] ?? '', 'mailgun'); ?>>Mailgun</option>
                        <option value="amazonses" <?php selected($settings['email_provider'] ?? '', 'amazonses'); ?>>Amazon SES</option>
                    </select>
                </div>
                
                <!-- Mailchimp Settings -->
                <div class="eem-provider-settings" id="mailchimp-settings" style="display: none;">
                    <h4>Mailchimp Configuration</h4>
                    <div class="eem-form-group">
                        <label for="mailchimp-api-key">API Key:</label>
                        <input type="password" id="mailchimp-api-key" name="mailchimp_api_key" 
                               value="<?php echo esc_attr($settings['mailchimp_api_key'] ?? ''); ?>" class="eem-input">
                        <p class="eem-field-description">
                            Get your API key from your Mailchimp account settings.
                        </p>
                    </div>
                    <div class="eem-form-group">
                        <label for="mailchimp-list-id">Default List ID:</label>
                        <input type="text" id="mailchimp-list-id" name="mailchimp_list_id" 
                               value="<?php echo esc_attr($settings['mailchimp_list_id'] ?? ''); ?>" class="eem-input">
                    </div>
                </div>
                
                <!-- SendGrid Settings -->
                <div class="eem-provider-settings" id="sendgrid-settings" style="display: none;">
                    <h4>SendGrid Configuration</h4>
                    <div class="eem-form-group">
                        <label for="sendgrid-api-key">API Key:</label>
                        <input type="password" id="sendgrid-api-key" name="sendgrid_api_key" 
                               value="<?php echo esc_attr($settings['sendgrid_api_key'] ?? ''); ?>" class="eem-input">
                    </div>
                    <div class="eem-form-group">
                        <label for="sendgrid-from-email">From Email:</label>
                        <input type="email" id="sendgrid-from-email" name="sendgrid_from_email" 
                               value="<?php echo esc_attr($settings['sendgrid_from_email'] ?? ''); ?>" class="eem-input">
                    </div>
                    <div class="eem-form-group">
                        <label for="sendgrid-from-name">From Name:</label>
                        <input type="text" id="sendgrid-from-name" name="sendgrid_from_name" 
                               value="<?php echo esc_attr($settings['sendgrid_from_name'] ?? ''); ?>" class="eem-input">
                    </div>
                </div>
                
                <!-- Mailgun Settings -->
                <div class="eem-provider-settings" id="mailgun-settings" style="display: none;">
                    <h4>Mailgun Configuration</h4>
                    <div class="eem-form-group">
                        <label for="mailgun-api-key">API Key:</label>
                        <input type="password" id="mailgun-api-key" name="mailgun_api_key" 
                               value="<?php echo esc_attr($settings['mailgun_api_key'] ?? ''); ?>" class="eem-input">
                    </div>
                    <div class="eem-form-group">
                        <label for="mailgun-domain">Domain:</label>
                        <input type="text" id="mailgun-domain" name="mailgun_domain" 
                               value="<?php echo esc_attr($settings['mailgun_domain'] ?? ''); ?>" class="eem-input">
                    </div>
                    <div class="eem-form-group">
                        <label for="mailgun-region">Region:</label>
                        <select id="mailgun-region" name="mailgun_region" class="eem-select">
                            <option value="us" <?php selected($settings['mailgun_region'] ?? 'us', 'us'); ?>>US</option>
                            <option value="eu" <?php selected($settings['mailgun_region'] ?? '', 'eu'); ?>>EU</option>
                        </select>
                    </div>
                </div>
                
                <!-- Amazon SES Settings -->
                <div class="eem-provider-settings" id="amazonses-settings" style="display: none;">
                    <h4>Amazon SES Configuration</h4>
                    <div class="eem-form-group">
                        <label for="amazonses-access-key">Access Key ID:</label>
                        <input type="text" id="amazonses-access-key" name="amazonses_access_key" 
                               value="<?php echo esc_attr($settings['amazonses_access_key'] ?? ''); ?>" class="eem-input">
                    </div>
                    <div class="eem-form-group">
                        <label for="amazonses-secret-key">Secret Access Key:</label>
                        <input type="password" id="amazonses-secret-key" name="amazonses_secret_key" 
                               value="<?php echo esc_attr($settings['amazonses_secret_key'] ?? ''); ?>" class="eem-input">
                    </div>
                    <div class="eem-form-group">
                        <label for="amazonses-region">Region:</label>
                        <select id="amazonses-region" name="amazonses_region" class="eem-select">
                            <option value="us-east-1" <?php selected($settings['amazonses_region'] ?? 'us-east-1', 'us-east-1'); ?>>US East (N. Virginia)</option>
                            <option value="us-west-2" <?php selected($settings['amazonses_region'] ?? '', 'us-west-2'); ?>>US West (Oregon)</option>
                            <option value="eu-west-1" <?php selected($settings['amazonses_region'] ?? '', 'eu-west-1'); ?>>EU (Ireland)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- General Settings Tab -->
        <div class="eem-tab-content" id="general-settings-tab">
            <div class="eem-settings-section">
                <h3>General Email Settings</h3>
                
                <div class="eem-form-row">
                    <div class="eem-form-group eem-col-6">
                        <label for="default-from-name">Default From Name:</label>
                        <input type="text" id="default-from-name" name="default_from_name" 
                               value="<?php echo esc_attr($settings['default_from_name'] ?? get_bloginfo('name')); ?>" class="eem-input">
                    </div>
                    <div class="eem-form-group eem-col-6">
                        <label for="default-from-email">Default From Email:</label>
                        <input type="email" id="default-from-email" name="default_from_email" 
                               value="<?php echo esc_attr($settings['default_from_email'] ?? get_option('admin_email')); ?>" class="eem-input">
                    </div>
                </div>
                
                <div class="eem-form-row">
                    <div class="eem-form-group eem-col-6">
                        <label for="default-reply-to">Default Reply-To:</label>
                        <input type="email" id="default-reply-to" name="default_reply_to" 
                               value="<?php echo esc_attr($settings['default_reply_to'] ?? ''); ?>" class="eem-input">
                    </div>
                    <div class="eem-form-group eem-col-6">
                        <label for="bounce-email">Bounce Email:</label>
                        <input type="email" id="bounce-email" name="bounce_email" 
                               value="<?php echo esc_attr($settings['bounce_email'] ?? ''); ?>" class="eem-input">
                    </div>
                </div>
                
                <div class="eem-form-group">
                    <label for="company-address">Company Address:</label>
                    <textarea id="company-address" name="company_address" class="eem-textarea" rows="3"><?php echo esc_textarea($settings['company_address'] ?? ''); ?></textarea>
                    <p class="eem-field-description">
                        Required for CAN-SPAM compliance. Will be included in email footers.
                    </p>
                </div>
            </div>
            
            <div class="eem-settings-section">
                <h3>Subscription Settings</h3>
                
                <div class="eem-form-group">
                    <label class="eem-checkbox-label">
                        <input type="checkbox" name="double_optin" value="1" 
                               <?php checked($settings['double_optin'] ?? true, 1); ?>>
                        Enable Double Opt-In
                    </label>
                    <p class="eem-field-description">
                        Subscribers must confirm their email address before being added to lists.
                    </p>
                </div>
                
                <div class="eem-form-group">
                    <label class="eem-checkbox-label">
                        <input type="checkbox" name="gdpr_compliance" value="1" 
                               <?php checked($settings['gdpr_compliance'] ?? true, 1); ?>>
                        Enable GDPR Compliance Features
                    </label>
                    <p class="eem-field-description">
                        Adds consent tracking and data retention controls.
                    </p>
                </div>
                
                <div class="eem-form-group">
                    <label for="data-retention-days">Data Retention Period (days):</label>
                    <input type="number" id="data-retention-days" name="data_retention_days" 
                           value="<?php echo esc_attr($settings['data_retention_days'] ?? 365); ?>" 
                           class="eem-input" min="30" max="3650">
                    <p class="eem-field-description">
                        How long to keep subscriber data after unsubscription (30-3650 days).
                    </p>
                </div>
            </div>
        </div>

        <!-- Environmental Tracking Tab -->
        <div class="eem-tab-content" id="environmental-tab">
            <div class="eem-settings-section">
                <h3>Environmental Impact Tracking</h3>
                <p class="eem-section-description">
                    Configure how environmental actions and impact are tracked and calculated.
                </p>
                
                <div class="eem-form-group">
                    <label class="eem-checkbox-label">
                        <input type="checkbox" name="enable_environmental_tracking" value="1" 
                               <?php checked($settings['enable_environmental_tracking'] ?? true, 1); ?>>
                        Enable Environmental Impact Tracking
                    </label>
                </div>
                
                <div class="eem-environmental-settings">
                    <h4>Carbon Footprint Calculations</h4>
                    <div class="eem-form-row">
                        <div class="eem-form-group eem-col-6">
                            <label for="co2-per-email">CO₂ per Email (grams):</label>
                            <input type="number" id="co2-per-email" name="co2_per_email" 
                                   value="<?php echo esc_attr($settings['co2_per_email'] ?? 4); ?>" 
                                   class="eem-input" step="0.1" min="0">
                        </div>
                        <div class="eem-form-group eem-col-6">
                            <label for="offset-rate">Carbon Offset Rate ($/kg):</label>
                            <input type="number" id="offset-rate" name="offset_rate" 
                                   value="<?php echo esc_attr($settings['offset_rate'] ?? 0.02); ?>" 
                                   class="eem-input" step="0.01" min="0">
                        </div>
                    </div>
                    
                    <h4>Eco Score Weights</h4>
                    <div class="eem-form-row">
                        <div class="eem-form-group eem-col-4">
                            <label for="weight-opens">Email Opens:</label>
                            <input type="number" id="weight-opens" name="weight_opens" 
                                   value="<?php echo esc_attr($settings['weight_opens'] ?? 1); ?>" 
                                   class="eem-input" min="0" max="10">
                        </div>
                        <div class="eem-form-group eem-col-4">
                            <label for="weight-clicks">Link Clicks:</label>
                            <input type="number" id="weight-clicks" name="weight_clicks" 
                                   value="<?php echo esc_attr($settings['weight_clicks'] ?? 3); ?>" 
                                   class="eem-input" min="0" max="10">
                        </div>
                        <div class="eem-form-group eem-col-4">
                            <label for="weight-actions">Eco Actions:</label>
                            <input type="number" id="weight-actions" name="weight_actions" 
                                   value="<?php echo esc_attr($settings['weight_actions'] ?? 10); ?>" 
                                   class="eem-input" min="0" max="50">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="eem-settings-section">
                <h3>Environmental Action Types</h3>
                <div class="eem-action-types">
                    <?php 
                    $default_actions = array(
                        'petition_signed' => 'Petition Signed',
                        'event_attended' => 'Event Attended',
                        'quiz_completed' => 'Quiz Completed',
                        'article_shared' => 'Article Shared',
                        'donation_made' => 'Donation Made',
                        'volunteer_signup' => 'Volunteer Signup'
                    );
                    $action_types = $settings['environmental_actions'] ?? $default_actions;
                    
                    foreach ($action_types as $key => $label): ?>
                        <div class="eem-action-type-row">
                            <input type="text" name="environmental_actions[<?php echo esc_attr($key); ?>]" 
                                   value="<?php echo esc_attr($label); ?>" class="eem-input">
                            <button type="button" class="eem-btn eem-btn-small eem-btn-danger remove-action-type">Remove</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="eem-btn eem-btn-secondary" id="add-action-type">Add Action Type</button>
            </div>
        </div>

        <!-- Automation Tab -->
        <div class="eem-tab-content" id="automation-tab">
            <div class="eem-settings-section">
                <h3>Email Automation Settings</h3>
                
                <div class="eem-form-group">
                    <label class="eem-checkbox-label">
                        <input type="checkbox" name="enable_automation" value="1" 
                               <?php checked($settings['enable_automation'] ?? true, 1); ?>>
                        Enable Email Automation
                    </label>
                </div>
                
                <div class="eem-form-row">
                    <div class="eem-form-group eem-col-6">
                        <label for="automation-frequency">Processing Frequency:</label>
                        <select id="automation-frequency" name="automation_frequency" class="eem-select">
                            <option value="hourly" <?php selected($settings['automation_frequency'] ?? 'hourly', 'hourly'); ?>>Every Hour</option>
                            <option value="daily" <?php selected($settings['automation_frequency'] ?? '', 'daily'); ?>>Daily</option>
                            <option value="twicedaily" <?php selected($settings['automation_frequency'] ?? '', 'twicedaily'); ?>>Twice Daily</option>
                        </select>
                    </div>
                    <div class="eem-form-group eem-col-6">
                        <label for="batch-size">Batch Size:</label>
                        <input type="number" id="batch-size" name="batch_size" 
                               value="<?php echo esc_attr($settings['batch_size'] ?? 100); ?>" 
                               class="eem-input" min="10" max="1000">
                    </div>
                </div>
                
                <div class="eem-form-group">
                    <label for="automation-delay">Default Delay Between Emails (hours):</label>
                    <input type="number" id="automation-delay" name="automation_delay" 
                           value="<?php echo esc_attr($settings['automation_delay'] ?? 24); ?>" 
                           class="eem-input" min="1" max="168">
                </div>
            </div>
            
            <div class="eem-settings-section">
                <h3>Welcome Email Series</h3>
                <div class="eem-form-group">
                    <label class="eem-checkbox-label">
                        <input type="checkbox" name="enable_welcome_series" value="1" 
                               <?php checked($settings['enable_welcome_series'] ?? true, 1); ?>>
                        Enable Welcome Email Series
                    </label>
                </div>
                
                <div class="eem-form-group">
                    <label for="welcome-series-count">Number of Welcome Emails:</label>
                    <input type="number" id="welcome-series-count" name="welcome_series_count" 
                           value="<?php echo esc_attr($settings['welcome_series_count'] ?? 3); ?>" 
                           class="eem-input" min="1" max="10">
                </div>
            </div>
        </div>

        <!-- Tracking & Analytics Tab -->
        <div class="eem-tab-content" id="tracking-tab">
            <div class="eem-settings-section">
                <h3>Email Tracking</h3>
                
                <div class="eem-form-group">
                    <label class="eem-checkbox-label">
                        <input type="checkbox" name="enable_open_tracking" value="1" 
                               <?php checked($settings['enable_open_tracking'] ?? true, 1); ?>>
                        Enable Open Tracking
                    </label>
                </div>
                
                <div class="eem-form-group">
                    <label class="eem-checkbox-label">
                        <input type="checkbox" name="enable_click_tracking" value="1" 
                               <?php checked($settings['enable_click_tracking'] ?? true, 1); ?>>
                        Enable Click Tracking
                    </label>
                </div>
                
                <div class="eem-form-group">
                    <label class="eem-checkbox-label">
                        <input type="checkbox" name="enable_unsubscribe_tracking" value="1" 
                               <?php checked($settings['enable_unsubscribe_tracking'] ?? true, 1); ?>>
                        Enable Unsubscribe Tracking
                    </label>
                </div>
            </div>
            
            <div class="eem-settings-section">
                <h3>Google Analytics Integration</h3>
                
                <div class="eem-form-group">
                    <label class="eem-checkbox-label">
                        <input type="checkbox" name="enable_ga_tracking" value="1" 
                               <?php checked($settings['enable_ga_tracking'] ?? false, 1); ?>>
                        Enable Google Analytics UTM Parameters
                    </label>
                </div>
                
                <div class="eem-form-group">
                    <label for="ga-campaign-source">Campaign Source:</label>
                    <input type="text" id="ga-campaign-source" name="ga_campaign_source" 
                           value="<?php echo esc_attr($settings['ga_campaign_source'] ?? 'email'); ?>" class="eem-input">
                </div>
                
                <div class="eem-form-group">
                    <label for="ga-campaign-medium">Campaign Medium:</label>
                    <input type="text" id="ga-campaign-medium" name="ga_campaign_medium" 
                           value="<?php echo esc_attr($settings['ga_campaign_medium'] ?? 'newsletter'); ?>" class="eem-input">
                </div>
            </div>
            
            <div class="eem-settings-section">
                <h3>Data Retention</h3>
                
                <div class="eem-form-group">
                    <label for="analytics-retention">Analytics Data Retention (days):</label>
                    <input type="number" id="analytics-retention" name="analytics_retention" 
                           value="<?php echo esc_attr($settings['analytics_retention'] ?? 365); ?>" 
                           class="eem-input" min="30" max="3650">
                </div>
            </div>
        </div>

        <!-- Advanced Tab -->
        <div class="eem-tab-content" id="advanced-tab">
            <div class="eem-settings-section">
                <h3>Rate Limiting</h3>
                
                <div class="eem-form-row">
                    <div class="eem-form-group eem-col-6">
                        <label for="rate-limit-per-minute">Emails per Minute:</label>
                        <input type="number" id="rate-limit-per-minute" name="rate_limit_per_minute" 
                               value="<?php echo esc_attr($settings['rate_limit_per_minute'] ?? 60); ?>" 
                               class="eem-input" min="1" max="1000">
                    </div>
                    <div class="eem-form-group eem-col-6">
                        <label for="rate-limit-per-hour">Emails per Hour:</label>
                        <input type="number" id="rate-limit-per-hour" name="rate_limit_per_hour" 
                               value="<?php echo esc_attr($settings['rate_limit_per_hour'] ?? 3600); ?>" 
                               class="eem-input" min="1" max="50000">
                    </div>
                </div>
            </div>
            
            <div class="eem-settings-section">
                <h3>API Settings</h3>
                
                <div class="eem-form-group">
                    <label class="eem-checkbox-label">
                        <input type="checkbox" name="enable_rest_api" value="1" 
                               <?php checked($settings['enable_rest_api'] ?? true, 1); ?>>
                        Enable REST API
                    </label>
                </div>
                
                <div class="eem-form-group">
                    <label for="api-key">API Key:</label>
                    <div class="eem-input-group">
                        <input type="text" id="api-key" name="api_key" 
                               value="<?php echo esc_attr($settings['api_key'] ?? ''); ?>" 
                               class="eem-input" readonly>
                        <button type="button" class="eem-btn eem-btn-secondary" id="generate-api-key">
                            Generate New Key
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="eem-settings-section">
                <h3>Debugging & Logging</h3>
                
                <div class="eem-form-group">
                    <label class="eem-checkbox-label">
                        <input type="checkbox" name="enable_debug_logging" value="1" 
                               <?php checked($settings['enable_debug_logging'] ?? false, 1); ?>>
                        Enable Debug Logging
                    </label>
                </div>
                
                <div class="eem-form-group">
                    <label for="log-level">Log Level:</label>
                    <select id="log-level" name="log_level" class="eem-select">
                        <option value="error" <?php selected($settings['log_level'] ?? 'error', 'error'); ?>>Error</option>
                        <option value="warning" <?php selected($settings['log_level'] ?? '', 'warning'); ?>>Warning</option>
                        <option value="info" <?php selected($settings['log_level'] ?? '', 'info'); ?>>Info</option>
                        <option value="debug" <?php selected($settings['log_level'] ?? '', 'debug'); ?>>Debug</option>
                    </select>
                </div>
                
                <div class="eem-form-group">
                    <button type="button" class="eem-btn eem-btn-secondary" id="view-logs">
                        <span class="dashicons dashicons-media-text"></span>
                        View Log Files
                    </button>
                    <button type="button" class="eem-btn eem-btn-secondary" id="clear-logs">
                        <span class="dashicons dashicons-trash"></span>
                        Clear Logs
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Test Connection Modal -->
<div id="test-connection-modal" class="eem-modal" style="display: none;">
    <div class="eem-modal-content">
        <div class="eem-modal-header">
            <h3>Test Email Provider Connection</h3>
            <button class="eem-modal-close">&times;</button>
        </div>
        <div class="eem-modal-body">
            <div class="eem-form-group">
                <label for="test-email">Send Test Email To:</label>
                <input type="email" id="test-email" value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>" class="eem-input">
            </div>
            <div class="eem-test-results" id="test-results" style="display: none;">
                <!-- Test results will be displayed here -->
            </div>
        </div>
        <div class="eem-modal-footer">
            <button type="button" class="eem-btn eem-btn-secondary" onclick="EEMAdmin.closeModal('test-connection-modal')">Cancel</button>
            <button type="button" class="eem-btn eem-btn-primary" id="send-test-email">
                <span class="eem-btn-text">Send Test Email</span>
                <span class="eem-btn-loading" style="display: none;">
                    <span class="eem-spinner-small"></span>
                    Testing...
                </span>
            </button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize settings page
    EEMAdmin.initializeSettingsPage();
});
</script>
