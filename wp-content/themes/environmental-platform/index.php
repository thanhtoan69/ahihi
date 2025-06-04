<?php
/**
 * The main template file for Environmental Platform theme
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 *
 * @package Environmental_Platform
 * @since 1.0.0
 */

get_header(); ?>

<main id="main" class="site-main">
    <div class="container">
        
        <?php if (have_posts()) : ?>
            
            <!-- Hero Section for Front Page -->
            <?php if (is_front_page()) : ?>
                <section class="hero-section">
                    <div class="container">
                        <div class="hero-content">
                            <h1><?php echo get_bloginfo('name'); ?></h1>
                            <p><?php echo get_bloginfo('description'); ?></p>
                            <div class="hero-buttons">
                                <a href="<?php echo esc_url(home_url('/about')); ?>" class="btn btn-primary">
                                    <?php _e('Learn More', 'environmental-platform'); ?>
                                </a>
                                <a href="<?php echo esc_url(home_url('/register')); ?>" class="btn btn-secondary">
                                    <?php _e('Join Community', 'environmental-platform'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Environmental Stats Section -->
                <section class="environmental-stats">
                    <div class="container">
                        <h2 class="text-center"><?php _e('Our Environmental Impact', 'environmental-platform'); ?></h2>
                        <div class="stats-grid">
                            <?php
                            // Get environmental statistics from database
                            global $wpdb;
                            $total_users = $wpdb->get_var("SELECT COUNT(*) FROM users WHERE status = 'active'") ?: 0;
                            $total_posts = $wpdb->get_var("SELECT COUNT(*) FROM posts WHERE status = 'published'") ?: 0;
                            $carbon_saved = $wpdb->get_var("SELECT SUM(carbon_impact_kg) FROM user_activities_comprehensive WHERE carbon_impact_kg > 0") ?: 0;
                            $waste_classified = $wpdb->get_var("SELECT COUNT(*) FROM waste_items WHERE status = 'classified'") ?: 0;
                            ?>
                            
                            <div class="stat-item fade-in-up">
                                <div class="stat-icon">🌱</div>
                                <span class="stat-number"><?php echo number_format($total_users); ?></span>
                                <div class="stat-label"><?php _e('Eco Warriors', 'environmental-platform'); ?></div>
                            </div>
                            
                            <div class="stat-item fade-in-up">
                                <div class="stat-icon">📚</div>
                                <span class="stat-number"><?php echo number_format($total_posts); ?></span>
                                <div class="stat-label"><?php _e('Environmental Articles', 'environmental-platform'); ?></div>
                            </div>
                            
                            <div class="stat-item fade-in-up">
                                <div class="stat-icon">🌍</div>
                                <span class="stat-number"><?php echo number_format($carbon_saved, 1); ?>kg</span>
                                <div class="stat-label"><?php _e('Carbon Saved', 'environmental-platform'); ?></div>
                            </div>
                            
                            <div class="stat-item fade-in-up">
                                <div class="stat-icon">♻️</div>
                                <span class="stat-number"><?php echo number_format($waste_classified); ?></span>
                                <div class="stat-label"><?php _e('Waste Items Classified', 'environmental-platform'); ?></div>
                            </div>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Posts Section -->
            <section class="posts-section">
                <div class="container">
                    <?php if (!is_front_page()) : ?>
                        <header class="page-header">
                            <h1 class="page-title">
                                <?php
                                if (is_category()) {
                                    single_cat_title();
                                } elseif (is_tag()) {
                                    single_tag_title();
                                } elseif (is_archive()) {
                                    the_archive_title();
                                } else {
                                    _e('Latest Environmental Content', 'environmental-platform');
                                }
                                ?>
                            </h1>
                            <?php if (is_category() || is_tag() || is_archive()) : ?>
                                <div class="archive-description">
                                    <?php the_archive_description(); ?>
                                </div>
                            <?php endif; ?>
                        </header>
                    <?php endif; ?>

                    <div class="posts-grid">
                        <?php
                        $post_count = 0;
                        while (have_posts()) :
                            the_post();
                            $post_count++;
                            ?>
                            
                            <article id="post-<?php the_ID(); ?>" <?php post_class('card fade-in-up'); ?>>
                                
                                <?php if (has_post_thumbnail()) : ?>
                                    <div class="card-image">
                                        <a href="<?php the_permalink(); ?>">
                                            <?php the_post_thumbnail('medium_large', array('loading' => 'lazy')); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <div class="card-content">
                                    <header class="card-header">
                                        <h2 class="card-title">
                                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        </h2>
                                        
                                        <div class="card-meta">
                                            <span class="post-date">
                                                <i class="icon-calendar"></i>
                                                <?php echo get_the_date(); ?>
                                            </span>
                                            <span class="post-author">
                                                <i class="icon-user"></i>
                                                <?php the_author(); ?>
                                            </span>
                                            <?php if (get_the_category()) : ?>
                                                <span class="post-category">
                                                    <i class="icon-folder"></i>
                                                    <?php the_category(', '); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </header>

                                    <div class="card-excerpt">
                                        <?php
                                        if (has_excerpt()) {
                                            the_excerpt();
                                        } else {
                                            echo wp_trim_words(get_the_content(), 30, '...');
                                        }
                                        ?>
                                    </div>

                                    <footer class="card-footer">
                                        <a href="<?php the_permalink(); ?>" class="btn btn-outline">
                                            <?php _e('Read More', 'environmental-platform'); ?>
                                        </a>
                                        
                                        <?php
                                        // Display environmental impact if available
                                        $environmental_score = get_post_meta(get_the_ID(), '_environmental_score', true);
                                        if ($environmental_score) :
                                        ?>
                                            <div class="environmental-impact-widget">
                                                <span class="green-text">
                                                    🌿 <?php printf(__('Environmental Score: %s', 'environmental-platform'), $environmental_score); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </footer>
                                </div>
                            </article>

                        <?php endwhile; ?>
                    </div>

                    <?php
                    // Pagination
                    the_posts_pagination(array(
                        'prev_text' => __('Previous', 'environmental-platform'),
                        'next_text' => __('Next', 'environmental-platform'),
                        'before_page_number' => '<span class="meta-nav screen-reader-text">' . __('Page', 'environmental-platform') . ' </span>',
                    ));
                    ?>
                </div>
            </section>

        <?php else : ?>
            
            <!-- No Posts Found -->
            <section class="no-results">
                <div class="container">
                    <div class="card text-center">
                        <h2><?php _e('Nothing Found', 'environmental-platform'); ?></h2>
                        <p><?php _e('It looks like nothing was found at this location. Maybe try a search?', 'environmental-platform'); ?></p>
                        <?php get_search_form(); ?>
                    </div>
                </div>
            </section>

        <?php endif; ?>

        <!-- Environmental Progress Section (Front Page Only) -->
        <?php if (is_front_page()) : ?>
            <section class="progress-section">
                <div class="container">
                    <h2 class="text-center"><?php _e('Community Environmental Goals', 'environmental-platform'); ?></h2>
                    
                    <?php
                    // Get progress data from database
                    $carbon_goal = 10000; // kg
                    $current_carbon = $carbon_saved;
                    $carbon_progress = min(($current_carbon / $carbon_goal) * 100, 100);
                    
                    $waste_goal = 5000;
                    $current_waste = $waste_classified;
                    $waste_progress = min(($current_waste / $waste_goal) * 100, 100);
                    ?>
                    
                    <div class="row">
                        <div class="col-6">
                            <h4><?php _e('Carbon Reduction Goal', 'environmental-platform'); ?></h4>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $carbon_progress; ?>%"></div>
                                <div class="progress-text">
                                    <?php printf(__('%s / %s kg', 'environmental-platform'), number_format($current_carbon), number_format($carbon_goal)); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-6">
                            <h4><?php _e('Waste Classification Goal', 'environmental-platform'); ?></h4>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $waste_progress; ?>%"></div>
                                <div class="progress-text">
                                    <?php printf(__('%s / %s items', 'environmental-platform'), number_format($current_waste), number_format($waste_goal)); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

    </div>
</main>

<?php get_footer(); ?>
