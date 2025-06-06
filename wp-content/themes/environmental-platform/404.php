<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @package Environmental_Platform
 */

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main error-404">

        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-10 text-center">
                    
                    <!-- 404 Hero Section -->
                    <div class="error-hero">
                        <div class="error-animation">
                            <div class="earth-icon">
                                <i class="fas fa-globe-americas"></i>
                            </div>
                            <div class="error-number">404</div>
                            <div class="error-leaves">
                                <i class="fas fa-leaf leaf-1"></i>
                                <i class="fas fa-leaf leaf-2"></i>
                                <i class="fas fa-leaf leaf-3"></i>
                            </div>
                        </div>
                        
                        <h1 class="error-title">
                            <?php esc_html_e('Oops! Page Not Found', 'environmental-platform'); ?>
                        </h1>
                        
                        <p class="error-description">
                            <?php esc_html_e('It looks like this page has gone off the grid! Like renewable energy, some things are harder to find but worth the search.', 'environmental-platform'); ?>
                        </p>
                    </div>

                    <!-- Environmental Message -->
                    <div class="environmental-message">
                        <div class="message-card">
                            <div class="message-icon">
                                <i class="fas fa-seedling"></i>
                            </div>
                            <h3><?php esc_html_e('Every Page Counts', 'environmental-platform'); ?></h3>
                            <p><?php esc_html_e('Just like every action for the environment matters, every page on our site has value. Let\'s help you find what you\'re looking for!', 'environmental-platform'); ?></p>
                        </div>
                    </div>

                    <!-- Search Section -->
                    <div class="error-search">
                        <h2><?php esc_html_e('Search Our Environmental Resources', 'environmental-platform'); ?></h2>
                        <form role="search" method="get" class="search-form error-search-form" action="<?php echo esc_url(home_url('/')); ?>">
                            <div class="search-input-group">
                                <input type="search" class="search-field" placeholder="<?php echo esc_attr__('Search for environmental tips, articles, and guides...', 'environmental-platform'); ?>" value="" name="s" />
                                <button type="submit" class="search-submit">
                                    <i class="fas fa-search"></i>
                                    <span class="screen-reader-text"><?php echo esc_html__('Search', 'environmental-platform'); ?></span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Quick Actions -->
                    <div class="error-actions">
                        <h3><?php esc_html_e('What Would You Like To Do?', 'environmental-platform'); ?></h3>
                        <div class="action-grid">
                            <a href="<?php echo esc_url(home_url('/')); ?>" class="action-card">
                                <div class="action-icon">
                                    <i class="fas fa-home"></i>
                                </div>
                                <h4><?php esc_html_e('Go Home', 'environmental-platform'); ?></h4>
                                <p><?php esc_html_e('Return to our homepage', 'environmental-platform'); ?></p>
                            </a>
                            
                            <a href="<?php echo esc_url(home_url('/environmental-tips')); ?>" class="action-card">
                                <div class="action-icon">
                                    <i class="fas fa-leaf"></i>
                                </div>
                                <h4><?php esc_html_e('Eco Tips', 'environmental-platform'); ?></h4>
                                <p><?php esc_html_e('Browse environmental tips', 'environmental-platform'); ?></p>
                            </a>
                            
                            <a href="<?php echo esc_url(home_url('/blog')); ?>" class="action-card">
                                <div class="action-icon">
                                    <i class="fas fa-newspaper"></i>
                                </div>
                                <h4><?php esc_html_e('Read Articles', 'environmental-platform'); ?></h4>
                                <p><?php esc_html_e('Explore our blog posts', 'environmental-platform'); ?></p>
                            </a>
                            
                            <a href="<?php echo esc_url(home_url('/carbon-calculator')); ?>" class="action-card">
                                <div class="action-icon">
                                    <i class="fas fa-calculator"></i>
                                </div>
                                <h4><?php esc_html_e('Carbon Calculator', 'environmental-platform'); ?></h4>
                                <p><?php esc_html_e('Calculate your footprint', 'environmental-platform'); ?></p>
                            </a>
                            
                            <a href="<?php echo esc_url(home_url('/green-challenges')); ?>" class="action-card">
                                <div class="action-icon">
                                    <i class="fas fa-trophy"></i>
                                </div>
                                <h4><?php esc_html_e('Challenges', 'environmental-platform'); ?></h4>
                                <p><?php esc_html_e('Join eco challenges', 'environmental-platform'); ?></p>
                            </a>
                            
                            <a href="<?php echo esc_url(home_url('/contact')); ?>" class="action-card">
                                <div class="action-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <h4><?php esc_html_e('Contact Us', 'environmental-platform'); ?></h4>
                                <p><?php esc_html_e('Get in touch', 'environmental-platform'); ?></p>
                            </a>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Popular Content Section -->
            <div class="row">
                <div class="col-12">
                    <div class="popular-content-section">
                        <h3 class="section-title text-center">
                            <?php esc_html_e('Popular Environmental Content', 'environmental-platform'); ?>
                        </h3>
                        
                        <div class="row">
                            <!-- Popular Posts -->
                            <div class="col-lg-4 col-md-6">
                                <div class="content-widget">
                                    <h4><i class="fas fa-fire"></i> <?php esc_html_e('Trending Articles', 'environmental-platform'); ?></h4>
                                    <?php
                                    $popular_posts = get_posts(array(
                                        'numberposts' => 5,
                                        'orderby' => 'comment_count',
                                        'order' => 'DESC',
                                        'post_status' => 'publish'
                                    ));
                                    
                                    if ($popular_posts) :
                                    ?>
                                        <ul class="popular-list">
                                            <?php foreach ($popular_posts as $post) : ?>
                                                <li>
                                                    <a href="<?php echo esc_url(get_permalink($post->ID)); ?>">
                                                        <i class="fas fa-leaf"></i>
                                                        <?php echo esc_html($post->post_title); ?>
                                                    </a>
                                                    <small><?php echo esc_html(get_comments_number($post->ID)); ?> comments</small>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; wp_reset_postdata(); ?>
                                </div>
                            </div>

                            <!-- Categories -->
                            <div class="col-lg-4 col-md-6">
                                <div class="content-widget">
                                    <h4><i class="fas fa-tags"></i> <?php esc_html_e('Browse Categories', 'environmental-platform'); ?></h4>
                                    <ul class="category-list">
                                        <?php
                                        $categories = get_categories(array(
                                            'orderby' => 'count',
                                            'order' => 'DESC',
                                            'number' => 8,
                                            'hide_empty' => true
                                        ));
                                        
                                        foreach ($categories as $category) :
                                            $icon_class = 'fas fa-leaf'; // Default icon
                                            
                                            // Assign specific icons
                                            if (strpos(strtolower($category->name), 'energy') !== false) {
                                                $icon_class = 'fas fa-bolt';
                                            } elseif (strpos(strtolower($category->name), 'water') !== false) {
                                                $icon_class = 'fas fa-tint';
                                            } elseif (strpos(strtolower($category->name), 'recycl') !== false) {
                                                $icon_class = 'fas fa-recycle';
                                            } elseif (strpos(strtolower($category->name), 'climate') !== false) {
                                                $icon_class = 'fas fa-globe';
                                            }
                                        ?>
                                            <li>
                                                <a href="<?php echo esc_url(get_category_link($category->term_id)); ?>">
                                                    <i class="<?php echo esc_attr($icon_class); ?>"></i>
                                                    <?php echo esc_html($category->name); ?>
                                                    <span class="count">(<?php echo esc_html($category->count); ?>)</span>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>

                            <!-- Environmental Stats -->
                            <div class="col-lg-4 col-md-12">
                                <div class="content-widget">
                                    <h4><i class="fas fa-chart-line"></i> <?php esc_html_e('Environmental Impact', 'environmental-platform'); ?></h4>
                                    <div class="impact-stats">
                                        <div class="stat-item">
                                            <div class="stat-icon">
                                                <i class="fas fa-tree"></i>
                                            </div>
                                            <div class="stat-content">
                                                <span class="stat-number"><?php echo esc_html(get_option('total_trees_planted', '12,458')); ?></span>
                                                <span class="stat-label"><?php esc_html_e('Trees Planted', 'environmental-platform'); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="stat-item">
                                            <div class="stat-icon">
                                                <i class="fas fa-recycle"></i>
                                            </div>
                                            <div class="stat-content">
                                                <span class="stat-number"><?php echo esc_html(get_option('total_waste_recycled', '8,750')); ?></span>
                                                <span class="stat-label"><?php esc_html_e('KG Recycled', 'environmental-platform'); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="stat-item">
                                            <div class="stat-icon">
                                                <i class="fas fa-users"></i>
                                            </div>
                                            <div class="stat-content">
                                                <span class="stat-number"><?php echo esc_html(get_option('active_users', '2,340')); ?></span>
                                                <span class="stat-label"><?php esc_html_e('Eco Warriors', 'environmental-platform'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

<style>
/* 404 Page Specific Styles */
.error-404 {
    padding: 60px 0;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9f7ef 100%);
    min-height: 70vh;
}

