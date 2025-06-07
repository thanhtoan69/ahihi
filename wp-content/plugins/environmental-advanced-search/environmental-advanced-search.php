<?php
/**
 * Plugin Name: Environmental Advanced Search & Filtering
 * Plugin URI: https://environmentalplatform.local
 * Description: Phase 53 - Advanced search and filtering functionality with Elasticsearch integration, faceted search, geolocation-based results, and search analytics for the Environmental Platform
 * Version: 1.0.0
 * Author: Environmental Platform Team
 * License: GPL v2 or later
 * Text Domain: environmental-advanced-search
 * Requires Plugins: environmental-platform-core
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EAS_VERSION', '1.0.0');
define('EAS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EAS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EAS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once EAS_PLUGIN_DIR . 'includes/class-elasticsearch-manager.php';
require_once EAS_PLUGIN_DIR . 'includes/class-search-engine.php';
require_once EAS_PLUGIN_DIR . 'includes/class-faceted-search.php';
require_once EAS_PLUGIN_DIR . 'includes/class-geolocation-search.php';
require_once EAS_PLUGIN_DIR . 'includes/class-search-analytics.php';
require_once EAS_PLUGIN_DIR . 'includes/class-ajax-handlers.php';
require_once EAS_PLUGIN_DIR . 'includes/class-search-widget.php';
require_once EAS_PLUGIN_DIR . 'includes/class-shortcodes.php';
require_once EAS_PLUGIN_DIR . 'includes/class-admin.php';

/**
 * Main plugin class for Environmental Advanced Search & Filtering
 */
class EnvironmentalAdvancedSearch {
    
    private static $instance = null;    private $elasticsearch_manager;
    private $search_engine;
    private $faceted_search;
    private $geolocation_search;
    private $search_analytics;
    private $ajax_handlers;
    private $search_widget;
    private $shortcodes;
    private $admin;

    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'), 20);
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Check if Environmental Platform Core is active
        if (!class_exists('EnvironmentalPlatformCore')) {
            add_action('admin_notices', array($this, 'core_plugin_notice'));
            return;
        }

        // Load plugin textdomain
        load_plugin_textdomain('environmental-advanced-search', false, dirname(plugin_basename(__FILE__)) . '/languages');

        // Initialize components
        $this->init_components();
        
