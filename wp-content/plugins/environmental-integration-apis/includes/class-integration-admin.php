<?php
/**
 * Integration Admin Dashboard
 *
 * @package Environmental_Integration_APIs
 * @subpackage Integration_Admin
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Environmental Integration Admin class
 *
 * Handles the administrative dashboard for managing all integrations
 */
class Environmental_Integration_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_eia_save_api_config', array($this, 'save_api_config'));
        add_action('wp_ajax_eia_test_api_connection', array($this, 'test_api_connection'));
        add_action('wp_ajax_eia_get_dashboard_stats', array($this, 'get_dashboard_stats'));
        add_action('wp_ajax_eia_export_logs', array($this, 'export_logs'));
        add_action('wp_ajax_eia_clear_cache', array($this, 'clear_cache'));
    }

    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Integration APIs', 'environmental-integration-apis'),
            __('Integration APIs', 'environmental-integration-apis'),
            'manage_options',
            'eia-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-networking',
            30
        );

        add_submenu_page(
            'eia-dashboard',
            __('API Configuration', 'environmental-integration-apis'),
            __('API Configuration', 'environmental-integration-apis'),
            'manage_options',
            'eia-api-config',
            array($this, 'api_config_page')
        );

        add_submenu_page(
            'eia-dashboard',
            __('Webhooks', 'environmental-integration-apis'),
            __('Webhooks', 'environmental-integration-apis'),
            'manage_options',
            'eia-webhooks',
            array($this, 'webhooks_page')
        );

        add_submenu_page(
            'eia-dashboard',
            __('API Monitoring', 'environmental-integration-apis'),
            __('API Monitoring', 'environmental-integration-apis'),
            'manage_options',
            'eia-monitoring',
            array($this, 'monitoring_page')
        );

        add_submenu_page(
            'eia-dashboard',
            __('Logs', 'environmental-integration-apis'),
            __('Logs', 'environmental-integration-apis'),
            'manage_options',
            'eia-logs',
            array($this, 'logs_page')
        );

        add_submenu_page(
            'eia-dashboard',
            __('Settings', 'environmental-integration-apis'),
            __('Settings', 'environmental-integration-apis'),
            'manage_options',
            'eia-settings',
            array($this, 'settings_page')
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        // Google Maps settings
        register_setting('eia_google_maps', 'eia_google_maps_api_key');
        register_setting('eia_google_maps', 'eia_google_maps_enabled');
        register_setting('eia_google_maps', 'eia_google_maps_libraries');

        // Weather API settings
        register_setting('eia_weather', 'eia_weather_provider');
        register_setting('eia_weather', 'eia_weather_api_key');
        register_setting('eia_weather', 'eia_weather_units');
        register_setting('eia_weather', 'eia_weather_cache_duration');

        // Air Quality settings
        register_setting('eia_air_quality', 'eia_air_quality_provider');
        register_setting('eia_air_quality', 'eia_air_quality_api_key');
        register_setting('eia_air_quality', 'eia_air_quality_alerts_enabled');

        // Social Media settings
        register_setting('eia_social_media', 'eia_facebook_app_id');
        register_setting('eia_social_media', 'eia_facebook_app_secret');
        register_setting('eia_social_media', 'eia_twitter_api_key');
        register_setting('eia_social_media', 'eia_twitter_api_secret');
        register_setting('eia_social_media', 'eia_instagram_access_token');

        // General settings
        register_setting('eia_general', 'eia_rate_limiting');
        register_setting('eia_general', 'eia_log_retention_days');
        register_setting('eia_general', 'eia_cache_enabled');
        register_setting('eia_general', 'eia_debug_mode');
    }

    /**
     * Dashboard page
     */
    public function dashboard_page() {
        ?>
        <div class="wrap eia-admin-wrap">
            <h1><?php _e('Integration APIs Dashboard', 'environmental-integration-apis'); ?></h1>
            
            <div class="eia-dashboard-grid">
                <!-- API Status Cards -->
                <div class="eia-card eia-status-card">
                    <h3><?php _e('API Status', 'environmental-integration-apis'); ?></h3>
                    <div id="eia-api-status" class="eia-status-grid">
                        <div class="eia-status-item">
                            <span class="eia-status-indicator" data-api="google_maps"></span>
                            <span><?php _e('Google Maps', 'environmental-integration-apis'); ?></span>
                        </div>
                        <div class="eia-status-item">
                            <span class="eia-status-indicator" data-api="weather"></span>
                            <span><?php _e('Weather API', 'environmental-integration-apis'); ?></span>
                        </div>
                        <div class="eia-status-item">
                            <span class="eia-status-indicator" data-api="air_quality"></span>
                            <span><?php _e('Air Quality', 'environmental-integration-apis'); ?></span>
                        </div>
                        <div class="eia-status-item">
                            <span class="eia-status-indicator" data-api="social_media"></span>
                            <span><?php _e('Social Media', 'environmental-integration-apis'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="eia-card eia-stats-card">
                    <h3><?php _e('24h Statistics', 'environmental-integration-apis'); ?></h3>
                    <div class="eia-stats-grid">
                        <div class="eia-stat-item">
                            <span class="eia-stat-number" id="eia-total-requests">-</span>
                            <span class="eia-stat-label"><?php _e('Total Requests', 'environmental-integration-apis'); ?></span>
                        </div>
                        <div class="eia-stat-item">
                            <span class="eia-stat-number" id="eia-success-rate">-</span>
                            <span class="eia-stat-label"><?php _e('Success Rate', 'environmental-integration-apis'); ?></span>
                        </div>
                        <div class="eia-stat-item">
                            <span class="eia-stat-number" id="eia-avg-response">-</span>
                            <span class="eia-stat-label"><?php _e('Avg Response', 'environmental-integration-apis'); ?></span>
                        </div>
                        <div class="eia-stat-item">
                            <span class="eia-stat-number" id="eia-cache-hits">-</span>
                            <span class="eia-stat-label"><?php _e('Cache Hits', 'environmental-integration-apis'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="eia-card eia-activity-card">
                    <h3><?php _e('Recent Activity', 'environmental-integration-apis'); ?></h3>
                    <div id="eia-recent-activity" class="eia-activity-list">
                        <!-- Activity items loaded via AJAX -->
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="eia-card eia-actions-card">
                    <h3><?php _e('Quick Actions', 'environmental-integration-apis'); ?></h3>
                    <div class="eia-actions-list">
                        <button class="button button-primary" id="eia-test-all-apis">
                            <?php _e('Test All APIs', 'environmental-integration-apis'); ?>
                        </button>
                        <button class="button" id="eia-clear-cache-btn">
                            <?php _e('Clear Cache', 'environmental-integration-apis'); ?>
                        </button>
                        <button class="button" id="eia-export-logs-btn">
                            <?php _e('Export Logs', 'environmental-integration-apis'); ?>
                        </button>
                        <button class="button" id="eia-refresh-stats">
                            <?php _e('Refresh Stats', 'environmental-integration-apis'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Performance Chart -->
            <div class="eia-card eia-chart-card">
                <h3><?php _e('API Performance (Last 7 Days)', 'environmental-integration-apis'); ?></h3>
                <canvas id="eia-performance-chart" width="800" height="400"></canvas>
            </div>
        </div>
        <?php
    }

    /**
     * API Configuration page
     */
    public function api_config_page() {
        if (isset($_POST['submit'])) {
            $this->save_api_settings();
        }
        ?>
        <div class="wrap eia-admin-wrap">
            <h1><?php _e('API Configuration', 'environmental-integration-apis'); ?></h1>

            <form method="post" action="">
                <?php wp_nonce_field('eia_api_config', 'eia_api_config_nonce'); ?>

                <!-- Google Maps Configuration -->
                <div class="eia-card">
                    <h3><?php _e('Google Maps API', 'environmental-integration-apis'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Enable Google Maps', 'environmental-integration-apis'); ?></th>
                            <td>
                                <input type="checkbox" name="eia_google_maps_enabled" value="1" 
                                       <?php checked(get_option('eia_google_maps_enabled', 0)); ?> />
                                <p class="description"><?php _e('Enable Google Maps integration', 'environmental-integration-apis'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('API Key', 'environmental-integration-apis'); ?></th>
                            <td>
                                <input type="text" name="eia_google_maps_api_key" 
                                       value="<?php echo esc_attr(get_option('eia_google_maps_api_key', '')); ?>" 
                                       class="regular-text" />
                                <button type="button" class="button eia-test-api" data-api="google_maps">
                                    <?php _e('Test Connection', 'environmental-integration-apis'); ?>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Libraries', 'environmental-integration-apis'); ?></th>
                            <td>
                                <input type="text" name="eia_google_maps_libraries" 
                                       value="<?php echo esc_attr(get_option('eia_google_maps_libraries', 'places,geometry')); ?>" 
                                       class="regular-text" />
                                <p class="description"><?php _e('Comma-separated list of Google Maps libraries', 'environmental-integration-apis'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Weather API Configuration -->
                <div class="eia-card">
                    <h3><?php _e('Weather API', 'environmental-integration-apis'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Provider', 'environmental-integration-apis'); ?></th>
                            <td>
                                <select name="eia_weather_provider">
                                    <option value="openweathermap" <?php selected(get_option('eia_weather_provider'), 'openweathermap'); ?>>
                                        OpenWeatherMap
                                    </option>
                                    <option value="weatherapi" <?php selected(get_option('eia_weather_provider'), 'weatherapi'); ?>>
                                        WeatherAPI
                                    </option>
                                    <option value="accuweather" <?php selected(get_option('eia_weather_provider'), 'accuweather'); ?>>
                                        AccuWeather
                                    </option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('API Key', 'environmental-integration-apis'); ?></th>
                            <td>
                                <input type="text" name="eia_weather_api_key" 
                                       value="<?php echo esc_attr(get_option('eia_weather_api_key', '')); ?>" 
                                       class="regular-text" />
                                <button type="button" class="button eia-test-api" data-api="weather">
                                    <?php _e('Test Connection', 'environmental-integration-apis'); ?>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Units', 'environmental-integration-apis'); ?></th>
                            <td>
                                <select name="eia_weather_units">
                                    <option value="metric" <?php selected(get_option('eia_weather_units'), 'metric'); ?>>
                                        Metric (°C, m/s)
                                    </option>
                                    <option value="imperial" <?php selected(get_option('eia_weather_units'), 'imperial'); ?>>
                                        Imperial (°F, mph)
                                    </option>
                                    <option value="kelvin" <?php selected(get_option('eia_weather_units'), 'kelvin'); ?>>
                                        Kelvin
                                    </option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Cache Duration (minutes)', 'environmental-integration-apis'); ?></th>
                            <td>
                                <input type="number" name="eia_weather_cache_duration" 
                                       value="<?php echo esc_attr(get_option('eia_weather_cache_duration', 30)); ?>" 
                                       min="5" max="1440" />
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Air Quality Configuration -->
                <div class="eia-card">
                    <h3><?php _e('Air Quality API', 'environmental-integration-apis'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Provider', 'environmental-integration-apis'); ?></th>
                            <td>
                                <select name="eia_air_quality_provider">
                                    <option value="iqair" <?php selected(get_option('eia_air_quality_provider'), 'iqair'); ?>>
                                        IQAir
                                    </option>
                                    <option value="openweathermap" <?php selected(get_option('eia_air_quality_provider'), 'openweathermap'); ?>>
                                        OpenWeatherMap
                                    </option>
                                    <option value="airnow" <?php selected(get_option('eia_air_quality_provider'), 'airnow'); ?>>
                                        AirNow
                                    </option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('API Key', 'environmental-integration-apis'); ?></th>
                            <td>
                                <input type="text" name="eia_air_quality_api_key" 
                                       value="<?php echo esc_attr(get_option('eia_air_quality_api_key', '')); ?>" 
                                       class="regular-text" />
                                <button type="button" class="button eia-test-api" data-api="air_quality">
                                    <?php _e('Test Connection', 'environmental-integration-apis'); ?>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Enable Alerts', 'environmental-integration-apis'); ?></th>
                            <td>
                                <input type="checkbox" name="eia_air_quality_alerts_enabled" value="1" 
                                       <?php checked(get_option('eia_air_quality_alerts_enabled', 1)); ?> />
                                <p class="description"><?php _e('Send alerts for poor air quality', 'environmental-integration-apis'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Social Media Configuration -->
                <div class="eia-card">
                    <h3><?php _e('Social Media APIs', 'environmental-integration-apis'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Facebook App ID', 'environmental-integration-apis'); ?></th>
                            <td>
                                <input type="text" name="eia_facebook_app_id" 
                                       value="<?php echo esc_attr(get_option('eia_facebook_app_id', '')); ?>" 
                                       class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Facebook App Secret', 'environmental-integration-apis'); ?></th>
                            <td>
                                <input type="password" name="eia_facebook_app_secret" 
                                       value="<?php echo esc_attr(get_option('eia_facebook_app_secret', '')); ?>" 
                                       class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Twitter API Key', 'environmental-integration-apis'); ?></th>
                            <td>
                                <input type="text" name="eia_twitter_api_key" 
                                       value="<?php echo esc_attr(get_option('eia_twitter_api_key', '')); ?>" 
                                       class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Twitter API Secret', 'environmental-integration-apis'); ?></th>
                            <td>
                                <input type="password" name="eia_twitter_api_secret" 
                                       value="<?php echo esc_attr(get_option('eia_twitter_api_secret', '')); ?>" 
                                       class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Instagram Access Token', 'environmental-integration-apis'); ?></th>
                            <td>
                                <input type="text" name="eia_instagram_access_token" 
                                       value="<?php echo esc_attr(get_option('eia_instagram_access_token', '')); ?>" 
                                       class="regular-text" />
                            </td>
                        </tr>
                    </table>
                    <p class="description">
                        <button type="button" class="button eia-test-api" data-api="social_media">
                            <?php _e('Test All Social Connections', 'environmental-integration-apis'); ?>
                        </button>
                    </p>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Webhooks management page
     */
    public function webhooks_page() {
        ?>
        <div class="wrap eia-admin-wrap">
            <h1><?php _e('Webhook Management', 'environmental-integration-apis'); ?></h1>

            <div class="eia-webhooks-toolbar">
                <button class="button button-primary" id="eia-add-webhook">
                    <?php _e('Add New Webhook', 'environmental-integration-apis'); ?>
                </button>
                <button class="button" id="eia-test-webhooks">
                    <?php _e('Test All Webhooks', 'environmental-integration-apis'); ?>
                </button>
            </div>

            <div class="eia-card">
                <h3><?php _e('Active Webhooks', 'environmental-integration-apis'); ?></h3>
                <table class="wp-list-table widefat fixed striped" id="eia-webhooks-table">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'environmental-integration-apis'); ?></th>
                            <th><?php _e('URL', 'environmental-integration-apis'); ?></th>
                            <th><?php _e('Events', 'environmental-integration-apis'); ?></th>
                            <th><?php _e('Status', 'environmental-integration-apis'); ?></th>
                            <th><?php _e('Last Delivery', 'environmental-integration-apis'); ?></th>
                            <th><?php _e('Actions', 'environmental-integration-apis'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Webhook rows loaded via AJAX -->
                    </tbody>
                </table>
            </div>

            <!-- Webhook Form Modal -->
            <div id="eia-webhook-modal" class="eia-modal" style="display: none;">
                <div class="eia-modal-content">
                    <div class="eia-modal-header">
                        <h3 id="eia-webhook-modal-title"><?php _e('Add Webhook', 'environmental-integration-apis'); ?></h3>
                        <span class="eia-modal-close">&times;</span>
                    </div>
                    <form id="eia-webhook-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Name', 'environmental-integration-apis'); ?></th>
                                <td>
                                    <input type="text" name="webhook_name" required class="regular-text" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('URL', 'environmental-integration-apis'); ?></th>
                                <td>
                                    <input type="url" name="webhook_url" required class="regular-text" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Events', 'environmental-integration-apis'); ?></th>
                                <td>
                                    <div class="eia-checkbox-group">
                                        <label><input type="checkbox" name="events[]" value="weather_alert"> <?php _e('Weather Alert', 'environmental-integration-apis'); ?></label>
                                        <label><input type="checkbox" name="events[]" value="air_quality_alert"> <?php _e('Air Quality Alert', 'environmental-integration-apis'); ?></label>
                                        <label><input type="checkbox" name="events[]" value="api_error"> <?php _e('API Error', 'environmental-integration-apis'); ?></label>
                                        <label><input type="checkbox" name="events[]" value="rate_limit_exceeded"> <?php _e('Rate Limit Exceeded', 'environmental-integration-apis'); ?></label>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Secret', 'environmental-integration-apis'); ?></th>
                                <td>
                                    <input type="text" name="webhook_secret" class="regular-text" />
                                    <p class="description"><?php _e('Optional secret for webhook signature verification', 'environmental-integration-apis'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Active', 'environmental-integration-apis'); ?></th>
                                <td>
                                    <input type="checkbox" name="webhook_active" value="1" checked />
                                </td>
                            </tr>
                        </table>
                        <div class="eia-modal-footer">
                            <button type="submit" class="button button-primary"><?php _e('Save Webhook', 'environmental-integration-apis'); ?></button>
                            <button type="button" class="button eia-modal-close"><?php _e('Cancel', 'environmental-integration-apis'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * API Monitoring page
     */
    public function monitoring_page() {
        ?>
        <div class="wrap eia-admin-wrap">
            <h1><?php _e('API Monitoring', 'environmental-integration-apis'); ?></h1>

            <div class="eia-monitoring-grid">
                <!-- Health Status -->
                <div class="eia-card eia-health-card">
                    <h3><?php _e('API Health Status', 'environmental-integration-apis'); ?></h3>
                    <div id="eia-health-status" class="eia-health-grid">
                        <!-- Health indicators loaded via AJAX -->
                    </div>
                </div>

                <!-- Rate Limiting -->
                <div class="eia-card eia-rate-limit-card">
                    <h3><?php _e('Rate Limiting', 'environmental-integration-apis'); ?></h3>
                    <div id="eia-rate-limits" class="eia-rate-limit-grid">
                        <!-- Rate limit info loaded via AJAX -->
                    </div>
                </div>

                <!-- Performance Metrics -->
                <div class="eia-card eia-performance-card">
                    <h3><?php _e('Performance Metrics', 'environmental-integration-apis'); ?></h3>
                    <canvas id="eia-response-time-chart" width="400" height="200"></canvas>
                </div>

                <!-- Error Rates -->
                <div class="eia-card eia-error-card">
                    <h3><?php _e('Error Rates', 'environmental-integration-apis'); ?></h3>
                    <canvas id="eia-error-rate-chart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Detailed Monitoring Table -->
            <div class="eia-card">
                <h3><?php _e('API Endpoint Monitoring', 'environmental-integration-apis'); ?></h3>
                <table class="wp-list-table widefat fixed striped" id="eia-monitoring-table">
                    <thead>
                        <tr>
                            <th><?php _e('API', 'environmental-integration-apis'); ?></th>
                            <th><?php _e('Endpoint', 'environmental-integration-apis'); ?></th>
                            <th><?php _e('Status', 'environmental-integration-apis'); ?></th>
                            <th><?php _e('Response Time', 'environmental-integration-apis'); ?></th>
                            <th><?php _e('Success Rate', 'environmental-integration-apis'); ?></th>
                            <th><?php _e('Last Check', 'environmental-integration-apis'); ?></th>
                            <th><?php _e('Actions', 'environmental-integration-apis'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Monitoring rows loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Logs page
     */
    public function logs_page() {
        ?>
        <div class="wrap eia-admin-wrap">
            <h1><?php _e('API Logs', 'environmental-integration-apis'); ?></h1>

            <!-- Log Filters -->
            <div class="eia-card eia-log-filters">
                <h3><?php _e('Filter Logs', 'environmental-integration-apis'); ?></h3>
                <form id="eia-log-filter-form" class="eia-filter-form">
                    <div class="eia-filter-row">
                        <div class="eia-filter-group">
                            <label><?php _e('API Service', 'environmental-integration-apis'); ?></label>
                            <select name="api_service">
                                <option value=""><?php _e('All Services', 'environmental-integration-apis'); ?></option>
                                <option value="google_maps">Google Maps</option>
                                <option value="weather">Weather</option>
                                <option value="air_quality">Air Quality</option>
                                <option value="social_media">Social Media</option>
                            </select>
                        </div>
                        <div class="eia-filter-group">
                            <label><?php _e('Status', 'environmental-integration-apis'); ?></label>
                            <select name="status">
                                <option value=""><?php _e('All Status', 'environmental-integration-apis'); ?></option>
                                <option value="success">Success</option>
                                <option value="error">Error</option>
                                <option value="timeout">Timeout</option>
                            </select>
                        </div>
                        <div class="eia-filter-group">
                            <label><?php _e('Date Range', 'environmental-integration-apis'); ?></label>
                            <input type="date" name="date_from" />
                            <input type="date" name="date_to" />
                        </div>
                        <div class="eia-filter-group">
                            <button type="submit" class="button button-primary"><?php _e('Filter', 'environmental-integration-apis'); ?></button>
                            <button type="button" class="button" id="eia-clear-filters"><?php _e('Clear', 'environmental-integration-apis'); ?></button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Log Table -->
            <div class="eia-card">
                <div class="eia-log-header">
                    <h3><?php _e('Request Logs', 'environmental-integration-apis'); ?></h3>
                    <div class="eia-log-actions">
                        <button class="button" id="eia-export-filtered-logs">
                            <?php _e('Export Filtered', 'environmental-integration-apis'); ?>
                        </button>
                        <button class="button" id="eia-clear-old-logs">
                            <?php _e('Clear Old Logs', 'environmental-integration-apis'); ?>
                        </button>
                    </div>
                </div>
                <table class="wp-list-table widefat fixed striped" id="eia-logs-table">
                    <thead>
                        <tr>
                            <th><?php _e('Timestamp', 'environmental-integration-apis'); ?></th>
                            <th><?php _e('API', 'environmental-integration-apis'); ?></th>
                            <th><?php _e('Endpoint', 'environmental-integration-apis'); ?></th>
                            <th><?php _e('Status', 'environmental-integration-apis'); ?></th>
                            <th><?php _e('Response Time', 'environmental-integration-apis'); ?></th>
                            <th><?php _e('Response Code', 'environmental-integration-apis'); ?></th>
                            <th><?php _e('Actions', 'environmental-integration-apis'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Log rows loaded via AJAX -->
                    </tbody>
                </table>
                <div class="eia-pagination">
                    <!-- Pagination loaded via AJAX -->
                </div>
            </div>

            <!-- Log Detail Modal -->
            <div id="eia-log-detail-modal" class="eia-modal" style="display: none;">
                <div class="eia-modal-content eia-modal-large">
                    <div class="eia-modal-header">
                        <h3><?php _e('Log Details', 'environmental-integration-apis'); ?></h3>
                        <span class="eia-modal-close">&times;</span>
                    </div>
                    <div class="eia-modal-body" id="eia-log-detail-content">
                        <!-- Log details loaded via AJAX -->
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Settings page
     */
    public function settings_page() {
        if (isset($_POST['submit'])) {
            $this->save_general_settings();
        }
        ?>
        <div class="wrap eia-admin-wrap">
            <h1><?php _e('Integration Settings', 'environmental-integration-apis'); ?></h1>

            <form method="post" action="">
                <?php wp_nonce_field('eia_general_settings', 'eia_general_settings_nonce'); ?>

                <!-- General Settings -->
                <div class="eia-card">
                    <h3><?php _e('General Settings', 'environmental-integration-apis'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Enable Caching', 'environmental-integration-apis'); ?></th>
                            <td>
                                <input type="checkbox" name="eia_cache_enabled" value="1" 
                                       <?php checked(get_option('eia_cache_enabled', 1)); ?> />
                                <p class="description"><?php _e('Enable caching for API responses', 'environmental-integration-apis'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Debug Mode', 'environmental-integration-apis'); ?></th>
                            <td>
                                <input type="checkbox" name="eia_debug_mode" value="1" 
                                       <?php checked(get_option('eia_debug_mode', 0)); ?> />
                                <p class="description"><?php _e('Enable debug logging (not recommended for production)', 'environmental-integration-apis'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Log Retention (days)', 'environmental-integration-apis'); ?></th>
                            <td>
                                <input type="number" name="eia_log_retention_days" 
                                       value="<?php echo esc_attr(get_option('eia_log_retention_days', 30)); ?>" 
                                       min="1" max="365" />
                                <p class="description"><?php _e('Number of days to keep API logs', 'environmental-integration-apis'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Rate Limiting -->
                <div class="eia-card">
                    <h3><?php _e('Rate Limiting', 'environmental-integration-apis'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Enable Rate Limiting', 'environmental-integration-apis'); ?></th>
                            <td>
                                <input type="checkbox" name="eia_rate_limiting" value="1" 
                                       <?php checked(get_option('eia_rate_limiting', 1)); ?> />
                                <p class="description"><?php _e('Enable rate limiting for API requests', 'environmental-integration-apis'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Save API settings
     */
    private function save_api_settings() {
        if (!wp_verify_nonce($_POST['eia_api_config_nonce'], 'eia_api_config')) {
            wp_die(__('Security check failed', 'environmental-integration-apis'));
        }

        // Save all API settings
        $settings = array(
            'eia_google_maps_enabled',
            'eia_google_maps_api_key',
            'eia_google_maps_libraries',
            'eia_weather_provider',
            'eia_weather_api_key',
            'eia_weather_units',
            'eia_weather_cache_duration',
            'eia_air_quality_provider',
            'eia_air_quality_api_key',
            'eia_air_quality_alerts_enabled',
            'eia_facebook_app_id',
            'eia_facebook_app_secret',
            'eia_twitter_api_key',
            'eia_twitter_api_secret',
            'eia_instagram_access_token'
        );

        foreach ($settings as $setting) {
            if (isset($_POST[$setting])) {
                update_option($setting, sanitize_text_field($_POST[$setting]));
            }
        }

        echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'environmental-integration-apis') . '</p></div>';
    }

    /**
     * Save general settings
     */
    private function save_general_settings() {
        if (!wp_verify_nonce($_POST['eia_general_settings_nonce'], 'eia_general_settings')) {
            wp_die(__('Security check failed', 'environmental-integration-apis'));
        }

        update_option('eia_cache_enabled', isset($_POST['eia_cache_enabled']) ? 1 : 0);
        update_option('eia_debug_mode', isset($_POST['eia_debug_mode']) ? 1 : 0);
        update_option('eia_log_retention_days', intval($_POST['eia_log_retention_days']));
        update_option('eia_rate_limiting', isset($_POST['eia_rate_limiting']) ? 1 : 0);

        echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'environmental-integration-apis') . '</p></div>';
    }

    /**
     * AJAX: Save API configuration
     */
    public function save_api_config() {
        check_ajax_referer('eia_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'environmental-integration-apis'));
        }

        $api = sanitize_text_field($_POST['api']);
        $config = $_POST['config'];

        // Save configuration based on API type
        switch ($api) {
            case 'google_maps':
                update_option('eia_google_maps_api_key', sanitize_text_field($config['api_key']));
                update_option('eia_google_maps_enabled', $config['enabled'] ? 1 : 0);
                break;
            // Add other API configurations
        }

        wp_send_json_success(array('message' => __('Configuration saved', 'environmental-integration-apis')));
    }

    /**
     * AJAX: Test API connection
     */
    public function test_api_connection() {
        check_ajax_referer('eia_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'environmental-integration-apis'));
        }

        $api = sanitize_text_field($_POST['api']);
        $result = array('success' => false, 'message' => '');

        // Test API connection based on type
        switch ($api) {
            case 'google_maps':
                $result = $this->test_google_maps_connection();
                break;
            case 'weather':
                $result = $this->test_weather_connection();
                break;
            case 'air_quality':
                $result = $this->test_air_quality_connection();
                break;
            case 'social_media':
                $result = $this->test_social_media_connections();
                break;
        }

        wp_send_json($result);
    }

    /**
     * AJAX: Get dashboard statistics
     */
    public function get_dashboard_stats() {
        check_ajax_referer('eia_admin', 'nonce');

        global $wpdb;
        $table_logs = $wpdb->prefix . EIA_TABLE_PREFIX . 'api_logs';

        // Get 24h statistics
        $stats = array(
            'total_requests' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_logs} WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"),
            'success_rate' => 0,
            'avg_response_time' => 0,
            'cache_hits' => 0
        );

        $success_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_logs} WHERE status = 'success' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        if ($stats['total_requests'] > 0) {
            $stats['success_rate'] = round(($success_count / $stats['total_requests']) * 100, 1);
        }

        $avg_response = $wpdb->get_var("SELECT AVG(response_time) FROM {$table_logs} WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $stats['avg_response_time'] = round($avg_response, 2);

        wp_send_json_success($stats);
    }

    /**
     * AJAX: Export logs
     */
    public function export_logs() {
        check_ajax_referer('eia_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'environmental-integration-apis'));
        }

        global $wpdb;
        $table_logs = $wpdb->prefix . EIA_TABLE_PREFIX . 'api_logs';

        $logs = $wpdb->get_results("SELECT * FROM {$table_logs} ORDER BY created_at DESC LIMIT 1000");

        $csv_content = "Timestamp,API,Endpoint,Status,Response Time,Response Code,Error Message\n";
        foreach ($logs as $log) {
            $csv_content .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s\n",
                $log->created_at,
                $log->api_service,
                $log->endpoint,
                $log->status,
                $log->response_time,
                $log->response_code,
                str_replace('"', '""', $log->error_message)
            );
        }

        $filename = 'eia-logs-' . date('Y-m-d-H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $csv_content;
        exit;
    }

    /**
     * AJAX: Clear cache
     */
    public function clear_cache() {
        check_ajax_referer('eia_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'environmental-integration-apis'));
        }

        global $wpdb;
        $table_cache = $wpdb->prefix . EIA_TABLE_PREFIX . 'api_cache';

        $wpdb->query("DELETE FROM {$table_cache}");

        wp_send_json_success(array('message' => __('Cache cleared successfully', 'environmental-integration-apis')));
    }

    /**
     * Test Google Maps connection
     */
    private function test_google_maps_connection() {
        $api_key = get_option('eia_google_maps_api_key');
        if (empty($api_key)) {
            return array('success' => false, 'message' => __('API key not configured', 'environmental-integration-apis'));
        }

        // Test geocoding endpoint
        $test_url = "https://maps.googleapis.com/maps/api/geocode/json?address=New+York&key=" . $api_key;
        $response = wp_remote_get($test_url);

        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if ($body['status'] === 'OK') {
            return array('success' => true, 'message' => __('Connection successful', 'environmental-integration-apis'));
        } else {
            return array('success' => false, 'message' => $body['error_message'] ?? __('Unknown error', 'environmental-integration-apis'));
        }
    }

    /**
     * Test weather connection
     */
    private function test_weather_connection() {
        $provider = get_option('eia_weather_provider', 'openweathermap');
        $api_key = get_option('eia_weather_api_key');

        if (empty($api_key)) {
            return array('success' => false, 'message' => __('API key not configured', 'environmental-integration-apis'));
        }

        $test_url = '';
        switch ($provider) {
            case 'openweathermap':
                $test_url = "https://api.openweathermap.org/data/2.5/weather?q=London&appid=" . $api_key;
                break;
            case 'weatherapi':
                $test_url = "https://api.weatherapi.com/v1/current.json?key=" . $api_key . "&q=London";
                break;
            case 'accuweather':
                $test_url = "https://dataservice.accuweather.com/locations/v1/search?apikey=" . $api_key . "&q=London";
                break;
        }

        $response = wp_remote_get($test_url);
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code === 200) {
            return array('success' => true, 'message' => __('Connection successful', 'environmental-integration-apis'));
        } else {
            return array('success' => false, 'message' => __('API returned error code: ', 'environmental-integration-apis') . $status_code);
        }
    }

    /**
     * Test air quality connection
     */
    private function test_air_quality_connection() {
        $provider = get_option('eia_air_quality_provider', 'iqair');
        $api_key = get_option('eia_air_quality_api_key');

        if (empty($api_key)) {
            return array('success' => false, 'message' => __('API key not configured', 'environmental-integration-apis'));
        }

        $test_url = '';
        switch ($provider) {
            case 'iqair':
                $test_url = "https://api.airvisual.com/v2/nearest_city?key=" . $api_key;
                break;
            case 'openweathermap':
                $test_url = "https://api.openweathermap.org/data/2.5/air_pollution?lat=51.5074&lon=-0.1278&appid=" . $api_key;
                break;
            case 'airnow':
                $test_url = "https://www.airnowapi.org/aq/observation/zipCode/current/?format=application/json&zipCode=20002&distance=25&API_KEY=" . $api_key;
                break;
        }

        $response = wp_remote_get($test_url);
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code === 200) {
            return array('success' => true, 'message' => __('Connection successful', 'environmental-integration-apis'));
        } else {
            return array('success' => false, 'message' => __('API returned error code: ', 'environmental-integration-apis') . $status_code);
        }
    }

    /**
     * Test social media connections
     */
    private function test_social_media_connections() {
        $results = array();
        
        // Test Facebook
        $fb_app_id = get_option('eia_facebook_app_id');
        if (!empty($fb_app_id)) {
            $results['facebook'] = array('success' => true, 'message' => __('App ID configured', 'environmental-integration-apis'));
        } else {
            $results['facebook'] = array('success' => false, 'message' => __('App ID not configured', 'environmental-integration-apis'));
        }

        // Test Twitter
        $twitter_key = get_option('eia_twitter_api_key');
        if (!empty($twitter_key)) {
            $results['twitter'] = array('success' => true, 'message' => __('API key configured', 'environmental-integration-apis'));
        } else {
            $results['twitter'] = array('success' => false, 'message' => __('API key not configured', 'environmental-integration-apis'));
        }

        // Test Instagram
        $instagram_token = get_option('eia_instagram_access_token');
        if (!empty($instagram_token)) {
            $results['instagram'] = array('success' => true, 'message' => __('Access token configured', 'environmental-integration-apis'));
        } else {
            $results['instagram'] = array('success' => false, 'message' => __('Access token not configured', 'environmental-integration-apis'));
        }

        $overall_success = $results['facebook']['success'] || $results['twitter']['success'] || $results['instagram']['success'];
        
        return array(
            'success' => $overall_success,
            'message' => __('Social media configuration checked', 'environmental-integration-apis'),
            'details' => $results
        );
    }
}
