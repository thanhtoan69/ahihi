<?php
/**
 * Translation API Utility
 * 
 * Handles automatic translation using various translation services
 */

if (!defined('ABSPATH')) {
    exit;
}

class EMS_Translation_API {

    /**
     * Translation providers
     */
    private $providers = array();

    /**
     * Current provider
     */
    private $current_provider;

    /**
     * API options
     */
    private $options;

    /**
     * Constructor
     */
    public function __construct() {
        $this->options = get_option('ems_options', array());
        $this->current_provider = isset($this->options['translation_provider']) ? $this->options['translation_provider'] : 'google';
        
        $this->init_providers();
        add_action('wp_ajax_ems_translate_text', array($this, 'ajax_translate_text'));
        add_action('wp_ajax_ems_translate_post', array($this, 'ajax_translate_post'));
    }

    /**
     * Initialize translation providers
     */
    private function init_providers() {
        $this->providers = array(
            'google' => array(
                'name' => 'Google Translate',
                'class' => 'EMS_Google_Translate',
                'api_key_required' => true,
                'supported_languages' => $this->get_google_supported_languages(),
            ),
            'microsoft' => array(
                'name' => 'Microsoft Translator',
                'class' => 'EMS_Microsoft_Translate',
                'api_key_required' => true,
                'supported_languages' => $this->get_microsoft_supported_languages(),
            ),
            'deepl' => array(
                'name' => 'DeepL',
                'class' => 'EMS_DeepL_Translate',
                'api_key_required' => true,
                'supported_languages' => $this->get_deepl_supported_languages(),
            ),
            'libre' => array(
                'name' => 'LibreTranslate',
                'class' => 'EMS_Libre_Translate',
                'api_key_required' => false,
                'supported_languages' => $this->get_libre_supported_languages(),
            ),
        );
    }

    /**
     * Translate text
     */
    public function translate_text($text, $from_language, $to_language, $options = array()) {
        if (empty($text) || $from_language === $to_language) {
            return $text;
        }

        // Check cache first
        $cache_key = $this->get_cache_key($text, $from_language, $to_language);
        $cached_translation = get_transient($cache_key);
        if ($cached_translation !== false) {
            return $cached_translation;
        }

        // Get provider instance
        $provider = $this->get_provider_instance($this->current_provider);
        if (!$provider) {
            return new WP_Error('no_provider', __('Translation provider not available.', 'environmental-multilang-support'));
        }

        // Perform translation
        $translation = $provider->translate($text, $from_language, $to_language, $options);
        
        if (is_wp_error($translation)) {
            return $translation;
        }

        // Cache the translation
        $cache_duration = apply_filters('ems_translation_cache_duration', DAY_IN_SECONDS);
        set_transient($cache_key, $translation, $cache_duration);

        // Track API usage
        $this->track_api_usage($this->current_provider, strlen($text));

        return $translation;
    }

    /**
     * Translate post content
     */
    public function translate_post($post_id, $from_language, $to_language, $options = array()) {
        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('invalid_post', __('Post not found.', 'environmental-multilang-support'));
        }

        $translation_data = array();

        // Translate title
        if (!empty($post->post_title)) {
            $translated_title = $this->translate_text($post->post_title, $from_language, $to_language, $options);
            if (!is_wp_error($translated_title)) {
                $translation_data['post_title'] = $translated_title;
            }
        }

        // Translate content
        if (!empty($post->post_content)) {
            // Process shortcodes and HTML
            $processed_content = $this->process_content_for_translation($post->post_content);
            $translated_content = $this->translate_text($processed_content, $from_language, $to_language, $options);
            if (!is_wp_error($translated_content)) {
                $translation_data['post_content'] = $this->restore_content_after_translation($translated_content);
            }
        }

