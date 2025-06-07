<?php
/**
 * Plugin Name: Environmental Content Recommendation Engine
 * Plugin URI: https://environmentalplatform.local
 * Description: Phase 55 - AI-powered content recommendation system with personalized product suggestions, similar content discovery, user behavior-based recommendations, and recommendation performance tracking
 * Version: 1.0.0
 * Author: Environmental Platform Team
 * License: GPL v2 or later
 * Text Domain: environmental-content-recommendation
 * Requires Plugins: environmental-platform-core
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ECR_VERSION', '1.0.0');
define('ECR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ECR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ECR_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once ECR_PLUGIN_DIR . 'includes/class-recommendation-engine.php';
require_once ECR_PLUGIN_DIR . 'includes/class-user-behavior-tracker.php';
require_once ECR_PLUGIN_DIR . 'includes/class-content-analyzer.php';
require_once ECR_PLUGIN_DIR . 'includes/class-similarity-calculator.php';
require_once ECR_PLUGIN_DIR . 'includes/class-recommendation-display.php';
require_once ECR_PLUGIN_DIR . 'includes/class-performance-tracker.php';
require_once ECR_PLUGIN_DIR . 'includes/class-admin-interface.php';
require_once ECR_PLUGIN_DIR . 'includes/class-ajax-handlers.php';

/**
 * Main plugin class for Environmental Content Recommendation Engine
 */
class EnvironmentalContentRecommendation {
    
    private static $instance = null;
    private $recommendation_engine;
    private $behavior_tracker;
    private $content_analyzer;
    private $similarity_calculator;
    private $display_manager;
    private $performance_tracker;
    private $admin_interface;
    private $ajax_handlers;

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
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Check dependencies
        if (!$this->check_dependencies()) {
            add_action('admin_notices', array($this, 'dependency_notice'));
            return;
        }

        // Load text domain
        load_plugin_textdomain('environmental-content-recommendation', false, dirname(ECR_PLUGIN_BASENAME) . '/languages/');

        // Initialize components
        $this->init_components();

        // Setup hooks
        $this->setup_hooks();

