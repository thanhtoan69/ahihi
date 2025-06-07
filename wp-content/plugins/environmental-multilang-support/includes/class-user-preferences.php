<?php
/**
 * User Preferences Component
 *
 * Handles user language preferences and settings
 *
 * @package Environmental_Multilang_Support
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EMS_User_Preferences {
    
    /**
     * Instance of this class
     *
     * @var EMS_User_Preferences
     */
    private static $instance = null;
    
    /**
     * Cookie name for language preference
     *
     * @var string
     */
    private $cookie_name = 'ems_language';
    
    /**
     * Cookie expiry time (30 days)
     *
     * @var int
     */
    private $cookie_expiry = 2592000;
    
    /**
     * Get instance
     *
     * @return EMS_User_Preferences
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
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_ems_set_language_preference', [$this, 'ajax_set_language_preference']);
        add_action('wp_ajax_nopriv_ems_set_language_preference', [$this, 'ajax_set_language_preference']);
        add_action('init', [$this, 'handle_language_switch']);
        add_action('show_user_profile', [$this, 'add_user_language_field']);
        add_action('edit_user_profile', [$this, 'add_user_language_field']);
        add_action('personal_options_update', [$this, 'save_user_language_field']);
        add_action('edit_user_profile_update', [$this, 'save_user_language_field']);
        add_filter('init', [$this, 'detect_and_set_language']);
        add_action('wp_login', [$this, 'restore_user_language_on_login'], 10, 2);
        add_action('wp_logout', [$this, 'clear_language_cookie_on_logout']);
        
        // Admin preferences
        add_action('admin_init', [$this, 'register_admin_settings']);
        add_action('admin_menu', [$this, 'add_preferences_page']);
    }
    
    /**
     * Handle language switch via URL parameter
     */
    public function handle_language_switch() {
        if (isset($_GET['lang']) && !empty($_GET['lang'])) {
            $language = sanitize_text_field($_GET['lang']);
            if ($this->is_supported_language($language)) {
                $this->set_language_preference($language);
                
                // Redirect to clean URL without lang parameter
                if (!wp_doing_ajax() && !is_admin()) {
                    $redirect_url = remove_query_arg('lang', wp_get_referer() ?: home_url());
                    wp_safe_redirect($redirect_url);
                    exit;
                }
            }
        }
    }
    
    /**
     * AJAX handler for setting language preference
     */
    public function ajax_set_language_preference() {
        check_ajax_referer('ems_nonce', 'nonce');
        
        $language = sanitize_text_field($_POST['language']);
        
        if (!$this->is_supported_language($language)) {
            wp_send_json_error(['message' => __('Unsupported language', 'environmental-multilang-support')]);
        }
        
        $this->set_language_preference($language);
        
        // Update user meta if logged in
        if (is_user_logged_in()) {
            update_user_meta(get_current_user_id(), 'ems_preferred_language', $language);
        }
        
        wp_send_json_success([
            'message' => __('Language preference updated', 'environmental-multilang-support'),
            'language' => $language,
            'redirect_url' => $this->get_translated_current_url($language)
        ]);
    }
    
    /**
     * Set language preference
     *
     * @param string $language
     * @return bool
     */
    public function set_language_preference($language) {
        if (!$this->is_supported_language($language)) {
            return false;
        }
        
        // Set cookie
        $cookie_path = parse_url(home_url(), PHP_URL_PATH) ?: '/';
        $cookie_domain = parse_url(home_url(), PHP_URL_HOST);
        
        setcookie(
            $this->cookie_name,
            $language,
            time() + $this->cookie_expiry,
            $cookie_path,
            $cookie_domain,
            is_ssl(),
            true
        );
        
        $_COOKIE[$this->cookie_name] = $language;
        
        // Update user meta if logged in
        if (is_user_logged_in()) {
            update_user_meta(get_current_user_id(), 'ems_preferred_language', $language);
        }
        
        // Set WordPress locale temporarily
        add_filter('locale', function() use ($language) {
            return $this->get_wp_locale($language);
        });
        
        do_action('ems_language_switched', $language);
        
        return true;
    }
    
    /**
     * Get user language preference
     *
     * @param int $user_id Optional user ID
     * @return string
     */
    public function get_language_preference($user_id = null) {
        // Priority order:
        // 1. URL parameter (temporary)
        // 2. User meta (for logged-in users)
        // 3. Cookie
        // 4. Browser detection
        // 5. Default language
        
        // URL parameter (temporary)
        if (isset($_GET['lang']) && $this->is_supported_language($_GET['lang'])) {
            return sanitize_text_field($_GET['lang']);
        }
        
        // User meta (for logged-in users)
        if ($user_id || is_user_logged_in()) {
            $user_id = $user_id ?: get_current_user_id();
            $user_lang = get_user_meta($user_id, 'ems_preferred_language', true);
            if ($user_lang && $this->is_supported_language($user_lang)) {
                return $user_lang;
            }
        }
        
        // Cookie
        if (isset($_COOKIE[$this->cookie_name]) && $this->is_supported_language($_COOKIE[$this->cookie_name])) {
            return $_COOKIE[$this->cookie_name];
        }
        
        // Browser detection
        $browser_lang = $this->detect_browser_language();
        if ($browser_lang && $this->is_supported_language($browser_lang)) {
            return $browser_lang;
        }
        
        // Default language
        return get_option('ems_default_language', 'en');
    }
    
    /**
     * Detect browser language
     *
     * @return string|false
     */
    public function detect_browser_language() {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return false;
        }
        
        $accepted_languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $supported_languages = $this->get_supported_languages();
        
        foreach ($accepted_languages as $lang) {
            $lang = trim(strtolower(substr($lang, 0, 2)));
            if (in_array($lang, $supported_languages)) {
                return $lang;
            }
        }
        
        return false;
    }
    
    /**
     * Detect and set language on init
     */
    public function detect_and_set_language() {
        $language = $this->get_language_preference();
        
        if ($language !== get_option('ems_default_language', 'en')) {
            // Set WordPress locale
            add_filter('locale', function() use ($language) {
                return $this->get_wp_locale($language);
            });
            
            // Load text domain for the language
            $this->load_language_textdomain($language);
        }
    }
    
    /**
     * Load text domain for specific language
     *
     * @param string $language
     */
    private function load_language_textdomain($language) {
        $locale = $this->get_wp_locale($language);
        
        // Load WordPress core translations
        load_default_textdomain($locale);
        
        // Load theme translations
        load_theme_textdomain(get_template(), get_template_directory() . '/languages');
        
        // Load plugin translations
        load_plugin_textdomain('environmental-multilang-support', false, dirname(plugin_basename(__FILE__)) . '/../languages');
    }
    
    /**
     * Add language field to user profile
     *
     * @param WP_User $user
     */
    public function add_user_language_field($user) {
        $current_lang = get_user_meta($user->ID, 'ems_preferred_language', true);
        $languages = $this->get_supported_languages_with_names();
        ?>
        
        <h3><?php _e('Language Preferences', 'environmental-multilang-support'); ?></h3>
        
        <table class="form-table">
            <tr>
                <th>
                    <label for="ems_preferred_language"><?php _e('Preferred Language', 'environmental-multilang-support'); ?></label>
                </th>
                <td>
                    <select name="ems_preferred_language" id="ems_preferred_language">
                        <option value=""><?php _e('Use site default', 'environmental-multilang-support'); ?></option>
                        <?php foreach ($languages as $code => $name): ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected($current_lang, $code); ?>>
                                <?php echo esc_html($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php _e('Select your preferred language for the site interface.', 'environmental-multilang-support'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="ems_auto_translate"><?php _e('Auto-translate Content', 'environmental-multilang-support'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="ems_auto_translate" id="ems_auto_translate" value="1" 
                           <?php checked(get_user_meta($user->ID, 'ems_auto_translate', true), '1'); ?> />
                    <label for="ems_auto_translate">
                        <?php _e('Automatically show translated content when available', 'environmental-multilang-support'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="ems_remember_language"><?php _e('Remember Language Choice', 'environmental-multilang-support'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="ems_remember_language" id="ems_remember_language" value="1" 
                           <?php checked(get_user_meta($user->ID, 'ems_remember_language', true), '1'); ?> />
                    <label for="ems_remember_language">
                        <?php _e('Remember my language choice across sessions', 'environmental-multilang-support'); ?>
                    </label>
                </td>
            </tr>
        </table>
        
        <?php
        wp_nonce_field('ems_user_preferences', 'ems_user_preferences_nonce');
    }
    
    /**
     * Save user language field
     *
     * @param int $user_id
     */
    public function save_user_language_field($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }
        
        if (!wp_verify_nonce($_POST['ems_user_preferences_nonce'], 'ems_user_preferences')) {
            return;
        }
        
        // Save preferred language
        if (isset($_POST['ems_preferred_language'])) {
            $language = sanitize_text_field($_POST['ems_preferred_language']);
            if (empty($language) || $this->is_supported_language($language)) {
                update_user_meta($user_id, 'ems_preferred_language', $language);
            }
        }
        
        // Save auto-translate preference
        $auto_translate = isset($_POST['ems_auto_translate']) ? '1' : '0';
        update_user_meta($user_id, 'ems_auto_translate', $auto_translate);
        
        // Save remember language preference
        $remember_language = isset($_POST['ems_remember_language']) ? '1' : '0';
        update_user_meta($user_id, 'ems_remember_language', $remember_language);
    }
    
    /**
     * Restore user language on login
     *
     * @param string $user_login
     * @param WP_User $user
     */
    public function restore_user_language_on_login($user_login, $user) {
        $user_lang = get_user_meta($user->ID, 'ems_preferred_language', true);
        $remember = get_user_meta($user->ID, 'ems_remember_language', true);
        
        if ($user_lang && $remember === '1') {
            $this->set_language_preference($user_lang);
        }
    }
    
    /**
     * Clear language cookie on logout
     */
    public function clear_language_cookie_on_logout() {
        $cookie_path = parse_url(home_url(), PHP_URL_PATH) ?: '/';
        $cookie_domain = parse_url(home_url(), PHP_URL_HOST);
        
        setcookie(
            $this->cookie_name,
            '',
            time() - 3600,
            $cookie_path,
            $cookie_domain,
            is_ssl(),
            true
        );
    }
    
    /**
     * Register admin settings
     */
    public function register_admin_settings() {
        register_setting('ems_preferences', 'ems_default_language');
        register_setting('ems_preferences', 'ems_auto_detect_language');
        register_setting('ems_preferences', 'ems_remember_user_choice');
        register_setting('ems_preferences', 'ems_cookie_expiry_days');
        register_setting('ems_preferences', 'ems_fallback_language');
    }
    
    /**
     * Add preferences page to admin menu
     */
    public function add_preferences_page() {
        add_options_page(
            __('Multilingual Preferences', 'environmental-multilang-support'),
            __('Multilingual', 'environmental-multilang-support'),
            'manage_options',
            'ems-preferences',
            [$this, 'render_preferences_page']
        );
    }
    
    /**
     * Render preferences page
     */
    public function render_preferences_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('ems_preferences');
            
            update_option('ems_default_language', sanitize_text_field($_POST['ems_default_language']));
            update_option('ems_auto_detect_language', isset($_POST['ems_auto_detect_language']) ? '1' : '0');
            update_option('ems_remember_user_choice', isset($_POST['ems_remember_user_choice']) ? '1' : '0');
            update_option('ems_cookie_expiry_days', intval($_POST['ems_cookie_expiry_days']));
            update_option('ems_fallback_language', sanitize_text_field($_POST['ems_fallback_language']));
            
            echo '<div class="notice notice-success"><p>' . __('Settings saved.', 'environmental-multilang-support') . '</p></div>';
        }
        
        $languages = $this->get_supported_languages_with_names();
        $default_lang = get_option('ems_default_language', 'en');
        $auto_detect = get_option('ems_auto_detect_language', '1') === '1';
        $remember_choice = get_option('ems_remember_user_choice', '1') === '1';
        $cookie_expiry = get_option('ems_cookie_expiry_days', '30');
        $fallback_lang = get_option('ems_fallback_language', 'en');
        ?>
        
        <div class="wrap">
            <h1><?php _e('Multilingual Preferences', 'environmental-multilang-support'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('ems_preferences'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="ems_default_language"><?php _e('Default Language', 'environmental-multilang-support'); ?></label>
                        </th>
                        <td>
                            <select name="ems_default_language" id="ems_default_language">
                                <?php foreach ($languages as $code => $name): ?>
                                    <option value="<?php echo esc_attr($code); ?>" <?php selected($default_lang, $code); ?>>
                                        <?php echo esc_html($name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php _e('The default language for the site.', 'environmental-multilang-support'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ems_auto_detect_language"><?php _e('Auto-detect Language', 'environmental-multilang-support'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="ems_auto_detect_language" id="ems_auto_detect_language" value="1" 
                                   <?php checked($auto_detect); ?> />
                            <label for="ems_auto_detect_language">
                                <?php _e('Automatically detect visitor language from browser settings', 'environmental-multilang-support'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ems_remember_user_choice"><?php _e('Remember User Choice', 'environmental-multilang-support'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="ems_remember_user_choice" id="ems_remember_user_choice" value="1" 
                                   <?php checked($remember_choice); ?> />
                            <label for="ems_remember_user_choice">
                                <?php _e('Remember user language choice using cookies', 'environmental-multilang-support'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ems_cookie_expiry_days"><?php _e('Cookie Expiry (Days)', 'environmental-multilang-support'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="ems_cookie_expiry_days" id="ems_cookie_expiry_days" 
                                   value="<?php echo esc_attr($cookie_expiry); ?>" min="1" max="365" />
                            <p class="description">
                                <?php _e('Number of days to remember user language choice.', 'environmental-multilang-support'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ems_fallback_language"><?php _e('Fallback Language', 'environmental-multilang-support'); ?></label>
                        </th>
                        <td>
                            <select name="ems_fallback_language" id="ems_fallback_language">
                                <?php foreach ($languages as $code => $name): ?>
                                    <option value="<?php echo esc_attr($code); ?>" <?php selected($fallback_lang, $code); ?>>
                                        <?php echo esc_html($name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php _e('Language to use when requested translation is not available.', 'environmental-multilang-support'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        
        <?php
    }
    
    /**
     * Check if language is supported
     *
     * @param string $language
     * @return bool
     */
    private function is_supported_language($language) {
        return in_array($language, $this->get_supported_languages());
    }
    
    /**
     * Get supported languages
     *
     * @return array
     */
    private function get_supported_languages() {
        return ['vi', 'en', 'zh', 'ja', 'ko', 'th', 'ar', 'he', 'fr', 'es'];
    }
    
    /**
     * Get supported languages with names
     *
     * @return array
     */
    private function get_supported_languages_with_names() {
        return [
            'vi' => 'Tiếng Việt',
            'en' => 'English',
            'zh' => '中文',
            'ja' => '日本語',
            'ko' => '한국어',
            'th' => 'ไทย',
            'ar' => 'العربية',
            'he' => 'עברית',
            'fr' => 'Français',
            'es' => 'Español'
        ];
    }
    
    /**
     * Get WordPress locale from language code
     *
     * @param string $language
     * @return string
     */
    private function get_wp_locale($language) {
        $locales = [
            'vi' => 'vi',
            'en' => 'en_US',
            'zh' => 'zh_CN',
            'ja' => 'ja',
            'ko' => 'ko_KR',
            'th' => 'th',
            'ar' => 'ar',
            'he' => 'he_IL',
            'fr' => 'fr_FR',
            'es' => 'es_ES'
        ];
        
        return isset($locales[$language]) ? $locales[$language] : 'en_US';
    }
    
    /**
     * Get translated current URL
     *
     * @param string $language
     * @return string
     */
    private function get_translated_current_url($language) {
        $current_url = (is_ssl() ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        return add_query_arg('lang', $language, $current_url);
    }
}
