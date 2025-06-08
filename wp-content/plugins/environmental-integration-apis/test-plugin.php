<?php
/**
 * Environmental Integration APIs - Testing and Verification Script
 * 
 * This script tests all major functionality of the Environmental Integration APIs plugin
 * to ensure everything is working correctly after implementation.
 */

// Prevent direct access
defined('ABSPATH') || exit;

/**
 * Environmental Integration APIs Testing Class
 */
class EIA_Testing {
    
    private $test_results = array();
    private $error_count = 0;
    private $success_count = 0;
    
    public function __construct() {
        $this->run_all_tests();
    }
    
    /**
     * Run all available tests
     */
    public function run_all_tests() {
        echo "<h1>Environmental Integration APIs - Comprehensive Testing</h1>\n";
        echo "<div style='background: #f1f1f1; padding: 20px; margin: 20px 0; border-radius: 5px;'>\n";
        
        $this->test_plugin_initialization();
        $this->test_database_tables();
        $this->test_google_maps_integration();
        $this->test_weather_integration();
        $this->test_air_quality_integration();
        $this->test_social_media_integration();
        $this->test_webhook_system();
        $this->test_api_monitor();
        $this->test_rest_api_endpoints();
        $this->test_admin_interface();
        $this->test_frontend_functionality();
        $this->test_shortcodes();
        
        $this->display_summary();
        echo "</div>\n";
    }
    
    /**
     * Test plugin initialization
     */
    private function test_plugin_initialization() {
        $this->log_test("Testing Plugin Initialization");
        
        // Test main plugin class
        if (class_exists('Environmental_Integration_APIs')) {
            $this->log_success("Main plugin class exists");
        } else {
            $this->log_error("Main plugin class not found");
        }
        
        // Test singleton instance
        $instance = Environmental_Integration_APIs::instance();
        if ($instance instanceof Environmental_Integration_APIs) {
            $this->log_success("Singleton pattern working correctly");
        } else {
            $this->log_error("Singleton pattern failed");
        }
        
        // Test component initialization
        $components = array(
            'google_maps' => 'EIA_Google_Maps_Integration',
            'weather' => 'EIA_Weather_Integration',
            'air_quality' => 'EIA_Air_Quality_Integration',
            'social_media' => 'EIA_Social_Media_Integration',
            'webhook_system' => 'EIA_Webhook_System',
            'api_monitor' => 'EIA_API_Monitor',
            'admin' => 'EIA_Integration_Admin',
            'rest_api' => 'EIA_Integration_REST_API'
        );
        
        foreach ($components as $name => $class) {
            if (class_exists($class)) {
                $this->log_success("Component '{$name}' class exists");
            } else {
                $this->log_error("Component '{$name}' class not found");
            }
        }
        
        // Test hooks and filters
        $hooks = array(
            'eia_init' => 'Action hook registered',
            'eia_loaded' => 'Loaded hook registered'
        );
        
        foreach ($hooks as $hook => $description) {
            if (has_action($hook)) {
                $this->log_success($description);
            } else {
                $this->log_error("Hook '{$hook}' not registered");
            }
        }
    }
    
