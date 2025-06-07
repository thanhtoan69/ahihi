<?php
/**
 * Geolocation Search Class
 *
 * @package Environmental_Advanced_Search
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAS_Geolocation_Search {
    
    /**
     * Distance units
     */
    const UNIT_KM = 'km';
    const UNIT_MILES = 'miles';
    
    /**
     * Default search radius in kilometers
     */
    const DEFAULT_RADIUS = 25;
    
    /**
     * Initialize geolocation search
     */
    public function __construct() {
        add_action('wp_ajax_eas_geocode_address', array($this, 'ajax_geocode_address'));
        add_action('wp_ajax_nopriv_eas_geocode_address', array($this, 'ajax_geocode_address'));
        add_action('wp_ajax_eas_get_user_location', array($this, 'ajax_get_user_location'));
        add_action('wp_ajax_nopriv_eas_get_user_location', array($this, 'ajax_get_user_location'));
        
        // Add location fields to posts
        add_action('add_meta_boxes', array($this, 'add_location_meta_boxes'));
        add_action('save_post', array($this, 'save_location_meta'));
        
        // Enqueue scripts for admin
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }
    
    /**
     * Add location-based search to query
     *
     * @param array $args Search arguments
     * @return array Modified search arguments
     */
    public function add_location_search($args = array()) {
        $location = isset($_GET['location']) ? sanitize_text_field($_GET['location']) : '';
        $radius = isset($_GET['radius']) ? absint($_GET['radius']) : self::DEFAULT_RADIUS;
        $unit = isset($_GET['unit']) ? sanitize_text_field($_GET['unit']) : self::UNIT_KM;
        
        if (empty($location)) {
            return $args;
        }
        
        // Geocode the location if it's not coordinates
        $coordinates = $this->parse_coordinates($location);
        if (!$coordinates) {
            $coordinates = $this->geocode_address($location);
        }
        
        if (!$coordinates) {
            return $args;
        }
        
        // Add geolocation meta query
        $meta_query = isset($args['meta_query']) ? $args['meta_query'] : array();
        
        // Use haversine formula for distance calculation
        $earth_radius = ($unit === self::UNIT_MILES) ? 3959 : 6371; // Earth radius in miles or km
        
        $args['meta_query'] = array_merge($meta_query, array(
            'relation' => 'AND',
            array(
                'key' => '_eas_latitude',
                'compare' => 'EXISTS'
            ),
            array(
                'key' => '_eas_longitude',
                'compare' => 'EXISTS'
            )
        ));
        
        // Add custom WHERE clause for distance calculation
        add_filter('posts_where', function($where) use ($coordinates, $radius, $earth_radius) {
            global $wpdb;
            
            $lat = $coordinates['lat'];
            $lng = $coordinates['lng'];
            
            $distance_where = $wpdb->prepare("
                AND (
                    %f * acos(
                        cos(radians(%f)) * 
                        cos(radians(CAST(lat_meta.meta_value AS DECIMAL(10,8)))) * 
                        cos(radians(CAST(lng_meta.meta_value AS DECIMAL(11,8))) - radians(%f)) + 
                        sin(radians(%f)) * 
                        sin(radians(CAST(lat_meta.meta_value AS DECIMAL(10,8))))
                    )
                ) <= %f
            ", $earth_radius, $lat, $lng, $lat, $radius);
            
            return $where . $distance_where;
        });
        
        // Add JOINs for latitude and longitude meta
        add_filter('posts_join', function($join) {
            global $wpdb;
            
            $join .= " LEFT JOIN {$wpdb->postmeta} lat_meta ON {$wpdb->posts}.ID = lat_meta.post_id AND lat_meta.meta_key = '_eas_latitude'";
            $join .= " LEFT JOIN {$wpdb->postmeta} lng_meta ON {$wpdb->posts}.ID = lng_meta.post_id AND lng_meta.meta_key = '_eas_longitude'";
            
            return $join;
        });
        
        // Add distance to SELECT for ordering
        add_filter('posts_fields', function($fields) use ($coordinates, $earth_radius) {
            global $wpdb;
            
            $lat = $coordinates['lat'];
            $lng = $coordinates['lng'];
            
            $distance_select = $wpdb->prepare("
                , (%f * acos(
                    cos(radians(%f)) * 
                    cos(radians(CAST(lat_meta.meta_value AS DECIMAL(10,8)))) * 
                    cos(radians(CAST(lng_meta.meta_value AS DECIMAL(11,8))) - radians(%f)) + 
                    sin(radians(%f)) * 
                    sin(radians(CAST(lat_meta.meta_value AS DECIMAL(10,8))))
                )) AS distance
            ", $earth_radius, $lat, $lng, $lat);
            
            return $fields . $distance_select;
        });
        
        // Order by distance
        add_filter('posts_orderby', function($orderby) {
            return 'distance ASC, ' . $orderby;
        });
        
        return $args;
    }
    
    /**
     * Parse coordinates from string
     *
     * @param string $location Location string
     * @return array|false Coordinates array or false
     */
    private function parse_coordinates($location) {
        // Check if location is in "lat,lng" format
        if (preg_match('/^(-?\d+\.?\d*),\s*(-?\d+\.?\d*)$/', $location, $matches)) {
            return array(
                'lat' => floatval($matches[1]),
                'lng' => floatval($matches[2])
            );
        }
        
        return false;
    }
    
    /**
     * Geocode address to coordinates
     *
     * @param string $address Address to geocode
     * @return array|false Coordinates array or false
     */
    public function geocode_address($address) {
        if (empty($address)) {
            return false;
        }
        
        // Check cache first
        $cache_key = 'eas_geocode_' . md5($address);
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
        
        $api_key = get_option('eas_google_maps_api_key', '');
        
        if (!empty($api_key)) {
            // Use Google Geocoding API
            $result = $this->geocode_with_google($address, $api_key);
        } else {
            // Use OpenStreetMap Nominatim (free alternative)
            $result = $this->geocode_with_nominatim($address);
        }
        
        if ($result) {
            // Cache for 24 hours
            set_transient($cache_key, $result, 24 * HOUR_IN_SECONDS);
        }
        
        return $result;
    }
    
    /**
     * Geocode with Google Maps API
     *
     * @param string $address Address
     * @param string $api_key API key
     * @return array|false
     */
    private function geocode_with_google($address, $api_key) {
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query(array(
            'address' => $address,
            'key' => $api_key
        ));
        
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($data['status'] === 'OK' && !empty($data['results'])) {
            $location = $data['results'][0]['geometry']['location'];
            return array(
                'lat' => $location['lat'],
                'lng' => $location['lng'],
                'formatted_address' => $data['results'][0]['formatted_address']
            );
        }
        
        return false;
    }
    
    /**
     * Geocode with OpenStreetMap Nominatim
     *
     * @param string $address Address
     * @return array|false
     */
    private function geocode_with_nominatim($address) {
        $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query(array(
            'q' => $address,
            'format' => 'json',
            'limit' => 1
        ));
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'User-Agent' => 'Environmental Platform WordPress Plugin'
            )
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!empty($data)) {
            return array(
                'lat' => floatval($data[0]['lat']),
                'lng' => floatval($data[0]['lon']),
                'formatted_address' => $data[0]['display_name']
            );
        }
        
        return false;
    }
    
    /**
     * Get nearby posts
     *
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @param int $radius Radius in kilometers
     * @param array $args Additional query arguments
     * @return WP_Query
     */
    public function get_nearby_posts($lat, $lng, $radius = 25, $args = array()) {
        $default_args = array(
            'post_type' => 'any',
            'post_status' => 'publish',
            'posts_per_page' => 10
        );
        
        $args = array_merge($default_args, $args);
        
        // Set location for the query
        $_GET['location'] = $lat . ',' . $lng;
        $_GET['radius'] = $radius;
        
        $args = $this->add_location_search($args);
        
        return new WP_Query($args);
    }
    
    /**
     * Add location meta boxes to posts
     */
    public function add_location_meta_boxes() {
        $post_types = get_post_types(array('public' => true), 'names');
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'eas_location',
                __('Location Information', 'environmental-advanced-search'),
                array($this, 'location_meta_box_callback'),
                $post_type,
                'normal',
                'default'
            );
        }
    }
    
    /**
     * Location meta box callback
     *
     * @param WP_Post $post Current post
     */
    public function location_meta_box_callback($post) {
        wp_nonce_field('eas_location_meta', 'eas_location_nonce');
        
        $latitude = get_post_meta($post->ID, '_eas_latitude', true);
        $longitude = get_post_meta($post->ID, '_eas_longitude', true);
        $address = get_post_meta($post->ID, '_eas_address', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="eas_address"><?php _e('Address', 'environmental-advanced-search'); ?></label></th>
                <td>
                    <input type="text" id="eas_address" name="eas_address" value="<?php echo esc_attr($address); ?>" class="regular-text" />
                    <button type="button" id="eas_geocode_btn" class="button"><?php _e('Geocode', 'environmental-advanced-search'); ?></button>
                    <p class="description"><?php _e('Enter address to automatically set coordinates', 'environmental-advanced-search'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="eas_latitude"><?php _e('Latitude', 'environmental-advanced-search'); ?></label></th>
                <td>
                    <input type="number" id="eas_latitude" name="eas_latitude" value="<?php echo esc_attr($latitude); ?>" step="any" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="eas_longitude"><?php _e('Longitude', 'environmental-advanced-search'); ?></label></th>
                <td>
                    <input type="number" id="eas_longitude" name="eas_longitude" value="<?php echo esc_attr($longitude); ?>" step="any" class="regular-text" />
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save location meta
     *
     * @param int $post_id Post ID
     */
    public function save_location_meta($post_id) {
        if (!isset($_POST['eas_location_nonce']) || !wp_verify_nonce($_POST['eas_location_nonce'], 'eas_location_meta')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $address = sanitize_text_field($_POST['eas_address']);
        $latitude = floatval($_POST['eas_latitude']);
        $longitude = floatval($_POST['eas_longitude']);
        
        update_post_meta($post_id, '_eas_address', $address);
        update_post_meta($post_id, '_eas_latitude', $latitude);
        update_post_meta($post_id, '_eas_longitude', $longitude);
    }
    
    /**
     * Enqueue admin scripts
     *
     * @param string $hook Current admin page hook
     */
    public function admin_enqueue_scripts($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }
        
        wp_enqueue_script(
            'eas-geolocation-admin',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/geolocation-admin.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_localize_script('eas-geolocation-admin', 'easGeoAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eas_geo_nonce')
        ));
    }
    
    /**
     * AJAX handler for geocoding addresses
     */
    public function ajax_geocode_address() {
        check_ajax_referer('eas_geo_nonce', 'nonce');
        
        $address = sanitize_text_field($_POST['address']);
        $result = $this->geocode_address($address);
        
        if ($result) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error(__('Unable to geocode address', 'environmental-advanced-search'));
        }
    }
    
    /**
     * AJAX handler for getting user location
     */
    public function ajax_get_user_location() {
        check_ajax_referer('eas_geo_nonce', 'nonce');
        
        $lat = floatval($_POST['lat']);
        $lng = floatval($_POST['lng']);
        
        if ($lat && $lng) {
            // Reverse geocode to get address
            $address = $this->reverse_geocode($lat, $lng);
            
            wp_send_json_success(array(
                'lat' => $lat,
                'lng' => $lng,
                'address' => $address
            ));
        } else {
            wp_send_json_error(__('Invalid coordinates', 'environmental-advanced-search'));
        }
    }
    
    /**
     * Reverse geocode coordinates to address
     *
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @return string Address
     */
    private function reverse_geocode($lat, $lng) {
        $cache_key = 'eas_reverse_geocode_' . md5($lat . ',' . $lng);
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
        
        $api_key = get_option('eas_google_maps_api_key', '');
        
        if (!empty($api_key)) {
            $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query(array(
                'latlng' => $lat . ',' . $lng,
                'key' => $api_key
            ));
        } else {
            $url = 'https://nominatim.openstreetmap.org/reverse?' . http_build_query(array(
                'lat' => $lat,
                'lon' => $lng,
                'format' => 'json'
            ));
        }
        
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            return '';
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        $address = '';
        
        if (!empty($api_key) && $data['status'] === 'OK' && !empty($data['results'])) {
            $address = $data['results'][0]['formatted_address'];
        } elseif (empty($api_key) && !empty($data['display_name'])) {
            $address = $data['display_name'];
        }
        
        if ($address) {
            set_transient($cache_key, $address, 24 * HOUR_IN_SECONDS);
        }
        
        return $address;
    }
    
    /**
     * Get location filter HTML
     *
     * @return string HTML output
     */
    public function get_location_filter_html() {
        $location = isset($_GET['location']) ? sanitize_text_field($_GET['location']) : '';
        $radius = isset($_GET['radius']) ? absint($_GET['radius']) : self::DEFAULT_RADIUS;
        $unit = isset($_GET['unit']) ? sanitize_text_field($_GET['unit']) : self::UNIT_KM;
        
        ob_start();
        ?>
        <div class="eas-location-filter">
            <h4><?php _e('Location', 'environmental-advanced-search'); ?></h4>
            
            <div class="eas-location-input">
                <input type="text" 
                       name="location" 
                       id="eas-location-input" 
                       value="<?php echo esc_attr($location); ?>" 
                       placeholder="<?php _e('Enter location or address', 'environmental-advanced-search'); ?>" />
                <button type="button" id="eas-use-my-location" class="button">
                    <?php _e('Use My Location', 'environmental-advanced-search'); ?>
                </button>
            </div>
            
            <div class="eas-radius-input">
                <label for="eas-radius"><?php _e('Within', 'environmental-advanced-search'); ?></label>
                <select name="radius" id="eas-radius">
                    <option value="5" <?php selected($radius, 5); ?>>5</option>
                    <option value="10" <?php selected($radius, 10); ?>>10</option>
                    <option value="25" <?php selected($radius, 25); ?>>25</option>
                    <option value="50" <?php selected($radius, 50); ?>>50</option>
                    <option value="100" <?php selected($radius, 100); ?>>100</option>
                </select>
                
                <select name="unit" id="eas-unit">
                    <option value="km" <?php selected($unit, 'km'); ?>><?php _e('kilometers', 'environmental-advanced-search'); ?></option>
                    <option value="miles" <?php selected($unit, 'miles'); ?>><?php _e('miles', 'environmental-advanced-search'); ?></option>
                </select>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
