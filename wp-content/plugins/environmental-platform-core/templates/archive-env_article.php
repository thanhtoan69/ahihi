<?php
/**
 * Archive Environmental Articles Template
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

get_header(); ?>

<div class="env-articles-archive">
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <header class="archive-header">
                    <h1 class="archive-title">
                        <i class="fa fa-leaf"></i>
                        <?php _e('Environmental Articles', 'environmental-platform-core'); ?>
                    </h1>
                    <p class="archive-description">
                        <?php _e('Stay updated with the latest environmental news, research, and insights from our experts.', 'environmental-platform-core'); ?>
                    </p>
                </header>
                
                <!-- Filter Controls -->
                <div class="archive-filters">
                    <div class="filter-group">
                        <label for="category-filter"><?php _e('Filter by Category:', 'environmental-platform-core'); ?></label>
                        <select id="category-filter" class="form-control">
                            <option value=""><?php _e('All Categories', 'environmental-platform-core'); ?></option>
                            <?php
                            $categories = get_terms(array(
                                'taxonomy' => 'env_category',
                                'hide_empty' => true
                            ));
                            foreach ($categories as $category) {
                                $selected = (isset($_GET['env_category']) && $_GET['env_category'] == $category->slug) ? 'selected' : '';
                                echo '<option value="' . esc_attr($category->slug) . '" ' . $selected . '>' . esc_html($category->name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="impact-filter"><?php _e('Impact Level:', 'environmental-platform-core'); ?></label>
                        <select id="impact-filter" class="form-control">
                            <option value=""><?php _e('All Levels', 'environmental-platform-core'); ?></option>
                            <?php
                            $impact_levels = get_terms(array(
                                'taxonomy' => 'impact_level',
                                'hide_empty' => true
                            ));
                            foreach ($impact_levels as $level) {
                                $selected = (isset($_GET['impact_level']) && $_GET['impact_level'] == $level->slug) ? 'selected' : '';
                                echo '<option value="' . esc_attr($level->slug) . '" ' . $selected . '>' . esc_html($level->name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="sort-filter"><?php _e('Sort by:', 'environmental-platform-core'); ?></label>
                        <select id="sort-filter" class="form-control">
                            <option value="date"><?php _e('Latest First', 'environmental-platform-core'); ?></option>
                            <option value="title"><?php _e('Title A-Z', 'environmental-platform-core'); ?></option>
                            <option value="popular"><?php _e('Most Popular', 'environmental-platform-core'); ?></option>
                        </select>
                    </div>
                </div>
                
                <!-- Articles Grid -->
                <div class="articles-grid" id="articles-container">
                    <?php if (have_posts()) : ?>
                        <?php while (have_posts()) : the_post(); ?>
                            <article class="article-card">
                                <?php if (has_post_thumbnail()) : ?>
                                    <div class="article-image">
                                        <a href="<?php the_permalink(); ?>">
                                            <?php the_post_thumbnail('medium', array('class' => 'img-responsive')); ?>
                                        </a>
                                        
                                        <!-- Impact Level Badge -->
                                        <?php 
                                        $impact_terms = get_the_terms(get_the_ID(), 'impact_level');
                                        if ($impact_terms && !is_wp_error($impact_terms)) :
                                            $impact = $impact_terms[0];
                                        ?>
                                            <span class="impact-badge impact-<?php echo esc_attr($impact->slug); ?>">
                                                <?php echo esc_html($impact->name); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="article-content">
                                    <div class="article-meta">
                                        <?php if (has_term('', 'env_category')) : ?>
                                            <span class="category">
                                                <?php echo get_the_term_list(get_the_ID(), 'env_category', '', ', '); ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <span class="date">
                                            <i class="fa fa-calendar"></i>
                                            <?php echo get_the_date(); ?>
                                        </span>
                                    </div>
                                    
                                    <h3 class="article-title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h3>
                                    
                                    <div class="article-excerpt">
                                        <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
                                    </div>
                                    
                                    <!-- Environmental Data Preview -->
                                    <?php 
                                    $env_location = get_post_meta(get_the_ID(), '_env_location', true);
                                    $env_impact_score = get_post_meta(get_the_ID(), '_env_impact_score', true);
                                    ?>
                                    
                                    <?php if ($env_location || $env_impact_score) : ?>
                                        <div class="env-data-preview">
                                            <?php if ($env_location) : ?>
                                                <span class="location">
                                                    <i class="fa fa-map-marker"></i>
                                                    <?php echo esc_html($env_location); ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <?php if ($env_impact_score) : ?>
                                                <span class="impact-score">
                                                    <i class="fa fa-thermometer-half"></i>
                                                    <?php _e('Impact:', 'environmental-platform-core'); ?> <?php echo esc_html($env_impact_score); ?>/10
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="article-footer">
                                        <a href="<?php the_permalink(); ?>" class="read-more-btn">
                                            <?php _e('Read More', 'environmental-platform-core'); ?>
                                            <i class="fa fa-arrow-right"></i>
                                        </a>
                                        
                                        <div class="article-stats">
                                            <span class="author">
                                                <i class="fa fa-user"></i>
                                                <?php the_author(); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <div class="no-articles-found">
                            <i class="fa fa-leaf fa-3x"></i>
                            <h3><?php _e('No Articles Found', 'environmental-platform-core'); ?></h3>
                            <p><?php _e('No environmental articles match your current filters. Try adjusting your search criteria.', 'environmental-platform-core'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                <div class="pagination-wrapper">
                    <?php
                    echo paginate_links(array(
                        'prev_text' => '<i class="fa fa-chevron-left"></i> ' . __('Previous', 'environmental-platform-core'),
                        'next_text' => __('Next', 'environmental-platform-core') . ' <i class="fa fa-chevron-right"></i>',
                        'type' => 'list'
                    ));
                    ?>
                </div>
            </div>
            
            <!-- Sidebar -->
            <aside class="col-md-4">
                <div class="archive-sidebar">
                    <!-- Search Widget -->
                    <div class="sidebar-widget search-widget">
                        <h3><?php _e('Search Articles', 'environmental-platform-core'); ?></h3>
                        <form role="search" method="get" action="<?php echo home_url('/'); ?>">
                            <div class="search-form">
                                <input type="search" name="s" placeholder="<?php _e('Search environmental articles...', 'environmental-platform-core'); ?>" value="<?php echo get_search_query(); ?>">
                                <input type="hidden" name="post_type" value="env_article">
                                <button type="submit">
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Categories Widget -->
                    <?php
                    $categories = get_terms(array(
                        'taxonomy' => 'env_category',
                        'hide_empty' => true
                    ));
                    ?>
                    
                    <?php if (!empty($categories)) : ?>
                        <div class="sidebar-widget categories-widget">
                            <h3><?php _e('Categories', 'environmental-platform-core'); ?></h3>
                            <ul class="category-list">
                                <?php foreach ($categories as $category) : ?>
                                    <li>
                                        <a href="<?php echo get_term_link($category); ?>">
                                            <span class="category-name"><?php echo esc_html($category->name); ?></span>
                                            <span class="category-count"><?php echo $category->count; ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Featured Articles -->
                    <?php
                    $featured_articles = new WP_Query(array(
                        'post_type' => 'env_article',
                        'posts_per_page' => 3,
                        'meta_query' => array(
                            array(
                                'key' => '_featured_article',
                                'value' => '1',
                                'compare' => '='
                            )
                        )
                    ));
                    ?>
                    
                    <?php if ($featured_articles->have_posts()) : ?>
                        <div class="sidebar-widget featured-articles">
                            <h3><?php _e('Featured Articles', 'environmental-platform-core'); ?></h3>
                            <div class="featured-list">
                                <?php while ($featured_articles->have_posts()) : $featured_articles->the_post(); ?>
                                    <div class="featured-item">
                                        <?php if (has_post_thumbnail()) : ?>
                                            <div class="featured-image">
                                                <a href="<?php the_permalink(); ?>">
                                                    <?php the_post_thumbnail('thumbnail'); ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="featured-content">
                                            <h4>
                                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                            </h4>
                                            <span class="featured-date"><?php echo get_the_date(); ?></span>
                                        </div>
                                    </div>
                                <?php endwhile; wp_reset_postdata(); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Environmental Stats -->
                    <div class="sidebar-widget stats-widget">
                        <h3><?php _e('Platform Statistics', 'environmental-platform-core'); ?></h3>
                        <div class="stats-grid">
                            <?php
                            $article_count = wp_count_posts('env_article')->publish;
                            $report_count = wp_count_posts('env_report')->publish;
                            $event_count = wp_count_posts('env_event')->publish;
                            ?>
                            
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $article_count; ?></span>
                                <span class="stat-label"><?php _e('Articles', 'environmental-platform-core'); ?></span>
                            </div>
                            
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $report_count; ?></span>
                                <span class="stat-label"><?php _e('Reports', 'environmental-platform-core'); ?></span>
                            </div>
                            
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $event_count; ?></span>
                                <span class="stat-label"><?php _e('Events', 'environmental-platform-core'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

<style>
.env-articles-archive {
    padding: 40px 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
}

.archive-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 30px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.archive-title {
    color: #2c3e50;
    font-size: 3em;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
}

.archive-title i {
    color: #27ae60;
}

.archive-description {
    color: #666;
    font-size: 18px;
    max-width: 600px;
    margin: 0 auto;
    line-height: 1.6;
}

.archive-filters {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.filter-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #2c3e50;
    font-size: 14px;
}

.filter-group select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: white;
    font-size: 14px;
}

.filter-group select:focus {
    outline: none;
    border-color: #27ae60;
    box-shadow: 0 0 5px rgba(39, 174, 96, 0.3);
}

.articles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 30px;
    margin-bottom: 40px;
}

.article-card {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
}

.article-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.article-image {
    position: relative;
    overflow: hidden;
    height: 200px;
}

.article-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.article-card:hover .article-image img {
    transform: scale(1.05);
}

.impact-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
    color: white;
}

.impact-low { background: #4caf50; }
.impact-medium { background: #ff9800; }
.impact-high { background: #f44336; }
.impact-critical { background: #9c27b0; }

.article-content {
    padding: 20px;
}

.article-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    font-size: 12px;
}

.article-meta .category a {
    color: #27ae60;
    text-decoration: none;
    font-weight: bold;
}

.article-meta .date {
    color: #666;
    display: flex;
    align-items: center;
    gap: 5px;
}

.article-title {
    margin-bottom: 15px;
}

.article-title a {
    color: #2c3e50;
    text-decoration: none;
    font-size: 18px;
    font-weight: bold;
    line-height: 1.3;
    display: block;
    transition: color 0.3s;
}

.article-title a:hover {
    color: #27ae60;
}

.article-excerpt {
    color: #666;
    line-height: 1.6;
    margin-bottom: 15px;
}

.env-data-preview {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 5px;
    font-size: 12px;
}

.env-data-preview span {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #666;
}

.env-data-preview i {
    color: #27ae60;
}

.article-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 15px;
    border-top: 1px solid #f0f0f0;
}

.read-more-btn {
    background: #27ae60;
    color: white;
    padding: 8px 15px;
    border-radius: 20px;
    text-decoration: none;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: background 0.3s;
}

.read-more-btn:hover {
    background: #219a52;
    color: white;
}

.article-stats {
    display: flex;
    align-items: center;
    gap: 15px;
    font-size: 12px;
    color: #666;
}

.article-stats span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.no-articles-found {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    grid-column: 1 / -1;
}

.no-articles-found i {
    color: #27ae60;
    margin-bottom: 20px;
}

.no-articles-found h3 {
    color: #2c3e50;
    margin-bottom: 15px;
}

.pagination-wrapper {
    text-align: center;
    margin-top: 40px;
}

.pagination-wrapper .page-numbers {
    margin: 40px 0;
}

.pagination-wrapper .page-numbers li {
    display: inline-block;
    margin: 0 5px;
}

.pagination-wrapper .page-numbers a,
.pagination-wrapper .page-numbers span {
    display: block;
    padding: 10px 15px;
    background: white;
    color: #2c3e50;
    text-decoration: none;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    transition: all 0.3s;
}

.pagination-wrapper .page-numbers a:hover,
.pagination-wrapper .page-numbers .current {
    background: #27ae60;
    color: white;
}

.archive-sidebar {
    background: white;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    overflow: hidden;
}

.sidebar-widget {
    padding: 25px;
    border-bottom: 1px solid #f0f0f0;
}

.sidebar-widget:last-child {
    border-bottom: none;
}

.sidebar-widget h3 {
    color: #2c3e50;
    margin-bottom: 20px;
    font-size: 18px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.search-form {
    display: flex;
    border: 1px solid #ddd;
    border-radius: 25px;
    overflow: hidden;
}

.search-form input[type="search"] {
    flex: 1;
    padding: 12px 15px;
    border: none;
    outline: none;
    font-size: 14px;
}

.search-form button {
    background: #27ae60;
    border: none;
    padding: 12px 15px;
    color: white;
    cursor: pointer;
    transition: background 0.3s;
}

.search-form button:hover {
    background: #219a52;
}

.category-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.category-list li {
    margin-bottom: 10px;
}

.category-list a {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 15px;
    background: #f8f9fa;
    border-radius: 5px;
    text-decoration: none;
    color: #2c3e50;
    transition: all 0.3s;
}

.category-list a:hover {
    background: #27ae60;
    color: white;
}

.category-count {
    background: white;
    color: #666;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 12px;
}

.category-list a:hover .category-count {
    background: rgba(255,255,255,0.2);
    color: white;
}

.featured-list {
    space-y: 15px;
}

.featured-item {
    display: flex;
    gap: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f0f0f0;
    margin-bottom: 15px;
}

.featured-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.featured-image {
    flex-shrink: 0;
}

.featured-image img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 5px;
}

.featured-content h4 {
    margin: 0 0 5px 0;
    font-size: 14px;
    line-height: 1.3;
}

.featured-content a {
    color: #2c3e50;
    text-decoration: none;
    transition: color 0.3s;
}

.featured-content a:hover {
    color: #27ae60;
}

.featured-date {
    font-size: 12px;
    color: #666;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

.stat-item {
    text-align: center;
    padding: 15px;
    background: linear-gradient(135deg, #27ae60, #2ecc71);
    border-radius: 8px;
    color: white;
}

.stat-number {
    display: block;
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 12px;
    text-transform: uppercase;
    opacity: 0.9;
}

@media (max-width: 768px) {
    .archive-filters {
        grid-template-columns: 1fr;
    }
    
    .articles-grid {
        grid-template-columns: 1fr;
    }
    
    .archive-title {
        font-size: 2em;
        flex-direction: column;
        gap: 10px;
    }
    
    .article-footer {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }
    
    .env-data-preview {
        flex-direction: column;
        gap: 10px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Filter functionality
    $('#category-filter, #impact-filter, #sort-filter').on('change', function() {
        var categoryFilter = $('#category-filter').val();
        var impactFilter = $('#impact-filter').val();
        var sortFilter = $('#sort-filter').val();
        
        var url = new URL(window.location);
        
        if (categoryFilter) {
            url.searchParams.set('env_category', categoryFilter);
        } else {
            url.searchParams.delete('env_category');
        }
        
        if (impactFilter) {
            url.searchParams.set('impact_level', impactFilter);
        } else {
            url.searchParams.delete('impact_level');
        }
        
        if (sortFilter && sortFilter !== 'date') {
            url.searchParams.set('orderby', sortFilter);
        } else {
            url.searchParams.delete('orderby');
        }
        
        window.location.href = url.toString();
    });
    
    // Set initial filter values from URL
    var urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.has('env_category')) {
        $('#category-filter').val(urlParams.get('env_category'));
    }
    
    if (urlParams.has('impact_level')) {
        $('#impact-filter').val(urlParams.get('impact_level'));
    }
    
    if (urlParams.has('orderby')) {
        $('#sort-filter').val(urlParams.get('orderby'));
    }
    
    // Animate article cards on scroll
    function animateOnScroll() {
        $('.article-card').each(function() {
            var elementTop = $(this).offset().top;
            var elementBottom = elementTop + $(this).outerHeight();
            var viewportTop = $(window).scrollTop();
            var viewportBottom = viewportTop + $(window).height();
            
            if (elementBottom > viewportTop && elementTop < viewportBottom) {
                $(this).addClass('animate-in');
            }
        });
    }
    
    $(window).on('scroll', animateOnScroll);
    animateOnScroll(); // Initial check
});
</script>

<?php get_footer(); ?>
