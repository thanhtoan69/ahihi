<?php
/**
 * Google Maps Integration Class
 *
 * @package Environmental_Integration_APIs
 * @subpackage Google_Maps
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Environmental Google Maps Integration
 */
class Environmental_Google_Maps_Integration {

    /**
     * API key
     *
     * @var string
     */
    private $api_key;

    /**
     * Base API URL
     *
     * @var string
     */
    private $base_url = 'https://maps.googleapis.com/maps/api';

    /**
     * Constructor
     */
    public function __construct() {
        $this->api_key = get_option('eia_google_maps_api_key', '');
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_maps_api'));
        add_shortcode('env_google_map', array($this, 'render_map_shortcode'));
        add_shortcode('env_location_picker', array($this, 'render_location_picker'));
    }

    /**
     * Enqueue Google Maps API
     */
    public function enqueue_maps_api() {
        if (!empty($this->api_key)) {
            wp_enqueue_script(
                'google-maps-api',
                "https://maps.googleapis.com/maps/api/js?key={$this->api_key}&libraries=places,geometry&callback=initGoogleMaps",
                array(),
                null,
                true
            );
        }
    }

    /**
     * Geocode an address
     *
     * @param string $address
     * @return array
     */
    public function geocode_address($address) {
        if (empty($this->api_key) || empty($address)) {
            return array('success' => false, 'error' => 'Missing API key or address');
        }

        $cache_key = 'geocode_' . md5($address);
        $cached_result = EIA()->get_cache($cache_key);
        
        if ($cached_result !== null) {
            return $cached_result;
        }

        $url = $this->base_url . '/geocode/json';
        $params = array(
            'address' => urlencode($address),
            'key' => $this->api_key
        );

        $request_url = $url . '?' . http_build_query($params);
        $start_time = microtime(true);

        $response = wp_remote_get($request_url, array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => 'Environmental Platform/1.0'
            )
        ));

        $response_time = microtime(true) - $start_time;
        $response_code = wp_remote_retrieve_response_code($response);

        if (is_wp_error($response)) {
            $error = $response->get_error_message();
            EIA()->log_api_request(0, $request_url, 'GET', $params, 0, null, $response_time, $error);
            return array('success' => false, 'error' => $error);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        EIA()->log_api_request(0, $request_url, 'GET', $params, $response_code, $data, $response_time);

        if ($data['status'] === 'OK' && !empty($data['results'])) {
            $result = array(
                'success' => true,
                'data' => array(
                    'formatted_address' => $data['results'][0]['formatted_address'],
                    'latitude' => $data['results'][0]['geometry']['location']['lat'],
                    'longitude' => $data['results'][0]['geometry']['location']['lng'],
                    'place_id' => $data['results'][0]['place_id'],
                    'components' => $this->parse_address_components($data['results'][0]['address_components'])
                )
            );

            // Cache for 1 hour
            EIA()->set_cache($cache_key, $result, 3600);
            return $result;
        }

        return array(
            'success' => false,
            'error' => $data['error_message'] ?? 'Geocoding failed'
        );
    }

    /**
     * Reverse geocode coordinates
     *
     * @param float $lat
     * @param float $lng
     * @return array
     */
    public function reverse_geocode($lat, $lng) {
        if (empty($this->api_key)) {
            return array('success' => false, 'error' => 'Missing API key');
        }

        $cache_key = 'reverse_geocode_' . md5($lat . '_' . $lng);
        $cached_result = EIA()->get_cache($cache_key);
        
        if ($cached_result !== null) {
            return $cached_result;
        }

        $url = $this->base_url . '/geocode/json';
        $params = array(
            'latlng' => $lat . ',' . $lng,
            'key' => $this->api_key
        );

        $request_url = $url . '?' . http_build_query($params);
        $start_time = microtime(true);

        $response = wp_remote_get($request_url, array('timeout' => 10));
        $response_time = microtime(true) - $start_time;
        $response_code = wp_remote_retrieve_response_code($response);

        if (is_wp_error($response)) {
            $error = $response->get_error_message();
            EIA()->log_api_request(0, $request_url, 'GET', $params, 0, null, $response_time, $error);
            return array('success' => false, 'error' => $error);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        EIA()->log_api_request(0, $request_url, 'GET', $params, $response_code, $data, $response_time);

        if ($data['status'] === 'OK' && !empty($data['results'])) {
            $result = array(
                'success' => true,
                'data' => array(
                    'formatted_address' => $data['results'][0]['formatted_address'],
                    'components' => $this->parse_address_components($data['results'][0]['address_components'])
                )
            );

            // Cache for 1 hour
            EIA()->set_cache($cache_key, $result, 3600);
            return $result;
        }

        return array(
            'success' => false,
            'error' => $data['error_message'] ?? 'Reverse geocoding failed'
        );
    }

    /**
     * Get nearby places
     *
     * @param float $lat
     * @param float $lng
     * @param string $type
     * @param int $radius
     * @return array
     */
    public function get_nearby_places($lat, $lng, $type = 'environmental', $radius = 5000) {
        if (empty($this->api_key)) {
            return array('success' => false, 'error' => 'Missing API key');
        }

        $cache_key = 'nearby_places_' . md5($lat . '_' . $lng . '_' . $type . '_' . $radius);
        $cached_result = EIA()->get_cache($cache_key);
        
        if ($cached_result !== null) {
            return $cached_result;
        }

        $url = $this->base_url . '/place/nearbysearch/json';
        $params = array(
            'location' => $lat . ',' . $lng,
            'radius' => $radius,
            'type' => $this->get_place_type($type),
            'key' => $this->api_key
        );

        $request_url = $url . '?' . http_build_query($params);
        $start_time = microtime(true);

        $response = wp_remote_get($request_url, array('timeout' => 10));
        $response_time = microtime(true) - $start_time;
        $response_code = wp_remote_retrieve_response_code($response);

        if (is_wp_error($response)) {
            $error = $response->get_error_message();
            EIA()->log_api_request(0, $request_url, 'GET', $params, 0, null, $response_time, $error);
            return array('success' => false, 'error' => $error);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        EIA()->log_api_request(0, $request_url, 'GET', $params, $response_code, $data, $response_time);

        if ($data['status'] === 'OK') {
            $places = array_map(array($this, 'format_place_data'), $data['results']);
            
            $result = array(
                'success' => true,
                'data' => $places
            );

            // Cache for 30 minutes
            EIA()->set_cache($cache_key, $result, 1800);
            return $result;
        }

        return array(
            'success' => false,
            'error' => $data['error_message'] ?? 'Places search failed'
        );
    }

    /**
     * Calculate distance between two points
     *
     * @param float $lat1
     * @param float $lng1
     * @param float $lat2
     * @param float $lng2
     * @return float Distance in kilometers
     */
    public function calculate_distance($lat1, $lng1, $lat2, $lng2) {
        $earth_radius = 6371; // Earth's radius in kilometers

        $lat_diff = deg2rad($lat2 - $lat1);
        $lng_diff = deg2rad($lng2 - $lng1);

        $a = sin($lat_diff / 2) * sin($lat_diff / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lng_diff / 2) * sin($lng_diff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earth_radius * $c;
    }

    /**
     * Render map shortcode
     *
     * @param array $atts
     * @return string
     */
    public function render_map_shortcode($atts) {
        $atts = shortcode_atts(array(
            'lat' => '21.0285',
            'lng' => '105.8542',
            'zoom' => '12',
            'height' => '400px',
            'width' => '100%',
            'markers' => '',
            'type' => 'roadmap',
            'controls' => 'true'
        ), $atts, 'env_google_map');

        if (empty($this->api_key)) {
            return '<div class="env-map-error">Google Maps API key is not configured.</div>';
        }

        $map_id = 'env-map-' . uniqid();
        $markers_data = !empty($atts['markers']) ? json_decode($atts['markers'], true) : array();

        ob_start();
        ?>
        <div id="<?php echo esc_attr($map_id); ?>" class="env-google-map" 
             style="width: <?php echo esc_attr($atts['width']); ?>; height: <?php echo esc_attr($atts['height']); ?>;"></div>
        
        <script>
        function init<?php echo esc_js(str_replace('-', '_', $map_id)); ?>() {
            var mapOptions = {
                center: { lat: <?php echo floatval($atts['lat']); ?>, lng: <?php echo floatval($atts['lng']); ?> },
                zoom: <?php echo intval($atts['zoom']); ?>,
                mapTypeId: google.maps.MapTypeId.<?php echo strtoupper($atts['type']); ?>,
                disableDefaultUI: <?php echo $atts['controls'] === 'false' ? 'true' : 'false'; ?>
            };
            
            var map = new google.maps.Map(document.getElementById('<?php echo esc_js($map_id); ?>'), mapOptions);
            
            <?php if (!empty($markers_data)): ?>
            var markers = <?php echo wp_json_encode($markers_data); ?>;
            markers.forEach(function(markerData) {
                var marker = new google.maps.Marker({
                    position: { lat: parseFloat(markerData.lat), lng: parseFloat(markerData.lng) },
                    map: map,
                    title: markerData.title || '',
                    icon: markerData.icon || null
                });
                
                if (markerData.info) {
                    var infoWindow = new google.maps.InfoWindow({
                        content: markerData.info
                    });
                    
                    marker.addListener('click', function() {
                        infoWindow.open(map, marker);
                    });
                }
            });
            <?php endif; ?>
        }

        if (typeof google !== 'undefined' && google.maps) {
            init<?php echo esc_js(str_replace('-', '_', $map_id)); ?>();
        } else {
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof initGoogleMaps === 'undefined') {
                    window.initGoogleMaps = function() {
                        init<?php echo esc_js(str_replace('-', '_', $map_id)); ?>();
                    };
                } else {
                    var originalInit = window.initGoogleMaps;
                    window.initGoogleMaps = function() {
                        originalInit();
                        init<?php echo esc_js(str_replace('-', '_', $map_id)); ?>();
                    };
                }
            });
        }
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Render location picker shortcode
     *
     * @param array $atts
     * @return string
     */
    public function render_location_picker($atts) {
        $atts = shortcode_atts(array(
            'name' => 'location',
            'value' => '',
            'height' => '300px',
            'placeholder' => 'Search for a location...'
        ), $atts, 'env_location_picker');

        if (empty($this->api_key)) {
            return '<div class="env-map-error">Google Maps API key is not configured.</div>';
        }

        $picker_id = 'env-location-picker-' . uniqid();
        
        ob_start();
        ?>
        <div class="env-location-picker-container">
            <input type="text" 
                   id="<?php echo esc_attr($picker_id); ?>-search" 
                   class="env-location-search"
                   placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
                   value="<?php echo esc_attr($atts['value']); ?>" />
            
            <div id="<?php echo esc_attr($picker_id); ?>-map" 
                 class="env-location-picker-map" 
                 style="height: <?php echo esc_attr($atts['height']); ?>; margin-top: 10px;"></div>
            
            <input type="hidden" 
                   name="<?php echo esc_attr($atts['name']); ?>[address]" 
                   id="<?php echo esc_attr($picker_id); ?>-address" />
            <input type="hidden" 
                   name="<?php echo esc_attr($atts['name']); ?>[lat]" 
                   id="<?php echo esc_attr($picker_id); ?>-lat" />
            <input type="hidden" 
                   name="<?php echo esc_attr($atts['name']); ?>[lng]" 
                   id="<?php echo esc_attr($picker_id); ?>-lng" />
        </div>

        <script>
        function init<?php echo esc_js(str_replace('-', '_', $picker_id)); ?>() {
            var map = new google.maps.Map(document.getElementById('<?php echo esc_js($picker_id); ?>-map'), {
                center: { lat: 21.0285, lng: 105.8542 },
                zoom: 12
            });
            
            var marker = new google.maps.Marker({
                map: map,
                draggable: true
            });
            
            var searchBox = new google.maps.places.SearchBox(
                document.getElementById('<?php echo esc_js($picker_id); ?>-search')
            );
            
            map.controls[google.maps.ControlPosition.TOP_LEFT].push(
                document.getElementById('<?php echo esc_js($picker_id); ?>-search')
            );
            
            map.addListener('bounds_changed', function() {
                searchBox.setBounds(map.getBounds());
            });
            
            searchBox.addListener('places_changed', function() {
                var places = searchBox.getPlaces();
                if (places.length === 0) return;
                
                var place = places[0];
                if (!place.geometry) return;
                
                map.setCenter(place.geometry.location);
                map.setZoom(15);
                marker.setPosition(place.geometry.location);
                
                updateLocationInputs(place.formatted_address, 
                                   place.geometry.location.lat(), 
                                   place.geometry.location.lng());
            });
            
            marker.addListener('dragend', function() {
                var position = marker.getPosition();
                reverseGeocode(position.lat(), position.lng());
            });
            
            map.addListener('click', function(event) {
                marker.setPosition(event.latLng);
                reverseGeocode(event.latLng.lat(), event.latLng.lng());
            });
            
            function reverseGeocode(lat, lng) {
                var geocoder = new google.maps.Geocoder();
                geocoder.geocode({ location: { lat: lat, lng: lng } }, function(results, status) {
                    if (status === 'OK' && results[0]) {
                        updateLocationInputs(results[0].formatted_address, lat, lng);
                        document.getElementById('<?php echo esc_js($picker_id); ?>-search').value = results[0].formatted_address;
                    }
                });
            }
            
            function updateLocationInputs(address, lat, lng) {
                document.getElementById('<?php echo esc_js($picker_id); ?>-address').value = address;
                document.getElementById('<?php echo esc_js($picker_id); ?>-lat').value = lat;
                document.getElementById('<?php echo esc_js($picker_id); ?>-lng').value = lng;
            }
        }

        if (typeof google !== 'undefined' && google.maps) {
            init<?php echo esc_js(str_replace('-', '_', $picker_id)); ?>();
        } else {
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof initGoogleMaps === 'undefined') {
                    window.initGoogleMaps = function() {
                        init<?php echo esc_js(str_replace('-', '_', $picker_id)); ?>();
                    };
                } else {
                    var originalInit = window.initGoogleMaps;
                    window.initGoogleMaps = function() {
                        originalInit();
                        init<?php echo esc_js(str_replace('-', '_', $picker_id)); ?>();
                    };
                }
            });
        }
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Parse address components
     *
     * @param array $components
     * @return array
     */
    private function parse_address_components($components) {
        $parsed = array();
        
        foreach ($components as $component) {
            $type = $component['types'][0];
            $parsed[$type] = array(
                'long_name' => $component['long_name'],
                'short_name' => $component['short_name']
            );
        }
        
        return $parsed;
    }

    /**
     * Get place type for Google Places API
     *
     * @param string $type
     * @return string
     */
    private function get_place_type($type) {
        $type_mapping = array(
            'environmental' => 'park',
            'recycling' => 'recycling_center',
            'eco_friendly' => 'store',
            'green_spaces' => 'park',
            'waste_management' => 'local_government_office'
        );
        
        return $type_mapping[$type] ?? $type;
    }

    /**
     * Format place data
     *
     * @param array $place
     * @return array
     */
    private function format_place_data($place) {
        return array(
            'place_id' => $place['place_id'],
            'name' => $place['name'],
            'rating' => $place['rating'] ?? null,
            'user_ratings_total' => $place['user_ratings_total'] ?? 0,
            'vicinity' => $place['vicinity'] ?? '',
            'types' => $place['types'],
            'geometry' => array(
                'lat' => $place['geometry']['location']['lat'],
                'lng' => $place['geometry']['location']['lng']
            ),
            'photos' => isset($place['photos']) ? array_slice($place['photos'], 0, 3) : array(),
            'price_level' => $place['price_level'] ?? null,
            'opening_hours' => $place['opening_hours'] ?? null
        );
    }
}
