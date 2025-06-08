<?php
/**
 * Environmental Platform Object Cache - Safe Version
 * Drop-in replacement for WordPress object cache
 * Safe version that checks for existing WP_Object_Cache class
 */

if (!defined('ABSPATH')) {
    exit;
}

// Only define our object cache if WordPress hasn't already defined one
if (!class_exists('WP_Object_Cache')) {
    
    class WP_Object_Cache {
        
        private $cache = array();
        private $cache_hits = 0;
        private $cache_misses = 0;
        private $global_groups = array();
        private $blog_prefix;
        
        public function __construct() {
            $this->blog_prefix = is_multisite() ? get_current_blog_id() . ':' : '';
            
            // Set global groups
            $this->global_groups = array(
                'users',
                'userlogins',
                'usermeta',
                'user_meta',
                'useremail',
                'usernicenames',
                'site-transient',
                'site-options',
                'site-lookup',
                'blog-lookup',
                'blog-details',
                'rss',
                'global-posts',
                'blog-id-cache',
                'networks',
                'sites'
            );
        }
        
        public function add($key, $data, $group = 'default', $expire = 0) {
            if (wp_suspend_cache_addition()) {
                return false;
            }
            
            if (empty($group)) {
                $group = 'default';
            }
            
            $id = $this->blog_prefix . $key;
            
            if (is_object($data)) {
                $data = clone $data;
            }
            
            $this->cache[$group][$id] = $data;
            return true;
        }
        
        public function delete($key, $group = 'default') {
            if (empty($group)) {
                $group = 'default';
            }
            
            $id = $this->blog_prefix . $key;
            
            unset($this->cache[$group][$id]);
            return true;
        }
        
        public function flush() {
            $this->cache = array();
            return true;
        }
        
        public function get($key, $group = 'default', $force = false, &$found = null) {
            if (empty($group)) {
                $group = 'default';
            }
            
            $id = $this->blog_prefix . $key;
            
            if (isset($this->cache[$group][$id])) {
                $found = true;
                $this->cache_hits++;
                
                if (is_object($this->cache[$group][$id])) {
                    return clone $this->cache[$group][$id];
                } else {
                    return $this->cache[$group][$id];
                }
            }
            
            $found = false;
            $this->cache_misses++;
            return false;
        }
        
        public function replace($key, $data, $group = 'default', $expire = 0) {
            if (empty($group)) {
                $group = 'default';
            }
            
            $id = $this->blog_prefix . $key;
            
            if (!isset($this->cache[$group][$id])) {
                return false;
            }
            
            return $this->set($key, $data, $group, $expire);
        }
        
        public function set($key, $data, $group = 'default', $expire = 0) {
            if (empty($group)) {
                $group = 'default';
            }
            
            $id = $this->blog_prefix . $key;
            
            if (is_object($data)) {
                $data = clone $data;
            }
            
            $this->cache[$group][$id] = $data;
            return true;
        }
        
        public function stats() {
            echo "<p>";
            echo "<strong>Cache Hits:</strong> {$this->cache_hits}<br />";
            echo "<strong>Cache Misses:</strong> {$this->cache_misses}<br />";
            echo "</p>";
        }
        
        public function switch_to_blog($blog_id) {
            $this->blog_prefix = $blog_id . ':';
        }
        
        public function add_global_groups($groups) {
            $groups = (array) $groups;
            $this->global_groups = array_merge($this->global_groups, $groups);
            $this->global_groups = array_unique($this->global_groups);
        }
        
        public function add_non_persistent_groups($groups) {
            // In this basic implementation, all groups are non-persistent
        }
        
        public function reset() {
            $this->cache = array();
            $this->cache_hits = 0;
            $this->cache_misses = 0;
        }
    }
    
} else {
    // If WP_Object_Cache already exists, we'll extend the default WordPress behavior
    // by adding some environmental platform specific optimizations
    
    add_action('init', function() {
        // Add environmental platform cache groups
        wp_cache_add_global_groups(array(
            'environmental_data',
            'user_achievements', 
            'waste_classifications',
            'analytics_data'
        ));
    });
}

// Initialize global cache object
if (!isset($wp_object_cache) || !is_object($wp_object_cache)) {
    $wp_object_cache = new WP_Object_Cache();
}
?>
