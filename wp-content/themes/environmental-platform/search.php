<?php
/**
 * The template for displaying search results pages
 *
 * @package Environmental_Platform
 */

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">

        <?php get_template_part('template-parts/page-header'); ?>

        <div class="container">
            <div class="row">
                <div class="col-lg-8 col-md-12">
                    
                    <?php if ( have_posts() ) : ?>

                        <!-- Search Results Header -->
                        <div class="search-results-header">
                            <div class="search-summary">
                                <h2>
                                    <?php
                                    printf(
                                        esc_html__('Search Results for: %s', 'environmental-platform'),
                                        '<span class="search-term">' . get_search_query() . '</span>'
                                    );
                                    ?>
                                </h2>
                                <p class="search-count">
                                    <?php
                                    printf(
                                        esc_html__('Found %d results matching your search', 'environmental-platform'),
                                        $wp_query->found_posts
                                    );
                                    ?>
                                </p>
                            </div>

                            <!-- Search Statistics -->
                            <div class="search-stats">
                                <div class="stats-row">
                                    <div class="stat-item">
                                        <span class="stat-label"><?php esc_html_e('Articles:', 'environmental-platform'); ?></span>
                                        <span class="stat-value"><?php echo esc_html($wp_query->found_posts); ?></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label"><?php esc_html_e('Est. Reading Time:', 'environmental-platform'); ?></span>
                                        <span class="stat-value">
                                            <?php
                                            // Calculate total reading time for search results
                                            $total_reading_time = 0;
                                            $temp_query = new WP_Query($wp_query->query);
                                            while ($temp_query->have_posts()) {
                                                $temp_query->the_post();
                                                $reading_time = environmental_platform_get_reading_time();
                                                $total_reading_time += intval($reading_time);
                                            }
                                            wp_reset_postdata();
                                            echo esc_html($total_reading_time) . ' ' . esc_html__('min', 'environmental-platform');
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Search Filters -->
                        <div class="search-filters">
                            <div class="filter-tabs">
                                <button class="filter-tab active" data-filter="all">
                                    <?php esc_html_e('All Results', 'environmental-platform'); ?>
                                    <span class="count"><?php echo esc_html($wp_query->found_posts); ?></span>
                                </button>
                                <button class="filter-tab" data-filter="posts">
                                    <?php esc_html_e('Articles', 'environmental-platform'); ?>
                                    <span class="count" id="posts-count">0</span>
                                </button>
                                <button class="filter-tab" data-filter="pages">
                                    <?php esc_html_e('Pages', 'environmental-platform'); ?>
                                    <span class="count" id="pages-count">0</span>
                                </button>
                            </div>
                            
                            <div class="sort-options">
                                <select id="sort-results" onchange="sortResults(this.value)">
                                    <option value="relevance"><?php esc_html_e('Most Relevant', 'environmental-platform'); ?></option>
                                    <option value="date"><?php esc_html_e('Most Recent', 'environmental-platform'); ?></option>
                                    <option value="title"><?php esc_html_e('Title A-Z', 'environmental-platform'); ?></option>
                                    <option value="environmental_score"><?php esc_html_e('Environmental Score', 'environmental-platform'); ?></option>
                                </select>
                            </div>
                        </div>

                        <!-- Search Results -->
                        <div class="search-results">
                            <?php
                            $post_count = 0;
                            $page_count = 0;
                            
                            while ( have_posts() ) :
                                the_post();
                                
                                // Count post types
                                if (get_post_type() === 'post') {
                                    $post_count++;
                                } elseif (get_post_type() === 'page') {
                                    $page_count++;
                                }
                                
                                get_template_part( 'template-parts/content', 'search' );
                            endwhile;
                            ?>
                        </div>

                        <!-- Search Pagination -->
                        <?php
                        the_posts_pagination(
                            array(
                                'mid_size'  => 2,
                                'prev_text' => '<i class="fas fa-chevron-left"></i> ' . __('Previous', 'environmental-platform'),
                                'next_text' => __('Next', 'environmental-platform') . ' <i class="fas fa-chevron-right"></i>',
                                'before_page_number' => '<span class="screen-reader-text">' . __('Page', 'environmental-platform') . ' </span>',
                            )
                        );
                        ?>

                        <!-- Related Searches -->
                        <div class="related-searches">
                            <h3><?php esc_html_e('Related Environmental Topics', 'environmental-platform'); ?></h3>
                            <div class="related-tags">
                                <?php
                                $related_terms = array(
                                    'climate-change', 'renewable-energy', 'sustainability', 
                                    'recycling', 'green-living', 'carbon-footprint',
                                    'environmental-protection', 'eco-friendly', 'conservation'
                                );
                                
                                foreach ($related_terms as $term) {
                                    $search_url = home_url('/?s=' . urlencode($term));
                                    echo '<a href="' . esc_url($search_url) . '" class="related-tag">' . esc_html(str_replace('-', ' ', $term)) . '</a>';
                                }
                                ?>
                            </div>
                        </div>

                        <script>
                        // Update post type counts
                        document.addEventListener('DOMContentLoaded', function() {
                            document.getElementById('posts-count').textContent = '<?php echo esc_js($post_count); ?>';
                            document.getElementById('pages-count').textContent = '<?php echo esc_js($page_count); ?>';
                        });
                        </script>

                    <?php else : ?>

                        <!-- No Search Results -->
                        <div class="no-search-results">
                            <div class="no-results-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <h2><?php esc_html_e('No results found', 'environmental-platform'); ?></h2>
                            <p>
                                <?php
                                printf(
                                    esc_html__('Sorry, but nothing matched your search terms "%s". Please try again with different keywords.', 'environmental-platform'),
                                    '<strong>' . get_search_query() . '</strong>'
                                );
                                ?>
                            </p>

                            <!-- Search Suggestions -->
                            <div class="search-suggestions">
                                <h3><?php esc_html_e('Search Tips:', 'environmental-platform'); ?></h3>
                                <ul>
                                    <li><?php esc_html_e('Try different keywords', 'environmental-platform'); ?></li>
                                    <li><?php esc_html_e('Use more general terms', 'environmental-platform'); ?></li>
                                    <li><?php esc_html_e('Check your spelling', 'environmental-platform'); ?></li>
                                    <li><?php esc_html_e('Try environmental synonyms', 'environmental-platform'); ?></li>
                                </ul>
                            </div>

                            <!-- Alternative Search -->
                            <div class="alternative-search">
                                <h3><?php esc_html_e('Try Another Search', 'environmental-platform'); ?></h3>
                                <form role="search" method="get" class="search-form alternative-search-form" action="<?php echo esc_url(home_url('/')); ?>">
                                    <div class="search-input-group">
                                        <input type="search" class="search-field" placeholder="<?php echo esc_attr__('Search environmental topics...', 'environmental-platform'); ?>" value="" name="s" />
                                        <button type="submit" class="search-submit">
                                            <i class="fas fa-search"></i>
                                            <span class="screen-reader-text"><?php echo esc_html__('Search', 'environmental-platform'); ?></span>
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Popular Content -->
                            <div class="popular-content">
                                <h3><?php esc_html_e('Popular Environmental Topics', 'environmental-platform'); ?></h3>
                                <div class="popular-links">
                                    <?php
                                    $popular_categories = get_categories(array(
                                        'orderby' => 'count',
                                        'order' => 'DESC',
                                        'number' => 6
                                    ));
                                    
                                    foreach ($popular_categories as $category) {
                                        echo '<a href="' . esc_url(get_category_link($category->term_id)) . '" class="popular-link">';
                                        echo '<i class="fas fa-leaf"></i> ' . esc_html($category->name);
                                        echo ' <span class="count">(' . esc_html($category->count) . ')</span>';
                                        echo '</a>';
                                    }
                                    ?>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="no-results-actions">
                                <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary">
                                    <i class="fas fa-home"></i> <?php esc_html_e('Back to Home', 'environmental-platform'); ?>
                                </a>
                                <a href="<?php echo esc_url(home_url('/environmental-tips')); ?>" class="btn btn-secondary">
                                    <i class="fas fa-leaf"></i> <?php esc_html_e('Browse Tips', 'environmental-platform'); ?>
                                </a>
                                <a href="<?php echo esc_url(home_url('/contact')); ?>" class="btn btn-outline">
                                    <i class="fas fa-envelope"></i> <?php esc_html_e('Contact Us', 'environmental-platform'); ?>
                                </a>
                            </div>
                        </div>

                    <?php endif; ?>

                </div>

                <!-- Search Sidebar -->
                <div class="col-lg-4 col-md-12">
                    <aside id="secondary" class="widget-area search-sidebar">
                        
                        <!-- Advanced Search -->
                        <div class="widget advanced-search">
                            <h3 class="widget-title"><?php esc_html_e('Refine Your Search', 'environmental-platform'); ?></h3>
                            <form class="advanced-search-form" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                                <div class="form-group">
                                    <label for="search-keywords"><?php esc_html_e('Keywords:', 'environmental-platform'); ?></label>
                                    <input type="text" id="search-keywords" name="s" value="<?php echo get_search_query(); ?>" />
                                </div>
                                
                                <div class="form-group">
                                    <label for="search-category"><?php esc_html_e('Category:', 'environmental-platform'); ?></label>
                                    <select id="search-category" name="cat">
                                        <option value=""><?php esc_html_e('All Categories', 'environmental-platform'); ?></option>
                                        <?php
                                        wp_list_categories(array(
                                            'title_li' => '',
                                            'show_option_none' => '',
                                            'style' => 'option',
                                            'show_count' => true
                                        ));
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="search-date"><?php esc_html_e('Date Range:', 'environmental-platform'); ?></label>
                                    <select id="search-date" name="date_range">
                                        <option value=""><?php esc_html_e('Any Time', 'environmental-platform'); ?></option>
                                        <option value="week"><?php esc_html_e('Past Week', 'environmental-platform'); ?></option>
                                        <option value="month"><?php esc_html_e('Past Month', 'environmental-platform'); ?></option>
                                        <option value="year"><?php esc_html_e('Past Year', 'environmental-platform'); ?></option>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-search"></i> <?php esc_html_e('Search', 'environmental-platform'); ?>
                                </button>
                            </form>
                        </div>

                        <!-- Environmental Categories -->
                        <div class="widget environmental-categories">
                            <h3 class="widget-title"><?php esc_html_e('Environmental Categories', 'environmental-platform'); ?></h3>
                            <ul class="category-list">
                                <?php
                                $env_categories = get_categories(array(
                                    'orderby' => 'name',
                                    'order' => 'ASC',
                                    'hide_empty' => true
                                ));
                                
                                foreach ($env_categories as $category) {
                                    $icon_class = 'fas fa-leaf'; // Default icon
                                    
                                    // Assign specific icons based on category
                                    if (strpos(strtolower($category->name), 'energy') !== false) {
                                        $icon_class = 'fas fa-bolt';
                                    } elseif (strpos(strtolower($category->name), 'water') !== false) {
                                        $icon_class = 'fas fa-tint';
                                    } elseif (strpos(strtolower($category->name), 'recycl') !== false) {
                                        $icon_class = 'fas fa-recycle';
                                    } elseif (strpos(strtolower($category->name), 'climate') !== false) {
                                        $icon_class = 'fas fa-globe';
                                    }
                                    
                                    echo '<li>';
                                    echo '<a href="' . esc_url(get_category_link($category->term_id)) . '">';
                                    echo '<i class="' . esc_attr($icon_class) . '"></i> ';
                                    echo esc_html($category->name);
                                    echo ' <span class="count">(' . esc_html($category->count) . ')</span>';
                                    echo '</a>';
                                    echo '</li>';
                                }
                                ?>
                            </ul>
                        </div>

                        <?php
                        // Search-specific sidebar
                        if (is_active_sidebar('search-sidebar')) {
                            dynamic_sidebar('search-sidebar');
                        }
                        ?>

                    </aside>
                </div>
            </div>
        </div>

    </main>
</div>

<script>
// Search page functionality
document.addEventListener('DOMContentLoaded', function() {
    // Filter tabs functionality
    const filterTabs = document.querySelectorAll('.filter-tab');
    const searchResults = document.querySelectorAll('.search-result-item');

    filterTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const filter = this.dataset.filter;
            
            // Update active states
            filterTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Filter results
            searchResults.forEach(result => {
                if (filter === 'all' || result.dataset.postType === filter) {
                    result.style.display = 'block';
                } else {
                    result.style.display = 'none';
                }
            });
        });
    });
});

function sortResults(sortBy) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('orderby', sortBy);
    window.location.search = urlParams;
}
</script>

<?php get_footer(); ?>