        // Hook into WordPress
        $this->setup_hooks();
    }    /**
     * Initialize plugin components
     */
    private function init_components() {
        $this->elasticsearch_manager = new EAS_Elasticsearch_Manager();
        $this->search_engine = new EAS_Search_Engine();
        $this->faceted_search = new EAS_Faceted_Search();
        $this->geolocation_search = new EAS_Geolocation_Search();
        $this->search_analytics = new EAS_Search_Analytics();
        $this->ajax_handlers = new EAS_Ajax_Handlers();
        $this->search_widget = new EAS_Search_Widget();
        $this->shortcodes = new EAS_Shortcodes();
        
        if (is_admin()) {
            $this->admin = new EAS_Admin();
        }
    }

    /**
     * Setup WordPress hooks
     */
    private function setup_hooks() {        // Override default WordPress search
        add_action('parse_query', array($this->search_engine, 'modify_search_query'));
        add_filter('posts_search', array($this->search_engine, 'modify_search_sql'), 10, 2);
        add_filter('posts_join', array($this->search_engine, 'modify_search_join'), 10, 2);
        add_filter('posts_where', array($this->search_engine, 'modify_search_where'), 10, 2);
        add_filter('posts_orderby', array($this->search_engine, 'modify_search_orderby'), 10, 2);
        
        // Add search to all post types
        add_filter('pre_get_posts', array($this->search_engine, 'include_all_post_types_in_search'));
        
        // Register AJAX handlers (handled by the AJAX handlers class)
        $this->ajax_handlers->register_hooks();
        
        // Register widgets
        add_action('widgets_init', array($this, 'register_widgets'));
        
        // Add search form filters
        add_filter('get_search_form', array($this->search_engine, 'enhanced_search_form'));
        
        // Add REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_endpoints'));
        
        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Register widgets
     */
    public function register_widgets() {
        register_widget('EAS_Search_Widget');
    }

    /**
     * Register REST API endpoints
     */
    public function register_rest_endpoints() {
        register_rest_route('environmental-search/v1', '/search', array(
            'methods' => 'GET',
            'callback' => array($this->search_ajax, 'rest_search'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('environmental-search/v1', '/facets', array(
            'methods' => 'GET',
            'callback' => array($this->search_ajax, 'rest_get_facets'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('environmental-search/v1', '/analytics', array(
            'methods' => 'GET',
            'callback' => array($this->search_analytics, 'rest_get_analytics'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ));
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_script('eas-frontend', EAS_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), EAS_VERSION, true);
        wp_enqueue_style('eas-frontend', EAS_PLUGIN_URL . 'assets/css/frontend.css', array(), EAS_VERSION);
        
        // Localize script with AJAX URL and nonce
        wp_localize_script('eas-frontend', 'eas_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('environmental-search/v1/'),
            'nonce' => wp_create_nonce('eas_nonce'),    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        // Only load on pages that might have search functionality
        if (!is_search() && !has_shortcode(get_post()->post_content ?? '', 'eas_search') && !is_active_widget(false, false, 'eas_search_widget')) {
            return;
        }
        
        wp_enqueue_style('eas-frontend', EAS_PLUGIN_URL . 'assets/css/frontend.css', array(), EAS_VERSION);
        wp_enqueue_script('eas-frontend', EAS_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), EAS_VERSION, true);
        
        wp_localize_script('eas-frontend', 'eas_ajax', array(
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eas_frontend_nonce'),
            'strings' => array(
                'search_placeholder' => __('Search environmental content...', 'environmental-advanced-search'),
                'no_results' => __('No results found.', 'environmental-advanced-search'),
                'loading' => __('Loading...', 'environmental-advanced-search'),
                'error' => __('An error occurred while searching.', 'environmental-advanced-search'),
                'location_error' => __('Unable to detect your location.', 'environmental-advanced-search')
            )
        ));
        
        // Add Google Maps API for geolocation features
        $google_maps_api_key = get_option('eas_google_maps_api_key', '');
        if (!empty($google_maps_api_key)) {
            wp_enqueue_script('google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . $google_maps_api_key . '&libraries=places', array(), null, true);
        }
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'environmental-advanced-search') === false) {
            return;
        }
          wp_enqueue_script('eas-admin', EAS_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'wp-color-picker'), EAS_VERSION, true);
        wp_enqueue_style('eas-admin', EAS_PLUGIN_URL . 'assets/css/admin.css', array('wp-color-picker'), EAS_VERSION);
        
        // Add Chart.js for analytics charts
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        
        wp_localize_script('eas-admin', 'eas_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eas_admin_nonce'),
            'strings' => array(
                'confirm_reindex' => __('Are you sure you want to reindex all content? This may take some time.', 'environmental-advanced-search'),
                'reindexing' => __('Reindexing content...', 'environmental-advanced-search'),
                'reindex_complete' => __('Reindexing completed successfully.', 'environmental-advanced-search'),
                'error' => __('An error occurred.', 'environmental-advanced-search')
            )
        ));
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables for analytics
        $this->create_database_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Schedule analytics cleanup
        if (!wp_next_scheduled('eas_cleanup_analytics')) {
            wp_schedule_event(time(), 'daily', 'eas_cleanup_analytics');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('eas_cleanup_analytics');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     */
    private function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Search analytics table
        $table_name = $wpdb->prefix . 'eas_search_analytics';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL auto_increment,
            search_term varchar(255) NOT NULL,
            search_filters text,
            results_count int(11) DEFAULT 0,
            user_id bigint(20) unsigned DEFAULT NULL,
            ip_address varchar(45),
            user_agent text,
            search_timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            response_time float DEFAULT 0,
            clicked_result_id bigint(20) unsigned DEFAULT NULL,
            click_position int(11) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY search_term (search_term),
            KEY user_id (user_id),
            KEY search_timestamp (search_timestamp)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Popular searches table
        $table_name = $wpdb->prefix . 'eas_popular_searches';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL auto_increment,
            search_term varchar(255) NOT NULL,
            search_count int(11) DEFAULT 1,
            last_searched datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY search_term (search_term)
        ) $charset_collate;";
        
        dbDelta($sql);
    }

    /**
     * Set default plugin options
     */
    private function set_default_options() {
        $defaults = array(
            'eas_enable_elasticsearch' => 'no',
            'eas_elasticsearch_host' => 'localhost:9200',
            'eas_elasticsearch_index' => 'environmental_platform',
            'eas_enable_faceted_search' => 'yes',
            'eas_enable_geolocation' => 'yes',
            'eas_enable_analytics' => 'yes',
            'eas_search_weight_title' => '10',
            'eas_search_weight_content' => '5',
            'eas_search_weight_excerpt' => '7',
            'eas_search_weight_meta' => '3',
            'eas_search_weight_taxonomy' => '4',
            'eas_results_per_page' => '10',
            'eas_enable_search_suggestions' => 'yes',
            'eas_min_search_length' => '3',
            'eas_google_maps_api_key' => '',
            'eas_geolocation_radius' => '50',
            'eas_cache_duration' => '3600'
        );
        
        foreach ($defaults as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }

    /**
     * Show notice if core plugin is not active
     */
    public function core_plugin_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('Environmental Advanced Search & Filtering requires the Environmental Platform Core plugin to be installed and activated.', 'environmental-advanced-search'); ?></p>
        </div>
        <?php
    }

    /**
     * Get elasticsearch manager instance
     */
    public function get_elasticsearch_manager() {
        return $this->elasticsearch_manager;
    }

    /**
     * Get search engine instance
     */
    public function get_search_engine() {
        return $this->search_engine;
    }

    /**
     * Get faceted search instance
     */
    public function get_faceted_search() {
        return $this->faceted_search;
    }

    /**
     * Get geolocation search instance
     */
    public function get_geolocation_search() {
        return $this->geolocation_search;
    }

    /**
     * Get search analytics instance
     */
    public function get_search_analytics() {
        return $this->search_analytics;
    }
}

// Initialize the plugin
function eas_init() {
    return EnvironmentalAdvancedSearch::getInstance();
}

// Hook into WordPress
add_action('init', 'eas_init', 0);

// Cleanup analytics on schedule
add_action('eas_cleanup_analytics', function() {
    $analytics = EnvironmentalAdvancedSearch::getInstance()->get_search_analytics();
    if ($analytics) {
        $analytics->cleanup_old_data();
    }
});
