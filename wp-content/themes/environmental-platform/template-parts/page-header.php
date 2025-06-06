<?php
/**
 * Template part for displaying page headers
 *
 * @package Environmental_Platform
 * @since 1.0.0
 */

$header_class = 'page-header';
$show_breadcrumbs = get_theme_mod('show_breadcrumbs', true);

// Add custom classes based on page type
if (is_front_page()) {
    $header_class .= ' front-page-header';
} elseif (is_category()) {
    $header_class .= ' category-header';
} elseif (is_archive()) {
    $header_class .= ' archive-header';
} elseif (is_search()) {
    $header_class .= ' search-header';
}
?>

<header class="<?php echo esc_attr($header_class); ?>">
    <div class="container">
        
        <?php if ($show_breadcrumbs && !is_front_page()) : ?>
            <nav class="breadcrumbs" aria-label="<?php esc_attr_e('Breadcrumb Navigation', 'environmental-platform'); ?>">
                <ol class="breadcrumb-list">
                    <li class="breadcrumb-item">
                        <a href="<?php echo esc_url(home_url('/')); ?>">
                            <span class="breadcrumb-icon">🏠</span>
                            <?php _e('Home', 'environmental-platform'); ?>
                        </a>
                    </li>
                    
                    <?php if (is_category()) : ?>
                        <li class="breadcrumb-item">
                            <a href="<?php echo esc_url(get_permalink(get_option('page_for_posts'))); ?>">
                                <?php _e('Blog', 'environmental-platform'); ?>
                            </a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            <?php single_cat_title(); ?>
                        </li>
                    <?php elseif (is_tag()) : ?>
                        <li class="breadcrumb-item">
                            <a href="<?php echo esc_url(get_permalink(get_option('page_for_posts'))); ?>">
                                <?php _e('Blog', 'environmental-platform'); ?>
                            </a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            <?php single_tag_title(); ?>
                        </li>
                    <?php elseif (is_archive()) : ?>
                        <li class="breadcrumb-item active" aria-current="page">
                            <?php the_archive_title(); ?>
                        </li>
                    <?php elseif (is_search()) : ?>
                        <li class="breadcrumb-item active" aria-current="page">
                            <?php printf(__('Search Results for: %s', 'environmental-platform'), get_search_query()); ?>
                        </li>
                    <?php elseif (is_page()) : ?>
                        <?php
                        $ancestors = get_post_ancestors(get_the_ID());
                        if ($ancestors) {
                            $ancestors = array_reverse($ancestors);
                            foreach ($ancestors as $ancestor_id) :
                        ?>
                            <li class="breadcrumb-item">
                                <a href="<?php echo esc_url(get_permalink($ancestor_id)); ?>">
                                    <?php echo get_the_title($ancestor_id); ?>
                                </a>
                            </li>
                        <?php
                            endforeach;
                        }
                        ?>
                        <li class="breadcrumb-item active" aria-current="page">
                            <?php the_title(); ?>
                        </li>
                    <?php elseif (is_single()) : ?>
                        <?php
                        $categories = get_the_category();
                        if ($categories) :
                            $category = $categories[0];
                        ?>
                            <li class="breadcrumb-item">
                                <a href="<?php echo esc_url(get_permalink(get_option('page_for_posts'))); ?>">
                                    <?php _e('Blog', 'environmental-platform'); ?>
                                </a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="<?php echo esc_url(get_category_link($category->term_id)); ?>">
                                    <?php echo esc_html($category->name); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="breadcrumb-item active" aria-current="page">
                            <?php the_title(); ?>
                        </li>
                    <?php endif; ?>
                </ol>
            </nav>
        <?php endif; ?>

        <div class="page-header-content">
            <?php if (is_front_page()) : ?>
                <!-- Front page hero content handled in hero.php template part -->
                
            <?php elseif (is_category()) : ?>
                <div class="category-header-content">
                    <div class="category-icon">
                        <span class="environmental-icon">🌱</span>
                    </div>
                    <h1 class="page-title"><?php single_cat_title(); ?></h1>
                    <?php if (category_description()) : ?>
                        <div class="category-description">
                            <?php echo category_description(); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="category-stats">
                        <?php
                        $category_id = get_queried_object_id();
                        $post_count = get_category($category_id)->count;
                        ?>
                        <span class="post-count">
                            <?php printf(_n('%d article', '%d articles', $post_count, 'environmental-platform'), $post_count); ?>
                        </span>
                    </div>
                </div>
                
            <?php elseif (is_tag()) : ?>
                <div class="tag-header-content">
                    <div class="tag-icon">
                        <span class="environmental-icon">🏷️</span>
                    </div>
                    <h1 class="page-title">
                        <?php printf(__('Tag: %s', 'environmental-platform'), single_tag_title('', false)); ?>
                    </h1>
                    <?php if (tag_description()) : ?>
                        <div class="tag-description">
                            <?php echo tag_description(); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
            <?php elseif (is_archive()) : ?>
                <div class="archive-header-content">
                    <div class="archive-icon">
                        <span class="environmental-icon">📚</span>
                    </div>
                    <h1 class="page-title"><?php the_archive_title(); ?></h1>
                    <?php if (get_the_archive_description()) : ?>
                        <div class="archive-description">
                            <?php the_archive_description(); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
            <?php elseif (is_search()) : ?>
                <div class="search-header-content">
                    <div class="search-icon">
                        <span class="environmental-icon">🔍</span>
                    </div>
                    <h1 class="page-title">
                        <?php printf(__('Search Results for: %s', 'environmental-platform'), get_search_query()); ?>
                    </h1>
                    <div class="search-stats">
                        <?php
                        global $wp_query;
                        $found_posts = $wp_query->found_posts;
                        ?>
                        <span class="results-count">
                            <?php printf(_n('%d result found', '%d results found', $found_posts, 'environmental-platform'), $found_posts); ?>
                        </span>
                    </div>
                    
                    <!-- Search form -->
                    <div class="search-form-container">
                        <?php get_search_form(); ?>
                    </div>
                </div>
                
            <?php elseif (is_page()) : ?>
                <div class="page-header-content">
                    <h1 class="page-title"><?php the_title(); ?></h1>
                    <?php if (has_excerpt()) : ?>
                        <div class="page-excerpt">
                            <?php the_excerpt(); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
            <?php elseif (is_single()) : ?>
                <div class="single-header-content">
                    <div class="post-meta">
                        <span class="post-date">
                            <i class="icon-calendar"></i>
                            <?php echo get_the_date(); ?>
                        </span>
                        <span class="reading-time">
                            <i class="icon-clock"></i>
                            <?php echo environmental_platform_get_reading_time(); ?>
                        </span>
                        <?php if (get_the_category()) : ?>
                            <span class="post-category">
                                <i class="icon-folder"></i>
                                <?php the_category(', '); ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php
                        // Display environmental score if available
                        $environmental_score = get_post_meta(get_the_ID(), '_environmental_score', true);
                        if ($environmental_score) :
                        ?>
                            <span class="environmental-score">
                                <?php echo environmental_platform_display_environmental_score($environmental_score, false); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <h1 class="page-title"><?php the_title(); ?></h1>
                    
                    <div class="post-author-info">
                        <div class="author-avatar">
                            <?php echo get_avatar(get_the_author_meta('ID'), 48); ?>
                        </div>
                        <div class="author-details">
                            <span class="author-name">
                                <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>">
                                    <?php the_author(); ?>
                                </a>
                            </span>
                            <span class="author-title">
                                <?php echo get_the_author_meta('description') ? get_the_author_meta('description') : __('Environmental Writer', 'environmental-platform'); ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Environmental Alert Banner -->
        <?php 
        $alert_text = get_theme_mod('environmental_alert_text', '');
        if ($alert_text && !is_admin()) :
        ?>
            <div class="environmental-alert-banner">
                <div class="alert-content">
                    <span class="alert-icon">⚠️</span>
                    <span class="alert-text"><?php echo esc_html($alert_text); ?></span>
                    <button class="alert-close" aria-label="<?php esc_attr_e('Close alert', 'environmental-platform'); ?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</header>
