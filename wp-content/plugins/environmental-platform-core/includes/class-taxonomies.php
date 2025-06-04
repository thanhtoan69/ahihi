<?php
/**
 * Environmental Platform Custom Taxonomies
 * 
 * Manages all custom taxonomies for the Environmental Platform
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EP_Taxonomies {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_taxonomies'));
        add_action('admin_init', array($this, 'add_taxonomy_meta_fields'));
        add_action('edited_term', array($this, 'save_taxonomy_meta'), 10, 3);
        add_action('create_term', array($this, 'save_taxonomy_meta'), 10, 3);
        add_filter('manage_edit-env_category_columns', array($this, 'add_taxonomy_columns'));
        add_filter('manage_env_category_custom_column', array($this, 'add_taxonomy_column_content'), 10, 3);
    }
    
    /**
     * Register all custom taxonomies
     */
    public function register_taxonomies() {
        $this->register_environmental_categories();
        $this->register_environmental_tags();
        $this->register_impact_levels();
        $this->register_regions();
        $this->register_sustainability_levels();
        $this->register_project_status();
        $this->register_product_types();
        $this->register_event_types();
        $this->register_report_types();
        $this->register_alert_types();
        $this->register_priority_levels();
        $this->register_waste_types();
        $this->register_recyclability();
        $this->register_disposal_methods();
        $this->register_resource_types();
        $this->register_education_levels();
        $this->register_community_types();
        $this->register_petition_types();
        $this->register_exchange_types();
        $this->register_item_conditions();
    }
    
    /**
     * Register Environmental Categories Taxonomy
     */
    private function register_environmental_categories() {
        $labels = array(
            'name'                       => __('Environmental Categories', 'environmental-platform-core'),
            'singular_name'              => __('Environmental Category', 'environmental-platform-core'),
            'menu_name'                  => __('Categories', 'environmental-platform-core'),
            'all_items'                  => __('All Categories', 'environmental-platform-core'),
            'parent_item'                => __('Parent Category', 'environmental-platform-core'),
            'parent_item_colon'          => __('Parent Category:', 'environmental-platform-core'),
            'new_item_name'              => __('New Category Name', 'environmental-platform-core'),
            'add_new_item'               => __('Add New Category', 'environmental-platform-core'),
            'edit_item'                  => __('Edit Category', 'environmental-platform-core'),
            'update_item'                => __('Update Category', 'environmental-platform-core'),
            'view_item'                  => __('View Category', 'environmental-platform-core'),
            'separate_items_with_commas' => __('Separate categories with commas', 'environmental-platform-core'),
            'add_or_remove_items'        => __('Add or remove categories', 'environmental-platform-core'),
            'choose_from_most_used'      => __('Choose from the most used', 'environmental-platform-core'),
            'popular_items'              => __('Popular Categories', 'environmental-platform-core'),
            'search_items'               => __('Search Categories', 'environmental-platform-core'),
            'not_found'                  => __('Not Found', 'environmental-platform-core'),
            'no_terms'                   => __('No categories', 'environmental-platform-core'),
            'items_list'                 => __('Categories list', 'environmental-platform-core'),
            'items_list_navigation'      => __('Categories list navigation', 'environmental-platform-core'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'show_in_rest'               => true,
            'rewrite'                    => array('slug' => 'environmental-category'),
        );
        
        $post_types = array(
            'env_article', 'env_report', 'env_alert', 'env_event', 
            'env_project', 'eco_product', 'community_post', 'edu_resource', 'env_petition'
        );
        
        register_taxonomy('env_category', $post_types, $args);
    }
    
    /**
     * Register Environmental Tags Taxonomy
     */
    private function register_environmental_tags() {
        $labels = array(
            'name'                       => __('Environmental Tags', 'environmental-platform-core'),
            'singular_name'              => __('Environmental Tag', 'environmental-platform-core'),
            'menu_name'                  => __('Tags', 'environmental-platform-core'),
            'all_items'                  => __('All Tags', 'environmental-platform-core'),
            'new_item_name'              => __('New Tag Name', 'environmental-platform-core'),
            'add_new_item'               => __('Add New Tag', 'environmental-platform-core'),
            'edit_item'                  => __('Edit Tag', 'environmental-platform-core'),
            'update_item'                => __('Update Tag', 'environmental-platform-core'),
            'view_item'                  => __('View Tag', 'environmental-platform-core'),
            'separate_items_with_commas' => __('Separate tags with commas', 'environmental-platform-core'),
            'add_or_remove_items'        => __('Add or remove tags', 'environmental-platform-core'),
            'choose_from_most_used'      => __('Choose from the most used', 'environmental-platform-core'),
            'popular_items'              => __('Popular Tags', 'environmental-platform-core'),
            'search_items'               => __('Search Tags', 'environmental-platform-core'),
            'not_found'                  => __('Not Found', 'environmental-platform-core'),
            'no_terms'                   => __('No tags', 'environmental-platform-core'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'show_in_rest'               => true,
            'rewrite'                    => array('slug' => 'environmental-tag'),
        );
        
        $post_types = array(
            'env_article', 'env_report', 'env_event', 'env_project', 
            'eco_product', 'community_post', 'edu_resource'
        );
        
        register_taxonomy('env_tag', $post_types, $args);
    }
    
    /**
     * Register Impact Levels Taxonomy
     */
    private function register_impact_levels() {
        $labels = array(
            'name'                       => __('Impact Levels', 'environmental-platform-core'),
            'singular_name'              => __('Impact Level', 'environmental-platform-core'),
            'menu_name'                  => __('Impact Levels', 'environmental-platform-core'),
            'all_items'                  => __('All Impact Levels', 'environmental-platform-core'),
            'edit_item'                  => __('Edit Impact Level', 'environmental-platform-core'),
            'view_item'                  => __('View Impact Level', 'environmental-platform-core'),
            'add_new_item'               => __('Add New Impact Level', 'environmental-platform-core'),
            'new_item_name'              => __('New Impact Level Name', 'environmental-platform-core'),
            'search_items'               => __('Search Impact Levels', 'environmental-platform-core'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_in_rest'               => true,
            'rewrite'                    => array('slug' => 'impact-level'),
        );
        
        register_taxonomy('impact_level', array('env_article', 'env_report', 'env_project'), $args);
    }
    
    /**
     * Register Regions Taxonomy
     */
    private function register_regions() {
        $labels = array(
            'name'                       => __('Regions', 'environmental-platform-core'),
            'singular_name'              => __('Region', 'environmental-platform-core'),
            'menu_name'                  => __('Regions', 'environmental-platform-core'),
            'all_items'                  => __('All Regions', 'environmental-platform-core'),
            'parent_item'                => __('Parent Region', 'environmental-platform-core'),
            'parent_item_colon'          => __('Parent Region:', 'environmental-platform-core'),
            'new_item_name'              => __('New Region Name', 'environmental-platform-core'),
            'add_new_item'               => __('Add New Region', 'environmental-platform-core'),
            'edit_item'                  => __('Edit Region', 'environmental-platform-core'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_in_rest'               => true,
            'rewrite'                    => array('slug' => 'region'),
        );
        
        $post_types = array(
            'env_article', 'env_alert', 'env_event', 'env_project', 
            'community_post', 'env_petition', 'item_exchange'
        );
        
        register_taxonomy('region', $post_types, $args);
    }
    
    /**
     * Register Sustainability Levels Taxonomy
     */
    private function register_sustainability_levels() {
        $labels = array(
            'name'                       => __('Sustainability Levels', 'environmental-platform-core'),
            'singular_name'              => __('Sustainability Level', 'environmental-platform-core'),
            'menu_name'                  => __('Sustainability', 'environmental-platform-core'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_rest'               => true,
            'rewrite'                    => array('slug' => 'sustainability-level'),
        );
        
        register_taxonomy('sustainability_level', array('eco_product'), $args);
    }
    
    /**
     * Register Project Status Taxonomy
     */
    private function register_project_status() {
        $labels = array(
            'name'                       => __('Project Status', 'environmental-platform-core'),
            'singular_name'              => __('Project Status', 'environmental-platform-core'),
            'menu_name'                  => __('Project Status', 'environmental-platform-core'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_rest'               => true,
            'rewrite'                    => array('slug' => 'project-status'),
        );
        
        register_taxonomy('project_status', array('env_project'), $args);
    }
    
    /**
     * Register Product Types Taxonomy
     */
    private function register_product_types() {
        $labels = array(
            'name'                       => __('Product Types', 'environmental-platform-core'),
            'singular_name'              => __('Product Type', 'environmental-platform-core'),
            'menu_name'                  => __('Product Types', 'environmental-platform-core'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_rest'               => true,
            'rewrite'                    => array('slug' => 'product-type'),
        );
        
        register_taxonomy('product_type', array('eco_product'), $args);
    }
    
    /**
     * Register Event Types Taxonomy
     */
    private function register_event_types() {
        $labels = array(
            'name'                       => __('Event Types', 'environmental-platform-core'),
            'singular_name'              => __('Event Type', 'environmental-platform-core'),
            'menu_name'                  => __('Event Types', 'environmental-platform-core'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_rest'               => true,
            'rewrite'                    => array('slug' => 'event-type'),
        );
        
        register_taxonomy('event_type', array('env_event'), $args);
    }
    
    /**
     * Register Report Types Taxonomy
     */
    private function register_report_types() {
        $labels = array(
            'name'                       => __('Report Types', 'environmental-platform-core'),
            'singular_name'              => __('Report Type', 'environmental-platform-core'),
            'menu_name'                  => __('Report Types', 'environmental-platform-core'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_rest'               => true,
            'rewrite'                    => array('slug' => 'report-type'),
        );
        
        register_taxonomy('report_type', array('env_report'), $args);
    }
    
    /**
     * Register Alert Types Taxonomy
     */
    private function register_alert_types() {
        $labels = array(
            'name'                       => __('Alert Types', 'environmental-platform-core'),
            'singular_name'              => __('Alert Type', 'environmental-platform-core'),
            'menu_name'                  => __('Alert Types', 'environmental-platform-core'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_rest'               => true,
            'rewrite'                    => array('slug' => 'alert-type'),
        );
        
        register_taxonomy('alert_type', array('env_alert'), $args);
    }
    
    /**
     * Register Priority Levels Taxonomy
     */
    private function register_priority_levels() {
        $labels = array(
            'name'                       => __('Priority Levels', 'environmental-platform-core'),
            'singular_name'              => __('Priority Level', 'environmental-platform-core'),
            'menu_name'                  => __('Priority Levels', 'environmental-platform-core'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_rest'               => true,
            'rewrite'                    => array('slug' => 'priority-level'),
        );
        
        register_taxonomy('priority_level', array('env_alert'), $args);
    }
    
    /**
     * Register Waste Types Taxonomy
     */
    private function register_waste_types() {
        $labels = array(
            'name'                       => __('Waste Types', 'environmental-platform-core'),
            'singular_name'              => __('Waste Type', 'environmental-platform-core'),
            'menu_name'                  => __('Waste Types', 'environmental-platform-core'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_rest'               => true,
            'rewrite'                    => array('slug' => 'waste-type'),
        );
        
        register_taxonomy('waste_type', array('waste_class'), $args);
    }
    
    /**
     * Register Recyclability Taxonomy
     */
    private function register_recyclability() {
        $labels = array(
            'name'                       => __('Recyclability', 'environmental-platform-core'),
            'singular_name'              => __('Recyclability', 'environmental-platform-core'),
            'menu_name'                  => __('Recyclability', 'environmental-platform-core'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_rest'               => true,
            'rewrite'                    => array('slug' => 'recyclability'),
        );
        
        register_taxonomy('recyclability', array('waste_class'), $args);
    }
    
    /**
     * Register Disposal Methods Taxonomy
     */
    private function register_disposal_methods() {
        $labels = array(
            'name'                       => __('Disposal Methods', 'environmental-platform-core'),
            'singular_name'              => __('Disposal Method', 'environmental-platform-core'),
            'menu_name'                  => __('Disposal Methods', 'environmental-platform-core'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_rest'               => true,
            'rewrite'                    => array('slug' => 'disposal-method'),
        );
        
        register_taxonomy('disposal_method', array('waste_class'), $args);
    }
    
    /**
     * Register Resource Types Taxonomy
     */
    private function register_resource_types() {
        $labels = array(
            'name'                       => __('Resource Types', 'environmental-platform-core'),
            'singular_name'              => __('Resource Type', 'environmental-platform-core'),
            'menu_name'                  => __('Resource Types', 'environmental-platform-core'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_rest'               => true,
            'rewrite'                    => array('slug' => 'resource-type'),
        );
        
        register_taxonomy('resource_type', array('edu_resource'), $args);
    }
    
    /**
     * Register Education Levels Taxonomy
     */
    private function register_education_levels() {
        $labels = array(
            'name'                       => __('Education Levels', 'environmental-platform-core'),
            'singular_name'              => __('Education Level', 'environmental-platform-core'),
            'menu_name'                  => __('Education Levels', 'environmental-platform-core'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_rest'               => true,
            'rewrite'                    => array('slug' => 'education-level'),
        );
        
        register_taxonomy('education_level', array('edu_resource'), $args);
    }
    
    /**
     * Register Community Types Taxonomy
     */
    private function register_community_types() {
        $labels = array(
            'name'                       => __('Community Types', 'environmental-platform-core'),
            'singular_name'              => __('Community Type', 'environmental-platform-core'),
            'menu_name'                  => __('Community Types', 'environmental-platform-core'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_rest'               => true,
            'rewrite'                    => array('slug' => 'community-type'),
        );
        
        register_taxonomy('community_type', array('community_post'), $args);
    }
    
    /**
     * Register Petition Types Taxonomy
     */
    private function register_petition_types() {
        $labels = array(
            'name'                       => __('Petition Types', 'environmental-platform-core'),
            'singular_name'              => __('Petition Type', 'environmental-platform-core'),
            'menu_name'                  => __('Petition Types', 'environmental-platform-core'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_rest'               => true,
            'rewrite'                    => array('slug' => 'petition-type'),
        );
        
        register_taxonomy('petition_type', array('env_petition'), $args);
    }
    
    /**
     * Register Exchange Types Taxonomy
     */
    private function register_exchange_types() {
        $labels = array(
            'name'                       => __('Exchange Types', 'environmental-platform-core'),
            'singular_name'              => __('Exchange Type', 'environmental-platform-core'),
            'menu_name'                  => __('Exchange Types', 'environmental-platform-core'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_rest'               => true,
            'rewrite'                    => array('slug' => 'exchange-type'),
        );
        
        register_taxonomy('exchange_type', array('item_exchange'), $args);
    }
    
    /**
     * Register Item Conditions Taxonomy
     */
    private function register_item_conditions() {
        $labels = array(
            'name'                       => __('Item Conditions', 'environmental-platform-core'),
            'singular_name'              => __('Item Condition', 'environmental-platform-core'),
            'menu_name'                  => __('Item Conditions', 'environmental-platform-core'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_rest'               => true,
            'rewrite'                    => array('slug' => 'item-condition'),
        );
        
        register_taxonomy('item_condition', array('item_exchange'), $args);
    }
    
    /**
     * Add custom meta fields to taxonomy edit pages
     */
    public function add_taxonomy_meta_fields() {
        add_action('env_category_edit_form_fields', array($this, 'category_meta_fields'));
        add_action('env_category_add_form_fields', array($this, 'category_meta_fields_add'));
    }
    
    /**
     * Category meta fields for edit form
     */
    public function category_meta_fields($term) {
        $color = get_term_meta($term->term_id, 'category_color', true);
        $icon = get_term_meta($term->term_id, 'category_icon', true);
        $environmental_score = get_term_meta($term->term_id, 'environmental_score', true);
        ?>
        <tr class="form-field">
            <th scope="row"><label for="category_color"><?php _e('Category Color', 'environmental-platform-core'); ?></label></th>
            <td>
                <input type="color" id="category_color" name="category_color" value="<?php echo esc_attr($color); ?>" />
                <p class="description"><?php _e('Choose a color for this category.', 'environmental-platform-core'); ?></p>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="category_icon"><?php _e('Category Icon', 'environmental-platform-core'); ?></label></th>
            <td>
                <input type="text" id="category_icon" name="category_icon" value="<?php echo esc_attr($icon); ?>" class="regular-text" />
                <p class="description"><?php _e('Enter a dashicon class name (e.g., dashicons-admin-site-alt3).', 'environmental-platform-core'); ?></p>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="environmental_score"><?php _e('Environmental Score', 'environmental-platform-core'); ?></label></th>
            <td>
                <input type="number" id="environmental_score" name="environmental_score" value="<?php echo esc_attr($environmental_score); ?>" min="1" max="100" />
                <p class="description"><?php _e('Environmental impact score for this category (1-100).', 'environmental-platform-core'); ?></p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Category meta fields for add form
     */
    public function category_meta_fields_add() {
        ?>
        <div class="form-field">
            <label for="category_color"><?php _e('Category Color', 'environmental-platform-core'); ?></label>
            <input type="color" id="category_color" name="category_color" value="#28a745" />
            <p><?php _e('Choose a color for this category.', 'environmental-platform-core'); ?></p>
        </div>
        <div class="form-field">
            <label for="category_icon"><?php _e('Category Icon', 'environmental-platform-core'); ?></label>
            <input type="text" id="category_icon" name="category_icon" class="regular-text" />
            <p><?php _e('Enter a dashicon class name (e.g., dashicons-admin-site-alt3).', 'environmental-platform-core'); ?></p>
        </div>
        <div class="form-field">
            <label for="environmental_score"><?php _e('Environmental Score', 'environmental-platform-core'); ?></label>
            <input type="number" id="environmental_score" name="environmental_score" min="1" max="100" />
            <p><?php _e('Environmental impact score for this category (1-100).', 'environmental-platform-core'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Save taxonomy meta fields
     */
    public function save_taxonomy_meta($term_id, $tt_id, $taxonomy) {
        if ($taxonomy == 'env_category') {
            if (isset($_POST['category_color'])) {
                update_term_meta($term_id, 'category_color', sanitize_hex_color($_POST['category_color']));
            }
            if (isset($_POST['category_icon'])) {
                update_term_meta($term_id, 'category_icon', sanitize_text_field($_POST['category_icon']));
            }
            if (isset($_POST['environmental_score'])) {
                update_term_meta($term_id, 'environmental_score', absint($_POST['environmental_score']));
            }
        }
    }
    
    /**
     * Add custom columns to taxonomy admin
     */
    public function add_taxonomy_columns($columns) {
        $columns['category_color'] = __('Color', 'environmental-platform-core');
        $columns['category_icon'] = __('Icon', 'environmental-platform-core');
        $columns['environmental_score'] = __('Env. Score', 'environmental-platform-core');
        return $columns;
    }
    
    /**
     * Add content to custom taxonomy columns
     */
    public function add_taxonomy_column_content($content, $column_name, $term_id) {
        switch ($column_name) {
            case 'category_color':
                $color = get_term_meta($term_id, 'category_color', true);
                if ($color) {
                    $content = '<span style="display:inline-block;width:20px;height:20px;background-color:' . esc_attr($color) . ';border-radius:50%;"></span>';
                }
                break;
            case 'category_icon':
                $icon = get_term_meta($term_id, 'category_icon', true);
                if ($icon) {
                    $content = '<span class="dashicons ' . esc_attr($icon) . '"></span>';
                }
                break;
            case 'environmental_score':
                $score = get_term_meta($term_id, 'environmental_score', true);
                if ($score) {
                    $content = $score . '/100';
                }
                break;
        }
        return $content;
    }
    
    /**
     * Get default taxonomy terms
     */
    public function create_default_terms() {
        // Create default environmental categories
        $default_categories = array(
            array('name' => 'Climate Change', 'slug' => 'climate-change', 'color' => '#ff6b6b', 'icon' => 'dashicons-admin-site-alt3', 'score' => 95),
            array('name' => 'Renewable Energy', 'slug' => 'renewable-energy', 'color' => '#4ecdc4', 'icon' => 'dashicons-lightbulb', 'score' => 90),
            array('name' => 'Waste Management', 'slug' => 'waste-management', 'color' => '#45b7d1', 'icon' => 'dashicons-trash', 'score' => 85),
            array('name' => 'Water Conservation', 'slug' => 'water-conservation', 'color' => '#96ceb4', 'icon' => 'dashicons-admin-site', 'score' => 88),
            array('name' => 'Biodiversity', 'slug' => 'biodiversity', 'color' => '#feca57', 'icon' => 'dashicons-admin-site-alt2', 'score' => 92),
            array('name' => 'Sustainable Agriculture', 'slug' => 'sustainable-agriculture', 'color' => '#6c5ce7', 'icon' => 'dashicons-carrot', 'score' => 87),
            array('name' => 'Green Transportation', 'slug' => 'green-transportation', 'color' => '#fd79a8', 'icon' => 'dashicons-admin-generic', 'score' => 83),
            array('name' => 'Eco-friendly Products', 'slug' => 'eco-friendly-products', 'color' => '#00b894', 'icon' => 'dashicons-products', 'score' => 80),
        );
        
        foreach ($default_categories as $category) {
            if (!term_exists($category['slug'], 'env_category')) {
                $term = wp_insert_term($category['name'], 'env_category', array('slug' => $category['slug']));
                if (!is_wp_error($term)) {
                    update_term_meta($term['term_id'], 'category_color', $category['color']);
                    update_term_meta($term['term_id'], 'category_icon', $category['icon']);
                    update_term_meta($term['term_id'], 'environmental_score', $category['score']);
                }
            }
        }
        
        // Create default impact levels
        $impact_levels = array(
            array('name' => 'Critical', 'slug' => 'critical'),
            array('name' => 'High', 'slug' => 'high'),
            array('name' => 'Medium', 'slug' => 'medium'),
            array('name' => 'Low', 'slug' => 'low'),
        );
        
        foreach ($impact_levels as $level) {
            if (!term_exists($level['slug'], 'impact_level')) {
                wp_insert_term($level['name'], 'impact_level', array('slug' => $level['slug']));
            }
        }
        
        // Create default sustainability levels
        $sustainability_levels = array(
            array('name' => 'Excellent', 'slug' => 'excellent'),
            array('name' => 'Good', 'slug' => 'good'),
            array('name' => 'Moderate', 'slug' => 'moderate'),
            array('name' => 'Poor', 'slug' => 'poor'),
        );
        
        foreach ($sustainability_levels as $level) {
            if (!term_exists($level['slug'], 'sustainability_level')) {
                wp_insert_term($level['name'], 'sustainability_level', array('slug' => $level['slug']));
            }
        }
        
        // Create default project statuses
        $project_statuses = array(
            array('name' => 'Planning', 'slug' => 'planning'),
            array('name' => 'In Progress', 'slug' => 'in-progress'),
            array('name' => 'Completed', 'slug' => 'completed'),
            array('name' => 'On Hold', 'slug' => 'on-hold'),
            array('name' => 'Cancelled', 'slug' => 'cancelled'),
        );
        
        foreach ($project_statuses as $status) {
            if (!term_exists($status['slug'], 'project_status')) {
                wp_insert_term($status['name'], 'project_status', array('slug' => $status['slug']));
            }
        }
    }
}

// Initialize the taxonomies class
new EP_Taxonomies();
