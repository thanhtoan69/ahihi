<?php
/**
 * Environmental Platform Advanced Caching Configuration
 * File: wp-content/advanced-cache.php
 */

if (!defined('ABSPATH')) {
    exit;
}

// WP_CACHE is already defined in wp-config.php, no need to redefine
if (!defined('WP_CACHE')) {
    define('WP_CACHE', true);
}

class EnvironmentalAdvancedCache {
    
    private $cache_dir;
    private $cache_enabled = true;
    private $cache_ttl = 3600; // 1 hour default
    private $excluded_pages = array();
    private $cache_key;
    private $start_time;
      public function __construct() {
        $this->start_time = microtime(true);
        
        // Use WP_CONTENT_DIR if available, otherwise construct path
        if (defined('WP_CONTENT_DIR')) {
            $this->cache_dir = WP_CONTENT_DIR . '/cache/environmental-platform/';
        } else {
            // Fallback: construct cache directory path
            $this->cache_dir = dirname(__FILE__) . '/cache/environmental-platform/';
        }
        
        $this->init();
    }
      private function init() {
        // Create cache directory if it doesn't exist
        if (!is_dir($this->cache_dir)) {
            // Check if WordPress functions are loaded
            if (function_exists('wp_mkdir_p')) {
                wp_mkdir_p($this->cache_dir);
            } else {
                // Fallback to PHP mkdir with recursive flag
                if (!file_exists($this->cache_dir)) {
                    mkdir($this->cache_dir, 0755, true);
                }
            }
        }
        
        // Set cache configuration only if WordPress is loaded
        if (function_exists('get_option')) {
            $this->cache_ttl = get_option('env_cache_ttl', 3600);
            $this->cache_enabled = get_option('env_cache_enabled', 1);
        } else {
            // Default values when WordPress functions aren't available
            $this->cache_ttl = 3600;
            $this->cache_enabled = 1;
        }
        
        // Excluded pages from caching
        $this->excluded_pages = array(
            'wp-admin',
            'wp-login.php',
            'wp-cron.php',
            'xmlrpc.php',
            '/cart/',
            '/checkout/',
            '/my-account/',
            '/admin-ajax.php'
        );
        
        // Generate cache key
        $this->generate_cache_key();
        
        // Start output buffering if caching is enabled
        if ($this->should_cache()) {
            ob_start(array($this, 'cache_output'));
        }
    }
      private function generate_cache_key() {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Use mobile detection fallback if wp_is_mobile() isn't available
        if (function_exists('wp_is_mobile')) {
            $is_mobile = wp_is_mobile() ? 'mobile' : 'desktop';
        } else {
            // Simple mobile detection fallback
            $is_mobile = $this->detect_mobile() ? 'mobile' : 'desktop';
        }
        
        // Use user detection fallback if is_user_logged_in() isn't available
        if (function_exists('is_user_logged_in') && function_exists('get_current_user_id')) {
            $user_id = is_user_logged_in() ? get_current_user_id() : 0;
        } else {
            $user_id = 0; // Default to anonymous user
        }
          // Include environmental data context in cache key
        $env_context = '';
        if (isset($_GET['location'])) {
            $location = function_exists('sanitize_text_field') ? 
                sanitize_text_field($_GET['location']) : 
                preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['location']);
            $env_context .= '_loc_' . $location;
        }
        if (isset($_GET['category'])) {
            $category = function_exists('sanitize_text_field') ? 
                sanitize_text_field($_GET['category']) : 
                preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['category']);
            $env_context .= '_cat_' . $category;
        }
        
        $this->cache_key = md5($uri . $is_mobile . $user_id . $env_context);
    }
      private function should_cache() {
        // Don't cache if caching is disabled
        if (!$this->cache_enabled) {
            return false;
        }
        
        // Don't cache for logged-in users (except for public pages)
        if (function_exists('is_user_logged_in')) {
            if (is_user_logged_in() && !$this->is_public_page()) {
                return false;
            }
        } else {
            // Fallback: check for WordPress auth cookies
            if ($this->has_user_specific_cookies() && !$this->is_public_page()) {
                return false;
            }
        }
        
        // Don't cache POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return false;
        }
        
        // Don't cache if query parameters exist (except allowed ones)
        if (!empty($_GET) && !$this->has_allowed_query_params()) {
            return false;
        }
        
        // Check excluded pages
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        foreach ($this->excluded_pages as $excluded) {
            if (strpos($request_uri, $excluded) !== false) {
                return false;
            }
        }
        
        // Don't cache if cookies indicate user-specific content
        if ($this->has_user_specific_cookies()) {
            return false;
        }
        
        return true;
    }
    
    private function is_public_page() {
        $public_pages = array(
            '/',
            '/about/',
            '/environmental-data/',
            '/green-marketplace/',
            '/events/',
            '/blog/'
        );
        
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        
        foreach ($public_pages as $page) {
            if (strpos($request_uri, $page) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    private function has_allowed_query_params() {
        $allowed_params = array('utm_source', 'utm_medium', 'utm_campaign', 'location', 'category', 'tag');
        
        foreach ($_GET as $param => $value) {
            if (!in_array($param, $allowed_params)) {
                return false;
            }
        }
        
        return true;
    }
    
    private function has_user_specific_cookies() {
        $user_cookies = array(
            'wordpress_logged_in',
            'wp-settings',
            'environmental_user_preferences',
            'shopping_cart'
        );
        
        foreach ($user_cookies as $cookie) {
            if (isset($_COOKIE[$cookie])) {
                return true;
            }
        }
        
        return false;
    }
    
    public function get_cached_content() {
        $cache_file = $this->get_cache_file_path();
        
        if (!file_exists($cache_file)) {
            return false;
        }
        
        // Check if cache has expired
        if (filemtime($cache_file) + $this->cache_ttl < time()) {
            unlink($cache_file);
            return false;
        }
        
        $cached_data = file_get_contents($cache_file);
        
        if ($cached_data === false) {
            return false;
        }
        
        $data = json_decode($cached_data, true);
        
        if (!$data || !isset($data['content'])) {
            return false;
        }
        
        // Add cache hit header
        header('X-Environmental-Cache: HIT');
        header('X-Cache-Created: ' . date('Y-m-d H:i:s', filemtime($cache_file)));
        
        // Set appropriate headers
        if (isset($data['headers'])) {
            foreach ($data['headers'] as $header) {
                header($header);
            }
        }
        
        return $data['content'];
    }
    
    public function cache_output($content) {
        // Don't cache if content is empty or contains errors
        if (empty($content) || strpos($content, '<title>Error</title>') !== false) {
            return $content;
        }
        
        // Don't cache if response contains cache-control headers preventing caching
        $headers = headers_list();
        foreach ($headers as $header) {
            if (stripos($header, 'cache-control') !== false && 
                (stripos($header, 'no-cache') !== false || stripos($header, 'no-store') !== false)) {
                return $content;
            }
        }
        
        // Add cache generation timestamp
        $cache_info = sprintf(
            '<!-- Environmental Platform Cache: Generated on %s, Load time: %.3fs -->',
            date('Y-m-d H:i:s'),
            microtime(true) - $this->start_time
        );
        
        $content = str_replace('</body>', $cache_info . "\n</body>", $content);
        
        // Save to cache
        $this->save_to_cache($content, $headers);
        
        // Add cache miss header
        header('X-Environmental-Cache: MISS');
        
        return $content;
    }
    
    private function save_to_cache($content, $headers) {
        $cache_file = $this->get_cache_file_path();
        $cache_dir = dirname($cache_file);
        
        if (!is_dir($cache_dir)) {
            wp_mkdir_p($cache_dir);
        }
        
        $cache_data = array(
            'content' => $content,
            'headers' => $headers,
            'generated' => time(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'mobile' => wp_is_mobile()
        );
        
        file_put_contents($cache_file, json_encode($cache_data), LOCK_EX);
        
        // Set file permissions
        chmod($cache_file, 0644);
        
        // Log cache creation for monitoring
        $this->log_cache_event('created', $cache_file);
    }
    
    private function get_cache_file_path() {
        $subdir = substr($this->cache_key, 0, 2);
        return $this->cache_dir . $subdir . '/' . $this->cache_key . '.json';
    }
    
    public function clear_cache($pattern = '*') {
        $cache_files = glob($this->cache_dir . '*/' . $pattern . '.json');
        
        $cleared = 0;
        foreach ($cache_files as $file) {
            if (unlink($file)) {
                $cleared++;
            }
        }
        
        // Also clear empty directories
        $this->cleanup_empty_directories();
        
        $this->log_cache_event('cleared', null, array('files_cleared' => $cleared));
        
        return $cleared;
    }
    
    private function cleanup_empty_directories() {
        $directories = glob($this->cache_dir . '*', GLOB_ONLYDIR);
        
        foreach ($directories as $dir) {
            if ($this->is_directory_empty($dir)) {
                rmdir($dir);
            }
        }
    }
    
    private function is_directory_empty($dir) {
        $files = scandir($dir);
        return count($files) <= 2; // Only . and ..
    }
    
    public function get_cache_stats() {
        $stats = array(
            'total_files' => 0,
            'total_size' => 0,
            'oldest_cache' => null,
            'newest_cache' => null,
            'cache_enabled' => $this->cache_enabled,
            'cache_ttl' => $this->cache_ttl
        );
        
        $cache_files = glob($this->cache_dir . '*/*.json');
        
        foreach ($cache_files as $file) {
            $stats['total_files']++;
            $stats['total_size'] += filesize($file);
            
            $mtime = filemtime($file);
            
            if ($stats['oldest_cache'] === null || $mtime < $stats['oldest_cache']) {
                $stats['oldest_cache'] = $mtime;
            }
            
            if ($stats['newest_cache'] === null || $mtime > $stats['newest_cache']) {
                $stats['newest_cache'] = $mtime;
            }
        }
        
        $stats['total_size_human'] = size_format($stats['total_size']);
        $stats['oldest_cache_human'] = $stats['oldest_cache'] ? date('Y-m-d H:i:s', $stats['oldest_cache']) : 'N/A';
        $stats['newest_cache_human'] = $stats['newest_cache'] ? date('Y-m-d H:i:s', $stats['newest_cache']) : 'N/A';
        
        return $stats;
    }
    
    private function log_cache_event($action, $file = null, $data = array()) {
        if (!defined('ENV_CACHE_LOGGING') || !ENV_CACHE_LOGGING) {
            return;
        }
          $log_entry = array(
            'timestamp' => function_exists('current_time') ? current_time('mysql') : date('Y-m-d H:i:s'),
            'action' => $action,
            'file' => $file,
            'cache_key' => $this->cache_key,
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'data' => $data
        );
        
        $log_file = (defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : dirname(__FILE__)) . '/cache/environmental-cache.log';
        file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    public function preload_cache($urls = array()) {
        if (empty($urls)) {
            // Get popular pages to preload
            $urls = $this->get_popular_pages();
        }
        
        foreach ($urls as $url) {
            $this->preload_url($url);
        }
    }
    
    private function get_popular_pages() {
        // Get most visited pages from analytics
        global $wpdb;
        
        $popular_pages = $wpdb->get_col("
            SELECT post_name 
            FROM {$wpdb->posts} 
            WHERE post_status = 'publish' 
            AND post_type IN ('page', 'post', 'product', 'event') 
            ORDER BY comment_count DESC 
            LIMIT 50
        ");
        
        $urls = array();
        foreach ($popular_pages as $page) {
            $urls[] = home_url('/' . $page);
        }
        
        return $urls;
    }
      private function preload_url($url) {
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'user-agent' => 'Environmental Platform Cache Preloader'
        ));
        
        if (!is_wp_error($response)) {
            $this->log_cache_event('preloaded', null, array('url' => $url));
        }
    }
    
    /**
     * Simple mobile detection fallback when wp_is_mobile() is not available
     */
    private function detect_mobile() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $mobile_keywords = array(
            'mobile', 'android', 'iphone', 'ipod', 'ipad', 'windows phone',
            'blackberry', 'webos', 'opera mini', 'opera mobi', 'nokia'
        );
        
        foreach ($mobile_keywords as $keyword) {
            if (stripos($user_agent, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
}

// Initialize advanced cache
$environmental_cache = new EnvironmentalAdvancedCache();

// Serve cached content if available
if ($environmental_cache->should_cache()) {
    $cached_content = $environmental_cache->get_cached_content();
    if ($cached_content !== false) {
        echo $cached_content;
        exit;
    }
}