        // Register REST API routes
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }

    /**
     * Check plugin dependencies
     */
    private function check_dependencies() {
        return class_exists('EnvironmentalPlatformCore');
    }

    /**
     * Show dependency notice
     */
    public function dependency_notice() {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('Environmental Content Recommendation plugin requires Environmental Platform Core to be active.', 'environmental-content-recommendation');
        echo '</p></div>';
    }

    /**
     * Initialize components
     */
    private function init_components() {
        $this->recommendation_engine = Environmental_Recommendation_Engine::get_instance();
        $this->behavior_tracker = Environmental_User_Behavior_Tracker::get_instance();
        $this->content_analyzer = Environmental_Content_Analyzer::get_instance();
        $this->similarity_calculator = Environmental_Similarity_Calculator::get_instance();
        $this->display_manager = Environmental_Recommendation_Display::get_instance();
        $this->performance_tracker = Environmental_Performance_Tracker::get_instance();
        $this->ajax_handlers = Environmental_Recommendation_AJAX_Handlers::get_instance();
        
        if (is_admin()) {
            $this->admin_interface = Environmental_Recommendation_Admin_Interface::get_instance();
        }
    }

    /**
     * Setup hooks
     */
    private function setup_hooks() {
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Content hooks
        add_action('wp_footer', array($this, 'add_recommendation_containers'));
        add_filter('the_content', array($this, 'add_content_recommendations'));
        
        // WooCommerce hooks
        add_action('woocommerce_single_product_summary', array($this, 'add_product_recommendations'), 25);
        add_action('woocommerce_after_shop_loop', array($this, 'add_shop_recommendations'));
        
        // User behavior tracking
        add_action('wp_head', array($this, 'add_tracking_script'));
        add_action('wp_ajax_track_user_behavior', array($this->ajax_handlers, 'track_user_behavior'));
        add_action('wp_ajax_nopriv_track_user_behavior', array($this->ajax_handlers, 'track_user_behavior'));
        
        // Environmental platform integration
        add_action('environmental_waste_reported', array($this, 'track_environmental_action'));
        add_action('environmental_event_attended', array($this, 'track_environmental_action'));
        add_action('environmental_petition_signed', array($this, 'track_environmental_action'));
        add_action('environmental_achievement_earned', array($this, 'track_environmental_action'));
        
        // Cron jobs for recommendation updates
        add_action('ecr_update_recommendations', array($this->recommendation_engine, 'update_user_recommendations'));
        add_action('ecr_analyze_content', array($this->content_analyzer, 'analyze_new_content'));
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('environmental-recommendations/v1', '/recommendations/(?P<user_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_user_recommendations'),
            'permission_callback' => array($this, 'check_permissions')
        ));

        register_rest_route('environmental-recommendations/v1', '/similar/(?P<content_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_similar_content'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route('environmental-recommendations/v1', '/track', array(
            'methods' => 'POST',
            'callback' => array($this, 'track_interaction'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route('environmental-recommendations/v1', '/analytics', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_recommendation_analytics'),
            'permission_callback' => array($this, 'check_admin_permissions')
        ));
    }

    /**
     * Check permissions
     */
    public function check_permissions() {
        return is_user_logged_in();
    }

    /**
     * Check admin permissions
     */
    public function check_admin_permissions() {
        return current_user_can('manage_options');
    }

    /**
     * Plugin activation
     */
    public function activate() {
        $this->create_database_tables();
        $this->set_default_options();
        
        // Schedule recommendation updates
        if (!wp_next_scheduled('ecr_update_recommendations')) {
            wp_schedule_event(time(), 'hourly', 'ecr_update_recommendations');
        }
        
        // Schedule content analysis
        if (!wp_next_scheduled('ecr_analyze_content')) {
            wp_schedule_event(time(), 'daily', 'ecr_analyze_content');
        }

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('ecr_update_recommendations');
        wp_clear_scheduled_hook('ecr_analyze_content');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     */
    private function create_database_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // User behavior tracking table
        $behavior_table = $wpdb->prefix . 'ecr_user_behavior';
        $sql_behavior = "CREATE TABLE $behavior_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            content_id bigint(20) NOT NULL,
            content_type varchar(50) NOT NULL,
            action_type varchar(50) NOT NULL,
            session_id varchar(100),
            duration int DEFAULT 0,
            scroll_depth float DEFAULT 0,
            click_count int DEFAULT 0,
            interaction_score float DEFAULT 0,
            device_type varchar(50),
            user_agent text,
            ip_address varchar(45),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY content_id (content_id),
            KEY content_type (content_type),
            KEY action_type (action_type),
            KEY session_id (session_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Content features table
        $features_table = $wpdb->prefix . 'ecr_content_features';
        $sql_features = "CREATE TABLE $features_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            content_id bigint(20) NOT NULL,
            content_type varchar(50) NOT NULL,
            title_keywords text,
            content_keywords text,
            categories text,
            tags text,
            environmental_score float DEFAULT 0,
            sustainability_rating float DEFAULT 0,
            popularity_score float DEFAULT 0,
            engagement_score float DEFAULT 0,
            similarity_vector longtext,
            last_analyzed datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY content_unique (content_id, content_type),
            KEY environmental_score (environmental_score),
            KEY popularity_score (popularity_score),
            KEY last_analyzed (last_analyzed)
        ) $charset_collate;";

        // User recommendations table
        $recommendations_table = $wpdb->prefix . 'ecr_user_recommendations';
        $sql_recommendations = "CREATE TABLE $recommendations_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            content_id bigint(20) NOT NULL,
            content_type varchar(50) NOT NULL,
            recommendation_type varchar(50) NOT NULL,
            score float NOT NULL,
            reasoning text,
            is_clicked tinyint(1) DEFAULT 0,
            is_viewed tinyint(1) DEFAULT 0,
            clicked_at datetime DEFAULT NULL,
            viewed_at datetime DEFAULT NULL,
            expires_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY content_id (content_id),
            KEY recommendation_type (recommendation_type),
            KEY score (score),
            KEY expires_at (expires_at),
            UNIQUE KEY user_content_type (user_id, content_id, recommendation_type)
        ) $charset_collate;";

        // Recommendation performance table
        $performance_table = $wpdb->prefix . 'ecr_recommendation_performance';
        $sql_performance = "CREATE TABLE $performance_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            recommendation_type varchar(50) NOT NULL,
            date_recorded date NOT NULL,
            total_recommendations int DEFAULT 0,
            total_views int DEFAULT 0,
            total_clicks int DEFAULT 0,
            total_conversions int DEFAULT 0,
            click_through_rate float DEFAULT 0,
            conversion_rate float DEFAULT 0,
            avg_score float DEFAULT 0,
            performance_score float DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY recommendation_type (recommendation_type),
            KEY date_recorded (date_recorded),
            KEY performance_score (performance_score),
            UNIQUE KEY type_date (recommendation_type, date_recorded)
        ) $charset_collate;";

        // User preferences table
        $preferences_table = $wpdb->prefix . 'ecr_user_preferences';
        $sql_preferences = "CREATE TABLE $preferences_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            preference_key varchar(100) NOT NULL,
            preference_value text NOT NULL,
            weight float DEFAULT 1.0,
            confidence_score float DEFAULT 0.5,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY preference_key (preference_key),
            KEY weight (weight),
            UNIQUE KEY user_preference (user_id, preference_key)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_behavior);
        dbDelta($sql_features);
        dbDelta($sql_recommendations);
        dbDelta($sql_performance);
        dbDelta($sql_preferences);
    }

    /**
     * Set default options
     */
    private function set_default_options() {
        $default_options = array(
            'ecr_enabled' => true,
            'ecr_recommendation_types' => array('similar_content', 'personalized', 'trending', 'environmental'),
            'ecr_max_recommendations' => 6,
            'ecr_min_score_threshold' => 0.3,
            'ecr_behavior_tracking_enabled' => true,
            'ecr_auto_display_enabled' => true,
            'ecr_cache_duration' => 3600, // 1 hour
            'ecr_update_frequency' => 'hourly',
            'ecr_environmental_weight' => 0.3,
            'ecr_popularity_weight' => 0.2,
            'ecr_similarity_weight' => 0.3,
            'ecr_personalization_weight' => 0.2,
            'ecr_diversity_threshold' => 0.7,
            'ecr_performance_tracking_enabled' => true
        );

        foreach ($default_options as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!get_option('ecr_enabled', true)) {
            return;
        }

        wp_enqueue_script(
            'environmental-content-recommendation-frontend',
            ECR_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            ECR_VERSION,
            true
        );

        wp_enqueue_style(
            'environmental-content-recommendation-frontend',
            ECR_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            ECR_VERSION
        );

        wp_localize_script('environmental-content-recommendation-frontend', 'ecrFrontend', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('environmental-recommendations/v1/'),
            'nonce' => wp_create_nonce('ecr_frontend_nonce'),
            'user_id' => get_current_user_id(),
            'session_id' => $this->get_session_id(),
            'tracking_enabled' => get_option('ecr_behavior_tracking_enabled', true),
            'auto_display' => get_option('ecr_auto_display_enabled', true),
            'strings' => array(
                'loading' => __('Loading recommendations...', 'environmental-content-recommendation'),
                'no_recommendations' => __('No recommendations available', 'environmental-content-recommendation'),
                'view_more' => __('View More', 'environmental-content-recommendation'),
                'similar_items' => __('Similar Items', 'environmental-content-recommendation'),
                'recommended_for_you' => __('Recommended for You', 'environmental-content-recommendation'),
                'trending_now' => __('Trending Now', 'environmental-content-recommendation'),
                'eco_friendly' => __('Eco-Friendly Picks', 'environmental-content-recommendation')
            )
        ));
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'environmental-content-recommendation') === false) {
            return;
        }

        wp_enqueue_script(
            'environmental-content-recommendation-admin',
            ECR_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'chart-js'),
            ECR_VERSION,
            true
        );

        wp_enqueue_style(
            'environmental-content-recommendation-admin',
            ECR_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            ECR_VERSION
        );

        // Chart.js for analytics
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
            array(),
            '3.9.1',
            true
        );

        wp_localize_script('environmental-content-recommendation-admin', 'ecrAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('environmental-recommendations/v1/'),
            'nonce' => wp_create_nonce('ecr_admin_nonce'),
            'strings' => array(
                'settings_saved' => __('Settings saved successfully', 'environmental-content-recommendation'),
                'error_occurred' => __('An error occurred', 'environmental-content-recommendation'),
                'confirm_reset' => __('Are you sure you want to reset all recommendations?', 'environmental-content-recommendation'),
                'analyzing_content' => __('Analyzing content...', 'environmental-content-recommendation'),
                'updating_recommendations' => __('Updating recommendations...', 'environmental-content-recommendation')
            )
        ));
    }

    /**
     * Get session ID
     */
    private function get_session_id() {
        if (!session_id()) {
            session_start();
        }
        return session_id();
    }

    /**
     * Add recommendation containers to footer
     */
    public function add_recommendation_containers() {
        if (!get_option('ecr_enabled', true) || !get_option('ecr_auto_display_enabled', true)) {
            return;
        }

        echo '<div id="ecr-recommendations-container"></div>';
        echo '<div id="ecr-floating-recommendations"></div>';
    }

    /**
     * Add content recommendations to post content
     */
    public function add_content_recommendations($content) {
        if (!is_single() || !get_option('ecr_enabled', true)) {
            return $content;
        }

        $recommendations_html = $this->display_manager->get_content_recommendations_html(get_the_ID());
        return $content . $recommendations_html;
    }

    /**
     * Add product recommendations to WooCommerce
     */
    public function add_product_recommendations() {
        if (!get_option('ecr_enabled', true)) {
            return;
        }

        global $product;
        echo $this->display_manager->get_product_recommendations_html($product->get_id());
    }

    /**
     * Add shop recommendations
     */
    public function add_shop_recommendations() {
        if (!get_option('ecr_enabled', true)) {
            return;
        }

        echo $this->display_manager->get_shop_recommendations_html();
    }

    /**
     * Add tracking script to head
     */
    public function add_tracking_script() {
        if (!get_option('ecr_behavior_tracking_enabled', true)) {
            return;
        }
        ?>
        <script>
        window.ecrTracking = {
            startTime: Date.now(),
            scrollDepth: 0,
            clickCount: 0,
            contentId: <?php echo get_the_ID() ?: 0; ?>,
            contentType: '<?php echo get_post_type() ?: 'page'; ?>'
        };
        </script>
        <?php
    }

    /**
     * Track environmental actions
     */
    public function track_environmental_action($data) {
        if (isset($data['user_id']) && isset($data['action_type'])) {
            $this->behavior_tracker->track_environmental_action($data);
        }
    }

    /**
     * REST API Callbacks
     */
    public function get_user_recommendations($request) {
        $user_id = $request['user_id'];
        $type = $request->get_param('type') ?: 'personalized';
        $limit = $request->get_param('limit') ?: 6;
        
        return $this->recommendation_engine->get_user_recommendations($user_id, $type, $limit);
    }

    public function get_similar_content($request) {
        $content_id = $request['content_id'];
        $content_type = $request->get_param('content_type') ?: 'post';
        $limit = $request->get_param('limit') ?: 6;
        
        return $this->similarity_calculator->get_similar_content($content_id, $content_type, $limit);
    }

    public function track_interaction($request) {
        $data = $request->get_json_params();
        return $this->behavior_tracker->track_interaction($data);
    }

    public function get_recommendation_analytics($request) {
        $type = $request->get_param('type') ?: 'all';
        $period = $request->get_param('period') ?: '7days';
        
        return $this->performance_tracker->get_analytics($type, $period);
    }
}

// Initialize the plugin
function environmental_content_recommendation() {
    return EnvironmentalContentRecommendation::getInstance();
}

// Start the plugin
environmental_content_recommendation();

// Cleanup and maintenance tasks
add_action('ecr_cleanup_old_data', function() {
    $performance_tracker = Environmental_Performance_Tracker::get_instance();
    $performance_tracker->cleanup_old_data();
});
