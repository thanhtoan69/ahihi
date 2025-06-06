<?php
/**
 * Environmental Platform Redis Object Cache
 * Drop-in replacement for WordPress object cache using Redis
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Object_Cache {
    
    private $redis;
    private $cache_hits = 0;
    private $cache_misses = 0;
    private $cache_key_salt;
    private $global_groups = array();
    private $no_redis_groups = array();
    private $cache = array();
    private $redis_connected = false;
    
    public function __construct() {
        $this->cache_key_salt = defined('WP_CACHE_KEY_SALT') ? WP_CACHE_KEY_SALT : 'environmental_platform';
        
        // Default global groups
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
            'sites',
            'blog_meta'
        );
        
        // Environmental platform specific global groups
        $this->global_groups = array_merge($this->global_groups, array(
            'environmental_data',
            'user_achievements',
            'waste_classifications',
            'analytics_data',
            'performance_metrics'
        ));
        
        $this->init_redis();
    }
    
    private function init_redis() {
        if (!extension_loaded('redis')) {
            return;
        }
        
        try {
            $this->redis = new Redis();
            
            // Connect to Redis
            $host = defined('WP_REDIS_HOST') ? WP_REDIS_HOST : '127.0.0.1';
            $port = defined('WP_REDIS_PORT') ? WP_REDIS_PORT : 6379;
            $timeout = defined('WP_REDIS_TIMEOUT') ? WP_REDIS_TIMEOUT : 1;
            
            $this->redis_connected = $this->redis->connect($host, $port, $timeout);
            
            if ($this->redis_connected) {
                // Set Redis options
                $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
                $this->redis->setOption(Redis::OPT_PREFIX, $this->cache_key_salt . ':');
                
                // Select database
                $database = defined('WP_REDIS_DATABASE') ? WP_REDIS_DATABASE : 0;
                $this->redis->select($database);
                
                // Authenticate if password is set
                if (defined('WP_REDIS_PASSWORD') && WP_REDIS_PASSWORD) {
                    $this->redis->auth(WP_REDIS_PASSWORD);
                }
            }
        } catch (Exception $e) {
            $this->redis_connected = false;
            error_log('Environmental Platform Redis: Connection failed - ' . $e->getMessage());
        }
    }
    
    public function add($key, $data, $group = 'default', $expire = 0) {
        if (wp_suspend_cache_addition()) {
            return false;
        }
        
        if (empty($group)) {
            $group = 'default';
        }
        
        $id = $key;
        if ($this->multisite && !isset($this->global_groups[$group])) {
            $id = $this->blog_prefix . $key;
        }
        
        if ($this->_exists($id, $group)) {
            return false;
        }
        
        return $this->set($key, $data, $group, (int) $expire);
    }
    
    public function add_global_groups($groups) {
        $groups = (array) $groups;
        $groups = array_fill_keys($groups, true);
        $this->global_groups = array_merge($this->global_groups, $groups);
    }
    
    public function add_non_persistent_groups($groups) {
        $groups = (array) $groups;
        $groups = array_fill_keys($groups, true);
        $this->no_redis_groups = array_merge($this->no_redis_groups, $groups);
    }
    
    public function switch_to_blog($blog_id) {
        $blog_id = (int) $blog_id;
        $this->blog_prefix = $this->multisite ? $blog_id . ':' : '';
    }
    
    public function decr($key, $offset = 1, $group = 'default') {
        if (empty($group)) {
            $group = 'default';
        }
        
        if (!$this->redis_connected || isset($this->no_redis_groups[$group])) {
            return $this->_decr_internal($key, $offset, $group);
        }
        
        $redis_key = $this->build_key($key, $group);
        
        try {
            $result = $this->redis->decrBy($redis_key, $offset);
            $this->cache[$group][$key] = $result;
            return $result;
        } catch (Exception $e) {
            return $this->_decr_internal($key, $offset, $group);
        }
    }
    
    private function _decr_internal($key, $offset, $group) {
        $value = $this->get($key, $group);
        
        if (false === $value) {
            $value = 0;
        }
        
        $value = (int) $value - (int) $offset;
        
        if ($value < 0) {
            $value = 0;
        }
        
        $this->set($key, $value, $group);
        return $value;
    }
    
    public function delete($key, $group = 'default') {
        if (empty($group)) {
            $group = 'default';
        }
        
        if (!$this->redis_connected || isset($this->no_redis_groups[$group])) {
            unset($this->cache[$group][$key]);
            return true;
        }
        
        $redis_key = $this->build_key($key, $group);
        
        try {
            $result = $this->redis->del($redis_key);
            unset($this->cache[$group][$key]);
            return $result > 0;
        } catch (Exception $e) {
            unset($this->cache[$group][$key]);
            return false;
        }
    }
    
    public function flush() {
        $this->cache = array();
        
        if (!$this->redis_connected) {
            return true;
        }
        
        try {
            return $this->redis->flushDB();
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function get($key, $group = 'default', $force = false, &$found = null) {
        if (empty($group)) {
            $group = 'default';
        }
        
        if (!$force && isset($this->cache[$group][$key])) {
            $found = true;
            $this->cache_hits++;
            return $this->cache[$group][$key];
        }
        
        if (!$this->redis_connected || isset($this->no_redis_groups[$group])) {
            $found = false;
            $this->cache_misses++;
            return false;
        }
        
        $redis_key = $this->build_key($key, $group);
        
        try {
            $value = $this->redis->get($redis_key);
            
            if ($value === false) {
                $found = false;
                $this->cache_misses++;
                return false;
            }
            
            $found = true;
            $this->cache_hits++;
            $this->cache[$group][$key] = $value;
            return $value;
        } catch (Exception $e) {
            $found = false;
            $this->cache_misses++;
            return false;
        }
    }
    
    public function get_multiple($keys, $group = 'default') {
        if (empty($group)) {
            $group = 'default';
        }
        
        $values = array();
        
        if (!$this->redis_connected || isset($this->no_redis_groups[$group])) {
            foreach ($keys as $key) {
                $values[$key] = $this->get($key, $group);
            }
            return $values;
        }
        
        $redis_keys = array();
        $key_map = array();
        
        foreach ($keys as $key) {
            $redis_key = $this->build_key($key, $group);
            $redis_keys[] = $redis_key;
            $key_map[$redis_key] = $key;
        }
        
        try {
            $redis_values = $this->redis->mget($redis_keys);
            
            foreach ($redis_keys as $i => $redis_key) {
                $key = $key_map[$redis_key];
                $value = $redis_values[$i];
                
                if ($value !== false) {
                    $this->cache[$group][$key] = $value;
                    $this->cache_hits++;
                } else {
                    $this->cache_misses++;
                }
                
                $values[$key] = $value;
            }
        } catch (Exception $e) {
            foreach ($keys as $key) {
                $values[$key] = $this->get($key, $group);
            }
        }
        
        return $values;
    }
    
    public function incr($key, $offset = 1, $group = 'default') {
        if (empty($group)) {
            $group = 'default';
        }
        
        if (!$this->redis_connected || isset($this->no_redis_groups[$group])) {
            return $this->_incr_internal($key, $offset, $group);
        }
        
        $redis_key = $this->build_key($key, $group);
        
        try {
            $result = $this->redis->incrBy($redis_key, $offset);
            $this->cache[$group][$key] = $result;
            return $result;
        } catch (Exception $e) {
            return $this->_incr_internal($key, $offset, $group);
        }
    }
    
    private function _incr_internal($key, $offset, $group) {
        $value = $this->get($key, $group);
        
        if (false === $value) {
            $value = 0;
        }
        
        $value = (int) $value + (int) $offset;
        $this->set($key, $value, $group);
        return $value;
    }
    
    public function replace($key, $data, $group = 'default', $expire = 0) {
        if (empty($group)) {
            $group = 'default';
        }
        
        $id = $key;
        if ($this->multisite && !isset($this->global_groups[$group])) {
            $id = $this->blog_prefix . $key;
        }
        
        if (!$this->_exists($id, $group)) {
            return false;
        }
        
        return $this->set($key, $data, $group, (int) $expire);
    }
    
    public function reset() {
        // Clear the cache
        $this->cache = array();
        $this->cache_hits = 0;
        $this->cache_misses = 0;
    }
    
    public function set($key, $data, $group = 'default', $expire = 0) {
        if (empty($group)) {
            $group = 'default';
        }
        
        if (is_object($data)) {
            $data = clone $data;
        }
        
        $this->cache[$group][$key] = $data;
        
        if (!$this->redis_connected || isset($this->no_redis_groups[$group])) {
            return true;
        }
        
        $redis_key = $this->build_key($key, $group);
        $expire = (int) $expire;
        
        try {
            if ($expire > 0) {
                return $this->redis->setex($redis_key, $expire, $data);
            } else {
                return $this->redis->set($redis_key, $data);
            }
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function set_multiple(array $data, $group = 'default', $expire = 0) {
        if (empty($group)) {
            $group = 'default';
        }
        
        foreach ($data as $key => $value) {
            $this->set($key, $value, $group, $expire);
        }
        
        return true;
    }
    
    public function stats() {
        $stats = array(
            'hits' => $this->cache_hits,
            'misses' => $this->cache_misses,
            'ratio' => $this->cache_hits + $this->cache_misses > 0 ? 
                       ($this->cache_hits / ($this->cache_hits + $this->cache_misses)) * 100 : 0,
            'redis_connected' => $this->redis_connected
        );
        
        if ($this->redis_connected) {
            try {
                $info = $this->redis->info();
                $stats['redis_memory'] = $info['used_memory_human'] ?? 'unknown';
                $stats['redis_hits'] = $info['keyspace_hits'] ?? 0;
                $stats['redis_misses'] = $info['keyspace_misses'] ?? 0;
            } catch (Exception $e) {
                // Redis info not available
            }
        }
        
        return $stats;
    }
    
    private function _exists($key, $group) {
        return isset($this->cache[$group]) && array_key_exists($key, $this->cache[$group]);
    }
    
    private function build_key($key, $group) {
        if ($this->multisite && !isset($this->global_groups[$group])) {
            return $this->blog_prefix . $group . ':' . $key;
        } else {
            return $group . ':' . $key;
        }
    }
    
    public function __destruct() {
        if ($this->redis_connected && $this->redis) {
            try {
                $this->redis->close();
            } catch (Exception $e) {
                // Ignore connection errors on destruct
            }
        }
    }
}

// Initialize WordPress object cache
$wp_object_cache = new WP_Object_Cache();
