<?php
/**
 * The template for displaying archive pages
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

                        <!-- Archive Description -->
                        <?php
                        $archive_description = get_the_archive_description();
                        if ( $archive_description ) :
                        ?>
                            <div class="archive-description">
                                <?php echo wp_kses_post( $archive_description ); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Environmental Archive Stats -->
                        <div class="archive-stats">
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-newspaper"></i>
                                    </div>
                                    <div class="stat-content">
                                        <span class="stat-number"><?php echo esc_html($wp_query->found_posts); ?></span>
                                        <span class="stat-label">
                                            <?php 
                                            if (is_category()) {
                                                esc_html_e('Articles in Category', 'environmental-platform');
                                            } elseif (is_tag()) {
                                                esc_html_e('Articles with Tag', 'environmental-platform');
                                            } elseif (is_author()) {
                                                esc_html_e('Articles by Author', 'environmental-platform');
                                            } elseif (is_date()) {
                                                esc_html_e('Articles in Period', 'environmental-platform');
                                            } else {
                                                esc_html_e('Total Articles', 'environmental-platform');
                                            }
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <?php if (is_category()) : 
                                    $category = get_queried_object();
                                    $avg_score = get_term_meta($category->term_id, '_avg_environmental_score', true);
                                    if ($avg_score) :
                                ?>
                                    <div class="stat-card">
                                        <div class="stat-icon">
                                            <i class="fas fa-leaf"></i>
                                        </div>
                                        <div class="stat-content">
                                            <span class="stat-number"><?php echo esc_html($avg_score); ?>/100</span>
                                            <span class="stat-label"><?php esc_html_e('Avg Environmental Score', 'environmental-platform'); ?></span>
                                        </div>
                                    </div>
                                <?php endif; endif; ?>

                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="stat-content">
                                        <span class="stat-number">
                                            <?php
                                            // Calculate total reading time
                                            $total_reading_time = 0;
                                            $temp_query = new WP_Query($wp_query->query);
                                            while ($temp_query->have_posts()) {
                                                $temp_query->the_post();
                                                $reading_time = environmental_platform_get_reading_time();
                                                $total_reading_time += intval($reading_time);
                                            }
                                            wp_reset_postdata();
                                            echo esc_html($total_reading_time);
                                            ?>
                                        </span>
                                        <span class="stat-label"><?php esc_html_e('Total Reading Minutes', 'environmental-platform'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filter and Sort Options -->
                        <div class="archive-filters">
                            <div class="filter-controls">
                                <div class="sort-options">
                                    <label for="sort-posts"><?php esc_html_e('Sort by:', 'environmental-platform'); ?></label>
                                    <select id="sort-posts" onchange="sortPosts(this.value)">
                                        <option value="date"><?php esc_html_e('Latest First', 'environmental-platform'); ?></option>
                                        <option value="title"><?php esc_html_e('Title A-Z', 'environmental-platform'); ?></option>
                                        <option value="environmental_score"><?php esc_html_e('Environmental Score', 'environmental-platform'); ?></option>
                                        <option value="reading_time"><?php esc_html_e('Reading Time', 'environmental-platform'); ?></option>
                                    </select>
                                </div>
                                
                                <div class="view-options">
                                    <button class="view-toggle active" data-view="grid" title="Grid View">
                                        <i class="fas fa-th"></i>
                                    </button>
                                    <button class="view-toggle" data-view="list" title="List View">
                                        <i class="fas fa-list"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Posts Grid/List -->
                        <div class="posts-container" id="posts-container">
                            <div class="posts-grid active" id="posts-grid">
                                <?php
                                while ( have_posts() ) :
                                    the_post();
                                    get_template_part( 'template-parts/content', get_post_type() );
                                endwhile;
                                ?>
                            </div>
                            
                            <div class="posts-list" id="posts-list">
                                <?php
                                // Reset query for list view
                                rewind_posts();
                                while ( have_posts() ) :
                                    the_post();
                                    get_template_part( 'template-parts/content', 'list' );
                                endwhile;
                                ?>
                            </div>
                        </div>

                        <?php
                        // Pagination
                        the_posts_pagination(
                            array(
                                'mid_size'  => 2,
                                'prev_text' => '<i class="fas fa-chevron-left"></i> ' . __('Previous', 'environmental-platform'),
                                'next_text' => __('Next', 'environmental-platform') . ' <i class="fas fa-chevron-right"></i>',
                            )
                        );
                        ?>

                    <?php else : ?>

                        <!-- No Posts Found -->
                        <div class="no-posts-found">
                            <div class="no-posts-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <h2><?php esc_html_e('No posts found', 'environmental-platform'); ?></h2>
                            <p><?php esc_html_e('Sorry, but nothing matched your search terms. Please try again with different keywords.', 'environmental-platform'); ?></p>
                            
                            <div class="suggested-actions">
                                <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary">
                                    <i class="fas fa-home"></i> <?php esc_html_e('Back to Home', 'environmental-platform'); ?>
                                </a>
                                <a href="<?php echo esc_url(home_url('/environmental-tips')); ?>" class="btn btn-secondary">
                                    <i class="fas fa-leaf"></i> <?php esc_html_e('Browse Tips', 'environmental-platform'); ?>
                                </a>
                            </div>
                        </div>

                    <?php endif; ?>

                </div>

                <!-- Sidebar -->
                <div class="col-lg-4 col-md-12">
                    <aside id="secondary" class="widget-area">
                        
                        <!-- Archive Navigation -->
                        <div class="widget archive-navigation">
                            <h3 class="widget-title"><?php esc_html_e('Browse Archives', 'environmental-platform'); ?></h3>
                            
                            <!-- Categories -->
                            <div class="archive-section">
                                <h4><?php esc_html_e('Categories', 'environmental-platform'); ?></h4>
                                <ul class="category-list">
                                    <?php
                                    wp_list_categories(array(
                                        'title_li' => '',
                                        'show_count' => true,
                                        'orderby' => 'name',
                                        'order' => 'ASC'
                                    ));
                                    ?>
                                </ul>
                            </div>

                            <!-- Monthly Archives -->
                            <div class="archive-section">
                                <h4><?php esc_html_e('By Month', 'environmental-platform'); ?></h4>
                                <ul class="monthly-archives">
                                    <?php
                                    wp_get_archives(array(
                                        'type' => 'monthly',
                                        'limit' => 12,
                                        'show_post_count' => true
                                    ));
                                    ?>
                                </ul>
                            </div>

                            <!-- Popular Tags -->
                            <div class="archive-section">
                                <h4><?php esc_html_e('Popular Tags', 'environmental-platform'); ?></h4>
                                <div class="tag-cloud">
                                    <?php
                                    wp_tag_cloud(array(
                                        'smallest' => 12,
                                        'largest' => 22,
                                        'unit' => 'px',
                                        'number' => 20,
                                        'orderby' => 'count',
                                        'order' => 'DESC'
                                    ));
                                    ?>
                                </div>
                            </div>
                        </div>

                        <?php
                        // Archive-specific sidebar
                        if (is_active_sidebar('archive-sidebar')) {
                            dynamic_sidebar('archive-sidebar');
                        }
                        ?>

                    </aside>
                </div>
            </div>
        </div>

    </main>
</div>

<script>
// Archive page functionality
document.addEventListener('DOMContentLoaded', function() {
    // View toggle functionality
    const viewToggles = document.querySelectorAll('.view-toggle');
    const postsGrid = document.getElementById('posts-grid');
    const postsList = document.getElementById('posts-list');

    viewToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const view = this.dataset.view;
            
            // Update active states
            viewToggles.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Show/hide views
            if (view === 'grid') {
                postsGrid.classList.add('active');
                postsList.classList.remove('active');
            } else {
                postsGrid.classList.remove('active');
                postsList.classList.add('active');
            }
        });
    });
});

function sortPosts(sortBy) {
    // This would typically be handled via AJAX in a production environment
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('orderby', sortBy);
    window.location.search = urlParams;
}
</script>

<?php get_footer(); ?>
