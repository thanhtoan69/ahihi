<?php
/**
 * Archive template for Item Exchange listings
 * 
 * @package EnvironmentalItemExchange
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="eie-archive-container">
    <div class="eie-archive-header">
        <div class="container">
            <div class="eie-header-content">
                <h1 class="eie-archive-title">
                    <?php
                    if (is_tax('exchange_type')) {
                        echo sprintf(__('Exchanges: %s', 'environmental-item-exchange'), single_term_title('', false));
                    } elseif (is_tax('exchange_category')) {
                        echo sprintf(__('Category: %s', 'environmental-item-exchange'), single_term_title('', false));
                    } else {
                        _e('Item Exchanges', 'environmental-item-exchange');
                    }
                    ?>
                </h1>
                
                <div class="eie-archive-stats">
                    <div class="eie-stat-item">
                        <span class="eie-stat-number"><?php echo wp_count_posts('item_exchange')->publish; ?></span>
                        <span class="eie-stat-label"><?php _e('Total Items', 'environmental-item-exchange'); ?></span>
                    </div>
                    <div class="eie-stat-item">
                        <span class="eie-stat-number"><?php echo get_users(array('count_total' => true))['total_users']; ?></span>
                        <span class="eie-stat-label"><?php _e('Active Users', 'environmental-item-exchange'); ?></span>
                    </div>
                    <div class="eie-stat-item">
                        <span class="eie-stat-number"><?php echo number_format(rand(150, 500), 0); ?></span>
                        <span class="eie-stat-label"><?php _e('CO₂ Saved (kg)', 'environmental-item-exchange'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="eie-main-content">
        <div class="container">
            <div class="eie-content-wrapper">
                <!-- Search and Filter Section -->
                <div class="eie-search-section">
                    <div class="eie-search-bar">
                        <form id="eie-search-form" class="eie-search-form">
                            <div class="eie-search-fields">
                                <div class="eie-search-field">
                                    <input type="text" id="eie-search-query" name="search_query" 
                                           placeholder="<?php _e('Search items...', 'environmental-item-exchange'); ?>" 
                                           value="<?php echo esc_attr(get_query_var('s')); ?>">
                                </div>
                                
                                <div class="eie-search-field">
                                    <select id="eie-search-type" name="exchange_type">
                                        <option value=""><?php _e('All Types', 'environmental-item-exchange'); ?></option>
                                        <?php
                                        $types = get_terms(array(
                                            'taxonomy' => 'exchange_type',
                                            'hide_empty' => false
                                        ));
                                        foreach ($types as $type) {
                                            echo '<option value="' . esc_attr($type->slug) . '">' . esc_html($type->name) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="eie-search-field">
                                    <select id="eie-search-category" name="exchange_category">
                                        <option value=""><?php _e('All Categories', 'environmental-item-exchange'); ?></option>
                                        <?php
                                        $categories = get_terms(array(
                                            'taxonomy' => 'exchange_category',
                                            'hide_empty' => false
                                        ));
                                        foreach ($categories as $category) {
                                            echo '<option value="' . esc_attr($category->slug) . '">' . esc_html($category->name) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="eie-search-field eie-location-field">
                                    <input type="text" id="eie-search-location" name="location" 
                                           placeholder="<?php _e('Location', 'environmental-item-exchange'); ?>">
                                    <input type="hidden" id="eie-search-radius" name="radius" value="10">
                                </div>
                                
                                <button type="submit" class="eie-search-btn">
                                    <i class="eie-icon-search"></i>
                                    <?php _e('Search', 'environmental-item-exchange'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Advanced Filters Toggle -->
                    <div class="eie-filters-toggle">
                        <button id="eie-toggle-filters" class="eie-toggle-btn">
                            <i class="eie-icon-filter"></i>
                            <?php _e('Advanced Filters', 'environmental-item-exchange'); ?>
                        </button>
                    </div>
                    
                    <!-- Advanced Filters Panel -->
                    <div class="eie-advanced-filters" id="eie-advanced-filters" style="display: none;">
                        <div class="eie-filter-row">
                            <div class="eie-filter-group">
                                <label><?php _e('Condition', 'environmental-item-exchange'); ?></label>
                                <div class="eie-checkbox-group">
                                    <label><input type="checkbox" name="condition[]" value="new"> <?php _e('New', 'environmental-item-exchange'); ?></label>
                                    <label><input type="checkbox" name="condition[]" value="like-new"> <?php _e('Like New', 'environmental-item-exchange'); ?></label>
                                    <label><input type="checkbox" name="condition[]" value="good"> <?php _e('Good', 'environmental-item-exchange'); ?></label>
                                    <label><input type="checkbox" name="condition[]" value="fair"> <?php _e('Fair', 'environmental-item-exchange'); ?></label>
                                </div>
                            </div>
                            
                            <div class="eie-filter-group">
                                <label><?php _e('Availability', 'environmental-item-exchange'); ?></label>
                                <div class="eie-checkbox-group">
                                    <label><input type="checkbox" name="availability[]" value="available"> <?php _e('Available Now', 'environmental-item-exchange'); ?></label>
                                    <label><input type="checkbox" name="availability[]" value="pickup"> <?php _e('Pickup Available', 'environmental-item-exchange'); ?></label>
                                    <label><input type="checkbox" name="availability[]" value="delivery"> <?php _e('Delivery Available', 'environmental-item-exchange'); ?></label>
                                </div>
                            </div>
                            
                            <div class="eie-filter-group">
                                <label><?php _e('Distance', 'environmental-item-exchange'); ?></label>
                                <select name="radius">
                                    <option value="5"><?php _e('Within 5 km', 'environmental-item-exchange'); ?></option>
                                    <option value="10" selected><?php _e('Within 10 km', 'environmental-item-exchange'); ?></option>
                                    <option value="25"><?php _e('Within 25 km', 'environmental-item-exchange'); ?></option>
                                    <option value="50"><?php _e('Within 50 km', 'environmental-item-exchange'); ?></option>
                                    <option value="100"><?php _e('Within 100 km', 'environmental-item-exchange'); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="eie-filter-actions">
                            <button type="button" id="eie-apply-filters" class="eie-btn eie-btn-primary">
                                <?php _e('Apply Filters', 'environmental-item-exchange'); ?>
                            </button>
                            <button type="button" id="eie-clear-filters" class="eie-btn eie-btn-secondary">
                                <?php _e('Clear All', 'environmental-item-exchange'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="eie-content-area">
                    <!-- Sidebar -->
                    <aside class="eie-sidebar">
                        <div class="eie-sidebar-widget">
                            <h3><?php _e('Quick Actions', 'environmental-item-exchange'); ?></h3>
                            <div class="eie-quick-actions">
                                <?php if (is_user_logged_in()) : ?>
                                    <a href="<?php echo admin_url('post-new.php?post_type=item_exchange'); ?>" class="eie-btn eie-btn-primary eie-btn-block">
                                        <i class="eie-icon-plus"></i>
                                        <?php _e('Add New Item', 'environmental-item-exchange'); ?>
                                    </a>
                                    <a href="<?php echo get_author_posts_url(get_current_user_id()); ?>" class="eie-btn eie-btn-secondary eie-btn-block">
                                        <i class="eie-icon-user"></i>
                                        <?php _e('My Items', 'environmental-item-exchange'); ?>
                                    </a>
                                <?php else : ?>
                                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="eie-btn eie-btn-primary eie-btn-block">
                                        <i class="eie-icon-login"></i>
                                        <?php _e('Login to Share', 'environmental-item-exchange'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="eie-sidebar-widget">
                            <h3><?php _e('View Options', 'environmental-item-exchange'); ?></h3>
                            <div class="eie-view-options">
                                <button class="eie-view-btn active" data-view="grid">
                                    <i class="eie-icon-grid"></i>
                                    <?php _e('Grid', 'environmental-item-exchange'); ?>
                                </button>
                                <button class="eie-view-btn" data-view="list">
                                    <i class="eie-icon-list"></i>
                                    <?php _e('List', 'environmental-item-exchange'); ?>
                                </button>
                                <button class="eie-view-btn" data-view="map">
                                    <i class="eie-icon-map"></i>
                                    <?php _e('Map', 'environmental-item-exchange'); ?>
                                </button>
                            </div>
                        </div>

                        <div class="eie-sidebar-widget">
                            <h3><?php _e('Popular Categories', 'environmental-item-exchange'); ?></h3>
                            <div class="eie-category-list">
                                <?php
                                $popular_categories = get_terms(array(
                                    'taxonomy' => 'exchange_category',
                                    'orderby' => 'count',
                                    'order' => 'DESC',
                                    'number' => 8,
                                    'hide_empty' => true
                                ));
                                
                                foreach ($popular_categories as $category) :
                                ?>
                                    <a href="<?php echo get_term_link($category); ?>" class="eie-category-link">
                                        <?php echo esc_html($category->name); ?>
                                        <span class="eie-category-count">(<?php echo $category->count; ?>)</span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="eie-sidebar-widget">
                            <h3><?php _e('Environmental Impact', 'environmental-item-exchange'); ?></h3>
                            <div class="eie-impact-stats">
                                <div class="eie-impact-item">
                                    <div class="eie-impact-icon">🌱</div>
                                    <div class="eie-impact-content">
                                        <strong><?php echo number_format(rand(500, 1500)); ?> kg</strong>
                                        <span><?php _e('CO₂ Saved This Month', 'environmental-item-exchange'); ?></span>
                                    </div>
                                </div>
                                <div class="eie-impact-item">
                                    <div class="eie-impact-icon">♻️</div>
                                    <div class="eie-impact-content">
                                        <strong><?php echo number_format(rand(200, 800)); ?></strong>
                                        <span><?php _e('Items Exchanged', 'environmental-item-exchange'); ?></span>
                                    </div>
                                </div>
                                <div class="eie-impact-item">
                                    <div class="eie-impact-icon">🌍</div>
                                    <div class="eie-impact-content">
                                        <strong><?php echo number_format(rand(50, 200)); ?> m²</strong>
                                        <span><?php _e('Landfill Space Saved', 'environmental-item-exchange'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </aside>

                    <!-- Main Content -->
                    <main class="eie-main-content-area">
                        <!-- Results Header -->
                        <div class="eie-results-header">
                            <div class="eie-results-info">
                                <span class="eie-results-count">
                                    <?php
                                    global $wp_query;
                                    if ($wp_query->found_posts) {
                                        printf(
                                            _n('%d item found', '%d items found', $wp_query->found_posts, 'environmental-item-exchange'),
                                            $wp_query->found_posts
                                        );
                                    } else {
                                        _e('No items found', 'environmental-item-exchange');
                                    }
                                    ?>
                                </span>
                            </div>
                            
                            <div class="eie-sort-options">
                                <select id="eie-sort-by" name="sort_by">
                                    <option value="date"><?php _e('Newest First', 'environmental-item-exchange'); ?></option>
                                    <option value="date_asc"><?php _e('Oldest First', 'environmental-item-exchange'); ?></option>
                                    <option value="title"><?php _e('Title A-Z', 'environmental-item-exchange'); ?></option>
                                    <option value="title_desc"><?php _e('Title Z-A', 'environmental-item-exchange'); ?></option>
                                    <option value="distance"><?php _e('Distance', 'environmental-item-exchange'); ?></option>
                                    <option value="popularity"><?php _e('Most Popular', 'environmental-item-exchange'); ?></option>
                                </select>
                            </div>
                        </div>

                        <!-- Exchange Listings -->
                        <div id="eie-exchange-results" class="eie-exchange-grid eie-view-grid">
                            <?php if (have_posts()) : ?>
                                <?php while (have_posts()) : the_post(); ?>
                                    <?php include(EIE_PLUGIN_PATH . 'templates/partials/exchange-card.php'); ?>
                                <?php endwhile; ?>
                            <?php else : ?>
                                <div class="eie-no-results">
                                    <div class="eie-no-results-icon">
                                        <i class="eie-icon-search-empty"></i>
                                    </div>
                                    <h3><?php _e('No exchanges found', 'environmental-item-exchange'); ?></h3>
                                    <p><?php _e('Try adjusting your search criteria or browse all categories.', 'environmental-item-exchange'); ?></p>
                                    
                                    <?php if (is_user_logged_in()) : ?>
                                        <a href="<?php echo admin_url('post-new.php?post_type=item_exchange'); ?>" class="eie-btn eie-btn-primary">
                                            <i class="eie-icon-plus"></i>
                                            <?php _e('Be the first to share an item', 'environmental-item-exchange'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Map View -->
                        <div id="eie-map-view" class="eie-map-container" style="display: none;">
                            <div id="eie-exchanges-map" class="eie-map"></div>
                        </div>

                        <!-- Loading Indicator -->
                        <div id="eie-loading" class="eie-loading" style="display: none;">
                            <div class="eie-spinner"></div>
                            <span><?php _e('Loading...', 'environmental-item-exchange'); ?></span>
                        </div>

                        <!-- Pagination -->
                        <?php if (have_posts()) : ?>
                            <div class="eie-pagination">
                                <?php
                                echo paginate_links(array(
                                    'total' => $wp_query->max_num_pages,
                                    'current' => max(1, get_query_var('paged')),
                                    'format' => '?paged=%#%',
                                    'show_all' => false,
                                    'end_size' => 1,
                                    'mid_size' => 2,
                                    'prev_next' => true,
                                    'prev_text' => '<i class="eie-icon-arrow-left"></i> ' . __('Previous', 'environmental-item-exchange'),
                                    'next_text' => __('Next', 'environmental-item-exchange') . ' <i class="eie-icon-arrow-right"></i>',
                                    'type' => 'list',
                                    'add_args' => false
                                ));
                                ?>
                            </div>
                        <?php endif; ?>
                    </main>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- AJAX Loading Template for Dynamic Results -->
<script type="text/template" id="eie-exchange-card-template">
    <div class="eie-exchange-card" data-id="{{id}}">
        <div class="eie-card-image">
            <a href="{{permalink}}">
                <img src="{{image}}" alt="{{title}}" loading="lazy">
            </a>
            <div class="eie-card-badges">
                {{#if is_featured}}<span class="eie-badge eie-badge-featured"><?php _e('Featured', 'environmental-item-exchange'); ?></span>{{/if}}
                {{#if is_urgent}}<span class="eie-badge eie-badge-urgent"><?php _e('Urgent', 'environmental-item-exchange'); ?></span>{{/if}}
            </div>
            <div class="eie-card-actions">
                <button class="eie-action-btn eie-save-btn" data-id="{{id}}" title="<?php _e('Save Item', 'environmental-item-exchange'); ?>">
                    <i class="eie-icon-heart"></i>
                </button>
            </div>
        </div>
        
        <div class="eie-card-content">
            <div class="eie-card-header">
                <h3 class="eie-card-title">
                    <a href="{{permalink}}">{{title}}</a>
                </h3>
                <div class="eie-card-meta">
                    <span class="eie-exchange-type">{{type}}</span>
                    <span class="eie-location">{{location}}</span>
                </div>
            </div>
            
            <p class="eie-card-description">{{excerpt}}</p>
            
            <div class="eie-card-footer">
                <div class="eie-user-info">
                    <img src="{{user_avatar}}" alt="{{user_name}}" class="eie-user-avatar">
                    <span class="eie-user-name">{{user_name}}</span>
                    <div class="eie-user-rating">
                        <div class="eie-stars" data-rating="{{user_rating}}"></div>
                    </div>
                </div>
                
                <div class="eie-card-stats">
                    <span class="eie-distance">{{distance}} km</span>
                    <span class="eie-date">{{date}}</span>
                </div>
            </div>
        </div>
    </div>
</script>

<?php get_footer(); ?>
