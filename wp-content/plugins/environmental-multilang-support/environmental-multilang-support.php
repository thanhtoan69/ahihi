<?php
/**
 * Plugin Name: Environmental Platform - Multi-language Support
 * Plugin URI: https://environmentalplatform.com/plugins/multilang-support
 * Description: Comprehensive multi-language support for Environmental Platform with WPML/Polylang integration, RTL support, and language-specific SEO.
 * Version: 1.0.0
 * Author: Environmental Platform Team
 * Author URI: https://environmentalplatform.com
 * License: GPL v2 or later
 * Text Domain: environmental-multilang-support
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EMS_VERSION', '1.0.0');
define('EMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EMS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EMS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Environmental Multi-language Support Plugin Class
 */
class Environmental_Multilang_Support {

    /**
     * Single instance of the plugin
     */
    private static $instance = null;    /**
     * Plugin components
     */
    public $language_switcher;
    public $translation_manager;
    public $rtl_support;
    public $seo_optimizer;
    public $user_preferences;
    public $admin_interface;

    /**
     * Plugin utilities
     */
    public $language_detector;
    public $url_manager;
    public $content_duplicator;
    public $translation_api;

    /**
     * Get single instance
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
        $this->init_components();
    }    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'load_textdomain'));
        add_action('plugins_loaded', array($this, 'init_plugin'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate_plugin'));
    }

    /**
     * Load plugin textdomain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'environmental-multilang-support',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Core components
        require_once EMS_PLUGIN_DIR . 'includes/class-language-switcher.php';
        require_once EMS_PLUGIN_DIR . 'includes/class-translation-manager.php';
        require_once EMS_PLUGIN_DIR . 'includes/class-rtl-support.php';
        require_once EMS_PLUGIN_DIR . 'includes/class-seo-optimizer.php';
        require_once EMS_PLUGIN_DIR . 'includes/class-user-preferences.php';
        require_once EMS_PLUGIN_DIR . 'includes/class-admin-interface.php';        // Utilities
        require_once EMS_PLUGIN_DIR . 'includes/class-language-detector.php';
        require_once EMS_PLUGIN_DIR . 'includes/class-url-manager.php';
        require_once EMS_PLUGIN_DIR . 'includes/class-content-duplicator.php';
        require_once EMS_PLUGIN_DIR . 'includes/class-translation-api.php';
    }    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Initialize utilities first
        $this->language_detector = new EMS_Language_Detector();
        $this->url_manager = new EMS_URL_Manager();
        $this->content_duplicator = new EMS_Content_Duplicator();
        $this->translation_api = new EMS_Translation_API();
        
        // Initialize core components
        $this->language_switcher = new EMS_Language_Switcher();
        $this->translation_manager = new EMS_Translation_Manager();
        $this->rtl_support = new EMS_RTL_Support();
        $this->seo_optimizer = new EMS_SEO_Optimizer();
        $this->user_preferences = new EMS_User_Preferences();
        $this->admin_interface = new EMS_Admin_Interface();
    }

    /**
     * Initialize plugin after all plugins are loaded
     */
    public function init_plugin() {
        // Check for required dependencies
        if (!$this->check_dependencies()) {
            add_action('admin_notices', array($this, 'dependency_notice'));
            return;
        }

        // Initialize plugin functionality
        $this->setup_default_languages();
        $this->register_hooks();
    }

    /**
     * Check plugin dependencies
     */
    private function check_dependencies() {
        // Check if any multilang plugin is available (optional but recommended)
        $has_wpml = class_exists('SitePress');
        $has_polylang = function_exists('pll_languages_list');
        $has_qtranslate = function_exists('qtranxf_init');

        // Store available plugins for later use
        update_option('ems_available_plugins', array(
            'wpml' => $has_wpml,
            'polylang' => $has_polylang,
            'qtranslate' => $has_qtranslate
        ));

        return true; // Plugin works standalone or with others
    }

