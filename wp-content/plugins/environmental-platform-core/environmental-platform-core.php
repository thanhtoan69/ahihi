<?php
/**
 * Plugin Name: Environmental Platform Core
 * Plugin URI: https://environmentalplatform.local
 * Description: Core functionality for the Environmental Platform - integrates with the custom database structure created in Phases 1-26
 * Version: 1.0.0
 * Author: Environmental Platform Team
 * License: GPL v2 or later
 * Text Domain: environmental-platform-core
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EP_CORE_VERSION', '1.0.0');
define('EP_CORE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EP_CORE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include database integration classes
require_once EP_CORE_PLUGIN_DIR . 'includes/class-database-manager.php';
require_once EP_CORE_PLUGIN_DIR . 'includes/class-database-migration.php';
require_once EP_CORE_PLUGIN_DIR . 'includes/class-database-version-control.php';

// Include Phase 29 custom post types and taxonomies classes
require_once EP_CORE_PLUGIN_DIR . 'includes/class-post-types.php';
require_once EP_CORE_PLUGIN_DIR . 'includes/class-taxonomies.php';
require_once EP_CORE_PLUGIN_DIR . 'includes/class-content-manager.php';
require_once EP_CORE_PLUGIN_DIR . 'includes/class-content-migration.php';

// Include Phase 30 ACF classes
require_once EP_CORE_PLUGIN_DIR . 'includes/class-acf-field-groups.php';
require_once EP_CORE_PLUGIN_DIR . 'includes/class-acf-export-import.php';

// Include Phase 31 User Management classes
require_once EP_CORE_PLUGIN_DIR . 'includes/class-user-management.php';
require_once EP_CORE_PLUGIN_DIR . 'includes/class-social-auth.php';

// Include Phase 32 WooCommerce Integration classes
require_once EP_CORE_PLUGIN_DIR . 'includes/class-woocommerce-integration.php';

class EnvironmentalPlatformCore {
    
    private $db_table_prefix = '';
    private $db_manager;
    private $db_migration;
    private $db_version_control;
    private $post_types;    private $taxonomies;
    private $content_manager;    private $content_migration;
    private $user_management;
    private $social_auth;
    private $woocommerce_integration;
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'));
        
        // Hook into WordPress user registration to sync with our custom users table
        add_action('user_register', array($this, 'sync_user_registration'));
        
        // Add custom REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));
          // Initialize database integration
        $this->init_database_integration();
    }
      /**
     * Initialize database integration components
     */
    private function init_database_integration() {
        $this->db_manager = EP_Database_Manager::get_instance();
        $this->db_migration = new EP_Database_Migration();
        $this->db_version_control = new EP_Database_Version_Control();
          // Initialize Phase 29 components
        $this->post_types = new EP_Post_Types();
        $this->taxonomies = new EP_Taxonomies();
        $this->content_manager = new EP_Content_Manager();
        $this->content_migration = new EP_Content_Migration();
          // Initialize Phase 31 User Management components
        $this->user_management = new EP_User_Management();
        $this->social_auth = new EP_Social_Auth();
        
        // Initialize Phase 32 WooCommerce Integration
        $this->woocommerce_integration = EP_WooCommerce_Integration::get_instance();
    }
      public function init() {
        // Load text domain for translations
        load_plugin_textdomain('environmental-platform-core', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        
        // Note: Custom post types and taxonomies are now handled by dedicated classes
        // initialized in init_database_integration()
        
        // Check database connection
        $this->verify_database_connection();
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script(
            'environmental-platform-js',
            EP_CORE_PLUGIN_URL . 'assets/environmental-platform.js',
            array('jquery'),
            EP_CORE_VERSION,
            true
        );
        
        wp_enqueue_style(
            'environmental-platform-css',
            EP_CORE_PLUGIN_URL . 'assets/environmental-platform.css',
            array(),
            EP_CORE_VERSION
        );
        
        // Localize script for AJAX
        wp_localize_script('environmental-platform-js', 'ep_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ep_nonce')
        ));
    }
    
    public function admin_menu() {
        add_menu_page(
            __('Environmental Platform', 'environmental-platform-core'),
            __('Environmental Platform', 'environmental-platform-core'),
            'manage_options',
            'environmental-platform',
            array($this, 'admin_dashboard_page'),
            'dashicons-admin-site-alt3',
            6
        );
        
        add_submenu_page(
            'environmental-platform',
            __('Users Management', 'environmental-platform-core'),
            __('Users', 'environmental-platform-core'),
            'manage_options',
            'ep-users',
            array($this, 'users_page')
        );
        
        add_submenu_page(
            'environmental-platform',
            __('Environmental Events', 'environmental-platform-core'),
            __('Events', 'environmental-platform-core'),
            'manage_options',
            'ep-events',
            array($this, 'events_page')
        );
          add_submenu_page(
            'environmental-platform',
            __('Analytics', 'environmental-platform-core'),
            __('Analytics', 'environmental-platform-core'),
            'manage_options',
            'ep-analytics',
            array($this, 'analytics_page')
        );
        
        // Database Integration Pages
        add_submenu_page(
            'environmental-platform',
            __('Database Manager', 'environmental-platform-core'),
            __('Database', 'environmental-platform-core'),
            'manage_options',
            'ep-database',
            array($this, 'database_manager_page')
        );
        
        add_submenu_page(
            'environmental-platform',
            __('Data Migration', 'environmental-platform-core'),
            __('Migration', 'environmental-platform-core'),
            'manage_options',
            'ep-migration',
            array($this, 'migration_page')
        );
          add_submenu_page(
            'environmental-platform',
            __('Version Control', 'environmental-platform-core'),
            __('Versions', 'environmental-platform-core'),
            'manage_options',
            'ep-versions',
            array($this, 'version_control_page')
        );
        
        // Phase 29 Content Management Pages
        add_submenu_page(
            'environmental-platform',
            __('Content Management', 'environmental-platform-core'),
            __('Content Manager', 'environmental-platform-core'),
            'manage_options',
            'ep-content-manager',
            array($this, 'content_manager_page')
        );
        
        add_submenu_page(
            'environmental-platform',
            __('Content Migration', 'environmental-platform-core'),
            __('Content Migration', 'environmental-platform-core'),
            'manage_options',
            'ep-content-migration',
            array($this, 'content_migration_page')
        );
          add_submenu_page(
            'environmental-platform',
            __('Post Types & Taxonomies', 'environmental-platform-core'),
            __('Post Types', 'environmental-platform-core'),
            'manage_options',
            'ep-post-types',
            array($this, 'post_types_page')
        );
        
        // Phase 32 WooCommerce Integration Pages
        add_submenu_page(
            'environmental-platform',
            __('WooCommerce Settings', 'environmental-platform-core'),
            __('WooCommerce', 'environmental-platform-core'),
            'manage_options',
            'ep-woocommerce',
            array($this, 'woocommerce_page')
        );
    }
    
    public function admin_init() {
        // Register settings
        register_setting('ep_settings', 'ep_database_status');
        register_setting('ep_settings', 'ep_api_settings');
    }
    
    public function add_dashboard_widgets() {
        wp_add_dashboard_widget(
            'environmental_platform_stats',
            __('Environmental Platform Statistics', 'environmental-platform-core'),
            array($this, 'dashboard_widget_stats')
        );
        
        wp_add_dashboard_widget(
            'environmental_platform_recent',
            __('Recent Environmental Activities', 'environmental-platform-core'),
            array($this, 'dashboard_widget_recent_activities')
        );
    }
    
    public function setup_custom_post_types() {
        // Environmental Posts
        register_post_type('environmental_post', array(
            'labels' => array(
                'name' => __('Environmental Posts', 'environmental-platform-core'),
                'singular_name' => __('Environmental Post', 'environmental-platform-core'),
                'add_new' => __('Add New Environmental Post', 'environmental-platform-core'),
                'add_new_item' => __('Add New Environmental Post', 'environmental-platform-core'),
                'edit_item' => __('Edit Environmental Post', 'environmental-platform-core')
            ),
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-admin-post',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'comments'),
            'show_in_rest' => true
        ));
        
        // Environmental Events
        register_post_type('environmental_event', array(
            'labels' => array(
                'name' => __('Environmental Events', 'environmental-platform-core'),
                'singular_name' => __('Environmental Event', 'environmental-platform-core'),
                'add_new' => __('Add New Event', 'environmental-platform-core'),
                'add_new_item' => __('Add New Environmental Event', 'environmental-platform-core'),
                'edit_item' => __('Edit Environmental Event', 'environmental-platform-core')
            ),
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-calendar-alt',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
            'show_in_rest' => true
        ));
        
        // Waste Classification Posts
        register_post_type('waste_classification', array(
            'labels' => array(
                'name' => __('Waste Classifications', 'environmental-platform-core'),
                'singular_name' => __('Waste Classification', 'environmental-platform-core')
            ),
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-admin-tools',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
            'show_in_rest' => true
        ));
    }
    
    public function setup_custom_taxonomies() {
        // Environmental Categories
        register_taxonomy('environmental_category', array('environmental_post', 'environmental_event'), array(
            'labels' => array(
                'name' => __('Environmental Categories', 'environmental-platform-core'),
                'singular_name' => __('Environmental Category', 'environmental-platform-core')
            ),
            'hierarchical' => true,
            'public' => true,
            'show_in_rest' => true
        ));
        
        // Waste Types
        register_taxonomy('waste_type', 'waste_classification', array(
            'labels' => array(
                'name' => __('Waste Types', 'environmental-platform-core'),
                'singular_name' => __('Waste Type', 'environmental-platform-core')
            ),
            'hierarchical' => true,
            'public' => true,
            'show_in_rest' => true
        ));
    }
    
    public function verify_database_connection() {
        global $wpdb;
        
        // Check if our custom tables exist
        $tables_to_check = array('users', 'posts', 'events', 'achievements', 'categories');
        $missing_tables = array();
        
        foreach ($tables_to_check as $table) {
            $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
            if (!$table_exists) {
                $missing_tables[] = $table;
            }
        }
        
        if (!empty($missing_tables)) {
            add_action('admin_notices', function() use ($missing_tables) {
                echo '<div class="notice notice-warning"><p>';
                echo __('Environmental Platform: Some database tables are missing: ', 'environmental-platform-core');
                echo implode(', ', $missing_tables);
                echo '</p></div>';
            });
        }
    }
    
    public function sync_user_registration($user_id) {
        global $wpdb;
        
        $user = get_userdata($user_id);
        if ($user) {
            // Insert into our custom users table
            $wpdb->insert(
                'users',
                array(
                    'username' => $user->user_login,
                    'email' => $user->user_email,
                    'full_name' => $user->display_name,
                    'wp_user_id' => $user_id,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%d', '%s', '%s')
            );
        }
    }
    
    public function register_rest_routes() {
        register_rest_route('environmental-platform/v1', '/stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_platform_stats'),
            'permission_callback' => function() {
                return current_user_can('read');
            }
        ));
        
        register_rest_route('environmental-platform/v1', '/events', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_environmental_events'),
            'permission_callback' => function() {
                return current_user_can('read');
            }
        ));
    }
    
    public function get_platform_stats($request) {
        global $wpdb;
        
        $stats = array(
            'total_users' => $wpdb->get_var("SELECT COUNT(*) FROM users"),
            'total_posts' => $wpdb->get_var("SELECT COUNT(*) FROM posts"),
            'total_events' => $wpdb->get_var("SELECT COUNT(*) FROM events"),
            'total_achievements' => $wpdb->get_var("SELECT COUNT(*) FROM achievements"),
            'database_tables' => $wpdb->get_var("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'environmental_platform'")
        );
        
        return rest_ensure_response($stats);
    }
    
    public function get_environmental_events($request) {
        global $wpdb;
        
        $events = $wpdb->get_results("SELECT * FROM events ORDER BY created_at DESC LIMIT 10");
        return rest_ensure_response($events);
    }
    
    // Admin page callbacks
    public function admin_dashboard_page() {
        include EP_CORE_PLUGIN_DIR . 'admin/dashboard.php';
    }
    
    public function users_page() {
        include EP_CORE_PLUGIN_DIR . 'admin/users.php';
    }
    
    public function events_page() {
        include EP_CORE_PLUGIN_DIR . 'admin/events.php';
    }
    
    public function analytics_page() {
        include EP_CORE_PLUGIN_DIR . 'admin/analytics.php';
    }
    
    // Database Integration Admin Pages
    public function database_manager_page() {
        include EP_CORE_PLUGIN_DIR . 'admin/database-manager.php';
    }
    
    public function migration_page() {
        include EP_CORE_PLUGIN_DIR . 'admin/migration.php';
    }
      public function version_control_page() {
        include EP_CORE_PLUGIN_DIR . 'admin/version-control.php';
    }
    
    // Phase 29 Admin Page Handlers
    public function content_manager_page() {
        include EP_CORE_PLUGIN_DIR . 'admin/content-management.php';
    }
    
    public function content_migration_page() {
        include EP_CORE_PLUGIN_DIR . 'admin/content-migration.php';
    }
      public function post_types_page() {
        include EP_CORE_PLUGIN_DIR . 'admin/post-types.php';
    }
    
    // Phase 32 WooCommerce Admin Page Handler
    public function woocommerce_page() {
        include EP_CORE_PLUGIN_DIR . 'admin/woocommerce.php';
    }
    
    public function dashboard_widget_stats() {
        global $wpdb;
        
        $user_count = $wpdb->get_var("SELECT COUNT(*) FROM users");
        $post_count = $wpdb->get_var("SELECT COUNT(*) FROM posts");
        $event_count = $wpdb->get_var("SELECT COUNT(*) FROM events");
        $achievement_count = $wpdb->get_var("SELECT COUNT(*) FROM achievements");
        
        echo '<div class="ep-dashboard-stats">';
        echo '<h4>' . __('Platform Statistics', 'environmental-platform-core') . '</h4>';
        echo '<ul>';
        echo '<li><strong>' . __('Total Users:', 'environmental-platform-core') . '</strong> ' . ($user_count ?: '0') . '</li>';
        echo '<li><strong>' . __('Total Posts:', 'environmental-platform-core') . '</strong> ' . ($post_count ?: '0') . '</li>';
        echo '<li><strong>' . __('Total Events:', 'environmental-platform-core') . '</strong> ' . ($event_count ?: '0') . '</li>';
        echo '<li><strong>' . __('Total Achievements:', 'environmental-platform-core') . '</strong> ' . ($achievement_count ?: '0') . '</li>';
        echo '</ul>';
        echo '<p><a href="' . admin_url('admin.php?page=environmental-platform') . '" class="button button-primary">' . __('View Full Dashboard', 'environmental-platform-core') . '</a></p>';
        echo '</div>';
    }
    
    public function dashboard_widget_recent_activities() {
        global $wpdb;
        
        $recent_posts = $wpdb->get_results("SELECT title, created_at FROM posts ORDER BY created_at DESC LIMIT 5");
        
        echo '<div class="ep-recent-activities">';
        echo '<h4>' . __('Recent Environmental Posts', 'environmental-platform-core') . '</h4>';
        
        if ($recent_posts) {
            echo '<ul>';
            foreach ($recent_posts as $post) {
                echo '<li>';
                echo '<strong>' . esc_html($post->title) . '</strong><br>';
                echo '<small>' . date('M j, Y g:i A', strtotime($post->created_at)) . '</small>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>' . __('No recent posts found.', 'environmental-platform-core') . '</p>';
        }
        
        echo '</div>';
    }
}

// Initialize the plugin
new EnvironmentalPlatformCore();

// Activation hook
register_activation_hook(__FILE__, 'ep_core_activation');
function ep_core_activation() {
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Create plugin options
    add_option('ep_core_version', EP_CORE_VERSION);
    add_option('ep_database_status', 'connected');
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'ep_core_deactivation');
function ep_core_deactivation() {
    flush_rewrite_rules();
}
?>
