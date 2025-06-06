<?php
/**
 * Plugin Name: Environmental Platform Performance Optimizer
 * Description: Advanced caching and performance optimization for Environmental Platform
 * Version: 1.0.0
 * Author: Environmental Platform Team
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ENV_PERF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ENV_PERF_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ENV_PERF_VERSION', '1.0.0');

class EnvironmentalPerformanceOptimizer {
    
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_optimized_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Performance hooks
        add_action('wp_head', array($this, 'add_performance_meta'));
        add_filter('script_loader_tag', array($this, 'add_defer_attribute'), 10, 2);
        add_filter('style_loader_tag', array($this, 'add_preload_attribute'), 10, 2);
        
        // Database optimization
        add_action('wp_scheduled_delete', array($this, 'cleanup_database'));
        add_action('wp_loaded', array($this, 'setup_object_cache'));
        
        // Image optimization
        add_filter('wp_get_attachment_image_attributes', array($this, 'add_lazy_loading'));
        add_filter('the_content', array($this, 'optimize_content_images'));
    }
    
    public function init() {
        // Initialize Redis cache if available
        $this->init_redis_cache();
        
        // Setup performance monitoring
        $this->setup_performance_monitoring();
        
        // Enable GZIP compression
        $this->enable_gzip_compression();
        
        // Setup CDN integration
        $this->setup_cdn_integration();
    }
    
    /**
     * Initialize Redis cache
     */
    private function init_redis_cache() {
        if (extension_loaded('redis') && !defined('WP_REDIS_DISABLED')) {
            try {
                $redis = new Redis();
                $redis->connect('127.0.0.1', 6379);
                
                // Test connection
                $redis->ping();
                
                // Enable Redis object cache
                if (!defined('WP_CACHE_KEY_SALT')) {
                    define('WP_CACHE_KEY_SALT', 'environmental_platform_' . get_site_url());
                }
                
                // Store Redis instance globally
                $GLOBALS['redis_cache'] = $redis;
                
                error_log('Environmental Platform: Redis cache initialized successfully');
            } catch (Exception $e) {
                error_log('Environmental Platform: Redis connection failed - ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Setup performance monitoring
     */
    private function setup_performance_monitoring() {
        if (!wp_next_scheduled('env_performance_monitor')) {
            wp_schedule_event(time(), 'hourly', 'env_performance_monitor');
        }
        add_action('env_performance_monitor', array($this, 'monitor_performance'));
    }
    
    /**
     * Monitor performance metrics
     */
    public function monitor_performance() {
        $metrics = array(
            'timestamp' => current_time('mysql'),
            'memory_usage' => memory_get_peak_usage(true),
            'query_count' => get_num_queries(),
            'load_time' => timer_stop(0, 3),
            'cache_hit_ratio' => $this->get_cache_hit_ratio()
        );
        
        // Store metrics in database
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'performance_metrics',
            $metrics
        );
        
        // Alert if performance is poor
        if ($metrics['load_time'] > 3.0 || $metrics['memory_usage'] > 256 * 1024 * 1024) {
            $this->send_performance_alert($metrics);
        }
    }
    
    /**
     * Enable GZIP compression
     */
    private function enable_gzip_compression() {
        if (!ob_get_level()) {
            ob_start('ob_gzhandler');
        }
        
        // Add compression headers
        add_action('send_headers', function() {
            if (!headers_sent()) {
                header('Vary: Accept-Encoding');
                if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
                    header('Content-Encoding: gzip');
                }
            }
        });
    }
    
    /**
     * Setup CDN integration
     */
    private function setup_cdn_integration() {
        $cdn_url = get_option('env_cdn_url', '');
        if (!empty($cdn_url)) {
            add_filter('wp_get_attachment_url', array($this, 'rewrite_attachment_url'));
            add_filter('stylesheet_uri', array($this, 'rewrite_asset_url'));
            add_filter('script_loader_src', array($this, 'rewrite_asset_url'));
        }
    }
    
    /**
     * Rewrite URLs for CDN
     */
    public function rewrite_attachment_url($url) {
        $cdn_url = get_option('env_cdn_url', '');
        if (!empty($cdn_url)) {
            $upload_dir = wp_upload_dir();
            $url = str_replace($upload_dir['baseurl'], $cdn_url, $url);
        }
        return $url;
    }
    
    /**
     * Rewrite asset URLs for CDN
     */
    public function rewrite_asset_url($url) {
        $cdn_url = get_option('env_cdn_url', '');
        if (!empty($cdn_url) && strpos($url, home_url()) === 0) {
            $url = str_replace(home_url(), $cdn_url, $url);
        }
        return $url;
    }
    
    /**
     * Add defer attribute to scripts
     */
    public function add_defer_attribute($tag, $handle) {
        $defer_scripts = array(
            'environmental-dashboard',
            'waste-classification',
            'social-sharing',
            'analytics-tracking'
        );
        
        if (in_array($handle, $defer_scripts) && strpos($tag, 'defer') === false) {
            $tag = str_replace(' src', ' defer src', $tag);
        }
        
        return $tag;
    }
    
    /**
     * Add preload attribute to critical CSS
     */
    public function add_preload_attribute($tag, $handle) {
        $critical_styles = array(
            'environmental-theme-style',
            'environmental-critical-css'
        );
        
        if (in_array($handle, $critical_styles)) {
            $tag = str_replace('rel=\'stylesheet\'', 'rel=\'preload\' as=\'style\' onload="this.onload=null;this.rel=\'stylesheet\'"', $tag);
        }
        
        return $tag;
    }
    
    /**
     * Add lazy loading to images
     */
    public function add_lazy_loading($attr) {
        if (!is_admin() && !wp_is_mobile()) {
            $attr['loading'] = 'lazy';
            $attr['decoding'] = 'async';
        }
        return $attr;
    }
    
    /**
     * Optimize content images
     */
    public function optimize_content_images($content) {
        // Add lazy loading to content images
        $content = preg_replace('/<img([^>]+?)src=/i', '<img$1loading="lazy" decoding="async" src=', $content);
        
        // Add responsive image attributes
        $content = preg_replace_callback(
            '/<img[^>]+>/i',
            array($this, 'add_responsive_attributes'),
            $content
        );
        
        return $content;
    }
    
    /**
     * Add responsive attributes to images
     */
    private function add_responsive_attributes($matches) {
        $img_tag = $matches[0];
        
        // Add srcset for responsive images
        if (strpos($img_tag, 'srcset') === false) {
            // Extract src attribute
            preg_match('/src="([^"]+)"/i', $img_tag, $src_matches);
            if (!empty($src_matches[1])) {
                $src = $src_matches[1];
                $attachment_id = attachment_url_to_postid($src);
                
                if ($attachment_id) {
                    $srcset = wp_get_attachment_image_srcset($attachment_id);
                    $sizes = wp_get_attachment_image_sizes($attachment_id);
                    
                    if ($srcset) {
                        $img_tag = str_replace('<img', '<img srcset="' . $srcset . '"', $img_tag);
                    }
                    if ($sizes) {
                        $img_tag = str_replace('<img', '<img sizes="' . $sizes . '"', $img_tag);
                    }
                }
            }
        }
        
        return $img_tag;
    }
    
    /**
     * Database optimization and cleanup
     */
    public function cleanup_database() {
        global $wpdb;
        
        // Clean up spam comments
        $wpdb->delete($wpdb->comments, array('comment_approved' => 'spam'));
        
        // Clean up trash posts
        $wpdb->delete($wpdb->posts, array('post_status' => 'trash'));
        
        // Clean up expired transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%' AND option_value < UNIX_TIMESTAMP()");
        
        // Optimize tables
        $tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);
        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE {$table[0]}");
        }
        
        error_log('Environmental Platform: Database optimization completed');
    }
    
    /**
     * Setup object cache
     */
    private function setup_object_cache() {
        if (isset($GLOBALS['redis_cache'])) {
            // Implement custom object cache using Redis
            wp_cache_add_global_groups(array(
                'environmental_data',
                'user_achievements',
                'waste_classifications',
                'analytics_data'
            ));
        }
    }
    
    /**
     * Get cache hit ratio
     */
    private function get_cache_hit_ratio() {
        if (isset($GLOBALS['redis_cache'])) {
            try {
                $info = $GLOBALS['redis_cache']->info();
                $hits = $info['keyspace_hits'] ?? 0;
                $misses = $info['keyspace_misses'] ?? 0;
                $total = $hits + $misses;
                
                return $total > 0 ? ($hits / $total) * 100 : 0;
            } catch (Exception $e) {
                return 0;
            }
        }
        return 0;
    }
    
    /**
     * Send performance alert
     */
    private function send_performance_alert($metrics) {
        $admin_email = get_option('admin_email');
        $subject = 'Environmental Platform - Performance Alert';
        $message = sprintf(
            "Performance issue detected:\n\nLoad Time: %s seconds\nMemory Usage: %s MB\nQuery Count: %d\nTimestamp: %s",
            $metrics['load_time'],
            round($metrics['memory_usage'] / (1024 * 1024), 2),
            $metrics['query_count'],
            $metrics['timestamp']
        );
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Enqueue optimized scripts
     */
    public function enqueue_optimized_scripts() {
        // Critical CSS inline
        $critical_css = $this->get_critical_css();
        if (!empty($critical_css)) {
            wp_add_inline_style('environmental-theme-style', $critical_css);
        }
        
        // Performance monitoring script
        wp_enqueue_script(
            'env-performance-monitor',
            ENV_PERF_PLUGIN_URL . 'assets/js/performance-monitor.js',
            array(),
            ENV_PERF_VERSION,
            true
        );
        
        wp_localize_script('env-performance-monitor', 'envPerf', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('env_perf_nonce')
        ));
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook === 'toplevel_page_environmental-performance') {
            wp_enqueue_script(
                'env-perf-admin',
                ENV_PERF_PLUGIN_URL . 'assets/js/admin-performance.js',
                array('jquery'),
                ENV_PERF_VERSION
            );
        }
    }
    
    /**
     * Add performance meta tags
     */
    public function add_performance_meta() {
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
        echo '<meta http-equiv="X-UA-Compatible" content="IE=edge">' . "\n";
        
        // DNS prefetch for external resources
        echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">' . "\n";
        echo '<link rel="dns-prefetch" href="//www.google-analytics.com">' . "\n";
        
        // Preconnect to critical resources
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
    }
    
    /**
     * Get critical CSS
     */
    private function get_critical_css() {
        $critical_css = get_transient('env_critical_css');
        
        if (false === $critical_css) {
            $critical_css = '
                body{margin:0;padding:0;font-family:Arial,sans-serif}
                .header{background:#2e7d32;color:white;padding:1rem}
                .main-nav{display:flex;justify-content:space-between;align-items:center}
                .hero-section{background:linear-gradient(135deg,#4caf50,#2e7d32);color:white;padding:4rem 2rem;text-align:center}
                .container{max-width:1200px;margin:0 auto;padding:0 1rem}
                .btn{display:inline-block;padding:0.75rem 1.5rem;background:#4caf50;color:white;text-decoration:none;border-radius:4px;transition:background 0.3s}
                .btn:hover{background:#45a049}
            ';
            
            set_transient('env_critical_css', $critical_css, HOUR_IN_SECONDS);
        }
        
        return $critical_css;
    }
}

// Initialize the plugin
EnvironmentalPerformanceOptimizer::getInstance();

// Admin menu
add_action('admin_menu', function() {
    add_menu_page(
        'Performance Settings',
        'Performance',
        'manage_options',
        'environmental-performance',
        'env_performance_admin_page',
        'dashicons-performance',
        30
    );
});

function env_performance_admin_page() {
    include ENV_PERF_PLUGIN_PATH . 'admin/performance-settings.php';
}

// Install performance metrics table
register_activation_hook(__FILE__, function() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'performance_metrics';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        memory_usage bigint NOT NULL,
        query_count int NOT NULL,
        load_time decimal(5,3) NOT NULL,
        cache_hit_ratio decimal(5,2) DEFAULT 0,
        page_url varchar(255) DEFAULT '',
        user_agent text DEFAULT '',
        PRIMARY KEY (id),
        KEY timestamp (timestamp),
        KEY load_time (load_time)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
});
