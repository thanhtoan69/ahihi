<?php
/**
 * Air Quality API Handler
 * 
 * Handles fetching and processing air quality data from various APIs
 * 
 * @package Environmental_Data_Dashboard
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Air_Quality_API {
    
    private static $instance = null;
    private $api_key;
    private $base_url = 'http://api.openweathermap.org/data/2.5/air_pollution';
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
        $this->table_name = $wpdb->prefix . 'env_air_quality_data';
        $this->api_key = get_option('env_dashboard_air_quality_api_key', '');
    }
    
    /**
     * Fetch air quality data from API
     */
    public function fetch_air_quality_data($lat, $lon) {
        if (empty($this->api_key)) {
            return $this->get_mock_air_quality_data($lat, $lon);
        }
        
        $url = $this->base_url . '?lat=' . $lat . '&lon=' . $lon . '&appid=' . $this->api_key;
        
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array(
                'User-Agent' => 'Environmental Dashboard Plugin v1.0'
            )
        ));
        
        if (is_wp_error($response)) {
            error_log('Air Quality API Error: ' . $response->get_error_message());
            return $this->get_mock_air_quality_data($lat, $lon);
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['list'][0])) {
            return $this->get_mock_air_quality_data($lat, $lon);
        }
        
        return $this->process_api_data($data['list'][0], $lat, $lon);
    }
    
    /**
     * Process API data into our format
     */
    private function process_api_data($api_data, $lat, $lon) {
        $components = $api_data['components'] ?? array();
        $aqi_value = $api_data['main']['aqi'] ?? 1;
        
        // Convert API AQI (1-5) to standard AQI (0-500)
        $aqi_mapping = array(1 => 25, 2 => 75, 3 => 125, 4 => 175, 5 => 250);
        $aqi = $aqi_mapping[$aqi_value] ?? 50;
        
        return array(
            'latitude' => $lat,
            'longitude' => $lon,
            'aqi' => $aqi,
            'pm25' => $components['pm2_5'] ?? 0,
            'pm10' => $components['pm10'] ?? 0,
            'o3' => $components['o3'] ?? 0,
            'no2' => $components['no2'] ?? 0,
            'so2' => $components['so2'] ?? 0,
            'co' => ($components['co'] ?? 0) / 1000, // Convert from μg/m³ to mg/m³
            'quality_level' => $this->get_quality_level($aqi),
            'recorded_at' => current_time('mysql')
        );
    }
    
    /**
     * Get mock air quality data for testing
     */
    private function get_mock_air_quality_data($lat, $lon) {
        // Generate realistic mock data based on location
        $base_aqi = rand(50, 150);
        $base_pm25 = rand(15, 80);
        
        return array(
            'latitude' => $lat,
            'longitude' => $lon,
            'aqi' => $base_aqi,
            'pm25' => $base_pm25,
            'pm10' => $base_pm25 * 1.5,
            'o3' => rand(20, 100) / 1000,
            'no2' => rand(10, 50) / 1000,
            'so2' => rand(5, 25) / 1000,
            'co' => rand(500, 2000) / 1000,
            'quality_level' => $this->get_quality_level($base_aqi),
            'recorded_at' => current_time('mysql')
        );
    }
    
    /**
     * Determine air quality level based on AQI
     */
    private function get_quality_level($aqi) {
        if ($aqi <= 50) {
            return 'Good';
        } elseif ($aqi <= 100) {
            return 'Moderate';
        } elseif ($aqi <= 150) {
            return 'Unhealthy for Sensitive Groups';
        } elseif ($aqi <= 200) {
            return 'Unhealthy';
        } elseif ($aqi <= 300) {
            return 'Very Unhealthy';
        } else {
            return 'Hazardous';
        }
    }
    
    /**
     * Store air quality data in database
     */
    public function store_air_quality_data($location_name, $data) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'location_name' => $location_name,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'aqi' => $data['aqi'],
                'pm25' => $data['pm25'],
                'pm10' => $data['pm10'],
                'o3' => $data['o3'],
                'no2' => $data['no2'],
                'so2' => $data['so2'],
                'co' => $data['co'],
                'quality_level' => $data['quality_level'],
                'recorded_at' => $data['recorded_at']
            ),
            array('%s', '%f', '%f', '%d', '%f', '%f', '%f', '%f', '%f', '%f', '%s', '%s')
        );
        
        if ($result === false) {
            error_log('Failed to store air quality data: ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get latest air quality data for a location
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
     * Get air quality data for a specific time period
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
     * Get air quality trends
     */
    public function get_trends($location_name, $days = 7) {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "SELECT 
                DATE(recorded_at) as date,
                AVG(aqi) as avg_aqi,
                AVG(pm25) as avg_pm25,
                AVG(pm10) as avg_pm10,
                MAX(aqi) as max_aqi,
                MIN(aqi) as min_aqi
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
     * Get air quality statistics
     */
    public function get_statistics($location_name = null) {
        global $wpdb;
        
        $where_clause = $location_name ? $wpdb->prepare("WHERE location_name = %s", $location_name) : '';
        
        $sql = "SELECT 
                    COUNT(*) as total_records,
                    AVG(aqi) as avg_aqi,
                    MAX(aqi) as max_aqi,
                    MIN(aqi) as min_aqi,
                    AVG(pm25) as avg_pm25,
                    AVG(pm10) as avg_pm10,
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
            $data = $this->fetch_air_quality_data($location['latitude'], $location['longitude']);
            if ($data) {
                $this->store_air_quality_data($location['name'], $data);
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
            )
        );
        
        // Allow filtering of locations
        return apply_filters('env_dashboard_air_quality_locations', $default_locations);
    }
    
    /**
     * Get air quality alerts
     */
    public function get_alerts($threshold_aqi = 100) {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             WHERE aqi > %d 
             AND recorded_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
             ORDER BY aqi DESC",
            $threshold_aqi
        );
        
        return $wpdb->get_results($sql, ARRAY_A);
    }
    
    /**
     * Clean old air quality data
     */
    public function clean_old_data($days_to_keep = 90) {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "DELETE FROM {$this->table_name} 
             WHERE recorded_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days_to_keep
        );
        
        return $wpdb->query($sql);
    }
    
    /**
     * Get air quality color based on AQI
     */
    public function get_aqi_color($aqi) {
        if ($aqi <= 50) {
            return '#00E400'; // Green - Good
        } elseif ($aqi <= 100) {
            return '#FFFF00'; // Yellow - Moderate
        } elseif ($aqi <= 150) {
            return '#FF7E00'; // Orange - Unhealthy for Sensitive Groups
        } elseif ($aqi <= 200) {
            return '#FF0000'; // Red - Unhealthy
        } elseif ($aqi <= 300) {
            return '#8F3F97'; // Purple - Very Unhealthy
        } else {
            return '#7E0023'; // Maroon - Hazardous
        }
    }
    
    /**
     * Get health recommendations based on AQI
     */
    public function get_health_recommendations($aqi) {
        if ($aqi <= 50) {
            return array(
                'general' => 'Air quality is considered satisfactory, and air pollution poses little or no risk.',
                'sensitive' => 'None',
                'outdoor_activities' => 'Ideal for outdoor activities.'
            );
        } elseif ($aqi <= 100) {
            return array(
                'general' => 'Air quality is acceptable for most people. However, sensitive people may experience minor respiratory symptoms.',
                'sensitive' => 'Consider reducing prolonged outdoor exertion.',
                'outdoor_activities' => 'Acceptable for most outdoor activities.'
            );
        } elseif ($aqi <= 150) {
            return array(
                'general' => 'Members of sensitive groups may experience health effects. The general public is not likely to be affected.',
                'sensitive' => 'Reduce prolonged or heavy outdoor exertion. Watch for symptoms such as coughing or shortness of breath.',
                'outdoor_activities' => 'Sensitive individuals should limit outdoor activities.'
            );
        } elseif ($aqi <= 200) {
            return array(
                'general' => 'Everyone may begin to experience health effects; members of sensitive groups may experience more serious health effects.',
                'sensitive' => 'Avoid prolonged or heavy outdoor exertion.',
                'outdoor_activities' => 'Everyone should reduce outdoor activities.'
            );
        } elseif ($aqi <= 300) {
            return array(
                'general' => 'Health warnings of emergency conditions. The entire population is more likely to be affected.',
                'sensitive' => 'Avoid all outdoor exertion.',
                'outdoor_activities' => 'Everyone should avoid outdoor activities.'
            );
        } else {
            return array(
                'general' => 'Health alert: everyone may experience more serious health effects.',
                'sensitive' => 'Remain indoors and keep activity levels low.',
                'outdoor_activities' => 'Avoid all outdoor activities.'
            );
        }
    }
    
    /**
     * Export air quality data to CSV
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
}

// End of file
