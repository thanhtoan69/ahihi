<?php
/**
 * Template part for displaying posts in list view
 *
 * @package Environmental_Platform
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('post-list-item'); ?>>
    
    <div class="list-content">
        
        <!-- Post Header -->
        <header class="list-header">
            
            <!-- Categories -->
            <div class="list-categories">
                <?php
                $categories = get_the_category();
                if ($categories) :
                    $category = $categories[0]; // Show only first category
                    $cat_color = get_term_meta($category->term_id, '_category_color', true);
                    $cat_icon = get_term_meta($category->term_id, '_category_icon', true);
                    
                    if (!$cat_color) $cat_color = '#28a745';
                    if (!$cat_icon) $cat_icon = 'fas fa-leaf';
                ?>
                    <a href="<?php echo esc_url(get_category_link($category->term_id)); ?>" 
                       class="list-category" 
                       style="background-color: <?php echo esc_attr($cat_color); ?>">
                        <i class="<?php echo esc_attr($cat_icon); ?>"></i>
                        <?php echo esc_html($category->name); ?>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Title -->
            <h3 class="list-title">
                <a href="<?php the_permalink(); ?>" rel="bookmark">
                    <?php the_title(); ?>
                </a>
            </h3>

            <!-- Meta Information -->
            <div class="list-meta">
                <div class="meta-left">
                    <span class="meta-item author">
                        <?php echo get_avatar(get_the_author_meta('ID'), 24); ?>
                        <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>">
                            <?php the_author(); ?>
                        </a>
                    </span>
                    
                    <span class="meta-item date">
                        <i class="fas fa-calendar-alt"></i>
                        <time datetime="<?php echo esc_attr(get_the_date(DATE_W3C)); ?>">
                            <?php echo esc_html(get_the_date()); ?>
                        </time>
                    </span>
                </div>
                
                <div class="meta-right">
                    <span class="meta-item reading-time">
                        <i class="fas fa-clock"></i>
                        <?php echo esc_html(environmental_platform_get_reading_time()); ?> min
                    </span>
                    
                    <span class="meta-item comments">
                        <i class="fas fa-comments"></i>
                        <?php comments_number('0', '1', '%'); ?>
                    </span>
                    
                    <?php
                    $environmental_score = get_post_meta(get_the_ID(), '_environmental_score', true);
                    if ($environmental_score) :
                    ?>
                        <span class="meta-item environmental-score">
                            <i class="fas fa-leaf"></i>
                            <?php echo esc_html($environmental_score); ?>/100
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- Excerpt -->
        <div class="list-excerpt">
            <?php the_excerpt(); ?>
        </div>

        <!-- Tags -->
        <?php
        $tags = get_the_tags();
        if ($tags) :
        ?>
            <div class="list-tags">
                <?php
                $tag_count = 0;
                foreach ($tags as $tag) :
                    if ($tag_count >= 4) break; // Limit to 4 tags
                    echo '<a href="' . esc_url(get_tag_link($tag->term_id)) . '" class="list-tag">' . esc_html($tag->name) . '</a>';
                    $tag_count++;
                endforeach;
                ?>
            </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="list-actions">
            <a href="<?php the_permalink(); ?>" class="list-btn primary">
                <i class="fas fa-arrow-right"></i>
                <?php esc_html_e('Read Article', 'environmental-platform'); ?>
            </a>
            
            <div class="list-quick-actions">
                <button class="list-btn secondary like-btn" data-post-id="<?php the_ID(); ?>">
                    <i class="fas fa-heart"></i>
                    <span><?php echo esc_html(get_post_meta(get_the_ID(), '_likes_count', true) ?: 0); ?></span>
                </button>
                
                <button class="list-btn secondary share-btn" data-url="<?php echo esc_url(get_permalink()); ?>" data-title="<?php echo esc_attr(get_the_title()); ?>">
                    <i class="fas fa-share-alt"></i>
                </button>
                
                <?php if (is_user_logged_in()) : ?>
                    <button class="list-btn secondary bookmark-btn" data-post-id="<?php the_ID(); ?>">
                        <i class="fas fa-bookmark"></i>
                    </button>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Thumbnail -->
    <?php if (has_post_thumbnail()) : ?>
        <div class="list-thumbnail">
            <a href="<?php the_permalink(); ?>">
                <?php the_post_thumbnail('medium', array('class' => 'img-fluid')); ?>
                
                <!-- Environmental Score Badge -->
                <?php if ($environmental_score) : ?>
                    <div class="environmental-badge">
                        <span class="badge-score"><?php echo esc_html($environmental_score); ?></span>
                        <span class="badge-icon"><i class="fas fa-leaf"></i></span>
                    </div>
                <?php endif; ?>
            </a>
        </div>
    <?php endif; ?>

</article>
