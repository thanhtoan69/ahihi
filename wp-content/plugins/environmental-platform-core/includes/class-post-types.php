<?php
/**
 * Environmental Platform Custom Post Types
 * 
 * Manages all custom post types for the Environmental Platform
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EP_Post_Types {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_post_types'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_filter('post_updated_messages', array($this, 'updated_messages'));
        add_filter('enter_title_here', array($this, 'change_title_placeholder'));
    }
    
    /**
     * Register all custom post types
     */
    public function register_post_types() {
        $this->register_environmental_articles();
        $this->register_environmental_reports();
        $this->register_environmental_alerts();
        $this->register_environmental_events();
        $this->register_environmental_projects();
        $this->register_eco_products();
        $this->register_community_posts();
        $this->register_educational_resources();
        $this->register_waste_classifications();
        $this->register_petitions();
        $this->register_exchanges();
    }
    
    /**
     * Register Environmental Articles Post Type
     */
    private function register_environmental_articles() {
        $labels = array(
            'name'                  => __('Environmental Articles', 'environmental-platform-core'),
            'singular_name'         => __('Environmental Article', 'environmental-platform-core'),
            'menu_name'             => __('Articles', 'environmental-platform-core'),
            'name_admin_bar'        => __('Article', 'environmental-platform-core'),
            'archives'              => __('Article Archives', 'environmental-platform-core'),
            'attributes'            => __('Article Attributes', 'environmental-platform-core'),
            'parent_item_colon'     => __('Parent Article:', 'environmental-platform-core'),
            'all_items'             => __('All Articles', 'environmental-platform-core'),
            'add_new_item'          => __('Add New Article', 'environmental-platform-core'),
            'add_new'               => __('Add New', 'environmental-platform-core'),
            'new_item'              => __('New Article', 'environmental-platform-core'),
            'edit_item'             => __('Edit Article', 'environmental-platform-core'),
            'update_item'           => __('Update Article', 'environmental-platform-core'),
            'view_item'             => __('View Article', 'environmental-platform-core'),
            'view_items'            => __('View Articles', 'environmental-platform-core'),
            'search_items'          => __('Search Articles', 'environmental-platform-core'),
            'not_found'             => __('Not found', 'environmental-platform-core'),
            'not_found_in_trash'    => __('Not found in Trash', 'environmental-platform-core'),
            'featured_image'        => __('Featured Image', 'environmental-platform-core'),
            'set_featured_image'    => __('Set featured image', 'environmental-platform-core'),
            'remove_featured_image' => __('Remove featured image', 'environmental-platform-core'),
            'use_featured_image'    => __('Use as featured image', 'environmental-platform-core'),
            'insert_into_item'      => __('Insert into article', 'environmental-platform-core'),
            'uploaded_to_this_item' => __('Uploaded to this article', 'environmental-platform-core'),
            'items_list'            => __('Articles list', 'environmental-platform-core'),
            'items_list_navigation' => __('Articles list navigation', 'environmental-platform-core'),
            'filter_items_list'     => __('Filter articles list', 'environmental-platform-core'),
        );
        
        $args = array(
            'label'                 => __('Environmental Article', 'environmental-platform-core'),
            'description'           => __('Environmental articles and news', 'environmental-platform-core'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'thumbnail', 'excerpt', 'comments', 'author', 'revisions'),
            'taxonomies'            => array('env_category', 'env_tag', 'impact_level', 'region'),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => 'environmental-platform',
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-admin-post',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => 'environmental-articles',
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
            'rest_base'             => 'environmental-articles',
            'rewrite'               => array('slug' => 'environmental-articles', 'with_front' => false),
        );
        
        register_post_type('env_article', $args);
    }
    
    /**
     * Register Environmental Reports Post Type
     */
    private function register_environmental_reports() {
        $labels = array(
            'name'                  => __('Environmental Reports', 'environmental-platform-core'),
            'singular_name'         => __('Environmental Report', 'environmental-platform-core'),
            'menu_name'             => __('Reports', 'environmental-platform-core'),
            'add_new_item'          => __('Add New Report', 'environmental-platform-core'),
            'edit_item'             => __('Edit Report', 'environmental-platform-core'),
            'view_item'             => __('View Report', 'environmental-platform-core'),
            'all_items'             => __('All Reports', 'environmental-platform-core'),
            'search_items'          => __('Search Reports', 'environmental-platform-core'),
            'not_found'             => __('No reports found', 'environmental-platform-core'),
        );
        
        $args = array(
            'labels'                => $labels,
            'public'                => true,
            'publicly_queryable'    => true,
            'show_ui'               => true,
            'show_in_menu'          => 'environmental-platform',
            'query_var'             => true,
            'rewrite'               => array('slug' => 'reports'),
            'capability_type'       => 'post',
            'has_archive'           => true,
            'hierarchical'          => false,
            'menu_position'         => null,
            'menu_icon'             => 'dashicons-chart-line',
            'supports'              => array('title', 'editor', 'thumbnail', 'excerpt', 'author'),
            'taxonomies'            => array('env_category', 'report_type', 'impact_level'),
            'show_in_rest'          => true,
        );
        
        register_post_type('env_report', $args);
    }
    
    /**
     * Register Environmental Alerts Post Type
     */
    private function register_environmental_alerts() {
        $labels = array(
            'name'                  => __('Environmental Alerts', 'environmental-platform-core'),
            'singular_name'         => __('Environmental Alert', 'environmental-platform-core'),
            'menu_name'             => __('Alerts', 'environmental-platform-core'),
            'add_new_item'          => __('Add New Alert', 'environmental-platform-core'),
            'edit_item'             => __('Edit Alert', 'environmental-platform-core'),
        );
        
        $args = array(
            'labels'                => $labels,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => 'environmental-platform',
            'menu_icon'             => 'dashicons-warning',
            'supports'              => array('title', 'editor', 'thumbnail', 'author'),
            'taxonomies'            => array('alert_type', 'priority_level', 'region'),
            'has_archive'           => true,
            'rewrite'               => array('slug' => 'alerts'),
            'show_in_rest'          => true,
        );
        
        register_post_type('env_alert', $args);
    }
    
    /**
     * Register Environmental Events Post Type
     */
    private function register_environmental_events() {
        $labels = array(
            'name'                  => __('Environmental Events', 'environmental-platform-core'),
            'singular_name'         => __('Environmental Event', 'environmental-platform-core'),
            'menu_name'             => __('Events', 'environmental-platform-core'),
            'add_new_item'          => __('Add New Event', 'environmental-platform-core'),
            'edit_item'             => __('Edit Event', 'environmental-platform-core'),
        );
        
        $args = array(
            'labels'                => $labels,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => 'environmental-platform',
            'menu_icon'             => 'dashicons-calendar-alt',
            'supports'              => array('title', 'editor', 'thumbnail', 'excerpt', 'author'),
            'taxonomies'            => array('event_type', 'env_category', 'region'),
            'has_archive'           => true,
            'rewrite'               => array('slug' => 'events'),
            'show_in_rest'          => true,
        );
        
        register_post_type('env_event', $args);
    }
    
    /**
     * Register Environmental Projects Post Type
     */
    private function register_environmental_projects() {
        $labels = array(
            'name'                  => __('Environmental Projects', 'environmental-platform-core'),
            'singular_name'         => __('Environmental Project', 'environmental-platform-core'),
            'menu_name'             => __('Projects', 'environmental-platform-core'),
            'add_new_item'          => __('Add New Project', 'environmental-platform-core'),
            'edit_item'             => __('Edit Project', 'environmental-platform-core'),
        );
        
        $args = array(
            'labels'                => $labels,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => 'environmental-platform',
            'menu_icon'             => 'dashicons-admin-tools',
            'supports'              => array('title', 'editor', 'thumbnail', 'excerpt', 'author', 'comments'),
            'taxonomies'            => array('project_type', 'project_status', 'impact_level', 'region'),
            'has_archive'           => true,
            'rewrite'               => array('slug' => 'projects'),
            'show_in_rest'          => true,
        );
        
        register_post_type('env_project', $args);
    }
    
    /**
     * Register Eco Products Post Type
     */
    private function register_eco_products() {
        $labels = array(
            'name'                  => __('Eco Products', 'environmental-platform-core'),
            'singular_name'         => __('Eco Product', 'environmental-platform-core'),
            'menu_name'             => __('Eco Products', 'environmental-platform-core'),
            'add_new_item'          => __('Add New Product', 'environmental-platform-core'),
            'edit_item'             => __('Edit Product', 'environmental-platform-core'),
        );
        
        $args = array(
            'labels'                => $labels,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => 'environmental-platform',
            'menu_icon'             => 'dashicons-products',
            'supports'              => array('title', 'editor', 'thumbnail', 'excerpt', 'author', 'comments'),
            'taxonomies'            => array('product_type', 'sustainability_level', 'env_category'),
            'has_archive'           => true,
            'rewrite'               => array('slug' => 'eco-products'),
            'show_in_rest'          => true,
        );
        
        register_post_type('eco_product', $args);
    }
    
    /**
     * Register Community Posts Post Type
     */
    private function register_community_posts() {
        $labels = array(
            'name'                  => __('Community Posts', 'environmental-platform-core'),
            'singular_name'         => __('Community Post', 'environmental-platform-core'),
            'menu_name'             => __('Community', 'environmental-platform-core'),
            'add_new_item'          => __('Add New Community Post', 'environmental-platform-core'),
            'edit_item'             => __('Edit Community Post', 'environmental-platform-core'),
        );
        
        $args = array(
            'labels'                => $labels,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => 'environmental-platform',
            'menu_icon'             => 'dashicons-groups',
            'supports'              => array('title', 'editor', 'thumbnail', 'excerpt', 'author', 'comments'),
            'taxonomies'            => array('community_type', 'env_category', 'region'),
            'has_archive'           => true,
            'rewrite'               => array('slug' => 'community'),
            'show_in_rest'          => true,
        );
        
        register_post_type('community_post', $args);
    }
    
    /**
     * Register Educational Resources Post Type
     */
    private function register_educational_resources() {
        $labels = array(
            'name'                  => __('Educational Resources', 'environmental-platform-core'),
            'singular_name'         => __('Educational Resource', 'environmental-platform-core'),
            'menu_name'             => __('Education', 'environmental-platform-core'),
            'add_new_item'          => __('Add New Resource', 'environmental-platform-core'),
            'edit_item'             => __('Edit Resource', 'environmental-platform-core'),
        );
        
        $args = array(
            'labels'                => $labels,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => 'environmental-platform',
            'menu_icon'             => 'dashicons-book',
            'supports'              => array('title', 'editor', 'thumbnail', 'excerpt', 'author', 'comments'),
            'taxonomies'            => array('resource_type', 'education_level', 'env_category'),
            'has_archive'           => true,
            'rewrite'               => array('slug' => 'education'),
            'show_in_rest'          => true,
        );
        
        register_post_type('edu_resource', $args);
    }
    
    /**
     * Register Waste Classifications Post Type
     */
    private function register_waste_classifications() {
        $labels = array(
            'name'                  => __('Waste Classifications', 'environmental-platform-core'),
            'singular_name'         => __('Waste Classification', 'environmental-platform-core'),
            'menu_name'             => __('Waste Types', 'environmental-platform-core'),
            'add_new_item'          => __('Add New Classification', 'environmental-platform-core'),
            'edit_item'             => __('Edit Classification', 'environmental-platform-core'),
        );
        
        $args = array(
            'labels'                => $labels,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => 'environmental-platform',
            'menu_icon'             => 'dashicons-trash',
            'supports'              => array('title', 'editor', 'thumbnail', 'excerpt'),
            'taxonomies'            => array('waste_type', 'recyclability', 'disposal_method'),
            'has_archive'           => true,
            'rewrite'               => array('slug' => 'waste-types'),
            'show_in_rest'          => true,
        );
        
        register_post_type('waste_class', $args);
    }
    
    /**
     * Register Petitions Post Type
     */
    private function register_petitions() {
        $labels = array(
            'name'                  => __('Environmental Petitions', 'environmental-platform-core'),
            'singular_name'         => __('Environmental Petition', 'environmental-platform-core'),
            'menu_name'             => __('Petitions', 'environmental-platform-core'),
            'add_new_item'          => __('Add New Petition', 'environmental-platform-core'),
            'edit_item'             => __('Edit Petition', 'environmental-platform-core'),
        );
        
        $args = array(
            'labels'                => $labels,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => 'environmental-platform',
            'menu_icon'             => 'dashicons-megaphone',
            'supports'              => array('title', 'editor', 'thumbnail', 'excerpt', 'author', 'comments'),
            'taxonomies'            => array('petition_type', 'env_category', 'region'),
            'has_archive'           => true,
            'rewrite'               => array('slug' => 'petitions'),
            'show_in_rest'          => true,
        );
        
        register_post_type('env_petition', $args);
    }
    
    /**
     * Register Exchanges Post Type
     */
    private function register_exchanges() {
        $labels = array(
            'name'                  => __('Item Exchanges', 'environmental-platform-core'),
            'singular_name'         => __('Item Exchange', 'environmental-platform-core'),
            'menu_name'             => __('Exchanges', 'environmental-platform-core'),
            'add_new_item'          => __('Add New Exchange', 'environmental-platform-core'),
            'edit_item'             => __('Edit Exchange', 'environmental-platform-core'),
        );
        
        $args = array(
            'labels'                => $labels,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => 'environmental-platform',
            'menu_icon'             => 'dashicons-randomize',
            'supports'              => array('title', 'editor', 'thumbnail', 'excerpt', 'author', 'comments'),
            'taxonomies'            => array('exchange_type', 'item_condition', 'region'),
            'has_archive'           => true,
            'rewrite'               => array('slug' => 'exchanges'),
            'show_in_rest'          => true,
        );
        
        register_post_type('item_exchange', $args);
    }
    
    /**
     * Add meta boxes for environmental data
     */
    public function add_meta_boxes() {
        $post_types = array(
            'env_article', 'env_report', 'env_alert', 'env_event', 
            'env_project', 'eco_product', 'community_post', 'edu_resource',
            'waste_class', 'env_petition', 'item_exchange'
        );
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'environmental_data',
                __('Environmental Data', 'environmental-platform-core'),
                array($this, 'environmental_data_meta_box'),
                $post_type,
                'normal',
                'high'
            );
        }
        
        // Specific meta boxes for different post types
        add_meta_box(
            'event_details',
            __('Event Details', 'environmental-platform-core'),
            array($this, 'event_details_meta_box'),
            'env_event',
            'side',
            'high'
        );
        
        add_meta_box(
            'project_details',
            __('Project Details', 'environmental-platform-core'),
            array($this, 'project_details_meta_box'),
            'env_project',
            'side',
            'high'
        );
        
        add_meta_box(
            'product_details',
            __('Product Details', 'environmental-platform-core'),
            array($this, 'product_details_meta_box'),
            'eco_product',
            'side',
            'high'
        );
    }
    
    /**
     * Environmental data meta box
     */
    public function environmental_data_meta_box($post) {
        wp_nonce_field('environmental_data_meta_box', 'environmental_data_meta_box_nonce');
        
        $environmental_score = get_post_meta($post->ID, '_environmental_score', true);
        $carbon_impact = get_post_meta($post->ID, '_carbon_impact', true);
        $sustainability_rating = get_post_meta($post->ID, '_sustainability_rating', true);
        $eco_keywords = get_post_meta($post->ID, '_eco_keywords', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="environmental_score"><?php _e('Environmental Score (1-100)', 'environmental-platform-core'); ?></label></th>
                <td><input type="number" id="environmental_score" name="environmental_score" value="<?php echo esc_attr($environmental_score); ?>" min="1" max="100" /></td>
            </tr>
            <tr>
                <th><label for="carbon_impact"><?php _e('Carbon Impact (kg CO2)', 'environmental-platform-core'); ?></label></th>
                <td><input type="number" id="carbon_impact" name="carbon_impact" value="<?php echo esc_attr($carbon_impact); ?>" step="0.01" /></td>
            </tr>
            <tr>
                <th><label for="sustainability_rating"><?php _e('Sustainability Rating', 'environmental-platform-core'); ?></label></th>
                <td>
                    <select id="sustainability_rating" name="sustainability_rating">
                        <option value=""><?php _e('Select Rating', 'environmental-platform-core'); ?></option>
                        <option value="excellent" <?php selected($sustainability_rating, 'excellent'); ?>><?php _e('Excellent', 'environmental-platform-core'); ?></option>
                        <option value="good" <?php selected($sustainability_rating, 'good'); ?>><?php _e('Good', 'environmental-platform-core'); ?></option>
                        <option value="moderate" <?php selected($sustainability_rating, 'moderate'); ?>><?php _e('Moderate', 'environmental-platform-core'); ?></option>
                        <option value="poor" <?php selected($sustainability_rating, 'poor'); ?>><?php _e('Poor', 'environmental-platform-core'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="eco_keywords"><?php _e('Eco Keywords', 'environmental-platform-core'); ?></label></th>
                <td><input type="text" id="eco_keywords" name="eco_keywords" value="<?php echo esc_attr($eco_keywords); ?>" class="regular-text" placeholder="<?php _e('renewable, sustainable, green', 'environmental-platform-core'); ?>" /></td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Event details meta box
     */
    public function event_details_meta_box($post) {
        wp_nonce_field('event_details_meta_box', 'event_details_meta_box_nonce');
        
        $event_date = get_post_meta($post->ID, '_event_date', true);
        $event_time = get_post_meta($post->ID, '_event_time', true);
        $event_location = get_post_meta($post->ID, '_event_location', true);
        $event_capacity = get_post_meta($post->ID, '_event_capacity', true);
        $registration_required = get_post_meta($post->ID, '_registration_required', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="event_date"><?php _e('Event Date', 'environmental-platform-core'); ?></label></th>
                <td><input type="date" id="event_date" name="event_date" value="<?php echo esc_attr($event_date); ?>" /></td>
            </tr>
            <tr>
                <th><label for="event_time"><?php _e('Event Time', 'environmental-platform-core'); ?></label></th>
                <td><input type="time" id="event_time" name="event_time" value="<?php echo esc_attr($event_time); ?>" /></td>
            </tr>
            <tr>
                <th><label for="event_location"><?php _e('Location', 'environmental-platform-core'); ?></label></th>
                <td><input type="text" id="event_location" name="event_location" value="<?php echo esc_attr($event_location); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="event_capacity"><?php _e('Capacity', 'environmental-platform-core'); ?></label></th>
                <td><input type="number" id="event_capacity" name="event_capacity" value="<?php echo esc_attr($event_capacity); ?>" min="1" /></td>
            </tr>
            <tr>
                <th><label for="registration_required"><?php _e('Registration Required', 'environmental-platform-core'); ?></label></th>
                <td><input type="checkbox" id="registration_required" name="registration_required" value="1" <?php checked($registration_required, '1'); ?> /></td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Project details meta box
     */
    public function project_details_meta_box($post) {
        wp_nonce_field('project_details_meta_box', 'project_details_meta_box_nonce');
        
        $project_budget = get_post_meta($post->ID, '_project_budget', true);
        $project_duration = get_post_meta($post->ID, '_project_duration', true);
        $project_participants = get_post_meta($post->ID, '_project_participants', true);
        $project_progress = get_post_meta($post->ID, '_project_progress', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="project_budget"><?php _e('Budget (VND)', 'environmental-platform-core'); ?></label></th>
                <td><input type="number" id="project_budget" name="project_budget" value="<?php echo esc_attr($project_budget); ?>" /></td>
            </tr>
            <tr>
                <th><label for="project_duration"><?php _e('Duration (months)', 'environmental-platform-core'); ?></label></th>
                <td><input type="number" id="project_duration" name="project_duration" value="<?php echo esc_attr($project_duration); ?>" min="1" /></td>
            </tr>
            <tr>
                <th><label for="project_participants"><?php _e('Participants', 'environmental-platform-core'); ?></label></th>
                <td><input type="number" id="project_participants" name="project_participants" value="<?php echo esc_attr($project_participants); ?>" min="1" /></td>
            </tr>
            <tr>
                <th><label for="project_progress"><?php _e('Progress (%)', 'environmental-platform-core'); ?></label></th>
                <td><input type="number" id="project_progress" name="project_progress" value="<?php echo esc_attr($project_progress); ?>" min="0" max="100" /></td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Product details meta box
     */
    public function product_details_meta_box($post) {
        wp_nonce_field('product_details_meta_box', 'product_details_meta_box_nonce');
        
        $product_price = get_post_meta($post->ID, '_product_price', true);
        $eco_certification = get_post_meta($post->ID, '_eco_certification', true);
        $recyclable_percentage = get_post_meta($post->ID, '_recyclable_percentage', true);
        $biodegradable = get_post_meta($post->ID, '_biodegradable', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="product_price"><?php _e('Price (VND)', 'environmental-platform-core'); ?></label></th>
                <td><input type="number" id="product_price" name="product_price" value="<?php echo esc_attr($product_price); ?>" /></td>
            </tr>
            <tr>
                <th><label for="eco_certification"><?php _e('Eco Certification', 'environmental-platform-core'); ?></label></th>
                <td><input type="text" id="eco_certification" name="eco_certification" value="<?php echo esc_attr($eco_certification); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="recyclable_percentage"><?php _e('Recyclable (%)', 'environmental-platform-core'); ?></label></th>
                <td><input type="number" id="recyclable_percentage" name="recyclable_percentage" value="<?php echo esc_attr($recyclable_percentage); ?>" min="0" max="100" /></td>
            </tr>
            <tr>
                <th><label for="biodegradable"><?php _e('Biodegradable', 'environmental-platform-core'); ?></label></th>
                <td><input type="checkbox" id="biodegradable" name="biodegradable" value="1" <?php checked($biodegradable, '1'); ?> /></td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save meta box data
     */
    public function save_meta_boxes($post_id) {
        // Check if nonces are set
        if (!isset($_POST['environmental_data_meta_box_nonce'])) {
            return;
        }
        
        // Verify nonces
        if (!wp_verify_nonce($_POST['environmental_data_meta_box_nonce'], 'environmental_data_meta_box')) {
            return;
        }
        
        // Check if user has permissions to save data
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save environmental data
        $environmental_fields = array(
            'environmental_score', 'carbon_impact', 'sustainability_rating', 'eco_keywords'
        );
        
        foreach ($environmental_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Save event-specific data
        if (isset($_POST['event_details_meta_box_nonce']) && 
            wp_verify_nonce($_POST['event_details_meta_box_nonce'], 'event_details_meta_box')) {
            
            $event_fields = array(
                'event_date', 'event_time', 'event_location', 'event_capacity', 'registration_required'
            );
            
            foreach ($event_fields as $field) {
                if (isset($_POST[$field])) {
                    update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
                }
            }
        }
        
        // Save project-specific data
        if (isset($_POST['project_details_meta_box_nonce']) && 
            wp_verify_nonce($_POST['project_details_meta_box_nonce'], 'project_details_meta_box')) {
            
            $project_fields = array(
                'project_budget', 'project_duration', 'project_participants', 'project_progress'
            );
            
            foreach ($project_fields as $field) {
                if (isset($_POST[$field])) {
                    update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
                }
            }
        }
        
        // Save product-specific data
        if (isset($_POST['product_details_meta_box_nonce']) && 
            wp_verify_nonce($_POST['product_details_meta_box_nonce'], 'product_details_meta_box')) {
            
            $product_fields = array(
                'product_price', 'eco_certification', 'recyclable_percentage', 'biodegradable'
            );
            
            foreach ($product_fields as $field) {
                if (isset($_POST[$field])) {
                    update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
                }
            }
        }
    }
    
    /**
     * Custom post update messages
     */
    public function updated_messages($messages) {
        $post_types = array(
            'env_article' => __('Environmental Article', 'environmental-platform-core'),
            'env_report' => __('Environmental Report', 'environmental-platform-core'),
            'env_alert' => __('Environmental Alert', 'environmental-platform-core'),
            'env_event' => __('Environmental Event', 'environmental-platform-core'),
            'env_project' => __('Environmental Project', 'environmental-platform-core'),
            'eco_product' => __('Eco Product', 'environmental-platform-core'),
            'community_post' => __('Community Post', 'environmental-platform-core'),
            'edu_resource' => __('Educational Resource', 'environmental-platform-core'),
            'waste_class' => __('Waste Classification', 'environmental-platform-core'),
            'env_petition' => __('Environmental Petition', 'environmental-platform-core'),
            'item_exchange' => __('Item Exchange', 'environmental-platform-core'),
        );
        
        foreach ($post_types as $post_type => $label) {
            $messages[$post_type] = array(
                0  => '', // Unused. Messages start at index 1.
                1  => sprintf(__('%s updated.', 'environmental-platform-core'), $label),
                2  => __('Custom field updated.', 'environmental-platform-core'),
                3  => __('Custom field deleted.', 'environmental-platform-core'),
                4  => sprintf(__('%s updated.', 'environmental-platform-core'), $label),
                5  => isset($_GET['revision']) ? sprintf(__('%s restored to revision from %s', 'environmental-platform-core'), $label, wp_post_revision_title((int) $_GET['revision'], false)) : false,
                6  => sprintf(__('%s published.', 'environmental-platform-core'), $label),
                7  => sprintf(__('%s saved.', 'environmental-platform-core'), $label),
                8  => sprintf(__('%s submitted.', 'environmental-platform-core'), $label),
                9  => sprintf(__('%s scheduled.', 'environmental-platform-core'), $label),
                10 => sprintf(__('%s draft updated.', 'environmental-platform-core'), $label)
            );
        }
        
        return $messages;
    }
    
    /**
     * Change title placeholder for custom post types
     */
    public function change_title_placeholder($title) {
        $screen = get_current_screen();
        
        $placeholders = array(
            'env_article' => __('Enter article title here', 'environmental-platform-core'),
            'env_report' => __('Enter report title here', 'environmental-platform-core'),
            'env_alert' => __('Enter alert title here', 'environmental-platform-core'),
            'env_event' => __('Enter event title here', 'environmental-platform-core'),
            'env_project' => __('Enter project name here', 'environmental-platform-core'),
            'eco_product' => __('Enter product name here', 'environmental-platform-core'),
            'community_post' => __('Enter community post title here', 'environmental-platform-core'),
            'edu_resource' => __('Enter resource title here', 'environmental-platform-core'),
            'waste_class' => __('Enter waste type here', 'environmental-platform-core'),
            'env_petition' => __('Enter petition title here', 'environmental-platform-core'),
            'item_exchange' => __('Enter item exchange title here', 'environmental-platform-core'),
        );
        
        if (isset($placeholders[$screen->post_type])) {
            $title = $placeholders[$screen->post_type];
        }
        
        return $title;
    }
}

// Initialize the post types class
new EP_Post_Types();
