<?php
/**
 * The template for displaying single posts
 *
 * @package Environmental_Platform
 */

get_header(); ?>

<main id="main" class="main-content">
    <div class="container">
        <?php while ( have_posts() ) : the_post(); ?>
            
            <article id="post-<?php the_ID(); ?>" <?php post_class('single-post'); ?>>
                
                <!-- Post Header -->
                <header class="post-header">
                    <div class="post-meta">
                        <span class="post-date">
                            <i class="fas fa-calendar"></i>
                            <?php echo get_the_date(); ?>
                        </span>
                        <span class="post-category">
                            <i class="fas fa-tag"></i>
                            <?php the_category(', '); ?>
                        </span>
                        <span class="post-author">
                            <i class="fas fa-user"></i>
                            <?php the_author(); ?>
                        </span>
                        <?php if (get_post_meta(get_the_ID(), 'environmental_impact', true)): ?>
                            <span class="environmental-impact">
                                <i class="fas fa-leaf"></i>
                                Impact: <?php echo get_post_meta(get_the_ID(), 'environmental_impact', true); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <h1 class="post-title"><?php the_title(); ?></h1>
                    
                    <?php if (has_post_thumbnail()): ?>
                        <div class="post-thumbnail">
                            <?php the_post_thumbnail('full'); ?>
                        </div>
                    <?php endif; ?>
                </header>

                <!-- Post Content -->
                <div class="post-content">
                    <?php the_content(); ?>
                    
                    <!-- Environmental Data Widget -->
                    <?php $environmental_data = get_post_meta(get_the_ID(), 'environmental_data', true); ?>
                    <?php if ($environmental_data): ?>
                        <div class="environmental-data-widget">
                            <h3><i class="fas fa-chart-line"></i> Environmental Impact Data</h3>
                            <div class="data-grid">
                                <?php if (isset($environmental_data['co2_saved'])): ?>
                                    <div class="data-item">
                                        <span class="data-label">CO2 Saved</span>
                                        <span class="data-value"><?php echo $environmental_data['co2_saved']; ?>kg</span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (isset($environmental_data['energy_saved'])): ?>
                                    <div class="data-item">
                                        <span class="data-label">Energy Saved</span>
                                        <span class="data-value"><?php echo $environmental_data['energy_saved']; ?>kWh</span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (isset($environmental_data['water_saved'])): ?>
                                    <div class="data-item">
                                        <span class="data-label">Water Saved</span>
                                        <span class="data-value"><?php echo $environmental_data['water_saved']; ?>L</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Tags -->
                    <?php if (has_tag()): ?>
                        <div class="post-tags">
                            <h4>Tags:</h4>
                            <?php the_tags('<div class="tag-list">', ' ', '</div>'); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Environmental Actions -->
                    <?php $related_actions = get_post_meta(get_the_ID(), 'related_actions', true); ?>
                    <?php if ($related_actions): ?>
                        <div class="related-actions">
                            <h3><i class="fas fa-hand-holding-heart"></i> Take Action</h3>
                            <div class="actions-grid">
                                <?php foreach ($related_actions as $action): ?>
                                    <div class="action-card">
                                        <h4><?php echo esc_html($action['title']); ?></h4>
                                        <p><?php echo esc_html($action['description']); ?></p>
                                        <div class="action-impact">
                                            <span class="impact-points">+<?php echo $action['points']; ?> Green Points</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Post Navigation -->
                <nav class="post-navigation">
                    <div class="nav-links">
                        <?php
                        $prev_post = get_previous_post();
                        $next_post = get_next_post();
                        ?>
                        
                        <?php if ($prev_post): ?>
                            <div class="nav-previous">
                                <a href="<?php echo get_permalink($prev_post->ID); ?>" rel="prev">
                                    <span class="nav-subtitle">Previous Article</span>
                                    <span class="nav-title"><?php echo get_the_title($prev_post->ID); ?></span>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($next_post): ?>
                            <div class="nav-next">
                                <a href="<?php echo get_permalink($next_post->ID); ?>" rel="next">
                                    <span class="nav-subtitle">Next Article</span>
                                    <span class="nav-title"><?php echo get_the_title($next_post->ID); ?></span>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </nav>

                <!-- Comments Section -->
                <?php if (comments_open() || get_comments_number()): ?>
                    <div class="comments-section">
                        <?php comments_template(); ?>
                    </div>
                <?php endif; ?>

            </article>

        <?php endwhile; ?>
    </div>
</main>

<?php get_footer(); ?>
