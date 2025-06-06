<?php
/**
 * Template part for displaying posts
 *
 * @package Environmental_Platform
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('post-card'); ?> data-post-type="<?php echo esc_attr(get_post_type()); ?>">
    
    <!-- Post Thumbnail -->
    <?php if (has_post_thumbnail()) : ?>
        <div class="post-thumbnail">
            <a href="<?php the_permalink(); ?>" class="thumbnail-link">
                <?php the_post_thumbnail('medium_large', array('class' => 'img-fluid')); ?>
                
                <!-- Environmental Score Badge -->
                <?php
                $environmental_score = get_post_meta(get_the_ID(), '_environmental_score', true);
                if ($environmental_score) :
                ?>
                    <div class="environmental-badge">
                        <span class="badge-icon"><i class="fas fa-leaf"></i></span>
                        <span class="badge-score"><?php echo esc_html($environmental_score); ?></span>
                    </div>
                <?php endif; ?>
                
                <!-- Reading Time Badge -->
                <div class="reading-time-badge">
                    <i class="fas fa-clock"></i>
                    <?php echo esc_html(environmental_platform_get_reading_time()); ?> min
                </div>
            </a>
        </div>
    <?php endif; ?>

    <!-- Post Header -->
    <header class="post-header">
        
        <!-- Categories -->
        <div class="post-categories">
            <?php
            $categories = get_the_category();
            if ($categories) :
                foreach ($categories as $category) :
                    $cat_color = get_term_meta($category->term_id, '_category_color', true);
                    $cat_icon = get_term_meta($category->term_id, '_category_icon', true);
                    
                    if (!$cat_color) $cat_color = '#28a745';
                    if (!$cat_icon) $cat_icon = 'fas fa-leaf';
            ?>
                <a href="<?php echo esc_url(get_category_link($category->term_id)); ?>" 
                   class="post-category" 
                   style="background-color: <?php echo esc_attr($cat_color); ?>">
                    <i class="<?php echo esc_attr($cat_icon); ?>"></i>
                    <?php echo esc_html($category->name); ?>
                </a>
            <?php 
                endforeach;
            endif; 
            ?>
        </div>

        <!-- Post Title -->
        <?php
        if (is_singular()) :
            the_title('<h1 class="post-title">', '</h1>');
        else :
            the_title('<h2 class="post-title"><a href="' . esc_url(get_permalink()) . '" rel="bookmark">', '</a></h2>');
        endif;
        ?>

        <!-- Post Meta -->
        <div class="post-meta">
            <div class="meta-item author-meta">
                <div class="author-avatar">
                    <?php echo get_avatar(get_the_author_meta('ID'), 32); ?>
                </div>
                <div class="author-info">
                    <span class="author-name">
                        <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>">
                            <?php the_author(); ?>
                        </a>
                    </span>
                    <span class="author-level">
                        <?php
                        $author_level = get_user_meta(get_the_author_meta('ID'), '_environmental_level', true);
                        if ($author_level) {
                            echo esc_html(environmental_platform_get_user_level_name($author_level));
                        } else {
                            esc_html_e('Eco Beginner', 'environmental-platform');
                        }
                        ?>
                    </span>
                </div>
            </div>
            
            <div class="meta-item date-meta">
                <i class="fas fa-calendar-alt"></i>
                <time datetime="<?php echo esc_attr(get_the_date(DATE_W3C)); ?>">
                    <?php
                    printf(
                        esc_html_x('%s ago', '%s = human-readable time difference', 'environmental-platform'),
                        human_time_diff(get_the_time('U'), current_time('timestamp'))
                    );
                    ?>
                </time>
            </div>

            <div class="meta-item comments-meta">
                <i class="fas fa-comments"></i>
                <a href="<?php comments_link(); ?>">
                    <?php comments_number('0 comments', '1 comment', '% comments'); ?>
                </a>
            </div>

            <?php if ($environmental_score) : ?>
                <div class="meta-item environmental-meta">
                    <i class="fas fa-leaf"></i>
                    <span class="environmental-score-text">
                        <?php echo esc_html(environmental_platform_get_environmental_score_text($environmental_score)); ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Post Content -->
    <div class="post-content">
        <?php
        if (is_singular()) :
            the_content(
                sprintf(
                    wp_kses(
                        __('Continue reading<span class="screen-reader-text"> "%s"</span>', 'environmental-platform'),
                        array(
                            'span' => array(
                                'class' => array(),
                            ),
                        )
                    ),
                    wp_kses_post(get_the_title())
                )
            );

            wp_link_pages(
                array(
                    'before' => '<div class="page-links">' . esc_html__('Pages:', 'environmental-platform'),
                    'after'  => '</div>',
                )
            );
        else :
            the_excerpt();
        endif;
        ?>
    </div>

    <!-- Post Footer -->
    <footer class="post-footer">
        
        <!-- Tags -->
        <?php
        $tags = get_the_tags();
        if ($tags && !is_singular()) :
        ?>
            <div class="post-tags">
                <i class="fas fa-tags"></i>
                <?php
                foreach ($tags as $tag) :
                    echo '<a href="' . esc_url(get_tag_link($tag->term_id)) . '" class="post-tag">' . esc_html($tag->name) . '</a>';
                endforeach;
                ?>
            </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="post-actions">
            
            <!-- Like Button -->
            <button class="action-btn like-btn" data-post-id="<?php the_ID(); ?>">
                <i class="fas fa-heart"></i>
                <span class="like-count"><?php echo esc_html(get_post_meta(get_the_ID(), '_likes_count', true) ?: 0); ?></span>
            </button>

            <!-- Share Button -->
            <div class="share-dropdown">
                <button class="action-btn share-btn">
                    <i class="fas fa-share-alt"></i>
                    <span><?php esc_html_e('Share', 'environmental-platform'); ?></span>
                </button>
                <div class="share-menu">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink()); ?>" 
                       target="_blank" class="share-option facebook">
                        <i class="fab fa-facebook-f"></i> Facebook
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode(get_the_title()); ?>" 
                       target="_blank" class="share-option twitter">
                        <i class="fab fa-twitter"></i> Twitter
                    </a>
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode(get_permalink()); ?>" 
                       target="_blank" class="share-option linkedin">
                        <i class="fab fa-linkedin-in"></i> LinkedIn
                    </a>
                    <a href="https://api.whatsapp.com/send?text=<?php echo urlencode(get_the_title() . ' ' . get_permalink()); ?>" 
                       target="_blank" class="share-option whatsapp">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                </div>
            </div>

            <!-- Read More Button -->
            <?php if (!is_singular()) : ?>
                <a href="<?php the_permalink(); ?>" class="action-btn read-more-btn">
                    <i class="fas fa-arrow-right"></i>
                    <?php esc_html_e('Read More', 'environmental-platform'); ?>
                </a>
            <?php endif; ?>

            <!-- Environmental Impact Button -->
            <?php if ($environmental_score) : ?>
                <button class="action-btn impact-btn" data-toggle="tooltip" 
                        title="<?php esc_attr_e('View Environmental Impact Details', 'environmental-platform'); ?>">
                    <i class="fas fa-leaf"></i>
                    <span><?php echo esc_html($environmental_score); ?>/100</span>
                </button>
            <?php endif; ?>
        </div>

        <!-- Environmental Impact Preview -->
        <?php if (is_singular() && $environmental_score) : ?>
            <div class="environmental-impact-preview">
                <h4><?php esc_html_e('Environmental Impact', 'environmental-platform'); ?></h4>
                <div class="impact-metrics">
                    <div class="impact-item">
                        <div class="impact-icon">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <div class="impact-content">
                            <span class="impact-label"><?php esc_html_e('Eco Score', 'environmental-platform'); ?></span>
                            <span class="impact-value"><?php echo esc_html($environmental_score); ?>/100</span>
                        </div>
                    </div>
                    
                    <?php
                    $carbon_impact = get_post_meta(get_the_ID(), '_carbon_impact', true);
                    if ($carbon_impact) :
                    ?>
                        <div class="impact-item">
                            <div class="impact-icon">
                                <i class="fas fa-cloud"></i>
                            </div>
                            <div class="impact-content">
                                <span class="impact-label"><?php esc_html_e('Carbon Impact', 'environmental-platform'); ?></span>
                                <span class="impact-value"><?php echo esc_html($carbon_impact); ?> kg CO₂</span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php
                    $eco_tips_count = get_post_meta(get_the_ID(), '_eco_tips_count', true);
                    if ($eco_tips_count) :
                    ?>
                        <div class="impact-item">
                            <div class="impact-icon">
                                <i class="fas fa-lightbulb"></i>
                            </div>
                            <div class="impact-content">
                                <span class="impact-label"><?php esc_html_e('Eco Tips', 'environmental-platform'); ?></span>
                                <span class="impact-value"><?php echo esc_html($eco_tips_count); ?> tips</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </footer>

</article>
