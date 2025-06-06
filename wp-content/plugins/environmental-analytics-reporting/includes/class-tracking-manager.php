<?php
/**
 * Tracking Manager for Environmental Analytics
 * Handles event tracking and data collection
 */

if (!defined('ABSPATH')) {
    exit;
}

class ENV_Analytics_Tracking_Manager {
    
    private $wpdb;
    private $session_id;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->init_session();
    }
    
    /**
     * Initialize user session
     */
    private function init_session() {
        if (!session_id()) {
            session_start();
        }
        
        if (!isset($_SESSION['env_analytics_session_id'])) {
            $_SESSION['env_analytics_session_id'] = $this->generate_session_id();
            $this->create_user_session();
        }
        
        $this->session_id = $_SESSION['env_analytics_session_id'];
    }
    
    /**
     * Generate unique session ID
     */
    private function generate_session_id() {
        return md5(uniqid(wp_rand(), true));
    }
    
    /**
     * Create new user session
     */
    private function create_user_session() {
        $table_name = $this->wpdb->prefix . 'env_user_sessions';
        
        $session_data = array(
            'session_id' => $_SESSION['env_analytics_session_id'],
            'user_id' => get_current_user_id() ?: null,
            'start_time' => current_time('mysql'),
            'entry_page' => $_SERVER['REQUEST_URI'] ?? '',
            'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
            'traffic_source' => $this->determine_traffic_source(),
            'device_info' => json_encode($this->get_device_info()),
            'location_data' => json_encode($this->get_location_data())
        );
        
        $this->wpdb->insert($table_name, $session_data);
    }
    
    /**
     * Track generic event
     */
    public function track_event($event_type, $event_category, $event_action, $event_label = null, $event_value = null, $additional_data = array()) {
        if (!get_option('env_analytics_tracking_enabled', 1)) {
            return false;
        }
        
        $table_name = $this->wpdb->prefix . 'env_analytics_events';
        
        $event_data = array(
            'user_id' => get_current_user_id() ?: null,
            'session_id' => $this->session_id,
            'event_type' => sanitize_text_field($event_type),
            'event_category' => sanitize_text_field($event_category),
            'event_action' => sanitize_text_field($event_action),
            'event_label' => $event_label ? sanitize_text_field($event_label) : null,
            'event_value' => $event_value ? floatval($event_value) : null,
            'page_url' => $_SERVER['REQUEST_URI'] ?? '',
            'page_title' => get_the_title() ?: '',
            'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip_address' => $this->get_user_ip(),
            'browser' => $this->get_browser_info(),
            'device_type' => $this->get_device_type(),
            'operating_system' => $this->get_os_info(),
            'screen_resolution' => $additional_data['screen_resolution'] ?? null,
            'event_data' => json_encode($additional_data)
        );
        
        $result = $this->wpdb->insert($table_name, $event_data);
        
        // Track in Google Analytics if enabled
        $this->track_in_google_analytics($event_type, $event_category, $event_action, $event_label, $event_value);
        
        return $result !== false;
    }
    
    /**
     * Track forum post creation
     */
    public function track_forum_post($post_id, $post) {
        if ($post->post_type === 'forum_post' && $post->post_status === 'publish') {
            $this->track_event(
                'environmental_action',
                'forum',
                'post_created',
                $post->post_title,
                1,
                array(
                    'post_id' => $post_id,
                    'post_title' => $post->post_title,
                    'category' => get_post_meta($post_id, 'forum_category', true)
                )
            );
        }
    }
    
    /**
     * Track donation completion
     */
    public function track_donation($donation_id, $amount) {
        $this->track_event(
            'environmental_action',
            'donation',
            'donation_completed',
            "Donation #{$donation_id}",
            $amount,
            array(
                'donation_id' => $donation_id,
                'amount' => $amount,
                'currency' => 'USD'
            )
        );
        
        // Track conversion
        $this->track_conversion('donation_completed', $amount);
    }
    
    /**
     * Track item exchange completion
     */
    public function track_item_exchange($exchange_id, $item_data) {
        $this->track_event(
            'environmental_action',
            'item_exchange',
            'item_exchange_completed',
            "Exchange #{$exchange_id}",
            1,
            array(
                'exchange_id' => $exchange_id,
                'item_category' => $item_data['category'] ?? '',
                'item_condition' => $item_data['condition'] ?? '',
                'exchange_type' => $item_data['type'] ?? ''
            )
        );
        
        // Track conversion
        $this->track_conversion('item_exchange_completed', 1);
    }
    
    /**
     * Track petition signature
     */
    public function track_petition_signature($petition_id, $user_id) {
        $petition_title = get_the_title($petition_id);
        
        $this->track_event(
            'environmental_action',
            'petition',
            'petition_signed',
            $petition_title,
            1,
            array(
                'petition_id' => $petition_id,
                'petition_title' => $petition_title,
                'user_id' => $user_id
            )
        );
        
        // Track conversion
        $this->track_conversion('petition_signed', 1);
    }
    
    /**
     * Track user registration
     */
    public function track_user_registration($user_id) {
        $user = get_user_by('id', $user_id);
        
        $this->track_event(
            'user_engagement',
            'registration',
            'user_registered',
            $user->user_email,
            1,
            array(
                'user_id' => $user_id,
                'user_email' => $user->user_email,
                'registration_date' => $user->user_registered
            )
        );
        
        // Track conversion
        $this->track_conversion('user_registration', 1);
    }
    
    /**
     * Track user login
     */
    public function track_user_login($user_login, $user) {
        $this->track_event(
            'user_engagement',
            'authentication',
            'user_login',
            $user->user_email,
            null,
            array(
                'user_id' => $user->ID,
                'user_login' => $user_login,
                'user_email' => $user->user_email
            )
        );
        
        // Update session with user ID
        $this->update_session_user($user->ID);
    }
    
    /**
     * Track user logout
     */
    public function track_user_logout() {
        $this->track_event(
            'user_engagement',
            'authentication',
            'user_logout',
            null,
            null,
            array(
                'user_id' => get_current_user_id()
            )
        );
        
        // End session
        $this->end_user_session();
    }
    
    /**
     * Track page view
     */
    public function track_page_view() {
        global $post;
        
        $page_type = 'other';
        if (is_home() || is_front_page()) {
            $page_type = 'homepage';
        } elseif (is_single()) {
            $page_type = 'post';
        } elseif (is_page()) {
            $page_type = 'page';
        } elseif (is_category() || is_tag() || is_taxonomy()) {
            $page_type = 'archive';
        }
        
        $this->track_event(
            'page_view',
            $page_type,
            'page_viewed',
            get_the_title($post->ID ?? 0),
            null,
            array(
                'post_id' => $post->ID ?? 0,
                'post_type' => $post->post_type ?? '',
                'page_type' => $page_type
            )
        );
        
        // Update session page view count
        $this->update_session_page_views();
    }
    
    /**
     * Track achievement earned
     */
    public function track_achievement($user_id, $achievement_id, $achievement_data) {
        $this->track_event(
            'environmental_action',
            'achievement',
            'achievement_earned',
            $achievement_data['name'] ?? "Achievement #{$achievement_id}",
            $achievement_data['points'] ?? 0,
            array(
                'user_id' => $user_id,
                'achievement_id' => $achievement_id,
                'achievement_name' => $achievement_data['name'] ?? '',
                'points_earned' => $achievement_data['points'] ?? 0,
                'level' => $achievement_data['level'] ?? ''
            )
        );
    }
    
    /**
     * Track voucher redemption
     */
    public function track_voucher_redemption($voucher_id, $user_id) {
        $voucher_data = get_post_meta($voucher_id, '_voucher_data', true);
        
        $this->track_event(
            'environmental_action',
            'voucher',
            'voucher_redeemed',
            get_the_title($voucher_id),
            $voucher_data['value'] ?? 0,
            array(
                'voucher_id' => $voucher_id,
                'user_id' => $user_id,
                'voucher_type' => $voucher_data['type'] ?? '',
                'voucher_value' => $voucher_data['value'] ?? 0
            )
        );
    }
    
    /**
     * Track conversion
     */
    private function track_conversion($action, $value = null) {
        $goals_table = $this->wpdb->prefix . 'env_conversion_goals';
        $tracking_table = $this->wpdb->prefix . 'env_conversion_tracking';
        
        // Find matching goal
        $goal = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM $goals_table WHERE target_action = %s AND is_active = 1",
            $action
        ));
        
        if (!$goal) {
            return false;
        }
        
        // Track conversion
        $conversion_data = array(
            'goal_id' => $goal->id,
            'user_id' => get_current_user_id() ?: null,
            'session_id' => $this->session_id,
            'conversion_value' => $value ?: $goal->conversion_value,
            'converted_at' => current_time('mysql'),
            'attribution_data' => json_encode($this->get_attribution_data()),
            'conversion_path' => json_encode($this->get_conversion_path()),
            'time_to_conversion' => $this->calculate_time_to_conversion()
        );
        
        return $this->wpdb->insert($tracking_table, $conversion_data);
    }
    
    /**
     * Track in Google Analytics
     */
    private function track_in_google_analytics($event_type, $category, $action, $label, $value) {
        if (!get_option('env_analytics_ga_enabled', 0)) {
            return;
        }
        
        $tracking_id = get_option('env_analytics_ga_tracking_id', '');
        if (empty($tracking_id)) {
            return;
        }
        
        // Add to frontend queue for GA tracking
        if (!wp_doing_ajax()) {
            wp_add_inline_script('env-analytics-tracking', 
                "gtag('event', '{$action}', {
                    'event_category': '{$category}',
                    'event_label': '" . esc_js($label) . "',
                    'value': " . ($value ?: 0) . "
                });"
            );
        }
    }
    
    /**
     * Get user IP address
     */
    private function get_user_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                return sanitize_text_field(trim($ip));
            }
        }
        
        return '';
    }
    
    /**
     * Get browser information
     */
    private function get_browser_info() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (strpos($user_agent, 'Chrome') !== false) return 'Chrome';
        if (strpos($user_agent, 'Firefox') !== false) return 'Firefox';
        if (strpos($user_agent, 'Safari') !== false) return 'Safari';
        if (strpos($user_agent, 'Edge') !== false) return 'Edge';
        if (strpos($user_agent, 'Opera') !== false) return 'Opera';
        
        return 'Other';
    }
    
    /**
     * Get device type
     */
    private function get_device_type() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (preg_match('/Mobile|Android|iPhone|iPad/', $user_agent)) {
            return 'mobile';
        } elseif (preg_match('/Tablet|iPad/', $user_agent)) {
            return 'tablet';
        }
        
        return 'desktop';
    }
    
    /**
     * Get operating system
     */
    private function get_os_info() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (strpos($user_agent, 'Windows') !== false) return 'Windows';
        if (strpos($user_agent, 'Mac') !== false) return 'macOS';
        if (strpos($user_agent, 'Linux') !== false) return 'Linux';
        if (strpos($user_agent, 'Android') !== false) return 'Android';
        if (strpos($user_agent, 'iOS') !== false) return 'iOS';
        
        return 'Other';
    }
    
    /**
     * Get device information
     */
    private function get_device_info() {
        return array(
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'browser' => $this->get_browser_info(),
            'device_type' => $this->get_device_type(),
            'operating_system' => $this->get_os_info()
        );
    }
    
    /**
     * Get location data (basic, respecting privacy)
     */
    private function get_location_data() {
        // Basic location data without compromising privacy
        return array(
            'timezone' => wp_timezone_string(),
            'language' => get_locale()
        );
    }
    
    /**
     * Determine traffic source
     */
    private function determine_traffic_source() {
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        
        if (empty($referrer)) {
            return 'direct';
        }
        
        $referrer_domain = parse_url($referrer, PHP_URL_HOST);
        $current_domain = parse_url(home_url(), PHP_URL_HOST);
        
        if ($referrer_domain === $current_domain) {
            return 'internal';
        }
        
        // Check for common search engines
        $search_engines = array('google', 'bing', 'yahoo', 'duckduckgo');
        foreach ($search_engines as $engine) {
            if (strpos($referrer_domain, $engine) !== false) {
                return 'search';
            }
        }
        
        // Check for social media
        $social_networks = array('facebook', 'twitter', 'linkedin', 'instagram', 'youtube');
        foreach ($social_networks as $network) {
            if (strpos($referrer_domain, $network) !== false) {
                return 'social';
            }
        }
        
        return 'referral';
    }
    
    /**
     * Update session with user ID
     */
    private function update_session_user($user_id) {
        $table_name = $this->wpdb->prefix . 'env_user_sessions';
        
        $this->wpdb->update(
            $table_name,
            array('user_id' => $user_id),
            array('session_id' => $this->session_id)
        );
    }
    
    /**
     * Update session page views
     */
    private function update_session_page_views() {
        $table_name = $this->wpdb->prefix . 'env_user_sessions';
        
        $this->wpdb->query($this->wpdb->prepare(
            "UPDATE $table_name SET page_views = page_views + 1, updated_at = NOW() WHERE session_id = %s",
            $this->session_id
        ));
    }
    
    /**
     * End user session
     */
    private function end_user_session() {
        $table_name = $this->wpdb->prefix . 'env_user_sessions';
        
        $this->wpdb->update(
            $table_name,
            array(
                'end_time' => current_time('mysql'),
                'duration' => time() - strtotime($this->get_session_start_time()),
                'exit_page' => $_SERVER['REQUEST_URI'] ?? ''
            ),
            array('session_id' => $this->session_id)
        );
    }
    
    /**
     * Get session start time
     */
    private function get_session_start_time() {
        $table_name = $this->wpdb->prefix . 'env_user_sessions';
        
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT start_time FROM $table_name WHERE session_id = %s",
            $this->session_id
        ));
    }
    
    /**
     * Get attribution data
     */
    private function get_attribution_data() {
        return array(
            'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
            'traffic_source' => $this->determine_traffic_source(),
            'landing_page' => $_SERVER['REQUEST_URI'] ?? '',
            'campaign' => $_GET['utm_campaign'] ?? '',
            'medium' => $_GET['utm_medium'] ?? '',
            'source' => $_GET['utm_source'] ?? ''
        );
    }
    
    /**
     * Get conversion path
     */
    private function get_conversion_path() {
        // Get user's page history from this session
        $events_table = $this->wpdb->prefix . 'env_analytics_events';
        
        $path = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT page_url, page_title, created_at 
             FROM $events_table 
             WHERE session_id = %s AND event_type = 'page_view' 
             ORDER BY created_at ASC",
            $this->session_id
        ));
        
        return array_map(function($step) {
            return array(
                'url' => $step->page_url,
                'title' => $step->page_title,
                'timestamp' => $step->created_at
            );
        }, $path);
    }
    
    /**
     * Calculate time to conversion
     */
    private function calculate_time_to_conversion() {
        $session_start = strtotime($this->get_session_start_time());
        return time() - $session_start;
    }
}
