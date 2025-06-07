<?php
/**
 * Search Shortcodes Class
 *
 * @package Environmental_Advanced_Search
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAS_Shortcodes {
    
    /**
     * Initialize shortcodes
     */
    public function __construct() {
        add_shortcode('eas_search', array($this, 'search_form_shortcode'));
        add_shortcode('eas_search_results', array($this, 'search_results_shortcode'));
        add_shortcode('eas_popular_searches', array($this, 'popular_searches_shortcode'));
        add_shortcode('eas_faceted_search', array($this, 'faceted_search_shortcode'));
        add_shortcode('eas_location_search', array($this, 'location_search_shortcode'));
        
        // Add shortcode button to editor
        add_action('media_buttons', array($this, 'add_shortcode_button'));
        add_action('wp_ajax_eas_shortcode_popup', array($this, 'shortcode_popup'));
    }
    
    /**
     * Search form shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function search_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => '',
            'placeholder' => __('Search...', 'environmental-advanced-search'),
            'show_filters' => 'false',
            'show_suggestions' => 'true',
            'show_location' => 'false',
            'post_types' => 'post,page',
            'ajax' => 'true',
            'results_per_page' => '10',
            'show_sorting' => 'true',
            'style' => 'default'
        ), $atts, 'eas_search');
        
        $post_types = array_map('trim', explode(',', $atts['post_types']));
        $show_filters = filter_var($atts['show_filters'], FILTER_VALIDATE_BOOLEAN);
        $show_suggestions = filter_var($atts['show_suggestions'], FILTER_VALIDATE_BOOLEAN);
        $show_location = filter_var($atts['show_location'], FILTER_VALIDATE_BOOLEAN);
        $ajax = filter_var($atts['ajax'], FILTER_VALIDATE_BOOLEAN);
        $show_sorting = filter_var($atts['show_sorting'], FILTER_VALIDATE_BOOLEAN);
        
        $form_id = 'eas-search-' . uniqid();
        
        ob_start();
        ?>
        <div class="eas-search-shortcode eas-style-<?php echo esc_attr($atts['style']); ?>" id="<?php echo esc_attr($form_id); ?>">
            
            <?php if (!empty($atts['title'])): ?>
            <h3 class="eas-search-title"><?php echo esc_html($atts['title']); ?></h3>
            <?php endif; ?>
            
            <form class="eas-search-form" method="get" action="<?php echo esc_url(home_url('/')); ?>" data-ajax="<?php echo $ajax ? 'true' : 'false'; ?>">
                
                <!-- Search Input -->
                <div class="eas-search-input-wrapper">
                    <div class="eas-search-input-container">
                        <input type="text" 
                               name="s" 
                               class="eas-search-input" 
                               value="<?php echo esc_attr(get_search_query()); ?>" 
                               placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
                               autocomplete="off" />
                        <button type="submit" class="eas-search-button">
                            <span><?php _e('Search', 'environmental-advanced-search'); ?></span>
                        </button>
                    </div>
                    
                    <?php if ($show_suggestions): ?>
                    <div class="eas-suggestions-container">
                        <!-- Populated via AJAX -->
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Post Types -->
                <?php foreach ($post_types as $post_type): ?>
                <input type="hidden" name="post_type[]" value="<?php echo esc_attr($post_type); ?>" />
                <?php endforeach; ?>
                
                <?php if ($show_filters || $show_location): ?>
                <!-- Advanced Filters -->
                <div class="eas-advanced-filters">
                    <button type="button" class="eas-toggle-filters">
                        <?php _e('Advanced Filters', 'environmental-advanced-search'); ?>
                        <span class="eas-toggle-icon">▼</span>
                    </button>
                    
                    <div class="eas-filters-panel" style="display: none;">
                        <?php if ($show_filters): ?>
                        <?php $this->render_filters(); ?>
                        <?php endif; ?>
                        
                        <?php if ($show_location): ?>
                        <?php $this->render_location_filters(); ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
            </form>
            
            <?php if ($ajax): ?>
            <!-- AJAX Results Container -->
            <div class="eas-search-results-container">
                <?php if ($show_sorting): ?>
                <div class="eas-search-controls" style="display: none;">
                    <div class="eas-results-info">
                        <span class="eas-results-count"></span>
                    </div>
                    <div class="eas-sorting-controls">
                        <label for="eas-sort-<?php echo esc_attr($form_id); ?>"><?php _e('Sort by:', 'environmental-advanced-search'); ?></label>
                        <select id="eas-sort-<?php echo esc_attr($form_id); ?>" name="orderby">
                            <option value="relevance"><?php _e('Relevance', 'environmental-advanced-search'); ?></option>
                            <option value="date"><?php _e('Date', 'environmental-advanced-search'); ?></option>
                            <option value="title"><?php _e('Title', 'environmental-advanced-search'); ?></option>
                            <option value="modified"><?php _e('Last Modified', 'environmental-advanced-search'); ?></option>
                        </select>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="eas-search-results">
                    <!-- Results populated via AJAX -->
                </div>
                
                <div class="eas-load-more-container">
                    <button type="button" class="eas-load-more-btn" style="display: none;">
                        <?php _e('Load More Results', 'environmental-advanced-search'); ?>
                    </button>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
        
        <?php if ($ajax): ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var $container = $('#<?php echo esc_js($form_id); ?>');
            var $form = $container.find('.eas-search-form');
            var $input = $container.find('.eas-search-input');
            var $results = $container.find('.eas-search-results');
            var $loadMore = $container.find('.eas-load-more-btn');
            var $controls = $container.find('.eas-search-controls');
            var $suggestions = $container.find('.eas-suggestions-container');
            var currentPage = 1;
            var totalPages = 1;
            var searchTimeout;
            
            // Handle form submission
            $form.on('submit', function(e) {
                if ($(this).data('ajax') === true) {
                    e.preventDefault();
                    performSearch();
                }
            });
            
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
            <?php endif; ?>
            
            // Toggle filters
            $container.find('.eas-toggle-filters').on('click', function() {
                var $panel = $container.find('.eas-filters-panel');
                var $icon = $(this).find('.eas-toggle-icon');
                
                $panel.slideToggle();
                $icon.text($panel.is(':visible') ? '▲' : '▼');
            });
            
            // Sorting
            $container.find('select[name="orderby"]').on('change', function() {
                if ($input.val()) {
                    performSearch();
                }
            });
            
            // Load more
            $loadMore.on('click', function() {
                loadMoreResults();
            });
            
            function performSearch() {
                var formData = $form.serializeArray();
                var searchData = {
                    action: 'eas_search',
                    nonce: easAjax.search_nonce,
                    query: $input.val(),
                    filters: {},
                    page: 1,
                    per_page: <?php echo absint($atts['results_per_page']); ?>,
                    post_types: <?php echo json_encode($post_types); ?>
                };
                
                // Process form data
                $.each(formData, function(index, field) {
                    if (field.name !== 's' && field.name !== 'post_type[]' && field.value) {
                        searchData.filters[field.name] = field.value;
                    }
                });
                
                $results.html('<div class="eas-loading">' + easAjax.strings.searching + '</div>');
                $controls.hide();
                $loadMore.hide();
                
                $.ajax({
                    url: easAjax.ajax_url,
                    type: 'POST',
                    data: searchData,
                    success: function(response) {
                        if (response.success) {
                            displayResults(response.data);
                            currentPage = 1;
                            totalPages = response.data.total_pages;
                        } else {
                            $results.html('<div class="eas-error">' + easAjax.strings.error + '</div>');
                        }
                    },
                    error: function() {
                        $results.html('<div class="eas-error">' + easAjax.strings.error + '</div>');
                    }
                });
            }
            
            function loadMoreResults() {
                if (currentPage >= totalPages) return;
                
                var formData = $form.serializeArray();
                var searchData = {
                    action: 'eas_load_more',
                    nonce: easAjax.search_nonce,
                    query: $input.val(),
                    filters: {},
                    page: currentPage + 1,
                    per_page: <?php echo absint($atts['results_per_page']); ?>,
                    post_types: <?php echo json_encode($post_types); ?>
                };
                
                $.each(formData, function(index, field) {
                    if (field.name !== 's' && field.name !== 'post_type[]' && field.value) {
                        searchData.filters[field.name] = field.value;
                    }
                });
                
                $loadMore.text(easAjax.strings.loading);
                
                $.ajax({
                    url: easAjax.ajax_url,
                    type: 'POST',
                    data: searchData,
                    success: function(response) {
                        if (response.success) {
                            appendResults(response.data.results);
                            currentPage++;
                            
                            if (!response.data.has_more) {
                                $loadMore.hide();
                            } else {
                                $loadMore.text(easAjax.strings.load_more);
                            }
                        }
                    }
                });
            }
            
            function displayResults(data) {
                var html = '';
                
                if (data.results.length > 0) {
                    $.each(data.results, function(index, result) {
                        html += formatResult(result, index);
                    });
                    
                    $controls.find('.eas-results-count').text(
                        data.total + ' results found in ' + data.execution_time + 's'
                    );
                    $controls.show();
                    
                    if (data.total_pages > 1) {
                        $loadMore.show();
                    }
                } else {
                    html = '<div class="eas-no-results">' + easAjax.strings.no_results + '</div>';
                }
                
                $results.html(html);
            }
            
            function appendResults(results) {
                var html = '';
                var startIndex = $results.find('.eas-result').length;
                
                $.each(results, function(index, result) {
                    html += formatResult(result, startIndex + index);
                });
                
                $results.append(html);
            }
            
            function formatResult(result, index) {
                var html = '<article class="eas-result">';
                
                if (result.featured_image) {
                    html += '<div class="eas-result-image">';
                    html += '<img src="' + result.featured_image + '" alt="' + result.title + '" />';
                    html += '</div>';
                }
                
                html += '<div class="eas-result-content">';
                html += '<h3 class="eas-result-title">';
                html += '<a href="' + result.url + '" data-post-id="' + result.id + '" data-position="' + index + '">';
                html += result.title;
                html += '</a>';
                html += '</h3>';
                
                html += '<div class="eas-result-excerpt">' + result.excerpt + '</div>';
                
                html += '<div class="eas-result-meta">';
                html += '<span class="eas-result-date">' + new Date(result.date).toLocaleDateString() + '</span>';
                if (result.author) {
                    html += '<span class="eas-result-author">by ' + result.author + '</span>';
                }
                html += '</div>';
                
                html += '</div>';
                html += '</article>';
                
                return html;
            }
            
            // Track clicks
            $results.on('click', 'a', function() {
                var postId = $(this).data('post-id');
                var position = $(this).data('position');
                var query = $input.val();
                
                if (postId && query) {
                    $.ajax({
                        url: easAjax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'eas_track_click',
                            nonce: easAjax.search_nonce,
                            post_id: postId,
                            query: query,
                            position: position
                        }
                    });
                }
            });
            
            <?php if ($show_suggestions): ?>
            function getSuggestions(query) {
                $.ajax({
                    url: easAjax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'eas_get_suggestions',
                        nonce: easAjax.search_nonce,
                        query: query,
                        limit: 5
                    },
                    success: function(response) {
                        if (response.success && response.data.suggestions.length > 0) {
                            var html = '<ul class="eas-suggestions-list">';
                            $.each(response.data.suggestions, function(index, suggestion) {
                                html += '<li class="eas-suggestion" data-query="' + suggestion.text + '">';
                                html += suggestion.text;
                                if (suggestion.type === 'popular') {
                                    html += ' <small>(popular)</small>';
                                }
                                html += '</li>';
                            });
                            html += '</ul>';
                            $suggestions.html(html).show();
                        } else {
                            $suggestions.hide();
                        }
                    }
                });
            }
            
            // Handle suggestion clicks
            $suggestions.on('click', '.eas-suggestion', function() {
                var query = $(this).data('query');
                $input.val(query);
                $suggestions.hide();
                performSearch();
            });
            <?php endif; ?>
        });
        </script>
        <?php endif; ?>
        
        <?php
        return ob_get_clean();
    }
    
    /**
     * Search results shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function search_results_shortcode($atts) {
        if (!is_search()) {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'posts_per_page' => get_option('posts_per_page', 10),
            'show_excerpts' => 'true',
            'show_thumbnails' => 'true',
            'show_meta' => 'true',
            'layout' => 'list'
        ), $atts, 'eas_search_results');
        
        $search_query = get_search_query();
        $paged = get_query_var('paged', 1);
        
        $search_engine = new EAS_Search_Engine();
        $results = $search_engine->search($search_query, array(
            'page' => $paged,
            'per_page' => absint($atts['posts_per_page'])
        ));
        
        ob_start();
        
        if (!empty($results['posts'])) {
            echo '<div class="eas-search-results-shortcode eas-layout-' . esc_attr($atts['layout']) . '">';
            
            foreach ($results['posts'] as $post) {
                setup_postdata($post);
                ?>
                <article class="eas-search-result">
                    
                    <?php if (filter_var($atts['show_thumbnails'], FILTER_VALIDATE_BOOLEAN) && has_post_thumbnail($post)): ?>
                    <div class="eas-result-thumbnail">
                        <a href="<?php echo get_permalink($post); ?>">
                            <?php echo get_the_post_thumbnail($post, 'medium'); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <div class="eas-result-content">
                        <h3 class="eas-result-title">
                            <a href="<?php echo get_permalink($post); ?>"><?php echo get_the_title($post); ?></a>
                        </h3>
                        
                        <?php if (filter_var($atts['show_excerpts'], FILTER_VALIDATE_BOOLEAN)): ?>
                        <div class="eas-result-excerpt">
                            <?php echo get_the_excerpt($post); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (filter_var($atts['show_meta'], FILTER_VALIDATE_BOOLEAN)): ?>
                        <div class="eas-result-meta">
                            <span class="eas-result-date"><?php echo get_the_date('', $post); ?></span>
                            <span class="eas-result-author"><?php echo get_the_author_meta('display_name', $post->post_author); ?></span>
                            <span class="eas-result-type"><?php echo get_post_type_object($post->post_type)->labels->singular_name; ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                </article>
                <?php
            }
            
            // Pagination
            if ($results['total'] > $atts['posts_per_page']) {
                $total_pages = ceil($results['total'] / $atts['posts_per_page']);
                echo paginate_links(array(
                    'total' => $total_pages,
                    'current' => $paged,
                    'format' => '?paged=%#%',
                    'prev_text' => '&laquo; Previous',
                    'next_text' => 'Next &raquo;'
                ));
            }
            
            echo '</div>';
            wp_reset_postdata();
        } else {
            echo '<div class="eas-no-results">' . __('No results found.', 'environmental-advanced-search') . '</div>';
        }
        
        return ob_get_clean();
    }
    
    /**
     * Popular searches shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function popular_searches_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => '10',
            'title' => __('Popular Searches', 'environmental-advanced-search'),
            'show_count' => 'true'
        ), $atts, 'eas_popular_searches');
        
        $analytics = new EAS_Search_Analytics();
        $popular_searches = $analytics->get_popular_searches('', absint($atts['limit']));
        
        if (empty($popular_searches)) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="eas-popular-searches">
            <?php if (!empty($atts['title'])): ?>
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <?php endif; ?>
            
            <ul class="eas-popular-searches-list">
                <?php foreach ($popular_searches as $search): ?>
                <li>
                    <a href="<?php echo esc_url(home_url('/?s=' . urlencode($search->search_term))); ?>">
                        <?php echo esc_html($search->search_term); ?>
                        <?php if (filter_var($atts['show_count'], FILTER_VALIDATE_BOOLEAN)): ?>
                        <span class="search-count">(<?php echo absint($search->search_count); ?>)</span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Faceted search shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function faceted_search_shortcode($atts) {
        $atts = shortcode_atts(array(
            'facets' => 'category,tags,date',
            'style' => 'sidebar',
            'collapsible' => 'true'
        ), $atts, 'eas_faceted_search');
        
        $facets = array_map('trim', explode(',', $atts['facets']));
        $faceted_search = new EAS_Faceted_Search();
        
        ob_start();
        ?>
        <div class="eas-faceted-search eas-style-<?php echo esc_attr($atts['style']); ?>">
            <?php foreach ($facets as $facet): ?>
                <?php $facet_data = $faceted_search->get_facet_data($facet); ?>
                <?php if (!empty($facet_data)): ?>
                <div class="eas-facet-group">
                    <h4 class="eas-facet-title"><?php echo esc_html(ucfirst($facet)); ?></h4>
                    <div class="eas-facet-content">
                        <?php echo $faceted_search->render_facet($facet, $facet_data); ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Location search shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function location_search_shortcode($atts) {
        $atts = shortcode_atts(array(
            'default_radius' => '25',
            'units' => 'km',
            'show_map' => 'false',
            'map_height' => '400px'
        ), $atts, 'eas_location_search');
        
        $geolocation = new EAS_Geolocation_Search();
        
        ob_start();
        ?>
        <div class="eas-location-search-shortcode">
            <?php echo $geolocation->get_location_filter_html(); ?>
            
            <?php if (filter_var($atts['show_map'], FILTER_VALIDATE_BOOLEAN)): ?>
            <div class="eas-location-map" style="height: <?php echo esc_attr($atts['map_height']); ?>;">
                <!-- Map will be loaded here -->
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render search filters
     */
    private function render_filters() {
        ?>
        <div class="eas-filter-row">
            <div class="eas-filter-group">
                <label for="eas-category-filter"><?php _e('Category', 'environmental-advanced-search'); ?></label>
                <select name="category" id="eas-category-filter">
                    <option value=""><?php _e('All Categories', 'environmental-advanced-search'); ?></option>
                    <?php
                    $categories = get_categories(array('hide_empty' => true));
                    foreach ($categories as $category) {
                        echo '<option value="' . esc_attr($category->slug) . '">' . esc_html($category->name) . '</option>';
                    }
                    ?>
                </select>
            </div>
            
            <div class="eas-filter-group">
                <label for="eas-date-filter"><?php _e('Date', 'environmental-advanced-search'); ?></label>
                <select name="date_range" id="eas-date-filter">
                    <option value=""><?php _e('Any Time', 'environmental-advanced-search'); ?></option>
                    <option value="today"><?php _e('Today', 'environmental-advanced-search'); ?></option>
                    <option value="week"><?php _e('This Week', 'environmental-advanced-search'); ?></option>
                    <option value="month"><?php _e('This Month', 'environmental-advanced-search'); ?></option>
                    <option value="year"><?php _e('This Year', 'environmental-advanced-search'); ?></option>
                </select>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render location filters
     */
    private function render_location_filters() {
        $geolocation = new EAS_Geolocation_Search();
        echo $geolocation->get_location_filter_html();
    }
    
    /**
     * Add shortcode button to editor
     */
    public function add_shortcode_button() {
        global $post;
        
        if (!$post || !current_user_can('edit_posts')) {
            return;
        }
        
        echo '<button type="button" id="eas-shortcode-button" class="button" data-editor="content">';
        echo '<span class="wp-media-buttons-icon dashicons dashicons-search"></span>';
        echo __('Add Search', 'environmental-advanced-search');
        echo '</button>';
        
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#eas-shortcode-button').on('click', function() {
                var editor = $(this).data('editor');
                
                // Open popup for shortcode configuration
                var popup = window.open(
                    ajaxurl + '?action=eas_shortcode_popup',
                    'eas_shortcode_popup',
                    'width=800,height=600,scrollbars=yes,resizable=yes'
                );
                
                // Handle popup response
                window.easInsertShortcode = function(shortcode) {
                    if (typeof tinyMCE !== 'undefined' && tinyMCE.get(editor) && !tinyMCE.get(editor).isHidden()) {
                        tinyMCE.get(editor).insertContent(shortcode);
                    } else {
                        var $textarea = $('#' + editor);
                        var cursorPos = $textarea.prop('selectionStart');
                        var content = $textarea.val();
                        var newContent = content.substring(0, cursorPos) + shortcode + content.substring(cursorPos);
                        $textarea.val(newContent);
                    }
                    popup.close();
                };
            });
        });
        </script>
        <?php
    }
    
    /**
     * Shortcode configuration popup
     */
    public function shortcode_popup() {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title><?php _e('Insert Search Shortcode', 'environmental-advanced-search'); ?></title>
            <script src="<?php echo includes_url('js/jquery/jquery.min.js'); ?>"></script>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                .form-group { margin-bottom: 15px; }
                .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
                .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 5px; }
                .form-group input[type="checkbox"] { width: auto; }
                .button { background: #0073aa; color: #fff; padding: 10px 20px; border: none; cursor: pointer; }
                .button:hover { background: #005a87; }
                .shortcode-preview { background: #f5f5f5; padding: 10px; margin: 10px 0; font-family: monospace; }
            </style>
        </head>
        <body>
            <h2><?php _e('Insert Search Shortcode', 'environmental-advanced-search'); ?></h2>
            
            <form id="shortcode-form">
                <div class="form-group">
                    <label for="shortcode-type"><?php _e('Shortcode Type', 'environmental-advanced-search'); ?></label>
                    <select id="shortcode-type" name="type">
                        <option value="eas_search"><?php _e('Search Form', 'environmental-advanced-search'); ?></option>
                        <option value="eas_search_results"><?php _e('Search Results', 'environmental-advanced-search'); ?></option>
                        <option value="eas_popular_searches"><?php _e('Popular Searches', 'environmental-advanced-search'); ?></option>
                        <option value="eas_faceted_search"><?php _e('Faceted Search', 'environmental-advanced-search'); ?></option>
                        <option value="eas_location_search"><?php _e('Location Search', 'environmental-advanced-search'); ?></option>
                    </select>
                </div>
                
                <!-- Common options -->
                <div class="form-group">
                    <label for="title"><?php _e('Title', 'environmental-advanced-search'); ?></label>
                    <input type="text" id="title" name="title" />
                </div>
                
                <!-- Search form specific options -->
                <div class="search-form-options">
                    <div class="form-group">
                        <label for="placeholder"><?php _e('Placeholder Text', 'environmental-advanced-search'); ?></label>
                        <input type="text" id="placeholder" name="placeholder" value="Search..." />
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="show_filters" value="true" />
                            <?php _e('Show Filters', 'environmental-advanced-search'); ?>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="show_suggestions" value="true" checked />
                            <?php _e('Show Suggestions', 'environmental-advanced-search'); ?>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="show_location" value="true" />
                            <?php _e('Show Location Filter', 'environmental-advanced-search'); ?>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="ajax" value="true" checked />
                            <?php _e('Enable AJAX Search', 'environmental-advanced-search'); ?>
                        </label>
                    </div>
                </div>
                
                <div class="shortcode-preview">
                    <strong><?php _e('Shortcode Preview:', 'environmental-advanced-search'); ?></strong>
                    <div id="shortcode-output">[eas_search]</div>
                </div>
                
                <button type="button" id="insert-shortcode" class="button">
                    <?php _e('Insert Shortcode', 'environmental-advanced-search'); ?>
                </button>
            </form>
            
            <script>
            jQuery(document).ready(function($) {
                function updateShortcode() {
                    var type = $('#shortcode-type').val();
                    var shortcode = '[' + type;
                    var hasAttributes = false;
                    
                    $('#shortcode-form input, #shortcode-form select').each(function() {
                        var $field = $(this);
                        var name = $field.attr('name');
                        var value = $field.val();
                        
                        if (name && name !== 'type' && value) {
                            if ($field.is(':checkbox') && !$field.is(':checked')) {
                                return;
                            }
                            
                            if (!hasAttributes) {
                                shortcode += ' ';
                                hasAttributes = true;
                            } else {
                                shortcode += ' ';
                            }
                            
                            shortcode += name + '="' + value + '"';
                        }
                    });
                    
                    shortcode += ']';
                    $('#shortcode-output').text(shortcode);
                }
                
                $('#shortcode-form').on('change', 'input, select', updateShortcode);
                
                $('#insert-shortcode').on('click', function() {
                    var shortcode = $('#shortcode-output').text();
                    if (window.opener && window.opener.easInsertShortcode) {
                        window.opener.easInsertShortcode(shortcode);
                    }
                });
                
                updateShortcode();
            });
            </script>
        </body>
        </html>
        <?php
        exit;
    }
}
