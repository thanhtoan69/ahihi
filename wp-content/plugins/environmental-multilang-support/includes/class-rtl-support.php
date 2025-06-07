<?php
/**
 * RTL Support Component
 *
 * Handles Right-to-Left language support for Arabic and Hebrew
 *
 * @package Environmental_Multilang_Support
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EMS_RTL_Support {
    
    /**
     * Instance of this class
     *
     * @var EMS_RTL_Support
     */
    private static $instance = null;
    
    /**
     * RTL languages
     *
     * @var array
     */
    private $rtl_languages = ['ar', 'he'];
    
    /**
     * Current language direction
     *
     * @var string
     */
    private $current_direction = 'ltr';
    
    /**
     * Get instance
     *
     * @return EMS_RTL_Support
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
        $this->set_current_direction();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_head', [$this, 'add_rtl_styles']);
        add_action('admin_head', [$this, 'add_admin_rtl_styles']);
        add_filter('body_class', [$this, 'add_rtl_body_class']);
        add_filter('admin_body_class', [$this, 'add_admin_rtl_body_class']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_rtl_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_rtl_scripts']);
        
        // WordPress built-in RTL support
        add_filter('locale_stylesheet_uri', [$this, 'rtl_stylesheet_uri'], 10, 2);
        add_action('wp_head', [$this, 'rtl_meta_tags']);
    }
    
    /**
     * Set current language direction
     */
    private function set_current_direction() {
        $current_lang = $this->get_current_language();
        $this->current_direction = $this->is_rtl_language($current_lang) ? 'rtl' : 'ltr';
    }
    
    /**
     * Get current language
     *
     * @return string
     */
    private function get_current_language() {
        // Try to get from various sources
        if (isset($_GET['lang'])) {
            return sanitize_text_field($_GET['lang']);
        }
        
        if (isset($_COOKIE['ems_language'])) {
            return sanitize_text_field($_COOKIE['ems_language']);
        }
        
        // Default to WordPress locale
        $locale = get_locale();
        return substr($locale, 0, 2);
    }
    
    /**
     * Check if language is RTL
     *
     * @param string $lang_code
     * @return bool
     */
    public function is_rtl_language($lang_code) {
        return in_array($lang_code, $this->rtl_languages);
    }
    
    /**
     * Get current direction
     *
     * @return string
     */
    public function get_current_direction() {
        return $this->current_direction;
    }
    
    /**
     * Check if current language is RTL
     *
     * @return bool
     */
    public function is_current_rtl() {
        return $this->current_direction === 'rtl';
    }
    
    /**
     * Add RTL styles to frontend
     */
    public function add_rtl_styles() {
        if (!$this->is_current_rtl()) {
            return;
        }
        
        ?>
        <style type="text/css">
        /* RTL Styles for Environmental Platform */
        body.rtl {
            direction: rtl;
            text-align: right;
        }
        
        body.rtl .site-header {
            text-align: right;
        }
        
        body.rtl .main-navigation ul {
            text-align: right;
        }
        
        body.rtl .main-navigation ul li {
            float: right;
        }
        
        body.rtl .entry-content {
            direction: rtl;
            text-align: right;
        }
        
        body.rtl .widget {
            text-align: right;
        }
        
        body.rtl .widget ul li {
            text-align: right;
        }
        
        body.rtl .comment-content {
            direction: rtl;
            text-align: right;
        }
        
        /* Environmental Platform specific RTL styles */
        body.rtl .environmental-card {
            text-align: right;
        }
        
        body.rtl .environmental-stats {
            direction: rtl;
        }
        
        body.rtl .petition-form {
            text-align: right;
        }
        
        body.rtl .donation-form {
            text-align: right;
        }
        
        body.rtl .item-exchange-grid {
            direction: rtl;
        }
        
        body.rtl .achievement-badge {
            float: right;
            margin-left: 0;
            margin-right: 10px;
        }
        
        /* Language switcher RTL adjustments */
        body.rtl .ems-language-switcher {
            text-align: right;
        }
        
        body.rtl .ems-language-switcher .flag-icon {
            margin-left: 5px;
            margin-right: 0;
        }
        
        /* Form elements RTL */
        body.rtl input[type="text"],
        body.rtl input[type="email"],
        body.rtl textarea,
        body.rtl select {
            text-align: right;
        }
        
        /* Buttons RTL */
        body.rtl .btn {
            text-align: center;
        }
        
        /* Tables RTL */
        body.rtl table {
            direction: rtl;
        }
        
        body.rtl th,
        body.rtl td {
            text-align: right;
        }
        </style>
        <?php
    }
    
    /**
     * Add RTL styles to admin
     */
    public function add_admin_rtl_styles() {
        if (!$this->is_current_rtl()) {
            return;
        }
        
        ?>
        <style type="text/css">
        /* Admin RTL Styles */
        body.rtl #wpcontent {
            direction: rtl;
        }
        
        body.rtl .wrap {
            direction: rtl;
        }
        
        body.rtl .form-table th {
            text-align: right;
        }
        
        body.rtl .form-table td {
            text-align: right;
        }
        
        body.rtl #poststuff {
            direction: rtl;
        }
        
        body.rtl .postbox {
            direction: rtl;
        }
        
        body.rtl .postbox h3 {
            text-align: right;
        }
        
        /* Environmental Platform admin RTL */
        body.rtl .ems-translation-meta-box {
            direction: rtl;
        }
        
        body.rtl .ems-language-links {
            text-align: right;
        }
        
        body.rtl .ems-admin-notice {
            text-align: right;
        }
        </style>
        <?php
    }
    
    /**
     * Add RTL body class
     *
     * @param array $classes
     * @return array
     */
    public function add_rtl_body_class($classes) {
        if ($this->is_current_rtl()) {
            $classes[] = 'rtl';
            $classes[] = 'ems-rtl';
        }
        return $classes;
    }
    
    /**
     * Add RTL admin body class
     *
     * @param string $classes
     * @return string
     */
    public function add_admin_rtl_body_class($classes) {
        if ($this->is_current_rtl()) {
            $classes .= ' rtl ems-rtl';
        }
        return $classes;
    }
    
    /**
     * Enqueue RTL scripts for frontend
     */
    public function enqueue_rtl_scripts() {
        if (!$this->is_current_rtl()) {
            return;
        }
        
        wp_enqueue_script(
            'ems-rtl-support',
            EMS_PLUGIN_URL . 'assets/js/rtl-support.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        wp_localize_script('ems-rtl-support', 'emsRTL', [
            'is_rtl' => true,
            'direction' => 'rtl',
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ems_rtl_nonce')
        ]);
    }
    
    /**
     * Enqueue RTL scripts for admin
     */
    public function enqueue_admin_rtl_scripts() {
        if (!$this->is_current_rtl()) {
            return;
        }
        
        wp_enqueue_script(
            'ems-admin-rtl-support',
            EMS_PLUGIN_URL . 'assets/js/admin-rtl-support.js',
            ['jquery'],
            '1.0.0',
            true
        );
    }
    
    /**
     * Filter RTL stylesheet URI
     *
     * @param string $stylesheet_uri
     * @param string $stylesheet_dir_uri
     * @return string
     */
    public function rtl_stylesheet_uri($stylesheet_uri, $stylesheet_dir_uri) {
        if ($this->is_current_rtl()) {
            $rtl_stylesheet = str_replace('.css', '-rtl.css', $stylesheet_uri);
            if (file_exists(str_replace(get_site_url(), ABSPATH, $rtl_stylesheet))) {
                return $rtl_stylesheet;
            }
        }
        return $stylesheet_uri;
    }
    
    /**
     * Add RTL meta tags
     */
    public function rtl_meta_tags() {
        if ($this->is_current_rtl()) {
            echo '<meta name="direction" content="rtl">' . "\n";
            echo '<meta name="text-direction" content="rtl">' . "\n";
        }
    }
    
    /**
     * Get RTL text direction for specific language
     *
     * @param string $lang_code
     * @return string
     */
    public function get_text_direction($lang_code) {
        return $this->is_rtl_language($lang_code) ? 'rtl' : 'ltr';
    }
    
    /**
     * Apply RTL formatting to text
     *
     * @param string $text
     * @param string $lang_code
     * @return string
     */
    public function format_rtl_text($text, $lang_code = null) {
        if ($lang_code === null) {
            $lang_code = $this->get_current_language();
        }
        
        if ($this->is_rtl_language($lang_code)) {
            return '<span dir="rtl" class="ems-rtl-text">' . $text . '</span>';
        }
        
        return $text;
    }
    
    /**
     * Get language alignment class
     *
     * @param string $lang_code
     * @return string
     */
    public function get_alignment_class($lang_code = null) {
        if ($lang_code === null) {
            $lang_code = $this->get_current_language();
        }
        
        return $this->is_rtl_language($lang_code) ? 'text-right' : 'text-left';
    }
    
    /**
     * Convert CSS for RTL
     *
     * @param string $css
     * @return string
     */
    public function convert_css_rtl($css) {
        if (!$this->is_current_rtl()) {
            return $css;
        }
        
        // Simple RTL CSS conversion
        $css = str_replace('float: left', 'float: right', $css);
        $css = str_replace('float:left', 'float:right', $css);
        $css = str_replace('text-align: left', 'text-align: right', $css);
        $css = str_replace('text-align:left', 'text-align:right', $css);
        
        // Margin and padding conversion
        $css = preg_replace('/margin-left:\s*([^;]+);/', 'margin-right: $1;', $css);
        $css = preg_replace('/margin-right:\s*([^;]+);/', 'margin-left: $1;', $css);
        $css = preg_replace('/padding-left:\s*([^;]+);/', 'padding-right: $1;', $css);
        $css = preg_replace('/padding-right:\s*([^;]+);/', 'padding-left: $1;', $css);
        
        return $css;
    }
}