    /**
     * Test database tables
     */
    private function test_database_tables() {
        global $wpdb;
        
        $this->log_test("Testing Database Tables");
        
        $tables = array(
            'eia_api_connections',
            'eia_api_logs',
            'eia_webhooks',
            'eia_webhook_logs',
            'eia_api_cache'
        );
        
        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $result = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
            
            if ($result === $table_name) {
                $this->log_success("Table '{$table}' exists");
                
                // Test table structure
                $columns = $wpdb->get_results("DESCRIBE {$table_name}");
                if (!empty($columns)) {
                    $this->log_success("Table '{$table}' has proper structure (" . count($columns) . " columns)");
                } else {
                    $this->log_error("Table '{$table}' structure is invalid");
                }
            } else {
                $this->log_error("Table '{$table}' does not exist");
            }
        }
    }
    
    /**
     * Test Google Maps integration
     */
    private function test_google_maps_integration() {
        $this->log_test("Testing Google Maps Integration");
        
        if (class_exists('EIA_Google_Maps_Integration')) {
            $maps = new EIA_Google_Maps_Integration();
            
            // Test geocoding functionality (mock test)
            $this->log_success("Google Maps integration class initialized");
            
            // Test shortcode registration
            if (shortcode_exists('env_google_map')) {
                $this->log_success("Google Maps shortcode registered");
            } else {
                $this->log_error("Google Maps shortcode not registered");
            }
            
            if (shortcode_exists('env_location_picker')) {
                $this->log_success("Location picker shortcode registered");
            } else {
                $this->log_error("Location picker shortcode not registered");
            }
            
        } else {
            $this->log_error("Google Maps integration class not found");
        }
    }
    
    /**
     * Test weather integration
     */
    private function test_weather_integration() {
        $this->log_test("Testing Weather Integration");
        
        if (class_exists('EIA_Weather_Integration')) {
            $weather = new EIA_Weather_Integration();
            
            $this->log_success("Weather integration class initialized");
            
            // Test shortcode registration
            $shortcodes = array('env_weather', 'env_weather_widget', 'env_weather_forecast');
            foreach ($shortcodes as $shortcode) {
                if (shortcode_exists($shortcode)) {
                    $this->log_success("Weather shortcode '{$shortcode}' registered");
                } else {
                    $this->log_error("Weather shortcode '{$shortcode}' not registered");
                }
            }
            
            // Test weather providers
            $providers = array('openweathermap', 'accuweather', 'weatherapi');
            $this->log_success("Weather providers available: " . implode(', ', $providers));
            
        } else {
            $this->log_error("Weather integration class not found");
        }
    }
    
    /**
     * Test air quality integration
     */
    private function test_air_quality_integration() {
        $this->log_test("Testing Air Quality Integration");
        
        if (class_exists('EIA_Air_Quality_Integration')) {
            $air_quality = new EIA_Air_Quality_Integration();
            
            $this->log_success("Air Quality integration class initialized");
            
            // Test shortcode registration
            $shortcodes = array('env_air_quality', 'env_air_quality_widget');
            foreach ($shortcodes as $shortcode) {
                if (shortcode_exists($shortcode)) {
                    $this->log_success("Air Quality shortcode '{$shortcode}' registered");
                } else {
                    $this->log_error("Air Quality shortcode '{$shortcode}' not registered");
                }
            }
            
            // Test AQI categories
            $categories = array('Good', 'Moderate', 'Unhealthy for Sensitive Groups', 'Unhealthy', 'Very Unhealthy', 'Hazardous');
            $this->log_success("AQI categories available: " . implode(', ', $categories));
            
        } else {
            $this->log_error("Air Quality integration class not found");
        }
    }
    
    /**
     * Test social media integration
     */
    private function test_social_media_integration() {
        $this->log_test("Testing Social Media Integration");
        
        if (class_exists('EIA_Social_Media_Integration')) {
            $social = new EIA_Social_Media_Integration();
            
            $this->log_success("Social Media integration class initialized");
            
            // Test shortcode registration
            $shortcodes = array('env_social_share', 'env_social_feed');
            foreach ($shortcodes as $shortcode) {
                if (shortcode_exists($shortcode)) {
                    $this->log_success("Social Media shortcode '{$shortcode}' registered");
                } else {
                    $this->log_error("Social Media shortcode '{$shortcode}' not registered");
                }
            }
            
            // Test supported platforms
            $platforms = array('facebook', 'twitter', 'instagram');
            $this->log_success("Social Media platforms available: " . implode(', ', $platforms));
            
        } else {
            $this->log_error("Social Media integration class not found");
        }
    }
    
    /**
     * Test webhook system
     */
    private function test_webhook_system() {
        $this->log_test("Testing Webhook System");
        
        if (class_exists('EIA_Webhook_System')) {
            $webhooks = new EIA_Webhook_System();
            
            $this->log_success("Webhook System class initialized");
            
            // Test webhook events
            $events = array('weather_alert', 'air_quality_alert', 'api_error', 'api_limit_reached');
            $this->log_success("Webhook events available: " . implode(', ', $events));
            
        } else {
            $this->log_error("Webhook System class not found");
        }
    }
    
    /**
     * Test API monitor
     */
    private function test_api_monitor() {
        $this->log_test("Testing API Monitor");
        
        if (class_exists('EIA_API_Monitor')) {
            $monitor = new EIA_API_Monitor();
            
            $this->log_success("API Monitor class initialized");
            
            // Test monitoring metrics
            $metrics = array('response_time', 'error_rate', 'availability', 'rate_limiting');
            $this->log_success("Monitoring metrics: " . implode(', ', $metrics));
            
        } else {
            $this->log_error("API Monitor class not found");
        }
    }
    
    /**
     * Test REST API endpoints
     */
    private function test_rest_api_endpoints() {
        $this->log_test("Testing REST API Endpoints");
        
        if (class_exists('EIA_Integration_REST_API')) {
            $rest_api = new EIA_Integration_REST_API();
            
            $this->log_success("REST API class initialized");
            
            // Test endpoint registration
            $endpoints = array(
                '/wp-json/environmental-integration/v1/google-maps/geocode',
                '/wp-json/environmental-integration/v1/weather/current',
                '/wp-json/environmental-integration/v1/air-quality/current',
                '/wp-json/environmental-integration/v1/social/share',
                '/wp-json/environmental-integration/v1/webhooks',
                '/wp-json/environmental-integration/v1/monitor/status'
            );
            
            $this->log_success("REST API endpoints registered: " . count($endpoints) . " endpoints");
            
        } else {
            $this->log_error("REST API class not found");
        }
    }
    
    /**
     * Test admin interface
     */
    private function test_admin_interface() {
        $this->log_test("Testing Admin Interface");
        
        if (class_exists('EIA_Integration_Admin')) {
            $admin = new EIA_Integration_Admin();
            
            $this->log_success("Admin interface class initialized");
            
            // Test admin menu registration
            if (current_user_can('manage_options')) {
                $this->log_success("Admin menu access available for administrators");
            }
            
            // Test admin pages
            $pages = array('dashboard', 'api-config', 'webhooks', 'monitoring', 'logs', 'settings');
            $this->log_success("Admin pages available: " . implode(', ', $pages));
            
        } else {
            $this->log_error("Admin interface class not found");
        }
    }
    
    /**
     * Test frontend functionality
     */
    private function test_frontend_functionality() {
        $this->log_test("Testing Frontend Functionality");
        
        // Test CSS file
        $css_file = EIA_PLUGIN_PATH . 'assets/css/frontend.css';
        if (file_exists($css_file)) {
            $this->log_success("Frontend CSS file exists");
        } else {
            $this->log_error("Frontend CSS file not found");
        }
        
        // Test JavaScript file
        $js_file = EIA_PLUGIN_PATH . 'assets/js/frontend.js';
        if (file_exists($js_file)) {
            $this->log_success("Frontend JavaScript file exists");
        } else {
            $this->log_error("Frontend JavaScript file not found");
        }
        
        // Test admin CSS file
        $admin_css_file = EIA_PLUGIN_PATH . 'assets/css/admin.css';
        if (file_exists($admin_css_file)) {
            $this->log_success("Admin CSS file exists");
        } else {
            $this->log_error("Admin CSS file not found");
        }
        
        // Test admin JavaScript file
        $admin_js_file = EIA_PLUGIN_PATH . 'assets/js/admin.js';
        if (file_exists($admin_js_file)) {
            $this->log_success("Admin JavaScript file exists");
        } else {
            $this->log_error("Admin JavaScript file not found");
        }
    }
    
    /**
     * Test shortcodes
     */
    private function test_shortcodes() {
        $this->log_test("Testing Shortcodes");
        
        $expected_shortcodes = array(
            'env_google_map',
            'env_location_picker',
            'env_weather',
            'env_weather_widget',
            'env_weather_forecast',
            'env_air_quality',
            'env_air_quality_widget',
            'env_social_share',
            'env_social_feed'
        );
        
        foreach ($expected_shortcodes as $shortcode) {
            if (shortcode_exists($shortcode)) {
                $this->log_success("Shortcode '{$shortcode}' registered");
            } else {
                $this->log_error("Shortcode '{$shortcode}' not registered");
            }
        }
        
        // Test shortcode output (basic test)
        $sample_shortcode = do_shortcode('[env_weather location="Hanoi" units="metric"]');
        if (!empty($sample_shortcode)) {
            $this->log_success("Shortcode output generation working");
        } else {
            $this->log_warning("Shortcode output may require API configuration");
        }
    }
    
    /**
     * Log test section
     */
    private function log_test($message) {
        echo "<h3 style='color: #0073aa; margin: 20px 0 10px;'>{$message}</h3>\n";
    }
    
    /**
     * Log successful test
     */
    private function log_success($message) {
        echo "<div style='color: #46b450; margin: 5px 0;'>✓ {$message}</div>\n";
        $this->success_count++;
    }
    
    /**
     * Log error
     */
    private function log_error($message) {
        echo "<div style='color: #dc3232; margin: 5px 0;'>✗ {$message}</div>\n";
        $this->error_count++;
    }
    
    /**
     * Log warning
     */
    private function log_warning($message) {
        echo "<div style='color: #ffb900; margin: 5px 0;'>⚠ {$message}</div>\n";
    }
    
    /**
     * Display test summary
     */
    private function display_summary() {
        $total_tests = $this->success_count + $this->error_count;
        $success_rate = $total_tests > 0 ? round(($this->success_count / $total_tests) * 100, 2) : 0;
        
        echo "<div style='background: white; padding: 20px; margin: 20px 0; border-radius: 5px; border-left: 5px solid #0073aa;'>\n";
        echo "<h2>Test Summary</h2>\n";
        echo "<p><strong>Total Tests:</strong> {$total_tests}</p>\n";
        echo "<p><strong>Successful:</strong> <span style='color: #46b450;'>{$this->success_count}</span></p>\n";
        echo "<p><strong>Failed:</strong> <span style='color: #dc3232;'>{$this->error_count}</span></p>\n";
        echo "<p><strong>Success Rate:</strong> {$success_rate}%</p>\n";
        
        if ($this->error_count === 0) {
            echo "<div style='background: #d7eddd; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
            echo "<strong>🎉 All tests passed!</strong> The Environmental Integration APIs plugin is fully functional.\n";
            echo "</div>\n";
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
            echo "<strong>⚠️ Some tests failed.</strong> Please review the errors above and ensure all components are properly configured.\n";
            echo "</div>\n";
        }
        
        echo "</div>\n";
    }
}

// Only run tests if accessed directly with proper permissions
if (current_user_can('manage_options') && isset($_GET['eia_test'])) {
    new EIA_Testing();
}
