<?php
/**
 * Environmental Item Exchange - Geolocation Manager
 * 
 * Handles geolocation features for exchanges including location detection,
 * distance calculation, and map integration
 * 
 * @package EnvironmentalItemExchange
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EIE_Geolocation {
    
    private static $instance = null;
    private $wpdb;
    private $google_api_key;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->google_api_key = get_option('eie_google_maps_api_key', '');
        
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_geolocation_scripts'));
        add_action('wp_ajax_eie_get_location_suggestions', array($this, 'ajax_get_location_suggestions'));
        add_action('wp_ajax_eie_save_location', array($this, 'ajax_save_location'));
        add_action('wp_ajax_eie_get_nearby_exchanges', array($this, 'ajax_get_nearby_exchanges'));
        add_action('wp_ajax_nopriv_eie_get_nearby_exchanges', array($this, 'ajax_get_nearby_exchanges'));
    }
    
    /**
     * Enqueue geolocation scripts
     */
    public function enqueue_geolocation_scripts() {
        if (is_singular('item_exchange') || is_post_type_archive('item_exchange')) {
            wp_enqueue_script('eie-geolocation', EIE_PLUGIN_URL . 'assets/js/geolocation.js', array('jquery'), EIE_PLUGIN_VERSION, true);
            
            wp_localize_script('eie-geolocation', 'eie_geo', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('eie_geo_nonce'),
                'google_maps_api_key' => $this->google_api_key,
                'messages' => array(
                    'location_error' => __('Unable to get your location. Please enable location services.', 'environmental-item-exchange'),
                    'location_success' => __('Location updated successfully!', 'environmental-item-exchange'),
                    'loading' => __('Loading...', 'environmental-item-exchange'),
                )
            ));
        }
    }
    
    /**
     * Get location suggestions via AJAX
     */
    public function ajax_get_location_suggestions() {
        check_ajax_referer('eie_geo_nonce', 'nonce');
        
        $query = sanitize_text_field($_POST['query']);
        
        if (empty($query)) {
            wp_send_json_error('Query is required');
        }
        
        $suggestions = $this->get_location_suggestions($query);
        wp_send_json_success($suggestions);
    }
    
    /**
     * Save location via AJAX
     */
    public function ajax_save_location() {
        check_ajax_referer('eie_geo_nonce', 'nonce');
        
        $exchange_id = intval($_POST['exchange_id']);
        $latitude = floatval($_POST['latitude']);
        $longitude = floatval($_POST['longitude']);
        $address = sanitize_text_field($_POST['address']);
        
        if (!$exchange_id || !$latitude || !$longitude) {
            wp_send_json_error('Invalid location data');
        }
        
        $result = $this->update_exchange_location($exchange_id, $latitude, $longitude, $address);
        
        if ($result) {
            wp_send_json_success('Location saved successfully');
        } else {
            wp_send_json_error('Failed to save location');
        }
    }
    
    /**
     * Get nearby exchanges via AJAX
     */
    public function ajax_get_nearby_exchanges() {
        check_ajax_referer('eie_geo_nonce', 'nonce');
        
        $latitude = floatval($_POST['latitude']);
        $longitude = floatval($_POST['longitude']);
        $radius = intval($_POST['radius']) ?: 10;
        $limit = intval($_POST['limit']) ?: 20;
        
        if (!$latitude || !$longitude) {
            wp_send_json_error('Location coordinates required');
        }
        
        $exchanges = $this->get_nearby_exchanges($latitude, $longitude, $radius, $limit);
        wp_send_json_success($exchanges);
    }
    
    /**
     * Get location suggestions from Google Places API
     */
    public function get_location_suggestions($query) {
        if (empty($this->google_api_key)) {
            return array();
        }
        
        $url = 'https://maps.googleapis.com/maps/api/place/autocomplete/json?' . http_build_query(array(
            'input' => $query,
            'key' => $this->google_api_key,
            'types' => 'geocode',
            'language' => get_locale()
        ));
        
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            return array();
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!isset($data['predictions'])) {
            return array();
        }
        
        $suggestions = array();
        foreach ($data['predictions'] as $prediction) {
            $suggestions[] = array(
                'place_id' => $prediction['place_id'],
                'description' => $prediction['description'],
                'main_text' => $prediction['structured_formatting']['main_text'] ?? '',
                'secondary_text' => $prediction['structured_formatting']['secondary_text'] ?? ''
            );
        }
        
        return $suggestions;
    }
    
    /**
     * Get coordinates from place ID
     */
    public function get_coordinates_from_place_id($place_id) {
        if (empty($this->google_api_key) || empty($place_id)) {
            return false;
        }
        
        $url = 'https://maps.googleapis.com/maps/api/place/details/json?' . http_build_query(array(
            'place_id' => $place_id,
            'key' => $this->google_api_key,
            'fields' => 'geometry,formatted_address,address_components'
        ));
        
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!isset($data['result']['geometry']['location'])) {
            return false;
        }
        
        $location = $data['result']['geometry']['location'];
        $formatted_address = $data['result']['formatted_address'];
        $address_components = $data['result']['address_components'] ?? array();
        
        return array(
            'latitude' => $location['lat'],
            'longitude' => $location['lng'],
            'formatted_address' => $formatted_address,
            'city' => $this->extract_address_component($address_components, 'locality'),
            'state' => $this->extract_address_component($address_components, 'administrative_area_level_1'),
            'country' => $this->extract_address_component($address_components, 'country'),
            'postal_code' => $this->extract_address_component($address_components, 'postal_code')
        );
    }
    
    /**
     * Extract address component
     */
    private function extract_address_component($components, $type) {
        foreach ($components as $component) {
            if (in_array($type, $component['types'])) {
                return $component['long_name'];
            }
        }
        return '';
    }
    
    /**
     * Update exchange location
     */
    public function update_exchange_location($exchange_id, $latitude, $longitude, $address = '') {
        // Update WordPress meta
        update_post_meta($exchange_id, '_exchange_latitude', $latitude);
        update_post_meta($exchange_id, '_exchange_longitude', $longitude);
        update_post_meta($exchange_id, '_exchange_address', $address);
        
        // Geocode additional information if Google API is available
        if (!empty($this->google_api_key) && empty($address)) {
            $geocoded = $this->reverse_geocode($latitude, $longitude);
            if ($geocoded) {
                update_post_meta($exchange_id, '_exchange_address', $geocoded['formatted_address']);
                update_post_meta($exchange_id, '_exchange_city', $geocoded['city']);
                update_post_meta($exchange_id, '_exchange_state', $geocoded['state']);
                update_post_meta($exchange_id, '_exchange_country', $geocoded['country']);
            }
        }
        
        // Update enhanced location table
        $result = $this->wpdb->replace(
            $this->wpdb->prefix . 'eie_locations',
            array(
                'exchange_id' => $exchange_id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'address' => $address,
                'city' => get_post_meta($exchange_id, '_exchange_city', true),
                'state' => get_post_meta($exchange_id, '_exchange_state', true),
                'country' => get_post_meta($exchange_id, '_exchange_country', true),
                'updated_at' => current_time('mysql')
            ),
            array('%d', '%f', '%f', '%s', '%s', '%s', '%s', '%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Reverse geocode coordinates to address
     */
    public function reverse_geocode($latitude, $longitude) {
        if (empty($this->google_api_key)) {
            return false;
        }
        
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query(array(
            'latlng' => $latitude . ',' . $longitude,
            'key' => $this->google_api_key,
            'language' => get_locale()
        ));
        
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!isset($data['results'][0])) {
            return false;
        }
        
        $result = $data['results'][0];
        $address_components = $result['address_components'] ?? array();
        
        return array(
            'formatted_address' => $result['formatted_address'],
            'city' => $this->extract_address_component($address_components, 'locality'),
            'state' => $this->extract_address_component($address_components, 'administrative_area_level_1'),
            'country' => $this->extract_address_component($address_components, 'country'),
            'postal_code' => $this->extract_address_component($address_components, 'postal_code')
        );
    }
    
    /**
     * Get nearby exchanges
     */
    public function get_nearby_exchanges($latitude, $longitude, $radius_km = 10, $limit = 20) {
        // SQL query to find exchanges within radius using Haversine formula
        $sql = "SELECT p.ID, p.post_title, p.post_excerpt, p.post_author,
                pm1.meta_value as latitude,
                pm2.meta_value as longitude,
                pm3.meta_value as address,
                pm4.meta_value as exchange_type,
                pm5.meta_value as estimated_value,
                pm6.meta_value as item_condition,
                (6371 * acos(cos(radians(%f)) * cos(radians(pm1.meta_value)) * 
                cos(radians(pm2.meta_value) - radians(%f)) + 
                sin(radians(%f)) * sin(radians(pm1.meta_value)))) AS distance_km
                FROM {$this->wpdb->posts} p
                JOIN {$this->wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_exchange_latitude'
                JOIN {$this->wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_exchange_longitude'
                LEFT JOIN {$this->wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_exchange_address'
                LEFT JOIN {$this->wpdb->postmeta} pm4 ON p.ID = pm4.post_id AND pm4.meta_key = '_exchange_type'
                LEFT JOIN {$this->wpdb->postmeta} pm5 ON p.ID = pm5.post_id AND pm5.meta_key = '_estimated_value'
                LEFT JOIN {$this->wpdb->postmeta} pm6 ON p.ID = pm6.post_id AND pm6.meta_key = '_item_condition'
                WHERE p.post_type = 'item_exchange' AND p.post_status = 'publish'
                AND pm1.meta_value IS NOT NULL AND pm2.meta_value IS NOT NULL
                HAVING distance_km <= %f
                ORDER BY distance_km ASC
                LIMIT %d";
        
        $results = $this->wpdb->get_results($this->wpdb->prepare(
            $sql, 
            $latitude, $longitude, $latitude, $radius_km, $limit
        ));
        
        $exchanges = array();
        foreach ($results as $result) {
            $exchange = array(
                'id' => $result->ID,
                'title' => $result->post_title,
                'excerpt' => $result->post_excerpt,
                'author_id' => $result->post_author,
                'author_name' => get_the_author_meta('display_name', $result->post_author),
                'latitude' => floatval($result->latitude),
                'longitude' => floatval($result->longitude),
                'address' => $result->address,
                'exchange_type' => $result->exchange_type,
                'estimated_value' => floatval($result->estimated_value),
                'item_condition' => $result->item_condition,
                'distance_km' => round(floatval($result->distance_km), 2),
                'thumbnail_url' => get_the_post_thumbnail_url($result->ID, 'medium'),
                'permalink' => get_permalink($result->ID),
                'date_posted' => human_time_diff(strtotime(get_the_date('c', $result->ID)), current_time('timestamp')) . ' ' . __('ago', 'environmental-item-exchange')
            );
            
            $exchanges[] = $exchange;
        }
        
        return $exchanges;
    }
    
    /**
     * Calculate distance between two points
     */
    public function calculate_distance($lat1, $lon1, $lat2, $lon2, $unit = 'km') {
        if (($lat1 == $lat2) && ($lon1 == $lon2)) {
            return 0;
        }
        
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        
        if ($unit == 'km') {
            return $miles * 1.609344;
        } else {
            return $miles;
        }
    }
    
    /**
     * Get exchange location data
     */
    public function get_exchange_location($exchange_id) {
        $latitude = get_post_meta($exchange_id, '_exchange_latitude', true);
        $longitude = get_post_meta($exchange_id, '_exchange_longitude', true);
        
        if (!$latitude || !$longitude) {
            return false;
        }
        
        return array(
            'latitude' => floatval($latitude),
            'longitude' => floatval($longitude),
            'address' => get_post_meta($exchange_id, '_exchange_address', true),
            'city' => get_post_meta($exchange_id, '_exchange_city', true),
            'state' => get_post_meta($exchange_id, '_exchange_state', true),
            'country' => get_post_meta($exchange_id, '_exchange_country', true),
            'delivery_available' => get_post_meta($exchange_id, '_delivery_available', true),
            'pickup_available' => get_post_meta($exchange_id, '_pickup_available', true),
            'shipping_available' => get_post_meta($exchange_id, '_shipping_available', true),
            'delivery_radius' => intval(get_post_meta($exchange_id, '_delivery_radius', true))
        );
    }
    
    /**
     * Get user's current location from browser
     */
    public function get_user_location_js() {
        ?>
        <script>
        function getUserLocation(callback) {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        callback({
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude,
                            accuracy: position.coords.accuracy
                        });
                    },
                    function(error) {
                        console.log('Geolocation error:', error);
                        callback(false);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 300000 // 5 minutes
                    }
                );
            } else {
                callback(false);
            }
        }
        </script>
        <?php
    }
    
    /**
     * Render location picker for admin
     */
    public function render_location_picker($exchange_id = null) {
        $location = $exchange_id ? $this->get_exchange_location($exchange_id) : null;
        ?>
        <div class="eie-location-picker">
            <div class="location-search">
                <input type="text" id="location-search" placeholder="<?php _e('Search for a location...', 'environmental-item-exchange'); ?>" />
                <div id="location-suggestions" class="location-suggestions"></div>
            </div>
            
            <div class="location-coordinates">
                <label>
                    <?php _e('Latitude:', 'environmental-item-exchange'); ?>
                    <input type="number" name="exchange_latitude" id="exchange_latitude" 
                           value="<?php echo $location ? $location['latitude'] : ''; ?>" 
                           step="any" />
                </label>
                <label>
                    <?php _e('Longitude:', 'environmental-item-exchange'); ?>
                    <input type="number" name="exchange_longitude" id="exchange_longitude" 
                           value="<?php echo $location ? $location['longitude'] : ''; ?>" 
                           step="any" />
                </label>
            </div>
            
            <div class="location-address">
                <label>
                    <?php _e('Address:', 'environmental-item-exchange'); ?>
                    <textarea name="exchange_address" id="exchange_address" rows="3"><?php echo $location ? $location['address'] : ''; ?></textarea>
                </label>
            </div>
            
            <div class="location-options">
                <label>
                    <input type="checkbox" name="delivery_available" value="1" 
                           <?php checked($location ? $location['delivery_available'] : false); ?> />
                    <?php _e('Delivery Available', 'environmental-item-exchange'); ?>
                </label>
                
                <label>
                    <input type="checkbox" name="pickup_available" value="1" 
                           <?php checked($location ? $location['pickup_available'] : true); ?> />
                    <?php _e('Pickup Available', 'environmental-item-exchange'); ?>
                </label>
                
                <label>
                    <input type="checkbox" name="shipping_available" value="1" 
                           <?php checked($location ? $location['shipping_available'] : false); ?> />
                    <?php _e('Shipping Available', 'environmental-item-exchange'); ?>
                </label>
            </div>
            
            <?php if (!empty($this->google_api_key)) : ?>
            <div id="location-map" style="height: 300px; margin-top: 20px;"></div>
            <?php endif; ?>
            
            <button type="button" id="detect-location" class="button">
                <?php _e('Detect My Location', 'environmental-item-exchange'); ?>
            </button>
        </div>
        
        <style>
        .eie-location-picker {
            border: 1px solid #ddd;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        
        .location-search {
            position: relative;
            margin-bottom: 15px;
        }
        
        .location-search input {
            width: 100%;
            padding: 10px;
        }
        
        .location-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        
        .location-suggestion {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        
        .location-suggestion:hover {
            background: #f5f5f5;
        }
        
        .location-coordinates {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .location-options {
            display: flex;
            gap: 20px;
            margin-top: 15px;
        }
        
        .location-options label {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        </style>
        <?php
    }
    
    /**
     * Get popular exchange locations
     */
    public function get_popular_locations($limit = 10) {
        $sql = "SELECT 
                    city, 
                    state, 
                    country, 
                    COUNT(*) as exchange_count,
                    AVG(latitude) as avg_lat,
                    AVG(longitude) as avg_lng
                FROM {$this->wpdb->prefix}eie_locations l
                JOIN {$this->wpdb->posts} p ON l.exchange_id = p.ID
                WHERE p.post_status = 'publish' AND city IS NOT NULL AND city != ''
                GROUP BY city, state, country
                ORDER BY exchange_count DESC
                LIMIT %d";
        
        return $this->wpdb->get_results($this->wpdb->prepare($sql, $limit));
    }
    
    /**
     * Get location statistics
     */
    public function get_location_stats() {
        $stats = array();
        
        // Total exchanges with location
        $stats['exchanges_with_location'] = $this->wpdb->get_var(
            "SELECT COUNT(*) 
             FROM {$this->wpdb->postmeta} pm 
             JOIN {$this->wpdb->posts} p ON pm.post_id = p.ID 
             WHERE pm.meta_key = '_exchange_latitude' 
             AND p.post_type = 'item_exchange' 
             AND p.post_status = 'publish'"
        );
        
        // Total cities
        $stats['total_cities'] = $this->wpdb->get_var(
            "SELECT COUNT(DISTINCT city) 
             FROM {$this->wpdb->prefix}eie_locations 
             WHERE city IS NOT NULL AND city != ''"
        );
        
        // Average delivery radius
        $stats['avg_delivery_radius'] = $this->wpdb->get_var(
            "SELECT AVG(radius_km) 
             FROM {$this->wpdb->prefix}eie_locations 
             WHERE delivery_available = 1"
        );
        
        return $stats;
    }
}

?>
