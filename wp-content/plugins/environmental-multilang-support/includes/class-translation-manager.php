<?php
/**
 * Translation Manager Component
 * 
 * Handles content translation, translation workflows, and content management
 */

if (!defined('ABSPATH')) {
    exit;
}

class EMS_Translation_Manager {

    /**
     * Translation meta key
     */
    const META_KEY = '_ems_translations';

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Post/Page translation hooks
        add_action('add_meta_boxes', array($this, 'add_translation_meta_boxes'));
        add_action('save_post', array($this, 'save_translation_data'));
        
        // Admin columns
        add_filter('manage_posts_columns', array($this, 'add_translation_columns'));
        add_filter('manage_pages_columns', array($this, 'add_translation_columns'));
        add_action('manage_posts_custom_column', array($this, 'display_translation_columns'), 10, 2);
        add_action('manage_pages_custom_column', array($this, 'display_translation_columns'), 10, 2);
        
        // AJAX handlers
        add_action('wp_ajax_ems_create_translation', array($this, 'ajax_create_translation'));
        add_action('wp_ajax_ems_link_translation', array($this, 'ajax_link_translation'));
        add_action('wp_ajax_ems_unlink_translation', array($this, 'ajax_unlink_translation'));
        
        // Content processing
        add_filter('the_content', array($this, 'process_content'), 10);
        add_filter('the_title', array($this, 'process_title'), 10);
        add_filter('the_excerpt', array($this, 'process_excerpt'), 10);
        
        // Shortcodes
        add_shortcode('ems_translate', array($this, 'translate_shortcode'));
        add_shortcode('ems_lang', array($this, 'language_specific_shortcode'));
        
