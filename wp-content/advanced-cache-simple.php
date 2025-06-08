<?php
/**
 * Minimal Advanced Cache Implementation
 * This is a safer version that doesn't use WordPress functions during early loading
 */

if (!defined('ABSPATH')) {
    exit;
}

// Only define WP_CACHE if not already defined
if (!defined('WP_CACHE')) {
    define('WP_CACHE', true);
}

class SimpleEnvironmentalCache {
    
    private $cache_dir;
    private $cache_enabled = true;
    private $cache_ttl = 3600;
    
    public function __construct() {
        // Simple cache directory setup
        $this->cache_dir = dirname(__FILE__) . '/cache/environmental-platform/';
        $this->init();
    }
    
    private function init() {
        // Create cache directory if it doesn't exist
        if (!is_dir($this->cache_dir)) {
            if (!file_exists($this->cache_dir)) {
                mkdir($this->cache_dir, 0755, true);
            }
        }
    }
    
    public function should_cache() {
        // Simple caching rules - don't cache admin, login, or POST requests
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        
        // Don't cache admin areas
        if (strpos($uri, 'wp-admin') !== false || 
            strpos($uri, 'wp-login') !== false ||
            strpos($uri, 'wp-cron') !== false) {
            return false;
        }
        
        // Don't cache POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return false;
        }
        
        // Don't cache if user appears logged in (simple cookie check)
        foreach ($_COOKIE as $name => $value) {
            if (strpos($name, 'wordpress_logged_in') === 0) {
                return false;
            }
        }
        
        return true;
    }
    
    public function get_cache_key() {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Simple mobile detection
        $is_mobile = (strpos(strtolower($user_agent), 'mobile') !== false) ? 'mobile' : 'desktop';
        
        return md5($uri . $is_mobile);
    }
    
    public function get_cached_content() {
        if (!$this->should_cache()) {
            return false;
        }
        
        $cache_key = $this->get_cache_key();
        $cache_file = $this->cache_dir . $cache_key . '.cache';
        
        if (!file_exists($cache_file)) {
            return false;
        }
        
        // Check if cache has expired
        if (filemtime($cache_file) + $this->cache_ttl < time()) {
            unlink($cache_file);
            return false;
        }
        
        return file_get_contents($cache_file);
    }
    
    public function save_cache($content) {
        if (!$this->should_cache()) {
            return;
        }
        
        $cache_key = $this->get_cache_key();
        $cache_file = $this->cache_dir . $cache_key . '.cache';
        
        file_put_contents($cache_file, $content, LOCK_EX);
    }
}

// Initialize minimal cache
$env_cache = new SimpleEnvironmentalCache();

// Try to serve cached content
$cached = $env_cache->get_cached_content();
if ($cached !== false) {
    echo $cached;
    exit;
}

// If we get here, start output buffering to cache the response
if ($env_cache->should_cache()) {
    ob_start(function($content) use ($env_cache) {
        $env_cache->save_cache($content);
        return $content;
    });
}
