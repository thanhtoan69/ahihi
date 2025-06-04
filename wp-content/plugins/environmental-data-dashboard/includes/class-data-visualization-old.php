<?php

class Environmental_Data_Visualization {
    
    private $wpdb;
    private $table_prefix;
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance        return array(
            'labels' => array('You', 'Community Average', 'National Average'),
            'datasets' => array(array(
                'label' => 'CO2 Emissions (kg)',
                'data' => array(
                    round($user_data->total_emissions ?: 0, 2),
                    round($community_avg ?: 0, 2),
                    $national_avg
                ),
                'backgroundColor' => array(
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56'
                ),
                'borderWidth' => 2
            ))
        );self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_prefix = $wpdb->prefix . 'env_';
    }
    
    /**
     * Generate chart data for air quality trends
     */
    public function get_air_quality_chart_data($days = 30, $location = null) {
        $where_clause = $location ? $this->wpdb->prepare("WHERE location = %s", $location) : "";
        
        $results = $this->wpdb->get_results($this->wpdb->prepare("
            SELECT 
                DATE(recorded_at) as date,
                AVG(aqi) as avg_aqi,
                AVG(pm25) as avg_pm25,
                AVG(pm10) as avg_pm10,
                AVG(co) as avg_co,
                AVG(no2) as avg_no2,
                AVG(so2) as avg_so2,
                AVG(o3) as avg_o3
            FROM {$this->table_prefix}air_quality_data 
            {$where_clause}
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(recorded_at)
            ORDER BY date ASC
        ", $days));
        
        $chart_data = [
            'labels' => [],
            'datasets' => [
                [
                    'label' => 'Air Quality Index',
                    'data' => [],
                    'borderColor' => '#FF6384',
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'yAxisID' => 'y'
                ],
                [
                    'label' => 'PM2.5 (μg/m³)',
                    'data' => [],
                    'borderColor' => '#36A2EB',
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'yAxisID' => 'y1'
                ],
                [
                    'label' => 'PM10 (μg/m³)',
                    'data' => [],
                    'borderColor' => '#FFCE56',
                    'backgroundColor' => 'rgba(255, 206, 86, 0.2)',
                    'yAxisID' => 'y1'
                ]
            ]
        ];
        
        foreach ($results as $row) {
            $chart_data['labels'][] = date('M j', strtotime($row->date));
            $chart_data['datasets'][0]['data'][] = round($row->avg_aqi, 1);
            $chart_data['datasets'][1]['data'][] = round($row->avg_pm25, 1);
            $chart_data['datasets'][2]['data'][] = round($row->avg_pm10, 1);
        }
        
        return $chart_data;
    }
    
    /**
     * Generate chart data for weather trends
     */
    public function get_weather_chart_data($days = 30, $location = null) {
        $where_clause = $location ? $this->wpdb->prepare("WHERE location = %s", $location) : "";
        
        $results = $this->wpdb->get_results($this->wpdb->prepare("
            SELECT 
                DATE(recorded_at) as date,
                AVG(temperature) as avg_temp,
                AVG(humidity) as avg_humidity,
                AVG(wind_speed) as avg_wind_speed,
                AVG(pressure) as avg_pressure,
                AVG(uv_index) as avg_uv
            FROM {$this->table_prefix}weather_data 
            {$where_clause}
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(recorded_at)
            ORDER BY date ASC
        ", $days));
        
        $chart_data = [
            'labels' => [],
            'datasets' => [
                [
                    'label' => 'Temperature (°C)',
                    'data' => [],
                    'borderColor' => '#FF6384',
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'yAxisID' => 'y'
                ],
                [
                    'label' => 'Humidity (%)',
                    'data' => [],
                    'borderColor' => '#36A2EB',
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'yAxisID' => 'y1'
                ],
                [
                    'label' => 'Wind Speed (m/s)',
                    'data' => [],
                    'borderColor' => '#FFCE56',
                    'backgroundColor' => 'rgba(255, 206, 86, 0.2)',
                    'yAxisID' => 'y2'
                ]
            ]
        ];
        
        foreach ($results as $row) {
            $chart_data['labels'][] = date('M j', strtotime($row->date));
            $chart_data['datasets'][0]['data'][] = round($row->avg_temp, 1);
            $chart_data['datasets'][1]['data'][] = round($row->avg_humidity, 1);
            $chart_data['datasets'][2]['data'][] = round($row->avg_wind_speed, 1);
        }
        
        return $chart_data;
    }
    
    /**
     * Generate carbon footprint visualization data
     */
    public function get_carbon_footprint_chart_data($user_id = null, $period = 'month') {
        $interval = $period === 'year' ? 'YEAR' : 'MONTH';
        $date_format = $period === 'year' ? '%Y' : '%Y-%m';
        
        $where_clause = $user_id ? $this->wpdb->prepare("WHERE user_id = %d", $user_id) : "";
        
        $results = $this->wpdb->get_results($this->wpdb->prepare("
            SELECT 
                DATE_FORMAT(recorded_at, '{$date_format}') as period,
                category,
                SUM(emission_amount) as total_emissions
            FROM {$this->table_prefix}carbon_footprint 
            {$where_clause}
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL 12 {$interval})
            GROUP BY period, category
            ORDER BY period ASC, category
        "));
        
        $categories = ['transportation', 'energy', 'food', 'waste', 'consumption'];
        $periods = [];
        $data_by_category = array_fill_keys($categories, []);
        
        foreach ($results as $row) {
            if (!in_array($row->period, $periods)) {
                $periods[] = $row->period;
            }
            $data_by_category[$row->category][] = [
                'x' => $row->period,
                'y' => round($row->total_emissions, 2)
            ];
        }
        
        $datasets = [];
        $colors = [
            'transportation' => '#FF6384',
            'energy' => '#36A2EB',
            'food' => '#FFCE56',
            'waste' => '#4BC0C0',
            'consumption' => '#9966FF'
        ];
        
        foreach ($categories as $category) {
            $datasets[] = [
                'label' => ucfirst($category),
                'data' => $data_by_category[$category],
                'backgroundColor' => $colors[$category],
                'borderColor' => $colors[$category],
                'borderWidth' => 2
            ];
        }
        
        return [
            'labels' => $periods,
            'datasets' => $datasets
        ];
    }
    
    /**
     * Generate environmental impact comparison chart
     */
    public function get_impact_comparison_data($user_id) {
        // Get user's current month data
        $user_data = $this->wpdb->get_row($this->wpdb->prepare("
            SELECT 
                SUM(emission_amount) as total_emissions,
                COUNT(DISTINCT category) as categories_tracked
            FROM {$this->table_prefix}carbon_footprint 
            WHERE user_id = %d 
            AND MONTH(recorded_at) = MONTH(NOW()) 
            AND YEAR(recorded_at) = YEAR(NOW())
        ", $user_id));
        
        // Get community average
        $community_avg = $this->wpdb->get_var("
            SELECT AVG(monthly_emissions) as avg_emissions
            FROM (
                SELECT 
                    user_id,
                    SUM(emission_amount) as monthly_emissions
                FROM {$this->table_prefix}carbon_footprint 
                WHERE MONTH(recorded_at) = MONTH(NOW()) 
                AND YEAR(recorded_at) = YEAR(NOW())
                GROUP BY user_id
            ) as monthly_totals
        ");
          // Get national average (mock data)
        $national_avg = 1200; // kg CO2e per month
        
        return array(
            'labels' => array('You', 'Community Average', 'National Average'),
            'datasets' => array(array(
                'label' => 'CO2 Emissions (kg)',
                'data' => array(
                    round($user_data->total_emissions ?? 0, 2),
                    round($community_avg ?? 0, 2),
                    $national_avg
                ],
                'backgroundColor' => [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56'
                ],
                'borderWidth' => 2
            }]
        ];
    }
    
    /**
     * Generate heatmap data for environmental metrics
     */
    public function get_environmental_heatmap_data($metric = 'aqi', $days = 30) {
        $table = $metric === 'aqi' ? 'air_quality_data' : 'weather_data';
        $column = $metric === 'aqi' ? 'aqi' : ($metric === 'temperature' ? 'temperature' : 'humidity');
        
        $results = $this->wpdb->get_results($this->wpdb->prepare("
            SELECT 
                DATE(recorded_at) as date,
                HOUR(recorded_at) as hour,
                AVG({$column}) as avg_value
            FROM {$this->table_prefix}{$table}
            WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(recorded_at), HOUR(recorded_at)
            ORDER BY date ASC, hour ASC
        ", $days));
        
        $heatmap_data = [];
        foreach ($results as $row) {
            $heatmap_data[] = [
                'x' => $row->date,
                'y' => $row->hour,
                'v' => round($row->avg_value, 2)
            ];
        }
        
        return $heatmap_data;
    }
    
    /**
     * Generate gauge chart data for current conditions
     */
    public function get_gauge_chart_data($metric = 'aqi', $location = null) {
        $where_clause = $location ? $this->wpdb->prepare("AND location = %s", $location) : "";
        
        if ($metric === 'aqi') {
            $result = $this->wpdb->get_var($this->wpdb->prepare("
                SELECT aqi 
                FROM {$this->table_prefix}air_quality_data 
                WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                {$where_clause}
                ORDER BY recorded_at DESC 
                LIMIT 1
            "));
            
            $ranges = [
                ['min' => 0, 'max' => 50, 'color' => '#00E400', 'label' => 'Good'],
                ['min' => 51, 'max' => 100, 'color' => '#FFFF00', 'label' => 'Moderate'],
                ['min' => 101, 'max' => 150, 'color' => '#FF7E00', 'label' => 'Unhealthy for Sensitive'],
                ['min' => 151, 'max' => 200, 'color' => '#FF0000', 'label' => 'Unhealthy'],
                ['min' => 201, 'max' => 300, 'color' => '#8F3F97', 'label' => 'Very Unhealthy'],
                ['min' => 301, 'max' => 500, 'color' => '#7E0023', 'label' => 'Hazardous']
            ];
        } else {
            $result = $this->wpdb->get_var($this->wpdb->prepare("
                SELECT temperature 
                FROM {$this->table_prefix}weather_data 
                WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                {$where_clause}
                ORDER BY recorded_at DESC 
                LIMIT 1
            "));
            
            $ranges = [
                ['min' => -20, 'max' => 0, 'color' => '#0066CC', 'label' => 'Very Cold'],
                ['min' => 1, 'max' => 10, 'color' => '#66B2FF', 'label' => 'Cold'],
                ['min' => 11, 'max' => 20, 'color' => '#99CCFF', 'label' => 'Cool'],
                ['min' => 21, 'max' => 30, 'color' => '#FFFF99', 'label' => 'Warm'],
                ['min' => 31, 'max' => 40, 'color' => '#FFCC00', 'label' => 'Hot'],
                ['min' => 41, 'max' => 50, 'color' => '#FF6600', 'label' => 'Very Hot']
            ];
        }
        
        return [
            'value' => round($result ?? 0, 1),
            'ranges' => $ranges,
            'min' => $ranges[0]['min'],
            'max' => end($ranges)['max']
        ];
    }
    
    /**
     * Generate environmental alerts data
     */
    public function get_environmental_alerts($location = null) {
        $alerts = [];
        
        // Check air quality alerts
        $where_clause = $location ? $this->wpdb->prepare("AND location = %s", $location) : "";
        
        $high_aqi = $this->wpdb->get_row($this->wpdb->prepare("
            SELECT aqi, pm25, location
            FROM {$this->table_prefix}air_quality_data 
            WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            {$where_clause}
            AND aqi > 100
            ORDER BY recorded_at DESC 
            LIMIT 1
        "));
        
        if ($high_aqi) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Air Quality Alert',
                'message' => "AQI is {$high_aqi->aqi} in {$high_aqi->location}. Consider limiting outdoor activities.",
                'timestamp' => current_time('mysql')
            ];
        }
        
        // Check weather alerts
        $extreme_weather = $this->wpdb->get_row($this->wpdb->prepare("
            SELECT temperature, wind_speed, humidity, location
            FROM {$this->table_prefix}weather_data 
            WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            {$where_clause}
            AND (temperature > 35 OR temperature < 0 OR wind_speed > 15)
            ORDER BY recorded_at DESC 
            LIMIT 1
        "));
        
        if ($extreme_weather) {
            $message = '';
            if ($extreme_weather->temperature > 35) {
                $message = "High temperature alert: {$extreme_weather->temperature}°C";
            } elseif ($extreme_weather->temperature < 0) {
                $message = "Freezing temperature alert: {$extreme_weather->temperature}°C";
            } elseif ($extreme_weather->wind_speed > 15) {
                $message = "High wind alert: {$extreme_weather->wind_speed} m/s";
            }
            
            $alerts[] = [
                'type' => 'info',
                'title' => 'Weather Alert',
                'message' => $message . " in {$extreme_weather->location}",
                'timestamp' => current_time('mysql')
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Generate summary statistics
     */
    public function get_environmental_summary($days = 7, $location = null) {
        $where_clause = $location ? $this->wpdb->prepare("AND location = %s", $location) : "";
        
        // Air quality summary
        $air_summary = $this->wpdb->get_row($this->wpdb->prepare("
            SELECT 
                AVG(aqi) as avg_aqi,
                MIN(aqi) as min_aqi,
                MAX(aqi) as max_aqi,
                AVG(pm25) as avg_pm25
            FROM {$this->table_prefix}air_quality_data 
            WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            {$where_clause}
        ", $days));
        
        // Weather summary
        $weather_summary = $this->wpdb->get_row($this->wpdb->prepare("
            SELECT 
                AVG(temperature) as avg_temp,
                MIN(temperature) as min_temp,
                MAX(temperature) as max_temp,
                AVG(humidity) as avg_humidity
            FROM {$this->table_prefix}weather_data 
            WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            {$where_clause}
        ", $days));
        
        return [
            'air_quality' => [
                'avg_aqi' => round($air_summary->avg_aqi ?? 0, 1),
                'min_aqi' => round($air_summary->min_aqi ?? 0, 1),
                'max_aqi' => round($air_summary->max_aqi ?? 0, 1),
                'avg_pm25' => round($air_summary->avg_pm25 ?? 0, 1),
                'quality_level' => $this->get_air_quality_level($air_summary->avg_aqi ?? 0)
            ],
            'weather' => [
                'avg_temp' => round($weather_summary->avg_temp ?? 0, 1),
                'min_temp' => round($weather_summary->min_temp ?? 0, 1),
                'max_temp' => round($weather_summary->max_temp ?? 0, 1),
                'avg_humidity' => round($weather_summary->avg_humidity ?? 0, 1)
            ]
        ];
    }
    
    /**
     * Helper function to determine air quality level
     */
    private function get_air_quality_level($aqi) {
        if ($aqi <= 50) return 'Good';
        elseif ($aqi <= 100) return 'Moderate';
        elseif ($aqi <= 150) return 'Unhealthy for Sensitive Groups';
        elseif ($aqi <= 200) return 'Unhealthy';
        elseif ($aqi <= 300) return 'Very Unhealthy';
        else return 'Hazardous';
    }
}

?>
