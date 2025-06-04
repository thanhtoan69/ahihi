<?php
/**
 * Plugin Name: Environmental Data Dashboard
 * Plugin URI: https://moitruong.local/environmental-dashboard
 * Description: Comprehensive environmental data visualization dashboard with real-time air quality, weather data, carbon footprint tracking, and community environmental statistics.
 * Version: 1.0.0
 * Author: Environmental Platform Team
 * License: GPL v2 or later
 * Text Domain: env-data-dashboard
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ENV_DASHBOARD_VERSION', '1.0.0');
define('ENV_DASHBOARD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ENV_DASHBOARD_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ENV_DASHBOARD_PLUGIN_FILE', __FILE__);

/**
 * Main Environmental Data Dashboard Class
 */
class Environmental_Data_Dashboard {
    
    private static $instance = null;
    
    /**
     * Get singleton instance
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
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_ajax_get_air_quality_data', array($this, 'ajax_get_air_quality_data'));
        add_action('wp_ajax_nopriv_get_air_quality_data', array($this, 'ajax_get_air_quality_data'));
        add_action('wp_ajax_get_weather_data', array($this, 'ajax_get_weather_data'));
        add_action('wp_ajax_nopriv_get_weather_data', array($this, 'ajax_get_weather_data'));        add_action('wp_ajax_save_carbon_footprint', array($this, 'ajax_save_carbon_footprint'));
        add_action('wp_ajax_get_user_dashboard_data', array($this, 'ajax_get_user_dashboard_data'));
        add_action('wp_ajax_get_community_stats', array($this, 'ajax_get_community_stats'));
        add_action('wp_ajax_nopriv_get_community_stats', array($this, 'ajax_get_community_stats'));
        
        // New AJAX actions for enhanced functionality
        add_action('wp_ajax_get_carbon_footprint_data', array($this, 'ajax_get_carbon_footprint_data'));
        add_action('wp_ajax_get_personal_dashboard_data', array($this, 'ajax_get_personal_dashboard_data'));
        add_action('wp_ajax_get_charts_data', array($this, 'ajax_get_charts_data'));
        add_action('wp_ajax_nopriv_get_charts_data', array($this, 'ajax_get_charts_data'));
          // Admin AJAX actions
        add_action('wp_ajax_get_admin_dashboard_data', array($this, 'ajax_get_admin_dashboard_data'));
        add_action('wp_ajax_save_environmental_settings', array($this, 'ajax_save_environmental_settings'));
        add_action('wp_ajax_save_api_config', array($this, 'ajax_save_api_config'));
        add_action('wp_ajax_test_api_connection', array($this, 'ajax_test_api_connection'));
        add_action('wp_ajax_clear_environmental_cache', array($this, 'ajax_clear_environmental_cache'));
        add_action('wp_ajax_reset_environmental_settings', array($this, 'ajax_reset_environmental_settings'));
        add_action('wp_ajax_delete_environmental_data', array($this, 'ajax_delete_environmental_data'));
        add_action('wp_ajax_generate_environmental_report', array($this, 'ajax_generate_environmental_report'));
        add_action('wp_ajax_export_environmental_report', array($this, 'ajax_export_environmental_report'));
          // AI & Waste Classification AJAX actions
        add_action('wp_ajax_classify_waste_image', array($this, 'ajax_classify_waste_image'));
        add_action('wp_ajax_nopriv_classify_waste_image', array($this, 'ajax_classify_waste_image'));
        add_action('wp_ajax_submit_classification_feedback', array($this, 'ajax_submit_classification_feedback'));
        add_action('wp_ajax_get_classification_history', array($this, 'ajax_get_classification_history'));
        add_action('wp_ajax_clear_classification_history', array($this, 'ajax_clear_classification_history'));
        add_action('wp_ajax_update_user_gamification', array($this, 'ajax_update_user_gamification'));
        add_action('wp_ajax_get_gamification_data', array($this, 'ajax_get_gamification_data'));        add_action('wp_ajax_get_leaderboard_data', array($this, 'ajax_get_leaderboard_data'));
        add_action('wp_ajax_nopriv_get_leaderboard_data', array($this, 'ajax_get_leaderboard_data'));
        
        // Quiz System AJAX actions
        add_action('wp_ajax_start_quiz', array($this, 'ajax_start_quiz'));
        add_action('wp_ajax_nopriv_start_quiz', array($this, 'ajax_start_quiz'));
        add_action('wp_ajax_submit_quiz_answer', array($this, 'ajax_submit_quiz_answer'));
        add_action('wp_ajax_nopriv_submit_quiz_answer', array($this, 'ajax_submit_quiz_answer'));
        add_action('wp_ajax_complete_quiz', array($this, 'ajax_complete_quiz'));
        add_action('wp_ajax_nopriv_complete_quiz', array($this, 'ajax_complete_quiz'));
        add_action('wp_ajax_get_quiz_leaderboard', array($this, 'ajax_get_quiz_leaderboard'));
        add_action('wp_ajax_nopriv_get_quiz_leaderboard', array($this, 'ajax_get_quiz_leaderboard'));
        add_action('wp_ajax_get_user_quiz_stats', array($this, 'ajax_get_user_quiz_stats'));
        
        // Challenge System AJAX actions
        add_action('wp_ajax_get_available_challenges', array($this, 'ajax_get_available_challenges'));
        add_action('wp_ajax_nopriv_get_available_challenges', array($this, 'ajax_get_available_challenges'));
        add_action('wp_ajax_participate_in_challenge', array($this, 'ajax_participate_in_challenge'));
        add_action('wp_ajax_update_challenge_progress', array($this, 'ajax_update_challenge_progress'));
        add_action('wp_ajax_get_user_challenges', array($this, 'ajax_get_user_challenges'));
        add_action('wp_ajax_complete_challenge', array($this, 'ajax_complete_challenge'));
        
        // Admin AI & Waste Classification AJAX actions
        add_action('wp_ajax_get_ai_classification_stats', array($this, 'ajax_get_ai_classification_stats'));
        add_action('wp_ajax_get_recent_classifications', array($this, 'ajax_get_recent_classifications'));
        add_action('wp_ajax_save_ai_configuration', array($this, 'ajax_save_ai_configuration'));
        add_action('wp_ajax_test_ai_connection', array($this, 'ajax_test_ai_connection'));
        add_action('wp_ajax_save_gamification_settings', array($this, 'ajax_save_gamification_settings'));
        add_action('wp_ajax_export_classifications_data', array($this, 'ajax_export_classifications_data'));
        add_action('wp_ajax_update_classification_status', array($this, 'ajax_update_classification_status'));
        add_action('wp_ajax_get_classification_details', array($this, 'ajax_get_classification_details'));
        add_action('wp_ajax_get_classifications_trend_data', array($this, 'ajax_get_classifications_trend_data'));
        add_action('wp_ajax_get_user_engagement_data', array($this, 'ajax_get_user_engagement_data'));
        
        // Cron jobs for data updates
        add_action('env_dashboard_update_air_quality', array($this, 'update_air_quality_data'));
        add_action('env_dashboard_update_weather', array($this, 'update_weather_data'));
        add_action('env_dashboard_calculate_community_stats', array($this, 'calculate_community_stats'));
          // Shortcodes
        add_shortcode('env_air_quality_widget', array($this, 'air_quality_widget_shortcode'));
        add_shortcode('env_weather_widget', array($this, 'weather_widget_shortcode'));
        add_shortcode('env_carbon_tracker', array($this, 'carbon_tracker_shortcode'));
        add_shortcode('env_personal_dashboard', array($this, 'personal_dashboard_shortcode'));
        add_shortcode('env_community_stats', array($this, 'community_stats_shortcode'));
        
        // AI & Waste Classification Shortcodes
        add_shortcode('env_waste_classifier', array($this, 'waste_classifier_shortcode'));
        add_shortcode('env_classification_history', array($this, 'classification_history_shortcode'));        add_shortcode('env_gamification_widget', array($this, 'gamification_widget_shortcode'));
        add_shortcode('env_achievements_display', array($this, 'achievements_display_shortcode'));
        add_shortcode('env_leaderboard', array($this, 'leaderboard_shortcode'));
        
        // Quiz & Challenge System Shortcodes
        add_shortcode('env_quiz_interface', array($this, 'quiz_interface_shortcode'));
        add_shortcode('env_quiz_leaderboard', array($this, 'quiz_leaderboard_shortcode'));
        add_shortcode('env_challenge_dashboard', array($this, 'challenge_dashboard_shortcode'));
        add_shortcode('env_user_progress', array($this, 'user_progress_shortcode'));
        
        // Custom post types and taxonomies
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        
        // Admin pages
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
      /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once ENV_DASHBOARD_PLUGIN_PATH . 'includes/class-air-quality-api.php';
        require_once ENV_DASHBOARD_PLUGIN_PATH . 'includes/class-weather-api.php';
        require_once ENV_DASHBOARD_PLUGIN_PATH . 'includes/class-carbon-footprint-tracker.php';
        require_once ENV_DASHBOARD_PLUGIN_PATH . 'includes/class-environmental-widgets.php';
        require_once ENV_DASHBOARD_PLUGIN_PATH . 'includes/class-data-visualization.php';
        require_once ENV_DASHBOARD_PLUGIN_PATH . 'includes/class-community-stats.php';
        require_once ENV_DASHBOARD_PLUGIN_PATH . 'includes/class-personal-dashboard.php';
        require_once ENV_DASHBOARD_PLUGIN_PATH . 'includes/class-database-manager.php';
          // AI & Waste Classification Dependencies
        require_once ENV_DASHBOARD_PLUGIN_PATH . 'includes/class-ai-service-manager.php';
        require_once ENV_DASHBOARD_PLUGIN_PATH . 'includes/class-waste-classification-interface.php';
        require_once ENV_DASHBOARD_PLUGIN_PATH . 'includes/class-gamification-system.php';
          // Quiz & Challenge System Dependencies
        require_once ENV_DASHBOARD_PLUGIN_PATH . 'includes/class-quiz-manager.php';
        require_once ENV_DASHBOARD_PLUGIN_PATH . 'includes/class-challenge-system.php';
        require_once ENV_DASHBOARD_PLUGIN_PATH . 'includes/sample-data-inserter.php';
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        $this->create_database_tables();
        $this->schedule_cron_jobs();
        $this->set_default_options();
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        $this->clear_cron_jobs();
        flush_rewrite_rules();
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('env-data-dashboard', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize components
        if (class_exists('Environmental_Database_Manager')) {
            Environmental_Database_Manager::get_instance();
        }
        if (class_exists('Environmental_Widgets')) {
            Environmental_Widgets::get_instance();
        }
        if (class_exists('Environmental_Data_Visualization')) {
            Environmental_Data_Visualization::get_instance();
        }
        if (class_exists('Community_Environmental_Stats')) {
            Community_Environmental_Stats::get_instance();
        }        if (class_exists('Personal_Environmental_Dashboard')) {
            Personal_Environmental_Dashboard::get_instance();
        }
        
        // Initialize Quiz & Challenge Systems
        if (class_exists('Environmental_Quiz_Manager')) {
            Environmental_Quiz_Manager::get_instance();
        }
        if (class_exists('Environmental_Challenge_System')) {
            Environmental_Challenge_System::get_instance();
        }
    }    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true);
        
        // Enqueue custom styles
        wp_enqueue_style(
            'environmental-dashboard-css',
            ENV_DASHBOARD_PLUGIN_URL . 'assets/css/environmental-dashboard.css',
            array(),
            ENV_DASHBOARD_VERSION
        );
          // Enqueue AI & Waste Classification styles
        wp_enqueue_style(
            'waste-classification-css',
            ENV_DASHBOARD_PLUGIN_URL . 'assets/css/waste-classification.css',
            array(),
            ENV_DASHBOARD_VERSION
        );
        
        // Enqueue Quiz & Challenge System styles
        wp_enqueue_style(
            'quiz-challenge-css',
            ENV_DASHBOARD_PLUGIN_URL . 'assets/css/quiz-challenge-styles.css',
            array(),
            ENV_DASHBOARD_VERSION
        );
        
        wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4');
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0');
        
        // Enqueue custom scripts
        wp_enqueue_script(
            'environmental-dashboard-js',
            ENV_DASHBOARD_PLUGIN_URL . 'assets/js/environmental-dashboard.js',
            array('jquery', 'chart-js'),
            ENV_DASHBOARD_VERSION,
            true
        );
          // Enqueue AI & Waste Classification scripts
        wp_enqueue_script(
            'waste-classification-js',
            ENV_DASHBOARD_PLUGIN_URL . 'assets/js/waste-classification.js',
            array('jquery'),
            ENV_DASHBOARD_VERSION,
            true
        );
        
        // Enqueue Quiz & Challenge System scripts
        wp_enqueue_script(
            'quiz-interface-js',
            ENV_DASHBOARD_PLUGIN_URL . 'assets/js/quiz-interface.js',
            array('jquery'),
            ENV_DASHBOARD_VERSION,
            true
        );
        
        wp_enqueue_script(
            'challenge-dashboard-js',
            ENV_DASHBOARD_PLUGIN_URL . 'assets/js/challenge-dashboard.js',
            array('jquery'),
            ENV_DASHBOARD_VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('environmental-dashboard-js', 'environmental_dashboard_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('environmental_dashboard_nonce'),
            'user_id' => get_current_user_id(),
            'is_user_logged_in' => is_user_logged_in()
        ));
          // Localize script for AI features
        wp_localize_script('waste-classification-js', 'envDashboard', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('env_dashboard_ai_nonce'),
            'userId' => get_current_user_id(),
            'isLoggedIn' => is_user_logged_in(),
            'pluginUrl' => ENV_DASHBOARD_PLUGIN_URL
        ));
        
        // Localize script for Quiz features
        wp_localize_script('quiz-interface-js', 'envQuizAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('env_quiz_nonce'),
            'userId' => get_current_user_id(),
            'isLoggedIn' => is_user_logged_in()
        ));
        
        // Localize script for Challenge features
        wp_localize_script('challenge-dashboard-js', 'envChallengeAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('env_challenge_nonce'),
            'userId' => get_current_user_id(),
            'isLoggedIn' => is_user_logged_in()
        ));
    }
      /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'env-dashboard') === false && strpos($hook, 'env-') === false) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        
        // Enqueue admin styles
        wp_enqueue_style(
            'environmental-admin-css',
            ENV_DASHBOARD_PLUGIN_URL . 'admin/css/admin-styles.css',
            array(),
            ENV_DASHBOARD_VERSION
        );
        
        // Enqueue AI Waste Classification admin styles for the AI waste page
        if (strpos($hook, 'env-ai-waste') !== false) {
            wp_enqueue_style(
                'waste-classification-css',
                ENV_DASHBOARD_PLUGIN_URL . 'assets/css/waste-classification.css',
                array(),
                ENV_DASHBOARD_VERSION
            );
        }
        
        // Enqueue admin scripts
        wp_enqueue_script(
            'environmental-admin-js',
            ENV_DASHBOARD_PLUGIN_URL . 'admin/js/admin-dashboard.js',
            array('jquery', 'chart-js'),
            ENV_DASHBOARD_VERSION,
            true
        );
        
        // Enqueue AI Waste Classification admin scripts for the AI waste page
        if (strpos($hook, 'env-ai-waste') !== false) {
            wp_enqueue_script(
                'waste-classification-admin-js',
                ENV_DASHBOARD_PLUGIN_URL . 'assets/js/waste-classification-admin.js',
                array('jquery', 'chart-js'),
                ENV_DASHBOARD_VERSION,
                true
            );
        }
        
        // Localize script for admin AJAX
        wp_localize_script('environmental-admin-js', 'environmental_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('environmental_admin_nonce'),
            'strings' => array(
                'loading' => __('Loading...', 'env-data-dashboard'),
                'error' => __('An error occurred', 'env-data-dashboard'),
                'success' => __('Operation completed successfully', 'env-data-dashboard'),
                'confirm_delete' => __('Are you sure you want to delete this item?', 'env-data-dashboard')
            )
        ));
        
        // Additional localization for AI waste classification admin
        if (strpos($hook, 'env-ai-waste') !== false) {
            wp_localize_script('waste-classification-admin-js', 'waste_classification_admin_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('waste_classification_admin_nonce'),
                'strings' => array(
                    'testing_connection' => __('Testing connection...', 'env-data-dashboard'),
                    'connection_successful' => __('Connection successful!', 'env-data-dashboard'),
                    'connection_failed' => __('Connection failed. Please check your settings.', 'env-data-dashboard'),
                    'exporting_data' => __('Exporting data...', 'env-data-dashboard'),
                    'export_complete' => __('Export completed successfully', 'env-data-dashboard')
                )
            ));
        }
    }
    
    /**
     * Create database tables
     */
    private function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Air quality data table
        $air_quality_table = $wpdb->prefix . 'env_air_quality_data';
        $sql_air_quality = "CREATE TABLE $air_quality_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            location_name varchar(255) NOT NULL,
            latitude decimal(10,8) NOT NULL,
            longitude decimal(11,8) NOT NULL,
            aqi int(11) NOT NULL,
            pm25 decimal(8,2) DEFAULT NULL,
            pm10 decimal(8,2) DEFAULT NULL,
            o3 decimal(8,2) DEFAULT NULL,
            no2 decimal(8,2) DEFAULT NULL,
            so2 decimal(8,2) DEFAULT NULL,
            co decimal(8,2) DEFAULT NULL,
            quality_level varchar(50) NOT NULL,
            recorded_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY location_name (location_name),
            KEY recorded_at (recorded_at)
        ) $charset_collate;";
        
        // Weather data table
        $weather_table = $wpdb->prefix . 'env_weather_data';
        $sql_weather = "CREATE TABLE $weather_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            location_name varchar(255) NOT NULL,
            latitude decimal(10,8) NOT NULL,
            longitude decimal(11,8) NOT NULL,
            temperature decimal(5,2) NOT NULL,
            humidity int(11) NOT NULL,
            pressure decimal(7,2) NOT NULL,
            wind_speed decimal(5,2) DEFAULT NULL,
            wind_direction int(11) DEFAULT NULL,
            visibility decimal(5,2) DEFAULT NULL,
            uv_index decimal(3,1) DEFAULT NULL,
            weather_condition varchar(100) NOT NULL,
            recorded_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY location_name (location_name),
            KEY recorded_at (recorded_at)
        ) $charset_collate;";
        
        // Carbon footprint tracking table
        $carbon_footprint_table = $wpdb->prefix . 'env_carbon_footprint';
        $sql_carbon = "CREATE TABLE $carbon_footprint_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            activity_type varchar(100) NOT NULL,
            activity_description text,
            carbon_amount decimal(10,4) NOT NULL,
            unit varchar(20) NOT NULL DEFAULT 'kg',
            category varchar(100) NOT NULL,
            date_recorded date NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY activity_type (activity_type),
            KEY category (category),
            KEY date_recorded (date_recorded),
            FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
        ) $charset_collate;";
        
        // Environmental goals table
        $goals_table = $wpdb->prefix . 'env_user_goals';
        $sql_goals = "CREATE TABLE $goals_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            goal_type varchar(100) NOT NULL,
            goal_description text,
            target_value decimal(10,4) NOT NULL,
            current_value decimal(10,4) NOT NULL DEFAULT 0,
            unit varchar(20) NOT NULL,
            target_date date NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY goal_type (goal_type),
            KEY status (status),
            FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
        ) $charset_collate;";
        
        // Community environmental data table
        $community_data_table = $wpdb->prefix . 'env_community_data';
        $sql_community = "CREATE TABLE $community_data_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            metric_name varchar(100) NOT NULL,
            metric_value decimal(15,4) NOT NULL,
            metric_unit varchar(20) NOT NULL,
            period_type varchar(20) NOT NULL DEFAULT 'daily',
            period_date date NOT NULL,
            location varchar(255) DEFAULT NULL,
            calculated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY metric_name (metric_name),
            KEY period_date (period_date),
            KEY location (location)
        ) $charset_collate;";
          require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        dbDelta($sql_air_quality);
        dbDelta($sql_weather);
        dbDelta($sql_carbon);
        dbDelta($sql_goals);
        dbDelta($sql_community);        // Create AI & Waste Classification tables
        $this->create_ai_database_tables();
        
        // Insert sample data
        $this->insert_sample_data();
        
        // Insert quiz and challenge sample data
        $this->insert_quiz_challenge_sample_data();
    }
      /**
     * Create AI & Waste Classification database tables
     */
    private function create_ai_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // AI Classifications table
        $ai_classifications_table = $wpdb->prefix . 'env_ai_classifications';
        $sql_ai_classifications = "CREATE TABLE $ai_classifications_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            image_url varchar(500) DEFAULT NULL,
            image_hash varchar(64) DEFAULT NULL,
            category varchar(100) NOT NULL,
            subcategory varchar(100) DEFAULT NULL,
            confidence_score decimal(3,2) NOT NULL DEFAULT 0.00,
            ai_response longtext DEFAULT NULL,
            disposal_recommendations longtext DEFAULT NULL,
            processing_time decimal(5,3) DEFAULT NULL,
            api_provider varchar(50) DEFAULT 'openai',
            model_version varchar(50) DEFAULT NULL,
            status enum('pending','completed','failed','reviewing') DEFAULT 'pending',
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY category (category),
            KEY confidence_score (confidence_score),
            KEY status (status),
            KEY created_at (created_at),
            UNIQUE KEY user_image_hash (user_id, image_hash),
            FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
        ) $charset_collate;";
          // Classification Feedback table
        $feedback_table = $wpdb->prefix . 'env_classification_feedback';
        $sql_feedback = "CREATE TABLE $feedback_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            classification_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            rating tinyint(1) NOT NULL,
            feedback_type enum('helpful','incorrect','incomplete','excellent') DEFAULT NULL,
            comments text DEFAULT NULL,
            is_correct_classification boolean DEFAULT NULL,
            suggested_category varchar(100) DEFAULT NULL,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY classification_id (classification_id),
            KEY user_id (user_id),
            KEY rating (rating),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // User Gamification table
        $gamification_table = $wpdb->prefix . 'env_user_gamification';
        $sql_gamification = "CREATE TABLE $gamification_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            total_points bigint(20) unsigned NOT NULL DEFAULT 0,
            level int(11) unsigned NOT NULL DEFAULT 1,
            level_progress decimal(3,2) NOT NULL DEFAULT 0.00,
            classifications_count int(11) unsigned NOT NULL DEFAULT 0,
            accuracy_rate decimal(3,2) NOT NULL DEFAULT 0.00,
            streak_days int(11) unsigned NOT NULL DEFAULT 0,
            longest_streak int(11) unsigned NOT NULL DEFAULT 0,
            last_activity_date date DEFAULT NULL,
            weekly_classifications int(11) unsigned NOT NULL DEFAULT 0,
            monthly_classifications int(11) unsigned NOT NULL DEFAULT 0,
            total_feedback_given int(11) unsigned NOT NULL DEFAULT 0,
            helpful_feedback_count int(11) unsigned NOT NULL DEFAULT 0,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            KEY total_points (total_points),
            KEY level (level),
            KEY streak_days (streak_days),
            KEY last_activity_date (last_activity_date),
            FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
        ) $charset_collate;";
        
        // Achievements table
        $achievements_table = $wpdb->prefix . 'env_achievements';
        $sql_achievements = "CREATE TABLE $achievements_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            achievement_key varchar(100) NOT NULL,
            name varchar(200) NOT NULL,
            description text NOT NULL,
            icon varchar(100) DEFAULT NULL,
            category enum('milestone','diversity','precision','consistency','timing','community','special') NOT NULL,
            points_reward int(11) unsigned NOT NULL DEFAULT 0,
            requirement_type enum('count','percentage','streak','score','time') NOT NULL,
            requirement_value int(11) unsigned NOT NULL,
            requirement_data json DEFAULT NULL,
            is_active boolean NOT NULL DEFAULT TRUE,
            sort_order int(11) unsigned NOT NULL DEFAULT 0,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY achievement_key (achievement_key),
            KEY category (category),
            KEY is_active (is_active),
            KEY sort_order (sort_order)
        ) $charset_collate;";
        
        // User Achievements table
        $user_achievements_table = $wpdb->prefix . 'env_user_achievements';
        $sql_user_achievements = "CREATE TABLE $user_achievements_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            achievement_id bigint(20) unsigned NOT NULL,
            progress decimal(5,2) NOT NULL DEFAULT 0.00,
            is_completed boolean NOT NULL DEFAULT FALSE,
            completed_at timestamp NULL DEFAULT NULL,
            notified boolean NOT NULL DEFAULT FALSE,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_achievement (user_id, achievement_id),
            KEY user_id (user_id),
            KEY achievement_id (achievement_id),
            KEY is_completed (is_completed),
            KEY completed_at (completed_at),
            FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
            FOREIGN KEY (achievement_id) REFERENCES $achievements_table(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // Challenges table
        $challenges_table = $wpdb->prefix . 'env_challenges';
        $sql_challenges = "CREATE TABLE $challenges_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            challenge_key varchar(100) NOT NULL,
            name varchar(200) NOT NULL,
            description text NOT NULL,
            type enum('daily','weekly','monthly','special') NOT NULL,
            category varchar(100) DEFAULT NULL,
            target_value int(11) unsigned NOT NULL,
            points_reward int(11) unsigned NOT NULL DEFAULT 0,
            bonus_multiplier decimal(3,2) NOT NULL DEFAULT 1.00,
            start_date date NOT NULL,
            end_date date NOT NULL,
            is_active boolean NOT NULL DEFAULT TRUE,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY challenge_key_date (challenge_key, start_date),
            KEY type (type),
            KEY is_active (is_active),
            KEY start_date (start_date),
            KEY end_date (end_date)
        ) $charset_collate;";
        
        // User Challenges table
        $user_challenges_table = $wpdb->prefix . 'env_user_challenges';
        $sql_user_challenges = "CREATE TABLE $user_challenges_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            challenge_id bigint(20) unsigned NOT NULL,
            progress int(11) unsigned NOT NULL DEFAULT 0,
            is_completed boolean NOT NULL DEFAULT FALSE,
            completed_at timestamp NULL DEFAULT NULL,
            points_earned int(11) unsigned NOT NULL DEFAULT 0,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_challenge (user_id, challenge_id),
            KEY user_id (user_id),
            KEY challenge_id (challenge_id),
            KEY is_completed (is_completed),
            KEY completed_at (completed_at),
            FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
            FOREIGN KEY (challenge_id) REFERENCES $challenges_table(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // AI Service Config table
        $service_config_table = $wpdb->prefix . 'env_ai_service_config';
        $sql_service_config = "CREATE TABLE $service_config_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            service_name varchar(100) NOT NULL,
            api_endpoint varchar(500) NOT NULL,
            api_key_hash varchar(255) DEFAULT NULL,
            model_name varchar(100) DEFAULT NULL,
            max_requests_per_minute int(11) unsigned NOT NULL DEFAULT 60,
            max_requests_per_day int(11) unsigned NOT NULL DEFAULT 1000,
            timeout_seconds int(11) unsigned NOT NULL DEFAULT 30,
            is_active boolean NOT NULL DEFAULT TRUE,
            last_used_at timestamp NULL DEFAULT NULL,
            total_requests bigint(20) unsigned NOT NULL DEFAULT 0,
            successful_requests bigint(20) unsigned NOT NULL DEFAULT 0,
            failed_requests bigint(20) unsigned NOT NULL DEFAULT 0,
            average_response_time decimal(5,3) DEFAULT NULL,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY service_name (service_name),
            KEY is_active (is_active),
            KEY last_used_at (last_used_at)
        ) $charset_collate;";
        
        // AI Usage Log table
        $usage_log_table = $wpdb->prefix . 'env_ai_usage_log';
        $sql_usage_log = "CREATE TABLE $usage_log_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            service_name varchar(100) NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            request_type varchar(50) NOT NULL,
            response_time decimal(5,3) DEFAULT NULL,
            tokens_used int(11) unsigned DEFAULT NULL,
            cost_estimate decimal(8,4) DEFAULT NULL,
            success boolean NOT NULL DEFAULT FALSE,
            error_message text DEFAULT NULL,
            request_date date NOT NULL,
            request_hour tinyint(2) unsigned NOT NULL,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY service_name (service_name),
            KEY user_id (user_id),
            KEY request_date (request_date),
            KEY request_hour (request_hour),
            KEY success (success),
            KEY created_at (created_at),            FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE SET NULL
        ) $charset_collate;";
        
        // Enhanced Challenge System Tables
        $env_challenges_table = $wpdb->prefix . 'env_challenges';
        $sql_env_challenges = "CREATE TABLE $env_challenges_table (
            challenge_id int(11) NOT NULL AUTO_INCREMENT,
            challenge_name varchar(255) NOT NULL,
            challenge_description text,
            challenge_type enum('daily', 'weekly', 'monthly', 'special', 'seasonal') NOT NULL,
            difficulty_level enum('easy', 'medium', 'hard', 'expert') DEFAULT 'medium',
            category enum('carbon', 'waste', 'energy', 'water', 'transport', 'consumption', 'education', 'social') DEFAULT 'carbon',
            requirements json,
            rewards json,
            start_date datetime NOT NULL,
            end_date datetime NOT NULL,
            max_participants int(11) DEFAULT 0,
            current_participants int(11) DEFAULT 0,
            is_active boolean DEFAULT TRUE,
            auto_generated boolean DEFAULT FALSE,
            created_by int(11),
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (challenge_id),
            KEY idx_active_dates (is_active, start_date, end_date),
            KEY idx_type_category (challenge_type, category),
            KEY idx_difficulty (difficulty_level)
        ) $charset_collate;";
        
        $env_challenge_participants_table = $wpdb->prefix . 'env_challenge_participants';
        $sql_env_challenge_participants = "CREATE TABLE $env_challenge_participants_table (
            participation_id int(11) NOT NULL AUTO_INCREMENT,
            challenge_id int(11) NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            joined_at timestamp DEFAULT CURRENT_TIMESTAMP,
            progress json,
            is_completed boolean DEFAULT FALSE,
            completed_at timestamp NULL DEFAULT NULL,
            points_earned int(11) DEFAULT 0,
            badge_earned varchar(255) DEFAULT NULL,
            notes text,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (participation_id),
            UNIQUE KEY unique_participation (challenge_id, user_id),
            KEY idx_user_challenges (user_id),
            KEY idx_completion_status (is_completed),
            FOREIGN KEY (challenge_id) REFERENCES $env_challenges_table(challenge_id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
        ) $charset_collate;";
          // Quiz System Tables
        $quiz_categories_table = $wpdb->prefix . 'quiz_categories';
        $sql_quiz_categories = "CREATE TABLE $quiz_categories_table (
            category_id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            description text,
            icon varchar(50),
            difficulty_level enum('beginner','intermediate','advanced') DEFAULT 'beginner',
            is_active tinyint(1) DEFAULT 1,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (category_id),
            UNIQUE KEY unique_category_name (name)
        ) $charset_collate;";

        $quiz_questions_table = $wpdb->prefix . 'quiz_questions';
        $sql_quiz_questions = "CREATE TABLE $quiz_questions_table (
            question_id int(11) NOT NULL AUTO_INCREMENT,
            category_id int(11) NOT NULL,
            question_text text NOT NULL,
            question_type enum('multiple_choice','true_false','fill_blank') DEFAULT 'multiple_choice',
            options json,
            correct_answer varchar(500) NOT NULL,
            explanation text,
            difficulty enum('easy','medium','hard') DEFAULT 'medium',
            points int(11) DEFAULT 10,
            is_active tinyint(1) DEFAULT 1,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (question_id),
            KEY idx_category (category_id),
            KEY idx_difficulty (difficulty),
            FOREIGN KEY (category_id) REFERENCES $quiz_categories_table(category_id) ON DELETE CASCADE
        ) $charset_collate;";

        $quiz_sessions_table = $wpdb->prefix . 'quiz_sessions';
        $sql_quiz_sessions = "CREATE TABLE $quiz_sessions_table (
            session_id int(11) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            category_id int(11),
            total_questions int(11) NOT NULL,
            correct_answers int(11) DEFAULT 0,
            total_points int(11) DEFAULT 0,
            completion_time int(11),
            started_at timestamp DEFAULT CURRENT_TIMESTAMP,
            completed_at timestamp NULL,
            status enum('active','completed','abandoned') DEFAULT 'active',
            PRIMARY KEY (session_id),
            KEY idx_user_sessions (user_id),
            KEY idx_category_sessions (category_id),
            KEY idx_session_status (status),
            FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES $quiz_categories_table(category_id) ON DELETE SET NULL
        ) $charset_collate;";

        $quiz_responses_table = $wpdb->prefix . 'quiz_responses';
        $sql_quiz_responses = "CREATE TABLE $quiz_responses_table (
            response_id int(11) NOT NULL AUTO_INCREMENT,
            session_id int(11) NOT NULL,
            question_id int(11) NOT NULL,
            user_answer varchar(500),
            is_correct tinyint(1) DEFAULT 0,
            points_earned int(11) DEFAULT 0,
            time_taken int(11),
            answered_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (response_id),
            KEY idx_session_responses (session_id),
            KEY idx_question_responses (question_id),
            FOREIGN KEY (session_id) REFERENCES $quiz_sessions_table(session_id) ON DELETE CASCADE,
            FOREIGN KEY (question_id) REFERENCES $quiz_questions_table(question_id) ON DELETE CASCADE
        ) $charset_collate;";

        // Create tables using dbDelta
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        dbDelta($sql_ai_classifications);
        dbDelta($sql_feedback);
        dbDelta($sql_gamification);
        dbDelta($sql_achievements);
        dbDelta($sql_user_achievements);
        dbDelta($sql_challenges);        dbDelta($sql_user_challenges);
        dbDelta($sql_service_config);
        dbDelta($sql_usage_log);
        
        // Quiz System Tables
        dbDelta($sql_quiz_categories);
        dbDelta($sql_quiz_questions);
        dbDelta($sql_quiz_sessions);
        dbDelta($sql_quiz_responses);
        
        // Enhanced Challenge System Tables
        dbDelta($sql_env_challenges);
        dbDelta($sql_env_challenge_participants);
        
        // Insert default achievements and sample data
        $this->insert_ai_sample_data();
    }
    
    /**
     * Insert AI sample data and default achievements
     */
    private function insert_ai_sample_data() {
        global $wpdb;
        
        $achievements_table = $wpdb->prefix . 'env_achievements';
        $service_config_table = $wpdb->prefix . 'env_ai_service_config';
        
        // Check if achievements already exist
        $existing_achievements = $wpdb->get_var("SELECT COUNT(*) FROM $achievements_table");
        
        if ($existing_achievements == 0) {
            // Insert default achievements
            $default_achievements = array(
                array(
                    'achievement_key' => 'first_classification',
                    'name' => 'First Step',
                    'description' => 'Complete your first waste classification',
                    'icon' => '🏁',
                    'category' => 'milestone',
                    'points_reward' => 50,
                    'requirement_type' => 'count',
                    'requirement_value' => 1
                ),
                array(
                    'achievement_key' => 'classifications_10',
                    'name' => 'Getting Started',
                    'description' => 'Complete 10 waste classifications',
                    'icon' => '📸',
                    'category' => 'milestone',
                    'points_reward' => 100,
                    'requirement_type' => 'count',
                    'requirement_value' => 10
                ),
                array(
                    'achievement_key' => 'classifications_50',
                    'name' => 'Waste Detective',
                    'description' => 'Complete 50 waste classifications',
                    'icon' => '🔍',
                    'category' => 'milestone',
                    'points_reward' => 300,
                    'requirement_type' => 'count',
                    'requirement_value' => 50
                ),
                array(
                    'achievement_key' => 'recycling_expert',
                    'name' => 'Recycling Expert',
                    'description' => 'Classify 25 recyclable items correctly',
                    'icon' => '♻️',
                    'category' => 'diversity',
                    'points_reward' => 250,
                    'requirement_type' => 'count',
                    'requirement_value' => 25
                ),
                array(
                    'achievement_key' => 'accuracy_80',
                    'name' => 'Sharp Eye',
                    'description' => 'Maintain 80% accuracy over 20 classifications',
                    'icon' => '👁️',
                    'category' => 'precision',
                    'points_reward' => 300,
                    'requirement_type' => 'percentage',
                    'requirement_value' => 80
                ),                array(
                    'achievement_key' => 'daily_classifier',
                    'name' => 'Daily Classifier',
                    'description' => 'Classify at least 1 item for 7 consecutive days',
                    'icon' => '📅',
                    'category' => 'consistency',
                    'points_reward' => 200,
                    'requirement_type' => 'streak',
                    'requirement_value' => 7
                ),
                array(
                    'achievement_key' => 'feedback_giver',
                    'name' => 'Helpful Helper',
                    'description' => 'Provide feedback on 20 classifications',
                    'icon' => '💬',
                    'category' => 'community',
                    'points_reward' => 150,
                    'requirement_type' => 'count',
                    'requirement_value' => 20
                ),
                array(
                    'achievement_key' => 'level_5',
                    'name' => 'Expert Classifier',
                    'description' => 'Reach level 5',
                    'icon' => '⭐',
                    'category' => 'milestone',
                    'points_reward' => 500,
                    'requirement_type' => 'level',
                    'requirement_value' => 5
                )
            );
            
            foreach ($default_achievements as $achievement) {
                $wpdb->insert($achievements_table, $achievement);
            }
        }
        
        // Check if AI service config already exists
        $existing_configs = $wpdb->get_var("SELECT COUNT(*) FROM $service_config_table");
        
        if ($existing_configs == 0) {
            // Insert default AI service configurations
            $default_configs = array(
                array(
                    'service_name' => 'openai',
                    'api_endpoint' => 'https://api.openai.com/v1/chat/completions',
                    'model_name' => 'gpt-4-vision-preview',
                    'max_requests_per_minute' => 60,
                    'max_requests_per_day' => 1000,
                    'timeout_seconds' => 30,
                    'is_active' => true
                ),
                array(
                    'service_name' => 'google_vision',
                    'api_endpoint' => 'https://vision.googleapis.com/v1/images:annotate',
                    'model_name' => 'vision-v1',
                    'max_requests_per_minute' => 100,
                    'max_requests_per_day' => 2000,
                    'timeout_seconds' => 20,
                    'is_active' => false
                )
            );
            
            foreach ($default_configs as $config) {
                $wpdb->insert($service_config_table, $config);
            }
        }
    }
    
    /**
     * Admin AI Waste Classification page
     */
    public function admin_ai_waste_classification_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('AI Waste Classification Management', 'env-data-dashboard'); ?></h1>
            
            <div class="env-ai-classification-admin">
                <!-- Statistics Cards -->
                <div class="env-admin-stats-row">
                    <div class="env-admin-stat-card">
                        <h3><?php _e('Total Classifications', 'env-data-dashboard'); ?></h3>
                        <div class="env-admin-stat-number" id="total-classifications">-</div>
                    </div>
                    <div class="env-admin-stat-card">
                        <h3><?php _e('Active Users', 'env-data-dashboard'); ?></h3>
                        <div class="env-admin-stat-number" id="active-users">-</div>
                    </div>
                    <div class="env-admin-stat-card">
                        <h3><?php _e('Average Accuracy', 'env-data-dashboard'); ?></h3>
                        <div class="env-admin-stat-number" id="average-accuracy">-%</div>
                    </div>
                    <div class="env-admin-stat-card">
                        <h3><?php _e('Total Carbon Tracked', 'env-data-dashboard'); ?></h3>
                        <div class="env-admin-stat-number" id="total-carbon">- kg</div>
                    </div>
                </div>
                
                <!-- Configuration Section -->
                <div class="env-admin-section">
                    <h2><?php _e('AI Service Configuration', 'env-data-dashboard'); ?></h2>
                    <form id="ai-config-form" method="post">
                        <?php wp_nonce_field('env_ai_config_nonce', 'ai_config_nonce'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="ai_service_provider"><?php _e('AI Service Provider', 'env-data-dashboard'); ?></label>
                                </th>
                                <td>
                                    <select id="ai_service_provider" name="ai_service_provider">
                                        <option value="openai"><?php _e('OpenAI GPT-4 Vision', 'env-data-dashboard'); ?></option>
                                        <option value="google_vision"><?php _e('Google Cloud Vision', 'env-data-dashboard'); ?></option>
                                        <option value="custom"><?php _e('Custom API', 'env-data-dashboard'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="api_key"><?php _e('API Key', 'env-data-dashboard'); ?></label>
                                </th>
                                <td>
                                    <input type="password" id="api_key" name="api_key" class="regular-text" 
                                           placeholder="<?php _e('Enter your API key', 'env-data-dashboard'); ?>" />
                                    <p class="description"><?php _e('Your API key will be encrypted and stored securely.', 'env-data-dashboard'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="max_requests_per_day"><?php _e('Daily Request Limit', 'env-data-dashboard'); ?></label>
                                </th>
                                <td>
                                    <input type="number" id="max_requests_per_day" name="max_requests_per_day" 
                                           value="1000" min="1" max="10000" class="small-text" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="confidence_threshold"><?php _e('Confidence Threshold', 'env-data-dashboard'); ?></label>
                                </th>
                                <td>
                                    <input type="range" id="confidence_threshold" name="confidence_threshold" 
                                           min="0.1" max="1.0" step="0.1" value="0.7" />
                                    <span id="confidence_value">0.7</span>
                                    <p class="description"><?php _e('Minimum confidence score for classifications to be accepted.', 'env-data-dashboard'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <input type="submit" name="save_ai_config" id="save_ai_config" 
                                   class="button-primary" value="<?php _e('Save Configuration', 'env-data-dashboard'); ?>" />
                            <button type="button" id="test_ai_connection" class="button">
                                <?php _e('Test Connection', 'env-data-dashboard'); ?>
                            </button>
                        </p>
                    </form>
                </div>
                
                <!-- Classifications Management -->
                <div class="env-admin-section">
                    <h2><?php _e('Recent Classifications', 'env-data-dashboard'); ?></h2>
                    <div class="env-admin-controls">
                        <input type="text" id="search-classifications" placeholder="<?php _e('Search classifications...', 'env-data-dashboard'); ?>" />
                        <select id="filter-category">
                            <option value=""><?php _e('All Categories', 'env-data-dashboard'); ?></option>
                            <option value="recyclable"><?php _e('Recyclable', 'env-data-dashboard'); ?></option>
                            <option value="organic"><?php _e('Organic', 'env-data-dashboard'); ?></option>
                            <option value="hazardous"><?php _e('Hazardous', 'env-data-dashboard'); ?></option>
                            <option value="general"><?php _e('General Waste', 'env-data-dashboard'); ?></option>
                        </select>
                        <button type="button" id="export-classifications" class="button">
                            <?php _e('Export Data', 'env-data-dashboard'); ?>
                        </button>
                    </div>
                    
                    <table class="wp-list-table widefat fixed striped" id="classifications-table">
                        <thead>
                            <tr>
                                <th><?php _e('Image', 'env-data-dashboard'); ?></th>
                                <th><?php _e('User', 'env-data-dashboard'); ?></th>
                                <th><?php _e('Category', 'env-data-dashboard'); ?></th>
                                <th><?php _e('Confidence', 'env-data-dashboard'); ?></th>
                                <th><?php _e('Feedback', 'env-data-dashboard'); ?></th>
                                <th><?php _e('Date', 'env-data-dashboard'); ?></th>
                                <th><?php _e('Actions', 'env-data-dashboard'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="classifications-data-table">
                            <!-- Data will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Gamification Management -->
                <div class="env-admin-section">
                    <h2><?php _e('Gamification Settings', 'env-data-dashboard'); ?></h2>
                    <form id="gamification-settings-form" method="post">
                        <?php wp_nonce_field('env_gamification_nonce', 'gamification_nonce'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="points_per_classification"><?php _e('Points per Classification', 'env-data-dashboard'); ?></label>
                                </th>
                                <td>
                                    <input type="number" id="points_per_classification" name="points_per_classification" 
                                           value="10" min="1" max="100" class="small-text" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="accuracy_bonus_multiplier"><?php _e('Accuracy Bonus Multiplier', 'env-data-dashboard'); ?></label>
                                </th>
                                <td>
                                    <input type="number" id="accuracy_bonus_multiplier" name="accuracy_bonus_multiplier" 
                                           value="1.5" min="1" max="5" step="0.1" class="small-text" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="enable_leaderboard"><?php _e('Enable Leaderboard', 'env-data-dashboard'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" id="enable_leaderboard" name="enable_leaderboard" value="1" checked />
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <input type="submit" name="save_gamification_settings" id="save_gamification_settings" 
                                   class="button-primary" value="<?php _e('Save Settings', 'env-data-dashboard'); ?>" />
                        </p>
                    </form>
                </div>
                
                <div class="env-charts-section">
                    <div class="env-chart-container">
                        <h3><?php _e('Classification Trends', 'env-data-dashboard'); ?></h3>
                        <canvas id="classifications-chart"></canvas>
                    </div>
                    
                    <div class="env-chart-container">
                        <h3><?php _e('User Engagement', 'env-data-dashboard'); ?></h3>
                        <canvas id="carbon-footprint-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Admin air quality page
     */
    public function admin_air_quality_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Air Quality Management', 'env-data-dashboard'); ?></h1>
            
            <div class="env-air-quality-management">
                <div class="env-location-controls">
                    <button class="button button-primary" id="refresh-air-data">
                        <?php _e('Refresh Air Quality Data', 'env-data-dashboard'); ?>
                    </button>
                    <button class="button" id="add-location">
                        <?php _e('Add New Location', 'env-data-dashboard'); ?>
                    </button>
                </div>
                
                <div class="env-air-quality-table">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Location', 'env-data-dashboard'); ?></th>
                                <th><?php _e('AQI', 'env-data-dashboard'); ?></th>
                                <th><?php _e('PM2.5', 'env-data-dashboard'); ?></th>
                                <th><?php _e('PM10', 'env-data-dashboard'); ?></th>
                                <th><?php _e('Quality Level', 'env-data-dashboard'); ?></th>
                                <th><?php _e('Last Updated', 'env-data-dashboard'); ?></th>
                                <th><?php _e('Actions', 'env-data-dashboard'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="air-quality-data-table">
                            <!-- Data will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Admin weather page
     */
    public function admin_weather_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Weather Data Management', 'env-data-dashboard'); ?></h1>
            
            <div class="env-weather-management">
                <div class="env-weather-controls">
                    <button class="button button-primary" id="refresh-weather-data">
                        <?php _e('Refresh Weather Data', 'env-data-dashboard'); ?>
                    </button>
                </div>
                
                <div class="env-weather-table">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Location', 'env-data-dashboard'); ?></th>
                                <th><?php _e('Temperature', 'env-data-dashboard'); ?></th>
                                <th><?php _e('Humidity', 'env-data-dashboard'); ?></th>
                                <th><?php _e('Pressure', 'env-data-dashboard'); ?></th>
                                <th><?php _e('Wind Speed', 'env-data-dashboard'); ?></th>
                                <th><?php _e('Condition', 'env-data-dashboard'); ?></th>
                                <th><?php _e('Last Updated', 'env-data-dashboard'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="weather-data-table">
                            <!-- Data will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Admin carbon tracking page
     */
    public function admin_carbon_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Carbon Footprint Tracking', 'env-data-dashboard'); ?></h1>
            
            <div class="env-carbon-management">
                <div class="env-carbon-stats">
                    <div class="env-stat-card">
                        <h3><?php _e('Total Users Tracking', 'env-data-dashboard'); ?></h3>
                        <div class="env-stat-number" id="carbon-users-count">-</div>
                    </div>
                    
                    <div class="env-stat-card">
                        <h3><?php _e('Total Carbon Tracked', 'env-data-dashboard'); ?></h3>
                        <div class="env-stat-number" id="total-carbon-tracked">- kg</div>
                    </div>
                    
                    <div class="env-stat-card">
                        <h3><?php _e('Average Per User', 'env-data-dashboard'); ?></h3>
                        <div class="env-stat-number" id="average-carbon-per-user">- kg</div>
                    </div>
                </div>
                
                <div class="env-carbon-chart">
                    <h3><?php _e('Carbon Footprint by Category', 'env-data-dashboard'); ?></h3>
                    <canvas id="carbon-category-chart"></canvas>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Admin community stats page
     */
    public function admin_community_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Community Environmental Statistics', 'env-data-dashboard'); ?></h1>
            
            <div class="env-community-management">
                <div class="env-community-stats">
                    <div class="env-stat-card">
                        <h3><?php _e('Carbon Saved Today', 'env-data-dashboard'); ?></h3>
                        <div class="env-stat-number" id="community-carbon-today">- kg</div>
                    </div>
                    
                    <div class="env-stat-card">
                        <h3><?php _e('Trees Planted', 'env-data-dashboard'); ?></h3>
                        <div class="env-stat-number" id="community-trees">-</div>
                    </div>
                    
                    <div class="env-stat-card">
                        <h3><?php _e('Waste Recycled', 'env-data-dashboard'); ?></h3>
                        <div class="env-stat-number" id="community-waste">- kg</div>
                    </div>
                    
                    <div class="env-stat-card">
                        <h3><?php _e('Energy Saved', 'env-data-dashboard'); ?></h3>
                        <div class="env-stat-number" id="community-energy">- kWh</div>
                    </div>
                </div>
                
                <div class="env-community-chart">
                    <h3><?php _e('Community Impact Over Time', 'env-data-dashboard'); ?></h3>
                    <canvas id="community-impact-chart"></canvas>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Admin settings page
     */
    public function admin_settings_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        
        $settings = $this->get_settings();
        ?>
        <div class="wrap">
            <h1><?php _e('Environmental Dashboard Settings', 'env-data-dashboard'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('env_dashboard_settings', 'env_dashboard_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Air Quality API Key', 'env-data-dashboard'); ?></th>
                        <td>
                            <input type="text" name="air_quality_api_key" 
                                   value="<?php echo esc_attr($settings['air_quality_api_key']); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php _e('API key for air quality data service', 'env-data-dashboard'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Weather API Key', 'env-data-dashboard'); ?></th>
                        <td>
                            <input type="text" name="weather_api_key" 
                                   value="<?php echo esc_attr($settings['weather_api_key']); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php _e('API key for weather data service', 'env-data-dashboard'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Default Location', 'env-data-dashboard'); ?></th>
                        <td>
                            <input type="text" name="default_location" 
                                   value="<?php echo esc_attr($settings['default_location']); ?>" 
                                   class="regular-text" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Default Latitude', 'env-data-dashboard'); ?></th>
                        <td>
                            <input type="text" name="default_latitude" 
                                   value="<?php echo esc_attr($settings['default_latitude']); ?>" 
                                   class="regular-text" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Default Longitude', 'env-data-dashboard'); ?></th>
                        <td>
                            <input type="text" name="default_longitude" 
                                   value="<?php echo esc_attr($settings['default_longitude']); ?>" 
                                   class="regular-text" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Update Frequency', 'env-data-dashboard'); ?></th>
                        <td>
                            <select name="update_frequency">
                                <option value="hourly" <?php selected($settings['update_frequency'], 'hourly'); ?>>
                                    <?php _e('Hourly', 'env-data-dashboard'); ?>
                                </option>
                                <option value="twicedaily" <?php selected($settings['update_frequency'], 'twicedaily'); ?>>
                                    <?php _e('Twice Daily', 'env-data-dashboard'); ?>
                                </option>
                                <option value="daily" <?php selected($settings['update_frequency'], 'daily'); ?>>
                                    <?php _e('Daily', 'env-data-dashboard'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Enable Notifications', 'env-data-dashboard'); ?></th>
                        <td>
                            <input type="checkbox" name="enable_notifications" value="1" 
                                   <?php checked($settings['enable_notifications'], true); ?> />
                            <label><?php _e('Send notifications for air quality alerts', 'env-data-dashboard'); ?></label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Get settings
     */
    private function get_settings() {
        return array(
            'air_quality_api_key' => get_option('env_dashboard_air_quality_api_key', ''),
            'weather_api_key' => get_option('env_dashboard_weather_api_key', ''),
            'default_location' => get_option('env_dashboard_default_location', 'Ho Chi Minh City'),
            'default_latitude' => get_option('env_dashboard_default_latitude', '10.8231'),
            'default_longitude' => get_option('env_dashboard_default_longitude', '106.6297'),
            'update_frequency' => get_option('env_dashboard_update_frequency', 'hourly'),
            'enable_notifications' => get_option('env_dashboard_enable_notifications', true),
            'dashboard_theme' => get_option('env_dashboard_dashboard_theme', 'default')
        );
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        if (!wp_verify_nonce($_POST['env_dashboard_settings_nonce'], 'env_dashboard_settings')) {
            return;
        }
        
        $settings = array(
            'air_quality_api_key',
            'weather_api_key',
            'default_location',
            'default_latitude',
            'default_longitude',
            'update_frequency'
        );
        
        foreach ($settings as $setting) {
            if (isset($_POST[$setting])) {
                update_option('env_dashboard_' . $setting, sanitize_text_field($_POST[$setting]));
            }
        }
        
        update_option('env_dashboard_enable_notifications', isset($_POST['enable_notifications']));
        
        echo '<div class="notice notice-success"><p>' . __('Settings saved successfully.', 'env-data-dashboard') . '</p></div>';
    }
    
    // AJAX Handlers
    public function ajax_get_air_quality_data() {
        check_ajax_referer('env_dashboard_nonce', 'nonce');
        
        if (class_exists('Air_Quality_API')) {
            $air_quality_api = new Air_Quality_API();
            $data = $air_quality_api->get_latest_data();
            wp_send_json_success($data);
        } else {
            wp_send_json_error('Air Quality API class not found');
        }
    }
    
    public function ajax_get_weather_data() {
        check_ajax_referer('env_dashboard_nonce', 'nonce');
        
        if (class_exists('Weather_API')) {
            $weather_api = new Weather_API();
            $data = $weather_api->get_latest_data();
            wp_send_json_success($data);
        } else {
            wp_send_json_error('Weather API class not found');
        }
    }
    
    public function ajax_save_carbon_footprint() {
        check_ajax_referer('env_dashboard_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }
        
        if (class_exists('Carbon_Footprint_Tracker')) {
            $carbon_tracker = new Carbon_Footprint_Tracker();
            $result = $carbon_tracker->save_footprint_data($_POST);
            
            if ($result) {
                wp_send_json_success('Carbon footprint data saved successfully');
            } else {
                wp_send_json_error('Failed to save carbon footprint data');
            }
        } else {
            wp_send_json_error('Carbon Footprint Tracker class not found');
        }
    }
    
    public function ajax_get_user_dashboard_data() {
        check_ajax_referer('env_dashboard_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }
        
        if (class_exists('Personal_Environmental_Dashboard')) {
            $dashboard = new Personal_Environmental_Dashboard();
            $data = $dashboard->get_user_dashboard_data(get_current_user_id());
            wp_send_json_success($data);
        } else {
            wp_send_json_error('Personal Dashboard class not found');
        }
    }
    
    public function ajax_get_community_stats() {
        check_ajax_referer('env_dashboard_nonce', 'nonce');
        
        if (class_exists('Community_Environmental_Stats')) {
            $community_stats = new Community_Environmental_Stats();
            $data = $community_stats->get_community_statistics();
            wp_send_json_success($data);
        } else {
            wp_send_json_error('Community Stats class not found');
        }
    }
    
    // Cron job handlers
    public function update_air_quality_data() {
        if (class_exists('Air_Quality_API')) {
            $air_quality_api = new Air_Quality_API();
            $air_quality_api->fetch_and_store_data();
        }
    }
    
    public function update_weather_data() {
        if (class_exists('Weather_API')) {
            $weather_api = new Weather_API();
            $weather_api->fetch_and_store_data();
        }
    }
    
    public function calculate_community_stats() {
        if (class_exists('Community_Environmental_Stats')) {
            $community_stats = new Community_Environmental_Stats();
            $community_stats->calculate_daily_statistics();
        }    }
    
    // Additional AJAX Handlers for Enhanced Functionality
    public function ajax_get_carbon_footprint_data() {
        check_ajax_referer('env_dashboard_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }
        
        if (class_exists('Carbon_Footprint_Tracker')) {
            $carbon_tracker = new Carbon_Footprint_Tracker();
            $user_id = get_current_user_id();
            $period = sanitize_text_field($_POST['period'] ?? 'month');
            $data = $carbon_tracker->get_user_carbon_data($user_id, $period);
            wp_send_json_success($data);
        } else {
            wp_send_json_error('Carbon Footprint Tracker class not found');
        }
    }
    
    public function ajax_get_personal_dashboard_data() {
        check_ajax_referer('env_dashboard_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }
        
        if (class_exists('Personal_Environmental_Dashboard')) {
            $dashboard = new Personal_Environmental_Dashboard();
            $user_id = get_current_user_id();
            $data = $dashboard->get_complete_dashboard_data($user_id);
            wp_send_json_success($data);
        } else {
            wp_send_json_error('Personal Dashboard class not found');
        }
    }
    
    public function ajax_get_charts_data() {
        check_ajax_referer('env_dashboard_nonce', 'nonce');
        
        if (class_exists('Environmental_Data_Visualization')) {
            $visualization = new Environmental_Data_Visualization();
            $chart_type = sanitize_text_field($_POST['chart_type'] ?? 'air_quality_trends');
            $period = sanitize_text_field($_POST['period'] ?? 'week');
            $location = sanitize_text_field($_POST['location'] ?? '');
            
            $data = $visualization->get_chart_data($chart_type, $period, $location);
            wp_send_json_success($data);
        } else {
            wp_send_json_error('Data Visualization class not found');
        }
    }
    
    // Admin AJAX Handlers
    public function ajax_get_admin_dashboard_data() {
        check_ajax_referer('env_dashboard_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $dashboard_data = array(
            'total_users' => count_users()['total_users'],
            'active_users' => $this->get_active_users_count(),
            'total_carbon_entries' => $this->get_total_carbon_entries(),
            'average_carbon_footprint' => $this->get_average_carbon_footprint(),
            'air_quality_data_points' => $this->get_air_quality_data_count(),
            'weather_data_points' => $this->get_weather_data_count(),
            'community_goals_achieved' => $this->get_community_goals_achieved(),
            'system_status' => $this->get_system_status()
        );
        
        wp_send_json_success($dashboard_data);
    }
    
    public function ajax_save_environmental_settings() {
        check_ajax_referer('env_dashboard_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $settings = array(
            'default_location' => sanitize_text_field($_POST['default_location'] ?? ''),
            'update_frequency' => sanitize_text_field($_POST['update_frequency'] ?? 'hourly'),
            'enable_notifications' => !empty($_POST['enable_notifications']),
            'dashboard_theme' => sanitize_text_field($_POST['dashboard_theme'] ?? 'default'),
            'data_retention_days' => intval($_POST['data_retention_days'] ?? 365)
        );
        
        foreach ($settings as $key => $value) {
            update_option('env_dashboard_' . $key, $value);
        }
        
        wp_send_json_success('Settings saved successfully');
    }
    
    public function ajax_save_api_config() {
        check_ajax_referer('env_dashboard_admin_nonce', 'nonce');
        
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $api_keys = array(
            'air_quality_api_key' => sanitize_text_field($_POST['air_quality_api_key'] ?? ''),
            'weather_api_key' => sanitize_text_field($_POST['weather_api_key'] ?? ''),
            'geocoding_api_key' => sanitize_text_field($_POST['geocoding_api_key'] ?? '')
        );
        
        foreach ($api_keys as $key => $value) {
            update_option('env_dashboard_' . $key, $value);
        }
        
        wp_send_json_success('API configuration saved successfully');
    }
    
    public function ajax_test_api_connection() {
        check_ajax_referer('env_dashboard_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $api_type = sanitize_text_field($_POST['api_type'] ?? '');
        $test_results = array();
        
        if ($api_type === 'air_quality' && class_exists('Air_Quality_API')) {
            $air_quality_api = new Air_Quality_API();
            $test_results['air_quality'] = $air_quality_api->test_connection();
        }
        
        if ($api_type === 'weather' && class_exists('Weather_API')) {
            $weather_api = new Weather_API();
            $test_results['weather'] = $weather_api->test_connection();
        }
        
        wp_send_json_success($test_results);
    }
    
    public function ajax_clear_environmental_cache() {
        check_ajax_referer('env_dashboard_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Clear WordPress transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_env_dashboard_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_env_dashboard_%'");
        
        wp_send_json_success('Environmental cache cleared successfully');
    }
    
    public function ajax_reset_environmental_settings() {
        check_ajax_referer('env_dashboard_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Reset to default settings
        $default_settings = array(
            'default_location' => 'Ho Chi Minh City',
            'default_latitude' => '10.8231',
            'default_longitude' => '106.6297',
            'update_frequency' => 'hourly',
            'enable_notifications' => true,
            'dashboard_theme' => 'default',
            'data_retention_days' => 365
        );
        
        foreach ($default_settings as $key => $value) {
            update_option('env_dashboard_' . $key, $value);
        }
        
        wp_send_json_success('Settings reset to defaults successfully');
    }
    
    public function ajax_delete_environmental_data() {
        check_ajax_referer('env_dashboard_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $data_type = sanitize_text_field($_POST['data_type'] ?? '');
        $date_range = sanitize_text_field($_POST['date_range'] ?? '30');
        
        global $wpdb;
        $date_limit = date('Y-m-d H:i:s', strtotime("-{$date_range} days"));
        
        switch ($data_type) {
            case 'air_quality':
                $wpdb->delete(
                    $wpdb->prefix . 'env_air_quality_data',
                    array('recorded_at' => array('value' => $date_limit, 'compare' => '<')),
                    array('%s')
                );
                break;
                
            case 'weather':
                $wpdb->delete(
                    $wpdb->prefix . 'env_weather_data',
                    array('recorded_at' => array('value' => $date_limit, 'compare' => '<')),
                    array('%s')
                );
                break;
                
            case 'carbon_footprint':
                $wpdb->delete(
                    $wpdb->prefix . 'env_carbon_footprint',
                    array('created_at' => array('value' => $date_limit, 'compare' => '<')),
                    array('%s')
                );
                break;
        }
        
        wp_send_json_success('Environmental data deleted successfully');
    }
    
    public function ajax_generate_environmental_report() {
        check_ajax_referer('env_dashboard_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $report_type = sanitize_text_field($_POST['report_type'] ?? 'monthly');
        $start_date = sanitize_text_field($_POST['start_date'] ?? date('Y-m-01'));
        $end_date = sanitize_text_field($_POST['end_date'] ?? date('Y-m-t'));
        
        if (class_exists('Environmental_Database_Manager')) {
            $db_manager = new Environmental_Database_Manager();
            $report_data = $db_manager->generate_report($report_type, $start_date, $end_date);
            wp_send_json_success($report_data);
        } else {
            wp_send_json_error('Database Manager class not found');
        }
    }
    
    public function ajax_export_environmental_report() {
        check_ajax_referer('env_dashboard_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $export_format = sanitize_text_field($_POST['export_format'] ?? 'csv');
        $report_data = json_decode(stripslashes($_POST['report_data']), true);
        
        if (class_exists('Environmental_Database_Manager')) {
            $db_manager = new Environmental_Database_Manager();
            $export_url = $db_manager->export_report($report_data, $export_format);
            wp_send_json_success(array('download_url' => $export_url));
        } else {
            wp_send_json_error('Database Manager class not found');
        }
    }
    
    // Helper methods for admin dashboard
    private function get_active_users_count() {
        global $wpdb;
        $thirty_days_ago = date('Y-m-d H:i:s', strtotime('-30 days'));
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}env_carbon_footprint WHERE created_at > %s",
            $thirty_days_ago
        ));
    }
    
    private function get_total_carbon_entries() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}env_carbon_footprint");
    }
    
    private function get_average_carbon_footprint() {
        global $wpdb;
        return $wpdb->get_var("SELECT AVG(carbon_amount) FROM {$wpdb->prefix}env_carbon_footprint");
    }
    
    private function get_air_quality_data_count() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}env_air_quality_data");
    }
    
    private function get_weather_data_count() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}env_weather_data");
    }
    
    private function get_community_goals_achieved() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}env_user_goals WHERE status = 'completed'");
    }
    
    private function get_system_status() {
        return array(
            'database_status' => 'connected',
            'api_status' => $this->check_api_status(),
            'cache_status' => 'active',
            'cron_status' => wp_next_scheduled('env_dashboard_update_air_quality') ? 'active' : 'inactive'
        );
    }
    
    private function check_api_status() {
        $air_quality_key = get_option('env_dashboard_air_quality_api_key');
        $weather_key = get_option('env_dashboard_weather_api_key');
        
        return array(
            'air_quality' => !empty($air_quality_key) ? 'configured' : 'not_configured',
            'weather' => !empty($weather_key) ? 'configured' : 'not_configured'
        );
    }
    
    // Shortcode handlers
    public function air_quality_widget_shortcode($atts) {
        $atts = shortcode_atts(array(
            'location' => get_option('env_dashboard_default_location', 'Ho Chi Minh City'),
            'show_map' => 'true',
            'height' => '400px'
        ), $atts);
        
        if (class_exists('Environmental_Widgets')) {
            $widgets = new Environmental_Widgets();
            return $widgets->render_air_quality_widget($atts);
        }
        
        return '<p>' . __('Air Quality Widget not available', 'env-data-dashboard') . '</p>';
    }
    
    public function weather_widget_shortcode($atts) {
        $atts = shortcode_atts(array(
            'location' => get_option('env_dashboard_default_location', 'Ho Chi Minh City'),
            'show_forecast' => 'true',
            'days' => '5'
        ), $atts);
        
        if (class_exists('Environmental_Widgets')) {
            $widgets = new Environmental_Widgets();
            return $widgets->render_weather_widget($atts);
        }
        
        return '<p>' . __('Weather Widget not available', 'env-data-dashboard') . '</p>';
    }
    
    public function carbon_tracker_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_form' => 'true',
            'show_chart' => 'true',
            'period' => 'month'
        ), $atts);
        
        if (class_exists('Carbon_Footprint_Tracker')) {
            $tracker = new Carbon_Footprint_Tracker();
            return $tracker->render_tracker_widget($atts);
        }
        
        return '<p>' . __('Carbon Tracker not available', 'env-data-dashboard') . '</p>';
    }
    
    public function personal_dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your personal environmental dashboard.', 'env-data-dashboard') . '</p>';
        }
        
        $atts = shortcode_atts(array(
            'show_goals' => 'true',
            'show_progress' => 'true',
            'show_achievements' => 'true'
        ), $atts);
        
        if (class_exists('Personal_Environmental_Dashboard')) {
            $dashboard = new Personal_Environmental_Dashboard();
            return $dashboard->render_personal_dashboard($atts);
        }
        
        return '<p>' . __('Personal Dashboard not available', 'env-data-dashboard') . '</p>';
    }
      public function community_stats_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_chart' => 'true',
            'show_leaderboard' => 'true',
            'period' => 'week'
        ), $atts);
        
        if (class_exists('Community_Environmental_Stats')) {
            $community_stats = new Community_Environmental_Stats();
            return $community_stats->render_community_stats_widget($atts);
        }
        
        return '<p>' . __('Community Stats not available', 'env-data-dashboard') . '</p>';
    }

    // AI & Waste Classification Shortcodes
    public function waste_classifier_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_camera' => 'true',
            'show_upload' => 'true',
            'show_feedback' => 'true',
            'max_file_size' => '10MB'
        ), $atts);
        
        if (class_exists('Waste_Classification_Interface')) {
            $interface = Waste_Classification_Interface::get_instance();
            return $interface->render_classifier_interface($atts);
        }
        
        return '<p>' . __('Waste Classifier not available', 'env-data-dashboard') . '</p>';
    }

    public function classification_history_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your classification history.', 'env-data-dashboard') . '</p>';
        }

        $atts = shortcode_atts(array(
            'limit' => '20',
            'show_stats' => 'true',
            'show_images' => 'true'
        ), $atts);
        
        if (class_exists('Waste_Classification_Interface')) {
            $interface = Waste_Classification_Interface::get_instance();
            return $interface->render_history_interface($atts);
        }
        
        return '<p>' . __('Classification History not available', 'env-data-dashboard') . '</p>';
    }

    public function gamification_widget_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your achievements and progress.', 'env-data-dashboard') . '</p>';
        }

        $atts = shortcode_atts(array(
            'show_level' => 'true',
            'show_points' => 'true',
            'show_achievements' => 'true',
            'show_progress' => 'true'
        ), $atts);
        
        if (class_exists('Environmental_Gamification_System')) {
            $gamification = Environmental_Gamification_System::get_instance();
            return $gamification->render_gamification_widget($atts);
        }
        
        return '<p>' . __('Gamification Widget not available', 'env-data-dashboard') . '</p>';
    }

    public function achievements_display_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view achievements.', 'env-data-dashboard') . '</p>';
        }

        $atts = shortcode_atts(array(
            'category' => 'all',
            'show_locked' => 'true',
            'layout' => 'grid'
        ), $atts);
        
        if (class_exists('Environmental_Gamification_System')) {
            $gamification = Environmental_Gamification_System::get_instance();
            return $gamification->render_achievements_display($atts);
        }
        
        return '<p>' . __('Achievements Display not available', 'env-data-dashboard') . '</p>';
    }

    public function leaderboard_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => '10',
            'period' => 'all_time',
            'show_current_user' => 'true',
            'anonymous' => 'false'
        ), $atts);
        
        if (class_exists('Environmental_Gamification_System')) {
            $gamification = Environmental_Gamification_System::get_instance();
            return $gamification->render_leaderboard($atts);
        }
        
        return '<p>' . __('Leaderboard not available', 'env-data-dashboard') . '</p>';
    }

    // AI & Waste Classification AJAX Handlers
    public function ajax_classify_waste_image() {
        check_ajax_referer('env_dashboard_ai_nonce', 'nonce');
        
        if (!class_exists('AI_Service_Manager')) {
            wp_die(json_encode(array('success' => false, 'data' => 'AI Service not available')));
        }

        $ai_service = AI_Service_Manager::get_instance();
        $result = $ai_service->handle_image_classification_request();
        
        wp_die(json_encode($result));
    }

    public function ajax_submit_classification_feedback() {
        check_ajax_referer('env_dashboard_ai_nonce', 'nonce');
        
        if (!class_exists('AI_Service_Manager')) {
            wp_die(json_encode(array('success' => false, 'data' => 'AI Service not available')));
        }

        $ai_service = AI_Service_Manager::get_instance();
        $result = $ai_service->handle_feedback_submission();
        
        wp_die(json_encode($result));
    }

    public function ajax_get_classification_history() {
        check_ajax_referer('env_dashboard_ai_nonce', 'nonce');
        
        if (!class_exists('Waste_Classification_Interface')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Interface not available')));
        }

        $interface = Waste_Classification_Interface::get_instance();
        $result = $interface->get_user_classification_history();
        
        wp_die(json_encode($result));
    }

    public function ajax_clear_classification_history() {
        check_ajax_referer('env_dashboard_ai_nonce', 'nonce');
        
        if (!class_exists('Waste_Classification_Interface')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Interface not available')));
        }

        $interface = Waste_Classification_Interface::get_instance();
        $result = $interface->clear_user_classification_history();
        
        wp_die(json_encode($result));
    }

    public function ajax_update_user_gamification() {
        check_ajax_referer('env_dashboard_ai_nonce', 'nonce');
        
        if (!class_exists('Environmental_Gamification_System')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Gamification not available')));
        }

        $gamification = Environmental_Gamification_System::get_instance();
        $result = $gamification->update_user_progress();
        
        wp_die(json_encode($result));
    }

    public function ajax_get_gamification_data() {
        check_ajax_referer('env_dashboard_ai_nonce', 'nonce');
        
        if (!class_exists('Environmental_Gamification_System')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Gamification not available')));
        }

        $gamification = Environmental_Gamification_System::get_instance();
        $result = $gamification->get_user_gamification_data();
        
        wp_die(json_encode($result));
    }    public function ajax_get_leaderboard_data() {
        if (!class_exists('Environmental_Gamification_System')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Gamification not available')));
        }

        $gamification = Environmental_Gamification_System::get_instance();
        $result = $gamification->get_leaderboard_data();
        
        wp_die(json_encode($result));
    }
      /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        // Main Environmental Dashboard Menu
        add_menu_page(
            __('Environmental Dashboard', 'env-data-dashboard'),
            __('Environmental', 'env-data-dashboard'),
            'manage_options',
            'env-dashboard',
            array($this, 'admin_dashboard_page'),
            'dashicons-chart-area',
            25
        );
        
        // Submenu pages
        add_submenu_page(
            'env-dashboard',
            __('Dashboard', 'env-data-dashboard'),
            __('Dashboard', 'env-data-dashboard'),
            'manage_options',
            'env-dashboard',
            array($this, 'admin_dashboard_page')
        );
        
        add_submenu_page(
            'env-dashboard',
            __('Air Quality', 'env-data-dashboard'),
            __('Air Quality', 'env-data-dashboard'),
            'manage_options',
            'env-air-quality',
            array($this, 'admin_air_quality_page')
        );
        
        add_submenu_page(
            'env-dashboard',
            __('Weather Data', 'env-data-dashboard'),
            __('Weather', 'env-data-dashboard'),
            'manage_options',
            'env-weather',
            array($this, 'admin_weather_page')
        );
        
        add_submenu_page(
            'env-dashboard',
            __('Carbon Footprint', 'env-data-dashboard'),
            __('Carbon Tracking', 'env-data-dashboard'),
            'manage_options',
            'env-carbon',
            array($this, 'admin_carbon_page')
        );
        
        add_submenu_page(
            'env-dashboard',
            __('Community Stats', 'env-data-dashboard'),
            __('Community', 'env-data-dashboard'),
            'manage_options',
            'env-community',
            array($this, 'admin_community_page')
        );
        
        add_submenu_page(
            'env-dashboard',
            __('AI Waste Classification', 'env-data-dashboard'),
            __('Waste Classification', 'env-data-dashboard'),
            'manage_options',
            'env-ai-waste',
            array($this, 'admin_ai_waste_classification_page')
        );
    }
    
    /**
     * Admin AJAX: Get AI classification statistics
     */
    public function ajax_get_ai_classification_stats() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'waste_classification_admin_nonce')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Access denied')));
        }
        
        global $wpdb;
        
        $classifications_table = $wpdb->prefix . 'env_ai_classifications';
        $gamification_table = $wpdb->prefix . 'env_user_gamification';
        
        // Get total classifications
        $total_classifications = $wpdb->get_var("SELECT COUNT(*) FROM $classifications_table");
        
        // Get active users (users who classified in last 30 days)
        $active_users = $wpdb->get_var("
            SELECT COUNT(DISTINCT user_id) 
            FROM $classifications_table 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        // Get average accuracy
        $avg_accuracy = $wpdb->get_var("
            SELECT AVG(accuracy_rate) 
            FROM $gamification_table 
            WHERE accuracy_rate > 0
        ");
        
        // Estimate carbon impact (simplified calculation)
        $total_carbon = $total_classifications * 0.5; // Assume 0.5kg CO2 saved per classification
        
        wp_die(json_encode(array(
            'success' => true,
            'data' => array(
                'total_classifications' => $total_classifications ?: 0,
                'active_users' => $active_users ?: 0,
                'average_accuracy' => $avg_accuracy ? round($avg_accuracy, 1) : 0,
                'total_carbon' => round($total_carbon, 1)
            )
        )));
    }
    
    /**
     * Admin AJAX: Get recent classifications
     */
    public function ajax_get_recent_classifications() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'waste_classification_admin_nonce')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Access denied')));
        }
        
        global $wpdb;
        
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 20;
        $classifications_table = $wpdb->prefix . 'env_ai_classifications';
        $feedback_table = $wpdb->prefix . 'env_classification_feedback';
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT c.*, u.display_name as user_name,
                   COUNT(f.id) as feedback_count
            FROM $classifications_table c
            LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
            LEFT JOIN $feedback_table f ON c.id = f.classification_id
            GROUP BY c.id
            ORDER BY c.created_at DESC
            LIMIT %d
        ", $limit));
        
        wp_die(json_encode(array('success' => true, 'data' => $results)));
    }
    
    /**
     * Admin AJAX: Save AI configuration
     */
    public function ajax_save_ai_configuration() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'waste_classification_admin_nonce')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Access denied')));
        }
        
        $provider = sanitize_text_field($_POST['ai_service_provider']);
        $api_key = sanitize_text_field($_POST['api_key']);
        $max_requests = intval($_POST['max_requests_per_day']);
        $confidence_threshold = floatval($_POST['confidence_threshold']);
        
        // Save configuration
        update_option('env_ai_service_provider', $provider);
        update_option('env_ai_max_requests_per_day', $max_requests);
        update_option('env_ai_confidence_threshold', $confidence_threshold);
        
        // Hash and save API key if provided
        if (!empty($api_key)) {
            update_option('env_ai_api_key_hash', wp_hash($api_key));
            update_option('env_ai_api_key', $api_key); // In production, encrypt this
        }
        
        wp_die(json_encode(array('success' => true, 'data' => 'Configuration saved successfully')));
    }
    
    /**
     * Admin AJAX: Test AI connection
     */
    public function ajax_test_ai_connection() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'waste_classification_admin_nonce')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Access denied')));
        }
        
        $provider = sanitize_text_field($_POST['ai_service_provider']);
        $api_key = sanitize_text_field($_POST['api_key']);
        
        if (class_exists('AI_Service_Manager')) {
            $ai_manager = AI_Service_Manager::get_instance();
            $test_result = $ai_manager->test_connection($provider, $api_key);
            
            wp_die(json_encode($test_result));
        }
        
        wp_die(json_encode(array('success' => false, 'data' => 'AI Service Manager not available')));
    }
    
    /**
     * Admin AJAX: Save gamification settings
     */
    public function ajax_save_gamification_settings() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'waste_classification_admin_nonce')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Access denied')));
        }
        
        $points_per_classification = intval($_POST['points_per_classification']);
        $accuracy_bonus_multiplier = floatval($_POST['accuracy_bonus_multiplier']);
        $enable_leaderboard = intval($_POST['enable_leaderboard']);
        
        update_option('env_points_per_classification', $points_per_classification);
        update_option('env_accuracy_bonus_multiplier', $accuracy_bonus_multiplier);
        update_option('env_enable_leaderboard', $enable_leaderboard);
        
        wp_die(json_encode(array('success' => true, 'data' => 'Settings saved successfully')));
    }
    
    /**
     * Admin AJAX: Export classifications data
     */
    public function ajax_export_classifications_data() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'waste_classification_admin_nonce')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Access denied')));
        }
        
        global $wpdb;
        
        $classifications_table = $wpdb->prefix . 'env_ai_classifications';
        
        $results = $wpdb->get_results("
            SELECT c.id, u.display_name as user_name, c.category, c.subcategory,
                   c.confidence_score, c.status, c.created_at
            FROM $classifications_table c
            LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
            ORDER BY c.created_at DESC
        ");
        
        if (empty($results)) {
            wp_die(json_encode(array('success' => false, 'data' => 'No data to export')));
        }
        
        // Generate CSV
        $upload_dir = wp_upload_dir();
        $filename = 'waste_classifications_' . date('Y-m-d_H-i-s') . '.csv';
        $file_path = $upload_dir['path'] . '/' . $filename;
        
        $file = fopen($file_path, 'w');
        
        // CSV headers
        fputcsv($file, array('ID', 'User', 'Category', 'Subcategory', 'Confidence', 'Status', 'Date'));
        
        // CSV data
        foreach ($results as $row) {
            fputcsv($file, array(
                $row->id,
                $row->user_name ?: 'Unknown',
                $row->category,
                $row->subcategory,
                ($row->confidence_score * 100) . '%',
                $row->status,
                $row->created_at
            ));
        }
        
        fclose($file);
        
        wp_die(json_encode(array(
            'success' => true,
            'data' => array(
                'download_url' => $upload_dir['url'] . '/' . $filename,
                'filename' => $filename
            )
        )));
    }
    
    /**
     * Admin AJAX: Update classification status
     */
    public function ajax_update_classification_status() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'waste_classification_admin_nonce')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Access denied')));
        }
        
        global $wpdb;
        
        $classification_id = intval($_POST['classification_id']);
        $status = sanitize_text_field($_POST['status']);
        
        $allowed_statuses = array('pending', 'completed', 'failed', 'reviewing');
        if (!in_array($status, $allowed_statuses)) {
            wp_die(json_encode(array('success' => false, 'data' => 'Invalid status')));
        }
        
        $classifications_table = $wpdb->prefix . 'env_ai_classifications';
        
        $result = $wpdb->update(
            $classifications_table,
            array('status' => $status, 'updated_at' => current_time('mysql')),
            array('id' => $classification_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_die(json_encode(array('success' => true, 'data' => 'Status updated successfully')));
        } else {
            wp_die(json_encode(array('success' => false, 'data' => 'Failed to update status')));
        }
    }
    
    /**
     * Admin AJAX: Get classification details
     */
    public function ajax_get_classification_details() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'waste_classification_admin_nonce')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Access denied')));
        }
        
        global $wpdb;
        
        $classification_id = intval($_POST['classification_id']);
        $classifications_table = $wpdb->prefix . 'env_ai_classifications';
        
        $classification = $wpdb->get_row($wpdb->prepare("
            SELECT c.*, u.display_name as user_name
            FROM $classifications_table c
            LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
            WHERE c.id = %d
        ", $classification_id));
        
        if ($classification) {
            wp_die(json_encode(array('success' => true, 'data' => $classification)));
        } else {
            wp_die(json_encode(array('success' => false, 'data' => 'Classification not found')));
        }
    }
    
    /**
     * Admin AJAX: Get classifications trend data
     */
    public function ajax_get_classifications_trend_data() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'waste_classification_admin_nonce')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Access denied')));
        }
        
        global $wpdb;
        
        $classifications_table = $wpdb->prefix . 'env_ai_classifications';
        
        $results = $wpdb->get_results("
            SELECT DATE(created_at) as date, COUNT(*) as count
            FROM $classifications_table
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        
        $labels = array();
        $data = array();
        
        foreach ($results as $row) {
            $labels[] = date('M j', strtotime($row->date));
            $data[] = intval($row->count);
        }
        
        $chart_data = array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => 'Classifications',
                    'data' => $data,
                    'borderColor' => 'rgb(75, 192, 192)',
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'tension' => 0.1
                )
            )
        );
        
        wp_die(json_encode(array('success' => true, 'data' => $chart_data)));
    }
    
    /**
     * Admin AJAX: Get user engagement data
     */
    public function ajax_get_user_engagement_data() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'waste_classification_admin_nonce')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Access denied')));
        }
        
        global $wpdb;
        
        $gamification_table = $wpdb->prefix . 'env_user_gamification';
        
        // Get user engagement levels based on classification counts
        $engagement_data = $wpdb->get_results("
            SELECT 
                CASE 
                    WHEN classifications_count >= 100 THEN 'High Engagement'
                    WHEN classifications_count >= 20 THEN 'Medium Engagement'
                    WHEN classifications_count >= 5 THEN 'Low Engagement'
                    ELSE 'New Users'
                END as engagement_level,
                COUNT(*) as user_count
            FROM $gamification_table
            GROUP BY engagement_level
        ");
        
        $labels = array();
        $data = array();
        $colors = array(
            'High Engagement' => 'rgba(46, 204, 113, 0.8)',
            'Medium Engagement' => 'rgba(52, 152, 219, 0.8)',
            'Low Engagement' => 'rgba(241, 196, 15, 0.8)',
            'New Users' => 'rgba(155, 89, 182, 0.8)'
        );
        
        $background_colors = array();
        
        foreach ($engagement_data as $row) {
            $labels[] = $row->engagement_level;
            $data[] = intval($row->user_count);
            $background_colors[] = $colors[$row->engagement_level] ?? 'rgba(127, 127, 127, 0.8)';
        }
        
        $chart_data = array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'data' => $data,
                    'backgroundColor' => $background_colors
                )
            )
        );
        
        wp_die(json_encode(array('success' => true, 'data' => $chart_data)));
    }
    
    // ===== QUIZ SYSTEM AJAX HANDLERS =====
    
    /**
     * AJAX: Start a quiz session
     */
    public function ajax_start_quiz() {
        if (!wp_verify_nonce($_POST['nonce'], 'env_quiz_nonce')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Nonce verification failed')));
        }
        
        $quiz_manager = Environmental_Quiz_Manager::get_instance();
        $category_id = intval($_POST['category_id']);
        $user_id = get_current_user_id();
        
        $result = $quiz_manager->start_quiz($user_id, $category_id);
        wp_die(json_encode($result));
    }
    
    /**
     * AJAX: Submit quiz answer
     */
    public function ajax_submit_quiz_answer() {
        if (!wp_verify_nonce($_POST['nonce'], 'env_quiz_nonce')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Nonce verification failed')));
        }
        
        $quiz_manager = Environmental_Quiz_Manager::get_instance();
        $session_id = intval($_POST['session_id']);
        $question_id = intval($_POST['question_id']);
        $answer = sanitize_text_field($_POST['answer']);
        
        $result = $quiz_manager->submit_answer($session_id, $question_id, $answer);
        wp_die(json_encode($result));
    }
    
    /**
     * AJAX: Complete quiz
     */
    public function ajax_complete_quiz() {
        if (!wp_verify_nonce($_POST['nonce'], 'env_quiz_nonce')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Nonce verification failed')));
        }
        
        $quiz_manager = Environmental_Quiz_Manager::get_instance();
        $session_id = intval($_POST['session_id']);
        
        $result = $quiz_manager->complete_quiz($session_id);
        wp_die(json_encode($result));
    }
    
    /**
     * AJAX: Get quiz leaderboard
     */
    public function ajax_get_quiz_leaderboard() {
        $quiz_manager = Environmental_Quiz_Manager::get_instance();
        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        
        $result = $quiz_manager->get_leaderboard($category_id, $limit);
        wp_die(json_encode(array('success' => true, 'data' => $result)));
    }
    
    /**
     * AJAX: Get user quiz statistics
     */
    public function ajax_get_user_quiz_stats() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_die(json_encode(array('success' => false, 'message' => 'Not logged in')));
        }
        
        $quiz_manager = Environmental_Quiz_Manager::get_instance();
        $result = $quiz_manager->get_user_statistics($user_id);
        wp_die(json_encode(array('success' => true, 'data' => $result)));
    }
    
    // ===== CHALLENGE SYSTEM AJAX HANDLERS =====
    
    /**
     * AJAX: Get available challenges
     */
    public function ajax_get_available_challenges() {
        $challenge_system = Environmental_Challenge_System::get_instance();
        $result = $challenge_system->get_active_challenges();
        wp_die(json_encode(array('success' => true, 'data' => $result)));
    }
    
    /**
     * AJAX: Participate in challenge
     */
    public function ajax_participate_in_challenge() {
        if (!wp_verify_nonce($_POST['nonce'], 'env_challenge_nonce')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Nonce verification failed')));
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_die(json_encode(array('success' => false, 'data' => 'Not logged in')));
        }
        
        $challenge_system = Environmental_Challenge_System::get_instance();
        $challenge_id = intval($_POST['challenge_id']);
        
        $result = $challenge_system->join_challenge($user_id, $challenge_id);
        wp_die(json_encode($result));
    }
    
    /**
     * AJAX: Update challenge progress
     */
    public function ajax_update_challenge_progress() {
        if (!wp_verify_nonce($_POST['nonce'], 'env_challenge_nonce')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Nonce verification failed')));
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_die(json_encode(array('success' => false, 'data' => 'Not logged in')));
        }
        
        $challenge_system = Environmental_Challenge_System::get_instance();
        $challenge_id = intval($_POST['challenge_id']);
        $progress_data = $_POST['progress'] ?? array();
        
        $result = $challenge_system->update_progress($user_id, $challenge_id, $progress_data);
        wp_die(json_encode($result));
    }
    
    /**
     * AJAX: Get user challenges
     */
    public function ajax_get_user_challenges() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_die(json_encode(array('success' => false, 'data' => 'Not logged in')));
        }
        
        $challenge_system = Environmental_Challenge_System::get_instance();
        $result = $challenge_system->get_user_challenges($user_id);
        wp_die(json_encode(array('success' => true, 'data' => $result)));
    }
    
    /**
     * AJAX: Complete challenge
     */
    public function ajax_complete_challenge() {
        if (!wp_verify_nonce($_POST['nonce'], 'env_challenge_nonce')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Nonce verification failed')));
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_die(json_encode(array('success' => false, 'data' => 'Not logged in')));
        }
        
        $challenge_system = Environmental_Challenge_System::get_instance();
        $challenge_id = intval($_POST['challenge_id']);
        
        $result = $challenge_system->complete_challenge($user_id, $challenge_id);
        wp_die(json_encode($result));
    }
    
    // ===== SHORTCODE HANDLERS =====
    
    /**
     * Quiz Interface Shortcode
     */
    public function quiz_interface_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'difficulty' => 'all',
            'theme' => 'default'
        ), $atts);
        
        wp_enqueue_script('env-quiz-interface', ENV_DASHBOARD_PLUGIN_URL . 'assets/js/quiz-interface.js', array('jquery'), ENV_DASHBOARD_VERSION, true);
        wp_localize_script('env-quiz-interface', 'envQuizAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('env_quiz_nonce')
        ));
        
        ob_start();
        ?>
        <div id="env-quiz-interface" class="env-quiz-container theme-<?php echo esc_attr($atts['theme']); ?>">
            <div class="quiz-loading">Loading quiz interface...</div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Quiz Leaderboard Shortcode
     */
    public function quiz_leaderboard_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'category' => '',
            'period' => 'all'
        ), $atts);
        
        ob_start();
        ?>
        <div id="env-quiz-leaderboard" class="env-leaderboard-container">
            <h3>Quiz Champions</h3>
            <div class="leaderboard-loading">Loading leaderboard...</div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            // Load leaderboard data
            $.post(ajaxurl, {
                action: 'get_quiz_leaderboard',
                limit: <?php echo intval($atts['limit']); ?>,
                category: '<?php echo esc_js($atts['category']); ?>'
            }, function(response) {
                if (response.success) {
                    // Render leaderboard
                    var html = '<ol class="leaderboard-list">';
                    response.data.forEach(function(user, index) {
                        html += '<li class="leaderboard-item rank-' + (index + 1) + '">';
                        html += '<span class="rank">#' + (index + 1) + '</span>';
                        html += '<span class="name">' + user.display_name + '</span>';
                        html += '<span class="score">' + user.total_points + ' pts</span>';
                        html += '</li>';
                    });
                    html += '</ol>';
                    $('#env-quiz-leaderboard .leaderboard-loading').html(html);
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Challenge Dashboard Shortcode
     */
    public function challenge_dashboard_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => 'all',
            'show_completed' => 'false'
        ), $atts);
        
        wp_enqueue_script('env-challenge-dashboard', ENV_DASHBOARD_PLUGIN_URL . 'assets/js/challenge-dashboard.js', array('jquery'), ENV_DASHBOARD_VERSION, true);
        wp_localize_script('env-challenge-dashboard', 'envChallengeAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('env_challenge_nonce')
        ));
        
        ob_start();
        ?>
        <div id="env-challenge-dashboard" class="env-challenge-container">
            <div class="challenge-tabs">
                <button class="tab-btn active" data-tab="available">Available Challenges</button>
                <button class="tab-btn" data-tab="my-challenges">My Challenges</button>
                <button class="tab-btn" data-tab="completed">Completed</button>
            </div>
            <div class="challenge-content">
                <div class="challenge-loading">Loading challenges...</div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * User Progress Shortcode
     */
    public function user_progress_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_stats' => 'true',
            'show_achievements' => 'true',
            'show_streaks' => 'true'
        ), $atts);
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            return '<p>Please log in to view your progress.</p>';
        }
        
        ob_start();
        ?>
        <div id="env-user-progress" class="env-progress-container">
            <h3>Your Environmental Journey</h3>
            <div class="progress-stats">
                <div class="progress-loading">Loading your progress...</div>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            // Load user progress data
            $.post(ajaxurl, {
                action: 'get_user_quiz_stats'
            }, function(response) {
                if (response.success) {
                    var data = response.data;
                    var html = '<div class="stats-grid">';
                    html += '<div class="stat-card"><h4>Quizzes Completed</h4><span class="stat-value">' + data.total_quizzes + '</span></div>';
                    html += '<div class="stat-card"><h4>Points Earned</h4><span class="stat-value">' + data.total_points + '</span></div>';
                    html += '<div class="stat-card"><h4>Accuracy Rate</h4><span class="stat-value">' + Math.round(data.accuracy_rate) + '%</span></div>';
                    html += '<div class="stat-card"><h4>Current Level</h4><span class="stat-value">' + data.level + '</span></div>';
                    html += '</div>';
                    $('#env-user-progress .progress-loading').html(html);
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}

    /**
     * AJAX Handler: Start Quiz
     */
    public function ajax_start_quiz() {
        if (!wp_verify_nonce($_POST['nonce'], 'env_quiz_nonce')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Nonce verification failed')));
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_die(json_encode(array('success' => false, 'data' => 'User not logged in')));
        }
        
        $category = sanitize_text_field($_POST['category'] ?? '');
        
        if (class_exists('Quiz_Manager')) {
            $quiz_manager = Quiz_Manager::get_instance();
            $result = $quiz_manager->start_quiz($user_id, $category);
            wp_die(json_encode($result));
        }
        
        wp_die(json_encode(array('success' => false, 'data' => 'Quiz Manager not available')));
    }
    
    /**
     * AJAX Handler: Submit Quiz Answer
     */
    public function ajax_submit_quiz_answer() {
        if (!wp_verify_nonce($_POST['nonce'], 'env_quiz_nonce')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Nonce verification failed')));
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_die(json_encode(array('success' => false, 'data' => 'User not logged in')));
        }
        
        $session_id = intval($_POST['session_id']);
        $question_id = intval($_POST['question_id']);
        $answer = sanitize_text_field($_POST['answer']);
        
        if (class_exists('Quiz_Manager')) {
            $quiz_manager = Quiz_Manager::get_instance();
            $result = $quiz_manager->submit_answer($session_id, $question_id, $answer, $user_id);
            wp_die(json_encode($result));
        }
        
        wp_die(json_encode(array('success' => false, 'data' => 'Quiz Manager not available')));
    }
    
    /**
     * AJAX Handler: Complete Quiz
     */
    public function ajax_complete_quiz() {
        if (!wp_verify_nonce($_POST['nonce'], 'env_quiz_nonce')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Nonce verification failed')));
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_die(json_encode(array('success' => false, 'data' => 'User not logged in')));
        }
        
        $session_id = intval($_POST['session_id']);
        
        if (class_exists('Quiz_Manager')) {
            $quiz_manager = Quiz_Manager::get_instance();
            $result = $quiz_manager->complete_quiz($session_id, $user_id);
            wp_die(json_encode($result));
        }
        
        wp_die(json_encode(array('success' => false, 'data' => 'Quiz Manager not available')));
    }
    
    /**
     * AJAX Handler: Get Quiz Leaderboard
     */
    public function ajax_get_quiz_leaderboard() {
        if (!wp_verify_nonce($_POST['nonce'], 'env_quiz_nonce')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Nonce verification failed')));
        }
        
        $period = sanitize_text_field($_POST['period'] ?? 'all');
        $limit = intval($_POST['limit'] ?? 10);
        
        if (class_exists('Quiz_Manager')) {
            $quiz_manager = Quiz_Manager::get_instance();
            $result = $quiz_manager->get_leaderboard($period, $limit);
            wp_die(json_encode($result));
        }
        
        wp_die(json_encode(array('success' => false, 'data' => 'Quiz Manager not available')));
    }
    
    /**
     * AJAX Handler: Get User Quiz Stats
     */
    public function ajax_get_user_quiz_stats() {
        if (!wp_verify_nonce($_POST['nonce'], 'env_quiz_nonce')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Nonce verification failed')));
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_die(json_encode(array('success' => false, 'data' => 'User not logged in')));
        }
        
        if (class_exists('Quiz_Manager')) {
            $quiz_manager = Quiz_Manager::get_instance();
            $result = $quiz_manager->get_user_stats($user_id);
            wp_die(json_encode($result));
        }
        
        wp_die(json_encode(array('success' => false, 'data' => 'Quiz Manager not available')));
    }
    
    /**
     * AJAX Handler: Get Available Challenges
     */
    public function ajax_get_available_challenges() {
        if (!wp_verify_nonce($_POST['nonce'], 'env_challenge_nonce')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Nonce verification failed')));
        }
        
        $challenge_type = sanitize_text_field($_POST['type'] ?? 'all');
        
        if (class_exists('Challenge_System')) {
            $challenge_system = Challenge_System::get_instance();
            $result = $challenge_system->get_available_challenges($challenge_type);
            wp_die(json_encode($result));
        }
        
        wp_die(json_encode(array('success' => false, 'data' => 'Challenge System not available')));
    }
    
    /**
     * AJAX Handler: Participate in Challenge
     */
    public function ajax_participate_in_challenge() {
        if (!wp_verify_nonce($_POST['nonce'], 'env_challenge_nonce')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Nonce verification failed')));
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_die(json_encode(array('success' => false, 'data' => 'User not logged in')));
        }
        
        $challenge_id = intval($_POST['challenge_id']);
        
        if (class_exists('Challenge_System')) {
            $challenge_system = Challenge_System::get_instance();
            $result = $challenge_system->join_challenge($challenge_id, $user_id);
            wp_die(json_encode($result));
        }
        
        wp_die(json_encode(array('success' => false, 'data' => 'Challenge System not available')));
    }
    
    /**
     * AJAX Handler: Update Challenge Progress
     */
    public function ajax_update_challenge_progress() {
        if (!wp_verify_nonce($_POST['nonce'], 'env_challenge_nonce')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Nonce verification failed')));
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_die(json_encode(array('success' => false, 'data' => 'User not logged in')));
        }
        
        $challenge_id = intval($_POST['challenge_id']);
        $progress_data = $_POST['progress_data'] ?? array();
        
        if (class_exists('Challenge_System')) {
            $challenge_system = Challenge_System::get_instance();
            $result = $challenge_system->update_progress($challenge_id, $user_id, $progress_data);
            wp_die(json_encode($result));
        }
        
        wp_die(json_encode(array('success' => false, 'data' => 'Challenge System not available')));
    }
    
    /**
     * AJAX Handler: Get User Challenges
     */
    public function ajax_get_user_challenges() {
        if (!wp_verify_nonce($_POST['nonce'], 'env_challenge_nonce')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Nonce verification failed')));
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_die(json_encode(array('success' => false, 'data' => 'User not logged in')));
        }
        
        $status = sanitize_text_field($_POST['status'] ?? 'active');
        
        if (class_exists('Challenge_System')) {
            $challenge_system = Challenge_System::get_instance();
            $result = $challenge_system->get_user_challenges($user_id, $status);
            wp_die(json_encode($result));
        }
        
        wp_die(json_encode(array('success' => false, 'data' => 'Challenge System not available')));
    }
    
    /**
     * AJAX Handler: Complete Challenge
     */
    public function ajax_complete_challenge() {
        if (!wp_verify_nonce($_POST['nonce'], 'env_challenge_nonce')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Nonce verification failed')));
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_die(json_encode(array('success' => false, 'data' => 'User not logged in')));
        }
        
        $challenge_id = intval($_POST['challenge_id']);
        
        if (class_exists('Challenge_System')) {
            $challenge_system = Challenge_System::get_instance();
            $result = $challenge_system->complete_challenge($challenge_id, $user_id);
            wp_die(json_encode($result));
        }
        
        wp_die(json_encode(array('success' => false, 'data' => 'Challenge System not available')));
    
}

// Initialize plugin
Environmental_Data_Dashboard::get_instance();

// End of file
