<?php
/**
 * Cache Manager Class
 * 
 * Handles API response caching for improved performance
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Mobile_API_Cache_Manager {
    
    private $cache_group = 'environmental_mobile_api';
    private $settings;
    private $default_ttl;
    
    public function __construct() {
        $this->settings = get_option('environmental_mobile_api_settings', array());
        $this->default_ttl = isset($this->settings['cache_ttl']) ? $this->settings['cache_ttl'] : 300; // 5 minutes
        
        add_filter('rest_pre_dispatch', array($this, 'maybe_serve_cached_response'), 5, 3);
        add_filter('rest_post_dispatch', array($this, 'maybe_cache_response'), 10, 3);
    }
    
    /**
     * Maybe serve cached response
     */
    public function maybe_serve_cached_response($result, $server, $request) {
        // Only cache GET requests from our API
        if ($request->get_method() !== 'GET' || 
            strpos($request->get_route(), '/environmental-mobile-api/') === false) {
            return $result;
        }
        
        // Skip caching for certain endpoints
        if ($this->should_skip_cache($request)) {
            return $result;
        }
        
        $cache_key = $this->generate_cache_key($request);
        $cached_response = $this->get_cached_response($cache_key);
        
        if ($cached_response !== false) {
            // Add cache headers
            $response = rest_ensure_response($cached_response);
            $response->header('X-Cache', 'HIT');
            $response->header('X-Cache-Key', $cache_key);
            
            return $response;
        }
        
        return $result;
    }
    
    /**
     * Maybe cache response
     */
    public function maybe_cache_response($response, $server, $request) {
        // Only cache successful GET requests from our API
        if ($request->get_method() !== 'GET' || 
            strpos($request->get_route(), '/environmental-mobile-api/') === false ||
            $response->get_status() !== 200) {
            return $response;
        }
        
        // Skip caching for certain endpoints
        if ($this->should_skip_cache($request)) {
            return $response;
        }
        
        $cache_key = $this->generate_cache_key($request);
        $ttl = $this->get_cache_ttl($request);
        
        $this->set_cached_response($cache_key, $response->get_data(), $ttl);
        
        // Add cache headers
        $response->header('X-Cache', 'MISS');
        $response->header('X-Cache-Key', $cache_key);
        $response->header('X-Cache-TTL', $ttl);
        
        return $response;
    }
    
    /**
     * Generate cache key for request
     */
    private function generate_cache_key($request) {
        $key_parts = array(
            'route' => $request->get_route(),
            'params' => $request->get_query_params(),
            'user' => get_current_user_id(),
            'version' => ENVIRONMENTAL_MOBILE_API_VERSION
        );
        
        // Remove sensitive parameters
        unset($key_parts['params']['_wpnonce']);
        unset($key_parts['params']['token']);
        
        // Sort parameters for consistent keys
        if (isset($key_parts['params'])) {
            ksort($key_parts['params']);
        }
        
        return md5(serialize($key_parts));
    }
    
    /**
     * Check if request should skip cache
     */
    private function should_skip_cache($request) {
        $skip_endpoints = array(
            '/auth/',
            '/logs',
            '/status',
            '/webhook',
            '/upload'
        );
        
        $route = $request->get_route();
        
        foreach ($skip_endpoints as $endpoint) {
            if (strpos($route, $endpoint) !== false) {
                return true;
            }
        }
        
        // Skip if no-cache header is present
        $headers = $request->get_headers();
        if (isset($headers['cache_control']) && 
            strpos(strtolower($headers['cache_control'][0]), 'no-cache') !== false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get cache TTL for request
     */
    private function get_cache_ttl($request) {
        $route = $request->get_route();
        
        // Custom TTL for different endpoints
        $ttl_rules = array(
            '/environmental/data' => 900,    // 15 minutes
            '/content/posts' => 600,         // 10 minutes  
            '/users/profile' => 300,         // 5 minutes
            '/analytics' => 1800,            // 30 minutes
            '/notifications' => 60           // 1 minute
        );
        
        foreach ($ttl_rules as $pattern => $ttl) {
            if (strpos($route, $pattern) !== false) {
                return $ttl;
            }
        }
        
        return $this->default_ttl;
    }
    
    /**
     * Get cached response
     */
    private function get_cached_response($cache_key) {
        // Try object cache first
        $cached = wp_cache_get($cache_key, $this->cache_group);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Try transient cache
        $cached = get_transient($this->cache_group . '_' . $cache_key);
        
        if ($cached !== false) {
            // Store back in object cache for faster access
            wp_cache_set($cache_key, $cached, $this->cache_group, 300);
            return $cached;
        }
        
        // Try file cache
        return $this->get_file_cache($cache_key);
    }
    
    /**
     * Set cached response
     */
    private function set_cached_response($cache_key, $data, $ttl) {
        // Store in object cache
        wp_cache_set($cache_key, $data, $this->cache_group, $ttl);
        
        // Store in transient cache
        set_transient($this->cache_group . '_' . $cache_key, $data, $ttl);
        
        // Store in file cache
        $this->set_file_cache($cache_key, $data, $ttl);
    }
    
    /**
     * Get file cache
     */
    private function get_file_cache($cache_key) {
        $cache_file = $this->get_cache_file_path($cache_key);
        
        if (!file_exists($cache_file)) {
            return false;
        }
        
        $cache_data = json_decode(file_get_contents($cache_file), true);
        
        if (!$cache_data || !isset($cache_data['expires']) || $cache_data['expires'] < time()) {
            unlink($cache_file);
            return false;
        }
        
        return $cache_data['data'];
    }
    
    /**
     * Set file cache
     */
    private function set_file_cache($cache_key, $data, $ttl) {
        $cache_file = $this->get_cache_file_path($cache_key);
        $cache_dir = dirname($cache_file);
        
        if (!file_exists($cache_dir)) {
            wp_mkdir_p($cache_dir);
        }
        
        $cache_data = array(
            'data' => $data,
            'expires' => time() + $ttl,
            'created' => time()
        );
        
        file_put_contents($cache_file, json_encode($cache_data));
    }
    
    /**
     * Get cache file path
     */
    private function get_cache_file_path($cache_key) {
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/environmental-mobile-api/cache';
        
        return $cache_dir . '/' . substr($cache_key, 0, 2) . '/' . $cache_key . '.json';
    }
    
    /**
     * Clear cache
     */
    public function clear_cache($pattern = null) {
        // Clear object cache
        wp_cache_flush();
        
        // Clear transient cache
        if ($pattern) {
            $this->clear_transients_by_pattern($pattern);
        } else {
            $this->clear_all_transients();
        }
        
        // Clear file cache
        $this->clear_file_cache($pattern);
        
        return true;
    }
    
    /**
     * Clear transients by pattern
     */
    private function clear_transients_by_pattern($pattern) {
        global $wpdb;
        
        $transients = $wpdb->get_results($wpdb->prepare(
            "SELECT option_name FROM {$wpdb->options} 
             WHERE option_name LIKE %s",
            '_transient_' . $this->cache_group . '_%' . $pattern . '%'
        ));
        
        foreach ($transients as $transient) {
            $key = str_replace('_transient_', '', $transient->option_name);
            delete_transient($key);
        }
    }
    
    /**
     * Clear all transients
     */
    private function clear_all_transients() {
        global $wpdb;
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE %s OR option_name LIKE %s",
            '_transient_' . $this->cache_group . '_%',
            '_transient_timeout_' . $this->cache_group . '_%'
        ));
    }
    
    /**
     * Clear file cache
     */
    private function clear_file_cache($pattern = null) {
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/environmental-mobile-api/cache';
        
        if (!file_exists($cache_dir)) {
            return;
        }
        
        if ($pattern) {
            $this->clear_file_cache_by_pattern($cache_dir, $pattern);
        } else {
            $this->clear_all_file_cache($cache_dir);
        }
    }
    
    /**
     * Clear file cache by pattern
     */
    private function clear_file_cache_by_pattern($cache_dir, $pattern) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($cache_dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'json') {
                if (strpos($file->getFilename(), $pattern) !== false) {
                    unlink($file->getPathname());
                }
            }
        }
    }
    
    /**
     * Clear all file cache
     */
    private function clear_all_file_cache($cache_dir) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($cache_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
    }
    
    /**
     * Get cache statistics
     */
    public function get_cache_stats() {
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/environmental-mobile-api/cache';
        
        $stats = array(
            'total_files' => 0,
            'total_size' => 0,
            'expired_files' => 0,
            'hit_rate' => 0,
            'oldest_file' => null,
            'newest_file' => null
        );
        
        if (!file_exists($cache_dir)) {
            return $stats;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($cache_dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        $oldest_time = time();
        $newest_time = 0;
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'json') {
                $stats['total_files']++;
                $stats['total_size'] += $file->getSize();
                
                $cache_data = json_decode(file_get_contents($file->getPathname()), true);
                
                if ($cache_data && isset($cache_data['expires'])) {
                    if ($cache_data['expires'] < time()) {
                        $stats['expired_files']++;
                    }
                    
                    if ($cache_data['created'] < $oldest_time) {
                        $oldest_time = $cache_data['created'];
                        $stats['oldest_file'] = date('Y-m-d H:i:s', $oldest_time);
                    }
                    
                    if ($cache_data['created'] > $newest_time) {
                        $newest_time = $cache_data['created'];
                        $stats['newest_file'] = date('Y-m-d H:i:s', $newest_time);
                    }
                }
            }
        }
        
        // Calculate hit rate from WordPress object cache if available
        if (function_exists('wp_cache_get_stats')) {
            $cache_stats = wp_cache_get_stats();
            if (isset($cache_stats['hits']) && isset($cache_stats['misses'])) {
                $total = $cache_stats['hits'] + $cache_stats['misses'];
                $stats['hit_rate'] = $total > 0 ? round(($cache_stats['hits'] / $total) * 100, 2) : 0;
            }
        }
        
        return $stats;
    }
    
    /**
     * Clean up expired cache
     */
    public function cleanup_expired_cache() {
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/environmental-mobile-api/cache';
        
        if (!file_exists($cache_dir)) {
            return 0;
        }
        
        $cleaned = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($cache_dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'json') {
                $cache_data = json_decode(file_get_contents($file->getPathname()), true);
                
                if (!$cache_data || !isset($cache_data['expires']) || $cache_data['expires'] < time()) {
                    unlink($file->getPathname());
                    $cleaned++;
                }
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Check if cache is available
     */
    public function is_available() {
        // Check if we can write to cache directory
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/environmental-mobile-api/cache';
        
        if (!file_exists($cache_dir)) {
            wp_mkdir_p($cache_dir);
        }
        
        return is_writable($cache_dir);
    }
    
    /**
     * Get cache status
     */
    public function get_status() {
        return array(
            'enabled' => true,
            'available' => $this->is_available(),
            'default_ttl' => $this->default_ttl,
            'statistics' => $this->get_cache_stats()
        );
    }
    
    /**
     * Invalidate cache by tag
     */
    public function invalidate_by_tag($tag) {
        // For now, just clear all cache - in a more advanced implementation,
        // we would store tags with cache entries
        return $this->clear_cache($tag);
    }
    
    /**
     * Warm up cache
     */
    public function warm_up_cache($endpoints = array()) {
        if (empty($endpoints)) {
            $endpoints = array(
                '/environmental-mobile-api/v1/environmental/data',
                '/environmental-mobile-api/v1/content/posts',
                '/environmental-mobile-api/v1/analytics/dashboard'
            );
        }
        
        foreach ($endpoints as $endpoint) {
            $request = new WP_REST_Request('GET', $endpoint);
            rest_do_request($request);
        }
        
        return count($endpoints);
    }
}
