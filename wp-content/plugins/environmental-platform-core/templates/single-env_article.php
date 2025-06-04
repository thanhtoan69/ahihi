<?php
/**
 * Single Environmental Article Template
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

get_header(); ?>

<div class="env-article-container">
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <?php while (have_posts()) : the_post(); ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class('env-article'); ?>>
                        <header class="article-header">
                            <h1 class="article-title"><?php the_title(); ?></h1>
                            
                            <div class="article-meta">
                                <span class="author">
                                    <i class="fa fa-user"></i>
                                    <?php _e('By', 'environmental-platform-core'); ?> 
                                    <?php the_author(); ?>
                                </span>
                                
                                <span class="date">
                                    <i class="fa fa-calendar"></i>
                                    <?php echo get_the_date(); ?>
                                </span>
                                
                                <?php if (has_term('', 'env_category')) : ?>
                                    <span class="categories">
                                        <i class="fa fa-folder"></i>
                                        <?php echo get_the_term_list(get_the_ID(), 'env_category', '', ', '); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if (has_term('', 'impact_level')) : ?>
                                    <span class="impact-level">
                                        <i class="fa fa-exclamation-triangle"></i>
                                        <?php _e('Impact Level:', 'environmental-platform-core'); ?>
                                        <?php echo get_the_term_list(get_the_ID(), 'impact_level', '', ', '); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </header>
                        
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="article-featured-image">
                                <?php the_post_thumbnail('large', array('class' => 'img-responsive')); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="article-content">
                            <?php the_content(); ?>
                        </div>
                        
                        <!-- Environmental Data Section -->
                        <?php 
                        $env_location = get_post_meta(get_the_ID(), '_env_location', true);
                        $env_impact_score = get_post_meta(get_the_ID(), '_env_impact_score', true);
                        $pollution_level = get_post_meta(get_the_ID(), '_pollution_level', true);
                        ?>
                        
                        <?php if ($env_location || $env_impact_score || $pollution_level) : ?>
                            <div class="environmental-data">
                                <h3><?php _e('Environmental Data', 'environmental-platform-core'); ?></h3>
                                <div class="env-data-grid">
                                    <?php if ($env_location) : ?>
                                        <div class="env-data-item">
                                            <label><?php _e('Location:', 'environmental-platform-core'); ?></label>
                                            <span><?php echo esc_html($env_location); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($env_impact_score) : ?>
                                        <div class="env-data-item">
                                            <label><?php _e('Impact Score:', 'environmental-platform-core'); ?></label>
                                            <span class="impact-score-<?php echo esc_attr($env_impact_score); ?>">
                                                <?php echo esc_html($env_impact_score); ?>/10
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($pollution_level) : ?>
                                        <div class="env-data-item">
                                            <label><?php _e('Pollution Level:', 'environmental-platform-core'); ?></label>
                                            <span class="pollution-level-<?php echo esc_attr(strtolower($pollution_level)); ?>">
                                                <?php echo esc_html($pollution_level); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Related Content -->
                        <?php 
                        $related_posts = get_post_meta(get_the_ID(), '_related_content', true);
                        if ($related_posts) : ?>
                            <div class="related-content">
                                <h3><?php _e('Related Articles', 'environmental-platform-core'); ?></h3>
                                <div class="related-posts-grid">
                                    <?php foreach ($related_posts as $related_id) : 
                                        $related_post = get_post($related_id);
                                        if ($related_post) : ?>
                                            <div class="related-post-item">
                                                <a href="<?php echo get_permalink($related_id); ?>">
                                                    <?php if (has_post_thumbnail($related_id)) : ?>
                                                        <?php echo get_the_post_thumbnail($related_id, 'thumbnail'); ?>
                                                    <?php endif; ?>
                                                    <h4><?php echo get_the_title($related_id); ?></h4>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <footer class="article-footer">
                            <?php if (has_term('', 'env_tag')) : ?>
                                <div class="article-tags">
                                    <i class="fa fa-tags"></i>
                                    <?php echo get_the_term_list(get_the_ID(), 'env_tag', '', ', '); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="article-sharing">
                                <h4><?php _e('Share this Article', 'environmental-platform-core'); ?></h4>
                                <div class="share-buttons">
                                    <a href="#" class="share-facebook" onclick="shareOnFacebook()">
                                        <i class="fa fa-facebook"></i> Facebook
                                    </a>
                                    <a href="#" class="share-twitter" onclick="shareOnTwitter()">
                                        <i class="fa fa-twitter"></i> Twitter
                                    </a>
                                    <a href="#" class="share-linkedin" onclick="shareOnLinkedIn()">
                                        <i class="fa fa-linkedin"></i> LinkedIn
                                    </a>
                                </div>
                            </div>
                        </footer>
                    </article>
                <?php endwhile; ?>
                
                <!-- Navigation -->
                <nav class="post-navigation">
                    <?php
                    $prev_post = get_previous_post();
                    $next_post = get_next_post();
                    ?>
                    
                    <?php if ($prev_post) : ?>
                        <div class="nav-previous">
                            <a href="<?php echo get_permalink($prev_post); ?>">
                                <span class="nav-subtitle"><?php _e('Previous Article', 'environmental-platform-core'); ?></span>
                                <span class="nav-title"><?php echo get_the_title($prev_post); ?></span>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($next_post) : ?>
                        <div class="nav-next">
                            <a href="<?php echo get_permalink($next_post); ?>">
                                <span class="nav-subtitle"><?php _e('Next Article', 'environmental-platform-core'); ?></span>
                                <span class="nav-title"><?php echo get_the_title($next_post); ?></span>
                            </a>
                        </div>
                    <?php endif; ?>
                </nav>
            </div>
            
            <!-- Sidebar -->
            <aside class="col-md-4">
                <div class="env-article-sidebar">
                    <!-- Recent Environmental Articles -->
                    <?php
                    $recent_articles = new WP_Query(array(
                        'post_type' => 'env_article',
                        'posts_per_page' => 5,
                        'post__not_in' => array(get_the_ID())
                    ));
                    ?>
                    
                    <?php if ($recent_articles->have_posts()) : ?>
                        <div class="sidebar-widget recent-articles">
                            <h3><?php _e('Recent Environmental Articles', 'environmental-platform-core'); ?></h3>
                            <ul class="recent-articles-list">
                                <?php while ($recent_articles->have_posts()) : $recent_articles->the_post(); ?>
                                    <li>
                                        <a href="<?php the_permalink(); ?>">
                                            <?php if (has_post_thumbnail()) : ?>
                                                <?php the_post_thumbnail('thumbnail'); ?>
                                            <?php endif; ?>
                                            <div class="article-info">
                                                <h4><?php the_title(); ?></h4>
                                                <span class="date"><?php echo get_the_date(); ?></span>
                                            </div>
                                        </a>
                                    </li>
                                <?php endwhile; wp_reset_postdata(); ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Environmental Categories -->
                    <?php
                    $env_categories = get_terms(array(
                        'taxonomy' => 'env_category',
                        'hide_empty' => true
                    ));
                    ?>
                    
                    <?php if (!empty($env_categories)) : ?>
                        <div class="sidebar-widget env-categories">
                            <h3><?php _e('Environmental Categories', 'environmental-platform-core'); ?></h3>
                            <ul class="category-list">
                                <?php foreach ($env_categories as $category) : ?>
                                    <li>
                                        <a href="<?php echo get_term_link($category); ?>">
                                            <?php echo esc_html($category->name); ?>
                                            <span class="count">(<?php echo $category->count; ?>)</span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    </div>
</div>

<style>
.env-article-container {
    padding: 20px 0;
    background: #f8f9fa;
}

.env-article {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 30px;
    margin-bottom: 30px;
}

.article-header {
    margin-bottom: 30px;
    border-bottom: 2px solid #27ae60;
    padding-bottom: 20px;
}

.article-title {
    color: #2c3e50;
    font-size: 2.5em;
    margin-bottom: 15px;
    line-height: 1.2;
}

.article-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    color: #666;
    font-size: 14px;
}

.article-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.article-meta i {
    color: #27ae60;
}

.article-featured-image {
    margin-bottom: 30px;
    border-radius: 8px;
    overflow: hidden;
}

.article-content {
    line-height: 1.8;
    font-size: 16px;
    color: #333;
}

.environmental-data {
    background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
    border: 1px solid #27ae60;
    border-radius: 8px;
    padding: 20px;
    margin: 30px 0;
}

.environmental-data h3 {
    color: #1b5e20;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.env-data-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.env-data-item {
    background: white;
    padding: 15px;
    border-radius: 6px;
    border-left: 4px solid #27ae60;
}

.env-data-item label {
    font-weight: bold;
    color: #1b5e20;
    display: block;
    margin-bottom: 5px;
}

.impact-score-1, .impact-score-2, .impact-score-3 { color: #4caf50; }
.impact-score-4, .impact-score-5, .impact-score-6 { color: #ff9800; }
.impact-score-7, .impact-score-8, .impact-score-9, .impact-score-10 { color: #f44336; }

.pollution-level-low { color: #4caf50; }
.pollution-level-medium { color: #ff9800; }
.pollution-level-high { color: #f44336; }

.related-content {
    margin: 30px 0;
    padding: 20px;
    background: #f5f5f5;
    border-radius: 8px;
}

.related-posts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.related-post-item {
    background: white;
    border-radius: 6px;
    overflow: hidden;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    transition: transform 0.3s;
}

.related-post-item:hover {
    transform: translateY(-3px);
}

.related-post-item a {
    text-decoration: none;
    color: inherit;
    display: block;
}

.related-post-item h4 {
    padding: 15px;
    margin: 0;
    font-size: 14px;
    color: #2c3e50;
}

.article-footer {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.article-tags {
    margin-bottom: 20px;
}

.article-tags i {
    color: #27ae60;
    margin-right: 5px;
}

.share-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.share-buttons a {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 8px 15px;
    border-radius: 20px;
    text-decoration: none;
    color: white;
    font-size: 14px;
    transition: transform 0.3s;
}

.share-buttons a:hover {
    transform: scale(1.05);
}

.share-facebook { background: #3b5998; }
.share-twitter { background: #1da1f2; }
.share-linkedin { background: #0077b5; }

.post-navigation {
    margin-top: 30px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.post-navigation a {
    display: block;
    padding: 20px;
    background: white;
    border-radius: 8px;
    text-decoration: none;
    color: #2c3e50;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    transition: transform 0.3s;
}

.post-navigation a:hover {
    transform: translateY(-3px);
}

.nav-subtitle {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    display: block;
    margin-bottom: 5px;
}

.nav-title {
    font-weight: bold;
    font-size: 16px;
    line-height: 1.3;
}

.env-article-sidebar {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.sidebar-widget {
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.sidebar-widget:last-child {
    border-bottom: none;
}

.sidebar-widget h3 {
    color: #2c3e50;
    margin-bottom: 15px;
    font-size: 18px;
}

.recent-articles-list, .category-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.recent-articles-list li {
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f0f0f0;
}

.recent-articles-list li:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.recent-articles-list a {
    display: flex;
    gap: 10px;
    text-decoration: none;
    color: inherit;
}

.recent-articles-list img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
}

.article-info h4 {
    margin: 0 0 5px 0;
    font-size: 14px;
    color: #2c3e50;
    line-height: 1.3;
}

.article-info .date {
    font-size: 12px;
    color: #666;
}

.category-list li {
    margin-bottom: 8px;
}

.category-list a {
    text-decoration: none;
    color: #2c3e50;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 5px 0;
    border-bottom: 1px solid #f0f0f0;
    transition: color 0.3s;
}

.category-list a:hover {
    color: #27ae60;
}

.category-list .count {
    font-size: 12px;
    color: #666;
}

@media (max-width: 768px) {
    .article-meta {
        flex-direction: column;
        gap: 10px;
    }
    
    .env-data-grid {
        grid-template-columns: 1fr;
    }
    
    .related-posts-grid {
        grid-template-columns: 1fr;
    }
    
    .post-navigation {
        grid-template-columns: 1fr;
    }
    
    .share-buttons {
        justify-content: center;
    }
}
</style>

<script>
function shareOnFacebook() {
    window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(window.location.href), '_blank', 'width=600,height=400');
}

function shareOnTwitter() {
    var text = '<?php echo esc_js(get_the_title()); ?>';
    window.open('https://twitter.com/intent/tweet?text=' + encodeURIComponent(text) + '&url=' + encodeURIComponent(window.location.href), '_blank', 'width=600,height=400');
}

function shareOnLinkedIn() {
    window.open('https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(window.location.href), '_blank', 'width=600,height=400');
}
</script>

<?php get_footer(); ?>