        // Menu items
        add_filter('wp_nav_menu_objects', array($this, 'filter_menu_items'), 10, 2);
    }

    /**
     * Add translation meta boxes
     */
    public function add_translation_meta_boxes() {
        $post_types = $this->get_translatable_post_types();
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'ems_translations',
                __('Translations', 'environmental-multilang-support'),
                array($this, 'translation_meta_box_callback'),
                $post_type,
                'side',
                'high'
            );
        }
    }

    /**
     * Translation meta box callback
     */
    public function translation_meta_box_callback($post) {
        wp_nonce_field('ems_save_translations', 'ems_translations_nonce');
        
        $languages = $this->get_available_languages();
        $current_lang = $this->get_post_language($post->ID);
        $translations = $this->get_post_translations($post->ID);
        
        echo '<div class="ems-translations-meta-box">';
        
        // Current language
        echo '<div class="ems-current-lang">';
        echo '<strong>' . __('Current Language:', 'environmental-multilang-support') . '</strong> ';
        echo '<select name="ems_post_language" id="ems-post-language">';
        
        foreach ($languages as $lang_code => $lang_data) {
            $selected = selected($current_lang, $lang_code, false);
            echo '<option value="' . esc_attr($lang_code) . '"' . $selected . '>' . esc_html($lang_data['native_name']) . '</option>';
        }
        
        echo '</select>';
        echo '</div>';
        
        // Translations list
        echo '<div class="ems-translations-list">';
        echo '<h4>' . __('Translations:', 'environmental-multilang-support') . '</h4>';
        
        if (empty($translations)) {
            echo '<p><em>' . __('No translations found.', 'environmental-multilang-support') . '</em></p>';
        } else {
            echo '<ul class="ems-translation-links">';
            foreach ($translations as $lang_code => $translation_id) {
                if ($lang_code !== $current_lang && $translation_id) {
                    $translation_post = get_post($translation_id);
                    if ($translation_post) {
                        $edit_url = get_edit_post_link($translation_id);
                        $lang_name = isset($languages[$lang_code]) ? $languages[$lang_code]['native_name'] : $lang_code;
                        
                        echo '<li>';
                        echo '<span class="ems-lang-flag">' . $this->get_flag_html($languages[$lang_code]['flag']) . '</span>';
                        echo '<a href="' . esc_url($edit_url) . '">' . esc_html($lang_name) . '</a>';
                        echo ' <small>(' . esc_html($translation_post->post_status) . ')</small>';
                        echo ' <button type="button" class="ems-unlink-translation button-link" data-lang="' . esc_attr($lang_code) . '" data-post="' . esc_attr($translation_id) . '">' . __('Unlink', 'environmental-multilang-support') . '</button>';
                        echo '</li>';
                    }
                }
            }
            echo '</ul>';
        }
        
        // Add translation buttons
        echo '<div class="ems-add-translations">';
        echo '<h4>' . __('Add Translations:', 'environmental-multilang-support') . '</h4>';
        
        foreach ($languages as $lang_code => $lang_data) {
            if ($lang_code !== $current_lang && !isset($translations[$lang_code])) {
                echo '<button type="button" class="ems-create-translation button button-secondary" data-lang="' . esc_attr($lang_code) . '" data-source="' . esc_attr($post->ID) . '">';
                echo $this->get_flag_html($lang_data['flag']) . ' ' . esc_html($lang_data['native_name']);
                echo '</button> ';
            }
        }
        
        echo '</div>';
        
        // Link existing post
        echo '<div class="ems-link-existing">';
        echo '<h4>' . __('Link Existing Post:', 'environmental-multilang-support') . '</h4>';
        echo '<select id="ems-link-existing-select">';
        echo '<option value="">' . __('Select a post...', 'environmental-multilang-support') . '</option>';
        
        $posts = get_posts(array(
            'post_type' => $post->post_type,
            'post_status' => array('publish', 'draft', 'private'),
            'numberposts' => -1,
            'exclude' => array($post->ID),
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => self::META_KEY,
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => self::META_KEY,
                    'value' => '',
                    'compare' => '='
                )
            )
        ));
        
        foreach ($posts as $existing_post) {
            echo '<option value="' . esc_attr($existing_post->ID) . '">' . esc_html($existing_post->post_title) . '</option>';
        }
        
        echo '</select>';
        echo '<select id="ems-link-existing-lang">';
        foreach ($languages as $lang_code => $lang_data) {
            if ($lang_code !== $current_lang) {
                echo '<option value="' . esc_attr($lang_code) . '">' . esc_html($lang_data['native_name']) . '</option>';
            }
        }
        echo '</select>';
        echo '<button type="button" class="ems-link-existing-btn button">' . __('Link', 'environmental-multilang-support') . '</button>';
        echo '</div>';
        
        echo '</div>';
        
        // Add inline styles and scripts
        echo '<style>
            .ems-translations-meta-box { margin: 10px 0; }
            .ems-current-lang { margin-bottom: 15px; }
            .ems-translation-links li { margin: 5px 0; display: flex; align-items: center; }
            .ems-lang-flag { margin-right: 5px; }
            .ems-add-translations button { margin: 2px; }
            .ems-link-existing { margin-top: 15px; }
            .ems-link-existing select { margin: 2px; }
        </style>';
        
        echo '<script>
            jQuery(document).ready(function($) {
                $(".ems-create-translation").on("click", function() {
                    var lang = $(this).data("lang");
                    var source = $(this).data("source");
                    emsCreateTranslation(source, lang);
                });
                
                $(".ems-unlink-translation").on("click", function() {
                    var lang = $(this).data("lang");
                    var post = $(this).data("post");
                    emsUnlinkTranslation(post, lang);
                });
                
                $(".ems-link-existing-btn").on("click", function() {
                    var postId = $("#ems-link-existing-select").val();
                    var lang = $("#ems-link-existing-lang").val();
                    if (postId && lang) {
                        emsLinkTranslation(' . $post->ID . ', postId, lang);
                    }
                });
            });
        </script>';
    }

    /**
     * Save translation data
     */
    public function save_translation_data($post_id) {
        // Security checks
        if (!isset($_POST['ems_translations_nonce']) || 
            !wp_verify_nonce($_POST['ems_translations_nonce'], 'ems_save_translations')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save post language
        if (isset($_POST['ems_post_language'])) {
            $lang = sanitize_text_field($_POST['ems_post_language']);
            update_post_meta($post_id, '_ems_post_language', $lang);
        }
    }

    /**
     * Add translation columns to post list
     */
    public function add_translation_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['ems_language'] = __('Language', 'environmental-multilang-support');
                $new_columns['ems_translations'] = __('Translations', 'environmental-multilang-support');
            }
        }
        
        return $new_columns;
    }

    /**
     * Display translation columns content
     */
    public function display_translation_columns($column, $post_id) {
        switch ($column) {
            case 'ems_language':
                $lang = $this->get_post_language($post_id);
                $languages = $this->get_available_languages();
                
                if (isset($languages[$lang])) {
                    echo $this->get_flag_html($languages[$lang]['flag']) . ' ';
                    echo esc_html($languages[$lang]['native_name']);
                } else {
                    echo '<em>' . __('Not set', 'environmental-multilang-support') . '</em>';
                }
                break;
                
            case 'ems_translations':
                $translations = $this->get_post_translations($post_id);
                $languages = $this->get_available_languages();
                $current_lang = $this->get_post_language($post_id);
                
                $translation_flags = array();
                foreach ($translations as $lang_code => $translation_id) {
                    if ($lang_code !== $current_lang && $translation_id) {
                        $translation_post = get_post($translation_id);
                        if ($translation_post) {
                            $edit_url = get_edit_post_link($translation_id);
                            $flag_html = $this->get_flag_html($languages[$lang_code]['flag']);
                            $translation_flags[] = '<a href="' . esc_url($edit_url) . '" title="' . esc_attr($languages[$lang_code]['native_name']) . '">' . $flag_html . '</a>';
                        }
                    }
                }
                
                if (empty($translation_flags)) {
                    echo '<em>' . __('None', 'environmental-multilang-support') . '</em>';
                } else {
                    echo implode(' ', $translation_flags);
                }
                break;
        }
    }

    /**
     * AJAX: Create translation
     */
    public function ajax_create_translation() {
        check_ajax_referer('ems_nonce', 'nonce');
        
        $source_id = intval($_POST['source_id']);
        $target_lang = sanitize_text_field($_POST['target_lang']);
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'environmental-multilang-support'));
        }
        
        $source_post = get_post($source_id);
        if (!$source_post) {
            wp_die(__('Source post not found', 'environmental-multilang-support'));
        }
        
        // Create translation post
        $translation_data = array(
            'post_title' => $source_post->post_title . ' (' . $target_lang . ')',
            'post_content' => $source_post->post_content,
            'post_excerpt' => $source_post->post_excerpt,
            'post_status' => 'draft',
            'post_type' => $source_post->post_type,
            'post_author' => get_current_user_id()
        );
        
        $translation_id = wp_insert_post($translation_data);
        
        if (is_wp_error($translation_id)) {
            wp_die(__('Failed to create translation', 'environmental-multilang-support'));
        }
        
        // Set language
        update_post_meta($translation_id, '_ems_post_language', $target_lang);
        
        // Link translations
        $this->link_translations($source_id, $translation_id, $target_lang);
        
        wp_send_json_success(array(
            'translation_id' => $translation_id,
            'edit_url' => get_edit_post_link($translation_id),
            'message' => __('Translation created successfully', 'environmental-multilang-support')
        ));
    }

    /**
     * AJAX: Link existing translation
     */
    public function ajax_link_translation() {
        check_ajax_referer('ems_nonce', 'nonce');
        
        $source_id = intval($_POST['source_id']);
        $target_id = intval($_POST['target_id']);
        $target_lang = sanitize_text_field($_POST['target_lang']);
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'environmental-multilang-support'));
        }
        
        // Set target language
        update_post_meta($target_id, '_ems_post_language', $target_lang);
        
        // Link translations
        $this->link_translations($source_id, $target_id, $target_lang);
        
        wp_send_json_success(array(
            'message' => __('Posts linked successfully', 'environmental-multilang-support')
        ));
    }

    /**
     * AJAX: Unlink translation
     */
    public function ajax_unlink_translation() {
        check_ajax_referer('ems_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $lang_code = sanitize_text_field($_POST['lang_code']);
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'environmental-multilang-support'));
        }
        
        $this->unlink_translation($post_id, $lang_code);
        
        wp_send_json_success(array(
            'message' => __('Translation unlinked successfully', 'environmental-multilang-support')
        ));
    }

    /**
     * Link translations between posts
     */
    private function link_translations($source_id, $target_id, $target_lang) {
        $source_lang = $this->get_post_language($source_id);
        $source_translations = $this->get_post_translations($source_id);
        $target_translations = $this->get_post_translations($target_id);
        
        // Add target to source translations
        $source_translations[$target_lang] = $target_id;
        $source_translations[$source_lang] = $source_id;
        
        // Add source to target translations
        $target_translations[$source_lang] = $source_id;
        $target_translations[$target_lang] = $target_id;
        
        // Sync all translations
        foreach ($source_translations as $lang => $post_id) {
            if ($post_id && $lang !== $target_lang) {
                $translations = $this->get_post_translations($post_id);
                $translations[$target_lang] = $target_id;
                update_post_meta($post_id, self::META_KEY, $translations);
            }
        }
        
        // Update both posts
        update_post_meta($source_id, self::META_KEY, $source_translations);
        update_post_meta($target_id, self::META_KEY, $target_translations);
    }

    /**
     * Unlink translation
     */
    private function unlink_translation($post_id, $lang_code) {
        $translations = $this->get_post_translations($post_id);
        
        if (isset($translations[$lang_code])) {
            $target_id = $translations[$lang_code];
            
            // Remove from current post
            unset($translations[$lang_code]);
            update_post_meta($post_id, self::META_KEY, $translations);
            
            // Remove from target post
            $target_translations = $this->get_post_translations($target_id);
            $current_lang = $this->get_post_language($post_id);
            unset($target_translations[$current_lang]);
            update_post_meta($target_id, self::META_KEY, $target_translations);
            
            // Update all other linked posts
            foreach ($translations as $lang => $linked_id) {
                if ($linked_id) {
                    $linked_translations = $this->get_post_translations($linked_id);
                    unset($linked_translations[$lang_code]);
                    update_post_meta($linked_id, self::META_KEY, $linked_translations);
                }
            }
        }
    }

    /**
     * Process content for translations
     */
    public function process_content($content) {
        // Process language-specific shortcodes
        $content = $this->process_language_shortcodes($content);
        
        // Process inline translations
        $content = $this->process_inline_translations($content);
        
        return $content;
    }

    /**
     * Process title for translations
     */
    public function process_title($title) {
        return $this->process_inline_translations($title);
    }

    /**
     * Process excerpt for translations
     */
    public function process_excerpt($excerpt) {
        return $this->process_inline_translations($excerpt);
    }

    /**
     * Process language shortcodes
     */
    private function process_language_shortcodes($content) {
        $current_lang = $this->get_current_language();
        
        // Remove content for other languages
        $pattern = '/\[ems_lang\s+lang="([^"]+)"\](.*?)\[\/ems_lang\]/s';
        $content = preg_replace_callback($pattern, function($matches) use ($current_lang) {
            $lang = $matches[1];
            $text = $matches[2];
            
            return ($lang === $current_lang) ? $text : '';
        }, $content);
        
        return $content;
    }

    /**
     * Process inline translations
     */
    private function process_inline_translations($content) {
        $current_lang = $this->get_current_language();
        
        // Process {en:English text|vi:Vietnamese text} format
        $pattern = '/\{([^}]+)\}/';
        $content = preg_replace_callback($pattern, function($matches) use ($current_lang) {
            $translations = $matches[1];
            $parts = explode('|', $translations);
            
            foreach ($parts as $part) {
                if (strpos($part, ':') !== false) {
                    list($lang, $text) = explode(':', $part, 2);
                    if (trim($lang) === $current_lang) {
                        return trim($text);
                    }
                }
            }
            
            // Return first translation if current language not found
            if (!empty($parts)) {
                $first = explode(':', $parts[0], 2);
                return isset($first[1]) ? trim($first[1]) : $parts[0];
            }
            
            return $matches[0];
        }, $content);
        
        return $content;
    }

    /**
     * Translation shortcode
     */
    public function translate_shortcode($atts, $content = '') {
        $atts = shortcode_atts(array(
            'from' => '',
            'to' => '',
            'text' => $content
        ), $atts);
        
        $current_lang = $this->get_current_language();
        
        // If 'to' matches current language, return the content
        if ($atts['to'] === $current_lang) {
            return $content;
        }
        
        // If 'from' matches current language and no 'to' specified, return content
        if ($atts['from'] === $current_lang && empty($atts['to'])) {
            return $content;
        }
        
        return '';
    }

    /**
     * Language-specific shortcode
     */
    public function language_specific_shortcode($atts, $content = '') {
        $atts = shortcode_atts(array(
            'lang' => ''
        ), $atts);
        
        $current_lang = $this->get_current_language();
        
        // Return content only if language matches
        if ($atts['lang'] === $current_lang) {
            return do_shortcode($content);
        }
        
        return '';
    }

    /**
     * Filter menu items by language
     */
    public function filter_menu_items($items, $args) {
        $current_lang = $this->get_current_language();
        $filtered_items = array();
        
        foreach ($items as $item) {
            $item_lang = get_post_meta($item->object_id, '_ems_post_language', true);
            
            // Include item if no language set or matches current language
            if (empty($item_lang) || $item_lang === $current_lang) {
                $filtered_items[] = $item;
            }
        }
        
        return $filtered_items;
    }

    /**
     * Get post language
     */
    public function get_post_language($post_id) {
        $lang = get_post_meta($post_id, '_ems_post_language', true);
        return $lang ?: $this->get_default_language();
    }

    /**
     * Get post translations
     */
    public function get_post_translations($post_id) {
        $translations = get_post_meta($post_id, self::META_KEY, true);
        return is_array($translations) ? $translations : array();
    }

    /**
     * Get translatable post types
     */
    private function get_translatable_post_types() {
        $post_types = get_post_types(array('public' => true), 'names');
        return apply_filters('ems_translatable_post_types', $post_types);
    }

    /**
     * Helper methods
     */
    private function get_available_languages() {
        $ems = Environmental_Multilang_Support::get_instance();
        return $ems->get_available_languages();
    }

    private function get_current_language() {
        $ems = Environmental_Multilang_Support::get_instance();
        return $ems->get_current_language();
    }

    private function get_default_language() {
        $ems = Environmental_Multilang_Support::get_instance();
        return $ems->get_default_language();
    }

    private function get_flag_html($flag_code) {
        $flag_url = EMS_PLUGIN_URL . 'assets/images/flags/' . $flag_code . '.png';
        
        if (!file_exists(EMS_PLUGIN_DIR . 'assets/images/flags/' . $flag_code . '.png')) {
            $flag_url = EMS_PLUGIN_URL . 'assets/images/flags/default.png';
        }
        
        return '<img src="' . esc_url($flag_url) . '" alt="" class="ems-flag" width="16" height="12">';
    }
}
