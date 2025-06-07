<?php
/**
 * Content Duplicator Utility
 *
 * Handles content duplication for translation purposes
 *
 * @package Environmental_Multilang_Support
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EMS_Content_Duplicator {
    
    /**
     * Instance of this class
     *
     * @var EMS_Content_Duplicator
     */
    private static $instance = null;
    
    /**
     * Supported post types
     *
     * @var array
     */
    private $supported_post_types = ['post', 'page'];
    
    /**
     * Meta keys to copy
     *
     * @var array
     */
    private $meta_keys_to_copy = [
        '_thumbnail_id',
        '_wp_page_template',
        '_edit_last',
        '_edit_lock'
    ];
    
    /**
     * Meta keys to exclude
     *
     * @var array
     */
    private $meta_keys_to_exclude = [
        '_ems_language',
        '_ems_original_post_id',
        '_ems_translations'
    ];
    
    /**
     * Get instance
     *
     * @return EMS_Content_Duplicator
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
        $this->supported_post_types = get_option('ems_supported_post_types', ['post', 'page']);
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_ems_duplicate_content', [$this, 'ajax_duplicate_content']);
        add_action('admin_post_ems_duplicate_content', [$this, 'handle_duplicate_content']);
        add_filter('post_row_actions', [$this, 'add_duplicate_action'], 10, 2);
        add_filter('page_row_actions', [$this, 'add_duplicate_action'], 10, 2);
    }
    
    /**
     * AJAX handler for content duplication
     */
    public function ajax_duplicate_content() {
        check_ajax_referer('ems_duplicate_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'environmental-multilang-support')]);
        }
        
        $post_id = intval($_POST['post_id']);
        $target_language = sanitize_text_field($_POST['target_language']);
        $copy_content = isset($_POST['copy_content']) && $_POST['copy_content'] === '1';
        
        if (!$post_id || !$target_language) {
            wp_send_json_error(['message' => __('Invalid parameters', 'environmental-multilang-support')]);
        }
        
        $result = $this->duplicate_post($post_id, $target_language, $copy_content);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success([
            'message' => __('Content duplicated successfully', 'environmental-multilang-support'),
            'new_post_id' => $result,
            'edit_url' => get_edit_post_link($result, 'raw')
        ]);
    }
    
    /**
     * Handle duplicate content form submission
     */
    public function handle_duplicate_content() {
        if (!wp_verify_nonce($_POST['ems_duplicate_nonce'], 'ems_duplicate_content')) {
            wp_die(__('Security check failed', 'environmental-multilang-support'));
        }
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions', 'environmental-multilang-support'));
        }
        
        $post_id = intval($_POST['post_id']);
        $target_language = sanitize_text_field($_POST['target_language']);
        $copy_content = isset($_POST['copy_content']);
        
        $result = $this->duplicate_post($post_id, $target_language, $copy_content);
        
        if (is_wp_error($result)) {
            wp_redirect(add_query_arg([
                'post_type' => get_post_type($post_id),
                'ems_error' => urlencode($result->get_error_message())
            ], admin_url('edit.php')));
        } else {
            wp_redirect(get_edit_post_link($result, 'raw'));
        }
        
        exit;
    }
    
    /**
     * Add duplicate action to post/page rows
     *
     * @param array $actions
     * @param WP_Post $post
     * @return array
     */
    public function add_duplicate_action($actions, $post) {
        if (!in_array($post->post_type, $this->supported_post_types)) {
            return $actions;
        }
        
        if (!current_user_can('edit_post', $post->ID)) {
            return $actions;
        }
        
        $duplicate_url = add_query_arg([
            'action' => 'ems_duplicate_form',
            'post' => $post->ID,
            'ems_duplicate_nonce' => wp_create_nonce('ems_duplicate_content')
        ], admin_url('admin.php'));
        
        $actions['ems_duplicate'] = sprintf(
            '<a href="%s" title="%s">%s</a>',
            $duplicate_url,
            __('Create translation copy', 'environmental-multilang-support'),
            __('Duplicate for Translation', 'environmental-multilang-support')
        );
        
        return $actions;
    }
    
    /**
     * Duplicate post for translation
     *
     * @param int $post_id
     * @param string $target_language
     * @param bool $copy_content
     * @return int|WP_Error
     */
    public function duplicate_post($post_id, $target_language, $copy_content = false) {
        $original_post = get_post($post_id);
        
        if (!$original_post) {
            return new WP_Error('invalid_post', __('Post not found', 'environmental-multilang-support'));
        }
        
        if (!in_array($original_post->post_type, $this->supported_post_types)) {
            return new WP_Error('unsupported_type', __('Post type not supported', 'environmental-multilang-support'));
        }
        
        // Check if translation already exists
        $existing_translation = $this->get_translation($post_id, $target_language);
        if ($existing_translation) {
            return new WP_Error('translation_exists', __('Translation already exists', 'environmental-multilang-support'));
        }
        
        // Create new post data
        $new_post_data = [
            'post_title' => $copy_content ? $original_post->post_title : $original_post->post_title . ' (' . strtoupper($target_language) . ')',
            'post_content' => $copy_content ? $original_post->post_content : '',
            'post_excerpt' => $copy_content ? $original_post->post_excerpt : '',
            'post_status' => 'draft',
            'post_type' => $original_post->post_type,
            'post_author' => get_current_user_id(),
            'post_parent' => $original_post->post_parent,
            'menu_order' => $original_post->menu_order,
            'comment_status' => $original_post->comment_status,
            'ping_status' => $original_post->ping_status
        ];
        
        // Insert new post
        $new_post_id = wp_insert_post($new_post_data);
        
        if (is_wp_error($new_post_id)) {
            return $new_post_id;
        }
        
        // Copy post meta
        $this->copy_post_meta($post_id, $new_post_id, $copy_content);
        
        // Set language meta
        update_post_meta($new_post_id, '_ems_language', $target_language);
        update_post_meta($new_post_id, '_ems_original_post_id', $post_id);
        
        // Copy taxonomies
        $this->copy_post_taxonomies($post_id, $new_post_id);
        
        // Create translation link
        $this->create_translation_link($post_id, $new_post_id, $target_language);
        
        // Copy featured image if exists
        if (has_post_thumbnail($post_id)) {
            $thumbnail_id = get_post_thumbnail_id($post_id);
            set_post_thumbnail($new_post_id, $thumbnail_id);
        }
        
        do_action('ems_post_duplicated', $new_post_id, $post_id, $target_language);
        
        return $new_post_id;
    }
    
    /**
     * Copy post meta
     *
     * @param int $source_post_id
     * @param int $target_post_id
     * @param bool $copy_content_meta
     */
    private function copy_post_meta($source_post_id, $target_post_id, $copy_content_meta = false) {
        $meta_data = get_post_meta($source_post_id);
        
        foreach ($meta_data as $meta_key => $meta_values) {
            // Skip excluded meta keys
            if (in_array($meta_key, $this->meta_keys_to_exclude)) {
                continue;
            }
            
            // Skip content-related meta if not copying content
            if (!$copy_content_meta && $this->is_content_meta($meta_key)) {
                continue;
            }
            
            // Copy meta values
            foreach ($meta_values as $meta_value) {
                $meta_value = maybe_unserialize($meta_value);
                update_post_meta($target_post_id, $meta_key, $meta_value);
            }
        }
    }
    
    /**
     * Copy post taxonomies
     *
     * @param int $source_post_id
     * @param int $target_post_id
     */
    private function copy_post_taxonomies($source_post_id, $target_post_id) {
        $taxonomies = get_object_taxonomies(get_post_type($source_post_id));
        
        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_object_terms($source_post_id, $taxonomy, ['fields' => 'slugs']);
            
            if (!is_wp_error($terms) && !empty($terms)) {
                wp_set_object_terms($target_post_id, $terms, $taxonomy);
            }
        }
    }
    
    /**
     * Create translation link
     *
     * @param int $original_post_id
     * @param int $translated_post_id
     * @param string $language
     */
    private function create_translation_link($original_post_id, $translated_post_id, $language) {
        global $wpdb;
        
        // Get original language
        $original_language = get_post_meta($original_post_id, '_ems_language', true) ?: get_option('ems_default_language', 'en');
        
        // Insert translation pair
        $wpdb->insert(
            $wpdb->prefix . 'ems_translation_pairs',
            [
                'original_post_id' => $original_post_id,
                'translated_post_id' => $translated_post_id,
                'original_language' => $original_language,
                'language_code' => $language,
                'created_at' => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );
    }
    
    /**
     * Get existing translation
     *
     * @param int $post_id
     * @param string $language
     * @return int|false
     */
    private function get_translation($post_id, $language) {
        global $wpdb;
        
        $translation_id = $wpdb->get_var($wpdb->prepare(
            "SELECT translated_post_id FROM {$wpdb->prefix}ems_translation_pairs 
             WHERE original_post_id = %d AND language_code = %s",
            $post_id,
            $language
        ));
        
        return $translation_id ? intval($translation_id) : false;
    }
    
    /**
     * Check if meta key is content-related
     *
     * @param string $meta_key
     * @return bool
     */
    private function is_content_meta($meta_key) {
        $content_meta_keys = [
            '_yoast_wpseo_title',
            '_yoast_wpseo_metadesc',
            '_yoast_wpseo_opengraph-title',
            '_yoast_wpseo_opengraph-description',
            '_yoast_wpseo_twitter-title',
            '_yoast_wpseo_twitter-description',
            'rank_math_title',
            'rank_math_description'
        ];
        
        return in_array($meta_key, $content_meta_keys) || strpos($meta_key, '_seo_') !== false;
    }
    
    /**
     * Bulk duplicate posts
     *
     * @param array $post_ids
     * @param string $target_language
     * @param bool $copy_content
     * @return array
     */
    public function bulk_duplicate_posts($post_ids, $target_language, $copy_content = false) {
        $results = [
            'success' => [],
            'errors' => []
        ];
        
        foreach ($post_ids as $post_id) {
            $result = $this->duplicate_post($post_id, $target_language, $copy_content);
            
            if (is_wp_error($result)) {
                $results['errors'][$post_id] = $result->get_error_message();
            } else {
                $results['success'][$post_id] = $result;
            }
        }
        
        return $results;
    }
    
    /**
     * Auto-duplicate on post save (if enabled)
     *
     * @param int $post_id
     * @param WP_Post $post
     * @param bool $update
     */
    public function maybe_auto_duplicate($post_id, $post, $update) {
        if (!get_option('ems_auto_duplicate', false)) {
            return;
        }
        
        if ($update || !in_array($post->post_type, $this->supported_post_types)) {
            return;
        }
        
        $auto_languages = get_option('ems_auto_duplicate_languages', []);
        
        foreach ($auto_languages as $language) {
            $this->duplicate_post($post_id, $language, false);
        }
    }
    
    /**
     * Sync post updates across translations
     *
     * @param int $post_id
     * @param WP_Post $post_after
     * @param WP_Post $post_before
     */
    public function sync_translation_updates($post_id, $post_after, $post_before) {
        if (!get_option('ems_sync_translations', false)) {
            return;
        }
        
        // Get all translations
        $translations = $this->get_all_translations($post_id);
        
        foreach ($translations as $translation_id) {
            if ($translation_id === $post_id) {
                continue;
            }
            
            // Sync specific fields
            $this->sync_translation_fields($post_id, $translation_id, [
                'post_status',
                'menu_order',
                'comment_status',
                'ping_status'
            ]);
            
            // Sync meta fields that should be synchronized
            $this->sync_translation_meta($post_id, $translation_id);
        }
    }
    
    /**
     * Get all translations for a post
     *
     * @param int $post_id
     * @return array
     */
    private function get_all_translations($post_id) {
        global $wpdb;
        
        $translations = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT 
                CASE 
                    WHEN original_post_id = %d THEN translated_post_id 
                    ELSE original_post_id 
                END as post_id
             FROM {$wpdb->prefix}ems_translation_pairs 
             WHERE original_post_id = %d OR translated_post_id = %d",
            $post_id,
            $post_id,
            $post_id
        ));
        
        $translations[] = $post_id; // Include original post
        
        return array_unique(array_map('intval', $translations));
    }
    
    /**
     * Sync translation fields
     *
     * @param int $source_post_id
     * @param int $target_post_id
     * @param array $fields
     */
    private function sync_translation_fields($source_post_id, $target_post_id, $fields) {
        $source_post = get_post($source_post_id);
        if (!$source_post) {
            return;
        }
        
        $update_data = ['ID' => $target_post_id];
        
        foreach ($fields as $field) {
            if (isset($source_post->$field)) {
                $update_data[$field] = $source_post->$field;
            }
        }
        
        wp_update_post($update_data);
    }
    
    /**
     * Sync translation meta
     *
     * @param int $source_post_id
     * @param int $target_post_id
     */
    private function sync_translation_meta($source_post_id, $target_post_id) {
        $sync_meta_keys = get_option('ems_sync_meta_keys', [
            '_thumbnail_id',
            '_wp_page_template'
        ]);
        
        foreach ($sync_meta_keys as $meta_key) {
            $meta_value = get_post_meta($source_post_id, $meta_key, true);
            update_post_meta($target_post_id, $meta_key, $meta_value);
        }
    }
    
    /**
     * Delete translation links when post is deleted
     *
     * @param int $post_id
     */
    public function cleanup_translation_links($post_id) {
        global $wpdb;
        
        $wpdb->delete(
            $wpdb->prefix . 'ems_translation_pairs',
            [
                'original_post_id' => $post_id
            ],
            ['%d']
        );
        
        $wpdb->delete(
            $wpdb->prefix . 'ems_translation_pairs',
            [
                'translated_post_id' => $post_id
            ],
            ['%d']
        );
    }
    
    /**
     * Get duplication statistics
     *
     * @return array
     */
    public function get_duplication_stats() {
        global $wpdb;
        
        $stats = [];
        
        // Total translations
        $stats['total_translations'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ems_translation_pairs"
        );
        
        // Translations by language
        $language_counts = $wpdb->get_results(
            "SELECT language_code, COUNT(*) as count 
             FROM {$wpdb->prefix}ems_translation_pairs 
             GROUP BY language_code"
        );
        
        $stats['by_language'] = [];
        foreach ($language_counts as $row) {
            $stats['by_language'][$row->language_code] = $row->count;
        }
        
        // Recent duplications
        $stats['recent_duplications'] = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}ems_translation_pairs 
             ORDER BY created_at DESC 
             LIMIT 10"
        );
        
        return $stats;
    }
}
