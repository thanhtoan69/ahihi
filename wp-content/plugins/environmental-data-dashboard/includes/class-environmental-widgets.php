<?php
/**
 * Environmental Widgets
 * 
 * Handles rendering of environmental data visualization widgets
 * 
 * @package Environmental_Data_Dashboard
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Widgets {
    
    private static $instance = null;
    
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
        add_action('wp_footer', array($this, 'add_widget_scripts'));
    }
    
    /**
     * Render air quality widget
     */
    public function render_air_quality_widget($atts) {
        $location = sanitize_text_field($atts['location']);
        $show_map = $atts['show_map'] === 'true';
        $height = sanitize_text_field($atts['height']);
        
        // Get air quality data
        if (class_exists('Air_Quality_API')) {
            $air_quality_api = new Air_Quality_API();
            $latest_data = $air_quality_api->get_latest_data($location);
        } else {
            $latest_data = null;
        }
        
        ob_start();
        ?>
        <div class="env-air-quality-widget" style="height: <?php echo esc_attr($height); ?>;">
            <div class="widget-header">
                <h3><?php printf(__('Air Quality - %s', 'env-data-dashboard'), esc_html($location)); ?></h3>
                <button class="refresh-button" data-widget="air-quality" data-location="<?php echo esc_attr($location); ?>">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Refresh', 'env-data-dashboard'); ?>
                </button>
            </div>
            
            <div class="widget-content" id="air-quality-content">
                <?php if ($latest_data): ?>
                    <div class="aqi-display">
                        <div class="aqi-main" style="background-color: <?php echo $this->get_aqi_color($latest_data['aqi']); ?>;">
                            <div class="aqi-value"><?php echo esc_html($latest_data['aqi']); ?></div>
                            <div class="aqi-label"><?php _e('AQI', 'env-data-dashboard'); ?></div>
                        </div>
                        <div class="aqi-status">
                            <div class="status-text"><?php echo esc_html($latest_data['quality_level']); ?></div>
                            <div class="last-updated"><?php printf(__('Updated: %s', 'env-data-dashboard'), date('H:i', strtotime($latest_data['recorded_at']))); ?></div>
                        </div>
                    </div>
                    
                    <div class="pollutant-details">
                        <div class="pollutant-grid">
                            <div class="pollutant-item">
                                <span class="pollutant-label">PM2.5</span>
                                <span class="pollutant-value"><?php echo round($latest_data['pm25'], 1); ?></span>
                                <span class="pollutant-unit">μg/m³</span>
                            </div>
                            <div class="pollutant-item">
                                <span class="pollutant-label">PM10</span>
                                <span class="pollutant-value"><?php echo round($latest_data['pm10'], 1); ?></span>
                                <span class="pollutant-unit">μg/m³</span>
                            </div>
                            <div class="pollutant-item">
                                <span class="pollutant-label">O3</span>
                                <span class="pollutant-value"><?php echo round($latest_data['o3'] * 1000, 1); ?></span>
                                <span class="pollutant-unit">μg/m³</span>
                            </div>
                            <div class="pollutant-item">
                                <span class="pollutant-label">NO2</span>
                                <span class="pollutant-value"><?php echo round($latest_data['no2'] * 1000, 1); ?></span>
                                <span class="pollutant-unit">μg/m³</span>
                            </div>
                            <div class="pollutant-item">
                                <span class="pollutant-label">SO2</span>
                                <span class="pollutant-value"><?php echo round($latest_data['so2'] * 1000, 1); ?></span>
                                <span class="pollutant-unit">μg/m³</span>
                            </div>
                            <div class="pollutant-item">
                                <span class="pollutant-label">CO</span>
                                <span class="pollutant-value"><?php echo round($latest_data['co'], 2); ?></span>
                                <span class="pollutant-unit">mg/m³</span>
                            </div>
                        </div>
                    </div>
                    
                    <?php
                    // Get health recommendations
                    if (class_exists('Air_Quality_API')) {
                        $air_quality_api = new Air_Quality_API();
                        $recommendations = $air_quality_api->get_health_recommendations($latest_data['aqi']);
                    ?>
                    <div class="health-recommendations">
                        <h4><?php _e('Health Recommendations', 'env-data-dashboard'); ?></h4>
                        <div class="recommendation-item">
                            <strong><?php _e('General Public:', 'env-data-dashboard'); ?></strong>
                            <p><?php echo esc_html($recommendations['general']); ?></p>
                        </div>
                        <div class="recommendation-item">
                            <strong><?php _e('Sensitive Groups:', 'env-data-dashboard'); ?></strong>
                            <p><?php echo esc_html($recommendations['sensitive']); ?></p>
                        </div>
                        <div class="recommendation-item">
                            <strong><?php _e('Outdoor Activities:', 'env-data-dashboard'); ?></strong>
                            <p><?php echo esc_html($recommendations['outdoor_activities']); ?></p>
                        </div>
                    </div>
                    <?php } ?>
                    
                    <?php if ($show_map): ?>
                    <div class="air-quality-map">
                        <h4><?php _e('Location Map', 'env-data-dashboard'); ?></h4>
                        <div id="air-quality-map-<?php echo md5($location); ?>" class="widget-map" style="height: 200px;"></div>
                    </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="no-data-message">
                        <p><?php _e('No air quality data available for this location.', 'env-data-dashboard'); ?></p>
                        <button class="button" onclick="location.reload();"><?php _e('Try Again', 'env-data-dashboard'); ?></button>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="widget-loading" style="display: none;">
                <div class="loading-spinner"></div>
                <p><?php _e('Loading air quality data...', 'env-data-dashboard'); ?></p>
            </div>
        </div>
        
        <?php if ($show_map && $latest_data): ?>
        <script>
        jQuery(document).ready(function($) {
            if (typeof L !== 'undefined') {
                const mapId = 'air-quality-map-<?php echo md5($location); ?>';
                const map = L.map(mapId).setView([<?php echo $latest_data['latitude']; ?>, <?php echo $latest_data['longitude']; ?>], 12);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);
                
                const marker = L.marker([<?php echo $latest_data['latitude']; ?>, <?php echo $latest_data['longitude']; ?>]).addTo(map);
                marker.bindPopup('<b><?php echo esc_js($location); ?></b><br>AQI: <?php echo $latest_data['aqi']; ?><br><?php echo esc_js($latest_data['quality_level']); ?>');
            }
        });
        </script>
        <?php endif; ?>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Render weather widget
     */
    public function render_weather_widget($atts) {
        $location = sanitize_text_field($atts['location']);
        $show_forecast = $atts['show_forecast'] === 'true';
        $forecast_days = intval($atts['days']);
        
        // Get weather data
        if (class_exists('Weather_API')) {
            $weather_api = new Weather_API();
            $latest_data = $weather_api->get_latest_data($location);
            
            if ($show_forecast && $latest_data) {
                $forecast_data = $weather_api->fetch_forecast_data($latest_data['latitude'], $latest_data['longitude'], $forecast_days);
            }
        } else {
            $latest_data = null;
            $forecast_data = null;
        }
        
        ob_start();
        ?>
        <div class="env-weather-widget">
            <div class="widget-header">
                <h3><?php printf(__('Weather - %s', 'env-data-dashboard'), esc_html($location)); ?></h3>
                <button class="refresh-button" data-widget="weather" data-location="<?php echo esc_attr($location); ?>">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Refresh', 'env-data-dashboard'); ?>
                </button>
            </div>
            
            <div class="widget-content" id="weather-content">
                <?php if ($latest_data): ?>
                    <div class="current-weather">
                        <div class="weather-main">
                            <div class="temperature">
                                <span class="temp-value"><?php echo round($latest_data['temperature']); ?></span>
                                <span class="temp-unit">°C</span>
                            </div>
                            <div class="weather-condition">
                                <div class="condition-text"><?php echo esc_html($latest_data['weather_condition']); ?></div>
                                <div class="weather-icon">
                                    <?php echo $this->get_weather_icon($latest_data['weather_condition']); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="weather-details">
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <span class="detail-icon">💧</span>
                                    <span class="detail-label"><?php _e('Humidity', 'env-data-dashboard'); ?></span>
                                    <span class="detail-value"><?php echo $latest_data['humidity']; ?>%</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-icon">🌬️</span>
                                    <span class="detail-label"><?php _e('Wind', 'env-data-dashboard'); ?></span>
                                    <span class="detail-value"><?php echo round($latest_data['wind_speed']); ?> km/h</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-icon">📊</span>
                                    <span class="detail-label"><?php _e('Pressure', 'env-data-dashboard'); ?></span>
                                    <span class="detail-value"><?php echo round($latest_data['pressure']); ?> hPa</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-icon">👁️</span>
                                    <span class="detail-label"><?php _e('Visibility', 'env-data-dashboard'); ?></span>
                                    <span class="detail-value"><?php echo round($latest_data['visibility']); ?> km</span>
                                </div>
                                <?php if ($latest_data['uv_index'] > 0): ?>
                                <div class="detail-item">
                                    <span class="detail-icon">☀️</span>
                                    <span class="detail-label"><?php _e('UV Index', 'env-data-dashboard'); ?></span>
                                    <span class="detail-value"><?php echo round($latest_data['uv_index'], 1); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php
                        // Get comfort index
                        if (class_exists('Weather_API')) {
                            $weather_api = new Weather_API();
                            $comfort = $weather_api->get_comfort_index($latest_data['temperature'], $latest_data['humidity']);
                        ?>
                        <div class="comfort-index">
                            <h4><?php _e('Comfort Level', 'env-data-dashboard'); ?></h4>
                            <div class="comfort-display comfort-<?php echo $comfort['level']; ?>">
                                <span class="comfort-value"><?php echo round($comfort['index'], 1); ?>°C</span>
                                <span class="comfort-description"><?php echo esc_html($comfort['description']); ?></span>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                    
                    <?php if ($show_forecast && !empty($forecast_data)): ?>
                    <div class="weather-forecast">
                        <h4><?php printf(__('%d-Day Forecast', 'env-data-dashboard'), $forecast_days); ?></h4>
                        <div class="forecast-grid">
                            <?php 
                            $daily_forecasts = $this->group_forecast_by_day($forecast_data);
                            $count = 0;
                            foreach ($daily_forecasts as $date => $day_data): 
                                if ($count >= $forecast_days) break;
                                $avg_temp = array_sum(array_column($day_data, 'temperature')) / count($day_data);
                                $condition = $day_data[0]['weather_condition']; // Use first condition of the day
                            ?>
                            <div class="forecast-day">
                                <div class="forecast-date"><?php echo date('M j', strtotime($date)); ?></div>
                                <div class="forecast-icon"><?php echo $this->get_weather_icon($condition, true); ?></div>
                                <div class="forecast-temp"><?php echo round($avg_temp); ?>°C</div>
                                <div class="forecast-condition"><?php echo esc_html($condition); ?></div>
                            </div>
                            <?php 
                                $count++;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="last-updated">
                        <?php printf(__('Last updated: %s', 'env-data-dashboard'), date('H:i', strtotime($latest_data['recorded_at']))); ?>
                    </div>
                    
                <?php else: ?>
                    <div class="no-data-message">
                        <p><?php _e('No weather data available for this location.', 'env-data-dashboard'); ?></p>
                        <button class="button" onclick="location.reload();"><?php _e('Try Again', 'env-data-dashboard'); ?></button>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="widget-loading" style="display: none;">
                <div class="loading-spinner"></div>
                <p><?php _e('Loading weather data...', 'env-data-dashboard'); ?></p>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Render environmental data comparison widget
     */
    public function render_comparison_widget($locations, $metric = 'aqi') {
        ob_start();
        ?>
        <div class="env-comparison-widget">
            <div class="widget-header">
                <h3><?php _e('Environmental Data Comparison', 'env-data-dashboard'); ?></h3>
                <select id="comparison-metric">
                    <option value="aqi" <?php selected($metric, 'aqi'); ?>><?php _e('Air Quality Index', 'env-data-dashboard'); ?></option>
                    <option value="temperature" <?php selected($metric, 'temperature'); ?>><?php _e('Temperature', 'env-data-dashboard'); ?></option>
                    <option value="humidity" <?php selected($metric, 'humidity'); ?>><?php _e('Humidity', 'env-data-dashboard'); ?></option>
                    <option value="pm25" <?php selected($metric, 'pm25'); ?>><?php _e('PM2.5', 'env-data-dashboard'); ?></option>
                </select>
            </div>
            
            <div class="comparison-chart">
                <canvas id="comparison-chart"></canvas>
            </div>
            
            <div class="comparison-table">
                <table class="env-data-table">
                    <thead>
                        <tr>
                            <th><?php _e('Location', 'env-data-dashboard'); ?></th>
                            <th class="metric-header"><?php _e('AQI', 'env-data-dashboard'); ?></th>
                            <th><?php _e('Quality Level', 'env-data-dashboard'); ?></th>
                            <th><?php _e('Temperature', 'env-data-dashboard'); ?></th>
                            <th><?php _e('Last Updated', 'env-data-dashboard'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="comparison-table-body">
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            loadComparisonData();
            
            $('#comparison-metric').change(function() {
                loadComparisonData();
            });
            
            function loadComparisonData() {
                const metric = $('#comparison-metric').val();
                const locations = <?php echo json_encode($locations); ?>;
                
                $.ajax({
                    url: envDashboard.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_comparison_data',
                        nonce: envDashboard.nonce,
                        locations: locations,
                        metric: metric
                    },
                    success: function(response) {
                        if (response.success) {
                            updateComparisonDisplay(response.data, metric);
                        }
                    }
                });
            }
            
            function updateComparisonDisplay(data, metric) {
                // Update table
                const tbody = $('#comparison-table-body');
                tbody.empty();
                
                data.forEach(function(item) {
                    const row = $('<tr>');
                    row.append('<td>' + item.location + '</td>');
                    row.append('<td>' + (item.aqi || '-') + '</td>');
                    row.append('<td>' + (item.quality_level || '-') + '</td>');
                    row.append('<td>' + (item.temperature || '-') + '°C</td>');
                    row.append('<td>' + (item.last_updated || '-') + '</td>');
                    tbody.append(row);
                });
                
                // Update chart
                const chartData = {
                    labels: data.map(item => item.location),
                    datasets: [{
                        label: metric.toUpperCase(),
                        data: data.map(item => item[metric] || 0),
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                };
                
                const ctx = document.getElementById('comparison-chart');
                if (ctx && typeof Chart !== 'undefined') {
                    new Chart(ctx, {
                        type: 'bar',
                        data: chartData,
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }
            }
        });
        </script>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Render environmental trends widget
     */
    public function render_trends_widget($location, $metric = 'aqi', $days = 7) {
        ob_start();
        ?>
        <div class="env-trends-widget">
            <div class="widget-header">
                <h3><?php printf(__('Environmental Trends - %s', 'env-data-dashboard'), esc_html($location)); ?></h3>
                <div class="trend-controls">
                    <select id="trend-metric">
                        <option value="aqi" <?php selected($metric, 'aqi'); ?>><?php _e('Air Quality Index', 'env-data-dashboard'); ?></option>
                        <option value="pm25" <?php selected($metric, 'pm25'); ?>><?php _e('PM2.5', 'env-data-dashboard'); ?></option>
                        <option value="temperature" <?php selected($metric, 'temperature'); ?>><?php _e('Temperature', 'env-data-dashboard'); ?></option>
                        <option value="humidity" <?php selected($metric, 'humidity'); ?>><?php _e('Humidity', 'env-data-dashboard'); ?></option>
                    </select>
                    <select id="trend-period">
                        <option value="7" <?php selected($days, 7); ?>><?php _e('7 Days', 'env-data-dashboard'); ?></option>
                        <option value="14" <?php selected($days, 14); ?>><?php _e('14 Days', 'env-data-dashboard'); ?></option>
                        <option value="30" <?php selected($days, 30); ?>><?php _e('30 Days', 'env-data-dashboard'); ?></option>
                    </select>
                </div>
            </div>
            
            <div class="trends-chart">
                <canvas id="trends-chart"></canvas>
            </div>
            
            <div class="trend-stats">
                <div class="stat-grid">
                    <div class="stat-item">
                        <span class="stat-label"><?php _e('Average', 'env-data-dashboard'); ?></span>
                        <span class="stat-value" id="trend-average">-</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label"><?php _e('Maximum', 'env-data-dashboard'); ?></span>
                        <span class="stat-value" id="trend-maximum">-</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label"><?php _e('Minimum', 'env-data-dashboard'); ?></span>
                        <span class="stat-value" id="trend-minimum">-</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label"><?php _e('Trend', 'env-data-dashboard'); ?></span>
                        <span class="stat-value" id="trend-direction">-</span>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            loadTrendData();
            
            $('#trend-metric, #trend-period').change(function() {
                loadTrendData();
            });
            
            function loadTrendData() {
                const metric = $('#trend-metric').val();
                const period = $('#trend-period').val();
                const location = '<?php echo esc_js($location); ?>';
                
                $.ajax({
                    url: envDashboard.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_trend_data',
                        nonce: envDashboard.nonce,
                        location: location,
                        metric: metric,
                        days: period
                    },
                    success: function(response) {
                        if (response.success) {
                            updateTrendDisplay(response.data, metric);
                        }
                    }
                });
            }
            
            function updateTrendDisplay(data, metric) {
                // Update chart
                const chartData = {
                    labels: data.map(item => item.date),
                    datasets: [{
                        label: metric.toUpperCase(),
                        data: data.map(item => item[metric] || 0),
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1
                    }]
                };
                
                const ctx = document.getElementById('trends-chart');
                if (ctx && typeof Chart !== 'undefined') {
                    new Chart(ctx, {
                        type: 'line',
                        data: chartData,
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }
                
                // Update statistics
                const values = data.map(item => parseFloat(item[metric]) || 0).filter(val => val > 0);
                if (values.length > 0) {
                    const average = values.reduce((a, b) => a + b, 0) / values.length;
                    const maximum = Math.max(...values);
                    const minimum = Math.min(...values);
                    
                    $('#trend-average').text(average.toFixed(1));
                    $('#trend-maximum').text(maximum.toFixed(1));
                    $('#trend-minimum').text(minimum.toFixed(1));
                    
                    // Calculate trend direction
                    const firstHalf = values.slice(0, Math.floor(values.length / 2));
                    const secondHalf = values.slice(Math.floor(values.length / 2));
                    const firstAvg = firstHalf.reduce((a, b) => a + b, 0) / firstHalf.length;
                    const secondAvg = secondHalf.reduce((a, b) => a + b, 0) / secondHalf.length;
                    
                    let trend = '';
                    if (secondAvg > firstAvg * 1.05) {
                        trend = '📈 <?php _e('Increasing', 'env-data-dashboard'); ?>';
                    } else if (secondAvg < firstAvg * 0.95) {
                        trend = '📉 <?php _e('Decreasing', 'env-data-dashboard'); ?>';
                    } else {
                        trend = '➡️ <?php _e('Stable', 'env-data-dashboard'); ?>';
                    }
                    
                    $('#trend-direction').html(trend);
                }
            }
        });
        </script>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Get AQI color
     */
    private function get_aqi_color($aqi) {
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
     * Get weather icon based on condition
     */
    private function get_weather_icon($condition, $small = false) {
        $size = $small ? '24' : '32';
        $condition_lower = strtolower($condition);
        
        if (strpos($condition_lower, 'clear') !== false || strpos($condition_lower, 'sunny') !== false) {
            return '<span style="font-size: ' . $size . 'px;">☀️</span>';
        } elseif (strpos($condition_lower, 'cloud') !== false) {
            if (strpos($condition_lower, 'few') !== false || strpos($condition_lower, 'scattered') !== false) {
                return '<span style="font-size: ' . $size . 'px;">⛅</span>';
            } else {
                return '<span style="font-size: ' . $size . 'px;">☁️</span>';
            }
        } elseif (strpos($condition_lower, 'rain') !== false) {
            return '<span style="font-size: ' . $size . 'px;">🌧️</span>';
        } elseif (strpos($condition_lower, 'storm') !== false) {
            return '<span style="font-size: ' . $size . 'px;">⛈️</span>';
        } elseif (strpos($condition_lower, 'snow') !== false) {
            return '<span style="font-size: ' . $size . 'px;">❄️</span>';
        } elseif (strpos($condition_lower, 'fog') !== false || strpos($condition_lower, 'mist') !== false) {
            return '<span style="font-size: ' . $size . 'px;">🌫️</span>';
        } elseif (strpos($condition_lower, 'wind') !== false) {
            return '<span style="font-size: ' . $size . 'px;">🌬️</span>';
        } else {
            return '<span style="font-size: ' . $size . 'px;">🌤️</span>';
        }
    }
    
    /**
     * Group forecast data by day
     */
    private function group_forecast_by_day($forecast_data) {
        $grouped = array();
        
        foreach ($forecast_data as $forecast) {
            $date = date('Y-m-d', strtotime($forecast['datetime']));
            if (!isset($grouped[$date])) {
                $grouped[$date] = array();
            }
            $grouped[$date][] = $forecast;
        }
        
        return $grouped;
    }
    
    /**
     * Add widget scripts to footer
     */
    public function add_widget_scripts() {
        if (!wp_script_is('env-dashboard-frontend', 'enqueued')) {
            return;
        }
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Widget refresh functionality
            $('.refresh-button').click(function() {
                const button = $(this);
                const widget = button.data('widget');
                const location = button.data('location');
                const widgetContainer = button.closest('.env-air-quality-widget, .env-weather-widget');
                const content = widgetContainer.find('.widget-content');
                const loading = widgetContainer.find('.widget-loading');
                
                // Show loading state
                content.hide();
                loading.show();
                button.prop('disabled', true);
                
                // Make AJAX request
                const action = widget === 'air-quality' ? 'get_air_quality_data' : 'get_weather_data';
                
                $.ajax({
                    url: envDashboard.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: action,
                        nonce: envDashboard.nonce,
                        location: location
                    },
                    success: function(response) {
                        if (response.success) {
                            // Reload the page to show updated data
                            location.reload();
                        } else {
                            alert(envDashboard.strings.error);
                        }
                    },
                    error: function() {
                        alert(envDashboard.strings.error);
                    },
                    complete: function() {
                        loading.hide();
                        content.show();
                        button.prop('disabled', false);
                    }
                });
            });
            
            // Auto-refresh widgets every 30 minutes
            setInterval(function() {
                $('.refresh-button').trigger('click');
            }, 30 * 60 * 1000);
        });
        </script>
        <?php
    }
    
    /**
     * Render widget loading state
     */
    public function render_loading_state($message = '') {
        if (empty($message)) {
            $message = __('Loading environmental data...', 'env-data-dashboard');
        }
        
        return '<div class="env-widget-loading">
                    <div class="loading-spinner"></div>
                    <p>' . esc_html($message) . '</p>
                </div>';
    }
    
    /**
     * Render widget error state
     */
    public function render_error_state($message = '') {
        if (empty($message)) {
            $message = __('Error loading environmental data.', 'env-data-dashboard');
        }
        
        return '<div class="env-widget-error">
                    <div class="error-icon">⚠️</div>
                    <p>' . esc_html($message) . '</p>
                    <button class="button" onclick="location.reload();">' . __('Try Again', 'env-data-dashboard') . '</button>
                </div>';
    }
}

// End of file
