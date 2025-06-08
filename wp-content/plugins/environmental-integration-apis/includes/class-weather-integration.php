<?php
/**
 * Weather Integration Class
 *
 * Handles integration with weather APIs (OpenWeatherMap, AccuWeather, etc.)
 * for environmental monitoring and weather data display.
 */

if (!defined('ABSPATH')) {
    exit;
}

class EIA_Weather_Integration {
    
    private static $instance = null;
    private $api_keys = array();
    private $cache_duration = 3600; // 1 hour
    private $default_provider = 'openweathermap';
    
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
        $this->api_keys = get_option('eia_weather_api_keys', array());
        
        // Register hooks
        add_action('init', array($this, 'register_shortcodes'));
        add_action('wp_ajax_eia_get_weather', array($this, 'ajax_get_weather'));
        add_action('wp_ajax_nopriv_eia_get_weather', array($this, 'ajax_get_weather'));
        add_action('wp_ajax_eia_weather_forecast', array($this, 'ajax_get_forecast'));
        add_action('wp_ajax_nopriv_eia_weather_forecast', array($this, 'ajax_get_forecast'));
        
        // Schedule weather alerts check
        add_action('eia_check_weather_alerts', array($this, 'check_weather_alerts'));
        if (!wp_next_scheduled('eia_check_weather_alerts')) {
            wp_schedule_event(time(), 'hourly', 'eia_check_weather_alerts');
        }
    }
    
    /**
     * Register weather shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('env_weather', array($this, 'weather_shortcode'));
        add_shortcode('env_weather_widget', array($this, 'weather_widget_shortcode'));
        add_shortcode('env_weather_forecast', array($this, 'forecast_shortcode'));
        add_shortcode('env_weather_alerts', array($this, 'alerts_shortcode'));
    }
    
    /**
     * Get current weather data
     */
    public function get_current_weather($location, $provider = null, $units = 'metric') {
        global $wpdb;
        
        if (!$provider) {
            $provider = $this->default_provider;
        }
        
        // Check cache first
        $cache_key = 'weather_current_' . md5($location . $provider . $units);
        $cached_data = $this->get_cached_data($cache_key);
        
        if ($cached_data) {
            return $cached_data;
        }
        
        // Log API request
        $request_id = $this->log_api_request('weather', $provider, 'current_weather', array(
            'location' => $location,
            'provider' => $provider,
            'units' => $units
        ));
        
        $weather_data = null;
        $start_time = microtime(true);
        
        try {
            switch ($provider) {
                case 'openweathermap':
                    $weather_data = $this->get_openweathermap_current($location, $units);
                    break;
                case 'accuweather':
                    $weather_data = $this->get_accuweather_current($location, $units);
                    break;
                case 'weatherapi':
                    $weather_data = $this->get_weatherapi_current($location, $units);
                    break;
                default:
                    throw new Exception('Unsupported weather provider: ' . $provider);
            }
            
            // Cache the result
            if ($weather_data) {
                $this->cache_data($cache_key, $weather_data, $this->cache_duration);
            }
            
            // Log successful response
            $this->log_api_response($request_id, 200, $weather_data, microtime(true) - $start_time);
            
            return $weather_data;
            
        } catch (Exception $e) {
            // Log error
            $this->log_api_response($request_id, 0, array('error' => $e->getMessage()), microtime(true) - $start_time);
            error_log('Weather API Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get weather forecast
     */
    public function get_weather_forecast($location, $days = 5, $provider = null, $units = 'metric') {
        if (!$provider) {
            $provider = $this->default_provider;
        }
        
        // Check cache first
        $cache_key = 'weather_forecast_' . md5($location . $provider . $days . $units);
        $cached_data = $this->get_cached_data($cache_key);
        
        if ($cached_data) {
            return $cached_data;
        }
        
        // Log API request
        $request_id = $this->log_api_request('weather', $provider, 'forecast', array(
            'location' => $location,
            'days' => $days,
            'provider' => $provider,
            'units' => $units
        ));
        
        $forecast_data = null;
        $start_time = microtime(true);
        
        try {
            switch ($provider) {
                case 'openweathermap':
                    $forecast_data = $this->get_openweathermap_forecast($location, $days, $units);
                    break;
                case 'accuweather':
                    $forecast_data = $this->get_accuweather_forecast($location, $days, $units);
                    break;
                case 'weatherapi':
                    $forecast_data = $this->get_weatherapi_forecast($location, $days, $units);
                    break;
                default:
                    throw new Exception('Unsupported weather provider: ' . $provider);
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
            error_log('Weather Forecast API Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * OpenWeatherMap current weather
     */
    private function get_openweathermap_current($location, $units) {
        if (empty($this->api_keys['openweathermap'])) {
            throw new Exception('OpenWeatherMap API key not configured');
        }
        
        // Determine if location is coordinates or city name
        if (preg_match('/^-?\d+\.?\d*,-?\d+\.?\d*$/', $location)) {
            list($lat, $lon) = explode(',', $location);
            $url = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&appid={$this->api_keys['openweathermap']}&units={$units}";
        } else {
            $url = "https://api.openweathermap.org/data/2.5/weather?q=" . urlencode($location) . "&appid={$this->api_keys['openweathermap']}&units={$units}";
        }
        
        $response = wp_remote_get($url, array('timeout' => 15));
        
        if (is_wp_error($response)) {
            throw new Exception('HTTP request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || isset($data['cod']) && $data['cod'] != 200) {
            throw new Exception('API returned error: ' . ($data['message'] ?? 'Unknown error'));
        }
        
        return $this->normalize_weather_data($data, 'openweathermap');
    }
    
    /**
     * OpenWeatherMap forecast
     */
    private function get_openweathermap_forecast($location, $days, $units) {
        if (empty($this->api_keys['openweathermap'])) {
            throw new Exception('OpenWeatherMap API key not configured');
        }
        
        // Determine if location is coordinates or city name
        if (preg_match('/^-?\d+\.?\d*,-?\d+\.?\d*$/', $location)) {
            list($lat, $lon) = explode(',', $location);
            $url = "https://api.openweathermap.org/data/2.5/forecast?lat={$lat}&lon={$lon}&appid={$this->api_keys['openweathermap']}&units={$units}";
        } else {
            $url = "https://api.openweathermap.org/data/2.5/forecast?q=" . urlencode($location) . "&appid={$this->api_keys['openweathermap']}&units={$units}";
        }
        
        $response = wp_remote_get($url, array('timeout' => 15));
        
        if (is_wp_error($response)) {
            throw new Exception('HTTP request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || isset($data['cod']) && $data['cod'] != '200') {
            throw new Exception('API returned error: ' . ($data['message'] ?? 'Unknown error'));
        }
        
        return $this->normalize_forecast_data($data, 'openweathermap', $days);
    }
    
    /**
     * Normalize weather data from different providers
     */
    private function normalize_weather_data($data, $provider) {
        switch ($provider) {
            case 'openweathermap':
                return array(
                    'location' => $data['name'] . ', ' . $data['sys']['country'],
                    'coordinates' => array(
                        'lat' => $data['coord']['lat'],
                        'lon' => $data['coord']['lon']
                    ),
                    'temperature' => round($data['main']['temp']),
                    'feels_like' => round($data['main']['feels_like']),
                    'humidity' => $data['main']['humidity'],
                    'pressure' => $data['main']['pressure'],
                    'wind_speed' => $data['wind']['speed'] ?? 0,
                    'wind_direction' => $data['wind']['deg'] ?? 0,
                    'visibility' => ($data['visibility'] ?? 0) / 1000, // Convert to km
                    'condition' => $data['weather'][0]['main'],
                    'description' => ucfirst($data['weather'][0]['description']),
                    'icon' => $data['weather'][0]['icon'],
                    'uv_index' => null, // Not available in current weather
                    'timestamp' => $data['dt'],
                    'sunrise' => $data['sys']['sunrise'],
                    'sunset' => $data['sys']['sunset'],
                    'provider' => 'openweathermap'
                );
            default:
                return $data;
        }
    }
    
    /**
     * Normalize forecast data from different providers
     */
    private function normalize_forecast_data($data, $provider, $days) {
        switch ($provider) {
            case 'openweathermap':
                $forecast = array();
                $daily_data = array();
                
                // Group 3-hourly data by day
                foreach ($data['list'] as $item) {
                    $date = date('Y-m-d', $item['dt']);
                    if (!isset($daily_data[$date])) {
                        $daily_data[$date] = array();
                    }
                    $daily_data[$date][] = $item;
                }
                
                // Convert to daily forecast
                $count = 0;
                foreach ($daily_data as $date => $day_items) {
                    if ($count >= $days) break;
                    
                    $temps = array_column($day_items, 'main');
                    $min_temp = min(array_column($temps, 'temp_min'));
                    $max_temp = max(array_column($temps, 'temp_max'));
                    
                    // Use midday item for main conditions
                    $main_item = $day_items[count($day_items) > 4 ? 4 : 0];
                    
                    $forecast[] = array(
                        'date' => $date,
                        'timestamp' => strtotime($date),
                        'temp_min' => round($min_temp),
                        'temp_max' => round($max_temp),
                        'condition' => $main_item['weather'][0]['main'],
                        'description' => ucfirst($main_item['weather'][0]['description']),
                        'icon' => $main_item['weather'][0]['icon'],
                        'humidity' => $main_item['main']['humidity'],
                        'wind_speed' => $main_item['wind']['speed'] ?? 0,
                        'precipitation' => $main_item['rain']['3h'] ?? $main_item['snow']['3h'] ?? 0
                    );
                    
                    $count++;
                }
                
                return array(
                    'location' => $data['city']['name'] . ', ' . $data['city']['country'],
                    'coordinates' => array(
                        'lat' => $data['city']['coord']['lat'],
                        'lon' => $data['city']['coord']['lon']
                    ),
                    'forecast' => $forecast,
                    'provider' => 'openweathermap'
                );
                
            default:
                return $data;
        }
    }
    
    /**
     * Weather shortcode
     */
    public function weather_shortcode($atts) {
        $atts = shortcode_atts(array(
            'location' => 'Hanoi,VN',
            'provider' => $this->default_provider,
            'units' => 'metric',
            'show_details' => 'true',
            'template' => 'default'
        ), $atts);
        
        $weather = $this->get_current_weather($atts['location'], $atts['provider'], $atts['units']);
        
        if (!$weather) {
            return '<div class="eia-weather-error">Unable to load weather data</div>';
        }
        
        return $this->render_weather_widget($weather, $atts);
    }
    
    /**
     * Weather widget shortcode
     */
    public function weather_widget_shortcode($atts) {
        $atts = shortcode_atts(array(
            'location' => 'Hanoi,VN',
            'provider' => $this->default_provider,
            'units' => 'metric',
            'style' => 'compact'
        ), $atts);
        
        $weather = $this->get_current_weather($atts['location'], $atts['provider'], $atts['units']);
        
        if (!$weather) {
            return '<div class="eia-weather-error">Unable to load weather data</div>';
        }
        
        return $this->render_weather_compact($weather, $atts);
    }
    
    /**
     * Forecast shortcode
     */
    public function forecast_shortcode($atts) {
        $atts = shortcode_atts(array(
            'location' => 'Hanoi,VN',
            'days' => '5',
            'provider' => $this->default_provider,
            'units' => 'metric',
            'layout' => 'horizontal'
        ), $atts);
        
        $forecast = $this->get_weather_forecast($atts['location'], intval($atts['days']), $atts['provider'], $atts['units']);
        
        if (!$forecast) {
            return '<div class="eia-weather-error">Unable to load forecast data</div>';
        }
        
        return $this->render_forecast_widget($forecast, $atts);
    }
    
    /**
     * Render weather widget
     */
    private function render_weather_widget($weather, $atts) {
        $unit_symbol = $atts['units'] === 'imperial' ? '°F' : '°C';
        $speed_unit = $atts['units'] === 'imperial' ? 'mph' : 'm/s';
        
        ob_start();
        ?>
        <div class="eia-weather-widget" data-location="<?php echo esc_attr($weather['location']); ?>">
            <div class="weather-header">
                <h3 class="weather-location"><?php echo esc_html($weather['location']); ?></h3>
                <div class="weather-time"><?php echo date('M j, Y g:i A', $weather['timestamp']); ?></div>
            </div>
            
            <div class="weather-current">
                <div class="weather-main">
                    <div class="weather-icon">
                        <img src="https://openweathermap.org/img/wn/<?php echo esc_attr($weather['icon']); ?>@2x.png" 
                             alt="<?php echo esc_attr($weather['description']); ?>">
                    </div>
                    <div class="weather-temp">
                        <span class="temp-value"><?php echo $weather['temperature']; ?></span>
                        <span class="temp-unit"><?php echo $unit_symbol; ?></span>
                    </div>
                </div>
                
                <div class="weather-info">
                    <div class="weather-condition"><?php echo esc_html($weather['description']); ?></div>
                    <div class="weather-feels-like">
                        Feels like <?php echo $weather['feels_like'] . $unit_symbol; ?>
                    </div>
                </div>
            </div>
            
            <?php if ($atts['show_details'] === 'true'): ?>
            <div class="weather-details">
                <div class="weather-detail">
                    <span class="detail-label">Humidity:</span>
                    <span class="detail-value"><?php echo $weather['humidity']; ?>%</span>
                </div>
                <div class="weather-detail">
                    <span class="detail-label">Wind:</span>
                    <span class="detail-value"><?php echo $weather['wind_speed'] . ' ' . $speed_unit; ?></span>
                </div>
                <div class="weather-detail">
                    <span class="detail-label">Pressure:</span>
                    <span class="detail-value"><?php echo $weather['pressure']; ?> hPa</span>
                </div>
                <div class="weather-detail">
                    <span class="detail-label">Visibility:</span>
                    <span class="detail-value"><?php echo $weather['visibility']; ?> km</span>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render compact weather widget
     */
    private function render_weather_compact($weather, $atts) {
        $unit_symbol = $atts['units'] === 'imperial' ? '°F' : '°C';
        
        ob_start();
        ?>
        <div class="eia-weather-compact" data-location="<?php echo esc_attr($weather['location']); ?>">
            <div class="weather-icon">
                <img src="https://openweathermap.org/img/wn/<?php echo esc_attr($weather['icon']); ?>.png" 
                     alt="<?php echo esc_attr($weather['description']); ?>">
            </div>
            <div class="weather-temp"><?php echo $weather['temperature'] . $unit_symbol; ?></div>
            <div class="weather-location"><?php echo esc_html($weather['location']); ?></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render forecast widget
     */
    private function render_forecast_widget($forecast, $atts) {
        $unit_symbol = $atts['units'] === 'imperial' ? '°F' : '°C';
        
        ob_start();
        ?>
        <div class="eia-weather-forecast <?php echo esc_attr($atts['layout']); ?>" data-location="<?php echo esc_attr($forecast['location']); ?>">
            <div class="forecast-header">
                <h3><?php echo esc_html($forecast['location']); ?> - <?php echo count($forecast['forecast']); ?> Day Forecast</h3>
            </div>
            
            <div class="forecast-days">
                <?php foreach ($forecast['forecast'] as $day): ?>
                <div class="forecast-day">
                    <div class="day-name"><?php echo date('D', $day['timestamp']); ?></div>
                    <div class="day-date"><?php echo date('M j', $day['timestamp']); ?></div>
                    <div class="day-icon">
                        <img src="https://openweathermap.org/img/wn/<?php echo esc_attr($day['icon']); ?>.png" 
                             alt="<?php echo esc_attr($day['description']); ?>">
                    </div>
                    <div class="day-condition"><?php echo esc_html($day['condition']); ?></div>
                    <div class="day-temps">
                        <span class="temp-high"><?php echo $day['temp_max'] . $unit_symbol; ?></span>
                        <span class="temp-low"><?php echo $day['temp_min'] . $unit_symbol; ?></span>
                    </div>
                    <div class="day-humidity"><?php echo $day['humidity']; ?>%</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX handler for weather requests
     */
    public function ajax_get_weather() {
        check_ajax_referer('eia_nonce', 'nonce');
        
        $location = sanitize_text_field($_POST['location'] ?? '');
        $provider = sanitize_text_field($_POST['provider'] ?? $this->default_provider);
        $units = sanitize_text_field($_POST['units'] ?? 'metric');
        
        if (empty($location)) {
            wp_send_json_error('Location is required');
        }
        
        $weather = $this->get_current_weather($location, $provider, $units);
        
        if ($weather) {
            wp_send_json_success($weather);
        } else {
            wp_send_json_error('Unable to fetch weather data');
        }
    }
    
    /**
     * AJAX handler for forecast requests
     */
    public function ajax_get_forecast() {
        check_ajax_referer('eia_nonce', 'nonce');
        
        $location = sanitize_text_field($_POST['location'] ?? '');
        $days = intval($_POST['days'] ?? 5);
        $provider = sanitize_text_field($_POST['provider'] ?? $this->default_provider);
        $units = sanitize_text_field($_POST['units'] ?? 'metric');
        
        if (empty($location)) {
            wp_send_json_error('Location is required');
        }
        
        $forecast = $this->get_weather_forecast($location, $days, $provider, $units);
        
        if ($forecast) {
            wp_send_json_success($forecast);
        } else {
            wp_send_json_error('Unable to fetch forecast data');
        }
    }
    
    /**
     * Check for weather alerts
     */
    public function check_weather_alerts() {
        $alert_locations = get_option('eia_weather_alert_locations', array());
        
        foreach ($alert_locations as $location_data) {
            $weather = $this->get_current_weather($location_data['location']);
            
            if ($weather) {
                $this->process_weather_alerts($weather, $location_data);
            }
        }
    }
    
    /**
     * Process weather alerts
     */
    private function process_weather_alerts($weather, $location_data) {
        $alerts = array();
        
        // Temperature alerts
        if (isset($location_data['temp_min']) && $weather['temperature'] < $location_data['temp_min']) {
            $alerts[] = array(
                'type' => 'temperature_low',
                'message' => "Temperature dropped below {$location_data['temp_min']}°C in {$weather['location']}"
            );
        }
        
        if (isset($location_data['temp_max']) && $weather['temperature'] > $location_data['temp_max']) {
            $alerts[] = array(
                'type' => 'temperature_high',
                'message' => "Temperature exceeded {$location_data['temp_max']}°C in {$weather['location']}"
            );
        }
        
        // Wind alerts
        if (isset($location_data['wind_max']) && $weather['wind_speed'] > $location_data['wind_max']) {
            $alerts[] = array(
                'type' => 'wind_high',
                'message' => "High wind speed ({$weather['wind_speed']} m/s) detected in {$weather['location']}"
            );
        }
        
        // Severe weather conditions
        $severe_conditions = array('Thunderstorm', 'Snow', 'Extreme');
        if (in_array($weather['condition'], $severe_conditions)) {
            $alerts[] = array(
                'type' => 'severe_weather',
                'message' => "Severe weather condition ({$weather['condition']}) in {$weather['location']}"
            );
        }
        
        // Send alerts if any
        if (!empty($alerts)) {
            $this->send_weather_alerts($alerts, $location_data);
        }
    }
    
    /**
     * Send weather alerts
     */
    private function send_weather_alerts($alerts, $location_data) {
        // Send email alerts
        if (!empty($location_data['email'])) {
            $subject = 'Weather Alert - ' . $location_data['location'];
            $message = "Weather alerts for {$location_data['location']}:\n\n";
            
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
EIA_Weather_Integration::get_instance();
