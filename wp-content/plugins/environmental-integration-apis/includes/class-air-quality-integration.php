<?php
/**
 * Air Quality Integration Class
 *
 * Handles integration with air quality APIs (IQAir, OpenWeatherMap Air Pollution, etc.)
 * for environmental monitoring and air quality data display.
 */

if (!defined('ABSPATH')) {
    exit;
}

class EIA_Air_Quality_Integration {
    
    private static $instance = null;
    private $api_keys = array();
    private $cache_duration = 1800; // 30 minutes
    private $default_provider = 'iqair';
    
    // Air Quality Index categories
    private $aqi_categories = array(
        1 => array('level' => 'Good', 'color' => '#00e400', 'description' => 'Air quality is considered satisfactory'),
        2 => array('level' => 'Fair', 'color' => '#ffff00', 'description' => 'Air quality is acceptable for most'),
        3 => array('level' => 'Moderate', 'color' => '#ff7e00', 'description' => 'Sensitive individuals may experience minor issues'),
        4 => array('level' => 'Poor', 'color' => '#ff0000', 'description' => 'Everyone may begin to experience health effects'),
        5 => array('level' => 'Very Poor', 'color' => '#8f3f97', 'description' => 'Health warnings of emergency conditions')
    );
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init();
    }
    
    private function init() {
        // Load API keys from options
        $this->api_keys = get_option('eia_air_quality_api_keys', array());
        
        // Register hooks
        add_action('init', array($this, 'register_shortcodes'));
        add_action('wp_ajax_eia_get_air_quality', array($this, 'ajax_get_air_quality'));
        add_action('wp_ajax_nopriv_eia_get_air_quality', array($this, 'ajax_get_air_quality'));
        add_action('wp_ajax_eia_air_quality_forecast', array($this, 'ajax_get_air_quality_forecast'));
        add_action('wp_ajax_nopriv_eia_air_quality_forecast', array($this, 'ajax_get_air_quality_forecast'));
        
        // Schedule air quality alerts check
        add_action('eia_check_air_quality_alerts', array($this, 'check_air_quality_alerts'));
        if (!wp_next_scheduled('eia_check_air_quality_alerts')) {
            wp_schedule_event(time(), 'hourly', 'eia_check_air_quality_alerts');
        }
    }
    
    /**
     * Register air quality shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('env_air_quality', array($this, 'air_quality_shortcode'));
        add_shortcode('env_air_quality_widget', array($this, 'air_quality_widget_shortcode'));
        add_shortcode('env_air_quality_map', array($this, 'air_quality_map_shortcode'));
        add_shortcode('env_air_quality_alerts', array($this, 'air_quality_alerts_shortcode'));
    }
    
    /**
     * Get current air quality data
     */
    public function get_current_air_quality($location, $provider = null) {
        if (!$provider) {
            $provider = $this->default_provider;
        }
        
        // Check cache first
        $cache_key = 'air_quality_current_' . md5($location . $provider);
        $cached_data = $this->get_cached_data($cache_key);
        
        if ($cached_data) {
            return $cached_data;
        }
        
        // Log API request
        $request_id = $this->log_api_request('air_quality', $provider, 'current', array(
            'location' => $location,
            'provider' => $provider
        ));
        
        $air_quality_data = null;
        $start_time = microtime(true);
        
        try {
            switch ($provider) {
                case 'iqair':
                    $air_quality_data = $this->get_iqair_current($location);
                    break;
                case 'openweathermap':
                    $air_quality_data = $this->get_openweathermap_air_pollution($location);
                    break;
                case 'airnow':
                    $air_quality_data = $this->get_airnow_current($location);
                    break;
                default:
                    throw new Exception('Unsupported air quality provider: ' . $provider);
            }
            
            // Cache the result
            if ($air_quality_data) {
                $this->cache_data($cache_key, $air_quality_data, $this->cache_duration);
            }
            
            // Log successful response
            $this->log_api_response($request_id, 200, $air_quality_data, microtime(true) - $start_time);
            
            return $air_quality_data;
            
        } catch (Exception $e) {
            // Log error
            $this->log_api_response($request_id, 0, array('error' => $e->getMessage()), microtime(true) - $start_time);
            error_log('Air Quality API Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get air quality forecast
     */
    public function get_air_quality_forecast($location, $provider = null) {
        if (!$provider) {
            $provider = $this->default_provider;
        }
        
        // Check cache first
        $cache_key = 'air_quality_forecast_' . md5($location . $provider);
        $cached_data = $this->get_cached_data($cache_key);
        
        if ($cached_data) {
            return $cached_data;
        }
        
        // Log API request
        $request_id = $this->log_api_request('air_quality', $provider, 'forecast', array(
            'location' => $location,
            'provider' => $provider
        ));
        
        $forecast_data = null;
        $start_time = microtime(true);
        
        try {
            switch ($provider) {
                case 'iqair':
                    $forecast_data = $this->get_iqair_forecast($location);
                    break;
                case 'openweathermap':
                    $forecast_data = $this->get_openweathermap_air_pollution_forecast($location);
                    break;
                default:
                    throw new Exception('Air quality forecast not supported for provider: ' . $provider);
            }
            
            // Cache the result
            if ($forecast_data) {
                $this->cache_data($cache_key, $forecast_data, $this->cache_duration);
            }
            
            // Log successful response
            $this->log_api_response($request_id, 200, $forecast_data, microtime(true) - $start_time);
            
            return $forecast_data;
            
        } catch (Exception $e) {
            // Log error
            $this->log_api_response($request_id, 0, array('error' => $e->getMessage()), microtime(true) - $start_time);
            error_log('Air Quality Forecast API Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * IQAir current air quality
     */
    private function get_iqair_current($location) {
        if (empty($this->api_keys['iqair'])) {
            throw new Exception('IQAir API key not configured');
        }
        
        // Determine if location is coordinates or city name
        if (preg_match('/^-?\d+\.?\d*,-?\d+\.?\d*$/', $location)) {
            list($lat, $lon) = explode(',', $location);
            $url = "http://api.airvisual.com/v2/nearest_city?lat={$lat}&lon={$lon}&key={$this->api_keys['iqair']}";
        } else {
            // Parse city, state, country format
            $parts = array_map('trim', explode(',', $location));
            if (count($parts) >= 3) {
                $city = urlencode($parts[0]);
                $state = urlencode($parts[1]);
                $country = urlencode($parts[2]);
                $url = "http://api.airvisual.com/v2/city?city={$city}&state={$state}&country={$country}&key={$this->api_keys['iqair']}";
            } else {
                throw new Exception('Invalid location format for IQAir. Use "City, State, Country" or "lat,lon"');
            }
        }
        
        $response = wp_remote_get($url, array('timeout' => 15));
        
        if (is_wp_error($response)) {
            throw new Exception('HTTP request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || $data['status'] !== 'success') {
            throw new Exception('API returned error: ' . ($data['data']['message'] ?? 'Unknown error'));
        }
        
        return $this->normalize_air_quality_data($data['data'], 'iqair');
    }
    
    /**
     * OpenWeatherMap air pollution
     */
    private function get_openweathermap_air_pollution($location) {
        if (empty($this->api_keys['openweathermap'])) {
            throw new Exception('OpenWeatherMap API key not configured');
        }
        
        // Convert location to coordinates if needed
        if (!preg_match('/^-?\d+\.?\d*,-?\d+\.?\d*$/', $location)) {
            // Use geocoding to get coordinates
            $geocode_url = "http://api.openweathermap.org/geo/1.0/direct?q=" . urlencode($location) . "&limit=1&appid={$this->api_keys['openweathermap']}";
            $geocode_response = wp_remote_get($geocode_url, array('timeout' => 15));
            
            if (is_wp_error($geocode_response)) {
                throw new Exception('Geocoding failed: ' . $geocode_response->get_error_message());
            }
            
            $geocode_data = json_decode(wp_remote_retrieve_body($geocode_response), true);
            if (empty($geocode_data)) {
                throw new Exception('Location not found');
            }
            
            $lat = $geocode_data[0]['lat'];
            $lon = $geocode_data[0]['lon'];
        } else {
            list($lat, $lon) = explode(',', $location);
        }
        
        $url = "http://api.openweathermap.org/data/2.5/air_pollution?lat={$lat}&lon={$lon}&appid={$this->api_keys['openweathermap']}";
        
        $response = wp_remote_get($url, array('timeout' => 15));
        
        if (is_wp_error($response)) {
            throw new Exception('HTTP request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['list'])) {
            throw new Exception('API returned invalid data');
        }
        
        return $this->normalize_air_quality_data($data, 'openweathermap');
    }
    
    /**
     * Normalize air quality data from different providers
     */
    private function normalize_air_quality_data($data, $provider) {
        switch ($provider) {
            case 'iqair':
                $current = $data['current'];
                return array(
                    'location' => $data['city'] . ', ' . $data['state'] . ', ' . $data['country'],
                    'coordinates' => array(
                        'lat' => $data['location']['coordinates'][1],
                        'lon' => $data['location']['coordinates'][0]
                    ),
                    'aqi' => array(
                        'value' => $current['pollution']['aqius'],
                        'scale' => 'US EPA',
                        'category' => $this->get_aqi_category($current['pollution']['aqius'], 'us_epa')
                    ),
                    'pollutants' => array(
                        'pm2_5' => $current['pollution']['pm2_5'] ?? null,
                        'pm10' => $current['pollution']['pm10'] ?? null,
                        'o3' => $current['pollution']['o3'] ?? null,
                        'no2' => $current['pollution']['no2'] ?? null,
                        'so2' => $current['pollution']['so2'] ?? null,
                        'co' => $current['pollution']['co'] ?? null
                    ),
                    'weather' => array(
                        'temperature' => $current['weather']['tp'],
                        'humidity' => $current['weather']['hu'],
                        'pressure' => $current['weather']['pr'],
                        'wind_speed' => $current['weather']['ws']
                    ),
                    'timestamp' => strtotime($current['pollution']['ts']),
                    'provider' => 'iqair'
                );
                
            case 'openweathermap':
                $current = $data['list'][0];
                $aqi_value = $current['main']['aqi'];
                
                return array(
                    'location' => $data['coord']['lat'] . ', ' . $data['coord']['lon'],
                    'coordinates' => array(
                        'lat' => $data['coord']['lat'],
                        'lon' => $data['coord']['lon']
                    ),
                    'aqi' => array(
                        'value' => $aqi_value,
                        'scale' => 'OpenWeatherMap',
                        'category' => $this->get_aqi_category($aqi_value, 'openweathermap')
                    ),
                    'pollutants' => array(
                        'pm2_5' => $current['components']['pm2_5'] ?? null,
                        'pm10' => $current['components']['pm10'] ?? null,
                        'o3' => $current['components']['o3'] ?? null,
                        'no2' => $current['components']['no2'] ?? null,
                        'so2' => $current['components']['so2'] ?? null,
                        'co' => $current['components']['co'] ?? null,
                        'no' => $current['components']['no'] ?? null,
                        'nh3' => $current['components']['nh3'] ?? null
                    ),
                    'timestamp' => $current['dt'],
                    'provider' => 'openweathermap'
                );
                
            default:
                return $data;
        }
    }
    
    /**
     * Get AQI category based on value and scale
     */
    private function get_aqi_category($aqi_value, $scale) {
        switch ($scale) {
            case 'us_epa':
                if ($aqi_value <= 50) return $this->aqi_categories[1];
                if ($aqi_value <= 100) return $this->aqi_categories[2];
                if ($aqi_value <= 150) return $this->aqi_categories[3];
                if ($aqi_value <= 200) return $this->aqi_categories[4];
                return $this->aqi_categories[5];
                
            case 'openweathermap':
                // OpenWeatherMap uses scale 1-5
                return $this->aqi_categories[$aqi_value] ?? $this->aqi_categories[5];
                
            default:
                return $this->aqi_categories[1];
        }
    }
    
    /**
     * Air quality shortcode
     */
    public function air_quality_shortcode($atts) {
        $atts = shortcode_atts(array(
            'location' => 'Hanoi,Hanoi,Vietnam',
            'provider' => $this->default_provider,
            'show_pollutants' => 'true',
            'show_weather' => 'false',
            'template' => 'default'
        ), $atts);
        
        $air_quality = $this->get_current_air_quality($atts['location'], $atts['provider']);
        
        if (!$air_quality) {
            return '<div class="eia-air-quality-error">Unable to load air quality data</div>';
        }
        
        return $this->render_air_quality_widget($air_quality, $atts);
    }
    
    /**
     * Air quality widget shortcode
     */
    public function air_quality_widget_shortcode($atts) {
        $atts = shortcode_atts(array(
            'location' => 'Hanoi,Hanoi,Vietnam',
            'provider' => $this->default_provider,
            'style' => 'compact'
        ), $atts);
        
        $air_quality = $this->get_current_air_quality($atts['location'], $atts['provider']);
        
        if (!$air_quality) {
            return '<div class="eia-air-quality-error">Unable to load air quality data</div>';
        }
        
        return $this->render_air_quality_compact($air_quality, $atts);
    }
    
    /**
     * Render air quality widget
     */
    private function render_air_quality_widget($air_quality, $atts) {
        $aqi = $air_quality['aqi'];
        $category = $aqi['category'];
        
        ob_start();
        ?>
        <div class="eia-air-quality-widget" data-location="<?php echo esc_attr($air_quality['location']); ?>">
            <div class="air-quality-header">
                <h3 class="air-quality-location"><?php echo esc_html($air_quality['location']); ?></h3>
                <div class="air-quality-time"><?php echo date('M j, Y g:i A', $air_quality['timestamp']); ?></div>
            </div>
            
            <div class="air-quality-main">
                <div class="aqi-display" style="background-color: <?php echo esc_attr($category['color']); ?>">
                    <div class="aqi-value"><?php echo $aqi['value']; ?></div>
                    <div class="aqi-scale"><?php echo esc_html($aqi['scale']); ?></div>
                </div>
                
                <div class="aqi-info">
                    <div class="aqi-category" style="color: <?php echo esc_attr($category['color']); ?>">
                        <?php echo esc_html($category['level']); ?>
                    </div>
                    <div class="aqi-description">
                        <?php echo esc_html($category['description']); ?>
                    </div>
                </div>
            </div>
            
            <?php if ($atts['show_pollutants'] === 'true' && !empty($air_quality['pollutants'])): ?>
            <div class="pollutants-grid">
                <h4>Pollutant Levels (μg/m³)</h4>
                <div class="pollutants-list">
                    <?php foreach ($air_quality['pollutants'] as $pollutant => $value): ?>
                        <?php if ($value !== null): ?>
                        <div class="pollutant-item">
                            <span class="pollutant-name"><?php echo strtoupper(str_replace('_', '.', $pollutant)); ?>:</span>
                            <span class="pollutant-value"><?php echo number_format($value, 1); ?></span>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($atts['show_weather'] === 'true' && !empty($air_quality['weather'])): ?>
            <div class="weather-info">
                <h4>Weather Conditions</h4>
                <div class="weather-details">
                    <?php if (isset($air_quality['weather']['temperature'])): ?>
                    <div class="weather-detail">
                        <span class="detail-label">Temperature:</span>
                        <span class="detail-value"><?php echo $air_quality['weather']['temperature']; ?>°C</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($air_quality['weather']['humidity'])): ?>
                    <div class="weather-detail">
                        <span class="detail-label">Humidity:</span>
                        <span class="detail-value"><?php echo $air_quality['weather']['humidity']; ?>%</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($air_quality['weather']['pressure'])): ?>
                    <div class="weather-detail">
                        <span class="detail-label">Pressure:</span>
                        <span class="detail-value"><?php echo $air_quality['weather']['pressure']; ?> hPa</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render compact air quality widget
     */
    private function render_air_quality_compact($air_quality, $atts) {
        $aqi = $air_quality['aqi'];
        $category = $aqi['category'];
        
        ob_start();
        ?>
        <div class="eia-air-quality-compact" data-location="<?php echo esc_attr($air_quality['location']); ?>">
            <div class="aqi-indicator" style="background-color: <?php echo esc_attr($category['color']); ?>">
                <div class="aqi-value"><?php echo $aqi['value']; ?></div>
            </div>
            <div class="aqi-info">
                <div class="aqi-category"><?php echo esc_html($category['level']); ?></div>
                <div class="aqi-location"><?php echo esc_html($air_quality['location']); ?></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX handler for air quality requests
     */
    public function ajax_get_air_quality() {
        check_ajax_referer('eia_nonce', 'nonce');
        
        $location = sanitize_text_field($_POST['location'] ?? '');
        $provider = sanitize_text_field($_POST['provider'] ?? $this->default_provider);
        
        if (empty($location)) {
            wp_send_json_error('Location is required');
        }
        
        $air_quality = $this->get_current_air_quality($location, $provider);
        
        if ($air_quality) {
            wp_send_json_success($air_quality);
        } else {
            wp_send_json_error('Unable to fetch air quality data');
        }
    }
    
    /**
     * AJAX handler for air quality forecast requests
     */
    public function ajax_get_air_quality_forecast() {
        check_ajax_referer('eia_nonce', 'nonce');
        
        $location = sanitize_text_field($_POST['location'] ?? '');
        $provider = sanitize_text_field($_POST['provider'] ?? $this->default_provider);
        
        if (empty($location)) {
            wp_send_json_error('Location is required');
        }
        
        $forecast = $this->get_air_quality_forecast($location, $provider);
        
        if ($forecast) {
            wp_send_json_success($forecast);
        } else {
            wp_send_json_error('Unable to fetch air quality forecast data');
        }
    }
    
    /**
     * Check for air quality alerts
     */
    public function check_air_quality_alerts() {
        $alert_locations = get_option('eia_air_quality_alert_locations', array());
        
        foreach ($alert_locations as $location_data) {
            $air_quality = $this->get_current_air_quality($location_data['location']);
            
            if ($air_quality) {
                $this->process_air_quality_alerts($air_quality, $location_data);
            }
        }
    }
    
    /**
     * Process air quality alerts
     */
    private function process_air_quality_alerts($air_quality, $location_data) {
        $alerts = array();
        
        // AQI threshold alerts
        if (isset($location_data['aqi_threshold']) && $air_quality['aqi']['value'] > $location_data['aqi_threshold']) {
            $alerts[] = array(
                'type' => 'aqi_high',
                'message' => "AQI level ({$air_quality['aqi']['value']}) exceeded threshold ({$location_data['aqi_threshold']}) in {$air_quality['location']}"
            );
        }
        
        // Pollutant-specific alerts
        $pollutant_thresholds = $location_data['pollutant_thresholds'] ?? array();
        foreach ($pollutant_thresholds as $pollutant => $threshold) {
            if (isset($air_quality['pollutants'][$pollutant]) && 
                $air_quality['pollutants'][$pollutant] > $threshold) {
                
                $alerts[] = array(
                    'type' => 'pollutant_high',
                    'message' => strtoupper(str_replace('_', '.', $pollutant)) . " level ({$air_quality['pollutants'][$pollutant]} μg/m³) exceeded threshold ({$threshold} μg/m³) in {$air_quality['location']}"
                );
            }
        }
        
        // Health category alerts
        $unhealthy_categories = array('Poor', 'Very Poor');
        if (in_array($air_quality['aqi']['category']['level'], $unhealthy_categories)) {
            $alerts[] = array(
                'type' => 'health_warning',
                'message' => "Air quality is {$air_quality['aqi']['category']['level']} in {$air_quality['location']}. {$air_quality['aqi']['category']['description']}"
            );
        }
        
        // Send alerts if any
        if (!empty($alerts)) {
            $this->send_air_quality_alerts($alerts, $location_data);
        }
    }
    
    /**
     * Send air quality alerts
     */
    private function send_air_quality_alerts($alerts, $location_data) {
        // Send email alerts
        if (!empty($location_data['email'])) {
            $subject = 'Air Quality Alert - ' . $location_data['location'];
            $message = "Air quality alerts for {$location_data['location']}:\n\n";
            
            foreach ($alerts as $alert) {
                $message .= "• " . $alert['message'] . "\n";
            }
            
            wp_mail($location_data['email'], $subject, $message);
        }
        
        // Trigger webhook if configured
        if (!empty($location_data['webhook_url'])) {
            $webhook_data = array(
                'location' => $location_data['location'],
                'alerts' => $alerts,
                'timestamp' => current_time('mysql')
            );
            
            wp_remote_post($location_data['webhook_url'], array(
                'body' => json_encode($webhook_data),
                'headers' => array('Content-Type' => 'application/json')
            ));
        }
    }
    
    /**
     * Helper methods for caching and logging
     */
    private function get_cached_data($key) {
        global $wpdb;
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT data, expires_at FROM {$wpdb->prefix}eia_api_cache 
             WHERE cache_key = %s AND expires_at > %s",
            $key, current_time('mysql')
        ));
        
        return $result ? json_decode($result->data, true) : null;
    }
    
    private function cache_data($key, $data, $duration) {
        global $wpdb;
        
        $expires_at = date('Y-m-d H:i:s', time() + $duration);
        
        $wpdb->replace(
            $wpdb->prefix . 'eia_api_cache',
            array(
                'cache_key' => $key,
                'data' => json_encode($data),
                'expires_at' => $expires_at,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s')
        );
    }
    
    private function log_api_request($service, $provider, $endpoint, $params) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'eia_api_logs',
            array(
                'service' => $service,
                'provider' => $provider,
                'endpoint' => $endpoint,
                'request_data' => json_encode($params),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
        
        return $wpdb->insert_id;
    }
    
    private function log_api_response($request_id, $status_code, $response_data, $response_time) {
        global $wpdb;
        
        $wpdb->update(
            $wpdb->prefix . 'eia_api_logs',
            array(
                'status_code' => $status_code,
                'response_data' => json_encode($response_data),
                'response_time' => $response_time,
                'completed_at' => current_time('mysql')
            ),
            array('id' => $request_id),
            array('%d', '%s', '%f', '%s'),
            array('%d')
        );
    }
}

// Initialize the class
EIA_Air_Quality_Integration::get_instance();