.error-hero {
    margin-bottom: 50px;
}

.error-animation {
    position: relative;
    margin-bottom: 30px;
}

.earth-icon {
    font-size: 120px;
    color: #28a745;
    margin-bottom: 20px;
    animation: float 3s ease-in-out infinite;
}

.error-number {
    font-size: 120px;
    font-weight: bold;
    color: #343a40;
    margin: 20px 0;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
}

.error-leaves {
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 200px;
    height: 200px;
}

.error-leaves i {
    position: absolute;
    color: #28a745;
    opacity: 0.7;
}

.leaf-1 {
    top: 20%;
    left: 10%;
    animation: leafFloat 4s ease-in-out infinite;
}

.leaf-2 {
    top: 50%;
    right: 15%;
    animation: leafFloat 4s ease-in-out infinite 1s;
}

.leaf-3 {
    bottom: 20%;
    left: 20%;
    animation: leafFloat 4s ease-in-out infinite 2s;
}

.error-title {
    font-size: 2.5rem;
    color: #343a40;
    margin-bottom: 20px;
}

.error-description {
    font-size: 1.2rem;
    color: #6c757d;
    max-width: 600px;
    margin: 0 auto;
}

.environmental-message {
    margin: 40px 0;
}

.message-card {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border-left: 5px solid #28a745;
}

