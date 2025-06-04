<?php
/**
 * Weather API Handler
 * 
 * Handles fetching and processing weather data from various APIs
 * 
 * @package Environmental_Data_Dashboard
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Weather_API {
    
    private static $instance = null;
    private $api_key;
    private $base_url = 'http://api.openweathermap.org/data/2.5/weather';
    private $forecast_url = 'http://api.openweathermap.org/data/2.5/forecast';
    private $table_name;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'env_weather_data';
        $this->api_key = get_option('env_dashboard_weather_api_key', '');
    }
    
    /**
     * Fetch weather data from API
     */
    public function fetch_weather_data($lat, $lon) {
        if (empty($this->api_key)) {
            return $this->get_mock_weather_data($lat, $lon);
        }
        
        $url = $this->base_url . '?lat=' . $lat . '&lon=' . $lon . '&appid=' . $this->api_key . '&units=metric';
        
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array(
                'User-Agent' => 'Environmental Dashboard Plugin v1.0'
            )
        ));
        
        if (is_wp_error($response)) {
            error_log('Weather API Error: ' . $response->get_error_message());
            return $this->get_mock_weather_data($lat, $lon);
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['main'])) {
            return $this->get_mock_weather_data($lat, $lon);
        }
        
        return $this->process_weather_api_data($data, $lat, $lon);
    }
    
    /**
     * Process API data into our format
     */
    private function process_weather_api_data($api_data, $lat, $lon) {
        $main = $api_data['main'];
        $wind = $api_data['wind'] ?? array();
        $weather = $api_data['weather'][0] ?? array();
        
        return array(
            'latitude' => $lat,
            'longitude' => $lon,
            'temperature' => $main['temp'],
            'humidity' => $main['humidity'],
            'pressure' => $main['pressure'],
            'wind_speed' => $wind['speed'] ?? 0,
            'wind_direction' => $wind['deg'] ?? 0,
            'visibility' => ($api_data['visibility'] ?? 10000) / 1000, // Convert to km
            'uv_index' => 0, // Would need separate UV API call
            'weather_condition' => ucfirst($weather['description'] ?? 'Unknown'),
            'recorded_at' => current_time('mysql')
        );
    }
    
    /**
     * Get mock weather data for testing
     */
    private function get_mock_weather_data($lat, $lon) {
        // Generate realistic mock data based on location and season
        $base_temp = 25 + rand(-5, 10); // Base temperature around 25°C
        $base_humidity = 60 + rand(-20, 30);
        
        $conditions = array(
            'Clear sky', 'Few clouds', 'Scattered clouds', 'Broken clouds',
            'Overcast', 'Light rain', 'Moderate rain', 'Partly cloudy', 'Sunny'
        );
        
        return array(
            'latitude' => $lat,
            'longitude' => $lon,
            'temperature' => $base_temp,
            'humidity' => max(30, min(95, $base_humidity)),
            'pressure' => 1013 + rand(-20, 20),
            'wind_speed' => rand(5, 25),
            'wind_direction' => rand(0, 360),
            'visibility' => rand(8, 15),
            'uv_index' => rand(3, 11),
            'weather_condition' => $conditions[array_rand($conditions)],
            'recorded_at' => current_time('mysql')
        );
    }
    
    /**
     * Fetch weather forecast data
     */
    public function fetch_forecast_data($lat, $lon, $days = 5) {
        if (empty($this->api_key)) {
            return $this->get_mock_forecast_data($lat, $lon, $days);
        }
        
        $url = $this->forecast_url . '?lat=' . $lat . '&lon=' . $lon . '&appid=' . $this->api_key . '&units=metric&cnt=' . ($days * 8);
        
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array(
                'User-Agent' => 'Environmental Dashboard Plugin v1.0'
            )
        ));
        
        if (is_wp_error($response)) {
            error_log('Weather Forecast API Error: ' . $response->get_error_message());
            return $this->get_mock_forecast_data($lat, $lon, $days);
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['list'])) {
            return $this->get_mock_forecast_data($lat, $lon, $days);
        }
        
        return $this->process_forecast_api_data($data['list']);
    }
    
    /**
     * Process forecast API data
     */
    private function process_forecast_api_data($forecast_list) {
        $processed_forecast = array();
        
        foreach ($forecast_list as $forecast) {
            $main = $forecast['main'];
            $wind = $forecast['wind'] ?? array();
            $weather = $forecast['weather'][0] ?? array();
            
            $processed_forecast[] = array(
                'datetime' => date('Y-m-d H:i:s', $forecast['dt']),
                'temperature' => $main['temp'],
                'humidity' => $main['humidity'],
                'pressure' => $main['pressure'],
                'wind_speed' => $wind['speed'] ?? 0,
                'wind_direction' => $wind['deg'] ?? 0,
                'weather_condition' => ucfirst($weather['description'] ?? 'Unknown'),
                'precipitation_probability' => ($forecast['pop'] ?? 0) * 100
            );
        }
        
        return $processed_forecast;
    }
    
    /**
     * Get mock forecast data
     */
    private function get_mock_forecast_data($lat, $lon, $days = 5) {
        $forecast = array();
        $conditions = array('Clear sky', 'Few clouds', 'Scattered clouds', 'Light rain', 'Partly cloudy');
        
        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d H:i:s', strtotime("+{$i} days"));
            $base_temp = 25 + rand(-5, 10);
            
            $forecast[] = array(
                'datetime' => $date,
                'temperature' => $base_temp,
                'humidity' => rand(50, 85),
                'pressure' => 1013 + rand(-15, 15),
                'wind_speed' => rand(5, 20),
                'wind_direction' => rand(0, 360),
                'weather_condition' => $conditions[array_rand($conditions)],
                'precipitation_probability' => rand(0, 80)
            );
        }
        
        return $forecast;
    }
    
    /**
     * Store weather data in database
     */
    public function store_weather_data($location_name, $data) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'location_name' => $location_name,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'temperature' => $data['temperature'],
                'humidity' => $data['humidity'],
                'pressure' => $data['pressure'],
                'wind_speed' => $data['wind_speed'],
                'wind_direction' => $data['wind_direction'],
                'visibility' => $data['visibility'],
                'uv_index' => $data['uv_index'],
                'weather_condition' => $data['weather_condition'],
                'recorded_at' => $data['recorded_at']
            ),
            array('%s', '%f', '%f', '%f', '%d', '%f', '%f', '%d', '%f', '%f', '%s', '%s')
        );
        
        if ($result === false) {
            error_log('Failed to store weather data: ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get latest weather data for a location
     */
    public function get_latest_data($location_name = null) {
        global $wpdb;
        
        if ($location_name) {
            $sql = $wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
                 WHERE location_name = %s 
                 ORDER BY recorded_at DESC 
                 LIMIT 1",
                $location_name
            );
        } else {
            $sql = "SELECT * FROM {$this->table_name} 
                    ORDER BY recorded_at DESC 
                    LIMIT 10";
        }
        
        if ($location_name) {
            return $wpdb->get_row($sql, ARRAY_A);
        } else {
            return $wpdb->get_results($sql, ARRAY_A);
        }
    }
    
    /**
     * Get weather data for a specific time period
     */
    public function get_historical_data($location_name, $start_date, $end_date) {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             WHERE location_name = %s 
             AND DATE(recorded_at) BETWEEN %s AND %s 
             ORDER BY recorded_at ASC",
            $location_name,
            $start_date,
            $end_date
        );
        
        return $wpdb->get_results($sql, ARRAY_A);
    }
    
    /**
     * Get weather trends
     */
    public function get_trends($location_name, $days = 7) {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "SELECT 
                DATE(recorded_at) as date,
                AVG(temperature) as avg_temperature,
                AVG(humidity) as avg_humidity,
                AVG(pressure) as avg_pressure,
                AVG(wind_speed) as avg_wind_speed,
                MAX(temperature) as max_temperature,
                MIN(temperature) as min_temperature
             FROM {$this->table_name} 
             WHERE location_name = %s 
             AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY DATE(recorded_at)
             ORDER BY date ASC",
            $location_name,
            $days
        );
        
        return $wpdb->get_results($sql, ARRAY_A);
    }
    
    /**
     * Get weather statistics
     */
    public function get_statistics($location_name = null) {
        global $wpdb;
        
        $where_clause = $location_name ? $wpdb->prepare("WHERE location_name = %s", $location_name) : '';
        
        $sql = "SELECT 
                    COUNT(*) as total_records,
                    AVG(temperature) as avg_temperature,
                    MAX(temperature) as max_temperature,
                    MIN(temperature) as min_temperature,
                    AVG(humidity) as avg_humidity,
                    AVG(pressure) as avg_pressure,
                    AVG(wind_speed) as avg_wind_speed,
                    COUNT(DISTINCT location_name) as total_locations
                FROM {$this->table_name} 
                {$where_clause}";
        
        return $wpdb->get_row($sql, ARRAY_A);
    }
    
    /**
     * Fetch and store data for all configured locations
     */
    public function fetch_and_store_data() {
        $locations = $this->get_configured_locations();
        
        foreach ($locations as $location) {
            $data = $this->fetch_weather_data($location['latitude'], $location['longitude']);
            if ($data) {
                $this->store_weather_data($location['name'], $data);
            }
            
            // Add delay to respect API rate limits
            sleep(1);
        }
    }
    
    /**
     * Get configured monitoring locations
     */
    private function get_configured_locations() {
        $default_locations = array(
            array(
                'name' => 'Ho Chi Minh City',
                'latitude' => 10.8231,
                'longitude' => 106.6297
            ),
            array(
                'name' => 'Hanoi',
                'latitude' => 21.0285,
                'longitude' => 105.8542
            ),
            array(
                'name' => 'Da Nang',
                'latitude' => 16.0544,
                'longitude' => 108.2022
            ),
            array(
                'name' => 'Can Tho',
                'latitude' => 10.0452,
                'longitude' => 105.7469
            ),
            array(
                'name' => 'Hai Phong',
                'latitude' => 20.8449,
                'longitude' => 106.6881
            ),
            array(
                'name' => 'Nha Trang',
                'latitude' => 12.2388,
                'longitude' => 109.1967
            )
        );
        
        // Allow filtering of locations
        return apply_filters('env_dashboard_weather_locations', $default_locations);
    }
    
    /**
     * Get weather alerts based on conditions
     */
    public function get_weather_alerts() {
        global $wpdb;
        
        $alerts = array();
        
        // Temperature alerts (extreme heat or cold)
        $temp_sql = "SELECT * FROM {$this->table_name} 
                     WHERE (temperature > 35 OR temperature < 5)
                     AND recorded_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                     ORDER BY recorded_at DESC";
        
        $temp_alerts = $wpdb->get_results($temp_sql, ARRAY_A);
        
        foreach ($temp_alerts as $alert) {
            $alerts[] = array(
                'type' => 'temperature',
                'location' => $alert['location_name'],
                'message' => $alert['temperature'] > 35 ? 
                    'Extreme heat warning: ' . $alert['temperature'] . '°C' :
                    'Cold weather alert: ' . $alert['temperature'] . '°C',
                'severity' => 'high',
                'recorded_at' => $alert['recorded_at']
            );
        }
        
        // Wind alerts (high wind speed)
        $wind_sql = "SELECT * FROM {$this->table_name} 
                     WHERE wind_speed > 50
                     AND recorded_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                     ORDER BY recorded_at DESC";
        
        $wind_alerts = $wpdb->get_results($wind_sql, ARRAY_A);
        
        foreach ($wind_alerts as $alert) {
            $alerts[] = array(
                'type' => 'wind',
                'location' => $alert['location_name'],
                'message' => 'High wind warning: ' . $alert['wind_speed'] . ' km/h',
                'severity' => 'medium',
                'recorded_at' => $alert['recorded_at']
            );
        }
        
        return $alerts;
    }
    
    /**
     * Get comfort index based on temperature and humidity
     */
    public function get_comfort_index($temperature, $humidity) {
        // Calculate heat index (feels like temperature)
        if ($temperature >= 26.7 && $humidity >= 40) {
            $T = $temperature;
            $R = $humidity;
            
            $heat_index = -42.379 + 2.04901523 * $T + 10.14333127 * $R 
                         - 0.22475541 * $T * $R - 6.83783e-3 * $T * $T 
                         - 5.481717e-2 * $R * $R + 1.22874e-3 * $T * $T * $R 
                         + 8.5282e-4 * $T * $R * $R - 1.99e-6 * $T * $T * $R * $R;
            
            // Convert to Celsius
            $heat_index = ($heat_index - 32) * 5/9;
        } else {
            $heat_index = $temperature;
        }
        
        // Determine comfort level
        if ($heat_index < 18) {
            return array('level' => 'cold', 'index' => $heat_index, 'description' => 'Too cold for comfort');
        } elseif ($heat_index < 24) {
            return array('level' => 'cool', 'index' => $heat_index, 'description' => 'Cool but comfortable');
        } elseif ($heat_index <= 28) {
            return array('level' => 'comfortable', 'index' => $heat_index, 'description' => 'Comfortable');
        } elseif ($heat_index <= 32) {
            return array('level' => 'warm', 'index' => $heat_index, 'description' => 'Warm but tolerable');
        } elseif ($heat_index <= 38) {
            return array('level' => 'hot', 'index' => $heat_index, 'description' => 'Hot and uncomfortable');
        } else {
            return array('level' => 'extreme', 'index' => $heat_index, 'description' => 'Dangerously hot');
        }
    }
    
    /**
     * Get UV index recommendations
     */
    public function get_uv_recommendations($uv_index) {
        if ($uv_index <= 2) {
            return array(
                'level' => 'Low',
                'color' => '#3EA72D',
                'recommendation' => 'Minimal protection required. Wear sunglasses on bright days.'
            );
        } elseif ($uv_index <= 5) {
            return array(
                'level' => 'Moderate',
                'color' => '#FFF300',
                'recommendation' => 'Take precautions. Wear sunglasses and use sunscreen.'
            );
        } elseif ($uv_index <= 7) {
            return array(
                'level' => 'High',
                'color' => '#F18B00',
                'recommendation' => 'Protection required. Seek shade during midday hours.'
            );
        } elseif ($uv_index <= 10) {
            return array(
                'level' => 'Very High',
                'color' => '#E53210',
                'recommendation' => 'Extra protection needed. Avoid sun exposure during midday.'
            );
        } else {
            return array(
                'level' => 'Extreme',
                'color' => '#B567A4',
                'recommendation' => 'Avoid sun exposure. Stay indoors during midday hours.'
            );
        }
    }
    
    /**
     * Clean old weather data
     */
    public function clean_old_data($days_to_keep = 30) {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "DELETE FROM {$this->table_name} 
             WHERE recorded_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days_to_keep
        );
        
        return $wpdb->query($sql);
    }
    
    /**
     * Export weather data to CSV
     */
    public function export_to_csv($location_name = null, $start_date = null, $end_date = null) {
        global $wpdb;
        
        $where_conditions = array();
        $where_values = array();
        
        if ($location_name) {
            $where_conditions[] = "location_name = %s";
            $where_values[] = $location_name;
        }
        
        if ($start_date) {
            $where_conditions[] = "DATE(recorded_at) >= %s";
            $where_values[] = $start_date;
        }
        
        if ($end_date) {
            $where_conditions[] = "DATE(recorded_at) <= %s";
            $where_values[] = $end_date;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $sql = "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY recorded_at DESC";
        
        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }
        
        $results = $wpdb->get_results($sql, ARRAY_A);
        
        if (empty($results)) {
            return false;
        }
        
        // Create CSV content
        $csv_data = array();
        $csv_data[] = array_keys($results[0]); // Header row
        
        foreach ($results as $row) {
            $csv_data[] = array_values($row);
        }
        
        return $csv_data;
    }
    
    /**
     * Get weather summary for location
     */
    public function get_weather_summary($location_name) {
        $latest_data = $this->get_latest_data($location_name);
        
        if (!$latest_data) {
            return null;
        }
        
        $comfort = $this->get_comfort_index($latest_data['temperature'], $latest_data['humidity']);
        $uv_info = $this->get_uv_recommendations($latest_data['uv_index']);
        
        return array(
            'current_weather' => $latest_data,
            'comfort_index' => $comfort,
            'uv_information' => $uv_info,
            'forecast' => $this->fetch_forecast_data($latest_data['latitude'], $latest_data['longitude'], 3)
        );
    }
}

// End of file
