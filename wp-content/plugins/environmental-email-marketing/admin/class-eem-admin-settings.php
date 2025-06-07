<?php
/**
 * Environmental Email Marketing - Settings Admin Interface
 *
 * Admin interface for plugin settings and configuration
 *
 * @package Environmental_Email_Marketing
 * @subpackage Admin
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EEM_Admin_Settings {

    /**
     * Settings tabs
     *
     * @var array
     */
    private $tabs = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->tabs = array(
            'general' => __('General', 'environmental-email-marketing'),
            'providers' => __('Email Providers', 'environmental-email-marketing'),
            'automations' => __('Automations', 'environmental-email-marketing'),
            'tracking' => __('Tracking & Analytics', 'environmental-email-marketing'),
            'environmental' => __('Environmental Settings', 'environmental-email-marketing'),
            'advanced' => __('Advanced', 'environmental-email-marketing')
        );
        
        add_action('wp_ajax_eem_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_eem_test_provider', array($this, 'ajax_test_provider'));
        add_action('wp_ajax_eem_reset_settings', array($this, 'ajax_reset_settings'));
    }

    /**
     * Render settings page
     */
    public function render_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        ?>
        <div class="wrap eem-settings">
            <h1><?php _e('Email Marketing Settings', 'environmental-email-marketing'); ?></h1>

            <!-- Settings Navigation -->
            <nav class="nav-tab-wrapper wp-clearfix">
                <?php foreach ($this->tabs as $tab_id => $tab_name): ?>
                    <a href="<?php echo admin_url('admin.php?page=eem-settings&tab=' . $tab_id); ?>" 
                       class="nav-tab <?php echo $active_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($tab_name); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <form method="post" id="eem-settings-form">
                <?php wp_nonce_field('eem_save_settings', 'eem_settings_nonce'); ?>
                <input type="hidden" name="active_tab" value="<?php echo esc_attr($active_tab); ?>">

                <div class="eem-settings-content">
                    <?php
                    switch ($active_tab) {
                        case 'providers':
                            $this->render_providers_settings();
                            break;
                        case 'automations':
                            $this->render_automations_settings();
                            break;
                        case 'tracking':
                            $this->render_tracking_settings();
                            break;
                        case 'environmental':
                            $this->render_environmental_settings();
                            break;
                        case 'advanced':
                            $this->render_advanced_settings();
                            break;
                        default:
                            $this->render_general_settings();
                            break;
                    }
                    ?>
                </div>

                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php _e('Save Settings', 'environmental-email-marketing'); ?>">
                    <button type="button" id="eem-test-settings" class="button">
                        <?php _e('Test Configuration', 'environmental-email-marketing'); ?>
                    </button>
                    <button type="button" id="eem-reset-settings" class="button button-secondary">
                        <?php _e('Reset to Defaults', 'environmental-email-marketing'); ?>
                    </button>
                </p>
            </form>
        </div>

        <style>
        .eem-settings {
            margin: 20px 0;
        }
        
        .eem-settings-content {
            background: #fff;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .eem-setting-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .eem-setting-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .eem-setting-section h3 {
            margin-top: 0;
            color: #46b450;
        }
        
        .eem-provider-config {
            background: #f9f9f9;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 4px solid #46b450;
        }
        
        .eem-provider-status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .eem-provider-status.connected {
            background: #46b450;
            color: white;
        }
        
        .eem-provider-status.disconnected {
            background: #dc3232;
            color: white;
        }
        
        .eem-env-impact-display {
            background: linear-gradient(135deg, #46b450, #00a32a);
            color: white;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
        }
        
        .eem-setting-help {
            font-style: italic;
            color: #666;
            font-size: 13px;
            margin-top: 5px;
        }
        </style>
        <?php
    }

    /**
     * Render general settings
     */
    private function render_general_settings() {
        $general_settings = get_option('eem_general_settings', array());
        ?>
        <div class="eem-setting-section">
            <h3><?php _e('Basic Configuration', 'environmental-email-marketing'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="plugin_enabled"><?php _e('Enable Plugin', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="general[plugin_enabled]" id="plugin_enabled" value="1" 
                                   <?php checked(!empty($general_settings['plugin_enabled'])); ?>>
                            <?php _e('Enable email marketing functionality', 'environmental-email-marketing'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="from_name"><?php _e('From Name', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="general[from_name]" id="from_name" class="regular-text" 
                               value="<?php echo esc_attr($general_settings['from_name'] ?? get_bloginfo('name')); ?>">
                        <p class="eem-setting-help">
                            <?php _e('The name that appears in the "From" field of your emails.', 'environmental-email-marketing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="from_email"><?php _e('From Email', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <input type="email" name="general[from_email]" id="from_email" class="regular-text" 
                               value="<?php echo esc_attr($general_settings['from_email'] ?? get_option('admin_email')); ?>">
                        <p class="eem-setting-help">
                            <?php _e('The email address that appears in the "From" field of your emails.', 'environmental-email-marketing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="reply_to_email"><?php _e('Reply-To Email', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <input type="email" name="general[reply_to_email]" id="reply_to_email" class="regular-text" 
                               value="<?php echo esc_attr($general_settings['reply_to_email'] ?? ''); ?>">
                        <p class="eem-setting-help">
                            <?php _e('Email address for replies. Leave empty to use From Email.', 'environmental-email-marketing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="default_list"><?php _e('Default List', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <select name="general[default_list]" id="default_list">
                            <option value=""><?php _e('Select a list...', 'environmental-email-marketing'); ?></option>
                            <?php
                            $subscriber_manager = new EEM_Subscriber_Manager();
                            $lists = $subscriber_manager->get_lists();
                            foreach ($lists as $list):
                            ?>
                                <option value="<?php echo $list->id; ?>" 
                                        <?php selected($general_settings['default_list'] ?? '', $list->id); ?>>
                                    <?php echo esc_html($list->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="eem-setting-help">
                            <?php _e('Default list for new subscribers.', 'environmental-email-marketing'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="eem-setting-section">
            <h3><?php _e('Subscription Settings', 'environmental-email-marketing'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="double_optin"><?php _e('Double Opt-in', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="general[double_optin]" id="double_optin" value="1" 
                                   <?php checked(!empty($general_settings['double_optin'])); ?>>
                            <?php _e('Require email confirmation for new subscribers', 'environmental-email-marketing'); ?>
                        </label>
                        <p class="eem-setting-help">
                            <?php _e('Recommended for GDPR compliance and better deliverability.', 'environmental-email-marketing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="welcome_email"><?php _e('Welcome Email', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="general[welcome_email]" id="welcome_email" value="1" 
                                   <?php checked(!empty($general_settings['welcome_email'])); ?>>
                            <?php _e('Send welcome email to new subscribers', 'environmental-email-marketing'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="unsubscribe_page"><?php _e('Unsubscribe Page', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <?php
                        wp_dropdown_pages(array(
                            'name' => 'general[unsubscribe_page]',
                            'id' => 'unsubscribe_page',
                            'selected' => $general_settings['unsubscribe_page'] ?? '',
                            'show_option_none' => __('Select a page...', 'environmental-email-marketing')
                        ));
                        ?>
                        <p class="eem-setting-help">
                            <?php _e('Page to redirect users after unsubscribing.', 'environmental-email-marketing'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Render providers settings
     */
    private function render_providers_settings() {
        $provider_settings = get_option('eem_provider_settings', array());
        ?>
        <div class="eem-setting-section">
            <h3><?php _e('Email Service Providers', 'environmental-email-marketing'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="primary_provider"><?php _e('Primary Provider', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <select name="providers[primary_provider]" id="primary_provider">
                            <option value=""><?php _e('Select provider...', 'environmental-email-marketing'); ?></option>
                            <option value="mailchimp" <?php selected($provider_settings['primary_provider'] ?? '', 'mailchimp'); ?>>
                                Mailchimp
                            </option>
                            <option value="sendgrid" <?php selected($provider_settings['primary_provider'] ?? '', 'sendgrid'); ?>>
                                SendGrid
                            </option>
                            <option value="mailgun" <?php selected($provider_settings['primary_provider'] ?? '', 'mailgun'); ?>>
                                Mailgun
                            </option>
                            <option value="ses" <?php selected($provider_settings['primary_provider'] ?? '', 'ses'); ?>>
                                Amazon SES
                            </option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Mailchimp Configuration -->
        <div class="eem-setting-section">
            <h3><?php _e('Mailchimp Configuration', 'environmental-email-marketing'); ?></h3>
            <div class="eem-provider-config">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="mailchimp_api_key"><?php _e('API Key', 'environmental-email-marketing'); ?></label>
                        </th>
                        <td>
                            <input type="password" name="providers[mailchimp_api_key]" id="mailchimp_api_key" class="regular-text" 
                                   value="<?php echo esc_attr($provider_settings['mailchimp_api_key'] ?? ''); ?>">
                            <button type="button" class="button eem-test-provider" data-provider="mailchimp">
                                <?php _e('Test Connection', 'environmental-email-marketing'); ?>
                            </button>
                            <span class="eem-provider-status" id="mailchimp-status">
                                <?php echo $this->get_provider_status('mailchimp'); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="mailchimp_list_id"><?php _e('Default List ID', 'environmental-email-marketing'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="providers[mailchimp_list_id]" id="mailchimp_list_id" class="regular-text" 
                                   value="<?php echo esc_attr($provider_settings['mailchimp_list_id'] ?? ''); ?>">
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- SendGrid Configuration -->
        <div class="eem-setting-section">
            <h3><?php _e('SendGrid Configuration', 'environmental-email-marketing'); ?></h3>
            <div class="eem-provider-config">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="sendgrid_api_key"><?php _e('API Key', 'environmental-email-marketing'); ?></label>
                        </th>
                        <td>
                            <input type="password" name="providers[sendgrid_api_key]" id="sendgrid_api_key" class="regular-text" 
                                   value="<?php echo esc_attr($provider_settings['sendgrid_api_key'] ?? ''); ?>">
                            <button type="button" class="button eem-test-provider" data-provider="sendgrid">
                                <?php _e('Test Connection', 'environmental-email-marketing'); ?>
                            </button>
                            <span class="eem-provider-status" id="sendgrid-status">
                                <?php echo $this->get_provider_status('sendgrid'); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="sendgrid_template_id"><?php _e('Default Template ID', 'environmental-email-marketing'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="providers[sendgrid_template_id]" id="sendgrid_template_id" class="regular-text" 
                                   value="<?php echo esc_attr($provider_settings['sendgrid_template_id'] ?? ''); ?>">
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Delivery Settings -->
        <div class="eem-setting-section">
            <h3><?php _e('Delivery Settings', 'environmental-email-marketing'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="batch_size"><?php _e('Batch Size', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="providers[batch_size]" id="batch_size" class="small-text" 
                               value="<?php echo esc_attr($provider_settings['batch_size'] ?? '100'); ?>" min="1" max="1000">
                        <p class="eem-setting-help">
                            <?php _e('Number of emails to send in each batch. Lower values reduce server load.', 'environmental-email-marketing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="send_delay"><?php _e('Send Delay', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="providers[send_delay]" id="send_delay" class="small-text" 
                               value="<?php echo esc_attr($provider_settings['send_delay'] ?? '2'); ?>" min="0" max="60">
                        <?php _e('seconds between batches', 'environmental-email-marketing'); ?>
                        <p class="eem-setting-help">
                            <?php _e('Delay between batch sends to respect rate limits.', 'environmental-email-marketing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="retry_failed"><?php _e('Retry Failed Sends', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="providers[retry_failed]" id="retry_failed" value="1" 
                                   <?php checked(!empty($provider_settings['retry_failed'])); ?>>
                            <?php _e('Automatically retry failed email sends', 'environmental-email-marketing'); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Render automations settings
     */
    private function render_automations_settings() {
        $automation_settings = get_option('eem_automation_settings', array());
        ?>
        <div class="eem-setting-section">
            <h3><?php _e('Automation Configuration', 'environmental-email-marketing'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="automations_enabled"><?php _e('Enable Automations', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="automations[automations_enabled]" id="automations_enabled" value="1" 
                                   <?php checked(!empty($automation_settings['automations_enabled'])); ?>>
                            <?php _e('Enable automated email sequences', 'environmental-email-marketing'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="processing_interval"><?php _e('Processing Interval', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <select name="automations[processing_interval]" id="processing_interval">
                            <option value="5" <?php selected($automation_settings['processing_interval'] ?? '15', '5'); ?>>
                                <?php _e('Every 5 minutes', 'environmental-email-marketing'); ?>
                            </option>
                            <option value="15" <?php selected($automation_settings['processing_interval'] ?? '15', '15'); ?>>
                                <?php _e('Every 15 minutes', 'environmental-email-marketing'); ?>
                            </option>
                            <option value="30" <?php selected($automation_settings['processing_interval'] ?? '15', '30'); ?>>
                                <?php _e('Every 30 minutes', 'environmental-email-marketing'); ?>
                            </option>
                            <option value="60" <?php selected($automation_settings['processing_interval'] ?? '15', '60'); ?>>
                                <?php _e('Every hour', 'environmental-email-marketing'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <div class="eem-setting-section">
            <h3><?php _e('Environmental Triggers', 'environmental-email-marketing'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="petition_trigger"><?php _e('Petition Signature Trigger', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="automations[petition_trigger]" id="petition_trigger" value="1" 
                                   <?php checked(!empty($automation_settings['petition_trigger'])); ?>>
                            <?php _e('Send follow-up emails when users sign petitions', 'environmental-email-marketing'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="eco_purchase_trigger"><?php _e('Eco Purchase Trigger', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="automations[eco_purchase_trigger]" id="eco_purchase_trigger" value="1" 
                                   <?php checked(!empty($automation_settings['eco_purchase_trigger'])); ?>>
                            <?php _e('Send follow-up emails for eco-friendly purchases', 'environmental-email-marketing'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="event_attendance_trigger"><?php _e('Event Attendance Trigger', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="automations[event_attendance_trigger]" id="event_attendance_trigger" value="1" 
                                   <?php checked(!empty($automation_settings['event_attendance_trigger'])); ?>>
                            <?php _e('Send follow-up emails for event attendees', 'environmental-email-marketing'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="quiz_completion_trigger"><?php _e('Quiz Completion Trigger', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="automations[quiz_completion_trigger]" id="quiz_completion_trigger" value="1" 
                                   <?php checked(!empty($automation_settings['quiz_completion_trigger'])); ?>>
                            <?php _e('Send follow-up emails when users complete environmental quizzes', 'environmental-email-marketing'); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>

        <div class="eem-setting-section">
            <h3><?php _e('Seasonal Campaigns', 'environmental-email-marketing'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="seasonal_campaigns"><?php _e('Enable Seasonal Campaigns', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="automations[seasonal_campaigns]" id="seasonal_campaigns" value="1" 
                                   <?php checked(!empty($automation_settings['seasonal_campaigns'])); ?>>
                            <?php _e('Automatically send seasonal environmental campaigns', 'environmental-email-marketing'); ?>
                        </label>
                        <p class="eem-setting-help">
                            <?php _e('Earth Day, World Environment Day, Climate Week, etc.', 'environmental-email-marketing'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Render tracking settings
     */
    private function render_tracking_settings() {
        $tracking_settings = get_option('eem_tracking_settings', array());
        ?>
        <div class="eem-setting-section">
            <h3><?php _e('Email Tracking', 'environmental-email-marketing'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="open_tracking"><?php _e('Open Tracking', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="tracking[open_tracking]" id="open_tracking" value="1" 
                                   <?php checked(!empty($tracking_settings['open_tracking'])); ?>>
                            <?php _e('Track email opens', 'environmental-email-marketing'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="click_tracking"><?php _e('Click Tracking', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="tracking[click_tracking]" id="click_tracking" value="1" 
                                   <?php checked(!empty($tracking_settings['click_tracking'])); ?>>
                            <?php _e('Track link clicks', 'environmental-email-marketing'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="unsubscribe_tracking"><?php _e('Unsubscribe Tracking', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="tracking[unsubscribe_tracking]" id="unsubscribe_tracking" value="1" 
                                   <?php checked(!empty($tracking_settings['unsubscribe_tracking'])); ?>>
                            <?php _e('Track unsubscribes', 'environmental-email-marketing'); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>

        <div class="eem-setting-section">
            <h3><?php _e('Environmental Action Tracking', 'environmental-email-marketing'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="env_action_tracking"><?php _e('Environmental Actions', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="tracking[env_action_tracking]" id="env_action_tracking" value="1" 
                                   <?php checked(!empty($tracking_settings['env_action_tracking'])); ?>>
                            <?php _e('Track environmental actions triggered by emails', 'environmental-email-marketing'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="carbon_tracking"><?php _e('Carbon Impact Tracking', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="tracking[carbon_tracking]" id="carbon_tracking" value="1" 
                                   <?php checked(!empty($tracking_settings['carbon_tracking'])); ?>>
                            <?php _e('Calculate and track carbon footprint of email campaigns', 'environmental-email-marketing'); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>

        <div class="eem-setting-section">
            <h3><?php _e('Analytics Configuration', 'environmental-email-marketing'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="data_retention"><?php _e('Data Retention', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <select name="tracking[data_retention]" id="data_retention">
                            <option value="90" <?php selected($tracking_settings['data_retention'] ?? '365', '90'); ?>>
                                <?php _e('90 days', 'environmental-email-marketing'); ?>
                            </option>
                            <option value="180" <?php selected($tracking_settings['data_retention'] ?? '365', '180'); ?>>
                                <?php _e('6 months', 'environmental-email-marketing'); ?>
                            </option>
                            <option value="365" <?php selected($tracking_settings['data_retention'] ?? '365', '365'); ?>>
                                <?php _e('1 year', 'environmental-email-marketing'); ?>
                            </option>
                            <option value="730" <?php selected($tracking_settings['data_retention'] ?? '365', '730'); ?>>
                                <?php _e('2 years', 'environmental-email-marketing'); ?>
                            </option>
                        </select>
                        <p class="eem-setting-help">
                            <?php _e('How long to keep analytics data.', 'environmental-email-marketing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="anonymize_data"><?php _e('Anonymize Data', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="tracking[anonymize_data]" id="anonymize_data" value="1" 
                                   <?php checked(!empty($tracking_settings['anonymize_data'])); ?>>
                            <?php _e('Anonymize subscriber data in analytics', 'environmental-email-marketing'); ?>
                        </label>
                        <p class="eem-setting-help">
                            <?php _e('Recommended for privacy compliance.', 'environmental-email-marketing'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Render environmental settings
     */
    private function render_environmental_settings() {
        $env_settings = get_option('eem_environmental_settings', array());
        ?>
        <div class="eem-setting-section">
            <h3><?php _e('Environmental Impact Calculation', 'environmental-email-marketing'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="carbon_per_email"><?php _e('Carbon per Email (kg CO₂)', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="environmental[carbon_per_email]" id="carbon_per_email" 
                               class="small-text" step="0.001" 
                               value="<?php echo esc_attr($env_settings['carbon_per_email'] ?? '0.004'); ?>">
                        <p class="eem-setting-help">
                            <?php _e('Average carbon footprint per email sent.', 'environmental-email-marketing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="energy_per_email"><?php _e('Energy per Email (kWh)', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="environmental[energy_per_email]" id="energy_per_email" 
                               class="small-text" step="0.0001" 
                               value="<?php echo esc_attr($env_settings['energy_per_email'] ?? '0.0006'); ?>">
                        <p class="eem-setting-help">
                            <?php _e('Average energy consumption per email sent.', 'environmental-email-marketing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="tree_co2_absorption"><?php _e('CO₂ per Tree per Year (kg)', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="environmental[tree_co2_absorption]" id="tree_co2_absorption" 
                               class="small-text" step="0.01" 
                               value="<?php echo esc_attr($env_settings['tree_co2_absorption'] ?? '21.77'); ?>">
                        <p class="eem-setting-help">
                            <?php _e('Average CO₂ absorbed by one tree per year.', 'environmental-email-marketing'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="eem-setting-section">
            <h3><?php _e('Environmental Scoring', 'environmental-email-marketing'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="scoring_enabled"><?php _e('Enable Environmental Scoring', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="environmental[scoring_enabled]" id="scoring_enabled" value="1" 
                                   <?php checked(!empty($env_settings['scoring_enabled'])); ?>>
                            <?php _e('Calculate environmental engagement scores for subscribers', 'environmental-email-marketing'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="petition_score"><?php _e('Petition Signature Score', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="environmental[petition_score]" id="petition_score" 
                               class="small-text" 
                               value="<?php echo esc_attr($env_settings['petition_score'] ?? '10'); ?>">
                        <p class="eem-setting-help">
                            <?php _e('Points awarded for signing petitions.', 'environmental-email-marketing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="green_purchase_score"><?php _e('Green Purchase Score', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="environmental[green_purchase_score]" id="green_purchase_score" 
                               class="small-text" 
                               value="<?php echo esc_attr($env_settings['green_purchase_score'] ?? '25'); ?>">
                        <p class="eem-setting-help">
                            <?php _e('Points awarded for eco-friendly purchases.', 'environmental-email-marketing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="event_attendance_score"><?php _e('Event Attendance Score', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="environmental[event_attendance_score]" id="event_attendance_score" 
                               class="small-text" 
                               value="<?php echo esc_attr($env_settings['event_attendance_score'] ?? '15'); ?>">
                        <p class="eem-setting-help">
                            <?php _e('Points awarded for attending environmental events.', 'environmental-email-marketing'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Current Environmental Impact Display -->
        <div class="eem-setting-section">
            <h3><?php _e('Current Environmental Impact', 'environmental-email-marketing'); ?></h3>
            <div class="eem-env-impact-display">
                <?php $impact_data = $this->get_current_environmental_impact(); ?>
                <p><strong><?php _e('Total Emails Sent:', 'environmental-email-marketing'); ?></strong> <?php echo number_format($impact_data['total_emails']); ?></p>
                <p><strong><?php _e('Carbon Footprint:', 'environmental-email-marketing'); ?></strong> <?php echo number_format($impact_data['carbon_footprint'], 3); ?> kg CO₂</p>
                <p><strong><?php _e('Trees Equivalent:', 'environmental-email-marketing'); ?></strong> <?php echo number_format($impact_data['trees_equivalent'], 6); ?> trees</p>
                <p><strong><?php _e('Environmental Actions Triggered:', 'environmental-email-marketing'); ?></strong> <?php echo number_format($impact_data['actions_triggered']); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Render advanced settings
     */
    private function render_advanced_settings() {
        $advanced_settings = get_option('eem_advanced_settings', array());
        ?>
        <div class="eem-setting-section">
            <h3><?php _e('Performance Settings', 'environmental-email-marketing'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="cache_enabled"><?php _e('Enable Caching', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="advanced[cache_enabled]" id="cache_enabled" value="1" 
                                   <?php checked(!empty($advanced_settings['cache_enabled'])); ?>>
                            <?php _e('Cache analytics data and subscriber lists', 'environmental-email-marketing'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="cache_duration"><?php _e('Cache Duration', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="advanced[cache_duration]" id="cache_duration" 
                               class="small-text" 
                               value="<?php echo esc_attr($advanced_settings['cache_duration'] ?? '3600'); ?>">
                        <?php _e('seconds', 'environmental-email-marketing'); ?>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="queue_processing"><?php _e('Queue Processing', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <select name="advanced[queue_processing]" id="queue_processing">
                            <option value="wp_cron" <?php selected($advanced_settings['queue_processing'] ?? 'wp_cron', 'wp_cron'); ?>>
                                <?php _e('WordPress Cron', 'environmental-email-marketing'); ?>
                            </option>
                            <option value="manual" <?php selected($advanced_settings['queue_processing'] ?? 'wp_cron', 'manual'); ?>>
                                <?php _e('Manual Processing', 'environmental-email-marketing'); ?>
                            </option>
                            <option value="external" <?php selected($advanced_settings['queue_processing'] ?? 'wp_cron', 'external'); ?>>
                                <?php _e('External Cron Job', 'environmental-email-marketing'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <div class="eem-setting-section">
            <h3><?php _e('Debug Settings', 'environmental-email-marketing'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="debug_mode"><?php _e('Debug Mode', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="advanced[debug_mode]" id="debug_mode" value="1" 
                                   <?php checked(!empty($advanced_settings['debug_mode'])); ?>>
                            <?php _e('Enable debug logging', 'environmental-email-marketing'); ?>
                        </label>
                        <p class="eem-setting-help">
                            <?php _e('Log detailed information about email sending and automation processing.', 'environmental-email-marketing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="log_level"><?php _e('Log Level', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <select name="advanced[log_level]" id="log_level">
                            <option value="error" <?php selected($advanced_settings['log_level'] ?? 'error', 'error'); ?>>
                                <?php _e('Error', 'environmental-email-marketing'); ?>
                            </option>
                            <option value="warning" <?php selected($advanced_settings['log_level'] ?? 'error', 'warning'); ?>>
                                <?php _e('Warning', 'environmental-email-marketing'); ?>
                            </option>
                            <option value="info" <?php selected($advanced_settings['log_level'] ?? 'error', 'info'); ?>>
                                <?php _e('Info', 'environmental-email-marketing'); ?>
                            </option>
                            <option value="debug" <?php selected($advanced_settings['log_level'] ?? 'error', 'debug'); ?>>
                                <?php _e('Debug', 'environmental-email-marketing'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <div class="eem-setting-section">
            <h3><?php _e('Database Maintenance', 'environmental-email-marketing'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label><?php _e('Clean Up Old Data', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <button type="button" id="eem-cleanup-data" class="button">
                            <?php _e('Clean Up Old Analytics Data', 'environmental-email-marketing'); ?>
                        </button>
                        <p class="eem-setting-help">
                            <?php _e('Remove analytics data older than retention period.', 'environmental-email-marketing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label><?php _e('Export Data', 'environmental-email-marketing'); ?></label>
                    </th>
                    <td>
                        <button type="button" id="eem-export-data" class="button">
                            <?php _e('Export All Data', 'environmental-email-marketing'); ?>
                        </button>
                        <p class="eem-setting-help">
                            <?php _e('Export subscribers, campaigns, and analytics data.', 'environmental-email-marketing'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Get provider status
     */
    private function get_provider_status($provider) {
        $provider_settings = get_option('eem_provider_settings', array());
        $status_key = $provider . '_connection_status';
        $status = $provider_settings[$status_key] ?? 'disconnected';
        
        $class = $status === 'connected' ? 'connected' : 'disconnected';
        $text = $status === 'connected' ? __('Connected', 'environmental-email-marketing') : __('Disconnected', 'environmental-email-marketing');
        
        return "<span class='{$class}'>{$text}</span>";
    }

    /**
     * Get current environmental impact
     */
    private function get_current_environmental_impact() {
        global $wpdb;
        
        $total_emails = $wpdb->get_var(
            "SELECT SUM(emails_sent) FROM {$wpdb->prefix}eem_campaigns WHERE status = 'sent'"
        ) ?: 0;
        
        $actions_triggered = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}eem_analytics_events WHERE event_type IN ('eco_action', 'petition_sign', 'green_purchase')"
        ) ?: 0;
        
        $env_settings = get_option('eem_environmental_settings', array());
        $carbon_per_email = $env_settings['carbon_per_email'] ?? 0.004;
        $tree_absorption = $env_settings['tree_co2_absorption'] ?? 21.77;
        
        $carbon_footprint = $total_emails * $carbon_per_email;
        $trees_equivalent = $carbon_footprint / $tree_absorption;
        
        return array(
            'total_emails' => $total_emails,
            'carbon_footprint' => $carbon_footprint,
            'trees_equivalent' => $trees_equivalent,
            'actions_triggered' => $actions_triggered
        );
    }

    /**
     * AJAX handler for saving settings
     */
    public function ajax_save_settings() {
        check_ajax_referer('eem_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $tab = sanitize_text_field($_POST['tab']);
        $settings = $_POST['settings'] ?? array();

        $option_map = array(
            'general' => 'eem_general_settings',
            'providers' => 'eem_provider_settings',
            'automations' => 'eem_automation_settings',
            'tracking' => 'eem_tracking_settings',
            'environmental' => 'eem_environmental_settings',
            'advanced' => 'eem_advanced_settings'
        );

        if (isset($option_map[$tab])) {
            $current_settings = get_option($option_map[$tab], array());
            $updated_settings = array_merge($current_settings, $settings);
            update_option($option_map[$tab], $updated_settings);
            
            wp_send_json_success(array(
                'message' => __('Settings saved successfully.', 'environmental-email-marketing')
            ));
        } else {
            wp_send_json_error(__('Invalid settings tab.', 'environmental-email-marketing'));
        }
    }

    /**
     * AJAX handler for testing provider connection
     */
    public function ajax_test_provider() {
        check_ajax_referer('eem_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $provider = sanitize_text_field($_POST['provider']);
        $credentials = $_POST['credentials'] ?? array();

        try {
            switch ($provider) {
                case 'mailchimp':
                    $provider_instance = new EEM_Mailchimp_Provider($credentials);
                    break;
                case 'sendgrid':
                    $provider_instance = new EEM_SendGrid_Provider($credentials);
                    break;
                default:
                    throw new Exception(__('Unsupported provider.', 'environmental-email-marketing'));
            }

            $is_valid = $provider_instance->validate_credentials();

            if ($is_valid) {
                // Update connection status
                $provider_settings = get_option('eem_provider_settings', array());
                $provider_settings[$provider . '_connection_status'] = 'connected';
                update_option('eem_provider_settings', $provider_settings);

                wp_send_json_success(array(
                    'message' => __('Connection successful!', 'environmental-email-marketing')
                ));
            } else {
                wp_send_json_error(__('Connection failed. Please check your credentials.', 'environmental-email-marketing'));
            }
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * AJAX handler for resetting settings
     */
    public function ajax_reset_settings() {
        check_ajax_referer('eem_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $tab = sanitize_text_field($_POST['tab']);

        $option_map = array(
            'general' => 'eem_general_settings',
            'providers' => 'eem_provider_settings',
            'automations' => 'eem_automation_settings',
            'tracking' => 'eem_tracking_settings',
            'environmental' => 'eem_environmental_settings',
            'advanced' => 'eem_advanced_settings'
        );

        if (isset($option_map[$tab])) {
            delete_option($option_map[$tab]);
            
            wp_send_json_success(array(
                'message' => __('Settings reset to defaults.', 'environmental-email-marketing')
            ));
        } else {
            wp_send_json_error(__('Invalid settings tab.', 'environmental-email-marketing'));
        }
    }
}
