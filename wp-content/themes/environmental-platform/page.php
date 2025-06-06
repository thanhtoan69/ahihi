<?php
/**
 * The template for displaying all pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site may use a
 * different template.
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
                    <?php
                    while ( have_posts() ) :
                        the_post();
                        ?>
                        <article id="post-<?php the_ID(); ?>" <?php post_class('page-content'); ?>>
                            
                            <!-- Environmental Page Header -->
                            <?php if ( has_post_thumbnail() ) : ?>
                                <div class="page-featured-image">
                                    <?php the_post_thumbnail('large', array('class' => 'img-fluid rounded')); ?>
                                </div>
                            <?php endif; ?>

                            <!-- Page Title -->
                            <header class="page-header">
                                <h1 class="page-title"><?php the_title(); ?></h1>
                                
                                <?php
                                // Show environmental score if available
                                $environmental_score = get_post_meta(get_the_ID(), '_environmental_score', true);
                                if ($environmental_score) {
                                    echo '<div class="environmental-score">';
                                    echo '<span class="score-label">Environmental Impact Score:</span>';
                                    echo '<span class="score-value score-' . esc_attr($environmental_score) . '">' . esc_html($environmental_score) . '/100</span>';
                                    echo '</div>';
                                }
                                ?>
                            </header>

                            <!-- Page Content -->
                            <div class="page-content">
                                <?php
                                the_content();

                                wp_link_pages(
                                    array(
                                        'before' => '<div class="page-links">' . esc_html__('Pages:', 'environmental-platform'),
                                        'after'  => '</div>',
                                    )
                                );
                                ?>
                            </div>

                            <!-- Environmental Action Buttons -->
                            <?php
                            $show_action_buttons = get_post_meta(get_the_ID(), '_show_environmental_actions', true);
                            if ($show_action_buttons) :
                            ?>
                                <div class="environmental-actions">
                                    <h3><?php esc_html_e('Take Environmental Action', 'environmental-platform'); ?></h3>
                                    <div class="action-buttons">
                                        <a href="<?php echo esc_url(home_url('/environmental-tips')); ?>" class="btn btn-primary">
                                            <i class="fas fa-leaf"></i> Get Eco Tips
                                        </a>
                                        <a href="<?php echo esc_url(home_url('/carbon-calculator')); ?>" class="btn btn-secondary">
                                            <i class="fas fa-calculator"></i> Calculate Carbon Footprint
                                        </a>
                                        <a href="<?php echo esc_url(home_url('/green-challenges')); ?>" class="btn btn-success">
                                            <i class="fas fa-trophy"></i> Join Challenges
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Social Sharing -->
                            <div class="social-sharing">
                                <h4><?php esc_html_e('Share this page:', 'environmental-platform'); ?></h4>
                                <div class="sharing-buttons">
                                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink()); ?>" 
                                       target="_blank" class="share-btn facebook" title="Share on Facebook">
                                        <i class="fab fa-facebook-f"></i>
                                    </a>
                                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode(get_the_title()); ?>" 
                                       target="_blank" class="share-btn twitter" title="Share on Twitter">
                                        <i class="fab fa-twitter"></i>
                                    </a>
                                    <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode(get_permalink()); ?>" 
                                       target="_blank" class="share-btn linkedin" title="Share on LinkedIn">
                                        <i class="fab fa-linkedin-in"></i>
                                    </a>
                                    <a href="https://api.whatsapp.com/send?text=<?php echo urlencode(get_the_title() . ' ' . get_permalink()); ?>" 
                                       target="_blank" class="share-btn whatsapp" title="Share on WhatsApp">
                                        <i class="fab fa-whatsapp"></i>
                                    </a>
                                </div>
                            </div>

                        </article>

                        <?php
                        // If comments are open or we have at least one comment, load up the comment template.
                        if ( comments_open() || get_comments_number() ) :
                            comments_template();
                        endif;
                        ?>

                        <?php
                    endwhile; // End of the loop.
                    ?>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4 col-md-12">
                    <aside id="secondary" class="widget-area">
                        
                        <!-- Environmental Quick Stats -->
                        <div class="widget environmental-quick-stats">
                            <h3 class="widget-title"><?php esc_html_e('Environmental Impact Today', 'environmental-platform'); ?></h3>
                            <div class="quick-stats">
                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i class="fas fa-leaf"></i>
                                    </div>
                                    <div class="stat-content">
                                        <span class="stat-number"><?php echo esc_html(get_option('daily_trees_planted', '1,247')); ?></span>
                                        <span class="stat-label"><?php esc_html_e('Trees Planted', 'environmental-platform'); ?></span>
                                    </div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i class="fas fa-recycle"></i>
                                    </div>
                                    <div class="stat-content">
                                        <span class="stat-number"><?php echo esc_html(get_option('daily_waste_recycled', '850')); ?></span>
                                        <span class="stat-label"><?php esc_html_e('KG Recycled', 'environmental-platform'); ?></span>
                                    </div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i class="fas fa-solar-panel"></i>
                                    </div>
                                    <div class="stat-content">
                                        <span class="stat-number"><?php echo esc_html(get_option('daily_energy_saved', '2,340')); ?></span>
                                        <span class="stat-label"><?php esc_html_e('kWh Saved', 'environmental-platform'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php
                        // Show page-specific sidebar if available
                        $page_sidebar = get_post_meta(get_the_ID(), '_custom_sidebar', true);
                        if ($page_sidebar && is_active_sidebar($page_sidebar)) {
                            dynamic_sidebar($page_sidebar);
                        } else {
                            // Default page sidebar
                            if (is_active_sidebar('page-sidebar')) {
                                dynamic_sidebar('page-sidebar');
                            }
                        }
                        ?>

                    </aside>
                </div>
            </div>
        </div>

    </main>
</div>

<?php get_footer(); ?>