    /**
     * Setup default languages
     */
    private function setup_default_languages() {
        $default_languages = array(
            'vi' => array(
                'name' => 'Tiếng Việt',
                'native_name' => 'Tiếng Việt',
                'flag' => 'vn',
                'rtl' => false,
                'default' => true
            ),
            'en' => array(
                'name' => 'English',
                'native_name' => 'English',
                'flag' => 'us',
                'rtl' => false,
                'default' => false
            ),
            'zh' => array(
                'name' => 'Chinese',
                'native_name' => '中文',
                'flag' => 'cn',
                'rtl' => false,
                'default' => false
            ),
            'ja' => array(
                'name' => 'Japanese',
                'native_name' => '日本語',
                'flag' => 'jp',
                'rtl' => false,
                'default' => false
            ),
            'ko' => array(
                'name' => 'Korean',
                'native_name' => '한국어',
                'flag' => 'kr',
                'rtl' => false,
                'default' => false
            ),
            'th' => array(
                'name' => 'Thai',
                'native_name' => 'ไทย',
                'flag' => 'th',
                'rtl' => false,
                'default' => false
            ),
            'ar' => array(
                'name' => 'Arabic',
                'native_name' => 'العربية',
                'flag' => 'sa',
                'rtl' => true,
                'default' => false
            ),
            'he' => array(
                'name' => 'Hebrew',
                'native_name' => 'עברית',
                'flag' => 'il',
                'rtl' => true,
                'default' => false
            ),
            'fr' => array(
                'name' => 'French',
                'native_name' => 'Français',
                'flag' => 'fr',
                'rtl' => false,
                'default' => false
            ),
            'es' => array(
                'name' => 'Spanish',
                'native_name' => 'Español',
                'flag' => 'es',
                'rtl' => false,
                'default' => false
            )
        );

        // Get existing languages option
        $existing_languages = get_option('ems_supported_languages', array());
        
        // Merge with defaults, preserving user customizations
        $languages = array_merge($default_languages, $existing_languages);
        
        update_option('ems_supported_languages', $languages);
    }

    /**
     * Register plugin hooks
     */
    private function register_hooks() {
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('wp_head', array($this, 'add_language_meta'));
        add_filter('locale', array($this, 'set_user_locale'));
        
        // Admin hooks
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Language switching hooks
        add_action('init', array($this, 'handle_language_switch'));
        add_filter('home_url', array($this, 'localize_home_url'), 10, 4);
        
        // Content hooks
        add_action('save_post', array($this, 'save_translation_data'));
        add_filter('the_content', array($this, 'process_multilang_content'));
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'ems-frontend-style',
            EMS_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            EMS_VERSION
        );

        wp_enqueue_script(
            'ems-frontend-script',
            EMS_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            EMS_VERSION,
            true
        );

