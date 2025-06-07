<?php
/**
 * Faceted Search Class
 * 
 * Handles faceted search functionality with dynamic filters
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAS_Faceted_Search {
    
    private $enabled;
    private $available_facets;
    
    public function __construct() {
        $this->enabled = get_option('eas_enable_faceted_search', 'yes') === 'yes';
        $this->init_available_facets();
        
        if ($this->enabled) {
            add_action('wp_footer', array($this, 'add_faceted_search_script'));
            add_shortcode('eas_faceted_search', array($this, 'faceted_search_shortcode'));
        }
    }
    
    /**
     * Initialize available facets
     */
    private function init_available_facets() {
        $this->available_facets = array(
            'post_type' => array(
                'label' => __('Content Type', 'environmental-advanced-search'),
                'type' => 'select',
                'multiple' => true,
                'options' => $this->get_post_type_options()
            ),
            'category' => array(
                'label' => __('Category', 'environmental-advanced-search'),
                'type' => 'hierarchical',
                'multiple' => true,
                'taxonomy' => 'category'
            ),
            'env_category' => array(
                'label' => __('Environmental Category', 'environmental-advanced-search'),
                'type' => 'hierarchical',
                'multiple' => true,
                'taxonomy' => 'env_category'
            ),
            'project_category' => array(
                'label' => __('Project Category', 'environmental-advanced-search'),
                'type' => 'hierarchical',
                'multiple' => true,
                'taxonomy' => 'project_category'
            ),
            'impact_level' => array(
                'label' => __('Impact Level', 'environmental-advanced-search'),
                'type' => 'radio',
                'options' => array(
                    'low' => __('Low Impact', 'environmental-advanced-search'),
                    'medium' => __('Medium Impact', 'environmental-advanced-search'),
                    'high' => __('High Impact', 'environmental-advanced-search')
                )
            ),
            'difficulty_level' => array(
                'label' => __('Difficulty Level', 'environmental-advanced-search'),
                'type' => 'radio',
                'options' => array(
                    'beginner' => __('Beginner', 'environmental-advanced-search'),
                    'intermediate' => __('Intermediate', 'environmental-advanced-search'),
                    'advanced' => __('Advanced', 'environmental-advanced-search')
                )
            ),
            'project_status' => array(
                'label' => __('Project Status', 'environmental-advanced-search'),
                'type' => 'checkbox',
                'multiple' => true,
                'options' => array(
                    'planning' => __('Planning', 'environmental-advanced-search'),
                    'active' => __('Active', 'environmental-advanced-search'),
                    'completed' => __('Completed', 'environmental-advanced-search'),
                    'on_hold' => __('On Hold', 'environmental-advanced-search')
                )
            ),
            'date_range' => array(
                'label' => __('Date Range', 'environmental-advanced-search'),
                'type' => 'radio',
                'options' => array(
                    'last_week' => __('Last Week', 'environmental-advanced-search'),
                    'last_month' => __('Last Month', 'environmental-advanced-search'),
                    'last_quarter' => __('Last 3 Months', 'environmental-advanced-search'),
                    'last_year' => __('Last Year', 'environmental-advanced-search')
                )
            ),
            'location' => array(
                'label' => __('Location', 'environmental-advanced-search'),
                'type' => 'location',
                'with_distance' => true
            ),
            'price_range' => array(
                'label' => __('Price Range', 'environmental-advanced-search'),
                'type' => 'range',
                'min' => 0,
                'max' => 1000,
                'step' => 10,
                'currency' => get_woocommerce_currency_symbol()
            ),
            'author' => array(
                'label' => __('Author', 'environmental-advanced-search'),
                'type' => 'select',
                'multiple' => true,
                'options' => $this->get_author_options()
            )
        );
        
        // Allow customization of facets
        $this->available_facets = apply_filters('eas_available_facets', $this->available_facets);
    }
    
    /**
     * Get post type options
     */
    private function get_post_type_options() {
        $post_types = get_post_types(array('public' => true), 'objects');
        $options = array();
        
        foreach ($post_types as $post_type) {
            $options[$post_type->name] = $post_type->labels->name;
        }
        
        return $options;
    }
    
    /**
     * Get author options
     */
    private function get_author_options() {
        $authors = get_users(array(
            'who' => 'authors',
            'has_published_posts' => true,
            'fields' => array('ID', 'display_name')
        ));
        
        $options = array();
        foreach ($authors as $author) {
            $options[$author->ID] = $author->display_name;
        }
        
        return $options;
    }
    
    /**
     * Render faceted search interface
     */
    public function render_faceted_search($args = array()) {
        if (!$this->enabled) {
            return '';
        }
        
        $defaults = array(
            'facets' => array_keys($this->available_facets),
            'layout' => 'sidebar', // sidebar, horizontal, modal
            'ajax' => true,
            'show_count' => true,
            'collapsible' => true,
            'show_clear' => true
        );
        
        $args = wp_parse_args($args, $defaults);
        
        ob_start();
        ?>
        <div class="eas-faceted-search" data-layout="<?php echo esc_attr($args['layout']); ?>" data-ajax="<?php echo $args['ajax'] ? 'true' : 'false'; ?>">
            
            <?php if ($args['show_clear']): ?>
            <div class="eas-facets-header">
                <h3 class="eas-facets-title"><?php _e('Filter Results', 'environmental-advanced-search'); ?></h3>
                <button type="button" class="eas-clear-facets" style="display: none;">
                    <span class="dashicons dashicons-dismiss"></span>
                    <?php _e('Clear All', 'environmental-advanced-search'); ?>
                </button>
            </div>
            <?php endif; ?>
            
            <form class="eas-facets-form" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                <input type="hidden" name="s" value="<?php echo esc_attr(get_search_query()); ?>" />
                
                <?php foreach ($args['facets'] as $facet_key): ?>
                    <?php if (isset($this->available_facets[$facet_key])): ?>
                        <?php $facet = $this->available_facets[$facet_key]; ?>
                        
                        <div class="eas-facet eas-facet-<?php echo esc_attr($facet_key); ?> eas-facet-type-<?php echo esc_attr($facet['type']); ?>"
                             <?php if ($args['collapsible']): ?>data-collapsible="true"<?php endif; ?>>
                            
                            <h4 class="eas-facet-title">
                                <?php echo esc_html($facet['label']); ?>
                                <?php if ($args['collapsible']): ?>
                                    <span class="eas-facet-toggle">
                                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                                    </span>
                                <?php endif; ?>
                            </h4>
                            
                            <div class="eas-facet-content">
                                <?php $this->render_facet_input($facet_key, $facet, $args); ?>
                            </div>
                            
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <?php if (!$args['ajax']): ?>
                    <div class="eas-facets-submit">
                        <button type="submit" class="eas-apply-facets">
                            <?php _e('Apply Filters', 'environmental-advanced-search'); ?>
                        </button>
                    </div>
                <?php endif; ?>
                
            </form>
            
            <div class="eas-active-facets" style="display: none;">
                <h4><?php _e('Active Filters:', 'environmental-advanced-search'); ?></h4>
                <div class="eas-active-facets-list"></div>
            </div>
            
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Render individual facet input
     */
    private function render_facet_input($facet_key, $facet, $args) {
        $current_value = $this->get_current_facet_value($facet_key);
        $facet_counts = $this->get_facet_counts($facet_key);
        
        switch ($facet['type']) {
            case 'select':
                $this->render_select_facet($facet_key, $facet, $current_value, $facet_counts, $args);
                break;
                
            case 'checkbox':
                $this->render_checkbox_facet($facet_key, $facet, $current_value, $facet_counts, $args);
                break;
                
            case 'radio':
                $this->render_radio_facet($facet_key, $facet, $current_value, $facet_counts, $args);
                break;
                
            case 'range':
                $this->render_range_facet($facet_key, $facet, $current_value, $args);
                break;
                
            case 'hierarchical':
                $this->render_hierarchical_facet($facet_key, $facet, $current_value, $facet_counts, $args);
                break;
                
            case 'location':
                $this->render_location_facet($facet_key, $facet, $current_value, $args);
                break;
        }
    }
    
    /**
     * Render select facet
     */
    private function render_select_facet($facet_key, $facet, $current_value, $facet_counts, $args) {
        $multiple = isset($facet['multiple']) && $facet['multiple'];
        $name = $multiple ? $facet_key . '[]' : $facet_key;
        ?>
        <select name="<?php echo esc_attr($name); ?>" 
                class="eas-facet-select" 
                <?php if ($multiple): ?>multiple<?php endif; ?>
                data-facet="<?php echo esc_attr($facet_key); ?>">
            
            <?php if (!$multiple): ?>
                <option value=""><?php _e('All', 'environmental-advanced-search'); ?></option>
            <?php endif; ?>
            
            <?php foreach ($facet['options'] as $value => $label): ?>
                <option value="<?php echo esc_attr($value); ?>"
                        <?php if ($multiple): ?>
                            <?php selected(in_array($value, (array)$current_value)); ?>
                        <?php else: ?>
                            <?php selected($current_value, $value); ?>
                        <?php endif; ?>>
                    <?php echo esc_html($label); ?>
                    <?php if ($args['show_count'] && isset($facet_counts[$value])): ?>
                        (<?php echo intval($facet_counts[$value]); ?>)
                    <?php endif; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }
    
    /**
     * Render checkbox facet
     */
    private function render_checkbox_facet($facet_key, $facet, $current_value, $facet_counts, $args) {
        $current_value = (array)$current_value;
        ?>
        <div class="eas-facet-checkboxes">
            <?php foreach ($facet['options'] as $value => $label): ?>
                <label class="eas-facet-checkbox-label">
                    <input type="checkbox" 
                           name="<?php echo esc_attr($facet_key); ?>[]" 
                           value="<?php echo esc_attr($value); ?>"
                           <?php checked(in_array($value, $current_value)); ?>
                           class="eas-facet-checkbox"
                           data-facet="<?php echo esc_attr($facet_key); ?>" />
                    
                    <span class="eas-facet-checkbox-text">
                        <?php echo esc_html($label); ?>
                        <?php if ($args['show_count'] && isset($facet_counts[$value])): ?>
                            <span class="eas-facet-count">(<?php echo intval($facet_counts[$value]); ?>)</span>
                        <?php endif; ?>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render radio facet
     */
    private function render_radio_facet($facet_key, $facet, $current_value, $facet_counts, $args) {
        ?>
        <div class="eas-facet-radios">
            <label class="eas-facet-radio-label">
                <input type="radio" 
                       name="<?php echo esc_attr($facet_key); ?>" 
                       value=""
                       <?php checked(empty($current_value)); ?>
                       class="eas-facet-radio"
                       data-facet="<?php echo esc_attr($facet_key); ?>" />
                
                <span class="eas-facet-radio-text">
                    <?php _e('All', 'environmental-advanced-search'); ?>
                </span>
            </label>
            
            <?php foreach ($facet['options'] as $value => $label): ?>
                <label class="eas-facet-radio-label">
                    <input type="radio" 
                           name="<?php echo esc_attr($facet_key); ?>" 
                           value="<?php echo esc_attr($value); ?>"
                           <?php checked($current_value, $value); ?>
                           class="eas-facet-radio"
                           data-facet="<?php echo esc_attr($facet_key); ?>" />
                    
                    <span class="eas-facet-radio-text">
                        <?php echo esc_html($label); ?>
                        <?php if ($args['show_count'] && isset($facet_counts[$value])): ?>
                            <span class="eas-facet-count">(<?php echo intval($facet_counts[$value]); ?>)</span>
                        <?php endif; ?>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render range facet
     */
    private function render_range_facet($facet_key, $facet, $current_value, $args) {
        $min = isset($facet['min']) ? $facet['min'] : 0;
        $max = isset($facet['max']) ? $facet['max'] : 100;
        $step = isset($facet['step']) ? $facet['step'] : 1;
        $currency = isset($facet['currency']) ? $facet['currency'] : '';
        
        $current_min = isset($current_value['min']) ? $current_value['min'] : $min;
        $current_max = isset($current_value['max']) ? $current_value['max'] : $max;
        ?>
        <div class="eas-facet-range">
            <div class="eas-range-inputs">
                <input type="number" 
                       name="<?php echo esc_attr($facet_key); ?>[min]" 
                       value="<?php echo esc_attr($current_min); ?>"
                       min="<?php echo esc_attr($min); ?>"
                       max="<?php echo esc_attr($max); ?>"
                       step="<?php echo esc_attr($step); ?>"
                       class="eas-range-min"
                       placeholder="<?php echo esc_attr($currency . $min); ?>" />
                
                <span class="eas-range-separator">-</span>
                
                <input type="number" 
                       name="<?php echo esc_attr($facet_key); ?>[max]" 
                       value="<?php echo esc_attr($current_max); ?>"
                       min="<?php echo esc_attr($min); ?>"
                       max="<?php echo esc_attr($max); ?>"
                       step="<?php echo esc_attr($step); ?>"
                       class="eas-range-max"
                       placeholder="<?php echo esc_attr($currency . $max); ?>" />
            </div>
            
            <div class="eas-range-slider">
                <input type="range" 
                       class="eas-range-slider-min"
                       min="<?php echo esc_attr($min); ?>"
                       max="<?php echo esc_attr($max); ?>"
                       value="<?php echo esc_attr($current_min); ?>"
                       step="<?php echo esc_attr($step); ?>" />
                
                <input type="range" 
                       class="eas-range-slider-max"
                       min="<?php echo esc_attr($min); ?>"
                       max="<?php echo esc_attr($max); ?>"
                       value="<?php echo esc_attr($current_max); ?>"
                       step="<?php echo esc_attr($step); ?>" />
            </div>
            
            <div class="eas-range-display">
                <span class="eas-range-current">
                    <?php echo esc_html($currency); ?><span class="eas-range-min-display"><?php echo esc_html($current_min); ?></span>
                    - 
                    <?php echo esc_html($currency); ?><span class="eas-range-max-display"><?php echo esc_html($current_max); ?></span>
                </span>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render hierarchical facet (for taxonomies)
     */
    private function render_hierarchical_facet($facet_key, $facet, $current_value, $facet_counts, $args) {
        if (!isset($facet['taxonomy'])) {
            return;
        }
        
        $terms = get_terms(array(
            'taxonomy' => $facet['taxonomy'],
            'hide_empty' => true,
            'hierarchical' => true
        ));
        
        if (is_wp_error($terms) || empty($terms)) {
            return;
        }
        
        $current_value = (array)$current_value;
        $multiple = isset($facet['multiple']) && $facet['multiple'];
        $input_type = $multiple ? 'checkbox' : 'radio';
        $name = $multiple ? $facet_key . '[]' : $facet_key;
        ?>
        <div class="eas-facet-hierarchical">
            <?php if (!$multiple): ?>
                <label class="eas-facet-hierarchical-label">
                    <input type="radio" 
                           name="<?php echo esc_attr($facet_key); ?>" 
                           value=""
                           <?php checked(empty($current_value)); ?>
                           class="eas-facet-hierarchical-input" />
                    <span><?php _e('All', 'environmental-advanced-search'); ?></span>
                </label>
            <?php endif; ?>
            
            <?php $this->render_hierarchical_terms($terms, 0, $name, $input_type, $current_value, $facet_counts, $args); ?>
        </div>
        <?php
    }
    
    /**
     * Render hierarchical terms recursively
     */
    private function render_hierarchical_terms($terms, $parent_id, $name, $input_type, $current_value, $facet_counts, $args) {
        $child_terms = array_filter($terms, function($term) use ($parent_id) {
            return $term->parent == $parent_id;
        });
        
        if (empty($child_terms)) {
            return;
        }
        ?>
        <ul class="eas-hierarchical-list eas-hierarchical-level-<?php echo intval($parent_id); ?>">
            <?php foreach ($child_terms as $term): ?>
                <li class="eas-hierarchical-item">
                    <label class="eas-facet-hierarchical-label">
                        <input type="<?php echo esc_attr($input_type); ?>" 
                               name="<?php echo esc_attr($name); ?>" 
                               value="<?php echo esc_attr($term->slug); ?>"
                               <?php if ($input_type === 'checkbox'): ?>
                                   <?php checked(in_array($term->slug, $current_value)); ?>
                               <?php else: ?>
                                   <?php checked(in_array($term->slug, $current_value)); ?>
                               <?php endif; ?>
                               class="eas-facet-hierarchical-input" />
                        
                        <span class="eas-term-name"><?php echo esc_html($term->name); ?></span>
                        
                        <?php if ($args['show_count']): ?>
                            <span class="eas-facet-count">(<?php echo intval($term->count); ?>)</span>
                        <?php endif; ?>
                    </label>
                    
                    <?php $this->render_hierarchical_terms($terms, $term->term_id, $name, $input_type, $current_value, $facet_counts, $args); ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php
    }
    
    /**
     * Render location facet
     */
    private function render_location_facet($facet_key, $facet, $current_value, $args) {
        $current_location = isset($current_value['location']) ? $current_value['location'] : '';
        $current_distance = isset($current_value['distance']) ? $current_value['distance'] : 50;
        $with_distance = isset($facet['with_distance']) && $facet['with_distance'];
        ?>
        <div class="eas-facet-location">
            <div class="eas-location-input-wrapper">
                <input type="text" 
                       name="<?php echo esc_attr($facet_key); ?>[location]" 
                       value="<?php echo esc_attr($current_location); ?>"
                       class="eas-location-input"
                       placeholder="<?php _e('Enter location...', 'environmental-advanced-search'); ?>"
                       autocomplete="off" />
                
                <button type="button" class="eas-use-current-location" title="<?php _e('Use current location', 'environmental-advanced-search'); ?>">
                    <span class="dashicons dashicons-location"></span>
                </button>
            </div>
            
            <?php if ($with_distance): ?>
                <div class="eas-distance-wrapper">
                    <label for="<?php echo esc_attr($facet_key); ?>_distance">
                        <?php _e('Within', 'environmental-advanced-search'); ?>
                    </label>
                    
                    <select name="<?php echo esc_attr($facet_key); ?>[distance]" 
                            id="<?php echo esc_attr($facet_key); ?>_distance"
                            class="eas-distance-select">
                        <option value="5" <?php selected($current_distance, 5); ?>>5 km</option>
                        <option value="10" <?php selected($current_distance, 10); ?>>10 km</option>
                        <option value="25" <?php selected($current_distance, 25); ?>>25 km</option>
                        <option value="50" <?php selected($current_distance, 50); ?>>50 km</option>
                        <option value="100" <?php selected($current_distance, 100); ?>>100 km</option>
                    </select>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Get current facet value from URL
     */
    private function get_current_facet_value($facet_key) {
        return isset($_GET[$facet_key]) ? $_GET[$facet_key] : '';
    }
    
    /**
     * Get facet counts
     */
    private function get_facet_counts($facet_key) {
        // Use Elasticsearch aggregations if available
        $elasticsearch_manager = EnvironmentalAdvancedSearch::getInstance()->get_elasticsearch_manager();
        
        if ($elasticsearch_manager && $elasticsearch_manager->is_available()) {
            return $this->get_elasticsearch_facet_counts($facet_key);
        }
        
        // Fallback to WordPress queries
        return $this->get_wordpress_facet_counts($facet_key);
    }
    
    /**
     * Get facet counts from Elasticsearch
     */
    private function get_elasticsearch_facet_counts($facet_key) {
        $elasticsearch_manager = EnvironmentalAdvancedSearch::getInstance()->get_elasticsearch_manager();
        $search_term = get_search_query();
        $current_filters = $this->get_current_filters();
        
        // Remove current facet from filters to get accurate counts
        unset($current_filters[$facet_key]);
        
        $aggregations = $elasticsearch_manager->get_aggregations($search_term, $current_filters);
        
        if ($aggregations && isset($aggregations['aggregations'])) {
            $agg_key = $this->get_elasticsearch_aggregation_key($facet_key);
            
            if (isset($aggregations['aggregations'][$agg_key]['buckets'])) {
                $counts = array();
                foreach ($aggregations['aggregations'][$agg_key]['buckets'] as $bucket) {
                    $counts[$bucket['key']] = $bucket['doc_count'];
                }
                return $counts;
            }
        }
        
        return array();
    }
    
    /**
     * Get WordPress facet counts
     */
    private function get_wordpress_facet_counts($facet_key) {
        global $wpdb;
        
        $counts = array();
        $facet = $this->available_facets[$facet_key];
        
        switch ($facet['type']) {
            case 'hierarchical':
                if (isset($facet['taxonomy'])) {
                    $terms = get_terms(array(
                        'taxonomy' => $facet['taxonomy'],
                        'hide_empty' => true
                    ));
                    
                    foreach ($terms as $term) {
                        $counts[$term->slug] = $term->count;
                    }
                }
                break;
                
            default:
                // For other facet types, you would implement custom counting logic
                break;
        }
        
        return $counts;
    }
    
    /**
     * Get Elasticsearch aggregation key for facet
     */
    private function get_elasticsearch_aggregation_key($facet_key) {
        $mapping = array(
            'post_type' => 'post_types',
            'category' => 'categories',
            'impact_level' => 'environmental_impacts',
            'project_status' => 'project_statuses'
        );
        
        return isset($mapping[$facet_key]) ? $mapping[$facet_key] : $facet_key;
    }
    
    /**
     * Get current filters
     */
    private function get_current_filters() {
        $filters = array();
        
        foreach (array_keys($this->available_facets) as $facet_key) {
            if (isset($_GET[$facet_key]) && !empty($_GET[$facet_key])) {
                $filters[$facet_key] = $_GET[$facet_key];
            }
        }
        
        return $filters;
    }
    
    /**
     * Faceted search shortcode
     */
    public function faceted_search_shortcode($atts) {
        $atts = shortcode_atts(array(
            'facets' => '',
            'layout' => 'sidebar',
            'ajax' => 'true',
            'show_count' => 'true',
            'collapsible' => 'true',
            'show_clear' => 'true'
        ), $atts);
        
        // Parse facets
        if (!empty($atts['facets'])) {
            $atts['facets'] = explode(',', $atts['facets']);
            $atts['facets'] = array_map('trim', $atts['facets']);
        } else {
            $atts['facets'] = array_keys($this->available_facets);
        }
        
        // Convert string boolean values
        $atts['ajax'] = $atts['ajax'] === 'true';
        $atts['show_count'] = $atts['show_count'] === 'true';
        $atts['collapsible'] = $atts['collapsible'] === 'true';
        $atts['show_clear'] = $atts['show_clear'] === 'true';
        
        return $this->render_faceted_search($atts);
    }
    
    /**
     * Add faceted search JavaScript
     */
    public function add_faceted_search_script() {
        if (!is_search() && !is_home() && !is_archive()) {
            return;
        }
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Faceted search functionality
            var $facetedSearch = $('.eas-faceted-search');
            var $searchResults = $('.search-results, .eas-search-results');
            var isAjaxEnabled = $facetedSearch.data('ajax');
            
            // Handle facet changes
            $facetedSearch.on('change', '.eas-facet-select, .eas-facet-checkbox, .eas-facet-radio, .eas-range-min, .eas-range-max', function() {
                if (isAjaxEnabled) {
                    performAjaxSearch();
                } else {
                    updateActiveFacets();
                }
            });
            
            // Handle location autocomplete
            initLocationAutocomplete();
            
            // Handle current location button
            $('.eas-use-current-location').on('click', function() {
                getCurrentLocation();
            });
            
            // Handle clear facets
            $('.eas-clear-facets').on('click', function() {
                clearAllFacets();
            });
            
            // Handle collapsible facets
            $('.eas-facet-title').on('click', function() {
                var $facet = $(this).closest('.eas-facet');
                if ($facet.data('collapsible')) {
                    $facet.toggleClass('eas-facet-collapsed');
                }
            });
            
            // Range slider functionality
            initRangeSliders();
            
            // Initial setup
            updateActiveFacets();
            
            function performAjaxSearch() {
                var formData = $('.eas-facets-form').serialize();
                
                $searchResults.addClass('eas-loading');
                
                $.ajax({
                    url: eas_ajax.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'eas_search',
                        nonce: eas_ajax.nonce,
                        search_query: $('input[name="s"]').val(),
                        facets: formData
                    },
                    success: function(response) {
                        if (response.success) {
                            $searchResults.html(response.data.html);
                            updateFacetCounts(response.data.facet_counts);
                            updateUrl(formData);
                        }
                    },
                    error: function() {
                        // Handle error
                    },
                    complete: function() {
                        $searchResults.removeClass('eas-loading');
                        updateActiveFacets();
                    }
                });
            }
            
            function updateActiveFacets() {
                var $activeFacets = $('.eas-active-facets-list');
                $activeFacets.empty();
                
                var hasActive = false;
                
                $('.eas-facets-form').find('input, select').each(function() {
                    var $input = $(this);
                    var value = $input.val();
                    var name = $input.attr('name');
                    
                    if (value && value !== '' && name !== 's') {
                        var label = getFacetLabel($input);
                        if (label) {
                            var $tag = $('<span class="eas-active-facet-tag">' + label + ' <button type="button" class="eas-remove-facet">&times;</button></span>');
                            $tag.data('input', $input);
                            $activeFacets.append($tag);
                            hasActive = true;
                        }
                    }
                });
                
                if (hasActive) {
                    $('.eas-active-facets').show();
                    $('.eas-clear-facets').show();
                } else {
                    $('.eas-active-facets').hide();
                    $('.eas-clear-facets').hide();
                }
            }
            
            function getFacetLabel($input) {
                var value = $input.val();
                var $facet = $input.closest('.eas-facet');
                var facetTitle = $facet.find('.eas-facet-title').text().trim();
                
                if ($input.is('select')) {
                    var optionText = $input.find('option:selected').text();
                    return facetTitle + ': ' + optionText;
                } else if ($input.is(':checkbox, :radio')) {
                    if ($input.is(':checked')) {
                        var labelText = $input.closest('label').find('.eas-facet-checkbox-text, .eas-facet-radio-text').text().trim();
                        return facetTitle + ': ' + labelText;
                    }
                } else {
                    return facetTitle + ': ' + value;
                }
                
                return null;
            }
            
            function clearAllFacets() {
                $('.eas-facets-form')[0].reset();
                $('.eas-facets-form input[name="s"]').val($('input[name="s"]').first().val());
                
                if (isAjaxEnabled) {
                    performAjaxSearch();
                } else {
                    $('.eas-facets-form').submit();
                }
            }
            
            function initLocationAutocomplete() {
                if (typeof google !== 'undefined' && google.maps && google.maps.places) {
                    $('.eas-location-input').each(function() {
                        var autocomplete = new google.maps.places.Autocomplete(this);
                        autocomplete.addListener('place_changed', function() {
                            if (isAjaxEnabled) {
                                performAjaxSearch();
                            }
                        });
                    });
                }
            }
            
            function getCurrentLocation() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        var geocoder = new google.maps.Geocoder();
                        var latlng = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
                        
                        geocoder.geocode({location: latlng}, function(results, status) {
                            if (status === 'OK' && results[0]) {
                                $('.eas-location-input').val(results[0].formatted_address);
                                if (isAjaxEnabled) {
                                    performAjaxSearch();
                                }
                            }
                        });
                    });
                }
            }
            
            function initRangeSliders() {
                $('.eas-range-slider').each(function() {
                    var $slider = $(this);
                    var $minSlider = $slider.find('.eas-range-slider-min');
                    var $maxSlider = $slider.find('.eas-range-slider-max');
                    var $minInput = $slider.siblings('.eas-range-inputs').find('.eas-range-min');
                    var $maxInput = $slider.siblings('.eas-range-inputs').find('.eas-range-max');
                    var $minDisplay = $slider.siblings('.eas-range-display').find('.eas-range-min-display');
                    var $maxDisplay = $slider.siblings('.eas-range-display').find('.eas-range-max-display');
                    
                    function updateValues() {
                        var minVal = parseInt($minSlider.val());
                        var maxVal = parseInt($maxSlider.val());
                        
                        if (minVal > maxVal) {
                            var temp = minVal;
                            minVal = maxVal;
                            maxVal = temp;
                            $minSlider.val(minVal);
                            $maxSlider.val(maxVal);
                        }
                        
                        $minInput.val(minVal);
                        $maxInput.val(maxVal);
                        $minDisplay.text(minVal);
                        $maxDisplay.text(maxVal);
                    }
                    
                    $minSlider.add($maxSlider).on('input', updateValues);
                    $minInput.add($maxInput).on('input', function() {
                        $minSlider.val($minInput.val());
                        $maxSlider.val($maxInput.val());
                        updateValues();
                    });
                });
            }
            
            function updateUrl(formData) {
                if (history.pushState) {
                    var newUrl = window.location.pathname + '?' + formData;
                    history.pushState(null, null, newUrl);
                }
            }
            
            function updateFacetCounts(counts) {
                if (counts) {
                    for (var facet in counts) {
                        for (var value in counts[facet]) {
                            var $count = $('.eas-facet-' + facet).find('[value="' + value + '"]').siblings('.eas-facet-count');
                            if ($count.length) {
                                $count.text('(' + counts[facet][value] + ')');
                            }
                        }
                    }
                }
            }
            
            // Handle active facet removal
            $(document).on('click', '.eas-remove-facet', function() {
                var $tag = $(this).closest('.eas-active-facet-tag');
                var $input = $tag.data('input');
                
                if ($input.is(':checkbox, :radio')) {
                    $input.prop('checked', false);
                } else {
                    $input.val('');
                }
                
                if (isAjaxEnabled) {
                    performAjaxSearch();
                } else {
                    updateActiveFacets();
                }
            });
        });
        </script>
        <?php
    }
}
