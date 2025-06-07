<?php
/**
 * URL Manager Utility
 *
 * Manages multilingual URLs and routing
 *
 * @package Environmental_Multilang_Support
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EMS_URL_Manager {
    
    /**
     * Instance of this class
     *
     * @var EMS_URL_Manager
     */
    private static $instance = null;
    
    /**
     * URL structure type
     *
     * @var string
     */
    private $url_structure = 'query'; // 'query', 'subdomain', 'directory'
    
    /**
     * Supported languages
     *
     * @var array
     */
    private $supported_languages = ['vi', 'en', 'zh', 'ja', 'ko', 'th', 'ar', 'he', 'fr', 'es'];
    
    /**
     * Default language
     *
     * @var string
     */
    private $default_language = 'en';
    
    /**
     * Get instance
     *
     * @return EMS_URL_Manager
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
        $this->url_structure = get_option('ems_url_structure', 'query');
        $this->default_language = get_option('ems_default_language', 'en');
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_filter('the_permalink', [$this, 'localize_permalink'], 10, 2);
        add_filter('home_url', [$this, 'localize_home_url'], 10, 4);
        add_filter('site_url', [$this, 'localize_site_url'], 10, 4);
        add_action('template_redirect', [$this, 'handle_language_routing']);
        
        // Admin URL filters
        if (!is_admin()) {
            add_filter('wp_list_pages', [$this, 'localize_page_links']);
            add_filter('wp_nav_menu_items', [$this, 'localize_menu_items'], 10, 2);
        }
    }
    
    /**
     * Add rewrite rules for different URL structures
     */
    public function add_rewrite_rules() {
        $languages = implode('|', $this->supported_languages);
        
        switch ($this->url_structure) {
            case 'directory':
                // Add rules for /lang/page structure
                add_rewrite_rule(
                    '^(' . $languages . ')/(.+?)/?$',
                    'index.php?lang=$matches[1]&pagename=$matches[2]',
                    'top'
                );
                
                add_rewrite_rule(
                    '^(' . $languages . ')/?$',
                    'index.php?lang=$matches[1]',
                    'top'
                );
                break;
                
            case 'subdomain':
                // Subdomain handling is done via server configuration
                // We just need to detect the subdomain
                $this->detect_subdomain_language();
                break;
                
            case 'query':
            default:
                // Query parameter structure doesn't need rewrite rules
                break;
        }
    }
    
    /**
     * Add query vars
     *
     * @param array $vars
     * @return array
     */
    public function add_query_vars($vars) {
        $vars[] = 'lang';
        return $vars;
    }
    
    /**
     * Localize permalink
     *
     * @param string $permalink
     * @param WP_Post $post
     * @return string
     */
    public function localize_permalink($permalink, $post) {
        if (is_admin() || !$post) {
            return $permalink;
        }
        
        $current_lang = $this->get_current_language();
        $post_lang = get_post_meta($post->ID, '_ems_language', true) ?: $this->default_language;
        
        // If post has a specific language, generate URL for that language
        if ($post_lang && $post_lang !== $this->default_language) {
            return $this->generate_language_url($permalink, $post_lang);
        }
        
        // Otherwise, use current language if not default
        if ($current_lang !== $this->default_language) {
            return $this->generate_language_url($permalink, $current_lang);
        }
        
        return $permalink;
    }
    
    /**
     * Localize home URL
     *
     * @param string $url
     * @param string $path
     * @param string $scheme
     * @param int $blog_id
     * @return string
     */
    public function localize_home_url($url, $path, $scheme, $blog_id) {
        if (is_admin()) {
            return $url;
        }
        
        $current_lang = $this->get_current_language();
        
        if ($current_lang !== $this->default_language) {
            return $this->generate_language_url($url, $current_lang);
        }
        
        return $url;
    }
    
    /**
     * Localize site URL
     *
     * @param string $url
     * @param string $path
     * @param string $scheme
     * @param int $blog_id
     * @return string
     */
    public function localize_site_url($url, $path, $scheme, $blog_id) {
        return $this->localize_home_url($url, $path, $scheme, $blog_id);
    }
    
    /**
     * Handle language routing
     */
    public function handle_language_routing() {
        $detected_lang = $this->detect_language_from_url();
        
        if ($detected_lang && $this->is_supported_language($detected_lang)) {
            // Set language if detected from URL
            if (class_exists('EMS_User_Preferences')) {
                EMS_User_Preferences::get_instance()->set_language_preference($detected_lang);
            }
        } else {
            // Redirect to language-specific URL if needed
            $current_lang = $this->get_current_language();
            
            if ($current_lang !== $this->default_language && get_option('ems_force_language_urls', false)) {
                $current_url = $this->get_current_url();
                $localized_url = $this->generate_language_url($current_url, $current_lang);
                
                if ($current_url !== $localized_url) {
                    wp_redirect($localized_url, 302);
                    exit;
                }
            }
        }
    }
    
    /**
     * Localize page links
     *
     * @param string $output
     * @return string
     */
    public function localize_page_links($output) {
        $current_lang = $this->get_current_language();
        
        if ($current_lang === $this->default_language) {
            return $output;
        }
        
        // Replace href attributes with localized URLs
        $output = preg_replace_callback(
            '/href=["\']([^"\']+)["\']/i',
            function($matches) use ($current_lang) {
                $url = $matches[1];
                $localized_url = $this->generate_language_url($url, $current_lang);
                return 'href="' . $localized_url . '"';
            },
            $output
        );
        
        return $output;
    }
    
    /**
     * Localize menu items
     *
     * @param string $items
     * @param object $args
     * @return string
     */
    public function localize_menu_items($items, $args) {
        $current_lang = $this->get_current_language();
        
        if ($current_lang === $this->default_language) {
            return $items;
        }
        
        // Replace href attributes in menu items
        $items = preg_replace_callback(
            '/href=["\']([^"\']+)["\']/i',
            function($matches) use ($current_lang) {
                $url = $matches[1];
                $localized_url = $this->generate_language_url($url, $current_lang);
                return 'href="' . $localized_url . '"';
            },
            $items
        );
        
        return $items;
    }
    
    /**
     * Generate language-specific URL
     *
     * @param string $url
     * @param string $language
     * @return string
     */
    public function generate_language_url($url, $language) {
        if (!$this->is_supported_language($language) || $language === $this->default_language) {
            return $url;
        }
        
        switch ($this->url_structure) {
            case 'directory':
                return $this->generate_directory_url($url, $language);
                
            case 'subdomain':
                return $this->generate_subdomain_url($url, $language);
                
            case 'query':
            default:
                return $this->generate_query_url($url, $language);
        }
    }
    
    /**
     * Generate directory-based URL
     *
     * @param string $url
     * @param string $language
     * @return string
     */
    private function generate_directory_url($url, $language) {
        $parsed_url = parse_url($url);
        
        if (!$parsed_url) {
            return $url;
        }
        
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '/';
        
        // Remove existing language from path
        $path = preg_replace('/^\/(' . implode('|', $this->supported_languages) . ')\//', '/', $path);
        
        // Add new language to path
        $path = '/' . $language . $path;
        
        // Rebuild URL
        $new_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];
        
        if (isset($parsed_url['port'])) {
            $new_url .= ':' . $parsed_url['port'];
        }
        
        $new_url .= $path;
        
        if (isset($parsed_url['query'])) {
            $new_url .= '?' . $parsed_url['query'];
        }
        
        if (isset($parsed_url['fragment'])) {
            $new_url .= '#' . $parsed_url['fragment'];
        }
        
        return $new_url;
    }
    
    /**
     * Generate subdomain-based URL
     *
     * @param string $url
     * @param string $language
     * @return string
     */
    private function generate_subdomain_url($url, $language) {
        $parsed_url = parse_url($url);
        
        if (!$parsed_url || !isset($parsed_url['host'])) {
            return $url;
        }
        
        $host_parts = explode('.', $parsed_url['host']);
        
        // Remove existing language subdomain if present
        if (in_array($host_parts[0], $this->supported_languages)) {
            array_shift($host_parts);
        }
        
        // Add new language subdomain
        array_unshift($host_parts, $language);
        
        $new_host = implode('.', $host_parts);
        
        // Rebuild URL
        $new_url = $parsed_url['scheme'] . '://' . $new_host;
        
        if (isset($parsed_url['port'])) {
            $new_url .= ':' . $parsed_url['port'];
        }
        
        if (isset($parsed_url['path'])) {
            $new_url .= $parsed_url['path'];
        }
        
        if (isset($parsed_url['query'])) {
            $new_url .= '?' . $parsed_url['query'];
        }
        
        if (isset($parsed_url['fragment'])) {
            $new_url .= '#' . $parsed_url['fragment'];
        }
        
        return $new_url;
    }
    
    /**
     * Generate query parameter URL
     *
     * @param string $url
     * @param string $language
     * @return string
     */
    private function generate_query_url($url, $language) {
        return add_query_arg('lang', $language, $url);
    }
    
    /**
     * Detect language from URL
     *
     * @return string|false
     */
    public function detect_language_from_url() {
        switch ($this->url_structure) {
            case 'directory':
                return $this->detect_directory_language();
                
            case 'subdomain':
                return $this->detect_subdomain_language();
                
            case 'query':
            default:
                return $this->detect_query_language();
        }
    }
    
    /**
     * Detect language from directory structure
     *
     * @return string|false
     */
    private function detect_directory_language() {
        $request_uri = $_SERVER['REQUEST_URI'];
        $path = parse_url($request_uri, PHP_URL_PATH);
        
        if (preg_match('/^\/(' . implode('|', $this->supported_languages) . ')\//', $path, $matches)) {
            return $matches[1];
        }
        
        return false;
    }
    
    /**
     * Detect language from subdomain
     *
     * @return string|false
     */
    private function detect_subdomain_language() {
        $host = $_SERVER['HTTP_HOST'];
        $host_parts = explode('.', $host);
        
        if (count($host_parts) >= 2 && in_array($host_parts[0], $this->supported_languages)) {
            return $host_parts[0];
        }
        
        return false;
    }
    
    /**
     * Detect language from query parameter
     *
     * @return string|false
     */
    private function detect_query_language() {
        if (isset($_GET['lang']) && $this->is_supported_language($_GET['lang'])) {
            return sanitize_text_field($_GET['lang']);
        }
        
        return false;
    }
    
    /**
     * Get current URL
     *
     * @return string
     */
    public function get_current_url() {
        $protocol = is_ssl() ? 'https' : 'http';
        return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
    
    /**
     * Get clean URL (without language parameters)
     *
     * @param string $url
     * @return string
     */
    public function get_clean_url($url) {
        switch ($this->url_structure) {
            case 'directory':
                return preg_replace('/^\/(' . implode('|', $this->supported_languages) . ')\//', '/', $url);
                
            case 'subdomain':
                $parsed = parse_url($url);
                if ($parsed && isset($parsed['host'])) {
                    $host_parts = explode('.', $parsed['host']);
                    if (in_array($host_parts[0], $this->supported_languages)) {
                        array_shift($host_parts);
                        $parsed['host'] = implode('.', $host_parts);
                        return $this->build_url($parsed);
                    }
                }
                return $url;
                
            case 'query':
            default:
                return remove_query_arg('lang', $url);
        }
    }
    
    /**
     * Build URL from parsed components
     *
     * @param array $parsed
     * @return string
     */
    private function build_url($parsed) {
        $url = '';
        
        if (isset($parsed['scheme'])) {
            $url .= $parsed['scheme'] . '://';
        }
        
        if (isset($parsed['host'])) {
            $url .= $parsed['host'];
        }
        
        if (isset($parsed['port'])) {
            $url .= ':' . $parsed['port'];
        }
        
        if (isset($parsed['path'])) {
            $url .= $parsed['path'];
        }
        
        if (isset($parsed['query'])) {
            $url .= '?' . $parsed['query'];
        }
        
        if (isset($parsed['fragment'])) {
            $url .= '#' . $parsed['fragment'];
        }
        
        return $url;
    }
    
    /**
     * Get current language
     *
     * @return string
     */
    private function get_current_language() {
        if (class_exists('EMS_User_Preferences')) {
            return EMS_User_Preferences::get_instance()->get_language_preference();
        }
        
        return $this->default_language;
    }
    
    /**
     * Check if language is supported
     *
     * @param string $language
     * @return bool
     */
    private function is_supported_language($language) {
        return in_array($language, $this->supported_languages);
    }
    
    /**
     * Get language from URL for specific post
     *
     * @param int $post_id
     * @param string $language
     * @return string
     */
    public function get_post_language_url($post_id, $language) {
        $permalink = get_permalink($post_id);
        return $this->generate_language_url($permalink, $language);
    }
    
    /**
     * Get all language URLs for current page
     *
     * @return array
     */
    public function get_all_language_urls() {
        $current_url = $this->get_current_url();
        $clean_url = $this->get_clean_url($current_url);
        $urls = [];
        
        foreach ($this->supported_languages as $language) {
            if ($language === $this->default_language) {
                $urls[$language] = $clean_url;
            } else {
                $urls[$language] = $this->generate_language_url($clean_url, $language);
            }
        }
        
        return $urls;
    }
    
    /**
     * Set URL structure
     *
     * @param string $structure
     * @return bool
     */
    public function set_url_structure($structure) {
        if (in_array($structure, ['query', 'directory', 'subdomain'])) {
            $this->url_structure = $structure;
            update_option('ems_url_structure', $structure);
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get URL structure
     *
     * @return string
     */
    public function get_url_structure() {
        return $this->url_structure;
    }
}
