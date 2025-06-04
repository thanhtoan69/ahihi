<?php
/**
 * Environmental Platform Content Manager
 * 
 * Manages content operations and relationships for the Environmental Platform
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EP_Content_Manager {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_ep_bulk_assign_categories', array($this, 'bulk_assign_categories'));
        add_action('wp_ajax_ep_duplicate_content', array($this, 'duplicate_content'));
        add_action('wp_ajax_ep_get_related_content', array($this, 'get_related_content'));
        add_filter('the_content', array($this, 'add_environmental_data_to_content'));
        add_action('pre_get_posts', array($this, 'modify_main_query'));
        add_action('wp_head', array($this, 'add_structured_data'));
    }
    
    /**
     * Initialize content manager
     */
    public function init() {
        $this->setup_content_relationships();
        $this->setup_content_templates();
        $this->setup_search_modifications();
    }
    
    /**
     * Setup content relationships
     */
    private function setup_content_relationships() {
        add_action('add_meta_boxes', array($this, 'add_content_relationship_meta_boxes'));
        add_action('save_post', array($this, 'save_content_relationships'));
    }
    
    /**
     * Add content relationship meta boxes
     */
    public function add_content_relationship_meta_boxes() {
        $post_types = array(
            'env_article', 'env_report', 'env_event', 'env_project', 
            'eco_product', 'community_post', 'edu_resource'
        );
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'content_relationships',
                __('Related Content', 'environmental-platform-core'),
                array($this, 'content_relationships_meta_box'),
                $post_type,
                'side',
                'default'
            );
        }
    }
    
    /**
     * Content relationships meta box
     */
    public function content_relationships_meta_box($post) {
        wp_nonce_field('content_relationships_meta_box', 'content_relationships_meta_box_nonce');
        
        $related_posts = get_post_meta($post->ID, '_related_posts', true);
        $related_posts = $related_posts ? explode(',', $related_posts) : array();
        
        $related_events = get_post_meta($post->ID, '_related_events', true);
        $related_events = $related_events ? explode(',', $related_events) : array();
        
        $related_projects = get_post_meta($post->ID, '_related_projects', true);
        $related_projects = $related_projects ? explode(',', $related_projects) : array();
        
        ?>
        <div class="content-relationships">
            <p><strong><?php _e('Related Articles', 'environmental-platform-core'); ?></strong></p>
            <select name="related_posts[]" multiple style="width: 100%; height: 100px;">
                <?php
                $articles = get_posts(array(
                    'post_type' => 'env_article',
                    'numberposts' => -1,
                    'post_status' => 'publish',
                    'exclude' => array($post->ID)
                ));
                foreach ($articles as $article) {
                    $selected = in_array($article->ID, $related_posts) ? 'selected' : '';
                    echo '<option value="' . $article->ID . '" ' . $selected . '>' . esc_html($article->post_title) . '</option>';
                }
                ?>
            </select>
            
            <p><strong><?php _e('Related Events', 'environmental-platform-core'); ?></strong></p>
            <select name="related_events[]" multiple style="width: 100%; height: 100px;">
                <?php
                $events = get_posts(array(
                    'post_type' => 'env_event',
                    'numberposts' => -1,
                    'post_status' => 'publish'
                ));
                foreach ($events as $event) {
                    $selected = in_array($event->ID, $related_events) ? 'selected' : '';
                    echo '<option value="' . $event->ID . '" ' . $selected . '>' . esc_html($event->post_title) . '</option>';
                }
                ?>
            </select>
            
            <p><strong><?php _e('Related Projects', 'environmental-platform-core'); ?></strong></p>
            <select name="related_projects[]" multiple style="width: 100%; height: 100px;">
                <?php
                $projects = get_posts(array(
                    'post_type' => 'env_project',
                    'numberposts' => -1,
                    'post_status' => 'publish'
                ));
                foreach ($projects as $project) {
                    $selected = in_array($project->ID, $related_projects) ? 'selected' : '';
                    echo '<option value="' . $project->ID . '" ' . $selected . '>' . esc_html($project->post_title) . '</option>';
                }
                ?>
            </select>
            
            <p><small><?php _e('Hold Ctrl/Cmd to select multiple items.', 'environmental-platform-core'); ?></small></p>
        </div>
        <?php
    }
    
    /**
     * Save content relationships
     */
    public function save_content_relationships($post_id) {
        if (!isset($_POST['content_relationships_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['content_relationships_meta_box_nonce'], 'content_relationships_meta_box')) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save related posts
        if (isset($_POST['related_posts'])) {
            $related_posts = array_map('intval', $_POST['related_posts']);
            update_post_meta($post_id, '_related_posts', implode(',', $related_posts));
        } else {
            delete_post_meta($post_id, '_related_posts');
        }
        
        // Save related events
        if (isset($_POST['related_events'])) {
            $related_events = array_map('intval', $_POST['related_events']);
            update_post_meta($post_id, '_related_events', implode(',', $related_events));
        } else {
            delete_post_meta($post_id, '_related_events');
        }
        
        // Save related projects
        if (isset($_POST['related_projects'])) {
            $related_projects = array_map('intval', $_POST['related_projects']);
            update_post_meta($post_id, '_related_projects', implode(',', $related_projects));
        } else {
            delete_post_meta($post_id, '_related_projects');
        }
    }
    
    /**
     * Setup content templates
     */
    private function setup_content_templates() {
        add_filter('single_template', array($this, 'load_custom_single_template'));
        add_filter('archive_template', array($this, 'load_custom_archive_template'));
    }
    
    /**
     * Load custom single templates
     */
    public function load_custom_single_template($template) {
        global $post;
        
        $custom_post_types = array(
            'env_article', 'env_report', 'env_alert', 'env_event', 
            'env_project', 'eco_product', 'community_post', 'edu_resource',
            'waste_class', 'env_petition', 'item_exchange'
        );
        
        if (in_array($post->post_type, $custom_post_types)) {
            $custom_template = EP_CORE_PLUGIN_DIR . 'templates/single-' . $post->post_type . '.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Load custom archive templates
     */
    public function load_custom_archive_template($template) {
        $custom_post_types = array(
            'env_article', 'env_report', 'env_alert', 'env_event', 
            'env_project', 'eco_product', 'community_post', 'edu_resource',
            'waste_class', 'env_petition', 'item_exchange'
        );
        
        foreach ($custom_post_types as $post_type) {
            if (is_post_type_archive($post_type)) {
                $custom_template = EP_CORE_PLUGIN_DIR . 'templates/archive-' . $post_type . '.php';
                if (file_exists($custom_template)) {
                    return $custom_template;
                }
            }
        }
        
        return $template;
    }
    
    /**
     * Setup search modifications
     */
    private function setup_search_modifications() {
        add_filter('posts_search', array($this, 'extend_search'), 500, 2);
        add_action('pre_get_posts', array($this, 'include_custom_post_types_in_search'));
    }
    
    /**
     * Extend search to include custom fields and taxonomy terms
     */
    public function extend_search($search, $query) {
        global $wpdb;
        
        if (!is_search() || !$query->is_main_query()) {
            return $search;
        }
        
        $search_term = $query->query_vars['s'];
        if (empty($search_term)) {
            return $search;
        }
        
        // Search in custom fields
        $meta_search = $wpdb->prepare(
            " OR ({$wpdb->posts}.ID IN (
                SELECT post_id FROM {$wpdb->postmeta} 
                WHERE meta_value LIKE %s
            ))",
            '%' . $wpdb->esc_like($search_term) . '%'
        );
        
        // Search in taxonomy terms
        $taxonomy_search = $wpdb->prepare(
            " OR ({$wpdb->posts}.ID IN (
                SELECT tr.object_id FROM {$wpdb->term_relationships} tr
                INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
                WHERE t.name LIKE %s OR t.slug LIKE %s
            ))",
            '%' . $wpdb->esc_like($search_term) . '%',
            '%' . $wpdb->esc_like($search_term) . '%'
        );
        
        $search = str_replace('))', ')' . $meta_search . $taxonomy_search . ')', $search);
        
        return $search;
    }
    
    /**
     * Include custom post types in search
     */
    public function include_custom_post_types_in_search($query) {
        if ($query->is_search() && $query->is_main_query() && !is_admin()) {
            $query->set('post_type', array(
                'post', 'page', 'env_article', 'env_report', 'env_event', 
                'env_project', 'eco_product', 'community_post', 'edu_resource'
            ));
        }
    }
    
    /**
     * Add environmental data to content
     */
    public function add_environmental_data_to_content($content) {
        global $post;
        
        if (!is_single() || !in_array($post->post_type, array(
            'env_article', 'env_report', 'env_event', 'env_project', 'eco_product'
        ))) {
            return $content;
        }
        
        $environmental_score = get_post_meta($post->ID, '_environmental_score', true);
        $carbon_impact = get_post_meta($post->ID, '_carbon_impact', true);
        $sustainability_rating = get_post_meta($post->ID, '_sustainability_rating', true);
        
        if ($environmental_score || $carbon_impact || $sustainability_rating) {
            $environmental_data = '<div class="environmental-data">';
            $environmental_data .= '<h4>' . __('Environmental Information', 'environmental-platform-core') . '</h4>';
            
            if ($environmental_score) {
                $environmental_data .= '<p><strong>' . __('Environmental Score:', 'environmental-platform-core') . '</strong> ' . $environmental_score . '/100</p>';
            }
            
            if ($carbon_impact) {
                $environmental_data .= '<p><strong>' . __('Carbon Impact:', 'environmental-platform-core') . '</strong> ' . $carbon_impact . ' kg CO2</p>';
            }
            
            if ($sustainability_rating) {
                $environmental_data .= '<p><strong>' . __('Sustainability Rating:', 'environmental-platform-core') . '</strong> ' . ucfirst($sustainability_rating) . '</p>';
            }
            
            $environmental_data .= '</div>';
            
            $content = $environmental_data . $content;
        }
        
        return $content;
    }
    
    /**
     * Modify main query for custom post types
     */
    public function modify_main_query($query) {
        if (!is_admin() && $query->is_main_query()) {
            // Include custom post types in home page
            if ($query->is_home()) {
                $query->set('post_type', array('post', 'env_article', 'community_post'));
            }
            
            // Order environmental events by event date
            if ($query->is_post_type_archive('env_event')) {
                $query->set('meta_key', '_event_date');
                $query->set('orderby', 'meta_value');
                $query->set('order', 'ASC');
                $query->set('meta_query', array(
                    array(
                        'key' => '_event_date',
                        'value' => date('Y-m-d'),
                        'compare' => '>='
                    )
                ));
            }
            
            // Order projects by progress
            if ($query->is_post_type_archive('env_project')) {
                $query->set('meta_key', '_project_progress');
                $query->set('orderby', 'meta_value_num');
                $query->set('order', 'DESC');
            }
        }
    }
    
    /**
     * Add structured data for SEO
     */
    public function add_structured_data() {
        global $post;
        
        if (!is_single() || !in_array($post->post_type, array(
            'env_article', 'env_event', 'env_project', 'eco_product'
        ))) {
            return;
        }
        
        $structured_data = array(
            '@context' => 'https://schema.org',
            '@type' => $this->get_schema_type($post->post_type),
            'name' => get_the_title(),
            'description' => get_the_excerpt(),
            'url' => get_permalink(),
            'datePublished' => get_the_date('c'),
            'dateModified' => get_the_modified_date('c'),
            'author' => array(
                '@type' => 'Person',
                'name' => get_the_author()
            )
        );
        
        // Add post-type specific data
        switch ($post->post_type) {
            case 'env_event':
                $event_date = get_post_meta($post->ID, '_event_date', true);
                $event_time = get_post_meta($post->ID, '_event_time', true);
                $event_location = get_post_meta($post->ID, '_event_location', true);
                
                if ($event_date) {
                    $structured_data['startDate'] = $event_date . 'T' . ($event_time ?: '00:00:00');
                }
                if ($event_location) {
                    $structured_data['location'] = array(
                        '@type' => 'Place',
                        'name' => $event_location
                    );
                }
                break;
                
            case 'eco_product':
                $product_price = get_post_meta($post->ID, '_product_price', true);
                if ($product_price) {
                    $structured_data['offers'] = array(
                        '@type' => 'Offer',
                        'price' => $product_price,
                        'priceCurrency' => 'VND'
                    );
                }
                break;
        }
        
        echo '<script type="application/ld+json">' . json_encode($structured_data) . '</script>';
    }
    
    /**
     * Get schema type for post type
     */
    private function get_schema_type($post_type) {
        $schema_types = array(
            'env_article' => 'Article',
            'env_event' => 'Event',
            'env_project' => 'Project',
            'eco_product' => 'Product',
            'env_report' => 'Report'
        );
        
        return isset($schema_types[$post_type]) ? $schema_types[$post_type] : 'Article';
    }
    
    /**
     * Get related content
     */
    public function get_related_content() {
        check_ajax_referer('ep_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $post_type = sanitize_text_field($_POST['post_type']);
        $limit = intval($_POST['limit']) ?: 5;
        
        $related_content = array();
        
        // Get content by same categories
        $categories = wp_get_post_terms($post_id, 'env_category', array('fields' => 'ids'));
        if (!empty($categories)) {
            $related_posts = get_posts(array(
                'post_type' => $post_type,
                'numberposts' => $limit,
                'post__not_in' => array($post_id),
                'tax_query' => array(
                    array(
                        'taxonomy' => 'env_category',
                        'field' => 'term_id',
                        'terms' => $categories
                    )
                )
            ));
            
            foreach ($related_posts as $related_post) {
                $related_content[] = array(
                    'id' => $related_post->ID,
                    'title' => $related_post->post_title,
                    'url' => get_permalink($related_post->ID),
                    'excerpt' => get_the_excerpt($related_post->ID),
                    'thumbnail' => get_the_post_thumbnail_url($related_post->ID, 'thumbnail')
                );
            }
        }
        
        wp_send_json_success($related_content);
    }
    
    /**
     * Bulk assign categories
     */
    public function bulk_assign_categories() {
        check_ajax_referer('ep_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $post_ids = array_map('intval', $_POST['post_ids']);
        $category_ids = array_map('intval', $_POST['category_ids']);
        $action = sanitize_text_field($_POST['action_type']); // 'add' or 'replace'
        
        $updated_count = 0;
        
        foreach ($post_ids as $post_id) {
            if ($action === 'replace') {
                wp_set_post_terms($post_id, $category_ids, 'env_category');
            } else {
                $current_terms = wp_get_post_terms($post_id, 'env_category', array('fields' => 'ids'));
                $new_terms = array_unique(array_merge($current_terms, $category_ids));
                wp_set_post_terms($post_id, $new_terms, 'env_category');
            }
            $updated_count++;
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d posts updated successfully.', 'environmental-platform-core'), $updated_count)
        ));
    }
    
    /**
     * Duplicate content
     */
    public function duplicate_content() {
        check_ajax_referer('ep_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $post_id = intval($_POST['post_id']);
        $original_post = get_post($post_id);
        
        if (!$original_post) {
            wp_send_json_error('Post not found');
        }
        
        // Create duplicate
        $new_post = array(
            'post_title' => $original_post->post_title . ' (Copy)',
            'post_content' => $original_post->post_content,
            'post_excerpt' => $original_post->post_excerpt,
            'post_type' => $original_post->post_type,
            'post_status' => 'draft',
            'post_author' => get_current_user_id()
        );
        
        $new_post_id = wp_insert_post($new_post);
        
        if ($new_post_id) {
            // Copy meta data
            $meta_data = get_post_meta($post_id);
            foreach ($meta_data as $key => $values) {
                foreach ($values as $value) {
                    update_post_meta($new_post_id, $key, maybe_unserialize($value));
                }
            }
            
            // Copy taxonomies
            $taxonomies = get_object_taxonomies($original_post->post_type);
            foreach ($taxonomies as $taxonomy) {
                $terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'ids'));
                wp_set_post_terms($new_post_id, $terms, $taxonomy);
            }
            
            wp_send_json_success(array(
                'message' => __('Content duplicated successfully.', 'environmental-platform-core'),
                'edit_url' => admin_url('post.php?action=edit&post=' . $new_post_id)
            ));
        } else {
            wp_send_json_error('Failed to duplicate content');
        }
    }
    
    /**
     * Get content statistics
     */
    public function get_content_statistics() {
        $stats = array();
        
        $post_types = array(
            'env_article', 'env_report', 'env_alert', 'env_event', 
            'env_project', 'eco_product', 'community_post', 'edu_resource',
            'waste_class', 'env_petition', 'item_exchange'
        );
        
        foreach ($post_types as $post_type) {
            $count = wp_count_posts($post_type);
            $stats[$post_type] = array(
                'published' => $count->publish,
                'draft' => $count->draft,
                'total' => $count->publish + $count->draft
            );
        }
        
        return $stats;
    }
    
    /**
     * Get trending content
     */
    public function get_trending_content($post_type = 'env_article', $limit = 10) {
        $args = array(
            'post_type' => $post_type,
            'posts_per_page' => $limit,
            'meta_key' => 'post_views_count',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'date_query' => array(
                array(
                    'after' => '1 month ago'
                )
            )
        );
        
        return get_posts($args);
    }
}

// Initialize the content manager
new EP_Content_Manager();
