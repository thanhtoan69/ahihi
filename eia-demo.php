<?php
/**
 * Environmental Integration APIs - Frontend Testing Page
 * Demonstrates all plugin features and widgets
 */

require_once('wp-config.php');
require_once('wp-load.php');

// Check if plugin is active
if (!is_plugin_active('environmental-integration-apis/environmental-integration-apis.php')) {
    wp_die('Environmental Integration APIs plugin is not active. Please activate it first.');
}

get_header();
?>

<style>
/* Additional styling for demo page */
.eia-demo-section {
    background: #f9f9f9;
    padding: 30px;
    margin: 30px 0;
    border-radius: 8px;
    border-left: 5px solid #0073aa;
}

.eia-demo-title {
    color: #0073aa;
    font-size: 24px;
    margin-bottom: 15px;
    font-weight: bold;
}

.eia-demo-description {
    margin-bottom: 20px;
    color: #666;
    line-height: 1.6;
}

.eia-demo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.eia-demo-card {
    background: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.eia-shortcode-example {
    background: #f4f4f4;
    padding: 15px;
    border-radius: 3px;
    font-family: monospace;
    margin: 10px 0;
    border-left: 3px solid #0073aa;
}

.eia-widget-container {
    min-height: 200px;
    padding: 20px;
    background: white;
    border-radius: 5px;
    margin: 15px 0;
}
</style>

<div class="wrap">
    <h1>Environmental Integration APIs - Live Demo</h1>
    <p style="font-size: 18px; color: #666; margin-bottom: 30px;">
        This page demonstrates all the features and widgets available in the Environmental Integration APIs plugin.
    </p>

    <!-- Google Maps Section -->
    <div class="eia-demo-section">
        <h2 class="eia-demo-title">🗺️ Google Maps Integration</h2>
        <p class="eia-demo-description">
            Interactive maps with environmental markers, location picking, and geocoding capabilities.
        </p>
        
        <div class="eia-demo-grid">
            <div class="eia-demo-card">
                <h3>Interactive Environmental Map</h3>
                <div class="eia-shortcode-example">
                    [env_google_map center_lat="21.0285" center_lng="105.8542" zoom="12" height="300px" 
                     markers='[{"lat":21.0285,"lng":105.8542,"title":"Hanoi Environmental Center","content":"Main environmental monitoring station"}]']
                </div>
                <div class="eia-widget-container">
                    <?php echo do_shortcode('[env_google_map center_lat="21.0285" center_lng="105.8542" zoom="12" height="300px"]'); ?>
                </div>
            </div>
            
            <div class="eia-demo-card">
                <h3>Location Picker</h3>
                <div class="eia-shortcode-example">
                    [env_location_picker name="demo_location" default_lat="21.0285" default_lng="105.8542" show_coordinates="true"]
                </div>
                <div class="eia-widget-container">
                    <?php echo do_shortcode('[env_location_picker name="demo_location" default_lat="21.0285" default_lng="105.8542" show_coordinates="true"]'); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Weather Section -->
    <div class="eia-demo-section">
        <h2 class="eia-demo-title">🌤️ Weather Integration</h2>
        <p class="eia-demo-description">
            Real-time weather data, forecasts, and severe weather alerts for environmental monitoring.
        </p>
        
        <div class="eia-demo-grid">
            <div class="eia-demo-card">
                <h3>Current Weather Widget</h3>
                <div class="eia-shortcode-example">
                    [env_weather location="Hanoi" units="metric" show_forecast="false"]
                </div>
                <div class="eia-widget-container">
                    <?php echo do_shortcode('[env_weather location="Hanoi" units="metric" show_forecast="false"]'); ?>
                </div>
            </div>
            
            <div class="eia-demo-card">
                <h3>Weather Forecast</h3>
                <div class="eia-shortcode-example">
                    [env_weather_forecast location="Hanoi" days="5" units="metric"]
                </div>
                <div class="eia-widget-container">
                    <?php echo do_shortcode('[env_weather_forecast location="Hanoi" days="5" units="metric"]'); ?>
                </div>
            </div>
            
            <div class="eia-demo-card">
                <h3>Compact Weather Widget</h3>
                <div class="eia-shortcode-example">
                    [env_weather_widget location="Ho Chi Minh City" style="compact" refresh_interval="300"]
                </div>
                <div class="eia-widget-container">
                    <?php echo do_shortcode('[env_weather_widget location="Ho Chi Minh City" style="compact" refresh_interval="300"]'); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Air Quality Section -->
    <div class="eia-demo-section">
        <h2 class="eia-demo-title">🌬️ Air Quality Monitoring</h2>
        <p class="eia-demo-description">
            Real-time air quality data with AQI ratings, pollutant breakdowns, and health recommendations.
        </p>
        
        <div class="eia-demo-grid">
            <div class="eia-demo-card">
                <h3>Air Quality Index Display</h3>
                <div class="eia-shortcode-example">
                    [env_air_quality location="Hanoi" show_pollutants="true"]
                </div>
                <div class="eia-widget-container">
                    <?php echo do_shortcode('[env_air_quality location="Hanoi" show_pollutants="true"]'); ?>
                </div>
            </div>
            
            <div class="eia-demo-card">
                <h3>Air Quality Widget</h3>
                <div class="eia-shortcode-example">
                    [env_air_quality_widget location="Hanoi" style="minimal" show_forecast="true"]
                </div>
                <div class="eia-widget-container">
                    <?php echo do_shortcode('[env_air_quality_widget location="Hanoi" style="minimal" show_forecast="true"]'); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Social Media Section -->
    <div class="eia-demo-section">
        <h2 class="eia-demo-title">📱 Social Media Integration</h2>
        <p class="eia-demo-description">
            Social sharing buttons and environmental social media feeds for community engagement.
        </p>
        
        <div class="eia-demo-grid">
            <div class="eia-demo-card">
                <h3>Social Sharing Buttons</h3>
                <div class="eia-shortcode-example">
                    [env_social_share platforms="facebook,twitter,linkedin,whatsapp" url="current" title="Environmental Data"]
                </div>
                <div class="eia-widget-container">
                    <?php echo do_shortcode('[env_social_share platforms="facebook,twitter,linkedin,whatsapp" url="current" title="Environmental Data"]'); ?>
                </div>
            </div>
            
            <div class="eia-demo-card">
                <h3>Environmental Social Feed</h3>
                <div class="eia-shortcode-example">
                    [env_social_feed platform="facebook" count="5" show_images="true"]
                </div>
                <div class="eia-widget-container">
                    <?php echo do_shortcode('[env_social_feed platform="facebook" count="5" show_images="true"]'); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- API Information Section -->
    <div class="eia-demo-section">
        <h2 class="eia-demo-title">🔗 REST API Endpoints</h2>
        <p class="eia-demo-description">
            The plugin provides comprehensive REST API endpoints for external integrations.
        </p>
        
        <div class="eia-demo-grid">
            <div class="eia-demo-card">
                <h3>Available Endpoints</h3>
                <ul style="list-style-type: none; padding: 0;">
                    <li><strong>Google Maps:</strong></li>
                    <li style="margin-left: 20px;">POST /wp-json/environmental-integration/v1/google-maps/geocode</li>
                    <li style="margin-left: 20px;">POST /wp-json/environmental-integration/v1/google-maps/reverse-geocode</li>
                    <li style="margin-left: 20px;">POST /wp-json/environmental-integration/v1/google-maps/nearby-places</li>
                    
                    <li style="margin-top: 15px;"><strong>Weather:</strong></li>
                    <li style="margin-left: 20px;">GET /wp-json/environmental-integration/v1/weather/current</li>
                    <li style="margin-left: 20px;">GET /wp-json/environmental-integration/v1/weather/forecast</li>
                    <li style="margin-left: 20px;">GET /wp-json/environmental-integration/v1/weather/alerts</li>
                    
                    <li style="margin-top: 15px;"><strong>Air Quality:</strong></li>
                    <li style="margin-left: 20px;">GET /wp-json/environmental-integration/v1/air-quality/current</li>
                    <li style="margin-left: 20px;">GET /wp-json/environmental-integration/v1/air-quality/forecast</li>
                    
                    <li style="margin-top: 15px;"><strong>Social Media:</strong></li>
                    <li style="margin-left: 20px;">POST /wp-json/environmental-integration/v1/social/share</li>
                    <li style="margin-left: 20px;">GET /wp-json/environmental-integration/v1/social/feed</li>
                    
                    <li style="margin-top: 15px;"><strong>Webhooks:</strong></li>
                    <li style="margin-left: 20px;">GET/POST/PUT/DELETE /wp-json/environmental-integration/v1/webhooks</li>
                    
                    <li style="margin-top: 15px;"><strong>Monitoring:</strong></li>
                    <li style="margin-left: 20px;">GET /wp-json/environmental-integration/v1/monitor/status</li>
                    <li style="margin-left: 20px;">GET /wp-json/environmental-integration/v1/monitor/statistics</li>
                </ul>
            </div>
            
            <div class="eia-demo-card">
                <h3>API Testing</h3>
                <p>Test the REST endpoints directly:</p>
                <button id="test-weather-api" class="button button-primary" style="margin: 5px;">
                    Test Weather API
                </button>
                <button id="test-air-quality-api" class="button button-primary" style="margin: 5px;">
                    Test Air Quality API
                </button>
                <button id="test-monitor-api" class="button button-primary" style="margin: 5px;">
                    Test Monitor API
                </button>
                <div id="api-test-results" style="margin-top: 15px; padding: 10px; background: #f0f0f0; border-radius: 3px; min-height: 50px;">
                    API test results will appear here...
                </div>
            </div>
        </div>
    </div>

    <!-- Configuration Section -->
    <div class="eia-demo-section">
        <h2 class="eia-demo-title">⚙️ Plugin Configuration</h2>
        <p class="eia-demo-description">
            Configure API keys and settings to enable full functionality.
        </p>
        
        <div class="eia-demo-card">
            <h3>Configuration Steps</h3>
            <ol>
                <li><strong>Access Admin Panel:</strong> Go to WordPress Admin → Environmental APIs</li>
                <li><strong>API Configuration:</strong> Enter your API keys for each service:
                    <ul>
                        <li>Google Maps API Key (required for maps and geocoding)</li>
                        <li>Weather API Key (OpenWeatherMap, AccuWeather, or WeatherAPI)</li>
                        <li>Air Quality API Key (IQAir or similar service)</li>
                        <li>Social Media API credentials (Facebook, Twitter, Instagram)</li>
                    </ul>
                </li>
                <li><strong>Test Connections:</strong> Use the built-in API testing tools</li>
                <li><strong>Configure Webhooks:</strong> Set up event notifications if needed</li>
                <li><strong>Monitor Performance:</strong> Check API usage and performance metrics</li>
            </ol>
            
            <?php if (current_user_can('manage_options')): ?>
                <p style="margin-top: 20px;">
                    <a href="<?php echo admin_url('admin.php?page=eia-dashboard'); ?>" 
                       class="button button-primary button-large">
                        Open Plugin Dashboard
                    </a>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Documentation Section -->
    <div class="eia-demo-section">
        <h2 class="eia-demo-title">📚 Documentation & Support</h2>
        <p class="eia-demo-description">
            Complete documentation and support resources for the Environmental Integration APIs plugin.
        </p>
        
        <div class="eia-demo-grid">
            <div class="eia-demo-card">
                <h3>Key Features</h3>
                <ul>
                    <li>Multi-provider API support</li>
                    <li>Intelligent caching system</li>
                    <li>Real-time monitoring</li>
                    <li>Webhook integration</li>
                    <li>Mobile-responsive widgets</li>
                    <li>Comprehensive logging</li>
                    <li>Rate limiting protection</li>
                    <li>REST API endpoints</li>
                    <li>Admin dashboard</li>
                    <li>Error handling & alerts</li>
                </ul>
            </div>
            
            <div class="eia-demo-card">
                <h3>Support Resources</h3>
                <ul>
                    <li>Plugin Documentation (README.md)</li>
                    <li>API Reference Guide</li>
                    <li>Shortcode Examples</li>
                    <li>JavaScript API Documentation</li>
                    <li>Webhook Implementation Guide</li>
                    <li>Troubleshooting Guide</li>
                    <li>Performance Optimization Tips</li>
                    <li>Security Best Practices</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // API Testing functionality
    $('#test-weather-api').on('click', function() {
        testApi('weather/current', {location: 'Hanoi'});
    });
    
    $('#test-air-quality-api').on('click', function() {
        testApi('air-quality/current', {location: 'Hanoi'});
    });
    
    $('#test-monitor-api').on('click', function() {
        testApi('monitor/status', {});
    });
    
    function testApi(endpoint, params) {
        var resultsDiv = $('#api-test-results');
        resultsDiv.html('Testing API endpoint: ' + endpoint + '...');
        
        $.ajax({
            url: '/wp-json/environmental-integration/v1/' + endpoint,
            type: 'GET',
            data: params,
            success: function(response) {
                resultsDiv.html('<strong>Success!</strong><br><pre>' + JSON.stringify(response, null, 2) + '</pre>');
            },
            error: function(xhr, status, error) {
                resultsDiv.html('<strong>Error:</strong> ' + error + '<br>Status: ' + xhr.status + '<br>Response: ' + xhr.responseText);
            }
        });
    }
    
    // Auto-refresh widgets every 5 minutes
    setInterval(function() {
        if (typeof EIA_Frontend !== 'undefined') {
            $('.env-weather-widget').each(function() {
                var widgetId = $(this).attr('id');
                if (widgetId) {
                    EIA_Frontend.refreshWidget(widgetId);
                }
            });
            
            $('.env-air-quality-widget').each(function() {
                var widgetId = $(this).attr('id');
                if (widgetId) {
                    EIA_Frontend.refreshWidget(widgetId);
                }
            });
        }
    }, 300000); // 5 minutes
});
</script>

<?php
get_footer();
?>
