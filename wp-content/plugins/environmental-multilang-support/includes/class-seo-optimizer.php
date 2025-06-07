<?php
/**
 * SEO Optimizer Component
 *
 * Handles language-specific SEO optimization for multilingual content
 *
 * @package Environmental_Multilang_Support
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EMS_SEO_Optimizer {
    
    /**
     * Instance of this class
     *
     * @var EMS_SEO_Optimizer
     */
    private static $instance = null;
    
    /**
     * Supported languages
     *
     * @var array
     */
    private $supported_languages = [
        'vi' => 'vi-VN',
        'en' => 'en-US',
        'zh' => 'zh-CN',
        'ja' => 'ja-JP',
        'ko' => 'ko-KR',
        'th' => 'th-TH',
        'ar' => 'ar-SA',
        'he' => 'he-IL',
        'fr' => 'fr-FR',
        'es' => 'es-ES'
    ];
    
    /**
     * Get instance
     *
     * @return EMS_SEO_Optimizer
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
        add_action('wp_head', [$this, 'add_hreflang_tags'], 1);
        add_action('wp_head', [$this, 'add_language_meta_tags'], 2);
        add_action('wp_head', [$this, 'add_canonical_tags'], 3);
        add_filter('document_title_parts', [$this, 'modify_title_parts']);
        add_filter('wp_title', [$this, 'modify_wp_title'], 10, 2);
        add_action('wp_head', [$this, 'add_open_graph_tags']);
        add_action('wp_head', [$this, 'add_schema_markup']);
        add_filter('the_seo_framework_generated_description', [$this, 'modify_meta_description']);
        add_filter('wpseo_title', [$this, 'yoast_title_filter']);
        add_filter('wpseo_metadesc', [$this, 'yoast_description_filter']);
        add_filter('rank_math/frontend/title', [$this, 'rankmath_title_filter']);
        add_filter('rank_math/frontend/description', [$this, 'rankmath_description_filter']);
        
        // Sitemap generation
        add_action('init', [$this, 'init_sitemap_generation']);
        add_filter('wp_sitemaps_posts_entry', [$this, 'modify_sitemap_entry'], 10, 3);
    }
    
    /**
     * Add hreflang tags for multilingual SEO
     */
    public function add_hreflang_tags() {
        if (!is_singular() && !is_home() && !is_front_page()) {
            return;
        }
        
        $current_post_id = $this->get_current_post_id();
        if (!$current_post_id) {
            return;
        }
        
        $current_lang = $this->get_current_language();
        $translations = $this->get_post_translations($current_post_id);
        
        // Add current page hreflang
        $current_url = $this->get_current_url();
        $current_locale = $this->get_locale_from_lang($current_lang);
        
        echo '<link rel="alternate" hreflang="' . esc_attr($current_locale) . '" href="' . esc_url($current_url) . '">' . "\n";
        
        // Add translations hreflang
        foreach ($translations as $lang => $post_id) {
            if ($lang !== $current_lang && $post_id) {
                $url = $this->get_translated_url($post_id, $lang);
                $locale = $this->get_locale_from_lang($lang);
                echo '<link rel="alternate" hreflang="' . esc_attr($locale) . '" href="' . esc_url($url) . '">' . "\n";
            }
        }
        
        // Add x-default for primary language
        $default_lang = get_option('ems_default_language', 'en');
        if (isset($translations[$default_lang])) {
            $default_url = $this->get_translated_url($translations[$default_lang], $default_lang);
            echo '<link rel="alternate" hreflang="x-default" href="' . esc_url($default_url) . '">' . "\n";
        }
    }
    
    /**
     * Add language meta tags
     */
    public function add_language_meta_tags() {
        $current_lang = $this->get_current_language();
        $locale = $this->get_locale_from_lang($current_lang);
        
        echo '<meta property="og:locale" content="' . esc_attr($locale) . '">' . "\n";
        echo '<meta name="language" content="' . esc_attr($current_lang) . '">' . "\n";
        echo '<meta http-equiv="content-language" content="' . esc_attr($current_lang) . '">' . "\n";
        
        // Add alternate locales for Open Graph
        $current_post_id = $this->get_current_post_id();
        if ($current_post_id) {
            $translations = $this->get_post_translations($current_post_id);
            foreach ($translations as $lang => $post_id) {
                if ($lang !== $current_lang && $post_id) {
                    $alt_locale = $this->get_locale_from_lang($lang);
                    echo '<meta property="og:locale:alternate" content="' . esc_attr($alt_locale) . '">' . "\n";
                }
            }
        }
    }
    
    /**
     * Add canonical tags
     */
    public function add_canonical_tags() {
        if (is_singular()) {
            $canonical_url = $this->get_canonical_url();
            if ($canonical_url) {
                echo '<link rel="canonical" href="' . esc_url($canonical_url) . '">' . "\n";
            }
        }
    }
    
    /**
     * Modify title parts for multilingual content
     *
     * @param array $title_parts
     * @return array
     */
    public function modify_title_parts($title_parts) {
        $current_lang = $this->get_current_language();
        $lang_name = $this->get_language_name($current_lang);
        
        // Add language indicator for non-default languages
        $default_lang = get_option('ems_default_language', 'en');
        if ($current_lang !== $default_lang && get_option('ems_add_lang_to_title', false)) {
            $title_parts['lang'] = $lang_name;
        }
        
        return $title_parts;
    }
    
    /**
     * Modify wp_title for older themes
     *
     * @param string $title
     * @param string $sep
     * @return string
     */
    public function modify_wp_title($title, $sep) {
        $current_lang = $this->get_current_language();
        $default_lang = get_option('ems_default_language', 'en');
        
        if ($current_lang !== $default_lang && get_option('ems_add_lang_to_title', false)) {
            $lang_name = $this->get_language_name($current_lang);
            $title = $title . " {$sep} {$lang_name}";
        }
        
        return $title;
    }
    
    /**
     * Add Open Graph tags for multilingual content
     */
    public function add_open_graph_tags() {
        if (!is_singular()) {
            return;
        }
        
        $post_id = get_the_ID();
        $current_lang = $this->get_current_language();
        
        // Get translated title and description
        $title = $this->get_translated_title($post_id, $current_lang);
        $description = $this->get_translated_description($post_id, $current_lang);
        $url = $this->get_translated_url($post_id, $current_lang);
        
        if ($title) {
            echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
        }
        
        if ($description) {
            echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
        }
        
        if ($url) {
            echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
        }
        
        // Add site name with language
        $site_name = get_bloginfo('name');
        $lang_name = $this->get_language_name($current_lang);
        echo '<meta property="og:site_name" content="' . esc_attr($site_name . ' - ' . $lang_name) . '">' . "\n";
    }
    
    /**
     * Add schema markup for multilingual content
     */
    public function add_schema_markup() {
        if (!is_singular()) {
            return;
        }
        
        $post_id = get_the_ID();
        $current_lang = $this->get_current_language();
        $translations = $this->get_post_translations($post_id);
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'url' => $this->get_translated_url($post_id, $current_lang),
            'name' => $this->get_translated_title($post_id, $current_lang),
            'description' => $this->get_translated_description($post_id, $current_lang),
            'inLanguage' => $this->get_locale_from_lang($current_lang),
        ];
        
        // Add translations
        if (!empty($translations)) {
            $schema['translationOfWork'] = [];
            foreach ($translations as $lang => $trans_post_id) {
                if ($lang !== $current_lang && $trans_post_id) {
                    $schema['translationOfWork'][] = [
                        '@type' => 'WebPage',
                        'url' => $this->get_translated_url($trans_post_id, $lang),
                        'inLanguage' => $this->get_locale_from_lang($lang)
                    ];
                }
            }
        }
        
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }
    
    /**
     * Modify meta description
     *
     * @param string $description
     * @return string
     */
    public function modify_meta_description($description) {
        if (is_singular()) {
            $post_id = get_the_ID();
            $current_lang = $this->get_current_language();
            $translated_desc = $this->get_translated_description($post_id, $current_lang);
            
            if ($translated_desc) {
                return $translated_desc;
            }
        }
        
        return $description;
    }
    
    /**
     * Yoast SEO title filter
     *
     * @param string $title
     * @return string
     */
    public function yoast_title_filter($title) {
        if (is_singular()) {
            $post_id = get_the_ID();
            $current_lang = $this->get_current_language();
            $translated_title = $this->get_translated_title($post_id, $current_lang);
            
            if ($translated_title) {
                return $translated_title;
            }
        }
        
        return $title;
    }
    
    /**
     * Yoast SEO description filter
     *
     * @param string $description
     * @return string
     */
    public function yoast_description_filter($description) {
        return $this->modify_meta_description($description);
    }
    
    /**
     * RankMath title filter
     *
     * @param string $title
     * @return string
     */
    public function rankmath_title_filter($title) {
        return $this->yoast_title_filter($title);
    }
    
    /**
     * RankMath description filter
     *
     * @param string $description
     * @return string
     */
    public function rankmath_description_filter($description) {
        return $this->modify_meta_description($description);
    }
    
    /**
     * Initialize sitemap generation
     */
    public function init_sitemap_generation() {
        add_action('wp_sitemaps_init', [$this, 'register_multilingual_sitemaps']);
    }
    
    /**
     * Register multilingual sitemaps
     *
     * @param WP_Sitemaps $wp_sitemaps
     */
    public function register_multilingual_sitemaps($wp_sitemaps) {
        foreach ($this->supported_languages as $lang => $locale) {
            if ($this->has_content_in_language($lang)) {
                $wp_sitemaps->add_provider('ems_posts_' . $lang, new EMS_Sitemap_Posts_Provider($lang));
            }
        }
    }
    
    /**
     * Modify sitemap entry
     *
     * @param array $sitemap_entry
     * @param WP_Post $post
     * @param string $sitemap
     * @return array
     */
    public function modify_sitemap_entry($sitemap_entry, $post, $sitemap) {
        $current_lang = $this->get_post_language($post->ID);
        
        if ($current_lang) {
            $sitemap_entry['loc'] = $this->get_translated_url($post->ID, $current_lang);
            
            // Add alternate URLs for translations
            $translations = $this->get_post_translations($post->ID);
            if (!empty($translations)) {
                $sitemap_entry['alternates'] = [];
                foreach ($translations as $lang => $trans_post_id) {
                    if ($trans_post_id && $lang !== $current_lang) {
                        $sitemap_entry['alternates'][] = [
                            'hreflang' => $this->get_locale_from_lang($lang),
                            'href' => $this->get_translated_url($trans_post_id, $lang)
                        ];
                    }
                }
            }
        }
        
        return $sitemap_entry;
    }
    
    /**
     * Get current post ID
     *
     * @return int|false
     */
    private function get_current_post_id() {
        if (is_singular()) {
            return get_the_ID();
        } elseif (is_home() || is_front_page()) {
            return get_option('page_for_posts', 0) ?: get_option('page_on_front', 0);
        }
        
        return false;
    }
    
    /**
     * Get current language
     *
     * @return string
     */
    private function get_current_language() {
        if (isset($_GET['lang'])) {
            return sanitize_text_field($_GET['lang']);
        }
        
        if (isset($_COOKIE['ems_language'])) {
            return sanitize_text_field($_COOKIE['ems_language']);
        }
        
        return get_option('ems_default_language', 'en');
    }
    
    /**
     * Get current URL
     *
     * @return string
     */
    private function get_current_url() {
        return (is_ssl() ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
    
    /**
     * Get post translations
     *
     * @param int $post_id
     * @return array
     */
    private function get_post_translations($post_id) {
        global $wpdb;
        
        $translations = [];
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT language_code, translated_post_id 
             FROM {$wpdb->prefix}ems_translation_pairs 
             WHERE original_post_id = %d OR translated_post_id = %d",
            $post_id,
            $post_id
        ));
        
        foreach ($results as $result) {
            $translations[$result->language_code] = $result->translated_post_id;
        }
        
        return $translations;
    }
    
    /**
     * Get translated URL
     *
     * @param int $post_id
     * @param string $lang
     * @return string
     */
    private function get_translated_url($post_id, $lang) {
        $url = get_permalink($post_id);
        return add_query_arg('lang', $lang, $url);
    }
    
    /**
     * Get canonical URL
     *
     * @return string
     */
    private function get_canonical_url() {
        $post_id = $this->get_current_post_id();
        if (!$post_id) {
            return '';
        }
        
        $current_lang = $this->get_current_language();
        return $this->get_translated_url($post_id, $current_lang);
    }
    
    /**
     * Get locale from language code
     *
     * @param string $lang
     * @return string
     */
    private function get_locale_from_lang($lang) {
        return isset($this->supported_languages[$lang]) ? $this->supported_languages[$lang] : $lang . '-' . strtoupper($lang);
    }
    
    /**
     * Get language name
     *
     * @param string $lang
     * @return string
     */
    private function get_language_name($lang) {
        $names = [
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
        
        return isset($names[$lang]) ? $names[$lang] : $lang;
    }
    
    /**
     * Get translated title
     *
     * @param int $post_id
     * @param string $lang
     * @return string
     */
    private function get_translated_title($post_id, $lang) {
        $title = get_the_title($post_id);
        
        // Process inline translations
        if (class_exists('EMS_Translation_Manager')) {
            $translation_manager = EMS_Translation_Manager::get_instance();
            $title = $translation_manager->process_inline_translations($title, $lang);
        }
        
        return $title;
    }
    
    /**
     * Get translated description
     *
     * @param int $post_id
     * @param string $lang
     * @return string
     */
    private function get_translated_description($post_id, $lang) {
        $post = get_post($post_id);
        if (!$post) {
            return '';
        }
        
        $description = wp_trim_words($post->post_excerpt ?: $post->post_content, 20);
        
        // Process inline translations
        if (class_exists('EMS_Translation_Manager')) {
            $translation_manager = EMS_Translation_Manager::get_instance();
            $description = $translation_manager->process_inline_translations($description, $lang);
        }
        
        return $description;
    }
    
    /**
     * Get post language
     *
     * @param int $post_id
     * @return string
     */
    private function get_post_language($post_id) {
        return get_post_meta($post_id, '_ems_language', true) ?: get_option('ems_default_language', 'en');
    }
    
    /**
     * Check if has content in language
     *
     * @param string $lang
     * @return bool
     */
    private function has_content_in_language($lang) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} 
             WHERE meta_key = '_ems_language' AND meta_value = %s",
            $lang
        ));
        
        return $count > 0;
    }
}
