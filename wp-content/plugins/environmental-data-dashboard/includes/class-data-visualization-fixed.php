<?php

class Environmental_Data_Visualization {
    
    private $wpdb;
    private $table_prefix;
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
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
        $where_clause = $location ? $this->wpdb->prepare("WHERE location_name = %s", $location) : "";
        
        $results = $this->wpdb->get_results($this->wpdb->prepare("
            SELECT 
                DATE(recorded_at) as date,
                AVG(aqi) as avg_aqi,
                AVG(pm25) as avg_pm25,
                AVG(pm10) as avg_pm10
            FROM {$this->table_prefix}air_quality_data 
            {$where_clause}
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(recorded_at)
            ORDER BY date ASC
        ", $days));
        
        $labels = array();
        $data = array();
        
        foreach ($results as $row) {
            $labels[] = date('M j', strtotime($row->date));
            $data[] = round($row->avg_aqi, 1);
        }
        
        return array(
            'labels' => $labels,
            'datasets' => array(array(
                'label' => 'AQI',
                'data' => $data,
                'borderColor' => '#28a745',
                'backgroundColor' => 'rgba(40, 167, 69, 0.1)',
                'tension' => 0.1
            ))
        );
    }
    
    /**
     * Generate chart data for weather trends
     */
    public function get_weather_chart_data($days = 30, $location = null) {
        $where_clause = $location ? $this->wpdb->prepare("WHERE location_name = %s", $location) : "";
        
        $results = $this->wpdb->get_results($this->wpdb->prepare("
            SELECT 
                DATE(recorded_at) as date,
                AVG(temperature) as avg_temp,
                AVG(humidity) as avg_humidity
            FROM {$this->table_prefix}weather_data 
            {$where_clause}
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(recorded_at)
            ORDER BY date ASC
        ", $days));
        
        $labels = array();
        $temp_data = array();
        $humidity_data = array();
        
        foreach ($results as $row) {
            $labels[] = date('M j', strtotime($row->date));
            $temp_data[] = round($row->avg_temp, 1);
            $humidity_data[] = round($row->avg_humidity, 1);
        }
        
        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => 'Temperature (°C)',
                    'data' => $temp_data,
                    'borderColor' => '#FF6384',
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)'
                ),
                array(
                    'label' => 'Humidity (%)',
                    'data' => $humidity_data,
                    'borderColor' => '#36A2EB',
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)'
                )
            )
        );
    }
    
    /**
     * Generate carbon footprint visualization data
     */
    public function get_carbon_footprint_chart_data($user_id = null, $period = 'month') {
        $where_clause = $user_id ? $this->wpdb->prepare("WHERE user_id = %d", $user_id) : "";
        
        $results = $this->wpdb->get_results($this->wpdb->prepare("
            SELECT 
                category,
                SUM(carbon_amount) as total_emissions
            FROM {$this->table_prefix}carbon_footprint 
            {$where_clause}
            AND date_recorded >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
            GROUP BY category
            ORDER BY total_emissions DESC
        "));
        
        $labels = array();
        $data = array();
        $colors = array('#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF');
        
        foreach ($results as $index => $row) {
            $labels[] = ucfirst($row->category);
            $data[] = round($row->total_emissions, 2);
        }
        
        return array(
            'labels' => $labels,
            'datasets' => array(array(
                'label' => 'CO2 Emissions (kg)',
                'data' => $data,
                'backgroundColor' => array_slice($colors, 0, count($data))
            ))
        );
    }
    
    /**
     * Generate environmental impact comparison chart
     */
    public function get_impact_comparison_data($user_id) {
        // Get user's current month data
        $user_data = $this->wpdb->get_row($this->wpdb->prepare("
            SELECT 
                SUM(carbon_amount) as total_emissions
            FROM {$this->table_prefix}carbon_footprint 
            WHERE user_id = %d 
            AND MONTH(date_recorded) = MONTH(NOW()) 
            AND YEAR(date_recorded) = YEAR(NOW())
        ", $user_id));
        
        // Get community average (mock data for now)
        $community_avg = 850; // kg CO2e per month
        $national_avg = 1200; // kg CO2e per month
        
        return array(
            'labels' => array('You', 'Community Average', 'National Average'),
            'datasets' => array(array(
                'label' => 'CO2 Emissions (kg)',
                'data' => array(
                    round($user_data->total_emissions ?: 0, 2),
                    $community_avg,
                    $national_avg
                ),
                'backgroundColor' => array('#FF6384', '#36A2EB', '#FFCE56')
            ))
        );
    }
    
    /**
     * Generate environmental summary
     */
    public function get_environmental_summary($days = 7, $location = null) {
        $where_clause = $location ? $this->wpdb->prepare("AND location_name = %s", $location) : "";
        
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
        
        return array(
            'air_quality' => array(
                'avg_aqi' => round($air_summary->avg_aqi ?: 0, 1),
                'min_aqi' => round($air_summary->min_aqi ?: 0, 1),
                'max_aqi' => round($air_summary->max_aqi ?: 0, 1),
                'avg_pm25' => round($air_summary->avg_pm25 ?: 0, 1),
                'quality_level' => $this->get_air_quality_level($air_summary->avg_aqi ?: 0)
            )
        );
    }
    
    /**
     * Get chart data based on type
     */
    public function get_chart_data($chart_type, $period, $location = null) {
        switch ($chart_type) {
            case 'air_quality_trends':
                return $this->get_air_quality_chart_data(30, $location);
            case 'weather_trends':
                return $this->get_weather_chart_data(30, $location);
            case 'carbon_footprint':
                return $this->get_carbon_footprint_chart_data(get_current_user_id(), $period);
            case 'impact_comparison':
                return $this->get_impact_comparison_data(get_current_user_id());
            default:
                return array('labels' => array(), 'datasets' => array());
        }
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