        // Translate excerpt
        if (!empty($post->post_excerpt)) {
            $translated_excerpt = $this->translate_text($post->post_excerpt, $from_language, $to_language, $options);
            if (!is_wp_error($translated_excerpt)) {
                $translation_data['post_excerpt'] = $translated_excerpt;
            }
        }

        // Translate meta fields
        $meta_fields_to_translate = apply_filters('ems_translatable_meta_fields', array(
            '_yoast_wpseo_title',
            '_yoast_wpseo_metadesc',
            '_environmental_subtitle',
            '_environmental_summary',
        ));

        foreach ($meta_fields_to_translate as $meta_key) {
            $meta_value = get_post_meta($post_id, $meta_key, true);
            if (!empty($meta_value)) {
                $translated_meta = $this->translate_text($meta_value, $from_language, $to_language, $options);
                if (!is_wp_error($translated_meta)) {
                    $translation_data['meta'][$meta_key] = $translated_meta;
                }
            }
        }

        return $translation_data;
    }

    /**
     * Bulk translate content
     */
    public function bulk_translate($content_type, $from_language, $to_language, $options = array()) {
        $results = array(
            'success' => 0,
            'failed' => 0,
            'errors' => array(),
        );

        switch ($content_type) {
            case 'posts':
                $posts = get_posts(array(
                    'post_type' => 'post',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        array(
                            'key' => '_ems_language',
                            'value' => $from_language,
                            'compare' => '=',
                        ),
                    ),
                ));

                foreach ($posts as $post) {
                    $translation = $this->translate_post($post->ID, $from_language, $to_language, $options);
                    if (is_wp_error($translation)) {
                        $results['failed']++;
                        $results['errors'][] = sprintf(
                            __('Failed to translate post "%s": %s', 'environmental-multilang-support'),
                            $post->post_title,
                            $translation->get_error_message()
                        );
                    } else {
                        $this->create_translated_post($post->ID, $translation, $to_language);
                        $results['success']++;
                    }
                }
                break;

            case 'pages':
                // Similar implementation for pages
                break;

            case 'categories':
                // Implementation for categories
                break;

            case 'tags':
                // Implementation for tags
                break;
        }

        return $results;
    }

    /**
     * Get provider instance
     */
    private function get_provider_instance($provider_name) {
        if (!isset($this->providers[$provider_name])) {
            return null;
        }

        $provider_config = $this->providers[$provider_name];
        $class_name = $provider_config['class'];

        if (!class_exists($class_name)) {
            require_once EMS_PLUGIN_DIR . 'includes/translation-providers/class-' . str_replace('_', '-', strtolower($class_name)) . '.php';
        }

        if (!class_exists($class_name)) {
            return null;
        }

        return new $class_name($this->options);
    }

    /**
     * Process content for translation
     */
    private function process_content_for_translation($content) {
        // Remove shortcodes temporarily
        $shortcode_pattern = '/\[([^\]]+)\]/';
        preg_match_all($shortcode_pattern, $content, $matches);
        $shortcodes = $matches[0];
        
        foreach ($shortcodes as $index => $shortcode) {
            $placeholder = "{{SHORTCODE_$index}}";
            $content = str_replace($shortcode, $placeholder, $content);
        }

        // Store shortcodes for restoration
        set_transient('ems_shortcodes_' . md5($content), $shortcodes, HOUR_IN_SECONDS);

        // Remove HTML tags temporarily
        $html_pattern = '/<[^>]+>/';
        preg_match_all($html_pattern, $content, $html_matches);
        $html_tags = $html_matches[0];
        
        foreach ($html_tags as $index => $tag) {
            $placeholder = "{{HTML_$index}}";
            $content = str_replace($tag, $placeholder, $content);
        }

        // Store HTML tags for restoration
        set_transient('ems_html_tags_' . md5($content), $html_tags, HOUR_IN_SECONDS);

        return $content;
    }

    /**
     * Restore content after translation
     */
    private function restore_content_after_translation($content) {
        // Restore HTML tags
        $html_tags = get_transient('ems_html_tags_' . md5($content));
        if ($html_tags) {
            foreach ($html_tags as $index => $tag) {
                $placeholder = "{{HTML_$index}}";
                $content = str_replace($placeholder, $tag, $content);
            }
        }

        // Restore shortcodes
        $shortcodes = get_transient('ems_shortcodes_' . md5($content));
        if ($shortcodes) {
            foreach ($shortcodes as $index => $shortcode) {
                $placeholder = "{{SHORTCODE_$index}}";
                $content = str_replace($placeholder, $shortcode, $content);
            }
        }

        return $content;
    }

    /**
     * Create translated post
     */
    private function create_translated_post($original_post_id, $translation_data, $target_language) {
        $original_post = get_post($original_post_id);
        
        $translated_post_data = array(
            'post_title' => isset($translation_data['post_title']) ? $translation_data['post_title'] : $original_post->post_title,
            'post_content' => isset($translation_data['post_content']) ? $translation_data['post_content'] : $original_post->post_content,
            'post_excerpt' => isset($translation_data['post_excerpt']) ? $translation_data['post_excerpt'] : $original_post->post_excerpt,
            'post_status' => 'draft', // Set as draft for review
            'post_type' => $original_post->post_type,
            'post_author' => $original_post->post_author,
            'post_parent' => $original_post->post_parent,
            'menu_order' => $original_post->menu_order,
        );

        $translated_post_id = wp_insert_post($translated_post_data);

        if ($translated_post_id && !is_wp_error($translated_post_id)) {
            // Set language meta
            update_post_meta($translated_post_id, '_ems_language', $target_language);
            update_post_meta($translated_post_id, '_ems_original_post_id', $original_post_id);
            
            // Add to translation pairs
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix . 'ems_translation_pairs',
                array(
                    'post_id' => $original_post_id,
                    'language' => get_post_meta($original_post_id, '_ems_language', true) ?: 'vi',
                    'translated_post_id' => $translated_post_id,
                    'translated_language' => $target_language,
                    'created_at' => current_time('mysql'),
                )
            );

            // Set translated meta fields
            if (isset($translation_data['meta'])) {
                foreach ($translation_data['meta'] as $meta_key => $meta_value) {
                    update_post_meta($translated_post_id, $meta_key, $meta_value);
                }
            }

            // Copy taxonomies
            $taxonomies = get_object_taxonomies($original_post->post_type);
            foreach ($taxonomies as $taxonomy) {
                $terms = wp_get_object_terms($original_post_id, $taxonomy, array('fields' => 'slugs'));
                wp_set_object_terms($translated_post_id, $terms, $taxonomy);
            }

            return $translated_post_id;
        }

        return false;
    }

    /**
     * Get cache key
     */
    private function get_cache_key($text, $from_language, $to_language) {
        return 'ems_translation_' . md5($text . $from_language . $to_language . $this->current_provider);
    }

    /**
     * Track API usage
     */
    private function track_api_usage($provider, $characters) {
        $today = current_time('Y-m-d');
        $usage_key = 'ems_api_usage_' . $provider . '_' . $today;
        
        $current_usage = get_transient($usage_key);
        if ($current_usage === false) {
            $current_usage = array('calls' => 0, 'characters' => 0);
        }

        $current_usage['calls']++;
        $current_usage['characters'] += $characters;

        set_transient($usage_key, $current_usage, DAY_IN_SECONDS);

        // Update total API calls for today
        $total_calls = get_transient('ems_api_calls_today') ?: 0;
        set_transient('ems_api_calls_today', $total_calls + 1, DAY_IN_SECONDS);
    }

    /**
     * AJAX translate text
     */
    public function ajax_translate_text() {
        check_ajax_referer('ems_admin_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have sufficient permissions.', 'environmental-multilang-support'));
        }

        $text = sanitize_textarea_field($_POST['text']);
        $from_language = sanitize_key($_POST['from_language']);
        $to_language = sanitize_key($_POST['to_language']);

        $translation = $this->translate_text($text, $from_language, $to_language);

        if (is_wp_error($translation)) {
            wp_send_json_error($translation->get_error_message());
        }

        wp_send_json_success(array('translation' => $translation));
    }

    /**
     * AJAX translate post
     */
    public function ajax_translate_post() {
        check_ajax_referer('ems_admin_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have sufficient permissions.', 'environmental-multilang-support'));
        }

        $post_id = intval($_POST['post_id']);
        $from_language = sanitize_key($_POST['from_language']);
        $to_language = sanitize_key($_POST['to_language']);

        $translation = $this->translate_post($post_id, $from_language, $to_language);

        if (is_wp_error($translation)) {
            wp_send_json_error($translation->get_error_message());
        }

        $translated_post_id = $this->create_translated_post($post_id, $translation, $to_language);

        if ($translated_post_id) {
            wp_send_json_success(array(
                'translated_post_id' => $translated_post_id,
                'edit_link' => get_edit_post_link($translated_post_id),
            ));
        } else {
            wp_send_json_error(__('Failed to create translated post.', 'environmental-multilang-support'));
        }
    }

    /**
     * Get supported languages for different providers
     */
    private function get_google_supported_languages() {
        return array('vi', 'en', 'zh', 'ja', 'ko', 'th', 'ar', 'he', 'fr', 'es', 'de', 'it', 'pt', 'ru', 'hi', 'bn');
    }

    private function get_microsoft_supported_languages() {
        return array('vi', 'en', 'zh', 'ja', 'ko', 'th', 'ar', 'he', 'fr', 'es', 'de', 'it', 'pt', 'ru', 'hi');
    }

    private function get_deepl_supported_languages() {
        return array('en', 'zh', 'ja', 'fr', 'es', 'de', 'it', 'pt', 'ru', 'pl', 'nl');
    }

    private function get_libre_supported_languages() {
        return array('vi', 'en', 'zh', 'ja', 'ko', 'th', 'ar', 'he', 'fr', 'es', 'de', 'it', 'pt', 'ru');
    }

    /**
     * Check if language is supported by current provider
     */
    public function is_language_supported($language) {
        if (!isset($this->providers[$this->current_provider])) {
            return false;
        }

        return in_array($language, $this->providers[$this->current_provider]['supported_languages']);
    }

    /**
     * Get translation statistics
     */
    public function get_translation_stats($days = 30) {
        $stats = array();
        
        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $usage_key = 'ems_api_usage_' . $this->current_provider . '_' . $date;
            $usage = get_transient($usage_key);
            
            $stats[$date] = array(
                'calls' => $usage ? $usage['calls'] : 0,
                'characters' => $usage ? $usage['characters'] : 0,
            );
        }

        return array_reverse($stats, true);
    }

    /**
     * Clear translation cache
     */
    public function clear_translation_cache() {
        global $wpdb;
        
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_ems_translation_%' 
             OR option_name LIKE '_transient_timeout_ems_translation_%'"
        );

        return true;
    }
}

/**
 * Abstract base class for translation providers
 */
abstract class EMS_Translation_Provider {
    
    protected $options;
    protected $api_key;

    public function __construct($options) {
        $this->options = $options;
        $this->api_key = $this->get_api_key();
    }

    abstract protected function get_api_key();
    abstract public function translate($text, $from_language, $to_language, $options = array());
    abstract public function get_supported_languages();

    protected function make_request($url, $data = array(), $method = 'POST') {
        $args = array(
            'method' => $method,
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
        );

        if ($method === 'POST') {
            $args['body'] = json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code !== 200) {
            return new WP_Error('api_error', sprintf(__('API returned status code %d', 'environmental-multilang-support'), $status_code));
        }

        return json_decode($body, true);
    }
}