.message-icon {
    font-size: 3rem;
    color: #28a745;
    margin-bottom: 15px;
}

.error-search {
    margin: 50px 0;
}

.error-search h2 {
    margin-bottom: 30px;
    color: #343a40;
}

.error-search-form .search-input-group {
    display: flex;
    max-width: 500px;
    margin: 0 auto;
    border-radius: 50px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.error-search-form .search-field {
    flex: 1;
    padding: 15px 25px;
    border: none;
    font-size: 1.1rem;
}

.error-search-form .search-submit {
    background: #28a745;
    color: white;
    border: none;
    padding: 15px 25px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.error-search-form .search-submit:hover {
    background: #218838;
}

.error-actions {
    margin: 50px 0;
}

.action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 30px;
}

.action-card {
    background: white;
    padding: 30px 20px;
    border-radius: 15px;
    text-align: center;
    text-decoration: none;
    color: #343a40;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: all 0.3s;
    border: 2px solid transparent;
}

.action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.15);
    border-color: #28a745;
    color: #343a40;
    text-decoration: none;
}

.action-icon {
    font-size: 2.5rem;
    color: #28a745;
    margin-bottom: 15px;
}

.action-card h4 {
    margin-bottom: 10px;
    font-size: 1.2rem;
}

.action-card p {
    font-size: 0.9rem;
    color: #6c757d;
    margin: 0;
}

.popular-content-section {
    margin-top: 80px;
    padding: 50px 0;
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.section-title {
    margin-bottom: 40px;
    color: #343a40;
    font-size: 2rem;
}

.content-widget {
    background: #f8f9fa;
    padding: 30px;
    border-radius: 15px;
    height: 100%;
    margin-bottom: 20px;
}

.content-widget h4 {
    color: #343a40;
    margin-bottom: 20px;
    font-size: 1.3rem;
}

.popular-list, .category-list {
    list-style: none;
    padding: 0;
}

.popular-list li, .category-list li {
    padding: 10px 0;
    border-bottom: 1px solid #e9ecef;
}

.popular-list li:last-child, .category-list li:last-child {
    border-bottom: none;
}

.popular-list a, .category-list a {
    text-decoration: none;
    color: #343a40;
    display: flex;
    align-items: center;
    transition: color 0.3s;
}

.popular-list a:hover, .category-list a:hover {
    color: #28a745;
}

.popular-list i, .category-list i {
    margin-right: 10px;
    color: #28a745;
}

.count {
    margin-left: auto;
    color: #6c757d;
    font-size: 0.9rem;
}

.impact-stats {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.stat-item {
    display: flex;
    align-items: center;
    padding: 15px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.stat-icon {
    font-size: 2rem;
    color: #28a745;
    margin-right: 15px;
}

.stat-content {
    display: flex;
    flex-direction: column;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: bold;
    color: #343a40;
}

.stat-label {
    font-size: 0.9rem;
    color: #6c757d;
}

/* Animations */
@keyframes float {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-20px);
    }
}

@keyframes leafFloat {
    0%, 100% {
        transform: translateY(0px) rotate(0deg);
    }
    25% {
        transform: translateY(-10px) rotate(5deg);
    }
    50% {
        transform: translateY(-5px) rotate(-5deg);
    }
    75% {
        transform: translateY(-15px) rotate(3deg);
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .error-number, .earth-icon {
        font-size: 80px;
    }
    
    .error-title {
        font-size: 2rem;
    }
    
    .action-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }
    
    .action-card {
        padding: 20px 15px;
    }
    
    .impact-stats {
        gap: 15px;
    }
}
</style>

<?php get_footer(); ?>
