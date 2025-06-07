<?php
/**
 * Advanced Search Widget
 *
 * @package Environmental_Advanced_Search
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAS_Search_Widget extends WP_Widget {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'eas_search_widget',
            __('Environmental Advanced Search', 'environmental-advanced-search'),
            array(
                'description' => __('Advanced search widget with filters and suggestions', 'environmental-advanced-search'),
                'classname' => 'eas-search-widget'
            )
        );
    }
    
    /**
     * Widget output
     *
     * @param array $args Widget arguments
     * @param array $instance Widget instance
     */
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        $this->render_search_form($instance);
        
        echo $args['after_widget'];
    }
    
    /**
     * Widget form in admin
     *
     * @param array $instance Widget instance
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Search', 'environmental-advanced-search');
        $show_filters = !empty($instance['show_filters']) ? $instance['show_filters'] : false;
        $show_suggestions = !empty($instance['show_suggestions']) ? $instance['show_suggestions'] : true;
        $show_location = !empty($instance['show_location']) ? $instance['show_location'] : false;
        $post_types = !empty($instance['post_types']) ? $instance['post_types'] : array('post', 'page');
        $placeholder = !empty($instance['placeholder']) ? $instance['placeholder'] : __('Search...', 'environmental-advanced-search');
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'environmental-advanced-search'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('placeholder'); ?>"><?php _e('Placeholder Text:', 'environmental-advanced-search'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('placeholder'); ?>" name="<?php echo $this->get_field_name('placeholder'); ?>" type="text" value="<?php echo esc_attr($placeholder); ?>">
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_filters); ?> id="<?php echo $this->get_field_id('show_filters'); ?>" name="<?php echo $this->get_field_name('show_filters'); ?>" />
            <label for="<?php echo $this->get_field_id('show_filters'); ?>"><?php _e('Show Filters', 'environmental-advanced-search'); ?></label>
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_suggestions); ?> id="<?php echo $this->get_field_id('show_suggestions'); ?>" name="<?php echo $this->get_field_name('show_suggestions'); ?>" />
            <label for="<?php echo $this->get_field_id('show_suggestions'); ?>"><?php _e('Show Search Suggestions', 'environmental-advanced-search'); ?></label>
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_location); ?> id="<?php echo $this->get_field_id('show_location'); ?>" name="<?php echo $this->get_field_name('show_location'); ?>" />
            <label for="<?php echo $this->get_field_id('show_location'); ?>"><?php _e('Show Location Filter', 'environmental-advanced-search'); ?></label>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('post_types'); ?>"><?php _e('Post Types to Search:', 'environmental-advanced-search'); ?></label>
            <select multiple class="widefat" id="<?php echo $this->get_field_id('post_types'); ?>" name="<?php echo $this->get_field_name('post_types'); ?>[]">
                <?php
                $available_post_types = get_post_types(array('public' => true), 'objects');
                foreach ($available_post_types as $post_type) {
                    $selected = in_array($post_type->name, $post_types) ? 'selected' : '';
                    echo '<option value="' . esc_attr($post_type->name) . '" ' . $selected . '>' . esc_html($post_type->label) . '</option>';
                }
                ?>
            </select>
        </p>
        <?php
    }
    
    /**
     * Update widget settings
     *
     * @param array $new_instance New settings
     * @param array $old_instance Old settings
     * @return array Updated settings
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['placeholder'] = (!empty($new_instance['placeholder'])) ? sanitize_text_field($new_instance['placeholder']) : '';
        $instance['show_filters'] = !empty($new_instance['show_filters']);
        $instance['show_suggestions'] = !empty($new_instance['show_suggestions']);
        $instance['show_location'] = !empty($new_instance['show_location']);
        $instance['post_types'] = (!empty($new_instance['post_types'])) ? array_map('sanitize_text_field', $new_instance['post_types']) : array();
        
        return $instance;
    }
    
    /**
     * Render search form
     *
     * @param array $instance Widget instance
     */
    private function render_search_form($instance) {
        $widget_id = $this->id;
        $show_filters = !empty($instance['show_filters']);
        $show_suggestions = !empty($instance['show_suggestions']);
        $show_location = !empty($instance['show_location']);
        $post_types = !empty($instance['post_types']) ? $instance['post_types'] : array('post', 'page');
        $placeholder = !empty($instance['placeholder']) ? $instance['placeholder'] : __('Search...', 'environmental-advanced-search');
        
        // Get current search query and filters
        $current_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $current_filters = $this->get_current_filters();
        
        ?>
        <div class="eas-search-widget" id="eas-search-widget-<?php echo esc_attr($widget_id); ?>">
            <form class="eas-search-form" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                
                <!-- Search Input -->
                <div class="eas-search-input-container">
                    <input type="text" 
                           name="s" 
                           class="eas-search-input" 
                           value="<?php echo esc_attr($current_query); ?>" 
                           placeholder="<?php echo esc_attr($placeholder); ?>"
                           autocomplete="off" />
                    <button type="submit" class="eas-search-submit">
                        <span class="screen-reader-text"><?php _e('Search', 'environmental-advanced-search'); ?></span>
                        <svg class="eas-search-icon" width="20" height="20" viewBox="0 0 20 20">
                            <path d="M14.386 14.386l4.0877 4.0877-4.0877-4.0877c-2.9418 2.9419-7.7115 2.9419-10.6533 0-2.9419-2.9418-2.9419-7.7115 0-10.6533 2.9418-2.9419 7.7115-2.9419 10.6533 0 2.9419 2.9418 2.9419 7.7115 0 10.6533z" stroke="currentColor" fill="none" fill-rule="evenodd" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </button>
                    
                    <?php if ($show_suggestions): ?>
                    <div class="eas-suggestions-dropdown" id="eas-suggestions-<?php echo esc_attr($widget_id); ?>">
                        <!-- Suggestions will be populated via AJAX -->
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Hidden fields for post types -->
                <?php foreach ($post_types as $post_type): ?>
                <input type="hidden" name="post_type[]" value="<?php echo esc_attr($post_type); ?>" />
                <?php endforeach; ?>
                
                <?php if ($show_filters): ?>
                <!-- Filters Section -->
                <div class="eas-filters-container">
                    <button type="button" class="eas-filters-toggle">
                        <?php _e('Filters', 'environmental-advanced-search'); ?>
                        <span class="eas-filter-count" style="display: none;"></span>
                    </button>
                    
                    <div class="eas-filters-content">
                        <?php $this->render_filters($current_filters, $show_location); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Results Container (for AJAX) -->
                <div class="eas-search-results" id="eas-results-<?php echo esc_attr($widget_id); ?>" style="display: none;">
                    <!-- Results will be populated via AJAX -->
                </div>
                
            </form>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var widgetId = '<?php echo esc_js($widget_id); ?>';
            var $widget = $('#eas-search-widget-' + widgetId);
            var $form = $widget.find('.eas-search-form');
            var $input = $widget.find('.eas-search-input');
            var $results = $widget.find('.eas-search-results');
            var $suggestions = $widget.find('.eas-suggestions-dropdown');
            var searchTimeout;
            
            // Search suggestions
            <?php if ($show_suggestions): ?>
            $input.on('input', function() {
                var query = $(this).val();
                
                clearTimeout(searchTimeout);
                
                if (query.length >= 2) {
                    searchTimeout = setTimeout(function() {
                        getSuggestions(query);
                    }, 300);
                } else {
                    $suggestions.hide();
                }
            });
            
            function getSuggestions(query) {
                $.ajax({
                    url: easAjax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'eas_get_suggestions',
                        query: query,
                        limit: 5,
                        nonce: easAjax.search_nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.suggestions.length > 0) {
                            var html = '';
                            $.each(response.data.suggestions, function(index, suggestion) {
                                html += '<div class="eas-suggestion-item" data-query="' + suggestion.text + '">';
                                html += '<span class="suggestion-text">' + suggestion.text + '</span>';
                                if (suggestion.type === 'popular') {
                                    html += '<span class="suggestion-type">Popular</span>';
                                }
                                html += '</div>';
                            });
                            $suggestions.html(html).show();
                        } else {
                            $suggestions.hide();
                        }
                    }
                });
            }
            
            // Handle suggestion clicks
            $suggestions.on('click', '.eas-suggestion-item', function() {
                var query = $(this).data('query');
                $input.val(query);
                $suggestions.hide();
                $form.submit();
            });
            
            // Hide suggestions when clicking outside
            $(document).on('click', function(e) {
                if (!$widget.is(e.target) && $widget.has(e.target).length === 0) {
                    $suggestions.hide();
                }
            });
            <?php endif; ?>
            
            <?php if ($show_filters): ?>
            // Filter toggle
            $widget.find('.eas-filters-toggle').on('click', function() {
                $widget.find('.eas-filters-content').toggle();
                $(this).toggleClass('active');
            });
            
            // Update filter count
            function updateFilterCount() {
                var count = 0;
                $widget.find('.eas-filters-content input:checked, .eas-filters-content select').each(function() {
                    if ($(this).val() && $(this).val() !== '') {
                        count++;
                    }
                });
                
                var $counter = $widget.find('.eas-filter-count');
                if (count > 0) {
                    $counter.text(count).show();
                } else {
                    $counter.hide();
                }
            }
            
            // Monitor filter changes
            $widget.find('.eas-filters-content').on('change', 'input, select', updateFilterCount);
            updateFilterCount(); // Initial count
            <?php endif; ?>
        });
        </script>
        <?php
    }
    
    /**
     * Render search filters
     *
     * @param array $current_filters Current filter values
     * @param bool $show_location Whether to show location filter
     */
    private function render_filters($current_filters, $show_location = false) {
        $faceted_search = new EAS_Faceted_Search();
        $available_facets = $faceted_search->get_available_facets();
        
        ?>
        <div class="eas-filters-grid">
            
            <!-- Category Filter -->
            <?php if (in_array('category', $available_facets)): ?>
            <div class="eas-filter-group">
                <h4><?php _e('Categories', 'environmental-advanced-search'); ?></h4>
                <select name="category" class="eas-filter-select">
                    <option value=""><?php _e('All Categories', 'environmental-advanced-search'); ?></option>
                    <?php
                    $categories = get_categories(array('hide_empty' => true));
                    foreach ($categories as $category) {
                        $selected = selected($current_filters['category'] ?? '', $category->slug, false);
                        echo '<option value="' . esc_attr($category->slug) . '" ' . $selected . '>' . esc_html($category->name) . ' (' . $category->count . ')</option>';
                    }
                    ?>
                </select>
            </div>
            <?php endif; ?>
            
            <!-- Date Filter -->
            <div class="eas-filter-group">
                <h4><?php _e('Date Range', 'environmental-advanced-search'); ?></h4>
                <select name="date_range" class="eas-filter-select">
                    <option value=""><?php _e('Any Time', 'environmental-advanced-search'); ?></option>
                    <option value="today" <?php selected($current_filters['date_range'] ?? '', 'today'); ?>><?php _e('Today', 'environmental-advanced-search'); ?></option>
                    <option value="week" <?php selected($current_filters['date_range'] ?? '', 'week'); ?>><?php _e('This Week', 'environmental-advanced-search'); ?></option>
                    <option value="month" <?php selected($current_filters['date_range'] ?? '', 'month'); ?>><?php _e('This Month', 'environmental-advanced-search'); ?></option>
                    <option value="year" <?php selected($current_filters['date_range'] ?? '', 'year'); ?>><?php _e('This Year', 'environmental-advanced-search'); ?></option>
                </select>
            </div>
            
            <!-- Custom Taxonomy Filters -->
            <?php
            $custom_taxonomies = get_taxonomies(array(
                'public' => true,
                '_builtin' => false
            ), 'objects');
            
            foreach ($custom_taxonomies as $taxonomy) {
                $terms = get_terms(array(
                    'taxonomy' => $taxonomy->name,
                    'hide_empty' => true,
                    'number' => 20
                ));
                
                if (!empty($terms) && !is_wp_error($terms)):
                ?>
                <div class="eas-filter-group">
                    <h4><?php echo esc_html($taxonomy->label); ?></h4>
                    <select name="<?php echo esc_attr($taxonomy->name); ?>" class="eas-filter-select">
                        <option value=""><?php printf(__('All %s', 'environmental-advanced-search'), $taxonomy->label); ?></option>
                        <?php foreach ($terms as $term): ?>
                        <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($current_filters[$taxonomy->name] ?? '', $term->slug); ?>>
                            <?php echo esc_html($term->name); ?> (<?php echo $term->count; ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php
                endif;
            }
            ?>
            
            <!-- Location Filter -->
            <?php if ($show_location): ?>
            <div class="eas-filter-group">
                <?php
                $geolocation = new EAS_Geolocation_Search();
                echo $geolocation->get_location_filter_html();
                ?>
            </div>
            <?php endif; ?>
            
        </div>
        
        <!-- Filter Actions -->
        <div class="eas-filter-actions">
            <button type="submit" class="button button-primary"><?php _e('Apply Filters', 'environmental-advanced-search'); ?></button>
            <button type="button" class="button eas-clear-filters"><?php _e('Clear All', 'environmental-advanced-search'); ?></button>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.eas-clear-filters').on('click', function() {
                var $form = $(this).closest('form');
                $form.find('select').val('');
                $form.find('input[type="checkbox"]').prop('checked', false);
                $form.find('input[type="text"]:not(.eas-search-input)').val('');
                $form.find('.eas-filter-count').hide();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Get current filter values from URL
     *
     * @return array Current filters
     */
    private function get_current_filters() {
        return array(
            'category' => isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '',
            'date_range' => isset($_GET['date_range']) ? sanitize_text_field($_GET['date_range']) : '',
            'location' => isset($_GET['location']) ? sanitize_text_field($_GET['location']) : '',
            'radius' => isset($_GET['radius']) ? absint($_GET['radius']) : 25
        );
    }
}

/**
 * Register the widget
 */
function eas_register_search_widget() {
    register_widget('EAS_Search_Widget');
}
add_action('widgets_init', 'eas_register_search_widget');
