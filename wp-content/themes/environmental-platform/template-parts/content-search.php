<?php
/**
 * Template part for displaying results in search pages
 *
 * @package Environmental_Platform
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('search-result-item'); ?> data-post-type="<?php echo esc_attr(get_post_type()); ?>">
    
    <div class="search-result-content">
        
        <!-- Post Type Badge -->
        <div class="result-type-badge">
            <?php
            $post_type = get_post_type();
            $post_type_obj = get_post_type_object($post_type);
            $icon = 'fas fa-file-alt';
            
            if ($post_type === 'post') {
                $icon = 'fas fa-newspaper';
            } elseif ($post_type === 'page') {
                $icon = 'fas fa-file';
            }
            ?>
            <i class="<?php echo esc_attr($icon); ?>"></i>
            <span><?php echo esc_html($post_type_obj->labels->singular_name); ?></span>
        </div>

        <!-- Result Header -->
        <header class="result-header">
            
            <!-- Categories (for posts) -->
            <?php if ($post_type === 'post') : ?>
                <div class="result-categories">
                    <?php
                    $categories = get_the_category();
                    if ($categories) :
                        foreach ($categories as $category) :
                            $cat_color = get_term_meta($category->term_id, '_category_color', true);
                            if (!$cat_color) $cat_color = '#28a745';
                    ?>
                        <a href="<?php echo esc_url(get_category_link($category->term_id)); ?>" 
                           class="result-category" 
                           style="background-color: <?php echo esc_attr($cat_color); ?>">
                            <?php echo esc_html($category->name); ?>
                        </a>
                    <?php 
                        endforeach;
                    endif; 
                    ?>
                </div>
            <?php endif; ?>

            <!-- Title -->
            <h3 class="result-title">
                <a href="<?php the_permalink(); ?>" rel="bookmark">
                    <?php
                    // Highlight search terms in title
                    $title = get_the_title();
                    $search_query = get_search_query();
                    if ($search_query) {
                        $title = preg_replace('/(' . preg_quote($search_query, '/') . ')/i', '<mark>$1</mark>', $title);
                    }
                    echo wp_kses($title, array('mark' => array()));
                    ?>
                </a>
            </h3>

            <!-- Meta Information -->
            <div class="result-meta">
                <span class="meta-item">
                    <i class="fas fa-user"></i>
                    <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>">
                        <?php the_author(); ?>
                    </a>
                </span>
                
                <span class="meta-item">
                    <i class="fas fa-calendar-alt"></i>
                    <time datetime="<?php echo esc_attr(get_the_date(DATE_W3C)); ?>">
                        <?php echo esc_html(get_the_date()); ?>
                    </time>
                </span>

                <span class="meta-item">
                    <i class="fas fa-clock"></i>
                    <?php echo esc_html(environmental_platform_get_reading_time()); ?> min read
                </span>

                <?php
                $environmental_score = get_post_meta(get_the_ID(), '_environmental_score', true);
                if ($environmental_score) :
                ?>
                    <span class="meta-item environmental-score">
                        <i class="fas fa-leaf"></i>
                        <span class="score-value"><?php echo esc_html($environmental_score); ?>/100</span>
                    </span>
                <?php endif; ?>
            </div>
        </header>

        <!-- Result Content -->
        <div class="result-content">
            <?php
            // Get excerpt and highlight search terms
            $excerpt = get_the_excerpt();
            $search_query = get_search_query();
            
            if ($search_query) {
                $excerpt = preg_replace('/(' . preg_quote($search_query, '/') . ')/i', '<mark>$1</mark>', $excerpt);
            }
            
            echo wp_kses($excerpt, array('mark' => array()));
            ?>
        </div>

        <!-- Result Footer -->
        <footer class="result-footer">
            
            <!-- Tags (for posts) -->
            <?php if ($post_type === 'post') : ?>
                <?php
                $tags = get_the_tags();
                if ($tags) :
                ?>
                    <div class="result-tags">
                        <i class="fas fa-tags"></i>
                        <?php
                        $tag_count = 0;
                        foreach ($tags as $tag) :
                            if ($tag_count >= 3) break; // Limit to 3 tags
                            echo '<a href="' . esc_url(get_tag_link($tag->term_id)) . '" class="result-tag">' . esc_html($tag->name) . '</a>';
                            $tag_count++;
                        endforeach;
                        ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="result-actions">
                <a href="<?php the_permalink(); ?>" class="result-btn primary">
                    <i class="fas fa-arrow-right"></i>
                    <?php esc_html_e('Read More', 'environmental-platform'); ?>
                </a>
                
                <!-- Quick Actions -->
                <div class="quick-actions">
                    <button class="result-btn quick-like" data-post-id="<?php the_ID(); ?>" title="<?php esc_attr_e('Like', 'environmental-platform'); ?>">
                        <i class="fas fa-heart"></i>
                        <span><?php echo esc_html(get_post_meta(get_the_ID(), '_likes_count', true) ?: 0); ?></span>
                    </button>
                    
                    <button class="result-btn quick-share" data-url="<?php echo esc_url(get_permalink()); ?>" data-title="<?php echo esc_attr(get_the_title()); ?>" title="<?php esc_attr_e('Share', 'environmental-platform'); ?>">
                        <i class="fas fa-share-alt"></i>
                    </button>
                    
                    <?php if (is_user_logged_in()) : ?>
                        <button class="result-btn quick-bookmark" data-post-id="<?php the_ID(); ?>" title="<?php esc_attr_e('Bookmark', 'environmental-platform'); ?>">
                            <i class="fas fa-bookmark"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </footer>

    </div>

    <!-- Thumbnail -->
    <?php if (has_post_thumbnail()) : ?>
        <div class="result-thumbnail">
            <a href="<?php the_permalink(); ?>">
                <?php the_post_thumbnail('medium', array('class' => 'img-fluid')); ?>
                
                <!-- Environmental Score Badge -->
                <?php if ($environmental_score) : ?>
                    <div class="environmental-badge">
                        <span class="badge-icon"><i class="fas fa-leaf"></i></span>
                        <span class="badge-score"><?php echo esc_html($environmental_score); ?></span>
                    </div>
                <?php endif; ?>
            </a>
        </div>
    <?php endif; ?>

</article>
