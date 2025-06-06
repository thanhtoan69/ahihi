<?php
/**
 * Template part for displaying single posts
 *
 * @package Environmental_Platform
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('single-post'); ?>>
    
    <!-- Post Header -->
    <header class="single-post-header">
        
        <!-- Categories -->
        <div class="single-categories">
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
                   class="single-category" 
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
        <h1 class="single-title"><?php the_title(); ?></h1>

        <!-- Post Meta -->
        <div class="single-meta">
            <div class="meta-row primary">
                <div class="author-info">
                    <div class="author-avatar">
                        <?php echo get_avatar(get_the_author_meta('ID'), 48); ?>
                    </div>
                    <div class="author-details">
                        <div class="author-name">
                            <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>">
                                <?php the_author(); ?>
                            </a>
                        </div>
                        <div class="author-level">
                            <?php
                            $author_level = get_user_meta(get_the_author_meta('ID'), '_environmental_level', true);
                            if ($author_level) {
                                echo esc_html(environmental_platform_get_user_level_name($author_level));
                            } else {
                                esc_html_e('Eco Beginner', 'environmental-platform');
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="meta-stats">
                    <div class="stat-item">
                        <i class="fas fa-calendar-alt"></i>
                        <div class="stat-content">
                            <span class="stat-label"><?php esc_html_e('Published', 'environmental-platform'); ?></span>
                            <time datetime="<?php echo esc_attr(get_the_date(DATE_W3C)); ?>">
                                <?php echo esc_html(get_the_date()); ?>
                            </time>
                        </div>
                    </div>
                    
                    <div class="stat-item">
                        <i class="fas fa-clock"></i>
                        <div class="stat-content">
                            <span class="stat-label"><?php esc_html_e('Reading Time', 'environmental-platform'); ?></span>
                            <span class="stat-value"><?php echo esc_html(environmental_platform_get_reading_time()); ?> min</span>
                        </div>
                    </div>
                    
                    <div class="stat-item">
                        <i class="fas fa-eye"></i>
                        <div class="stat-content">
                            <span class="stat-label"><?php esc_html_e('Views', 'environmental-platform'); ?></span>
                            <span class="stat-value"><?php echo esc_html(get_post_meta(get_the_ID(), '_post_views', true) ?: 0); ?></span>
                        </div>
                    </div>
                    
                    <?php
                    $environmental_score = get_post_meta(get_the_ID(), '_environmental_score', true);
                    if ($environmental_score) :
                    ?>
                        <div class="stat-item environmental">
                            <i class="fas fa-leaf"></i>
                            <div class="stat-content">
                                <span class="stat-label"><?php esc_html_e('Eco Score', 'environmental-platform'); ?></span>
                                <span class="stat-value environmental-score"><?php echo esc_html($environmental_score); ?>/100</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Social Sharing -->
            <div class="meta-row secondary">
                <div class="social-sharing">
                    <span class="sharing-label"><?php esc_html_e('Share:', 'environmental-platform'); ?></span>
                    <div class="sharing-buttons">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink()); ?>" 
                           target="_blank" class="share-btn facebook" title="<?php esc_attr_e('Share on Facebook', 'environmental-platform'); ?>">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode(get_the_title()); ?>" 
                           target="_blank" class="share-btn twitter" title="<?php esc_attr_e('Share on Twitter', 'environmental-platform'); ?>">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode(get_permalink()); ?>" 
                           target="_blank" class="share-btn linkedin" title="<?php esc_attr_e('Share on LinkedIn', 'environmental-platform'); ?>">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="https://api.whatsapp.com/send?text=<?php echo urlencode(get_the_title() . ' ' . get_permalink()); ?>" 
                           target="_blank" class="share-btn whatsapp" title="<?php esc_attr_e('Share on WhatsApp', 'environmental-platform'); ?>">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <button class="share-btn copy-link" data-url="<?php echo esc_url(get_permalink()); ?>" 
                                title="<?php esc_attr_e('Copy Link', 'environmental-platform'); ?>">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                </div>
                
                <div class="post-actions">
                    <button class="action-btn like-btn" data-post-id="<?php the_ID(); ?>">
                        <i class="fas fa-heart"></i>
                        <span class="like-count"><?php echo esc_html(get_post_meta(get_the_ID(), '_likes_count', true) ?: 0); ?></span>
                        <span class="like-text"><?php esc_html_e('Like', 'environmental-platform'); ?></span>
                    </button>
                    
                    <?php if (is_user_logged_in()) : ?>
                        <button class="action-btn bookmark-btn" data-post-id="<?php the_ID(); ?>">
                            <i class="fas fa-bookmark"></i>
                            <span class="bookmark-text"><?php esc_html_e('Save', 'environmental-platform'); ?></span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Featured Image -->
    <?php if (has_post_thumbnail()) : ?>
        <div class="single-featured-image">
            <?php the_post_thumbnail('large', array('class' => 'img-fluid')); ?>
            
            <?php
            $caption = get_the_post_thumbnail_caption();
            if ($caption) :
            ?>
                <div class="image-caption">
                    <?php echo wp_kses_post($caption); ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Post Content -->
    <div class="single-content">
        <?php
        the_content();

        wp_link_pages(
            array(
                'before' => '<div class="page-links"><span class="page-links-title">' . esc_html__('Pages:', 'environmental-platform') . '</span>',
                'after'  => '</div>',
            )
        );
        ?>
    </div>

    <!-- Environmental Impact Section -->
    <?php if ($environmental_score) : ?>
        <div class="environmental-impact-section">
            <h3><?php esc_html_e('Environmental Impact Analysis', 'environmental-platform'); ?></h3>
            
            <div class="impact-grid">
                <div class="impact-card main-score">
                    <div class="impact-icon">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <div class="impact-content">
                        <div class="impact-score"><?php echo esc_html($environmental_score); ?>/100</div>
                        <div class="impact-label"><?php esc_html_e('Environmental Score', 'environmental-platform'); ?></div>
                        <div class="impact-description">
                            <?php echo esc_html(environmental_platform_get_environmental_score_text($environmental_score)); ?>
                        </div>
                    </div>
                </div>
                
                <?php
                $carbon_impact = get_post_meta(get_the_ID(), '_carbon_impact', true);
                if ($carbon_impact) :
                ?>
                    <div class="impact-card">
                        <div class="impact-icon">
                            <i class="fas fa-cloud"></i>
                        </div>
                        <div class="impact-content">
                            <div class="impact-value"><?php echo esc_html($carbon_impact); ?> kg</div>
                            <div class="impact-label"><?php esc_html_e('CO₂ Impact', 'environmental-platform'); ?></div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php
                $water_impact = get_post_meta(get_the_ID(), '_water_impact', true);
                if ($water_impact) :
                ?>
                    <div class="impact-card">
                        <div class="impact-icon">
                            <i class="fas fa-tint"></i>
                        </div>
                        <div class="impact-content">
                            <div class="impact-value"><?php echo esc_html($water_impact); ?> L</div>
                            <div class="impact-label"><?php esc_html_e('Water Saved', 'environmental-platform'); ?></div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php
                $energy_impact = get_post_meta(get_the_ID(), '_energy_impact', true);
                if ($energy_impact) :
                ?>
                    <div class="impact-card">
                        <div class="impact-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div class="impact-content">
                            <div class="impact-value"><?php echo esc_html($energy_impact); ?> kWh</div>
                            <div class="impact-label"><?php esc_html_e('Energy Saved', 'environmental-platform'); ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Post Footer -->
    <footer class="single-post-footer">
        
        <!-- Tags -->
        <?php
        $tags = get_the_tags();
        if ($tags) :
        ?>
            <div class="post-tags-section">
                <h4><?php esc_html_e('Tags', 'environmental-platform'); ?></h4>
                <div class="post-tags">
                    <?php
                    foreach ($tags as $tag) :
                        echo '<a href="' . esc_url(get_tag_link($tag->term_id)) . '" class="post-tag">' . esc_html($tag->name) . '</a>';
                    endforeach;
                    ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Post Navigation -->
        <nav class="post-navigation">
            <?php
            $prev_post = get_previous_post();
            $next_post = get_next_post();
            ?>
            
            <?php if ($prev_post) : ?>
                <div class="nav-previous">
                    <div class="nav-label"><?php esc_html_e('Previous Article', 'environmental-platform'); ?></div>
                    <a href="<?php echo esc_url(get_permalink($prev_post->ID)); ?>" class="nav-link">
                        <i class="fas fa-chevron-left"></i>
                        <span class="nav-title"><?php echo esc_html($prev_post->post_title); ?></span>
                    </a>
                </div>
            <?php endif; ?>
            
            <?php if ($next_post) : ?>
                <div class="nav-next">
                    <div class="nav-label"><?php esc_html_e('Next Article', 'environmental-platform'); ?></div>
                    <a href="<?php echo esc_url(get_permalink($next_post->ID)); ?>" class="nav-link">
                        <span class="nav-title"><?php echo esc_html($next_post->post_title); ?></span>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            <?php endif; ?>
        </nav>

        <!-- Author Bio -->
        <?php
        $author_bio = get_the_author_meta('description');
        if ($author_bio) :
        ?>
            <div class="author-bio-section">
                <div class="author-bio">
                    <div class="author-avatar-large">
                        <?php echo get_avatar(get_the_author_meta('ID'), 80); ?>
                    </div>
                    <div class="author-info">
                        <h4 class="author-name">
                            <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>">
                                <?php the_author(); ?>
                            </a>
                        </h4>
                        <div class="author-level">
                            <?php
                            $author_level = get_user_meta(get_the_author_meta('ID'), '_environmental_level', true);
                            if ($author_level) {
                                echo esc_html(environmental_platform_get_user_level_name($author_level));
                            }
                            ?>
                        </div>
                        <div class="author-description">
                            <?php echo wp_kses_post($author_bio); ?>
                        </div>
                        <div class="author-links">
                            <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>" class="author-link">
                                <i class="fas fa-user"></i> <?php esc_html_e('View All Posts', 'environmental-platform'); ?>
                            </a>
                            
                            <?php
                            $author_website = get_the_author_meta('url');
                            if ($author_website) :
                            ?>
                                <a href="<?php echo esc_url($author_website); ?>" class="author-link" target="_blank">
                                    <i class="fas fa-globe"></i> <?php esc_html_e('Website', 'environmental-platform'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </footer>

</article>