        // Localize script with language data
        wp_localize_script('ems-frontend-script', 'emsData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ems_nonce'),
            'currentLang' => $this->get_current_language(),
            'languages' => $this->get_available_languages(),
            'rtlLanguages' => $this->get_rtl_languages()
        ));
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'environmental-multilang') === false) {
            return;
        }

        wp_enqueue_style(
            'ems-admin-style',
            EMS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            EMS_VERSION
        );

        wp_enqueue_script(
            'ems-admin-script',
            EMS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-util'),
            EMS_VERSION,
            true
        );
    }

    /**
     * Add language meta tags to head
     */
    public function add_language_meta() {
        $current_lang = $this->get_current_language();
        $languages = $this->get_available_languages();
        
        // Add current language meta
        echo '<meta property="og:locale" content="' . esc_attr($current_lang) . '">' . "\n";
        
        // Add alternate language links
        foreach ($languages as $lang_code => $lang_data) {
            if ($lang_code !== $current_lang) {
                $url = $this->get_language_url($lang_code);
                echo '<link rel="alternate" hreflang="' . esc_attr($lang_code) . '" href="' . esc_url($url) . '">' . "\n";
            }
        }
        
        // Add x-default for SEO
        $default_lang = $this->get_default_language();
        $default_url = $this->get_language_url($default_lang);
        echo '<link rel="alternate" hreflang="x-default" href="' . esc_url($default_url) . '">' . "\n";
    }

    /**
     * Set user locale based on language preference
     */
    public function set_user_locale($locale) {
        // Check URL parameter first
        if (isset($_GET['lang']) && $this->is_valid_language($_GET['lang'])) {
            return sanitize_text_field($_GET['lang']);
        }
        
        // Check user preference
        if (is_user_logged_in()) {
            $user_lang = get_user_meta(get_current_user_id(), 'ems_language_preference', true);
            if ($user_lang && $this->is_valid_language($user_lang)) {
                return $user_lang;
            }
        }
        
        // Check session/cookie
        $session_lang = $this->get_session_language();
        if ($session_lang && $this->is_valid_language($session_lang)) {
            return $session_lang;
        }
        
        // Auto-detect from browser
        $detected_lang = $this->detect_browser_language();
        if ($detected_lang && $this->is_valid_language($detected_lang)) {
            return $detected_lang;
        }
        
        return $locale;
    }

    /**
     * Handle language switching
     */
    public function handle_language_switch() {
        if (isset($_GET['switch_lang']) && wp_verify_nonce($_GET['_wpnonce'], 'ems_switch_lang')) {
            $new_lang = sanitize_text_field($_GET['switch_lang']);
            
            if ($this->is_valid_language($new_lang)) {
                // Set session language
                $this->set_session_language($new_lang);
                
                // Update user preference if logged in
                if (is_user_logged_in()) {
                    update_user_meta(get_current_user_id(), 'ems_language_preference', $new_lang);
                }
                
                // Redirect to current page in new language
                $redirect_url = remove_query_arg(array('switch_lang', '_wpnonce'));
                $redirect_url = $this->get_language_url($new_lang, $redirect_url);
                wp_redirect($redirect_url);
                exit;
            }
        }
    }

    /**
     * Get current language
     */
    public function get_current_language() {
        return get_locale();
    }

    /**
     * Get available languages
     */
    public function get_available_languages() {
        return get_option('ems_supported_languages', array());
    }

    /**
     * Get RTL languages
     */
    public function get_rtl_languages() {
        $languages = $this->get_available_languages();
        $rtl_langs = array();
        
        foreach ($languages as $code => $data) {
            if (isset($data['rtl']) && $data['rtl']) {
                $rtl_langs[] = $code;
            }
        }
        
        return $rtl_langs;
    }

    /**
     * Get default language
     */
    public function get_default_language() {
        $languages = $this->get_available_languages();
        
        foreach ($languages as $code => $data) {
            if (isset($data['default']) && $data['default']) {
                return $code;
            }
        }
        
        return 'vi'; // Fallback to Vietnamese
    }

    /**
     * Check if language is valid
     */
    public function is_valid_language($lang_code) {
        $languages = $this->get_available_languages();
        return isset($languages[$lang_code]);
    }

    /**
     * Get language URL
     */
    public function get_language_url($lang_code, $url = null) {
        if (!$url) {
            $url = home_url(add_query_arg(array()));
        }
        
        // Add language parameter
        return add_query_arg('lang', $lang_code, $url);
    }

    /**
     * Get session language
     */
    private function get_session_language() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['ems_language']) ? $_SESSION['ems_language'] : null;
    }

    /**
     * Set session language
     */
    private function set_session_language($lang_code) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['ems_language'] = $lang_code;
    }

    /**
     * Detect browser language
     */
    private function detect_browser_language() {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return null;
        }
        
        $browser_langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $available_langs = array_keys($this->get_available_languages());
        
        foreach ($browser_langs as $browser_lang) {
            $lang = trim(substr($browser_lang, 0, 2));
            if (in_array($lang, $available_langs)) {
                return $lang;
            }
        }
        
        return null;
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Multi-language Support', 'environmental-multilang-support'),
            __('Multi-language', 'environmental-multilang-support'),
            'manage_options',
            'environmental-multilang-support',
            array($this, 'admin_page'),
            'dashicons-translation',
            30
        );
    }

    /**
     * Admin page callback
     */
    public function admin_page() {
        include EMS_PLUGIN_DIR . 'templates/admin-page.php';
    }

    /**
     * Save translation data
     */
    public function save_translation_data($post_id) {
        // Handle translation metadata saving
        if (isset($_POST['ems_translations'])) {
            $translations = sanitize_text_field($_POST['ems_translations']);
            update_post_meta($post_id, '_ems_translations', $translations);
        }
    }

    /**
     * Process multilang content
     */
    public function process_multilang_content($content) {
        // Process shortcodes and language-specific content
        return $this->translation_manager->process_content($content);
    }

    /**
     * Plugin activation
     */
    public function activate_plugin() {
        // Create database tables if needed
        $this->create_database_tables();
        
        // Set default options
        $this->setup_default_languages();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate_plugin() {
        // Clean up if needed
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     */
    private function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Translation pairs table
        $table_name = $wpdb->prefix . 'ems_translations';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            original_id bigint(20) NOT NULL,
            translated_id bigint(20) NOT NULL,
            original_lang varchar(10) NOT NULL,
            translated_lang varchar(10) NOT NULL,
            translation_type varchar(20) NOT NULL DEFAULT 'post',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY original_id (original_id),
            KEY translated_id (translated_id),
            KEY language_pair (original_lang, translated_lang)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Dependency notice
     */
    public function dependency_notice() {
        echo '<div class="notice notice-warning"><p>';
        _e('Environmental Multi-language Support: No critical dependencies missing. Plugin will work with basic WordPress i18n support.', 'environmental-multilang-support');
        echo '</p></div>';
    }
}

/**
 * Initialize the plugin
 */
function ems_init() {
    return Environmental_Multilang_Support::get_instance();
}

// Start the plugin
ems_init();

/**
 * Helper functions for template use
 */

/**
 * Get language switcher HTML
 */
function ems_language_switcher($args = array()) {
    $plugin = Environmental_Multilang_Support::get_instance();
    return $plugin->language_switcher->render($args);
}

/**
 * Get current language code
 */
function ems_get_current_language() {
    $plugin = Environmental_Multilang_Support::get_instance();
    return $plugin->get_current_language();
}

/**
 * Check if current language is RTL
 */
function ems_is_rtl() {
    $plugin = Environmental_Multilang_Support::get_instance();
    $current_lang = $plugin->get_current_language();
    $rtl_langs = $plugin->get_rtl_languages();
    return in_array($current_lang, $rtl_langs);
}

/**
 * Get translation URL for current page
 */
function ems_get_translation_url($lang_code) {
    $plugin = Environmental_Multilang_Support::get_instance();
    return $plugin->get_language_url($lang_code);
}
