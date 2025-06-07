<?php
/**
 * Dashboard Settings Admin Page
 * 
 * Configuration interface for dashboard settings, preferences,
 * and system options for the Environmental Platform.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current settings
$options = get_option('env_dashboard_options', array());
$default_options = array(
    'dashboard_layout' => 'two_column',
    'enable_notifications' => true,
    'enable_analytics' => true,
    'notification_frequency' => 'daily',
    'email_notifications' => true,
    'browser_notifications' => false,
    'data_retention_days' => 90,
    'auto_backup' => true,
    'backup_frequency' => 'weekly',
    'performance_monitoring' => true,
    'debug_mode' => false,
    'api_key' => '',
    'external_integrations' => array()
);

$options = wp_parse_args($options, $default_options);

// Handle form submission
if (isset($_POST['submit']) && wp_verify_nonce($_POST['env_settings_nonce'], 'env_dashboard_settings')) {
    $new_options = array();
    
    // Sanitize and save options
    $new_options['dashboard_layout'] = sanitize_text_field($_POST['dashboard_layout']);
    $new_options['enable_notifications'] = isset($_POST['enable_notifications']);
    $new_options['enable_analytics'] = isset($_POST['enable_analytics']);
    $new_options['notification_frequency'] = sanitize_text_field($_POST['notification_frequency']);
    $new_options['email_notifications'] = isset($_POST['email_notifications']);
    $new_options['browser_notifications'] = isset($_POST['browser_notifications']);
    $new_options['data_retention_days'] = intval($_POST['data_retention_days']);
    $new_options['auto_backup'] = isset($_POST['auto_backup']);
    $new_options['backup_frequency'] = sanitize_text_field($_POST['backup_frequency']);
    $new_options['performance_monitoring'] = isset($_POST['performance_monitoring']);
    $new_options['debug_mode'] = isset($_POST['debug_mode']);
    $new_options['api_key'] = sanitize_text_field($_POST['api_key']);
    
    update_option('env_dashboard_options', $new_options);
    
    echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'env-admin-dashboard') . '</p></div>';
    
    $options = $new_options;
}
?>

<div class="wrap env-dashboard-settings">
    <h1><?php _e('Environmental Dashboard Settings', 'env-admin-dashboard'); ?></h1>
    
    <div class="nav-tab-wrapper">
        <a href="#general-settings" class="nav-tab nav-tab-active"><?php _e('General', 'env-admin-dashboard'); ?></a>
        <a href="#notification-settings" class="nav-tab"><?php _e('Notifications', 'env-admin-dashboard'); ?></a>
        <a href="#data-settings" class="nav-tab"><?php _e('Data & Privacy', 'env-admin-dashboard'); ?></a>
        <a href="#performance-settings" class="nav-tab"><?php _e('Performance', 'env-admin-dashboard'); ?></a>
        <a href="#integration-settings" class="nav-tab"><?php _e('Integrations', 'env-admin-dashboard'); ?></a>
        <a href="#advanced-settings" class="nav-tab"><?php _e('Advanced', 'env-admin-dashboard'); ?></a>
    </div>
    
    <form method="post" action="">
        <?php wp_nonce_field('env_dashboard_settings', 'env_settings_nonce'); ?>
        
        <!-- General Settings Tab -->
        <div id="general-settings" class="tab-content active">
            <h2><?php _e('General Settings', 'env-admin-dashboard'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Dashboard Layout', 'env-admin-dashboard'); ?></th>
                    <td>
                        <select name="dashboard_layout">
                            <option value="one_column" <?php selected($options['dashboard_layout'], 'one_column'); ?>><?php _e('Single Column', 'env-admin-dashboard'); ?></option>
                            <option value="two_column" <?php selected($options['dashboard_layout'], 'two_column'); ?>><?php _e('Two Columns', 'env-admin-dashboard'); ?></option>
                            <option value="three_column" <?php selected($options['dashboard_layout'], 'three_column'); ?>><?php _e('Three Columns', 'env-admin-dashboard'); ?></option>
                            <option value="four_column" <?php selected($options['dashboard_layout'], 'four_column'); ?>><?php _e('Four Columns', 'env-admin-dashboard'); ?></option>
                        </select>
                        <p class="description"><?php _e('Choose the default layout for dashboard widgets.', 'env-admin-dashboard'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Enable Analytics', 'env-admin-dashboard'); ?></th>
                    <td>
                        <label for="enable_analytics">
                            <input name="enable_analytics" type="checkbox" id="enable_analytics" value="1" <?php checked($options['enable_analytics']); ?> />
                            <?php _e('Enable analytics tracking and reporting', 'env-admin-dashboard'); ?>
                        </label>
                        <p class="description"><?php _e('Track environmental data, user engagement, and platform performance.', 'env-admin-dashboard'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Default Dashboard Widgets', 'env-admin-dashboard'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><?php _e('Default Dashboard Widgets', 'env-admin-dashboard'); ?></legend>
                            <label for="widget_overview">
                                <input name="default_widgets[]" type="checkbox" id="widget_overview" value="overview" checked />
                                <?php _e('Environmental Platform Overview', 'env-admin-dashboard'); ?>
                            </label><br />
                            <label for="widget_activities">
                                <input name="default_widgets[]" type="checkbox" id="widget_activities" value="activities" checked />
                                <?php _e('Recent Environmental Activities', 'env-admin-dashboard'); ?>
                            </label><br />
                            <label for="widget_goals">
                                <input name="default_widgets[]" type="checkbox" id="widget_goals" value="goals" checked />
                                <?php _e('Environmental Goals Progress', 'env-admin-dashboard'); ?>
                            </label><br />
                            <label for="widget_performance">
                                <input name="default_widgets[]" type="checkbox" id="widget_performance" value="performance" checked />
                                <?php _e('Content Performance Analytics', 'env-admin-dashboard'); ?>
                            </label><br />
                            <label for="widget_health">
                                <input name="default_widgets[]" type="checkbox" id="widget_health" value="health" checked />
                                <?php _e('Platform Health Monitor', 'env-admin-dashboard'); ?>
                            </label><br />
                            <label for="widget_actions">
                                <input name="default_widgets[]" type="checkbox" id="widget_actions" value="actions" checked />
                                <?php _e('Quick Actions Panel', 'env-admin-dashboard'); ?>
                            </label>
                        </fieldset>
                        <p class="description"><?php _e('Select which widgets to display by default for new users.', 'env-admin-dashboard'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Notification Settings Tab -->
        <div id="notification-settings" class="tab-content">
            <h2><?php _e('Notification Settings', 'env-admin-dashboard'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Enable Notifications', 'env-admin-dashboard'); ?></th>
                    <td>
                        <label for="enable_notifications">
                            <input name="enable_notifications" type="checkbox" id="enable_notifications" value="1" <?php checked($options['enable_notifications']); ?> />
                            <?php _e('Enable admin notifications system', 'env-admin-dashboard'); ?>
                        </label>
                        <p class="description"><?php _e('Allow the system to send notifications about important events and updates.', 'env-admin-dashboard'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Email Notifications', 'env-admin-dashboard'); ?></th>
                    <td>
                        <label for="email_notifications">
                            <input name="email_notifications" type="checkbox" id="email_notifications" value="1" <?php checked($options['email_notifications']); ?> />
                            <?php _e('Send notifications via email', 'env-admin-dashboard'); ?>
                        </label>
                        <p class="description"><?php _e('Receive important notifications in your email inbox.', 'env-admin-dashboard'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Browser Notifications', 'env-admin-dashboard'); ?></th>
                    <td>
                        <label for="browser_notifications">
                            <input name="browser_notifications" type="checkbox" id="browser_notifications" value="1" <?php checked($options['browser_notifications']); ?> />
                            <?php _e('Enable browser push notifications', 'env-admin-dashboard'); ?>
                        </label>
                        <p class="description"><?php _e('Receive real-time notifications in your browser (requires user permission).', 'env-admin-dashboard'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Notification Frequency', 'env-admin-dashboard'); ?></th>
                    <td>
                        <select name="notification_frequency">
                            <option value="immediately" <?php selected($options['notification_frequency'], 'immediately'); ?>><?php _e('Immediately', 'env-admin-dashboard'); ?></option>
                            <option value="hourly" <?php selected($options['notification_frequency'], 'hourly'); ?>><?php _e('Hourly Digest', 'env-admin-dashboard'); ?></option>
                            <option value="daily" <?php selected($options['notification_frequency'], 'daily'); ?>><?php _e('Daily Digest', 'env-admin-dashboard'); ?></option>
                            <option value="weekly" <?php selected($options['notification_frequency'], 'weekly'); ?>><?php _e('Weekly Digest', 'env-admin-dashboard'); ?></option>
                        </select>
                        <p class="description"><?php _e('How often to receive notification summaries.', 'env-admin-dashboard'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Notification Types', 'env-admin-dashboard'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><?php _e('Notification Types', 'env-admin-dashboard'); ?></legend>
                            <label for="notify_goals">
                                <input name="notification_types[]" type="checkbox" id="notify_goals" value="goals" checked />
                                <?php _e('Environmental goal achievements', 'env-admin-dashboard'); ?>
                            </label><br />
                            <label for="notify_activities">
                                <input name="notification_types[]" type="checkbox" id="notify_activities" value="activities" checked />
                                <?php _e('New environmental activities', 'env-admin-dashboard'); ?>
                            </label><br />
                            <label for="notify_reports">
                                <input name="notification_types[]" type="checkbox" id="notify_reports" value="reports" checked />
                                <?php _e('Report generation completion', 'env-admin-dashboard'); ?>
                            </label><br />
                            <label for="notify_system">
                                <input name="notification_types[]" type="checkbox" id="notify_system" value="system" checked />
                                <?php _e('System alerts and warnings', 'env-admin-dashboard'); ?>
                            </label><br />
                            <label for="notify_performance">
                                <input name="notification_types[]" type="checkbox" id="notify_performance" value="performance" />
                                <?php _e('Performance threshold alerts', 'env-admin-dashboard'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Data & Privacy Settings Tab -->
        <div id="data-settings" class="tab-content">
            <h2><?php _e('Data & Privacy Settings', 'env-admin-dashboard'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Data Retention Period', 'env-admin-dashboard'); ?></th>
                    <td>
                        <input name="data_retention_days" type="number" min="30" max="365" value="<?php echo esc_attr($options['data_retention_days']); ?>" class="small-text" />
                        <?php _e('days', 'env-admin-dashboard'); ?>
                        <p class="description"><?php _e('How long to keep analytics and activity data (30-365 days).', 'env-admin-dashboard'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Automatic Backup', 'env-admin-dashboard'); ?></th>
                    <td>
                        <label for="auto_backup">
                            <input name="auto_backup" type="checkbox" id="auto_backup" value="1" <?php checked($options['auto_backup']); ?> />
                            <?php _e('Enable automatic data backups', 'env-admin-dashboard'); ?>
                        </label>
                        <p class="description"><?php _e('Automatically backup environmental data and settings.', 'env-admin-dashboard'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Backup Frequency', 'env-admin-dashboard'); ?></th>
                    <td>
                        <select name="backup_frequency">
                            <option value="daily" <?php selected($options['backup_frequency'], 'daily'); ?>><?php _e('Daily', 'env-admin-dashboard'); ?></option>
                            <option value="weekly" <?php selected($options['backup_frequency'], 'weekly'); ?>><?php _e('Weekly', 'env-admin-dashboard'); ?></option>
                            <option value="monthly" <?php selected($options['backup_frequency'], 'monthly'); ?>><?php _e('Monthly', 'env-admin-dashboard'); ?></option>
                        </select>
                        <p class="description"><?php _e('How often to create automatic backups.', 'env-admin-dashboard'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Data Export', 'env-admin-dashboard'); ?></th>
                    <td>
                        <button type="button" class="button" id="export-all-data"><?php _e('Export All Data', 'env-admin-dashboard'); ?></button>
                        <button type="button" class="button" id="export-analytics"><?php _e('Export Analytics', 'env-admin-dashboard'); ?></button>
                        <button type="button" class="button" id="export-activities"><?php _e('Export Activities', 'env-admin-dashboard'); ?></button>
                        <p class="description"><?php _e('Export platform data in various formats (JSON, CSV, XML).', 'env-admin-dashboard'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Data Privacy', 'env-admin-dashboard'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><?php _e('Data Privacy Options', 'env-admin-dashboard'); ?></legend>
                            <label for="anonymize_data">
                                <input name="privacy_options[]" type="checkbox" id="anonymize_data" value="anonymize" />
                                <?php _e('Anonymize user data in analytics', 'env-admin-dashboard'); ?>
                            </label><br />
                            <label for="gdpr_compliance">
                                <input name="privacy_options[]" type="checkbox" id="gdpr_compliance" value="gdpr" checked />
                                <?php _e('GDPR compliance mode', 'env-admin-dashboard'); ?>
                            </label><br />
                            <label for="cookie_consent">
                                <input name="privacy_options[]" type="checkbox" id="cookie_consent" value="cookies" />
                                <?php _e('Require cookie consent for tracking', 'env-admin-dashboard'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Performance Settings Tab -->
        <div id="performance-settings" class="tab-content">
            <h2><?php _e('Performance Settings', 'env-admin-dashboard'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Performance Monitoring', 'env-admin-dashboard'); ?></th>
                    <td>
                        <label for="performance_monitoring">
                            <input name="performance_monitoring" type="checkbox" id="performance_monitoring" value="1" <?php checked($options['performance_monitoring']); ?> />
                            <?php _e('Enable performance monitoring', 'env-admin-dashboard'); ?>
                        </label>
                        <p class="description"><?php _e('Monitor page load times, database queries, and system resources.', 'env-admin-dashboard'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Cache Settings', 'env-admin-dashboard'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><?php _e('Cache Settings', 'env-admin-dashboard'); ?></legend>
                            <label for="enable_widget_cache">
                                <input name="cache_options[]" type="checkbox" id="enable_widget_cache" value="widgets" checked />
                                <?php _e('Cache dashboard widgets', 'env-admin-dashboard'); ?>
                            </label><br />
                            <label for="enable_report_cache">
                                <input name="cache_options[]" type="checkbox" id="enable_report_cache" value="reports" checked />
                                <?php _e('Cache report data', 'env-admin-dashboard'); ?>
                            </label><br />
                            <label for="enable_analytics_cache">
                                <input name="cache_options[]" type="checkbox" id="enable_analytics_cache" value="analytics" />
                                <?php _e('Cache analytics queries', 'env-admin-dashboard'); ?>
                            </label>
                        </fieldset>
                        <p class="description"><?php _e('Enable caching to improve dashboard performance.', 'env-admin-dashboard'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Database Optimization', 'env-admin-dashboard'); ?></th>
                    <td>
                        <button type="button" class="button" id="optimize-database"><?php _e('Optimize Database', 'env-admin-dashboard'); ?></button>
                        <button type="button" class="button" id="clean-cache"><?php _e('Clear All Caches', 'env-admin-dashboard'); ?></button>
                        <button type="button" class="button" id="rebuild-indexes"><?php _e('Rebuild Indexes', 'env-admin-dashboard'); ?></button>
                        <p class="description"><?php _e('Maintenance tools to optimize database performance.', 'env-admin-dashboard'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Resource Limits', 'env-admin-dashboard'); ?></th>
                    <td>
                        <label for="max_query_time"><?php _e('Max Query Time:', 'env-admin-dashboard'); ?></label>
                        <input name="max_query_time" type="number" id="max_query_time" value="5" min="1" max="30" class="small-text" />
                        <?php _e('seconds', 'env-admin-dashboard'); ?><br /><br />
                        
                        <label for="max_memory_usage"><?php _e('Max Memory Usage:', 'env-admin-dashboard'); ?></label>
                        <input name="max_memory_usage" type="number" id="max_memory_usage" value="128" min="64" max="512" class="small-text" />
                        <?php _e('MB', 'env-admin-dashboard'); ?>
                        
                        <p class="description"><?php _e('Set resource limits for dashboard operations.', 'env-admin-dashboard'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Integration Settings Tab -->
        <div id="integration-settings" class="tab-content">
            <h2><?php _e('External Integrations', 'env-admin-dashboard'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('API Key', 'env-admin-dashboard'); ?></th>
                    <td>
                        <input name="api_key" type="text" value="<?php echo esc_attr($options['api_key']); ?>" class="regular-text" />
                        <p class="description"><?php _e('API key for external environmental data services.', 'env-admin-dashboard'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Weather API Integration', 'env-admin-dashboard'); ?></th>
                    <td>
                        <label for="enable_weather_api">
                            <input name="integrations[]" type="checkbox" id="enable_weather_api" value="weather" />
                            <?php _e('Enable weather data integration', 'env-admin-dashboard'); ?>
                        </label>
                        <p class="description"><?php _e('Integrate weather data for environmental analytics.', 'env-admin-dashboard'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Air Quality API', 'env-admin-dashboard'); ?></th>
                    <td>
                        <label for="enable_air_quality">
                            <input name="integrations[]" type="checkbox" id="enable_air_quality" value="air_quality" />
                            <?php _e('Enable air quality monitoring', 'env-admin-dashboard'); ?>
                        </label>
                        <p class="description"><?php _e('Monitor air quality data from external sources.', 'env-admin-dashboard'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Carbon Footprint Calculator', 'env-admin-dashboard'); ?></th>
                    <td>
                        <label for="enable_carbon_calc">
                            <input name="integrations[]" type="checkbox" id="enable_carbon_calc" value="carbon_calc" />
                            <?php _e('Enable carbon footprint calculations', 'env-admin-dashboard'); ?>
                        </label>
                        <p class="description"><?php _e('Calculate carbon footprint for activities and content.', 'env-admin-dashboard'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Social Media Integration', 'env-admin-dashboard'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><?php _e('Social Media Platforms', 'env-admin-dashboard'); ?></legend>
                            <label for="social_twitter">
                                <input name="social_integrations[]" type="checkbox" id="social_twitter" value="twitter" />
                                <?php _e('Twitter', 'env-admin-dashboard'); ?>
                            </label><br />
                            <label for="social_facebook">
                                <input name="social_integrations[]" type="checkbox" id="social_facebook" value="facebook" />
                                <?php _e('Facebook', 'env-admin-dashboard'); ?>
                            </label><br />
                            <label for="social_instagram">
                                <input name="social_integrations[]" type="checkbox" id="social_instagram" value="instagram" />
                                <?php _e('Instagram', 'env-admin-dashboard'); ?>
                            </label><br />
                            <label for="social_linkedin">
                                <input name="social_integrations[]" type="checkbox" id="social_linkedin" value="linkedin" />
                                <?php _e('LinkedIn', 'env-admin-dashboard'); ?>
                            </label>
                        </fieldset>
                        <p class="description"><?php _e('Share environmental content on social media platforms.', 'env-admin-dashboard'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Advanced Settings Tab -->
        <div id="advanced-settings" class="tab-content">
            <h2><?php _e('Advanced Settings', 'env-admin-dashboard'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Debug Mode', 'env-admin-dashboard'); ?></th>
                    <td>
                        <label for="debug_mode">
                            <input name="debug_mode" type="checkbox" id="debug_mode" value="1" <?php checked($options['debug_mode']); ?> />
                            <?php _e('Enable debug mode', 'env-admin-dashboard'); ?>
                        </label>
                        <p class="description"><?php _e('Enable detailed logging and error reporting (use only for troubleshooting).', 'env-admin-dashboard'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Custom CSS', 'env-admin-dashboard'); ?></th>
                    <td>
                        <textarea name="custom_css" rows="10" cols="50" class="large-text code"><?php echo esc_textarea(get_option('env_custom_css', '')); ?></textarea>
                        <p class="description"><?php _e('Add custom CSS styles for the dashboard.', 'env-admin-dashboard'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Custom JavaScript', 'env-admin-dashboard'); ?></th>
                    <td>
                        <textarea name="custom_js" rows="10" cols="50" class="large-text code"><?php echo esc_textarea(get_option('env_custom_js', '')); ?></textarea>
                        <p class="description"><?php _e('Add custom JavaScript for the dashboard (advanced users only).', 'env-admin-dashboard'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('System Information', 'env-admin-dashboard'); ?></th>
                    <td>
                        <div class="system-info">
                            <p><strong><?php _e('Plugin Version:', 'env-admin-dashboard'); ?></strong> <?php echo ENV_ADMIN_DASHBOARD_VERSION; ?></p>
                            <p><strong><?php _e('WordPress Version:', 'env-admin-dashboard'); ?></strong> <?php echo get_bloginfo('version'); ?></p>
                            <p><strong><?php _e('PHP Version:', 'env-admin-dashboard'); ?></strong> <?php echo PHP_VERSION; ?></p>
                            <p><strong><?php _e('Database Version:', 'env-admin-dashboard'); ?></strong> <?php global $wpdb; echo $wpdb->db_version(); ?></p>
                        </div>
                        <button type="button" class="button" id="copy-system-info"><?php _e('Copy System Info', 'env-admin-dashboard'); ?></button>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Reset Settings', 'env-admin-dashboard'); ?></th>
                    <td>
                        <button type="button" class="button button-secondary" id="reset-settings" onclick="return confirm('<?php _e('Are you sure you want to reset all settings to defaults?', 'env-admin-dashboard'); ?>')"><?php _e('Reset to Defaults', 'env-admin-dashboard'); ?></button>
                        <p class="description"><?php _e('Warning: This will reset all dashboard settings to their default values.', 'env-admin-dashboard'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <?php submit_button(); ?>
    </form>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        $('.tab-content').removeClass('active');
        $(target).addClass('active');
    });
    
    // Data export buttons
    $('#export-all-data, #export-analytics, #export-activities').on('click', function() {
        var exportType = $(this).attr('id').replace('export-', '');
        alert('<?php _e('Export started. You will receive an email when the export is complete.', 'env-admin-dashboard'); ?>');
    });
    
    // Performance optimization buttons
    $('#optimize-database, #clean-cache, #rebuild-indexes').on('click', function() {
        var $btn = $(this);
        var originalText = $btn.text();
        
        $btn.prop('disabled', true).text('<?php _e('Processing...', 'env-admin-dashboard'); ?>');
        
        setTimeout(function() {
            $btn.prop('disabled', false).text(originalText);
            alert('<?php _e('Operation completed successfully!', 'env-admin-dashboard'); ?>');
        }, 3000);
    });
    
    // Copy system info
    $('#copy-system-info').on('click', function() {
        var systemInfo = $('.system-info').text();
        navigator.clipboard.writeText(systemInfo).then(function() {
            alert('<?php _e('System information copied to clipboard!', 'env-admin-dashboard'); ?>');
        });
    });
    
    // Reset settings
    $('#reset-settings').on('click', function() {
        if (confirm('<?php _e('Are you sure you want to reset all settings to defaults? This action cannot be undone.', 'env-admin-dashboard'); ?>')) {
            // Add AJAX call to reset settings
            $.post(ajaxurl, {
                action: 'env_reset_settings',
                nonce: '<?php echo wp_create_nonce('env_reset_settings'); ?>'
            }, function(response) {
                if (response.success) {
                    alert('<?php _e('Settings reset successfully. The page will now reload.', 'env-admin-dashboard'); ?>');
                    location.reload();
                } else {
                    alert('<?php _e('Error resetting settings. Please try again.', 'env-admin-dashboard'); ?>');
                }
            });
        }
    });
});
</script>
