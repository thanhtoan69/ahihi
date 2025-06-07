<?php
/**
 * Language Detector Utility
 *
 * Detects user language from various sources
 *
 * @package Environmental_Multilang_Support
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EMS_Language_Detector {
    
    /**
     * Instance of this class
     *
     * @var EMS_Language_Detector
     */
    private static $instance = null;
    
    /**
     * Supported languages
     *
     * @var array
     */
    private $supported_languages = ['vi', 'en', 'zh', 'ja', 'ko', 'th', 'ar', 'he', 'fr', 'es'];
    
    /**
     * Language priority weights
     *
     * @var array
     */
    private $detection_weights = [
        'url_param' => 100,
        'user_meta' => 90,
        'cookie' => 80,
        'session' => 70,
        'browser' => 60,
        'ip_geo' => 50,
        'referrer' => 40,
        'default' => 10
    ];
    
    /**
     * Get instance
     *
     * @return EMS_Language_Detector
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
    private function __construct() {
        // Initialize
    }
    
    /**
     * Detect language from all available sources
     *
     * @param array $options Detection options
     * @return string
     */
    public function detect_language($options = []) {
        $defaults = [
            'enable_url_param' => true,
            'enable_user_meta' => true,
            'enable_cookie' => true,
            'enable_session' => true,
            'enable_browser' => true,
            'enable_ip_geo' => false,
            'enable_referrer' => false,
            'fallback_language' => get_option('ems_default_language', 'en')
        ];
        
        $options = wp_parse_args($options, $defaults);
        
        $detected_languages = [];
        
        // URL parameter detection
        if ($options['enable_url_param']) {
            $url_lang = $this->detect_from_url_param();
            if ($url_lang) {
                $detected_languages['url_param'] = [
                    'language' => $url_lang,
                    'confidence' => 1.0,
                    'weight' => $this->detection_weights['url_param']
                ];
            }
        }
        
        // User meta detection (for logged-in users)
        if ($options['enable_user_meta'] && is_user_logged_in()) {
            $user_lang = $this->detect_from_user_meta();
            if ($user_lang) {
                $detected_languages['user_meta'] = [
                    'language' => $user_lang,
                    'confidence' => 0.95,
                    'weight' => $this->detection_weights['user_meta']
                ];
            }
        }
        
        // Cookie detection
        if ($options['enable_cookie']) {
            $cookie_lang = $this->detect_from_cookie();
            if ($cookie_lang) {
                $detected_languages['cookie'] = [
                    'language' => $cookie_lang,
                    'confidence' => 0.9,
                    'weight' => $this->detection_weights['cookie']
                ];
            }
        }
        
        // Session detection
        if ($options['enable_session']) {
            $session_lang = $this->detect_from_session();
            if ($session_lang) {
                $detected_languages['session'] = [
                    'language' => $session_lang,
                    'confidence' => 0.85,
                    'weight' => $this->detection_weights['session']
                ];
            }
        }
        
        // Browser detection
        if ($options['enable_browser']) {
            $browser_result = $this->detect_from_browser();
            if ($browser_result) {
                $detected_languages['browser'] = [
                    'language' => $browser_result['language'],
                    'confidence' => $browser_result['confidence'],
                    'weight' => $this->detection_weights['browser']
                ];
            }
        }
        
        // IP geolocation detection
        if ($options['enable_ip_geo']) {
            $geo_result = $this->detect_from_ip_geolocation();
            if ($geo_result) {
                $detected_languages['ip_geo'] = [
                    'language' => $geo_result['language'],
                    'confidence' => $geo_result['confidence'],
                    'weight' => $this->detection_weights['ip_geo']
                ];
            }
        }
        
        // Referrer detection
        if ($options['enable_referrer']) {
            $referrer_lang = $this->detect_from_referrer();
            if ($referrer_lang) {
                $detected_languages['referrer'] = [
                    'language' => $referrer_lang,
                    'confidence' => 0.3,
                    'weight' => $this->detection_weights['referrer']
                ];
            }
        }
        
        // Choose best language based on weighted scores
        return $this->choose_best_language($detected_languages, $options['fallback_language']);
    }
    
    /**
     * Detect language from URL parameter
     *
     * @return string|false
     */
    public function detect_from_url_param() {
        if (isset($_GET['lang'])) {
            $lang = sanitize_text_field($_GET['lang']);
            return $this->is_supported_language($lang) ? $lang : false;
        }
        
        return false;
    }
    
    /**
     * Detect language from user meta
     *
     * @param int $user_id Optional user ID
     * @return string|false
     */
    public function detect_from_user_meta($user_id = null) {
        if (!is_user_logged_in() && !$user_id) {
            return false;
        }
        
        $user_id = $user_id ?: get_current_user_id();
        $user_lang = get_user_meta($user_id, 'ems_preferred_language', true);
        
        return $user_lang && $this->is_supported_language($user_lang) ? $user_lang : false;
    }
    
    /**
     * Detect language from cookie
     *
     * @return string|false
     */
    public function detect_from_cookie() {
        if (isset($_COOKIE['ems_language'])) {
            $lang = sanitize_text_field($_COOKIE['ems_language']);
            return $this->is_supported_language($lang) ? $lang : false;
        }
        
        return false;
    }
    
    /**
     * Detect language from session
     *
     * @return string|false
     */
    public function detect_from_session() {
        if (!session_id()) {
            session_start();
        }
        
        if (isset($_SESSION['ems_language'])) {
            $lang = sanitize_text_field($_SESSION['ems_language']);
            return $this->is_supported_language($lang) ? $lang : false;
        }
        
        return false;
    }
    
    /**
     * Detect language from browser Accept-Language header
     *
     * @return array|false
     */
    public function detect_from_browser() {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return false;
        }
        
        $accepted_languages = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        $languages = [];
        
        // Parse Accept-Language header
        preg_match_all('/([a-z]{1,8}(?:-[a-z]{1,8})?)\s*(?:;\s*q\s*=\s*(1(?:\.0{1,3})?|0(?:\.[0-9]{1,3})))?/i', $accepted_languages, $matches);
        
        if (!empty($matches[1])) {
            for ($i = 0; $i < count($matches[1]); $i++) {
                $lang_code = strtolower(substr($matches[1][$i], 0, 2));
                $quality = isset($matches[2][$i]) && $matches[2][$i] !== '' ? floatval($matches[2][$i]) : 1.0;
                
                if ($this->is_supported_language($lang_code)) {
                    $languages[] = [
                        'language' => $lang_code,
                        'quality' => $quality
                    ];
                }
            }
        }
        
        if (empty($languages)) {
            return false;
        }
        
        // Sort by quality (highest first)
        usort($languages, function($a, $b) {
            return $b['quality'] <=> $a['quality'];
        });
        
        return [
            'language' => $languages[0]['language'],
            'confidence' => $languages[0]['quality']
        ];
    }
    
    /**
     * Detect language from IP geolocation
     *
     * @return array|false
     */
    public function detect_from_ip_geolocation() {
        $ip = $this->get_user_ip();
        if (!$ip || $this->is_local_ip($ip)) {
            return false;
        }
        
        // Use cached result if available
        $cache_key = 'ems_geo_lang_' . md5($ip);
        $cached_result = get_transient($cache_key);
        if ($cached_result !== false) {
            return $cached_result;
        }
        
        $country_code = $this->get_country_from_ip($ip);
        if (!$country_code) {
            return false;
        }
        
        $language = $this->map_country_to_language($country_code);
        if (!$language) {
            return false;
        }
        
        $result = [
            'language' => $language,
            'confidence' => 0.6, // Lower confidence for geo-based detection
            'country' => $country_code
        ];
        
        // Cache for 1 hour
        set_transient($cache_key, $result, HOUR_IN_SECONDS);
        
        return $result;
    }
    
    /**
     * Detect language from referrer URL
     *
     * @return string|false
     */
    public function detect_from_referrer() {
        if (!isset($_SERVER['HTTP_REFERER'])) {
            return false;
        }
        
        $referrer = $_SERVER['HTTP_REFERER'];
        $parsed_url = parse_url($referrer);
        
        if (!$parsed_url || !isset($parsed_url['host'])) {
            return false;
        }
        
        // Check for language parameter in referrer URL
        if (isset($parsed_url['query'])) {
            parse_str($parsed_url['query'], $query_params);
            if (isset($query_params['lang']) && $this->is_supported_language($query_params['lang'])) {
                return $query_params['lang'];
            }
        }
        
        // Check for country-specific domains
        $host = $parsed_url['host'];
        $tld = substr($host, strrpos($host, '.') + 1);
        
        $tld_to_lang = [
            'vn' => 'vi',
            'cn' => 'zh',
            'jp' => 'ja',
            'kr' => 'ko',
            'th' => 'th',
            'sa' => 'ar',
            'il' => 'he',
            'fr' => 'fr',
            'es' => 'es'
        ];
        
        return isset($tld_to_lang[$tld]) ? $tld_to_lang[$tld] : false;
    }
    
    /**
     * Choose best language from detected languages
     *
     * @param array $detected_languages
     * @param string $fallback_language
     * @return string
     */
    private function choose_best_language($detected_languages, $fallback_language) {
        if (empty($detected_languages)) {
            return $fallback_language;
        }
        
        $best_score = 0;
        $best_language = $fallback_language;
        
        foreach ($detected_languages as $source => $data) {
            $score = $data['confidence'] * $data['weight'];
            
            if ($score > $best_score) {
                $best_score = $score;
                $best_language = $data['language'];
            }
        }
        
        return $best_language;
    }
    
    /**
     * Get user IP address
     *
     * @return string|false
     */
    private function get_user_ip() {
        $ip_headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Check if IP is local/private
     *
     * @param string $ip
     * @return bool
     */
    private function is_local_ip($ip) {
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
    
    /**
     * Get country from IP using free API
     *
     * @param string $ip
     * @return string|false
     */
    private function get_country_from_ip($ip) {
        // Use ipapi.co free API (1000 requests/day)
        $url = "http://ipapi.co/{$ip}/country/";
        
        $response = wp_remote_get($url, [
            'timeout' => 5,
            'user-agent' => 'Environmental Platform Multilang Support'
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $country_code = trim(strtolower($body));
        
        return strlen($country_code) === 2 ? $country_code : false;
    }
    
    /**
     * Map country code to language
     *
     * @param string $country_code
     * @return string|false
     */
    private function map_country_to_language($country_code) {
        $country_to_lang = [
            'vn' => 'vi',   // Vietnam
            'cn' => 'zh',   // China
            'jp' => 'ja',   // Japan
            'kr' => 'ko',   // South Korea
            'th' => 'th',   // Thailand
            'sa' => 'ar',   // Saudi Arabia
            'ae' => 'ar',   // UAE
            'eg' => 'ar',   // Egypt
            'il' => 'he',   // Israel
            'fr' => 'fr',   // France
            'es' => 'es',   // Spain
            'mx' => 'es',   // Mexico
            'ar' => 'es',   // Argentina
            'us' => 'en',   // United States
            'gb' => 'en',   // United Kingdom
            'au' => 'en',   // Australia
            'ca' => 'en',   // Canada (default to English)
        ];
        
        return isset($country_to_lang[$country_code]) ? $country_to_lang[$country_code] : false;
    }
    
    /**
     * Check if language is supported
     *
     * @param string $language
     * @return bool
     */
    private function is_supported_language($language) {
        return in_array($language, $this->supported_languages);
    }
    
    /**
     * Get detection statistics
     *
     * @return array
     */
    public function get_detection_stats() {
        $stats = get_option('ems_detection_stats', []);
        
        return wp_parse_args($stats, [
            'url_param' => 0,
            'user_meta' => 0,
            'cookie' => 0,
            'session' => 0,
            'browser' => 0,
            'ip_geo' => 0,
            'referrer' => 0,
            'default' => 0,
            'total_detections' => 0
        ]);
    }
    
    /**
     * Update detection statistics
     *
     * @param string $method
     */
    public function update_detection_stats($method) {
        $stats = $this->get_detection_stats();
        
        if (isset($stats[$method])) {
            $stats[$method]++;
        }
        
        $stats['total_detections']++;
        
        update_option('ems_detection_stats', $stats);
    }
    
    /**
     * Reset detection statistics
     */
    public function reset_detection_stats() {
        delete_option('ems_detection_stats');
    }
}
