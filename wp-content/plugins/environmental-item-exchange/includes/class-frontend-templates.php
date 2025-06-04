<?php
/**
 * Frontend Templates for Environmental Item Exchange
 * 
 * Provides user-facing templates for displaying exchanges,
 * matches, and interactive elements
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Item_Exchange_Frontend_Templates {
    
    private static $instance = null;
    private $matching_engine;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->matching_engine = Environmental_Item_Exchange_Matching_Engine::get_instance();
        
        add_action('init', array($this, 'init_templates'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_shortcode('ep_exchange_form', array($this, 'render_exchange_form'));
        add_shortcode('ep_exchange_list', array($this, 'render_exchange_list'));
        add_shortcode('ep_my_exchanges', array($this, 'render_my_exchanges'));
        add_shortcode('ep_match_finder', array($this, 'render_match_finder'));
        add_shortcode('ep_user_dashboard', array($this, 'render_user_dashboard'));
        
        // AJAX handlers for frontend
        add_action('wp_ajax_ep_submit_exchange', array($this, 'ajax_submit_exchange'));
        add_action('wp_ajax_nopriv_ep_submit_exchange', array($this, 'ajax_submit_exchange'));
        add_action('wp_ajax_ep_get_user_matches', array($this, 'ajax_get_user_matches'));
        add_action('wp_ajax_ep_contact_user', array($this, 'ajax_contact_user'));
        add_action('wp_ajax_ep_update_exchange_status', array($this, 'ajax_update_exchange_status'));
        add_action('wp_ajax_ep_search_exchanges', array($this, 'ajax_search_exchanges'));
    }
    
    /**
     * Initialize template hooks
     */
    public function init_templates() {
        // Override single post template for item_exchange post type
        add_filter('single_template', array($this, 'single_exchange_template'));
        
        // Override archive template for item_exchange post type
        add_filter('archive_template', array($this, 'archive_exchange_template'));
        
        // Add custom content to single exchange posts
        add_filter('the_content', array($this, 'enhance_single_exchange_content'));
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        if (is_singular('item_exchange') || is_post_type_archive('item_exchange') || $this->has_exchange_shortcode()) {
            wp_enqueue_style('ep-frontend', 
                plugin_dir_url(__FILE__) . '../assets/css/frontend.css', 
                array(), 
                '1.0.0'
            );
            
            wp_enqueue_script('ep-frontend', 
                plugin_dir_url(__FILE__) . '../assets/js/frontend.js', 
                array('jquery'), 
                '1.0.0', 
                true
            );
            
            wp_localize_script('ep-frontend', 'epFrontend', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ep_frontend_nonce'),
                'strings' => array(
                    'loading' => __('Loading...', 'environmental-item-exchange'),
                    'error' => __('An error occurred', 'environmental-item-exchange'),
                    'success' => __('Success!', 'environmental-item-exchange'),
                    'confirm' => __('Are you sure?', 'environmental-item-exchange'),
                    'contact_sent' => __('Message sent successfully!', 'environmental-item-exchange'),
                    'login_required' => __('Please log in to continue', 'environmental-item-exchange')
                )
            ));
        }
    }
    
    /**
     * Check if current page has exchange shortcodes
     */
    private function has_exchange_shortcode() {
        global $post;
        if (!$post) return false;
        
        $shortcodes = array('ep_exchange_form', 'ep_exchange_list', 'ep_my_exchanges', 'ep_match_finder', 'ep_user_dashboard');
        foreach ($shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Custom single exchange template
     */
    public function single_exchange_template($template) {
        global $post;
        
        if ($post->post_type === 'item_exchange') {
            $plugin_template = plugin_dir_path(__FILE__) . '../templates/single-exchange.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Custom archive exchange template
     */
    public function archive_exchange_template($template) {
        if (is_post_type_archive('item_exchange')) {
            $plugin_template = plugin_dir_path(__FILE__) . '../templates/archive-exchange.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Enhance single exchange content
     */
    public function enhance_single_exchange_content($content) {
        if (!is_singular('item_exchange')) {
            return $content;
        }
        
        global $post;
        
        $enhanced_content = $this->render_exchange_details($post->ID);
        $enhanced_content .= $this->render_exchange_matches($post->ID);
        $enhanced_content .= $this->render_contact_form($post->ID);
        
        return $content . $enhanced_content;
    }
    
    /**
     * Render exchange form shortcode
     */
    public function render_exchange_form($atts) {
        $atts = shortcode_atts(array(
            'type' => 'all', // all, give_away, exchange, lending, request
            'redirect' => '',
            'class' => ''
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<div class="ep-login-required">' . 
                   __('Please log in to submit an exchange.', 'environmental-item-exchange') . 
                   ' <a href="' . wp_login_url(get_permalink()) . '">' . __('Log In', 'environmental-item-exchange') . '</a>' .
                   '</div>';
        }
        
        ob_start();
        ?>
        <div class="ep-exchange-form-container <?php echo esc_attr($atts['class']); ?>">
            <form id="ep-exchange-form" class="ep-exchange-form" enctype="multipart/form-data">
                <h3><?php _e('Submit an Item Exchange', 'environmental-item-exchange'); ?></h3>
                
                <div class="ep-form-group">
                    <label for="exchange_title"><?php _e('Item Title', 'environmental-item-exchange'); ?> *</label>
                    <input type="text" id="exchange_title" name="exchange_title" required 
                           placeholder="<?php _e('What are you offering or looking for?', 'environmental-item-exchange'); ?>">
                </div>
                
                <div class="ep-form-group">
                    <label for="exchange_description"><?php _e('Description', 'environmental-item-exchange'); ?> *</label>
                    <textarea id="exchange_description" name="exchange_description" rows="4" required 
                              placeholder="<?php _e('Provide details about the item, its condition, and any specific requirements...', 'environmental-item-exchange'); ?>"></textarea>
                </div>
                
                <div class="ep-form-row">
                    <div class="ep-form-group">
                        <label for="exchange_type"><?php _e('Exchange Type', 'environmental-item-exchange'); ?> *</label>
                        <select id="exchange_type" name="exchange_type" required>
                            <?php if ($atts['type'] === 'all' || $atts['type'] === 'give_away'): ?>
                            <option value="give_away"><?php _e('Give Away (Free)', 'environmental-item-exchange'); ?></option>
                            <?php endif; ?>
                            <?php if ($atts['type'] === 'all' || $atts['type'] === 'exchange'): ?>
                            <option value="exchange"><?php _e('Exchange/Trade', 'environmental-item-exchange'); ?></option>
                            <?php endif; ?>
                            <?php if ($atts['type'] === 'all' || $atts['type'] === 'lending'): ?>
                            <option value="lending"><?php _e('Lending/Borrowing', 'environmental-item-exchange'); ?></option>
                            <?php endif; ?>
                            <?php if ($atts['type'] === 'all' || $atts['type'] === 'request'): ?>
                            <option value="request"><?php _e('Request/Seeking', 'environmental-item-exchange'); ?></option>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="ep-form-group">
                        <label for="item_category"><?php _e('Category', 'environmental-item-exchange'); ?> *</label>
                        <select id="item_category" name="item_category" required>
                            <option value=""><?php _e('Select a category', 'environmental-item-exchange'); ?></option>
                            <?php 
                            $categories = get_terms(array(
                                'taxonomy' => 'exchange_type',
                                'hide_empty' => false
                            ));
                            foreach ($categories as $category): ?>
                            <option value="<?php echo $category->term_id; ?>"><?php echo $category->name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="ep-form-row">
                    <div class="ep-form-group">
                        <label for="item_condition"><?php _e('Condition', 'environmental-item-exchange'); ?></label>
                        <select id="item_condition" name="item_condition">
                            <option value="new"><?php _e('New', 'environmental-item-exchange'); ?></option>
                            <option value="like_new"><?php _e('Like New', 'environmental-item-exchange'); ?></option>
                            <option value="good" selected><?php _e('Good', 'environmental-item-exchange'); ?></option>
                            <option value="fair"><?php _e('Fair', 'environmental-item-exchange'); ?></option>
                            <option value="needs_repair"><?php _e('Needs Repair', 'environmental-item-exchange'); ?></option>
                        </select>
                    </div>
                    
                    <div class="ep-form-group">
                        <label for="estimated_value"><?php _e('Estimated Value (USD)', 'environmental-item-exchange'); ?></label>
                        <input type="number" id="estimated_value" name="estimated_value" min="0" step="0.01" 
                               placeholder="0.00">
                    </div>
                </div>
                
                <div class="ep-form-group">
                    <label for="exchange_location"><?php _e('Location', 'environmental-item-exchange'); ?> *</label>
                    <input type="text" id="exchange_location" name="exchange_location" required 
                           placeholder="<?php _e('City, State or Zip Code', 'environmental-item-exchange'); ?>">
                </div>
                
                <div class="ep-form-group">
                    <label for="item_images"><?php _e('Images', 'environmental-item-exchange'); ?></label>
                    <input type="file" id="item_images" name="item_images[]" multiple accept="image/*">
                    <small><?php _e('Upload up to 5 images (max 5MB each)', 'environmental-item-exchange'); ?></small>
                </div>
                
                <div class="ep-form-group">
                    <label for="contact_preferences"><?php _e('Contact Preferences', 'environmental-item-exchange'); ?></label>
                    <div class="ep-checkbox-group">
                        <label><input type="checkbox" name="contact_email" value="1" checked> <?php _e('Email notifications', 'environmental-item-exchange'); ?></label>
                        <label><input type="checkbox" name="contact_sms" value="1"> <?php _e('SMS notifications', 'environmental-item-exchange'); ?></label>
                        <label><input type="checkbox" name="is_urgent" value="1"> <?php _e('Urgent exchange', 'environmental-item-exchange'); ?></label>
                    </div>
                </div>
                
                <div class="ep-form-group">
                    <label for="additional_notes"><?php _e('Additional Notes', 'environmental-item-exchange'); ?></label>
                    <textarea id="additional_notes" name="additional_notes" rows="2" 
                              placeholder="<?php _e('Any additional information or special requirements...', 'environmental-item-exchange'); ?>"></textarea>
                </div>
                
                <div class="ep-form-actions">
                    <button type="submit" class="ep-btn ep-btn-primary">
                        <?php _e('Submit Exchange', 'environmental-item-exchange'); ?>
                    </button>
                    <button type="button" class="ep-btn ep-btn-secondary" onclick="EpFrontend.resetForm()">
                        <?php _e('Reset', 'environmental-item-exchange'); ?>
                    </button>
                </div>
                
                <input type="hidden" name="action" value="ep_submit_exchange">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('ep_frontend_nonce'); ?>">
                <input type="hidden" name="redirect_to" value="<?php echo esc_url($atts['redirect']); ?>">
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render exchange list shortcode
     */
    public function render_exchange_list($atts) {
        $atts = shortcode_atts(array(
            'type' => 'all',
            'category' => '',
            'location' => '',
            'limit' => 12,
            'show_filters' => 'true',
            'show_search' => 'true',
            'layout' => 'grid' // grid, list
        ), $atts);
        
        ob_start();
        ?>
        <div class="ep-exchange-list-container">
            <?php if ($atts['show_search'] === 'true' || $atts['show_filters'] === 'true'): ?>
            <div class="ep-search-filters">
                <?php if ($atts['show_search'] === 'true'): ?>
                <div class="ep-search-box">
                    <input type="text" id="ep-search-input" placeholder="<?php _e('Search exchanges...', 'environmental-item-exchange'); ?>">
                    <button type="button" id="ep-search-btn" class="ep-btn ep-btn-primary">
                        <?php _e('Search', 'environmental-item-exchange'); ?>
                    </button>
                </div>
                <?php endif; ?>
                
                <?php if ($atts['show_filters'] === 'true'): ?>
                <div class="ep-filters">
                    <select id="ep-filter-type">
                        <option value=""><?php _e('All Types', 'environmental-item-exchange'); ?></option>
                        <option value="give_away"><?php _e('Give Away', 'environmental-item-exchange'); ?></option>
                        <option value="exchange"><?php _e('Exchange', 'environmental-item-exchange'); ?></option>
                        <option value="lending"><?php _e('Lending', 'environmental-item-exchange'); ?></option>
                        <option value="request"><?php _e('Request', 'environmental-item-exchange'); ?></option>
                    </select>
                    
                    <select id="ep-filter-category">
                        <option value=""><?php _e('All Categories', 'environmental-item-exchange'); ?></option>
                        <?php 
                        $categories = get_terms(array('taxonomy' => 'exchange_type', 'hide_empty' => false));
                        foreach ($categories as $category): ?>
                        <option value="<?php echo $category->term_id; ?>"><?php echo $category->name; ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <input type="text" id="ep-filter-location" placeholder="<?php _e('Location', 'environmental-item-exchange'); ?>">
                    
                    <button type="button" id="ep-apply-filters" class="ep-btn ep-btn-secondary">
                        <?php _e('Apply Filters', 'environmental-item-exchange'); ?>
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="ep-exchange-list <?php echo esc_attr('ep-layout-' . $atts['layout']); ?>" id="ep-exchange-list">
                <?php echo $this->get_exchange_list_html($atts); ?>
            </div>
            
            <div class="ep-load-more-container">
                <button type="button" id="ep-load-more" class="ep-btn ep-btn-outline" data-page="1" data-limit="<?php echo $atts['limit']; ?>">
                    <?php _e('Load More', 'environmental-item-exchange'); ?>
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get exchange list HTML
     */
    private function get_exchange_list_html($atts) {
        $args = array(
            'post_type' => 'item_exchange',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['limit']),
            'meta_query' => array(
                array(
                    'key' => '_exchange_status',
                    'value' => 'active',
                    'compare' => '='
                )
            )
        );
        
        if (!empty($atts['type']) && $atts['type'] !== 'all') {
            $args['meta_query'][] = array(
                'key' => '_exchange_type',
                'value' => $atts['type'],
                'compare' => '='
            );
        }
        
        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'exchange_type',
                    'field' => 'term_id',
                    'terms' => intval($atts['category'])
                )
            );
        }
        
        $exchanges = get_posts($args);
        
        if (empty($exchanges)) {
            return '<div class="ep-no-results">' . __('No exchanges found.', 'environmental-item-exchange') . '</div>';
        }
        
        $html = '';
        foreach ($exchanges as $exchange) {
            $html .= $this->render_exchange_card($exchange);
        }
        
        return $html;
    }
    
    /**
     * Render individual exchange card
     */
    private function render_exchange_card($exchange) {
        $meta = get_post_meta($exchange->ID);
        $exchange_type = $meta['_exchange_type'][0] ?? 'exchange';
        $location = $meta['_exchange_location'][0] ?? '';
        $condition = $meta['_item_condition'][0] ?? '';
        $value = $meta['_item_estimated_value'][0] ?? '';
        $author = get_userdata($exchange->post_author);
        $author_rating = get_user_meta($exchange->post_author, '_exchange_rating', true) ?: 0;
        
        // Get featured image
        $image_url = get_the_post_thumbnail_url($exchange->ID, 'medium');
        if (!$image_url) {
            $image_url = plugin_dir_url(__FILE__) . '../assets/images/placeholder.jpg';
        }
        
        ob_start();
        ?>
        <div class="ep-exchange-card" data-id="<?php echo $exchange->ID; ?>">
            <div class="ep-card-image">
                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($exchange->post_title); ?>">
                <div class="ep-card-type ep-type-<?php echo esc_attr($exchange_type); ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $exchange_type)); ?>
                </div>
            </div>
            
            <div class="ep-card-content">
                <h3 class="ep-card-title">
                    <a href="<?php echo get_permalink($exchange->ID); ?>"><?php echo esc_html($exchange->post_title); ?></a>
                </h3>
                
                <div class="ep-card-meta">
                    <div class="ep-card-author">
                        <span class="ep-author-name"><?php echo esc_html($author->display_name); ?></span>
                        <div class="ep-author-rating">
                            <?php echo $this->render_star_rating($author_rating); ?>
                            <span class="ep-rating-text">(<?php echo number_format($author_rating, 1); ?>)</span>
                        </div>
                    </div>
                    
                    <?php if ($location): ?>
                    <div class="ep-card-location">
                        <span class="ep-icon-location"></span>
                        <?php echo esc_html($location); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="ep-card-details">
                        <?php if ($condition): ?>
                        <span class="ep-condition"><?php echo ucfirst(str_replace('_', ' ', $condition)); ?></span>
                        <?php endif; ?>
                        
                        <?php if ($value && $value > 0): ?>
                        <span class="ep-value">$<?php echo number_format($value, 2); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="ep-card-excerpt">
                    <?php echo wp_trim_words($exchange->post_content, 20); ?>
                </div>
                
                <div class="ep-card-actions">
                    <a href="<?php echo get_permalink($exchange->ID); ?>" class="ep-btn ep-btn-primary ep-btn-small">
                        <?php _e('View Details', 'environmental-item-exchange'); ?>
                    </a>
                    
                    <?php if (is_user_logged_in() && get_current_user_id() !== $exchange->post_author): ?>
                    <button type="button" class="ep-btn ep-btn-secondary ep-btn-small ep-contact-btn" 
                            data-exchange-id="<?php echo $exchange->ID; ?>">
                        <?php _e('Contact', 'environmental-item-exchange'); ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render star rating
     */
    private function render_star_rating($rating, $max = 5) {
        $rating = floatval($rating);
        $full_stars = floor($rating);
        $half_star = ($rating - $full_stars) >= 0.5;
        $empty_stars = $max - $full_stars - ($half_star ? 1 : 0);
        
        $html = '<div class="ep-star-rating">';
        
        // Full stars
        for ($i = 0; $i < $full_stars; $i++) {
            $html .= '<span class="ep-star ep-star-full">★</span>';
        }
        
        // Half star
        if ($half_star) {
            $html .= '<span class="ep-star ep-star-half">★</span>';
        }
        
        // Empty stars
        for ($i = 0; $i < $empty_stars; $i++) {
            $html .= '<span class="ep-star ep-star-empty">☆</span>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render my exchanges shortcode
     */
    public function render_my_exchanges($atts) {
        if (!is_user_logged_in()) {
            return '<div class="ep-login-required">' . 
                   __('Please log in to view your exchanges.', 'environmental-item-exchange') . 
                   ' <a href="' . wp_login_url(get_permalink()) . '">' . __('Log In', 'environmental-item-exchange') . '</a>' .
                   '</div>';
        }
        
        $atts = shortcode_atts(array(
            'status' => 'all', // all, active, completed, paused
            'limit' => 10
        ), $atts);
        
        $user_id = get_current_user_id();
        
        $args = array(
            'post_type' => 'item_exchange',
            'post_status' => 'publish',
            'author' => $user_id,
            'posts_per_page' => intval($atts['limit']),
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        if ($atts['status'] !== 'all') {
            $args['meta_query'] = array(
                array(
                    'key' => '_exchange_status',
                    'value' => $atts['status'],
                    'compare' => '='
                )
            );
        }
        
        $my_exchanges = get_posts($args);
        
        ob_start();
        ?>
        <div class="ep-my-exchanges">
            <div class="ep-my-exchanges-header">
                <h3><?php _e('My Exchanges', 'environmental-item-exchange'); ?></h3>
                <div class="ep-status-filter">
                    <select id="ep-my-status-filter">
                        <option value="all"><?php _e('All Status', 'environmental-item-exchange'); ?></option>
                        <option value="active"><?php _e('Active', 'environmental-item-exchange'); ?></option>
                        <option value="completed"><?php _e('Completed', 'environmental-item-exchange'); ?></option>
                        <option value="paused"><?php _e('Paused', 'environmental-item-exchange'); ?></option>
                    </select>
                </div>
            </div>
            
            <div class="ep-my-exchanges-list">
                <?php if (empty($my_exchanges)): ?>
                <div class="ep-no-results">
                    <?php _e('You haven\'t created any exchanges yet.', 'environmental-item-exchange'); ?>
                    <a href="#" class="ep-btn ep-btn-primary"><?php _e('Create Your First Exchange', 'environmental-item-exchange'); ?></a>
                </div>
                <?php else: ?>
                <?php foreach ($my_exchanges as $exchange): ?>
                    <?php echo $this->render_my_exchange_item($exchange); ?>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render individual my exchange item
     */
    private function render_my_exchange_item($exchange) {
        $meta = get_post_meta($exchange->ID);
        $status = $meta['_exchange_status'][0] ?? 'active';
        $type = $meta['_exchange_type'][0] ?? 'exchange';
        $matches_count = $this->get_matches_count($exchange->ID);
        $messages_count = $this->get_messages_count($exchange->ID);
        
        ob_start();
        ?>
        <div class="ep-my-exchange-item" data-id="<?php echo $exchange->ID; ?>">
            <div class="ep-item-header">
                <h4><?php echo esc_html($exchange->post_title); ?></h4>
                <div class="ep-item-status ep-status-<?php echo esc_attr($status); ?>">
                    <?php echo ucfirst($status); ?>
                </div>
            </div>
            
            <div class="ep-item-meta">
                <span class="ep-item-type ep-type-<?php echo esc_attr($type); ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $type)); ?>
                </span>
                <span class="ep-item-date">
                    <?php echo human_time_diff(strtotime($exchange->post_date), current_time('timestamp')) . ' ago'; ?>
                </span>
            </div>
            
            <div class="ep-item-stats">
                <div class="ep-stat">
                    <span class="ep-stat-number"><?php echo $matches_count; ?></span>
                    <span class="ep-stat-label"><?php _e('Matches', 'environmental-item-exchange'); ?></span>
                </div>
                <div class="ep-stat">
                    <span class="ep-stat-number"><?php echo $messages_count; ?></span>
                    <span class="ep-stat-label"><?php _e('Messages', 'environmental-item-exchange'); ?></span>
                </div>
            </div>
            
            <div class="ep-item-actions">
                <a href="<?php echo get_permalink($exchange->ID); ?>" class="ep-btn ep-btn-small ep-btn-outline">
                    <?php _e('View', 'environmental-item-exchange'); ?>
                </a>
                <a href="<?php echo get_edit_post_link($exchange->ID); ?>" class="ep-btn ep-btn-small ep-btn-outline">
                    <?php _e('Edit', 'environmental-item-exchange'); ?>
                </a>
                <button type="button" class="ep-btn ep-btn-small ep-btn-secondary ep-view-matches" 
                        data-exchange-id="<?php echo $exchange->ID; ?>">
                    <?php _e('Matches', 'environmental-item-exchange'); ?>
                </button>
                <div class="ep-status-actions">
                    <?php if ($status === 'active'): ?>
                    <button type="button" class="ep-btn ep-btn-small ep-btn-outline ep-pause-exchange" 
                            data-exchange-id="<?php echo $exchange->ID; ?>">
                        <?php _e('Pause', 'environmental-item-exchange'); ?>
                    </button>
                    <?php elseif ($status === 'paused'): ?>
                    <button type="button" class="ep-btn ep-btn-small ep-btn-primary ep-activate-exchange" 
                            data-exchange-id="<?php echo $exchange->ID; ?>">
                        <?php _e('Activate', 'environmental-item-exchange'); ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get matches count for exchange
     */
    private function get_matches_count($exchange_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'exchange_matches';
        
        return $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$table} 
            WHERE (post_id_1 = %d OR post_id_2 = %d) 
            AND compatibility_score > 0.3
        ", $exchange_id, $exchange_id));
    }
    
    /**
     * Get messages count for exchange
     */
    private function get_messages_count($exchange_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'exchange_messages';
        
        return $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$table} 
            WHERE exchange_id = %d
        ", $exchange_id));
    }
    
    /**
     * Render match finder shortcode
     */
    public function render_match_finder($atts) {
        if (!is_user_logged_in()) {
            return '<div class="ep-login-required">' . 
                   __('Please log in to find matches.', 'environmental-item-exchange') . 
                   ' <a href="' . wp_login_url(get_permalink()) . '">' . __('Log In', 'environmental-item-exchange') . '</a>' .
                   '</div>';
        }
        
        $atts = shortcode_atts(array(
            'exchange_id' => '',
            'auto_load' => 'true'
        ), $atts);
        
        ob_start();
        ?>
        <div class="ep-match-finder">
            <div class="ep-match-finder-header">
                <h3><?php _e('Find Matches', 'environmental-item-exchange'); ?></h3>
                
                <?php if (empty($atts['exchange_id'])): ?>
                <div class="ep-exchange-selector">
                    <label for="ep-select-exchange"><?php _e('Select your exchange:', 'environmental-item-exchange'); ?></label>
                    <select id="ep-select-exchange">
                        <option value=""><?php _e('Choose an exchange...', 'environmental-item-exchange'); ?></option>
                        <?php 
                        $user_exchanges = get_posts(array(
                            'post_type' => 'item_exchange',
                            'author' => get_current_user_id(),
                            'posts_per_page' => -1,
                            'meta_query' => array(
                                array(
                                    'key' => '_exchange_status',
                                    'value' => 'active',
                                    'compare' => '='
                                )
                            )
                        ));
                        
                        foreach ($user_exchanges as $exchange): ?>
                        <option value="<?php echo $exchange->ID; ?>"><?php echo esc_html($exchange->post_title); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="ep-match-results" id="ep-match-results">
                <?php if (!empty($atts['exchange_id']) && $atts['auto_load'] === 'true'): ?>
                    <?php echo $this->get_matches_html($atts['exchange_id']); ?>
                <?php else: ?>
                <div class="ep-match-placeholder">
                    <?php _e('Select an exchange to see potential matches.', 'environmental-item-exchange'); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get matches HTML for specific exchange
     */
    private function get_matches_html($exchange_id) {
        $matches = $this->matching_engine->find_matches($exchange_id, 10);
        
        if (empty($matches)) {
            return '<div class="ep-no-matches">' . __('No matches found for this exchange.', 'environmental-item-exchange') . '</div>';
        }
        
        $html = '<div class="ep-matches-grid">';
        
        foreach ($matches as $match) {
            $match_post = $match['post'];
            $score = $match['score'];
            $reasons = $match['reasons'];
            
            $html .= '<div class="ep-match-card" data-match-id="' . $match_post->ID . '">';
            $html .= '<div class="ep-match-score">';
            $html .= '<div class="ep-score-circle" data-score="' . round($score * 100) . '">';
            $html .= '<span>' . round($score * 100) . '%</span>';
            $html .= '</div>';
            $html .= '<div class="ep-compatibility">' . __('Match', 'environmental-item-exchange') . '</div>';
            $html .= '</div>';
            
            $html .= '<div class="ep-match-content">';
            $html .= '<h4><a href="' . get_permalink($match_post->ID) . '">' . esc_html($match_post->post_title) . '</a></h4>';
            
            $author = get_userdata($match_post->post_author);
            $html .= '<div class="ep-match-author">' . esc_html($author->display_name) . '</div>';
            
            $html .= '<div class="ep-match-reasons">';
            foreach ($reasons as $reason) {
                $html .= '<span class="ep-reason-tag">' . esc_html($reason) . '</span>';
            }
            $html .= '</div>';
            
            $html .= '<div class="ep-match-actions">';
            $html .= '<a href="' . get_permalink($match_post->ID) . '" class="ep-btn ep-btn-small ep-btn-outline">' . __('View', 'environmental-item-exchange') . '</a>';
            $html .= '<button type="button" class="ep-btn ep-btn-small ep-btn-primary ep-contact-match" data-exchange-id="' . $match_post->ID . '">' . __('Contact', 'environmental-item-exchange') . '</button>';
            $html .= '</div>';
            
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render user dashboard shortcode
     */
    public function render_user_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<div class="ep-login-required">' . 
                   __('Please log in to access your dashboard.', 'environmental-item-exchange') . 
                   ' <a href="' . wp_login_url(get_permalink()) . '">' . __('Log In', 'environmental-item-exchange') . '</a>' .
                   '</div>';
        }
        
        $user_id = get_current_user_id();
        $user_stats = $this->get_user_stats($user_id);
        
        ob_start();
        ?>
        <div class="ep-user-dashboard">
            <div class="ep-dashboard-header">
                <h2><?php printf(__('Welcome, %s!', 'environmental-item-exchange'), wp_get_current_user()->display_name); ?></h2>
                <div class="ep-user-stats">
                    <div class="ep-stat-card">
                        <div class="ep-stat-number"><?php echo $user_stats['total_exchanges']; ?></div>
                        <div class="ep-stat-label"><?php _e('Total Exchanges', 'environmental-item-exchange'); ?></div>
                    </div>
                    <div class="ep-stat-card">
                        <div class="ep-stat-number"><?php echo $user_stats['successful_exchanges']; ?></div>
                        <div class="ep-stat-label"><?php _e('Successful', 'environmental-item-exchange'); ?></div>
                    </div>
                    <div class="ep-stat-card">
                        <div class="ep-stat-number"><?php echo number_format($user_stats['rating'], 1); ?></div>
                        <div class="ep-stat-label"><?php _e('Rating', 'environmental-item-exchange'); ?></div>
                    </div>
                    <div class="ep-stat-card">
                        <div class="ep-stat-number"><?php echo $user_stats['eco_points']; ?></div>
                        <div class="ep-stat-label"><?php _e('Eco Points', 'environmental-item-exchange'); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="ep-dashboard-content">
                <div class="ep-dashboard-section">
                    <h3><?php _e('Recent Activity', 'environmental-item-exchange'); ?></h3>
                    <div class="ep-recent-activity">
                        <?php echo $this->get_recent_activity_html($user_id); ?>
                    </div>
                </div>
                
                <div class="ep-dashboard-section">
                    <h3><?php _e('Pending Matches', 'environmental-item-exchange'); ?></h3>
                    <div class="ep-pending-matches">
                        <?php echo $this->get_pending_matches_html($user_id); ?>
                    </div>
                </div>
                
                <div class="ep-dashboard-section">
                    <h3><?php _e('Environmental Impact', 'environmental-item-exchange'); ?></h3>
                    <div class="ep-environmental-impact">
                        <div class="ep-impact-stats">
                            <div class="ep-impact-item">
                                <span class="ep-impact-number"><?php echo number_format($user_stats['co2_saved'], 1); ?></span>
                                <span class="ep-impact-unit">kg CO₂</span>
                                <span class="ep-impact-label"><?php _e('Saved', 'environmental-item-exchange'); ?></span>
                            </div>
                            <div class="ep-impact-item">
                                <span class="ep-impact-number"><?php echo $user_stats['items_diverted']; ?></span>
                                <span class="ep-impact-unit"><?php _e('items', 'environmental-item-exchange'); ?></span>
                                <span class="ep-impact-label"><?php _e('From Landfill', 'environmental-item-exchange'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="ep-dashboard-actions">
                <a href="#" class="ep-btn ep-btn-primary"><?php _e('Create New Exchange', 'environmental-item-exchange'); ?></a>
                <a href="#" class="ep-btn ep-btn-secondary"><?php _e('Browse Exchanges', 'environmental-item-exchange'); ?></a>
                <a href="#" class="ep-btn ep-btn-outline"><?php _e('View Profile', 'environmental-item-exchange'); ?></a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get user statistics
     */
    private function get_user_stats($user_id) {
        global $wpdb;
        
        // Get total exchanges
        $total_exchanges = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type = 'item_exchange' AND post_author = %d AND post_status = 'publish'
        ", $user_id));
        
        // Get successful exchanges
        $successful_exchanges = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'item_exchange' AND p.post_author = %d 
            AND pm.meta_key = '_exchange_status' AND pm.meta_value = 'completed'
        ", $user_id));
        
        // Get user rating and eco points
        $rating = get_user_meta($user_id, '_exchange_rating', true) ?: 0;
        $eco_points = get_user_meta($user_id, '_eco_points', true) ?: 0;
        
        // Calculate environmental impact
        $co2_saved = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(CAST(pm.meta_value AS DECIMAL(10,2))) 
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_author = %d AND pm.meta_key = '_carbon_footprint_saved'
        ", $user_id)) ?: 0;
        
        return array(
            'total_exchanges' => intval($total_exchanges),
            'successful_exchanges' => intval($successful_exchanges),
            'rating' => floatval($rating),
            'eco_points' => intval($eco_points),
            'co2_saved' => floatval($co2_saved),
            'items_diverted' => intval($successful_exchanges) // Simplified calculation
        );
    }
    
    /**
     * Get recent activity HTML
     */
    private function get_recent_activity_html($user_id) {
        // Implementation for recent activity
        return '<div class="ep-activity-placeholder">' . __('No recent activity', 'environmental-item-exchange') . '</div>';
    }
      /**
     * Get pending matches HTML
     */
    private function get_pending_matches_html($user_id) {
        // Implementation for pending matches
        return '<div class="ep-matches-placeholder">' . __('No pending matches', 'environmental-item-exchange') . '</div>';
    }
    
    // ===== AJAX HANDLERS =====
    
    /**
     * Handle exchange search AJAX
     */
    public function ajax_search_exchanges() {
        check_ajax_referer('ep_frontend_nonce', 'nonce');
        
        $search_term = sanitize_text_field($_POST['search_term']);
        $filters = array(
            'type' => sanitize_text_field($_POST['type'] ?? ''),
            'category' => intval($_POST['category'] ?? 0),
            'location' => sanitize_text_field($_POST['location'] ?? ''),
            'radius' => intval($_POST['radius'] ?? 10)
        );
        $page = intval($_POST['page'] ?? 1);
        $limit = intval($_POST['limit'] ?? 12);
        
        $args = array(
            'post_type' => 'item_exchange',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'paged' => $page,
            's' => $search_term,
            'meta_query' => array(
                array(
                    'key' => '_exchange_status',
                    'value' => 'active',
                    'compare' => '='
                )
            )
        );
        
        // Add type filter
        if (!empty($filters['type'])) {
            $args['meta_query'][] = array(
                'key' => '_exchange_type',
                'value' => $filters['type'],
                'compare' => '='
            );
        }
        
        // Add category filter
        if (!empty($filters['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'exchange_type',
                    'field' => 'term_id',
                    'terms' => $filters['category']
                )
            );
        }
        
        $query = new WP_Query($args);
        $exchanges = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $exchanges[] = $this->format_exchange_for_ajax(get_post());
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success(array(
            'exchanges' => $exchanges,
            'has_more' => $query->max_num_pages > $page,
            'total' => $query->found_posts
        ));
    }
    
    /**
     * Handle save exchange AJAX
     */
    public function ajax_save_exchange() {
        check_ajax_referer('ep_frontend_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Please log in to save exchanges.', 'environmental-item-exchange'));
        }
        
        $exchange_id = intval($_POST['exchange_id']);
        $user_id = get_current_user_id();
        
        // Check if exchange exists
        if (!get_post($exchange_id) || get_post_type($exchange_id) !== 'item_exchange') {
            wp_send_json_error(__('Invalid exchange.', 'environmental-item-exchange'));
        }
        
        // Check if already saved
        $saved_exchanges = get_user_meta($user_id, '_saved_exchanges', true) ?: array();
        
        if (in_array($exchange_id, $saved_exchanges)) {
            // Remove from saved
            $saved_exchanges = array_diff($saved_exchanges, array($exchange_id));
            $message = __('Exchange removed from saved items.', 'environmental-item-exchange');
            $action = 'removed';
        } else {
            // Add to saved
            $saved_exchanges[] = $exchange_id;
            $message = __('Exchange saved successfully.', 'environmental-item-exchange');
            $action = 'saved';
        }
        
        update_user_meta($user_id, '_saved_exchanges', $saved_exchanges);
        
        wp_send_json_success(array(
            'message' => $message,
            'action' => $action
        ));
    }
    
    /**
     * Handle contact exchange owner AJAX
     */
    public function ajax_contact_exchange_owner() {
        check_ajax_referer('ep_frontend_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Please log in to contact exchange owners.', 'environmental-item-exchange'));
        }
        
        $exchange_id = intval($_POST['exchange_id']);
        $message = sanitize_textarea_field($_POST['message']);
        $sender_id = get_current_user_id();
        
        // Validate inputs
        if (empty($message)) {
            wp_send_json_error(__('Message cannot be empty.', 'environmental-item-exchange'));
        }
        
        $exchange = get_post($exchange_id);
        if (!$exchange || $exchange->post_type !== 'item_exchange') {
            wp_send_json_error(__('Invalid exchange.', 'environmental-item-exchange'));
        }
        
        $owner_id = $exchange->post_author;
        
        // Don't allow messaging yourself
        if ($sender_id == $owner_id) {
            wp_send_json_error(__('You cannot message yourself.', 'environmental-item-exchange'));
        }
        
        // Create conversation or add to existing one
        global $wpdb;
        
        // Check if conversation exists
        $conversation_id = $wpdb->get_var($wpdb->prepare("
            SELECT conversation_id FROM {$wpdb->prefix}ep_exchange_conversations 
            WHERE exchange_id = %d AND ((user1_id = %d AND user2_id = %d) OR (user1_id = %d AND user2_id = %d))
        ", $exchange_id, $sender_id, $owner_id, $owner_id, $sender_id));
        
        if (!$conversation_id) {
            // Create new conversation
            $wpdb->insert(
                $wpdb->prefix . 'ep_exchange_conversations',
                array(
                    'exchange_id' => $exchange_id,
                    'user1_id' => $sender_id,
                    'user2_id' => $owner_id,
                    'created_at' => current_time('mysql')
                )
            );
            $conversation_id = $wpdb->insert_id;
        }
        
        // Add message to conversation
        $wpdb->insert(
            $wpdb->prefix . 'ep_exchange_messages',
            array(
                'conversation_id' => $conversation_id,
                'sender_id' => $sender_id,
                'message' => $message,
                'created_at' => current_time('mysql')
            )
        );
        
        // Send notification to owner
        do_action('ep_new_exchange_message', $conversation_id, $sender_id, $owner_id, $exchange_id);
        
        wp_send_json_success(__('Message sent successfully!', 'environmental-item-exchange'));
    }
    
    /**
     * Handle rate exchange AJAX
     */
    public function ajax_rate_exchange() {
        check_ajax_referer('ep_frontend_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Please log in to rate exchanges.', 'environmental-item-exchange'));
        }
        
        $exchange_id = intval($_POST['exchange_id']);
        $rating = intval($_POST['rating']);
        $review = sanitize_textarea_field($_POST['review'] ?? '');
        $user_id = get_current_user_id();
        
        // Validate rating
        if ($rating < 1 || $rating > 5) {
            wp_send_json_error(__('Invalid rating. Please select 1-5 stars.', 'environmental-item-exchange'));
        }
        
        // Check if exchange exists and user participated
        $exchange = get_post($exchange_id);
        if (!$exchange || $exchange->post_type !== 'item_exchange') {
            wp_send_json_error(__('Invalid exchange.', 'environmental-item-exchange'));
        }
        
        // Check if user already rated this exchange
        global $wpdb;
        $existing_rating = $wpdb->get_var($wpdb->prepare("
            SELECT rating_id FROM {$wpdb->prefix}ep_exchange_ratings 
            WHERE exchange_id = %d AND rater_id = %d
        ", $exchange_id, $user_id));
        
        if ($existing_rating) {
            wp_send_json_error(__('You have already rated this exchange.', 'environmental-item-exchange'));
        }
        
        // Add rating
        $wpdb->insert(
            $wpdb->prefix . 'ep_exchange_ratings',
            array(
                'exchange_id' => $exchange_id,
                'rater_id' => $user_id,
                'rated_user_id' => $exchange->post_author,
                'rating' => $rating,
                'review' => $review,
                'created_at' => current_time('mysql')
            )
        );
        
        // Update user's average rating
        $this->update_user_average_rating($exchange->post_author);
        
        wp_send_json_success(__('Rating submitted successfully!', 'environmental-item-exchange'));
    }
    
    /**
     * Handle get user dashboard data AJAX
     */
    public function ajax_get_user_dashboard_data() {
        check_ajax_referer('ep_frontend_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Unauthorized access.', 'environmental-item-exchange'));
        }
        
        $user_id = get_current_user_id();
        $data_type = sanitize_text_field($_POST['data_type']);
        
        switch ($data_type) {
            case 'stats':
                $data = $this->get_user_stats($user_id);
                break;
                
            case 'recent_activity':
                $data = $this->get_user_recent_activity($user_id);
                break;
                
            case 'pending_matches':
                $data = $this->get_user_pending_matches($user_id);
                break;
                
            case 'messages':
                $data = $this->get_user_messages($user_id);
                break;
                
            default:
                wp_send_json_error(__('Invalid data type.', 'environmental-item-exchange'));
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * Format exchange data for AJAX response
     */
    private function format_exchange_for_ajax($post) {
        $exchange_type = get_post_meta($post->ID, '_exchange_type', true);
        $item_condition = get_post_meta($post->ID, '_item_condition', true);
        $location = get_post_meta($post->ID, '_exchange_location', true);
        $images = get_post_meta($post->ID, '_exchange_images', true) ?: array();
        
        return array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'excerpt' => wp_trim_words($post->post_content, 20),
            'type' => $exchange_type,
            'condition' => $item_condition,
            'location' => $location,
            'date' => get_the_date('', $post),
            'author' => get_the_author_meta('display_name', $post->post_author),
            'permalink' => get_permalink($post->ID),
            'thumbnail' => get_the_post_thumbnail_url($post->ID, 'medium') ?: '',
            'images' => $images
        );
    }
    
    /**
     * Update user average rating
     */
    private function update_user_average_rating($user_id) {
        global $wpdb;
        
        $average = $wpdb->get_var($wpdb->prepare("
            SELECT AVG(rating) FROM {$wpdb->prefix}ep_exchange_ratings 
            WHERE rated_user_id = %d
        ", $user_id));
        
        if ($average !== null) {
            update_user_meta($user_id, '_exchange_rating', round($average, 2));
        }
    }
    
    /**
     * Get user recent activity
     */
    private function get_user_recent_activity($user_id) {
        // Placeholder implementation
        return array(
            'activities' => array(),
            'total' => 0
        );
    }
    
    /**
     * Get user pending matches
     */
    private function get_user_pending_matches($user_id) {
        // Placeholder implementation
        return array(
            'matches' => array(),
            'total' => 0
        );
    }
    
    /**
     * Get user messages
     */
    private function get_user_messages($user_id) {
        // Placeholder implementation
        return array(
            'messages' => array(),
            'unread_count' => 0
        );
    }
